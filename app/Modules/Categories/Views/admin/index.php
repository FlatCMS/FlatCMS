<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('Categories', 'css/categories.css') ?>">

<?php
$trashEnabled = !empty($trashEnabled);
$trashCount = max(0, (int) ($trashCount ?? 0));
$trashLabel = __('categories_trash', 'Categories');
if ($trashEnabled && $trashCount > 0) {
    $trashLabel .= ' (' . $trashCount . ')';
}

$categoriesLocaleFlag = static function (string $locale): string {
    $value = trim($locale);
    if ($value === '') {
        return '🏳️';
    }

    $parts = preg_split('/[-_]/', $value) ?: [];
    $country = strtoupper((string) end($parts));
    if (!preg_match('/^[A-Z]{2}$/', $country)) {
        $country = strtoupper(substr($value, 0, 2));
    }

    if (!preg_match('/^[A-Z]{2}$/', $country)) {
        return '🏳️';
    }

    $first = 127397 + ord($country[0]);
    $second = 127397 + ord($country[1]);

    return html_entity_decode('&#' . $first . ';&#' . $second . ';', ENT_QUOTES | ENT_HTML5, 'UTF-8');
};
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
    </div>
    <div class="page-header-actions" data-tour-target="categories-list-toolbar">
        <?php if ($trashEnabled): ?>
            <a href="<?= url('/admin/trash?type=category') ?>" class="btn btn-ghost">
                <i class="fas fa-trash-can" aria-hidden="true"></i>
                <?= e($trashLabel) ?>
            </a>
        <?php endif; ?>
        <a href="<?= url('/admin/categories/create') ?>" class="btn btn-primary" data-tour-target="categories-list-create">+ <?= __('create_category', 'Categories') ?></a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-tags"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('categories_help_badge', 'Categories') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('categories_help_title', 'Categories') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('categories_help_intro', 'Categories') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('categories_help_step_create', 'Categories') ?></li>
            <li><?= __('categories_help_step_module', 'Categories') ?></li>
            <li><?= __('categories_help_step_translate', 'Categories') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/categories/create') ?>" class="btn btn-primary"><?= __('categories_help_action_create', 'Categories') ?></a>
            <a href="#categoriesTableCard" class="btn btn-secondary"><?= __('categories_help_action_list', 'Categories') ?></a>
        </div>
    </div>
</div>

