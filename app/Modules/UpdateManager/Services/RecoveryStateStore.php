<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/UpdateManager/Services/RecoveryStateStore.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\JsonStore;
use App\Core\Storage\StorageException;
use App\Core\Storage\StoragePathGuard;

final class RecoveryStateStore
{
    private const ACTIVE = 'storage/recovery/active.json';
    private string $basePath;
    private ?JsonStore $json = null;
    private ?FileLockManager $locks = null;

    public function __construct(string $basePath)
    {
        $this->basePath = (new StoragePathGuard($basePath))->root();
    }

    public function exclusive(callable $operation): mixed
    {
        $this->initialize();
        return $this->locks->synchronized('recovery-state', $operation);
    }

    /** A missing journal is normal; an invalid existing journal never is. */
    public function read(): array
    {
        $path = (new StoragePathGuard($this->basePath))->resolve(self::ACTIVE);
        if (!file_exists($path)) { return []; }
        // Atomic generations keep status polling available during a long backup.
        // All read-modify-write callers still hold exclusive() around this read.
        $this->initialize();
        if (!$this->json->exists(self::ACTIVE)) { return []; }
        $state = $this->json->read(self::ACTIVE);
        $this->validate($state);
        return $state;
    }

    public function write(array $state): void
    {
        $this->validate($state);
        $this->exclusive(fn () => $this->json->write(self::ACTIVE, $state));
    }

    /** The callback receives the latest generation under the shared lock. */
    public function mutate(callable $change): array
    {
        return $this->exclusive(function () use ($change): array {
            $state = $this->read();
            if ($state === []) { return []; }
            $next = $change($state);
            $this->validate($next);
            $next['updated_at'] = gmdate('c');
            $this->json->write(self::ACTIVE, $next);
            return $next;
        });
    }

    public function deleteIf(callable $condition): bool
    {
        return $this->exclusive(function () use ($condition): bool {
            $state = $this->read();
            if ($state === [] || !$condition($state)) { return false; }
            $this->json->delete(self::ACTIVE);
            return true;
        });
    }

    public function archive(array $state): void
    {
        $this->validate($state);
        $id = (string) ($state['recovery_id'] ?? '');
        if (preg_match('/^[a-zA-Z0-9_-]+$/D', $id) !== 1) {
            throw new StorageException('update_recovery_id_invalid');
        }
        $this->exclusive(fn () => $this->json->write('storage/recovery/history/' . $id . '.json', $state));
    }

    private function initialize(): void
    {
        if ($this->json !== null) { return; }
        $paths = new StoragePathGuard($this->basePath, 0750);
        $lockRoot = $paths->ensureDirectory('storage/recovery/locks', 0750);
        $this->locks = new FileLockManager($lockRoot);
        $this->json = new JsonStore($this->basePath, new AtomicFileWriter($this->basePath, $this->locks, 0750, 0640));
    }

    private function validate(array $state): void
    {
        if (($state['kind'] ?? null) !== 'flatcms-recovery-state'
            || !in_array($state['status'] ?? null, ['armed', 'backup_ready', 'updating', 'monitoring',
                'failed', 'failed_post_update', 'restoring', 'recovery_failed', 'success', 'recovered', 'finalizing'], true)) {
            throw new StorageException('update_recovery_state_invalid');
        }
    }
}
