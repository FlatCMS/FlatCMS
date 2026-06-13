<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\SnapCards;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'snap_cards';
    }

    public static function definition(): array
    {
        return
        array (
          'type' => 'snap_cards',
          'label' =>
          array (
            '__label' => true,
            'key' => 'snap_cards_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-grip-horizontal',
          'category' => 'content',
          'i18n_module' => 'SnapCards',
          'render' => 'render.php',
          'preview_handler' => 'snap_cards',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/snap-cards.css',
            ),
            'js' =>
            array (
              0 => 'js/snap-cards.js',
            ),
            'preview_css' =>
            array (
              0 => 'css/snap-cards.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/snap-cards-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'title' =>
            array (
              '__label' => true,
              'key' => 'snap_cards_default_title',
              'fallback' => '',
            ),
            'titles' =>
            array (
              '__label' => true,
              'key' => 'snap_cards_default_titles',
              'fallback' => '',
            ),
            'texts' =>
            array (
              '__label' => true,
              'key' => 'snap_cards_default_texts',
              'fallback' => '',
            ),
            'backgrounds' => '',
            'links' => '',
            'ctaEnableds' => 'on
        on
        on',
            'ctaLabels' => '',
            'targets' => '_self
        _self
        _self',
            'buttonAligns' => '',
            'ctaLabel' =>
            array (
              '__label' => true,
              'key' => 'snap_cards_default_cta_label',
              'fallback' => '',
            ),
            'target' => '_self',
            'mediaFullBleed' => '',
            'height' => 420,
            'overlay' => 45,
            'align' => 'left',
            'variant' => 'soft',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 1,
            'designBorderColor' => '',
            'designRadius' => 13,
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
                'key' => 'snap_cards_field_title',
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
                'key' => 'snap_cards_field_titles',
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
                  'key' => 'snap_cards_field_title_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'min' => 1,
                'max' => 12,
              ),
            ),
            2 =>
            array (
              'key' => 'texts',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_texts',
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
                  'key' => 'snap_cards_field_text_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'min' => 1,
                'max' => 12,
              ),
            ),
            3 =>
            array (
              'key' => 'mediaFullBleed',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_media_full_bleed',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'media',
            ),
            4 =>
            array (
              'key' => 'backgrounds',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_backgrounds',
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
                  'key' => 'snap_cards_field_background_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'min' => 1,
                'max' => 12,
              ),
            ),
            5 =>
            array (
              'key' => 'links',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_links',
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
                  'key' => 'snap_cards_field_link_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'min' => 1,
                'max' => 12,
              ),
            ),
            6 =>
            array (
              'key' => 'ctaEnableds',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_cta_enabled',
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
                  'key' => 'snap_cards_field_cta_enabled',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 12,
              ),
            ),
            7 =>
            array (
              'key' => 'ctaLabels',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_cta_labels',
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
                  'key' => 'snap_cards_field_cta_label_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 12,
              ),
            ),
            8 =>
            array (
              'key' => 'targets',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_target',
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
                  'key' => 'snap_cards_option_target_self',
                  'fallback' => '',
                ),
                '_blank' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_target_blank',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_field_target',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 12,
              ),
            ),
            9 =>
            array (
              'key' => 'buttonAligns',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_align',
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
                  'key' => 'snap_cards_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_align_right',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_field_align',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 12,
              ),
            ),
            10 =>
            array (
              'key' => 'ctaLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_cta_labels',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'navigation',
            ),
            11 =>
            array (
              'key' => 'target',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_target',
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
                  'key' => 'snap_cards_option_target_self',
                  'fallback' => '',
                ),
                '_blank' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_target_blank',
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
                'key' => 'snap_cards_field_height',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 280,
              'max' => 760,
              'step' => 10,
            ),
            13 =>
            array (
              'key' => 'overlay',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_overlay',
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
                'key' => 'snap_cards_field_align',
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
                  'key' => 'snap_cards_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_align_right',
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
                'key' => 'snap_cards_field_variant',
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
                  'key' => 'snap_cards_option_variant_default',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_variant_soft',
                  'fallback' => '',
                ),
                'dark' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_variant_dark',
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
                'key' => 'snap_cards_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_section_surface_help',
                'fallback' => '',
              ),
            ),
            17 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_section_surface',
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
                'key' => 'snap_cards_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_section_surface',
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
                'key' => 'snap_cards_field_design_border_style',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_section_surface',
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
                  'key' => 'snap_cards_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_design_border_style_dotted',
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
                'key' => 'snap_cards_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_section_surface',
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
                'key' => 'snap_cards_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_section_surface',
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
                'key' => 'snap_cards_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_section_surface',
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
                'key' => 'snap_cards_field_design_shadow',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_section_surface',
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
                  'key' => 'snap_cards_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'snap_cards_option_design_shadow_strong',
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
                'key' => 'snap_cards_field_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'titleStyle',
            ),
            25 =>
            array (
              'key' => 'itemTitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'snap_cards_field_title_text_style',
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
