/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const VALID_FOLDERS = new Set([
        'images',
        'videos',
        'sounds',
        'documents',
        'pdf',
        'spreadsheets',
        'archives',
    ]);

    const FOLDER_ACCEPT = {
        images: 'image/*',
        videos: 'video/*',
        sounds: 'audio/*',
        documents: '.doc,.docx,.txt,.rtf,.odt',
        pdf: '.pdf',
        spreadsheets: '.xls,.xlsx,.csv,.ods',
        archives: '.zip,.rar,.7z,.tar,.gz',
    };

    let initialized = false;
    let config = {};

    function parseModalConfig(modal) {
        if (!modal) {
            return {};
        }
        const raw = String(modal.getAttribute('data-media-config') || '').trim();
        if (raw === '') {
            return {};
        }
        try {
            return JSON.parse(raw);
        } catch (error) {
            return {};
        }
    }

    function sanitizeFolder(folder) {
        const value = String(folder || '').trim().toLowerCase();
        return VALID_FOLDERS.has(value) ? value : 'images';
    }

    function escapeAttribute(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function showToast(message, type) {
        const text = String(message || '').trim();
        if (text === '') {
            return;
        }
        if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
            window.FlatCMS.toast.show(text, type || 'warning');
            return;
        }
        if (window.console && typeof window.console.warn === 'function') {
            window.console.warn(text);
        }
    }

    function resolveMediaSrc(raw, uploadsBase) {
        const src = String(raw || '').trim();
        if (src === '') {
            return '';
        }
        if (/^(https?:)?\/\//i.test(src) || src.startsWith('data:') || src.startsWith('blob:')) {
            return src;
        }

        const base = String(uploadsBase || '/uploads').replace(/\/$/, '');
        if (src.startsWith('/public/uploads/')) {
            return base + '/' + src.replace(/^\/public\/uploads\/?/, '');
        }
        if (src.startsWith('/uploads/')) {
            return base + '/' + src.replace(/^\/uploads\/?/, '');
        }
        if (src.startsWith('/')) {
            return src;
        }
        return base + '/' + src.replace(/^\//, '');
    }

    window.initMediaModal = function(options) {
        const modal = document.getElementById('mediaModal');
        if (!modal) return;

        const baseConfig = parseModalConfig(modal);
        config = Object.assign(
            {},
            baseConfig,
            config,
            options && typeof options === 'object' ? options : {}
        );

        if (initialized) {
            if (window.FlatCMS && window.FlatCMS.mediaModal && typeof window.FlatCMS.mediaModal.reload === 'function') {
                window.FlatCMS.mediaModal.reload(options);
            }
            return;
        }
        initialized = true;

        const getApiImagesUrl = () => String(config.apiImagesUrl || '');
        const getApiFilesUrl = () => String(config.apiFilesUrl || '');
        const getApiDirectoriesUrl = () => String(config.apiDirectoriesUrl || '');
        const getUploadsBase = () => String(config.uploadsBase || '/uploads');
        const getUploadUrl = () => String(config.uploadUrl || '');
        const getCsrfToken = () => String(config.csrfToken || '');
        const getUploadFailedLabel = () => String(config.uploadFailedLabel || '').trim();
        const getUploadInvalidLabel = () => String(config.uploadInvalidLabel || '').trim();
        const getSelectMediaLabel = () => String(config.selectMediaLabel || '').trim();
        const getImagesLabel = () => String(config.imagesLabel || '').trim();
        const getVideosLabel = () => String(config.videosLabel || '').trim();
        const getFilesLabel = () => String(config.filesLabel || '').trim();
        const getNoImagesLabel = () => String(config.noImagesLabel || '').trim();
        const getNoMediaLabel = () => String(config.noMediaLabel || '').trim();
        const getCurrentDirectoryLabel = () => String(config.currentDirectoryLabel || '').trim();
        const getRootDirectoryLabel = () => String(config.rootDirectoryLabel || '').trim();
        const getDirectoryEmptyLabel = () => String(config.directoryEmptyLabel || '').trim();
        const getMediaMode = () => (String(config.mode || 'images').toLowerCase() === 'files' ? 'files' : 'images');
        const normalizeContext = (rawContext) => {
            const raw = String(rawContext || '').replace(/\\/g, '/').trim();
            if (raw === '') {
                return '';
            }
            return raw
                .replace(/^\/+|\/+$/g, '')
                .split('/')
                .map((part) => part.replace(/[^a-z0-9_-]+/gi, '-').replace(/^-+|-+$/g, '').toLowerCase())
                .filter(Boolean)
                .join('/')
                .slice(0, 160);
        };
        const getMediaContext = () => {
            const raw = String(config.mediaContext || config.context || '').replace(/\\/g, '/').trim();
            if (raw === '') {
                return '';
            }
            return normalizeContext(raw);
        };
        const getMediaFolder = () => {
            if (getMediaMode() === 'images') {
                return 'images';
            }
            return sanitizeFolder(config.folder || 'documents');
        };
        const getUploadFolder = () => {
            return getMediaMode() === 'images' ? 'images' : getMediaFolder();
        };
        const getFileAccept = () => {
            const explicit = String(config.accept || '').trim();
            if (explicit !== '') {
                return explicit;
            }
            return FOLDER_ACCEPT[getUploadFolder()] || '*/*';
        };
        const shouldOpenUploadOnEmpty = () => !!config.openUploadIfEmpty;
        const getInitialTab = () => (config.initialTab === 'upload' ? 'upload' : 'library');

        const grid = modal.querySelector('#mediaLibraryGrid');
        const empty = modal.querySelector('#mediaLibraryEmpty');
        const loading = modal.querySelector('#mediaLibraryLoading');
        const selectBtn = modal.querySelector('#btnSelectMedia');
        const info = modal.querySelector('#selectedMediaInfo');
        const tabLibrary = modal.querySelector('#tabLibrary');
        const tabUpload = modal.querySelector('#tabUpload');
        const tabContentLibrary = modal.querySelector('#tabContentLibrary');
        const tabContentUpload = modal.querySelector('#tabContentUpload');
        const dropZone = modal.querySelector('#modalDropZone');
        const fileInput = modal.querySelector('#modalFileInput');
        const fileButton = modal.querySelector('[data-file-target="modalFileInput"]');
        const progress = modal.querySelector('#modalUploadProgress');
        const progressBar = modal.querySelector('#modalUploadBar');
        const progressPercent = modal.querySelector('#modalUploadPercent');
        const titleIcon = modal.querySelector('#mediaModalTitleIcon');
        const titleText = modal.querySelector('#mediaModalTitleText');
        const libraryIcon = modal.querySelector('#tabLibraryIcon');
        const emptyIcon = modal.querySelector('#mediaLibraryEmptyIcon');
        const emptyText = modal.querySelector('#mediaLibraryEmptyText');
        const directoryCurrent = modal.querySelector('#mediaModalDirectoryCurrent');
        const directoryList = modal.querySelector('#mediaModalDirectoryList');

        let files = [];
        let directories = [];
        let selectedIndex = null;
        let uploadInProgress = false;
        let activeContext = getMediaContext();

        const show = (el) => { if (el) el.classList.remove('hidden'); };
        const hide = (el) => { if (el) el.classList.add('hidden'); };
        const openModal = () => {
            modal.classList.remove('hidden', 'is-initially-hidden');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
        };
        const closeModal = () => {
            modal.classList.add('hidden');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        };

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
                searchParams.set(key, String(params[key]));
            });
            return origin + front + '?' + searchParams.toString();
        }

        function appendQueryParams(url, params) {
            const base = String(url || '').trim();
            if (base === '') {
                return '';
            }
            const finalParams = Object.assign({}, params || {});
            if (!Object.keys(finalParams).length) {
                return base;
            }
            const glue = base.includes('?') ? '&' : '?';
            const query = new URLSearchParams(finalParams).toString();
            return query ? (base + glue + query) : base;
        }

        function buildApiCandidates(apiUrl, pathValue, params) {
            const origin = window.location.origin || '';
            // Priority: explicit endpoint (module-specific) first, then generic fallback routes.
            const primary = appendQueryParams(apiUrl, params);
            const fallback = buildApiUrlFromLocation(pathValue, params);
            const publicPath = new URLSearchParams(Object.assign({ path: pathValue }, params || {})).toString();
            const publicFallback = origin + '/public/index.php?' + publicPath;
            return Array.from(new Set([primary, fallback, publicFallback].filter(Boolean)));
        }

        function fetchJsonWithFallback(url, fallbackUrls) {
            const fallbacks = Array.isArray(fallbackUrls) ? fallbackUrls.slice() : [];
            return fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'include',
                cache: 'no-store',
            })
                .then((response) => response.text().then((text) => ({
                    text,
                    url: response.url || url,
                    contentType: response.headers.get('content-type') || '',
                })))
                .then(({ text, url: resolvedUrl, contentType }) => {
                    const isJson = contentType.includes('application/json') || contentType.includes('text/json');
                    if (isJson) {
                        return JSON.parse(text);
                    }
                    while (fallbacks.length) {
                        const next = fallbacks.shift();
                        if (next && next !== resolvedUrl) {
                            return fetchJsonWithFallback(next, fallbacks);
                        }
                    }
                    return JSON.parse(text);
                });
        }

        function resolveFileUrl(file) {
            const path = String((file && file.path) || '').trim();
            const uploadsBase = getUploadsBase().replace(/\/$/, '');

            if (path !== '') {
                return resolveMediaSrc(path, uploadsBase);
            }

            const explicit = String((file && file.url) || '').trim();
            if (explicit !== '') {
                return resolveMediaSrc(explicit, uploadsBase);
            }

            return '';
        }

        function buildFilePlaceholder(file) {
            const mime = String((file && file.mime) || '').toLowerCase();
            const ext = String((file && file.extension) || '').toLowerCase();

            let icon = 'fa-file-alt';
            let placeholderClass = 'media-item-preview-placeholder';

            if (mime.startsWith('video/')) {
                icon = 'fa-play-circle';
                placeholderClass += ' media-item-preview-placeholder--video';
            } else if (mime.startsWith('audio/')) {
                icon = 'fa-music';
                placeholderClass += ' media-item-preview-placeholder--audio';
            } else if (mime === 'application/pdf' || ext === 'pdf') {
                icon = 'fa-file-pdf';
                placeholderClass += ' media-item-preview-placeholder--pdf';
            } else if (mime.includes('spreadsheet') || mime.includes('excel') || ext === 'csv' || ext === 'ods') {
                icon = 'fa-file-excel';
                placeholderClass += ' media-item-preview-placeholder--sheet';
            } else if (mime.includes('zip') || mime.includes('rar') || mime.includes('archive') || ext === '7z' || ext === 'tar' || ext === 'gz') {
                icon = 'fa-file-archive';
                placeholderClass += ' media-item-preview-placeholder--archive';
            }

            return `<div class="${placeholderClass}"><i class="fas ${icon}" aria-hidden="true"></i></div>`;
        }

        function renderItemPreview(file) {
            const mime = String((file && file.mime) || '').toLowerCase();
            const src = resolveFileUrl(file);

            if (src && mime.startsWith('image/')) {
                return `<img src="${escapeAttribute(src)}" alt="">`;
            }

            return buildFilePlaceholder(file);
        }

        function applyFileInputAccept() {
            if (!fileInput) return;
            fileInput.setAttribute('accept', getFileAccept());
        }

        function updateContextUi() {
            const mode = getMediaMode();
            const folder = getMediaFolder();
            const isImages = mode === 'images';
            const isVideos = mode === 'files' && folder === 'videos';
            const iconClass = isImages ? 'fa-image' : (isVideos ? 'fa-video' : 'fa-folder-open');
            const mediaLabel = isImages
                ? (getImagesLabel() || getSelectMediaLabel())
                : (isVideos ? (getVideosLabel() || getFilesLabel()) : (getFilesLabel() || getSelectMediaLabel()));
            const emptyLabel = isImages ? (getNoImagesLabel() || getNoMediaLabel()) : (getNoMediaLabel() || getNoImagesLabel());

            if (titleIcon) {
                titleIcon.className = `fas ${iconClass} media-modal-icon`;
            }
            if (libraryIcon) {
                libraryIcon.className = `fas ${iconClass}`;
            }
            if (emptyIcon) {
                emptyIcon.className = `fas ${iconClass}`;
            }
            if (titleText) {
                titleText.textContent = mediaLabel || '';
            }
            if (emptyText) {
                emptyText.textContent = emptyLabel || '';
            }
            updateDirectoryUi();
        }

        function getDirectoryLabel(context) {
            const normalized = normalizeContext(context);
            return normalized === '' ? (getRootDirectoryLabel() || 'Root') : normalized;
        }

        function updateDirectoryUi() {
            if (directoryCurrent) {
                const prefix = getCurrentDirectoryLabel() || 'Current directory';
                directoryCurrent.textContent = `${prefix}: ${getDirectoryLabel(activeContext)}`;
            }

            if (!directoryList) {
                return;
            }

            if (!directories.length) {
                directoryList.innerHTML = `<p class="media-modal-directory-empty">${escapeAttribute(getDirectoryEmptyLabel() || 'No subdirectory.')}</p>`;
                return;
            }

            directoryList.innerHTML = '';
            directories.forEach((directory) => {
                const path = normalizeContext(directory.path || '');
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'media-modal-directory-item' + (path === activeContext ? ' active' : '');
                item.dataset.context = path;
                item.innerHTML = `
                    <i class="fas ${path === '' ? 'fa-folder-open' : 'fa-folder'}" aria-hidden="true"></i>
                    <span>${escapeAttribute(getDirectoryLabel(path))}</span>
                `;
                directoryList.appendChild(item);
            });
        }

        function setActiveContext(context) {
            activeContext = normalizeContext(context);
            selectedIndex = null;
            if (info) info.textContent = '';
            if (selectBtn) selectBtn.disabled = true;
            updateDirectoryUi();
            load({ preserveTab: true });
        }

        function loadDirectories() {
            const params = { folder: getUploadFolder() };
            const apiCandidates = buildApiCandidates(getApiDirectoriesUrl(), 'admin/media/api/directories', params);
            const primaryApi = apiCandidates.shift() || buildApiUrlFromLocation('admin/media/api/directories', params);

            return fetchJsonWithFallback(primaryApi, apiCandidates)
                .then((data) => {
                    directories = Array.isArray(data.directories) ? data.directories : [];
                    updateDirectoryUi();
                })
                .catch(() => {
                    directories = [];
                    updateDirectoryUi();
                });
        }

        function render() {
            if (!grid) return;
            grid.innerHTML = '';

            if (!files.length) {
                show(empty);
                return;
            }

            hide(empty);
            files.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'media-library-item' + (selectedIndex === index ? ' selected' : '');
                item.dataset.index = String(index);
                item.innerHTML = renderItemPreview(file);
                grid.appendChild(item);
            });
        }

        function setSelected(index) {
            selectedIndex = index;

            if (grid) {
                grid.querySelectorAll('.media-library-item').forEach((el) => el.classList.remove('selected'));
                const active = grid.querySelector(`[data-index="${index}"]`);
                if (active) {
                    active.classList.add('selected');
                }
            }

            const file = files[index];
            if (info && file) {
                info.textContent = file.original_name || file.name || file.filename || file.path || '';
            }
            if (selectBtn) {
                selectBtn.disabled = !file;
            }
        }

        function load(options) {
            const preserveTab = !!(options && options.preserveTab);
            const mode = getMediaMode();
            const context = activeContext;
            const params = mode === 'images' ? {} : { folder: getMediaFolder() };
            if (context !== '') {
                params.context = context;
            }
            const pathValue = mode === 'images' ? 'admin/media/api/images' : 'admin/media/api/files';
            const apiUrl = mode === 'images' ? getApiImagesUrl() : (getApiFilesUrl() || getApiImagesUrl());

            applyFileInputAccept();
            updateContextUi();
            show(loading);

            const apiCandidates = buildApiCandidates(apiUrl, pathValue, params);
            const primaryApi = apiCandidates.shift() || buildApiUrlFromLocation(pathValue, params);

            fetchJsonWithFallback(primaryApi, apiCandidates)
                .then((data) => {
                    files = Array.isArray(data.files) ? data.files : [];
                    selectedIndex = null;
                    if (info) info.textContent = '';
                    if (selectBtn) selectBtn.disabled = true;

                    if (!files.length && shouldOpenUploadOnEmpty()) {
                        switchTab('upload');
                    } else if (!preserveTab) {
                        switchTab(getInitialTab());
                    }

                    render();
                })
                .catch(() => {
                    files = [];
                    render();
                })
                .finally(() => hide(loading));
        }

        if (grid) {
            grid.addEventListener('click', (event) => {
                const item = event.target.closest('.media-library-item');
                if (!item) return;
                const index = Number(item.dataset.index || 0);
                setSelected(index);
            });
        }

        if (directoryList) {
            directoryList.addEventListener('click', (event) => {
                const item = event.target.closest('.media-modal-directory-item');
                if (!item) {
                    return;
                }
                event.preventDefault();
                setActiveContext(item.dataset.context || '');
            });
        }

        if (selectBtn) {
            selectBtn.addEventListener('click', () => {
                if (selectedIndex === null) return;
                const file = files[selectedIndex];
                if (file && typeof config.onSelect === 'function') {
                    config.onSelect(file);
                }
            });
        }

        function switchTab(name) {
            if (name === 'upload') {
                tabUpload && tabUpload.classList.add('active');
                tabLibrary && tabLibrary.classList.remove('active');
                hide(tabContentLibrary);
                show(tabContentUpload);
                return;
            }
            tabLibrary && tabLibrary.classList.add('active');
            tabUpload && tabUpload.classList.remove('active');
            show(tabContentLibrary);
            hide(tabContentUpload);
        }

        if (tabLibrary) {
            tabLibrary.addEventListener('click', () => switchTab('library'));
        }
        if (tabUpload) {
            tabUpload.addEventListener('click', () => switchTab('upload'));
        }
        modal.querySelectorAll('[data-media-tab]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.mediaTab || 'library';
                switchTab(tab);
            });
        });

        function uploadFiles(fileList) {
            const uploadUrl = getUploadUrl();
            if (!uploadUrl || !fileList || !fileList.length || uploadInProgress) return;

            uploadInProgress = true;
            const formData = new FormData();
            formData.append('folder', getUploadFolder());
            const context = activeContext;
            if (context !== '') {
                formData.append('media_context', context);
            }

            const csrfToken = getCsrfToken();
            if (csrfToken) {
                formData.append('_token', csrfToken);
            }

            Array.from(fileList).forEach((file) => formData.append('files[]', file));

            show(progress);
            if (progressBar) progressBar.style.width = '0%';
            if (progressPercent) progressPercent.textContent = '0%';

            const xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', (event) => {
                if (!event.lengthComputable) return;
                const percent = Math.round((event.loaded / event.total) * 100);
                if (progressBar) progressBar.style.width = percent + '%';
                if (progressPercent) progressPercent.textContent = percent + '%';
            });

            xhr.addEventListener('load', () => {
                uploadInProgress = false;
                hide(progress);
                if (fileInput) fileInput.value = '';
                let payload = null;
                try {
                    payload = xhr.responseText ? JSON.parse(xhr.responseText) : null;
                } catch (error) {
                    payload = null;
                }

                if (xhr.status < 200 || xhr.status >= 300) {
                    const fallbackPayload = payload && typeof payload === 'object' ? payload : { success: false };
                    const failureMessage = xhr.status === 413
                        ? (getUploadInvalidLabel() || getUploadFailedLabel())
                        : (
                            String((fallbackPayload && (fallbackPayload.message || fallbackPayload.error)) || '').trim()
                            || getUploadFailedLabel()
                        );
                    if (typeof config.onUploadComplete === 'function') {
                        config.onUploadComplete(fallbackPayload);
                    }
                    showToast(failureMessage, 'error');
                    switchTab('upload');
                    return;
                }

                if (typeof config.onUploadComplete === 'function') {
                    config.onUploadComplete(payload);
                }

                if (payload && payload.success === false) {
                    const failureMessage = String((payload.message || payload.error) || '').trim()
                        || getUploadFailedLabel();
                    showToast(failureMessage, 'error');
                    switchTab('upload');
                    return;
                }

                switchTab('library');
                loadDirectories().finally(() => load({ preserveTab: true }));
            });

            xhr.addEventListener('error', () => {
                uploadInProgress = false;
                hide(progress);
                if (fileInput) fileInput.value = '';
                if (typeof config.onUploadComplete === 'function') {
                    config.onUploadComplete({ success: false });
                }
                showToast(getUploadFailedLabel(), 'error');
                switchTab('upload');
            });

            xhr.addEventListener('abort', () => {
                uploadInProgress = false;
                hide(progress);
                if (fileInput) fileInput.value = '';
                if (typeof config.onUploadComplete === 'function') {
                    config.onUploadComplete({ success: false });
                }
                showToast(getUploadFailedLabel(), 'error');
                switchTab('upload');
            });

            xhr.open('POST', uploadUrl);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        }

        if (dropZone && fileInput) {
            dropZone.addEventListener('click', (event) => {
                if (event.target.closest('[data-file-target]')) return;
                if (
                    event.target === dropZone ||
                    event.target.closest('.upload-zone-icon') ||
                    event.target.closest('.upload-zone-text') ||
                    event.target.closest('.upload-zone-hint')
                ) {
                    if (!uploadInProgress) {
                        fileInput.click();
                    }
                }
            });

            dropZone.addEventListener('dragover', (event) => {
                event.preventDefault();
                dropZone.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', (event) => {
                event.preventDefault();
                dropZone.classList.remove('dragover');
                uploadFiles(event.dataTransfer.files);
            });

            fileInput.addEventListener('change', () => {
                if (uploadInProgress) return;
                uploadFiles(fileInput.files);
            });
        }

        if (fileButton && fileInput) {
            fileButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                if (uploadInProgress) return;
                fileInput.click();
            });
        }

        modal.querySelectorAll('[data-modal-close="mediaModal"]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                closeModal();
            });
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        window.FlatCMS = window.FlatCMS || {};
        window.FlatCMS.mediaModal = {
            reload: (nextOptions) => {
                const liveConfig = parseModalConfig(modal);
                config = Object.assign({}, liveConfig, config, nextOptions && typeof nextOptions === 'object' ? nextOptions : {});
                activeContext = getMediaContext();
                loadDirectories().finally(() => load(nextOptions));
            },
            open: () => openModal(),
            close: () => closeModal(),
            openLibrary: () => switchTab('library'),
            openUpload: () => switchTab('upload'),
            updateConfig: (nextConfig) => {
                const liveConfig = parseModalConfig(modal);
                config = Object.assign({}, liveConfig, config, nextConfig && typeof nextConfig === 'object' ? nextConfig : {});
                activeContext = getMediaContext();
                applyFileInputAccept();
                loadDirectories();
            },
        };

        loadDirectories().finally(() => load());
    };
})();
