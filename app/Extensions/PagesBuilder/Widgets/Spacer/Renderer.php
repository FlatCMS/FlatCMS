<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Spacer;

use App\Extensions\PagesBuilder\Support\AbstractWidgetRenderer;

final class Renderer extends AbstractWidgetRenderer
{
    protected static function renderer(): callable
    {
        return static function (array $settings, array $context): array {
            $height = (int) ($settings['height'] ?? 32);
            $height = max(8, min(240, $height));

            $html = '<div class="pb-spacer" data-spacer-height="' . htmlspecialchars((string) $height, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></div>';

            $safeId = self::blockId($context);
            $css = '';
            if ($safeId !== '') {
                $css = self::blockSelector($safeId, '.pb-spacer') . '{height:' . htmlspecialchars((string) $height, ENT_QUOTES, 'UTF-8') . 'px;}';
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

        return self::blockSelector($safeId, '.pb-spacer') . '{'
            . implode('', $rules)
            . '}';
    }
}
