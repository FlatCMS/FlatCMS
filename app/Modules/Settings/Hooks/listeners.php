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
