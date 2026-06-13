<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$uploadUrl = url('/admin/media/upload');
$adminFront = strtok($uploadUrl, '?') ?: $uploadUrl;
$defaultMediaModalConfig = [
    // Always use same front controller as uploadUrl (avoid /public mismatch)
    'apiImagesUrl' => $adminFront . '?path=admin/media/api/images',
    'apiFilesUrl' => $adminFront . '?path=admin/media/api/files',
    'uploadUrl' => $uploadUrl,
    'csrfToken' => csrf_token(),
    'uploadsBase' => url('/uploads'),
    'uploadFailedLabel' => __('upload_failed', 'Media'),
    'uploadInvalidLabel' => __('upload_invalid', 'Media'),
    'selectMediaLabel' => __('select_media', 'Media'),
    'libraryLabel' => __('library', 'Media'),
    'imagesLabel' => __('images', 'Media'),
    'videosLabel' => __('videos', 'Media'),
    'filesLabel' => __('files_label', 'Media'),
    'noImagesLabel' => __('no_images', 'Media'),
    'noMediaLabel' => __('no_media', 'Media'),
];
$mediaModalConfig = array_merge(
    $defaultMediaModalConfig,
    is_array($mediaModalConfig ?? null) ? $mediaModalConfig : []
);

$mediaModalConfigJson = e(json_encode($mediaModalConfig));

?>
<?php if (empty($GLOBALS['flatcms_media_css_loaded'])): ?>
    <?php $GLOBALS['flatcms_media_css_loaded'] = true; ?>
    <link rel="stylesheet" href="<?= module_asset('Media', 'css/media-module.css') ?>?v=<?= filemtime(BASE_PATH . '/app/Modules/Media/Assets/css/media-module.css') ?>">
<?php endif; ?>

<!-- Media Selection Modal -->
<div id="mediaModal" class="modal-overlay hidden" data-media-config="<?= $mediaModalConfigJson ?>">
    <div class="modal-container modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">
                <i id="mediaModalTitleIcon" class="fas fa-image media-modal-icon"></i>
                <span id="mediaModalTitleText"><?= __('select_media', 'Media') ?></span>
            </h3>
            <button type="button" class="modal-close" data-modal-close="mediaModal">&times;</button>
        </div>

        <div class="modal-body">
            <div class="media-modal-tabs">
                <button type="button" id="tabLibrary" class="media-modal-tab active" data-media-tab="library">
                    <i id="tabLibraryIcon" class="fas fa-image"></i>
                    <?= __('library', 'Media') ?>
                </button>
                <button type="button" id="tabUpload" class="media-modal-tab" data-media-tab="upload">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <?= __('upload', 'Media') ?>
                </button>
            </div>

            <div id="tabContentLibrary">
                <div id="mediaLibraryGrid" class="media-library-grid">
                    <!-- Images loaded via JS -->
                </div>
                <div id="mediaLibraryEmpty" class="media-empty hidden">
                    <div class="media-empty-icon">
                        <i id="mediaLibraryEmptyIcon" class="fas fa-image"></i>
                    </div>
                    <p id="mediaLibraryEmptyText"><?= __('no_images', 'Media') ?></p>
                    <button type="button" class="btn btn-primary btn-sm media-empty-upload-btn" data-media-tab="upload">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <?= __('upload', 'Media') ?>
                    </button>
                </div>
                <div id="mediaLibraryLoading" class="media-loading hidden">
                    <div class="spinner"></div>
                    <p><?= __('loading', 'Core') ?></p>
                </div>
            </div>

            <div id="tabContentUpload" class="hidden">
                <div id="modalDropZone" class="upload-zone">
                    <input type="file" id="modalFileInput" accept="image/*" class="media-file-input-hidden">

                    <div class="upload-zone-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>

                    <p class="upload-zone-text"><?= __('drop_message', 'Media') ?></p>
                    <p class="upload-zone-hint"><?= __('drop_hint', 'Media') ?></p>
                    <button type="button" class="btn btn-primary btn-sm" data-file-target="modalFileInput">
                        <i class="fas fa-plus"></i>
                        <?= __('select_files', 'Media') ?>
                    </button>
                </div>

                <div id="modalUploadProgress" class="upload-progress hidden">
                    <div class="upload-progress-header">
                        <span class="upload-progress-label"><?= __('uploading', 'Media') ?></span>
                        <span id="modalUploadPercent" class="upload-progress-percent">0%</span>
                    </div>
                    <div class="upload-progress-bar">
                        <div id="modalUploadBar" class="upload-progress-fill"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <div id="selectedMediaInfo" class="modal-footer-info"></div>
            <button type="button" class="btn btn-secondary" data-modal-close="mediaModal">
                <?= __('cancel', 'Core') ?>
            </button>
            <button type="button" id="btnSelectMedia" class="btn btn-primary" disabled>
                <i class="fas fa-check"></i>
                <?= __('select', 'Media') ?>
            </button>
        </div>
    </div>
</div>
