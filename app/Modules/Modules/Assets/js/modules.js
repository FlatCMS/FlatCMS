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
        const moduleIndex = document.querySelector('[data-module-index]');
        const moduleLists = Array.from(document.querySelectorAll('[data-module-list]'));
        const cards = Array.from(document.querySelectorAll('[data-module-card]'));
        const headers = Array.from(document.querySelectorAll('[data-module-toggle]'));
        const statusButtons = Array.from(document.querySelectorAll('[data-filter-status]'));
        const typeFilter = document.getElementById('moduleTypeFilter');
        const locationFilter = document.getElementById('moduleLocationFilter');
        const searchInput = document.getElementById('moduleSearchInput');
        const initialStatus = moduleIndex && moduleIndex.dataset.initialStatus ? moduleIndex.dataset.initialStatus : '';
        const autoDeleteModule = moduleIndex && moduleIndex.dataset.autoDeleteModule
            ? moduleIndex.dataset.autoDeleteModule.toLowerCase().replace(/[^a-z0-9_-]/g, '')
            : '';
        const autoOpenModule = moduleIndex && moduleIndex.dataset.autoOpenModule
            ? moduleIndex.dataset.autoOpenModule.toLowerCase().replace(/[^a-z0-9_-]/g, '')
            : '';

        if (!cards.length) {
            return;
        }

        let activeStatus = ['enabled', 'all', 'disabled', 'required'].includes(initialStatus)
            ? initialStatus
            : 'enabled';

        function setActiveStatus(button) {
            statusButtons.forEach(btn => btn.classList.remove('is-active'));
            button.classList.add('is-active');
            activeStatus = button.dataset.filterStatus || 'all';
            applyFilters();
        }

        function applyFilters() {
            const typeValue = typeFilter ? typeFilter.value : '';
            const locationValue = locationFilter ? locationFilter.value : '';
            const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            const visibleByList = new Map();

            moduleLists.forEach(list => {
                visibleByList.set(list, 0);
            });

            cards.forEach(card => {
                const status = card.dataset.status || '';
                const type = card.dataset.type || '';
                const location = card.dataset.location || '';
                const search = card.dataset.search || '';

                const matchStatus = activeStatus === 'all'
                    || status === activeStatus
                    || (activeStatus === 'enabled' && status === 'required')
                    || (activeStatus === 'disabled' && status === 'locked');
                const matchType = !typeValue || type === typeValue;
                const matchLocation = !locationValue || location === locationValue;
                const matchSearch = !query || search.includes(query);

                const isVisible = matchStatus && matchType && matchLocation && matchSearch;
                card.classList.toggle('hidden', !isVisible);
                if (isVisible) {
                    const list = card.closest('[data-module-list]');
                    if (list) {
                        visibleByList.set(list, (visibleByList.get(list) || 0) + 1);
                    }
                }
            });

            moduleLists.forEach(list => {
                const emptyState = list.querySelector('[data-module-empty]');
                if (!emptyState) {
                    return;
                }

                emptyState.classList.toggle('hidden', (visibleByList.get(list) || 0) !== 0);
            });
        }

        statusButtons.forEach(button => {
            button.addEventListener('click', () => setActiveStatus(button));
        });

        if (typeFilter) {
            typeFilter.addEventListener('change', applyFilters);
        }

        if (locationFilter) {
            locationFilter.addEventListener('change', applyFilters);
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }

        headers.forEach(header => {
            header.addEventListener('click', () => {
                const list = header.closest('[data-module-list]');
                const content = header.nextElementSibling;
                if (!content) return;
                const isActive = header.classList.contains('active');

                if (list && !isActive) {
                    const openHeaders = Array.from(list.querySelectorAll('[data-module-toggle].active'));
                    openHeaders.forEach(openHeader => {
                        if (openHeader === header) {
                            return;
                        }

                        const openContent = openHeader.nextElementSibling;
                        if (!openContent) {
                            return;
                        }

                        openContent.style.maxHeight = openContent.scrollHeight + 'px';
                        openContent.offsetHeight;
                        openContent.style.maxHeight = '0';
                        openHeader.classList.remove('active');
                        openContent.classList.remove('active');
                    });
                }

                if (!isActive) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                    header.classList.add('active');
                    content.classList.add('active');
                    return;
                }

                content.style.maxHeight = content.scrollHeight + 'px';
                content.offsetHeight;
                content.style.maxHeight = '0';
                header.classList.remove('active');
                content.classList.remove('active');
            });
        });

        function openCard(card) {
            if (!card) return;
            const header = card.querySelector('[data-module-toggle]');
            const content = header ? header.nextElementSibling : null;
            if (!header || !content) return;
            if (header.classList.contains('active')) return;
            header.click();
        }

        function resolveStatusFilterForCard(card) {
            if (!card) {
                return 'all';
            }

            const status = card.dataset.status || '';
            if (status === 'required') {
                return 'required';
            }

            if (status === 'enabled') {
                return 'enabled';
            }

            if (status === 'disabled' || status === 'locked') {
                return 'disabled';
            }

            return 'all';
        }

        function cleanupAutoModuleQueryParam(paramName) {
            if (!paramName || !window.history || typeof window.history.replaceState !== 'function') {
                return;
            }

            const url = new URL(window.location.href);
            url.searchParams.delete(paramName);
            window.history.replaceState({}, document.title, url.toString());
        }

        function triggerAutoOpenModule() {
            if (!autoOpenModule) return;

            const targetCard = document.querySelector('[data-module-name="' + autoOpenModule + '"]');
            if (!targetCard) {
                cleanupAutoModuleQueryParam('installed');
                return;
            }

            const targetStatus = resolveStatusFilterForCard(targetCard);
            if (targetStatus !== activeStatus) {
                const targetButton = statusButtons.find(btn => (btn.dataset.filterStatus || '') === targetStatus);
                if (targetButton) {
                    setActiveStatus(targetButton);
                } else {
                    applyFilters();
                }
            }

            targetCard.classList.remove('hidden');
            openCard(targetCard);

            if (typeof targetCard.scrollIntoView === 'function') {
                targetCard.scrollIntoView({ block: 'start', behavior: 'smooth' });
            }

            cleanupAutoModuleQueryParam('installed');
        }

        // Fallback confirm only when global admin modal handler is unavailable.
        const hasGlobalConfirmHandler = !!(window.FlatCMS
            && window.FlatCMS.modal
            && typeof window.FlatCMS.modal.confirm === 'function');

        if (!hasGlobalConfirmHandler) {
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-action="confirm-delete"]');
                if (!btn) return;

                const form = btn.closest('form');
                if (!form) return;

                const message = btn.dataset.message || 'Êtes-vous sûr ?';
                if (!window.confirm(message)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        }

        function triggerAutoDeletePrompt() {
            if (!autoDeleteModule) return;

            const targetButton = document.querySelector('[data-module-delete-target="' + autoDeleteModule + '"]');
            if (!targetButton || targetButton.disabled) {
                cleanupAutoModuleQueryParam('prompt_delete');
                return;
            }

            const card = targetButton.closest('[data-module-card]');
            if (card) {
                card.classList.remove('hidden');
                openCard(card);
            }

            targetButton.click();
            cleanupAutoModuleQueryParam('prompt_delete');
        }

        const preferredButton = statusButtons.find(btn => (btn.dataset.filterStatus || '') === activeStatus);
        if (preferredButton) {
            setActiveStatus(preferredButton);
        } else {
            const defaultButton = statusButtons.find(btn => btn.classList.contains('is-active'));
            if (defaultButton) {
                setActiveStatus(defaultButton);
            } else {
                applyFilters();
            }
        }

        setTimeout(triggerAutoOpenModule, 120);
        setTimeout(triggerAutoDeletePrompt, 220);
    });
})();
