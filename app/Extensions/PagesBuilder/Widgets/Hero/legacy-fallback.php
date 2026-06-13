<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$pagesBuilderPage = is_array($pagesBuilderPage ?? null) ? $pagesBuilderPage : [];
$pagesBuilderState = is_array($pagesBuilderState ?? null) ? $pagesBuilderState : [];
$pagesBuilderContentHtml = (string) ($pagesBuilderContentHtml ?? '');

$eyebrow = trim((string) ($pagesBuilderState['eyebrow'] ?? ''));
$intro = trim((string) ($pagesBuilderState['intro'] ?? ''));
$highlightTitle = trim((string) ($pagesBuilderState['highlight_title'] ?? ''));
$highlightBody = trim((string) ($pagesBuilderState['highlight_body'] ?? ''));
$ctaLabel = trim((string) ($pagesBuilderState['cta_label'] ?? ''));
$ctaUrl = trim((string) ($pagesBuilderState['cta_url'] ?? ''));
$pageTitle = trim((string) ($pagesBuilderPage['title'] ?? ''));
?>

<div class="content-builder">
    <section class="card card-elevated page-builder-hero-shell">
        <div class="card-body">
            <?php if ($eyebrow !== ''): ?>
                <p class="text-muted text-sm"><?= e($eyebrow) ?></p>
            <?php endif; ?>
            <?php if ($pageTitle !== ''): ?>
                <h1><?= e($pageTitle) ?></h1>
            <?php endif; ?>
            <?php if ($intro !== ''): ?>
                <p><?= nl2br(e($intro)) ?></p>
            <?php endif; ?>
            <?php if ($ctaLabel !== '' && $ctaUrl !== ''): ?>
                <p>
                    <a href="<?= e($ctaUrl) ?>" class="btn btn-primary"><?= e($ctaLabel) ?></a>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($highlightTitle !== '' || $highlightBody !== ''): ?>
        <section class="card page-builder-highlight-shell">
            <div class="card-body">
                <?php if ($highlightTitle !== ''): ?>
                    <h2><?= e($highlightTitle) ?></h2>
                <?php endif; ?>
                <?php if ($highlightBody !== ''): ?>
                    <p><?= nl2br(e($highlightBody)) ?></p>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="page-builder-classic-content prose">
        <?= $pagesBuilderContentHtml ?>
    </section>
</div>
