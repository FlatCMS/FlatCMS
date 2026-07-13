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

use App\Core\FlatFile;
use App\Modules\Users\Support\UserName;

class AuthService
{
    private FlatFile $users;
    private const REMEMBER_COOKIE = 'flatcms_remember';
    private const REMEMBER_DAYS = 30;

    public function __construct()
    {
        $this->users = FlatFile::for('users');
    }

    public function attempt(string $email, string $password): ?array
    {
        $user = $this->users->findBy('email', $email);

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        // Check status
        $status = $user['status'] ?? 'active';
        if ($status !== 'active') {
            return null;
        }

        // Check legacy active field
        if (isset($user['active']) && !$user['active']) {
            return null;
        }

        // Re-hash if using old algorithm
        if (password_needs_rehash($user['password'], PASSWORD_ARGON2ID)) {
            $this->users->update((string) $user['id'], [
                'password' => password_hash($password, PASSWORD_ARGON2ID),
            ]);
        }

        unset($user['password']);

        return UserName::forSession($user);
    }

    public function touchLastLogin(string $userId, string $ipAddress = ''): void
    {
        $ipAddress = trim($ipAddress);
        if ($ipAddress === '') {
            $ipAddress = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        }

        $this->users->update($userId, [
            'last_login' => date('Y-m-d H:i:s'),
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ipAddress,
        ]);
    }

    public function login(array $user, bool $remember = false): void
    {
        $userId = (string) ($user['id'] ?? '');
        $forceNextLogin = !empty($user['admin_tour_force_next_login']);
        if ($forceNextLogin && $userId !== '') {
            $this->users->update($userId, [
                'admin_tour_force_next_login' => 0,
            ]);
        }

        $sessionUser = $user;
        if ($forceNextLogin) {
            $sessionUser['admin_tour_force_next_login'] = 0;
        }

        session()->set('user', UserName::forSession($sessionUser));
        session()->regenerate();

        if ($forceNextLogin) {
            session()->set('admin_tour_force_next_login', true);
        }

        if ($remember) {
            $this->setRememberToken($userId);
        }
    }

    public function loginWithRememberToken(string $ipAddress = ''): ?array
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if (!$cookie) {
            return null;
        }

        $parts = explode('|', $cookie, 2);
        if (count($parts) !== 2) {
            $this->clearRememberCookie();
            return null;
        }

        [$userId, $token] = $parts;
        $user = $this->users->find($userId);

        if (!$user) {
            $this->clearRememberCookie();
            return null;
        }

        $storedHash = $user['remember_token'] ?? '';
        $expires = $user['remember_expires'] ?? null;

        if (empty($storedHash) || !hash_equals($storedHash, hash('sha256', $token))) {
            $this->clearRememberCookie();
            return null;
        }

        if ($expires && strtotime($expires) < time()) {
            $this->clearRememberCookie();
            $this->users->update($userId, ['remember_token' => '', 'remember_expires' => null]);
            return null;
        }

        // Check status
        $status = $user['status'] ?? 'active';
        if ($status !== 'active') {
            $this->clearRememberCookie();
            return null;
        }

        if ($this->shouldDisableRememberWhenEmail2faActive() && $this->shouldRequireEmail2fa($user)) {
            $this->forgetRememberToken((string) $userId);
            return null;
        }

        $this->touchLastLogin($userId, $ipAddress);

