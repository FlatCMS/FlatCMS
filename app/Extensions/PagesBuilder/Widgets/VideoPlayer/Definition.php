<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\VideoPlayer;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'video_player';
    }

    public static function definition(): array
    {
        return
        array (
          'type' => 'video_player',
          'label' =>
          array (
            '__label' => true,
            'key' => 'video_player_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-circle-play',
          'category' => 'media',
          'i18n_module' => 'VideoPlayer',
          'render' => 'render.php',
          'preview_handler' => 'video_player',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/video-player.css',
            ),
            'js' =>
            array (
              0 => 'js/video-player.js',
            ),
            'preview_css' =>
            array (
              0 => 'css/video-player.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/video-player.js',
              1 => 'js/video-player-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'title' =>
            array (
              '__label' => true,
              'key' => 'video_player_default_title',
              'fallback' => '',
            ),
            'subtitle' =>
            array (
              '__label' => true,
              'key' => 'video_player_default_subtitle',
              'fallback' => '',
            ),
            'videoUrl' => '',
            'posterImage' => '',
            'ambientMode' => '',
            'showHeader' => 'on',
            'autoplay' => '',
            'loop' => '',
            'muted' => '',
            'preload' => 'metadata',
            'height' => 420,
            'align' => 'left',
            'skin' => 'classic',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designOverlayColor' => '',
            'designOverlayOpacity' => 0,
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 1,
            'designBorderColor' => '',
            'designRadius' => 20,
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
                'key' => 'video_player_field_title',
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
                'key' => 'video_player_field_subtitle',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            2 =>
            array (
              'key' => 'videoUrl',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_video',
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
            ),
            3 =>
            array (
              'key' => 'posterImage',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_poster',
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
            4 =>
            array (
              'key' => 'ambientMode',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_ambient_mode',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'media',
            ),
            5 =>
            array (
              'key' => 'height',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_height',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 260,
              'max' => 720,
              'step' => 10,
            ),
            6 =>
            array (
              'key' => 'preload',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_preload',
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
                  'key' => 'video_player_option_preload_metadata',
                  'fallback' => '',
                ),
                'auto' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_preload_auto',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_preload_none',
                  'fallback' => '',
                ),
              ),
            ),
            7 =>
            array (
              'key' => 'align',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_align',
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
                  'key' => 'video_player_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_align_right',
                  'fallback' => '',
                ),
              ),
            ),
            8 =>
            array (
              'key' => 'skin',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_skin',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'classic',
                1 => 'soft',
                2 => 'cinema',
              ),
              'optionLabels' =>
              array (
                'classic' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_skin_classic',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_skin_soft',
                  'fallback' => '',
                ),
                'cinema' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_skin_cinema',
                  'fallback' => '',
                ),
              ),
            ),
            9 =>
            array (
              'key' => 'showHeader',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_show_header',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            10 =>
            array (
              'key' => 'autoplay',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_autoplay',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            11 =>
            array (
              'key' => 'loop',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_loop',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            12 =>
            array (
              'key' => 'muted',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_muted',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            13 =>
            array (
              'key' => 'useCustomDesign',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface_help',
                'fallback' => '',
              ),
            ),
            14 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            15 =>
            array (
              'key' => 'designOverlayColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_design_overlay_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            16 =>
            array (
              'key' => 'designOverlayOpacity',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_design_overlay_opacity',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface',
                'fallback' => '',
              ),
              'min' => 0,
              'max' => 100,
              'step' => 1,
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            17 =>
            array (
              'key' => 'designTextColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface',
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
              'key' => 'designBorderStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_design_border_style',
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
                  'key' => 'video_player_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_design_border_style_dotted',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface',
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
              'key' => 'designBorderWidth',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface',
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
            20 =>
            array (
              'key' => 'designBorderColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface',
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
              'key' => 'designRadius',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface',
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
            22 =>
            array (
              'key' => 'designShadow',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_design_shadow',
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
                  'key' => 'video_player_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'video_player_option_design_shadow_strong',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'video_player_section_surface',
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
              'key' => 'titleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'titleStyle',
            ),
            24 =>
            array (
              'key' => 'subtitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'video_player_field_subtitle_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'subtitleStyle',
            ),
          ),
        );
    }
}
