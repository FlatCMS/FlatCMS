<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/UpdateManager/Services/RecoveryCapsuleService.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Modules\Backups\Services\FullBackupService;

final class RecoveryCapsuleService
{
    public const COOKIE_NAME = 'flatcms_recovery';
    private string $basePath;
    private string $recoveryRoot;
    private string $activePath;
    private RecoveryStateStore $journal;

    /** @var null|callable(string,string):void */
    private $backupCheckpoint;

    public function __construct(?string $basePath = null, ?callable $backupCheckpoint = null)
    {
        $this->basePath = rtrim($basePath ?: BASE_PATH, '/\\');
        $this->recoveryRoot = $this->basePath . '/storage/recovery';
        $this->activePath = $this->recoveryRoot . '/active.json';
        $this->journal = new RecoveryStateStore($this->basePath);
        $this->backupCheckpoint = $backupCheckpoint;
    }

    /** @return array<string,mixed> */
    public function arm(string $targetVersion): array
    {
        return $this->journal->exclusive(fn (): array => $this->armLocked($targetVersion));
    }

    private function armLocked(string $targetVersion): array
    {
        $existing = $this->readState();
        if ($existing !== []) {
            $status = (string) ($existing['status'] ?? '');
            $hasBackup = trim((string) ($existing['full_backup_path'] ?? '')) !== '';
            // Expiring an access token must never discard an unfinished recovery.
            $safeFailedWithoutBackup = $status === 'failed' && !$hasBackup && empty($existing['mutation_started']) && empty($existing['backup_operation_started']);
            $blockingStatus = in_array($status, [
                'armed', 'backup_ready', 'updating', 'monitoring',
                'failed', 'failed_post_update', 'restoring', 'recovery_failed', 'finalizing',
            ], true);
            if (($blockingStatus && !$safeFailedWithoutBackup) || $hasBackup || !empty($existing['backup_operation_started'])) {
                throw new \RuntimeException('update_recovery_pending');
            }
            $this->archiveState($existing);
            $this->journal->deleteIf(static fn (): bool => true);
        }
        $this->ensureRuntime();
        $token = bin2hex(random_bytes(32));
        $state = [
            'kind' => 'flatcms-recovery-state',
            'version' => 2,
            'recovery_id' => gmdate('YmdHis') . '-' . bin2hex(random_bytes(6)),
            'status' => 'armed',
            'token_hash' => hash('sha256', $token),
            'cookie_name' => self::COOKIE_NAME,
            'created_at' => gmdate('c'),
            'updated_at' => gmdate('c'),
            'expires_at' => time() + 21600,
            'from_version' => $this->readVersion(),
            'locale' => \App\Core\I18n::getLocale(),
            'target_version' => trim($targetVersion),
            'full_backup_path' => '',
            'full_backup_key_path' => '',
            'full_backup_sha256' => '',
            'full_backup_size_bytes' => 0,
            'error' => '',
            'auto_rollback_succeeded' => false,
            'mutation_started' => false,
        ];
        $this->writeState($state);
        return ['token' => $token, 'state' => $state, 'recovery_url' => '/recovery.php'];
    }

    /** @return array<string,mixed> */
    public function resumeAccess(): array
    {
        return $this->journal->exclusive(fn (): array => $this->resumeAccessLocked());
    }

    private function resumeAccessLocked(): array
    {
        $state = $this->readState();
        if ($state === []) {
            throw new \RuntimeException('update_recovery_not_armed');
        }
        $status = (string) ($state['status'] ?? '');
        $backupPath = trim((string) ($state['full_backup_path'] ?? ''));
        if ($backupPath === '' || in_array($status, ['success', 'recovered', 'finalizing'], true)) {
            throw new \RuntimeException('update_recovery_resume_unavailable');
        }
        (new RecoveryBackupReference($this->basePath))->verify($state);

        $token = bin2hex(random_bytes(32));
        $state['token_hash'] = hash('sha256', $token);
        $state['expires_at'] = time() + 21600;
        $state['updated_at'] = gmdate('c');
        $this->writeState($state);
        return ['token' => $token, 'state' => $state, 'recovery_url' => '/recovery.php'];
    }

