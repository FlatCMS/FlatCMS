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
$backupsCssPath = BASE_PATH . '/app/Modules/Backups/Assets/css/backups.css';
$backupsCssVersion = is_file($backupsCssPath) ? (string) filemtime($backupsCssPath) : '';
$backups = is_array($backups ?? null) ? $backups : [];
$zipAvailable = !empty($zipAvailable);
$canManageBackups = !empty($canManageBackups);
$backupStoragePath = trim((string) ($backupStoragePath ?? ''));
$totalBackupSize = (int) ($totalBackupSize ?? 0);
?>

<link rel="stylesheet" href="<?= module_asset('Backups', 'css/backups.css') ?><?= $backupsCssVersion !== '' ? '?v=' . rawurlencode($backupsCssVersion) : '' ?>">

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
        <p class="page-subtitle"><?= __('backups_subtitle', 'Backups') ?></p>
    </div>
</div>

<?php if (!$zipAvailable): ?>
    <div class="alert alert-warning">
        <div class="alert-content">
            <strong><?= __('backups_zip_missing_title', 'Backups') ?></strong>
            <span><?= __('backups_zip_missing', 'Backups') ?></span>
        </div>
        <button type="button" class="alert-close" aria-label="<?= __('close', 'Core') ?>">&times;</button>
    </div>
<?php endif; ?>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-box-archive"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('backups_help_badge', 'Backups') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('backups_help_title', 'Backups') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('backups_help_intro', 'Backups') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('backups_help_step_create', 'Backups') ?></li>
            <li><?= __('backups_help_step_restore', 'Backups') ?></li>
            <li><?= __('backups_help_step_reset', 'Backups') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <?php if ($canManageBackups): ?>
                <form method="POST" action="<?= url('/admin/backups/create') ?>" class="form-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary"<?= $zipAvailable ? '' : ' disabled' ?>>
                        <?= __('backups_help_action_create', 'Backups') ?>
                    </button>
                </form>
            <?php endif; ?>
            <a href="#backups-reset-card" class="btn btn-secondary"><?= __('backups_help_action_reset', 'Backups') ?></a>
        </div>
    </div>
</div>

