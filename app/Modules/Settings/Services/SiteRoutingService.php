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
use App\Modules\Pages\Services\PageTranslationService;

final class SiteRoutingService
{
    private const SETTINGS_KEY = 'site_routing';
    private const HOMEPAGE_MODE_NATIVE = 'native';
    private const HOMEPAGE_MODE_PAGE = 'page';

    private PageTranslationService $pages;

    public function __construct(?PageTranslationService $pages = null)
    {
        $this->pages = $pages ?? new PageTranslationService();
    }

    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        $state = FlatFile::settings(self::SETTINGS_KEY);
        return $this->normalizeState(is_array($state) ? $state : []);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function save(array $state): bool
    {
        return FlatFile::saveSettings($this->normalizeState($state), self::SETTINGS_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareHomepagePayload(string $mode, string $refGroup): array
    {
        $normalizedMode = $this->normalizeHomepageMode($mode);
        $normalizedGroup = '';

        if ($normalizedMode === self::HOMEPAGE_MODE_PAGE) {
            $normalizedGroup = trim($refGroup);
            if ($normalizedGroup !== '') {
                $sourcePage = $this->pages->resolveSourcePage($normalizedGroup);
                if (is_array($sourcePage) && $this->pages->resolveEffectiveStatus($sourcePage) === 'published') {
                    $normalizedGroup = trim((string) ($sourcePage['translation_group'] ?? ''));
                } else {
                    $normalizedGroup = '';
                }
            }

            if ($normalizedGroup === '') {
                $normalizedMode = self::HOMEPAGE_MODE_NATIVE;
            }
        }

        return [
            'homepage' => [
                'mode' => $normalizedMode,
                'ref_type' => $normalizedMode === self::HOMEPAGE_MODE_PAGE ? 'page' : '',
                'ref_group' => $normalizedGroup,
            ],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function listHomepagePageOptions(?string $uiLocale = null): array
    {
        $options = [];
        $seenGroups = [];

        foreach ($this->pages->all() as $page) {
            $translationGroup = trim((string) ($page['translation_group'] ?? ''));
            if ($translationGroup === '' || isset($seenGroups[$translationGroup])) {
                continue;
            }

            $sourcePage = $this->pages->resolveSourcePage($translationGroup);
            if (!is_array($sourcePage)) {
                continue;
            }

            if ($this->pages->resolveEffectiveStatus($sourcePage) !== 'published') {
                continue;
            }

            $id = trim((string) ($sourcePage['id'] ?? ''));
            $title = trim((string) ($sourcePage['title'] ?? ''));
            $slug = trim((string) ($sourcePage['slug'] ?? ''));
            $locale = trim((string) ($sourcePage['locale'] ?? ''));
            if ($id === '' || $title === '' || $slug === '' || $locale === '') {
                continue;
            }

            $seenGroups[$translationGroup] = true;
            $options[] = [
                'id' => $id,
                'translation_group' => $translationGroup,
                'title' => $title,
                'slug' => $slug,
                'locale' => $locale,
                'locale_label' => $this->pages->getLocaleLabel($locale, $uiLocale ?? I18n::getLocale()),
                'editor_mode' => (string) ($sourcePage['editor_mode'] ?? 'classic'),
            ];
        }

        usort($options, static function (array $left, array $right): int {
            $leftTitle = mb_strtolower((string) ($left['title'] ?? ''), 'UTF-8');
            $rightTitle = mb_strtolower((string) ($right['title'] ?? ''), 'UTF-8');
            if ($leftTitle !== $rightTitle) {
                return $leftTitle <=> $rightTitle;
            }

            return strcmp((string) ($left['translation_group'] ?? ''), (string) ($right['translation_group'] ?? ''));
        });

        return $options;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveHomepagePage(string $locale): ?array
    {
        $homepage = $this->homepageConfig();
        if (($homepage['mode'] ?? self::HOMEPAGE_MODE_NATIVE) !== self::HOMEPAGE_MODE_PAGE) {
            return null;
        }

        if (($homepage['ref_type'] ?? '') !== 'page') {
            return null;
        }

        $translationGroup = trim((string) ($homepage['ref_group'] ?? ''));
        if ($translationGroup === '') {
            return null;
        }

        $localizedPage = $this->pages->findByTranslationGroupAndLocale($translationGroup, $locale, true);
        if (is_array($localizedPage)) {
            return $localizedPage;
        }

        $sourcePage = $this->pages->resolveSourcePage($translationGroup);
        if (is_array($sourcePage) && $this->pages->resolveEffectiveStatus($sourcePage) === 'published') {
            return $sourcePage;
        }

        return null;
    }

    public function isHomepagePage(array|string|null $page): bool
    {
        $homepage = $this->homepageConfig();
        if (($homepage['mode'] ?? self::HOMEPAGE_MODE_NATIVE) !== self::HOMEPAGE_MODE_PAGE) {
            return false;
        }

        if (($homepage['ref_type'] ?? '') !== 'page') {
            return false;
        }

        $homepageGroup = trim((string) ($homepage['ref_group'] ?? ''));
        if ($homepageGroup === '') {
            return false;
        }

        $pageGroup = is_array($page)
            ? trim((string) ($page['translation_group'] ?? ''))
            : trim((string) $page);

        return $pageGroup !== '' && $pageGroup === $homepageGroup;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeState(array $state): array
    {
        $homepage = is_array($state['homepage'] ?? null) ? $state['homepage'] : [];
        $mode = $this->normalizeHomepageMode((string) ($homepage['mode'] ?? self::HOMEPAGE_MODE_NATIVE));
        $refType = $mode === self::HOMEPAGE_MODE_PAGE ? 'page' : '';
        $refGroup = $mode === self::HOMEPAGE_MODE_PAGE ? trim((string) ($homepage['ref_group'] ?? '')) : '';

        if ($mode === self::HOMEPAGE_MODE_PAGE && $refGroup !== '') {
            $sourcePage = $this->pages->resolveSourcePage($refGroup);
            if (is_array($sourcePage) && $this->pages->resolveEffectiveStatus($sourcePage) === 'published') {
                $refGroup = trim((string) ($sourcePage['translation_group'] ?? $refGroup));
            } else {
                $mode = self::HOMEPAGE_MODE_NATIVE;
                $refType = '';
                $refGroup = '';
            }
        }

        return [
            'homepage' => [
                'mode' => $mode,
                'ref_type' => $refType,
                'ref_group' => $refGroup,
            ],
            'updated_at' => trim((string) ($state['updated_at'] ?? '')),
        ];
    }

    private function normalizeHomepageMode(string $mode): string
    {
        $value = strtolower(trim($mode));
        return in_array($value, [self::HOMEPAGE_MODE_NATIVE, self::HOMEPAGE_MODE_PAGE], true)
            ? $value
            : self::HOMEPAGE_MODE_NATIVE;
    }

    /**
     * @return array<string, mixed>
     */
    private function homepageConfig(): array
    {
        $state = $this->read();
        $homepage = $state['homepage'] ?? [];
        return is_array($homepage) ? $homepage : [];
    }
}
