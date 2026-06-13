<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Hook;

class RoleService
{
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_DEMO = 'demo';
    public const ROLE_MEMBER = 'member';

    private const LEGACY_ROLE_ALIASES = [
        'author' => self::ROLE_EDITOR,
    ];

    public const ROLES = [
        self::ROLE_SUPER_ADMIN => [
            'label' => 'Super Admin',
            'icon' => 'fas fa-user-shield',
            'color' => '#8b5cf6',
            'badge_class' => 'badge-primary',
            'description' => 'Full platform access',
            'registerable' => false,
        ],
        self::ROLE_ADMIN => [
            'label' => 'Admin',
            'icon' => 'fas fa-shield-alt',
            'color' => '#e74c3c',
            'badge_class' => 'badge-danger',
            'description' => 'Site administration',
            'registerable' => false,
        ],
        self::ROLE_EDITOR => [
            'label' => 'Editor',
            'icon' => 'fas fa-edit',
            'color' => '#3498db',
            'badge_class' => 'badge-info',
            'description' => 'Content management',
            'registerable' => false,
        ],
        self::ROLE_DEMO => [
            'label' => 'Demo',
            'icon' => 'fas fa-flask',
            'color' => '#16a085',
            'badge_class' => 'badge-info',
            'description' => 'Restricted back-office demo',
            'registerable' => false,
        ],
        self::ROLE_MEMBER => [
            'label' => 'Member',
            'icon' => 'fas fa-user',
            'color' => '#95a5a6',
            'badge_class' => 'badge-secondary',
            'description' => 'Private member area',
            'registerable' => false,
        ],
    ];

    public const PERMISSIONS = [
        // Platform
        'admin.access',

        // Dashboard
        'dashboard.view',

        // Pages
        'pages.view', 'pages.create', 'pages.edit', 'pages.delete', 'pages.publish',
        'pages.delete_own',

        // Posts
        'posts.view', 'posts.create', 'posts.edit', 'posts.delete', 'posts.publish',
        'posts.edit_own', 'posts.delete_own',

        // Categories
        'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
        'categories.delete_own',

        // Media
        'media.view', 'media.upload', 'media.delete',

        // Menus
        'menus.view', 'menus.create', 'menus.edit', 'menus.delete',
        'footers.view', 'footers.edit',

        // Comments
        'comments.view', 'comments.moderate', 'comments.delete',
        'contact.view', 'contact.manage', 'contact.delete', 'contact.delete_own',

        // Users
        'users.view', 'users.create', 'users.edit', 'users.delete',

        // Settings
        'settings.view', 'settings.edit',

        // Languages
        'languages.view', 'languages.create', 'languages.edit', 'languages.delete',
        'languages.translations',

        // Themes
        'themes.view', 'themes.edit',

        // Modules
        'modules.view', 'modules.manage',
        // Hooks
        'hooks.view', 'hooks.manage',

        // Profile
        'profile.view', 'profile.edit',

        // Licenses
        'licenses.manage', 'licenses.reveal',
    ];

    public const ROLE_PERMISSIONS = [
        self::ROLE_SUPER_ADMIN => '*', // all permissions

        self::ROLE_ADMIN => [
            'admin.access',
            'dashboard.view',
            'pages.view', 'pages.create', 'pages.edit', 'pages.delete', 'pages.publish',
            'posts.view', 'posts.create', 'posts.edit', 'posts.delete', 'posts.publish',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'media.view', 'media.upload', 'media.delete',
            'menus.view', 'menus.create', 'menus.edit', 'menus.delete',
            'footers.view', 'footers.edit',
            'comments.view', 'comments.moderate', 'comments.delete',
            'contact.view', 'contact.manage', 'contact.delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'settings.view', 'settings.edit',
            'languages.view', 'languages.create', 'languages.edit', 'languages.delete',
            'languages.translations',
            'themes.view', 'themes.edit',
            'modules.view', 'modules.manage',
            'hooks.view', 'hooks.manage',
            'profile.view', 'profile.edit',
            'licenses.manage', 'licenses.reveal',
        ],

        self::ROLE_EDITOR => [
            'admin.access',
            'dashboard.view',
            'pages.view', 'pages.create', 'pages.edit', 'pages.delete', 'pages.publish',
            'posts.view', 'posts.create', 'posts.edit', 'posts.delete', 'posts.publish',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'media.view', 'media.upload', 'media.delete',
            'menus.view', 'menus.create', 'menus.edit', 'menus.delete',
            'footers.view', 'footers.edit',
            'comments.view', 'comments.moderate', 'comments.delete',
            'contact.view', 'contact.manage', 'contact.delete',
            'users.view',
            'languages.view', 'languages.translations',
            'profile.view', 'profile.edit',
        ],

        self::ROLE_DEMO => [
            'admin.access',
            'dashboard.view',
            'pages.view', 'pages.create', 'pages.edit', 'pages.publish', 'pages.delete_own',
            'posts.view', 'posts.create', 'posts.edit', 'posts.publish', 'posts.delete_own',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete_own',
            'media.view', 'media.upload',
            'menus.view', 'menus.create', 'menus.edit',
            'footers.view', 'footers.edit',
            'contact.view', 'contact.manage', 'contact.delete_own',
            'languages.view', 'languages.translations',
            'themes.view', 'themes.edit',
            'profile.view', 'profile.edit',
        ],

        self::ROLE_MEMBER => [
            'profile.view', 'profile.edit',
        ],
    ];

