<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

$bootJson = json_encode(
    $boot,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
);
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>" dir="<?= text_direction() ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($csrf_token) ?>">
    <title><?= e($pageTitle) ?> - <?= e(__('app_name', 'Core')) ?></title>
    <?php foreach (($headScriptUrls ?? []) as $headScriptUrl): ?>
        <script src="<?= e($headScriptUrl) ?>"></script>
    <?php endforeach; ?>
    <?php foreach (($headStyleUrls ?? []) as $headStyleUrl): ?>
        <link rel="stylesheet" href="<?= e($headStyleUrl) ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= e($stylesUrl) ?>">
</head>
<body class="sfc-studio-body">
    <div id="sfc-studio-app" class="sfc-studio-app">
        <header class="sfc-studio-topbar" data-tour-target="studio-flatcms-topbar">
            <div class="sfc-studio-topbar-left">
                <a href="<?= e(url('/admin')) ?>" class="sfc-studio-back"><?= e(__('studio_flatcms_back_admin', 'StudioFlatCMS')) ?></a>
                <span class="sfc-studio-badge"><?= e(__('studio_flatcms_title', 'StudioFlatCMS')) ?></span>
            </div>

            <div class="sfc-studio-topbar-center">
                <div class="sfc-studio-mode-switch" role="tablist" aria-label="<?= e(__('studio_flatcms_mode_aria', 'StudioFlatCMS')) ?>">
                    <button type="button" class="sfc-studio-mode-btn" data-action="switch-mode" data-mode="compose"><?= e(__('studio_flatcms_mode_compose', 'StudioFlatCMS')) ?></button>
                    <button type="button" class="sfc-studio-mode-btn" data-action="switch-mode" data-mode="theme"><?= e(__('studio_flatcms_mode_theme', 'StudioFlatCMS')) ?></button>
                </div>

                <div class="sfc-studio-viewport-group" role="group" aria-label="<?= e(__('studio_flatcms_viewports_aria', 'StudioFlatCMS')) ?>">
                    <button type="button" class="sfc-studio-viewport-btn" data-action="viewport" data-viewport="desktop" aria-label="<?= e(__('studio_flatcms_viewport_desktop', 'StudioFlatCMS')) ?>">
                        <i class="fa-solid fa-desktop" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="sfc-studio-viewport-btn" data-action="viewport" data-viewport="tablet" aria-label="<?= e(__('studio_flatcms_viewport_tablet', 'StudioFlatCMS')) ?>">
                        <i class="fa-solid fa-tablet-screen-button" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="sfc-studio-viewport-btn" data-action="viewport" data-viewport="mobile" aria-label="<?= e(__('studio_flatcms_viewport_mobile', 'StudioFlatCMS')) ?>">
                        <i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="sfc-studio-viewport-meta" aria-live="polite">
                    <span id="sfc-studio-viewport-label"><?= e(__('studio_flatcms_viewport_desktop', 'StudioFlatCMS')) ?></span>
                    <span id="sfc-studio-viewport-size">1180px</span>
                </div>

                <label class="sfc-studio-zoom-wrap">
                    <span class="sr-only"><?= e(__('studio_flatcms_zoom_aria', 'StudioFlatCMS')) ?></span>
                    <select id="sfc-studio-zoom" class="sfc-studio-zoom" data-action="zoom">
                        <option value="50">50%</option>
                        <option value="67">67%</option>
                        <option value="75">75%</option>
                        <option value="90">90%</option>
                        <option value="100" selected>100%</option>
                        <option value="110">110%</option>
                        <option value="125">125%</option>
                        <option value="150">150%</option>
                    </select>
                </label>
            </div>

            <div class="sfc-studio-topbar-right">
                <button type="button" class="sfc-btn sfc-btn-ghost" data-action="preview"><?= e(__('studio_flatcms_preview_button', 'StudioFlatCMS')) ?></button>
                <button type="button" class="sfc-btn sfc-btn-primary" data-action="save"><?= e(__('studio_flatcms_save_button', 'StudioFlatCMS')) ?></button>
            </div>
        </header>

        <aside class="sfc-studio-rail" data-tour-target="studio-flatcms-rail" aria-label="<?= e(__('studio_flatcms_rail_aria', 'StudioFlatCMS')) ?>">
            <button type="button" class="sfc-studio-rail-btn" data-action="toggle-drawer" data-drawer="structure" aria-label="<?= e(__('studio_flatcms_drawer_structure_title', 'StudioFlatCMS')) ?>">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
            </button>
            <button type="button" class="sfc-studio-rail-btn" data-action="toggle-drawer" data-drawer="elements" aria-label="<?= e(__('studio_flatcms_drawer_elements_title', 'StudioFlatCMS')) ?>">
                <i class="fa-solid fa-cubes" aria-hidden="true"></i>
            </button>
            <button type="button" class="sfc-studio-rail-btn" data-action="toggle-drawer" data-drawer="shell" aria-label="<?= e(__('studio_flatcms_drawer_shell_title', 'StudioFlatCMS')) ?>">
                <i class="fa-solid fa-window-maximize" aria-hidden="true"></i>
            </button>
            <button type="button" class="sfc-studio-rail-btn sfc-studio-rail-btn-bottom" data-action="toggle-drawer" data-drawer="page" aria-label="<?= e(__('studio_flatcms_drawer_page_title', 'StudioFlatCMS')) ?>">
                <i class="fa-solid fa-sliders" aria-hidden="true"></i>
            </button>
        </aside>

        <aside id="sfc-studio-drawer" class="sfc-studio-drawer" aria-label="<?= e(__('studio_flatcms_drawer_aria', 'StudioFlatCMS')) ?>">
            <div class="sfc-studio-panel-head">
                <div>
                    <div id="sfc-studio-drawer-title" class="sfc-studio-panel-title"><?= e(__('studio_flatcms_drawer_structure_title', 'StudioFlatCMS')) ?></div>
                    <div id="sfc-studio-drawer-subtitle" class="sfc-studio-panel-subtitle"><?= e(__('studio_flatcms_drawer_structure_subtitle', 'StudioFlatCMS')) ?></div>
                </div>
                <button type="button" class="sfc-studio-icon-btn" data-action="close-drawer" aria-label="<?= e(__('studio_flatcms_close', 'StudioFlatCMS')) ?>">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div id="sfc-studio-drawer-body" class="sfc-studio-panel-body"></div>
        </aside>

        <main class="sfc-studio-main" data-tour-target="studio-flatcms-canvas">
            <div class="sfc-studio-canvas-shell">
                <div class="sfc-studio-canvas-scroll">
                    <div id="sfc-studio-stage-wrap" class="sfc-studio-stage-wrap">
                        <div id="sfc-studio-stage" class="sfc-studio-stage" data-action="select-page"></div>
                        <div id="sfc-studio-inline-editor-root" class="sfc-studio-inline-editor-root" aria-hidden="true"></div>
                    </div>
                </div>
            </div>
        </main>

        <aside id="sfc-studio-inspector" class="sfc-studio-inspector" data-tour-target="studio-flatcms-inspector" aria-label="<?= e(__('studio_flatcms_inspector_aria', 'StudioFlatCMS')) ?>">
            <div class="sfc-studio-panel-head">
                <div>
                    <div class="sfc-studio-panel-title"><?= e(__('studio_flatcms_inspector_title', 'StudioFlatCMS')) ?></div>
                    <div id="sfc-studio-selection-name" class="sfc-studio-panel-subtitle"><?= e(__('studio_flatcms_empty_selection', 'StudioFlatCMS')) ?></div>
                </div>
                <button type="button" class="sfc-studio-icon-btn" data-action="close-inspector" aria-label="<?= e(__('studio_flatcms_close', 'StudioFlatCMS')) ?>">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div id="sfc-studio-inspector-tabs" class="sfc-studio-inspector-tabs"></div>
            <div id="sfc-studio-inspector-body" class="sfc-studio-panel-body"></div>
        </aside>

        <button
            type="button"
            id="sfc-studio-inspector-handle"
            class="sfc-studio-inspector-handle"
            data-action="open-inspector"
            aria-label="<?= e(__('studio_flatcms_inspector_title', 'StudioFlatCMS')) ?>"
        >
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>

        <div id="sfc-studio-toast-root" class="sfc-studio-toast-root" aria-live="polite"></div>
        <template id="sfc-studio-boot"><?= $bootJson ?></template>
        <noscript><?= e(__('studio_flatcms_noscript', 'StudioFlatCMS')) ?></noscript>
    </div>

    <?php if (!empty($mediaEnabled) && is_file(BASE_PATH . '/app/Modules/Media/Views/admin/partials/media-modal.php')): ?>
        <?php include BASE_PATH . '/app/Modules/Media/Views/admin/partials/media-modal.php'; ?>
    <?php endif; ?>

    <?php foreach (($scriptUrls ?? []) as $scriptUrl): ?>
        <script src="<?= e($scriptUrl) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
