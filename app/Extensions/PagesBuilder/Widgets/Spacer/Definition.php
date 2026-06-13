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

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'spacer';
    }

    public static function definition(): array
    {
        return [
            'type' => 'spacer',
            'label' => self::label('spacer_widget_label'),
            'icon' => 'fas fa-arrows-alt-v',
            'category' => 'layout',
            'i18n_module' => 'Spacer',
            'render' => 'render.php',
            'preview_handler' => 'spacer',
            'assets' => [
                'css' => [
                    'css/spacer.css',
                ],
                'preview_css' => [
                    'css/spacer.css',
                ],
                'preview_js' => [
                    'js/spacer-preview.js',
                ],
            ],
            'defaults' => [
                'height' => 32,
                'useCustomDesign' => '',
                'designSurfaceColor' => '',
                'designBorderStyle' => 'inherit',
                'designBorderWidth' => 1,
                'designBorderColor' => '',
                'designRadius' => 0,
                'designShadow' => 'inherit',
            ],
            'fields' => array_merge(
                [
                    [
                        'key' => 'height',
                        'label' => self::label('spacer_field_height'),
                        'type' => 'number',
                        'group' => 'layout',
                        'min' => 8,
                        'max' => 240,
                        'step' => 1,
                    ],
                ],
                self::designFields('spacer')
            ),
        ];
    }

    /**
     * @return array{__label: true, key: string, fallback: string}
     */
    private static function label(string $key): array
    {
        return [
            '__label' => true,
            'key' => $key,
            'fallback' => '',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function designFields(string $prefix): array
    {
        $surfaceSection = [
            'section' => 'surface',
            'sectionLabel' => self::label($prefix . '_section_surface'),
        ];
        $condition = [
            'field' => 'useCustomDesign',
            'operator' => 'equals',
            'value' => 'on',
        ];

        return [
            [
                'key' => 'useCustomDesign',
                'label' => self::label($prefix . '_field_use_custom_design'),
                'type' => 'checkbox',
                'group' => 'design',
                ...$surfaceSection,
                'sectionHelp' => self::label($prefix . '_section_surface_help'),
            ],
            [
                'key' => 'designSurfaceColor',
                'label' => self::label($prefix . '_field_design_surface_color'),
                'type' => 'color',
                'group' => 'design',
                ...$surfaceSection,
                'condition' => $condition,
            ],
            [
                'key' => 'designBorderStyle',
                'label' => self::label($prefix . '_field_design_border_style'),
                'type' => 'select',
                'options' => ['inherit', 'none', 'solid', 'dashed', 'dotted'],
                'optionLabels' => [
                    'inherit' => self::label($prefix . '_option_design_border_style_inherit'),
                    'none' => self::label($prefix . '_option_design_border_style_none'),
                    'solid' => self::label($prefix . '_option_design_border_style_solid'),
                    'dashed' => self::label($prefix . '_option_design_border_style_dashed'),
                    'dotted' => self::label($prefix . '_option_design_border_style_dotted'),
                ],
                'group' => 'design',
                ...$surfaceSection,
                'condition' => $condition,
            ],
            [
                'key' => 'designBorderWidth',
                'label' => self::label($prefix . '_field_design_border_width'),
                'type' => 'number',
                'group' => 'design',
                ...$surfaceSection,
                'min' => 0,
                'max' => 8,
                'step' => 1,
                'condition' => $condition,
            ],
            [
                'key' => 'designBorderColor',
                'label' => self::label($prefix . '_field_design_border_color'),
                'type' => 'color',
                'group' => 'design',
                ...$surfaceSection,
                'condition' => $condition,
            ],
            [
                'key' => 'designRadius',
                'label' => self::label($prefix . '_field_design_radius'),
                'type' => 'number',
                'group' => 'design',
                ...$surfaceSection,
                'min' => 0,
                'max' => 48,
                'step' => 1,
                'condition' => $condition,
            ],
            [
                'key' => 'designShadow',
                'label' => self::label($prefix . '_field_design_shadow'),
                'type' => 'select',
                'options' => ['inherit', 'none', 'soft', 'medium', 'strong'],
                'optionLabels' => [
                    'inherit' => self::label($prefix . '_option_design_shadow_inherit'),
                    'none' => self::label($prefix . '_option_design_shadow_none'),
                    'soft' => self::label($prefix . '_option_design_shadow_soft'),
                    'medium' => self::label($prefix . '_option_design_shadow_medium'),
                    'strong' => self::label($prefix . '_option_design_shadow_strong'),
                ],
                'group' => 'design',
                ...$surfaceSection,
                'condition' => $condition,
            ],
        ];
    }
}
