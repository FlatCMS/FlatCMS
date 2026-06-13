<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Hero;

use App\Extensions\PagesBuilder\Services\PageBuilderWidgetLocaleService;
use App\Extensions\PagesBuilder\Support\AbstractWidgetRenderer;

final class Renderer extends AbstractWidgetRenderer
{
    protected static function renderer(): callable
    {
        return static function (array $settings, array $context): array {
            $helpers = is_array($context['helpers'] ?? null) ? $context['helpers'] : [];
            $escape = $helpers['escape'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $escapeAttr = $helpers['escape_attr'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $resolveImage = $helpers['resolve_image'] ?? static fn(string $value): string => $value;

            $resolveTextStyle = static function (array $source, string $prefix, string $fallbackAlign): array {
                $keyPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'textStyle';
                $read = static function (array $settings, string $key): mixed {
                    return array_key_exists($key, $settings) ? $settings[$key] : null;
                };
                $aliasPrefix = match ($keyPrefix) {
                    'titleStyle' => 'titleTextStyle',
                    'subtitleStyle' => 'subtitleTextStyle',
                    default => '',
                };
                $readStyleValue = static function (array $settings, string $suffix) use ($keyPrefix, $aliasPrefix, $read): mixed {
                    $primary = $read($settings, $keyPrefix . $suffix);
                    if ($primary !== null && trim((string) $primary) !== '') {
                        return $primary;
                    }
                    return $aliasPrefix !== '' ? $read($settings, $aliasPrefix . $suffix) : $primary;
                };

                $iconPosition = strtolower(trim((string) ($readStyleValue($source, 'IconPosition') ?? 'start')));

                return [
                    'align' => self::normalizeAlign((string) ($readStyleValue($source, 'Align') ?? ''), $fallbackAlign),
                    'font' => self::normalizeTextStyleFont((string) ($readStyleValue($source, 'Font') ?? 'inherit')),
                    'size' => self::normalizeTextStyleSize((string) ($readStyleValue($source, 'Size') ?? 'inherit')),
                    'bold' => self::normalizeToggle($readStyleValue($source, 'Bold') ?? false),
                    'italic' => self::normalizeToggle($readStyleValue($source, 'Italic') ?? false),
                    'underline' => self::normalizeToggle($readStyleValue($source, 'Underline') ?? false),
                    'color' => self::normalizeColor((string) ($readStyleValue($source, 'Color') ?? '')),
                    'list' => self::normalizeTextStyleList((string) ($readStyleValue($source, 'List') ?? 'none')),
                    'icon' => self::sanitizeIconClass((string) ($readStyleValue($source, 'Icon') ?? '')),
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
                $listStyle = self::normalizeTextStyleList((string) ($style['list'] ?? 'none'));
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

                $content = '<span class="pb-styled-text-content">' . $escape($value) . '</span>';
                $decorated = $injectTextListMarker($injectTextIcon($content, $style), $style);

                return '<' . $tag . ' class="' . $escapeAttr($className) . '">' . $decorated . '</' . $tag . '>';
            };

            $buildTextStyleRules = static function (string $safeId, string $selector, array $style) use (
                $escapeAttr,
            ): array {
                if ($safeId === '') {
                    return [];
                }

                $scopedSelector = self::blockSelector($safeId, $selector);
                $scopedSelectors = [
                    $scopedSelector,
                    '.prose ' . $scopedSelector,
                ];
                $textAlign = self::normalizeAlign((string) ($style['align'] ?? 'left'));
                $justifySelf = match ($textAlign) {
                    'center' => 'center',
                    'right' => 'end',
                    default => 'start',
                };
                $rules = [
                    'text-align:' . $escapeAttr($textAlign) . ';',
                    'justify-self:' . $escapeAttr($justifySelf) . ';',
                ];

                $color = trim((string) ($style['color'] ?? ''));
                if ($color !== '') {
                    $rules[] = 'color:' . $escapeAttr($color) . ';';
                }

                $fontRule = self::widgetTextFontRule((string) ($style['font'] ?? 'inherit'));
                if ($fontRule !== '') {
                    $rules[] = $fontRule;
                }

                $sizeRule = self::widgetTextSizeRule((string) ($style['size'] ?? 'inherit'));
                if ($sizeRule !== '') {
                    $rules[] = $sizeRule;
                }

                $cssRules = [implode(',', $scopedSelectors) . '{' . implode('', $rules) . '}'];
                if ($color !== '') {
                    $colorRule = 'color:' . $escapeAttr($color) . ';';
                    $cssRules[] = implode(',', array_map(
                        static fn(string $item): string => $item . ' .pb-styled-text-content,' . $item . ' .pb-styled-text-icon,' . $item . ' .pb-styled-text-list-marker',
                        $scopedSelectors
                    )) . '{' . $colorRule . '}';
                }
                $contentRules = [];
                if (self::normalizeToggle($style['bold'] ?? false)) {
                    $contentRules[] = 'font-weight:700;';
                }
                if (self::normalizeToggle($style['italic'] ?? false)) {
                    $contentRules[] = 'font-style:italic;';
                }
                if (self::normalizeToggle($style['underline'] ?? false)) {
                    $contentRules[] = 'text-decoration:underline;';
                }
                if ($contentRules !== []) {
                    $cssRules[] = implode(',', array_map(
                        static fn(string $item): string => $item . ' .pb-styled-text-content',
                        $scopedSelectors
                    )) . '{' . implode('', $contentRules) . '}';
                }

                $listStyle = self::normalizeTextStyleList((string) ($style['list'] ?? 'none'));
                if ($listStyle !== 'none') {
                    $cssRules[] = implode(',', array_map(
                        static fn(string $item): string => $item . ' .pb-styled-text-content-rich ul',
                        $scopedSelectors
                    )) . '{list-style-type:' . $escapeAttr($listStyle) . ';}';
                }

                return $cssRules;
            };

            $title = trim((string) ($settings['title'] ?? ''));
            $subtitle = self::normalizeShortText((string) ($settings['subtitle'] ?? ''));
            $showPrimaryCta = self::normalizeToggle($settings['showPrimaryCta'] ?? 'on', true);
            $showSecondaryCta = self::normalizeToggle($settings['showSecondaryCta'] ?? 'on', true);
            $primaryLabel = $showPrimaryCta ? trim((string) ($settings['primaryLabel'] ?? '')) : '';
            $secondaryLabel = $showSecondaryCta ? trim((string) ($settings['secondaryLabel'] ?? '')) : '';
            $primaryUrl = self::sanitizeUrl((string) ($settings['primaryUrl'] ?? ''));
            $secondaryUrl = self::sanitizeUrl((string) ($settings['secondaryUrl'] ?? ''));
            $primaryTarget = in_array((string) ($settings['primaryTarget'] ?? '_self'), ['_self', '_blank'], true)
                ? (string) ($settings['primaryTarget'] ?? '_self')
                : '_self';
            $secondaryTarget = in_array((string) ($settings['secondaryTarget'] ?? '_self'), ['_self', '_blank'], true)
                ? (string) ($settings['secondaryTarget'] ?? '_self')
                : '_self';
            $backgroundImage = $resolveImage((string) ($settings['backgroundImage'] ?? ''));
            $height = max(260, min(760, (int) ($settings['height'] ?? 420)));
            $overlay = max(0, min(85, (int) ($settings['overlay'] ?? 35)));
            $overlayStrength = min(1, $overlay / 85);
            $legacyContentAlign = trim((string) ($settings['align'] ?? ''));
            if ($legacyContentAlign === '') {
                $legacyContentAlign = (string) ($settings['contentAlign'] ?? 'left');
            }
            $headingTag = self::normalizeHeadingTag($settings['headingTag'] ?? 'h2');
            $contentAlign = self::normalizeAlign($legacyContentAlign, 'left');
            $contentJustifyItems = match ($contentAlign) {
                'center' => 'center',
                'right' => 'end',
                default => 'start',
            };
            $actionsAlign = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $variant = strtolower(trim((string) ($settings['variant'] ?? 'soft')));
            if (!in_array($variant, ['default', 'soft', 'dark'], true)) {
                $variant = 'soft';
            }
            $mediaFit = self::normalizeMediaFit($settings['mediaFit'] ?? 'cover');
            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 0)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(40, (int) ($settings['designRadius'] ?? 12)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $contentAlign);
            $subtitleStyle = $resolveTextStyle($settings, 'subtitleStyle', $titleStyle['align'] ?? $contentAlign);

            $safeId = self::blockId($context);

            $primaryButton = '';
            if ($primaryLabel !== '') {
                if ($primaryUrl !== '') {
                    $primaryRel = $primaryTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                    $primaryButton = '<a class="btn btn-primary pb-btn pb-btn-primary" href="' . $escapeAttr($primaryUrl) . '" target="' . $escapeAttr($primaryTarget) . '"' . $primaryRel . '>' . $escape($primaryLabel) . '</a>';
                } else {
                    $primaryButton = '<span class="btn btn-primary pb-btn pb-btn-primary is-static" aria-disabled="true">' . $escape($primaryLabel) . '</span>';
                }
            }

            $secondaryButton = '';
            if ($secondaryLabel !== '') {
                if ($secondaryUrl !== '') {
                    $secondaryRel = $secondaryTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                    $secondaryButton = '<a class="btn btn-ghost pb-btn pb-btn-ghost" href="' . $escapeAttr($secondaryUrl) . '" target="' . $escapeAttr($secondaryTarget) . '"' . $secondaryRel . '>' . $escape($secondaryLabel) . '</a>';
                } else {
                    $secondaryButton = '<span class="btn btn-ghost pb-btn pb-btn-ghost is-static" aria-disabled="true">' . $escape($secondaryLabel) . '</span>';
                }
            }

            $hasMedia = trim((string) $backgroundImage) !== '';
            $mediaNode = $hasMedia
                ? '<div class="fc-hero-media"><img class="fc-hero-media-image" src="' . $escapeAttr($backgroundImage) . '" alt=""></div>'
                : '';

            $emptyState = $title === '' && $subtitle === '' && $primaryButton === '' && $secondaryButton === '' && !$hasMedia
                ? '<div class="pb-empty">' . $escape(PageBuilderWidgetLocaleService::translate('Hero', 'hero_empty')) . '</div>'
                : '';

            $html = '<div class="fc-hero-wrapper">'
                . '<section class="fc-hero fc-hero-variant-' . $escapeAttr($variant) . ' fc-hero-media-fit-' . $escapeAttr($mediaFit) . ($hasMedia ? ' fc-hero-has-media' : '') . '">'
                . $mediaNode
                . '<div class="fc-hero-overlay"></div>'
                . '<div class="fc-hero-content fc-hero-content-align-' . $escapeAttr($contentAlign) . '">'
                . $renderStyledText($title, $headingTag, 'fc-hero-title', $titleStyle)
                . $renderStyledText($subtitle, 'p', 'fc-hero-subtitle', $subtitleStyle);

            if ($primaryButton !== '' || $secondaryButton !== '') {
                $html .= '<div class="fc-hero-actions fc-hero-actions-align-' . $escapeAttr($actionsAlign) . '">'
                    . $primaryButton
                    . $secondaryButton
                    . '</div>';
            }

            $html .= $emptyState
                . '</div>'
                . '</section>'
                . '</div>';

            $css = [];
            if ($safeId !== '') {
                $css[] = self::blockSelector($safeId, '.fc-hero') . '{--fc-hero-height:' . $escapeAttr((string) $height) . 'px;--fc-hero-overlay-strength:' . $escapeAttr((string) $overlayStrength) . ';}';
                $css[] = self::blockSelector($safeId, '.fc-hero-content') . '{text-align:' . $escapeAttr($contentAlign) . ';justify-items:' . $escapeAttr($contentJustifyItems) . ';}';
                if ($useCustomDesign) {
                    $heroDesignRules = ['border-radius:' . $escapeAttr((string) $designRadius) . 'px;'];
                    if ($designSurfaceColor !== '') {
                        $heroDesignRules[] = '--fc-hero-base-bg:' . $escapeAttr($designSurfaceColor) . ';';
                        $heroDesignRules[] = '--fc-hero-media-bg:' . $escapeAttr($designSurfaceColor) . ';';
                    }
                    if ($designTextColor !== '') {
                        $heroDesignRules[] = '--fc-hero-text-color:' . $escapeAttr($designTextColor) . ';';
                        $heroDesignRules[] = '--fc-hero-subtitle-color:' . $escapeAttr($designTextColor) . ';';
                    }
                    if ($designBorderStyle !== 'inherit') {
                        $heroDesignRules[] = 'border-style:' . $escapeAttr($designBorderStyle) . ';';
                        $heroDesignRules[] = 'border-width:' . $escapeAttr((string) $designBorderWidth) . 'px;';
                    }
                    if ($designBorderColor !== '') {
                        $heroDesignRules[] = 'border-color:' . $escapeAttr($designBorderColor) . ';';
                        if ($designBorderStyle === 'inherit') {
                            $heroDesignRules[] = 'border-width:' . $escapeAttr((string) $designBorderWidth) . 'px;';
                        }
                    }
                    $shadowValue = self::shadowValue($designShadow);
                    if ($shadowValue !== '') {
                        $heroDesignRules[] = 'box-shadow:' . $escapeAttr($shadowValue) . ';';
                    }
                    $css[] = self::blockSelector($safeId, '.fc-hero') . '{' . implode('', $heroDesignRules) . '}';
                }
                $css = array_merge($css, $buildTextStyleRules($safeId, '.fc-hero-title', $titleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.fc-hero-subtitle', $subtitleStyle));
            }

            return [
                'html' => $html,
                'css' => implode("\n", $css),
            ];
        };
    }
}
