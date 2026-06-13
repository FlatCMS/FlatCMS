<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\NewsletterSection;

use App\Extensions\PagesBuilder\Services\PageBuilderContactFormCatalogService;
use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'newsletter_section';
    }

    public static function definition(): array
    {
        $formCatalog = new PageBuilderContactFormCatalogService();
        $formChoices = $formCatalog->fieldChoices(PageBuilderContactFormCatalogService::SCOPE_NEWSLETTER, 'newsletter-rgpd');

        return
        array (
          'type' => 'newsletter_section',
          'label' =>
          array (
            '__label' => true,
            'key' => 'newsletter_section_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-envelope-open-text',
          'category' => 'content',
          'i18n_module' => 'NewsletterSection',
          'render' => 'render.php',
          'preview_handler' => 'newsletter_section',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/newsletter-section.css',
            ),
            'preview_css' =>
            array (
              0 => 'css/newsletter-section.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/newsletter-section-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'showEyebrow' => 'on',
            'eyebrow' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_eyebrow',
              'fallback' => '',
            ),
            'title' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_title',
              'fallback' => '',
            ),
            'subtitle' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_subtitle',
              'fallback' => '',
            ),
            'showBody' => 'on',
            'body' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_body',
              'fallback' => '',
            ),
            'showFeatures' => 'on',
            'featureItems' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_features',
              'fallback' => '',
            ),
            'showProof' => 'on',
            'proofLabel' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_proof_label',
              'fallback' => '',
            ),
            'formTitle' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_form_title',
              'fallback' => '',
            ),
            'formDescription' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_form_description',
              'fallback' => '',
            ),
            'emailLabel' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_form_email_label',
              'fallback' => '',
            ),
            'placeholder' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_placeholder',
              'fallback' => '',
            ),
            'buttonLabel' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_button_label',
              'fallback' => '',
            ),
            'helperText' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_helper_text',
              'fallback' => '',
            ),
            'newsletterFormSlug' => $formChoices['default'],
            'consentLabel' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_consent_label',
              'fallback' => '',
            ),
            'consentHelp' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_consent_help',
              'fallback' => '',
            ),
            'consentLinksPrefix' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_consent_links_prefix',
              'fallback' => '',
            ),
            'legalLinkLabel' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_legal_link_label',
              'fallback' => '',
            ),
            'privacyLinkLabel' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_privacy_link_label',
              'fallback' => '',
            ),
            'captchaLabel' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_captcha_label',
              'fallback' => '',
            ),
            'formUnavailableMessage' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_form_unavailable_message',
              'fallback' => '',
            ),
            'emptyMessage' =>
            array (
              '__label' => true,
              'key' => 'newsletter_section_default_empty_message',
              'fallback' => '',
            ),
            'align' => 'left',
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
          array_values(array (
            0 =>
            array (
              'key' => 'title',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_title',
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
                'key' => 'newsletter_section_field_subtitle',
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
                'key' => 'newsletter_section_field_show_eyebrow',
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
                'key' => 'newsletter_section_field_eyebrow',
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
                'key' => 'newsletter_section_field_show_body',
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
                'key' => 'newsletter_section_field_body',
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
                'key' => 'newsletter_section_field_show_features',
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
                'key' => 'newsletter_section_field_feature_items',
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
                  'key' => 'newsletter_section_field_feature_item',
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
                'key' => 'newsletter_section_field_show_proof',
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
                'key' => 'newsletter_section_field_proof_label',
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
                'key' => 'newsletter_section_field_form_title',
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
                'key' => 'newsletter_section_field_form_description',
                'fallback' => '',
              ),
              'type' => 'textarea',
              'group' => 'content',
              'rows' => 3,
            ),
            12 =>
            array (
              'key' => 'emailLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_email_label',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            13 =>
            array (
              'key' => 'placeholder',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_placeholder',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            14 =>
            array (
              'key' => 'helperText',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_helper_text',
                'fallback' => '',
              ),
              'type' => 'textarea',
              'group' => 'content',
              'rows' => 3,
            ),
            'newsletterFormSlugField' =>
            array (
              'key' => 'newsletterFormSlug',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_form_slug',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'content',
              'options' => $formChoices['options'],
              'optionLabels' => $formChoices['optionLabels'],
            ),
            15 =>
            array (
              'key' => 'buttonLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_button_label',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'navigation',
            ),
            16 =>
            array (
              'key' => 'align',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_align',
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
                  'key' => 'newsletter_section_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_align_right',
                  'fallback' => '',
                ),
              ),
            ),
            17 =>
            array (
              'key' => 'variant',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_variant',
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
                  'key' => 'newsletter_section_option_variant_subtle',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_variant_strong',
                  'fallback' => '',
                ),
                'dark' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_variant_dark',
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
                'key' => 'newsletter_section_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_section_surface_help',
                'fallback' => '',
              ),
            ),
            19 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_section_surface',
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
                'key' => 'newsletter_section_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_section_surface',
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
                'key' => 'newsletter_section_field_design_border_style',
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
                  'key' => 'newsletter_section_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_design_border_style_dotted',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_section_surface',
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
              'key' => 'designBorderWidth',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_section_surface',
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
                'key' => 'newsletter_section_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_section_surface',
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
                'key' => 'newsletter_section_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_section_surface',
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
                'key' => 'newsletter_section_field_design_shadow',
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
                  'key' => 'newsletter_section_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'newsletter_section_option_design_shadow_strong',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_section_surface',
                'fallback' => '',
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
              'key' => 'eyebrowTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_eyebrow_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'eyebrowStyle',
            ),
            27 =>
            array (
              'key' => 'titleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'titleStyle',
            ),
            28 =>
            array (
              'key' => 'subtitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_subtitle_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'subtitleStyle',
            ),
            29 =>
            array (
              'key' => 'bodyTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_body_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'bodyStyle',
            ),
            30 =>
            array (
              'key' => 'featureTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_feature_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'featureStyle',
            ),
            31 =>
            array (
              'key' => 'proofTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_proof_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'proofStyle',
            ),
            32 =>
            array (
              'key' => 'formTitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_form_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'formTitleStyle',
            ),
            33 =>
            array (
              'key' => 'formDescriptionTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_form_description_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'formDescriptionStyle',
            ),
            34 =>
            array (
              'key' => 'helperTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'newsletter_section_field_helper_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'helperTextStyle',
            ),
          )),
        );
    }
}
