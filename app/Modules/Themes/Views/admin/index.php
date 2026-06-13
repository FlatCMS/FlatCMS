<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<?php
/**
 * View variables provided by controller/layout.
 *
 * @var array $frontendThemes
 * @var array $adminThemes
 * @var string $pageTitle
 * @var string|bool $activeFrontend
 * @var string|bool $activeAdmin
 */
$themesCssVersion = file_exists(BASE_PATH . '/app/Modules/Themes/Assets/css/themes-module.css')
    ? (string) filemtime(BASE_PATH . '/app/Modules/Themes/Assets/css/themes-module.css')
    : '';
$themesJsVersion = file_exists(BASE_PATH . '/app/Modules/Themes/Assets/js/themes-index.js')
    ? (string) filemtime(BASE_PATH . '/app/Modules/Themes/Assets/js/themes-index.js')
    : '';
?>
<link rel="stylesheet" href="<?= module_asset('Themes', 'css/themes-module.css') ?><?= $themesCssVersion !== '' ? '?v=' . rawurlencode($themesCssVersion) : '' ?>">

<?php
function theme_slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9\\s_-]/', '', $value) ?? '';
    $value = preg_replace('/\\s+/', '-', $value) ?? '';
    return trim($value, '-');
}

function theme_category_label(array $theme): string
{
    $category = $theme['category'] ?? '';
    if (is_array($theme['categories'] ?? null) && !empty($theme['categories'])) {
        $category = (string) ($theme['categories'][0] ?? $category);
    }
    return trim((string) $category);
}

function theme_supports_customization(array $theme): bool
{
    $supports = $theme['supports'] ?? null;
    if (is_array($supports) && array_key_exists('customization', $supports)) {
        return (bool) $supports['customization'];
    }

    $themeColors = $theme['colors'] ?? null;
    if (is_array($themeColors) && $themeColors !== []) {
        return true;
    }

    $features = $theme['features'] ?? null;
    if (is_array($features)) {
        foreach ($features as $feature) {
            if (in_array((string) $feature, ['customization', 'theme-customization'], true)) {
                return true;
            }
        }
    }

    return false;
}

function theme_color_scheme(array $theme): string
{
    $background = $theme['colors']['background'] ?? '';
    $background = ltrim((string) $background, '#');
    if (strlen($background) === 3) {
        $background = $background[0] . $background[0] . $background[1] . $background[1] . $background[2] . $background[2];
    }
    if (strlen($background) !== 6) {
        return '';
    }
    $r = hexdec(substr($background, 0, 2));
    $g = hexdec(substr($background, 2, 2));
    $b = hexdec(substr($background, 4, 2));
    $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    return $luminance < 0.5 ? 'dark' : 'light';
}

$categoryOptions = [];
foreach (['frontend' => $frontendThemes, 'admin' => $adminThemes] as $type => $themes) {
    foreach ($themes as $name => $theme) {
        $label = theme_category_label($theme);
        if ($label !== '') {
            $slug = theme_slugify($label);
            if ($slug !== '') {
                $categoryOptions[$slug] = $label;
            }
        }
    }
}
ksort($categoryOptions);
?>

<div class="page-header">
    <h1 class="page-title"><?= e($pageTitle) ?></h1>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-palette"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('themes_help_badge', 'Themes') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('themes_help_title', 'Themes') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('themes_help_intro', 'Themes') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('themes_help_step_install', 'Themes') ?></li>
            <li><?= __('themes_help_step_activate', 'Themes') ?></li>
            <li><?= __('themes_help_step_cleanup', 'Themes') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="#themesInstallerCard" class="btn btn-primary"><?= __('themes_help_action_install', 'Themes') ?></a>
            <a href="#themesFrontendSection" class="btn btn-secondary"><?= __('themes_help_action_frontend', 'Themes') ?></a>
        </div>
    </div>
</div>

