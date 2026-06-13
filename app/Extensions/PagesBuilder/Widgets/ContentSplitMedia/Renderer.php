<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\ContentSplitMedia;

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
            $resolveMedia = $helpers['resolve_image'] ?? static fn(string $value): string => $value;
            $translate = static fn(string $key, string $fallback = ''): string => PageBuilderWidgetLocaleService::translate('ContentSplitMedia', $key, $fallback);

            $normalizeVerticalAlign = static function (mixed $raw, string $fallback = 'center'): string {
                $value = strtolower(trim((string) $raw));
                if (in_array($value, ['top', 'center', 'bottom'], true)) {
                    return $value;
                }

                $safeFallback = strtolower(trim($fallback));
                return in_array($safeFallback, ['top', 'center', 'bottom'], true) ? $safeFallback : 'center';
            };

            $normalizeVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['subtle', 'strong', 'dark'], true) ? $value : 'subtle';
            };

            $normalizeMediaKind = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['image', 'video'], true) ? $value : 'image';
            };

            $normalizeMediaPosition = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['left', 'right'], true) ? $value : 'right';
            };

            $normalizeRatio = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['balanced', 'content-wide', 'media-wide'], true) ? $value : 'balanced';
            };

            $normalizePreload = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['auto', 'metadata', 'none'], true) ? $value : 'metadata';
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

            $renderStyledParagraphs = static function (string $text, string $className, array $style) use (
                $escape,
                $escapeAttr,
                $injectTextIcon,
                $injectTextListMarker
            ): string {
                $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));
                if ($normalized === '') {
                    return '';
                }

                $chunks = preg_split('/\n\s*\n/u', $normalized) ?: [];
                $paragraphs = [];
                foreach ($chunks as $chunk) {
                    $line = preg_replace('/\n+/u', "<br>\n", trim((string) $chunk));
                    if (!is_string($line) || trim(strip_tags($line)) === '') {
                        continue;
                    }
                    $safeLine = nl2br($escape(trim((string) $chunk)), false);
                    $content = '<span class="pb-styled-text-content">' . $safeLine . '</span>';
                    $paragraphs[] = '<p class="pb-content-split-media-body-paragraph">'
                        . $injectTextListMarker($injectTextIcon($content, $style), $style)
                        . '</p>';
                }

                if ($paragraphs === []) {
                    return '';
                }

                return '<div class="' . $escapeAttr($className) . '">' . implode('', $paragraphs) . '</div>';
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

                $css = [$scopedSelector . '{' . implode('', $rules) . '}'];
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
                    $css[] = $scopedSelector . ' .pb-styled-text-content{' . implode('', $contentRules) . '}';
                }

                $listStyle = self::normalizeTextStyleList((string) ($style['list'] ?? 'none'));
                if ($listStyle !== 'none') {
                    $css[] = $scopedSelector . ' .pb-styled-text-list-marker{display:inline-block;margin-right:0.45rem;}';
                }

                return $css;
            };

            $buildSelfAlignRules = static function (string $safeId, string $selector, string $align) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $normalizedAlign = self::normalizeAlign($align, 'left');
                $justifySelf = match ($normalizedAlign) {
                    'center' => 'center',
                    'right' => 'end',
                    default => 'start',
                };

                return [
                    self::blockSelector($safeId, $selector) . '{justify-self:' . $escapeAttr($justifySelf) . ';}',
                ];
            };

            $buildAccentColorRules = static function (string $safeId, string $selector, array $style) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $color = trim((string) ($style['color'] ?? ''));
                if ($color === '') {
                    return [];
                }

                return [
                    self::blockSelector($safeId, $selector) . '{color:' . $escapeAttr($color) . ';}',
                ];
            };

            $buildFeatureListAlignRules = static function (string $safeId, array $style) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $align = self::normalizeAlign((string) ($style['align'] ?? 'left'), 'left');
                $direction = $align === 'right' ? 'row-reverse' : 'row';
                $justifySelf = match ($align) {
                    'center' => 'center',
                    'right' => 'end',
                    default => 'start',
                };
                $textAlign = $align === 'center' ? 'left' : $align;
                $featureListJustify = $align === 'center' ? 'stretch' : $justifySelf;
                $featureItemJustify = $align === 'center' ? 'stretch' : $justifySelf;
                $featureRules = [
                    'flex-direction:' . $escapeAttr($direction) . ';',
                    'justify-self:' . $escapeAttr($featureItemJustify) . ';',
                ];
                $featureTextRules = ['text-align:' . $escapeAttr($textAlign) . ';'];
                $featureListRules = ['justify-items:' . $escapeAttr($featureListJustify) . ';'];

                if ($align === 'center') {
                    $featureListRules[] = 'justify-self:center;';
                    $featureListRules[] = 'width:fit-content;';
                    $featureListRules[] = 'max-width:100%;';
                    $featureListRules[] = 'text-align:left;';
                    $featureRules[] = 'width:100%;';
                    $featureRules[] = 'max-width:100%;';
                    $featureRules[] = 'text-align:left;';
                    $featureTextRules[] = 'flex:1 1 auto;';
                    $featureTextRules[] = 'width:100%;';
                }

                return [
                    self::blockSelector($safeId, '.pb-content-split-media-features') . '{' . implode('', $featureListRules) . '}',
                    self::blockSelector($safeId, '.pb-content-split-media-feature') . '{' . implode('', $featureRules) . '}',
                    self::blockSelector($safeId, '.pb-content-split-media-feature-text') . '{' . implode('', $featureTextRules) . '}',
                ];
            };

            $parseFeatureItems = static function (string $raw): array {
                $normalized = str_replace(["\r\n", "\r"], "\n", $raw);
                $lines = preg_split('/\n/u', $normalized) ?: [];
                $items = [];
                foreach ($lines as $line) {
                    $value = ltrim(trim((string) $line), "-*• \t");
                    if ($value === '') {
                        continue;
                    }
                    $items[] = $value;
                }
                return $items;
            };


            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(48, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $showEyebrow = self::normalizeToggle($settings['showEyebrow'] ?? true, true);
            $showBody = self::normalizeToggle($settings['showBody'] ?? true, true);
            $showFeatures = self::normalizeToggle($settings['showFeatures'] ?? true, true);
            $showPrimaryCta = self::normalizeToggle($settings['showPrimaryCta'] ?? true, true);
            $showSecondaryCta = self::normalizeToggle($settings['showSecondaryCta'] ?? true, true);

            $eyebrow = trim((string) ($settings['eyebrow'] ?? ''));
            $title = trim((string) ($settings['title'] ?? ''));
            $subtitle = trim((string) ($settings['subtitle'] ?? ''));
            $body = trim((string) ($settings['body'] ?? ''));
            $featureItems = $parseFeatureItems((string) ($settings['featureItems'] ?? ''));

            $primaryLabel = $showPrimaryCta ? trim((string) ($settings['primaryLabel'] ?? '')) : '';
            $primaryUrl = $showPrimaryCta ? self::sanitizeUrl((string) ($settings['primaryUrl'] ?? '')) : '';
            $primaryTarget = trim((string) ($settings['primaryTarget'] ?? '_self'));
            $primaryTarget = in_array($primaryTarget, ['_self', '_blank'], true) ? $primaryTarget : '_self';

            $secondaryLabel = $showSecondaryCta ? trim((string) ($settings['secondaryLabel'] ?? '')) : '';
            $secondaryUrl = $showSecondaryCta ? self::sanitizeUrl((string) ($settings['secondaryUrl'] ?? '')) : '';
            $secondaryTarget = trim((string) ($settings['secondaryTarget'] ?? '_self'));
            $secondaryTarget = in_array($secondaryTarget, ['_self', '_blank'], true) ? $secondaryTarget : '_self';
            $placeholderTitle = trim((string) ($settings['placeholderTitle'] ?? ''));
            $placeholderText = trim((string) ($settings['placeholderText'] ?? ''));
            $emptyMessage = trim((string) ($settings['emptyMessage'] ?? ''));

            $mediaKind = $normalizeMediaKind($settings['mediaKind'] ?? 'image');
            $imageSrc = trim((string) ($settings['imageSrc'] ?? ''));
            $imageAlt = trim((string) ($settings['imageAlt'] ?? ''));
            $videoUrl = trim((string) ($settings['videoUrl'] ?? ''));
            $videoPoster = trim((string) ($settings['videoPoster'] ?? ''));
            $preload = $normalizePreload($settings['preload'] ?? 'metadata');
            $autoplay = self::normalizeToggle($settings['autoplay'] ?? false, false);
            $loop = self::normalizeToggle($settings['loop'] ?? false, false);
            $muted = self::normalizeToggle($settings['muted'] ?? false, false);
            $mediaPosition = $normalizeMediaPosition($settings['mediaPosition'] ?? 'right');
            $ratio = $normalizeRatio($settings['ratio'] ?? 'balanced');
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $textVerticalAlign = $normalizeVerticalAlign($settings['textVerticalAlign'] ?? 'center');
            $variant = $normalizeVariant($settings['variant'] ?? 'subtle');
            $mediaFit = self::normalizeMediaFit($settings['mediaFit'] ?? 'cover');

            $eyebrowStyle = $resolveTextStyle($settings, 'eyebrowStyle', $align);
            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $subtitleStyle = $resolveTextStyle($settings, 'subtitleStyle', $titleStyle['align'] ?? $align);
            $bodyStyle = $resolveTextStyle($settings, 'bodyStyle', $subtitleStyle['align'] ?? $align);
            $featureStyle = $resolveTextStyle($settings, 'featureStyle', $bodyStyle['align'] ?? $align);

            $resolvedImageSrc = $imageSrc !== '' ? trim((string) $resolveMedia($imageSrc)) : '';
            $resolvedVideoUrl = $videoUrl !== '' ? trim((string) $resolveMedia($videoUrl)) : '';
            $resolvedVideoPoster = $videoPoster !== '' ? trim((string) $resolveMedia($videoPoster)) : '';

            $hasImage = $mediaKind === 'image' && $resolvedImageSrc !== '';
            $hasVideo = $mediaKind === 'video' && $resolvedVideoUrl !== '';
            $hasMedia = $hasImage || $hasVideo;

            $safeId = self::blockId($context);

            $primaryButton = '';
            if ($primaryLabel !== '') {
                if ($primaryUrl !== '') {
                    $rel = $primaryTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                    $primaryButton = '<a class="btn btn-primary pb-btn pb-btn-primary" href="' . $escapeAttr($primaryUrl) . '" target="' . $escapeAttr($primaryTarget) . '"' . $rel . '>' . $escape($primaryLabel) . '</a>';
                } else {
                    $primaryButton = '<span class="btn btn-primary pb-btn pb-btn-primary is-static" aria-disabled="true">' . $escape($primaryLabel) . '</span>';
                }
            }

            $secondaryButton = '';
            if ($secondaryLabel !== '') {
                if ($secondaryUrl !== '') {
                    $rel = $secondaryTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                    $secondaryButton = '<a class="btn btn-ghost pb-btn pb-btn-ghost" href="' . $escapeAttr($secondaryUrl) . '" target="' . $escapeAttr($secondaryTarget) . '"' . $rel . '>' . $escape($secondaryLabel) . '</a>';
                } else {
                    $secondaryButton = '<span class="btn btn-ghost pb-btn pb-btn-ghost is-static" aria-disabled="true">' . $escape($secondaryLabel) . '</span>';
                }
            }

            $actionsHtml = '';
            if ($primaryButton !== '' || $secondaryButton !== '') {
                $actionsHtml = '<div class="pb-content-split-media-actions pb-content-split-media-actions-align-' . $escapeAttr($align) . '">'
                    . $primaryButton
                    . $secondaryButton
                    . '</div>';
            }

            $featuresHtml = '';
            if ($showFeatures && $featureItems !== []) {
                $itemsHtml = [];
                foreach ($featureItems as $featureItem) {
                    $itemsHtml[] = '<li class="pb-content-split-media-feature">'
                        . $renderStyledText($featureItem, 'span', 'pb-content-split-media-feature-text', $featureStyle)
                        . '</li>';
                }
                $featuresHtml = '<ul class="pb-content-split-media-features">' . implode('', $itemsHtml) . '</ul>';
            }

            if ($hasImage) {
                $mediaHtml = '<div class="pb-content-split-media-media-shell pb-content-split-media-media-shell-image">'
                    . '<div class="pb-content-split-media-media-inner">'
                    . '<img class="pb-content-split-media-image" src="' . $escapeAttr($resolvedImageSrc) . '" alt="' . $escapeAttr($imageAlt !== '' ? $imageAlt : $title) . '">'
                    . '</div>'
                    . '</div>';
            } elseif ($hasVideo) {
                $posterAttr = $resolvedVideoPoster !== '' ? ' poster="' . $escapeAttr($resolvedVideoPoster) . '"' : '';
                $videoAttributes = [
                    'class="pb-content-split-media-video"',
                    'controls',
                    'playsinline',
                    'preload="' . $escapeAttr($preload) . '"',
                ];
                if ($autoplay) {
                    $videoAttributes[] = 'autoplay';
                }
                if ($loop) {
                    $videoAttributes[] = 'loop';
                }
                if ($muted) {
                    $videoAttributes[] = 'muted';
                }
                if ($posterAttr !== '') {
                    $videoAttributes[] = trim($posterAttr);
                }
                $mediaHtml = '<div class="pb-content-split-media-media-shell pb-content-split-media-media-shell-video">'
                    . '<div class="pb-content-split-media-media-inner">'
                    . '<video ' . implode(' ', $videoAttributes) . '>'
                    . '<source src="' . $escapeAttr($resolvedVideoUrl) . '">'
                    . '</video>'
                    . '</div>'
                    . '</div>';
            } else {
                $mediaHtml = '<div class="pb-content-split-media-media-shell is-empty">'
                    . '<div class="pb-content-split-media-placeholder">'
                    . '<span class="pb-content-split-media-placeholder-icon" aria-hidden="true">◫</span>'
                    . '<strong class="pb-content-split-media-placeholder-title">' . $escape($placeholderTitle !== '' ? $placeholderTitle : $translate('content_split_media_placeholder_title')) . '</strong>'
                    . '<p class="pb-content-split-media-placeholder-text">' . $escape($placeholderText !== '' ? $placeholderText : $translate('content_split_media_placeholder_text')) . '</p>'
                    . '</div>'
                    . '</div>';
            }

            $contentHtml = '';
            if ($showEyebrow) {
                $contentHtml .= $renderStyledText($eyebrow, 'p', 'pb-content-split-media-eyebrow', $eyebrowStyle);
            }
            $contentHtml .= $renderStyledText($title, 'h2', 'pb-content-split-media-title', $titleStyle);
            $contentHtml .= $renderStyledText($subtitle, 'p', 'pb-content-split-media-subtitle', $subtitleStyle);
            if ($showBody) {
                $contentHtml .= $renderStyledParagraphs($body, 'pb-content-split-media-body', $bodyStyle);
            }
            $contentHtml .= $featuresHtml;
            $contentHtml .= $actionsHtml;

            if (!$hasMedia && trim(strip_tags($contentHtml)) === '') {
                $contentHtml .= '<div class="pb-empty">' . $escape($emptyMessage !== '' ? $emptyMessage : $translate('content_split_media_empty')) . '</div>';
            }

            $html = '<section class="pb-content-split-media pb-content-split-media-variant-' . $escapeAttr($variant)
                . ' pb-content-split-media-align-' . $escapeAttr($align)
                . ' pb-content-split-media-text-valign-' . $escapeAttr($textVerticalAlign)
                . ' pb-content-split-media-media-' . $escapeAttr($mediaPosition)
                . ' pb-content-split-media-ratio-' . $escapeAttr($ratio)
                . ' pb-content-split-media-fit-' . $escapeAttr($mediaFit)
                . ($hasMedia ? ' pb-content-split-media-has-media' : ' pb-content-split-media-no-media')
                . '">'
                . '<div class="pb-content-split-media-frame">'
                . '<div class="pb-content-split-media-content">'
                . $contentHtml
                . '</div>'
                . '<div class="pb-content-split-media-media">'
                . $mediaHtml
                . '</div>'
                . '</div>'
                . '</section>';

            $css = [];
            if ($safeId !== '') {
                $css = array_merge($css, self::buildWidgetDesignRules(
                    $safeId,
                    ['.pb-content-split-media-frame'],
                    ['.pb-content-split-media-eyebrow', '.pb-content-split-media-title', '.pb-content-split-media-subtitle', '.pb-content-split-media-body', '.pb-content-split-media-body *', '.pb-content-split-media-feature-text'],
                    $useCustomDesign,
                    $designSurfaceColor,
                    $designTextColor,
                    $designBorderStyle,
                    $designBorderWidth,
                    $designBorderColor,
                    $designRadius,
                    $designShadow
                ));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-content-split-media-eyebrow', $eyebrowStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-content-split-media-title', $titleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-content-split-media-subtitle', $subtitleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-content-split-media-body', $bodyStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-content-split-media-feature-text', $featureStyle));
                $css = array_merge($css, $buildSelfAlignRules($safeId, '.pb-content-split-media-eyebrow', (string) ($eyebrowStyle['align'] ?? 'left')));
                $css = array_merge($css, $buildAccentColorRules($safeId, '.pb-content-split-media-feature', $featureStyle));
                $css = array_merge($css, $buildFeatureListAlignRules($safeId, $featureStyle));
            }

            return [
                'html' => $html,
                'css' => implode("\n", $css),
            ];
        };
    }
}
