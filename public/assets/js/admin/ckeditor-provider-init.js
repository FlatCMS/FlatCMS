/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

/**
 * CKEditor 5 (GPL) provider.
 *
 * Uses GeneralHtmlSupport (GHS) so theme-authored sections, classes and
 * attributes remain editable. The FlatCMS media buttons insert standard HTML,
 * while the contextual image-size control updates only an image width style.
 * Server-side reconciliation reapplies those real edits to the human-authored
 * source without replacing its indentation or unrelated markup.
 *
 * All textareas marked with [data-ckeditor] (and, as a global switch, any
 * textarea.form-input not marked with [data-no-editor]) are initialized once
 * they become visible. The editor is kept in sync with its source textarea so
 * form submissions, the AiAgent apply flow and the Pages translation tabs all
 * work without knowing the provider internals.
 *
 * Public API (window.FlatCMSCKEditor):
 *   create(textarea, options)  -> synchronous handle { editor, getHtml, ... }
 *   getEditor(textarea)        -> handle or null
 *   destroy(textarea)          -> destroy a single editor
 *   destroyAll()               -> destroy every managed editor
 *   sync(textarea)             -> push editor data back to the textarea
 *   syncAll()                  -> push every managed editor back to its textarea
 *   bootstrap()                -> (re)scan and init visible textareas (idempotent)
 */
