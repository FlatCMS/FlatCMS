/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function () {
    'use strict';

    function readConfigFromJsonTag() {
        const node = document.getElementById('flatcms-guided-tour-config');
        if (!node) {
            return null;
        }

        const raw = String(
            (node.dataset && node.dataset.guidedTourConfig)
                ? node.dataset.guidedTourConfig
                : (node.textContent || '')
        ).trim();
        if (raw === '') {
            return null;
        }

        try {
            return JSON.parse(raw);
        } catch (error) {
            return null;
        }
    }

    const rootConfig = readConfigFromJsonTag() || window.FlatCMSGuidedTourConfig || {};
    const rootNamespace = window.FlatCMS = window.FlatCMS || {};

    if (!rootConfig || typeof rootConfig !== 'object') {
        return;
    }

    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
    const isTruthy = (value) => value === true || value === 1 || value === '1';
    const normalizeRoutePath = (value) => String(value || '')
        .trim()
        .replace(/^[a-z]+:\/\/[^/]+/i, '')
        .replace(/[?#].*$/, '')
        .replace(/^\/+/, '')
        .replace(/\/+$/, '')
        .toLowerCase();
    const normalizeModuleKey = (value) => String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9_-]/g, '');

    function getToastApi() {
        return rootNamespace.toast && typeof rootNamespace.toast.show === 'function'
            ? rootNamespace.toast
            : null;
    }

    function showToast(message, type) {
        const text = String(message || '').trim();
        if (!text) {
            return;
        }

        const toast = getToastApi();
        if (toast) {
            toast.show(text, type || 'success', normalizeToastType(type || 'success') === 'error' ? 20000 : 10000);
        }
    }

    function normalizeToastType(type) {
        const key = String(type || '').toLowerCase();
        if (key === 'error' || key === 'danger') {
            return 'error';
        }
        if (key === 'warning' || key === 'warn' || key === 'info') {
            return 'warning';
        }
        return 'success';
    }

    function buildFormPayload(values) {
        const payload = new URLSearchParams();

        Object.keys(values || {}).forEach((key) => {
            const value = values[key];
            if (value === undefined || value === null) {
                return;
            }

            payload.set(key, String(value));
        });

        return payload.toString();
    }

    class GuidedTour {
        constructor(config) {
            this.config = config;
            this.labels = config.labels || {};
            this.globalSteps = Array.isArray(config.steps) ? config.steps : [];
            this.moduleTours = this.normalizeModuleTours(config.moduleTours || {});
            this.currentPath = normalizeRoutePath(config.currentPath || '');
            this.globalSeen = isTruthy(config.globalSeen);
            this.seenModules = this.normalizeSeenModules(config.seenModules);
            this.currentTourModule = 'global';
            this.currentTourIncludesGlobalIntro = false;
            this.steps = [];
            this.currentStepIndex = 0;
            this.active = false;
            this.markingSeen = false;

            this.backdropEl = null;
            this.focusEl = null;
            this.popoverEl = null;
            this.titleEl = null;
            this.bodyEl = null;
            this.counterEl = null;
            this.prevButton = null;
            this.nextButton = null;
            this.skipButton = null;
            this.closeButton = null;
            this.promptBackdropEl = null;
            this.promptEl = null;
            this.promptTitleEl = null;
            this.promptBodyEl = null;
            this.promptStartButton = null;
            this.promptQuitButton = null;
            this.promptCloseButton = null;
            this.promptVisible = false;
            this.pendingTourPayload = null;

            this.currentTarget = null;
            this.previousInlineStyle = {
                position: '',
                zIndex: '',
            };

            this.boundOnWindowChange = this.onWindowChange.bind(this);
            this.boundOnKeydown = this.onKeydown.bind(this);
            this.boundOnPromptKeydown = this.onPromptKeydown.bind(this);
        }

        normalizeSeenModules(value) {
            const source = Array.isArray(value) ? value : [];
            const modules = new Set();

            source.forEach((moduleKey) => {
                const normalized = normalizeModuleKey(moduleKey);
                if (normalized !== '' && normalized !== 'global') {
                    modules.add(normalized);
                }
            });

            return modules;
        }

        normalizeModuleTours(source) {
            if (!source || typeof source !== 'object') {
                return [];
            }

            const tours = [];
            Object.keys(source).forEach((rawKey) => {
                const key = normalizeModuleKey(rawKey);
                if (key === '') {
                    return;
                }

                const entry = source[rawKey];
                if (!entry || typeof entry !== 'object') {
                    return;
                }

                const routes = Array.isArray(entry.routes)
                    ? entry.routes.map(normalizeRoutePath).filter((route) => route !== '')
                    : [];
                const steps = Array.isArray(entry.steps) ? entry.steps : [];

                tours.push({
                    key,
                    routes,
                    steps,
                });
            });

            tours.sort((a, b) => {
                const aMax = a.routes.reduce((max, route) => Math.max(max, route.length), 0);
                const bMax = b.routes.reduce((max, route) => Math.max(max, route.length), 0);
                return bMax - aMax;
            });

            return tours;
        }

        resolveCurrentPath() {
            if (this.currentPath !== '') {
                return this.currentPath;
            }

            try {
                const params = new URLSearchParams(window.location.search || '');
                const queryPath = params.get('path');
                if (queryPath) {
                    this.currentPath = normalizeRoutePath(queryPath);
                    if (this.currentPath !== '') {
                        return this.currentPath;
                    }
                }
            } catch (error) {
                // no-op
            }

            this.currentPath = normalizeRoutePath(window.location.pathname || '');
            return this.currentPath;
        }

        routeMatches(route, currentPath) {
            const normalizedRoute = normalizeRoutePath(route);
            if (normalizedRoute === '' || currentPath === '') {
                return false;
            }

            if (currentPath === normalizedRoute) {
                return true;
            }

            if (normalizedRoute.indexOf('/') === -1) {
                return false;
            }

            return currentPath.indexOf(`${normalizedRoute}/`) === 0;
        }

        findCurrentModuleTour() {
            const currentPath = this.resolveCurrentPath();
            if (currentPath === '' || !this.moduleTours.length) {
                return null;
            }

            let bestMatch = null;
            let bestLength = -1;

            this.moduleTours.forEach((tour) => {
                tour.routes.forEach((route) => {
                    if (this.routeMatches(route, currentPath) && route.length > bestLength) {
                        bestMatch = tour;
                        bestLength = route.length;
                    }
                });
            });

            return bestMatch;
        }

        shouldAutoStart(moduleKey, forceStart) {
            if (forceStart) {
                return true;
            }

            const allowForcedAutoStart = isTruthy(this.config.forceAutoStart);
            if (!isTruthy(this.config.autoStart) || (!isTruthy(this.config.enabled) && !allowForcedAutoStart)) {
                return false;
            }

            if (moduleKey === 'dashboard' && !this.globalSeen) {
                return true;
            }

            if (moduleKey !== 'global') {
                return !this.seenModules.has(moduleKey);
            }

            return !this.globalSeen;
        }

        prepareTour(forceStart) {
            const moduleTour = this.findCurrentModuleTour();
            const moduleKey = moduleTour ? moduleTour.key : 'global';
            const includeGlobalIntro = moduleKey === 'global' || (!this.globalSeen && moduleKey === 'dashboard');
            const sourceSteps = moduleTour
                ? (includeGlobalIntro ? this.globalSteps.concat(moduleTour.steps) : moduleTour.steps.slice())
                : this.globalSteps.slice();

            return {
                moduleKey,
                includeGlobalIntro,
                sourceSteps,
                shouldStart: this.shouldAutoStart(moduleKey, forceStart),
            };
        }

        isActive() {
            return this.active;
        }

        resolveConditionSelectors(value) {
            if (Array.isArray(value)) {
                return value.map((item) => String(item || '').trim()).filter((item) => item !== '');
            }

            const single = String(value || '').trim();
            return single !== '' ? [single] : [];
        }

        matchesStepConditions(step) {
            if (!step || typeof step !== 'object') {
                return true;
            }

            const whenVisible = this.resolveConditionSelectors(step.whenVisible);
            for (let index = 0; index < whenVisible.length; index += 1) {
                const element = document.querySelector(whenVisible[index]);
                if (!element || !this.isElementVisible(element)) {
                    return false;
                }
            }

            const whenHidden = this.resolveConditionSelectors(step.whenHidden);
            for (let index = 0; index < whenHidden.length; index += 1) {
                const element = document.querySelector(whenHidden[index]);
                if (element && this.isElementVisible(element)) {
                    return false;
                }
            }

            return true;
        }

        collectSteps(source) {
            const sourceSteps = Array.isArray(source) ? source : [];
            const items = [];
            const signatures = new Set();

            sourceSteps.forEach((step) => {
                if (!step || typeof step !== 'object') {
                    return;
                }

                if (!this.matchesStepConditions(step)) {
                    return;
                }

                const selector = String(step.selector || '').trim();
                if (selector === '') {
                    return;
                }

                const element = document.querySelector(selector);
                if (!element || !this.isElementVisible(element)) {
                    return;
                }

                const title = String(step.title || '');
                const content = String(step.content || '');
                const signature = `${selector}::${title}::${content}`;
                if (signatures.has(signature)) {
                    return;
                }
                signatures.add(signature);

                items.push({
                    selector,
                    element,
                    title,
                    content,
                    placement: String(step.placement || 'bottom').toLowerCase(),
                });
            });

            return items;
        }

        buildSteps(source) {
            this.steps = this.collectSteps(source);
        }

        isElementVisible(element) {
            if (!(element instanceof Element)) {
                return false;
            }

            const style = window.getComputedStyle(element);
            if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
                return false;
            }

            const rect = element.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        }

        ensureUi() {
            if (this.backdropEl && this.focusEl && this.popoverEl) {
                return;
            }

            const backdrop = document.createElement('div');
            backdrop.className = 'flatcms-guided-tour-backdrop';
            backdrop.setAttribute('hidden', 'hidden');

            const focus = document.createElement('div');
            focus.className = 'flatcms-guided-tour-focus';
            focus.setAttribute('hidden', 'hidden');

            const popover = document.createElement('div');
            popover.className = 'flatcms-guided-tour-popover';
            popover.setAttribute('hidden', 'hidden');
            popover.setAttribute('role', 'dialog');
            popover.setAttribute('aria-modal', 'true');

            popover.innerHTML = ''
                + '<div class="flatcms-guided-tour-head">'
                + '  <h3 class="flatcms-guided-tour-title"></h3>'
                + '  <button type="button" class="flatcms-guided-tour-close" data-action="close" aria-label="Close">'
                + '    <i class="fas fa-times"></i>'
                + '  </button>'
                + '</div>'
                + '<div class="flatcms-guided-tour-body"></div>'
                + '<div class="flatcms-guided-tour-foot">'
                + '  <span class="flatcms-guided-tour-counter"></span>'
                + '  <div class="flatcms-guided-tour-actions">'
                + '    <button type="button" class="flatcms-guided-tour-btn is-ghost" data-action="skip"></button>'
                + '    <button type="button" class="flatcms-guided-tour-btn" data-action="prev"></button>'
                + '    <button type="button" class="flatcms-guided-tour-btn is-primary" data-action="next"></button>'
                + '  </div>'
                + '</div>';

            document.body.appendChild(backdrop);
            document.body.appendChild(focus);
            document.body.appendChild(popover);

            this.backdropEl = backdrop;
            this.focusEl = focus;
            this.popoverEl = popover;
            this.titleEl = popover.querySelector('.flatcms-guided-tour-title');
            this.bodyEl = popover.querySelector('.flatcms-guided-tour-body');
            this.counterEl = popover.querySelector('.flatcms-guided-tour-counter');
            this.prevButton = popover.querySelector('[data-action="prev"]');
            this.nextButton = popover.querySelector('[data-action="next"]');
            this.skipButton = popover.querySelector('[data-action="skip"]');
            this.closeButton = popover.querySelector('[data-action="close"]');

            if (this.skipButton) {
                this.skipButton.textContent = this.labels.skip || 'Skip';
            }
            if (this.prevButton) {
                this.prevButton.textContent = this.labels.previous || 'Previous';
            }
            if (this.nextButton) {
                this.nextButton.textContent = this.labels.next || 'Next';
            }
            if (this.closeButton) {
                this.closeButton.setAttribute('aria-label', this.labels.close || 'Close');
            }

            if (this.backdropEl) {
                this.backdropEl.addEventListener('click', () => this.close('skip'));
            }
            if (this.closeButton) {
                this.closeButton.addEventListener('click', () => this.close('skip'));
            }
            if (this.skipButton) {
                this.skipButton.addEventListener('click', () => this.close('skip'));
            }
            if (this.prevButton) {
                this.prevButton.addEventListener('click', () => this.prev());
            }
            if (this.nextButton) {
                this.nextButton.addEventListener('click', () => this.next());
            }
        }

        ensurePromptUi() {
            if (this.promptBackdropEl && this.promptEl) {
                return;
            }

            const promptBackdrop = document.createElement('div');
            promptBackdrop.className = 'flatcms-guided-tour-prompt-backdrop';
            promptBackdrop.setAttribute('hidden', 'hidden');

            const prompt = document.createElement('div');
            prompt.className = 'flatcms-guided-tour-prompt';
            prompt.setAttribute('hidden', 'hidden');
            prompt.setAttribute('role', 'dialog');
            prompt.setAttribute('aria-modal', 'true');
            prompt.innerHTML = ''
                + '<div class="flatcms-guided-tour-prompt-head">'
                + '  <h3 class="flatcms-guided-tour-prompt-title"></h3>'
                + '  <button type="button" class="flatcms-guided-tour-close" data-action="prompt-close"></button>'
                + '</div>'
                + '<div class="flatcms-guided-tour-prompt-body"></div>'
                + '<div class="flatcms-guided-tour-prompt-foot">'
                + '  <div class="flatcms-guided-tour-actions">'
                + '    <button type="button" class="flatcms-guided-tour-btn is-ghost" data-action="prompt-quit"></button>'
                + '    <button type="button" class="flatcms-guided-tour-btn is-primary" data-action="prompt-start"></button>'
                + '  </div>'
                + '</div>';

            document.body.appendChild(promptBackdrop);
            document.body.appendChild(prompt);

            this.promptBackdropEl = promptBackdrop;
            this.promptEl = prompt;
            this.promptTitleEl = prompt.querySelector('.flatcms-guided-tour-prompt-title');
            this.promptBodyEl = prompt.querySelector('.flatcms-guided-tour-prompt-body');
            this.promptStartButton = prompt.querySelector('[data-action="prompt-start"]');
            this.promptQuitButton = prompt.querySelector('[data-action="prompt-quit"]');
            this.promptCloseButton = prompt.querySelector('[data-action="prompt-close"]');

            if (this.promptCloseButton) {
                this.promptCloseButton.innerHTML = '<i class="fas fa-times"></i>';
                this.promptCloseButton.setAttribute('aria-label', this.labels.close || 'Close');
            }
            if (this.promptStartButton) {
                this.promptStartButton.textContent = this.labels.promptStart || 'Start tutorial';
            }
            if (this.promptQuitButton) {
                this.promptQuitButton.textContent = this.labels.promptQuit || 'Quit';
            }

            if (this.promptBackdropEl) {
                this.promptBackdropEl.addEventListener('click', () => this.dismissPrompt(false));
            }
            if (this.promptCloseButton) {
                this.promptCloseButton.addEventListener('click', () => this.dismissPrompt(false));
            }
            if (this.promptQuitButton) {
                this.promptQuitButton.addEventListener('click', () => this.dismissPrompt(false));
            }
            if (this.promptStartButton) {
                this.promptStartButton.addEventListener('click', () => this.dismissPrompt(true));
            }
        }

        getPromptTitle() {
            const raw = String(this.labels.promptTitle || '').trim();
            if (raw !== '') {
                return raw;
            }

            return 'Contextual help available';
        }

        getPromptMessage() {
            const raw = String(this.labels.promptMessage || '').trim();
            if (raw !== '') {
                return raw;
            }

            return 'A guided tour is available for this section. Do you want to start it now?';
        }

        showPrompt(tourPayload) {
            if (!tourPayload || typeof tourPayload !== 'object') {
                return;
            }

            this.ensurePromptUi();

            this.pendingTourPayload = tourPayload;
            this.promptVisible = true;

            if (this.promptTitleEl) {
                this.promptTitleEl.textContent = this.getPromptTitle();
            }
            if (this.promptBodyEl) {
                this.promptBodyEl.textContent = this.getPromptMessage();
            }

            if (this.promptBackdropEl && this.promptEl) {
                this.promptBackdropEl.removeAttribute('hidden');
                this.promptEl.removeAttribute('hidden');
                document.body.classList.add('flatcms-guided-tour-prompt-open');
                requestAnimationFrame(() => {
                    if (this.promptBackdropEl) {
                        this.promptBackdropEl.classList.add('is-visible');
                    }
                    if (this.promptEl) {
                        this.promptEl.classList.add('is-visible');
                    }
                });
            }

            document.addEventListener('keydown', this.boundOnPromptKeydown);
        }

        hidePrompt() {
            if (!this.promptVisible) {
                return;
            }

            this.promptVisible = false;
            document.removeEventListener('keydown', this.boundOnPromptKeydown);
            document.body.classList.remove('flatcms-guided-tour-prompt-open');

            if (this.promptBackdropEl) {
                this.promptBackdropEl.classList.remove('is-visible');
            }
            if (this.promptEl) {
                this.promptEl.classList.remove('is-visible');
            }

            window.setTimeout(() => {
                if (this.promptVisible) {
                    return;
                }

                if (this.promptBackdropEl) {
                    this.promptBackdropEl.setAttribute('hidden', 'hidden');
                }
                if (this.promptEl) {
                    this.promptEl.setAttribute('hidden', 'hidden');
                }
            }, 180);
        }

        dismissPrompt(shouldStart) {
            if (!this.promptVisible) {
                return;
            }

            const payload = this.pendingTourPayload;
            this.pendingTourPayload = null;
            this.hidePrompt();

            if (shouldStart === true) {
                this.start(true, payload);
                return;
            }

            showToast(this.labels.promptDisableHint || '', 'info');
        }

        requestAutoStart() {
            const tourPayload = this.prepareTour(false);
            if (!tourPayload.shouldStart) {
                return;
            }

            const preparedSteps = this.collectSteps(tourPayload.sourceSteps);
            if (!preparedSteps.length) {
                return;
            }

            tourPayload.preparedSteps = preparedSteps;
            this.showPrompt(tourPayload);
        }

        start(force, payloadOverride) {
            if (this.active) {
                return;
            }

            if (!force && !isTruthy(this.config.enabled)) {
                return;
            }

            if (this.promptVisible) {
                this.hidePrompt();
            }

            const tourPayload = payloadOverride && typeof payloadOverride === 'object'
                ? payloadOverride
                : this.prepareTour(force === true);
            if (!tourPayload.shouldStart) {
                return;
            }

            this.currentTourModule = tourPayload.moduleKey;
            this.currentTourIncludesGlobalIntro = tourPayload.includeGlobalIntro === true;
            const preparedSteps = Array.isArray(tourPayload.preparedSteps)
                ? tourPayload.preparedSteps
                : this.collectSteps(tourPayload.sourceSteps);
            if (!preparedSteps.length) {
                return;
            }
            this.steps = preparedSteps;

            this.ensureUi();

            this.active = true;
            this.currentStepIndex = 0;
            document.body.classList.add('flatcms-guided-tour-open');

            if (this.backdropEl && this.focusEl && this.popoverEl) {
                this.backdropEl.removeAttribute('hidden');
                this.focusEl.removeAttribute('hidden');
                this.popoverEl.removeAttribute('hidden');

                requestAnimationFrame(() => {
                    this.backdropEl.classList.add('is-visible');
                    this.popoverEl.classList.add('is-visible');
                });
            }

            window.addEventListener('resize', this.boundOnWindowChange);
            window.addEventListener('scroll', this.boundOnWindowChange, true);
            document.addEventListener('keydown', this.boundOnKeydown);

            this.showStep(this.currentStepIndex);
        }

        close(reason) {
            if (!this.active) {
                return;
            }

            this.active = false;
            this.clearCurrentTarget();
            document.body.classList.remove('flatcms-guided-tour-open');

            window.removeEventListener('resize', this.boundOnWindowChange);
            window.removeEventListener('scroll', this.boundOnWindowChange, true);
            document.removeEventListener('keydown', this.boundOnKeydown);

            if (this.backdropEl && this.focusEl && this.popoverEl) {
                this.backdropEl.classList.remove('is-visible');
                this.popoverEl.classList.remove('is-visible');

                window.setTimeout(() => {
                    if (!this.active) {
                        this.backdropEl.setAttribute('hidden', 'hidden');
                        this.focusEl.setAttribute('hidden', 'hidden');
                        this.popoverEl.setAttribute('hidden', 'hidden');
                    }
                }, 180);
            }

            this.markSeen();

            if (reason === 'finish') {
                showToast(this.labels.completedToast || '', 'success');
            }
        }

        next() {
            if (!this.active) {
                return;
            }

            if (this.currentStepIndex >= this.steps.length - 1) {
                this.close('finish');
                return;
            }

            this.showStep(this.currentStepIndex + 1);
        }

        prev() {
            if (!this.active) {
                return;
            }

            if (this.currentStepIndex <= 0) {
                return;
            }

            this.showStep(this.currentStepIndex - 1);
        }

        showStep(index) {
            if (!this.active || !this.steps.length) {
                return;
            }

            const safeIndex = clamp(index, 0, this.steps.length - 1);
            this.currentStepIndex = safeIndex;
            const step = this.steps[safeIndex];

            this.clearCurrentTarget();
            this.currentTarget = step.element;
            this.applyTargetStyle(this.currentTarget);

            if (this.titleEl) {
                this.titleEl.textContent = step.title || '';
            }
            if (this.bodyEl) {
                this.bodyEl.textContent = step.content || '';
            }

            const isFirst = safeIndex === 0;
            const isLast = safeIndex === this.steps.length - 1;

            if (this.prevButton) {
                this.prevButton.disabled = isFirst;
            }
            if (this.nextButton) {
                this.nextButton.textContent = isLast
                    ? (this.labels.finish || 'Finish')
                    : (this.labels.next || 'Next');
            }
            if (this.counterEl) {
                const template = this.labels.stepCounter || 'Step :current/:total';
                this.counterEl.textContent = template
                    .replace(':current', String(safeIndex + 1))
                    .replace(':total', String(this.steps.length));
            }

            this.ensureTargetVisibility(step.element, () => {
                this.positionFocus(step.element);
                this.positionPopover(step);
            });
        }

        ensureTargetVisibility(element, callback) {
            if (!(element instanceof Element)) {
                if (typeof callback === 'function') {
                    callback();
                }
                return;
            }

            const rect = element.getBoundingClientRect();
            const marginTop = 90;
            const marginBottom = 120;
            const outOfView = rect.top < marginTop || rect.bottom > (window.innerHeight - marginBottom);

            if (!outOfView) {
                if (typeof callback === 'function') {
                    callback();
                }
                return;
            }

            element.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'nearest',
            });

            window.setTimeout(() => {
                if (typeof callback === 'function') {
                    callback();
                }
            }, 260);
        }

        applyTargetStyle(element) {
            if (!(element instanceof Element)) {
                return;
            }

            const computed = window.getComputedStyle(element);
            this.previousInlineStyle = {
                position: element.style.position || '',
                zIndex: element.style.zIndex || '',
            };

            if (computed.position === 'static') {
                element.style.position = 'relative';
            }
            element.style.zIndex = '12012';
            element.classList.add('flatcms-guided-tour-target');
        }

        clearCurrentTarget() {
            if (!(this.currentTarget instanceof Element)) {
                this.currentTarget = null;
                return;
            }

            this.currentTarget.classList.remove('flatcms-guided-tour-target');
            this.currentTarget.style.position = this.previousInlineStyle.position;
            this.currentTarget.style.zIndex = this.previousInlineStyle.zIndex;
            this.currentTarget = null;
        }

        positionFocus(element) {
            if (!this.focusEl || !(element instanceof Element)) {
                return;
            }

            const rect = element.getBoundingClientRect();
            const padding = 6;

            const top = clamp(rect.top - padding, 8, window.innerHeight - 8);
            const left = clamp(rect.left - padding, 8, window.innerWidth - 8);
            const width = clamp(rect.width + (padding * 2), 16, window.innerWidth - left - 8);
            const height = clamp(rect.height + (padding * 2), 16, window.innerHeight - top - 8);

            this.focusEl.style.top = `${top}px`;
            this.focusEl.style.left = `${left}px`;
            this.focusEl.style.width = `${width}px`;
            this.focusEl.style.height = `${height}px`;
        }

        positionPopover(step) {
            if (!this.popoverEl || !step || !(step.element instanceof Element)) {
                return;
            }

            const gap = 14;
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const rect = step.element.getBoundingClientRect();
            const popRect = this.popoverEl.getBoundingClientRect();

            const candidates = [];
            const preferred = ['top', 'right', 'bottom', 'left'].includes(step.placement) ? step.placement : 'bottom';
            candidates.push(preferred);
            ['bottom', 'right', 'left', 'top'].forEach((place) => {
                if (!candidates.includes(place)) {
                    candidates.push(place);
                }
            });

            const computePosition = (placement) => {
                let top = rect.bottom + gap;
                let left = rect.left;

                if (placement === 'top') {
                    top = rect.top - popRect.height - gap;
                    left = rect.left + (rect.width / 2) - (popRect.width / 2);
                } else if (placement === 'right') {
                    top = rect.top + (rect.height / 2) - (popRect.height / 2);
                    left = rect.right + gap;
                } else if (placement === 'left') {
                    top = rect.top + (rect.height / 2) - (popRect.height / 2);
                    left = rect.left - popRect.width - gap;
                } else {
                    top = rect.bottom + gap;
                    left = rect.left + (rect.width / 2) - (popRect.width / 2);
                }

                return { top, left };
            };

            const isVisible = (position) => (
                position.left >= 8
                && (position.left + popRect.width) <= (viewportWidth - 8)
                && position.top >= 8
                && (position.top + popRect.height) <= (viewportHeight - 8)
            );

            let chosen = computePosition(candidates[0]);
            for (let i = 0; i < candidates.length; i += 1) {
                const candidate = computePosition(candidates[i]);
                if (isVisible(candidate)) {
                    chosen = candidate;
                    break;
                }
            }

            chosen.left = clamp(chosen.left, 8, viewportWidth - popRect.width - 8);
            chosen.top = clamp(chosen.top, 8, viewportHeight - popRect.height - 8);

            this.popoverEl.style.left = `${chosen.left}px`;
            this.popoverEl.style.top = `${chosen.top}px`;
        }

        onWindowChange() {
            if (!this.active || !this.steps.length) {
                return;
            }

            const step = this.steps[this.currentStepIndex];
            if (!step || !(step.element instanceof Element)) {
                return;
            }

            this.positionFocus(step.element);
            this.positionPopover(step);
        }

        onKeydown(event) {
            if (!this.active) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                this.close('skip');
                return;
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                this.next();
                return;
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                this.prev();
            }
        }

        onPromptKeydown(event) {
            if (!this.promptVisible) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                this.dismissPrompt(false);
            }
        }

        async markSeen() {
            if (this.markingSeen) {
                return;
            }

            const endpoint = String(this.config.markSeenUrl || '').trim();
            const token = String(this.config.csrfToken || '').trim();
            if (endpoint === '' || token === '') {
                return;
            }

            const moduleKey = normalizeModuleKey(this.currentTourModule || 'global') || 'global';
            const markGlobal = moduleKey === 'global' || this.currentTourIncludesGlobalIntro === true;

            if (moduleKey === 'global' && this.globalSeen) {
                return;
            }
            if (moduleKey !== 'global' && this.seenModules.has(moduleKey) && (!markGlobal || this.globalSeen)) {
                return;
            }

            this.markingSeen = true;
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': token,
                    },
                    credentials: 'same-origin',
                    body: buildFormPayload({
                        _token: token,
                        version: this.config.version || 'v1',
                        module: moduleKey,
                        mark_global: markGlobal ? '1' : '0',
                    }),
                });

                if (response.ok) {
                    if (markGlobal) {
                        this.globalSeen = true;
                        this.config.globalSeen = true;
                    }
                    if (moduleKey !== 'global') {
                        this.seenModules.add(moduleKey);
                    }
                    try {
                        const payload = await response.json();
                        if (payload && Array.isArray(payload.modules)) {
                            this.seenModules = this.normalizeSeenModules(payload.modules);
                        }
                    } catch (error) {
                        // no-op
                    }
                    this.config.autoStart = false;
                }
            } catch (error) {
                // no-op
            } finally {
                this.markingSeen = false;
            }
        }

        async resetSeen() {
            const endpoint = String(this.config.resetUrl || '').trim();
            const token = String(this.config.csrfToken || '').trim();
            if (endpoint === '' || token === '') {
                return false;
            }

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': token,
                    },
                    credentials: 'same-origin',
                    body: buildFormPayload({
                        _token: token,
                        reset: '1',
                    }),
                });

                if (!response.ok) {
                    showToast(this.labels.errorToast || '', 'error');
                    return false;
                }

                this.globalSeen = false;
                this.seenModules.clear();
                this.currentTourModule = 'global';
                this.config.autoStart = true;
                this.config.globalSeen = false;
                showToast(this.labels.resetToast || '', 'success');
                return true;
            } catch (error) {
                showToast(this.labels.errorToast || '', 'error');
                return false;
            }
        }
    }

    const tour = new GuidedTour(rootConfig);

    rootNamespace.guidedTour = {
        start(force) {
            tour.start(force === true);
        },
        close() {
            tour.close('skip');
        },
        isActive() {
            return tour.isActive();
        },
        resetSeen() {
            return tour.resetSeen();
        },
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (isTruthy(rootConfig.autoStart)) {
            window.setTimeout(function () {
                tour.requestAutoStart();
            }, 320);
        }
    });
})();
