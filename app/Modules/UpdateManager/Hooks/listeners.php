<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use App\Core\I18n;
use App\Modules\UpdateManager\Services\UpdateManagerService;

$updateCachedStatus = static function (): ?array {
    static $loaded = false;
    static $status = null;
    if ($loaded) {
        return $status;
    }
    $loaded = true;
    try {
        $status = (new UpdateManagerService())->cachedStatus();
    } catch (\Throwable) {
        $status = null;
    }
    return is_array($status) ? $status : null;
};

$updateFindPackage = static function (string $catalog, string $slug, string $themeType = '') use ($updateCachedStatus): ?array {
    $status = $updateCachedStatus();
    $packages = is_array($status['catalogs'][$catalog]['packages'] ?? null)
        ? $status['catalogs'][$catalog]['packages']
        : [];
    foreach ($packages as $package) {
        if (!is_array($package) || (string) ($package['slug'] ?? '') !== $slug) {
            continue;
        }
        if ($catalog === 'themes' && (string) ($package['theme_type'] ?? '') !== $themeType) {
            continue;
        }
        return $package;
    }
    return null;
};

hook_register('auth.permissions.extend', static function (): array {
    return [
        'permissions' => ['updates.view', 'updates.manage'],
        'role_permissions' => [
            'super_admin' => ['updates.view', 'updates.manage'],
            'admin' => ['updates.view', 'updates.manage'],
        ],
    ];
}, ['module' => 'UpdateManager', 'priority' => 20]);

hook_register('auth.menus.extend', static function (): array {
    $entry = [
        'url' => '/admin/updates',
        'icon' => 'fas fa-arrows-rotate',
        'label' => 'updates_title',
        'module' => 'UpdateManager',
        'permission' => 'updates.view',
    ];

    return [
        'super_admin' => [$entry],
        'admin' => [$entry],
    ];
}, ['module' => 'UpdateManager', 'priority' => 22]);

hook_register('auth.menus.transform', static function (array $payload = []) use ($updateCachedStatus): array {
    $menus = is_array($payload['menus'] ?? null) ? $payload['menus'] : [];
    $status = $updateCachedStatus();
    $count = is_array($status) ? max(0, (int) ($status['update_count'] ?? 0)) : 0;

    foreach ($menus as &$item) {
        if (!is_array($item) || (string) ($item['module'] ?? '') !== 'UpdateManager') {
            continue;
        }
        if ($count > 0) {
            $item['badge'] = $count;
        } else {
            unset($item['badge']);
        }
    }
    unset($item);

    return ['menus' => $menus];
}, ['module' => 'UpdateManager', 'priority' => 30]);

hook_register('modules.admin.card.badges', static function (array $payload = []) use ($updateFindPackage): string {
    if (!function_exists('can') || !can('updates.view')) {
        return '';
    }

    $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
    $name = trim((string) ($payload['name'] ?? ''));
    $slug = trim((string) ($meta['key'] ?? ''));
    if ($slug === '') {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    }
    $catalog = match (strtolower((string) ($payload['location'] ?? 'module'))) {
        'extension' => 'extensions',
        'plugin' => 'plugins',
        default => 'modules',
    };

    $package = $updateFindPackage($catalog, $slug);
    if (!is_array($package)) {
        return '';
    }

    $packageStatus = (string) ($package['status'] ?? '');
    if (!in_array($packageStatus, ['update_available', 'incompatible_update'], true)) {
        return '';
    }

    I18n::load('UpdateManager');
    $version = $packageStatus === 'update_available'
        ? (string) ($package['compatible_version'] ?? $package['latest_version'] ?? '')
        : (string) ($package['latest_version'] ?? '');
    $componentUpdateClass = $packageStatus === 'update_available' ? 'badge-warning' : 'badge-danger';
    $componentUpdateIcon = $packageStatus === 'update_available' ? 'fas fa-arrow-up' : 'fas fa-triangle-exclamation';
    $componentUpdateTitle = __($packageStatus === 'update_available' ? 'updates_status_update_available' : 'updates_status_incompatible_update', 'UpdateManager');
    $componentUpdateLabel = __($packageStatus === 'update_available' ? 'updates_card_update' : 'updates_card_incompatible', 'UpdateManager', ['version' => $version]);
    $componentUpdateUrl = url('/admin/updates');

    ob_start();
    include BASE_PATH . '/app/Modules/UpdateManager/Views/admin/component-update-badge.php';
    return (string) ob_get_clean();
}, ['module' => 'UpdateManager', 'priority' => 10]);

