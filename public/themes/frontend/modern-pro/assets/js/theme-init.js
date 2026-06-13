/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
  'use strict';

  const themeMedia = typeof window.matchMedia === 'function'
    ? window.matchMedia('(prefers-color-scheme: light)')
    : null;
  let bodyObserver = null;

  function prefersLightTheme() {
    return !!(themeMedia && themeMedia.matches);
  }

  function applySystemThemeClass() {
    const isLight = prefersLightTheme();
    document.documentElement.classList.toggle('theme-light-init', isLight);

    if (document.body) {
      document.body.classList.toggle('light-mode', isLight);
      if (bodyObserver) {
        bodyObserver.disconnect();
        bodyObserver = null;
      }
      return;
    }

    if (!bodyObserver && document.documentElement) {
      bodyObserver = new MutationObserver(function() {
        if (!document.body) {
          return;
        }
        applySystemThemeClass();
      });
      bodyObserver.observe(document.documentElement, { childList: true, subtree: true });
    }
  }

  applySystemThemeClass();

  if (themeMedia) {
    if (typeof themeMedia.addEventListener === 'function') {
      themeMedia.addEventListener('change', applySystemThemeClass);
    } else if (typeof themeMedia.addListener === 'function') {
      themeMedia.addListener(applySystemThemeClass);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applySystemThemeClass, { once: true });
  }
})();
