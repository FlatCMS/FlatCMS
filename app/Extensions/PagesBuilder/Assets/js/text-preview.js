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

    registry.text = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const escapeHtml = helpers.escapeHtml || ((value) => String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;'));
        const label = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `fc-text-preview-${Math.random().toString(36).slice(2, 10)}`;
        const fallbackVideoLabel = label('text_video_fallback_link', 'Open video');

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

        const resolveTextStyle = (source, prefix, fallbackAlign) => {
            const safeSource = source && typeof source === 'object' ? source : {};
            const safePrefix = String(prefix || '').replace(/[^a-zA-Z0-9_]/g, '') || 'textStyle';
            const iconPosition = String(safeSource[`${safePrefix}IconPosition`] || 'start').trim().toLowerCase();
            return {
                align: normalizeAlign(safeSource[`${safePrefix}Align`], fallbackAlign),
                font: normalizeFont(safeSource[`${safePrefix}Font`]),
                size: normalizeFontSize(safeSource[`${safePrefix}Size`]),
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

        const renderStyledText = (text, className, style, tagName) => {
            const safeText = String(text || '').trim();
            if (!safeText) {
                return '';
            }
            const content = `<span class="pb-styled-text-content">${escapeHtml(safeText)}</span>`;
            return `<${tagName} class="${escapeAttr(className)}">${injectIcon(content, style)}</${tagName}>`;
        };

        const sanitizeRichText = (value) => String(value || '')
            .replace(/<\s*(script|style)[^>]*>[\s\S]*?<\s*\/\s*\1\s*>/gi, '');

        const extractAttribute = (attributes, name) => {
            const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            let match = attributes.match(new RegExp(`\\b${escaped}\\s*=\\s*"([^"]*)"`, 'i'));
            if (match) {
                return match[1] || '';
            }
            match = attributes.match(new RegExp(`\\b${escaped}\\s*=\\s*'([^']*)'`, 'i'));
            if (match) {
                return match[1] || '';
            }
            match = attributes.match(new RegExp(`\\b${escaped}\\s*=\\s*([^\\s>]+)`, 'i'));
            return match ? (match[1] || '') : '';
        };

        const sanitizeCssLength = (value) => {
            const safe = String(value || '').trim();
            return /^\d+(?:\.\d+)?(?:px|%|rem|em|vw|vh)$/i.test(safe) ? safe : '';
        };

        const extractPreferredWidth = (attributes) => {
            const percentage = extractAttribute(attributes, 'data-percentage');
            if (percentage) {
                const first = String(percentage).split(',')[0]?.trim() || '';
                const candidate = sanitizeCssLength(`${first}%`);
                if (candidate) {
                    return candidate;
                }
            }

            const size = extractAttribute(attributes, 'data-size');
            if (size) {
                const first = String(size).split(',')[0]?.trim() || '';
                const candidate = sanitizeCssLength(first);
                if (candidate) {
                    return candidate;
                }
            }

            return '';
        };

        const normalizeInlineStyle = (style, fallbackWidth = '') => {
            const source = String(style || '').trim();
            const parts = source ? source.split(/\s*;\s*/).filter(Boolean) : [];
            let width = '';
            for (const part of parts) {
                const [property, rawValue = ''] = part.split(':', 2);
                if (String(property || '').trim().toLowerCase() !== 'width') {
                    continue;
                }
                const candidate = sanitizeCssLength(rawValue);
                if (candidate) {
                    width = candidate;
                }
            }

            if (!width && fallbackWidth) {
                width = sanitizeCssLength(fallbackWidth);
            }

            return width ? `width: ${width};` : '';
        };

        const normalizeRichText = (value) => {
            let html = sanitizeRichText(value);
            if (!html) {
                return '';
            }

            const emptyParagraphPattern = /<p\b[^>]*>(?:\s|&nbsp;|&#160;|&#8203;|&#x200B;|​|<br\s*\/?>)*<\/p>/giu;
            html = html.replace(new RegExp(`${emptyParagraphPattern.source}\\s*(<(?:img|video)\\b)`, 'gi'), '$1');
            html = html.replace(new RegExp(`(<\\/video>|<img\\b[^>]*>)\\s*${emptyParagraphPattern.source}`, 'gi'), '$1');
            html = html.replace(/<p\b[^>]*>​<\/p>/gu, '');
            html = html.replace(/<p\b[^>]*>​<br\s*\/?><\/p>/giu, '');

            html = html.replace(/<img\b([^>]*)>/gi, (_full, attributes) => {
                const src = extractAttribute(attributes, 'src');
                if (!src) {
                    return '';
                }
                const alt = extractAttribute(attributes, 'alt');
                const style = normalizeInlineStyle(
                    extractAttribute(attributes, 'style'),
                    extractPreferredWidth(attributes)
                );

                const parts = [`src="${escapeAttr(src)}"`];
                if (alt) {
                    parts.push(`alt="${escapeAttr(alt)}"`);
                }
                if (style) {
                    parts.push(`style="${escapeAttr(style)}"`);
                }
                ['data-align', 'data-file-name', 'data-file-size', 'data-origin', 'data-size', 'data-percentage', 'data-proportion', 'data-rotate'].forEach((name) => {
                    const attrValue = extractAttribute(attributes, name);
                    if (attrValue) {
                        parts.push(`${name}="${escapeAttr(attrValue)}"`);
                    }
                });
                parts.push('loading="lazy"', 'decoding="async"');
                return `<img ${parts.join(' ')}>`;
            });

            html = html.replace(/<video\b([^>]*)>([\s\S]*?)<\/video>/gi, (_full, attributes, inner) => {
                const preferredWidth = extractPreferredWidth(attributes);
                const style = normalizeInlineStyle(
                    preferredWidth ? '' : extractAttribute(attributes, 'style'),
                    preferredWidth
                );

                const parts = ['controls', 'playsinline', 'preload="metadata"'];
                if (style) {
                    parts.push(`style="${escapeAttr(style)}"`);
                }
                ['data-align', 'data-file-name', 'data-file-size', 'data-origin', 'data-size', 'data-percentage', 'data-proportion', 'data-rotate', 'poster'].forEach((name) => {
                    const attrValue = extractAttribute(attributes, name);
                    if (attrValue) {
                        parts.push(`${name}="${escapeAttr(attrValue)}"`);
                    }
                });

                let firstSourceUrl = '';
                let sourceHtml = '';
                String(inner || '').replace(/<source\b([^>]*)>/gi, (_sourceFull, sourceAttributes) => {
                    const src = extractAttribute(sourceAttributes, 'src');
                    if (!src) {
                        return '';
                    }
                    const type = extractAttribute(sourceAttributes, 'type');
                    if (!firstSourceUrl) {
                        firstSourceUrl = src;
                    }
                    sourceHtml += `<source src="${escapeAttr(src)}"${type ? ` type="${escapeAttr(type)}"` : ''}>`;
                    return '';
                });

                if (!sourceHtml) {
                    const directSrc = extractAttribute(attributes, 'src');
                    if (directSrc) {
                        firstSourceUrl = directSrc;
                        sourceHtml = `<source src="${escapeAttr(directSrc)}">`;
                    }
                }

                const fallbackHtml = firstSourceUrl
                    ? `<a href="${escapeAttr(firstSourceUrl)}" target="_blank" rel="noopener noreferrer">${escapeHtml(fallbackVideoLabel)}</a>`
                    : '';

                return `<video ${parts.join(' ')}>${sourceHtml}${fallbackHtml}</video>`;
            });

            html = html.replace(new RegExp(`${emptyParagraphPattern.source}\\s*(<(?:img|video)\\b)`, 'giu'), '$1');
            html = html.replace(new RegExp(`(<\\/video>|<img\\b[^>]*>)\\s*${emptyParagraphPattern.source}`, 'giu'), '$1');
            html = html.replace(/<p\b[^>]*>​<\/p>/gu, '');
            html = html.replace(/<p\b[^>]*>​<br\s*\/?><\/p>/giu, '');

            if (typeof DOMParser === 'function' && /<li\b/i.test(html)) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(`<div data-pb-text-list-root="1">${html}</div>`, 'text/html');
                const root = doc.body.querySelector('[data-pb-text-list-root="1"]');
                if (root instanceof HTMLElement) {
                    root.querySelectorAll('li').forEach((item) => {
                        if (!(item instanceof HTMLElement)) {
                            return;
                        }

                        if (
                            item.childNodes.length === 1
                            && item.firstElementChild instanceof HTMLElement
                            && item.firstElementChild.classList.contains('pb-text-list-item-content')
                        ) {
                            return;
                        }

                        const wrapper = doc.createElement('div');
                        wrapper.className = 'pb-text-list-item-content';

                        while (item.firstChild) {
                            wrapper.appendChild(item.firstChild);
                        }

                        item.appendChild(wrapper);
                    });

                    html = root.innerHTML;
                }
            }

            return html;
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

        const title = String(settings.title || '').trim();
        const content = normalizeRichText(settings.text || label('text_default_html', '<p>New text</p>'));
        const showTitle = normalizeToggle(settings.showTitle, true);
        const align = normalizeAlign(settings.align);
        const legacyColor = normalizeColor(settings.color);
        const titleStyle = resolveTextStyle(settings, 'titleStyle', align);
        const bodyStyle = resolveTextStyle(settings, 'bodyStyle', align);
        if (legacyColor) {
            if (!titleStyle.color) {
                titleStyle.color = legacyColor;
            }
            if (!bodyStyle.color) {
                bodyStyle.color = legacyColor;
            }
        }

        const resolveListStyleClass = (style) => {
            const normalized = normalizeTextStyleList(style && style.list ? style.list : 'none');
            return normalized === 'none' ? 'disc' : normalized;
        };

        const applyListStyleClasses = (element, style) => {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            element.classList.remove(
                'pb-text-list-align-left',
                'pb-text-list-align-center',
                'pb-text-list-align-right',
                'pb-text-list-style-disc',
                'pb-text-list-style-circle',
                'pb-text-list-style-square'
            );

            element.classList.add(`pb-text-list-align-${normalizeAlign(style.align, 'left')}`);
            element.classList.add(`pb-text-list-style-${resolveListStyleClass(style)}`);
        };

        const applyTextStyle = (element, style) => {
            if (!(element instanceof HTMLElement)) {
                return;
            }
            element.style.textAlign = normalizeAlign(style.align, 'left');
            element.style.color = style.color || '';
            element.style.fontFamily = getFontFamily(style.font) || '';
            element.style.fontSize = style.size && style.size !== 'inherit' ? style.size : '';
            element.style.fontWeight = style.bold ? '700' : '';
            element.style.fontStyle = style.italic ? 'italic' : '';
            element.style.textDecoration = style.underline ? 'underline' : '';

            if (element.classList.contains('pb-text-inner')) {
                applyListStyleClasses(element, style);
            }
        };

        const syncAlignedLists = (scope) => {
            if (!(scope instanceof HTMLElement)) {
                return;
            }

            scope.querySelectorAll('.pb-text-inner').forEach((node) => {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                const lists = Array.from(node.querySelectorAll('ul, ol')).filter((list) => list instanceof HTMLElement);
                lists.forEach((list) => {
                    if (list instanceof HTMLElement) {
                        list.style.width = '';
                    }
                });

                if (!node.classList.contains('pb-text-list-align-center') || lists.length === 0) {
                    return;
                }

                const availableWidth = Math.floor(node.clientWidth);
                if (availableWidth <= 0) {
                    return;
                }

                const targetWidth = Math.min(
                    availableWidth,
                    lists.reduce((maxWidth, list) => {
                        if (!(list instanceof HTMLElement)) {
                            return maxWidth;
                        }
                        return Math.max(maxWidth, Math.ceil(list.getBoundingClientRect().width));
                    }, 0)
                );

                if (targetWidth <= 0) {
                    return;
                }

                lists.forEach((list) => {
                    if (list instanceof HTMLElement) {
                        list.style.width = `${targetWidth}px`;
                    }
                });
            });
        };

        const scheduleStyleSync = (attempts = 4) => {
            if (typeof window.requestAnimationFrame !== 'function') {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-text-preview-id="${previewId}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        scheduleStyleSync(attempts - 1);
                    }
                    return;
                }

                const titleNode = root.querySelector('.pb-text-title');
                if (titleNode instanceof HTMLElement) {
                    applyTextStyle(titleNode, titleStyle);
                }
                const bodyNode = root.querySelector('.pb-text-inner');
                applyTextStyle(bodyNode, bodyStyle);
                syncAlignedLists(root);
            });
        };

        scheduleStyleSync();
        schedulePreviewDesignSync(previewId, 'data-text-preview-id', ['.pb-text-block'], ['.pb-text-title', '.pb-text-inner', '.pb-text-inner *'], resolveWidgetDesign(settings, 16));

        const headerHtml = showTitle && title
            ? `<header class="pb-text-header">${renderStyledText(title, 'pb-text-title', titleStyle, 'h2')}</header>`
            : '';

        return `<section class="pb-text-block" data-text-preview-id="${escapeAttr(previewId)}">${headerHtml}<div class="pb-text-inner pb-text-list-align-${escapeAttr(normalizeAlign(bodyStyle.align, 'left'))} pb-text-list-style-${escapeAttr(resolveListStyleClass(bodyStyle))}">${content}</div></section>`;
    };
})();
