/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const getAccordionItems = (root) => Array.from(root.querySelectorAll('[data-faq-accordion-item]'));

    const getAccordionParts = (item) => {
        if (!(item instanceof HTMLElement)) {
            return { item: null, button: null, panel: null, inner: null };
        }

        return {
            item,
            button: item.querySelector('.pb-faq-accordion-toggle'),
            panel: item.querySelector('.pb-faq-accordion-panel'),
            inner: item.querySelector('.pb-faq-accordion-panel-inner'),
        };
    };

    const setItemState = (item, expanded) => {
        const { button, panel } = getAccordionParts(item);
        if (!(item instanceof HTMLElement) || !(button instanceof HTMLElement) || !(panel instanceof HTMLElement)) {
            return;
        }

        item.classList.toggle('is-active', expanded);
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        panel.hidden = !expanded;
        panel.style.height = expanded ? 'auto' : '0px';
    };

    const animateItemState = (item, expanded) => {
        const { button, panel, inner } = getAccordionParts(item);
        if (!(item instanceof HTMLElement) || !(button instanceof HTMLElement) || !(panel instanceof HTMLElement) || !(inner instanceof HTMLElement)) {
            return;
        }

        const previousHandler = panel._faqAccordionTransitionEnd;
        if (typeof previousHandler === 'function') {
            panel.removeEventListener('transitionend', previousHandler);
            panel._faqAccordionTransitionEnd = null;
        }

        panel.hidden = false;
        item.classList.toggle('is-active', expanded);
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');

        const currentHeight = panel.getBoundingClientRect().height;
        const targetHeight = expanded ? inner.scrollHeight : 0;

        if (Math.abs(currentHeight - targetHeight) < 1) {
            if (expanded) {
                panel.hidden = false;
                panel.style.height = 'auto';
            } else {
                panel.hidden = true;
                panel.style.height = '0px';
            }
            return;
        }

        panel.style.height = `${currentHeight}px`;
        panel.offsetHeight;
        panel.style.height = `${targetHeight}px`;

        const handleEnd = (event) => {
            if (event.propertyName !== 'height') {
                return;
            }

            panel.removeEventListener('transitionend', handleEnd);
            panel._faqAccordionTransitionEnd = null;
            if (expanded) {
                panel.style.height = 'auto';
            } else {
                panel.hidden = true;
                panel.style.height = '0px';
            }
        };

        panel._faqAccordionTransitionEnd = handleEnd;
        panel.addEventListener('transitionend', handleEnd);
    };

    const bindAccordion = (root) => {
        if (!(root instanceof HTMLElement) || root.dataset.faqAccordionBound === '1') {
            return;
        }

        root.dataset.faqAccordionBound = '1';
        const items = getAccordionItems(root);
        let activeSeen = false;

        items.forEach((item) => {
            if (!(item instanceof HTMLElement)) {
                return;
            }

            const isActive = item.classList.contains('is-active');
            if (isActive && !activeSeen) {
                activeSeen = true;
                setItemState(item, true);
            } else {
                setItemState(item, false);
            }

            const { button } = getAccordionParts(item);
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            button.addEventListener('click', (event) => {
                event.preventDefault();
                const shouldOpen = !item.classList.contains('is-active');

                items.forEach((other) => {
                    if (!(other instanceof HTMLElement)) {
                        return;
                    }
                    if (other === item) {
                        animateItemState(other, shouldOpen);
                        return;
                    }
                    animateItemState(other, false);
                });
            });
        });
    };

    const init = (scope) => {
        const root = scope && typeof scope.querySelectorAll === 'function' ? scope : document;

        if (root instanceof HTMLElement && root.matches('.pb-faq-accordion')) {
            bindAccordion(root);
        }

        root.querySelectorAll('.pb-faq-accordion').forEach((node) => {
            bindAccordion(node);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init(document), { once: true });
    } else {
        init(document);
    }

    window.FlatCMSFaqAccordion = { init };
})();
