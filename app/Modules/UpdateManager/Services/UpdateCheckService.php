<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateCheckService
{
    private const CATALOGS = ['core', 'modules', 'extensions', 'plugins', 'themes', 'appliances'];

    public function __construct(
        private ?InstalledPackageService $installed = null,
        private ?UpdateRepositoryService $repositories = null,
        private ?UpdateCacheService $cache = null,
        private ?UpdateRequirementService $requirements = null
    ) {
        $this->installed ??= new InstalledPackageService();
        $this->repositories ??= new UpdateRepositoryService();
        $this->cache ??= new UpdateCacheService();
        $this->requirements ??= new UpdateRequirementService($this->installed);
    }

    /** @return array<string, mixed> */
    public function status(bool $force = false): array
    {
        $cached = $this->cache->read();
        if (!$force && $this->cache->isFresh($cached)) {
            $cached['from_cache'] = true;
            return $cached;
        }

        return $this->refresh();
    }

    /** @return array<string, mixed>|null */
    public function cachedStatus(): ?array
    {
        return $this->cache->read();
    }

    /** @return array<string, mixed> */
    public function refresh(): array
    {
        $installed = $this->installed->all();
        $result = [
            'checked_at' => gmdate('c'),
            'core_version' => flatcms_version(),
            'php_version' => PHP_VERSION,
            'from_cache' => false,
            'update_count' => 0,
            'incompatible_count' => 0,
            'installed_count' => 0,
            'errors' => [],
            'catalogs' => [],
        ];

        foreach (self::CATALOGS as $catalog) {
            $localPackages = is_array($installed[$catalog] ?? null) ? $installed[$catalog] : [];
            $result['installed_count'] += count($localPackages);

            try {
                $remote = $this->repositories->fetch($catalog);
                $catalogResult = $this->compareCatalog($catalog, $localPackages, $remote);
            } catch (\Throwable $exception) {
                $result['errors'][$catalog] = $exception->getMessage();
                $catalogResult = $this->unavailableCatalog($catalog, $localPackages);
            }

            $result['update_count'] += (int) ($catalogResult['update_count'] ?? 0);
            $result['incompatible_count'] += (int) ($catalogResult['incompatible_count'] ?? 0);
            $result['catalogs'][$catalog] = $catalogResult;
        }

        try {
            $this->cache->write($result);
        } catch (\Throwable $exception) {
            $result['cache_error'] = $exception->getMessage();
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $installed
     * @param array<string, mixed> $remote
     * @return array<string, mixed>
     */
    public function compareCatalog(string $catalog, array $installed, array $remote): array
    {
        $remotePackages = is_array($remote['packages'] ?? null) ? $remote['packages'] : [];
        $records = [];
        $updates = 0;
        $incompatible = 0;

        foreach ($installed as $local) {
            if (!is_array($local)) {
                continue;
            }

            $record = $this->comparePackage($catalog, $local, $remotePackages);
            $records[] = $record;
            if (($record['status'] ?? '') === 'update_available') {
                $updates++;
            } elseif (($record['status'] ?? '') === 'incompatible_update') {
                $incompatible++;
            }
        }

        return [
            'catalog' => $catalog,
            'channel' => (string) ($remote['channel'] ?? 'stable'),
            'generated_at' => (string) ($remote['generated_at'] ?? ''),
            'available' => true,
            'installed_count' => count($installed),
            'update_count' => $updates,
            'incompatible_count' => $incompatible,
            'packages' => $records,
        ];
    }

    /**
     * @param array<string, mixed> $local
     * @param array<int, mixed> $remotePackages
     * @return array<string, mixed>
     */
    private function comparePackage(string $catalog, array $local, array $remotePackages): array
    {
        $candidates = [];
        foreach ($remotePackages as $remote) {
            if (!is_array($remote) || !$this->matchesIdentity($catalog, $local, $remote)) {
                continue;
            }
            if (!$this->isPublished($remote)) {
                continue;
            }
            $candidates[] = $remote;
        }

        usort($candidates, fn (array $left, array $right): int =>
            version_compare(
                $this->comparableVersion((string) ($right['version'] ?? '0.0.0')),
                $this->comparableVersion((string) ($left['version'] ?? '0.0.0'))
            )
        );

        $currentVersion = trim((string) ($local['version'] ?? '0.0.0'));
        $currentComparable = $this->comparableVersion($currentVersion);
        $latestPublished = $candidates[0] ?? null;
        $latestCompatible = null;
        foreach ($candidates as $candidate) {
            if ($this->compatibilityReasons($candidate) === []) {
                $latestCompatible = $candidate;
                break;
            }
        }

        $status = 'not_in_catalog';
        $selected = $latestCompatible ?? $latestPublished;
        if (is_array($latestCompatible)) {
            $latestVersion = (string) ($latestCompatible['version'] ?? '0.0.0');
            $status = version_compare($this->comparableVersion($latestVersion), $currentComparable, '>')
                ? 'update_available'
                : 'up_to_date';
        } elseif (is_array($latestPublished)) {
            $publishedVersion = (string) ($latestPublished['version'] ?? '0.0.0');
            $status = version_compare($this->comparableVersion($publishedVersion), $currentComparable, '>')
                ? 'incompatible_update'
                : 'up_to_date';
        }

        $reasons = is_array($latestPublished) ? $this->compatibilityReasons($latestPublished) : [];

        return [
            'catalog' => $catalog,
            'name' => (string) ($local['name'] ?? $local['slug'] ?? ''),
            'slug' => (string) ($local['slug'] ?? ''),
            'vendor' => (string) ($local['vendor'] ?? ''),
            'theme_type' => (string) ($local['theme_type'] ?? ''),
            'current_version' => $currentVersion,
            'latest_version' => is_array($latestPublished) ? (string) ($latestPublished['version'] ?? '') : '',
            'compatible_version' => is_array($latestCompatible) ? (string) ($latestCompatible['version'] ?? '') : '',
            'status' => $status,
            'compatibility_reasons' => $reasons,
            'download_url' => is_array($selected) ? (string) ($selected['download_url'] ?? '') : '',
            'changelog_url' => is_array($selected)
                ? $this->normalizeChangelogUrl((string) ($selected['changelog_url'] ?? ''))
                : '',
            'changelog' => is_array($selected) ? trim((string) ($selected['changelog'] ?? '')) : '',
            'published_at' => is_array($selected) ? (string) ($selected['published_at'] ?? '') : '',
            'sha256' => is_array($selected) ? (string) ($selected['sha256'] ?? '') : '',
            'signature' => is_array($selected) ? (string) ($selected['signature'] ?? '') : '',
            'size_bytes' => is_array($selected) ? max(0, (int) ($selected['size_bytes'] ?? 0)) : 0,
            'official' => is_array($selected) && !empty($selected['official']),
            'channel' => is_array($selected) ? (string) ($selected['channel'] ?? 'stable') : 'stable',
            'enabled' => !array_key_exists('enabled', $local) || !empty($local['enabled']),
        ];
    }

    /** @param array<string, mixed> $local @param array<string, mixed> $remote */
    private function matchesIdentity(string $catalog, array $local, array $remote): bool
    {
        if ((string) ($local['slug'] ?? '') !== (string) ($remote['slug'] ?? '')) {
            return false;
        }

        $localVendor = strtolower(trim((string) ($local['vendor'] ?? '')));
        $remoteVendor = strtolower(trim((string) ($remote['vendor'] ?? '')));
        if ($localVendor !== '' && $remoteVendor !== '' && $localVendor !== $remoteVendor) {
            return false;
        }

        $localChannel = strtolower(trim((string) ($local['channel'] ?? 'stable')));
        $remoteChannel = strtolower(trim((string) ($remote['channel'] ?? 'stable')));
        if ($localChannel !== '' && $remoteChannel !== '' && $localChannel !== $remoteChannel) {
            return false;
        }

        if ($catalog === 'themes') {
            $localType = strtolower(trim((string) ($local['theme_type'] ?? '')));
            $remoteType = strtolower(trim((string) ($remote['theme_type'] ?? '')));
            if ($localType === '' || $remoteType === '' || $localType !== $remoteType) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $package */
    private function isPublished(array $package): bool
    {
        if (strtolower(trim((string) ($package['availability'] ?? 'draft'))) !== 'published') {
            return false;
        }

        if (array_key_exists('download_ready', $package) && empty($package['download_ready'])) {
            return false;
        }

        return trim((string) ($package['version'] ?? '')) !== '';
    }

    /** @param array<string, mixed> $package @return array<int, string> */
    private function compatibilityReasons(array $package): array
    {
        $reasons = [];
        $requiresPhp = trim((string) ($package['requires_php'] ?? ''));
        if ($requiresPhp !== '' && !$this->meetsRequirement(PHP_VERSION, $requiresPhp)) {
            $reasons[] = 'php';
        }

        $coreVersion = $this->comparableVersion(flatcms_version());
        $minCore = trim((string) ($package['min_core_version'] ?? ''));
        if ($minCore !== '' && version_compare($coreVersion, $this->comparableVersion($minCore), '<')) {
            $reasons[] = 'core_min';
        }

        $maxCore = trim((string) ($package['max_core_version'] ?? ''));
        if ($maxCore !== '' && version_compare($coreVersion, $this->comparableVersion($maxCore), '>')) {
            $reasons[] = 'core_max';
        }

        try {
            if ($this->requirements->missing($package['requires_packages'] ?? []) !== []) {
                $reasons[] = 'packages';
            }
        } catch (\Throwable) {
            $reasons[] = 'packages';
        }

        return array_values(array_unique($reasons));
    }

    private function meetsRequirement(string $current, string $requirement): bool
    {
        if (preg_match('/^(>=|<=|>|<|==|=)?\s*([0-9][0-9A-Za-z._+-]*)$/', trim($requirement), $match) !== 1) {
            return false;
        }

        $operator = (string) ($match[1] ?? '');
        $operator = $operator === '' ? '>=' : ($operator === '=' ? '==' : $operator);
        return version_compare($this->comparableVersion($current), $this->comparableVersion((string) $match[2]), $operator);
    }

    private function comparableVersion(string $version): string
    {
        $version = trim($version);
        if (preg_match('/[0-9]+(?:\.[0-9A-Za-z_-]+)+/', $version, $match) === 1) {
            return (string) $match[0];
        }

        return $version !== '' ? $version : '0.0.0';
    }

    private function normalizeChangelogUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || !in_array($scheme, ['https', 'http'], true)) {
            return '';
        }

        if ($scheme !== 'https' && !is_local_host($host)) {
            return '';
        }

        return $url;
    }

    /**
     * @param array<int, array<string, mixed>> $installed
     * @return array<string, mixed>
     */
    private function unavailableCatalog(string $catalog, array $installed): array
    {
        $packages = [];
        foreach ($installed as $local) {
            if (!is_array($local)) {
                continue;
            }
            $packages[] = [
                'name' => (string) ($local['name'] ?? $local['slug'] ?? ''),
                'slug' => (string) ($local['slug'] ?? ''),
                'vendor' => (string) ($local['vendor'] ?? ''),
                'theme_type' => (string) ($local['theme_type'] ?? ''),
                'current_version' => (string) ($local['version'] ?? '0.0.0'),
                'latest_version' => '',
                'compatible_version' => '',
                'status' => 'repository_unavailable',
                'compatibility_reasons' => [],
                'enabled' => !array_key_exists('enabled', $local) || !empty($local['enabled']),
            ];
        }

        return [
            'catalog' => $catalog,
            'channel' => '',
            'generated_at' => '',
            'available' => false,
            'installed_count' => count($installed),
            'update_count' => 0,
            'incompatible_count' => 0,
            'packages' => $packages,
        ];
    }
}
