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
        'users' => [
            'routes' => ['admin/users'],
            'steps' => [
                guided_tour_step('.users-filter-form', __('users_list', 'Users'), __('users_help_step_filters', 'Users'), 'left'),
                guided_tour_step('#roleFilter, #statusFilter', __('role', 'Users'), __('users_help_step_roles', 'Users'), 'bottom'),
                guided_tour_step('.users-filter-controls .btn', __('create_user', 'Users'), __('users_help_intro', 'Users'), 'bottom'),
                guided_tour_step('.user-stats-row', __('total_users', 'Users'), __('users_help_intro', 'Users'), 'top'),
                guided_tour_step('.user-stat-card', __('total_users', 'Users'), __('users_help_intro', 'Users'), 'top'),
                guided_tour_step('.table-wrapper', __('users_list', 'Users'), __('users_help_step_security', 'Users'), 'top'),
                guided_tour_step('.user-cell', __('users_list', 'Users'), __('users_help_step_security', 'Users'), 'top'),
                guided_tour_step('.user-actions, .table-actions', __('edit_user', 'Users'), __('users_help_step_security', 'Users'), 'left'),
                guided_tour_step('.pagination', __('users_list', 'Users'), __('users_help_intro', 'Users'), 'top'),
                guided_tour_step('form[action*="/admin/users"] .form-layout-columns', __('users_form_help_title', 'Users'), __('users_form_help_intro', 'Users'), 'top'),
                guided_tour_step('#name, #email, #role', __('users_form_help_title', 'Users'), __('users_form_help_step_identity', 'Users'), 'bottom'),
                guided_tour_step('.avatar-upload-container', __('avatar', 'Users'), __('users_form_help_step_profile', 'Users'), 'left'),
                guided_tour_step('form[action*="/admin/users"] .form-actions', __('users_form_help_title', 'Users'), __('users_form_help_step_access', 'Users'), 'top'),
            ],
        ],
    ];
}, ['module' => 'Users', 'priority' => 10]);
