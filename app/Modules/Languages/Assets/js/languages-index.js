/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var scanFillModal = null;
        var reloadAfterModalClose = false;

        function ensureScanFillModal(closeLabel) {
            if (scanFillModal) {
                return scanFillModal;
            }

            var modal = document.createElement('div');
            modal.id = 'scanFillResultModal';
            modal.className = 'modal-overlay hidden';
            modal.setAttribute('aria-hidden', 'true');
            modal.innerHTML = '' +
                '<div class="modal-container modal-sm" role="dialog" aria-modal="true" aria-labelledby="scanFillResultTitle">' +
                    '<div class="modal-header">' +
                        '<h3 class="modal-title" id="scanFillResultTitle"></h3>' +
                        '<button type="button" class="modal-close" data-modal-close="scanFillResultModal" aria-label="' + escapeHtml(closeLabel || 'Close') + '">&times;</button>' +
                    '</div>' +
                    '<div class="modal-body modal-body-centered">' +
                        '<p id="scanFillResultMessage"></p>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                        '<button type="button" class="btn btn-primary" id="scanFillResultConfirm" data-modal-close="scanFillResultModal">' + escapeHtml(closeLabel || 'Close') + '</button>' +
                    '</div>' +
                '</div>';

            document.body.appendChild(modal);
            scanFillModal = modal;
            return modal;
        }

        function openScanFillModal(title, message, closeLabel, shouldReloadAfterClose) {
            var modal = ensureScanFillModal(closeLabel);
            var titleEl = modal.querySelector('#scanFillResultTitle');
            var messageEl = modal.querySelector('#scanFillResultMessage');
            var confirmBtn = modal.querySelector('#scanFillResultConfirm');

            if (titleEl) {
                titleEl.textContent = title || '';
            }
            if (messageEl) {
                messageEl.textContent = message || '';
            }
            if (confirmBtn) {
                confirmBtn.textContent = closeLabel || 'Close';
            }

            reloadAfterModalClose = !!shouldReloadAfterClose;
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');

            window.requestAnimationFrame(function () {
                if (confirmBtn) {
                    confirmBtn.focus();
                }
            });
        }

        function closeScanFillModal() {
            if (!scanFillModal) return;

            scanFillModal.classList.add('hidden');
            scanFillModal.setAttribute('aria-hidden', 'true');

            var mustReload = reloadAfterModalClose;
            reloadAfterModalClose = false;

            if (mustReload) {
                window.location.reload();
            }
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        document.addEventListener('click', function (e) {
            var closeTrigger = e.target.closest('[data-modal-close="scanFillResultModal"]');
            if (closeTrigger) {
                e.preventDefault();
                closeScanFillModal();
                return;
            }

            if (scanFillModal && e.target === scanFillModal) {
                closeScanFillModal();
                return;
            }

            var btn = e.target.closest('[data-action="scan-fill"]');
            if (!btn) return;

            var code = btn.dataset.code;
            var url  = btn.dataset.url;
            var token = btn.dataset.token;
            var msgSuccess = btn.dataset.msgSuccess;
            var msgNone    = btn.dataset.msgNone;
            var modalTitle = btn.dataset.modalTitle || '';
            var modalClose = btn.dataset.modalClose || 'OK';

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(url + '/' + code + '/scan-fill', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: '_token=' + encodeURIComponent(token)
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search-plus"></i>';

                if (data.total_added > 0) {
                    openScanFillModal(
                        modalTitle,
                        msgSuccess.replace(':count', data.total_added),
                        modalClose,
                        true
                    );
                } else {
                    openScanFillModal(
                        modalTitle,
                        msgNone,
                        modalClose,
                        false
                    );
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search-plus"></i>';
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && scanFillModal && !scanFillModal.classList.contains('hidden')) {
                closeScanFillModal();
            }
        });
    });
})();
