<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Settings\Services;

final class CacheManager
{
    public const ACTION_CLEAR_APP_CACHE = 'clear_app_cache';
    public const ACTION_CLEAR_RUNTIME_CSS = 'clear_runtime_css';
    public const ACTION_RESET_OPCACHE = 'reset_opcache';
    public const ACTION_CLEAR_ALL = 'clear_all';

    /**
     * @return array<string, mixed>
     */
    public function diagnostics(): array
    {
        $loadedIni = (string) (php_ini_loaded_file() ?: '');
        $scanDir = trim((string) getenv('PHP_INI_SCAN_DIR'));
        if ($scanDir === '' && $loadedIni !== '') {
            $scanDir = dirname($loadedIni) . '/conf.d';
        }

        $opcacheEnabled = $this->iniBool('opcache.enable');
        $validateTimestamps = $this->iniBool('opcache.validate_timestamps');
        $revalidateFreq = $this->iniInt('opcache.revalidate_freq');
        $fileUpdateProtection = $this->iniInt('opcache.file_update_protection');

        return [
            'environment' => (string) env('APP_ENV', 'production'),
            'is_local_request' => is_local_host(),
            'asset_version_mode' => flatcms_asset_version_mode(),
            'php_sapi' => PHP_SAPI,
            'php_ini' => $loadedIni,
            'php_scan_dir' => $scanDir,
            'additional_ini_files' => $this->additionalIniFiles(),
            'opcache_enabled' => $opcacheEnabled,
            'opcache_reset_available' => function_exists('opcache_reset'),
            'opcache_validate_timestamps' => $validateTimestamps,
            'opcache_revalidate_freq' => $revalidateFreq,
            'opcache_file_update_protection' => $fileUpdateProtection,
            'realpath_cache_ttl' => $this->iniInt('realpath_cache_ttl'),
            'local_dev_recommendation_ok' => !$opcacheEnabled || ($validateTimestamps && $revalidateFreq === 0 && $fileUpdateProtection === 0),
            'cache_groups' => $this->cacheGroups(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function runAction(string $action): array
    {
        return match ($action) {
            self::ACTION_CLEAR_APP_CACHE => $this->clearAppCache(),
            self::ACTION_CLEAR_RUNTIME_CSS => $this->clearRuntimeCssCache(),
            self::ACTION_RESET_OPCACHE => $this->resetOpcacheOnly(),
            self::ACTION_CLEAR_ALL => $this->clearAll(),
            default => [
                'success' => false,
                'removed' => 0,
                'warnings' => [],
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function clearAppCache(): array
    {
        $removed = 0;
        $removed += $this->purgeDirectoryContents($this->storageCacheDataPath());
        $removed += $this->purgeDirectoryContents($this->storageCacheViewsPath());
        clearstatcache(true);

        return [
            'success' => true,
            'removed' => $removed,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clearRuntimeCssCache(): array
    {
        $removed = $this->purgeDirectoryContents($this->runtimeCssCachePath());
        clearstatcache(true);

        return [
            'success' => true,
            'removed' => $removed,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resetOpcacheOnly(): array
    {
        $warnings = [];
        $success = $this->resetOpcache($warnings);
        clearstatcache(true);

        return [
            'success' => $success,
            'removed' => 0,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clearAll(): array
    {
        $removed = 0;
        $removed += $this->purgeDirectoryContents($this->storageCacheDataPath());
        $removed += $this->purgeDirectoryContents($this->storageCacheViewsPath());
        $removed += $this->purgeDirectoryContents($this->runtimeCssCachePath());

        $warnings = [];
        $this->resetOpcache($warnings);
        clearstatcache(true);

        return [
            'success' => true,
            'removed' => $removed,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param array<int, string> $warnings
     */
    private function resetOpcache(array &$warnings): bool
    {
        if (!function_exists('opcache_reset')) {
            $warnings[] = 'settings_advanced_warning_opcache_unavailable';
            return false;
        }

        $enabled = $this->iniBool('opcache.enable');
        if (!$enabled) {
            $warnings[] = 'settings_advanced_warning_opcache_unavailable';
            return false;
        }

        $result = @opcache_reset();
        if ($result !== true) {
            $warnings[] = 'settings_advanced_warning_opcache_reset_failed';
            return false;
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function additionalIniFiles(): array
    {
        $files = php_ini_scanned_files();
        if (!is_string($files) || trim($files) === '') {
            return [];
        }

        $items = preg_split('/[\n,]+/', $files) ?: [];
        $result = [];
        foreach ($items as $item) {
            $path = trim((string) $item);
            if ($path === '') {
                continue;
            }
            $result[] = $path;
        }

        return array_values(array_unique($result));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cacheGroups(): array
    {
        $groups = [
            [
                'key' => 'data',
                'label_key' => 'settings_advanced_cache_data',
                'path' => $this->storageCacheDataPath(),
            ],
            [
                'key' => 'views',
                'label_key' => 'settings_advanced_cache_views',
                'path' => $this->storageCacheViewsPath(),
            ],
            [
                'key' => 'runtime_css',
                'label_key' => 'settings_advanced_cache_runtime_css',
                'path' => $this->runtimeCssCachePath(),
            ],
        ];

        foreach ($groups as &$group) {
            $stats = $this->directoryStats((string) ($group['path'] ?? ''));
            $group['entries'] = $stats['entries'];
            $group['size_bytes'] = $stats['size_bytes'];
        }
        unset($group);

        return $groups;
    }

    private function storageCacheDataPath(): string
    {
        return rtrim(BASE_PATH, '/') . '/storage/cache/data';
    }

    private function storageCacheViewsPath(): string
    {
        return rtrim(BASE_PATH, '/') . '/storage/cache/views';
    }

    private function runtimeCssCachePath(): string
    {
        return rtrim(PUBLIC_PATH, '/') . '/uploads/cache/runtime-css';
    }

    private function iniBool(string $key): bool
    {
        $value = strtolower(trim((string) ini_get($key)));
        return in_array($value, ['1', 'on', 'true', 'yes'], true);
    }

    private function iniInt(string $key): int
    {
        return (int) ini_get($key);
    }

    private function purgeDirectoryContents(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $removed = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $basename = (string) $item->getBasename();
            if ($basename === '.gitkeep') {
                continue;
            }

            $pathname = $item->getPathname();
            if ($item->isDir()) {
                if (@rmdir($pathname)) {
                    $removed++;
                }
                continue;
            }

            if (@unlink($pathname)) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @return array{entries:int,size_bytes:int}
     */
    private function directoryStats(string $path): array
    {
        if (!is_dir($path)) {
            return [
                'entries' => 0,
                'size_bytes' => 0,
            ];
        }

        $entries = 0;
        $sizeBytes = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            if ((string) $item->getBasename() === '.gitkeep') {
                continue;
            }

            $entries++;
            $sizeBytes += (int) $item->getSize();
        }

        return [
            'entries' => $entries,
            'size_bytes' => $sizeBytes,
        ];
    }
}
