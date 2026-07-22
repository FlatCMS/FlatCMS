/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    function getActiveProvider() {
        var root = document.body || document.documentElement;
        return String(root && root.getAttribute ? root.getAttribute('data-wysiwyg-provider') : 'suneditor').toLowerCase();
    }

    function getTextarea() {
        return document.querySelector('textarea#content[data-post-suneditor]');
    }

    function parseMediaConfig(modal) {
        if (!modal) {
            return {};
        }
        var raw = String(modal.getAttribute('data-media-config') || '').trim();
        if (raw === '') {
            return {};
        }
        try {
            return JSON.parse(raw);
        } catch (error) {
            return {};
        }
    }

    function resolveMediaSource(file, uploadsBase) {
        var explicit = String((file && file.url) || '').trim();
        if (explicit !== '') {
            return explicit;
        }
        var path = String((file && file.path) || '').trim();
        if (path === '') {
            return '';
        }
        var base = String(uploadsBase || '/uploads').replace(/\/+$/, '');
        return base + '/' + path.replace(/^\/+/, '');
    }

    function escapeAttribute(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function closeMediaModal(modal) {
        if (!modal) {
            return;
        }
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function showToast(message, type) {
        var text = String(message || '').trim();
        if (text === '') {
            return;
        }
        if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
            window.FlatCMS.toast.show(text, type || 'warning');
        }
    }

    function normalizeMediaContext(value) {
        return String(value || '')
            .replace(/\\/g, '/')
            .trim()
            .split('/')
            .map(function(part) {
                return part.replace(/[^a-z0-9_-]+/gi, '-').replace(/^-+|-+$/g, '').toLowerCase();
            })
            .filter(Boolean)
            .join('/')
            .slice(0, 160);
    }

    function shouldContextualizeMedia(file, folder, mediaContext) {
        var context = normalizeMediaContext(mediaContext);
        var path = String((file && file.path) || '').replace(/\\/g, '/').replace(/^\/+/, '').trim();
        var targetPrefix = String(folder || 'images').trim() + '/' + context + '/';

        return context !== '' && path !== '' && !path.startsWith(targetPrefix);
    }

    function getContextualizeUrl(baseConfig) {
        var explicit = String((baseConfig && baseConfig.contextualizeUrl) || '').trim();
        if (explicit !== '') {
            return explicit;
        }

        var uploadUrl = String((baseConfig && baseConfig.uploadUrl) || '').trim();
        if (uploadUrl === '') {
            return '';
        }

        var front = uploadUrl.split('?')[0] || uploadUrl;
        return front + '?path=admin/media/api/contextualize';
    }

    function contextualizeMedia(file, baseConfig, folder, mediaContext) {
        if (!shouldContextualizeMedia(file, folder, mediaContext)) {
            return Promise.resolve(file);
        }

        var contextualizeUrl = getContextualizeUrl(baseConfig);
        var csrfToken = String((baseConfig && baseConfig.csrfToken) || '').trim();
        if (contextualizeUrl === '' || csrfToken === '') {
            return Promise.reject(new Error('contextualize_unavailable'));
        }

        var formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('path', String((file && file.path) || ''));
        formData.append('folder', String(folder || 'images'));
        formData.append('media_context', normalizeMediaContext(mediaContext));

        return fetch(contextualizeUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'include',
            body: formData,
        }).then(function(response) {
            return response.text().then(function(text) {
                var payload = null;
                try {
                    payload = text ? JSON.parse(text) : null;
                } catch (error) {
                    payload = null;
                }

                if (!response.ok || !payload || payload.success === false || !payload.media) {
                    throw new Error('contextualize_failed');
                }

                return payload.media;
            });
        });
    }

    function insertHtmlWithFallback(editor, textarea, html) {
        var inserted = false;

        try {
            editor.focus();
            if (typeof editor.insertHTML === 'function') {
                editor.insertHTML(html, true, true, true);
                inserted = true;
            }
        } catch (error) {
            inserted = false;
        }

        if (!inserted) {
            try {
                if (typeof editor.appendContents === 'function') {
                    editor.appendContents(html);
                    inserted = true;
                }
            } catch (error) {
                inserted = false;
            }
        }

        if (!inserted) {
            textarea.value = String(textarea.value || '') + String(html || '');
            if (typeof editor.setContents === 'function') {
                editor.setContents(textarea.value);
            }
        }
    }

    function openMediaModalForEditor(editor, textarea, mediaModalError, options) {
        var modal = document.getElementById('mediaModal');
        if (!modal || typeof window.initMediaModal !== 'function') {
            showToast(mediaModalError, 'warning');
            return;
        }

        var baseConfig = parseMediaConfig(modal);
        var uploadsBase = String(baseConfig.uploadsBase || '/uploads');
        var mode = String((options && options.mode) || 'images').toLowerCase() === 'files' ? 'files' : 'images';
        var folder = String((options && options.folder) || (mode === 'files' ? 'documents' : 'images')).trim();
        var mediaContext = String((options && options.mediaContext) || '').trim();
        var initialTab = String((options && options.initialTab) || 'library') === 'upload' ? 'upload' : 'library';
        var htmlBuilder = (options && typeof options.buildHtml === 'function') ? options.buildHtml : null;
        var replaceElement = options && options.replaceElement instanceof HTMLElement ? options.replaceElement : null;

        function applySelectedMedia(file) {
            var src = resolveMediaSource(file, uploadsBase);
            if (src !== '') {
                var alt = String((file && (file.original_name || file.name)) || '').trim();
                if (replaceElement) {
                    replaceMediaElement(replaceElement, file, src, alt);
                } else {
                    var html = '';
                    if (htmlBuilder) {
                        html = String(htmlBuilder(file, src) || '');
                    }
                    if (html === '') {
                        html = '<img src="' + escapeAttribute(src) + '" alt="' + escapeAttribute(alt) + '">';
                    }
                    insertHtmlWithFallback(editor, textarea, html);
                }
                textarea.value = typeof editor.getContents === 'function'
                    ? String(editor.getContents() || '')
                    : String(textarea.value || '');
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
            }
            closeMediaModal(modal);
        }

        window.initMediaModal(Object.assign({}, baseConfig, {
            mode: mode,
            folder: folder,
            mediaContext: mediaContext,
            openUploadIfEmpty: true,
            initialTab: initialTab,
            onSelect: function(file) {
                contextualizeMedia(file, baseConfig, folder, mediaContext)
                    .then(applySelectedMedia)
                    .catch(function() {
                        showToast(baseConfig.uploadFailedLabel || mediaModalError, 'error');
                    });
            },
        }));

        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function replaceMediaElement(element, file, src, alt) {
        var tag = String(element.tagName || '').toLowerCase();
        var name = String((file && (file.original_name || file.name)) || alt || '').trim();
        var size = String((file && file.size) || '').trim();
        var mime = String((file && file.mime) || '').trim();

        if (tag === 'img') {
            element.setAttribute('src', src);
            element.setAttribute('alt', alt);
            element.removeAttribute('srcset');
            element.removeAttribute('sizes');
        }

        if (tag === 'video') {
            var source = element.querySelector('source');
            if (!source) {
                source = document.createElement('source');
                element.appendChild(source);
            }
            source.setAttribute('src', src);
            if (mime !== '') {
                source.setAttribute('type', mime);
            } else {
                source.removeAttribute('type');
            }
            element.load();
        }

        if (name !== '') {
            element.setAttribute('data-file-name', name);
        }
        if (size !== '') {
            element.setAttribute('data-file-size', size);
        }
    }

    function getActiveMediaElement(editor, command) {
        if (!editor || !editor.core || !editor.core.context) {
            return null;
        }

        var context = editor.core.context;
        var pluginContext = context[command] || {};
        var candidate = pluginContext._element || null;
        var expectedTag = command === 'video' ? 'video' : 'img';

        if (candidate instanceof HTMLElement && String(candidate.tagName || '').toLowerCase() === expectedTag) {
            return candidate;
        }

        if (context.resizing && context.resizing._resize_plugin === command) {
            candidate = pluginContext._element || context.resizing._element || null;
            if (candidate instanceof HTMLElement && String(candidate.tagName || '').toLowerCase() === expectedTag) {
                return candidate;
            }
        }

        return null;
    }

    function isToolbarMediaEvent(event, toolbar, command, selector) {
        if (!event || !event.target || typeof event.target.closest !== 'function') {
            return false;
        }

        var target = event.target;
        if (selector !== '' && target.closest(selector)) {
            return true;
        }

        var commandButton = target.closest('[data-command="' + command + '"]');
        if (commandButton && toolbar.contains(commandButton)) {
            return true;
        }

        var moduleButton = target.closest('.se-btn-module-' + command);
        return !!(moduleButton && toolbar.contains(moduleButton));
    }

    function getMediaContext(textarea, rootFolder) {
        var explicit = String(textarea && textarea.getAttribute ? textarea.getAttribute('data-suneditor-media-context') : '').trim();
        if (explicit !== '') {
            return explicit;
        }

        return rootFolder + '/draft';
    }

    function bindNativeMediaUpdate(editor, textarea, mediaModalError, config) {
        if (!editor || !editor.core || !editor.core.context || !editor.core.context.element) {
            return;
        }

        var command = String((config && config.command) || '').trim();
        if (command === '') {
            return;
        }

        editor.__flatcmsMediaUpdateBound = editor.__flatcmsMediaUpdateBound || {};
        if (editor.__flatcmsMediaUpdateBound[command]) {
            return;
        }
        editor.__flatcmsMediaUpdateBound[command] = true;

        function shouldHandle(event) {
            if (!event || !event.target || typeof event.target.closest !== 'function') {
                return false;
            }
            var commandButton = event.target.closest('.se-controller-resizing [data-command="update"], .se-controller [data-command="update"]');
            if (!commandButton) {
                return false;
            }
            var context = editor.core && editor.core.context ? editor.core.context : {};
            return !!(context.resizing && context.resizing._resize_plugin === command);
        }

        document.addEventListener('mousedown', function(event) {
            if (!shouldHandle(event)) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }, true);

        document.addEventListener('click', function(event) {
            if (!shouldHandle(event)) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            var activeElement = getActiveMediaElement(editor, command);
            openMediaModalForEditor(editor, textarea, mediaModalError, Object.assign({}, config, {
                replaceElement: activeElement,
            }));
        }, true);
    }

    function bindNativeMediaButton(editor, textarea, mediaModalError, config, attempt) {
        if (!editor || !editor.core || !editor.core.context || !editor.core.context.element) {
            return;
        }

        var toolbar = editor.core.context.element.toolbar;
        if (!toolbar) {
            var nextAttempt = Number(attempt || 0) + 1;
            if (nextAttempt <= 8) {
                window.setTimeout(function() {
                    bindNativeMediaButton(editor, textarea, mediaModalError, config, nextAttempt);
                }, 60);
            }
            return;
        }

        var command = String((config && config.command) || '').trim();
        if (command === '') {
            return;
        }

        var selector = String((config && config.selector) || '').trim();
        var bindFlag = 'data-flatcms-media-bound-' + command;
        if (toolbar.getAttribute(bindFlag) === '1') {
            return;
        }
        toolbar.setAttribute(bindFlag, '1');
        bindNativeMediaUpdate(editor, textarea, mediaModalError, config);

        toolbar.addEventListener('mousedown', function(event) {
            if (!isToolbarMediaEvent(event, toolbar, command, selector)) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }, true);

        toolbar.addEventListener('click', function(event) {
            if (!isToolbarMediaEvent(event, toolbar, command, selector)) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            openMediaModalForEditor(editor, textarea, mediaModalError, config);
        }, true);
    }

    function prepareTextareaForSunEditor() {
        var textarea = getTextarea();
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return;
        }
        if (getActiveProvider() === 'tinymce') {
            return;
        }
        if (!textarea.hasAttribute('data-no-editor')) {
            textarea.setAttribute('data-no-editor', '');
        }
    }

    function initPostsSunEditor() {
        if (getActiveProvider() === 'tinymce') {
            return;
        }

        var textarea = getTextarea();
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return;
        }

        var sun = window.FlatCMSSunEditor;
        if (!sun || typeof sun.create !== 'function') {
            return;
        }

        if (textarea.__postSunEditorHandle && typeof textarea.__postSunEditorHandle.destroy === 'function') {
            textarea.__postSunEditorHandle.destroy();
        }

        textarea.__postSunEditorHandle = sun.create(textarea, {
            minHeight: '360px',
            height: 420,
            charCounter: true,
            applyAccordion: true,
            expandLabel: String(textarea.getAttribute('data-suneditor-toolbar-expand') || ''),
            collapseLabel: String(textarea.getAttribute('data-suneditor-toolbar-collapse') || ''),
            onInput: function(nextHtml) {
                textarea.value = String(nextHtml || '');
            },
            onChange: function(nextHtml) {
                textarea.value = String(nextHtml || '');
            },
        });

        if (!textarea.__postSunEditorHandle || !textarea.__postSunEditorHandle.editor) {
            return;
        }

        var mediaModalError = String(textarea.getAttribute('data-suneditor-media-modal-error') || '').trim();
        var editor = textarea.__postSunEditorHandle.editor;

        bindNativeMediaButton(editor, textarea, mediaModalError, {
            command: 'image',
            selector: '.se-btn-module-image .se-btn',
            mode: 'images',
            folder: 'images',
            mediaContext: getMediaContext(textarea, 'posts'),
            initialTab: 'library',
            buildHtml: function(file, src) {
                var alt = String((file && (file.original_name || file.name)) || '').trim();
                return '<img src="' + escapeAttribute(src) + '" alt="' + escapeAttribute(alt) + '">';
            },
        });

        bindNativeMediaButton(editor, textarea, mediaModalError, {
            command: 'video',
            selector: '.se-btn-module-video .se-btn',
            mode: 'files',
            folder: 'videos',
            mediaContext: getMediaContext(textarea, 'posts'),
            initialTab: 'library',
            buildHtml: function(file, src) {
                var mime = String((file && file.mime) || '').trim();
                var sourceTag = '<source src="' + escapeAttribute(src) + '"';
                if (mime !== '') {
                    sourceTag += ' type="' + escapeAttribute(mime) + '"';
                }
                sourceTag += '>';
                return '<video controls preload="metadata">' + sourceTag + '</video>';
            },
        });

        var form = textarea.closest('form');
        if (form && !form.__postSunEditorSubmitBound) {
            form.__postSunEditorSubmitBound = true;
            form.addEventListener('submit', function() {
                if (textarea.__postSunEditorHandle && typeof textarea.__postSunEditorHandle.getHtml === 'function') {
                    textarea.value = String(textarea.__postSunEditorHandle.getHtml() || '');
                }
            });
        }
    }

    prepareTextareaForSunEditor();
    document.addEventListener('DOMContentLoaded', function() {
        initPostsSunEditor();
    });
})();
