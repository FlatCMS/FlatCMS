<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core;

class TranslationScanner
{
    /**
     * Scan all PHP files in app/ (except Install) and themes (root + public) for __() calls.
     *
     * @return array<string, string[]> ['Module' => ['key1', 'key2', ...]]
     */
    public static function scanCodeUsage(): array
    {
        $usage = [];
        $manager = new ModuleManager();
        $enabledModules = array_flip($manager->enabledNames());

        $dirs = [
            BASE_PATH . '/app',
            BASE_PATH . '/themes',
            BASE_PATH . '/public/themes',
        ];

        $pattern = '/__\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*[\'"]([^\'"]+)[\'"])?\s*(?:,\s*\[.*?\])?\s*\)/s';

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                // Skip Install module
                $path = $file->getPathname();
                if (str_contains($path, '/Modules/Install/')) {
                    continue;
                }

                // Skip disabled modules
                if (preg_match('#/(Modules|Extensions)/([^/]+)/#', $path, $match)) {
                    $moduleName = $match[2] ?? '';
                    if ($moduleName !== '' && !isset($enabledModules[$moduleName])) {
                        continue;
                    }
                }

                $content = file_get_contents($path);
                if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $key = $match[1];
                        $module = $match[2] ?? 'Core';

                        // Ignore translation keys bound to modules that are not enabled.
                        // This prevents optional module keys from leaking into Languages UI
                        // when helpers/templates reference them conditionally.
                        if ($module !== 'Core' && !isset($enabledModules[$module])) {
                            continue;
                        }

                        $usage[$module][] = $key;
                    }
                }
            }
        }

        // Deduplicate keys per module
        foreach ($usage as $module => $keys) {
            $usage[$module] = array_values(array_unique($keys));
        }

        ksort($usage);

        return $usage;
    }

    /**
     * Get all defined keys for a locale/module, flattened with dot notation for nested keys.
     *
     * @return string[]
     */
    public static function getDefinedKeys(string $locale, string $module): array
    {
        $path = I18n::resolveTranslationPathForNamespace($module, $locale);

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return [];
        }

        return self::flattenKeys($data);
    }

    /**
     * Get global completion percentage: used keys that are defined vs total used keys.
     */
    public static function getCompletionStats(string $locale): array
    {
        $usage = self::scanCodeUsage();
        $totalUsed = 0;
        $totalDefined = 0;

        foreach ($usage as $module => $keys) {
            $definedKeys = self::getDefinedKeys($locale, $module);

            foreach ($keys as $key) {
                $totalUsed++;
                if (in_array($key, $definedKeys, true)) {
                    $totalDefined++;
                }
            }
        }

        $missing = max(0, $totalUsed - $totalDefined);
        $percentage = $totalUsed > 0 ? (int) floor(($totalDefined / $totalUsed) * 100) : 100;
        if ($missing > 0 && $percentage >= 100) {
            $percentage = 99;
        }

        return [
            'total_used' => $totalUsed,
            'total_defined' => $totalDefined,
            'percentage' => (int) $percentage,
        ];
    }

    /**
     * Get per-module stats with list of missing keys.
     *
     * @return array<string, array{used: int, defined: int, percentage: int, missing: string[]}>
     */
    public static function getModuleStats(string $locale): array
    {
        $usage = self::scanCodeUsage();
        $stats = [];

        foreach ($usage as $module => $keys) {
            $definedKeys = self::getDefinedKeys($locale, $module);
            $missing = [];

            foreach ($keys as $key) {
                if (!in_array($key, $definedKeys, true)) {
                    $missing[] = $key;
                }
            }

            $usedCount = count($keys);
            $missingCount = count($missing);
            $definedCount = $usedCount - $missingCount;
            $percentage = $usedCount > 0 ? (int) floor(($definedCount / $usedCount) * 100) : 100;
            if ($missingCount > 0 && $percentage >= 100) {
                $percentage = 99;
            }

            $stats[$module] = [
                'used' => $usedCount,
                'defined' => $definedCount,
                'percentage' => (int) $percentage,
                'missing' => $missing,
            ];
        }

        ksort($stats);

        return $stats;
    }

    /**
     * Flatten a nested array into dot-notation keys.
     *
     * @return string[]
     */
    private static function flattenKeys(array $data, string $prefix = ''): array
    {
        $keys = [];

        foreach ($data as $key => $value) {
            $fullKey = $prefix !== '' ? "{$prefix}.{$key}" : (string) $key;

            // Always include the current key
            $keys[] = $fullKey;

            // Also recurse into nested arrays
            if (is_array($value)) {
                $keys = array_merge($keys, self::flattenKeys($value, $fullKey));
            }
        }

        return $keys;
    }
}
