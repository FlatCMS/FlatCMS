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
        'pages' => [
            'routes' => ['admin/pages'],
            'steps' => [
                guided_tour_step('[data-tour-target="pages-list-create"]', __('pages_tour_next_action_title', 'Pages'), __('pages_tour_list_empty_content', 'Pages'), 'left', [
                    'whenVisible' => '[data-tour-target="pages-list-table"][data-tour-state="empty"]',
                ]),
                guided_tour_step('[data-tour-target="pages-list-toolbar"]', __('pages_tour_list_toolbar_title', 'Pages'), __('pages_tour_list_toolbar_content', 'Pages'), 'bottom', [
                    'whenVisible' => '[data-tour-target="pages-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="pages-list-batch"]', __('pages_tour_list_batch_title', 'Pages'), __('pages_tour_list_batch_content', 'Pages'), 'bottom', [
                    'whenVisible' => '[data-tour-target="pages-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="pages-list-table"]', __('pages_tour_list_table_title', 'Pages'), __('pages_tour_list_table_content', 'Pages'), 'top', [
                    'whenVisible' => '[data-tour-target="pages-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="pages-list-create"]', __('pages_tour_next_action_title', 'Pages'), __('pages_tour_list_ready_next_content', 'Pages'), 'left', [
                    'whenVisible' => '[data-tour-target="pages-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="pages-translation-tabs"]', __('pages_tour_form_translations_title', 'Pages'), __('pages_tour_form_translations_content', 'Pages'), 'bottom'),
                guided_tour_step('[data-tour-section="pages-form-fields"], [data-tour-target="pages-form-fields"]', __('pages_tour_form_fields_title', 'Pages'), __('pages_tour_form_fields_content', 'Pages'), 'top'),
                guided_tour_step('[data-tour-target="pages-form-status"]', __('pages_tour_form_status_title', 'Pages'), __('pages_tour_form_status_content', 'Pages'), 'left'),
                guided_tour_step('[data-tour-target="pages-form-seo"]', __('pages_tour_form_seo_title', 'Pages'), __('pages_tour_form_seo_content', 'Pages'), 'left'),
                guided_tour_step('[data-tour-target="pages-form-save"]', __('pages_tour_next_action_title', 'Pages'), __('pages_tour_form_create_next_content', 'Pages'), 'left', [
                    'whenVisible' => 'form[data-tour-state="create"]',
                ]),
                guided_tour_step('[data-tour-target="pages-form-save"]', __('pages_tour_next_action_title', 'Pages'), __('pages_tour_form_edit_next_content', 'Pages'), 'left', [
                    'whenVisible' => 'form[data-tour-state="edit"]',
                ]),
            ],
        ],
    ];
}, ['module' => 'Pages', 'priority' => 10]);
