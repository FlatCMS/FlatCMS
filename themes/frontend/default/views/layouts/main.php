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
<html lang="<?= $locale ?>" dir="<?= text_direction() ?>">
<?php include __DIR__ . '/../partials/head.php'; ?>
<body id="flatcms">
    <!-- Promo Banner -->
    <?php $promoBannerSlot = 'above_topbar'; ?>
    <?php include __DIR__ . '/../partials/promo-banner.php'; ?>

    <!-- Header -->
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <?php $promoBannerSlot = 'below_topbar'; ?>
    <?php include __DIR__ . '/../partials/promo-banner.php'; ?>

    <!-- Flash Messages -->
    <?php if (!empty($flash['success']) || !empty($flash['error']) || !empty($flash['warning']) || !empty($frontendNotice['message'])): ?>
        <div class="container flash-container">
            <?php if (!empty($flash['success'])): ?>
                <div class="alert alert-success"><?= e($flash['success']) ?></div>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <div class="alert alert-error"><?= e($flash['error']) ?></div>
            <?php endif; ?>
            <?php if (!empty($flash['warning'])): ?>
                <div class="alert alert-warning"><?= e($flash['warning']) ?></div>
            <?php endif; ?>
            <?php if (!empty($frontendNotice['message'])): ?>
                <div class="alert alert-warning"><?= e((string) $frontendNotice['message']) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="site-main">
        <?= $content ?>
    </main>

    <?php $promoBannerSlot = 'above_footer'; ?>
    <?php include __DIR__ . '/../partials/promo-banner.php'; ?>

    <!-- Footer -->
    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <?php $promoBannerSlot = 'below_footer'; ?>
    <?php include __DIR__ . '/../partials/promo-banner.php'; ?>

    <?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
