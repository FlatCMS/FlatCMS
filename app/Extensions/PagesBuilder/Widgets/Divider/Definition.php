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

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'divider';
    }

    public static function definition(): array
    {
        return [
            'type' => 'divider',
            'label' => self::label('divider_widget_label'),
            'icon' => 'fas fa-minus',
            'category' => 'layout',
            'i18n_module' => 'Divider',
            'render' => 'render.php',
            'preview_handler' => 'divider',
            'assets' => [
                'css' => [
                    'css/divider.css',
                ],
                'preview_css' => [
                    'css/divider.css',
                ],
                'preview_js' => [
                    'js/divider-preview.js',
                ],
            ],
            'defaults' => [
                'color' => '#d1d5db',
                'weight' => 1,
                'length' => 100,
                'style' => 'solid',
                'align' => 'center',
                'useCustomDesign' => '',
                'designSurfaceColor' => '',
                'designTextColor' => '',
                'designBorderStyle' => 'inherit',
                'designBorderWidth' => 1,
                'designBorderColor' => '',
                'designRadius' => 0,
                'designShadow' => 'inherit',
            ],
            'fields' => array_merge(
                [
                    [
                        'key' => 'weight',
                        'label' => self::label('divider_field_weight'),
                        'type' => 'range',
                        'group' => 'layout',
                        'min' => 1,
                        'max' => 8,
                        'step' => 1,
                    ],
                    [
                        'key' => 'length',
                        'label' => self::label('divider_field_length'),
                        'type' => 'range',
                        'group' => 'layout',
                        'min' => 10,
                        'max' => 100,
                        'step' => 5,
                    ],
                    [
                        'key' => 'color',
                        'label' => self::label('divider_field_color'),
                        'type' => 'color',
                        'group' => 'layout',
                    ],
                    [
                        'key' => 'style',
                        'label' => self::label('divider_field_style'),
                        'type' => 'select',
                        'group' => 'layout',
                        'options' => ['solid', 'dashed', 'dotted'],
                        'optionLabels' => [
                            'solid' => self::label('divider_option_style_solid'),
                            'dashed' => self::label('divider_option_style_dashed'),
                            'dotted' => self::label('divider_option_style_dotted'),
                        ],
                    ],
                    [
                        'key' => 'align',
                        'label' => self::label('divider_field_align'),
                        'type' => 'select',
                        'control' => 'align',
                        'group' => 'layout',
                        'options' => ['left', 'center', 'right'],
                        'optionLabels' => [
                            'left' => self::label('divider_option_align_left'),
                            'center' => self::label('divider_option_align_center'),
                            'right' => self::label('divider_option_align_right'),
                        ],
                    ],
                ],
                self::designFields('divider')
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
                'key' => 'designTextColor',
                'label' => self::label($prefix . '_field_design_text_color'),
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
