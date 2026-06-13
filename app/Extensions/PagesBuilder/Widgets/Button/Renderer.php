<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Button;

use App\Extensions\PagesBuilder\Support\AbstractWidgetRenderer;

final class Renderer extends AbstractWidgetRenderer
{
    protected static function renderer(): callable
    {
        return static function (array $settings, array $context): array {
            $helpers = is_array($context['helpers'] ?? null) ? $context['helpers'] : [];
            $escape = $helpers['escape'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $escapeAttr = $helpers['escape_attr'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

            if (!self::normalizeToggle($settings['showButton'] ?? 'on', true)) {
                return ['html' => '', 'css' => ''];
            }

            $label = trim((string) ($settings['label'] ?? ''));
            if ($label === '') {
                return ['html' => '', 'css' => ''];
            }

            $url = self::sanitizeUrl((string) ($settings['url'] ?? ''));
            $target = self::normalizeTarget((string) ($settings['target'] ?? '_self'), $url);
            $variant = self::normalizeVariant((string) ($settings['variant'] ?? 'primary'));
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $icon = self::sanitizeIconClass((string) ($settings['icon'] ?? ''));
            $iconPosition = self::normalizeIconPosition((string) ($settings['iconPosition'] ?? 'left'));
            $textStyle = self::resolveTextStyle($settings, 'labelStyle', $align);

            $content = '<span class="fc-widget-button__label">' . $escape($label) . '</span>';
            if ($icon !== '') {
                $iconHtml = '<i class="' . $escapeAttr($icon) . ' fc-widget-button__icon" aria-hidden="true"></i>';
                $content = $iconPosition === 'right' ? $content . $iconHtml : $iconHtml . $content;
            }

            $buttonClass = 'fc-widget-button__control btn btn-' . $escapeAttr($variant)
                . ' pb-btn pb-btn-' . $escapeAttr($variant)
                . ' fc-widget-button__control--' . $escapeAttr($variant);

            if ($url !== '') {
                $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
                $buttonHtml = '<a class="' . $buttonClass . '" href="' . $escapeAttr($url) . '" target="' . $escapeAttr($target) . '"' . $rel . '>'
                    . $content
                    . '</a>';
            } else {
                $buttonHtml = '<span class="' . $buttonClass . ' is-static" aria-disabled="true">'
                    . $content
                    . '</span>';
            }

            return [
                'html' => '<div class="fc-widget-button fc-widget-button--align-' . $escapeAttr($align) . '">' . $buttonHtml . '</div>',
                'css' => self::buildCss($settings, $context, $textStyle),
            ];
        };
    }

    private static function normalizeTarget(string $target, string $url): string
    {
        $normalized = strtolower(trim($target));
        if (in_array($normalized, ['_self', '_blank'], true)) {
            return $normalized;
        }

        if ($url !== '' && preg_match('/^(https?:)?\/\//i', $url) === 1) {
            return '_blank';
        }

        return '_self';
    }

    private static function normalizeVariant(string $variant): string
    {
        $normalized = strtolower(trim($variant));
        return in_array($normalized, ['primary', 'secondary', 'ghost'], true) ? $normalized : 'primary';
    }

    private static function normalizeIconPosition(string $position): string
    {
        $normalized = strtolower(trim($position));
        return in_array($normalized, ['left', 'right'], true) ? $normalized : 'left';
    }

    private static function resolveTextStyle(array $source, string $prefix, string $fallbackAlign): array
    {
        $keyPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'textStyle';

        return [
            'align' => self::normalizeAlign((string) ($source[$keyPrefix . 'Align'] ?? $fallbackAlign)),
            'font' => self::normalizeTextStyleFont($source[$keyPrefix . 'Font'] ?? 'inherit'),
            'size' => self::normalizeTextStyleSize($source[$keyPrefix . 'Size'] ?? 'inherit'),
            'bold' => self::normalizeToggle($source[$keyPrefix . 'Bold'] ?? false),
            'italic' => self::normalizeToggle($source[$keyPrefix . 'Italic'] ?? false),
            'underline' => self::normalizeToggle($source[$keyPrefix . 'Underline'] ?? false),
            'color' => self::normalizeColor((string) ($source[$keyPrefix . 'Color'] ?? '')),
        ];
    }

    private static function buildCss(array $settings, array $context, array $textStyle): string
    {
        $safeId = self::blockId($context);
        if ($safeId === '') {
            return '';
        }

        $css = [];

        if (self::normalizeToggle($settings['useCustomDesign'] ?? '', false)) {
            $rules = ['border-radius:' . self::normalizeInt($settings['designRadius'] ?? 12, 12, 0, 48) . 'px;'];
            $surfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            if ($surfaceColor !== '') {
                $rules[] = 'background:' . $surfaceColor . ';';
            }

            $textColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            if ($textColor !== '') {
                $rules[] = 'color:' . $textColor . ';';
            }

            $borderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $borderWidth = self::normalizeInt($settings['designBorderWidth'] ?? 1, 1, 0, 8);
            if ($borderStyle !== 'inherit') {
                $rules[] = 'border-style:' . $borderStyle . ';';
                $rules[] = 'border-width:' . $borderWidth . 'px;';
            }

            $borderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            if ($borderColor !== '') {
                $rules[] = 'border-color:' . $borderColor . ';';
                if ($borderStyle === 'inherit') {
                    $rules[] = 'border-width:' . $borderWidth . 'px;';
                }
            }

            $shadow = self::shadowValue(self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit'));
            if ($shadow !== '') {
                $rules[] = 'box-shadow:' . $shadow . ';';
            }

            $css[] = self::blockSelector($safeId, '.fc-widget-button__control') . '{' . implode('', $rules) . '}';
        }

        $css = array_merge($css, self::buildTextStyleRules($safeId, $textStyle));

        return implode('', $css);
    }

    /**
     * @return array<int, string>
     */
    private static function buildTextStyleRules(string $safeId, array $style): array
    {
        if ($safeId === '') {
            return [];
        }

        $selector = self::blockSelector($safeId, '.fc-widget-button__control');
        $rules = [];
        $fontRule = self::widgetTextFontRule((string) ($style['font'] ?? 'inherit'));
        if ($fontRule !== '') {
            $rules[] = $fontRule;
        }

        $size = (string) ($style['size'] ?? 'inherit');
        if ($size !== '' && $size !== 'inherit') {
            $rules[] = 'font-size:' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . ';';
        }

        $color = self::normalizeColor((string) ($style['color'] ?? ''));
        if ($color !== '') {
            $rules[] = 'color:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . ';';
        }

        $css = $rules !== [] ? [$selector . '{' . implode('', $rules) . '}'] : [];

        $contentRules = [];
        if (!empty($style['bold'])) {
            $contentRules[] = 'font-weight:700;';
        }
        if (!empty($style['italic'])) {
            $contentRules[] = 'font-style:italic;';
        }
        if (!empty($style['underline'])) {
            $contentRules[] = 'text-decoration:underline;';
        }

        if ($contentRules !== []) {
            $css[] = $selector . ' .fc-widget-button__label{' . implode('', $contentRules) . '}';
        }

        if ($color !== '') {
            $escapedColor = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
            $css[] = $selector . ' .fc-widget-button__label,' . $selector . ' .fc-widget-button__icon{color:' . $escapedColor . ';}';
        }

        return $css;
    }

}
