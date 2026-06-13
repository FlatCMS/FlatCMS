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

final class SiteBrandingTranslationService
{
    private const SETTINGS_KEY = 'site_branding_translations';

    /** @var array<int, string> */
    private const FIELDS = [
        'site_name',
        'site_description',
        'site_slogan',
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

    public function localeLabel(string $locale, ?string $uiLocale = null): string
    {
        $normalized = $this->normalizeLocale($locale);
        if ($normalized === '') {
            return trim($locale);
        }

        $label = I18n::getLocalizedLanguageName($normalized, $uiLocale ?? I18n::getLocale());
        return $label !== '' ? $label : $normalized;
    }

    public function defaultLocale(?array $settings = null): string
    {
        $settings = $settings ?? FlatFile::settings();
        $defaultLocale = $this->normalizeLocale((string) ($settings['default_language'] ?? ''));
        if ($defaultLocale !== '') {
            return $defaultLocale;
        }

        $supported = $this->supportedLocales();
        return $supported[0] ?? 'fr-FR';
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
        $targetValues = $this->normalizeTranslation((array) ($translations[$targetLocale] ?? []), $settings);

        foreach (self::FIELDS as $field) {
            $resolved = trim((string) ($targetValues[$field] ?? ''));
            if ($resolved === '') {
                $resolved = trim((string) ($sourceValues[$field] ?? ''));
            }
            if ($resolved === '') {
                $resolved = trim((string) ($settings[$field] ?? ''));
            }
            if ($field === 'site_name' && $resolved === '') {
                $resolved = (string) config('app.name', 'FlatCMS');
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
            $input = $submitted[$locale] ?? [];
            if (!is_array($input)) {
                $input = [];
            }

            $fallback = $existing['translations'][$locale] ?? [];
            if (!is_array($fallback)) {
                $fallback = [];
            }

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
            if ($field === 'site_name' && $value === '' && is_array($fallbackSettings)) {
                $value = (string) config('app.name', 'FlatCMS');
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