    /** @return array<string,mixed> */
    public function prepareFullBackup(string $targetVersion): array
    {
        return \App\Core\Storage\ApplicationLock::for($this->basePath)->exclusive(
            fn (): array => $this->journal->exclusive(fn (): array => $this->prepareFullBackupLocked($targetVersion))
        );
    }

    private function prepareFullBackupLocked(string $targetVersion): array
    {
        $state = $this->readState();
        if ($state === []) {
            throw new \RuntimeException('update_recovery_not_armed');
        }
        if (($state['target_version'] ?? '') !== $targetVersion || !empty($state['mutation_started'])) {
            throw new \RuntimeException('update_recovery_pending');
        }
        $reference = new RecoveryBackupReference($this->basePath);
        if (trim((string) ($state['full_backup_path'] ?? '')) !== '') {
            $reference->verify($state);
            return $state;
        }
        $id = (string) ($state['recovery_id'] ?? '');
        if (preg_match('/^[0-9]{14}-[a-f0-9]{12}$/D', $id) !== 1) { throw new \RuntimeException('update_recovery_id_invalid'); }
        $relative = 'storage/backups/upgrade-provisional-' . $id;
        $paths = new \App\Core\Storage\StoragePathGuard($this->basePath);
        $directory = $paths->resolve($relative);
        $ready = new \App\Core\Storage\JsonStore($this->basePath,
            new \App\Core\Storage\AtomicFileWriter($this->basePath,
                new \App\Core\Storage\FileLockManager($this->recoveryRoot . '/locks'), 0750, 0640));
        if (!empty($state['backup_operation_started'])) {
            if (($state['backup_operation_root'] ?? null) !== $relative) {
                throw new \RuntimeException('update_recovery_backup_incomplete');
            }
            if ($ready->exists($relative . '/ready.json')) {
                return $this->adoptReceipt($state, $ready->read($relative . '/ready.json'), $reference, $id);
            }
            return $this->resumeInterruptedBackup($state, $targetVersion, $relative, $directory, $ready, $reference, $id);
        }
        if (file_exists($directory)) { throw new \RuntimeException('update_recovery_backup_incomplete'); }
        $state['backup_operation_started'] = true;
        $state['backup_operation_root'] = $relative;
        $this->writeState($state);
        $paths->ensureDirectory($relative, 0700);
        $backup = (new FullBackupService($this->basePath, $directory, $directory . '/keys', $this->backupCheckpoint))->createBackup([
            'backup_id' => $id,
            'reason' => 'pre_core_update_full',
            'created_by' => 'UpdateManager',
            'target_version' => $targetVersion,
            'scope' => 'core-recovery-excluding-runtime-and-download-payloads',
            'exclude_prefixes' => ['resources/downloads/'],
        ]);
        $receipt = $reference->describe($backup);
        $reference->verify($receipt);
        $ready->write($relative . '/ready.json', $receipt);
        $state = array_replace($state, $receipt, ['status' => 'backup_ready', 'updated_at' => gmdate('c')]);
        $this->writeState($state);
        return $state;
    }

    /** @param array<string,mixed> $state @param array<string,mixed> $receipt @return array<string,mixed> */
    private function adoptReceipt(array $state, array $receipt, RecoveryBackupReference $reference, string $id): array
    {
        $receipt = array_intersect_key($receipt, array_flip([
            'full_backup_id', 'full_backup_path', 'full_backup_key_path',
            'full_backup_sha256', 'full_backup_size_bytes', 'full_backup_files_count',
        ]));
        if (($receipt['full_backup_id'] ?? '') !== $id) {
            throw new \RuntimeException('recovery_backup_identity_mismatch');
        }
        $reference->verify($receipt);
        $state = array_replace($state, $receipt, ['status' => 'backup_ready', 'updated_at' => gmdate('c')]);
        $this->writeState($state);
        return $state;
    }

