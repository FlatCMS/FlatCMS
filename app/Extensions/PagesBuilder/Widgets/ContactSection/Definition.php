<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\ContactSection;

use App\Extensions\PagesBuilder\Services\PageBuilderContactFormCatalogService;
use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'contact_section';
    }

    public static function definition(): array
    {
        $formCatalog = new PageBuilderContactFormCatalogService();
        $formChoices = $formCatalog->fieldChoices(PageBuilderContactFormCatalogService::SCOPE_CONTACT, 'contact-main');

        return
        array (
          'type' => 'contact_section',
          'label' =>
          array (
            '__label' => true,
            'key' => 'contact_section_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-paper-plane',
          'category' => 'content',
          'i18n_module' => 'ContactSection',
          'render' => 'render.php',
          'preview_handler' => 'contact_section',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/contact-section.css',
            ),
            'preview_css' =>
            array (
              0 => 'css/contact-section.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/contact-section-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'showEyebrow' => 'on',
            'eyebrow' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_eyebrow',
              'fallback' => '',
            ),
            'title' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_title',
              'fallback' => '',
            ),
            'subtitle' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_subtitle',
              'fallback' => '',
            ),
            'showBody' => 'on',
            'body' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_body',
              'fallback' => '',
            ),
            'showFeatures' => 'on',
            'featureItems' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_features',
              'fallback' => '',
            ),
            'showProof' => 'on',
            'proofLabel' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_proof_label',
              'fallback' => '',
            ),
            'formTitle' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_form_title',
              'fallback' => '',
            ),
            'formDescription' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_form_description',
              'fallback' => '',
            ),
            'helperText' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_helper_text',
              'fallback' => '',
            ),
            'contactFormSlug' => $formChoices['default'],
            'formUnavailableMessage' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_form_unavailable_message',
              'fallback' => '',
            ),
            'emptyMessage' =>
            array (
              '__label' => true,
              'key' => 'contact_section_default_empty_message',
              'fallback' => '',
            ),
            'align' => 'left',
            'textVerticalAlign' => 'center',
            'variant' => 'subtle',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 1,
            'designBorderColor' => '',
            'designRadius' => 28,
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
                'key' => 'contact_section_field_title',
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
                'key' => 'contact_section_field_subtitle',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            2 =>
            array (
              'key' => 'showEyebrow',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_show_eyebrow',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'content',
            ),
            3 =>
            array (
              'key' => 'eyebrow',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_eyebrow',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
              'condition' =>
              array (
                'field' => 'showEyebrow',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            4 =>
            array (
              'key' => 'showBody',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_show_body',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'content',
            ),
            5 =>
            array (
              'key' => 'body',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_body',
                'fallback' => '',
              ),
              'type' => 'textarea',
              'group' => 'content',
              'rows' => 5,
              'condition' =>
              array (
                'field' => 'showBody',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            6 =>
            array (
              'key' => 'showFeatures',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_show_features',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'content',
            ),
            7 =>
            array (
              'key' => 'featureItems',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_feature_items',
                'fallback' => '',
              ),
              'type' => 'textarea',
              'group' => 'content',
              'rows' => 5,
              'condition' =>
              array (
                'field' => 'showFeatures',
                'operator' => 'equals',
                'value' => 'on',
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_field_feature_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            8 =>
            array (
              'key' => 'showProof',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_show_proof',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'content',
            ),
            9 =>
            array (
              'key' => 'proofLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_proof_label',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
              'condition' =>
              array (
                'field' => 'showProof',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            10 =>
            array (
              'key' => 'formTitle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_form_title',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            11 =>
            array (
              'key' => 'formDescription',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_form_description',
                'fallback' => '',
              ),
              'type' => 'textarea',
              'group' => 'content',
              'rows' => 3,
            ),
            12 =>
            array (
              'key' => 'helperText',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_helper_text',
                'fallback' => '',
              ),
              'type' => 'textarea',
              'group' => 'content',
              'rows' => 3,
            ),
            13 =>
            array (
              'key' => 'contactFormSlug',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_form_slug',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'content',
              'options' => $formChoices['options'],
              'optionLabels' => $formChoices['optionLabels'],
            ),
            14 =>
            array (
              'key' => 'align',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_align',
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
                  'key' => 'contact_section_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_align_right',
                  'fallback' => '',
                ),
              ),
            ),
            15 =>
            array (
              'key' => 'textVerticalAlign',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_text_vertical_align',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'top',
                1 => 'center',
                2 => 'bottom',
              ),
              'optionLabels' =>
              array (
                'top' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_text_vertical_align_top',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_text_vertical_align_center',
                  'fallback' => '',
                ),
                'bottom' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_text_vertical_align_bottom',
                  'fallback' => '',
                ),
              ),
            ),
            16 =>
            array (
              'key' => 'variant',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_variant',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'choice',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'subtle',
                1 => 'strong',
                2 => 'dark',
              ),
              'optionLabels' =>
              array (
                'subtle' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_variant_subtle',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_variant_strong',
                  'fallback' => '',
                ),
                'dark' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_variant_dark',
                  'fallback' => '',
                ),
              ),
            ),
            17 =>
            array (
              'key' => 'useCustomDesign',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'contact_section_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'contact_section_section_surface_help',
                'fallback' => '',
              ),
            ),
            18 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'contact_section_section_surface',
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
                'key' => 'contact_section_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'contact_section_section_surface',
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
                'key' => 'contact_section_field_design_border_style',
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
                  'key' => 'contact_section_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_design_border_style_dotted',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'contact_section_section_surface',
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
                'key' => 'contact_section_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'contact_section_section_surface',
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
                'key' => 'contact_section_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'contact_section_section_surface',
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
                'key' => 'contact_section_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'contact_section_section_surface',
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
                'key' => 'contact_section_field_design_shadow',
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
                  'key' => 'contact_section_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'contact_section_option_design_shadow_strong',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'contact_section_section_surface',
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
              'key' => 'eyebrowTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_eyebrow_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'eyebrowStyle',
            ),
            26 =>
            array (
              'key' => 'titleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'titleStyle',
            ),
            27 =>
            array (
              'key' => 'subtitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_subtitle_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'subtitleStyle',
            ),
            28 =>
            array (
              'key' => 'bodyTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_body_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'bodyStyle',
            ),
            29 =>
            array (
              'key' => 'featureTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_feature_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'featureStyle',
            ),
            30 =>
            array (
              'key' => 'proofTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_proof_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'proofStyle',
            ),
            31 =>
            array (
              'key' => 'formTitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_form_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'formTitleStyle',
            ),
            32 =>
            array (
              'key' => 'formDescriptionTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_form_description_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'formDescriptionStyle',
            ),
            33 =>
            array (
              'key' => 'helperTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'contact_section_field_helper_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'helperTextStyle',
            ),
          ),
        );
    }
}
