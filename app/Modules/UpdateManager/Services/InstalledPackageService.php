<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Core\CoreManifest;
use App\Core\ModuleManager;

final class InstalledPackageService
{
    /** @return array<string, array<int, array<string, mixed>>> */
    public function all(): array
    {
        $packages = [
            'core' => [$this->corePackage()],
            'modules' => [],
            'extensions' => [],
            'plugins' => [],
            'themes' => $this->themePackages(),
            'appliances' => [],
        ];

        $manager = new ModuleManager();
        foreach ($manager->all() as $name => $meta) {
            if (!is_array($meta)) {
                continue;
            }

            $catalog = match ((string) ($meta['location'] ?? 'module')) {
                'extension' => 'extensions',
                'plugin' => 'plugins',
                default => 'modules',
            };

            $packages[$catalog][] = [
                'name' => (string) ($meta['name'] ?? $name),
                'slug' => (string) ($meta['key'] ?? $this->slugify((string) $name)),
                'version' => trim((string) ($meta['version'] ?? '0.0.0')),
                'vendor' => $this->vendor($meta),
                'channel' => (string) ($meta['channel'] ?? 'stable'),
                'official' => !empty($meta['official']),
                'enabled' => !empty($meta['enabled']),
                'location' => (string) ($meta['location'] ?? 'module'),
            ];
        }

        return $packages;
    }

    /** @return array<string, mixed> */
    private function corePackage(): array
    {
        $manifest = CoreManifest::all();

        return [
            'name' => CoreManifest::name('FlatCMS'),
            'slug' => (string) ($manifest['slug'] ?? 'flatcms'),
            'version' => CoreManifest::version('1.0.0'),
            'vendor' => $this->vendor($manifest),
            'channel' => (string) ($manifest['channel'] ?? 'stable'),
            'official' => true,
            'enabled' => true,
            'location' => 'core',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function themePackages(): array
    {
        $packages = [];
        foreach (['frontend', 'admin'] as $themeType) {
            $root = BASE_PATH . '/themes/' . $themeType;
            if (!is_dir($root)) {
                continue;
            }

            foreach (glob($root . '/*/theme.json') ?: [] as $manifestPath) {
                $raw = @file_get_contents($manifestPath);
                $manifest = json_decode((string) $raw, true);
                if (!is_array($manifest)) {
                    continue;
                }

                $slug = trim((string) ($manifest['slug'] ?? basename(dirname($manifestPath))));
                $packages[] = [
                    'name' => (string) ($manifest['name'] ?? $slug),
                    'slug' => $slug,
                    'version' => trim((string) ($manifest['version'] ?? '0.0.0')),
                    'vendor' => $this->vendor($manifest),
                    'channel' => (string) ($manifest['channel'] ?? 'stable'),
                    'official' => !empty($manifest['official']),
                    'enabled' => true,
                    'location' => 'theme',
                    'theme_type' => (string) ($manifest['type'] ?? $themeType),
                ];
            }
        }

        return $packages;
    }

    /** @param array<string, mixed> $manifest */
    private function vendor(array $manifest): string
    {
        $vendor = trim((string) ($manifest['vendor'] ?? ''));
        if ($vendor !== '') {
            return strtolower($vendor);
        }

        $origin = strtolower(trim((string) ($manifest['origin'] ?? '')));
        if (!empty($manifest['official']) || $origin === 'flatcms') {
            return 'flatcms';
        }

        $author = trim((string) ($manifest['author'] ?? ''));
        return $author !== '' ? $this->slugify($author) : '';
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
        return trim($value, '-');
    }
}
