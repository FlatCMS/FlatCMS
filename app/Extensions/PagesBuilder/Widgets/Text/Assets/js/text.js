/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const ROOT_SELECTOR = '.pb-text-inner';

    const syncAlignedLists = (scope) => {
        const root = scope instanceof HTMLElement || scope instanceof Document ? scope : document;

        root.querySelectorAll(ROOT_SELECTOR).forEach((node) => {
            if (!(node instanceof HTMLElement)) {
                return;
            }

            const lists = Array.from(node.querySelectorAll('ul, ol')).filter((list) => list instanceof HTMLElement);
            lists.forEach((list) => {
                if (list instanceof HTMLElement) {
                    list.style.width = '';
                }
            });

            if (!node.classList.contains('pb-text-list-align-center') || lists.length === 0) {
                return;
            }

            const availableWidth = Math.floor(node.clientWidth);
            if (availableWidth <= 0) {
                return;
            }

            const targetWidth = Math.min(
                availableWidth,
                lists.reduce((maxWidth, list) => {
                    if (!(list instanceof HTMLElement)) {
                        return maxWidth;
                    }

                    return Math.max(maxWidth, Math.ceil(list.getBoundingClientRect().width));
                }, 0)
            );

            if (targetWidth <= 0) {
                return;
            }

            lists.forEach((list) => {
                if (list instanceof HTMLElement) {
                    list.style.width = `${targetWidth}px`;
                }
            });
        });
    };

    const init = (scope) => {
        syncAlignedLists(scope);
    };

    window.FlatCMSText = {
        init,
        syncAlignedLists,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init(document), { once: true });
    } else {
        init(document);
    }

    window.addEventListener('resize', () => init(document));
})();
