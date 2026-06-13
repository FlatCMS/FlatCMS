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
<html lang="<?= locale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('error.not_found', 'Core') ?></title>
    <link rel="stylesheet" href="<?= url('/assets/css/errors/error-404.css') ?>">
</head>
<body>
    <div class="error-page">
        <h1>404</h1>
        <p><?= __('error.not_found', 'Core') ?></p>
        <a href="<?= url('/') ?>"><?= __('home', 'Core') ?></a>
    </div>
</body>
</html>
