<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Newsletter;

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
            $translate = static fn(string $key, string $fallback = ''): string => PageBuilderWidgetLocaleService::translate('Newsletter', $key, $fallback);

            $title = trim((string) ($settings['title'] ?? $translate('footer_widget_newsletter_default_title')));
            $description = trim((string) ($settings['description'] ?? $translate('footer_widget_newsletter_default_description')));
            $placeholder = trim((string) ($settings['placeholder'] ?? $translate('footer_widget_newsletter_default_placeholder')));
            $buttonLabel = trim((string) ($settings['buttonLabel'] ?? $translate('footer_widget_newsletter_default_button')));
            $action = self::sanitizeUrl((string) ($settings['action'] ?? '/newsletter'));
            if ($action === '') {
                $action = '#';
            }
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));

            $html = '<section class="pb-newsletter-widget pb-newsletter-widget--align-' . $escapeAttr($align) . '"><div class="pb-newsletter-widget-shell">';
            if ($title !== '') {
                $html .= '<strong class="pb-newsletter-widget-title">' . $escape($title) . '</strong>';
            }
            if ($description !== '') {
                $html .= '<p class="pb-newsletter-widget-description">' . $escape($description) . '</p>';
            }
            $html .= '<form class="pb-form pb-form-newsletter pb-newsletter-widget-form" action="' . $escapeAttr($action) . '" method="post">';
            $html .= '<label class="pb-sr-only">' . $escape($placeholder) . '</label>';
            $html .= '<input type="email" name="email" class="form-input pb-input pb-newsletter-widget-input" placeholder="' . $escapeAttr($placeholder) . '" autocomplete="email" required>';
            $html .= '<button type="submit" class="btn btn-primary pb-btn pb-btn-primary pb-newsletter-widget-button">' . $escape($buttonLabel) . '</button>';
            $html .= '</form>';
            $html .= '</div></section>';

            return [
                'html' => $html,
                'css' => self::designCss($settings, $context),
            ];
        };
    }

    private static function designCss(array $settings, array $context): string
    {
        if (!self::normalizeToggle($settings['useCustomDesign'] ?? '', false)) {
            return '';
        }

        $safeId = self::blockId($context);
        if ($safeId === '') {
            return '';
        }

        $rules = [
            'padding:1.25rem;',
            'border-radius:' . self::normalizeInt($settings['designRadius'] ?? 20, 20, 0, 48) . 'px;',
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

        $css = [
            self::blockSelector($safeId, '.pb-newsletter-widget-shell') . '{' . implode('', $rules) . '}',
        ];

        $textColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
        if ($textColor !== '') {
            $escapedColor = htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8');
            $css[] = self::blockSelector($safeId, '.pb-newsletter-widget-shell') . ','
                . self::blockSelector($safeId, '.pb-newsletter-widget-title') . ','
                . self::blockSelector($safeId, '.pb-newsletter-widget-description')
                . '{color:' . $escapedColor . ';}';
            $css[] = self::blockSelector($safeId, '.pb-newsletter-widget-input') . '{color:' . $escapedColor . ';}';
            $css[] = self::blockSelector($safeId, '.pb-newsletter-widget-input::placeholder') . '{color:' . $escapedColor . ';opacity:.72;}';
        }

        return implode('', $css);
    }
}
