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
        'categories' => [
            'routes' => ['admin/categories'],
            'steps' => [
                guided_tour_step('[data-tour-target="categories-list-create"]', __('categories_tour_next_action_title', 'Categories'), __('categories_tour_list_empty_content', 'Categories'), 'left', [
                    'whenVisible' => '[data-tour-target="categories-list-table"][data-tour-state="empty"]',
                ]),
                guided_tour_step('[data-tour-target="categories-list-toolbar"]', __('categories_tour_list_toolbar_title', 'Categories'), __('categories_tour_list_toolbar_content', 'Categories'), 'bottom', [
                    'whenVisible' => '[data-tour-target="categories-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="categories-list-batch"]', __('categories_tour_list_batch_title', 'Categories'), __('categories_tour_list_batch_content', 'Categories'), 'bottom', [
                    'whenVisible' => '[data-tour-target="categories-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="categories-list-table"]', __('categories_tour_list_table_title', 'Categories'), __('categories_tour_list_table_content', 'Categories'), 'top', [
                    'whenVisible' => '[data-tour-target="categories-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="categories-list-create"]', __('categories_tour_next_action_title', 'Categories'), __('categories_tour_list_ready_next_content', 'Categories'), 'left', [
                    'whenVisible' => '[data-tour-target="categories-list-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="categories-translation-tabs"]', __('categories_tour_form_translations_title', 'Categories'), __('categories_tour_form_translations_content', 'Categories'), 'bottom'),
                guided_tour_step('[data-tour-section="categories-form-fields"]', __('categories_tour_form_fields_title', 'Categories'), __('categories_tour_form_fields_content', 'Categories'), 'top'),
                guided_tour_step('[data-tour-target="categories-form-settings"]', __('categories_tour_form_settings_title', 'Categories'), __('categories_tour_form_settings_content', 'Categories'), 'left'),
                guided_tour_step('[data-tour-target="categories-form-save"]', __('categories_tour_next_action_title', 'Categories'), __('categories_tour_form_create_next_content', 'Categories'), 'left', [
                    'whenVisible' => 'form[data-tour-state="create"]',
                ]),
                guided_tour_step('[data-tour-target="categories-form-save"]', __('categories_tour_next_action_title', 'Categories'), __('categories_tour_form_edit_next_content', 'Categories'), 'left', [
                    'whenVisible' => 'form[data-tour-state="edit"]',
                ]),
            ],
        ],
    ];
}, ['module' => 'Categories', 'priority' => 10]);
