(function (window) {
  'use strict';

  const Studio = window.FlatCMSStudio || (window.FlatCMSStudio = {});

  async function load(app) {
    if (!app.config.dataUrl) {
      return;
    }

    const response = await fetch(app.config.dataUrl, {
      credentials: 'same-origin'
    });
    const payload = await response.json();
    if (!response.ok || !payload.ok) {
      throw new Error(payload && payload.message ? payload.message : 'load');
    }

    app.page = Studio.state.normalizePage(payload.page || app.page);
    app.sources = Array.isArray(payload.sources) ? payload.sources : app.sources;
    app.currentSource = payload.currentSource && typeof payload.currentSource === 'object' ? payload.currentSource : app.currentSource;
    app.ui = payload.ui || app.ui;
    app.library = payload.library || app.library;
    Studio.state.syncSelection(app);
  }

  async function save(app) {
    app.isSaving = true;

    const response = await fetch(app.config.saveUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': app.config.token || ''
      },
      body: JSON.stringify({
        page: app.page
      })
    });

    const payload = await response.json();
    app.isSaving = false;

    if (!response.ok || !payload.ok) {
      throw new Error(payload && payload.message ? payload.message : 'save');
    }

    app.page = Studio.state.normalizePage(payload.page || app.page);
    app.currentSource = payload.currentSource && typeof payload.currentSource === 'object' ? payload.currentSource : app.currentSource;
    if (app.currentSource && app.currentSource.preview_url) {
      app.config.previewUrl = app.currentSource.preview_url;
    }
    Studio.state.syncSelection(app);
    Studio.state.setClean(app);

    return payload;
  }

  function resolvePreviewNavIndex(app) {
    if (app.selection.kind === 'nav-item' && app.selection.navIndex != null) {
      return app.selection.navIndex;
    }

    if (Studio.nav && typeof Studio.nav.activeMegaPreviewIndex === 'function') {
      return Studio.nav.activeMegaPreviewIndex(app);
    }

    return null;
  }

  async function refreshPreviewFrame(app) {
    if (!app.config.previewRenderUrl) {
      throw new Error('preview');
    }

    const response = await fetch(app.config.previewRenderUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': app.config.token || ''
      },
      body: JSON.stringify({
        page: app.page,
        nav_index: resolvePreviewNavIndex(app)
      })
    });

    const payload = await response.json();
    if (!response.ok || !payload.ok || !payload.url) {
      throw new Error(payload && payload.message ? payload.message : 'preview');
    }

    app.previewFrameUrl = String(payload.url || '');
    app.previewNeedsRefresh = false;

    return app.previewFrameUrl;
  }

  async function preview(app) {
    const previewUrl = await refreshPreviewFrame(app);
    if (previewUrl) {
      window.open(previewUrl, '_blank', 'noopener');
    }
  }

  function exportState(app) {
    const filenameBase = app.ui.exportFilename || 'studio-export';
    const slug = app.page && app.page.page ? app.page.page.slug || 'home' : 'home';
    Studio.core.createDownload(filenameBase + '-' + slug + '.json', JSON.stringify(app.page, null, 2));
  }

  Studio.api = {
    load,
    save,
    refreshPreviewFrame,
    preview,
    exportState
  };
})(window);
