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
        'menu' => [
            'routes' => ['admin/menus'],
            'steps' => [
                guided_tour_step('.menu-page-header .page-header-actions, #menuForm', __('menus', 'Menu'), __('menu_help_step_save', 'Menu'), 'bottom'),
                guided_tour_step('#menuActive', __('menu_active', 'Menu'), __('menu_help_step_structure', 'Menu'), 'top'),
                guided_tour_step('#menuAvailable, .menu-available-accordion', __('menu_available', 'Menu'), __('menu_help_step_library', 'Menu'), 'top'),
                guided_tour_step('.menu-item-config, .menu-custom-card', __('settings', 'Menu'), __('menu_structure_hint', 'Menu'), 'left'),
            ],
        ],
    ];
}, ['module' => 'Menu', 'priority' => 10]);
