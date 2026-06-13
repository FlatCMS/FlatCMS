<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Categories\Services;

use App\Core\FlatFile;
use App\Core\I18n;

final class CategoryTranslationService
{
    private FlatFile $categories;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $allCategories = null;

    public function __construct(?FlatFile $categories = null)
    {
        $this->categories = $categories ?? FlatFile::for('core/categories');
    }

    /**
     * @return array<int, string>
     */
    public function supportedLocales(): array
    {
        return I18n::getSupportedLocales();
    }

    public function defaultLocale(): string
    {
        $supported = $this->supportedLocales();
        $settings = FlatFile::settings();
        $defaultLocale = $this->normalizeLocale((string) ($settings['default_language'] ?? ''));

        if ($defaultLocale !== '') {
            return $defaultLocale;
        }

        return $supported[0] ?? 'fr-FR';
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

    public function getLocaleLabel(string $locale, ?string $uiLocale = null): string
    {
        $normalized = $this->normalizeLocale($locale);
        if ($normalized === '') {
            return trim($locale);
        }

        $label = I18n::getLocalizedLanguageName($normalized, $uiLocale ?? I18n::getLocale());
        return $label !== '' ? $label : $normalized;
    }

    /**
     * @param array<string, mixed> $category
     * @return array<string, mixed>
     */
    public function normalizeCategory(array $category): array
    {
        $categoryId = trim((string) ($category['id'] ?? ''));
        $translationGroup = trim((string) ($category['translation_group'] ?? ''));
        if ($translationGroup === '') {
            $translationGroup = $categoryId;
        }

        $locale = $this->normalizeLocale((string) ($category['locale'] ?? ''));
        if ($locale === '') {
            $locale = $this->defaultLocale();
        }

        $sourceLocale = $this->normalizeLocale((string) ($category['source_locale'] ?? ''));
        if ($sourceLocale === '') {
            $sourceLocale = $locale;
        }

        $status = trim((string) ($category['status'] ?? 'active'));
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $module = trim((string) ($category['module'] ?? 'blog'));
        if ($module === '') {
            $module = 'blog';
        }

        $category['translation_group'] = $translationGroup;
        $category['locale'] = $locale;
        $category['source_locale'] = $sourceLocale;
        $category['status'] = $status;
        $category['module'] = $module;

        return $category;
    }

    public function normalizePayload(array $data, ?array $existing = null): array
    {
        $payload = $existing ? array_merge($existing, $data) : $data;
        $payload = $this->normalizeCategory($payload);

        if (trim((string) ($payload['translation_group'] ?? '')) === '' && !empty($payload['id'])) {
            $payload['translation_group'] = (string) $payload['id'];
        }

        return $payload;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->allCategories !== null) {
            return $this->allCategories;
        }

        $rawCategories = $this->categories->all();
        $normalizedCategories = [];
        $groupSourceLocales = [];

        foreach ($rawCategories as $category) {
            $normalized = $this->normalizeCategory($category);
            $translationGroup = trim((string) ($normalized['translation_group'] ?? ''));
            $explicitSourceLocale = $this->normalizeLocale((string) ($category['source_locale'] ?? ''));

            $normalized['__flatcms_explicit_locale'] = trim((string) ($category['locale'] ?? '')) !== '';
            $normalized['__flatcms_explicit_source_locale'] = $explicitSourceLocale !== '';

            if ($translationGroup !== '' && $explicitSourceLocale !== '' && !isset($groupSourceLocales[$translationGroup])) {
                $groupSourceLocales[$translationGroup] = $explicitSourceLocale;
            }

            $normalizedCategories[] = $normalized;
        }

        foreach ($normalizedCategories as &$category) {
            $translationGroup = trim((string) ($category['translation_group'] ?? ''));
            $explicitLocale = !empty($category['__flatcms_explicit_locale']);
            $explicitSourceLocale = !empty($category['__flatcms_explicit_source_locale']);
            $inferredSourceLocale = $groupSourceLocales[$translationGroup] ?? '';

            if (!$explicitLocale && !$explicitSourceLocale && $translationGroup !== '' && $inferredSourceLocale !== '') {
                $category['locale'] = $inferredSourceLocale;
                $category['source_locale'] = $inferredSourceLocale;
            }

            unset($category['__flatcms_explicit_locale'], $category['__flatcms_explicit_source_locale']);
        }
        unset($category);

        $this->allCategories = $normalizedCategories;

        return $this->allCategories;
    }

