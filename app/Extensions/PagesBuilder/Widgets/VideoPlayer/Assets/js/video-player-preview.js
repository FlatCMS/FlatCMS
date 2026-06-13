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

    registry.video_player = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || ''));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const resolveMedia = helpers.resolveImage || ((value) => String(value || ''));
        const label = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `fc-video-player-preview-${Math.random().toString(36).slice(2, 10)}`;

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

        const normalizeSkin = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['classic', 'soft', 'cinema'].includes(safe) ? safe : 'classic';
        };

        const normalizePreload = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['auto', 'metadata', 'none'].includes(safe) ? safe : 'metadata';
        };

        const normalizeHeight = (value) => {
            const safe = Math.trunc(Number(value || 420)) || 420;
            return Math.max(260, Math.min(720, safe));
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

        const sanitizeIconClass = (value) => {
            const normalized = String(value || '').trim().replace(/\s+/g, ' ');
            if (!normalized) {
                return '';
            }

            return normalized
                .split(' ')
                .map((token) => String(token || '').trim())
                .filter((token) => /^[a-zA-Z0-9_-]+$/.test(token))
                .join(' ');
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

        const schedulePreviewSync = (id, styleMap, heightValue, attempts = 10) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-video-player-preview-id="${id}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        window.setTimeout(() => {
                            schedulePreviewSync(id, styleMap, heightValue, attempts - 1);
                        }, 80);
                    }
                    return;
                }

                root.style.setProperty('--pb-video-player-height', `${normalizeHeight(heightValue)}px`);
                applyTextStyle(root.querySelectorAll('.pb-video-player-title'), styleMap.titleStyle);
                applyTextStyle(root.querySelectorAll('.pb-video-player-subtitle'), styleMap.subtitleStyle);

                const shell = root.querySelector('[data-video-player-shell]');
                const hasPreviewRuntime = window.FlatCMSVideoPlayer && typeof window.FlatCMSVideoPlayer.init === 'function';

                if (hasPreviewRuntime) {
                    window.FlatCMSVideoPlayer.init(root);
                }

                if (
                    attempts > 0
                    && shell instanceof HTMLElement
                    && !shell.classList.contains('is-enhanced')
                ) {
                    window.setTimeout(() => {
                        schedulePreviewSync(id, styleMap, heightValue, attempts - 1);
                    }, 80);
                }
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
            overlayColor: normalizeDesignColor(source.designOverlayColor || ''),
            overlayOpacity: normalizeDesignInt(source.designOverlayOpacity, 0, 0, 100),
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

        const applyDesignOverlay = (node, design) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }
            if (!design.useCustom) {
                node.style.backgroundColor = '';
                node.style.opacity = '';
                return;
            }
            const effectiveOverlayColor = design.overlayColor || '#000000';
            node.style.backgroundColor = effectiveOverlayColor;
            node.style.opacity = String(Math.max(0, Math.min(100, Number(design.overlayOpacity) || 0)) / 100);
        };

        const schedulePreviewDesignSync = (id, attribute, surfaceSelectors, textSelectors, overlaySelectors, design, attempts = 4) => {
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
                overlaySelectors.forEach((selector) => {
                    root.querySelectorAll(selector).forEach((node) => applyDesignOverlay(node, design));
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

        const title = String(settings.title || label('video_player_default_title', '')).trim();
        const subtitle = String(settings.subtitle || label('video_player_default_subtitle', '')).trim();
        const videoUrl = String(settings.videoUrl || '').trim();
        const posterImage = String(settings.posterImage || '').trim();
        const ambientMode = normalizeToggle(settings.ambientMode, false);
        const showHeader = normalizeToggle(settings.showHeader, true);
        const autoplay = ambientMode ? true : normalizeToggle(settings.autoplay, false);
        const loop = ambientMode ? true : normalizeToggle(settings.loop, false);
        const muted = ambientMode ? true : normalizeToggle(settings.muted, false);
        const preload = normalizePreload(settings.preload || 'metadata');
        const height = normalizeHeight(settings.height || 420);
        const align = normalizeAlign(settings.align || 'left', 'left');
        const skin = normalizeSkin(settings.skin || 'classic');

        const titleStyle = resolveTextStyle(settings, 'titleStyle', align);
        const subtitleStyle = resolveTextStyle(settings, 'subtitleStyle', titleStyle.align || align);

        const resolvedVideoUrl = videoUrl ? resolveMedia(videoUrl) : '';
        const resolvedPosterUrl = posterImage ? resolveMedia(posterImage) : '';

        let headerHtml = '';
        if (showHeader && (title || subtitle)) {
            headerHtml = `<header class="pb-video-player-header">${renderStyledText(title, 'h2', 'pb-video-player-title', titleStyle)}${renderStyledText(subtitle, 'p', 'pb-video-player-subtitle', subtitleStyle)}</header>`;
        }

        let playerHtml = '';
        if (resolvedVideoUrl) {
            playerHtml = `
                <div class="pb-video-player-shell${ambientMode ? ' is-ambient' : ''}" data-video-player-shell>
                    <div class="pb-video-player-stage">
                        <video class="pb-video-player-media" controls playsinline preload="${escapeAttr(preload)}"${autoplay ? ' autoplay' : ''}${loop ? ' loop' : ''}${muted ? ' muted' : ''}${resolvedPosterUrl ? ` poster="${escapeAttr(resolvedPosterUrl)}"` : ''}>
                            <source src="${escapeAttr(resolvedVideoUrl)}">
                        </video>
                        <div class="pb-video-player-design-overlay" aria-hidden="true"></div>
                        <button type="button" class="pb-video-player-big-play" data-video-player-big-play aria-label="${escapeAttr(label('video_player_control_play', ''))}"><span aria-hidden="true">▶</span></button>
                    </div>
                    <div class="pb-video-player-ui" data-video-player-ui>
                        <div class="pb-video-player-progress-wrap">
                            <input class="pb-video-player-progress" data-video-player-seek type="range" min="0" max="100" step="0.1" value="0" aria-label="${escapeAttr(label('video_player_control_progress', ''))}">
                        </div>
                        <div class="pb-video-player-controls">
                            <button type="button" class="pb-video-player-control-btn" data-video-player-toggle aria-label="${escapeAttr(label('video_player_control_play', ''))}"><span aria-hidden="true">▶</span></button>
                            <div class="pb-video-player-time"><span data-video-player-current>0:00</span><span class="pb-video-player-time-separator">/</span><span data-video-player-duration>0:00</span></div>
                            <button type="button" class="pb-video-player-control-btn" data-video-player-mute aria-label="${escapeAttr(label('video_player_control_mute', ''))}"><span aria-hidden="true">🔊</span></button>
                            <input class="pb-video-player-volume" data-video-player-volume type="range" min="0" max="1" step="0.05" value="${escapeAttr(muted ? '0' : '1')}" aria-label="${escapeAttr(label('video_player_control_volume', ''))}">
                            <span class="pb-video-player-controls-spacer" aria-hidden="true"></span>
                            <button type="button" class="pb-video-player-control-btn" data-video-player-fullscreen aria-label="${escapeAttr(label('video_player_control_fullscreen', ''))}"><span aria-hidden="true">⤢</span></button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            playerHtml = `
                <div class="pb-video-player-shell is-empty" data-video-player-shell>
                    <div class="pb-video-player-placeholder">
                        <span class="pb-video-player-placeholder-icon" aria-hidden="true">▶</span>
                        <strong class="pb-video-player-placeholder-title">${escapeHtml(label('video_player_placeholder_title', ''))}</strong>
                        <p class="pb-video-player-placeholder-text">${escapeHtml(label('video_player_placeholder_text', ''))}</p>
                    </div>
                </div>
            `;
        }

        schedulePreviewSync(previewId, { titleStyle, subtitleStyle }, height);
        schedulePreviewDesignSync(
            previewId,
            'data-video-player-preview-id',
            ['.pb-video-player-shell'],
            ['.pb-video-player-title', '.pb-video-player-subtitle', '.pb-video-player-placeholder-title', '.pb-video-player-placeholder-text'],
            ['.pb-video-player-design-overlay'],
            resolveWidgetDesign(settings, 20)
        );

        return `
            <section class="pb-video-player pb-video-player-skin-${escapeAttr(skin)} pb-video-player-align-${escapeAttr(align)}${ambientMode ? ' pb-video-player-mode-ambient' : ''}"
                data-video-player
                data-video-player-ambient="${ambientMode ? '1' : '0'}"
                data-video-player-preview-id="${escapeAttr(previewId)}"
                data-label-play="${escapeAttr(label('video_player_control_play', ''))}"
                data-label-pause="${escapeAttr(label('video_player_control_pause', ''))}"
                data-label-mute="${escapeAttr(label('video_player_control_mute', ''))}"
                data-label-unmute="${escapeAttr(label('video_player_control_unmute', ''))}"
                data-label-fullscreen="${escapeAttr(label('video_player_control_fullscreen', ''))}"
                data-label-exit-fullscreen="${escapeAttr(label('video_player_control_exit_fullscreen', ''))}">
                ${headerHtml}
                ${playerHtml}
                ${resolvedVideoUrl ? '' : `<div class="pb-empty">${escapeHtml(label('video_player_empty', ''))}</div>`}
            </section>
        `;
    };
})();
