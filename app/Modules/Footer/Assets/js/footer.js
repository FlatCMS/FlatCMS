/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Footer/Assets/js/footer.js
 * Version: 2.0.0-dev
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const root = document.querySelector('[data-footer-translations-root]');
        if (!root) {
            return;
        }

        const buttons = Array.from(root.querySelectorAll('[data-footer-tab-btn]'));
        const panels = Array.from(root.querySelectorAll('[data-footer-panel]'));
        const activeLocaleInput = root.querySelector('[data-footer-active-locale]');
        const translationTabs = window.FlatCMS && window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.translationTabs;

        if (!buttons.length || !panels.length || !activeLocaleInput
            || !translationTabs || typeof translationTabs.attach !== 'function') {
            return;
        }

        function updateBadgeLabels(activeButton) {
            if (!activeButton) {
                return;
            }

            const sourceLabel = String(activeButton.getAttribute('data-footer-label-source') || '').trim();
            const readyLabel = String(activeButton.getAttribute('data-footer-label-ready') || '').trim();
            const missingLabel = String(activeButton.getAttribute('data-footer-label-missing') || '').trim();

            buttons.forEach(function(button) {
                const badge = button.querySelector('.footer-translation-tab-badge');
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

        function handleLocaleChange(locale, activeButton) {
            updateBadgeLabels(activeButton);
        }

        translationTabs.attach({
            root: root,
            tabSelector: '[data-footer-tab-btn]',
            panelSelector: '[data-footer-panel]',
            tabAttribute: 'data-tab',
            panelAttribute: 'data-footer-panel',
            activeInput: activeLocaleInput,
            initialValue: String(activeLocaleInput.value || buttons[0].getAttribute('data-tab') || ''),
            onChange: handleLocaleChange
        });
    });
})();
