<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Backups/Services/SiteBackupBaselineService.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Backups\Services;

final class SiteBackupBaselineService
{
    private const SCHEMA_VERSION = 1;

    /** @var list<string> */
    private const PROTECTED_ROOTS = [
        'app/Bootstrap',
        'app/Controllers',
        'app/Core',
        'app/Helpers',
        'app/Services',
        'app/ThirdParty',
        'bin',
        'config',
        'public/assets/css',
        'public/assets/dists',
        'public/assets/img',
        'public/assets/install',
        'public/assets/js',
        'resources/server',
        'resources/views',
    ];

    /** @var list<string> */
    private const PROTECTED_FILES = [
        '.htaccess',
        'VERSION',
        'flatcms.json',
        'index.php',
        'public/.htaccess',
        'public/index.php',
        'public/recovery.php',
        'recovery.php',
    ];

    private string $basePath;
    private string $baselinePath;

    public function __construct(?string $basePath = null, ?string $baselinePath = null)
    {
        $this->basePath = rtrim($basePath ?: BASE_PATH, '/\\');
        $this->baselinePath = $baselinePath
            ?: $this->basePath . '/resources/backups/site-baseline.json';
    }

    /** @return array<string,mixed> */
    public function generate(): array
    {
        $protected = [];
        foreach (self::PROTECTED_ROOTS as $relative) {
            $protected[$relative] = $this->fingerprintDirectory($relative);
        }
        foreach (self::PROTECTED_FILES as $relative) {
            $protected[$relative] = $this->fingerprintFile($relative);
        }
        ksort($protected);

        return [
            'schema' => self::SCHEMA_VERSION,
            'flatcms_version' => $this->readVersion(),
            'protected_roots' => $protected,
            'components' => [
                'modules' => $this->componentInventory('app/Modules', 'module.json'),
                'extensions' => $this->componentInventory('app/Extensions', 'extension.json'),
                'plugins' => $this->componentInventory('app/Plugins', 'plugin.json'),
                'themes' => $this->themeInventory(),
            ],
        ];
    }

    /** @return array<string,mixed> */
    public function write(): array
    {
        $baseline = $this->generate();
        $directory = dirname($this->baselinePath);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('backups_site_baseline_write_failed');
        }

