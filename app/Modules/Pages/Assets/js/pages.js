/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    function getTranslationRoot() {
        return document.querySelector('[data-pages-translations-root]');
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

    function initPagesTranslationTabs() {
        var root = getTranslationRoot();
        if (!root) {
            return;
        }

        var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-pages-tab-btn]'));
        var panels = Array.prototype.slice.call(root.querySelectorAll('[data-pages-panel]'));
        var activeLocaleInput = root.querySelector('[data-pages-active-locale]');
        var documentTitleSuffix = '';
        if (!buttons.length || !panels.length || !(activeLocaleInput instanceof HTMLInputElement)) {
            return;
        }

        if (typeof document.title === 'string') {
            var suffixMatch = document.title.match(/\s+-\s+.+$/);
            documentTitleSuffix = suffixMatch ? String(suffixMatch[0] || '') : '';
        }

        function updateBadgeLabels(activeButton) {
            if (!activeButton) {
                return;
            }

            var sourceLabel = String(activeButton.getAttribute('data-pages-label-source') || '').trim();
            var readyLabel = String(activeButton.getAttribute('data-pages-label-ready') || '').trim();
            var missingLabel = String(activeButton.getAttribute('data-pages-label-missing') || '').trim();

            buttons.forEach(function(button) {
                var badge = button.querySelector('.pages-translation-tab-badge');
                if (!badge) {
                    return;
                }

                var state = String(button.getAttribute('data-tab-state') || '').trim();
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

        function updateChromeLabels(activeButton) {
            if (!activeButton) {
                return;
            }

            var pageTitle = String(activeButton.getAttribute('data-pages-page-title') || '').trim();
            var backLabel = String(activeButton.getAttribute('data-pages-back-label') || '').trim();
            var statusLabel = String(activeButton.getAttribute('data-pages-status-label') || '').trim();
            var statusDraftLabel = String(activeButton.getAttribute('data-pages-status-draft-label') || '').trim();
            var statusPublishedLabel = String(activeButton.getAttribute('data-pages-status-published-label') || '').trim();
            var saveLabel = String(activeButton.getAttribute('data-pages-save-label') || '').trim();

            if (pageTitle !== '') {
                document.title = pageTitle + documentTitleSuffix;
            }

            var pageTitleNode = document.querySelector('[data-pages-dynamic-page-title]');
            if (pageTitleNode && pageTitle !== '') {
                pageTitleNode.textContent = pageTitle;
            }

            var backLabelNode = document.querySelector('[data-pages-dynamic-back-label]');
            if (backLabelNode && backLabel !== '') {
                backLabelNode.textContent = backLabel;
            }

            var statusLabelNode = document.querySelector('[data-pages-dynamic-status-label]');
            if (statusLabelNode && statusLabel !== '') {
                statusLabelNode.textContent = statusLabel;
            }

            var statusDraftNode = document.querySelector('[data-pages-dynamic-status-draft-label]');
            if (statusDraftNode && statusDraftLabel !== '') {
                statusDraftNode.textContent = statusDraftLabel;
            }

            var statusPublishedNode = document.querySelector('[data-pages-dynamic-status-published-label]');
            if (statusPublishedNode && statusPublishedLabel !== '') {
                statusPublishedNode.textContent = statusPublishedLabel;
            }

            var saveLabelNode = document.querySelector('[data-pages-dynamic-save-label]');
            if (saveLabelNode && saveLabel !== '') {
                saveLabelNode.textContent = saveLabel;
            }
        }

        function activateTab(locale) {
            var targetLocale = String(locale || '').trim();
            if (targetLocale === '') {
                return;
            }

            var pagesSunEditor = window.FlatCMSPagesSunEditor || null;

            if (pagesSunEditor && typeof pagesSunEditor.destroy === 'function' && root.__pageActiveEditorTextarea instanceof HTMLTextAreaElement) {
                pagesSunEditor.destroy(root.__pageActiveEditorTextarea);
            }

            activeLocaleInput.value = targetLocale;
            var activeButton = buttons.find(function(button) {
                return String(button.getAttribute('data-tab') || '') === targetLocale;
            }) || null;
            updateBadgeLabels(activeButton);
            updateChromeLabels(activeButton);

            buttons.forEach(function(button) {
                var isActive = String(button.getAttribute('data-tab') || '') === targetLocale;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            panels.forEach(function(panel) {
                var isActive = String(panel.getAttribute('data-pages-panel') || '') === targetLocale;
                panel.classList.toggle('is-active', isActive);
                panel.hidden = !isActive;
            });

            if (pagesSunEditor && typeof pagesSunEditor.init === 'function') {
                pagesSunEditor.init();
            }

            document.dispatchEvent(new CustomEvent('pages:locale-changed', {
                detail: {
                    locale: targetLocale
                }
            }));
        }

        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                activateTab(String(button.getAttribute('data-tab') || ''));
            });
        });

        activateTab(String(activeLocaleInput.value || buttons[0].getAttribute('data-tab') || ''));
    }

    function syncBatchHiddenInputs(container, ids) {
        if (!container) {
            return;
        }

        container.innerHTML = '';
        ids.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = String(id || '');
            container.appendChild(input);
        });
    }

    function initPagesBatchActions() {
        var form = document.querySelector('[data-pages-batch-form]');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var selectAll = form.querySelector('[data-pages-select-all]');
        var actionSelect = form.querySelector('[data-pages-batch-action]');
        var submitButton = form.querySelector('[data-pages-batch-submit]');
        var idsContainer = form.querySelector('[data-pages-batch-ids]');
        var countLabel = form.querySelector('[data-pages-batch-count]');
        var checkboxes = Array.prototype.slice.call(document.querySelectorAll('[data-page-select]'));
        var emptySelectionMessage = String(form.getAttribute('data-empty-selection-message') || '').trim();
        var actionRequiredMessage = String(form.getAttribute('data-action-required-message') || '').trim();
        var selectedTemplate = String(form.getAttribute('data-selected-template') || ':count').trim();
        var deleteMessage = String(form.getAttribute('data-delete-message') || '').trim();
        var deleteItemTemplate = String(form.getAttribute('data-delete-item-template') || ':count').trim();

        if (!actionSelect || !submitButton || !idsContainer) {
            return;
        }

        function getSelectedIds() {
            return checkboxes
                .filter(function(checkbox) {
                    return checkbox && checkbox.checked && !checkbox.disabled;
                })
                .map(function(checkbox) {
                    return String(checkbox.value || '').trim();
                })
                .filter(function(value) {
                    return value !== '';
                });
        }

        function syncSelectAll(ids) {
            if (!(selectAll instanceof HTMLInputElement)) {
                return;
            }

            var enabledCheckboxes = checkboxes.filter(function(checkbox) {
                return checkbox && !checkbox.disabled;
            });
            var selectedCount = ids.length;
            selectAll.checked = enabledCheckboxes.length > 0 && selectedCount === enabledCheckboxes.length;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < enabledCheckboxes.length;
        }

        function syncConfirmBehavior(selectedCount) {
            if (!(submitButton instanceof HTMLButtonElement)) {
                return;
            }

            if (String(actionSelect.value || '') === 'delete' && selectedCount > 0) {
                submitButton.setAttribute('data-action', 'confirm-delete');
                submitButton.setAttribute('data-message', deleteMessage);
                submitButton.setAttribute('data-item-name', deleteItemTemplate.replace(':count', String(selectedCount)));
                return;
            }

            submitButton.removeAttribute('data-action');
            submitButton.removeAttribute('data-message');
            submitButton.removeAttribute('data-item-name');
        }

        function syncBatchState() {
            var ids = getSelectedIds();
            syncBatchHiddenInputs(idsContainer, ids);
            syncSelectAll(ids);
            syncConfirmBehavior(ids.length);

            if (countLabel) {
                countLabel.textContent = selectedTemplate.replace(':count', String(ids.length));
            }

            submitButton.disabled = ids.length === 0 || String(actionSelect.value || '').trim() === '';
        }

        if (selectAll instanceof HTMLInputElement) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(function(checkbox) {
                    if (!checkbox || checkbox.disabled) {
                        return;
                    }
                    checkbox.checked = selectAll.checked;
                });
                syncBatchState();
            });
        }

        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', syncBatchState);
        });

        actionSelect.addEventListener('change', syncBatchState);

        form.addEventListener('submit', function(event) {
            var ids = getSelectedIds();
            if (ids.length === 0) {
                event.preventDefault();
                showToast(emptySelectionMessage, 'warning');
                return;
            }

            if (String(actionSelect.value || '').trim() === '') {
                event.preventDefault();
                showToast(actionRequiredMessage, 'warning');
            }
        });

        syncBatchState();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPagesTranslationTabs();
        if (!getTranslationRoot() && window.FlatCMSPagesSunEditor && typeof window.FlatCMSPagesSunEditor.init === 'function') {
            window.FlatCMSPagesSunEditor.init();
        }
        initPagesBatchActions();
    });
})();
