<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Services/Licensing/ComponentLicenseActivationService.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Services\Licensing;

use App\Core\ModuleManager;
use App\Core\ModuleStateRepository;
use App\Modules\Auth\Services\LicenseVaultService;
use RuntimeException;

final class ComponentLicenseActivationService
{
    private const OFFICIAL_KEY_PATTERN = '/^FCM(?:-[A-HJ-NP-Z2-9]{4}){5}$/';

    private ModuleManager $modules;
    private LicenseVaultService $vault;
    private string $statePath;
    private ModuleStateRepository $stateRepository;

    public function __construct(
        ?ModuleManager $modules = null,
        ?LicenseVaultService $vault = null,
        ?string $statePath = null,
        ?ModuleStateRepository $stateRepository = null
    ) {
        $this->statePath = $statePath ?? (BASE_PATH . '/data/modules.json');
        $this->stateRepository = $stateRepository ?? new ModuleStateRepository($this->statePath);
        $this->modules = $modules ?? new ModuleManager(null, $this->statePath);
        $this->vault = $vault ?? new LicenseVaultService();
    }

    /**
     * @return array<string, mixed>
     */
    public function activate(
        string $component,
        string $plainKey,
        string $host,
        string $ownerUserId = ''
    ): array {
        $meta = $this->modules->get($component);
        if (!$this->supportsComponentOwnedActivation($meta)) {
            throw new RuntimeException('component_license_activation_unavailable');
        }

        $plainKey = strtoupper(trim($plainKey));
        if (!$this->isOfficialKeyFormat($plainKey)) {
            throw new RuntimeException('component_license_key_invalid');
        }

        $host = normalize_host($host);
        if (!$this->isValidHost($host)) {
            throw new RuntimeException('component_license_domain_invalid');
        }

        foreach ($meta['dependencies'] ?? [] as $dependency) {
            if (!$this->modules->isEnabled((string) $dependency)) {
                throw new RuntimeException('component_license_dependency_missing');
            }
        }

        $previousSummary = $this->vault->getModuleLicense($component, $host);
        $previousKey = $this->vault->decryptModuleLicenseKey($component);

        try {
            $summary = $this->vault->storeModuleLicense(
                $component,
                $plainKey,
                $host,
                'active',
                '',
                $ownerUserId
            );

            if (!$this->vault->isModuleLicenseValid($component, $host, null, false)) {
                throw new RuntimeException('component_license_validation_failed');
            }

            $this->enableComponent($component);
            return $summary;
        } catch (\Throwable $exception) {
            $this->restorePreviousLicense($component, $previousKey, $previousSummary);
            throw $exception;
        }
    }

    public function isOfficialKeyFormat(string $plainKey): bool
    {
        return preg_match(self::OFFICIAL_KEY_PATTERN, strtoupper(trim($plainKey))) === 1;
    }

    /**
     * @param array<string, mixed>|null $meta
     */
    private function supportsComponentOwnedActivation(?array $meta): bool
    {
        if (!is_array($meta) || !($meta['integrity_valid'] ?? true)) {
            return false;
        }

        $license = is_array($meta['license'] ?? null) ? $meta['license'] : [];
        $activation = is_array($meta['license_activation'] ?? null) ? $meta['license_activation'] : [];

        return (bool) ($license['required'] ?? false)
            && trim((string) ($activation['target'] ?? '')) !== '';
    }

    private function isValidHost(string $host): bool
    {
        if ($host === '' || strlen($host) > 253 || preg_match('/[\s\/\\\\]/', $host) === 1) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return true;
        }

        return filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    private function enableComponent(string $component): void
    {
        $this->stateRepository->merge([$component => ['enabled' => true]]);
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function restorePreviousLicense(string $component, string $plainKey, array $summary): void
    {
        if ($plainKey === '') {
            $this->vault->clearModuleLicense($component);
            return;
        }

        $this->vault->storeModuleLicense(
            $component,
            $plainKey,
            (string) ($summary['domain'] ?? ''),
            (string) ($summary['status'] ?? 'inactive'),
            (string) ($summary['updated_at'] ?? ''),
            (string) ($summary['owner_user_id'] ?? '')
        );
    }
}
