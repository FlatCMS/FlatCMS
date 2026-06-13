<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\FeatureGrid;

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
            $sanitizeRichText = $helpers['sanitize_rich_text'] ?? static fn(string $value): string => $value;
        
            $parseRepeaterLines = static function (mixed $raw): array {
                if (!is_string($raw) || $raw === '') {
                    return [];
                }
        
                $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
                $items = array_map(static fn(mixed $line): string => trim((string) $line), $lines);
                while ($items !== [] && trim((string) $items[count($items) - 1]) === '') {
                    array_pop($items);
                }
        
                return $items;
            };
        
            $parseFeatureGridTextValues = static function (mixed $raw) use ($parseRepeaterLines): array {
                $value = trim((string) $raw);
                if ($value === '') {
                    return [];
                }
        
                if (str_starts_with($value, '[')) {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        return array_map(static fn(mixed $item): string => trim((string) $item), $decoded);
                    }
                }
        
                return $parseRepeaterLines($value);
            };
        
            $resolveShadowValue = static function (string $preset): string {
                return match ($preset) {
                    'none' => 'none',
                    'soft' => '0 12px 24px rgba(15, 23, 42, 0.12)',
                    'medium' => '0 18px 36px rgba(15, 23, 42, 0.16)',
                    'strong' => '0 24px 48px rgba(15, 23, 42, 0.22)',
                    default => '',
                };
            };
        
            $normalizeButtonVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['primary', 'secondary'], true) ? $value : 'ghost';
            };
        
            $normalizeGridVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                if (in_array($value, ['subtle', 'strong', 'dashed'], true)) {
                    return $value;
                }
                return match ($value) {
                    'outline' => 'strong',
                    'soft' => 'dashed',
                    default => 'subtle',
                };
            };
        
            $normalizeTarget = static function (mixed $raw, string $fallback = '_self'): string {
                $value = trim((string) $raw);
                if (in_array($value, ['_self', '_blank'], true)) {
                    return $value;
                }
                return in_array($fallback, ['_self', '_blank'], true) ? $fallback : '_self';
            };
        
            $resolveTextStyle = static function (array $source, string $prefix, string $fallbackAlign): array {
                $keyPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'textStyle';
                $iconPosition = strtolower(trim((string) ($source[$keyPrefix . 'IconPosition'] ?? 'start')));
        
                return [
                    'align' => self::normalizeAlign((string) ($source[$keyPrefix . 'Align'] ?? ''), $fallbackAlign),
                    'font' => self::normalizeTextStyleFont((string) ($source[$keyPrefix . 'Font'] ?? 'inherit')),
                    'size' => self::normalizeTextStyleSize((string) ($source[$keyPrefix . 'Size'] ?? 'inherit')),
                    'bold' => self::normalizeToggle($source[$keyPrefix . 'Bold'] ?? false),
                    'italic' => self::normalizeToggle($source[$keyPrefix . 'Italic'] ?? false),
                    'underline' => self::normalizeToggle($source[$keyPrefix . 'Underline'] ?? false),
                    'color' => self::normalizeColor((string) ($source[$keyPrefix . 'Color'] ?? '')),
                    'list' => self::normalizeTextStyleList((string) ($source[$keyPrefix . 'List'] ?? 'none')),
                    'icon' => self::sanitizeIconClass((string) ($source[$keyPrefix . 'Icon'] ?? '')),
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
        
            $buildTextStyleRules = static function (string $safeId, string $selector, array $style) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }
        
                $scopedSelector = self::blockSelector($safeId, $selector);
                $rules = ['text-align:' . $escapeAttr(self::normalizeAlign((string) ($style['align'] ?? 'left'))) . ';'];
        
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
        
                $cssRules = [$scopedSelector . '{' . implode('', $rules) . '}'];
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
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-content{' . implode('', $contentRules) . '}';
                }
        
                $listStyle = self::normalizeTextStyleList((string) ($style['list'] ?? 'none'));
                if ($listStyle !== 'none') {
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-list-marker{display:inline-block;margin-right:0.45rem;}';
                }
        
                return $cssRules;
            };
        
            $title = trim((string) ($settings['title'] ?? ''));
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $columns = max(1, min(4, (int) ($settings['columns'] ?? 3)));
            $variant = $normalizeGridVariant($settings['variant'] ?? 'subtle');
            $showHeader = self::normalizeToggle($settings['showHeader'] ?? 'on', true);
            $showTitle = self::normalizeToggle($settings['showTitle'] ?? 'on', true);
            $showBody = self::normalizeToggle($settings['showBody'] ?? 'on', true);
            $legacyShowFooter = self::normalizeToggle($settings['showFooter'] ?? 'off', false);
            $defaultButtonLabel = trim((string) ($settings['buttonLabel'] ?? ''));
            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(40, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');
        
            $titles = $parseRepeaterLines($settings['titles'] ?? '');
            $texts = $parseFeatureGridTextValues($settings['texts'] ?? '');
            $icons = $parseRepeaterLines($settings['icons'] ?? '');
            $iconEnableds = $parseRepeaterLines($settings['iconEnableds'] ?? '');
            $iconAligns = $parseRepeaterLines($settings['iconAligns'] ?? '');
            $links = $parseRepeaterLines($settings['links'] ?? '');
            $buttonLabels = $parseRepeaterLines($settings['buttonLabels'] ?? '');
            $buttonTargets = $parseRepeaterLines($settings['buttonTargets'] ?? '');
            $buttonVariants = $parseRepeaterLines($settings['buttonVariants'] ?? '');
            $buttonAligns = $parseRepeaterLines($settings['buttonAligns'] ?? '');
            $buttonEnableds = $parseRepeaterLines($settings['buttonEnableds'] ?? '');
        
            $maxItems = max(
                1,
                count($titles),
                count($texts),
                count($icons),
                count($iconAligns),
                count($links),
                count($buttonLabels),
                count($buttonTargets),
                count($buttonVariants),
                count($buttonAligns),
                count($buttonEnableds)
            );
            $limit = min(8, $maxItems);
        
            $itemsHtml = '';
            $safeId = self::blockId($context);
            $css = [];
        
            for ($index = 0; $index < $limit; $index++) {
                $itemNumber = (string) ($index + 1);
                $itemTitle = trim((string) ($titles[$index] ?? ''));
                $itemText = trim((string) ($texts[$index] ?? ''));
                $iconClass = self::sanitizeIconClass((string) ($icons[$index] ?? ''));
                $itemIconEnabledRaw = trim((string) ($iconEnableds[$index] ?? ''));
                $itemIconEnabled = $itemIconEnabledRaw !== ''
                    ? self::normalizeToggle($itemIconEnabledRaw, false)
                    : true;
                $itemIconAlign = self::normalizeAlign((string) ($iconAligns[$index] ?? ''), $align);
                $itemLink = self::sanitizeUrl((string) ($links[$index] ?? ''));
                $itemButtonEnabledRaw = trim((string) ($buttonEnableds[$index] ?? ''));
                $itemButtonEnabled = $itemButtonEnabledRaw !== ''
                    ? self::normalizeToggle($itemButtonEnabledRaw, false)
                    : $legacyShowFooter;
                $itemButtonLabel = trim((string) ($buttonLabels[$index] ?? ''));
                if ($itemButtonLabel === '') {
                    $itemButtonLabel = $defaultButtonLabel;
                }
                $itemButtonTarget = $normalizeTarget((string) ($buttonTargets[$index] ?? ''), '_self');
                if ($itemLink === '') {
                    $itemButtonTarget = '_self';
                }
                $itemButtonRel = $itemButtonTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                $itemButtonVariant = $normalizeButtonVariant((string) ($buttonVariants[$index] ?? 'ghost'));
                $itemButtonAlign = self::normalizeAlign((string) ($buttonAligns[$index] ?? ''), $align);
                $itemTitleStyle = $resolveTextStyle($settings, 'itemTitleStyle' . $itemNumber, $align);
                $itemTextStyle = $resolveTextStyle($settings, 'itemTextStyle' . $itemNumber, $align);
        
                if ($safeId !== '') {
                    $css = array_merge(
                        $css,
                        $buildTextStyleRules(
                            $safeId,
                            '.pb-feature-item[data-feature-index="' . $escapeAttr($itemNumber) . '"] .pb-feature-item-title',
                            $itemTitleStyle
                        )
                    );
                    $css = array_merge(
                        $css,
                        $buildTextStyleRules(
                            $safeId,
                            '.pb-feature-item[data-feature-index="' . $escapeAttr($itemNumber) . '"] .pb-feature-item-text',
                            $itemTextStyle
                        )
                    );
                }
        
                $iconClasses = 'pb-feature-item-icon';
                if ($iconClass === '') {
                    $iconClasses .= ' is-empty';
                }
        
                $itemCardClass = 'pb-card ' . ($variant === 'strong' ? 'pb-card-strong' : 'pb-card-subtle');
                $itemsHtml .= '<article class="pb-feature-item ' . $escapeAttr($itemCardClass) . '" data-feature-index="' . $escapeAttr($itemNumber) . '">';
                if ($showHeader && $itemIconEnabled) {
                    $itemsHtml .= '<div class="pb-feature-item-header pb-feature-item-header-align-' . $escapeAttr($itemIconAlign) . '">';
                    $itemsHtml .= '<span class="' . $escapeAttr($iconClasses) . '"' . ($iconClass === '' ? ' aria-hidden="true"' : '') . '>';
                    if ($iconClass !== '') {
                        $itemsHtml .= '<i class="' . $escapeAttr($iconClass) . '" aria-hidden="true"></i>';
                    }
                    $itemsHtml .= '</span>';
                    $itemsHtml .= '</div>';
                }
                if ($showTitle) {
                    $itemsHtml .= $renderStyledText($itemTitle, 'h4', 'pb-feature-item-title', $itemTitleStyle);
                }
                if ($showBody) {
                    $itemsHtml .= '<div class="pb-feature-item-body">';
                    if ($itemText !== '') {
                        $itemsHtml .= '<div class="pb-feature-item-text">' . $sanitizeRichText($itemText) . '</div>';
                    }
                    $itemsHtml .= '</div>';
                }
                if ($itemButtonEnabled) {
                    $itemsHtml .= '<div class="pb-feature-item-footer pb-feature-item-footer-align-' . $escapeAttr($itemButtonAlign) . '">';
                    if ($itemButtonLabel !== '' && $itemLink !== '') {
                        $itemsHtml .= '<a class="btn btn-' . $escapeAttr($itemButtonVariant) . ' pb-btn pb-btn-' . $escapeAttr($itemButtonVariant) . ' pb-feature-item-cta" href="' . $escapeAttr($itemLink) . '" target="' . $escapeAttr($itemButtonTarget) . '"' . $itemButtonRel . '><span class="pb-feature-item-cta-label">' . $escape($itemButtonLabel) . '</span></a>';
                    } elseif ($itemButtonLabel !== '') {
                        $itemsHtml .= '<span class="btn btn-' . $escapeAttr($itemButtonVariant) . ' pb-btn pb-btn-' . $escapeAttr($itemButtonVariant) . ' pb-feature-item-cta is-static" aria-disabled="true"><span class="pb-feature-item-cta-label">' . $escape($itemButtonLabel) . '</span></span>';
                    } else {
                        $itemsHtml .= '<span class="pb-feature-item-footer-placeholder" aria-hidden="true"></span>';
                    }
                    $itemsHtml .= '</div>';
                }
                if ((!$showHeader || !$itemIconEnabled || $iconClass === '') && (!$showTitle || $itemTitle === '') && (!$showBody || $itemText === '') && (!$itemButtonEnabled || $itemButtonLabel === '')) {
                    $itemsHtml .= '<div class="pb-empty">' . $escape(PageBuilderWidgetLocaleService::translate('FeatureGrid', 'feature_grid_empty')) . '</div>';
                }
                $itemsHtml .= '</article>';
            }
        
            $html = '<div class="pb-feature-grid-inner pb-feature-grid-variant-' . $escapeAttr($variant) . '">'
                . $renderStyledText($title, 'strong', 'pb-feature-grid-title', $titleStyle)
                . '<div class="pb-feature-grid-items pb-feature-grid-cols-' . $escapeAttr((string) $columns) . '">'
                . $itemsHtml
                . '</div>'
                . '</div>';
        
            if ($safeId !== '') {
                $css[] = self::blockSelector($safeId, '.pb-feature-grid-inner') . '{text-align:' . $escapeAttr($align) . ';}';
                $css[] = self::blockSelector($safeId, '.pb-feature-grid-items') . '{grid-template-columns:repeat(' . $columns . ',minmax(0,1fr));}';
                if ($useCustomDesign) {
                    $itemSelectors = [
                        self::blockSelector($safeId, '.pb-feature-grid-inner .pb-feature-item'),
                        self::blockSelector($safeId, '.pb-feature-grid-inner .pb-feature-item:hover'),
                        self::blockSelector($safeId, '.pb-feature-grid-inner.pb-feature-grid-variant-subtle .pb-feature-item'),
                        self::blockSelector($safeId, '.pb-feature-grid-inner.pb-feature-grid-variant-subtle .pb-feature-item:hover'),
                        self::blockSelector($safeId, '.pb-feature-grid-inner.pb-feature-grid-variant-strong .pb-feature-item'),
                        self::blockSelector($safeId, '.pb-feature-grid-inner.pb-feature-grid-variant-strong .pb-feature-item:hover'),
                        self::blockSelector($safeId, '.pb-feature-grid-inner.pb-feature-grid-variant-dashed .pb-feature-item'),
                        self::blockSelector($safeId, '.pb-feature-grid-inner.pb-feature-grid-variant-dashed .pb-feature-item:hover'),
                    ];
                    $itemRules = ['border-radius:' . $escapeAttr((string) $designRadius) . 'px;'];
                    if ($designSurfaceColor !== '') {
                        $itemRules[] = 'background:' . $escapeAttr($designSurfaceColor) . ';';
                    }
                    if ($designBorderStyle !== 'inherit') {
                        $itemRules[] = 'border-style:' . $escapeAttr($designBorderStyle) . ';';
                        $itemRules[] = 'border-width:' . $escapeAttr((string) $designBorderWidth) . 'px;';
                    }
                    if ($designBorderColor !== '') {
                        $itemRules[] = 'border-color:' . $escapeAttr($designBorderColor) . ';';
                        if ($designBorderStyle === 'inherit') {
                            $itemRules[] = 'border-width:' . $escapeAttr((string) $designBorderWidth) . 'px;';
                        }
                    }
                    $shadowValue = $resolveShadowValue($designShadow);
                    if ($shadowValue !== '') {
                        $itemRules[] = 'box-shadow:' . $escapeAttr($shadowValue) . ';';
                    }
                    $css[] = implode(',', $itemSelectors) . '{' . implode('', $itemRules) . '}';
                    if ($designTextColor !== '') {
                        $css[] = implode(',', [
                            self::blockSelector($safeId, '.pb-feature-grid-title'),
                            self::blockSelector($safeId, '.pb-feature-item-title'),
                            self::blockSelector($safeId, '.pb-feature-item-text'),
                            self::blockSelector($safeId, '.pb-feature-item-text *'),
                            self::blockSelector($safeId, '.pb-feature-item-icon'),
                        ]) . '{color:' . $escapeAttr($designTextColor) . ';}';
                    }
                }
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-feature-grid-title', $titleStyle));
            }
        
            return [
                'html' => $html,
                'css' => implode("\n", $css),
            ];
        };
    }
}
