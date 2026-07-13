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

final class FhseCapabilityService
{
    private const DEFAULT_CAPABILITIES_PATH = '/etc/fhse/capabilities.json';
    private const DEFAULT_SENTINEL_PATH = BASE_PATH . '/.fhse-flatcms-instance.json';

    /**
     * @return array<string,mixed>
     */
    public function read(): array
    {
        $apiPayload = $this->readFromLocalApi();
        if ($apiPayload !== null) {
            return $apiPayload;
        }

        $capabilitiesPath = $this->normalizePath((string) env('FHSE_CAPABILITIES_PATH', self::DEFAULT_CAPABILITIES_PATH), self::DEFAULT_CAPABILITIES_PATH);
        $sentinelPath = $this->normalizePath((string) env('FHSE_SENTINEL_PATH', self::DEFAULT_SENTINEL_PATH), self::DEFAULT_SENTINEL_PATH);

        $capabilitiesFile = $this->readJsonFile($capabilitiesPath);
        $sentinelFile = $this->readJsonFile($sentinelPath);

        $capabilities = is_array($capabilitiesFile['data'] ?? null) ? $capabilitiesFile['data'] : [];
        $sentinel = is_array($sentinelFile['data'] ?? null) ? $sentinelFile['data'] : [];

        $sentinelValid = $this->isValidSentinel($sentinel);
        $fhseDetected = !empty($capabilities['fhse']) || $sentinelValid;
        $capabilitiesRestricted = (string) ($capabilitiesFile['status'] ?? '') === 'restricted';

        $flatcms = is_array($capabilities['flatcms'] ?? null) ? $capabilities['flatcms'] : [];
        $flatcmsDetected = array_key_exists('detected', $flatcms)
            ? (bool) $flatcms['detected']
            : $sentinelValid;

        $flatcmsStatus = trim((string) ($flatcms['status'] ?? ''));
        if ($flatcmsStatus === '') {
            $flatcmsStatus = $flatcmsDetected ? 'flatcms_detected' : 'flatcms_missing';
        }

        $webRoot = trim((string) ($flatcms['web_root'] ?? ($sentinel['web_root'] ?? BASE_PATH)));
        $publicRoot = trim((string) ($flatcms['public_root'] ?? ($sentinel['public_root'] ?? (BASE_PATH . '/public'))));

        $features = is_array($capabilities['features'] ?? null) ? $capabilities['features'] : [];
        $tunnel = is_array($features['cloudflare_tunnel'] ?? null) ? $features['cloudflare_tunnel'] : [];

        $supported = array_key_exists('supported', $tunnel)
            ? (bool) $tunnel['supported']
            : ($capabilitiesRestricted && $sentinelValid);
        $configuredKnown = array_key_exists('configured', $tunnel);
        $configured = $configuredKnown ? (bool) $tunnel['configured'] : null;
        $activeKnown = array_key_exists('active', $tunnel);
        $active = $activeKnown ? (bool) $tunnel['active'] : null;
        $allowed = array_key_exists('allowed', $tunnel)
            ? (bool) $tunnel['allowed']
            : ($supported && $flatcmsDetected);
        $mode = trim((string) ($tunnel['mode'] ?? ''));
        $version = trim((string) ($capabilities['version'] ?? ''));
        if ($version === '') {
            $version = trim((string) ($sentinel['fhse_version'] ?? ''));
        }

        return [
            'detected' => $fhseDetected,
            'version' => $version,
            'capabilities_file' => [
                'path' => $capabilitiesPath,
                'exists' => !empty($capabilitiesFile['exists']),
                'readable' => !empty($capabilitiesFile['readable']),
                'valid' => !empty($capabilitiesFile['valid']),
                'status' => (string) ($capabilitiesFile['status'] ?? 'missing'),
            ],
            'sentinel_file' => [
                'path' => $sentinelPath,
                'exists' => !empty($sentinelFile['exists']),
                'readable' => !empty($sentinelFile['readable']),
                'valid' => $sentinelValid,
                'status' => (string) ($sentinelFile['status'] ?? 'missing'),
            ],
            'flatcms' => [
                'detected' => $flatcmsDetected,
                'status' => $flatcmsStatus,
                'web_root' => $webRoot,
                'public_root' => $publicRoot,
            ],
            'cloudflare_tunnel' => [
                'supported' => $supported,
                'configured' => $configured,
                'active' => $active,
                'configured_known' => $configuredKnown,
                'active_known' => $activeKnown,
                'allowed' => $allowed,
                'mode' => $mode !== '' ? $mode : 'token',
                'public_hostname' => trim((string) ($tunnel['public_hostname'] ?? '')),
                'config_path' => trim((string) ($tunnel['config_path'] ?? '')),
                'service_name' => trim((string) ($tunnel['service_name'] ?? '')),
                'status_source' => !empty($capabilitiesFile['valid'])
                    ? 'capabilities'
                    : ($capabilitiesRestricted && $sentinelValid ? 'sentinel_fallback' : 'unknown'),
            ],
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function readFromLocalApi(): ?array
    {
        $response = (new FhseApiClient())->getCapabilities();
        if (empty($response['ok']) || !is_array($response['capabilities'] ?? null)) {
            return null;
        }

        $payload = $response['capabilities'];
        if (!array_key_exists('detected', $payload) || !is_array($payload['cloudflare_tunnel'] ?? null)) {
            return null;
        }

        return $payload;
    }

    private function normalizePath(string $candidate, string $fallback): string
    {
        $path = trim($candidate);

        return $path !== '' ? $path : $fallback;
    }

    /**
     * @return array{path:string,exists:bool,readable:bool,valid:bool,data:array<string,mixed>|null}
     */
    private function readJsonFile(string $path): array
    {
        $restricted = $this->isRestrictedByOpenBaseDir($path);
        $exists = !$restricted && @is_file($path);
        $readable = $exists && is_readable($path);

        if ($restricted) {
            return [
                'path' => $path,
                'exists' => false,
                'readable' => false,
                'valid' => false,
                'status' => 'restricted',
                'data' => null,
            ];
        }

        if (!$readable) {
            return [
                'path' => $path,
                'exists' => $exists,
                'readable' => $readable,
                'valid' => false,
                'status' => $exists ? 'unreadable' : 'missing',
                'data' => null,
            ];
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return [
                'path' => $path,
                'exists' => true,
                'readable' => true,
                'valid' => false,
                'status' => 'unreadable',
                'data' => null,
            ];
        }

        $decoded = json_decode($raw, true);

        return [
            'path' => $path,
            'exists' => true,
            'readable' => true,
            'valid' => is_array($decoded),
            'status' => is_array($decoded) ? 'ok' : 'invalid',
            'data' => is_array($decoded) ? $decoded : null,
        ];
    }

    private function isRestrictedByOpenBaseDir(string $path): bool
    {
        $openBaseDir = trim((string) ini_get('open_basedir'));
        if ($openBaseDir === '') {
            return false;
        }

        $normalizedPath = $this->normalizeFilesystemPath($path);
        if ($normalizedPath === '') {
            return false;
        }

        $allowedRoots = preg_split('/[:;]/', $openBaseDir) ?: [];
        foreach ($allowedRoots as $root) {
            $root = trim((string) $root);
            if ($root === '' || $root === '.') {
                return false;
            }

            $normalizedRoot = $this->normalizeFilesystemPath($root);
            if ($normalizedRoot === '') {
                continue;
            }

            $rootWithSeparator = rtrim($normalizedRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (
                $normalizedPath === rtrim($normalizedRoot, DIRECTORY_SEPARATOR)
                || str_starts_with($normalizedPath, $rootWithSeparator)
            ) {
                return false;
            }
        }

        return true;
    }

    private function normalizeFilesystemPath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '';
        }

        $resolved = @realpath($trimmed);
        if (is_string($resolved) && $resolved !== '') {
            return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $resolved);
        }

        $normalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $trimmed);
        if ($normalized[0] !== DIRECTORY_SEPARATOR) {
            $normalized = BASE_PATH . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR);
        }

        return preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR, '#') . '+#', DIRECTORY_SEPARATOR, $normalized) ?: '';
    }

    /**
     * @param array<string,mixed> $sentinel
     */
    private function isValidSentinel(array $sentinel): bool
    {
        return strtolower(trim((string) ($sentinel['product'] ?? ''))) === 'flatcms'
            && !empty($sentinel['fhse_managed'])
            && trim((string) ($sentinel['web_root'] ?? '')) !== '';
    }
}
