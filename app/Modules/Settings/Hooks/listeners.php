<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

hook_register('auth.menus.extend', static function (): array {
    $entry = [
        'url' => '/admin/settings/advanced',
        'icon' => 'fas fa-sliders',
        'label' => 'settings_advanced',
        'module' => 'Settings',
        'permission' => 'settings.view',
    ];

    return [
        'super_admin' => [$entry],
        'admin' => [$entry],
    ];
}, ['module' => 'Settings', 'priority' => 25]);

hook_register('admin.guided_tour.module_tours', static function (): array {
    return [
        'settings' => [
            'routes' => ['admin/settings'],
            'steps' => [
                guided_tour_step('[data-settings-tabs]', __('settings_help_title', 'Settings'), __('settings_help_intro', 'Settings'), 'bottom'),
                guided_tour_step('[data-tour-target="settings-branding"]', __('general', 'Settings'), __('settings_help_step_general', 'Settings'), 'top'),
                guided_tour_step('[data-tour-target="settings-branding-translations"]', __('site_branding_translations', 'Settings'), __('site_branding_translations_hint', 'Settings'), 'left'),
                guided_tour_step('[data-tour-target="settings-guided-tour"]', __('guided_tour_title', 'Settings'), __('guided_tour_start_hint', 'Settings'), 'top'),
                guided_tour_step('.settings-inline-actions, .settings-guided-tour-actions', __('guided_tour_title', 'Settings'), __('guided_tour_start_hint', 'Settings'), 'top'),
                guided_tour_step('.settings-system-overview, .settings-path-list', __('system_information', 'Settings'), __('settings_help_step_system', 'Settings'), 'top'),
                guided_tour_step('.form-actions.form-actions-divider', __('settings_help_title', 'Settings'), __('settings_help_intro', 'Settings'), 'top'),
            ],
        ],
    ];
}, ['module' => 'Settings', 'priority' => 25]);
