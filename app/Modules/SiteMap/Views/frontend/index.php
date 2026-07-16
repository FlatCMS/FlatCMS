<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

$groupedEntries = is_array($groupedEntries ?? null) ? $groupedEntries : [];
$sitemapSummary = is_array($sitemapSummary ?? null) ? $sitemapSummary : [];
$pageTitle = trim((string) ($pageTitle ?? __('sitemap_public_title', 'SiteMap')));
$pageDescription = trim((string) ($pageDescription ?? __('sitemap_public_description', 'SiteMap')));
?>

<section class="page-header sitemap-public-header">
    <div class="container sitemap-public-header-inner">
        <div class="sitemap-public-intro">
            <p class="sitemap-public-kicker"><?= __('sitemap_title', 'SiteMap') ?></p>
            <h1><?= e($pageTitle) ?></h1>
            <p><?= e($pageDescription) ?></p>
        </div>
        <div class="sitemap-public-summary">
            <article class="sitemap-public-summary-card">
                <span class="sitemap-public-summary-label"><?= __('sitemap_stat_entries', 'SiteMap') ?></span>
                <strong class="sitemap-public-summary-value"><?= e((string) ((int) ($sitemapSummary['entries'] ?? 0))) ?></strong>
            </article>
            <article class="sitemap-public-summary-card">
                <span class="sitemap-public-summary-label"><?= __('sitemap_stat_locales', 'SiteMap') ?></span>
                <strong class="sitemap-public-summary-value"><?= e((string) ((int) ($sitemapSummary['locales'] ?? 0))) ?></strong>
            </article>
        </div>
    </div>
</section>

<section class="page-content sitemap-public-content">
    <div class="container sitemap-public-stack">
        <?php foreach ($groupedEntries as $localeSection): ?>
            <?php
            $localeCode = trim((string) ($localeSection['locale'] ?? ''));
            $localeLabel = trim((string) ($localeSection['label'] ?? $localeCode));
            $sections = is_array($localeSection['sections'] ?? null) ? $localeSection['sections'] : [];
            ?>
            <article class="card sitemap-public-locale-card">
                <div class="card-body sitemap-public-locale-body">
                    <div class="sitemap-public-locale-head">
                        <h2 class="card-title"><?= e(__('sitemap_locale_heading', 'SiteMap', ['locale' => $localeLabel])) ?></h2>
                        <span class="badge"><?= e($localeCode) ?></span>
                    </div>

                    <div class="sitemap-public-grid">
                        <?php foreach ($sections as $section): ?>
                            <?php
                            $items = is_array($section['items'] ?? null) ? $section['items'] : [];
                            if ($items === []) {
                                continue;
                            }
                            ?>
                            <section class="sitemap-public-section card">
                                <div class="card-body sitemap-public-section-body">
                                    <h3 class="card-title sitemap-public-section-title"><?= e((string) ($section['label'] ?? '')) ?></h3>
                                    <ul class="sitemap-public-links">
                                        <?php foreach ($items as $item): ?>
                                            <li>
                                                <a href="<?= e((string) ($item['loc'] ?? '')) ?>">
                                                    <?= e((string) ($item['label'] ?? '')) ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
