/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const selector = '.pb-logo-cloud-model-classic';
    const maxScaleBoost = 0.52;
    const easingPower = 1.35;

    const computeScale = (itemRect, rowRect) => {
        const rowCenter = rowRect.left + (rowRect.width / 2);
        const itemCenter = itemRect.left + (itemRect.width / 2);
        const maxDistance = Math.max(1, rowRect.width / 2);
        const distance = Math.abs(itemCenter - rowCenter);
        const normalized = Math.min(1, distance / maxDistance);
        const influence = Math.pow(1 - normalized, easingPower);
        const scale = 1 + (maxScaleBoost * influence);
        const zIndex = 1 + Math.round(influence * 100);

        return {
            scale,
            zIndex,
        };
    };

    const initWidget = (widget) => {
        if (!(widget instanceof HTMLElement) || widget.__flatcmsLogoCloudClassicReady) {
            return;
        }

        const row = widget.querySelector('.pb-logo-cloud-classic-row');
        const track = widget.querySelector('.pb-logo-cloud-classic-track');
        if (!(row instanceof HTMLElement) || !(track instanceof HTMLElement)) {
            return;
        }

        const items = Array.from(track.querySelectorAll('.pb-logo-cloud-classic-item'));
        if (!items.length) {
            return;
        }

        const state = {
            rafId: 0,
        };

        const tick = () => {
            if (!document.contains(widget)) {
                if (state.rafId) {
                    window.cancelAnimationFrame(state.rafId);
                    state.rafId = 0;
                }
                return;
            }

            const rowRect = row.getBoundingClientRect();
            if (rowRect.width > 0 && rowRect.height > 0) {
                items.forEach((item) => {
                    const itemRect = item.getBoundingClientRect();
                    const { scale, zIndex } = computeScale(itemRect, rowRect);
                    item.style.setProperty('--pb-logo-cloud-classic-scale', scale.toFixed(4));
                    item.style.setProperty('--pb-logo-cloud-classic-z', String(zIndex));
                });
            }

            state.rafId = window.requestAnimationFrame(tick);
        };

        const scheduleTick = () => {
            if (state.rafId) {
                return;
            }
            state.rafId = window.requestAnimationFrame(tick);
        };

        scheduleTick();
        window.addEventListener('resize', scheduleTick);
        widget.__flatcmsLogoCloudClassicReady = true;
    };

    const initAll = (root) => {
        const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
        const widgets = [];

        if (root instanceof Element && root.matches(selector)) {
            widgets.push(root);
        }

        widgets.push(...Array.from(scope.querySelectorAll(selector)));
        widgets.forEach((widget) => {
            initWidget(widget);
        });
    };

    const observe = () => {
        if (!(document.body instanceof HTMLElement) || typeof MutationObserver !== 'function') {
            return;
        }

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (!(node instanceof Element)) {
                        return;
                    }
                    initAll(node);
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initAll(document);
            observe();
        }, { once: true });
    } else {
        initAll(document);
        observe();
    }
})();