<div id="themesToolbar" class="themes-toolbar">
    <div class="themes-filter-group">
        <button type="button" class="btn btn-sm btn-ghost" data-theme-status="all"><?= __('themes_filter_all', 'Themes') ?></button>
        <button type="button" class="btn btn-sm btn-ghost is-active" data-theme-status="active"><?= __('themes_filter_active', 'Themes') ?></button>
        <button type="button" class="btn btn-sm btn-ghost" data-theme-status="inactive"><?= __('themes_filter_inactive', 'Themes') ?></button>
    </div>
    <div class="themes-filter-group themes-filter-group-right">
        <div class="themes-filter-field">
            <label class="form-label" for="themeTypeFilter"><?= __('themes_filter_type', 'Themes') ?></label>
            <select id="themeTypeFilter" class="form-select">
                <option value=""><?= __('themes_filter_type_all', 'Themes') ?></option>
                <option value="frontend"><?= __('themes_filter_type_frontend', 'Themes') ?></option>
                <option value="admin"><?= __('themes_filter_type_admin', 'Themes') ?></option>
            </select>
        </div>
        <div class="themes-filter-field">
            <label class="form-label" for="themeCategoryFilter"><?= __('themes_filter_category', 'Themes') ?></label>
            <select id="themeCategoryFilter" class="form-select">
                <option value=""><?= __('themes_filter_category_all', 'Themes') ?></option>
                <?php foreach ($categoryOptions as $slug => $label): ?>
                    <option value="<?= e($slug) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="themes-filter-field">
            <label class="form-label" for="themeColorFilter"><?= __('themes_filter_color', 'Themes') ?></label>
            <select id="themeColorFilter" class="form-select">
                <option value=""><?= __('themes_filter_color_all', 'Themes') ?></option>
                <option value="dark"><?= __('themes_filter_color_dark', 'Themes') ?></option>
                <option value="light"><?= __('themes_filter_color_light', 'Themes') ?></option>
            </select>
        </div>
        <div class="themes-filter-field">
            <label class="form-label" for="themePriceFilter"><?= __('themes_filter_price', 'Themes') ?></label>
            <select id="themePriceFilter" class="form-select">
                <option value=""><?= __('themes_filter_price_all', 'Themes') ?></option>
                <option value="free"><?= __('themes_filter_price_free', 'Themes') ?></option>
                <option value="paid"><?= __('themes_filter_price_paid', 'Themes') ?></option>
            </select>
        </div>
        <div class="themes-filter-field themes-filter-search">
            <label class="form-label" for="themeSearchInput"><?= __('themes_filter_search', 'Themes') ?></label>
            <input type="text" id="themeSearchInput" class="form-input" placeholder="<?= __('themes_filter_search_placeholder', 'Themes') ?>">
        </div>
    </div>
</div>

<div class="card theme-installer-card" id="themesInstallerCard">
    <div class="card-header">
        <h3 class="card-title"><?= __('themes_installer_title', 'Themes') ?></h3>
        <span class="module-installer-hint"><?= __('themes_installer_hint', 'Themes') ?></span>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/themes/install') ?>" enctype="multipart/form-data" class="module-installer-form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="theme_zip"><?= __('themes_installer_file', 'Themes') ?></label>
                <input type="file" name="theme_zip" id="theme_zip" class="form-input" accept=".zip" required>
                <span class="form-hint"><?= __('themes_installer_hint_file', 'Themes') ?></span>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-cloud-upload-alt"></i>
                <?= __('themes_installer_action', 'Themes') ?>
            </button>
        </form>
    </div>
</div>

