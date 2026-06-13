<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\ContentSplitMedia;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'content_split_media';
    }

    public static function definition(): array
    {
        return
        array (
          'type' => 'content_split_media',
          'label' =>
          array (
            '__label' => true,
            'key' => 'content_split_media_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-columns',
          'category' => 'content',
          'i18n_module' => 'ContentSplitMedia',
          'render' => 'render.php',
          'preview_handler' => 'content_split_media',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/content-split-media.css',
            ),
            'preview_css' =>
            array (
              0 => 'css/content-split-media.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/content-split-media-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'showEyebrow' => 'on',
            'eyebrow' =>
            array (
              '__label' => true,
              'key' => 'content_split_media_default_eyebrow',
              'fallback' => '',
            ),
            'title' =>
            array (
              '__label' => true,
              'key' => 'content_split_media_default_title',
              'fallback' => '',
            ),
            'subtitle' =>
            array (
              '__label' => true,
              'key' => 'content_split_media_default_subtitle',
              'fallback' => '',
            ),
            'showBody' => 'on',
            'body' =>
            array (
              '__label' => true,
              'key' => 'content_split_media_default_body',
              'fallback' => '',
            ),
            'showFeatures' => 'on',
            'featureItems' =>
            array (
              '__label' => true,
              'key' => 'content_split_media_default_features',
              'fallback' => '',
            ),
            'showPrimaryCta' => 'on',
            'primaryLabel' =>
            array (
              '__label' => true,
              'key' => 'content_split_media_default_primary_label',
              'fallback' => '',
            ),
            'primaryUrl' => '#',
            'primaryTarget' => '_self',
            'showSecondaryCta' => 'on',
            'secondaryLabel' =>
            array (
              '__label' => true,
              'key' => 'content_split_media_default_secondary_label',
              'fallback' => '',
            ),
            'secondaryUrl' => '#',
            'secondaryTarget' => '_self',
            'placeholderTitle' =>
            array (
              '__label' => true,
              'key' => 'content_split_media_default_placeholder_title',
              'fallback' => '',
            ),
            'placeholderText' =>
            array (
              '__label' => true,
              'key' => 'content_split_media_default_placeholder_text',
              'fallback' => '',
            ),
            'emptyMessage' =>
            array (
              '__label' => true,
              'key' => 'content_split_media_default_empty_message',
              'fallback' => '',
            ),
            'mediaKind' => 'image',
            'imageSrc' => '',
            'imageAlt' => '',
            'videoUrl' => '',
            'videoPoster' => '',
            'preload' => 'metadata',
            'autoplay' => '',
            'loop' => '',
            'muted' => '',
            'mediaPosition' => 'right',
            'ratio' => 'balanced',
            'align' => 'left',
            'textVerticalAlign' => 'center',
            'variant' => 'subtle',
            'mediaFit' => 'cover',
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
                'key' => 'content_split_media_field_title',
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
                'key' => 'content_split_media_field_subtitle',
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
                'key' => 'content_split_media_field_show_eyebrow',
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
                'key' => 'content_split_media_field_eyebrow',
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
                'key' => 'content_split_media_field_show_body',
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
                'key' => 'content_split_media_field_body',
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
                'key' => 'content_split_media_field_show_features',
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
                'key' => 'content_split_media_field_feature_items',
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
                  'key' => 'content_split_media_field_feature_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            8 =>
            array (
              'key' => 'showPrimaryCta',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_show_primary_cta',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'navigation',
            ),
            9 =>
            array (
              'key' => 'primaryLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_primary_label',
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
            10 =>
            array (
              'key' => 'primaryUrl',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_primary_url',
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
            11 =>
            array (
              'key' => 'primaryTarget',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_primary_target',
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
                  'key' => 'content_split_media_option_target_self',
                  'fallback' => '',
                ),
                '_blank' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_target_blank',
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
            12 =>
            array (
              'key' => 'showSecondaryCta',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_show_secondary_cta',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'navigation',
            ),
            13 =>
            array (
              'key' => 'secondaryLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_secondary_label',
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
            14 =>
            array (
              'key' => 'secondaryUrl',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_secondary_url',
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
            15 =>
            array (
              'key' => 'secondaryTarget',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_secondary_target',
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
                  'key' => 'content_split_media_option_target_self',
                  'fallback' => '',
                ),
                '_blank' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_target_blank',
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
            16 =>
            array (
              'key' => 'mediaKind',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_media_kind',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'media',
              'options' =>
              array (
                0 => 'image',
                1 => 'video',
              ),
              'optionLabels' =>
              array (
                'image' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_media_kind_image',
                  'fallback' => '',
                ),
                'video' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_media_kind_video',
                  'fallback' => '',
                ),
              ),
            ),
            17 =>
            array (
              'key' => 'imageSrc',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_image',
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
              'condition' =>
              array (
                'field' => 'mediaKind',
                'operator' => 'equals',
                'value' => 'image',
              ),
            ),
            18 =>
            array (
              'key' => 'imageAlt',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_image_alt',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'media',
              'condition' =>
              array (
                'field' => 'mediaKind',
                'operator' => 'equals',
                'value' => 'image',
              ),
            ),
            19 =>
            array (
              'key' => 'videoUrl',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_video',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'media',
              'media' =>
              array (
                'mode' => 'files',
                'folder' => 'videos',
                'preview' => 'file',
              ),
              'condition' =>
              array (
                'field' => 'mediaKind',
                'operator' => 'equals',
                'value' => 'video',
              ),
            ),
            20 =>
            array (
              'key' => 'videoPoster',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_video_poster',
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
              'condition' =>
              array (
                'field' => 'mediaKind',
                'operator' => 'equals',
                'value' => 'video',
              ),
            ),
            21 =>
            array (
              'key' => 'mediaPosition',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_media_position',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'left',
                1 => 'right',
              ),
              'optionLabels' =>
              array (
                'left' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_media_position_left',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_media_position_right',
                  'fallback' => '',
                ),
              ),
            ),
            22 =>
            array (
              'key' => 'ratio',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_ratio',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'balanced',
                1 => 'content-wide',
                2 => 'media-wide',
              ),
              'optionLabels' =>
              array (
                'balanced' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_ratio_balanced',
                  'fallback' => '',
                ),
                'content-wide' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_ratio_content_wide',
                  'fallback' => '',
                ),
                'media-wide' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_ratio_media_wide',
                  'fallback' => '',
                ),
              ),
            ),
            23 =>
            array (
              'key' => 'align',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_align',
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
                  'key' => 'content_split_media_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_align_right',
                  'fallback' => '',
                ),
              ),
            ),
            24 =>
            array (
              'key' => 'textVerticalAlign',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_text_vertical_align',
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
                  'key' => 'content_split_media_option_text_vertical_align_top',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_text_vertical_align_center',
                  'fallback' => '',
                ),
                'bottom' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_text_vertical_align_bottom',
                  'fallback' => '',
                ),
              ),
            ),
            25 =>
            array (
              'key' => 'variant',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_variant',
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
                  'key' => 'content_split_media_option_variant_subtle',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_variant_strong',
                  'fallback' => '',
                ),
                'dark' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_variant_dark',
                  'fallback' => '',
                ),
              ),
            ),
            26 =>
            array (
              'key' => 'mediaFit',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_media_fit',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'layout',
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
                  'key' => 'content_split_media_option_media_fit_cover',
                  'fallback' => '',
                ),
                'contain' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_media_fit_contain',
                  'fallback' => '',
                ),
              ),
              'condition' =>
              array (
                'field' => 'mediaKind',
                'operator' => 'equals',
                'value' => 'image',
              ),
            ),
            27 =>
            array (
              'key' => 'preload',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_preload',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'metadata',
                1 => 'auto',
                2 => 'none',
              ),
              'optionLabels' =>
              array (
                'metadata' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_preload_metadata',
                  'fallback' => '',
                ),
                'auto' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_preload_auto',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_preload_none',
                  'fallback' => '',
                ),
              ),
              'condition' =>
              array (
                'field' => 'mediaKind',
                'operator' => 'equals',
                'value' => 'video',
              ),
            ),
            28 =>
            array (
              'key' => 'autoplay',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_autoplay',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
              'condition' =>
              array (
                'field' => 'mediaKind',
                'operator' => 'equals',
                'value' => 'video',
              ),
            ),
            29 =>
            array (
              'key' => 'loop',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_loop',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
              'condition' =>
              array (
                'field' => 'mediaKind',
                'operator' => 'equals',
                'value' => 'video',
              ),
            ),
            30 =>
            array (
              'key' => 'muted',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_muted',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
              'condition' =>
              array (
                'field' => 'mediaKind',
                'operator' => 'equals',
                'value' => 'video',
              ),
            ),
            31 =>
            array (
              'key' => 'useCustomDesign',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_section_surface_help',
                'fallback' => '',
              ),
            ),
            32 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            33 =>
            array (
              'key' => 'designTextColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            34 =>
            array (
              'key' => 'designBorderStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_design_border_style',
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
                  'key' => 'content_split_media_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_design_border_style_dotted',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            35 =>
            array (
              'key' => 'designBorderWidth',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_section_surface',
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
            36 =>
            array (
              'key' => 'designBorderColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            37 =>
            array (
              'key' => 'designRadius',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_section_surface',
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
            38 =>
            array (
              'key' => 'designShadow',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_design_shadow',
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
                  'key' => 'content_split_media_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'content_split_media_option_design_shadow_strong',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            39 =>
            array (
              'key' => 'eyebrowTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_eyebrow_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'eyebrowStyle',
            ),
            40 =>
            array (
              'key' => 'titleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'titleStyle',
            ),
            41 =>
            array (
              'key' => 'subtitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_subtitle_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'subtitleStyle',
            ),
            42 =>
            array (
              'key' => 'bodyTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_body_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'bodyStyle',
            ),
            43 =>
            array (
              'key' => 'featureTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'content_split_media_field_feature_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'featureStyle',
            ),
          ),
        );
    }
}