    /** @return array<string,mixed> */
    private function resumeInterruptedBackup(
        array $state,
        string $targetVersion,
        string $relative,
        string $directory,
        \App\Core\Storage\JsonStore $ready,
        RecoveryBackupReference $reference,
        string $id
    ): array {
        $archive = $directory . '/flatcms-full-backup-' . $id . '.zip';
        $key = $directory . '/keys/' . $id . '.key';
        if (file_exists($archive) || is_link($archive)) {
            if (!is_file($archive) || is_link($archive) || !is_file($key) || is_link($key)) {
                throw new \RuntimeException('update_recovery_backup_incomplete');
            }
            $manifest = (new FullBackupService($this->basePath))->validateBackup($archive, $key);
            if (($manifest['backup_id'] ?? '') !== $id) {
                throw new \RuntimeException('recovery_backup_identity_mismatch');
            }
            $receipt = $reference->describe([
                'id' => $id,
                'path' => $archive,
                'key_path' => $key,
                'sha256' => strtolower((string) hash_file('sha256', $archive)),
                'size_bytes' => (int) filesize($archive),
                'files_count' => (int) ($manifest['files_count'] ?? count((array) ($manifest['files'] ?? []))),
            ]);
            $reference->verify($receipt);
            $ready->write($relative . '/ready.json', $receipt);
            return $this->adoptReceipt($state, $receipt, $reference, $id);
        }

        $this->cleanupInterruptedBackupArtifacts($directory, $id);
        $backup = (new FullBackupService($this->basePath, $directory, $directory . '/keys', $this->backupCheckpoint))->createBackup([
            'backup_id' => $id,
            'reason' => 'pre_core_update_full',
            'created_by' => 'UpdateManager',
            'target_version' => $targetVersion,
            'scope' => 'core-recovery-excluding-runtime-and-download-payloads',
            'exclude_prefixes' => ['resources/downloads/'],
        ]);
        $receipt = $reference->describe($backup);
        $reference->verify($receipt);
        $ready->write($relative . '/ready.json', $receipt);
        return $this->adoptReceipt($state, $receipt, $reference, $id);
    }

