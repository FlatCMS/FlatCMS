<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/Storage/FileLockManager.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Core\Storage;

final class FileLockManager
{
    private StoragePathGuard $paths;
    private int $timeoutMilliseconds;

    /** @var array<string, array{handle: resource, depth: int}> */
    private array $heldLocks = [];

    public function __construct(string $lockRoot, int $timeoutMilliseconds = 2000)
    {
        if ($timeoutMilliseconds < 1) {
            throw new StorageException('Lock timeout must be greater than zero.');
        }
        $this->paths = new StoragePathGuard($lockRoot);
        $this->timeoutMilliseconds = $timeoutMilliseconds;
    }

    public function synchronized(string $scope, callable $operation): mixed
    {
        $scope = trim($scope);
        if ($scope === '' || str_contains($scope, "\0")) {
            throw new StorageException('Lock scope cannot be empty or contain null bytes.');
        }

        $key = hash('sha256', PHP_OS_FAMILY === 'Windows' ? StoragePathGuard::foldWindowsCase($scope) : $scope);
        if (isset($this->heldLocks[$key])) {
            $this->heldLocks[$key]['depth']++;
            try {
                return $operation();
            } finally {
                $this->heldLocks[$key]['depth']--;
            }
        }

        $lockPath = $this->paths->resolve($key . '.lock');
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
