(function (window, document) {
  'use strict';

  const root = document.getElementById('flatcms-studio');
  const Studio = window.FlatCMSStudio || (window.FlatCMSStudio = {});

  if (!root || !Studio.core || !Studio.state || !Studio.render || !Studio.api || !Studio.nav || !Studio.dnd) {
    return;
  }

  const boot = Studio.core.parseBoot();
  const app = Studio.state.create(root, boot);

  app.onMutation = function () {
    app.previewNeedsRefresh = true;
    Studio.state.syncSelection(app);
    Studio.render.renderAll(app);
  };

  Studio.render.renderAll(app);
  Studio.dnd.bind(app);

  var resizeFrame = 0;
  window.addEventListener('resize', function () {
    if (resizeFrame) {
      window.cancelAnimationFrame(resizeFrame);
    }
    resizeFrame = window.requestAnimationFrame(function () {
      resizeFrame = 0;
      Studio.render.renderAll(app);
    });
  });

  function blockTargetFromDataset(source) {
    return {
      sectionId: source && source.dataset ? source.dataset.sectionId || '' : '',
      layoutRegion: source && source.dataset ? source.dataset.layoutRegion || '' : ''
    };
  }

  function resolveBlockInsertTarget() {
    if (app.selection.kind === 'section') {
      return { sectionId: app.selection.sectionId || '' };
    }

    if (app.selection.kind === 'block') {
      if (app.selection.layoutRegion) {
        return { layoutRegion: app.selection.layoutRegion };
      }

      if (app.selection.sectionId) {
        return { sectionId: app.selection.sectionId };
      }
    }

    if (app.selection.kind === 'layout-region' && app.selection.layoutRegion && app.selection.layoutRegion !== 'main') {
      return { layoutRegion: app.selection.layoutRegion };
    }

    return null;
  }

  function isTextualBoundField(input) {
    if (!input || !input.dataset || !input.dataset.bind) {
      return false;
    }

    if (input.tagName === 'TEXTAREA') {
      return true;
    }

    if (input.tagName !== 'INPUT') {
      return false;
    }

    return ['checkbox', 'radio', 'color', 'range', 'file'].indexOf(String(input.type || '').toLowerCase()) === -1;
  }

  function captureBoundFieldState(input) {
    if (!isTextualBoundField(input)) {
      return null;
    }

    return {
      bind: input.dataset.bind || '',
      tagName: input.tagName,
      type: String(input.type || '').toLowerCase(),
      scrollTop: app.cache && app.cache.inspectorContent ? app.cache.inspectorContent.scrollTop : 0,
      selectionStart: typeof input.selectionStart === 'number' ? input.selectionStart : null,
      selectionEnd: typeof input.selectionEnd === 'number' ? input.selectionEnd : null,
      selectionDirection: typeof input.selectionDirection === 'string' ? input.selectionDirection : 'none'
    };
  }

  function restoreBoundFieldState(snapshot) {
    if (!snapshot || !snapshot.bind) {
      return;
    }

    window.requestAnimationFrame(function () {
      if (app.cache && app.cache.inspectorContent) {
        app.cache.inspectorContent.scrollTop = snapshot.scrollTop || 0;
      }

      const fields = root.querySelectorAll('[data-bind]');
      let nextField = null;

      for (let index = 0; index < fields.length; index += 1) {
        const field = fields[index];
        if (!field || !field.dataset || field.dataset.bind !== snapshot.bind) {
          continue;
        }

        if (field.tagName !== snapshot.tagName) {
          continue;
        }

        if (field.tagName === 'INPUT' && String(field.type || '').toLowerCase() !== snapshot.type) {
          continue;
        }

        nextField = field;
        break;
      }

      if (!nextField) {
        return;
      }

      try {
        nextField.focus({ preventScroll: true });
      } catch (error) {
        nextField.focus();
      }

      if (typeof nextField.setSelectionRange === 'function' && typeof snapshot.selectionStart === 'number') {
        const maxLength = String(nextField.value || '').length;
        const start = Math.min(snapshot.selectionStart, maxLength);
        const end = Math.min(snapshot.selectionEnd != null ? snapshot.selectionEnd : start, maxLength);
        try {
          nextField.setSelectionRange(start, end, snapshot.selectionDirection || 'none');
        } catch (error) {
          nextField.setSelectionRange(start, end);
        }
      }

      if (app.cache && app.cache.inspectorContent) {
        app.cache.inspectorContent.scrollTop = snapshot.scrollTop || 0;
      }
    });
  }

  function selectionFingerprint(selection) {
    if (!selection || !selection.kind) {
      return '';
    }

    return [
      selection.kind || '',
      selection.layoutRegion || '',
      selection.sectionId || '',
      selection.blockId || '',
      selection.navId || '',
      selection.navIndex != null ? String(selection.navIndex) : '',
      selection.navRow || '',
      selection.navZone || '',
      selection.navElementId || ''
    ].join('::');
  }

  function applyCanvasSelection(nextSelection) {
    Studio.state.select(app, nextSelection);

    const nextFingerprint = selectionFingerprint(app.selection);
    app.inspectorOpen = nextFingerprint !== '';

    Studio.render.renderAll(app);
  }

  root.addEventListener('click', function (event) {
    const target = event.target.closest('[data-action]');
    if (!target) {
      return;
    }

    const action = target.dataset.action;
    const selectAction = action === 'select-page' ||
      action === 'select-nav' ||
      action === 'select-nav-item' ||
      action === 'select-nav-zone' ||
      action === 'select-nav-element' ||
      action === 'select-layout-region' ||
      action === 'select-section' ||
      action === 'select-block';

    if (selectAction && app.drag && (app.drag.active || app.drag.suppressClickUntil > Date.now())) {
      event.preventDefault();
      return;
    }

    if (action === 'toggle-drawer') {
      const drawerName = target.dataset.drawer || 'blocks';
      if (drawerName === 'page') {
        Studio.state.select(app, { kind: 'page' });
        Studio.render.renderAll(app);
      } else if (drawerName === 'menu' && app.selection.kind !== 'nav' && app.selection.kind !== 'nav-item') {
        Studio.state.select(app, { kind: 'nav' });
        Studio.render.renderAll(app);
      }
      Studio.render.openDrawer(app, drawerName);
      return;
    }

    if (action === 'close-drawer') {
      Studio.render.closeDrawer(app);
      Studio.render.renderAll(app);
      return;
    }

    if (action === 'close-inspector') {
      app.inspectorOpen = false;
      Studio.render.renderAll(app);
      return;
    }

    if (action === 'tool-click') {
      addToolByClick(target.dataset.kind, target.dataset.type);
      return;
    }

    if (action === 'select-page') {
      applyCanvasSelection({ kind: 'page' });
      return;
    }

    if (action === 'select-nav') {
      applyCanvasSelection({ kind: 'nav' });
      return;
    }

    if (action === 'select-nav-item') {
      event.stopPropagation();
      applyCanvasSelection({ kind: 'nav-item', navIndex: Number(target.dataset.index) });
      return;
    }

    if (action === 'select-nav-zone') {
      event.stopPropagation();
      applyCanvasSelection({
        kind: 'nav-zone',
        navRow: target.dataset.row,
        navZone: target.dataset.zone
      });
      return;
    }

    if (action === 'select-nav-element') {
      event.stopPropagation();
      applyCanvasSelection({
        kind: 'nav-element',
        navElementId: target.dataset.elementId,
        navRow: target.dataset.row,
        navZone: target.dataset.zone
      });
      return;
    }

    if (action === 'select-layout-region') {
      if (event.target.closest('.studio-canvas-section') || event.target.closest('.studio-block')) {
        return;
      }
      applyCanvasSelection({ kind: 'layout-region', layoutRegion: target.dataset.layoutRegion });
      return;
    }

    if (action === 'select-section') {
      if (event.target.closest('.studio-block') || event.target.closest('.studio-section-toolbar button')) {
        return;
      }
      applyCanvasSelection({ kind: 'section', sectionId: target.dataset.sectionId });
      return;
    }

    if (action === 'select-block') {
      event.stopPropagation();
      const blockTarget = blockTargetFromDataset(target);
      applyCanvasSelection({ kind: 'block', sectionId: blockTarget.sectionId, layoutRegion: blockTarget.layoutRegion, blockId: target.dataset.blockId });
      return;
    }

    if (action === 'move-section') {
      if (Studio.state.moveSection(app, target.dataset.id, target.dataset.direction)) {
        app.onMutation();
      }
      return;
    }

    if (action === 'duplicate-section') {
      if (Studio.state.duplicateSection(app, target.dataset.id)) {
        app.onMutation();
      }
      return;
    }

    if (action === 'delete-section') {
      if (Studio.state.deleteSection(app, target.dataset.id)) {
        app.onMutation();
      }
      return;
    }

    if (action === 'delete-block') {
      if (Studio.state.deleteBlock(app, blockTargetFromDataset(target), target.dataset.blockId)) {
        app.onMutation();
      }
      return;
    }

    if (action === 'add-item') {
      if (Studio.state.addSectionItem(app, target.dataset.id)) {
        app.onMutation();
      }
      return;
    }

    if (action === 'remove-item') {
      if (Studio.state.removeSectionItem(app, target.dataset.id, Number(target.dataset.index))) {
        app.onMutation();
      }
      return;
    }

    if (action === 'add-block-item') {
      if (Studio.state.addBlockItem(app, blockTargetFromDataset(target), target.dataset.blockId)) {
        app.onMutation();
      }
      return;
    }

    if (action === 'remove-block-item') {
      if (Studio.state.removeBlockItem(app, blockTargetFromDataset(target), target.dataset.blockId, Number(target.dataset.index))) {
        app.onMutation();
      }
      return;
    }

    if (action === 'add-nav-item') {
      if (Studio.nav.addItem(app)) {
        app.onMutation();
      }
      return;
    }

    if (action === 'remove-nav-item') {
      if (Studio.nav.removeItem(app, Number(target.dataset.index))) {
        app.onMutation();
      }
      return;
    }

    if (action === 'move-nav-item') {
      if (Studio.nav.moveItem(app, Number(target.dataset.index), target.dataset.direction)) {
        app.onMutation();
      }
      return;
    }

    if (action === 'remove-nav-element') {
      if (Studio.nav.removeElement(app, target.dataset.elementId || '')) {
        app.onMutation();
      }
      return;
    }

    if (action === 'add-mega-column') {
      if (Studio.nav.addColumn(app, Number(target.dataset.navIndex))) {
        app.onMutation();
      }
      return;
    }

    if (action === 'remove-mega-column') {
      if (Studio.nav.removeColumn(app, Number(target.dataset.navIndex), Number(target.dataset.columnIndex))) {
        app.onMutation();
      }
      return;
    }

    if (action === 'move-mega-column') {
      if (Studio.nav.moveColumnStep(app, Number(target.dataset.navIndex), Number(target.dataset.columnIndex), target.dataset.direction)) {
        app.onMutation();
      }
      return;
    }

    if (action === 'add-mega-element') {
      if (Studio.nav.addElementToColumn(app, Number(target.dataset.navIndex), Number(target.dataset.columnIndex), target.dataset.elementType || 'link')) {
        app.onMutation();
      }
      return;
    }

    if (action === 'remove-mega-element') {
      if (Studio.nav.removeElementFromColumn(app, Number(target.dataset.navIndex), Number(target.dataset.columnIndex), Number(target.dataset.elementIndex))) {
        app.onMutation();
      }
      return;
    }

    if (action === 'move-mega-element') {
      if (Studio.nav.moveElementStep(app, Number(target.dataset.navIndex), Number(target.dataset.columnIndex), Number(target.dataset.elementIndex), target.dataset.direction)) {
        app.onMutation();
      }
      return;
    }

    if (action === 'viewport') {
      app.viewport = target.dataset.viewport || 'desktop';
      Studio.render.renderAll(app);
      return;
    }

    if (action === 'switch-canvas-mode') {
      switchCanvasMode(target.dataset.mode || 'compose');
      return;
    }

    if (action === 'refresh-render-frame') {
      refreshRenderFrame();
      return;
    }

    if (action === 'save') {
      save();
      return;
    }

    if (action === 'preview') {
      preview();
      return;
    }

    if (action === 'export') {
      Studio.api.exportState(app);
      Studio.render.setStatus(app, 'exported');
      return;
    }
  });

  root.addEventListener('input', handleInput);
  root.addEventListener('change', handleInput);

  function handleInput(event) {
    const input = event.target;
    if (input.dataset && input.dataset.action === 'switch-source-page') {
      switchSourcePage(String(input.value || ''));
      return;
    }

    if (input.dataset && input.dataset.action === 'zoom') {
      app.zoom = normalizeZoomValue(input.value);
      Studio.render.renderAll(app);
      return;
    }

    const bind = input.dataset.bind;
    if (!bind) {
      return;
    }

    const value = input.type === 'checkbox' ? input.checked : input.value;
    const fieldState = captureBoundFieldState(input);
    if (Studio.state.applyBinding(app, bind, value)) {
      app.onMutation();
      restoreBoundFieldState(fieldState);
    }
  }

  function addToolByClick(kind, type) {
    if (kind === 'section') {
      if (Studio.state.insertSection(app, type, app.page.sections.length)) {
        app.onMutation();
      }
      return;
    }

    if (kind === 'block') {
      const target = resolveBlockInsertTarget();
      if (!target) {
        return;
      }

      if (Studio.state.addBlock(app, target, type)) {
        app.onMutation();
      }
      return;
    }

    if (kind === 'menu-tool') {
      if (type === 'nav-link') {
        if (Studio.nav.addItem(app)) {
          app.onMutation();
        }
        return;
      }

      if (type.indexOf('nav-') === 0 && type !== 'nav-link') {
        const zone = Studio.nav.defaultZone(app);
        const kindName = type.replace('nav-', '');
        if (Studio.nav.addElement(app, kindName, zone.row, zone.zone)) {
          app.onMutation();
        }
        return;
      }

      const selectionIndex = app.selection.kind === 'nav-item' && app.selection.navId
        ? Studio.nav.findItemIndex(app, app.selection.navId)
        : Studio.nav.resolvePreviewIndex(app);
      const navIndex = selectionIndex >= 0 ? selectionIndex : 0;

      if (type === 'mega-column') {
        if (Studio.nav.addColumn(app, navIndex)) {
          app.onMutation();
        }
        return;
      }

      const item = Studio.nav.navItem(app, navIndex);
      if (item && (!item.mega_menu.columns || item.mega_menu.columns.length === 0)) {
        Studio.nav.addColumn(app, navIndex);
      }

      if (Studio.nav.addElementToColumn(app, navIndex, 0, type.replace('mega-', ''))) {
        app.onMutation();
      }
    }
  }

  async function save() {
    try {
      await Studio.api.save(app);
      if (app.canvasMode === 'render') {
        await Studio.api.refreshPreviewFrame(app);
      }
      Studio.render.renderAll(app);
      Studio.render.setStatus(app, 'saved');
    } catch (error) {
      Studio.render.setStatus(app, error && error.message ? error.message : 'error', true);
    }
  }

  async function refreshRenderFrame() {
    try {
      await Studio.api.refreshPreviewFrame(app);
      app.canvasMode = 'render';
      Studio.render.renderAll(app);
    } catch (error) {
      Studio.render.setStatus(app, error && error.message ? error.message : 'error', true);
    }
  }

  async function switchCanvasMode(mode) {
    const nextMode = mode === 'render' ? 'render' : 'compose';
    if (nextMode === 'compose') {
      app.canvasMode = 'compose';
      Studio.render.renderAll(app);
      return;
    }

    await refreshRenderFrame();
  }

  async function preview() {
    try {
      await Studio.api.preview(app);
      Studio.render.renderAll(app);
    } catch (error) {
      Studio.render.setStatus(app, error && error.message ? error.message : 'error', true);
    }
  }

  async function switchSourcePage(sourceId) {
    const targetSource = Studio.state.findSource ? Studio.state.findSource(app, sourceId) : null;
    if (!targetSource) {
      return;
    }

    const currentSource = Studio.state.currentSource ? Studio.state.currentSource(app) : null;
    if (currentSource && String(currentSource.id || '') === String(targetSource.id || '')) {
      return;
    }

    try {
      if (app.dirty) {
        await save();
      }
      window.location.href = targetSource.studio_url || ('/admin/studio?page=' + encodeURIComponent(String(targetSource.id || '')));
    } catch (error) {
      Studio.render.setStatus(app, error && error.message ? error.message : 'error', true);
      Studio.render.renderAll(app);
    }
  }

  function normalizeZoomValue(value) {
    const allowed = [50, 67, 75, 90, 100, 110, 125, 150];
    const numeric = Number(value);
    return allowed.indexOf(numeric) >= 0 ? numeric : 100;
  }
})(window, document);
