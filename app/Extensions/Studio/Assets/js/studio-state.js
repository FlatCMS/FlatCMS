(function (window) {
  'use strict';

  const Studio = window.FlatCMSStudio || (window.FlatCMSStudio = {});
  const core = Studio.core;
  const BLOCK_LAYOUT_REGIONS = ['header_before', 'header_after', 'aside', 'footer'];
  const STRUCTURAL_LAYOUT_REGIONS = ['header', 'header_before', 'header_after', 'main', 'aside', 'footer'];

  function emptySelection() {
    return {
      kind: null,
      layoutRegion: null,
      sectionId: null,
      blockId: null,
      navIndex: null,
      navId: null,
      navRow: null,
      navZone: null,
      navElementId: null
    };
  }

  function create(root, boot) {
    return {
      root,
      config: boot.config || {},
      ui: boot.ui || {},
      library: boot.library || {},
      sources: Array.isArray(boot.sources) ? boot.sources : [],
      currentSource: boot.currentSource && typeof boot.currentSource === 'object' ? boot.currentSource : null,
      page: normalizePage(boot.page || {}),
      selection: emptySelection(),
      viewport: 'desktop',
      zoom: 100,
      canvasMode: 'compose',
      activeDrawer: 'blocks',
      previewFrameUrl: '',
      previewNeedsRefresh: false,
      drag: {
        active: false,
        kind: '',
        type: '',
        payload: null,
        activeZone: null,
        suppressClickUntil: 0
      },
      dirty: false,
      isSaving: false,
      cache: {},
      inspectorOpen: false,
      statusKey: 'loading'
    };
  }

  function normalizePage(page) {
    const documentState = core.deepClone(page || {});

    documentState.page = documentState.page || {};
    documentState.source = normalizeSource(documentState.source, documentState.page);
    documentState.design = documentState.design || { global: {} };
    documentState.design.global = documentState.design.global || {};
    documentState.navbar = documentState.navbar || { settings: {}, brand: { label: '', subtitle: '' }, rows: {}, items: [] };
    documentState.navbar.settings = documentState.navbar.settings || {};
    documentState.navbar.settings.mega_columns_desktop = String(Math.max(1, Math.min(6, Number(documentState.navbar.settings.mega_columns_desktop || 5))));
    documentState.navbar.brand = documentState.navbar.brand || { label: '', subtitle: '' };
    documentState.navbar.brand.subtitle = documentState.navbar.brand.subtitle || '';
    documentState.navbar.rows = normalizeNavbarRows(documentState.navbar);
    if (documentState.navbar.rows.main && Array.isArray(documentState.navbar.rows.main.left)) {
      const mainBrand = documentState.navbar.rows.main.left.find(function (element) {
        return element && element.kind === 'brand';
      });
      if (mainBrand) {
        documentState.navbar.brand.label = documentState.navbar.brand.label || mainBrand.label || '';
        documentState.navbar.brand.subtitle = documentState.navbar.brand.subtitle || mainBrand.subtitle || '';
      }
    }
    documentState.navbar.items = Array.isArray(documentState.navbar.items) ? documentState.navbar.items : [];
    documentState.layout = normalizeLayout(documentState.layout || {});
    documentState.sections = Array.isArray(documentState.sections) ? documentState.sections : [];

    documentState.navbar.items.forEach(function (item) {
      item.id = item.id || 'nav-' + core.shortId();
      item.label = item.label || '';
      item.url = item.url || '#';
      item.target = item.target || '_self';
      item.mega_menu = item.mega_menu || { enabled: false, columns: [] };
      item.mega_menu.columns = Array.isArray(item.mega_menu.columns) ? item.mega_menu.columns : [];
      item.mega_menu.columns.forEach(function (column, index) {
        column.id = column.id || 'mega-' + core.shortId();
        column.slot = Math.max(0, Math.min(5, Number(column.slot != null ? column.slot : index)));
        column.title = column.title || '';
        column.elements = Array.isArray(column.elements) ? column.elements : [];
        column.elements.forEach(function (element) {
          element.id = element.id || 'mega-element-' + core.shortId();
        });
      });
      item.mega_menu.columns.sort(function (left, right) {
        return Number(left.slot || 0) - Number(right.slot || 0);
      });
    });

    BLOCK_LAYOUT_REGIONS.forEach(function (regionName) {
      documentState.layout[regionName].blocks = normalizeBlockCollection(documentState.layout[regionName].blocks);
    });

    documentState.sections.forEach(function (section) {
      section.id = section.id || (section.type || 'section') + '-' + core.shortId();
      section.settings = section.settings || {};
      section.items = Array.isArray(section.items) ? section.items : [];
      section.blocks = normalizeBlockCollection(section.blocks);
    });

    return documentState;
  }

  function normalizeSource(source, page) {
    const data = source && typeof source === 'object' ? source : {};
    const pageData = page && typeof page === 'object' ? page : {};

    return {
      entity_type: String(data.entity_type || 'page'),
      entity_id: String(data.entity_id || pageData.id || ''),
      translation_group: String(data.translation_group || data.entity_id || pageData.id || ''),
      locale: String(data.locale || ''),
      title: String(data.title || pageData.title || ''),
      slug: String(data.slug || pageData.slug || ''),
      status: String(data.status || 'draft')
    };
  }

  function findSource(app, sourceId) {
    const id = String(sourceId || '');
    if (!id) {
      return null;
    }

    return (app.sources || []).find(function (source) {
      return source && String(source.id || '') === id;
    }) || null;
  }

  function currentSource(app) {
    const sourceId = app.page && app.page.source ? app.page.source.entity_id : '';
    return findSource(app, sourceId) || app.currentSource || null;
  }

  function normalizeLayout(layout) {
    const normalized = {
      header_before: { blocks: [] },
      header_after: { blocks: [] },
      aside: { blocks: [] },
      footer: { blocks: [] }
    };
    const legacyHeader = layout && typeof layout.header === 'object' && layout.header
      ? layout.header
      : null;

    BLOCK_LAYOUT_REGIONS.forEach(function (regionName) {
      let region = layout && typeof layout[regionName] === 'object' && layout[regionName]
        ? layout[regionName]
        : {};
      if (regionName === 'header_before' && (!region || !Array.isArray(region.blocks)) && legacyHeader) {
        region = legacyHeader;
      }
      normalized[regionName] = {
        blocks: Array.isArray(region.blocks) ? region.blocks : []
      };
    });

    return normalized;
  }

  function normalizeBlockCollection(blocks) {
    const list = Array.isArray(blocks) ? blocks : [];
    list.forEach(function (block) {
      block.id = block.id || 'block-' + core.shortId();
      block.settings = block.settings || {};
      block.items = Array.isArray(block.items) ? block.items : [];
    });
    return list;
  }

  function normalizeNavbarRows(navbar) {
    const rows = navbar && navbar.rows ? navbar.rows : {};
    const normalized = {
      top: { left: [], center: [], right: [] },
      main: { left: [], center: [], right: [] },
      bottom: { left: [], center: [], right: [] }
    };
    const usedKinds = {};

    ['top', 'main', 'bottom'].forEach(function (rowName) {
      ['left', 'center', 'right'].forEach(function (zoneName) {
        const zone = Array.isArray(rows[rowName] && rows[rowName][zoneName]) ? rows[rowName][zoneName] : [];
        normalized[rowName][zoneName] = zone.slice(0, 8).map(function (element) {
          if (!element || typeof element !== 'object') {
            return null;
          }

          const next = core.deepClone(element);
          next.id = next.id || 'nav-element-' + core.shortId();
          next.kind = String(next.kind || 'text');
          if (next.kind === 'brand' || next.kind === 'menu') {
            if (usedKinds[next.kind]) {
              return null;
            }
            usedKinds[next.kind] = true;
          }
          if (next.kind === 'brand') {
            next.label = next.label || '';
            next.subtitle = next.subtitle || '';
            next.src = next.src || '';
            next.alt = next.alt || next.label || '';
          } else
          if (next.kind === 'button') {
            next.label = next.label || '';
            next.url = next.url || '#';
            next.target = next.target || '_self';
          } else if (next.kind === 'slogan' || next.kind === 'text') {
            next.text = next.text || '';
          } else {
            next.label = next.label || '';
          }
          return next;
        }).filter(Boolean);
      });
    });

    const mainLeft = normalized.main.left;
    const mainCenter = normalized.main.center;
    const mainRight = normalized.main.right;
    const mainBrand = mainLeft.length === 1 && mainLeft[0] && mainLeft[0].kind === 'brand'
      ? mainLeft[0]
      : null;
    const mainMenuPresent = mainCenter.some(function (element) {
      return element && element.kind === 'menu';
    });
    const borrowedSlogan = mainRight.length === 1 && mainRight[0] && mainRight[0].kind === 'slogan'
      ? String(mainRight[0].text || '').trim()
      : '';

    if (mainBrand && mainMenuPresent && borrowedSlogan !== '' && String(mainBrand.subtitle || '').trim() === '') {
      mainBrand.subtitle = borrowedSlogan;
      normalized.main.right = [];
      if (navbar && navbar.brand && typeof navbar.brand === 'object') {
        navbar.brand.subtitle = borrowedSlogan;
      }
    }

    return normalized;
  }

  function resolveNavSelection(app, selection) {
    const items = app.page && app.page.navbar && Array.isArray(app.page.navbar.items)
      ? app.page.navbar.items
      : [];

    if (selection.navId) {
      const indexById = items.findIndex(function (item) {
        return item && item.id === selection.navId;
      });
      if (indexById >= 0) {
        return { index: indexById, item: items[indexById] };
      }
    }

    const index = Number(selection.navIndex);
    if (Number.isInteger(index) && index >= 0 && index < items.length) {
      return { index: index, item: items[index] };
    }

    return null;
  }

  function normalizeSelection(app, selection) {
    const next = emptySelection();
    if (!selection || !selection.kind) {
      return next;
    }

    if (selection.kind === 'page' || selection.kind === 'nav') {
      next.kind = selection.kind;
      return next;
    }

    if (selection.kind === 'layout-region') {
      if (!isStructuralLayoutRegion(selection.layoutRegion)) {
        return next;
      }

      next.kind = 'layout-region';
      next.layoutRegion = selection.layoutRegion;
      return next;
    }

    if (selection.kind === 'nav-zone') {
      if (!selection.navRow || !selection.navZone) {
        next.kind = 'nav';
        return next;
      }

      next.kind = 'nav-zone';
      next.navRow = selection.navRow;
      next.navZone = selection.navZone;
      return next;
    }

    if (selection.kind === 'nav-item') {
      const resolved = resolveNavSelection(app, selection);
      if (!resolved) {
        next.kind = 'nav';
        return next;
      }

      next.kind = 'nav-item';
      next.navIndex = resolved.index;
      next.navId = resolved.item.id || null;
      return next;
    }

    if (selection.kind === 'nav-element') {
      const location = Studio.nav && typeof Studio.nav.findElementLocation === 'function'
        ? Studio.nav.findElementLocation(app, selection.navElementId)
        : null;
      if (!location) {
        next.kind = 'nav';
        return next;
      }

      next.kind = 'nav-element';
      next.navElementId = location.element.id || null;
      next.navRow = location.row;
      next.navZone = location.zone;
      return next;
    }

    if (selection.kind === 'section') {
      const section = findSection(app, selection.sectionId);
      if (!section) {
        return next;
      }

      next.kind = 'section';
      next.sectionId = section.id;
      return next;
    }

    if (selection.kind === 'block') {
      const block = findBlock(app, { sectionId: selection.sectionId, layoutRegion: selection.layoutRegion }, selection.blockId);
      if (!block) {
        if (isStructuralLayoutRegion(selection.layoutRegion)) {
          next.kind = 'layout-region';
          next.layoutRegion = selection.layoutRegion;
          return next;
        }

        const section = findSection(app, selection.sectionId);
        if (!section) {
          return next;
        }

        next.kind = 'section';
        next.sectionId = section.id;
        return next;
      }

      next.kind = 'block';
      next.sectionId = selection.sectionId || null;
      next.layoutRegion = selection.layoutRegion || null;
      next.blockId = block.id;
      return next;
    }

    return next;
  }

  function select(app, selection) {
    app.selection = normalizeSelection(app, selection);
    return app.selection;
  }

  function clearSelection(app) {
    app.selection = emptySelection();
    return app.selection;
  }

  function syncSelection(app) {
    app.selection = normalizeSelection(app, app.selection);
    return app.selection;
  }

  function markDirty(app) {
    app.dirty = true;
  }

  function setClean(app) {
    app.dirty = false;
  }

  function findSection(app, sectionId) {
    return (app.page.sections || []).find(function (section) {
      return section.id === sectionId;
    }) || null;
  }

  function findSectionIndex(app, sectionId) {
    return (app.page.sections || []).findIndex(function (section) {
      return section.id === sectionId;
    });
  }

  function isBlockLayoutRegion(regionName) {
    return BLOCK_LAYOUT_REGIONS.indexOf(String(regionName || '')) >= 0;
  }

  function isStructuralLayoutRegion(regionName) {
    return STRUCTURAL_LAYOUT_REGIONS.indexOf(String(regionName || '')) >= 0;
  }

  function normalizeBlockTarget(sectionOrTarget, layoutRegion) {
    if (sectionOrTarget && typeof sectionOrTarget === 'object' && !Array.isArray(sectionOrTarget)) {
      return {
        sectionId: String(sectionOrTarget.sectionId || ''),
        layoutRegion: String(sectionOrTarget.layoutRegion || '')
      };
    }

    return {
      sectionId: typeof sectionOrTarget === 'string' ? sectionOrTarget : '',
      layoutRegion: String(layoutRegion || '')
    };
  }

  function findLayoutRegion(app, regionName) {
    if (!isBlockLayoutRegion(regionName)) {
      return null;
    }

    app.page.layout = normalizeLayout(app.page.layout || {});
    return app.page.layout[regionName] || null;
  }

  function resolveBlockOwner(app, sectionOrTarget, layoutRegion) {
    const target = normalizeBlockTarget(sectionOrTarget, layoutRegion);

    if (isBlockLayoutRegion(target.layoutRegion)) {
      const region = findLayoutRegion(app, target.layoutRegion);
      if (!region) {
        return null;
      }

      region.blocks = normalizeBlockCollection(region.blocks);
      return {
        sectionId: '',
        layoutRegion: target.layoutRegion,
        blocks: region.blocks
      };
    }

    const section = findSection(app, target.sectionId);
    if (!section) {
      return null;
    }

    section.blocks = normalizeBlockCollection(section.blocks);
    return {
      sectionId: section.id,
      layoutRegion: '',
      blocks: section.blocks
    };
  }

  function findBlock(app, sectionOrTarget, blockId, layoutRegion) {
    const owner = resolveBlockOwner(app, sectionOrTarget, layoutRegion);
    if (!owner || !Array.isArray(owner.blocks)) {
      return null;
    }

    return owner.blocks.find(function (block) {
      return block.id === blockId;
    }) || null;
  }

  function insertSection(app, type, index) {
    const meta = app.library.sections && app.library.sections[type] ? app.library.sections[type] : null;
    if (!meta) {
      return null;
    }

    const section = core.deepClone(meta.defaults || {});
    section.id = type + '-' + core.shortId();
    section.blocks = Array.isArray(section.blocks) ? section.blocks : [];
    section.blocks.forEach(function (block) {
      block.id = 'block-' + core.shortId();
    });

    const list = app.page.sections || [];
    const targetIndex = Math.max(0, Math.min(Number(index) || 0, list.length));
    list.splice(targetIndex, 0, section);
    app.page.sections = list;
    select(app, { kind: 'section', sectionId: section.id });
    markDirty(app);

    return section;
  }

  function moveSectionByDrop(app, fromIndex, toIndex) {
    const list = app.page.sections || [];
    if (fromIndex < 0 || fromIndex >= list.length || toIndex < 0 || toIndex > list.length) {
      return false;
    }

    const nextIndex = fromIndex < toIndex ? toIndex - 1 : toIndex;
    const moved = list.splice(fromIndex, 1)[0];
    if (!moved) {
      return false;
    }

    list.splice(nextIndex, 0, moved);
    select(app, { kind: 'section', sectionId: moved.id });
    markDirty(app);

    return true;
  }

  function moveSection(app, sectionId, direction) {
    const list = app.page.sections || [];
    const index = findSectionIndex(app, sectionId);
    if (index < 0) {
      return false;
    }

    const next = direction === 'up' ? index - 1 : index + 1;
    if (next < 0 || next >= list.length) {
      return false;
    }

    const current = list[index];
    list[index] = list[next];
    list[next] = current;
    syncSelection(app);
    markDirty(app);

    return true;
  }

  function duplicateSection(app, sectionId) {
    const list = app.page.sections || [];
    const index = findSectionIndex(app, sectionId);
    if (index < 0) {
      return null;
    }

    const copy = core.deepClone(list[index]);
    copy.id = (copy.type || 'section') + '-' + core.shortId();
    copy.blocks = Array.isArray(copy.blocks) ? copy.blocks : [];
    copy.blocks.forEach(function (block) {
      block.id = 'block-' + core.shortId();
    });

    list.splice(index + 1, 0, copy);
    select(app, { kind: 'section', sectionId: copy.id });
    markDirty(app);

    return copy;
  }

  function deleteSection(app, sectionId) {
    const index = findSectionIndex(app, sectionId);
    if (index < 0) {
      return false;
    }

    app.page.sections.splice(index, 1);
    syncSelection(app);
    markDirty(app);

    return true;
  }

  function addSectionItem(app, sectionId) {
    const section = findSection(app, sectionId);
    if (!section) {
      return false;
    }

    const meta = app.library.sections && app.library.sections[section.type] ? app.library.sections[section.type] : null;
    if (!meta || !meta.repeater) {
      return false;
    }

    const item = {};
    meta.repeater.fields.forEach(function (field) {
      item[field.key] = field.key === 'value' ? '1' : '';
    });

    section.items = Array.isArray(section.items) ? section.items : [];
    section.items.push(item);
    markDirty(app);

    return true;
  }

  function removeSectionItem(app, sectionId, itemIndex) {
    const section = findSection(app, sectionId);
    if (!section || !Array.isArray(section.items) || itemIndex < 0 || itemIndex >= section.items.length) {
      return false;
    }

    section.items.splice(itemIndex, 1);
    markDirty(app);

    return true;
  }

  function addBlock(app, sectionOrTarget, type, index) {
    const owner = resolveBlockOwner(app, sectionOrTarget);
    const meta = app.library.blocks && app.library.blocks[type] ? app.library.blocks[type] : null;
    if (!owner || !meta) {
      return null;
    }

    const block = core.deepClone(meta.defaults || {});
    block.id = 'block-' + core.shortId();
    owner.blocks = normalizeBlockCollection(owner.blocks);

    const targetIndex = typeof index === 'number'
      ? Math.max(0, Math.min(index, owner.blocks.length))
      : owner.blocks.length;

    owner.blocks.splice(targetIndex, 0, block);
    select(app, { kind: 'block', sectionId: owner.sectionId, layoutRegion: owner.layoutRegion, blockId: block.id });
    markDirty(app);

    return block;
  }

  function moveBlockByDrop(app, fromTarget, blockId, toTarget, toIndex) {
    const from = resolveBlockOwner(app, fromTarget);
    const to = resolveBlockOwner(app, toTarget);
    if (!from || !to || !Array.isArray(from.blocks)) {
      return false;
    }

    const fromIndex = from.blocks.findIndex(function (block) {
      return block.id === blockId;
    });
    if (fromIndex < 0) {
      return false;
    }

    const block = from.blocks.splice(fromIndex, 1)[0];
    if (!block) {
      return false;
    }

    to.blocks = normalizeBlockCollection(to.blocks);
    const targetIndex = typeof toIndex === 'number'
      ? Math.max(0, Math.min(toIndex, to.blocks.length))
      : to.blocks.length;
    const sameOwner = from.sectionId === to.sectionId && from.layoutRegion === to.layoutRegion;
    const insertionIndex = sameOwner && fromIndex < targetIndex ? targetIndex - 1 : targetIndex;
    to.blocks.splice(insertionIndex, 0, block);
    select(app, { kind: 'block', sectionId: to.sectionId, layoutRegion: to.layoutRegion, blockId: block.id });
    markDirty(app);

    return true;
  }

  function moveBlock(app, fromTarget, blockId, toTarget) {
    const to = resolveBlockOwner(app, toTarget);
    if (!to) {
      return false;
    }

    const targetIndex = Array.isArray(to.blocks) ? to.blocks.length : 0;
    return moveBlockByDrop(app, fromTarget, blockId, toTarget, targetIndex);
  }

  function deleteBlock(app, sectionOrTarget, blockId) {
    const owner = resolveBlockOwner(app, sectionOrTarget);
    if (!owner || !Array.isArray(owner.blocks)) {
      return false;
    }

    const index = owner.blocks.findIndex(function (block) {
      return block.id === blockId;
    });
    if (index < 0) {
      return false;
    }

    owner.blocks.splice(index, 1);
    syncSelection(app);
    markDirty(app);

    return true;
  }

  function addBlockItem(app, sectionOrTarget, blockId) {
    const block = findBlock(app, sectionOrTarget, blockId);
    const meta = block && app.library.blocks ? app.library.blocks[block.type] : null;
    if (!block || !meta || !meta.repeater) {
      return false;
    }

    const item = {};
    meta.repeater.fields.forEach(function (field) {
      item[field.key] = field.key === 'url' ? '#' : '';
    });

    block.items = Array.isArray(block.items) ? block.items : [];
    block.items.push(item);
    markDirty(app);

    return true;
  }

  function removeBlockItem(app, sectionOrTarget, blockId, itemIndex) {
    const block = findBlock(app, sectionOrTarget, blockId);
    if (!block || !Array.isArray(block.items) || itemIndex < 0 || itemIndex >= block.items.length) {
      return false;
    }

    block.items.splice(itemIndex, 1);
    markDirty(app);

    return true;
  }

  function applyBinding(app, bind, value) {
    const parts = String(bind || '').split('.');
    if (!parts[0]) {
      return false;
    }

    if (parts[0] === 'page') {
      app.page.page[parts[1]] = value;
      markDirty(app);
      return true;
    }

    if (parts[0] === 'design') {
      app.page.design.global[parts[1]] = value;
      markDirty(app);
      return true;
    }

    if (parts[0] === 'navbar' && parts[1] === 'brand' && (parts[2] === 'label' || parts[2] === 'subtitle')) {
      app.page.navbar.brand[parts[2]] = value;
      if (Studio.nav && typeof Studio.nav.findElementByKind === 'function') {
        const brand = Studio.nav.findElementByKind(app, 'brand');
        if (brand && brand.element) {
          brand.element[parts[2]] = value;
        }
      }
      markDirty(app);
      return true;
    }

    if (parts[0] === 'navbar' && parts[1] === 'settings' && parts[2] === 'mega_columns_desktop') {
      app.page.navbar.settings.mega_columns_desktop = String(Math.max(1, Math.min(6, Number(value || 5))));
      markDirty(app);
      return true;
    }

    if (parts[0] === 'navelement') {
      const element = Studio.nav.navElement(app, parts[1]);
      if (!element) {
        return false;
      }

      if (parts[2] === 'target') {
        element.target = value || '_self';
      } else {
        element[parts[2]] = value;
      }
      if (element.kind === 'brand' && (parts[2] === 'label' || parts[2] === 'subtitle')) {
        app.page.navbar.brand[parts[2]] = value;
      }
      markDirty(app);
      return true;
    }

    if (parts[0] === 'navitem') {
      const item = Studio.nav.navItem(app, Number(parts[1]));
      if (!item) {
        return false;
      }

      if (parts[2] === 'mega_enabled') {
        item.mega_menu.enabled = Boolean(value);
      } else {
        item[parts[2]] = value;
      }
      markDirty(app);
      return true;
    }

    if (parts[0] === 'megacolumn') {
      const column = Studio.nav.megaColumn(app, Number(parts[1]), Number(parts[2]));
      if (!column) {
        return false;
      }

      column[parts[3]] = value;
      markDirty(app);
      return true;
    }

    if (parts[0] === 'megaelement') {
      const element = Studio.nav.megaElement(app, Number(parts[1]), Number(parts[2]), Number(parts[3]));
      if (!element) {
        return false;
      }

      element[parts[4]] = value;
      markDirty(app);
      return true;
    }

    if (parts[0] === 'section') {
      const section = findSection(app, parts[1]);
      if (!section) {
        return false;
      }

      if (parts[2] === 'label') {
        section.label = value;
      } else if (parts[2] === 'settings') {
        section.settings[parts[3]] = value;
      } else {
        return false;
      }

      markDirty(app);
      return true;
    }

    if (parts[0] === 'sectionitem') {
      const section = findSection(app, parts[1]);
      if (!section || !Array.isArray(section.items) || !section.items[Number(parts[2])]) {
        return false;
      }

      section.items[Number(parts[2])][parts[3]] = value;
      markDirty(app);
      return true;
    }

    if (parts[0] === 'layoutblock') {
      const block = findBlock(app, { layoutRegion: parts[1] }, parts[2]);
      if (!block) {
        return false;
      }

      if (parts[3] === 'label') {
        block.label = value;
      } else if (parts[3] === 'settings') {
        block.settings[parts[4]] = value;
      } else {
        return false;
      }

      markDirty(app);
      return true;
    }

    if (parts[0] === 'layoutblockitem') {
      const block = findBlock(app, { layoutRegion: parts[1] }, parts[2]);
      if (!block || !Array.isArray(block.items) || !block.items[Number(parts[3])]) {
        return false;
      }

      block.items[Number(parts[3])][parts[4]] = value;
      markDirty(app);
      return true;
    }

    if (parts[0] === 'block') {
      const block = findBlock(app, parts[1], parts[2]);
      if (!block) {
        return false;
      }

      if (parts[3] === 'label') {
        block.label = value;
      } else if (parts[3] === 'settings') {
        block.settings[parts[4]] = value;
      } else {
        return false;
      }

      markDirty(app);
      return true;
    }

    if (parts[0] === 'blockitem') {
      const block = findBlock(app, parts[1], parts[2]);
      if (!block || !Array.isArray(block.items) || !block.items[Number(parts[3])]) {
        return false;
      }

      block.items[Number(parts[3])][parts[4]] = value;
      markDirty(app);
      return true;
    }

    return false;
  }

  Studio.state = {
    create,
    normalizePage,
    findSource,
    currentSource,
    select,
    clearSelection,
    syncSelection,
    markDirty,
    setClean,
    findSection,
    findSectionIndex,
    findLayoutRegion,
    findBlock,
    insertSection,
    moveSectionByDrop,
    moveSection,
    duplicateSection,
    deleteSection,
    addSectionItem,
    removeSectionItem,
    addBlock,
    moveBlockByDrop,
    moveBlock,
    deleteBlock,
    addBlockItem,
    removeBlockItem,
    applyBinding
  };
})(window);
