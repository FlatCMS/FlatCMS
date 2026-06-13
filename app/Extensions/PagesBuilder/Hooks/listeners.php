<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Services\Licensing\ExtensionLicenseService;
use App\Extensions\PagesBuilder\Services\PageBuilderRenderService;
use App\Extensions\PagesBuilder\Services\PageBuilderSetupService;
use App\Extensions\PagesBuilder\Services\PageBuilderStateService;

hook_register('auth.permissions.extend', static function (): array {
    return [
        'permissions' => [
            'pagesbuilder.view',
            'pagesbuilder.edit',
        ],
        'role_permissions' => [
            'super_admin' => [
                'pagesbuilder.view',
                'pagesbuilder.edit',
            ],
            'admin' => [
                'pagesbuilder.view',
                'pagesbuilder.edit',
            ],
            'editor' => [
                'pagesbuilder.view',
                'pagesbuilder.edit',
            ],
        ],
    ];
}, ['module' => 'PagesBuilder', 'priority' => 20]);

hook_register('auth.menus.transform', static function ($payload): ?array {
    if (!is_array($payload)) {
        return null;
    }

    $manager = new \App\Core\ModuleManager();
    if (!$manager->isSidebarVisible('PagesBuilder')) {
        return null;
    }

    $role = \App\Modules\Auth\Services\RoleService::normalizeRole((string) ($payload['role'] ?? ''));
    $menus = $payload['menus'] ?? null;
    if (!is_array($menus) || $menus === []) {
        return null;
    }

    if (!\App\Modules\Auth\Services\RoleService::hasPermission($role, 'pages.view')
        && !\App\Modules\Auth\Services\RoleService::hasPermission($role, 'pagesbuilder.view')) {
        return null;
    }

    $setupService = new PageBuilderSetupService();
    $setupReady = $setupService->isReady();
    $builderAdminUrl = $setupReady ? '/admin/pages-builder' : '/admin/pages-builder/setup';

    $transformed = [];
    foreach ($menus as $item) {
        if (!is_array($item)) {
            continue;
        }

        $module = (string) ($item['module'] ?? '');
        $url = (string) ($item['url'] ?? '');
        if ($module === 'Pages' && $url === '/admin/pages') {
            $item['url'] = $builderAdminUrl;
            $item['icon'] = 'fas fa-file-alt';
            $item['label'] = 'pages_builder_pages_menu';
            $item['module'] = 'PagesBuilder';
            $item['permission'] = 'pages.view';
        }

        $transformed[] = $item;
    }

    return ['menus' => $transformed];
}, ['module' => 'PagesBuilder', 'priority' => 40]);

hook_register('pages.admin.route_override', static function ($payload): ?array {
    if (!is_array($payload)) {
        return null;
    }

    $setupService = new PageBuilderSetupService();
    if (!$setupService->isReady()) {
        return [
            'redirect_url' => '/admin/pages-builder/setup',
        ];
    }

    $action = trim((string) ($payload['action'] ?? ''));
    if ($action === '') {
        return null;
    }

    $locale = trim((string) ($payload['locale'] ?? ''));
    $id = trim((string) ($payload['id'] ?? ''));
    $query = [];
    if ($locale !== '') {
        $query['locale'] = $locale;
    }

    if ($action === 'index') {
        return [
            'redirect_url' => '/admin/pages-builder',
        ];
    }

    if ($action === 'create') {
        $url = '/admin/pages-builder/create';
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return [
            'redirect_url' => $url,
        ];
    }

    if ($action === 'edit' && $id !== '') {
        $url = '/admin/pages-builder/' . rawurlencode($id);
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return [
            'redirect_url' => $url,
        ];
    }

    return null;
}, ['module' => 'PagesBuilder', 'priority' => 40]);

hook_register('content.renderer.resolve', static function ($payload): ?array {
    if (!is_array($payload) || (string) ($payload['domain'] ?? '') !== 'pages') {
        return null;
    }

    $page = $payload['entity'] ?? null;
    if (!is_array($page)) {
        return null;
    }

    $stateService = new PageBuilderStateService();
    $state = $stateService->getActiveStateForPage($page);
    if (!is_array($state)) {
        return null;
    }

    $renderer = new PageBuilderRenderService();
    $resolvedPage = $renderer->buildRenderablePage($page, $state, is_array($payload) ? $payload : []);

    return [
        'handled' => true,
        'entity' => $resolvedPage,
        'render_mode' => 'builder',
        'provider' => 'PagesBuilder',
    ];
}, ['module' => 'PagesBuilder', 'priority' => 40]);

hook_register('pages.frontend.notices', static function ($payload): ?array {
    if (!is_array($payload)) {
        return null;
    }

    $page = $payload['page'] ?? null;
    if (!is_array($page) || trim((string) ($page['render_mode'] ?? '')) !== 'builder') {
        return null;
    }

    $forceWarning = filter_var(env('DEMO_FORCE_LICENSE_WARNING', 0), FILTER_VALIDATE_BOOL);
    $licenseService = new ExtensionLicenseService();
    $licenseProfile = $licenseService->describe('PagesBuilder');
    $licenseStatus = is_array($licenseProfile) ? (string) ($licenseProfile['status'] ?? '') : '';

    if ($forceWarning !== true && is_local_host()) {
        return null;
    }

    if ($forceWarning === true) {
        if ($licenseStatus === 'active') {
            return null;
        }
    } elseif ($licenseService->canAuthor('PagesBuilder')) {
        return null;
    }

    return [
        'type' => 'warning',
        'title' => __('builder_front_license_notice_title', 'PagesBuilder'),
        'message' => __('builder_front_license_notice_text', 'PagesBuilder'),
    ];
}, ['module' => 'PagesBuilder', 'priority' => 40]);
