/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const registry = window.FlatCMSWidgetPreviews && typeof window.FlatCMSWidgetPreviews === 'object'
        ? window.FlatCMSWidgetPreviews
        : (window.FlatCMSWidgetPreviews = {});

    registry.divider = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const previewId = `pb-divider-preview-${Math.random().toString(36).slice(2, 10)}`;

        const normalizeColor = (value) => {
            const safe = String(value || '').trim();
            if (/^#[0-9a-f]{3,8}$/i.test(safe) || /^rgb(a)?\([^)]+\)$/i.test(safe)) {
                return safe;
            }
            return '#d1d5db';
        };

        const normalizeWeight = (value) => {
            const num = Math.trunc(Number(value));
            if (!Number.isFinite(num)) {
                return 1;
            }
            return Math.max(1, Math.min(8, num));
        };

        const normalizeStyle = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['solid', 'dashed', 'dotted'].includes(safe) ? safe : 'solid';
        };

        const normalizeLength = (value) => {
            const num = Math.trunc(Number(value));
            if (!Number.isFinite(num)) {
                return 100;
            }
            const clamped = Math.max(10, Math.min(100, num));
            return Math.max(10, Math.min(100, Math.round(clamped / 5) * 5));
        };

        const normalizeAlign = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['left', 'center', 'right'].includes(safe) ? safe : 'center';
        };

        const normalizeToggle = (value, fallback = false) => {
            if (typeof value === 'boolean') {
                return value;
            }
            const safe = String(value || '').trim().toLowerCase();
            if (['1', 'true', 'on', 'yes'].includes(safe)) {
                return true;
            }
            if (['0', 'false', 'off', 'no', ''].includes(safe)) {
                return false;
            }
            return fallback;
        };

        const normalizeBorderStyle = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', 'none', 'solid', 'dashed', 'dotted'].includes(safe) ? safe : 'inherit';
        };

        const normalizeShadowPreset = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', 'none', 'soft', 'medium', 'strong'].includes(safe) ? safe : 'inherit';
        };

        const normalizeDesignInt = (value, fallback, min, max) => {
            const number = Math.trunc(Number(value));
            const safe = Number.isFinite(number) ? number : fallback;
            return Math.max(min, Math.min(max, safe));
        };

        const resolveShadowValue = (preset) => {
            if (preset === 'none') return 'none';
            if (preset === 'soft') return '0 12px 34px rgba(15,23,42,.10)';
            if (preset === 'medium') return '0 18px 48px rgba(15,23,42,.16)';
            if (preset === 'strong') return '0 26px 70px rgba(15,23,42,.24)';
            return '';
        };

        const resolveDesign = (source) => ({
            useCustom: normalizeToggle(source.useCustomDesign || '', false),
            surfaceColor: normalizeColor(source.designSurfaceColor || ''),
            lineColor: normalizeColor(source.designTextColor || ''),
            borderStyle: normalizeBorderStyle(source.designBorderStyle || 'inherit'),
            borderWidth: normalizeDesignInt(source.designBorderWidth, 1, 0, 8),
            borderColor: normalizeColor(source.designBorderColor || ''),
            radius: normalizeDesignInt(source.designRadius, 0, 0, 48),
            shadowPreset: normalizeShadowPreset(source.designShadow || 'inherit'),
        });

        const applyDesign = (root, design) => {
            if (!(root instanceof HTMLElement)) {
                return;
            }
            const line = root.querySelector('.pb-divider-line');
            if (!design.useCustom) {
                root.style.background = '';
                root.style.borderStyle = '';
                root.style.borderWidth = '';
                root.style.borderColor = '';
                root.style.borderRadius = '';
                root.style.boxShadow = '';
                if (line instanceof HTMLElement) {
                    line.style.borderTopColor = '';
                }
                return;
            }
            root.style.borderRadius = `${design.radius}px`;
            root.style.background = design.surfaceColor || '';
            if (design.borderStyle !== 'inherit') {
                root.style.borderStyle = design.borderStyle;
                root.style.borderWidth = `${design.borderWidth}px`;
            } else {
                root.style.borderStyle = '';
                root.style.borderWidth = '';
            }
            if (design.borderColor) {
                root.style.borderColor = design.borderColor;
                if (design.borderStyle === 'inherit') {
                    root.style.borderWidth = `${design.borderWidth}px`;
                }
            } else {
                root.style.borderColor = '';
            }
            root.style.boxShadow = resolveShadowValue(design.shadowPreset);
            if (line instanceof HTMLElement && design.lineColor) {
                line.style.borderTopColor = design.lineColor;
            }
        };

        const scheduleDesignSync = (design, attempts = 4) => {
            if (typeof window.requestAnimationFrame !== 'function') {
                return;
            }
            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-divider-preview-id="${previewId}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        scheduleDesignSync(design, attempts - 1);
                    }
                    return;
                }
                applyDesign(root, design);
            });
        };

        scheduleDesignSync(resolveDesign(settings));

        return `
            <div class="pb-divider" data-divider-preview-id="${escapeAttr(previewId)}" data-divider-mode="${escapeAttr(normalizeStyle(settings.style))}" data-divider-weight="${escapeAttr(String(normalizeWeight(settings.weight)))}" data-divider-color="${escapeAttr(normalizeColor(settings.color))}" data-divider-length="${escapeAttr(String(normalizeLength(settings.length)))}" data-divider-align="${escapeAttr(normalizeAlign(settings.align))}">
                <span class="pb-divider-line" aria-hidden="true"></span>
            </div>
        `;
    };
})();
