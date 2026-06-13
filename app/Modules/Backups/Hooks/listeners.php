<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Core\I18n;
use App\Modules\Backups\Services\SiteBackupService;

hook_register('auth.permissions.extend', static function (): array {
    return [
        'permissions' => [
            'backups.view',
            'backups.manage',
        ],
        'role_permissions' => [
            'super_admin' => [
                'backups.view',
                'backups.manage',
            ],
            'admin' => [
                'backups.view',
                'backups.manage',
            ],
        ],
    ];
}, ['module' => 'Backups', 'priority' => 20]);

hook_register('auth.menus.extend', static function (): array {
    $entry = [
        'url' => '/admin/backups',
        'icon' => 'fas fa-database',
        'label' => 'backups_title',
        'module' => 'Backups',
        'permission' => 'backups.view',
    ];

    return [
        'super_admin' => [$entry],
        'admin' => [$entry],
    ];
}, ['module' => 'Backups', 'priority' => 20]);

hook_register('dashboard.admin.banners', static function (): string {
    if (!function_exists('can') || !can('backups.view')) {
        return '';
    }

    I18n::load('Backups');

    try {
        $service = new SiteBackupService();
        $backups = $service->listBackups();
    } catch (\Throwable) {
        $backups = [];
    }

    $dashboardBackupsCount = count($backups);
    $dashboardBackupsManage = function_exists('can') && can('backups.manage');
    $dashboardBackupsUrl = url('/admin/backups');

    ob_start();
    include BASE_PATH . '/app/Modules/Backups/Views/admin/dashboard-banner.php';
    return (string) ob_get_clean();
}, ['module' => 'Backups', 'priority' => 20]);
