<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core\Storage;

final class JsonStore
{
    private const JSON_FLAGS = JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR;

    /** @var array<string, array{identity: string, value: array<string|int, mixed>}> */
    private static array $requestCache = [];

    private string $root;
    private AtomicFileWriter $writer;

    public function __construct(string $root, ?AtomicFileWriter $writer = null, int $lockTimeoutMilliseconds = 2000)
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $lockRoot = dirname($root) . '/storage/cache/locks/data';
        $this->writer = $writer ?? new AtomicFileWriter(
            $root,
            new FileLockManager($lockRoot, $lockTimeoutMilliseconds)
        );
        $this->root = $this->writer->root();

        $resolvedRoot = realpath($root);
        if ($resolvedRoot === false || rtrim(str_replace('\\', '/', $resolvedRoot), '/') !== $this->root) {
            throw new StorageException('JSON store and atomic writer roots do not match.');
        }
    }

    /**
     * @param array<string|int, mixed> $default
     * @return array<string|int, mixed>
     */
    public function read(string $path, array $default = []): array
    {
        $target = $this->writer->resolvePath($path);
        if (!is_file($target)) {
            return $default;
        }

        $cacheKey = $this->cacheKey($target);
        $identity = $this->fileIdentity($target);
        if (isset(self::$requestCache[$cacheKey]) && self::$requestCache[$cacheKey]['identity'] === $identity) {
            return self::$requestCache[$cacheKey]['value'];
        }

        $value = $this->readFresh($target);
        self::$requestCache[$cacheKey] = [
            'identity' => $this->fileIdentity($target),
            'value' => $value,
        ];

        return $value;
    }

    /**
     * @param array<string|int, mixed> $default
     * @return array{data: array<string|int, mixed>, hash: string|null}
     */
    public function snapshot(string $path, array $default = []): array
    {
        $target = $this->writer->resolvePath($path);

        return $this->writer->synchronized($target, function () use ($target, $default): array {
            if (!is_file($target)) {
                if (file_exists($target) || is_link($target)) {
                    throw new StorageException('Storage snapshot target is not a regular file: ' . $target);
                }

                return ['data' => $default, 'hash' => null];
            }

            $contents = file_get_contents($target);
            if (!is_string($contents)) {
                throw new StorageException('Unable to read JSON file: ' . $target);
            }

            return [
                'data' => $this->decode($contents, $target),
                'hash' => hash('sha256', $contents),
            ];
        });
    }

    /**
     * @param array<string|int, mixed> $data
     */
    public function write(string $path, array $data): void
    {
        $target = $this->writer->resolvePath($path);
        $payload = $this->encode($data);

        $this->writer->write($target, $payload, function (string $temporary): void {
            $this->decode((string) file_get_contents($temporary), $temporary);
        });

        unset(self::$requestCache[$this->cacheKey($target)]);
    }

    /**
     * Replaces a JSON generation only when it still matches a prior snapshot.
     *
     * @param array<string|int, mixed> $data
     * @return string|null The new SHA-256 hash, or null when the source changed.
     */
    public function replaceIfUnchanged(string $path, array $data, ?string $expectedHash): ?string
    {
        $target = $this->writer->resolvePath($path);
        $payload = $this->encode($data);

        return $this->writer->synchronized($target, function () use ($target, $payload, $expectedHash): ?string {
            $actualHash = null;
            if (is_file($target)) {
                $contents = file_get_contents($target);
                if (!is_string($contents)) {
                    throw new StorageException('Unable to read JSON file: ' . $target);
                }

                $this->decode($contents, $target);
                $actualHash = hash('sha256', $contents);
            } elseif (file_exists($target) || is_link($target)) {
                throw new StorageException('Storage replacement target is not a regular file: ' . $target);
            }

            $matches = $expectedHash === null
                ? $actualHash === null
                : $actualHash !== null && hash_equals($expectedHash, $actualHash);
            if (!$matches) {
                return null;
            }

            $this->writer->write($target, $payload, function (string $temporary): void {
                $this->decode((string) file_get_contents($temporary), $temporary);
            });
            unset(self::$requestCache[$this->cacheKey($target)]);

            return hash('sha256', $payload);
        });
    }

    /**
     * @param callable(array<string|int, mixed>): array<string|int, mixed> $mutation
     * @param array<string|int, mixed> $default
     * @return array<string|int, mixed>
     */
    public function mutate(string $path, callable $mutation, array $default = []): array
    {
        $target = $this->writer->resolvePath($path);

        return $this->writer->synchronized($target, function () use ($target, $mutation, $default): array {
            $current = is_file($target) ? $this->readFresh($target) : $default;
            $updated = $mutation($current);
            if (!is_array($updated)) {
                throw new StorageException('JSON mutation must return an array: ' . $target);
            }

            $this->writer->write($target, $this->encode($updated), function (string $temporary): void {
                $this->decode((string) file_get_contents($temporary), $temporary);
            });
            unset(self::$requestCache[$this->cacheKey($target)]);

            return $updated;
        });
    }

    public function delete(string $path): bool
    {
        $target = $this->writer->resolvePath($path);
        $deleted = $this->writer->delete($target);
        unset(self::$requestCache[$this->cacheKey($target)]);

        return $deleted;
    }

    public function ensureDirectory(string $path): string
    {
        return $this->writer->ensureDirectory($path);
    }

    public function root(): string
    {
        return $this->writer->root();
    }

    public static function resetRequestCache(?string $root = null): void
    {
        if ($root === null) {
            self::$requestCache = [];
            return;
        }

        $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        foreach (array_keys(self::$requestCache) as $key) {
            if (str_starts_with($key, $root)) {
                unset(self::$requestCache[$key]);
            }
        }
    }

    /**
     * @return array<string|int, mixed>
     */
    private function readFresh(string $target): array
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $before = $this->fileIdentity($target);
            $contents = file_get_contents($target);
            if (!is_string($contents)) {
                throw new StorageException('Unable to read JSON file: ' . $target);
            }
            $after = $this->fileIdentity($target);
            if ($before === $after) {
                return $this->decode($contents, $target);
            }
        }

        throw new StorageException('JSON file changed repeatedly while being read: ' . $target);
    }

    /**
     * @param array<string|int, mixed> $data
     */
    private function encode(array $data): string
    {
        try {
            return json_encode($data, self::JSON_FLAGS) . PHP_EOL;
        } catch (\JsonException $exception) {
            throw new StorageException('Unable to encode JSON payload: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return array<string|int, mixed>
     */
    private function decode(string $contents, string $path): array
    {
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new StorageException('Malformed JSON file ' . $path . ': ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new StorageException('Canonical JSON root must be an array or object: ' . $path);
        }

        return $decoded;
    }

    private function fileIdentity(string $path): string
    {
        clearstatcache(true, $path);
        $stat = lstat($path);
        if (!is_array($stat) || !is_file($path) || is_link($path)) {
            throw new StorageException('Unable to identify canonical JSON file: ' . $path);
        }

        return implode(':', [
            (string) ($stat['dev'] ?? ''),
            (string) ($stat['ino'] ?? ''),
            (string) ($stat['size'] ?? ''),
            (string) ($stat['mtime'] ?? ''),
            (string) ($stat['ctime'] ?? ''),
        ]);
    }

    private function cacheKey(string $path): string
    {
        return $this->root . '/' . ltrim(substr($path, strlen($this->root)), '/');
    }
}
