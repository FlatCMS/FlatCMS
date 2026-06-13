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

    registry.button = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;'));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const labelHelper = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `pb-button-preview-${Math.random().toString(36).slice(2, 10)}`;

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

        const normalizeAlign = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['left', 'center', 'right'].includes(safe) ? safe : 'left';
        };

        const normalizeVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['primary', 'secondary', 'ghost'].includes(safe) ? safe : 'primary';
        };

        const normalizeTarget = (value) => {
            const safe = String(value || '').trim();
            return ['_self', '_blank'].includes(safe) ? safe : '_self';
        };

        const normalizeIconPosition = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['left', 'right'].includes(safe) ? safe : 'left';
        };

        const normalizeBorderStyle = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', 'none', 'solid', 'dashed', 'dotted'].includes(safe) ? safe : 'inherit';
        };

        const normalizeShadowPreset = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', 'none', 'soft', 'medium', 'strong'].includes(safe) ? safe : 'inherit';
        };

        const normalizeFont = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', 'system', 'sans', 'serif', 'mono', 'display'].includes(safe) ? safe : 'inherit';
        };

        const normalizeFontSize = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', '12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'].includes(safe) ? safe : 'inherit';
        };

        const resolveShadowValue = (preset) => {
            if (preset === 'none') return 'none';
            if (preset === 'soft') return '0 12px 34px rgba(15,23,42,.10)';
            if (preset === 'medium') return '0 18px 48px rgba(15,23,42,.16)';
            if (preset === 'strong') return '0 26px 70px rgba(15,23,42,.24)';
            return '';
        };

        const sanitizeUrl = (value) => {
            const safe = String(value || '').trim();
            if (!safe) {
                return '#';
            }
            if (safe[0] === '#' || safe[0] === '/' || safe[0] === '?') {
                return safe;
            }
            return /^(https?:|mailto:|tel:)/i.test(safe) ? safe : '#';
        };

        const sanitizeIconClass = (value) => String(value || '')
            .trim()
            .split(/\s+/)
            .filter((token) => /^[a-z0-9_-]+$/i.test(token))
            .join(' ');

        const getFontFamily = (value) => {
            const safe = normalizeFont(value);
            if (safe === 'system') return 'var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif)';
            if (safe === 'sans') return '"Cabin", var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif)';
            if (safe === 'serif') return 'Georgia, "Times New Roman", Times, serif';
            if (safe === 'mono') return '"SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace';
            if (safe === 'display') return '"Cabin", var(--font-family-heading, var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif))';
            return '';
        };

        const resolveTextStyle = (source, prefix) => {
            const safePrefix = String(prefix || '').replace(/[^a-zA-Z0-9_]/g, '') || 'textStyle';
            return {
                font: normalizeFont(source[`${safePrefix}Font`]),
                size: normalizeFontSize(source[`${safePrefix}Size`]),
                bold: normalizeToggle(source[`${safePrefix}Bold`]),
                italic: normalizeToggle(source[`${safePrefix}Italic`]),
                underline: normalizeToggle(source[`${safePrefix}Underline`]),
                color: normalizeColor(source[`${safePrefix}Color`]),
            };
        };

        const resolveDesign = (source) => ({
            useCustom: normalizeToggle(source.useCustomDesign || '', false),
            surfaceColor: normalizeColor(source.designSurfaceColor || ''),
            textColor: normalizeColor(source.designTextColor || ''),
            borderStyle: normalizeBorderStyle(source.designBorderStyle || 'inherit'),
            borderWidth: normalizeInt(source.designBorderWidth, 1, 0, 8),
            borderColor: normalizeColor(source.designBorderColor || ''),
            radius: normalizeInt(source.designRadius, 12, 0, 48),
            shadowPreset: normalizeShadowPreset(source.designShadow || 'inherit'),
        });

        const applyTextStyle = (node, style) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }
            node.style.fontFamily = getFontFamily(style.font) || '';
            node.style.fontSize = style.size !== 'inherit' ? style.size : '';
            if (style.color) {
                node.style.color = style.color;
            }
            const labelNode = node.querySelector('.fc-widget-button__label');
            if (labelNode instanceof HTMLElement) {
                labelNode.style.fontWeight = style.bold ? '700' : '';
                labelNode.style.fontStyle = style.italic ? 'italic' : '';
                labelNode.style.textDecoration = style.underline ? 'underline' : '';
                if (style.color) {
                    labelNode.style.color = style.color;
                }
            }
            const iconNode = node.querySelector('.fc-widget-button__icon');
            if (iconNode instanceof HTMLElement && style.color) {
                iconNode.style.color = style.color;
            }
        };

        const applyDesign = (node, design) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }
            if (!design.useCustom) {
                node.style.background = '';
                node.style.color = '';
                node.style.borderStyle = '';
                node.style.borderWidth = '';
                node.style.borderColor = '';
                node.style.borderRadius = '';
                node.style.boxShadow = '';
                return;
            }
            node.style.borderRadius = `${design.radius}px`;
            node.style.background = design.surfaceColor || '';
            node.style.color = design.textColor || '';
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

        const scheduleSync = (design, textStyle, attempts = 4) => {
            if (typeof window.requestAnimationFrame !== 'function') {
                return;
            }
            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-button-preview-id="${previewId}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        scheduleSync(design, textStyle, attempts - 1);
                    }
                    return;
                }
                const control = root.querySelector('.fc-widget-button__control');
                applyDesign(control, design);
                applyTextStyle(control, textStyle);
            });
        };

        const showButton = normalizeToggle(settings.showButton || 'on', true);
        if (!showButton) {
            return '';
        }

        const label = escapeHtml(String(settings.label || labelHelper('footer_widget_button_default_label', 'Découvrir')));
        const url = sanitizeUrl(settings.url || '#');
        const target = normalizeTarget(settings.target || '_self');
        const targetRel = target === '_blank' ? ' rel="noopener noreferrer"' : '';
        const align = normalizeAlign(settings.align || 'left');
        const variant = normalizeVariant(settings.variant || 'primary');
        const iconClass = sanitizeIconClass(settings.icon || '');
        const iconPosition = normalizeIconPosition(settings.iconPosition || 'left');
        const iconHtml = iconClass
            ? `<i class="${escapeAttr(iconClass)} fc-widget-button__icon" aria-hidden="true"></i>`
            : '';
        const contentHtml = iconHtml
            ? (iconPosition === 'right'
                ? `<span class="fc-widget-button__label">${label}</span>${iconHtml}`
                : `${iconHtml}<span class="fc-widget-button__label">${label}</span>`)
            : `<span class="fc-widget-button__label">${label}</span>`;
        const buttonClasses = [
            'fc-widget-button__control',
            'btn',
            `btn-${variant}`,
            'pb-btn',
            `pb-btn-${variant}`,
            `fc-widget-button__control--${variant}`,
        ].join(' ');

        scheduleSync(resolveDesign(settings), resolveTextStyle(settings, 'labelStyle'));

        return `<div class="fc-widget-button fc-widget-button--align-${escapeAttr(align)}" data-button-preview-id="${escapeAttr(previewId)}"><a href="${escapeAttr(url)}" target="${escapeAttr(target)}"${targetRel} class="${escapeAttr(buttonClasses)}">${contentHtml}</a></div>`;
    };
})();
