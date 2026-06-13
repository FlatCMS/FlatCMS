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

    registry.hero = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || ''));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const resolveImage = helpers.resolveImage || ((value) => String(value || ''));
        const labelHelper = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `fc-hero-preview-${Math.random().toString(36).slice(2, 10)}`;

        const normalizeToggle = (value, fallback) => {
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

        const alignToJustifySelf = (value) => {
            const safe = normalizeAlign(value, 'left');
            if (safe === 'center') return 'center';
            if (safe === 'right') return 'end';
            return 'start';
        };

        const normalizeHeadingTag = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(safe) ? safe : 'h2';
        };

        const normalizeHeight = (value) => {
            const num = Math.trunc(Number(value));
            if (!Number.isFinite(num)) {
                return 420;
            }
            return Math.max(260, Math.min(760, num));
        };

        const normalizeOverlay = (value) => {
            const num = Math.trunc(Number(value));
            if (!Number.isFinite(num)) {
                return 35;
            }
            return Math.max(0, Math.min(85, num));
        };

        const normalizeVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['default', 'soft', 'dark'].includes(safe) ? safe : 'soft';
        };

        const normalizeMediaFit = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['cover', 'contain'].includes(safe) ? safe : 'cover';
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

        const resolveTextStyle = (source, prefix, fallbackAlign) => {
            const safeSource = source && typeof source === 'object' ? source : {};
            const safePrefix = String(prefix || '').replace(/[^a-zA-Z0-9_]/g, '') || 'textStyle';
            const aliasPrefix = safePrefix === 'titleStyle'
                ? 'titleTextStyle'
                : (safePrefix === 'subtitleStyle' ? 'subtitleTextStyle' : '');
            const readStyleValue = (suffix) => {
                const primary = safeSource[`${safePrefix}${suffix}`];
                if (primary !== undefined && primary !== null && String(primary).trim() !== '') {
                    return primary;
                }
                return aliasPrefix !== '' ? safeSource[`${aliasPrefix}${suffix}`] : primary;
            };
            const iconPosition = String(readStyleValue('IconPosition') || 'start').trim().toLowerCase();

            return {
                align: normalizeAlign(readStyleValue('Align'), fallbackAlign),
                font: normalizeFont(readStyleValue('Font')),
                size: normalizeFontSize(readStyleValue('Size')),
                bold: normalizeToggle(readStyleValue('Bold'), false),
                italic: normalizeToggle(readStyleValue('Italic'), false),
                underline: normalizeToggle(readStyleValue('Underline'), false),
                color: normalizeColor(readStyleValue('Color')),
                list: normalizeTextStyleList(readStyleValue('List')),
                icon: sanitizeIconClass(readStyleValue('Icon')),
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

            const align = normalizeAlign(style.align, 'left');
            const attrs = [
                `class="${escapeAttr(className)} pb-preview-styled-text"`,
                `data-text-align="${escapeAttr(align)}"`,
                `data-text-font="${escapeAttr(normalizeFont(style.font))}"`,
                `data-text-size="${escapeAttr(normalizeFontSize(style.size))}"`,
                `data-text-bold="${normalizeToggle(style.bold, false) ? '1' : '0'}"`,
                `data-text-italic="${normalizeToggle(style.italic, false) ? '1' : '0'}"`,
                `data-text-underline="${normalizeToggle(style.underline, false) ? '1' : '0'}"`,
            ];
            if (style.color) {
                attrs.push(`data-text-color="${escapeAttr(style.color)}"`);
            }
            if (style.list && style.list !== 'none') {
                attrs.push(`data-text-list="${escapeAttr(style.list)}"`);
            }

            const content = `<span class="pb-styled-text-content pb-preview-text-content">${escapeHtml(safeText)}</span>`;
            return `<${tagName} ${attrs.join(' ')}>${injectListMarker(injectIcon(content, style), style)}</${tagName}>`;
        };

        const applyTextStyle = (element, style) => {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            const normalizedAlign = normalizeAlign(style.align, 'left');
            element.style.textAlign = normalizedAlign;
            element.style.justifySelf = alignToJustifySelf(normalizedAlign);
            element.style.color = style.color || '';
            element.style.fontFamily = getFontFamily(style.font) || '';
            element.style.fontSize = style.size !== 'inherit' ? style.size : '';

            element.querySelectorAll('.pb-styled-text-content, .pb-preview-text-content').forEach((node) => {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                node.style.fontWeight = style.bold ? '700' : '';
                node.style.fontStyle = style.italic ? 'italic' : '';
                node.style.textDecoration = style.underline ? 'underline' : '';
            });
        };

        const stripHtml = (value) => String(value || '')
            .replace(/<br\s*\/?>/gi, ' ')
            .replace(/<[^>]*>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

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

        const schedulePreviewSync = (id, payload, attempts = 4) => {
            if (!window.requestAnimationFrame) {
                return;
            }
            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-fc-hero-preview-id="${id}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        schedulePreviewSync(id, payload, attempts - 1);
                    }
                    return;
                }

                const hero = root.querySelector('.fc-hero');
                if (hero instanceof HTMLElement) {
                    hero.style.setProperty('--fc-hero-height', `${payload.height}px`);
                    hero.style.setProperty('--fc-hero-overlay-strength', String(payload.overlayStrength));
                    if (payload.design.useCustom) {
                        if (payload.design.surfaceColor) {
                            hero.style.setProperty('--fc-hero-base-bg', payload.design.surfaceColor);
                            hero.style.setProperty('--fc-hero-media-bg', payload.design.surfaceColor);
                        } else {
                            hero.style.removeProperty('--fc-hero-base-bg');
                            hero.style.removeProperty('--fc-hero-media-bg');
                        }
                        if (payload.design.textColor) {
                            hero.style.setProperty('--fc-hero-text-color', payload.design.textColor);
                            hero.style.setProperty('--fc-hero-subtitle-color', payload.design.textColor);
                        } else {
                            hero.style.removeProperty('--fc-hero-text-color');
                            hero.style.removeProperty('--fc-hero-subtitle-color');
                        }
                        hero.style.borderRadius = `${payload.design.radius}px`;
                        if (payload.design.borderStyle !== 'inherit') {
                            hero.style.borderStyle = payload.design.borderStyle;
                            hero.style.borderWidth = `${payload.design.borderWidth}px`;
                        } else {
                            hero.style.borderStyle = '';
                            hero.style.borderWidth = '';
                        }
                        if (payload.design.borderColor) {
                            hero.style.borderColor = payload.design.borderColor;
                            if (payload.design.borderStyle === 'inherit') {
                                hero.style.borderWidth = `${payload.design.borderWidth}px`;
                            }
                        } else {
                            hero.style.borderColor = '';
                        }
                        hero.style.boxShadow = resolveShadowValue(payload.design.shadowPreset);
                    } else {
                        hero.style.removeProperty('--fc-hero-base-bg');
                        hero.style.removeProperty('--fc-hero-media-bg');
                        hero.style.removeProperty('--fc-hero-text-color');
                        hero.style.removeProperty('--fc-hero-subtitle-color');
                        hero.style.borderRadius = '';
                        hero.style.borderStyle = '';
                        hero.style.borderWidth = '';
                        hero.style.borderColor = '';
                        hero.style.boxShadow = '';
                    }
                }

                const content = root.querySelector('.fc-hero-content');
                if (content instanceof HTMLElement) {
                    content.style.minHeight = `${payload.height}px`;
                    content.style.textAlign = payload.contentAlign;
                    content.style.justifyItems = alignToJustifySelf(payload.contentAlign);
                }

                const title = root.querySelector('.fc-hero-title');
                applyTextStyle(title, payload.titleStyle);

                const subtitle = root.querySelector('.fc-hero-subtitle');
                applyTextStyle(subtitle, payload.subtitleStyle);
            });
        };

        const title = String(settings.title || labelHelper('hero_default_title', '')).trim();
        const subtitle = stripHtml(String(settings.subtitle || labelHelper('hero_default_subtitle', '')).trim());
        const showPrimaryCta = normalizeToggle(settings.showPrimaryCta, true);
        const showSecondaryCta = normalizeToggle(settings.showSecondaryCta, true);
        const primaryLabel = showPrimaryCta ? String(settings.primaryLabel || labelHelper('hero_default_primary_label', '')).trim() : '';
        const secondaryLabel = showSecondaryCta ? String(settings.secondaryLabel || labelHelper('hero_default_secondary_label', '')).trim() : '';
        const primaryUrl = sanitizeUrl(settings.primaryUrl);
        const secondaryUrl = sanitizeUrl(settings.secondaryUrl);
        const primaryTarget = ['_self', '_blank'].includes(String(settings.primaryTarget || '').trim()) ? String(settings.primaryTarget || '').trim() : '_self';
        const secondaryTarget = ['_self', '_blank'].includes(String(settings.secondaryTarget || '').trim()) ? String(settings.secondaryTarget || '').trim() : '_self';
        const backgroundImage = resolveImage(String(settings.backgroundImage || '').trim());
        const height = normalizeHeight(settings.height);
        const overlay = normalizeOverlay(settings.overlay);
        const overlayStrength = Math.min(1, overlay / 85);
        const legacyContentAlign = String(settings.align || '').trim() !== ''
            ? String(settings.align || '').trim()
            : String(settings.contentAlign || 'left');
        const headingTag = normalizeHeadingTag(settings.headingTag || 'h2');
        const contentAlign = normalizeAlign(legacyContentAlign, 'left');
        const actionsAlign = normalizeAlign(settings.align, 'left');
        const variant = normalizeVariant(settings.variant);
        const mediaFit = normalizeMediaFit(settings.mediaFit);
        const useCustomDesign = normalizeToggle(settings.useCustomDesign, false);
        const designSurfaceColor = normalizeColor(settings.designSurfaceColor);
        const designTextColor = normalizeColor(settings.designTextColor);
        const designBorderStyle = normalizeBorderStyle(settings.designBorderStyle);
        const designBorderWidth = normalizeClampedInt(settings.designBorderWidth, 0, 0, 8);
        const designBorderColor = normalizeColor(settings.designBorderColor);
        const designRadius = normalizeClampedInt(settings.designRadius, 12, 0, 40);
        const designShadow = normalizeShadowPreset(settings.designShadow);
        const titleStyle = resolveTextStyle(settings, 'titleStyle', contentAlign);
        const subtitleStyle = resolveTextStyle(settings, 'subtitleStyle', titleStyle.align || contentAlign);

        schedulePreviewSync(previewId, {
            height,
            overlayStrength,
            contentAlign,
            titleStyle,
            subtitleStyle,
            design: {
                useCustom: useCustomDesign,
                surfaceColor: designSurfaceColor,
                textColor: designTextColor,
                borderStyle: designBorderStyle,
                borderWidth: designBorderWidth,
                borderColor: designBorderColor,
                radius: designRadius,
                shadowPreset: designShadow,
            },
        });

        const hasMedia = backgroundImage !== '';
        const mediaNode = hasMedia
            ? `<div class="fc-hero-media"><img class="fc-hero-media-image" src="${escapeAttr(backgroundImage)}" alt=""></div>`
            : '';

        let primaryButton = '';
        if (primaryLabel) {
            if (primaryUrl) {
                const rel = primaryTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                primaryButton = `<a class="btn btn-primary pb-btn pb-btn-primary" href="${escapeAttr(primaryUrl)}" target="${escapeAttr(primaryTarget)}"${rel}>${escapeHtml(primaryLabel)}</a>`;
            } else {
                primaryButton = `<span class="btn btn-primary pb-btn pb-btn-primary is-static" aria-disabled="true">${escapeHtml(primaryLabel)}</span>`;
            }
        }

        let secondaryButton = '';
        if (secondaryLabel) {
            if (secondaryUrl) {
                const rel = secondaryTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                secondaryButton = `<a class="btn btn-ghost pb-btn pb-btn-ghost" href="${escapeAttr(secondaryUrl)}" target="${escapeAttr(secondaryTarget)}"${rel}>${escapeHtml(secondaryLabel)}</a>`;
            } else {
                secondaryButton = `<span class="btn btn-ghost pb-btn pb-btn-ghost is-static" aria-disabled="true">${escapeHtml(secondaryLabel)}</span>`;
            }
        }

        const emptyState = title === '' && subtitle === '' && primaryButton === '' && secondaryButton === '' && !hasMedia
            ? `<div class="pb-empty">${escapeHtml(labelHelper('hero_empty', 'Hero'))}</div>`
            : '';

        return `<div class="fc-hero-wrapper" data-fc-hero-preview-id="${escapeAttr(previewId)}"><section class="fc-hero fc-hero-variant-${escapeAttr(variant)} fc-hero-media-fit-${escapeAttr(mediaFit)}${hasMedia ? ' fc-hero-has-media' : ''}">${mediaNode}<div class="fc-hero-overlay"></div><div class="fc-hero-content fc-hero-content-align-${escapeAttr(contentAlign)}">${renderStyledText(title, headingTag, 'fc-hero-title', titleStyle)}${renderStyledText(subtitle, 'p', 'fc-hero-subtitle', subtitleStyle)}${(primaryButton || secondaryButton) ? `<div class="fc-hero-actions fc-hero-actions-align-${escapeAttr(actionsAlign)}">${primaryButton}${secondaryButton}</div>` : ''}${emptyState}</div></section></div>`;
    };
})();