    public const ROLE_HIERARCHY = [
        self::ROLE_SUPER_ADMIN => 120,
        self::ROLE_ADMIN => 100,
        self::ROLE_EDITOR => 80,
        self::ROLE_DEMO => 60,
        self::ROLE_MEMBER => 20,
    ];

    public const ROLE_MENUS = [
        self::ROLE_SUPER_ADMIN => [
            ['url' => '/admin', 'icon' => 'fas fa-home', 'label' => 'dashboard', 'module' => 'Core'],
            ['section' => 'content', 'module' => 'Core'],
            ['url' => '/admin/pages', 'icon' => 'fas fa-file-alt', 'label' => 'pages', 'module' => 'Pages', 'permission' => 'pages.view'],
            ['url' => '/admin/posts', 'icon' => 'fas fa-newspaper', 'label' => 'posts', 'module' => 'Posts', 'permission' => 'posts.view'],
            ['url' => '/admin/categories', 'icon' => 'fas fa-folder-open', 'label' => 'categories', 'module' => 'Categories', 'permission' => 'categories.view'],
            ['url' => '/admin/media', 'icon' => 'fas fa-image', 'label' => 'media', 'module' => 'Media', 'permission' => 'media.view'],
            ['url' => '/admin/menus', 'icon' => 'fas fa-bars', 'label' => 'menus', 'module' => 'Menu', 'permission' => 'menus.view'],
            ['url' => '/admin/footer', 'icon' => 'fas fa-grip-lines', 'label' => 'footer_title', 'module' => 'Footer', 'permission' => 'footers.view'],
            ['url' => '/admin/comments', 'icon' => 'fas fa-comments', 'label' => 'comments', 'module' => 'Comments', 'permission' => 'comments.view'],
            ['url' => '/admin/contact', 'icon' => 'fas fa-envelope-open-text', 'label' => 'contact_forms_list_title', 'module' => 'Contact', 'permission' => 'contact.view'],
            ['section' => 'system', 'module' => 'Core'],
            ['url' => '/admin/users', 'icon' => 'fas fa-users', 'label' => 'users', 'module' => 'Users', 'permission' => 'users.view'],
            ['url' => '/admin/settings', 'icon' => 'fas fa-cog', 'label' => 'settings', 'module' => 'Settings', 'permission' => 'settings.view'],
            ['url' => '/admin/languages', 'icon' => 'fas fa-language', 'label' => 'languages', 'module' => 'Languages', 'permission' => 'languages.view'],
            ['url' => '/admin/modules', 'icon' => 'fas fa-boxes', 'label' => 'module_name_modules', 'module' => 'Modules', 'permission' => 'modules.view'],
            ['url' => '/admin/hooks', 'icon' => 'fas fa-plug', 'label' => 'hooks', 'module' => 'HookManager', 'permission' => 'hooks.view'],
            ['url' => '/admin/themes', 'icon' => 'fas fa-palette', 'label' => 'themes', 'module' => 'Themes', 'permission' => 'themes.view'],
        ],

        self::ROLE_ADMIN => [
            ['url' => '/admin', 'icon' => 'fas fa-home', 'label' => 'dashboard', 'module' => 'Core'],
            ['section' => 'content', 'module' => 'Core'],
            ['url' => '/admin/pages', 'icon' => 'fas fa-file-alt', 'label' => 'pages', 'module' => 'Pages', 'permission' => 'pages.view'],
            ['url' => '/admin/posts', 'icon' => 'fas fa-newspaper', 'label' => 'posts', 'module' => 'Posts', 'permission' => 'posts.view'],
            ['url' => '/admin/categories', 'icon' => 'fas fa-folder-open', 'label' => 'categories', 'module' => 'Categories', 'permission' => 'categories.view'],
            ['url' => '/admin/media', 'icon' => 'fas fa-image', 'label' => 'media', 'module' => 'Media', 'permission' => 'media.view'],
            ['url' => '/admin/menus', 'icon' => 'fas fa-bars', 'label' => 'menus', 'module' => 'Menu', 'permission' => 'menus.view'],
            ['url' => '/admin/footer', 'icon' => 'fas fa-grip-lines', 'label' => 'footer_title', 'module' => 'Footer', 'permission' => 'footers.view'],
            ['url' => '/admin/comments', 'icon' => 'fas fa-comments', 'label' => 'comments', 'module' => 'Comments', 'permission' => 'comments.view'],
            ['url' => '/admin/contact', 'icon' => 'fas fa-envelope-open-text', 'label' => 'contact_forms_list_title', 'module' => 'Contact', 'permission' => 'contact.view'],
            ['section' => 'system', 'module' => 'Core'],
            ['url' => '/admin/users', 'icon' => 'fas fa-users', 'label' => 'users', 'module' => 'Users', 'permission' => 'users.view'],
            ['url' => '/admin/settings', 'icon' => 'fas fa-cog', 'label' => 'settings', 'module' => 'Settings', 'permission' => 'settings.view'],
            ['url' => '/admin/languages', 'icon' => 'fas fa-language', 'label' => 'languages', 'module' => 'Languages', 'permission' => 'languages.view'],
            ['url' => '/admin/modules', 'icon' => 'fas fa-boxes', 'label' => 'module_name_modules', 'module' => 'Modules', 'permission' => 'modules.view'],
            ['url' => '/admin/hooks', 'icon' => 'fas fa-plug', 'label' => 'hooks', 'module' => 'HookManager', 'permission' => 'hooks.view'],
            ['url' => '/admin/themes', 'icon' => 'fas fa-palette', 'label' => 'themes', 'module' => 'Themes', 'permission' => 'themes.view'],
        ],

        self::ROLE_EDITOR => [
            ['url' => '/admin', 'icon' => 'fas fa-home', 'label' => 'dashboard', 'module' => 'Core'],
            ['section' => 'content', 'module' => 'Core'],
            ['url' => '/admin/pages', 'icon' => 'fas fa-file-alt', 'label' => 'pages', 'module' => 'Pages', 'permission' => 'pages.view'],
            ['url' => '/admin/posts', 'icon' => 'fas fa-newspaper', 'label' => 'posts', 'module' => 'Posts', 'permission' => 'posts.view'],
            ['url' => '/admin/categories', 'icon' => 'fas fa-folder-open', 'label' => 'categories', 'module' => 'Categories', 'permission' => 'categories.view'],
            ['url' => '/admin/media', 'icon' => 'fas fa-image', 'label' => 'media', 'module' => 'Media', 'permission' => 'media.view'],
            ['url' => '/admin/menus', 'icon' => 'fas fa-bars', 'label' => 'menus', 'module' => 'Menu', 'permission' => 'menus.view'],
            ['url' => '/admin/footer', 'icon' => 'fas fa-grip-lines', 'label' => 'footer_title', 'module' => 'Footer', 'permission' => 'footers.view'],
            ['url' => '/admin/comments', 'icon' => 'fas fa-comments', 'label' => 'comments', 'module' => 'Comments', 'permission' => 'comments.view'],
            ['url' => '/admin/contact', 'icon' => 'fas fa-envelope-open-text', 'label' => 'contact_forms_list_title', 'module' => 'Contact', 'permission' => 'contact.view'],
        ],

        self::ROLE_DEMO => [
            ['url' => '/admin', 'icon' => 'fas fa-home', 'label' => 'dashboard', 'module' => 'Core'],
            ['section' => 'content', 'module' => 'Core'],
            ['url' => '/admin/pages', 'icon' => 'fas fa-file-alt', 'label' => 'pages', 'module' => 'Pages', 'permission' => 'pages.view'],
            ['url' => '/admin/posts', 'icon' => 'fas fa-newspaper', 'label' => 'posts', 'module' => 'Posts', 'permission' => 'posts.view'],
            ['url' => '/admin/categories', 'icon' => 'fas fa-folder-open', 'label' => 'categories', 'module' => 'Categories', 'permission' => 'categories.view'],
            ['url' => '/admin/media', 'icon' => 'fas fa-image', 'label' => 'media', 'module' => 'Media', 'permission' => 'media.view'],
            ['url' => '/admin/menus', 'icon' => 'fas fa-bars', 'label' => 'menus', 'module' => 'Menu', 'permission' => 'menus.view'],
            ['url' => '/admin/footer', 'icon' => 'fas fa-grip-lines', 'label' => 'footer_title', 'module' => 'Footer', 'permission' => 'footers.view'],
            ['url' => '/admin/contact', 'icon' => 'fas fa-envelope-open-text', 'label' => 'contact_forms_list_title', 'module' => 'Contact', 'permission' => 'contact.view'],
            ['section' => 'system', 'module' => 'Core'],
            ['url' => '/admin/languages', 'icon' => 'fas fa-language', 'label' => 'languages', 'module' => 'Languages', 'permission' => 'languages.view'],
            ['url' => '/admin/themes', 'icon' => 'fas fa-palette', 'label' => 'themes', 'module' => 'Themes', 'permission' => 'themes.view'],
        ],

        self::ROLE_MEMBER => [
            ['section' => 'system', 'module' => 'Core'],
            ['url' => '/admin/profile', 'icon' => 'fas fa-user', 'label' => 'my_profile', 'module' => 'Users', 'permission' => 'profile.view'],
        ],
    ];

