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
        'modules' => [
            'routes' => ['admin/modules'],
            'steps' => [
                guided_tour_step('.modules-toolbar', __('modules_help_title', 'Modules'), __('modules_help_step_filters', 'Modules'), 'bottom'),
                guided_tour_step('.modules-filter-group [data-filter-status]', __('module_status', 'Modules'), __('modules_help_step_filters', 'Modules'), 'bottom'),
                guided_tour_step('#moduleSearchInput, .modules-filter-group-right', __('module_filter_search', 'Modules'), __('modules_help_step_filters', 'Modules'), 'top'),
                guided_tour_step('#moduleTypeFilter, #moduleLocationFilter', __('module_filter_type', 'Modules'), __('modules_help_step_filters', 'Modules'), 'top'),
                guided_tour_step('.module-installer-card', __('extensions_installer_title', 'Modules'), __('modules_help_step_install', 'Modules'), 'top'),
                guided_tour_step('.module-installer-form', __('extensions_installer_action', 'Modules'), __('extensions_installer_hint', 'Modules'), 'top'),
                guided_tour_step('.module-card-list', __('modules_list', 'Modules'), __('modules_help_intro', 'Modules'), 'top'),
                guided_tour_step('.module-card-header', __('module_name', 'Modules'), __('modules_help_step_dependencies', 'Modules'), 'top'),
                guided_tour_step('.module-detail-grid, .module-detail-block', __('module_dependencies', 'Modules'), __('modules_help_step_dependencies', 'Modules'), 'top'),
                guided_tour_step('.module-actions', __('modules_help_title', 'Modules'), __('modules_help_step_dependencies', 'Modules'), 'left'),
            ],
        ],
    ];
}, ['module' => 'Modules', 'priority' => 10]);
