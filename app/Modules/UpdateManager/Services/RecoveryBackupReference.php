<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/UpdateManager/Services/RecoveryBackupReference.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Core\Storage\StoragePathGuard;
use App\Modules\Backups\Services\FullBackupService;

/** Portable, verified reference shared with the autonomous recovery capsule. */
final class RecoveryBackupReference
{
    private StoragePathGuard $paths;

    public function __construct(string $basePath)
    {
        $this->paths = new StoragePathGuard($basePath);
    }

    public function path(string $path, bool $key = false): string
    {
        $relative = $this->paths->relative($path);
        $provisional = preg_match('~^storage/backups/upgrade-provisional-[0-9]{14}-[a-f0-9]{12}/~D', $relative) === 1;
        $legacy = str_starts_with($relative, $key ? 'storage/recovery/keys/' : 'storage/backups/full/');
        if ((!$provisional && !$legacy) || !str_ends_with($relative, $key ? '.key' : '.zip')) {
            throw new \RuntimeException('recovery_backup_path_invalid');
        }
        return $this->paths->resolve($relative);
    }

    /** Validate bytes, key and manifest, not just the presence of a path in JSON. */
    public function verify(array $state): array
    {
        $archive = $this->path((string) ($state['full_backup_path'] ?? ''));
        $key = $this->path((string) ($state['full_backup_key_path'] ?? ''), true);
        if (!is_file($archive) || !is_file($key)) { throw new \RuntimeException('recovery_backup_missing'); }
        $hash = hash_file('sha256', $archive);
        $expected = (string) ($state['full_backup_sha256'] ?? '');
        if (!is_string($hash) || preg_match('/^[a-f0-9]{64}$/D', $expected) !== 1 || !hash_equals($expected, $hash)) {
            throw new \RuntimeException('recovery_backup_hash_mismatch');
        }
        $size = (int) filesize($archive);
        if (isset($state['full_backup_size_bytes']) && $size !== (int) $state['full_backup_size_bytes']) {
            throw new \RuntimeException('recovery_backup_hash_mismatch');
        }
        $manifest = (new FullBackupService($this->paths->root()))->validateBackup($archive, $key);
        if (isset($state['full_backup_id']) && ($manifest['backup_id'] ?? '') !== $state['full_backup_id']) {
            throw new \RuntimeException('recovery_backup_identity_mismatch');
        }
        return ['path' => $archive, 'key_path' => $key, 'manifest' => $manifest];
    }

    public function describe(array $backup): array
    {
        return [
            'full_backup_id' => (string) $backup['id'],
            'full_backup_path' => $this->paths->relative($this->path((string) $backup['path'])),
            'full_backup_key_path' => $this->paths->relative($this->path((string) $backup['key_path'], true)),
            'full_backup_sha256' => (string) $backup['sha256'],
            'full_backup_size_bytes' => (int) $backup['size_bytes'],
            'full_backup_files_count' => (int) $backup['files_count'],
        ];
    }
}
