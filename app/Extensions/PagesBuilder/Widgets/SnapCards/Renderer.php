<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\SnapCards;

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
        
            $resolveShadowValue = static function (string $preset): string {
                return match ($preset) {
                    'none' => 'none',
                    'soft' => '0 12px 24px rgba(15, 23, 42, 0.12)',
                    'medium' => '0 18px 36px rgba(15, 23, 42, 0.16)',
                    'strong' => '0 24px 48px rgba(15, 23, 42, 0.22)',
                    default => '',
                };
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
        
            $normalizeVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['default', 'soft', 'dark'], true) ? $value : 'soft';
            };
        
            $title = trim((string) ($settings['title'] ?? PageBuilderWidgetLocaleService::translate('SnapCards', 'snap_cards_default_title')));
            $titles = $parseRepeaterLines($settings['titles'] ?? '');
            $texts = $parseRepeaterLines($settings['texts'] ?? '');
            $backgrounds = $parseRepeaterLines($settings['backgrounds'] ?? '');
            $links = $parseRepeaterLines($settings['links'] ?? '');
            $ctaEnableds = $parseRepeaterLines($settings['ctaEnableds'] ?? '');
            $ctaLabels = $parseRepeaterLines($settings['ctaLabels'] ?? '');
            $targets = $parseRepeaterLines($settings['targets'] ?? '');
            $buttonAligns = $parseRepeaterLines($settings['buttonAligns'] ?? '');
        
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $variant = $normalizeVariant($settings['variant'] ?? 'soft');
            $mediaFullBleed = self::normalizeToggle($settings['mediaFullBleed'] ?? '', false);
            $height = max(280, min(760, (int) ($settings['height'] ?? 420)));
            $overlay = max(0, min(85, (int) ($settings['overlay'] ?? 45)));
            $overlayStrength = min(1, $overlay / 85);
            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(40, (int) ($settings['designRadius'] ?? 13)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');
            $defaultCtaLabel = trim((string) ($settings['ctaLabel'] ?? PageBuilderWidgetLocaleService::translate('SnapCards', 'snap_cards_default_cta_label')));
            $globalTarget = in_array((string) ($settings['target'] ?? '_self'), ['_self', '_blank'], true)
                ? (string) ($settings['target'] ?? '_self')
                : '_self';
            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $itemTitleStyles = [];
            $itemTextStyles = [];
        
            $maxItems = max(1, count($titles), count($texts), count($backgrounds), count($links), count($ctaEnableds), count($ctaLabels), count($targets), count($buttonAligns));
            $limit = min(12, $maxItems);
            $initialIndex = $limit > 1 ? 1 : 0;
            $itemsHtml = [];
        
            for ($i = 0; $i < $limit; $i++) {
                $itemTitle = trim((string) ($titles[$i] ?? ''));
                $itemText = trim((string) ($texts[$i] ?? ''));
                $itemBackground = $resolveImage(trim((string) ($backgrounds[$i] ?? '')));
                $itemLink = self::sanitizeUrl((string) ($links[$i] ?? ''));
                $itemEnabledRaw = trim((string) ($ctaEnableds[$i] ?? ''));
                $itemEnabled = self::normalizeToggle($itemEnabledRaw === '' ? 'on' : $itemEnabledRaw, true);
                $itemLabel = trim((string) ($ctaLabels[$i] ?? ''));
                if ($itemLabel === '') {
                    $itemLabel = $defaultCtaLabel;
                }
                if ($itemEnabled && $itemLink === '') {
                    $itemLink = '#';
                }
                $itemTargetRaw = strtolower(trim((string) ($targets[$i] ?? '')));
                if (!in_array($itemTargetRaw, ['_self', '_blank'], true)) {
                    $itemTargetRaw = $globalTarget;
                }
                $itemTarget = $itemLink !== '' ? $itemTargetRaw : '_self';
                $itemRel = $itemTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                $hasMedia = $itemBackground !== '';
                $itemTitleStyle = $resolveTextStyle($settings, 'itemTitleStyle' . ($i + 1), $align);
                $itemTextStyle = $resolveTextStyle($settings, 'itemTextStyle' . ($i + 1), $align);
                $itemButtonAlign = self::normalizeAlign((string) ($buttonAligns[$i] ?? ''), $align);
                $itemTitleStyles[$i] = $itemTitleStyle;
                $itemTextStyles[$i] = $itemTextStyle;
        
                $contentHtml = '';
                if ($itemTitle !== '') {
                    $contentHtml .= $renderStyledText($itemTitle, 'h4', 'pb-snap-card-title', $itemTitleStyle);
                }
                if ($itemText !== '') {
                    $contentHtml .= '<div class="pb-snap-card-text">' . $sanitizeRichText($itemText) . '</div>';
                }
                if ($itemEnabled && $itemLink !== '' && $itemLabel !== '') {
                    $contentHtml .= '<footer class="pb-snap-card-footer pb-snap-card-footer-align-' . $escapeAttr($itemButtonAlign) . '"><a class="btn btn-primary pb-btn pb-btn-primary pb-snap-card-link" href="' . $escapeAttr($itemLink) . '" target="' . $escapeAttr($itemTarget) . '"' . $itemRel . '>' . $escape($itemLabel) . '</a></footer>';
                }
                if ($itemTitle === '' && $itemText === '' && (!$itemEnabled || $itemLink === '') && !$hasMedia) {
                    $contentHtml .= '<div class="pb-empty">' . $escape(PageBuilderWidgetLocaleService::translate('SnapCards', 'snap_cards_empty')) . '</div>';
                }
        
                $itemsHtml[] = '<article class="pb-snap-card' . ($i === $initialIndex ? ' is-center' : '') . ($hasMedia ? ' has-media' : '') . '" data-snap-index="' . $escapeAttr((string) ($i + 1)) . '" tabindex="0">'
                    . '<div class="pb-snap-card-media">'
                    . ($hasMedia
                        ? '<img class="pb-snap-card-image" src="' . $escapeAttr($itemBackground) . '" alt="' . $escapeAttr($itemTitle !== '' ? $itemTitle : ($title !== '' ? $title : PageBuilderWidgetLocaleService::translate('SnapCards', 'snap_cards_card_alt'))) . '" loading="lazy" decoding="async">'
                        : '')
                    . '</div>'
                    . '<div class="pb-snap-card-overlay"></div>'
                    . '<div class="pb-snap-card-content">' . $contentHtml . '</div>'
                    . '</article>';
            }
        
            $controlsHtml = '';
            if ($limit > 1) {
                $controlsHtml = '<div class="pb-snap-cards-controls" data-snap-cards-controls hidden>'
                    . '<button class="pb-snap-cards-arrow pb-snap-cards-arrow-prev" type="button" data-snap-cards-prev aria-label="' . $escapeAttr(PageBuilderWidgetLocaleService::translate('SnapCards', 'snap_cards_prev_label')) . '"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>'
                    . '<button class="pb-snap-cards-arrow pb-snap-cards-arrow-next" type="button" data-snap-cards-next aria-label="' . $escapeAttr(PageBuilderWidgetLocaleService::translate('SnapCards', 'snap_cards_next_label')) . '"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>'
                    . '</div>';
            }
        
            $swipeHintHtml = '';
            if ($limit > 1) {
                $swipeHintHtml = '<div class="pb-mobile-swipe-hint" data-mobile-swipe-hint aria-hidden="true">'
                    . '<span class="pb-mobile-swipe-hint-core">'
                    . '<span class="pb-mobile-swipe-hint-trail"></span>'
                    . '<i class="fa-classic fa-solid fa-hand-pointer pb-mobile-swipe-hint-hand" aria-hidden="true"></i>'
                    . '</span>'
                    . '</div>';
            }
        
            $rootClasses = [
                'pb-snap-cards-inner',
                'pb-snap-cards-variant-' . $variant,
            ];
            if ($mediaFullBleed) {
                $rootClasses[] = 'pb-snap-cards-media-fit-cover';
            }
        
            $html = '<div class="' . $escapeAttr(implode(' ', $rootClasses)) . '" data-snap-cards="1">'
                . ($title !== '' ? $renderStyledText($title, 'strong', 'pb-snap-cards-title', $titleStyle) : '')
                . '<div class="pb-snap-cards-shell">'
                . '<div class="pb-snap-cards-track">' . implode('', $itemsHtml) . '</div>'
                . $swipeHintHtml
                . $controlsHtml
                . '</div>'
                . '</div>';
        
            $safeId = self::blockId($context);
            $cssRules = [];
            if ($safeId !== '') {
                $alignMap = [
                    'left' => 'flex-start',
                    'center' => 'center',
                    'right' => 'flex-end',
                ];
                $contentAlign = $alignMap[$align] ?? 'flex-start';
                $cssRules[] = self::blockSelector($safeId, '.pb-snap-cards-inner') . '{text-align:' . $escapeAttr($align) . ';--pb-snap-card-height:' . $height . 'px;--pb-snap-overlay-opacity:' . $escapeAttr((string) $overlayStrength) . ';}';
                $cssRules[] = self::blockSelector($safeId, '.pb-snap-card-content') . '{text-align:' . $escapeAttr($align) . ';align-items:' . $escapeAttr($contentAlign) . ';}';
                $cssRules[] = self::blockSelector($safeId, '.pb-snap-card-link') . '{align-self:' . $escapeAttr($contentAlign) . ';}';
                if ($useCustomDesign) {
                    $variantClass = 'pb-snap-cards-variant-' . $variant;
                    $cardSelectors = [
                        self::blockSelector($safeId, '.pb-snap-cards-inner .pb-snap-card'),
                        self::blockSelector($safeId, '.pb-snap-cards-inner .pb-snap-card.is-center'),
                        self::blockSelector($safeId, '.pb-snap-cards-inner .pb-snap-card.has-media'),
                        self::blockSelector($safeId, '.pb-snap-cards-inner .pb-snap-card.has-media.is-center'),
                        self::blockSelector($safeId, '.pb-snap-cards-inner.' . $variantClass . ' .pb-snap-card'),
                        self::blockSelector($safeId, '.pb-snap-cards-inner.' . $variantClass . ' .pb-snap-card.is-center'),
                        self::blockSelector($safeId, '.pb-snap-cards-inner.' . $variantClass . ' .pb-snap-card.has-media'),
                        self::blockSelector($safeId, '.pb-snap-cards-inner.' . $variantClass . ' .pb-snap-card.has-media.is-center'),
                    ];
                    $cardRules = ['border-radius:' . $escapeAttr((string) $designRadius) . 'px;'];
                    if ($designSurfaceColor !== '') {
                        $cardRules[] = 'background:' . $escapeAttr($designSurfaceColor) . ';';
                    }
                    if ($designBorderStyle !== 'inherit') {
                        $cardRules[] = 'border-style:' . $escapeAttr($designBorderStyle) . ';';
                        $cardRules[] = 'border-width:' . $escapeAttr((string) $designBorderWidth) . 'px;';
                    }
                    if ($designBorderColor !== '') {
                        $cardRules[] = 'border-color:' . $escapeAttr($designBorderColor) . ';';
                        if ($designBorderStyle === 'inherit') {
                            $cardRules[] = 'border-width:' . $escapeAttr((string) $designBorderWidth) . 'px;';
                        }
                    }
                    $shadowValue = $resolveShadowValue($designShadow);
                    if ($shadowValue !== '') {
                        $cardRules[] = 'box-shadow:' . $escapeAttr($shadowValue) . ';';
                    }
                    $cssRules[] = implode(',', $cardSelectors) . '{' . implode('', $cardRules) . '}';
                    if ($designTextColor !== '') {
                        $cssRules[] = implode(',', [
                            self::blockSelector($safeId, '.pb-snap-cards-title'),
                            self::blockSelector($safeId, '.pb-snap-card-title'),
                            self::blockSelector($safeId, '.pb-snap-card-text'),
                            self::blockSelector($safeId, '.pb-snap-card-text *'),
                        ]) . '{color:' . $escapeAttr($designTextColor) . ';}';
                    }
                }
                $cssRules = array_merge($cssRules, $buildTextStyleRules($safeId, '.pb-snap-cards-title', $titleStyle));
                for ($i = 0; $i < $limit; $i++) {
                    $cssRules = array_merge(
                        $cssRules,
                        $buildTextStyleRules(
                            $safeId,
                            '.pb-snap-card[data-snap-index="' . $escapeAttr((string) ($i + 1)) . '"] .pb-snap-card-title',
                            $itemTitleStyles[$i] ?? $resolveTextStyle($settings, 'itemTitleStyle' . ($i + 1), $align)
                        )
                    );
                    $cssRules = array_merge(
                        $cssRules,
                        $buildTextStyleRules(
                            $safeId,
                            '.pb-snap-card[data-snap-index="' . $escapeAttr((string) ($i + 1)) . '"] .pb-snap-card-text',
                            $itemTextStyles[$i] ?? $resolveTextStyle($settings, 'itemTextStyle' . ($i + 1), $align)
                        )
                    );
                }
            }
        
            return [
                'html' => $html,
                'css' => implode("\n", $cssRules),
                'assets' => [],
            ];
        };
    }
}
