<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$siteName = trim((string) ($settings['site_name'] ?? __('app_name', 'Core')));
if ($siteName === '') {
    $siteName = (string) config('app.name', flatcms_product_name());
}
$siteSlogan = trim((string) ($settings['site_slogan'] ?? ''));
$showSiteName = !array_key_exists('site_name_enabled', $settings ?? [])
    ? true
    : ((int) ($settings['site_name_enabled'] ?? 0) === 1);
$showSiteSlogan = !array_key_exists('site_slogan_enabled', $settings ?? [])
    ? true
    : ((int) ($settings['site_slogan_enabled'] ?? 0) === 1);
$renderSiteName = $showSiteName && $siteName !== '';
$renderSiteSlogan = $showSiteSlogan && $siteSlogan !== '';
$siteLogoVariantDefault = (!$renderSiteName && !$renderSiteSlogan) ? 'banner' : 'compact';
$siteLogoVariant = trim((string) ($settings['site_logo_variant'] ?? $siteLogoVariantDefault));
if (!in_array($siteLogoVariant, ['compact', 'banner', 'banner_framed'], true)) {
    $siteLogoVariant = $siteLogoVariantDefault;
}
$siteLogoService = new \App\Modules\Settings\Services\SiteLogoService();
$siteLogoState = $siteLogoService->resolveLogoUrls($settings ?? []);
$siteLogoUrl = trim((string) ($siteLogoState['default'] ?? ''));
$siteLogoDarkUrl = trim((string) ($siteLogoState['dark'] ?? ''));
$headerClasses = ['site-header'];
$siteLogoClasses = ['site-logo'];
$siteLogoImageClasses = ['site-logo-image'];
if ($siteLogoUrl !== '' && $siteLogoVariant !== 'compact') {
    $headerClasses[] = 'site-header--banner-logo';
    $siteLogoClasses[] = 'site-logo--banner';
    $siteLogoImageClasses[] = $siteLogoVariant === 'banner_framed'
        ? 'site-logo-image--banner-framed'
        : 'site-logo-image--banner';
}
if (!$renderSiteName && !$renderSiteSlogan) {
    $siteLogoClasses[] = 'site-logo--logo-only';
}
$accountUrl = url('/admin/profile');
$accountLabel = __('my_profile', 'Users');
$registrationEnabled = false;
$toggleLabel = __('toggle_submenu', 'Core');
$menuStandard = $menuStandard ?? ($menu ?? []);
$avatarUrl = is_auth() ? avatar_url(auth()) : null;
$indentHtml = static function (string $html, int $level): string {
    $value = trim($html);
    if ($value === '') {
        return '';
    }

    $lines = preg_split('/\r\n|\r|\n/', $value);
    if (!is_array($lines)) {
        return $value;
    }

    $prefix = str_repeat('    ', $level);
    foreach ($lines as &$line) {
        if ($line !== '') {
            $line = $prefix . $line;
        }
    }
    unset($line);

    return implode(PHP_EOL, $lines);
};

ob_start();
if ($siteLogoUrl !== ''): ?>
<picture>
<?php if ($siteLogoDarkUrl !== '' && $siteLogoDarkUrl !== $siteLogoUrl): ?>
    <source srcset="<?= e($siteLogoDarkUrl) ?>" media="(prefers-color-scheme: dark)">
<?php endif; ?>
    <img src="<?= e($siteLogoUrl) ?>" alt="<?= e($siteName) ?>" class="<?= e(implode(' ', $siteLogoImageClasses)) ?>" loading="lazy" decoding="async">
</picture>
<?php else: ?>
<span class="logo-icon">◆</span>
<?php endif;
$siteLogoMediaHtml = $indentHtml((string) ob_get_clean(), 3);

ob_start();
if ($renderSiteSlogan): ?>
<span class="site-brand-slogan"><?= e($siteSlogan) ?></span>
<?php endif;
$siteSloganHtml = $indentHtml((string) ob_get_clean(), 4);

ob_start();
if (is_auth()):
    if (can('dashboard.view')): ?>
<a href="<?= url('/admin/dashboard') ?>" class="mobile-auth-link">
    <i class="fas fa-gauge-high nav-icon" aria-hidden="true"></i>
    <?= __('dashboard', 'Core') ?>
</a>
<?php endif; ?>
<a href="<?= $accountUrl ?>" class="mobile-auth-link">
    <i class="fas fa-user nav-icon" aria-hidden="true"></i>
    <?= $accountLabel ?>
</a>
<a href="<?= url('/admin/change-password') ?>" class="mobile-auth-link">
    <i class="fas fa-key nav-icon" aria-hidden="true"></i>
    <?= __('change_password', 'Auth') ?>
</a>
<form method="POST" action="<?= url('/logout') ?>" class="mobile-auth-form">
    <?= csrf_field() ?>
    <button type="submit" class="mobile-auth-link mobile-auth-link-danger">
        <i class="fas fa-sign-out-alt nav-icon" aria-hidden="true"></i>
        <?= __('logout', 'Auth') ?>
    </button>
