<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\LogoCloud;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'logo_cloud';
    }

    public static function definition(): array
    {
        return
        array (
          'type' => 'logo_cloud',
          'label' =>
          array (
            '__label' => true,
            'key' => 'logo_cloud_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-cloud',
          'category' => 'content',
          'i18n_module' => 'LogoCloud',
          'render' => 'render.php',
          'preview_handler' => 'logo_cloud',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/logo-cloud.css',
            ),
            'js' =>
            array (
              0 => 'js/logo-cloud.js',
            ),
            'preview_css' =>
            array (
              0 => 'css/logo-cloud.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/logo-cloud-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'title' =>
            array (
              '__label' => true,
              'key' => 'logo_cloud_default_title',
              'fallback' => '',
            ),
            'subtitle' =>
            array (
              '__label' => true,
              'key' => 'logo_cloud_default_subtitle',
              'fallback' => '',
            ),
            'labels' =>
            array (
              '__label' => true,
              'key' => 'logo_cloud_default_labels',
              'fallback' => '',
            ),
            'logos' => '',
            'links' => '',
            'targets' => '_self
                _self
                _self
                _self',
            'showHeader' => 'on',
            'showLabels' => '',
            'presentationModel' => 'classic',
            'columns' => 4,
            'logoHeight' => 72,
            'gap' => 20,
            'animationSpeed' => 28,
            'widgetHeight' => 280,
            'align' => 'center',
            'variant' => 'subtle',
            'grayscale' => 'on',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 1,
            'designBorderColor' => '',
            'designRadius' => 18,
            'designShadow' => 'inherit',
          ),
          'fields' =>
          array (
            0 =>
            array (
              'key' => 'title',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_title',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            1 =>
            array (
              'key' => 'subtitle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_subtitle',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            2 =>
            array (
              'key' => 'labels',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_labels',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_field_label_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'min' => 1,
                'max' => 13,
              ),
            ),
            3 =>
            array (
              'key' => 'logos',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_logos',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'media',
              'media' =>
              array (
                'mode' => 'images',
                'folder' => 'images',
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_field_logo_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'min' => 1,
                'max' => 13,
              ),
            ),
            4 =>
            array (
              'key' => 'links',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_links',
                'fallback' => '',
              ),
              'type' => 'url',
              'group' => 'navigation',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_field_link_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 13,
              ),
            ),
            5 =>
            array (
              'key' => 'targets',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_target',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'navigation',
              'options' =>
              array (
                0 => '_self',
                1 => '_blank',
              ),
              'optionLabels' =>
              array (
                '_self' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_target_self',
                  'fallback' => '',
                ),
                '_blank' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_target_blank',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_field_target',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 13,
              ),
            ),
            6 =>
            array (
              'key' => 'columns',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_columns',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 2,
              'max' => 6,
              'step' => 1,
            ),
            7 =>
            array (
              'key' => 'logoHeight',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_logo_height',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 40,
              'max' => 160,
              'step' => 4,
            ),
            8 =>
            array (
              'key' => 'gap',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_gap',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 8,
              'max' => 48,
              'step' => 2,
            ),
            9 =>
            array (
              'key' => 'animationSpeed',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_animation_speed',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 12,
              'max' => 60,
              'step' => 1,
            ),
            10 =>
            array (
              'key' => 'widgetHeight',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_widget_height',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 220,
              'max' => 760,
              'step' => 10,
            ),
            11 =>
            array (
              'key' => 'align',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_align',
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
              'optionLabels' =>
              array (
                'left' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_align_right',
                  'fallback' => '',
                ),
              ),
            ),
            12 =>
            array (
              'key' => 'presentationModel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_presentation_model',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'layout',
              'options' =>
              array (
                'classic' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_model_classic',
                  'fallback' => '',
                ),
                'cloud4' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_model_cloud4',
                  'fallback' => '',
                ),
                'cloud6' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_model_cloud6',
                  'fallback' => '',
                ),
                'cloud7' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_model_cloud7',
                  'fallback' => '',
                ),
              ),
            ),
            13 =>
            array (
              'key' => 'variant',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_variant',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'choice',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'subtle',
                1 => 'strong',
                2 => 'ghost',
              ),
              'optionLabels' =>
              array (
                'subtle' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_variant_subtle',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_variant_strong',
                  'fallback' => '',
                ),
                'ghost' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_variant_ghost',
                  'fallback' => '',
                ),
              ),
            ),
            14 =>
            array (
              'key' => 'showHeader',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_show_header',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            15 =>
            array (
              'key' => 'showLabels',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_show_labels',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            16 =>
            array (
              'key' => 'grayscale',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_grayscale',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            17 =>
            array (
              'key' => 'useCustomDesign',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_section_surface_help',
                'fallback' => '',
              ),
            ),
            18 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            19 =>
            array (
              'key' => 'designTextColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            20 =>
            array (
              'key' => 'designBorderStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_design_border_style',
                'fallback' => '',
              ),
              'type' => 'select',
              'options' =>
              array (
                0 => 'inherit',
                1 => 'none',
                2 => 'solid',
                3 => 'dashed',
                4 => 'dotted',
              ),
              'optionLabels' =>
              array (
                'inherit' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_design_border_style_dotted',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            21 =>
            array (
              'key' => 'designBorderWidth',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_section_surface',
                'fallback' => '',
              ),
              'min' => 0,
              'max' => 8,
              'step' => 1,
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            22 =>
            array (
              'key' => 'designBorderColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            23 =>
            array (
              'key' => 'designRadius',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_section_surface',
                'fallback' => '',
              ),
              'min' => 0,
              'max' => 48,
              'step' => 1,
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            24 =>
            array (
              'key' => 'designShadow',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_design_shadow',
                'fallback' => '',
              ),
              'type' => 'select',
              'options' =>
              array (
                0 => 'inherit',
                1 => 'none',
                2 => 'soft',
                3 => 'medium',
                4 => 'strong',
              ),
              'optionLabels' =>
              array (
                'inherit' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'logo_cloud_option_design_shadow_strong',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            25 =>
            array (
              'key' => 'titleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'titleStyle',
            ),
            26 =>
            array (
              'key' => 'subtitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_subtitle_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'subtitleStyle',
            ),
            27 =>
            array (
              'key' => 'labelTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'logo_cloud_field_label_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'labelStyle',
            ),
          ),
        );
    }
}
