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
<html lang="<?= e(locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(__('error.server', 'Core')) ?></title>
</head>
<body>
    <main>
        <h1><?= e(__('error.server', 'Core')) ?></h1>
        <p><?= htmlspecialchars($siteName ?? __('app_name', 'Core'), ENT_QUOTES, 'UTF-8') ?></p>
        <p><?= e(__('warning', 'Core')) ?></p>
    </main>
</body>
</html>
