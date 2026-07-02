<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\Studio\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class StudioSchemaService
{
    public const SCHEMA = 'flatcms-studio.document.v1';
    private const SECTION_TYPES = ['content', 'hero', 'services', 'split', 'stats', 'testimonial', 'faq', 'cta', 'blog'];
    private const BLOCK_TYPES = ['heading', 'text', 'button', 'image', 'cards', 'form', 'map', 'plugin', 'spacer'];
    private const NAV_TARGETS = ['_self', '_blank'];
    private const PAGE_STATUSES = ['prototype', 'draft', 'published'];
    private const MEGA_ELEMENT_TYPES = ['link', 'text', 'button'];
    private const NAVBAR_ROWS = ['top', 'main', 'bottom'];
    private const NAVBAR_ZONES = ['left', 'center', 'right'];
    private const NAVBAR_ELEMENT_TYPES = ['brand', 'menu', 'slogan', 'text', 'language', 'cart', 'account', 'button'];
    private const BLOCK_LAYOUT_REGIONS = ['header_before', 'header_after', 'aside', 'footer'];
    private const LOGO_VARIANTS = ['compact', 'banner', 'banner_framed'];
    private const BLOCK_KIND_MAP = [
        'heading' => 'block',
        'text' => 'block',
        'button' => 'block',
        'image' => 'block',
        'cards' => 'widget',
        'form' => 'widget',
        'map' => 'widget',
        'plugin' => 'plugin',
        'spacer' => 'block',
    ];

    public function ui(): array
    {
        return [
            'status' => [
                'loading' => __('studio_status_loading', 'Studio'),
                'ready' => __('studio_status_ready', 'Studio'),
                'dirty' => __('studio_status_dirty', 'Studio'),
                'saving' => __('studio_status_saving', 'Studio'),
                'saved' => __('studio_status_saved', 'Studio'),
                'preview' => __('studio_status_preview', 'Studio'),
                'exported' => __('studio_status_exported', 'Studio'),
                'error' => __('studio_status_error', 'Studio'),
            ],
            'drawers' => [
                'sections' => [
                    'title' => __('studio_drawer_sections_title', 'Studio'),
                    'subtitle' => __('studio_drawer_sections_subtitle', 'Studio'),
                ],
                'blocks' => [
                    'title' => __('studio_drawer_blocks_title', 'Studio'),
                    'subtitle' => __('studio_drawer_blocks_subtitle', 'Studio'),
                ],
                'menu' => [
                    'title' => __('studio_drawer_menu_title', 'Studio'),
                    'subtitle' => __('studio_drawer_menu_subtitle', 'Studio'),
                    'note' => __('studio_drawer_menu_note', 'Studio'),
                ],
                'plugins' => [
                    'title' => __('studio_drawer_plugins_title', 'Studio'),
                    'subtitle' => __('studio_drawer_plugins_subtitle', 'Studio'),
                    'note' => __('studio_drawer_plugins_note', 'Studio'),
                ],
                'page' => [
                    'title' => __('studio_drawer_page_title', 'Studio'),
                    'subtitle' => __('studio_drawer_page_subtitle', 'Studio'),
                ],
            ],
            'canvas' => [
                'sectionDrop' => __('studio_canvas_section_drop', 'Studio'),
                'blockDrop' => __('studio_canvas_block_drop', 'Studio'),
                'navDrop' => __('studio_canvas_nav_drop', 'Studio'),
                'navZoneDrop' => __('studio_canvas_nav_zone_drop', 'Studio'),
                'megaColumnDrop' => __('studio_canvas_mega_column_drop', 'Studio'),
                'megaElementDrop' => __('studio_canvas_mega_element_drop', 'Studio'),
                'fakeMedia' => __('studio_canvas_fake_media', 'Studio'),
                'spacer' => __('studio_canvas_spacer', 'Studio'),
                'megaPreviewPrefix' => __('studio_canvas_mega_prefix', 'Studio'),
            ],
            'layout' => [
                'header' => [
                    'title' => __('studio_layout_header_title', 'Studio'),
                    'tag' => __('studio_layout_header_tag', 'Studio'),
                ],
                'header_before' => [
                    'title' => __('studio_layout_header_before_title', 'Studio'),
                    'tag' => __('studio_layout_header_before_tag', 'Studio'),
                ],
                'nav' => [
                    'title' => __('studio_layout_navigation_title', 'Studio'),
                    'tag' => __('studio_layout_navigation_tag', 'Studio'),
                ],
                'header_after' => [
                    'title' => __('studio_layout_header_after_title', 'Studio'),
                    'tag' => __('studio_layout_header_after_tag', 'Studio'),
                ],
                'main' => [
                    'title' => __('studio_layout_main_title', 'Studio'),
                    'tag' => __('studio_layout_main_tag', 'Studio'),
                ],
                'aside' => [
                    'title' => __('studio_layout_aside_title', 'Studio'),
                    'tag' => __('studio_layout_aside_tag', 'Studio'),
                ],
                'footer' => [
                    'title' => __('studio_layout_footer_title', 'Studio'),
                    'tag' => __('studio_layout_footer_tag', 'Studio'),
                ],
            ],
            'inspector' => [
                'navTitle' => __('studio_inspector_nav_title', 'Studio'),
                'navSubtitle' => __('studio_inspector_nav_subtitle', 'Studio'),
                'navItemTitlePrefix' => __('studio_inspector_nav_item_prefix', 'Studio'),
                'navItemSubtitle' => __('studio_inspector_nav_item_subtitle', 'Studio'),
                'navElementTitlePrefix' => __('studio_inspector_nav_element_prefix', 'Studio'),
                'navElementSubtitle' => __('studio_inspector_nav_element_subtitle', 'Studio'),
                'layoutRegionSubtitle' => __('studio_inspector_layout_region_subtitle', 'Studio'),
                'layoutMainHint' => __('studio_inspector_layout_main_hint', 'Studio'),
                'layoutBlockHint' => __('studio_inspector_layout_block_hint', 'Studio'),
                'sectionSubtitle' => __('studio_inspector_section_subtitle', 'Studio'),
                'blockSubtitle' => __('studio_inspector_block_subtitle', 'Studio'),
                'pageTitle' => __('studio_page_settings_title', 'Studio'),
                'designTitle' => __('studio_design_settings_title', 'Studio'),
                'navItemsTitle' => __('studio_nav_items_title', 'Studio'),
                'navLayoutTitle' => __('studio_nav_layout_title', 'Studio'),
                'navElementTitle' => __('studio_nav_elements_title', 'Studio'),
                'navRowTop' => __('studio_nav_row_top', 'Studio'),
                'navRowMain' => __('studio_nav_row_main', 'Studio'),
                'navRowBottom' => __('studio_nav_row_bottom', 'Studio'),
                'navZoneLeft' => __('studio_nav_zone_left', 'Studio'),
                'navZoneCenter' => __('studio_nav_zone_center', 'Studio'),
                'navZoneRight' => __('studio_nav_zone_right', 'Studio'),
                'sectionItemsTitle' => __('studio_repeater_items_title', 'Studio'),
                'blockItemsTitle' => __('studio_repeater_items_title', 'Studio'),
                'megaColumnsTitle' => __('studio_mega_columns_title', 'Studio'),
                'megaElementsTitle' => __('studio_mega_elements_title', 'Studio'),
                'summaryTitle' => __('studio_inspector_summary_title', 'Studio'),
                'actionsTitle' => __('studio_inspector_actions_title', 'Studio'),
                'locationLabel' => __('studio_inspector_location_label', 'Studio'),
                'typeLabel' => __('studio_inspector_type_label', 'Studio'),
                'blocksLabel' => __('studio_inspector_blocks_label', 'Studio'),
                'itemsLabel' => __('studio_inspector_items_label', 'Studio'),
                'statusLabel' => __('studio_inspector_status_label', 'Studio'),
                'sourceLabel' => __('studio_inspector_source_label', 'Studio'),
                'targetLabel' => __('studio_inspector_target_label', 'Studio'),
                'columnsLabel' => __('studio_inspector_columns_label', 'Studio'),
                'empty' => __('studio_empty_selection', 'Studio'),
            ],
            'actions' => [
                'duplicate' => __('studio_action_duplicate', 'Studio'),
                'delete' => __('studio_action_delete', 'Studio'),
                'remove' => __('studio_action_remove', 'Studio'),
                'moveUp' => __('studio_action_move_up', 'Studio'),
                'moveDown' => __('studio_action_move_down', 'Studio'),
                'addNavItem' => __('studio_action_add_nav_item', 'Studio'),
                'addNavElement' => __('studio_action_add_nav_element', 'Studio'),
                'addSectionItem' => __('studio_action_add_item', 'Studio'),
                'addBlockItem' => __('studio_action_add_item', 'Studio'),
                'addMegaColumn' => __('studio_action_add_mega_column', 'Studio'),
                'addMegaElement' => __('studio_action_add_mega_element', 'Studio'),
                'removeMegaColumn' => __('studio_action_remove_mega_column', 'Studio'),
                'removeMegaElement' => __('studio_action_remove_mega_element', 'Studio'),
                'refreshRender' => __('studio_action_refresh_render', 'Studio'),
                'openRender' => __('studio_action_open_render', 'Studio'),
            ],
            'buttons' => [
                'navbar' => __('studio_navbar_button', 'Studio'),
            ],
            'preview' => [
                'title' => __('studio_render_title', 'Studio'),
                'caption' => __('studio_render_caption', 'Studio'),
                'stale' => __('studio_render_stale', 'Studio'),
                'sourcePrefix' => __('studio_render_source_prefix', 'Studio'),
            ],
            'media' => [
                'chooseImage' => __('studio_media_choose_image', 'Studio'),
                'removeMedia' => __('studio_media_remove', 'Studio'),
                'noMedia' => __('studio_media_empty', 'Studio'),
                'unavailable' => __('studio_media_modal_unavailable', 'Studio'),
            ],
            'pagePanel' => [
                'sourceField' => [
                    'label' => __('studio_field_page_source', 'Studio'),
                    'hint' => __('studio_page_source_hint', 'Studio'),
                ],
                'documentFields' => [
                    $this->fieldBinding('page.status', __('studio_field_page_status', 'Studio'), 'select', $this->statusOptions()),
                ],
                'pageFields' => [
                    $this->fieldBinding('page.title', __('studio_field_page_title', 'Studio'), 'text', [], ['readonly' => true]),
                    $this->fieldBinding('page.slug', __('studio_field_page_slug', 'Studio'), 'text', [], ['readonly' => true]),
                ],
                'designFields' => [
                    $this->fieldBinding('design.primary', __('studio_field_design_primary', 'Studio'), 'color'),
                    $this->fieldBinding('design.accent', __('studio_field_design_accent', 'Studio'), 'color'),
                    $this->fieldBinding('design.ink', __('studio_field_design_ink', 'Studio'), 'color'),
                    $this->fieldBinding('design.paper', __('studio_field_design_paper', 'Studio'), 'color'),
                    $this->fieldBinding('design.soft', __('studio_field_design_soft', 'Studio'), 'color'),
                    $this->fieldBinding('design.radius', __('studio_field_design_radius', 'Studio'), 'number'),
                    $this->fieldBinding('design.width', __('studio_field_design_width', 'Studio'), 'number'),
                    $this->fieldBinding('design.font', __('studio_field_design_font', 'Studio')),
                ],
            ],
            'targets' => $this->targetOptions(),
            'exportFilename' => __('studio_export_filename', 'Studio'),
        ];
    }

    public function library(): array
    {
        return [
            'sections' => [
                'content' => [
                    'icon' => '¶',
                    'name' => __('studio_section_content_name', 'Studio'),
                    'help' => __('studio_section_content_help', 'Studio'),
                    'defaults' => $this->sectionDraft('content'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                        $this->fieldMeta('html', __('studio_field_html', 'Studio'), 'textarea'),
                    ],
                ],
                'hero' => [
                    'icon' => 'H1',
                    'name' => __('studio_section_hero_name', 'Studio'),
                    'help' => __('studio_section_hero_help', 'Studio'),
                    'defaults' => $this->sectionDraft('hero'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                        $this->fieldMeta('eyebrow', __('studio_field_eyebrow', 'Studio')),
                        $this->fieldMeta('title', __('studio_field_title', 'Studio')),
                        $this->fieldMeta('text', __('studio_field_text', 'Studio'), 'textarea'),
                        $this->fieldMeta('button_label', __('studio_field_button_label', 'Studio')),
                        $this->fieldMeta('button_url', __('studio_field_button_url', 'Studio')),
                    ],
                ],
                'services' => [
                    'icon' => '▦',
                    'name' => __('studio_section_services_name', 'Studio'),
                    'help' => __('studio_section_services_help', 'Studio'),
                    'defaults' => $this->sectionDraft('services'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                        $this->fieldMeta('eyebrow', __('studio_field_eyebrow', 'Studio')),
                        $this->fieldMeta('title', __('studio_field_title', 'Studio')),
                    ],
                    'repeater' => $this->repeaterMeta([
                        $this->fieldMeta('title', __('studio_field_title', 'Studio')),
                        $this->fieldMeta('text', __('studio_field_text', 'Studio'), 'textarea'),
                    ]),
                ],
                'split' => [
                    'icon' => '◫',
                    'name' => __('studio_section_split_name', 'Studio'),
                    'help' => __('studio_section_split_help', 'Studio'),
                    'defaults' => $this->sectionDraft('split'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                        $this->fieldMeta('eyebrow', __('studio_field_eyebrow', 'Studio')),
                        $this->fieldMeta('title', __('studio_field_title', 'Studio')),
                        $this->fieldMeta('text', __('studio_field_text', 'Studio'), 'textarea'),
                        $this->fieldMeta('button_label', __('studio_field_button_label', 'Studio')),
                        $this->fieldMeta('button_url', __('studio_field_button_url', 'Studio')),
                    ],
                ],
                'stats' => [
                    'icon' => '#',
                    'name' => __('studio_section_stats_name', 'Studio'),
                    'help' => __('studio_section_stats_help', 'Studio'),
                    'defaults' => $this->sectionDraft('stats'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                    ],
                    'repeater' => $this->repeaterMeta([
                        $this->fieldMeta('value', __('studio_field_value', 'Studio')),
                        $this->fieldMeta('label', __('studio_field_label', 'Studio')),
                    ]),
                ],
                'testimonial' => [
                    'icon' => '“”',
                    'name' => __('studio_section_testimonial_name', 'Studio'),
                    'help' => __('studio_section_testimonial_help', 'Studio'),
                    'defaults' => $this->sectionDraft('testimonial'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                        $this->fieldMeta('quote', __('studio_field_quote', 'Studio'), 'textarea'),
                        $this->fieldMeta('author', __('studio_field_author', 'Studio')),
                    ],
                ],
                'faq' => [
                    'icon' => '?',
                    'name' => __('studio_section_faq_name', 'Studio'),
                    'help' => __('studio_section_faq_help', 'Studio'),
                    'defaults' => $this->sectionDraft('faq'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                        $this->fieldMeta('eyebrow', __('studio_field_eyebrow', 'Studio')),
                        $this->fieldMeta('title', __('studio_field_title', 'Studio')),
                    ],
                    'repeater' => $this->repeaterMeta([
                        $this->fieldMeta('question', __('studio_field_question', 'Studio')),
                        $this->fieldMeta('answer', __('studio_field_answer', 'Studio'), 'textarea'),
                    ]),
                ],
                'cta' => [
                    'icon' => '↗',
                    'name' => __('studio_section_cta_name', 'Studio'),
                    'help' => __('studio_section_cta_help', 'Studio'),
                    'defaults' => $this->sectionDraft('cta'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                        $this->fieldMeta('title', __('studio_field_title', 'Studio')),
                        $this->fieldMeta('text', __('studio_field_text', 'Studio'), 'textarea'),
                        $this->fieldMeta('button_label', __('studio_field_button_label', 'Studio')),
                        $this->fieldMeta('button_url', __('studio_field_button_url', 'Studio')),
                    ],
                ],
                'blog' => [
                    'icon' => '▤',
                    'name' => __('studio_section_blog_name', 'Studio'),
                    'help' => __('studio_section_blog_help', 'Studio'),
                    'defaults' => $this->sectionDraft('blog'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                        $this->fieldMeta('eyebrow', __('studio_field_eyebrow', 'Studio')),
                        $this->fieldMeta('title', __('studio_field_title', 'Studio')),
                    ],
                    'repeater' => $this->repeaterMeta([
                        $this->fieldMeta('title', __('studio_field_title', 'Studio')),
                        $this->fieldMeta('text', __('studio_field_text', 'Studio'), 'textarea'),
                    ]),
                ],
            ],
            'blocks' => [
                'heading' => $this->blockMeta('heading', 'T', 'blocks'),
                'text' => $this->blockMeta('text', '¶', 'blocks'),
                'button' => $this->blockMeta('button', '□', 'blocks'),
                'image' => $this->blockMeta('image', '◎', 'blocks'),
                'cards' => $this->blockMeta('cards', '▦', 'blocks'),
                'form' => $this->blockMeta('form', '@', 'plugins'),
                'map' => $this->blockMeta('map', '⌖', 'plugins'),
                'plugin' => $this->blockMeta('plugin', '⚙', 'plugins'),
                'spacer' => $this->blockMeta('spacer', '↕', 'blocks'),
            ],
            'menuTools' => [
                'nav-link' => $this->toolMeta('↪', __('studio_menu_tool_nav_link_name', 'Studio'), __('studio_menu_tool_nav_link_help', 'Studio')),
                'nav-brand' => $this->toolMeta('Aa', __('studio_menu_tool_nav_brand_name', 'Studio'), __('studio_menu_tool_nav_brand_help', 'Studio')),
                'nav-slogan' => $this->toolMeta('≋', __('studio_menu_tool_nav_slogan_name', 'Studio'), __('studio_menu_tool_nav_slogan_help', 'Studio')),
                'nav-menu' => $this->toolMeta('☰', __('studio_menu_tool_nav_menu_name', 'Studio'), __('studio_menu_tool_nav_menu_help', 'Studio')),
                'nav-language' => $this->toolMeta('🌐', __('studio_menu_tool_nav_language_name', 'Studio'), __('studio_menu_tool_nav_language_help', 'Studio')),
                'nav-cart' => $this->toolMeta('🛒', __('studio_menu_tool_nav_cart_name', 'Studio'), __('studio_menu_tool_nav_cart_help', 'Studio')),
                'nav-account' => $this->toolMeta('☺', __('studio_menu_tool_nav_account_name', 'Studio'), __('studio_menu_tool_nav_account_help', 'Studio')),
                'nav-button' => $this->toolMeta('□', __('studio_menu_tool_nav_button_name', 'Studio'), __('studio_menu_tool_nav_button_help', 'Studio')),
                'mega-column' => $this->toolMeta('▥', __('studio_menu_tool_mega_column_name', 'Studio'), __('studio_menu_tool_mega_column_help', 'Studio')),
                'mega-link' => $this->toolMeta('•', __('studio_menu_tool_mega_link_name', 'Studio'), __('studio_menu_tool_mega_link_help', 'Studio')),
                'mega-text' => $this->toolMeta('T', __('studio_menu_tool_mega_text_name', 'Studio'), __('studio_menu_tool_mega_text_help', 'Studio')),
                'mega-button' => $this->toolMeta('□', __('studio_menu_tool_mega_button_name', 'Studio'), __('studio_menu_tool_mega_button_help', 'Studio')),
            ],
            'navbarElements' => [
                'brand' => [
                    'name' => __('studio_nav_element_brand_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_label', 'Studio')),
                        $this->fieldMeta('subtitle', __('studio_nav_element_slogan_name', 'Studio')),
                        $this->fieldMeta('src', __('studio_field_source', 'Studio'), 'media'),
                        $this->fieldMeta('alt', __('studio_field_alt', 'Studio')),
                    ],
                    'defaults' => [
                        'kind' => 'brand',
                        'label' => __('studio_default_nav_brand', 'Studio'),
                        'subtitle' => '',
                        'src' => '',
                        'alt' => __('studio_default_nav_brand', 'Studio'),
                        'variant' => 'compact',
                    ],
                ],
                'menu' => [
                    'name' => __('studio_nav_element_menu_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_label', 'Studio')),
                    ],
                    'defaults' => [
                        'kind' => 'menu',
                        'label' => __('studio_nav_element_menu_name', 'Studio'),
                    ],
                ],
                'slogan' => [
                    'name' => __('studio_nav_element_slogan_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('text', __('studio_field_text', 'Studio')),
                    ],
                    'defaults' => [
                        'kind' => 'slogan',
                        'text' => __('studio_sample_nav_slogan', 'Studio'),
                    ],
                ],
                'text' => [
                    'name' => __('studio_nav_element_text_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('text', __('studio_field_text', 'Studio')),
                    ],
                    'defaults' => [
                        'kind' => 'text',
                        'text' => __('studio_sample_nav_text', 'Studio'),
                    ],
                ],
                'language' => [
                    'name' => __('studio_nav_element_language_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_label', 'Studio')),
                    ],
                    'defaults' => [
                        'kind' => 'language',
                        'label' => __('studio_sample_nav_language', 'Studio'),
                    ],
                ],
                'cart' => [
                    'name' => __('studio_nav_element_cart_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_label', 'Studio')),
                    ],
                    'defaults' => [
                        'kind' => 'cart',
                        'label' => __('studio_sample_nav_cart', 'Studio'),
                    ],
                ],
                'account' => [
                    'name' => __('studio_nav_element_account_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_label', 'Studio')),
                    ],
                    'defaults' => [
                        'kind' => 'account',
                        'label' => __('studio_sample_nav_account', 'Studio'),
                    ],
                ],
                'button' => [
                    'name' => __('studio_nav_element_button_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_label', 'Studio')),
                        $this->fieldMeta('url', __('studio_field_url', 'Studio')),
                        $this->fieldMeta('target', __('studio_field_target', 'Studio'), 'select', $this->targetOptions()),
                    ],
                    'defaults' => [
                        'kind' => 'button',
                        'label' => __('studio_sample_button_label', 'Studio'),
                        'url' => '#',
                        'target' => '_self',
                    ],
                ],
            ],
            'megaElements' => [
                'link' => [
                    'name' => __('studio_mega_element_link_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_label', 'Studio')),
                        $this->fieldMeta('url', __('studio_field_url', 'Studio')),
                        $this->fieldMeta('target', __('studio_field_target', 'Studio'), 'select', $this->targetOptions()),
                    ],
                    'defaults' => [
                        'kind' => 'link',
                        'label' => __('studio_sample_link_label', 'Studio'),
                        'url' => '#',
                        'target' => '_self',
                    ],
                ],
                'text' => [
                    'name' => __('studio_mega_element_text_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('title', __('studio_field_title', 'Studio')),
                        $this->fieldMeta('text', __('studio_field_text', 'Studio'), 'textarea'),
                    ],
                    'defaults' => [
                        'kind' => 'text',
                        'title' => __('studio_sample_mega_text_title', 'Studio'),
                        'text' => __('studio_sample_mega_text_body', 'Studio'),
                    ],
                ],
                'button' => [
                    'name' => __('studio_mega_element_button_name', 'Studio'),
                    'fields' => [
                        $this->fieldMeta('label', __('studio_field_button_label', 'Studio')),
                        $this->fieldMeta('url', __('studio_field_url', 'Studio')),
                        $this->fieldMeta('target', __('studio_field_target', 'Studio'), 'select', $this->targetOptions()),
                    ],
                    'defaults' => [
                        'kind' => 'button',
                        'label' => __('studio_sample_button_label', 'Studio'),
                        'url' => '#',
                        'target' => '_self',
                    ],
                ],
            ],
        ];
    }

    public function defaultPage(array $sourcePage = []): array
    {
        $timestamp = gmdate('c');
        $source = $this->sourceDraft($sourcePage);
        $sourceTitle = (string) ($source['title'] ?? '');
        $sourceSlug = (string) ($source['slug'] ?? '');

        return [
            'schema' => self::SCHEMA,
            'page' => [
                'id' => (string) ($source['entity_id'] ?? ''),
                'title' => $sourceTitle,
                'slug' => $sourceSlug,
                'status' => 'prototype',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            'source' => $source,
            'design' => [
                'global' => $this->defaultDesign(),
            ],
            'navbar' => [
                'settings' => $this->defaultNavbarSettings(),
                'brand' => [
                    'label' => '',
                ],
                'rows' => $this->defaultNavbarRows(),
                'items' => [],
            ],
            'layout' => $this->defaultLayout(),
            'sections' => $this->defaultSectionsForSource($sourcePage),
        ];
    }

    public function normalizePage(array $payload, array $sourcePage = []): array
    {
        $default = $this->defaultPage($sourcePage);
        $pageData = is_array($payload['page'] ?? null) ? $payload['page'] : $payload;
        $sourceData = is_array($payload['source'] ?? null) ? $payload['source'] : [];
        $designData = is_array($payload['design'] ?? null) ? $payload['design'] : ['global' => $payload['global_design'] ?? null];
        $navbarData = is_array($payload['navbar'] ?? null) ? $payload['navbar'] : ($payload['nav'] ?? []);
        $resolvedSource = $this->normalizeSource($sourceData, $sourcePage);

        $normalized = [
            'schema' => self::SCHEMA,
            'page' => [
                'id' => (string) ($resolvedSource['entity_id'] ?? ''),
                'title' => $this->cleanText((string) ($resolvedSource['title'] ?? $default['page']['title']), 120),
                'slug' => $this->cleanSlug((string) ($resolvedSource['slug'] ?? $default['page']['slug'])),
                'status' => $this->normalizeStatus((string) ($pageData['status'] ?? $default['page']['status'])),
                'created_at' => $this->cleanText((string) ($pageData['created_at'] ?? $default['page']['created_at']), 80),
                'updated_at' => $this->cleanText((string) ($pageData['updated_at'] ?? $default['page']['updated_at']), 80),
            ],
            'source' => $resolvedSource,
            'design' => [
                'global' => $this->normalizeDesign(is_array($designData['global'] ?? null) ? $designData['global'] : ($payload['global_design'] ?? $default['design']['global'])),
            ],
            'navbar' => $this->normalizeNavbar(is_array($navbarData) ? $navbarData : $default['navbar']),
            'layout' => $this->normalizeLayout(is_array($payload['layout'] ?? null) ? $payload['layout'] : $default['layout']),
            'sections' => [],
        ];

        $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : $default['sections'];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $normalized['sections'][] = $this->normalizeSection($section);
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function importContentBlocks(string $html): array
    {
        $content = trim($html);
        if ($content === '') {
            return [];
        }

        if (preg_match('/<[^>]+>/', $content) !== 1) {
            return [$this->makeImportedTextBlock($content)];
        }

        if (!class_exists(DOMDocument::class)) {
            return [];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><!DOCTYPE html><html><body>' . $content . '</body></html>'
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        if ($loaded !== true) {
            return [];
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body instanceof DOMElement) {
            return [];
        }

        $blocks = [];
        foreach ($body->childNodes as $node) {
            if (!$this->appendImportedBlocksFromNode($node, $blocks)) {
                return [];
            }
        }

        return $blocks;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultSectionsForSource(array $sourcePage): array
    {
        $content = trim((string) ($sourcePage['content'] ?? ''));
        if ($content === '') {
            return [];
        }

        $section = $this->sectionDraft('content');
        $section['id'] = 'content-' . $this->shortId();
        $section['settings']['html'] = $content;
        $section['blocks'] = $this->importContentBlocks($content);

        return [$section];
    }

    /**
     * @return array<string, string>
     */
    private function sourceDraft(array $sourcePage): array
    {
        $entityId = $this->cleanId((string) ($sourcePage['id'] ?? ''));
        $title = $this->cleanText((string) ($sourcePage['title'] ?? ''), 120);
        $slug = $this->cleanSlug((string) ($sourcePage['slug'] ?? ''));
        $locale = $this->cleanText((string) ($sourcePage['locale'] ?? ''), 20);
        $translationGroup = $this->cleanId((string) ($sourcePage['translation_group'] ?? $entityId));
        $status = $this->cleanText((string) ($sourcePage['status'] ?? 'draft'), 20);

        return [
            'entity_type' => 'page',
            'entity_id' => $entityId,
            'translation_group' => $translationGroup,
            'locale' => $locale,
            'title' => $title,
            'slug' => $slug,
            'status' => $status !== '' ? $status : 'draft',
        ];
    }

    /**
     * @param array<string, mixed> $payloadSource
     * @return array<string, string>
     */
    private function normalizeSource(array $payloadSource, array $sourcePage): array
    {
        $fallback = $this->sourceDraft($sourcePage);

        return [
            'entity_type' => 'page',
            'entity_id' => $fallback['entity_id'],
            'translation_group' => $fallback['translation_group'],
            'locale' => $fallback['locale'],
            'title' => $fallback['title'],
            'slug' => $fallback['slug'],
            'status' => $fallback['status'],
        ];
    }

    private function defaultLayout(): array
    {
        return [
            'header_before' => [
                'blocks' => [],
            ],
            'header_after' => [
                'blocks' => [],
            ],
            'aside' => [
                'blocks' => [],
            ],
            'footer' => [
                'blocks' => [],
            ],
        ];
    }

    private function normalizeLayout(array $layout): array
    {
        $normalized = $this->defaultLayout();
        $legacyHeader = is_array($layout['header'] ?? null) ? $layout['header'] : [];

        foreach (self::BLOCK_LAYOUT_REGIONS as $regionName) {
            $region = is_array($layout[$regionName] ?? null) ? $layout[$regionName] : [];
            if ($regionName === 'header_before' && $region === [] && $legacyHeader !== []) {
                $region = $legacyHeader;
            }
            $normalized[$regionName] = $this->normalizeLayoutRegion($region);
        }

        return $normalized;
    }

    private function normalizeLayoutRegion(array $region): array
    {
        $blocks = [];
        $rawBlocks = is_array($region['blocks'] ?? null) ? $region['blocks'] : (array_is_list($region) ? $region : []);

        foreach (array_slice($rawBlocks, 0, 24) as $block) {
            if (!is_array($block)) {
                continue;
            }

            $blocks[] = $this->normalizeBlock($block);
        }

        return [
            'blocks' => $blocks,
        ];
    }

    private function normalizeNavbar(array $navbar): array
    {
        $brand = $navbar['brand'] ?? null;
        if (is_string($brand)) {
            $brand = ['label' => $brand];
        }

        $items = [];
        foreach (array_slice(is_array($navbar['items'] ?? null) ? $navbar['items'] : [], 0, 16) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = $this->normalizeNavItem($item);
        }

        $brandLabel = $this->cleanText((string) ($brand['label'] ?? ''), 80);
        $brandSubtitle = $this->cleanText((string) ($brand['subtitle'] ?? ''), 160);
        $brandVariant = $this->normalizeLogoVariant((string) ($brand['variant'] ?? 'compact'));
        $rows = is_array($navbar['rows'] ?? null)
            ? $this->normalizeNavbarRows($navbar['rows'])
            : $this->defaultNavbarRows();

        return [
            'settings' => $this->normalizeNavbarSettings(is_array($navbar['settings'] ?? null) ? $navbar['settings'] : []),
            'brand' => [
                'label' => $brandLabel,
                'subtitle' => $brandSubtitle,
                'variant' => $brandVariant,
            ],
            'rows' => $rows,
            'items' => $items,
        ];
    }

    private function defaultNavbarSettings(): array
    {
        return [
            'mega_columns_desktop' => '5',
        ];
    }

    private function normalizeNavbarSettings(array $settings): array
    {
        return [
            'mega_columns_desktop' => (string) max(1, min(6, (int) ($settings['mega_columns_desktop'] ?? 5))),
        ];
    }

    private function defaultNavbarRows(): array
    {
        return [
            'top' => [
                'left' => [],
                'center' => [],
                'right' => [],
            ],
            'main' => [
                'left' => [],
                'center' => [],
                'right' => [],
            ],
            'bottom' => [
                'left' => [],
                'center' => [],
                'right' => [],
            ],
        ];
    }

    private function normalizeNavbarRows(array $rows): array
    {
        $normalized = $this->defaultNavbarRows();
        $usedKinds = [];

        foreach (self::NAVBAR_ROWS as $rowName) {
            foreach (self::NAVBAR_ZONES as $zoneName) {
                $zone = $rows[$rowName][$zoneName] ?? [];
                $normalized[$rowName][$zoneName] = $this->normalizeNavbarZone($zone, $usedKinds);
            }
        }

        return $normalized;
    }

    private function normalizeNavbarZone(mixed $zone, array &$usedKinds): array
    {
        $elements = [];
        foreach (array_slice(is_array($zone) ? $zone : [], 0, 8) as $element) {
            if (!is_array($element)) {
                continue;
            }

            $normalized = $this->normalizeNavbarElement($element);
            $kind = $normalized['kind'];
            if (in_array($kind, ['brand', 'menu'], true)) {
                if (isset($usedKinds[$kind])) {
                    continue;
                }
                $usedKinds[$kind] = true;
            }

            $elements[] = $normalized;
        }

        return $elements;
    }

    private function navbarRowsAreEmpty(array $rows): bool
    {
        foreach (self::NAVBAR_ROWS as $rowName) {
            foreach (self::NAVBAR_ZONES as $zoneName) {
                if (($rows[$rowName][$zoneName] ?? []) !== []) {
                    return false;
                }
            }
        }

        return true;
    }

    private function normalizeNavbarElement(array $element): array
    {
        $kind = $this->cleanText((string) ($element['kind'] ?? 'text'), 24);
        if (!in_array($kind, self::NAVBAR_ELEMENT_TYPES, true)) {
            $kind = 'text';
        }

        $normalized = [
            'id' => $this->cleanId((string) ($element['id'] ?? ('nav-element-' . $this->shortId()))),
            'kind' => $kind,
        ];

        if (in_array($kind, ['brand', 'menu', 'language', 'cart', 'account'], true)) {
            $defaultLabel = match ($kind) {
                'brand' => __('studio_default_nav_brand', 'Studio'),
                'menu' => __('studio_nav_element_menu_name', 'Studio'),
                'language' => __('studio_sample_nav_language', 'Studio'),
                'cart' => __('studio_sample_nav_cart', 'Studio'),
                'account' => __('studio_sample_nav_account', 'Studio'),
                default => '',
            };
            $normalized['label'] = $this->cleanText((string) ($element['label'] ?? $defaultLabel), 80);
            if ($kind === 'brand') {
                $normalized['subtitle'] = $this->cleanText((string) ($element['subtitle'] ?? ''), 160);
                $normalized['src'] = $this->cleanText((string) ($element['src'] ?? ''), 220);
                $normalized['alt'] = $this->cleanText((string) ($element['alt'] ?? $normalized['label']), 160);
                $normalized['variant'] = $this->normalizeLogoVariant((string) ($element['variant'] ?? 'compact'));
            }
            return $normalized;
        }

        if ($kind === 'button') {
            $normalized['label'] = $this->cleanText((string) ($element['label'] ?? __('studio_sample_button_label', 'Studio')), 80);
            $normalized['url'] = $this->cleanText((string) ($element['url'] ?? '#'), 220);
            $normalized['target'] = $this->normalizeTarget((string) ($element['target'] ?? '_self'));
            return $normalized;
        }

        $normalized['text'] = $this->cleanText((string) ($element['text'] ?? __('studio_sample_nav_text', 'Studio')), 180);
        return $normalized;
    }

    private function normalizeNavItem(array $item): array
    {
        $megaMenu = $item['mega_menu'] ?? $item['mega'] ?? [];

        return [
            'id' => $this->cleanId((string) ($item['id'] ?? ('nav-' . $this->shortId()))),
            'label' => $this->cleanText((string) ($item['label'] ?? __('studio_sample_link_label', 'Studio')), 80),
            'url' => $this->cleanText((string) ($item['url'] ?? '#'), 220),
            'target' => $this->normalizeTarget((string) ($item['target'] ?? '_self')),
            'mega_menu' => $this->normalizeMegaMenu(is_array($megaMenu) ? $megaMenu : []),
        ];
    }

    private function normalizeMegaMenu(array $megaMenu): array
    {
        $columns = [];
        $slots = [];
        foreach (array_slice(is_array($megaMenu['columns'] ?? null) ? $megaMenu['columns'] : [], 0, 6) as $index => $column) {
            if (!is_array($column)) {
                continue;
            }
            $normalized = $this->normalizeMegaColumn($column, $index);
            $slot = (int) ($normalized['slot'] ?? $index);
            $slot = max(0, min(5, $slot));
            while (isset($slots[$slot]) && $slot < 5) {
                $slot++;
            }
            if (isset($slots[$slot])) {
                continue;
            }
            $normalized['slot'] = $slot;
            $slots[$slot] = true;
            $columns[] = $normalized;
        }

        usort($columns, static function (array $left, array $right): int {
            return (int) ($left['slot'] ?? 0) <=> (int) ($right['slot'] ?? 0);
        });

        return [
            'enabled' => (bool) ($megaMenu['enabled'] ?? false),
            'columns' => $columns,
        ];
    }

    private function normalizeMegaColumn(array $column, int $fallbackSlot = 0): array
    {
        $rawElements = [];
        if (is_array($column['elements'] ?? null)) {
            $rawElements = $column['elements'];
        } elseif (is_array($column['items'] ?? null)) {
            foreach ($column['items'] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $rawElements[] = [
                    'kind' => 'link',
                    'label' => $item['label'] ?? '',
                    'url' => $item['url'] ?? '#',
                    'target' => $item['target'] ?? '_self',
                ];
            }
        }

        $elements = [];
        foreach (array_slice($rawElements, 0, 12) as $element) {
            if (!is_array($element)) {
                continue;
            }
            $elements[] = $this->normalizeMegaElement($element);
        }

        return [
            'id' => $this->cleanId((string) ($column['id'] ?? ('mega-' . $this->shortId()))),
            'slot' => max(0, min(5, (int) ($column['slot'] ?? $fallbackSlot))),
            'title' => $this->cleanText((string) ($column['title'] ?? __('studio_sample_column_title', 'Studio')), 80),
            'elements' => $elements,
        ];
    }

    private function normalizeMegaElement(array $element): array
    {
        $kind = $this->cleanText((string) ($element['kind'] ?? ''), 20);
        if (!in_array($kind, self::MEGA_ELEMENT_TYPES, true)) {
            $kind = isset($element['url']) ? 'link' : 'text';
        }

        $normalized = [
            'id' => $this->cleanId((string) ($element['id'] ?? ('mega-element-' . $this->shortId()))),
            'kind' => $kind,
        ];

        if ($kind === 'text') {
            $normalized['title'] = $this->cleanText((string) ($element['title'] ?? __('studio_sample_mega_text_title', 'Studio')), 120);
            $normalized['text'] = $this->cleanText((string) ($element['text'] ?? __('studio_sample_mega_text_body', 'Studio')), 700);
            return $normalized;
        }

        $normalized['label'] = $this->cleanText((string) ($element['label'] ?? __('studio_sample_link_label', 'Studio')), 80);
        $normalized['url'] = $this->cleanText((string) ($element['url'] ?? '#'), 220);
        $normalized['target'] = $this->normalizeTarget((string) ($element['target'] ?? '_self'));

        return $normalized;
    }

    private function normalizeSection(array $section): array
    {
        $type = $this->cleanText((string) ($section['type'] ?? 'hero'), 40);
        if (!in_array($type, self::SECTION_TYPES, true)) {
            $type = 'hero';
        }

        $defaults = $this->sectionDraft($type);
        $settings = is_array($section['settings'] ?? null) ? $section['settings'] : $this->legacySectionSettings($section);

        $normalized = [
            'id' => $this->cleanId((string) ($section['id'] ?? ($type . '-' . $this->shortId()))),
            'type' => $type,
            'label' => $this->cleanText((string) ($section['label'] ?? $defaults['label']), 80),
            'settings' => $this->normalizeSettings($settings, $defaults['settings']),
            'items' => $this->normalizeSectionItems($type, is_array($section['items'] ?? null) ? $section['items'] : ($defaults['items'] ?? [])),
            'blocks' => [],
        ];

        foreach (array_slice(is_array($section['blocks'] ?? null) ? $section['blocks'] : [], 0, 24) as $block) {
            if (!is_array($block)) {
                continue;
            }
            $normalized['blocks'][] = $this->normalizeBlock($block);
        }

        return $normalized;
    }

    private function normalizeBlock(array $block): array
    {
        $type = $this->cleanText((string) ($block['type'] ?? 'text'), 40);
        if (!in_array($type, self::BLOCK_TYPES, true)) {
            $type = 'text';
        }

        $defaults = $this->blockDraft($type);
        $settings = is_array($block['settings'] ?? null) ? $block['settings'] : $this->legacyBlockSettings($block);

        return [
            'id' => $this->cleanId((string) ($block['id'] ?? ('block-' . $this->shortId()))),
            'kind' => $this->cleanText((string) ($block['kind'] ?? $defaults['kind']), 20),
            'type' => $type,
            'label' => $this->cleanText((string) ($block['label'] ?? $defaults['label']), 80),
            'settings' => $this->normalizeSettings($settings, $defaults['settings']),
            'items' => $this->normalizeBlockItems($type, is_array($block['items'] ?? null) ? $block['items'] : ($defaults['items'] ?? [])),
        ];
    }

    private function normalizeSettings(array $settings, array $defaults): array
    {
        $normalized = [];
        foreach ($defaults as $key => $defaultValue) {
            $max = match ($key) {
                'html' => 200000,
                'text', 'quote', 'answer' => 900,
                default => 220,
            };

            if ($key === 'html') {
                $normalized[$key] = $this->cleanHtml((string) ($settings[$key] ?? $defaultValue), $max);
                continue;
            }

            if ($key === 'height') {
                $normalized[$key] = $this->normalizeImageHeight((string) ($settings[$key] ?? $defaultValue));
                continue;
            }

            $normalized[$key] = $this->cleanText((string) ($settings[$key] ?? $defaultValue), $max);
        }

        return $normalized;
    }

    private function normalizeSectionItems(string $type, array $items): array
    {
        $fields = match ($type) {
            'services', 'blog' => ['title', 'text'],
            'stats' => ['value', 'label'],
            'faq' => ['question', 'answer'],
            default => [],
        };

        if ($fields === []) {
            return [];
        }

        $normalized = [];
        foreach (array_slice($items, 0, 12) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $row = [];
            foreach ($fields as $field) {
                $row[$field] = $this->cleanText((string) ($item[$field] ?? ''), $field === 'answer' || $field === 'text' ? 700 : 160);
            }
            $normalized[] = $row;
        }

        return $normalized;
    }

    private function normalizeBlockItems(string $type, array $items): array
    {
        if ($type !== 'cards') {
            return [];
        }

        $normalized = [];
        foreach (array_slice($items, 0, 12) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized[] = [
                'title' => $this->cleanText((string) ($item['title'] ?? ''), 160),
                'text' => $this->cleanText((string) ($item['text'] ?? ''), 500),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     */
    private function appendImportedBlocksFromNode(DOMNode $node, array &$blocks): bool
    {
        if ($node instanceof DOMText) {
            $text = $this->normalizeImportedText($node->nodeValue ?? '');
            if ($text !== '') {
                $blocks[] = $this->makeImportedTextBlock($text);
            }

            return true;
        }

        if (!$node instanceof DOMElement) {
            return true;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style'], true)) {
            return true;
        }

        if (in_array($tag, ['form', 'iframe', 'video', 'audio', 'table', 'details', 'summary', 'select', 'textarea', 'input', 'button', 'embed', 'object', 'canvas'], true)) {
            return false;
        }

        if ($this->containsUnsupportedAnchors($node) && !$this->isButtonLinkGroup($node)) {
            return false;
        }

        if (preg_match('/^h[1-6]$/', $tag) === 1) {
            $text = $this->normalizeImportedText($node->textContent ?? '');
            if ($text !== '') {
                $blocks[] = $this->makeImportedHeadingBlock($text);
            }

            return true;
        }

        if (in_array($tag, ['ul', 'ol'], true)) {
            $text = $this->listTextFromElement($node);
            if ($text !== '') {
                $blocks[] = $this->makeImportedTextBlock($text);
            }

            return true;
        }

        if ($tag === 'img') {
            $blocks[] = $this->makeImportedImageBlock($node);
            return true;
        }

        if ($tag === 'a') {
            if (!$this->elementHasClass($node, 'btn')) {
                return false;
            }

            $button = $this->makeImportedButtonBlock($node);
            if ($button !== null) {
                $blocks[] = $button;
            }

            return true;
        }

        if ($this->isButtonLinkGroup($node)) {
            foreach ($node->getElementsByTagName('a') as $anchor) {
                if (!$anchor instanceof DOMElement) {
                    continue;
                }

                $button = $this->makeImportedButtonBlock($anchor);
                if ($button !== null) {
                    $blocks[] = $button;
                }
            }

            return true;
        }

        $image = $this->extractStandaloneImage($node);
        if ($image instanceof DOMElement) {
            $blocks[] = $this->makeImportedImageBlock($image);
            return true;
        }

        $text = $this->normalizeImportedText($node->textContent ?? '');
        if ($text !== '') {
            $blocks[] = $this->makeImportedTextBlock($text);
        }

        return true;
    }

    private function containsUnsupportedAnchors(DOMElement $element): bool
    {
        foreach ($element->getElementsByTagName('a') as $anchor) {
            if (!$anchor instanceof DOMElement) {
                continue;
            }

            if (!$this->elementHasClass($anchor, 'btn')) {
                return true;
            }
        }

        return false;
    }

    private function isButtonLinkGroup(DOMElement $element): bool
    {
        $anchors = $element->getElementsByTagName('a');
        if ($anchors->length === 0) {
            return false;
        }

        foreach ($anchors as $anchor) {
            if (!$anchor instanceof DOMElement || !$this->elementHasClass($anchor, 'btn')) {
                return false;
            }
        }

        return true;
    }

    private function extractStandaloneImage(DOMElement $element): ?DOMElement
    {
        $images = $element->getElementsByTagName('img');
        if ($images->length !== 1) {
            return null;
        }

        $image = $images->item(0);
        if (!$image instanceof DOMElement) {
            return null;
        }

        $text = $this->normalizeImportedText($element->textContent ?? '');
        return $text === '' ? $image : null;
    }

    private function listTextFromElement(DOMElement $element): string
    {
        $tag = strtolower($element->tagName);
        $items = [];
        $index = 1;

        foreach ($element->childNodes as $child) {
            if (!$child instanceof DOMElement || strtolower($child->tagName) !== 'li') {
                continue;
            }

            $text = $this->normalizeImportedText($child->textContent ?? '');
            if ($text === '') {
                continue;
            }

            $prefix = $tag === 'ol' ? ($index . '. ') : '* ';
            $items[] = $prefix . $text;
            $index++;
        }

        return implode("\n", $items);
    }

    private function makeImportedHeadingBlock(string $text): array
    {
        return $this->hydrateBlock($this->blockDraft('heading', [
            'settings' => [
                'text' => $text,
            ],
        ]));
    }

    private function makeImportedTextBlock(string $text): array
    {
        return $this->hydrateBlock($this->blockDraft('text', [
            'settings' => [
                'text' => $text,
            ],
        ]));
    }

    private function makeImportedImageBlock(DOMElement $image): array
    {
        return $this->hydrateBlock($this->blockDraft('image', [
            'settings' => [
                'src' => trim((string) $image->getAttribute('src')),
                'alt' => $this->normalizeImportedText((string) $image->getAttribute('alt')),
            ],
        ]));
    }

    private function makeImportedButtonBlock(DOMElement $anchor): ?array
    {
        $text = $this->normalizeImportedText($anchor->textContent ?? '');
        $url = trim((string) $anchor->getAttribute('href'));
        if ($text === '' || $url === '') {
            return null;
        }

        return $this->hydrateBlock($this->blockDraft('button', [
            'settings' => [
                'text' => $text,
                'url' => $url,
            ],
        ]));
    }

    private function elementHasClass(DOMElement $element, string $className): bool
    {
        $classAttribute = trim((string) $element->getAttribute('class'));
        if ($classAttribute === '') {
            return false;
        }

        $classes = preg_split('/\s+/', $classAttribute) ?: [];
        return in_array($className, $classes, true);
    }

    private function normalizeImportedText(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        return trim($normalized);
    }

    private function defaultDesign(): array
    {
        return [
            'primary' => '#4F46E5',
            'accent' => '#111827',
            'ink' => '#111827',
            'paper' => '#FFFFFF',
            'soft' => '#F7F8FA',
            'radius' => '8',
            'width' => '1180',
            'font' => 'Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        ];
    }

    private function normalizeDesign(array $design): array
    {
        return [
            'primary' => $this->cleanColor((string) ($design['primary'] ?? '#4F46E5'), '#4F46E5'),
            'accent' => $this->cleanColor((string) ($design['accent'] ?? '#111827'), '#111827'),
            'ink' => $this->cleanColor((string) ($design['ink'] ?? '#111827'), '#111827'),
            'paper' => $this->cleanColor((string) ($design['paper'] ?? '#FFFFFF'), '#FFFFFF'),
            'soft' => $this->cleanColor((string) ($design['soft'] ?? '#F7F8FA'), '#F7F8FA'),
            'radius' => (string) max(0, min(24, (int) ($design['radius'] ?? 8))),
            'width' => (string) max(960, min(1440, (int) ($design['width'] ?? 1180))),
            'font' => $this->cleanText((string) ($design['font'] ?? $this->defaultDesign()['font']), 180),
        ];
    }

    private function sectionDraft(string $type): array
    {
        return match ($type) {
            'content' => [
                'type' => 'content',
                'label' => __('studio_section_content_name', 'Studio'),
                'settings' => [
                    'html' => '',
                ],
                'items' => [],
                'blocks' => [],
            ],
            'hero' => [
                'type' => 'hero',
                'label' => __('studio_section_hero_name', 'Studio'),
                'settings' => [
                    'eyebrow' => __('studio_sample_hero_eyebrow', 'Studio'),
                    'title' => __('studio_sample_hero_title', 'Studio'),
                    'text' => __('studio_sample_hero_text', 'Studio'),
                    'button_label' => __('studio_sample_hero_button', 'Studio'),
                    'button_url' => '#studio-canvas',
                ],
                'items' => [],
                'blocks' => [
                    $this->blockDraft('button', [
                        'label' => __('studio_sample_secondary_button_label', 'Studio'),
                        'settings' => [
                            'text' => __('studio_sample_secondary_button_text', 'Studio'),
                            'url' => '#services',
                        ],
                    ]),
                ],
            ],
            'services' => [
                'type' => 'services',
                'label' => __('studio_section_services_name', 'Studio'),
                'settings' => [
                    'eyebrow' => __('studio_sample_services_eyebrow', 'Studio'),
                    'title' => __('studio_sample_services_title', 'Studio'),
                ],
                'items' => [
                    [
                        'title' => __('studio_sample_services_item_1_title', 'Studio'),
                        'text' => __('studio_sample_services_item_1_text', 'Studio'),
                    ],
                    [
                        'title' => __('studio_sample_services_item_2_title', 'Studio'),
                        'text' => __('studio_sample_services_item_2_text', 'Studio'),
                    ],
                    [
                        'title' => __('studio_sample_services_item_3_title', 'Studio'),
                        'text' => __('studio_sample_services_item_3_text', 'Studio'),
                    ],
                ],
                'blocks' => [],
            ],
            'split' => [
                'type' => 'split',
                'label' => __('studio_section_split_name', 'Studio'),
                'settings' => [
                    'eyebrow' => __('studio_sample_split_eyebrow', 'Studio'),
                    'title' => __('studio_sample_split_title', 'Studio'),
                    'text' => __('studio_sample_split_text', 'Studio'),
                    'button_label' => __('studio_sample_split_button', 'Studio'),
                    'button_url' => '#',
                ],
                'items' => [],
                'blocks' => [],
            ],
            'stats' => [
                'type' => 'stats',
                'label' => __('studio_section_stats_name', 'Studio'),
                'settings' => [],
                'items' => [
                    ['value' => '20+', 'label' => __('studio_sample_stats_item_1', 'Studio')],
                    ['value' => '100%', 'label' => __('studio_sample_stats_item_2', 'Studio')],
                    ['value' => '0', 'label' => __('studio_sample_stats_item_3', 'Studio')],
                ],
                'blocks' => [],
            ],
            'testimonial' => [
                'type' => 'testimonial',
                'label' => __('studio_section_testimonial_name', 'Studio'),
                'settings' => [
                    'quote' => __('studio_sample_testimonial_quote', 'Studio'),
                    'author' => __('studio_sample_testimonial_author', 'Studio'),
                ],
                'items' => [],
                'blocks' => [],
            ],
            'faq' => [
                'type' => 'faq',
                'label' => __('studio_section_faq_name', 'Studio'),
                'settings' => [
                    'eyebrow' => __('studio_sample_faq_eyebrow', 'Studio'),
                    'title' => __('studio_sample_faq_title', 'Studio'),
                ],
                'items' => [
                    [
                        'question' => __('studio_sample_faq_item_1_question', 'Studio'),
                        'answer' => __('studio_sample_faq_item_1_answer', 'Studio'),
                    ],
                    [
                        'question' => __('studio_sample_faq_item_2_question', 'Studio'),
                        'answer' => __('studio_sample_faq_item_2_answer', 'Studio'),
                    ],
                ],
                'blocks' => [],
            ],
            'cta' => [
                'type' => 'cta',
                'label' => __('studio_section_cta_name', 'Studio'),
                'settings' => [
                    'title' => __('studio_sample_cta_title', 'Studio'),
                    'text' => __('studio_sample_cta_text', 'Studio'),
                    'button_label' => __('studio_sample_cta_button', 'Studio'),
                    'button_url' => '#save',
                ],
                'items' => [],
                'blocks' => [],
            ],
            'blog' => [
                'type' => 'blog',
                'label' => __('studio_section_blog_name', 'Studio'),
                'settings' => [
                    'eyebrow' => __('studio_sample_blog_eyebrow', 'Studio'),
                    'title' => __('studio_sample_blog_title', 'Studio'),
                ],
                'items' => [
                    [
                        'title' => __('studio_sample_blog_item_1_title', 'Studio'),
                        'text' => __('studio_sample_blog_item_1_text', 'Studio'),
                    ],
                    [
                        'title' => __('studio_sample_blog_item_2_title', 'Studio'),
                        'text' => __('studio_sample_blog_item_2_text', 'Studio'),
                    ],
                    [
                        'title' => __('studio_sample_blog_item_3_title', 'Studio'),
                        'text' => __('studio_sample_blog_item_3_text', 'Studio'),
                    ],
                ],
                'blocks' => [],
            ],
            default => $this->sectionDraft('hero'),
        };
    }

    private function blockDraft(string $type, array $override = []): array
    {
        $draft = match ($type) {
            'heading' => [
                'kind' => self::BLOCK_KIND_MAP['heading'],
                'type' => 'heading',
                'label' => __('studio_block_heading_name', 'Studio'),
                'settings' => [
                    'text' => __('studio_sample_heading_text', 'Studio'),
                ],
                'items' => [],
            ],
            'text' => [
                'kind' => self::BLOCK_KIND_MAP['text'],
                'type' => 'text',
                'label' => __('studio_block_text_name', 'Studio'),
                'settings' => [
                    'text' => __('studio_sample_text_body', 'Studio'),
                ],
                'items' => [],
            ],
            'button' => [
                'kind' => self::BLOCK_KIND_MAP['button'],
                'type' => 'button',
                'label' => __('studio_block_button_name', 'Studio'),
                'settings' => [
                    'text' => __('studio_sample_button_label', 'Studio'),
                    'url' => '#',
                ],
                'items' => [],
            ],
            'image' => [
                'kind' => self::BLOCK_KIND_MAP['image'],
                'type' => 'image',
                'label' => __('studio_block_image_name', 'Studio'),
                'settings' => [
                    'src' => '',
                    'alt' => __('studio_sample_image_alt', 'Studio'),
                    'height' => 'auto',
                ],
                'items' => [],
            ],
            'cards' => [
                'kind' => self::BLOCK_KIND_MAP['cards'],
                'type' => 'cards',
                'label' => __('studio_block_cards_name', 'Studio'),
                'settings' => [],
                'items' => [
                    [
                        'title' => __('studio_sample_card_1_title', 'Studio'),
                        'text' => __('studio_sample_card_1_text', 'Studio'),
                    ],
                    [
                        'title' => __('studio_sample_card_2_title', 'Studio'),
                        'text' => __('studio_sample_card_2_text', 'Studio'),
                    ],
                ],
            ],
            'form' => [
                'kind' => self::BLOCK_KIND_MAP['form'],
                'type' => 'form',
                'label' => __('studio_block_form_name', 'Studio'),
                'settings' => [
                    'text' => __('studio_sample_form_text', 'Studio'),
                ],
                'items' => [],
            ],
            'map' => [
                'kind' => self::BLOCK_KIND_MAP['map'],
                'type' => 'map',
                'label' => __('studio_block_map_name', 'Studio'),
                'settings' => [
                    'address' => __('studio_sample_map_address', 'Studio'),
                ],
                'items' => [],
            ],
            'plugin' => [
                'kind' => self::BLOCK_KIND_MAP['plugin'],
                'type' => 'plugin',
                'label' => __('studio_block_plugin_name', 'Studio'),
                'settings' => [
                    'plugin' => __('studio_sample_plugin_name', 'Studio'),
                    'text' => __('studio_sample_plugin_text', 'Studio'),
                ],
                'items' => [],
            ],
            'spacer' => [
                'kind' => self::BLOCK_KIND_MAP['spacer'],
                'type' => 'spacer',
                'label' => __('studio_block_spacer_name', 'Studio'),
                'settings' => [],
                'items' => [],
            ],
            default => $this->blockDraft('text'),
        };

        if (isset($override['label'])) {
            $draft['label'] = $override['label'];
        }

        if (isset($override['settings']) && is_array($override['settings'])) {
            $draft['settings'] = array_replace($draft['settings'], $override['settings']);
        }

        if (isset($override['items']) && is_array($override['items'])) {
            $draft['items'] = $override['items'];
        }

        return $draft;
    }

    private function blockMeta(string $type, string $icon, string $drawer): array
    {
        $labels = [
            'heading' => [__('studio_block_heading_name', 'Studio'), __('studio_block_heading_help', 'Studio')],
            'text' => [__('studio_block_text_name', 'Studio'), __('studio_block_text_help', 'Studio')],
            'button' => [__('studio_block_button_name', 'Studio'), __('studio_block_button_help', 'Studio')],
            'image' => [__('studio_block_image_name', 'Studio'), __('studio_block_image_help', 'Studio')],
            'cards' => [__('studio_block_cards_name', 'Studio'), __('studio_block_cards_help', 'Studio')],
            'form' => [__('studio_block_form_name', 'Studio'), __('studio_block_form_help', 'Studio')],
            'map' => [__('studio_block_map_name', 'Studio'), __('studio_block_map_help', 'Studio')],
            'plugin' => [__('studio_block_plugin_name', 'Studio'), __('studio_block_plugin_help', 'Studio')],
            'spacer' => [__('studio_block_spacer_name', 'Studio'), __('studio_block_spacer_help', 'Studio')],
        ];

        $fields = match ($type) {
            'heading' => [
                $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                $this->fieldMeta('text', __('studio_field_title', 'Studio')),
            ],
            'text' => [
                $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                $this->fieldMeta('text', __('studio_field_text', 'Studio'), 'textarea'),
            ],
            'button' => [
                $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                $this->fieldMeta('text', __('studio_field_button_label', 'Studio')),
                $this->fieldMeta('url', __('studio_field_url', 'Studio')),
            ],
            'image' => [
                $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                $this->fieldMeta('src', __('studio_field_source', 'Studio'), 'media'),
                $this->fieldMeta('alt', __('studio_field_alt', 'Studio')),
                $this->fieldMeta('height', __('studio_field_image_height', 'Studio'), 'select', $this->imageHeightOptions()),
            ],
            'cards' => [
                $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
            ],
            'form' => [
                $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                $this->fieldMeta('text', __('studio_field_text', 'Studio')),
            ],
            'map' => [
                $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                $this->fieldMeta('address', __('studio_field_address', 'Studio')),
            ],
            'plugin' => [
                $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
                $this->fieldMeta('plugin', __('studio_field_plugin', 'Studio')),
                $this->fieldMeta('text', __('studio_field_text', 'Studio')),
            ],
            'spacer' => [
                $this->fieldMeta('label', __('studio_field_internal_name', 'Studio')),
            ],
            default => [],
        };

        $meta = [
            'icon' => $icon,
            'drawer' => $drawer,
            'kind' => self::BLOCK_KIND_MAP[$type],
            'name' => $labels[$type][0],
            'help' => $labels[$type][1],
            'defaults' => $this->blockDraft($type),
            'fields' => $fields,
        ];

        if ($type === 'cards') {
            $meta['repeater'] = $this->repeaterMeta([
                $this->fieldMeta('title', __('studio_field_title', 'Studio')),
                $this->fieldMeta('text', __('studio_field_text', 'Studio'), 'textarea'),
            ]);
        }

        return $meta;
    }

    private function hydrateSection(array $section): array
    {
        $section['id'] = $this->cleanId((string) ($section['id'] ?? ($section['type'] . '-' . $this->shortId())));
        $section['blocks'] = array_map(function ($block): array {
            return $this->hydrateBlock(is_array($block) ? $block : $this->blockDraft('text'));
        }, is_array($section['blocks'] ?? null) ? $section['blocks'] : []);

        return $section;
    }

    private function hydrateBlock(array $block): array
    {
        $block['id'] = $this->cleanId((string) ($block['id'] ?? ('block-' . $this->shortId())));
        return $block;
    }

    private function hydrateNavItem(array $item): array
    {
        $item['id'] = $this->cleanId((string) ($item['id'] ?? ('nav-' . $this->shortId())));
        $columns = is_array($item['mega_menu']['columns'] ?? null) ? $item['mega_menu']['columns'] : [];
        $item['mega_menu']['columns'] = array_map(function ($column): array {
            return $this->hydrateMegaColumn(is_array($column) ? $column : []);
        }, $columns);

        return $item;
    }

    private function hydrateNavbarElement(array $element): array
    {
        $element['id'] = $this->cleanId((string) ($element['id'] ?? ('nav-element-' . $this->shortId())));
        return $element;
    }

    private function hydrateMegaColumn(array $column): array
    {
        $column['id'] = $this->cleanId((string) ($column['id'] ?? ('mega-' . $this->shortId())));
        $column['slot'] = max(0, min(5, (int) ($column['slot'] ?? 0)));
        $elements = is_array($column['elements'] ?? null) ? $column['elements'] : [];
        $column['elements'] = array_map(function ($element): array {
            return $this->hydrateMegaElement(is_array($element) ? $element : []);
        }, $elements);

        return $column;
    }

    private function hydrateMegaElement(array $element): array
    {
        $element['id'] = $this->cleanId((string) ($element['id'] ?? ('mega-element-' . $this->shortId())));
        return $element;
    }

    private function legacySectionSettings(array $section): array
    {
        $settings = [];
        foreach (['eyebrow', 'title', 'text', 'button_label', 'button_url', 'quote', 'author'] as $key) {
            if (array_key_exists($key, $section)) {
                $settings[$key] = $section[$key];
            }
        }

        return $settings;
    }

    private function legacyBlockSettings(array $block): array
    {
        $settings = [];
        foreach (['text', 'url', 'src', 'alt', 'height', 'plugin', 'provider', 'address'] as $key) {
            if (array_key_exists($key, $block)) {
                $settings[$key] = $block[$key];
            }
        }

        return $settings;
    }

    private function fieldMeta(string $key, string $label, string $type = 'text', array $options = []): array
    {
        $meta = [
            'key' => $key,
            'label' => $label,
            'type' => $type,
        ];

        if ($options !== []) {
            $meta['options'] = $options;
        }

        return $meta;
    }

    private function fieldBinding(string $bind, string $label, string $type = 'text', array $options = [], array $attributes = []): array
    {
        $meta = [
            'bind' => $bind,
            'label' => $label,
            'type' => $type,
        ];

        if ($options !== []) {
            $meta['options'] = $options;
        }

        if ($attributes !== []) {
            $meta = array_merge($meta, $attributes);
        }

        return $meta;
    }

    private function repeaterMeta(array $fields): array
    {
        return [
            'key' => 'items',
            'label' => __('studio_repeater_items_title', 'Studio'),
            'fields' => $fields,
        ];
    }

    private function toolMeta(string $icon, string $name, string $help): array
    {
        return [
            'icon' => $icon,
            'name' => $name,
            'help' => $help,
        ];
    }

    private function statusOptions(): array
    {
        return [
            ['value' => 'prototype', 'label' => __('studio_status_option_prototype', 'Studio')],
            ['value' => 'draft', 'label' => __('studio_status_option_draft', 'Studio')],
            ['value' => 'published', 'label' => __('studio_status_option_published', 'Studio')],
        ];
    }

    private function targetOptions(): array
    {
        return [
            ['value' => '_self', 'label' => __('studio_target_self', 'Studio')],
            ['value' => '_blank', 'label' => __('studio_target_blank', 'Studio')],
        ];
    }

    private function imageHeightOptions(): array
    {
        return [
            ['value' => 'auto', 'label' => __('studio_image_height_auto', 'Studio')],
            ['value' => '180', 'label' => __('studio_image_height_180', 'Studio')],
            ['value' => '240', 'label' => __('studio_image_height_240', 'Studio')],
            ['value' => '320', 'label' => __('studio_image_height_320', 'Studio')],
            ['value' => '420', 'label' => __('studio_image_height_420', 'Studio')],
            ['value' => '560', 'label' => __('studio_image_height_560', 'Studio')],
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        return in_array($status, self::PAGE_STATUSES, true) ? $status : 'prototype';
    }

    private function normalizeTarget(string $target): string
    {
        return in_array($target, self::NAV_TARGETS, true) ? $target : '_self';
    }

    private function normalizeImageHeight(string $value): string
    {
        $allowed = ['auto', '180', '240', '320', '420', '560'];
        $normalized = trim($value);

        return in_array($normalized, $allowed, true) ? $normalized : 'auto';
    }

    private function normalizeLogoVariant(string $value): string
    {
        $normalized = trim($value);

        return in_array($normalized, self::LOGO_VARIANTS, true) ? $normalized : 'compact';
    }

    private function cleanText(string $value, int $max): string
    {
        $value = trim(strip_tags($value));
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max, 'UTF-8');
        }

        return substr($value, 0, $max);
    }

    private function cleanHtml(string $value, int $max): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = (string) preg_replace('~<scr' . 'ipt\b[^>]*>.*?</scr' . 'ipt>~is', '', $value);
        $value = (string) preg_replace('~<sty' . 'le\b[^>]*>.*?</sty' . 'le>~is', '', $value);
        $value = (string) preg_replace('~<(?:iframe|object|embed)\b[^>]*>.*?</(?:iframe|object|embed)>~is', '', $value);
        $value = strip_tags($value, '<a><article><blockquote><br><button><code><div><em><figcaption><figure><form><h1><h2><h3><h4><h5><h6><hr><i><img><input><label><li><ol><option><p><pre><section><select><source><span><strong><textarea><u><ul><video>');

        $value = (string) preg_replace('/\s+on[a-z0-9_-]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/iu', '', $value);
        $value = (string) preg_replace('/\s+style\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/iu', '', $value);
        $value = (string) preg_replace('/\s+srcdoc\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/iu', '', $value);
        $value = (string) preg_replace_callback(
            '/\b(href|src|poster)\s*=\s*(["\'])(.*?)\2/iu',
            static function (array $matches): string {
                $attribute = (string) ($matches[1] ?? '');
                $quote = (string) ($matches[2] ?? '"');
                $raw = html_entity_decode((string) ($matches[3] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $normalized = trim($raw);

                if ($normalized === '' || preg_match('~^(?:javascript|vbscript|data):~iu', $normalized) === 1) {
                    return '';
                }

                return $attribute . '=' . $quote . htmlspecialchars($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8') . $quote;
            },
            $value
        );

        if (function_exists('mb_substr')) {
            return trim(mb_substr($value, 0, $max, 'UTF-8'));
        }

        return trim(substr($value, 0, $max));
    }

    private function cleanSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9-]+/', '-', $value) ?? 'studio-home';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'studio-home';
    }

    private function cleanId(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_-]+/', '-', trim($value)) ?? '';
        $value = trim($value, '-_');

        return $value !== '' ? $value : 'item-' . $this->shortId();
    }

    private function cleanColor(string $value, string $fallback): string
    {
        $value = trim($value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtoupper($value) : $fallback;
    }

    private function shortId(): string
    {
        return substr(bin2hex(random_bytes(4)), 0, 8);
    }
}
