<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

$siteMapCssPath = BASE_PATH . '/app/Modules/SiteMap/Assets/css/sitemap.css';
$siteMapCssVersion = is_file($siteMapCssPath) ? (string) filemtime($siteMapCssPath) : '';
$summary = is_array($summary ?? null) ? $summary : [];
$htmlFile = is_array($htmlFile ?? null) ? $htmlFile : [];
$xmlFile = is_array($xmlFile ?? null) ? $xmlFile : [];
$canManageSiteMap = !empty($canManageSiteMap);
?>

<link rel="stylesheet" href="<?= module_asset('SiteMap', 'css/sitemap.css') ?><?= $siteMapCssVersion !== '' ? '?v=' . rawurlencode($siteMapCssVersion) : '' ?>">

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e((string) ($pageTitle ?? __('sitemap_title', 'SiteMap'))) ?></h1>
        <p class="page-subtitle"><?= __('sitemap_subtitle', 'SiteMap') ?></p>
    </div>
</div>

<div class="sitemap-admin-layout">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title"><?= __('sitemap_overview_title', 'SiteMap') ?></h2>
                <p class="module-installer-hint"><?= __('sitemap_overview_hint', 'SiteMap') ?></p>
            </div>
        </div>
        <div class="card-body">
            <div class="sitemap-stats">
                <article class="sitemap-stat">
                    <p class="sitemap-stat-label"><?= __('sitemap_stat_entries', 'SiteMap') ?></p>
                    <p class="sitemap-stat-value"><?= e((string) ((int) ($summary['entries'] ?? 0))) ?></p>
                </article>
                <article class="sitemap-stat">
                    <p class="sitemap-stat-label"><?= __('sitemap_stat_pages', 'SiteMap') ?></p>
                    <p class="sitemap-stat-value"><?= e((string) ((int) ($summary['pages'] ?? 0))) ?></p>
                </article>
                <article class="sitemap-stat">
                    <p class="sitemap-stat-label"><?= __('sitemap_stat_posts', 'SiteMap') ?></p>
                    <p class="sitemap-stat-value"><?= e((string) ((int) ($summary['posts'] ?? 0))) ?></p>
                </article>
                <article class="sitemap-stat">
                    <p class="sitemap-stat-label"><?= __('sitemap_stat_categories', 'SiteMap') ?></p>
                    <p class="sitemap-stat-value"><?= e((string) ((int) ($summary['categories'] ?? 0))) ?></p>
                </article>
                <article class="sitemap-stat">
                    <p class="sitemap-stat-label"><?= __('sitemap_stat_locales', 'SiteMap') ?></p>
                    <p class="sitemap-stat-value"><?= e((string) ((int) ($summary['locales'] ?? 0))) ?></p>
                </article>
            </div>
        </div>
    </div>

    <div class="sitemap-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title"><?= __('sitemap_html_title', 'SiteMap') ?></h2>
                    <p class="module-installer-hint"><?= __('sitemap_html_hint', 'SiteMap') ?></p>
                </div>
            </div>
            <div class="card-body sitemap-card-body">
                <dl class="sitemap-path-list">
                    <div class="sitemap-path-row">
                        <dt><?= __('sitemap_status_label', 'SiteMap') ?></dt>
                        <dd class="sitemap-status"><?= e((string) ($htmlFile['status_label'] ?? '')) ?></dd>
                    </div>
                    <div class="sitemap-path-row">
                        <dt><?= __('sitemap_file_path', 'SiteMap') ?></dt>
                        <dd><?= e((string) ($htmlFile['path'] ?? '')) ?></dd>
                    </div>
                    <div class="sitemap-path-row">
                        <dt><?= __('sitemap_public_url', 'SiteMap') ?></dt>
                        <dd><a href="<?= e((string) ($htmlFile['url'] ?? '')) ?>" target="_blank" rel="noopener"><?= e((string) ($htmlFile['url'] ?? '')) ?></a></dd>
                    </div>
                    <div class="sitemap-path-row">
                        <dt><?= __('sitemap_updated_at', 'SiteMap') ?></dt>
                        <dd><?= e((string) (($htmlFile['updated_at'] ?? '') !== '' ? $htmlFile['updated_at'] : __('sitemap_never_generated', 'SiteMap'))) ?></dd>
                    </div>
                </dl>

                <?php if ($canManageSiteMap): ?>
                    <form method="POST" action="<?= url('/admin/sitemap/generate-html') ?>" class="sitemap-action-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sitemap" aria-hidden="true"></i>
                            <?= __('sitemap_generate_html_action', 'SiteMap') ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title"><?= __('sitemap_xml_title', 'SiteMap') ?></h2>
                    <p class="module-installer-hint"><?= __('sitemap_xml_hint', 'SiteMap') ?></p>
                </div>
            </div>
            <div class="card-body sitemap-card-body">
                <dl class="sitemap-path-list">
                    <div class="sitemap-path-row">
                        <dt><?= __('sitemap_status_label', 'SiteMap') ?></dt>
                        <dd class="sitemap-status"><?= e((string) ($xmlFile['status_label'] ?? '')) ?></dd>
                    </div>
                    <div class="sitemap-path-row">
                        <dt><?= __('sitemap_file_path', 'SiteMap') ?></dt>
                        <dd><?= e((string) ($xmlFile['path'] ?? '')) ?></dd>
                    </div>
                    <div class="sitemap-path-row">
                        <dt><?= __('sitemap_public_url', 'SiteMap') ?></dt>
                        <dd><a href="<?= e((string) ($xmlFile['url'] ?? '')) ?>" target="_blank" rel="noopener"><?= e((string) ($xmlFile['url'] ?? '')) ?></a></dd>
                    </div>
                    <div class="sitemap-path-row">
                        <dt><?= __('sitemap_updated_at', 'SiteMap') ?></dt>
                        <dd><?= e((string) (($xmlFile['updated_at'] ?? '') !== '' ? $xmlFile['updated_at'] : __('sitemap_never_generated', 'SiteMap'))) ?></dd>
                    </div>
                </dl>

                <?php if ($canManageSiteMap): ?>
                    <form method="POST" action="<?= url('/admin/sitemap/generate-xml') ?>" class="sitemap-action-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-file-code" aria-hidden="true"></i>
                            <?= __('sitemap_generate_xml_action', 'SiteMap') ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
