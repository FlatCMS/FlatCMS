<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>
<head>
    <?php
    $headSettings = is_array($settings ?? null) ? $settings : \App\Core\FlatFile::settings();
    $themeCustomizationService = new \App\Modules\Themes\Services\ThemeCustomizationService();
    $themeCustomizationAsset = $themeCustomizationService->assetForActiveTheme('admin', $headSettings);
    $editorPreviewThemeAsset = $themeCustomizationService->editorPreviewAssetForActiveFrontendTheme($headSettings);
    $siteFavicon = trim((string) ($headSettings['site_favicon'] ?? ''));
    $siteFaviconUrl = $siteFavicon !== '' ? site_media_url($siteFavicon) : '';
    if ($siteFaviconUrl === '') {
        $siteFaviconUrl = url('/favicon.ico');
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="generator" content="FlatCMS">
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    <link rel="icon" href="<?= e($siteFaviconUrl) ?>">
    <title><?= e($pageTitle ?? __('admin_title', 'Core')) ?> - <?= __('app_name', 'Core') ?></title>

    <!-- Theme initialization (external JS to prevent flash) -->
    <script src="<?= theme_asset('js/theme-init.js', 'admin') ?>"></script>

    <!-- Font Awesome (local) -->
    <link rel="stylesheet" href="<?= asset('dists/fontawesome/css/all.min.css') ?>">

    <link rel="stylesheet" href="<?= asset('css/admin/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin/themes/admin-modern-pro.css') ?>">
    <?php if ($themeCustomizationAsset !== ''): ?>
        <link rel="stylesheet" href="<?= e($themeCustomizationAsset) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('dists/suneditor/suneditor.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin/suneditor.css') ?>">
    <?php if ($editorPreviewThemeAsset !== ''): ?>
        <link rel="stylesheet" href="<?= e($editorPreviewThemeAsset) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('css/core/components-password-toggle.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin/guided-tour.css') ?>">
<?php
$adminHeadAssetsHtml = \App\Core\HookAssets::render('admin.assets.head', [
    'settings' => $headSettings,
    'locale' => $locale ?? locale(),
    'auth_user' => $auth_user ?? null,
]);
?>
<?= $adminHeadAssetsHtml !== '' ? $adminHeadAssetsHtml . PHP_EOL : '' ?>
</head>