<div class="backups-grid">
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title"><?= __('backups_create_title', 'Backups') ?></h3>
                <p class="module-installer-hint"><?= __('backups_create_hint', 'Backups') ?></p>
            </div>
        </div>
        <div class="card-body">
            <div class="backups-stats">
                <div class="backups-stat">
                    <span class="backups-stat-label"><?= __('backups_stat_archives', 'Backups') ?></span>
                    <strong class="backups-stat-value"><?= e((string) count($backups)) ?></strong>
                </div>
                <div class="backups-stat">
                    <span class="backups-stat-label"><?= __('backups_stat_total_size', 'Backups') ?></span>
                    <strong class="backups-stat-value"><?= e(human_size($totalBackupSize)) ?></strong>
                </div>
            </div>

            <dl class="backups-path-list">
                <div class="backups-path-row">
                    <dt><?= __('backups_storage_path', 'Backups') ?></dt>
                    <dd><?= e($backupStoragePath) ?></dd>
                </div>
                <div class="backups-path-row">
                    <dt><?= __('backups_scope_label', 'Backups') ?></dt>
                    <dd><?= __('backups_scope_value', 'Backups') ?></dd>
                </div>
            </dl>

            <?php if ($canManageBackups): ?>
                <form method="POST" action="<?= url('/admin/backups/create') ?>" class="backups-action-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary"<?= $zipAvailable ? '' : ' disabled' ?>>
                        <i class="fas fa-box-archive" aria-hidden="true"></i>
                        <?= __('backups_create_action', 'Backups') ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title"><?= __('backups_restore_title', 'Backups') ?></h3>
                <p class="module-installer-hint"><?= __('backups_restore_hint', 'Backups') ?></p>
            </div>
        </div>
        <div class="card-body">
            <div class="backups-warning">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <span><?= __('backups_restore_warning', 'Backups') ?></span>
            </div>

            <form method="POST" action="<?= url('/admin/backups/restore-upload') ?>" enctype="multipart/form-data" class="module-installer-form">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="backup_zip"><?= __('backups_upload_field', 'Backups') ?></label>
                    <input type="file" name="backup_zip" id="backup_zip" class="form-input" accept=".zip,application/zip" required>
                    <span class="form-hint"><?= __('backups_upload_hint', 'Backups') ?></span>
                </div>
                <?php if ($canManageBackups): ?>
                    <button
                        type="submit"
                        class="btn btn-secondary"
                        data-action="confirm-delete"
                        data-message="<?= e(__('backups_restore_confirm', 'Backups')) ?>"
                        data-confirm-text="<?= e(__('backups_restore_action', 'Backups')) ?>"
                        data-warning="<?= e(__('backups_restore_warning', 'Backups')) ?>"
                        data-item-name="<?= e(__('backups_restore_upload_item', 'Backups')) ?>"
                        <?= $zipAvailable ? '' : 'disabled' ?>
                    >
                        <i class="fas fa-upload" aria-hidden="true"></i>
                        <?= __('backups_restore_action', 'Backups') ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="card" id="backups-reset-card">
    <div class="card-header">
        <div>
            <h3 class="card-title"><?= __('backups_reset_title', 'Backups') ?></h3>
            <p class="module-installer-hint"><?= __('backups_reset_hint', 'Backups') ?></p>
        </div>
    </div>
    <div class="card-body">
        <div class="backups-warning">
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
            <span><?= __('backups_reset_warning', 'Backups') ?></span>
        </div>

        <?php if ($canManageBackups): ?>
            <form method="POST" action="<?= url('/admin/backups/reset') ?>" class="backups-action-form">
                <?= csrf_field() ?>
                <button
                    type="submit"
                    class="btn btn-danger"
                    data-action="confirm-delete"
                    data-message="<?= e(__('backups_reset_confirm', 'Backups')) ?>"
                    data-confirm-text="<?= e(__('backups_reset_action', 'Backups')) ?>"
                    data-warning="<?= e(__('backups_reset_warning', 'Backups')) ?>"
                    data-item-name="<?= e(__('backups_reset_title', 'Backups')) ?>"
                    <?= $zipAvailable ? '' : 'disabled' ?>
                >
                    <i class="fas fa-rotate-left" aria-hidden="true"></i>
                    <?= __('backups_reset_action', 'Backups') ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card" id="backups-factory-reset-card">
    <div class="card-header">
        <div>
            <h3 class="card-title"><?= __('backups_factory_reset_title', 'Backups') ?></h3>
            <p class="module-installer-hint"><?= __('backups_factory_reset_hint', 'Backups') ?></p>
        </div>
    </div>
    <div class="card-body">
        <div class="backups-warning backups-warning-danger">
            <i class="fas fa-skull-crossbones" aria-hidden="true"></i>
            <span><?= __('backups_factory_reset_warning', 'Backups') ?></span>
        </div>

        <?php if ($canManageBackups): ?>
            <form method="POST" action="<?= url('/admin/backups/factory-reset') ?>" class="backups-action-form">
                <?= csrf_field() ?>
                <button
                    type="submit"
                    class="btn btn-danger"
                    data-action="confirm-delete"
                    data-message="<?= e(__('backups_factory_reset_confirm', 'Backups')) ?>"
                    data-confirm-text="<?= e(__('backups_factory_reset_action', 'Backups')) ?>"
                    data-warning="<?= e(__('backups_factory_reset_warning', 'Backups')) ?>"
                    data-item-name="<?= e(__('backups_factory_reset_title', 'Backups')) ?>"
                    <?= $zipAvailable ? '' : 'disabled' ?>
                >
                    <i class="fas fa-power-off" aria-hidden="true"></i>
                    <?= __('backups_factory_reset_action', 'Backups') ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card backups-table-card">
    <div class="card-header">
        <div>
            <h3 class="card-title"><?= __('backups_list_title', 'Backups') ?></h3>
            <p class="module-installer-hint"><?= __('backups_list_hint', 'Backups', ['count' => (string) count($backups)]) ?></p>
        </div>
    </div>

    <?php if ($backups === []): ?>
        <div class="card-body">
            <div class="admin-empty-state-panel">
                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                    <i class="fas fa-hard-drive"></i>
                </div>
                <h2 class="admin-empty-state-panel__title"><?= __('backups_empty_title', 'Backups') ?></h2>
                <p class="admin-empty-state-panel__text"><?= __('backups_empty_text', 'Backups') ?></p>
                <div class="admin-empty-state-panel__actions">
                    <?php if ($canManageBackups): ?>
                        <form method="POST" action="<?= url('/admin/backups/create') ?>" class="form-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary"<?= $zipAvailable ? '' : ' disabled' ?>>
                                <?= __('backups_empty_action_create', 'Backups') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="#backups-reset-card" class="btn btn-secondary"><?= __('backups_help_action_reset', 'Backups') ?></a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= __('backups_table_name', 'Backups') ?></th>
                        <th><?= __('backups_table_created', 'Backups') ?></th>
                        <th><?= __('backups_table_site', 'Backups') ?></th>
                        <th><?= __('backups_table_files', 'Backups') ?></th>
                        <th><?= __('backups_table_size', 'Backups') ?></th>
                        <th><?= __('backups_table_actions', 'Backups') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <?php
                        $filename = (string) ($backup['filename'] ?? '');
                        $reason = (string) ($backup['reason'] ?? 'manual');
                        $reasonKey = match ($reason) {
                            'pre_restore' => 'backups_reason_pre_restore',
                            'pre_reset' => 'backups_reason_pre_reset',
                            'pre_factory_reset' => 'backups_reason_pre_factory_reset',
                            'upload_restore' => 'backups_reason_upload_restore',
                            default => 'backups_reason_manual',
                        };
                        $siteName = trim((string) ($backup['site_name'] ?? ''));
                        $language = trim((string) ($backup['default_language'] ?? ''));
                        $createdBy = trim((string) ($backup['created_by'] ?? ''));
                        $sourceUrl = trim((string) ($backup['source_url'] ?? ''));
                        $jsonFilesCount = (int) ($backup['json_files_count'] ?? 0);
                        $mediaFilesCount = (int) ($backup['media_files_count'] ?? 0);
                        $totalFilesCount = (int) ($backup['total_files_count'] ?? ($jsonFilesCount + $mediaFilesCount));
                        ?>
                        <tr>
                            <td data-label="<?= __('backups_table_name', 'Backups') ?>">
                                <div class="backups-name">
                                    <strong><?= e($filename) ?></strong>
                                    <div class="backups-meta">
                                        <span class="badge badge-info"><?= __($reasonKey, 'Backups') ?></span>
                                        <?php if ($createdBy !== ''): ?>
                                            <span><?= __('backups_created_by', 'Backups', ['name' => $createdBy]) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="<?= __('backups_table_created', 'Backups') ?>">
                                <?= e((string) ($backup['created_at'] ?? '')) ?>
                            </td>
                            <td data-label="<?= __('backups_table_site', 'Backups') ?>">
                                <div class="backups-site">
                                    <strong><?= e($siteName !== '' ? $siteName : __('backups_site_unknown', 'Backups')) ?></strong>
                                    <div class="backups-meta">
                                        <?php if ($language !== ''): ?>
                                            <span><?= e($language) ?></span>
                                        <?php endif; ?>
                                        <?php if ($sourceUrl !== ''): ?>
                                            <span><?= e($sourceUrl) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-label="<?= __('backups_table_files', 'Backups') ?>">
                                <div class="backups-files">
                                    <strong><?= __('backups_table_files_total', 'Backups', ['count' => (string) $totalFilesCount]) ?></strong>
                                    <div class="backups-meta">
                                        <span><?= __('backups_table_files_json', 'Backups', ['count' => (string) $jsonFilesCount]) ?></span>
                                        <span><?= __('backups_table_files_media', 'Backups', ['count' => (string) $mediaFilesCount]) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="<?= __('backups_table_size', 'Backups') ?>">
                                <?= e(human_size((int) ($backup['size_bytes'] ?? 0))) ?>
                            </td>
                            <td data-label="<?= __('backups_table_actions', 'Backups') ?>" class="backups-actions-cell">
                                <div class="table-actions table-actions-compact backups-actions">
                                    <a
                                        href="<?= url('/admin/backups/download/' . rawurlencode($filename)) ?>"
                                        class="table-action table-action-download"
                                        title="<?= e(__('download', 'Core')) ?>"
                                        aria-label="<?= e(__('download', 'Core')) ?>"
                                    >
                                        <i class="fas fa-download" aria-hidden="true"></i>
                                    </a>
                                    <?php if ($canManageBackups): ?>
                                        <form method="POST" action="<?= url('/admin/backups/' . rawurlencode($filename) . '/restore') ?>" class="form-inline">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="table-action table-action-restore"
                                                data-action="confirm-delete"
                                                data-message="<?= e(__('backups_restore_confirm', 'Backups')) ?>"
                                                data-confirm-text="<?= e(__('backups_restore_action', 'Backups')) ?>"
                                                data-warning="<?= e(__('backups_restore_warning', 'Backups')) ?>"
                                                data-item-name="<?= e($filename) ?>"
                                                title="<?= e(__('backups_restore_action', 'Backups')) ?>"
                                                aria-label="<?= e(__('backups_restore_action', 'Backups')) ?>"
                                                <?= $zipAvailable ? '' : 'disabled' ?>
                                            >
                                                <i class="fas fa-rotate-left" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= url('/admin/backups/' . rawurlencode($filename) . '/delete') ?>" class="form-inline">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="table-action table-action-delete"
                                                data-action="confirm-delete"
                                                data-message="<?= e(__('backups_delete_confirm', 'Backups')) ?>"
                                                data-confirm-text="<?= e(__('delete', 'Core')) ?>"
                                                data-warning="<?= e(__('delete_warning', 'Core')) ?>"
                                                data-item-name="<?= e($filename) ?>"
                                                title="<?= e(__('backups_delete_action', 'Backups')) ?>"
                                                aria-label="<?= e(__('backups_delete_action', 'Backups')) ?>"
                                            >
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