    public function find(string $id): ?array
    {
        $category = $this->categories->find($id);
        return is_array($category) ? $this->normalizeCategory($category) : null;
    }

    public function findByTranslationGroupAndLocale(string $translationGroup, string $locale, bool $activeOnly = false): ?array
    {
        $normalizedGroup = trim($translationGroup);
        $normalizedLocale = $this->normalizeLocale($locale);
        if ($normalizedGroup === '' || $normalizedLocale === '') {
            return null;
        }

        foreach ($this->all() as $category) {
            if (($category['translation_group'] ?? '') !== $normalizedGroup) {
                continue;
            }

            if (($category['locale'] ?? '') !== $normalizedLocale) {
                continue;
            }

            if ($activeOnly && $this->resolveEffectiveStatus($category) !== 'active') {
                continue;
            }

            return $category;
        }

        return null;
    }

    public function findBySlugAndLocale(string $slug, string $locale, bool $activeOnly = false): ?array
    {
        $normalizedSlug = trim($slug);
        $normalizedLocale = $this->normalizeLocale($locale);
        if ($normalizedSlug === '' || $normalizedLocale === '') {
            return null;
        }

        foreach ($this->all() as $category) {
            if ((string) ($category['slug'] ?? '') !== $normalizedSlug) {
                continue;
            }

            if ((string) ($category['locale'] ?? '') !== $normalizedLocale) {
                continue;
            }

            if ($activeOnly && $this->resolveEffectiveStatus($category) !== 'active') {
                continue;
            }

            return $category;
        }

        return null;
    }

