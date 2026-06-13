<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-id-badge"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('users_help_badge', 'Users') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('my_profile', 'Users') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('users_profile_help_intro', 'Users') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('users_profile_help_step_identity', 'Users') ?></li>
            <li><?= __('users_profile_help_step_security', 'Users') ?></li>
            <li><?= __('users_profile_help_step_save', 'Users') ?></li>
        </ul>
    </div>
</div>

<div class="card">
    <form method="POST" action="<?= url('/admin/profile') ?>">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name" class="form-label"><?= __('name', 'Users') ?> *</label>
            <input
                type="text"
                id="name"
                name="name"
                class="form-input"
                value="<?= e(old('name', $user['name'] ?? '')) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="email" class="form-label"><?= __('email', 'Users') ?> *</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-input"
                value="<?= e(old('email', $user['email'] ?? '')) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label for="password" class="form-label"><?= __('password', 'Users') ?></label>
            <?= form_password('password', [
                'placeholder' => __('leave_blank_to_keep_current', 'Users'),
                'autocomplete' => 'new-password'
            ]) ?>
            <p class="form-hint"><?= __('password_hint', 'Users') ?></p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <?= __('save', 'Core') ?>
            </button>
        </div>
    </form>
</div>
