/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const config = getMediaConfig();
    let currentFolder = config.currentFolder || null;
    let deleteMediaId = null;
    let deleteMediaPath = null;
    let allowMediaLeave = false;
    let pendingAiPaths = loadPendingAiPaths();

    document.addEventListener('DOMContentLoaded', function() {
        bindMediaActions();
        initMediaBatchActions();
        setupDropZone();
        setupFileInput();
        setupModals();
        setupLeaveGuard();
    });

    function getMediaConfig() {
        const holder = document.getElementById('mediaConfig') || document.getElementById('mediaApp');
        if (holder && holder.dataset.mediaConfig) {
            try {
                return JSON.parse(holder.dataset.mediaConfig);
            } catch (e) {
                console.warn('Invalid media config', e);
            }
        }
        return window.mediaConfig || {};
    }

    function getLabel(key, fallback) {
        if (config && config.i18n && typeof config.i18n[key] === 'string' && config.i18n[key].trim() !== '') {
            return config.i18n[key];
        }

        return String(fallback || '');
    }

    function showElement(el) {
        if (el) el.classList.remove('hidden');
    }

    function hideElement(el) {
        if (el) el.classList.add('hidden');
    }

    function openModal(id) {
        showElement(document.getElementById(id));
    }

    function closeModal(id) {
        hideElement(document.getElementById(id));
    }

    function storageKey() {
        return 'flatcms.media.pending-ai-paths';
    }

    function loadPendingAiPaths() {
        try {
            const raw = window.sessionStorage.getItem(storageKey());
            const decoded = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(decoded)) {
                return [];
            }

            return decoded
                .map(function(path) {
                    return String(path || '').trim();
                })
                .filter(function(path) {
                    return path !== '';
                });
        } catch (error) {
            return [];
        }
    }

    function persistPendingAiPaths() {
        try {
            if (pendingAiPaths.length === 0) {
                window.sessionStorage.removeItem(storageKey());
                return;
            }

            window.sessionStorage.setItem(storageKey(), JSON.stringify(pendingAiPaths));
        } catch (error) {
            console.warn('Unable to persist media AI pending paths', error);
        }
    }

    function addPendingAiPaths(paths) {
        var next = Array.isArray(paths) ? paths : [];
        var merged = pendingAiPaths.slice();

        next.forEach(function(path) {
            var normalized = String(path || '').trim();
            if (normalized === '' || merged.indexOf(normalized) !== -1) {
                return;
            }

            merged.push(normalized);
        });

        pendingAiPaths = merged;
        persistPendingAiPaths();
    }

    function removePendingAiPaths(paths) {
        var toRemove = Array.isArray(paths) ? paths : [];
        if (toRemove.length === 0 || pendingAiPaths.length === 0) {
            return;
        }

        var blacklist = toRemove
            .map(function(path) {
                return String(path || '').trim();
            })
            .filter(function(path) {
                return path !== '';
            });

        if (blacklist.length === 0) {
            return;
        }

        pendingAiPaths = pendingAiPaths.filter(function(path) {
            return blacklist.indexOf(path) === -1;
        });
        persistPendingAiPaths();
    }

    function hasPendingAiPaths() {
        return pendingAiPaths.length > 0;
    }

    function prunePendingAiPathsForFolder(folderName, files) {
        var folder = String(folderName || '').trim();
        if (folder === '' || pendingAiPaths.length === 0) {
            return;
        }

        var existing = Array.isArray(files) ? files.map(function(file) {
            return file ? String(file.path || '').trim() : '';
        }).filter(function(path) {
            return path !== '';
        }) : [];

        pendingAiPaths = pendingAiPaths.filter(function(path) {
            if (path.indexOf(folder + '/') !== 0) {
                return true;
            }

            return existing.indexOf(path) !== -1;
        });

        persistPendingAiPaths();
    }

    function getMediaModalApi() {
        if (window.FlatCMS && window.FlatCMS.modal && typeof window.FlatCMS.modal.confirm === 'function') {
            return window.FlatCMS.modal;
        }

        return null;
    }

    function isMediaAdminUrl(rawUrl) {
        try {
            var parsed = new URL(rawUrl, window.location.href);
            var path = String(parsed.pathname || '');
            var route = String(parsed.searchParams.get('path') || '');

            if (path.indexOf('/admin/media') !== -1) {
                return true;
            }

            return route.indexOf('admin/media') === 0;
        } catch (error) {
            return false;
        }
    }

    function confirmLeaveMedia(callback) {
        var modalApi = getMediaModalApi();
        var message = getLabel('media_ai_leave_message', 'Newly uploaded files are still waiting for AI indexing.');
        var warning = getLabel('media_ai_leave_warning', 'Leave the media library without indexing them now?');

        if (!modalApi) {
            var confirmed = window.confirm(message + '\n\n' + warning);
            if (confirmed && typeof callback === 'function') {
                callback();
            }
            return;
        }

        modalApi.confirm(message, function() {
            allowMediaLeave = true;
            if (typeof callback === 'function') {
                callback();
            }
        }, {
            warning: warning,
            confirmText: getLabel('media_ai_leave_confirm', 'Leave without indexing'),
        });
    }

    function setupLeaveGuard() {
        document.addEventListener('click', function(event) {
            var link = event.target.closest('a[href]');
            if (!(link instanceof HTMLAnchorElement)) {
                return;
            }

            if (!hasPendingAiPaths() || allowMediaLeave) {
                return;
            }

            var href = link.getAttribute('href') || '';
            if (href === '' || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
                return;
            }

            var targetUrl = link.href || href;
            if (isMediaAdminUrl(targetUrl)) {
                return;
            }

            event.preventDefault();
            confirmLeaveMedia(function() {
                window.location.href = targetUrl;
            });
        }, true);

        window.addEventListener('beforeunload', function(event) {
            if (!hasPendingAiPaths() || allowMediaLeave) {
                return;
            }

            event.preventDefault();
            event.returnValue = getLabel('media_ai_leave_message', 'Newly uploaded files are still waiting for AI indexing.');
            return event.returnValue;
        });
    }

    function syncBatchHiddenInputs(container, paths) {
        if (!container) {
            return;
        }

        container.innerHTML = '';
        paths.forEach(function(path) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'paths[]';
            input.value = String(path || '');
            container.appendChild(input);
        });
    }

    function getMediaCheckboxes() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-media-select]'));
    }

    function getSelectedMediaPaths() {
        return getMediaCheckboxes()
            .filter(function(checkbox) {
                return checkbox instanceof HTMLInputElement && checkbox.checked && !checkbox.disabled;
            })
            .map(function(checkbox) {
                return String(checkbox.value || '').trim();
            })
            .filter(function(path) {
                return path !== '';
            });
    }

    function syncMediaSelectionVisuals() {
        getMediaCheckboxes().forEach(function(checkbox) {
            var card = checkbox.closest('.media-item');
            if (!card) {
                return;
            }

            card.classList.toggle('is-selected', !!checkbox.checked);
        });
    }

    function setMediaBatchVisibility(hasFiles) {
        var form = document.querySelector('[data-media-batch-form]');
        var folderInput = document.getElementById('mediaBatchFolder');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (folderInput instanceof HTMLInputElement) {
            folderInput.value = hasFiles && currentFolder ? String(currentFolder) : '';
        }

        form.classList.toggle('hidden', !hasFiles);
    }

    function syncMediaBatchState() {
        var form = document.querySelector('[data-media-batch-form]');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var idsContainer = form.querySelector('[data-media-batch-paths]');
        var countLabel = form.querySelector('[data-media-batch-count]');
        var submitButton = form.querySelector('[data-media-batch-submit]');
        var selectAll = form.querySelector('[data-media-select-all]');
        var selectedPaths = getSelectedMediaPaths();
        var checkboxes = getMediaCheckboxes();

        syncBatchHiddenInputs(idsContainer, selectedPaths);
        syncMediaSelectionVisuals();

        if (countLabel) {
            countLabel.textContent = getLabel('media_batch_selected_count', ':count').replace(':count', String(selectedPaths.length));
        }

        if (selectAll instanceof HTMLInputElement) {
            var enabledCheckboxes = checkboxes.filter(function(checkbox) {
                return checkbox instanceof HTMLInputElement && !checkbox.disabled;
            });
            selectAll.checked = enabledCheckboxes.length > 0 && selectedPaths.length === enabledCheckboxes.length;
            selectAll.indeterminate = selectedPaths.length > 0 && selectedPaths.length < enabledCheckboxes.length;
        }

        if (submitButton instanceof HTMLButtonElement) {
            submitButton.disabled = selectedPaths.length === 0;

            if (selectedPaths.length > 0) {
                submitButton.setAttribute('data-action', 'confirm-delete');
                submitButton.setAttribute('data-message', form.getAttribute('data-delete-message') || '');
                submitButton.setAttribute('data-warning', form.getAttribute('data-delete-warning') || '');
                submitButton.setAttribute('data-item-name', getLabel('media_batch_delete_items_label', ':count').replace(':count', String(selectedPaths.length)));
            } else {
                submitButton.removeAttribute('data-action');
                submitButton.removeAttribute('data-message');
                submitButton.removeAttribute('data-warning');
                submitButton.removeAttribute('data-item-name');
            }
        }
    }

    function initMediaBatchActions() {
        var form = document.querySelector('[data-media-batch-form]');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var selectAll = form.querySelector('[data-media-select-all]');
        var emptySelectionMessage = String(form.getAttribute('data-empty-selection-message') || '').trim();

        document.addEventListener('change', function(event) {
            var target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
            }

            if (target.matches('[data-media-select-all]')) {
                getMediaCheckboxes().forEach(function(checkbox) {
                    if (!(checkbox instanceof HTMLInputElement) || checkbox.disabled) {
                        return;
                    }
                    checkbox.checked = target.checked;
                });
                syncMediaBatchState();
                return;
            }

            if (target.matches('[data-media-select]')) {
                syncMediaBatchState();
            }
        });

        form.addEventListener('submit', function(event) {
            if (getSelectedMediaPaths().length > 0) {
                return;
            }

            event.preventDefault();
            showToast(emptySelectionMessage, 'warning');
        });

        if (selectAll instanceof HTMLInputElement) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }

        syncMediaBatchState();
    }

    function bindMediaActions() {
        document.addEventListener('click', function(e) {
            const modalClose = e.target.closest('[data-modal-close]');
            if (modalClose) {
                e.preventDefault();
                handleModalClose(modalClose.dataset.modalClose);
                return;
            }

            const fileTrigger = e.target.closest('[data-file-target]');
            if (fileTrigger) {
                e.preventDefault();
                const targetId = fileTrigger.dataset.fileTarget;
                const input = document.getElementById(targetId);
                if (input) input.click();
                return;
            }

            const tab = e.target.closest('.media-tab');
            if (tab && tab.dataset.folder) {
                e.preventDefault();
                selectFolder(tab.dataset.folder);
                return;
            }

            const actionEl = e.target.closest('[data-action]');
            if (!actionEl) return;

            const action = actionEl.dataset.action;
            switch (action) {
                case 'media-sync-open':
                    e.preventDefault();
                    openSyncModal();
                    break;
                case 'media-sync-confirm':
                    e.preventDefault();
                    confirmSync();
                    break;
                case 'media-delete-confirm':
                    e.preventDefault();
                    confirmDeleteMedia();
                    break;
                case 'media-upload-open':
                    e.preventDefault();
                    openUploadModal();
                    break;
                case 'media-copy-url':
                    e.preventDefault();
                    copyUrl(actionEl.dataset.url || '');
                    break;
                case 'media-delete-open':
                    e.preventDefault();
                    openDeleteModal(
                        Number(actionEl.dataset.id || 0),
                        actionEl.dataset.name || '',
                        actionEl.dataset.path || ''
                    );
                    break;
                case 'media-ai-index':
                    e.preventDefault();
                    confirmAiIndex(actionEl);
                    break;
                default:
                    break;
            }
        });
    }

    function handleModalClose(modalId) {
        if (!modalId) return;
        if (modalId === 'deleteModal') {
            closeDeleteModal();
            return;
        }
        if (modalId === 'syncModal') {
            closeSyncModal();
            return;
        }
        if (modalId === 'uploadModal') {
            closeUploadModal();
            return;
        }
        closeModal(modalId);
    }

    /**
     * Sélectionner un dossier (onglet)
     */
    function selectFolder(folderName) {
        currentFolder = folderName;
        
        document.querySelectorAll('.media-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.folder === folderName);
        });
        
        const initialMessage = document.getElementById('initialMessage');
        hideElement(initialMessage);
        
        const uploadZone = document.getElementById('uploadZone');
        showElement(uploadZone);
        
        const uploadFolderName = document.getElementById('uploadFolderName');
        if (uploadFolderName) {
            const label = (config.labels && config.labels[folderName]) || folderName;
            uploadFolderName.textContent = label;
        }
        
        const uploadFolderInput = document.getElementById('uploadFolder');
        if (uploadFolderInput) uploadFolderInput.value = folderName;
        
        const folderConfig = (config.folders || {})[folderName];
        const fileInput = document.getElementById('fileInput');
        const acceptedFormats = document.getElementById('acceptedFormats');
        
        if (fileInput && folderConfig) {
            fileInput.accept = folderConfig.accept || '*/*';
        }
        if (acceptedFormats && folderConfig) {
            const acceptedLabel = (config.labels && config.labels.accepted_formats) || 'Formats acceptés';
            acceptedFormats.textContent = acceptedLabel + ': ' + (folderConfig.extensions || []).join(', ');
        }
        
        const filesList = document.getElementById('filesList');
        showElement(filesList);
        
        const currentFolderName = document.getElementById('currentFolderName');
        if (currentFolderName) {
            const label = (config.labels && config.labels[folderName]) || folderName;
            currentFolderName.textContent = label;
        }
        
        loadFiles(folderName);
    }

    window.selectFolder = selectFolder;

    function getFrontControllerPath() {
        const currentPath = window.location.pathname || '';
        if (currentPath.includes('index.php')) {
            return currentPath;
        }
        if (currentPath.startsWith('/public/')) {
            return '/public/index.php';
        }
        return '/index.php';
    }

    function buildApiUrlFromLocation(pathValue, params) {
        const origin = window.location.origin || '';
        const front = getFrontControllerPath();
        const searchParams = new URLSearchParams();
        searchParams.set('path', pathValue);
        Object.keys(params || {}).forEach((key) => {
            searchParams.set(key, params[key]);
        });
        return origin + front + '?' + searchParams.toString();
    }

    function buildFallbackApiUrl(folderName) {
        return buildApiUrlFromLocation('admin/media/api/files', { folder: folderName });
    }

    function buildPublicFallbackApiUrl(folderName) {
        const origin = window.location.origin || '';
        return origin + '/public/index.php?path=admin/media/api/files&folder=' + encodeURIComponent(folderName);
    }

    function getFileLabel(count, mode) {
        const labels = config.labels || {};
        if (mode === 'in') {
            const singular = labels.file_in || labels.files_in || 'file in';
            const plural = labels.files_in || singular;
            return count === 1 ? singular : plural;
        }
        const singular = labels.file_label || 'file';
        const plural = labels.files_label || singular + 's';
        return count === 1 ? singular : plural;
    }

    function updateFilesCountDisplay(count) {
        const filesCount = document.getElementById('filesCount');
        if (filesCount) filesCount.textContent = count;
        const filesInLabel = document.getElementById('filesInLabel');
        if (filesInLabel) filesInLabel.textContent = getFileLabel(count, 'in');
    }

    function fetchJsonWithFallback(url, fallbackUrls) {
        const fallbacks = Array.isArray(fallbackUrls) ? fallbackUrls : (fallbackUrls ? [fallbackUrls] : []);
        return fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'include',
            cache: 'no-store'
        })
            .then(response => response.text().then(text => ({
                text,
                url: response.url || url,
                contentType: response.headers.get('content-type') || ''
            })))
            .then(({ text, url, contentType }) => {
                const isJson = contentType.includes('application/json') || contentType.includes('text/json');
                if (isJson) {
                    return JSON.parse(text);
                }
                if (fallbacks.length) {
                    const next = fallbacks.shift();
                    if (next && next !== url) {
                        return fetchJsonWithFallback(next, fallbacks);
                    }
                }
                return JSON.parse(text);
            });
    }

    /**
     * Charger les fichiers d'un dossier via AJAX
     */
    function loadFiles(folderName) {
        const filesGrid = document.getElementById('filesGrid');
        const filesEmpty = document.getElementById('filesEmpty');
        const filesLoading = document.getElementById('filesLoading');
        const filesCount = document.getElementById('filesCount');
        
        if (filesGrid) filesGrid.innerHTML = '';
        setMediaBatchVisibility(false);
        hideElement(filesEmpty);
        showElement(filesLoading);
        
        const apiBase = config.apiFilesUrl || '';
        const filesUrl = apiBase + (apiBase.includes('?') ? '&' : '?') + 'folder=' + encodeURIComponent(folderName);
        const primaryUrl = buildApiUrlFromLocation('admin/media/api/files', { folder: folderName });
        const fallbackUrl = buildFallbackApiUrl(folderName);
        const publicFallbackUrl = buildPublicFallbackApiUrl(folderName);
        fetchJsonWithFallback(primaryUrl, [filesUrl, fallbackUrl, publicFallbackUrl])
        .then(data => {
            hideElement(filesLoading);
            
            if (data.success && data.files && data.files.length > 0) {
                prunePendingAiPathsForFolder(folderName, data.files);
                updateFilesCountDisplay(data.files.length);
                renderFiles(data.files);
            } else {
                prunePendingAiPathsForFolder(folderName, []);
                updateFilesCountDisplay(0);
                setMediaBatchVisibility(false);
                showElement(filesEmpty);
            }
        })
        .catch(error => {
            console.error('Error loading files:', error);
            hideElement(filesLoading);
            setMediaBatchVisibility(false);
            showElement(filesEmpty);
        });
    }

    /**
     * Afficher les fichiers dans la grille
     */
    function renderFiles(files) {
        const filesGrid = document.getElementById('filesGrid');
        if (!filesGrid) return;
        
        filesGrid.innerHTML = '';
        setMediaBatchVisibility(files.length > 0);
        
        files.forEach(file => {
            const item = document.createElement('div');
            item.className = 'media-item';
            item.dataset.id = file.id || 0;
            item.dataset.path = file.path || '';
            
            // Extension badge
            const ext = (file.extension || file.name.split('.').pop() || '').toUpperCase();
            
            // Prévisualisation
            let preview = '';
            if (file.mime && file.mime.startsWith('image/')) {
                preview = `<img src="${escapeAttribute(file.url)}" alt="${escapeAttribute(file.original_name || file.name)}" loading="lazy">`;
            } else {
                // Icône selon le type
                let icon = 'fa-file-alt';
                let placeholderClass = 'media-item-preview-placeholder';
                
                if (file.mime && file.mime.startsWith('video/')) {
                    icon = 'fa-play-circle';
                    placeholderClass += ' media-item-preview-placeholder--video';
                } else if (file.mime && file.mime.startsWith('audio/')) {
                    icon = 'fa-music';
                    placeholderClass += ' media-item-preview-placeholder--audio';
                } else if (file.mime === 'application/pdf') {
                    icon = 'fa-file-pdf';
                    placeholderClass += ' media-item-preview-placeholder--pdf';
                } else if (file.mime && (file.mime.includes('spreadsheet') || file.mime.includes('excel') || file.extension === 'csv')) {
                    icon = 'fa-file-excel';
                    placeholderClass += ' media-item-preview-placeholder--sheet';
                } else if (file.mime && (file.mime.includes('zip') || file.mime.includes('rar') || file.mime.includes('archive'))) {
                    icon = 'fa-file-archive';
                    placeholderClass += ' media-item-preview-placeholder--archive';
                }
                
                preview = `<div class="${placeholderClass}">
                    <i class="fas ${icon}"></i>
                </div>`;
            }
            
            // Taille formatée
            const size = formatSizeMB(file.size || 0);
            
            // Date formatée
            const date = formatDate(file.created_at || '');
            
            item.innerHTML = `
                <div class="media-item-preview">
                    <label class="media-item-select-toggle" aria-label="${escapeAttribute(getLabel('select', 'Select'))}">
                        <input type="checkbox" class="form-checkbox media-item-select-checkbox" data-media-select value="${escapeAttribute(file.path || '')}">
                    </label>
                    ${preview}
                    <span class="media-item-badge">${ext}</span>
                    <div class="media-item-overlay">
                        <button type="button" class="media-item-action" data-action="media-copy-url" data-url="${escapeAttribute(file.url)}" title="${escapeAttribute(getLabel('copy_url', 'Copy URL'))}">
                            <i class="fas fa-link"></i>
                        </button>
                        <button type="button" class="media-item-action delete" data-action="media-delete-open" data-id="${file.id || 0}" data-name="${escapeAttribute(file.original_name || file.name)}" data-path="${escapeAttribute(file.path)}" title="${escapeAttribute(getLabel('delete', 'Delete'))}">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="media-item-info">
                    <p class="media-item-name" title="${escapeHtml(file.original_name || file.name)}">${escapeHtml(file.original_name || file.name)}</p>
                    <p class="media-item-meta">
                        <span>${size}</span>
                        <span class="media-item-separator">•</span>
                        <span class="media-item-date">${date}</span>
                    </p>
                </div>
            `;
            
            filesGrid.appendChild(item);
        });

        syncMediaBatchState();
    }

    /**
     * Configuration de la zone de drop
     */
    function setupDropZone() {
        const dropZone = document.getElementById('dropZone');
        if (!dropZone) return;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('dragover');
            }, false);
        });

        dropZone.addEventListener('drop', handleDrop, false);
        dropZone.addEventListener('click', function(e) {
            if (e.target === dropZone || e.target.closest('.upload-zone-icon') || e.target.closest('.upload-zone-text')) {
                document.getElementById('fileInput')?.click();
            }
        });
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function handleDrop(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0 && currentFolder) {
            uploadFiles(files);
        }
    }

    function setupFileInput() {
        const fileInput = document.getElementById('fileInput');
        if (!fileInput) return;

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0 && currentFolder) {
                uploadFiles(this.files);
            }
        });
    }

    /**
     * Upload des fichiers
     */
    function uploadFiles(files) {
        if (!currentFolder) {
            showToast('Sélectionnez un dossier', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('folder', currentFolder);
        formData.append('_token', config.csrfToken);

        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        const progressDiv = document.getElementById('uploadProgress');
        const progressBar = document.getElementById('uploadBar');
        const progressPercent = document.getElementById('uploadPercent');

        showElement(progressDiv);

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                if (progressBar) progressBar.style.width = percent + '%';
                if (progressPercent) progressPercent.textContent = percent + '%';
            }
        });

        xhr.addEventListener('load', function() {
            hideElement(progressDiv);
            
            try {
                const response = JSON.parse(xhr.responseText);

                if (response.success) {
                    addPendingAiPaths(extractUploadedPaths(response));
                    showToast(response.message || 'Upload réussi', 'success');
                    loadFiles(currentFolder);
                    updateTabCount(currentFolder);
                } else {
                    showToast(response.message || 'Erreur lors de l\'upload', 'error');
                }
            } catch (e) {
                console.error('Upload error:', e);
                showToast('Erreur serveur', 'error');
            }
            
            const fileInput = document.getElementById('fileInput');
            if (fileInput) fileInput.value = '';
        });

        xhr.addEventListener('error', function() {
            hideElement(progressDiv);
            showToast('Erreur réseau', 'error');
        });

        xhr.open('POST', config.uploadUrl);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    }

    /**
     * Mettre à jour le compteur d'un onglet
     */
    function updateTabCount(folderName) {
        const apiBase = config.apiFilesUrl || '';
        const filesUrl = apiBase + (apiBase.includes('?') ? '&' : '?') + 'folder=' + encodeURIComponent(folderName);
        const primaryUrl = buildApiUrlFromLocation('admin/media/api/files', { folder: folderName });
        const fallbackUrl = buildFallbackApiUrl(folderName);
        const publicFallbackUrl = buildPublicFallbackApiUrl(folderName);
        fetchJsonWithFallback(primaryUrl, [filesUrl, fallbackUrl, publicFallbackUrl])
        .then(data => {
            if (data.success) {
                const tab = document.querySelector(`.media-tab[data-folder="${folderName}"]`);
                if (tab) {
                    const countEl = tab.querySelector('.media-tab-count');
                    const countValue = data.count || (data.files ? data.files.length : 0);
                    if (countEl) {
                        countEl.textContent = countValue + ' ' + getFileLabel(countValue, 'label');
                    }
                }
                updateFilesCountDisplay(data.count || (data.files ? data.files.length : 0));
            }
        });
    }

    /**
     * Suppression
     */
    function openDeleteModal(id, name, path) {
        deleteMediaId = id;
        deleteMediaPath = path;
        
        const fileNameEl = document.getElementById('deleteFileName');
        if (fileNameEl) fileNameEl.textContent = name;

        const deleteForm = document.getElementById('deleteForm');
        if (deleteForm) {
            if (deleteMediaId > 0) {
                deleteForm.action = config.deleteUrl + '/' + deleteMediaId + '/delete';
            } else {
                deleteForm.action = config.deletePathUrl || (config.deleteUrl + '/delete-path');
            }
            const deletePathInput = document.getElementById('deletePath');
            if (deletePathInput) deletePathInput.value = deleteMediaPath || '';
        }
        
        openModal('deleteModal');
    }

    function closeDeleteModal() {
        closeModal('deleteModal');
        deleteMediaId = null;
        deleteMediaPath = null;
    }

    function confirmDeleteMedia() {
        if (!deleteMediaId && !deleteMediaPath) return;
        
        const formData = new FormData();
        formData.append('_token', config.csrfToken);
        
        let url = '';
        if (deleteMediaId > 0) {
            url = config.deleteUrl + '/' + deleteMediaId + '/delete';
        } else {
            url = config.deletePathUrl || (config.deleteUrl + '/delete-path');
            formData.append('path', deleteMediaPath);
        }
        
        fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            closeDeleteModal();
            if (data.success) {
                removePendingAiPaths([deleteMediaPath]);
                showToast(data.message || 'Fichier supprimé', 'success');
                loadFiles(currentFolder);
                updateTabCount(currentFolder);
            } else {
                showToast(data.message || 'Erreur', 'error');
            }
        })
        .catch(error => {
            closeDeleteModal();
            showToast('Erreur réseau', 'error');
        });
    }

    /**
     * Copier URL
     */
    function copyUrl(url) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('URL copiée', 'success');
        }).catch(() => {
            const input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            showToast('URL copiée', 'success');
        });
    }

    /**
     * Synchronisation
     */
    function openSyncModal() {
        openModal('syncModal');
    }

    function closeSyncModal() {
        closeModal('syncModal');
        
        const progress = document.getElementById('syncProgress');
        const result = document.getElementById('syncResult');
        const confirmBtn = document.getElementById('syncConfirmBtn');
        
        hideElement(progress);
        hideElement(result);
        if (confirmBtn) confirmBtn.disabled = false;
    }

    function confirmSync() {
        const progress = document.getElementById('syncProgress');
        const result = document.getElementById('syncResult');
        const resultText = document.getElementById('syncResultText');
        const confirmBtn = document.getElementById('syncConfirmBtn');
        
        showElement(progress);
        if (confirmBtn) confirmBtn.disabled = true;
        
        const formData = new FormData();
        formData.append('_token', config.csrfToken);
        
        fetch(config.syncUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideElement(progress);
            showElement(result);
            if (resultText) resultText.textContent = data.message || 'Synchronisation terminée';
            
            setTimeout(() => {
                closeSyncModal();
                window.location.reload();
            }, 2000);
        })
        .catch(error => {
            hideElement(progress);
            showToast('Erreur', 'error');
            if (confirmBtn) confirmBtn.disabled = false;
        });
    }

    function confirmAiIndex(actionEl) {
        var button = actionEl instanceof HTMLElement ? actionEl : null;
        var target = resolveAiIndexTarget(button);
        if (!target) {
            showToast(getLabel('media_ai_index_failed', 'AI indexing failed.'), 'error');
            return;
        }

        runAiIndex(target, button);
    }

    function resolveAiIndexTarget(button) {
        var pending = pendingAiPaths.slice();
        if (pending.length > 0) {
            return {
                folder: '',
                paths: pending,
            };
        }

        if (button && button.dataset.aiScope === 'folder') {
            return {
                folder: String(button.dataset.folder || currentFolder || '').trim(),
                paths: [],
            };
        }

        return {
            folder: '',
            paths: [],
        };
    }

    function runAiIndex(target, button) {
        if (!config.aiIndexUrl) {
            showToast(getLabel('media_ai_index_failed', 'AI indexing failed.'), 'error');
            return;
        }

        setAiButtonsBusy(true);
        showToast(getLabel('media_ai_indexing', 'AI indexing in progress...'), 'info');

        var formData = new FormData();
        formData.append('_token', config.csrfToken);

        if (target.folder) {
            formData.append('folder', target.folder);
        }

        (target.paths || []).forEach(function(path) {
            formData.append('paths[]', path);
        });

        fetch(config.aiIndexUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                setAiButtonsBusy(false);

                if (data && data.success) {
                    removePendingAiPaths([].concat(data.completed_paths || []));
                    showToast(data.message || getLabel('media_ai_indexing', 'AI indexing complete.'), 'success');

                    if (currentFolder) {
                        loadFiles(currentFolder);
                        updateTabCount(currentFolder);
                        return;
                    }

                    window.location.reload();
                    return;
                }

                showToast((data && data.message) || getLabel('media_ai_index_failed', 'AI indexing failed.'), 'error');
            })
            .catch(function() {
                setAiButtonsBusy(false);
                showToast(getLabel('media_ai_index_failed', 'AI indexing failed.'), 'error');
            });
    }

    function setAiButtonsBusy(isBusy) {
        document.querySelectorAll('[data-action="media-ai-index"]').forEach(function(button) {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            button.disabled = isBusy;
        });
    }

    function extractUploadedPaths(response) {
        var results = Array.isArray(response && response.results) ? response.results : [];
        return results
            .map(function(result) {
                return result && result.media ? String(result.media.path || '').trim() : '';
            })
            .filter(function(path) {
                return path !== '';
            });
    }

    function openUploadModal() {
        openModal('uploadModal');
    }

    function closeUploadModal() {
        closeModal('uploadModal');
    }

    window.syncMedia = openSyncModal;

    function setupModals() {
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                handleModalClose('deleteModal');
                handleModalClose('syncModal');
                handleModalClose('uploadModal');
            }
        });

        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    handleModalClose(modal.id);
                }
            });
        });
    }

    /**
     * Toast notification
     */
    function showToast(message, type) {
        const toast = document.getElementById('toast');
        if (!toast) return;

        toast.textContent = message;
        toast.className = 'toast show ' + type;
        const toastType = String(type || 'success').toLowerCase();
        const displayDuration = toastType === 'error' ? 20000 : 10000;

        setTimeout(() => {
            toast.className = 'toast';
        }, displayDuration);
    }

    window.showToast = showToast;

    /**
     * Utilitaires
     */
    function formatSizeMB(bytes) {
        if (bytes === 0) return '0 Mo';
        const mb = bytes / (1024 * 1024);
        if (mb < 0.01) {
            return (bytes / 1024).toFixed(1) + ' Ko';
        }
        return mb.toFixed(2) + ' Mo';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return '';
        
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        
        return `${day}/${month}/${year}`;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function escapeAttribute(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

})();
