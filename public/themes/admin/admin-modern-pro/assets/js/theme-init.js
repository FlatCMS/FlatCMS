/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
  'use strict';
  const savedTheme = localStorage.getItem('admin-theme');
  if (savedTheme === 'light') {
    document.documentElement.classList.add('theme-light-init');
  }
})();
