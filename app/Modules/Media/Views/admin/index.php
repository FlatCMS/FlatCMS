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
$uploadDirectories = $uploadDirectories ?? [];
$totalFiles = $totalFiles ?? 0;
$directoryTree = $directoryTree ?? ['name' => 'uploads', 'path' => '', 'type' => 'directory', 'count' => 0, 'children' => []];
$publicUrl = $publicUrl ?? '';
$aiAgentEnabled = (bool) ($aiAgentEnabled ?? false);

// Dossiers réservés (non affichés) - gérés par d'autres modules
$reservedFolders = ['cache', 'files', 'logo', 'media', 'personal'];

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

// Icônes par dossier
$folderIcons = [
    'images' => 'fa-image',
    'videos' => 'fa-video',
    'sounds' => 'fa-music',
    'documents' => 'fa-file-alt',
    'pdf' => 'fa-file-pdf',
    'spreadsheets' => 'fa-file-excel',
    'archives' => 'fa-file-archive',
];

$uploadUrl = url('/admin/media/upload');
$adminFront = strtok($uploadUrl, '?') ?: $uploadUrl;
$mediaConfig = [
    'uploadUrl' => $uploadUrl,
    'syncUrl' => url('/admin/media/sync'),
    'aiIndexUrl' => url('/admin/media/ai-index'),
    // Always use same front controller as uploadUrl (avoid /public mismatch)
    'apiFilesUrl' => $adminFront . '?path=admin/media/api/files',
    'apiDirectoriesUrl' => $adminFront . '?path=admin/media/api/directories',
    'apiMoveUrl' => $adminFront . '?path=admin/media/api/move',
    'apiStatsUrl' => $adminFront . '?path=admin/media/api/stats',
    'createDirectoryUrl' => $adminFront . '?path=admin/media/api/directories',
    'deleteUrl' => url('/admin/media'),
    'deletePathUrl' => url('/admin/media/delete-path'),
    'batchDeleteUrl' => url('/admin/media/batch-delete'),
    'csrfToken' => csrf_token(),
    'folders' => $publicFolders,
    'directoryTree' => $directoryTree,
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
        'root_directory' => __('root_directory', 'Media'),
    ],
    'i18n' => \App\Core\I18n::all('Media'),
];

$mediaConfigJson = e(json_encode($mediaConfig));
$uploadAccept = $publicFolders['images']['accept'] ?? 'image/*';
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
        <?php if ($aiAgentEnabled): ?>
            <button type="button" class="btn btn-secondary" data-action="media-ai-index" data-ai-scope="all">
                <i class="fas fa-robot"></i>
                <?= __('media_ai_index', 'Media') ?>
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-secondary" data-action="media-sync-open">
            <i class="fas fa-sync-alt"></i>
            <?= __('sync', 'Media') ?>
        </button>
    </div>
</div>

<!-- Onglets de dossiers (pleine largeur) -->
<div class="media-tabs-section">
    <div class="media-tabs-section-title"><?= __('media_folders', 'Media') ?></div>
    <div class="media-tabs" id="mediaFolderTabs">
        <?php foreach ($publicFolders as $folderName => $config):
            $color = $folderColors[$folderName] ?? 'blue';
            $icon = $folderIcons[$folderName] ?? 'fa-file';
            $count = $stats[$folderName] ?? 0;
        ?>
        <button type="button"
                class="media-tab media-tab-<?= $color ?>"
                data-folder="<?= $folderName ?>"
                data-accept="<?= $config['accept'] ?? '*/*' ?>">
            <span class="media-tab-icon">
                <i class="fas <?= $icon ?>"></i>
            </span>
            <span class="media-tab-name"><?= __($folderName, 'Media') ?></span>
            <span class="media-tab-count"><?= $count ?> <?= __('files', 'Media') ?></span>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- Zone d'upload (pleine largeur) -->
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
                <input type="hidden" name="media_context" id="uploadContext" value="">

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

