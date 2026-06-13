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
        var select = document.querySelector('[data-action="lang-select"]');
        if (!select) return;

        select.addEventListener('change', function () {
            var nameInput = document.getElementById('name');
            var customGroup = document.getElementById('customCodeGroup');
            var selectedOption = this.options[this.selectedIndex];
            if (!customGroup) return;

            if (this.value === 'custom') {
                customGroup.classList.remove('hidden');
                nameInput.value = '';
            } else {
                customGroup.classList.add('hidden');
                if (selectedOption.dataset.name) {
                    nameInput.value = selectedOption.dataset.name;
                }
            }
        });
    });
})();
