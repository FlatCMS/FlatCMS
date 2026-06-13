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

use App\Services\StructuredData\Contracts\StructuredDataProviderInterface;

class SiteSchemaProvider implements StructuredDataProviderInterface
{
    public function provide(array $context): array
    {
        $siteUrl = trim((string) ($context['site_url'] ?? ''));
        $siteName = trim((string) ($context['site_name'] ?? ''));
        if ($siteUrl === '' || $siteName === '') {
            return [];
        }

        $siteId = $siteUrl . '#website';
        $organizationId = $siteUrl . '#organization';
        $description = trim((string) ($context['site_description'] ?? ''));
        $locale = trim((string) ($context['locale'] ?? ''));
        $siteLogoUrl = trim((string) ($context['site_logo_url'] ?? ''));

        $website = [
            '@type' => 'WebSite',
            '@id' => $siteId,
            'url' => $siteUrl,
            'name' => $siteName,
            'description' => $description,
            'inLanguage' => $locale,
            'publisher' => [
                '@id' => $organizationId,
            ],
        ];

        $organization = [
            '@type' => 'Organization',
            '@id' => $organizationId,
            'url' => $siteUrl,
            'name' => $siteName,
            'description' => $description,
        ];

        if ($siteLogoUrl !== '') {
            $organization['logo'] = [
                '@type' => 'ImageObject',
                'url' => $siteLogoUrl,
            ];
        }

        return [$website, $organization];
    }
}
