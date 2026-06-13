/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function () {
    var darkStored = null;
    try {
        darkStored = window.localStorage.getItem("flatcms_install_dark");
    } catch (error) {
        darkStored = null;
    }
    var prefersDark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
    var isDark = darkStored === "true" || (darkStored === null && prefersDark);

    if (isDark) {
        document.documentElement.classList.add("dark");
    }
})();
