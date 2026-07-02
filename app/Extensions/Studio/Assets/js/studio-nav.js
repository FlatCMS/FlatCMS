(function (window) {
  'use strict';

  const Studio = window.FlatCMSStudio || (window.FlatCMSStudio = {});
  const core = Studio.core;
  const ROWS = ['top', 'main', 'bottom'];
  const ZONES = ['left', 'center', 'right'];

  function items(app) {
    return app.page && app.page.navbar && Array.isArray(app.page.navbar.items)
      ? app.page.navbar.items
      : [];
  }

  function settings(app) {
    if (!app.page.navbar.settings) {
      app.page.navbar.settings = {};
    }

    if (!app.page.navbar.settings.mega_columns_desktop) {
      app.page.navbar.settings.mega_columns_desktop = '5';
    }

    return app.page.navbar.settings;
  }

  function megaColumnsDesktop(app) {
    return Math.max(1, Math.min(6, Number(settings(app).mega_columns_desktop || 5)));
  }

  function rows(app) {
    if (!app.page.navbar.rows) {
      app.page.navbar.rows = {};
    }

    ROWS.forEach(function (rowName) {
      if (!app.page.navbar.rows[rowName]) {
        app.page.navbar.rows[rowName] = {};
      }
      ZONES.forEach(function (zoneName) {
        if (!Array.isArray(app.page.navbar.rows[rowName][zoneName])) {
          app.page.navbar.rows[rowName][zoneName] = [];
        }
      });
    });

    return app.page.navbar.rows;
  }

  function navElements(app, rowName, zoneName) {
    const allRows = rows(app);
    return allRows[rowName] && Array.isArray(allRows[rowName][zoneName])
      ? allRows[rowName][zoneName]
      : [];
  }

  function findItemIndex(app, navId) {
    return items(app).findIndex(function (item) {
      return item && item.id === navId;
    });
  }

  function navItem(app, indexOrId) {
    const list = items(app);
    if (typeof indexOrId === 'string' && indexOrId !== '') {
      const indexById = findItemIndex(app, indexOrId);
      return indexById >= 0 ? list[indexById] || null : null;
    }

    const index = Number(indexOrId);
    return Number.isInteger(index) && index >= 0 && index < list.length
      ? list[index] || null
      : null;
  }

  function findElementLocation(app, elementId) {
    let found = null;
    ROWS.some(function (rowName) {
      return ZONES.some(function (zoneName) {
        const zone = navElements(app, rowName, zoneName);
        const index = zone.findIndex(function (element) {
          return element && element.id === elementId;
        });
        if (index >= 0) {
          found = {
            row: rowName,
            zone: zoneName,
            index: index,
            element: zone[index]
          };
          return true;
        }
        return false;
      });
    });
    return found;
  }

  function navElement(app, elementId) {
    const location = findElementLocation(app, elementId);
    return location ? location.element : null;
  }

  function defaultZone(app) {
    if (app.selection.kind === 'nav-zone' && app.selection.navRow && app.selection.navZone) {
      return { row: app.selection.navRow, zone: app.selection.navZone };
    }

    if (app.selection.kind === 'nav-element' && app.selection.navRow && app.selection.navZone) {
      return { row: app.selection.navRow, zone: app.selection.navZone };
    }

    return { row: 'main', zone: 'center' };
  }

  function createNavElement(app, kind) {
    const meta = app.library.navbarElements && app.library.navbarElements[kind]
      ? app.library.navbarElements[kind]
      : null;
    if (!meta) {
      return null;
    }

    const element = core.deepClone(meta.defaults || {});
    element.id = 'nav-element-' + core.shortId();
    element.kind = kind;
    if (kind === 'button') {
      element.target = element.target || '_self';
      element.url = element.url || '#';
    }
    return element;
  }

  function addElement(app, kind, rowName, zoneName, index) {
    if ((kind === 'brand' || kind === 'menu') && findElementByKind(app, kind)) {
      const existing = findElementByKind(app, kind);
      if (existing) {
        Studio.state.select(app, {
          kind: 'nav-element',
          navElementId: existing.id,
          navRow: existing.row,
          navZone: existing.zone
        });
        return existing.element;
      }
    }

    const element = createNavElement(app, kind);
    if (!element) {
      return null;
    }

    const zone = navElements(app, rowName, zoneName);
    const targetIndex = typeof index === 'number'
      ? Math.max(0, Math.min(index, zone.length))
      : zone.length;

    zone.splice(targetIndex, 0, element);
    Studio.state.select(app, {
      kind: 'nav-element',
      navElementId: element.id,
      navRow: rowName,
      navZone: zoneName
    });
    Studio.state.markDirty(app);

    return element;
  }

  function findElementByKind(app, kind) {
    let found = null;
    ROWS.some(function (rowName) {
      return ZONES.some(function (zoneName) {
        const zone = navElements(app, rowName, zoneName);
        const index = zone.findIndex(function (element) {
          return element && element.kind === kind;
        });
        if (index >= 0) {
          found = { row: rowName, zone: zoneName, index: index, element: zone[index], id: zone[index].id };
          return true;
        }
        return false;
      });
    });
    return found;
  }

  function removeElement(app, elementId) {
    const location = findElementLocation(app, elementId);
    if (!location) {
      return false;
    }

    navElements(app, location.row, location.zone).splice(location.index, 1);
    Studio.state.syncSelection(app);
    if (!app.selection.kind) {
      Studio.state.select(app, { kind: 'nav' });
    }
    Studio.state.markDirty(app);

    return true;
  }

  function moveElement(app, fromRow, fromZone, fromIndex, toRow, toZone, toIndex) {
    const source = navElements(app, fromRow, fromZone);
    const target = navElements(app, toRow, toZone);
    if (fromIndex < 0 || fromIndex >= source.length || toIndex < 0 || toIndex > target.length) {
      return false;
    }

    const element = source.splice(fromIndex, 1)[0];
    if (!element) {
      return false;
    }

    const insertionIndex = source === target && fromIndex < toIndex ? toIndex - 1 : toIndex;
    target.splice(Math.max(0, Math.min(insertionIndex, target.length)), 0, element);
    Studio.state.select(app, {
      kind: 'nav-element',
      navElementId: element.id,
      navRow: toRow,
      navZone: toZone
    });
    Studio.state.markDirty(app);

    return true;
  }

  function sortedColumns(item) {
    const columns = item && item.mega_menu && Array.isArray(item.mega_menu.columns)
      ? item.mega_menu.columns.slice()
      : [];

    return columns.sort(function (left, right) {
      return Number(left.slot || 0) - Number(right.slot || 0);
    });
  }

  function megaColumn(app, navIndex, columnIndex) {
    const item = navItem(app, navIndex);
    const columns = sortedColumns(item);
    return columns[columnIndex] || null;
  }

  function megaColumnAtSlot(app, navIndex, slotIndex) {
    const item = navItem(app, navIndex);
    const columns = item && item.mega_menu && Array.isArray(item.mega_menu.columns)
      ? item.mega_menu.columns
      : [];

    return columns.find(function (column) {
      return Number(column.slot || 0) === Number(slotIndex);
    }) || null;
  }

  function megaColumnIndex(app, navIndex, columnId) {
    const columns = sortedColumns(navItem(app, navIndex));
    return columns.findIndex(function (column) {
      return column && column.id === columnId;
    });
  }

  function megaElement(app, navIndex, columnIndex, elementIndex) {
    const column = megaColumn(app, navIndex, columnIndex);
    return column && Array.isArray(column.elements)
      ? column.elements[elementIndex] || null
      : null;
  }

  function resolvePreviewIndex(app) {
    const list = items(app);
    if (app.selection.kind === 'nav-item' && app.selection.navId) {
      const selectedIndex = findItemIndex(app, app.selection.navId);
      if (selectedIndex >= 0) {
        return selectedIndex;
      }
    }

    const index = list.findIndex(function (item) {
      return item && item.mega_menu && item.mega_menu.enabled;
    });

    return index >= 0 ? index : null;
  }

  function activeMegaPreviewIndex(app) {
    if (app.selection.kind !== 'nav-item') {
      return null;
    }

    const index = app.selection.navId
      ? findItemIndex(app, app.selection.navId)
      : Number(app.selection.navIndex);

    if (!Number.isInteger(index) || index < 0) {
      return null;
    }

    const item = navItem(app, index);
    if (!item || !item.mega_menu || item.mega_menu.enabled !== true) {
      return null;
    }

    return index;
  }

  function addItem(app, index) {
    const item = {
      id: 'nav-' + core.shortId(),
      label: '',
      url: '#',
      target: '_self',
      mega_menu: {
        enabled: false,
        columns: []
      }
    };

    const list = items(app);
    const targetIndex = typeof index === 'number' ? Math.max(0, Math.min(index, list.length)) : list.length;
    list.splice(targetIndex, 0, item);
    Studio.state.select(app, { kind: 'nav-item', navId: item.id });
    Studio.state.markDirty(app);

    return item;
  }

  function removeItem(app, index) {
    const list = items(app);
    if (index < 0 || index >= list.length) {
      return false;
    }

    list.splice(index, 1);
    Studio.state.syncSelection(app);
    if (!app.selection.kind) {
      Studio.state.select(app, { kind: 'nav' });
    }
    Studio.state.markDirty(app);

    return true;
  }

  function reorderItems(app, fromIndex, toIndex) {
    const list = items(app);
    if (fromIndex < 0 || fromIndex >= list.length || toIndex < 0 || toIndex > list.length) {
      return false;
    }

    const targetIndex = fromIndex < toIndex ? toIndex - 1 : toIndex;
    const item = list.splice(fromIndex, 1)[0];
    if (!item) {
      return false;
    }

    list.splice(targetIndex, 0, item);
    Studio.state.select(app, { kind: 'nav-item', navId: item.id });
    Studio.state.markDirty(app);

    return true;
  }

  function moveItem(app, index, direction) {
    const list = items(app);
    if (index < 0 || index >= list.length) {
      return false;
    }

    if (direction === 'up') {
      if (index === 0) {
        return false;
      }
      return reorderItems(app, index, index - 1);
    }

    if (direction === 'down') {
      if (index >= list.length - 1) {
        return false;
      }
      return reorderItems(app, index, index + 2);
    }

    return false;
  }

  function addColumn(app, navIndex, slotIndex) {
    const item = navItem(app, navIndex);
    if (!item) {
      return null;
    }

    item.mega_menu = item.mega_menu || { enabled: true, columns: [] };
    item.mega_menu.enabled = true;
    item.mega_menu.columns = Array.isArray(item.mega_menu.columns) ? item.mega_menu.columns : [];

    const availableSlots = [];
    for (let slot = 0; slot < megaColumnsDesktop(app); slot += 1) {
      if (!megaColumnAtSlot(app, navIndex, slot)) {
        availableSlots.push(slot);
      }
    }

    const targetSlot = typeof slotIndex === 'number' && slotIndex >= 0 && slotIndex < megaColumnsDesktop(app) && !megaColumnAtSlot(app, navIndex, slotIndex)
      ? slotIndex
      : (availableSlots[0] != null ? availableSlots[0] : null);

    if (targetSlot == null) {
      return null;
    }

    const column = {
      id: 'mega-' + core.shortId(),
      slot: targetSlot,
      title: '',
      elements: []
    };

    item.mega_menu.columns.push(column);
    item.mega_menu.columns.sort(function (left, right) {
      return Number(left.slot || 0) - Number(right.slot || 0);
    });
    Studio.state.select(app, { kind: 'nav-item', navId: item.id });
    Studio.state.markDirty(app);

    return column;
  }

  function removeColumn(app, navIndex, columnIndex) {
    const item = navItem(app, navIndex);
    const column = megaColumn(app, navIndex, columnIndex);
    if (!item || !item.mega_menu || !Array.isArray(item.mega_menu.columns) || !column) {
      return false;
    }

    const index = item.mega_menu.columns.findIndex(function (entry) {
      return entry && entry.id === column.id;
    });
    if (index < 0) {
      return false;
    }

    item.mega_menu.columns.splice(index, 1);
    Studio.state.markDirty(app);

    return true;
  }

  function moveColumnToSlot(app, navIndex, columnIndex, slotIndex) {
    const item = navItem(app, navIndex);
    const column = megaColumn(app, navIndex, columnIndex);
    if (!item || !column || !item.mega_menu || !Array.isArray(item.mega_menu.columns)) {
      return false;
    }

    const targetSlot = Math.max(0, Math.min(megaColumnsDesktop(app) - 1, Number(slotIndex)));
    const occupied = megaColumnAtSlot(app, navIndex, targetSlot);
    if (occupied && occupied.id !== column.id) {
      occupied.slot = Number(column.slot || 0);
    }

    column.slot = targetSlot;
    item.mega_menu.columns.sort(function (left, right) {
      return Number(left.slot || 0) - Number(right.slot || 0);
    });
    Studio.state.markDirty(app);

    return true;
  }

  function moveColumnStep(app, navIndex, columnIndex, direction) {
    const column = megaColumn(app, navIndex, columnIndex);
    if (!column) {
      return false;
    }

    const nextSlot = direction === 'up'
      ? Number(column.slot || 0) - 1
      : Number(column.slot || 0) + 1;

    if (nextSlot < 0 || nextSlot >= megaColumnsDesktop(app)) {
      return false;
    }

    return moveColumnToSlot(app, navIndex, columnIndex, nextSlot);
  }

  function addElementToColumn(app, navIndex, columnIndex, type, elementIndex) {
    const column = megaColumn(app, navIndex, columnIndex);
    const definition = app.library.megaElements && app.library.megaElements[type] ? app.library.megaElements[type] : null;
    if (!column || !definition) {
      return null;
    }

    column.elements = Array.isArray(column.elements) ? column.elements : [];
    const element = core.deepClone(definition.defaults || {});
    element.id = 'mega-element-' + core.shortId();
    const index = typeof elementIndex === 'number'
      ? Math.max(0, Math.min(elementIndex, column.elements.length))
      : column.elements.length;

    column.elements.splice(index, 0, element);
    Studio.state.markDirty(app);

    return element;
  }

  function removeElementFromColumn(app, navIndex, columnIndex, elementIndex) {
    const column = megaColumn(app, navIndex, columnIndex);
    if (!column || !Array.isArray(column.elements) || elementIndex < 0 || elementIndex >= column.elements.length) {
      return false;
    }

    column.elements.splice(elementIndex, 1);
    Studio.state.markDirty(app);

    return true;
  }

  function moveElementInColumns(app, fromNavIndex, fromColumnIndex, fromElementIndex, toNavIndex, toColumnIndex, toElementIndex) {
    const fromColumn = megaColumn(app, fromNavIndex, fromColumnIndex);
    const toColumn = megaColumn(app, toNavIndex, toColumnIndex);
    if (!fromColumn || !toColumn || !Array.isArray(fromColumn.elements)) {
      return false;
    }

    const element = fromColumn.elements.splice(fromElementIndex, 1)[0];
    if (!element) {
      return false;
    }

    toColumn.elements = Array.isArray(toColumn.elements) ? toColumn.elements : [];
    const targetIndex = fromColumn === toColumn && fromElementIndex < toElementIndex ? toElementIndex - 1 : toElementIndex;
    toColumn.elements.splice(Math.max(0, Math.min(targetIndex, toColumn.elements.length)), 0, element);
    Studio.state.markDirty(app);

    return true;
  }

  function moveElementStep(app, navIndex, columnIndex, elementIndex, direction) {
    const column = megaColumn(app, navIndex, columnIndex);
    const elements = column && Array.isArray(column.elements) ? column.elements : [];

    if (elementIndex < 0 || elementIndex >= elements.length) {
      return false;
    }

    if (direction === 'up') {
      if (elementIndex === 0) {
        return false;
      }
      return moveElementInColumns(app, navIndex, columnIndex, elementIndex, navIndex, columnIndex, elementIndex - 1);
    }

    if (direction === 'down') {
      if (elementIndex >= elements.length - 1) {
        return false;
      }
      return moveElementInColumns(app, navIndex, columnIndex, elementIndex, navIndex, columnIndex, elementIndex + 2);
    }

    return false;
  }

  Studio.nav = {
    ROWS,
    ZONES,
    settings,
    megaColumnsDesktop,
    rows,
    navElements,
    navElement,
    findElementLocation,
    findElementByKind,
    defaultZone,
    addElement,
    removeElement,
    moveElement,
    findItemIndex,
    navItem,
    megaColumn,
    megaColumnAtSlot,
    megaColumnIndex,
    megaElement,
    activeMegaPreviewIndex,
    resolvePreviewIndex,
    addItem,
    removeItem,
    reorderItems,
    moveItem,
    addColumn,
    removeColumn,
    moveColumnToSlot,
    moveColumnStep,
    addElementToColumn,
    removeElementFromColumn,
    moveElementInColumns,
    moveElementStep
  };
})(window);
