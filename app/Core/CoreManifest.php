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

final class CoreManifest
{
    private const DEFAULTS = [
        'name' => 'FlatCMS',
        'slug' => 'flatcms',
        'type' => 'core',
        'version' => '1.0.0',
        'channel' => 'stable',
        'vendor' => 'flatcms',
        'official' => true,
        'signature' => '',
        'requires_php' => '8.1',
    ];

    private static ?array $cached = null;

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (is_array(self::$cached)) {
            return self::$cached;
        }

        $manifest = self::DEFAULTS;
        $path = self::manifestPath();

        if (is_file($path)) {
            $raw = file_get_contents($path);
            $data = json_decode((string) $raw, true);
            if (is_array($data)) {
                $manifest = array_replace(self::DEFAULTS, $data);
            }
        }

        $manifest['version'] = self::versionFromLegacyFile(trim((string) ($manifest['version'] ?? (string) self::DEFAULTS['version'])));

        self::$cached = $manifest;
        return self::$cached;
    }

    public static function get(?string $key = null, mixed $default = null): mixed
    {
        $manifest = self::all();
        if ($key === null || trim($key) === '') {
            return $manifest;
        }

        return $manifest[$key] ?? $default;
    }

    public static function name(string $fallback = 'FlatCMS'): string
    {
        $value = trim((string) self::get('name', $fallback));
        return $value !== '' ? $value : $fallback;
    }

    public static function version(string $fallback = '1.0.0'): string
    {
        $value = trim((string) self::get('version', ''));
        if ($value !== '') {
            return $value;
        }

        return self::versionFromLegacyFile($fallback);
    }

    private static function manifestPath(): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        return rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'flatcms.json';
    }

    private static function versionFromLegacyFile(string $fallback): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $versionFile = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'VERSION';
        if (!is_file($versionFile)) {
            return $fallback;
        }

        $raw = trim((string) file_get_contents($versionFile));
        if ($raw === '') {
            return $fallback;
        }

        $candidates = [];
        if (preg_match('/VERSION\s*=\s*"([^"]+)"/i', $raw, $match) === 1) {
            $candidates[] = (string) ($match[1] ?? '');
        }
        if (preg_match("/VERSION\\s*=\\s*'([^']+)'/i", $raw, $match) === 1) {
            $candidates[] = (string) ($match[1] ?? '');
        }
        if (preg_match('/\b([0-9]+(?:\.[0-9A-Za-z_-]+)+)\b/', $raw, $match) === 1) {
            $candidates[] = (string) ($match[1] ?? '');
        }
        if (preg_match('/^\s*([0-9A-Za-z._+-]+)\s*$/', $raw, $match) === 1) {
            $candidates[] = (string) ($match[1] ?? '');
        }

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $fallback;
    }
}
