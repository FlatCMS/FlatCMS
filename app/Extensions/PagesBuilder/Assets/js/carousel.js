/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const carousels = Array.from(document.querySelectorAll('[data-fc-carousel]'));
    if (!carousels.length) {
        return;
    }

    carousels.forEach((carousel) => {
        const slides = Array.from(carousel.querySelectorAll('[data-fc-carousel-slide]'));
        if (!slides.length) {
            return;
        }

        const track = carousel.querySelector('.fc-carousel-track');
        const indicators = Array.from(carousel.querySelectorAll('[data-fc-carousel-to]'));
        const prevBtn = carousel.querySelector('[data-fc-carousel-prev]');
        const nextBtn = carousel.querySelector('[data-fc-carousel-next]');
        let activeIndex = 0;
        let autoplayTimer = null;
        const transition = String(carousel.dataset.transition || 'slide');
        const autoplay = String(carousel.dataset.autoplay || '0') === '1';
        const loop = String(carousel.dataset.loop || '0') === '1';
        let delay = parseInt(String(carousel.dataset.delay || ''), 10);
        if (Number.isNaN(delay) || delay < 2000 || delay > 15000) {
            delay = 5000;
        }

        function setActive(index) {
            const nextIndex = clampIndex(index);
            activeIndex = nextIndex;
            slides.forEach((slide, idx) => {
                slide.classList.toggle('is-active', idx === nextIndex);
            });
            indicators.forEach((indicator, idx) => {
                indicator.classList.toggle('is-active', idx === nextIndex);
            });
            if (transition === 'slide' && track) {
                track.style.transform = `translateX(-${nextIndex * 100}%)`;
            }
        }

        function clampIndex(nextIndex) {
            if (nextIndex < 0) {
                return loop ? slides.length - 1 : 0;
            }
            if (nextIndex >= slides.length) {
                return loop ? 0 : slides.length - 1;
            }
            return nextIndex;
        }

        function goNext() {
            setActive(activeIndex + 1);
        }

        function goPrev() {
            setActive(activeIndex - 1);
        }

        function startAutoplay() {
            if (!autoplay || slides.length < 2) {
                return;
            }
            stopAutoplay();
            autoplayTimer = window.setInterval(() => {
                goNext();
            }, delay);
        }

        function stopAutoplay() {
            if (autoplayTimer) {
                window.clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                stopAutoplay();
                goPrev();
                startAutoplay();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                stopAutoplay();
                goNext();
                startAutoplay();
            });
        }

        indicators.forEach((indicator) => {
            indicator.addEventListener('click', () => {
                const index = parseInt(String(indicator.dataset.fcCarouselTo || '0'), 10);
                if (Number.isNaN(index)) {
                    return;
                }
                stopAutoplay();
                setActive(index);
                startAutoplay();
            });
        });

        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoplay();
            } else {
                startAutoplay();
            }
        });

        setActive(0);
        startAutoplay();
    });
})();