<!-- Media Container (Joomla-style layout) -->
<div class="media-container" data-tour-target="media-folders">

    <!-- Toolbar (pleine largeur) -->
    <div class="media-toolbar" data-tour-target="media-toolbar">
        <!-- Dropdown dossiers (disk/drive/tree) -->
        <details class="media-toolbar-drive-dropdown" id="mediaDriveDropdown">
            <summary class="media-toolbar-drive-trigger">
                <i class="fas fa-hdd"></i>
                <span class="media-toolbar-drive-label" id="mediaDriveLabel"><?= __('media_uploads_root', 'Media') ?></span>
                <i class="fas fa-chevron-down media-toolbar-drive-chevron"></i>
            </summary>
            <div class="media-toolbar-drive-panel">
                <div class="media-drive">
                    <ul class="media-tree" role="tree">
                        <li class="media-tree-item" data-folder-root>
                            <a role="treeitem" class="media-tree-root-link" tabindex="0">
                                <span class="item-icon"><i class="fas fa-hdd"></i></span>
                                <span class="item-name"><?= __('media_uploads_root', 'Media') ?></span>
                                <span class="item-count"><?= $totalFiles ?></span>
                            </a>
                            <ul class="media-tree" role="group">
                                <?php $__renderTree = static function(array $node, string $parentPath = '') use (&$__renderTree, $folderIcons): void {
                                    $itemPath = $node['path'];
                                    $isDir = $node['type'] === 'directory';
                                    $name = $node['name'];
                                    $ext = $node['extension'] ?? '';
                                ?>
                                <li class="media-tree-item <?= $isDir ? 'media-tree-folder' : 'media-tree-file' ?>" data-path="<?= e($itemPath) ?>">
                                    <?php if ($isDir): ?>
                                    <button type="button" class="media-tree-toggle" aria-label="<?= __('media_toggle_folder', 'Media') ?>"><i class="fas fa-chevron-right"></i></button>
                                    <?php endif; ?>
                                    <a role="treeitem" tabindex="-1" <?= $isDir ? 'data-select-folder="' . e($itemPath) . '"' : 'data-action="media-preview" data-url="' . e($node['url'] ?? '') . '" data-mime="' . e($node['mime'] ?? 'application/octet-stream') . '" data-name="' . e($name) . '"' ?>>
                                        <span class="item-icon">
                                            <?php if ($isDir): ?>
                                                <i class="fas fa-folder media-icon-collapsed"></i>
                                                <i class="fas fa-folder-open media-icon-expanded"></i>
                                            <?php else: ?>
                                                <?php
                                                    $fileIcon = 'fa-file-alt';
                                                    if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg','avif','bmp'])) $fileIcon = 'fa-file-image';
                                                    elseif (in_array($ext, ['mp4','webm','ogg','avi','mov','mkv'])) $fileIcon = 'fa-file-video';
                                                    elseif (in_array($ext, ['mp3','wav','ogg','flac','aac','wma'])) $fileIcon = 'fa-file-audio';
                                                    elseif (in_array($ext, ['pdf'])) $fileIcon = 'fa-file-pdf';
                                                    elseif (in_array($ext, ['zip','rar','tar','gz','7z'])) $fileIcon = 'fa-file-archive';
                                                    elseif (in_array($ext, ['csv','xls','xlsx'])) $fileIcon = 'fa-file-excel';
                                                ?>
                                                <i class="fas <?= $fileIcon ?>"></i>
                                            <?php endif; ?>
                                        </span>
                                        <span class="item-name"><?= e($name) ?></span>
                                        <?php if ($isDir && $node['count'] > 0): ?>
                                        <span class="item-count"><?= $node['count'] ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <?php if ($isDir && !empty($node['children'])): ?>
                                    <ul class="media-tree media-tree-children" role="group">
                                        <?php foreach ($node['children'] as $child): ?>
                                        <?php $__renderTree($child, $itemPath); ?>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>
                                </li>
                                <?php }; ?>
                                <?php foreach ($directoryTree['children'] ?? [] as $child): ?>
                                <?php $__renderTree($child, ''); ?>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </details>

        <div class="media-toolbar-btns">
            <button type="button" class="media-toolbar-icon" title="<?= __('media_select_all', 'Media') ?>" data-toolbar-action="select-all">
                <i class="fas fa-check-double"></i>
            </button>
            <div class="media-toolbar-divider"></div>
            <button type="button" class="media-toolbar-icon" title="<?= __('media_sort', 'Media') ?>" data-toolbar-action="sort" data-sort-dir="asc">
                <i class="fas fa-sort-amount-down-alt"></i>
            </button>
            <button type="button" class="media-toolbar-icon" title="<?= __('media_grid_small', 'Media') ?>" data-toolbar-action="view-small">
                <i class="fas fa-search-minus"></i>
            </button>
            <button type="button" class="media-toolbar-icon active" title="<?= __('media_grid', 'Media') ?>" data-toolbar-action="view-grid">
                <i class="fas fa-th"></i>
            </button>
            <button type="button" class="media-toolbar-icon" title="<?= __('media_list', 'Media') ?>" data-toolbar-action="view-list">
                <i class="fas fa-list"></i>
            </button>
            <div class="media-toolbar-divider"></div>
            <button type="button" class="media-toolbar-icon" title="<?= __('media_infos', 'Media') ?>" data-toolbar-action="infobar-toggle">
                <i class="fas fa-info-circle"></i>
            </button>
            <div class="media-toolbar-divider"></div>
            <button type="button" class="media-toolbar-icon" title="<?= __('upload_files', 'Media') ?>" data-file-target="fileInput">
                <i class="fas fa-cloud-upload-alt"></i>
            </button>
            <button type="button" class="media-toolbar-icon" title="<?= __('media_empty_action_create_directory', 'Media') ?>" data-action="media-directory-create-open">
                <i class="fas fa-folder-plus"></i>
            </button>
        </div>

        <!-- Recherche -->
        <div class="media-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="<?= __('media_search_placeholder', 'Media') ?>" id="mediaSearchInput">
        </div>
    </div>

    <!-- Panneau principal -->
    <div class="media-main">

        <!-- Panneau d'exploration (caché par défaut) -->
        <div id="mediaDirectoryPanel" class="media-explorer hidden" data-tour-target="media-directories">

                <!-- Liste des fichiers (cachée par défaut) -->
                <div id="filesList" class="media-files-section hidden" data-tour-target="media-files-grid">
                    <div class="media-files-header">
                        <h3><span id="filesCount">0</span> <span id="filesInLabel"><?= __('files_in', 'Media') ?></span> <span id="mediaBreadcrumb" class="media-breadcrumb-inline"></span></h3>
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
                        <input type="hidden" name="media_context" id="mediaBatchContext" value="">
                        <div class="media-batch-controls">
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

                    <!-- Grille de fichiers (Joomla media-browser) -->
                    <div class="media-browser">
                        <div class="media-browser-grid">
                            <div id="filesGrid" class="media-browser-items">
                                <!-- Les fichiers seront insérés ici par JavaScript -->
                            </div>
                        </div>

                        <!-- Infobar (Joomla properties panel) -->
                        <div class="media-infobar" id="mediaInfobar">
                            <div class="media-infobar-inner">
                                <span class="infobar-close" id="btnCloseInfobar">&times;</span>
                                <h2 id="infobarFileName"></h2>
                                <dl>
                                    <dt><?= __('media_infobar_folder', 'Media') ?></dt>
                                    <dd id="infobarFolder">-</dd>
                                    <dt><?= __('media_infobar_type', 'Media') ?></dt>
                                    <dd id="infobarType">-</dd>
                                    <dt><?= __('media_infobar_created', 'Media') ?></dt>
                                    <dd id="infobarCreated">-</dd>
                                    <dt><?= __('media_infobar_modified', 'Media') ?></dt>
                                    <dd id="infobarModified">-</dd>
                                    <dt><?= __('media_infobar_dimensions', 'Media') ?></dt>
                                    <dd id="infobarDimensions">-</dd>
                                    <dt><?= __('media_infobar_size', 'Media') ?></dt>
                                    <dd id="infobarSize">-</dd>
                                    <dt><?= __('media_infobar_mime', 'Media') ?></dt>
                                    <dd id="infobarMime">-</dd>
                                    <dt><?= __('media_infobar_extension', 'Media') ?></dt>
                                    <dd id="infobarExtension">-</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div id="filesLoading" class="media-loading hidden">
                        <div class="spinner"></div>
                        <p><?= __('loading', 'Core') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Directory Modal -->