        $encoded = json_encode(
            $baseline,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        if (!is_string($encoded)) {
            throw new \RuntimeException('backups_site_baseline_write_failed');
        }

        $temporary = $this->baselinePath . '.tmp-' . bin2hex(random_bytes(4));
        if (@file_put_contents($temporary, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('backups_site_baseline_write_failed');
        }
        @chmod($temporary, 0644);
        if (!@rename($temporary, $this->baselinePath)) {
            @unlink($temporary);
            throw new \RuntimeException('backups_site_baseline_write_failed');
        }

        return [
            'path' => $this->baselinePath,
            'flatcms_version' => (string) $baseline['flatcms_version'],
            'modules' => count((array) ($baseline['components']['modules'] ?? [])),
            'extensions' => count((array) ($baseline['components']['extensions'] ?? [])),
            'plugins' => count((array) ($baseline['components']['plugins'] ?? [])),
            'themes' => count((array) ($baseline['components']['themes'] ?? [])),
        ];
    }

    /** @return array<string,mixed> */
    public function inspect(): array
    {
        $baseline = $this->read();
        $current = $this->generate();

        $protectedChanges = [];
        foreach ((array) ($baseline['protected_roots'] ?? []) as $relative => $expected) {
            $actual = $current['protected_roots'][$relative] ?? null;
            if (!$this->sameFingerprint($expected, $actual)) {
                $protectedChanges[] = (string) $relative;
            }
        }

        $added = [];
        $changed = [];
        $missing = [];
        foreach (['modules', 'extensions', 'plugins', 'themes'] as $catalog) {
            $expected = (array) ($baseline['components'][$catalog] ?? []);
            $actual = (array) ($current['components'][$catalog] ?? []);

            foreach (array_diff(array_keys($actual), array_keys($expected)) as $key) {
                $added[$catalog][] = $actual[$key];
            }
            foreach (array_diff(array_keys($expected), array_keys($actual)) as $key) {
                $missing[$catalog][] = $expected[$key];
            }
            foreach (array_intersect(array_keys($expected), array_keys($actual)) as $key) {
                if (!$this->sameFingerprint($expected[$key], $actual[$key])) {
                    $changed[$catalog][] = [
                        'expected' => $expected[$key],
                        'actual' => $actual[$key],
                    ];
                }
            }
        }

        $version = (string) ($current['flatcms_version'] ?? '');
        $expectedVersion = (string) ($baseline['flatcms_version'] ?? '');
        $versionCompatible = $version !== '' && hash_equals($expectedVersion, $version);

        return [
            'portable' => $versionCompatible
                && $protectedChanges === []
                && $changed === []
                && $missing === [],
            'version_compatible' => $versionCompatible,
            'flatcms_version' => $version,
            'baseline_version' => $expectedVersion,
            'protected_changes' => $protectedChanges,
            'added' => $added,
            'changed' => $changed,
            'missing' => $missing,
        ];
    }

    /** @return array<string,mixed> */
    public function read(): array
    {
        if (!is_file($this->baselinePath) || is_link($this->baselinePath)) {
            throw new \RuntimeException('backups_site_baseline_missing');
        }

        try {
            $decoded = json_decode(
                (string) file_get_contents($this->baselinePath),
                true,
                64,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            throw new \RuntimeException('backups_site_baseline_invalid');
        }

        if (!is_array($decoded)
            || (int) ($decoded['schema'] ?? 0) !== self::SCHEMA_VERSION
            || trim((string) ($decoded['flatcms_version'] ?? '')) === ''
            || !is_array($decoded['protected_roots'] ?? null)
            || !is_array($decoded['components'] ?? null)) {
            throw new \RuntimeException('backups_site_baseline_invalid');
        }

        return $decoded;
    }

    /** @return array<string,array<string,mixed>> */
    private function componentInventory(string $relativeRoot, string $manifestName): array
    {
        $absoluteRoot = $this->absolute($relativeRoot);
        if (!is_dir($absoluteRoot)) {
            return [];
        }

        $inventory = [];
        $entries = scandir($absoluteRoot);
        if (!is_array($entries)) {
            throw new \RuntimeException('backups_site_baseline_read_failed');
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $relative = $relativeRoot . '/' . $entry;
            $absolute = $this->absolute($relative);
            if (!is_dir($absolute) || is_link($absolute)) {
                continue;
            }

            $manifestPath = $absolute . '/' . $manifestName;
            if (!is_file($manifestPath) || is_link($manifestPath)) {
                continue;
            }

            $inventory[$entry] = $this->fingerprintDirectory($relative)
                + ['identity' => $this->manifestIdentity($manifestPath)];
        }

        ksort($inventory);
        return $inventory;
    }

    /** @return array<string,array<string,mixed>> */
    private function themeInventory(): array
    {
        $inventory = [];
        foreach (['frontend', 'admin'] as $type) {
            $root = 'themes/' . $type;
            foreach ($this->componentInventory($root, 'theme.json') as $name => $meta) {
                $inventory[$type . '/' . $name] = $meta + ['theme_type' => $type];
            }
        }
        ksort($inventory);
        return $inventory;
    }

    /** @return array{path:string,sha256:string,files_count:int,size_bytes:int} */
    private function fingerprintDirectory(string $relative): array
    {
        $absolute = $this->absolute($relative);
        if (!is_dir($absolute) || is_link($absolute)) {
            throw new \RuntimeException('backups_site_baseline_root_missing');
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isLink()) {
                throw new \RuntimeException('backups_site_baseline_symlink_forbidden');
            }
            if (!$item->isFile()) {
                continue;
            }
            $basename = $item->getBasename();
            if ($basename === '.DS_Store' || str_starts_with($basename, '._')) {
                continue;
            }
            $pathname = $item->getPathname();
            $child = ltrim(str_replace('\\', '/', substr($pathname, strlen($absolute))), '/');
            if ($this->isNonRuntimePath($child)) {
                continue;
            }
            $files[$child] = $pathname;
        }
        ksort($files);

        $hash = hash_init('sha256');
        $bytes = 0;
        foreach ($files as $child => $pathname) {
            $size = filesize($pathname);
            $digest = hash_file('sha256', $pathname);
            if ($size === false || !is_string($digest)) {
                throw new \RuntimeException('backups_site_baseline_read_failed');
            }
            $bytes += $size;
            hash_update($hash, $child . "\0" . $size . "\0" . $digest . "\n");
        }

        return [
            'path' => $relative,
            'sha256' => hash_final($hash),
            'files_count' => count($files),
            'size_bytes' => $bytes,
        ];
    }

    private function isNonRuntimePath(string $relative): bool
    {
        foreach (['.git', '.svn', '.idea', '.vscode'] as $directory) {
            if ($relative === $directory || str_starts_with($relative, $directory . '/')) {
                return true;
            }
        }

        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        return in_array($extension, [
            'bak', 'tmp', 'orig', 'rej',
            'zip', 'tar', 'tgz', 'gz',
        ], true);
    }

    /** @return array{path:string,sha256:string,files_count:int,size_bytes:int} */
    private function fingerprintFile(string $relative): array
    {
        $absolute = $this->absolute($relative);
        if (!is_file($absolute) || is_link($absolute)) {
            throw new \RuntimeException('backups_site_baseline_root_missing');
        }

        $size = filesize($absolute);
        $digest = hash_file('sha256', $absolute);
        if (!is_int($size) || !is_string($digest)) {
            throw new \RuntimeException('backups_site_baseline_read_failed');
        }

        return [
            'path' => $relative,
            'sha256' => $digest,
            'files_count' => 1,
            'size_bytes' => $size,
        ];
    }

    /** @return array<string,mixed> */
    private function manifestIdentity(string $path): array
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('backups_site_baseline_manifest_invalid');
        }

