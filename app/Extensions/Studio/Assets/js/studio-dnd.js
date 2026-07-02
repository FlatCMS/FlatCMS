(function (window) {
  'use strict';

  const Studio = window.FlatCMSStudio || (window.FlatCMSStudio = {});

  function blockTargetFromDataset(source) {
    return {
      sectionId: source && source.dataset ? source.dataset.sectionId || '' : '',
      layoutRegion: source && source.dataset ? source.dataset.layoutRegion || '' : ''
    };
  }

  function dragState(app) {
    if (!app.drag) {
      app.drag = {
        active: false,
        kind: '',
        type: '',
        payload: null,
        activeZone: null,
        suppressClickUntil: 0
      };
    }

    return app.drag;
  }

  function dragClass(payload) {
    if (!payload || !payload.kind) {
      return '';
    }

    if (
      payload.kind === 'nav-item' ||
      payload.kind === 'existing-nav-element' ||
      (payload.kind === 'menu-tool' && payload.type === 'nav-link') ||
      (payload.kind === 'menu-tool' && payload.type.indexOf('nav-') === 0)
    ) {
      return 'is-dragging-nav-item';
    }

    if (payload.kind === 'existing-mega-column' || (payload.kind === 'menu-tool' && payload.type === 'mega-column')) {
      return 'is-dragging-mega-column';
    }

    if (payload.kind === 'existing-mega-element' || (payload.kind === 'menu-tool' && payload.type.indexOf('mega-') === 0)) {
      return 'is-dragging-mega-element';
    }

    return '';
  }

  function dragDimensions(draggable, payload) {
    const measured = draggable && typeof draggable.getBoundingClientRect === 'function'
      ? draggable.getBoundingClientRect()
      : null;

    if (measured && measured.width > 0 && measured.height > 0) {
      return {
        width: Math.round(measured.width),
        height: Math.round(measured.height)
      };
    }

    if (payload.kind === 'menu-tool' && payload.type === 'mega-column') {
      return { width: 180, height: 110 };
    }

    if (payload.kind === 'menu-tool' && payload.type === 'nav-link') {
      return { width: 96, height: 32 };
    }

    if (payload.kind === 'menu-tool' && payload.type.indexOf('mega-') === 0) {
      return { width: 180, height: 52 };
    }

    return { width: 72, height: 32 };
  }

  function acceptsDrop(payload, zone) {
    if (!payload || !payload.kind || !zone) {
      return false;
    }

    const dropZone = zone.dataset.dropZone;

    if (payload.kind === 'section' && dropZone === 'section') {
      return true;
    }

    if (payload.kind === 'existing-section' && dropZone === 'section') {
      return true;
    }

    if (payload.kind === 'block' && dropZone === 'block') {
      return true;
    }

    if (payload.kind === 'existing-block' && dropZone === 'block') {
      return true;
    }

    if (payload.kind === 'nav-item' && dropZone === 'nav-item') {
      return true;
    }

    if (payload.kind === 'menu-tool' && payload.type === 'nav-link' && dropZone === 'nav-item') {
      return true;
    }

    if (payload.kind === 'menu-tool' && payload.type.indexOf('nav-') === 0 && payload.type !== 'nav-link' && dropZone === 'nav-zone') {
      return true;
    }

    if (payload.kind === 'existing-nav-element' && dropZone === 'nav-zone') {
      return true;
    }

    if (payload.kind === 'menu-tool' && payload.type === 'mega-column' && dropZone === 'mega-slot') {
      return true;
    }

    if (payload.kind === 'existing-mega-column' && dropZone === 'mega-slot') {
      return true;
    }

    if (payload.kind === 'menu-tool' && dropZone === 'mega-element' && payload.type.indexOf('mega-') === 0) {
      return true;
    }

    if (payload.kind === 'existing-mega-element' && dropZone === 'mega-element') {
      return true;
    }

    return false;
  }

  function setActiveZone(app, zone) {
    const state = dragState(app);
    if (state.activeZone && state.activeZone !== zone) {
      state.activeZone.classList.remove('is-drag-over');
    }

    if (zone) {
      zone.classList.add('is-drag-over');
    }

    state.activeZone = zone || null;
  }

  function bind(app) {
    app.root.addEventListener('dragstart', function (event) {
      const draggable = event.target.closest('[draggable="true"]');
      if (!draggable) {
        return;
      }

      const payload = {
        kind: draggable.dataset.dragKind || '',
        type: draggable.dataset.dragType || '',
        index: draggable.dataset.dragIndex != null ? Number(draggable.dataset.dragIndex) : null,
        sectionId: draggable.dataset.dragSectionId || '',
        layoutRegion: draggable.dataset.dragLayoutRegion || '',
        blockId: draggable.dataset.dragBlockId || '',
        row: draggable.dataset.row || '',
        zone: draggable.dataset.zone || '',
        elementId: draggable.dataset.elementId || '',
        navIndex: draggable.dataset.navIndex != null ? Number(draggable.dataset.navIndex) : null,
        columnIndex: draggable.dataset.columnIndex != null ? Number(draggable.dataset.columnIndex) : null,
        slotIndex: draggable.dataset.slotIndex != null ? Number(draggable.dataset.slotIndex) : null,
        elementIndex: draggable.dataset.elementIndex != null ? Number(draggable.dataset.elementIndex) : null
      };
      const shell = app.root.querySelector('.studio-shell');
      const state = dragState(app);
      const size = dragDimensions(draggable, payload);
      const category = dragClass(payload);

      event.dataTransfer.effectAllowed = 'copyMove';
      event.dataTransfer.setData('application/x-flatcms-studio', JSON.stringify(payload));
      event.dataTransfer.setData('text/plain', JSON.stringify(payload));

      state.active = true;
      state.kind = payload.kind;
      state.type = payload.type;
      state.payload = payload;
      state.activeZone = null;

      if (shell) {
        shell.classList.add('is-dragging');
        shell.classList.remove('is-dragging-nav-item', 'is-dragging-mega-column', 'is-dragging-mega-element');
        if (category) {
          shell.classList.add(category);
        }
        shell.style.setProperty('--studio-drag-width', String(size.width) + 'px');
        shell.style.setProperty('--studio-drag-height', String(size.height) + 'px');
      }
    });

    app.root.addEventListener('dragover', function (event) {
      const zone = event.target.closest('[data-drop-zone]');
      const state = dragState(app);
      if (!zone || !acceptsDrop(state.payload, zone)) {
        return;
      }

      event.preventDefault();
      setActiveZone(app, zone);
    });

    app.root.addEventListener('dragleave', function (event) {
      const zone = event.target.closest('[data-drop-zone]');
      if (zone && dragState(app).activeZone === zone) {
        zone.classList.remove('is-drag-over');
        dragState(app).activeZone = null;
      }
    });

    app.root.addEventListener('dragend', function () {
      clearDragState(app, true);
    });

    app.root.addEventListener('drop', function (event) {
      const zone = event.target.closest('[data-drop-zone]');
      let payload = {};
      try {
        payload = JSON.parse(event.dataTransfer.getData('application/x-flatcms-studio') || event.dataTransfer.getData('text/plain') || '{}');
      } catch (error) {
        payload = {};
      }

      if (!zone || !acceptsDrop(payload, zone)) {
        clearDragState(app, true);
        return;
      }

      event.preventDefault();
      const applied = handleDrop(app, payload, zone);
      clearDragState(app, true);

      if (applied && typeof app.onMutation === 'function') {
        app.onMutation();
      }
    });
  }

  function clearDragState(app, suppressClicks) {
    const shell = app.root.querySelector('.studio-shell');
    const state = dragState(app);

    if (state.activeZone) {
      state.activeZone.classList.remove('is-drag-over');
      state.activeZone = null;
    }

    if (shell) {
      shell.classList.remove('is-dragging', 'is-dragging-nav-item', 'is-dragging-mega-column', 'is-dragging-mega-element');
      shell.style.removeProperty('--studio-drag-width');
      shell.style.removeProperty('--studio-drag-height');
    }

    app.root.querySelectorAll('.is-drag-over').forEach(function (element) {
      element.classList.remove('is-drag-over');
    });

    state.active = false;
    state.kind = '';
    state.type = '';
    state.payload = null;
    if (suppressClicks) {
      state.suppressClickUntil = Date.now() + 180;
    }
  }

  function handleDrop(app, payload, zone) {
    if (!payload.kind) {
      return false;
    }

    const dropZone = zone.dataset.dropZone;

    if (payload.kind === 'section' && dropZone === 'section') {
      return Boolean(Studio.state.insertSection(app, payload.type, Number(zone.dataset.index)));
    }

    if (payload.kind === 'existing-section' && dropZone === 'section') {
      return Studio.state.moveSectionByDrop(app, Number(payload.index), Number(zone.dataset.index));
    }

    if (payload.kind === 'block' && dropZone === 'block') {
      return Boolean(Studio.state.addBlock(app, blockTargetFromDataset(zone), payload.type, Number(zone.dataset.index)));
    }

    if (payload.kind === 'existing-block' && dropZone === 'block') {
      return Studio.state.moveBlockByDrop(
        app,
        { sectionId: payload.sectionId, layoutRegion: payload.layoutRegion },
        payload.blockId,
        blockTargetFromDataset(zone),
        Number(zone.dataset.index)
      );
    }

    if (payload.kind === 'nav-item' && dropZone === 'nav-item') {
      return Studio.nav.reorderItems(app, Number(payload.index), Number(zone.dataset.index));
    }

    if (payload.kind === 'menu-tool' && payload.type === 'nav-link' && dropZone === 'nav-item') {
      return Boolean(Studio.nav.addItem(app, Number(zone.dataset.index)));
    }

    if (payload.kind === 'menu-tool' && payload.type.indexOf('nav-') === 0 && payload.type !== 'nav-link' && dropZone === 'nav-zone') {
      return Boolean(Studio.nav.addElement(
        app,
        payload.type.replace('nav-', ''),
        zone.dataset.row,
        zone.dataset.zone,
        Number(zone.dataset.index)
      ));
    }

    if (payload.kind === 'existing-nav-element' && dropZone === 'nav-zone') {
      return Studio.nav.moveElement(
        app,
        payload.row,
        payload.zone,
        Number(payload.index),
        zone.dataset.row,
        zone.dataset.zone,
        Number(zone.dataset.index)
      );
    }

    if (payload.kind === 'menu-tool' && payload.type === 'mega-column' && dropZone === 'mega-slot') {
      return Boolean(Studio.nav.addColumn(app, Number(zone.dataset.navIndex), Number(zone.dataset.slotIndex)));
    }

    if (payload.kind === 'existing-mega-column' && dropZone === 'mega-slot') {
      return Studio.nav.moveColumnToSlot(app, Number(payload.navIndex), Number(payload.columnIndex), Number(zone.dataset.slotIndex));
    }

    if (payload.kind === 'menu-tool' && dropZone === 'mega-element' && payload.type.indexOf('mega-') === 0) {
      return Boolean(Studio.nav.addElementToColumn(
        app,
        Number(zone.dataset.navIndex),
        Number(zone.dataset.columnIndex),
        payload.type.replace('mega-', ''),
        Number(zone.dataset.elementIndex)
      ));
    }

    if (payload.kind === 'existing-mega-element' && dropZone === 'mega-element') {
      return Studio.nav.moveElementInColumns(
        app,
        Number(payload.navIndex),
        Number(payload.columnIndex),
        Number(payload.elementIndex),
        Number(zone.dataset.navIndex),
        Number(zone.dataset.columnIndex),
        Number(zone.dataset.elementIndex)
      );
    }

    return false;
  }

  Studio.dnd = {
    bind
  };
})(window);
