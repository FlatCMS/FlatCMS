/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: public/assets/js/admin/editor-provider-init.js
 * Version: 2.0.0-dev
 */

/**
 * Rich-text provider dispatcher.
 *
 * The default provider is CKEditor 5 (GPL), loaded globally through
 * ckeditor-provider-init.js which auto-bootstraps every editable textarea. When
 * TINYMCE_ENABLED is on, the TinyMCE Cloud build is used instead and the
 * CKEditor provider is disabled for the current page.
 */
(function() {
    'use strict';

    const root = document.body || document.documentElement;
    const adapterScript = document.currentScript;
    const providerRaw = String(root.getAttribute('data-wysiwyg-provider') || 'ckeditor').toLowerCase();
    const provider = providerRaw === 'tinymce' ? 'tinymce' : 'ckeditor';

    if (provider !== 'tinymce') {
        // CKEditor 5 auto-bootstraps through ckeditor-provider-init.js.
        return;
    }

    if (window.FlatCMSCKEditor && typeof window.FlatCMSCKEditor.setProviderDisabled === 'function') {
        window.FlatCMSCKEditor.setProviderDisabled(true);
    }

    const selector = 'textarea.form-input:not([data-no-editor])';
    const candidates = Array.from(document.querySelectorAll(selector));

    function parseMediaConfig() {
        const modal = document.getElementById('mediaModal');
        if (!modal) {
            return null;
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

    function resolveMediaSource(file, uploadsBase) {
        const explicit = String((file && file.url) || '').trim();
        if (explicit !== '') {
            return explicit;
        }
        const path = String((file && file.path) || '').trim();
        if (path === '') {
            return '';
        }
        const base = String(uploadsBase || '/uploads').replace(/\/+$/, '');
        return base + '/' + path.replace(/^\/+/, '');
    }

    function openMediaModalForImage(onSelect) {
        const modal = document.getElementById('mediaModal');
        const callback = typeof onSelect === 'function' ? onSelect : function() {};
        const media = window.FlatCMS && window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.media;

        if (!modal || !media || typeof media.open !== 'function') {
            const fallbackUrl = window.prompt('', 'https://');
            if (fallbackUrl) {
                callback(String(fallbackUrl));
            }
            return;
        }

        const baseConfig = parseMediaConfig() || {};
        const uploadsBase = String(baseConfig.uploadsBase || '/uploads');

        media.open(Object.assign({}, baseConfig, {
            mode: 'images',
            folder: 'images',
            openUploadIfEmpty: true,
            initialTab: 'library',
            onSelect: function(file) {
                const src = resolveMediaSource(file, uploadsBase);
                if (src !== '') {
                    callback(src);
                }
                media.close();
            },
        }));
    }

    function markAsExternalEditor(textareas) {
        textareas.forEach((textarea) => {
            if (!textarea.hasAttribute('data-no-editor')) {
                textarea.setAttribute('data-no-editor', '');
            }
            textarea.setAttribute('data-editor-provider-active', provider);
        });
    }

    function clearExternalEditorMarks(textareas) {
        textareas.forEach((textarea) => {
            if (!textarea) {
                return;
            }
            textarea.removeAttribute('data-no-editor');
            textarea.removeAttribute('data-editor-provider-active');
            textarea.removeAttribute('data-editor-instance-initialized');
        });
    }

    function initTinyMce(textareas) {
        if (!window.tinymce || typeof window.tinymce.init !== 'function') {
            return false;
        }

        const isLightMode = !!(root.classList.contains('light-mode') || document.documentElement.classList.contains('theme-light-init'));
        const isDarkMode = !isLightMode && window.getComputedStyle(root).colorScheme.split(/\s+/).includes('dark');
        const tinySkin = isDarkMode ? 'oxide-dark' : 'oxide';
        const contentCss = adapterScript && adapterScript.getAttribute('data-content-css');
        const tinyContentCss = [isDarkMode ? 'dark' : 'default'];
        if (contentCss) {
            tinyContentCss.push(contentCss);
        }
        document.querySelectorAll('link[data-editor-content-theme]').forEach((link) => {
            if (link.href) {
                tinyContentCss.push(link.href);
            }
        });

        markAsExternalEditor(textareas);

        try {
            textareas.forEach((textarea) => {
                if (textarea.getAttribute('data-editor-instance-initialized') === '1') {
                    return;
                }
                textarea.setAttribute('data-editor-instance-initialized', '1');

                window.tinymce.init({
                    target: textarea,
                    menubar: false,
                    branding: false,
                    promotion: false,
                    statusbar: false,
                    height: 320,
                    skin: tinySkin,
                    content_css: tinyContentCss,
                    body_id: 'flatcms',
                    body_class: 'flatcms-editor-content admin-body' + (isLightMode ? ' light-mode' : ''),
                    convert_urls: false,
                    relative_urls: false,
                    plugins: 'autolink autoresize code image link lists table',
                    toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link image table | removeformat code',
                    file_picker_types: 'image',
                    file_picker_callback: function(callback, value, meta) {
                        if (meta && meta.filetype !== 'image') {
                            return;
                        }
                        openMediaModalForImage(function(src) {
                            callback(src, { alt: '' });
                        });
                    },
                    setup: function(editor) {
                        editor.on('input change undo redo SetContent', function() {
                            textarea.value = editor.getContent();
                            textarea.dispatchEvent(new CustomEvent('flatcms:editor-change', {
                                bubbles: true,
                                detail: { provider: 'tinymce' }
                            }));
                        });
                    },
                });
            });
        } catch (error) {
            console.warn('FlatCMS: TinyMCE bootstrap failed, falling back to CKEditor.', error);
            clearExternalEditorMarks(textareas);
            if (window.FlatCMSCKEditor && typeof window.FlatCMSCKEditor.setProviderDisabled === 'function') {
                window.FlatCMSCKEditor.setProviderDisabled(false);
            }
            if (window.FlatCMSCKEditor && typeof window.FlatCMSCKEditor.bootstrap === 'function') {
                window.FlatCMSCKEditor.bootstrap();
            }
            return false;
        }

        return true;
    }

    const initialized = initTinyMce(candidates);
    if (!initialized) {
        clearExternalEditorMarks(candidates);
    }
})();
