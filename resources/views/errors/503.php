<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$siteName = $siteName ?? 'FlatCMS';
?>
<!DOCTYPE html>
<html lang="<?= locale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title><?= e(__('maintenance_title', 'Core')) ?> - <?= htmlspecialchars($siteName) ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/errors/error-503.css') ?>">
</head>
<body>
    <div class="maintenance-container">
        <div class="gear-icon">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm9 3.5a7.3 7.3 0 0 0-.12-1.3l2.02-1.57-2-3.46-2.41.86a7.8 7.8 0 0 0-2.24-1.3l-.36-2.54h-4l-.36 2.54c-.8.3-1.55.73-2.24 1.3l-2.41-.86-2 3.46 2.02 1.57a7.3 7.3 0 0 0 0 2.6L2.03 15l2 3.46 2.41-.86c.69.57 1.44 1 2.24 1.3l.36 2.54h4l.36-2.54c.8-.3 1.55-.73 2.24-1.3l2.41.86 2-3.46-2.02-1.57c.08-.42.12-.85.12-1.3Z"/>
            </svg>
        </div>

        <h1><?= e(__('maintenance_title', 'Core')) ?></h1>

        <p class="description">
            <?= e(__('maintenance_description', 'Core')) ?>
        </p>

        <div class="progress-container">
            <div class="progress-label"><?= e(__('maintenance_in_progress', 'Core')) ?></div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>

        <div class="refresh-info">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M21 12a9 9 0 0 1-15.3 6.36l1.76-1.77A6.5 6.5 0 1 0 6.5 7.5h1.75L5 4.25 1.75 7.5H3.5A8.5 8.5 0 1 1 21 12Z"/>
            </svg>
            <span><?= e(__('maintenance_autorefresh', 'Core')) ?></span>
        </div>
    </div>

    <div class="footer">
        &copy; <?= date('Y') ?> <span class="site-name"><?= htmlspecialchars($siteName) ?></span>
    </div>
</body>
</html>
