<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <?php
    $authSettings = \App\Core\FlatFile::settings();
    $siteFavicon = trim((string) ($authSettings['site_favicon'] ?? ''));
    $siteFaviconUrl = $siteFavicon !== '' ? site_media_url($siteFavicon) : '';
    if ($siteFaviconUrl === '') {
        $siteFaviconUrl = url('/favicon.ico');
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= e($siteFaviconUrl) ?>">
    <title><?= e($pageTitle) ?> - <?= __('app_name', 'Core') ?></title>
    <link rel="stylesheet" href="<?= theme_asset('css/auth.css', 'admin') ?>">
    <link rel="stylesheet" href="<?= module_asset('Auth', 'css/auth-module.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/core/components-password-toggle.css') ?>">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-logo"><?= __('app_name', 'Core') ?></h1>
                <p class="auth-subtitle"><?= __('reset_password_subtitle', 'Auth') ?></p>
            </div>

            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-error"><?= e($flash['error']) ?></div>
            <?php endif; ?>

            <p class="auth-reset-hint">
                <?= __('reset_for_email', 'Auth') ?> <strong><?= e($email) ?></strong>
            </p>

            <form method="POST" action="<?= url('/reset-password/' . $token) ?>" class="auth-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="password" class="form-label"><?= __('new_password', 'Auth') ?></label>
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
                    <label for="password_confirmation" class="form-label"><?= __('confirm_password', 'Auth') ?></label>
                    <?= form_password('password_confirmation', [
                        'placeholder' => '••••••••',
                        'required' => true,
                        'autocomplete' => 'new-password'
                    ]) ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <?= __('reset_password_button', 'Auth') ?>
                </button>
            </form>
        </div>

        <p class="auth-footer">
            &copy; <?= date('Y') ?> <?= __('app_name', 'Core') ?>
        </p>
    </div>

    <script src="<?= asset('js/core/components-password-toggle.js') ?>" defer></script>
    <script src="<?= module_asset('Auth', 'js/auth-module.js') ?>" defer></script>
</body>
</html>
