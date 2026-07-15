<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Themes\Services;

use App\Core\FlatFile;

final class ThemeCustomizationService
{
    private const HEX_COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    public function assetForActiveTheme(string $type, ?array $settings = null): string
    {
        if (!in_array($type, ['admin', 'frontend'], true)) {
            return '';
        }

        $settings = $settings ?? FlatFile::settings();
        $theme = trim((string) ($settings[$type . '_theme'] ?? ($type === 'admin' ? 'admin-modern-pro' : 'default')));
        if ($theme === '') {
            return '';
        }

        return $this->assetForTheme($type, $theme);
    }

    public function assetForTheme(string $type, string $name): string
    {
        $css = $this->buildRuntimeCss($type, $name);
        if ($css === '') {
            return '';
        }

        return runtime_css_asset($css, 'theme-customization', $type . '-' . $name);
    }

    public function buildRuntimeCss(string $type, string $name): string
    {
        if (!in_array($type, ['admin', 'frontend'], true)) {
            return '';
        }

        $safeName = trim((string) preg_replace('/[^a-zA-Z0-9_-]/', '', $name));
        if ($safeName === '') {
            return '';
        }

        $customizationPath = BASE_PATH . '/data/themes/' . $type . '_' . $safeName . '.json';
        $customization = $this->readJsonFile($customizationPath);
        if ($customization === null) {
            return '';
        }

        $customColors = is_array($customization['colors'] ?? null) ? $customization['colors'] : [];
        $customLightColors = is_array($customization['light_colors'] ?? null) ? $customization['light_colors'] : [];
        $customCss = trim((string) ($customization['custom_css'] ?? ''));
        $componentCss = $this->buildComponentCustomizationCss($customization);
        if ($customColors === [] && $customLightColors === [] && $customCss === '' && $componentCss === '') {
            return '';
        }

        $themeConfig = $this->readJsonFile($this->resolveThemeConfigPath($type, $safeName)) ?? [];
        $themeColors = is_array($themeConfig['colors'] ?? null) ? $themeConfig['colors'] : [];
        $palette = $this->resolvePalette($themeColors, $customColors);

        $blocks = [];
        $rootBlock = $this->buildSelectorBlock(':root', $palette, false);
        if ($rootBlock !== '') {
            $blocks[] = $rootBlock;
        }

        $lightModeSelector = $this->resolveLightModeSelector($type, $safeName);
        if ($lightModeSelector !== '') {
            $lightPalette = $customLightColors !== []
                ? $this->resolvePalette($themeColors, $customLightColors)
                : $this->deriveLightModePalette($palette);
            $lightBlock = $this->buildSelectorBlock($lightModeSelector, $lightPalette, true);
            if ($lightBlock !== '') {
                $blocks[] = $lightBlock;
            }
        }

        if ($customCss !== '') {
            $blocks[] = $customCss;
        }
        if ($componentCss !== '') {
            $blocks[] = $componentCss;
        }

        return trim(implode("\n\n", array_filter($blocks, static fn($block): bool => trim((string) $block) !== '')));
    }