<!-- Frontend Themes -->
<div class="card" id="themesFrontendSection" data-theme-section data-theme-section-type="frontend">
    <div class="card-header">
        <h3 class="card-title"><?= __('frontend_themes', 'Themes') ?></h3>
    </div>
    
    <?php if (empty($frontendThemes)): ?>
        <div class="card-body">
            <div class="admin-empty-state-panel">
                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                    <i class="fas fa-paint-roller"></i>
                </div>
                <h2 class="admin-empty-state-panel__title"><?= __('themes_empty_title', 'Themes') ?></h2>
                <p class="admin-empty-state-panel__text"><?= __('themes_empty_text', 'Themes') ?></p>
                <div class="admin-empty-state-panel__actions">
                    <a href="#themesInstallerCard" class="btn btn-primary"><?= __('themes_empty_action_install', 'Themes') ?></a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="themes-grid">
            <?php foreach ($frontendThemes as $name => $theme): ?>
                <?php
                    $status = $name === $activeFrontend ? 'active' : 'inactive';
                    $categoryLabel = theme_category_label($theme);
                    $categorySlug = $categoryLabel !== '' ? theme_slugify($categoryLabel) : '';
                    $colorScheme = theme_color_scheme($theme);
                    $supportsCustomization = theme_supports_customization($theme);
                    $searchValue = strtolower(trim(
                        ($theme['name'] ?? $name) . ' ' .
                        ($theme['description'] ?? '') . ' ' .
                        ($theme['author'] ?? '') . ' ' .
                        ($theme['version'] ?? '') . ' ' .
                        $name . ' frontend'
                    ));
                ?>
                <div class="theme-card <?= $name === $activeFrontend ? 'theme-active' : '' ?>" data-theme-card data-status="<?= e($status) ?>" data-type="frontend" data-category="<?= e($categorySlug) ?>" data-color="<?= e($colorScheme) ?>" data-search="<?= e($searchValue) ?>">
                    <div class="theme-screenshot">
                        <?php if ($theme['screenshot']): ?>
                            <img src="<?= url($theme['screenshot']) ?>" alt="<?= e($theme['name']) ?>">
                        <?php else: ?>
                            <div class="theme-placeholder">
                                <i class="fas fa-palette"></i>
                            </div>
                        <?php endif; ?>
                        <?php if ($name === $activeFrontend): ?>
                            <span class="theme-badge"><?= __('active', 'Themes') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="theme-info">
                        <h4><?= e($theme['name']) ?></h4>
                        <p class="theme-meta"><?= __('version', 'Themes') ?>: <?= e($theme['version']) ?> | <?= __('author', 'Themes') ?> : <?= e($theme['author']) ?></p>
                        <?php if (!empty($theme['description'])): ?>
                            <p class="theme-desc"><?= e($theme['description']) ?></p>
                        <?php endif; ?>
                        <div class="theme-actions theme-actions-compact">
                            <?php if ($name !== $activeFrontend): ?>
                                <form action="<?= url("/admin/themes/activate/frontend/{$name}") ?>" method="POST" class="form-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-primary btn-sm"><?= __('activate', 'Themes') ?></button>
                                </form>
                                <form action="<?= url("/admin/themes/trash/frontend/{$name}") ?>" method="POST" class="form-inline">
                                    <?= csrf_field() ?>
                                    <button
                                        type="submit"
                                        class="btn btn-outline btn-sm"
                                        data-action="confirm-delete"
                                        data-message="<?= e(__('theme_move_to_trash_confirm', 'Themes')) ?>"
                                        data-item-name="<?= e((string) ($theme['name'] ?? $name)) ?>"
                                    >
                                        <?= __('theme_move_to_trash', 'Themes') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($supportsCustomization): ?>
                                <a href="<?= url("/admin/themes/frontend/{$name}/customize") ?>" class="btn btn-secondary btn-sm"><?= __('customize', 'Themes') ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Admin Themes -->
