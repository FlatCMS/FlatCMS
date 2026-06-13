<?php
/**
 * FlatCMS v1.0.0 - Système de Gestion de Contenu Flat-File
 * @project     FlatCMS v1.0.0
 * @author      Alain BROYE
 * @version     1.0.0
 * @last_update 2026-02-03
 * @file        app/Modules/Auth/Views/login.php
 * @description Vue du module Auth.
 * @license     MIT
 * "Simplicité, Rapidité, Flexibilité."
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
                <p class="auth-subtitle"><?= __('login_subtitle', 'Auth') ?></p>
            </div>

            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-error">
                    <?= e($flash['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($flash['success'])): ?>
                <div class="alert alert-success">
                    <?= e($flash['success']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($flash['warning'])): ?>
                <div class="alert alert-warning">
                    <?= e($flash['warning']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('/login') ?>" class="auth-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email" class="form-label"><?= __('email', 'Auth') ?></label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                        value="<?= e($old['email'] ?? '') ?>"
                        placeholder="admin@example.test"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label"><?= __('password', 'Auth') ?></label>
                    <?= form_password('password', [
                        'placeholder' => '••••••••',
                        'required' => true,
                        'autocomplete' => 'current-password',
                        'class' => !empty($errors['password']) ? 'is-invalid' : ''
                    ]) ?>
                </div>

                <div class="auth-remember-row">
                    <div class="form-check">
                        <input type="checkbox" id="remember" name="remember" class="form-checkbox" value="1">
                        <label for="remember" class="form-check-label"><?= __('remember_me', 'Auth') ?></label>
                    </div>
                    <a href="<?= url('/forgot-password') ?>" class="auth-link"><?= __('forgot_password', 'Auth') ?></a>
                </div>

                <?php if (!empty($turnstileEnabled) && !empty($turnstileSiteKey)): ?>
                    <div class="form-group">
                        <div class="cf-turnstile" data-sitekey="<?= e($turnstileSiteKey) ?>"></div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary btn-block">
                    <?= __('login_button', 'Auth') ?>
                </button>
            </form>

            <p class="auth-center-text">
                <?= __('no_account', 'Auth') ?>
                <a href="<?= url('/register') ?>" class="auth-link"><?= __('register_link', 'Auth') ?></a>
            </p>

            <p class="auth-center-text auth-center-text--compact">
                <a href="<?= url('/') ?>" class="auth-back-link"><?= __('back_to_site', 'Auth') ?></a>
            </p>
        </div>

        <p class="auth-footer">
            &copy; <?= date('Y') ?> <?= __('app_name', 'Core') ?>
        </p>
    </div>
    
    <script src="<?= asset('js/core/components-password-toggle.js') ?>" defer></script>
    <script src="<?= module_asset('Auth', 'js/auth-module.js') ?>" defer></script>
</body>
</html>
