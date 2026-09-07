<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Core\Storage;

final class CacheStore
{
    private const JSON_FLAGS = JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR;

    private AtomicFileWriter $writer;
    private string $root;

    public function __construct(
        string $root,
        ?AtomicFileWriter $writer = null,
        ?string $lockRoot = null,
        int $lockTimeoutMilliseconds = 2000
    ) {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $lockRoot ??= dirname($root) . '/locks/' . basename($root);
        $this->writer = $writer ?? new AtomicFileWriter(
            $root,
            new FileLockManager($lockRoot, $lockTimeoutMilliseconds)
        );
        $this->root = $this->writer->root();

        $resolvedRoot = realpath($root);
        if ($resolvedRoot === false || rtrim(str_replace('\\', '/', $resolvedRoot), '/') !== $this->root) {
            throw new StorageException('Cache store and atomic writer roots do not match.');
        }
    }

    /** @return array<string|int, mixed>|null */
    public function readJson(string $key): ?array
    {
        $target = $this->pathFor($key, 'json');

        return $this->writer->synchronized($target, function () use ($target): ?array {
            if (!is_file($target)) {
                return null;
            }

            $contents = file_get_contents($target);
            if (!is_string($contents)) {
                throw new StorageException('Unable to read cache entry: ' . $target);
            }

            try {
                $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }

            return is_array($decoded) ? $decoded : null;
        });
    }

    /** @param array<string|int, mixed> $payload */
    public function writeJson(string $key, array $payload): void
    {
        try {
            $encoded = json_encode($payload, self::JSON_FLAGS) . PHP_EOL;
        } catch (\JsonException $exception) {
            throw new StorageException('Unable to encode cache entry: ' . $exception->getMessage(), 0, $exception);
        }

        $this->writer->write($this->pathFor($key, 'json'), $encoded, function (string $temporary): void {
            try {
                $decoded = json_decode((string) file_get_contents($temporary), true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new StorageException('Unable to validate cache entry: ' . $exception->getMessage(), 0, $exception);
            }

            if (!is_array($decoded)) {
                throw new StorageException('Cache JSON root must be an array or object.');
            }
        });
    }

    public function readText(string $key, string $extension = 'php'): ?string
    {
        $target = $this->pathFor($key, $extension);

        return $this->writer->synchronized($target, function () use ($target): ?string {
            if (!is_file($target)) {
                return null;
            }

            $contents = file_get_contents($target);
            if (!is_string($contents)) {
                throw new StorageException('Unable to read cache entry: ' . $target);
            }

            return $contents;
        });
    }

    public function writeText(string $key, string $contents, string $extension = 'php'): void
    {
        $this->writer->write($this->pathFor($key, $extension), $contents);
    }

    public function forget(string $key, string $extension = 'json'): bool
    {
        return $this->writer->delete($this->pathFor($key, $extension));
    }

    public function clear(string $extension = 'json'): void
    {
        $extension = $this->normalizeExtension($extension);
        $iterator = new \FilesystemIterator($this->root, \FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $entry) {
            if (!$entry->isFile() || $entry->isLink() || !str_ends_with($entry->getFilename(), '.' . $extension)) {
                continue;
            }

            $this->writer->delete($entry->getPathname());
        }
    }

    public function pathFor(string $key, string $extension = 'json'): string
    {
        $key = trim($key);
        if ($key === '' || str_contains($key, "\0")) {
            throw new StorageException('Cache key cannot be empty or contain null bytes.');
        }

        return $this->writer->resolvePath(md5($key) . '.' . $this->normalizeExtension($extension));
    }

    public function root(): string
    {
        return $this->root;
    }

    private function normalizeExtension(string $extension): string
    {
        $extension = strtolower(ltrim(trim($extension), '.'));
        if ($extension === '' || preg_match('/^[a-z0-9]+$/', $extension) !== 1) {
            throw new StorageException('Cache extension is invalid.');
        }

        return $extension;
    }
}