<div class="card" id="categoriesTableCard">
    <form
        method="POST"
        action="<?= url('/admin/categories/batch') ?>"
        class="categories-batch-form"
        data-categories-batch-form
        data-empty-selection-message="<?= e(__('categories_batch_no_selection', 'Categories')) ?>"
        data-action-required-message="<?= e(__('categories_batch_invalid_action', 'Categories')) ?>"
        data-selected-template="<?= e(__('categories_batch_selected_count', 'Categories', ['count' => ':count'])) ?>"
        data-delete-message="<?= e(__('confirm_delete_categories_batch', 'Categories')) ?>"
        data-delete-item-template="<?= e(__('categories_batch_delete_items_label', 'Categories', ['count' => ':count'])) ?>"
    >
        <?= csrf_field() ?>
        <div class="categories-batch-controls" data-tour-target="categories-list-batch">
            <label class="categories-batch-select-all">
                <input type="checkbox" data-categories-select-all>
                <span><?= __('categories_batch_select_all', 'Categories') ?></span>
            </label>
            <span class="categories-batch-count" data-categories-batch-count><?= __('categories_batch_selected_count', 'Categories', ['count' => '0']) ?></span>
            <select name="action" class="form-select" data-categories-batch-action>
                <option value=""><?= __('categories_batch_action_placeholder', 'Categories') ?></option>
                <?php if ($trashEnabled): ?>
                    <option value="archive"><?= __('categories_batch_action_archive', 'Categories') ?></option>
                <?php endif; ?>
                <option value="delete"><?= __('categories_batch_action_delete', 'Categories') ?></option>
            </select>
            <button
                type="submit"
                class="btn btn-sm btn-secondary"
                data-categories-batch-submit
                data-confirm-text="<?= e(__('delete', 'Core')) ?>"
                data-warning="<?= e(__('delete_warning', 'Core')) ?>"
                disabled
            >
                <?= __('categories_batch_apply', 'Categories') ?>
            </button>
        </div>
        <div data-categories-batch-ids></div>
    </form>

    <div class="table-wrapper" data-tour-target="categories-list-table" data-tour-state="<?= empty($categories['data']) ? 'empty' : 'ready' ?>">
        <table class="table">
            <thead>
                <tr>
                    <th class="categories-select-column"><?= __('categories_batch_select_label', 'Categories') ?></th>
                    <th><?= __('name', 'Categories') ?></th>
                    <th><?= __('available_translations', 'Categories') ?></th>
                    <th><?= __('module', 'Categories') ?></th>
                    <th><?= __('status', 'Categories') ?></th>
                    <th><?= __('created_at', 'Categories') ?></th>
                    <th class="table-actions-header"><?= __('actions', 'Core') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories['data'])): ?>
                    <tr>
                        <td colspan="7" class="empty-state-cell">
                            <div class="admin-empty-state-panel">
                                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <h2 class="admin-empty-state-panel__title"><?= __('categories_empty_title', 'Categories') ?></h2>
                                <p class="admin-empty-state-panel__text"><?= __('categories_empty_text', 'Categories') ?></p>
                                <div class="admin-empty-state-panel__actions">
                                    <a href="<?= url('/admin/categories/create') ?>" class="btn btn-primary btn-sm"><?= __('categories_empty_action_create', 'Categories') ?></a>
                                    <a href="<?= url('/admin/posts') ?>" class="btn btn-secondary btn-sm"><?= __('categories_empty_action_posts', 'Categories') ?></a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories['data'] as $cat): ?>
                        <?php
                        $canDeleteCategory = !empty($cat['can_delete']);
                        $translationFlags = $cat['translations_available'] ?? [];
                        if (!is_array($translationFlags)) {
                            $translationFlags = [];
                        }
                        $moduleKey = (string) ($cat['module'] ?? 'blog');
                        $moduleLabel = $moduleOptions[$moduleKey] ?? $moduleKey;
                        ?>
                        <tr>
                            <td data-label="<?= __('categories_batch_select_label', 'Categories') ?>" class="categories-select-column">
                                <?php if ($canDeleteCategory): ?>
                                    <input type="checkbox" class="categories-row-checkbox" data-category-select value="<?= e((string) ($cat['id'] ?? '')) ?>">
                                <?php else: ?>
                                    <span class="categories-row-checkbox-disabled">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= __('name', 'Categories') ?>">
                                <strong><?= e((string) ($cat['name'] ?? '')) ?></strong>
                            </td>
                            <td data-label="<?= __('available_translations', 'Categories') ?>">
                                <?php if ($translationFlags !== []): ?>
                                    <div class="categories-translation-flags">
                                        <?php foreach ($translationFlags as $translation): ?>
                                            <?php
                                            $translationCode = (string) ($translation['code'] ?? '');
                                            $translationLabel = (string) ($translation['label'] ?? $translationCode);
                                            $translationClasses = ['categories-translation-flag-badge'];
                                            if (!empty($translation['is_source'])) {
                                                $translationClasses[] = 'is-source';
                                            }
                                            ?>
                                            <span class="<?= e(implode(' ', $translationClasses)) ?>" title="<?= e($translationLabel) ?>" aria-label="<?= e($translationLabel) ?>">
                                                <span class="categories-translation-flag-badge-icon"><?= $categoriesLocaleFlag($translationCode) ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="categories-translation-na">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= __('module', 'Categories') ?>">
                                <span class="badge badge-primary"><?= e($moduleLabel) ?></span>
                            </td>
                            <td data-label="<?= __('status', 'Categories') ?>">
                                <?php if (($cat['status'] ?? 'active') === 'active'): ?>
                                    <span class="badge badge-success"><?= __('status_active', 'Categories') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= __('status_inactive', 'Categories') ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= __('created_at', 'Categories') ?>">
                                <?= !empty($cat['created_at']) ? human_date((string) $cat['created_at']) : '-' ?>
                            </td>
                            <td data-label="<?= __('actions', 'Core') ?>">
                                <div class="table-actions table-actions-compact">
                                    <a
                                        href="<?= url('/admin/categories/' . $cat['id'] . '/edit') ?>"
                                        class="table-action table-action-edit"
                                        title="<?= e(__('edit', 'Core')) ?>"
                                        aria-label="<?= e(__('edit', 'Core')) ?>"
                                    >
                                        <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (($categories['total_pages'] ?? 1) > 1): ?>
        <div class="categories-pagination">
            <?= pagination($categories, url('/admin/categories')) ?>
        </div>
    <?php endif; ?>
</div>

<script src="<?= module_asset('Categories', 'js/categories.js') ?>"></script>
