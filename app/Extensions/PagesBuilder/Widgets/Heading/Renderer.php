<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Heading;

use App\Extensions\PagesBuilder\Support\AbstractWidgetRenderer;

final class Renderer extends AbstractWidgetRenderer
{
    protected static function renderer(): callable
    {
        return static function (array $settings, array $context): array {
            $helpers = is_array($context['helpers'] ?? null) ? $context['helpers'] : [];
            $escape = $helpers['escape'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $escapeAttr = $helpers['escape_attr'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

            $resolveTextStyle = static function (array $source, string $prefix, string $fallbackAlign): array {
                $keyPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'textStyle';
                $iconPosition = strtolower(trim((string) ($source[$keyPrefix . 'IconPosition'] ?? 'start')));

                return [
                    'align' => self::normalizeAlign($source[$keyPrefix . 'Align'] ?? '', $fallbackAlign),
                    'font' => self::normalizeTextStyleFont($source[$keyPrefix . 'Font'] ?? 'inherit'),
                    'size' => self::normalizeTextStyleSize($source[$keyPrefix . 'Size'] ?? 'inherit'),
                    'bold' => self::normalizeToggle($source[$keyPrefix . 'Bold'] ?? false),
                    'italic' => self::normalizeToggle($source[$keyPrefix . 'Italic'] ?? false),
                    'underline' => self::normalizeToggle($source[$keyPrefix . 'Underline'] ?? false),
                    'color' => self::normalizeColor((string) ($source[$keyPrefix . 'Color'] ?? '')),
                    'list' => self::normalizeTextStyleList($source[$keyPrefix . 'List'] ?? 'none'),
                    'icon' => self::sanitizeIconClass($source[$keyPrefix . 'Icon'] ?? ''),
                    'iconPosition' => in_array($iconPosition, ['start', 'end'], true) ? $iconPosition : 'start',
                ];
            };

            $injectTextIcon = static function (string $content, array $style) use ($escapeAttr): string {
                $icon = trim((string) ($style['icon'] ?? ''));
                if ($icon === '') {
                    return $content;
                }

                $iconPosition = strtolower(trim((string) ($style['iconPosition'] ?? 'start')));
                if (!in_array($iconPosition, ['start', 'end'], true)) {
                    $iconPosition = 'start';
                }

                $iconHtml = '<i class="' . $escapeAttr($icon) . ' pb-styled-text-icon pb-styled-text-icon-' . $escapeAttr($iconPosition) . '" aria-hidden="true"></i>';
                return $iconPosition === 'end' ? $content . $iconHtml : $iconHtml . $content;
            };

            $injectTextListMarker = static function (string $content, array $style) use ($escape): string {
                $listStyle = self::normalizeTextStyleList($style['list'] ?? 'none');
                if ($listStyle === 'none') {
                    return $content;
                }

                $glyph = match ($listStyle) {
                    'circle' => '∘',
                    'square' => '▪',
                    default => '•',
                };

                return '<span class="pb-styled-text-list-marker pb-styled-text-list-marker-' . $escape($listStyle) . '" aria-hidden="true">'
                    . $escape($glyph)
                    . '</span>' . $content;
            };

            $renderStyledText = static function (string $text, string $tag, string $className, array $style) use (
                $escape,
                $escapeAttr,
                $injectTextIcon,
                $injectTextListMarker
            ): string {
                $value = trim($text);
                if ($value === '') {
                    return '';
                }

                $content = $escape($value);
                $decorated = $injectTextListMarker($injectTextIcon($content, $style), $style);

                return '<' . $tag . ' class="' . $escapeAttr($className) . '">' . $decorated . '</' . $tag . '>';
            };

            $buildTextStyleRules = static function (string $safeId, string $selector, array $style) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $scopedSelector = self::blockSelector($safeId, $selector);
                $rules = ['text-align:' . $escapeAttr(self::normalizeAlign($style['align'] ?? 'left')) . ';'];

                $color = trim((string) ($style['color'] ?? ''));
                if ($color !== '') {
                    $rules[] = 'color:' . $escapeAttr($color) . ';';
                }

                $fontRule = self::widgetTextFontRule((string) ($style['font'] ?? 'inherit'));
                if ($fontRule !== '') {
                    $rules[] = $fontRule;
                }

                if (!empty($style['bold'])) {
                    $rules[] = 'font-weight:700;';
                }

                if (!empty($style['italic'])) {
                    $rules[] = 'font-style:italic;';
                }

                if (!empty($style['underline'])) {
                    $rules[] = 'text-decoration:underline;';
                }

                $css = [];
                if ($rules !== []) {
                    $css[] = $scopedSelector . '{' . implode('', $rules) . '}';
                }

                $listStyle = self::normalizeTextStyleList($style['list'] ?? 'none');
                if ($listStyle !== 'none') {
                    $css[] = $scopedSelector . ' .pb-styled-text-list-marker{display:inline-flex;align-items:center;margin-right:.45rem;}';
                }

                return $css;
            };

            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(48, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $text = trim((string) ($settings['text'] ?? ''));
            if ($text === '') {
                return [
                    'html' => '',
                    'css' => '',
                ];
            }

            $tag = self::normalizeHeadingTag($settings['tag'] ?? 'h2');
            $align = self::normalizeAlign($settings['align'] ?? 'left');
            $color = self::normalizeColor((string) ($settings['color'] ?? ''));
            $style = $resolveTextStyle($settings, 'headingStyle', $align);
            if ($style['color'] === '' && $color !== '') {
                $style['color'] = $color;
            }

            $html = '<div class="pb-heading pb-heading-align-' . $escapeAttr($style['align']) . '">'
                . $renderStyledText($text, $tag, 'pb-heading-inner pb-heading-tag-' . $tag, $style)
                . '</div>';

            $safeId = self::blockId($context);

            $cssRules = self::buildWidgetDesignRules(
                $safeId,
                ['.pb-heading'],
                ['.pb-heading-inner'],
                $useCustomDesign,
                $designSurfaceColor,
                $designTextColor,
                $designBorderStyle,
                $designBorderWidth,
                $designBorderColor,
                $designRadius,
                $designShadow
            );
            $cssRules = array_merge($cssRules, $buildTextStyleRules($safeId, '.pb-heading-inner', $style));

            return [
                'html' => $html,
                'css' => implode('', $cssRules),
            ];
        };
    }
}
