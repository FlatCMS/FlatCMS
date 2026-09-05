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
use RuntimeException;

final class ComponentLicenseActivationService
{
    private const OFFICIAL_KEY_PATTERN = '/^FCM(?:-[A-HJ-NP-Z2-9]{4}){5}$/';

    private ModuleManager $modules;
    private LicenseVaultService $vault;
    private string $statePath;

    public function __construct(
        ?ModuleManager $modules = null,
        ?LicenseVaultService $vault = null,
        ?string $statePath = null
    ) {
        $this->statePath = $statePath ?? (BASE_PATH . '/data/modules.json');
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
        $state = $this->readState();
        $entry = is_array($state[$component] ?? null) ? $state[$component] : [];
        $entry['enabled'] = true;
        $state[$component] = $entry;
        $this->writeState($state);
    }

    /**
     * @return array<string, mixed>
     */
    private function readState(): array
    {
        if (!is_file($this->statePath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($this->statePath), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $state
     */
    private function writeState(array $state): void
    {
        $directory = dirname($this->statePath);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('component_license_state_write_failed');
        }

        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new RuntimeException('component_license_state_write_failed');
        }

        $temporary = tempnam($directory, '.modules-');
        if (!is_string($temporary)) {
            throw new RuntimeException('component_license_state_write_failed');
        }

        try {
            if (file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false || !rename($temporary, $this->statePath)) {
                throw new RuntimeException('component_license_state_write_failed');
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
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
