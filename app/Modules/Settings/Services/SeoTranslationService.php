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

final class SeoTranslationService
{
    private const SETTINGS_KEY = 'seo_translations';

    /** @var array<int, string> */
    private const FIELDS = [
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    private SiteBrandingTranslationService $locales;

    public function __construct(?SiteBrandingTranslationService $locales = null)
    {
        $this->locales = $locales ?? new SiteBrandingTranslationService();
    }

    /**
     * @return array<int, string>
     */
    public function supportedLocales(): array
    {
        return $this->locales->supportedLocales();
    }

    public function normalizeLocale(string $locale): string
    {
        return $this->locales->normalizeLocale($locale);
    }

    public function localeLabel(string $locale, ?string $uiLocale = null): string
    {
        return $this->locales->localeLabel($locale, $uiLocale);
    }

    public function defaultLocale(?array $settings = null): string
    {
        return $this->locales->defaultLocale($settings);
    }

    /**
     * @return array<string, mixed>
     */
    public function read(?array $settings = null): array
    {
        $state = FlatFile::settings(self::SETTINGS_KEY);
        return $this->normalizeState(is_array($state) ? $state : [], $settings);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function save(array $state): bool
    {
        return FlatFile::saveSettings($this->normalizeState($state), self::SETTINGS_KEY);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function resolveForLocale(array $settings, string $locale): array
    {
        $state = $this->read($settings);
        $translations = is_array($state['translations'] ?? null) ? $state['translations'] : [];
        $sourceLocale = (string) ($state['source_locale'] ?? $this->defaultLocale($settings));
        $targetLocale = $this->normalizeLocale($locale);
        if ($targetLocale === '') {
            $targetLocale = $sourceLocale;
        }

        $sourceValues = $this->normalizeTranslation((array) ($translations[$sourceLocale] ?? []), $settings);
        $targetValues = $this->normalizeTranslation((array) ($translations[$targetLocale] ?? []));

        foreach (self::FIELDS as $field) {
            $resolved = trim((string) ($targetValues[$field] ?? ''));
            if ($resolved === '') {
                $resolved = trim((string) ($sourceValues[$field] ?? ''));
            }
            if ($resolved === '') {
                $resolved = trim((string) ($settings[$field] ?? ''));
            }
            $settings[$field] = $resolved;
        }

        return $settings;
    }

    /**
     * @param array<string, mixed> $submitted
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function prepareSavePayload(array $submitted, array $settings, string $sourceLocale): array
    {
        $existing = $this->read($settings);
        $normalizedSourceLocale = $this->normalizeLocale($sourceLocale);
        if ($normalizedSourceLocale === '') {
            $normalizedSourceLocale = (string) ($existing['source_locale'] ?? $this->defaultLocale($settings));
        }

        $translations = [];
        foreach ($this->supportedLocales() as $locale) {
            $input = is_array($submitted[$locale] ?? null) ? $submitted[$locale] : [];
            $fallback = is_array($existing['translations'][$locale] ?? null)
                ? $existing['translations'][$locale]
                : [];
            $entry = [];

            foreach (self::FIELDS as $field) {
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
        }

        return [
            'source_locale' => $normalizedSourceLocale,
            'updated_at' => date('Y-m-d H:i:s'),
            'translations' => $translations,
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function normalizeState(array $state, ?array $settings = null): array
    {
        $settings = $settings ?? FlatFile::settings();
        $sourceLocale = $this->normalizeLocale((string) ($state['source_locale'] ?? ''));
        if ($sourceLocale === '') {
            $sourceLocale = $this->defaultLocale($settings);
        }

        $rawTranslations = is_array($state['translations'] ?? null) ? $state['translations'] : [];
        $translations = [];
        foreach ($this->supportedLocales() as $locale) {
            $entry = is_array($rawTranslations[$locale] ?? null) ? $rawTranslations[$locale] : [];
            $normalized = $locale === $sourceLocale
                ? $this->normalizeTranslation($entry, $settings)
                : $this->normalizeTranslation($entry);

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

    /**
     * @param array<string, mixed>|null $fallbackSettings
     * @return array<string, string>
     */
    private function normalizeTranslation(array $translation, ?array $fallbackSettings = null): array
    {
        $normalized = [];
        foreach (self::FIELDS as $field) {
            $value = trim((string) ($translation[$field] ?? ''));
            if ($value === '' && is_array($fallbackSettings)) {
                $value = trim((string) ($fallbackSettings[$field] ?? ''));
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
        foreach (self::FIELDS as $field) {
            if (trim((string) ($translation[$field] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }
}
