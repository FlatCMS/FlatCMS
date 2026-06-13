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

    registry.feature_grid = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || ''));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const sanitizeRichText = helpers.sanitizeRichText || ((value) => String(value || ''));
        const labelHelper = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `fc-feature-grid-preview-${Math.random().toString(36).slice(2, 10)}`;

        const parseRepeaterLines = (raw) => {
            if (typeof raw !== 'string' || raw === '') return [];
            const items = raw
                .split(/\r\n|\r|\n/)
                .map((item) => String(item || '').trim());
            while (items.length && String(items[items.length - 1] || '').trim() === '') {
                items.pop();
            }
            return items;
        };

        const parseFeatureGridTextValues = (raw) => {
            const value = String(raw === null || raw === undefined ? '' : raw).trim();
            if (value === '') {
                return [];
            }
            if (value.startsWith('[')) {
                try {
                    const parsed = JSON.parse(value);
                    if (Array.isArray(parsed)) {
                        return parsed.map((item) => String(item === null || item === undefined ? '' : item).trim());
                    }
                } catch (error) {
                    // Keep legacy newline fallback.
                }
            }
            return parseRepeaterLines(value);
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

        const normalizeAlign = (value, fallback = 'left') => {
            const safe = String(value || '').trim().toLowerCase();
            if (['left', 'center', 'right'].includes(safe)) {
                return safe;
            }
            const safeFallback = String(fallback || '').trim().toLowerCase();
            return ['left', 'center', 'right'].includes(safeFallback) ? safeFallback : 'left';
        };

        const normalizeButtonVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['primary', 'secondary'].includes(safe) ? safe : 'ghost';
        };

        const normalizeGridVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            if (['subtle', 'strong', 'dashed'].includes(safe)) {
                return safe;
            }
            if (safe === 'outline') {
                return 'strong';
            }
            if (safe === 'soft') {
                return 'dashed';
            }
            return 'subtle';
        };

        const normalizeTarget = (value, fallback = '_self') => {
            const safe = String(value || '').trim();
            return ['_self', '_blank'].includes(safe) ? safe : fallback;
        };

        const normalizeColor = (value) => {
            const safe = String(value || '').trim();
            if (!safe) {
                return '';
            }
            if (/^#[0-9a-f]{3,8}$/i.test(safe) || /^rgb(a)?\([^)]+\)$/i.test(safe)) {
                return safe;
            }
            return '';
        };

        const normalizeClampedInt = (value, fallback, min, max) => {
            const parsed = Math.trunc(Number(value));
            if (!Number.isFinite(parsed)) {
                return fallback;
            }
            return Math.max(min, Math.min(max, parsed));
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
            if (preset === 'soft') return '0 12px 24px rgba(15, 23, 42, 0.12)';
            if (preset === 'medium') return '0 18px 36px rgba(15, 23, 42, 0.16)';
            if (preset === 'strong') return '0 24px 48px rgba(15, 23, 42, 0.22)';
            return '';
        };

        const normalizeTextStyleFont = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', 'system', 'sans', 'serif', 'mono', 'display'].includes(safe) ? safe : 'inherit';
        };

        const normalizeTextStyleSize = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', '12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'].includes(safe) ? safe : 'inherit';
        };

        const normalizeTextStyleList = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['disc', 'circle', 'square'].includes(safe) ? safe : 'none';
        };

        const sanitizeIconClass = (value) => String(value || '')
            .trim()
            .split(/\s+/)
            .filter((token) => /^[a-z0-9_-]+$/i.test(token))
            .join(' ');

        const getFontFamily = (value) => {
            const safe = normalizeTextStyleFont(value);
            if (safe === 'system') return 'var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif)';
            if (safe === 'sans') return '"Cabin", var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif)';
            if (safe === 'serif') return 'Georgia, "Times New Roman", Times, serif';
            if (safe === 'mono') return '"SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace';
            if (safe === 'display') return '"Cabin", var(--font-family-heading, var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif))';
            return '';
        };

        const resolveTextStyle = (source, prefix, fallbackAlign) => {
            const safeSource = source && typeof source === 'object' ? source : {};
            const safePrefix = String(prefix || '').replace(/[^a-zA-Z0-9_]/g, '') || 'textStyle';
            const iconPosition = String(safeSource[`${safePrefix}IconPosition`] || 'start').trim().toLowerCase();
            return {
                align: normalizeAlign(safeSource[`${safePrefix}Align`], fallbackAlign),
                font: normalizeTextStyleFont(safeSource[`${safePrefix}Font`]),
                size: normalizeTextStyleSize(safeSource[`${safePrefix}Size`]),
                bold: normalizeToggle(safeSource[`${safePrefix}Bold`]),
                italic: normalizeToggle(safeSource[`${safePrefix}Italic`]),
                underline: normalizeToggle(safeSource[`${safePrefix}Underline`]),
                color: normalizeColor(safeSource[`${safePrefix}Color`]),
                list: normalizeTextStyleList(safeSource[`${safePrefix}List`]),
                icon: sanitizeIconClass(safeSource[`${safePrefix}Icon`]),
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

        const injectListMarker = (content, style) => {
            if (style.list === 'none') {
                return content;
            }
            const glyph = style.list === 'circle' ? '∘' : (style.list === 'square' ? '▪' : '•');
            return `<span class="pb-styled-text-list-marker pb-styled-text-list-marker-${escapeAttr(style.list)}" aria-hidden="true">${escapeHtml(glyph)}</span>${content}`;
        };

        const renderStyledText = (text, className, style, tagName) => {
            const safeText = String(text || '').trim();
            if (!safeText) {
                return '';
            }
            const content = `<span class="pb-styled-text-content">${escapeHtml(safeText)}</span>`;
            return `<${tagName} class="${escapeAttr(className)}">${injectListMarker(injectIcon(content, style), style)}</${tagName}>`;
        };

        const applyTextStyle = (elements, style) => {
            Array.from(elements || []).forEach((element) => {
                if (!(element instanceof HTMLElement)) {
                    return;
                }
                element.style.textAlign = normalizeAlign(style.align, 'left');
                if (style.color) {
                    element.style.color = style.color;
                }
                element.style.fontFamily = getFontFamily(style.font) || '';
                element.style.fontSize = style.size !== 'inherit' ? style.size : '';
                element.querySelectorAll('.pb-styled-text-content').forEach((node) => {
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    node.style.fontWeight = style.bold ? '700' : '';
                    node.style.fontStyle = style.italic ? 'italic' : '';
                    node.style.textDecoration = style.underline ? 'underline' : '';
                });
            });
        };

        const schedulePreviewStyleSync = (id, styleMap, attempts = 4) => {
            if (!window.requestAnimationFrame) {
                return;
            }
            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-feature-grid-preview-id="${id}"]`);
                if (!root) {
                    if (attempts > 0) {
                        schedulePreviewStyleSync(id, styleMap, attempts - 1);
                    }
                    return;
                }

                applyTextStyle(root.querySelectorAll('.pb-preview-feature-title'), styleMap.titleStyle);
                root.querySelectorAll('.pb-preview-feature-item').forEach((itemNode, itemIndex) => {
                    if (!(itemNode instanceof HTMLElement)) {
                        return;
                    }
                    const titleNode = itemNode.querySelector('.pb-preview-feature-item-title');
                    if (titleNode instanceof HTMLElement) {
                        applyTextStyle([titleNode], styleMap.itemTitleStyles[itemIndex] || styleMap.itemTitleFallbackStyle);
                    }
                    const textNode = itemNode.querySelector('.pb-preview-feature-item-text');
                    if (textNode instanceof HTMLElement) {
                        applyTextStyle([textNode], styleMap.itemTextStyles[itemIndex] || styleMap.itemTextFallbackStyle);
                    }
                });
            });
        };

        const schedulePreviewDesignSync = (id, design, attempts = 4) => {
            if (!window.requestAnimationFrame) {
                return;
            }
            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-feature-grid-preview-id="${id}"]`);
                if (!root) {
                    if (attempts > 0) {
                        schedulePreviewDesignSync(id, design, attempts - 1);
                    }
                    return;
                }

                root.querySelectorAll('.pb-preview-feature-item').forEach((itemNode) => {
                    if (!(itemNode instanceof HTMLElement)) {
                        return;
                    }

                    if (!design.useCustom) {
                        itemNode.style.background = '';
                        itemNode.style.borderStyle = '';
                        itemNode.style.borderWidth = '';
                        itemNode.style.borderColor = '';
                        itemNode.style.borderRadius = '';
                        itemNode.style.boxShadow = '';
                        return;
                    }

                    itemNode.style.borderRadius = `${design.radius}px`;
                    itemNode.style.background = design.surfaceColor || '';
                    if (design.borderStyle !== 'inherit') {
                        itemNode.style.borderStyle = design.borderStyle;
                        itemNode.style.borderWidth = `${design.borderWidth}px`;
                    } else {
                        itemNode.style.borderStyle = '';
                        itemNode.style.borderWidth = '';
                    }
                    if (design.borderColor) {
                        itemNode.style.borderColor = design.borderColor;
                        if (design.borderStyle === 'inherit') {
                            itemNode.style.borderWidth = `${design.borderWidth}px`;
                        }
                    } else {
                        itemNode.style.borderColor = '';
                    }
                    itemNode.style.boxShadow = resolveShadowValue(design.shadowPreset);
                });

                root.querySelectorAll('.pb-preview-feature-title, .pb-preview-feature-item-title, .pb-preview-feature-item-text, .pb-preview-feature-item-text *, .pb-preview-feature-item-icon').forEach((node) => {
                    if (node instanceof HTMLElement && design.useCustom && design.textColor) {
                        node.style.color = design.textColor;
                    }
                });
            });
        };

        const title = String(settings.title || labelHelper('feature_grid_default_title', '')).trim();
        const align = normalizeAlign(settings.align, 'left');
        const variant = normalizeGridVariant(settings.variant || 'subtle');
        const columns = Math.max(1, Math.min(4, Math.trunc(Number(settings.columns || 3)) || 3));
        const showHeader = normalizeToggle(settings.showHeader, true);
        const showTitle = normalizeToggle(settings.showTitle, true);
        const showBody = normalizeToggle(settings.showBody, true);
        const legacyShowFooter = normalizeToggle(settings.showFooter, false);
        const defaultButtonLabel = String(settings.buttonLabel || labelHelper('feature_grid_default_button_label', '')).trim();
        const titleStyle = resolveTextStyle(settings, 'titleStyle', align);
        const useCustomDesign = normalizeToggle(settings.useCustomDesign || '', false);
        const designSurfaceColor = normalizeColor(settings.designSurfaceColor || '');
        const designTextColor = normalizeColor(settings.designTextColor || '');
        const designBorderStyle = normalizeBorderStyle(settings.designBorderStyle || 'inherit');
        const designBorderWidth = normalizeClampedInt(settings.designBorderWidth, 1, 0, 8);
        const designBorderColor = normalizeColor(settings.designBorderColor || '');
        const designRadius = normalizeClampedInt(settings.designRadius, 16, 0, 40);
        const designShadow = normalizeShadowPreset(settings.designShadow || 'inherit');
        const titles = parseRepeaterLines(settings.titles || labelHelper('feature_grid_default_titles', ''));
        const texts = parseFeatureGridTextValues(settings.texts || labelHelper('feature_grid_default_texts', ''));
        const icons = parseRepeaterLines(settings.icons);
        const iconEnableds = parseRepeaterLines(settings.iconEnableds);
        const iconAligns = parseRepeaterLines(settings.iconAligns);
        const links = parseRepeaterLines(settings.links);
        const buttonEnableds = parseRepeaterLines(settings.buttonEnableds);
        const buttonLabels = parseRepeaterLines(settings.buttonLabels);
        const buttonTargets = parseRepeaterLines(settings.buttonTargets);
        const buttonVariants = parseRepeaterLines(settings.buttonVariants);
        const buttonAligns = parseRepeaterLines(settings.buttonAligns);

        const count = Math.max(titles.length, texts.length, icons.length, iconAligns.length, links.length, buttonEnableds.length, buttonLabels.length, buttonTargets.length, buttonVariants.length, buttonAligns.length, 1);
        const itemTitleStyles = [];
        const itemTextStyles = [];

        const items = Array.from({ length: Math.min(count, 8) }, (_, index) => {
            const itemTitle = String(titles[index] || '').trim();
            const itemText = String(texts[index] || '').trim();
            const iconClass = sanitizeIconClass(icons[index] || '');
            const itemIconEnabled = normalizeToggle(iconEnableds[index] || 'on', true);
            const itemIconAlign = normalizeAlign(iconAligns[index] || '', align);
            const itemLink = String(links[index] || '').trim();
            const itemButtonEnabledRaw = String(buttonEnableds[index] || '').trim();
            const itemButtonEnabled = itemButtonEnabledRaw !== ''
                ? normalizeToggle(itemButtonEnabledRaw, false)
                : legacyShowFooter;
            const itemButtonLabel = String(buttonLabels[index] || '').trim() || defaultButtonLabel;
            const itemButtonTarget = itemLink !== ''
                ? normalizeTarget(buttonTargets[index] || '', '_self')
                : '_self';
            const itemButtonVariant = normalizeButtonVariant(buttonVariants[index] || 'ghost');
            const itemButtonAlign = normalizeAlign(buttonAligns[index] || '', align);
            const itemTitleStyle = resolveTextStyle(settings, `itemTitleStyle${index + 1}`, align);
            const itemTextStyle = resolveTextStyle(settings, `itemTextStyle${index + 1}`, align);
            itemTitleStyles.push(itemTitleStyle);
            itemTextStyles.push(itemTextStyle);

            const iconNode = showHeader && itemIconEnabled
                ? (iconClass !== ''
                    ? `<div class="pb-preview-feature-item-header pb-preview-feature-item-header-align-${escapeAttr(itemIconAlign)}"><span class="pb-preview-feature-item-icon"><i class="${escapeAttr(iconClass)}" aria-hidden="true"></i></span></div>`
                    : `<div class="pb-preview-feature-item-header pb-preview-feature-item-header-align-${escapeAttr(itemIconAlign)}"><span class="pb-preview-feature-item-icon is-empty" aria-hidden="true"></span></div>`)
                : '';
            const titleNode = showTitle
                ? renderStyledText(itemTitle, 'pb-preview-feature-item-title', itemTitleStyle, 'strong')
                : '';
            const textNode = showBody
                ? `<div class="pb-preview-feature-item-body">${itemText !== '' ? `<div class="pb-preview-feature-item-text">${sanitizeRichText(itemText)}</div>` : ''}</div>`
                : '';
            const footerNode = itemButtonEnabled
                ? `<div class="pb-preview-feature-item-footer pb-preview-feature-item-footer-align-${escapeAttr(itemButtonAlign)}">${itemButtonLabel !== '' && itemLink !== '' ? `<a class="btn btn-${escapeAttr(itemButtonVariant)} pb-btn pb-btn-${escapeAttr(itemButtonVariant)} pb-preview-feature-item-cta" href="${escapeAttr(itemLink)}" target="${escapeAttr(itemButtonTarget)}"${itemButtonTarget === '_blank' ? ' rel="noopener noreferrer"' : ''}><span class="pb-preview-feature-item-cta-label">${escapeHtml(itemButtonLabel)}</span></a>` : itemButtonLabel !== '' ? `<span class="btn btn-${escapeAttr(itemButtonVariant)} pb-btn pb-btn-${escapeAttr(itemButtonVariant)} pb-preview-feature-item-cta is-static" aria-disabled="true"><span class="pb-preview-feature-item-cta-label">${escapeHtml(itemButtonLabel)}</span></span>` : '<span class="pb-preview-feature-item-footer-placeholder" aria-hidden="true"></span>'}</div>`
                : '';
            const isEmpty = (!showHeader || !itemIconEnabled || iconClass === '')
                && (!showTitle || itemTitle === '')
                && (!showBody || itemText === '')
                && (!itemButtonEnabled || itemButtonLabel === '');

            const itemCardClass = variant === 'strong' ? 'pb-card pb-card-strong' : 'pb-card pb-card-subtle';
            return `<article class="pb-preview-feature-item ${itemCardClass}">${iconNode}${titleNode}${textNode}${footerNode}${isEmpty ? `<div class="pb-empty">${escapeHtml(labelHelper('feature_grid_empty', ''))}</div>` : ''}</article>`;
        }).join('');

        schedulePreviewStyleSync(previewId, {
            titleStyle: titleStyle,
            itemTitleStyles: itemTitleStyles,
            itemTitleFallbackStyle: resolveTextStyle(settings, 'itemTitleStyle', align),
            itemTextStyles: itemTextStyles,
            itemTextFallbackStyle: resolveTextStyle(settings, 'itemTextStyle', align),
        });
        schedulePreviewDesignSync(previewId, {
            useCustom: useCustomDesign,
            surfaceColor: designSurfaceColor,
            textColor: designTextColor,
            borderStyle: designBorderStyle,
            borderWidth: designBorderWidth,
            borderColor: designBorderColor,
            radius: designRadius,
            shadowPreset: designShadow,
        });

        return `
            <div class="pb-preview-feature-grid pb-preview-align pb-preview-align-${escapeAttr(align)}" data-feature-grid-preview-id="${escapeAttr(previewId)}">
                ${renderStyledText(title, 'pb-preview-feature-title', titleStyle, 'strong')}
                <div class="pb-preview-feature-grid-items pb-preview-feature-grid-cols-${columns} pb-preview-feature-grid-variant-${escapeAttr(variant)}">${items}</div>
            </div>
        `;
    };
})();
