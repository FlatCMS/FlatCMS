<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\Licensing;

use App\Core\ModuleManager;
use App\Modules\Auth\Services\LicenseVaultService;

final class ExtensionLicenseService
{
    private ModuleManager $modules;
    private LicenseVaultService $vault;

    public function __construct(?ModuleManager $modules = null, ?LicenseVaultService $vault = null)
    {
        $this->modules = $modules ?? ModuleManager::instance();
        $this->vault = $vault ?? new LicenseVaultService();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(bool $enabledOnly = false, ?string $host = null): array
    {
        $profiles = [];
        $source = $enabledOnly ? $this->modules->enabled() : $this->modules->all();

        foreach ($source as $module => $meta) {
            if (!$this->isLicensableExtension($meta)) {
                continue;
            }

            $profile = $this->describe($module, $host);
            if (!is_array($profile)) {
                continue;
            }

            $profiles[$module] = $profile;
        }

        return $profiles;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function describe(string $module, ?string $host = null): ?array
    {
        $meta = $this->modules->get($module);
        if (!is_array($meta)) {
            return null;
        }

        $contract = is_array($meta['license'] ?? null) ? $meta['license'] : [
            'required' => false,
            'gate' => 'authoring',
            'subject' => (string) ($meta['key'] ?? $module),
            'revealable' => false,
        ];
        $requiresLicense = (bool) ($contract['required'] ?? false);
        $normalizedHost = normalize_host($host ?? ($_SERVER['HTTP_HOST'] ?? ''));
        $summary = $this->vault->getModuleLicense($module, $normalizedHost);
        $hasLicense = trim((string) ($summary['license_id'] ?? '')) !== ''
            || trim((string) ($summary['masked_key'] ?? '')) !== '';

        $localBypassEnabled = $this->localLicenseBypassEnabled();
        $localBypass = $requiresLicense && is_local_host($normalizedHost) && $localBypassEnabled;
        $isValid = !$requiresLicense || $localBypass || $this->vault->isModuleLicenseValid(
            $module,
            $normalizedHost,
            null,
            $localBypassEnabled
        );
        $status = $this->resolveStatus($requiresLicense, $localBypass, $isValid, $hasLicense, $summary);

        return [
            'module' => $module,
            'module_name' => (string) ($meta['name'] ?? $module),
            'description' => trim((string) ($meta['description'] ?? '')),
            'icon' => $this->resolveIcon($meta),
            'key' => (string) ($meta['key'] ?? $module),
            'type' => (string) ($meta['type'] ?? 'module'),
            'tier' => (string) ($meta['tier'] ?? 'standard'),
            'location' => (string) ($meta['location'] ?? 'module'),
            'official' => (bool) ($meta['official'] ?? false),
            'enabled' => $this->modules->isEnabled($module),
            'license' => $contract,
            'license_summary' => $summary,
            'requires_license' => $requiresLicense,
            'license_valid' => $isValid,
            'status' => $status,
            'authoring_enabled' => !$requiresLicense || $isValid,
            'published_runtime_enabled' => true,
        ];
    }

    public function canAuthor(string $module, ?string $host = null): bool
    {
        $profile = $this->describe($module, $host);
        if (!is_array($profile)) {
            return true;
        }

        return (bool) ($profile['authoring_enabled'] ?? true);
    }

    private function isLicensableExtension(array $meta): bool
    {
        if ((string) ($meta['location'] ?? '') !== 'extension') {
            return false;
        }

        $contract = is_array($meta['license'] ?? null) ? $meta['license'] : [];
        return (bool) ($contract['required'] ?? false) || (string) ($meta['tier'] ?? '') === 'premium';
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function resolveIcon(array $meta): string
    {
        $custom = trim((string) ($meta['icon'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return (string) ($meta['type'] ?? '') === 'builder' ? 'fas fa-cubes' : 'fas fa-gem';
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function resolveStatus(
        bool $requiresLicense,
        bool $localBypass,
        bool $isValid,
        bool $hasLicense,
        array $summary
    ): string {
        if (!$requiresLicense) {
            return 'not_required';
        }

        if ($localBypass) {
            return 'local_bypass';
        }

        if ($isValid) {
            return 'active';
        }

        if (!$hasLicense) {
            return 'missing';
        }

        if (trim((string) ($summary['status'] ?? '')) !== 'active') {
            return 'inactive';
        }

        return 'invalid_domain';
    }

    private function localLicenseBypassEnabled(): bool
    {
        return filter_var(env('EXTENSIONS_LOCAL_LICENSE_BYPASS', false), FILTER_VALIDATE_BOOL);
    }
}