    private function buildComponentCustomizationCss(array $customization): string
    {
        $buttons = is_array($customization['buttons'] ?? null) ? $customization['buttons'] : [];
        $badges = is_array($customization['badges'] ?? null) ? $customization['badges'] : [];
        $typography = is_array($customization['typography'] ?? null) ? $customization['typography'] : [];
        $customCss = trim((string) ($customization['custom_css'] ?? ''));

        $vars = [];
        $buttonRadiusFromCustomCss = $this->resolveButtonRadiusFromCustomCss($customCss);
        if ($buttonRadiusFromCustomCss !== '') {
            $vars['--btn-radius'] = $buttonRadiusFromCustomCss;
            $vars['--fc-btn-radius'] = $buttonRadiusFromCustomCss;
        }
        $buttonRadius = $this->componentRadius((string) ($buttons['shape'] ?? 'theme'));
        if ($buttonRadius !== '') {
            $vars['--btn-radius'] = $buttonRadius;
            $vars['--fc-btn-radius'] = $buttonRadius;
        }
        $buttonWeight = $this->componentWeight((string) ($buttons['weight'] ?? 'theme'));
        if ($buttonWeight !== '') {
            $vars['--btn-font-weight'] = $buttonWeight;
            $vars['--fc-btn-font-weight'] = $buttonWeight;
        }
        $buttonStyle = $this->componentChoice((string) ($buttons['style'] ?? 'theme'), ['classic', 'soft', 'elevated']);
        if ($buttonStyle !== '') {
            $vars['--theme-button-style'] = $buttonStyle;
            foreach ($this->buttonStyleVars($buttonStyle) as $name => $value) {
                $vars[$name] = $value;
            }
        }

        $badgeRadius = $this->componentRadius((string) ($badges['shape'] ?? 'theme'));
        if ($badgeRadius !== '') {
            $vars['--theme-badge-radius'] = $badgeRadius;
        }
        $badgeWeight = $this->componentWeight((string) ($badges['weight'] ?? 'theme'));
        if ($badgeWeight !== '') {
            $vars['--theme-badge-font-weight'] = $badgeWeight;
        }
        $badgeStyle = $this->componentChoice((string) ($badges['style'] ?? 'theme'), ['soft', 'solid', 'outline']);
        if ($badgeStyle !== '') {
            $vars['--theme-badge-style'] = $badgeStyle;
        }

        $bodyFamily = $this->typographyFamily((string) ($typography['body_family'] ?? 'theme'));
        if ($bodyFamily !== '') {
            $vars['--font-family'] = $bodyFamily;
            $vars['--theme-body-font-family'] = $bodyFamily;
        }
        $headingFamily = $this->typographyFamily((string) ($typography['heading_family'] ?? 'theme'));
        if ($headingFamily !== '') {
            $vars['--theme-heading-font-family'] = $headingFamily;
        }
        $typeScale = $this->componentChoice((string) ($typography['scale'] ?? 'theme'), ['compact', 'balanced', 'comfortable']);
        if ($typeScale !== '') {
            $vars['--theme-typography-scale'] = $typeScale;
            foreach ($this->typographyScaleVars($typeScale) as $name => $value) {
                $vars[$name] = $value;
            }
        }
        $headingWeight = $this->headingWeight((string) ($typography['heading_weight'] ?? 'theme'));
        if ($headingWeight !== '') {
            $vars['--theme-heading-font-weight'] = $headingWeight;
        }

        if ($vars === []) {
            return '';
        }

        $lines = [];
        foreach ($vars as $name => $value) {
            $lines[] = '  ' . $name . ': ' . $value . ';';
        }

        $blocks = [
            ":root {\n" . implode("\n", $lines) . "\n}",
        ];

        if (
            isset($vars['--theme-body-font-family'])
            || isset($vars['--theme-body-font-size'])
            || isset($vars['--theme-body-line-height'])
        ) {
            $blocks[] = "body {\n"
                . (isset($vars['--theme-body-font-family']) ? '  font-family: var(--theme-body-font-family);' . "\n" : '')
                . (isset($vars['--theme-body-font-size']) ? '  font-size: var(--theme-body-font-size);' . "\n" : '')
                . (isset($vars['--theme-body-line-height']) ? '  line-height: var(--theme-body-line-height);' . "\n" : '')
                . "}";
        }

        if (
            isset($vars['--theme-heading-font-family'])
            || isset($vars['--theme-heading-font-weight'])
            || isset($vars['--theme-heading-line-height'])
            || isset($vars['--theme-heading-letter-spacing'])
        ) {
            $blocks[] = "h1, h2, h3, h4, h5, h6 {\n"
                . (isset($vars['--theme-heading-font-family']) ? '  font-family: var(--theme-heading-font-family);' . "\n" : '')
                . (isset($vars['--theme-heading-font-weight']) ? '  font-weight: var(--theme-heading-font-weight);' . "\n" : '')
                . (isset($vars['--theme-heading-line-height']) ? '  line-height: var(--theme-heading-line-height);' . "\n" : '')
                . (isset($vars['--theme-heading-letter-spacing']) ? '  letter-spacing: var(--theme-heading-letter-spacing);' . "\n" : '')
                . "}";
        }

        if (isset($vars['--theme-body-line-height'])) {
            $blocks[] = "p, li, dt, dd, blockquote, .prose, .page-content, .post-content {\n"
                . '  line-height: var(--theme-body-line-height);' . "\n"
                . "}";
        }

        if (
            isset($vars['--theme-badge-radius'])
            || isset($vars['--theme-badge-font-weight'])
            || $badgeStyle !== ''
        ) {
            $blocks[] = ".badge, .theme-badge, .theme-preview-badge, [class*=\"badge\"] {\n"
                . (isset($vars['--theme-badge-radius']) ? '  border-radius: var(--theme-badge-radius);' . "\n" : '')
                . (isset($vars['--theme-badge-font-weight']) ? '  font-weight: var(--theme-badge-font-weight);' . "\n" : '')
                . $this->badgeStyleDeclarations($badgeStyle)
                . "}";
        }

        return implode("\n\n", $blocks);
    }

    private function resolveButtonRadiusFromCustomCss(string $customCss): string
    {
        $css = trim($customCss);
        if ($css === '') {
            return '';
        }

        if (!preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $blocks, PREG_SET_ORDER)) {
            return '';
        }

        $radius = '';
        foreach ($blocks as $block) {
            $selectors = trim((string) ($block[1] ?? ''));
            $declarations = trim((string) ($block[2] ?? ''));
            if ($selectors === '' || $declarations === '' || !str_contains($selectors, '.btn')) {
                continue;
            }

            if (preg_match('/border-radius\s*:\s*([^;]+)\s*;/i', $declarations, $matches)) {
                $candidate = trim((string) ($matches[1] ?? ''));
                if ($candidate !== '') {
                    $radius = $candidate;
                }
            }
        }

