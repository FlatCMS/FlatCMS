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

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'newsletter';
    }

    public static function definition(): array
    {
        return
        array (
          'type' => 'newsletter',
          'label' =>
          array (
            '__label' => true,
            'key' => 'footer_widget_newsletter_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-envelope-open-text',
          'category' => 'forms',
          'i18n_module' => 'Newsletter',
          'render' => 'render.php',
          'preview_handler' => 'newsletter',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/newsletter.css',
            ),
            'preview_css' =>
            array (
              0 => 'css/newsletter.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/newsletter-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'title' =>
            array (
              '__label' => true,
              'key' => 'footer_widget_newsletter_default_title',
              'fallback' => '',
            ),
            'description' =>
            array (
              '__label' => true,
              'key' => 'footer_widget_newsletter_default_description',
              'fallback' => '',
            ),
            'placeholder' =>
            array (
              '__label' => true,
              'key' => 'footer_widget_newsletter_default_placeholder',
              'fallback' => '',
            ),
            'buttonLabel' =>
            array (
              '__label' => true,
              'key' => 'footer_widget_newsletter_default_button',
              'fallback' => '',
            ),
            'action' => '/newsletter',
            'align' => 'left',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 1,
            'designBorderColor' => '',
            'designRadius' => 20,
            'designShadow' => 'inherit',
          ),
          'fields' => array_merge(
            array (
              0 =>
              array (
                'key' => 'title',
                'label' =>
                array (
                  '__label' => true,
                  'key' => 'footer_widget_newsletter_field_title',
                  'fallback' => '',
                ),
                'type' => 'text',
                'group' => 'content',
              ),
              1 =>
              array (
                'key' => 'description',
                'label' =>
                array (
                  '__label' => true,
                  'key' => 'footer_widget_newsletter_field_description',
                  'fallback' => '',
                ),
                'type' => 'textarea',
                'group' => 'content',
                'rows' => 3,
              ),
              2 =>
              array (
                'key' => 'placeholder',
                'label' =>
                array (
                  '__label' => true,
                  'key' => 'footer_widget_newsletter_field_placeholder',
                  'fallback' => '',
                ),
                'type' => 'text',
                'group' => 'content',
              ),
              3 =>
              array (
                'key' => 'buttonLabel',
                'label' =>
                array (
                  '__label' => true,
                  'key' => 'footer_widget_newsletter_field_button',
                  'fallback' => '',
                ),
                'type' => 'text',
                'group' => 'navigation',
              ),
              4 =>
              array (
                'key' => 'action',
                'label' =>
                array (
                  '__label' => true,
                  'key' => 'footer_widget_newsletter_field_action',
                  'fallback' => '',
                ),
                'type' => 'url',
                'group' => 'navigation',
              ),
              5 =>
              array (
                'key' => 'align',
                'label' =>
                array (
                  '__label' => true,
                  'key' => 'footer_widget_newsletter_field_align',
                  'fallback' => '',
                ),
                'type' => 'select',
                'control' => 'align',
                'group' => 'layout',
                'options' =>
                array (
                  0 => 'left',
                  1 => 'center',
                  2 => 'right',
                ),
              ),
            ),
            self::designFields('footer_widget_newsletter')
          ),
        );
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
}
