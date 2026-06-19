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

            session()->set('intended_url', $this->resolveIntendedUrl($request));
            session()->flash('warning', __('login_required', 'Auth'));
            redirect(url('/login'));
            return false;
        }

        return true;
    }

    private function resolveIntendedUrl(Request $request): string
    {
        $method = $request->method();
        if (in_array($method, ['GET', 'HEAD'], true)) {
            return $request->fullUrl();
        }

        $referer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
        if ($referer !== '' && $this->isSameOriginUrl($referer)) {
            return $referer;
        }

        return url('/admin');
    }

    private function isSameOriginUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $targetHost = strtolower((string) ($parts['host'] ?? ''));
        if ($targetHost === '') {
            return false;
        }

        [$currentHost, $currentPort] = $this->parseHostAndPort((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $currentHost = strtolower($currentHost);
        if ($currentHost === '' || $targetHost !== $currentHost) {
            return false;
        }

        $targetScheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $targetPort = isset($parts['port']) && is_int($parts['port']) ? $parts['port'] : null;
        $targetEffectivePort = $targetPort ?? ($targetScheme === 'https' ? 443 : 80);
        $currentEffectivePort = $currentPort ?? (((string) ($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) ($_SERVER['HTTPS'] ?? '')) !== 'off') ? 443 : 80);

        return $targetEffectivePort === $currentEffectivePort;
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function parseHostAndPort(string $hostHeader): array
    {
        $hostHeader = trim($hostHeader);
        if ($hostHeader === '') {
            return ['', null];
        }

        if (str_starts_with($hostHeader, '[')) {
            $end = strpos($hostHeader, ']');
            if ($end !== false) {
                $host = substr($hostHeader, 1, $end - 1);
                $rest = substr($hostHeader, $end + 1);
                if (str_starts_with($rest, ':')) {
                    $portRaw = substr($rest, 1);
                    if ($portRaw !== '' && ctype_digit($portRaw)) {
                        return [$host, (int) $portRaw];
                    }
                }
                return [$host, null];
            }
        }

        $firstColon = strpos($hostHeader, ':');
        $lastColon = strrpos($hostHeader, ':');
        if ($firstColon !== false && $lastColon !== false && $firstColon === $lastColon) {
            $host = substr($hostHeader, 0, $lastColon);
            $portRaw = substr($hostHeader, $lastColon + 1);
            if ($portRaw !== '' && ctype_digit($portRaw)) {
                return [$host, (int) $portRaw];
            }
        }

        return [$hostHeader, null];
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
