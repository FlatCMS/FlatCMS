<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Carousel;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'carousel';
    }

    public static function definition(): array
    {
        return
        array (
          'type' => 'carousel',
          'label' =>
          array (
            '__label' => true,
            'key' => 'carousel_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-images',
          'category' => 'media',
          'i18n_module' => 'Carousel',
          'render' => 'render.php',
          'preview_handler' => 'carousel',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/carousel.css',
            ),
            'js' =>
            array (
              0 => 'js/carousel.js',
            ),
            'preview_css' =>
            array (
              0 => 'css/carousel.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/carousel-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'title' =>
            array (
              '__label' => true,
              'key' => 'carousel_default_title',
              'fallback' => '',
            ),
            'images' => '',
            'titles' =>
            array (
              '__label' => true,
              'key' => 'carousel_default_titles',
              'fallback' => '',
            ),
            'texts' =>
            array (
              '__label' => true,
              'key' => 'carousel_default_texts',
              'fallback' => '',
            ),
            'links' => '',
            'buttonEnableds' => 'on
        on
        on',
            'buttonLabels' =>
            array (
              '__label' => true,
              'key' => 'carousel_default_button_labels',
              'fallback' => '',
            ),
            'buttonTargets' => '_self
        _self
        _self',
            'buttonAligns' => 'left
        left
        left',
            'buttonLabel' =>
            array (
              '__label' => true,
              'key' => 'carousel_default_button_label',
              'fallback' => '',
            ),
            'mediaFullBleed' => '',
            'showIndicators' => 'on',
            'showArrows' => 'on',
            'indicatorStyle' => 'dots',
            'arrowStyle' => 'filled',
            'autoplay' => 'on',
            'autoplayDelay' => 5,
            'loop' => 'on',
            'height' => 420,
            'transition' => 'slide',
            'align' => 'left',
            'target' => '_self',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 1,
            'designBorderColor' => '',
            'designRadius' => 14,
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
                'key' => 'carousel_field_title',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            1 =>
            array (
              'key' => 'mediaFullBleed',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_media_full_bleed',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'media',
            ),
            2 =>
            array (
              'key' => 'images',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_images',
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
                  'key' => 'carousel_field_image_item',
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
              'key' => 'titles',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_titles',
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
                  'key' => 'carousel_field_title_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 12,
              ),
            ),
            4 =>
            array (
              'key' => 'texts',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_texts',
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
                  'key' => 'carousel_field_text_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 12,
              ),
            ),
            5 =>
            array (
              'key' => 'links',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_links',
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
                  'key' => 'carousel_field_link_item',
                  'fallback' => '',
                ),
                'delimiter' => '
        ',
                'max' => 12,
              ),
            ),
            6 =>
            array (
              'key' => 'showIndicators',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_show_indicators',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'navigation',
            ),
            7 =>
            array (
              'key' => 'showArrows',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_show_arrows',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'navigation',
            ),
            8 =>
            array (
              'key' => 'indicatorStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_indicator_style',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'navigation',
              'options' =>
              array (
                0 => 'dots',
                1 => 'bars',
                2 => 'numbers',
              ),
              'optionLabels' =>
              array (
                'dots' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_indicator_style_dots',
                  'fallback' => '',
                ),
                'bars' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_indicator_style_bars',
                  'fallback' => '',
                ),
                'numbers' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_indicator_style_numbers',
                  'fallback' => '',
                ),
              ),
            ),
            9 =>
            array (
              'key' => 'arrowStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_arrow_style',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'navigation',
              'options' =>
              array (
                0 => 'filled',
                1 => 'outline',
                2 => 'minimal',
              ),
              'optionLabels' =>
              array (
                'filled' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_arrow_style_filled',
                  'fallback' => '',
                ),
                'outline' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_arrow_style_outline',
                  'fallback' => '',
                ),
                'minimal' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_arrow_style_minimal',
                  'fallback' => '',
                ),
              ),
            ),
            10 =>
            array (
              'key' => 'autoplay',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_autoplay',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'navigation',
            ),
            11 =>
            array (
              'key' => 'loop',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_loop',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'navigation',
            ),
            12 =>
            array (
              'key' => 'transition',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_transition',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'slide',
                1 => 'fade',
              ),
              'optionLabels' =>
              array (
                'slide' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_transition_slide',
                  'fallback' => '',
                ),
                'fade' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_transition_fade',
                  'fallback' => '',
                ),
              ),
            ),
            13 =>
            array (
              'key' => 'autoplayDelay',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_autoplay_delay',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 2,
              'max' => 15,
              'step' => 1,
            ),
            14 =>
            array (
              'key' => 'height',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_height',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 240,
              'max' => 720,
              'step' => 10,
            ),
            15 =>
            array (
              'key' => 'align',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_align',
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
                  'key' => 'carousel_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_align_right',
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
                'key' => 'carousel_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'carousel_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'carousel_section_surface_help',
                'fallback' => '',
              ),
            ),
            17 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'carousel_section_surface',
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
                'key' => 'carousel_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'carousel_section_surface',
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
                'key' => 'carousel_field_design_border_style',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'carousel_section_surface',
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
                  'key' => 'carousel_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_design_border_style_dotted',
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
                'key' => 'carousel_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'carousel_section_surface',
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
                'key' => 'carousel_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'carousel_section_surface',
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
                'key' => 'carousel_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'carousel_section_surface',
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
                'key' => 'carousel_field_design_shadow',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'carousel_section_surface',
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
                  'key' => 'carousel_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'carousel_option_design_shadow_strong',
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
              'key' => 'itemTitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_item_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'itemTitleStyle',
            ),
            25 =>
            array (
              'key' => 'itemTextTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'carousel_field_item_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'itemTextStyle',
            ),
          ),
        );
    }
}
