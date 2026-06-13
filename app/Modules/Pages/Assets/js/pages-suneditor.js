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

    function getTranslationRoot() {
        return document.querySelector('[data-pages-translations-root]');
    }

    function getTextarea(root) {
        var scope = root || document;
        var activePanelTextarea = scope.querySelector('.pages-translation-panel.is-active textarea[data-page-suneditor]');
        if (activePanelTextarea instanceof HTMLTextAreaElement) {
            return activePanelTextarea;
        }

        var defaultTextarea = scope.querySelector('textarea[data-page-suneditor]');
        return defaultTextarea instanceof HTMLTextAreaElement ? defaultTextarea : null;
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
        var initialTab = String((options && options.initialTab) || 'library') === 'upload' ? 'upload' : 'library';
        var htmlBuilder = (options && typeof options.buildHtml === 'function') ? options.buildHtml : null;

        window.initMediaModal(Object.assign({}, baseConfig, {
            mode: mode,
            folder: folder,
            openUploadIfEmpty: true,
            initialTab: initialTab,
            onSelect: function(file) {
                var src = resolveMediaSource(file, uploadsBase);
                if (src !== '') {
                    var html = '';
                    if (htmlBuilder) {
                        html = String(htmlBuilder(file, src) || '');
                    }
                    if (html === '') {
                        var alt = String((file && (file.original_name || file.name)) || '').trim();
                        html = '<img src="' + escapeAttribute(src) + '" alt="' + escapeAttribute(alt) + '">';
                    }
                    insertHtmlWithFallback(editor, textarea, html);
                    textarea.value = typeof editor.getContents === 'function'
                        ? String(editor.getContents() || '')
                        : String(textarea.value || '');
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                }
                closeMediaModal(modal);
            },
        }));

        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function bindNativeMediaButton(editor, textarea, mediaModalError, config, attempt) {
        if (!editor || !editor.core || !editor.core.context || !editor.core.context.element) {
            return;
        }

        var toolbar = editor.core.context.element.toolbar;
        if (!toolbar) {
            return;
        }

        var command = String((config && config.command) || '').trim();
        if (command === '') {
            return;
        }

        var selector = String((config && config.selector) || '').trim();
        var targetButton = null;
        if (selector !== '') {
            targetButton = toolbar.querySelector(selector);
        }
        if (!targetButton) {
            targetButton = toolbar.querySelector('button[data-command="' + command + '"]');
        }

        if (!targetButton) {
            var nextAttempt = Number(attempt || 0) + 1;
            if (nextAttempt <= 8) {
                window.setTimeout(function() {
                    bindNativeMediaButton(editor, textarea, mediaModalError, config, nextAttempt);
                }, 60);
            }
            return;
        }

        var bindFlag = 'data-flatcms-media-bound-' + command;
        if (targetButton.getAttribute(bindFlag) === '1') {
            return;
        }
        targetButton.setAttribute(bindFlag, '1');

        targetButton.addEventListener('mousedown', function(event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }, true);

        targetButton.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            openMediaModalForEditor(editor, textarea, mediaModalError, config);
        }, true);
    }

    function sync(textarea) {
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return;
        }
        if (!textarea.__pageSunEditorHandle || typeof textarea.__pageSunEditorHandle.getHtml !== 'function') {
            return;
        }
        textarea.value = String(textarea.__pageSunEditorHandle.getHtml() || '');
    }

    function destroy(textarea) {
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return;
        }
        if (!textarea.__pageSunEditorHandle || typeof textarea.__pageSunEditorHandle.destroy !== 'function') {
            return;
        }

        sync(textarea);
        textarea.__pageSunEditorHandle.destroy();
        textarea.__pageSunEditorHandle = null;

        var root = getTranslationRoot();
        if (root && root.__pageActiveEditorTextarea === textarea) {
            root.__pageActiveEditorTextarea = null;
        }
    }

    function prepare() {
        if (getActiveProvider() === 'tinymce') {
            return;
        }

        Array.prototype.slice.call(document.querySelectorAll('textarea[data-page-suneditor]')).forEach(function(textarea) {
            if (!(textarea instanceof HTMLTextAreaElement)) {
                return;
            }
            if (!textarea.hasAttribute('data-no-editor')) {
                textarea.setAttribute('data-no-editor', '');
            }
        });
    }

    function init(targetTextarea) {
        if (getActiveProvider() === 'tinymce') {
            return;
        }

        var root = getTranslationRoot();
        var textarea = targetTextarea instanceof HTMLTextAreaElement ? targetTextarea : getTextarea(root || document);
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return;
        }

        if (root && root.__pageActiveEditorTextarea && root.__pageActiveEditorTextarea !== textarea) {
            destroy(root.__pageActiveEditorTextarea);
        }

        var sun = window.FlatCMSSunEditor;
        if (!sun || typeof sun.create !== 'function') {
            return;
        }

        if (textarea.__pageSunEditorHandle && typeof textarea.__pageSunEditorHandle.destroy === 'function') {
            destroy(textarea);
        }

        textarea.__pageSunEditorHandle = sun.create(textarea, {
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

        if (!textarea.__pageSunEditorHandle || !textarea.__pageSunEditorHandle.editor) {
            return;
        }

        if (root) {
            root.__pageActiveEditorTextarea = textarea;
        }

        var mediaModalError = String(textarea.getAttribute('data-suneditor-media-modal-error') || '').trim();
        var editor = textarea.__pageSunEditorHandle.editor;

        bindNativeMediaButton(editor, textarea, mediaModalError, {
            command: 'image',
            selector: '.se-btn-module-image .se-btn',
            mode: 'images',
            folder: 'images',
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
        if (form && !form.__pageSunEditorSubmitBound) {
            form.__pageSunEditorSubmitBound = true;
            form.addEventListener('submit', function() {
                var translationRoot = getTranslationRoot();
                var activeTextarea = translationRoot && translationRoot.__pageActiveEditorTextarea
                    ? translationRoot.__pageActiveEditorTextarea
                    : getTextarea(form);
                if (activeTextarea instanceof HTMLTextAreaElement) {
                    sync(activeTextarea);
                }
            });
        }
    }

    window.FlatCMSPagesSunEditor = {
        prepare: prepare,
        init: init,
        destroy: destroy,
        sync: sync,
    };

    prepare();
    document.addEventListener('DOMContentLoaded', function() {
        init();
    });
})();
