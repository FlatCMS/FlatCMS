<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\Seo;

use App\Core\ContentDocumentStore;
use App\Modules\Categories\Services\CategoryTranslationService;
use App\Modules\Pages\Services\PageTranslationService;
use App\Modules\Posts\Services\PostTranslationService;
use App\Modules\Settings\Services\SiteRoutingService;

final class SeoMetadataService
{
    /**
     * @param array<string, mixed> $viewData
     * @return array<string, mixed>
     */
    public function build(array $viewData): array
    {
        $settings = is_array($viewData['settings'] ?? null) ? $viewData['settings'] : [];
        $page = is_array($viewData['page'] ?? null) ? $viewData['page'] : null;
        $post = is_array($viewData['post'] ?? null) ? $viewData['post'] : null;
        $category = is_array($viewData['currentCategory'] ?? null) ? $viewData['currentCategory'] : null;
        $sitemapPage = !empty($viewData['sitemap_page']);
        $requestedLocale = $this->normalizeLocale((string) ($viewData['locale'] ?? locale()));
        $document = $page ?? $post ?? $category;
        $contentLocale = $this->normalizeLocale((string) ($document['locale'] ?? $requestedLocale));
        if ($contentLocale === '') {
            $contentLocale = $requestedLocale !== '' ? $requestedLocale : 'fr-FR';
        }

        $baseUrl = $this->baseUrl($settings);
        $canonicalPath = $this->documentPath($page, $post, $category, $contentLocale, $sitemapPage);
        $canonicalUrl = $this->absoluteUrl($canonicalPath, $baseUrl);
        $alternates = $this->translationAlternates($page, $post, $category, $baseUrl);
        if ($document === null && !$sitemapPage && $this->isBlogIndexPath($canonicalPath)) {
            $alternates = $this->blogAlternates($baseUrl);
        }
        if ($document !== null && !isset($alternates[$contentLocale])) {
            $alternates[$contentLocale] = $canonicalUrl;
        }
        ksort($alternates, SORT_STRING);

        $blogIndex = $document === null && $this->isBlogIndexPath($canonicalPath);
        $sourceLocale = $this->normalizeLocale((string) ($document['source_locale']
            ?? ($blogIndex ? ($settings['default_language'] ?? $contentLocale) : $contentLocale)));
        $xDefaultUrl = $alternates !== [] ? ($alternates[$sourceLocale] ?? $canonicalUrl) : '';
        $title = trim((string) ($viewData['pageTitle']
            ?? $document['meta_title']
            ?? $this->seoValue($document, 'title')
            ?? $document['title']
            ?? $document['name']
            ?? $settings['site_name']
            ?? ''));
        $description = trim((string) ($viewData['metaDescription']
            ?? $document['meta_description']
            ?? $this->seoValue($document, 'description')
            ?? $document['excerpt']
            ?? $document['description']
            ?? $settings['meta_description']
            ?? $settings['site_description']
            ?? ''));
        $imageUrl = $this->imageUrl($document, $baseUrl);
        $publishedTime = $post !== null ? $this->isoDate((string) ($post['published_at'] ?? $post['created_at'] ?? '')) : '';
        $modifiedTime = $post !== null ? $this->isoDate((string) ($post['updated_at'] ?? '')) : '';

        return [
            'canonical_url' => $canonicalUrl,
            'content_locale' => $contentLocale,
            'alternates' => $alternates,
            'x_default_url' => $xDefaultUrl,
            'robots' => $this->robotsDirective($viewData, $document),
            'open_graph' => [
                'type' => $post !== null ? 'article' : 'website',
                'title' => $title,
                'description' => $description,
                'url' => $canonicalUrl,
                'site_name' => trim((string) ($settings['site_name'] ?? '')),
                'locale' => str_replace('-', '_', $contentLocale),
                'locale_alternates' => array_values(array_map(
                    static fn (string $locale): string => str_replace('-', '_', $locale),
                    array_keys(array_filter(
                        $alternates,
                        static fn (string $url, string $locale): bool => $locale !== $contentLocale,
                        ARRAY_FILTER_USE_BOTH
                    ))
                )),
                'image' => $imageUrl,
                'published_time' => $publishedTime,
                'modified_time' => $modifiedTime,
            ],
            'twitter' => [
                'card' => $imageUrl !== '' ? 'summary_large_image' : 'summary',
                'title' => $title,
                'description' => $description,
                'image' => $imageUrl,
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $page
     * @param array<string, mixed>|null $post
     * @param array<string, mixed>|null $category
     * @return array<string, string>
     */
    private function translationAlternates(?array $page, ?array $post, ?array $category, string $baseUrl): array
    {
        $alternates = [];

        if ($page !== null) {
            $service = new PageTranslationService(ContentDocumentStore::for('core/pages'));
            $group = trim((string) ($page['translation_group'] ?? ''));
            foreach ($service->getTranslations($group, true) as $locale => $translation) {
                $normalizedLocale = $this->normalizeLocale((string) $locale);
                if ($normalizedLocale === '') {
                    continue;
                }
                $alternates[$normalizedLocale] = $this->absoluteUrl(
                    $this->documentPath($translation, null, null, $normalizedLocale),
                    $baseUrl
                );
            }
        }

        if ($post !== null) {
            $service = new PostTranslationService(ContentDocumentStore::for('core/posts'));
            $group = trim((string) ($post['translation_group'] ?? ''));
            foreach ($service->getTranslations($group, true) as $locale => $translation) {
                $normalizedLocale = $this->normalizeLocale((string) $locale);
                if ($normalizedLocale === '') {
                    continue;
                }
                $alternates[$normalizedLocale] = $this->absoluteUrl(
                    $this->documentPath(null, $translation, null, $normalizedLocale),
                    $baseUrl
                );
            }
        }

        if ($category !== null) {
            $service = new CategoryTranslationService();
            $group = trim((string) ($category['translation_group'] ?? ''));
            foreach ($service->getTranslations($group, true) as $locale => $translation) {
                $normalizedLocale = $this->normalizeLocale((string) $locale);
                if ($normalizedLocale === '') {
                    continue;
                }
                $alternates[$normalizedLocale] = $this->absoluteUrl(
                    $this->documentPath(null, null, $translation, $normalizedLocale),
                    $baseUrl
                );
            }
        }

        return $alternates;
    }

    /**
     * @return array<string, string>
     */
    private function blogAlternates(string $baseUrl): array
    {
        $service = new PostTranslationService(ContentDocumentStore::for('core/posts'));
        $locales = [];
        foreach ($service->all() as $post) {
            if ($service->resolveEffectiveStatus($post) !== 'published') {
                continue;
            }

            $locale = $this->normalizeLocale((string) ($post['locale'] ?? ''));
            if ($locale !== '') {
                $locales[$locale] = $this->absoluteUrl('/' . $locale . '/blog', $baseUrl);
            }
        }

        ksort($locales, SORT_STRING);
        return $locales;
    }

    /**
     * @param array<string, mixed>|null $page
     * @param array<string, mixed>|null $post
     * @param array<string, mixed>|null $category
     */
    private function documentPath(
        ?array $page,
        ?array $post,
        ?array $category,
        string $locale,
        bool $sitemapPage = false
    ): string
    {
        if ($sitemapPage) {
            return '/sitemap';
        }

        if ($page !== null) {
            $routing = new SiteRoutingService();
            if ($routing->isHomepagePage($page)) {
                return '/' . $locale;
            }

            $slug = trim((string) ($page['slug'] ?? ''));
            return '/' . $locale . '/page/' . rawurlencode($slug);
        }

        if ($post !== null) {
            $slug = trim((string) ($post['slug'] ?? ''));
            return '/' . $locale . '/blog/' . rawurlencode($slug);
        }

        if ($category !== null) {
            $slug = trim((string) ($category['slug'] ?? ''));
            return '/' . $locale . '/blog/categorie/' . rawurlencode($slug);
        }

        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        return $path !== '' ? $path : '/';
    }

    /**
     * @param array<string, mixed> $viewData
     * @param array<string, mixed>|null $document
     */
    private function robotsDirective(array $viewData, ?array $document): string
    {
        if (http_response_code() >= 400) {
            return 'noindex,follow';
        }

        $seo = is_array($document['seo'] ?? null) ? $document['seo'] : [];
        $candidate = trim((string) ($viewData['robots']
            ?? $document['meta_robots']
            ?? $seo['robots']
            ?? ''));

        if ($candidate !== '' && preg_match('/^[a-z0-9_-]+(?:\s*,\s*[a-z0-9_-]+)*$/i', $candidate) === 1) {
            return strtolower(preg_replace('/\s+/', '', $candidate) ?? $candidate);
        }

        return 'index,follow';
    }

    /**
     * @param array<string, mixed>|null $document
     */
    private function seoValue(?array $document, string $key): ?string
    {
        if ($document === null || !is_array($document['seo'] ?? null)) {
            return null;
        }

        $value = trim((string) ($document['seo'][$key] ?? ''));
        return $value !== '' ? $value : null;
    }

    private function isoDate(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($value))->format(DATE_ATOM);
        } catch (\Throwable) {
            return '';
        }
    }

    private function isBlogIndexPath(string $path): bool
    {
        return preg_match('~^/[a-z]{2}-[a-z]{2}/blog/?$~i', $path) === 1;
    }

    /**
     * @param array<string, mixed>|null $document
     */
    private function imageUrl(?array $document, string $baseUrl): string
    {
        if ($document === null) {
            return '';
        }

        $image = trim((string) ($document['featured_image'] ?? $document['image'] ?? ''));
        if ($image === '') {
            return '';
        }

        $resolved = site_media_url($image);
        if (preg_match('~^https?://~i', $resolved) === 1) {
            return $resolved;
        }

        return $this->absoluteUrl($resolved, $baseUrl);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function baseUrl(array $settings): string
    {
        $configured = trim((string) ($settings['site_url'] ?? ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $scheme = $this->isSecureRequest() ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return rtrim($scheme . '://' . $host, '/');
    }

    private function absoluteUrl(string $path, string $baseUrl): string
    {
        if (preg_match('~^https?://~i', $path) === 1) {
            return $path;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = str_replace('_', '-', trim($locale));
        if (preg_match('/^([a-z]{2})-([a-z]{2})$/i', $locale, $matches) !== 1) {
            return '';
        }

        return strtolower($matches[1]) . '-' . strtoupper($matches[2]);
    }

    private function isSecureRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
        if ($forwardedProto === 'https') {
            return true;
        }

        $visitor = json_decode((string) ($_SERVER['HTTP_CF_VISITOR'] ?? ''), true);
        return is_array($visitor) && strtolower((string) ($visitor['scheme'] ?? '')) === 'https';
    }
}
