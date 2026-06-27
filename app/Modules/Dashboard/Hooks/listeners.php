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
        'dashboard' => [
            'routes' => ['admin', 'admin/dashboard'],
            'steps' => [
                guided_tour_step('.welcome-banner', __('admin_tour_dashboard_welcome_title', 'Dashboard'), __('admin_tour_dashboard_welcome_content', 'Dashboard'), 'bottom'),
                guided_tour_step('.maintenance-banner', __('admin_tour_dashboard_maintenance_title', 'Dashboard'), __('admin_tour_dashboard_maintenance_content', 'Dashboard'), 'bottom'),
                guided_tour_step('[data-tour-target="dashboard-backups"]', __('admin_tour_dashboard_backups_title', 'Dashboard'), __('admin_tour_dashboard_backups_content', 'Dashboard'), 'bottom'),
                guided_tour_step('.stats-grid', __('admin_tour_dashboard_stats_title', 'Dashboard'), __('admin_tour_dashboard_stats_content', 'Dashboard'), 'top'),
                guided_tour_step('.chart-container', __('admin_tour_dashboard_chart_title', 'Dashboard'), __('admin_tour_dashboard_chart_content', 'Dashboard'), 'top'),
                guided_tour_step('.recent-list', __('admin_tour_dashboard_recent_title', 'Dashboard'), __('admin_tour_dashboard_recent_content', 'Dashboard'), 'top'),
                guided_tour_step('.quick-actions', __('admin_tour_dashboard_quick_actions_title', 'Dashboard'), __('admin_tour_dashboard_quick_actions_content', 'Dashboard'), 'left'),
                guided_tour_step('.system-info, .disk-usage', __('admin_tour_dashboard_system_title', 'Dashboard'), __('admin_tour_dashboard_system_content', 'Dashboard'), 'left'),
                guided_tour_step('.page-header', __('admin_tour_dashboard_final_title', 'Dashboard'), __('admin_tour_dashboard_final_content', 'Dashboard'), 'bottom'),
            ],
        ],
    ];
}, ['module' => 'Dashboard', 'priority' => 10]);
