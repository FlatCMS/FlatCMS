<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\Studio\Services;

use App\Core\FlatFile;
use App\Core\I18n;
use App\Modules\Footer\Services\FooterTranslationService;
use App\Modules\Settings\Services\PromoBannerService;
use App\Modules\Settings\Services\SiteBrandingTranslationService;
use App\Modules\Settings\Services\SiteLogoService;

final class StudioStructureImportService
{
    public const MANAGED_NAVBAR_BRAND_ID = 'studio-managed-navbar-brand';
    public const MANAGED_FOOTER_LOGO_ID = 'studio-managed-footer-logo';
    public const MANAGED_FOOTER_BRAND_ID = 'studio-managed-footer-brand';
    public const MANAGED_FOOTER_COPYRIGHT_ID = 'studio-managed-footer-copyright';
    public const MANAGED_FOOTER_POWERED_ID = 'studio-managed-footer-powered';
    private const LOGO_VARIANTS = ['compact', 'banner', 'banner_framed'];

    public function buildForSource(array $sourcePage): array
    {
        $locale = $this->resolveLocale($sourcePage);
        $settings = FlatFile::settings();
        if (!is_array($settings)) {
            $settings = [];
        }

        $localizedSettings = $this->localizedSettings($settings, $locale);
        $logo = $this->logoState($localizedSettings);
        $navbar = $this->buildNavbar($localizedSettings, $logo, $locale);
        $layout = [
            'header_before' => [
                'blocks' => [],
            ],
            'header_after' => [
                'blocks' => [],
            ],
            'aside' => [
                'blocks' => [],
            ],
            'footer' => [
                'blocks' => $this->buildFooterBlocks($localizedSettings, $logo, $locale),
            ],
        ];

        $promoBlocks = $this->buildPromoBlocks($localizedSettings, $locale);
        if ($promoBlocks !== []) {
            $banner = $this->promoBanner($localizedSettings, $locale);
            $position = trim((string) ($banner['position'] ?? 'above_topbar'));
            if ($position === 'below_topbar') {
                $layout['header_after']['blocks'] = $promoBlocks;
            } elseif ($position === 'above_footer') {
                $layout['footer']['blocks'] = array_merge($promoBlocks, $layout['footer']['blocks']);
            } elseif ($position === 'below_footer') {
                $layout['footer']['blocks'] = array_merge($layout['footer']['blocks'], $promoBlocks);
            } else {
                $layout['header_before']['blocks'] = $promoBlocks;
            }
        }

        return [
            'navbar' => $navbar,
            'layout' => $layout,
        ];
    }

