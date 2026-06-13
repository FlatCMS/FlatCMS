<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Image;

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

            $src = trim((string) ($settings['src'] ?? ''));
            $safeSrc = $src !== '' ? trim((string) $resolveImage($src)) : '';
            if ($safeSrc === '') {
                return [
                    'html' => '',
                    'css' => '',
                ];
            }

            $alt = trim((string) ($settings['altText'] ?? $settings['alt'] ?? ''));
            if ($alt === '') {
                $alt = self::inferAltFromSource($src);
            }

            $align = self::normalizeAlign((string) ($settings['align'] ?? 'center'), 'center');
            $width = self::normalizeWidth($settings['widthPercent'] ?? ($settings['width'] ?? 100));
            $textPlacement = self::normalizePlacement($settings['textPlacement'] ?? 'below');
            $title = trim((string) ($settings['title'] ?? ''));
            $text = trim((string) ($settings['text'] ?? ''));
            $titleStyle = self::resolveTextStyle($settings, 'titleStyle', $align);
            $bodyStyle = self::resolveTextStyle($settings, 'bodyStyle', (string) ($titleStyle['align'] ?? $align));

            $showButton = self::normalizeToggle($settings['showButton'] ?? '', false);
            $buttonLabel = $showButton ? trim((string) ($settings['buttonLabel'] ?? '')) : '';
            $buttonUrl = self::sanitizeUrl((string) ($settings['buttonUrl'] ?? ''));
            $buttonTarget = self::normalizeTarget($settings['buttonTarget'] ?? '_self');
            $buttonVariant = self::normalizeButtonVariant($settings['buttonVariant'] ?? 'primary');
            $buttonPlacement = self::normalizePlacement($settings['buttonPlacement'] ?? 'below');
            $buttonAlign = self::normalizeAlign((string) ($settings['buttonAlign'] ?? 'center'), 'center');
            $buttonVerticalAlign = self::normalizeVerticalAlign($settings['buttonVerticalAlign'] ?? 'center');

            $titleHtml = self::renderStyledText($title, 'h3', 'fc-image-block-title', $titleStyle, $escape, $escapeAttr);
            $textHtml = self::renderStyledParagraphs($text, 'fc-image-block-body', $bodyStyle, $escape, $escapeAttr);
            $copyHtml = ($titleHtml !== '' || $textHtml !== '')
                ? '<div class="fc-image-block-copy">' . $titleHtml . $textHtml . '</div>'
                : '';
            $buttonHtml = self::renderButton($buttonLabel, $buttonUrl, $buttonTarget, $buttonVariant, $escape, $escapeAttr);

            $aboveSlot = self::renderSlot('above', $textPlacement, $copyHtml, $buttonPlacement, $buttonHtml, $escapeAttr);
            $belowSlot = self::renderSlot('below', $textPlacement, $copyHtml, $buttonPlacement, $buttonHtml, $escapeAttr);
            $overlayHtml = self::renderOverlay($textPlacement, $copyHtml, $buttonPlacement, $buttonHtml, $buttonAlign, $buttonVerticalAlign);

            $html = '<section class="fc-image-block fc-image-block-align-' . $escapeAttr($align) . '">'
                . '<div class="fc-image-block-shell" data-fc-image-width="' . $escapeAttr((string) $width) . '">'
                . $aboveSlot
                . '<div class="fc-image-block-media">'
                . '<img class="fc-image-block-img" src="' . $escapeAttr($safeSrc) . '" alt="' . $escapeAttr($alt) . '" loading="lazy" decoding="async">'
                . $overlayHtml
                . '</div>'
                . $belowSlot
                . '</div>'
                . '</section>';

            $css = self::buildCss(
                $settings,
                $context,
                $width,
                $titleStyle,
                $bodyStyle,
                $buttonPlacement,
                $buttonAlign,
                $buttonVerticalAlign
            );

            return [
                'html' => $html,
                'css' => $css,
            ];
        };
    }

    private static function normalizePlacement(mixed $raw): string
    {
        $value = strtolower(trim((string) $raw));
        return in_array($value, ['above', 'overlay', 'below'], true) ? $value : 'below';
    }

    private static function normalizeVerticalAlign(mixed $raw): string
    {
        $value = strtolower(trim((string) $raw));
        return in_array($value, ['top', 'center', 'bottom'], true) ? $value : 'center';
    }

    private static function normalizeTarget(mixed $raw): string
    {
        return (string) $raw === '_blank' ? '_blank' : '_self';
    }

    private static function normalizeButtonVariant(mixed $raw): string
    {
        $value = strtolower(trim((string) $raw));
        return in_array($value, ['primary', 'secondary', 'ghost'], true) ? $value : 'primary';
    }

    private static function normalizeWidth(mixed $raw): int
    {
        $value = filter_var($raw, FILTER_VALIDATE_INT);
        $width = is_int($value) ? $value : 100;
        return max(10, min(100, $width));
    }

    private static function inferAltFromSource(string $raw): string
    {
        $source = trim(str_replace('\\', '/', $raw));
        if ($source === '') {
            return '';
        }

        $basename = pathinfo($source, PATHINFO_FILENAME);
        $basename = is_string($basename) ? trim($basename) : '';
        if ($basename === '') {
            return '';
        }

        $normalized = preg_replace('/[_-]+/', ' ', $basename);
        $normalized = is_string($normalized) ? trim($normalized) : '';
        return $normalized;
    }

    private static function resolveTextStyle(array $source, string $prefix, string $fallbackAlign): array
    {
        $keyPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'textStyle';
        $iconPosition = strtolower(trim((string) ($source[$keyPrefix . 'IconPosition'] ?? 'start')));

        return [
            'align' => self::normalizeAlign((string) ($source[$keyPrefix . 'Align'] ?? $fallbackAlign)),
            'font' => self::normalizeTextStyleFont($source[$keyPrefix . 'Font'] ?? 'inherit'),
            'size' => self::normalizeTextStyleSize($source[$keyPrefix . 'Size'] ?? 'inherit'),
            'bold' => self::normalizeToggle($source[$keyPrefix . 'Bold'] ?? false),
            'italic' => self::normalizeToggle($source[$keyPrefix . 'Italic'] ?? false),
            'underline' => self::normalizeToggle($source[$keyPrefix . 'Underline'] ?? false),
            'color' => self::normalizeColor((string) ($source[$keyPrefix . 'Color'] ?? '')),
            'icon' => self::sanitizeIconClass($source[$keyPrefix . 'Icon'] ?? ''),
            'iconPosition' => in_array($iconPosition, ['start', 'end'], true) ? $iconPosition : 'start',
        ];
    }

    private static function injectTextIcon(string $content, array $style, callable $escapeAttr): string
    {
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
    }

    private static function renderStyledText(string $text, string $tag, string $className, array $style, callable $escape, callable $escapeAttr): string
    {
        $value = trim($text);
        if ($value === '') {
            return '';
        }

        $content = '<span class="pb-styled-text-content">' . $escape($value) . '</span>';
        $decorated = self::injectTextIcon($content, $style, $escapeAttr);
        $align = self::normalizeAlign((string) ($style['align'] ?? 'left'));

        return '<' . $tag . ' class="' . $escapeAttr($className . ' fc-image-block-text-' . $align) . '">' . $decorated . '</' . $tag . '>';
    }

    private static function renderStyledParagraphs(string $text, string $className, array $style, callable $escape, callable $escapeAttr): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($normalized === '') {
            return '';
        }

        $chunks = preg_split('/\n\s*\n/u', $normalized) ?: [];
        $paragraphs = [];
        foreach ($chunks as $chunk) {
            $line = trim((string) $chunk);
            if ($line === '') {
                continue;
            }
            $paragraphs[] = '<p class="fc-image-block-body-paragraph"><span class="pb-styled-text-content">'
                . nl2br($escape($line), false)
                . '</span></p>';
        }

        if ($paragraphs === []) {
            return '';
        }

        $content = '<div class="pb-styled-text-content-rich">' . implode('', $paragraphs) . '</div>';
        $decorated = self::injectTextIcon($content, $style, $escapeAttr);

        $align = self::normalizeAlign((string) ($style['align'] ?? 'left'));

        return '<div class="' . $escapeAttr($className . ' fc-image-block-text-' . $align) . '">' . $decorated . '</div>';
    }

    private static function renderButton(string $label, string $url, string $target, string $variant, callable $escape, callable $escapeAttr): string
    {
        $safeLabel = trim($label);
        if ($safeLabel === '') {
            return '';
        }

        $class = match ($variant) {
            'secondary' => 'btn btn-secondary pb-btn pb-btn-secondary',
            'ghost' => 'btn btn-ghost pb-btn pb-btn-ghost',
            default => 'btn btn-primary pb-btn pb-btn-primary',
        };

        if ($url !== '') {
            $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
            return '<a class="' . $class . '" href="' . $escapeAttr($url) . '" target="' . $escapeAttr($target) . '"' . $rel . '>' . $escape($safeLabel) . '</a>';
        }

        return '<span class="' . $class . ' is-static" aria-disabled="true">' . $escape($safeLabel) . '</span>';
    }

    private static function renderSlot(string $slot, string $textPlacement, string $copyHtml, string $buttonPlacement, string $buttonHtml, callable $escapeAttr): string
    {
        $parts = [];
        if ($textPlacement === $slot && $copyHtml !== '') {
            $parts[] = '<div class="fc-image-block-slot-copy fc-image-block-slot-copy-' . $escapeAttr($slot) . '">' . $copyHtml . '</div>';
        }
        if ($buttonPlacement === $slot && $buttonHtml !== '') {
            $parts[] = '<div class="fc-image-block-slot-actions fc-image-block-slot-actions-' . $escapeAttr($slot) . '"><div class="fc-image-block-actions">' . $buttonHtml . '</div></div>';
        }

        if ($parts === []) {
            return '';
        }

        return '<div class="fc-image-block-slot fc-image-block-slot-' . $escapeAttr($slot) . '">' . implode('', $parts) . '</div>';
    }

    private static function renderOverlay(string $textPlacement, string $copyHtml, string $buttonPlacement, string $buttonHtml, string $buttonAlign, string $buttonVerticalAlign): string
    {
        $hasOverlayCopy = $textPlacement === 'overlay' && $copyHtml !== '';
        $hasOverlayButton = $buttonPlacement === 'overlay' && $buttonHtml !== '';
        $actionClass = self::overlayActionClass($buttonAlign, $buttonVerticalAlign);

        if ($hasOverlayCopy && $hasOverlayButton) {
            return '<div class="fc-image-block-overlay fc-image-block-overlay-combined">'
                . '<div class="fc-image-block-overlay-layer fc-image-block-overlay-layer-copy"><div class="fc-image-block-overlay-inner fc-image-block-overlay-inner-copy">' . $copyHtml . '</div></div>'
                . '<div class="fc-image-block-overlay-layer fc-image-block-overlay-layer-actions ' . htmlspecialchars($actionClass, ENT_QUOTES, 'UTF-8') . '"><div class="fc-image-block-overlay-inner fc-image-block-overlay-inner-actions"><div class="fc-image-block-actions">' . $buttonHtml . '</div></div></div>'
                . '</div>'
                ;
        }

        $parts = [];
        if ($hasOverlayCopy) {
            $parts[] = '<div class="fc-image-block-overlay fc-image-block-overlay-copy"><div class="fc-image-block-overlay-inner fc-image-block-overlay-inner-copy">' . $copyHtml . '</div></div>';
        }
        if ($hasOverlayButton) {
            $parts[] = '<div class="fc-image-block-overlay fc-image-block-overlay-actions ' . htmlspecialchars($actionClass, ENT_QUOTES, 'UTF-8') . '"><div class="fc-image-block-overlay-inner fc-image-block-overlay-inner-actions"><div class="fc-image-block-actions">' . $buttonHtml . '</div></div></div>';
        }

        return implode('', $parts);
    }

    private static function overlayActionClass(string $buttonAlign, string $buttonVerticalAlign): string
    {
        return 'fc-image-block-overlay-h-' . self::normalizeAlign($buttonAlign, 'center')
            . ' fc-image-block-overlay-v-' . self::normalizeVerticalAlign($buttonVerticalAlign);
    }

    private static function buildCss(
        array $settings,
        array $context,
        int $width,
        array $titleStyle,
        array $bodyStyle,
        string $buttonPlacement,
        string $buttonAlign,
        string $buttonVerticalAlign
    ): string {
        $safeId = self::blockId($context);
        if ($safeId === '') {
            return '';
        }

        $css = [
            self::blockSelector($safeId, '.fc-image-block-shell') . '{width:' . htmlspecialchars((string) $width, ENT_QUOTES, 'UTF-8') . '%;}',
        ];

        $horizontalAlign = match ($buttonAlign) {
            'left' => 'flex-start',
            'right' => 'flex-end',
            default => 'center',
        };
        $verticalAlign = match ($buttonVerticalAlign) {
            'top' => 'flex-start',
            'bottom' => 'flex-end',
            default => 'center',
        };

        if ($buttonPlacement === 'overlay') {
            $css[] = self::blockSelector($safeId, '.fc-image-block-overlay-actions') . ','
                . self::blockSelector($safeId, '.fc-image-block-overlay-layer-actions')
                . '{justify-content:' . htmlspecialchars($verticalAlign, ENT_QUOTES, 'UTF-8') . ';align-items:' . htmlspecialchars($horizontalAlign, ENT_QUOTES, 'UTF-8') . ';}';
        } else {
            $css[] = self::blockSelector($safeId, '.fc-image-block-slot-actions-' . htmlspecialchars($buttonPlacement, ENT_QUOTES, 'UTF-8')) . '{justify-content:' . htmlspecialchars($horizontalAlign, ENT_QUOTES, 'UTF-8') . ';}';
        }

        if (self::normalizeToggle($settings['useCustomDesign'] ?? '', false)) {
            $rules = [
                'overflow:hidden;',
                'border-radius:' . self::normalizeInt($settings['designRadius'] ?? 12, 12, 0, 48) . 'px;',
            ];

            $surfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            if ($surfaceColor !== '') {
                $rules[] = 'background:' . $surfaceColor . ';';
            }

            $borderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $borderWidth = self::normalizeInt($settings['designBorderWidth'] ?? 1, 1, 0, 8);
            if ($borderStyle !== 'inherit') {
                $rules[] = 'border-style:' . $borderStyle . ';';
                $rules[] = 'border-width:' . $borderWidth . 'px;';
            }

            $borderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            if ($borderColor !== '') {
                $rules[] = 'border-color:' . $borderColor . ';';
                if ($borderStyle === 'inherit') {
                    $rules[] = 'border-width:' . $borderWidth . 'px;';
                }
            }

            $shadow = self::shadowValue(self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit'));
            if ($shadow !== '') {
                $rules[] = 'box-shadow:' . $shadow . ';';
            }

            $css[] = self::blockSelector($safeId, '.fc-image-block-media') . '{' . implode('', $rules) . '}';
            $css[] = self::blockSelector($safeId, '.fc-image-block-img') . '{display:block;width:100%;height:auto;border-radius:inherit;}';

            $textColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            if ($textColor !== '') {
                $css[] = self::blockSelector($safeId, '.fc-image-block-copy') . '{color:' . htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8') . ';}';
            }
        }

        $css = array_merge($css, self::buildTextStyleRules($safeId, '.fc-image-block-title', $titleStyle));
        $css = array_merge($css, self::buildTextStyleRules($safeId, '.fc-image-block-body', $bodyStyle));

        return implode('', $css);
    }

    private static function buildTextStyleRules(string $safeId, string $selector, array $style): array
    {
        if ($safeId === '') {
            return [];
        }

        $scopedSelector = self::blockSelector($safeId, $selector);
        $align = self::normalizeAlign((string) ($style['align'] ?? 'left'));
        $justify = match ($align) {
            'center' => 'center',
            'right' => 'flex-end',
            default => 'flex-start',
        };

        $rules = [
            'text-align:' . htmlspecialchars($align, ENT_QUOTES, 'UTF-8') . ';',
            'justify-content:' . htmlspecialchars($justify, ENT_QUOTES, 'UTF-8') . ';',
        ];

        $color = trim((string) ($style['color'] ?? ''));
        if ($color !== '') {
            $rules[] = 'color:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . ';';
        }

        $fontRule = self::widgetTextFontRule((string) ($style['font'] ?? 'inherit'));
        if ($fontRule !== '') {
            $rules[] = $fontRule;
        }

        $size = (string) ($style['size'] ?? 'inherit');
        if ($size !== '' && $size !== 'inherit') {
            $rules[] = 'font-size:' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . ';';
        }

        $css = [$scopedSelector . '{' . implode('', $rules) . '}'];

        $contentRules = [];
        if (!empty($style['bold'])) {
            $contentRules[] = 'font-weight:700;';
        }
        if (!empty($style['italic'])) {
            $contentRules[] = 'font-style:italic;';
        }
        if (!empty($style['underline'])) {
            $contentRules[] = 'text-decoration:underline;';
        }
        if ($contentRules !== []) {
            $css[] = $scopedSelector . ' .pb-styled-text-content{' . implode('', $contentRules) . '}';
        }

        if ($color !== '') {
            $escapedColor = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
            $css[] = $scopedSelector . ' .pb-styled-text-content,' . $scopedSelector . ' .pb-styled-text-icon{color:' . $escapedColor . ';}';
        }

        return $css;
    }

}
