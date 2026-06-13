/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    function setLegendColors() {
        var dots = document.querySelectorAll('.legend-dot[data-color]');
        dots.forEach(function(dot) {
            var color = dot.dataset.color;
            if (color) {
                dot.style.backgroundColor = color;
            }
        });
    }

    function setProgressWidths() {
        var fills = document.querySelectorAll('.disk-fill[data-progress]');
        fills.forEach(function(fill) {
            var val = parseInt(fill.dataset.progress, 10);
            if (!isNaN(val)) {
                fill.style.width = Math.min(val, 100) + '%';
            }
        });
    }

    function initMaintenanceToggle() {
        var banner = document.getElementById('maintenance-banner');
        var toggle = document.getElementById('maintenance-toggle');
        if (!banner || !toggle) return;

        var badge = document.getElementById('maintenance-badge');
        var url = banner.dataset.toggleUrl;
        var labelOn = banner.dataset.labelOn || '';
        var labelOff = banner.dataset.labelOff || '';

        var getCsrf = function() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.content) return meta.content;
            var input = document.querySelector('input[name="_token"]');
            return input ? input.value : '';
        };

        var updateUi = function(isOn) {
            banner.classList.toggle('is-on', isOn);
            banner.classList.toggle('is-off', !isOn);
            if (badge) {
                badge.textContent = isOn ? labelOn : labelOff;
            }
            toggle.checked = !!isOn;
        };

        toggle.addEventListener('change', function() {
            if (!url) return;
            toggle.disabled = true;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: '_token=' + encodeURIComponent(getCsrf())
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || !data.success) return;
                updateUi(!!data.maintenance_mode);
            })
            .catch(function() {
                toggle.checked = !toggle.checked;
            })
            .finally(function() {
                toggle.disabled = false;
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        setLegendColors();
        setProgressWidths();
        initMaintenanceToggle();
    });
})();
