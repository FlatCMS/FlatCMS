<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

if (!function_exists('hook_register')) {
    return;
}

hook_register('auth.menus.extend', static function (): array {
    $entry = [
        'url' => '/admin/google-forms',
        'icon' => 'fas fa-brands fa-google',
        'label' => 'google_forms_menu',
        'module' => 'GoogleForms',
    ];

    return [
        'super_admin' => [$entry],
        'admin' => [$entry],
    ];
}, ['module' => 'GoogleForms', 'priority' => 50]);

hook_register('auth.permissions.extend', static function (): array {
    return [
        'permissions' => [
            'google_forms.view',
            'google_forms.manage',
            'google_forms.sync',
            'google_forms.settings',
        ],
        'role_permissions' => [
            'super_admin' => [
                'google_forms.view',
                'google_forms.manage',
                'google_forms.sync',
                'google_forms.settings',
            ],
            'admin' => [
                'google_forms.view',
                'google_forms.manage',
                'google_forms.sync',
            ],
        ],
    ];
}, ['module' => 'GoogleForms', 'priority' => 50]);
