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

class PageSchemaProvider implements StructuredDataProviderInterface
{
    public function provide(array $context): array
    {
        $currentUrl = trim((string) ($context['current_url'] ?? ''));
        $siteUrl = trim((string) ($context['site_url'] ?? ''));
        $pageTitle = trim((string) ($context['page_title'] ?? ''));
        if ($currentUrl === '' || $pageTitle === '') {
            return [];
        }

        $nodes = [];
        $webPageId = $currentUrl . '#webpage';
        $breadcrumbId = $currentUrl . '#breadcrumb';
        $description = trim((string) ($context['meta_description'] ?? ''));
        $viewType = trim((string) ($context['view_type'] ?? ''));
        $pageType = $viewType === 'contact' ? 'ContactPage' : 'WebPage';

        $webPage = [
            '@type' => $pageType,
            '@id' => $webPageId,
            'url' => $currentUrl,
            'name' => $pageTitle,
            'description' => $description,
            'inLanguage' => trim((string) ($context['content_locale'] ?? $context['locale'] ?? '')),
            'isPartOf' => $siteUrl !== '' ? ['@id' => $siteUrl . '#website'] : null,
            'breadcrumb' => ['@id' => $breadcrumbId],
        ];

        if ($viewType === 'contact' && $siteUrl !== '') {
            $webPage['about'] = ['@id' => $siteUrl . '#organization'];
            $webPage['mainEntity'] = ['@id' => $siteUrl . '#organization'];
        }

        $nodes[] = $webPage;

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

        if ($viewType === 'posts_index') {
            I18n::load('Posts');
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

            $currentCategory = $context['current_category'] ?? null;
            if (is_array($currentCategory)) {
                $categoryName = trim((string) ($currentCategory['name'] ?? ''));
                if ($categoryName !== '') {
                    $breadcrumbItems[] = [
                        '@type' => 'ListItem',
                        'position' => count($breadcrumbItems) + 1,
                        'name' => $categoryName,
                        'item' => $currentUrl,
                    ];
                }
            }
        } elseif ($viewType !== 'home') {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => count($breadcrumbItems) + 1,
                'name' => $pageTitle,
                'item' => $currentUrl,
            ];
        }

        if (count($breadcrumbItems) >= 1) {
            $nodes[] = [
                '@type' => 'BreadcrumbList',
                '@id' => $breadcrumbId,
                'itemListElement' => $breadcrumbItems,
            ];
        }

        return $nodes;
    }
}
