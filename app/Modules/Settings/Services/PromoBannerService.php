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

use App\Core\FlatFile;
use App\Core\I18n;

final class PromoBannerService
{
    private const SETTINGS_KEY = 'promo_banner_translations';
    private const HEX_COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';
    private const CTA_VARIANTS = ['primary', 'secondary', 'outline', 'ghost'];
    private const ALIGNMENTS = ['left', 'center', 'right'];
    private const TRANSLATABLE_FIELDS = ['text', 'cta_label', 'cta_url'];

    public function defaults(): array
    {
        return [
            'enabled' => false,
            'text' => '',
            'cta_label' => '',
            'cta_url' => '',
            'cta_variant' => 'primary',
            'alignment' => 'left',
            'background_color' => '#111827',
            'text_color' => '#FFFFFF',
        ];
    }

    public function normalizeSettings(?array $settings = null): array
    {
        $settings = is_array($settings) ? $settings : FlatFile::settings();
        $defaults = $this->defaults();

        $enabledRaw = $settings['promo_banner_enabled'] ?? $defaults['enabled'];
        $enabled = filter_var($enabledRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($enabled === null) {
            $enabled = (bool) $defaults['enabled'];
        }

        return [
            'enabled' => $enabled,
            'text' => $this->normalizeText((string) ($settings['promo_banner_text'] ?? '')),
            'cta_label' => $this->normalizeText((string) ($settings['promo_banner_cta_label'] ?? '')),
            'cta_url' => $this->normalizeUrl((string) ($settings['promo_banner_cta_url'] ?? '')),
            'cta_variant' => $this->normalizeVariant((string) ($settings['promo_banner_cta_variant'] ?? '')),
            'alignment' => $this->normalizeAlignment((string) ($settings['promo_banner_alignment'] ?? '')),
            'background_color' => $this->normalizeColor((string) ($settings['promo_banner_background_color'] ?? ''), (string) $defaults['background_color']),
            'text_color' => $this->normalizeColor((string) ($settings['promo_banner_text_color'] ?? ''), (string) $defaults['text_color']),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function supportedLocales(): array
    {
        return I18n::getSupportedLocales();
    }

    public function normalizeLocale(string $locale): string
    {
        $value = trim($locale);
        if ($value === '') {
            return '';
        }

        $supported = $this->supportedLocales();
        foreach ($supported as $candidate) {
            if (strcasecmp($candidate, $value) === 0) {
                return $candidate;
            }
        }

        $prefix = strtolower(substr($value, 0, 2));
        if ($prefix === '') {
            return '';
        }

        foreach ($supported as $candidate) {
            if (strtolower(substr($candidate, 0, 2)) === $prefix) {
                return $candidate;
            }
        }

        return '';
    }

    public function defaultLocale(?array $settings = null): string
    {
        $settings = $settings ?? FlatFile::settings();
        $locale = $this->normalizeLocale((string) ($settings['default_language'] ?? ''));
        if ($locale !== '') {
            return $locale;
        }

        $supported = $this->supportedLocales();
        return $supported[0] ?? 'fr-FR';
    }

    public function localeLabel(string $locale, ?string $uiLocale = null): string
    {
        $normalized = $this->normalizeLocale($locale);
        if ($normalized === '') {
            return trim($locale);
        }

        $label = I18n::getLocalizedLanguageName($normalized, $uiLocale ?? I18n::getLocale());
        return $label !== '' ? $label : $normalized;
    }

    public function applyToSettings(array $settings): array
    {
        $normalized = $this->normalizeSettings($settings);

        $settings['promo_banner_enabled'] = $normalized['enabled'] ? 1 : 0;
        $settings['promo_banner_text'] = $normalized['text'];
        $settings['promo_banner_cta_label'] = $normalized['cta_label'];
        $settings['promo_banner_cta_url'] = $normalized['cta_url'];
        $settings['promo_banner_cta_variant'] = $normalized['cta_variant'];
        $settings['promo_banner_alignment'] = $normalized['alignment'];
        $settings['promo_banner_background_color'] = $normalized['background_color'];
        $settings['promo_banner_text_color'] = $normalized['text_color'];

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    public function readTranslations(?array $settings = null): array
    {
        $state = FlatFile::settings(self::SETTINGS_KEY);
        return $this->normalizeTranslationState(is_array($state) ? $state : [], $settings);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function saveTranslations(array $state): bool
    {
        return FlatFile::saveSettings($this->normalizeTranslationState($state), self::SETTINGS_KEY);
    }

    /**
     * @param array<string, mixed> $submitted
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function prepareTranslationPayload(array $submitted, array $settings, string $sourceLocale): array
    {
        $existing = $this->readTranslations($settings);
        $normalizedSourceLocale = $this->normalizeLocale($sourceLocale);
        if ($normalizedSourceLocale === '') {
            $normalizedSourceLocale = (string) ($existing['source_locale'] ?? $this->defaultLocale($settings));
        }

        $translations = [];
        foreach ($this->supportedLocales() as $locale) {
            $input = $submitted[$locale] ?? [];
            if (!is_array($input)) {
                $input = [];
            }

            $fallback = $existing['translations'][$locale] ?? [];
            if (!is_array($fallback)) {
                $fallback = [];
            }

            $entry = [];
            foreach (self::TRANSLATABLE_FIELDS as $field) {
                $raw = array_key_exists($field, $input) ? $input[$field] : ($fallback[$field] ?? '');
                $entry[$field] = trim((string) $raw);
            }

            if ($locale === $normalizedSourceLocale) {
                $entry = $this->normalizeTranslation($entry, $settings);
            }

            if (!$this->isEmptyTranslation($entry) || $locale === $normalizedSourceLocale) {
                $translations[$locale] = $entry;
            }
        }

        if (!isset($translations[$normalizedSourceLocale])) {
            $translations[$normalizedSourceLocale] = $this->normalizeTranslation([], $settings);
        } else {
            $translations[$normalizedSourceLocale] = $this->normalizeTranslation($translations[$normalizedSourceLocale], $settings);
        }

        return [
            'source_locale' => $normalizedSourceLocale,
            'updated_at' => date('Y-m-d H:i:s'),
            'translations' => $translations,
        ];
    }

    /**
     * @param array<string, mixed>|null $settings
     * @return array<string, mixed>
     */
    public function resolveForLocale(?array $settings = null, string $locale = '', ?array $state = null): array
    {
        $settings = is_array($settings) ? $settings : FlatFile::settings();
        $banner = $this->normalizeSettings($settings);
        $state = is_array($state) ? $this->normalizeTranslationState($state, $settings) : $this->readTranslations($settings);
        $translations = is_array($state['translations'] ?? null) ? $state['translations'] : [];
        $sourceLocale = (string) ($state['source_locale'] ?? $this->defaultLocale($settings));
        $targetLocale = $this->normalizeLocale($locale);
        if ($targetLocale === '') {
            $targetLocale = $sourceLocale;
        }

        $sourceValues = $this->normalizeTranslation((array) ($translations[$sourceLocale] ?? []), $settings);
        $targetValues = $this->normalizeTranslation((array) ($translations[$targetLocale] ?? []), null);

        foreach (self::TRANSLATABLE_FIELDS as $field) {
            $resolved = trim((string) ($targetValues[$field] ?? ''));
            if ($resolved === '') {
                $resolved = trim((string) ($sourceValues[$field] ?? ''));
            }
            if ($resolved === '') {
                $resolved = trim((string) ($banner[$field] ?? ''));
            }
            $banner[$field] = $resolved;
        }

        $banner['source_locale'] = $sourceLocale;
        $banner['resolved_locale'] = $targetLocale;

        return $banner;
    }

    public function hasRenderableContent(?array $settings = null): bool
    {
        return $this->hasVisibleContent($this->resolveForLocale($settings, locale()));
    }

    public function assetForSettings(?array $settings = null): string
    {
        $banner = $this->normalizeSettings($settings);
        if (!$banner['enabled'] || !$this->hasVisibleContent($banner)) {
            return '';
        }

        $css = $this->buildRuntimeCss($banner);
        if ($css === '') {
            return '';
        }

        return runtime_css_asset($css, 'promo-banner', 'frontend');
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function normalizeTranslationState(array $state, ?array $settings = null): array
    {
        $settings = $settings ?? FlatFile::settings();
        $sourceLocale = $this->normalizeLocale((string) ($state['source_locale'] ?? ''));
        if ($sourceLocale === '') {
            $sourceLocale = $this->defaultLocale($settings);
        }

        $rawTranslations = $state['translations'] ?? [];
        if (!is_array($rawTranslations)) {
            $rawTranslations = [];
        }

        $translations = [];
        foreach ($this->supportedLocales() as $locale) {
            $entry = $rawTranslations[$locale] ?? [];
            if (!is_array($entry)) {
                $entry = [];
            }

            $normalized = $locale === $sourceLocale
                ? $this->normalizeTranslation($entry, $settings)
                : $this->normalizeTranslation($entry, null);

            if (!$this->isEmptyTranslation($normalized) || $locale === $sourceLocale) {
                $translations[$locale] = $normalized;
            }
        }

        if (!isset($translations[$sourceLocale])) {
            $translations[$sourceLocale] = $this->normalizeTranslation([], $settings);
        }

        return [
            'source_locale' => $sourceLocale,
            'updated_at' => trim((string) ($state['updated_at'] ?? '')),
            'translations' => $translations,
        ];
    }

    private function buildRuntimeCss(array $banner): string
    {
        $backgroundColor = (string) ($banner['background_color'] ?? '');
        $textColor = (string) ($banner['text_color'] ?? '');
        if ($backgroundColor === '' || $textColor === '') {
            return '';
        }

        return implode("\n", [
            '.site-promo-banner {',
            '  --promo-banner-bg: ' . $backgroundColor . ';',
            '  --promo-banner-text: ' . $textColor . ';',
            '}',
        ]);
    }

    private function hasVisibleContent(array $banner): bool
    {
        $text = trim((string) ($banner['text'] ?? ''));
        $ctaLabel = trim((string) ($banner['cta_label'] ?? ''));
        $ctaUrl = trim((string) ($banner['cta_url'] ?? ''));

        return $text !== '' || ($ctaLabel !== '' && $ctaUrl !== '');
    }

    /**
     * @param array<string, mixed>|null $fallbackSettings
     * @return array<string, string>
     */
    private function normalizeTranslation(array $translation, ?array $fallbackSettings = null): array
    {
        $normalized = [];
        foreach (self::TRANSLATABLE_FIELDS as $field) {
            $value = trim((string) ($translation[$field] ?? ''));
            if ($value === '' && is_array($fallbackSettings)) {
                $fallbackKey = 'promo_banner_' . $field;
                $value = trim((string) ($fallbackSettings[$fallbackKey] ?? ''));
            }
            $normalized[$field] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, string> $translation
     */
    private function isEmptyTranslation(array $translation): bool
    {
        foreach (self::TRANSLATABLE_FIELDS as $field) {
            if (trim((string) ($translation[$field] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeText(string $value): string
    {
        $value = strip_tags($value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;
        return trim($value);
    }

    private function normalizeVariant(string $value): string
    {
        $value = strtolower(trim($value));
        if (!in_array($value, self::CTA_VARIANTS, true)) {
            return 'primary';
        }

        return $value;
    }

    private function normalizeAlignment(string $value): string
    {
        $value = strtolower(trim($value));
        if (!in_array($value, self::ALIGNMENTS, true)) {
            return 'left';
        }

        return $value;
    }

    private function normalizeColor(string $value, string $fallback): string
    {
        $value = strtoupper(trim($value));
        if (!preg_match(self::HEX_COLOR_PATTERN, $value)) {
            return strtoupper($fallback);
        }

        return $value;
    }

    private function normalizeUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('~^\s*javascript:~i', $value) === 1) {
            return '';
        }

        if (preg_match('~^(mailto:|tel:|#|/)~i', $value) === 1) {
            return $value;
        }

        if (preg_match('~^https?://~i', $value) === 1) {
            return filter_var($value, FILTER_VALIDATE_URL) !== false ? $value : '';
        }

        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return $value;
        }

        return '/' . ltrim($value, '/');
    }
}
