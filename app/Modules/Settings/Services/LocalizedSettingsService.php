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

final class LocalizedSettingsService
{
    private SiteBrandingTranslationService $branding;
    private SeoTranslationService $seo;

    public function __construct(
        ?SiteBrandingTranslationService $branding = null,
        ?SeoTranslationService $seo = null
    ) {
        $this->branding = $branding ?? new SiteBrandingTranslationService();
        $this->seo = $seo ?? new SeoTranslationService($this->branding);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function resolveForLocale(array $settings, string $locale): array
    {
        $settings = $this->branding->resolveForLocale($settings, $locale);
        return $this->seo->resolveForLocale($settings, $locale);
    }
}
