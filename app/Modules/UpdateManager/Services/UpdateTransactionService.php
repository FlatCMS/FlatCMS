<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/UpdateManager/Services/UpdateTransactionService.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Core\RuntimeProbe;
use App\Core\Storage\ApplicationLock;
use App\Core\Storage\StoragePathGuard;

final class UpdateTransactionService
{
    private string $basePath;
    private StoragePathGuard $paths;
    private CoreUpdatePathPolicy $pathPolicy;

    public function __construct(
        ?string $basePath = null,
        private ?UpdateHistoryService $history = null,
        ?CoreUpdatePathPolicy $pathPolicy = null
    ) {
        $this->paths = new StoragePathGuard($basePath ?: BASE_PATH);
        $this->basePath = $this->paths->root();
        $this->history ??= new UpdateHistoryService($this->basePath . '/storage/logs/update-manager/history.jsonl');
        $this->pathPolicy = $pathPolicy ?? new CoreUpdatePathPolicy();
    }

    public function applyCore(array $prepared, array $package, ?callable $healthCheck = null, ?callable $afterRollback = null): array
    {
        // A pending plan must be recovered explicitly, never replaced by a second update.
        return ApplicationLock::for($this->basePath)->exclusive(
            fn (): array => $this->applyUnderLease($prepared, $package, $healthCheck, $afterRollback));
    }

    private function applyUnderLease(array $prepared, array $package, ?callable $healthCheck, ?callable $afterRollback): array
    {
        $manifest = is_array($prepared['manifest'] ?? null) ? $prepared['manifest'] : [];
        $extractPath = (string) ($prepared['extract_path'] ?? '');
        if ($manifest === [] || $extractPath === '' || !is_dir($extractPath)) { throw new \RuntimeException('update_apply_plan_invalid'); }
        $this->assertCoreManifestOwnership($manifest);
        $lock = $this->acquireLock();
        $recovery = new CoreUpdateRecoveryService($this->basePath);
        $id = (string) ($package['recovery_id'] ?? '');
        $record = ['transaction_id' => $id, 'recovery_id' => $id, 'catalog' => 'core', 'slug' => 'flatcms',
            'version' => (string) ($manifest['version'] ?? ''), 'started_at' => gmdate('c')];
        $preparedPlan = false;
        try {
            $recovery->prepare($manifest, $extractPath, $id);
            $preparedPlan = true;
            $state = (new RecoveryStateStore($this->basePath))->read();
            $record['full_backup_path'] = $state['full_backup_path'];
            $recovery->applyFiles($extractPath);
            if (!(new UpdateHealthCheckService())->check($this->basePath, $manifest)
                || ($healthCheck !== null && $healthCheck($this->basePath, $manifest) !== true)) {
                throw new \RuntimeException('update_health_check_failed');
            }
            $recovery->markCommitted();
            $result = $record + ['status' => 'success'];
            $this->history->append($result);
            return $result;
        } catch (\Throwable $error) {
            if (!$preparedPlan) {
                $this->history->append($record + ['status' => 'preparation_failed', 'error' => $error->getMessage()]);
                throw $error;
            }
            $rollbackError = '';
            try {
                $restored = $recovery->rollback();
                if ($afterRollback !== null) { $afterRollback($this->basePath, $manifest); }
                if (!(new RuntimeProbe())->check($this->basePath, $restored['flatcms_version'], ['core-update'])) {
                    throw new \RuntimeException('update_rollback_health_failed');
                }
            } catch (\Throwable $failure) { $rollbackError = $failure->getMessage(); }
            $this->history->append($record + ['status' => $rollbackError === '' ? 'rolled_back' : 'rollback_failed',
                'error' => $error->getMessage(), 'rollback_error' => $rollbackError]);
            $prefix = $rollbackError === '' ? 'update_apply_failed_rolled_back' : 'update_apply_rollback_failed';
            throw new \RuntimeException($prefix . ':' . $error->getMessage() . ($rollbackError !== '' ? ':' . $rollbackError : ''), 0, $error);
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    public function assertWritableTargets(array $manifest): void
    {
        $this->assertCoreManifestOwnership($manifest);
        $targets = array_merge(array_keys((array) ($manifest['files'] ?? [])), (array) ($manifest['remove'] ?? []));
        $checked = [];
        $blocked = [];
        foreach ($targets as $relative) {
            $parent = dirname($this->targetPath((string) $relative));
            while (!is_dir($parent)) {
                $next = dirname($parent);
                if ($next === $parent) { throw new \RuntimeException('update_preflight_permissions_failed'); }
                $parent = $next;
            }
            if (isset($checked[$parent])) { continue; }
            $checked[$parent] = true;
            $probe = $this->paths->resolve($parent . '/.flatcms-update-preflight-' . bin2hex(random_bytes(6)) . '.tmp');
            $handle = @fopen($probe, 'xb');
            if (!is_resource($handle)) { $blocked[] = $relative; continue; }
            try { $written = fwrite($handle, 'flatcms'); }
            finally { fclose($handle); $removed = unlink($probe); }
            if ($written !== 7 || !$removed) { $blocked[] = $relative; }
        }
        if ($blocked !== []) { throw new \RuntimeException('update_preflight_permissions_failed:' . count($blocked) . ':' . implode('|', array_slice($blocked, 0, 8))); }
    }

    private function acquireLock()
    {
        $this->paths->ensureDirectory('storage/cache/update-manager', 0750);
        $handle = fopen($this->paths->resolve('storage/cache/update-manager/apply.lock'), 'c+b');
        if (!is_resource($handle) || !flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) { fclose($handle); }
            throw new \RuntimeException('update_already_running');
        }
        return $handle;
    }

    private function assertCoreManifestOwnership(array $manifest): void
    {
        foreach (array_merge(array_keys((array) ($manifest['files'] ?? [])), (array) ($manifest['remove'] ?? [])) as $path) {
            $this->targetPath((string) $path);
        }
    }

    private function targetPath(string $path): string
    {
        return $this->paths->resolve($this->pathPolicy->assertAllowed($path, 'update_apply_core_path_forbidden'));
    }
}
