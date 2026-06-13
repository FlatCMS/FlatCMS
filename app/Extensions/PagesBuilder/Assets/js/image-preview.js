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

    registry.image = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const escapeHtml = helpers.escapeHtml || ((value) => String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;'));
        const resolveImage = helpers.resolveImage || ((value) => String(value || ''));
        const label = helpers.label || ((_key, fallback) => String(fallback || ''));
        const safeSettings = settings && typeof settings === 'object' ? settings : {};
        const src = resolveImage(String(safeSettings.src || ''));
        if (!src) {
            return `<div class="pb-empty-state pb-empty-state-lg">${escapeAttr(label('image_empty_state_no_image', ''))}</div>`;
        }

        const previewId = `pb-image-preview-${Math.random().toString(36).slice(2, 10)}`;

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

        const normalizeAlign = (value, fallback = 'center') => {
            const safe = String(value || '').trim().toLowerCase();
            if (['left', 'center', 'right'].includes(safe)) {
                return safe;
            }
            const safeFallback = String(fallback || 'center').trim().toLowerCase();
            return ['left', 'center', 'right'].includes(safeFallback) ? safeFallback : 'center';
        };

        const normalizePlacement = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['above', 'overlay', 'below'].includes(safe) ? safe : 'below';
        };

        const normalizeVerticalAlign = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['top', 'center', 'bottom'].includes(safe) ? safe : 'center';
        };

        const normalizeTarget = (value) => String(value || '').trim() === '_blank' ? '_blank' : '_self';

        const normalizeVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['primary', 'secondary', 'ghost'].includes(safe) ? safe : 'primary';
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

        const normalizeFont = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', 'system', 'sans', 'serif', 'mono', 'display'].includes(safe) ? safe : 'inherit';
        };

        const normalizeFontSize = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', '12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'].includes(safe) ? safe : 'inherit';
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

        const resolveShadowValue = (preset) => {
            if (preset === 'none') return 'none';
            if (preset === 'soft') return '0 12px 34px rgba(15,23,42,.10)';
            if (preset === 'medium') return '0 18px 48px rgba(15,23,42,.16)';
            if (preset === 'strong') return '0 26px 70px rgba(15,23,42,.24)';
            return '';
        };

        const resolveTextStyle = (source, prefix, fallbackAlign) => {
            const safePrefix = String(prefix || '').replace(/[^a-zA-Z0-9_]/g, '') || 'textStyle';
            const iconPosition = String(source[`${safePrefix}IconPosition`] || 'start').trim().toLowerCase();
            return {
                align: normalizeAlign(source[`${safePrefix}Align`], fallbackAlign),
                font: normalizeFont(source[`${safePrefix}Font`]),
                size: normalizeFontSize(source[`${safePrefix}Size`]),
                bold: normalizeToggle(source[`${safePrefix}Bold`]),
                italic: normalizeToggle(source[`${safePrefix}Italic`]),
                underline: normalizeToggle(source[`${safePrefix}Underline`]),
                color: normalizeColor(source[`${safePrefix}Color`]),
                icon: sanitizeIconClass(source[`${safePrefix}Icon`]),
                iconPosition: ['start', 'end'].includes(iconPosition) ? iconPosition : 'start',
            };
        };

        const injectIcon = (content, style) => {
            if (!style.icon) {
                return content;
            }
            const iconHtml = `<i class="${escapeAttr(style.icon)} pb-styled-text-icon pb-styled-text-icon-${escapeAttr(style.iconPosition)}" aria-hidden="true"></i>`;
            return style.iconPosition === 'end' ? `${content}${iconHtml}` : `${iconHtml}${content}`;
        };

        const renderStyledText = (text, tagName, className, style) => {
            const safeText = String(text || '').trim();
            if (!safeText) {
                return '';
            }
            const content = `<span class="pb-styled-text-content">${escapeHtml(safeText)}</span>`;
            const align = normalizeAlign(style.align, 'left');
            return `<${tagName} class="${escapeAttr(`${className} fc-image-block-text-${align}`)}">${injectIcon(content, style)}</${tagName}>`;
        };

        const renderStyledParagraphs = (text, className, style) => {
            const normalized = String(text || '').replace(/\r\n?/g, '\n').trim();
            if (!normalized) {
                return '';
            }

            const paragraphs = normalized
                .split(/\n\s*\n/g)
                .map((chunk) => String(chunk || '').trim())
                .filter(Boolean)
                .map((chunk) => `<p class="fc-image-block-body-paragraph"><span class="pb-styled-text-content">${escapeHtml(chunk).replace(/\n/g, '<br>')}</span></p>`);

            if (!paragraphs.length) {
                return '';
            }

            const content = `<div class="pb-styled-text-content-rich">${paragraphs.join('')}</div>`;
            const align = normalizeAlign(style.align, 'left');
            return `<div class="${escapeAttr(`${className} fc-image-block-text-${align}`)}">${injectIcon(content, style)}</div>`;
        };

        const renderButton = (buttonLabel, buttonUrl, buttonTarget, buttonVariant) => {
            const safeLabel = String(buttonLabel || '').trim();
            if (!safeLabel) {
                return '';
            }

            const variantClass = buttonVariant === 'secondary'
                ? 'btn btn-secondary pb-btn pb-btn-secondary'
                : (buttonVariant === 'ghost' ? 'btn btn-ghost pb-btn pb-btn-ghost' : 'btn btn-primary pb-btn pb-btn-primary');

            if (buttonUrl) {
                const rel = buttonTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                return `<a class="${escapeAttr(variantClass)}" href="${escapeAttr(buttonUrl)}" target="${escapeAttr(buttonTarget)}"${rel}>${escapeHtml(safeLabel)}</a>`;
            }

            return `<span class="${escapeAttr(variantClass)} is-static" aria-disabled="true">${escapeHtml(safeLabel)}</span>`;
        };

        const overlayActionClass = (buttonAlign, buttonVerticalAlign) => `fc-image-block-overlay-h-${normalizeAlign(buttonAlign, 'center')} fc-image-block-overlay-v-${normalizeVerticalAlign(buttonVerticalAlign)}`;

        const renderSlot = (slot, textPlacement, copyHtml, buttonPlacement, buttonHtml) => {
            const parts = [];
            if (textPlacement === slot && copyHtml) {
                parts.push(`<div class="fc-image-block-slot-copy fc-image-block-slot-copy-${escapeAttr(slot)}">${copyHtml}</div>`);
            }
            if (buttonPlacement === slot && buttonHtml) {
                parts.push(`<div class="fc-image-block-slot-actions fc-image-block-slot-actions-${escapeAttr(slot)}"><div class="fc-image-block-actions">${buttonHtml}</div></div>`);
            }
            return parts.length ? `<div class="fc-image-block-slot fc-image-block-slot-${escapeAttr(slot)}">${parts.join('')}</div>` : '';
        };

        const renderOverlay = (textPlacement, copyHtml, buttonPlacement, buttonHtml) => {
            const hasOverlayCopy = textPlacement === 'overlay' && !!copyHtml;
            const hasOverlayButton = buttonPlacement === 'overlay' && !!buttonHtml;
            const actionClass = overlayActionClass(buttonAlign, buttonVerticalAlign);

            if (hasOverlayCopy && hasOverlayButton) {
                return `<div class="fc-image-block-overlay fc-image-block-overlay-combined"><div class="fc-image-block-overlay-layer fc-image-block-overlay-layer-copy"><div class="fc-image-block-overlay-inner fc-image-block-overlay-inner-copy">${copyHtml}</div></div><div class="fc-image-block-overlay-layer fc-image-block-overlay-layer-actions ${escapeAttr(actionClass)}"><div class="fc-image-block-overlay-inner fc-image-block-overlay-inner-actions"><div class="fc-image-block-actions">${buttonHtml}</div></div></div></div>`;
            }

            const parts = [];
            if (hasOverlayCopy) {
                parts.push(`<div class="fc-image-block-overlay fc-image-block-overlay-copy"><div class="fc-image-block-overlay-inner fc-image-block-overlay-inner-copy">${copyHtml}</div></div>`);
            }
            if (hasOverlayButton) {
                parts.push(`<div class="fc-image-block-overlay fc-image-block-overlay-actions ${escapeAttr(actionClass)}"><div class="fc-image-block-overlay-inner fc-image-block-overlay-inner-actions"><div class="fc-image-block-actions">${buttonHtml}</div></div></div>`);
            }

            return parts.join('');
        };

        const applyTextStyle = (elements, style) => {
            const align = normalizeAlign(style.align, 'left');
            const justify = align === 'center' ? 'center' : (align === 'right' ? 'flex-end' : 'flex-start');
            Array.from(elements || []).forEach((element) => {
                if (!(element instanceof HTMLElement)) {
                    return;
                }
                element.style.textAlign = align;
                element.style.justifyContent = justify;
                element.style.fontFamily = getFontFamily(style.font) || '';
                element.style.fontSize = style.size !== 'inherit' ? style.size : '';
                if (style.color) {
                    element.style.color = style.color;
                }

                element.querySelectorAll('.pb-styled-text-content').forEach((node) => {
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    node.style.fontWeight = style.bold ? '700' : '';
                    node.style.fontStyle = style.italic ? 'italic' : '';
                    node.style.textDecoration = style.underline ? 'underline' : '';
                    if (style.color) {
                        node.style.color = style.color;
                    }
                });

                element.querySelectorAll('.pb-styled-text-icon').forEach((node) => {
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    if (style.color) {
                        node.style.color = style.color;
                    }
                });
            });
        };

        const applyDesign = (node, design) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }
            const media = node.querySelector('.fc-image-block-media');
            const image = node.querySelector('.fc-image-block-img');
            if (!(media instanceof HTMLElement) || !(image instanceof HTMLElement)) {
                return;
            }

            if (!design.useCustom) {
                media.style.background = '';
                media.style.borderStyle = '';
                media.style.borderWidth = '';
                media.style.borderColor = '';
                media.style.borderRadius = '';
                media.style.boxShadow = '';
                media.style.overflow = '';
                image.style.borderRadius = '';
                const copy = node.querySelector('.fc-image-block-copy');
                if (copy instanceof HTMLElement) {
                    copy.style.color = '';
                }
                return;
            }

            media.style.overflow = 'hidden';
            media.style.borderRadius = `${design.radius}px`;
            media.style.background = design.surfaceColor || '';
            if (design.borderStyle !== 'inherit') {
                media.style.borderStyle = design.borderStyle;
                media.style.borderWidth = `${design.borderWidth}px`;
            } else {
                media.style.borderStyle = '';
                media.style.borderWidth = '';
            }
            if (design.borderColor) {
                media.style.borderColor = design.borderColor;
                if (design.borderStyle === 'inherit') {
                    media.style.borderWidth = `${design.borderWidth}px`;
                }
            } else {
                media.style.borderColor = '';
            }
            media.style.boxShadow = resolveShadowValue(design.shadowPreset);
            image.style.borderRadius = 'inherit';
            const copy = node.querySelector('.fc-image-block-copy');
            if (copy instanceof HTMLElement) {
                copy.style.color = design.textColor || '';
            }
        };

        const scheduleSync = (state, attempts = 4) => {
            if (typeof window.requestAnimationFrame !== 'function') {
                return;
            }
            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-image-preview-id="${previewId}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        scheduleSync(state, attempts - 1);
                    }
                    return;
                }

                const shell = root.querySelector('.fc-image-block-shell');
                if (shell instanceof HTMLElement) {
                    shell.style.width = `${state.widthPercent}%`;
                }

                const horizontal = state.buttonAlign === 'left' ? 'flex-start' : (state.buttonAlign === 'right' ? 'flex-end' : 'center');
                const topActions = root.querySelector('.fc-image-block-slot-actions-above');
                const bottomActions = root.querySelector('.fc-image-block-slot-actions-below');
                if (topActions instanceof HTMLElement) {
                    topActions.style.justifyContent = horizontal;
                }
                if (bottomActions instanceof HTMLElement) {
                    bottomActions.style.justifyContent = horizontal;
                }

                const overlayActions = root.querySelector('.fc-image-block-overlay-actions');
                if (overlayActions instanceof HTMLElement) {
                    overlayActions.style.flexDirection = 'column';
                    overlayActions.style.alignItems = 'stretch';
                    overlayActions.style.justifyContent = state.buttonVerticalAlign === 'top'
                        ? 'flex-start'
                        : (state.buttonVerticalAlign === 'bottom' ? 'flex-end' : 'center');
                    const overlayActionsRow = overlayActions.querySelector('.fc-image-block-actions');
                    if (overlayActionsRow instanceof HTMLElement) {
                        overlayActionsRow.style.justifyContent = horizontal;
                    }
                }

                const overlayCombinedActions = root.querySelector('.fc-image-block-overlay-layer-actions');
                if (overlayCombinedActions instanceof HTMLElement) {
                    overlayCombinedActions.style.flexDirection = 'column';
                    overlayCombinedActions.style.alignItems = 'stretch';
                    overlayCombinedActions.style.justifyContent = state.buttonVerticalAlign === 'top'
                        ? 'flex-start'
                        : (state.buttonVerticalAlign === 'bottom' ? 'flex-end' : 'center');
                    const overlayCombinedRow = overlayCombinedActions.querySelector('.fc-image-block-actions');
                    if (overlayCombinedRow instanceof HTMLElement) {
                        overlayCombinedRow.style.justifyContent = horizontal;
                    }
                }

                applyTextStyle(root.querySelectorAll('.fc-image-block-title'), state.titleStyle);
                applyTextStyle(root.querySelectorAll('.fc-image-block-body'), state.bodyStyle);
                applyDesign(root, state.design);
            });
        };

        const align = normalizeAlign(safeSettings.align, 'center');
        const altText = String(safeSettings.altText || '');
        const widthPercent = Math.max(10, Math.min(100, Number.parseInt(String(safeSettings.widthPercent || '100'), 10) || 100));
        const textPlacement = normalizePlacement(safeSettings.textPlacement);
        const title = String(safeSettings.title || '');
        const text = String(safeSettings.text || '');
        const titleStyle = resolveTextStyle(safeSettings, 'titleStyle', align);
        const bodyStyle = resolveTextStyle(safeSettings, 'bodyStyle', titleStyle.align);
        const titleHtml = renderStyledText(title, 'h3', 'fc-image-block-title', titleStyle);
        const textHtml = renderStyledParagraphs(text, 'fc-image-block-body', bodyStyle);
        const copyHtml = titleHtml || textHtml ? `<div class="fc-image-block-copy">${titleHtml}${textHtml}</div>` : '';
        const showButton = normalizeToggle(safeSettings.showButton, false);
        const buttonLabel = showButton ? String(safeSettings.buttonLabel || '') : '';
        const buttonTarget = normalizeTarget(safeSettings.buttonTarget);
        const buttonVariant = normalizeVariant(safeSettings.buttonVariant);
        const buttonPlacement = normalizePlacement(safeSettings.buttonPlacement);
        const buttonAlign = normalizeAlign(safeSettings.buttonAlign, 'center');
        const buttonVerticalAlign = normalizeVerticalAlign(safeSettings.buttonVerticalAlign);
        const buttonHtml = renderButton(buttonLabel, String(safeSettings.buttonUrl || ''), buttonTarget, buttonVariant);
        const design = {
            useCustom: normalizeToggle(safeSettings.useCustomDesign || '', false),
            surfaceColor: normalizeColor(safeSettings.designSurfaceColor || ''),
            textColor: normalizeColor(safeSettings.designTextColor || ''),
            borderStyle: normalizeBorderStyle(safeSettings.designBorderStyle || 'inherit'),
            borderWidth: normalizeDesignInt(safeSettings.designBorderWidth, 1, 0, 8),
            borderColor: normalizeColor(safeSettings.designBorderColor || ''),
            radius: normalizeDesignInt(safeSettings.designRadius, 12, 0, 48),
            shadowPreset: normalizeShadowPreset(safeSettings.designShadow || 'inherit'),
        };

        scheduleSync({
            widthPercent,
            buttonAlign,
            buttonVerticalAlign,
            titleStyle,
            bodyStyle,
            design,
        });

        return `<section class="fc-image-block fc-image-block-align-${escapeAttr(align)}" data-image-preview-id="${escapeAttr(previewId)}"><div class="fc-image-block-shell">${renderSlot('above', textPlacement, copyHtml, buttonPlacement, buttonHtml)}<div class="fc-image-block-media"><img src="${escapeAttr(src)}" alt="${escapeAttr(altText)}" class="fc-image-block-img" data-media-width="${widthPercent}" loading="lazy" decoding="async">${renderOverlay(textPlacement, copyHtml, buttonPlacement, buttonHtml)}</div>${renderSlot('below', textPlacement, copyHtml, buttonPlacement, buttonHtml)}</div></section>`;
    };
})();
