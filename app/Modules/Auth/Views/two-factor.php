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
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-logo"><?= __('app_name', 'Core') ?></h1>
                <p class="auth-subtitle">
                    <?= __('two_factor_subtitle', 'Auth', ['email' => e($maskedEmail ?? '')]) ?>
                </p>
            </div>

            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-error"><?= e($flash['error']) ?></div>
            <?php endif; ?>

            <?php if (!empty($flash['success'])): ?>
                <div class="alert alert-success"><?= e($flash['success']) ?></div>
            <?php endif; ?>

            <?php if (!empty($flash['info'])): ?>
                <div class="alert alert-info auth-alert-info"><?= e($flash['info']) ?></div>
            <?php endif; ?>

            <?php if (!empty($flash['two_factor_code_dev'])): ?>
                <div class="alert alert-info auth-alert-info">
                    <strong><?= e(__('two_factor_dev_code', 'Auth')) ?></strong><br>
                    <code><?= e((string) $flash['two_factor_code_dev']) ?></code>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/two-factor') ?>" class="auth-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="code" class="form-label"><?= __('two_factor_code_label', 'Auth') ?></label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        class="form-input"
                        placeholder="<?= e(__('two_factor_code_placeholder', 'Auth')) ?>"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        required
                        autofocus
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <?= __('two_factor_verify_button', 'Auth') ?>
                </button>
            </form>

            <form method="POST" action="<?= url('/two-factor/resend') ?>" class="auth-form auth-form--compact">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-block">
                    <?= __('two_factor_resend_button', 'Auth') ?>
                </button>
            </form>

            <p class="auth-center-text">
                <a href="<?= url('/login') ?>" class="auth-link"><?= __('back_to_login', 'Auth') ?></a>
            </p>
        </div>

        <p class="auth-footer">
            &copy; <?= date('Y') ?> <?= __('app_name', 'Core') ?>
        </p>
    </div>
</body>
</html>