        unset($user['password']);
        return UserName::forSession($user);
    }

    public function logout(): void
    {
        $userId = session()->get('user')['id'] ?? null;

        if ($userId) {
            $this->users->update((string) $userId, [
                'remember_token' => '',
                'remember_expires' => null,
            ]);
        }

        $this->clearRememberCookie();
        session()->remove('user');
        session()->regenerate();
    }

    public function user(): ?array
    {
        return session()->get('user');
    }

    public function check(): bool
    {
        return session()->has('user');
    }

    public function shouldRequireEmail2fa(array $user): bool
    {
        $enabled = $this->envBool(env('AUTH_2FA_EMAIL_ENABLED', false), false);
        if (!$enabled) {
            return false;
        }

        $role = strtolower(trim((string) ($user['role'] ?? '')));
        $rolesRaw = strtolower((string) env('AUTH_2FA_EMAIL_ROLES', 'super_admin,admin'));
        $roles = array_filter(array_map('trim', explode(',', $rolesRaw)));

        if (in_array('all', $roles, true)) {
            return true;
        }

        return $role !== '' && in_array($role, $roles, true);
    }

    public function shouldDisableRememberWhenEmail2faActive(): bool
    {
        return $this->envBool(env('AUTH_2FA_EMAIL_DISABLE_REMEMBER', '1'), true);
    }

    public function id(): ?string
    {
        $user = $this->user();
        return $user['id'] ?? null;
    }

    public function refresh(): ?array
    {
        $userId = $this->id();
        if (!$userId) {
            return null;
        }

        $user = $this->users->find($userId);
        if (!$user) {
            return null;
        }

        unset($user['password']);
        $user = UserName::forSession($user);
        session()->set('user', $user);
        return $user;
    }

    // --- Registration ---

    public function register(array $data): array
    {
        $data = UserName::forStorage($data);
        $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        $data['status'] = $data['status'] ?? 'active';
        $data['role'] = $data['role'] ?? RoleService::ROLE_MEMBER;
        $data['bio'] = $data['bio'] ?? '';
        $data['phone'] = $data['phone'] ?? '';
        $data['company'] = $data['company'] ?? '';
        $data['avatar'] = $data['avatar'] ?? '';
        $data['last_login'] = '';
        $data['last_login_at'] = '';
        $data['last_login_ip'] = '';
        $data['remember_token'] = '';
        $data['remember_expires'] = null;
        $data['admin_tour_seen_at'] = $data['admin_tour_seen_at'] ?? '';
        $data['admin_tour_version'] = $data['admin_tour_version'] ?? '';
        $data['admin_tour_seen_modules'] = is_array($data['admin_tour_seen_modules'] ?? null)
            ? array_values($data['admin_tour_seen_modules'])
            : [];

        return UserName::forSession($this->users->create($data));
    }

    public function createUser(array $data): array
    {
        $data = UserName::forStorage($data);
        $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        $data['status'] = $data['status'] ?? 'active';
        $data['role'] = $data['role'] ?? RoleService::ROLE_MEMBER;
        $data['bio'] = $data['bio'] ?? '';
        $data['phone'] = $data['phone'] ?? '';
        $data['company'] = $data['company'] ?? '';
        $data['avatar'] = $data['avatar'] ?? '';
        $data['last_login'] = '';
        $data['last_login_at'] = '';
        $data['last_login_ip'] = '';
        $data['remember_token'] = '';
        $data['remember_expires'] = null;
        $data['admin_tour_seen_at'] = $data['admin_tour_seen_at'] ?? '';
        $data['admin_tour_version'] = $data['admin_tour_version'] ?? '';
        $data['admin_tour_seen_modules'] = is_array($data['admin_tour_seen_modules'] ?? null)
            ? array_values($data['admin_tour_seen_modules'])
            : [];

        return UserName::forSession($this->users->create($data));
    }

    public function updatePassword(string $userId, string $newPassword): bool
    {
        $result = $this->users->update($userId, [
            'password' => password_hash($newPassword, PASSWORD_ARGON2ID),
        ]);

        return $result !== null;
    }

    public function verifyPassword(string $userId, string $password): bool
    {
        $user = $this->users->find($userId);

        if (!$user) {
            return false;
        }

        return password_verify($password, $user['password']);
    }

    // --- Password validation ---

    public function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'password_min_length';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'password_uppercase';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'password_lowercase';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'password_number';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'password_special';
        }

        return $errors;
    }

    public function getPasswordStrength(string $password): int
    {
        $score = 0;
        $len = strlen($password);

        // Length
        if ($len >= 8) $score += 20;
        if ($len >= 12) $score += 10;
        if ($len >= 16) $score += 10;

        // Character types
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 15;
        if (preg_match('/[0-9]/', $password)) $score += 15;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 20;

        return min(100, $score);
    }

    // --- Permissions ---

    public function can(string $permission): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        $role = $user['role'] ?? RoleService::ROLE_MEMBER;
        return RoleService::hasPermission($role, $permission);
    }

    public function canAny(array $permissions): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        $role = $user['role'] ?? RoleService::ROLE_MEMBER;
        return RoleService::hasAnyPermission($role, $permissions);
    }

    // --- Remember me helpers ---

    private function setRememberToken(string $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (self::REMEMBER_DAYS * 86400));

        $this->users->update($userId, [
            'remember_token' => hash('sha256', $token),
            'remember_expires' => $expires,
        ]);

        $cookieValue = $userId . '|' . $token;
        $cookieExpires = time() + (self::REMEMBER_DAYS * 86400);

        setcookie(self::REMEMBER_COOKIE, $cookieValue, [
            'expires' => $cookieExpires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => isset($_SERVER['HTTPS']),
        ]);
    }

    private function forgetRememberToken(string $userId): void
    {
        $this->clearRememberCookie();
        $this->users->update($userId, [
            'remember_token' => '',
            'remember_expires' => null,
        ]);
    }

    private function envBool(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === null) {
            return $default;
        }

        $v = strtolower(trim((string) $value));
        if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    private function clearRememberCookie(): void
    {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