    private function cleanupInterruptedBackupArtifacts(string $directory, string $id): void
    {
        if (!file_exists($directory)) {
            (new \App\Core\Storage\StoragePathGuard($this->basePath))->ensureDirectory(
                'storage/backups/upgrade-provisional-' . $id,
                0700
            );
            return;
        }
        if (!is_dir($directory) || is_link($directory)) {
            throw new \RuntimeException('update_recovery_backup_incomplete');
        }

        $allowed = [
            'flatcms-full-backup-' . $id . '.zip.partial',
            'keys/' . $id . '.key',
        ];
        $sidecar = '/^keys\\/\\.' . preg_quote($id . '.key', '/') . '\\.[a-f0-9]{16}\\.(?:tmp|bak)$/D';
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $name = str_replace('\\', '/', substr($path, strlen($directory) + 1));
            if ($item->isLink() || ($item->isDir() && $name !== 'keys')
                || (!$item->isDir() && !in_array($name, $allowed, true) && preg_match($sidecar, $name) !== 1)) {
                throw new \RuntimeException('update_recovery_backup_incomplete');
            }
            if (!$item->isDir()) {
                $files[] = $path;
            }
        }
        foreach ($files as $path) {
            if (!unlink($path)) {
                throw new \RuntimeException('update_recovery_backup_incomplete');
            }
        }
        $keys = $directory . '/keys';
        if (is_dir($keys) && !rmdir($keys)) {
            throw new \RuntimeException('update_recovery_backup_incomplete');
        }
    }

    public function markDispatched(int $pid, string $logPath): void
    {
        $this->updateState([
            'worker_pid' => max(0, $pid),
            'worker_log_path' => (new \App\Core\Storage\StoragePathGuard($this->basePath))->relative($logPath),
            'worker_dispatched_at' => gmdate('c'),
        ]);
    }

    public function markUpdating(): void
    {
        $this->updateState(['status' => 'updating', 'update_started_at' => gmdate('c'), 'mutation_started' => true]);
    }

    public function markFailure(string $error, bool $autoRollbackSucceeded = false): void
    {
        try {
            $this->journal->mutate(static function (array $state) use ($error, $autoRollbackSucceeded): array {
                $verified = $autoRollbackSucceeded && ($state['core_transaction']['phase'] ?? '') === 'rolled_back';
                $state = array_replace($state, ['status' => $verified ? 'recovered' : 'failed',
                    'failed_at' => gmdate('c'), 'error' => $error, 'auto_rollback_succeeded' => $verified]);
                if ($verified) {
                    $state['recovered_at'] = gmdate('c');
                    $state['token_hash'] = hash('sha256', random_bytes(32));
                    $state['expires_at'] = 0;
                }
                return $state;
            });
        } catch (\Throwable) {
            // Never mask the original update failure with a recovery-journal failure.
        }
    }

    public function markSuccess(): void
    {
        $this->updateState(['status' => 'monitoring', 'completed_at' => gmdate('c'), 'monitor_until' => time() + 3600]);
    }

    public function cancelIfUnprepared(): void
    {
        $this->journal->deleteIf(static fn (array $state): bool =>
            empty($state['mutation_started']) && empty($state['backup_operation_started']) && trim((string) ($state['full_backup_path'] ?? '')) === '');
    }

    public function hasPreparedBackup(): bool
    {
        $state = $this->readState();
        if (trim((string) ($state['full_backup_path'] ?? '')) === '') { return false; }
        try { (new RecoveryBackupReference($this->basePath))->verify($state); return true; }
        catch (\Throwable) { return false; }
    }

    public function requiresRecovery(): bool
    {
        $state = $this->readState();
        if (!empty($state['mutation_started'])) return true;
        return in_array((string) ($state['status'] ?? ''), ['failed_post_update', 'restoring', 'recovery_failed'], true);
    }

    public function cancelIfSafe(): void
    {
        $this->journal->deleteIf(function (array $state): bool {
            if (!empty($state['mutation_started']) || !empty($state['backup_operation_started'])
                || trim((string) ($state['full_backup_path'] ?? '')) !== '') { return false; }
            $this->archiveState($state);
            return true;
        });
    }

    /** @return array<string,mixed> */
    public function state(): array
    {
        return $this->readState();
    }

    private function ensureRuntime(): void
    {
        $this->ensureEntrypoints();

        $runtimeRoot = $this->recoveryRoot . '/runtime';
        $this->ensureDirectory($runtimeRoot, 0750);
        $serviceSource = $this->basePath . '/app/Modules/Backups/Services/FullBackupService.php';
        $runtimeSource = $this->basePath . '/app/Modules/UpdateManager/Recovery/recovery-runtime.php';
        $styleSource = $this->basePath . '/app/Modules/UpdateManager/Assets/css/recovery.css';
        // A separate namespace prevents partially loaded live classes shadowing the capsule.
        foreach (['StorageException', 'StoragePathGuard', 'ApplicationLock', 'FileLockManager', 'AtomicFileWriter', 'JsonStore', 'StreamFileTransaction'] as $class) {
            $source = $this->basePath . '/app/Core/Storage/' . $class . '.php';
            $target = $runtimeRoot . '/' . $class . '.php';
            $this->captureSource($source, $target);
        }
        foreach ([$this->basePath . '/app/Modules/Backups/Services/BackupSecretCipher.php' => $runtimeRoot . '/BackupSecretCipher.php',
            $serviceSource => $runtimeRoot . '/FullBackupService.php',
            $this->basePath . '/app/Core/RuntimeProbe.php' => $runtimeRoot . '/RuntimeProbe.php',
            $this->basePath . '/app/Modules/UpdateManager/Services/RecoveryStateStore.php' => $runtimeRoot . '/RecoveryStateStore.php',
            $this->basePath . '/app/Modules/UpdateManager/Services/RecoveryBackupReference.php' => $runtimeRoot . '/RecoveryBackupReference.php',
            $this->basePath . '/app/Modules/UpdateManager/Services/CoreUpdatePathPolicy.php' => $runtimeRoot . '/CoreUpdatePathPolicy.php',
            $this->basePath . '/app/Modules/UpdateManager/Services/CoreUpdateRecoveryService.php' => $runtimeRoot . '/CoreUpdateRecoveryService.php',
            $runtimeSource => $runtimeRoot . '/recovery-runtime.php'] as $source => $target) {
            $this->captureSource($source, $target);
        }

        // The child must boot the restored live Core, not the capsule's namespace.
        $writer = new \App\Core\Storage\AtomicFileWriter($this->basePath,
            new \App\Core\Storage\FileLockManager($this->recoveryRoot . '/locks'), 0750, 0640);
        $worker = file_get_contents($this->basePath . '/app/Core/RuntimeProbe/worker.php');
        if (!is_string($worker)) { throw new \RuntimeException('update_recovery_runtime_create_failed'); }
        $writer->write($runtimeRoot . '/RuntimeProbe/worker.php', $worker);

        $languagesSource = $this->basePath . '/app/Modules/UpdateManager/Languages';
        $languagesTarget = $runtimeRoot . '/Languages';
        $this->ensureDirectory($languagesTarget, 0750);
        foreach (['fr-FR', 'en-US', 'de-DE', 'es-ES', 'it-IT', 'pt-PT'] as $locale) {
            $source = $languagesSource . '/' . $locale . '.json';
            $target = $languagesTarget . '/' . $locale . '.json';
            if (!is_file($source) || !@copy($source, $target)) {
                throw new \RuntimeException('update_recovery_runtime_create_failed');
            }
            @chmod($target, 0640);
        }
    }

    private function ensureEntrypoints(): void
    {
        $sourceRoot = $this->basePath . '/app/Modules/UpdateManager/Recovery/entrypoints';
        $styleSource = $this->basePath . '/app/Modules/UpdateManager/Assets/css/recovery.css';
        $targets = [
            [$sourceRoot . '/root-recovery.php', $this->basePath . '/recovery.php'],
            [$sourceRoot . '/public-recovery.php', $this->basePath . '/public/recovery.php'],
            [$styleSource, $this->basePath . '/recovery.css'],
            [$styleSource, $this->basePath . '/public/recovery.css'],
        ];

        foreach ($targets as [$source, $target]) {
            if (!is_file($source)) {
                throw new \RuntimeException('update_recovery_entrypoint_source_missing');
            }
            $temporary = $target . '.recovery.tmp-' . bin2hex(random_bytes(4));
            if (!@copy($source, $temporary)) {
                throw new \RuntimeException('update_recovery_entrypoint_create_failed');
            }
            @chmod($temporary, 0644);
            if (!@rename($temporary, $target)) {
                @unlink($temporary);
                throw new \RuntimeException('update_recovery_entrypoint_create_failed');
            }
        }
    }

    /** @param array<string,mixed> $changes */
    private function updateState(array $changes): void
    {
        $this->journal->mutate(static fn (array $state): array => array_replace($state, $changes));
    }

    /** @param array<string,mixed> $state */
    private function archiveState(array $state): void
    {
        if ($state === []) return;
        try {
            $this->journal->archive($state);
        } catch (\Throwable) {
            // Recovery history must never block a new transaction after expiry.
        }
    }

    /** @return array<string,mixed> */
    private function readState(): array
    {
        return $this->journal->read();
    }

    /** @param array<string,mixed> $state */
    private function writeState(array $state): void
    {
        $this->journal->write($state);
    }

    private function captureSource(string $source, string $target): void
    {
        $contents = file_get_contents($source);
        if (!is_string($contents)) { throw new \RuntimeException('update_recovery_runtime_create_failed'); }
        $contents = str_replace([
            'App\\Core\\Storage',
            'App\\Modules\\Backups\\Services\\FullBackupService',
            'namespace App\\Modules\\Backups\\Services;',
            'namespace App\\Modules\\UpdateManager\\Services;',
            'namespace App\\Core;',
        ], [
            'FlatCMS\\RecoverySnapshot\\Storage',
            'FlatCMS\\RecoverySnapshot\\FullBackupService',
            'namespace FlatCMS\\RecoverySnapshot;',
            'namespace FlatCMS\\RecoverySnapshot;',
            'namespace FlatCMS\\RecoverySnapshot;',
        ], $contents);
        $writer = new \App\Core\Storage\AtomicFileWriter($this->basePath,
            new \App\Core\Storage\FileLockManager($this->recoveryRoot . '/locks'), 0750, 0640);
        $writer->write($target, $contents);
    }

    private function readVersion(): string
    {
        $raw = trim((string) @file_get_contents($this->basePath . '/VERSION'));
        if (preg_match('/["\']([^"\']+)["\']/', $raw, $match) === 1) {
            return trim((string) $match[1]);
        }
        return $raw !== '' ? $raw : 'unknown';
    }

    private function ensureDirectory(string $path, int $mode): void
    {
        if (!is_dir($path) && !@mkdir($path, $mode, true) && !is_dir($path)) {
            throw new \RuntimeException('update_recovery_directory_failed');
        }
    }
}
