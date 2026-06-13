/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const selector = '[data-snap-cards="1"]';
    const desktopQuery = '(max-width: 900px)';

    const initWidget = (widget) => {
        if (!(widget instanceof HTMLElement) || widget.__flatcmsSnapCardsReady) {
            return;
        }

        const track = widget.querySelector('.pb-snap-cards-track');
        if (!(track instanceof HTMLElement)) {
            return;
        }

        const cards = Array.from(track.querySelectorAll('.pb-snap-card'));
        if (!cards.length) {
            return;
        }

        const controls = widget.querySelector('[data-snap-cards-controls]');
        const prevButton = widget.querySelector('[data-snap-cards-prev]');
        const nextButton = widget.querySelector('[data-snap-cards-next]');
        const swipeHint = widget.querySelector('[data-mobile-swipe-hint]');
        const canMatchMedia = typeof window.matchMedia === 'function';
        const state = {
            activeIndex: -1,
            rafId: 0,
            swipeHintDismissed: false,
        };

        const isDesktopViewport = () => (canMatchMedia ? !window.matchMedia(desktopQuery).matches : true);
        const isSwipeViewport = () => (canMatchMedia ? window.matchMedia(desktopQuery).matches : false);
        const hasOverflow = () => Math.ceil(track.scrollWidth - track.clientWidth) > 8;
        const clearActiveCards = () => {
            state.activeIndex = -1;
            cards.forEach((card) => {
                card.classList.remove('is-center');
            });
        };
        const syncSwipeHint = () => {
            if (!(swipeHint instanceof HTMLElement)) {
                return;
            }

            const shouldShow = isSwipeViewport() && hasOverflow() && !state.swipeHintDismissed;
            swipeHint.classList.toggle('is-visible', shouldShow);
        };
        const dismissSwipeHint = () => {
            if (state.swipeHintDismissed) {
                return;
            }
            state.swipeHintDismissed = true;
            syncSwipeHint();
        };

        const scrollToCard = (card, smooth) => {
            if (!(card instanceof HTMLElement)) {
                return;
            }

            const trackRect = track.getBoundingClientRect();
            const cardRect = card.getBoundingClientRect();
            const offsetLeft = (cardRect.left - trackRect.left) + track.scrollLeft;
            const targetLeft = offsetLeft - ((trackRect.width / 2) - (cardRect.width / 2));
            const maxLeft = Math.max(0, track.scrollWidth - track.clientWidth);
            const nextLeft = Math.max(0, Math.min(maxLeft, targetLeft));
            if (typeof track.scrollTo === 'function') {
                track.scrollTo({
                    left: nextLeft,
                    behavior: smooth ? 'smooth' : 'auto',
                });
            } else {
                track.scrollLeft = nextLeft;
            }
        };

        const setActiveCard = (nextIndex, centerCard, smooth) => {
            const boundedIndex = Math.max(0, Math.min(cards.length - 1, nextIndex));
            if (state.activeIndex !== boundedIndex) {
                state.activeIndex = boundedIndex;
            }

            cards.forEach((card, cardIndex) => {
                card.classList.toggle('is-center', cardIndex === boundedIndex);
            });
            const activeCard = cards[boundedIndex] || null;

            if (centerCard && activeCard) {
                scrollToCard(activeCard, smooth);
            }
        };

        const resolveCenteredIndex = () => {
            const trackRect = track.getBoundingClientRect();
            const trackCenter = trackRect.left + (trackRect.width / 2);
            let bestIndex = 0;
            let bestDistance = Number.POSITIVE_INFINITY;

            cards.forEach((card, cardIndex) => {
                const cardRect = card.getBoundingClientRect();
                const cardCenter = cardRect.left + (cardRect.width / 2);
                const distance = Math.abs(cardCenter - trackCenter);
                if (distance < bestDistance) {
                    bestDistance = distance;
                    bestIndex = cardIndex;
                }
            });

            return bestIndex;
        };

        const syncControls = () => {
            const canNavigate = cards.length > 1 && hasOverflow();
            const showDesktopControls = isDesktopViewport() && canNavigate;

            if (controls instanceof HTMLElement) {
                controls.hidden = !showDesktopControls;
            }

            if (prevButton instanceof HTMLButtonElement) {
                prevButton.disabled = !canNavigate;
            }

            if (nextButton instanceof HTMLButtonElement) {
                nextButton.disabled = !canNavigate;
            }

            syncSwipeHint();
        };

        const queueCenterSync = () => {
            syncControls();
            if (!isDesktopViewport()) {
                clearActiveCards();
                return;
            }

            if (state.rafId) {
                return;
            }

            state.rafId = window.requestAnimationFrame(() => {
                state.rafId = 0;
                setActiveCard(resolveCenteredIndex(), false, false);
            });
        };

        const stepBy = (delta, smooth) => {
            if (cards.length <= 1 || !hasOverflow()) {
                return;
            }

            const baseIndex = state.activeIndex >= 0 ? state.activeIndex : 0;
            const nextIndex = (baseIndex + delta + cards.length) % cards.length;
            setActiveCard(nextIndex, true, smooth);
        };

        if (prevButton instanceof HTMLButtonElement) {
            prevButton.addEventListener('click', () => {
                stepBy(-1, true);
            });
        }

        if (nextButton instanceof HTMLButtonElement) {
            nextButton.addEventListener('click', () => {
                stepBy(1, true);
            });
        }

        cards.forEach((card, cardIndex) => {
            card.addEventListener('click', (event) => {
                if (event.target instanceof Element && event.target.closest('a')) {
                    return;
                }
                if (!isDesktopViewport()) {
                    return;
                }
                setActiveCard(cardIndex, true, true);
            });

            card.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }
                event.preventDefault();
                if (!isDesktopViewport()) {
                    return;
                }
                setActiveCard(cardIndex, true, true);
            });

            const image = card.querySelector('.pb-snap-card-image');
            if (image instanceof HTMLImageElement && !image.complete) {
                image.addEventListener('load', queueCenterSync, { once: true });
                image.addEventListener('error', queueCenterSync, { once: true });
            }
        });

        track.addEventListener('scroll', () => {
            if (track.scrollLeft > 6) {
                dismissSwipeHint();
            }
            queueCenterSync();
        }, { passive: true });
        track.addEventListener('pointerup', queueCenterSync);
        track.addEventListener('touchend', queueCenterSync, { passive: true });
        window.addEventListener('resize', queueCenterSync);

        const initialIndex = cards.length >= 2 ? 1 : 0;
        if (isDesktopViewport()) {
            setActiveCard(initialIndex, false, false);
        } else {
            clearActiveCards();
        }
        syncControls();
        queueCenterSync();

        widget.__flatcmsSnapCardsReady = true;
    };

    const initAll = (root) => {
        const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
        const widgets = Array.from(scope.querySelectorAll(selector));
        widgets.forEach((widget) => {
            initWidget(widget);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initAll(document);
        }, { once: true });
    } else {
        initAll(document);
    }
})();
