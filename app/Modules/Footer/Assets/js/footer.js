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
        const root = document.querySelector('[data-footer-translations-root]');
        if (!root) {
            return;
        }

        const buttons = Array.from(root.querySelectorAll('[data-footer-tab-btn]'));
        const panels = Array.from(root.querySelectorAll('[data-footer-panel]'));
        const activeLocaleInput = root.querySelector('[data-footer-active-locale]');

        if (!buttons.length || !panels.length || !activeLocaleInput) {
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
                const isActive = String(panel.getAttribute('data-footer-panel') || '') === locale;
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
    });
})();
