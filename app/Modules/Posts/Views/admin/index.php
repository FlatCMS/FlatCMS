<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('Posts', 'css/posts.css') ?>">

<?php
$categories = $categories ?? [];
$filterCategory = $filterCategory ?? '';
$categoriesEnabled = $categoriesEnabled ?? true;
$trashEnabled = !empty($trashEnabled);
$trashCount = max(0, (int) ($trashCount ?? 0));
$trashLabel = __('posts_trash', 'Posts');
if ($trashEnabled && $trashCount > 0) {
    $trashLabel .= ' (' . $trashCount . ')';
}

$postsLocaleFlag = static function (string $locale): string {
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
    <div class="page-header-actions" data-tour-target="posts-list-toolbar">
        <?php if ($categoriesEnabled): ?>
            <form method="GET" action="<?= url('/admin/posts') ?>" class="posts-filter-form">
                <label for="categoryFilter" class="form-label"><?= __('filter_by_category', 'Posts') ?></label>
                <div class="posts-filter-controls">
                    <select id="categoryFilter" name="category" class="form-select">
                        <option value=""><?= __('all_categories', 'Posts') ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <?php $catId = (string) ($cat['id'] ?? ''); ?>
                            <option value="<?= e($catId) ?>" <?= selected($catId, $filterCategory) ?>>
                                <?= e($cat['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-secondary"><?= __('filter', 'Core') ?></button>
                </div>
            </form>
        <?php endif; ?>
        <?php if ($trashEnabled): ?>
            <a href="<?= url('/admin/trash?type=post') ?>" class="btn btn-ghost">
                <i class="fas fa-trash-can" aria-hidden="true"></i>
                <?= e($trashLabel) ?>
            </a>
        <?php endif; ?>
        <a href="<?= url('/admin/posts/create') ?>" class="btn btn-primary" data-tour-target="posts-list-create">+ <?= __('create_post', 'Posts') ?></a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-newspaper"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('posts_help_badge', 'Posts') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('posts_help_title', 'Posts') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('posts_help_intro', 'Posts') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('posts_help_step_source', 'Posts') ?></li>
            <li><?= __('posts_help_step_taxonomy', 'Posts') ?></li>
            <li><?= __('posts_help_step_translations', 'Posts') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= e(url('/admin/posts/create')) ?>" class="btn btn-primary"><?= __('posts_help_action_create', 'Posts') ?></a>
            <?php if ($categoriesEnabled): ?>
                <a href="<?= e(url('/admin/categories')) ?>" class="btn btn-secondary"><?= __('posts_help_action_categories', 'Posts') ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <?php if (empty($posts['data'])): ?>
        <div class="card-body">
            <div class="admin-empty-state-panel" data-tour-target="posts-list-table" data-tour-state="empty">
                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                    <i class="fas fa-pen-to-square"></i>
                </div>
                <h2 class="admin-empty-state-panel__title"><?= __('posts_empty_title', 'Posts') ?></h2>
                <p class="admin-empty-state-panel__text"><?= __('posts_empty_text', 'Posts') ?></p>
                <div class="admin-empty-state-panel__actions">
                    <a href="<?= e(url('/admin/posts/create')) ?>" class="btn btn-primary"><?= __('posts_empty_action_create', 'Posts') ?></a>
                    <?php if ($categoriesEnabled): ?>
                        <a href="<?= e(url('/admin/categories')) ?>" class="btn btn-secondary"><?= __('posts_empty_action_categories', 'Posts') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <form
            method="POST"
            action="<?= url('/admin/posts/batch') ?>"
            class="posts-batch-form"
            data-posts-batch-form
            data-empty-selection-message="<?= e(__('posts_batch_no_selection', 'Posts')) ?>"
            data-action-required-message="<?= e(__('posts_batch_invalid_action', 'Posts')) ?>"
            data-selected-template="<?= e(__('posts_batch_selected_count', 'Posts', ['count' => ':count'])) ?>"
            data-delete-message="<?= e(__('confirm_delete_posts_batch', 'Posts')) ?>"
            data-delete-item-template="<?= e(__('posts_batch_delete_items_label', 'Posts', ['count' => ':count'])) ?>"
        >
            <?= csrf_field() ?>
            <?php if ($filterCategory !== ''): ?>
                <input type="hidden" name="return_category" value="<?= e($filterCategory) ?>">
            <?php endif; ?>
            <div class="posts-batch-controls" data-tour-target="posts-list-batch">
                <label class="posts-batch-select-all">
                    <input type="checkbox" data-posts-select-all>
                    <span><?= __('posts_batch_select_all', 'Posts') ?></span>
                </label>
                <span class="posts-batch-count" data-posts-batch-count><?= __('posts_batch_selected_count', 'Posts', ['count' => '0']) ?></span>
                <select name="action" class="form-select" data-posts-batch-action>
                    <option value=""><?= __('posts_batch_action_placeholder', 'Posts') ?></option>
                    <?php if ($trashEnabled): ?>
                        <option value="archive"><?= __('posts_batch_action_archive', 'Posts') ?></option>
                    <?php endif; ?>
                    <option value="delete"><?= __('posts_batch_action_delete', 'Posts') ?></option>
                </select>
                <button
                    type="submit"
                    class="btn btn-sm btn-secondary"
                    data-posts-batch-submit
                    data-confirm-text="<?= e(__('delete', 'Core')) ?>"
                    data-warning="<?= e(__('delete_warning', 'Core')) ?>"
                    disabled
                >
                    <?= __('posts_batch_apply', 'Posts') ?>
                </button>
            </div>
            <div data-posts-batch-ids></div>
        </form>

        <div class="table-wrapper" data-tour-target="posts-list-table" data-tour-state="ready">
            <table class="table">
                <thead>
                    <tr>
                        <th class="posts-select-column"><?= __('posts_batch_select_label', 'Posts') ?></th>
                        <th><?= __('title', 'Posts') ?></th>
                        <th><?= __('available_translations', 'Posts') ?></th>
                        <th><?= __('status', 'Posts') ?></th>
                        <?php if ($categoriesEnabled): ?>
                            <th><?= __('categories', 'Posts') ?></th>
                        <?php endif; ?>
                        <th class="table-actions-header"><?= __('actions', 'Core') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts['data'] as $p): ?>
                            <?php
                            $postSlug = trim((string) ($p['slug'] ?? ''));
                            $canDeletePost = !empty($p['can_delete']);
                            $postShortcode = $postSlug !== ''
                                ? '[post slug="' . $postSlug . '"]'
                                : '[post id="' . (string) ($p['id'] ?? '') . '"]';
                            ?>
	                        <tr>
                                <td data-label="<?= __('posts_batch_select_label', 'Posts') ?>" class="posts-select-column">
                                    <?php if ($canDeletePost): ?>
                                        <input type="checkbox" class="posts-row-checkbox" data-post-select value="<?= e((string) ($p['id'] ?? '')) ?>">
                                    <?php else: ?>
                                        <span class="posts-row-checkbox-disabled">—</span>
                                    <?php endif; ?>
                                </td>
	                            <td data-label="<?= __('title', 'Posts') ?>">
                                    <button
                                        type="button"
                                        class="posts-title-trigger"
                                        data-post-copy-shortcode
                                        data-copy-text="<?= e($postShortcode) ?>"
                                        data-popover-message="<?= e(__('post_shortcode_popover_copied', 'Posts')) ?>"
                                        title="<?= e(__('post_shortcode_copy', 'Posts')) ?>"
                                        aria-label="<?= e(__('post_shortcode_copy', 'Posts')) ?>"
                                    >
                                        <strong><?= e($p['title']) ?></strong>
                                    </button>
	                            </td>
                            <td data-label="<?= __('available_translations', 'Posts') ?>">
                                <div class="posts-translation-flags">
                                    <?php foreach (($p['translations_available'] ?? []) as $translation): ?>
                                        <?php
                                        $translationCode = (string) ($translation['code'] ?? '');
                                        $translationLabel = (string) ($translation['label'] ?? $translationCode);
                                        $translationClasses = ['posts-translation-flag-badge'];
                                        if (!empty($translation['is_source'])) {
                                            $translationClasses[] = 'is-source';
                                        }
                                        ?>
                                        <span class="<?= e(implode(' ', $translationClasses)) ?>" title="<?= e($translationLabel) ?>" aria-label="<?= e($translationLabel) ?>">
                                            <span class="posts-translation-flag-badge-icon"><?= $postsLocaleFlag($translationCode) ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td data-label="<?= __('status', 'Posts') ?>">
                                <?php if (($p['status'] ?? 'draft') === 'published'): ?>
                                    <span class="badge badge-success"><?= __('status_published', 'Posts') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= __('status_draft', 'Posts') ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if ($categoriesEnabled): ?>
                                <td data-label="<?= __('categories', 'Posts') ?>">
                                    <?php
                                    $postCategories = $p['categories'] ?? [];
                                    if (!is_array($postCategories)) {
                                        $postCategories = [$postCategories];
                                    }
                                    $labels = [];
                                    foreach ($postCategories as $catId) {
                                        $label = $categoriesById[(string) $catId] ?? '';
                                        if ($label !== '') {
                                            $labels[] = $label;
                                        }
                                    }
                                    ?>
                                    <?= !empty($labels) ? e(implode(', ', $labels)) : '—' ?>
                                </td>
                            <?php endif; ?>
                            <td data-label="<?= __('actions', 'Core') ?>">
                                <div class="table-actions table-actions-compact">
                                    <a
                                        href="<?= url('/admin/posts/' . $p['id'] . '/edit') ?>"
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
                </tbody>
            </table>
        </div>
        <?php if ($posts['total_pages'] > 1): ?>
            <?php
            $baseUrl = url('/admin/posts');
            if ($filterCategory !== '') {
                $baseUrl .= '?category=' . urlencode($filterCategory);
            }
            ?>
            <div class="posts-pagination">
                <?= pagination($posts, $baseUrl) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="<?= module_asset('Posts', 'js/posts.js') ?>"></script>
