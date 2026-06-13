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
                cachedForms = Array.isArray(config.contactForms)
                    ? config.contactForms.filter((entry) => entry && typeof entry === 'object')
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

    registry.contact_section = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || escapeFallback;
        const escapeAttr = helpers.escapeAttr || escapeFallback;
        const labelHelper = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `pb-contact-section-preview-${Math.random().toString(36).slice(2, 10)}`;

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
                    return `<p class="pb-contact-section-body-paragraph">${injectListMarker(injectIcon(content, style), style)}</p>`;
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
                if (element.classList.contains('pb-contact-section-eyebrow') || element.classList.contains('pb-contact-section-proof')) {
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
                const root = document.querySelector(`[data-contact-section-preview-id="${id}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        schedulePreviewSync(id, styleMap, attempts - 1);
                    }
                    return;
                }

                applyTextStyle(root.querySelectorAll('.pb-contact-section-eyebrow'), styleMap.eyebrowStyle);
                applyTextStyle(root.querySelectorAll('.pb-contact-section-title'), styleMap.titleStyle);
                applyTextStyle(root.querySelectorAll('.pb-contact-section-subtitle'), styleMap.subtitleStyle);
                applyTextStyle(root.querySelectorAll('.pb-contact-section-body'), styleMap.bodyStyle);
                const featureAlign = normalizeAlign(styleMap.featureStyle.align, 'left');
                const featureJustify = featureAlign === 'center'
                    ? 'center'
                    : (featureAlign === 'right' ? 'end' : 'start');
                const featureTextAlign = featureAlign === 'center' ? 'left' : featureAlign;
                const featureList = root.querySelector('.pb-contact-section-features');
                if (featureList instanceof HTMLElement) {
                    featureList.style.justifyItems = featureAlign === 'center' ? 'stretch' : featureJustify;
                    featureList.style.justifySelf = featureAlign === 'center' ? 'center' : '';
                    featureList.style.width = featureAlign === 'center' ? 'fit-content' : '';
                    featureList.style.maxWidth = featureAlign === 'center' ? '100%' : '';
                    featureList.style.textAlign = featureAlign === 'center' ? 'left' : '';
                }
                root.querySelectorAll('.pb-contact-section-feature-text').forEach((node) => {
                    applyTextStyle([node], styleMap.featureStyle);
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    node.style.textAlign = featureTextAlign;
                    node.style.flex = featureAlign === 'center' ? '1 1 auto' : '';
                    node.style.width = featureAlign === 'center' ? '100%' : '';
                });
                root.querySelectorAll('.pb-contact-section-feature').forEach((node) => {
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
                applyTextStyle(root.querySelectorAll('.pb-contact-section-proof'), styleMap.proofStyle);
                applyTextStyle(root.querySelectorAll('.pb-contact-section-form-title'), styleMap.formTitleStyle);
                applyTextStyle(root.querySelectorAll('.pb-contact-section-form-description'), styleMap.formDescriptionStyle);
                applyTextStyle(root.querySelectorAll('.pb-contact-section-helper'), styleMap.helperTextStyle);

                root.querySelectorAll('form[data-preview-static="true"]').forEach((formNode) => {
                    if (!(formNode instanceof HTMLFormElement) || formNode.dataset.previewStaticBound === 'true') {
                        return;
                    }

                    formNode.dataset.previewStaticBound = 'true';
                    formNode.addEventListener('submit', (event) => {
                        event.preventDefault();
                    });
                });
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

                const formVariableMap = {
                    '--pb-contact-form-shell-bg': design.useCustom && design.surfaceColor ? design.surfaceColor : '',
                    '--pb-contact-form-input-bg': design.useCustom && design.surfaceColor ? design.surfaceColor : '',
                    '--pb-contact-form-shell-border': design.useCustom && design.borderColor ? design.borderColor : '',
                    '--pb-contact-form-input-border': design.useCustom && design.borderColor ? design.borderColor : '',
                    '--pb-contact-form-label-color': design.useCustom && design.textColor ? design.textColor : '',
                    '--pb-contact-form-input-color': design.useCustom && design.textColor ? design.textColor : '',
                    '--pb-contact-form-placeholder-color': design.useCustom && design.textColor ? design.textColor : '',
                    '--pb-contact-form-shell-radius': design.useCustom ? `${Math.max(0, Number(design.radius || 28) - 6)}px` : '',
                };

                Object.entries(formVariableMap).forEach(([property, value]) => {
                    if (!value) {
                        root.style.removeProperty(property);
                        return;
                    }
                    root.style.setProperty(property, value);
                });
            });
        };

        const normalizePreviewFieldType = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            const allowed = ['text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date'];
            return allowed.includes(safe) ? safe : 'text';
        };

        const normalizePreviewFieldWidth = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return safe === 'half' ? 'half' : 'full';
        };

        const normalizePreviewOptions = (value) => {
            if (Array.isArray(value)) {
                return value
                    .map((entry) => String(entry || '').trim())
                    .filter((entry) => entry !== '');
            }

            if (typeof value === 'string') {
                return value
                    .split(/\r\n|\r|\n|,|;/)
                    .map((entry) => String(entry || '').trim())
                    .filter((entry) => entry !== '');
            }

            return [];
        };

        const findContactFormConfigBySlug = (slug) => {
            const forms = readBuilderContactForms();
            if (!forms.length) {
                return null;
            }

            const requestedSlug = String(slug || '').trim().toLowerCase();
            if (requestedSlug !== '') {
                const exactMatch = forms.find((entry) => String(entry.slug || '').trim().toLowerCase() === requestedSlug);
                if (exactMatch) {
                    return exactMatch;
                }
            }

            const defaultForm = forms.find((entry) => !!entry.isDefault && String(entry.slug || '').trim() !== '');
            if (defaultForm) {
                return defaultForm;
            }

            const firstForm = forms.find((entry) => String(entry.slug || '').trim() !== '');
            return firstForm || null;
        };

        const normalizePreviewFields = (formConfig) => {
            const rawFields = Array.isArray(formConfig && formConfig.previewFields) ? formConfig.previewFields : [];
            const fields = rawFields
                .map((rawField) => {
                    if (!rawField || typeof rawField !== 'object') {
                        return null;
                    }

                    const key = String(rawField.key || '').trim();
                    const labelText = String(rawField.label || '').trim();
                    if (key === '' || labelText === '') {
                        return null;
                    }

                    return {
                        key,
                        label: labelText,
                        type: normalizePreviewFieldType(rawField.type),
                        required: normalizeToggle(rawField.required, false),
                        width: normalizePreviewFieldWidth(rawField.width),
                        placeholder: String(rawField.placeholder || '').trim(),
                        help: String(rawField.help || '').trim(),
                        options: normalizePreviewOptions(rawField.options),
                    };
                })
                .filter((field) => !!field);

            if (fields.length > 0) {
                return fields;
            }

            return [
                {
                    key: 'name',
                    label: labelHelper('contact_section_preview_name_label', 'Nom complet'),
                    type: 'text',
                    required: true,
                    width: 'half',
                    placeholder: labelHelper('contact_section_preview_name_placeholder', 'Votre nom'),
                    help: '',
                    options: [],
                },
                {
                    key: 'email',
                    label: labelHelper('contact_section_preview_email_label', 'Adresse e-mail'),
                    type: 'email',
                    required: true,
                    width: 'half',
                    placeholder: labelHelper('contact_section_preview_email_placeholder', 'vous@entreprise.fr'),
                    help: '',
                    options: [],
                },
                {
                    key: 'subject',
                    label: labelHelper('contact_section_preview_subject_label', 'Sujet'),
                    type: 'text',
                    required: true,
                    width: 'full',
                    placeholder: labelHelper('contact_section_preview_subject_placeholder', 'Votre sujet'),
                    help: '',
                    options: [],
                },
                {
                    key: 'message',
                    label: labelHelper('contact_section_preview_message_label', 'Message'),
                    type: 'textarea',
                    required: true,
                    width: 'full',
                    placeholder: labelHelper('contact_section_preview_message_placeholder', 'Décrivez votre besoin'),
                    help: '',
                    options: [],
                },
            ];
        };

        const normalizePreviewAttachments = (formConfig) => {
            const raw = formConfig && typeof formConfig === 'object' && formConfig.attachments && typeof formConfig.attachments === 'object'
                ? formConfig.attachments
                : {};
            const maxFiles = Math.max(1, Math.min(5, Math.round(Number(raw.maxFiles || 1) || 1)));
            const maxSizeMb = Math.max(1, Math.min(25, Math.round(Number(raw.maxSizeMb || 5) || 5)));
            const extensions = normalizePreviewOptions(raw.extensions).slice(0, 12);

            return {
                enabled: normalizeToggle(raw.enabled, false),
                required: normalizeToggle(raw.required, false),
                maxFiles,
                maxSizeMb,
                extensions,
            };
        };

        const renderPreviewFieldControl = (field, fieldId) => {
            const safeType = normalizePreviewFieldType(field.type);
            const placeholder = escapeAttr(String(field.placeholder || '').trim());
            const options = Array.isArray(field.options) ? field.options : [];
            const inertAttrs = ' data-preview-static-control="true" tabindex="-1" aria-disabled="true"';

            if (safeType === 'textarea') {
                return `<textarea id="${escapeAttr(fieldId)}" class="form-input pb-input flatcms-contact-message" rows="4" placeholder="${placeholder}"${inertAttrs}></textarea>`;
            }

            if (safeType === 'select') {
                const emptyOption = escapeHtml(String(labelHelper('contactFormSelectPlaceholder', '')));
                const optionsHtml = options
                    .map((option) => `<option value="${escapeAttr(option)}">${escapeHtml(option)}</option>`)
                    .join('');
                return `<select id="${escapeAttr(fieldId)}" class="form-input pb-input"${inertAttrs}><option value="">${emptyOption}</option>${optionsHtml}</select>`;
            }

            if (safeType === 'radio' || safeType === 'checkbox') {
                const inputType = safeType;
                const choiceValues = options.length > 0 ? options : [String(field.label || '')];
                const choicesHtml = choiceValues.map((option, index) => {
                    const choiceId = `${fieldId}_${index + 1}`;
                    return `
                        <label class="flatcms-contact-choice-item" for="${escapeAttr(choiceId)}">
                            <input id="${escapeAttr(choiceId)}" type="${escapeAttr(inputType)}"${inertAttrs}>
                            <span>${escapeHtml(option)}</span>
                        </label>
                    `;
                }).join('');

                return `<div class="flatcms-contact-choice-list">${choicesHtml}</div>`;
            }

            const inputTypeMap = {
                text: 'text',
                email: 'email',
                tel: 'tel',
                url: 'url',
                number: 'number',
                date: 'date',
            };
            const inputType = Object.prototype.hasOwnProperty.call(inputTypeMap, safeType)
                ? inputTypeMap[safeType]
                : 'text';
            return `<input id="${escapeAttr(fieldId)}" type="${escapeAttr(inputType)}" class="form-input pb-input" placeholder="${placeholder}"${inertAttrs}>`;
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
        const helperText = String(settings.helperText || '').trim();
        const formUnavailableMessage = String(settings.formUnavailableMessage || '').trim();
        const emptyMessage = String(settings.emptyMessage || '').trim();
        const align = normalizeAlign(settings.align, 'left');
        const textVerticalAlign = normalizeVerticalAlign(settings.textVerticalAlign, 'center');
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
            contentHtml += renderStyledText(eyebrow, 'p', 'pb-contact-section-eyebrow', styles.eyebrowStyle);
        }
        contentHtml += renderStyledText(title, 'h2', 'pb-contact-section-title', styles.titleStyle);
        contentHtml += renderStyledText(subtitle, 'p', 'pb-contact-section-subtitle', styles.subtitleStyle);
        if (showBody) {
            contentHtml += renderStyledParagraphs(body, 'pb-contact-section-body', styles.bodyStyle);
        }
        if (showFeatures && featureItems.length) {
            contentHtml += `<ul class="pb-contact-section-features">${featureItems.map((featureItem) => `<li class="pb-contact-section-feature">${renderStyledText(featureItem, 'span', 'pb-contact-section-feature-text', styles.featureStyle)}</li>`).join('')}</ul>`;
        }
        if (showProof) {
            contentHtml += renderStyledText(proofLabel, 'p', 'pb-contact-section-proof', styles.proofStyle);
        }

        const selectedFormConfig = findContactFormConfigBySlug(settings.contactFormSlug || settings.formSlug || 'contact-main');
        let formEmbedHtml = '';

        if (selectedFormConfig) {
            const submitLabel = String(selectedFormConfig.submitLabel || '').trim()
                || labelHelper('contact_section_preview_submit_label', 'Envoyer le message');
            const fields = normalizePreviewFields(selectedFormConfig);
            const attachments = normalizePreviewAttachments(selectedFormConfig);
            const fieldsHtml = fields.map((field, index) => {
                const fieldId = `pbContactSectionField_${index + 1}`;
                const isFullWidth = normalizePreviewFieldWidth(field.width) === 'full';
                const requiredMark = field.required ? '<span class="flatcms-contact-required-mark" aria-hidden="true">*</span>' : '';
                const help = String(field.help || '').trim();

                return `
                    <div class="flatcms-contact-custom-field${isFullWidth ? ' flatcms-contact-custom-field--full' : ''}">
                        <div class="form-group">
                            <label class="form-label" for="${escapeAttr(fieldId)}">
                                ${escapeHtml(field.label)}${requiredMark}
                            </label>
                            ${renderPreviewFieldControl(field, fieldId)}
                            ${help !== '' ? `<small class="flatcms-contact-hint">${escapeHtml(help)}</small>` : ''}
                        </div>
                    </div>
                `;
            }).join('');

            const attachmentMeta = [];
            attachmentMeta.push(String(attachments.maxFiles));
            attachmentMeta.push(`${attachments.maxSizeMb} MB`);
            if (attachments.extensions.length) {
                attachmentMeta.push(attachments.extensions.join(', '));
            }

            const attachmentsHtml = attachments.enabled
                ? `
                    <div class="form-group" data-contact-attachments>
                        <label class="form-label">
                            ${escapeHtml(String(labelHelper('contactFormAttachmentsInputLabel', 'Ajouter des pièces jointes')))}
                            ${attachments.required ? '<span class="flatcms-contact-required-mark" aria-hidden="true">*</span>' : ''}
                        </label>
                        <input type="file" class="form-input pb-input flatcms-contact-attachments-input" data-preview-static-control="true" tabindex="-1" aria-disabled="true">
                        ${attachmentMeta.length ? `<small class="flatcms-contact-hint">${escapeHtml(attachmentMeta.join(' · '))}</small>` : ''}
                    </div>
                `
                : '';

            formEmbedHtml = `
                <section class="flatcms-contact-native flatcms-contact-embed">
                    <form class="flatcms-contact-form flatcms-contact-native-form pb-contact-section-form" action="/contact/send" method="post" data-preview-static="true" novalidate>
                        <div class="flatcms-contact-custom-grid">
                            ${fieldsHtml}
                        </div>
                        ${attachmentsHtml}
                        <button type="submit" class="btn btn-primary pb-btn pb-btn-primary pb-contact-section-submit" data-preview-static-control="true" tabindex="-1" aria-disabled="true">${escapeHtml(submitLabel)}</button>
                    </form>
                </section>
            `;
        } else {
            const fallbackMessage = formUnavailableMessage || labelHelper(
                'contact_section_default_form_unavailable_message',
                'Créez ou activez le formulaire Contact « contact-main » pour finaliser cette section.'
            );
            formEmbedHtml = `<div class="pb-contact-section-form-unavailable"><p class="pb-contact-section-form-unavailable-text">${escapeHtml(fallbackMessage)}</p></div>`;
        }

        const formHtml = `
            <div class="pb-contact-section-panel pb-contact-section-form-panel">
                ${renderStyledText(formTitle, 'h3', 'pb-contact-section-form-title', styles.formTitleStyle)}
                ${renderStyledParagraphs(formDescription, 'pb-contact-section-form-description', styles.formDescriptionStyle)}
                <div class="pb-contact-section-form-embed">
                    ${formEmbedHtml}
                </div>
                ${renderStyledParagraphs(helperText, 'pb-contact-section-helper', styles.helperTextStyle)}
            </div>
        `;

        let frameInner = `
            <div class="pb-contact-section-content">${contentHtml}</div>
            <div class="pb-contact-section-form-wrap">${formHtml}</div>
        `;

        if (!String(contentHtml + formTitle + formDescription + helperText).replace(/<[^>]+>/g, '').trim()) {
            frameInner = `<div class="pb-empty">${escapeHtml(emptyMessage || formUnavailableMessage || labelHelper('contact_section_empty', 'Ajoutez du contenu pour afficher cette section contact.'))}</div>`;
        }

        schedulePreviewSync(previewId, styles);
        schedulePreviewDesignSync(previewId, 'data-contact-section-preview-id', ['.pb-contact-section-frame'], ['.pb-contact-section-eyebrow', '.pb-contact-section-title', '.pb-contact-section-subtitle', '.pb-contact-section-body', '.pb-contact-section-body *', '.pb-contact-section-feature-text', '.pb-contact-section-proof', '.pb-contact-section-form-title', '.pb-contact-section-form-description', '.pb-contact-section-helper'], resolveWidgetDesign(settings, 28));

        return `
            <section class="pb-contact-section pb-contact-section-variant-${escapeAttr(variant)} pb-contact-section-align-${escapeAttr(align)} pb-contact-section-text-valign-${escapeAttr(textVerticalAlign)}" data-contact-section-preview-id="${escapeAttr(previewId)}">
                <div class="pb-contact-section-frame">
                    ${frameInner}
                </div>
            </section>
        `;
    };
})();
