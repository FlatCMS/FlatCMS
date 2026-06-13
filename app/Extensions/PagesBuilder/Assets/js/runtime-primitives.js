/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function () {
    function clampNumber(value, min, max, fallback) {
        const next = Number(value);
        if (!Number.isFinite(next)) {
            return fallback;
        }
        if (next < min) {
            return min;
        }
        if (next > max) {
            return max;
        }
        return next;
    }

    function normalizeColor(value, fallback) {
        const safe = String(value || '').trim();
        return safe !== '' ? safe : fallback;
    }

    function applyPrimitiveRuntime(root) {
        const scope = root || document;

        scope.querySelectorAll('[data-fc-spacer-height]').forEach((el) => {
            const height = clampNumber(el.getAttribute('data-fc-spacer-height'), 8, 240, 32);
            el.style.height = `${height}px`;
        });

        scope.querySelectorAll('.fc-divider-block').forEach((el) => {
            const line = el.querySelector('.fc-divider-block-line');
            if (!(line instanceof HTMLElement)) {
                return;
            }

            const weight = clampNumber(el.getAttribute('data-fc-divider-weight'), 1, 8, 1);
            const color = normalizeColor(el.getAttribute('data-fc-divider-color'), '#d1d5db');
            line.style.borderTopWidth = `${weight}px`;
            line.style.borderTopStyle = 'solid';
            line.style.borderTopColor = color;
        });

        scope.querySelectorAll('[data-fc-image-width]').forEach((el) => {
            const media = el.querySelector('.fc-image-block-media');
            if (!(media instanceof HTMLElement)) {
                return;
            }

            const width = clampNumber(el.getAttribute('data-fc-image-width'), 10, 100, 100);
            media.style.maxWidth = `${width}%`;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => applyPrimitiveRuntime(document));
    } else {
        applyPrimitiveRuntime(document);
    }
})();
