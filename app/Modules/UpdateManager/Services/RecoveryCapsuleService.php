<?php
/** FlatCMS autonomous disaster-recovery capsule manager. */
declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Modules\Backups\Services\FullBackupService;

final class RecoveryCapsuleService
{
    public const COOKIE_NAME = 'flatcms_recovery';
    private string $basePath;
    private string $recoveryRoot;
    private string $activePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = rtrim($basePath ?: BASE_PATH, '/\\');
        $this->recoveryRoot = $this->basePath . '/storage/recovery';
        $this->activePath = $this->recoveryRoot . '/active.json';
    }

    /** @return array<string,mixed> */
    public function arm(string $targetVersion): array
    {
        $existing = $this->readState();
        if ($existing !== []) {
            $status = (string) ($existing['status'] ?? '');
            $hasBackup = trim((string) ($existing['full_backup_path'] ?? '')) !== '';
            $monitoringExpired = $status === 'monitoring' && (int) ($existing['monitor_until'] ?? 0) < time();
            $accessExpired = (int) ($existing['expires_at'] ?? 0) > 0 && (int) ($existing['expires_at'] ?? 0) < time();
            $safeFailedWithoutBackup = $status === 'failed' && !$hasBackup && empty($existing['mutation_started']);
            $blockingStatus = in_array($status, [
                'armed', 'backup_ready', 'updating', 'monitoring',
                'failed', 'failed_post_update', 'restoring', 'recovery_failed',
            ], true);
            if ($blockingStatus && !$monitoringExpired && !$accessExpired && !$safeFailedWithoutBackup) {
                throw new \RuntimeException('update_recovery_pending');
            }
            $this->archiveState($existing);
            @unlink($this->activePath);
        }
        $this->ensureRuntime();
        $token = bin2hex(random_bytes(32));
        $state = [
            'kind' => 'flatcms-recovery-state',
            'version' => 1,
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
        $state = $this->readState();
        if ($state === []) {
            throw new \RuntimeException('update_recovery_not_armed');
        }
        $status = (string) ($state['status'] ?? '');
        $backupPath = trim((string) ($state['full_backup_path'] ?? ''));
        if ($backupPath === '' || !is_file($backupPath) || in_array($status, ['success', 'recovered'], true)) {
            throw new \RuntimeException('update_recovery_resume_unavailable');
        }

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
        $state = $this->readState();
        if ($state === []) {
            throw new \RuntimeException('update_recovery_not_armed');
        }
        if (trim((string) ($state['full_backup_path'] ?? '')) !== '') {
            return $state;
        }
        $backup = (new FullBackupService($this->basePath))->createBackup([
            'reason' => 'pre_core_update_full',
            'created_by' => 'UpdateManager',
            'target_version' => $targetVersion,
            'scope' => 'core-recovery-excluding-runtime-and-download-payloads',
            'exclude_prefixes' => ['resources/downloads/'],
        ]);
        $state['status'] = 'backup_ready';
        $state['full_backup_path'] = (string) ($backup['path'] ?? '');
        $state['full_backup_key_path'] = (string) ($backup['key_path'] ?? '');
        $state['full_backup_sha256'] = (string) ($backup['sha256'] ?? '');
        $state['full_backup_size_bytes'] = (int) ($backup['size_bytes'] ?? 0);
        $state['full_backup_files_count'] = (int) ($backup['files_count'] ?? 0);
        $state['updated_at'] = gmdate('c');
        $this->writeState($state);
        return $state;
    }

    public function markDispatched(int $pid, string $logPath): void
    {
        $this->updateState([
            'worker_pid' => max(0, $pid),
            'worker_log_path' => trim($logPath),
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
            $this->updateState([
                'status' => 'failed',
                'failed_at' => gmdate('c'),
                'error' => $error,
                'auto_rollback_succeeded' => $autoRollbackSucceeded,
            ]);
        } catch (\Throwable) {
            // Never mask the original update failure with a recovery-journal failure.
        }
    }

    public function markSuccess(): void
    {
        $state = $this->readState();
        if ($state === []) {
            return;
        }
        $state['status'] = 'monitoring';
        $state['completed_at'] = gmdate('c');
        $state['monitor_until'] = time() + 3600;
        $state['updated_at'] = gmdate('c');
        $this->writeState($state);
    }

    public function cancelIfUnprepared(): void
    {
        $state = $this->readState();
        if ($state !== [] && trim((string) ($state['full_backup_path'] ?? '')) === '') {
            @unlink($this->activePath);
        }
    }

    public function hasPreparedBackup(): bool
    {
        $state = $this->readState();
        $path = trim((string) ($state['full_backup_path'] ?? ''));
        return $path !== '' && is_file($path);
    }

    public function requiresRecovery(): bool
    {
        $state = $this->readState();
        $path = trim((string) ($state['full_backup_path'] ?? ''));
        if ($path === '' || !is_file($path)) return false;
        if (!empty($state['mutation_started'])) return true;
        return in_array((string) ($state['status'] ?? ''), ['failed_post_update', 'restoring', 'recovery_failed'], true);
    }

    public function cancelIfSafe(): void
    {
        $state = $this->readState();
        if ($state !== [] && empty($state['mutation_started'])) {
            $this->archiveState($state);
            @unlink($this->activePath);
        }
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
        foreach ([$serviceSource => $runtimeRoot . '/FullBackupService.php', $runtimeSource => $runtimeRoot . '/recovery-runtime.php'] as $source => $target) {
            if (!is_file($source) || !@copy($source, $target)) {
                throw new \RuntimeException('update_recovery_runtime_create_failed');
            }
            @chmod($target, 0640);
        }

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
        $state = $this->readState();
        if ($state === []) {
            return;
        }
        foreach ($changes as $key => $value) {
            $state[$key] = $value;
        }
        $state['updated_at'] = gmdate('c');
        $this->writeState($state);
    }

    /** @param array<string,mixed> $state */
    private function archiveState(array $state): void
    {
        if ($state === []) return;
        $history = $this->recoveryRoot . '/history';
        $this->ensureDirectory($history, 0750);
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($state['recovery_id'] ?? 'recovery')) ?: 'recovery';
        try {
            $this->writeJson($history . '/' . $id . '.json', $state, 0640);
        } catch (\Throwable) {
            // Recovery history must never block a new transaction after expiry.
        }
    }

    /** @return array<string,mixed> */
    private function readState(): array
    {
        $decoded = json_decode((string) @file_get_contents($this->activePath), true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string,mixed> $state */
    private function writeState(array $state): void
    {
        $this->ensureDirectory($this->recoveryRoot, 0750);
        $this->writeJson($this->activePath, $state, 0640);
    }

    /** @param array<string,mixed> $data */
    private function writeJson(string $path, array $data, int $mode): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            throw new \RuntimeException('update_recovery_state_encode_failed');
        }
        $tmp = $path . '.tmp';
        if (@file_put_contents($tmp, $json, LOCK_EX) === false || !@rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('update_recovery_state_write_failed');
        }
        @chmod($path, $mode);
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
