/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const SELECTOR = '[data-testimonial-cards="1"]';

    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

    const parseColor = (raw, fallback) => {
        const safe = String(raw || '').trim();
        if (!safe) {
            return fallback;
        }

        const rgbMatch = safe.match(/^rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})/i);
        if (rgbMatch) {
            return [
                clamp(Number.parseInt(rgbMatch[1], 10) || 0, 0, 255),
                clamp(Number.parseInt(rgbMatch[2], 10) || 0, 0, 255),
                clamp(Number.parseInt(rgbMatch[3], 10) || 0, 0, 255),
            ];
        }

        const hexMatch = safe.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (!hexMatch) {
            return fallback;
        }

        const hex = hexMatch[1];
        if (hex.length === 3) {
            return [
                Number.parseInt(`${hex[0]}${hex[0]}`, 16),
                Number.parseInt(`${hex[1]}${hex[1]}`, 16),
                Number.parseInt(`${hex[2]}${hex[2]}`, 16),
            ];
        }

        return [
            Number.parseInt(hex.slice(0, 2), 16),
            Number.parseInt(hex.slice(2, 4), 16),
            Number.parseInt(hex.slice(4, 6), 16),
        ];
    };

    const resolveWavePalette = (host) => {
        if (!(host instanceof HTMLElement)) {
            return {
                start: [22, 30, 50],
                mid: [209, 200, 237],
                end: [121, 77, 255],
            };
        }

        const style = window.getComputedStyle(host);

        return {
            start: parseColor(style.getPropertyValue('--color-text-secondary'), [22, 30, 50]),
            mid: parseColor(style.getPropertyValue('--color-secondary'), [209, 200, 237]),
            end: parseColor(style.getPropertyValue('--color-primary'), [121, 77, 255]),
        };
    };

    class TestimonialCloudWaves {
        constructor(canvas, host) {
            this.canvas = canvas;
            this.host = host instanceof HTMLElement ? host : null;
            this.ctx = this.canvas.getContext('2d');
            this.rafId = 0;
            this.resizeRaf = 0;

            if (!this.ctx) {
                return;
            }

            const data = this.canvas.dataset || {};
            this.settings = {
                waveCount: Math.max(3, Number.parseInt(data.waveCount || '', 10) || 10),
                amplitude: Math.max(12, Number.parseFloat(data.amplitude || '') || 50),
                baseSpeed: Math.max(0.0008, Number.parseFloat(data.baseSpeed || '') || 0.005),
                waveSpacing: Math.max(12, Number.parseFloat(data.waveSpacing || '') || 30),
                lineWidth: Math.max(0.5, Number.parseFloat(data.lineWidth || '') || 1),
                direction: data.direction === 'right' ? 'right' : 'left',
                leftOffset: Number.parseFloat(data.leftOffset || '') || 0,
                rightOffset: Number.parseFloat(data.rightOffset || '') || 0,
            };

            this.width = 0;
            this.height = 0;
            this.phases = Array.from({ length: this.settings.waveCount }, (_value, index) => index * 0.3);
            this.palette = resolveWavePalette(this.host || this.canvas.closest(SELECTOR));

            this.handleResize = this.queueResize.bind(this);
            this.animateFrame = this.animateFrame.bind(this);

            window.addEventListener('resize', this.handleResize, { passive: true });
            this.resize();
            this.rafId = window.requestAnimationFrame(this.animateFrame);
        }

        destroy() {
            window.removeEventListener('resize', this.handleResize);
            if (this.resizeRaf) {
                window.cancelAnimationFrame(this.resizeRaf);
                this.resizeRaf = 0;
            }
            if (this.rafId) {
                window.cancelAnimationFrame(this.rafId);
                this.rafId = 0;
            }
            this.canvas.__flatcmsWave = null;
        }

        queueResize() {
            if (this.resizeRaf) {
                return;
            }

            this.resizeRaf = window.requestAnimationFrame(() => {
                this.resizeRaf = 0;
                this.resize();
            });
        }

        resize() {
            if (!this.canvas.isConnected) {
                return;
            }

            const rect = this.canvas.getBoundingClientRect();
            const nextWidth = Math.max(1, Math.round(rect.width));
            const nextHeight = Math.max(1, Math.round(rect.height));
            const ratio = Math.max(1, window.devicePixelRatio || 1);

            if (this.canvas.width !== Math.round(nextWidth * ratio) || this.canvas.height !== Math.round(nextHeight * ratio)) {
                this.canvas.width = Math.round(nextWidth * ratio);
                this.canvas.height = Math.round(nextHeight * ratio);
            }

            this.ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            this.width = nextWidth;
            this.height = nextHeight;
            this.palette = resolveWavePalette(this.host || this.canvas.closest(SELECTOR));
        }

        drawWave(index) {
            const waveCount = this.settings.waveCount;
            const totalHeight = (waveCount - 1) * this.settings.waveSpacing;
            const centerOffset = (this.height - totalHeight) / 2;
            const yOffset = centerOffset + (index * this.settings.waveSpacing);
            const maxAmplitude = Math.min(this.settings.amplitude, this.height / (waveCount * 2));
            const adjustedAmplitude = Math.max(10, maxAmplitude);
            const phase = this.phases[index];

            const gradient = this.ctx.createLinearGradient(0, 0, this.width, 0);
            const opacity = 1 - (index / Math.max(1, waveCount));
            gradient.addColorStop(0.1053, `rgba(${this.palette.start[0]}, ${this.palette.start[1]}, ${this.palette.start[2]}, ${clamp(opacity * 0.8, 0, 1)})`);
            gradient.addColorStop(0.5257, `rgba(${this.palette.mid[0]}, ${this.palette.mid[1]}, ${this.palette.mid[2]}, ${clamp(opacity * 0.78, 0, 1)})`);
            gradient.addColorStop(0.9786, `rgba(${this.palette.end[0]}, ${this.palette.end[1]}, ${this.palette.end[2]}, ${clamp(opacity, 0, 1)})`);

            this.ctx.beginPath();
            this.ctx.strokeStyle = gradient;
            this.ctx.lineWidth = this.settings.lineWidth;

            const leftOffsetPx = (this.settings.leftOffset / 100) * this.height;
            const rightOffsetPx = (this.settings.rightOffset / 100) * this.height;

            for (let x = 0; x <= this.width; x += 16) {
                const t = this.width > 0 ? x / this.width : 0;
                const offset = (leftOffsetPx * (1 - t)) + (rightOffsetPx * t);
                const y = yOffset
                    + offset
                    + (Math.sin((x * 0.005) + phase) * adjustedAmplitude)
                    + (Math.cos((x * 0.002) + phase) * adjustedAmplitude * 0.5);
                const clampedY = clamp(y, 0, this.height);

                if (x === 0) {
                    this.ctx.moveTo(x, clampedY);
                } else {
                    this.ctx.lineTo(x, clampedY);
                }
            }

            this.ctx.stroke();

            const speed = this.settings.direction === 'right' ? -this.settings.baseSpeed : this.settings.baseSpeed;
            this.phases[index] += speed;
        }

        animateFrame() {
            if (!this.canvas.isConnected) {
                this.destroy();
                return;
            }

            if (this.width <= 0 || this.height <= 0) {
                this.resize();
            }

            this.ctx.clearRect(0, 0, this.width, this.height);
            for (let index = 0; index < this.settings.waveCount; index += 1) {
                this.drawWave(index);
            }

            this.rafId = window.requestAnimationFrame(this.animateFrame);
        }
    }

    const resolveWidgets = (root) => {
        const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
        const list = [];

        if (scope instanceof HTMLElement && scope.matches(SELECTOR)) {
            list.push(scope);
        }

        scope.querySelectorAll(SELECTOR).forEach((node) => {
            if (node instanceof HTMLElement) {
                list.push(node);
            }
        });

        return list;
    };

    const resolveVisible = (widget) => {
        if (!(widget instanceof HTMLElement)) {
            return 1;
        }

        const raw = window.getComputedStyle(widget).getPropertyValue('--pb-testimonial-visible');
        const value = Number.parseInt(String(raw || '').trim(), 10);
        return Number.isFinite(value) && value > 0 ? value : 1;
    };

    const readTrackStep = (track, slides) => {
        if (!(track instanceof HTMLElement) || !slides.length) {
            return 0;
        }

        if (slides.length > 1) {
            const firstOffset = slides[0].offsetLeft;
            const secondOffset = slides[1].offsetLeft;
            const delta = secondOffset - firstOffset;
            if (delta > 0) {
                return delta;
            }
        }

        return slides[0].offsetWidth;
    };

    const initCloudWaves = (widget) => {
        const canvases = Array.from(widget.querySelectorAll('[data-testimonial-cloud-waves]'));
        canvases.forEach((node) => {
            if (!(node instanceof HTMLCanvasElement)) {
                return;
            }

            const existing = node.__flatcmsWave;
            if (existing && typeof existing.resize === 'function') {
                existing.resize();
                return;
            }

            node.__flatcmsWave = new TestimonialCloudWaves(node, widget);
        });
    };

    const initWidget = (widget) => {
        if (!(widget instanceof HTMLElement)) {
            return;
        }

        initCloudWaves(widget);

        const track = widget.querySelector('[data-testimonial-track]');
        const controls = widget.querySelector('[data-testimonial-controls]');
        const prevButton = widget.querySelector('[data-testimonial-prev]');
        const nextButton = widget.querySelector('[data-testimonial-next]');

        if (!(track instanceof HTMLElement)) {
            return;
        }

        const slides = Array.from(track.querySelectorAll('[data-testimonial-slide]')).filter((node) => node instanceof HTMLElement);
        if (!slides.length) {
            widget.dataset.testimonialReady = '1';
            return;
        }

        const state = widget.__flatcmsTestimonialState || {
            index: 0,
            maxIndex: 0,
            step: 0,
            listenersBound: false,
            resizeRaf: 0,
        };

        widget.__flatcmsTestimonialState = state;

        const syncControls = () => {
            const hasNavigation = state.maxIndex > 0;
            widget.dataset.testimonialReady = '1';

            if (controls instanceof HTMLElement) {
                controls.hidden = !hasNavigation;
            }

            if (prevButton instanceof HTMLButtonElement) {
                prevButton.disabled = !hasNavigation;
            }

            if (nextButton instanceof HTMLButtonElement) {
                nextButton.disabled = !hasNavigation;
            }
        };

        const apply = (animate) => {
            if (state.step <= 0) {
                track.style.transform = 'translate3d(0, 0, 0)';
                syncControls();
                return;
            }

            if (!animate) {
                const previous = track.style.transitionDuration;
                track.style.transitionDuration = '0ms';
                track.style.transform = `translate3d(${-state.index * state.step}px, 0, 0)`;
                track.offsetHeight;
                track.style.transitionDuration = previous;
            } else {
                track.style.transform = `translate3d(${-state.index * state.step}px, 0, 0)`;
            }

            syncControls();
        };

        const equalizeSlideHeights = () => {
            slides.forEach((slide) => {
                if (slide instanceof HTMLElement) {
                    slide.style.minHeight = '';
                }
            });

            let maxHeight = 0;
            slides.forEach((slide) => {
                if (!(slide instanceof HTMLElement)) {
                    return;
                }
                maxHeight = Math.max(maxHeight, Math.ceil(slide.getBoundingClientRect().height));
            });

            if (maxHeight <= 0) {
                return;
            }

            const target = `${maxHeight}px`;
            slides.forEach((slide) => {
                if (slide instanceof HTMLElement) {
                    slide.style.minHeight = target;
                }
            });
        };

        const recompute = (animate) => {
            equalizeSlideHeights();
            const visible = resolveVisible(widget);
            state.maxIndex = Math.max(0, slides.length - visible);
            state.index = Math.max(0, Math.min(state.index, state.maxIndex));
            state.step = readTrackStep(track, slides);
            apply(animate);
        };

        const jumpTo = (nextIndex, animate) => {
            if (state.maxIndex <= 0) {
                state.index = 0;
                apply(animate);
                return;
            }

            if (nextIndex < 0) {
                state.index = state.maxIndex;
            } else if (nextIndex > state.maxIndex) {
                state.index = 0;
            } else {
                state.index = nextIndex;
            }

            apply(animate);
        };

        const scheduleRecompute = () => {
            if (state.resizeRaf) {
                return;
            }

            state.resizeRaf = window.requestAnimationFrame(() => {
                state.resizeRaf = 0;
                recompute(false);
            });
        };

        const bindLayoutObservers = () => {
            if (state.resizeObserver && typeof state.resizeObserver.disconnect === 'function') {
                state.resizeObserver.disconnect();
            }

            state.resizeObserver = null;

            if (typeof ResizeObserver === 'function') {
                const observer = new ResizeObserver(() => {
                    scheduleRecompute();
                });

                observer.observe(track);
                slides.forEach((slide) => {
                    if (slide instanceof HTMLElement) {
                        observer.observe(slide);
                    }
                });

                state.resizeObserver = observer;
            }

            if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
                document.fonts.ready
                    .then(() => {
                        scheduleRecompute();
                    })
                    .catch(() => {
                        // Ignore font readiness failures and keep current layout.
                    });
            }

            window.requestAnimationFrame(() => {
                scheduleRecompute();
            });

            window.setTimeout(() => {
                scheduleRecompute();
            }, 120);

            window.setTimeout(() => {
                scheduleRecompute();
            }, 320);
        };

        slides.forEach((slide) => {
            if (!(slide instanceof HTMLElement)) {
                return;
            }

            slide.querySelectorAll('img').forEach((image) => {
                if (!(image instanceof HTMLImageElement) || image.dataset.testimonialResizeBound === '1') {
                    return;
                }

                image.dataset.testimonialResizeBound = '1';
                image.addEventListener('load', scheduleRecompute, { passive: true });
                image.addEventListener('error', scheduleRecompute, { passive: true });
            });
        });

        if (!state.listenersBound) {
            if (prevButton instanceof HTMLButtonElement) {
                prevButton.addEventListener('click', () => {
                    jumpTo(state.index - 1, true);
                });
            }

            if (nextButton instanceof HTMLButtonElement) {
                nextButton.addEventListener('click', () => {
                    jumpTo(state.index + 1, true);
                });
            }

            window.addEventListener('resize', scheduleRecompute);
            state.listenersBound = true;
        }

        bindLayoutObservers();
        recompute(false);
    };

    const init = (root) => {
        resolveWidgets(root).forEach((widget) => {
            initWidget(widget);
        });
    };

    window.FlatCMSTestimonialCards = window.FlatCMSTestimonialCards || {};
    window.FlatCMSTestimonialCards.init = init;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            init(document);
        }, { once: true });
    } else {
        init(document);
    }
})();
