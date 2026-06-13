<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Pages\Services;

use App\Core\FlatFile;
use App\Core\I18n;

final class PageTranslationService
{
    private FlatFile $pages;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $allPages = null;

    public function __construct(?FlatFile $pages = null)
    {
        $this->pages = $pages ?? FlatFile::for('core/pages');
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
     * @return array<string, mixed>
     */
    public function normalizePage(array $page): array
    {
        $pageId = trim((string) ($page['id'] ?? ''));
        $translationGroup = trim((string) ($page['translation_group'] ?? ''));
        if ($translationGroup === '') {
            $translationGroup = $pageId;
        }

        $locale = $this->normalizeLocale((string) ($page['locale'] ?? ''));
        if ($locale === '') {
            $locale = $this->defaultLocale();
        }

        $sourceLocale = $this->normalizeLocale((string) ($page['source_locale'] ?? ''));
        if ($sourceLocale === '') {
            $sourceLocale = $locale;
        }

        $page['translation_group'] = $translationGroup;
        $page['locale'] = $locale;
        $page['source_locale'] = $sourceLocale;
        $page['render_mode'] = 'classic';

        return $page;
    }

    public function resolveRenderMode(array $page): string
    {
        return 'classic';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->allPages !== null) {
            return $this->allPages;
        }

        $rawPages = $this->pages->all();
        $normalizedPages = [];
        $groupSourceLocales = [];

        foreach ($rawPages as $page) {
            $normalized = $this->normalizePage($page);
            $translationGroup = trim((string) ($normalized['translation_group'] ?? ''));
            $explicitSourceLocale = $this->normalizeLocale((string) ($page['source_locale'] ?? ''));

            $normalized['__flatcms_explicit_locale'] = trim((string) ($page['locale'] ?? '')) !== '';
            $normalized['__flatcms_explicit_source_locale'] = $explicitSourceLocale !== '';

            if ($translationGroup !== '' && $explicitSourceLocale !== '' && !isset($groupSourceLocales[$translationGroup])) {
                $groupSourceLocales[$translationGroup] = $explicitSourceLocale;
            }

            $normalizedPages[] = $normalized;
        }

        foreach ($normalizedPages as &$page) {
            $translationGroup = trim((string) ($page['translation_group'] ?? ''));
            $explicitLocale = !empty($page['__flatcms_explicit_locale']);
            $explicitSourceLocale = !empty($page['__flatcms_explicit_source_locale']);
            $inferredSourceLocale = $groupSourceLocales[$translationGroup] ?? '';

            if (!$explicitLocale && !$explicitSourceLocale && $translationGroup !== '' && $inferredSourceLocale !== '') {
                $page['locale'] = $inferredSourceLocale;
                $page['source_locale'] = $inferredSourceLocale;
            }

            unset($page['__flatcms_explicit_locale'], $page['__flatcms_explicit_source_locale']);
        }
        unset($page);

        $this->allPages = $normalizedPages;

        return $this->allPages;
    }

    public function find(string $id): ?array
    {
        $page = $this->pages->find($id);
        return is_array($page) ? $this->normalizePage($page) : null;
    }

    public function findByTranslationGroupAndLocale(string $translationGroup, string $locale, bool $publishedOnly = false): ?array
    {
        $normalizedGroup = trim($translationGroup);
        $normalizedLocale = $this->normalizeLocale($locale);
        if ($normalizedGroup === '' || $normalizedLocale === '') {
            return null;
        }

        foreach ($this->all() as $page) {
            if (($page['translation_group'] ?? '') !== $normalizedGroup) {
                continue;
            }

            if (($page['locale'] ?? '') !== $normalizedLocale) {
                continue;
            }

            if ($publishedOnly && $this->resolveEffectiveStatus($page) !== 'published') {
                continue;
            }

            return $page;
        }

        return null;
    }

    public function findBySlugAndLocale(string $slug, string $locale, bool $publishedOnly = false): ?array
    {
        $normalizedSlug = trim($slug);
        $normalizedLocale = $this->normalizeLocale($locale);
        if ($normalizedSlug === '' || $normalizedLocale === '') {
            return null;
        }

        foreach ($this->all() as $page) {
            if ((string) ($page['slug'] ?? '') !== $normalizedSlug) {
                continue;
            }

            if ((string) ($page['locale'] ?? '') !== $normalizedLocale) {
                continue;
            }

            if ($publishedOnly && $this->resolveEffectiveStatus($page) !== 'published') {
                continue;
            }

            return $page;
        }

        return null;
    }

    public function findBySlug(string $slug, bool $publishedOnly = false): ?array
    {
        $normalizedSlug = trim($slug);
        if ($normalizedSlug === '') {
            return null;
        }

        foreach ($this->all() as $page) {
            if ((string) ($page['slug'] ?? '') !== $normalizedSlug) {
                continue;
            }

            if ($publishedOnly && $this->resolveEffectiveStatus($page) !== 'published') {
                continue;
            }

            return $page;
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getTranslations(string $translationGroup, bool $publishedOnly = false): array
    {
        $normalizedGroup = trim($translationGroup);
        if ($normalizedGroup === '') {
            return [];
        }

        $translations = [];
        foreach ($this->all() as $page) {
            if (($page['translation_group'] ?? '') !== $normalizedGroup) {
                continue;
            }

            if ($publishedOnly && $this->resolveEffectiveStatus($page) !== 'published') {
                continue;
            }

            $locale = (string) ($page['locale'] ?? '');
            if ($locale === '') {
                continue;
            }

            $translations[$locale] = $page;
        }

        return $translations;
    }

    public function resolveSourcePage(string $translationGroup): ?array
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

    public function resolveEffectiveStatus(array $page): string
    {
        $normalized = $this->normalizePage($page);
        $translationGroup = trim((string) ($normalized['translation_group'] ?? ''));
        if ($translationGroup === '') {
            return (string) ($normalized['status'] ?? 'draft');
        }

        $sourcePage = $this->resolveSourcePage($translationGroup);
        if (is_array($sourcePage)) {
            return (string) ($sourcePage['status'] ?? 'draft');
        }

        return (string) ($normalized['status'] ?? 'draft');
    }

    /**
     * @return array<string, mixed>
     */
    public function buildTranslationSeed(string $locale, ?array $sourcePage = null): array
    {
        $normalizedLocale = $this->normalizeLocale($locale);
        if ($normalizedLocale === '') {
            $normalizedLocale = $this->defaultLocale();
        }

        if (!is_array($sourcePage)) {
            return [
                'locale' => $normalizedLocale,
                'source_locale' => $normalizedLocale,
                'status' => 'draft',
            ];
        }

        $source = $this->normalizePage($sourcePage);

        return [
            'title' => '',
            'slug' => '',
            'content' => '',
            'meta_title' => '',
            'meta_description' => '',
            'status' => (string) ($source['status'] ?? 'draft'),
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
}
