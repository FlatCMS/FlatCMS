<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/Storage/JsonLineStore.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Core\Storage;

final class JsonLineStore
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    private AtomicFileWriter $writer;

    public function __construct(string $root, ?AtomicFileWriter $writer = null, int $lockTimeoutMilliseconds = 2000)
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $lockRoot = dirname($root) . '/storage/cache/locks/data';
        $this->writer = $writer ?? new AtomicFileWriter(
            $root,
            new FileLockManager($lockRoot, $lockTimeoutMilliseconds)
        );

        $resolvedRoot = realpath($root);
        if ($resolvedRoot === false || rtrim(str_replace('\\', '/', $resolvedRoot), '/') !== $this->writer->root()) {
            throw new StorageException('JSON line store and atomic writer roots do not match.');
        }
    }

    /**
     * @param array<string|int, mixed> $record
     */
    public function append(string $path, array $record): void
    {
        try {
            $line = json_encode($record, self::JSON_FLAGS) . PHP_EOL;
        } catch (\JsonException $exception) {
            throw new StorageException('Unable to encode JSON line payload: ' . $exception->getMessage(), 0, $exception);
        }

        $this->writer->append($path, $line, function (string $temporary): void {
            $this->validate($temporary);
        });
    }

    /** @return array<int, array<string|int, mixed>> */
    public function read(string $path): array
    {
        $target = $this->writer->resolvePath($path);

        return $this->writer->synchronized($target, function () use ($target): array {
            if (!is_file($target)) {
                return [];
            }

            $contents = file_get_contents($target);
            if (!is_string($contents)) {
                throw new StorageException('Unable to read JSON line storage: ' . $target);
            }

            return $this->decodeRecords($contents, $target);
        });
    }

    public function root(): string
    {
        return $this->writer->root();
    }

    private function validate(string $path): void
    {
        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            throw new StorageException('Unable to validate JSON line storage: ' . $path);
        }

        $this->decodeRecords($contents, $path);
    }

    /** @return array<int, array<string|int, mixed>> */
    private function decodeRecords(string $contents, string $path): array
    {
        $records = [];
        $lines = preg_split('/\R/', $contents) ?: [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            try {
                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new StorageException('Malformed JSON line in storage ' . $path . ': ' . $exception->getMessage(), 0, $exception);
            }

            if (!is_array($record)) {
                throw new StorageException('JSON line storage records must decode to arrays: ' . $path);
            }

            $records[] = $record;
        }

        return $records;
    }
}
