<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\StructuredData\Providers;

use App\Core\I18n;
use App\Services\StructuredData\Contracts\StructuredDataProviderInterface;

class PostSchemaProvider implements StructuredDataProviderInterface
{
    public function provide(array $context): array
    {
        $post = $context['post'] ?? null;
        if (!is_array($post)) {
            return [];
        }

        $currentUrl = trim((string) ($context['current_url'] ?? ''));
        $siteUrl = trim((string) ($context['site_url'] ?? ''));
        $title = trim((string) ($post['title'] ?? $context['page_title'] ?? ''));
        if ($currentUrl === '' || $title === '') {
            return [];
        }

        $description = trim((string) ($post['meta_description'] ?? $post['excerpt'] ?? ''));
        $featuredImage = trim((string) ($context['post_image_url'] ?? ''));
        $locale = trim((string) ($post['locale'] ?? $context['locale'] ?? ''));
        $articleId = $currentUrl . '#article';
        $breadcrumbId = $currentUrl . '#breadcrumb';

        $article = [
            '@type' => 'BlogPosting',
            '@id' => $articleId,
            'mainEntityOfPage' => [
                '@id' => $currentUrl . '#webpage',
            ],
            'headline' => $title,
            'description' => $description,
            'url' => $currentUrl,
            'datePublished' => $this->toIso8601((string) ($post['created_at'] ?? '')),
            'dateModified' => $this->toIso8601((string) ($post['updated_at'] ?? '')),
            'inLanguage' => $locale,
            'publisher' => $siteUrl !== '' ? ['@id' => $siteUrl . '#organization'] : null,
            'author' => $siteUrl !== '' ? ['@id' => $siteUrl . '#organization'] : null,
            'image' => $featuredImage !== '' ? [$featuredImage] : null,
        ];

        I18n::load('Posts');
        $breadcrumbItems = [];
        $homeName = trim((string) ($context['site_name'] ?? __('app_name', 'Core')));
        $homeUrl = trim((string) ($context['home_url'] ?? $siteUrl));
        if ($homeUrl !== '' && $homeName !== '') {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => count($breadcrumbItems) + 1,
                'name' => $homeName,
                'item' => $homeUrl,
            ];
        }

        $blogUrl = trim((string) ($context['blog_url'] ?? ''));
        $blogName = (string) __('blog', 'Posts');
        if ($blogUrl !== '' && $blogName !== '') {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => count($breadcrumbItems) + 1,
                'name' => $blogName,
                'item' => $blogUrl,
            ];
        }

        $postCategories = $context['post_categories'] ?? [];
        if (is_array($postCategories) && isset($postCategories[0]) && is_array($postCategories[0])) {
            $firstCategory = $postCategories[0];
            $categoryName = trim((string) ($firstCategory['name'] ?? ''));
            $categorySlug = trim((string) ($firstCategory['slug'] ?? ''));
            $siteUrl = trim((string) ($context['site_url'] ?? ''));
            if ($categoryName !== '' && $categorySlug !== '' && trim((string) ($context['locale'] ?? '')) !== '' && $siteUrl !== '') {
                $categoryUrl = rtrim($siteUrl, '/') . '/' . trim((string) ($context['locale'] ?? ''), '/') . '/blog/categorie/' . rawurlencode($categorySlug);
                $breadcrumbItems[] = [
                    '@type' => 'ListItem',
                    'position' => count($breadcrumbItems) + 1,
                    'name' => $categoryName,
                    'item' => $categoryUrl,
                ];
            }
        }

        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name' => $title,
            'item' => $currentUrl,
        ];

        return [
            $article,
            [
                '@type' => 'BreadcrumbList',
                '@id' => $breadcrumbId,
                'itemListElement' => $breadcrumbItems,
            ],
        ];
    }

    private function toIso8601(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($value))->format(\DateTimeInterface::ATOM);
        } catch (\Throwable) {
            return '';
        }
    }
}
