<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Footer\Controllers;

use App\Core\BaseController;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Modules\Footer\Services\FooterTranslationService;

class AdminController extends BaseController
{
    private FooterTranslationService $translations;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Footer');
        $this->translations = new FooterTranslationService();
    }

    public function index(): void
    {
        if (!$this->authorize('footers.view')) {
            return;
        }

        $settings = FlatFile::settings();
        $footer = footer_settings(null, $settings);
        $activeLocale = $this->resolveRequestedLocale((string) $this->request->input('locale', ''), $settings);

        $this->render('Footer/Views/admin/index', [
            'pageTitle' => __('footer_title', 'Footer'),
            'footer' => $footer,
            'translationUi' => $this->buildTranslationUi($footer, $settings, $activeLocale),
        ], 'admin.main');
    }

    public function update(): void
    {
        if (!$this->authorize('footers.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $settings = FlatFile::settings();
        $existingFooter = footer_settings(null, $settings);
        $activeLocale = $this->resolveRequestedLocale((string) $this->request->input('locale', ''), $settings);
        $sourceLocale = $this->translations->normalizeLocale((string) $this->request->input('source_locale', ''));
        if ($sourceLocale === '') {
            $sourceLocale = (string) ($existingFooter['source_locale'] ?? $this->translations->defaultLocale($settings));
        }

        $enabled = (string) $this->request->input('enabled', '0') === '1';
        $poweredByEnabled = (string) $this->request->input('powered_by_enabled', '0') === '1';
        $translationsInput = $this->request->input('translations', []);
        if (!is_array($translationsInput)) {
            $translationsInput = [];
        }

        $translations = [];
        foreach ($this->translations->supportedLocales() as $locale) {
            $entry = $translationsInput[$locale] ?? [];
            if (!is_array($entry)) {
                $entry = [];
            }

            $translations[$locale] = [
                'brand_text' => trim((string) ($entry['brand_text'] ?? '')),
                'copyright_text' => trim((string) ($entry['copyright_text'] ?? '')),
                'powered_by_label' => trim((string) ($entry['powered_by_label'] ?? '')),
            ];
        }

        $payload = [
            'enabled' => $enabled,
            'source_locale' => $sourceLocale,
            'brand_text' => trim((string) ($existingFooter['brand_text'] ?? '')),
            'copyright_text' => trim((string) ($existingFooter['copyright_text'] ?? '')),
            'translations' => $translations,
            'powered_by' => [
                'enabled' => $poweredByEnabled,
                'label' => trim((string) ($existingFooter['powered_by']['label'] ?? '')),
                'url' => trim((string) $this->request->input('powered_by_url', '')),
            ],
        ];

        $footer = footer_settings($payload, $settings);

        hook_run('footer.before_save', $footer);
        FlatFile::saveSettings($footer, 'footer');
        hook_run('footer.after_save', $footer);

        $this->session->flash('success', __('footer_saved', 'Footer'));
        $this->redirect(url('/admin/footer?locale=' . rawurlencode($activeLocale)));
    }

    /**
     * @param array<string, mixed> $footer
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function buildTranslationUi(array $footer, array $settings, string $activeLocale): array
    {
        $sourceLocale = (string) ($footer['source_locale'] ?? $this->translations->defaultLocale($settings));
        $tabs = [];
        foreach ($this->translations->supportedLocales() as $locale) {
            $values = $this->translations->localeValues($footer, $locale);
            $tabs[] = [
                'code' => $locale,
                'label' => $this->translations->localeLabel($locale, I18n::getLocale()),
                'exists' => $locale === $sourceLocale || $this->translations->hasMeaningfulTranslation($values),
                'is_active' => $locale === $activeLocale,
                'is_source' => $locale === $sourceLocale,
                'values' => $values,
                'form_labels' => $this->buildFormLabels($locale),
            ];
        }

        return [
            'tabs' => $tabs,
            'active_locale' => $activeLocale,
            'source_locale' => $sourceLocale,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildFormLabels(string $locale): array
    {
        $footerTranslations = $this->loadTranslationsForLocale('Footer', $locale);
        $coreTranslations = $this->loadTranslationsForLocale('Core', $locale);

        $map = [
            'footer_title',
            'footer_subtitle',
            'footer_enabled',
            'footer_brand_text',
            'footer_brand_text_hint',
            'footer_copyright_text',
            'footer_copyright_hint',
            'footer_tokens_hint',
            'footer_powered_by',
            'footer_powered_by_enabled',
            'footer_powered_by_label',
            'footer_powered_by_url',
            'translations',
            'translation_source',
            'translation_missing',
            'translation_ready',
            'save',
        ];

        $labels = [];
        foreach ($map as $key) {
            if (isset($footerTranslations[$key]) && is_string($footerTranslations[$key]) && trim($footerTranslations[$key]) !== '') {
                $labels[$key] = trim((string) $footerTranslations[$key]);
                continue;
            }
            if (isset($coreTranslations[$key]) && is_string($coreTranslations[$key]) && trim($coreTranslations[$key]) !== '') {
                $labels[$key] = trim((string) $coreTranslations[$key]);
            }
        }

        return $labels;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadTranslationsForLocale(string $namespace, string $locale): array
    {
        $resolvedLocale = $this->translations->normalizeLocale($locale);
        if ($resolvedLocale === '') {
            $resolvedLocale = I18n::getLocale();
        }

        $languageDir = BASE_PATH . '/app/Modules/' . $namespace . '/Languages';
        if ($namespace === 'Core') {
            $languageDir = BASE_PATH . '/app/Modules/Core/Languages';
        }

        $path = $languageDir . '/' . $resolvedLocale . '.json';
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function resolveRequestedLocale(string $requestedLocale, array $settings): string
    {
        $locale = $this->translations->normalizeLocale($requestedLocale);
        if ($locale !== '') {
            return $locale;
        }

        $queryLocale = $this->translations->normalizeLocale((string) $this->request->input('locale', ''));
        if ($queryLocale !== '') {
            return $queryLocale;
        }

        $uiLocale = $this->translations->normalizeLocale(I18n::getLocale());
        if ($uiLocale !== '') {
            return $uiLocale;
        }

        return $this->translations->defaultLocale($settings);
    }

}