        return $radius;
    }

    private function componentRadius(string $shape): string
    {
        return match ($shape) {
            'sharp' => '0',
            'rounded' => '0.75rem',
            'pill' => '999px',
            default => '',
        };
    }

    private function componentWeight(string $weight): string
    {
        return match ($weight) {
            'medium' => '500',
            'semibold' => '600',
            'bold' => '700',
            default => '',
        };
    }

    private function headingWeight(string $weight): string
    {
        return match ($weight) {
            'semibold' => '600',
            'bold' => '700',
            'black' => '900',
            default => '',
        };
    }

    private function typographyFamily(string $family): string
    {
        return match ($family) {
            'system' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            'sans' => '"Inter", "Segoe UI", system-ui, sans-serif',
            'geometric' => '"Space Grotesk", "Inter", system-ui, sans-serif',
            'editorial' => '"Fraunces", Georgia, serif',
            default => '',
        };
    }

    private function componentChoice(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : '';
    }

    private function buttonStyleVars(string $style): array
    {
        return match ($style) {
            'classic' => [
                '--btn-primary-shadow' => '0 4px 10px color-mix(in srgb, var(--color-primary, #2563EB) 20%, transparent)',
                '--btn-primary-shadow-hover' => '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 26%, transparent)',
                '--btn-secondary-bg' => 'var(--color-bg-secondary, var(--color-surface, #F8FAFC))',
                '--btn-secondary-bg-hover' => 'var(--color-bg-hover, #EEF2F7)',
                '--btn-secondary-bg-active' => 'color-mix(in srgb, var(--color-bg-hover, #EEF2F7) 76%, var(--color-border, #CBD5E1))',
                '--btn-secondary-color' => 'var(--color-text-primary, #111827)',
                '--btn-secondary-border' => 'var(--color-border, #CBD5E1)',
                '--btn-secondary-border-hover' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 68%, var(--color-text-primary, #111827))',
                '--btn-secondary-shadow' => '0 1px 2px rgba(15, 23, 42, 0.08)',
                '--btn-secondary-shadow-hover' => '0 8px 14px color-mix(in srgb, var(--color-primary, #2563EB) 10%, transparent)',
                '--btn-ghost-bg' => 'transparent',
                '--btn-ghost-color' => 'var(--color-text-secondary, #4B5563)',
                '--btn-ghost-border' => 'var(--color-border, #CBD5E1)',
                '--btn-ghost-bg-hover' => 'var(--color-bg-hover, #F8FAFC)',
                '--btn-ghost-color-hover' => 'var(--color-text-primary, #111827)',
                '--btn-ghost-border-hover' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 72%, var(--color-text-primary, #111827))',
                '--btn-ghost-shadow' => 'none',
                '--btn-ghost-shadow-hover' => 'none',
                '--btn-outline-bg' => 'transparent',
                '--btn-outline-color' => 'var(--color-primary, #2563EB)',
                '--btn-outline-border' => 'color-mix(in srgb, var(--color-primary, #2563EB) 38%, var(--color-border, #CBD5E1))',
                '--btn-outline-bg-hover' => 'var(--color-primary, #2563EB)',
                '--btn-outline-color-hover' => '#FFFFFF',
                '--btn-outline-border-hover' => 'var(--color-primary-dark, var(--color-primary, #2563EB))',
                '--btn-outline-shadow' => 'none',
                '--btn-outline-shadow-hover' => '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 18%, transparent)',
                '--fc-btn-primary-shadow' => '0 4px 10px color-mix(in srgb, var(--color-primary, #2563EB) 20%, transparent)',
                '--fc-btn-primary-hover-shadow' => '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 26%, transparent)',
                '--fc-btn-secondary-bg' => 'var(--color-bg-secondary, var(--color-surface, #F8FAFC))',
                '--fc-btn-secondary-hover-bg' => 'var(--color-bg-hover, #EEF2F7)',
                '--fc-btn-secondary-active-bg' => 'color-mix(in srgb, var(--color-bg-hover, #EEF2F7) 76%, var(--color-border, #CBD5E1))',
                '--fc-btn-secondary-color' => 'var(--color-text-primary, #111827)',
                '--fc-btn-secondary-border' => 'var(--color-border, #CBD5E1)',
                '--fc-btn-secondary-hover-border' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 68%, var(--color-text-primary, #111827))',
                '--fc-btn-secondary-active-border' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 68%, var(--color-text-primary, #111827))',
                '--fc-btn-secondary-shadow' => '0 1px 2px rgba(15, 23, 42, 0.08)',
                '--fc-btn-secondary-hover-shadow' => '0 8px 14px color-mix(in srgb, var(--color-primary, #2563EB) 10%, transparent)',
                '--fc-btn-ghost-bg' => 'transparent',
                '--fc-btn-ghost-color' => 'var(--color-text-secondary, #4B5563)',
                '--fc-btn-ghost-border' => 'var(--color-border, #CBD5E1)',
                '--fc-btn-ghost-hover-bg' => 'var(--color-bg-hover, #F8FAFC)',
                '--fc-btn-ghost-hover-color' => 'var(--color-text-primary, #111827)',
                '--fc-btn-ghost-hover-border' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 72%, var(--color-text-primary, #111827))',
                '--fc-btn-ghost-shadow' => 'none',
                '--fc-btn-ghost-hover-shadow' => 'none',
                '--fc-btn-outline-bg' => 'transparent',
                '--fc-btn-outline-color' => 'var(--color-primary, #2563EB)',
                '--fc-btn-outline-border' => 'color-mix(in srgb, var(--color-primary, #2563EB) 38%, var(--color-border, #CBD5E1))',
                '--fc-btn-outline-hover-bg' => 'var(--color-primary, #2563EB)',
                '--fc-btn-outline-hover-color' => '#FFFFFF',
                '--fc-btn-outline-hover-border' => 'var(--color-primary-dark, var(--color-primary, #2563EB))',
                '--fc-btn-outline-shadow' => 'none',
                '--fc-btn-outline-hover-shadow' => '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 18%, transparent)',
            ],
            'soft' => [
                '--btn-primary-shadow' => '0 6px 14px color-mix(in srgb, var(--color-primary, #2563EB) 18%, transparent)',
                '--btn-primary-shadow-hover' => '0 12px 20px color-mix(in srgb, var(--color-primary, #2563EB) 24%, transparent)',
                '--btn-secondary-bg' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 86%, var(--color-primary, #2563EB) 14%)',
                '--btn-secondary-bg-hover' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 74%, var(--color-primary, #2563EB) 26%)',
                '--btn-secondary-bg-active' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 66%, var(--color-primary, #2563EB) 34%)',
                '--btn-secondary-color' => 'var(--color-text-primary, #111827)',
                '--btn-secondary-border' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 78%, var(--color-primary, #2563EB) 22%)',
                '--btn-secondary-border-hover' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 62%, var(--color-primary, #2563EB) 38%)',
                '--btn-secondary-shadow' => '0 6px 14px color-mix(in srgb, var(--color-primary, #2563EB) 10%, transparent)',
                '--btn-secondary-shadow-hover' => '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 16%, transparent)',
                '--btn-ghost-bg' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 94%, var(--color-primary, #2563EB) 6%)',
                '--btn-ghost-color' => 'var(--color-text-secondary, #4B5563)',
                '--btn-ghost-border' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 82%, var(--color-primary, #2563EB) 18%)',
                '--btn-ghost-bg-hover' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 82%, var(--color-primary, #2563EB) 18%)',
                '--btn-ghost-color-hover' => 'var(--color-text-primary, #111827)',
                '--btn-ghost-border-hover' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 66%, var(--color-primary, #2563EB) 34%)',
                '--btn-ghost-shadow' => 'none',
                '--btn-ghost-shadow-hover' => 'none',
                '--btn-outline-bg' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 92%, var(--color-primary, #2563EB) 8%)',
                '--btn-outline-color' => 'var(--color-primary, #2563EB)',
                '--btn-outline-border' => 'color-mix(in srgb, var(--color-primary, #2563EB) 44%, var(--color-border, #CBD5E1))',
                '--btn-outline-bg-hover' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 76%, var(--color-primary, #2563EB) 24%)',
                '--btn-outline-color-hover' => 'var(--color-primary-dark, var(--color-primary, #2563EB))',
                '--btn-outline-border-hover' => 'var(--color-primary, #2563EB)',
                '--btn-outline-shadow' => 'none',
                '--btn-outline-shadow-hover' => '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 16%, transparent)',
                '--fc-btn-primary-shadow' => '0 6px 14px color-mix(in srgb, var(--color-primary, #2563EB) 18%, transparent)',
                '--fc-btn-primary-hover-shadow' => '0 12px 20px color-mix(in srgb, var(--color-primary, #2563EB) 24%, transparent)',
                '--fc-btn-secondary-bg' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 86%, var(--color-primary, #2563EB) 14%)',
                '--fc-btn-secondary-hover-bg' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 74%, var(--color-primary, #2563EB) 26%)',
                '--fc-btn-secondary-active-bg' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 66%, var(--color-primary, #2563EB) 34%)',
                '--fc-btn-secondary-color' => 'var(--color-text-primary, #111827)',
                '--fc-btn-secondary-border' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 78%, var(--color-primary, #2563EB) 22%)',
                '--fc-btn-secondary-hover-border' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 62%, var(--color-primary, #2563EB) 38%)',
                '--fc-btn-secondary-active-border' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 62%, var(--color-primary, #2563EB) 38%)',
                '--fc-btn-secondary-shadow' => '0 6px 14px color-mix(in srgb, var(--color-primary, #2563EB) 10%, transparent)',
                '--fc-btn-secondary-hover-shadow' => '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 16%, transparent)',
                '--fc-btn-ghost-bg' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 94%, var(--color-primary, #2563EB) 6%)',
                '--fc-btn-ghost-color' => 'var(--color-text-secondary, #4B5563)',
                '--fc-btn-ghost-border' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 82%, var(--color-primary, #2563EB) 18%)',
                '--fc-btn-ghost-hover-bg' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 82%, var(--color-primary, #2563EB) 18%)',
                '--fc-btn-ghost-hover-color' => 'var(--color-text-primary, #111827)',
                '--fc-btn-ghost-hover-border' => 'color-mix(in srgb, var(--color-border, #CBD5E1) 66%, var(--color-primary, #2563EB) 34%)',
                '--fc-btn-ghost-shadow' => 'none',
                '--fc-btn-ghost-hover-shadow' => 'none',
                '--fc-btn-outline-bg' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 92%, var(--color-primary, #2563EB) 8%)',
                '--fc-btn-outline-color' => 'var(--color-primary, #2563EB)',
                '--fc-btn-outline-border' => 'color-mix(in srgb, var(--color-primary, #2563EB) 44%, var(--color-border, #CBD5E1))',
                '--fc-btn-outline-hover-bg' => 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 76%, var(--color-primary, #2563EB) 24%)',
                '--fc-btn-outline-hover-color' => 'var(--color-primary-dark, var(--color-primary, #2563EB))',
                '--fc-btn-outline-hover-border' => 'var(--color-primary, #2563EB)',
                '--fc-btn-outline-shadow' => 'none',
                '--fc-btn-outline-hover-shadow' => '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 16%, transparent)',
            ],
            'elevated' => [
                '--btn-primary-shadow' => '0 14px 28px color-mix(in srgb, var(--color-primary, #2563EB) 28%, transparent)',
                '--btn-primary-shadow-hover' => '0 18px 34px color-mix(in srgb, var(--color-primary, #2563EB) 34%, transparent)',
                '--btn-secondary-shadow' => '0 12px 24px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 22%, transparent)',
                '--btn-secondary-shadow-hover' => '0 16px 30px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 28%, transparent)',
                '--btn-ghost-shadow' => '0 8px 18px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 12%, transparent)',
                '--btn-ghost-shadow-hover' => '0 12px 24px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 18%, transparent)',
                '--btn-outline-shadow' => '0 10px 22px color-mix(in srgb, var(--color-primary, #2563EB) 14%, transparent)',
                '--btn-outline-shadow-hover' => '0 14px 26px color-mix(in srgb, var(--color-primary, #2563EB) 20%, transparent)',
                '--fc-btn-primary-shadow' => '0 14px 28px color-mix(in srgb, var(--color-primary, #2563EB) 28%, transparent)',
                '--fc-btn-primary-hover-shadow' => '0 18px 34px color-mix(in srgb, var(--color-primary, #2563EB) 34%, transparent)',
                '--fc-btn-secondary-shadow' => '0 12px 24px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 22%, transparent)',
                '--fc-btn-secondary-hover-shadow' => '0 16px 30px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 28%, transparent)',
                '--fc-btn-ghost-shadow' => '0 8px 18px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 12%, transparent)',
                '--fc-btn-ghost-hover-shadow' => '0 12px 24px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 18%, transparent)',
                '--fc-btn-outline-shadow' => '0 10px 22px color-mix(in srgb, var(--color-primary, #2563EB) 14%, transparent)',
                '--fc-btn-outline-hover-shadow' => '0 14px 26px color-mix(in srgb, var(--color-primary, #2563EB) 20%, transparent)',
            ],
            default => [],
        };
    }

    private function badgeStyleDeclarations(string $style): string
    {
        return match ($style) {
            'soft' => '  background: color-mix(in srgb, var(--color-primary, #2563EB) 12%, transparent);' . "\n"
                . '  color: var(--color-text-primary, #111827);' . "\n"
                . '  border: 1px solid color-mix(in srgb, var(--color-primary, #2563EB) 18%, transparent);' . "\n",
            'solid' => '  background: var(--color-primary, #2563EB);' . "\n"
                . '  color: #FFFFFF;' . "\n"
                . '  border: 1px solid var(--color-primary-dark, var(--color-primary, #2563EB));' . "\n",
            'outline' => '  background: transparent;' . "\n"
                . '  color: var(--color-primary, #2563EB);' . "\n"
                . '  border: 1px solid color-mix(in srgb, var(--color-primary, #2563EB) 36%, var(--color-border, #CBD5E1));' . "\n",
            default => '',
        };
    }

    private function typographyScaleVars(string $scale): array
    {
        return match ($scale) {
            'compact' => [
                '--theme-body-font-size' => '0.975rem',
                '--theme-body-line-height' => '1.58',
                '--theme-heading-line-height' => '1.12',
                '--theme-heading-letter-spacing' => '-0.02em',
            ],
            'balanced' => [
                '--theme-body-font-size' => '1rem',
                '--theme-body-line-height' => '1.65',
                '--theme-heading-line-height' => '1.15',
                '--theme-heading-letter-spacing' => '-0.025em',
            ],
            'comfortable' => [
                '--theme-body-font-size' => '1.0625rem',
                '--theme-body-line-height' => '1.72',
                '--theme-heading-line-height' => '1.18',
                '--theme-heading-letter-spacing' => '-0.03em',
            ],
            default => [],
        };
    }

    private function resolveThemeConfigPath(string $type, string $name): string
    {
        $rootPath = BASE_PATH . '/themes/' . $type . '/' . $name . '/theme.json';
        if (is_file($rootPath)) {
            return $rootPath;
        }

        return BASE_PATH . '/public/themes/' . $type . '/' . $name . '/theme.json';
    }

    private function readJsonFile(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function resolvePalette(array $themeColors, array $customColors): array
    {
        $background = $this->resolveColor($customColors['background'] ?? null, $themeColors['background'] ?? null, '#FFFFFF');
        $text = $this->resolveColor($customColors['text'] ?? null, $themeColors['text'] ?? null, '#111827');
        $surfaceFallback = $this->isLightColor($background)
            ? $this->mix($background, '#0F172A', 0.03)
            : $this->mix($background, '#FFFFFF', 0.08);
        $surface = $this->resolveColor($customColors['surface'] ?? null, $themeColors['surface'] ?? null, $surfaceFallback);
        $borderFallback = $this->isLightColor($background)
            ? $this->mix($background, $text, 0.12)
            : $this->mix($surface, '#FFFFFF', 0.16);
        $border = $this->resolveColor($customColors['border'] ?? null, $themeColors['border'] ?? null, $borderFallback);
        $mutedFallback = $this->mix($text, $background, 0.55);
        $textMuted = $this->resolveColor($customColors['text_muted'] ?? null, $themeColors['text-muted'] ?? null, $mutedFallback);
        $primary = $this->resolveColor($customColors['primary'] ?? null, $themeColors['primary'] ?? null, '#2563EB');
        $secondary = $this->resolveColor($customColors['secondary'] ?? null, $themeColors['secondary'] ?? null, '#3B82F6');
        $accentFallback = $this->resolveColor($customColors['secondary'] ?? null, $themeColors['secondary'] ?? null, $secondary);
        $accent = $this->resolveColor($customColors['accent'] ?? null, $themeColors['accent'] ?? null, $accentFallback);

        $textSecondary = $this->mix($text, $textMuted, 0.54);
        $bgTertiary = $this->mix($surface, $border, 0.34);
        $bgHover = $this->mix($surface, $border, 0.5);

        return [
            'primary' => $primary,
            'primary_dark' => $this->mix($primary, '#0F172A', 0.18),
            'primary_light' => $this->mix($primary, '#FFFFFF', 0.18),
            'secondary' => $secondary,
            'secondary_dark' => $this->mix($secondary, '#0F172A', 0.16),
            'secondary_light' => $this->mix($secondary, '#FFFFFF', 0.18),
            'accent' => $accent,
            'accent_soft' => $this->mix($accent, '#FFFFFF', 0.24),
            'background' => $background,
            'surface' => $surface,
            'bg_tertiary' => $bgTertiary,
            'bg_hover' => $bgHover,
            'text' => $text,
            'text_secondary' => $textSecondary,
            'text_muted' => $textMuted,
            'border' => $border,
            'border_light' => $this->mix($border, '#FFFFFF', 0.28),
            'border_dark' => $this->mix($border, '#0F172A', 0.18),
        ];
    }

    private function deriveLightModePalette(array $palette): array
    {
        $background = $this->mix($palette['background'], '#FFFFFF', 0.92);
        $surface = $this->mix($palette['surface'], '#FFFFFF', 0.9);
        $border = $this->mix($palette['border'], '#E2E8F0', 0.68);
        $text = $this->mix($palette['text'], '#0F172A', 0.9);
        $textMuted = $this->mix($palette['text_muted'], '#64748B', 0.74);

        return [
            'primary' => $palette['primary'],
            'primary_dark' => $this->mix($palette['primary'], '#312E81', 0.22),
            'primary_light' => $this->mix($palette['primary'], '#FFFFFF', 0.22),
            'secondary' => $palette['secondary'],
            'secondary_dark' => $this->mix($palette['secondary'], '#581C87', 0.18),
            'secondary_light' => $this->mix($palette['secondary'], '#FFFFFF', 0.22),
            'accent' => $palette['accent'],
            'accent_soft' => $this->mix($palette['accent'], '#FFFFFF', 0.16),
            'background' => $background,
            'surface' => $surface,
            'bg_tertiary' => $this->mix($surface, '#E2E8F0', 0.38),
            'bg_hover' => $this->mix($surface, '#CBD5E1', 0.48),
            'text' => $text,
            'text_secondary' => $this->mix($text, '#475569', 0.48),
            'text_muted' => $textMuted,
            'border' => $border,
            'border_light' => $this->mix($border, '#FFFFFF', 0.22),
            'border_dark' => $this->mix($border, '#94A3B8', 0.32),
        ];
    }

    private function buildSelectorBlock(string $selector, array $palette, bool $lightMode): string
    {
        if ($selector === '' || $palette === []) {
            return '';
        }

        $ghostAccent = $palette['accent'];
        $ghostAccentSoft = $palette['accent_soft'];
        $buttonSecondaryBg = $lightMode
            ? $palette['surface']
            : $palette['secondary'];
        $buttonSecondaryColor = $lightMode
            ? $palette['text']
            : '#FFFFFF';
        $buttonSecondaryBorder = $lightMode
            ? $palette['border']
            : $palette['secondary'];
        $buttonSecondaryShadow = $lightMode
            ? '0 6px 12px ' . $this->rgba($palette['secondary'], 0.16)
            : '0 10px 20px -6px ' . $this->rgba($palette['secondary'], 0.45);

        $vars = [
            '--color-primary' => $palette['primary'],
            '--color-primary-dark' => $palette['primary_dark'],
            '--color-primary-light' => $palette['primary_light'],
            '--color-secondary' => $palette['secondary'],
            '--color-secondary-dark' => $palette['secondary_dark'],
            '--color-secondary-light' => $palette['secondary_light'],
            '--color-bg-primary' => $palette['background'],
            '--color-bg-secondary' => $palette['surface'],
            '--color-bg-tertiary' => $palette['bg_tertiary'],
            '--color-bg-hover' => $palette['bg_hover'],
            '--color-bg' => $palette['background'],
            '--color-surface' => $palette['surface'],
            '--color-text-primary' => $palette['text'],
            '--color-text-secondary' => $palette['text_secondary'],
            '--color-text-muted' => $palette['text_muted'],
            '--color-border' => $palette['border'],
            '--color-border-light' => $palette['border_light'],
            '--color-border-dark' => $palette['border_dark'],
            '--color-input-bg' => $palette['surface'],
            '--color-input-border' => $palette['border'],
            '--color-input-focus' => $palette['primary'],
            '--color-card-bg' => $palette['surface'],
            '--color-card-border' => $palette['border'],
            '--color-header-bg' => $palette['surface'],
            '--color-link' => $palette['primary'],
            '--color-link-hover' => $palette['primary_dark'],
            '--btn-primary-bg' => $palette['primary'],
            '--btn-primary-bg-hover' => $palette['primary_dark'],
            '--btn-primary-bg-active' => $this->mix($palette['primary'], '#0F172A', 0.28),
            '--btn-primary-color' => '#FFFFFF',
            '--btn-primary-shadow' => '0 4px 10px ' . $this->rgba($palette['primary'], $lightMode ? 0.22 : 0.3),
            '--btn-primary-shadow-hover' => '0 12px 20px ' . $this->rgba($palette['primary'], $lightMode ? 0.28 : 0.36),
            '--btn-secondary-bg' => $buttonSecondaryBg,
            '--btn-secondary-bg-hover' => $lightMode ? $palette['bg_hover'] : $palette['secondary_dark'],
            '--btn-secondary-bg-active' => $lightMode ? $this->mix($palette['bg_hover'], $palette['border'], 0.4) : $this->mix($palette['secondary_dark'], '#0F172A', 0.2),
            '--btn-secondary-color' => $buttonSecondaryColor,
            '--btn-secondary-border' => $buttonSecondaryBorder,
            '--btn-secondary-border-hover' => $lightMode ? $palette['border_dark'] : $palette['secondary_dark'],
            '--btn-secondary-shadow' => $buttonSecondaryShadow,
            '--btn-secondary-shadow-hover' => '0 14px 24px ' . $this->rgba($palette['secondary'], $lightMode ? 0.2 : 0.3),
            '--btn-ghost-bg' => $lightMode ? 'transparent' : $this->rgba($ghostAccent, 0.08),
            '--btn-ghost-color' => $lightMode ? $ghostAccent : $ghostAccentSoft,
            '--btn-ghost-border' => $this->rgba($ghostAccent, $lightMode ? 0.34 : 0.46),
            '--btn-ghost-bg-hover' => $lightMode ? $ghostAccent : $this->mix($ghostAccent, '#0F172A', 0.18),
            '--btn-ghost-color-hover' => '#FFFFFF',
            '--btn-ghost-border-hover' => $lightMode ? $ghostAccent : $ghostAccentSoft,
            '--btn-ghost-shadow' => $lightMode ? 'none' : 'inset 0 1px 0 rgba(255, 255, 255, 0.08)',
            '--btn-ghost-shadow-hover' => '0 14px 26px -12px ' . $this->rgba($ghostAccent, 0.38),
            '--btn-outline-bg' => $lightMode ? 'transparent' : $this->rgba($palette['primary'], 0.04),
            '--btn-outline-color' => $lightMode ? $palette['primary'] : $palette['primary_light'],
            '--btn-outline-border' => $lightMode
                ? $this->mix($palette['primary'], $palette['border'], 0.34)
                : $this->rgba($palette['primary'], 0.54),
            '--btn-outline-bg-hover' => $lightMode ? $palette['primary'] : $this->mix($palette['primary'], '#0F172A', 0.12),
            '--btn-outline-color-hover' => '#FFFFFF',
            '--btn-outline-border-hover' => $lightMode ? $palette['primary_dark'] : $palette['primary_light'],
            '--btn-outline-shadow' => $lightMode ? 'none' : '0 10px 22px ' . $this->rgba($palette['primary'], 0.14),
            '--btn-outline-shadow-hover' => '0 14px 26px ' . $this->rgba($palette['primary'], $lightMode ? 0.18 : 0.24),
            '--fc-btn-primary-bg' => $palette['primary'],
            '--fc-btn-primary-border' => $palette['primary_dark'],
            '--fc-btn-primary-color' => '#FFFFFF',
            '--fc-btn-primary-shadow' => '0 8px 18px ' . $this->rgba($palette['primary'], $lightMode ? 0.18 : 0.28),
            '--fc-btn-primary-hover-bg' => $palette['primary_dark'],
            '--fc-btn-primary-hover-border' => $palette['primary_dark'],
            '--fc-btn-primary-hover-color' => '#FFFFFF',
            '--fc-btn-primary-hover-shadow' => '0 14px 24px ' . $this->rgba($palette['primary'], $lightMode ? 0.24 : 0.34),
            '--fc-btn-primary-active-bg' => $this->mix($palette['primary'], '#0F172A', 0.28),
            '--fc-btn-primary-active-border' => $this->mix($palette['primary'], '#0F172A', 0.28),
            '--fc-btn-primary-active-color' => '#FFFFFF',
            '--fc-btn-secondary-bg' => $buttonSecondaryBg,
            '--fc-btn-secondary-border' => $buttonSecondaryBorder,
            '--fc-btn-secondary-color' => $buttonSecondaryColor,
            '--fc-btn-secondary-shadow' => $buttonSecondaryShadow,
            '--fc-btn-secondary-hover-bg' => $lightMode ? $palette['bg_hover'] : $palette['secondary_dark'],
            '--fc-btn-secondary-hover-border' => $lightMode ? $palette['border_dark'] : $palette['secondary_dark'],
            '--fc-btn-secondary-hover-color' => $buttonSecondaryColor,
            '--fc-btn-secondary-hover-shadow' => '0 12px 22px ' . $this->rgba($palette['secondary'], $lightMode ? 0.18 : 0.28),
            '--fc-btn-secondary-active-bg' => $lightMode ? $this->mix($palette['bg_hover'], $palette['border'], 0.4) : $this->mix($palette['secondary_dark'], '#0F172A', 0.2),
            '--fc-btn-secondary-active-border' => $lightMode ? $palette['border_dark'] : $this->mix($palette['secondary_dark'], '#0F172A', 0.2),
            '--fc-btn-secondary-active-color' => $buttonSecondaryColor,
            '--fc-btn-ghost-bg' => $lightMode ? 'transparent' : $this->rgba($ghostAccent, 0.08),
            '--fc-btn-ghost-border' => $this->rgba($ghostAccent, $lightMode ? 0.34 : 0.46),
            '--fc-btn-ghost-color' => $lightMode ? $ghostAccent : $ghostAccentSoft,
            '--fc-btn-ghost-shadow' => $lightMode ? 'none' : 'inset 0 1px 0 rgba(255, 255, 255, 0.08)',
            '--fc-btn-ghost-hover-bg' => $lightMode ? $ghostAccent : $this->mix($ghostAccent, '#0F172A', 0.18),
            '--fc-btn-ghost-hover-border' => $lightMode ? $ghostAccent : $ghostAccentSoft,
            '--fc-btn-ghost-hover-color' => '#FFFFFF',
            '--fc-btn-ghost-hover-shadow' => '0 12px 22px ' . $this->rgba($ghostAccent, 0.32),
            '--fc-btn-outline-bg' => $lightMode ? 'transparent' : $this->rgba($palette['primary'], 0.04),
            '--fc-btn-outline-border' => $lightMode
                ? $this->mix($palette['primary'], $palette['border'], 0.34)
                : $this->rgba($palette['primary'], 0.54),
            '--fc-btn-outline-color' => $lightMode ? $palette['primary'] : $palette['primary_light'],
            '--fc-btn-outline-shadow' => $lightMode ? 'none' : '0 10px 22px ' . $this->rgba($palette['primary'], 0.14),
            '--fc-btn-outline-hover-bg' => $lightMode ? $palette['primary'] : $this->mix($palette['primary'], '#0F172A', 0.12),
            '--fc-btn-outline-hover-border' => $lightMode ? $palette['primary_dark'] : $palette['primary_light'],
            '--fc-btn-outline-hover-color' => '#FFFFFF',
            '--fc-btn-outline-hover-shadow' => '0 14px 26px ' . $this->rgba($palette['primary'], $lightMode ? 0.18 : 0.24),
            '--fc-card-bg' => $palette['surface'],
            '--fc-card-border' => $this->rgba($palette['border'], 0.88),
            '--fc-card-shadow' => '0 12px 24px ' . $this->rgba($palette['primary'], $lightMode ? 0.08 : 0.12),
            '--fc-card-hover-border' => $this->mix($palette['border'], $palette['primary'], 0.24),
            '--fc-card-hover-shadow' => '0 16px 30px ' . $this->rgba($palette['primary'], $lightMode ? 0.14 : 0.18),
            '--fc-card-strong-bg' => $this->mix($palette['surface'], $palette['primary'], $lightMode ? 0.06 : 0.14),
            '--fc-card-strong-border' => $this->mix($palette['border'], $palette['primary'], 0.28),
            '--fc-card-strong-shadow' => '0 18px 36px ' . $this->rgba($palette['primary'], $lightMode ? 0.12 : 0.16),
            '--fc-card-strong-hover-border' => $this->mix($palette['border'], $palette['primary'], 0.4),
            '--fc-card-strong-hover-shadow' => '0 20px 40px ' . $this->rgba($palette['primary'], $lightMode ? 0.16 : 0.2),
        ];

        $lines = [];
        foreach ($vars as $name => $value) {
            $lines[] = '  ' . $name . ': ' . $value . ';';
        }

        return $selector . " {\n" . implode("\n", $lines) . "\n}";
    }

    private function resolveLightModeSelector(string $type, string $name): string
    {
        if ($type === 'frontend' && $name === 'modern-pro') {
            return 'body.light-mode';
        }

        if ($type === 'admin' && $name === 'admin-modern-pro') {
            return 'body.light-mode,html.theme-light-init' . ' body';
        }

        return '';
    }

    private function resolveColor(mixed $customValue, mixed $themeValue, string $fallback): string
    {
        foreach ([$customValue, $themeValue, $fallback] as $value) {
            $candidate = strtoupper(trim((string) $value));
            if (preg_match(self::HEX_COLOR_PATTERN, $candidate) === 1) {
                return $candidate;
            }
        }

        return strtoupper($fallback);
    }

    private function mix(string $from, string $to, float $ratio): string
    {
        $safeRatio = max(0.0, min(1.0, $ratio));
        [$fromR, $fromG, $fromB] = $this->hexToRgb($from);
        [$toR, $toG, $toB] = $this->hexToRgb($to);

        $r = (int) round(($fromR * (1 - $safeRatio)) + ($toR * $safeRatio));
        $g = (int) round(($fromG * (1 - $safeRatio)) + ($toG * $safeRatio));
        $b = (int) round(($fromB * (1 - $safeRatio)) + ($toB * $safeRatio));

        return $this->rgbToHex($r, $g, $b);
    }

    private function rgba(string $hex, float $alpha): string
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $safeAlpha = max(0.0, min(1.0, $alpha));
        return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . rtrim(rtrim(number_format($safeAlpha, 2, '.', ''), '0'), '.') . ')';
    }

    private function isLightColor(string $hex): bool
    {
        [$r, $g, $b] = $this->hexToRgb($hex);
        $luminance = ((0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b)) / 255;
        return $luminance >= 0.58;
    }

    private function hexToRgb(string $hex): array
    {
        $safeHex = strtoupper(trim($hex));
        if (preg_match(self::HEX_COLOR_PATTERN, $safeHex) !== 1) {
            $safeHex = '#000000';
        }

        return [
            hexdec(substr($safeHex, 1, 2)),
            hexdec(substr($safeHex, 3, 2)),
            hexdec(substr($safeHex, 5, 2)),
        ];
    }

    private function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02X%02X%02X', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }
}
