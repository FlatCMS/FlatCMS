<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Extensions\Studio\Services\StudioPreviewService;

if (!function_exists('hook_register')) {
    return;
}

hook_register('auth.permissions.extend', static function (): array {
    return [
        'permissions' => [
            'studio.view',
            'studio.edit',
        ],
        'role_permissions' => [
            'super_admin' => [
                'studio.view',
                'studio.edit',
            ],
            'admin' => [
                'studio.view',
                'studio.edit',
            ],
            'editor' => [
                'studio.view',
                'studio.edit',
            ],
        ],
    ];
}, ['module' => 'Studio', 'priority' => 20]);

hook_register('content.renderer.resolve', static function ($payload): ?array {
    if (!is_array($payload) || (string) ($payload['domain'] ?? '') !== 'pages') {
        return null;
    }

    $page = $payload['entity'] ?? null;
    if (!is_array($page)) {
        return null;
    }

    $preview = new StudioPreviewService();
    $resolvedPage = $preview->buildRenderablePage($page, $payload);
    if (!is_array($resolvedPage)) {
        return null;
    }

    return [
        'handled' => true,
        'entity' => $resolvedPage,
        'render_mode' => 'studio',
        'provider' => 'Studio',
    ];
}, ['module' => 'Studio', 'priority' => 5]);

hook_register('pages.frontend.notices', static function ($payload): ?array {
    if (!is_array($payload)) {
        return null;
    }

    $page = $payload['page'] ?? null;
    if (!is_array($page)) {
        return null;
    }

    $preview = new StudioPreviewService();
    return $preview->buildPreviewNotice($page);
}, ['module' => 'Studio', 'priority' => 5]);
