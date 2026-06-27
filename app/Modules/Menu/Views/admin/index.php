<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$menuItems = $menuItems ?? [];
$availableItems = $availableItems ?? [];
$menuSourceLocale = trim((string) ($menuSourceLocale ?? 'fr-FR'));
$menuTranslationLocales = is_array($menuTranslationLocales ?? null) ? $menuTranslationLocales : [];
$canBrowseMenuIcons = !empty($canBrowseMenuIcons);
$canUploadMenuIcons = !empty($canUploadMenuIcons);
$availableOrderMap = [];
$availableTypeMap = [];
$availableGroups = [
    'pages' => [],
    'posts' => [],
    'categories' => [],
    'cta' => [],
];

foreach ($availableItems as $idx => $availableItem) {
    $keyLabel = strtolower(trim((string) ($availableItem['label'] ?? '')));
    $keyUrl = trim((string) ($availableItem['url'] ?? ''));
    $availableOrderMap[$keyLabel . '|' . $keyUrl] = $idx;

    $itemType = strtolower(trim((string) ($availableItem['type'] ?? '')));
    $itemSource = strtolower(trim((string) ($availableItem['source'] ?? '')));
    if ($itemSource === 'custom') {
        $itemType = 'cta';
    }
    if (!in_array($itemType, ['pages', 'posts', 'categories', 'cta'], true)) {
        $itemType = 'pages';
    }
    $availableItem['type'] = $itemType;
    $availableTypeMap[$keyLabel . '|' . $keyUrl] = $itemType;
    $availableGroups[$itemType][] = [
        'item' => $availableItem,
        'order' => $idx,
    ];
}

$levelLabels = [
    __('level_root', 'Menu'),
    __('level_1', 'Menu'),
    __('level_2', 'Menu'),
    __('level_3', 'Menu'),
];

$menuConfig = [
    'maxDepth' => 3,
    'rootItemWarningThreshold' => 6,
    'indentStep' => 26,
    'iconsEndpoint' => url('/admin/menus/icons'),
    'iconImagesEndpoint' => url('/admin/media/api/images'),
    'iconUploadEndpoint' => url('/admin/media/upload'),
    'csrfToken' => csrf_token(),
    'customIconAccept' => '.png,.gif,.webp,.avif,image/png,image/gif,image/webp,image/avif',
    'canBrowseCustomIcons' => $canBrowseMenuIcons,
    'canUploadCustomIcons' => $canUploadMenuIcons,
    'translationLocales' => array_values($menuTranslationLocales),
    'sourceLocale' => $menuSourceLocale,
    'levelLabels' => $levelLabels,
    'messages' => [
        'confirmRemove' => __('confirm_delete', 'Core'),
        'labelRequired' => __('label_required', 'Menu'),
        'maxRootItemsReached' => __('menu_root_items_warning', 'Menu'),
        'menuEmpty' => __('menu_empty', 'Menu'),
        'iconsLoading' => __('icon_loading', 'Menu'),
        'iconsError' => __('icon_error', 'Menu'),
        'iconsEmpty' => __('icon_empty', 'Menu'),
        'toastItemAdded' => __('toast_item_added', 'Menu'),
        'toastItemMoved' => __('toast_item_moved', 'Menu'),
        'toastItemRemoved' => __('toast_item_removed', 'Menu'),
        'toastItemReturned' => __('toast_item_returned', 'Menu'),
        'toastCustomAdded' => __('toast_custom_added', 'Menu'),
        'toastIconUpdated' => __('toast_icon_updated', 'Menu'),
        'toastIconRemoved' => __('toast_icon_removed', 'Menu'),
        'toastTranslationSaved' => __('toast_translation_saved', 'Menu'),
        'toastCustomIconSelected' => __('toast_custom_icon_selected', 'Menu'),
        'toastCustomIconUploaded' => __('toast_custom_icon_uploaded', 'Menu'),
        'customIconInvalidType' => __('icon_custom_invalid_type', 'Menu'),
        'customIconUploadError' => __('icon_custom_upload_failed', 'Menu'),
        'customIconUploadUnavailable' => __('icon_custom_upload_unavailable', 'Menu'),
        'customIconEmpty' => __('icon_custom_empty', 'Menu'),
        'customIconUnavailable' => __('icon_custom_unavailable', 'Menu'),
        'mediaModalUnavailable' => __('media_modal_unavailable', 'Menu'),
    ],
    'toastDuration' => 1500,
    'defaults' => [
        'icon' => '',
        'urlEmpty' => __('url_home', 'Menu'),
    ],
];
$menuConfigJson = e(json_encode($menuConfig));
$menuCssPath = BASE_PATH . '/app/Modules/Menu/Assets/css/menu-module.css';
$menuCssVersion = is_file($menuCssPath) ? (string) filemtime($menuCssPath) : '';
$menuScriptPath = BASE_PATH . '/app/Modules/Menu/Assets/js/menu.js';
$menuScriptVersion = is_file($menuScriptPath) ? (string) filemtime($menuScriptPath) : '';

