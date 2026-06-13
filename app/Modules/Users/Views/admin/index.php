<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Users Module CSS -->
<link rel="stylesheet" href="<?= module_asset('Users', 'css/users.css') ?>">

<?php
$filterRole = $filterRole ?? 'all';
$filterStatus = $filterStatus ?? 'all';
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
    </div>
    <div class="page-header-actions">
        <form method="GET" action="<?= url('/admin/users') ?>" class="users-filter-form">
            <label for="roleFilter" class="form-label"><?= __('role', 'Users') ?></label>
            <div class="users-filter-controls">
                <select id="roleFilter" name="role" class="form-select">
                    <option value="all" <?= selected('all', $filterRole) ?>><?= __('all_roles', 'Users') ?></option>
                    <?php foreach ($roles as $roleKey => $roleLabel): ?>
                        <option value="<?= e($roleKey) ?>" <?= selected($roleKey, $filterRole) ?>>
                            <?= e($roleLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label for="statusFilter" class="form-label"><?= __('status', 'Users') ?></label>
            <div class="users-filter-controls">
                <select id="statusFilter" name="status" class="form-select">
                    <option value="all" <?= selected('all', $filterStatus) ?>><?= __('all_statuses', 'Users') ?></option>
                    <option value="active" <?= selected('active', $filterStatus) ?>><?= __('active', 'Users') ?></option>
                    <option value="inactive" <?= selected('inactive', $filterStatus) ?>><?= __('inactive', 'Users') ?></option>
                    <option value="pending" <?= selected('pending', $filterStatus) ?>><?= __('pending', 'Users') ?></option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary"><?= __('filter', 'Core') ?></button>
            </div>
        </form>
        <a href="<?= url('/admin/users/create') ?>" class="btn btn-primary" id="usersCreateAction">
            + <?= __('create_user', 'Users') ?>
        </a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-user-shield"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('users_help_badge', 'Users') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('users_help_title', 'Users') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('users_help_intro', 'Users') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('users_help_step_roles', 'Users') ?></li>
            <li><?= __('users_help_step_filters', 'Users') ?></li>
            <li><?= __('users_help_step_security', 'Users') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="#usersCreateAction" class="btn btn-primary"><?= __('users_help_action_create', 'Users') ?></a>
            <a href="#usersTableCard" class="btn btn-secondary"><?= __('users_help_action_table', 'Users') ?></a>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="user-stats-row">
    <div class="card user-stat-card">
        <div class="user-stat-value is-total"><?= $stats['total'] ?></div>
        <div class="user-stat-label"><?= __('total_users', 'Users') ?></div>
    </div>
    <div class="card user-stat-card">
        <div class="user-stat-value is-admins"><?= $stats['admins'] ?></div>
        <div class="user-stat-label"><?= __('role_admin', 'Users') ?></div>
    </div>
    <div class="card user-stat-card">
        <div class="user-stat-value is-active"><?= $stats['active'] ?></div>
        <div class="user-stat-label"><?= __('active', 'Users') ?></div>
    </div>
</div>

<div class="card" id="usersTableCard">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th><?= __('name', 'Users') ?></th>
                    <th><?= __('email', 'Users') ?></th>
                    <th><?= __('role', 'Users') ?></th>
                    <th><?= __('status', 'Users') ?></th>
                    <th><?= __('last_login', 'Users') ?></th>
                    <th><?= __('actions', 'Core') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users['data'])): ?>
                    <tr>
                        <td colspan="6" class="empty-state-cell">
                            <div class="admin-empty-state-panel">
                                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                                    <i class="fas fa-users-slash"></i>
                                </div>
                                <h2 class="admin-empty-state-panel__title"><?= __('users_empty_title', 'Users') ?></h2>
                                <p class="admin-empty-state-panel__text"><?= __('users_empty_text', 'Users') ?></p>
                                <div class="admin-empty-state-panel__actions">
                                    <a href="<?= url('/admin/users/create') ?>" class="btn btn-primary btn-sm"><?= __('users_empty_action_create', 'Users') ?></a>
                                    <a href="<?= url('/admin/users') ?>" class="btn btn-ghost btn-sm"><?= __('users_empty_action_reset', 'Users') ?></a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users['data'] as $u): ?>
                        <tr>
                            <td data-label="<?= __('name', 'Users') ?>">
                                <div class="user-cell">
                                    <?php $avatarUrl = avatar_url($u); ?>
                                    <?php if (!empty($avatarUrl)): ?>
                                        <img src="<?= $avatarUrl ?>" alt="" class="user-cell-avatar">
                                    <?php else: ?>
                                        <span class="user-avatar user-cell-avatar slug-code">
                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?= e($u['name']) ?>
                                </div>
                            </td>
                            <td data-label="<?= __('email', 'Users') ?>"><?= e($u['email']) ?></td>
                            <td data-label="<?= __('role', 'Users') ?>">
                                <?php
                                    $normalizedRole = \App\Modules\Auth\Services\RoleService::normalizeRole((string) ($u['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER));
                                    $roleMeta = \App\Modules\Auth\Services\RoleService::ROLES[$normalizedRole] ?? [];
                                    $badgeClass = $roleMeta['badge_class'] ?? 'badge-secondary';
                                ?>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= e($roles[$normalizedRole] ?? ucfirst($normalizedRole)) ?>
                                </span>
                            </td>
                            <td data-label="<?= __('status', 'Users') ?>">
                                <?php
                                    $status = $u['status'] ?? ($u['active'] ?? true ? 'active' : 'inactive');
                                ?>
                                <?php if ($status === 'active'): ?>
                                    <span class="badge badge-success"><?= __('active', 'Users') ?></span>
                                <?php elseif ($status === 'pending'): ?>
                                    <span class="badge badge-warning"><?= __('pending', 'Users') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?= __('inactive', 'Users') ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= __('last_login', 'Users') ?>">
                                <?= !empty($u['last_login']) ? human_date($u['last_login']) : '-' ?>
                            </td>
                            <td data-label="<?= __('actions', 'Core') ?>">
                                <div class="table-actions table-actions-compact user-actions">
                                    <a
                                        href="<?= url('/admin/users/' . $u['id'] . '/edit') ?>"
                                        class="table-action table-action-edit"
                                        title="<?= e(__('edit', 'Core')) ?>"
                                        aria-label="<?= e(__('edit', 'Core')) ?>"
                                    >
                                        <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                    <?php if ($u['id'] !== auth()['id']): ?>
                                        <form action="<?= url('/admin/users/' . $u['id'] . '/delete') ?>" method="POST" class="form-inline">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="table-action table-action-delete"
                                                data-action="confirm-delete"
                                                data-message="<?= __('confirm_delete_user', 'Users') ?>"
                                                data-item-name="<?= e($u['name']) ?>"
                                                title="<?= e(__('delete', 'Core')) ?>"
                                                aria-label="<?= e(__('delete', 'Core')) ?>"
                                            >
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($users['total_pages'] > 1): ?>
        <?php
        $query = [];
        if ($filterRole !== 'all') {
            $query['role'] = $filterRole;
        }
        if ($filterStatus !== 'all') {
            $query['status'] = $filterStatus;
        }
        $baseUrl = url('/admin/users');
        if (!empty($query)) {
            $baseUrl .= '?' . http_build_query($query);
        }
        ?>
        <?= pagination($users, $baseUrl) ?>
    <?php endif; ?>
</div>
