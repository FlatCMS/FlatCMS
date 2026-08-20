<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateApplyService
{
    private string $basePath;

    public function __construct(
        private ?UpdateCacheService $cache = null,
        private ?UpdateDownloadService $downloads = null,
        private ?UpdateCorePackageService $packages = null,
        private ?UpdateTransactionService $transactions = null,
        private ?UpdateHealthCheckService $health = null,
        private ?UpdateDiskSpaceService $disk = null,
        private ?RecoveryCapsuleService $recovery = null,
        private ?UpdateRequirementService $requirements = null,
        ?string $basePath = null
    ) {
        $this->basePath = rtrim($basePath ?: BASE_PATH, '/\\');
        $this->cache ??= new UpdateCacheService($this->basePath . '/storage/cache/update-manager/status.json');
        $this->downloads ??= new UpdateDownloadService(null, $this->basePath . '/storage/tmp/update-manager');
        $this->packages ??= new UpdateCorePackageService($this->basePath . '/storage/tmp/update-manager');
        $this->transactions ??= new UpdateTransactionService($this->basePath);
        $this->health ??= new UpdateHealthCheckService();
        $this->disk ??= new UpdateDiskSpaceService();
        $this->recovery ??= new RecoveryCapsuleService($this->basePath);
        $this->requirements ??= new UpdateRequirementService();
    }

    /** @return array<string,mixed> */
    public function apply(string $catalog, string $slug, string $version): array
    {
        $catalog = strtolower(trim($catalog));
        $slug = trim($slug);
        $version = trim($version);
        if ($catalog !== 'core' || $slug !== 'flatcms' || $version === '') {
            throw new \RuntimeException('update_apply_family_not_supported');
        }

        $package = $this->findCachedPackage($catalog, $slug, $version);
        $package['catalog'] = $catalog;
        $package['version'] = $version;
        $package['official'] = true;

        $this->disk->assertBeforeDownload($package, $this->basePath);
        $download = $this->downloads->download($package);
        $archivePath = (string) ($download['path'] ?? '');
        $prepared = [];
        $previousMaintenance = null;
        $result = null;
        $failure = null;

        try {
            $prepared = $this->packages->prepare($archivePath, $version);
            $manifest = is_array($prepared['manifest'] ?? null) ? $prepared['manifest'] : [];
            $this->requirements->assertInstalled($manifest['requires_packages'] ?? []);
            $this->disk->assertBeforeApply($prepared, $this->basePath);
            $this->transactions->assertWritableTargets($manifest);
            $recoveryState = $this->recovery->prepareFullBackup($version);
            $package['full_backup_path'] = (string) ($recoveryState['full_backup_path'] ?? '');
            $package['recovery_id'] = (string) ($recoveryState['recovery_id'] ?? '');
            $siteBackup = $this->createSiteBackup($version);
            $package['site_backup_path'] = (string) ($siteBackup['path'] ?? '');
            $this->recovery->markUpdating();
            $previousMaintenance = $this->enableMaintenance();
            $result = $this->transactions->applyCore(
                $prepared,
                $package,
                fn (string $basePath, array $manifest): bool => $this->health->check($basePath, $manifest)
            );
            if (is_array($result)) {
                $result['site_backup'] = $siteBackup;
                $result['full_backup_path'] = (string) ($recoveryState['full_backup_path'] ?? '');
                $result['recovery_id'] = (string) ($recoveryState['recovery_id'] ?? '');
            }
            try {
                $this->cache->clear();
            } catch (\Throwable) {
                // A stale cache must never turn a successful transaction into a failed update.
            }
        } catch (\Throwable $exception) {
            $failure = $exception;
        }

        $maintenanceFailure = $this->restoreMaintenanceSafely($previousMaintenance);
        $this->cleanupPreparedSafely($prepared, $archivePath);

        if ($failure instanceof \Throwable) {
            $message = $failure->getMessage();
            $autoRollback = str_starts_with($message, 'update_apply_failed_rolled_back:');
            $this->recovery->markFailure($message, $autoRollback);
            try {
                if (!$this->recovery->requiresRecovery()) {
                    $this->recovery->cancelIfSafe();
                    $this->recovery->cancelIfUnprepared();
                }
            } catch (\Throwable) {
                // Cleanup of a safe pre-mutation capsule must never mask the original failure.
            }
            if ($maintenanceFailure !== null) {
                $combined = 'update_failed_and_maintenance_restore_failed:' . $message . ':' . $maintenanceFailure;
                $this->recovery->markFailure($combined, $autoRollback);
                throw new \RuntimeException($combined);
            }
            throw $failure;
        }
        if ($maintenanceFailure !== null) {
            $message = 'update_maintenance_restore_failed:' . $maintenanceFailure;
            $this->recovery->markFailure($message, false);
            throw new \RuntimeException($message);
        }
        try {
            $this->recovery->markSuccess();
        } catch (\Throwable) {
            // Monitoring is a safety net; persistence failure must not invalidate a successful Core transaction.
        }
        return is_array($result) ? $result : [];

    }


    /** @return array<string,mixed> */
    private function createSiteBackup(string $version): array
    {
        if (!class_exists(\App\Modules\Backups\Services\SiteBackupService::class)) {
            throw new \RuntimeException('update_site_backup_service_unavailable');
        }
        try {
            return (new \App\Modules\Backups\Services\SiteBackupService())->createBackup([
                'reason' => 'pre_core_update',
                'created_by' => 'UpdateManager',
                'created_by_email' => '',
                'target_version' => $version,
            ]);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('update_site_backup_failed:' . $exception->getMessage(), 0, $exception);
        }
    }

    /** @return array<string,mixed> */
    private function findCachedPackage(string $catalog, string $slug, string $version): array
    {
        $status = $this->cache->read();
        $packages = is_array($status['catalogs'][$catalog]['packages'] ?? null)
            ? $status['catalogs'][$catalog]['packages']
            : [];
        foreach ($packages as $package) {
            if (!is_array($package) || (string) ($package['slug'] ?? '') !== $slug) {
                continue;
            }
            if ((string) ($package['status'] ?? '') !== 'update_available'
                || (string) ($package['compatible_version'] ?? '') !== $version) {
                throw new \RuntimeException('update_apply_version_not_available');
            }
            if (trim((string) ($package['download_url'] ?? '')) === ''
                || trim((string) ($package['sha256'] ?? '')) === ''
                || trim((string) ($package['signature'] ?? '')) === '') {
                throw new \RuntimeException('update_apply_metadata_incomplete');
            }
            return $package;
        }
        throw new \RuntimeException('update_apply_package_not_found');
    }

    private function enableMaintenance(): bool
    {
        $settings = $this->readSettings();
        $previous = !empty($settings['maintenance_mode']);
        if (!$previous) {
            $settings['maintenance_mode'] = true;
            $this->writeSettings($settings);
        }
        return $previous;
    }

    private function restoreMaintenance(bool $previous): void
    {
        if ($previous) {
            return;
        }
        $settings = $this->readSettings();
        $settings['maintenance_mode'] = false;
        $this->writeSettings($settings);
    }

    private function restoreMaintenanceSafely(?bool $previous): ?string
    {
        if ($previous === null) {
            return null;
        }
        try {
            $this->restoreMaintenance($previous);
            return null;
        } catch (\Throwable $exception) {
            return $exception->getMessage();
        }
    }

    /** @return array<string,mixed> */
    private function readSettings(): array
    {
        $path = $this->basePath . '/data/settings.json';
        $data = json_decode((string) @file_get_contents($path), true);
        if (!is_array($data)) {
            throw new \RuntimeException('update_maintenance_settings_invalid');
        }
        return $data;
    }

    /** @param array<string,mixed> $settings */
    private function writeSettings(array $settings): void
    {
        $path = $this->basePath . '/data/settings.json';
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new \RuntimeException('update_maintenance_settings_invalid');
        }
        $tmp = $path . '.update-tmp';
        if (@file_put_contents($tmp, $json . PHP_EOL, LOCK_EX) === false || !@rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('update_maintenance_settings_write_failed');
        }
    }

    /** @param array<string,mixed> $prepared */
    private function cleanupPrepared(array $prepared, string $archivePath): void
    {
        if ($archivePath !== '' && is_file($archivePath)) {
            @unlink($archivePath);
        }
        $extractPath = (string) ($prepared['extract_path'] ?? '');
        if ($extractPath !== '' && is_dir($extractPath)) {
            $this->removeDirectory($extractPath);
        }
    }

    /** @param array<string,mixed> $prepared */
    private function cleanupPreparedSafely(array $prepared, string $archivePath): void
    {
        try {
            $this->cleanupPrepared($prepared, $archivePath);
        } catch (\Throwable) {
            // Temporary cleanup must not hide the result of the transactional update.
        }
    }

    private function removeDirectory(string $path): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($path);
    }
}
