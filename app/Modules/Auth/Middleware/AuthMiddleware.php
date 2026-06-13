<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Auth\Middleware;

use App\Core\Request;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\RoleService;

class AuthMiddleware
{
    public function handle(Request $request): bool
    {
        // Try remember me auto-login if not authenticated
        if (!is_auth()) {
            $authService = new AuthService();
            $user = $authService->loginWithRememberToken($request->ip());
            if ($user) {
                $authService->login($user);
            }
        }

        if (!is_auth()) {
            if ($request->isAjax()) {
                json_error(__('login_required', 'Auth'), 401);
                return false;
            }

            session()->set('intended_url', $request->fullUrl());
            session()->flash('warning', __('login_required', 'Auth'));
            redirect(url('/login'));
            return false;
        }

        return true;
    }

    public function handleAdmin(Request $request): bool
    {
        if (!$this->handle($request)) {
            return false;
        }

        $user = auth();
        $role = $user['role'] ?? RoleService::ROLE_MEMBER;

        if (!RoleService::canAccessAdmin($role)) {
            if ($request->isAjax()) {
                json_error(__('error.unauthorized', 'Core'), 403);
                return false;
            }

            session()->flash('error', __('error.unauthorized', 'Core'));
            redirect(url('/'));
            return false;
        }

        return true;
    }

    public function handleRole(Request $request, string $role): bool
    {
        if (!$this->handle($request)) {
            return false;
        }

        $user = auth();
        $userRole = RoleService::normalizeRole((string) ($user['role'] ?? RoleService::ROLE_MEMBER));
        $role = RoleService::normalizeRole($role);

        if ($userRole !== $role && $userRole !== RoleService::ROLE_SUPER_ADMIN) {
            if ($request->isAjax()) {
                json_error(__('error.unauthorized', 'Core'), 403);
                return false;
            }

            session()->flash('error', __('error.unauthorized', 'Core'));
            redirect(url('/admin'));
            return false;
        }

        return true;
    }

    public function handlePermission(Request $request, string $permission): bool
    {
        if (!$this->handle($request)) {
            return false;
        }

        $user = auth();
        $role = $user['role'] ?? RoleService::ROLE_MEMBER;

        if (!RoleService::hasPermission($role, $permission)) {
            if ($request->isAjax()) {
                json_error(__('error.unauthorized', 'Core'), 403);
                return false;
            }

            session()->flash('error', __('error.unauthorized', 'Core'));
            redirect(url('/admin'));
            return false;
        }

        return true;
    }
}
