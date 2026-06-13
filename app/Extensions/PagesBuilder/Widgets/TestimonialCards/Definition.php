<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\TestimonialCards;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'testimonial_cards';
    }

    public static function definition(): array
    {
        return
        array (
          'type' => 'testimonial_cards',
          'label' =>
          array (
            '__label' => true,
            'key' => 'testimonial_cards_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-comments',
          'category' => 'content',
          'i18n_module' => 'TestimonialCards',
          'render' => 'render.php',
          'preview_handler' => 'testimonial_cards',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/testimonial-cards.css',
            ),
            'js' =>
            array (
              0 => 'js/testimonial-cards.js',
            ),
            'preview_css' =>
            array (
              0 => 'css/testimonial-cards.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/testimonial-cards.js',
              1 => 'js/testimonial-cards-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'title' =>
            array (
              '__label' => true,
              'key' => 'testimonial_cards_default_title',
              'fallback' => '',
            ),
            'subtitle' =>
            array (
              '__label' => true,
              'key' => 'testimonial_cards_default_subtitle',
              'fallback' => '',
            ),
            'quotes' =>
            array (
              '__label' => true,
              'key' => 'testimonial_cards_default_quotes',
              'fallback' => '',
            ),
            'names' =>
            array (
              '__label' => true,
              'key' => 'testimonial_cards_default_names',
              'fallback' => '',
            ),
            'companies' =>
            array (
              '__label' => true,
              'key' => 'testimonial_cards_default_companies',
              'fallback' => '',
            ),
            'roles' =>
            array (
              '__label' => true,
              'key' => 'testimonial_cards_default_roles',
              'fallback' => '',
            ),
            'ratings' =>
            array (
              '__label' => true,
              'key' => 'testimonial_cards_default_ratings',
              'fallback' => '',
            ),
            'avatars' => '',
            'links' => '',
            'targets' => '_self
                _self
                _self',
            'showHeader' => 'on',
            'showRatings' => 'on',
            'showCompany' => 'on',
            'showAvatars' => 'on',
            'columns' => 3,
            'align' => 'left',
            'variant' => 'subtle',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 1,
            'designBorderColor' => '',
            'designRadius' => 22,
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
                'key' => 'testimonial_cards_field_title',
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
                'key' => 'testimonial_cards_field_subtitle',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            2 =>
            array (
              'key' => 'quotes',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_quotes',
                'fallback' => '',
              ),
              'type' => 'textarea',
              'group' => 'content',
              'rows' => 4,
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_field_quote_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ---
                ',
                'min' => 1,
                'max' => 20,
              ),
            ),
            3 =>
            array (
              'key' => 'names',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_names',
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
                  'key' => 'testimonial_cards_field_name_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'min' => 1,
                'max' => 20,
              ),
            ),
            4 =>
            array (
              'key' => 'companies',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_companies',
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
                  'key' => 'testimonial_cards_field_company_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'min' => 1,
                'max' => 20,
              ),
            ),
            5 =>
            array (
              'key' => 'roles',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_roles',
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
                  'key' => 'testimonial_cards_field_role_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'min' => 1,
                'max' => 20,
              ),
            ),
            6 =>
            array (
              'key' => 'ratings',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_ratings',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'content',
              'options' =>
              array (
                0 => '5',
                1 => '4',
                2 => '3',
                3 => '2',
                4 => '1',
              ),
              'optionLabels' =>
              array (
                5 =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_rating_5',
                  'fallback' => '',
                ),
                4 =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_rating_4',
                  'fallback' => '',
                ),
                3 =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_rating_3',
                  'fallback' => '',
                ),
                2 =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_rating_2',
                  'fallback' => '',
                ),
                1 =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_rating_1',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_field_rating_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'min' => 1,
                'max' => 20,
              ),
            ),
            7 =>
            array (
              'key' => 'avatars',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_avatars',
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
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_field_avatar_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 20,
              ),
            ),
            8 =>
            array (
              'key' => 'links',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_links',
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
                  'key' => 'testimonial_cards_field_link_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 20,
              ),
            ),
            9 =>
            array (
              'key' => 'targets',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_target',
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
                  'key' => 'testimonial_cards_option_target_self',
                  'fallback' => '',
                ),
                '_blank' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_target_blank',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_field_target',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 20,
              ),
            ),
            10 =>
            array (
              'key' => 'columns',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_columns',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 1,
              'max' => 3,
              'step' => 1,
            ),
            11 =>
            array (
              'key' => 'align',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_align',
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
                  'key' => 'testimonial_cards_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_align_right',
                  'fallback' => '',
                ),
              ),
            ),
            12 =>
            array (
              'key' => 'variant',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_variant',
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
                  'key' => 'testimonial_cards_option_variant_subtle',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_variant_strong',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_variant_dashed',
                  'fallback' => '',
                ),
              ),
            ),
            13 =>
            array (
              'key' => 'showHeader',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_show_header',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            14 =>
            array (
              'key' => 'showRatings',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_show_ratings',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            15 =>
            array (
              'key' => 'showCompany',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_show_company',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            16 =>
            array (
              'key' => 'showAvatars',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_show_avatars',
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
                'key' => 'testimonial_cards_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_section_surface_help',
                'fallback' => '',
              ),
            ),
            18 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_section_surface',
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
                'key' => 'testimonial_cards_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_section_surface',
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
                'key' => 'testimonial_cards_field_design_border_style',
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
                  'key' => 'testimonial_cards_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_design_border_style_dotted',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_section_surface',
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
                'key' => 'testimonial_cards_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_section_surface',
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
                'key' => 'testimonial_cards_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_section_surface',
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
                'key' => 'testimonial_cards_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_section_surface',
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
                'key' => 'testimonial_cards_field_design_shadow',
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
                  'key' => 'testimonial_cards_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'testimonial_cards_option_design_shadow_strong',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_section_surface',
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
                'key' => 'testimonial_cards_field_title_text_style',
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
                'key' => 'testimonial_cards_field_subtitle_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'subtitleStyle',
            ),
            27 =>
            array (
              'key' => 'quoteTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_quote_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'quoteStyle',
            ),
            28 =>
            array (
              'key' => 'nameTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_name_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'nameStyle',
            ),
            29 =>
            array (
              'key' => 'roleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'testimonial_cards_field_role_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'roleStyle',
            ),
          ),
        );
    }
}
