<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

hook_register('admin.guided_tour.module_tours', static function (): array {
    return [
        'posts' => [
            'routes' => ['admin/posts'],
            'steps' => [
                guided_tour_step('[data-tour-target="posts-list-create"]', __('posts_tour_next_action_title', 'Posts'), __('posts_tour_list_empty_content', 'Posts'), 'left', [
                    'whenVisible' => '[data-tour-target="posts-list-table"][data-tour-state="empty"]',
                ]),
                guided_tour_step('[data-tour-target="posts-list-toolbar"]', __('posts_tour_list_toolbar_title', 'Posts'), __('posts_tour_list_toolbar_content', 'Posts'), 'bottom', [
                    'whenVisible' => '[data-tour-target="posts-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="posts-list-batch"]', __('posts_tour_list_batch_title', 'Posts'), __('posts_tour_list_batch_content', 'Posts'), 'bottom', [
                    'whenVisible' => '[data-tour-target="posts-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="posts-list-table"]', __('posts_tour_list_table_title', 'Posts'), __('posts_tour_list_table_content', 'Posts'), 'top', [
                    'whenVisible' => '[data-tour-target="posts-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="posts-list-create"]', __('posts_tour_next_action_title', 'Posts'), __('posts_tour_list_ready_next_content', 'Posts'), 'left', [
                    'whenVisible' => '[data-tour-target="posts-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="posts-translation-tabs"]', __('posts_tour_form_translations_title', 'Posts'), __('posts_tour_form_translations_content', 'Posts'), 'bottom'),
                guided_tour_step('[data-tour-target="posts-form-fields"]', __('posts_tour_form_fields_title', 'Posts'), __('posts_tour_form_fields_content', 'Posts'), 'top'),
                guided_tour_step('[data-tour-target="posts-form-status"]', __('posts_tour_form_status_title', 'Posts'), __('posts_tour_form_status_content', 'Posts'), 'left'),
                guided_tour_step('[data-tour-target="posts-form-media"]', __('posts_tour_form_media_title', 'Posts'), __('posts_tour_form_media_content', 'Posts'), 'left'),
                guided_tour_step('[data-tour-target="posts-form-taxonomies"]', __('posts_tour_form_taxonomies_title', 'Posts'), __('posts_tour_form_taxonomies_content', 'Posts'), 'left'),
                guided_tour_step('[data-tour-target="posts-form-seo"]', __('posts_tour_form_seo_title', 'Posts'), __('posts_tour_form_seo_content', 'Posts'), 'left'),
                guided_tour_step('[data-tour-target="posts-form-save"]', __('posts_tour_next_action_title', 'Posts'), __('posts_tour_form_create_next_content', 'Posts'), 'left', [
                    'whenVisible' => 'form[data-tour-state="create"]',
                ]),
                guided_tour_step('[data-tour-target="posts-form-save"]', __('posts_tour_next_action_title', 'Posts'), __('posts_tour_form_edit_next_content', 'Posts'), 'left', [
                    'whenVisible' => 'form[data-tour-state="edit"]',
                ]),
            ],
        ],
    ];
}, ['module' => 'Posts', 'priority' => 10]);
