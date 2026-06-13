<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Divider;

use App\Extensions\PagesBuilder\Support\AbstractWidgetRenderer;

final class Renderer extends AbstractWidgetRenderer
{
    protected static function renderer(): callable
    {
        return static function (array $settings, array $context): array {
            $color = self::normalizeColor((string) ($settings['color'] ?? ''));

            $weight = (int) ($settings['weight'] ?? 1);
            $weight = max(1, min(8, $weight));

            $style = strtolower(trim((string) ($settings['style'] ?? 'solid')));
            if (!in_array($style, ['solid', 'dashed', 'dotted'], true)) {
                $style = 'solid';
            }

            $length = (int) ($settings['length'] ?? 100);
            $length = max(10, min(100, $length));
            $length = (int) (round($length / 5) * 5);
            $length = max(10, min(100, $length));

            $align = strtolower(trim((string) ($settings['align'] ?? 'center')));
            if (!in_array($align, ['left', 'center', 'right'], true)) {
                $align = 'center';
            }

            $html = '<div class="pb-divider" data-divider-mode="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"'
                . ' data-divider-weight="' . htmlspecialchars((string) $weight, ENT_QUOTES, 'UTF-8') . '"'
                . ' data-divider-length="' . htmlspecialchars((string) $length, ENT_QUOTES, 'UTF-8') . '"'
                . ' data-divider-align="' . htmlspecialchars($align, ENT_QUOTES, 'UTF-8') . '"'
                . ($color !== '' ? ' data-divider-color="' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . '"' : '') . '>'
                . '<span class="pb-divider-line" aria-hidden="true"></span>'
                . '</div>';

            $safeId = self::blockId($context);
            $css = '';
            if ($safeId !== '') {
                $selector = self::blockSelector($safeId, '.pb-divider-line');
                $css = $selector . '{'
                    . 'border-top-width:' . htmlspecialchars((string) $weight, ENT_QUOTES, 'UTF-8') . 'px;'
                    . 'width:' . htmlspecialchars((string) $length, ENT_QUOTES, 'UTF-8') . '%;'
                    . '}';
                if ($color !== '') {
                    $css .= $selector . '{'
                        . 'border-top-color:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . ';'
                        . '}';
                }
            }
            $css .= self::designCss($settings, $context);

            return [
                'html' => $html,
                'css' => $css,
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

        $rules = ['border-radius:' . self::normalizeInt($settings['designRadius'] ?? 0, 0, 0, 48) . 'px;'];
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

        $css = self::blockSelector($safeId, '.pb-divider') . '{' . implode('', $rules) . '}';
        $lineColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
        if ($lineColor !== '') {
            $css .= self::blockSelector($safeId, '.pb-divider-line') . '{border-top-color:' . $lineColor . ';}';
        }

        return $css;
    }
}
