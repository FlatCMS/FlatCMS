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
    $siteFavicon = trim((string) ($headSettings['site_favicon'] ?? ''));
    $siteFaviconUrl = $siteFavicon !== '' ? site_media_url($siteFavicon) : '';
    if ($siteFaviconUrl === '') {
        $siteFaviconUrl = url('/favicon.ico');
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="FlatCMS">
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    <link rel="icon" href="<?= e($siteFaviconUrl) ?>">
    <title><?= e($pageTitle ?? __('admin_title', 'Core')) ?> - <?= __('app_name', 'Core') ?></title>
    <link rel="stylesheet" href="<?= asset('dists/fontawesome/css/all.min.css') ?>">

    <link rel="stylesheet" href="<?= asset('css/admin/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin/themes/default.css') ?>">
    <?php if ($themeCustomizationAsset !== ''): ?>
        <link rel="stylesheet" href="<?= e($themeCustomizationAsset) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('dists/suneditor/suneditor.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin/suneditor.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/core/components-password-toggle.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin/guided-tour.css') ?>">
    <?php if (module_enabled('AiAgent')): ?>
        <link rel="stylesheet" href="<?= module_asset('AiAgent', 'css/ai-agent.css') ?>">
    <?php endif; ?>
</head>
