/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const ROOT_SELECTOR = '[data-pricing-plans-root]';
    const MOBILE_QUERY = '(max-width: 900px)';

    const getMode = (root) => {
        const current = String(root.dataset.billingMode || '').trim().toLowerCase();
        if (current === 'monthly' || current === 'yearly') {
            return current;
        }

        const fallback = String(root.dataset.billingDefault || '').trim().toLowerCase();
        return fallback === 'monthly' ? 'monthly' : 'yearly';
    };

    const setMode = (root, mode) => {
        const safeMode = mode === 'monthly' ? 'monthly' : 'yearly';
        root.dataset.billingMode = safeMode;

        root.querySelectorAll('[data-billing-choice]').forEach((button) => {
            if (!(button instanceof HTMLElement)) {
                return;
            }

            const isActive = String(button.dataset.billingChoice || '') === safeMode;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        root.querySelectorAll('[data-pricing-plan-amount]').forEach((node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }

            const monthly = String(node.dataset.priceMonthly || '').trim();
            const yearly = String(node.dataset.priceYearly || '').trim();
            const value = safeMode === 'monthly'
                ? (monthly !== '' ? monthly : yearly)
                : (yearly !== '' ? yearly : monthly);

            const content = node.querySelector('.pb-styled-text-content');
            if (content instanceof HTMLElement) {
                content.textContent = value;
            } else {
                node.textContent = value;
            }
        });

        root.querySelectorAll('[data-pricing-plan-interval]').forEach((node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }

            const monthly = String(node.dataset.intervalMonthly || '').trim();
            const yearly = String(node.dataset.intervalYearly || '').trim();
            const value = safeMode === 'monthly'
                ? (monthly !== '' ? monthly : yearly)
                : (yearly !== '' ? yearly : monthly);

            const content = node.querySelector('.pb-styled-text-content');
            if (content instanceof HTMLElement) {
                content.textContent = value;
            } else {
                node.textContent = value;
            }
        });
    };

    const init = (root) => {
        if (!(root instanceof HTMLElement) || root.dataset.pricingPlansBound === '1') {
            return;
        }

        root.dataset.pricingPlansBound = '1';
        const initialMode = getMode(root);
        const grid = root.querySelector('.pb-pricing-plans-grid');
        const swipeHint = root.querySelector('[data-mobile-swipe-hint]');
        const canMatchMedia = typeof window.matchMedia === 'function';
        let swipeHintDismissed = false;

        const isSwipeViewport = () => (canMatchMedia ? window.matchMedia(MOBILE_QUERY).matches : false);
        const hasOverflow = () => grid instanceof HTMLElement && Math.ceil(grid.scrollWidth - grid.clientWidth) > 8;
        const syncSwipeHint = () => {
            if (!(swipeHint instanceof HTMLElement)) {
                return;
            }

            const shouldShow = isSwipeViewport() && hasOverflow() && !swipeHintDismissed;
            swipeHint.classList.toggle('is-visible', shouldShow);
        };
        const dismissSwipeHint = () => {
            if (swipeHintDismissed) {
                return;
            }
            swipeHintDismissed = true;
            syncSwipeHint();
        };

        root.querySelectorAll('[data-billing-choice]').forEach((button) => {
            if (!(button instanceof HTMLElement)) {
                return;
            }

            button.addEventListener('click', () => {
                const nextMode = String(button.dataset.billingChoice || '').trim().toLowerCase();
                if (nextMode !== 'monthly' && nextMode !== 'yearly') {
                    return;
                }
                setMode(root, nextMode);
            });
        });

        if (grid instanceof HTMLElement) {
            grid.addEventListener('scroll', () => {
                if (grid.scrollLeft > 6) {
                    dismissSwipeHint();
                }
                syncSwipeHint();
            }, { passive: true });
        }

        window.addEventListener('resize', syncSwipeHint);

        setMode(root, initialMode);
        syncSwipeHint();
    };

    const initAll = (scope) => {
        const root = scope instanceof HTMLElement || scope instanceof Document ? scope : document;
        root.querySelectorAll(ROOT_SELECTOR).forEach((node) => init(node));
    };

    window.FlatCMSPricingPlans = {
        init,
        initAll,
        setMode,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initAll(document), { once: true });
    } else {
        initAll(document);
    }
})();
