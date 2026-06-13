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

    registry.logo_cloud = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || ''));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const labelHelper = helpers.label || ((_key, fallback) => String(fallback || ''));
        const resolveImage = helpers.resolveImage || ((value) => String(value || ''));
        const previewId = `fc-logo-cloud-preview-${Math.random().toString(36).slice(2, 10)}`;

        const parseRepeaterLines = (raw) => {
            if (typeof raw !== 'string' || String(raw).trim() === '') {
                return [];
            }

            const items = String(raw)
                .split(/\r\n|\r|\n/)
                .map((item) => String(item || '').trim());

            while (items.length && String(items[items.length - 1] || '').trim() === '') {
                items.pop();
            }

            return items;
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

        const normalizeAlign = (value, fallback = 'center') => {
            const safe = String(value || '').trim().toLowerCase();
            if (['left', 'center', 'right'].includes(safe)) {
                return safe;
            }
            const safeFallback = String(fallback || 'center').trim().toLowerCase();
            return ['left', 'center', 'right'].includes(safeFallback) ? safeFallback : 'center';
        };

        const normalizeVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['subtle', 'strong', 'ghost'].includes(safe) ? safe : 'subtle';
        };

        const normalizePresentationModel = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['classic', 'cloud4', 'cloud6', 'cloud7'].includes(safe) ? safe : 'classic';
        };

        const normalizeColumns = (value) => {
            const safe = Math.trunc(Number(value || 4)) || 4;
            return Math.max(2, Math.min(6, safe));
        };

        const normalizeLogoHeight = (value) => {
            const safe = Math.trunc(Number(value || 72)) || 72;
            return Math.max(40, Math.min(160, safe));
        };

        const normalizeWidgetHeight = (value) => {
            const safe = Math.trunc(Number(value || 280)) || 280;
            return Math.max(220, Math.min(760, safe));
        };

        const normalizeGap = (value) => {
            const safe = Math.trunc(Number(value || 20)) || 20;
            return Math.max(8, Math.min(48, safe));
        };

        const normalizeAnimationSpeed = (value) => {
            const safe = Math.trunc(Number(value || 28)) || 28;
            return Math.max(12, Math.min(60, safe));
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

        const sanitizeUrl = (value) => {
            const safe = String(value || '').trim();
            if (!safe) {
                return '';
            }
            if (safe[0] === '#' || safe[0] === '/' || safe[0] === '?') {
                return safe;
            }
            if (/^(https?:|mailto:|tel:)/i.test(safe)) {
                return safe;
            }
            return '';
        };

        const normalizeTarget = (value, fallback = '_self') => {
            const safe = String(value || '').trim();
            if (['_self', '_blank'].includes(safe)) {
                return safe;
            }
            return ['_self', '_blank'].includes(fallback) ? fallback : '_self';
        };

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
                bold: normalizeToggle(safeSource[`${safePrefix}Bold`], false),
                italic: normalizeToggle(safeSource[`${safePrefix}Italic`], false),
                underline: normalizeToggle(safeSource[`${safePrefix}Underline`], false),
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

        const renderStyledText = (text, tagName, className, style) => {
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
                const root = document.querySelector(`[data-logo-cloud-preview-id="${id}"]`);
                if (!root) {
                    if (attempts > 0) {
                        schedulePreviewStyleSync(id, styleMap, attempts - 1);
                    }
                    return;
                }

                const grid = root.querySelector('.pb-logo-cloud-grid');
                const columnLayout = root.querySelector('.pb-logo-cloud-columns');
                const gapValue = Number(root.getAttribute('data-logo-cloud-gap') || 20);
                const motionDurationValue = Number(root.getAttribute('data-logo-cloud-motion-duration') || 28);
                const heightValue = Number(root.getAttribute('data-logo-cloud-height') || 72);
                const widgetHeightValue = Number(root.getAttribute('data-logo-cloud-widget-height') || 280);
                const columnsValue = Number(root.getAttribute('data-logo-cloud-columns') || 4);
                const columnCountValue = Number(root.getAttribute('data-logo-cloud-column-count') || columnsValue || 1);

                root.style.setProperty('--pb-logo-cloud-gap', `${normalizeGap(gapValue)}px`);
                root.style.setProperty('--pb-logo-cloud-motion-duration', `${normalizeAnimationSpeed(motionDurationValue)}s`);
                root.style.setProperty('--pb-logo-cloud-logo-height', `${normalizeLogoHeight(heightValue)}px`);
                root.style.setProperty('--pb-logo-cloud-widget-height', `${normalizeWidgetHeight(widgetHeightValue)}px`);
                root.style.setProperty('--pb-logo-cloud-columns', String(normalizeColumns(columnsValue)));
                if (grid instanceof HTMLElement) {
                    grid.style.gridTemplateColumns = `repeat(${normalizeColumns(columnsValue)},minmax(0,1fr))`;
                }
                if (columnLayout instanceof HTMLElement) {
                    columnLayout.style.gridTemplateColumns = `repeat(${Math.max(1, Math.min(6, Math.trunc(columnCountValue) || 1))},minmax(0,1fr))`;
                }

                applyTextStyle(root.querySelectorAll('.pb-logo-cloud-title'), styleMap.titleStyle);
                applyTextStyle(root.querySelectorAll('.pb-logo-cloud-subtitle'), styleMap.subtitleStyle);
                applyTextStyle(root.querySelectorAll('.pb-logo-cloud-label'), styleMap.labelStyle);
            });
        };


        const normalizeDesignColor = (value) => {
            if (typeof normalizeColor === 'function') {
                return normalizeColor(value);
            }
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

        const resolveWidgetDesign = (source, defaultRadius = 16) => ({
            useCustom: normalizeToggle(source.useCustomDesign || '', false),
            surfaceColor: normalizeDesignColor(source.designSurfaceColor || ''),
            textColor: normalizeDesignColor(source.designTextColor || ''),
            borderStyle: normalizeBorderStyle(source.designBorderStyle || 'inherit'),
            borderWidth: normalizeDesignInt(source.designBorderWidth, 1, 0, 8),
            borderColor: normalizeDesignColor(source.designBorderColor || ''),
            radius: normalizeDesignInt(source.designRadius, defaultRadius, 0, 48),
            shadowPreset: normalizeShadowPreset(source.designShadow || 'inherit'),
        });

        const applyDesignSurface = (node, design) => {
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
            node.style.borderRadius = String(design.radius) + 'px';
            node.style.background = design.surfaceColor || '';
            if (design.borderStyle !== 'inherit') {
                node.style.borderStyle = design.borderStyle;
                node.style.borderWidth = String(design.borderWidth) + 'px';
            } else {
                node.style.borderStyle = '';
                node.style.borderWidth = '';
            }
            if (design.borderColor) {
                node.style.borderColor = design.borderColor;
                if (design.borderStyle === 'inherit') {
                    node.style.borderWidth = String(design.borderWidth) + 'px';
                }
            } else {
                node.style.borderColor = '';
            }
            node.style.boxShadow = resolveShadowValue(design.shadowPreset);
        };

        const schedulePreviewDesignSync = (id, attribute, surfaceSelectors, textSelectors, design, attempts = 4) => {
            if (!window.requestAnimationFrame) {
                return;
            }
            window.requestAnimationFrame(() => {
                const root = document.querySelector('[' + attribute + '="' + id + '"]');
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        schedulePreviewDesignSync(id, attribute, surfaceSelectors, textSelectors, design, attempts - 1);
                    }
                    return;
                }
                surfaceSelectors.forEach((selector) => {
                    root.querySelectorAll(selector).forEach((node) => applyDesignSurface(node, design));
                });
                textSelectors.forEach((selector) => {
                    root.querySelectorAll(selector).forEach((node) => {
                        if (node instanceof HTMLElement && design.useCustom && design.textColor) {
                            node.style.color = design.textColor;
                        }
                    });
                });
            });
        };

        const design = resolveWidgetDesign(settings, 18);
        const title = String(settings.title || labelHelper('logo_cloud_default_title', '')).trim();
        const subtitle = String(settings.subtitle || labelHelper('logo_cloud_default_subtitle', '')).trim();
        const labels = parseRepeaterLines(settings.labels || labelHelper('logo_cloud_default_labels', ''));
        const logos = parseRepeaterLines(settings.logos || '');
        const links = parseRepeaterLines(settings.links || '');
        const targets = parseRepeaterLines(settings.targets || '');
        const showHeader = normalizeToggle(settings.showHeader, true);
        const showLabels = normalizeToggle(settings.showLabels, false);
        const presentationModel = normalizePresentationModel(settings.presentationModel || 'classic');
        const columns = normalizeColumns(settings.columns || 4);
        const animationSpeed = normalizeAnimationSpeed(settings.animationSpeed || 28);
        const logoHeight = normalizeLogoHeight(settings.logoHeight || 72);
        const widgetHeight = normalizeWidgetHeight(settings.widgetHeight || 280);
        const gap = normalizeGap(settings.gap || 20);
        const align = normalizeAlign(settings.align, 'center');
        const variant = normalizeVariant(settings.variant || 'subtle');
        const grayscale = normalizeToggle(settings.grayscale, true);
        const titleStyle = resolveTextStyle(settings, 'titleStyle', align);
        const subtitleStyle = resolveTextStyle(settings, 'subtitleStyle', align);
        const labelStyle = resolveTextStyle(settings, 'labelStyle', align);
        const count = Math.min(Math.max(labels.length, logos.length, links.length, targets.length, 1), 13);
        const items = [];

        for (let index = 0; index < count; index += 1) {
            const itemLabel = String(labels[index] || '').trim();
            const mediaSrc = resolveImage(String(logos[index] || '').trim());
            const itemLink = sanitizeUrl(String(links[index] || '').trim());
            const itemTarget = normalizeTarget(targets[index] || '_self', '_self');

            if (!itemLabel && !mediaSrc) {
                continue;
            }

            items.push({
                label: itemLabel,
                logo: mediaSrc,
                link: itemLink,
                target: itemTarget,
            });
        }

        const buildSurfaceClasses = (variantValue, baseClasses) => {
            const classes = Array.isArray(baseClasses) ? [...baseClasses] : [];
            classes.push('pb-logo-cloud-surface');
            if (variantValue === 'strong') {
                classes.push('pb-card', 'pb-card-strong');
            } else if (variantValue === 'subtle') {
                classes.push('pb-card', 'pb-card-subtle');
            } else {
                classes.push('pb-logo-cloud-surface-ghost');
            }

            return classes;
        };

        const buildInteractiveWrapper = (item, className, innerHtml) => {
            if (item.link) {
                const relAttr = item.target === '_blank' ? ' rel="noopener noreferrer"' : '';
                return `<a class="${escapeAttr(className)}" href="${escapeAttr(item.link)}" target="${escapeAttr(item.target)}"${relAttr}>${innerHtml}</a>`;
            }

            return `<div class="${escapeAttr(className)}">${innerHtml}</div>`;
        };

        const renderMediaHtml = (item) => {
            if (item.logo) {
                return `<img class="pb-logo-cloud-image" src="${escapeAttr(item.logo)}" alt="${escapeAttr(item.label || labelHelper('logo_cloud_logo_alt', ''))}">`;
            }

            return `<span class="pb-logo-cloud-fallback">${escapeHtml(item.label)}</span>`;
        };

        const expandSequence = (source, minimumItems) => {
            if (!Array.isArray(source) || source.length === 0) {
                return [];
            }

            let expanded = [...source];
            while (expanded.length < minimumItems) {
                expanded = expanded.concat(source);
            }

            return expanded;
        };

        const rotateItems = (source, offset) => {
            if (!Array.isArray(source) || source.length <= 1) {
                return Array.isArray(source) ? [...source] : [];
            }

            const safeOffset = Math.abs(Math.trunc(offset || 0)) % source.length;
            if (safeOffset === 0) {
                return [...source];
            }

            return source.slice(safeOffset).concat(source.slice(0, safeOffset));
        };

        const renderGridItem = (item) => {
            const classes = buildSurfaceClasses(variant, ['pb-logo-cloud-item']);
            let bodyHtml = `<div class="pb-logo-cloud-media">${renderMediaHtml(item)}</div>`;
            if (showLabels && item.label) {
                bodyHtml += renderStyledText(item.label, 'p', 'pb-logo-cloud-label', labelStyle);
            }

            return `<article class="${escapeAttr(classes.join(' '))}">${buildInteractiveWrapper(item, 'pb-logo-cloud-item-shell', bodyHtml)}</article>`;
        };

        const renderMarqueeItem = (item) => {
            const classes = buildSurfaceClasses(variant, ['pb-logo-cloud-marquee-item']);
            let bodyHtml = `<div class="pb-logo-cloud-media">${renderMediaHtml(item)}</div>`;
            if (showLabels && item.label) {
                bodyHtml += renderStyledText(item.label, 'p', 'pb-logo-cloud-label', labelStyle);
            }

            return `<article class="${escapeAttr(classes.join(' '))}">${buildInteractiveWrapper(item, 'pb-logo-cloud-marquee-shell', bodyHtml)}</article>`;
        };

        const renderClassicTapeItem = (item) => {
            const bodyHtml = `<div class="pb-logo-cloud-media pb-logo-cloud-media-tape">${renderMediaHtml(item)}</div>`;
            return `<article class="pb-logo-cloud-classic-item pb-logo-cloud-surface">${buildInteractiveWrapper(item, 'pb-logo-cloud-classic-shell', bodyHtml)}</article>`;
        };

        const renderColumnItem = (item) => {
            const badgeClasses = buildSurfaceClasses(variant, ['pb-logo-cloud-column-badge']);
            let bodyHtml = `<div class="${escapeAttr(badgeClasses.join(' '))}"><div class="pb-logo-cloud-media pb-logo-cloud-media-circle">${renderMediaHtml(item)}</div></div>`;
            if (showLabels && item.label) {
                bodyHtml += renderStyledText(item.label, 'p', 'pb-logo-cloud-label pb-logo-cloud-column-label', labelStyle);
            }

            return `<article class="pb-logo-cloud-column-item">${buildInteractiveWrapper(item, 'pb-logo-cloud-column-link', bodyHtml)}</article>`;
        };

        const renderOrbitItem = (item, articleClass, surfaceBaseClasses, wrapperClass, labelClass) => {
            let bodyHtml = `<div class="${escapeAttr(surfaceBaseClasses.join(' '))}"><div class="pb-logo-cloud-media pb-logo-cloud-media-orbit">${renderMediaHtml(item)}</div>`;
            if (showLabels && item.label) {
                bodyHtml += renderStyledText(item.label, 'p', labelClass, labelStyle);
            }
            bodyHtml += '</div>';

            return `<article class="${escapeAttr(articleClass)}">${buildInteractiveWrapper(item, wrapperClass, bodyHtml)}</article>`;
        };

        let headerHtml = '';
        if (showHeader && (title || subtitle)) {
            headerHtml = `
                <header class="pb-logo-cloud-header">
                    ${title ? renderStyledText(title, 'h2', 'pb-logo-cloud-title', titleStyle) : ''}
                    ${subtitle ? renderStyledText(subtitle, 'p', 'pb-logo-cloud-subtitle', subtitleStyle) : ''}
                </header>
            `;
        }

        const columnCount = Math.max(1, Math.min(6, columns));
        let contentHtml = '';

        if (!items.length) {
            contentHtml = `<div class="pb-empty">${escapeHtml(labelHelper('logo_cloud_empty', ''))}</div>`;
        } else if (presentationModel === 'classic') {
            const sequence = expandSequence(items, Math.max(8, items.length * 2));
            const trackItems = sequence.concat(sequence).map((item) => renderClassicTapeItem(item)).join('');

            contentHtml = `
                <div class="pb-logo-cloud-classic-row">
                    <div class="pb-logo-cloud-classic-track">${trackItems}</div>
                </div>
            `;
        } else if (presentationModel === 'cloud4') {
            const firstRow = [];
            const secondRow = [];
            items.forEach((item, index) => {
                if (index % 2 === 0) {
                    firstRow.push(item);
                } else {
                    secondRow.push(item);
                }
            });

            const rowA = firstRow.length ? firstRow : items;
            const rowB = secondRow.length ? secondRow : items;
            const renderMarqueeRow = (rowItems, directionClass) => {
                const sequence = expandSequence(rowItems, Math.max(6, rowItems.length * 2));
                const trackItems = sequence.concat(sequence).map((item) => renderMarqueeItem(item)).join('');
                return `<div class="pb-logo-cloud-marquee-row ${escapeAttr(directionClass)}"><div class="pb-logo-cloud-marquee-track">${trackItems}</div></div>`;
            };

            contentHtml = `
                <div class="pb-logo-cloud-marquee">
                    ${renderMarqueeRow(rowA, 'is-forward')}
                    ${renderMarqueeRow(rowB, 'is-reverse')}
                </div>
            `;
        } else if (presentationModel === 'cloud6') {
            const buildColumnSequence = (sourceItems, columnIndex) => {
                if (!sourceItems.length) {
                    return [];
                }

                const offset = sourceItems.length > 1 ? ((columnIndex * 2) % sourceItems.length) : 0;
                let ordered = rotateItems(sourceItems, offset);
                if (columnIndex % 2 === 1) {
                    ordered = [...ordered].reverse();
                }

                return expandSequence(ordered, Math.max(6, sourceItems.length * 2));
            };

            const renderVerticalColumn = (sequence, directionClass) => {
                const trackItems = sequence.concat(sequence).map((item) => renderColumnItem(item)).join('');
                return `<div class="pb-logo-cloud-column"><div class="pb-logo-cloud-vtrack ${escapeAttr(directionClass)}">${trackItems}</div></div>`;
            };

            contentHtml = `
                <div class="pb-logo-cloud-columns">
                    ${Array.from({ length: columnCount }, (_, index) => renderVerticalColumn(buildColumnSequence(items, index), index % 2 === 0 ? 'is-down' : 'is-up')).join('')}
                </div>
            `;
        } else if (presentationModel === 'cloud7') {
            const centerItem = items[0];
            const orbitItems = items.slice(1, 13);
            const hasThirdRing = orbitItems.length > 8;
            const orbitHtml = [
                renderOrbitItem(centerItem, 'pb-logo-cloud-orbit-core', ['pb-logo-cloud-orbit-core-surface', 'pb-logo-cloud-surface'], 'pb-logo-cloud-orbit-core-link', 'pb-logo-cloud-label pb-logo-cloud-orbit-label pb-logo-cloud-orbit-label-core'),
                ...orbitItems.map((item, index) => renderOrbitItem(item, `pb-logo-cloud-orbit-item pb-logo-cloud-orbit-item-pos-${index}`, ['pb-logo-cloud-orbit-satellite-surface', 'pb-logo-cloud-surface'], 'pb-logo-cloud-orbit-link', 'pb-logo-cloud-label pb-logo-cloud-orbit-label')),
            ].join('');

            contentHtml = `
                <div class="pb-logo-cloud-orbit">
                    <div class="pb-logo-cloud-orbit-shell">
                        <div class="pb-logo-cloud-orbit-ring pb-logo-cloud-orbit-ring-inner" aria-hidden="true"></div>
                        <div class="pb-logo-cloud-orbit-ring pb-logo-cloud-orbit-ring-outer" aria-hidden="true"></div>
                        ${hasThirdRing ? '<div class="pb-logo-cloud-orbit-ring pb-logo-cloud-orbit-ring-third" aria-hidden="true"></div>' : ''}
                        ${orbitHtml}
                    </div>
                </div>
            `;
        } else {
            contentHtml = `
                <div class="pb-logo-cloud-grid">
                    ${items.map((item) => renderGridItem(item)).join('')}
                </div>
            `;
        }

        const rootClasses = [
            'pb-logo-cloud',
            `pb-logo-cloud-model-${escapeAttr(presentationModel)}`,
            `pb-logo-cloud-align-${escapeAttr(align)}`,
            `pb-logo-cloud-variant-${escapeAttr(variant)}`,
        ];
        if (grayscale) {
            rootClasses.push('is-grayscale');
        }
        if (design.useCustom) {
            rootClasses.push('pb-logo-cloud-has-custom-design');
        }

        schedulePreviewDesignSync(previewId, 'data-logo-cloud-preview-id', ['.pb-logo-cloud-surface', '.pb-logo-cloud-classic-item', '.pb-logo-cloud-column-item'], ['.pb-logo-cloud-title', '.pb-logo-cloud-subtitle', '.pb-logo-cloud-label', '.pb-logo-cloud-fallback'], design);

        schedulePreviewStyleSync(previewId, {
            titleStyle,
            subtitleStyle,
            labelStyle,
        });

        return `
            <section class="${rootClasses.join(' ')}" data-logo-cloud-preview-id="${escapeAttr(previewId)}" data-logo-cloud-gap="${escapeAttr(String(gap))}" data-logo-cloud-motion-duration="${escapeAttr(String(animationSpeed))}" data-logo-cloud-height="${escapeAttr(String(logoHeight))}" data-logo-cloud-widget-height="${escapeAttr(String(widgetHeight))}" data-logo-cloud-columns="${escapeAttr(String(columns))}" data-logo-cloud-column-count="${escapeAttr(String(columnCount))}">
                ${headerHtml}
                ${contentHtml}
            </section>
        `;
    };
})();
