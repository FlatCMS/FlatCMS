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
        'hooks' => [
            'routes' => ['admin/hooks'],
            'steps' => [
                guided_tour_step('.hook-toolbar', __('hooks_title', 'HookManager'), __('hooks_help_intro', 'HookManager'), 'bottom'),
                guided_tour_step('.hook-summary, .hook-summary-count', __('hooks_total', 'HookManager'), __('hooks_help_step_groups', 'HookManager'), 'bottom'),
                guided_tour_step('.hook-controls, #hookSearchInput', __('hooks_title', 'HookManager'), __('hooks_help_step_search', 'HookManager'), 'top'),
                guided_tour_step('.hook-filter-row', __('hooks_help_action_filters', 'HookManager'), __('hooks_help_step_search', 'HookManager'), 'top'),
                guided_tour_step('#hookGroupFilter, #hookListenerFilter', __('hooks_filter_group', 'HookManager'), __('hooks_help_step_listeners', 'HookManager'), 'top'),
                guided_tour_step('.hook-accordion', __('hooks', 'HookManager'), __('hooks_help_step_groups', 'HookManager'), 'top'),
                guided_tour_step('.hook-accordion-header', __('hooks_help_action_groups', 'HookManager'), __('hooks_help_step_groups', 'HookManager'), 'top'),
                guided_tour_step('.hook-table', __('hooks_field_listeners', 'HookManager'), __('hooks_help_step_listeners', 'HookManager'), 'top'),
                guided_tour_step('.hook-row, .hook-listeners', __('hooks_title', 'HookManager'), __('hooks_help_step_listeners', 'HookManager'), 'top'),
            ],
        ],
    ];
}, ['module' => 'HookManager', 'priority' => 10]);
