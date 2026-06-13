/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    function showToast(message, type) {
        var text = String(message || '').trim();
        if (text === '') {
            return;
        }

        if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
            window.FlatCMS.toast.show(text, type || 'warning');
        }
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

    function initTrashBatchActions() {
        var form = document.querySelector('[data-trash-batch-form]');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var selectAll = form.querySelector('[data-trash-select-all]');
        var actionSelect = form.querySelector('[data-trash-batch-action]');
        var submitButton = form.querySelector('[data-trash-batch-submit]');
        var idsContainer = form.querySelector('[data-trash-batch-ids]');
        var countLabel = form.querySelector('[data-trash-batch-count]');
        var checkboxes = Array.prototype.slice.call(document.querySelectorAll('[data-trash-select]'));
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

    document.addEventListener('DOMContentLoaded', function() {
        initTrashBatchActions();
    });
})();
