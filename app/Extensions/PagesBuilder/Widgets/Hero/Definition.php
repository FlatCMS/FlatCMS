<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Hero;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'hero';
    }

    public static function definition(): array
    {
        $definition = array (
          'type' => 'hero',
          'label' =>
          array (
            '__label' => true,
            'key' => 'hero_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-window-maximize',
          'category' => 'content',
          'i18n_module' => 'Hero',
          'render' => 'render.php',
          'preview_handler' => 'hero',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/hero.css',
            ),
            'preview_css' =>
            array (
              0 => 'css/hero.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/hero-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'title' =>
            array (
              '__label' => true,
              'key' => 'hero_default_title',
              'fallback' => '',
            ),
            'subtitle' =>
            array (
              '__label' => true,
              'key' => 'hero_default_subtitle',
              'fallback' => '',
            ),
            'showPrimaryCta' => 'on',
            'primaryLabel' =>
            array (
              '__label' => true,
              'key' => 'hero_default_primary_label',
              'fallback' => '',
            ),
            'primaryUrl' => '#',
            'primaryTarget' => '_self',
            'showSecondaryCta' => 'on',
            'secondaryLabel' =>
            array (
              '__label' => true,
              'key' => 'hero_default_secondary_label',
              'fallback' => '',
            ),
            'secondaryUrl' => '#',
            'secondaryTarget' => '_self',
            'backgroundImage' => '',
            'mediaFit' => 'cover',
            'height' => 420,
            'overlay' => 35,
            'contentAlign' => 'left',
            'align' => 'left',
            'variant' => 'soft',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 0,
            'designBorderColor' => '',
            'designRadius' => 12,
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
                'key' => 'hero_field_title',
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
                'key' => 'hero_field_subtitle',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
              'placeholder' =>
              array (
                '__label' => true,
                'key' => 'hero_field_subtitle_placeholder',
                'fallback' => '',
              ),
            ),
            2 =>
            array (
              'key' => 'showPrimaryCta',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_show_primary_cta',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'navigation',
            ),
            3 =>
            array (
              'key' => 'primaryLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_primary_label',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'navigation',
              'condition' =>
              array (
                'field' => 'showPrimaryCta',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            4 =>
            array (
              'key' => 'primaryUrl',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_primary_url',
                'fallback' => '',
              ),
              'type' => 'url',
              'group' => 'navigation',
              'condition' =>
              array (
                'field' => 'showPrimaryCta',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            5 =>
            array (
              'key' => 'primaryTarget',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_primary_target',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'choice',
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
                  'key' => 'hero_option_target_self',
                  'fallback' => '',
                ),
                '_blank' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_target_blank',
                  'fallback' => '',
                ),
              ),
              'condition' =>
              array (
                'field' => 'showPrimaryCta',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            6 =>
            array (
              'key' => 'showSecondaryCta',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_show_secondary_cta',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'navigation',
            ),
            7 =>
            array (
              'key' => 'secondaryLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_secondary_label',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'navigation',
              'condition' =>
              array (
                'field' => 'showSecondaryCta',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            8 =>
            array (
              'key' => 'secondaryUrl',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_secondary_url',
                'fallback' => '',
              ),
              'type' => 'url',
              'group' => 'navigation',
              'condition' =>
              array (
                'field' => 'showSecondaryCta',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            9 =>
            array (
              'key' => 'secondaryTarget',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_secondary_target',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'choice',
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
                  'key' => 'hero_option_target_self',
                  'fallback' => '',
                ),
                '_blank' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_target_blank',
                  'fallback' => '',
                ),
              ),
              'condition' =>
              array (
                'field' => 'showSecondaryCta',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            10 =>
            array (
              'key' => 'backgroundImage',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_background_image',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'media',
              'media' =>
              array (
                'mode' => 'images',
                'folder' => 'images',
                'preview' => 'image',
              ),
            ),
            11 =>
            array (
              'key' => 'mediaFit',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_media_fit',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'choice',
              'group' => 'media',
              'options' =>
              array (
                0 => 'cover',
                1 => 'contain',
              ),
              'optionLabels' =>
              array (
                'cover' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_media_fit_cover',
                  'fallback' => '',
                ),
                'contain' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_media_fit_contain',
                  'fallback' => '',
                ),
              ),
            ),
            12 =>
            array (
              'key' => 'height',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_height',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 260,
              'max' => 760,
              'step' => 10,
            ),
            13 =>
            array (
              'key' => 'overlay',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_overlay',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 0,
              'max' => 85,
              'step' => 5,
            ),
            14 =>
            array (
              'key' => 'align',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_actions_align',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'align',
              'group' => 'navigation',
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
                  'key' => 'hero_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_align_right',
                  'fallback' => '',
                ),
              ),
            ),
            15 =>
            array (
              'key' => 'variant',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_variant',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'choice',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'default',
                1 => 'soft',
                2 => 'dark',
              ),
              'optionLabels' =>
              array (
                'default' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_variant_default',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_variant_soft',
                  'fallback' => '',
                ),
                'dark' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_variant_dark',
                  'fallback' => '',
                ),
              ),
            ),
            16 =>
            array (
              'key' => 'useCustomDesign',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'hero_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'hero_section_surface_help',
                'fallback' => '',
              ),
            ),
            17 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'hero_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            18 =>
            array (
              'key' => 'designTextColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'hero_section_surface',
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
              'key' => 'designBorderStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_design_border_style',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'hero_section_surface',
                'fallback' => '',
              ),
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
                  'key' => 'hero_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_design_border_style_dotted',
                  'fallback' => '',
                ),
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
              'key' => 'designBorderWidth',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'hero_section_surface',
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
            21 =>
            array (
              'key' => 'designBorderColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'hero_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            22 =>
            array (
              'key' => 'designRadius',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'hero_section_surface',
                'fallback' => '',
              ),
              'min' => 0,
              'max' => 40,
              'step' => 1,
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            23 =>
            array (
              'key' => 'designShadow',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_design_shadow',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'hero_section_surface',
                'fallback' => '',
              ),
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
                  'key' => 'hero_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'hero_option_design_shadow_strong',
                  'fallback' => '',
                ),
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            24 =>
            array (
              'key' => 'titleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'hero_field_title_text_style',
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
                'key' => 'hero_field_subtitle_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'subtitleStyle',
            ),
          ),
        );

        $definition['fields'] = array_values($definition['fields']);

        return $definition;
    }
}
