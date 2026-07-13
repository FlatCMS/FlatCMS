/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        function initSettingsMediaPicker() {
            const configNode = document.querySelector('[data-settings-media-config]');
            if (!configNode) {
                return;
            }

            const rawConfig = String(configNode.getAttribute('data-config') || '{}');
            const modalError = String(configNode.getAttribute('data-modal-error') || 'Media modal unavailable');
            const mediaModal = document.getElementById('mediaModal');

            let baseConfig = {};
            try {
                baseConfig = JSON.parse(rawConfig);
            } catch (error) {
                baseConfig = {};
            }

            function showModalError() {
                if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
                    window.FlatCMS.toast.show(modalError, 'warning');
                    return;
                }
                alert(modalError);
            }

            function openMediaModal(options) {
                if (!mediaModal || typeof window.initMediaModal !== 'function') {
                    showModalError();
                    return;
                }

                mediaModal.classList.remove('hidden');
                mediaModal.style.display = 'flex';
                window.initMediaModal(options);
            }

            function closeMediaModal() {
                if (!mediaModal) {
                    return;
                }
                mediaModal.classList.add('hidden');
                mediaModal.style.display = 'none';
            }

            function normalizePreviewUrl(rawValue, mediaKind) {
                const value = String(rawValue || '').trim();
                if (value === '') {
                    return '';
                }

                if (
                    value.startsWith('http://') ||
                    value.startsWith('https://') ||
                    value.startsWith('//') ||
                    value.startsWith('data:') ||
                    value.startsWith('blob:')
                ) {
                    return value;
                }

                const uploadsBase = String(baseConfig.uploadsBase || '/uploads').replace(/\/+$/, '');
                const cleaned = value.replace(/^\.?\//, '');
                const normalizedUploadPath = normalizeUploadPath(value, mediaKind);

                if (normalizedUploadPath !== '') {
                    const relativeUploadPath = normalizedUploadPath.replace(/^\/uploads\/?/i, '');
                    return relativeUploadPath === '' ? uploadsBase : (uploadsBase + '/' + relativeUploadPath);
                }

                if (value.startsWith('/')) {
                    return value;
                }

                if (cleaned.startsWith('uploads/')) {
                    return '/' + cleaned;
                }

                if (cleaned.startsWith('logo/')) {
                    return uploadsBase + '/' + cleaned;
                }

                if (mediaKind === 'logo' || mediaKind === 'favicon') {
                    return uploadsBase + '/logo/' + cleaned;
                }

                return '/' + cleaned;
            }

            function normalizeUploadPath(rawValue, mediaKind) {
                const value = String(rawValue || '').trim();
                if (value === '') {
                    return '';
                }

                if (
                    value.startsWith('http://') ||
                    value.startsWith('https://') ||
                    value.startsWith('//')
                ) {
                    const extracted = extractUploadPathFromUrl(value, mediaKind);
                    if (extracted !== '') {
                        return extracted;
                    }
                    return '';
                }

                const cleaned = value.replace(/^\.?\//, '');
                if (cleaned === '') {
                    return '';
                }

                if (/^public\/uploads\//i.test(cleaned)) {
                    return '/uploads/' + cleaned.replace(/^public\/uploads\//i, '').replace(/^\/+/, '');
                }

                if (/^uploads\//i.test(cleaned)) {
                    return '/uploads/' + cleaned.replace(/^uploads\//i, '').replace(/^\/+/, '');
                }

                if (/^logo\//i.test(cleaned)) {
                    return '/uploads/logo/' + cleaned.replace(/^logo\//i, '').replace(/^\/+/, '');
                }

                if ((mediaKind === 'logo' || mediaKind === 'favicon') && !cleaned.includes('/')) {
                    return '/uploads/logo/' + cleaned.replace(/^\/+/, '');
                }

                return '';
            }

            function extractUploadPathFromUrl(rawUrl, mediaKind) {
                const urlValue = String(rawUrl || '').trim();
                if (urlValue === '') {
                    return '';
                }

                try {
                    const parsed = new URL(urlValue, window.location.origin);
                    return normalizeUploadPath(parsed.pathname || '', mediaKind);
                } catch (error) {
                    return '';
                }
            }

            function getFieldParts(field) {
                if (!field) {
                    return null;
                }
                return {
                    input: field.querySelector('[data-settings-media-input]'),
                    preview: field.querySelector('[data-settings-media-preview]'),
                    image: field.querySelector('[data-settings-media-image]'),
                };
            }

            function isLogoModeField(field, parts) {
                return !!(field && parts && parts.input && parts.input.hasAttribute('data-settings-logo-active-input'));
            }

            function getLogoModeState(field) {
                if (!field) {
                    return null;
                }

                const selector = field.querySelector('[data-settings-logo-mode]');
                if (!selector) {
                    return null;
                }

                const mode = String(selector.value || '').trim().toLowerCase() === 'dark' ? 'dark' : 'light';
                const storageInput = field.querySelector('[data-settings-logo-mode-value="' + mode + '"]');
                if (!storageInput) {
                    return null;
                }

                return {
                    selector: selector,
                    mode: mode,
                    storageInput: storageInput,
                };
            }

            function syncLogoModeStorage(field, parts) {
                if (!isLogoModeField(field, parts)) {
                    return;
                }

                const modeState = getLogoModeState(field);
                if (!modeState) {
                    return;
                }

                modeState.storageInput.value = String(parts.input.value || '').trim();
            }

            function syncLogoModeInput(field) {
                const parts = getFieldParts(field);
                if (!isLogoModeField(field, parts)) {
                    return;
                }

                const modeState = getLogoModeState(field);
                if (!modeState) {
                    return;
                }

                parts.input.value = String(modeState.storageInput.value || '').trim();
            }

            function hideFieldPreview(parts) {
                if (!parts || !parts.preview || !parts.image) {
                    return;
                }
                parts.preview.hidden = true;
                parts.image.hidden = true;
                parts.image.removeAttribute('src');
                delete parts.image.dataset.expectedSrc;
                parts.image.onload = null;
                parts.image.onerror = null;
            }

            function updateFieldPreview(field) {
                const parts = getFieldParts(field);
                if (!parts || !parts.input || !parts.image || !parts.preview) {
                    return;
                }

                const mediaKind = String(field.getAttribute('data-media-kind') || '');
                const value = String(parts.input.value || '').trim();
                const src = normalizePreviewUrl(value, mediaKind);

                if (src === '') {
                    hideFieldPreview(parts);
                    return;
                }

                hideFieldPreview(parts);
                parts.image.dataset.expectedSrc = src;
                parts.image.onload = function() {
                    if (parts.image.dataset.expectedSrc !== src) {
                        return;
                    }
                    parts.preview.hidden = false;
                    parts.image.hidden = false;
                    parts.image.onload = null;
                    parts.image.onerror = null;
                };
                parts.image.onerror = function() {
                    if (parts.image.dataset.expectedSrc !== src) {
                        return;
                    }
                    hideFieldPreview(parts);
                };
                parts.image.src = src;
                if (parts.image.complete) {
                    if (parts.image.naturalWidth > 0 && parts.image.naturalHeight > 0) {
                        parts.preview.hidden = false;
                        parts.image.hidden = false;
                        parts.image.onload = null;
                        parts.image.onerror = null;
                    } else {
                        hideFieldPreview(parts);
                    }
                }
            }

            function extractInputValue(file) {
                if (!file || typeof file !== 'object') {
                    return '';
                }

                const mediaKind = String(file.__mediaKind || '').trim().toLowerCase();
                const pathValue = normalizeUploadPath(String(file.path || ''), mediaKind);
                if (pathValue !== '') {
                    return pathValue;
                }

                const explicitUrl = String(file.url || '').trim();
                if (explicitUrl !== '') {
                    const extractedPath = extractUploadPathFromUrl(explicitUrl, mediaKind);
                    if (extractedPath !== '') {
                        return extractedPath;
                    }
                    return explicitUrl;
                }

                const path = String(file.path || '').trim();
                if (path === '') {
                    return '';
                }

                const uploadsBase = String(baseConfig.uploadsBase || '/uploads').replace(/\/+$/, '');
                return uploadsBase + '/' + path.replace(/^\/+/, '');
            }

            const fields = Array.from(document.querySelectorAll('[data-settings-media-field]'));
            if (!fields.length) {
                return;
            }

            fields.forEach(function(field) {
                const parts = getFieldParts(field);
                if (!parts || !parts.input) {
                    return;
                }

                const openButton = field.querySelector('[data-settings-media-open]');
                const clearButton = field.querySelector('[data-settings-media-clear]');

                syncLogoModeInput(field);
                updateFieldPreview(field);

                parts.input.addEventListener('input', function() {
                    syncLogoModeStorage(field, parts);
                    updateFieldPreview(field);
                });
                parts.input.addEventListener('change', function() {
                    syncLogoModeStorage(field, parts);
                    updateFieldPreview(field);
                });

                const logoModeSelector = field.querySelector('[data-settings-logo-mode]');
                if (logoModeSelector) {
                    logoModeSelector.addEventListener('change', function() {
                        syncLogoModeInput(field);
                        updateFieldPreview(field);
                    });
                }

                if (clearButton) {
                    clearButton.addEventListener('click', function() {
                        parts.input.value = '';
                        parts.input.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }

                if (openButton) {
                    openButton.addEventListener('click', function() {
                        const mediaKind = String(field.getAttribute('data-media-kind') || '').trim().toLowerCase();
                        const modalOptions = Object.assign({}, baseConfig, {
                            mode: 'images',
                            folder: 'images',
                            openUploadIfEmpty: true,
                            initialTab: 'library',
                            onSelect: function(file) {
                                if (file && typeof file === 'object') {
                                    file.__mediaKind = mediaKind;
                                }
                                const value = extractInputValue(file);
                                if (value !== '') {
                                    parts.input.value = value;
                                }
                                updateFieldPreview(field);
                                parts.input.dispatchEvent(new Event('change', { bubbles: true }));
                                closeMediaModal();
                            },
                        });

                        openMediaModal(modalOptions);
                    });
                }
            });
        }

        function initContactCaptchaDependency() {
            const turnstileToggle = document.querySelector('input[type="checkbox"][name="env[TURNSTILE_ENABLED]"]');
            const contactCaptchaGroup = document.querySelector('[data-contact-captcha-group]');
            const contactCaptchaToggle = document.querySelector('input[type="checkbox"][name="contact_enable_captcha"]');
            const contactCaptchaLabel = contactCaptchaGroup
                ? contactCaptchaGroup.querySelector('[data-contact-captcha-label]')
                : null;
            const contactCaptchaHint = contactCaptchaGroup
                ? contactCaptchaGroup.querySelector('[data-contact-captcha-hint]')
                : null;

            if (!turnstileToggle || !contactCaptchaGroup || !contactCaptchaToggle) {
                return;
            }

            const defaultHint = String(contactCaptchaGroup.getAttribute('data-hint-default') || '');
            const disabledHint = String(contactCaptchaGroup.getAttribute('data-hint-disabled') || defaultHint);
            const disabledTooltip = String(contactCaptchaGroup.getAttribute('data-tooltip-disabled') || disabledHint);

            const syncState = function() {
                const turnstileEnabled = !!turnstileToggle.checked;

                contactCaptchaGroup.classList.toggle('is-disabled', !turnstileEnabled);
                contactCaptchaToggle.disabled = !turnstileEnabled;

                if (!turnstileEnabled) {
                    contactCaptchaToggle.checked = false;
                }

                if (contactCaptchaHint) {
                    contactCaptchaHint.textContent = turnstileEnabled ? defaultHint : disabledHint;
                }

                if (contactCaptchaLabel) {
                    if (turnstileEnabled) {
                        contactCaptchaLabel.removeAttribute('title');
                    } else {
                        contactCaptchaLabel.setAttribute('title', disabledTooltip);
                    }
                }
            };

            turnstileToggle.addEventListener('change', syncState);
            syncState();
        }

        function initIntegrationsAccordions() {
            const root = document.querySelector('[data-settings-integrations-accordion]');
            if (!root) {
                return function() {};
            }

            const headers = Array.from(root.querySelectorAll('[data-settings-integration-toggle]'));
            if (!headers.length) {
                return function() {};
            }

            headers.forEach(function(header) {
                const content = header.nextElementSibling;
                if (!content) {
                    return;
                }

                const card = header.closest('[data-settings-integration-card]');
                const shouldOpen = card && String(card.getAttribute('data-settings-initial-open') || '').toLowerCase() === 'true';

                if (shouldOpen) {
                    header.classList.add('active');
                    content.classList.add('active');
                } else {
                    content.style.maxHeight = '0px';
                    header.classList.remove('active');
                    content.classList.remove('active');
                }

                header.addEventListener('click', function() {
                    const isOpen = header.classList.contains('active');
                    if (isOpen) {
                        content.style.maxHeight = content.scrollHeight + 'px';
                        content.offsetHeight;
                        content.style.maxHeight = '0';
                        header.classList.remove('active');
                        content.classList.remove('active');
                        return;
                    }
                    content.style.maxHeight = content.scrollHeight + 'px';
                    header.classList.add('active');
                    content.classList.add('active');
                });
            });

            return function syncIntegrationsAccordionHeights() {
                headers.forEach(function(header) {
                    const content = header.nextElementSibling;
                    if (!content) {
                        return;
                    }
                    if (header.classList.contains('active')) {
                        content.style.maxHeight = content.scrollHeight + 'px';
                    } else {
                        content.style.maxHeight = '0';
                    }
                });
            };
        }

        function initSiteBrandingModal() {
            const modal = document.querySelector('[data-site-branding-modal]');
            const openButtons = Array.from(document.querySelectorAll('[data-site-branding-open]'));
            const tabButtons = modal ? Array.from(modal.querySelectorAll('[data-site-branding-tab-btn]')) : [];
            const panels = modal ? Array.from(modal.querySelectorAll('[data-site-branding-panel]')) : [];
            const localeFields = modal ? Array.from(modal.querySelectorAll('[data-site-branding-locale-field]')) : [];
            let activeLocale = modal ? String(modal.getAttribute('data-site-branding-active-locale') || '').trim() : '';
            const modalTitle = modal ? modal.querySelector('[data-site-branding-modal-title]') : null;
            const modalTablist = modal ? modal.querySelector('[data-site-branding-tablist]') : null;
            const modalFooterInfo = modal ? modal.querySelector('[data-site-branding-footer-info]') : null;
            const modalCloseIcon = modal ? modal.querySelector('[data-site-branding-close-icon]') : null;
            const modalCloseButton = modal ? modal.querySelector('[data-site-branding-close-btn]') : null;
            const modalSaveButton = modal ? modal.querySelector('[data-site-branding-save-btn]') : null;
            const mainFields = {
                site_name: document.querySelector('[data-site-branding-main-field="site_name"]'),
                site_description: document.querySelector('[data-site-branding-main-field="site_description"]'),
                site_slogan: document.querySelector('[data-site-branding-main-field="site_slogan"]')
            };

            if (!modal || !openButtons.length || !tabButtons.length || !panels.length) {
                return;
            }

            function parseNodeJson(node, attributeName) {
                if (!node) {
                    return {};
                }

                const rawValue = String(node.getAttribute(attributeName) || '').trim();
                if (rawValue === '') {
                    return {};
                }

                try {
                    const parsed = JSON.parse(rawValue);
                    return parsed && typeof parsed === 'object' ? parsed : {};
                } catch (error) {
                    return {};
                }
            }

            function localeUiLabels(localeCode) {
                const panel = modal.querySelector('[data-site-branding-panel="' + localeCode + '"]');
                return parseNodeJson(panel, 'data-site-branding-ui');
            }

            function updateTabBadgeLabels(labels) {
                if (!labels || typeof labels !== 'object') {
                    return;
                }

                tabButtons.forEach(function(button) {
                    const status = String(button.getAttribute('data-status') || 'empty').trim();
                    const badge = button.querySelector('.settings-branding-translation-tab-badge');
                    if (!badge) {
                        return;
                    }

                    let nextLabel = '';
                    if (status === 'source') {
                        nextLabel = String(labels.translation_source || '');
                    } else if (status === 'translated') {
                        nextLabel = String(labels.translation_ready || '');
                    } else {
                        nextLabel = String(labels.translation_missing || '');
                    }

                    if (nextLabel !== '') {
                        badge.textContent = nextLabel;
                    }
                });
            }

            function applyModalLocaleUi(localeCode) {
                const labels = localeUiLabels(localeCode);
                if (!labels || typeof labels !== 'object') {
                    return;
                }

                if (modalTitle && labels.modal_title) {
                    modalTitle.textContent = String(labels.modal_title);
                }

                if (modalTablist && labels.translations_label) {
                    modalTablist.setAttribute('aria-label', String(labels.translations_label));
                }

                if (modalFooterInfo && labels.modal_help) {
                    modalFooterInfo.textContent = String(labels.modal_help);
                }

                if (modalCloseIcon && labels.close) {
                    modalCloseIcon.setAttribute('aria-label', String(labels.close));
                }

                if (modalCloseButton && labels.close) {
                    modalCloseButton.textContent = String(labels.close);
                }

                if (modalSaveButton && labels.save) {
                    modalSaveButton.textContent = String(labels.save);
                }

                updateTabBadgeLabels(labels);
            }

            function activeLocalePanelField(fieldName) {
                if (!activeLocale || !fieldName) {
                    return null;
                }

                const panel = modal.querySelector('[data-site-branding-panel="' + activeLocale + '"]');
                if (!panel) {
                    return null;
                }

                return panel.querySelector('[data-site-branding-locale-field="' + fieldName + '"]');
            }

            function openBrandingModal() {
                modal.classList.remove('is-initially-hidden');
                modal.style.display = 'flex';
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                Object.keys(mainFields).forEach(function(fieldName) {
                    syncMainToActiveLocale(fieldName);
                });

                if (activeLocale) {
                    activateBrandingTab(activeLocale);
                }

                const activePanel = panels.find(function(panel) {
                    return panel.classList.contains('is-active');
                }) || panels[0];
                const firstInput = activePanel ? activePanel.querySelector('input, textarea') : null;
                if (firstInput && typeof firstInput.focus === 'function') {
                    window.requestAnimationFrame(function() {
                        firstInput.focus();
                    });
                }
            }

            function updateModalBodyOverflow() {
                const anyVisibleModal = Array.from(document.querySelectorAll('.modal-overlay')).some(function(modalNode) {
                    return window.getComputedStyle(modalNode).display !== 'none';
                });
                document.body.style.overflow = anyVisibleModal ? 'hidden' : '';
            }

            function closeBrandingModal() {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.add('is-initially-hidden');
                updateModalBodyOverflow();
            }

            function activateBrandingTab(tabCode) {
                const target = String(tabCode || '').trim();
                if (!target) {
                    return;
                }

                activeLocale = target;
                modal.setAttribute('data-site-branding-active-locale', target);

                tabButtons.forEach(function(button) {
                    const active = String(button.getAttribute('data-tab') || '') === target;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panels.forEach(function(panel) {
                    const active = String(panel.getAttribute('data-site-branding-panel') || '') === target;
                    panel.classList.toggle('is-active', active);
                    if (active) {
                        panel.removeAttribute('hidden');
                    } else {
                        panel.setAttribute('hidden', 'hidden');
                    }
                });

                applyModalLocaleUi(target);
            }

            function syncMainToActiveLocale(fieldName) {
                const mainField = mainFields[fieldName];
                const localeField = activeLocalePanelField(fieldName);
                if (!mainField || !localeField) {
                    return;
                }

                localeField.value = String(mainField.value || '');
            }

            function syncActiveLocaleToMain(fieldName) {
                const mainField = mainFields[fieldName];
                const localeField = activeLocalePanelField(fieldName);
                if (!mainField || !localeField) {
                    return;
                }

                mainField.value = String(localeField.value || '');
            }

            openButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    openBrandingModal();
                });
            });

            tabButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    activateBrandingTab(String(button.getAttribute('data-tab') || ''));
                });
            });

            localeFields.forEach(function(field) {
                const name = String(field.getAttribute('data-site-branding-locale-field') || '').trim();
                if (!name) {
                    return;
                }

                const panel = field.closest('[data-site-branding-panel]');
                const fieldLocale = panel ? String(panel.getAttribute('data-site-branding-panel') || '').trim() : '';

                const syncIfActive = function() {
                    if (fieldLocale !== activeLocale) {
                        return;
                    }
                    syncActiveLocaleToMain(name);
                };

                field.addEventListener('input', function() {
                    syncIfActive();
                });
                field.addEventListener('change', function() {
                    syncIfActive();
                });
            });

            Array.from(modal.querySelectorAll('[data-modal-close="siteBrandingModal"]')).forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    closeBrandingModal();
                });
            });

            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeBrandingModal();
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key !== 'Escape' || modal.getAttribute('aria-hidden') === 'true') {
                    return;
                }

                closeBrandingModal();
            });

            Object.keys(mainFields).forEach(function(fieldName) {
                const mainField = mainFields[fieldName];
                if (!mainField) {
                    return;
                }

                mainField.addEventListener('input', function() {
                    syncMainToActiveLocale(fieldName);
                });
                mainField.addEventListener('change', function() {
                    syncMainToActiveLocale(fieldName);
                });

                syncMainToActiveLocale(fieldName);
            });

            applyModalLocaleUi(activeLocale || String((panels[0] && panels[0].getAttribute('data-site-branding-panel')) || ''));
        }

        function initLocalizationDefaults() {
            const languageSelect = document.getElementById('default_language');
            const timezoneSelect = document.getElementById('timezone');
            const dateFormatSelect = document.getElementById('date_format');

            if (!languageSelect || !timezoneSelect || !dateFormatSelect) {
                return;
            }

            const frLocale = 'fr-FR';
            const frTimezone = 'Europe/Paris';
            const frDateFormat = 'd F Y';
            const hasFrDateFormat = Array.from(dateFormatSelect.options || []).some(function(option) {
                return String(option.value || '') === frDateFormat;
            });

            languageSelect.addEventListener('change', function() {
                if (String(languageSelect.value || '') !== frLocale) {
                    return;
                }

                if (String(timezoneSelect.value || '') !== frTimezone) {
                    timezoneSelect.value = frTimezone;
                    timezoneSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (hasFrDateFormat && String(dateFormatSelect.value || '') !== frDateFormat) {
                    dateFormatSelect.value = frDateFormat;
                    dateFormatSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        function initHomepageRouting() {
            const modeSelect = document.querySelector('[data-homepage-mode]');
            const pageField = document.querySelector('[data-homepage-page-field]');
            if (!modeSelect || !pageField) {
                return;
            }

            function syncHomepageFields() {
                const usePage = String(modeSelect.value || '') === 'page';
                if (usePage) {
                    pageField.removeAttribute('hidden');
                } else {
                    pageField.setAttribute('hidden', 'hidden');
                }
            }

            modeSelect.addEventListener('change', syncHomepageFields);
            syncHomepageFields();
        }

        function initPromoBannerTranslations() {
            const root = document.querySelector('[data-promo-banner-translations-root]');
            if (!root) {
                return;
            }

            const buttons = Array.from(root.querySelectorAll('[data-promo-banner-tab-btn]'));
            const panels = Array.from(document.querySelectorAll('[data-promo-banner-panel]'));
            const activeLocaleInput = document.querySelector('[data-promo-banner-active-locale]');

            if (!buttons.length || !panels.length || !activeLocaleInput) {
                return;
            }

            function updateBadgeLabels(activeButton) {
                if (!activeButton) {
                    return;
                }

                const sourceLabel = String(activeButton.getAttribute('data-promo-banner-label-source') || '').trim();
                const readyLabel = String(activeButton.getAttribute('data-promo-banner-label-ready') || '').trim();
                const missingLabel = String(activeButton.getAttribute('data-promo-banner-label-missing') || '').trim();

                buttons.forEach(function(button) {
                    const badge = button.querySelector('.promo-banner-translation-tab-badge');
                    if (!badge) {
                        return;
                    }

                    const state = String(button.getAttribute('data-tab-state') || '').trim();
                    if (state === 'source') {
                        badge.textContent = sourceLabel;
                        return;
                    }
                    if (state === 'ready') {
                        badge.textContent = readyLabel;
                        return;
                    }
                    badge.textContent = missingLabel;
                });
            }

            function activateTab(target) {
                const locale = String(target || '').trim();
                if (!locale) {
                    return;
                }

                const activeButton = buttons.find(function(button) {
                    return String(button.getAttribute('data-tab') || '') === locale;
                }) || null;

                activeLocaleInput.value = locale;
                updateBadgeLabels(activeButton);

                buttons.forEach(function(button) {
                    const isActive = String(button.getAttribute('data-tab') || '') === locale;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                panels.forEach(function(panel) {
                    const isActive = String(panel.getAttribute('data-promo-banner-panel') || '') === locale;
                    panel.classList.toggle('is-active', isActive);
                    panel.hidden = !isActive;
                });
            }

            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    activateTab(String(button.getAttribute('data-tab') || ''));
                });
            });

            activateTab(String(activeLocaleInput.value || buttons[0].getAttribute('data-tab') || ''));
        }

        function initAlignControls() {
            const controls = Array.from(document.querySelectorAll('[data-align-control]'));
            if (!controls.length) {
                return;
            }

            controls.forEach(function(control) {
                const options = Array.from(control.querySelectorAll('.settings-align-option'));
                const inputs = Array.from(control.querySelectorAll('.settings-align-option-input'));
                if (!options.length || !inputs.length) {
                    return;
                }

                const sync = function() {
                    options.forEach(function(option) {
                        const input = option.querySelector('.settings-align-option-input');
                        option.classList.toggle('is-active', !!(input && input.checked));
                    });
                };

                inputs.forEach(function(input) {
                    input.addEventListener('change', sync);
                });

                sync();
            });
        }

        function initSettingsCopyButtons() {
            const buttons = Array.from(document.querySelectorAll('[data-settings-copy-target]'));
            if (!buttons.length) {
                return;
            }

            function fallbackCopyText(text) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', 'readonly');
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                textarea.style.top = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();

                let copied = false;
                try {
                    copied = document.execCommand('copy');
                } catch (error) {
                    copied = false;
                }

                document.body.removeChild(textarea);
                return copied;
            }

            function copyText(text) {
                const value = String(text || '');
                if (!value) {
                    return Promise.resolve(false);
                }

                if (
                    window.navigator &&
                    window.navigator.clipboard &&
                    typeof window.navigator.clipboard.writeText === 'function' &&
                    window.isSecureContext
                ) {
                    return window.navigator.clipboard.writeText(value)
                        .then(function() { return true; })
                        .catch(function() { return fallbackCopyText(value); });
                }

                return Promise.resolve(fallbackCopyText(value));
            }

            buttons.forEach(function(button) {
                const targetSelector = String(button.getAttribute('data-settings-copy-target') || '').trim();
                if (!targetSelector) {
                    return;
                }

                const label = button.querySelector('[data-settings-copy-button-label]');
                const defaultLabel = String(button.getAttribute('data-settings-copy-label') || (label ? label.textContent : '') || '').trim();
                const copiedLabel = String(button.getAttribute('data-settings-copied-label') || 'OK').trim();

                button.addEventListener('click', function() {
                    const target = document.querySelector(targetSelector);
                    if (!target) {
                        return;
                    }

                    const value = typeof target.value === 'string'
                        ? target.value
                        : String(target.textContent || '');

                    if (typeof target.select === 'function') {
                        target.focus();
                        target.select();
                    }

                    copyText(value).then(function(copied) {
                        if (!copied) {
                            return;
                        }

                        button.classList.add('is-copied');
                        if (label && copiedLabel) {
                            label.textContent = copiedLabel;
                        }

                        window.setTimeout(function() {
                            button.classList.remove('is-copied');
                            if (label && defaultLabel) {
                                label.textContent = defaultLabel;
                            }
                        }, 1600);
                    });
                });
            });
        }

        const tabsRoot = document.querySelector('[data-settings-tabs]');
        if (!tabsRoot) {
            initSettingsMediaPicker();
            initLocalizationDefaults();
            initHomepageRouting();
            initPromoBannerTranslations();
            initAlignControls();
            initSettingsCopyButtons();
            return;
        }

        const tabButtons = Array.from(tabsRoot.querySelectorAll('[data-settings-tab-btn]'));
        const tabPanels = Array.from(document.querySelectorAll('[data-settings-panel]'));
        const activeTabInput = document.querySelector('[data-settings-active-tab]');
        if (!tabButtons.length || !tabPanels.length) {
            return;
        }

        const storageKey = 'flatcms.settings.active_tab';
        let syncIntegrationsAccordions = function() {};

        function activateTab(tabName, updateHash) {
            const normalized = String(tabName || '').trim();
            const hasTarget = tabPanels.some((panel) => panel.dataset.settingsPanel === normalized);
            const target = hasTarget ? normalized : String(tabButtons[0].dataset.tab || '');

            tabButtons.forEach((button) => {
                const active = button.dataset.tab === target;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            tabPanels.forEach((panel) => {
                const active = panel.dataset.settingsPanel === target;
                panel.classList.toggle('is-active', active);
                if (active) {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', 'hidden');
                }
            });

            try {
                window.localStorage.setItem(storageKey, target);
            } catch (error) {
                // no-op
            }

            if (updateHash) {
                const nextHash = '#settings-' + target;
                if (window.location.hash !== nextHash) {
                    if (window.history && typeof window.history.replaceState === 'function') {
                        window.history.replaceState(null, '', nextHash);
                    } else {
                        window.location.hash = nextHash;
                    }
                }
            }

            if (activeTabInput) {
                activeTabInput.value = target;
            }

            if (target === 'integrations') {
                window.requestAnimationFrame(function() {
                    window.requestAnimationFrame(function() {
                        syncIntegrationsAccordions();
                    });
                });
            }
        }

        function openGoogleOAuthCardFromHash() {
            const currentHash = String(window.location.hash || '');
            if (currentHash !== '#settings-google-oauth') {
                return;
            }

            const card = document.querySelector('[data-settings-google-oauth-card]');
            if (!card) {
                return;
            }

            const header = card.querySelector('[data-settings-integration-toggle]');
            if (header && !header.classList.contains('active')) {
                header.click();
            }

            window.requestAnimationFrame(function() {
                syncIntegrationsAccordions();
                if (typeof card.scrollIntoView === 'function') {
                    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }

        tabButtons.forEach((button) => {
            button.addEventListener('click', function() {
                activateTab(button.dataset.tab || '', true);
            });
        });

        const tabShortcutButtons = Array.from(document.querySelectorAll('[data-settings-open-tab]'));
        tabShortcutButtons.forEach((button) => {
            button.addEventListener('click', function() {
                activateTab(button.getAttribute('data-settings-open-tab') || '', true);
            });
        });

        let initialTab = '';
        const hash = String(window.location.hash || '');
        if (hash === '#settings-google-oauth') {
            initialTab = 'integrations';
        } else if (hash.indexOf('#settings-') === 0) {
            initialTab = hash.replace('#settings-', '');
        }

        if (!initialTab) {
            try {
                initialTab = window.localStorage.getItem(storageKey) || '';
            } catch (error) {
                initialTab = '';
            }
        }

        syncIntegrationsAccordions = initIntegrationsAccordions();
        activateTab(initialTab, false);
        window.requestAnimationFrame(openGoogleOAuthCardFromHash);
        window.addEventListener('hashchange', function() {
            const currentHash = String(window.location.hash || '');
            if (currentHash === '#settings-integrations' || currentHash === '#settings-google-oauth') {
                activateTab('integrations', false);
                window.requestAnimationFrame(openGoogleOAuthCardFromHash);
            }
        });
        initSettingsMediaPicker();
        initSiteBrandingModal();
        initLocalizationDefaults();
        initHomepageRouting();
        initPromoBannerTranslations();
        initAlignControls();
        initSettingsCopyButtons();
        initContactCaptchaDependency();

        const startTourButton = document.querySelector('[data-action="guided-tour-start"]');
        const tourConfig = window.FlatCMSGuidedTourConfig && typeof window.FlatCMSGuidedTourConfig === 'object'
            ? window.FlatCMSGuidedTourConfig
            : {};
        const tourErrorMessage = (tourConfig.labels && tourConfig.labels.errorToast)
            ? String(tourConfig.labels.errorToast)
            : '';

        if (startTourButton) {
            startTourButton.addEventListener('click', function() {
                if (window.FlatCMS && window.FlatCMS.guidedTour && typeof window.FlatCMS.guidedTour.start === 'function') {
                    window.FlatCMS.guidedTour.start(true);
                    return;
                }

                if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
                    window.FlatCMS.toast.show(tourErrorMessage, 'warning');
                }
            });
        }

        const resetTourButton = document.querySelector('[data-action="guided-tour-reset"]');
        if (resetTourButton) {
            resetTourButton.addEventListener('click', function() {
                if (!(window.FlatCMS && window.FlatCMS.guidedTour && typeof window.FlatCMS.guidedTour.resetSeen === 'function')) {
                    if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
                        window.FlatCMS.toast.show(tourErrorMessage, 'warning');
                    }
                    return;
                }

                const resetResult = window.FlatCMS.guidedTour.resetSeen();
                if (resetResult && typeof resetResult.then === 'function') {
                    resetResult.then(function(success) {
                        if (!success) {
                            return;
                        }

                        const enabledInput = document.getElementById('admin_guided_tour_enabled');
                        if (enabledInput && enabledInput.type === 'checkbox') {
                            enabledInput.checked = true;
                        }
                    });
                }
            });
        }
    });
})();
