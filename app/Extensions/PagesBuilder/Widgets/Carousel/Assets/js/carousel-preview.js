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

    registry.carousel = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || ''));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const sanitizeRichText = helpers.sanitizeRichText || ((value) => String(value || ''));
        const resolveImage = helpers.resolveImage || ((value) => String(value || ''));
        const labelHelper = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `fc-carousel-preview-${Math.random().toString(36).slice(2, 10)}`;

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
            return ['left', 'center', 'right'].includes(String(fallback || '').trim().toLowerCase())
                ? String(fallback).trim().toLowerCase()
                : 'left';
        };

        const normalizeIndicatorStyle = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['dots', 'bars', 'numbers'].includes(safe) ? safe : 'dots';
        };

        const normalizeArrowStyle = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['filled', 'outline', 'minimal'].includes(safe) ? safe : 'filled';
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

        const normalizeHeight = (value) => {
            const num = Math.trunc(Number(value));
            if (!Number.isFinite(num)) {
                return 420;
            }
            return Math.max(240, Math.min(720, num));
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
            const fallbackPrefix = /^itemTitleStyle\d+$/i.test(safePrefix)
                ? 'itemTitleStyle'
                : (/^itemTextStyle\d+$/i.test(safePrefix) ? 'itemTextStyle' : '');
            const readSetting = (suffix) => {
                const primary = safeSource[`${safePrefix}${suffix}`];
                if (primary !== undefined && primary !== null && String(primary).trim() !== '') {
                    return primary;
                }
                if (fallbackPrefix) {
                    return safeSource[`${fallbackPrefix}${suffix}`];
                }
                return primary;
            };

            const iconPosition = String(readSetting('IconPosition') || 'start').trim().toLowerCase();
            return {
                align: normalizeAlign(readSetting('Align'), fallbackAlign),
                font: normalizeTextStyleFont(readSetting('Font')),
                size: normalizeTextStyleSize(readSetting('Size')),
                bold: normalizeToggle(readSetting('Bold')),
                italic: normalizeToggle(readSetting('Italic')),
                underline: normalizeToggle(readSetting('Underline')),
                color: normalizeColor(readSetting('Color')),
                list: normalizeTextStyleList(readSetting('List')),
                icon: sanitizeIconClass(readSetting('Icon')),
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
            const glyph = style.list === 'circle'
                ? '∘'
                : (style.list === 'square' ? '▪' : '•');
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

        const renderStyledHtml = (html, className, style) => {
            const safeHtml = String(html || '').trim();
            if (!safeHtml) {
                return '';
            }
            const content = `<div class="pb-styled-text-content pb-styled-text-content-rich">${safeHtml}</div>`;
            return `<div class="${escapeAttr(className)}">${injectIcon(content, style)}</div>`;
        };

        const applyTextStyle = (elements, style, isRich = false) => {
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

                element.querySelectorAll('.pb-styled-text-content').forEach((contentNode) => {
                    if (!(contentNode instanceof HTMLElement)) {
                        return;
                    }
                    contentNode.style.fontWeight = style.bold ? '700' : '';
                    contentNode.style.fontStyle = style.italic ? 'italic' : '';
                    contentNode.style.textDecoration = style.underline ? 'underline' : '';
                });

                if (isRich && style.list !== 'none') {
                    element.querySelectorAll('.pb-styled-text-content-rich ul').forEach((listNode) => {
                        if (listNode instanceof HTMLElement) {
                            listNode.style.listStyleType = style.list;
                        }
                    });
                }
            });
        };

        const schedulePreviewStyleSync = (id, styleMap, attempts = 4) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-fc-carousel-preview-id="${id}"]`);
                if (!root) {
                    if (attempts > 0) {
                        schedulePreviewStyleSync(id, styleMap, attempts - 1);
                    }
                    return;
                }

                applyTextStyle(root.querySelectorAll('.fc-carousel-title'), styleMap.titleStyle, false);
                root.querySelectorAll('.fc-carousel-slide').forEach((slideNode, slideIndex) => {
                    if (!(slideNode instanceof HTMLElement)) {
                        return;
                    }
                    const titleNode = slideNode.querySelector('.fc-carousel-caption-title');
                    if (titleNode instanceof HTMLElement) {
                        applyTextStyle([titleNode], styleMap.itemTitleStyles[slideIndex] || styleMap.itemTitleFallbackStyle, false);
                    }
                    const textNode = slideNode.querySelector('.fc-carousel-caption-text');
                    if (textNode instanceof HTMLElement) {
                        applyTextStyle([textNode], styleMap.itemTextStyles[slideIndex] || styleMap.itemTextFallbackStyle, true);
                    }
                });
            });
        };

        const schedulePreviewHeightSync = (id, height, attempts = 4) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-fc-carousel-preview-id="${id}"]`);
                if (!root) {
                    if (attempts > 0) {
                        schedulePreviewHeightSync(id, height, attempts - 1);
                    }
                    return;
                }

                const carousel = root.querySelector('.fc-carousel');
                if (!(carousel instanceof HTMLElement)) {
                    if (attempts > 0) {
                        schedulePreviewHeightSync(id, height, attempts - 1);
                    }
                    return;
                }

                carousel.style.setProperty('--fc-carousel-height', `${height}px`);
                carousel.style.height = `${height}px`;
            });
        };

        const schedulePreviewDesignSync = (id, design, attempts = 4) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-fc-carousel-preview-id="${id}"]`);
                if (!root) {
                    if (attempts > 0) {
                        schedulePreviewDesignSync(id, design, attempts - 1);
                    }
                    return;
                }

                const carousel = root.querySelector('.fc-carousel');
                if (!(carousel instanceof HTMLElement)) {
                    if (attempts > 0) {
                        schedulePreviewDesignSync(id, design, attempts - 1);
                    }
                    return;
                }

                if (!design.useCustom) {
                    carousel.style.background = '';
                    carousel.style.borderStyle = '';
                    carousel.style.borderWidth = '';
                    carousel.style.borderColor = '';
                    carousel.style.borderRadius = '';
                    carousel.style.boxShadow = '';
                    root.querySelectorAll('.fc-carousel-slide').forEach((slideNode) => {
                        if (slideNode instanceof HTMLElement) {
                            slideNode.style.background = '';
                        }
                    });
                    root.querySelectorAll('.fc-carousel-title, .fc-carousel-caption, .fc-carousel-caption-title, .fc-carousel-caption-text').forEach((node) => {
                        if (node instanceof HTMLElement) {
                            node.style.color = '';
                        }
                    });
                    return;
                }

                if (design.surfaceColor) {
                    carousel.style.background = design.surfaceColor;
                } else {
                    carousel.style.background = '';
                }
                carousel.style.borderRadius = `${design.radius}px`;
                if (design.borderStyle !== 'inherit') {
                    carousel.style.borderStyle = design.borderStyle;
                    carousel.style.borderWidth = `${design.borderWidth}px`;
                } else {
                    carousel.style.borderStyle = '';
                    carousel.style.borderWidth = '';
                }
                if (design.borderColor) {
                    carousel.style.borderColor = design.borderColor;
                    if (design.borderStyle === 'inherit') {
                        carousel.style.borderWidth = `${design.borderWidth}px`;
                    }
                } else {
                    carousel.style.borderColor = '';
                }
                carousel.style.boxShadow = resolveShadowValue(design.shadowPreset);

                root.querySelectorAll('.fc-carousel-slide').forEach((slideNode) => {
                    if (!(slideNode instanceof HTMLElement)) {
                        return;
                    }
                    slideNode.style.background = design.surfaceColor || '';
                });
                root.querySelectorAll('.fc-carousel-title, .fc-carousel-caption, .fc-carousel-caption-title, .fc-carousel-caption-text').forEach((node) => {
                    if (node instanceof HTMLElement) {
                        node.style.color = design.textColor || '';
                    }
                });
            });
        };

        const title = String(settings.title || '').trim();
        const images = parseRepeaterLines(settings.images);
        const titles = parseRepeaterLines(settings.titles);
        const texts = parseRepeaterLines(settings.texts);
        const links = parseRepeaterLines(settings.links);
        const buttonEnableds = parseRepeaterLines(settings.buttonEnableds);
        const buttonLabels = parseRepeaterLines(settings.buttonLabels);
        const buttonTargets = parseRepeaterLines(settings.buttonTargets);
        const buttonAligns = parseRepeaterLines(settings.buttonAligns);

        const defaultButtonLabel = String(settings.buttonLabel || '').trim() || String(labelHelper('carousel_default_button_label', 'Découvrir') || '').trim();
        const align = normalizeAlign(settings.align, 'left');
        const height = normalizeHeight(settings.height);
        const mediaFullBleed = normalizeToggle(settings.mediaFullBleed || '', false);
        const transition = ['slide', 'fade'].includes(String(settings.transition || '').toLowerCase())
            ? String(settings.transition).toLowerCase()
            : 'slide';
        const titleStyle = resolveTextStyle(settings, 'titleStyle', align);
        const itemTitleFallbackStyle = resolveTextStyle(settings, 'itemTitleStyle', 'left');
        const itemTextFallbackStyle = resolveTextStyle(settings, 'itemTextStyle', 'left');

        const maxItems = Math.max(1, images.length, titles.length, texts.length, links.length, buttonEnableds.length, buttonLabels.length);
        const limit = Math.min(12, maxItems);
        const hasSlideData = images.length > 0
            || titles.length > 0
            || texts.length > 0
            || links.length > 0
            || buttonEnableds.length > 0
            || buttonLabels.length > 0;
        const itemTitleStyles = [];
        const itemTextStyles = [];

        const slides = [];
        if (hasSlideData) {
            for (let i = 0; i < limit; i++) {
                const image = resolveImage(String(images[i] || ''));
                const slideTitle = String(titles[i] || '').trim();
                const slideText = String(texts[i] || '').trim();
                const slideLink = String(links[i] || '').trim();
                const slideButtonEnabled = normalizeToggle(buttonEnableds[i] || 'on', true);
                const slideButtonLabel = String(buttonLabels[i] || '').trim() || defaultButtonLabel;
                const slideButtonTarget = ['_self', '_blank'].includes(String(buttonTargets[i] || '').trim())
                    ? String(buttonTargets[i] || '').trim()
                    : (['_self', '_blank'].includes(String(settings.target || '').trim()) ? String(settings.target || '').trim() : '_self');
                const slideTitleStyle = resolveTextStyle(settings, `itemTitleStyle${i + 1}`, 'left');
                const slideTextStyle = resolveTextStyle(settings, `itemTextStyle${i + 1}`, 'left');
                const slideButtonAlign = normalizeAlign(String(buttonAligns[i] || 'left'));
                itemTitleStyles.push(slideTitleStyle);
                itemTextStyles.push(slideTextStyle);

                const media = image
                    ? `<img class="fc-carousel-image" src="${escapeAttr(image)}" alt="${escapeAttr(slideTitle || 'Slide')}">`
                    : '<div class="fc-carousel-media-placeholder" aria-hidden="true"></div>';

                const captionParts = [];
                if (slideTitle) {
                    captionParts.push(renderStyledText(slideTitle, 'fc-carousel-caption-title', slideTitleStyle, 'h3'));
                }
                if (slideText) {
                    captionParts.push(renderStyledHtml(sanitizeRichText(slideText), 'fc-carousel-caption-text', slideTextStyle));
                }
                if (slideButtonEnabled && slideButtonLabel) {
                    if (slideLink) {
                        const relAttr = slideButtonTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                        captionParts.push(`<a class="fc-carousel-caption-btn btn btn-primary pb-btn pb-btn-primary fc-carousel-caption-btn-align-${escapeAttr(slideButtonAlign)}" href="${escapeAttr(slideLink)}" target="${escapeAttr(slideButtonTarget)}"${relAttr}>${escapeHtml(slideButtonLabel)}</a>`);
                    } else {
                        captionParts.push(`<span class="fc-carousel-caption-btn btn btn-primary pb-btn pb-btn-primary fc-carousel-caption-btn-align-${escapeAttr(slideButtonAlign)} is-static" aria-disabled="true">${escapeHtml(slideButtonLabel)}</span>`);
                    }
                }

                const caption = captionParts.length
                    ? `<div class="fc-carousel-caption">${captionParts.join('')}</div>`
                    : '';

                slides.push(`<article class="fc-carousel-slide${i === 0 ? ' is-active' : ''}" data-fc-carousel-slide>${media}${caption}</article>`);
            }
        }

        if (!slides.length) {
            slides.push('<article class="fc-carousel-slide is-active" data-fc-carousel-slide><div class="fc-carousel-empty">Carousel</div></article>');
        }

        const indicatorStyle = normalizeIndicatorStyle(settings.indicatorStyle);
        const arrowStyle = normalizeArrowStyle(settings.arrowStyle);
        const useCustomDesign = normalizeToggle(settings.useCustomDesign, false);
        const designSurfaceColor = normalizeColor(settings.designSurfaceColor);
        const designTextColor = normalizeColor(settings.designTextColor);
        const designBorderStyle = normalizeBorderStyle(settings.designBorderStyle);
        const designBorderWidth = normalizeClampedInt(settings.designBorderWidth, 1, 0, 8);
        const designBorderColor = normalizeColor(settings.designBorderColor);
        const designRadius = normalizeClampedInt(settings.designRadius, 14, 0, 40);
        const designShadow = normalizeShadowPreset(settings.designShadow);
        const indicators = [];
        if (normalizeToggle(settings.showIndicators, true) && limit > 1) {
            for (let i = 0; i < limit; i++) {
                const mark = indicatorStyle === 'numbers'
                    ? String(i + 1)
                    : String(i + 1).padStart(2, '0');
                indicators.push(`<button class="fc-carousel-indicator${i === 0 ? ' is-active' : ''}" type="button" data-fc-carousel-to="${escapeAttr(String(i))}"><span class="fc-carousel-indicator-mark" aria-hidden="true">${escapeHtml(mark)}</span></button>`);
            }
        }

        const showArrows = normalizeToggle(settings.showArrows, true) && limit > 1;
        const controls = showArrows
            ? `<button class="fc-carousel-control fc-carousel-control-style-${escapeAttr(arrowStyle)} fc-carousel-control-prev" type="button" data-fc-carousel-prev><span class="fc-carousel-control-icon" aria-hidden="true">&lsaquo;</span></button>`
                + `<button class="fc-carousel-control fc-carousel-control-style-${escapeAttr(arrowStyle)} fc-carousel-control-next" type="button" data-fc-carousel-next><span class="fc-carousel-control-icon" aria-hidden="true">&rsaquo;</span></button>`
            : '';
        const carouselClasses = ['fc-carousel', `fc-carousel-transition-${escapeAttr(transition)}`];
        if (showArrows) {
            carouselClasses.push('fc-carousel-has-arrows', `fc-carousel-arrows-${escapeAttr(arrowStyle)}`);
        }
        if (indicators.length) {
            carouselClasses.push('fc-carousel-has-indicators', `fc-carousel-indicators-${escapeAttr(indicatorStyle)}`);
        }
        if (mediaFullBleed) {
            carouselClasses.push('fc-carousel-media-fit-cover');
        }

        const header = title ? `<div class="fc-carousel-header">${renderStyledText(title, 'fc-carousel-title', titleStyle, 'h2')}</div>` : '';
        schedulePreviewStyleSync(previewId, { titleStyle, itemTitleStyles, itemTitleFallbackStyle, itemTextStyles, itemTextFallbackStyle });
        schedulePreviewHeightSync(previewId, height);
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

        return `<div class="fc-carousel-wrapper" data-fc-carousel-preview-id="${escapeAttr(previewId)}"><div class="fc-carousel-preview-shell">${header}<div class="${carouselClasses.join(' ')}" data-fc-carousel="1"><div class="fc-carousel-track">${slides.join('')}</div>${controls}${indicators.length ? `<div class="fc-carousel-indicators fc-carousel-indicators-style-${escapeAttr(indicatorStyle)}" data-fc-carousel-indicators>${indicators.join('')}</div>` : ''}</div></div></div>`;
    };
})();
