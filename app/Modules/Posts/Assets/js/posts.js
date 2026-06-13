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
            input.value = id;
            container.appendChild(input);
        });
    }

    function initFeaturedImagePicker() {
        var field = document.querySelector('[data-post-featured-media]');
        if (!field) {
            return;
        }

        var input = field.querySelector('[data-post-featured-input]');
        var preview = field.querySelector('[data-post-featured-preview]');
        var previewImage = field.querySelector('[data-post-featured-preview-img]');
        var openButton = field.querySelector('[data-post-featured-open]');
        var clearButton = field.querySelector('[data-post-featured-clear]');
        var mediaModal = document.getElementById('mediaModal');
        var modalError = String(field.getAttribute('data-modal-error') || '').trim();

        if (!input) {
            return;
        }

        function showModalError() {
            if (modalError === '') {
                return;
            }

            if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
                window.FlatCMS.toast.show(modalError, 'warning');
                return;
            }
        }

        function closeMediaModal() {
            if (!mediaModal) {
                return;
            }
            mediaModal.classList.add('hidden');
            mediaModal.style.display = 'none';
        }

        function normalizeUploadPath(rawValue) {
            var value = String(rawValue || '').trim();
            if (value === '') {
                return '';
            }

            if (
                value.indexOf('http://') === 0 ||
                value.indexOf('https://') === 0 ||
                value.indexOf('//') === 0
            ) {
                try {
                    var parsed = new URL(value, window.location.origin);
                    value = String(parsed.pathname || '').trim();
                } catch (error) {
                    return '';
                }
            }

            var cleaned = value.replace(/^\.?\//, '');
            if (cleaned === '') {
                return '';
            }

            if (/^public\/uploads\//i.test(cleaned)) {
                return '/uploads/' + cleaned.replace(/^public\/uploads\//i, '').replace(/^\/+/, '');
            }

            if (/^uploads\//i.test(cleaned)) {
                return '/uploads/' + cleaned.replace(/^uploads\//i, '').replace(/^\/+/, '');
            }

            return '';
        }

        function resolvePreviewUrl(rawValue) {
            var value = String(rawValue || '').trim();
            if (value === '') {
                return '';
            }

            if (
                value.indexOf('http://') === 0 ||
                value.indexOf('https://') === 0 ||
                value.indexOf('//') === 0 ||
                value.indexOf('data:') === 0 ||
                value.indexOf('blob:') === 0
            ) {
                return value;
            }

            var normalizedUploadPath = normalizeUploadPath(value);
            if (normalizedUploadPath !== '') {
                return normalizedUploadPath;
            }

            if (value.charAt(0) === '/') {
                return value;
            }

            return '/uploads/' + value.replace(/^\/+/, '');
        }

        function hidePreview() {
            if (!preview || !previewImage) {
                return;
            }
            preview.hidden = true;
            previewImage.hidden = true;
            previewImage.removeAttribute('src');
            delete previewImage.dataset.expectedSrc;
            previewImage.onload = null;
            previewImage.onerror = null;
        }

        function updatePreview() {
            if (!preview || !previewImage) {
                return;
            }

            var src = resolvePreviewUrl(input.value);
            if (src === '') {
                hidePreview();
                return;
            }

            hidePreview();
            previewImage.dataset.expectedSrc = src;
            previewImage.onload = function() {
                if (previewImage.dataset.expectedSrc !== src) {
                    return;
                }
                preview.hidden = false;
                previewImage.hidden = false;
                previewImage.onload = null;
                previewImage.onerror = null;
            };
            previewImage.onerror = function() {
                if (previewImage.dataset.expectedSrc !== src) {
                    return;
                }
                hidePreview();
            };
            previewImage.src = src;

            if (previewImage.complete) {
                if (previewImage.naturalWidth > 0 && previewImage.naturalHeight > 0) {
                    preview.hidden = false;
                    previewImage.hidden = false;
                    previewImage.onload = null;
                    previewImage.onerror = null;
                } else {
                    hidePreview();
                }
            }
        }

        function extractInputValue(file) {
            if (!file || typeof file !== 'object') {
                return '';
            }

            var pathValue = normalizeUploadPath(file.path || '');
            if (pathValue !== '') {
                return pathValue;
            }

            pathValue = normalizeUploadPath(file.url || '');
            if (pathValue !== '') {
                return pathValue;
            }

            return '';
        }

        if (clearButton) {
            clearButton.addEventListener('click', function() {
                input.value = '';
                updatePreview();
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }

        if (openButton) {
            openButton.addEventListener('click', function() {
                if (!mediaModal || typeof window.initMediaModal !== 'function') {
                    showModalError();
                    return;
                }

                mediaModal.classList.remove('hidden');
                mediaModal.style.display = 'flex';
                window.initMediaModal({
                    mode: 'images',
                    folder: 'images',
                    openUploadIfEmpty: true,
                    initialTab: 'library',
                    onSelect: function(file) {
                        var value = extractInputValue(file);
                        if (value !== '') {
                            input.value = value;
                        }
                        updatePreview();
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        closeMediaModal();
                    }
                });
            });
        }

        input.addEventListener('input', updatePreview);
        input.addEventListener('change', updatePreview);
        updatePreview();
    }

    function initShortcodeCopy() {
        var copyButtons = document.querySelectorAll('[data-post-copy-shortcode]');
        if (!copyButtons.length) {
            return;
        }

        var activePopover = null;
        var popoverTimer = 0;

        function fallbackCopy(text) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.pointerEvents = 'none';
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);

            var success = false;
            try {
                success = document.execCommand('copy');
            } catch (error) {
                success = false;
            }

            textarea.remove();
            return success;
        }

        function writeClipboard(text) {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                return navigator.clipboard.writeText(text);
            }

            return new Promise(function(resolve, reject) {
                if (fallbackCopy(text)) {
                    resolve();
                    return;
                }
                reject(new Error('copy_failed'));
            });
        }

        function clearPopover() {
            if (popoverTimer) {
                window.clearTimeout(popoverTimer);
                popoverTimer = 0;
            }

            if (activePopover) {
                activePopover.remove();
                activePopover = null;
            }
        }

        function showPopover(button, message) {
            if (String(message || '').trim() === '') {
                return;
            }

            clearPopover();

            var popover = document.createElement('span');
            popover.className = 'posts-inline-popover';
            popover.textContent = message;
            document.body.appendChild(popover);

            var buttonRect = button.getBoundingClientRect();
            var popoverRect = popover.getBoundingClientRect();
            var horizontalPadding = 12;
            var viewportWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);

            var top = buttonRect.top - popoverRect.height - 12;
            var left = buttonRect.left + (buttonRect.width / 2) - (popoverRect.width / 2);

            if (left < horizontalPadding) {
                left = horizontalPadding;
            } else if ((left + popoverRect.width) > (viewportWidth - horizontalPadding)) {
                left = viewportWidth - popoverRect.width - horizontalPadding;
            }

            popover.style.top = Math.max(8, top) + 'px';
            popover.style.left = Math.max(8, left) + 'px';

            window.requestAnimationFrame(function() {
                popover.classList.add('is-visible');
            });

            activePopover = popover;
            popoverTimer = window.setTimeout(function() {
                if (!activePopover) {
                    return;
                }
                activePopover.classList.remove('is-visible');
                window.setTimeout(clearPopover, 180);
            }, 1650);
        }

        function pulseCopied(button) {
            button.classList.add('is-copied');
            window.setTimeout(function() {
                button.classList.remove('is-copied');
            }, 900);
        }

        for (var i = 0; i < copyButtons.length; i += 1) {
            copyButtons[i].addEventListener('click', function(event) {
                event.preventDefault();

                var button = event.currentTarget;
                var text = String(button.getAttribute('data-copy-text') || '').trim();
                if (text === '') {
                    text = String(button.textContent || '').trim();
                }
                if (text === '') {
                    return;
                }

                var popoverMessage = String(button.getAttribute('data-popover-message') || '').trim();

                writeClipboard(text).then(function() {
                    pulseCopied(button);
                    showPopover(button, popoverMessage);
                }).catch(function() {
                    // noop
                });
            });
        }

        window.addEventListener('scroll', clearPopover, { passive: true });
        window.addEventListener('resize', clearPopover);
    }

    function initPostsBatchActions() {
        var form = document.querySelector('[data-posts-batch-form]');
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        var selectAll = form.querySelector('[data-posts-select-all]');
        var actionSelect = form.querySelector('[data-posts-batch-action]');
        var submitButton = form.querySelector('[data-posts-batch-submit]');
        var idsContainer = form.querySelector('[data-posts-batch-ids]');
        var countLabel = form.querySelector('[data-posts-batch-count]');
        var checkboxes = Array.prototype.slice.call(document.querySelectorAll('[data-post-select]'));
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
        initFeaturedImagePicker();
        initShortcodeCopy();
        initPostsBatchActions();
    });
})();
