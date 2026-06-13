<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Footer\Services;

use App\Core\I18n;

final class FooterTranslationService
{
    /** @var array<int, string> */
    private const TRANSLATABLE_FIELDS = [
        'brand_text',
        'copyright_text',
        'powered_by_label',
    ];

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

    public function defaultLocale(array $settings): string
    {
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

    /**
     * @param array<string, mixed> $footer
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function normalizeSettings(array $footer, array $settings): array
    {
        $defaults = footer_default_config($settings);
        $powered = is_array($footer['powered_by'] ?? null) ? $footer['powered_by'] : [];
        $defaultPowered = is_array($defaults['powered_by'] ?? null) ? $defaults['powered_by'] : [];

        $enabledRaw = $footer['enabled'] ?? ($defaults['enabled'] ?? true);
        $enabled = filter_var($enabledRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($enabled === null) {
            $enabled = (bool) ($defaults['enabled'] ?? true);
        }

        $poweredEnabledRaw = $powered['enabled'] ?? ($defaultPowered['enabled'] ?? true);
        $poweredEnabled = filter_var($poweredEnabledRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($poweredEnabled === null) {
            $poweredEnabled = (bool) ($defaultPowered['enabled'] ?? true);
        }

        $poweredUrl = trim((string) ($powered['url'] ?? ($defaultPowered['url'] ?? 'https://flat-cms.fr')));
        if ($poweredUrl === '') {
            $poweredUrl = trim((string) ($defaultPowered['url'] ?? 'https://flat-cms.fr'));
        }

        $sourceLocale = $this->normalizeLocale((string) ($footer['source_locale'] ?? ''));
        if ($sourceLocale === '') {
            $sourceLocale = $this->defaultLocale($settings);
        }

        $sourceDefaults = [
            'brand_text' => trim((string) ($footer['brand_text'] ?? ($defaults['brand_text'] ?? ''))),
            'copyright_text' => trim((string) ($footer['copyright_text'] ?? ($defaults['copyright_text'] ?? ''))),
            'powered_by_label' => trim((string) ($powered['label'] ?? ($defaultPowered['label'] ?? ''))),
        ];

        $rawTranslations = $footer['translations'] ?? [];
        if (!is_array($rawTranslations)) {
            $rawTranslations = [];
        }

        $translations = [];
        foreach ($this->supportedLocales() as $locale) {
            $entry = $rawTranslations[$locale] ?? [];
            if (!is_array($entry)) {
                $entry = [];
            }

            $normalized = $this->normalizeTranslation($entry, $locale === $sourceLocale ? $sourceDefaults : null);
            if (!$this->isEmptyTranslation($normalized) || $locale === $sourceLocale) {
                $translations[$locale] = $normalized;
            }
        }

        if (!isset($translations[$sourceLocale])) {
            $translations[$sourceLocale] = $this->normalizeTranslation([], $sourceDefaults);
        }

        return [
            'enabled' => $enabled,
            'source_locale' => $sourceLocale,
            'translations' => $translations,
            'brand_text' => (string) ($translations[$sourceLocale]['brand_text'] ?? $sourceDefaults['brand_text']),
            'copyright_text' => (string) ($translations[$sourceLocale]['copyright_text'] ?? $sourceDefaults['copyright_text']),
            'powered_by' => [
                'enabled' => $poweredEnabled,
                'label' => (string) ($translations[$sourceLocale]['powered_by_label'] ?? $sourceDefaults['powered_by_label']),
                'url' => $poweredUrl,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $footer
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function resolveForLocale(array $footer, array $settings, string $locale): array
    {
        $normalized = $this->normalizeSettings($footer, $settings);
        $sourceLocale = (string) ($normalized['source_locale'] ?? $this->defaultLocale($settings));
        $targetLocale = $this->normalizeLocale($locale);
        if ($targetLocale === '') {
            $targetLocale = $sourceLocale;
        }

        $translations = is_array($normalized['translations'] ?? null) ? $normalized['translations'] : [];
        $sourceTranslation = is_array($translations[$sourceLocale] ?? null) ? $translations[$sourceLocale] : [];
        $targetTranslation = is_array($translations[$targetLocale] ?? null) ? $translations[$targetLocale] : [];

        foreach (self::TRANSLATABLE_FIELDS as $field) {
            $value = trim((string) ($targetTranslation[$field] ?? ''));
            if ($value === '') {
                $value = trim((string) ($sourceTranslation[$field] ?? ''));
            }

            if ($field === 'powered_by_label') {
                $normalized['powered_by']['label'] = $value;
                continue;
            }

            $normalized[$field] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $footer
     * @return array<string, string>
     */
    public function localeValues(array $footer, string $locale): array
    {
        $normalized = $this->normalizeLocale($locale);
        $translations = is_array($footer['translations'] ?? null) ? $footer['translations'] : [];
        $values = $translations[$normalized] ?? [];
        if (!is_array($values)) {
            $values = [];
        }

        return $this->normalizeTranslation($values, null);
    }

    /**
     * @param array<string, string> $translation
     */
    public function hasMeaningfulTranslation(array $translation): bool
    {
        return !$this->isEmptyTranslation($translation);
    }

    /**
     * @param array<string, mixed> $translation
     * @param array<string, string>|null $fallback
     * @return array<string, string>
     */
    private function normalizeTranslation(array $translation, ?array $fallback): array
    {
        $normalized = [];
        foreach (self::TRANSLATABLE_FIELDS as $field) {
            $value = trim((string) ($translation[$field] ?? ''));
            if ($value === '' && is_array($fallback)) {
                $value = trim((string) ($fallback[$field] ?? ''));
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
}