<div id="directoryModal" class="modal-overlay hidden">
    <div class="modal-container modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-folder-plus"></i>
                <?= __('create_directory', 'Media') ?>
            </h3>
            <button type="button" class="modal-close" data-modal-close="directoryModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="directoryForm" class="media-directory-form">
                <label for="directoryName" class="form-label"><?= __('directory_name', 'Media') ?></label>
                <input type="text" id="directoryName" class="form-input" autocomplete="off">
                <p class="form-hint"><?= __('directory_hint', 'Media') ?></p>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close="directoryModal"><?= __('cancel', 'Core') ?></button>
            <button type="button" class="btn btn-primary" data-action="media-directory-create-confirm">
                <i class="fas fa-check"></i>
                <?= __('create_directory', 'Media') ?>
            </button>
        </div>
    </div>
</div>

<!-- Rename Modal -->
<div id="renameModal" class="modal-overlay hidden">
    <div class="modal-container modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-i-cursor"></i>
                <?= __('rename', 'Media') ?>
            </h3>
            <button type="button" class="modal-close" data-modal-close="renameModal">&times;</button>
        </div>
        <div class="modal-body">
            <label for="renameInput" class="form-label"><?= __('new_name', 'Media') ?></label>
            <input type="text" id="renameInput" class="form-input" autocomplete="off">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-modal-close="renameModal"><?= __('cancel', 'Core') ?></button>
            <button type="button" class="btn btn-primary" data-action="media-rename-confirm">
                <i class="fas fa-check"></i>
                <?= __('rename', 'Media') ?>
            </button>
        </div>
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

<!-- Preview Modal -->
<div id="previewModal" class="modal-overlay hidden">
    <div class="modal-container modal-lg">
        <div class="modal-header">
            <h3 class="modal-title"><?= __('media_preview', 'Media') ?></h3>
            <button type="button" class="modal-close" data-modal-close="previewModal">&times;</button>
        </div>
        <div class="modal-body">
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

<script src="<?= module_asset('Media', 'js/media.js') ?>?v=<?= filemtime(BASE_PATH . '/app/Modules/Media/Assets/js/media.js') ?>"></script>
