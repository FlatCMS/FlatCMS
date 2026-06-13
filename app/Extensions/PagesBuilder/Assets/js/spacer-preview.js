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

    registry.spacer = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const previewId = `pb-spacer-preview-${Math.random().toString(36).slice(2, 10)}`;

        const normalizeHeight = (value) => {
            const number = Math.trunc(Number(value));
            const safe = Number.isFinite(number) ? number : 32;
            return Math.max(8, Math.min(240, safe));
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

        const normalizeColor = (value) => {
            const safe = String(value || '').trim();
            if (!safe) {
                return '';
            }
            return /^#[0-9a-f]{3,8}$/i.test(safe) || /^rgb(a)?\([^)]+\)$/i.test(safe) ? safe : '';
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
            borderStyle: normalizeBorderStyle(source.designBorderStyle || 'inherit'),
            borderWidth: normalizeDesignInt(source.designBorderWidth, 1, 0, 8),
            borderColor: normalizeColor(source.designBorderColor || ''),
            radius: normalizeDesignInt(source.designRadius, 0, 0, 48),
            shadowPreset: normalizeShadowPreset(source.designShadow || 'inherit'),
        });

        const applyDesign = (node, design) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }
            if (!design.useCustom) {
                node.style.background = '';
                node.style.borderStyle = '';
                node.style.borderWidth = '';
                node.style.borderColor = '';
                node.style.borderRadius = '';
                node.style.boxShadow = '';
                return;
            }
            node.style.borderRadius = `${design.radius}px`;
            node.style.background = design.surfaceColor || '';
            if (design.borderStyle !== 'inherit') {
                node.style.borderStyle = design.borderStyle;
                node.style.borderWidth = `${design.borderWidth}px`;
            } else {
                node.style.borderStyle = '';
                node.style.borderWidth = '';
            }
            if (design.borderColor) {
                node.style.borderColor = design.borderColor;
                if (design.borderStyle === 'inherit') {
                    node.style.borderWidth = `${design.borderWidth}px`;
                }
            } else {
                node.style.borderColor = '';
            }
            node.style.boxShadow = resolveShadowValue(design.shadowPreset);
        };

        const scheduleDesignSync = (design, attempts = 4) => {
            if (typeof window.requestAnimationFrame !== 'function') {
                return;
            }
            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-spacer-preview-id="${previewId}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        scheduleDesignSync(design, attempts - 1);
                    }
                    return;
                }
                applyDesign(root, design);
            });
        };

        const height = normalizeHeight(settings.height);
        scheduleDesignSync(resolveDesign(settings));

        return `<div class="pb-spacer" data-spacer-preview-id="${escapeAttr(previewId)}" data-spacer-height="${escapeAttr(String(height))}" aria-hidden="true"></div>`;
    };
})();
