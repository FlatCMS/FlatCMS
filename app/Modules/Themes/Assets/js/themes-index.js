/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function () {
    'use strict';

    const cards = Array.from(document.querySelectorAll('[data-theme-card]'));
    if (!cards.length) {
        return;
    }

    const sections = Array.from(document.querySelectorAll('[data-theme-section]'));
    const emptyState = document.getElementById('themesEmptyState');
    const statusButtons = Array.from(document.querySelectorAll('[data-theme-status]'));
    const typeFilter = document.getElementById('themeTypeFilter');
    const categoryFilter = document.getElementById('themeCategoryFilter');
    const colorFilter = document.getElementById('themeColorFilter');
    const searchInput = document.getElementById('themeSearchInput');

    let currentStatus = 'active';

    function normalize(value) {
        return (value || '').toLowerCase();
    }

    function matchesFilters(card) {
        const status = card.dataset.status || '';
        const type = card.dataset.type || '';
        const category = card.dataset.category || '';
        const color = card.dataset.color || '';
        const searchValue = normalize(card.dataset.search || '');

        if (currentStatus !== 'all' && status !== currentStatus) {
            return false;
        }
        if (typeFilter && typeFilter.value && typeFilter.value !== type) {
            return false;
        }
        if (categoryFilter && categoryFilter.value && categoryFilter.value !== category) {
            return false;
        }
        if (colorFilter && colorFilter.value && colorFilter.value !== color) {
            return false;
        }
        if (searchInput && searchInput.value.trim()) {
            const needle = normalize(searchInput.value.trim());
            if (!searchValue.includes(needle)) {
                return false;
            }
        }
        return true;
    }

    function applyFilters() {
        let visibleCount = 0;

        cards.forEach(card => {
            const visible = matchesFilters(card);
            card.classList.toggle('hidden', !visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        sections.forEach(section => {
            const sectionCards = Array.from(section.querySelectorAll('[data-theme-card]'));
            const hasVisible = sectionCards.some(card => !card.classList.contains('hidden'));
            section.classList.toggle('hidden', !hasVisible);
        });

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
        }
    }

    statusButtons.forEach(button => {
        button.addEventListener('click', () => {
            statusButtons.forEach(item => item.classList.remove('is-active'));
            button.classList.add('is-active');
            currentStatus = button.dataset.themeStatus || 'all';
            applyFilters();
        });
    });

    [typeFilter, categoryFilter, colorFilter].forEach(select => {
        if (!select) return;
        select.addEventListener('change', applyFilters);
    });

    let searchTimer = null;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            if (searchTimer) {
                window.clearTimeout(searchTimer);
            }
            searchTimer = window.setTimeout(applyFilters, 150);
        });
    }

    applyFilters();
})();
