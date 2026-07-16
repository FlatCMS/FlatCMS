<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$folder = $folder ?? 'images';
$files = $files ?? [];
$stats = $stats ?? [];
$folderConfig = $folderConfig ?? [];
$publicUrl = $publicUrl ?? '';
$aiAgentEnabled = (bool) ($aiAgentEnabled ?? false);

// Couleurs par dossier
$folderColors = [
    'images' => 'blue',
    'videos' => 'red',
    'sounds' => 'green',
    'documents' => 'yellow',
];
$color = $folderColors[$folder] ?? 'blue';

$mediaConfig = [
    'uploadUrl' => url('/admin/media/upload'),
    'aiIndexUrl' => url('/admin/media/ai-index'),
    'deleteUrl' => url('/admin/media'),
    'deletePathUrl' => url('/admin/media/delete-path'),
    'batchDeleteUrl' => url('/admin/media/batch-delete'),
    'csrfToken' => csrf_token(),
    'currentFolder' => $folder,
    'folderConfig' => $folderConfig,
    'i18n' => [
        'copy_url' => __('copy_url', 'Media'),
        'delete' => __('delete', 'Core'),
        'select' => __('select', 'Media'),
        'media_ai_indexing' => __('media_ai_indexing', 'Media'),
        'media_ai_leave_message' => __('media_ai_leave_message', 'Media'),
        'media_ai_leave_warning' => __('media_ai_leave_warning', 'Media'),
        'media_ai_leave_confirm' => __('media_ai_leave_confirm', 'Media'),
        'media_ai_index_failed' => __('media_ai_index_failed', 'Media'),
        'media_batch_selected_count' => __('media_batch_selected_count', 'Media', ['count' => ':count']),
        'media_batch_delete_items_label' => __('media_batch_delete_items_label', 'Media', ['count' => ':count']),
    ],
];

$mediaConfigJson = e(json_encode($mediaConfig));
?>

<link rel="stylesheet" href="<?= module_asset('Media', 'css/media-module.css') ?>?v=<?= filemtime(BASE_PATH . '/app/Modules/Media/Assets/css/media-module.css') ?>">

<div id="mediaConfig" class="hidden" data-media-config="<?= $mediaConfigJson ?>"></div>

<!-- Page Header with Breadcrumb -->
<div class="page-header">
    <div class="page-header-content">
        <div class="media-breadcrumb">
            <a href="<?= url('/admin/media') ?>">
                <i class="fas fa-arrow-left"></i>
            </a>
            <a href="<?= url('/admin/media') ?>"><?= __('title', 'Media') ?></a>
            <i class="fas fa-chevron-right"></i>
            <span class="media-breadcrumb-current"><?= __($folder, 'Media') ?></span>
        </div>
        <h1 class="page-title"><?= __($folder, 'Media') ?></h1>
        <p class="page-subtitle"><?= count($files) ?> <?= __('files', 'Media') ?></p>
    </div>
    <div class="page-header-actions">
        <?php if ($aiAgentEnabled): ?>
            <button type="button" class="btn btn-secondary" data-action="media-ai-index" data-ai-scope="folder" data-folder="<?= e($folder) ?>">
                <i class="fas fa-robot"></i>
                <?= __('media_ai_index', 'Media') ?>
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" data-action="media-upload-open">
            <i class="fas fa-plus"></i>
            <?= __('upload', 'Media') ?>
        </button>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-photo-film"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('media_help_badge', 'Media') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('media_help_title', 'Media') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('media_tour_initial_content', 'Media') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('media_help_step_upload', 'Media') ?></li>
            <li><?= __('media_tour_files_content', 'Media') ?></li>
            <li><?= __('media_help_step_sync', 'Media') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/media') ?>" class="btn btn-primary"><?= __('title', 'Media') ?></a>
        </div>
    </div>
</div>

