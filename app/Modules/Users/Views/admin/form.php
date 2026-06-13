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

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
    </div>
    <div class="page-header-actions">
        <a href="<?= url('/admin/users') ?>" class="btn btn-secondary">
            <?= __('back', 'Core') ?>
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
            <h2 class="admin-guidance-card__title"><?= __('users_form_help_title', 'Users') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('users_form_help_intro', 'Users') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('users_form_help_step_identity', 'Users') ?></li>
            <li><?= __('users_form_help_step_access', 'Users') ?></li>
            <li><?= __('users_form_help_step_profile', 'Users') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/users') ?>" class="btn btn-primary"><?= __('users_list', 'Users') ?></a>
        </div>
    </div>
</div>

<div class="card">
    <form method="POST" action="<?= $user ? url('/admin/users/' . $user['id']) : url('/admin/users') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-layout-columns">
            <!-- Left column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="name" class="form-label"><?= __('name', 'Users') ?> *</label>
                    <input type="text" id="name" name="name"
                        class="form-input <?= has_error('name') ? 'is-invalid' : '' ?>"
                        value="<?= e(old('name', $user['name'] ?? '')) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label"><?= __('email', 'Users') ?> *</label>
                    <input type="email" id="email" name="email"
                        class="form-input <?= has_error('email') ? 'is-invalid' : '' ?>"
                        value="<?= e(old('email', $user['email'] ?? '')) ?>" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <?= __('password', 'Users') ?> <?= $user ? '' : '*' ?>
                    </label>
                    <?= form_password('password', [
                        'placeholder' => $user ? __('leave_blank_to_keep_current', 'Users') : __('enter_password', 'Users'),
                        'required' => $user ? false : true,
                        'autocomplete' => 'new-password'
                    ]) ?>
                    <?php if ($user): ?>
                        <p class="form-hint"><?= __('password_hint', 'Users') ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="role" class="form-label"><?= __('role', 'Users') ?></label>
                    <select id="role" name="role" class="form-select">
                        <?php foreach ($roles as $value => $label): ?>
                            <option value="<?= $value ?>" <?= selected($value, old('role', $user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER)) ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($user): ?>
                    <div class="form-group">
                        <label for="status" class="form-label"><?= __('status', 'Users') ?></label>
                        <select id="status" name="status" class="form-select">
                            <option value="active" <?= selected('active', old('status', $user['status'] ?? 'active')) ?>><?= __('active', 'Users') ?></option>
                            <option value="inactive" <?= selected('inactive', old('status', $user['status'] ?? 'active')) ?>><?= __('inactive', 'Users') ?></option>
                            <option value="pending" <?= selected('pending', old('status', $user['status'] ?? 'active')) ?>><?= __('pending', 'Users') ?></option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right column -->
            <div class="form-column">
                <div class="form-group">
                    <label for="bio" class="form-label"><?= __('bio', 'Users') ?></label>
                    <textarea id="bio" name="bio" class="form-input" rows="3" data-no-editor><?= e(old('bio', $user['bio'] ?? '')) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label"><?= __('phone', 'Users') ?></label>
                    <input type="tel" id="phone" name="phone" class="form-input"
                        value="<?= e(old('phone', $user['phone'] ?? '')) ?>">
                </div>

                <div class="form-group">
                    <label for="company" class="form-label"><?= __('company', 'Users') ?></label>
                    <input type="text" id="company" name="company" class="form-input"
                        value="<?= e(old('company', $user['company'] ?? '')) ?>">
                </div>

                <div class="form-group">
                    <label for="avatarInput" class="form-label"><?= __('avatar', 'Users') ?></label>
                    <?php $avatarUrl = $user ? avatar_url($user) : null; ?>
                    <?php $isAvatarReadonly = !$user || (string) (auth()['id'] ?? '') !== (string) ($user['id'] ?? ''); ?>
                    <div class="avatar-upload-container<?= $isAvatarReadonly ? ' is-readonly' : '' ?>"
                        data-avatar-readonly="<?= $isAvatarReadonly ? '1' : '0' ?>"
                        data-msg-readonly="<?= __('avatar_private_action', 'Users') ?>"
                        data-msg-invalid-type="<?= __('invalid_file_type', 'Users') ?>"
                        data-msg-file-too-large="<?= __('file_too_large', 'Users') ?>"
                        data-msg-confirm-remove="<?= __('confirm_remove_avatar', 'Users') ?>">
                        <div class="avatar-preview-wrapper">
                            <div class="avatar-preview" id="avatarPreview">
                                <?php if (!empty($avatarUrl)): ?>
                                    <img src="<?= $avatarUrl ?>" alt="Avatar" class="avatar-image" id="avatarImage">
                                <?php else: ?>
                                    <div class="avatar-placeholder" id="avatarPlaceholder">
                                        <svg class="avatar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <div class="avatar-overlay" id="avatarOverlay">
                                    <svg class="avatar-upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="avatar-overlay-text"><?= __('change_avatar', 'Users') ?></span>
                                </div>
                            </div>
                            <input type="file" id="avatarInput" name="avatar" accept="image/*" class="hidden">
                            <input type="hidden" id="avatarRemove" name="avatar_remove" value="0">
                        </div>

                        <div class="avatar-actions">
                            <button type="button" class="btn-avatar-upload" id="btnSelectAvatar">
                                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span><?= __('select_photo', 'Users') ?></span>
                            </button>
                            <?php if (!empty($avatarUrl)): ?>
                                <button type="button" class="btn-avatar-remove" id="btnRemoveAvatar">
                                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="avatar-hints">
                            <p class="avatar-hint">
                                <svg class="hint-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <?= __('avatar_hint', 'Users') ?>
                            </p>
                            <p class="avatar-filename" id="avatarFilename"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= __('save', 'Core') ?>
            </button>
            <a href="<?= url('/admin/users') ?>" class="btn btn-secondary">
                <?= __('cancel', 'Core') ?>
            </a>
        </div>
    </form>
</div>

<script src="<?= module_asset('Users', 'js/avatar-upload.js') ?>?v=<?= filemtime(BASE_PATH . '/app/Modules/Users/Assets/js/avatar-upload.js') ?>"></script>
