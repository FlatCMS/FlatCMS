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
    let currentContext = '';

    let deleteMediaId = null;
    let deleteMediaPath = null;
    let renameMediaId = null;
    let renameMediaPath = null;
    let allowMediaLeave = false;
    let pendingAiPaths = loadPendingAiPaths();

    var currentFiles = [];
    var currentDirectories = [];
    var currentSortField = 'name';
    var currentSortDir = 'asc';
    var currentViewMode = 'grid';

    document.addEventListener('DOMContentLoaded', function() {
        bindMediaActions();
        initMediaBatchActions();
        setupDropZone();
        setupMediaMoveDrop();
        setupFileInput();
        setupModals();
        setupLeaveGuard();
        setupSearch();
        setupToolbarActions();

        var firstTab = document.querySelector('.media-tab');
        if (!currentFolder) {
            loadRootDirectories();
        } else if (firstTab && firstTab.dataset.folder) {
            selectFolder(firstTab.dataset.folder);
        }
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

    function normalizeContext(raw) {
        const value = String(raw || '').replace(/\\/g, '/').trim();
        if (value === '') {
            return '';
        }

        return value
            .replace(/^\/+|\/+$/g, '')
            .split('/')
            .map(function(part) {
                return part
                    .replace(/[^a-z0-9_-]+/gi, '-')
                    .replace(/^-+|-+$/g, '')
                    .toLowerCase();
            })
            .filter(Boolean)
            .join('/')
            .slice(0, 160);
    }

    function getFolderLabel(folderName) {
        return (config.labels && config.labels[folderName]) || folderName || '';
    }

    function isAuthorizedFolder(folderName) {
        return !!(folderName && config.folders && Object.prototype.hasOwnProperty.call(config.folders, folderName));
    }

    function showRootDirectoryForbidden() {
        showToast(getLabel('media_root_directory_forbidden', ''), 'error');
    }

    function showRootUploadForbidden() {
        showToast(getLabel('media_root_upload_forbidden', ''), 'error');
    }

    function getCurrentDirectoryLabel(context) {
        const normalized = normalizeContext(context);
        if (normalized === '') {
            return getLabel('root_directory', (config.labels && config.labels.root_directory) || '');
        }

        return normalized;
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

    function setupSearch() {
        var searchInput = document.getElementById('mediaSearchInput');
        if (!searchInput) return;

        var searchTimer = null;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                applyFiltersAndRender();
            }, 250);
        });
    }

    function setupToolbarActions() {
        var closeInfobar = document.getElementById('btnCloseInfobar');
        if (closeInfobar) {
            closeInfobar.addEventListener('click', function() {
                var infobar = document.getElementById('mediaInfobar');
                if (infobar) infobar.classList.remove('open');
                var toggleBtn = document.querySelector('[data-toolbar-action="infobar-toggle"]');
                if (toggleBtn) toggleBtn.classList.remove('active');
            });
        }

        document.addEventListener('click', function(e) {
            var actionEl = e.target.closest('[data-toolbar-action]');
            if (!actionEl) return;

            var action = actionEl.getAttribute('data-toolbar-action');
            e.preventDefault();

            switch (action) {
                case 'select-all':
                    toggleSelectAll();
                    break;
                case 'sort':
                    cycleSortOrder();
                    break;
                case 'view-small':
                    setViewMode('small');
                    break;
                case 'view-grid':
                    setViewMode('grid');
                    break;
                case 'view-list':
                    setViewMode('list');
                    break;
                case 'infobar-toggle':
                    toggleInfobar();
                    break;
            }
        });
    }

    function toggleSelectAll() {
        var selectAll = document.getElementById('mediaSelectAll');
        if (selectAll instanceof HTMLInputElement) {
            selectAll.checked = !selectAll.checked;
            selectAll.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        var checkboxes = getMediaCheckboxes();
        if (checkboxes.length === 0) return;

        var allChecked = checkboxes.every(function(cb) {
            return cb instanceof HTMLInputElement && cb.checked;
        });
        var newState = !allChecked;

        checkboxes.forEach(function(cb) {
            if (cb instanceof HTMLInputElement && !cb.disabled) {
                cb.checked = newState;
                var card = cb.closest('.media-browser-item');
                if (card) {
                    card.classList.toggle('selected', newState);
                }
            }
        });
        syncMediaBatchState();
    }

    function cycleSortOrder() {
        var fields = ['name', 'date'];
        var dirs = ['asc', 'desc'];
        var fieldIdx = fields.indexOf(currentSortField);
        var dirIdx = dirs.indexOf(currentSortDir);

        dirIdx++;
        if (dirIdx >= dirs.length) {
            dirIdx = 0;
            fieldIdx++;
            if (fieldIdx >= fields.length) {
                fieldIdx = 0;
            }
        }

        currentSortField = fields[fieldIdx];
        currentSortDir = dirs[dirIdx];

        var sortBtn = document.querySelector('[data-toolbar-action="sort"]');
        if (sortBtn) {
            sortBtn.classList.toggle('active', currentSortField !== 'name' || currentSortDir !== 'asc');
            sortBtn.setAttribute('data-sort-dir', currentSortDir);
        }

        applyFiltersAndRender();
    }

    function setViewMode(mode) {
        currentViewMode = mode;
        var grid = document.getElementById('filesGrid');
        if (grid) {
            grid.classList.remove('media-browser-items--small', 'media-browser-items--list');
            if (mode === 'small') {
                grid.classList.add('media-browser-items--small');
            } else if (mode === 'list') {
                grid.classList.add('media-browser-items--list');
            }
        }

        document.querySelectorAll('[data-toolbar-action^="view-"]').forEach(function(btn) {
            btn.classList.remove('active');
        });
        var activeBtn = document.querySelector('[data-toolbar-action="view-' + mode + '"]');
        if (activeBtn) {
            activeBtn.classList.add('active');
        }

        applyFiltersAndRender();
        renderDirectoriesIntoGrid();
    }

    function toggleInfobar() {
        var infobar = document.getElementById('mediaInfobar');
        if (!infobar) return;
        infobar.classList.toggle('open');

        var toggleBtn = document.querySelector('[data-toolbar-action="infobar-toggle"]');
        if (toggleBtn) {
            toggleBtn.classList.toggle('active', infobar.classList.contains('open'));
        }
    }

    function sortFiles(files) {
        var sorted = files.slice();
        sorted.sort(function(a, b) {
            var valA, valB;
            if (currentSortField === 'date') {
                valA = a.created_at || '';
                valB = b.created_at || '';
            } else {
                valA = (a.original_name || a.name || '').toLowerCase();
                valB = (b.original_name || b.name || '').toLowerCase();
            }
            var cmp = valA < valB ? -1 : valA > valB ? 1 : 0;
            return currentSortDir === 'desc' ? -cmp : cmp;
        });
        return sorted;
    }

    function filterFiles(files) {
        var searchInput = document.getElementById('mediaSearchInput');
        var query = searchInput ? String(searchInput.value || '').trim().toLowerCase() : '';
        if (query === '') return files;

        return files.filter(function(file) {
            var name = (file.original_name || file.name || '').toLowerCase();
            var ext = (file.extension || '').toLowerCase();
            var mime = (file.mime || '').toLowerCase();
            return name.indexOf(query) !== -1 || ext.indexOf(query) !== -1 || mime.indexOf(query) !== -1;
        });
    }

    function applyFiltersAndRender() {
        var filtered = filterFiles(currentFiles);
        var sorted = sortFiles(filtered);
        renderFilesToGrid(sorted);
    }

    function renderFilesToGrid(files) {
        var filesGrid = document.getElementById('filesGrid');
        if (!filesGrid) return;

        var existingItems = filesGrid.querySelectorAll('.media-browser-item');
        existingItems.forEach(function(el) { el.remove(); });

        var existingHeader = filesGrid.querySelector('.media-browser-list-header');
        if (existingHeader) existingHeader.remove();

        setMediaBatchVisibility(files.length > 0);

        var listTbody = null;

        if (currentViewMode === 'list') {
            var existingWrapper = filesGrid.querySelector('.table-wrapper');
            var tableWrapper, tbody;

            if (existingWrapper && existingWrapper.querySelector('table tbody')) {
                tableWrapper = existingWrapper;
                tbody = existingWrapper.querySelector('table tbody');
            } else {
                tableWrapper = document.createElement('div');
                tableWrapper.className = 'table-wrapper';

                var table = document.createElement('table');
                table.className = 'table';

                var thead = document.createElement('thead');
                thead.innerHTML = '<tr>' +
                    '<th class="media-select-column"><input type="checkbox" class="media-row-checkbox" id="mediaSelectAll" aria-label="' + escapeAttribute(getLabel('media_select_all', 'Tout sélectionner')) + '"></th>' +
                    '<th class="media-thumb-column">' + escapeHtml(getLabel('media_preview', 'Vignette')) + '</th>' +
                    '<th>' + escapeHtml(getLabel('media_name', 'Nom du fichier')) + '</th>' +
                    '<th>' + escapeHtml(getLabel('media_folder', 'Dossier')) + '</th>' +
                    '<th>' + escapeHtml(getLabel('media_dimensions', 'Dimensions')) + '</th>' +
                    '<th>' + escapeHtml(getLabel('media_size', 'Poids')) + '</th>' +
                    '<th class="table-actions-header">' + escapeHtml(getLabel('media_actions', 'Actions')) + '</th>' +
                    '</tr>';
                table.appendChild(thead);

                tbody = document.createElement('tbody');
                table.appendChild(tbody);
                tableWrapper.appendChild(table);
                filesGrid.appendChild(tableWrapper);
            }

            listTbody = tbody;
        } else {
            var existingTableWrapper = filesGrid.querySelector('.table-wrapper');
            if (existingTableWrapper) existingTableWrapper.remove();
        }

        files.forEach(function(file) {
            var item = document.createElement(currentViewMode === 'list' ? 'tr' : 'div');
            item.className = 'media-browser-item';
            item.draggable = true;
            item.dataset.id = file.id || 0;
            item.dataset.path = file.path || '';
            item.dataset.name = file.original_name || file.name || '';
            item.dataset.moveType = 'file';

            var ext = (file.extension || file.name.split('.').pop() || '').toUpperCase();

            var preview = '';
            if (file.mime && file.mime.startsWith('image/')) {
                preview = '<div class="media-browser-item-preview"><img src="' + escapeAttribute(file.url) + '" alt="' + escapeAttribute(file.original_name || file.name) + '" loading="lazy"></div>';
            } else if (file.mime && file.mime.startsWith('video/')) {
                preview = '<div class="media-browser-item-preview"><div class="file-icon"><i class="fas fa-play-circle"></i></div></div>';
            } else if (file.mime && file.mime.startsWith('audio/')) {
                preview = '<div class="media-browser-item-preview"><div class="file-icon"><i class="fas fa-music"></i></div></div>';
            } else {
                var icon = 'fa-file-alt';
                if (file.mime === 'application/pdf') icon = 'fa-file-pdf';
                else if (file.mime && (file.mime.includes('spreadsheet') || file.mime.includes('excel') || file.extension === 'csv')) icon = 'fa-file-excel';
                else if (file.mime && (file.mime.includes('zip') || file.mime.includes('archive'))) icon = 'fa-file-archive';
                preview = '<div class="media-browser-item-preview"><div class="file-icon"><i class="fas ' + icon + '"></i></div></div>';
            }

            var folderPath = (file.path || '').split('/').slice(0, -1).join('/') || currentFolder;
            var fileName = file.original_name || file.name;
            var filePath = file.path || '';

            if (currentViewMode === 'list') {
                var sizeLabel = file.size ? formatBytes(file.size) : '-';
                var folderLabel = file.folder || folderPath || '-';
                var dimensionsLabel = file.dimensions ? (file.dimensions.width + 'x' + file.dimensions.height) : '-';

                var thumbHtml = '';
                if (file.mime && file.mime.startsWith('image/')) {
                    thumbHtml = '<img src="' + escapeAttribute(file.url) + '" alt="" class="img-thumbnail" loading="lazy">';
                } else if (file.mime && file.mime.startsWith('video/')) {
                    thumbHtml = '<div class="file-icon"><i class="fas fa-play-circle"></i></div>';
                } else if (file.mime && file.mime.startsWith('audio/')) {
                    thumbHtml = '<div class="file-icon"><i class="fas fa-music"></i></div>';
                } else {
                    var listIcon = 'fa-file-alt';
                    if (file.mime === 'application/pdf') listIcon = 'fa-file-pdf';
                    else if (file.mime && (file.mime.includes('spreadsheet') || file.mime.includes('excel') || file.extension === 'csv')) listIcon = 'fa-file-excel';
                    else if (file.mime && (file.mime.includes('zip') || file.mime.includes('archive'))) listIcon = 'fa-file-archive';
                    thumbHtml = '<div class="file-icon"><i class="fas ' + listIcon + '"></i></div>';
                }

                item.innerHTML = '<td class="media-select-column"><input type="checkbox" class="media-row-checkbox" data-media-select value="' + escapeAttribute(filePath) + '"></td>' +
                    '<td class="media-thumb-column">' + thumbHtml + '</td>' +
                    '<td><span class="media-file-name">' + escapeHtml(fileName) + '</span></td>' +
                    '<td><span class="media-folder-path">' + escapeHtml(folderLabel) + '</span></td>' +
                    '<td>' + escapeHtml(dimensionsLabel) + '</td>' +
                    '<td>' + escapeHtml(sizeLabel) + '</td>' +
                    '<td><div class="table-actions table-actions-compact">' +
                        '<button type="button" class="table-action table-action-view" data-action="media-preview" data-url="' + escapeAttribute(file.url) + '" data-mime="' + escapeAttribute(file.mime || '') + '" data-name="' + escapeAttribute(fileName) + '" title="' + escapeAttribute(getLabel('media_preview', 'Preview')) + '"><i class="fas fa-eye"></i></button>' +
                        '<button type="button" class="table-action table-action-download" data-action="media-download" data-url="' + escapeAttribute(file.url) + '" data-name="' + escapeAttribute(fileName) + '" title="' + escapeAttribute(getLabel('media_download', 'Download')) + '"><i class="fas fa-download"></i></button>' +
                        '<button type="button" class="table-action table-action-edit" data-action="media-rename" data-id="' + (file.id || 0) + '" data-name="' + escapeAttribute(fileName) + '" data-path="' + escapeAttribute(filePath) + '" title="' + escapeAttribute(getLabel('media_rename', 'Rename')) + '"><i class="fas fa-i-cursor"></i></button>' +
                        '<button type="button" class="table-action table-action-default" data-action="media-copy-url" data-url="' + escapeAttribute(file.url) + '" title="' + escapeAttribute(getLabel('copy_url', 'Copy URL')) + '"><i class="fas fa-link"></i></button>' +
                        '<button type="button" class="table-action table-action-delete" data-action="media-delete-open" data-id="' + (file.id || 0) + '" data-name="' + escapeAttribute(fileName) + '" data-path="' + escapeAttribute(filePath) + '" title="' + escapeAttribute(getLabel('delete', 'Delete')) + '"><i class="fas fa-trash"></i></button>' +
                    '</div></td>';
            } else {
                item.innerHTML = '<span class="media-browser-select"><input type="checkbox" data-media-select value="' + escapeAttribute(filePath) + '"><i class="fas fa-check"></i></span>' +
                    '<div class="media-browser-actions">' +
                        '<button class="media-browser-actions-toggle"><i class="fas fa-ellipsis-v"></i></button>' +
                        '<div class="media-browser-actions-list">' +
                            '<span class="media-browser-actions-item-name"><strong>' + escapeHtml(fileName) + '</strong></span>' +
                            '<button data-action="media-preview" data-url="' + escapeAttribute(file.url) + '" data-mime="' + escapeAttribute(file.mime || '') + '" data-name="' + escapeAttribute(fileName) + '"><i class="fas fa-eye"></i><span>' + escapeAttribute(getLabel('media_preview', 'Preview')) + '</span></button>' +
                            '<button data-action="media-download" data-url="' + escapeAttribute(file.url) + '" data-name="' + escapeAttribute(fileName) + '"><i class="fas fa-download"></i><span>' + escapeAttribute(getLabel('media_download', 'Download')) + '</span></button>' +
                            '<button data-action="media-rename" data-id="' + (file.id || 0) + '" data-name="' + escapeAttribute(fileName) + '" data-path="' + escapeAttribute(filePath) + '"><i class="fas fa-i-cursor"></i><span>' + escapeAttribute(getLabel('media_rename', 'Rename')) + '</span></button>' +
                            '<button data-action="media-copy-url" data-url="' + escapeAttribute(file.url) + '"><i class="fas fa-link"></i><span>' + escapeAttribute(getLabel('copy_url', 'Copy URL')) + '</span></button>' +
                            '<div class="media-browser-actions-divider"></div>' +
                            '<button class="action-delete" data-action="media-delete-open" data-id="' + (file.id || 0) + '" data-name="' + escapeAttribute(fileName) + '" data-path="' + escapeAttribute(filePath) + '"><i class="fas fa-trash"></i><span>' + escapeAttribute(getLabel('delete', 'Delete')) + '</span></button>' +
                        '</div>' +
                    '</div>' +
                    preview +
                    '<div class="media-browser-item-info" title="' + escapeHtml(fileName) + '">' + escapeHtml(fileName) + '</div>';
            }

            var checkbox = item.querySelector('[data-media-select]');
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    item.classList.toggle('selected', checkbox.checked);
                    syncMediaBatchState();
                });

                var selectOverlay = item.querySelector('.media-browser-select');
                if (selectOverlay) {
                    selectOverlay.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        checkbox.checked = !checkbox.checked;
                        item.classList.toggle('selected', checkbox.checked);
                        syncMediaBatchState();
                    });
                }
            }

            item.addEventListener('click', function(e) {
                if (e.target.closest('.media-browser-actions') || e.target.closest('.media-browser-item-actions') || e.target.closest('.media-browser-select') || e.target.closest('.table-actions') || e.target.closest('.media-select-column')) return;
                var filesGrid = document.getElementById('filesGrid');
                if (filesGrid) {
                    filesGrid.querySelectorAll('.media-browser-item.active').forEach(function(el) {
                        el.classList.remove('active');
                    });
                }
                item.classList.add('active');

                var driveDD = document.getElementById('mediaDriveDropdown');
                if (driveDD) {
                    driveDD.querySelectorAll('.media-tree-item').forEach(function(el) {
                        el.classList.remove('active');
                    });
                    var filePath = file.path || '';
                    driveDD.querySelectorAll('.media-tree-item[data-path]').forEach(function(el) {
                        if (el.getAttribute('data-path') === filePath) {
                            el.classList.add('active');
                        }
                    });
                }

                showFileInfobar(file);
            });

            item.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    type: 'file',
                    folder: file._rootFolder || currentFolder,
                    context: currentContext,
                    name: file.original_name || file.name,
                    path: file.path || ''
                }));
                e.dataTransfer.effectAllowed = 'move';
                item.classList.add('dragging');
            });

            item.addEventListener('dragend', function() {
                item.classList.remove('dragging');
            });

            if (listTbody) {
                listTbody.appendChild(item);
            } else {
                filesGrid.appendChild(item);
            }
        });

        syncMediaBatchState();
    }

    function showFileInfobar(file) {
        var infobar = document.getElementById('mediaInfobar');
        if (!infobar) return;

        infobar.classList.add('open');

        var toggleBtn = document.querySelector('[data-toolbar-action="infobar-toggle"]');
        if (toggleBtn) toggleBtn.classList.add('active');

        var setField = function(id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = value || '-';
        };

        setField('infobarFileName', file.original_name || file.name || '');
        setField('infobarFolder', file.folder || currentFolder || '');
        setField('infobarType', file.type || '');
        setField('infobarCreated', file.created_at || '');
        setField('infobarModified', file.updated_at || file.created_at || '');
        setField('infobarDimensions', file.dimensions ? file.dimensions.width + ' x ' + file.dimensions.height : '');
        setField('infobarSize', file.size ? formatBytes(file.size) : '');
        setField('infobarMime', file.mime || '');
        setField('infobarExtension', file.extension || '');
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        var units = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(i > 0 ? 1 : 0) + ' ' + units[i];
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
            var card = checkbox.closest('.media-browser-item') || checkbox.closest('.media-item');
            if (!card) {
                return;
            }

            card.classList.toggle('is-selected', !!checkbox.checked);
        });
    }

    function setMediaBatchVisibility(hasFiles) {
        var form = document.querySelector('[data-media-batch-form]');
        var folderInput = document.getElementById('mediaBatchFolder');
        var contextInput = document.getElementById('mediaBatchContext');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (folderInput instanceof HTMLInputElement) {
            folderInput.value = hasFiles && currentFolder ? String(currentFolder) : '';
        }
        if (contextInput instanceof HTMLInputElement) {
            contextInput.value = hasFiles && currentContext ? String(currentContext) : '';
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
        var selectAll = document.getElementById('mediaSelectAll');
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

        var emptySelectionMessage = String(form.getAttribute('data-empty-selection-message') || '').trim();

        document.addEventListener('change', function(event) {
            var target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
            }

            if (target.id === 'mediaSelectAll') {
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

        var headerSelectAll = document.getElementById('mediaSelectAll');
        if (headerSelectAll instanceof HTMLInputElement) {
            headerSelectAll.checked = false;
            headerSelectAll.indeterminate = false;
        }

        syncMediaBatchState();
    }

    function closeAllDropdowns() {
        document.querySelectorAll('.media-browser-actions-list.open').forEach(function(l) {
            l.classList.remove('open');
            l.style.left = '';
            l.style.right = '';
            l.style.maxWidth = '';
        });
    }

    function bindMediaActions() {
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.media-browser-actions')) {
                closeAllDropdowns();
            }

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

            const toggle = e.target.closest('.media-browser-actions-toggle');
            if (toggle) {
                e.preventDefault();
                var list = toggle.parentElement.querySelector('.media-browser-actions-list');
                if (!list) return;

                document.querySelectorAll('.media-browser-actions-list.open').forEach(function(l) {
                    if (l !== list) {
                        l.classList.remove('open');
                        l.style.left = '';
                        l.style.right = '';
                    }
                });

                if (list.classList.contains('open')) {
                    list.classList.remove('open');
                    list.style.left = '';
                    list.style.right = '';
                    list.style.maxWidth = '';
                } else {
                    var toggleRect = toggle.getBoundingClientRect();
                    var viewportMid = window.innerWidth / 2;

                    list.style.visibility = 'hidden';
                    list.classList.add('open');

                    if (toggleRect.left < viewportMid) {
                        list.style.left = '0';
                        list.style.right = 'auto';
                    } else {
                        list.style.left = '';
                        list.style.right = '';
                    }

                    var r = list.getBoundingClientRect();
                    if (r.right > window.innerWidth) {
                        list.style.maxWidth = Math.max(160, window.innerWidth - r.left - 8) + 'px';
                    }

                    list.style.visibility = '';
                }
                return;
            }

            const actionEl = e.target.closest('[data-action]');
            if (!actionEl) return;

            closeAllDropdowns();

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
                    if (!isAuthorizedFolder(currentFolder)) {
                        showRootUploadForbidden();
                        break;
                    }
                    openUploadModal();
                    break;
                case 'media-directory-open':
                    e.preventDefault();
                    if (currentFolder === null) {
                        selectFolder(actionEl.dataset.path || '');
                    } else {
                        selectDirectory(actionEl.dataset.path || '');
                    }
                    break;
                case 'media-directory-create-open':
                    e.preventDefault();
                    if (!isAuthorizedFolder(currentFolder)) {
                        showRootDirectoryForbidden();
                        break;
                    }
                    openDirectoryModal();
                    break;
                case 'media-directory-create-confirm':
                    e.preventDefault();
                    confirmCreateDirectory();
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
                case 'media-preview':
                    e.preventDefault();
                    openPreviewModal(
                        actionEl.dataset.url || '',
                        actionEl.dataset.mime || '',
                        actionEl.dataset.name || ''
                    );
                    break;
                case 'media-download':
                    e.preventDefault();
                    downloadFile(actionEl.dataset.url || '', actionEl.dataset.name || '');
                    break;
                case 'media-rename':
                    e.preventDefault();
                    openRenameModal(
                        Number(actionEl.dataset.id || 0),
                        actionEl.dataset.name || '',
                        actionEl.dataset.path || ''
                    );
                    break;
                case 'media-rename-confirm':
                    e.preventDefault();
                    confirmRename();
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
        if (modalId === 'directoryModal') {
            closeDirectoryModal();
            return;
        }
        if (modalId === 'renameModal') {
            closeRenameModal();
            return;
        }
        if (modalId === 'previewModal') {
            var previewContent = document.querySelector('#previewModal .media-preview-content');
            if (previewContent) previewContent.remove();
        }
        closeModal(modalId);
    }

    /**
     * Sélectionner un dossier (onglet)
     */
    function syncTreeState(path, dropdown) {
        if (!dropdown) return;
        dropdown.querySelectorAll('.media-tree-folder').forEach(function(item) {
            item.classList.remove('expanded');
        });
        dropdown.querySelectorAll('.media-tree-item').forEach(function(item) {
            item.classList.remove('active');
        });
        dropdown.querySelectorAll('.media-tree-item[data-path]').forEach(function(item) {
            if (item.getAttribute('data-path') === path) {
                item.classList.add('active');
                if (item.classList.contains('media-tree-folder')) {
                    item.classList.add('expanded');
                }
                var parent = item.parentElement ? item.parentElement.closest('.media-tree-folder') : null;
                while (parent) {
                    parent.classList.add('expanded');
                    parent.classList.add('active');
                    parent = parent.parentElement ? parent.parentElement.closest('.media-tree-folder') : null;
                }
            }
        });
    }

    function getDriveLabelText() {
        if (currentFolder === null) {
            return getLabel('media_uploads_root', 'Fichiers uploadés');
        }
        if (currentContext !== '') {
            var parts = currentContext.split('/');
            var lastPart = parts[parts.length - 1];
            return getFolderLabel(lastPart) || lastPart;
        }
        return getFolderLabel(currentFolder);
    }

    function updateDriveLabel() {
        var driveLbl = document.getElementById('mediaDriveLabel');
        if (driveLbl) {
            driveLbl.textContent = getDriveLabelText();
        }
    }

    function selectFolder(folderName) {
        currentFolder = folderName;
        setCurrentContext('');

        var searchInput = document.getElementById('mediaSearchInput');
        if (searchInput) searchInput.value = '';
        
        document.querySelectorAll('.media-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.folder === folderName);
        });
        
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

        updateDriveLabel();

        var driveDD = document.getElementById('mediaDriveDropdown');
        if (driveDD) {
            syncTreeState(folderName, driveDD);
        }

        const directoryPanel = document.getElementById('mediaDirectoryPanel');
        showElement(directoryPanel);
        loadDirectories(folderName);
        loadFiles(folderName, currentContext);
        updateBreadcrumb();
    }

    window.selectFolder = selectFolder;

    /* ============================================
       ROOT VIEW (uploads/ top-level folders)
       ============================================ */

    function loadRootDirectories() {
        currentFolder = null;
        currentContext = '';
        currentDirectories = [];
        currentFiles = [];

        var searchInput = document.getElementById('mediaSearchInput');
        if (searchInput) searchInput.value = '';

        document.querySelectorAll('.media-tab').forEach(function(tab) {
            tab.classList.remove('active');
        });

        var uploadZone = document.getElementById('uploadZone');
        if (uploadZone) uploadZone.classList.add('hidden');

        var directoryPanel = document.getElementById('mediaDirectoryPanel');
        showElement(directoryPanel);

        var filesList = document.getElementById('filesList');
        showElement(filesList);

        var currentFolderName = document.getElementById('currentFolderName');
        if (currentFolderName) {
            currentFolderName.textContent = getLabel('media_uploads_root', 'Fichiers uploadés');
        }

        var filesGrid = document.getElementById('filesGrid');
        if (filesGrid) {
            filesGrid.querySelectorAll('.media-browser-item, .media-directory-item, .table-wrapper').forEach(function(el) { el.remove(); });
        }

        setMediaBatchVisibility(false);
        var filesLoading = document.getElementById('filesLoading');
        hideElement(filesLoading);

        var tree = config.directoryTree || {};
        var children = Array.isArray(tree.children) ? tree.children : [];

        var rootDirs = children.filter(function(child) {
            return child.type === 'directory';
        }).map(function(child) {
            return {
                name: child.name,
                path: child.name,
                depth: 1,
                files_count: child.count || 0,
                subdir_count: Array.isArray(child.children) ? child.children.length : 0,
                subdirs: Array.isArray(child.children) ? child.children.map(function(c) { return c.name; }) : []
            };
        });

        currentDirectories = rootDirs;
        renderDirectoriesIntoGrid();
        updateBreadcrumb();

        var filesCount = document.getElementById('filesCount');
        if (filesCount) filesCount.textContent = rootDirs.length;

        var filesInLabel = document.getElementById('filesInLabel');
        if (filesInLabel) filesInLabel.textContent = getFileLabel(rootDirs.length, 'in');

        updateDriveLabel();
    }

    /* ============================================
       DRIVE DROPDOWN (toolbar disk/drive/tree)
       ============================================ */
    var driveDropdown = document.getElementById('mediaDriveDropdown');

    if (driveDropdown) {
        driveDropdown.addEventListener('click', function(event) {
            var toggle = event.target.closest('.media-tree-toggle');
            if (toggle) {
                event.preventDefault();
                event.stopPropagation();
                var folder = toggle.closest('.media-tree-folder');
                if (folder) {
                    folder.classList.toggle('expanded');
                }
                return;
            }

            var rootLink = event.target.closest('.media-tree-root-link');
            if (rootLink) {
                event.preventDefault();

                loadRootDirectories();
                driveDropdown.removeAttribute('open');
                driveDropdown.querySelectorAll('.media-tree-folder').forEach(function(item) {
                    item.classList.remove('expanded');
                });
                driveDropdown.querySelectorAll('.media-tree-item').forEach(function(item) {
                    item.classList.toggle('active', item.hasAttribute('data-folder-root'));
                });
                return;
            }

            var link = event.target.closest('[data-select-folder]');
            if (link) {
                var folderItem = link.closest('.media-tree-folder');
                if (!folderItem) return;

                event.preventDefault();
                var folderName = link.getAttribute('data-select-folder');

                var parts = folderName.split('/');
                var topFolder = parts[0];
                var subContext = parts.slice(1).join('/');

                currentFolder = topFolder;
                setCurrentContext(subContext);

                document.querySelectorAll('.media-tab').forEach(function(tab) {
                    tab.classList.toggle('active', tab.dataset.folder === topFolder);
                });

                syncTreeState(folderName, driveDropdown);

                updateDriveLabel();

                var directoryPanel = document.getElementById('mediaDirectoryPanel');
                var filesList = document.getElementById('filesList');
                showElement(directoryPanel);
                showElement(filesList);

                loadDirectories(topFolder, subContext);
                loadFiles(topFolder, currentContext);
                updateBreadcrumb();
                driveDropdown.removeAttribute('open');
                return;
            }

            var treeFile = event.target.closest('.media-tree-file');
            if (treeFile) {
                event.preventDefault();
                event.stopPropagation();
                driveDropdown.querySelectorAll('.media-tree-item').forEach(function(item) {
                    item.classList.remove('active');
                });
                treeFile.classList.add('active');
                return;
            }
        });
    }

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

    function appendQueryParams(url, params) {
        const base = String(url || '').trim();
        if (base === '') {
            return '';
        }

        const searchParams = new URLSearchParams();
        Object.keys(params || {}).forEach(function(key) {
            const value = params[key];
            if (value !== undefined && value !== null && String(value) !== '') {
                searchParams.set(key, String(value));
            }
        });

        const query = searchParams.toString();
        if (query === '') {
            return base;
        }

        return base + (base.includes('?') ? '&' : '?') + query;
    }

    function buildFallbackApiUrl(folderName, context) {
        const params = { folder: folderName };
        if (normalizeContext(context) !== '') {
            params.context = normalizeContext(context);
        }

        return buildApiUrlFromLocation('admin/media/api/files', params);
    }

    function buildDirectoryApiUrl(folderName) {
        var params = { folder: folderName };
        if (currentContext !== '') {
            params.context = currentContext;
        }
        return buildApiUrlFromLocation('admin/media/api/directories', params);
    }

    function buildPublicDirectoryApiUrl(folderName) {
        const origin = window.location.origin || '';
        var url = origin + '/public/index.php?path=admin/media/api/directories&folder=' + encodeURIComponent(folderName);
        if (currentContext !== '') {
            url += '&context=' + encodeURIComponent(currentContext);
        }
        return url;
    }

    function buildPublicFallbackApiUrl(folderName, context) {
        const origin = window.location.origin || '';
        const params = new URLSearchParams({
            path: 'admin/media/api/files',
            folder: folderName,
        });
        if (normalizeContext(context) !== '') {
            params.set('context', normalizeContext(context));
        }

        return origin + '/public/index.php?' + params.toString();
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

    function getDirectoryCountLabel(count) {
        var key = count === 1 ? 'media_folder_count' : 'media_folders_count';
        var fallback = count === 1 ? '%d folder' : '%d folders';
        return getLabel(key, fallback).replace('%d', String(count));
    }

    function updateFilesCountDisplay(count) {
        const filesCount = document.getElementById('filesCount');
        if (filesCount) filesCount.textContent = count;
        const filesInLabel = document.getElementById('filesInLabel');
        if (filesInLabel) filesInLabel.textContent = getFileLabel(count, 'in');
    }

    function setCurrentContext(context) {
        currentContext = normalizeContext(context);

        const uploadContextInput = document.getElementById('uploadContext');
        if (uploadContextInput instanceof HTMLInputElement) {
            uploadContextInput.value = currentContext;
        }

        const batchContextInput = document.getElementById('mediaBatchContext');
        if (batchContextInput instanceof HTMLInputElement) {
            batchContextInput.value = currentContext;
        }

        updateDirectoryHeader();
    }

    function updateDirectoryHeader() {
        const currentFolderName = document.getElementById('currentFolderName');

        if (currentFolderName) {
            const folderLabel = getFolderLabel(currentFolder);
            const directoryLabel = getCurrentDirectoryLabel(currentContext);
            currentFolderName.textContent = currentContext === '' ? folderLabel : folderLabel + ' / ' + directoryLabel;
        }
    }

    function loadDirectories(folderName) {
        if (!folderName) {
            return;
        }

        var params = { folder: folderName };
        if (currentContext !== '') {
            params.context = currentContext;
        }
        var configuredUrl = appendQueryParams(config.apiDirectoriesUrl || '', params);
        var primaryUrl = buildDirectoryApiUrl(folderName);
        var publicFallbackUrl = buildPublicDirectoryApiUrl(folderName);

        fetchJsonWithFallback(primaryUrl, [configuredUrl, publicFallbackUrl])
            .then(function(data) {
                if (!data || data.success !== true) {
                    renderDirectories([]);
                    return;
                }
                renderDirectories(data.directories || []);
            })
            .catch(function(error) {
                console.error('Error loading directories:', error);
                renderDirectories([]);
            });
    }

    function renderDirectories(directories) {
        var items = Array.isArray(directories) ? directories : [];
        currentDirectories = items;
        renderDirectoriesIntoGrid();
    }

    function renderDirectoriesIntoGrid() {
        var filesGrid = document.getElementById('filesGrid');
        if (!filesGrid) return;

        var existing = filesGrid.querySelectorAll('.media-directory-item');
        existing.forEach(function(el) { el.remove(); });

        if (currentViewMode === 'list' && !filesGrid.querySelector('.table-wrapper')) {
            var tableWrapper = document.createElement('div');
            tableWrapper.className = 'table-wrapper';

            var table = document.createElement('table');
            table.className = 'table';

            var thead = document.createElement('thead');
            thead.innerHTML = '<tr>' +
                '<th class="media-select-column"><input type="checkbox" class="media-row-checkbox" id="mediaSelectAll" aria-label="' + escapeAttribute(getLabel('media_select_all', 'Tout sélectionner')) + '"></th>' +
                '<th class="media-thumb-column">' + escapeHtml(getLabel('media_preview', 'Vignette')) + '</th>' +
                '<th>' + escapeHtml(getLabel('media_name', 'Nom du fichier')) + '</th>' +
                '<th>' + escapeHtml(getLabel('media_folder', 'Dossier')) + '</th>' +
                '<th>' + escapeHtml(getLabel('media_dimensions', 'Dimensions')) + '</th>' +
                '<th>' + escapeHtml(getLabel('media_size', 'Poids')) + '</th>' +
                '<th class="table-actions-header">' + escapeHtml(getLabel('media_actions', 'Actions')) + '</th>' +
                '</tr>';
            table.appendChild(thead);

            var dirTbody = document.createElement('tbody');
            table.appendChild(dirTbody);
            tableWrapper.appendChild(table);
            filesGrid.appendChild(tableWrapper);
        }

        if (currentDirectories.length === 0) return;

        var contextDepth = currentContext === '' ? 0 : currentContext.split('/').length;

        var children = currentDirectories.filter(function(d) {
            var depth = Number(d.depth || 0);
            var path = normalizeContext(d.path || '');
            return depth === contextDepth + 1 && path !== currentContext;
        });

        if (children.length === 0) return;

        var sorted = children.sort(function(a, b) {
            return ((a.name || '') < (b.name || '') ? -1 : 1);
        });

        sorted.forEach(function(directory) {
            var path = normalizeContext(directory.path || '');
            var directoryName = String(directory.name || path);
            var count = Number(directory.files_count || 0);
            var subdirCount = Number(directory.subdir_count || 0);
            var subdirNames = Array.isArray(directory.subdirs) ? directory.subdirs : [];
            var metaHtml = '';
            if (count > 0) {
                metaHtml = count + ' ' + escapeHtml(getFileLabel(count, 'label'));
            } else if (subdirCount > 0) {
                metaHtml = escapeHtml(getDirectoryCountLabel(subdirCount));
            } else {
                metaHtml = '0 ' + escapeHtml(getFileLabel(0, 'label'));
            }
            var item = document.createElement(currentViewMode === 'list' ? 'tr' : 'div');
            item.className = 'media-directory-item';
            item.draggable = true;
            item.dataset.context = path;
            item.dataset.moveType = 'directory';
            item.dataset.moveName = directoryName;
            var deletePath = escapeAttribute(currentFolder + '/' + path);

            if (currentViewMode === 'list') {
                item.innerHTML =
                    '<td class="media-select-column"></td>' +
                    '<td class="media-thumb-column"><i class="fas fa-folder media-folder-row-icon"></i></td>' +
                    '<td><span class="media-file-name">' + escapeHtml(directoryName) + '</span></td>' +
                    '<td><span class="media-folder-path">-</span></td>' +
                    '<td>-</td>' +
                    '<td>' + metaHtml + '</td>' +
                    '<td><div class="table-actions table-actions-compact">' +
                        '<button type="button" class="table-action table-action-view" data-action="media-directory-open" data-path="' + escapeAttribute(path) + '" title="' + escapeAttribute(getLabel('media_open', 'Open')) + '"><i class="fas fa-folder-open"></i></button>' +
                        '<button type="button" class="table-action table-action-delete" data-action="media-delete-open" data-id="0" data-name="' + escapeAttribute(directoryName) + '" data-path="' + deletePath + '" title="' + escapeAttribute(getLabel('delete', 'Delete')) + '"><i class="fas fa-trash"></i></button>' +
                    '</div></td>';
            } else {
                item.innerHTML =
                    '<div class="media-browser-actions">' +
                        '<button class="media-browser-actions-toggle"><i class="fas fa-ellipsis-v"></i></button>' +
                        '<div class="media-browser-actions-list">' +
                            '<span class="media-browser-actions-item-name"><strong>' + escapeHtml(directoryName) + '</strong></span>' +
                            '<button data-action="media-directory-open" data-path="' + escapeAttribute(path) + '"><i class="fas fa-folder-open"></i><span>' + escapeAttribute(getLabel('media_open', 'Open')) + '</span></button>' +
                            '<div class="media-browser-actions-divider"></div>' +
                            '<button class="action-delete" data-action="media-delete-open" data-id="0" data-name="' + escapeAttribute(directoryName) + '" data-path="' + deletePath + '"><i class="fas fa-trash"></i><span>' + escapeAttribute(getLabel('delete', 'Delete')) + '</span></button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="media-directory-item-preview"><i class="fas fa-folder"></i></div>' +
                    '<div class="media-directory-item-info">' +
                        '<span class="media-directory-item-name">' + escapeHtml(directoryName) + '</span>' +
                        '<span class="media-directory-item-meta">' + metaHtml + '</span>' +
                    '</div>';
            }

            item.addEventListener('click', function(e) {
                if (e.target.closest('.media-browser-actions') || e.target.closest('.media-browser-item-actions') || e.target.closest('.table-actions') || e.target.closest('.media-select-column')) return;
                if (currentFolder === null) {
                    selectFolder(directoryName);
                } else {
                    selectDirectory(path);
                }
            });

            item.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    type: 'directory',
                    folder: currentFolder,
                    context: currentContext,
                    name: directoryName,
                    path: path
                }));
                e.dataTransfer.effectAllowed = 'move';
                item.classList.add('dragging');
                e.stopPropagation();
            });

            item.addEventListener('dragend', function() {
                item.classList.remove('dragging');
            });

            item.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.dataTransfer.dropEffect = 'move';
                item.classList.add('drag-over');
            });

            item.addEventListener('dragleave', function() {
                item.classList.remove('drag-over');
            });

            item.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                item.classList.remove('drag-over');
                handleMoveDrop(e, path);
            });

            if (currentViewMode === 'list') {
                var dirTbody = filesGrid.querySelector('.table-wrapper .table tbody');
                if (dirTbody) {
                    dirTbody.insertBefore(item, dirTbody.firstChild);
                }
            } else {
                filesGrid.insertBefore(item, filesGrid.firstChild);
            }
        });
    }

    function selectDirectory(context) {
        setCurrentContext(context);
        var fullPath = context ? currentFolder + '/' + context : currentFolder;
        var driveDD = document.getElementById('mediaDriveDropdown');
        if (driveDD) {
            syncTreeState(fullPath, driveDD);
        }
        updateDriveLabel();
        loadDirectories(currentFolder);
        loadFiles(currentFolder, currentContext);
        updateBreadcrumb();
    }

    function handleMoveDrop(e, targetContext) {
        var raw = e.dataTransfer.getData('text/plain');
        if (!raw) return;

        var data;
        try {
            data = JSON.parse(raw);
        } catch (err) {
            return;
        }

        if (!data || !data.type || !data.name) return;

        var formData = new FormData();
        formData.append('_token', config.csrfToken || '');
        formData.append('folder', data.folder || currentFolder);
        formData.append('context', data.context || '');
        formData.append('item', data.name);
        formData.append('target', targetContext || '');
        formData.append('type', data.type);

        fetch(config.apiMoveUrl || '', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'include',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(result) {
            if (result.success) {
                showToast(getLabel('media_move_success', 'Item moved successfully.'), 'success');
                loadDirectories(currentFolder);
                loadFiles(currentFolder, currentContext);
                updateTabCount(currentFolder);
            } else {
                var msg = result.message || result.error || getLabel('media_move_failed', 'Move failed.');
                showToast(msg, 'error');
            }
        })
        .catch(function() {
            showToast(getLabel('media_move_failed', 'Move failed.'), 'error');
        });
    }

    function updateBreadcrumb() {
        var container = document.getElementById('mediaBreadcrumb');
        if (!container) return;
        container.innerHTML = '';

        var rootBtn = document.createElement('button');
        rootBtn.type = 'button';
        rootBtn.className = 'media-breadcrumb-link';
        rootBtn.textContent = getLabel('media_uploads_root', 'Fichiers uploadés');
        rootBtn.addEventListener('click', function() {
            if (currentFolder === null) {
                loadRootDirectories();
            } else {
                currentFolder = null;
                currentContext = '';
                var driveDD = document.getElementById('mediaDriveDropdown');
                if (driveDD) {
                    driveDD.querySelectorAll('.media-tree-folder').forEach(function(item) {
                        item.classList.remove('expanded');
                    });
                    driveDD.querySelectorAll('.media-tree-item').forEach(function(item) {
                        item.classList.remove('active');
                    });
                    driveDD.querySelectorAll('.media-tree-item[data-folder-root]').forEach(function(item) {
                        item.classList.add('active');
                    });
                }
                loadRootDirectories();
            }
        });
        container.appendChild(rootBtn);

        if (currentFolder) {
            var sep1 = document.createElement('span');
            sep1.className = 'media-breadcrumb-sep';
            sep1.textContent = ' / ';
            container.appendChild(sep1);

            var folderBtn = document.createElement('button');
            folderBtn.type = 'button';
            folderBtn.className = 'media-breadcrumb-link';
            folderBtn.textContent = getFolderLabel(currentFolder);
            var capturedFolder = currentFolder;
            folderBtn.addEventListener('click', function() {
                currentFolder = capturedFolder;
                currentContext = '';
                var driveDD = document.getElementById('mediaDriveDropdown');
                if (driveDD) {
                    syncTreeState(capturedFolder, driveDD);
                }
                document.querySelectorAll('.media-tab').forEach(function(tab) {
                    tab.classList.toggle('active', tab.dataset.folder === capturedFolder);
                });
                loadDirectories(capturedFolder);
                loadFiles(capturedFolder, '');
                updateBreadcrumb();
            });
            container.appendChild(folderBtn);

            if (currentContext !== '') {
                var parts = currentContext.split('/');
                var builtContext = '';
                parts.forEach(function(part) {
                    builtContext = builtContext === '' ? part : builtContext + '/' + part;
                    var sep = document.createElement('span');
                    sep.className = 'media-breadcrumb-sep';
                    sep.textContent = ' / ';
                    container.appendChild(sep);

                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'media-breadcrumb-link';
                    btn.textContent = part;
                    var capturedContext = builtContext;
                    btn.addEventListener('click', function() {
                        setCurrentContext(capturedContext);
                        loadDirectories(currentFolder);
                        loadFiles(currentFolder, capturedContext);
                        updateBreadcrumb();
                    });
                    container.appendChild(btn);
                });

                var lastBtn = container.querySelector('.media-breadcrumb-link:last-of-type');
                if (lastBtn) {
                    lastBtn.classList.add('media-breadcrumb-current');
                    lastBtn.disabled = true;
                }
            } else {
                folderBtn.classList.add('media-breadcrumb-current');
                folderBtn.disabled = true;
            }
        } else {
            rootBtn.classList.add('media-breadcrumb-current');
            rootBtn.disabled = true;
        }
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
                while (fallbacks.length) {
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
    function loadFiles(folderName, context) {
        const filesGrid = document.getElementById('filesGrid');
        const filesLoading = document.getElementById('filesLoading');
        const filesCount = document.getElementById('filesCount');
        const activeContext = normalizeContext(context);
        
        if (filesGrid) {
            var existingFileItems = filesGrid.querySelectorAll('.media-browser-item');
            existingFileItems.forEach(function(el) { el.remove(); });
        }
        setMediaBatchVisibility(false);
        showElement(filesLoading);
        
        const params = { folder: folderName };
        if (activeContext !== '') {
            params.context = activeContext;
        }
        const filesUrl = appendQueryParams(config.apiFilesUrl || '', params);
        const primaryUrl = buildApiUrlFromLocation('admin/media/api/files', params);
        const fallbackUrl = buildFallbackApiUrl(folderName, activeContext);
        const publicFallbackUrl = buildPublicFallbackApiUrl(folderName, activeContext);
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
            }
        })
        .catch(error => {
            console.error('Error loading files:', error);
            hideElement(filesLoading);
            setMediaBatchVisibility(false);
        });
    }

    /**
     * Stocker les fichiers et appliquer filtres/tri/vue
     */
    function renderFiles(files) {
        currentFiles = Array.isArray(files) ? files.slice() : [];
        applyFiltersAndRender();
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
                if (!isAuthorizedFolder(currentFolder)) {
                    showRootUploadForbidden();
                    return;
                }
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
        if (files.length > 0 && isAuthorizedFolder(currentFolder)) {
            uploadFiles(files);
        } else if (files.length > 0) {
            showRootUploadForbidden();
        }
    }

    function setupFileInput() {
        const fileInput = document.getElementById('fileInput');
        if (!fileInput) return;

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0 && isAuthorizedFolder(currentFolder)) {
                uploadFiles(this.files);
            } else if (this.files.length > 0) {
                showRootUploadForbidden();
                this.value = '';
            }
        });
    }

    function setupMediaMoveDrop() {
        var grid = document.getElementById('filesGrid');
        if (!grid) return;

        var preventDefaults = function(e) {
            e.preventDefault();
            e.stopPropagation();
        };

        grid.addEventListener('dragover', function(e) {
            var dt = e.dataTransfer;
            if (dt.types && dt.types.indexOf && dt.types.indexOf('text/plain') !== -1) {
                e.preventDefault();
                e.stopPropagation();
                dt.dropEffect = 'move';
                grid.classList.add('drag-over');
            }
        });

        grid.addEventListener('dragleave', function(e) {
            grid.classList.remove('drag-over');
        });

        grid.addEventListener('drop', function(e) {
            grid.classList.remove('drag-over');
            var raw = e.dataTransfer.getData('text/plain');
            if (!raw) return;
            var data;
            try {
                data = JSON.parse(raw);
            } catch (err) {
                return;
            }
            if (!data || !data.type || !data.name) return;
            e.preventDefault();
            e.stopPropagation();
            handleMoveDrop(e, currentContext);
        });
    }

    /**
     * Upload des fichiers
     */
    function uploadFiles(files) {
        if (!isAuthorizedFolder(currentFolder)) {
            showRootUploadForbidden();
            return;
        }
        
        const formData = new FormData();
        formData.append('folder', currentFolder);
        if (currentContext !== '') {
            formData.append('media_context', currentContext);
        }
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
                    loadDirectories(currentFolder);
                    loadFiles(currentFolder, currentContext);
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
        const configuredUrl = config.apiStatsUrl || '';
        const primaryUrl = buildApiUrlFromLocation('admin/media/api/stats', {});
        const publicFallbackUrl = (window.location.origin || '') + '/public/index.php?path=admin/media/api/stats';

        fetchJsonWithFallback(primaryUrl, [configuredUrl, publicFallbackUrl])
        .then(data => {
            if (data.success && data.stats) {
                const tab = document.querySelector(`.media-tab[data-folder="${folderName}"]`);
                if (tab) {
                    const countEl = tab.querySelector('.media-tab-count');
                    const countValue = Number(data.stats[folderName] || 0);
                    if (countEl) {
                        countEl.textContent = countValue + ' ' + getFileLabel(countValue, 'label');
                    }
                }
            }
        });
    }

    function openDirectoryModal() {
        if (!isAuthorizedFolder(currentFolder)) {
            showRootDirectoryForbidden();
            return;
        }

        const input = document.getElementById('directoryName');
        if (input instanceof HTMLInputElement) {
            input.value = '';
        }

        openModal('directoryModal');

        if (input instanceof HTMLInputElement) {
            input.focus();
        }
    }

    function closeDirectoryModal() {
        closeModal('directoryModal');
    }

    function resolveDirectoryCreateContext(rawValue) {
        const requested = normalizeContext(rawValue);
        if (requested === '') {
            return '';
        }

        if (currentContext !== '' && requested.indexOf('/') === -1) {
            return normalizeContext(currentContext + '/' + requested);
        }

        return requested;
    }

    function confirmCreateDirectory() {
        if (!isAuthorizedFolder(currentFolder)) {
            showRootDirectoryForbidden();
            return;
        }

        const input = document.getElementById('directoryName');
        const context = resolveDirectoryCreateContext(input instanceof HTMLInputElement ? input.value : '');
        if (context === '') {
            showToast(getLabel('directory_invalid', ''), 'error');
            return;
        }

        const formData = new FormData();
        formData.append('folder', currentFolder);
        formData.append('context', context);
        formData.append('_token', config.csrfToken);

        fetch(config.createDirectoryUrl || buildApiUrlFromLocation('admin/media/api/directories', {}), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (!data || data.success !== true) {
                    showToast((data && (data.message || data.error)) || getLabel('directory_create_error', ''), 'error');
                    return;
                }

                closeDirectoryModal();
                showToast(data.message || getLabel('directory_created', ''), 'success');
                loadDirectories(currentFolder);
                loadFiles(currentFolder, currentContext);
            })
            .catch(function(error) {
                console.error('Directory creation error:', error);
                showToast(getLabel('directory_create_error', ''), 'error');
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
                loadDirectories(currentFolder);
                loadFiles(currentFolder, currentContext);
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
        navigator.clipboard.writeText(url).then(function() {
            showToast(getLabel('media_url_copied', 'URL copied'), 'success');
        }).catch(function() {
            var input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            showToast(getLabel('media_url_copied', 'URL copied'), 'success');
        });
    }

    function openPreviewModal(url, mime, name) {
        var modal = document.getElementById('previewModal');
        if (!modal) return;

        var body = modal.querySelector('.modal-body') || modal;
        var content = '';

        if (mime && mime.startsWith('image/')) {
            content = '<div class="media-preview-content"><img class="media-preview-visual" src="' + escapeAttribute(url) + '" alt="' + escapeAttribute(name) + '"></div>';
        } else if (mime && mime.startsWith('video/')) {
            content = '<div class="media-preview-content"><video class="media-preview-visual" src="' + escapeAttribute(url) + '" controls></video></div>';
        } else if (mime && mime.startsWith('audio/')) {
            content = '<div class="media-preview-content"><div class="media-preview-icon-frame"><i class="fas fa-music media-preview-icon"></i></div><audio class="media-preview-audio" src="' + escapeAttribute(url) + '" controls></audio></div>';
        } else if (mime === 'application/pdf') {
            content = '<div class="media-preview-content"><iframe class="media-preview-frame" src="' + escapeAttribute(url) + '"></iframe></div>';
        } else if (mime && (mime.startsWith('text/') || mime === 'application/json' || mime === 'application/xml')) {
            content = '<div class="media-preview-content"><pre class="media-text-preview">' + escapeHtml(getLabel('loading', 'Loading...')) + '</pre></div>';
        } else {
            content = '<div class="media-preview-content media-preview-content-empty"><i class="fas fa-file media-preview-icon"></i><p class="media-preview-filename">' + escapeHtml(name) + '</p></div>';
        }

        var existing = body.querySelector('.media-preview-content');
        if (existing) existing.remove();
        body.insertAdjacentHTML('beforeend', content);

        openModal('previewModal');

        if (mime && (mime.startsWith('text/') || mime === 'application/json' || mime === 'application/xml') && url) {
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(text) {
                var pre = body.querySelector('.media-text-preview');
                if (pre) pre.textContent = text;
            })
            .catch(function() {
                var pre = body.querySelector('.media-text-preview');
                if (pre) pre.textContent = getLabel('error', 'Error') + ': ' + escapeHtml(name);
            });
        }
    }

    function downloadFile(url, name) {
        var a = document.createElement('a');
        a.href = url;
        a.download = name;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    function openRenameModal(id, currentName, path) {
        renameMediaId = id;
        renameMediaPath = path;

        var input = document.getElementById('renameInput');
        if (input instanceof HTMLInputElement) {
            input.value = currentName;
        }

        openModal('renameModal');

        if (input instanceof HTMLInputElement) {
            input.select();
        }
    }

    function closeRenameModal() {
        closeModal('renameModal');
        renameMediaId = null;
        renameMediaPath = null;
    }

    function confirmRename() {
        var input = document.getElementById('renameInput');
        if (!(input instanceof HTMLInputElement)) return;

        var newName = input.value.trim();
        if (!newName) return;

        var params = new URLSearchParams();
        params.set('_token', config.csrfToken || '');
        params.set('id', String(renameMediaId));
        params.set('new_name', newName);
        params.set('path', renameMediaPath);
        params.set('folder', currentFolder || '');

        fetch(config.deletePathUrl || '', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            credentials: 'include',
            body: params.toString()
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            closeRenameModal();
            if (data.success) {
                loadFiles(currentFolder, currentContext);
            } else {
                showToast(getLabel('media_rename_error', 'Rename failed'), 'error');
            }
        })
        .catch(function() {
            closeRenameModal();
            showToast(getLabel('media_rename_error', 'Rename failed'), 'error');
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
                        loadFiles(currentFolder, currentContext);
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
                handleModalClose('directoryModal');
                handleModalClose('renameModal');
            }
        });

        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    handleModalClose(modal.id);
                }
            });
        });

        const directoryForm = document.getElementById('directoryForm');
        if (directoryForm instanceof HTMLFormElement) {
            directoryForm.addEventListener('submit', function(event) {
                event.preventDefault();
                confirmCreateDirectory();
            });
        }

        const renameInput = document.getElementById('renameInput');
        if (renameInput instanceof HTMLInputElement) {
            renameInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    confirmRename();
                }
            });
        }
    }

    /**
     * Toast notification
     */
    function showToast(message, type) {
        const text = String(message || '').trim();
        if (text === '') {
            return;
        }

        if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
            window.FlatCMS.toast.show(text, type);
        }
    }

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
