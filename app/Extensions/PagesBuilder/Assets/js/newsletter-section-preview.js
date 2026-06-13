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
    const readBuilderContactForms = (() => {
        let cachedForms = null;

        return () => {
            if (cachedForms !== null) {
                return cachedForms;
            }

            const configHolder = document.getElementById('pagesBuilderConfig');
            if (!(configHolder instanceof HTMLElement)) {
                cachedForms = [];
                return cachedForms;
            }

            try {
                const config = JSON.parse(configHolder.dataset.pagesBuilderConfig || '{}');
                const rawForms = Array.isArray(config.newsletterForms)
                    ? config.newsletterForms
                    : (Array.isArray(config.contactForms) ? config.contactForms : []);
                cachedForms = Array.isArray(rawForms)
                    ? rawForms.filter((entry) => entry && typeof entry === 'object')
                    : [];
            } catch (_error) {
                cachedForms = [];
            }

            return cachedForms;
        };
    })();

    const escapeFallback = (value) => String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const findContactFormConfigBySlug = (slug) => {
        const safeSlug = String(slug || '').trim().toLowerCase();
        if (!safeSlug) {
            return null;
        }

        const forms = readBuilderContactForms();
        for (let index = 0; index < forms.length; index += 1) {
            const item = forms[index];
            if (!item || typeof item !== 'object') {
                continue;
            }

            if (String(item.slug || '').trim().toLowerCase() === safeSlug) {
                return item;
            }
        }

        return null;
    };

    registry.newsletter_section = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || escapeFallback;
        const escapeAttr = helpers.escapeAttr || escapeFallback;
        const labelHelper = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `pb-newsletter-section-preview-${Math.random().toString(36).slice(2, 10)}`;

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

        const normalizeVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['subtle', 'strong', 'dark'].includes(safe) ? safe : 'subtle';
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
                    return `<p class="pb-newsletter-section-body-paragraph">${injectListMarker(injectIcon(content, style), style)}</p>`;
                });

            return paragraphs.length ? `<div class="${escapeAttr(className)}">${paragraphs.join('')}</div>` : '';
        };

        const applyTextStyle = (elements, style) => {
            Array.from(elements || []).forEach((element) => {
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
                if (element.matches('.pb-newsletter-section-eyebrow, .pb-newsletter-section-proof')) {
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
            });
        };

        const schedulePreviewSync = (id, styleMap, attempts = 5) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-newsletter-section-preview-id="${id}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        schedulePreviewSync(id, styleMap, attempts - 1);
                    }
                    return;
                }

                applyTextStyle(root.querySelectorAll('.pb-newsletter-section-eyebrow'), styleMap.eyebrowStyle);
                applyTextStyle(root.querySelectorAll('.pb-newsletter-section-title'), styleMap.titleStyle);
                applyTextStyle(root.querySelectorAll('.pb-newsletter-section-subtitle'), styleMap.subtitleStyle);
                applyTextStyle(root.querySelectorAll('.pb-newsletter-section-body'), styleMap.bodyStyle);
                const featureAlign = normalizeAlign(styleMap.featureStyle.align, 'left');
                const featureJustify = featureAlign === 'center'
                    ? 'center'
                    : (featureAlign === 'right' ? 'end' : 'start');
                const featureTextAlign = featureAlign === 'center' ? 'left' : featureAlign;
                const featureList = root.querySelector('.pb-newsletter-section-features');
                if (featureList instanceof HTMLElement) {
                    featureList.style.justifyItems = featureAlign === 'center' ? 'stretch' : featureJustify;
                    featureList.style.justifySelf = featureAlign === 'center' ? 'center' : '';
                    featureList.style.width = featureAlign === 'center' ? 'fit-content' : '';
                    featureList.style.maxWidth = featureAlign === 'center' ? '100%' : '';
                    featureList.style.textAlign = featureAlign === 'center' ? 'left' : '';
                }
                root.querySelectorAll('.pb-newsletter-section-feature-text').forEach((node) => {
                    applyTextStyle([node], styleMap.featureStyle);
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    node.style.textAlign = featureTextAlign;
                    node.style.flex = featureAlign === 'center' ? '1 1 auto' : '';
                    node.style.width = featureAlign === 'center' ? '100%' : '';
                });
                root.querySelectorAll('.pb-newsletter-section-feature').forEach((node) => {
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    node.style.color = styleMap.featureStyle.color || '';
                    node.style.flexDirection = featureAlign === 'right' ? 'row-reverse' : 'row';
                    node.style.justifySelf = featureAlign === 'center' ? 'stretch' : featureJustify;
                    node.style.width = featureAlign === 'center' ? '100%' : '';
                    node.style.maxWidth = featureAlign === 'center' ? '100%' : '';
                    node.style.textAlign = featureAlign === 'center' ? 'left' : '';
                });
                applyTextStyle(root.querySelectorAll('.pb-newsletter-section-proof'), styleMap.proofStyle);
                applyTextStyle(root.querySelectorAll('.pb-newsletter-section-form-title'), styleMap.formTitleStyle);
                applyTextStyle(root.querySelectorAll('.pb-newsletter-section-form-description'), styleMap.formDescriptionStyle);
                applyTextStyle(root.querySelectorAll('.pb-newsletter-section-helper'), styleMap.helperTextStyle);
            });
        };

        const parseFeatureItems = (value) => String(value || '')
            .replace(/\r\n?/g, '\n')
            .split('\n')
            .map((entry) => String(entry || '').replace(/^[-*•\s]+/u, '').trim())
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
        const showProof = normalizeToggle(settings.showProof, true);

        const eyebrow = String(settings.eyebrow || '').trim();
        const title = String(settings.title || '').trim();
        const subtitle = String(settings.subtitle || '').trim();
        const body = String(settings.body || '').trim();
        const featureItems = parseFeatureItems(settings.featureItems);
        const proofLabel = String(settings.proofLabel || '').trim();
        const formTitle = String(settings.formTitle || '').trim();
        const formDescription = String(settings.formDescription || '').trim();
        const emailLabel = String(settings.emailLabel || '').trim();
        const placeholder = String(settings.placeholder || '').trim();
        const selectedFormConfig = findContactFormConfigBySlug(settings.newsletterFormSlug || 'newsletter-rgpd');
        const showCaptcha = !!(selectedFormConfig && selectedFormConfig.captchaEnabled);
        const buttonLabel = String(settings.buttonLabel || '').trim()
            || String((selectedFormConfig && selectedFormConfig.submitLabel) || '').trim()
            || String(labelHelper('newsletter_section_default_button_label', '')).trim();
        const helperText = String(settings.helperText || '').trim();
        const consentLabel = String(settings.consentLabel || '').trim();
        const consentHelp = String(settings.consentHelp || '').trim();
        const consentLinksPrefix = String(settings.consentLinksPrefix || '').trim();
        const legalLinkLabel = String(settings.legalLinkLabel || '').trim();
        const privacyLinkLabel = String(settings.privacyLinkLabel || '').trim();
        const captchaLabel = String(settings.captchaLabel || '').trim();
        const formUnavailableMessage = String(settings.formUnavailableMessage || '').trim();
        const emptyMessage = String(settings.emptyMessage || '').trim();
        const align = normalizeAlign(settings.align, 'left');
        const variant = normalizeVariant(settings.variant);

        const styles = {
            eyebrowStyle: resolveTextStyle(settings, 'eyebrowStyle', align),
            titleStyle: resolveTextStyle(settings, 'titleStyle', align),
            subtitleStyle: resolveTextStyle(settings, 'subtitleStyle', align),
            bodyStyle: resolveTextStyle(settings, 'bodyStyle', align),
            featureStyle: resolveTextStyle(settings, 'featureStyle', align),
            proofStyle: resolveTextStyle(settings, 'proofStyle', align),
            formTitleStyle: resolveTextStyle(settings, 'formTitleStyle', align),
            formDescriptionStyle: resolveTextStyle(settings, 'formDescriptionStyle', align),
            helperTextStyle: resolveTextStyle(settings, 'helperTextStyle', align),
        };

        let contentHtml = '';
        if (showEyebrow) {
            contentHtml += renderStyledText(eyebrow, 'p', 'pb-newsletter-section-eyebrow', styles.eyebrowStyle);
        }
        contentHtml += renderStyledText(title, 'h2', 'pb-newsletter-section-title', styles.titleStyle);
        contentHtml += renderStyledText(subtitle, 'p', 'pb-newsletter-section-subtitle', styles.subtitleStyle);
        if (showBody) {
            contentHtml += renderStyledParagraphs(body, 'pb-newsletter-section-body', styles.bodyStyle);
        }
        if (showFeatures && featureItems.length) {
            contentHtml += `<ul class="pb-newsletter-section-features">${featureItems.map((featureItem) => `<li class="pb-newsletter-section-feature">${renderStyledText(featureItem, 'span', 'pb-newsletter-section-feature-text', styles.featureStyle)}</li>`).join('')}</ul>`;
        }
        if (showProof) {
            contentHtml += renderStyledText(proofLabel, 'p', 'pb-newsletter-section-proof', styles.proofStyle);
        }

        const formHtml = `
            <div class="pb-newsletter-section-panel pb-newsletter-section-form-panel">
                ${renderStyledText(formTitle, 'h3', 'pb-newsletter-section-form-title', styles.formTitleStyle)}
                ${renderStyledParagraphs(formDescription, 'pb-newsletter-section-form-description', styles.formDescriptionStyle)}
                <section class="flatcms-contact-native flatcms-contact-embed">
                    <form class="flatcms-contact-form flatcms-contact-native-form pb-form-contact pb-newsletter-section-form" action="#" method="post" novalidate>
                        <label class="pb-sr-only" for="${escapeAttr(previewId)}-newsletter-email">${escapeHtml(emailLabel || placeholder)}</label>
                        <div class="pb-newsletter-section-form-row">
                            <input id="${escapeAttr(previewId)}-newsletter-email" type="email" class="form-input pb-input pb-newsletter-section-input" placeholder="${escapeAttr(placeholder)}">
                            <button type="button" class="btn btn-primary pb-btn pb-btn-primary pb-newsletter-section-submit">${escapeHtml(buttonLabel)}</button>
                        </div>
                        <div class="pb-newsletter-section-consent">
                            <label class="pb-newsletter-section-consent-label">
                                <input type="checkbox">
                                <span class="pb-newsletter-section-consent-text">${escapeHtml(consentLabel)}</span>
                            </label>
                            <p class="pb-newsletter-section-consent-help">${escapeHtml(consentHelp)}</p>
                            <p class="pb-newsletter-section-consent-links">
                                <span>${escapeHtml(consentLinksPrefix)}</span>
                                <a class="pb-newsletter-section-text-link" href="#" target="_self"><span class="pb-newsletter-section-text-link-label">${escapeHtml(legalLinkLabel)}</span></a>
                                <span class="pb-newsletter-section-consent-separator" aria-hidden="true">&middot;</span>
                                <a class="pb-newsletter-section-text-link" href="#" target="_self"><span class="pb-newsletter-section-text-link-label">${escapeHtml(privacyLinkLabel)}</span></a>
                            </p>
                        </div>
                        ${showCaptcha ? `<div class="pb-newsletter-section-captcha pb-newsletter-section-captcha-preview" aria-hidden="true">
                            <span class="pb-newsletter-section-captcha-placeholder">${escapeHtml(captchaLabel)}</span>
                        </div>` : ''}
                    </form>
                </section>
                ${renderStyledParagraphs(helperText, 'pb-newsletter-section-helper', styles.helperTextStyle)}
            </div>
        `;

        let frameInner = `
            <div class="pb-newsletter-section-content">${contentHtml}</div>
            <div class="pb-newsletter-section-split">
                <div class="pb-newsletter-section-form-wrap">${formHtml}</div>
            </div>
        `;

        if (!String(contentHtml + formTitle + formDescription + buttonLabel + helperText).replace(/<[^>]+>/g, '').trim()) {
            frameInner = `<div class="pb-empty">${escapeHtml(emptyMessage || formUnavailableMessage)}</div>`;
        }

        schedulePreviewSync(previewId, styles);
        schedulePreviewDesignSync(previewId, 'data-newsletter-section-preview-id', ['.pb-newsletter-section-frame'], ['.pb-newsletter-section-eyebrow', '.pb-newsletter-section-title', '.pb-newsletter-section-subtitle', '.pb-newsletter-section-body', '.pb-newsletter-section-body *', '.pb-newsletter-section-feature-text', '.pb-newsletter-section-proof', '.pb-newsletter-section-form-title', '.pb-newsletter-section-form-description', '.pb-newsletter-section-helper'], resolveWidgetDesign(settings, 28));

        return `
            <section class="pb-newsletter-section pb-newsletter-section-variant-${escapeAttr(variant)} pb-newsletter-section-align-${escapeAttr(align)}" data-newsletter-section-preview-id="${escapeAttr(previewId)}">
                <div class="pb-newsletter-section-frame">
                    ${frameInner}
                </div>
            </section>
        `;
    };
})();
