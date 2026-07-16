<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

hook_register('auth.permissions.extend', static function (): array {
    return [
        'permissions' => [
            'sitemap.view',
            'sitemap.manage',
        ],
        'role_permissions' => [
            'super_admin' => [
                'sitemap.view',
                'sitemap.manage',
            ],
            'admin' => [
                'sitemap.view',
                'sitemap.manage',
            ],
        ],
    ];
}, ['module' => 'SiteMap', 'priority' => 20]);

hook_register('auth.menus.extend', static function (): array {
    $entry = [
        'url' => '/admin/sitemap',
        'icon' => 'fas fa-sitemap',
        'label' => 'sitemap_title',
        'module' => 'SiteMap',
        'permission' => 'sitemap.view',
    ];

    return [
        'super_admin' => [$entry],
        'admin' => [$entry],
    ];
}, ['module' => 'SiteMap', 'priority' => 20]);

hook_register('frontend.assets.head', static function (array $payload = []): array {
    if (empty($payload['sitemap_page'])) {
        return [];
    }

    return [[
        'id' => 'sitemap.front.css',
        'type' => 'css',
        'src' => module_asset('SiteMap', 'css/sitemap-front.css'),
        'priority' => 10,
    ]];
}, ['module' => 'SiteMap', 'priority' => 10]);