</form>
<?php else: ?>
<a href="<?= url('/login') ?>" class="mobile-auth-link">
    <i class="fas fa-right-to-bracket nav-icon" aria-hidden="true"></i>
    <?= __('login_button', 'Auth') ?>
</a>
<?php if ($registrationEnabled): ?>
<a href="<?= url('/register') ?>" class="mobile-auth-link">
    <i class="fas fa-user-plus nav-icon" aria-hidden="true"></i>
    <?= __('register_link', 'Auth') ?>
</a>
<?php endif;
endif;
$mobileAuthHtml = $indentHtml((string) ob_get_clean(), 4);

ob_start();
if (!empty($avatarUrl)): ?>
<img src="<?= $avatarUrl ?>" alt="" class="header-auth-avatar" loading="lazy" decoding="async">
<?php else: ?>
<span class="header-auth-avatar header-auth-avatar-fallback" aria-hidden="true">
    <i class="fas fa-user"></i>
</span>
<?php endif;
$authTriggerContentHtml = $indentHtml((string) ob_get_clean(), 5);

ob_start();
if (is_auth()):
    if (can('dashboard.view')): ?>
<a href="<?= url('/admin/dashboard') ?>" class="header-auth-item dropdown-item"><?= __('dashboard', 'Core') ?></a>
<?php endif; ?>
<a href="<?= $accountUrl ?>" class="header-auth-item dropdown-item"><?= $accountLabel ?></a>
<a href="<?= url('/admin/change-password') ?>" class="header-auth-item dropdown-item"><?= __('change_password', 'Auth') ?></a>
<hr class="header-auth-divider dropdown-divider">
<form method="POST" action="<?= url('/logout') ?>" class="header-auth-form dropdown-form">
    <?= csrf_field() ?>
    <button type="submit" class="header-auth-item dropdown-item header-auth-item-danger dropdown-item-danger"><?= __('logout', 'Auth') ?></button>
</form>
<?php else: ?>
<a href="<?= url('/login') ?>" class="header-auth-item dropdown-item"><?= __('login_button', 'Auth') ?></a>
<?php if ($registrationEnabled): ?>
<a href="<?= url('/register') ?>" class="header-auth-item dropdown-item"><?= __('register_link', 'Auth') ?></a>
<?php endif;
endif;
$authMenuHtml = $indentHtml((string) ob_get_clean(), 5);

ob_start();
include __DIR__ . '/lang-switch.php';
$langSwitchHtml = trim((string) ob_get_clean());
if ($langSwitchHtml !== '') {
    $langSwitchLines = preg_split('/\r\n|\r|\n/', $langSwitchHtml);
    if (is_array($langSwitchLines)) {
        foreach ($langSwitchLines as &$langSwitchLine) {
            if ($langSwitchLine !== '') {
                $langSwitchLine = '            ' . $langSwitchLine;
            }
        }
        unset($langSwitchLine);
        $langSwitchHtml = implode(PHP_EOL, $langSwitchLines);
    }
}
?>
<header class="<?= e(implode(' ', $headerClasses)) ?>">
    <div class="container">
        <a href="<?= url('/' . $locale) ?>" class="<?= e(implode(' ', $siteLogoClasses)) ?>">
<?= $siteLogoMediaHtml !== '' ? $siteLogoMediaHtml . PHP_EOL : '' ?>
<?php if ($renderSiteName || $renderSiteSlogan): ?>
            <span class="site-brand-text">
<?php if ($renderSiteName): ?>
                <span class="site-brand-name"><?= e($siteName) ?></span>
<?php endif; ?>
<?= $siteSloganHtml !== '' ? $siteSloganHtml . PHP_EOL : '' ?>
            </span>
<?php endif; ?>
        </a>

        <nav class="main-nav" id="mainNav">
<?= menu_front_render_menu(
    is_array($menuStandard) ? $menuStandard : [],
    (string) $locale,
    [
        'toggleLabel' => $toggleLabel,
        'depth' => 4,
    ]
) . PHP_EOL ?>
            <div class="mobile-auth-block">
<?= $mobileAuthHtml !== '' ? $mobileAuthHtml . PHP_EOL : '' ?>
            </div>
        </nav>

        <div class="header-actions">
            <div class="header-auth user-dropdown" data-component="dropdown">
                <button type="button"
                        class="header-auth-trigger user-btn"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-label="<?= is_auth() ? e(__('my_profile', 'Users')) : e(__('login_button', 'Auth')) ?>">
<?= $authTriggerContentHtml !== '' ? $authTriggerContentHtml . PHP_EOL : '' ?>
                </button>

                <div class="header-auth-menu dropdown-menu">
<?= $authMenuHtml !== '' ? $authMenuHtml . PHP_EOL : '' ?>
                </div>
            </div>
<?= $langSwitchHtml !== '' ? $langSwitchHtml . PHP_EOL : '' ?>
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="<?= e(__('navigation', 'Core')) ?>">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>
