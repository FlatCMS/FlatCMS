<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\FaqAccordion;

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
            $translate = static fn(string $key, string $fallback = ''): string => PageBuilderWidgetLocaleService::translate('FaqAccordion', $key, $fallback);

            $parseRepeaterLines = static function (mixed $raw, string $delimiter = "\n---\n"): array {
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
                return max(1, min(2, $value > 0 ? $value : 1));
            };

            $resolveTextStyle = static function (array $source, string $prefix, string $fallbackAlign): array {
                $keyPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'textStyle';
                $read = static function (array $settings, string $key): mixed {
                    return array_key_exists($key, $settings) ? $settings[$key] : null;
                };

                $iconPosition = strtolower(trim((string) ($read($source, $keyPrefix . 'IconPosition') ?? 'start')));

                return [
                    'align' => self::normalizeAlign((string) ($read($source, $keyPrefix . 'Align') ?? ''), $fallbackAlign),
                    'font' => self::normalizeTextStyleFont((string) ($read($source, $keyPrefix . 'Font') ?? 'inherit')),
                    'size' => self::normalizeTextStyleSize((string) ($read($source, $keyPrefix . 'Size') ?? 'inherit')),
                    'bold' => self::normalizeToggle($read($source, $keyPrefix . 'Bold') ?? false),
                    'italic' => self::normalizeToggle($read($source, $keyPrefix . 'Italic') ?? false),
                    'underline' => self::normalizeToggle($read($source, $keyPrefix . 'Underline') ?? false),
                    'color' => self::normalizeColor((string) ($read($source, $keyPrefix . 'Color') ?? '')),
                    'list' => self::normalizeTextStyleList((string) ($read($source, $keyPrefix . 'List') ?? 'none')),
                    'icon' => self::sanitizeIconClass((string) ($read($source, $keyPrefix . 'Icon') ?? '')),
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
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-content-rich ul{list-style-type:' . $escapeAttr($listStyle) . ';}';
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

            $title = trim((string) ($settings['title'] ?? $translate('faq_accordion_default_title')));
            $subtitle = trim((string) ($settings['subtitle'] ?? $translate('faq_accordion_default_subtitle')));
            $questions = $parseRepeaterLines($settings['questions'] ?? $translate('faq_accordion_default_questions'));
            $answers = $parseRepeaterLines($settings['answers'] ?? $translate('faq_accordion_default_answers'));
            $showHeader = self::normalizeToggle($settings['showHeader'] ?? 'on', true);
            $openFirst = self::normalizeToggle($settings['openFirst'] ?? 'on', true);
            $align = self::normalizeAlign($settings['align'] ?? 'left', 'left');
            $variant = $normalizeVariant($settings['variant'] ?? 'subtle');
            $columns = $normalizeColumns($settings['columns'] ?? 1);

            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $subtitleStyle = $resolveTextStyle($settings, 'subtitleStyle', $titleStyle['align'] ?? $align);
            $questionStyle = $resolveTextStyle($settings, 'questionStyle', $align);
            $answerStyle = $resolveTextStyle($settings, 'answerStyle', $questionStyle['align'] ?? $align);
            $safeId = self::blockId($context);

            $count = min(max(count($questions), count($answers), 1), 12);
            $itemsHtml = '';
            for ($index = 0; $index < $count; $index++) {
                $question = trim((string) ($questions[$index] ?? ''));
                $answer = trim((string) ($answers[$index] ?? ''));

                if ($question === '' && $answer === '') {
                    continue;
                }

                $cardClass = match ($variant) {
                    'strong' => 'pb-card pb-card-strong',
                    'dashed' => 'pb-card pb-card-subtle',
                    default => 'pb-card pb-card-subtle',
                };
                $isOpen = $openFirst && $index === 0;
                $questionLabel = $question !== '' ? $question : $translate('faq_accordion_fallback_question');
                $answerHtml = $answer !== '' ? nl2br($escape($answer), false) : '';
                $itemBaseId = ($safeId !== '' ? $safeId : 'faq-accordion') . '-' . ($index + 1);
                $toggleId = 'faq-accordion-toggle-' . $itemBaseId;
                $panelId = 'faq-accordion-panel-' . $itemBaseId;

                $itemsHtml .= '<article class="pb-faq-accordion-item ' . $escapeAttr($cardClass) . ($isOpen ? ' is-active' : '') . '" data-faq-accordion-item>';
                $itemsHtml .= '<button type="button" class="pb-faq-accordion-toggle" id="' . $escapeAttr($toggleId) . '" aria-controls="' . $escapeAttr($panelId) . '" aria-expanded="' . ($isOpen ? 'true' : 'false') . '">';
                $itemsHtml .= '<span class="pb-faq-accordion-icon pb-faq-accordion-icon-plus" aria-hidden="true">+</span>';
                $itemsHtml .= '<span class="pb-faq-accordion-icon pb-faq-accordion-icon-minus" aria-hidden="true">−</span>';
                $itemsHtml .= $renderStyledText($questionLabel, 'span', 'pb-faq-accordion-question-label', $questionStyle);
                $itemsHtml .= '</button>';
                $itemsHtml .= '<div class="pb-faq-accordion-panel" id="' . $escapeAttr($panelId) . '" role="region" aria-labelledby="' . $escapeAttr($toggleId) . '"' . ($isOpen ? '' : ' hidden') . '>';
                $itemsHtml .= '<div class="pb-faq-accordion-panel-inner">';
                if ($answerHtml !== '') {
                    $itemsHtml .= $renderStyledHtml($answerHtml, 'div', 'pb-faq-accordion-answer-copy', $answerStyle);
                }
                $itemsHtml .= '</div></div></article>';
            }

            if ($itemsHtml === '') {
                $itemsHtml = '<div class="pb-empty">' . $escape($translate('faq_accordion_empty')) . '</div>';
            }

            $headerHtml = '';
            if ($showHeader && ($title !== '' || $subtitle !== '')) {
                $headerHtml .= '<header class="pb-faq-accordion-header">';
                $headerHtml .= $renderStyledText($title, 'h2', 'pb-faq-accordion-title', $titleStyle);
                $headerHtml .= $renderStyledText($subtitle, 'p', 'pb-faq-accordion-subtitle', $subtitleStyle);
                $headerHtml .= '</header>';
            }

            $html = '<section class="pb-faq-accordion pb-faq-accordion-variant-' . $escapeAttr($variant) . ' pb-faq-accordion-align-' . $escapeAttr($align) . '">'
                . $headerHtml
                . '<div class="pb-faq-accordion-grid">'
                . $itemsHtml
                . '</div>'
                . '</section>';

            $css = [];
            if ($safeId !== '') {
                $css[] = self::blockSelector($safeId, '.pb-faq-accordion') . '{--pb-faq-columns:' . $escapeAttr((string) $columns) . ';}';
                $css = array_merge($css, self::buildWidgetDesignRules(
                    $safeId,
                    ['.pb-faq-accordion-item', '.pb-faq-accordion-item:hover'],
                    ['.pb-faq-accordion-title', '.pb-faq-accordion-subtitle', '.pb-faq-accordion-question-label', '.pb-faq-accordion-answer-copy', '.pb-faq-accordion-answer-copy *'],
                    $useCustomDesign,
                    $designSurfaceColor,
                    $designTextColor,
                    $designBorderStyle,
                    $designBorderWidth,
                    $designBorderColor,
                    $designRadius,
                    $designShadow
                ));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-faq-accordion-title', $titleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-faq-accordion-subtitle', $subtitleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-faq-accordion-question-label', $questionStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-faq-accordion-answer-copy', $answerStyle));
            }

            return [
                'html' => $html,
                'css' => implode("\n", $css),
            ];
        };
    }
}
