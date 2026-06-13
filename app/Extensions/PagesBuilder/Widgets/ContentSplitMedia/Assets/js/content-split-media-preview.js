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

    registry.content_split_media = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || ''));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const resolveImage = helpers.resolveImage || ((value) => String(value || ''));
        const label = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `pb-content-split-media-${Math.random().toString(36).slice(2, 10)}`;

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
            return !!fallback;
        };

        const normalizeAlign = (value, fallback = 'left') => {
            const safe = String(value || '').trim().toLowerCase();
            if (['left', 'center', 'right'].includes(safe)) {
                return safe;
            }
            const safeFallback = String(fallback || 'left').trim().toLowerCase();
            return ['left', 'center', 'right'].includes(safeFallback) ? safeFallback : 'left';
        };

        const normalizeVerticalAlign = (value, fallback = 'center') => {
            const safe = String(value || '').trim().toLowerCase();
            if (['top', 'center', 'bottom'].includes(safe)) {
                return safe;
            }
            const safeFallback = String(fallback || 'center').trim().toLowerCase();
            return ['top', 'center', 'bottom'].includes(safeFallback) ? safeFallback : 'center';
        };

        const normalizeVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['subtle', 'strong', 'dark'].includes(safe) ? safe : 'subtle';
        };

        const normalizeMediaKind = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['image', 'video'].includes(safe) ? safe : 'image';
        };

        const normalizeMediaPosition = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['left', 'right'].includes(safe) ? safe : 'right';
        };

        const normalizeRatio = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['balanced', 'content-wide', 'media-wide'].includes(safe) ? safe : 'balanced';
        };

        const normalizeMediaFit = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['cover', 'contain'].includes(safe) ? safe : 'cover';
        };

        const normalizePreload = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['auto', 'metadata', 'none'].includes(safe) ? safe : 'metadata';
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

        const normalizeFont = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', 'system', 'sans', 'serif', 'mono', 'display'].includes(safe) ? safe : 'inherit';
        };

        const normalizeFontSize = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['inherit', '12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'].includes(safe) ? safe : 'inherit';
        };

        const normalizeTextStyleList = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['disc', 'circle', 'square'].includes(safe) ? safe : 'none';
        };

        const getFontFamily = (value) => {
            const safe = normalizeFont(value);
            if (safe === 'system') return 'var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif)';
            if (safe === 'sans') return '"Cabin", var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif)';
            if (safe === 'serif') return 'Georgia, "Times New Roman", Times, serif';
            if (safe === 'mono') return '"SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace';
            if (safe === 'display') return '"Cabin", var(--font-family-heading, var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif))';
            return '';
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

        const resolveTextStyle = (source, prefix, fallbackAlign) => {
            const safeSource = source && typeof source === 'object' ? source : {};
            const safePrefix = String(prefix || '').replace(/[^a-zA-Z0-9_]/g, '') || 'textStyle';
            const iconPosition = String(safeSource[`${safePrefix}IconPosition`] || 'start').trim().toLowerCase();

            return {
                align: normalizeAlign(safeSource[`${safePrefix}Align`], fallbackAlign),
                font: normalizeFont(safeSource[`${safePrefix}Font`]),
                size: normalizeFontSize(safeSource[`${safePrefix}Size`]),
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

        const renderStyledParagraphs = (text, className, style) => {
            const normalized = String(text || '').replace(/\r\n?/g, '\n').trim();
            if (!normalized) {
                return '';
            }

            const paragraphs = normalized
                .split(/\n\s*\n/g)
                .map((chunk) => String(chunk || '').trim())
                .filter(Boolean)
                .map((chunk) => {
                    const content = `<span class="pb-styled-text-content">${escapeHtml(chunk).replace(/\n/g, '<br>')}</span>`;
                    return `<p class="pb-content-split-media-body-paragraph">${injectListMarker(injectIcon(content, style), style)}</p>`;
                });

            if (!paragraphs.length) {
                return '';
            }

            return `<div class="${escapeAttr(className)}">${paragraphs.join('')}</div>`;
        };

        const applyTextStyle = (element, style) => {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            const normalizedAlign = normalizeAlign(style.align, 'left');
            element.style.textAlign = normalizedAlign;
            if (style.color) {
                element.style.color = style.color;
            }
            element.style.fontFamily = getFontFamily(style.font) || '';
            element.style.fontSize = style.size !== 'inherit' ? style.size : '';
            if (element.classList.contains('pb-content-split-media-eyebrow')) {
                element.style.justifySelf = normalizedAlign === 'center'
                    ? 'center'
                    : (normalizedAlign === 'right' ? 'end' : 'start');
            }

            element.querySelectorAll('.pb-styled-text-content').forEach((node) => {
                if (!(node instanceof HTMLElement)) {
                    return;
                }
                node.style.fontWeight = style.bold ? '700' : '';
                node.style.fontStyle = style.italic ? 'italic' : '';
                node.style.textDecoration = style.underline ? 'underline' : '';
            });
        };

        const schedulePreviewSync = (id, styles) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-content-split-media-preview-id="${id}"]`);
                if (!(root instanceof HTMLElement)) {
                    return;
                }

                applyTextStyle(root.querySelector('.pb-content-split-media-eyebrow'), styles.eyebrowStyle);
                applyTextStyle(root.querySelector('.pb-content-split-media-title'), styles.titleStyle);
                applyTextStyle(root.querySelector('.pb-content-split-media-subtitle'), styles.subtitleStyle);
                applyTextStyle(root.querySelector('.pb-content-split-media-body'), styles.bodyStyle);
                const featureAlign = normalizeAlign(styles.featureStyle.align, 'left');
                const featureJustify = featureAlign === 'center'
                    ? 'center'
                    : (featureAlign === 'right' ? 'end' : 'start');
                const featureTextAlign = featureAlign === 'center' ? 'left' : featureAlign;
                const featureList = root.querySelector('.pb-content-split-media-features');
                if (featureList instanceof HTMLElement) {
                    featureList.style.justifyItems = featureAlign === 'center' ? 'stretch' : featureJustify;
                    featureList.style.justifySelf = featureAlign === 'center' ? 'center' : '';
                    featureList.style.width = featureAlign === 'center' ? 'fit-content' : '';
                    featureList.style.maxWidth = featureAlign === 'center' ? '100%' : '';
                    featureList.style.textAlign = featureAlign === 'center' ? 'left' : '';
                }
                root.querySelectorAll('.pb-content-split-media-feature-text').forEach((node) => {
                    applyTextStyle(node, styles.featureStyle);
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    node.style.textAlign = featureTextAlign;
                    node.style.flex = featureAlign === 'center' ? '1 1 auto' : '';
                    node.style.width = featureAlign === 'center' ? '100%' : '';
                });
                root.querySelectorAll('.pb-content-split-media-feature').forEach((node) => {
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    node.style.color = styles.featureStyle.color || '';
                    node.style.flexDirection = featureAlign === 'right' ? 'row-reverse' : 'row';
                    node.style.justifySelf = featureAlign === 'center' ? 'stretch' : featureJustify;
                    node.style.width = featureAlign === 'center' ? '100%' : '';
                    node.style.maxWidth = featureAlign === 'center' ? '100%' : '';
                    node.style.textAlign = featureAlign === 'center' ? 'left' : '';
                });
            });
        };

        const parseFeatureItems = (value) => String(value || '')
            .replace(/\r\n?/g, '\n')
            .split('\n')
            .map((entry) => String(entry || '').replace(/^[-*•\s]+/, '').trim())
            .filter(Boolean);


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
                        if (node instanceof HTMLElement) {
                            if (design.useCustom && design.textColor) {
                                node.style.color = design.textColor;
                            }
                        }
                    });
                });
            });
        };

        const showEyebrow = normalizeToggle(settings.showEyebrow, true);
        const showBody = normalizeToggle(settings.showBody, true);
        const showFeatures = normalizeToggle(settings.showFeatures, true);
        const showPrimaryCta = normalizeToggle(settings.showPrimaryCta, true);
        const showSecondaryCta = normalizeToggle(settings.showSecondaryCta, true);
        const eyebrow = String(settings.eyebrow || '').trim();
        const title = String(settings.title || '').trim();
        const subtitle = String(settings.subtitle || '').trim();
        const body = String(settings.body || '').trim();
        const featureItems = parseFeatureItems(settings.featureItems);
        const mediaKind = normalizeMediaKind(settings.mediaKind);
        const mediaPosition = normalizeMediaPosition(settings.mediaPosition);
        const ratio = normalizeRatio(settings.ratio);
        const align = normalizeAlign(settings.align, 'left');
        const textVerticalAlign = normalizeVerticalAlign(settings.textVerticalAlign, 'center');
        const variant = normalizeVariant(settings.variant);
        const mediaFit = normalizeMediaFit(settings.mediaFit);
        const imageSrc = resolveImage(String(settings.imageSrc || '').trim());
        const imageAlt = String(settings.imageAlt || '').trim();
        const videoUrl = resolveImage(String(settings.videoUrl || '').trim());
        const videoPoster = resolveImage(String(settings.videoPoster || '').trim());
        const preload = normalizePreload(settings.preload || 'metadata');
        const autoplay = normalizeToggle(settings.autoplay, false);
        const loop = normalizeToggle(settings.loop, false);
        const muted = normalizeToggle(settings.muted, false);
        const primaryLabel = showPrimaryCta ? String(settings.primaryLabel || '').trim() : '';
        const primaryUrl = showPrimaryCta ? sanitizeUrl(settings.primaryUrl) : '';
        const primaryTarget = ['_self', '_blank'].includes(String(settings.primaryTarget || '').trim()) ? String(settings.primaryTarget || '').trim() : '_self';
        const secondaryLabel = showSecondaryCta ? String(settings.secondaryLabel || '').trim() : '';
        const secondaryUrl = showSecondaryCta ? sanitizeUrl(settings.secondaryUrl) : '';
        const secondaryTarget = ['_self', '_blank'].includes(String(settings.secondaryTarget || '').trim()) ? String(settings.secondaryTarget || '').trim() : '_self';
        const placeholderTitle = String(settings.placeholderTitle || label('content_split_media_default_placeholder_title', '') || label('content_split_media_placeholder_title', '')).trim();
        const placeholderText = String(settings.placeholderText || label('content_split_media_default_placeholder_text', '') || label('content_split_media_placeholder_text', '')).trim();
        const emptyMessage = String(settings.emptyMessage || label('content_split_media_default_empty_message', '') || label('content_split_media_empty', '')).trim();
        const hasMedia = (mediaKind === 'image' && !!imageSrc) || (mediaKind === 'video' && !!videoUrl);

        const eyebrowStyle = resolveTextStyle(settings, 'eyebrowStyle', align);
        const titleStyle = resolveTextStyle(settings, 'titleStyle', align);
        const subtitleStyle = resolveTextStyle(settings, 'subtitleStyle', titleStyle.align);
        const bodyStyle = resolveTextStyle(settings, 'bodyStyle', subtitleStyle.align);
        const featureStyle = resolveTextStyle(settings, 'featureStyle', bodyStyle.align);

        let actionsHtml = '';
        if (primaryLabel || secondaryLabel) {
            actionsHtml = `<div class="pb-content-split-media-actions pb-content-split-media-actions-align-${escapeAttr(align)}">`;
            if (primaryLabel) {
                if (primaryUrl) {
                    const rel = primaryTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                    actionsHtml += `<a class="btn btn-primary pb-btn pb-btn-primary" href="${escapeAttr(primaryUrl)}" target="${escapeAttr(primaryTarget)}"${rel}>${escapeHtml(primaryLabel)}</a>`;
                } else {
                    actionsHtml += `<span class="btn btn-primary pb-btn pb-btn-primary is-static" aria-disabled="true">${escapeHtml(primaryLabel)}</span>`;
                }
            }
            if (secondaryLabel) {
                if (secondaryUrl) {
                    const rel = secondaryTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                    actionsHtml += `<a class="btn btn-ghost pb-btn pb-btn-ghost" href="${escapeAttr(secondaryUrl)}" target="${escapeAttr(secondaryTarget)}"${rel}>${escapeHtml(secondaryLabel)}</a>`;
                } else {
                    actionsHtml += `<span class="btn btn-ghost pb-btn pb-btn-ghost is-static" aria-disabled="true">${escapeHtml(secondaryLabel)}</span>`;
                }
            }
            actionsHtml += '</div>';
        }

        let featuresHtml = '';
        if (showFeatures && featureItems.length) {
            featuresHtml = '<ul class="pb-content-split-media-features">';
            featureItems.forEach((featureItem) => {
                featuresHtml += `<li class="pb-content-split-media-feature">${renderStyledText(featureItem, 'span', 'pb-content-split-media-feature-text', featureStyle)}</li>`;
            });
            featuresHtml += '</ul>';
        }

        let mediaHtml = '';
        if (mediaKind === 'image' && imageSrc) {
            mediaHtml = `<div class="pb-content-split-media-media-shell pb-content-split-media-media-shell-image"><div class="pb-content-split-media-media-inner"><img class="pb-content-split-media-image" src="${escapeAttr(imageSrc)}" alt="${escapeAttr(imageAlt || title)}"></div></div>`;
        } else if (mediaKind === 'video' && videoUrl) {
            const posterAttr = videoPoster ? ` poster="${escapeAttr(videoPoster)}"` : '';
            mediaHtml = `<div class="pb-content-split-media-media-shell pb-content-split-media-media-shell-video"><div class="pb-content-split-media-media-inner"><video class="pb-content-split-media-video" controls playsinline preload="${escapeAttr(preload)}"${autoplay ? ' autoplay' : ''}${loop ? ' loop' : ''}${muted ? ' muted' : ''}${posterAttr}><source src="${escapeAttr(videoUrl)}"></video></div></div>`;
        } else {
            mediaHtml = `<div class="pb-content-split-media-media-shell is-empty"><div class="pb-content-split-media-placeholder"><span class="pb-content-split-media-placeholder-icon" aria-hidden="true">◫</span><strong class="pb-content-split-media-placeholder-title">${escapeHtml(placeholderTitle)}</strong><p class="pb-content-split-media-placeholder-text">${escapeHtml(placeholderText)}</p></div></div>`;
        }

        let contentHtml = '';
        if (showEyebrow) {
            contentHtml += renderStyledText(eyebrow, 'p', 'pb-content-split-media-eyebrow', eyebrowStyle);
        }
        contentHtml += renderStyledText(title, 'h2', 'pb-content-split-media-title', titleStyle);
        contentHtml += renderStyledText(subtitle, 'p', 'pb-content-split-media-subtitle', subtitleStyle);
        if (showBody) {
            contentHtml += renderStyledParagraphs(body, 'pb-content-split-media-body', bodyStyle);
        }
        contentHtml += featuresHtml;
        contentHtml += actionsHtml;

        const contentText = contentHtml.replace(/<[^>]+>/g, '').trim();
        if (!hasMedia && !contentText) {
            contentHtml = `<div class="pb-empty">${escapeHtml(emptyMessage)}</div>`;
        }

        const html = `<section class="pb-content-split-media pb-content-split-media-variant-${escapeAttr(variant)} pb-content-split-media-align-${escapeAttr(align)} pb-content-split-media-text-valign-${escapeAttr(textVerticalAlign)} pb-content-split-media-media-${escapeAttr(mediaPosition)} pb-content-split-media-ratio-${escapeAttr(ratio)} pb-content-split-media-fit-${escapeAttr(mediaFit)}" data-content-split-media-preview-id="${escapeAttr(previewId)}"><div class="pb-content-split-media-frame"><div class="pb-content-split-media-content">${contentHtml}</div><div class="pb-content-split-media-media">${mediaHtml}</div></div></section>`;

        schedulePreviewDesignSync(previewId, 'data-content-split-media-preview-id', ['.pb-content-split-media-frame'], ['.pb-content-split-media-eyebrow', '.pb-content-split-media-title', '.pb-content-split-media-subtitle', '.pb-content-split-media-body', '.pb-content-split-media-body *', '.pb-content-split-media-feature-text'], resolveWidgetDesign(settings, 28));

        schedulePreviewSync(previewId, {
            eyebrowStyle,
            titleStyle,
            subtitleStyle,
            bodyStyle,
            featureStyle,
        });

        return html;
    };
})();
