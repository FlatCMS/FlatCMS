<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\CoreManifest;

final class UpdateCatalogService
{
    private const API_VERSION = '1.0';
    private const CATALOGS = ['core', 'modules'];

    public function __construct(private ?UpdateArtifactService $artifacts = null)
    {
        $this->artifacts = $this->artifacts ?? new UpdateArtifactService();
    }

    public function discovery(): array
    {
        $baseUrl = $this->absoluteBaseUrl();
        $catalogs = [];

        foreach (self::CATALOGS as $catalog) {
            $payload = $this->catalog($catalog);
            $packages = is_array($payload['packages'] ?? null) ? $payload['packages'] : [];
            $published = 0;

            foreach ($packages as $package) {
                if (($package['availability'] ?? '') === 'published' && !empty($package['download_ready'])) {
                    $published++;
                }
            }

            $catalogs[$catalog] = [
                'url' => $baseUrl . '/api/updates/' . $catalog . '.json',
                'package_count' => count($packages),
                'published_count' => $published,
            ];
        }

        return [
            'name' => CoreManifest::name('FlatCMS') . ' Update API',
            'api_version' => self::API_VERSION,
            'generated_at' => gmdate('c'),
            'catalogs' => $catalogs,
        ];
    }

    public function catalog(string $catalog): ?array
    {
        $catalog = strtolower(trim($catalog));
        if (!in_array($catalog, self::CATALOGS, true)) {
            return null;
        }

        $registry = json_read($this->registryPath($catalog));
        if (!is_array($registry)) {
            $registry = [];
        }

        $packages = is_array($registry['packages'] ?? null) ? $registry['packages'] : [];
        $normalizedPackages = [];

        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }

