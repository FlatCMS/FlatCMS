<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Button;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'button';
    }

    public static function definition(): array
    {
        return [
            'type' => 'button',
            'label' => self::label('footer_widget_button_label'),
            'icon' => 'fas fa-link',
            'category' => 'navigation',
            'i18n_module' => 'Button',
            'render' => 'render.php',
            'preview_handler' => 'button',
            'assets' => [
                'css' => [
                    'css/button.css',
                ],
                'preview_css' => [
                    'css/button.css',
                ],
                'preview_js' => [
                    'js/button-preview.js',
                ],
            ],
            'defaults' => [
                'showButton' => 'on',
                'label' => self::label('footer_widget_button_default_label'),
                'url' => '#',
                'target' => '_self',
                'variant' => 'primary',
                'icon' => '',
                'iconPosition' => 'left',
                'align' => 'left',
                'useCustomDesign' => '',
                'designSurfaceColor' => '',
                'designTextColor' => '',
                'designBorderStyle' => 'inherit',
                'designBorderWidth' => 1,
                'designBorderColor' => '',
                'designRadius' => 12,
                'designShadow' => 'inherit',
            ],
            'fields' => array_merge(
                [
                    [
                        'key' => 'label',
                        'label' => self::label('footer_widget_button_field_label'),
                        'type' => 'text',
                        'group' => 'content',
                    ],
                    [
                        'key' => 'showButton',
                        'label' => self::label('footer_widget_button_field_show_button'),
                        'type' => 'checkbox',
                        'group' => 'navigation',
                    ],
                    [
                        'key' => 'url',
                        'label' => self::label('footer_widget_button_field_url'),
                        'type' => 'url',
                        'group' => 'navigation',
                    ],
                    [
                        'key' => 'target',
                        'label' => self::label('footer_widget_button_field_target'),
                        'type' => 'select',
                        'control' => 'choice',
                        'group' => 'navigation',
                        'options' => ['_self', '_blank'],
                        'optionLabels' => [
                            '_self' => self::label('footer_widget_button_option_target_self'),
                            '_blank' => self::label('footer_widget_button_option_target_blank'),
                        ],
                    ],
                    [
                        'key' => 'icon',
                        'label' => self::label('footer_widget_button_field_icon'),
                        'type' => 'text',
                        'group' => 'media',
                        'iconPicker' => true,
                    ],
                    [
                        'key' => 'variant',
                        'label' => self::label('footer_widget_button_field_variant'),
                        'type' => 'select',
                        'control' => 'choice',
                        'group' => 'layout',
                        'options' => ['primary', 'secondary', 'ghost'],
                        'optionLabels' => [
                            'primary' => self::label('footer_widget_button_option_variant_primary'),
                            'secondary' => self::label('footer_widget_button_option_variant_secondary'),
                            'ghost' => self::label('footer_widget_button_option_variant_ghost'),
                        ],
                    ],
                    [
                        'key' => 'iconPosition',
                        'label' => self::label('footer_widget_button_field_icon_position'),
                        'type' => 'select',
                        'control' => 'choice',
                        'group' => 'layout',
                        'options' => ['left', 'right'],
                        'optionLabels' => [
                            'left' => self::label('footer_widget_button_option_icon_position_left'),
                            'right' => self::label('footer_widget_button_option_icon_position_right'),
                        ],
                    ],
                    [
                        'key' => 'align',
                        'label' => self::label('footer_widget_button_field_align'),
                        'type' => 'select',
                        'control' => 'align',
                        'group' => 'layout',
                        'options' => ['left', 'center', 'right'],
                        'optionLabels' => [
                            'left' => self::label('footer_widget_button_option_align_left'),
                            'center' => self::label('footer_widget_button_option_align_center'),
                            'right' => self::label('footer_widget_button_option_align_right'),
                        ],
                    ],
                    [
                        'key' => 'labelTextStyle',
                        'label' => self::label('footer_widget_button_field_label_text_style'),
                        'type' => 'text_style',
                        'group' => 'advanced',
                        'stylePrefix' => 'labelStyle',
                        'previewSource' => 'label',
                        'disableList' => true,
                        'disableIcon' => true,
                    ],
                ],
                self::designFields('footer_widget_button')
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
