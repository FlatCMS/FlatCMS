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

    registry.stats_section = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || ((value) => String(value || ''));
        const escapeAttr = helpers.escapeAttr || ((value) => String(value || ''));
        const label = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `pb-stats-section-preview-${Math.random().toString(36).slice(2, 10)}`;

        const parseRepeaterLines = (raw) => {
            if (typeof raw !== 'string' || String(raw).trim() === '') {
                return [];
            }

            const items = String(raw)
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
            return ['left', 'center', 'right'].includes(fallback) ? fallback : 'left';
        };

        const normalizeVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['subtle', 'strong', 'dashed'].includes(safe) ? safe : 'subtle';
        };

        const normalizeColumns = (value) => {
            const safe = Math.trunc(Number(value || 3)) || 3;
            return Math.max(2, Math.min(4, safe));
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

        const title = String(settings.title || label('stats_section_default_title', '')).trim();
        const subtitle = String(settings.subtitle || label('stats_section_default_subtitle', '')).trim();
        const values = parseRepeaterLines(settings.values || label('stats_section_default_values', ''));
        const labels = parseRepeaterLines(settings.labels || label('stats_section_default_labels', ''));
        const notes = parseRepeaterLines(settings.notes || label('stats_section_default_notes', ''));
        const showHeader = normalizeToggle(settings.showHeader, true);
        const showNotes = normalizeToggle(settings.showNotes, true);
        const align = normalizeAlign(settings.align, 'left');
        const variant = normalizeVariant(settings.variant || 'subtle');
        const columns = normalizeColumns(settings.columns || 3);

        const count = Math.min(Math.max(values.length, labels.length, notes.length, 1), 8);

        let itemsHtml = '';
        for (let index = 0; index < count; index += 1) {
            const value = String(values[index] || '').trim();
            const itemLabel = String(labels[index] || '').trim();
            const note = String(notes[index] || '').trim();

            if (!value && !itemLabel && !note) {
                continue;
            }

            const cardClass = variant === 'strong' ? 'pb-card pb-card-strong' : 'pb-card pb-card-subtle';
            itemsHtml += `<article class="pb-stats-card ${cardClass}">`;
            if (value) {
                itemsHtml += `<strong class="pb-stats-card-value"><span class="pb-styled-text-content">${escapeHtml(value)}</span></strong>`;
            }
            if (itemLabel) {
                itemsHtml += `<h3 class="pb-stats-card-label"><span class="pb-styled-text-content">${escapeHtml(itemLabel)}</span></h3>`;
            }
            if (showNotes && note) {
                itemsHtml += `<p class="pb-stats-card-note"><span class="pb-styled-text-content">${escapeHtml(note)}</span></p>`;
            }
            itemsHtml += '</article>';
        }

        if (!itemsHtml) {
            itemsHtml = `<div class="pb-empty">${escapeHtml(label('stats_section_empty', ''))}</div>`;
        }

        let headerHtml = '';
        if (showHeader && (title || subtitle)) {
            headerHtml += '<header class="pb-stats-section-header">';
            if (title) {
                headerHtml += `<h2 class="pb-stats-section-title"><span class="pb-styled-text-content">${escapeHtml(title)}</span></h2>`;
            }
            if (subtitle) {
                headerHtml += `<p class="pb-stats-section-subtitle"><span class="pb-styled-text-content">${escapeHtml(subtitle)}</span></p>`;
            }
            headerHtml += '</header>';
        }

        schedulePreviewDesignSync(previewId, 'data-stats-section-preview-id', ['.pb-stats-card'], ['.pb-stats-section-title', '.pb-stats-section-subtitle', '.pb-stats-card-value', '.pb-stats-card-label', '.pb-stats-card-note'], resolveWidgetDesign(settings, 16));

        return `
            <section class="pb-stats-section pb-stats-section-variant-${escapeAttr(variant)} pb-stats-section-align-${escapeAttr(align)}" data-stats-section-preview-id="${escapeAttr(previewId)}">
                ${headerHtml}
                <div class="pb-stats-grid pb-stats-grid-cols-${escapeAttr(String(columns))}">
                    ${itemsHtml}
                </div>
            </section>
        `;
    };
})();
