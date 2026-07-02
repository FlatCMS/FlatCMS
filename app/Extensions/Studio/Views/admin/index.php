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
    <title><?= e($pageTitle) ?></title>
    <?php foreach (($headStyleUrls ?? []) as $headStyleUrl): ?>
        <link rel="stylesheet" href="<?= e($headStyleUrl) ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="<?= e($stylesUrl) ?>">
</head>
<body class="studio-admin-body admin-body">
    <div id="flatcms-studio">
        <div class="studio-shell studio-workbench">
            <div class="studio-overlay" data-action="close-drawer" aria-hidden="true"></div>

            <header class="studio-topbar">
                <div class="studio-top-left">
                    <a class="studio-back" href="<?= e(url('/admin')) ?>"><?= e(__('studio_back_admin', 'Studio')) ?></a>
                </div>
                <div class="studio-top-center">
                    <div class="studio-view-modes" role="tablist" aria-label="<?= e(__('studio_canvas_modes_aria', 'Studio')) ?>">
                        <button class="studio-btn" type="button" data-action="switch-canvas-mode" data-mode="compose"><?= e(__('studio_canvas_mode_compose', 'Studio')) ?></button>
                        <button class="studio-btn" type="button" data-action="switch-canvas-mode" data-mode="render"><?= e(__('studio_canvas_mode_render', 'Studio')) ?></button>
                    </div>
                    <span class="studio-top-divider" aria-hidden="true"></span>
                    <div class="studio-preview-cluster" role="group" aria-label="<?= e(__('studio_viewports_aria', 'Studio')) ?>">
                        <div class="studio-viewport-strip" role="group" aria-label="<?= e(__('studio_viewports_aria', 'Studio')) ?>">
                            <button class="studio-viewport-btn" type="button" data-action="viewport" data-viewport="desktop" title="<?= e(__('studio_viewport_desktop', 'Studio')) ?>" aria-label="<?= e(__('studio_viewport_desktop', 'Studio')) ?>">
                                <i class="fa-solid fa-desktop studio-viewport-icon" aria-hidden="true"></i>
                            </button>
                            <button class="studio-viewport-btn" type="button" data-action="viewport" data-viewport="tablet" title="<?= e(__('studio_viewport_tablet', 'Studio')) ?>" aria-label="<?= e(__('studio_viewport_tablet', 'Studio')) ?>">
                                <i class="fa-solid fa-tablet-screen-button studio-viewport-icon" aria-hidden="true"></i>
                            </button>
                            <button class="studio-viewport-btn" type="button" data-action="viewport" data-viewport="mobile" title="<?= e(__('studio_viewport_mobile', 'Studio')) ?>" aria-label="<?= e(__('studio_viewport_mobile', 'Studio')) ?>">
                                <i class="fa-solid fa-mobile-screen-button studio-viewport-icon" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="studio-preview-meta" aria-live="polite">
                            <div id="studio-viewport-label" class="studio-top-readout studio-top-readout-muted"><?= e(__('studio_viewport_desktop', 'Studio')) ?></div>
                            <div id="studio-viewport-size" class="studio-top-readout">1180px</div>
                        </div>
                        <label class="studio-zoom-control" aria-label="<?= e(__('studio_zoom_aria', 'Studio')) ?>">
                            <i class="fa-solid fa-magnifying-glass-plus studio-zoom-control-icon" aria-hidden="true"></i>
                            <select id="studio-zoom-select" class="studio-zoom-select" data-action="zoom" aria-label="<?= e(__('studio_zoom_aria', 'Studio')) ?>">
                                <option value="50">50%</option>
                                <option value="67">67%</option>
                                <option value="75">75%</option>
                                <option value="90">90%</option>
                                <option value="100" selected>100%</option>
                                <option value="110">110%</option>
                                <option value="125">125%</option>
                                <option value="150">150%</option>
                            </select>
                            <i class="fa-solid fa-chevron-down studio-zoom-caret" aria-hidden="true"></i>
                        </label>
                    </div>
                </div>
                <div class="studio-top-actions">
                    <button class="studio-btn" type="button" data-action="preview"><?= e(__('studio_preview_button', 'Studio')) ?></button>
                    <button class="studio-btn studio-btn-primary" type="button" data-action="save"><?= e(__('studio_save_button', 'Studio')) ?></button>
                </div>
            </header>

            <aside class="studio-rail" aria-label="<?= e(__('studio_rail_aria', 'Studio')) ?>">
                <button class="studio-rail-btn" type="button" title="<?= e(__('studio_drawer_sections_title', 'Studio')) ?>" data-action="toggle-drawer" data-drawer="sections">
                    <span>＋</span>
                </button>
                <button class="studio-rail-btn" type="button" title="<?= e(__('studio_drawer_blocks_title', 'Studio')) ?>" data-action="toggle-drawer" data-drawer="blocks">
                    <span>▣</span>
                </button>
                <button class="studio-rail-btn" type="button" title="<?= e(__('studio_drawer_menu_title', 'Studio')) ?>" data-action="toggle-drawer" data-drawer="menu">
                    <span>☰</span>
                </button>
                <button class="studio-rail-btn" type="button" title="<?= e(__('studio_drawer_plugins_title', 'Studio')) ?>" data-action="toggle-drawer" data-drawer="plugins">
                    <span>⌘</span>
                </button>
                <button class="studio-rail-btn studio-rail-bottom" type="button" title="<?= e(__('studio_drawer_page_title', 'Studio')) ?>" data-action="toggle-drawer" data-drawer="page">
                    <span>⚙</span>
                </button>
            </aside>

            <aside id="studio-drawer" class="studio-drawer" aria-label="<?= e(__('studio_drawer_aria', 'Studio')) ?>">
                <div class="studio-drawer-head">
                    <div>
                        <strong id="studio-drawer-title"><?= e(__('studio_drawer_sections_title', 'Studio')) ?></strong>
                        <span id="studio-drawer-subtitle"><?= e(__('studio_drawer_sections_subtitle', 'Studio')) ?></span>
                    </div>
                    <button class="studio-icon-btn" type="button" data-action="close-drawer" aria-label="<?= e(__('studio_close', 'Studio')) ?>">×</button>
                </div>
                <div id="studio-drawer-content" class="studio-drawer-content"></div>
            </aside>

            <main class="studio-main">
                <div class="studio-canvas-wrap">
                    <div id="studio-stage-shell" class="studio-stage-shell">
                        <div id="studio-stage" class="studio-stage" data-action="select-page" data-drop-zone="page"></div>
                    </div>
                </div>
            </main>

            <aside id="studio-inspector-panel" class="studio-inspector" aria-label="<?= e(__('studio_inspector_aria', 'Studio')) ?>">
                <div class="studio-inspector-head">
                    <div>
                        <strong id="studio-inspector-title"><?= e(__('studio_inspector_title', 'Studio')) ?></strong>
                        <span id="studio-inspector-subtitle"><?= e(__('studio_empty_selection', 'Studio')) ?></span>
                    </div>
                    <button class="studio-icon-btn" type="button" data-action="close-inspector" aria-label="<?= e(__('studio_close', 'Studio')) ?>">×</button>
                </div>
                <div id="studio-inspector-content" class="studio-inspector-content"></div>
            </aside>
        </div>

        <template id="flatcms-studio-boot"><?= $bootJson ?></template>
        <noscript><?= e(__('studio_noscript', 'Studio')) ?></noscript>
    </div>

    <?php if (!empty($mediaEnabled) && is_file(BASE_PATH . '/app/Modules/Media/Views/admin/partials/media-modal.php')): ?>
        <?php include BASE_PATH . '/app/Modules/Media/Views/admin/partials/media-modal.php'; ?>
    <?php endif; ?>

    <?php foreach ($scriptUrls as $scriptUrl): ?>
        <script src="<?= e($scriptUrl) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
