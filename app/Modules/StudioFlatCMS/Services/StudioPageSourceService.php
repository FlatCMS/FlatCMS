<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\StudioFlatCMS\Services;

use App\Core\I18n;
use App\Modules\Pages\Services\PageTranslationService;
use App\Modules\Settings\Services\SiteRoutingService;

final class StudioPageSourceService
{
    private PageTranslationService $pages;
    private SiteRoutingService $siteRouting;

    /** @var array<int, array<string, string>>|null */
    private ?array $cachedOptions = null;

    public function __construct(
        ?PageTranslationService $pages = null,
        ?SiteRoutingService $siteRouting = null
    ) {
        $this->pages = $pages ?? new PageTranslationService();
        $this->siteRouting = $siteRouting ?? new SiteRoutingService($this->pages);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function listPages(?string $uiLocale = null): array
    {
        if ($this->cachedOptions !== null) {
            return $this->cachedOptions;
        }

        $locale = $uiLocale ?? I18n::getLocale();
        $options = [];

        foreach ($this->pages->all() as $page) {
            $normalized = $this->pages->normalizePage($page);
            $id = trim((string) ($normalized['id'] ?? ''));
            $slug = trim((string) ($normalized['slug'] ?? ''));
            $pageLocale = trim((string) ($normalized['locale'] ?? ''));
            $translationGroup = trim((string) ($normalized['translation_group'] ?? ''));
            if ($id === '' || $pageLocale === '' || $translationGroup === '') {
                continue;
            }

            $title = trim((string) ($normalized['title'] ?? ''));
            if ($title === '') {
                $title = __('studio_flatcms_source_untitled_page', 'StudioFlatCMS');
            }

            $status = $this->pages->resolveEffectiveStatus($normalized);
            $options[] = [
                'id' => $id,
                'title' => $title,
                'slug' => $slug,
                'locale' => $pageLocale,
                'locale_label' => $this->pages->getLocaleLabel($pageLocale, $locale),
                'translation_group' => $translationGroup,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'studio_url' => $this->studioUrlForPage($normalized),
                'frontend_path' => $this->buildFrontendPath($normalized),
            ];
        }

        usort($options, static function (array $left, array $right): int {
            $leftTitle = mb_strtolower((string) ($left['title'] ?? ''), 'UTF-8');
            $rightTitle = mb_strtolower((string) ($right['title'] ?? ''), 'UTF-8');
            if ($leftTitle !== $rightTitle) {
                return $leftTitle <=> $rightTitle;
            }

            $leftLocale = (string) ($left['locale'] ?? '');
            $rightLocale = (string) ($right['locale'] ?? '');
            if ($leftLocale !== $rightLocale) {
                return $leftLocale <=> $rightLocale;
            }

            return strcmp((string) ($left['id'] ?? ''), (string) ($right['id'] ?? ''));
        });

        $this->cachedOptions = $options;

        return $this->cachedOptions;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveSelectedPage(?string $requestedId): ?array
    {
        $normalizedId = trim((string) $requestedId);
        if ($normalizedId !== '') {
            $page = $this->pages->find($normalizedId);
            if (is_array($page)) {
                return $this->decorateResolvedPage($this->pages->normalizePage($page));
            }
        }

        $preferred = $this->resolveDefaultPage();
        if (is_array($preferred)) {
            return $preferred;
        }

        $currentLocale = $this->pages->normalizeLocale(I18n::getLocale());
        if ($currentLocale !== '') {
            foreach ($this->listPages() as $option) {
                if ((string) ($option['locale'] ?? '') !== $currentLocale) {
                    continue;
                }

                $page = $this->pages->find((string) ($option['id'] ?? ''));
                if (is_array($page)) {
                    return $this->decorateResolvedPage($this->pages->normalizePage($page));
                }
            }
        }

        $first = $this->listPages()[0] ?? null;
        if (!is_array($first)) {
            return null;
        }

        $page = $this->pages->find((string) ($first['id'] ?? ''));

        return is_array($page) ? $this->decorateResolvedPage($this->pages->normalizePage($page)) : null;
    }

    /**
     * @param array<string, mixed> $page
     */
    public function buildFrontendPath(array $page): string
    {
        $locale = trim((string) ($page['locale'] ?? $this->pages->defaultLocale()), '/');
        $slug = trim((string) ($page['slug'] ?? ''));

        if ($slug === '' || $slug === 'home' || $this->siteRouting->isHomepagePage($page)) {
            return $locale !== '' ? '/' . $locale : '/';
        }

        return ($locale !== '' ? '/' . $locale : '') . '/page/' . rawurlencode($slug);
    }

    /**
     * @param array<string, mixed> $page
     */
    public function studioUrlForPage(array $page): string
    {
        return url('/admin/studio-flatcms?page=' . rawurlencode((string) ($page['id'] ?? '')));
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'published' => __('status_published', 'Pages'),
            default => __('status_draft', 'Pages'),
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveDefaultPage(): ?array
    {
        $locales = [];

        $currentLocale = $this->pages->normalizeLocale(I18n::getLocale());
        if ($currentLocale !== '') {
            $locales[] = $currentLocale;
        }

        $defaultLocale = $this->pages->defaultLocale();
        if ($defaultLocale !== '' && !in_array($defaultLocale, $locales, true)) {
            $locales[] = $defaultLocale;
        }

        foreach ($locales as $locale) {
            $homepage = $this->resolveHomepageCandidate($locale);
            if (is_array($homepage)) {
                return $homepage;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveHomepageCandidate(string $locale): ?array
    {
        $normalizedLocale = $this->pages->normalizeLocale($locale);
        if ($normalizedLocale === '') {
            return null;
        }

        $homepage = $this->siteRouting->resolveHomepagePage($normalizedLocale);
        if (is_array($homepage)) {
            return $this->decorateResolvedPage($this->pages->normalizePage($homepage));
        }

        $homePage = $this->pages->findBySlugAndLocale('home', $normalizedLocale, true);
        if (is_array($homePage)) {
            return $this->decorateResolvedPage($this->pages->normalizePage($homePage));
        }

        return null;
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private function decorateResolvedPage(array $page): array
    {
        $page['frontend_path'] = $this->buildFrontendPath($page);
        return $page;
    }
}