<div class="card theme-section-spacer" data-theme-section data-theme-section-type="admin">
    <div class="card-header">
        <h3 class="card-title"><?= __('admin_themes', 'Themes') ?></h3>
    </div>
    
    <?php if (empty($adminThemes)): ?>
        <div class="card-body">
            <div class="admin-empty-state-panel">
                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                    <i class="fas fa-swatchbook"></i>
                </div>
                <h2 class="admin-empty-state-panel__title"><?= __('themes_empty_title', 'Themes') ?></h2>
                <p class="admin-empty-state-panel__text"><?= __('themes_empty_text', 'Themes') ?></p>
                <div class="admin-empty-state-panel__actions">
                    <a href="#themesInstallerCard" class="btn btn-primary"><?= __('themes_empty_action_install', 'Themes') ?></a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="themes-grid">
            <?php foreach ($adminThemes as $name => $theme): ?>
                <?php
                    $status = $name === $activeAdmin ? 'active' : 'inactive';
                    $categoryLabel = theme_category_label($theme);
                    $categorySlug = $categoryLabel !== '' ? theme_slugify($categoryLabel) : '';
                    $colorScheme = theme_color_scheme($theme);
                    $supportsCustomization = theme_supports_customization($theme);
                    $searchValue = strtolower(trim(
                        ($theme['name'] ?? $name) . ' ' .
                        ($theme['description'] ?? '') . ' ' .
                        ($theme['author'] ?? '') . ' ' .
                        ($theme['version'] ?? '') . ' ' .
                        $name . ' admin'
                    ));
                ?>
                <div class="theme-card <?= $name === $activeAdmin ? 'theme-active' : '' ?>" data-theme-card data-status="<?= e($status) ?>" data-type="admin" data-category="<?= e($categorySlug) ?>" data-color="<?= e($colorScheme) ?>" data-search="<?= e($searchValue) ?>">
                    <div class="theme-screenshot">
                        <?php if ($theme['screenshot']): ?>
                            <img src="<?= url($theme['screenshot']) ?>" alt="<?= e($theme['name']) ?>">
                        <?php else: ?>
                            <div class="theme-placeholder">
                                <i class="fas fa-palette"></i>
                            </div>
                        <?php endif; ?>
                        <?php if ($name === $activeAdmin): ?>
                            <span class="theme-badge"><?= __('active', 'Themes') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="theme-info">
                        <h4><?= e($theme['name']) ?></h4>
                        <p class="theme-meta"><?= __('version', 'Themes') ?>: <?= e($theme['version']) ?> | <?= __('author', 'Themes') ?> : <?= e($theme['author']) ?></p>
                        <?php if (!empty($theme['description'])): ?>
                            <p class="theme-desc"><?= e($theme['description']) ?></p>
                        <?php endif; ?>
                        <div class="theme-actions theme-actions-compact">
                            <?php if ($name !== $activeAdmin): ?>
                                <form action="<?= url("/admin/themes/activate/admin/{$name}") ?>" method="POST" class="form-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-primary btn-sm"><?= __('activate', 'Themes') ?></button>
                                </form>
                                <form action="<?= url("/admin/themes/trash/admin/{$name}") ?>" method="POST" class="form-inline">
                                    <?= csrf_field() ?>
                                    <button
                                        type="submit"
                                        class="btn btn-outline btn-sm"
                                        data-action="confirm-delete"
                                        data-message="<?= e(__('theme_move_to_trash_confirm', 'Themes')) ?>"
                                        data-item-name="<?= e((string) ($theme['name'] ?? $name)) ?>"
                                    >
                                        <?= __('theme_move_to_trash', 'Themes') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($supportsCustomization): ?>
                                <a href="<?= url("/admin/themes/admin/{$name}/customize") ?>" class="btn btn-secondary btn-sm"><?= __('customize', 'Themes') ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card themes-empty hidden" id="themesEmptyState">
    <div class="card-body">
        <div class="admin-empty-state-panel">
            <div class="admin-empty-state-panel__icon" aria-hidden="true">
                <i class="fas fa-filter-circle-xmark"></i>
            </div>
            <h2 class="admin-empty-state-panel__title"><?= __('themes_filter_empty_title', 'Themes') ?></h2>
            <p class="admin-empty-state-panel__text"><?= __('themes_filter_empty_text', 'Themes') ?></p>
            <div class="admin-empty-state-panel__actions">
                <a href="#themesToolbar" class="btn btn-secondary"><?= __('themes_filter_empty_action_toolbar', 'Themes') ?></a>
                <a href="#themesInstallerCard" class="btn btn-primary"><?= __('themes_empty_action_install', 'Themes') ?></a>
            </div>
        </div>
    </div>
</div>

<script src="<?= module_asset('Themes', 'js/themes-index.js') ?><?= $themesJsVersion !== '' ? '?v=' . rawurlencode($themesJsVersion) : '' ?>"></script>