(function () {
    'use strict';

    var initialized = new Map();
    var submitBoundForms = new WeakSet();
    var bootstrapBound = false;
    var providerDisabled = false;
    var supportedEditorLocales = ['de', 'en', 'es', 'fr', 'it', 'pt'];

    function normalizeEditorLocale(value) {
        var raw = String(value || '')
            .trim()
            .toLowerCase()
            .replace(/_/g, '-');

        for (var index = 0; index < supportedEditorLocales.length; index++) {
            var locale = supportedEditorLocales[index];
            if (raw === locale || raw.indexOf(locale + '-') === 0) {
                return locale;
            }
        }

        return 'en';
    }

    function textareaEditorLocale(textarea, options) {
        var opts = options || {};
        var optionLocale = typeof opts.locale === 'string'
            ? opts.locale
            : (typeof opts.language === 'string' ? opts.language : '');
        var explicit = String(optionLocale || '').trim();

        if (explicit === '' && textarea && typeof textarea.getAttribute === 'function') {
            explicit = String(textarea.getAttribute('data-ckeditor-locale') || '').trim();
        }

        if (explicit === '' && textarea && typeof textarea.closest === 'function') {
            var panel = textarea.closest('[data-pages-panel]');
            if (panel) {
                explicit = String(panel.getAttribute('data-pages-panel') || '').trim();
            }
        }

        if (explicit === '') {
            explicit = String(document.documentElement.getAttribute('lang') || '').trim();
        }

        return normalizeEditorLocale(explicit);
    }

    function translatedEditorLabel(locale, key, fallback) {
        var translations = window.CKEDITOR_TRANSLATIONS || {};
        var entry = translations[normalizeEditorLocale(locale)] || null;
        var dictionary = entry && entry.dictionary ? entry.dictionary : null;
        var translated = dictionary ? dictionary[key] : null;

        if (typeof translated === 'string' && translated.trim() !== '') {
            return translated;
        }

        return String(fallback || key || '');
    }

    /* --------------------------------------------------------------------- *
     * Small DOM / media helpers
     * --------------------------------------------------------------------- */

    function escapeAttribute(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function normalizeMediaContext(value) {
        return String(value || '')
            .replace(/\\/g, '/')
            .trim()
            .split('/')
            .map(function (part) {
                return part.replace(/[^a-z0-9_-]+/gi, '-').replace(/^-+|-+$/g, '').toLowerCase();
            })
            .filter(Boolean)
            .join('/')
            .slice(0, 160);
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

    function shouldContextualizeMedia(file, folder, mediaContext) {
        var context = normalizeMediaContext(mediaContext);
        var path = String((file && file.path) || '').replace(/\\/g, '/').replace(/^\/+/, '').trim();
        var targetPrefix = String(folder || 'images').trim() + '/' + context + '/';

        return context !== '' && path !== '' && !path.startsWith(targetPrefix);
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
        }).then(function (response) {
            return response.text().then(function (text) {
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

    function buildMediaReferenceCandidates(value) {
        var raw = String(value || '').trim();
        var candidates = {};

        function add(candidate) {
            var normalized = String(candidate || '').trim();
            if (normalized !== '') {
                candidates[normalized] = true;
            }
        }

        add(raw);
        if (raw !== '') {
            try {
                var parsed = new URL(raw, window.location.origin);
                add(parsed.href);
                add(parsed.pathname);
            } catch (error) {
                // Ignore malformed relative values.
            }

            var uploadIndex = raw.indexOf('/uploads/');
            if (uploadIndex >= 0) {
                add(raw.slice(uploadIndex));
            }
        }

        return Object.keys(candidates);
    }

    function escapeRegExp(value) {
        return String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function replaceMediaReferences(html, previousSrc, nextSrc) {
        var output = String(html || '');
        var next = String(nextSrc || '').trim();
        if (output === '' || next === '') {
            return output;
        }

        buildMediaReferenceCandidates(previousSrc).forEach(function (candidate) {
            if (candidate === '' || candidate === next) {
                return;
            }
            output = output.replace(new RegExp(escapeRegExp(candidate), 'g'), next);
        });

        return output;
    }

    function propagateMediaReplacementToTranslations(activeTextarea, previousSrc, nextSrc) {
        if (String(previousSrc || '').trim() === '' || String(nextSrc || '').trim() === '') {
            return;
        }

        getTargets().forEach(function (candidate) {
            if (!(candidate instanceof HTMLTextAreaElement) || candidate === activeTextarea) {
                return;
            }

            var previousHtml = String(candidate.value || '');
            var nextHtml = replaceMediaReferences(previousHtml, previousSrc, nextSrc);
            if (nextHtml === previousHtml) {
                return;
            }

            candidate.value = nextHtml;
            var handle = initialized.get(candidate);
            if (handle && typeof handle.setHtml === 'function') {
                handle.setHtml(nextHtml);
            }
            candidate.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    /* --------------------------------------------------------------------- *
     * Target discovery / visibility
     * --------------------------------------------------------------------- */

    function getTargets() {
        var byAttribute = Array.prototype.slice.call(document.querySelectorAll('textarea[data-ckeditor]'));
        var seen = new Set(byAttribute);

        Array.prototype.slice.call(document.querySelectorAll('textarea.form-input:not([data-no-editor])')).forEach(function (textarea) {
            if (!seen.has(textarea)) {
                seen.add(textarea);
                byAttribute.push(textarea);
            }
        });

        return byAttribute;
    }

    function isEffectivelyVisible(textarea) {
        if (textarea.offsetParent !== null || (textarea.getClientRects && textarea.getClientRects().length > 0)) {
            return true;
        }
        var panel = textarea.closest ? textarea.closest('.pages-translation-panel') : null;
        return !panel || panel.classList.contains('is-active');
    }

    function textareaMediaContext(textarea) {
        return String(textarea.getAttribute && textarea.getAttribute('data-media-context') || '').trim();
    }

    function textareaMediaErrorLabel(textarea) {
        var explicit = textarea.getAttribute && (textarea.getAttribute('data-media-modal-error') || textarea.getAttribute('data-suneditor-media-modal-error'));
        return String(explicit || '').trim();
    }

    /* --------------------------------------------------------------------- *
     * Media insertion (CKEditor model API - document.execCommand is ignored)
     * --------------------------------------------------------------------- */

    function insertHtmlViaModel(editor, html) {
        var viewFragment = editor.data.processor.toView(html);
        var modelFragment = editor.data.toModel(viewFragment);
        editor.model.change(function (writer) {
            editor.model.insertContent(modelFragment);
        });
    }


    function openMediaModalForEditor(editor, textarea, options) {
        var modal = document.getElementById('mediaModal');
        if (!modal || typeof window.initMediaModal !== 'function') {
            showToast(textareaMediaErrorLabel(textarea) || translatedEditorLabel(textareaEditorLocale(textarea), 'Media unavailable', 'Media unavailable'), 'warning');
            return;
        }

        var baseConfig = {};
        var rawConfig = String(modal.getAttribute('data-media-config') || '').trim();
        if (rawConfig !== '') {
            try {
                baseConfig = JSON.parse(rawConfig);
            } catch (error) {
                baseConfig = {};
            }
        }

        var uploadsBase = String(baseConfig.uploadsBase || '/uploads');
        var mode = String((options && options.mode) || 'images').toLowerCase() === 'files' ? 'files' : 'images';
        var folder = String((options && options.folder) || (mode === 'files' ? 'videos' : 'images')).trim();
        var mediaContext = textareaMediaContext(textarea);
        var buildHtml = (options && typeof options.buildHtml === 'function') ? options.buildHtml : null;

        function applySelectedMedia(file) {
            var src = resolveMediaSource(file, uploadsBase);
            if (src !== '') {
                var alt = String((file && (file.original_name || file.name)) || '').trim();
                var previousSrc = '';
                var html = buildHtml
                    ? String(buildHtml(file, src) || '')
                    : '<img src="' + escapeAttribute(src) + '" alt="' + escapeAttribute(alt) + '">';

                try {
                    editor.focus();
                    insertHtmlViaModel(editor, html);
                } catch (error) {
                    textarea.value = String(textarea.value || '') + html;
                }

                syncToTextarea(editor, textarea);
                propagateMediaReplacementToTranslations(textarea, previousSrc, src);
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
            }
            closeMediaModal(modal);
        }

        window.initMediaModal(Object.assign({}, baseConfig, {
            mode: mode,
            folder: folder,
            mediaContext: mediaContext,
            openUploadIfEmpty: true,
            initialTab: 'library',
            onSelect: function (file) {
                contextualizeMedia(file, baseConfig, folder, mediaContext)
                    .then(applySelectedMedia)
                    .catch(function () {
                        showToast(String(baseConfig.uploadFailedLabel || textareaMediaErrorLabel(textarea) || ''), 'error');
                    });
            },
        }));

        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    var ICON_IMAGE = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="currentColor" d="M2 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V4zm2 0v9.6l3.4-3.4a1 1 0 0 1 1.4 0L13 14.4l2.6-2.6a1 1 0 0 1 1.4 0L18 13V4H4zm3 2a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/></svg>';

    var ICON_VIDEO = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="currentColor" d="M2 5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1.2l3.2-2a.5.5 0 0 1 .8.4v8.8a.5.5 0 0 1-.8.4L14 12.8V15a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5z"/></svg>';

    function mediaButtonPlugin(definition, textareaRef) {
        return class FlatCMSMediaButtonPlugin extends window.CKEDITOR.Plugin {
            static get pluginName() {
                return definition.pluginName;
            }
            init() {
                var editor = this.editor;
                var textarea = typeof textareaRef === 'function' ? textareaRef() : textareaRef;
                editor.ui.componentFactory.add(definition.name, function (locale) {
                    var ButtonView = window.CKEDITOR.ButtonView;
                    var button = new ButtonView(locale);
                    var editorLocale = textareaEditorLocale(textarea);
                    button.set({
                        label: translatedEditorLabel(editorLocale, definition.labelKey, definition.labelKey),
                        icon: definition.icon,
                        tooltip: true,
                    });
                    button.on('execute', function () {
                        openMediaModalForEditor(editor, textarea, definition);
                    });
                    return button;
                });
            }
        };
    }

    /* --------------------------------------------------------------------- *
     * Editor configuration
     * --------------------------------------------------------------------- */

    var MEDIA_DEFINITIONS = [
        {
            pluginName: 'FlatCMSMediaImage',
            name: 'flatcmsImage',
            labelKey: 'Insert image',
            icon: ICON_IMAGE,
            mode: 'images',
            folder: 'images',
            buildHtml: function (file, src) {
                var alt = String((file && (file.original_name || file.name)) || '').trim();
                return '<img src="' + escapeAttribute(src) + '" alt="' + escapeAttribute(alt) + '">';
            },
        },
        {
            pluginName: 'FlatCMSMediaVideo',
            name: 'flatcmsVideo',
            labelKey: 'Insert video',
            icon: ICON_VIDEO,
            mode: 'files',
            folder: 'videos',
            buildHtml: function (file, src) {
                var mime = String((file && file.mime) || '').trim();
                var sourceTag = '<source src="' + escapeAttribute(src) + '"';
                if (mime !== '') {
                    sourceTag += ' type="' + escapeAttribute(mime) + '"';
                }
                sourceTag += '>';
                return '<video controls preload="metadata">' + sourceTag + '</video>';
            },
        },
    ];

    function buildConfig(textarea, options) {
        var CK = window.CKEDITOR;
        var opts = options || {};
        var mediaEnabled = opts.media !== false;
        var editorLocale = textareaEditorLocale(textarea, opts);

        var plugins = [
            CK.Essentials,
            CK.Paragraph,
            CK.Typing,
            CK.SelectAll,
            CK.Undo,
            CK.Bold,
            CK.Italic,
            CK.Underline,
            CK.Strikethrough,
            CK.Heading,
            CK.Font,
            CK.Highlight,
            CK.Alignment,
            CK.List,
            CK.ListProperties,
            CK.Indent,
            CK.IndentBlock,
            CK.BlockQuote,
            CK.Link,
            CK.Table,
            CK.HorizontalLine,
            CK.RemoveFormat,
            CK.SourceEditing,
            CK.GeneralHtmlSupport,
            CK.Autoformat,
            CK.PasteFromOffice,
        ];

        if (mediaEnabled) {
            MEDIA_DEFINITIONS.forEach(function (definition) {
                plugins.push(mediaButtonPlugin(definition, textarea));
            });
        }

        var toolbar = opts.toolbar && opts.toolbar.length ? opts.toolbar.slice() : [
            'undo', 'redo', '|',
            'sourceEditing', '|',
            'heading', '|',
            'fontFamily', 'fontSize', '|',
            'fontColor', 'fontBackgroundColor', 'highlight', '|',
            'bold', 'italic', 'underline', 'strikethrough', '|',
            'alignment', '|',
            'bulletedList', 'numberedList', '|',
            'blockQuote', 'link', 'insertTable', '|',
            'indent', 'outdent', '|',
            'horizontalLine', 'removeFormat', '|',
            'flatcmsImage', 'flatcmsVideo',
        ];

        var config = {
            licenseKey: 'GPL',
            initialData: String(textarea.value || ''),
            language: {
                ui: editorLocale,
                content: editorLocale,
            },
            plugins: plugins,
            toolbar: {
                items: toolbar,
                shouldNotGroupWhenFull: true,
            },
            heading: {
                options: [
                    { model: 'paragraph', title: translatedEditorLabel(editorLocale, 'Paragraph', 'Paragraph'), class: 'ck-heading_paragraph' },
                    { model: 'heading1', view: 'h1', title: translatedEditorLabel(editorLocale, 'Heading 1', 'Heading 1'), class: 'ck-heading_heading1' },
                    { model: 'heading2', view: 'h2', title: translatedEditorLabel(editorLocale, 'Heading 2', 'Heading 2'), class: 'ck-heading_heading2' },
                    { model: 'heading3', view: 'h3', title: translatedEditorLabel(editorLocale, 'Heading 3', 'Heading 3'), class: 'ck-heading_heading3' },
                    { model: 'heading4', view: 'h4', title: translatedEditorLabel(editorLocale, 'Heading 4', 'Heading 4'), class: 'ck-heading_heading4' },
                ],
            },
            fontFamily: {
                options: [
                    'default',
                    'Arial, Helvetica, sans-serif',
                    'Arial Black, Gadget, sans-serif',
                    'Courier New, Courier, monospace',
                    'Georgia, serif',
                    'Impact, Charcoal, sans-serif',
                    'Tahoma, Geneva, sans-serif',
                    'Times New Roman, Times, serif',
                    'Trebuchet MS, Helvetica, sans-serif',
                    'Verdana, Geneva, sans-serif',
                ],
            },
            fontSize: {
                options: ['default', 12, 14, 16, 18, 20, 24, 28, 32, 36, 48],
            },
            htmlSupport: {
                allow: [
                    { name: /.*/, attributes: true, classes: true, styles: true },
                ],
                allowEmpty: ['section', 'article', 'div', 'p', 'span', 'i', 'figure', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'ul', 'ol', 'table', 'tbody', 'thead', 'tr', 'td', 'th', 'video', 'source'],
            },
        };

        if (opts.initialData !== undefined) {
            config.initialData = String(opts.initialData);
        }

        return config;
    }

    function initImageSizeControls(editor, textarea) {
        var editable = editor.ui && typeof editor.ui.getEditableElement === 'function'
            ? editor.ui.getEditableElement()
            : null;
        var host = editor.ui && editor.ui.view ? editor.ui.view.element : null;
        if (!(editable instanceof HTMLElement) || !(host instanceof HTMLElement)) {
            return;
        }

        var locale = textareaEditorLocale(textarea);
        var panel = document.createElement('div');
        panel.className = 'flatcms-ckeditor-image-size';
        panel.hidden = true;
        panel.setAttribute('role', 'toolbar');
        panel.setAttribute('aria-label', translatedEditorLabel(locale, 'Image size', 'Image size'));

        var title = document.createElement('span');
        title.className = 'flatcms-ckeditor-image-size__title';
        title.textContent = translatedEditorLabel(locale, 'Image size', 'Image size');
        panel.appendChild(title);

        var presets = [
            { value: null, label: translatedEditorLabel(locale, 'Original size', 'Original size') },
            { value: '25%', label: '25%' },
            { value: '50%', label: '50%' },
            { value: '75%', label: '75%' },
            { value: '100%', label: '100%' },
        ];
        var presetButtons = [];
        var activeImage = null;
        var activeWidget = null;

        function imageWidth(modelElement) {
            if (!modelElement || typeof modelElement.getAttribute !== 'function') {
                return null;
            }
            var attributes = modelElement.getAttribute('htmlImgAttributes') || {};
            var styles = attributes.styles || {};
            return typeof styles.width === 'string' && styles.width.trim() !== ''
                ? styles.width.trim()
                : null;
        }

        function cloneImageAttributes(modelElement) {
            var current = modelElement.getAttribute('htmlImgAttributes') || {};
            var next = Object.assign({}, current);
            if (current.attributes) {
                next.attributes = Object.assign({}, current.attributes);
            }
            if (current.styles) {
                next.styles = Object.assign({}, current.styles);
            }
            if (Array.isArray(current.classes)) {
                next.classes = current.classes.slice();
            }
            return next;
        }

        function updatePanelState() {
            var width = imageWidth(activeImage);
            presetButtons.forEach(function (button) {
                var selected = button.__flatcmsWidth === width;
                button.classList.toggle('is-active', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
            if (width && /%$/.test(width)) {
                customInput.value = width.slice(0, -1);
            } else {
                customInput.value = '';
            }
        }

        function applyWidth(width) {
            if (!activeImage || activeImage.name !== 'htmlImg') {
                return;
            }
            var next = cloneImageAttributes(activeImage);
            var styles = Object.assign({}, next.styles || {});
            if (width === null) {
                delete styles.width;
            } else {
                styles.width = width;
            }
            if (Object.keys(styles).length > 0) {
                next.styles = styles;
            } else {
                delete next.styles;
            }

            editor.model.change(function (writer) {
                writer.setAttribute('htmlImgAttributes', next, activeImage);
            });
            updatePanelState();
            if (editor.editing && editor.editing.view) {
                editor.editing.view.focus();
            }
        }

        presets.forEach(function (preset) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'flatcms-ckeditor-image-size__button';
            button.textContent = preset.label;
            button.__flatcmsWidth = preset.value;
            button.setAttribute('aria-pressed', 'false');
            button.addEventListener('click', function () {
                applyWidth(preset.value);
            });
            presetButtons.push(button);
            panel.appendChild(button);
        });

        var custom = document.createElement('span');
        custom.className = 'flatcms-ckeditor-image-size__custom';
        var customInput = document.createElement('input');
        customInput.type = 'number';
        customInput.min = '1';
        customInput.max = '100';
        customInput.step = '1';
        customInput.inputMode = 'decimal';
        customInput.setAttribute('aria-label', translatedEditorLabel(locale, 'Custom size', 'Custom size'));
        customInput.setAttribute('title', translatedEditorLabel(locale, 'Custom size', 'Custom size'));
        var percent = document.createElement('span');
        percent.textContent = '%';
        var applyButton = document.createElement('button');
        applyButton.type = 'button';
        applyButton.className = 'flatcms-ckeditor-image-size__apply';
        applyButton.textContent = translatedEditorLabel(locale, 'Apply', 'Apply');

        function applyCustomWidth() {
            var value = Number(customInput.value);
            if (!Number.isFinite(value) || value <= 0 || value > 100) {
                customInput.focus();
                return;
            }
            var normalized = String(Math.round(value * 100) / 100).replace(/\.0+$/, '');
            applyWidth(normalized + '%');
        }

        customInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyCustomWidth();
            }
        });
        applyButton.addEventListener('click', applyCustomWidth);
        custom.appendChild(customInput);
        custom.appendChild(percent);
        custom.appendChild(applyButton);
        panel.appendChild(custom);
        host.classList.add('flatcms-ckeditor-image-size-host');
        host.appendChild(panel);

        function hidePanel() {
            panel.hidden = true;
            activeImage = null;
            activeWidget = null;
        }

        function positionPanel() {
            if (panel.hidden || !(activeWidget instanceof HTMLElement)) {
                return;
            }
            var hostRect = host.getBoundingClientRect();
            var widgetRect = activeWidget.getBoundingClientRect();
            var panelRect = panel.getBoundingClientRect();
            var left = widgetRect.left - hostRect.left;
            var maximumLeft = Math.max(8, hostRect.width - panelRect.width - 8);
            left = Math.max(8, Math.min(left, maximumLeft));
            panel.style.left = left + 'px';
            panel.style.top = Math.max(8, widgetRect.bottom - hostRect.top + 6) + 'px';
        }

        function showPanel(widget, modelElement) {
            activeWidget = widget;
            activeImage = modelElement;
            panel.hidden = false;
            updatePanelState();
            window.requestAnimationFrame(positionPanel);
        }

        function resolveImageModel(widget) {
            var viewElement = editor.editing.view.domConverter.domToView(widget);
            if (!viewElement) {
                return null;
            }
            var modelElement = editor.editing.mapper.toModelElement(viewElement);
            return modelElement && modelElement.name === 'htmlImg' ? modelElement : null;
        }

        function handleEditableClick(event) {
            var target = event.target instanceof Element ? event.target : null;
            var widget = target ? target.closest('.html-object-embed.ck-widget') : null;
            if (!widget || !editable.contains(widget)) {
                hidePanel();
                return;
            }
            var modelElement = resolveImageModel(widget);
            if (!modelElement) {
                hidePanel();
                return;
            }
            showPanel(widget, modelElement);
        }

        function handleDocumentPointer(event) {
            var target = event.target instanceof Node ? event.target : null;
            if (target && (panel.contains(target) || editable.contains(target))) {
                return;
            }
            hidePanel();
        }

        function cleanup() {
            editable.removeEventListener('click', handleEditableClick);
            document.removeEventListener('mousedown', handleDocumentPointer, true);
            window.removeEventListener('resize', positionPanel);
            panel.remove();
        }

        panel.addEventListener('mousedown', function (event) {
            event.stopPropagation();
        });
        editable.addEventListener('click', handleEditableClick);
        document.addEventListener('mousedown', handleDocumentPointer, true);
        window.addEventListener('resize', positionPanel);
        if (typeof editor.on === 'function') {
            editor.on('destroy', cleanup);
        }
    }

    /* --------------------------------------------------------------------- *
     * Sync / lifecycle
     * --------------------------------------------------------------------- */

    function cleanOutputHtml(html) {
        var output = String(html || '');
        output = output.replace(/ data-list-item-id="[^"]*"/g, '');
        output = output.replace(/ data-cke-[a-z0-9-]*="[^"]*"/g, '');
        return output;
    }

    function baselineFieldName(textareaName) {
        var name = String(textareaName || '').trim();
        if (name === '') {
            return '';
        }
        if (/\[content\]$/.test(name)) {
            return name.replace(/\[content\]$/, '[content__editor_baseline]');
        }
        return name + '__editor_baseline';
    }

    function ensureBaselineInput(textarea, baselineHtml) {
        var form = textarea.closest('form');
        var name = baselineFieldName(textarea.name);
        if (!form || name === '') {
            return;
        }

        var key = String(textarea.id || textarea.name || 'content');
        var input = Array.prototype.find.call(
            form.querySelectorAll('input[type="hidden"][data-ckeditor-baseline]'),
            function (candidate) {
                return candidate.getAttribute('data-ckeditor-baseline') === key;
            }
        );
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.setAttribute('data-ckeditor-baseline', key);
            form.appendChild(input);
        }
        input.value = String(baselineHtml || '');
    }

    function formatHtmlForStorage(html) {
        var source = String(html || '').trim();
        if (source === '') {
            return '';
        }

        var template = document.createElement('template');
        template.innerHTML = source;
        var blockTags = new Set([
            'address', 'article', 'aside', 'blockquote', 'details', 'div', 'dl', 'dt', 'dd',
            'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4',
            'h5', 'h6', 'header', 'li', 'main', 'nav', 'ol', 'p', 'picture', 'pre',
            'section', 'summary', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'ul', 'video'
        ]);
        var voidTags = new Set(['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']);
        var rawTags = new Set(['pre', 'textarea']);
        var indentUnit = '  ';

        function indent(depth) {
            return indentUnit.repeat(Math.max(0, depth));
        }

        function openingTag(element) {
            var shell = element.cloneNode(false).outerHTML;
            var close = shell.indexOf('>');
            return close >= 0 ? shell.slice(0, close + 1) : '<' + element.localName + '>';
        }

        function serializeBlock(element, depth) {
            var tag = element.localName.toLowerCase();
            if (voidTags.has(tag)) {
                return [indent(depth) + element.outerHTML];
            }
            if (rawTags.has(tag)) {
                return [indent(depth) + element.outerHTML];
            }

            var children = Array.prototype.slice.call(element.childNodes);
            var hasBlockChild = children.some(function (child) {
                return child.nodeType === Node.ELEMENT_NODE && blockTags.has(child.localName.toLowerCase());
            });
            var open = openingTag(element);
            var close = '</' + tag + '>';
            if (!hasBlockChild) {
                return [indent(depth) + open + element.innerHTML + close];
            }

            var lines = [indent(depth) + open];
            var inlineBuffer = '';
            function flushInline() {
                if (/\S/.test(inlineBuffer)) {
                    lines.push(indent(depth + 1) + inlineBuffer.trim());
                }
                inlineBuffer = '';
            }

            children.forEach(function (child) {
                if (child.nodeType === Node.ELEMENT_NODE && blockTags.has(child.localName.toLowerCase())) {
                    flushInline();
                    lines = lines.concat(serializeBlock(child, depth + 1));
                    return;
                }
                if (child.nodeType === Node.COMMENT_NODE) {
                    inlineBuffer += '<!--' + child.nodeValue + '-->';
                    return;
                }
                inlineBuffer += child.nodeType === Node.ELEMENT_NODE ? child.outerHTML : child.nodeValue;
            });
            flushInline();
            lines.push(indent(depth) + close);
            return lines;
        }

        var output = [];
        Array.prototype.slice.call(template.content.childNodes).forEach(function (node) {
            if (node.nodeType === Node.ELEMENT_NODE && blockTags.has(node.localName.toLowerCase())) {
                output = output.concat(serializeBlock(node, 0));
            } else if (node.nodeType === Node.COMMENT_NODE) {
                output.push('<!--' + node.nodeValue + '-->');
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                output.push(node.outerHTML);
            } else if (/\S/.test(String(node.nodeValue || ''))) {
                output.push(String(node.nodeValue || '').trim());
            }
        });

        return output.join('\n').trim() + '\n';
    }

    function syncToTextarea(editor, textarea, forStorage) {
        try {
            var html = cleanOutputHtml(editor.getData());
            textarea.value = forStorage ? formatHtmlForStorage(html) : html;
        } catch (error) {
            // Keep the last known value.
        }
    }

    function wireEditor(editor, textarea, overrides) {
        editor.model.document.on('change:data', function () {
            syncToTextarea(editor, textarea);
            var html = textarea.value;
            if (overrides && typeof overrides.onChange === 'function') {
                overrides.onChange(html);
            } else if (overrides && typeof overrides.onInput === 'function') {
                overrides.onInput(html);
            }
        });

        if (editor.ui && editor.ui.focusTracker) {
            editor.ui.focusTracker.on('change:isFocused', function (event, name, isFocused) {
                if (!isFocused) {
                    syncToTextarea(editor, textarea);
                }
            });
        }

        initImageSizeControls(editor, textarea);

        var form = textarea.closest('form');
        if (form && !submitBoundForms.has(form)) {
            submitBoundForms.add(form);
            form.addEventListener('submit', function () {
                syncForm(form);
            });
        }

        // Legacy-compatible methods used by the AiAgent apply flow.
        if (typeof editor.setContents !== 'function') {
            editor.setContents = function (html) {
                editor.setData(String(html || ''));
            };
        }
        if (typeof editor.getContents !== 'function') {
            editor.getContents = function () {
                return editor.getData();
            };
        }

        var onReady = overrides && typeof overrides.onReady === 'function' ? overrides.onReady : null;
        if (onReady) {
            try {
                onReady(editor);
            } catch (error) {
                // no-op
            }
        }
    }

    function syncForm(form) {
        initialized.forEach(function (handle, textarea) {
            if (!(textarea instanceof HTMLTextAreaElement) || textarea.form !== form) {
                return;
            }
            if (handle && typeof handle.sync === 'function') {
                handle.sync();
            }
        });
    }

    function createHandle(textarea, editorPromise, overrides) {
        var destroyRequested = false;
        var handle = {
            editor: null,
            textarea: textarea,
            promise: editorPromise,
            getHtml: function () {
                if (handle.editor) {
                    try {
                        return formatHtmlForStorage(cleanOutputHtml(handle.editor.getData()));
                    } catch (error) {
                        // fall through
                    }
                }
                return String(textarea.value || '');
            },
            setContents: function (html) {
                textarea.value = String(html || '');
                if (handle.editor) {
                    try {
                        handle.editor.setContents(String(html || ''));
                    } catch (error) {
                        // Keep the raw textarea value.
                    }
                }
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
            },
            setHtml: function (html) {
                handle.setContents(html);
            },
            markDirty: function () {
                if (handle.editor) {
                    syncToTextarea(handle.editor, textarea, false);
                }
            },
            sync: function () {
                if (handle.editor) {
                    syncToTextarea(handle.editor, textarea, true);
                }
            },
            destroy: function () {
                destroyRequested = true;
                if (!handle.editor) {
                    return;
                }

                var editor = handle.editor;
                handle.editor = null;
                try {
                    return editor.destroy();
                } catch (error) {
                    // already destroyed
                }
            },
        };

        editorPromise.then(function (editor) {
            if (destroyRequested || initialized.get(textarea) !== handle) {
                try {
                    var pendingDestroy = editor.destroy();
                    if (pendingDestroy && typeof pendingDestroy.catch === 'function') {
                        pendingDestroy.catch(function () {});
                    }
                } catch (error) {
                    // already destroyed
                }
                return;
            }

            try {
                handle.editor = editor;
                ensureBaselineInput(textarea, cleanOutputHtml(editor.getData()));
                wireEditor(editor, textarea, overrides);
                textarea.setAttribute('data-ckeditor-init', '1');
            } catch (error) {
                handle.editor = null;
                try {
                    var failedDestroy = editor.destroy();
                    if (failedDestroy && typeof failedDestroy.catch === 'function') {
                        failedDestroy.catch(function () {});
                    }
                } catch (destroyError) {
                    // no-op
                }
                throw error;
            }
        }).catch(function () {
            if (initialized.get(textarea) === handle) {
                initialized.delete(textarea);
            }
            textarea.removeAttribute('data-ckeditor-init');
            delete textarea.__flatcmsCkEditorHandle;
        });

        return handle;
    }

    function create(textarea, options) {
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return null;
        }

        var existing = initialized.get(textarea);
        if (existing && typeof existing.destroy === 'function') {
            return existing;
        }
        if (!window.CKEDITOR || !window.CKEDITOR.ClassicEditor) {
            return null;
        }

        var overrides = options || {};
        var promise = window.CKEDITOR.ClassicEditor.create(textarea, buildConfig(textarea, overrides));
        var handle = createHandle(textarea, promise, overrides);
        initialized.set(textarea, handle);
        textarea.__flatcmsCkEditorHandle = handle;

        return handle;
    }

    function destroy(textarea) {
        var handle = initialized.get(textarea);
        initialized.delete(textarea);
        if (handle && typeof handle.destroy === 'function') {
            handle.destroy();
        }
        if (textarea instanceof HTMLTextAreaElement) {
            delete textarea.__flatcmsCkEditorHandle;
            textarea.removeAttribute('data-ckeditor-init');
        }
    }

    function sync(textarea) {
        var handle = initialized.get(textarea);
        if (handle && typeof handle.sync === 'function') {
            handle.sync();
        }
    }

    /* --------------------------------------------------------------------- *
     * Bootstrap (idempotent)
     * --------------------------------------------------------------------- */

    function initVisible() {
        getTargets().forEach(function (textarea) {
            if (isEffectivelyVisible(textarea)) {
                create(textarea);
            }
        });
    }

    function bindTranslationTabs() {
        var root = document.querySelector('[data-pages-translations-root]');
        if (!root) {
            return;
        }
        root.addEventListener('click', function (event) {
            if (!event.target || typeof event.target.closest !== 'function') {
                return;
            }
            if (!event.target.closest('[data-pages-tab-btn]')) {
                return;
            }
            window.setTimeout(function () {
                initVisible();
            }, 80);
        });
    }

    function bootstrap(force) {
        if (bootstrapBound && !force) {
            initVisible();
            return;
        }
        if (!force && providerDisabled) {
            return;
        }
        bootstrapBound = true;
        bindTranslationTabs();
        initVisible();
    }

    /* --------------------------------------------------------------------- *
     * Auto-start
     * --------------------------------------------------------------------- */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }

    window.FlatCMSCKEditor = {
        create: create,
        getEditor: function (textarea) {
            return initialized.get(textarea) || null;
        },
        destroy: destroy,
        destroyAll: function () {
            Array.from(initialized.keys()).forEach(function (textarea) {
                destroy(textarea);
            });
        },
        sync: sync,
        syncAll: function () {
            Array.from(initialized.keys()).forEach(function (textarea) {
                sync(textarea);
            });
        },
        setProviderDisabled: function (disabled) {
            providerDisabled = !!disabled;
        },
        bootstrap: function () {
            bootstrap(true);
        },
    };
})();
