<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/UpdateManager/Services/RecoveryFinalizationService.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Core\RuntimeProbe;
use App\Core\Storage\ApplicationLock;
use App\Core\Storage\StoragePathGuard;

/** Explicit acceptance and resumable disposal of one owned recovery backup. */
final class RecoveryFinalizationService
{
    private StoragePathGuard $paths;
    private RecoveryStateStore $journal;

    public function __construct(string $basePath, private ?RuntimeProbe $probe = null, private ?\Closure $checkpoint = null)
    {
        $this->paths = new StoragePathGuard($basePath);
        $this->journal = new RecoveryStateStore($this->paths->root());
        $this->probe ??= new RuntimeProbe();
    }

    public function finalize(string $recoveryId, bool $operatorVerified): array
    {
        if (!$operatorVerified) { throw new \RuntimeException('update_finalization_confirmation_required'); }
        return ApplicationLock::for($this->paths->root())->exclusive(
            fn (): array => $this->journal->exclusive(fn (): array => $this->finalizeLocked($recoveryId))
        );
    }

    private function finalizeLocked(string $recoveryId): array
    {
        $state = $this->journal->read();
        if ($state === [] || ($state['recovery_id'] ?? null) !== $recoveryId) {
            throw new \RuntimeException('update_finalization_identity_invalid');
        }
        $files = $this->ownedPaths($state);
        $resuming = ($state['status'] ?? '') === 'finalizing';
        if (!$resuming && !in_array($state['status'] ?? '', ['monitoring', 'recovered'], true)) {
            throw new \RuntimeException('update_finalization_not_ready');
        }
        if ($resuming) {
            $plan = $this->validatePlan($state, $files);
        } else {
            (new RecoveryBackupReference($this->paths->root()))->verify($state);
            $receipt = $this->readReceipt($files[2]);
            foreach (['full_backup_id', 'full_backup_path', 'full_backup_key_path', 'full_backup_sha256',
                'full_backup_size_bytes', 'full_backup_files_count'] as $key) {
                if (!array_key_exists($key, $state) || ($receipt[$key] ?? null) !== $state[$key]) {
                    throw new \RuntimeException('update_finalization_identity_invalid');
                }
            }
            $result = $state['status'] === 'recovered' ? 'restored' : 'updated';
            $plan = ['version' => 1, 'result' => $result,
                'expected_version' => $state[$result === 'restored' ? 'from_version' : 'target_version'] ?? '',
                'acceptance' => 'operator-and-cli-login', 'verified_at' => gmdate('c'), 'files' => []];
            foreach ($files as $file) { $plan['files'][$file] = $this->fingerprint($file); }
        }
        // Validate every remaining entry before removing anything. No recursive deletion.
        $this->verifyWorkspace($state, $plan, $resuming);
        if (!$this->probe->check($this->paths->root(), (string) $plan['expected_version'])) {
            throw new \RuntimeException('update_finalization_health_failed');
        }
        if (!$resuming) {
            $state['status'] = 'finalizing';
            $state['finalization'] = $plan;
            $state['updated_at'] = gmdate('c');
            // Revoke recovery access before the first irreversible deletion.
            $state['token_hash'] = hash('sha256', random_bytes(32));
            $state['expires_at'] = 0;
            $this->journal->write($state);
            $this->notify('prepared');
        }
        foreach ($plan['files'] as $file => $fingerprint) {
            $path = $this->paths->resolve($file);
            if (!file_exists($path)) { continue; }
            if ($this->fingerprint($file) !== $fingerprint) { throw new \RuntimeException('update_finalization_conflict'); }
            if (!unlink($path)) { throw new \RuntimeException('update_finalization_cleanup_failed'); }
            $this->notify('file_removed');
        }
        foreach ([$state['backup_operation_root'] . '/keys', $state['backup_operation_root']] as $directory) {
            $path = $this->paths->resolve($directory);
            if (file_exists($path) && (!is_dir($path) || !rmdir($path))) {
                throw new \RuntimeException('update_finalization_cleanup_failed');
            }
        }
        $this->notify('workspace_removed');
        $result = ['kind' => 'flatcms-recovery-state', 'version' => 2, 'recovery_id' => $recoveryId,
            'status' => 'success', 'result' => $plan['result'], 'accepted_version' => $plan['expected_version'],
            'from_version' => $state['from_version'], 'target_version' => $state['target_version'],
            'verified_at' => $plan['verified_at'], 'closed_at' => gmdate('c'),
            'purged_files' => count($files), 'purged_bytes' => array_sum(array_column($plan['files'], 'size'))];
        // A history-write failure keeps the active plan, even after payload disposal.
        $this->journal->archive($result);
        if (!$this->journal->deleteIf(static fn (array $current): bool =>
            ($current['recovery_id'] ?? null) === $recoveryId && ($current['status'] ?? null) === 'finalizing')) {
            throw new \RuntimeException('update_finalization_conflict');
        }
        return $result;
    }

