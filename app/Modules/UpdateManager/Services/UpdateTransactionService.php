<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateTransactionService
{
    private string $basePath;
    private string $backupRoot;
    private string $lockPath;
    private CoreUpdatePathPolicy $pathPolicy;

    public function __construct(
        ?string $basePath = null,
        ?string $backupRoot = null,
        private ?UpdateHistoryService $history = null,
        ?CoreUpdatePathPolicy $pathPolicy = null
    ) {
        $this->basePath = rtrim($basePath ?: BASE_PATH, '/\\');
        $this->backupRoot = $backupRoot ?: $this->basePath . '/storage/update-manager/backups';
        $this->lockPath = $this->basePath . '/storage/cache/update-manager/apply.lock';
        $this->history ??= new UpdateHistoryService();
        $this->pathPolicy = $pathPolicy ?? new CoreUpdatePathPolicy();
    }

    /**
     * @param array<string,mixed> $prepared
     * @param array<string,mixed> $package
     * @param callable|null $healthCheck function(string $basePath, array $manifest): bool
     * @return array<string,mixed>
     */
    public function applyCore(array $prepared, array $package, ?callable $healthCheck = null): array
    {
        $manifest = is_array($prepared['manifest'] ?? null) ? $prepared['manifest'] : [];
        $extractPath = rtrim((string) ($prepared['extract_path'] ?? ''), '/\\');
        if ($manifest === [] || $extractPath === '' || !is_dir($extractPath)) {
            throw new \RuntimeException('update_apply_plan_invalid');
        }
        $this->assertCoreManifestOwnership($manifest);

        $lock = $this->acquireLock();
        $transactionId = gmdate('YmdHis') . '-' . bin2hex(random_bytes(6));
        $startedAt = gmdate('c');
        $siteBackupPath = trim((string) ($package['site_backup_path'] ?? ''));
        $fullBackupPath = trim((string) ($package['full_backup_path'] ?? ''));
        $recoveryId = trim((string) ($package['recovery_id'] ?? ''));
        $targets = [];
        $backupPath = '';
        $backupReady = false;
        $mutatedPaths = [];

        try {
            $targets = $this->snapshotTargets($manifest);
            $backupPath = $this->writeBackup($transactionId, $targets);
            $backupReady = true;

            $this->applyFiles($transactionId, $manifest, $extractPath, $mutatedPaths);
            $this->applyRemovals($manifest, $mutatedPaths);
            $this->verifyApplied($manifest);
            if ($healthCheck !== null && $healthCheck($this->basePath, $manifest) !== true) {
                throw new \RuntimeException('update_health_check_failed');
            }

            $result = [
                'transaction_id' => $transactionId,
                'status' => 'success',
                'version' => (string) ($manifest['version'] ?? ''),
                'backup_path' => $backupPath,
                'site_backup_path' => $siteBackupPath,
                'full_backup_path' => $fullBackupPath,
                'recovery_id' => $recoveryId,
            ];
            $this->history->append(array_merge($result, [
                'catalog' => 'core',
                'slug' => (string) ($package['slug'] ?? 'flatcms'),
                'started_at' => $startedAt,
            ]));
            return $result;
        } catch (\Throwable $exception) {
            if (!$backupReady) {
                $this->history->append([
                    'transaction_id' => $transactionId,
                    'status' => 'backup_failed',
                    'catalog' => 'core',
                    'slug' => (string) ($package['slug'] ?? 'flatcms'),
                    'version' => (string) ($manifest['version'] ?? ''),
                    'started_at' => $startedAt,
                    'error' => $exception->getMessage(),
                    'backup_path' => '',
                    'site_backup_path' => $siteBackupPath,
                'full_backup_path' => $fullBackupPath,
                'recovery_id' => $recoveryId,
                ]);
                throw $exception;
            }

            $rollbackError = '';
            try {
                $this->rollback($backupPath, $targets, $mutatedPaths);
            } catch (\Throwable $rollbackException) {
                $rollbackError = $rollbackException->getMessage();
            }

            $this->history->append([
                'transaction_id' => $transactionId,
                'status' => $rollbackError === '' ? 'rolled_back' : 'rollback_failed',
                'catalog' => 'core',
                'slug' => (string) ($package['slug'] ?? 'flatcms'),
                'version' => (string) ($manifest['version'] ?? ''),
                'started_at' => $startedAt,
                'error' => $exception->getMessage(),
                'rollback_error' => $rollbackError,
                'backup_path' => $backupPath,
                'site_backup_path' => $siteBackupPath,
                'full_backup_path' => $fullBackupPath,
                'recovery_id' => $recoveryId,
            ]);

            $message = $rollbackError === '' ? 'update_apply_failed_rolled_back' : 'update_apply_rollback_failed';
            throw new \RuntimeException($message . ':' . $exception->getMessage() . ($rollbackError !== '' ? ':' . $rollbackError : ''));
        } finally {
            $this->releaseLock($lock);
        }
    }

    /** @param array<string,mixed> $manifest @return array<string,array<string,mixed>> */
    private function snapshotTargets(array $manifest): array
    {
        $paths = array_merge(array_keys((array) ($manifest['files'] ?? [])), array_values((array) ($manifest['remove'] ?? [])));
        $targets = [];
        foreach (array_values(array_unique(array_map('strval', $paths))) as $relative) {
            $target = $this->targetPath($relative);
            if (is_dir($target)) {
                throw new \RuntimeException('update_apply_directory_target_unsupported');
            }
            $exists = is_file($target);
            $targets[$relative] = [
                'existed' => $exists,
                'sha256' => $exists ? strtolower((string) hash_file('sha256', $target)) : '',
                'mode' => $exists ? ((int) @fileperms($target) & 0777) : 0,
            ];
        }
        return $targets;
    }

    /** @param array<string,array<string,mixed>> $targets */
    private function writeBackup(string $transactionId, array $targets): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('update_backup_zip_required');
        }
        if (!is_dir($this->backupRoot) && !@mkdir($this->backupRoot, 0750, true) && !is_dir($this->backupRoot)) {
            throw new \RuntimeException('update_backup_create_failed');
        }
        $path = $this->backupRoot . '/' . $transactionId . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) {
            throw new \RuntimeException('update_backup_create_failed');
        }

        try {
            $meta = json_encode(['transaction_id' => $transactionId, 'targets' => $targets], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (!is_string($meta) || !$zip->addFromString('transaction.json', $meta)) {
                throw new \RuntimeException('update_backup_create_failed');
            }
            foreach ($targets as $relative => $state) {
                if (empty($state['existed'])) {
                    continue;
                }
                $source = $this->targetPath($relative);
                if (!is_file($source) || !$zip->addFile($source, 'files/' . $relative)) {
                    throw new \RuntimeException('update_backup_create_failed');
                }
            }
        } finally {
            $zip->close();
        }

        if (!is_file($path) || (int) @filesize($path) < 1) {
            throw new \RuntimeException('update_backup_create_failed');
        }
        return $path;
    }

    /** @param array<string,mixed> $manifest */
    public function assertWritableTargets(array $manifest): void
    {
        $this->assertCoreManifestOwnership($manifest);
        $paths = array_keys((array) ($manifest['files'] ?? []));
        foreach ((array) ($manifest['remove'] ?? []) as $relative) {
            if (is_file($this->targetPath((string) $relative))) {
                $paths[] = (string) $relative;
            }
        }

        $checked = [];
        $blocked = [];
        foreach (array_values(array_unique(array_map('strval', $paths))) as $relative) {
            $parent = dirname($this->targetPath($relative));
            $probeDir = $this->nearestExistingDirectory($parent);
            if ($probeDir === '' || isset($checked[$probeDir])) {
                continue;
            }
            $checked[$probeDir] = true;
            if (!$this->probeDirectoryWritable($probeDir)) {
                $blocked[$relative] = $probeDir;
            }
        }

        if ($blocked !== []) {
            $paths = array_slice(array_keys($blocked), 0, 8);
            throw new \RuntimeException('update_preflight_permissions_failed:' . count($blocked) . ':' . implode('|', $paths));
        }
    }

    /** @param array<string,mixed> $manifest @param array<int,string> $mutatedPaths */
    private function applyFiles(string $transactionId, array $manifest, string $extractPath, array &$mutatedPaths): void
    {
        foreach ((array) ($manifest['files'] ?? []) as $relative => $expectedHash) {
            $relative = (string) $relative;
            $source = $extractPath . '/' . $relative;
            if (!is_file($source)) {
                throw new \RuntimeException('update_apply_source_missing');
            }
            $mode = str_starts_with($relative, 'bin/') ? 0755 : 0644;
            $this->atomicReplace(
                $source,
                $this->targetPath($relative),
                strtolower((string) $expectedHash),
                $mode,
                $transactionId,
                static function () use (&$mutatedPaths, $relative): void {
                    if (!in_array($relative, $mutatedPaths, true)) {
                        $mutatedPaths[] = $relative;
                    }
                }
            );
        }
    }

    /** @param array<string,mixed> $manifest @param array<int,string> $mutatedPaths */
    private function applyRemovals(array $manifest, array &$mutatedPaths): void
    {
        foreach ((array) ($manifest['remove'] ?? []) as $relative) {
            $relative = (string) $relative;
            $target = $this->targetPath($relative);
            if (is_file($target)) {
                if (!@unlink($target)) {
                    throw new \RuntimeException('update_apply_remove_failed');
                }
                if (!in_array($relative, $mutatedPaths, true)) {
                    $mutatedPaths[] = $relative;
                }
            }
        }
    }

    /** @param array<string,mixed> $manifest */
    private function verifyApplied(array $manifest): void
    {
        foreach ((array) ($manifest['files'] ?? []) as $relative => $expectedHash) {
            $target = $this->targetPath((string) $relative);
            $actual = is_file($target) ? strtolower((string) hash_file('sha256', $target)) : '';
            if ($actual === '' || !hash_equals(strtolower((string) $expectedHash), $actual)) {
                throw new \RuntimeException('update_apply_verify_failed');
            }
        }
        foreach ((array) ($manifest['remove'] ?? []) as $relative) {
            if (file_exists($this->targetPath((string) $relative))) {
                throw new \RuntimeException('update_apply_verify_failed');
            }
        }

        $version = trim((string) @file_get_contents($this->basePath . '/VERSION'));
        if ($version !== trim((string) ($manifest['version'] ?? ''))) {
            throw new \RuntimeException('update_apply_version_failed');
        }
    }

    /** @param array<string,array<string,mixed>> $targets @param array<int,string> $mutatedPaths */
    private function rollback(string $backupPath, array $targets, array $mutatedPaths): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($backupPath) !== true) {
            throw new \RuntimeException('update_rollback_backup_open_failed');
        }
        try {
            foreach (array_reverse(array_values(array_unique($mutatedPaths))) as $relative) {
                $state = $targets[$relative] ?? null;
                if (!is_array($state)) {
                    throw new \RuntimeException('update_rollback_target_state_missing');
                }
                $target = $this->targetPath($relative);
                if (empty($state['existed'])) {
                    if (is_file($target) && !@unlink($target)) {
                        throw new \RuntimeException('update_rollback_remove_new_failed');
                    }
                    continue;
                }

                $stream = $zip->getStream('files/' . $relative);
                if (!is_resource($stream)) {
                    throw new \RuntimeException('update_rollback_file_missing');
                }
                $this->restoreStream($stream, $target, (int) ($state['mode'] ?? 0644));
                fclose($stream);

                $actual = strtolower((string) hash_file('sha256', $target));
                if (!hash_equals((string) ($state['sha256'] ?? ''), $actual)) {
                    throw new \RuntimeException('update_rollback_verify_failed');
                }
            }
        } finally {
            $zip->close();
        }
    }

    private function atomicReplace(string $source, string $target, string $expectedHash, int $mode, string $transactionId, ?callable $onMutation = null): void
    {
        $parent = dirname($target);
        if (!is_dir($parent) && !@mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new \RuntimeException('update_apply_target_create_failed');
        }
        $tmp = $target . '.flatcms-update-' . $transactionId . '.tmp';
        if (!@copy($source, $tmp)) {
            throw new \RuntimeException('update_apply_copy_failed');
        }
        @chmod($tmp, $mode);
        $actual = strtolower((string) hash_file('sha256', $tmp));
        if ($actual === '' || !hash_equals($expectedHash, $actual)) {
            @unlink($tmp);
            throw new \RuntimeException('update_apply_temp_verify_failed');
        }

        $mutationRecorded = false;
        $recordMutation = static function () use (&$mutationRecorded, $onMutation): void {
            if (!$mutationRecorded && $onMutation !== null) {
                $onMutation();
            }
            $mutationRecorded = true;
        };

        if (@rename($tmp, $target)) {
            $recordMutation();
            return;
        }
        if (is_file($target)) {
            if (!@unlink($target)) {
                @unlink($tmp);
                throw new \RuntimeException('update_apply_swap_failed');
            }
            $recordMutation();
        }
        if (!@rename($tmp, $target)) {
            @unlink($tmp);
            throw new \RuntimeException('update_apply_swap_failed');
        }
        $recordMutation();
    }

    private function nearestExistingDirectory(string $path): string
    {
        $current = $path;
        while ($current !== '' && !is_dir($current)) {
            $parent = dirname($current);
            if ($parent === $current) {
                return '';
            }
            $current = $parent;
        }
        return $current;
    }

    private function probeDirectoryWritable(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }
        try {
            $suffix = bin2hex(random_bytes(6));
        } catch (\Throwable) {
            return false;
        }
        $probe = rtrim($directory, '/\\') . '/.flatcms-update-preflight-' . $suffix . '.tmp';
        $handle = @fopen($probe, 'xb');
        if (!is_resource($handle)) {
            return false;
        }
        $written = fwrite($handle, 'flatcms');
        fclose($handle);
        $removed = @unlink($probe);
        return $written === 7 && $removed;
    }

    /** @param resource $stream */
    private function restoreStream($stream, string $target, int $mode): void
    {
        $parent = dirname($target);
        if (!is_dir($parent) && !@mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new \RuntimeException('update_rollback_target_create_failed');
        }
        $tmp = $target . '.flatcms-rollback.tmp';
        $out = @fopen($tmp, 'wb');
        if (!is_resource($out)) {
            throw new \RuntimeException('update_rollback_write_failed');
        }
        $copied = stream_copy_to_stream($stream, $out);
        fclose($out);
        if ($copied === false) {
            @unlink($tmp);
            throw new \RuntimeException('update_rollback_write_failed');
        }
        @chmod($tmp, $mode > 0 ? $mode : 0644);
        if (!@rename($tmp, $target)) {
            if (is_file($target)) {
                @unlink($target);
            }
            if (!@rename($tmp, $target)) {
                @unlink($tmp);
                throw new \RuntimeException('update_rollback_swap_failed');
            }
        }
    }

    /** @return resource */
    private function acquireLock()
    {
        $dir = dirname($this->lockPath);
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new \RuntimeException('update_lock_create_failed');
        }
        $handle = @fopen($this->lockPath, 'c+');
        if (!is_resource($handle) || !flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new \RuntimeException('update_already_running');
        }
        ftruncate($handle, 0);
        fwrite($handle, (string) getmypid());
        fflush($handle);
        return $handle;
    }

    /** @param resource $handle */
    private function releaseLock($handle): void
    {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /** @param array<string,mixed> $manifest */
    private function assertCoreManifestOwnership(array $manifest): void
    {
        foreach (array_keys((array) ($manifest['files'] ?? [])) as $relative) {
            $this->pathPolicy->assertAllowed((string) $relative, 'update_apply_core_path_forbidden');
        }
        foreach ((array) ($manifest['remove'] ?? []) as $relative) {
            $this->pathPolicy->assertAllowed((string) $relative, 'update_apply_core_path_forbidden');
        }
    }

    private function targetPath(string $relative): string
    {
        $relative = $this->pathPolicy->assertAllowed($relative, 'update_apply_core_path_forbidden');
        return $this->basePath . '/' . $relative;
    }
}
