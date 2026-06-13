/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function () {
    'use strict';

    function injectStructuredData(scriptEl) {
        if (!scriptEl) {
            return;
        }

        const raw = (scriptEl.textContent || '').trim();
        if (!raw) {
            return;
        }

        let parsed;
        try {
            parsed = JSON.parse(raw);
        } catch (error) {
            return;
        }

        if (!parsed || (typeof parsed !== 'object' && !Array.isArray(parsed))) {
            return;
        }

        const jsonLdScript = document.createElement('script');
        jsonLdScript.setAttribute('type', 'application/ld+json');
        jsonLdScript.textContent = JSON.stringify(parsed);
        document.head.appendChild(jsonLdScript);
    }

    function run() {
        const payloads = document.querySelectorAll('script[type="application/json"][data-flatcms-structured-data]');
        payloads.forEach(injectStructuredData);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
        return;
    }

    run();
})();
