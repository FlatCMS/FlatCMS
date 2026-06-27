<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$flatcmsEditorTruthy = static function ($value): bool {
    return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
};

$flatcmsTinyMceEnabled = $flatcmsEditorTruthy(env('TINYMCE_ENABLED', '0'));
$flatcmsWysiwygProvider = $flatcmsTinyMceEnabled ? 'tinymce' : 'suneditor';
?>

<!DOCTYPE html>
<html lang="<?= $locale ?>" dir="<?= text_direction() ?>">

<?php include __DIR__ . '/../partials/head.php'; ?>

<body id="flatcms" class="admin-body" data-theme="modern-pro" data-wysiwyg-provider="<?= e($flatcmsWysiwygProvider) ?>">
<?php
$adminBodyStartHtml = \App\Core\HookSlots::render('admin.layout.body_start', [
    'settings' => \App\Core\FlatFile::settings(),
    'locale' => $locale,
    'auth_user' => $auth_user ?? null,
]);
?>
<?= $adminBodyStartHtml !== '' ? $adminBodyStartHtml . PHP_EOL : '' ?>
    <div class="admin-layout">
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/../partials/topbar.php'; ?>

            <div class="page-content" data-tour="content">
                <?php include __DIR__ . '/../partials/flash.php'; ?>

                <?= $content ?>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../partials/modals.php'; ?>

<?php
$adminBodyEndHtml = \App\Core\HookSlots::render('admin.layout.body_end', [
    'settings' => \App\Core\FlatFile::settings(),
    'locale' => $locale,
    'auth_user' => $auth_user ?? null,
]);
?>
<?= $adminBodyEndHtml !== '' ? $adminBodyEndHtml . PHP_EOL : '' ?>
    <?php include __DIR__ . '/../partials/scripts.php'; ?>
</body>
</html>
