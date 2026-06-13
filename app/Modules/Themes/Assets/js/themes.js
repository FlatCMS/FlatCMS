/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const preview = document.querySelector('[data-theme-preview-box], #preview-box');
    if (!preview) return;

    const themeName = String(preview.dataset.themeName || '').toLowerCase();
    const isModernPro = themeName.includes('modern-pro');
    const legacyModernProSecondary = '#64748B';
    const HEX_COLOR = /^#[0-9A-Fa-f]{6}$/;
    const modeTabsRoot = document.querySelector('[data-theme-mode-tabs]');
    const modeTabButtons = Array.from(document.querySelectorAll('[data-theme-mode-tab]'));
    const modePanels = Array.from(document.querySelectorAll('[data-theme-mode-panel]'));
    const resetModal = document.querySelector('[data-theme-reset-modal]');
    const resetOpenButtons = Array.from(document.querySelectorAll('[data-theme-reset-open]'));
    const resetCloseButtons = Array.from(document.querySelectorAll('[data-theme-reset-close]'));
    let activeScope = modeTabButtons.find((button) => button.classList.contains('is-active'))?.dataset.themeMode || 'default';

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
        const badgeShape = getControlValue('[data-theme-badge-control="shape"]', 'theme');
        const badgeWeight = getControlValue('[data-theme-badge-control="weight"]', 'theme');
        const typographyBody = getControlValue('[data-theme-typography-control="body_family"]', 'theme');
        const typographyHeading = getControlValue('[data-theme-typography-control="heading_family"]', 'theme');
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

        if (radiusMap[buttonShape]) {
            preview.style.setProperty('--btn-radius', radiusMap[buttonShape]);
            preview.style.setProperty('--fc-btn-radius', radiusMap[buttonShape]);
        }
        if (weightMap[buttonWeight]) {
            preview.style.setProperty('--btn-font-weight', weightMap[buttonWeight]);
            preview.style.setProperty('--fc-btn-font-weight', weightMap[buttonWeight]);
        }
        if (radiusMap[badgeShape]) {
            preview.style.setProperty('--theme-badge-radius', radiusMap[badgeShape]);
        }
        if (weightMap[badgeWeight]) {
            preview.style.setProperty('--theme-badge-font-weight', weightMap[badgeWeight]);
        }
        if (familyMap[typographyBody]) {
            preview.style.setProperty('--theme-body-font-family', familyMap[typographyBody]);
        }
        if (familyMap[typographyHeading]) {
            preview.style.setProperty('--theme-heading-font-family', familyMap[typographyHeading]);
        }
        if (weightMap[typographyHeadingWeight]) {
            preview.style.setProperty('--theme-heading-font-weight', weightMap[typographyHeadingWeight]);
        }
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
        preview.dataset.previewMode = scope;
        updatePreview();
    }

    function openResetModal() {
        if (!resetModal) return;
        resetModal.hidden = false;
        requestAnimationFrame(function() {
            resetModal.classList.remove('is-initially-hidden');
        });
    }

    function closeResetModal() {
        if (!resetModal) return;
        resetModal.classList.add('is-initially-hidden');
        window.setTimeout(function() {
            resetModal.hidden = true;
        }, 150);
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

        preview.style.setProperty('--color-primary', primary);
        preview.style.setProperty('--color-primary-dark', primaryDark);
        preview.style.setProperty('--color-secondary', secondary);
        preview.style.setProperty('--color-secondary-dark', secondaryDark);
        preview.style.setProperty('--color-bg-primary', background);
        preview.style.setProperty('--color-bg-secondary', surface);
        preview.style.setProperty('--color-bg', background);
        preview.style.setProperty('--color-text-primary', text);
        preview.style.setProperty('--color-text-secondary', textMuted);
        preview.style.setProperty('--color-text-muted', textMuted);
        preview.style.setProperty('--color-border', border);
        preview.style.setProperty('--color-surface', surface);
        preview.style.background = background;
        preview.style.color = text;

        if (isModernPro) {
            const lightMode = scope === 'light';
            const ghostAccent = accent;
            const ghostAccentSoft = mixHex(ghostAccent, '#FFFFFF', 0.24);
            const primaryDark = mixHex(primary, '#0F172A', 0.18);
            const secondaryDark = mixHex(secondary, '#0F172A', 0.16);
            const buttonSecondaryBg = lightMode ? surface : secondary;
            const buttonSecondaryColor = lightMode ? text : '#FFFFFF';
            const buttonSecondaryBorder = lightMode ? border : secondary;
            const buttonSecondaryShadow = lightMode
                ? `0 6px 12px ${rgba(secondary, 0.16)}`
                : `0 10px 20px -6px ${rgba(secondary, 0.45)}`;

            preview.style.setProperty('--fc-btn-font-weight', '700');
            preview.style.setProperty('--fc-btn-radius', resolveButtonRadiusFromCustomCss('999px'));
            preview.style.setProperty('--fc-btn-primary-bg', primary);
            preview.style.setProperty('--fc-btn-primary-border', primaryDark);
            preview.style.setProperty('--fc-btn-primary-color', '#FFFFFF');
            preview.style.setProperty('--fc-btn-primary-shadow', `0 8px 18px ${rgba(primary, lightMode ? 0.18 : 0.28)}`);
            preview.style.setProperty('--fc-btn-primary-hover-bg', primaryDark);
            preview.style.setProperty('--fc-btn-primary-hover-border', primaryDark);
            preview.style.setProperty('--fc-btn-primary-hover-color', '#FFFFFF');
            preview.style.setProperty('--fc-btn-primary-hover-shadow', `0 14px 24px ${rgba(primary, lightMode ? 0.24 : 0.34)}`);
            preview.style.setProperty('--fc-btn-primary-active-bg', mixHex(primary, '#0F172A', 0.28));
            preview.style.setProperty('--fc-btn-primary-active-border', mixHex(primary, '#0F172A', 0.28));
            preview.style.setProperty('--fc-btn-primary-active-color', '#FFFFFF');

            preview.style.setProperty('--fc-btn-secondary-bg', buttonSecondaryBg);
            preview.style.setProperty('--fc-btn-secondary-border', buttonSecondaryBorder);
            preview.style.setProperty('--fc-btn-secondary-color', buttonSecondaryColor);
            preview.style.setProperty('--fc-btn-secondary-shadow', buttonSecondaryShadow);
            preview.style.setProperty('--fc-btn-secondary-hover-bg', lightMode ? adjustHex(surface, 0.08) : secondaryDark);
            preview.style.setProperty('--fc-btn-secondary-hover-border', lightMode ? mixHex(border, '#94A3B8', 0.32) : secondaryDark);
            preview.style.setProperty('--fc-btn-secondary-hover-color', buttonSecondaryColor);
            preview.style.setProperty('--fc-btn-secondary-hover-shadow', `0 12px 22px ${rgba(secondary, lightMode ? 0.18 : 0.28)}`);
            preview.style.setProperty('--fc-btn-secondary-active-bg', lightMode ? mixHex(adjustHex(surface, 0.08), border, 0.4) : mixHex(secondaryDark, '#0F172A', 0.2));
            preview.style.setProperty('--fc-btn-secondary-active-border', lightMode ? mixHex(border, '#94A3B8', 0.32) : mixHex(secondaryDark, '#0F172A', 0.2));
            preview.style.setProperty('--fc-btn-secondary-active-color', buttonSecondaryColor);

            preview.style.setProperty('--fc-btn-ghost-bg', lightMode ? 'transparent' : rgba(ghostAccent, 0.08));
            preview.style.setProperty('--fc-btn-ghost-border', rgba(ghostAccent, lightMode ? 0.34 : 0.46));
            preview.style.setProperty('--fc-btn-ghost-color', lightMode ? ghostAccent : ghostAccentSoft);
            preview.style.setProperty('--fc-btn-ghost-shadow', lightMode ? 'none' : 'inset 0 1px 0 rgba(255, 255, 255, 0.08)');
            preview.style.setProperty('--fc-btn-ghost-hover-bg', lightMode ? ghostAccent : mixHex(ghostAccent, '#0F172A', 0.18));
            preview.style.setProperty('--fc-btn-ghost-hover-border', lightMode ? ghostAccent : ghostAccentSoft);
            preview.style.setProperty('--fc-btn-ghost-hover-color', '#FFFFFF');
            preview.style.setProperty('--fc-btn-ghost-hover-shadow', `0 12px 22px ${rgba(ghostAccent, 0.32)}`);
            applyComponentCustomization();
            return;
        }

        preview.style.setProperty('--btn-font-weight', '500');
        preview.style.setProperty('--btn-radius', '0.5rem');
        preview.style.setProperty('--btn-primary-bg', primary);
        preview.style.setProperty('--btn-primary-bg-hover', primaryDark);
        preview.style.setProperty('--btn-primary-bg-active', adjustHex(primary, 0.24));
        preview.style.setProperty('--btn-primary-color', '#FFFFFF');
        preview.style.setProperty('--btn-primary-shadow', `0 4px 10px ${rgba(primary, 0.24)}`);
        preview.style.setProperty('--btn-primary-shadow-hover', `0 10px 15px -3px ${rgba(primary, 0.4)}`);

        preview.style.setProperty('--btn-secondary-bg', surface);
        preview.style.setProperty('--btn-secondary-bg-hover', adjustHex(surface, 0.08));
        preview.style.setProperty('--btn-secondary-bg-active', adjustHex(surface, 0.14));
        preview.style.setProperty('--btn-secondary-color', text);
        preview.style.setProperty('--btn-secondary-color-hover', text);
        preview.style.setProperty('--btn-secondary-color-active', text);
        preview.style.setProperty('--btn-secondary-border', border);
        preview.style.setProperty('--btn-secondary-border-hover', adjustHex(border, 0.1));
        preview.style.setProperty('--btn-secondary-shadow', '0 1px 2px rgba(15, 23, 42, 0.06)');
        preview.style.setProperty('--btn-secondary-shadow-hover', '0 6px 12px rgba(148, 163, 184, 0.2)');

        preview.style.setProperty('--btn-ghost-bg', 'transparent');
        preview.style.setProperty('--btn-ghost-color', accent);
        preview.style.setProperty('--btn-ghost-border', border);
        preview.style.setProperty('--btn-ghost-bg-hover', adjustHex(surface, 0.08));
        preview.style.setProperty('--btn-ghost-color-hover', text);
        preview.style.setProperty('--btn-ghost-border-hover', accent);
        preview.style.setProperty('--btn-ghost-shadow', 'none');
        preview.style.setProperty('--btn-ghost-shadow-hover', 'none');

        preview.style.setProperty('--btn-outline-bg', 'transparent');
        preview.style.setProperty('--btn-outline-color', accent);
        preview.style.setProperty('--btn-outline-border', border);
        preview.style.setProperty('--btn-outline-bg-hover', adjustHex(surface, 0.08));
        preview.style.setProperty('--btn-outline-color-hover', text);
        preview.style.setProperty('--btn-outline-border-hover', accent);
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

    if (resetModal) {
        resetModal.addEventListener('click', function(event) {
            if (event.target === resetModal) {
                closeResetModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !resetModal.hidden) {
                closeResetModal();
            }
        });
    }

    updatePreview();
})();
