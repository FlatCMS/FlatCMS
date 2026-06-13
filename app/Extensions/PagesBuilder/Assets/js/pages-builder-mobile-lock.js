/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const modal = document.getElementById('pagesBuilderMobileLockModal');
    if (!modal) {
        return;
    }

    const isMobileLockMode = () => {
        if (!window.matchMedia) {
            return window.innerWidth <= 767;
        }
        if (window.matchMedia('(max-width: 767px)').matches) {
            return true;
        }
        return window.matchMedia('(pointer: coarse)').matches && window.matchMedia('(max-height: 500px)').matches;
    };

    if (!isMobileLockMode()) {
        return;
    }

    const redirectUrl = String(modal.dataset.redirectUrl || '/admin/pages').trim() || '/admin/pages';
    const close = () => {
        window.location.href = redirectUrl;
    };

    modal.classList.remove('is-initially-hidden');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            close();
        }
    });

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('[data-action="pb-mobile-lock-close"]');
        if (!btn) {
            return;
        }
        event.preventDefault();
        close();
    });
})();
