<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$pagesBuilderRows = is_array($pagesBuilderRows ?? null) ? $pagesBuilderRows : [];
$pagesBuilderCanEdit = !empty($pagesBuilderCanEdit);
$trashEnabled = !empty($trashEnabled);
$trashCount = max(0, (int) ($trashCount ?? 0));
$pagesBuilderCanDeleteAny = false;
foreach ($pagesBuilderRows as $pagesBuilderRow) {
    if (!empty($pagesBuilderRow['can_delete'])) {
        $pagesBuilderCanDeleteAny = true;
        break;
    }
}
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

<link rel="stylesheet" href="<?= module_asset('Pages', 'css/pages.css') ?>">
<link rel="stylesheet" href="<?= module_asset('PagesBuilder', 'css/pages-builder.css') ?>">
<script src="<?= module_asset('Pages', 'js/pages.js') ?>"></script>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e(__('pages_builder_title', 'PagesBuilder')) ?></h1>
    </div>
    <?php if ($pagesBuilderCanEdit): ?>
        <div class="page-header-actions">
            <?php if ($trashEnabled): ?>
                <a href="<?= url('/admin/trash?type=page') ?>" class="btn btn-ghost">
                    <i class="fas fa-trash-can" aria-hidden="true"></i>
                    <?= e($trashLabel) ?>
                </a>
            <?php endif; ?>
            <a href="<?= url('/admin/pages-builder/create') ?>" class="btn btn-primary">
                + <?= e(__('create_page', 'Pages')) ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if (!$pagesBuilderCanEdit): ?>
    <div class="alert alert-warning">
        <strong><?= e(__('pages_builder_license_locked', 'PagesBuilder')) ?></strong>
        <span><?= e(__('pages_builder_license_locked_body', 'PagesBuilder')) ?></span>
    </div>
<?php endif; ?>

<div class="card pages-builder-card">
    <div class="card-body">
        <?php if ($pagesBuilderRows === []): ?>
            <p class="pages-builder-empty"><?= e(__('pages_builder_no_pages', 'PagesBuilder')) ?></p>
        <?php else: ?>
            <?php if ($pagesBuilderCanDeleteAny): ?>
                <form
                    method="POST"
                    action="<?= url('/admin/pages-builder/batch') ?>"
                    class="pages-batch-form"
                    data-pages-batch-form
                    data-empty-selection-message="<?= e(__('pages_batch_no_selection', 'Pages')) ?>"
                    data-action-required-message="<?= e(__('pages_batch_invalid_action', 'Pages')) ?>"
                    data-selected-template="<?= e(__('pages_batch_selected_count', 'Pages', ['count' => ':count'])) ?>"
                    data-delete-message="<?= e(__('confirm_delete_pages_batch', 'Pages')) ?>"
                    data-delete-item-template="<?= e(__('pages_batch_delete_items_label', 'Pages', ['count' => ':count'])) ?>"
                >
                    <?= csrf_field() ?>
                    <div class="pages-batch-controls">
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
            <?php endif; ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="pages-select-column"><?= e(__('pages_batch_select_label', 'Pages')) ?></th>
                            <th><?= e(__('title', 'Pages')) ?></th>
                            <th><?= e(__('available_translations', 'Pages')) ?></th>
                            <th><?= e(__('status', 'Pages')) ?></th>
                            <th><?= e(__('pages_builder_state', 'PagesBuilder')) ?></th>
                            <th><?= e(__('pages_builder_updated', 'PagesBuilder')) ?></th>
                            <th><?= e(__('actions', 'Core')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagesBuilderRows as $row): ?>
                            <tr>
                                <td class="pages-select-column" data-label="<?= e(__('pages_batch_select_label', 'Pages')) ?>">
                                    <?php if (!empty($row['can_delete'])): ?>
                                        <input type="checkbox" class="pages-row-checkbox" data-page-select value="<?= e((string) ($row['id'] ?? '')) ?>">
                                    <?php else: ?>
                                        <span class="pages-row-checkbox-disabled">—</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="<?= e(__('title', 'Pages')) ?>">
                                    <div class="pages-builder-table-title"><?= e((string) ($row['title'] ?? '')) ?></div>
                                </td>
                                <td data-label="<?= e(__('available_translations', 'Pages')) ?>">
                                    <?php
                                    $translationFlags = $row['translations_available'] ?? [];
                                    if (!is_array($translationFlags)) {
                                        $translationFlags = [];
                                    }
                                    ?>
                                    <?php if ($translationFlags !== []): ?>
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
                                <td data-label="<?= e(__('status', 'Pages')) ?>">
                                    <?= e((string) ($row['status_label'] ?? ($row['status'] ?? 'draft'))) ?>
                                </td>
                                <td data-label="<?= e(__('pages_builder_state', 'PagesBuilder')) ?>">
                                    <?php if (!empty($row['builder_active'])): ?>
                                        <span class="badge badge-success"><?= e(__('pages_builder_state_builder_active', 'PagesBuilder')) ?></span>
                                    <?php else: ?>
                                        <span class="status-badge default"><?= e(__('pages_builder_state_inactive', 'PagesBuilder')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="<?= e(__('pages_builder_updated', 'PagesBuilder')) ?>">
                                    <?= e((string) ($row['updated_at_label'] ?? (($row['builder_updated_at'] ?? '') !== '' ? $row['builder_updated_at'] : ($row['page_updated_at'] ?? '')))) ?>
                                </td>
                                <td data-label="<?= e(__('actions', 'Core')) ?>">
                                    <a href="<?= url('/admin/pages-builder/' . rawurlencode((string) ($row['id'] ?? ''))) ?>" class="table-action table-action-edit" title="<?= e(__('pages_builder_edit_layout', 'PagesBuilder')) ?>" aria-label="<?= e(__('pages_builder_edit_layout', 'PagesBuilder')) ?>">
                                        <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
