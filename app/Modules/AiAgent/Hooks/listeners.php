<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

hook_register('admin.assets.head', static function (): array {
    return [[
        'id' => 'ai-agent.admin.css',
        'type' => 'css',
        'src' => module_asset('AiAgent', 'css/ai-agent.css'),
        'priority' => 20,
    ]];
}, ['module' => 'AiAgent', 'priority' => 20]);

hook_register('admin.assets.footer', static function (): array {
    return [[
        'id' => 'ai-agent.admin.js',
        'type' => 'js',
        'src' => module_asset('AiAgent', 'js/ai-agent.js'),
        'priority' => 20,
    ]];
}, ['module' => 'AiAgent', 'priority' => 20]);

hook_register('admin.layout.modals', static function (): ?array {
    ob_start();
    include BASE_PATH . '/app/Modules/AiAgent/Views/admin/partials/drawer.php';
    $html = trim((string) ob_get_clean());

    if ($html === '') {
        return null;
    }

    return ['html' => $html];
}, ['module' => 'AiAgent', 'priority' => 20]);
