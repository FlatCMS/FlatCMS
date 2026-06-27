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
        'languages' => [
            'routes' => ['admin/languages'],
            'steps' => [
                guided_tour_step('.language-actions-grid', __('add_language', 'Languages'), __('languages_help_step_add', 'Languages'), 'bottom'),
                guided_tour_step('#code_quick, #name_quick, #direction_quick', __('languages_form_help_title', 'Languages'), __('languages_form_help_step_identity', 'Languages'), 'bottom'),
                guided_tour_step('#language_file, #import_code', __('import_language', 'Languages'), __('languages_help_step_add', 'Languages'), 'bottom'),
                guided_tour_step('.language-card-list', __('languages', 'Languages'), __('languages_help_step_default', 'Languages'), 'top'),
                guided_tour_step('.language-card', __('edit_translations', 'Languages'), __('languages_help_step_translate', 'Languages'), 'top'),
                guided_tour_step('.global-stats', __('global_completion', 'Languages'), __('translations_help_step_progress', 'Languages'), 'top'),
                guided_tour_step('[data-action="scan-fill"], #btnScanFill', __('scan_fill_missing', 'Languages'), __('translations_help_step_modules', 'Languages'), 'left'),
                guided_tour_step('.translations-controls', __('edit_translations', 'Languages'), __('translations_help_step_modules', 'Languages'), 'bottom'),
                guided_tour_step('#searchInput, #showOnlyMissing', __('search_translations', 'Languages'), __('translations_help_intro', 'Languages'), 'bottom'),
                guided_tour_step('.module-card, .module-card-header', __('module_translations', 'Languages'), __('translations_help_step_modules', 'Languages'), 'top'),
                guided_tour_step('#modulesList', __('translations', 'Languages'), __('translations_help_intro', 'Languages'), 'top'),
                guided_tour_step('.sticky-actions', __('save_translations', 'Languages'), __('translations_help_step_save', 'Languages'), 'top'),
                guided_tour_step('form[action*="/admin/languages"]', __('languages_form_help_title', 'Languages'), __('languages_form_help_intro', 'Languages'), 'top'),
                guided_tour_step('#code, #name, #direction', __('language_code', 'Languages'), __('languages_form_help_step_identity', 'Languages'), 'bottom'),
                guided_tour_step('form[action*="/admin/languages"] .form-actions', __('languages_form_help_title', 'Languages'), __('languages_form_help_step_activation', 'Languages'), 'top'),
            ],
        ],
    ];
}, ['module' => 'Languages', 'priority' => 10]);
