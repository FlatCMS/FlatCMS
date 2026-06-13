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

    registry.newsletter = function(settings) {
        const safeSettings = settings && typeof settings === 'object' ? settings : {};
        const previewId = `pb-newsletter-preview-${Math.random().toString(36).slice(2, 10)}`;

        const escapeHtml = (value) => String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        const escapeAttr = (value) => escapeHtml(value);

        const normalizeAlign = (value, fallback = 'left') => {
            const safe = String(value || '').trim().toLowerCase();
            if (['left', 'center', 'right'].includes(safe)) {
                return safe;
            }
            const safeFallback = String(fallback || 'left').trim().toLowerCase();
            return ['left', 'center', 'right'].includes(safeFallback) ? safeFallback : 'left';
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

        const normalizeInt = (value, fallback, min, max) => {
            const number = Math.trunc(Number(value));
            const safe = Number.isFinite(number) ? number : fallback;
            return Math.max(min, Math.min(max, safe));
        };

        const normalizeBorderStyle = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', 'none', 'solid', 'dashed', 'dotted'].includes(safe) ? safe : 'inherit';
        };

        const normalizeShadowPreset = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', 'none', 'soft', 'medium', 'strong'].includes(safe) ? safe : 'inherit';
        };

        const resolveShadowValue = (preset) => {
            if (preset === 'none') return 'none';
            if (preset === 'soft') return '0 12px 34px rgba(15,23,42,.10)';
            if (preset === 'medium') return '0 18px 48px rgba(15,23,42,.16)';
            if (preset === 'strong') return '0 26px 70px rgba(15,23,42,.24)';
            return '';
        };

        const applyDesign = (root, design) => {
            if (!(root instanceof HTMLElement)) {
                return;
            }

            const shell = root.querySelector('.pb-newsletter-widget-shell');
            const input = root.querySelector('.pb-newsletter-widget-input');
            if (!(shell instanceof HTMLElement)) {
                return;
            }

            if (!design.useCustom) {
                shell.style.background = '';
                shell.style.color = '';
                shell.style.borderStyle = '';
                shell.style.borderWidth = '';
                shell.style.borderColor = '';
                shell.style.borderRadius = '';
                shell.style.boxShadow = '';
                shell.style.padding = '';
                if (input instanceof HTMLElement) {
                    input.style.color = '';
                }
                return;
            }

            shell.style.padding = '1.25rem';
            shell.style.borderRadius = `${design.radius}px`;
            shell.style.background = design.surfaceColor || '';
            shell.style.color = design.textColor || '';
            if (design.borderStyle !== 'inherit') {
                shell.style.borderStyle = design.borderStyle;
                shell.style.borderWidth = `${design.borderWidth}px`;
            } else {
                shell.style.borderStyle = '';
                shell.style.borderWidth = '';
            }
            if (design.borderColor) {
                shell.style.borderColor = design.borderColor;
                if (design.borderStyle === 'inherit') {
                    shell.style.borderWidth = `${design.borderWidth}px`;
                }
            } else {
                shell.style.borderColor = '';
            }
            shell.style.boxShadow = resolveShadowValue(design.shadowPreset);
            if (input instanceof HTMLElement) {
                input.style.color = design.textColor || '';
            }
        };

        const design = {
            useCustom: normalizeToggle(safeSettings.useCustomDesign || '', false),
            surfaceColor: normalizeColor(safeSettings.designSurfaceColor || ''),
            textColor: normalizeColor(safeSettings.designTextColor || ''),
            borderStyle: normalizeBorderStyle(safeSettings.designBorderStyle || 'inherit'),
            borderWidth: normalizeInt(safeSettings.designBorderWidth, 1, 0, 8),
            borderColor: normalizeColor(safeSettings.designBorderColor || ''),
            radius: normalizeInt(safeSettings.designRadius, 20, 0, 48),
            shadowPreset: normalizeShadowPreset(safeSettings.designShadow || 'inherit'),
        };

        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-newsletter-preview-id="${previewId}"]`);
                if (root instanceof HTMLElement) {
                    applyDesign(root, design);
                }
            });
        }

        const title = String(safeSettings.title || 'Newsletter').trim();
        const description = String(safeSettings.description || '').trim();
        const placeholder = String(safeSettings.placeholder || 'Votre adresse e-mail').trim();
        const buttonLabel = String(safeSettings.buttonLabel || 'S’inscrire').trim();
        const align = normalizeAlign(safeSettings.align, 'left');

        return `<section class="pb-newsletter-widget pb-newsletter-widget--align-${escapeAttr(align)}" data-newsletter-preview-id="${escapeAttr(previewId)}"><div class="pb-newsletter-widget-shell">${title ? `<strong class="pb-newsletter-widget-title">${escapeHtml(title)}</strong>` : ''}${description ? `<p class="pb-newsletter-widget-description">${escapeHtml(description)}</p>` : ''}<form class="pb-form pb-form-newsletter pb-newsletter-widget-form" action="#" method="post"><label class="pb-sr-only">${escapeHtml(placeholder)}</label><input type="email" class="form-input pb-input pb-newsletter-widget-input" placeholder="${escapeAttr(placeholder)}" disabled><button type="button" class="btn btn-primary pb-btn pb-btn-primary pb-newsletter-widget-button">${escapeHtml(buttonLabel)}</button></form></div></section>`;
    };
})();
