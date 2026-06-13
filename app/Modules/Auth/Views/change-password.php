<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('Auth', 'css/auth-module.css') ?>">

<div class="page-header">
    <h1 class="page-title"><?= e($pageTitle) ?></h1>
    <a href="<?= url('/admin/profile') ?>" class="btn btn-secondary"><?= __('back', 'Core') ?></a>
</div>

<div class="card">
    <form method="POST" action="<?= url('/admin/change-password') ?>">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="current_password" class="form-label"><?= __('current_password', 'Auth') ?> *</label>
            <?= form_password('current_password', [
                'placeholder' => '••••••••',
                'required' => true,
                'autocomplete' => 'current-password'
            ]) ?>
        </div>

        <div class="form-group">
            <label for="password" class="form-label"><?= __('new_password', 'Auth') ?> *</label>
            <?= form_password('password', [
                'placeholder' => '••••••••',
                'required' => true,
                'autocomplete' => 'new-password'
            ]) ?>
            <div class="password-strength"
                 data-strength-weak="<?= e(__('strength_weak', 'Auth')) ?>"
                 data-strength-medium="<?= e(__('strength_medium', 'Auth')) ?>"
                 data-strength-strong="<?= e(__('strength_strong', 'Auth')) ?>">
                <div class="password-strength-bar">
                    <div class="password-strength-fill"></div>
                </div>
                <small class="password-strength-text"></small>
            </div>
            <div class="password-requirements">
                <small><?= __('password_requirements', 'Auth') ?></small>
            </div>
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label"><?= __('confirm_password', 'Auth') ?> *</label>
            <?= form_password('password_confirmation', [
                'placeholder' => '••••••••',
                'required' => true,
                'autocomplete' => 'new-password'
            ]) ?>
        </div>

        <div class="form-actions auth-form-actions">
            <button type="submit" class="btn btn-primary">
                <?= __('change_password_button', 'Auth') ?>
            </button>
        </div>
    </form>
</div>

<script src="<?= module_asset('Auth', 'js/auth-module.js') ?>"></script>
