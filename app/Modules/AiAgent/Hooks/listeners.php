<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

$aiAgentCanUse = static function (): bool {
    $user = auth();
    if (!is_array($user)) {
        return false;
    }

    $role = \App\Modules\Auth\Services\RoleService::normalizeRole((string) ($user['role'] ?? 'member'));
    return \App\Modules\Auth\Services\RoleService::hasPermission($role, 'ai.use');
};

hook_register('auth.permissions.extend', static function (): array {
    return [
        'permissions' => ['ai.use'],
        'role_permissions' => [
            'super_admin' => ['ai.use'],
            'admin' => ['ai.use'],
            'editor' => ['ai.use'],
        ],
    ];
}, ['module' => 'AiAgent', 'priority' => 20]);

hook_register('admin.assets.head', static function () use ($aiAgentCanUse): array {
    if (!$aiAgentCanUse()) {
        return [];
    }

    return [[
        'id' => 'ai-agent.admin.css',
        'type' => 'css',
        'src' => module_asset('AiAgent', 'css/ai-agent.css'),
        'priority' => 20,
    ]];
}, ['module' => 'AiAgent', 'priority' => 20]);

hook_register('admin.assets.footer', static function () use ($aiAgentCanUse): array {
    if (!$aiAgentCanUse()) {
        return [];
    }

    return [[
        'id' => 'ai-agent.admin.js',
        'type' => 'js',
        'src' => module_asset('AiAgent', 'js/ai-agent.js'),
        'priority' => 20,
    ]];
}, ['module' => 'AiAgent', 'priority' => 20]);

hook_register('admin.layout.modals', static function () use ($aiAgentCanUse): ?array {
    if (!$aiAgentCanUse()) {
        return null;
    }

    ob_start();
    include BASE_PATH . '/app/Modules/AiAgent/Views/admin/partials/drawer.php';
    $html = trim((string) ob_get_clean());

    if ($html === '') {
        return null;
    }

    return ['html' => $html];
}, ['module' => 'AiAgent', 'priority' => 20]);
