<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Contact;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'contact';
    }

    public static function definition(): array
    {
        return [
            'type' => 'contact',
            'label' => self::label('footer_widget_contact_label'),
            'icon' => 'fas fa-paper-plane',
            'category' => 'forms',
            'i18n_module' => 'Contact',
            'render' => 'render.php',
            'preview_handler' => 'contact',
            'assets' => [
                'css' => [
                    'css/contact.css',
                ],
                'preview_css' => [
                    'css/contact.css',
                ],
                'preview_js' => [
                    'js/contact-preview.js',
                ],
            ],
            'defaults' => [
                'title' => self::label('footer_widget_contact_default_title'),
                'formSlug' => 'contact-main',
                'align' => 'left',
                'variant' => 'subtle',
                'useCustomDesign' => '',
                'designSurfaceColor' => '',
                'designTextColor' => '',
                'designBorderStyle' => 'inherit',
                'designBorderWidth' => 1,
                'designBorderColor' => '',
                'designRadius' => 20,
                'designShadow' => 'inherit',
            ],
            'fields' => array_merge(
                [
                    [
                        'key' => 'title',
                        'label' => self::label('footer_widget_contact_field_title'),
                        'type' => 'text',
                        'group' => 'content',
                    ],
                    [
                        'key' => 'formSlug',
                        'label' => self::label('footer_widget_contact_field_form_slug'),
                        'type' => 'text',
                        'group' => 'navigation',
                    ],
                    [
                        'key' => 'align',
                        'label' => self::label('footer_widget_contact_field_align'),
                        'type' => 'select',
                        'control' => 'align',
                        'group' => 'layout',
                        'options' => ['left', 'center', 'right'],
                        'optionLabels' => [
                            'left' => self::label('footer_widget_contact_option_align_left'),
                            'center' => self::label('footer_widget_contact_option_align_center'),
                            'right' => self::label('footer_widget_contact_option_align_right'),
                        ],
                    ],
                    [
                        'key' => 'variant',
                        'label' => self::label('footer_widget_contact_field_variant'),
                        'type' => 'select',
                        'control' => 'choice',
                        'group' => 'layout',
                        'options' => ['subtle', 'strong', 'dark'],
                        'optionLabels' => [
                            'subtle' => self::label('footer_widget_contact_option_variant_subtle'),
                            'strong' => self::label('footer_widget_contact_option_variant_strong'),
                            'dark' => self::label('footer_widget_contact_option_variant_dark'),
                        ],
                    ],
                ],
                self::designFields('footer_widget_contact')
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