if (!function_exists('menu_render_item')) {
    function menu_render_item(array $item, int $order, int $depth, string $origin, array $levelLabels): void
    {
        $label = trim((string) ($item['label'] ?? ''));
        $url = trim((string) ($item['url'] ?? ''));
        $icon = trim((string) ($item['icon'] ?? ''));
        $target = trim((string) ($item['target'] ?? ''));
        $id = trim((string) ($item['id'] ?? ''));
        if ($id === '') {
            $id = 'menu_' . substr(md5($label . '|' . $url . '|' . $order), 0, 10);
        }
        $source = trim((string) ($item['source'] ?? ''));
        $labelMode = strtolower(trim((string) ($item['labelMode'] ?? '')));
        if (!in_array($labelMode, ['auto', 'custom'], true)) {
            $labelMode = '';
        }
        $refType = trim((string) ($item['refType'] ?? ''));
        $ref = trim((string) ($item['ref'] ?? ''));
        $autoLabel = trim((string) ($item['autoLabel'] ?? $label));
        $autoUrl = trim((string) ($item['autoUrl'] ?? $url));
        $iconType = strtolower(trim((string) ($item['iconType'] ?? '')));
        $iconMedia = trim((string) ($item['iconMedia'] ?? ''));
        if ($iconType !== 'media' || $iconMedia === '') {
            $iconType = '';
            $iconMedia = '';
        }
        $translations = is_array($item['translations'] ?? null) ? $item['translations'] : [];
        $translationsJson = e((string) json_encode($translations, JSON_UNESCAPED_UNICODE));
        $translationFallbacks = is_array($item['translationFallbacks'] ?? null) ? $item['translationFallbacks'] : [];
        $translationFallbacksJson = e((string) json_encode($translationFallbacks, JSON_UNESCAPED_UNICODE));
        $depth = max(0, min(3, $depth));
        $levelLabel = $levelLabels[$depth] ?? $levelLabels[0] ?? '';
        $titleDisplay = $label !== '' ? $label : __('menu_item', 'Menu');
        $isExternal = preg_match('#^(https?:)?//#i', $url) || preg_match('#^https?://#i', $url);
        if ($target === '' && $isExternal) {
            $target = '_blank';
        }
        if (!in_array($target, ['_self', '_blank'], true)) {
            $target = '_self';
        }
        if ($source === '') {
            $source = $isExternal ? 'custom' : 'core';
        }
        $type = strtolower(trim((string) ($item['type'] ?? '')));
        if ($source === 'custom') {
            $type = 'cta';
        }
        if (!in_array($type, ['pages', 'posts', 'categories', 'cta'], true)) {
            $type = 'pages';
        }
        $isActive = $origin === 'active';
        $activeClass = $isActive ? ' menu-item--active' : '';
        $hasCustomIcon = $iconType === 'media' && $iconMedia !== '';
        ?>
        <div class="menu-item menu-indent-<?= $depth ?><?= $activeClass ?>" data-id="<?= e($id) ?>" data-indent="<?= $depth ?>" data-order="<?= $order ?>" data-origin="<?= e($origin) ?>" data-source="<?= e($source) ?>" data-type="<?= e($type) ?>" data-label-mode="<?= e($labelMode) ?>" data-ref-type="<?= e($refType) ?>" data-ref="<?= e($ref) ?>" data-auto-label="<?= e($autoLabel) ?>" data-auto-url="<?= e($autoUrl) ?>">
            <div class="menu-item-main">
                <button type="button" class="menu-drag-handle" data-action="drag-handle" aria-label="<?= __('drag_handle', 'Menu') ?>">
                    <i class="fas fa-grip-vertical"></i>
                </button>
                <span class="menu-item-icon<?= $icon === '' && !$hasCustomIcon ? ' is-empty' : '' ?>">
                    <?php if ($hasCustomIcon): ?>
                        <span class="menu-item-icon-media">
                            <img src="<?= e(site_media_url($iconMedia)) ?>" alt="">
                        </span>
                    <?php else: ?>
                        <i class="menu-item-icon-preview<?= $icon !== '' ? ' ' . e($icon) : ' is-empty' ?>"></i>
                    <?php endif; ?>
                </span>
                <div class="menu-item-content">
                    <span class="menu-item-title" data-role="title" data-fallback="<?= __('menu_item', 'Menu') ?>"><?= e($titleDisplay) ?></span>
                </div>
                <div class="menu-item-badges">
                    <span class="menu-item-level" data-role="level"><?= e($levelLabel) ?></span>
                </div>
                <div class="menu-item-actions">
                    <button type="button" class="btn btn-ghost btn-sm" data-action="toggle-config" aria-expanded="false" aria-label="<?= __('settings', 'Menu') ?>">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </div>
            <div class="menu-item-config" data-role="config">
                <div class="menu-config-grid">
                    <div class="form-group">
                        <label class="form-label"><?= __('label', 'Menu') ?></label>
                        <input type="text" class="form-input" data-field="label" value="<?= e($label) ?>" placeholder="<?= __('label', 'Menu') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('url', 'Menu') ?></label>
                        <input type="text" class="form-input" data-field="url" value="<?= e($url) ?>" placeholder="<?= __('url_placeholder', 'Menu') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('target', 'Menu') ?></label>
                        <select class="form-select" data-field="target">
                            <option value="_self" <?= $target === '_self' ? 'selected' : '' ?>><?= __('target_self', 'Menu') ?></option>
                            <option value="_blank" <?= $target === '_blank' ? 'selected' : '' ?>><?= __('target_blank', 'Menu') ?></option>
                        </select>
                    </div>
                </div>
                <div class="menu-config-actions">
                    <input type="hidden" class="menu-icon-input" data-field="icon" value="<?= e($icon) ?>">
                    <input type="hidden" data-field="iconType" value="<?= e($iconType) ?>">
                    <input type="hidden" data-field="iconMedia" value="<?= e($iconMedia) ?>">
                    <input type="hidden" data-field="translations" value="<?= $translationsJson ?>">
                    <input type="hidden" data-field="translationFallbacks" value="<?= $translationFallbacksJson ?>">
                    <button type="button" class="btn btn-secondary btn-sm" data-action="icon-picker">
                        <i class="fas fa-icons"></i>
                        <?= __('icon_picker', 'Menu') ?>
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" data-action="icon-clear">
                        <i class="fas fa-times"></i>
                        <?= __('icon_remove', 'Menu') ?>
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" data-action="open-translation-modal">
                        <i class="fas fa-language"></i>
                        <?= __('menu_translate', 'Menu') ?>
                    </button>
                    <div class="menu-config-spacer"></div>
                    <button type="button" class="btn btn-ghost btn-sm menu-return-btn" data-action="return-item">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                        <?= __('return_item', 'Menu') ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm menu-remove-btn" data-action="remove-item">
                        <i class="fas fa-trash-alt"></i>
                        <?= __('remove', 'Menu') ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}
?>

<link rel="stylesheet" href="<?= module_asset('Menu', 'css/menu-module.css') ?><?= $menuCssVersion !== '' ? ('?v=' . $menuCssVersion) : '' ?>">

<div id="menuConfig" class="menu-config-data" data-menu-config="<?= $menuConfigJson ?>"></div>
<div id="menuIndentGuide" class="menu-indent-guide" aria-hidden="true"></div>

<div class="page-header menu-page-header">
    <div>
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
        <p class="page-subtitle"><?= __('menu_subtitle', 'Menu') ?></p>
    </div>
    <div class="page-header-actions">
        <button type="submit" form="menuForm" class="btn btn-primary">
            <i class="fas fa-save"></i>
            <?= __('save', 'Core') ?>
        </button>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-bars"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('menu_help_badge', 'Menu') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('menu_help_title', 'Menu') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('menu_help_intro', 'Menu') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('menu_help_step_library', 'Menu') ?></li>
            <li><?= __('menu_help_step_structure', 'Menu') ?></li>
            <li><?= __('menu_help_step_save', 'Menu') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="#menuActivePanel" class="btn btn-primary"><?= __('menu_help_action_structure', 'Menu') ?></a>
            <a href="#menuAvailablePanel" class="btn btn-secondary"><?= __('menu_help_action_library', 'Menu') ?></a>
        </div>
    </div>
</div>

<form method="POST" action="<?= url('/admin/menus') ?>" id="menuForm" class="menu-editor-form">
    <?= csrf_field() ?>
    <input type="hidden" name="menu_data" id="menuDataField" value="">
    <input type="hidden" name="menu_library" id="menuLibraryField" value="">

        <div class="menu-editor">
            <section class="menu-panel" id="menuActivePanel">
                <div class="menu-panel-header">
                    <div>
                        <h2 class="menu-panel-title"><?= __('menu_structure', 'Menu') ?></h2>
                        <p class="menu-panel-subtitle"><?= __('menu_structure_hint', 'Menu') ?></p>
                    </div>
                    <span class="menu-panel-badge">
                        <i class="fas fa-layer-group"></i>
                        <?= __('menu_active', 'Menu') ?>
                    </span>
                </div>
                <div id="menuActive" class="menu-list menu-list-active">
                    <?php if (empty($menuItems)): ?>
                        <div id="menuEmptyState" class="menu-empty-state">
                            <i class="fas fa-hand-pointer menu-empty-icon"></i>
                            <h3 class="menu-empty-title"><?= __('menu_empty_title', 'Menu') ?></h3>
                            <p class="menu-empty-text"><?= __('menu_empty_text', 'Menu') ?></p>
                            <div class="menu-empty-actions">
                                <a href="#menuAvailablePanel" class="btn btn-secondary btn-sm"><?= __('menu_empty_action_library', 'Menu') ?></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div id="menuEmptyState" class="menu-empty-state menu-empty-hidden">
                            <i class="fas fa-hand-pointer menu-empty-icon"></i>
                            <h3 class="menu-empty-title"><?= __('menu_empty_title', 'Menu') ?></h3>
                            <p class="menu-empty-text"><?= __('menu_empty_text', 'Menu') ?></p>
                            <div class="menu-empty-actions">
                                <a href="#menuAvailablePanel" class="btn btn-secondary btn-sm"><?= __('menu_empty_action_library', 'Menu') ?></a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($menuItems as $index => $item): ?>
                        <?php
                            $keyLabel = strtolower(trim((string) ($item['label'] ?? '')));
                            $keyUrl = trim((string) ($item['url'] ?? ''));
                            $order = $availableOrderMap[$keyLabel . '|' . $keyUrl] ?? $index;
                            if (!isset($item['type']) || trim((string) $item['type']) === '') {
                                $itemType = $availableTypeMap[$keyLabel . '|' . $keyUrl] ?? '';
                                if ($itemType !== '') {
                                    $item['type'] = $itemType;
                                }
                            }
                        ?>
                        <?php menu_render_item($item, (int) $order, (int) ($item['depth'] ?? 0), 'active', $levelLabels); ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="menu-sidebar-column menu-sidebar-column--sticky">
                <section class="menu-panel menu-panel--sticky" id="menuAvailablePanel">
                    <div class="menu-panel-sticky-inner">
                        <div class="menu-panel-header">
                            <div>
                                <h2 class="menu-panel-title"><?= __('available_items', 'Menu') ?></h2>
                                <p class="menu-panel-subtitle"><?= __('available_items_hint', 'Menu') ?></p>
                            </div>
                            <span class="menu-panel-badge menu-panel-badge-alt">
                                <i class="fas fa-list"></i>
                                <?= __('menu_available', 'Menu') ?>
                            </span>
                        </div>

                        <div id="menuAvailable" class="menu-available-accordion">
                            <section class="menu-accordion-group" data-group="pages">
                                <button type="button" class="menu-accordion-toggle" data-action="menu-available-toggle" aria-expanded="false" aria-controls="menuAvailablePages">
                                    <span class="menu-accordion-label"><?= __('menu_group_pages', 'Menu') ?></span>
                                    <span class="menu-accordion-meta">
                                        <span class="menu-accordion-count" data-role="available-group-count"><?= count($availableGroups['pages']) ?></span>
                                        <i class="fas fa-chevron-down menu-accordion-chevron" aria-hidden="true"></i>
                                    </span>
                                </button>
                                <div class="menu-accordion-panel" id="menuAvailablePages">
                                    <div class="menu-accordion-body">
                                        <div class="menu-list menu-list-available menu-list-available-group" data-available-group="pages">
                                            <?php foreach ($availableGroups['pages'] as $entry): ?>
                                                <?php menu_render_item($entry['item'], (int) $entry['order'], 0, 'available', $levelLabels); ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="menu-accordion-group" data-group="posts">
                                <button type="button" class="menu-accordion-toggle" data-action="menu-available-toggle" aria-expanded="false" aria-controls="menuAvailablePosts">
                                    <span class="menu-accordion-label"><?= __('menu_group_posts', 'Menu') ?></span>
                                    <span class="menu-accordion-meta">
                                        <span class="menu-accordion-count" data-role="available-group-count"><?= count($availableGroups['posts']) ?></span>
                                        <i class="fas fa-chevron-down menu-accordion-chevron" aria-hidden="true"></i>
                                    </span>
                                </button>
                                <div class="menu-accordion-panel" id="menuAvailablePosts">
                                    <div class="menu-accordion-body">
                                        <div class="menu-list menu-list-available menu-list-available-group" data-available-group="posts">
                                            <?php foreach ($availableGroups['posts'] as $entry): ?>
                                                <?php menu_render_item($entry['item'], (int) $entry['order'], 0, 'available', $levelLabels); ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="menu-accordion-group" data-group="categories">
                                <button type="button" class="menu-accordion-toggle" data-action="menu-available-toggle" aria-expanded="false" aria-controls="menuAvailableCategories">
                                    <span class="menu-accordion-label"><?= __('menu_group_categories', 'Menu') ?></span>
                                    <span class="menu-accordion-meta">
                                        <span class="menu-accordion-count" data-role="available-group-count"><?= count($availableGroups['categories']) ?></span>
                                        <i class="fas fa-chevron-down menu-accordion-chevron" aria-hidden="true"></i>
                                    </span>
                                </button>
                                <div class="menu-accordion-panel" id="menuAvailableCategories">
                                    <div class="menu-accordion-body">
                                        <div class="menu-list menu-list-available menu-list-available-group" data-available-group="categories">
                                            <?php foreach ($availableGroups['categories'] as $entry): ?>
                                                <?php menu_render_item($entry['item'], (int) $entry['order'], 0, 'available', $levelLabels); ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="menu-accordion-group" data-group="cta">
                                <button type="button" class="menu-accordion-toggle" data-action="menu-available-toggle" aria-expanded="false" aria-controls="menuAvailableCta">
                                    <span class="menu-accordion-label"><?= __('menu_group_custom_links', 'Menu') ?></span>
                                    <span class="menu-accordion-meta">
                                        <span class="menu-accordion-count" data-role="available-group-count"><?= count($availableGroups['cta']) ?></span>
                                        <i class="fas fa-chevron-down menu-accordion-chevron" aria-hidden="true"></i>
                                    </span>
                                </button>
                                <div class="menu-accordion-panel" id="menuAvailableCta">
                                    <div class="menu-accordion-body">
                                        <div class="menu-list menu-list-available menu-list-available-group" data-available-group="cta">
                                            <?php foreach ($availableGroups['cta'] as $entry): ?>
                                                <?php menu_render_item($entry['item'], (int) $entry['order'], 0, 'available', $levelLabels); ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="menu-custom-card menu-available-custom">
                            <h3 class="menu-custom-title"><?= __('external_links', 'Menu') ?></h3>
                            <div class="menu-custom-form">
                                <input type="text" class="form-input" id="menuCustomLabel" placeholder="<?= __('custom_label', 'Menu') ?>">
                                <input type="text" class="form-input" id="menuCustomUrl" placeholder="<?= __('custom_url', 'Menu') ?>">
                                <button type="button" class="btn btn-secondary" data-action="add-custom-item">
                                    <i class="fas fa-plus"></i>
                                    <?= __('add_custom', 'Menu') ?>
                                </button>
                            </div>
                            <p class="menu-custom-hint"><?= __('custom_hint', 'Menu') ?></p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

</form>

<template id="menuItemTemplate">
    <div class="menu-item menu-indent-0" data-id="" data-indent="0" data-order="0" data-origin="available" data-source="custom" data-type="cta" data-label-mode="" data-ref-type="" data-ref="" data-auto-label="" data-auto-url="">
        <div class="menu-item-main">
            <button type="button" class="menu-drag-handle" data-action="drag-handle" aria-label="<?= __('drag_handle', 'Menu') ?>">
                <i class="fas fa-grip-vertical"></i>
            </button>
            <span class="menu-item-icon is-empty">
                <i class="menu-item-icon-preview is-empty"></i>
            </span>
            <div class="menu-item-content">
                <span class="menu-item-title" data-role="title" data-fallback="<?= __('menu_item', 'Menu') ?>"><?= __('menu_item', 'Menu') ?></span>
            </div>
            <div class="menu-item-badges">
                <span class="menu-item-level" data-role="level"><?= __('level_root', 'Menu') ?></span>
            </div>
            <div class="menu-item-actions">
                <button type="button" class="btn btn-ghost btn-sm" data-action="toggle-config" aria-expanded="false" aria-label="<?= __('settings', 'Menu') ?>">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </div>
        <div class="menu-item-config" data-role="config">
            <div class="menu-config-grid">
                <div class="form-group">
                    <label class="form-label"><?= __('label', 'Menu') ?></label>
                    <input type="text" class="form-input" data-field="label" value="" placeholder="<?= __('label', 'Menu') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('url', 'Menu') ?></label>
                    <input type="text" class="form-input" data-field="url" value="" placeholder="<?= __('url_placeholder', 'Menu') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('target', 'Menu') ?></label>
                    <select class="form-select" data-field="target">
                        <option value="_self"><?= __('target_self', 'Menu') ?></option>
                        <option value="_blank"><?= __('target_blank', 'Menu') ?></option>
                    </select>
                </div>
            </div>
            <div class="menu-config-actions">
                <input type="hidden" class="menu-icon-input" data-field="icon" value="">
                <input type="hidden" data-field="iconType" value="">
                <input type="hidden" data-field="iconMedia" value="">
                <input type="hidden" data-field="translations" value="{}">
                <input type="hidden" data-field="translationFallbacks" value="{}">
                <button type="button" class="btn btn-secondary btn-sm" data-action="icon-picker">
                    <i class="fas fa-icons"></i>
                    <?= __('icon_picker', 'Menu') ?>
                </button>
                <button type="button" class="btn btn-ghost btn-sm" data-action="icon-clear">
                    <i class="fas fa-times"></i>
                    <?= __('icon_remove', 'Menu') ?>
                </button>
                <button type="button" class="btn btn-ghost btn-sm" data-action="open-translation-modal">
                    <i class="fas fa-language"></i>
                    <?= __('menu_translate', 'Menu') ?>
                </button>
                <div class="menu-config-spacer"></div>
                <button type="button" class="btn btn-ghost btn-sm menu-return-btn" data-action="return-item">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                    <?= __('return_item', 'Menu') ?>
                </button>
                <button type="button" class="btn btn-danger btn-sm menu-remove-btn" data-action="remove-item">
                    <i class="fas fa-trash-alt"></i>
                    <?= __('remove', 'Menu') ?>
                </button>
            </div>
        </div>
    </div>
</template>

<div id="menuIconModal" class="menu-icon-modal" aria-hidden="true">
    <div class="menu-icon-dialog">
        <div class="menu-icon-header">
            <h3><?= __('icon_picker_title', 'Menu') ?></h3>
            <button type="button" class="menu-icon-close" data-action="icon-modal-close" aria-label="<?= __('close', 'Core') ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="menu-icon-search">
            <input type="text" class="form-input" id="menuIconSearch" placeholder="<?= __('icon_search', 'Menu') ?>">
            <div class="menu-icon-upload-row">
                <div class="menu-icon-upload-copy">
                    <span class="menu-icon-upload-title"><?= __('icon_custom_library', 'Menu') ?></span>
                    <span class="menu-icon-upload-hint"><?= __('icon_custom_upload_hint', 'Menu') ?></span>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" data-action="icon-custom-media-modal">
                    <i class="fas fa-photo-film"></i>
                    <?= __('icon_custom_upload', 'Menu') ?>
                </button>
            </div>
        </div>
        <div class="menu-icon-section-header">
            <h4><?= __('icon_fontawesome_library', 'Menu') ?></h4>
        </div>
        <div id="menuIconGrid" class="menu-icon-grid">
            <div class="menu-icon-loading"><?= __('icon_loading', 'Menu') ?></div>
        </div>
    </div>
</div>

<div id="menuTranslationModal" class="menu-translation-modal" aria-hidden="true">
    <div class="menu-translation-dialog">
        <div class="menu-translation-header">
            <div>
                <h3><?= __('menu_translate_title', 'Menu') ?></h3>
                <p class="menu-translation-subtitle"><?= __('menu_translate_intro', 'Menu') ?></p>
            </div>
            <button type="button" class="menu-icon-close" data-action="translation-modal-close" aria-label="<?= __('close', 'Core') ?>">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="menu-translation-body">
            <div class="menu-translation-source">
                <span class="menu-translation-source-label"><?= __('menu_translate_source_label', 'Menu') ?></span>
                <strong id="menuTranslationSourceLabel"><?= __('menu_item', 'Menu') ?></strong>
            </div>
            <?php if ($menuTranslationLocales !== []): ?>
                <div class="menu-translation-fields">
                    <?php foreach ($menuTranslationLocales as $translationLocale): ?>
                        <?php
                            $localeCode = trim((string) ($translationLocale['code'] ?? ''));
                            $localeLabel = trim((string) ($translationLocale['label'] ?? $localeCode));
                            $inputId = 'menuTranslation_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $localeCode);
                        ?>
                        <div class="form-group">
                            <label class="form-label" for="<?= e($inputId) ?>"><?= e($localeLabel) ?></label>
                            <input type="text" class="form-input" id="<?= e($inputId) ?>" data-locale="<?= e($localeCode) ?>" value="">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="menu-translation-empty"><?= __('menu_translate_empty', 'Menu') ?></p>
            <?php endif; ?>
        </div>
        <div class="menu-translation-actions">
            <button type="button" class="btn btn-ghost" data-action="translation-modal-close">
                <?= __('cancel', 'Core') ?>
            </button>
            <button type="button" class="btn btn-primary" data-action="translation-modal-save">
                <i class="fas fa-save"></i>
                <?= __('save', 'Core') ?>
            </button>
        </div>
    </div>
</div>

<?php
    $mediaModalPath = BASE_PATH . '/app/Modules/Media/Views/admin/partials/media-modal.php';
    $mediaModalScriptPath = BASE_PATH . '/app/Modules/Media/Assets/js/media-modal.js';
?>
<?php if (is_file($mediaModalPath)): ?>
    <?php include $mediaModalPath; ?>
    <?php if (is_file($mediaModalScriptPath)): ?>
        <script src="<?= module_asset('Media', 'js/media-modal.js') ?>"></script>
    <?php endif; ?>
<?php endif; ?>

<script src="<?= module_asset('Menu', 'js/menu.js') ?><?= $menuScriptVersion !== '' ? ('?v=' . $menuScriptVersion) : '' ?>"></script>
