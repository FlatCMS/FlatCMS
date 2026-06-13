<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('Pages', 'css/pages.css') ?>">

<?php
$status = $status ?? 'all';
$trashEnabled = !empty($trashEnabled);
$trashCount = max(0, (int) ($trashCount ?? 0));
$createPageUrl = url('/admin/pages/create');
$trashLabel = __('pages_trash', 'Pages');
if ($trashEnabled && $trashCount > 0) {
    $trashLabel .= ' (' . $trashCount . ')';
}

$pagesLocaleFlag = static function (string $locale): string {
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
    <div class="page-header-actions" data-tour-target="pages-list-toolbar">
        <form method="GET" action="<?= url('/admin/pages') ?>" class="pages-filter-form">
            <label for="statusFilter" class="form-label"><?= __('status', 'Pages') ?></label>
            <div class="pages-filter-controls">
                <select id="statusFilter" name="status" class="form-select">
                    <option value="all" <?= selected('all', $status) ?>><?= __('all_statuses', 'Pages') ?></option>
                    <option value="published" <?= selected('published', $status) ?>><?= __('status_published', 'Pages') ?></option>
                    <option value="draft" <?= selected('draft', $status) ?>><?= __('status_draft', 'Pages') ?></option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary"><?= __('filter', 'Core') ?></button>
            </div>
        </form>
        <?php if ($trashEnabled): ?>
            <a href="<?= url('/admin/trash?type=page') ?>" class="btn btn-ghost">
                <i class="fas fa-trash-can" aria-hidden="true"></i>
                <?= e($trashLabel) ?>
            </a>
        <?php endif; ?>
        <a href="<?= e($createPageUrl) ?>" class="btn btn-primary" data-tour-target="pages-list-create">
            + <?= __('create_page', 'Pages') ?>
        </a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-file-alt"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('pages_help_badge', 'Pages') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('pages_help_title', 'Pages') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('pages_help_intro', 'Pages') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('pages_help_step_source', 'Pages') ?></li>
            <li><?= __('pages_help_step_translations', 'Pages') ?></li>
            <li><?= __('pages_help_step_homepage', 'Pages') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= e($createPageUrl) ?>" class="btn btn-primary"><?= __('pages_help_action_create', 'Pages') ?></a>
            <a href="<?= e(url('/admin/settings') . '#settings-content') ?>" class="btn btn-secondary"><?= __('pages_help_action_homepage', 'Pages') ?></a>
        </div>
    </div>
</div>

<script src="<?= module_asset('Pages', 'js/pages.js') ?>"></script>

<div class="card">
    <?php if (empty($pages['data'])): ?>
        <div class="card-body">
            <div class="admin-empty-state-panel" data-tour-target="pages-list-table" data-tour-state="empty">
                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                    <i class="fas fa-file-circle-plus"></i>
                </div>
                <h2 class="admin-empty-state-panel__title"><?= __('pages_empty_title', 'Pages') ?></h2>
                <p class="admin-empty-state-panel__text"><?= __('pages_empty_text', 'Pages') ?></p>
                <div class="admin-empty-state-panel__actions">
                    <a href="<?= e($createPageUrl) ?>" class="btn btn-primary"><?= __('pages_empty_action_create', 'Pages') ?></a>
                    <a href="<?= e(url('/admin/settings') . '#settings-content') ?>" class="btn btn-secondary"><?= __('pages_empty_action_homepage', 'Pages') ?></a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <form
            method="POST"
            action="<?= url('/admin/pages/batch') ?>"
            class="pages-batch-form"
            data-pages-batch-form
            data-empty-selection-message="<?= e(__('pages_batch_no_selection', 'Pages')) ?>"
            data-action-required-message="<?= e(__('pages_batch_invalid_action', 'Pages')) ?>"
            data-selected-template="<?= e(__('pages_batch_selected_count', 'Pages', ['count' => ':count'])) ?>"
            data-delete-message="<?= e(__('confirm_delete_pages_batch', 'Pages')) ?>"
            data-delete-item-template="<?= e(__('pages_batch_delete_items_label', 'Pages', ['count' => ':count'])) ?>"
        >
            <?= csrf_field() ?>
            <?php if ($status !== 'all'): ?>
                <input type="hidden" name="status" value="<?= e($status) ?>">
            <?php endif; ?>
            <div class="pages-batch-controls" data-tour-target="pages-list-batch">
                <label class="pages-batch-select-all">
                    <input type="checkbox" data-pages-select-all>
                    <span><?= __('pages_batch_select_all', 'Pages') ?></span>
                </label>
                <span class="pages-batch-count" data-pages-batch-count><?= __('pages_batch_selected_count', 'Pages', ['count' => '0']) ?></span>
                <select name="action" class="form-select" data-pages-batch-action>
                    <option value=""><?= __('pages_batch_action_placeholder', 'Pages') ?></option>
                    <?php if ($trashEnabled): ?>
                        <option value="archive"><?= __('pages_batch_action_archive', 'Pages') ?></option>
                    <?php endif; ?>
                    <option value="delete"><?= __('pages_batch_action_delete', 'Pages') ?></option>
                </select>
                <button
                    type="submit"
                    class="btn btn-sm btn-secondary"
                    data-pages-batch-submit
                    data-confirm-text="<?= e(__('delete', 'Core')) ?>"
                    data-warning="<?= e(__('delete_warning', 'Core')) ?>"
                    disabled
                >
                    <?= __('pages_batch_apply', 'Pages') ?>
                </button>
            </div>
            <div data-pages-batch-ids></div>
        </form>

        <div class="table-wrapper" data-tour-target="pages-list-table" data-tour-state="ready">
            <table class="table">
                <thead>
                    <tr>
                        <th class="pages-select-column"><?= __('pages_batch_select_label', 'Pages') ?></th>
                        <th><?= __('title', 'Pages') ?></th>
                        <th><?= __('available_translations', 'Pages') ?></th>
                        <th><?= __('status', 'Pages') ?></th>
                        <th class="table-actions-header"><?= __('actions', 'Core') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages['data'] as $p): ?>
                        <?php
                        $isSystemRequiredPage = !empty($p['system_required']);
                        $canDeletePage = !$isSystemRequiredPage && !empty($p['can_delete']);
                        $editUrl = url('/admin/pages/' . $p['id'] . '/edit');
                        $translationFlags = $p['translations_available'] ?? [];
                        if (!is_array($translationFlags)) {
                            $translationFlags = [];
                        }
                        ?>
                        <tr>
                            <td data-label="<?= __('pages_batch_select_label', 'Pages') ?>" class="pages-select-column">
                                <?php if ($canDeletePage): ?>
                                    <input type="checkbox" class="pages-row-checkbox" data-page-select value="<?= e((string) ($p['id'] ?? '')) ?>">
                                <?php else: ?>
                                    <span class="pages-row-checkbox-disabled">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= __('title', 'Pages') ?>">
                                <strong><?= e((string) ($p['title'] ?? '')) ?></strong>
                                <?php if ($isSystemRequiredPage): ?>
                                    <span class="badge badge-info"><?= __('system_page_required_badge', 'Pages') ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= __('available_translations', 'Pages') ?>">
                                <?php if (!empty($translationFlags)): ?>
                                    <div class="pages-translation-flags">
                                        <?php foreach ($translationFlags as $translation): ?>
                                            <?php
                                            $translationCode = (string) ($translation['code'] ?? '');
                                            $translationLabel = (string) ($translation['label'] ?? $translationCode);
                                            $translationClasses = ['pages-translation-flag-badge'];
                                            if (!empty($translation['is_source'])) {
                                                $translationClasses[] = 'is-source';
                                            }
                                            ?>
                                            <span class="<?= e(implode(' ', $translationClasses)) ?>" title="<?= e($translationLabel) ?>" aria-label="<?= e($translationLabel) ?>">
                                                <span class="pages-translation-flag-badge-icon"><?= $pagesLocaleFlag($translationCode) ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="pages-translation-na">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= __('status', 'Pages') ?>">
                                <?php if (($p['status'] ?? 'draft') === 'published'): ?>
                                    <span class="badge badge-success"><?= __('status_published', 'Pages') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= __('status_draft', 'Pages') ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= __('actions', 'Core') ?>">
                                <div class="table-actions table-actions-compact">
                                    <a
                                        href="<?= e($editUrl) ?>"
                                        class="table-action table-action-edit"
                                        title="<?= e(__('edit', 'Core')) ?>"
                                        aria-label="<?= e(__('edit', 'Core')) ?>"
                                    >
                                        <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                    <?php if (!$isSystemRequiredPage): ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages['total_pages'] > 1): ?>
            <?php
            $baseUrl = url('/admin/pages');
            if ($status !== 'all') {
                $baseUrl .= '?status=' . urlencode($status);
            }
            ?>
            <div class="pages-pagination">
                <?= pagination($pages, $baseUrl) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
