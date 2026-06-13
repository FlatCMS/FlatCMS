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

    registry.faq_accordion = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || ''));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const label = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `fc-faq-accordion-preview-${Math.random().toString(36).slice(2, 10)}`;

        const parseRepeaterLines = (raw, delimiter = '\n---\n') => {
            if (typeof raw !== 'string' || String(raw).trim() === '') {
                return [];
            }

            const source = String(raw);
            const items = (delimiter && source.includes(delimiter)
                ? source.split(delimiter)
                : source.split(/\r\n|\r|\n/))
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

        const normalizeVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['subtle', 'strong', 'dashed'].includes(safe) ? safe : 'subtle';
        };

        const normalizeColumns = (value) => {
            const safe = Math.trunc(Number(value || 1)) || 1;
            return Math.max(1, Math.min(2, safe));
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

        const renderStyledHtml = (html, tagName, className, style) => {
            const safeHtml = String(html || '').trim();
            if (!safeHtml) {
                return '';
            }

            const content = `<div class="pb-styled-text-content pb-styled-text-content-rich">${safeHtml}</div>`;
            return `<${tagName} class="${escapeAttr(className)}">${injectIcon(content, style)}</${tagName}>`;
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

        const schedulePreviewStyleSync = (id, styleMap, columnsValue, attempts = 4) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-faq-accordion-preview-id="${id}"]`);
                if (!root) {
                    if (attempts > 0) {
                        schedulePreviewStyleSync(id, styleMap, columnsValue, attempts - 1);
                    }
                    return;
                }

                if (root instanceof HTMLElement) {
                    root.style.setProperty('--pb-faq-columns', String(normalizeColumns(columnsValue)));
                }

                applyTextStyle(root.querySelectorAll('.pb-faq-accordion-title'), styleMap.titleStyle);
                applyTextStyle(root.querySelectorAll('.pb-faq-accordion-subtitle'), styleMap.subtitleStyle);
                applyTextStyle(root.querySelectorAll('.pb-faq-accordion-question-label'), styleMap.questionStyle);
                applyTextStyle(root.querySelectorAll('.pb-faq-accordion-answer-copy'), styleMap.answerStyle);
                if (window.FlatCMSFaqAccordion && typeof window.FlatCMSFaqAccordion.init === 'function') {
                    window.FlatCMSFaqAccordion.init(root);
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

        const title = String(settings.title || label('faq_accordion_default_title', '')).trim();
        const subtitle = String(settings.subtitle || label('faq_accordion_default_subtitle', '')).trim();
        const questions = parseRepeaterLines(settings.questions || label('faq_accordion_default_questions', ''));
        const answers = parseRepeaterLines(settings.answers || label('faq_accordion_default_answers', ''));
        const showHeader = normalizeToggle(settings.showHeader, true);
        const openFirst = normalizeToggle(settings.openFirst, true);
        const align = normalizeAlign(settings.align, 'left');
        const variant = normalizeVariant(settings.variant || 'subtle');
        const columns = normalizeColumns(settings.columns || 1);

        const titleStyle = resolveTextStyle(settings, 'titleStyle', align);
        const subtitleStyle = resolveTextStyle(settings, 'subtitleStyle', titleStyle.align || align);
        const questionStyle = resolveTextStyle(settings, 'questionStyle', align);
        const answerStyle = resolveTextStyle(settings, 'answerStyle', questionStyle.align || align);

        const count = Math.min(Math.max(questions.length, answers.length, 1), 12);
        let itemsHtml = '';
        for (let index = 0; index < count; index += 1) {
            const question = String(questions[index] || '').trim();
            const answer = String(answers[index] || '').trim();
            if (!question && !answer) {
                continue;
            }

            const cardClass = variant === 'strong' ? 'pb-card pb-card-strong' : 'pb-card pb-card-subtle';
            const questionLabel = question || label('faq_accordion_fallback_question', '');
            const answerHtml = answer ? escapeHtml(answer).replace(/\n/g, '<br>') : '';
            const itemBaseId = `${previewId}-${index + 1}`;
            const toggleId = `faq-accordion-toggle-${itemBaseId}`;
            const panelId = `faq-accordion-panel-${itemBaseId}`;
            itemsHtml += `
                <article class="pb-faq-accordion-item ${cardClass}${openFirst && index === 0 ? ' is-active' : ''}" data-faq-accordion-item>
                    <button type="button" class="pb-faq-accordion-toggle" id="${escapeAttr(toggleId)}" aria-controls="${escapeAttr(panelId)}" aria-expanded="${openFirst && index === 0 ? 'true' : 'false'}">
                        <span class="pb-faq-accordion-icon pb-faq-accordion-icon-plus" aria-hidden="true">+</span>
                        <span class="pb-faq-accordion-icon pb-faq-accordion-icon-minus" aria-hidden="true">−</span>
                        ${renderStyledText(questionLabel, 'span', 'pb-faq-accordion-question-label', questionStyle)}
                    </button>
                    <div class="pb-faq-accordion-panel" id="${escapeAttr(panelId)}" role="region" aria-labelledby="${escapeAttr(toggleId)}"${openFirst && index === 0 ? '' : ' hidden'}>
                        <div class="pb-faq-accordion-panel-inner">
                            ${answerHtml ? renderStyledHtml(answerHtml, 'div', 'pb-faq-accordion-answer-copy', answerStyle) : ''}
                        </div>
                    </div>
                </article>
            `;
        }

        if (!itemsHtml) {
            itemsHtml = `<div class="pb-empty">${escapeHtml(label('faq_accordion_empty', ''))}</div>`;
        }

        let headerHtml = '';
        if (showHeader && (title || subtitle)) {
            headerHtml = `
                <header class="pb-faq-accordion-header">
                    ${renderStyledText(title, 'h2', 'pb-faq-accordion-title', titleStyle)}
                    ${renderStyledText(subtitle, 'p', 'pb-faq-accordion-subtitle', subtitleStyle)}
                </header>
            `;
        }

        schedulePreviewDesignSync(previewId, 'data-faq-accordion-preview-id', ['.pb-faq-accordion-item'], ['.pb-faq-accordion-title', '.pb-faq-accordion-subtitle', '.pb-faq-accordion-question-label', '.pb-faq-accordion-answer-copy', '.pb-faq-accordion-answer-copy *'], resolveWidgetDesign(settings, 16));

        schedulePreviewStyleSync(previewId, {
            titleStyle,
            subtitleStyle,
            questionStyle,
            answerStyle,
        }, columns);

        return `
            <section class="pb-faq-accordion pb-faq-accordion-variant-${escapeAttr(variant)} pb-faq-accordion-align-${escapeAttr(align)}" data-faq-accordion-preview-id="${escapeAttr(previewId)}">
                ${headerHtml}
                <div class="pb-faq-accordion-grid">
                    ${itemsHtml}
                </div>
            </section>
        `;
    };
})();