            $normalizedPackages[] = $this->normalizePackage($catalog, $package);
        }

        usort($normalizedPackages, static function (array $left, array $right): int {
            return version_compare((string) ($right['version'] ?? '0.0.0'), (string) ($left['version'] ?? '0.0.0'));
        });

        return [
            'catalog' => $catalog,
            'api_version' => self::API_VERSION,
            'generated_at' => gmdate('c'),
            'channel' => (string) ($registry['channel'] ?? 'stable'),
            'packages' => $normalizedPackages,
        ];
    }

    public function resolveDownload(string $catalog, string $slug, string $version): ?array
    {
        $package = $this->findPackageRecord($catalog, $slug, $version);
        if (!is_array($package)) {
            return null;
        }

        $artifact = $this->artifacts->resolveArtifact($package);
        if (is_array($artifact)) {
            return $artifact;
        }

        $downloadPath = trim((string) ($package['download_path'] ?? ''));
        $downloadFile = $this->publicFileFromDownloadPath($downloadPath);
        if ($downloadFile === '' || !is_file($downloadFile)) {
            return null;
        }

        return [
            'product_id' => trim((string) ($package['slug'] ?? '')),
            'version' => trim((string) ($package['version'] ?? '')),
            'file_path' => $downloadFile,
            'filename' => basename($downloadFile),
            'size_bytes' => (int) @filesize($downloadFile),
            'sha256' => trim((string) ($package['sha256'] ?? '')),
            'published_at' => trim((string) ($package['published_at'] ?? '')),
            'release_status' => trim((string) ($package['availability'] ?? 'published')),
        ];
    }

    public function resolveChangelog(string $catalog, string $slug, string $version): ?string
    {
        $package = $this->findPackageRecord($catalog, $slug, $version);
        if (!is_array($package)) {
            return null;
        }

        $changelog = trim((string) ($package['changelog'] ?? ''));
        if ($changelog !== '') {
            return $changelog . PHP_EOL;
        }

        $changelogUrl = trim((string) ($package['changelog_url'] ?? ''));
        $filePath = $this->publicFileFromDownloadPath($changelogUrl);
        if ($filePath === '' || !is_file($filePath)) {
            return null;
        }

        $contents = @file_get_contents($filePath);
        if (!is_string($contents) || $contents === '') {
            return null;
        }

        return $contents;
    }

    private function normalizePackage(string $catalog, array $package): array
    {
        $downloadFile = '';
        $downloadReady = false;
        $downloadUrl = '';
        $sizeBytes = 0;
        $sha256 = (string) ($package['sha256'] ?? '');

        $artifact = $this->artifacts->resolveArtifact($package);
        if (is_array($artifact)) {
            $downloadFile = (string) ($artifact['file_path'] ?? '');
            $downloadReady = $downloadFile !== '' && is_file($downloadFile);
            $downloadUrl = $downloadReady ? ($this->absoluteBaseUrl() . $this->downloadRoutePath($catalog, $package)) : '';
            $sizeBytes = (int) ($artifact['size_bytes'] ?? 0);
            if ($sha256 === '') {
                $sha256 = trim((string) ($artifact['sha256'] ?? ''));
            }
        } else {
            $downloadPath = trim((string) ($package['download_path'] ?? ''));
            $downloadFile = $this->publicFileFromDownloadPath($downloadPath);
            $downloadReady = $downloadFile !== '' && is_file($downloadFile);
            if ($downloadReady) {
                $downloadUrl = $this->absoluteBaseUrl() . $downloadPath;
                $sizeBytes = (int) @filesize($downloadFile);
            }
        }

        $availability = strtolower(trim((string) ($package['availability'] ?? 'draft')));
        if ($availability === 'published' && !$downloadReady) {
            $availability = 'draft';
        }
        if (!in_array($availability, ['draft', 'published', 'archived'], true)) {
            $availability = $downloadReady ? 'published' : 'draft';
        }

        $changelogUrl = '';
        if (trim((string) ($package['changelog'] ?? '')) !== '') {
            $changelogUrl = $this->absoluteBaseUrl() . $this->changelogRoutePath($catalog, $package);
        } else {
            $legacyChangelogUrl = trim((string) ($package['changelog_url'] ?? ''));
            if ($legacyChangelogUrl !== '' && str_starts_with($legacyChangelogUrl, '/')) {
                $changelogUrl = $this->absoluteBaseUrl() . $legacyChangelogUrl;
            }
        }

        return [
            'slug' => (string) ($package['slug'] ?? ''),
            'name' => (string) ($package['name'] ?? ''),
            'type' => (string) ($package['type'] ?? $catalog),
            'version' => (string) ($package['version'] ?? ''),
            'channel' => (string) ($package['channel'] ?? 'stable'),
            'vendor' => (string) ($package['vendor'] ?? 'flatcms'),
            'official' => !empty($package['official']),
            'requires_php' => (string) ($package['requires_php'] ?? ''),
            'download_url' => $downloadUrl,
            'download_ready' => $downloadReady,
            'sha256' => $sha256,
            'signature' => (string) ($package['signature'] ?? ''),
            'min_core_version' => (string) ($package['min_core_version'] ?? ''),
            'max_core_version' => (string) ($package['max_core_version'] ?? ''),
            'changelog_url' => $changelogUrl,
            'changelog' => (string) ($package['changelog'] ?? ''),
            'published_at' => (string) ($package['published_at'] ?? ''),
            'availability' => $availability,
            'size_bytes' => $downloadReady ? max(0, $sizeBytes) : 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findPackageRecord(string $catalog, string $slug, string $version): ?array
    {
        $catalog = strtolower(trim($catalog));
        $slug = trim($slug);
        $version = trim($version);
        if (!in_array($catalog, self::CATALOGS, true) || $slug === '' || $version === '') {
            return null;
        }

        $registry = json_read($this->registryPath($catalog));
        if (!is_array($registry)) {
            return null;
        }

        $packages = is_array($registry['packages'] ?? null) ? $registry['packages'] : [];
        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }

            if ((string) ($package['slug'] ?? '') !== $slug) {
                continue;
            }

            if ((string) ($package['version'] ?? '') !== $version) {
                continue;
            }

            return $package;
        }

        return null;
    }

    private function registryPath(string $catalog): string
    {
        return BASE_PATH . '/resources/updates/catalogs/' . $catalog . '.json';
    }

    private function publicFileFromDownloadPath(string $downloadPath): string
    {
        if ($downloadPath === '' || !str_starts_with($downloadPath, '/')) {
            return '';
        }

        $publicPath = defined('PUBLIC_PATH') ? (string) PUBLIC_PATH : (BASE_PATH . '/public');
        return rtrim($publicPath, '/\\') . $downloadPath;
    }

    private function downloadRoutePath(string $catalog, array $package): string
    {
        return '/api/updates/download/'
            . rawurlencode(strtolower($catalog))
            . '/'
            . rawurlencode((string) ($package['slug'] ?? ''))
            . '/'
            . rawurlencode((string) ($package['version'] ?? ''));
    }

    private function changelogRoutePath(string $catalog, array $package): string
    {
        return '/api/updates/changelog/'
            . rawurlencode(strtolower($catalog))
            . '/'
            . rawurlencode((string) ($package['slug'] ?? ''))
            . '/'
            . rawurlencode((string) ($package['version'] ?? ''));
    }

    private function absoluteBaseUrl(): string
    {
        $scheme = 'http';
        if (
            (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
            || strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https'
        ) {
            $scheme = 'https';
        }

        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        if ($host === '') {
            $host = 'localhost';
        }

        $basePath = (string) parse_url((string) base_url(), PHP_URL_PATH);
        $basePath = rtrim($basePath, '/');

        return $scheme . '://' . $host . $basePath;
    }
}
