<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\TestimonialCards;

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
            $translate = static fn(string $key, string $fallback = ''): string => PageBuilderWidgetLocaleService::translate('TestimonialCards', $key, $fallback);

            $parseRepeater = static function (mixed $raw, string $delimiter = "\n"): array {
                if (!is_string($raw) || trim($raw) === '') {
                    return [];
                }

                if ($delimiter !== '' && str_contains($raw, $delimiter)) {
                    $items = explode($delimiter, $raw);
                } else {
                    $items = preg_split('/\r\n|\r|\n/', $raw) ?: [];
                }

                $items = array_map(static fn(mixed $item): string => trim((string) $item), $items);
                while ($items !== [] && trim((string) $items[count($items) - 1]) === '') {
                    array_pop($items);
                }

                return $items;
            };

            $normalizeVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['subtle', 'strong', 'dashed'], true) ? $value : 'subtle';
            };

            $normalizeColumns = static function (mixed $raw): int {
                $value = (int) $raw;
                return max(1, min(3, $value > 0 ? $value : 3));
            };

            $normalizeRating = static function (mixed $raw): int {
                $value = (int) $raw;
                return max(0, min(5, $value));
            };

            $normalizeTarget = static function (mixed $raw, string $fallback = '_self'): string {
                $value = trim((string) $raw);
                if (in_array($value, ['_self', '_blank'], true)) {
                    return $value;
                }

                return in_array($fallback, ['_self', '_blank'], true) ? $fallback : '_self';
            };

            $buildInitials = static function (string $name): string {
                $trimmed = trim($name);
                if ($trimmed === '') {
                    return 'FC';
                }

                $parts = preg_split('/\s+/u', $trimmed) ?: [];
                $initials = '';
                foreach ($parts as $part) {
                    if ($part === '') {
                        continue;
                    }
                    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                    if (mb_strlen($initials) >= 2) {
                        break;
                    }
                }

                return $initials !== '' ? $initials : 'FC';
            };

            $resolveSettingString = static function (mixed $value) use ($translate): string {
                if (is_array($value) && (($value['__label'] ?? false) === true || isset($value['key']))) {
                    return trim($translate((string) ($value['key'] ?? ''), (string) ($value['fallback'] ?? '')));
                }

                return trim((string) $value);
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

                return '<span class="pb-styled-text-list-marker pb-styled-text-list-marker-' . $escape($listStyle) . '" aria-hidden="true">' . $escape($glyph) . '</span>' . $content;
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

            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(48, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $title = $resolveSettingString($settings['title'] ?? ['__label' => true, 'key' => 'testimonial_cards_default_title', 'fallback' => '']);
            $subtitle = $resolveSettingString($settings['subtitle'] ?? ['__label' => true, 'key' => 'testimonial_cards_default_subtitle', 'fallback' => '']);
            $quotes = $parseRepeater($resolveSettingString($settings['quotes'] ?? ['__label' => true, 'key' => 'testimonial_cards_default_quotes', 'fallback' => '']), "\n---\n");
            $names = $parseRepeater($resolveSettingString($settings['names'] ?? ['__label' => true, 'key' => 'testimonial_cards_default_names', 'fallback' => '']));
            $companies = $parseRepeater($resolveSettingString($settings['companies'] ?? ['__label' => true, 'key' => 'testimonial_cards_default_companies', 'fallback' => '']));
            $roles = $parseRepeater($resolveSettingString($settings['roles'] ?? ['__label' => true, 'key' => 'testimonial_cards_default_roles', 'fallback' => '']));
            $ratings = $parseRepeater($resolveSettingString($settings['ratings'] ?? ['__label' => true, 'key' => 'testimonial_cards_default_ratings', 'fallback' => '']));
            $avatars = $parseRepeater((string) ($settings['avatars'] ?? ''));
            $links = $parseRepeater((string) ($settings['links'] ?? ''));
            $targets = $parseRepeater((string) ($settings['targets'] ?? ''));
            $showHeader = self::normalizeToggle($settings['showHeader'] ?? 'on', true);
            $showRatings = self::normalizeToggle($settings['showRatings'] ?? 'on', true);
            $showCompany = self::normalizeToggle($settings['showCompany'] ?? 'on', true);
            $showAvatars = self::normalizeToggle($settings['showAvatars'] ?? 'on', true);
            $columns = $normalizeColumns($settings['columns'] ?? 3);
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $variant = $normalizeVariant($settings['variant'] ?? 'subtle');

            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $subtitleStyle = $resolveTextStyle($settings, 'subtitleStyle', $titleStyle['align'] ?? $align);
            $quoteStyle = $resolveTextStyle($settings, 'quoteStyle', $align);
            $nameStyle = $resolveTextStyle($settings, 'nameStyle', $align);
            $roleStyle = $resolveTextStyle($settings, 'roleStyle', $nameStyle['align'] ?? $align);

            $safeId = self::blockId($context);

            $count = max(count($quotes), count($names), count($companies), count($roles), count($ratings), count($avatars), count($links), 1);
            $count = min($count, 20);

            $itemsHtml = '';
            $renderedCount = 0;
            $cloudItems = [];
            for ($index = 0; $index < $count; $index++) {
                $quote = trim((string) ($quotes[$index] ?? ''));
                $name = trim((string) ($names[$index] ?? ''));
                $company = trim((string) ($companies[$index] ?? ''));
                $role = trim((string) ($roles[$index] ?? ''));
                $rating = $normalizeRating($ratings[$index] ?? 0);
                $avatar = trim((string) ($avatars[$index] ?? ''));
                $link = self::sanitizeUrl((string) ($links[$index] ?? ''));
                $target = $normalizeTarget($targets[$index] ?? '_self');

                if ($quote === '' && $name === '' && $company === '' && $role === '' && $avatar === '' && $rating === 0) {
                    continue;
                }

                $avatarUrl = $avatar !== '' ? (string) $resolveImage($avatar) : '';
                $cardClass = match ($variant) {
                    'strong' => 'pb-card pb-card-strong',
                    'dashed' => 'pb-card pb-card-subtle',
                    default => 'pb-card pb-card-subtle',
                };

                $ratingHtml = '';
                if ($showRatings && $rating > 0) {
                    $ratingHtml = '<div class="pb-testimonial-rating" aria-label="' . $escapeAttr(sprintf('%d/5', $rating)) . '">'
                        . '<span class="pb-testimonial-rating-stars" aria-hidden="true">'
                        . str_repeat('&#9733;', $rating)
                        . str_repeat('&#9734;', max(0, 5 - $rating))
                        . '</span>'
                        . '</div>';
                }

                $avatarNode = '';
                if ($showAvatars) {
                    if ($avatarUrl !== '') {
                        $avatarNode = '<span class="pb-testimonial-avatar pb-testimonial-avatar-image"><img class="pb-testimonial-avatar-image-el" src="' . $escapeAttr($avatarUrl) . '" alt="' . $escapeAttr($name !== '' ? $name : $translate('testimonial_cards_fallback_name')) . '"></span>';
                    } else {
                        $avatarNode = '<span class="pb-testimonial-avatar pb-testimonial-avatar-fallback" aria-hidden="true">' . $escape($buildInitials($name)) . '</span>';
                    }

                    if (count($cloudItems) < 20) {
                        $cloudItems[] = [
                            'url' => $avatarUrl,
                            'name' => $name !== '' ? $name : $translate('testimonial_cards_fallback_name'),
                            'initials' => $buildInitials($name),
                        ];
                    }
                }

                $nameHtml = $renderStyledText($name !== '' ? $name : $translate('testimonial_cards_fallback_name'), 'h3', 'pb-testimonial-name', $nameStyle);
                if ($link !== '' && $nameHtml !== '') {
                    $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
                    $nameHtml = '<a class="pb-testimonial-author-link" href="' . $escapeAttr($link) . '" target="' . $escapeAttr($target) . '"' . $rel . '>' . $nameHtml . '</a>';
                }
                $companyHtml = '';
                if ($showCompany && $company !== '') {
                    $companyHtml = '<p class="pb-testimonial-company">' . $escape($company) . '</p>';
                }

                $itemsHtml .= '<article class="pb-testimonial-card ' . $escapeAttr($cardClass) . '" data-testimonial-slide="1">';
                $itemsHtml .= '<div class="pb-testimonial-card-top"><span class="pb-testimonial-card-mark" aria-hidden="true">&ldquo;</span>' . $ratingHtml . '</div>';
                $itemsHtml .= '<div class="pb-testimonial-card-body">' . $renderStyledText($quote, 'blockquote', 'pb-testimonial-quote', $quoteStyle) . '</div>';
                $itemsHtml .= '<footer class="pb-testimonial-footer"><div class="pb-testimonial-author">';
                if ($avatarNode !== '') {
                    $itemsHtml .= $avatarNode;
                }
                $itemsHtml .= '<div class="pb-testimonial-meta">' . $nameHtml . $companyHtml . $renderStyledText($role, 'p', 'pb-testimonial-role', $roleStyle) . '</div>';
                $itemsHtml .= '</div></footer>';
                $itemsHtml .= '</article>';
                $renderedCount++;
            }

            $isEmpty = false;
            if ($itemsHtml === '') {
                $isEmpty = true;
                $itemsHtml = '<div class="pb-empty">' . $escape($translate('testimonial_cards_empty')) . '</div>';
            }

            $cloudHtml = '';
            if ($showAvatars && $cloudItems !== []) {
                $cloudHtml .= '<div class="pb-testimonial-cloud" aria-hidden="true">';
                $cloudHtml .= '<div class="pb-testimonial-cloud-lines"><canvas class="pb-testimonial-cloud-waves" data-testimonial-cloud-waves data-wave-count="10" data-amplitude="50" data-base-speed="0.005" data-wave-spacing="30" data-line-width="1" data-left-offset="0" data-right-offset="0" aria-hidden="true"></canvas></div>';
                foreach ($cloudItems as $itemIndex => $item) {
                    $positionClass = 'pb-testimonial-cloud-item-pos-' . $escapeAttr((string) ($itemIndex % 10));
                    $cloudHtml .= '<span class="pb-testimonial-cloud-item ' . $positionClass . '">';
                    if ((string) ($item['url'] ?? '') !== '') {
                        $cloudHtml .= '<span class="pb-testimonial-cloud-avatar pb-testimonial-cloud-avatar-image"><img class="pb-testimonial-cloud-avatar-image-el" src="' . $escapeAttr((string) $item['url']) . '" alt="' . $escapeAttr((string) ($item['name'] ?? '')) . '"></span>';
                    } else {
                        $cloudHtml .= '<span class="pb-testimonial-cloud-avatar pb-testimonial-cloud-avatar-fallback">' . $escape((string) ($item['initials'] ?? 'FC')) . '</span>';
                    }
                    $cloudHtml .= '</span>';
                }
                $cloudHtml .= '</div>';
            }

            $headerHtml = '';
            if ($showHeader && ($title !== '' || $subtitle !== '')) {
                $headerHtml .= '<header class="pb-testimonial-cards-header">';
                $headerHtml .= $renderStyledText($title, 'h2', 'pb-testimonial-cards-title', $titleStyle);
                $headerHtml .= $renderStyledText($subtitle, 'p', 'pb-testimonial-cards-subtitle', $subtitleStyle);
                $headerHtml .= '</header>';
            }

            $shellClass = 'pb-testimonial-cards-shell' . ($headerHtml !== '' ? ' has-header' : '');
            $visibleClass = 'pb-testimonial-cards-visible-' . $escapeAttr((string) $columns);

            $controlsHtml = '';
            if (!$isEmpty && $renderedCount > 1) {
                $prevLabel = $translate('testimonial_cards_nav_prev');
                $nextLabel = $translate('testimonial_cards_nav_next');
                $controlsHtml = '<div class="pb-testimonial-cards-controls" data-testimonial-controls hidden>'
                    . '<button type="button" class="pb-testimonial-cards-nav pb-testimonial-cards-nav-prev" data-testimonial-prev aria-label="' . $escapeAttr($prevLabel) . '" title="' . $escapeAttr($prevLabel) . '"><span aria-hidden="true">&#8249;</span></button>'
                    . '<button type="button" class="pb-testimonial-cards-nav pb-testimonial-cards-nav-next" data-testimonial-next aria-label="' . $escapeAttr($nextLabel) . '" title="' . $escapeAttr($nextLabel) . '"><span aria-hidden="true">&#8250;</span></button>'
                    . '</div>';
            }

            $html = '<section class="pb-testimonial-cards pb-testimonial-cards-variant-' . $escapeAttr($variant) . ' pb-testimonial-cards-align-' . $escapeAttr($align) . ' ' . $visibleClass . '" data-testimonial-cards="1">'
                . '<div class="' . $escapeAttr($shellClass) . '">'
                . $headerHtml
                . '<div class="pb-testimonial-cards-stage">'
                . $cloudHtml
                . '<div class="pb-testimonial-cards-rail">'
                . '<div class="pb-testimonial-cards-track" data-testimonial-track>' . $itemsHtml
                . '</div>'
                . $controlsHtml
                . '</div>'
                . '</div>'
                . '</div>'
                . '</section>';

            $css = [];
            if ($safeId !== '') {
                $css = array_merge($css, self::buildWidgetDesignRules(
                    $safeId,
                    ['.pb-testimonial-card', '.pb-testimonial-card:hover'],
                    ['.pb-testimonial-cards-title', '.pb-testimonial-cards-subtitle', '.pb-testimonial-quote', '.pb-testimonial-name', '.pb-testimonial-company', '.pb-testimonial-role', '.pb-testimonial-rating-stars', '.pb-testimonial-card-mark'],
                    $useCustomDesign,
                    $designSurfaceColor,
                    $designTextColor,
                    $designBorderStyle,
                    $designBorderWidth,
                    $designBorderColor,
                    $designRadius,
                    $designShadow
                ));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-testimonial-cards-title', $titleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-testimonial-cards-subtitle', $subtitleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-testimonial-quote', $quoteStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-testimonial-name', $nameStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-testimonial-role', $roleStyle));
            }

            return [
                'html' => $html,
                'css' => implode("\n", $css),
            ];
        };
    }
}
