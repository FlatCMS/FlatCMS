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
    <link rel="stylesheet" href="<?= asset('dists/fontawesome/css/all.min.css') ?>">
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
    <div class="auth-container auth-container-wide">
        <!-- Logo Header -->
        <div class="auth-header auth-header-spaced">
            <div class="auth-header-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1 class="auth-logo"><?= __('register_title', 'Auth') ?></h1>
            <p class="auth-subtitle"><?= __('register_subtitle', 'Auth') ?></p>
        </div>

        <!-- Role Selection -->
        <div class="role-selection">
            <h3><?= __('register_as', 'Auth') ?></h3>
            <div class="role-grid">
                <?php foreach ($roles as $roleKey => $roleMeta): ?>
                    <a href="<?= url('/register/' . $roleKey) ?>"
                       class="role-card-link <?= ($selectedRole === $roleKey) ? 'active' : '' ?>">
                        <div class="role-card-icon">
                            <i class="<?= e($roleMeta['icon']) ?>"></i>
                        </div>
                        <div class="role-card-name"><?= __('role_' . $roleKey, 'Auth') ?></div>
                        <div class="role-card-desc"><?= __('role_desc_' . $roleKey, 'Auth') ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (!empty($flash['error'])): ?>
            <div class="alert alert-error auth-alert-spaced">
                <i class="fas fa-exclamation-circle auth-alert-icon"></i>
                <?= e($flash['error']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash['errors']) && is_array($flash['errors'])): ?>
            <div class="alert alert-error auth-alert-spaced">
                <?php foreach ($flash['errors'] as $err): ?>
                    <div class="auth-error-item">
                        <i class="fas fa-times-circle"></i> <?= e($err) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($flash['success'])): ?>
            <div class="alert alert-success auth-alert-spaced">
                <i class="fas fa-check-circle auth-alert-icon"></i>
                <?= e($flash['success']) ?>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <div class="register-card">
            <form method="POST" action="<?= url('/register') ?>" class="auth-form">
                <?= csrf_field() ?>
                <input type="hidden" name="role" value="<?= e($selectedRole ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER) ?>">

                <!-- Identity -->
                <div class="auth-form-row auth-form-row-2">
                    <div class="form-group">
                        <label for="first_name" class="form-label"><?= __('first_name', 'Auth') ?> <span class="required-star">*</span></label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input type="text" id="first_name" name="first_name" class="form-input" autocomplete="given-name"
                                value="<?= e($old['first_name'] ?? '') ?>" required autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="name" class="form-label"><?= __('last_name', 'Auth') ?> <span class="required-star">*</span></label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                            <input type="text" id="name" name="name" class="form-input" autocomplete="family-name"
                                value="<?= e($old['name'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label"><?= __('email', 'Auth') ?> <span class="required-star">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="email" name="email" class="form-input" autocomplete="email"
                            value="<?= e($old['email'] ?? '') ?>" placeholder="you@example.test" required>
                    </div>
                </div>

                <!-- Phone -->
                <div class="form-group">
                    <label for="phone" class="form-label"><?= __('phone', 'Users') ?></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fas fa-phone"></i></span>
                        <input type="tel" id="phone" name="phone" class="form-input" autocomplete="tel"
                            value="<?= e($old['phone'] ?? '') ?>">
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label"><?= __('password', 'Auth') ?> <span class="required-star">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-input has-toggle"
                            placeholder="&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;"
                            required autocomplete="new-password">
                        <button type="button" class="toggle-btn" data-toggle-password="password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength password-strength--compact"
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

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password_confirmation" class="form-label"><?= __('confirm_password', 'Auth') ?> <span class="required-star">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-input has-toggle has-indicator"
                            placeholder="&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;"
                            required autocomplete="new-password">
                        <button type="button" class="toggle-btn" data-toggle-password="password_confirmation" aria-label="<?= e(__('show_password', 'Auth')) ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                        <span class="match-indicator" id="match-indicator">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    </div>
                </div>

                <!-- Terms -->
                <div class="form-check">
                    <input type="checkbox" id="terms" name="terms" class="form-checkbox" value="1">
                    <label for="terms" class="form-check-label"><?= __('accept_terms', 'Auth') ?></label>
                </div>

                <?php if (!empty($turnstileEnabled) && !empty($turnstileSiteKey)): ?>
                    <div class="form-group auth-captcha">
                        <div class="cf-turnstile" data-sitekey="<?= e($turnstileSiteKey) ?>" data-size="flexible"></div>
                    </div>
                <?php endif; ?>

                <!-- Submit -->
                <button type="submit" class="btn btn-primary btn-block btn-auth-submit">
                    <i class="fas fa-user-plus auth-inline-icon"></i>
                    <?= __('register_button', 'Auth') ?>
                </button>
            </form>

            <!-- Social Auth Divider -->
            <div class="auth-divider">
                <span><?= __('or_continue_with', 'Auth') ?></span>
            </div>

            <!-- Social Auth Buttons -->
            <div class="social-grid">
                <a href="<?= url('/auth/social/google') ?>" class="btn-social btn-google">
                    <span class="social-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path fill="#ea4335" d="M5.27 9.76A7.08 7.08 0 0 1 12 5.48c1.68 0 3.19.58 4.38 1.53l3.24-3.24A11.96 11.96 0 0 0 12 .5a11.98 11.98 0 0 0-10.71 6.59l3.98 3.09Z"/>
                            <path fill="#34a853" d="M16.04 18.01A7.4 7.4 0 0 1 12 19.26 7.08 7.08 0 0 1 5.27 14l-3.97 3.09A11.98 11.98 0 0 0 12 23.5a11.45 11.45 0 0 0 7.84-3.03l-3.8-2.46Z"/>
                            <path fill="#4a90d9" d="M19.84 20.47A11.82 11.82 0 0 0 23.54 12c0-.82-.1-1.68-.29-2.5H12v5.04h6.47a5.56 5.56 0 0 1-2.43 3.63l3.8 2.3Z"/>
                            <path fill="#fbbc05" d="M5.27 14a7.08 7.08 0 0 1 0-4.24L1.29 6.67A11.98 11.98 0 0 0 0 12c0 1.93.47 3.76 1.29 5.38L5.27 14Z"/>
                        </svg>
                    </span>
                    Google
                </a>
                <a href="<?= url('/auth/social/microsoft') ?>" class="btn-social btn-microsoft">
                    <span class="social-icon">
                        <svg viewBox="0 0 21 21" width="18" height="18">
                            <rect fill="#f25022" x="1" y="1" width="9" height="9"/>
                            <rect fill="#00a4ef" x="1" y="11" width="9" height="9"/>
                            <rect fill="#7fba00" x="11" y="1" width="9" height="9"/>
                            <rect fill="#ffb900" x="11" y="11" width="9" height="9"/>
                        </svg>
                    </span>
                    Microsoft
                </a>
                <a href="<?= url('/auth/social/linkedin') ?>" class="btn-social btn-linkedin">
                    <span class="social-icon"><i class="fab fa-linkedin-in social-icon--linkedin"></i></span>
                    LinkedIn
                </a>
                <a href="<?= url('/auth/social/facebook') ?>" class="btn-social btn-facebook">
                    <span class="social-icon"><i class="fab fa-facebook-f social-icon--facebook"></i></span>
                    Facebook
                </a>
                <a href="<?= url('/auth/social/github') ?>" class="btn-social btn-github btn-full">
                    <span class="social-icon"><i class="fab fa-github social-icon--github"></i></span>
                    GitHub
                </a>
            </div>
        </div>

        <!-- Login link -->
        <p class="auth-center-text">
            <?= __('already_have_account', 'Auth') ?>
            <a href="<?= url('/login') ?>" class="auth-link"><?= __('login_link', 'Auth') ?></a>
        </p>

        <!-- Back to site -->
        <p class="auth-center-text auth-center-text--compact">
            <a href="<?= url('/') ?>" class="auth-back-link">
                <i class="fas fa-arrow-left auth-inline-icon"></i><?= __('back', 'Core') ?>
            </a>
        </p>

        <p class="auth-footer">
            &copy; <?= date('Y') ?> <?= __('app_name', 'Core') ?>
        </p>
    </div>

    <script src="<?= module_asset('Auth', 'js/auth-module.js') ?>" defer></script>
</body>
</html>