hook_register('themes.admin.card.badges', static function (array $payload = []) use ($updateFindPackage): string {
    if (!function_exists('can') || !can('updates.view')) {
        return '';
    }

    $theme = is_array($payload['theme'] ?? null) ? $payload['theme'] : [];
    $slug = trim((string) ($theme['slug'] ?? $payload['name'] ?? ''));
    $themeType = strtolower(trim((string) ($payload['theme_type'] ?? '')));
    $package = $updateFindPackage('themes', $slug, $themeType);
    if (!is_array($package)) {
        return '';
    }

    $packageStatus = (string) ($package['status'] ?? '');
    if (!in_array($packageStatus, ['update_available', 'incompatible_update'], true)) {
        return '';
    }

    I18n::load('UpdateManager');
    $version = $packageStatus === 'update_available'
        ? (string) ($package['compatible_version'] ?? $package['latest_version'] ?? '')
        : (string) ($package['latest_version'] ?? '');
    $componentUpdateClass = $packageStatus === 'update_available' ? 'badge-warning' : 'badge-danger';
    $componentUpdateIcon = $packageStatus === 'update_available' ? 'fas fa-arrow-up' : 'fas fa-triangle-exclamation';
    $componentUpdateTitle = __($packageStatus === 'update_available' ? 'updates_status_update_available' : 'updates_status_incompatible_update', 'UpdateManager');
    $componentUpdateLabel = __($packageStatus === 'update_available' ? 'updates_card_update' : 'updates_card_incompatible', 'UpdateManager', ['version' => $version]);
    $componentUpdateUrl = url('/admin/updates');

    ob_start();
    include BASE_PATH . '/app/Modules/UpdateManager/Views/admin/component-update-badge.php';
    return (string) ob_get_clean();
}, ['module' => 'UpdateManager', 'priority' => 10]);

hook_register('dashboard.admin.meta_badges', static function (): string {
    if (!function_exists('can') || !can('updates.view')) {
        return '';
    }

    I18n::load('UpdateManager');
    try {
        $status = (new UpdateManagerService())->cachedStatus();
    } catch (\Throwable) {
        $status = null;
    }

    $coreStatus = 'unverified';
    $corePackages = is_array($status['catalogs']['core']['packages'] ?? null)
        ? $status['catalogs']['core']['packages']
        : [];
    foreach ($corePackages as $package) {
        if (is_array($package) && (string) ($package['slug'] ?? '') === 'flatcms') {
            $coreStatus = (string) ($package['status'] ?? 'unverified');
            break;
        }
    }

    [$welcomeStatusClass, $welcomeStatusIcon, $welcomeStatusKey] = match ($coreStatus) {
        'up_to_date' => ['is-success', 'fas fa-circle-check', 'updates_status_up_to_date'],
        'update_available' => ['is-warning', 'fas fa-arrow-up', 'updates_status_update_available'],
        'incompatible_update' => ['is-danger', 'fas fa-triangle-exclamation', 'updates_status_incompatible_update'],
        'repository_unavailable' => ['is-muted', 'fas fa-cloud-arrow-down', 'updates_status_repository_unavailable'],
        default => ['is-muted', 'fas fa-circle-question', 'updates_status_unverified'],
    };

    $welcomeStatusLabel = __($welcomeStatusKey, 'UpdateManager');
    $welcomeStatusUrl = url('/admin/updates');

    ob_start();
    include BASE_PATH . '/app/Modules/UpdateManager/Views/admin/dashboard-status-badge.php';
    return (string) ob_get_clean();
}, ['module' => 'UpdateManager', 'priority' => 9]);

hook_register('dashboard.admin.banners', static function (): string {
    if (!function_exists('can') || !can('updates.view')) {
        return '';
    }

    I18n::load('UpdateManager');
    try {
        $status = (new UpdateManagerService())->cachedStatus();
    } catch (\Throwable) {
        return '';
    }

    if (!is_array($status) || (int) ($status['update_count'] ?? 0) < 1) {
        return '';
    }

    $dashboardUpdateCount = (int) ($status['update_count'] ?? 0);
    $dashboardCheckedAt = (string) ($status['checked_at'] ?? '');
    $dashboardUpdatesUrl = url('/admin/updates');

    ob_start();
    include BASE_PATH . '/app/Modules/UpdateManager/Views/admin/dashboard-banner.php';
    return (string) ob_get_clean();
}, ['module' => 'UpdateManager', 'priority' => 10]);

hook_register('tasks.run', static function (array $payload = []): array {
    try {
        $status = (new UpdateManagerService())->status();
        return [
            'checked_at' => (string) ($status['checked_at'] ?? ''),
            'updates' => (int) ($status['update_count'] ?? 0),
            'incompatible' => (int) ($status['incompatible_count'] ?? 0),
            'errors' => is_array($status['errors'] ?? null) ? count($status['errors']) : 0,
            'cached' => !empty($status['from_cache']),
        ];
    } catch (\Throwable $exception) {
        return [
            'updates' => 0,
            'incompatible' => 0,
            'errors' => 1,
            'error' => $exception->getMessage(),
        ];
    }
}, ['module' => 'UpdateManager', 'priority' => 30]);
