<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/ThemeResolver.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Core;

use App\Core\Storage\StoragePathGuard;

/** Read-only selection shared by templates, assets and theme customization. */
final class ThemeResolver
{
    public static function active(string $type, ?array $settings = null): string
    {
        if (!in_array($type, ['admin', 'frontend'], true)) {
            throw new \InvalidArgumentException('Invalid theme type.');
        }
        $settings ??= FlatFile::settings();
        $default = $type === 'admin' ? 'admin-modern-pro' : 'default';
        $configured = (string) ($settings[$type . '_theme'] ?? config('app.' . $type . '_theme', $default));
        $candidates = array_unique([$configured, 'default', $type === 'admin' ? 'admin-modern-pro' : 'modern-pro']);
        foreach ($candidates as $name) {
            if (self::available($type, $name)) { return $name; }
        }
        foreach (glob(BASE_PATH . '/themes/' . $type . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $name = basename($directory);
            if (self::available($type, $name)) { return $name; }
        }
        throw new \RuntimeException('No usable installed ' . $type . ' theme.');
    }

    private static function available(string $type, string $name): bool
    {
        if (preg_match('/^[a-zA-Z0-9_-]+$/D', $name) !== 1) { return false; }
        try {
            $paths = new StoragePathGuard(BASE_PATH);
            $prefix = 'themes/' . $type . '/' . $name;
            $manifest = $paths->resolve($prefix . '/theme.json');
            $layout = $paths->resolve($prefix . '/views/layouts/main.php');
            if (!is_file($manifest) || !is_file($layout)) { return false; }
            $metadata = json_decode((string) file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
            return is_array($metadata) && ($metadata['type'] ?? $type) === $type
                && ($metadata['slug'] ?? $name) === $name;
        } catch (\Throwable) {
            // A missing, invalid or externally linked theme is not a Core dependency.
            return false;
        }
    }
}
