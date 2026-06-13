<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\FeatureGrid;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'feature_grid';
    }

    public static function definition(): array
    {
        return
        array (
          'type' => 'feature_grid',
          'label' =>
          array (
            '__label' => true,
            'key' => 'feature_grid_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-th-large',
          'category' => 'content',
          'i18n_module' => 'FeatureGrid',
          'render' => 'render.php',
          'preview_handler' => 'feature_grid',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/feature-grid.css',
            ),
            'preview_css' =>
            array (
              0 => 'css/feature-grid.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/feature-grid-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'title' =>
            array (
              '__label' => true,
              'key' => 'feature_grid_default_title',
              'fallback' => '',
            ),
            'titles' =>
            array (
              '__label' => true,
              'key' => 'feature_grid_default_titles',
              'fallback' => '',
            ),
            'texts' =>
            array (
              '__label' => true,
              'key' => 'feature_grid_default_texts',
              'fallback' => '',
            ),
            'icons' => '',
            'iconEnableds' => '',
            'iconAligns' => '',
            'links' => '',
            'showHeader' => 'on',
            'showTitle' => 'on',
            'showBody' => 'on',
            'showFooter' => 'off',
            'buttonLabel' =>
            array (
              '__label' => true,
              'key' => 'feature_grid_default_button_label',
              'fallback' => '',
            ),
            'buttonEnableds' => '',
            'buttonLabels' => '',
            'buttonTargets' => '',
            'buttonVariants' => '',
            'buttonAligns' => '',
            'columns' => 3,
            'align' => 'left',
            'variant' => 'subtle',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 1,
            'designBorderColor' => '',
            'designRadius' => 16,
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
                'key' => 'feature_grid_field_title',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            1 =>
            array (
              'key' => 'titles',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_titles',
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
                  'key' => 'feature_grid_field_title_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'min' => 1,
                'max' => 8,
              ),
            ),
            2 =>
            array (
              'key' => 'texts',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_texts',
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
                  'key' => 'feature_grid_field_text_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'min' => 1,
                'max' => 8,
              ),
            ),
            3 =>
            array (
              'key' => 'icons',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_icons',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'media',
              'iconPicker' => true,
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_field_icon_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 8,
              ),
            ),
            4 =>
            array (
              'key' => 'iconEnableds',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_icon_enabled',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'media',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_field_icon_enabled',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 8,
              ),
            ),
            5 =>
            array (
              'key' => 'iconAligns',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_icon_align',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'align',
              'group' => 'media',
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
                  'key' => 'feature_grid_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_align_right',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_field_icon_align',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 8,
              ),
            ),
            6 =>
            array (
              'key' => 'buttonEnableds',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_button_enabled',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'navigation',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_field_button_enabled',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 8,
              ),
            ),
            7 =>
            array (
              'key' => 'buttonLabels',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_button_label',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'navigation',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_field_button_label_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 8,
              ),
            ),
            8 =>
            array (
              'key' => 'buttonTargets',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_target',
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
                  'key' => 'feature_grid_option_target_self',
                  'fallback' => '',
                ),
                '_blank' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_target_blank',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_field_target',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 8,
              ),
            ),
            9 =>
            array (
              'key' => 'buttonVariants',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_button_variant',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'choice',
              'group' => 'navigation',
              'options' =>
              array (
                0 => 'primary',
                1 => 'secondary',
                2 => 'ghost',
              ),
              'optionLabels' =>
              array (
                'primary' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_button_variant_primary',
                  'fallback' => '',
                ),
                'secondary' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_button_variant_secondary',
                  'fallback' => '',
                ),
                'ghost' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_button_variant_ghost',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_field_button_variant',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 8,
              ),
            ),
            10 =>
            array (
              'key' => 'buttonAligns',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_button_align',
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
                  'key' => 'feature_grid_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_align_right',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_field_button_align',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 8,
              ),
            ),
            11 =>
            array (
              'key' => 'links',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_links',
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
                  'key' => 'feature_grid_field_link_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 8,
              ),
            ),
            12 =>
            array (
              'key' => 'showHeader',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_show_header',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            13 =>
            array (
              'key' => 'showTitle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_show_title',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            14 =>
            array (
              'key' => 'showBody',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_show_body',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            15 =>
            array (
              'key' => 'columns',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_columns',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 1,
              'max' => 4,
              'step' => 1,
            ),
            16 =>
            array (
              'key' => 'variant',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_variant',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'choice',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'subtle',
                1 => 'strong',
                2 => 'dashed',
              ),
              'optionLabels' =>
              array (
                'subtle' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_variant_subtle',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_variant_strong',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_variant_dashed',
                  'fallback' => '',
                ),
              ),
            ),
            17 =>
            array (
              'key' => 'align',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_align',
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
                  'key' => 'feature_grid_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_align_right',
                  'fallback' => '',
                ),
              ),
            ),
            18 =>
            array (
              'key' => 'useCustomDesign',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_section_surface_help',
                'fallback' => '',
              ),
            ),
            19 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_section_surface',
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
              'key' => 'designTextColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_section_surface',
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
              'key' => 'designBorderStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_design_border_style',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_section_surface',
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
                  'key' => 'feature_grid_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_design_border_style_dotted',
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
            22 =>
            array (
              'key' => 'designBorderWidth',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_section_surface',
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
            23 =>
            array (
              'key' => 'designBorderColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_section_surface',
                'fallback' => '',
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
              'key' => 'designRadius',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_section_surface',
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
            25 =>
            array (
              'key' => 'designShadow',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_design_shadow',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_section_surface',
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
                  'key' => 'feature_grid_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'feature_grid_option_design_shadow_strong',
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
            26 =>
            array (
              'key' => 'titleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'titleStyle',
            ),
            27 =>
            array (
              'key' => 'itemTitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'feature_grid_field_item_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'itemTitleStyle',
            ),
          ),
        );
    }
}
