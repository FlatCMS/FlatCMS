<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class CoreUpdatePathPolicy
{
    private const TOP_FILES = [
        'VERSION', 'flatcms.json', 'index.php', 'public/index.php',
        'public/.htaccess', 'data/.htaccess', 'storage/.htaccess', 'nginx.conf',
        'README.md', 'LICENSE', 'LICENSING.md', 'COMMERCIAL_LICENSE.md',
        'CLA.md', 'TRADEMARK.md', 'THIRD_PARTY_NOTICES.md',
    ];

    private const ROOTS = [
        'app/Bootstrap', 'app/Controllers', 'app/Core', 'app/Helpers',
        'app/Middleware', 'app/Services', 'app/ThirdParty',
        'bin', 'config', 'public/assets', 'resources',
    ];

    private const BLOCKED_PREFIXES = [
        'app/Modules/',
        'app/Extensions/',
        'app/Plugins/',
        'themes/',
        'public/themes/',
        'public/modules/',
        'data/',
        'storage/',
        'uploads/',
        'public/uploads/',
        'resources/Store/',
        'resources/updates/catalogs/',
    ];

    /** @return array<int,string> */
    public function topFiles(): array
    {
        return self::TOP_FILES;
    }

    /** @return array<int,string> */
    public function roots(): array
    {
        return self::ROOTS;
    }

    public function normalize(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '' || str_contains($path, "\0") || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:/', $path) === 1) {
            return '';
        }

        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                return '';
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    public function isCanonical(string $path): bool
    {
        $raw = trim(str_replace('\\', '/', $path));
        $normalized = $this->normalize($path);
        return $normalized !== '' && $raw === $normalized;
    }

    public function isAllowed(string $path): bool
    {
        $path = $this->normalize($path);
        if ($path === '' || in_array($path, ['.env', '.env.local'], true)) {
            return false;
        }

        if (in_array($path, self::TOP_FILES, true)) {
            return true;
        }

        foreach (self::BLOCKED_PREFIXES as $blocked) {
            if (str_starts_with($path, $blocked)) {
                return false;
            }
        }

        foreach (self::ROOTS as $root) {
            if (str_starts_with($path, rtrim($root, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    public function assertAllowed(string $path, string $error = 'update_core_path_forbidden'): string
    {
        $normalized = $this->normalize($path);
        if (!$this->isCanonical($path) || !$this->isAllowed($normalized)) {
            throw new \RuntimeException($error . ':' . ($normalized !== '' ? $normalized : $path));
        }
        return $normalized;
    }

    public function isAllowedDirectory(string $path): bool
    {
        $path = $this->normalize($path);
        if ($path === '') {
            return false;
        }
        $candidate = rtrim($path, '/') . '/';
        foreach (self::BLOCKED_PREFIXES as $blocked) {
            if (str_starts_with($candidate, $blocked) || str_starts_with($blocked, $candidate)) {
                return false;
            }
        }
        foreach (self::ROOTS as $root) {
            $root = rtrim($root, '/') . '/';
            if (str_starts_with($candidate, $root) || str_starts_with($root, $candidate)) {
                return true;
            }
        }
        return false;
    }
}
