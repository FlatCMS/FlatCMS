<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('Trash', 'css/trash.css') ?>">

<?php $trashItems = is_array($trashItems ?? null) ? $trashItems : ['data' => [], 'total_pages' => 1]; ?>
<?php $trashType = (string) ($trashType ?? 'all'); ?>
<?php $trashAllowedTypes = is_array($trashAllowedTypes ?? null) ? $trashAllowedTypes : []; ?>
<?php $trashBackUrl = (string) ($trashBackUrl ?? url('/admin/pages')); ?>
<?php $trashBackLabel = (string) ($trashBackLabel ?? __('trash_back_to_pages', 'Trash')); ?>
<?php
$trashPaginationBaseUrl = url('/admin/trash');
if ($trashType !== 'all') {
    $trashPaginationBaseUrl .= '?type=' . urlencode($trashType);
}
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
    </div>
    <div class="page-header-actions">
        <a href="<?= e($trashBackUrl) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <?= e($trashBackLabel) ?>
        </a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-trash-restore"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('trash_help_badge', 'Trash') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('trash_help_title', 'Trash') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('trash_help_intro', 'Trash') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('trash_help_step_filter', 'Trash') ?></li>
            <li><?= __('trash_help_step_restore', 'Trash') ?></li>
            <li><?= __('trash_help_step_delete', 'Trash') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="#trashTableCard" class="btn btn-primary"><?= __('trash_help_action_list', 'Trash') ?></a>
            <a href="<?= e($trashBackUrl) ?>" class="btn btn-secondary"><?= e($trashBackLabel) ?></a>
        </div>
    </div>
</div>

