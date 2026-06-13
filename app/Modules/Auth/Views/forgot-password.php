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
    <?php if (!empty($turnstileEnabled) && !empty($turnstileSiteKey)): ?>
        <?= flatcms_front_external_script('https://challenges.cloudflare.com/turnstile/v0/api.js', [
            'async' => true,
            'defer' => true,
            'essential' => true,
            'data' => [
                'flatcms-turnstile' => '1',
            ],
        ]) ?>
    <?php endif; ?>
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1 class="auth-logo"><?= __('app_name', 'Core') ?></h1>
                <p class="auth-subtitle"><?= __('forgot_password_subtitle', 'Auth') ?></p>
            </div>

            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-error"><?= e($flash['error']) ?></div>
            <?php endif; ?>

            <?php if (!empty($flash['success'])): ?>
                <div class="alert alert-success"><?= e($flash['success']) ?></div>
            <?php endif; ?>

            <?php if (!empty($flash['reset_url'])): ?>
                <div class="alert alert-info auth-alert-info">
                    <strong><?= __('reset_link_dev', 'Auth') ?></strong><br>
                    <a href="<?= e($flash['reset_url']) ?>"><?= e($flash['reset_url']) ?></a>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/forgot-password') ?>" class="auth-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email" class="form-label"><?= __('email', 'Auth') ?></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        value="<?= e($old['email'] ?? '') ?>"
                        placeholder="admin@example.test"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>

                <?php if (!empty($turnstileEnabled) && !empty($turnstileSiteKey)): ?>
                    <div class="form-group auth-captcha">
                        <div class="cf-turnstile" data-sitekey="<?= e($turnstileSiteKey) ?>" data-size="flexible"></div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary btn-block">
                    <?= __('send_reset_link', 'Auth') ?>
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

    <script src="<?= module_asset('Auth', 'js/auth-module.js') ?>" defer></script>
</body>
</html>
