<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Carousel;

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
        
            $normalizeAutoplayDelayMs = static function (mixed $raw, int $fallbackMs = 5000): int {
                $value = trim((string) $raw);
                if ($value === '') {
                    return $fallbackMs;
                }

                $parsed = (int) round((float) $raw);
                if ($parsed <= 0) {
                    return $fallbackMs;
                }

                if ($parsed > 100) {
                    return max(2000, min(15000, $parsed));
                }

                return max(2000, min(15000, $parsed * 1000));
            };
        
            $normalizeIndicatorStyle = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['dots', 'bars', 'numbers'], true) ? $value : 'dots';
            };
        
            $normalizeArrowStyle = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['filled', 'outline', 'minimal'], true) ? $value : 'filled';
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
                $fallbackPrefix = preg_match('/^itemTitleStyle\d+$/i', $keyPrefix) === 1
                    ? 'itemTitleStyle'
                    : (preg_match('/^itemTextStyle\d+$/i', $keyPrefix) === 1 ? 'itemTextStyle' : '');
                $readSetting = static function (array $settings, string $currentPrefix, string $suffix, string $alternatePrefix = ''): mixed {
                    $primaryKey = $currentPrefix . $suffix;
                    if (array_key_exists($primaryKey, $settings)) {
                        $primary = $settings[$primaryKey];
                        if ($primary !== null && trim((string) $primary) !== '') {
                            return $primary;
                        }
                    }
        
                    if ($alternatePrefix !== '') {
                        $fallbackKey = $alternatePrefix . $suffix;
                        if (array_key_exists($fallbackKey, $settings)) {
                            return $settings[$fallbackKey];
                        }
                    }
        
                    return null;
                };
        
                $alignRaw = strtolower(trim((string) $readSetting($source, $keyPrefix, 'Align', $fallbackPrefix)));
                $iconPositionRaw = strtolower(trim((string) ($readSetting($source, $keyPrefix, 'IconPosition', $fallbackPrefix) ?? 'start')));
        
                return [
                    'align' => self::normalizeAlign($alignRaw, $fallbackAlign),
                    'font' => self::normalizeTextStyleFont((string) ($readSetting($source, $keyPrefix, 'Font', $fallbackPrefix) ?? 'inherit')),
                    'size' => self::normalizeTextStyleSize((string) ($readSetting($source, $keyPrefix, 'Size', $fallbackPrefix) ?? 'inherit')),
                    'bold' => self::normalizeToggle($readSetting($source, $keyPrefix, 'Bold', $fallbackPrefix) ?? false),
                    'italic' => self::normalizeToggle($readSetting($source, $keyPrefix, 'Italic', $fallbackPrefix) ?? false),
                    'underline' => self::normalizeToggle($readSetting($source, $keyPrefix, 'Underline', $fallbackPrefix) ?? false),
                    'color' => self::normalizeColor((string) ($readSetting($source, $keyPrefix, 'Color', $fallbackPrefix) ?? '')),
                    'list' => self::normalizeTextStyleList((string) ($readSetting($source, $keyPrefix, 'List', $fallbackPrefix) ?? 'none')),
                    'icon' => self::sanitizeIconClass((string) ($readSetting($source, $keyPrefix, 'Icon', $fallbackPrefix) ?? '')),
                    'iconPosition' => in_array($iconPositionRaw, ['start', 'end'], true) ? $iconPositionRaw : 'start',
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
                return $iconPosition === 'end'
                    ? $content . $iconHtml
                    : $iconHtml . $content;
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
        
            $renderStyledHtml = static function (string $html, string $tag, string $className, array $style) use (
                $escapeAttr,
                $injectTextIcon
            ): string {
                $value = trim($html);
                if ($value === '') {
                    return '';
                }
        
                $content = '<div class="pb-styled-text-content pb-styled-text-content-rich">' . $value . '</div>';
                $decorated = $injectTextIcon($content, $style);
        
                return '<' . $tag . ' class="' . $escapeAttr($className) . '">' . $decorated . '</' . $tag . '>';
            };
        
            $buildTextStyleRules = static function (string $safeId, string $selector, array $style) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }
        
                $scopedSelector = self::blockSelector($safeId, $selector);
                $rules = [];
                $align = self::normalizeAlign((string) ($style['align'] ?? 'left'));
                $rules[] = 'text-align:' . $escapeAttr($align) . ';';
        
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
        
                $cssRules = [];
                if ($rules !== []) {
                    $cssRules[] = $scopedSelector . '{' . implode('', $rules) . '}';
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
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-content{' . implode('', $contentRules) . '}';
                }
        
                $listStyle = self::normalizeTextStyleList((string) ($style['list'] ?? 'none'));
                if ($listStyle !== 'none') {
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-content-rich ul{list-style-type:' . $escapeAttr($listStyle) . ';}';
                }
        
                return $cssRules;
            };
        
            $title = trim((string) ($settings['title'] ?? ''));
            $images = $parseRepeaterLines($settings['images'] ?? '');
            $titles = $parseRepeaterLines($settings['titles'] ?? '');
            $texts = $parseRepeaterLines($settings['texts'] ?? '');
            $links = $parseRepeaterLines($settings['links'] ?? '');
            $buttonEnableds = $parseRepeaterLines($settings['buttonEnableds'] ?? '');
            $buttonLabels = $parseRepeaterLines($settings['buttonLabels'] ?? '');
            $buttonTargets = $parseRepeaterLines($settings['buttonTargets'] ?? '');
            $buttonAligns = $parseRepeaterLines($settings['buttonAligns'] ?? '');
        
            $defaultButtonLabel = trim((string) ($settings['buttonLabel'] ?? ''));
            if ($defaultButtonLabel === '') {
                $defaultButtonLabel = PageBuilderWidgetLocaleService::translate('Carousel', 'carousel_default_button_label');
            }
        
            $target = (string) ($settings['target'] ?? '_self');
            if (!in_array($target, ['_self', '_blank'], true)) {
                $target = '_self';
            }
        
            $showIndicators = self::normalizeToggle($settings['showIndicators'] ?? 'on', true);
            $showArrows = self::normalizeToggle($settings['showArrows'] ?? 'on', true);
            $indicatorStyle = $normalizeIndicatorStyle($settings['indicatorStyle'] ?? 'dots');
            $arrowStyle = $normalizeArrowStyle($settings['arrowStyle'] ?? 'filled');
            $autoplay = self::normalizeToggle($settings['autoplay'] ?? 'on', true);
            $loop = self::normalizeToggle($settings['loop'] ?? 'on', true);
        
            $autoplayDelay = $normalizeAutoplayDelayMs($settings['autoplayDelay'] ?? 5, 5000);
        
            $height = (int) ($settings['height'] ?? 420);
            if ($height < 240) {
                $height = 240;
            } elseif ($height > 720) {
                $height = 720;
            }
            $mediaFullBleed = self::normalizeToggle($settings['mediaFullBleed'] ?? '', false);
            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(40, (int) ($settings['designRadius'] ?? 14)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');
        
            $transition = strtolower(trim((string) ($settings['transition'] ?? 'slide')));
            if (!in_array($transition, ['slide', 'fade'], true)) {
                $transition = 'slide';
            }
        
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
        
            $maxItems = max(1, count($images), count($titles), count($texts), count($links), count($buttonEnableds), count($buttonLabels));
            $limit = min(12, $maxItems);
            $hasSlideData = count($images) > 0
                || count($titles) > 0
                || count($texts) > 0
                || count($links) > 0
                || count($buttonEnableds) > 0
                || count($buttonLabels) > 0;
        
            $slidesHtml = [];
            if ($hasSlideData) {
                for ($i = 0; $i < $limit; $i++) {
                    $image = $resolveImage((string) ($images[$i] ?? ''));
                    $slideTitle = trim((string) ($titles[$i] ?? ''));
                    $slideText = trim((string) ($texts[$i] ?? ''));
                    $slideLink = trim((string) ($links[$i] ?? ''));
                    $slideButtonEnabled = self::normalizeToggle($buttonEnableds[$i] ?? 'on', true);
                    $slideButtonLabel = trim((string) ($buttonLabels[$i] ?? ''));
                    if ($slideButtonLabel === '') {
                        $slideButtonLabel = $defaultButtonLabel;
                    }
                    $slideTitleStyle = $resolveTextStyle($settings, 'itemTitleStyle' . ($i + 1), 'left');
                    $slideTextStyle = $resolveTextStyle($settings, 'itemTextStyle' . ($i + 1), 'left');
                    $slideTarget = (string) ($buttonTargets[$i] ?? $target);
                    if (!in_array($slideTarget, ['_self', '_blank'], true)) {
                        $slideTarget = $target;
                    }
                    $slideTargetRel = $slideTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                    $slideButtonAlign = self::normalizeAlign((string) ($buttonAligns[$i] ?? 'left'));
        
                    $media = $image !== ''
                        ? '<img class="fc-carousel-image" src="' . $escapeAttr($image) . '" alt="' . $escapeAttr($slideTitle !== '' ? $slideTitle : PageBuilderWidgetLocaleService::translate('Carousel', 'carousel_slide_alt')) . '" loading="lazy" decoding="async">'
                        : '<div class="fc-carousel-media-placeholder" aria-hidden="true"></div>';
        
                    $caption = '';
                    if ($slideTitle !== '' || $slideText !== '' || ($slideButtonEnabled && $slideButtonLabel !== '')) {
                        $captionParts = [];
                        if ($slideTitle !== '') {
                            $captionParts[] = $renderStyledText($slideTitle, 'h3', 'fc-carousel-caption-title', $slideTitleStyle);
                        }
                        if ($slideText !== '') {
                            $captionParts[] = $renderStyledHtml($sanitizeRichText($slideText), 'div', 'fc-carousel-caption-text', $slideTextStyle);
                        }
                        if ($slideButtonEnabled && $slideButtonLabel !== '') {
                            if ($slideLink !== '') {
                                $captionParts[] = '<a class="fc-carousel-caption-btn btn btn-primary pb-btn pb-btn-primary fc-carousel-caption-btn-align-' . $escapeAttr($slideButtonAlign) . '" href="' . $escapeAttr($slideLink) . '" target="' . $escapeAttr($slideTarget) . '"' . $slideTargetRel . '>' . $escape($slideButtonLabel) . '</a>';
                            } else {
                                $captionParts[] = '<span class="fc-carousel-caption-btn btn btn-primary pb-btn pb-btn-primary fc-carousel-caption-btn-align-' . $escapeAttr($slideButtonAlign) . ' is-static" aria-disabled="true">' . $escape($slideButtonLabel) . '</span>';
                            }
                        }
                        $caption = '<div class="fc-carousel-caption">' . implode('', $captionParts) . '</div>';
                    }
        
                    $slidesHtml[] = '<article class="fc-carousel-slide' . ($i === 0 ? ' is-active' : '') . '" data-fc-carousel-slide>' . $media . $caption . '</article>';
                }
            }
        
            if ($slidesHtml === []) {
                $slidesHtml[] = '<article class="fc-carousel-slide is-active" data-fc-carousel-slide><div class="fc-carousel-empty">' . $escape(PageBuilderWidgetLocaleService::translate('Carousel', 'carousel_empty')) . '</div></article>';
                $showIndicators = false;
                $showArrows = false;
                $autoplay = false;
            }
        
            $indicatorHtml = '';
            if ($showIndicators && $limit > 1) {
                $dots = [];
                for ($i = 0; $i < $limit; $i++) {
                    $label = PageBuilderWidgetLocaleService::translate('Carousel', 'carousel_indicator_label', '', ['index' => (string) ($i + 1)]);
                    $mark = $indicatorStyle === 'numbers'
                        ? (string) ($i + 1)
                        : str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                    $dots[] = '<button class="fc-carousel-indicator' . ($i === 0 ? ' is-active' : '') . '" type="button" data-fc-carousel-to="' . $escapeAttr((string) $i) . '" aria-label="' . $escapeAttr($label) . '"><span class="fc-carousel-indicator-mark" aria-hidden="true">' . $escape($mark) . '</span></button>';
                }
                $indicatorHtml = '<div class="fc-carousel-indicators fc-carousel-indicators-style-' . $escapeAttr($indicatorStyle) . '" data-fc-carousel-indicators>' . implode('', $dots) . '</div>';
            }
        
            $controlsHtml = '';
            if ($showArrows && $limit > 1) {
                $prevLabel = PageBuilderWidgetLocaleService::translate('Carousel', 'carousel_prev_label');
                $nextLabel = PageBuilderWidgetLocaleService::translate('Carousel', 'carousel_next_label');
                $controlsHtml = '<button class="fc-carousel-control fc-carousel-control-style-' . $escapeAttr($arrowStyle) . ' fc-carousel-control-prev" type="button" data-fc-carousel-prev aria-label="' . $escapeAttr($prevLabel) . '">'
                    . '<span class="fc-carousel-control-icon" aria-hidden="true">&lsaquo;</span>'
                    . '</button>'
                    . '<button class="fc-carousel-control fc-carousel-control-style-' . $escapeAttr($arrowStyle) . ' fc-carousel-control-next" type="button" data-fc-carousel-next aria-label="' . $escapeAttr($nextLabel) . '">'
                    . '<span class="fc-carousel-control-icon" aria-hidden="true">&rsaquo;</span>'
                    . '</button>';
            }
        
            $headerHtml = $title !== '' ? '<div class="fc-carousel-header">' . $renderStyledText($title, 'h2', 'fc-carousel-title', $titleStyle) . '</div>' : '';
            $carouselClasses = [
                'fc-carousel',
                'fc-carousel-transition-' . $transition,
            ];
            if ($showArrows && $limit > 1) {
                $carouselClasses[] = 'fc-carousel-has-arrows';
                $carouselClasses[] = 'fc-carousel-arrows-' . $arrowStyle;
            }
            if ($showIndicators && $limit > 1) {
                $carouselClasses[] = 'fc-carousel-has-indicators';
                $carouselClasses[] = 'fc-carousel-indicators-' . $indicatorStyle;
            }
            if ($mediaFullBleed) {
                $carouselClasses[] = 'fc-carousel-media-fit-cover';
            }
        
            $html = '<div class="fc-carousel-wrapper">'
                . $headerHtml
                . '<div class="' . $escapeAttr(implode(' ', $carouselClasses)) . '" data-fc-carousel="1" data-transition="' . $escapeAttr($transition) . '" data-autoplay="' . ($autoplay ? '1' : '0') . '" data-delay="' . $escapeAttr((string) $autoplayDelay) . '" data-loop="' . ($loop ? '1' : '0') . '">'
                . '<div class="fc-carousel-track">' . implode('', $slidesHtml) . '</div>'
                . $controlsHtml
                . $indicatorHtml
                . '</div>'
                . '</div>';
        
            $safeId = self::blockId($context);
            $cssRules = [];
            if ($safeId !== '') {
                $cssRules[] = self::blockSelector($safeId, '.fc-carousel') . '{--fc-carousel-height:' . $height . 'px;}';
                if ($useCustomDesign) {
                    $carouselRules = ['border-radius:' . $escapeAttr((string) $designRadius) . 'px;'];
                    if ($designSurfaceColor !== '') {
                        $carouselRules[] = 'background:' . $escapeAttr($designSurfaceColor) . ';';
                    }
                    if ($designBorderStyle !== 'inherit') {
                        $carouselRules[] = 'border-style:' . $escapeAttr($designBorderStyle) . ';';
                        $carouselRules[] = 'border-width:' . $escapeAttr((string) $designBorderWidth) . 'px;';
                    }
                    if ($designBorderColor !== '') {
                        $carouselRules[] = 'border-color:' . $escapeAttr($designBorderColor) . ';';
                        if ($designBorderStyle === 'inherit') {
                            $carouselRules[] = 'border-width:' . $escapeAttr((string) $designBorderWidth) . 'px;';
                        }
                    }
                    $shadowValue = $resolveShadowValue($designShadow);
                    if ($shadowValue !== '') {
                        $carouselRules[] = 'box-shadow:' . $escapeAttr($shadowValue) . ';';
                    }
                    $cssRules[] = self::blockSelector($safeId, '.fc-carousel') . '{' . implode('', $carouselRules) . '}';
                    if ($designSurfaceColor !== '') {
                        $cssRules[] = self::blockSelector($safeId, '.fc-carousel-slide') . '{background:' . $escapeAttr($designSurfaceColor) . ';}';
                    }
                    if ($designTextColor !== '') {
                        $cssRules[] = implode(',', [
                            self::blockSelector($safeId, '.fc-carousel-title'),
                            self::blockSelector($safeId, '.fc-carousel-caption'),
                            self::blockSelector($safeId, '.fc-carousel-caption-title'),
                            self::blockSelector($safeId, '.fc-carousel-caption-text'),
                        ]) . '{color:' . $escapeAttr($designTextColor) . ';}';
                    }
                }
                $cssRules = array_merge($cssRules, $buildTextStyleRules($safeId, '.fc-carousel-title', $titleStyle));
                for ($i = 0; $i < $limit; $i++) {
                    $cssRules = array_merge(
                        $cssRules,
                        $buildTextStyleRules(
                            $safeId,
                            '.fc-carousel-slide:nth-child(' . ($i + 1) . ') .fc-carousel-caption-title',
                            $resolveTextStyle($settings, 'itemTitleStyle' . ($i + 1), 'left')
                        )
                    );
                    $cssRules = array_merge(
                        $cssRules,
                        $buildTextStyleRules(
                            $safeId,
                            '.fc-carousel-slide:nth-child(' . ($i + 1) . ') .fc-carousel-caption-text',
                            $resolveTextStyle($settings, 'itemTextStyle' . ($i + 1), 'left')
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
