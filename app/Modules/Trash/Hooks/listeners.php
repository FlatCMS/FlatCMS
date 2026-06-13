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
        'url' => '/admin/trash',
        'icon' => 'fas fa-trash-can',
        'label' => 'trash_title',
        'module' => 'Trash',
        'permission' => 'pages.view',
    ];

    return [
        'super_admin' => [$entry],
        'admin' => [$entry],
        'editor' => [$entry],
        'demo' => [$entry],
    ];
}, ['module' => 'Trash', 'priority' => 20]);
