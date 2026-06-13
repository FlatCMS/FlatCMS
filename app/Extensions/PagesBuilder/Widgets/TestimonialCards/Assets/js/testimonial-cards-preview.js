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

    registry.testimonial_cards = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || ''));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const resolveImage = helpers.resolveImage || ((value) => String(value || ''));
        const label = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `pb-testimonial-cards-preview-${Math.random().toString(36).slice(2, 10)}`;

        const parseRepeater = (raw, delimiter = '\n') => {
            if (typeof raw !== 'string' || String(raw).trim() === '') {
                return [];
            }

            const source = String(raw);
            const items = delimiter && source.includes(delimiter)
                ? source.split(delimiter)
                : source.split(/\r\n|\r|\n/);

            while (items.length && String(items[items.length - 1] || '').trim() === '') {
                items.pop();
            }

            return items.map((item) => String(item || '').trim());
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

        const normalizeVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['subtle', 'strong', 'dashed'].includes(safe) ? safe : 'subtle';
        };

        const normalizeColumns = (value) => {
            const safe = Math.trunc(Number(value || 3)) || 3;
            return Math.max(1, Math.min(3, safe));
        };

        const normalizeRating = (value) => {
            const safe = Math.trunc(Number(value || 0)) || 0;
            return Math.max(0, Math.min(5, safe));
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

        const getFontFamily = (value) => {
            const safe = normalizeTextStyleFont(value);
            if (safe === 'system') return 'var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif)';
            if (safe === 'sans') return '"Cabin", var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif)';
            if (safe === 'serif') return 'Georgia, "Times New Roman", Times, serif';
            if (safe === 'mono') return '"SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace';
            if (safe === 'display') return '"Cabin", var(--font-family-heading, var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif))';
            return '';
        };

        const buildInitials = (name) => {
            const safe = String(name || '').trim();
            if (!safe) {
                return 'FC';
            }

            const parts = safe.split(/\s+/);
            let initials = '';
            parts.forEach((part) => {
                if (!part || initials.length >= 2) {
                    return;
                }
                initials += part.charAt(0).toUpperCase();
            });
            return initials || 'FC';
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

        const schedulePreviewSync = (id, styleMap, attempts = 4) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-testimonial-cards-preview-id="${id}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        schedulePreviewSync(id, styleMap, attempts - 1);
                    }
                    return;
                }

                applyTextStyle(root.querySelectorAll('.pb-testimonial-cards-title'), styleMap.titleStyle);
                applyTextStyle(root.querySelectorAll('.pb-testimonial-cards-subtitle'), styleMap.subtitleStyle);
                applyTextStyle(root.querySelectorAll('.pb-testimonial-quote'), styleMap.quoteStyle);
                applyTextStyle(root.querySelectorAll('.pb-testimonial-name'), styleMap.nameStyle);
                applyTextStyle(root.querySelectorAll('.pb-testimonial-role'), styleMap.roleStyle);

                if (window.FlatCMSTestimonialCards && typeof window.FlatCMSTestimonialCards.init === 'function') {
                    window.FlatCMSTestimonialCards.init(root);
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

        const title = String(settings.title || label('testimonial_cards_default_title', '')).trim();
        const subtitle = String(settings.subtitle || label('testimonial_cards_default_subtitle', '')).trim();
        const quotes = parseRepeater(settings.quotes || label('testimonial_cards_default_quotes', ''), '\n---\n');
        const names = parseRepeater(settings.names || label('testimonial_cards_default_names', ''));
        const companies = parseRepeater(settings.companies || label('testimonial_cards_default_companies', ''));
        const roles = parseRepeater(settings.roles || label('testimonial_cards_default_roles', ''));
        const ratings = parseRepeater(settings.ratings || label('testimonial_cards_default_ratings', ''));
        const avatars = parseRepeater(settings.avatars || '');
        const links = parseRepeater(settings.links || '');
        const targets = parseRepeater(settings.targets || '');
        const showHeader = normalizeToggle(settings.showHeader, true);
        const showRatings = normalizeToggle(settings.showRatings, true);
        const showCompany = normalizeToggle(settings.showCompany, true);
        const showAvatars = normalizeToggle(settings.showAvatars, true);
        const columns = normalizeColumns(settings.columns || 3);
        const align = normalizeAlign(settings.align || 'left', 'left');
        const variant = normalizeVariant(settings.variant || 'subtle');

        const titleStyle = resolveTextStyle(settings, 'titleStyle', align);
        const subtitleStyle = resolveTextStyle(settings, 'subtitleStyle', titleStyle.align || align);
        const quoteStyle = resolveTextStyle(settings, 'quoteStyle', align);
        const nameStyle = resolveTextStyle(settings, 'nameStyle', align);
        const roleStyle = resolveTextStyle(settings, 'roleStyle', nameStyle.align || align);

        const count = Math.min(Math.max(quotes.length, names.length, companies.length, roles.length, ratings.length, avatars.length, links.length, 1), 20);

        let itemsHtml = '';
        let renderedCount = 0;
        const cloudItems = [];
        for (let index = 0; index < count; index += 1) {
            const quote = String(quotes[index] || '').trim();
            const name = String(names[index] || '').trim();
            const company = String(companies[index] || '').trim();
            const role = String(roles[index] || '').trim();
            const rating = normalizeRating(ratings[index] || 0);
            const avatar = String(avatars[index] || '').trim();
            const link = String(links[index] || '').trim();
            const target = ['_self', '_blank'].includes(String(targets[index] || '').trim()) ? String(targets[index] || '').trim() : '_self';

            if (!quote && !name && !company && !role && !avatar && rating === 0) {
                continue;
            }

            const avatarUrl = avatar ? resolveImage(avatar) : '';
            let ratingHtml = '';
            if (showRatings && rating > 0) {
                ratingHtml = `<div class="pb-testimonial-rating" aria-label="${escapeAttr(`${rating}/5`)}"><span class="pb-testimonial-rating-stars" aria-hidden="true">${escapeHtml(`${'★'.repeat(rating)}${'☆'.repeat(Math.max(0, 5 - rating))}`)}</span></div>`;
            }

            let avatarHtml = '';
            if (showAvatars) {
                avatarHtml = avatarUrl
                    ? `<span class="pb-testimonial-avatar pb-testimonial-avatar-image"><img class="pb-testimonial-avatar-image-el" src="${escapeAttr(avatarUrl)}" alt="${escapeAttr(name || label('testimonial_cards_fallback_name', ''))}"></span>`
                    : `<span class="pb-testimonial-avatar pb-testimonial-avatar-fallback" aria-hidden="true">${escapeHtml(buildInitials(name))}</span>`;

                if (cloudItems.length < 20) {
                    cloudItems.push({
                        url: avatarUrl,
                        name: name || label('testimonial_cards_fallback_name', ''),
                        initials: buildInitials(name),
                    });
                }
            }

            let nameHtml = renderStyledText(name || label('testimonial_cards_fallback_name', ''), 'h3', 'pb-testimonial-name', nameStyle);
            if (link && nameHtml) {
                const rel = target === '_blank' ? ' rel="noopener noreferrer"' : '';
                nameHtml = `<a class="pb-testimonial-author-link" href="${escapeAttr(link)}" target="${escapeAttr(target)}"${rel}>${nameHtml}</a>`;
            }
            const companyHtml = showCompany && company
                ? `<p class="pb-testimonial-company">${escapeHtml(company)}</p>`
                : '';

            const cardClass = variant === 'strong' ? 'pb-card pb-card-strong' : 'pb-card pb-card-subtle';
            itemsHtml += `<article class="pb-testimonial-card ${escapeAttr(cardClass)}" data-testimonial-slide="1">`;
            itemsHtml += `<div class="pb-testimonial-card-top"><span class="pb-testimonial-card-mark" aria-hidden="true">&ldquo;</span>${ratingHtml}</div>`;
            itemsHtml += `<div class="pb-testimonial-card-body">${renderStyledText(quote, 'blockquote', 'pb-testimonial-quote', quoteStyle)}</div>`;
            itemsHtml += `<footer class="pb-testimonial-footer"><div class="pb-testimonial-author">${avatarHtml}<div class="pb-testimonial-meta">${nameHtml}${companyHtml}${renderStyledText(role, 'p', 'pb-testimonial-role', roleStyle)}</div></div></footer>`;
            itemsHtml += '</article>';
            renderedCount += 1;
        }

        const isEmpty = !itemsHtml;
        if (!itemsHtml) {
            itemsHtml = `<div class="pb-empty">${escapeHtml(label('testimonial_cards_empty', ''))}</div>`;
        }

        let headerHtml = '';
        if (showHeader && (title || subtitle)) {
            headerHtml = `<header class="pb-testimonial-cards-header">${renderStyledText(title, 'h2', 'pb-testimonial-cards-title', titleStyle)}${renderStyledText(subtitle, 'p', 'pb-testimonial-cards-subtitle', subtitleStyle)}</header>`;
        }

        let cloudHtml = '';
        if (showAvatars && cloudItems.length > 0) {
            cloudHtml = '<div class="pb-testimonial-cloud" aria-hidden="true"><div class="pb-testimonial-cloud-lines"><canvas class="pb-testimonial-cloud-waves" data-testimonial-cloud-waves data-wave-count="10" data-amplitude="50" data-base-speed="0.005" data-wave-spacing="30" data-line-width="1" data-left-offset="0" data-right-offset="0" aria-hidden="true"></canvas></div>';
            cloudItems.forEach((item, index) => {
                const positionClass = `pb-testimonial-cloud-item-pos-${index % 10}`;
                if (item.url) {
                    cloudHtml += `<span class="pb-testimonial-cloud-item ${escapeAttr(positionClass)}"><span class="pb-testimonial-cloud-avatar pb-testimonial-cloud-avatar-image"><img class="pb-testimonial-cloud-avatar-image-el" src="${escapeAttr(item.url)}" alt="${escapeAttr(item.name || '')}"></span></span>`;
                } else {
                    cloudHtml += `<span class="pb-testimonial-cloud-item ${escapeAttr(positionClass)}"><span class="pb-testimonial-cloud-avatar pb-testimonial-cloud-avatar-fallback">${escapeHtml(item.initials || 'FC')}</span></span>`;
                }
            });
            cloudHtml += '</div>';
        }

        let controlsHtml = '';
        if (!isEmpty && renderedCount > 1) {
            const navPrevLabel = String(label('testimonial_cards_nav_prev', '') || '').trim();
            const navNextLabel = String(label('testimonial_cards_nav_next', '') || '').trim();
            controlsHtml = `
                <div class="pb-testimonial-cards-controls" data-testimonial-controls hidden>
                    <button type="button" class="pb-testimonial-cards-nav pb-testimonial-cards-nav-prev" data-testimonial-prev aria-label="${escapeAttr(navPrevLabel)}" title="${escapeAttr(navPrevLabel)}"><span aria-hidden="true">&#8249;</span></button>
                    <button type="button" class="pb-testimonial-cards-nav pb-testimonial-cards-nav-next" data-testimonial-next aria-label="${escapeAttr(navNextLabel)}" title="${escapeAttr(navNextLabel)}"><span aria-hidden="true">&#8250;</span></button>
                </div>
            `;
        }

        schedulePreviewSync(previewId, { titleStyle, subtitleStyle, quoteStyle, nameStyle, roleStyle });
        schedulePreviewDesignSync(previewId, 'data-testimonial-cards-preview-id', ['.pb-testimonial-card'], ['.pb-testimonial-cards-title', '.pb-testimonial-cards-subtitle', '.pb-testimonial-quote', '.pb-testimonial-name', '.pb-testimonial-company', '.pb-testimonial-role', '.pb-testimonial-rating-stars', '.pb-testimonial-card-mark'], resolveWidgetDesign(settings, 22));

        return `
            <section class="pb-testimonial-cards pb-testimonial-cards-variant-${escapeAttr(variant)} pb-testimonial-cards-align-${escapeAttr(align)} pb-testimonial-cards-visible-${escapeAttr(String(columns))}" data-testimonial-cards="1" data-testimonial-cards-preview-id="${escapeAttr(previewId)}">
                <div class="pb-testimonial-cards-shell${showHeader && (title || subtitle) ? ' has-header' : ''}">
                    ${headerHtml}
                    <div class="pb-testimonial-cards-stage">
                        ${cloudHtml}
                        <div class="pb-testimonial-cards-rail">
                            <div class="pb-testimonial-cards-track" data-testimonial-track>
                                ${itemsHtml}
                            </div>
                            ${controlsHtml}
                        </div>
                    </div>
                </div>
            </section>
        `;
    };
})();
