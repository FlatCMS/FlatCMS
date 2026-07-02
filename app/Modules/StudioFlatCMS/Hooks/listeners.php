<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

if (!function_exists('hook_register')) {
    return;
}

hook_register('auth.permissions.extend', static function (): array {
    return [
        'permissions' => [
            'studio-flatcms.view',
            'studio-flatcms.edit',
        ],
        'role_permissions' => [
            'super_admin' => [
                'studio-flatcms.view',
                'studio-flatcms.edit',
            ],
            'admin' => [
                'studio-flatcms.view',
                'studio-flatcms.edit',
            ],
            'editor' => [
                'studio-flatcms.view',
                'studio-flatcms.edit',
            ],
        ],
    ];
}, ['module' => 'StudioFlatCMS', 'priority' => 20]);

hook_register('auth.menus.extend', static function (): array {
    $entry = [
        'url' => '/admin/studio-flatcms',
        'icon' => 'fas fa-object-group',
        'label' => 'studio_flatcms_menu',
        'module' => 'StudioFlatCMS',
        'permission' => 'studio-flatcms.view',
    ];

    return [
        'super_admin' => [$entry],
        'admin' => [$entry],
        'editor' => [$entry],
    ];
}, ['module' => 'StudioFlatCMS', 'priority' => 20]);

hook_register('admin.guided_tour.module_tours', static function (): array {
    return [
        'studio-flatcms' => [
            'routes' => ['admin/studio-flatcms'],
            'steps' => [
                guided_tour_step('[data-tour-target="studio-flatcms-topbar"]', __('studio_flatcms_title', 'StudioFlatCMS'), __('studio_flatcms_tour_topbar', 'StudioFlatCMS'), 'bottom'),
                guided_tour_step('[data-tour-target="studio-flatcms-rail"]', __('studio_flatcms_menu', 'StudioFlatCMS'), __('studio_flatcms_tour_rail', 'StudioFlatCMS'), 'right'),
                guided_tour_step('[data-tour-target="studio-flatcms-canvas"]', __('studio_flatcms_title', 'StudioFlatCMS'), __('studio_flatcms_tour_canvas', 'StudioFlatCMS'), 'top'),
                guided_tour_step('[data-tour-target="studio-flatcms-inspector"]', __('studio_flatcms_inspector_title', 'StudioFlatCMS'), __('studio_flatcms_tour_inspector', 'StudioFlatCMS'), 'left'),
            ],
        ],
    ];
}, ['module' => 'StudioFlatCMS', 'priority' => 20]);
