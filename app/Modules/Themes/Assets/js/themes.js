/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const previewBoxes = Array.from(document.querySelectorAll('[data-theme-preview-box], #preview-box'));
    if (previewBoxes.length === 0) return;

    const preview = previewBoxes[0];

    const themeName = String(preview.dataset.themeName || '').toLowerCase();
    const isModernPro = themeName.includes('modern-pro');
    const legacyModernProSecondary = '#64748B';
    const HEX_COLOR = /^#[0-9A-Fa-f]{6}$/;
    const modeTabsRoot = document.querySelector('[data-theme-mode-tabs]');
    const modeTabButtons = Array.from(document.querySelectorAll('[data-theme-mode-tab]'));
    const modePanels = Array.from(document.querySelectorAll('[data-theme-mode-panel]'));
    const componentsModal = document.querySelector('[data-theme-components-modal]');
    const componentsOpenButtons = Array.from(document.querySelectorAll('[data-theme-components-open]'));
    const componentsCloseButtons = Array.from(document.querySelectorAll('[data-theme-components-close]'));
    const componentTabButtons = Array.from(document.querySelectorAll('[data-theme-components-tab]'));
    const componentPanels = Array.from(document.querySelectorAll('[data-theme-components-panel]'));
    const resetModal = document.querySelector('[data-theme-reset-modal]');
    const resetOpenButtons = Array.from(document.querySelectorAll('[data-theme-reset-open]'));
    const resetCloseButtons = Array.from(document.querySelectorAll('[data-theme-reset-close]'));
    let activeScope = modeTabButtons.find((button) => button.classList.contains('is-active'))?.dataset.themeMode || 'default';
    let activeComponentPanel = componentTabButtons.find((button) => button.classList.contains('is-active'))?.dataset.themeComponentsTab || 'buttons';

    function setPreviewProperty(name, value) {
        previewBoxes.forEach((box) => {
            if (value === '' || value === null || typeof value === 'undefined') {
                box.style.removeProperty(name);
                return;
            }
            box.style.setProperty(name, value);
        });
    }

    function setPreviewInlineStyle(name, value) {
        previewBoxes.forEach((box) => {
            box.style[name] = value;
        });
    }

    function setPreviewDataset(key, value) {
        previewBoxes.forEach((box) => {
            if (value === '' || value === null || typeof value === 'undefined') {
                delete box.dataset[key];
                return;
            }
            box.dataset[key] = value;
        });
    }

    function isModalVisible(modal) {
        if (!modal || modal.hidden) {
            return false;
        }
        if (modal.style.display && modal.style.display !== 'none') {
            return true;
        }
        return window.getComputedStyle(modal).display !== 'none';
    }

    function updateBodyOverflow() {
        const anyVisibleModal = Array.from(document.querySelectorAll('.modal-overlay')).some(isModalVisible);
        document.body.style.overflow = anyVisibleModal ? 'hidden' : '';
    }

    function openModal(modal) {
        if (!modal) {
            return;
        }

        document.querySelectorAll('.modal-overlay').forEach((otherModal) => {
            if (otherModal !== modal && isModalVisible(otherModal)) {
                closeModal(otherModal);
            }
        });

        modal.hidden = false;
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => {
            modal.classList.remove('is-initially-hidden');
        });
        updateBodyOverflow();
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.add('is-initially-hidden');
        modal.style.display = 'none';
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        updateBodyOverflow();
    }

    function setActiveComponentPanel(panelKey) {
        const targetPanel = componentPanels.some((panel) => panel.dataset.themeComponentsPanel === panelKey)
            ? panelKey
            : 'buttons';

        activeComponentPanel = targetPanel;
        componentTabButtons.forEach((button) => {
            const isActive = button.dataset.themeComponentsTab === targetPanel;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        componentPanels.forEach((panel) => {
            const isActive = panel.dataset.themeComponentsPanel === targetPanel;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
        });
    }

    function openComponentsModal(panelKey) {
        setActiveComponentPanel(panelKey || activeComponentPanel);
        openModal(componentsModal);
    }

    function closeComponentsModal() {
        closeModal(componentsModal);
    }

    function normalizeHex(value, fallback) {
        const color = String(value || '').trim();
        if (!HEX_COLOR.test(color)) {
            return fallback;
        }
        return color.toUpperCase();
    }

    function adjustHex(hex, ratio) {
        const safeHex = normalizeHex(hex, '#2563EB');
        const safeRatio = Number.isFinite(ratio) ? Math.max(-0.95, Math.min(0.95, ratio)) : 0;
        const r = parseInt(safeHex.slice(1, 3), 16);
        const g = parseInt(safeHex.slice(3, 5), 16);
        const b = parseInt(safeHex.slice(5, 7), 16);

        const toChannel = (channel) => {
            if (safeRatio >= 0) {
                return channel * (1 - safeRatio);
            }
            return channel + ((255 - channel) * Math.abs(safeRatio));
        };

        const toHex = (channel) => Math.max(0, Math.min(255, Math.round(toChannel(channel)))).toString(16).padStart(2, '0');
        return `#${toHex(r)}${toHex(g)}${toHex(b)}`.toUpperCase();
    }

    function rgba(hex, alpha) {
        const safeHex = normalizeHex(hex, '#2563EB');
        const r = parseInt(safeHex.slice(1, 3), 16);
        const g = parseInt(safeHex.slice(3, 5), 16);
        const b = parseInt(safeHex.slice(5, 7), 16);
        const a = Number.isFinite(alpha) ? Math.max(0, Math.min(1, alpha)) : 1;
        return `rgba(${r}, ${g}, ${b}, ${a})`;
    }

    function mixHex(fromHex, toHex, ratio) {
        const from = normalizeHex(fromHex, '#2563EB');
        const to = normalizeHex(toHex, '#FFFFFF');
        const weight = Number.isFinite(ratio) ? Math.max(0, Math.min(1, ratio)) : 0;
        const fromR = parseInt(from.slice(1, 3), 16);
        const fromG = parseInt(from.slice(3, 5), 16);
        const fromB = parseInt(from.slice(5, 7), 16);
        const toR = parseInt(to.slice(1, 3), 16);
        const toG = parseInt(to.slice(3, 5), 16);
        const toB = parseInt(to.slice(5, 7), 16);
        const toChannel = (start, end) => Math.round(start + ((end - start) * weight)).toString(16).padStart(2, '0');

        return `#${toChannel(fromR, toR)}${toChannel(fromG, toG)}${toChannel(fromB, toB)}`.toUpperCase();
    }

    function resolveButtonRadiusFromCustomCss(fallback) {
        const customCssInput = document.getElementById('custom_css');
        const cssText = String(customCssInput ? customCssInput.value : '').trim();
        if (cssText === '') {
            return fallback;
        }

        const blockPattern = /([^{}]+)\{([^{}]*)\}/g;
        let match = null;
        let radius = fallback;

        while ((match = blockPattern.exec(cssText)) !== null) {
            const selectors = String(match[1] || '');
            const declarations = String(match[2] || '');
            if (!selectors.includes('.btn')) {
                continue;
            }

            const radiusMatch = declarations.match(/border-radius\s*:\s*([^;]+)\s*;/i);
            if (radiusMatch && String(radiusMatch[1] || '').trim() !== '') {
                radius = String(radiusMatch[1]).trim();
            }
        }

        return radius;
    }

    function getControlValue(selector, fallback) {
        const control = document.querySelector(selector);
        return String(control ? control.value : fallback || 'theme');
    }

    function applyComponentCustomization() {
        const buttonShape = getControlValue('[data-theme-button-control="shape"]', 'theme');
        const buttonWeight = getControlValue('[data-theme-button-control="weight"]', 'theme');
        const buttonStyle = getControlValue('[data-theme-button-control="style"]', 'theme');
        const badgeShape = getControlValue('[data-theme-badge-control="shape"]', 'theme');
        const badgeWeight = getControlValue('[data-theme-badge-control="weight"]', 'theme');
        const badgeStyle = getControlValue('[data-theme-badge-control="style"]', 'theme');
        const typographyBody = getControlValue('[data-theme-typography-control="body_family"]', 'theme');
        const typographyHeading = getControlValue('[data-theme-typography-control="heading_family"]', 'theme');
        const typographyScale = getControlValue('[data-theme-typography-control="scale"]', 'theme');
        const typographyHeadingWeight = getControlValue('[data-theme-typography-control="heading_weight"]', 'theme');
        const radiusMap = {
            sharp: '0',
            rounded: '0.75rem',
            pill: '999px'
        };
        const weightMap = {
            medium: '500',
            semibold: '600',
            bold: '700',
            black: '900'
        };
        const familyMap = {
            system: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            sans: '"Inter", "Segoe UI", system-ui, sans-serif',
            geometric: '"Space Grotesk", "Inter", system-ui, sans-serif',
            editorial: '"Fraunces", Georgia, serif'
        };
        const scaleMap = {
            compact: {
                '--theme-body-font-size': '0.975rem',
                '--theme-body-line-height': '1.58',
                '--theme-heading-line-height': '1.12',
                '--theme-heading-letter-spacing': '-0.02em',
                '--theme-preview-heading-size': '1.25rem'
            },
            balanced: {
                '--theme-body-font-size': '1rem',
                '--theme-body-line-height': '1.65',
                '--theme-heading-line-height': '1.15',
                '--theme-heading-letter-spacing': '-0.025em',
                '--theme-preview-heading-size': '1.4rem'
            },
            comfortable: {
                '--theme-body-font-size': '1.0625rem',
                '--theme-body-line-height': '1.72',
                '--theme-heading-line-height': '1.18',
                '--theme-heading-letter-spacing': '-0.03em',
                '--theme-preview-heading-size': '1.55rem'
            }
        };
        const buttonStyleVars = {
            classic: {
                '--theme-preview-primary-shadow': '0 4px 10px color-mix(in srgb, var(--color-primary, #2563EB) 20%, transparent)',
                '--theme-preview-primary-hover-shadow': '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 26%, transparent)',
                '--theme-preview-secondary-bg': 'var(--color-bg-secondary, var(--color-surface, #F8FAFC))',
                '--theme-preview-secondary-border': 'var(--color-border, #CBD5E1)',
                '--theme-preview-secondary-color': 'var(--color-text-primary, #111827)',
                '--theme-preview-secondary-shadow': '0 1px 2px rgba(15, 23, 42, 0.08)',
                '--theme-preview-secondary-hover-bg': 'var(--color-bg-hover, #EEF2F7)',
                '--theme-preview-secondary-hover-border': 'color-mix(in srgb, var(--color-border, #CBD5E1) 68%, var(--color-text-primary, #111827))',
                '--theme-preview-secondary-hover-color': 'var(--color-text-primary, #111827)',
                '--theme-preview-secondary-hover-shadow': '0 8px 14px color-mix(in srgb, var(--color-primary, #2563EB) 10%, transparent)',
                '--theme-preview-ghost-bg': 'transparent',
                '--theme-preview-ghost-border': 'var(--color-border, #CBD5E1)',
                '--theme-preview-ghost-color': 'var(--color-text-secondary, #4B5563)',
                '--theme-preview-ghost-shadow': 'none',
                '--theme-preview-ghost-hover-bg': 'var(--color-bg-hover, #F8FAFC)',
                '--theme-preview-ghost-hover-border': 'color-mix(in srgb, var(--color-border, #CBD5E1) 72%, var(--color-text-primary, #111827))',
                '--theme-preview-ghost-hover-color': 'var(--color-text-primary, #111827)',
                '--theme-preview-ghost-hover-shadow': 'none',
                '--theme-preview-outline-bg': 'transparent',
                '--theme-preview-outline-border': 'color-mix(in srgb, var(--color-primary, #2563EB) 38%, var(--color-border, #CBD5E1))',
                '--theme-preview-outline-color': 'var(--color-primary, #2563EB)',
                '--theme-preview-outline-shadow': 'none',
                '--theme-preview-outline-hover-bg': 'var(--color-primary, #2563EB)',
                '--theme-preview-outline-hover-border': 'var(--color-primary-dark, var(--color-primary, #2563EB))',
                '--theme-preview-outline-hover-color': '#FFFFFF',
                '--theme-preview-outline-hover-shadow': '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 18%, transparent)'
            },
            soft: {
                '--theme-preview-primary-shadow': '0 6px 14px color-mix(in srgb, var(--color-primary, #2563EB) 18%, transparent)',
                '--theme-preview-primary-hover-shadow': '0 12px 20px color-mix(in srgb, var(--color-primary, #2563EB) 24%, transparent)',
                '--theme-preview-secondary-bg': 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 86%, var(--color-primary, #2563EB) 14%)',
                '--theme-preview-secondary-border': 'color-mix(in srgb, var(--color-border, #CBD5E1) 78%, var(--color-primary, #2563EB) 22%)',
                '--theme-preview-secondary-color': 'var(--color-text-primary, #111827)',
                '--theme-preview-secondary-shadow': '0 6px 14px color-mix(in srgb, var(--color-primary, #2563EB) 10%, transparent)',
                '--theme-preview-secondary-hover-bg': 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 74%, var(--color-primary, #2563EB) 26%)',
                '--theme-preview-secondary-hover-border': 'color-mix(in srgb, var(--color-border, #CBD5E1) 62%, var(--color-primary, #2563EB) 38%)',
                '--theme-preview-secondary-hover-color': 'var(--color-text-primary, #111827)',
                '--theme-preview-secondary-hover-shadow': '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 16%, transparent)',
                '--theme-preview-ghost-bg': 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 94%, var(--color-primary, #2563EB) 6%)',
                '--theme-preview-ghost-border': 'color-mix(in srgb, var(--color-border, #CBD5E1) 82%, var(--color-primary, #2563EB) 18%)',
                '--theme-preview-ghost-color': 'var(--color-text-secondary, #4B5563)',
                '--theme-preview-ghost-shadow': 'none',
                '--theme-preview-ghost-hover-bg': 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 82%, var(--color-primary, #2563EB) 18%)',
                '--theme-preview-ghost-hover-border': 'color-mix(in srgb, var(--color-border, #CBD5E1) 66%, var(--color-primary, #2563EB) 34%)',
                '--theme-preview-ghost-hover-color': 'var(--color-text-primary, #111827)',
                '--theme-preview-ghost-hover-shadow': 'none',
                '--theme-preview-outline-bg': 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 92%, var(--color-primary, #2563EB) 8%)',
                '--theme-preview-outline-border': 'color-mix(in srgb, var(--color-primary, #2563EB) 44%, var(--color-border, #CBD5E1))',
                '--theme-preview-outline-color': 'var(--color-primary, #2563EB)',
                '--theme-preview-outline-shadow': 'none',
                '--theme-preview-outline-hover-bg': 'color-mix(in srgb, var(--color-bg-secondary, #F8FAFC) 76%, var(--color-primary, #2563EB) 24%)',
                '--theme-preview-outline-hover-border': 'var(--color-primary, #2563EB)',
                '--theme-preview-outline-hover-color': 'var(--color-primary-dark, var(--color-primary, #2563EB))',
                '--theme-preview-outline-hover-shadow': '0 10px 18px color-mix(in srgb, var(--color-primary, #2563EB) 16%, transparent)'
            },
            elevated: {
                '--theme-preview-primary-shadow': '0 14px 28px color-mix(in srgb, var(--color-primary, #2563EB) 28%, transparent)',
                '--theme-preview-primary-hover-shadow': '0 18px 34px color-mix(in srgb, var(--color-primary, #2563EB) 34%, transparent)',
                '--theme-preview-secondary-shadow': '0 12px 24px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 22%, transparent)',
                '--theme-preview-secondary-hover-shadow': '0 16px 30px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 28%, transparent)',
                '--theme-preview-ghost-shadow': '0 8px 18px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 12%, transparent)',
                '--theme-preview-ghost-hover-shadow': '0 12px 24px color-mix(in srgb, var(--color-secondary, var(--color-primary, #2563EB)) 18%, transparent)',
                '--theme-preview-outline-shadow': '0 10px 22px color-mix(in srgb, var(--color-primary, #2563EB) 14%, transparent)',
                '--theme-preview-outline-hover-shadow': '0 14px 26px color-mix(in srgb, var(--color-primary, #2563EB) 20%, transparent)'
            }
        };
        const previewButtonVars = [
            '--theme-preview-primary-shadow',
            '--theme-preview-primary-hover-shadow',
            '--theme-preview-secondary-bg',
            '--theme-preview-secondary-border',
            '--theme-preview-secondary-color',
            '--theme-preview-secondary-shadow',
            '--theme-preview-secondary-hover-bg',
            '--theme-preview-secondary-hover-border',
            '--theme-preview-secondary-hover-color',
            '--theme-preview-secondary-hover-shadow',
            '--theme-preview-ghost-bg',
            '--theme-preview-ghost-border',
            '--theme-preview-ghost-color',
            '--theme-preview-ghost-shadow',
            '--theme-preview-ghost-hover-bg',
            '--theme-preview-ghost-hover-border',
            '--theme-preview-ghost-hover-color',
            '--theme-preview-ghost-hover-shadow',
            '--theme-preview-outline-bg',
            '--theme-preview-outline-border',
            '--theme-preview-outline-color',
            '--theme-preview-outline-shadow',
            '--theme-preview-outline-hover-bg',
            '--theme-preview-outline-hover-border',
            '--theme-preview-outline-hover-color',
            '--theme-preview-outline-hover-shadow'
        ];
        const resolvedTypographyBody = typographyBody === 'theme' ? 'sans' : typographyBody;
        const resolvedTypographyHeading = typographyHeading === 'theme' ? 'sans' : typographyHeading;
        const resolvedTypographyScale = typographyScale === 'theme' ? 'balanced' : typographyScale;
        const resolvedTypographyHeadingWeight = typographyHeadingWeight === 'theme' ? 'semibold' : typographyHeadingWeight;

        setPreviewDataset('themeButtonStyle', buttonStyle !== 'theme' ? buttonStyle : '');
        setPreviewDataset('themeBadgeStyle', badgeStyle !== 'theme' ? badgeStyle : '');
        setPreviewDataset('themeTypographyScale', typographyScale !== 'theme' ? typographyScale : '');

        if (radiusMap[buttonShape]) {
            setPreviewProperty('--btn-radius', radiusMap[buttonShape]);
            setPreviewProperty('--fc-btn-radius', radiusMap[buttonShape]);
        }
        if (weightMap[buttonWeight]) {
            setPreviewProperty('--btn-font-weight', weightMap[buttonWeight]);
            setPreviewProperty('--fc-btn-font-weight', weightMap[buttonWeight]);
        }
        setPreviewProperty('--theme-badge-radius', radiusMap[badgeShape] || '');
        setPreviewProperty('--theme-badge-font-weight', weightMap[badgeWeight] || '');
        setPreviewProperty('--theme-body-font-family', familyMap[resolvedTypographyBody] || '');
        setPreviewProperty('--theme-heading-font-family', familyMap[resolvedTypographyHeading] || '');
        setPreviewProperty('--theme-heading-font-weight', weightMap[resolvedTypographyHeadingWeight] || '');

        previewButtonVars.forEach((name) => {
            setPreviewProperty(name, buttonStyleVars[buttonStyle]?.[name] || '');
        });

        setPreviewProperty('--theme-body-font-size', scaleMap[resolvedTypographyScale]?.['--theme-body-font-size'] || '');
        setPreviewProperty('--theme-body-line-height', scaleMap[resolvedTypographyScale]?.['--theme-body-line-height'] || '');
        setPreviewProperty('--theme-heading-line-height', scaleMap[resolvedTypographyScale]?.['--theme-heading-line-height'] || '');
        setPreviewProperty('--theme-heading-letter-spacing', scaleMap[resolvedTypographyScale]?.['--theme-heading-letter-spacing'] || '');
        setPreviewProperty('--theme-preview-heading-size', scaleMap[resolvedTypographyScale]?.['--theme-preview-heading-size'] || '');
    }

    function findColorInput(scope, key, kind) {
        const selector = kind === 'picker' ? '[data-theme-color-picker]' : '[data-theme-color-text]';
        return document.querySelector(`${selector}[data-theme-color-scope="${scope}"][data-theme-color-key="${key}"]`);
    }

    function getColorValue(scope, key, fallback) {
        const picker = findColorInput(scope, key, 'picker');
        const textInput = findColorInput(scope, key, 'text');
        const value = picker ? picker.value : (textInput ? textInput.value : '');
        return normalizeHex(value, fallback);
    }

    function setActiveMode(scope) {
        activeScope = scope;
        modeTabButtons.forEach((button) => {
            const active = button.dataset.themeMode === scope;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        modePanels.forEach((panel) => {
            const active = panel.dataset.themeModePanel === scope;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });
        previewBoxes.forEach((box) => {
            box.dataset.previewMode = scope;
        });
        updatePreview();
    }

    function openResetModal() {
        openModal(resetModal);
    }

    function closeResetModal() {
        closeModal(resetModal);
    }

    function updatePreview() {
        if (!preview) return;

        const scope = activeScope || 'default';
        const primary = getColorValue(scope, 'primary', '#2563EB');
        let secondary = getColorValue(scope, 'secondary', '#3B82F6');
        const accent = getColorValue(scope, 'accent', secondary);
        const background = getColorValue(scope, 'background', isModernPro ? '#0F172A' : '#FFFFFF');
        const surface = getColorValue(scope, 'surface', isModernPro ? '#1E293B' : '#F8FAFC');
        const text = getColorValue(scope, 'text', isModernPro ? '#F1F5F9' : '#111827');
        const textMuted = getColorValue(scope, 'text_muted', isModernPro ? '#94A3B8' : '#6B7280');
        const border = getColorValue(scope, 'border', isModernPro ? '#334155' : '#CBD5E1');
        if (isModernPro && secondary === legacyModernProSecondary) {
            secondary = '#8B5CF6';
        }
        const primaryDark = adjustHex(primary, 0.16);
        const secondaryDark = adjustHex(secondary, 0.12);

        setPreviewProperty('--color-primary', primary);
        setPreviewProperty('--color-primary-dark', primaryDark);
        setPreviewProperty('--color-secondary', secondary);
        setPreviewProperty('--color-secondary-dark', secondaryDark);
        setPreviewProperty('--color-bg-primary', background);
        setPreviewProperty('--color-bg-secondary', surface);
        setPreviewProperty('--color-bg', background);
        setPreviewProperty('--color-text-primary', text);
        setPreviewProperty('--color-text-secondary', textMuted);
        setPreviewProperty('--color-text-muted', textMuted);
        setPreviewProperty('--color-border', border);
        setPreviewProperty('--color-surface', surface);
        setPreviewInlineStyle('background', background);
        setPreviewInlineStyle('color', text);

        if (isModernPro) {
            const lightMode = scope === 'light';
            const ghostAccent = accent;
            const ghostAccentSoft = mixHex(ghostAccent, '#FFFFFF', 0.24);
            const primaryMixDark = mixHex(primary, '#0F172A', 0.18);
            const secondaryMixDark = mixHex(secondary, '#0F172A', 0.16);
            const buttonSecondaryBg = lightMode ? surface : secondary;
            const buttonSecondaryColor = lightMode ? text : '#FFFFFF';
            const buttonSecondaryBorder = lightMode ? border : secondary;
            const buttonSecondaryShadow = lightMode
                ? `0 6px 12px ${rgba(secondary, 0.16)}`
                : `0 10px 20px -6px ${rgba(secondary, 0.45)}`;
            const outlineColor = lightMode ? primary : mixHex(primary, '#FFFFFF', 0.24);
            const outlineBorder = lightMode ? mixHex(primary, border, 0.34) : rgba(primary, 0.54);
            const outlineHoverBg = lightMode ? primary : mixHex(primary, '#0F172A', 0.12);
            const outlineHoverBorder = lightMode ? primaryDark : mixHex(primary, '#FFFFFF', 0.16);
            const outlineShadow = lightMode ? 'none' : `0 10px 22px ${rgba(primary, 0.14)}`;
            const outlineHoverShadow = `0 14px 26px ${rgba(primary, lightMode ? 0.18 : 0.24)}`;

            setPreviewProperty('--fc-btn-font-weight', '700');
            setPreviewProperty('--fc-btn-radius', resolveButtonRadiusFromCustomCss('999px'));
            setPreviewProperty('--fc-btn-primary-bg', primary);
            setPreviewProperty('--fc-btn-primary-border', primaryMixDark);
            setPreviewProperty('--fc-btn-primary-color', '#FFFFFF');
            setPreviewProperty('--fc-btn-primary-shadow', `0 8px 18px ${rgba(primary, lightMode ? 0.18 : 0.28)}`);
            setPreviewProperty('--fc-btn-primary-hover-bg', primaryMixDark);
            setPreviewProperty('--fc-btn-primary-hover-border', primaryMixDark);
            setPreviewProperty('--fc-btn-primary-hover-color', '#FFFFFF');
            setPreviewProperty('--fc-btn-primary-hover-shadow', `0 14px 24px ${rgba(primary, lightMode ? 0.24 : 0.34)}`);
            setPreviewProperty('--fc-btn-primary-active-bg', mixHex(primary, '#0F172A', 0.28));
            setPreviewProperty('--fc-btn-primary-active-border', mixHex(primary, '#0F172A', 0.28));
            setPreviewProperty('--fc-btn-primary-active-color', '#FFFFFF');

            setPreviewProperty('--fc-btn-secondary-bg', buttonSecondaryBg);
            setPreviewProperty('--fc-btn-secondary-border', buttonSecondaryBorder);
            setPreviewProperty('--fc-btn-secondary-color', buttonSecondaryColor);
            setPreviewProperty('--fc-btn-secondary-shadow', buttonSecondaryShadow);
            setPreviewProperty('--fc-btn-secondary-hover-bg', lightMode ? adjustHex(surface, 0.08) : secondaryMixDark);
            setPreviewProperty('--fc-btn-secondary-hover-border', lightMode ? mixHex(border, '#94A3B8', 0.32) : secondaryMixDark);
            setPreviewProperty('--fc-btn-secondary-hover-color', buttonSecondaryColor);
            setPreviewProperty('--fc-btn-secondary-hover-shadow', `0 12px 22px ${rgba(secondary, lightMode ? 0.18 : 0.28)}`);
            setPreviewProperty('--fc-btn-secondary-active-bg', lightMode ? mixHex(adjustHex(surface, 0.08), border, 0.4) : mixHex(secondaryMixDark, '#0F172A', 0.2));
            setPreviewProperty('--fc-btn-secondary-active-border', lightMode ? mixHex(border, '#94A3B8', 0.32) : mixHex(secondaryMixDark, '#0F172A', 0.2));
            setPreviewProperty('--fc-btn-secondary-active-color', buttonSecondaryColor);

            setPreviewProperty('--fc-btn-ghost-bg', lightMode ? 'transparent' : rgba(ghostAccent, 0.08));
            setPreviewProperty('--fc-btn-ghost-border', rgba(ghostAccent, lightMode ? 0.34 : 0.46));
            setPreviewProperty('--fc-btn-ghost-color', lightMode ? ghostAccent : ghostAccentSoft);
            setPreviewProperty('--fc-btn-ghost-shadow', lightMode ? 'none' : 'inset 0 1px 0 rgba(255, 255, 255, 0.08)');
            setPreviewProperty('--fc-btn-ghost-hover-bg', lightMode ? ghostAccent : mixHex(ghostAccent, '#0F172A', 0.18));
            setPreviewProperty('--fc-btn-ghost-hover-border', lightMode ? ghostAccent : ghostAccentSoft);
            setPreviewProperty('--fc-btn-ghost-hover-color', '#FFFFFF');
            setPreviewProperty('--fc-btn-ghost-hover-shadow', `0 12px 22px ${rgba(ghostAccent, 0.32)}`);

            setPreviewProperty('--fc-btn-outline-bg', lightMode ? 'transparent' : rgba(primary, 0.04));
            setPreviewProperty('--fc-btn-outline-border', outlineBorder);
            setPreviewProperty('--fc-btn-outline-color', outlineColor);
            setPreviewProperty('--fc-btn-outline-shadow', outlineShadow);
            setPreviewProperty('--fc-btn-outline-hover-bg', outlineHoverBg);
            setPreviewProperty('--fc-btn-outline-hover-border', outlineHoverBorder);
            setPreviewProperty('--fc-btn-outline-hover-color', '#FFFFFF');
            setPreviewProperty('--fc-btn-outline-hover-shadow', outlineHoverShadow);
            applyComponentCustomization();
            return;
        }

        setPreviewProperty('--btn-font-weight', '500');
        setPreviewProperty('--btn-radius', '0.5rem');
        setPreviewProperty('--btn-primary-bg', primary);
        setPreviewProperty('--btn-primary-bg-hover', primaryDark);
        setPreviewProperty('--btn-primary-bg-active', adjustHex(primary, 0.24));
        setPreviewProperty('--btn-primary-color', '#FFFFFF');
        setPreviewProperty('--btn-primary-shadow', `0 4px 10px ${rgba(primary, 0.24)}`);
        setPreviewProperty('--btn-primary-shadow-hover', `0 10px 15px -3px ${rgba(primary, 0.4)}`);

        setPreviewProperty('--btn-secondary-bg', surface);
        setPreviewProperty('--btn-secondary-bg-hover', adjustHex(surface, 0.08));
        setPreviewProperty('--btn-secondary-bg-active', adjustHex(surface, 0.14));
        setPreviewProperty('--btn-secondary-color', text);
        setPreviewProperty('--btn-secondary-color-hover', text);
        setPreviewProperty('--btn-secondary-color-active', text);
        setPreviewProperty('--btn-secondary-border', border);
        setPreviewProperty('--btn-secondary-border-hover', adjustHex(border, 0.1));
        setPreviewProperty('--btn-secondary-shadow', '0 1px 2px rgba(15, 23, 42, 0.06)');
        setPreviewProperty('--btn-secondary-shadow-hover', '0 6px 12px rgba(148, 163, 184, 0.2)');

        setPreviewProperty('--btn-ghost-bg', 'transparent');
        setPreviewProperty('--btn-ghost-color', accent);
        setPreviewProperty('--btn-ghost-border', border);
        setPreviewProperty('--btn-ghost-bg-hover', adjustHex(surface, 0.08));
        setPreviewProperty('--btn-ghost-color-hover', text);
        setPreviewProperty('--btn-ghost-border-hover', accent);
        setPreviewProperty('--btn-ghost-shadow', 'none');
        setPreviewProperty('--btn-ghost-shadow-hover', 'none');

        setPreviewProperty('--btn-outline-bg', 'transparent');
        setPreviewProperty('--btn-outline-color', primary);
        setPreviewProperty('--btn-outline-border', mixHex(primary, border, 0.34));
        setPreviewProperty('--btn-outline-bg-hover', primary);
        setPreviewProperty('--btn-outline-color-hover', '#FFFFFF');
        setPreviewProperty('--btn-outline-border-hover', primaryDark);
        setPreviewProperty('--btn-outline-shadow', 'none');
        setPreviewProperty('--btn-outline-shadow-hover', `0 10px 15px -3px ${rgba(primary, 0.24)}`);
        applyComponentCustomization();
    }

    document.querySelectorAll('[data-theme-color-picker]').forEach((input) => {
        input.addEventListener('input', function() {
            const scope = String(this.dataset.themeColorScope || 'default');
            const key = String(this.dataset.themeColorKey || '');
            const textField = findColorInput(scope, key, 'text');
            if (textField) {
                textField.value = String(this.value).toUpperCase();
            }
            if (scope === activeScope) {
                updatePreview();
            }
        });
    });

    document.querySelectorAll('[data-theme-color-text]').forEach((input) => {
        input.addEventListener('input', function() {
            const scope = String(this.dataset.themeColorScope || 'default');
            const key = String(this.dataset.themeColorKey || '');
            const picker = findColorInput(scope, key, 'picker');
            const normalized = normalizeHex(this.value, '');
            if (picker && normalized !== '') {
                picker.value = normalized;
            }
            if (scope === activeScope) {
                updatePreview();
            }
        });

        input.addEventListener('blur', function() {
            const normalized = normalizeHex(this.value, '');
            if (normalized !== '') {
                this.value = normalized;
            }
        });
    });

    const customCssInput = document.getElementById('custom_css');
    if (customCssInput) {
        customCssInput.addEventListener('input', function() {
            updatePreview();
        });
    }

    document.querySelectorAll('[data-theme-button-control], [data-theme-badge-control], [data-theme-typography-control]').forEach((input) => {
        input.addEventListener('change', updatePreview);
    });

    if (modeTabsRoot) {
        modeTabButtons.forEach((button) => {
            button.addEventListener('click', function() {
                setActiveMode(String(this.dataset.themeMode || 'dark'));
            });
        });
    }

    resetOpenButtons.forEach((button) => {
        button.addEventListener('click', function() {
            openResetModal();
        });
    });

    resetCloseButtons.forEach((button) => {
        button.addEventListener('click', function() {
            closeResetModal();
        });
    });

    componentsOpenButtons.forEach((button) => {
        button.addEventListener('click', function() {
            openComponentsModal(String(this.dataset.themeComponentsOpen || 'buttons'));
        });
    });

    componentsCloseButtons.forEach((button) => {
        button.addEventListener('click', function() {
            closeComponentsModal();
        });
    });

    componentTabButtons.forEach((button) => {
        button.addEventListener('click', function() {
            setActiveComponentPanel(String(this.dataset.themeComponentsTab || 'buttons'));
        });
    });

    if (componentsModal) {
        componentsModal.addEventListener('click', function(event) {
            if (event.target === componentsModal) {
                closeComponentsModal();
            }
        });
    }

    if (resetModal) {
        resetModal.addEventListener('click', function(event) {
            if (event.target === resetModal) {
                closeResetModal();
            }
        });
    }

    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape') {
            return;
        }
        if (componentsModal && isModalVisible(componentsModal)) {
            closeComponentsModal();
            return;
        }
        if (resetModal && isModalVisible(resetModal)) {
            closeResetModal();
        }
    });

    updatePreview();
})();
