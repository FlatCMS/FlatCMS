(function (window, document) {
  'use strict';

  const Studio = window.FlatCMSStudio || (window.FlatCMSStudio = {});
  const core = Studio.core;
  const MANAGED_FOOTER_LOGO_ID = 'studio-managed-footer-logo';
  const MANAGED_FOOTER_BRAND_ID = 'studio-managed-footer-brand';
  const BRAND_FALLBACK_GLYPH = '◆';

  function cache(app) {
    if (app.cache.ready) {
      return;
    }

    app.cache.drawer = document.getElementById('studio-drawer');
    app.cache.drawerTitle = document.getElementById('studio-drawer-title');
    app.cache.drawerSubtitle = document.getElementById('studio-drawer-subtitle');
    app.cache.drawerContent = document.getElementById('studio-drawer-content');
    app.cache.stageShell = document.getElementById('studio-stage-shell');
    app.cache.stage = document.getElementById('studio-stage');
    app.cache.inspector = document.getElementById('studio-inspector-panel');
    app.cache.inspectorTitle = document.getElementById('studio-inspector-title');
    app.cache.inspectorSubtitle = document.getElementById('studio-inspector-subtitle');
    app.cache.inspectorContent = document.getElementById('studio-inspector-content');
    app.cache.viewportLabel = document.getElementById('studio-viewport-label');
    app.cache.viewportSize = document.getElementById('studio-viewport-size');
    app.cache.zoomSelect = document.getElementById('studio-zoom-select');
    app.cache.topbar = app.root.querySelector('.studio-topbar');
    app.cache.shell = app.root.querySelector('.studio-shell');
    app.cache.ready = true;
  }

  function renderAll(app) {
    cache(app);
    applyDesign(app);
    renderDrawer(app);
    renderCanvas(app);
    renderInspector(app);
    syncShell(app);
    syncViewport(app);
    syncPanelTop(app);
  }

  function applyDesign(app) {
    const stage = app.cache.stage;
    const design = app.page && app.page.design ? app.page.design.global || {} : {};
    if (!stage) {
      return;
    }

    stage.style.setProperty('--page-primary', design.primary || '#4F46E5');
    stage.style.setProperty('--page-accent', design.accent || '#111827');
    stage.style.setProperty('--page-ink', design.ink || '#111827');
    stage.style.setProperty('--page-paper', design.paper || '#FFFFFF');
    stage.style.setProperty('--page-soft', design.soft || '#F7F8FA');
    stage.style.setProperty('--page-radius', String(Number(design.radius || 8)) + 'px');
    stage.style.maxWidth = String(Number(design.width || 1180)) + 'px';

    if (design.font) {
      stage.style.fontFamily = design.font;
    } else {
      stage.style.removeProperty('font-family');
    }

    stage.classList.toggle('is-selected', app.selection.kind === 'page');
    document.documentElement.style.setProperty('--studio-primary', design.primary || '#4F46E5');
  }

  function syncShell(app) {
    const shell = app.cache.shell;
    const drawer = app.cache.drawer;
    const inspector = app.cache.inspector;
    if (!shell) {
      return;
    }

    const drawerOpen = Boolean(drawer && drawer.classList.contains('is-open'));
    const inspectorOpen = Boolean(inspector && inspector.classList.contains('is-open'));

    shell.classList.toggle('has-drawer-open', drawerOpen);
    shell.classList.toggle('has-inspector-open', inspectorOpen);

    app.root.querySelectorAll('.studio-rail-btn').forEach(function (button) {
      button.classList.toggle('is-active', drawerOpen && button.dataset.drawer === app.activeDrawer);
    });

    app.root.querySelectorAll('[data-action="switch-canvas-mode"]').forEach(function (button) {
      button.classList.toggle('is-active', button.dataset.mode === app.canvasMode);
    });
  }

  function syncViewport(app) {
    const viewportWidths = {
      desktop: String(Number(app.page && app.page.design && app.page.design.global && app.page.design.global.width ? app.page.design.global.width : 1180)) + 'px',
      tablet: '834px',
      mobile: '390px'
    };
    const viewportLabels = {
      desktop: localizedViewportLabel(app, 'desktop'),
      tablet: localizedViewportLabel(app, 'tablet'),
      mobile: localizedViewportLabel(app, 'mobile')
    };
    const zoom = normalizeZoom(app.zoom);
    const scale = zoom / 100;

    app.root.querySelectorAll('[data-action="viewport"]').forEach(function (button) {
      button.classList.toggle('is-active', button.dataset.viewport === app.viewport);
    });

    if (app.cache.stage) {
      app.cache.stage.classList.toggle('is-tablet', app.viewport === 'tablet');
      app.cache.stage.classList.toggle('is-mobile', app.viewport === 'mobile');
      app.cache.stage.style.setProperty('--studio-stage-scale', String(scale));
    }

    if (app.cache.stageShell) {
      applyStageScale(app, scale);
    }

    if (app.cache.viewportLabel) {
      app.cache.viewportLabel.textContent = viewportLabels[app.viewport] || viewportLabels.desktop;
    }

    if (app.cache.viewportSize) {
      app.cache.viewportSize.textContent = viewportWidths[app.viewport] || viewportWidths.desktop;
    }

    if (app.cache.zoomSelect) {
      app.cache.zoomSelect.value = String(zoom);
    }
  }

  function localizedViewportLabel(app, viewport) {
    const selector = '[data-action="viewport"][data-viewport="' + viewport + '"]';
    const button = app.root.querySelector(selector);
    return button ? String(button.getAttribute('aria-label') || button.getAttribute('title') || viewport) : viewport;
  }

  function normalizeZoom(value) {
    const allowed = [50, 67, 75, 90, 100, 110, 125, 150];
    const numeric = Number(value);
    return allowed.indexOf(numeric) >= 0 ? numeric : 100;
  }

  function applyStageScale(app, scale) {
    const shell = app.cache.stageShell;
    const stage = app.cache.stage;

    if (!shell || !stage) {
      return;
    }

    shell.style.setProperty('--studio-stage-scale', String(scale));

    if (scale === 1) {
      shell.classList.remove('is-zoomed');
      shell.style.removeProperty('--studio-stage-height');
      return;
    }

    const stageHeight = Math.max(stage.offsetHeight || 0, stage.scrollHeight || 0, stage.clientHeight || 0);
    shell.classList.add('is-zoomed');
    shell.style.setProperty('--studio-stage-height', String(stageHeight) + 'px');

    window.requestAnimationFrame(function () {
      const nextHeight = Math.max(stage.offsetHeight || 0, stage.scrollHeight || 0, stage.clientHeight || 0);
      shell.style.setProperty('--studio-stage-height', String(nextHeight) + 'px');
    });
  }

  function syncPanelTop(app) {
    const topbar = app.cache.topbar;
    if (!topbar) {
      return;
    }

    const height = Math.ceil(topbar.getBoundingClientRect().height);
    app.root.style.setProperty('--studio-panel-top', String(height) + 'px');
  }

  function setStatus(app, keyOrMessage, errorMode) {
    app.statusKey = keyOrMessage;
    app.statusError = Boolean(errorMode);
    syncStatus(app);
  }

  function syncStatus(app) {
    if (!shouldToastStatus(app.statusKey, app.statusError)) {
      return;
    }

    const label = app.ui.status && app.ui.status[app.statusKey] ? app.ui.status[app.statusKey] : app.statusKey;
    const text = String(label || '').trim();
    if (text === '') {
      return;
    }

    if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
      window.FlatCMS.toast.show(text, app.statusError ? 'error' : 'success');
    }
  }

  function shouldToastStatus(statusKey, errorMode) {
    if (errorMode) {
      return true;
    }

    return statusKey === 'saved' || statusKey === 'exported';
  }

  function openDrawer(app, drawerName) {
    app.activeDrawer = drawerName;
    app.cache.drawer.classList.add('is-open');
    syncShell(app);
    renderDrawer(app);
  }

  function closeDrawer(app) {
    app.cache.drawer.classList.remove('is-open');
    syncShell(app);
  }

  function renderDrawer(app) {
    const drawerUi = app.ui.drawers || {};
    const drawerMeta = drawerUi[app.activeDrawer] || drawerUi.sections || {};
    app.cache.drawerTitle.textContent = drawerMeta.title || '';
    app.cache.drawerSubtitle.textContent = drawerMeta.subtitle || '';

    if (app.activeDrawer === 'sections') {
      app.cache.drawerContent.innerHTML = renderToolList(app, Object.keys(app.library.sections || {}).map(function (key) {
        return renderToolCard('section', key, app.library.sections[key]);
      }).join(''));
      return;
    }

    if (app.activeDrawer === 'blocks') {
      app.cache.drawerContent.innerHTML = renderToolList(app, Object.keys(app.library.blocks || {}).filter(function (key) {
        return app.library.blocks[key].drawer === 'blocks';
      }).map(function (key) {
        return renderToolCard('block', key, app.library.blocks[key]);
      }).join(''));
      return;
    }

    if (app.activeDrawer === 'plugins') {
      app.cache.drawerContent.innerHTML = renderToolList(app, Object.keys(app.library.blocks || {}).filter(function (key) {
        return app.library.blocks[key].drawer === 'plugins';
      }).map(function (key) {
        return renderToolCard('block', key, app.library.blocks[key]);
      }).join('')) + renderNote(drawerMeta.note || '');
      return;
    }

    if (app.activeDrawer === 'menu') {
      app.cache.drawerContent.innerHTML =
        renderToolList(app, Object.keys(app.library.menuTools || {}).map(function (key) {
          return renderToolCard('menu-tool', key, app.library.menuTools[key]);
        }).join('')) +
        '<div class="studio-drawer-divider"></div>' +
        '<div class="studio-inspector-actions">' +
        '<button class="studio-btn studio-btn-full" type="button" data-action="select-nav">' + core.escapeHtml((app.ui.buttons && app.ui.buttons.navbar) || '') + '</button>' +
        '<button class="studio-btn studio-btn-full" type="button" data-action="add-nav-item">' + core.escapeHtml(app.ui.actions.addNavItem || '') + '</button>' +
        '</div>' +
        renderNote(drawerMeta.note || '');
      return;
    }

    app.cache.drawerContent.innerHTML = renderPagePanel(app);
  }

  function renderToolList(app, html) {
    if (html === '') {
      return renderEmptyNote(app.ui.inspector.empty || '');
    }

    return '<div class="studio-tool-list">' + html + '</div>';
  }

  function renderToolCard(kind, type, meta) {
    return '<button class="studio-tool-card" type="button" draggable="true" data-drag-kind="' + core.escapeAttr(kind) + '" data-drag-type="' + core.escapeAttr(type) + '" data-action="tool-click" data-kind="' + core.escapeAttr(kind) + '" data-type="' + core.escapeAttr(type) + '">' +
      '<span class="studio-tool-icon">' + core.escapeHtml(meta.icon || '□') + '</span>' +
      '<span><strong>' + core.escapeHtml(meta.name || '') + '</strong><small>' + core.escapeHtml(meta.help || '') + '</small></span>' +
      '</button>';
  }

  function renderNote(text) {
    return text ? '<p class="studio-help-note">' + core.escapeHtml(text) + '</p>' : '';
  }

  function renderEmptyNote(text) {
    return '<p class="studio-empty-note">' + core.escapeHtml(text || '') + '</p>';
  }

  function renderPagePanel(app) {
    const sourceField = app.ui.pagePanel ? app.ui.pagePanel.sourceField || null : null;
    const documentFields = app.ui.pagePanel ? app.ui.pagePanel.documentFields || [] : [];
    const pageFields = app.ui.pagePanel ? app.ui.pagePanel.pageFields || [] : [];
    const designFields = app.ui.pagePanel ? app.ui.pagePanel.designFields || [] : [];
    const pageMeta = app.page.page || {};
    const design = app.page.design ? app.page.design.global || {} : {};
    const currentSource = Studio.state.currentSource ? Studio.state.currentSource(app) : null;

    return '<div class="studio-inspector-stack">' +
      renderInspectorCard(
        app.ui.inspector.summaryTitle || '',
        currentSource && currentSource.title ? currentSource.title : '',
        [
          { label: app.ui.inspector.sourceLabel || '', value: currentSource && currentSource.title ? currentSource.title : '' },
          { label: app.ui.inspector.statusLabel || '', value: currentSource && currentSource.status_label ? currentSource.status_label : (pageMeta.status || '') }
        ]
      ) +
      '<div class="studio-form-grid">' +
      renderSourceField(app, sourceField, currentSource) +
      '<div class="studio-mini-title">' + core.escapeHtml(app.ui.inspector.pageTitle || '') + '</div>' +
      documentFields.map(function (field) {
        return renderBoundField(field, field.bind, resolvePagePanelValue(pageMeta, design, field.bind));
      }).join('') +
      pageFields.map(function (field) {
        return renderBoundField(field, field.bind, resolvePagePanelValue(pageMeta, design, field.bind));
      }).join('') +
      '<div class="studio-mini-title">' + core.escapeHtml(app.ui.inspector.designTitle || '') + '</div>' +
      designFields.map(function (field) {
        return renderBoundField(field, field.bind, resolvePagePanelValue(pageMeta, design, field.bind));
      }).join('') +
      '</div>' +
      '</div>';
  }

  function resolvePagePanelValue(pageMeta, design, bind) {
    const parts = bind.split('.');
    if (parts[0] === 'page') {
      return pageMeta[parts[1]] || '';
    }

    if (parts[0] === 'design') {
      return design[parts[1]] || '';
    }

    return '';
  }

  function renderSourceField(app, field, currentSource) {
    if (!field) {
      return '';
    }

    const options = (app.sources || []).map(function (source) {
      const pieces = [source.title || ''];
      if (source.locale_label) {
        pieces.push(source.locale_label);
      }
      if (source.status_label) {
        pieces.push(source.status_label);
      }

      return {
        value: String(source.id || ''),
        label: pieces.filter(Boolean).join(' · ')
      };
    });

    const currentId = currentSource && currentSource.id ? String(currentSource.id) : '';
    const hint = field.hint ? '<div class="studio-help-note">' + core.escapeHtml(field.hint) + '</div>' : '';
    const emptyOption = options.length === 0 ? '<option value="">-</option>' : '';

    return '<div class="studio-field">' +
      '<label>' + core.escapeHtml(field.label || '') + '</label>' +
      '<select class="studio-select" data-action="switch-source-page"' + (options.length === 0 ? ' disabled' : '') + '>' +
      emptyOption +
      options.map(function (option) {
        return '<option value="' + core.escapeAttr(option.value) + '"' + (option.value === currentId ? ' selected' : '') + '>' + core.escapeHtml(option.label) + '</option>';
      }).join('') +
      '</select>' +
      hint +
      '</div>';
  }

  function renderCanvas(app) {
    const html = app.canvasMode === 'render'
      ? renderRenderStage(app)
      : renderComposeCanvas(app);

    app.cache.stage.innerHTML = html;
    app.cache.stage.classList.toggle('is-render-mode', app.canvasMode === 'render');
    app.cache.stage.classList.toggle('is-compose-mode', app.canvasMode !== 'render');
  }

  function renderComposeCanvas(app) {
    return '<div class="studio-layout-canvas">' +
      renderHeaderCanvas(app) +
      renderContentLayout(app) +
      renderLayoutBlockRegion(app, 'footer', 'footer') +
      '</div>';
  }

  function renderRenderStage(app) {
    const currentSource = Studio.state.currentSource ? Studio.state.currentSource(app) : null;
    const sourceTitle = currentSource && currentSource.title ? currentSource.title : '';
    const frameUrl = String(app.previewFrameUrl || '');
    const headline = (app.ui.preview && app.ui.preview.title) || '';
    const caption = app.previewNeedsRefresh
      ? ((app.ui.preview && app.ui.preview.stale) || '')
      : ((app.ui.preview && app.ui.preview.caption) || '');

    return '<div class="studio-render-stage">' +
      '<div class="studio-render-stage-head">' +
      '<div class="studio-render-stage-copy">' +
      '<strong>' + core.escapeHtml(headline) + '</strong>' +
      '<span>' + core.escapeHtml(caption) + '</span>' +
      (sourceTitle ? '<small>' + core.escapeHtml(((app.ui.preview && app.ui.preview.sourcePrefix) || '') + sourceTitle) + '</small>' : '') +
      '</div>' +
      '<div class="studio-render-stage-actions">' +
      '<button class="studio-btn" type="button" data-action="refresh-render-frame">' + core.escapeHtml((app.ui.actions && app.ui.actions.refreshRender) || '') + '</button>' +
      '<button class="studio-btn" type="button" data-action="preview">' + core.escapeHtml((app.ui.actions && app.ui.actions.openRender) || '') + '</button>' +
      '</div>' +
      '</div>' +
      '<div class="studio-render-stage-frame">' +
      (
        frameUrl
          ? '<iframe class="studio-render-frame" src="' + core.escapeAttr(frameUrl) + '" title="' + core.escapeAttr(headline || 'Studio preview') + '" loading="eager" referrerpolicy="strict-origin-when-cross-origin"></iframe>'
          : '<div class="studio-render-empty"><strong>' + core.escapeHtml(headline) + '</strong><span>' + core.escapeHtml(caption) + '</span></div>'
      ) +
      '</div>' +
      '</div>';
  }

  function renderHeaderCanvas(app) {
    const active = isHeaderRegionActive(app);

    return '<header class="studio-layout-region studio-layout-region-header ' + (active ? 'is-selected' : '') + '">' +
      renderLayoutHead(app, 'header') +
      '<div class="studio-layout-surface studio-layout-surface-header">' +
      '<div class="studio-header-stack">' +
      renderHeaderSlot(app, 'header_before') +
      renderNavSlotCanvas(app) +
      renderHeaderSlot(app, 'header_after') +
      '</div>' +
      '</div>' +
      '</header>';
  }

  function renderHeaderSlot(app, regionName) {
    const meta = layoutMeta(app, regionName);
    const selected = isLayoutRegionSelected(app, regionName) ||
      (app.selection.kind === 'block' && app.selection.layoutRegion === regionName);

    return '<section class="studio-layout-slot studio-layout-slot-' + core.escapeAttr(regionName) + ' ' + (selected ? 'is-selected' : '') + '" data-action="select-layout-region" data-layout-region="' + core.escapeAttr(regionName) + '">' +
      renderLayoutSlotHead(meta) +
      renderBlockZone(app, { layoutRegion: regionName }) +
      '</section>';
  }

  function renderNavSlotCanvas(app) {
    const meta = layoutMeta(app, 'nav');
    const navSelected = app.selection.kind === 'nav' || app.selection.kind === 'nav-item' || app.selection.kind === 'nav-zone' || app.selection.kind === 'nav-element';

    return '<section class="studio-layout-slot studio-layout-slot-nav ' + (navSelected ? 'is-selected' : '') + '" data-action="select-nav">' +
      renderLayoutSlotHead(meta) +
      renderNavSurface(app) +
      '</section>';
  }

  function renderNavSurface(app) {
    const navbar = app.page.navbar || { brand: { label: '' }, rows: {}, items: [] };
    const previewIndex = Studio.nav.activeMegaPreviewIndex(app);
    const previewItem = previewIndex != null ? Studio.nav.navItem(app, previewIndex) : null;
    const navSelected = app.selection.kind === 'nav' || app.selection.kind === 'nav-item' || app.selection.kind === 'nav-zone' || app.selection.kind === 'nav-element';
    const navDetailed = navSelected || navbarHasCanvasContent(app) || Boolean(previewItem);

    return '<div class="studio-layout-surface studio-layout-surface-nav">' +
      (
        navDetailed
          ? '<div class="studio-canvas-nav ' + (previewItem ? 'has-mega-preview' : '') + '">' +
            Studio.nav.ROWS.map(function (rowName) {
              return renderNavRow(app, rowName, navSelected);
            }).join('') +
            (previewItem && previewItem.mega_menu && previewItem.mega_menu.enabled ? renderMegaPreview(app, previewItem, previewIndex) : '') +
            '</div>'
          : renderNavEmptyState(app)
      ) +
      '</div>';
  }

  function layoutMeta(app, regionName) {
    return app.ui.layout && app.ui.layout[regionName] ? app.ui.layout[regionName] : {};
  }

  function renderLayoutHead(app, regionName) {
    const meta = layoutMeta(app, regionName);
    const title = meta.title || '';
    const tag = meta.tag || '';

    return '<div class="studio-layout-head"><strong>' + core.escapeHtml(title) + '</strong><span>&lt;' + core.escapeHtml(tag) + '&gt;</span></div>';
  }

  function renderLayoutSlotHead(meta) {
    const title = meta && meta.title ? meta.title : '';
    const tag = meta && meta.tag ? meta.tag : '';

    return '<div class="studio-layout-slot-head"><strong>' + core.escapeHtml(title) + '</strong><span>&lt;' + core.escapeHtml(tag) + '&gt;</span></div>';
  }

  function isLayoutRegionSelected(app, regionName) {
    return app.selection.kind === 'layout-region' && app.selection.layoutRegion === regionName;
  }

  function regionHasBlocks(app, regionName) {
    return resolveBlocksForTarget(app, { layoutRegion: regionName }).length > 0;
  }

  function isHeaderRegionActive(app) {
    return app.selection.kind === 'nav' ||
      app.selection.kind === 'nav-item' ||
      app.selection.kind === 'nav-zone' ||
      app.selection.kind === 'nav-element' ||
      app.selection.layoutRegion === 'header_before' ||
      app.selection.layoutRegion === 'header_after';
  }

  function navbarHasCanvasContent(app) {
    return Studio.nav.ROWS.some(function (rowName) {
      return Studio.nav.ZONES.some(function (zoneName) {
        return Studio.nav.navElements(app, rowName, zoneName).length > 0;
      });
    });
  }

  function blockTargetData(target) {
    if (target && target.layoutRegion) {
      return ' data-layout-region="' + core.escapeAttr(target.layoutRegion) + '"';
    }

    return ' data-section-id="' + core.escapeAttr(target && target.sectionId ? target.sectionId : '') + '"';
  }

  function blockTargetSelectionMatches(app, target) {
    if (target && target.layoutRegion) {
      return app.selection.layoutRegion === target.layoutRegion;
    }

    return app.selection.sectionId === (target && target.sectionId ? target.sectionId : '');
  }

  function resolveBlocksForTarget(app, target) {
    if (target && target.layoutRegion) {
      const region = Studio.state.findLayoutRegion(app, target.layoutRegion);
      return region && Array.isArray(region.blocks) ? region.blocks : [];
    }

    const section = Studio.state.findSection(app, target && target.sectionId ? target.sectionId : '');
    return section && Array.isArray(section.blocks) ? section.blocks : [];
  }

  function renderLayoutBlockRegion(app, regionName, tagName) {
    const selected = isLayoutRegionSelected(app, regionName);

    return '<' + tagName + ' class="studio-layout-region studio-layout-region-' + core.escapeAttr(regionName) + ' ' + (selected ? 'is-selected' : '') + '" data-action="select-layout-region" data-layout-region="' + core.escapeAttr(regionName) + '">' +
      renderLayoutHead(app, regionName) +
      '<div class="studio-layout-surface">' +
      renderBlockZone(app, { layoutRegion: regionName }) +
      '</div>' +
      '</' + tagName + '>';
  }

  function renderContentLayout(app) {
    const asideExpanded = regionHasBlocks(app, 'aside') || isLayoutRegionSelected(app, 'aside');

    return '<div class="studio-layout-main-shell ' + (asideExpanded ? '' : 'is-main-only') + '">' +
      renderMainRegion(app) +
      (asideExpanded ? renderAsideRegion(app) : '') +
      '</div>' +
      (asideExpanded ? '' : renderAsideRegion(app));
  }

  function renderMainRegion(app) {
    const sections = app.page.sections || [];
    const selected = isLayoutRegionSelected(app, 'main');
    const content = sections.length
      ? renderSectionDrop(app, 0) +
        sections.map(function (section, index) {
          return renderSection(app, section, index) + renderSectionDrop(app, index + 1);
        }).join('')
      : renderSectionEmptyState(app);

    return '<main class="studio-layout-region studio-layout-region-main ' + (selected ? 'is-selected' : '') + '" data-action="select-layout-region" data-layout-region="main">' +
      renderLayoutHead(app, 'main') +
      '<div class="studio-layout-surface studio-layout-surface-main">' +
      content +
      '</div>' +
      '</main>';
  }

  function renderAsideRegion(app) {
    const selected = isLayoutRegionSelected(app, 'aside');

    return '<aside class="studio-layout-region studio-layout-region-aside ' + (selected ? 'is-selected' : '') + '" data-action="select-layout-region" data-layout-region="aside">' +
      renderLayoutHead(app, 'aside') +
      '<div class="studio-layout-surface">' +
      renderBlockZone(app, { layoutRegion: 'aside' }) +
      '</div>' +
      '</aside>';
  }

  function renderCanvasEmpty(message, dropMarkup, modifierClass) {
    return '<div class="studio-canvas-empty ' + core.escapeAttr(modifierClass || '') + '">' +
      (dropMarkup || '') +
      '<div class="studio-canvas-empty-copy">' + core.escapeHtml(message || '') + '</div>' +
      '</div>';
  }

  function renderSectionEmptyState(app) {
    return renderCanvasEmpty(
      app.ui.canvas.sectionDrop || '',
      '<div class="studio-canvas-empty-drop" data-drop-zone="section" data-index="0"></div>',
      'studio-canvas-empty-section'
    );
  }

  function renderNavEmptyState(app) {
    return renderCanvasEmpty(
      app.ui.canvas.navZoneDrop || '',
      '<div class="studio-canvas-empty-drop" data-drop-zone="nav-zone" data-row="main" data-zone="center" data-index="0"></div>',
      'studio-canvas-empty-nav'
    );
  }

  function renderNavRow(app, rowName, navSelected) {
    const zones = navCanvasZones(app, rowName);
    const visibleZones = zones.length > 0 ? zones : (navSelected ? Studio.nav.ZONES.slice() : []);
    const hasContent = visibleZones.length > 0;

    if (!hasContent && !navSelected && rowName !== 'main') {
      return '';
    }

    return '<div class="studio-nav-row studio-nav-row-' + core.escapeAttr(rowName) + ' is-zones-' + core.escapeAttr(String(visibleZones.length || 1)) + '">' +
      visibleZones.map(function (zoneName) {
        return renderNavZone(app, rowName, zoneName);
      }).join('') +
      '</div>';
  }

  function renderNavZone(app, rowName, zoneName) {
    const elements = navCanvasElements(app, rowName, zoneName);
    const selected = (app.selection.kind === 'nav-zone' && app.selection.navRow === rowName && app.selection.navZone === zoneName) ||
      (app.selection.kind === 'nav-element' && app.selection.navRow === rowName && app.selection.navZone === zoneName);

    return '<div class="studio-nav-zone ' + (selected ? 'is-selected' : '') + '" data-action="select-nav-zone" data-row="' + core.escapeAttr(rowName) + '" data-zone="' + core.escapeAttr(zoneName) + '">' +
      renderNavZoneDrop(rowName, zoneName, 0) +
      elements.map(function (element, index) {
        return renderNavElement(app, rowName, zoneName, element, index) + renderNavZoneDrop(rowName, zoneName, index + 1);
      }).join('') +
      (elements.length === 0 ? '<div class="studio-nav-zone-empty">' + core.escapeHtml(app.ui.canvas.navZoneDrop || '') + '</div>' : '') +
      '</div>';
  }

  function renderNavZoneDrop(rowName, zoneName, index) {
    return '<div class="studio-nav-zone-drop" data-drop-zone="nav-zone" data-row="' + core.escapeAttr(rowName) + '" data-zone="' + core.escapeAttr(zoneName) + '" data-index="' + core.escapeAttr(String(index)) + '"></div>';
  }

  function renderNavElement(app, rowName, zoneName, element, index) {
    const selected = app.selection.kind === 'nav-element' && app.selection.navElementId === element.id;
    let body = '';

    if (element.kind === 'menu') {
      body = renderMenuElement(app);
    } else if (element.kind === 'brand') {
      body = renderNavBrandElement(app, element, rowName, zoneName);
    } else if (element.kind === 'button') {
      body = '<span class="studio-nav-button-chip">' + core.escapeHtml(element.label || '') + '</span>';
    } else if (element.kind === 'language' || element.kind === 'cart' || element.kind === 'account') {
      body = '<span class="studio-nav-utility-chip">' + core.escapeHtml(element.label || '') + '</span>';
    } else {
      body = '<span class="studio-nav-inline-text">' + core.escapeHtml(element.text || '') + '</span>';
    }

    return '<div class="studio-nav-element studio-nav-element-' + core.escapeAttr(element.kind || 'text') + ' ' + (selected ? 'is-selected' : '') + '" data-action="select-nav-element" data-row="' + core.escapeAttr(rowName) + '" data-zone="' + core.escapeAttr(zoneName) + '" data-element-id="' + core.escapeAttr(element.id || '') + '" draggable="true" data-drag-kind="existing-nav-element" data-drag-index="' + core.escapeAttr(String(index)) + '">' +
      body +
      '</div>';
  }

  function renderMenuElement(app) {
    const navbar = app.page.navbar || { items: [] };
    const items = navbar.items || [];
    return '<div class="studio-canvas-nav-items studio-canvas-nav-menu">' +
      renderNavSlot(0) +
      items.map(function (item, index) {
        return renderNavItem(app, item, index) + renderNavSlot(index + 1);
      }).join('') +
      '</div>';
  }

  function renderNavBrandElement(app, element, rowName, zoneName) {
    const label = element.label || (app.page.navbar.brand && app.page.navbar.brand.label) || '';
    const subtitle = resolveBrandSubtitle(app, element, rowName, zoneName);
    const alt = element.alt || label || '';
    const src = resolveMediaSource(app, element.src || '');
    const variant = resolveBrandVariant(app, element);
    const hasText = String(label).trim() !== '' || String(subtitle).trim() !== '';

    return '<span class="studio-nav-brand studio-nav-brand--' + core.escapeAttr(variant) + ' ' + (hasText ? '' : 'studio-nav-brand--logo-only') + '">' +
      (src
        ? '<img class="studio-nav-brand-media studio-nav-brand-media--' + core.escapeAttr(variant) + '" src="' + core.escapeAttr(src) + '" alt="' + core.escapeAttr(alt) + '">'
        : '<span class="studio-nav-brand-fallback studio-nav-brand-fallback--' + core.escapeAttr(variant) + '" aria-hidden="true">' + core.escapeHtml(BRAND_FALLBACK_GLYPH) + '</span>') +
      (hasText
        ? '<span class="studio-nav-brand-copy">' +
          (label ? '<strong class="studio-nav-brand-text">' + core.escapeHtml(label) + '</strong>' : '') +
          (subtitle ? '<span class="studio-nav-brand-subtitle">' + core.escapeHtml(subtitle) + '</span>' : '') +
          '</span>'
        : '') +
      '</span>';
  }

  function navCanvasZones(app, rowName) {
    return Studio.nav.ZONES.filter(function (zoneName) {
      const elements = navCanvasElements(app, rowName, zoneName);
      const selectedZone = app.selection.kind === 'nav-zone' && app.selection.navRow === rowName && app.selection.navZone === zoneName;
      const selectedElement = app.selection.kind === 'nav-element' && app.selection.navRow === rowName && app.selection.navZone === zoneName;
      return elements.length > 0 || selectedZone || selectedElement;
    });
  }

  function navCanvasElements(app, rowName, zoneName) {
    const elements = Studio.nav.navElements(app, rowName, zoneName);
    if (rowName === 'main' && zoneName === 'right' && resolveBorrowedMainSlogan(app) !== '') {
      return [];
    }

    return elements;
  }

  function resolveBorrowedMainSlogan(app) {
    const rows = app.page && app.page.navbar && app.page.navbar.rows ? app.page.navbar.rows : {};
    const mainRow = rows.main || {};
    const left = Array.isArray(mainRow.left) ? mainRow.left : [];
    const center = Array.isArray(mainRow.center) ? mainRow.center : [];
    const right = Array.isArray(mainRow.right) ? mainRow.right : [];
    const brand = left.length === 1 && left[0] && left[0].kind === 'brand' ? left[0] : null;
    const menuPresent = center.some(function (item) {
      return item && item.kind === 'menu';
    });

    if (!brand || !menuPresent || String(brand.subtitle || '').trim() !== '') {
      return '';
    }

    if (right.length !== 1 || !right[0] || right[0].kind !== 'slogan') {
      return '';
    }

    return String(right[0].text || '').trim();
  }

  function resolveBrandSubtitle(app, element, rowName, zoneName) {
    const direct = String(element.subtitle || '').trim();
    if (direct !== '') {
      return direct;
    }

    const navbarSubtitle = app.page && app.page.navbar && app.page.navbar.brand
      ? String(app.page.navbar.brand.subtitle || '').trim()
      : '';
    if (navbarSubtitle !== '') {
      return navbarSubtitle;
    }

    if (rowName === 'main' && zoneName === 'left') {
      return resolveBorrowedMainSlogan(app);
    }

    return '';
  }

  function resolveBrandVariant(app, element) {
    const elementVariant = element ? String(element.variant || '').trim() : '';
    if (elementVariant === 'compact' || elementVariant === 'banner' || elementVariant === 'banner_framed') {
      return elementVariant;
    }

    const navbarVariant = app.page && app.page.navbar && app.page.navbar.brand
      ? String(app.page.navbar.brand.variant || '').trim()
      : '';

    if (navbarVariant === 'compact' || navbarVariant === 'banner' || navbarVariant === 'banner_framed') {
      return navbarVariant;
    }

    return 'compact';
  }

  function renderNavSlot(index) {
    return '<div class="studio-nav-slot" data-drop-zone="nav-item" data-index="' + core.escapeAttr(String(index)) + '"></div>';
  }

  function renderNavItem(app, item, index) {
    const selected = app.selection.kind === 'nav-item' && app.selection.navId === item.id;
    return '<button type="button" class="studio-canvas-nav-item ' + (selected ? 'is-selected' : '') + '" data-action="select-nav-item" data-index="' + core.escapeAttr(String(index)) + '" data-nav-id="' + core.escapeAttr(item.id || '') + '" draggable="true" data-drag-kind="nav-item" data-drag-index="' + core.escapeAttr(String(index)) + '">' +
      core.escapeHtml(item.label || '') +
      '</button>';
  }

  function renderMegaPreview(app, item, navIndex) {
    const columnsDesktop = Studio.nav.megaColumnsDesktop(app);
    return '<div class="studio-mega-preview">' +
      '<div class="studio-mega-preview-head"><strong>' + core.escapeHtml((app.ui.canvas.megaPreviewPrefix || '') + ' ' + (item.label || '')) + '</strong></div>' +
      '<div class="studio-mega-columns is-cols-' + core.escapeAttr(String(columnsDesktop)) + '">' +
      Array.from({ length: columnsDesktop }).map(function (_, slotIndex) {
        return renderMegaSlot(app, item, navIndex, slotIndex);
      }).join('') +
      '</div>' +
      '</div>';
  }

  function renderMegaSlot(app, item, navIndex, slotIndex) {
    const column = Studio.nav.megaColumnAtSlot(app, navIndex, slotIndex);
    const columnIndex = column ? Studio.nav.megaColumnIndex(app, navIndex, column.id) : -1;

    return '<div class="studio-mega-slot is-slot-' + core.escapeAttr(String(slotIndex + 1)) + ' ' + (column ? 'is-occupied' : 'is-empty') + '" data-drop-zone="mega-slot" data-nav-index="' + core.escapeAttr(String(navIndex)) + '" data-slot-index="' + core.escapeAttr(String(slotIndex)) + '">' +
      (column ? renderMegaColumn(app, navIndex, column, columnIndex, slotIndex) : '<div class="studio-mega-slot-empty">' + core.escapeHtml(app.ui.canvas.megaColumnDrop || '') + '</div>') +
      '</div>';
  }

  function renderMegaColumn(app, navIndex, column, columnIndex, slotIndex) {
    const elements = column.elements || [];
    return '<div class="studio-mega-column" draggable="true" data-drag-kind="existing-mega-column" data-nav-index="' + core.escapeAttr(String(navIndex)) + '" data-column-index="' + core.escapeAttr(String(columnIndex)) + '" data-slot-index="' + core.escapeAttr(String(slotIndex)) + '">' +
      '<span class="studio-mega-slot-badge">' + core.escapeHtml(String(slotIndex + 1)) + '</span>' +
      '<strong>' + core.escapeHtml(column.title || '') + '</strong>' +
      renderMegaElementSlot(navIndex, columnIndex, 0) +
      elements.map(function (element, elementIndex) {
        return renderMegaElement(app, navIndex, columnIndex, element, elementIndex) + renderMegaElementSlot(navIndex, columnIndex, elementIndex + 1);
      }).join('') +
      (elements.length === 0 ? '<div class="studio-empty-note">' + core.escapeHtml(app.ui.canvas.megaElementDrop || '') + '</div>' : '') +
      '</div>';
  }

  function renderMegaElementSlot(navIndex, columnIndex, elementIndex) {
    return '<div class="studio-mega-element-drop" data-drop-zone="mega-element" data-nav-index="' + core.escapeAttr(String(navIndex)) + '" data-column-index="' + core.escapeAttr(String(columnIndex)) + '" data-element-index="' + core.escapeAttr(String(elementIndex)) + '"></div>';
  }

  function renderMegaElement(app, navIndex, columnIndex, element, elementIndex) {
    const kind = element.kind || 'link';
    let body = '';
    if (kind === 'text') {
      body = '<strong>' + core.escapeHtml(element.title || '') + '</strong><p>' + core.escapeHtml(element.text || '') + '</p>';
    } else {
      body = '<strong>' + core.escapeHtml(element.label || '') + '</strong>';
    }

    return '<div class="studio-mega-element" draggable="true" data-drag-kind="existing-mega-element" data-nav-index="' + core.escapeAttr(String(navIndex)) + '" data-column-index="' + core.escapeAttr(String(columnIndex)) + '" data-element-index="' + core.escapeAttr(String(elementIndex)) + '">' + body + '</div>';
  }

  function renderSectionDrop(app, index) {
    return '<div class="studio-page-drop" data-drop-zone="section" data-index="' + core.escapeAttr(String(index)) + '">' + core.escapeHtml(app.ui.canvas.sectionDrop || '') + '</div>';
  }

  function renderSection(app, section, index) {
    const selected = app.selection.kind === 'section' && app.selection.sectionId === section.id;
    const total = app.page.sections.length;
    const preview = renderSectionPreview(app, section);
    return '<section class="studio-canvas-section ' + (selected ? 'is-selected' : '') + '" data-action="select-section" data-section-id="' + core.escapeAttr(section.id) + '" draggable="true" data-drag-kind="existing-section" data-drag-index="' + core.escapeAttr(String(index)) + '">' +
      '<div class="studio-section-frame studio-section-frame--' + core.escapeAttr(section.type || 'default') + '">' +
      '<div class="studio-section-toolbar">' +
      '<span>' + core.escapeHtml(section.label || section.type || '') + '</span>' +
      '<div>' +
      '<button type="button" data-action="move-section" data-id="' + core.escapeAttr(section.id) + '" data-direction="up" ' + (index === 0 ? 'disabled' : '') + '>' + core.escapeHtml(app.ui.actions.moveUp || '') + '</button>' +
      '<button type="button" data-action="move-section" data-id="' + core.escapeAttr(section.id) + '" data-direction="down" ' + (index === total - 1 ? 'disabled' : '') + '>' + core.escapeHtml(app.ui.actions.moveDown || '') + '</button>' +
      '<button type="button" data-action="duplicate-section" data-id="' + core.escapeAttr(section.id) + '">' + core.escapeHtml(app.ui.actions.duplicate || '') + '</button>' +
      '<button type="button" data-action="delete-section" data-id="' + core.escapeAttr(section.id) + '">' + core.escapeHtml(app.ui.actions.delete || '') + '</button>' +
      '</div>' +
      '</div>' +
      (preview ? '<div class="studio-section-preview studio-section-' + core.escapeAttr(section.type || 'hero') + '">' + preview + '</div>' : '') +
      renderBlockZone(app, section) +
      '</div>' +
      '</section>';
  }

  function renderSectionPreview(app, section) {
    const settings = section.settings || {};
    const items = Array.isArray(section.items) ? section.items : [];
    const blocks = Array.isArray(section.blocks) ? section.blocks : [];

    if (section.type === 'content') {
      if (blocks.length > 0) {
        return '';
      }

      const previewHtml = sanitizeContentPreviewHtml(settings.html || '');
      if (previewHtml) {
        return '<div class="studio-content-preview">' + previewHtml + '</div>';
      }

      return '<div class="studio-content-preview"><p>' + core.escapeHtml(app.ui.inspector.empty || '') + '</p></div>';
    }

    if (section.type === 'hero') {
      return '<div class="studio-hero-preview"><small>' + core.escapeHtml(settings.eyebrow || '') + '</small><h1>' + core.escapeHtml(settings.title || '') + '</h1><p>' + core.escapeHtml(settings.text || '') + '</p>' + (settings.button_label ? '<a>' + core.escapeHtml(settings.button_label) + '</a>' : '') + '</div>';
    }

    if (section.type === 'services' || section.type === 'blog') {
      return '<div class="studio-heading-preview"><small>' + core.escapeHtml(settings.eyebrow || '') + '</small><h2>' + core.escapeHtml(settings.title || '') + '</h2></div><div class="studio-card-grid">' +
        items.map(function (item) {
          return '<article><strong>' + core.escapeHtml(item.title || '') + '</strong><p>' + core.escapeHtml(item.text || '') + '</p></article>';
        }).join('') +
        '</div>';
    }

    if (section.type === 'split') {
      return '<div class="studio-split-preview"><div><small>' + core.escapeHtml(settings.eyebrow || '') + '</small><h2>' + core.escapeHtml(settings.title || '') + '</h2><p>' + core.escapeHtml(settings.text || '') + '</p></div><div class="studio-fake-media">' + core.escapeHtml(app.ui.canvas.fakeMedia || '') + '</div></div>';
    }

    if (section.type === 'stats') {
      return '<div class="studio-stats-preview">' +
        items.map(function (item) {
          return '<div><strong>' + core.escapeHtml(item.value || '') + '</strong><span>' + core.escapeHtml(item.label || '') + '</span></div>';
        }).join('') +
        '</div>';
    }

    if (section.type === 'testimonial') {
      return '<blockquote class="studio-testimonial-preview">“' + core.escapeHtml(settings.quote || '') + '”<cite>' + core.escapeHtml(settings.author || '') + '</cite></blockquote>';
    }

    if (section.type === 'faq') {
      return '<div class="studio-heading-preview"><small>' + core.escapeHtml(settings.eyebrow || '') + '</small><h2>' + core.escapeHtml(settings.title || '') + '</h2></div>' +
        items.map(function (item) {
          return '<details class="studio-faq-preview"><summary>' + core.escapeHtml(item.question || '') + '</summary><p>' + core.escapeHtml(item.answer || '') + '</p></details>';
        }).join('');
    }

    return '<div class="studio-cta-preview"><h2>' + core.escapeHtml(settings.title || '') + '</h2><p>' + core.escapeHtml(settings.text || '') + '</p>' + (settings.button_label ? '<a>' + core.escapeHtml(settings.button_label) + '</a>' : '') + '</div>';
  }

  function sanitizeContentPreviewHtml(html) {
    const raw = String(html || '').trim();
    if (!raw) {
      return '';
    }

    const documentPreview = document.implementation.createHTMLDocument('');
    documentPreview.body.innerHTML = raw;

    documentPreview.body.querySelectorAll('script, style, iframe, object, embed').forEach(function (node) {
      node.remove();
    });

    documentPreview.body.querySelectorAll('*').forEach(function (node) {
      Array.from(node.attributes).forEach(function (attribute) {
        const name = String(attribute.name || '').toLowerCase();
        const value = String(attribute.value || '');

        if (name.indexOf('on') === 0) {
          node.removeAttribute(attribute.name);
          return;
        }

        if ((name === 'href' || name === 'src') && /^\s*javascript:/i.test(value)) {
          node.removeAttribute(attribute.name);
        }
      });
    });

    return documentPreview.body.innerHTML;
  }

  function renderPlainTextPreviewHtml(value) {
    const normalized = String(value || '').replace(/\r\n?/g, '\n').trim();
    if (!normalized) {
      return '';
    }

    const lines = normalized.split('\n');
    const parts = [];
    let paragraph = [];
    let listItems = [];
    let listTag = '';

    function flushParagraph() {
      if (!paragraph.length) {
        return;
      }

      parts.push('<p>' + paragraph.map(function (line) {
        return core.escapeHtml(line);
      }).join('<br>') + '</p>');
      paragraph = [];
    }

    function flushList() {
      if (!listItems.length || !listTag) {
        listItems = [];
        listTag = '';
        return;
      }

      parts.push('<' + listTag + '>' + listItems.join('') + '</' + listTag + '>');
      listItems = [];
      listTag = '';
    }

    lines.forEach(function (line) {
      const trimmed = String(line || '').trim();
      if (!trimmed) {
        flushParagraph();
        flushList();
        return;
      }

      const match = trimmed.match(/^(?:([*\-•])|(\d+)\.)\s+(.+)$/);
      if (match) {
        flushParagraph();
        const nextTag = match[2] ? 'ol' : 'ul';
        if (listTag && listTag !== nextTag) {
          flushList();
        }
        listTag = nextTag;
        listItems.push('<li>' + core.escapeHtml(match[3] || '') + '</li>');
        return;
      }

      flushList();
      paragraph.push(trimmed);
    });

    flushParagraph();
    flushList();

    return parts.join('');
  }

  function resolveMediaSource(app, rawValue) {
    const src = String(rawValue || '').trim();
    if (!src) {
      return '';
    }

    if (/^(https?:|data:|blob:)/i.test(src)) {
      return src;
    }

    const mediaConfig = app.config && app.config.media && typeof app.config.media === 'object' ? app.config.media : {};
    const uploadsBase = String(mediaConfig.uploadsBase || '/uploads').replace(/\/$/, '');

    if (src.startsWith('/public/uploads/')) {
      return uploadsBase + '/' + src.replace(/^\/public\/uploads\/?/, '');
    }

    if (src.startsWith('/uploads/')) {
      return uploadsBase + '/' + src.replace(/^\/uploads\/?/, '');
    }

    if (src.startsWith('uploads/')) {
      return uploadsBase + '/' + src.replace(/^uploads\/?/, '');
    }

    if (src.startsWith('/')) {
      return src;
    }

    return uploadsBase + '/' + src.replace(/^\//, '');
  }

  function closeMediaModal() {
    const modal = document.getElementById('mediaModal');
    if (!modal) {
      return;
    }

    modal.classList.add('hidden');
    modal.style.display = 'none';
  }

  let mediaRuntimePromise = null;

  function mediaScriptUrl(app) {
    const mediaConfig = app.config && app.config.media && typeof app.config.media === 'object' ? app.config.media : {};
    const configured = String(mediaConfig.scriptUrl || '').trim();
    if (configured) {
      return configured;
    }

    const current = document.querySelector('script[src*="/modules/media/js/media-modal.js"]');
    return current ? String(current.getAttribute('src') || '').trim() : '';
  }

  function ensureMediaPickerRuntime(app) {
    if (typeof window.initMediaModal === 'function') {
      return Promise.resolve(true);
    }

    if (mediaRuntimePromise) {
      return mediaRuntimePromise;
    }

    const scriptUrl = mediaScriptUrl(app);
    if (!scriptUrl) {
      return Promise.resolve(false);
    }

    mediaRuntimePromise = new Promise(function (resolve) {
      const script = document.createElement('script');
      script.src = scriptUrl;
      script.async = true;
      script.onload = function () {
        resolve(typeof window.initMediaModal === 'function');
      };
      script.onerror = function () {
        resolve(false);
      };
      document.head.appendChild(script);
    }).finally(function () {
      mediaRuntimePromise = null;
    });

    return mediaRuntimePromise;
  }

  function canUseMediaPicker(app) {
    const mediaConfig = app.config && app.config.media && typeof app.config.media === 'object' ? app.config.media : null;
    return !!(mediaConfig && mediaConfig.uploadUrl && document.getElementById('mediaModal'));
  }

  function openMediaPicker(app, onSelect) {
    if (!canUseMediaPicker(app)) {
      window.alert((app.ui.media && app.ui.media.unavailable) || '');
      return;
    }

    const modal = document.getElementById('mediaModal');
    const mediaConfig = app.config.media || {};

    ensureMediaPickerRuntime(app).then(function (ready) {
      if (!ready || typeof window.initMediaModal !== 'function') {
        window.alert((app.ui.media && app.ui.media.unavailable) || '');
        return;
      }

      modal.classList.remove('hidden');
      modal.style.display = 'flex';

      window.initMediaModal({
        apiImagesUrl: mediaConfig.apiImagesUrl || '',
        apiFilesUrl: mediaConfig.apiFilesUrl || '',
        uploadUrl: mediaConfig.uploadUrl || '',
        uploadsBase: mediaConfig.uploadsBase || '/uploads',
        csrfToken: mediaConfig.csrfToken || app.config.token || '',
        mode: 'images',
        folder: 'images',
        accept: 'image/*',
        openUploadIfEmpty: true,
        initialTab: 'library',
        onSelect: function (file) {
          if (typeof onSelect === 'function') {
            onSelect(file);
          }
          closeMediaModal();
        }
      });

      if (window.FlatCMS && window.FlatCMS.mediaModal && typeof window.FlatCMS.mediaModal.open === 'function') {
        window.FlatCMS.mediaModal.open();
      }
    });
  }

  function renderButtonBlockGroup(app, target, entries) {
    if (!entries.length) {
      return '';
    }

    const startIndex = entries[0].index;
    const parts = [renderBlockDrop(target, startIndex, 'studio-block-drop-inline')];

    entries.forEach(function (entry) {
      parts.push(renderBlock(app, target, entry.block));
      parts.push(renderBlockDrop(target, entry.index + 1, 'studio-block-drop-inline'));
    });

    return '<div class="studio-block-actions">' + parts.join('') + '</div>';
  }

  function blockZoneMode(target) {
    if (target && target.layoutRegion) {
      if (target.layoutRegion === 'header_before' || target.layoutRegion === 'header_after') {
        return 'inline';
      }
      if (target.layoutRegion === 'footer') {
        return 'footer';
      }

      return 'layout';
    }

    return 'section';
  }

  function renderBlockZone(app, section) {
    const target = section && section.layoutRegion
      ? { layoutRegion: section.layoutRegion }
      : { sectionId: section.id };
    const blocks = resolveBlocksForTarget(app, target);
    const mode = blockZoneMode(target);

    if (blocks.length === 0) {
      return '<div class="studio-block-zone studio-block-zone--' + core.escapeAttr(mode) + ' is-empty"' + blockTargetData(target) + '>' +
        renderCanvasEmpty(
          app.ui.canvas.blockDrop || '',
          renderBlockDrop(target, 0).replace('class="studio-block-drop"', 'class="studio-canvas-empty-drop studio-block-drop"'),
          'studio-canvas-empty-block studio-canvas-empty-block--' + core.escapeAttr(mode)
        ) +
        '</div>';
    }

    if (mode === 'inline') {
      const inlineParts = [renderBlockDrop(target, 0, 'studio-block-drop-inline')];
      blocks.forEach(function (block, index) {
        inlineParts.push(renderBlock(app, target, block));
        inlineParts.push(renderBlockDrop(target, index + 1, 'studio-block-drop-inline'));
      });

      return '<div class="studio-block-zone studio-block-zone--filled studio-block-zone--inline"' + blockTargetData(target) + '>' + inlineParts.join('') + '</div>';
    }

    if (mode === 'footer') {
      const footerParts = [renderBlockDrop(target, 0, 'studio-block-drop-layout')];
      let footerIndex = 0;

      while (footerIndex < blocks.length) {
        if (isFooterBrandPair(blocks, footerIndex)) {
          footerParts.push(renderFooterBrandGroup(app, target, blocks[footerIndex], blocks[footerIndex + 1]));
          footerIndex += 2;
          footerParts.push(renderBlockDrop(target, footerIndex, 'studio-block-drop-layout'));
          continue;
        }

        footerParts.push(renderBlock(app, target, blocks[footerIndex]));
        footerParts.push(renderBlockDrop(target, footerIndex + 1, 'studio-block-drop-layout'));
        footerIndex += 1;
      }

      return '<div class="studio-block-zone studio-block-zone--filled studio-block-zone--footer"' + blockTargetData(target) + '>' + footerParts.join('') + '</div>';
    }

    const parts = [renderBlockDrop(target, 0, mode === 'layout' ? 'studio-block-drop-layout' : '')];
    let index = 0;

    while (index < blocks.length) {
      if (blocks[index].type === 'button') {
        const buttonBlocks = [];
        while (index < blocks.length && blocks[index].type === 'button') {
          buttonBlocks.push({ block: blocks[index], index: index });
          index += 1;
        }

        parts.pop();
        parts.push(renderButtonBlockGroup(app, target, buttonBlocks));
        continue;
      }

      parts.push(renderBlock(app, target, blocks[index]));
      parts.push(renderBlockDrop(target, index + 1, mode === 'layout' ? 'studio-block-drop-layout' : ''));
      index += 1;
    }

    return '<div class="studio-block-zone studio-block-zone--filled studio-block-zone--' + core.escapeAttr(mode) + '"' + blockTargetData(target) + '>' + parts.join('') + '</div>';
  }

  function isFooterBrandPair(blocks, index) {
    return index === 0 &&
      blocks[index] &&
      blocks[index + 1] &&
      blocks[index].type === 'image' &&
      blocks[index + 1].type === 'text' &&
      String(blocks[index].id || '') === MANAGED_FOOTER_LOGO_ID &&
      String(blocks[index + 1].id || '') === MANAGED_FOOTER_BRAND_ID;
  }

  function renderFooterBrandGroup(app, target, imageBlock, textBlock) {
    const variant = resolveBrandVariant(app);
    const hasText = Boolean(textBlock && textBlock.settings && String(textBlock.settings.text || '').trim() !== '');

    return '<div class="studio-footer-brand-group studio-footer-brand-group--' + core.escapeAttr(variant) + ' ' + (hasText ? '' : 'studio-footer-brand-group--logo-only') + '">' +
      renderBlock(app, target, imageBlock) +
      renderBlock(app, target, textBlock) +
      '</div>';
  }

  function renderBlockDrop(target, index, modifierClass) {
    const classes = ['studio-block-drop'];
    if (modifierClass) {
      classes.push(modifierClass);
    }

    return '<div class="' + classes.join(' ') + '" data-drop-zone="block"' + blockTargetData(target) + ' data-index="' + core.escapeAttr(String(index)) + '"></div>';
  }

  function renderBlock(app, target, block) {
    const selected = app.selection.kind === 'block' && app.selection.blockId === block.id && blockTargetSelectionMatches(app, target);
    const settings = block.settings || {};
    const items = Array.isArray(block.items) ? block.items : [];
    const imageSrc = block.type === 'image' ? resolveMediaSource(app, settings.src || '') : '';
    const imageHeight = block.type === 'image' ? normalizeImageHeightPreset(settings.height || 'auto') : 'auto';
    let body = '';

    if (block.type === 'heading') {
      body = '<h3>' + core.escapeHtml(settings.text || '') + '</h3>';
    } else if (block.type === 'text') {
      body = '<div class="studio-block-copy">' + renderPlainTextPreviewHtml(settings.text || '') + '</div>';
    } else if (block.type === 'button') {
      body = '<a class="studio-block-button">' + core.escapeHtml(settings.text || '') + '</a>';
    } else if (block.type === 'image') {
      body = imageSrc
        ? '<figure class="studio-block-image" data-image-height="' + core.escapeAttr(imageHeight) + '"><img src="' + core.escapeAttr(imageSrc) + '" alt="' + core.escapeAttr(settings.alt || '') + '"></figure>'
        : (
          String(block.id || '') === MANAGED_FOOTER_LOGO_ID
            ? '<figure class="studio-block-image studio-block-image--brand-fallback" data-image-height="' + core.escapeAttr(imageHeight) + '"><span class="studio-block-image-fallback" aria-hidden="true">' + core.escapeHtml(BRAND_FALLBACK_GLYPH) + '</span></figure>'
            : '<div class="studio-block-image is-empty" data-image-height="' + core.escapeAttr(imageHeight) + '">' + core.escapeHtml(app.ui.canvas.fakeMedia || '') + '</div>'
        );
    } else if (block.type === 'cards') {
      body = '<div class="studio-block-cards">' + items.map(function (item) {
        return '<article><strong>' + core.escapeHtml(item.title || '') + '</strong><div class="studio-block-copy">' + renderPlainTextPreviewHtml(item.text || '') + '</div></article>';
      }).join('') + '</div>';
    } else if (block.type === 'form') {
      body = '<div class="studio-block-placeholder">@ ' + core.escapeHtml(settings.text || '') + '</div>';
    } else if (block.type === 'map') {
      body = '<div class="studio-block-placeholder">⌖ ' + core.escapeHtml(settings.address || '') + '</div>';
    } else if (block.type === 'plugin') {
      body = '<div class="studio-block-placeholder">⚙ ' + core.escapeHtml(settings.plugin || '') + '</div>';
    } else {
      body = '<div class="studio-block-spacer">' + core.escapeHtml(app.ui.canvas.spacer || '') + '</div>';
    }

    const classes = ['studio-block'];
    if (selected) {
      classes.push('is-selected');
    }
    if (block.type) {
      classes.push('studio-block--' + block.type);
    }
    if (block.type === 'button') {
      classes.push('studio-block--button');
    }

    return '<div class="' + classes.join(' ') + '" data-action="select-block"' + blockTargetData(target) + ' data-block-id="' + core.escapeAttr(block.id) + '" draggable="true" data-drag-kind="existing-block"' + blockTargetData(target).replace(' data-', ' data-drag-') + ' data-drag-block-id="' + core.escapeAttr(block.id) + '">' +
      '<div class="studio-block-label">' + core.escapeHtml(block.label || '') + '</div>' + body +
      '</div>';
  }

  function enhanceInspectorMediaFields(app) {
    if (!canUseMediaPicker(app)) {
      return;
    }

    const primitives = window.FlatCMSUIPrimitives || {};
    if (typeof primitives.createBuilderMediaFieldControls !== 'function' || !app.cache || !app.cache.inspectorContent) {
      return;
    }

    app.cache.inspectorContent.querySelectorAll('[data-media-bind]').forEach(function (host) {
      const bind = String(host.getAttribute('data-media-bind') || '').trim();
      if (!bind) {
        return;
      }

      const field = host.closest('.studio-field-media');
      const valueInput = field ? field.querySelector('.studio-media-source[data-bind]') : null;
      const currentValue = valueInput ? String(valueInput.value || '') : String(host.getAttribute('data-media-value') || '');
      const mediaField = primitives.createBuilderMediaFieldControls({
        value: currentValue,
        disabled: false,
        previewEnabled: true,
        mediaOptions: { mode: 'images', preview: 'image' },
        resolveSrc: function (nextValue) {
          return resolveMediaSource(app, nextValue);
        },
        noMediaLabel: (app.ui.media && app.ui.media.noMedia) || '',
        pickButtonClass: 'studio-btn',
        clearButtonClass: 'studio-btn',
        pickButtonText: (app.ui.media && app.ui.media.chooseImage) || '',
        clearButtonHtml: core.escapeHtml((app.ui.media && app.ui.media.removeMedia) || '')
      });

      mediaField.pickButton.addEventListener('click', function () {
        openMediaPicker(app, function (file) {
          const nextValue = String((file && (file.path || file.url)) || '').trim();
          if (!nextValue) {
            return;
          }

          if (Studio.state.applyBinding(app, bind, nextValue)) {
            app.onMutation();
          }
        });
      });

      mediaField.clearButton.addEventListener('click', function () {
        if (Studio.state.applyBinding(app, bind, '')) {
          app.onMutation();
        }
      });

      host.innerHTML = '';
      host.appendChild(mediaField.element);
      if (field) {
        field.classList.add('is-enhanced');
      }
    });
  }

  function renderInspector(app) {
    const selection = app.selection;
    const inspector = app.cache.inspector;
    if (!selection.kind || !app.inspectorOpen) {
      inspector.classList.remove('is-open');
      app.cache.inspectorTitle.textContent = '';
      app.cache.inspectorSubtitle.textContent = app.ui.inspector.empty || '';
      app.cache.inspectorContent.innerHTML = '';
      syncShell(app);
      return;
    }

    inspector.classList.add('is-open');

    if (selection.kind === 'page') {
      app.cache.inspectorTitle.textContent = app.page.page && app.page.page.title ? app.page.page.title : (app.ui.inspector.pageTitle || '');
      app.cache.inspectorSubtitle.textContent = app.ui.drawers && app.ui.drawers.page ? app.ui.drawers.page.subtitle || '' : '';
      app.cache.inspectorContent.innerHTML = renderPagePanel(app);
    } else if (selection.kind === 'nav') {
      app.cache.inspectorTitle.textContent = app.ui.inspector.navTitle || '';
      app.cache.inspectorSubtitle.textContent = app.ui.inspector.navSubtitle || '';
      app.cache.inspectorContent.innerHTML = renderNavInspector(app);
    } else if (selection.kind === 'nav-zone') {
      app.cache.inspectorTitle.textContent = (app.ui.inspector.navLayoutTitle || '') + ' · ' + ((app.ui.inspector['navRow' + capitalize(selection.navRow)] || selection.navRow || '') + ' / ' + (app.ui.inspector['navZone' + capitalize(selection.navZone)] || selection.navZone || ''));
      app.cache.inspectorSubtitle.textContent = app.ui.inspector.navSubtitle || '';
      app.cache.inspectorContent.innerHTML = renderNavZoneInspector(app, selection.navRow, selection.navZone);
    } else if (selection.kind === 'nav-item') {
      const item = Studio.nav.navItem(app, selection.navId || selection.navIndex);
      app.cache.inspectorTitle.textContent = (app.ui.inspector.navItemTitlePrefix || '') + ' ' + (item && item.label ? item.label : '');
      app.cache.inspectorSubtitle.textContent = app.ui.inspector.navItemSubtitle || '';
      app.cache.inspectorContent.innerHTML = renderNavItemInspector(app, selection.navId || selection.navIndex);
    } else if (selection.kind === 'nav-element') {
      const element = Studio.nav.navElement(app, selection.navElementId || '');
      const meta = element && app.library.navbarElements ? app.library.navbarElements[element.kind] : null;
      app.cache.inspectorTitle.textContent = (app.ui.inspector.navElementTitlePrefix || '') + ' ' + (meta && meta.name ? meta.name : '');
      app.cache.inspectorSubtitle.textContent = app.ui.inspector.navElementSubtitle || '';
      app.cache.inspectorContent.innerHTML = element ? renderNavElementInspector(app, element) : renderEmptyNote(app.ui.inspector.empty || '');
    } else if (selection.kind === 'layout-region') {
      const meta = layoutMeta(app, selection.layoutRegion);
      app.cache.inspectorTitle.textContent = meta.title || '';
      app.cache.inspectorSubtitle.textContent = app.ui.inspector.layoutRegionSubtitle || '';
      app.cache.inspectorContent.innerHTML = renderLayoutRegionInspector(app, selection.layoutRegion);
    } else if (selection.kind === 'section') {
      const section = Studio.state.findSection(app, selection.sectionId);
      app.cache.inspectorTitle.textContent = section ? section.label || section.type || '' : '';
      app.cache.inspectorSubtitle.textContent = app.ui.inspector.sectionSubtitle || '';
      app.cache.inspectorContent.innerHTML = section ? renderSectionInspector(app, section) : '';
    } else if (selection.kind === 'block') {
      const target = selection.layoutRegion
        ? { layoutRegion: selection.layoutRegion }
        : { sectionId: selection.sectionId };
      const block = Studio.state.findBlock(app, target, selection.blockId);
      app.cache.inspectorTitle.textContent = block ? block.label || block.type || '' : '';
      app.cache.inspectorSubtitle.textContent = app.ui.inspector.blockSubtitle || '';
      app.cache.inspectorContent.innerHTML = block ? renderBlockInspector(app, target, block) : '';
    }

    enhanceInspectorMediaFields(app);
    syncShell(app);
  }

  function renderLayoutRegionInspector(app, regionName) {
    const meta = layoutMeta(app, regionName);
    const count = regionName === 'main'
      ? String((app.page.sections || []).length)
      : String(resolveBlocksForTarget(app, { layoutRegion: regionName }).length);
    const countLabel = regionName === 'main'
      ? (app.ui.inspector.itemsLabel || '')
      : (app.ui.inspector.blocksLabel || '');
    const note = regionName === 'main'
      ? app.ui.inspector.layoutMainHint || ''
      : app.ui.inspector.layoutBlockHint || '';

    return '<div class="studio-inspector-stack">' +
      renderInspectorCard(
        app.ui.inspector.summaryTitle || '',
        note,
        [
          { label: app.ui.inspector.typeLabel || '', value: meta.tag || regionName },
          { label: countLabel, value: count }
        ]
      ) +
      '</div>';
  }

  function renderInspectorCard(title, text, facts) {
    const items = (facts || []).filter(function (fact) {
      return fact && fact.label && fact.value !== undefined && fact.value !== null && String(fact.value).trim() !== '';
    });

    return '<section class="studio-inspector-card">' +
      (title ? '<strong>' + core.escapeHtml(title) + '</strong>' : '') +
      (text ? '<p>' + core.escapeHtml(text) + '</p>' : '') +
      (items.length ? '<dl class="studio-inspector-facts">' + items.map(function (fact) {
        return '<div><dt>' + core.escapeHtml(fact.label) + '</dt><dd>' + core.escapeHtml(String(fact.value)) + '</dd></div>';
      }).join('') + '</dl>' : '') +
      '</section>';
  }

  function renderInspectorPanel(title, body) {
    if (!body) {
      return '';
    }

    return '<section class="studio-inspector-panel">' +
      (title ? '<div class="studio-mini-title">' + core.escapeHtml(title) + '</div>' : '') +
      body +
      '</section>';
  }

  function layoutRegionTitle(app, regionName) {
    const meta = layoutMeta(app, regionName);
    return meta.title || regionName || '';
  }

  function resolveSectionLocation(app, target) {
    if (target && target.layoutRegion) {
      return layoutRegionTitle(app, target.layoutRegion);
    }

    if (target && target.sectionId) {
      const section = Studio.state.findSection(app, target.sectionId);
      return section ? (section.label || section.type || '') : '';
    }

    return '';
  }

  function sectionTypeName(app, section) {
    const meta = app.library.sections && section ? app.library.sections[section.type] : null;
    return meta && meta.name ? meta.name : (section && (section.label || section.type) ? (section.label || section.type) : '');
  }

  function blockTypeName(app, block) {
    const meta = app.library.blocks && block ? app.library.blocks[block.type] : null;
    return meta && meta.name ? meta.name : (block && (block.label || block.type) ? (block.label || block.type) : '');
  }

  function renderCompactActionButton(action, label, data, className, text) {
    function attributeName(key) {
      return String(key).replace(/([A-Z])/g, '-$1').toLowerCase();
    }

    return '<button type="button" class="' + core.escapeAttr(className || '') + '" title="' + core.escapeAttr(label || '') + '" data-action="' + core.escapeAttr(action) + '"' +
      Object.keys(data || {}).map(function (key) {
        return ' data-' + attributeName(key) + '="' + core.escapeAttr(String(data[key])) + '"';
      }).join('') +
      '>' + core.escapeHtml(text || '') + '</button>';
  }

  function capitalize(value) {
    const text = String(value || '');
    return text ? text.charAt(0).toUpperCase() + text.slice(1) : '';
  }

  function renderCompactActionGroup(actions) {
    return '<div class="studio-inline-actions">' + actions.join('') + '</div>';
  }

  function renderMegaElementAdders(app, navIndex, columnIndex) {
    const names = app.library.megaElements || {};
    return '<div class="studio-add-grid">' +
      '<button class="studio-btn" type="button" data-action="add-mega-element" data-nav-index="' + core.escapeAttr(String(navIndex)) + '" data-column-index="' + core.escapeAttr(String(columnIndex)) + '" data-element-type="link">+ ' + core.escapeHtml((names.link && names.link.name) || '') + '</button>' +
      '<button class="studio-btn" type="button" data-action="add-mega-element" data-nav-index="' + core.escapeAttr(String(navIndex)) + '" data-column-index="' + core.escapeAttr(String(columnIndex)) + '" data-element-type="text">+ ' + core.escapeHtml((names.text && names.text.name) || '') + '</button>' +
      '<button class="studio-btn" type="button" data-action="add-mega-element" data-nav-index="' + core.escapeAttr(String(navIndex)) + '" data-column-index="' + core.escapeAttr(String(columnIndex)) + '" data-element-type="button">+ ' + core.escapeHtml((names.button && names.button.name) || '') + '</button>' +
      '</div>';
  }

  function renderNavInspector(app) {
    const navbar = app.page.navbar || { settings: {}, brand: { label: '' }, rows: {}, items: [] };
    const brandMeta = app.library.navbarElements && app.library.navbarElements.brand ? app.library.navbarElements.brand : null;
    const brandRef = Studio.nav.findElementByKind(app, 'brand');
    const brandField = brandRef && brandRef.element
      ? renderBoundField(
        { label: brandMeta && brandMeta.name ? brandMeta.name : (app.ui.inspector.navTitle || ''), type: 'text' },
        'navelement.' + brandRef.element.id + '.label',
        brandRef.element.label || navbar.brand.label || ''
      )
      : '';

    return '<div class="studio-inspector-stack">' +
      renderInspectorCard(
        app.ui.inspector.summaryTitle || '',
        app.ui.inspector.navSubtitle || '',
        [
          { label: app.ui.inspector.itemsLabel || '', value: String((navbar.items || []).length) },
          { label: app.ui.inspector.columnsLabel || '', value: navbar.settings && navbar.settings.mega_columns_desktop ? String(navbar.settings.mega_columns_desktop) : '5' }
        ]
      ) +
      '<div class="studio-form-grid">' +
      brandField +
      renderBoundField({
        label: app.ui.inspector.megaColumnsTitle || '',
        type: 'select',
        options: [1, 2, 3, 4, 5, 6].map(function (count) {
          return { value: String(count), label: String(count) };
        })
      }, 'navbar.settings.mega_columns_desktop', navbar.settings && navbar.settings.mega_columns_desktop ? navbar.settings.mega_columns_desktop : '5') +
      '<div class="studio-mini-title">' + core.escapeHtml(app.ui.inspector.navLayoutTitle || '') + '</div>' +
      '<div class="studio-nav-layout-matrix">' +
      Studio.nav.ROWS.map(function (rowName) {
        return renderNavLayoutRow(app, rowName);
      }).join('') +
      '</div>' +
      '<div class="studio-inspector-actions"><button class="studio-btn studio-btn-full" type="button" data-action="add-nav-item">' + core.escapeHtml(app.ui.actions.addNavItem || '') + '</button></div>' +
      '<div class="studio-mini-title">' + core.escapeHtml(app.ui.inspector.navItemsTitle || '') + '</div>' +
      '<div class="studio-nav-editor-list">' +
      (navbar.items || []).map(function (item, index) {
        const selected = app.selection.kind === 'nav-item' && app.selection.navId === item.id;
        const meta = item.mega_menu && item.mega_menu.enabled
          ? String((item.mega_menu.columns || []).length)
          : (item.url || '');

        return '<div class="studio-nav-editor-item ' + (selected ? 'is-active' : '') + '" draggable="true" data-drag-kind="nav-item" data-drag-index="' + core.escapeAttr(String(index)) + '">' +
          '<span class="studio-drag-handle">⋮⋮</span>' +
          '<div class="studio-nav-editor-main">' +
          '<button class="studio-nav-editor-link" type="button" data-action="select-nav-item" data-index="' + core.escapeAttr(String(index)) + '">' + core.escapeHtml(item.label || '') + '</button>' +
          '<small>' + core.escapeHtml(meta) + '</small>' +
          '</div>' +
          renderCompactActionGroup([
            renderCompactActionButton('move-nav-item', app.ui.actions.moveUp || '', { index: index, direction: 'up' }, 'studio-icon-action', '↑'),
            renderCompactActionButton('move-nav-item', app.ui.actions.moveDown || '', { index: index, direction: 'down' }, 'studio-icon-action', '↓'),
            renderCompactActionButton('remove-nav-item', app.ui.actions.delete || '', { index: index }, 'studio-icon-action is-danger', '×')
          ]) +
          '</div>';
      }).join('') +
      '</div>' +
      '</div>' +
      '</div>';
  }

  function renderNavLayoutRow(app, rowName) {
    return '<div class="studio-nav-layout-row">' +
      '<div class="studio-nav-layout-row-title">' + core.escapeHtml(app.ui.inspector['navRow' + capitalize(rowName)] || rowName) + '</div>' +
      '<div class="studio-nav-layout-row-grid">' +
      renderNavZoneCard(app, rowName, 'left') +
      renderNavZoneCard(app, rowName, 'center') +
      renderNavZoneCard(app, rowName, 'right') +
      '</div>' +
      '</div>';
  }

  function renderNavZoneCard(app, rowName, zoneName) {
    const elements = Studio.nav.navElements(app, rowName, zoneName);
    const selected = (app.selection.kind === 'nav-zone' && app.selection.navRow === rowName && app.selection.navZone === zoneName) ||
      (app.selection.kind === 'nav-element' && app.selection.navRow === rowName && app.selection.navZone === zoneName);
    const title = (app.ui.inspector['navRow' + capitalize(rowName)] || rowName) + ' · ' + (app.ui.inspector['navZone' + capitalize(zoneName)] || zoneName);
    const meta = elements.map(function (element) {
      const library = app.library.navbarElements && app.library.navbarElements[element.kind] ? app.library.navbarElements[element.kind] : null;
      return library && library.name ? library.name : element.kind;
    }).join(', ');

    return '<button class="studio-nav-zone-card ' + (selected ? 'is-active' : '') + '" type="button" data-action="select-nav-zone" data-row="' + core.escapeAttr(rowName) + '" data-zone="' + core.escapeAttr(zoneName) + '">' +
      '<strong>' + core.escapeHtml(title) + '</strong>' +
      '<small>' + core.escapeHtml(meta || (app.ui.canvas.navZoneDrop || '')) + '</small>' +
      '</button>';
  }

  function renderNavZoneInspector(app, rowName, zoneName) {
    const title = (app.ui.inspector['navRow' + capitalize(rowName)] || rowName) + ' · ' + (app.ui.inspector['navZone' + capitalize(zoneName)] || zoneName);
    const elements = Studio.nav.navElements(app, rowName, zoneName);
    return '<div class="studio-inspector-stack">' +
      renderInspectorCard(
        app.ui.inspector.summaryTitle || '',
        title,
        [
          { label: app.ui.inspector.itemsLabel || '', value: String(elements.length) }
        ]
      ) +
      '<div class="studio-form-grid">' +
      '<div class="studio-mini-title">' + core.escapeHtml(app.ui.inspector.navElementTitle || '') + '</div>' +
      '<div class="studio-nav-layout-grid">' +
      Object.keys(app.library.navbarElements || {}).map(function (kind) {
        const meta = app.library.navbarElements[kind];
        return '<button class="studio-nav-zone-card" type="button" data-action="tool-click" data-kind="menu-tool" data-type="nav-' + core.escapeAttr(kind) + '">' +
          '<strong>' + core.escapeHtml(meta.name || kind) + '</strong>' +
          '<small>' + core.escapeHtml((meta.defaults && (meta.defaults.label || meta.defaults.text)) || '') + '</small>' +
          '</button>';
      }).join('') +
      '</div>' +
      '</div>' +
      '</div>';
  }

  function renderNavElementInspector(app, element) {
    const meta = app.library.navbarElements && app.library.navbarElements[element.kind] ? app.library.navbarElements[element.kind] : null;
    if (!meta) {
      return renderEmptyNote(app.ui.inspector.empty || '');
    }

    const fields = (meta.fields || []).map(function (field) {
      const key = field.key;
      return renderBoundField(field, 'navelement.' + element.id + '.' + key, element[key] || '');
    }).join('');

    let extra = '';
    if (element.kind === 'menu') {
      extra = '<div class="studio-inspector-actions"><button class="studio-btn studio-btn-full" type="button" data-action="add-nav-item">' + core.escapeHtml(app.ui.actions.addNavItem || '') + '</button></div>' +
        '<div class="studio-mini-title">' + core.escapeHtml(app.ui.inspector.navItemsTitle || '') + '</div>' +
        '<div class="studio-nav-editor-list">' +
        (app.page.navbar.items || []).map(function (item, index) {
          return '<div class="studio-nav-editor-item" draggable="true" data-drag-kind="nav-item" data-drag-index="' + core.escapeAttr(String(index)) + '">' +
            '<span class="studio-drag-handle">⋮⋮</span>' +
            '<div class="studio-nav-editor-main">' +
            '<button class="studio-nav-editor-link" type="button" data-action="select-nav-item" data-index="' + core.escapeAttr(String(index)) + '">' + core.escapeHtml(item.label || '') + '</button>' +
            '<small>' + core.escapeHtml(item.url || '') + '</small>' +
            '</div>' +
            '</div>';
        }).join('') +
        '</div>';
    }

    return '<div class="studio-inspector-stack">' +
      renderInspectorCard(
        app.ui.inspector.summaryTitle || '',
        meta.help || '',
        [
          { label: app.ui.inspector.typeLabel || '', value: meta.name || element.kind },
          { label: app.ui.inspector.locationLabel || '', value: (app.ui.inspector['navRow' + capitalize(element.row || '')] || element.row || '') + ' / ' + (app.ui.inspector['navZone' + capitalize(element.zone || '')] || element.zone || '') }
        ]
      ) +
      '<div class="studio-form-grid">' +
      fields +
      extra +
      '<button class="studio-btn studio-btn-danger studio-btn-full" type="button" data-action="remove-nav-element" data-element-id="' + core.escapeAttr(element.id || '') + '">' + core.escapeHtml(app.ui.actions.delete || '') + '</button>' +
      '</div>' +
      '</div>';
  }

  function renderNavItemInspector(app, indexOrId) {
    const item = Studio.nav.navItem(app, indexOrId);
    const index = item ? Studio.nav.findItemIndex(app, item.id) : -1;
    if (!item || index < 0) {
      return renderEmptyNote(app.ui.inspector.empty || '');
    }

    const linkFields = app.library.megaElements && app.library.megaElements.link ? app.library.megaElements.link.fields || [] : [];
    const columns = item.mega_menu && Array.isArray(item.mega_menu.columns) ? item.mega_menu.columns.slice().sort(function (left, right) {
      return Number(left.slot || 0) - Number(right.slot || 0);
    }) : [];

    return '<div class="studio-inspector-stack">' +
      renderInspectorCard(
        app.ui.inspector.summaryTitle || '',
        item.label || '',
        [
          { label: app.ui.inspector.targetLabel || '', value: item.target || '_self' },
          { label: app.ui.inspector.columnsLabel || '', value: String(columns.length) },
          { label: app.ui.inspector.sourceLabel || '', value: item.url || '' }
        ]
      ) +
      '<div class="studio-form-grid">' +
      renderBoundField({ label: linkFields[0] ? linkFields[0].label : '', type: 'text' }, 'navitem.' + index + '.label', item.label || '') +
      renderBoundField({ label: linkFields[1] ? linkFields[1].label : '', type: 'text' }, 'navitem.' + index + '.url', item.url || '') +
      renderBoundField({ label: linkFields[2] ? linkFields[2].label : '', type: 'select', options: app.ui.targets || [] }, 'navitem.' + index + '.target', item.target || '_self') +
      '<label class="studio-check"><input type="checkbox" data-bind="navitem.' + core.escapeAttr(String(index)) + '.mega_enabled" ' + (item.mega_menu && item.mega_menu.enabled ? 'checked' : '') + '> ' + core.escapeHtml(app.ui.inspector.megaColumnsTitle || '') + '</label>' +
      renderInspectorPanel(app.ui.inspector.actionsTitle || '', '<div class="studio-inspector-actions">' +
        '<button class="studio-btn" type="button" data-action="move-nav-item" data-index="' + core.escapeAttr(String(index)) + '" data-direction="up">' + core.escapeHtml(app.ui.actions.moveUp || '') + '</button>' +
        '<button class="studio-btn" type="button" data-action="move-nav-item" data-index="' + core.escapeAttr(String(index)) + '" data-direction="down">' + core.escapeHtml(app.ui.actions.moveDown || '') + '</button>' +
        '<button class="studio-btn studio-btn-danger" type="button" data-action="remove-nav-item" data-index="' + core.escapeAttr(String(index)) + '">' + core.escapeHtml(app.ui.actions.delete || '') + '</button>' +
        '<button class="studio-btn studio-btn-full" type="button" data-action="add-mega-column" data-nav-index="' + core.escapeAttr(String(index)) + '">' + core.escapeHtml(app.ui.actions.addMegaColumn || '') + '</button>' +
      '</div>') +
      '<div class="studio-mini-title">' + core.escapeHtml(app.ui.inspector.megaColumnsTitle || '') + '</div>' +
      (columns.length ? columns.map(function (column, columnIndex) {
        return renderMegaColumnInspector(app, index, column, columnIndex);
      }).join('') : renderEmptyNote(app.ui.canvas.megaColumnDrop || '')) +
      '</div>' +
      '</div>';
  }

  function renderMegaColumnInspector(app, navIndex, column, columnIndex) {
    const elements = Array.isArray(column.elements) ? column.elements : [];
    return '<div class="studio-repeater-card">' +
      '<div class="studio-item-toolbar">' +
      '<span class="studio-item-chip">' + core.escapeHtml((column.title || app.ui.inspector.megaColumnsTitle || '') + ' · ' + String(Number(column.slot || 0) + 1)) + '</span>' +
      renderCompactActionGroup([
        renderCompactActionButton('move-mega-column', app.ui.actions.moveUp || '', { navIndex: navIndex, columnIndex: columnIndex, direction: 'up' }, 'studio-icon-action', '↑'),
        renderCompactActionButton('move-mega-column', app.ui.actions.moveDown || '', { navIndex: navIndex, columnIndex: columnIndex, direction: 'down' }, 'studio-icon-action', '↓'),
        renderCompactActionButton('remove-mega-column', app.ui.actions.removeMegaColumn || '', { navIndex: navIndex, columnIndex: columnIndex }, 'studio-icon-action is-danger', '×')
      ]) +
      '</div>' +
      renderBoundField({ label: app.library.sections.hero.fields[2].label, type: 'text' }, 'megacolumn.' + navIndex + '.' + columnIndex + '.title', column.title || '') +
      '<div class="studio-mini-title">' + core.escapeHtml(app.ui.inspector.megaElementsTitle || '') + '</div>' +
      (elements.length ? elements.map(function (element, elementIndex) {
        return renderMegaElementInspector(app, navIndex, columnIndex, element, elementIndex, elements.length);
      }).join('') : renderEmptyNote(app.ui.canvas.megaElementDrop || '')) +
      renderMegaElementAdders(app, navIndex, columnIndex) +
      '</div>';
  }

  function renderMegaElementInspector(app, navIndex, columnIndex, element, elementIndex, totalElements) {
    const meta = app.library.megaElements[element.kind] || app.library.megaElements.link;
    return '<div class="studio-repeater-row">' +
      '<div class="studio-item-toolbar">' +
      '<span class="studio-item-chip">' + core.escapeHtml(meta.name || '') + '</span>' +
      renderCompactActionGroup([
        renderCompactActionButton('move-mega-element', app.ui.actions.moveUp || '', { navIndex: navIndex, columnIndex: columnIndex, elementIndex: elementIndex, direction: 'up' }, 'studio-icon-action', '↑'),
        renderCompactActionButton('move-mega-element', app.ui.actions.moveDown || '', { navIndex: navIndex, columnIndex: columnIndex, elementIndex: elementIndex, direction: 'down' }, 'studio-icon-action', '↓'),
        renderCompactActionButton('remove-mega-element', app.ui.actions.removeMegaElement || '', { navIndex: navIndex, columnIndex: columnIndex, elementIndex: elementIndex }, 'studio-icon-action is-danger', '×')
      ]) +
      '</div>' +
      meta.fields.map(function (field) {
        return renderBoundField(field, 'megaelement.' + navIndex + '.' + columnIndex + '.' + elementIndex + '.' + field.key, element[field.key] || '');
      }).join('') +
      '</div>';
  }

  function renderSectionInspector(app, section) {
    const meta = app.library.sections[section.type];
    const index = (app.page.sections || []).findIndex(function (entry) {
      return entry && entry.id === section.id;
    });
    const totalSections = (app.page.sections || []).length;
    const blocksCount = Array.isArray(section.blocks) ? section.blocks.length : 0;
    const itemsCount = Array.isArray(section.items) ? section.items.length : 0;
    const fields = (meta.fields || []).map(function (field) {
      if (field.key === 'label') {
        return renderBoundField(field, 'section.' + section.id + '.label', section.label || '');
      }
      return renderBoundField(field, 'section.' + section.id + '.settings.' + field.key, section.settings[field.key] || '');
    }).join('');

    const repeater = meta.repeater ? renderRepeater(section.id, meta.repeater, section.items || [], 'sectionitem.' + section.id, app.ui.actions.addSectionItem, app.ui.actions.remove, 'add-item', 'remove-item') : '';
    return '<div class="studio-inspector-stack">' +
      renderInspectorCard(
        app.ui.inspector.summaryTitle || '',
        meta && meta.help ? meta.help : '',
        [
          { label: app.ui.inspector.typeLabel || '', value: sectionTypeName(app, section) },
          { label: app.ui.inspector.blocksLabel || '', value: String(blocksCount) },
          { label: app.ui.inspector.itemsLabel || '', value: String(itemsCount) }
        ]
      ) +
      '<div class="studio-form-grid">' + fields + repeater + '</div>' +
      renderInspectorPanel(app.ui.inspector.actionsTitle || '', '<div class="studio-inspector-actions">' +
        '<button class="studio-btn" type="button" data-action="move-section" data-id="' + core.escapeAttr(section.id) + '" data-direction="up" ' + (index <= 0 ? 'disabled' : '') + '>' + core.escapeHtml(app.ui.actions.moveUp || '') + '</button>' +
        '<button class="studio-btn" type="button" data-action="move-section" data-id="' + core.escapeAttr(section.id) + '" data-direction="down" ' + (index === -1 || index >= totalSections - 1 ? 'disabled' : '') + '>' + core.escapeHtml(app.ui.actions.moveDown || '') + '</button>' +
        '<button class="studio-btn" type="button" data-action="duplicate-section" data-id="' + core.escapeAttr(section.id) + '">' + core.escapeHtml(app.ui.actions.duplicate || '') + '</button>' +
        '<button class="studio-btn studio-btn-danger" type="button" data-action="delete-section" data-id="' + core.escapeAttr(section.id) + '">' + core.escapeHtml(app.ui.actions.delete || '') + '</button>' +
      '</div>') +
      '</div>';
  }

  function renderBlockInspector(app, target, block) {
    const meta = app.library.blocks[block.type];
    const fieldPrefix = target.layoutRegion
      ? 'layoutblock.' + target.layoutRegion + '.' + block.id
      : 'block.' + target.sectionId + '.' + block.id;
    const itemPrefix = target.layoutRegion
      ? 'layoutblockitem.' + target.layoutRegion + '.' + block.id
      : 'blockitem.' + target.sectionId + '.' + block.id;

    const fields = (meta.fields || []).map(function (field) {
      if (field.key === 'label') {
        return renderBoundField(field, fieldPrefix + '.label', block.label || '');
      }
      return renderBoundField(field, fieldPrefix + '.settings.' + field.key, block.settings[field.key] || '');
    }).join('');

    const repeater = meta.repeater ? renderRepeater(block.id, meta.repeater, block.items || [], itemPrefix, app.ui.actions.addBlockItem, app.ui.actions.remove, 'add-block-item', 'remove-block-item', target) : '';
    return '<div class="studio-inspector-stack">' +
      renderInspectorCard(
        app.ui.inspector.summaryTitle || '',
        meta && meta.help ? meta.help : '',
        [
          { label: app.ui.inspector.typeLabel || '', value: blockTypeName(app, block) },
          { label: app.ui.inspector.locationLabel || '', value: resolveSectionLocation(app, target) },
          { label: app.ui.inspector.itemsLabel || '', value: String(Array.isArray(block.items) ? block.items.length : 0) }
        ]
      ) +
      '<div class="studio-form-grid">' + fields + repeater + '</div>' +
      renderInspectorPanel(app.ui.inspector.actionsTitle || '', '<div class="studio-inspector-actions">' +
        '<button class="studio-btn studio-btn-danger studio-btn-full" type="button" data-action="delete-block"' + blockTargetData(target) + ' data-block-id="' + core.escapeAttr(block.id) + '">' + core.escapeHtml(app.ui.actions.delete || '') + '</button>' +
      '</div>') +
      '</div>';
  }

  function renderRepeater(entityId, repeater, items, bindPrefix, addLabel, removeLabel, addAction, removeAction, target) {
    const targetData = target ? blockTargetData(target) : '';

    return '<div class="studio-mini-title">' + core.escapeHtml(repeater.label || '') + '</div>' +
      items.map(function (item, itemIndex) {
        return '<div class="studio-repeater-card">' +
          repeater.fields.map(function (field) {
            return renderBoundField(field, bindPrefix + '.' + itemIndex + '.' + field.key, item[field.key] || '');
          }).join('') +
          '<button type="button" data-action="' + core.escapeAttr(removeAction) + '" data-id="' + core.escapeAttr(entityId) + '"' + targetData + ' data-block-id="' + core.escapeAttr(entityId) + '" data-index="' + core.escapeAttr(String(itemIndex)) + '">' + core.escapeHtml(removeLabel || '') + '</button>' +
          '</div>';
      }).join('') +
      '<button class="studio-btn studio-btn-full" type="button" data-action="' + core.escapeAttr(addAction) + '" data-id="' + core.escapeAttr(entityId) + '"' + targetData + ' data-block-id="' + core.escapeAttr(entityId) + '">' + core.escapeHtml(addLabel || '') + '</button>';
  }

  function renderBoundField(field, bind, value) {
    const readonly = field.readonly ? ' readonly' : '';

    if (field.type === 'textarea') {
      return '<div class="studio-field"><label>' + core.escapeHtml(field.label || '') + '</label><textarea class="studio-textarea" data-bind="' + core.escapeAttr(bind) + '"' + readonly + '>' + core.escapeHtml(value || '') + '</textarea></div>';
    }

    if (field.type === 'color') {
      return '<div class="studio-field"><label>' + core.escapeHtml(field.label || '') + '</label><div class="studio-color-row"><input type="color" data-bind="' + core.escapeAttr(bind) + '" value="' + core.escapeAttr(value || '#000000') + '"><input class="studio-input" type="text" data-bind="' + core.escapeAttr(bind) + '" value="' + core.escapeAttr(value || '') + '"></div></div>';
    }

    if (field.type === 'media') {
      return '<div class="studio-field studio-field-media"><label>' + core.escapeHtml(field.label || '') + '</label><div class="studio-media-field-host" data-media-bind="' + core.escapeAttr(bind) + '" data-media-value="' + core.escapeAttr(value || '') + '"></div><input class="studio-input studio-media-source" type="text" data-bind="' + core.escapeAttr(bind) + '" value="' + core.escapeAttr(value || '') + '"' + readonly + '></div>';
    }

    if (field.type === 'select') {
      return '<div class="studio-field"><label>' + core.escapeHtml(field.label || '') + '</label><select class="studio-select" data-bind="' + core.escapeAttr(bind) + '"' + (field.readonly ? ' disabled' : '') + '>' +
        (field.options || []).map(function (option) {
          return '<option value="' + core.escapeAttr(option.value || '') + '"' + (String(option.value) === String(value) ? ' selected' : '') + '>' + core.escapeHtml(option.label || '') + '</option>';
        }).join('') +
        '</select></div>';
    }

    const inputType = field.type === 'number' ? 'number' : 'text';
    return '<div class="studio-field"><label>' + core.escapeHtml(field.label || '') + '</label><input class="studio-input" type="' + core.escapeAttr(inputType) + '" data-bind="' + core.escapeAttr(bind) + '" value="' + core.escapeAttr(value || '') + '"' + readonly + '></div>';
  }

  function normalizeImageHeightPreset(value) {
    const allowed = ['auto', '180', '240', '320', '420', '560'];
    const normalized = String(value || '').trim();
    return allowed.indexOf(normalized) >= 0 ? normalized : 'auto';
  }

  Studio.render = {
    cache,
    renderAll,
    setStatus,
    openDrawer,
    closeDrawer
  };
})(window, document);
