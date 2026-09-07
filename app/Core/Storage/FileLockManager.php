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

final class FileLockManager
{
    private string $lockRoot;
    private int $timeoutMilliseconds;

    /** @var array<string, array{handle: resource, depth: int}> */
    private array $heldLocks = [];

    public function __construct(string $lockRoot, int $timeoutMilliseconds = 2000)
    {
        $lockRoot = rtrim(str_replace('\\', '/', trim($lockRoot)), '/');
        if ($lockRoot === '' || !str_starts_with($lockRoot, '/') || str_contains($lockRoot, "\0")) {
            throw new StorageException('Lock root must be an absolute path.');
        }
        if ($timeoutMilliseconds < 1) {
            throw new StorageException('Lock timeout must be greater than zero.');
        }
        if (is_link($lockRoot)) {
            throw new StorageException('Lock root cannot be a symbolic link: ' . $lockRoot);
        }
        if (!is_dir($lockRoot) && !mkdir($lockRoot, 0755, true) && !is_dir($lockRoot)) {
            throw new StorageException('Unable to create lock root: ' . $lockRoot);
        }

        $resolved = realpath($lockRoot);
        if ($resolved === false) {
            throw new StorageException('Unable to resolve lock root: ' . $lockRoot);
        }

        $this->lockRoot = rtrim(str_replace('\\', '/', $resolved), '/');
        $this->timeoutMilliseconds = $timeoutMilliseconds;
    }

    public function synchronized(string $scope, callable $operation): mixed
    {
        $scope = trim($scope);
        if ($scope === '' || str_contains($scope, "\0")) {
            throw new StorageException('Lock scope cannot be empty or contain null bytes.');
        }

        $key = hash('sha256', $scope);
        if (isset($this->heldLocks[$key])) {
            $this->heldLocks[$key]['depth']++;
            try {
                return $operation();
            } finally {
                $this->heldLocks[$key]['depth']--;
            }
        }

        $lockPath = $this->lockRoot . '/' . $key . '.lock';
        $handle = fopen($lockPath, 'c+b');
        if (!is_resource($handle)) {
            throw new StorageException('Unable to open storage lock: ' . $lockPath);
        }
        @chmod($lockPath, 0600);

        $startedAt = hrtime(true);
        $timeoutNanoseconds = $this->timeoutMilliseconds * 1_000_000;
        $acquired = false;

        try {
            do {
                $acquired = flock($handle, LOCK_EX | LOCK_NB);
                if ($acquired) {
                    break;
                }
                usleep(10_000);
            } while ((hrtime(true) - $startedAt) < $timeoutNanoseconds);

            if (!$acquired) {
                throw new StorageException('Storage lock timeout for scope: ' . $scope);
            }

            $this->heldLocks[$key] = ['handle' => $handle, 'depth' => 1];
            return $operation();
        } finally {
            unset($this->heldLocks[$key]);
            if ($acquired) {
                flock($handle, LOCK_UN);
            }
            fclose($handle);
        }
    }
}
