<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Core\FlatFile;
use App\Modules\Auth\Services\RoleService;

class AdminController extends BaseController
{
    private FlatFile $users;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Users');
        $this->users = FlatFile::for('users');
    }

    public function index(): void
    {
        if (!$this->authorize('users.view')) {
            return;
        }

        $page = (int) $this->request->input('page', 1);
        $filterRole = (string) $this->request->input('role', 'all');
        $filterStatus = (string) $this->request->input('status', 'all');
        $roles = $this->getRoles();

        // Stats
        $allUsers = array_map(fn(array $user): array => $this->normalizeUser($user), $this->users->all());
        $stats = [
            'total' => count($allUsers),
            'admins' => count(array_filter($allUsers, fn($u) => in_array(($u['role'] ?? ''), [RoleService::ROLE_SUPER_ADMIN, RoleService::ROLE_ADMIN], true))),
            'active' => count(array_filter($allUsers, fn($u) => ($u['status'] ?? 'active') === 'active')),
        ];

        $validRoles = array_keys($roles);
        if ($filterRole !== 'all' && !in_array($filterRole, $validRoles, true)) {
            $filterRole = 'all';
        }

        $validStatuses = ['active', 'inactive', 'pending'];
        if ($filterStatus !== 'all' && !in_array($filterStatus, $validStatuses, true)) {
            $filterStatus = 'all';
        }

        $filteredUsers = $allUsers;
        if ($filterRole !== 'all') {
            $filteredUsers = array_filter($filteredUsers, function ($u) use ($filterRole) {
                return RoleService::normalizeRole((string) ($u['role'] ?? RoleService::ROLE_MEMBER)) === $filterRole;
            });
        }

        if ($filterStatus !== 'all') {
            $filteredUsers = array_filter($filteredUsers, function ($u) use ($filterStatus) {
                $status = $u['status'] ?? ($u['active'] ?? true ? 'active' : 'inactive');
                return $status === $filterStatus;
            });
        }

        usort($filteredUsers, function ($a, $b) {
            return ($b['created_at'] ?? '') <=> ($a['created_at'] ?? '');
        });

        $perPage = 15;
        $total = count($filteredUsers);
        $totalPages = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $users = [
            'data' => array_slice($filteredUsers, $offset, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ];

        $this->render('Users/Views/admin/index', [
            'pageTitle' => __('users_list', 'Users'),
            'users' => $users,
            'stats' => $stats,
            'roles' => $roles,
            'filterRole' => $filterRole,
            'filterStatus' => $filterStatus,
        ], 'admin.main');
    }

    public function create(): void
    {
        if (!$this->authorize('users.create')) {
            return;
        }

        $this->render('Users/Views/admin/form', [
            'pageTitle' => __('create_user', 'Users'),
            'user' => null,
            'roles' => $this->getRoles(),
        ], 'admin.main');
    }

    public function store(): void
    {
        if (!$this->authorize('users.create')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $data = $this->request->only(['name', 'email', 'password', 'role', 'bio', 'phone', 'company', 'status']);
        $data['role'] = RoleService::normalizeRole((string) ($data['role'] ?? RoleService::ROLE_MEMBER));

        // Validate
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            $this->session->flash('error', __('validation.required_fields', 'Users'));
            $this->session->flash('old', $data);
            $this->redirect(url('/admin/users/create'));
            return;
        }

        if (!$this->isAssignableRole($data['role'])) {
            $this->session->flash('error', __('role_not_assignable', 'Users'));
            $this->session->flash('old', $data);
            $this->redirect(url('/admin/users/create'));
            return;
        }

        // Check email unique
        if ($this->users->findBy('email', $data['email'])) {
            $this->session->flash('error', __('email_exists', 'Users'));
            $this->session->flash('old', $data);
            $this->redirect(url('/admin/users/create'));
            return;
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        $data['status'] = $data['status'] ?? 'active';
        $data['bio'] = $data['bio'] ?? '';
        $data['phone'] = $data['phone'] ?? '';
        $data['company'] = $data['company'] ?? '';
        $data['avatar'] = '';
        $data['last_login'] = '';
        $data['last_login_at'] = '';
        $data['last_login_ip'] = '';
        $data['remember_token'] = '';
        $data['remember_expires'] = null;
        $data['admin_tour_seen_at'] = '';
        $data['admin_tour_version'] = '';
        $data['admin_tour_seen_modules'] = [];

        // Handle avatar upload
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $avatar = $this->handleAvatarUpload($_FILES['avatar']);
            if ($avatar) {
                $data['avatar'] = $avatar;
            }
        }

        $this->users->create($data);

        $this->session->flash('success', __('user_created', 'Users'));
        $this->redirect(url('/admin/users'));
    }

    public function edit(string $id): void
    {
        if (!$this->authorize('users.edit')) {
            return;
        }

        $user = $this->users->find($id);

        if (!$user) {
            $this->session->flash('error', __('user_not_found', 'Users'));
            $this->redirect(url('/admin/users'));
            return;
        }

        $user = $this->normalizeUser($user);

        if (($user['role'] ?? '') === RoleService::ROLE_SUPER_ADMIN && $this->currentManagerRole() !== RoleService::ROLE_SUPER_ADMIN) {
            $this->session->flash('error', __('role_not_assignable', 'Users'));
            $this->redirect(url('/admin/users'));
            return;
        }

        $this->render('Users/Views/admin/form', [
            'pageTitle' => __('edit_user', 'Users'),
            'user' => $user,
            'roles' => $this->getRoles(),
        ], 'admin.main');
    }

    public function update(string $id): void
    {
        if (!$this->authorize('users.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $user = $this->users->find($id);
        if (!$user) {
            $this->session->flash('error', __('user_not_found', 'Users'));
            $this->redirect(url('/admin/users'));
            return;
        }

        $user = $this->normalizeUser($user);

        if (($user['role'] ?? '') === RoleService::ROLE_SUPER_ADMIN && $this->currentManagerRole() !== RoleService::ROLE_SUPER_ADMIN) {
            $this->session->flash('error', __('role_not_assignable', 'Users'));
            $this->redirect(url('/admin/users'));
            return;
        }

        $data = $this->request->only(['name', 'email', 'role', 'status', 'bio', 'phone', 'company']);
        $data['role'] = RoleService::normalizeRole((string) ($data['role'] ?? $user['role'] ?? RoleService::ROLE_MEMBER));
        $data['status'] = $data['status'] ?? 'inactive';

        // Check email unique (exclude current user)
        $existing = $this->users->findBy('email', $data['email']);
        if ($existing && $existing['id'] !== $id) {
            $this->session->flash('error', __('email_exists', 'Users'));
            $this->redirect(url('/admin/users/' . $id . '/edit'));
            return;
        }

        if (!$this->isAssignableRole($data['role'])) {
            $this->session->flash('error', __('role_not_assignable', 'Users'));
            $this->redirect(url('/admin/users/' . $id . '/edit'));
            return;
        }

        if ((string) auth()['id'] === $id && $data['role'] !== ($user['role'] ?? RoleService::ROLE_MEMBER)) {
            $this->session->flash('error', __('cannot_change_own_role', 'Users'));
            $this->redirect(url('/admin/users/' . $id . '/edit'));
            return;
        }

        if (($user['role'] ?? '') === RoleService::ROLE_SUPER_ADMIN && $data['role'] !== RoleService::ROLE_SUPER_ADMIN && $this->countSuperAdmins() <= 1) {
            $this->session->flash('error', __('cannot_downgrade_last_super_admin', 'Users'));
            $this->redirect(url('/admin/users/' . $id . '/edit'));
            return;
        }

        // Update password only if provided
        $newPassword = $this->request->input('password');
        if (!empty($newPassword)) {
            $data['password'] = password_hash($newPassword, PASSWORD_ARGON2ID);
        }

        $removeAvatar = $this->request->input('avatar_remove') === '1';
        $oldAvatar = $user['avatar'] ?? '';

        // Handle avatar upload
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $avatar = $this->handleAvatarUpload($_FILES['avatar']);
            if ($avatar) {
                $data['avatar'] = $avatar;
                if (!empty($oldAvatar)) {
                    $this->deleteAvatarFile($oldAvatar);
                }
                $removeAvatar = false;
            }
        }

        if ($removeAvatar) {
            $data['avatar'] = '';
            if (!empty($oldAvatar)) {
                $this->deleteAvatarFile($oldAvatar);
            }
        }

        $this->users->update($id, $data);

        // Update session if editing self
        if ((string) auth()['id'] === $id) {
            $updatedUser = $this->users->find($id);
            $updatedUser = $this->normalizeUser($updatedUser ?: []);
            unset($updatedUser['password']);
            $this->session->set('user', $updatedUser);
        }

        $this->session->flash('success', __('user_updated', 'Users'));
        $this->redirect(url('/admin/users'));
    }

    public function delete(string $id): void
    {
        if (!$this->authorize('users.delete')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $user = $this->users->find($id);
        if (!$user) {
            $this->session->flash('error', __('user_not_found', 'Users'));
            $this->redirect(url('/admin/users'));
            return;
        }

        $user = $this->normalizeUser($user);

        // Prevent self-deletion
        if ((string) auth()['id'] === $id) {
            $this->session->flash('error', __('cannot_delete_self', 'Users'));
            $this->redirect(url('/admin/users'));
            return;
        }

        if (($user['role'] ?? '') === RoleService::ROLE_SUPER_ADMIN && $this->currentManagerRole() !== RoleService::ROLE_SUPER_ADMIN) {
            $this->session->flash('error', __('role_not_assignable', 'Users'));
            $this->redirect(url('/admin/users'));
            return;
        }

        if (($user['role'] ?? '') === RoleService::ROLE_SUPER_ADMIN && $this->countSuperAdmins() <= 1) {
            $this->session->flash('error', __('cannot_delete_last_super_admin', 'Users'));
            $this->redirect(url('/admin/users'));
            return;
        }

        $this->users->delete($id);
        $this->session->flash('success', __('user_deleted', 'Users'));
        $this->redirect(url('/admin/users'));
    }

    private function getRoles(): array
    {
        $roles = [];
        foreach (RoleService::getAssignableRoles($this->currentManagerRole()) as $key => $meta) {
            $roles[$key] = __('role_' . $key, 'Users');
        }
        return $roles;
    }

    private function currentManagerRole(): string
    {
        return RoleService::normalizeRole((string) (auth()['role'] ?? RoleService::ROLE_MEMBER));
    }

    private function isAssignableRole(string $role): bool
    {
        return array_key_exists($role, RoleService::getAssignableRoles($this->currentManagerRole()));
    }

    private function normalizeUser(array $user): array
    {
        if ($user === []) {
            return $user;
        }

        $user['role'] = RoleService::normalizeRole((string) ($user['role'] ?? RoleService::ROLE_MEMBER));
        return $user;
    }

    private function countSuperAdmins(): int
    {
        $users = array_map(fn(array $user): array => $this->normalizeUser($user), $this->users->all());
        return count(array_filter($users, fn(array $user): bool => ($user['role'] ?? '') === RoleService::ROLE_SUPER_ADMIN));
    }

    private function handleAvatarUpload(array $file): ?string
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file['type'], $allowed, true)) {
            return null;
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            return null;
        }

        $ext = match ($file['type']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $uploadDir = BASE_PATH . '/storage/uploads/avatars';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destination = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return 'avatars/' . $filename;
        }

        return null;
    }

    private function deleteAvatarFile(string $avatar): void
    {
        $path = $this->resolveAvatarPath($avatar);
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }

    private function resolveAvatarPath(string $avatar): ?string
    {
        if ($avatar === '') {
            return null;
        }

        $normalized = ltrim($avatar, '/');

        if (str_starts_with($normalized, 'uploads/avatars/')) {
            $legacyPath = BASE_PATH . '/public/' . $normalized;
            return is_file($legacyPath) ? $legacyPath : null;
        }

        if (str_starts_with($normalized, 'avatars/')) {
            return BASE_PATH . '/storage/uploads/' . $normalized;
        }

        if (str_starts_with($normalized, 'storage/uploads/avatars/')) {
            $storagePath = BASE_PATH . '/' . $normalized;
            return is_file($storagePath) ? $storagePath : null;
        }

        return null;
    }
}