    public function findBySlug(string $slug, bool $activeOnly = false): ?array
    {
        $normalizedSlug = trim($slug);
        if ($normalizedSlug === '') {
            return null;
        }

        foreach ($this->all() as $category) {
            if ((string) ($category['slug'] ?? '') !== $normalizedSlug) {
                continue;
            }

            if ($activeOnly && $this->resolveEffectiveStatus($category) !== 'active') {
                continue;
            }

            return $category;
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getTranslations(string $translationGroup, bool $activeOnly = false): array
    {
        $normalizedGroup = trim($translationGroup);
        if ($normalizedGroup === '') {
            return [];
        }

        $translations = [];
        foreach ($this->all() as $category) {
            if (($category['translation_group'] ?? '') !== $normalizedGroup) {
                continue;
            }

            if ($activeOnly && $this->resolveEffectiveStatus($category) !== 'active') {
                continue;
            }

            $locale = (string) ($category['locale'] ?? '');
            if ($locale === '') {
                continue;
            }

            $translations[$locale] = $category;
        }

        return $translations;
    }

    public function resolveSourceCategory(string $translationGroup): ?array
    {
        $translations = $this->getTranslations($translationGroup, false);
        if ($translations === []) {
            return null;
        }

        $sourceLocale = '';
        foreach ($translations as $translation) {
            $candidate = trim((string) ($translation['source_locale'] ?? ''));
            if ($candidate !== '') {
                $sourceLocale = $candidate;
                break;
            }
        }

        if ($sourceLocale !== '' && isset($translations[$sourceLocale])) {
            return $translations[$sourceLocale];
        }

        foreach ($this->supportedLocales() as $locale) {
            if (isset($translations[$locale])) {
                return $translations[$locale];
            }
        }

        return reset($translations) ?: null;
    }

    public function resolveEffectiveStatus(array $category): string
    {
        $normalized = $this->normalizeCategory($category);
        $translationGroup = trim((string) ($normalized['translation_group'] ?? ''));
        if ($translationGroup === '') {
            return (string) ($normalized['status'] ?? 'active');
        }

        $sourceCategory = $this->resolveSourceCategory($translationGroup);
        if (is_array($sourceCategory)) {
            return (string) ($sourceCategory['status'] ?? 'active');
        }

        return (string) ($normalized['status'] ?? 'active');
    }

    public function resolveCanonicalId(string $id): string
    {
        $category = $this->find($id);
        if (!is_array($category)) {
            return '';
        }

        $translationGroup = trim((string) ($category['translation_group'] ?? ''));
        if ($translationGroup === '') {
            return trim((string) ($category['id'] ?? ''));
        }

        $sourceCategory = $this->resolveSourceCategory($translationGroup);
        if (is_array($sourceCategory)) {
            return trim((string) ($sourceCategory['id'] ?? ''));
        }

        return trim((string) ($category['id'] ?? ''));
    }

    public function buildTranslationSeed(string $locale, ?array $sourceCategory = null): array
    {
        $normalizedLocale = $this->normalizeLocale($locale);
        if ($normalizedLocale === '') {
            $normalizedLocale = $this->defaultLocale();
        }

        if (!is_array($sourceCategory)) {
            return [
                'locale' => $normalizedLocale,
                'source_locale' => $normalizedLocale,
                'module' => 'blog',
                'status' => 'active',
            ];
        }

        $source = $this->normalizeCategory($sourceCategory);

        return [
            'name' => '',
            'slug' => '',
            'description' => '',
            'module' => (string) ($source['module'] ?? 'blog'),
            'status' => (string) ($source['status'] ?? 'active'),
            'translation_group' => (string) ($source['translation_group'] ?? ''),
            'locale' => $normalizedLocale,
            'source_locale' => (string) ($source['source_locale'] ?? $source['locale'] ?? $normalizedLocale),
        ];
    }

    public function resolveUniqueSlug(string $slug, string $locale, ?string $ignoreId = null): string
    {
        $base = trim($slug);
        if ($base === '') {
            return '';
        }

        $candidate = $base;
        $suffix = 2;

        while (true) {
            $existing = $this->findBySlugAndLocale($candidate, $locale, false);
            if (!is_array($existing)) {
                return $candidate;
            }

            if ($ignoreId !== null && (string) ($existing['id'] ?? '') === $ignoreId) {
                return $candidate;
            }

            $candidate = $base . '-' . $suffix;
            $suffix++;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildLocalizedCategories(string $module, string $locale, bool $activeOnly = false): array
    {
        $normalizedLocale = $this->normalizeLocale($locale);
        if ($normalizedLocale === '') {
            $normalizedLocale = $this->defaultLocale();
        }

        $safeModule = trim($module);
        if ($safeModule === '') {
            $safeModule = 'blog';
        }

        $groups = [];
        foreach ($this->all() as $category) {
            if ((string) ($category['module'] ?? 'blog') !== $safeModule) {
                continue;
            }

            if ($activeOnly && $this->resolveEffectiveStatus($category) !== 'active') {
                continue;
            }

            $groupId = trim((string) ($category['translation_group'] ?? $category['id'] ?? ''));
            if ($groupId === '') {
                continue;
            }

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [];
            }
            $groups[$groupId][] = $category;
        }

        $items = [];
        foreach ($groups as $groupId => $translations) {
            $sourceCategory = $this->resolveSourceCategory($groupId);
            if (!is_array($sourceCategory)) {
                $sourceCategory = reset($translations) ?: null;
            }
            if (!is_array($sourceCategory)) {
                continue;
            }

            $localizedCategory = $this->findByTranslationGroupAndLocale($groupId, $normalizedLocale, false);
            if (!is_array($localizedCategory)) {
                $localizedCategory = $sourceCategory;
            }

            $canonicalId = trim((string) ($sourceCategory['id'] ?? $localizedCategory['id'] ?? ''));
            if ($canonicalId === '') {
                continue;
            }

            $entry = $this->normalizeCategory($localizedCategory);
            $entry['source_id'] = $canonicalId;
            $entry['translation_id'] = trim((string) ($localizedCategory['id'] ?? $canonicalId));
            $entry['id'] = $canonicalId;
            $entry['status'] = $this->resolveEffectiveStatus($sourceCategory);
            $entry['module'] = (string) ($sourceCategory['module'] ?? $entry['module'] ?? $safeModule);
            $entry['source_locale'] = (string) ($sourceCategory['source_locale'] ?? $sourceCategory['locale'] ?? $entry['source_locale'] ?? $normalizedLocale);
            $items[] = $entry;
        }

        usort($items, static fn(array $a, array $b): int => strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

        return $items;
    }
}