<div class="card" id="trashTableCard">
    <?php if (count($trashAllowedTypes) > 1): ?>
        <div class="trash-filter-bar" id="trashFilterBar">
            <a href="<?= url('/admin/trash') ?>" class="btn btn-sm <?= $trashType === 'all' ? 'btn-primary' : 'btn-ghost' ?>">
                <?= __('trash_filter_all', 'Trash') ?>
            </a>
            <?php if (in_array('page', $trashAllowedTypes, true)): ?>
                <a href="<?= url('/admin/trash?type=page') ?>" class="btn btn-sm <?= $trashType === 'page' ? 'btn-primary' : 'btn-ghost' ?>">
                    <?= __('trash_filter_pages', 'Trash') ?>
                </a>
            <?php endif; ?>
            <?php if (in_array('post', $trashAllowedTypes, true)): ?>
                <a href="<?= url('/admin/trash?type=post') ?>" class="btn btn-sm <?= $trashType === 'post' ? 'btn-primary' : 'btn-ghost' ?>">
                    <?= __('trash_filter_posts', 'Trash') ?>
                </a>
            <?php endif; ?>
            <?php if (in_array('category', $trashAllowedTypes, true)): ?>
                <a href="<?= url('/admin/trash?type=category') ?>" class="btn btn-sm <?= $trashType === 'category' ? 'btn-primary' : 'btn-ghost' ?>">
                    <?= __('trash_filter_categories', 'Trash') ?>
                </a>
            <?php endif; ?>
            <?php if (in_array('theme', $trashAllowedTypes, true)): ?>
                <a href="<?= url('/admin/trash?type=theme') ?>" class="btn btn-sm <?= $trashType === 'theme' ? 'btn-primary' : 'btn-ghost' ?>">
                    <?= __('trash_filter_themes', 'Trash') ?>
                </a>
            <?php endif; ?>
            <?php if (in_array('media', $trashAllowedTypes, true)): ?>
                <a href="<?= url('/admin/trash?type=media') ?>" class="btn btn-sm <?= $trashType === 'media' ? 'btn-primary' : 'btn-ghost' ?>">
                    <?= __('trash_filter_media', 'Trash') ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($trashItems['data'])): ?>
        <div class="trash-empty-state">
            <div class="admin-empty-state-panel">
                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                    <i class="fas fa-trash"></i>
                </div>
                <h2 class="admin-empty-state-panel__title"><?= __('trash_empty_title', 'Trash') ?></h2>
                <p class="admin-empty-state-panel__text"><?= __('trash_empty_text', 'Trash') ?></p>
                <div class="admin-empty-state-panel__actions">
                    <a href="<?= e($trashBackUrl) ?>" class="btn btn-primary"><?= e($trashBackLabel) ?></a>
                    <?php if ($trashType !== 'all' && count($trashAllowedTypes) > 1): ?>
                        <a href="<?= url('/admin/trash') ?>" class="btn btn-secondary"><?= __('trash_empty_action_all', 'Trash') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <form
            method="POST"
            action="<?= url('/admin/trash/batch') ?>"
            class="trash-batch-form"
            data-trash-batch-form
            data-empty-selection-message="<?= e(__('trash_batch_no_selection', 'Trash')) ?>"
            data-action-required-message="<?= e(__('trash_batch_invalid_action', 'Trash')) ?>"
            data-selected-template="<?= e(__('trash_batch_selected_count', 'Trash', ['count' => ':count'])) ?>"
            data-delete-message="<?= e(__('trash_delete_confirm', 'Trash')) ?>"
            data-delete-item-template="<?= e(__('trash_batch_delete_items_label', 'Trash', ['count' => ':count'])) ?>"
        >
            <?= csrf_field() ?>
            <?php if ($trashType !== 'all'): ?>
                <input type="hidden" name="type" value="<?= e($trashType) ?>">
            <?php endif; ?>
            <div class="trash-batch-controls">
                <label class="trash-batch-select-all">
                    <input type="checkbox" data-trash-select-all>
                    <span><?= __('trash_batch_select_all', 'Trash') ?></span>
                </label>
                <span class="trash-batch-count" data-trash-batch-count><?= __('trash_batch_selected_count', 'Trash', ['count' => '0']) ?></span>
                <select name="action" class="form-select" data-trash-batch-action>
                    <option value=""><?= __('trash_batch_action_placeholder', 'Trash') ?></option>
                    <option value="restore"><?= __('trash_batch_action_restore', 'Trash') ?></option>
                    <option value="delete"><?= __('trash_batch_action_delete', 'Trash') ?></option>
                </select>
                <button
                    type="submit"
                    class="btn btn-sm btn-secondary"
                    data-trash-batch-submit
                    data-confirm-text="<?= e(__('delete', 'Core')) ?>"
                    data-warning="<?= e(__('delete_warning', 'Core')) ?>"
                    disabled
                >
                    <?= __('trash_batch_apply', 'Trash') ?>
                </button>
            </div>
            <div data-trash-batch-ids></div>
        </form>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th class="trash-select-column"><?= __('trash_batch_select_label', 'Trash') ?></th>
                        <th><?= __('trash_type_column', 'Trash') ?></th>
                        <th><?= __('trash_title_column', 'Trash') ?></th>
                        <th><?= __('trash_slug_column', 'Trash') ?></th>
                        <th><?= __('trash_deleted_at', 'Trash') ?></th>
                        <th><?= __('trash_deleted_by', 'Trash') ?></th>
                        <th><?= __('actions', 'Core') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trashItems['data'] as $item): ?>
                        <?php
                        $trashId = (string) ($item['id'] ?? '');
                        $entityType = (string) ($item['entity_type'] ?? '');
                        $title = (string) ($item['entity_title'] ?? '');
                        $slug = (string) ($item['entity_slug'] ?? '');
                        $deletedBy = trim((string) ($item['deleted_by'] ?? ''));
                        $typeLabel = match ($entityType) {
                            'post' => __('trash_item_type_post', 'Trash'),
                            'category' => __('trash_item_type_category', 'Trash'),
                            'theme' => __('trash_item_type_theme', 'Trash'),
                            'media' => __('trash_item_type_media', 'Trash'),
                            default => __('trash_item_type_page', 'Trash'),
                        };
                        ?>
                        <tr>
                            <td data-label="<?= __('trash_batch_select_label', 'Trash') ?>" class="trash-select-column">
                                <input type="checkbox" class="trash-row-checkbox" data-trash-select value="<?= e($trashId) ?>">
                            </td>
                            <td data-label="<?= __('trash_type_column', 'Trash') ?>">
                                <span class="trash-type-badge is-<?= e($entityType !== '' ? $entityType : 'page') ?>">
                                    <?= e($typeLabel) ?>
                                </span>
                            </td>
                            <td data-label="<?= __('trash_title_column', 'Trash') ?>">
                                <strong><?= e($title !== '' ? $title : __('trash_untitled', 'Trash')) ?></strong>
                            </td>
                            <td data-label="<?= __('trash_slug_column', 'Trash') ?>">
                                <?= $slug !== '' ? e($slug) : '—' ?>
                            </td>
                            <td data-label="<?= __('trash_deleted_at', 'Trash') ?>">
                                <?= human_date((string) ($item['deleted_at'] ?? '')) ?>
                            </td>
                            <td data-label="<?= __('trash_deleted_by', 'Trash') ?>">
                                <?= $deletedBy !== '' ? e($deletedBy) : '—' ?>
                            </td>
                            <td data-label="<?= __('actions', 'Core') ?>" class="trash-action-cell">
                                <div class="table-actions table-actions-compact trash-table-actions">
                                    <form action="<?= url('/admin/trash/' . $trashId . '/restore') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="type" value="<?= e($entityType) ?>">
                                        <button
                                            type="submit"
                                            class="table-action table-action-restore"
                                            title="<?= e(__('trash_restore', 'Trash')) ?>"
                                            aria-label="<?= e(__('trash_restore', 'Trash')) ?>"
                                        >
                                            <i class="fas fa-rotate-left" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                    <form action="<?= url('/admin/trash/' . $trashId . '/delete') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="type" value="<?= e($entityType) ?>">
                                        <button
                                            type="submit"
                                            class="table-action table-action-delete"
                                            data-action="confirm-delete"
                                            data-message="<?= __('trash_delete_confirm', 'Trash') ?>"
                                            data-item-name="<?= e($title !== '' ? $title : __('trash_untitled', 'Trash')) ?>"
                                            title="<?= e(__('trash_delete_forever', 'Trash')) ?>"
                                            aria-label="<?= e(__('trash_delete_forever', 'Trash')) ?>"
                                        >
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (($trashItems['total_pages'] ?? 1) > 1): ?>
            <div class="trash-pagination">
                <?= pagination($trashItems, $trashPaginationBaseUrl) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="<?= module_asset('Trash', 'js/trash.js') ?>"></script>