    private function ownedPaths(array $state): array
    {
        $id = $state['recovery_id'] ?? '';
        if (!is_string($id) || preg_match('/^[0-9]{14}-[a-f0-9]{12}$/D', $id) !== 1
            || ($state['version'] ?? null) !== 2 || ($state['backup_operation_started'] ?? null) !== true
            || ($state['full_backup_id'] ?? null) !== $id) {
            throw new \RuntimeException('update_finalization_identity_invalid');
        }
        $directory = 'storage/backups/upgrade-provisional-' . $id;
        $files = [$directory . '/flatcms-full-backup-' . $id . '.zip', $directory . '/keys/' . $id . '.key', $directory . '/ready.json'];
        if (($state['backup_operation_root'] ?? null) !== $directory
            || ($state['full_backup_path'] ?? null) !== $files[0] || ($state['full_backup_key_path'] ?? null) !== $files[1]) {
            // Legacy or user-retained archives have no disposable-ownership proof.
            throw new \RuntimeException('update_finalization_identity_invalid');
        }
        return $files;
    }

    private function validatePlan(array $state, array $files): array
    {
        $plan = $state['finalization'] ?? [];
        if (!is_array($plan)) { throw new \RuntimeException('update_finalization_identity_invalid'); }
        $expected = ($plan['result'] ?? '') === 'restored' ? ($state['from_version'] ?? null) : ($state['target_version'] ?? null);
        if (($plan['version'] ?? null) !== 1 || !in_array($plan['result'] ?? '', ['restored', 'updated'], true)
            || ($plan['expected_version'] ?? null) !== $expected || ($plan['acceptance'] ?? null) !== 'operator-and-cli-login'
            || !is_string($plan['verified_at'] ?? null) || !is_array($plan['files'] ?? null)
            || array_keys($plan['files']) !== $files) {
            throw new \RuntimeException('update_finalization_identity_invalid');
        }
        foreach ($plan['files'] as $fingerprint) {
            if (!is_array($fingerprint) || !is_int($fingerprint['size'] ?? null) || $fingerprint['size'] < 0
                || !is_string($fingerprint['sha256'] ?? null) || preg_match('/^[a-f0-9]{64}$/D', $fingerprint['sha256']) !== 1) {
                throw new \RuntimeException('update_finalization_identity_invalid');
            }
        }
        if ($plan['files'][$files[0]] !== ['size' => $state['full_backup_size_bytes'], 'sha256' => $state['full_backup_sha256']]) {
            throw new \RuntimeException('update_finalization_identity_invalid');
        }
        return $plan;
    }

    private function verifyWorkspace(array $state, array $plan, bool $allowMissing): void
    {
        $directory = $state['backup_operation_root'];
        $expected = [$directory => [basename($state['full_backup_path']), 'keys', 'ready.json'],
            $directory . '/keys' => [basename($state['full_backup_key_path'])]];
        foreach ($expected as $relative => $allowed) {
            $path = $this->paths->resolve($relative);
            if ($allowMissing && !file_exists($path)) { continue; }
            $entries = is_dir($path) ? scandir($path) : false;
            if ($entries === false || array_diff($entries, ['.', '..'], $allowed) !== []) {
                throw new \RuntimeException('update_finalization_conflict');
            }
        }
        foreach ($plan['files'] as $file => $fingerprint) {
            $path = $this->paths->resolve($file);
            if ($allowMissing && !file_exists($path)) { continue; }
            if ($this->fingerprint($file) !== $fingerprint) { throw new \RuntimeException('update_finalization_conflict'); }
        }
    }

    private function fingerprint(string $relative): array
    {
        $path = $this->paths->resolve($relative);
        clearstatcache(true, $path);
        $stat = lstat($path);
        if (!is_array($stat) || ($stat['mode'] & 0170000) !== 0100000 || $stat['nlink'] !== 1) {
            throw new \RuntimeException('update_finalization_conflict');
        }
        $hash = hash_file('sha256', $path);
        if (!is_string($hash)) { throw new \RuntimeException('update_finalization_conflict'); }
        return ['size' => $stat['size'], 'sha256' => $hash];
    }

    private function readReceipt(string $relative): array
    {
        if ($this->fingerprint($relative)['size'] > 8192) { throw new \RuntimeException('update_finalization_identity_invalid'); }
        $raw = file_get_contents($this->paths->resolve($relative));
        if (!is_string($raw) || strlen($raw) > 8192) { throw new \RuntimeException('update_finalization_identity_invalid'); }
        $receipt = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($receipt)) { throw new \RuntimeException('update_finalization_identity_invalid'); }
        return $receipt;
    }

    private function notify(string $phase): void
    {
        if ($this->checkpoint !== null) { ($this->checkpoint)($phase); }
    }
}
