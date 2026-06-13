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

    const escapeFallback = (value) => String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    registry.pricing_plans = function(settings, context) {
        const helpers = (context && context.helpers) ? context.helpers : {};
        const escapeHtml = helpers.escape || escapeFallback;
        const escapeAttr = helpers.escapeAttr || escapeFallback;
        const label = helpers.label || ((_key, fallback) => String(fallback || ''));
        const previewId = `pb-pricing-plans-preview-${Math.random().toString(36).slice(2, 10)}`;

        const parseRepeater = (raw, delimiter = '\n') => {
            const value = String(raw === null || raw === undefined ? '' : raw).trim();
            if (!value) {
                return [];
            }

            if (value.startsWith('[')) {
                try {
                    const parsed = JSON.parse(value);
                    if (Array.isArray(parsed)) {
                        return parsed.map((item) => String(item === null || item === undefined ? '' : item).trim());
                    }
                } catch (error) {
                    // Ignore JSON parse errors and fallback to plain text.
                }
            }

            const items = delimiter && value.includes(delimiter)
                ? value.split(delimiter)
                : value.split(/\r\n|\r|\n/);

            while (items.length && String(items[items.length - 1] || '').trim() === '') {
                items.pop();
            }

            return items.map((item) => String(item || '').trim());
        };

        const parseFeatureGroups = (raw) => parseRepeater(raw, '\n---\n');
        const parseFeatureItems = (raw) => parseRepeater(raw, '\n')
            .map((item) => String(item || '').replace(/^[-*•\s]+/, '').trim())
            .filter((item) => item !== '');

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

        const normalizeTarget = (value, fallback = '_self') => {
            const safe = String(value || '').trim();
            return ['_self', '_blank'].includes(safe) ? safe : fallback;
        };

        const normalizeButtonVariant = (value) => {
            const safe = String(value || '').trim().toLowerCase();
            return ['primary', 'secondary'].includes(safe) ? safe : 'ghost';
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

        const sanitizeIconClass = (value) => String(value || '')
            .trim()
            .split(/\s+/)
            .filter((token) => /^[a-z0-9_-]+$/i.test(token))
            .join(' ');

        const hasDigitValue = (value) => /\d/u.test(String(value || ''));

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

        const applyFlexAlign = (elements, align) => {
            const safeAlign = normalizeAlign(align, 'left');
            const justifyContent = safeAlign === 'center'
                ? 'center'
                : (safeAlign === 'right' ? 'flex-end' : 'flex-start');

            Array.from(elements || []).forEach((element) => {
                if (!(element instanceof HTMLElement)) {
                    return;
                }

                element.style.justifyContent = justifyContent;
            });
        };

        const schedulePreviewSync = (id, styleMap, attempts = 5) => {
            if (!window.requestAnimationFrame) {
                return;
            }

            window.requestAnimationFrame(() => {
                const root = document.querySelector(`[data-pricing-plans-preview-id="${id}"]`);
                if (!(root instanceof HTMLElement)) {
                    if (attempts > 0) {
                        schedulePreviewSync(id, styleMap, attempts - 1);
                    }
                    return;
                }

                applyTextStyle(root.querySelectorAll('.pb-pricing-plans-title'), styleMap.titleStyle);
                applyTextStyle(root.querySelectorAll('.pb-pricing-plans-subtitle'), styleMap.subtitleStyle);
                applyTextStyle(root.querySelectorAll('.pb-pricing-plan-name'), styleMap.planTitleStyle);
                applyTextStyle(root.querySelectorAll('.pb-pricing-plan-price'), styleMap.priceStyle);
                applyTextStyle(root.querySelectorAll('.pb-pricing-plan-period'), styleMap.periodStyle);
                applyTextStyle(root.querySelectorAll('.pb-pricing-plan-description-text'), styleMap.descriptionStyle);
                const featureAlign = normalizeAlign(styleMap.featureStyle.align, 'left');
                const featureJustify = featureAlign === 'center'
                    ? 'center'
                    : (featureAlign === 'right' ? 'end' : 'start');
                const featureTextAlign = featureAlign === 'center' ? 'left' : featureAlign;
                root.querySelectorAll('.pb-pricing-plan-features').forEach((node) => {
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    node.style.justifyItems = featureAlign === 'center' ? 'stretch' : featureJustify;
                    node.style.justifySelf = featureAlign === 'center' ? 'center' : '';
                    node.style.width = featureAlign === 'center' ? 'fit-content' : '';
                    node.style.maxWidth = featureAlign === 'center' ? '100%' : '';
                    node.style.textAlign = featureAlign === 'center' ? 'left' : '';
                });
                root.querySelectorAll('.pb-pricing-plan-feature-text').forEach((node) => {
                    applyTextStyle([node], styleMap.featureStyle);
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    node.style.textAlign = featureTextAlign;
                    node.style.flex = featureAlign === 'center' ? '1 1 auto' : '';
                    node.style.width = featureAlign === 'center' ? '100%' : '';
                });
                root.querySelectorAll('.pb-pricing-plan-feature').forEach((node) => {
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
                applyTextStyle(root.querySelectorAll('.pb-pricing-plan-badge'), styleMap.badgeStyle);
                applyFlexAlign(root.querySelectorAll('.pb-pricing-plan-badge-line'), styleMap.badgeStyle.align);
                applyFlexAlign(root.querySelectorAll('.pb-pricing-plan-top'), styleMap.planTitleStyle.align);
                applyFlexAlign(root.querySelectorAll('.pb-pricing-plan-price-row'), styleMap.priceStyle.align);

                if (window.FlatCMSPricingPlans && typeof window.FlatCMSPricingPlans.init === 'function') {
                    window.FlatCMSPricingPlans.init(root);
                }
            });
        };

        const title = String(settings.title || label('pricing_plans_default_title', '')).trim();
        const subtitle = String(settings.subtitle || label('pricing_plans_default_subtitle', '')).trim();

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

        const planNames = parseRepeater(settings.planNames || label('pricing_plans_default_plan_names', ''));
        const planMonthlyPrices = parseRepeater(settings.planPrices || label('pricing_plans_default_plan_prices', ''));
        const legacyPlanYearlyPrices = parseRepeater(settings.planPeriods || '');
        const hasExplicitPlanYearlyPrices = Object.prototype.hasOwnProperty.call(settings || {}, 'planYearlyPrices');
        const hasLegacyPlanPeriods = Object.prototype.hasOwnProperty.call(settings || {}, 'planPeriods');
        const yearlyPriceSource = hasExplicitPlanYearlyPrices
            ? settings.planYearlyPrices
            : (hasLegacyPlanPeriods ? settings.planPeriods : label('pricing_plans_default_plan_yearly_prices', ''));
        const planYearlyPrices = parseRepeater(yearlyPriceSource || '');
        const planDescriptions = parseRepeater(settings.planDescriptions || label('pricing_plans_default_plan_descriptions', ''));
        const planFeatures = parseFeatureGroups(settings.planFeatures || label('pricing_plans_default_plan_features', ''));
        const planBadges = parseRepeater(settings.planBadges || label('pricing_plans_default_plan_badges', ''));
        const planIcons = parseRepeater(settings.planIcons || '');
        const featuredPlans = parseRepeater(settings.featuredPlans || '');
        const ctaEnableds = parseRepeater(settings.ctaEnableds || 'on');
        const ctaLabels = parseRepeater(settings.ctaLabels || label('pricing_plans_default_cta_labels', ''));
        const ctaLinks = parseRepeater(settings.ctaLinks || '');
        const ctaTargets = parseRepeater(settings.ctaTargets || '');
        const ctaVariants = parseRepeater(settings.ctaVariants || '');
        const ctaAligns = parseRepeater(settings.ctaAligns || '');

        const columns = Math.max(1, Math.min(4, Math.trunc(Number(settings.columns || 3)) || 3));
        const align = normalizeAlign(settings.align || 'left', 'left');
        const variant = normalizeVariant(settings.variant || 'subtle');
        const showHeader = normalizeToggle(settings.showHeader, true);
        const showBadges = normalizeToggle(settings.showBadges, true);
        const showDescriptions = normalizeToggle(settings.showDescriptions, true);
        const showFeatures = normalizeToggle(settings.showFeatures, true);
        const showPopular = normalizeToggle(settings.showPopular, true);
        const popularBadgeLabel = String(settings.popularBadgeLabel || label('pricing_plans_badge_popular', 'Populaire') || '').trim();
        const billingMonthlyLabel = String(settings.billingMonthlyLabel || label('pricing_plans_toggle_monthly', 'Mensuel') || '').trim();
        const billingYearlyLabel = String(settings.billingYearlyLabel || label('pricing_plans_toggle_yearly', 'Annuel') || '').trim();
        const billingSavingsLabel = String(settings.billingSavingsLabel || label('pricing_plans_toggle_savings', 'Économisez 15 %') || '').trim();
        const billingGroupLabel = String(label('pricing_plans_toggle_group_label', 'Période de facturation') || '').trim();
        const featuresHeading = String(settings.featuresHeading || label('pricing_plans_features_heading', 'Tout ce qui est inclus') || '').trim();
        const monthlyIntervalLabel = String(settings.monthlyIntervalLabel || label('pricing_plans_price_interval_monthly', '/mois') || '').trim();
        const yearlyIntervalLabel = String(settings.yearlyIntervalLabel || label('pricing_plans_price_interval_yearly', '/an') || '').trim();
        const billingMode = 'monthly';

        const titleStyle = resolveTextStyle(settings, 'titleStyle', align);
        const subtitleStyle = resolveTextStyle(settings, 'subtitleStyle', align);
        const planTitleStyle = resolveTextStyle(settings, 'planTitleStyle', align);
        const priceStyle = resolveTextStyle(settings, 'priceStyle', align);
        const periodStyle = resolveTextStyle(settings, 'periodStyle', align);
        const descriptionStyle = resolveTextStyle(settings, 'descriptionStyle', align);
        const featureStyle = resolveTextStyle(settings, 'featureStyle', align);
        const badgeStyle = resolveTextStyle(settings, 'badgeStyle', align);

        const count = Math.min(Math.max(
            planNames.length,
            planMonthlyPrices.length,
            planYearlyPrices.length,
            planDescriptions.length,
            planFeatures.length,
            planBadges.length,
            planIcons.length,
            featuredPlans.length,
            ctaEnableds.length,
            ctaLabels.length,
            ctaLinks.length,
            ctaTargets.length,
            ctaVariants.length,
            ctaAligns.length,
            1
        ), 8);

        const plans = [];
        for (let index = 0; index < count; index += 1) {
            const planIndex = index + 1;
            const name = String(planNames[index] || '').trim();
            let monthlyPrice = String(planMonthlyPrices[index] || '').trim();
            let yearlyPrice = String(planYearlyPrices[index] || '').trim();
            if (!yearlyPrice) {
                const legacyYearlyPrice = String(legacyPlanYearlyPrices[index] || '').trim();
                if (hasDigitValue(legacyYearlyPrice)) {
                    yearlyPrice = legacyYearlyPrice;
                }
            }
            if (!hasDigitValue(monthlyPrice) && hasDigitValue(yearlyPrice)) {
                monthlyPrice = yearlyPrice;
            }
            if (!hasDigitValue(yearlyPrice)) {
                yearlyPrice = monthlyPrice;
            }

            const initialPrice = billingMode === 'monthly'
                ? (monthlyPrice || yearlyPrice)
                : (yearlyPrice || monthlyPrice);
            const initialInterval = billingMode === 'monthly' ? monthlyIntervalLabel : yearlyIntervalLabel;
            const description = String(planDescriptions[index] || '').trim();
            const badge = String(planBadges[index] || '').trim();
            const iconClass = sanitizeIconClass(planIcons[index] || '');
            const isFeatured = normalizeToggle(featuredPlans[index] || 'off', false);
            const features = parseFeatureItems(planFeatures[index] || '');
            const ctaEnabled = normalizeToggle(ctaEnableds[index] || 'on', true);
            const ctaLabel = String(ctaLabels[index] || '').trim();
            const ctaUrl = sanitizeUrl(String(ctaLinks[index] || '').trim());
            const ctaTarget = ctaUrl ? normalizeTarget(ctaTargets[index] || '_self', '_self') : '_self';
            const ctaVariant = normalizeButtonVariant(ctaVariants[index] || (isFeatured ? 'primary' : 'ghost'));
            const ctaAlign = normalizeAlign(ctaAligns[index] || 'left', 'left');
            const showPopularBadge = showPopular && isFeatured && popularBadgeLabel !== '';
            const showCustomBadge = showBadges && badge !== '' && !showPopularBadge;

            const hasSwipeHint = count > 1 && index === 0;
            let cardHtml = `<article class="pb-pricing-plan${isFeatured ? ' is-featured' : ''}${hasSwipeHint ? ' has-mobile-swipe-hint' : ''}" data-pricing-plan-index="${escapeAttr(planIndex)}">`;
            cardHtml += `<div class="pb-pricing-plan-surface pb-card ${escapeAttr((isFeatured || variant === 'strong') ? 'pb-card-strong' : 'pb-card-subtle')}" aria-hidden="true"></div>`;
            cardHtml += '<div class="pb-pricing-plan-content">';
            cardHtml += '<div class="pb-pricing-plan-badge-stack"><div class="pb-pricing-plan-badge-line">';
            if (showPopularBadge) {
                cardHtml += `<span class="pb-pricing-plan-badge pb-pricing-plan-badge-popular"><i class="fa-classic fa-solid fa-crown pb-pricing-plan-badge-icon" aria-hidden="true"></i><span class="pb-pricing-plan-badge-text">${escapeHtml(popularBadgeLabel)}</span></span>`;
            } else if (showCustomBadge) {
                cardHtml += renderStyledText(badge, 'span', 'pb-pricing-plan-badge', badgeStyle);
            }
            cardHtml += '</div></div>';

            cardHtml += '<div class="pb-pricing-plan-top"><div class="pb-pricing-plan-title-wrap">';
            if (iconClass) {
                cardHtml += `<span class="pb-pricing-plan-icon" aria-hidden="true"><i class="${escapeAttr(iconClass)}"></i></span>`;
            }
            cardHtml += renderStyledText(name, 'h3', 'pb-pricing-plan-name', planTitleStyle);
            cardHtml += '</div></div>';

            if (showDescriptions && description) {
                cardHtml += `<div class="pb-pricing-plan-description">${renderStyledText(description, 'span', 'pb-pricing-plan-description-text', descriptionStyle)}</div>`;
            }

            cardHtml += `<div class="pb-pricing-plan-price-row"><span class="pb-pricing-plan-price" data-pricing-plan-amount data-price-monthly="${escapeAttr(monthlyPrice)}" data-price-yearly="${escapeAttr(yearlyPrice)}"><span class="pb-styled-text-content">${escapeHtml(initialPrice)}</span></span>`;
            cardHtml += `<span class="pb-pricing-plan-period" data-pricing-plan-interval data-interval-monthly="${escapeAttr(monthlyIntervalLabel)}" data-interval-yearly="${escapeAttr(yearlyIntervalLabel)}"><span class="pb-styled-text-content">${escapeHtml(initialInterval)}</span></span></div>`;

            cardHtml += '<div class="pb-pricing-plan-divider" aria-hidden="true"></div>';

            if (showFeatures) {
                cardHtml += '<div class="pb-pricing-plan-features-group">';
                if (featuresHeading) {
                    cardHtml += `<p class="pb-pricing-plan-features-title"><span class="pb-styled-text-content">${escapeHtml(featuresHeading)}</span></p>`;
                }
                cardHtml += '<ul class="pb-pricing-plan-features">';
                features.forEach((feature) => {
                    cardHtml += `<li class="pb-pricing-plan-feature">${renderStyledText(feature, 'span', 'pb-pricing-plan-feature-text', featureStyle)}</li>`;
                });
                cardHtml += '</ul></div>';
            }

            if (ctaEnabled) {
                cardHtml += `<div class="pb-pricing-plan-footer pb-pricing-plan-footer-align-${escapeAttr(ctaAlign)}">`;
                if (ctaLabel && ctaUrl) {
                    const rel = ctaTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                    cardHtml += `<a class="btn btn-${escapeAttr(ctaVariant)} pb-btn pb-btn-${escapeAttr(ctaVariant)} pb-pricing-plan-cta" href="${escapeAttr(ctaUrl)}" target="${escapeAttr(ctaTarget)}"${rel}>${escapeHtml(ctaLabel)}</a>`;
                } else if (ctaLabel) {
                    cardHtml += `<span class="btn btn-${escapeAttr(ctaVariant)} pb-btn pb-btn-${escapeAttr(ctaVariant)} pb-pricing-plan-cta is-static" aria-disabled="true">${escapeHtml(ctaLabel)}</span>`;
                }
                cardHtml += '</div>';
            }

            if (!name && !monthlyPrice && !yearlyPrice && !description && !features.length && !ctaLabel) {
                cardHtml += `<div class="pb-empty">${escapeHtml(label('pricing_plans_empty', 'Ajoutez au moins une formule pour afficher votre bloc tarifaire.'))}</div>`;
            }

            if (hasSwipeHint) {
                cardHtml += `
                    <div class="pb-mobile-swipe-hint" data-mobile-swipe-hint aria-hidden="true">
                        <span class="pb-mobile-swipe-hint-core">
                            <span class="pb-mobile-swipe-hint-trail"></span>
                            <i class="fa-classic fa-solid fa-hand-pointer pb-mobile-swipe-hint-hand" aria-hidden="true"></i>
                        </span>
                    </div>
                `;
            }
            cardHtml += '</div>';
            cardHtml += '</article>';
            plans.push(cardHtml);
        }

        let headerHtml = '';
        if (showHeader && (title || subtitle)) {
            headerHtml = `<header class="pb-pricing-plans-header">${renderStyledText(title, 'h2', 'pb-pricing-plans-title', titleStyle)}${renderStyledText(subtitle, 'p', 'pb-pricing-plans-subtitle', subtitleStyle)}</header>`;
        }

        let billingHtml = `<div class="pb-pricing-plans-toggle-wrap"><div class="pb-pricing-plans-toggle" data-pricing-plans-toggle role="group" aria-label="${escapeAttr(billingGroupLabel)}"><span class="pb-pricing-plans-toggle-indicator" aria-hidden="true"></span><button type="button" class="pb-pricing-plans-toggle-btn is-active" data-billing-choice="monthly" aria-pressed="true">${escapeHtml(billingMonthlyLabel)}</button><button type="button" class="pb-pricing-plans-toggle-btn" data-billing-choice="yearly" aria-pressed="false">${escapeHtml(billingYearlyLabel)}</button></div>`;
        if (billingSavingsLabel) {
            billingHtml += `<div class="pb-pricing-plans-saving"><span class="pb-pricing-plans-saving-line" aria-hidden="true"></span><span class="pb-pricing-plans-saving-badge">${escapeHtml(billingSavingsLabel)}</span></div>`;
        }
        billingHtml += '</div>';

        const html = `<section class="pb-pricing-plans pb-pricing-plans-variant-${escapeAttr(variant)} pb-pricing-plans-align-${escapeAttr(align)}" data-pricing-plans-root data-billing-default="${escapeAttr(billingMode)}" data-billing-mode="${escapeAttr(billingMode)}" data-pricing-plans-preview-id="${escapeAttr(previewId)}"><div class="pb-pricing-plans-shell"><div class="pb-pricing-plans-orb" aria-hidden="true"></div><div class="pb-pricing-plans-frame">${headerHtml}${billingHtml}<div class="pb-pricing-plans-grid-shell"><div class="pb-pricing-plans-grid pb-pricing-plans-cols-${escapeAttr(columns)}">${plans.join('')}</div></div></div></div></section>`;

        schedulePreviewDesignSync(previewId, 'data-pricing-plans-preview-id', ['.pb-pricing-plan-surface'], ['.pb-pricing-plans-title', '.pb-pricing-plans-subtitle', '.pb-pricing-plan-name', '.pb-pricing-plan-description-text', '.pb-pricing-plan-price', '.pb-pricing-plan-period', '.pb-pricing-plan-features-title', '.pb-pricing-plan-feature-text', '.pb-pricing-plan-badge', '.pb-pricing-plan-icon'], resolveWidgetDesign(settings, 24));

        schedulePreviewSync(previewId, {
            titleStyle,
            subtitleStyle,
            planTitleStyle,
            priceStyle,
            periodStyle,
            descriptionStyle,
            featureStyle,
            badgeStyle,
        });

        return html;
    };
})();
