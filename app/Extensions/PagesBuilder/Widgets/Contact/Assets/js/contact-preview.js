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

    registry.contact = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || escapeFallback;
        const escapeAttr = helpers.escapeAttr || escapeFallback;
        const label = helpers.label || ((_key, fallback) => String(fallback || ''));
        const safeSettings = settings && typeof settings === 'object' ? settings : {};
        const previewId = `pb-contact-preview-${Math.random().toString(36).slice(2, 10)}`;

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

        const normalizeColor = (value) => {
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

        const normalizeVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['subtle', 'strong', 'dark'].includes(safe) ? safe : 'subtle';
        };

        const normalizeDesignInt = (value, fallback, min, max) => {
            const parsed = Math.trunc(Number(value));
            const safe = Number.isFinite(parsed) ? parsed : fallback;
            return Math.max(min, Math.min(max, safe));
        };

        const normalizeFieldType = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date'].includes(safe)
                ? safe
                : 'text';
        };

        const normalizeFieldWidth = (value) => String(value || '').trim().toLowerCase() === 'half' ? 'half' : 'full';

        const normalizeAttachments = (raw) => {
            const safe = raw && typeof raw === 'object' ? raw : {};
            const maxFiles = normalizeDesignInt(safe.maxFiles, 1, 1, 5);
            const maxSizeMb = normalizeDesignInt(safe.maxSizeMb, 5, 1, 25);
            const extensions = Array.isArray(safe.extensions)
                ? safe.extensions.map((entry) => String(entry || '').trim()).filter(Boolean).slice(0, 12)
                : [];

            return {
                enabled: normalizeToggle(safe.enabled, false),
                required: normalizeToggle(safe.required, false),
                maxFiles,
                maxSizeMb,
                extensions,
            };
        };

        const resolveShadowValue = (preset) => {
            if (preset === 'none') {
                return 'none';
            }
            if (preset === 'soft') {
                return '0 12px 34px rgba(15, 23, 42, 0.10)';
            }
            if (preset === 'medium') {
                return '0 18px 48px rgba(15, 23, 42, 0.16)';
            }
            if (preset === 'strong') {
                return '0 26px 70px rgba(15, 23, 42, 0.24)';
            }

            return '';
        };

        const findFormConfigBySlug = (slug) => {
            const target = String(slug || '').trim().toLowerCase();
            const forms = readBuilderContactForms();
            let fallback = null;

            for (const form of forms) {
                if (!form || typeof form !== 'object') {
                    continue;
                }

                const candidateSlug = String(form.slug || '').trim();
                if (!candidateSlug) {
                    continue;
                }

                if (fallback === null) {
                    fallback = form;
                }

                if (target !== '' && candidateSlug.toLowerCase() === target) {
                    return form;
                }
            }

            return fallback;
        };

        const renderFieldControl = (field, fieldId) => {
            const fieldType = normalizeFieldType(field.type);
            const placeholder = escapeAttr(String(field.placeholder || '').trim());
            const options = Array.isArray(field.options) ? field.options : [];

            if (fieldType === 'textarea') {
                return `<textarea id="${escapeAttr(fieldId)}" class="form-input pb-preview-contact-input pb-preview-contact-input-textarea" rows="4" placeholder="${placeholder}" disabled></textarea>`;
            }

            if (fieldType === 'select') {
                const emptyOption = placeholder || escapeHtml(String(field.label || ''));
                const optionsHtml = options
                    .map((option) => `<option value="${escapeAttr(option)}">${escapeHtml(option)}</option>`)
                    .join('');
                return `<select id="${escapeAttr(fieldId)}" class="form-input pb-preview-contact-input" disabled><option value="">${emptyOption}</option>${optionsHtml}</select>`;
            }

            if (fieldType === 'radio' || fieldType === 'checkbox') {
                const inputType = fieldType;
                const choiceValues = options.length > 0 ? options : [String(field.label || '')];
                const choicesHtml = choiceValues.map((option, index) => {
                    const choiceId = `${fieldId}_${index + 1}`;
                    return `
                        <label class="pb-preview-contact-choice-item" for="${escapeAttr(choiceId)}">
                            <input id="${escapeAttr(choiceId)}" type="${escapeAttr(inputType)}" disabled>
                            <span>${escapeHtml(option)}</span>
                        </label>
                    `;
                }).join('');

                return `<div class="pb-preview-contact-choice-list">${choicesHtml}</div>`;
            }

            const inputTypeMap = {
                text: 'text',
                email: 'email',
                tel: 'tel',
                url: 'url',
                number: 'number',
                date: 'date',
            };
            const inputType = Object.prototype.hasOwnProperty.call(inputTypeMap, fieldType)
                ? inputTypeMap[fieldType]
                : 'text';

            return `<input id="${escapeAttr(fieldId)}" type="${escapeAttr(inputType)}" class="form-input pb-preview-contact-input" placeholder="${placeholder}" disabled>`;
        };

        const schedulePreviewSync = (id, design) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-contact-preview-id="${id}"]`);
                if (!(root instanceof HTMLElement)) {
                    return;
                }

                root.querySelectorAll('form[data-preview-static="true"]').forEach((formNode) => {
                    if (!(formNode instanceof HTMLFormElement) || formNode.dataset.previewStaticBound === 'true') {
                        return;
                    }

                    formNode.dataset.previewStaticBound = 'true';
                    formNode.addEventListener('submit', (event) => {
                        event.preventDefault();
                    });
                });

                if (!design.useCustom) {
                    return;
                }

                if (design.surfaceColor) {
                    root.style.setProperty('--pb-contact-widget-surface', design.surfaceColor);
                    root.style.setProperty('--pb-contact-widget-input-bg', design.surfaceColor);
                }
                if (design.textColor) {
                    root.style.setProperty('--pb-contact-widget-text', design.textColor);
                    root.style.setProperty('--pb-contact-widget-label-color', design.textColor);
                    root.style.setProperty('--pb-contact-widget-input-color', design.textColor);
                    root.style.setProperty('--pb-contact-widget-placeholder-color', design.textColor);
                }
                if (design.borderStyle !== 'inherit') {
                    root.style.setProperty('--pb-contact-widget-border-style', design.borderStyle);
                }
                root.style.setProperty('--pb-contact-widget-border-width', `${design.borderWidth}px`);
                if (design.borderColor) {
                    root.style.setProperty('--pb-contact-widget-border-color', design.borderColor);
                    root.style.setProperty('--pb-contact-widget-input-border', design.borderColor);
                }
                root.style.setProperty('--pb-contact-widget-radius', `${design.radius}px`);
                if (design.shadowValue) {
                    root.style.setProperty('--pb-contact-widget-shadow', design.shadowValue);
                }
            });
        };

        const formConfig = findFormConfigBySlug(safeSettings.formSlug || '');
        const align = normalizeAlign(safeSettings.align, 'left');
        const variant = normalizeVariant(safeSettings.variant);
        const title = String(safeSettings.title || '').trim();
        const submitLabel = String((formConfig && formConfig.submitLabel) || label('footer_widget_contact_preview_submit_label', '') || '').trim();
        const fields = Array.isArray(formConfig && formConfig.previewFields)
            ? formConfig.previewFields.filter((entry) => entry && typeof entry === 'object')
            : [];
        const attachments = normalizeAttachments(formConfig && formConfig.attachments);
        const design = {
            useCustom: normalizeToggle(safeSettings.useCustomDesign, false),
            surfaceColor: normalizeColor(safeSettings.designSurfaceColor),
            textColor: normalizeColor(safeSettings.designTextColor),
            borderStyle: normalizeBorderStyle(safeSettings.designBorderStyle),
            borderWidth: normalizeDesignInt(safeSettings.designBorderWidth, 1, 0, 8),
            borderColor: normalizeColor(safeSettings.designBorderColor),
            radius: normalizeDesignInt(safeSettings.designRadius, 20, 0, 48),
            shadowPreset: normalizeShadowPreset(safeSettings.designShadow),
            shadowValue: resolveShadowValue(normalizeShadowPreset(safeSettings.designShadow)),
        };

        if (!formConfig) {
            return `
                <div class="pb-contact-widget pb-contact-widget-preview pb-contact-widget-variant-${escapeAttr(variant)} pb-align pb-align-${escapeAttr(align)}" data-contact-preview-id="${escapeAttr(previewId)}">
                    ${title ? `<strong class="pb-contact-widget-title pb-preview-contact-title">${escapeHtml(title)}</strong>` : ''}
                    <div class="pb-contact-widget-embed">
                        <div class="pb-form-card">${escapeHtml(String(label('footer_widget_contact_preview_form_unavailable', '')))}</div>
                    </div>
                </div>
            `;
        }

        const fieldsHtml = fields.map((field, index) => {
            const fieldId = `pbPreviewContactField_${index + 1}`;
            const width = normalizeFieldWidth(field.width);
            const requiredMark = field.required ? '<span class="pb-preview-contact-required" aria-hidden="true">*</span>' : '';
            const help = String(field.help || '').trim();

            return `
                <div class="pb-preview-contact-field pb-preview-contact-field--${escapeAttr(width)}">
                    <label class="form-label pb-preview-contact-label" for="${escapeAttr(fieldId)}">
                        ${escapeHtml(String(field.label || ''))}${requiredMark}
                    </label>
                    ${renderFieldControl(field, fieldId)}
                    ${help ? `<small class="pb-preview-contact-help">${escapeHtml(help)}</small>` : ''}
                </div>
            `;
        }).join('');

        const attachmentsHintParts = [`${attachments.maxFiles}`, `${attachments.maxSizeMb} MB`];
        if (attachments.extensions.length > 0) {
            attachmentsHintParts.push(attachments.extensions.join(', '));
        }

        const attachmentsHtml = attachments.enabled
            ? `
                <div class="pb-preview-contact-field pb-preview-contact-field--full">
                    <label class="form-label pb-preview-contact-label">
                        ${escapeHtml(String(label('footer_widget_contact_preview_attachments_label', '')))}
                        ${attachments.required ? '<span class="pb-preview-contact-required" aria-hidden="true">*</span>' : ''}
                    </label>
                    <input type="file" class="form-input pb-preview-contact-input" disabled>
                    <small class="pb-preview-contact-help">${escapeHtml(attachmentsHintParts.join(' · '))}</small>
                </div>
            `
            : '';

        schedulePreviewSync(previewId, design);

        return `
            <div class="pb-contact-widget pb-contact-widget-preview pb-contact-widget-variant-${escapeAttr(variant)} ${design.useCustom ? 'pb-contact-widget-has-design' : ''} pb-align pb-align-${escapeAttr(align)}" data-contact-preview-id="${escapeAttr(previewId)}">
                ${title ? `<strong class="pb-contact-widget-title pb-preview-contact-title">${escapeHtml(title)}</strong>` : ''}
                <div class="pb-contact-widget-embed">
                    <div class="pb-preview-form pb-preview-contact pb-preview-align pb-preview-align-${escapeAttr(align)}">
                        <section class="flatcms-contact-native flatcms-contact-embed">
                        <form action="#" method="post" class="pb-preview-contact-form flatcms-contact-form flatcms-contact-native-form" data-preview-static="true" novalidate>
                            <div class="pb-preview-contact-grid">
                                ${fieldsHtml}
                                ${attachmentsHtml}
                            </div>
                            ${submitLabel ? `<button type="button" class="btn btn-primary pb-btn pb-btn-primary">${escapeHtml(submitLabel)}</button>` : ''}
                        </form>
                        </section>
                    </div>
                </div>
            </div>
        `;
    };
})();
