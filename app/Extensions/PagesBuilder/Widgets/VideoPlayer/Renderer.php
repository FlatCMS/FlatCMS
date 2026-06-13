<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\VideoPlayer;

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
            $translate = static fn(string $key, string $fallback = ''): string => PageBuilderWidgetLocaleService::translate('VideoPlayer', $key, $fallback);

            $normalizeSkin = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['classic', 'soft', 'cinema'], true) ? $value : 'classic';
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

            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designOverlayColor = self::normalizeColor((string) ($settings['designOverlayColor'] ?? ''));
            $designOverlayOpacity = max(0, min(100, (int) ($settings['designOverlayOpacity'] ?? 0)));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(48, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $title = trim((string) ($settings['title'] ?? ''));
            $subtitle = trim((string) ($settings['subtitle'] ?? ''));
            $videoUrl = trim((string) ($settings['videoUrl'] ?? ''));
            $posterImage = trim((string) ($settings['posterImage'] ?? ''));
            $ambientMode = self::normalizeToggle($settings['ambientMode'] ?? false, false);
            $showHeader = self::normalizeToggle($settings['showHeader'] ?? true, true);
            $autoplay = self::normalizeToggle($settings['autoplay'] ?? false, false);
            $loop = self::normalizeToggle($settings['loop'] ?? false, false);
            $muted = self::normalizeToggle($settings['muted'] ?? false, false);
            if ($ambientMode) {
                $autoplay = true;
                $loop = true;
                $muted = true;
            }
            $preload = $normalizePreload($settings['preload'] ?? 'metadata');
            $height = max(260, min(720, (int) ($settings['height'] ?? 420)));
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $skin = $normalizeSkin($settings['skin'] ?? 'classic');

            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $subtitleStyle = $resolveTextStyle($settings, 'subtitleStyle', $titleStyle['align'] ?? $align);

            $safeId = self::blockId($context);

            $resolvedVideoUrl = $videoUrl !== '' ? (string) $resolveMedia($videoUrl) : '';
            $resolvedPosterUrl = $posterImage !== '' ? (string) $resolveMedia($posterImage) : '';

            $headerHtml = '';
            if ($showHeader && ($title !== '' || $subtitle !== '')) {
                $headerHtml .= '<header class="pb-video-player-header">';
                $headerHtml .= $renderStyledText($title, 'h2', 'pb-video-player-title', $titleStyle);
                $headerHtml .= $renderStyledText($subtitle, 'p', 'pb-video-player-subtitle', $subtitleStyle);
                $headerHtml .= '</header>';
            }

            $playerHtml = '';
            if ($resolvedVideoUrl !== '') {
                $videoAttributes = [
                    'class="pb-video-player-media"',
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
                if ($resolvedPosterUrl !== '') {
                    $videoAttributes[] = 'poster="' . $escapeAttr($resolvedPosterUrl) . '"';
                }

                $playerHtml = '<div class="pb-video-player-shell' . ($ambientMode ? ' is-ambient' : '') . '" data-video-player-shell>'
                    . '<div class="pb-video-player-stage">'
                    . '<video ' . implode(' ', $videoAttributes) . '>'
                    . '<source src="' . $escapeAttr($resolvedVideoUrl) . '">'
                    . '</video>'
                    . '<div class="pb-video-player-design-overlay" aria-hidden="true"></div>'
                    . '<button type="button" class="pb-video-player-big-play" data-video-player-big-play aria-label="' . $escapeAttr($translate('video_player_control_play')) . '"><span aria-hidden="true">▶</span></button>'
                    . '</div>'
                    . '<div class="pb-video-player-ui" data-video-player-ui>'
                    . '<div class="pb-video-player-progress-wrap">'
                    . '<input class="pb-video-player-progress" data-video-player-seek type="range" min="0" max="100" step="0.1" value="0" aria-label="' . $escapeAttr($translate('video_player_control_progress')) . '">'
                    . '</div>'
                    . '<div class="pb-video-player-controls">'
                    . '<button type="button" class="pb-video-player-control-btn" data-video-player-toggle aria-label="' . $escapeAttr($translate('video_player_control_play')) . '"><span aria-hidden="true">▶</span></button>'
                    . '<div class="pb-video-player-time"><span data-video-player-current>0:00</span><span class="pb-video-player-time-separator">/</span><span data-video-player-duration>0:00</span></div>'
                    . '<button type="button" class="pb-video-player-control-btn" data-video-player-mute aria-label="' . $escapeAttr($translate('video_player_control_mute')) . '"><span aria-hidden="true">🔊</span></button>'
                    . '<input class="pb-video-player-volume" data-video-player-volume type="range" min="0" max="1" step="0.05" value="' . $escapeAttr($muted ? '0' : '1') . '" aria-label="' . $escapeAttr($translate('video_player_control_volume')) . '">'
                    . '<span class="pb-video-player-controls-spacer" aria-hidden="true"></span>'
                    . '<button type="button" class="pb-video-player-control-btn" data-video-player-fullscreen aria-label="' . $escapeAttr($translate('video_player_control_fullscreen')) . '"><span aria-hidden="true">⤢</span></button>'
                    . '</div>'
                    . '</div>'
                    . '</div>';
            } else {
                $playerHtml = '<div class="pb-video-player-shell is-empty" data-video-player-shell>'
                    . '<div class="pb-video-player-placeholder">'
                    . '<span class="pb-video-player-placeholder-icon" aria-hidden="true">▶</span>'
                    . '<strong class="pb-video-player-placeholder-title">' . $escape($translate('video_player_placeholder_title')) . '</strong>'
                    . '<p class="pb-video-player-placeholder-text">' . $escape($translate('video_player_placeholder_text')) . '</p>'
                    . '</div>'
                    . '</div>';
            }

            $html = '<section class="pb-video-player pb-video-player-skin-' . $escapeAttr($skin) . ' pb-video-player-align-' . $escapeAttr($align) . ($ambientMode ? ' pb-video-player-mode-ambient' : '') . '"'
                . ' data-video-player'
                . ' data-video-player-ambient="' . ($ambientMode ? '1' : '0') . '"'
                . ' data-label-play="' . $escapeAttr($translate('video_player_control_play')) . '"'
                . ' data-label-pause="' . $escapeAttr($translate('video_player_control_pause')) . '"'
                . ' data-label-mute="' . $escapeAttr($translate('video_player_control_mute')) . '"'
                . ' data-label-unmute="' . $escapeAttr($translate('video_player_control_unmute')) . '"'
                . ' data-label-fullscreen="' . $escapeAttr($translate('video_player_control_fullscreen')) . '"'
                . ' data-label-exit-fullscreen="' . $escapeAttr($translate('video_player_control_exit_fullscreen')) . '">'
                . $headerHtml
                . $playerHtml;

            if ($resolvedVideoUrl === '') {
                $html .= '<div class="pb-empty">' . $escape($translate('video_player_empty')) . '</div>';
            }

            $html .= '</section>';

            $css = [];
            if ($safeId !== '') {
                $css[] = self::blockSelector($safeId, '.pb-video-player') . '{--pb-video-player-height:' . $escapeAttr((string) $height) . 'px;}';
                $css = array_merge($css, self::buildWidgetDesignRules(
                    $safeId,
                    ['.pb-video-player-shell', '.pb-video-player-shell:hover'],
                    ['.pb-video-player-title', '.pb-video-player-subtitle', '.pb-video-player-placeholder-title', '.pb-video-player-placeholder-text'],
                    $useCustomDesign,
                    $designSurfaceColor,
                    $designTextColor,
                    $designBorderStyle,
                    $designBorderWidth,
                    $designBorderColor,
                    $designRadius,
                    $designShadow
                ));
                if ($useCustomDesign) {
                    $effectiveOverlayColor = $designOverlayColor !== '' ? $designOverlayColor : '#000000';
                    $overlayOpacity = min(1, $designOverlayOpacity / 100);
                    $css[] = self::blockSelector($safeId, '.pb-video-player-design-overlay') . '{background-color:' . $escapeAttr($effectiveOverlayColor) . ';opacity:' . $escapeAttr((string) $overlayOpacity) . ';}';
                }
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-video-player-title', $titleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-video-player-subtitle', $subtitleStyle));
            }

            return [
                'html' => $html,
                'css' => implode("\n", $css),
            ];
        };
    }
}
