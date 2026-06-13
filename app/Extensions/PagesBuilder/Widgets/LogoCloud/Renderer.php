<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\LogoCloud;

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
            $translate = static fn(string $key, string $fallback = ''): string => PageBuilderWidgetLocaleService::translate('LogoCloud', $key, $fallback);

            $parseRepeaterLines = static function (mixed $raw): array {
                if (!is_string($raw) || trim($raw) === '') {
                    return [];
                }

                $items = preg_split('/\r\n|\r|\n/', $raw) ?: [];
                $items = array_map(static fn(mixed $item): string => trim((string) $item), $items);
                while ($items !== [] && trim((string) $items[count($items) - 1]) === '') {
                    array_pop($items);
                }

                return $items;
            };

            $normalizeVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['subtle', 'strong', 'ghost'], true) ? $value : 'subtle';
            };

            $normalizePresentationModel = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['classic', 'cloud4', 'cloud6', 'cloud7'], true) ? $value : 'classic';
            };

            $normalizeColumns = static function (mixed $raw): int {
                $value = (int) $raw;
                return max(2, min(6, $value > 0 ? $value : 4));
            };

            $normalizeLogoHeight = static function (mixed $raw): int {
                $value = (int) $raw;
                return max(40, min(160, $value > 0 ? $value : 72));
            };

            $normalizeWidgetHeight = static function (mixed $raw): int {
                $value = (int) $raw;
                return max(220, min(760, $value > 0 ? $value : 280));
            };

            $normalizeGap = static function (mixed $raw): int {
                $value = (int) $raw;
                return max(8, min(48, $value > 0 ? $value : 20));
            };

            $normalizeAnimationSpeed = static function (mixed $raw): int {
                $value = (int) $raw;
                return max(12, min(60, $value > 0 ? $value : 28));
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

            $buildTextStyleRules = static function (string $safeId, string $selector, array $style) use (
                $escapeAttr,
            ): array {
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

            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(48, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $title = trim((string) ($settings['title'] ?? $translate('logo_cloud_default_title')));
            $subtitle = trim((string) ($settings['subtitle'] ?? $translate('logo_cloud_default_subtitle')));
            $labels = $parseRepeaterLines($settings['labels'] ?? $translate('logo_cloud_default_labels'));
            $logos = $parseRepeaterLines($settings['logos'] ?? '');
            $links = $parseRepeaterLines($settings['links'] ?? '');
            $targets = $parseRepeaterLines($settings['targets'] ?? '');
            $showHeader = self::normalizeToggle($settings['showHeader'] ?? 'on', true);
            $showLabels = self::normalizeToggle($settings['showLabels'] ?? '', false);
            $presentationModel = $normalizePresentationModel($settings['presentationModel'] ?? 'classic');
            $columns = $normalizeColumns($settings['columns'] ?? 4);
            $animationSpeed = $normalizeAnimationSpeed($settings['animationSpeed'] ?? 28);
            $logoHeight = $normalizeLogoHeight($settings['logoHeight'] ?? 72);
            $widgetHeight = $normalizeWidgetHeight($settings['widgetHeight'] ?? 280);
            $gap = $normalizeGap($settings['gap'] ?? 20);
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'center'), 'center');
            $variant = $normalizeVariant($settings['variant'] ?? 'subtle');
            $grayscale = self::normalizeToggle($settings['grayscale'] ?? 'on', true);
            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $subtitleStyle = $resolveTextStyle($settings, 'subtitleStyle', $align);
            $labelStyle = $resolveTextStyle($settings, 'labelStyle', $align);
            $count = min(13, max(count($labels), count($logos), count($links), count($targets), 1));
            $items = [];

            for ($index = 0; $index < $count; $index++) {
                $label = trim((string) ($labels[$index] ?? ''));
                $logo = $resolveImage(trim((string) ($logos[$index] ?? '')));
                $link = self::sanitizeUrl((string) ($links[$index] ?? ''));
                $target = $normalizeTarget($targets[$index] ?? '_self', '_self');

                if ($label === '' && $logo === '') {
                    continue;
                }

                $items[] = [
                    'label' => $label,
                    'logo' => $logo,
                    'link' => $link,
                    'target' => $target,
                ];
            }

            $buildSurfaceClasses = static function (string $variantValue, array $baseClasses): array {
                $classes = $baseClasses;
                $classes[] = 'pb-logo-cloud-surface';
                if ($variantValue === 'strong') {
                    $classes[] = 'pb-card';
                    $classes[] = 'pb-card-strong';
                } elseif ($variantValue === 'subtle') {
                    $classes[] = 'pb-card';
                    $classes[] = 'pb-card-subtle';
                } else {
                    $classes[] = 'pb-logo-cloud-surface-ghost';
                }

                return $classes;
            };

            $buildInteractiveWrapper = static function (array $item, string $className, string $innerHtml) use ($escapeAttr): string {
                $attributes = ' class="' . $escapeAttr($className) . '"';
                if (($item['link'] ?? '') !== '') {
                    $targetValue = (string) ($item['target'] ?? '_self');
                    $attributes .= ' href="' . $escapeAttr((string) $item['link']) . '" target="' . $escapeAttr($targetValue) . '"';
                    if ($targetValue === '_blank') {
                        $attributes .= ' rel="noopener noreferrer"';
                    }

                    return '<a' . $attributes . '>' . $innerHtml . '</a>';
                }

                return '<div' . $attributes . '>' . $innerHtml . '</div>';
            };

            $renderMediaHtml = static function (array $item) use ($escape, $escapeAttr, $translate): string {
                $label = trim((string) ($item['label'] ?? ''));
                $logo = trim((string) ($item['logo'] ?? ''));
                if ($logo !== '') {
                    return '<img class="pb-logo-cloud-image" src="' . $escapeAttr($logo) . '" alt="' . $escapeAttr($label !== '' ? $label : $translate('logo_cloud_logo_alt')) . '" loading="lazy" decoding="async">';
                }

                return '<span class="pb-logo-cloud-fallback">' . $escape($label) . '</span>';
            };

            $expandSequence = static function (array $source, int $minimumItems): array {
                if ($source === []) {
                    return [];
                }

                $expanded = $source;
                while (count($expanded) < $minimumItems) {
                    $expanded = array_merge($expanded, $source);
                }

                return $expanded;
            };

            $rotateItems = static function (array $source, int $offset): array {
                $count = count($source);
                if ($count <= 1) {
                    return $source;
                }

                $safeOffset = $offset % $count;
                if ($safeOffset === 0) {
                    return $source;
                }

                return array_merge(
                    array_slice($source, $safeOffset),
                    array_slice($source, 0, $safeOffset)
                );
            };

            $renderGridItem = static function (array $item) use (
                $buildInteractiveWrapper,
                $buildSurfaceClasses,
                $renderMediaHtml,
                $renderStyledText,
                $showLabels,
                $labelStyle,
                $variant,
                $escapeAttr
            ): string {
                $surfaceClasses = $buildSurfaceClasses($variant, ['pb-logo-cloud-item']);
                $bodyHtml = '<div class="pb-logo-cloud-media">' . $renderMediaHtml($item) . '</div>';
                if ($showLabels && trim((string) ($item['label'] ?? '')) !== '') {
                    $bodyHtml .= $renderStyledText((string) $item['label'], 'p', 'pb-logo-cloud-label', $labelStyle);
                }

                return '<article class="' . $escapeAttr(implode(' ', $surfaceClasses)) . '">'
                    . $buildInteractiveWrapper($item, 'pb-logo-cloud-item-shell', $bodyHtml)
                    . '</article>';
            };

            $renderMarqueeItem = static function (array $item) use (
                $buildInteractiveWrapper,
                $buildSurfaceClasses,
                $renderMediaHtml,
                $renderStyledText,
                $showLabels,
                $labelStyle,
                $variant,
                $escapeAttr
            ): string {
                $surfaceClasses = $buildSurfaceClasses($variant, ['pb-logo-cloud-marquee-item']);
                $bodyHtml = '<div class="pb-logo-cloud-media">' . $renderMediaHtml($item) . '</div>';
                if ($showLabels && trim((string) ($item['label'] ?? '')) !== '') {
                    $bodyHtml .= $renderStyledText((string) $item['label'], 'p', 'pb-logo-cloud-label', $labelStyle);
                }

                return '<article class="' . $escapeAttr(implode(' ', $surfaceClasses)) . '">'
                    . $buildInteractiveWrapper($item, 'pb-logo-cloud-marquee-shell', $bodyHtml)
                    . '</article>';
            };

            $renderClassicTapeItem = static function (array $item) use (
                $buildInteractiveWrapper,
                $renderMediaHtml,
                $escapeAttr
            ): string {
                $bodyHtml = '<div class="pb-logo-cloud-media pb-logo-cloud-media-tape">' . $renderMediaHtml($item) . '</div>';

                return '<article class="pb-logo-cloud-classic-item pb-logo-cloud-surface">'
                    . $buildInteractiveWrapper($item, 'pb-logo-cloud-classic-shell', $bodyHtml)
                    . '</article>';
            };

            $renderColumnItem = static function (array $item) use (
                $buildInteractiveWrapper,
                $buildSurfaceClasses,
                $renderMediaHtml,
                $renderStyledText,
                $showLabels,
                $labelStyle,
                $variant,
                $escapeAttr
            ): string {
                $badgeClasses = $buildSurfaceClasses($variant, ['pb-logo-cloud-column-badge']);
                $bodyHtml = '<div class="' . $escapeAttr(implode(' ', $badgeClasses)) . '"><div class="pb-logo-cloud-media pb-logo-cloud-media-circle">' . $renderMediaHtml($item) . '</div></div>';
                if ($showLabels && trim((string) ($item['label'] ?? '')) !== '') {
                    $bodyHtml .= $renderStyledText((string) $item['label'], 'p', 'pb-logo-cloud-label pb-logo-cloud-column-label', $labelStyle);
                }

                return '<article class="pb-logo-cloud-column-item">'
                    . $buildInteractiveWrapper($item, 'pb-logo-cloud-column-link', $bodyHtml)
                    . '</article>';
            };

            $renderOrbitItem = static function (
                array $item,
                string $articleClass,
                array $surfaceBaseClasses,
                string $wrapperClass,
                string $labelClass
            ) use (
                $buildInteractiveWrapper,
                $renderMediaHtml,
                $renderStyledText,
                $showLabels,
                $labelStyle,
                $escapeAttr
            ): string {
                $bodyHtml = '<div class="' . $escapeAttr(implode(' ', $surfaceBaseClasses)) . '">'
                    . '<div class="pb-logo-cloud-media pb-logo-cloud-media-orbit">' . $renderMediaHtml($item) . '</div>';
                if ($showLabels && trim((string) ($item['label'] ?? '')) !== '') {
                    $bodyHtml .= $renderStyledText((string) $item['label'], 'p', $labelClass, $labelStyle);
                }
                $bodyHtml .= '</div>';

                return '<article class="' . $escapeAttr($articleClass) . '">'
                    . $buildInteractiveWrapper($item, $wrapperClass, $bodyHtml)
                    . '</article>';
            };

            $headerHtml = '';
            if ($showHeader && ($title !== '' || $subtitle !== '')) {
                $headerHtml .= '<header class="pb-logo-cloud-header">';
                if ($title !== '') {
                    $headerHtml .= $renderStyledText($title, 'h2', 'pb-logo-cloud-title', $titleStyle);
                }
                if ($subtitle !== '') {
                    $headerHtml .= $renderStyledText($subtitle, 'p', 'pb-logo-cloud-subtitle', $subtitleStyle);
                }
                $headerHtml .= '</header>';
            }

            $columnCount = max(1, min(6, $columns));
            $contentHtml = '';

            if ($items === []) {
                $contentHtml = '<div class="pb-empty">' . $escape($translate('logo_cloud_empty')) . '</div>';
            } elseif ($presentationModel === 'classic') {
                $sequence = $expandSequence($items, max(8, count($items) * 2));
                $trackHtml = '';
                foreach (array_merge($sequence, $sequence) as $item) {
                    $trackHtml .= $renderClassicTapeItem($item);
                }

                $contentHtml = '<div class="pb-logo-cloud-classic-row"><div class="pb-logo-cloud-classic-track">' . $trackHtml . '</div></div>';
            } elseif ($presentationModel === 'cloud4') {
                $firstRow = [];
                $secondRow = [];
                foreach ($items as $index => $item) {
                    if ($index % 2 === 0) {
                        $firstRow[] = $item;
                    } else {
                        $secondRow[] = $item;
                    }
                }
                if ($firstRow === []) {
                    $firstRow = $items;
                }
                if ($secondRow === []) {
                    $secondRow = $items;
                }

                $renderMarqueeRow = static function (array $rowItems, string $directionClass) use ($expandSequence, $renderMarqueeItem): string {
                    $sequence = $expandSequence($rowItems, max(6, count($rowItems) * 2));
                    if ($sequence === []) {
                        return '';
                    }

                    $trackHtml = '';
                    foreach (array_merge($sequence, $sequence) as $item) {
                        $trackHtml .= $renderMarqueeItem($item);
                    }

                    return '<div class="pb-logo-cloud-marquee-row ' . $directionClass . '"><div class="pb-logo-cloud-marquee-track">' . $trackHtml . '</div></div>';
                };

                $contentHtml = '<div class="pb-logo-cloud-marquee">'
                    . $renderMarqueeRow($firstRow, 'is-forward')
                    . $renderMarqueeRow($secondRow, 'is-reverse')
                    . '</div>';
            } elseif ($presentationModel === 'cloud6') {
                $buildColumnSequence = static function (array $sourceItems, int $columnIndex) use ($expandSequence, $rotateItems): array {
                    if ($sourceItems === []) {
                        return [];
                    }

                    $count = count($sourceItems);
                    $offset = $count > 1 ? (($columnIndex * 2) % $count) : 0;
                    $ordered = $rotateItems($sourceItems, $offset);
                    if ($columnIndex % 2 === 1) {
                        $ordered = array_reverse($ordered);
                    }

                    return $expandSequence($ordered, max(6, $count * 2));
                };

                $renderVerticalColumn = static function (array $sequence, string $directionClass) use ($renderColumnItem): string {
                    if ($sequence === []) {
                        return '';
                    }

                    $trackHtml = '';
                    foreach (array_merge($sequence, $sequence) as $item) {
                        $trackHtml .= $renderColumnItem($item);
                    }

                    return '<div class="pb-logo-cloud-column"><div class="pb-logo-cloud-vtrack ' . $directionClass . '">' . $trackHtml . '</div></div>';
                };

                $columnsHtml = '';
                for ($index = 0; $index < $columnCount; $index++) {
                    $columnsHtml .= $renderVerticalColumn(
                        $buildColumnSequence($items, $index),
                        $index % 2 === 0 ? 'is-down' : 'is-up'
                    );
                }

                $contentHtml = '<div class="pb-logo-cloud-columns">' . $columnsHtml . '</div>';
            } elseif ($presentationModel === 'cloud7') {
                $centerItem = $items[0];
                $satelliteItems = array_slice($items, 1, 12);
                $hasThirdRing = count($satelliteItems) > 8;
                $orbitHtml = $renderOrbitItem(
                    $centerItem,
                    'pb-logo-cloud-orbit-core',
                    ['pb-logo-cloud-orbit-core-surface', 'pb-logo-cloud-surface'],
                    'pb-logo-cloud-orbit-core-link',
                    'pb-logo-cloud-label pb-logo-cloud-orbit-label pb-logo-cloud-orbit-label-core'
                );

                foreach ($satelliteItems as $index => $item) {
                    $orbitHtml .= $renderOrbitItem(
                        $item,
                        'pb-logo-cloud-orbit-item pb-logo-cloud-orbit-item-pos-' . $index,
                        ['pb-logo-cloud-orbit-satellite-surface', 'pb-logo-cloud-surface'],
                        'pb-logo-cloud-orbit-link',
                        'pb-logo-cloud-label pb-logo-cloud-orbit-label'
                    );
                }

                $contentHtml = '<div class="pb-logo-cloud-orbit">'
                    . '<div class="pb-logo-cloud-orbit-shell">'
                    . '<div class="pb-logo-cloud-orbit-ring pb-logo-cloud-orbit-ring-inner" aria-hidden="true"></div>'
                    . '<div class="pb-logo-cloud-orbit-ring pb-logo-cloud-orbit-ring-outer" aria-hidden="true"></div>'
                    . ($hasThirdRing ? '<div class="pb-logo-cloud-orbit-ring pb-logo-cloud-orbit-ring-third" aria-hidden="true"></div>' : '')
                    . $orbitHtml
                    . '</div>'
                    . '</div>';
            } else {
                $gridHtml = '';
                foreach ($items as $item) {
                    $gridHtml .= $renderGridItem($item);
                }

                $contentHtml = '<div class="pb-logo-cloud-grid">' . $gridHtml . '</div>';
            }

            $rootClasses = [
                'pb-logo-cloud',
                'pb-logo-cloud-model-' . $presentationModel,
                'pb-logo-cloud-align-' . $align,
                'pb-logo-cloud-variant-' . $variant,
            ];
            if ($grayscale) {
                $rootClasses[] = 'is-grayscale';
            }
            if ($useCustomDesign) {
                $rootClasses[] = 'pb-logo-cloud-has-custom-design';
            }

            $html = '<section class="' . $escapeAttr(implode(' ', $rootClasses)) . '">'
                . $headerHtml
                . $contentHtml
                . '</section>';

            $safeId = self::blockId($context);
            $css = [];
            if ($safeId !== '') {
                $justifyMap = [
                    'left' => 'flex-start',
                    'center' => 'center',
                    'right' => 'flex-end',
                ];
                $justify = $justifyMap[$align] ?? 'center';
                $css[] = self::blockSelector($safeId, '.pb-logo-cloud') . '{--pb-logo-cloud-gap:' . $gap . 'px;--pb-logo-cloud-logo-height:' . $logoHeight . 'px;--pb-logo-cloud-widget-height:' . $widgetHeight . 'px;--pb-logo-cloud-columns:' . $columns . ';--pb-logo-cloud-motion-duration:' . $animationSpeed . 's;text-align:' . $escapeAttr($align) . ';}';
                $css[] = self::blockSelector($safeId, '.pb-logo-cloud-grid') . '{grid-template-columns:repeat(' . $columns . ',minmax(0,1fr));}';
                $css[] = self::blockSelector($safeId, '.pb-logo-cloud-columns') . '{grid-template-columns:repeat(' . $columnCount . ',minmax(0,1fr));}';
                $css[] = self::blockSelector($safeId, '.pb-logo-cloud-media') . '{justify-content:' . $escapeAttr($justify) . ';}';
                $css = array_merge($css, self::buildWidgetDesignRules(
                    $safeId,
                    ['.pb-logo-cloud-surface', '.pb-logo-cloud-surface:hover', '.pb-logo-cloud-classic-item', '.pb-logo-cloud-classic-item:hover', '.pb-logo-cloud-column-item', '.pb-logo-cloud-column-item:hover'],
                    ['.pb-logo-cloud-title', '.pb-logo-cloud-subtitle', '.pb-logo-cloud-label', '.pb-logo-cloud-fallback'],
                    $useCustomDesign,
                    $designSurfaceColor,
                    $designTextColor,
                    $designBorderStyle,
                    $designBorderWidth,
                    $designBorderColor,
                    $designRadius,
                    $designShadow
                ));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-logo-cloud-title', $titleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-logo-cloud-subtitle', $subtitleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-logo-cloud-label', $labelStyle));
            }

            return [
                'html' => $html,
                'css' => implode("\n", $css),
            ];
        };
    }
}
