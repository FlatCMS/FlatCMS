<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Services\Licensing\ExtensionLicenseService;

final class LicenseCatalogService
{
    /**
     * @return array<string, array{
     *     module_name: string,
     *     title: string,
     *     description: string,
     *     icon: string
     * }>
     */
    public function all(): array
    {
        $catalog = [];
        $profiles = (new ExtensionLicenseService())->all(false);

        foreach ($profiles as $module => $profile) {
            $catalog[$module] = [
                'module_name' => $module,
                'title' => trim((string) ($profile['module_name'] ?? $module)),
                'description' => $this->resolveLocalizedDescription(
                    $module,
                    trim((string) ($profile['description'] ?? ''))
                ),
                'icon' => trim((string) ($profile['icon'] ?? 'fas fa-gem')),
            ];
        }

        return $catalog;
    }

    public function has(string $module): bool
    {
        return array_key_exists($module, $this->all());
    }

    /**
     * @return array<string, string>|null
     */
    public function get(string $module): ?array
    {
        return $this->all()[$module] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function codes(): array
    {
        return array_keys($this->all());
    }

    private function resolveLocalizedDescription(string $module, string $fallback): string
    {
        $key = 'license_catalog_description';
        $description = __($key, $module);

        return $description === $key ? $fallback : trim((string) $description);
    }
}