    private function buildNavbar(array $settings, array $logo, string $locale): array
    {
        $branding = $this->brandingState($settings, $logo);
        $brandElement = [
            'id' => self::MANAGED_NAVBAR_BRAND_ID,
            'kind' => 'brand',
            'label' => $branding['render_site_name'] ? $branding['site_name'] : '',
            'src' => $branding['logo_url'],
            'alt' => $branding['logo_alt'],
            'subtitle' => $branding['render_site_slogan'] ? $branding['site_slogan'] : '',
            'variant' => $branding['logo_variant'],
        ];

        $rows = [
            'top' => [
                'left' => [],
                'center' => [],
                'right' => [],
            ],
            'main' => [
                'left' => [$brandElement],
                'center' => [
                    [
                        'kind' => 'menu',
                        'label' => __('studio_nav_element_menu_name', 'Studio'),
                    ],
                ],
                'right' => [],
            ],
            'bottom' => [
                'left' => [],
                'center' => [],
                'right' => [],
            ],
        ];

        return [
            'settings' => [
                'mega_columns_desktop' => '5',
            ],
            'brand' => [
                'label' => $brandElement['label'],
                'subtitle' => $brandElement['subtitle'],
                'variant' => $brandElement['variant'],
            ],
            'rows' => $rows,
            'items' => $this->buildNavItems($this->mainMenuItems(), $locale),
        ];
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildNavItems(array $items, string $locale): array
    {
        $navItems = [];

        foreach (array_slice($items, 0, 16) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = $this->menuItemLabel($item, $locale);
            $url = trim((string) ($item['url'] ?? '#'));
            if ($label === '') {
                continue;
            }

            $children = is_array($item['children'] ?? null) ? $item['children'] : [];
            $columns = $this->buildMegaColumns($children, $locale);

            $navItems[] = [
                'label' => $label,
                'url' => $url !== '' ? $url : '#',
                'target' => $this->normalizeTarget((string) ($item['target'] ?? '_self')),
                'mega_menu' => [
                    'enabled' => $columns !== [],
                    'columns' => $columns,
                ],
            ];
        }

        return $navItems;
    }

    /**
     * @param array<int, mixed> $children
     * @return array<int, array<string, mixed>>
     */
    private function buildMegaColumns(array $children, string $locale): array
    {
        if ($children === []) {
            return [];
        }

        $columns = [];
        $hasNestedChildren = false;
        foreach ($children as $child) {
            if (is_array($child) && is_array($child['children'] ?? null) && $child['children'] !== []) {
                $hasNestedChildren = true;
                break;
            }
        }

        if ($hasNestedChildren) {
            foreach (array_slice($children, 0, 6) as $slot => $child) {
                if (!is_array($child)) {
                    continue;
                }

                $grandChildren = is_array($child['children'] ?? null) ? $child['children'] : [];
                $elements = $this->buildMegaLinkElements($grandChildren !== [] ? $grandChildren : [$child], $locale);
                if ($elements === []) {
                    continue;
                }

                $columns[] = [
                    'slot' => $slot,
                    'title' => $this->menuItemLabel($child, $locale),
                    'elements' => $elements,
                ];
            }

            return $columns;
        }

        $elements = $this->buildMegaLinkElements($children, $locale);
        if ($elements === []) {
            return [];
        }

        return [[
            'slot' => 0,
            'title' => '',
            'elements' => $elements,
        ]];
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, array<string, string>>
     */
    private function buildMegaLinkElements(array $items, string $locale): array
    {
        $elements = [];

        foreach (array_slice($items, 0, 12) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = $this->menuItemLabel($item, $locale);
            $url = trim((string) ($item['url'] ?? '#'));
            if ($label === '') {
                continue;
            }

            $elements[] = [
                'kind' => 'link',
                'label' => $label,
                'url' => $url !== '' ? $url : '#',
                'target' => $this->normalizeTarget((string) ($item['target'] ?? '_self')),
            ];
        }

        return $elements;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPromoBlocks(array $settings, string $locale): array
    {
        $banner = $this->promoBanner($settings, $locale);
        if (!$this->promoBannerHasVisibleContent($banner)) {
            return [];
        }

        $blocks = [];
        $text = trim((string) ($banner['text'] ?? ''));
        if ($text !== '') {
            $blocks[] = [
                'type' => 'text',
                'label' => __('studio_import_promo_text_label', 'Studio'),
                'settings' => [
                    'text' => $text,
                ],
                'items' => [],
            ];
        }

        $ctaLabel = trim((string) ($banner['cta_label'] ?? ''));
        $ctaUrl = trim((string) ($banner['cta_url'] ?? ''));
        if ($ctaLabel !== '' && $ctaUrl !== '') {
            $blocks[] = [
                'type' => 'button',
                'label' => __('studio_block_button_name', 'Studio'),
                'settings' => [
                    'text' => $ctaLabel,
                    'url' => $ctaUrl,
                ],
                'items' => [],
            ];
        }

        return $blocks;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildFooterBlocks(array $settings, array $logo, string $locale): array
    {
        $footer = $this->footerPayload($settings, $locale);
        $branding = $this->brandingState($settings, $logo);
        $blocks = [];

        $blocks[] = [
            'id' => self::MANAGED_FOOTER_LOGO_ID,
            'type' => 'image',
            'label' => __('studio_import_brand_image_label', 'Studio'),
            'settings' => [
                'src' => $branding['logo_url'],
                'alt' => $branding['logo_alt'],
                'height' => 'auto',
            ],
            'items' => [],
        ];

        $brandText = trim((string) ($footer['brand_text'] ?? ''));
        if ($branding['render_site_name'] && $brandText !== '') {
            $blocks[] = [
                'id' => self::MANAGED_FOOTER_BRAND_ID,
                'type' => 'text',
                'label' => __('studio_import_footer_brand_label', 'Studio'),
                'settings' => [
                    'text' => $brandText,
                ],
                'items' => [],
            ];
        }

        $copyrightText = trim((string) ($footer['copyright_text'] ?? ''));
        if ($copyrightText !== '') {
            $blocks[] = [
                'id' => self::MANAGED_FOOTER_COPYRIGHT_ID,
                'type' => 'text',
                'label' => __('studio_import_footer_meta_label', 'Studio'),
                'settings' => [
                    'text' => $copyrightText,
                ],
                'items' => [],
            ];
        }

        $poweredBy = is_array($footer['powered_by'] ?? null) ? $footer['powered_by'] : [];
        if ((bool) ($poweredBy['enabled'] ?? false)) {
            $poweredLabel = trim((string) ($poweredBy['label'] ?? ''));
            $poweredUrl = trim((string) ($poweredBy['url'] ?? ''));
            if ($poweredLabel !== '' && $poweredUrl !== '') {
                $blocks[] = [
                    'id' => self::MANAGED_FOOTER_POWERED_ID,
                    'type' => 'button',
                    'label' => __('studio_block_button_name', 'Studio'),
                    'settings' => [
                        'text' => $poweredLabel,
                        'url' => $poweredUrl,
                    ],
                    'items' => [],
                ];
            }
        }

        return $blocks;
    }

    /**
     * @return array<string, mixed>
     */
    private function footerPayload(array $settings, string $locale): array
    {
        $footer = FlatFile::settings('footer');
        if (!is_array($footer)) {
            $footer = [];
        }

        if (function_exists('footer_settings')) {
            $footer = footer_settings($footer, $settings);
        }

        if (class_exists(FooterTranslationService::class)) {
            $footer = (new FooterTranslationService())->resolveForLocale($footer, $settings, $locale);
        }

        $siteName = trim((string) ($settings['site_name'] ?? __('app_name', 'Core')));
        if ($siteName === '') {
            $siteName = __('app_name', 'Core');
        }

        $copyrightTemplate = trim((string) ($footer['copyright_text'] ?? ''));
        if ($copyrightTemplate === '' && function_exists('footer_default_config')) {
            $defaults = footer_default_config($settings);
            $copyrightTemplate = trim((string) ($defaults['copyright_text'] ?? ''));
        }

        $copyrightHtml = strtr($copyrightTemplate, [
            '{site_name}' => $siteName,
            '{year}' => date('Y'),
        ]);
        if (function_exists('footer_sanitize_fragment')) {
            $copyrightHtml = footer_sanitize_fragment($copyrightHtml);
        }

        return [
            'enabled' => (bool) ($footer['enabled'] ?? true),
            'brand_text' => trim((string) ($footer['brand_text'] ?? $siteName)),
            'copyright_text' => trim(strip_tags($copyrightHtml)),
            'powered_by' => [
                'enabled' => (bool) (($footer['powered_by']['enabled'] ?? true)),
                'label' => trim((string) ($footer['powered_by']['label'] ?? __('app_name', 'Core'))),
                'url' => trim((string) ($footer['powered_by']['url'] ?? 'https://flat-cms.fr')),
            ],
        ];
    }

    /**
     * @return array<string, bool|string>
     */
    private function brandingState(array $settings, array $logo): array
    {
        $siteName = trim((string) ($settings['site_name'] ?? __('app_name', 'Core')));
        if ($siteName === '') {
            $siteName = (string) config('app.name', flatcms_product_name());
        }

        $siteSlogan = trim((string) ($settings['site_slogan'] ?? ''));
        $showSiteName = !array_key_exists('site_name_enabled', $settings)
            ? true
            : ((int) ($settings['site_name_enabled'] ?? 0) === 1);
        $showSiteSlogan = !array_key_exists('site_slogan_enabled', $settings)
            ? true
            : ((int) ($settings['site_slogan_enabled'] ?? 0) === 1);
        $renderSiteName = $showSiteName && $siteName !== '';
        $renderSiteSlogan = $showSiteSlogan && $siteSlogan !== '';
        $logoVariantDefault = (!$renderSiteName && !$renderSiteSlogan) ? 'banner' : 'compact';
        $logoVariant = trim((string) ($settings['site_logo_variant'] ?? $logoVariantDefault));
        if (!in_array($logoVariant, self::LOGO_VARIANTS, true)) {
            $logoVariant = $logoVariantDefault;
        }

        return [
            'site_name' => $siteName,
            'site_slogan' => $siteSlogan,
            'show_site_name' => $showSiteName,
            'show_site_slogan' => $showSiteSlogan,
            'render_site_name' => $renderSiteName,
            'render_site_slogan' => $renderSiteSlogan,
            'logo_variant' => $logoVariant,
            'logo_url' => trim((string) ($logo['default'] ?? '')),
            'logo_alt' => $siteName,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function mainMenuItems(): array
    {
        $menus = FlatFile::settings('menus');
        if (!is_array($menus)) {
            return [];
        }

        return is_array($menus['main']['items'] ?? null) ? $menus['main']['items'] : [];
    }

    private function menuItemLabel(array $item, string $locale): string
    {
        $translations = is_array($item['translations'] ?? null) ? $item['translations'] : [];
        $label = trim((string) ($translations[$locale] ?? ''));
        if ($label !== '') {
            return $label;
        }

        $prefix = strtolower(substr($locale, 0, 2));
        if ($prefix !== '') {
            foreach ($translations as $code => $translatedLabel) {
                if (!is_string($code)) {
                    continue;
                }
                if (strtolower(substr($code, 0, 2)) === $prefix && trim((string) $translatedLabel) !== '') {
                    return trim((string) $translatedLabel);
                }
            }
        }

        return trim((string) ($item['label'] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function localizedSettings(array $settings, string $locale): array
    {
        if (!class_exists(SiteBrandingTranslationService::class)) {
            return $settings;
        }

        return (new SiteBrandingTranslationService())->resolveForLocale($settings, $locale);
    }

    /**
     * @return array<string, string>
     */
    private function logoState(array $settings): array
    {
        if (!class_exists(SiteLogoService::class)) {
            return [];
        }

        return (new SiteLogoService())->resolveLogoUrls($settings);
    }

    /**
     * @return array<string, mixed>
     */
    private function promoBanner(array $settings, string $locale): array
    {
        if (!class_exists(PromoBannerService::class)) {
            return [];
        }

        return (new PromoBannerService())->resolveForLocale($settings, $locale);
    }

    /**
     * @param array<string, mixed> $banner
     */
    private function promoBannerHasVisibleContent(array $banner): bool
    {
        if (!(bool) ($banner['enabled'] ?? false)) {
            return false;
        }

        return trim((string) ($banner['text'] ?? '')) !== ''
            || (
                trim((string) ($banner['cta_label'] ?? '')) !== ''
                && trim((string) ($banner['cta_url'] ?? '')) !== ''
            );
    }

    private function normalizeTarget(string $target): string
    {
        return trim($target) === '_blank' ? '_blank' : '_self';
    }

    private function resolveLocale(array $sourcePage): string
    {
        $locale = trim((string) ($sourcePage['locale'] ?? ''));
        if ($locale !== '') {
            return $locale;
        }

        return I18n::getLocale();
    }
}
