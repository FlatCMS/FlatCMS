<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Posts\Services;

use App\Core\FlatFile;
use App\Core\I18n;

final class PostTranslationService
{
    private FlatFile $posts;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $allPosts = null;

    public function __construct(?FlatFile $posts = null)
    {
        $this->posts = $posts ?? FlatFile::for('core/posts');
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
    public function normalizePost(array $post): array
    {
        $postId = trim((string) ($post['id'] ?? ''));
        $translationGroup = trim((string) ($post['translation_group'] ?? ''));
        if ($translationGroup === '') {
            $translationGroup = $postId;
        }

        $locale = $this->normalizeLocale((string) ($post['locale'] ?? ''));
        if ($locale === '') {
            $locale = $this->defaultLocale();
        }

        $sourceLocale = $this->normalizeLocale((string) ($post['source_locale'] ?? ''));
        if ($sourceLocale === '') {
            $sourceLocale = $locale;
        }

        $post['translation_group'] = $translationGroup;
        $post['locale'] = $locale;
        $post['source_locale'] = $sourceLocale;

        return $post;
    }

    public function normalizePayload(array $data, ?array $existing = null): array
    {
        $payload = $existing ? array_merge($existing, $data) : $data;
        $payload = $this->normalizePost($payload);

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
        if ($this->allPosts !== null) {
            return $this->allPosts;
        }

        $rawPosts = $this->posts->all();
        $normalizedPosts = [];
        $groupSourceLocales = [];

        foreach ($rawPosts as $post) {
            $normalized = $this->normalizePost($post);
            $translationGroup = trim((string) ($normalized['translation_group'] ?? ''));
            $explicitSourceLocale = $this->normalizeLocale((string) ($post['source_locale'] ?? ''));

            $normalized['__flatcms_explicit_locale'] = trim((string) ($post['locale'] ?? '')) !== '';
            $normalized['__flatcms_explicit_source_locale'] = $explicitSourceLocale !== '';

            if ($translationGroup !== '' && $explicitSourceLocale !== '' && !isset($groupSourceLocales[$translationGroup])) {
                $groupSourceLocales[$translationGroup] = $explicitSourceLocale;
            }

            $normalizedPosts[] = $normalized;
        }

        foreach ($normalizedPosts as &$post) {
            $translationGroup = trim((string) ($post['translation_group'] ?? ''));
            $explicitLocale = !empty($post['__flatcms_explicit_locale']);
            $explicitSourceLocale = !empty($post['__flatcms_explicit_source_locale']);
            $inferredSourceLocale = $groupSourceLocales[$translationGroup] ?? '';

            if (!$explicitLocale && !$explicitSourceLocale && $translationGroup !== '' && $inferredSourceLocale !== '') {
                $post['locale'] = $inferredSourceLocale;
                $post['source_locale'] = $inferredSourceLocale;
            }

            unset($post['__flatcms_explicit_locale'], $post['__flatcms_explicit_source_locale']);
        }
        unset($post);

        $this->allPosts = $normalizedPosts;

        return $this->allPosts;
    }

    public function find(string $id): ?array
    {
        $post = $this->posts->find($id);
        return is_array($post) ? $this->normalizePost($post) : null;
    }

    public function findByTranslationGroupAndLocale(string $translationGroup, string $locale, bool $publishedOnly = false): ?array
    {
        $normalizedGroup = trim($translationGroup);
        $normalizedLocale = $this->normalizeLocale($locale);
        if ($normalizedGroup === '' || $normalizedLocale === '') {
            return null;
        }

        foreach ($this->all() as $post) {
            if (($post['translation_group'] ?? '') !== $normalizedGroup) {
                continue;
            }

            if (($post['locale'] ?? '') !== $normalizedLocale) {
                continue;
            }

            if ($publishedOnly && $this->resolveEffectiveStatus($post) !== 'published') {
                continue;
            }

            return $post;
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

        foreach ($this->all() as $post) {
            if ((string) ($post['slug'] ?? '') !== $normalizedSlug) {
                continue;
            }

            if ((string) ($post['locale'] ?? '') !== $normalizedLocale) {
                continue;
            }

            if ($publishedOnly && $this->resolveEffectiveStatus($post) !== 'published') {
                continue;
            }

            return $post;
        }

        return null;
    }

    public function findBySlug(string $slug, bool $publishedOnly = false): ?array
    {
        $normalizedSlug = trim($slug);
        if ($normalizedSlug === '') {
            return null;
        }

        foreach ($this->all() as $post) {
            if ((string) ($post['slug'] ?? '') !== $normalizedSlug) {
                continue;
            }

            if ($publishedOnly && $this->resolveEffectiveStatus($post) !== 'published') {
                continue;
            }

            return $post;
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
        foreach ($this->all() as $post) {
            if (($post['translation_group'] ?? '') !== $normalizedGroup) {
                continue;
            }

            if ($publishedOnly && $this->resolveEffectiveStatus($post) !== 'published') {
                continue;
            }

            $locale = (string) ($post['locale'] ?? '');
            if ($locale === '') {
                continue;
            }

            $translations[$locale] = $post;
        }

        return $translations;
    }

    public function resolveSourcePost(string $translationGroup): ?array
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

    public function resolveEffectiveStatus(array $post): string
    {
        $normalized = $this->normalizePost($post);
        $translationGroup = trim((string) ($normalized['translation_group'] ?? ''));
        if ($translationGroup === '') {
            return (string) ($normalized['status'] ?? 'draft');
        }

        $sourcePost = $this->resolveSourcePost($translationGroup);
        if (is_array($sourcePost)) {
            return (string) ($sourcePost['status'] ?? 'draft');
        }

        return (string) ($normalized['status'] ?? 'draft');
    }

    public function buildTranslationSeed(string $locale, ?array $sourcePost = null): array
    {
        $normalizedLocale = $this->normalizeLocale($locale);
        if ($normalizedLocale === '') {
            $normalizedLocale = $this->defaultLocale();
        }

        if (!is_array($sourcePost)) {
            return [
                'locale' => $normalizedLocale,
                'source_locale' => $normalizedLocale,
                'status' => 'draft',
            ];
        }

        $source = $this->normalizePost($sourcePost);

        return [
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'content' => '',
            'featured_image' => '',
            'meta_title' => '',
            'meta_description' => '',
            'categories' => [],
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
