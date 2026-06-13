<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$stats = $stats ?? [];
$foldersConfig = $foldersConfig ?? [];
$publicUrl = $publicUrl ?? '';

// Dossiers réservés (non affichés) - gérés par d'autres modules
$reservedFolders = ['personal'];

// Dossiers publics
$publicFolders = array_filter($foldersConfig, function($key) use ($reservedFolders) {
    return !in_array($key, $reservedFolders);
}, ARRAY_FILTER_USE_KEY);

// Configuration des couleurs
$folderColors = [
    'images' => 'blue',
    'videos' => 'red',
    'sounds' => 'green',
    'documents' => 'gray',
    'pdf' => 'orange',
    'spreadsheets' => 'teal',
    'archives' => 'yellow',
];

$uploadUrl = url('/admin/media/upload');
$adminFront = strtok($uploadUrl, '?') ?: $uploadUrl;
$mediaConfig = [
    'uploadUrl' => $uploadUrl,
    'syncUrl' => url('/admin/media/sync'),
    'aiIndexUrl' => url('/admin/media/ai-index'),
    // Always use same front controller as uploadUrl (avoid /public mismatch)
    'apiFilesUrl' => $adminFront . '?path=admin/media/api/files',
    'deleteUrl' => url('/admin/media'),
    'batchDeleteUrl' => url('/admin/media/batch-delete'),
    'csrfToken' => csrf_token(),
    'folders' => $publicFolders,
    'labels' => [
        'images' => __('images', 'Media'),
        'videos' => __('videos', 'Media'),
        'sounds' => __('sounds', 'Media'),
        'documents' => __('documents', 'Media'),
        'pdf' => __('pdf', 'Media'),
        'spreadsheets' => __('spreadsheets', 'Media'),
        'archives' => __('archives', 'Media'),
        'accepted_formats' => __('accepted_formats', 'Media'),
        'file_in' => __('file_in', 'Media'),
        'files_in' => __('files_in', 'Media'),
        'file_label' => __('file_label', 'Media'),
        'files_label' => __('files_label', 'Media'),
    ],
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

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
        <p class="page-subtitle"><?= __('subtitle', 'Media') ?></p>
    </div>
    <div class="page-header-actions" data-tour-target="media-toolbar">
        <button type="button" class="btn btn-secondary" data-action="media-ai-index" data-ai-scope="all">
            <i class="fas fa-robot"></i>
            <?= __('media_ai_index', 'Media') ?>
        </button>
        <button type="button" class="btn btn-secondary" data-action="media-sync-open">
            <i class="fas fa-sync-alt"></i>
            <?= __('sync', 'Media') ?>
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
            <p class="admin-guidance-card__copy"><?= __('media_help_intro', 'Media') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('media_help_step_folder', 'Media') ?></li>
            <li><?= __('media_help_step_upload', 'Media') ?></li>
            <li><?= __('media_help_step_sync', 'Media') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="#mediaFolderTabs" class="btn btn-primary"><?= __('media_help_action_folders', 'Media') ?></a>
            <button type="button" class="btn btn-secondary" data-action="media-ai-index" data-ai-scope="all"><?= __('media_ai_index', 'Media') ?></button>
            <button type="button" class="btn btn-secondary" data-action="media-sync-open"><?= __('media_help_action_sync', 'Media') ?></button>
        </div>
    </div>
</div>

<!-- Onglets des dossiers -->
<div id="mediaFolderTabs" class="media-tabs" data-tour-target="media-folders">
    <?php foreach ($publicFolders as $folderName => $config): 
        $color = $folderColors[$folderName] ?? 'blue';
        $count = $stats[$folderName] ?? 0;
    ?>
    <button type="button"
            class="media-tab media-tab-<?= $color ?>"
            data-folder="<?= $folderName ?>"
            data-accept="<?= $config['accept'] ?? '*/*' ?>">
        <span class="media-tab-icon">
            <?php echo match($folderName) {
                'images' => '<i class="fas fa-image"></i>',
                'videos' => '<i class="fas fa-video"></i>',
                'sounds' => '<i class="fas fa-music"></i>',
                'documents' => '<i class="fas fa-file-alt"></i>',
                'pdf' => '<i class="fas fa-file-pdf"></i>',
                'spreadsheets' => '<i class="fas fa-file-excel"></i>',
                'archives' => '<i class="fas fa-file-archive"></i>',
                default => '<i class="fas fa-file"></i>'
            }; ?>
        </span>
        <span class="media-tab-name"><?= __($folderName, 'Media') ?></span>
        <span class="media-tab-count"><?= $count ?> <?= __('files', 'Media') ?></span>
    </button>
    <?php endforeach; ?>
</div>

<!-- Message initial -->
<div id="initialMessage" class="media-initial-message" data-tour-target="media-initial-state">
    <div class="media-initial-icon">
        <i class="fas fa-folder-open"></i>
    </div>
    <h3><?= __('select_folder_title', 'Media') ?></h3>
    <p><?= __('select_folder_message', 'Media') ?></p>
    <div class="media-initial-actions">
        <a href="#mediaFolderTabs" class="btn btn-secondary"><?= __('media_initial_action_folders', 'Media') ?></a>
    </div>
</div>

<!-- Zone d'upload (cachée par défaut) -->
<div id="uploadZone" class="media-upload-section hidden" data-tour-target="media-upload-zone">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-cloud-upload-alt"></i>
                <?= __('upload_to', 'Media') ?> <span id="uploadFolderName" class="text-primary"></span>
            </h2>
        </div>
        <div class="card-body">
            <form id="uploadForm" action="<?= url('/admin/media/upload') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="folder" id="uploadFolder" value="">
                
                <div id="dropZone" class="upload-zone">
                    <input type="file" name="files[]" id="fileInput" multiple class="media-file-input-hidden">
                    
                    <div class="upload-zone-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    
                    <p class="upload-zone-text"><?= __('drop_message', 'Media') ?></p>
                    <p class="upload-zone-hint" id="acceptedFormats"></p>
                    
                    <button type="button" class="btn btn-primary media-upload-trigger" data-file-target="fileInput">
                        <i class="fas fa-plus"></i>
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
        </div>
    </div>
</div>

<!-- Liste des fichiers (cachée par défaut) -->
<div id="filesList" class="media-files-section hidden" data-tour-target="media-files-grid">
    <div class="media-files-header">
        <h3><span id="filesCount">0</span> <span id="filesInLabel"><?= __('files_in', 'Media') ?></span> <span id="currentFolderName" class="text-primary"></span></h3>
    </div>
    <form
        method="POST"
        action="<?= url('/admin/media/batch-delete') ?>"
        class="media-batch-form hidden"
        data-media-batch-form
        data-empty-selection-message="<?= e(__('media_batch_no_selection', 'Media')) ?>"
        data-selected-template="<?= e(__('media_batch_selected_count', 'Media', ['count' => ':count'])) ?>"
        data-delete-message="<?= e(__('media_batch_confirm_delete', 'Media')) ?>"
        data-delete-warning="<?= e(__('media_batch_delete_warning', 'Media')) ?>"
        data-delete-item-template="<?= e(__('media_batch_delete_items_label', 'Media', ['count' => ':count'])) ?>"
    >
        <?= csrf_field() ?>
        <input type="hidden" name="folder" id="mediaBatchFolder" value="">
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
    <div id="filesGrid" class="media-grid">
        <!-- Les fichiers seront insérés ici par JavaScript -->
    </div>
    <div id="filesEmpty" class="media-empty hidden">
        <div class="media-empty-icon">
            <i class="fas fa-folder-open"></i>
        </div>
        <h3 class="media-empty-title"><?= __('empty_folder', 'Media') ?></h3>
        <p class="media-empty-text"><?= __('empty_folder_message', 'Media') ?></p>
        <div class="media-empty-actions">
            <button type="button" class="btn btn-primary media-empty-upload-btn" data-file-target="fileInput"><?= __('media_empty_action_upload', 'Media') ?></button>
        </div>
    </div>
    <div id="filesLoading" class="media-loading hidden">
        <div class="spinner"></div>
        <p><?= __('loading', 'Core') ?></p>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal-overlay hidden">
    <div class="modal-container modal-sm">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-trash-alt media-delete-title-icon"></i> <?= __('delete_title', 'Media') ?></h3>
            <button type="button" class="modal-close" data-modal-close="deleteModal">&times;</button>
        </div>
        <div class="modal-body media-modal-body-center">
            <p class="media-modal-text">
                <?= __('confirm_delete', 'Media') ?>
            </p>
            <p class="media-modal-warning"><?= __('delete_warning', 'Media') ?></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close="deleteModal"><?= __('cancel', 'Core') ?></button>
            <button type="button" class="btn btn-danger" data-action="media-delete-confirm">
                <i class="fas fa-trash-alt"></i>
                <?= __('delete', 'Core') ?>
            </button>
        </div>
    </div>
</div>

<!-- Sync Modal -->
<div id="syncModal" class="modal-overlay hidden">
    <div class="modal-container modal-sm">
        <div class="modal-header">
            <h3 class="modal-title"><?= __('sync_title', 'Media') ?></h3>
            <button type="button" class="modal-close" data-modal-close="syncModal">&times;</button>
        </div>
        <div class="modal-body media-modal-body-center">
            <div class="modal-icon media-sync-icon-wrap">
                <i class="fas fa-sync-alt fa-3x media-sync-icon"></i>
            </div>
            <p class="media-modal-text media-sync-progress"><?= __('sync_message', 'Media') ?></p>
            
            <div id="syncProgress" class="hidden media-sync-progress">
                <div class="spinner media-sync-spinner"></div>
                <p class="media-sync-text"><?= __('syncing', 'Media') ?></p>
            </div>
            
            <div id="syncResult" class="hidden media-sync-result">
                <p id="syncResultText" class="media-sync-result-text"></p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close="syncModal"><?= __('close', 'Media') ?></button>
            <button type="button" id="syncConfirmBtn" class="btn btn-primary" data-action="media-sync-confirm">
                <i class="fas fa-sync-alt"></i>
                <?= __('sync_now', 'Media') ?>
            </button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script src="<?= module_asset('Media', 'js/media.js') ?>?v=<?= filemtime(BASE_PATH . '/app/Modules/Media/Assets/js/media.js') ?>"></script>
