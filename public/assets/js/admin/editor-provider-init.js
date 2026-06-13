/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const root = document.body || document.documentElement;
    const search = (window.location && window.location.search) ? String(window.location.search) : '';
    const params = new URLSearchParams(search);
    const routePath = String(params.get('path') || '').toLowerCase();
    const isBuilderRoute = routePath.indexOf('admin/pages-builder') === 0
        || routePath.indexOf('admin/menu-builder') === 0
        || routePath.indexOf('admin/footer-builder') === 0;
    const hasBuilderConfigNode = !!(
        document.getElementById('pagesBuilderConfig')
        || document.getElementById('footerBuilderConfig')
        || document.getElementById('megaMenuConfig')
    );
    const isBuilderMode = !!(root
        && root.classList
        && (
            root.classList.contains('pb-editor-mode')
            || root.classList.contains('fb-editor-mode')
            || root.classList.contains('menu-mega-mode')
        ));

    // Builders manage their own editor lifecycle per widget field.
    if (isBuilderRoute || hasBuilderConfigNode || isBuilderMode) {
        return;
    }

    const selector = 'textarea.form-input:not([data-no-editor])';
    const candidates = Array.from(document.querySelectorAll(selector));
    if (!candidates.length) {
        return;
    }

    const providerRaw = String(root.getAttribute('data-wysiwyg-provider') || 'suneditor').toLowerCase();
    const provider = providerRaw === 'tinymce' ? 'tinymce' : 'suneditor';

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

    function closeMediaModal(modal) {
        if (!modal) {
            return;
        }
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function openMediaModalForImage(onSelect) {
        const modal = document.getElementById('mediaModal');
        const callback = typeof onSelect === 'function' ? onSelect : function() {};

        if (!modal || typeof window.initMediaModal !== 'function') {
            const fallbackUrl = window.prompt('', 'https://');
            if (fallbackUrl) {
                callback(String(fallbackUrl));
            }
            return;
        }

        const baseConfig = parseMediaConfig() || {};
        const uploadsBase = String(baseConfig.uploadsBase || '/uploads');

        window.initMediaModal(Object.assign({}, baseConfig, {
            mode: 'images',
            folder: 'images',
            openUploadIfEmpty: true,
            initialTab: 'library',
            onSelect: function(file) {
                const src = resolveMediaSource(file, uploadsBase);
                if (src !== '') {
                    callback(src);
                }
                closeMediaModal(modal);
            },
        }));

        modal.classList.remove('hidden');
        modal.style.display = 'flex';
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

        const adminTheme = String(root.getAttribute('data-theme') || '').toLowerCase();
        const isModernPro = adminTheme === 'modern-pro';
        const isLightMode = !!(root.classList.contains('light-mode') || document.documentElement.classList.contains('theme-light-init'));
        const tinySkin = isModernPro && !isLightMode ? 'oxide-dark' : 'oxide';
        const tinyContentCss = isModernPro && !isLightMode ? 'dark' : 'default';
        const tinyContentStyle = isModernPro && !isLightMode
            ? [
                'body{background:#1e293b;color:#e2e8f0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.65;padding:12px;}',
                'a{color:#818cf8;}',
                'h1,h2,h3,h4,h5,h6{color:#f8fafc;}',
                'blockquote{border-left:3px solid #334155;color:#cbd5e1;margin:0;padding-left:12px;}',
                'pre,code{background:#0f172a;color:#e2e8f0;}'
            ].join('')
            : [
                'body{background:#ffffff;color:#0f172a;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.65;padding:12px;}',
                'a{color:#4f46e5;}',
                'blockquote{border-left:3px solid #e2e8f0;color:#334155;margin:0;padding-left:12px;}',
                'pre,code{background:#f8fafc;color:#0f172a;}'
            ].join('');

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
                    content_style: tinyContentStyle,
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
                });
            });
        } catch (error) {
            console.warn('FlatCMS: TinyMCE bootstrap failed, fallback to SunEditor.', error);
            return false;
        }

        return true;
    }

    function initSunEditor(textareas) {
        const sun = window.FlatCMSSunEditor;
        if (!sun || typeof sun.create !== 'function') {
            return false;
        }

        markAsExternalEditor(textareas);

        try {
            textareas.forEach((textarea) => {
                if (!(textarea instanceof HTMLTextAreaElement)) {
                    return;
                }

                if (textarea.getAttribute('data-editor-instance-initialized') === '1') {
                    return;
                }

                if (textarea.__flatcmsSunEditorHandle && typeof textarea.__flatcmsSunEditorHandle.destroy === 'function') {
                    textarea.__flatcmsSunEditorHandle.destroy();
                }

                textarea.__flatcmsSunEditorHandle = sun.create(textarea, {
                    minHeight: '220px',
                    height: 320,
                    applyAccordion: true,
                    onInput: function(nextHtml) {
                        textarea.value = String(nextHtml || '');
                    },
                    onChange: function(nextHtml) {
                        textarea.value = String(nextHtml || '');
                    },
                });

                if (!textarea.__flatcmsSunEditorHandle) {
                    return;
                }

                textarea.setAttribute('data-editor-instance-initialized', '1');
            });
        } catch (error) {
            console.warn('FlatCMS: SunEditor bootstrap failed.', error);
            return false;
        }

        return true;
    }

    if (provider === 'tinymce') {
        const initialized = initTinyMce(candidates);
        if (!initialized) {
            const fallbackInitialized = initSunEditor(candidates);
            if (!fallbackInitialized) {
                clearExternalEditorMarks(candidates);
            }
        }
        return;
    }

    const initialized = initSunEditor(candidates);
    if (!initialized) {
        clearExternalEditorMarks(candidates);
    }

})();