<!-- Files Grid -->
<div class="card">
    <div class="card-body">
        <?php if (empty($files)): ?>
        <!-- Empty State -->
        <div class="media-empty">
            <div class="media-empty-icon">
                <i class="fas fa-image"></i>
            </div>
            <h3 class="media-empty-title"><?= __('empty_folder', 'Media') ?></h3>
            <p class="media-empty-text"><?= __('empty_folder_message', 'Media') ?></p>
            <button type="button" class="btn btn-primary" data-action="media-upload-open">
                <i class="fas fa-plus"></i>
                <?= __('upload_first', 'Media') ?>
            </button>
        </div>
        <?php else: ?>
        <form
            method="POST"
            action="<?= url('/admin/media/batch-delete') ?>"
            class="media-batch-form"
            data-media-batch-form
            data-empty-selection-message="<?= e(__('media_batch_no_selection', 'Media')) ?>"
            data-selected-template="<?= e(__('media_batch_selected_count', 'Media', ['count' => ':count'])) ?>"
            data-delete-message="<?= e(__('media_batch_confirm_delete', 'Media')) ?>"
            data-delete-warning="<?= e(__('media_batch_delete_warning', 'Media')) ?>"
            data-delete-item-template="<?= e(__('media_batch_delete_items_label', 'Media', ['count' => ':count'])) ?>"
        >
            <?= csrf_field() ?>
            <input type="hidden" name="folder" value="<?= e($folder) ?>">
            <div class="media-batch-controls">
                <label class="media-batch-select-all">
                    <input type="checkbox" data-media-select-all>
                    <span><?= __('media_batch_select_all', 'Media') ?></span>
                </label>
                <span class="media-batch-count" data-media-batch-count><?= __('media_batch_selected_count', 'Media', ['count' => '0']) ?></span>
                <button
                    type="submit"
                    class="btn btn-sm btn-secondary"
                    data-media-batch-submit
                    data-confirm-text="<?= e(__('delete', 'Core')) ?>"
                    disabled
                >
                    <?= __('media_batch_delete', 'Media') ?>
                </button>
            </div>
            <div data-media-batch-paths></div>
        </form>

        <!-- Media Grid -->
        <div class="media-grid">
            <?php foreach ($files as $file): ?>
            <div class="media-item" data-id="<?= $file['id'] ?? 0 ?>" data-path="<?= e($file['path'] ?? '') ?>">
                <!-- Preview -->
                <div class="media-item-preview">
                    <label class="media-item-select-toggle" aria-label="<?= e(__('select', 'Media')) ?>">
                        <input type="checkbox" class="form-checkbox media-item-select-checkbox" data-media-select value="<?= e((string) ($file['path'] ?? '')) ?>">
                    </label>
                    <?php if (str_starts_with($file['mime'] ?? '', 'image/')): ?>
                    <img src="<?= e($file['url'] ?? '') ?>" alt="<?= e($file['original_name'] ?? '') ?>" loading="lazy">
                    <?php elseif (str_starts_with($file['mime'] ?? '', 'video/')): ?>
                    <div class="media-item-preview-icon video">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <?php elseif (str_starts_with($file['mime'] ?? '', 'audio/')): ?>
                    <div class="media-item-preview-icon audio">
                        <i class="fas fa-music"></i>
                    </div>
                    <?php else: ?>
                    <div class="media-item-preview-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Hover Overlay -->
                    <div class="media-item-overlay">
                        <button type="button" class="media-item-action" data-action="media-copy-url" data-url="<?= e($file['url'] ?? '') ?>" title="<?= __('copy_url', 'Media') ?>">
                            <i class="fas fa-clipboard"></i>
                        </button>
                        <button type="button" class="media-item-action delete" data-action="media-delete-open" data-id="<?= $file['id'] ?? 0 ?>" data-name="<?= e($file['original_name'] ?? '') ?>" data-path="<?= e($file['path'] ?? '') ?>" title="<?= __('delete', 'Core') ?>">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Info -->
                <div class="media-item-info">
                    <p class="media-item-name" title="<?= e($file['original_name'] ?? '') ?>">
                        <?= e($file['original_name'] ?? '') ?>
                    </p>
                    <p class="media-item-meta">
                        <?= \App\Modules\Media\Models\MediaModel::formatSize($file['size'] ?? 0) ?>
                        <?php if (!empty($file['dimensions'])): ?>
                        • <?= $file['dimensions']['width'] ?>x<?= $file['dimensions']['height'] ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="modal-overlay hidden">
    <div class="modal-container modal-md">
        <div class="modal-header">
            <h3 class="modal-title"><?= __('upload_to', 'Media') ?> <?= __($folder, 'Media') ?></h3>
            <button type="button" class="modal-close" data-modal-close="uploadModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="uploadForm" action="<?= url('/admin/media/upload') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="folder" value="<?= $folder ?>">
                
                <div id="dropZone" class="upload-zone">
                    <input type="file" name="files[]" id="fileInput" multiple class="media-file-input-hidden" accept="<?= $folderConfig['accept'] ?? '*/*' ?>">
                    
                    <div class="upload-zone-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    
                    <p class="upload-zone-text"><?= __('drop_message', 'Media') ?></p>
                    <p class="upload-zone-hint"><?= __('accepted_formats', 'Media') ?>: <?= implode(', ', \App\Modules\Media\Models\MediaModel::FOLDERS[$folder] ?? []) ?></p>
                    
                    <button type="button" class="btn btn-primary media-upload-trigger" data-file-target="fileInput">
                        <?= __('select_files', 'Media') ?>
                    </button>
                </div>
            </form>

            <!-- Upload Progress -->
            <div id="uploadProgress" class="upload-progress hidden">
                <div class="upload-progress-header">
                    <span class="upload-progress-label"><?= __('uploading', 'Media') ?></span>
                    <span id="uploadPercent" class="upload-progress-percent">0%</span>
                </div>
                <div class="upload-progress-bar">
                    <div id="uploadBar" class="upload-progress-fill"></div>
                </div>
            </div>

            <!-- Upload Results -->
            <div id="uploadResults" class="upload-results hidden">
                <div id="uploadList"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close="uploadModal"><?= __('close', 'Media') ?></button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay hidden">
    <div class="modal-container modal-sm">
        <div class="modal-header">
            <h3 class="modal-title"><?= __('delete_title', 'Media') ?></h3>
            <button type="button" class="modal-close" data-modal-close="deleteModal">&times;</button>
        </div>
        <div class="modal-body media-modal-body-center">
            <div class="media-delete-icon-box">
                <i class="fas fa-trash-alt fa-2x media-delete-icon"></i>
            </div>
            <p class="media-modal-text">
                <?= __('confirm_delete', 'Media') ?>
            </p>
            <p class="media-modal-warning"><?= __('delete_warning', 'Media') ?></p>
        </div>
        <form id="deleteForm" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="path" id="deletePath" value="">
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="deleteModal"><?= __('cancel', 'Core') ?></button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i>
                    <?= __('delete', 'Core') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script src="<?= module_asset('Media', 'js/media.js') ?>?v=<?= filemtime(BASE_PATH . '/app/Modules/Media/Assets/js/media.js') ?>"></script>