        return array_intersect_key($decoded, array_flip([
            'name', 'slug', 'version', 'type', 'vendor', 'official', 'origin',
        ]));
    }

    private function sameFingerprint(mixed $expected, mixed $actual): bool
    {
        if (!is_array($expected) || !is_array($actual)) {
            return false;
        }
        $left = strtolower(trim((string) ($expected['sha256'] ?? '')));
        $right = strtolower(trim((string) ($actual['sha256'] ?? '')));
        return preg_match('/^[a-f0-9]{64}$/D', $left) === 1
            && hash_equals($left, $right);
    }

    private function absolute(string $relative): string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');
        if ($relative === ''
            || str_contains($relative, "\0")
            || str_contains($relative, ':')
            || preg_match('#(^|/)\.\.?(/|$)#', $relative)) {
            throw new \RuntimeException('backups_site_baseline_path_invalid');
        }

        return $this->basePath . '/' . $relative;
    }

    private function readVersion(): string
    {
        $manifest = $this->basePath . '/flatcms.json';
        if (is_file($manifest)) {
            $decoded = json_decode((string) file_get_contents($manifest), true);
            $version = is_array($decoded) ? trim((string) ($decoded['version'] ?? '')) : '';
            if ($version !== '') {
                return $version;
            }
        }

        $legacy = trim((string) @file_get_contents($this->basePath . '/VERSION'));
        if ($legacy !== '') {
            if (preg_match('/\b([0-9]+(?:\.[0-9A-Za-z_-]+)+)\b/', $legacy, $match) === 1) {
                return (string) $match[1];
            }
            return $legacy;
        }

        throw new \RuntimeException('backups_site_baseline_version_missing');
    }
}
