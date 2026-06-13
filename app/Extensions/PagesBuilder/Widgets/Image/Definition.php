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

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'image';
    }

    public static function definition(): array
    {
        return [
            'type' => 'image',
            'label' => self::label('image_widget_label'),
            'icon' => 'fas fa-image',
            'category' => 'media',
            'i18n_module' => 'Image',
            'render' => 'render.php',
            'preview_handler' => 'image',
            'assets' => [
                'css' => [
                    'css/image.css',
                ],
                'preview_css' => [
                    'css/image.css',
                ],
                'preview_js' => [
                    'js/image-preview.js',
                ],
            ],
            'defaults' => [
                'title' => '',
                'text' => '',
                'textPlacement' => 'below',
                'src' => '',
                'altText' => '',
                'showButton' => '',
                'buttonLabel' => '',
                'buttonUrl' => '#',
                'buttonTarget' => '_self',
                'buttonVariant' => 'primary',
                'buttonPlacement' => 'below',
                'buttonAlign' => 'center',
                'buttonVerticalAlign' => 'center',
                'align' => 'center',
                'widthPercent' => 100,
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
                        'key' => 'title',
                        'label' => self::label('image_field_title'),
                        'type' => 'text',
                        'group' => 'content',
                    ],
                    [
                        'key' => 'text',
                        'label' => self::label('image_field_text'),
                        'type' => 'textarea',
                        'group' => 'content',
                        'rows' => 4,
                    ],
                    [
                        'key' => 'textPlacement',
                        'label' => self::label('image_field_text_placement'),
                        'type' => 'select',
                        'control' => 'choice',
                        'group' => 'content',
                        'options' => ['above', 'overlay', 'below'],
                        'optionLabels' => [
                            'above' => self::label('image_option_placement_above'),
                            'overlay' => self::label('image_option_placement_overlay'),
                            'below' => self::label('image_option_placement_below'),
                        ],
                    ],
                    [
                        'key' => 'showButton',
                        'label' => self::label('image_field_show_button'),
                        'type' => 'checkbox',
                        'group' => 'navigation',
                    ],
                    [
                        'key' => 'buttonLabel',
                        'label' => self::label('image_field_button_label'),
                        'type' => 'text',
                        'group' => 'navigation',
                        'condition' => [
                            'field' => 'showButton',
                            'operator' => 'equals',
                            'value' => 'on',
                        ],
                    ],
                    [
                        'key' => 'buttonUrl',
                        'label' => self::label('image_field_button_url'),
                        'type' => 'url',
                        'group' => 'navigation',
                        'condition' => [
                            'field' => 'showButton',
                            'operator' => 'equals',
                            'value' => 'on',
                        ],
                    ],
                    [
                        'key' => 'buttonTarget',
                        'label' => self::label('image_field_button_target'),
                        'type' => 'select',
                        'control' => 'choice',
                        'group' => 'navigation',
                        'options' => ['_self', '_blank'],
                        'optionLabels' => [
                            '_self' => self::label('image_option_target_self'),
                            '_blank' => self::label('image_option_target_blank'),
                        ],
                        'condition' => [
                            'field' => 'showButton',
                            'operator' => 'equals',
                            'value' => 'on',
                        ],
                    ],
                    [
                        'key' => 'buttonVariant',
                        'label' => self::label('image_field_button_variant'),
                        'type' => 'select',
                        'control' => 'choice',
                        'group' => 'navigation',
                        'options' => ['primary', 'secondary', 'ghost'],
                        'optionLabels' => [
                            'primary' => self::label('image_option_button_variant_primary'),
                            'secondary' => self::label('image_option_button_variant_secondary'),
                            'ghost' => self::label('image_option_button_variant_ghost'),
                        ],
                        'condition' => [
                            'field' => 'showButton',
                            'operator' => 'equals',
                            'value' => 'on',
                        ],
                    ],
                    [
                        'key' => 'buttonPlacement',
                        'label' => self::label('image_field_button_placement'),
                        'type' => 'select',
                        'control' => 'choice',
                        'group' => 'navigation',
                        'options' => ['above', 'overlay', 'below'],
                        'optionLabels' => [
                            'above' => self::label('image_option_placement_above'),
                            'overlay' => self::label('image_option_placement_overlay'),
                            'below' => self::label('image_option_placement_below'),
                        ],
                        'condition' => [
                            'field' => 'showButton',
                            'operator' => 'equals',
                            'value' => 'on',
                        ],
                    ],
                    [
                        'key' => 'buttonAlign',
                        'label' => self::label('image_field_button_align'),
                        'type' => 'select',
                        'control' => 'align',
                        'group' => 'navigation',
                        'options' => ['left', 'center', 'right'],
                        'optionLabels' => [
                            'left' => self::label('image_option_align_left'),
                            'center' => self::label('image_option_align_center'),
                            'right' => self::label('image_option_align_right'),
                        ],
                        'condition' => [
                            'field' => 'showButton',
                            'operator' => 'equals',
                            'value' => 'on',
                        ],
                    ],
                    [
                        'key' => 'buttonVerticalAlign',
                        'label' => self::label('image_field_button_vertical_align'),
                        'type' => 'select',
                        'control' => 'choice',
                        'group' => 'navigation',
                        'options' => ['top', 'center', 'bottom'],
                        'optionLabels' => [
                            'top' => self::label('image_option_vertical_align_top'),
                            'center' => self::label('image_option_vertical_align_center'),
                            'bottom' => self::label('image_option_vertical_align_bottom'),
                        ],
                        'condition' => [
                            'field' => 'showButton',
                            'operator' => 'equals',
                            'value' => 'on',
                        ],
                    ],
                    [
                        'key' => 'src',
                        'label' => self::label('image_field_src'),
                        'type' => 'text',
                        'group' => 'media',
                        'media' => [
                            'mode' => 'images',
                            'folder' => 'images',
                            'preview' => 'image',
                        ],
                    ],
                    [
                        'key' => 'altText',
                        'label' => self::label('image_field_alt_text'),
                        'type' => 'text',
                        'group' => 'media',
                    ],
                    [
                        'key' => 'align',
                        'label' => self::label('image_field_align'),
                        'type' => 'select',
                        'control' => 'align',
                        'group' => 'layout',
                        'options' => ['left', 'center', 'right'],
                        'optionLabels' => [
                            'left' => self::label('image_option_align_left'),
                            'center' => self::label('image_option_align_center'),
                            'right' => self::label('image_option_align_right'),
                        ],
                    ],
                    [
                        'key' => 'widthPercent',
                        'label' => self::label('image_field_width'),
                        'type' => 'range',
                        'group' => 'layout',
                        'min' => 10,
                        'max' => 100,
                        'step' => 5,
                    ],
                    [
                        'key' => 'titleTextStyle',
                        'label' => self::label('image_field_title_text_style'),
                        'type' => 'text_style',
                        'group' => 'advanced',
                        'stylePrefix' => 'titleStyle',
                        'previewSource' => 'title',
                        'disableList' => true,
                    ],
                    [
                        'key' => 'bodyTextStyle',
                        'label' => self::label('image_field_body_text_style'),
                        'type' => 'text_style',
                        'group' => 'advanced',
                        'stylePrefix' => 'bodyStyle',
                        'previewSource' => 'text',
                        'disableList' => true,
                    ],
                ],
                self::designFields('image')
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