    public static function normalizeRole(string $role): string
    {
        $normalized = strtolower(trim($role));
        if ($normalized === '') {
            return self::ROLE_MEMBER;
        }

        return self::LEGACY_ROLE_ALIASES[$normalized] ?? $normalized;
    }

    public static function hasPermission(string $role, string $permission): bool
    {
        $permissions = self::resolveRolePermissions($role);

        if ($permissions === '*') {
            return true;
        }

        return in_array($permission, $permissions, true);
    }

    public static function hasAnyPermission(string $role, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::hasPermission($role, $permission)) {
                return true;
            }
        }
        return false;
    }

    public static function hasAllPermissions(string $role, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!self::hasPermission($role, $permission)) {
                return false;
            }
        }
        return true;
    }

    public static function getRolePermissions(string $role): array
    {
        $permissions = self::resolveRolePermissions($role);

        if ($permissions === '*') {
            return self::resolveAllPermissionsCatalog();
        }

        return $permissions;
    }

    public static function getRoleMenus(string $role): array
    {
        $role = self::normalizeRole($role);
        $menus = self::ROLE_MENUS[$role] ?? [];
        $menus = array_merge($menus, self::resolveRoleMenuExtensions($role));
        if (empty($menus)) {
            return [];
        }

        $manager = new \App\Core\ModuleManager();
        $enabled = array_flip($manager->enabledNames());

        $filtered = [];
        $pendingSection = null;

        foreach ($menus as $item) {
            if (isset($item['section'])) {
                $pendingSection = $item;
                continue;
            }

            $module = $item['module'] ?? null;
            if ($module && !isset($enabled[$module])) {
                continue;
            }

            if ($module && !$manager->isSidebarVisible((string) $module)) {
                continue;
            }

            if ($pendingSection) {
                $filtered[] = $pendingSection;
                $pendingSection = null;
            }

            $filtered[] = $item;
        }

        return self::applyRoleMenuTransforms($role, $filtered);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private static function permissionExtensionPayloads(): array
    {
        $results = Hook::run('auth.permissions.extend', []);
        return array_values(array_filter($results, static fn ($entry): bool => is_array($entry)));
    }

    /**
     * @return array<string>|'*'
     */
    private static function resolveRolePermissions(string $role)
    {
        $role = self::normalizeRole($role);
        $base = self::ROLE_PERMISSIONS[$role] ?? [];
        if ($base === '*') {
            return '*';
        }

        $merged = is_array($base) ? $base : [];
        foreach (self::permissionExtensionPayloads() as $payload) {
            $rolePermissions = $payload['role_permissions'] ?? [];
            if (!is_array($rolePermissions)) {
                continue;
            }

            $extra = $rolePermissions[$role] ?? $rolePermissions['*'] ?? [];
            if (!is_array($extra)) {
                continue;
            }

            foreach ($extra as $permission) {
                if (!is_string($permission) || $permission === '') {
                    continue;
                }
                $merged[] = $permission;
            }
        }

        return array_values(array_unique($merged));
    }

    /**
     * @return array<int,string>
     */
    private static function resolveAllPermissionsCatalog(): array
    {
        $permissions = self::PERMISSIONS;
        foreach (self::permissionExtensionPayloads() as $payload) {
            $extra = $payload['permissions'] ?? [];
            if (!is_array($extra)) {
                continue;
            }

            foreach ($extra as $permission) {
                if (!is_string($permission) || $permission === '') {
                    continue;
                }
                $permissions[] = $permission;
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function resolveRoleMenuExtensions(string $role): array
    {
        $role = self::normalizeRole($role);
        $results = Hook::run('auth.menus.extend', ['role' => $role]);
        if (!is_array($results) || $results === []) {
            return [];
        }

        $menus = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $entries = $result[$role] ?? $result['*'] ?? [];
            if (!is_array($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $menus[] = $entry;
            }
        }

        return $menus;
    }

    /**
     * @param array<int,array<string,mixed>> $menus
     * @return array<int,array<string,mixed>>
     */
    private static function applyRoleMenuTransforms(string $role, array $menus): array
    {
        $role = self::normalizeRole($role);
        $results = Hook::run('auth.menus.transform', [
            'role' => $role,
            'menus' => $menus,
        ]);

        if (!is_array($results) || $results === []) {
            return $menus;
        }

        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $candidateMenus = $result['menus'] ?? null;
            if (!is_array($candidateMenus)) {
                continue;
            }

            $menus = array_values(array_filter($candidateMenus, static fn ($item): bool => is_array($item)));
        }

        return $menus;
    }

    public static function getLoginRedirect(string $role): string
    {
        $role = self::normalizeRole($role);
        return match ($role) {
            self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN, self::ROLE_EDITOR, self::ROLE_DEMO => '/admin/dashboard',
            self::ROLE_MEMBER => '/admin/profile',
            default => '/',
        };
    }

    public static function getRoleBadge(string $role): string
    {
        $role = self::normalizeRole($role);
        $meta = self::ROLES[$role] ?? self::ROLES[self::ROLE_MEMBER];
        $class = $meta['badge_class'];
        $label = __('role_' . $role, 'Users');
        return '<span class="badge ' . $class . '">' . e($label) . '</span>';
    }

    public static function getRegistrationRoles(): array
    {
        $roles = [];
        foreach (self::ROLES as $key => $meta) {
            if ($meta['registerable']) {
                $roles[$key] = $meta;
            }
        }
        return $roles;
    }

    public static function getAssignableRoles(string $managerRole): array
    {
        $managerRole = self::normalizeRole($managerRole);

        return match ($managerRole) {
            self::ROLE_SUPER_ADMIN => self::ROLES,
            self::ROLE_ADMIN => [
                self::ROLE_ADMIN => self::ROLES[self::ROLE_ADMIN],
                self::ROLE_EDITOR => self::ROLES[self::ROLE_EDITOR],
                self::ROLE_DEMO => self::ROLES[self::ROLE_DEMO],
                self::ROLE_MEMBER => self::ROLES[self::ROLE_MEMBER],
            ],
            default => [],
        };
    }

    public static function canAccessAdmin(string $role): bool
    {
        return self::hasPermission($role, 'admin.access');
    }

    public static function getRoleLevel(string $role): int
    {
        $role = self::normalizeRole($role);
        return self::ROLE_HIERARCHY[$role] ?? 0;
    }

    public static function isHigherRole(string $roleA, string $roleB): bool
    {
        return self::getRoleLevel($roleA) > self::getRoleLevel($roleB);
    }

    public static function getAllRoles(): array
    {
        return self::ROLES;
    }
}
