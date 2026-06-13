/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function () {
    'use strict';

    const scriptEl = document.querySelector('script[data-flatcms-integrations-runtime]');
    if (!scriptEl) {
        return;
    }

    const toBool = (value) => {
        const normalized = String(value || '').trim().toLowerCase();
        return ['1', 'true', 'on', 'yes'].includes(normalized);
    };

    const normalizeToken = (value) => String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9._-]+/g, '');

    const normalizeRequirements = (value) => {
        if (Array.isArray(value)) {
            return value
                .map((item) => normalizeToken(item))
                .filter((item) => item !== '');
        }

        return String(value || '')
            .split(',')
            .map((item) => normalizeToken(item))
            .filter((item) => item !== '');
    };

    const normalizeChoiceValue = (value) => {
        if (typeof value === 'boolean') {
            return value;
        }

        if (typeof value === 'number') {
            return value === 1;
        }

        if (typeof value === 'string') {
            const normalized = value.trim().toLowerCase();
            if (['1', 'true', 'yes', 'on', 'allowed', 'accepted', 'granted'].includes(normalized)) {
                return true;
            }
            if (['0', 'false', 'no', 'off', 'denied', 'refused', 'rejected'].includes(normalized)) {
                return false;
            }
        }

        return null;
    };

    const cfg = {
        cookieBannerEnabled: toBool(scriptEl.dataset.cookieBannerEnabled),
        cookieRequireConsent: toBool(scriptEl.dataset.cookieRequireConsent),
        axeptioClientId: String(scriptEl.dataset.axeptioClientId || '').trim(),
        axeptioCookiesVersion: String(scriptEl.dataset.axeptioCookiesVersion || '').trim(),
        matomoEnabled: toBool(scriptEl.dataset.matomoEnabled),
        matomoBaseUrl: String(scriptEl.dataset.matomoBaseUrl || '').trim(),
        matomoSiteId: String(scriptEl.dataset.matomoSiteId || '').trim(),
        googleAnalyticsEnabled: toBool(scriptEl.dataset.googleAnalyticsEnabled),
        googleAnalyticsMeasurementId: String(scriptEl.dataset.googleAnalyticsMeasurementId || '').trim(),
    };

    const axeptioEnabled = cfg.cookieBannerEnabled && cfg.axeptioClientId !== '' && cfg.axeptioCookiesVersion !== '';
    const consentRequired = cfg.cookieRequireConsent && axeptioEnabled;
    const currentOrigin = String(window.location && window.location.origin ? window.location.origin : '').toLowerCase();

    const state = {
        ready: !consentRequired,
        required: consentRequired,
        choices: {},
        map: Object.create(null),
        loadedScripts: Object.create(null),
        executedInline: Object.create(null),
        queuedDynamicScripts: [],
        domPatchInstalled: false,
        readyCallbacks: [],
        updateCallbacks: [],
    };

    const analyticsState = {
        matomoInitialized: false,
        googleAnalyticsInitialized: false,
    };

    const SCRIPT_MIME_JS = [
        '',
        'text/javascript',
        'application/javascript',
        'application/ecmascript',
        'text/ecmascript',
        'module',
    ];

    const CONSENT_DOMAIN_RULES = [
        {
            pattern: /(google-analytics\.com|googletagmanager\.com|gtag\/js|gtm\.js)/i,
            requirements: ['analytics', 'statistics', 'measurement', 'ga4', 'google_analytics', 'google_tag_manager', 'gtm'],
        },
        {
            pattern: /(clarity\.ms|bing\.com\/clarity)/i,
            requirements: ['analytics', 'statistics', 'clarity', 'microsoft_clarity'],
        },
        {
            pattern: /(hotjar\.com|hotjar\.io|script\.hotjar\.com)/i,
            requirements: ['analytics', 'statistics', 'hotjar'],
        },
        {
            pattern: /(connect\.facebook\.net|facebook\.com\/tr)/i,
            requirements: ['marketing', 'advertising', 'social', 'facebook_pixel', 'meta_pixel'],
        },
        {
            pattern: /(matomo|piwik)/i,
            requirements: ['analytics', 'statistics', 'matomo'],
        },
        {
            pattern: /(linkedin\.com\/insight|licdn\.com)/i,
            requirements: ['marketing', 'advertising', 'linkedin'],
        },
        {
            pattern: /(tiktok\.com|analytics\.tiktok)/i,
            requirements: ['marketing', 'advertising', 'tiktok'],
        },
    ];

    const dispatchConsentEvent = (name, detail) => {
        if (typeof window.CustomEvent !== 'function') {
            return;
        }
        window.dispatchEvent(new window.CustomEvent(name, { detail }));
    };

    const runCallbacks = (callbacks, detail) => {
        for (let index = 0; index < callbacks.length; index += 1) {
            const callback = callbacks[index];
            if (typeof callback !== 'function') {
                continue;
            }

            try {
                callback(detail);
            } catch (error) {
                // Ignore callback errors to keep the consent runtime resilient.
            }
        }
    };

    const currentConsentDetail = () => ({
        choices: state.choices,
        map: Object.assign({}, state.map),
        required: state.required,
        ready: state.ready,
    });

    const normalizeBaseUrl = (value) => String(value || '').trim().replace(/\/+$/, '');

    const appendExternalScript = (src, options) => {
        const targetSrc = String(src || '').trim();
        if (targetSrc === '') {
            return null;
        }

        const attrs = options && typeof options === 'object' ? options : {};
        if (state.loadedScripts[targetSrc]) {
            return null;
        }

        const script = document.createElement('script');
        script.src = targetSrc;
        script.async = attrs.async !== false;
        script.defer = attrs.defer === true;

        if (attrs.id) {
            script.setAttribute('data-flatcms-runtime-id', String(attrs.id));
        }

        state.loadedScripts[targetSrc] = true;
        (document.head || document.body || document.documentElement).appendChild(script);
        return script;
    };

    const initMatomo = () => {
        if (!cfg.matomoEnabled || analyticsState.matomoInitialized) {
            return;
        }

        const baseUrl = normalizeBaseUrl(cfg.matomoBaseUrl);
        const siteId = String(cfg.matomoSiteId || '').trim();
        if (baseUrl === '' || siteId === '') {
            return;
        }

        if (state.required && !hasConsent(['analytics', 'statistics', 'matomo'])) {
            return;
        }

        analyticsState.matomoInitialized = true;
        const trackerUrl = baseUrl + '/matomo.php';
        window._paq = Array.isArray(window._paq) ? window._paq : [];
        window._paq.push(['setTrackerUrl', trackerUrl]);
        window._paq.push(['setSiteId', siteId]);
        window._paq.push(['trackPageView']);
        window._paq.push(['enableLinkTracking']);

        appendExternalScript(baseUrl + '/matomo.js', {
            id: 'flatcms-matomo',
            async: true,
            defer: true,
        });
    };

    const initGoogleAnalytics = () => {
        if (!cfg.googleAnalyticsEnabled || analyticsState.googleAnalyticsInitialized) {
            return;
        }

        const measurementId = String(cfg.googleAnalyticsMeasurementId || '').trim();
        if (measurementId === '') {
            return;
        }

        if (state.required && !hasConsent(['analytics', 'statistics', 'measurement', 'ga4', 'google_analytics'])) {
            return;
        }

        analyticsState.googleAnalyticsInitialized = true;
        window.dataLayer = Array.isArray(window.dataLayer) ? window.dataLayer : [];
        window.gtag = typeof window.gtag === 'function'
            ? window.gtag
            : function gtag() {
                window.dataLayer.push(arguments);
            };

        window.gtag('js', new Date());
        window.gtag('config', measurementId);

        appendExternalScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId), {
            id: 'flatcms-ga4',
            async: true,
            defer: false,
        });
    };

    const initAnalyticsIntegrations = () => {
        initMatomo();
        initGoogleAnalytics();
    };

    const asUrl = (rawUrl) => {
        const value = String(rawUrl || '').trim();
        if (value === '') {
            return null;
        }

        try {
            return new URL(value, window.location.href);
        } catch (error) {
            return null;
        }
    };

    const isScriptExecutableType = (node) => {
        const typeValue = String(node && node.type ? node.type : '')
            .trim()
            .toLowerCase();
        return SCRIPT_MIME_JS.includes(typeValue);
    };

    const isExternalScriptUrl = (src) => {
        const url = asUrl(src);
        if (!url) {
            return false;
        }
        if (url.protocol !== 'http:' && url.protocol !== 'https:') {
            return false;
        }
        const sourceOrigin = String(url.origin || '').toLowerCase();
        return sourceOrigin !== '' && sourceOrigin !== currentOrigin;
    };

    const inferRequirementsFromSrc = (src) => {
        const source = String(src || '');
        for (let index = 0; index < CONSENT_DOMAIN_RULES.length; index += 1) {
            const rule = CONSENT_DOMAIN_RULES[index];
            if (rule.pattern.test(source)) {
                return normalizeRequirements(rule.requirements || []);
            }
        }
        return [];
    };

    const getScriptRequirements = (node, src) => {
        const explicit = normalizeRequirements(
            (node && node.dataset && (
                node.dataset.flatcmsConsentVendors
                || node.dataset.flatcmsConsentVendor
                || node.dataset.flatcmsConsentCategories
                || node.dataset.flatcmsConsentCategory
            )) || ''
        );

        if (explicit.length > 0) {
            return explicit;
        }

        return inferRequirementsFromSrc(src);
    };

    const isEssentialScript = (node, src) => {
        const explicitEssential = toBool(node && node.dataset ? node.dataset.flatcmsConsentEssential : '');
        if (explicitEssential) {
            return true;
        }

        if (!isExternalScriptUrl(src)) {
            return true;
        }

        const source = String(src || '');
        if (toBool(node && node.dataset ? node.dataset.flatcmsTurnstile : '') || /challenges\.cloudflare\.com\/turnstile/i.test(source)) {
            return true;
        }

        if (/static\.axept\.io\/sdk\.js/i.test(source)) {
            return true;
        }

        return false;
    };

    const flattenChoices = (payload, prefix, map) => {
        const normalizedPrefix = normalizeToken(prefix);
        const asBool = normalizeChoiceValue(payload);
        if (normalizedPrefix !== '' && asBool !== null) {
            map[normalizedPrefix] = asBool;
        }

        if (!payload || typeof payload !== 'object') {
            return;
        }

        if (Array.isArray(payload)) {
            for (let index = 0; index < payload.length; index += 1) {
                flattenChoices(payload[index], normalizedPrefix, map);
            }
            return;
        }

        const keys = Object.keys(payload);
        for (let index = 0; index < keys.length; index += 1) {
            const key = keys[index];
            const child = payload[key];
            const childPrefix = normalizedPrefix !== '' ? (normalizedPrefix + '.' + normalizeToken(key)) : normalizeToken(key);
            flattenChoices(child, childPrefix, map);
        }
    };

    const hasConsent = (requirements) => {
        if (!state.required) {
            return true;
        }

        const tokens = normalizeRequirements(requirements);
        if (tokens.length === 0) {
            return false;
        }

        const mapKeys = Object.keys(state.map);
        for (let index = 0; index < tokens.length; index += 1) {
            const token = tokens[index];
            if (state.map[token] === true) {
                return true;
            }

            for (let keyIndex = 0; keyIndex < mapKeys.length; keyIndex += 1) {
                const key = mapKeys[keyIndex];
                if (state.map[key] !== true) {
                    continue;
                }

                if (
                    key === token
                    || key.endsWith('.' + token)
                    || key.indexOf(token + '.') === 0
                    || key.indexOf(token) !== -1
                    || token.indexOf(key) !== -1
                ) {
                    return true;
                }
            }
        }

        return false;
    };

    const flushQueuedDynamicScripts = () => {
        if (!state.required || state.queuedDynamicScripts.length === 0) {
            return;
        }

        const pending = state.queuedDynamicScripts.slice();
        state.queuedDynamicScripts = [];

        for (let index = 0; index < pending.length; index += 1) {
            const entry = pending[index];
            if (!entry || !entry.parent || !entry.src) {
                continue;
            }

            if (!entry.parent.isConnected) {
                continue;
            }

            const requirements = Array.isArray(entry.requirements) ? entry.requirements : [];
            const allowed = hasConsent(requirements);
            if (!allowed) {
                state.queuedDynamicScripts.push(entry);
                continue;
            }

            if (state.loadedScripts[entry.src]) {
                continue;
            }

            const loaded = document.createElement('script');
            const attrs = entry.attributes || [];

            for (let attrIndex = 0; attrIndex < attrs.length; attrIndex += 1) {
                const attr = attrs[attrIndex];
                const attrName = String(attr && attr.name ? attr.name : '').toLowerCase();
                if (
                    attrName === ''
                    || attrName === 'src'
                    || attrName === 'type'
                    || attrName.indexOf('data-flatcms-consent-') === 0
                ) {
                    continue;
                }
                loaded.setAttribute(attr.name, attr.value);
            }

            loaded.src = entry.src;
            loaded.setAttribute('data-flatcms-consent-loaded', '1');
            if (entry.crossOrigin !== '') {
                loaded.crossOrigin = entry.crossOrigin;
            }
            if (entry.referrerPolicy !== '') {
                loaded.referrerPolicy = entry.referrerPolicy;
            }
            if (entry.integrity !== '') {
                loaded.integrity = entry.integrity;
                if (!loaded.crossOrigin) {
                    loaded.crossOrigin = 'anonymous';
                }
            }

            state.loadedScripts[entry.src] = true;

            const before = entry.nextSibling && entry.nextSibling.parentNode === entry.parent
                ? entry.nextSibling
                : null;

            if (before) {
                entry.parent.insertBefore(loaded, before);
            } else {
                entry.parent.appendChild(loaded);
            }
        }
    };

    const queueDynamicScript = (node, parent, nextSibling, src, requirements) => {
        const queueEntry = {
            node: node,
            parent: parent,
            nextSibling: nextSibling || null,
            src: src,
            requirements: requirements,
            attributes: Array.from(node.attributes || []),
            crossOrigin: String(node.crossOrigin || ''),
            referrerPolicy: String(node.referrerPolicy || ''),
            integrity: String(node.integrity || ''),
        };

        node.setAttribute('data-flatcms-consent-status', 'queued');
        state.queuedDynamicScripts.push(queueEntry);
    };

    const installDynamicScriptGuard = () => {
        if (!state.required || state.domPatchInstalled) {
            return;
        }

        state.domPatchInstalled = true;

        const patchInsert = (methodName) => {
            const original = Node.prototype[methodName];
            if (typeof original !== 'function') {
                return;
            }

            Node.prototype[methodName] = function patchedInsert(node, referenceNode) {
                if (!state.required || !node || String(node.tagName || '').toLowerCase() !== 'script') {
                    return original.apply(this, arguments);
                }

                if (!isScriptExecutableType(node)) {
                    return original.apply(this, arguments);
                }

                const src = String(node.src || '').trim();
                if (src === '' || !isExternalScriptUrl(src)) {
                    return original.apply(this, arguments);
                }

                if (isEssentialScript(node, src)) {
                    return original.apply(this, arguments);
                }

                const requirements = getScriptRequirements(node, src);
                if (hasConsent(requirements)) {
                    return original.apply(this, arguments);
                }

                queueDynamicScript(node, this, methodName === 'appendChild' ? null : referenceNode, src, requirements);
                return node;
            };
        };

        patchInsert('appendChild');
        patchInsert('insertBefore');
    };

    const loadDeferredScripts = () => {
        const externalSelectors = [
            'script[type="text/plain"][data-flatcms-consent-src]',
            'script[type="application/plain"][data-flatcms-consent-src]',
            'script[type="text/x-flatcms-consent"][data-flatcms-consent-src]',
        ];
        const externalNodes = document.querySelectorAll(externalSelectors.join(','));
        for (let index = 0; index < externalNodes.length; index += 1) {
            const node = externalNodes[index];
            const src = String(node.dataset.flatcmsConsentSrc || '').trim();
            if (src === '') {
                continue;
            }

            if (state.loadedScripts[src]) {
                continue;
            }

            const requirements = normalizeRequirements(
                node.dataset.flatcmsConsentVendors
                || node.dataset.flatcmsConsentVendor
                || node.dataset.flatcmsConsentCategories
                || node.dataset.flatcmsConsentCategory
                || ''
            );

            if (!hasConsent(requirements)) {
                continue;
            }

            const loaded = document.createElement('script');
            loaded.src = src;
            loaded.async = node.dataset.async !== '0';
            loaded.defer = node.dataset.defer === '1';
            loaded.setAttribute('data-flatcms-consent-loaded', '1');

            if (node.dataset.crossorigin) {
                loaded.crossOrigin = node.dataset.crossorigin;
            }
            if (node.dataset.referrerpolicy) {
                loaded.referrerPolicy = node.dataset.referrerpolicy;
            }
            if (node.dataset.integrity) {
                loaded.integrity = node.dataset.integrity;
                if (!loaded.crossOrigin) {
                    loaded.crossOrigin = 'anonymous';
                }
            }

            state.loadedScripts[src] = true;
            node.setAttribute('data-flatcms-consent-status', 'loaded');
            node.parentNode.insertBefore(loaded, node.nextSibling);
        }

        const inlineSelectors = [
            'script[type="text/plain"][data-flatcms-consent-inline]',
            'script[type="application/plain"][data-flatcms-consent-inline]',
            'script[type="text/x-flatcms-consent"][data-flatcms-consent-inline]',
        ];
        const inlineNodes = document.querySelectorAll(inlineSelectors.join(','));
        for (let index = 0; index < inlineNodes.length; index += 1) {
            const node = inlineNodes[index];
            const code = String(node.textContent || '').trim();
            if (code === '') {
                continue;
            }

            const inlineId = String(node.dataset.flatcmsConsentId || '').trim() || ('inline_' + index);
            if (state.executedInline[inlineId]) {
                continue;
            }

            const requirements = normalizeRequirements(
                node.dataset.flatcmsConsentVendors
                || node.dataset.flatcmsConsentVendor
                || node.dataset.flatcmsConsentCategories
                || node.dataset.flatcmsConsentCategory
                || ''
            );

            if (!hasConsent(requirements)) {
                continue;
            }

            const inlineScript = document.createElement('script');
            inlineScript.setAttribute('data-flatcms-consent-loaded', '1');
            inlineScript.textContent = code;

            state.executedInline[inlineId] = true;
            node.setAttribute('data-flatcms-consent-status', 'loaded');
            node.parentNode.insertBefore(inlineScript, node.nextSibling);
        }

        flushQueuedDynamicScripts();
    };

    const applyConsentChoices = (choices) => {
        state.choices = (choices && typeof choices === 'object') ? choices : {};
        state.map = Object.create(null);
        flattenChoices(state.choices, '', state.map);
        state.ready = true;

        const detail = currentConsentDetail();
        loadDeferredScripts();
        initAnalyticsIntegrations();
        runCallbacks(state.updateCallbacks, detail);

        if (state.readyCallbacks.length > 0) {
            const pending = state.readyCallbacks.slice();
            state.readyCallbacks = [];
            runCallbacks(pending, detail);
        }

        dispatchConsentEvent('flatcms:consent:update', detail);
        dispatchConsentEvent('flatcms:consent:ready', detail);
    };

    const consentApi = window.FlatCMSConsent || {};
    consentApi.isRequired = () => state.required;
    consentApi.isReady = () => state.ready;
    consentApi.getChoices = () => state.choices;
    consentApi.getChoiceMap = () => Object.assign({}, state.map);
    consentApi.hasConsent = (requirements) => hasConsent(requirements);
    consentApi.onUpdate = (callback) => {
        if (typeof callback === 'function') {
            state.updateCallbacks.push(callback);
        }
    };
    consentApi.whenReady = (callback) => {
        if (typeof callback !== 'function') {
            return;
        }

        if (state.ready) {
            callback(currentConsentDetail());
            return;
        }

        state.readyCallbacks.push(callback);
    };
    consentApi.loadDeferredScripts = () => {
        loadDeferredScripts();
    };
    consentApi.injectScript = (options) => {
        if (!options || typeof options !== 'object') {
            return null;
        }

        const src = String(options.src || '').trim();
        if (src === '') {
            return null;
        }

        const script = document.createElement('script');
        script.type = 'text/plain';
        script.setAttribute('data-flatcms-consent-src', src);

        const requirements = normalizeRequirements(options.vendors || options.vendor || options.categories || options.category || '');
        if (requirements.length > 0) {
            script.setAttribute('data-flatcms-consent-vendors', requirements.join(','));
        }

        if (toBool(options.essential)) {
            script.setAttribute('data-flatcms-consent-essential', '1');
        }

        if (options.id) {
            script.setAttribute('data-flatcms-consent-id', String(options.id));
        }
        if (options.async === true) {
            script.setAttribute('data-async', '1');
        } else if (options.async === false) {
            script.setAttribute('data-async', '0');
        }
        if (options.defer === true) {
            script.setAttribute('data-defer', '1');
        }
        if (options.crossorigin) {
            script.setAttribute('data-crossorigin', String(options.crossorigin));
        }
        if (options.referrerpolicy) {
            script.setAttribute('data-referrerpolicy', String(options.referrerpolicy));
        }
        if (options.integrity) {
            script.setAttribute('data-integrity', String(options.integrity));
        }

        const target = options.target && options.target.nodeType === 1 ? options.target : document.head;
        target.appendChild(script);
        loadDeferredScripts();
        return script;
    };
    window.FlatCMSConsent = consentApi;

    installDynamicScriptGuard();

    if (!axeptioEnabled) {
        state.ready = true;
        loadDeferredScripts();
        initAnalyticsIntegrations();
        dispatchConsentEvent('flatcms:consent:ready', currentConsentDetail());
        return;
    }

    const axeptioSettings = {
        clientId: cfg.axeptioClientId,
        cookiesVersion: cfg.axeptioCookiesVersion,
    };

    window.axeptioSettings = axeptioSettings;

    window._axcb = Array.isArray(window._axcb) ? window._axcb : [];
    if (!window.__flatcmsAxeptioHookRegistered) {
        window._axcb.push(function (axeptio) {
            if (!axeptio || typeof axeptio.on !== 'function') {
                return;
            }

            axeptio.on('cookies:complete', function (choices) {
                window.__flatcmsAxeptioChoices = choices || {};
                applyConsentChoices(window.__flatcmsAxeptioChoices);

                if (typeof window.CustomEvent === 'function') {
                    window.dispatchEvent(new window.CustomEvent('flatcms:axeptio:cookies-complete', {
                        detail: {
                            choices: window.__flatcmsAxeptioChoices,
                        },
                    }));
                }
            });
        });

        window.__flatcmsAxeptioHookRegistered = true;
    }

    if (document.querySelector('script[data-flatcms-axeptio-sdk]')) {
        return;
    }

    const sdk = document.createElement('script');
    sdk.src = '//static.axept.io/sdk.js';
    sdk.async = true;
    sdk.defer = true;
    sdk.setAttribute('data-flatcms-axeptio-sdk', '1');

    const firstScript = document.getElementsByTagName('script')[0];
    if (firstScript && firstScript.parentNode) {
        firstScript.parentNode.insertBefore(sdk, firstScript);
        if (!state.required) {
            state.ready = true;
            loadDeferredScripts();
            initAnalyticsIntegrations();
            dispatchConsentEvent('flatcms:consent:ready', currentConsentDetail());
        }
        return;
    }

    (document.head || document.body || document.documentElement).appendChild(sdk);
    if (!state.required) {
        state.ready = true;
        loadDeferredScripts();
        initAnalyticsIntegrations();
        dispatchConsentEvent('flatcms:consent:ready', currentConsentDetail());
    }
})();
