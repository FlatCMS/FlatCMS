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

    registry.snap_cards = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || ''));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const labelHelper = helpers.label || ((_key, fallback) => String(fallback || ''));
        const resolveImage = helpers.resolveImage || ((value) => String(value || ''));
        const previewId = `fc-snap-cards-preview-${Math.random().toString(36).slice(2, 10)}`;

        const parseRepeaterLines = (raw) => {
            if (typeof raw !== 'string' || raw === '') {
                return [];
            }

            const items = raw
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

        const normalizeAlign = (value, fallback = 'left') => {
            const safe = String(value || '').trim().toLowerCase();
            if (['left', 'center', 'right'].includes(safe)) {
                return safe;
            }
            const safeFallback = String(fallback || 'left').trim().toLowerCase();
            return ['left', 'center', 'right'].includes(safeFallback) ? safeFallback : 'left';
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

        const normalizeTextStyleIconPosition = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['start', 'end'].includes(safe) ? safe : 'start';
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

        const getTextStyleListGlyph = (listStyle) => {
            if (listStyle === 'circle') {
                return '∘';
            }
            if (listStyle === 'square') {
                return '▪';
            }
            if (listStyle === 'disc') {
                return '•';
            }
            return '';
        };

        const resolveTextStyle = (source, prefix, fallbackAlign) => {
            const safeSource = source && typeof source === 'object' ? source : {};
            const safePrefix = String(prefix || '').replace(/[^a-zA-Z0-9_]/g, '') || 'textStyle';

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
                iconPosition: normalizeTextStyleIconPosition(safeSource[`${safePrefix}IconPosition`]),
            };
        };

        const renderStyledPreviewText = (rawText, tag, className, styleState) => {
            const text = String(rawText || '').trim();
            if (!text) {
                return '';
            }

            const style = styleState && typeof styleState === 'object' ? styleState : {};
            const align = normalizeAlign(style.align, 'left');
            const font = normalizeTextStyleFont(style.font || 'inherit');
            const color = normalizeColor(style.color || '');
            const icon = String(style.icon || '').trim();
            const iconPosition = normalizeTextStyleIconPosition(style.iconPosition || 'start');
            const listStyle = normalizeTextStyleList(style.list || 'none');

            const attrs = [
                `class="${escapeAttr(className)} pb-preview-styled-text"`,
                `data-text-align="${escapeAttr(align)}"`,
                `data-text-font="${escapeAttr(font)}"`,
                `data-text-size="${escapeAttr(normalizeTextStyleSize(style.size || 'inherit'))}"`,
                `data-text-bold="${normalizeToggle(style.bold, false) ? '1' : '0'}"`,
                `data-text-italic="${normalizeToggle(style.italic, false) ? '1' : '0'}"`,
                `data-text-underline="${normalizeToggle(style.underline, false) ? '1' : '0'}"`,
            ];

            if (color) {
                attrs.push(`data-text-color="${escapeAttr(color)}"`);
            }
            if (listStyle !== 'none') {
                attrs.push(`data-text-list="${escapeAttr(listStyle)}"`);
            }

            const listMarkerGlyph = getTextStyleListGlyph(listStyle);
            const listMarkerNode = listMarkerGlyph
                ? `<span class="pb-preview-text-list-marker pb-preview-text-list-marker-${escapeAttr(listStyle)}" aria-hidden="true">${escapeHtml(listMarkerGlyph)}</span>`
                : '';
            const content = `<span class="pb-preview-text-content">${escapeHtml(text)}</span>`;
            const iconNode = icon
                ? `<i class="${escapeAttr(icon)} pb-preview-text-icon pb-preview-text-icon-${escapeAttr(iconPosition)}" aria-hidden="true"></i>`
                : '';
            const inner = iconPosition === 'end'
                ? `${listMarkerNode}${content}${iconNode}`
                : `${listMarkerNode}${iconNode}${content}`;

            return `<${tag} ${attrs.join(' ')}>${inner}</${tag}>`;
        };

        const schedulePreviewDesignSync = (id, design, attempts = 4) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-snap-cards-preview-id="${id}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        schedulePreviewDesignSync(id, design, attempts - 1);
                    }
                    return;
                }

                root.querySelectorAll('.pb-preview-snap-card').forEach((cardNode) => {
                    if (!(cardNode instanceof HTMLElement)) {
                        return;
                    }

                    if (!design.useCustom) {
                        cardNode.style.background = '';
                        cardNode.style.borderStyle = '';
                        cardNode.style.borderWidth = '';
                        cardNode.style.borderColor = '';
                        cardNode.style.borderRadius = '';
                        cardNode.style.boxShadow = '';
                        return;
                    }

                    cardNode.style.borderRadius = `${design.radius}px`;
                    cardNode.style.background = design.surfaceColor || '';
                    if (design.borderStyle !== 'inherit') {
                        cardNode.style.borderStyle = design.borderStyle;
                        cardNode.style.borderWidth = `${design.borderWidth}px`;
                    } else {
                        cardNode.style.borderStyle = '';
                        cardNode.style.borderWidth = '';
                    }
                    if (design.borderColor) {
                        cardNode.style.borderColor = design.borderColor;
                        if (design.borderStyle === 'inherit') {
                            cardNode.style.borderWidth = `${design.borderWidth}px`;
                        }
                    } else {
                        cardNode.style.borderColor = '';
                    }
                    cardNode.style.boxShadow = resolveShadowValue(design.shadowPreset);
                });

                root.querySelectorAll('.pb-preview-snap-cards-title, .pb-preview-snap-card-title, .pb-preview-snap-card-text, .pb-preview-snap-card-text *').forEach((node) => {
                    if (node instanceof HTMLElement && design.useCustom && design.textColor) {
                        node.style.color = design.textColor;
                    }
                });
            });
        };

        const title = String(settings.title || labelHelper('snap_cards_default_title', '')).trim();
        const align = normalizeAlign(String(settings.align || 'left'));
        const variantRaw = String(settings.variant || 'soft').trim().toLowerCase();
        const variant = ['default', 'soft', 'dark'].includes(variantRaw) ? variantRaw : 'soft';
        const mediaFullBleed = normalizeToggle(settings.mediaFullBleed || '', false);
        const height = Math.max(220, Math.min(640, Number(settings.height || 360) || 360));
        const overlay = Math.max(0, Math.min(85, Number(settings.overlay || 45) || 45));
        const useCustomDesign = normalizeToggle(settings.useCustomDesign || '', false);
        const designSurfaceColor = normalizeColor(settings.designSurfaceColor || '');
        const designTextColor = normalizeColor(settings.designTextColor || '');
        const designBorderStyle = normalizeBorderStyle(settings.designBorderStyle || 'inherit');
        const designBorderWidth = normalizeClampedInt(settings.designBorderWidth, 1, 0, 8);
        const designBorderColor = normalizeColor(settings.designBorderColor || '');
        const designRadius = normalizeClampedInt(settings.designRadius, 13, 0, 40);
        const designShadow = normalizeShadowPreset(settings.designShadow || 'inherit');
        const defaultCtaLabel = String(settings.ctaLabel || labelHelper('snap_cards_default_cta_label', '')).trim();
        const globalTarget = ['_self', '_blank'].includes(String(settings.target || '').trim()) ? String(settings.target || '').trim() : '_self';
        const titleStyle = resolveTextStyle(settings, 'titleStyle', align);

        const titles = parseRepeaterLines(settings.titles || labelHelper('snap_cards_default_titles', ''));
        const texts = parseRepeaterLines(settings.texts || labelHelper('snap_cards_default_texts', ''));
        const backgrounds = parseRepeaterLines(settings.backgrounds || '');
        const links = parseRepeaterLines(settings.links || '');
        const ctaEnableds = parseRepeaterLines(settings.ctaEnableds || '');
        const ctaLabels = parseRepeaterLines(settings.ctaLabels || '');
        const targets = parseRepeaterLines(settings.targets || '');
        const buttonAligns = parseRepeaterLines(settings.buttonAligns || '');

        const count = Math.max(titles.length, texts.length, backgrounds.length, links.length, ctaEnableds.length, ctaLabels.length, targets.length, buttonAligns.length, 1);
        const limit = Math.min(count, 12);
        const initialIndex = limit > 1 ? 1 : 0;

        const items = Array.from({ length: limit }, (_, index) => {
            const itemTitle = String(titles[index] || '').trim();
            const itemText = String(texts[index] || '').trim();
            const mediaSrc = resolveImage(String(backgrounds[index] || '').trim());
            const rawLink = sanitizeUrl(String(links[index] || '').trim());
            const rawEnabled = String(ctaEnableds[index] || '').trim();
            const itemEnabled = normalizeToggle(rawEnabled === '' ? 'on' : rawEnabled, true);
            const itemLink = itemEnabled && rawLink === '' ? '#' : rawLink;
            const ctaLabel = String(ctaLabels[index] || '').trim() || defaultCtaLabel;
            const hasLink = itemEnabled && itemLink !== '' && ctaLabel !== '';
            const targetValue = ['_self', '_blank'].includes(String(targets[index] || '').trim())
                ? String(targets[index] || '').trim()
                : globalTarget;
            const itemTarget = hasLink ? targetValue : '_self';
            const relAttr = hasLink && itemTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
            const itemTitleStyle = resolveTextStyle(settings, `itemTitleStyle${index + 1}`, align);
            const itemTextStyle = resolveTextStyle(settings, `itemTextStyle${index + 1}`, align);
            const itemButtonAlign = normalizeAlign(String(buttonAligns[index] || ''), align);

            return `
                <article class="pb-preview-snap-card${index === initialIndex ? ' is-center' : ''}${mediaSrc ? ' has-media' : ''}" data-snap-index="${index + 1}" tabindex="0">
                    <div class="pb-preview-snap-card-media">
                        ${mediaSrc ? `<img class="pb-preview-snap-card-image" src="${escapeAttr(mediaSrc)}" alt="${escapeAttr(itemTitle || title || labelHelper('snap_cards_card_alt', ''))}">` : ''}
                    </div>
                    <div class="pb-preview-snap-card-overlay"></div>
                    <div class="pb-preview-snap-card-content">
                        ${renderStyledPreviewText(itemTitle, 'h4', 'pb-preview-snap-card-title', itemTitleStyle, false)}
                        ${renderStyledPreviewText(itemText, 'div', 'pb-preview-snap-card-text', itemTextStyle, false)}
                        ${hasLink ? `<footer class="pb-preview-snap-card-footer pb-preview-actions pb-preview-actions-bar pb-preview-actions-align-${escapeAttr(itemButtonAlign)}"><a class="btn btn-primary pb-btn pb-btn-primary pb-preview-snap-card-link" href="${escapeAttr(itemLink)}" target="${escapeAttr(itemTarget)}"${relAttr}>${escapeHtml(ctaLabel)}</a></footer>` : ''}
                    </div>
                </article>
            `;
        }).join('');

        const showControls = limit > 1;
        const prevLabel = escapeAttr(labelHelper('snap_cards_prev_label', ''));
        const nextLabel = escapeAttr(labelHelper('snap_cards_next_label', ''));
        const rootClasses = [
            'pb-preview-snap-cards',
            `pb-preview-align pb-preview-align-${escapeAttr(align)}`,
            `pb-preview-snap-cards-variant-${escapeAttr(variant)}`
        ];
        if (mediaFullBleed) {
            rootClasses.push('pb-preview-snap-cards-media-fit-cover');
        }

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
            <div class="${rootClasses.join(' ')}" data-snap-cards-preview-id="${escapeAttr(previewId)}" data-snap-card-height="${Math.round(height)}" data-snap-card-overlay="${Math.round(overlay)}" data-snap-cards-preview="1">
                ${renderStyledPreviewText(title, 'strong', 'pb-preview-snap-cards-title', titleStyle)}
                <div class="pb-preview-snap-cards-shell">
                    <div class="pb-preview-snap-cards-track">${items}</div>
                    ${showControls ? `
                    <div class="pb-preview-snap-cards-controls" data-preview-snap-cards-controls>
                        <button class="pb-preview-snap-cards-arrow pb-preview-snap-cards-arrow-prev" type="button" data-preview-snap-cards-prev aria-label="${prevLabel}"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
                        <button class="pb-preview-snap-cards-arrow pb-preview-snap-cards-arrow-next" type="button" data-preview-snap-cards-next aria-label="${nextLabel}"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                    </div>` : ''}
                </div>
            </div>
        `;
    };
})();
