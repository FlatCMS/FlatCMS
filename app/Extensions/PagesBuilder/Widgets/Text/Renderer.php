<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Text;

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
            $sanitizeRichText = $helpers['sanitize_rich_text'] ?? static function (string $value): string {
                $html = (string) $value;
                $html = preg_replace('#<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html) ?? '';
                $allowed = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code><span><img><video><source><table><thead><tbody><tr><th><td><del><s><strike>';
                return strip_tags($html, $allowed);
            };

            $resolveTextStyle = static function (array $source, string $prefix, string $fallbackAlign): array {
                $keyPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'textStyle';
                $iconPosition = strtolower(trim((string) ($source[$keyPrefix . 'IconPosition'] ?? 'start')));

                return [
                    'align' => self::normalizeAlign($source[$keyPrefix . 'Align'] ?? $fallbackAlign),
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

            $renderStyledText = static function (string $text, string $tag, string $className, array $style) use (
                $escape,
                $escapeAttr,
                $injectTextIcon
            ): string {
                $value = trim($text);
                if ($value === '') {
                    return '';
                }

                $content = '<span class="pb-styled-text-content">' . $escape($value) . '</span>';
                $decorated = $injectTextIcon($content, $style);

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

                $size = (string) ($style['size'] ?? 'inherit');
                if ($size !== '' && $size !== 'inherit') {
                    $rules[] = self::widgetTextSizeRule($size);
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

                return [$scopedSelector . '{' . implode('', $rules) . '}'];
            };

            $decodeAttr = static function (string $value): string {
                return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            };

            $extractAttribute = static function (string $attributes, string $name) use ($decodeAttr): string {
                if (preg_match('/\b' . preg_quote($name, '/') . '\s*=\s*"([^"]*)"/i', $attributes, $match) === 1) {
                    return $decodeAttr((string) ($match[1] ?? ''));
                }
                if (preg_match("/\b" . preg_quote($name, '/') . "\s*=\s*'([^']*)'/i", $attributes, $match) === 1) {
                    return $decodeAttr((string) ($match[1] ?? ''));
                }
                if (preg_match('/\b' . preg_quote($name, '/') . '\s*=\s*([^\s>]+)/i', $attributes, $match) === 1) {
                    return $decodeAttr((string) ($match[1] ?? ''));
                }

                return '';
            };

            $sanitizeCssLength = static function (string $value): string {
                $safe = trim($value);
                if ($safe === '') {
                    return '';
                }

                if (preg_match('/^\d+(?:\.\d+)?(?:px|%|rem|em|vw|vh)$/i', $safe) === 1) {
                    return $safe;
                }

                return '';
            };

            $normalizeInlineStyle = static function (string $style, string $fallbackWidth = '') use ($sanitizeCssLength): string {
                $parts = preg_split('/\s*;\s*/', trim($style), -1, PREG_SPLIT_NO_EMPTY);
                $width = '';
                foreach ($parts as $part) {
                    [$property, $value] = array_pad(explode(':', $part, 2), 2, '');
                    if (strtolower(trim($property)) !== 'width') {
                        continue;
                    }
                    $candidate = $sanitizeCssLength($value);
                    if ($candidate !== '') {
                        $width = $candidate;
                    }
                }

                if ($width === '' && $fallbackWidth !== '') {
                    $width = $sanitizeCssLength($fallbackWidth);
                }

                return $width !== '' ? 'width: ' . $width . ';' : '';
            };

            $extractPreferredWidth = static function (string $attributes) use ($extractAttribute, $sanitizeCssLength): string {
                $percentage = $extractAttribute($attributes, 'data-percentage');
                if ($percentage !== '') {
                    $parts = array_map('trim', explode(',', $percentage));
                    $candidate = $parts[0] ?? '';
                    if ($candidate !== '') {
                        $normalized = $sanitizeCssLength($candidate . '%');
                        if ($normalized !== '') {
                            return $normalized;
                        }
                    }
                }

                $size = $extractAttribute($attributes, 'data-size');
                if ($size !== '') {
                    $parts = array_map('trim', explode(',', $size));
                    $candidate = $parts[0] ?? '';
                    $normalized = $sanitizeCssLength($candidate);
                    if ($normalized !== '') {
                        return $normalized;
                    }
                }

                return '';
            };

            $fallbackVideoLabel = PageBuilderWidgetLocaleService::translate('Text', 'text_video_fallback_link', 'Open video');

            $mediaRules = [];
            $mediaIndex = 0;

            $normalizeMediaHtml = static function (string $html) use (
                $sanitizeRichText,
                $extractAttribute,
                $extractPreferredWidth,
                $normalizeInlineStyle,
                $escapeAttr,
                $escape,
                $fallbackVideoLabel,
                &$mediaRules,
                &$mediaIndex
            ): string {
                $normalized = trim($sanitizeRichText($html));
                if ($normalized === '') {
                    return '';
                }

                $emptyParagraphPattern = '<p\b[^>]*>(?:\s|&nbsp;|&#160;|&#8203;|&#x200B;|​|<br\s*/?>)*</p>';
                $normalized = preg_replace('~' . $emptyParagraphPattern . '\s*(<(?:img|video)\b)~iu', '$1', $normalized) ?? $normalized;
                $normalized = preg_replace('~(</video>|<img\b[^>]*>)\s*' . $emptyParagraphPattern . '~iu', '$1', $normalized) ?? $normalized;
                $normalized = preg_replace('#<p\b[^>]*>​</p>#u', '', $normalized) ?? $normalized;
                $normalized = preg_replace('#<p\b[^>]*>​<br\s*/?></p>#iu', '', $normalized) ?? $normalized;

                $normalized = preg_replace_callback(
                    '#<img\b([^>]*)>#i',
                    static function (array $matches) use ($extractAttribute, $extractPreferredWidth, $normalizeInlineStyle, $escapeAttr, &$mediaRules, &$mediaIndex): string {
                        $attributes = (string) ($matches[1] ?? '');
                        $src = $extractAttribute($attributes, 'src');
                        $alt = $extractAttribute($attributes, 'alt');
                        if ($src === '') {
                            return '';
                        }

                        $style = $normalizeInlineStyle(
                            $extractAttribute($attributes, 'style'),
                            $extractPreferredWidth($attributes)
                        );
                        $mediaClasses = ['pb-text-media'];
                        if ($style !== '') {
                            $mediaIndex++;
                            $mediaClass = 'pb-text-media-' . $mediaIndex;
                            $mediaClasses[] = $mediaClass;
                            $mediaRules[] = ['selector' => '.' . $mediaClass, 'width' => rtrim(substr($style, strlen('width: ')), ';')];
                        }

                        $parts = ['src="' . $escapeAttr($src) . '"'];
                        if ($alt !== '') {
                            $parts[] = 'alt="' . $escapeAttr($alt) . '"';
                        }
                        $parts[] = 'class="' . $escapeAttr(implode(' ', $mediaClasses)) . '"';

                        foreach (['data-align', 'data-file-name', 'data-file-size', 'data-origin', 'data-size', 'data-percentage', 'data-proportion', 'data-rotate'] as $name) {
                            $value = $extractAttribute($attributes, $name);
                            if ($value !== '') {
                                $parts[] = $name . '="' . $escapeAttr($value) . '"';
                            }
                        }

                        $parts[] = 'loading="lazy"';
                        $parts[] = 'decoding="async"';

                        return '<img ' . implode(' ', $parts) . '>';
                    },
                    $normalized
                ) ?? $normalized;

                $normalized = preg_replace_callback(
                    '#<video\b([^>]*)>(.*?)</video>#is',
                    static function (array $matches) use (
                        $extractAttribute,
                        $extractPreferredWidth,
                        $normalizeInlineStyle,
                        $escapeAttr,
                        $escape,
                        $fallbackVideoLabel,
                        &$mediaRules,
                        &$mediaIndex
                    ): string {
                        $attributes = (string) ($matches[1] ?? '');
                        $inner = (string) ($matches[2] ?? '');
                        $preferredWidth = $extractPreferredWidth($attributes);
                        $style = $normalizeInlineStyle(
                            $preferredWidth !== '' ? '' : $extractAttribute($attributes, 'style'),
                            $preferredWidth
                        );
                        $mediaClasses = ['pb-text-media'];
                        if ($style !== '') {
                            $mediaIndex++;
                            $mediaClass = 'pb-text-media-' . $mediaIndex;
                            $mediaClasses[] = $mediaClass;
                            $mediaRules[] = ['selector' => '.' . $mediaClass, 'width' => rtrim(substr($style, strlen('width: ')), ';')];
                        }

                        $parts = ['controls', 'playsinline', 'preload="metadata"', 'class="' . $escapeAttr(implode(' ', $mediaClasses)) . '"'];

                        foreach (['data-align', 'data-file-name', 'data-file-size', 'data-origin', 'data-size', 'data-percentage', 'data-proportion', 'data-rotate', 'poster'] as $name) {
                            $value = $extractAttribute($attributes, $name);
                            if ($value !== '') {
                                $parts[] = $name . '="' . $escapeAttr($value) . '"';
                            }
                        }

                        $sources = preg_match_all('#<source\b([^>]*)>#i', $inner, $sourceMatches, PREG_SET_ORDER) ? $sourceMatches : [];
                        $sourceHtml = '';
                        $firstSourceUrl = '';

                        foreach ($sources as $sourceMatch) {
                            $sourceAttributes = (string) ($sourceMatch[1] ?? '');
                            $src = $extractAttribute($sourceAttributes, 'src');
                            if ($src === '') {
                                continue;
                            }
                            $type = $extractAttribute($sourceAttributes, 'type');
                            if ($firstSourceUrl === '') {
                                $firstSourceUrl = $src;
                            }
                            $sourceHtml .= '<source src="' . $escapeAttr($src) . '"' . ($type !== '' ? ' type="' . $escapeAttr($type) . '"' : '') . '>';
                        }

                        if ($sourceHtml === '') {
                            $src = $extractAttribute($attributes, 'src');
                            if ($src !== '') {
                                $firstSourceUrl = $src;
                                $sourceHtml = '<source src="' . $escapeAttr($src) . '">';
                            }
                        }

                        $fallbackHtml = '';
                        if ($firstSourceUrl !== '') {
                            $fallbackHtml = '<a href="' . $escapeAttr($firstSourceUrl) . '" target="_blank" rel="noopener noreferrer">' . $escape($fallbackVideoLabel) . '</a>';
                        }

                        return '<video ' . implode(' ', $parts) . '>' . $sourceHtml . $fallbackHtml . '</video>';
                    },
                    $normalized
                ) ?? $normalized;

                $normalized = preg_replace('~' . $emptyParagraphPattern . '\s*(<(?:img|video)\b)~iu', '$1', $normalized) ?? $normalized;
                $normalized = preg_replace('~(</video>|<img\b[^>]*>)\s*' . $emptyParagraphPattern . '~iu', '$1', $normalized) ?? $normalized;
                $normalized = preg_replace('#<p\b[^>]*>​</p>#u', '', $normalized) ?? $normalized;
                $normalized = preg_replace('#<p\b[^>]*>​<br\s*/?></p>#iu', '', $normalized) ?? $normalized;

                return $normalized;
            };

            $normalizeListItemMarkup = static function (string $html): string {
                $source = trim($html);
                if ($source === '' || stripos($source, '<li') === false) {
                    return $source;
                }

                $previousUseErrors = libxml_use_internal_errors(true);
                $dom = new \DOMDocument('1.0', 'UTF-8');
                $loaded = $dom->loadHTML(
                    '<?xml encoding="utf-8" ?><div id="pb-text-list-root">' . $source . '</div>',
                    LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
                );

                if ($loaded !== true) {
                    libxml_clear_errors();
                    libxml_use_internal_errors($previousUseErrors);
                    return $source;
                }

                $root = $dom->getElementById('pb-text-list-root');
                if (!$root instanceof \DOMElement) {
                    libxml_clear_errors();
                    libxml_use_internal_errors($previousUseErrors);
                    return $source;
                }

                $items = [];
                foreach ($root->getElementsByTagName('li') as $item) {
                    if ($item instanceof \DOMElement) {
                        $items[] = $item;
                    }
                }

                foreach ($items as $item) {
                    if (
                        $item->childNodes->length === 1
                        && $item->firstChild instanceof \DOMElement
                        && $item->firstChild->getAttribute('class') === 'pb-text-list-item-content'
                    ) {
                        continue;
                    }

                    $wrapper = $dom->createElement('div');
                    $wrapper->setAttribute('class', 'pb-text-list-item-content');

                    while ($item->firstChild !== null) {
                        $wrapper->appendChild($item->removeChild($item->firstChild));
                    }

                    $item->appendChild($wrapper);
                }

                $normalized = '';
                foreach ($root->childNodes as $child) {
                    $normalized .= $dom->saveHTML($child);
                }

                libxml_clear_errors();
                libxml_use_internal_errors($previousUseErrors);

                return $normalized !== '' ? $normalized : $source;
            };

            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(48, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $title = trim((string) ($settings['title'] ?? ''));
            $text = (string) ($settings['text'] ?? '');
            $showTitle = self::normalizeToggle($settings['showTitle'] ?? 'on', true);
            $align = self::normalizeAlign($settings['align'] ?? 'left');
            $legacyColor = self::normalizeColor((string) ($settings['color'] ?? ''));
            $safeHtml = trim($normalizeListItemMarkup($normalizeMediaHtml($text)));
            $safeId = self::blockId($context);

            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $bodyStyle = $resolveTextStyle($settings, 'bodyStyle', $align);
            if ($legacyColor !== '') {
                if (($titleStyle['color'] ?? '') === '') {
                    $titleStyle['color'] = $legacyColor;
                }
                if (($bodyStyle['color'] ?? '') === '') {
                    $bodyStyle['color'] = $legacyColor;
                }
            }

            $bodyListAlign = self::normalizeAlign($bodyStyle['align'] ?? $align);
            $bodyListStyle = self::normalizeTextStyleList($bodyStyle['list'] ?? 'none');
            $bodyListClass = $bodyListStyle === 'none' ? 'disc' : $bodyListStyle;

            $headerHtml = '';
            if ($showTitle && $title !== '') {
                $headerHtml = '<header class="pb-text-header">'
                    . $renderStyledText($title, 'h2', 'pb-text-title', $titleStyle)
                    . '</header>';
            }

            $html = '<section class="pb-text-block">'
                . $headerHtml
                . '<div class="pb-text-inner pb-text-list-align-' . $escapeAttr($bodyListAlign) . ' pb-text-list-style-' . $escapeAttr($bodyListClass) . '">' . $safeHtml . '</div>'
                . '</section>';

            $css = '';
            if ($safeId !== '') {
                $rules = self::buildWidgetDesignRules(
                    $safeId,
                    ['.pb-text-block'],
                    ['.pb-text-title', '.pb-text-inner', '.pb-text-inner *'],
                    $useCustomDesign,
                    $designSurfaceColor,
                    $designTextColor,
                    $designBorderStyle,
                    $designBorderWidth,
                    $designBorderColor,
                    $designRadius,
                    $designShadow
                );

                if ($showTitle && $title !== '') {
                    $rules = array_merge($rules, $buildTextStyleRules($safeId, '.pb-text-block .pb-text-title', $titleStyle));
                }
                $rules = array_merge($rules, $buildTextStyleRules($safeId, '.pb-text-block .pb-text-inner', $bodyStyle));

                foreach ($mediaRules as $mediaRule) {
                    $selector = trim((string) ($mediaRule['selector'] ?? ''));
                    $width = trim((string) ($mediaRule['width'] ?? ''));
                    if ($selector === '' || $width === '') {
                        continue;
                    }
                    $rules[] = self::blockSelector($safeId, '.pb-text-block .pb-text-inner ' . $selector) . '{width:' . $escapeAttr($width) . ';}';
                }

                $css = implode('', $rules);
            }

            return [
                'html' => $html,
                'css' => $css,
            ];
        };
    }
}
