<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>
<script src="<?= theme_asset('js/main.js', 'frontend') ?>"></script>
<?php
$frontendFooterAssetsHtml = \App\Core\HookAssets::render('frontend.assets.footer', [
    'settings' => is_array($settings ?? null) ? $settings : [],
    'locale' => $locale ?? locale(),
    'page' => $page ?? null,
    'post' => $post ?? null,
]);
?>
<?= $frontendFooterAssetsHtml !== '' ? $frontendFooterAssetsHtml . PHP_EOL : '' ?>
