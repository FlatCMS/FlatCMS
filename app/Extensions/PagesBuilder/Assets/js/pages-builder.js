/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const configHolder = document.getElementById('pagesBuilderConfig');
    if (!configHolder) return;

    let config = {};
    try {
        config = JSON.parse(configHolder.dataset.pagesBuilderConfig || '{}');
    } catch (e) {
        console.error('Invalid PagesBuilder config', e);
        return;
    }

    const widgetPreviewRegistry = (() => {
        if (window.FlatCMSWidgetPreviews && typeof window.FlatCMSWidgetPreviews === 'object') {
            return window.FlatCMSWidgetPreviews;
        }
        const registry = {};
        window.FlatCMSWidgetPreviews = registry;
        return registry;
    })();

    const saveBtn = document.getElementById('pbSaveBtn');
    const previewDraftBtn = document.getElementById('pbPreviewDraftBtn');
    const saveStatus = document.getElementById('pbSaveStatus');
    const canvas = document.getElementById('pbCanvas');
    const addSectionBtn = document.getElementById('pbAddSectionBtn');
    const editorRoot = document.getElementById('pbEditorRoot');
    const widgetsSidebar = document.getElementById('pbWidgetsSidebar');
    const settingsSidebar = document.getElementById('pbSettingsSidebar');
    const toggleWidgetsBtn = document.getElementById('pbToggleWidgets');
    const toggleInspectorBtn = document.getElementById('pbToggleInspector');
    const drawerOverlay = document.getElementById('pbDrawerOverlay');
    const builderTopbar = document.getElementById('pbBuilderTopbar');
    const topHeader = document.querySelector('.top-header');
    const catalog = document.getElementById('pbWidgetCatalog');
    const sourceCatalog = document.getElementById('pbSourceCatalog');
    const sourceAddSelectedBtn = document.getElementById('pbSourceAddSelected');
    const inspector = document.getElementById('pbInspector');
    const widgetSearch = document.getElementById('pbWidgetSearch');
    const sourceSearch = document.getElementById('pbSourceSearch');
    const deviceSwitch = document.getElementById('pbDeviceSwitch');
    const disableForm = document.getElementById('pbDisableForm');
    const modeBadge = document.getElementById('pbModeBadge');
    const adminSidebar = document.querySelector('.sidebar');
    const adminSidebarOverlay = document.querySelector('.sidebar-overlay');
    const pageTitleInput = document.getElementById('pbPageTitleInput');
    const pageSlugPreview = document.getElementById('pbPageSlugPreview');
    const pageMetaTitleInput = document.getElementById('pbPageMetaTitleInput');
    const pageMetaDescriptionInput = document.getElementById('pbPageMetaDescriptionInput');

    if (!canvas || !catalog || !inspector) {
        return;
    }

    const sharedPrimitives = window.FlatCMSUIPrimitives || {};
    const shellController = typeof sharedPrimitives.createBuilderShellController === 'function'
        ? sharedPrimitives.createBuilderShellController({
            root: editorRoot,
            overlay: drawerOverlay,
            topHeader: topHeader,
            topbar: builderTopbar,
            adminSidebar: adminSidebar,
            adminSidebarOverlay: adminSidebarOverlay,
            bodyClass: 'pb-editor-mode',
            offsetTargets: [editorRoot],
            offsetCssVar: '--pb-drawer-top',
            onOverlayUpdate: () => {
                if (!editorRoot) return;
                editorRoot.classList.remove('pb-overlay-active');
                if (drawerOverlay) {
                    drawerOverlay.classList.remove('is-active');
                    drawerOverlay.setAttribute('aria-hidden', 'true');
                }
            },
            sides: {
                left: {
                    toggleButton: toggleWidgetsBtn,
                    sidebar: widgetsSidebar,
                    sidebarOpenClass: 'is-open',
                    rootOpenClass: 'pb-left-open',
                    storageKey: 'flatcms_pb_left_open',
                    legacyCollapsedKey: 'flatcms_pb_sidebar_left',
                    openIconClass: 'fa-chevron-left',
                    closedIconClass: 'fa-chevron-right',
                },
                right: {
                    toggleButton: toggleInspectorBtn,
                    sidebar: settingsSidebar,
                    sidebarOpenClass: 'is-open',
                    rootOpenClass: 'pb-right-open',
                    storageKey: 'flatcms_pb_right_open',
                    legacyCollapsedKey: 'flatcms_pb_sidebar_right',
                    openIconClass: 'fa-chevron-right',
                    closedIconClass: 'fa-chevron-left',
                },
            },
        })
        : null;

    const widgetDefs = normalizeConfigWidgetDefs(
        []
            .concat(Array.isArray(config.widgetDefs) ? config.widgetDefs : [])
            .concat(Array.isArray(config.lockedWidgetDefs) ? config.lockedWidgetDefs : [])
    );

    const state = {
        builder: normalizeBuilder(config.builder),
        selection: null, // { kind: 'block', sectionId, columnId, blockId } | { kind: 'section', sectionId }
        inspectorMode: 'widget', // 'widget' | 'spacing'
        inspectorSearch: '',
        inspectorSheetTab: 'all',
        sectionInspectorTab: 'all',
        inspectorDensity: readInspectorDensity(),
        pendingInspectorFocus: null,
        inspectorFocusRequestId: 0,
        pendingCommitInspectorRefresh: false,
        inspectorCommitRefreshTimer: 0,
        inspectorPointerFieldInteraction: false,
        preferredInsertTarget: null, // { sectionId, columnId }
        sourceCatalog: [],
        sourceSelectedKeys: [],
        adminSidebarState: null,
        drag: {
            kind: null, // 'block' | 'section'
            sourceId: null,
            dropId: null,
            dropPosition: 'after',
        },
        device: 'desktop',
        isSaving: false,
        page: {
            title: pageTitleInput
                ? String(pageTitleInput.value || '')
                : String(config.pageTitle || ''),
            slug: String(config.pageSlug || ''),
            metaTitle: pageMetaTitleInput
                ? String(pageMetaTitleInput.value || '')
                : '',
            metaDescription: pageMetaDescriptionInput
                ? String(pageMetaDescriptionInput.value || '')
                : '',
        },
    };
    let builderPageId = String(config.pageId || '');
    const TEXT_STYLE_SUFFIX = Object.freeze({
        align: 'Align',
        font: 'Font',
        size: 'Size',
        bold: 'Bold',
        italic: 'Italic',
        underline: 'Underline',
        color: 'Color',
        list: 'List',
        icon: 'Icon',
        iconPosition: 'IconPosition',
    });
    const TEXT_STYLE_FONT_OPTIONS = ['inherit', 'system', 'sans', 'serif', 'mono', 'display'];
    const TEXT_STYLE_SIZE_OPTIONS = ['inherit', '12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'];

    let iconModal = null;
    let iconGrid = null;
    let iconSearch = null;
    let iconList = [];
    let iconLoaded = false;
    let iconSelectCallback = null;
    let iconSearchTimer = null;
    let iconCurrentValue = '';

    let quickAddOverlay = null;
    let quickAddPanel = null;
    let quickAddZone = null;
    let quickAddInsertIndex = 0;
    let quickAddView = 'actions';
    let quickAddSectionDraft = null;
    let templateGalleryOverlay = null;
    let templateGalleryInsertIndex = 0;
    let boxEditOverlay = null;
    let boxEditPanel = null;
    let boxEditBlockId = '';
    let boxEditAnchor = null;
    let sectionDropPlaceholder = null;
    let inspectorSheet = null;
    let inspectorSheetBody = null;
    let inspectorSidebarBody = inspector && inspector.parentNode ? inspector.parentNode : null;

    let catalogOpenGroupKey = '';
    let sourceOpenGroupKey = '';
    let lastCatalogSearchTerm = '';
    let lastSourceSearchTerm = '';

    init();

    function init() {
        syncFrontendPreviewThemeMode();
        ensureInspectorSheet();
        registerBuilderBridge();
        if (shellController) {
            shellController.init();
        } else {
            document.body.classList.add('pb-editor-mode');
            collapseAdminSidebarForBuilder();
            updateDrawerOffsets();
            restoreBuilderSidebarState();
        }
        if (shouldOpenPageSettingsOnInit()) {
            setSidebarState('right', { open: true }, { persist: false });
            consumeBuilderInitContext();
        }
        bindEvents();
        renderCatalog();
        renderSourceCatalog();
        syncPageMetaUi();
        ensureSelection();
        renderCanvas();
        renderInspector();
        publishBuilderState('init');
    }

    function shouldOpenPageSettingsOnInit() {
        try {
            const url = new URL(window.location.href);
            return String(url.searchParams.get('builder_context') || '').trim().toLowerCase() === 'create';
        } catch (e) {
            return false;
        }
    }

    function consumeBuilderInitContext() {
        if (!(window.history && typeof window.history.replaceState === 'function')) {
            return;
        }

        try {
            const url = new URL(window.location.href);
            if (!url.searchParams.has('builder_context')) {
                return;
            }

            url.searchParams.delete('builder_context');
            const nextQuery = url.searchParams.toString();
            const nextUrl = url.pathname + (nextQuery !== '' ? `?${nextQuery}` : '') + url.hash;
            window.history.replaceState(window.history.state, '', nextUrl);
        } catch (e) {
            // ignore malformed location state
        }
    }

    function syncFrontendPreviewThemeMode() {
        const shell = document.getElementById('pbEditorShell');
        if (!(shell instanceof HTMLElement) || typeof window.matchMedia !== 'function') {
            return;
        }

        const themeMedia = window.matchMedia('(prefers-color-scheme: light)');
        const applyThemeMode = () => {
            shell.classList.toggle('theme-light-init', !!themeMedia.matches);
        };

        applyThemeMode();

        if (themeMedia && themeMedia.addEventListener) {
            themeMedia.addEventListener('change', applyThemeMode);
            return;
        }

        if (themeMedia && themeMedia.addListener) {
            themeMedia.addListener(applyThemeMode);
        }
    }

    function bindEvents() {
        window.addEventListener('beforeunload', restoreAdminSidebarAfterBuilder, { once: true });
        window.addEventListener('pagehide', restoreAdminSidebarAfterBuilder, { once: true });

        if (saveBtn) {
            saveBtn.addEventListener('click', saveBuilder);
        }

        if (previewDraftBtn) {
            previewDraftBtn.addEventListener('click', (event) => {
                event.preventDefault();
                openDraftPreview();
            });
        }

        if (pageTitleInput) {
            pageTitleInput.addEventListener('input', () => {
                state.page.title = String(pageTitleInput.value || '');
                state.page.slug = buildAutoSlug(state.page.title);
                syncPageMetaUi();
            });
        }

        if (pageMetaTitleInput) {
            pageMetaTitleInput.addEventListener('input', () => {
                state.page.metaTitle = String(pageMetaTitleInput.value || '');
            });
        }

        if (pageMetaDescriptionInput) {
            pageMetaDescriptionInput.addEventListener('input', () => {
                state.page.metaDescription = String(pageMetaDescriptionInput.value || '');
            });
        }

        if (addSectionBtn) {
            addSectionBtn.addEventListener('click', () => {
                addSection(2);
            });
        }

        if (toggleWidgetsBtn) {
            toggleWidgetsBtn.addEventListener('click', () => toggleSidebar('left'));
        }

        if (toggleInspectorBtn) {
            toggleInspectorBtn.addEventListener('click', () => toggleSidebar('right'));
        }

        if (drawerOverlay) {
            drawerOverlay.addEventListener('click', () => closeOverlayDrawers());
        }

        window.addEventListener('resize', () => {
            updateDrawerOffsets();
            positionBlockBoxEditor();
        });

        if (widgetsSidebar) {
            const searchBtn = widgetsSidebar.querySelector('[data-role="pb-rail-search"]');
            if (searchBtn) {
                searchBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    setSidebarState('left', { open: true }, { persist: true });
                    window.requestAnimationFrame(() => {
                        if (widgetSearch) {
                            widgetSearch.focus();
                        }
                    });
                });
            }

            widgetsSidebar.querySelectorAll('[data-pb-cat]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const key = String(btn.dataset.pbCat || '').trim();
                    if (!key) return;

                    catalogOpenGroupKey = key;
                    lastCatalogSearchTerm = '';
                    if (widgetSearch) {
                        widgetSearch.value = '';
                    }
                    setSidebarState('left', { open: true }, { persist: true });
                    renderCatalog('');
                });
            });
        }

        if (widgetSearch) {
            widgetSearch.addEventListener('input', () => renderCatalog(widgetSearch.value || ''));
        }
        if (sourceSearch) {
            sourceSearch.addEventListener('input', () => renderSourceCatalog(sourceSearch.value || ''));
        }
        if (sourceAddSelectedBtn) {
            sourceAddSelectedBtn.addEventListener('click', () => {
                addSelectedSourceItems();
            });
        }

        if (inspector) {
            inspector.addEventListener('pointerdown', (event) => {
                cancelQueuedInspectorFocus();
                state.inspectorPointerFieldInteraction = isInspectorFieldInteractionTarget(event.target);
                if (state.inspectorPointerFieldInteraction) {
                    return;
                }
                schedulePendingCommitInspectorRefresh();
            }, true);
        }

        document.addEventListener('focusin', (event) => {
            if (state.inspectorPointerFieldInteraction && inspector && inspector.contains(event.target)) {
                state.inspectorPointerFieldInteraction = false;
            }
            schedulePendingCommitInspectorRefresh();
        }, true);
        document.addEventListener('pointerup', (event) => {
            const releaseDeferredRefresh = state.inspectorPointerFieldInteraction
                && inspector
                && inspector.contains(event.target);
            if (releaseDeferredRefresh) {
                window.setTimeout(() => {
                    state.inspectorPointerFieldInteraction = false;
                    schedulePendingCommitInspectorRefresh();
                }, 0);
                return;
            }
            schedulePendingCommitInspectorRefresh();
        }, true);

        if (deviceSwitch) {
            deviceSwitch.addEventListener('click', (event) => {
                const btn = event.target.closest('[data-device]');
                if (!btn) return;
                const device = btn.dataset.device || 'desktop';
                state.device = ['desktop', 'tablet', 'mobile'].includes(device) ? device : 'desktop';
                deviceSwitch.querySelectorAll('[data-device]').forEach((node) => {
                    node.classList.toggle('is-active', node === btn);
                });
                canvas.classList.remove('is-tablet', 'is-mobile');
                if (state.device === 'tablet') {
                    canvas.classList.add('is-tablet');
                } else if (state.device === 'mobile') {
                    canvas.classList.add('is-mobile');
                }
            });
        }

        canvas.addEventListener('dragover', (event) => {
            event.preventDefault();

            if (dragHasType(event, 'application/x-pagesbuilder-section')) {
                const sectionsInCanvas = Array.from(canvas.querySelectorAll('.pb-section'));
                const lastSection = sectionsInCanvas.length ? sectionsInCanvas[sectionsInCanvas.length - 1] : null;
                if (lastSection) {
                    state.drag.dropId = String(lastSection.dataset.sectionId || '');
                    state.drag.dropPosition = 'after';
                    placeSectionDropPlaceholder(lastSection, false);
                }
            }
        });

        canvas.addEventListener('drop', (event) => {
            event.preventDefault();
            clearSectionDropIndicators();

            const sectionId = event.dataTransfer.getData('application/x-pagesbuilder-section') || String(state.drag.sourceId || '');
            if (sectionId) {
                const targetId = String(state.drag.dropId || '');
                const position = state.drag.dropPosition || 'after';
                if (targetId && targetId !== sectionId) {
                    moveSection(sectionId, targetId, position);
                    return;
                }

                const sections = getSections();
                if (sections.length) {
                    const lastId = String(sections[sections.length - 1].id || '');
                    if (lastId && lastId !== sectionId) {
                        moveSection(sectionId, lastId, 'after');
                    }
                }
                return;
            }

            const sourceIndex = event.dataTransfer.getData('application/x-pagesbuilder-source');
            const widgetType = event.dataTransfer.getData('application/x-pagesbuilder-widget');
            const widgetSettings = getDraggedWidgetInitialSettings(event.dataTransfer);
            const blockId = event.dataTransfer.getData('application/x-pagesbuilder-block');

            if (sourceIndex !== '') {
                appendSourceItemToEnd(sourceIndex);
                return;
            }

            if (widgetType) {
                appendWidgetToEnd(widgetType, widgetSettings);
                return;
            }

            if (blockId) {
                moveBlockToCanvasEnd(blockId);
            }
        });

        document.addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                saveBuilder();
            }
            if (event.key === 'Escape') {
                if (isGlobalConfirmModalOpen()) {
                    return;
                }
                if (isBoxEditorOpen()) {
                    closeBlockBoxEditor();
                    return;
                }
                if (isInspectorSheetOpen()) {
                    toggleInspectorPanelMode();
                    return;
                }
                if (isSidebarOpen('left') || isSidebarOpen('right')) {
                    closeOverlayDrawers();
                }
            }
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'b') {
                const target = event.target;
                const isTyping = target && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
                if (isTyping) return;
                event.preventDefault();
                toggleSidebar('left');
            }
        });

        if (disableForm) {
            disableForm.addEventListener('submit', (event) => {
                const message = label('confirmDisable', 'Disable builder for this page?');
                if (!confirm(message)) {
                    event.preventDefault();
                }
            });
        }
    }

    function collapseAdminSidebarForBuilder() {
        if (shellController) {
            shellController.collapseAdminSidebar();
            return;
        }

        if (!adminSidebar || state.adminSidebarState) {
            return;
        }

        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        state.adminSidebarState = {
            wasCollapsed: adminSidebar.classList.contains('collapsed'),
            wasOpen: adminSidebar.classList.contains('open'),
            overlayWasActive: sidebarOverlay ? sidebarOverlay.classList.contains('active') : false,
            bodyOverflow: document.body.style.overflow || '',
        };

        adminSidebar.classList.add('collapsed');
        adminSidebar.classList.remove('open');

        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('active');
        }

        if (document.body.style.overflow === 'hidden') {
            document.body.style.overflow = '';
        }
    }

    function restoreAdminSidebarAfterBuilder() {
        closeInspectorSheet({ restoreSidebar: false });
        if (shellController) {
            shellController.restoreAdminSidebar();
            return;
        }
        document.body.classList.remove('pb-editor-mode');

        if (!adminSidebar || !state.adminSidebarState) {
            return;
        }

        const sidebarOverlay = document.querySelector('.sidebar-overlay');
        const previousState = state.adminSidebarState;
        state.adminSidebarState = null;

        adminSidebar.classList.toggle('collapsed', previousState.wasCollapsed);
        adminSidebar.classList.toggle('open', previousState.wasOpen);

        if (sidebarOverlay) {
            sidebarOverlay.classList.toggle('active', previousState.overlayWasActive);
        }

        document.body.style.overflow = previousState.bodyOverflow;
    }

    function ensureInspectorSheet() {
        if (inspectorSheet) {
            return;
        }

        if (typeof sharedPrimitives.createBuilderInspectorSheet === 'function') {
            const inspectorSheetApi = sharedPrimitives.createBuilderInspectorSheet({
                title: label('builder_inspector_sheet_title', 'Inspecteur étendu'),
                closeLabel: label('quickAddClose', 'Fermer'),
                sheetClass: 'pb-inspector-sheet',
                backdropClass: 'pb-inspector-sheet-backdrop',
                panelClass: 'pb-inspector-sheet-panel',
                headClass: 'pb-inspector-sheet-head',
                titleClass: 'pb-inspector-sheet-title',
                bodyClass: 'pb-inspector-sheet-body',
                onRequestClose: () => {
                    toggleInspectorPanelMode();
                },
            });
            inspectorSheet = inspectorSheetApi.element;
            inspectorSheetBody = inspectorSheetApi.body;
            document.body.appendChild(inspectorSheet);
            return;
        }

        inspectorSheet = document.createElement('div');
        inspectorSheet.className = 'pb-inspector-sheet';
        inspectorSheet.setAttribute('aria-hidden', 'true');
        inspectorSheet.innerHTML = `
            <div class="pb-inspector-sheet-backdrop" data-action="close-sheet"></div>
            <section class="pb-inspector-sheet-panel" role="dialog" aria-label="${escapeAttr(label('builder_inspector_sheet_title', 'Inspecteur étendu'))}">
                <header class="pb-inspector-sheet-head">
                    <h3 class="pb-inspector-sheet-title">${escapeHtml(label('builder_inspector_sheet_title', 'Inspecteur étendu'))}</h3>
                    <button type="button" class="btn btn-ghost btn-sm" data-action="close-sheet" aria-label="${escapeAttr(label('quickAddClose', 'Fermer'))}">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </header>
                <div class="pb-inspector-sheet-body" id="pbInspectorSheetBody"></div>
            </section>
        `;

        document.body.appendChild(inspectorSheet);
        inspectorSheetBody = inspectorSheet.querySelector('#pbInspectorSheetBody');

        inspectorSheet.addEventListener('click', (event) => {
            const closeBtn = event.target && event.target.closest ? event.target.closest('[data-action="close-sheet"]') : null;
            if (closeBtn) {
                event.preventDefault();
                toggleInspectorPanelMode();
            }
        });
    }

    function isInspectorSheetOpen() {
        return !!(inspectorSheet && inspectorSheet.classList.contains('is-open'));
    }

    function openInspectorSheet() {
        ensureInspectorSheet();
        if (!inspector || !inspectorSheet || !inspectorSheetBody) {
            return;
        }

        if (inspector.parentNode !== inspectorSheetBody) {
            inspectorSheetBody.appendChild(inspector);
        }
        inspector.classList.add('is-sheet-mode');
        inspectorSheet.classList.add('is-open');
        inspectorSheet.setAttribute('aria-hidden', 'false');
        document.body.classList.add('pb-inspector-sheet-open');
    }

    function closeInspectorSheet(options) {
        if (inspector && inspectorSidebarBody && inspector.parentNode !== inspectorSidebarBody) {
            inspectorSidebarBody.appendChild(inspector);
        }

        if (inspector) {
            inspector.classList.remove('is-sheet-mode');
        }

        if (inspectorSheet) {
            inspectorSheet.classList.remove('is-open');
            inspectorSheet.setAttribute('aria-hidden', 'true');
        }
        document.body.classList.remove('pb-inspector-sheet-open');
        state.inspectorSearch = '';
        setInspectorMode('widget');
    }

    function toggleInspectorPanelMode() {
        if (isInspectorSheetOpen()) {
            closeInspectorSheet({ restoreSidebar: false });
        } else {
            openInspectorSheet();
        }
        renderInspector();
    }

    function updateDrawerOffsets() {
        if (shellController) {
            shellController.updateOffsets();
            return;
        }

        if (!editorRoot) return;
        const headerHeight = topHeader ? topHeader.getBoundingClientRect().height : 0;
        const topbarHeight = builderTopbar ? builderTopbar.getBoundingClientRect().height : 0;
        const total = Math.max(0, Math.round(headerHeight + topbarHeight));
        editorRoot.style.setProperty('--pb-drawer-top', `${total}px`);
    }

    function restoreBuilderSidebarState() {
        if (shellController) {
            shellController.restoreDrawerState({ update: false });
            return;
        }

        const leftState = readSidebarState('left');
        const rightState = readSidebarState('right');
        applySidebarState('left', leftState, { persist: false });
        applySidebarState('right', rightState, { persist: false });
        updateDrawerOverlayState();
    }

    function toggleSidebar(side) {
        const nextOpen = !isSidebarOpen(side);
        setSidebarState(side, { open: nextOpen }, { persist: true });
    }

    function isSidebarOpen(side) {
        if (shellController) {
            return shellController.isOpen(side);
        }

        if (!editorRoot) return false;
        if (side === 'left') return editorRoot.classList.contains('pb-left-open');
        if (side === 'right') return editorRoot.classList.contains('pb-right-open');
        return false;
    }

    function setSidebarState(side, patch, options) {
        const current = {
            open: isSidebarOpen(side),
        };
        const next = Object.assign({}, current, patch || {});
        applySidebarState(side, next, options);
    }

    function applySidebarState(side, sidebarState, options) {
        if (shellController) {
            shellController.applyState(side, sidebarState, options);
            return;
        }

        const opts = options || {};
        const shouldPersist = !!opts.persist;
        const nextOpen = !!(sidebarState && sidebarState.open);

        if (!editorRoot) return;

        if (side === 'left') {
            editorRoot.classList.toggle('pb-left-open', nextOpen);
            if (widgetsSidebar) widgetsSidebar.classList.toggle('is-open', nextOpen);
            updateToggleIcon(toggleWidgetsBtn, nextOpen ? 'fa-chevron-left' : 'fa-chevron-right');
        }

        if (side === 'right') {
            editorRoot.classList.toggle('pb-right-open', nextOpen);
            if (settingsSidebar) settingsSidebar.classList.toggle('is-open', nextOpen);
            updateToggleIcon(toggleInspectorBtn, nextOpen ? 'fa-chevron-right' : 'fa-chevron-left');
        }

        if (shouldPersist) {
            try {
                if (side === 'left') {
                    window.localStorage.setItem('flatcms_pb_left_open', nextOpen ? '1' : '0');
                }
                if (side === 'right') {
                    window.localStorage.setItem('flatcms_pb_right_open', nextOpen ? '1' : '0');
                }
            } catch (e) {
                // ignore
            }
        }

        updateDrawerOverlayState();
    }

    function updateToggleIcon(button, iconClass) {
        if (!button) return;
        const icon = button.querySelector('i');
        if (!icon) return;
        icon.classList.remove('fa-chevron-left', 'fa-chevron-right');
        icon.classList.add(iconClass);
    }

    function updateDrawerOverlayState() {
        if (shellController) {
            shellController.updateOverlayState();
            return;
        }

        if (!editorRoot) return;
        // PagesBuilder stays interactive even with sidebars open (Elementor-like).
        // Keep the overlay disabled to avoid blocking drag/drop and canvas controls.
        editorRoot.classList.remove('pb-overlay-active');
        if (drawerOverlay) {
            drawerOverlay.classList.remove('is-active');
            drawerOverlay.setAttribute('aria-hidden', 'true');
        }
    }

    function closeOverlayDrawers() {
        if (shellController) {
            shellController.closeAll({ persist: true });
            return;
        }

        if (!editorRoot) return;

        if (editorRoot.classList.contains('pb-left-open')) {
            setSidebarState('left', { open: false }, { persist: true });
        }
        if (editorRoot.classList.contains('pb-right-open')) {
            setSidebarState('right', { open: false }, { persist: true });
        }
    }

    function readSidebarState(side) {
        if (shellController) {
            return shellController.readState(side);
        }

        const openRaw = readNullableBoolStorage(side === 'left' ? 'flatcms_pb_left_open' : 'flatcms_pb_right_open');
        let open = openRaw;

        // Migration from older "collapsed" keys: 'flatcms_pb_sidebar_left/right'.
        if (open === null) {
            const legacyKey = side === 'left' ? 'flatcms_pb_sidebar_left' : 'flatcms_pb_sidebar_right';
            const legacyCollapsed = readNullableBoolStorage(legacyKey);
            if (legacyCollapsed !== null) {
                const legacyOpen = !legacyCollapsed;
                if (open === null) open = legacyOpen;
            }
        }

        return {
            open: open === null ? false : open,
        };
    }

    function readNullableBoolStorage(key) {
        try {
            const raw = window.localStorage.getItem(String(key || ''));
            if (raw === null) return null;
            if (raw === '1' || raw === 'true') return true;
            if (raw === '0' || raw === 'false') return false;
            return null;
        } catch (e) {
            return null;
        }
    }

    function normalizeInspectorDensity(mode) {
        return String(mode || '').trim().toLowerCase() === 'full' ? 'full' : 'basic';
    }

    function readInspectorDensity() {
        try {
            const raw = window.localStorage.getItem('flatcms_pb_inspector_density');
            return normalizeInspectorDensity(raw || 'basic');
        } catch (e) {
            return 'basic';
        }
    }

    function normalizeBuilder(input) {
        const versionRaw = Number(input && input.version ? input.version : 2);
        const sectionsInput = Array.isArray(input && input.sections) ? input.sections : null;

        // Backward compatibility (v1): { blocks: [...] }
        if (!sectionsInput) {
            const blocksInput = Array.isArray(input && input.blocks) ? input.blocks : [];
            const normalizedBlocks = normalizeBlocks(blocksInput);
            if (!normalizedBlocks.length) {
                return { version: 2, sections: [] };
            }

            return {
                version: 2,
                sections: [
                    {
                        id: makeId('sec'),
                        layoutTemplate: buildEqualSectionTemplate(1),
                        settings: normalizeSectionSettings({}),
                        columns: [
                            { id: makeId('col'), blocks: normalizedBlocks },
                        ],
                    },
                ],
            };
        }

        const sections = [];
        sectionsInput.forEach((section) => {
            if (!section || typeof section !== 'object') return;

            const sectionId = String(section.id || makeId('sec'));
            const columnsInput = Array.isArray(section.columns) ? section.columns : [];
            const columns = [];

            columnsInput.slice(0, 4).forEach((column) => {
                if (!column || typeof column !== 'object') return;
                columns.push({
                    id: String(column.id || makeId('col')),
                    blocks: normalizeBlocks(column.blocks),
                });
            });

            if (!columns.length) {
                columns.push({ id: makeId('col'), blocks: [] });
            }

            sections.push({
                id: sectionId,
                layoutTemplate: sanitizeSectionLayoutTemplate(
                    String((section.layoutTemplate || section.template || '')),
                    columns.length || 1
                ),
                settings: normalizeSectionSettings(section.settings || {}),
                columns: columns,
            });
        });

        return {
            version: versionRaw >= 2 ? versionRaw : 2,
            sections: sections.slice(0, 60),
        };
    }

    function normalizeBlocks(blocksInput) {
        const blocks = Array.isArray(blocksInput) ? blocksInput : [];
        return blocks
            .filter((block) => block && typeof block === 'object' && String(block.type || '').trim() !== '')
            .map((block) => {
                const type = String(block.type || '');
                const settings = applyWidgetDefaults(type, block.settings || {});
                normalizeWidgetLinkedRepeaters(type, settings, { compact: true });
                return {
                    id: String(block.id || makeId()),
                    type: type,
                    settings: settings,
                };
            })
            .slice(0, 200);
    }

    function normalizeWidgetLinkedRepeaters(type, settings, options) {
        const safeType = String(type || '').trim().toLowerCase();
        if ((safeType !== 'feature_grid' && safeType !== 'snap_cards' && safeType !== 'carousel' && safeType !== 'nw_carrousel' && safeType !== 'stats_section' && safeType !== 'logo_cloud' && safeType !== 'faq_accordion' && safeType !== 'testimonial_cards' && safeType !== 'pricing_plans') || !settings || typeof settings !== 'object') {
            return;
        }

        const opts = options || {};
        const compact = opts.compact !== false;
        const minLength = Math.max(0, Number(opts.minLength || 0));
        const delimiter = '\n';

        const findLastNonEmptyIndex = (values) => {
            for (let index = values.length - 1; index >= 0; index -= 1) {
                if (String(values[index] || '').trim() !== '') {
                    return index;
                }
            }
            return -1;
        };

        const sanitize = (values) => (Array.isArray(values) ? values : []).map((item) => String(item || '').trim());

        if (safeType === 'faq_accordion') {
            const faqDelimiter = '\n---\n';
            const parseFaqValues = (value) => {
                const raw = String(value || '');
                if (raw.includes(faqDelimiter)) {
                    return parseRepeaterValues(raw, faqDelimiter);
                }
                return parseRepeaterValues(raw, delimiter);
            };

            const questions = parseFaqValues(settings.questions || '');
            const answers = parseFaqValues(settings.answers || '');

            const safeQuestions = sanitize(questions);
            const safeAnswers = sanitize(answers);

            let keepLength = Math.max(safeQuestions.length, safeAnswers.length);
            if (compact) {
                const questionLast = findLastNonEmptyIndex(safeQuestions);
                const answerLast = findLastNonEmptyIndex(safeAnswers);
                if (questionLast >= 0) {
                    keepLength = questionLast + 1;
                } else {
                    keepLength = answerLast + 1;
                }
            }
            keepLength = Math.max(1, Math.min(12, Math.max(keepLength, minLength)));

            const nextQuestions = [];
            const nextAnswers = [];
            for (let index = 0; index < keepLength; index += 1) {
                nextQuestions.push(String(safeQuestions[index] || '').trim());
                nextAnswers.push(String(safeAnswers[index] || '').trim());
            }

            settings.questions = serializeRepeaterValues(nextQuestions, faqDelimiter);
            settings.answers = serializeRepeaterValues(nextAnswers, faqDelimiter);
            return;
        }

        if (safeType === 'stats_section') {
            const values = parseRepeaterValues(settings.values || '', delimiter);
            const labels = parseRepeaterValues(settings.labels || '', delimiter);
            const notes = parseRepeaterValues(settings.notes || '', delimiter);

            const safeValues = sanitize(values);
            const safeLabels = sanitize(labels);
            const safeNotes = sanitize(notes);

            let keepLength = Math.max(
                safeValues.length,
                safeLabels.length,
                safeNotes.length
            );
            if (compact) {
                const valueLast = findLastNonEmptyIndex(safeValues);
                const labelLast = findLastNonEmptyIndex(safeLabels);
                const noteLast = findLastNonEmptyIndex(safeNotes);
                if (valueLast >= 0) {
                    keepLength = valueLast + 1;
                } else {
                    keepLength = Math.max(labelLast, noteLast) + 1;
                }
            }
            keepLength = Math.max(1, Math.min(8, Math.max(keepLength, minLength)));

            const nextValues = [];
            const nextLabels = [];
            const nextNotes = [];

            for (let index = 0; index < keepLength; index += 1) {
                nextValues.push(String(safeValues[index] || '').trim());
                nextLabels.push(String(safeLabels[index] || '').trim());
                nextNotes.push(String(safeNotes[index] || '').trim());
            }

            settings.values = serializeRepeaterValues(nextValues, delimiter);
            settings.labels = serializeRepeaterValues(nextLabels, delimiter);
            settings.notes = serializeRepeaterValues(nextNotes, delimiter);
            return;
        }

        if (safeType === 'logo_cloud') {
            const labels = parseRepeaterValues(settings.labels || '', delimiter);
            const logos = parseRepeaterValues(settings.logos || '', delimiter);
            const links = parseRepeaterValues(settings.links || '', delimiter);
            const targets = parseRepeaterValues(settings.targets || '', delimiter);

            const safeLabels = sanitize(labels);
            const safeLogos = sanitize(logos);
            const safeLinks = sanitize(links);
            const safeTargets = sanitize(targets);

            let keepLength = Math.max(
                safeLabels.length,
                safeLogos.length,
                safeLinks.length,
                safeTargets.length
            );
            if (compact) {
                const labelLast = findLastNonEmptyIndex(safeLabels);
                const logoLast = findLastNonEmptyIndex(safeLogos);
                const linkLast = findLastNonEmptyIndex(safeLinks);
                const targetLast = findLastNonEmptyIndex(safeTargets);
                if (labelLast >= 0) {
                    keepLength = labelLast + 1;
                } else {
                    keepLength = Math.max(logoLast, linkLast, targetLast) + 1;
                }
            }
            keepLength = Math.max(1, Math.min(13, Math.max(keepLength, minLength)));

            const nextLabels = [];
            const nextLogos = [];
            const nextLinks = [];
            const nextTargets = [];

            for (let index = 0; index < keepLength; index += 1) {
                const nextLink = String(safeLinks[index] || '').trim();
                nextLabels.push(String(safeLabels[index] || '').trim());
                nextLogos.push(String(safeLogos[index] || '').trim());
                nextLinks.push(nextLink);
                nextTargets.push(normalizeLinkTarget(String(safeTargets[index] || '_self'), nextLink));
            }

            settings.labels = serializeRepeaterValues(nextLabels, delimiter);
            settings.logos = serializeRepeaterValues(nextLogos, delimiter);
            settings.links = serializeRepeaterValues(nextLinks, delimiter);
            settings.targets = serializeRepeaterValues(nextTargets, delimiter);
            return;
        }

        if (safeType === 'testimonial_cards') {
            const quoteDelimiter = '\n---\n';
            const quotes = parseRepeaterValues(settings.quotes || '', quoteDelimiter);
            const names = parseRepeaterValues(settings.names || '', delimiter);
            const companies = parseRepeaterValues(settings.companies || '', delimiter);
            const roles = parseRepeaterValues(settings.roles || '', delimiter);
            const ratings = parseRepeaterValues(settings.ratings || '', delimiter);
            const avatars = parseRepeaterValues(settings.avatars || '', delimiter);
            const links = parseRepeaterValues(settings.links || '', delimiter);
            const targets = parseRepeaterValues(settings.targets || '', delimiter);

            const safeQuotes = sanitize(quotes);
            const safeNames = sanitize(names);
            const safeCompanies = sanitize(companies);
            const safeRoles = sanitize(roles);
            const safeRatings = sanitize(ratings).map((item) => String(normalizeTestimonialRating(item)));
            const safeAvatars = sanitize(avatars);
            const safeLinks = sanitize(links);
            const safeTargets = sanitize(targets);

            let keepLength = Math.max(
                safeQuotes.length,
                safeNames.length,
                safeCompanies.length,
                safeRoles.length,
                safeRatings.length,
                safeAvatars.length,
                safeLinks.length,
                safeTargets.length
            );
            if (compact) {
                const quoteLast = findLastNonEmptyIndex(safeQuotes);
                const nameLast = findLastNonEmptyIndex(safeNames);
                const companyLast = findLastNonEmptyIndex(safeCompanies);
                const roleLast = findLastNonEmptyIndex(safeRoles);
                const ratingLast = findLastNonEmptyIndex(safeRatings);
                const avatarLast = findLastNonEmptyIndex(safeAvatars);
                const linkLast = findLastNonEmptyIndex(safeLinks);
                if (quoteLast >= 0) {
                    keepLength = quoteLast + 1;
                } else {
                    keepLength = Math.max(nameLast, companyLast, roleLast, ratingLast, avatarLast, linkLast) + 1;
                }
            }
            keepLength = Math.max(1, Math.min(20, Math.max(keepLength, minLength)));

            const nextQuotes = [];
            const nextNames = [];
            const nextCompanies = [];
            const nextRoles = [];
            const nextRatings = [];
            const nextAvatars = [];
            const nextLinks = [];
            const nextTargets = [];

            for (let index = 0; index < keepLength; index += 1) {
                const nextLink = String(safeLinks[index] || '').trim();
                nextQuotes.push(String(safeQuotes[index] || '').trim());
                nextNames.push(String(safeNames[index] || '').trim());
                nextCompanies.push(String(safeCompanies[index] || '').trim());
                nextRoles.push(String(safeRoles[index] || '').trim());
                nextRatings.push(String(normalizeTestimonialRating(safeRatings[index] || '5')));
                nextAvatars.push(String(safeAvatars[index] || '').trim());
                nextLinks.push(nextLink);
                nextTargets.push(normalizeLinkTarget(String(safeTargets[index] || '_self'), nextLink));
            }

            settings.quotes = serializeRepeaterValues(nextQuotes, quoteDelimiter);
            settings.names = serializeRepeaterValues(nextNames, delimiter);
            settings.companies = serializeRepeaterValues(nextCompanies, delimiter);
            settings.roles = serializeRepeaterValues(nextRoles, delimiter);
            settings.ratings = serializeRepeaterValues(nextRatings, delimiter);
            settings.avatars = serializeRepeaterValues(nextAvatars, delimiter);
            settings.links = serializeRepeaterValues(nextLinks, delimiter);
            settings.targets = serializeRepeaterValues(nextTargets, delimiter);
            return;
        }

        if (safeType === 'feature_grid') {
            const titles = parseRepeaterValues(settings.titles || '', delimiter);
            const texts = parseFeatureGridTextValues(settings.texts || '');
            const icons = parseRepeaterValues(settings.icons || '', delimiter);
            const iconEnableds = parseRepeaterValues(settings.iconEnableds || '', delimiter);
            const iconAligns = parseRepeaterValues(settings.iconAligns || '', delimiter);
            const links = parseRepeaterValues(settings.links || '', delimiter);
            const buttonEnableds = parseRepeaterValues(settings.buttonEnableds || '', delimiter);
            const buttonLabels = parseRepeaterValues(settings.buttonLabels || '', delimiter);
            const buttonTargets = parseRepeaterValues(settings.buttonTargets || '', delimiter);
            const buttonVariants = parseRepeaterValues(settings.buttonVariants || '', delimiter);
            const buttonAligns = parseRepeaterValues(settings.buttonAligns || '', delimiter);

            const safeTitles = sanitize(titles);
            const safeTexts = sanitize(texts);
            const safeIcons = sanitize(icons);
            const safeIconEnableds = sanitize(iconEnableds).map((item) => normalizeToggleSettingValue(item, 'on'));
            const safeIconAligns = sanitize(iconAligns).map((item) => normalizeAlign(item));
            const safeLinks = sanitize(links);
            const safeButtonEnableds = sanitize(buttonEnableds).map((item) => normalizeFeatureGridButtonEnabled(item, 'off'));
            const safeButtonLabels = sanitize(buttonLabels);
            const safeButtonTargets = sanitize(buttonTargets);
            const safeButtonVariants = sanitize(buttonVariants).map((item) => normalizeFeatureGridButtonVariant(item));
            const safeButtonAligns = sanitize(buttonAligns).map((item) => normalizeAlign(item));
            const legacyButtonLabel = String(settings.buttonLabel || '').trim();
            const legacyShowFooter = normalizeTextStyleToggle(settings.showFooter, false);
            const baseAlign = normalizeAlign(String(settings.align || 'left'));

            let keepLength = Math.max(
                safeTitles.length,
                safeTexts.length,
                safeIcons.length,
                safeIconAligns.length,
                safeLinks.length,
                safeButtonEnableds.length,
                safeButtonLabels.length,
                safeButtonTargets.length,
                safeButtonVariants.length,
                safeButtonAligns.length
            );
            if (compact) {
                const titleLast = findLastNonEmptyIndex(safeTitles);
                const textLast = findLastNonEmptyIndex(safeTexts);
                const iconLast = findLastNonEmptyIndex(safeIcons);
                const iconAlignLast = findLastNonEmptyIndex(safeIconAligns);
                const linkLast = findLastNonEmptyIndex(safeLinks);
                const buttonEnabledLast = findLastNonEmptyIndex(safeButtonEnableds);
                const buttonLabelLast = findLastNonEmptyIndex(safeButtonLabels);
                const buttonTargetLast = findLastNonEmptyIndex(safeButtonTargets);
                const buttonVariantLast = findLastNonEmptyIndex(safeButtonVariants);
                const buttonAlignLast = findLastNonEmptyIndex(safeButtonAligns);
                if (titleLast >= 0) {
                    keepLength = titleLast + 1;
                } else {
                    keepLength = Math.max(textLast, iconLast, iconAlignLast, linkLast, buttonEnabledLast, buttonLabelLast, buttonTargetLast, buttonVariantLast, buttonAlignLast) + 1;
                }
            }
            keepLength = Math.max(0, Math.min(8, keepLength), minLength);

            const nextTitles = [];
            const nextTexts = [];
            const nextIcons = [];
            const nextIconEnableds = [];
            const nextIconAligns = [];
            const nextLinks = [];
            const nextButtonEnableds = [];
            const nextButtonLabels = [];
            const nextButtonTargets = [];
            const nextButtonVariants = [];
            const nextButtonAligns = [];

            for (let index = 0; index < keepLength; index += 1) {
                nextTitles.push(String(safeTitles[index] || '').trim());
                nextTexts.push(String(safeTexts[index] || '').trim());
                nextIcons.push(String(safeIcons[index] || '').trim());
                nextIconEnableds.push(normalizeToggleSettingValue(String(safeIconEnableds[index] || 'on'), 'on'));
                nextIconAligns.push(normalizeAlign(String(safeIconAligns[index] || baseAlign), baseAlign));
                nextLinks.push(String(safeLinks[index] || '').trim());
                const defaultEnabled = legacyShowFooter ? 'on' : 'off';
                nextButtonEnableds.push(normalizeFeatureGridButtonEnabled(String(safeButtonEnableds[index] || ''), defaultEnabled));
                const nextLabel = String(safeButtonLabels[index] || '').trim() || legacyButtonLabel;
                nextButtonLabels.push(nextLabel);
                nextButtonTargets.push(normalizeLinkTarget(String(safeButtonTargets[index] || ''), nextLinks[index]));
                nextButtonVariants.push(normalizeFeatureGridButtonVariant(String(safeButtonVariants[index] || 'ghost')));
                nextButtonAligns.push(normalizeAlign(String(safeButtonAligns[index] || baseAlign), baseAlign));
            }

            settings.titles = serializeRepeaterValues(nextTitles, delimiter);
            settings.texts = serializeFeatureGridTextValues(nextTexts);
            settings.icons = serializeRepeaterValues(nextIcons, delimiter);
            settings.iconEnableds = serializeRepeaterValues(nextIconEnableds, delimiter);
            settings.iconAligns = serializeRepeaterValues(nextIconAligns, delimiter);
            settings.links = serializeRepeaterValues(nextLinks, delimiter);
            settings.buttonEnableds = serializeRepeaterValues(nextButtonEnableds, delimiter);
            settings.buttonLabels = serializeRepeaterValues(nextButtonLabels, delimiter);
            settings.buttonTargets = serializeRepeaterValues(nextButtonTargets, delimiter);
            settings.buttonVariants = serializeRepeaterValues(nextButtonVariants, delimiter);
            settings.buttonAligns = serializeRepeaterValues(nextButtonAligns, delimiter);
            return;
        }

        if (safeType === 'carousel') {
            const titles = parseRepeaterValues(settings.titles || '', delimiter);
            const texts = parseRepeaterValues(settings.texts || '', delimiter);
            const images = parseRepeaterValues(settings.images || '', delimiter);
            const links = parseRepeaterValues(settings.links || '', delimiter);
            const buttonEnableds = parseRepeaterValues(settings.buttonEnableds || '', delimiter);
            const buttonLabels = parseRepeaterValues(settings.buttonLabels || '', delimiter);
            const buttonTargets = parseRepeaterValues(settings.buttonTargets || '', delimiter);
            const buttonAligns = parseRepeaterValues(settings.buttonAligns || '', delimiter);
            const globalButtonLabel = String(settings.buttonLabel || '').trim();
            const globalTarget = ['_self', '_blank'].includes(String(settings.target || '').trim())
                ? String(settings.target || '').trim()
                : '_self';
            settings.target = globalTarget;

            const safeTitles = sanitize(titles);
            const safeTexts = sanitize(texts);
            const safeImages = sanitize(images);
            const safeLinks = sanitize(links);
            const safeButtonEnableds = sanitize(buttonEnableds);
            const safeButtonLabels = sanitize(buttonLabels);
            const safeButtonTargets = sanitize(buttonTargets);
            const safeButtonAligns = sanitize(buttonAligns).map((item) => normalizeAlign(item));

            let keepLength = Math.max(
                safeTitles.length,
                safeTexts.length,
                safeImages.length,
                safeLinks.length,
                safeButtonEnableds.length,
                safeButtonLabels.length,
                safeButtonTargets.length,
                safeButtonAligns.length
            );
            if (compact) {
                const titleLast = findLastNonEmptyIndex(safeTitles);
                const textLast = findLastNonEmptyIndex(safeTexts);
                const imageLast = findLastNonEmptyIndex(safeImages);
                const linkLast = findLastNonEmptyIndex(safeLinks);
                const buttonEnabledLast = findLastNonEmptyIndex(safeButtonEnableds);
                const buttonLabelLast = findLastNonEmptyIndex(safeButtonLabels);
                const buttonTargetLast = findLastNonEmptyIndex(safeButtonTargets);
                const buttonAlignLast = findLastNonEmptyIndex(safeButtonAligns);
                if (titleLast >= 0) {
                    keepLength = titleLast + 1;
                } else {
                    keepLength = Math.max(textLast, imageLast, linkLast, buttonEnabledLast, buttonLabelLast, buttonTargetLast, buttonAlignLast) + 1;
                }
            }
            keepLength = Math.max(0, Math.min(12, keepLength), minLength);

            const nextTitles = [];
            const nextTexts = [];
            const nextImages = [];
            const nextLinks = [];
            const nextButtonEnableds = [];
            const nextButtonLabels = [];
            const nextButtonTargets = [];
            const nextButtonAligns = [];

            for (let index = 0; index < keepLength; index += 1) {
                nextTitles.push(String(safeTitles[index] || '').trim());
                nextTexts.push(String(safeTexts[index] || '').trim());
                nextImages.push(String(safeImages[index] || '').trim());
                nextLinks.push(String(safeLinks[index] || '').trim());
                const rawEnabled = String(safeButtonEnableds[index] || '').trim();
                const nextEnabled = rawEnabled === '' ? 'on' : normalizeToggleSettingValue(rawEnabled, 'on');
                nextButtonEnableds.push(nextEnabled === 'on' ? 'on' : 'off');
                nextButtonLabels.push(String(safeButtonLabels[index] || '').trim() || globalButtonLabel);
                nextButtonTargets.push(normalizeLinkTarget(String(safeButtonTargets[index] || globalTarget), nextLinks[index]));
                nextButtonAligns.push(normalizeAlign(String(safeButtonAligns[index] || 'left')));
            }

            settings.titles = serializeRepeaterValues(nextTitles, delimiter);
            settings.texts = serializeRepeaterValues(nextTexts, delimiter);
            settings.images = serializeRepeaterValues(nextImages, delimiter);
            settings.links = serializeRepeaterValues(nextLinks, delimiter);
            settings.buttonEnableds = serializeRepeaterValues(nextButtonEnableds, delimiter);
            settings.buttonLabels = serializeRepeaterValues(nextButtonLabels, delimiter);
            settings.buttonTargets = serializeRepeaterValues(nextButtonTargets, delimiter);
            settings.buttonAligns = serializeRepeaterValues(nextButtonAligns, delimiter);
            return;
        }

        if (safeType === 'nw_carrousel') {
            const itemDelimiter = '\n---\n';
            const titles = parseRepeaterValues(settings.titles || '', itemDelimiter);
            const descriptions = parseRepeaterValues(settings.descriptions || '', itemDelimiter);
            const images = parseRepeaterValues(settings.images || '', itemDelimiter);
            const links = parseRepeaterValues(settings.links || '', itemDelimiter);
            const buttonEnableds = parseRepeaterValues(settings.buttonEnableds || '', itemDelimiter);
            const buttonLabels = parseRepeaterValues(settings.buttonLabels || '', itemDelimiter);
            const buttonTargets = parseRepeaterValues(settings.buttonTargets || '', itemDelimiter);
            const buttonAligns = parseRepeaterValues(settings.buttonAligns || '', itemDelimiter);

            const safeTitles = sanitize(titles);
            const safeDescriptions = sanitize(descriptions);
            const safeImages = sanitize(images);
            const safeLinks = sanitize(links);
            const safeButtonEnableds = sanitize(buttonEnableds);
            const safeButtonLabels = sanitize(buttonLabels);
            const safeButtonTargets = sanitize(buttonTargets);
            const safeButtonAligns = sanitize(buttonAligns).map((item) => normalizeAlign(item));

            let keepLength = Math.max(
                safeTitles.length,
                safeDescriptions.length,
                safeImages.length,
                safeLinks.length,
                safeButtonEnableds.length,
                safeButtonLabels.length,
                safeButtonTargets.length,
                safeButtonAligns.length
            );
            if (compact) {
                const titleLast = findLastNonEmptyIndex(safeTitles);
                const descriptionLast = findLastNonEmptyIndex(safeDescriptions);
                const imageLast = findLastNonEmptyIndex(safeImages);
                const linkLast = findLastNonEmptyIndex(safeLinks);
                const buttonEnabledLast = findLastNonEmptyIndex(safeButtonEnableds);
                const buttonLabelLast = findLastNonEmptyIndex(safeButtonLabels);
                const buttonTargetLast = findLastNonEmptyIndex(safeButtonTargets);
                const buttonAlignLast = findLastNonEmptyIndex(safeButtonAligns);
                if (titleLast >= 0) {
                    keepLength = titleLast + 1;
                } else {
                    keepLength = Math.max(descriptionLast, imageLast, linkLast, buttonEnabledLast, buttonLabelLast, buttonTargetLast, buttonAlignLast) + 1;
                }
            }
            keepLength = Math.max(1, Math.min(12, Math.max(keepLength, minLength)));

            const nextTitles = [];
            const nextDescriptions = [];
            const nextImages = [];
            const nextLinks = [];
            const nextButtonEnableds = [];
            const nextButtonLabels = [];
            const nextButtonTargets = [];
            const nextButtonAligns = [];

            for (let index = 0; index < keepLength; index += 1) {
                const nextLink = String(safeLinks[index] || '').trim();
                nextTitles.push(String(safeTitles[index] || '').trim());
                nextDescriptions.push(String(safeDescriptions[index] || '').trim());
                nextImages.push(String(safeImages[index] || '').trim());
                nextLinks.push(nextLink);
                const rawEnabled = String(safeButtonEnableds[index] || '').trim();
                const nextEnabled = rawEnabled === '' ? 'on' : normalizeToggleSettingValue(rawEnabled, 'on');
                nextButtonEnableds.push(nextEnabled === 'on' ? 'on' : 'off');
                nextButtonLabels.push(String(safeButtonLabels[index] || '').trim());
                nextButtonTargets.push(normalizeLinkTarget(String(safeButtonTargets[index] || '_self'), nextLink));
                nextButtonAligns.push(normalizeAlign(String(safeButtonAligns[index] || 'left')));
            }

            settings.titles = serializeRepeaterValues(nextTitles, itemDelimiter);
            settings.descriptions = serializeRepeaterValues(nextDescriptions, itemDelimiter);
            settings.images = serializeRepeaterValues(nextImages, itemDelimiter);
            settings.links = serializeRepeaterValues(nextLinks, itemDelimiter);
            settings.buttonEnableds = serializeRepeaterValues(nextButtonEnableds, itemDelimiter);
            settings.buttonLabels = serializeRepeaterValues(nextButtonLabels, itemDelimiter);
            settings.buttonTargets = serializeRepeaterValues(nextButtonTargets, itemDelimiter);
            settings.buttonAligns = serializeRepeaterValues(nextButtonAligns, itemDelimiter);
            return;
        }

        if (safeType === 'pricing_plans') {
            const featureDelimiter = '\n---\n';
            const planNames = parseRepeaterValues(settings.planNames || '', delimiter);
            const planPrices = parseRepeaterValues(settings.planPrices || '', delimiter);
            const planYearlyPrices = parseRepeaterValues(settings.planYearlyPrices || '', delimiter);
            const legacyPlanPeriods = parseRepeaterValues(settings.planPeriods || '', delimiter);
            const planDescriptions = parseRepeaterValues(settings.planDescriptions || '', delimiter);
            const planFeatures = parseRepeaterValues(settings.planFeatures || '', featureDelimiter);
            const planBadges = parseRepeaterValues(settings.planBadges || '', delimiter);
            const planIcons = parseRepeaterValues(settings.planIcons || '', delimiter);
            const featuredPlans = parseRepeaterValues(settings.featuredPlans || '', delimiter);
            const ctaEnableds = parseRepeaterValues(settings.ctaEnableds || '', delimiter);
            const ctaLabels = parseRepeaterValues(settings.ctaLabels || '', delimiter);
            const ctaLinks = parseRepeaterValues(settings.ctaLinks || '', delimiter);
            const ctaTargets = parseRepeaterValues(settings.ctaTargets || '', delimiter);
            const ctaVariants = parseRepeaterValues(settings.ctaVariants || '', delimiter);
            const ctaAligns = parseRepeaterValues(settings.ctaAligns || '', delimiter);
            const baseAlign = normalizeAlign(String(settings.align || 'left'));

            const safePlanNames = sanitize(planNames);
            const safePlanPrices = sanitize(planPrices);
            const safeLegacyPlanPeriods = sanitize(legacyPlanPeriods);
            const safePlanYearlyPrices = sanitize(planYearlyPrices).map((item, index) => {
                const value = String(item || '').trim();
                if (value !== '') {
                    return value;
                }
                const legacyValue = String(safeLegacyPlanPeriods[index] || '').trim();
                return /\d/u.test(legacyValue) ? legacyValue : '';
            });
            const safePlanDescriptions = sanitize(planDescriptions);
            const safePlanFeatures = sanitize(planFeatures);
            const safePlanBadges = sanitize(planBadges);
            const safePlanIcons = sanitize(planIcons);
            const safeFeaturedPlans = sanitize(featuredPlans).map((item) => normalizeToggleSettingValue(item, 'off'));
            const safeCtaEnableds = sanitize(ctaEnableds).map((item) => normalizeToggleSettingValue(item, 'on'));
            const safeCtaLabels = sanitize(ctaLabels);
            const safeCtaLinks = sanitize(ctaLinks);
            const safeCtaTargets = sanitize(ctaTargets);
            const safeCtaVariants = sanitize(ctaVariants).map((item) => normalizeFeatureGridButtonVariant(item));
            const safeCtaAligns = sanitize(ctaAligns).map((item) => normalizeAlign(item, baseAlign));

            let keepLength = Math.max(
                safePlanNames.length,
                safePlanPrices.length,
                safePlanYearlyPrices.length,
                safePlanDescriptions.length,
                safePlanFeatures.length,
                safePlanBadges.length,
                safePlanIcons.length,
                safeFeaturedPlans.length,
                safeCtaEnableds.length,
                safeCtaLabels.length,
                safeCtaLinks.length,
                safeCtaTargets.length,
                safeCtaVariants.length,
                safeCtaAligns.length
            );
            if (compact) {
                const planNameLast = findLastNonEmptyIndex(safePlanNames);
                const planPriceLast = findLastNonEmptyIndex(safePlanPrices);
                const planYearlyPriceLast = findLastNonEmptyIndex(safePlanYearlyPrices);
                const planDescriptionLast = findLastNonEmptyIndex(safePlanDescriptions);
                const planFeatureLast = findLastNonEmptyIndex(safePlanFeatures);
                const planBadgeLast = findLastNonEmptyIndex(safePlanBadges);
                const planIconLast = findLastNonEmptyIndex(safePlanIcons);
                const featuredLast = findLastNonEmptyIndex(safeFeaturedPlans);
                const ctaEnabledLast = findLastNonEmptyIndex(safeCtaEnableds);
                const ctaLabelLast = findLastNonEmptyIndex(safeCtaLabels);
                const ctaLinkLast = findLastNonEmptyIndex(safeCtaLinks);
                const ctaTargetLast = findLastNonEmptyIndex(safeCtaTargets);
                const ctaVariantLast = findLastNonEmptyIndex(safeCtaVariants);
                const ctaAlignLast = findLastNonEmptyIndex(safeCtaAligns);
                if (planNameLast >= 0) {
                    keepLength = planNameLast + 1;
                } else {
                    keepLength = Math.max(
                        planPriceLast,
                        planYearlyPriceLast,
                        planDescriptionLast,
                        planFeatureLast,
                        planBadgeLast,
                        planIconLast,
                        featuredLast,
                        ctaEnabledLast,
                        ctaLabelLast,
                        ctaLinkLast,
                        ctaTargetLast,
                        ctaVariantLast,
                        ctaAlignLast
                    ) + 1;
                }
            }
            keepLength = Math.max(1, Math.min(8, Math.max(keepLength, minLength)));

            const nextPlanNames = [];
            const nextPlanPrices = [];
            const nextPlanYearlyPrices = [];
            const nextPlanDescriptions = [];
            const nextPlanFeatures = [];
            const nextPlanBadges = [];
            const nextPlanIcons = [];
            const nextFeaturedPlans = [];
            const nextCtaEnableds = [];
            const nextCtaLabels = [];
            const nextCtaLinks = [];
            const nextCtaTargets = [];
            const nextCtaVariants = [];
            const nextCtaAligns = [];

            for (let index = 0; index < keepLength; index += 1) {
                const nextLink = String(safeCtaLinks[index] || '').trim();
                nextPlanNames.push(String(safePlanNames[index] || '').trim());
                nextPlanPrices.push(String(safePlanPrices[index] || '').trim());
                nextPlanYearlyPrices.push(String(safePlanYearlyPrices[index] || '').trim());
                nextPlanDescriptions.push(String(safePlanDescriptions[index] || '').trim());
                nextPlanFeatures.push(String(safePlanFeatures[index] || '').trim());
                nextPlanBadges.push(String(safePlanBadges[index] || '').trim());
                nextPlanIcons.push(String(safePlanIcons[index] || '').trim());
                nextFeaturedPlans.push(normalizeToggleSettingValue(String(safeFeaturedPlans[index] || 'off'), 'off'));
                nextCtaEnableds.push(normalizeToggleSettingValue(String(safeCtaEnableds[index] || 'on'), 'on'));
                nextCtaLabels.push(String(safeCtaLabels[index] || '').trim());
                nextCtaLinks.push(nextLink);
                nextCtaTargets.push(normalizeLinkTarget(String(safeCtaTargets[index] || '_self'), nextLink));
                nextCtaVariants.push(normalizeFeatureGridButtonVariant(String(safeCtaVariants[index] || 'ghost')));
                nextCtaAligns.push(normalizeAlign(String(safeCtaAligns[index] || baseAlign), baseAlign));
            }

            settings.planNames = serializeRepeaterValues(nextPlanNames, delimiter);
            settings.planPrices = serializeRepeaterValues(nextPlanPrices, delimiter);
            settings.planYearlyPrices = serializeRepeaterValues(nextPlanYearlyPrices, delimiter);
            settings.planDescriptions = serializeRepeaterValues(nextPlanDescriptions, delimiter);
            settings.planFeatures = serializeRepeaterValues(nextPlanFeatures, featureDelimiter);
            settings.planBadges = serializeRepeaterValues(nextPlanBadges, delimiter);
            settings.planIcons = serializeRepeaterValues(nextPlanIcons, delimiter);
            settings.featuredPlans = serializeRepeaterValues(nextFeaturedPlans, delimiter);
            settings.ctaEnableds = serializeRepeaterValues(nextCtaEnableds, delimiter);
            settings.ctaLabels = serializeRepeaterValues(nextCtaLabels, delimiter);
            settings.ctaLinks = serializeRepeaterValues(nextCtaLinks, delimiter);
            settings.ctaTargets = serializeRepeaterValues(nextCtaTargets, delimiter);
            settings.ctaVariants = serializeRepeaterValues(nextCtaVariants, delimiter);
            settings.ctaAligns = serializeRepeaterValues(nextCtaAligns, delimiter);
            return;
        }

        const titles = parseRepeaterValues(settings.titles || '', delimiter);
        const texts = parseRepeaterValues(settings.texts || '', delimiter);
        const backgrounds = parseRepeaterValues(settings.backgrounds || '', delimiter);
        const links = parseRepeaterValues(settings.links || '', delimiter);
        const ctaEnableds = parseRepeaterValues(settings.ctaEnableds || '', delimiter);
        const ctaLabels = parseRepeaterValues(settings.ctaLabels || '', delimiter);
        const targets = parseRepeaterValues(settings.targets || '', delimiter);
        const buttonAligns = parseRepeaterValues(settings.buttonAligns || '', delimiter);
        const globalCtaLabel = String(settings.ctaLabel || '').trim();
        const globalTarget = ['_self', '_blank'].includes(String(settings.target || '').trim())
            ? String(settings.target || '').trim()
            : '_self';
        const globalAlign = normalizeAlign(String(settings.align || 'left'));
        settings.target = globalTarget;

        const safeTitles = sanitize(titles);
        const safeTexts = sanitize(texts);
        const safeBackgrounds = sanitize(backgrounds);
        const safeLinks = sanitize(links);
        const safeCtaEnableds = sanitize(ctaEnableds);
        const safeCtaLabels = sanitize(ctaLabels);
        const safeTargets = sanitize(targets);
        const safeButtonAligns = sanitize(buttonAligns).map((item) => normalizeAlign(item, globalAlign));

        let keepLength = Math.max(
            safeTitles.length,
            safeTexts.length,
            safeBackgrounds.length,
            safeLinks.length,
            safeCtaEnableds.length,
            safeCtaLabels.length,
            safeTargets.length,
            safeButtonAligns.length
        );
        if (compact) {
            const titleLast = findLastNonEmptyIndex(safeTitles);
            const textLast = findLastNonEmptyIndex(safeTexts);
            const mediaLast = findLastNonEmptyIndex(safeBackgrounds);
            const linkLast = findLastNonEmptyIndex(safeLinks);
            const ctaLabelLast = findLastNonEmptyIndex(safeCtaLabels);
            const targetLast = findLastNonEmptyIndex(safeTargets);
            const buttonAlignLast = findLastNonEmptyIndex(safeButtonAligns);
            if (titleLast >= 0) {
                keepLength = titleLast + 1;
            } else {
                keepLength = Math.max(textLast, mediaLast, linkLast, ctaLabelLast, targetLast, buttonAlignLast) + 1;
            }
        }
        keepLength = Math.max(0, Math.min(12, keepLength), minLength);

        const nextTitles = [];
        const nextTexts = [];
        const nextBackgrounds = [];
        const nextLinks = [];
        const nextCtaEnableds = [];
        const nextCtaLabels = [];
        const nextTargets = [];
        const nextButtonAligns = [];

        for (let index = 0; index < keepLength; index += 1) {
            nextTitles.push(String(safeTitles[index] || '').trim());
            nextTexts.push(String(safeTexts[index] || '').trim());
            nextBackgrounds.push(String(safeBackgrounds[index] || '').trim());
            nextLinks.push(String(safeLinks[index] || '').trim());
            const rawEnabled = String(safeCtaEnableds[index] || '').trim();
            const nextEnabled = rawEnabled === '' ? 'on' : normalizeToggleSettingValue(rawEnabled, 'on');
            nextCtaEnableds.push(nextEnabled === 'on' ? 'on' : 'off');
            const nextLabel = String(safeCtaLabels[index] || '').trim() || globalCtaLabel;
            nextCtaLabels.push(nextLabel);
            const fallbackTarget = ['_self', '_blank'].includes(String(safeTargets[index] || '').trim())
                ? String(safeTargets[index] || '').trim()
                : globalTarget;
            nextTargets.push(normalizeLinkTarget(fallbackTarget, nextLinks[index]));
            nextButtonAligns.push(normalizeAlign(String(safeButtonAligns[index] || globalAlign), globalAlign));
        }

        settings.titles = serializeRepeaterValues(nextTitles, delimiter);
        settings.texts = serializeRepeaterValues(nextTexts, delimiter);
        settings.backgrounds = serializeRepeaterValues(nextBackgrounds, delimiter);
        settings.links = serializeRepeaterValues(nextLinks, delimiter);
        settings.ctaEnableds = serializeRepeaterValues(nextCtaEnableds, delimiter);
        settings.ctaLabels = serializeRepeaterValues(nextCtaLabels, delimiter);
        settings.targets = serializeRepeaterValues(nextTargets, delimiter);
        settings.buttonAligns = serializeRepeaterValues(nextButtonAligns, delimiter);
    }

    function createPricingPlansInspectorState(settings) {
        const source = settings && typeof settings === 'object' ? settings : {};
        const delimiter = '\n';
        const featureDelimiter = '\n---\n';
        const explicitPlanYearlyPrices = parseRepeaterValues(source.planYearlyPrices || '', delimiter);
        const legacyPlanPeriods = parseRepeaterValues(source.planPeriods || '', delimiter);
        const planYearlyPrices = [];
        const priceCount = Math.max(explicitPlanYearlyPrices.length, legacyPlanPeriods.length);
        for (let index = 0; index < priceCount; index += 1) {
            const explicitValue = String(explicitPlanYearlyPrices[index] || '').trim();
            if (explicitValue !== '') {
                planYearlyPrices.push(explicitValue);
                continue;
            }
            const legacyValue = String(legacyPlanPeriods[index] || '').trim();
            planYearlyPrices.push(/\d/u.test(legacyValue) ? legacyValue : '');
        }

        return {
            delimiter,
            featureDelimiter,
            planNames: parseRepeaterValues(source.planNames || '', delimiter),
            planPrices: parseRepeaterValues(source.planPrices || '', delimiter),
            planYearlyPrices,
            planDescriptions: parseRepeaterValues(source.planDescriptions || '', delimiter),
            planFeatures: parseRepeaterValues(source.planFeatures || '', featureDelimiter),
            planBadges: parseRepeaterValues(source.planBadges || '', delimiter),
            planIcons: parseRepeaterValues(source.planIcons || '', delimiter),
            featuredPlans: parseRepeaterValues(source.featuredPlans || '', delimiter),
            ctaEnableds: parseRepeaterValues(source.ctaEnableds || '', delimiter),
            ctaLabels: parseRepeaterValues(source.ctaLabels || '', delimiter),
            ctaLinks: parseRepeaterValues(source.ctaLinks || '', delimiter),
            ctaTargets: parseRepeaterValues(source.ctaTargets || '', delimiter),
            ctaVariants: parseRepeaterValues(source.ctaVariants || '', delimiter),
            ctaAligns: parseRepeaterValues(source.ctaAligns || '', delimiter),
        };
    }

    function normalizePricingPlansInspectorState(state, settings, options) {
        const safeState = state && typeof state === 'object'
            ? state
            : createPricingPlansInspectorState(settings);
        const safeSettings = settings && typeof settings === 'object' ? settings : {};
        const delimiter = safeState.delimiter || '\n';
        const featureDelimiter = safeState.featureDelimiter || '\n---\n';
        const opts = options && typeof options === 'object' ? options : {};
        const minItems = Math.max(1, Number(opts.minItems || 0));
        const maxItems = Math.max(0, Number(opts.maxItems || 0));
        const baseAlign = normalizeAlign(String(safeSettings.align || 'left'));

        const nextSettings = {
            align: baseAlign,
            planNames: serializeRepeaterValues(safeState.planNames, delimiter),
            planPrices: serializeRepeaterValues(safeState.planPrices, delimiter),
            planYearlyPrices: serializeRepeaterValues(safeState.planYearlyPrices, delimiter),
            planDescriptions: serializeRepeaterValues(safeState.planDescriptions, delimiter),
            planFeatures: serializeRepeaterValues(safeState.planFeatures, featureDelimiter),
            planBadges: serializeRepeaterValues(safeState.planBadges, delimiter),
            planIcons: serializeRepeaterValues(safeState.planIcons, delimiter),
            featuredPlans: serializeRepeaterValues(safeState.featuredPlans, delimiter),
            ctaEnableds: serializeRepeaterValues(safeState.ctaEnableds, delimiter),
            ctaLabels: serializeRepeaterValues(safeState.ctaLabels, delimiter),
            ctaLinks: serializeRepeaterValues(safeState.ctaLinks, delimiter),
            ctaTargets: serializeRepeaterValues(safeState.ctaTargets, delimiter),
            ctaVariants: serializeRepeaterValues(safeState.ctaVariants, delimiter),
            ctaAligns: serializeRepeaterValues(safeState.ctaAligns, delimiter),
        };
        normalizeWidgetLinkedRepeaters('pricing_plans', nextSettings, opts);

        safeState.planNames = parseRepeaterValues(nextSettings.planNames || '', delimiter);
        safeState.planPrices = parseRepeaterValues(nextSettings.planPrices || '', delimiter);
        safeState.planYearlyPrices = parseRepeaterValues(nextSettings.planYearlyPrices || '', delimiter);
        safeState.planDescriptions = parseRepeaterValues(nextSettings.planDescriptions || '', delimiter);
        safeState.planFeatures = parseRepeaterValues(nextSettings.planFeatures || '', featureDelimiter);
        safeState.planBadges = parseRepeaterValues(nextSettings.planBadges || '', delimiter);
        safeState.planIcons = parseRepeaterValues(nextSettings.planIcons || '', delimiter);
        safeState.featuredPlans = parseRepeaterValues(nextSettings.featuredPlans || '', delimiter);
        safeState.ctaEnableds = parseRepeaterValues(nextSettings.ctaEnableds || '', delimiter);
        safeState.ctaLabels = parseRepeaterValues(nextSettings.ctaLabels || '', delimiter);
        safeState.ctaLinks = parseRepeaterValues(nextSettings.ctaLinks || '', delimiter);
        safeState.ctaTargets = parseRepeaterValues(nextSettings.ctaTargets || '', delimiter);
        safeState.ctaVariants = parseRepeaterValues(nextSettings.ctaVariants || '', delimiter);
        safeState.ctaAligns = parseRepeaterValues(nextSettings.ctaAligns || '', delimiter);

        const targetLength = Math.max(
            minItems,
            safeState.planNames.length,
            safeState.planPrices.length,
            safeState.planYearlyPrices.length,
            safeState.planDescriptions.length,
            safeState.planFeatures.length,
            safeState.planBadges.length,
            safeState.planIcons.length,
            safeState.featuredPlans.length,
            safeState.ctaEnableds.length,
            safeState.ctaLabels.length,
            safeState.ctaLinks.length,
            safeState.ctaTargets.length,
            safeState.ctaVariants.length,
            safeState.ctaAligns.length
        );

        while (safeState.planNames.length < targetLength) safeState.planNames.push('');
        while (safeState.planPrices.length < targetLength) safeState.planPrices.push('');
        while (safeState.planYearlyPrices.length < targetLength) safeState.planYearlyPrices.push('');
        while (safeState.planDescriptions.length < targetLength) safeState.planDescriptions.push('');
        while (safeState.planFeatures.length < targetLength) safeState.planFeatures.push('');
        while (safeState.planBadges.length < targetLength) safeState.planBadges.push('');
        while (safeState.planIcons.length < targetLength) safeState.planIcons.push('');
        while (safeState.featuredPlans.length < targetLength) safeState.featuredPlans.push('off');
        while (safeState.ctaEnableds.length < targetLength) safeState.ctaEnableds.push('on');
        while (safeState.ctaLabels.length < targetLength) safeState.ctaLabels.push('');
        while (safeState.ctaLinks.length < targetLength) safeState.ctaLinks.push('');
        while (safeState.ctaTargets.length < targetLength) safeState.ctaTargets.push('_self');
        while (safeState.ctaVariants.length < targetLength) safeState.ctaVariants.push('ghost');
        while (safeState.ctaAligns.length < targetLength) safeState.ctaAligns.push(baseAlign);

        if (maxItems > 0) {
            safeState.planNames = safeState.planNames.slice(0, maxItems);
            safeState.planPrices = safeState.planPrices.slice(0, maxItems);
            safeState.planYearlyPrices = safeState.planYearlyPrices.slice(0, maxItems);
            safeState.planDescriptions = safeState.planDescriptions.slice(0, maxItems);
            safeState.planFeatures = safeState.planFeatures.slice(0, maxItems);
            safeState.planBadges = safeState.planBadges.slice(0, maxItems);
            safeState.planIcons = safeState.planIcons.slice(0, maxItems);
            safeState.featuredPlans = safeState.featuredPlans.slice(0, maxItems);
            safeState.ctaEnableds = safeState.ctaEnableds.slice(0, maxItems);
            safeState.ctaLabels = safeState.ctaLabels.slice(0, maxItems);
            safeState.ctaLinks = safeState.ctaLinks.slice(0, maxItems);
            safeState.ctaTargets = safeState.ctaTargets.slice(0, maxItems);
            safeState.ctaVariants = safeState.ctaVariants.slice(0, maxItems);
            safeState.ctaAligns = safeState.ctaAligns.slice(0, maxItems);
        }

        return safeState;
    }

    function buildPricingPlansInspectorPatch(state) {
        const safeState = state && typeof state === 'object' ? state : createPricingPlansInspectorState({});
        const delimiter = safeState.delimiter || '\n';
        const featureDelimiter = safeState.featureDelimiter || '\n---\n';

        return {
            planNames: serializeRepeaterValues(safeState.planNames, delimiter),
            planPrices: serializeRepeaterValues(safeState.planPrices, delimiter),
            planYearlyPrices: serializeRepeaterValues(safeState.planYearlyPrices, delimiter),
            planDescriptions: serializeRepeaterValues(safeState.planDescriptions, delimiter),
            planFeatures: serializeRepeaterValues(safeState.planFeatures, featureDelimiter),
            planBadges: serializeRepeaterValues(safeState.planBadges, delimiter),
            planIcons: serializeRepeaterValues(safeState.planIcons, delimiter),
            featuredPlans: serializeRepeaterValues(safeState.featuredPlans, delimiter),
            ctaEnableds: serializeRepeaterValues(safeState.ctaEnableds, delimiter),
            ctaLabels: serializeRepeaterValues(safeState.ctaLabels, delimiter),
            ctaLinks: serializeRepeaterValues(safeState.ctaLinks, delimiter),
            ctaTargets: serializeRepeaterValues(safeState.ctaTargets, delimiter),
            ctaVariants: serializeRepeaterValues(safeState.ctaVariants, delimiter),
            ctaAligns: serializeRepeaterValues(safeState.ctaAligns, delimiter),
        };
    }

    function buildPersistableBuilderPayload(builderInput) {
        let payload = null;
        try {
            payload = JSON.parse(JSON.stringify(builderInput || {}));
        } catch (error) {
            payload = normalizeBuilder(builderInput || {});
        }

        const sections = Array.isArray(payload && payload.sections) ? payload.sections : [];
        sections.forEach((section) => {
            const columns = Array.isArray(section && section.columns) ? section.columns : [];
            columns.forEach((column) => {
                const blocks = Array.isArray(column && column.blocks) ? column.blocks : [];
                blocks.forEach((block) => {
                    if (!block || typeof block !== 'object') {
                        return;
                    }
                    const type = String(block.type || '');
                    const settings = block.settings && typeof block.settings === 'object'
                        ? block.settings
                        : {};
                    block.settings = settings;
                    normalizeWidgetLinkedRepeaters(type, settings, { compact: true });
                });
            });
        });

        return normalizeBuilder(payload);
    }

    function sanitizeColumnCount(value) {
        const num = Number(value);
        if (Number.isNaN(num)) return 1;
        return Math.max(1, Math.min(4, Math.trunc(num)));
    }

    function buildEqualSectionTemplate(columnCount) {
        const cols = sanitizeColumnCount(columnCount);
        if (cols === 1) return 'minmax(0, 1fr)';
        return `repeat(${cols}, minmax(0, 1fr))`;
    }

    function createDefaultSectionSettings() {
        return {
            backgroundColor: '',
            backgroundImage: '',
            backgroundSize: 'cover',
            backgroundPosition: 'center center',
            backgroundRepeat: 'no-repeat',
            overlayColor: '',
            overlayOpacity: 0,
            containerMode: 'container',
            containerModeExplicit: false,
            paddingTop: 0,
            paddingBottom: 0,
        };
    }

    function normalizeSectionCssValue(value, maxLength = 160) {
        const normalized = String(value || '').replace(/\s+/g, ' ').trim();
        if (normalized === '' || normalized.length > maxLength) {
            return '';
        }
        return normalized;
    }

    function normalizeSectionCssKeyword(value, allowed, fallback) {
        const normalized = String(value || '').trim().toLowerCase();
        return allowed.includes(normalized) ? normalized : fallback;
    }

    function normalizeSectionSpacingValue(value) {
        const raw = Number(value);
        if (Number.isNaN(raw)) return 0;
        return Math.max(0, Math.min(240, Math.round(raw)));
    }

    function normalizeSectionOpacityValue(value) {
        const raw = Number(value);
        if (Number.isNaN(raw)) return 0;
        return Math.max(0, Math.min(100, Math.round(raw)));
    }

    function normalizeSectionSettings(input) {
        const settings = input && typeof input === 'object' ? input : {};
        const defaults = createDefaultSectionSettings();
        const hasExplicitContainerMode = Object.prototype.hasOwnProperty.call(settings, 'containerModeExplicit')
            ? !!settings.containerModeExplicit
            : false;
        const requestedContainerMode = String(settings.containerMode || '').trim().toLowerCase();
        const resolvedContainerMode = hasExplicitContainerMode
            ? normalizeSectionCssKeyword(requestedContainerMode || defaults.containerMode, ['container', 'fluid'], defaults.containerMode)
            : defaults.containerMode;

        return {
            backgroundColor: normalizeSectionCssValue(settings.backgroundColor || '', 120),
            backgroundImage: normalizeSectionCssValue(settings.backgroundImage || '', 2048),
            backgroundSize: normalizeSectionCssKeyword(
                settings.backgroundSize || defaults.backgroundSize,
                ['auto', 'cover', 'contain'],
                defaults.backgroundSize
            ),
            backgroundPosition: normalizeSectionCssValue(
                settings.backgroundPosition || defaults.backgroundPosition,
                80
            ) || defaults.backgroundPosition,
            backgroundRepeat: normalizeSectionCssKeyword(
                settings.backgroundRepeat || defaults.backgroundRepeat,
                ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'],
                defaults.backgroundRepeat
            ),
            overlayColor: normalizeSectionCssValue(settings.overlayColor || '', 120),
            overlayOpacity: normalizeSectionOpacityValue(settings.overlayOpacity),
            containerMode: resolvedContainerMode,
            containerModeExplicit: hasExplicitContainerMode,
            paddingTop: normalizeSectionSpacingValue(settings.paddingTop),
            paddingBottom: normalizeSectionSpacingValue(settings.paddingBottom),
        };
    }

    function buildSectionBackgroundImageCssValue(value) {
        const normalized = resolveMediaSrc(String(value || '').trim());
        if (normalized === '') {
            return 'none';
        }

        const escaped = normalized
            .replace(/\\/g, '\\\\')
            .replace(/"/g, '\\"');

        return `url("${escaped}")`;
    }

    function applySectionCanvasSettings(sectionEl, settings) {
        if (!sectionEl || !settings) {
            return;
        }

        const sectionShell = sectionEl.querySelector('.pb-section-shell');
        const sectionOverlay = sectionEl.querySelector('.pb-section-overlay');
        const sectionInner = sectionEl.querySelector('.pb-section-inner');

        sectionEl.dataset.containerMode = String(settings.containerMode || 'container');

        if (sectionInner) {
            sectionInner.classList.remove('pb-section-inner-container', 'pb-section-inner-fluid');
            sectionInner.classList.add(`pb-section-inner-${String(settings.containerMode || 'container')}`);
            sectionInner.style.paddingTop = `${normalizeSectionSpacingValue(settings.paddingTop)}px`;
            sectionInner.style.paddingBottom = `${normalizeSectionSpacingValue(settings.paddingBottom)}px`;
        }

        if (sectionShell) {
            sectionShell.style.backgroundColor = String(settings.backgroundColor || '');
            sectionShell.style.backgroundImage = buildSectionBackgroundImageCssValue(settings.backgroundImage);
            sectionShell.style.backgroundSize = String(settings.backgroundSize || 'cover');
            sectionShell.style.backgroundPosition = String(settings.backgroundPosition || 'center center');
            sectionShell.style.backgroundRepeat = String(settings.backgroundRepeat || 'no-repeat');
        }

        if (sectionOverlay) {
            sectionOverlay.style.backgroundColor = String(settings.overlayColor || '');
            sectionOverlay.style.opacity = String(normalizeSectionOpacityValue(settings.overlayOpacity) / 100);
        }
    }

    function sanitizeSectionLayoutTemplate(template, columnCount) {
        const fallback = buildEqualSectionTemplate(columnCount);
        const raw = String(template || '').trim();
        if (raw === '') return fallback;
        if (raw.length > 120) return fallback;
        if (!/^[0-9a-zA-Z(),.%\s-]+$/.test(raw)) return fallback;
        const normalized = raw.replace(/\s+/g, ' ').trim();
        if (normalized === '') return fallback;
        if (!/(repeat|minmax|fr)/i.test(normalized)) return fallback;
        return normalized;
    }

    function getSectionLayoutPresets() {
        return {
            'cols-1': {
                template: 'minmax(0, 1fr)',
                columns: 1,
                gridClass: 'cols-1',
                bars: 1,
                label: label('cols1', '1'),
            },
            'cols-2': {
                template: 'repeat(2, minmax(0, 1fr))',
                columns: 2,
                gridClass: 'cols-2',
                bars: 2,
                label: label('cols2', '2'),
            },
            'cols-3': {
                template: 'repeat(3, minmax(0, 1fr))',
                columns: 3,
                gridClass: 'cols-3',
                bars: 3,
                label: label('cols3', '3'),
            },
            'cols-4': {
                template: 'repeat(4, minmax(0, 1fr))',
                columns: 4,
                gridClass: 'cols-4',
                bars: 4,
                label: label('cols4', '4'),
            },
            'cols-2-1-1': {
                template: 'minmax(0, 2fr) minmax(0, 1fr) minmax(0, 1fr)',
                columns: 3,
                gridClass: 'cols-2-1-1',
                bars: 3,
                label: label('cols211', '2/4 - 1/4 - 1/4'),
            },
            'cols-1-1-2': {
                template: 'minmax(0, 1fr) minmax(0, 1fr) minmax(0, 2fr)',
                columns: 3,
                gridClass: 'cols-1-1-2',
                bars: 3,
                label: label('cols112', '1/4 - 1/4 - 2/4'),
            },
            'cols-1-3-2-3': {
                template: 'minmax(0, 1fr) minmax(0, 2fr)',
                columns: 2,
                gridClass: 'cols-1-3-2-3',
                bars: 2,
                label: '1/3 - 2/3',
            },
            'cols-2-3-1-3': {
                template: 'minmax(0, 2fr) minmax(0, 1fr)',
                columns: 2,
                gridClass: 'cols-2-3-1-3',
                bars: 2,
                label: '2/3 - 1/3',
            },
            'cols-1-4-3-4': {
                template: 'minmax(0, 1fr) minmax(0, 3fr)',
                columns: 2,
                gridClass: 'cols-1-4-3-4',
                bars: 2,
                label: '1/4 - 3/4',
            },
            'cols-3-4-1-4': {
                template: 'minmax(0, 3fr) minmax(0, 1fr)',
                columns: 2,
                gridClass: 'cols-3-4-1-4',
                bars: 2,
                label: '3/4 - 1/4',
            },
        };
    }

    function getQuickAddTemplatePresets() {
        const demoContactImage = '/uploads/images/forms.webp';
        const demoHomeImage = '/uploads/images/pages.webp';
        const demoPlanningImage = '/uploads/images/posts.webp';
        const demoServicesImage = '/uploads/images/templates.webp';
        const demoShowcaseImage = '/uploads/images/success.webp';
        const demoShowroomImage = '/uploads/images/dashbord.webp';
        const demoStorefrontImage = '/uploads/images/extensions.webp';
        const demoTrustImage = '/uploads/images/settings.webp';
        const demoLogoLabels = [
            'FlatCMS',
            'WordPress',
            'Webflow',
            'Strapi',
            'Shopify',
            'Wix',
            'Drupal',
            'Magento',
        ].join('\n');
        const demoLogoImages = [
            '/uploads/images/logo_flatcms.png',
            '/uploads/images/wordpress.png',
            '/uploads/images/webflow.png',
            '/uploads/images/strapi.png',
            '/uploads/images/shopify.png',
            '/uploads/images/wix.png',
            '/uploads/images/drupal.png',
            '/uploads/images/magento.png',
        ].join('\n');
        const demoLogoLinks = ['#', '#', '#', '#', '#', '#', '#', '#'].join('\n');
        const demoSnapBackgrounds = [
            '/uploads/images/01_animation_installateur.webp',
            '/uploads/images/step-1.webp',
            '/uploads/images/step-2.webp',
        ].join('\n');
        const demoSnapTitles = [
            'Bloc strategique',
            'Livraison rapide',
            'Pilotage clair',
        ].join('\n');
        const demoSnapTexts = [
            'Presentez un argument fort en un coup d oeil.',
            'Cadrez vos offres avec un message oriente resultat.',
            'Ajoutez un bouton pour convertir immediatement.',
        ].join('\n');

        return {
            'page-contact-complete': {
                label: label('quickAddTemplatePageContactComplete', 'Page contact complete'),
                description: label('quickAddTemplatePageContactCompleteDesc', 'Hero, contenu editorial, contact et FAQ'),
                pageTitle: label('quickTemplatePageTitleContact', 'Contact'),
                sections: [
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'hero',
                                    settings: {
                                        title: label('quickTemplateContactHeroTitle', 'Parlons de votre projet'),
                                        subtitle: label('quickTemplateContactHeroText', 'Decrivez votre besoin. Nous revenons vers vous rapidement avec une reponse claire.'),
                                        primaryLabel: label('defaultCallToAction', 'En savoir plus'),
                                        primaryUrl: '#',
                                        secondaryLabel: label('defaultHeroSecondaryLabel', 'Contact'),
                                        secondaryUrl: '#',
                                        backgroundImage: demoContactImage,
                                        variant: 'soft',
                                        align: 'left',
                                        height: 380,
                                        overlay: 20,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'content_split_media',
                                    settings: {
                                        imageSrc: demoContactImage,
                                        imageAlt: label('quickTemplatePageTitleContact', 'Contact'),
                                        mediaKind: 'image',
                                        mediaPosition: 'right',
                                        ratio: 'content-wide',
                                        variant: 'subtle',
                                        align: 'left',
                                        primaryUrl: '#',
                                        secondaryUrl: '#',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'contact_section',
                                    settings: {
                                        contactFormSlug: 'contact-main',
                                        variant: 'subtle',
                                        align: 'left',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'faq_accordion',
                                    settings: {
                                        variant: 'subtle',
                                        align: 'left',
                                        openFirst: 'on',
                                    },
                                },
                            ],
                        ],
                    },
                ],
            },
            'page-coming-soon': {
                label: label('quickAddTemplatePageComingSoon', 'Page en construction'),
                description: label('quickAddTemplatePageComingSoonDesc', 'Hero, texte, newsletter et preuve sociale'),
                pageTitle: label('quickTemplatePageTitleComingSoon', 'En construction'),
                sections: [
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'hero',
                                    settings: {
                                        title: label('quickTemplateComingSoonTitle', 'Site en construction'),
                                        subtitle: label('quickTemplateComingSoonText', 'Nous preparons une nouvelle experience. Inscrivez-vous pour etre informe du lancement.'),
                                        primaryLabel: label('defaultNewsletterButton', 'S\'abonner'),
                                        primaryUrl: '#',
                                        showSecondaryCta: 'off',
                                        backgroundImage: demoStorefrontImage,
                                        variant: 'dark',
                                        align: 'center',
                                        height: 440,
                                        overlay: 45,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'text',
                                    settings: {
                                        showTitle: 'off',
                                        align: 'center',
                                        text: `<p>${escapeHtml(label('quickTemplateComingSoonText', 'Nous preparons une nouvelle experience. Inscrivez-vous pour etre informe du lancement.'))}</p>`,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'newsletter_section',
                                    settings: {
                                        variant: 'dark',
                                        align: 'center',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'logo_cloud',
                                    settings: {
                                        title: 'Compatible avec vos outils favoris',
                                        subtitle: 'Gardez une preuve visuelle simple pendant votre pre-lancement.',
                                        labels: demoLogoLabels,
                                        logos: demoLogoImages,
                                        links: demoLogoLinks,
                                        columns: 4,
                                        presentationModel: 'classic',
                                        logoHeight: 64,
                                        variant: 'ghost',
                                        align: 'center',
                                        grayscale: '',
                                    },
                                },
                            ],
                        ],
                    },
                ],
            },
            'page-business-stats': {
                label: label('quickAddTemplatePageBusinessStats', 'Page chiffres cles'),
                description: label('quickAddTemplatePageBusinessStatsDesc', 'Hero, statistiques, logos et temoignages'),
                pageTitle: label('quickTemplatePageTitleStats', 'Nos chiffres cles'),
                sections: [
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'hero',
                                    settings: {
                                        title: label('quickTemplateStatsTitle', 'Notre progression en quelques chiffres'),
                                        subtitle: label('quickTemplateStatsText', 'Utilisez ce modele pour afficher vos indicateurs de confiance.'),
                                        backgroundImage: demoShowcaseImage,
                                        variant: 'soft',
                                        align: 'center',
                                        height: 360,
                                        overlay: 25,
                                        showSecondaryCta: 'off',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'stats_section',
                                    settings: {
                                        variant: 'strong',
                                        align: 'center',
                                        columns: 4,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'logo_cloud',
                                    settings: {
                                        title: 'Des outils deja integres a votre quotidien',
                                        subtitle: 'Exposez vos compatibilites et partenaires avec un bloc visuel lisible.',
                                        labels: demoLogoLabels,
                                        logos: demoLogoImages,
                                        links: demoLogoLinks,
                                        columns: 4,
                                        presentationModel: 'classic',
                                        logoHeight: 68,
                                        variant: 'subtle',
                                        align: 'center',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'testimonial_cards',
                                    settings: {
                                        variant: 'subtle',
                                        align: 'left',
                                        columns: 3,
                                    },
                                },
                            ],
                        ],
                    },
                ],
            },
            'page-service-landing': {
                label: label('quickAddTemplatePageServiceLanding', 'Landing services'),
                description: label('quickAddTemplatePageServiceLandingDesc', 'Hero, bloc editorial, grille, tarifs et contact'),
                pageTitle: label('quickTemplatePageTitleServices', 'Nos services'),
                sections: [
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'hero',
                                    settings: {
                                        title: label('quickTemplateServiceLandingTitle', 'Une offre claire pour votre site'),
                                        subtitle: label('quickTemplateServiceLandingText', 'Presentez votre proposition de valeur avec un parcours simple et lisible.'),
                                        primaryLabel: label('defaultCallToAction', 'En savoir plus'),
                                        primaryUrl: '#',
                                        backgroundImage: demoServicesImage,
                                        variant: 'soft',
                                        align: 'left',
                                        height: 380,
                                        overlay: 20,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'content_split_media',
                                    settings: {
                                        imageSrc: demoServicesImage,
                                        imageAlt: label('quickTemplateServiceLandingTitle', 'Une offre claire pour votre site'),
                                        mediaKind: 'image',
                                        mediaPosition: 'right',
                                        ratio: 'content-wide',
                                        variant: 'subtle',
                                        align: 'left',
                                        primaryUrl: '#',
                                        secondaryUrl: '#',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'feature_grid',
                                    settings: {
                                        title: label('defaultFeatureTitle', 'Nos points forts'),
                                        titles: [
                                            label('quickTemplateServiceOne', 'Audit rapide'),
                                            label('quickTemplateServiceTwo', 'Production modulaire'),
                                            label('quickTemplateServiceThree', 'Optimisation continue'),
                                        ].join('\n'),
                                        texts: [
                                            label('quickTemplateServiceOneText', 'Cadrage initial et priorites en moins de 48h.'),
                                            label('quickTemplateServiceTwoText', 'Execution progressive avec livrables reutilisables.'),
                                            label('quickTemplateServiceThreeText', 'Suivi des resultats et ameliorations en continu.'),
                                        ].join('\n'),
                                        icons: ['fas fa-bolt', 'fas fa-layer-group', 'fas fa-chart-line'].join('\n'),
                                        columns: 3,
                                        variant: 'strong',
                                        align: 'left',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'pricing_plans',
                                    settings: {
                                        variant: 'subtle',
                                        align: 'center',
                                        columns: 3,
                                        showPopular: 'on',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'testimonial_cards',
                                    settings: {
                                        variant: 'dashed',
                                        align: 'left',
                                        columns: 3,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'contact_section',
                                    settings: {
                                        contactFormSlug: 'contact-main',
                                        variant: 'subtle',
                                        align: 'left',
                                    },
                                },
                            ],
                        ],
                    },
                ],
            },
            'page-faq-conversion': {
                label: label('quickAddTemplatePageFaqConversion', 'Page FAQ conversion'),
                description: label('quickAddTemplatePageFaqConversionDesc', 'Hero, FAQ, reassurance et contact'),
                pageTitle: label('quickTemplatePageTitleFaqConversion', 'FAQ & conversion'),
                sections: [
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'hero',
                                    settings: {
                                        title: label('defaultFaqTitle', 'Questions frequentes'),
                                        subtitle: label('defaultCtaText', 'Utilisez cette page pour traiter les objections et convertir les visiteurs.'),
                                        primaryLabel: label('defaultCallToAction', 'En savoir plus'),
                                        primaryUrl: '#',
                                        backgroundImage: demoShowcaseImage,
                                        variant: 'soft',
                                        align: 'left',
                                        height: 360,
                                        overlay: 20,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'faq_accordion',
                                    settings: {
                                        variant: 'subtle',
                                        align: 'left',
                                        openFirst: 'on',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'testimonial_cards',
                                    settings: {
                                        variant: 'dashed',
                                        align: 'left',
                                        columns: 3,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'contact_section',
                                    settings: {
                                        contactFormSlug: 'contact-main',
                                        variant: 'dark',
                                        align: 'left',
                                    },
                                },
                            ],
                        ],
                    },
                ],
            },
            'page-product-showcase': {
                label: label('quickAddTemplatePageProductShowcase', 'Page vitrine produit'),
                description: label('quickAddTemplatePageProductShowcaseDesc', 'Hero, bloc editorial, scroll cartes, tarifs et logos'),
                pageTitle: label('quickTemplatePageTitleProductShowcase', 'Vitrine produit'),
                sections: [
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'hero',
                                    settings: {
                                        title: label('defaultHeroTitle', 'Creez plus vite avec FlatCMS'),
                                        subtitle: label('defaultHeroSubtitle', 'Composez et publiez des pages modernes sans ecrire de code repetitif.'),
                                        primaryLabel: label('defaultCallToAction', 'En savoir plus'),
                                        primaryUrl: '#',
                                        backgroundImage: demoHomeImage,
                                        variant: 'soft',
                                        align: 'center',
                                        height: 380,
                                        overlay: 20,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'content_split_media',
                                    settings: {
                                        imageSrc: demoShowroomImage,
                                        imageAlt: label('quickTemplatePageTitleProductShowcase', 'Vitrine produit'),
                                        mediaKind: 'image',
                                        mediaPosition: 'right',
                                        ratio: 'media-wide',
                                        variant: 'strong',
                                        align: 'left',
                                        primaryUrl: '#',
                                        secondaryUrl: '#',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'snap_cards',
                                    settings: {
                                        title: 'Mises en avant produit',
                                        titles: demoSnapTitles,
                                        texts: demoSnapTexts,
                                        backgrounds: demoSnapBackgrounds,
                                        links: ['#', '#', '#'].join('\n'),
                                        ctaEnableds: ['on', 'on', 'on'].join('\n'),
                                        ctaLabels: ['Decouvrir', 'Decouvrir', 'Decouvrir'].join('\n'),
                                        variant: 'soft',
                                        align: 'left',
                                        height: 420,
                                        overlay: 45,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'pricing_plans',
                                    settings: {
                                        variant: 'subtle',
                                        align: 'center',
                                        columns: 3,
                                        showPopular: 'on',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'feature_grid',
                                    settings: {
                                        variant: 'dashed',
                                        align: 'left',
                                        columns: 3,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'logo_cloud',
                                    settings: {
                                        title: 'Ils travaillent deja avec des stacks variees',
                                        subtitle: 'Exposez vos integrations et votre ecosysteme en bas de page.',
                                        labels: demoLogoLabels,
                                        logos: demoLogoImages,
                                        links: demoLogoLinks,
                                        columns: 4,
                                        presentationModel: 'classic',
                                        logoHeight: 68,
                                        variant: 'ghost',
                                        align: 'center',
                                    },
                                },
                            ],
                        ],
                    },
                ],
            },
            'page-legal-trust': {
                label: label('quickAddTemplatePageLegalTrust', 'Page legal & confiance'),
                description: label('quickAddTemplatePageLegalTrustDesc', 'Hero, contenu legal, reassurance et contact'),
                pageTitle: label('quickTemplatePageTitleLegalTrust', 'Conformite & confiance'),
                sections: [
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'hero',
                                    settings: {
                                        title: label('defaultLegalTitle', 'Informations legales'),
                                        subtitle: label('defaultLegalText', 'Ce bloc permet d\'afficher clairement les liens de conformite et de politique.'),
                                        backgroundImage: demoTrustImage,
                                        variant: 'default',
                                        align: 'left',
                                        height: 340,
                                        overlay: 20,
                                        showSecondaryCta: 'off',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'text',
                                    settings: {
                                        title: label('defaultLegalTitle', 'Informations legales'),
                                        align: 'left',
                                        text: [
                                            `<p>${escapeHtml(label('defaultLegalText', "Ce bloc permet d'afficher clairement les liens de conformite et de politique."))}</p>`,
                                            '<p><a href="/page/legal-notice">Mentions legales</a> | <a href="/page/privacy-policy">Politique de confidentialite</a> | <a href="/page/contact">Contact</a></p>',
                                        ].join(''),
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'feature_grid',
                                    settings: {
                                        title: label('defaultFeatureTitle', 'Pourquoi nous choisir'),
                                        titles: [
                                            label('quickTemplateContactReassuranceOne', 'Reponse rapide'),
                                            label('quickTemplateContactReassuranceTwo', 'Demarche fiable'),
                                            label('quickTemplateContactReassuranceThree', 'Suivi humain'),
                                        ].join('\n'),
                                        texts: [
                                            label('quickTemplateContactReassuranceOneText', 'Accuse de reception immediat et suivi clair.'),
                                            label('quickTemplateContactReassuranceTwoText', 'Process simple, transparent et sans surprise.'),
                                            label('quickTemplateContactReassuranceThreeText', 'Un interlocuteur unique du debut a la fin.'),
                                        ].join('\n'),
                                        icons: ['fas fa-shield-halved', 'fas fa-file-shield', 'fas fa-circle-check'].join('\n'),
                                        columns: 3,
                                        variant: 'subtle',
                                        align: 'left',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'contact_section',
                                    settings: {
                                        contactFormSlug: 'contact-main',
                                        variant: 'subtle',
                                        align: 'left',
                                    },
                                },
                            ],
                        ],
                    },
                ],
            },
            'page-newsletter-growth': {
                label: label('quickAddTemplatePageNewsletterGrowth', 'Page croissance newsletter'),
                description: label('quickAddTemplatePageNewsletterGrowthDesc', 'Hero, avantages, logos, newsletter et statistiques'),
                pageTitle: label('quickTemplatePageTitleNewsletterGrowth', 'Croissance newsletter'),
                sections: [
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'hero',
                                    settings: {
                                        title: label('defaultNewsletterTitle', 'Newsletter'),
                                        subtitle: label('defaultNewsletterDescription', 'Recevez nos dernieres actualites.'),
                                        primaryLabel: label('defaultNewsletterButton', 'S\'abonner'),
                                        primaryUrl: '#',
                                        backgroundImage: demoPlanningImage,
                                        variant: 'dark',
                                        align: 'center',
                                        height: 380,
                                        overlay: 45,
                                        showSecondaryCta: 'off',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'feature_grid',
                                    settings: {
                                        variant: 'subtle',
                                        align: 'center',
                                        columns: 3,
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'logo_cloud',
                                    settings: {
                                        title: 'Une diffusion pensee pour plusieurs stacks',
                                        subtitle: 'Ajoutez une couche de reassurance avant le formulaire d inscription.',
                                        labels: demoLogoLabels,
                                        logos: demoLogoImages,
                                        links: demoLogoLinks,
                                        columns: 4,
                                        presentationModel: 'classic',
                                        logoHeight: 64,
                                        variant: 'subtle',
                                        align: 'center',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'newsletter_section',
                                    settings: {
                                        variant: 'strong',
                                        align: 'left',
                                    },
                                },
                            ],
                        ],
                    },
                    {
                        layout: 'cols-1',
                        columns: [
                            [
                                {
                                    type: 'stats_section',
                                    settings: {
                                        variant: 'subtle',
                                        align: 'center',
                                        columns: 3,
                                    },
                                },
                            ],
                        ],
                    },
                ],
            },
        };
    }

    function dragHasType(event, mime) {
        try {
            const types = Array.from((event && event.dataTransfer && event.dataTransfer.types) || []);
            return types.includes(mime);
        } catch (e) {
            return false;
        }
    }

    function dragHasAnyType(event, mimes) {
        try {
            const types = Array.from((event && event.dataTransfer && event.dataTransfer.types) || []);
            return (mimes || []).some((mime) => types.includes(mime));
        } catch (e) {
            return false;
        }
    }

    function getDraggedWidgetInitialSettings(dataTransfer) {
        if (!dataTransfer || typeof dataTransfer.getData !== 'function') {
            return {};
        }

        const raw = String(dataTransfer.getData('application/x-pagesbuilder-widget-settings') || '').trim();
        if (!raw) {
            return {};
        }

        try {
            const decoded = JSON.parse(raw);
            return decoded && typeof decoded === 'object' && !Array.isArray(decoded) ? decoded : {};
        } catch (e) {
            return {};
        }
    }

    function setDragGhost(event, sourceNode) {
        if (!event || !event.dataTransfer || typeof event.dataTransfer.setDragImage !== 'function') {
            return;
        }

        if (!sourceNode || typeof sourceNode.cloneNode !== 'function') {
            return;
        }

        try {
            const ghost = sourceNode.cloneNode(true);
            const rect = sourceNode.getBoundingClientRect();
            ghost.style.position = 'absolute';
            ghost.style.top = '-1000px';
            ghost.style.left = '-1000px';
            ghost.style.width = Math.max(180, Math.min(360, Math.round(rect.width || 0))) + 'px';
            ghost.style.pointerEvents = 'none';
            ghost.style.opacity = '0.9';
            ghost.style.transform = 'scale(0.98)';
            ghost.style.boxShadow = '0 18px 40px rgba(15, 23, 42, 0.22)';

            document.body.appendChild(ghost);
            event.dataTransfer.setDragImage(ghost, 18, 18);
            window.setTimeout(() => {
                if (ghost && ghost.parentNode) ghost.parentNode.removeChild(ghost);
            }, 0);
        } catch (e) {
            // ignore
        }
    }

    function clearSectionDropIndicators() {
        if (!canvas) return;

        canvas.querySelectorAll('.pb-section.is-drag-over-before, .pb-section.is-drag-over-after').forEach((node) => {
            node.classList.remove('is-drag-over-before', 'is-drag-over-after');
        });

        if (sectionDropPlaceholder && sectionDropPlaceholder.parentNode) {
            sectionDropPlaceholder.parentNode.removeChild(sectionDropPlaceholder);
        }
        sectionDropPlaceholder = null;
    }

    function placeSectionDropPlaceholder(targetSection, before) {
        if (!canvas || !targetSection) return;

        const sourceId = String(state.drag.sourceId || '');
        const targetId = String(targetSection.dataset.sectionId || '');
        if (!sourceId || !targetId || sourceId === targetId) return;

        if (!sectionDropPlaceholder) {
            sectionDropPlaceholder = document.createElement('div');
            sectionDropPlaceholder.className = 'pb-section-placeholder';
            sectionDropPlaceholder.setAttribute('aria-hidden', 'true');
        }

        const sourceSection = Array.from(canvas.querySelectorAll('.pb-section')).find((node) => {
            return String(node.dataset.sectionId || '') === sourceId;
        }) || null;
        const fallbackHeight = Math.round(targetSection.getBoundingClientRect().height || 120);
        const sourceHeight = sourceSection ? Math.round(sourceSection.getBoundingClientRect().height || fallbackHeight) : fallbackHeight;
        sectionDropPlaceholder.style.height = `${Math.max(72, sourceHeight)}px`;

        const referenceNode = before ? targetSection : targetSection.nextSibling;
        if (sectionDropPlaceholder.parentNode !== canvas || sectionDropPlaceholder.nextSibling !== referenceNode) {
            canvas.insertBefore(sectionDropPlaceholder, referenceNode || null);
        }
    }

    function renderCatalog(searchTerm) {
        const query = normalizeSearchText(searchTerm);
        const queryTerms = tokenizeSearchText(searchTerm);
        const hiddenNativeFormWidgets = new Set(['contact', 'newsletter']);
        if (lastCatalogSearchTerm !== '' && query === '') {
            catalogOpenGroupKey = '';
        }
        lastCatalogSearchTerm = query;
        catalog.innerHTML = '';

        const categories = [
            { key: 'content', label: label('catContent', 'Contenu') },
            { key: 'media', label: label('catMedia', 'Médias') },
            { key: 'navigation', label: label('catNavigation', 'Navigation') },
            { key: 'forms', label: label('catForms', 'Formulaires') },
            { key: 'layout', label: label('catLayout', 'Mise en page') },
            { key: 'advanced', label: label('catAdvanced', 'Avancé') },
        ];

        const matches = widgetDefs.filter((widget) => {
            const widgetType = String(widget && widget.type ? widget.type : '').trim().toLowerCase();
            if (hiddenNativeFormWidgets.has(widgetType)) {
                return false;
            }
            if (isWidgetDefLocked(widget)) {
                return false;
            }
            if (!queryTerms.length) return true;
            const tokens = tokenizeSearchText(`${widget.label} ${widget.type}`);
            if (!tokens.length) return false;
            return queryTerms.every((term) => tokens.some((token) => token.includes(term)));
        });

        const byCategory = new Map();
        categories.forEach((cat) => byCategory.set(cat.key, []));
        matches.forEach((widget) => {
            const key = widget.category && byCategory.has(widget.category) ? widget.category : 'advanced';
            byCategory.get(key).push(widget);
        });
        const dynamicFormWidgets = getDynamicContactFormWidgets(queryTerms);

        const getCategoryCount = (key) => {
            const staticCount = (byCategory.get(key) || []).length;
            if (key === 'forms') {
                return staticCount + dynamicFormWidgets.length;
            }
            return staticCount;
        };

        const availableWidgetGroups = categories
            .map((cat) => cat.key)
            .filter((key) => getCategoryCount(key) > 0);
        let desiredOpen = String(catalogOpenGroupKey || '').trim();
        if (!availableWidgetGroups.includes(desiredOpen)) {
            desiredOpen = '';
        }
        if (queryTerms.length) {
            const first = categories.find((cat) => getCategoryCount(cat.key) > 0);
            desiredOpen = first ? first.key : '';
            catalogOpenGroupKey = desiredOpen;
        } else {
            catalogOpenGroupKey = desiredOpen;
        }

        const fragment = document.createDocumentFragment();
        categories.forEach((cat) => {
            const items = byCategory.get(cat.key) || [];
            const dynamicItems = cat.key === 'forms' ? dynamicFormWidgets : [];
            if (!items.length && !dynamicItems.length) return;

            const group = document.createElement('div');
            group.className = 'pb-widget-group';
            group.dataset.groupKey = cat.key;

            const header = document.createElement('button');
            header.type = 'button';
            header.className = 'pb-widget-group-header';
            header.setAttribute('aria-expanded', desiredOpen === cat.key ? 'true' : 'false');
            header.innerHTML = `
                <span>${escapeHtml(cat.label)}</span>
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            `;

            const panel = document.createElement('div');
            panel.className = 'pb-widget-group-panel';
            panel.style.maxHeight = desiredOpen === cat.key ? 'none' : '0px';

            const body = document.createElement('div');
            body.className = 'pb-widget-group-body';

            items.forEach((widget) => {
                body.appendChild(createWidgetButton(widget));
            });
            dynamicItems.forEach((formWidget) => {
                body.appendChild(createContactFormWidgetButton(formWidget));
            });

            panel.appendChild(body);
            group.appendChild(header);
            group.appendChild(panel);

            if (desiredOpen === cat.key) {
                group.classList.add('is-open');
            }

            header.addEventListener('click', () => {
                const willOpen = !group.classList.contains('is-open');
                if (willOpen) {
                    closeWidgetGroupsExcept(group);
                    catalogOpenGroupKey = cat.key;
                    openWidgetGroup(group);
                } else {
                    catalogOpenGroupKey = '';
                    closeWidgetGroup(group);
                }
            });

            fragment.appendChild(group);
        });

        if (!fragment.childNodes.length) {
            const empty = document.createElement('div');
            empty.className = 'pb-catalog-empty';
            empty.textContent = label('invalidConfig', 'Aucun widget disponible pour ce module.');
            fragment.appendChild(empty);
        }

        catalog.appendChild(fragment);
    }

    function renderSourceCatalog(searchTerm) {
        if (!sourceCatalog) return;
        const sources = buildSourceCatalog(searchTerm || '');
        state.sourceCatalog = sources.items;
        sourceCatalog.innerHTML = '';

        if (!sources.groups.length) {
            const empty = document.createElement('div');
            empty.className = 'pb-source-empty';
            empty.textContent = label('sourceEmpty', 'Aucun element disponible.');
            sourceCatalog.appendChild(empty);
            updateSourceAddButton();
            return;
        }

        sourceCatalog.appendChild(createSourceCatalogBlock(sources.groups));
        updateSourceAddButton();
    }

    function getSourceSelectionKey(item) {
        if (!item || typeof item !== 'object') return '';
        const refType = String(item.refType || '').trim();
        const ref = String(item.ref || '').trim();
        if (refType !== '' && ref !== '') {
            return `ref:${refType}:${ref}`;
        }
        const type = normalizeLinkSourceType(item.type);
        const url = sanitizeUrl(String(item.url || ''));
        const labelValue = String(item.label || '').trim().toLowerCase();
        return [type, url, labelValue].filter(Boolean).join('|');
    }

    function getSourceSelectedKeys() {
        return Array.isArray(state.sourceSelectedKeys) ? state.sourceSelectedKeys : [];
    }

    function isSourceItemSelected(item) {
        const key = getSourceSelectionKey(item);
        if (key === '') return false;
        return getSourceSelectedKeys().includes(key);
    }

    function setSourceItemSelected(item, selected) {
        const key = getSourceSelectionKey(item);
        if (key === '') return;
        const nextKeys = getSourceSelectedKeys().slice();
        const existingIndex = nextKeys.indexOf(key);
        if (selected) {
            if (existingIndex === -1) {
                nextKeys.push(key);
            }
        } else if (existingIndex !== -1) {
            nextKeys.splice(existingIndex, 1);
        }
        state.sourceSelectedKeys = nextKeys;
        updateSourceAddButton();
    }

    function clearSourceSelection() {
        state.sourceSelectedKeys = [];
        updateSourceAddButton();
    }

    function updateSourceAddButton() {
        if (!sourceAddSelectedBtn) return;
        sourceAddSelectedBtn.disabled = getSourceSelectedKeys().length === 0;
    }

    function getDynamicContactFormWidgets(queryTerms) {
        const rawForms = Array.isArray(config.contactForms) ? config.contactForms : [];
        const searchTerms = Array.isArray(queryTerms) ? queryTerms.filter((term) => !!term) : [];
        const seen = new Set();
        const items = [];

        rawForms.forEach((rawItem) => {
            if (!rawItem || typeof rawItem !== 'object') return;

            const slug = String(rawItem.slug || '').trim();
            if (!slug) return;

            const key = slug.toLowerCase();
            if (seen.has(key)) return;
            seen.add(key);

            const name = String(rawItem.name || slug).trim() || slug;
            const haystack = tokenizeSearchText(
                `${name} ${slug} ${label('widgetContact', 'Contact')}`
            );
            if (searchTerms.length && !searchTerms.every((term) => haystack.some((token) => token.includes(term)))) {
                return;
            }

            items.push({
                type: 'contact',
                slug: slug,
                label: name,
                icon: 'fas fa-envelope-open-text',
                isDefault: !!rawItem.isDefault,
            });
        });

        items.sort((a, b) => {
            if (a.isDefault !== b.isDefault) {
                return a.isDefault ? -1 : 1;
            }
            return compareText(a.label, b.label);
        });

        return items;
    }

    function findContactFormConfigBySlug(slug) {
        const targetSlug = String(slug || '').trim().toLowerCase();
        if (targetSlug === '') {
            return null;
        }

        const forms = Array.isArray(config.contactForms) ? config.contactForms : [];
        for (let i = 0; i < forms.length; i += 1) {
            const item = forms[i];
            if (!item || typeof item !== 'object') {
                continue;
            }

            const itemSlug = String(item.slug || '').trim().toLowerCase();
            if (itemSlug === targetSlug) {
                return item;
            }
        }

        return null;
    }

    function getPreferredContactFormSlug() {
        const forms = Array.isArray(config.contactForms) ? config.contactForms : [];
        if (!forms.length) {
            return '';
        }

        const defaultForm = forms.find((item) => (
            item
            && typeof item === 'object'
            && !!item.isDefault
            && String(item.slug || '').trim() !== ''
        ));
        if (defaultForm) {
            return String(defaultForm.slug || '').trim();
        }

        const first = forms.find((item) => item && typeof item === 'object' && String(item.slug || '').trim() !== '');
        return first ? String(first.slug || '').trim() : '';
    }

    function resolveContactFormSlugForInsert(slug) {
        const requestedSlug = String(slug || '').trim();
        if (requestedSlug !== '' && findContactFormConfigBySlug(requestedSlug)) {
            return requestedSlug;
        }

        const preferredSlug = getPreferredContactFormSlug();
        if (preferredSlug !== '') {
            return preferredSlug;
        }

        return requestedSlug;
    }

    function resolveContactFormSettingsUrl(block) {
        const fallbackUrl = String(config.contactFormsAdminUrl || '').trim();
        if (!block || typeof block !== 'object') {
            return fallbackUrl;
        }

        const settings = block.settings && typeof block.settings === 'object' ? block.settings : {};
        const slug = String(settings.formSlug || '').trim();
        if (slug === '') {
            return fallbackUrl;
        }

        const formConfig = findContactFormConfigBySlug(slug);
        if (!formConfig || typeof formConfig !== 'object') {
            return fallbackUrl;
        }

        const editUrl = String(formConfig.editUrl || '').trim();
        return editUrl !== '' ? editUrl : fallbackUrl;
    }

    function normalizeContactPreviewFieldType(value) {
        const safe = String(value || '').trim().toLowerCase();
        const allowed = ['text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date'];
        return allowed.includes(safe) ? safe : 'text';
    }

    function normalizeContactPreviewFieldWidth(value) {
        const safe = String(value || '').trim().toLowerCase();
        return safe === 'half' ? 'half' : 'full';
    }

    function normalizeContactPreviewOptions(options) {
        if (Array.isArray(options)) {
            return options
                .map((option) => String(option || '').trim())
                .filter((option) => option !== '');
        }

        if (typeof options === 'string') {
            return options
                .split(/\r\n|\r|\n|,|;/)
                .map((option) => String(option || '').trim())
                .filter((option) => option !== '');
        }

        return [];
    }

    function normalizeContactPreviewFields(formConfig) {
        const rawFields = Array.isArray(formConfig && formConfig.previewFields) ? formConfig.previewFields : [];
        const fields = rawFields
            .map((rawField) => {
                if (!rawField || typeof rawField !== 'object') {
                    return null;
                }

                const key = String(rawField.key || '').trim();
                const labelText = String(rawField.label || '').trim();
                if (!key || !labelText) {
                    return null;
                }

                return {
                    key,
                    label: labelText,
                    type: normalizeContactPreviewFieldType(rawField.type),
                    required: normalizeTextStyleToggle(rawField.required, false),
                    width: normalizeContactPreviewFieldWidth(rawField.width),
                    placeholder: String(rawField.placeholder || '').trim(),
                    help: String(rawField.help || '').trim(),
                    options: normalizeContactPreviewOptions(rawField.options),
                };
            })
            .filter((field) => !!field);

        if (fields.length > 0) {
            return fields;
        }

        return [
            {
                key: 'name',
                label: String(label('contactFieldName', 'Nom')),
                type: 'text',
                required: true,
                width: 'half',
                placeholder: String(label('defaultContactNamePlaceholder', '')),
                help: '',
                options: [],
            },
            {
                key: 'email',
                label: String(label('contactFieldEmail', 'E-mail')),
                type: 'email',
                required: true,
                width: 'half',
                placeholder: String(label('defaultContactEmailPlaceholder', '')),
                help: '',
                options: [],
            },
            {
                key: 'subject',
                label: String(label('contactSubject', 'Sujet')),
                type: 'text',
                required: true,
                width: 'full',
                placeholder: String(label('defaultContactSubjectPlaceholder', '')),
                help: '',
                options: [],
            },
            {
                key: 'message',
                label: String(label('contactFieldMessage', 'Message')),
                type: 'textarea',
                required: true,
                width: 'full',
                placeholder: String(label('defaultContactMessagePlaceholder', '')),
                help: '',
                options: [],
            },
        ];
    }

    function normalizeContactPreviewAttachments(formConfig) {
        const raw = formConfig && typeof formConfig === 'object' && formConfig.attachments && typeof formConfig.attachments === 'object'
            ? formConfig.attachments
            : {};

        const maxFiles = clampNumber(raw.maxFiles, 1, 5, 1);
        const maxSizeMb = clampNumber(raw.maxSizeMb, 1, 25, 5);
        const extensions = normalizeContactPreviewOptions(raw.extensions).slice(0, 12);

        return {
            enabled: normalizeTextStyleToggle(raw.enabled, false),
            required: normalizeTextStyleToggle(raw.required, false),
            maxFiles: Math.round(maxFiles),
            maxSizeMb: Math.round(maxSizeMb),
            extensions,
        };
    }

    function renderContactPreviewFieldControl(field, fieldId) {
        const safeType = normalizeContactPreviewFieldType(field.type);
        const placeholder = escapeAttr(String(field.placeholder || '').trim());
        const options = Array.isArray(field.options) ? field.options : [];

        if (safeType === 'textarea') {
            return `<textarea id="${escapeAttr(fieldId)}" class="form-input pb-preview-contact-input pb-preview-contact-input-textarea" rows="4" placeholder="${placeholder}" disabled></textarea>`;
        }

        if (safeType === 'select') {
            const emptyOption = escapeHtml(String(label('contactFormSelectPlaceholder', '')));
            const optionsHtml = options
                .map((option) => `<option value="${escapeAttr(option)}">${escapeHtml(option)}</option>`)
                .join('');
            return `<select id="${escapeAttr(fieldId)}" class="form-input pb-preview-contact-input" disabled><option value="">${emptyOption}</option>${optionsHtml}</select>`;
        }

        if (safeType === 'radio' || safeType === 'checkbox') {
            const inputType = safeType;
            const choiceValues = options.length > 0 ? options : [String(field.label || '')];
            const choicesHtml = choiceValues.map((option, index) => {
                const choiceId = `${fieldId}_${index + 1}`;
                return `
                    <label class="pb-preview-contact-choice-item" for="${escapeAttr(choiceId)}">
                        <input id="${escapeAttr(choiceId)}" type="${escapeAttr(inputType)}" disabled>
                        <span>${escapeHtml(option)}</span>
                    </label>
                `;
            }).join('');

            return `<div class="pb-preview-contact-choice-list">${choicesHtml}</div>`;
        }

        const inputTypeMap = {
            text: 'text',
            email: 'email',
            tel: 'tel',
            url: 'url',
            number: 'number',
            date: 'date',
        };
        const inputType = Object.prototype.hasOwnProperty.call(inputTypeMap, safeType)
            ? inputTypeMap[safeType]
            : 'text';
        return `<input id="${escapeAttr(fieldId)}" type="${escapeAttr(inputType)}" class="form-input pb-preview-contact-input" placeholder="${placeholder}" disabled>`;
    }

    function renderContactWidgetPreview(settings) {
        const align = normalizeAlign(String(settings.align || 'left'));
        const slug = String(settings.formSlug || '').trim();
        const formConfig = findContactFormConfigBySlug(slug);
        const title = escapeHtml(String((formConfig && formConfig.name) || label('widgetContact', 'Contact')));
        const descriptionText = String((formConfig && formConfig.description) || '').trim();
        const description = escapeHtml(descriptionText || String(label('defaultContactDescription', '')));
        const submitLabel = escapeHtml(String((formConfig && formConfig.submitLabel) || label('defaultContactButton', '')));
        const successMessage = escapeHtml(String((formConfig && formConfig.successMessage) || ''));
        const fields = normalizeContactPreviewFields(formConfig);
        const attachments = normalizeContactPreviewAttachments(formConfig);

        const fieldsHtml = fields.map((field, index) => {
            const fieldId = `pbPreviewField_${index + 1}`;
            const width = normalizeContactPreviewFieldWidth(field.width);
            const requiredMark = field.required ? '<span class="pb-preview-contact-required" aria-hidden="true">*</span>' : '';
            const help = String(field.help || '').trim();

            return `
                <div class="pb-preview-contact-field pb-preview-contact-field--${escapeAttr(width)}">
                    <label class="form-label pb-preview-contact-label" for="${escapeAttr(fieldId)}">
                        ${escapeHtml(field.label)}${requiredMark}
                    </label>
                    ${renderContactPreviewFieldControl(field, fieldId)}
                    ${help ? `<small class="pb-preview-contact-help">${escapeHtml(help)}</small>` : ''}
                </div>
            `;
        }).join('');

        const attachmentsHtml = attachments.enabled
            ? `
                <div class="pb-preview-contact-field pb-preview-contact-field--full">
                    <label class="form-label pb-preview-contact-label">
                        ${escapeHtml(String(label('contactFormAttachmentsInputLabel', '')))}
                        ${attachments.required ? '<span class="pb-preview-contact-required" aria-hidden="true">*</span>' : ''}
                    </label>
                    <input type="file" class="form-input pb-preview-contact-input" disabled>
                    <small class="pb-preview-contact-help">${escapeHtml(`${attachments.maxFiles} · ${attachments.maxSizeMb} MB · ${attachments.extensions.join(', ')}`)}</small>
                </div>
            `
            : '';

        return `
            <div class="pb-preview-form pb-preview-contact pb-preview-align pb-preview-align-${escapeAttr(align)}">
                <strong>${title}</strong>
                ${description ? `<p>${description}</p>` : ''}
                <form action="#" method="post" class="pb-preview-contact-form">
                    <div class="pb-preview-contact-grid">
                        ${fieldsHtml}
                        ${attachmentsHtml}
                    </div>
                    <button type="button" class="btn btn-primary pb-btn pb-btn-primary">${submitLabel}</button>
                </form>
                ${successMessage ? `<small>${successMessage}</small>` : ''}
            </div>
        `;
    }

    function createWidgetButton(widget) {
        const locked = isWidgetDefLocked(widget);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pb-widget-btn';
        if (locked) {
            btn.classList.add('is-locked');
            btn.setAttribute('aria-disabled', 'true');
            btn.title = label('widgetProLockedNotice', 'Activate a license to use this widget.');
        }
        btn.draggable = !locked;
        btn.dataset.widgetType = widget.type;
        btn.innerHTML = `<span><i class="${escapeAttr(widget.icon)}"></i> ${escapeHtml(widget.label)}${locked ? ` <em class="pb-widget-badge">${escapeHtml(label('widgetProBadge', 'PRO'))}</em>` : ''}</span>`;

        btn.addEventListener('click', () => {
            if (locked) {
                notifyProWidgetLocked();
                return;
            }
            addWidget(widget.type);
        });
        btn.addEventListener('dragstart', (event) => {
            if (locked) {
                event.preventDefault();
                notifyProWidgetLocked();
                return;
            }
            event.dataTransfer.setData('application/x-pagesbuilder-widget', widget.type);
            event.dataTransfer.setData('application/x-pagesbuilder-widget-settings', '');
            event.dataTransfer.effectAllowed = 'copy';
            btn.classList.add('is-dragging');
            setDragGhost(event, btn);
        });
        btn.addEventListener('dragend', () => {
            btn.classList.remove('is-dragging');
        });

        return btn;
    }

    function createContactFormWidgetButton(formWidget) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pb-widget-btn';
        btn.draggable = true;
        btn.dataset.widgetType = 'contact';
        btn.dataset.formSlug = String(formWidget.slug || '').trim();

        const iconClass = String(formWidget.icon || 'fas fa-envelope-open-text').trim();
        const title = String(formWidget.label || formWidget.slug || label('widgetContact', 'Contact')).trim();
        const slug = String(formWidget.slug || '').trim();

        btn.innerHTML = `
            <span>
                <i class="${escapeAttr(iconClass)}"></i>
                ${escapeHtml(title)}
            </span>
        `;

        btn.addEventListener('click', () => {
            if (!slug) return;
            addWidget('contact', { formSlug: slug });
        });

        btn.addEventListener('dragstart', (event) => {
            if (!slug) {
                event.preventDefault();
                return;
            }
            event.dataTransfer.setData('application/x-pagesbuilder-widget', 'contact');
            event.dataTransfer.setData('application/x-pagesbuilder-widget-settings', JSON.stringify({ formSlug: slug }));
            event.dataTransfer.effectAllowed = 'copy';
            btn.classList.add('is-dragging');
            setDragGhost(event, btn);
        });

        btn.addEventListener('dragend', () => {
            btn.classList.remove('is-dragging');
        });

        return btn;
    }

    function getWidgetGroupPanel(group) {
        if (!group) return null;
        return group.querySelector('.pb-widget-group-panel');
    }

    function openWidgetGroup(group) {
        if (!group) return;
        const panel = getWidgetGroupPanel(group);
        if (!panel) return;

        group.classList.add('is-open');
        const header = group.querySelector('.pb-widget-group-header');
        if (header) header.setAttribute('aria-expanded', 'true');

        panel.style.maxHeight = '0px';
        panel.offsetHeight;
        panel.style.maxHeight = panel.scrollHeight + 'px';

        const onEnd = (event) => {
            if (event.propertyName !== 'max-height') return;
            panel.removeEventListener('transitionend', onEnd);
            if (group.classList.contains('is-open')) {
                panel.style.maxHeight = 'none';
            }
        };
        panel.addEventListener('transitionend', onEnd);
    }

    function closeWidgetGroup(group) {
        if (!group) return;
        const panel = getWidgetGroupPanel(group);
        if (!panel) return;

        group.classList.remove('is-open');
        const header = group.querySelector('.pb-widget-group-header');
        if (header) header.setAttribute('aria-expanded', 'false');

        if (panel.style.maxHeight === 'none' || panel.style.maxHeight === '') {
            panel.style.maxHeight = panel.scrollHeight + 'px';
        }
        panel.offsetHeight;
        panel.style.maxHeight = '0px';
    }

    function closeWidgetGroupsExcept(exceptGroup) {
        const groups = catalog ? Array.from(catalog.querySelectorAll('.pb-widget-group.is-open')) : [];
        groups.forEach((group) => {
            if (group === exceptGroup) return;
            closeWidgetGroup(group);
        });
    }

    function buildSourceCatalog(query) {
        const available = Array.isArray(config.availableItems) ? config.availableItems : [];
        const normalizedQuery = normalizeSearchText(query);
        const queryTerms = tokenizeSearchText(query);
        if (lastSourceSearchTerm !== '' && normalizedQuery === '') {
            sourceOpenGroupKey = '';
        }
        lastSourceSearchTerm = normalizedQuery;

        const groupsDef = [
            { key: 'pages', label: label('sourcePages', 'Pages') },
            { key: 'posts', label: label('sourcePosts', 'Articles') },
            { key: 'categories', label: label('sourceCategories', 'Categories') },
            { key: 'cta', label: label('sourceCta', 'CTA') },
        ];

        const grouped = new Map(groupsDef.map((entry) => [entry.key, []]));
        const seen = new Set();

        available.forEach((rawItem) => {
            if (!rawItem || typeof rawItem !== 'object') return;

            const labelText = String(rawItem.label || '').trim();
            const urlText = String(rawItem.url || '').trim();
            if (!labelText) return;

            const typeRaw = String(rawItem.type || '').trim().toLowerCase();
            const type = ['posts', 'categories', 'cta'].includes(typeRaw) ? typeRaw : 'pages';
            const key = `${type}|${labelText.toLowerCase()}|${urlText.toLowerCase()}`;
            if (seen.has(key)) return;
            seen.add(key);

            if (queryTerms.length) {
                const labelTokens = tokenizeSearchText(labelText);
                if (!labelTokens.length) return;
                const matchesLabel = queryTerms.every((term) => labelTokens.some((token) => token.includes(term)));
                if (!matchesLabel) return;
            }

            grouped.get(type).push({
                label: labelText,
                url: urlText,
                type,
                target: String(rawItem.target || ''),
                displayType: String(rawItem.displayType || ''),
                buttonStyle: String(rawItem.buttonStyle || ''),
            });
        });

        const groups = groupsDef
            .map((entry) => {
                const items = (grouped.get(entry.key) || []).sort((a, b) => compareText(a.label, b.label));
                return {
                    key: entry.key,
                    label: entry.label,
                    items,
                };
            })
            .filter((entry) => entry.items.length > 0);

        if (!groups.some((entry) => entry.key === sourceOpenGroupKey)) {
            sourceOpenGroupKey = '';
        }
        if (queryTerms.length) {
            sourceOpenGroupKey = groups.length ? groups[0].key : '';
        }

        const indexedItems = [];
        groups.forEach((group) => {
            group.items = group.items.map((item) => {
                const withIndex = Object.assign({}, item, { _index: indexedItems.length });
                indexedItems.push(withIndex);
                return withIndex;
            });
        });

        return { groups, items: indexedItems };
    }

    function createSourceCatalogBlock(groups) {
        const wrapper = document.createElement('div');
        wrapper.className = 'pb-source-catalog';

        groups.forEach((groupData) => {
            const group = document.createElement('div');
            group.className = 'pb-source-group';
            group.dataset.groupKey = groupData.key;

            const header = document.createElement('button');
            header.type = 'button';
            header.className = 'pb-source-group-header';
            header.setAttribute('aria-expanded', sourceOpenGroupKey === groupData.key ? 'true' : 'false');
            header.innerHTML = `
                <span class="pb-source-group-title">${escapeHtml(groupData.label)}</span>
                <span class="pb-source-group-meta">
                    <span class="pb-source-count">${groupData.items.length}</span>
                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                </span>
            `;

            const panel = document.createElement('div');
            panel.className = 'pb-source-group-panel';
            panel.style.maxHeight = sourceOpenGroupKey === groupData.key ? 'none' : '0px';

            const body = document.createElement('div');
            body.className = 'pb-source-group-body';
            if (groupData.items.length) {
                groupData.items.forEach((item) => {
                    body.appendChild(createSourceItemButton(item));
                });
            } else {
                const empty = document.createElement('div');
                empty.className = 'pb-source-empty';
                empty.textContent = label('sourceEmpty', 'Aucun element disponible.');
                body.appendChild(empty);
            }

            panel.appendChild(body);
            group.appendChild(header);
            group.appendChild(panel);
            if (sourceOpenGroupKey === groupData.key) {
                group.classList.add('is-open');
            }

            header.addEventListener('click', () => {
                const willOpen = !group.classList.contains('is-open');
                if (willOpen) {
                    closeSourceGroupsExcept(wrapper, group);
                    sourceOpenGroupKey = groupData.key;
                    openSourceGroup(group);
                } else {
                    sourceOpenGroupKey = '';
                    closeSourceGroup(group);
                }
            });

            wrapper.appendChild(group);
        });

        return wrapper;
    }

    function createSourceItemButton(item) {
        const card = document.createElement('div');
        const selected = isSourceItemSelected(item);
        card.className = 'pb-source-item';
        card.draggable = true;
        card.dataset.sourceIndex = String(item._index);
        card.title = `${label('sourceInsert', 'Ajouter un element')} : ${String(item.label || '')}`;
        if (selected) {
            card.classList.add('is-selected');
        }
        card.innerHTML = `
            <span>
                <i class="${escapeAttr(getSourceTypeIcon(item.type))}"></i>
                ${escapeHtml(item.label)}
            </span>
            <span class="fc-builder-source-select">
                <input type="checkbox" class="fc-builder-source-checkbox" ${selected ? 'checked' : ''} aria-label="${escapeAttr(String(item.label || ''))}">
            </span>
        `;

        const checkbox = card.querySelector('.fc-builder-source-checkbox');
        if (checkbox) {
            checkbox.addEventListener('click', (event) => {
                event.stopPropagation();
            });
            checkbox.addEventListener('change', () => {
                const checked = checkbox.checked === true;
                setSourceItemSelected(item, checked);
                card.classList.toggle('is-selected', checked);
            });
        }

        card.addEventListener('click', (event) => {
            if (!checkbox) return;
            if (event.target === checkbox) return;
            const nextChecked = !checkbox.checked;
            checkbox.checked = nextChecked;
            setSourceItemSelected(item, nextChecked);
            card.classList.toggle('is-selected', nextChecked);
        });

        card.addEventListener('dragstart', (event) => {
            event.dataTransfer.setData('application/x-pagesbuilder-source', String(item._index));
            event.dataTransfer.effectAllowed = 'copy';
            card.classList.add('is-dragging');
            setDragGhost(event, card);
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('is-dragging');
        });

        return card;
    }

    function getSourceTypeLabel(type) {
        const value = normalizeLinkSourceType(type);
        if (value === 'posts') return label('sourcePosts', 'Articles');
        if (value === 'categories') return label('sourceCategories', 'Categories');
        if (value === 'cta') return label('sourceCta', 'CTA');
        return label('sourcePages', 'Pages');
    }

    function getSourceTypeIcon(type) {
        const value = normalizeLinkSourceType(type);
        if (value === 'posts') return 'fas fa-newspaper';
        if (value === 'categories') return 'fas fa-folder-open';
        if (value === 'cta') return 'fas fa-bolt';
        return 'fas fa-file-alt';
    }

    function closeSourceGroupsExcept(scope, exceptGroup) {
        if (!scope) return;
        const groups = Array.from(scope.querySelectorAll('.pb-source-group.is-open'));
        groups.forEach((group) => {
            if (group === exceptGroup) return;
            closeSourceGroup(group);
        });
    }

    function openSourceGroup(group) {
        if (!group) return;
        const panel = group.querySelector('.pb-source-group-panel');
        if (!panel) return;

        group.classList.add('is-open');
        const header = group.querySelector('.pb-source-group-header');
        if (header) header.setAttribute('aria-expanded', 'true');

        panel.style.maxHeight = '0px';
        panel.offsetHeight;
        panel.style.maxHeight = panel.scrollHeight + 'px';

        const onEnd = (event) => {
            if (event.propertyName !== 'max-height') return;
            panel.removeEventListener('transitionend', onEnd);
            if (group.classList.contains('is-open')) {
                panel.style.maxHeight = 'none';
            }
        };
        panel.addEventListener('transitionend', onEnd);
    }

    function closeSourceGroup(group) {
        if (!group) return;
        const panel = group.querySelector('.pb-source-group-panel');
        if (!panel) return;

        group.classList.remove('is-open');
        const header = group.querySelector('.pb-source-group-header');
        if (header) header.setAttribute('aria-expanded', 'false');

        if (panel.style.maxHeight === 'none' || panel.style.maxHeight === '') {
            panel.style.maxHeight = panel.scrollHeight + 'px';
        }
        panel.offsetHeight;
        panel.style.maxHeight = '0px';
    }

    function addSelectedSourceItems() {
        const selectedKeys = getSourceSelectedKeys();
        if (!selectedKeys.length) return;

        const selectedItems = (Array.isArray(state.sourceCatalog) ? state.sourceCatalog : [])
            .filter((item) => selectedKeys.includes(getSourceSelectionKey(item)));
        if (!selectedItems.length) {
            clearSourceSelection();
            renderSourceCatalog(sourceSearch ? (sourceSearch.value || '') : '');
            return;
        }

        const target = getSmartInsertTarget();
        let anchorBlockId = target.targetBlockId;
        let insertPosition = target.position;

        selectedItems.forEach((item) => {
            const insertedBlock = insertSourceItem(item, target.sectionId, target.columnId, anchorBlockId, insertPosition);
            if (insertedBlock && insertedBlock.id) {
                anchorBlockId = insertedBlock.id;
                insertPosition = 'after';
            }
        });

        clearSourceSelection();
        renderSourceCatalog(sourceSearch ? (sourceSearch.value || '') : '');
    }

    function getSourceItemByIndex(sourceIndex) {
        const index = Number(sourceIndex);
        if (!Number.isFinite(index)) return null;
        const item = Array.isArray(state.sourceCatalog) ? state.sourceCatalog[index] : null;
        if (!item || typeof item !== 'object') return null;
        return Object.assign({}, item);
    }

    function createBlockFromSourceItem(item) {
        if (!item || typeof item !== 'object') return null;

        const rawLabel = String(item.label || '').trim();
        if (!rawLabel) return null;

        const url = sanitizeUrl(String(item.url || '')) || '#';
        const target = ['_self', '_blank'].includes(String(item.target || '')) ? String(item.target) : '_self';
        const type = String(item.type || '').trim().toLowerCase();
        const displayType = String(item.displayType || '').trim().toLowerCase();
        const buttonStyle = String(item.buttonStyle || '').trim().toLowerCase();

        const makeTextLinkBlock = () => {
            const targetAttr = target === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';
            const safeLabel = escapeHtml(rawLabel);
            const safeUrl = escapeAttr(url);
            return {
                id: makeId(),
                type: 'text',
                settings: applyWidgetDefaults('text', {
                    text: `<p><a href="${safeUrl}"${targetAttr}>${safeLabel}</a></p>`,
                    align: 'left',
                    color: '',
                }),
            };
        };

        if (type === 'pages' || type === 'posts' || type === 'categories') {
            return makeTextLinkBlock();
        }

        let variant = 'ghost';
        if (type === 'cta') {
            if (displayType === 'link') {
                return makeTextLinkBlock();
            }
            if (displayType === 'button') {
                variant = ['primary', 'secondary', 'ghost'].includes(buttonStyle) ? buttonStyle : 'primary';
            }
        }

        return {
            id: makeId(),
            type: 'button',
            settings: applyWidgetDefaults('button', {
                label: rawLabel,
                url: url,
                target: target,
                align: 'left',
                variant: variant,
            }),
        };
    }

    function insertSourceItem(item, sectionId, columnId, targetBlockId, position) {
        const newBlock = createBlockFromSourceItem(item);
        if (!newBlock) return null;

        const target = ensureColumnTarget(sectionId, columnId);
        if (!target) return null;

        const blocks = target.column.blocks;
        let insertIndex = blocks.length;
        if (targetBlockId) {
            const targetIndex = blocks.findIndex((b) => String(b.id || '') === String(targetBlockId));
            if (targetIndex !== -1) {
                insertIndex = position === 'before' ? targetIndex : targetIndex + 1;
            }
        }

        blocks.splice(insertIndex, 0, newBlock);
        selectBlock(target.section.id, target.column.id, newBlock.id);
        renderCanvas();
        renderInspector();
        openInspectorAfterWidgetInsert();
        return newBlock;
    }

    function insertSourceItemAsSectionAt(item, index) {
        const block = createBlockFromSourceItem(item);
        if (!block) return;

        const section = {
            id: makeId('sec'),
            layoutTemplate: buildEqualSectionTemplate(1),
            settings: normalizeSectionSettings({}),
            columns: [
                {
                    id: makeId('col'),
                    blocks: [block],
                },
            ],
        };

        insertSectionAt(index, section);
        selectBlock(section.id, section.columns[0].id, block.id);
        renderCanvas();
        renderInspector();
        openInspectorAfterWidgetInsert();
    }

    function appendSourceItemToEnd(sourceIndex) {
        const item = getSourceItemByIndex(sourceIndex);
        if (!item) return;

        const target = ensureColumnTarget('', '');
        if (!target) return;

        insertSourceItem(item, target.section.id, target.column.id, null, 'after');
    }

    function addWidget(type, initialSettings) {
        const def = getWidgetDef(type);
        if (!def) return;
        if (isWidgetDefLocked(def)) {
            notifyProWidgetLocked();
            return;
        }

        const target = getSmartInsertTarget();
        insertWidget(type, target.sectionId, target.columnId, target.targetBlockId, target.position, initialSettings);
    }

    function openInspectorAfterWidgetInsert() {
        setSidebarState('left', { open: false }, { persist: true });
        openInspectorSheet();
        renderInspector();
    }

    function openInspectorSidebar() {
        setSidebarState('right', { open: true }, { persist: true });
    }

    function openWidgetsForColumn(sectionId, columnId) {
        state.preferredInsertTarget = {
            sectionId: String(sectionId || ''),
            columnId: String(columnId || ''),
        };
        setSidebarState('left', { open: true }, { persist: true });
        if (widgetSearch) {
            window.requestAnimationFrame(() => widgetSearch.focus());
        }
    }

    function normalizeInspectorMode(mode) {
        return String(mode || '') === 'spacing' ? 'spacing' : 'widget';
    }

    function setInspectorMode(mode) {
        state.inspectorMode = normalizeInspectorMode(mode);
    }

    function defaultBoxSettings() {
        return { mt: 0, mr: 0, mb: 0, ml: 0, pt: 0, pr: 0, pb: 0, pl: 0 };
    }

    function normalizeBoxSettingValue(key, value) {
        const num = Number(value);
        const safe = Number.isFinite(num) ? Math.round(num) : 0;
        const isPadding = String(key || '').startsWith('p');
        if (isPadding) {
            return Math.max(0, Math.min(240, safe));
        }
        return Math.max(-240, Math.min(240, safe));
    }

    function getBlockBoxSettings(settings) {
        const defaults = defaultBoxSettings();
        const source = settings && typeof settings === 'object' && settings.__box && typeof settings.__box === 'object'
            ? settings.__box
            : {};

        const next = {};
        Object.keys(defaults).forEach((key) => {
            next[key] = normalizeBoxSettingValue(key, source[key]);
        });
        return next;
    }

    function setBlockBoxSettings(settings, box) {
        if (!settings || typeof settings !== 'object') return;
        const normalized = getBlockBoxSettings({ __box: box || {} });
        const hasValue = Object.values(normalized).some((n) => Number(n) !== 0);
        if (hasValue) {
            settings.__box = normalized;
        } else if (Object.prototype.hasOwnProperty.call(settings, '__box')) {
            delete settings.__box;
        }
    }

    function buildBlockBoxInlineStyle(settings) {
        const box = getBlockBoxSettings(settings || {});
        const hasValue = Object.values(box).some((n) => Number(n) !== 0);
        if (!hasValue) return '';
        return `margin:${box.mt}px ${box.mr}px ${box.mb}px ${box.ml}px;padding:${box.pt}px ${box.pr}px ${box.pb}px ${box.pl}px;`;
    }

    function isBoxEditorOpen() {
        return !!(boxEditOverlay && boxEditOverlay.classList.contains('is-open'));
    }

    function closeBlockBoxEditor() {
        if (boxEditOverlay) {
            boxEditOverlay.classList.remove('is-open');
            boxEditOverlay.setAttribute('aria-hidden', 'true');
        }
        boxEditBlockId = '';
        boxEditAnchor = null;
    }

    function positionBlockBoxEditor() {
        if (!boxEditPanel || !boxEditOverlay || !isBoxEditorOpen()) return;

        boxEditPanel.style.left = '0px';
        boxEditPanel.style.top = '0px';
        boxEditPanel.style.visibility = 'hidden';

        window.requestAnimationFrame(() => {
            if (!boxEditPanel || !isBoxEditorOpen()) return;

            const panelRect = boxEditPanel.getBoundingClientRect();
            const anchorRect = boxEditAnchor && typeof boxEditAnchor.getBoundingClientRect === 'function'
                ? boxEditAnchor.getBoundingClientRect()
                : null;

            let left = (window.innerWidth - panelRect.width) / 2;
            let top = (window.innerHeight - panelRect.height) / 2;

            if (anchorRect) {
                left = anchorRect.right - panelRect.width;
                top = anchorRect.bottom + 12;
                if (top + panelRect.height > window.innerHeight - 10) {
                    top = anchorRect.top - panelRect.height - 12;
                }
            }

            left = Math.max(10, Math.min(left, window.innerWidth - panelRect.width - 10));
            top = Math.max(10, Math.min(top, window.innerHeight - panelRect.height - 10));

            boxEditPanel.style.left = `${Math.round(left)}px`;
            boxEditPanel.style.top = `${Math.round(top)}px`;
            boxEditPanel.style.visibility = 'visible';
        });
    }

    function refreshBlockBoxEditor() {
        if (!boxEditOverlay || !boxEditBlockId) return;
        const found = findBlockLocation(boxEditBlockId);
        if (!found) {
            closeBlockBoxEditor();
            return;
        }

        const box = getBlockBoxSettings(found.block.settings || {});
        Object.keys(box).forEach((key) => {
            const input = boxEditOverlay.querySelector(`[data-box-key="${escapeCssSelector(key)}"]`);
            if (!input) return;
            input.value = String(box[key] ?? 0);
        });
    }

    function renderCanvas() {
        canvas.innerHTML = '';
        sectionDropPlaceholder = null;

        const sections = getSections();
        if (boxEditBlockId && !findBlockLocation(boxEditBlockId)) {
            closeBlockBoxEditor();
        }

        if (!sections.length) {
            const fragment = document.createDocumentFragment();
            fragment.appendChild(createInsertZone(0));
            canvas.appendChild(fragment);
            publishBuilderState('render-canvas-empty');
            return;
        }

        const fragment = document.createDocumentFragment();

        sections.forEach((section, sectionIndex) => {
            const columns = Array.isArray(section.columns) ? section.columns : [];
            const sectionEl = document.createElement('section');
            sectionEl.className = 'pb-section';
            sectionEl.dataset.sectionId = String(section.id || '');
            const sectionSettings = normalizeSectionSettings(section.settings || {});
            const isSelectedSection = !!(
                state.selection
                && state.selection.kind === 'section'
                && String(state.selection.sectionId || '') === String(section.id || '')
            );

            const colCount = sanitizeColumnCount(columns.length || 1);
            const removeSectionTitle = label('removeSection', 'Remove this section');
            const sectionSettingsTitle = label('sectionSettingsOpen', 'Ouvrir les réglages de la section');
            const moveSectionUpTitle = label('moveSectionUp', 'Monter la section');
            const moveSectionDownTitle = label('moveSectionDown', 'Descendre la section');
            const canMoveSectionUp = sectionIndex > 0;
            const canMoveSectionDown = sectionIndex < sections.length - 1;

            sectionEl.innerHTML = `
                <div class="pb-section-controls" aria-hidden="true">
                    <button type="button" class="pb-section-tool pb-section-move-up" title="${escapeAttr(moveSectionUpTitle)}"${canMoveSectionUp ? '' : ' disabled'}>
                        <i class="fas fa-arrow-up" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="pb-section-tool pb-section-move-down" title="${escapeAttr(moveSectionDownTitle)}"${canMoveSectionDown ? '' : ' disabled'}>
                        <i class="fas fa-arrow-down" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="pb-section-tool pb-section-settings${isSelectedSection ? ' is-active' : ''}" title="${escapeAttr(sectionSettingsTitle)}">
                        <i class="fas fa-cog" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="pb-section-tool pb-section-remove" title="${escapeAttr(removeSectionTitle)}">
                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="pb-section-shell">
                    <div class="pb-section-overlay" aria-hidden="true"></div>
                    <div class="pb-section-inner pb-section-inner-${escapeAttr(sectionSettings.containerMode)}">
                        <div class="pb-section-columns cols-${colCount}"></div>
                    </div>
                </div>
            `;
            applySectionCanvasSettings(sectionEl, sectionSettings);
            sectionEl.classList.toggle('is-selected', isSelectedSection);
            if (isSelectedSection) {
                sectionEl.classList.add('is-controls-visible');
            }

            let sectionControlsHideTimer = null;
            let sectionControlsCloseTimer = null;
            const sectionControlsHideDelay = 900;
            const sectionControlsCloseDelay = 900;
            const keepSectionControlsOpen = () => {
                if (sectionControlsCloseTimer) {
                    clearTimeout(sectionControlsCloseTimer);
                    sectionControlsCloseTimer = null;
                }
                sectionEl.classList.add('is-controls-visible');
            };
            const scheduleSectionControlsClose = () => {
                if (sectionControlsCloseTimer) {
                    clearTimeout(sectionControlsCloseTimer);
                }
                sectionControlsCloseTimer = window.setTimeout(() => {
                    sectionControlsCloseTimer = null;
                    sectionEl.classList.remove('is-controls-visible');
                    sectionEl.classList.remove('is-hovering-widget');
                }, sectionControlsCloseDelay);
            };
            const applySectionControlsVisibility = (hideForWidgetHover) => {
                if (!hideForWidgetHover) {
                    if (sectionControlsHideTimer) {
                        clearTimeout(sectionControlsHideTimer);
                        sectionControlsHideTimer = null;
                    }
                    sectionEl.classList.remove('is-hovering-widget');
                    keepSectionControlsOpen();
                    return;
                }

                if (sectionEl.classList.contains('is-hovering-widget') || sectionControlsHideTimer) {
                    return;
                }

                sectionControlsHideTimer = window.setTimeout(() => {
                    sectionControlsHideTimer = null;
                    sectionEl.classList.add('is-hovering-widget');
                }, sectionControlsHideDelay);
            };

            sectionEl.addEventListener('mouseenter', () => {
                applySectionControlsVisibility(false);
            });

            sectionEl.addEventListener('mousemove', (event) => {
                const isOverWidget = !!(event.target && event.target.closest('.pb-block-item'));
                applySectionControlsVisibility(isOverWidget);
            });

            sectionEl.addEventListener('mouseleave', () => {
                applySectionControlsVisibility(false);
                scheduleSectionControlsClose();
            });

            const sectionControls = sectionEl.querySelector('.pb-section-controls');
            if (sectionControls) {
                sectionControls.addEventListener('mouseenter', () => {
                    applySectionControlsVisibility(false);
                });
                sectionControls.addEventListener('mouseleave', () => {
                    if (!sectionEl.matches(':hover')) {
                        scheduleSectionControlsClose();
                    }
                });
            }

            const settingsBtn = sectionEl.querySelector('.pb-section-settings');
            if (settingsBtn) {
                settingsBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    closeQuickAdd();
                    closeBlockBoxEditor();
                    selectSection(String(section.id || ''));
                    openInspectorSheet();
                    withStableViewport(() => {
                        renderCanvas();
                        renderInspector();
                    });
                });
            }

            const moveUpBtn = sectionEl.querySelector('.pb-section-move-up');
            if (moveUpBtn) {
                moveUpBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (!canMoveSectionUp) {
                        return;
                    }
                    closeQuickAdd();
                    closeBlockBoxEditor();
                    withStableViewport(() => {
                        moveSectionByOffset(String(section.id || ''), -1);
                    });
                });
            }

            const moveDownBtn = sectionEl.querySelector('.pb-section-move-down');
            if (moveDownBtn) {
                moveDownBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (!canMoveSectionDown) {
                        return;
                    }
                    closeQuickAdd();
                    closeBlockBoxEditor();
                    withStableViewport(() => {
                        moveSectionByOffset(String(section.id || ''), 1);
                    });
                });
            }

            sectionEl.addEventListener('dragover', (event) => {
                if (!dragHasType(event, 'application/x-pagesbuilder-section')) {
                    return;
                }

                const draggedSectionId = event.dataTransfer.getData('application/x-pagesbuilder-section') || String(state.drag.sourceId || '');
                if (!draggedSectionId || draggedSectionId === String(section.id || '')) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const rect = sectionEl.getBoundingClientRect();
                const before = event.clientY < rect.top + rect.height / 2;
                canvas.querySelectorAll('.pb-section').forEach((node) => {
                    if (node !== sectionEl) {
                        node.classList.remove('is-drag-over-before', 'is-drag-over-after');
                    }
                });
                sectionEl.classList.toggle('is-drag-over-before', before);
                sectionEl.classList.toggle('is-drag-over-after', !before);
                state.drag.dropId = String(section.id || '');
                state.drag.dropPosition = before ? 'before' : 'after';
                placeSectionDropPlaceholder(sectionEl, before);
            });

            sectionEl.addEventListener('dragleave', (event) => {
                const next = event.relatedTarget;
                if (next && sectionEl.contains(next)) {
                    return;
                }
                sectionEl.classList.remove('is-drag-over-before', 'is-drag-over-after');
            });

            sectionEl.addEventListener('drop', (event) => {
                const draggedSectionId = event.dataTransfer.getData('application/x-pagesbuilder-section') || String(state.drag.sourceId || '');
                if (!draggedSectionId || draggedSectionId === String(section.id || '')) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                clearSectionDropIndicators();
                sectionEl.classList.remove('is-drag-over-before', 'is-drag-over-after');
                moveSection(draggedSectionId, String(section.id || ''), state.drag.dropPosition || 'after');
            });

            const removeBtn = sectionEl.querySelector('.pb-section-remove');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    confirmDeleteAction(
                        label('removeSectionConfirm', 'Delete this section?'),
                        () => {
                            removeSection(String(section.id || ''));
                        }
                    );
                });
            }

            const colsWrap = sectionEl.querySelector('.pb-section-columns');
            const sectionLayoutTemplate = sanitizeSectionLayoutTemplate(
                String(section.layoutTemplate || ''),
                colCount
            );
            if (colsWrap) {
                colsWrap.style.gridTemplateColumns = sectionLayoutTemplate;
            }
            const effectiveColumns = columns.length ? columns.slice(0, colCount) : [{ id: makeId('col'), blocks: [] }];
            const isEmpty = effectiveColumns.every((column) => Array.isArray(column.blocks) ? column.blocks.length === 0 : true);
            if (isEmpty) {
                sectionEl.classList.add('is-empty');
            }

            effectiveColumns.forEach((column) => {
                if (!colsWrap) return;
                    const blocks = Array.isArray(column.blocks) ? column.blocks : [];
                    const colEl = document.createElement('div');
                    colEl.className = 'pb-col';
                    colEl.dataset.sectionId = String(section.id || '');
                    colEl.dataset.columnId = String(column.id || '');
                    const minColumnHeight = Math.max(120, Math.min(760, 120 + Math.max(0, blocks.length - 1) * 72));
                    colEl.style.setProperty('--pb-col-min-height', `${minColumnHeight}px`);

                    colEl.addEventListener('dragover', (event) => {
                        if (!dragHasAnyType(event, ['application/x-pagesbuilder-widget', 'application/x-pagesbuilder-block', 'application/x-pagesbuilder-source'])) {
                            return;
                        }
                        event.preventDefault();
                        event.stopPropagation();
                        colEl.classList.add('is-drop-target');
                    });

                    colEl.addEventListener('dragleave', () => {
                        colEl.classList.remove('is-drop-target');
                    });

                    colEl.addEventListener('drop', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        colEl.classList.remove('is-drop-target');

                        const widgetType = event.dataTransfer.getData('application/x-pagesbuilder-widget');
                        const widgetSettings = getDraggedWidgetInitialSettings(event.dataTransfer);
                        const draggedBlockId = event.dataTransfer.getData('application/x-pagesbuilder-block');
                        const sourceIndex = event.dataTransfer.getData('application/x-pagesbuilder-source');

                        if (sourceIndex !== '') {
                            const sourceItem = getSourceItemByIndex(sourceIndex);
                            if (sourceItem) {
                                insertSourceItem(sourceItem, String(section.id || ''), String(column.id || ''), null, 'after');
                            }
                            return;
                        }

                        if (widgetType) {
                            insertWidget(widgetType, String(section.id || ''), String(column.id || ''), null, 'after', widgetSettings);
                            return;
                        }

                        if (draggedBlockId) {
                            moveBlock(draggedBlockId, String(section.id || ''), String(column.id || ''), null, 'after');
                        }
                    });

                    if (!blocks.length) {
                        const empty = document.createElement('button');
                        empty.type = 'button';
                        empty.className = 'pb-col-empty';
                        empty.innerHTML = `
                            <div class="pb-col-empty-inner">
                                <div class="pb-col-empty-shape pb-col-empty-shape-top" aria-hidden="true"></div>
                                <div class="pb-col-empty-view">
                                    <span class="pb-col-first-add" aria-hidden="true">
                                        <i class="fas fa-plus"></i>
                                    </span>
                                </div>
                                <div class="pb-col-empty-shape pb-col-empty-shape-bottom" aria-hidden="true"></div>
                                <div class="pb-col-empty-label">${escapeHtml(label('addWidget', 'Ajouter un widget'))}</div>
                            </div>
                        `;
                        empty.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            openWidgetsForColumn(String(section.id || ''), String(column.id || ''));
                        });
                        colEl.appendChild(empty);
                    } else {
		                        blocks.forEach((block) => {
	                            const def = getWidgetDef(block.type);
	                            const row = document.createElement('article');
	                            const isSelected = state.selection && state.selection.kind === 'block' && state.selection.blockId === block.id;
	                            row.className = 'pb-block-item' + (isSelected ? ' is-selected' : '');
                            row.dataset.blockId = block.id;
                            row.dataset.sectionId = String(section.id || '');
                            row.dataset.columnId = String(column.id || '');
                            row.dataset.blockType = String(block.type || '');
	                            row.draggable = true;

		                            const dragLabel = label('drag', 'Glisser');
		                            const removeLabel = label('removeBlock', 'Remove this block');
		                            row.innerHTML = `
		                                <div class="pb-block-controls" aria-hidden="true">
		                                    <button type="button" class="pb-block-control pb-block-drag" aria-label="${escapeAttr(dragLabel)}" title="${escapeAttr(dragLabel)}">
		                                        <i class="fas fa-grip-vertical" aria-hidden="true"></i>
		                                    </button>
		                                    <button type="button" class="pb-block-control pb-block-remove is-danger" aria-label="${escapeAttr(removeLabel)}" title="${escapeAttr(removeLabel)}">
		                                        <i class="fas fa-trash-alt" aria-hidden="true"></i>
		                                    </button>
		                                </div>
		                                <div class="pb-block-preview">${renderBlockPreview(block)}</div>
		                            `;
                                    applyBlockPreviewPresentation(row.querySelector('.pb-block-preview'));

                                    const boxInlineStyle = buildBlockBoxInlineStyle(block.settings || {});
                                    if (boxInlineStyle !== '') {
                                        row.style.cssText = boxInlineStyle;
                                    }

	                            row.addEventListener('click', (event) => {
	                                if (event.target.closest('.pb-block-remove')) {
                                        confirmDeleteAction(
                                            label('removeBlockConfirm', 'Delete this block?'),
                                            () => {
	                                            removeBlock(block.id);
                                            }
	                                        );
	                                    return;
	                                }
	                                closeQuickAdd();
	                                closeBlockBoxEditor();
	                                selectBlock(String(section.id || ''), String(column.id || ''), block.id, 'widget');
	                                openInspectorSheet();
	                                withStableViewport(() => {
	                                    renderCanvas();
	                                    renderInspector();
	                                });
	                            });

                            row.addEventListener('dragstart', (event) => {
                                state.drag.kind = 'block';
                                state.drag.sourceId = block.id;
                                event.dataTransfer.setData('application/x-pagesbuilder-block', block.id);
                                event.dataTransfer.effectAllowed = 'move';
                                row.classList.add('is-dragging');
                                setDragGhost(event, row);
                            });

                            row.addEventListener('dragend', () => {
                                row.classList.remove('is-dragging', 'is-drag-over-before', 'is-drag-over-after');
                                state.drag.kind = null;
                                state.drag.sourceId = null;
                                state.drag.dropId = null;
                                state.drag.dropPosition = 'after';
                            });

                            row.addEventListener('dragover', (event) => {
                                if (!dragHasAnyType(event, ['application/x-pagesbuilder-widget', 'application/x-pagesbuilder-block', 'application/x-pagesbuilder-source'])) {
                                    return;
                                }
                                event.preventDefault();
                                event.stopPropagation();
                                const rect = row.getBoundingClientRect();
                                const before = event.clientY < rect.top + rect.height / 2;
                                row.classList.toggle('is-drag-over-before', before);
                                row.classList.toggle('is-drag-over-after', !before);
                                state.drag.dropId = block.id;
                                state.drag.dropPosition = before ? 'before' : 'after';
                            });

                            row.addEventListener('dragleave', () => {
                                row.classList.remove('is-drag-over-before', 'is-drag-over-after');
                            });

                            row.addEventListener('drop', (event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                row.classList.remove('is-drag-over-before', 'is-drag-over-after');

                                const widgetType = event.dataTransfer.getData('application/x-pagesbuilder-widget');
                                const widgetSettings = getDraggedWidgetInitialSettings(event.dataTransfer);
                                const draggedBlockId = event.dataTransfer.getData('application/x-pagesbuilder-block');
                                const sourceIndex = event.dataTransfer.getData('application/x-pagesbuilder-source');

                                const position = state.drag.dropPosition || 'after';
                                if (sourceIndex !== '') {
                                    const sourceItem = getSourceItemByIndex(sourceIndex);
                                    if (sourceItem) {
                                        insertSourceItem(sourceItem, String(section.id || ''), String(column.id || ''), block.id, position);
                                    }
                                    return;
                                }

                                if (widgetType) {
                                    insertWidget(widgetType, String(section.id || ''), String(column.id || ''), block.id, position, widgetSettings);
                                    return;
                                }

                                if (draggedBlockId) {
                                    moveBlock(draggedBlockId, String(section.id || ''), String(column.id || ''), block.id, position);
                                }
                            });

		                            colEl.appendChild(row);
		                        });

                        const addBtn = document.createElement('button');
                        addBtn.type = 'button';
                        addBtn.className = 'pb-col-add';
                        addBtn.innerHTML = `
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            <span>${escapeHtml(label('addWidget', 'Ajouter un widget'))}</span>
                        `;
                        addBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            openWidgetsForColumn(String(section.id || ''), String(column.id || ''));
                        });
                        colEl.appendChild(addBtn);
                    }

                    if (colsWrap) {
                        colsWrap.appendChild(colEl);
                    }
                });

            fragment.appendChild(sectionEl);
            if (sectionIndex < sections.length - 1) {
                fragment.appendChild(createInsertZone(sectionIndex + 1));
            }
        });

        // Always show an insert point at the end so users can quickly add
        // Spacer / Divider / Section even when there is only one section.
        fragment.appendChild(createInsertZone(sections.length));

        canvas.appendChild(fragment);
        publishBuilderState('render-canvas');
    }

    function createInsertZone(insertIndex) {
        const zone = document.createElement('div');
        zone.className = 'pb-insert-zone';
        zone.dataset.insertIndex = String(insertIndex);
        zone.tabIndex = 0;
        zone.setAttribute('role', 'button');
        zone.setAttribute('aria-label', label('quickAddTitle', 'Ajouter un élément de mise en page'));

        zone.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            openQuickAdd(zone, Number(insertIndex));
        });

        zone.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                event.stopPropagation();
                openQuickAdd(zone, Number(insertIndex));
            }
        });

        zone.addEventListener('dragover', (event) => {
            if (!dragHasAnyType(event, ['application/x-pagesbuilder-widget', 'application/x-pagesbuilder-block', 'application/x-pagesbuilder-source'])) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            zone.classList.add('is-active');
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('is-active');
        });

        zone.addEventListener('drop', (event) => {
            event.preventDefault();
            event.stopPropagation();
            zone.classList.remove('is-active');

            const index = clampSectionInsertIndex(insertIndex);
            const widgetType = event.dataTransfer.getData('application/x-pagesbuilder-widget');
            const widgetSettings = getDraggedWidgetInitialSettings(event.dataTransfer);
            const draggedBlockId = event.dataTransfer.getData('application/x-pagesbuilder-block');
            const sourceIndex = event.dataTransfer.getData('application/x-pagesbuilder-source');

            if (sourceIndex !== '') {
                const sourceItem = getSourceItemByIndex(sourceIndex);
                if (sourceItem) {
                    insertSourceItemAsSectionAt(sourceItem, index);
                }
                return;
            }

            if (widgetType) {
                insertWidgetAsSectionAt(widgetType, index, widgetSettings);
                return;
            }

            if (draggedBlockId) {
                moveBlockAsSectionAt(draggedBlockId, index);
            }
        });

        return zone;
    }

    function ensureQuickAdd() {
        if (quickAddOverlay) return;

        quickAddOverlay = document.createElement('div');
        quickAddOverlay.className = 'pb-quickadd';
        quickAddOverlay.setAttribute('aria-hidden', 'true');
        quickAddOverlay.tabIndex = -1;

        quickAddOverlay.innerHTML = `
            <div class="pb-quickadd-panel" role="dialog" aria-modal="true" aria-label="${escapeAttr(label('quickAddTitle', 'Ajouter un élément de mise en page'))}">
                <div class="pb-quickadd-header">
                    <button type="button" class="pb-quickadd-nav pb-quickadd-back is-hidden" data-action="back" aria-label="${escapeAttr(label('quickAddBack', 'Retour'))}">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </button>
                    <div class="pb-quickadd-title" data-role="pb-quickadd-title">${escapeHtml(label('quickAddTitle', 'Ajouter un élément de mise en page'))}</div>
                    <button type="button" class="pb-quickadd-nav pb-quickadd-close" data-action="close" aria-label="${escapeAttr(label('quickAddClose', 'Fermer'))}">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="pb-quickadd-body">
                    <div class="pb-quickadd-view is-active" data-view="actions">
                        <div class="pb-quickadd-actions">
                            <button type="button" class="pb-quickadd-btn pb-quickadd-btn-primary" data-action="open-structure">
                                <i class="fas fa-layer-group" aria-hidden="true"></i>
                                <span>${escapeHtml(label('quickAddNewSection', 'Ajouter un nouveau conteneur'))}</span>
                            </button>
                            <button type="button" class="pb-quickadd-btn" data-action="open-templates">
                                <i class="fas fa-object-group" aria-hidden="true"></i>
                                <span>${escapeHtml(label('quickAddTemplatePageAction', 'Ajouter un template de page'))}</span>
                            </button>
                            <button type="button" class="pb-quickadd-btn" data-action="spacer">
                                <i class="fas fa-arrows-alt-v" aria-hidden="true"></i>
                                <span>${escapeHtml(label('widgetSpacer', 'Espacement'))}</span>
                            </button>
                            <button type="button" class="pb-quickadd-btn" data-action="divider">
                                <i class="fas fa-minus" aria-hidden="true"></i>
                                <span>${escapeHtml(label('widgetDivider', 'Séparateur'))}</span>
                            </button>
                        </div>
                    </div>
                    <div class="pb-quickadd-view" data-view="structure">
                        <div class="pb-quickadd-legend">${escapeHtml(label('quickAddChooseStructure', 'Choisir votre structure'))}</div>
                        <div class="pb-quickadd-presets">
                            ${buildQuickAddPresetsHtml()}
                        </div>
                    </div>
                    <div class="pb-quickadd-view" data-view="templates">
                        <div class="pb-quickadd-legend">${escapeHtml(label('quickAddChooseTemplate', 'Choisir votre template'))}</div>
                        <div class="pb-quickadd-presets">
                            ${buildQuickAddTemplatesHtml()}
                        </div>
                        <div class="pb-quickadd-more-wrap">
                            <button type="button" class="pb-quickadd-more-btn" data-action="open-template-gallery">
                                <i class="fas fa-grip-horizontal" aria-hidden="true"></i>
                                <span>${escapeHtml(label('quickAddTemplatesMore', 'Voir plus de templates'))}</span>
                            </button>
                        </div>
                    </div>
                    <div class="pb-quickadd-view" data-view="section-settings">
                        <div class="pb-quickadd-legend">${escapeHtml(label('quickAddSectionSetupLegend', 'Finalisez rapidement votre conteneur'))}</div>
                        <div class="pb-quickadd-section-settings" data-role="pb-quickadd-section-settings"></div>
                        <div class="pb-quickadd-section-actions">
                            <button type="button" class="btn btn-primary" data-action="insert-configured-section">
                                <i class="fas fa-check" aria-hidden="true"></i>
                                <span>${escapeHtml(label('quickAddSectionCreate', 'Créer le conteneur'))}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(quickAddOverlay);
        quickAddPanel = quickAddOverlay.querySelector('.pb-quickadd-panel');

        quickAddOverlay.addEventListener('click', (event) => {
            if (event.target === quickAddOverlay) {
                closeQuickAdd();
            }
        });

        quickAddOverlay.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeQuickAdd();
            }
        });

        if (quickAddPanel) {
            quickAddPanel.addEventListener('click', (event) => {
                const btn = event.target.closest('[data-action]');
                if (!btn) return;
                event.preventDefault();
                event.stopPropagation();
                const action = String(btn.dataset.action || '');

                if (action === 'close') {
                    closeQuickAdd();
                    return;
                }

                if (action === 'back') {
                    setQuickAddView(quickAddView === 'section-settings' ? 'structure' : 'actions');
                    focusQuickAddPrimaryAction();
                    return;
                }

                if (action === 'open-structure') {
                    setQuickAddView('structure');
                    focusQuickAddPrimaryAction();
                    return;
                }

                if (action === 'open-templates') {
                    setQuickAddView('templates');
                    focusQuickAddPrimaryAction();
                    return;
                }
                if (action === 'open-template-gallery') {
                    openTemplateGallery(clampSectionInsertIndex(quickAddInsertIndex));
                    return;
                }

                const index = clampSectionInsertIndex(quickAddInsertIndex);

                if (action === 'spacer') {
                    closeQuickAdd();
                    insertWidgetAsSectionAt('spacer', index);
                    return;
                }
                if (action === 'divider') {
                    closeQuickAdd();
                    insertWidgetAsSectionAt('divider', index);
                    return;
                }
                if (action === 'section-layout') {
                    const layoutId = String(btn.dataset.layout || 'cols-2');
                    quickAddSectionDraft = createQuickAddSectionDraft(
                        layoutId,
                        quickAddSectionDraft && quickAddSectionDraft.settings
                            ? quickAddSectionDraft.settings
                            : {}
                    );
                    renderQuickAddSectionSettings();
                    setQuickAddView('section-settings');
                    focusQuickAddPrimaryAction();
                    return;
                }
                if (action === 'section-template') {
                    const templateId = String(btn.dataset.template || '');
                    closeQuickAdd();
                    applyQuickAddTemplate(templateId, index);
                    return;
                }
                if (action === 'insert-configured-section') {
                    const draft = quickAddSectionDraft;
                    if (!draft) {
                        return;
                    }
                    closeQuickAdd();
                    insertEmptySectionAt(index, draft.columns, draft.template, draft.settings || {});
                    renderCanvas();
                }
            });
        }
    }

    function buildQuickAddPresetPreviewHtml(preset) {
        if (!preset || typeof preset !== 'object') {
            return '';
        }
        const bars = Array.from({ length: Number(preset.bars || 0) }, () => '<span></span>').join('');
        return `<span class="pb-quickadd-preset-grid ${escapeAttr(preset.gridClass || '')}" aria-hidden="true">${bars}</span>`;
    }

    function buildQuickAddPresetsHtml() {
        const presetMap = getSectionLayoutPresets();
        const presetOrder = [
            'cols-1',
            'cols-2',
            'cols-3',
            'cols-4',
            'cols-2-1-1',
            'cols-1-1-2',
            'cols-1-3-2-3',
            'cols-2-3-1-3',
            'cols-1-4-3-4',
            'cols-3-4-1-4',
        ];
        return presetOrder.map((presetId) => {
            const preset = presetMap[presetId];
            if (!preset) return '';
            return `
                <button type="button" class="pb-quickadd-preset" data-action="section-layout" data-layout="${escapeAttr(presetId)}">
                    ${buildQuickAddPresetPreviewHtml(preset)}
                    <span class="pb-quickadd-preset-label">${escapeHtml(preset.label)}</span>
                </button>
            `;
        }).join('');
    }

    function getQuickAddTemplatePriorityOrder() {
        return {
            primary: [
                'page-contact-complete',
                'page-coming-soon',
                'page-business-stats',
                'page-service-landing',
            ],
            fallback: [
                'page-faq-conversion',
                'page-product-showcase',
                'page-legal-trust',
                'page-newsletter-growth',
            ],
        };
    }

    function collectQuickAddTemplateWidgetTypes(template) {
        const sections = Array.isArray(template && template.sections) ? template.sections : [];
        const types = new Set();
        sections.forEach((section) => {
            const columns = Array.isArray(section && section.columns) ? section.columns : [];
            columns.forEach((column) => {
                const blockSpecs = Array.isArray(column) ? column : [];
                blockSpecs.forEach((blockSpec) => {
                    const type = String((blockSpec && blockSpec.type) || '').trim();
                    if (type !== '') {
                        types.add(type);
                    }
                });
            });
        });
        return Array.from(types);
    }

    function resolveQuickAddTemplateAvailability(template) {
        const requiredTypes = collectQuickAddTemplateWidgetTypes(template);
        const missingTypes = requiredTypes.filter((type) => {
            const def = getWidgetDef(type);
            return !def || isWidgetDefLocked(def);
        });
        return {
            enabled: missingTypes.length === 0,
            missingTypes: missingTypes,
        };
    }

    function resolveQuickAddTemplateEntries(templates, maxCount) {
        const desiredCountRaw = Number(maxCount);
        const desiredCount = Number.isFinite(desiredCountRaw)
            ? Math.max(1, Math.trunc(desiredCountRaw))
            : 4;
        const priority = getQuickAddTemplatePriorityOrder();
        const orderedTemplateIds = Array.from(new Set([...(priority.primary || []), ...(priority.fallback || [])]));

        const enabledEntries = [];
        const disabledEntries = [];

        orderedTemplateIds.forEach((templateId) => {
            const template = templates[String(templateId || '')];
            if (!template) return;

            const availability = resolveQuickAddTemplateAvailability(template);
            const entry = {
                id: String(templateId || ''),
                template,
                disabled: !availability.enabled,
                missingTypes: availability.missingTypes,
            };

            if (entry.disabled) {
                disabledEntries.push(entry);
            } else {
                enabledEntries.push(entry);
            }
        });

        const selectedEntries = enabledEntries.slice(0, desiredCount);
        if (selectedEntries.length < desiredCount) {
            const missingCount = desiredCount - selectedEntries.length;
            selectedEntries.push(...disabledEntries.slice(0, missingCount));
        }

        return selectedEntries;
    }

    function buildQuickAddTemplatesHtml() {
        const templates = getQuickAddTemplatePresets();
        const entries = resolveQuickAddTemplateEntries(templates, 4);
        return entries.map((entry) => {
            const templateId = String(entry && entry.id ? entry.id : '');
            const template = entry && entry.template ? entry.template : null;
            if (!template || templateId === '') return '';
            const preview = buildQuickAddTemplatePreviewHtml(template);
            const isDisabled = !!(entry && entry.disabled);
            const disabledClass = isDisabled ? ' is-disabled' : '';
            const disabledAttrs = isDisabled ? ' disabled aria-disabled="true"' : '';
            const unavailableHint = isDisabled
                ? `<span class="pb-quickadd-template-unavailable">${escapeHtml(label('quickAddTemplateUnavailable', 'Template indisponible (widgets requis manquants).'))}</span>`
                : '';
            return `
                <button type="button" class="pb-quickadd-preset pb-quickadd-template${disabledClass}" data-action="section-template" data-template="${escapeAttr(templateId)}"${disabledAttrs}>
                    ${preview}
                    <span class="pb-quickadd-preset-label">${escapeHtml(String(template.label || templateId))}</span>
                    <span class="pb-quickadd-preset-desc">${escapeHtml(String(template.description || ''))}</span>
                    ${unavailableHint}
                </button>
            `;
        }).join('');
    }

    function getAllQuickAddTemplateIds() {
        const priority = getQuickAddTemplatePriorityOrder();
        return Array.from(new Set([...(priority.primary || []), ...(priority.fallback || [])]));
    }

    function getTemplateWidgetLabel(type) {
        const safeType = String(type || '').trim();
        if (safeType === '') return '';
        const def = getWidgetDef(safeType);
        if (def && String(def.label || '').trim() !== '') {
            return String(def.label || '').trim();
        }
        return safeType.replace(/_/g, ' ');
    }

    function buildTemplateLayoutLines(template) {
        const sections = Array.isArray(template && template.sections) ? template.sections : [];
        if (!sections.length) {
            return [];
        }

        const layoutPresets = getSectionLayoutPresets();
        return sections.map((sectionSpec, sectionIndex) => {
            const layoutId = String((sectionSpec && sectionSpec.layout) || 'cols-1');
            const preset = layoutPresets[layoutId] || layoutPresets['cols-1'];
            const columns = Array.isArray(sectionSpec && sectionSpec.columns) ? sectionSpec.columns : [];
            const columnLines = Array.from({ length: preset.columns }, (_entry, columnIndex) => {
                const blockSpecs = Array.isArray(columns[columnIndex]) ? columns[columnIndex] : [];
                const blockNames = blockSpecs
                    .map((blockSpec) => getTemplateWidgetLabel(blockSpec && blockSpec.type))
                    .filter((name) => String(name || '').trim() !== '');
                const blocksLabel = blockNames.length ? blockNames.join(' + ') : '—';
                return `C${columnIndex + 1}: ${blocksLabel}`;
            }).join(' · ');
            return `S${sectionIndex + 1} (${preset.label}) · ${columnLines}`;
        });
    }

    function buildTemplateGalleryCardsHtml() {
        const templates = getQuickAddTemplatePresets();
        const templateIds = getAllQuickAddTemplateIds();
        return templateIds.map((templateId) => {
            const template = templates[String(templateId || '')];
            if (!template) return '';

            const availability = resolveQuickAddTemplateAvailability(template);
            const disabled = !availability.enabled;
            const disabledClass = disabled ? ' is-disabled' : '';
            const disabledAttrs = disabled ? ' disabled aria-disabled="true"' : '';
            const preview = buildQuickAddTemplatePreviewHtml(template);
            const layoutLines = buildTemplateLayoutLines(template);
            const layoutHtml = layoutLines.length
                ? `<ul class="pb-template-gallery-layout-list">${layoutLines.map((line) => `<li>${escapeHtml(line)}</li>`).join('')}</ul>`
                : `<div class="pb-template-gallery-layout-empty">—</div>`;
            const unavailableHint = disabled
                ? `<div class="pb-template-gallery-unavailable">${escapeHtml(label('quickAddTemplateUnavailable', 'Template indisponible (widgets requis manquants).'))}</div>`
                : '';

            return `
                <article class="card pb-template-gallery-card${disabledClass}" data-template="${escapeAttr(templateId)}">
                    <header class="card-header">${escapeHtml(String(template.label || templateId))}</header>
                    <div class="card-title">${escapeHtml(String(template.description || ''))}</div>
                    <div class="card-body">
                        <div class="pb-template-gallery-layout-heading">${escapeHtml(label('templateGalleryLayout', 'Disposition des blocs'))}</div>
                        ${preview ? `<div class="pb-template-gallery-preview">${preview}</div>` : ''}
                        ${layoutHtml}
                        ${unavailableHint}
                    </div>
                    <footer class="card-footer">
                        <button type="button" class="btn btn-secondary" data-action="template-gallery-preview" data-template="${escapeAttr(templateId)}"${disabledAttrs}>${escapeHtml(label('templateGalleryView', 'Voir'))}</button>
                        <button type="button" class="btn btn-primary" data-action="template-gallery-use" data-template="${escapeAttr(templateId)}"${disabledAttrs}>${escapeHtml(label('templateGalleryUse', 'Utiliser'))}</button>
                    </footer>
                </article>
            `;
        }).join('');
    }

    function ensureTemplateGallery() {
        if (templateGalleryOverlay) return;

        templateGalleryOverlay = document.createElement('div');
        templateGalleryOverlay.className = 'pb-template-gallery';
        templateGalleryOverlay.setAttribute('aria-hidden', 'true');

        templateGalleryOverlay.innerHTML = `
            <div class="pb-template-gallery-panel" role="dialog" aria-modal="true" aria-label="${escapeAttr(label('templateGalleryTitle', 'Templates de page'))}">
                <div class="pb-template-gallery-header">
                    <h3 class="pb-template-gallery-title">${escapeHtml(label('templateGalleryTitle', 'Templates de page'))}</h3>
                    <button type="button" class="pb-template-gallery-close" data-action="template-gallery-close" aria-label="${escapeAttr(label('close', 'Fermer'))}">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="pb-template-gallery-grid" data-role="template-gallery-grid"></div>
            </div>
        `;

        document.body.appendChild(templateGalleryOverlay);

        templateGalleryOverlay.addEventListener('click', (event) => {
            if (event.target === templateGalleryOverlay) {
                closeTemplateGallery();
                return;
            }

            const actionBtn = event.target.closest('[data-action]');
            if (!actionBtn) return;
            const action = String(actionBtn.dataset.action || '');
            const templateId = String(actionBtn.dataset.template || '');

            if (action === 'template-gallery-close') {
                closeTemplateGallery();
                return;
            }

            if (templateId === '') {
                return;
            }

            const template = getQuickAddTemplatePresets()[templateId];
            if (!template) return;
            const availability = resolveQuickAddTemplateAvailability(template);
            if (!availability.enabled) {
                return;
            }

            if (action === 'template-gallery-preview') {
                openTemplatePreview(templateId);
                return;
            }

            if (action === 'template-gallery-use') {
                closeTemplateGallery();
                applyQuickAddTemplate(templateId, templateGalleryInsertIndex);
            }
        });

        templateGalleryOverlay.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeTemplateGallery();
            }
        });
    }

    function openTemplateGallery(insertIndex) {
        ensureTemplateGallery();
        if (!templateGalleryOverlay) return;

        templateGalleryInsertIndex = clampSectionInsertIndex(insertIndex);
        const grid = templateGalleryOverlay.querySelector('[data-role="template-gallery-grid"]');
        if (grid) {
            grid.innerHTML = buildTemplateGalleryCardsHtml();
        }

        closeQuickAdd();
        templateGalleryOverlay.classList.add('is-open');
        templateGalleryOverlay.setAttribute('aria-hidden', 'false');

        const firstAction = templateGalleryOverlay.querySelector('[data-action="template-gallery-preview"]:not(:disabled)');
        if (firstAction) {
            firstAction.focus();
        }
    }

    function closeTemplateGallery() {
        if (!templateGalleryOverlay) return;
        templateGalleryOverlay.classList.remove('is-open');
        templateGalleryOverlay.setAttribute('aria-hidden', 'true');
    }

    function buildQuickAddTemplatePreviewHtml(template) {
        const sections = Array.isArray(template && template.sections) ? template.sections.slice(0, 5) : [];
        if (!sections.length) {
            return '';
        }

        const layoutPresets = getSectionLayoutPresets();
        const rows = sections.map((sectionSpec) => {
            const layoutId = String((sectionSpec && sectionSpec.layout) || 'cols-1');
            const preset = layoutPresets[layoutId] || layoutPresets['cols-1'];
            if (!preset) {
                return '';
            }

            const columnSpecs = Array.isArray(sectionSpec && sectionSpec.columns) ? sectionSpec.columns : [];
            const cells = Array.from({ length: preset.columns }, (_, index) => {
                const blockCountRaw = Array.isArray(columnSpecs[index]) ? columnSpecs[index].length : 0;
                const blockCount = Math.max(1, Math.min(4, blockCountRaw || 1));
                const lines = Array.from({ length: blockCount }, () => '<span></span>').join('');
                return `<span class="pb-quickadd-template-cell">${lines}</span>`;
            }).join('');

            return `
                <span class="pb-quickadd-template-row">
                    <span class="pb-quickadd-template-row-grid ${escapeAttr(preset.gridClass)}">${cells}</span>
                </span>
            `;
        }).filter((rowHtml) => rowHtml !== '');

        if (!rows.length) {
            return '';
        }

        return `<span class="pb-quickadd-template-preview" aria-hidden="true">${rows.join('')}</span>`;
    }

    function setQuickAddView(view) {
        quickAddView = ['structure', 'templates', 'section-settings'].includes(String(view || '')) ? String(view) : 'actions';
        if (!quickAddPanel) return;

        quickAddPanel.classList.toggle('is-section-settings', quickAddView === 'section-settings');
        quickAddPanel.querySelectorAll('.pb-quickadd-view').forEach((node) => {
            const nodeView = String(node.dataset.view || '');
            node.classList.toggle('is-active', nodeView === quickAddView);
        });

        const backBtn = quickAddPanel.querySelector('.pb-quickadd-back');
        if (backBtn) {
            backBtn.classList.toggle('is-hidden', quickAddView === 'actions');
        }

        const titleNode = quickAddPanel.querySelector('[data-role="pb-quickadd-title"]');
        if (titleNode) {
            titleNode.textContent = quickAddView === 'structure'
                ? label('quickAddChooseStructure', 'Choisir votre structure')
                : (quickAddView === 'section-settings'
                    ? label('quickAddSectionSetupTitle', 'Configurer votre conteneur')
                : (quickAddView === 'templates'
                    ? label('quickAddChooseTemplate', 'Choisir votre template')
                    : label('quickAddTitle', 'Ajouter un élément de mise en page')));
        }

        if (quickAddView === 'section-settings') {
            renderQuickAddSectionSettings();
        }

        window.requestAnimationFrame(() => {
            positionQuickAddPanel();
        });
    }

    function focusQuickAddPrimaryAction() {
        if (!quickAddPanel) return;
        let selector = '.pb-quickadd-view[data-view="actions"] [data-action="open-structure"]';
        if (quickAddView === 'structure') {
            selector = '.pb-quickadd-view[data-view="structure"] .pb-quickadd-preset';
        } else if (quickAddView === 'templates') {
            selector = '.pb-quickadd-view[data-view="templates"] .pb-quickadd-preset:not(:disabled)';
        } else if (quickAddView === 'section-settings') {
            selector = '.pb-quickadd-view[data-view="section-settings"] [data-action="insert-configured-section"]';
        }
        const firstBtn = quickAddPanel.querySelector(selector);
        if (firstBtn) {
            firstBtn.focus();
        } else if (quickAddOverlay) {
            quickAddOverlay.focus();
        }
    }

    function openQuickAdd(zone, insertIndex) {
        ensureQuickAdd();

        if (!quickAddOverlay || !quickAddPanel) {
            insertEmptySectionAt(Number(insertIndex), 2);
            renderCanvas();
            return;
        }

        // Toggle if clicking the same insert-zone twice.
        if (quickAddOverlay.classList.contains('is-open') && quickAddZone === zone) {
            closeQuickAdd();
            return;
        }

        closeQuickAdd();
        quickAddZone = zone;
        quickAddInsertIndex = Number(insertIndex);
        quickAddSectionDraft = null;
        zone.classList.add('is-open');
        setQuickAddView('actions');

        quickAddOverlay.classList.add('is-open');
        quickAddOverlay.setAttribute('aria-hidden', 'false');

        // Place the panel near the "+", staying inside the viewport.
        const rect = zone.getBoundingClientRect();
        quickAddPanel.style.left = '0px';
        quickAddPanel.style.top = '0px';
        quickAddPanel.style.visibility = 'hidden';

        window.requestAnimationFrame(() => {
            if (!quickAddOverlay || !quickAddPanel) return;
            positionQuickAddPanel(rect);
            quickAddPanel.style.visibility = 'visible';
            focusQuickAddPrimaryAction();
        });
    }

    function closeQuickAdd() {
        if (quickAddZone) {
            quickAddZone.classList.remove('is-open');
        }
        quickAddZone = null;
        quickAddInsertIndex = 0;
        quickAddSectionDraft = null;

        if (!quickAddOverlay) return;
        quickAddOverlay.classList.remove('is-open');
        quickAddOverlay.setAttribute('aria-hidden', 'true');
        if (quickAddPanel) {
            quickAddPanel.classList.remove('is-section-settings');
        }
    }

    function positionQuickAddPanel(zoneRect) {
        if (!quickAddOverlay || !quickAddPanel || !quickAddZone) {
            return;
        }

        const rect = zoneRect || quickAddZone.getBoundingClientRect();
        const panelRect = quickAddPanel.getBoundingClientRect();

        let top = rect.top - panelRect.height - 12;
        if (top < 10) {
            top = rect.bottom + 12;
        }

        let left = rect.left + rect.width / 2 - panelRect.width / 2;
        left = Math.max(10, Math.min(left, window.innerWidth - panelRect.width - 10));
        top = Math.max(10, Math.min(top, window.innerHeight - panelRect.height - 10));

        quickAddPanel.style.left = `${Math.round(left)}px`;
        quickAddPanel.style.top = `${Math.round(top)}px`;
    }

    function createQuickAddSectionDraft(layoutId, existingSettings) {
        const presetMap = getSectionLayoutPresets();
        const preset = presetMap[String(layoutId || '')] || presetMap['cols-2'];
        if (!preset) {
            return null;
        }

        return {
            layoutId: String(layoutId || 'cols-2'),
            label: String(preset.label || ''),
            gridClass: String(preset.gridClass || ''),
            bars: Number(preset.bars || 0),
            columns: sanitizeColumnCount(preset.columns),
            template: sanitizeSectionLayoutTemplate(String(preset.template || ''), sanitizeColumnCount(preset.columns)),
            settings: normalizeSectionSettings(existingSettings || {}),
        };
    }

    function updateQuickAddSectionDraftSettings(patch) {
        if (!quickAddSectionDraft) {
            return;
        }

        quickAddSectionDraft = Object.assign({}, quickAddSectionDraft, {
            settings: normalizeSectionSettings(Object.assign({}, quickAddSectionDraft.settings || {}, patch || {})),
        });
    }

    function renderQuickAddSectionSettings() {
        if (!quickAddPanel) {
            return;
        }

        const host = quickAddPanel.querySelector('[data-role="pb-quickadd-section-settings"]');
        if (!host) {
            return;
        }

        host.innerHTML = '';

        if (!quickAddSectionDraft) {
            const empty = document.createElement('div');
            empty.className = 'pb-inspector-empty';
            empty.textContent = label('builder_inspector_sheet_empty', 'Aucun réglage complémentaire pour cet élément.');
            host.appendChild(empty);
            return;
        }

        const settings = normalizeSectionSettings(quickAddSectionDraft.settings || {});

        const summary = document.createElement('div');
        summary.className = 'pb-quickadd-section-summary';
        summary.innerHTML = `
            <div class="pb-quickadd-section-summary-preview">${buildQuickAddPresetPreviewHtml(quickAddSectionDraft)}</div>
            <div class="pb-quickadd-section-summary-text">
                <span class="pb-quickadd-section-summary-kicker">${escapeHtml(label('quickAddSectionStructureLabel', 'Structure choisie'))}</span>
                <strong class="pb-quickadd-section-summary-title">${escapeHtml(String(quickAddSectionDraft.label || ''))}</strong>
            </div>
        `;
        host.appendChild(summary);

        const panel = document.createElement('div');
        panel.className = 'pb-section-settings-panel pb-quickadd-section-settings-panel';
        host.appendChild(panel);

        const backgroundGroup = createSectionInspectorGroup(panel, 'background', label('sectionBackground', 'Arrière-plan'));
        const backgroundRow = document.createElement('div');
        backgroundRow.className = 'pb-quickadd-section-settings-row';

        const backgroundColorField = createSectionField(label('sectionBackgroundColor', 'Couleur de fond'), 'backgroundColor');
        backgroundColorField.appendChild(createColorControl(settings.backgroundColor, (nextColor) => {
            updateQuickAddSectionDraftSettings({ backgroundColor: nextColor });
        }));
        backgroundRow.appendChild(backgroundColorField);

        const imageField = createSectionField(label('sectionBackgroundImage', 'Image de fond'), 'backgroundImage');
        const mediaLayout = document.createElement('div');
        mediaLayout.className = 'pb-section-background-path-layout';
        const mediaPathInput = document.createElement('input');
        mediaPathInput.type = 'text';
        mediaPathInput.className = 'form-input pb-section-background-path-input';
        mediaPathInput.placeholder = label('noMediaSelected', 'Aucun fichier sélectionné');
        mediaPathInput.value = String(settings.backgroundImage || '').trim();
        mediaPathInput.addEventListener('input', () => {
            const nextValue = String(mediaPathInput.value || '').trim();
            syncSectionMediaRemoveState(nextValue);
            updateQuickAddSectionDraftSettings({ backgroundImage: nextValue });
        });
        mediaLayout.appendChild(mediaPathInput);
        imageField.appendChild(mediaLayout);
        backgroundRow.appendChild(imageField);

        const imageActionsField = createSectionField('\u00A0', 'backgroundImageActions');
        imageActionsField.classList.add('pb-section-background-actions-field');
        let pickBtn = null;
        let removeBtn = null;
        const syncSectionMediaRemoveState = (rawValue) => {
            if (removeBtn) {
                removeBtn.disabled = String(rawValue || '').trim() === '';
            }
        };
        const pickImageAction = () => {
            openMediaPicker((file) => {
                const nextValue = String(file && (file.path || file.url) || '').trim();
                if (nextValue === '') {
                    return;
                }
                mediaPathInput.value = nextValue;
                syncSectionMediaRemoveState(nextValue);
                updateQuickAddSectionDraftSettings({ backgroundImage: nextValue });
            }, { mode: 'images', folder: 'images', accept: 'image/*' });
        };
        const clearImageAction = () => {
            mediaPathInput.value = '';
            syncSectionMediaRemoveState('');
            updateQuickAddSectionDraftSettings({ backgroundImage: '' });
        };
        let mediaActions = null;
        if (typeof sharedPrimitives.createBuilderActionsRow === 'function') {
            const actionsRow = sharedPrimitives.createBuilderActionsRow({
                rowClass: 'pb-section-background-path-actions',
                buttons: [
                    {
                        key: 'pick',
                        className: 'btn btn-secondary btn-sm',
                        label: label('chooseImage', 'Choose image'),
                        onClick: pickImageAction,
                    },
                    {
                        key: 'remove',
                        className: 'btn btn-ghost btn-sm',
                        html: `<i class="fas fa-trash"></i> ${escapeHtml(label('removeMedia', 'Remove media'))}`,
                        onClick: clearImageAction,
                    },
                ],
            });
            mediaActions = actionsRow.element;
            pickBtn = actionsRow.buttons.pick || null;
            removeBtn = actionsRow.buttons.remove || null;
        } else {
            mediaActions = document.createElement('div');
            mediaActions.className = 'pb-section-background-path-actions';
            pickBtn = document.createElement('button');
            pickBtn.type = 'button';
            pickBtn.className = 'btn btn-secondary btn-sm';
            pickBtn.textContent = label('chooseImage', 'Choose image');
            removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-ghost btn-sm';
            removeBtn.innerHTML = `<i class="fas fa-trash"></i> ${escapeHtml(label('removeMedia', 'Remove media'))}`;
            pickBtn.addEventListener('click', pickImageAction);
            removeBtn.addEventListener('click', clearImageAction);
            mediaActions.appendChild(pickBtn);
            mediaActions.appendChild(removeBtn);
        }
        syncSectionMediaRemoveState(settings.backgroundImage);
        imageActionsField.appendChild(mediaActions);
        backgroundRow.appendChild(imageActionsField);
        backgroundGroup.appendChild(backgroundRow);

        const containerGroup = createSectionInspectorGroup(panel, 'container', label('sectionContainer', 'Conteneur'));
        const containerModeField = createSectionField(label('sectionContainerMode', 'Mode du conteneur'), 'containerMode');
        containerModeField.classList.add('pb-quickadd-section-container-field');
        containerModeField.appendChild(createLayoutChoiceControl({
            key: 'sectionContainerMode',
            label: label('sectionContainerMode', 'Mode du conteneur'),
            options: ['container', 'fluid'],
            optionLabels: {
                container: label('sectionContainerModeContainer', 'Container'),
                fluid: label('sectionContainerModeFluid', 'Container fluid'),
            },
        }, settings.containerMode, (nextValue) => {
            updateQuickAddSectionDraftSettings({
                containerMode: nextValue,
                containerModeExplicit: true,
            });
        }));
        containerGroup.appendChild(containerModeField);
    }

    function confirmDeleteAction(message, onConfirm, options) {
        if (typeof onConfirm !== 'function') return;

        const finalMessage = String(message || label('removeBlockConfirm', 'Delete this block?'));
        const opts = options || {};
        const modal = window.FlatCMS && window.FlatCMS.modal && window.FlatCMS.modal.confirm;

        if (typeof modal === 'function') {
            modal(finalMessage, onConfirm, {
                confirmText: String(opts.confirmText || label('confirmDelete', 'Supprimer')),
                warning: String(opts.warning || ''),
                itemName: String(opts.itemName || ''),
            });
            return;
        }

        if (confirm(finalMessage)) {
            onConfirm();
        }
    }

    function queueInspectorFocus(selector, options) {
        const safeSelector = String(selector || '').trim();
        if (safeSelector === '') {
            cancelQueuedInspectorFocus();
            return;
        }

        const settings = options && typeof options === 'object' ? options : {};
        const requestId = Number(state.inspectorFocusRequestId || 0) + 1;
        state.inspectorFocusRequestId = requestId;
        state.pendingInspectorFocus = {
            selector: safeSelector,
            focus: settings.focus === true,
            scroll: settings.scroll !== false,
            select: settings.select === true,
            caretAtEnd: settings.caretAtEnd !== false,
            requestId,
        };
    }

    function cancelQueuedInspectorFocus() {
        state.pendingInspectorFocus = null;
        state.inspectorFocusRequestId = Number(state.inspectorFocusRequestId || 0) + 1;
    }

    function applyPendingInspectorFocus() {
        const pending = state.pendingInspectorFocus;
        if (!pending || !inspector) {
            return;
        }

        state.pendingInspectorFocus = null;
        const focusTarget = () => {
            if (Number(pending.requestId || 0) !== Number(state.inspectorFocusRequestId || 0)) {
                return;
            }
            const field = inspector.querySelector(pending.selector);
            if (
                !(field instanceof HTMLInputElement)
                && !(field instanceof HTMLTextAreaElement)
                && !(field instanceof HTMLSelectElement)
            ) {
                return;
            }

            if (pending.scroll !== false && typeof field.scrollIntoView === 'function') {
                field.scrollIntoView({
                    block: 'nearest',
                    inline: 'nearest',
                });
            }

            if (pending.focus !== true) {
                return;
            }

            if (typeof field.focus === 'function') {
                field.focus();
            }

            if (pending.select === true && typeof field.select === 'function') {
                field.select();
                return;
            }

            if (
                pending.caretAtEnd !== false
                && typeof field.value === 'string'
                && typeof field.setSelectionRange === 'function'
            ) {
                const caret = field.value.length;
                field.setSelectionRange(caret, caret);
            }
        };

        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(focusTarget);
            });
            return;
        }

        window.setTimeout(focusTarget, 0);
    }

    function isInspectorInteractiveElement(node) {
        if (!(node instanceof Element)) {
            return false;
        }

        if (node.matches('input, textarea, select, button, [contenteditable=""], [contenteditable="true"]')) {
            return true;
        }

        return !!node.closest('.tox, .pb-wysiwyg-inline');
    }

    function isInspectorFieldInteractionTarget(node) {
        if (!(node instanceof Element) || !inspector) {
            return false;
        }

        if (isInspectorInteractiveElement(node)) {
            return true;
        }

        const field = node.closest('.pb-field[data-field-key]');
        if (!(field instanceof Element) || !inspector.contains(field)) {
            return false;
        }

        return !!field.querySelector('input, textarea, select, button, [contenteditable=\"\"], [contenteditable=\"true\"], .tox, .pb-wysiwyg-inline');
    }

    function hasBlockingInspectorInteraction() {
        if (!inspector) {
            return false;
        }

        const active = document.activeElement;
        return active instanceof Element
            && inspector.contains(active)
            && isInspectorInteractiveElement(active);
    }

    function hasPendingStructuralInspectorFocus() {
        return !!(
            state.pendingInspectorFocus
            && typeof state.pendingInspectorFocus.selector === 'string'
            && state.pendingInspectorFocus.selector.trim() !== ''
        );
    }

    function flushPendingCommitInspectorRefresh() {
        if (!state.pendingCommitInspectorRefresh) {
            return;
        }
        if (state.inspectorPointerFieldInteraction) {
            return;
        }
        if (hasBlockingInspectorInteraction()) {
            return;
        }

        state.pendingCommitInspectorRefresh = false;
        renderInspector();
    }

    function schedulePendingCommitInspectorRefresh() {
        if (!state.pendingCommitInspectorRefresh) {
            return;
        }
        if (Number(state.inspectorCommitRefreshTimer || 0) > 0) {
            return;
        }

        state.inspectorCommitRefreshTimer = window.setTimeout(() => {
            state.inspectorCommitRefreshTimer = 0;
            flushPendingCommitInspectorRefresh();
        }, 0);
    }

    function requestInteractiveSafeInspectorRefresh(refreshInspector) {
        if (!refreshInspector) {
            return;
        }

        if (hasPendingStructuralInspectorFocus()) {
            state.pendingCommitInspectorRefresh = false;
            renderInspector();
            return;
        }

        if (state.inspectorPointerFieldInteraction) {
            state.pendingCommitInspectorRefresh = true;
            schedulePendingCommitInspectorRefresh();
            return;
        }

        if (hasBlockingInspectorInteraction()) {
            state.pendingCommitInspectorRefresh = true;
            schedulePendingCommitInspectorRefresh();
            return;
        }

        state.pendingCommitInspectorRefresh = false;
        renderInspector();
    }

    function requestFieldCommitInspectorRefresh(field, refreshInspector) {
        if (!refreshInspector) {
            return;
        }

        if (hasPendingStructuralInspectorFocus()) {
            state.pendingCommitInspectorRefresh = false;
            renderInspector();
            return;
        }

        if (state.inspectorPointerFieldInteraction) {
            state.pendingCommitInspectorRefresh = true;
            schedulePendingCommitInspectorRefresh();
            return;
        }

        const fieldType = String((field && field.type) || '').trim().toLowerCase();
        const requiresImmediateRefresh = fieldType === 'select'
            || fieldType === 'checkbox'
            || fieldType === 'color'
            || fieldType === 'text_style';

        if (requiresImmediateRefresh) {
            state.pendingCommitInspectorRefresh = false;
            renderInspector();
            return;
        }

        requestInteractiveSafeInspectorRefresh(true);
    }

    function isGlobalConfirmModalOpen() {
        const modal = document.getElementById('confirmModal');
        if (!modal) return false;
        if (modal.style.display && modal.style.display !== 'none') return true;
        return window.getComputedStyle(modal).display !== 'none';
    }

    function renderInspector() {
        if (Number(state.inspectorCommitRefreshTimer || 0) > 0) {
            window.clearTimeout(state.inspectorCommitRefreshTimer);
            state.inspectorCommitRefreshTimer = 0;
        }
        state.pendingCommitInspectorRefresh = false;
        inspector.innerHTML = '';
        inspector.removeAttribute('data-block-type');
        const selectedSection = getSelectedSection();
        if (selectedSection) {
            inspector.setAttribute('data-block-type', 'section');
            const inspectorContext = resolveInspectorContext();
            inspector.removeAttribute('data-sheet-tab');
            if (inspectorContext === 'sheet') {
                const activeSectionTab = normalizeSectionInspectorTab(state.sectionInspectorTab);
                if (state.sectionInspectorTab !== activeSectionTab) {
                    state.sectionInspectorTab = activeSectionTab;
                }
                inspector.setAttribute('data-sheet-tab', activeSectionTab);
                inspector.appendChild(buildSectionInspectorToolbar(inspectorContext));
                inspector.appendChild(buildSectionInspectorTabbar());
                inspector.appendChild(buildSectionInspector(selectedSection, activeSectionTab));
            } else {
                inspector.appendChild(buildSectionInspectorToolbar(inspectorContext));
                inspector.appendChild(buildSectionInspectorSidebarHint());
            }
            applyPendingInspectorFocus();
            return;
        }
        const block = getSelectedBlock();

        if (!block) {
            const empty = document.createElement('div');
            empty.className = 'pb-inspector-empty';
            empty.textContent = label('inspectorEmpty', 'Sélectionnez un bloc pour modifier ses réglages.');
            inspector.appendChild(empty);
            applyPendingInspectorFocus();
            return;
        }

        const def = getWidgetDef(block.type);
        if (!def) {
            applyPendingInspectorFocus();
            return;
        }

        inspector.setAttribute('data-block-type', String(block.type || '').trim().toLowerCase());

        const inspectorContext = resolveInspectorContext();
        const inspectorMode = normalizeInspectorMode(state.inspectorMode);
        if (inspectorContext !== 'sheet') {
            inspector.removeAttribute('data-sheet-tab');
        }
        inspector.appendChild(buildInspectorToolbar(def, inspectorMode, inspectorContext, block));

        if (inspectorContext !== 'sheet') {
            return;
        }

        if (inspectorMode === 'spacing') {
            inspector.appendChild(buildSpacingInspector(block));
            return;
        }

        const queryTerms = [];
        const groupContainers = new Map();
        const blockType = String((block.type || '')).trim().toLowerCase();
        const availableSheetGroups = inspectorContext === 'sheet'
            ? collectInspectorSheetGroups(block, def)
            : [];
        let activeSheetTab = inspectorContext === 'sheet'
            ? normalizeInspectorSheetTab(state.inspectorSheetTab, blockType)
            : 'all';
        if (inspectorContext === 'sheet' && activeSheetTab !== 'all' && !availableSheetGroups.includes(activeSheetTab)) {
            activeSheetTab = 'all';
        }
        if (inspectorContext === 'sheet') {
            if (state.inspectorSheetTab !== activeSheetTab) {
                state.inspectorSheetTab = activeSheetTab;
            }
            inspector.setAttribute('data-sheet-tab', activeSheetTab);
            inspector.appendChild(buildInspectorSheetTabbar(availableSheetGroups));
        }

        const orderedFields = getOrderedInspectorFields(def.fields, inspectorContext, activeSheetTab, block);
        let hasVisibleFields = false;
        let renderedStandaloneAdvancedTextStyleGroup = false;
        const settings = applyWidgetDefaults(block.type, block.settings || {});
        orderedFields.forEach((field) => {
            try {
            if (!isFieldVisibleForInspector(field, settings) && !shouldKeepConditionalFieldVisible(block, field, settings)) {
                return;
            }
            if (!isFieldMatchingInspectorQuery(field, queryTerms)) {
                return;
            }
            const isEssential = isEssentialInspectorField(block, field);
            const fieldKey = String((field && field.key) || '').trim().toLowerCase();
            const fieldGroupKey = resolveInspectorGroupKey(field);
            const useFeatureGridContentCards = blockType === 'feature_grid'
                && fieldGroupKey === 'content'
                && fieldKey === 'titles';
            const useCarouselContentCards = blockType === 'carousel'
                && fieldGroupKey === 'content'
                && fieldKey === 'titles';
            const useNwCarrouselContentCards = blockType === 'nw_carrousel'
                && fieldGroupKey === 'content'
                && fieldKey === 'titles';
            const useHeroContentEditor = blockType === 'hero'
                && fieldGroupKey === 'content'
                && fieldKey === 'subtitle';
            const useStatsSectionContentEditor = blockType === 'stats_section'
                && fieldGroupKey === 'content'
                && fieldKey === 'values';
            const useFaqAccordionContentEditor = blockType === 'faq_accordion'
                && fieldGroupKey === 'content'
                && fieldKey === 'questions';
            const useLogoCloudContentEditor = blockType === 'logo_cloud'
                && fieldGroupKey === 'content'
                && fieldKey === 'labels';
            const useTestimonialCardsContentEditor = blockType === 'testimonial_cards'
                && fieldGroupKey === 'content'
                && fieldKey === 'quotes';
            const useSnapCardsContentCards = blockType === 'snap_cards'
                && fieldGroupKey === 'content'
                && fieldKey === 'titles';
            const usePricingPlansContentEditor = blockType === 'pricing_plans'
                && fieldGroupKey === 'content'
                && fieldKey === 'plannames';
            const useLogoCloudMediaEditor = blockType === 'logo_cloud'
                && fieldGroupKey === 'media'
                && fieldKey === 'logos';
            const useTestimonialCardsMediaEditor = blockType === 'testimonial_cards'
                && fieldGroupKey === 'media'
                && fieldKey === 'avatars';
            const useFeatureGridMediaEditor = blockType === 'feature_grid'
                && fieldGroupKey === 'media'
                && fieldKey === 'icons';
            const usePricingPlansMediaEditor = blockType === 'pricing_plans'
                && fieldGroupKey === 'media'
                && fieldKey === 'planicons';
            const useNwCarrouselMediaEditor = blockType === 'nw_carrousel'
                && fieldGroupKey === 'media'
                && fieldKey === 'images';
            const useLogoCloudNavigationEditor = blockType === 'logo_cloud'
                && fieldGroupKey === 'navigation'
                && fieldKey === 'links';
            const useTestimonialCardsNavigationEditor = blockType === 'testimonial_cards'
                && fieldGroupKey === 'navigation'
                && fieldKey === 'links';
            const useButtonNavigationEditor = blockType === 'button'
                && fieldGroupKey === 'navigation'
                && fieldKey === 'showbutton'
                && activeSheetTab === 'navigation';
            const useFeatureGridNavigationEditor = blockType === 'feature_grid'
                && fieldGroupKey === 'navigation'
                && fieldKey === 'links';
            const useCarouselNavigationEditor = blockType === 'carousel'
                && fieldGroupKey === 'navigation'
                && fieldKey === 'links';
            const useNwCarrouselNavigationEditor = blockType === 'nw_carrousel'
                && fieldGroupKey === 'navigation'
                && fieldKey === 'links';
            const useSnapCardsNavigationEditor = blockType === 'snap_cards'
                && fieldGroupKey === 'navigation'
                && fieldKey === 'links';
            const usePricingPlansNavigationEditor = blockType === 'pricing_plans'
                && fieldGroupKey === 'navigation'
                && fieldKey === 'ctaenableds';
            const usePricingPlansLayoutEditor = blockType === 'pricing_plans'
                && fieldGroupKey === 'layout'
                && fieldKey === 'featuredplans';
            const skipSnapCardsTitleTextStyleField = blockType === 'snap_cards'
                && fieldGroupKey === 'advanced'
                && fieldKey === 'titletextstyle';
            const useSnapCardsAdvancedCards = blockType === 'snap_cards'
                && fieldGroupKey === 'advanced'
                && fieldKey === 'itemtitletextstyle';
            const skipFeatureGridTitleTextStyleField = blockType === 'feature_grid'
                && fieldGroupKey === 'advanced'
                && fieldKey === 'titletextstyle';
            const useFeatureGridAdvancedCards = blockType === 'feature_grid'
                && fieldGroupKey === 'advanced'
                && fieldKey === 'itemtitletextstyle';
            const useCarouselAdvancedCards = blockType === 'carousel'
                && fieldGroupKey === 'advanced'
                && fieldKey === 'itemtitletextstyle';
            const useNwCarrouselAdvancedCards = blockType === 'nw_carrousel'
                && fieldGroupKey === 'advanced'
                && fieldKey === 'itemtitletextstyle';
            const isStandaloneAdvancedTextStyleField = fieldGroupKey === 'advanced'
                && String((field && field.type) || '').trim().toLowerCase() === 'text_style'
                && !useSnapCardsAdvancedCards
                && !useFeatureGridAdvancedCards
                && !useCarouselAdvancedCards
                && !useNwCarrouselAdvancedCards;
            const skipFeatureGridTextsField = blockType === 'feature_grid'
                && fieldGroupKey === 'content'
                && fieldKey === 'texts';
            const skipCarouselTextsField = blockType === 'carousel'
                && fieldGroupKey === 'content'
                && fieldKey === 'texts';
            const skipNwCarrouselDescriptionsField = blockType === 'nw_carrousel'
                && fieldGroupKey === 'content'
                && fieldKey === 'descriptions';
            const skipSnapCardsTextsField = blockType === 'snap_cards'
                && fieldGroupKey === 'content'
                && fieldKey === 'texts';
            const skipStatsSectionLabelsField = blockType === 'stats_section'
                && fieldGroupKey === 'content'
                && fieldKey === 'labels';
            const skipStatsSectionNotesField = blockType === 'stats_section'
                && fieldGroupKey === 'content'
                && fieldKey === 'notes';
            const skipFaqAccordionAnswersField = blockType === 'faq_accordion'
                && fieldGroupKey === 'content'
                && fieldKey === 'answers';
            const skipTestimonialCardsNamesField = blockType === 'testimonial_cards'
                && fieldGroupKey === 'content'
                && fieldKey === 'names';
            const skipTestimonialCardsCompaniesField = blockType === 'testimonial_cards'
                && fieldGroupKey === 'content'
                && fieldKey === 'companies';
            const skipTestimonialCardsRolesField = blockType === 'testimonial_cards'
                && fieldGroupKey === 'content'
                && fieldKey === 'roles';
            const skipTestimonialCardsRatingsField = blockType === 'testimonial_cards'
                && fieldGroupKey === 'content'
                && fieldKey === 'ratings';
            const skipPricingPlansContentManagedField = blockType === 'pricing_plans'
                && fieldGroupKey === 'content'
                && (
                    fieldKey === 'planprices'
                    || fieldKey === 'planyearlyprices'
                    || fieldKey === 'plandescriptions'
                    || fieldKey === 'planfeatures'
                    || fieldKey === 'planbadges'
                );
            const skipLogoCloudNavigationManagedField = blockType === 'logo_cloud'
                && fieldGroupKey === 'navigation'
                && fieldKey === 'targets';
            const skipTestimonialCardsNavigationManagedField = blockType === 'testimonial_cards'
                && fieldGroupKey === 'navigation'
                && fieldKey === 'targets';
            const skipPricingPlansNavigationManagedField = blockType === 'pricing_plans'
                && fieldGroupKey === 'navigation'
                && (
                    fieldKey === 'ctalabels'
                    || fieldKey === 'ctalinks'
                    || fieldKey === 'ctatargets'
                    || fieldKey === 'ctavariants'
                    || fieldKey === 'ctaaligns'
                );
            const skipFeatureGridContentToggleField = blockType === 'feature_grid'
                && fieldGroupKey === 'content'
                && (fieldKey === 'showtitle' || fieldKey === 'showbody');
            const skipFeatureGridMediaToggleField = blockType === 'feature_grid'
                && fieldGroupKey === 'media'
                && fieldKey === 'showheader';
            const skipFeatureGridMediaManagedField = blockType === 'feature_grid'
                && fieldGroupKey === 'media'
                && (fieldKey === 'iconenableds' || fieldKey === 'iconaligns');
            const skipFeatureGridItemTextStyleField = blockType === 'feature_grid'
                && fieldGroupKey === 'advanced'
                && fieldKey === 'itemtexttextstyle';
            const skipCarouselItemTextStyleField = blockType === 'carousel'
                && fieldGroupKey === 'advanced'
                && fieldKey === 'itemtexttextstyle';
            const skipNwCarrouselItemTextStyleField = blockType === 'nw_carrousel'
                && fieldGroupKey === 'advanced'
                && fieldKey === 'itemtexttextstyle';
            const skipFeatureGridNavigationManagedField = blockType === 'feature_grid'
                && fieldGroupKey === 'navigation'
                && (fieldKey === 'showfooter'
                    || fieldKey === 'buttonlabel'
                    || fieldKey === 'buttonenableds'
                    || fieldKey === 'buttonlabels'
                    || fieldKey === 'buttontargets'
                    || fieldKey === 'buttonvariants'
                    || fieldKey === 'buttonaligns');
            const skipButtonNavigationManagedField = blockType === 'button'
                && fieldGroupKey === 'navigation'
                && (fieldKey === 'label'
                    || fieldKey === 'url'
                    || fieldKey === 'target'
                    || fieldKey === 'variant'
                    || fieldKey === 'align');
            const skipSnapCardsNavigationManagedField = blockType === 'snap_cards'
                && fieldGroupKey === 'navigation'
                && (fieldKey === 'ctaenableds'
                    || fieldKey === 'ctalabels'
                    || fieldKey === 'ctalabel'
                    || fieldKey === 'buttonaligns'
                    || fieldKey === 'target'
                    || fieldKey === 'targets');
            const skipCarouselNavigationManagedField = blockType === 'carousel'
                && fieldGroupKey === 'navigation'
                && (fieldKey === 'showindicators'
                    || fieldKey === 'showarrows'
                    || fieldKey === 'indicatorstyle'
                    || fieldKey === 'arrowstyle'
                    || fieldKey === 'autoplay'
                    || fieldKey === 'loop'
                    || fieldKey === 'buttonlabel'
                    || fieldKey === 'buttonenableds'
                    || fieldKey === 'buttonlabels'
                    || fieldKey === 'target');
            const skipNwCarrouselNavigationManagedField = blockType === 'nw_carrousel'
                && fieldGroupKey === 'navigation'
                && (fieldKey === 'showindicators'
                    || fieldKey === 'showarrows'
                    || fieldKey === 'indicatorstyle'
                    || fieldKey === 'arrowstyle'
                    || fieldKey === 'autoplay'
                    || fieldKey === 'loop'
                    || fieldKey === 'buttonenableds'
                    || fieldKey === 'buttonlabels'
                    || fieldKey === 'buttontargets'
                    || fieldKey === 'buttonaligns');
            if (skipFeatureGridTitleTextStyleField) {
                return;
            }
            if (skipSnapCardsTitleTextStyleField) {
                return;
            }
            if (skipFeatureGridTextsField) {
                return;
            }
            if (skipButtonNavigationManagedField) {
                return;
            }
            if (skipCarouselTextsField) {
                return;
            }
            if (skipNwCarrouselDescriptionsField) {
                return;
            }
            if (skipSnapCardsTextsField) {
                return;
            }
            if (skipStatsSectionLabelsField) {
                return;
            }
            if (skipStatsSectionNotesField) {
                return;
            }
            if (skipFaqAccordionAnswersField) {
                return;
            }
            if (skipTestimonialCardsNamesField) {
                return;
            }
            if (skipTestimonialCardsCompaniesField) {
                return;
            }
            if (skipTestimonialCardsRolesField) {
                return;
            }
            if (skipTestimonialCardsRatingsField) {
                return;
            }
            if (skipPricingPlansContentManagedField) {
                return;
            }
            if (skipLogoCloudNavigationManagedField) {
                return;
            }
            if (skipTestimonialCardsNavigationManagedField) {
                return;
            }
            if (skipPricingPlansNavigationManagedField) {
                return;
            }
            if (skipFeatureGridContentToggleField) {
                return;
            }
            if (skipFeatureGridMediaToggleField) {
                return;
            }
            if (skipFeatureGridMediaManagedField) {
                return;
            }
            if (skipFeatureGridItemTextStyleField) {
                return;
            }
            if (skipCarouselItemTextStyleField) {
                return;
            }
            if (skipNwCarrouselItemTextStyleField) {
                return;
            }
            if (skipFeatureGridNavigationManagedField) {
                return;
            }
            if (skipSnapCardsNavigationManagedField) {
                return;
            }
            if (skipCarouselNavigationManagedField) {
                return;
            }
            if (skipNwCarrouselNavigationManagedField) {
                return;
            }
            if (isStandaloneAdvancedTextStyleField && renderedStandaloneAdvancedTextStyleGroup) {
                return;
            }
            if (inspectorContext === 'sidebar' && !isEssential) {
                return;
            }
            if (inspectorContext === 'sheet' && isEssential) {
                return;
            }
            if (inspectorContext === 'sheet') {
                const groupKey = resolveInspectorSheetTabGroup(resolveInspectorGroupKey(field), blockType);
                if (activeSheetTab !== 'all' && groupKey !== activeSheetTab) {
                    return;
                }
            }
            if (isAdvancedInspectorField(field) && inspectorContext === 'sidebar') {
                return;
            }
            hasVisibleFields = true;
            const fieldDisabled = isConditionalFieldDisabled(block, field, settings);
            const primitives = window.FlatCMSUIPrimitives || {};
            const isCheckboxField = String((field && field.type) || '').trim().toLowerCase() === 'checkbox';
            const fieldDataKey = String((field && field.key) || '')
                .trim()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '');

            let wrap;
            let labelEl;
            let helpTarget = null;
            if (typeof primitives.createBuilderFieldShell === 'function') {
                const fieldShell = primitives.createBuilderFieldShell({
                    fieldKey: fieldDataKey,
                    label: field.label,
                    wrapperClass: 'fc-builder-field',
                    disabled: fieldDisabled,
                    switchField: isCheckboxField,
                    hideLabel: isCheckboxField,
                });
                wrap = fieldShell.element;
                labelEl = fieldShell.label;
            } else {
                wrap = document.createElement('div');
                wrap.className = 'pb-field fc-builder-field';
                if (fieldDataKey !== '') {
                    wrap.dataset.fieldKey = fieldDataKey;
                }
                if (fieldDisabled) {
                    wrap.classList.add('is-disabled');
                }
                labelEl = document.createElement('label');
                labelEl.textContent = field.label;
                if (isCheckboxField) {
                    wrap.classList.add('pb-field-switch');
                }
                if (!isCheckboxField) {
                    wrap.appendChild(labelEl);
                }
            }
            helpTarget = wrap;

            let input = null;
            let customFieldHandled = false;
            let customFieldController = null;
            const value = settings[field.key] !== undefined ? settings[field.key] : '';
            const isRepeaterField = !!(field.repeater && field.repeater.enabled);
            const useLinksQuickAdd = field.type === 'textarea' && isLinksItemsField(block, field);
            const applyValue = (nextValue, refreshInspector) => {
                const normalizedValue = normalizeFieldValue(field, nextValue);
                updateSetting(block.id, field.key, normalizedValue);
                if (blockType === 'video_player') {
                    if (fieldKey === 'ambientmode') {
                        if (normalizeTextStyleToggle(normalizedValue, false)) {
                            updateSetting(block.id, 'autoplay', 'on');
                            updateSetting(block.id, 'loop', 'on');
                            updateSetting(block.id, 'muted', 'on');
                        }
                    } else if (
                        (fieldKey === 'autoplay' || fieldKey === 'loop' || fieldKey === 'muted')
                        && normalizeTextStyleToggle(block.settings.ambientMode, false)
                        && normalizeToggleSettingValue(normalizedValue, 'off') !== 'on'
                    ) {
                        updateSetting(block.id, 'ambientMode', 'off');
                    }
                }
                if (inspectorContext === 'sheet' && fieldKey === 'usecustomdesign') {
                    state.inspectorSheetTab = normalizeTextStyleToggle(normalizedValue, false) ? 'design' : 'all';
                }
                requestFieldCommitInspectorRefresh(field, refreshInspector);
            };
            const isAlignField = isAlignSelectField(field);
            const useLayoutUx = inspectorContext === 'sheet'
                && fieldGroupKey === 'layout'
                && (activeSheetTab === 'layout' || activeSheetTab === 'all');
            const useDesignUx = inspectorContext === 'sheet'
                && fieldGroupKey === 'design'
                && (activeSheetTab === 'design' || activeSheetTab === 'all');
            const useSectionRangeUx = (useLayoutUx || useDesignUx)
                && (field.type === 'number' || field.type === 'range')
                && hasFiniteNumberRange(field);

            if (useLinksQuickAdd && labelEl) {
                labelEl.remove();
            }

            if (isInspectorFieldWide(field, isRepeaterField, useLinksQuickAdd)) {
                wrap.classList.add('is-wide');
            }
            const isFeatureGridRepeater = blockType === 'feature_grid' && (fieldKey === 'titles' || fieldKey === 'texts');
            if (isFeatureGridRepeater) {
                wrap.classList.add('pb-feature-grid-repeater-block');
                wrap.classList.add(fieldKey === 'titles'
                    ? 'pb-feature-grid-repeater-block--titles'
                    : 'pb-feature-grid-repeater-block--texts');
            }

            if (useCarouselContentCards) {
                wrap.classList.add('is-wide', 'pb-feature-grid-content-editor', 'pb-carousel-content-editor', 'pb-pricing-plans-content-editor');
                labelEl.remove();
                const textsField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'texts')
                    : null;
                const titleRepeater = field.repeater || {};
                const textRepeater = textsField && textsField.repeater ? textsField.repeater : {};
                const delimiter = '\n';
                const minItems = Math.max(
                    0,
                    Number(titleRepeater.min || 0),
                    Number(textRepeater.min || 0)
                );
                const maxItems = Math.max(
                    0,
                    Number(titleRepeater.max || 0),
                    Number(textRepeater.max || 0)
                );

                let titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                let textItems = parseRepeaterValues(block.settings.texts || '', delimiter);
                let imageItems = parseRepeaterValues(block.settings.images || '', delimiter);
                let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                let buttonEnabledItems = parseRepeaterValues(block.settings.buttonEnableds || '', delimiter);
                let buttonLabelItems = parseRepeaterValues(block.settings.buttonLabels || '', delimiter);
                let buttonTargetItems = parseRepeaterValues(block.settings.buttonTargets || '', delimiter);
                let buttonAlignItems = parseRepeaterValues(block.settings.buttonAligns || '', delimiter);

                const normalizeCarouselContentItems = (preserveLength) => {
                    const nextSettings = {
                        titles: serializeRepeaterValues(titleItems, delimiter),
                        texts: serializeRepeaterValues(textItems, delimiter),
                        images: serializeRepeaterValues(imageItems, delimiter),
                        links: serializeRepeaterValues(linkItems, delimiter),
                        buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                        buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                        buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                        buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                        buttonLabel: String(block.settings.buttonLabel || ''),
                        target: String(block.settings.target || '_self'),
                    };
                    normalizeWidgetLinkedRepeaters('carousel', nextSettings, {
                        compact: true,
                        minLength: Math.max(0, Number(preserveLength || 0)),
                    });
                    titleItems = parseRepeaterValues(nextSettings.titles || '', delimiter);
                    textItems = parseRepeaterValues(nextSettings.texts || '', delimiter);
                    imageItems = parseRepeaterValues(nextSettings.images || '', delimiter);
                    linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                    buttonEnabledItems = parseRepeaterValues(nextSettings.buttonEnableds || '', delimiter);
                    buttonLabelItems = parseRepeaterValues(nextSettings.buttonLabels || '', delimiter);
                    buttonTargetItems = parseRepeaterValues(nextSettings.buttonTargets || '', delimiter);
                    buttonAlignItems = parseRepeaterValues(nextSettings.buttonAligns || '', delimiter);

                    const targetLength = Math.max(
                        minItems,
                        titleItems.length,
                        textItems.length,
                        imageItems.length,
                        linkItems.length,
                        buttonEnabledItems.length,
                        buttonLabelItems.length,
                        buttonTargetItems.length,
                        buttonAlignItems.length
                    );

                    while (titleItems.length < targetLength) titleItems.push('');
                    while (textItems.length < targetLength) textItems.push('');
                    while (imageItems.length < targetLength) imageItems.push('');
                    while (linkItems.length < targetLength) linkItems.push('');
                    while (buttonEnabledItems.length < targetLength) buttonEnabledItems.push('on');
                    while (buttonLabelItems.length < targetLength) buttonLabelItems.push('');
                    while (buttonTargetItems.length < targetLength) buttonTargetItems.push('_self');
                    while (buttonAlignItems.length < targetLength) buttonAlignItems.push('left');

                    if (maxItems > 0) {
                        titleItems = titleItems.slice(0, maxItems);
                        textItems = textItems.slice(0, maxItems);
                        imageItems = imageItems.slice(0, maxItems);
                        linkItems = linkItems.slice(0, maxItems);
                        buttonEnabledItems = buttonEnabledItems.slice(0, maxItems);
                        buttonLabelItems = buttonLabelItems.slice(0, maxItems);
                        buttonTargetItems = buttonTargetItems.slice(0, maxItems);
                        buttonAlignItems = buttonAlignItems.slice(0, maxItems);
                    }
                };

                const carouselStylePrefixes = ['itemTitleStyle', 'itemTextStyle'];
                const carouselStyleSuffixes = ['Align', 'Font', 'Size', 'Bold', 'Italic', 'Underline', 'Color', 'List', 'Icon', 'IconPosition'];
                const buildCarouselStyleRemovalPatch = (removedIndex) => {
                    if (removedIndex < 0) {
                        return {};
                    }

                    const sourceSettings = block.settings && typeof block.settings === 'object'
                        ? block.settings
                        : {};
                    const patch = {};
                    const nextLength = titleItems.length;

                    for (let position = removedIndex + 1; position <= nextLength; position += 1) {
                        const sourcePosition = position + 1;
                        carouselStylePrefixes.forEach((stylePrefix) => {
                            carouselStyleSuffixes.forEach((suffix) => {
                                const sourceKey = `${stylePrefix}${sourcePosition}${suffix}`;
                                const targetKey = `${stylePrefix}${position}${suffix}`;
                                patch[targetKey] = Object.prototype.hasOwnProperty.call(sourceSettings, sourceKey)
                                    ? sourceSettings[sourceKey]
                                    : '';
                            });
                        });
                    }

                    for (let position = nextLength + 1; position <= 12; position += 1) {
                        carouselStylePrefixes.forEach((stylePrefix) => {
                            carouselStyleSuffixes.forEach((suffix) => {
                                patch[`${stylePrefix}${position}${suffix}`] = '';
                            });
                        });
                    }

                    return patch;
                };

                const syncCarouselContentItems = (refreshInspector, extraPatch) => {
                    normalizeCarouselContentItems(titleItems.length);
                    updateSettings(block.id, Object.assign({
                        titles: serializeRepeaterValues(titleItems, delimiter),
                        texts: serializeRepeaterValues(textItems, delimiter),
                        images: serializeRepeaterValues(imageItems, delimiter),
                        links: serializeRepeaterValues(linkItems, delimiter),
                        buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                        buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                        buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                        buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                    }, extraPatch && typeof extraPatch === 'object' ? extraPatch : {}));
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };

                normalizeCarouselContentItems(titleItems.length);

                const scaffold = createRepeaterCardScaffold({
                    bodyClass: 'fc-builder-card-editor-body pb-feature-grid-content-body',
                    listClass: 'fc-builder-card-editor-list pb-feature-grid-content-list',
                });
                const body = scaffold.body;
                const list = scaffold.list;

                const titleItemLabel = String(titleRepeater.itemLabel || field.label || label('fieldLabel', 'Titre')).trim();
                const textItemLabel = String((textsField && textsField.repeater && textsField.repeater.itemLabel) || (textsField && textsField.label) || label('fieldDescription', 'Description')).trim();
                const removeLabelText = label('confirmDelete', 'Supprimer');

                const renderCarouselContentCards = () => {
                    list.innerHTML = '';
                    titleItems.forEach((titleValue, itemIndex) => {
                        const cardParts = createRepeaterCard({
                            cardClass: 'fc-builder-card pb-feature-grid-content-card pb-carousel-content-card',
                            gridClass: 'fc-builder-card-grid pb-carousel-content-grid',
                            removeButtonClass: 'pb-carousel-content-remove',
                            attachHead: false,
                        });
                        const card = cardParts.card;
                        const removeBtn = cardParts.removeButton;
                        removeBtn.title = `${removeLabelText} ${titleItemLabel} ${itemIndex + 1}`;
                        removeBtn.setAttribute('aria-label', `${removeLabelText} ${titleItemLabel} ${itemIndex + 1}`);
                        removeBtn.disabled = titleItems.length <= minItems;
                        removeBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            if (titleItems.length <= minItems) {
                                return;
                            }
                            confirmDeleteAction(
                                label('removeBlockConfirm', 'Supprimer ce bloc ?'),
                                () => {
                                    titleItems.splice(itemIndex, 1);
                                    textItems.splice(itemIndex, 1);
                                    imageItems.splice(itemIndex, 1);
                                    linkItems.splice(itemIndex, 1);
                                    buttonEnabledItems.splice(itemIndex, 1);
                                    buttonLabelItems.splice(itemIndex, 1);
                                    buttonTargetItems.splice(itemIndex, 1);
                                    buttonAlignItems.splice(itemIndex, 1);
                                    syncCarouselContentItems(true, buildCarouselStyleRemovalPatch(itemIndex));
                                },
                                {
                                    confirmText: label('confirmDelete', 'Supprimer'),
                                    itemName: `${titleItemLabel} ${itemIndex + 1}`,
                                }
                            );
                        });
                        const grid = cardParts.grid;

                        const titleFieldWrap = document.createElement('div');
                        titleFieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field';
                        const titlePlaceholder = `${titleItemLabel} ${itemIndex + 1}`;
                        const titleInput = document.createElement('input');
                        titleInput.className = 'form-input pb-carousel-content-input';
                        titleInput.type = 'text';
                        titleInput.value = String(titleValue || '');
                        titleInput.placeholder = titlePlaceholder;
                        titleInput.title = titlePlaceholder;
                        titleInput.setAttribute('aria-label', titlePlaceholder);
                        titleInput.addEventListener('input', () => {
                            titleItems[itemIndex] = String(titleInput.value || '');
                            syncCarouselContentItems(false);
                        });
                        titleInput.addEventListener('change', () => {
                            titleItems[itemIndex] = String(titleInput.value || '');
                            syncCarouselContentItems(false);
                        });
                        titleFieldWrap.appendChild(titleInput);
                        grid.appendChild(titleFieldWrap);

                        const textFieldWrap = document.createElement('div');
                        textFieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field';
                        const textPlaceholder = label('fieldShortDescription', 'Description courte');
                        const textInput = document.createElement('input');
                        textInput.className = 'form-input pb-snap-cards-content-input pb-carousel-content-input';
                        textInput.type = 'text';
                        textInput.value = String(textItems[itemIndex] || '');
                        textInput.placeholder = textPlaceholder;
                        textInput.title = textPlaceholder;
                        textInput.setAttribute('aria-label', textPlaceholder);
                        textInput.addEventListener('input', () => {
                            textItems[itemIndex] = String(textInput.value || '');
                            syncCarouselContentItems(false);
                        });
                        textInput.addEventListener('change', () => {
                            textItems[itemIndex] = String(textInput.value || '');
                            syncCarouselContentItems(false);
                        });
                        textFieldWrap.appendChild(textInput);
                        grid.appendChild(textFieldWrap);

                        const textAlignField = {
                            key: 'itemTextAlign',
                            label: label('fieldAlign', 'Alignement'),
                            type: 'select',
                            options: ['left', 'center', 'right'],
                        };
                        const textAlignControl = createAlignIconControl(
                            textAlignField,
                            resolveTextStyleState(block.settings || {}, `itemTextStyle${itemIndex + 1}`, 'left').align,
                            (nextValue) => {
                                updateSetting(
                                    block.id,
                                    textStyleSettingKey(`itemTextStyle${itemIndex + 1}`, TEXT_STYLE_SUFFIX.align),
                                    normalizeAlign(nextValue)
                                );
                            }
                        );
                        textAlignControl.classList.add('pb-carousel-content-align');
                        const actionsWrap = createRepeaterCardActionsRow({
                            rowClass: 'pb-carousel-content-actions-wrap',
                            controls: [textAlignControl, removeBtn],
                        });
                        grid.appendChild(actionsWrap);

                        list.appendChild(card);
                    });
                };

                const addBtn = createRepeaterAddButton(`<i class="fas fa-plus"></i> ${escapeHtml(label('add', 'Ajouter'))}`);
                addBtn.disabled = maxItems > 0 && titleItems.length >= maxItems;
                addBtn.addEventListener('click', () => {
                    if (maxItems > 0 && titleItems.length >= maxItems) {
                        return;
                    }
                    queueInspectorFocus('.pb-carousel-content-card:last-child .pb-carousel-content-input');
                    titleItems.push('');
                    textItems.push('');
                    imageItems.push('');
                    linkItems.push('');
                    buttonEnabledItems.push('on');
                    buttonLabelItems.push('');
                    buttonTargetItems.push('_self');
                    buttonAlignItems.push('left');
                    syncCarouselContentItems(true);
                });

                renderCarouselContentCards();
                body.appendChild(addBtn);
                wrap.appendChild(body);
            } else if (useNwCarrouselContentCards) {
                wrap.classList.add('is-wide', 'pb-feature-grid-content-editor', 'pb-carousel-content-editor', 'pb-nw-carrousel-content-editor');
                labelEl.remove();
                const descriptionsField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'descriptions')
                    : null;
                const delimiter = '\n---\n';
                const titleRepeater = field.repeater || {};
                const descriptionRepeater = descriptionsField && descriptionsField.repeater ? descriptionsField.repeater : {};
                const minItems = Math.max(
                    1,
                    Number(titleRepeater.min || 0),
                    Number(descriptionRepeater.min || 0)
                );
                const maxItems = Math.max(
                    0,
                    Number(titleRepeater.max || 0),
                    Number(descriptionRepeater.max || 0)
                );

                let titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                let descriptionItems = parseRepeaterValues(block.settings.descriptions || '', delimiter);
                let imageItems = parseRepeaterValues(block.settings.images || '', delimiter);
                let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                let buttonEnabledItems = parseRepeaterValues(block.settings.buttonEnableds || '', delimiter);
                let buttonLabelItems = parseRepeaterValues(block.settings.buttonLabels || '', delimiter);
                let buttonTargetItems = parseRepeaterValues(block.settings.buttonTargets || '', delimiter);
                let buttonAlignItems = parseRepeaterValues(block.settings.buttonAligns || '', delimiter);

                const normalizeNwCarrouselContentItems = (preserveLength) => {
                    const nextSettings = {
                        titles: serializeRepeaterValues(titleItems, delimiter),
                        descriptions: serializeRepeaterValues(descriptionItems, delimiter),
                        images: serializeRepeaterValues(imageItems, delimiter),
                        links: serializeRepeaterValues(linkItems, delimiter),
                        buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                        buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                        buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                        buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                    };
                    normalizeWidgetLinkedRepeaters('nw_carrousel', nextSettings, {
                        compact: true,
                        minLength: Math.max(1, Number(preserveLength || 0)),
                    });
                    titleItems = parseRepeaterValues(nextSettings.titles || '', delimiter);
                    descriptionItems = parseRepeaterValues(nextSettings.descriptions || '', delimiter);
                    imageItems = parseRepeaterValues(nextSettings.images || '', delimiter);
                    linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                    buttonEnabledItems = parseRepeaterValues(nextSettings.buttonEnableds || '', delimiter);
                    buttonLabelItems = parseRepeaterValues(nextSettings.buttonLabels || '', delimiter);
                    buttonTargetItems = parseRepeaterValues(nextSettings.buttonTargets || '', delimiter);
                    buttonAlignItems = parseRepeaterValues(nextSettings.buttonAligns || '', delimiter);

                    const targetLength = Math.max(
                        minItems,
                        titleItems.length,
                        descriptionItems.length,
                        imageItems.length,
                        linkItems.length,
                        buttonEnabledItems.length,
                        buttonLabelItems.length,
                        buttonTargetItems.length,
                        buttonAlignItems.length
                    );

                    while (titleItems.length < targetLength) titleItems.push('');
                    while (descriptionItems.length < targetLength) descriptionItems.push('');
                    while (imageItems.length < targetLength) imageItems.push('');
                    while (linkItems.length < targetLength) linkItems.push('');
                    while (buttonEnabledItems.length < targetLength) buttonEnabledItems.push('on');
                    while (buttonLabelItems.length < targetLength) buttonLabelItems.push('');
                    while (buttonTargetItems.length < targetLength) buttonTargetItems.push('_self');
                    while (buttonAlignItems.length < targetLength) buttonAlignItems.push('left');

                    if (maxItems > 0) {
                        titleItems = titleItems.slice(0, maxItems);
                        descriptionItems = descriptionItems.slice(0, maxItems);
                        imageItems = imageItems.slice(0, maxItems);
                        linkItems = linkItems.slice(0, maxItems);
                        buttonEnabledItems = buttonEnabledItems.slice(0, maxItems);
                        buttonLabelItems = buttonLabelItems.slice(0, maxItems);
                        buttonTargetItems = buttonTargetItems.slice(0, maxItems);
                        buttonAlignItems = buttonAlignItems.slice(0, maxItems);
                    }
                };

                const nwCarrouselStylePrefixes = ['itemTitleStyle', 'itemTextStyle'];
                const nwCarrouselStyleSuffixes = ['Align', 'Font', 'Size', 'Bold', 'Italic', 'Underline', 'Color', 'List', 'Icon', 'IconPosition'];
                const buildNwCarrouselStyleRemovalPatch = (removedIndex) => {
                    if (removedIndex < 0) {
                        return {};
                    }

                    const sourceSettings = block.settings && typeof block.settings === 'object'
                        ? block.settings
                        : {};
                    const patch = {};
                    const nextLength = titleItems.length;

                    for (let position = removedIndex + 1; position <= nextLength; position += 1) {
                        const sourcePosition = position + 1;
                        nwCarrouselStylePrefixes.forEach((stylePrefix) => {
                            nwCarrouselStyleSuffixes.forEach((suffix) => {
                                const sourceKey = `${stylePrefix}${sourcePosition}${suffix}`;
                                const targetKey = `${stylePrefix}${position}${suffix}`;
                                patch[targetKey] = Object.prototype.hasOwnProperty.call(sourceSettings, sourceKey)
                                    ? sourceSettings[sourceKey]
                                    : '';
                            });
                        });
                    }

                    for (let position = nextLength + 1; position <= 12; position += 1) {
                        nwCarrouselStylePrefixes.forEach((stylePrefix) => {
                            nwCarrouselStyleSuffixes.forEach((suffix) => {
                                patch[`${stylePrefix}${position}${suffix}`] = '';
                            });
                        });
                    }

                    return patch;
                };

                const syncNwCarrouselContentItems = (refreshInspector, extraPatch) => {
                    normalizeNwCarrouselContentItems(titleItems.length);
                    updateSettings(block.id, {
                        titles: serializeRepeaterValues(titleItems, delimiter),
                        descriptions: serializeRepeaterValues(descriptionItems, delimiter),
                        images: serializeRepeaterValues(imageItems, delimiter),
                        links: serializeRepeaterValues(linkItems, delimiter),
                        buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                        buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                        buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                        buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                        ...(extraPatch || {}),
                    });
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };

                normalizeNwCarrouselContentItems(titleItems.length);

                const scaffold = createRepeaterCardScaffold({
                    bodyClass: 'fc-builder-card-editor-body pb-feature-grid-content-body',
                    listClass: 'fc-builder-card-editor-list pb-feature-grid-content-list',
                });
                const body = scaffold.body;
                const list = scaffold.list;

                const titleItemLabel = String(titleRepeater.itemLabel || field.label || label('fieldLabel', 'Titre')).trim();
                const removeLabelText = label('confirmDelete', 'Supprimer');
                const descriptionPlaceholder = label('fieldShortDescription', 'Description courte');

                const renderNwCarrouselContentCards = () => {
                    list.innerHTML = '';
                    titleItems.forEach((titleValue, itemIndex) => {
                        const cardParts = createRepeaterCard({
                            cardClass: 'fc-builder-card pb-feature-grid-content-card pb-carousel-content-card pb-nw-carrousel-content-card',
                            gridClass: 'fc-builder-card-grid pb-carousel-content-grid pb-nw-carrousel-content-grid',
                            removeButtonClass: 'pb-carousel-content-remove',
                            attachHead: false,
                        });
                        const removeBtn = cardParts.removeButton;
                        removeBtn.title = `${removeLabelText} ${titleItemLabel} ${itemIndex + 1}`;
                        removeBtn.setAttribute('aria-label', `${removeLabelText} ${titleItemLabel} ${itemIndex + 1}`);
                        removeBtn.disabled = titleItems.length <= minItems;
                        removeBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            if (titleItems.length <= minItems) {
                                return;
                            }
                            confirmDeleteAction(
                                label('removeBlockConfirm', 'Supprimer ce bloc ?'),
                                () => {
                                    titleItems.splice(itemIndex, 1);
                                    descriptionItems.splice(itemIndex, 1);
                                    imageItems.splice(itemIndex, 1);
                                    linkItems.splice(itemIndex, 1);
                                    buttonEnabledItems.splice(itemIndex, 1);
                                    buttonLabelItems.splice(itemIndex, 1);
                                    buttonTargetItems.splice(itemIndex, 1);
                                    buttonAlignItems.splice(itemIndex, 1);
                                    syncNwCarrouselContentItems(true, buildNwCarrouselStyleRemovalPatch(itemIndex));
                                },
                                {
                                    confirmText: label('confirmDelete', 'Supprimer'),
                                    itemName: `${titleItemLabel} ${itemIndex + 1}`,
                                }
                            );
                        });
                        const grid = cardParts.grid;

                        const titleFieldWrap = document.createElement('div');
                        titleFieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field';
                        const titleInput = document.createElement('input');
                        titleInput.className = 'form-input pb-carousel-content-input';
                        titleInput.type = 'text';
                        titleInput.value = String(titleValue || '');
                        titleInput.placeholder = `${titleItemLabel} ${itemIndex + 1}`;
                        titleInput.title = `${titleItemLabel} ${itemIndex + 1}`;
                        titleInput.setAttribute('aria-label', `${titleItemLabel} ${itemIndex + 1}`);
                        titleInput.addEventListener('input', () => {
                            titleItems[itemIndex] = String(titleInput.value || '');
                            syncNwCarrouselContentItems(false);
                        });
                        titleInput.addEventListener('change', () => {
                            titleItems[itemIndex] = String(titleInput.value || '');
                            syncNwCarrouselContentItems(true);
                        });
                        titleFieldWrap.appendChild(titleInput);
                        grid.appendChild(titleFieldWrap);

                        const descriptionFieldWrap = document.createElement('div');
                        descriptionFieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field';
                        const descriptionInput = document.createElement('input');
                        descriptionInput.className = 'form-input pb-snap-cards-content-input pb-carousel-content-input';
                        descriptionInput.type = 'text';
                        descriptionInput.value = String(descriptionItems[itemIndex] || '');
                        descriptionInput.placeholder = descriptionPlaceholder;
                        descriptionInput.title = descriptionPlaceholder;
                        descriptionInput.setAttribute('aria-label', descriptionPlaceholder);
                        descriptionInput.addEventListener('input', () => {
                            descriptionItems[itemIndex] = String(descriptionInput.value || '');
                            syncNwCarrouselContentItems(false);
                        });
                        descriptionInput.addEventListener('change', () => {
                            descriptionItems[itemIndex] = String(descriptionInput.value || '');
                            syncNwCarrouselContentItems(true);
                        });
                        descriptionFieldWrap.appendChild(descriptionInput);
                        grid.appendChild(descriptionFieldWrap);

                        const textAlignField = {
                            key: 'itemTextAlign',
                            label: label('fieldAlign', 'Alignement'),
                            type: 'select',
                            options: ['left', 'center', 'right'],
                        };
                        const textAlignControl = createAlignIconControl(
                            textAlignField,
                            resolveTextStyleState(block.settings || {}, `itemTextStyle${itemIndex + 1}`, 'left').align,
                            (nextValue) => {
                                updateSetting(
                                    block.id,
                                    textStyleSettingKey(`itemTextStyle${itemIndex + 1}`, TEXT_STYLE_SUFFIX.align),
                                    normalizeAlign(nextValue)
                                );
                            }
                        );
                        textAlignControl.classList.add('pb-carousel-content-align');
                        const actionsWrap = createRepeaterCardActionsRow({
                            rowClass: 'pb-carousel-content-actions-wrap pb-nw-carrousel-content-actions-wrap',
                            controls: [textAlignControl, removeBtn],
                        });
                        grid.appendChild(actionsWrap);

                        list.appendChild(cardParts.card);
                    });
                };

                const addBtn = createRepeaterAddButton(`<i class="fas fa-plus"></i> ${escapeHtml(label('add', 'Ajouter'))}`);
                addBtn.disabled = maxItems > 0 && titleItems.length >= maxItems;
                addBtn.addEventListener('click', () => {
                    if (maxItems > 0 && titleItems.length >= maxItems) {
                        return;
                    }
                    queueInspectorFocus('.pb-carousel-content-card:last-child .pb-carousel-content-input');
                    titleItems.push('');
                    descriptionItems.push('');
                    imageItems.push('');
                    linkItems.push('');
                    buttonEnabledItems.push('on');
                    buttonLabelItems.push('');
                    buttonTargetItems.push('_self');
                    buttonAlignItems.push('left');
                    syncNwCarrouselContentItems(true);
                });

                renderNwCarrouselContentCards();
                body.appendChild(addBtn);
                wrap.appendChild(body);
            } else if (useHeroContentEditor) {
                wrap.classList.add('is-wide', 'pb-feature-grid-content-editor', 'pb-carousel-content-editor', 'pb-hero-content-editor');
                labelEl.remove();

                const textPlaceholder = String(field.placeholder || field.label || label('fieldShortDescription', 'Description courte')).trim();
                const clearLabelText = label('builder_clear_color', 'Effacer');
                const normalizeHeroShortText = (value) => {
                    const source = String(value || '');
                    if (!source) {
                        return '';
                    }
                    const plainSource = source.replace(/<br\s*\/?>/gi, ' ');
                    const probe = document.createElement('div');
                    probe.innerHTML = plainSource;
                    return String(probe.textContent || probe.innerText || '')
                        .replace(/\s+/g, ' ')
                        .trim();
                };

                const syncHeroContent = (patch, refreshInspector) => {
                    updateSettings(block.id, Object.assign({
                        title: String(block.settings.title || ''),
                        subtitle: String(block.settings.subtitle || ''),
                    }, patch && typeof patch === 'object' ? patch : {}));
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };

                const scaffold = createRepeaterCardScaffold({
                    bodyClass: 'fc-builder-card-editor-body pb-feature-grid-content-body',
                    listClass: 'fc-builder-card-editor-list pb-feature-grid-content-list',
                });
                const body = scaffold.body;
                const list = scaffold.list;

                const cardParts = createRepeaterCard({
                    cardClass: 'fc-builder-card pb-feature-grid-content-card pb-carousel-content-card',
                    gridClass: 'fc-builder-card-grid pb-carousel-content-grid',
                    removeButtonClass: 'pb-carousel-content-remove pb-hero-content-action',
                    attachHead: false,
                });
                const card = cardParts.card;
                const grid = cardParts.grid;

                const textFieldWrap = document.createElement('div');
                textFieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field pb-hero-content-text-field';
                const textInput = document.createElement('input');
                textInput.className = 'form-input pb-snap-cards-content-input pb-carousel-content-input';
                textInput.type = 'text';
                textInput.value = normalizeHeroShortText(block.settings.subtitle || '');
                textInput.placeholder = textPlaceholder;
                textInput.title = textPlaceholder;
                textInput.setAttribute('aria-label', textPlaceholder);
                textInput.addEventListener('input', () => {
                    syncHeroContent({ subtitle: String(textInput.value || '') }, false);
                });
                textInput.addEventListener('change', () => {
                    syncHeroContent({ subtitle: String(textInput.value || '') }, true);
                });
                textFieldWrap.appendChild(textInput);
                grid.appendChild(textFieldWrap);

                const textAlignField = {
                    key: 'heroSubtitleAlign',
                    label: label('fieldAlign', 'Alignement'),
                    type: 'select',
                    options: ['left', 'center', 'right'],
                };
                const textAlignControl = createAlignIconControl(
                    textAlignField,
                    resolveTextStyleState(block.settings || {}, 'subtitleStyle', 'left').align,
                    (nextValue) => {
                        updateSetting(
                            block.id,
                            textStyleSettingKey('subtitleStyle', TEXT_STYLE_SUFFIX.align),
                            normalizeAlign(nextValue)
                        );
                    }
                );
                textAlignControl.classList.add('pb-carousel-content-align');

                const actionBtn = cardParts.removeButton;
                actionBtn.title = clearLabelText;
                actionBtn.setAttribute('aria-label', clearLabelText);
                actionBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    updateSettings(block.id, {
                        subtitle: '',
                        [textStyleSettingKey('subtitleStyle', TEXT_STYLE_SUFFIX.align)]: 'left',
                    });
                    renderInspector();
                });
                const actionsWrap = createRepeaterCardActionsRow({
                    rowClass: 'pb-carousel-content-actions-wrap',
                    controls: [textAlignControl, actionBtn],
                });
                grid.appendChild(actionsWrap);

                list.appendChild(card);
                wrap.appendChild(body);
            } else if (useStatsSectionContentEditor) {
                wrap.classList.add('is-wide', 'pb-stats-section-content-editor');
                labelEl.remove();

                const labelsField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'labels')
                    : null;
                const notesField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'notes')
                    : null;
                const delimiter = '\n';
                const valueRepeater = field.repeater || {};
                const labelRepeater = labelsField && labelsField.repeater ? labelsField.repeater : {};
                const noteRepeater = notesField && notesField.repeater ? notesField.repeater : {};
                const minItems = Math.max(
                    1,
                    Number(valueRepeater.min || 0),
                    Number(labelRepeater.min || 0)
                );
                const maxItems = Math.max(
                    0,
                    Number(valueRepeater.max || 0),
                    Number(labelRepeater.max || 0),
                    Number(noteRepeater.max || 0)
                );

                let valueItems = parseRepeaterValues(block.settings.values || '', delimiter);
                let labelItems = parseRepeaterValues(block.settings.labels || '', delimiter);
                let noteItems = parseRepeaterValues(block.settings.notes || '', delimiter);

                const normalizeStatsSectionItems = (preserveLength) => {
                    const nextSettings = {
                        values: serializeRepeaterValues(valueItems, delimiter),
                        labels: serializeRepeaterValues(labelItems, delimiter),
                        notes: serializeRepeaterValues(noteItems, delimiter),
                    };
                    normalizeWidgetLinkedRepeaters('stats_section', nextSettings, {
                        compact: true,
                        minLength: Math.max(minItems, Number(preserveLength || 0)),
                    });
                    valueItems = parseRepeaterValues(nextSettings.values || '', delimiter);
                    labelItems = parseRepeaterValues(nextSettings.labels || '', delimiter);
                    noteItems = parseRepeaterValues(nextSettings.notes || '', delimiter);

                    const targetLength = Math.max(minItems, valueItems.length, labelItems.length, noteItems.length);
                    while (valueItems.length < targetLength) {
                        valueItems.push('');
                    }
                    while (labelItems.length < targetLength) {
                        labelItems.push('');
                    }
                    while (noteItems.length < targetLength) {
                        noteItems.push('');
                    }

                    if (maxItems > 0) {
                        valueItems = valueItems.slice(0, maxItems);
                        labelItems = labelItems.slice(0, maxItems);
                        noteItems = noteItems.slice(0, maxItems);
                    }
                };

                const syncStatsSectionItems = (refreshInspector) => {
                    normalizeStatsSectionItems(valueItems.length);
                    updateSettings(block.id, {
                        values: serializeRepeaterValues(valueItems, delimiter),
                        labels: serializeRepeaterValues(labelItems, delimiter),
                        notes: serializeRepeaterValues(noteItems, delimiter),
                    });
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };

                normalizeStatsSectionItems(valueItems.length);

                const valueItemLabel = String(valueRepeater.itemLabel || field.label || label('fieldValue', '')).trim();
                const labelItemLabel = String((labelRepeater && labelRepeater.itemLabel) || (labelsField && labelsField.label) || label('fieldLabel', '')).trim();
                const noteItemLabel = String((noteRepeater && noteRepeater.itemLabel) || (notesField && notesField.label) || label('fieldDescription', '')).trim();
                const removeLabelText = label('confirmDelete', '');
                const buildIndexedLabel = (baseText, itemIndex) => `${String(baseText || '').trim()} ${itemIndex + 1}`.trim();

                const scaffold = createRepeaterCardScaffold({
                    bodyClass: 'fc-builder-card-editor-body pb-stats-section-content-body',
                    listClass: 'fc-builder-card-editor-list pb-stats-section-content-list',
                });
                const body = scaffold.body;
                const list = scaffold.list;

                const renderStatsSectionContent = () => {
                    list.innerHTML = '';

                    valueItems.forEach((valueValue, itemIndex) => {
                        const cardParts = createRepeaterCard({
                            cardClass: 'fc-builder-card pb-feature-grid-content-card pb-stats-section-content-card',
                            headClass: 'fc-builder-card-head pb-stats-section-content-card-head',
                            titleClass: 'fc-builder-card-title pb-stats-section-content-card-title',
                            gridClass: 'fc-builder-card-grid pb-stats-section-content-grid',
                            title: buildIndexedLabel(valueItemLabel, itemIndex),
                            removeButtonClass: 'pb-carousel-content-remove',
                        });
                        const card = cardParts.card;
                        const removeBtn = cardParts.removeButton;
                        removeBtn.title = `${removeLabelText} ${buildIndexedLabel(valueItemLabel, itemIndex)}`;
                        removeBtn.setAttribute('aria-label', `${removeLabelText} ${buildIndexedLabel(valueItemLabel, itemIndex)}`);
                        removeBtn.disabled = valueItems.length <= minItems;
                        removeBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            if (valueItems.length <= minItems) {
                                return;
                            }
                            confirmDeleteAction(
                                label('removeBlockConfirm', ''),
                                () => {
                                    valueItems.splice(itemIndex, 1);
                                    labelItems.splice(itemIndex, 1);
                                    noteItems.splice(itemIndex, 1);
                                    syncStatsSectionItems(true);
                                },
                                {
                                    confirmText: label('confirmDelete', ''),
                                    itemName: buildIndexedLabel(valueItemLabel, itemIndex),
                                }
                            );
                        });
                        const grid = cardParts.grid;

                        const valueFieldWrap = document.createElement('div');
                        valueFieldWrap.className = 'pb-stats-section-content-field';
                        const valueInput = document.createElement('input');
                        valueInput.className = 'form-input pb-stats-section-content-input';
                        valueInput.type = 'text';
                        valueInput.value = String(valueValue || '');
                        valueInput.placeholder = buildIndexedLabel(valueItemLabel, itemIndex);
                        valueInput.title = buildIndexedLabel(valueItemLabel, itemIndex);
                        valueInput.setAttribute('aria-label', buildIndexedLabel(valueItemLabel, itemIndex));
                        valueInput.addEventListener('input', () => {
                            valueItems[itemIndex] = String(valueInput.value || '');
                            syncStatsSectionItems(false);
                        });
                        valueInput.addEventListener('change', () => {
                            valueItems[itemIndex] = String(valueInput.value || '');
                            syncStatsSectionItems(true);
                        });
                        valueFieldWrap.appendChild(valueInput);
                        grid.appendChild(valueFieldWrap);

                        const labelFieldWrap = document.createElement('div');
                        labelFieldWrap.className = 'pb-stats-section-content-field';
                        const labelInput = document.createElement('input');
                        labelInput.className = 'form-input pb-stats-section-content-input';
                        labelInput.type = 'text';
                        labelInput.value = String(labelItems[itemIndex] || '');
                        labelInput.placeholder = buildIndexedLabel(labelItemLabel, itemIndex);
                        labelInput.title = buildIndexedLabel(labelItemLabel, itemIndex);
                        labelInput.setAttribute('aria-label', buildIndexedLabel(labelItemLabel, itemIndex));
                        labelInput.addEventListener('input', () => {
                            labelItems[itemIndex] = String(labelInput.value || '');
                            syncStatsSectionItems(false);
                        });
                        labelInput.addEventListener('change', () => {
                            labelItems[itemIndex] = String(labelInput.value || '');
                            syncStatsSectionItems(true);
                        });
                        labelFieldWrap.appendChild(labelInput);
                        grid.appendChild(labelFieldWrap);

                        const noteFieldWrap = document.createElement('div');
                        noteFieldWrap.className = 'pb-stats-section-content-field pb-stats-section-content-field--note';
                        const noteInput = document.createElement('input');
                        noteInput.className = 'form-input pb-stats-section-content-input';
                        noteInput.type = 'text';
                        noteInput.value = String(noteItems[itemIndex] || '');
                        noteInput.placeholder = buildIndexedLabel(noteItemLabel, itemIndex);
                        noteInput.title = buildIndexedLabel(noteItemLabel, itemIndex);
                        noteInput.setAttribute('aria-label', buildIndexedLabel(noteItemLabel, itemIndex));
                        noteInput.addEventListener('input', () => {
                            noteItems[itemIndex] = String(noteInput.value || '');
                            syncStatsSectionItems(false);
                        });
                        noteInput.addEventListener('change', () => {
                            noteItems[itemIndex] = String(noteInput.value || '');
                            syncStatsSectionItems(true);
                        });
                        noteFieldWrap.appendChild(noteInput);
                        grid.appendChild(noteFieldWrap);

                        card.appendChild(grid);
                        list.appendChild(card);
                    });
                };

                const addBtn = createRepeaterAddButton(`<i class="fas fa-plus"></i> ${escapeHtml(label('add', 'Ajouter'))}`);
                addBtn.disabled = maxItems > 0 && valueItems.length >= maxItems;
                addBtn.addEventListener('click', () => {
                    if (maxItems > 0 && valueItems.length >= maxItems) {
                        return;
                    }
                    queueInspectorFocus('.pb-stats-section-content-card:last-child .pb-stats-section-content-input');
                    valueItems.push('');
                    labelItems.push('');
                    noteItems.push('');
                    syncStatsSectionItems(true);
                });

                renderStatsSectionContent();
                body.appendChild(addBtn);
                wrap.appendChild(body);
            } else if (useFaqAccordionContentEditor) {
                wrap.classList.add('is-wide', 'pb-faq-accordion-content-editor');
                labelEl.remove();

                const answersField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'answers')
                    : null;
                const delimiter = '\n---\n';
                const questionRepeater = field.repeater || {};
                const answerRepeater = answersField && answersField.repeater ? answersField.repeater : {};
                const minItems = Math.max(1, Number(questionRepeater.min || 0), Number(answerRepeater.min || 0));
                const maxItems = Math.max(0, Number(questionRepeater.max || 0), Number(answerRepeater.max || 0));

                const parseFaqAccordionValues = (value) => {
                    const raw = String(value || '');
                    if (raw.includes(delimiter)) {
                        return parseRepeaterValues(raw, delimiter);
                    }
                    return parseRepeaterValues(raw || '', '\n');
                };

                let questionItems = parseFaqAccordionValues(block.settings.questions || '');
                let answerItems = parseFaqAccordionValues(block.settings.answers || '');

                const normalizeFaqAccordionItems = (preserveLength) => {
                    const nextSettings = {
                        questions: serializeRepeaterValues(questionItems, delimiter),
                        answers: serializeRepeaterValues(answerItems, delimiter),
                    };
                    normalizeWidgetLinkedRepeaters('faq_accordion', nextSettings, {
                        compact: true,
                        minLength: Math.max(minItems, Number(preserveLength || 0)),
                    });
                    questionItems = parseFaqAccordionValues(nextSettings.questions || '');
                    answerItems = parseFaqAccordionValues(nextSettings.answers || '');

                    const targetLength = Math.max(minItems, questionItems.length, answerItems.length);
                    while (questionItems.length < targetLength) {
                        questionItems.push('');
                    }
                    while (answerItems.length < targetLength) {
                        answerItems.push('');
                    }

                    if (maxItems > 0) {
                        questionItems = questionItems.slice(0, maxItems);
                        answerItems = answerItems.slice(0, maxItems);
                    }
                };

                const syncFaqAccordionItems = (refreshInspector) => {
                    normalizeFaqAccordionItems(questionItems.length);
                    updateSettings(block.id, {
                        questions: serializeRepeaterValues(questionItems, delimiter),
                        answers: serializeRepeaterValues(answerItems, delimiter),
                    });
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };

                normalizeFaqAccordionItems(questionItems.length);

                const questionItemLabel = String(questionRepeater.itemLabel || field.label || label('fieldLabel', '')).trim();
                const answerItemLabel = String((answerRepeater && answerRepeater.itemLabel) || (answersField && answersField.label) || label('fieldDescription', '')).trim();
                const removeLabelText = label('confirmDelete', '');
                const buildIndexedLabel = (baseText, itemIndex) => `${String(baseText || '').trim()} ${itemIndex + 1}`.trim();

                const scaffold = createRepeaterCardScaffold({
                    bodyClass: 'fc-builder-card-editor-body pb-faq-accordion-content-body',
                    listClass: 'fc-builder-card-editor-list pb-faq-accordion-content-list',
                });
                const body = scaffold.body;
                const list = scaffold.list;

                const renderFaqAccordionContent = () => {
                    list.innerHTML = '';

                    questionItems.forEach((questionValue, itemIndex) => {
                        const cardParts = createRepeaterCard({
                            cardClass: 'fc-builder-card pb-feature-grid-content-card pb-faq-accordion-content-card',
                            headClass: 'fc-builder-card-head pb-faq-accordion-content-card-head',
                            titleClass: 'fc-builder-card-title pb-faq-accordion-content-card-title',
                            gridClass: 'fc-builder-card-grid pb-faq-accordion-content-grid',
                            title: buildIndexedLabel(questionItemLabel, itemIndex),
                            removeButtonClass: 'pb-carousel-content-remove',
                        });
                        const card = cardParts.card;
                        const removeBtn = cardParts.removeButton;
                        removeBtn.title = `${removeLabelText} ${buildIndexedLabel(questionItemLabel, itemIndex)}`;
                        removeBtn.setAttribute('aria-label', `${removeLabelText} ${buildIndexedLabel(questionItemLabel, itemIndex)}`);
                        removeBtn.disabled = questionItems.length <= minItems;
                        removeBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            if (questionItems.length <= minItems) {
                                return;
                            }
                            confirmDeleteAction(
                                label('removeBlockConfirm', ''),
                                () => {
                                    questionItems.splice(itemIndex, 1);
                                    answerItems.splice(itemIndex, 1);
                                    syncFaqAccordionItems(true);
                                },
                                {
                                    confirmText: label('confirmDelete', ''),
                                    itemName: buildIndexedLabel(questionItemLabel, itemIndex),
                                }
                            );
                        });
                        const grid = cardParts.grid;

                        const questionFieldWrap = document.createElement('div');
                        questionFieldWrap.className = 'pb-faq-accordion-content-field';
                        const questionInput = document.createElement('input');
                        questionInput.className = 'form-input pb-faq-accordion-content-input';
                        questionInput.type = 'text';
                        questionInput.value = String(questionValue || '');
                        questionInput.placeholder = buildIndexedLabel(questionItemLabel, itemIndex);
                        questionInput.title = buildIndexedLabel(questionItemLabel, itemIndex);
                        questionInput.setAttribute('aria-label', buildIndexedLabel(questionItemLabel, itemIndex));
                        questionInput.addEventListener('input', () => {
                            questionItems[itemIndex] = String(questionInput.value || '');
                            syncFaqAccordionItems(false);
                        });
                        questionInput.addEventListener('change', () => {
                            questionItems[itemIndex] = String(questionInput.value || '');
                            syncFaqAccordionItems(false);
                        });
                        questionFieldWrap.appendChild(questionInput);
                        grid.appendChild(questionFieldWrap);

                        const answerFieldWrap = document.createElement('div');
                        answerFieldWrap.className = 'pb-faq-accordion-content-field pb-faq-accordion-content-field--answer';
                        const answerInput = document.createElement('textarea');
                        answerInput.className = 'form-input pb-faq-accordion-content-textarea';
                        answerInput.rows = 4;
                        answerInput.value = String(answerItems[itemIndex] || '');
                        answerInput.placeholder = buildIndexedLabel(answerItemLabel, itemIndex);
                        answerInput.title = buildIndexedLabel(answerItemLabel, itemIndex);
                        answerInput.setAttribute('aria-label', buildIndexedLabel(answerItemLabel, itemIndex));
                        answerInput.addEventListener('input', () => {
                            answerItems[itemIndex] = String(answerInput.value || '');
                            syncFaqAccordionItems(false);
                        });
                        answerInput.addEventListener('change', () => {
                            answerItems[itemIndex] = String(answerInput.value || '');
                            syncFaqAccordionItems(false);
                        });
                        answerFieldWrap.appendChild(answerInput);
                        grid.appendChild(answerFieldWrap);

                        card.appendChild(grid);
                        list.appendChild(card);
                    });
                };

                const addBtn = createRepeaterAddButton(`<i class="fas fa-plus"></i> ${escapeHtml(label('add', 'Ajouter'))}`);
                addBtn.disabled = maxItems > 0 && questionItems.length >= maxItems;
                addBtn.addEventListener('click', () => {
                    if (maxItems > 0 && questionItems.length >= maxItems) {
                        return;
                    }
                    queueInspectorFocus('.pb-faq-accordion-content-card:last-child .pb-faq-accordion-content-input');
                    questionItems.push('');
                    answerItems.push('');
                    syncFaqAccordionItems(true);
                });

                renderFaqAccordionContent();
                body.appendChild(addBtn);
                wrap.appendChild(body);
            } else if (useTestimonialCardsContentEditor) {
                wrap.classList.add('is-wide', 'pb-testimonial-cards-content-editor');
                labelEl.remove();

                const namesField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'names')
                    : null;
                const companiesField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'companies')
                    : null;
                const rolesField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'roles')
                    : null;
                const ratingsField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'ratings')
                    : null;
                const quoteDelimiter = '\n---\n';
                const lineDelimiter = '\n';
                const quoteRepeater = field.repeater || {};
                const nameRepeater = namesField && namesField.repeater ? namesField.repeater : {};
                const companyRepeater = companiesField && companiesField.repeater ? companiesField.repeater : {};
                const roleRepeater = rolesField && rolesField.repeater ? rolesField.repeater : {};
                const ratingRepeater = ratingsField && ratingsField.repeater ? ratingsField.repeater : {};
                const minItems = Math.max(
                    1,
                    Number(quoteRepeater.min || 0),
                    Number(nameRepeater.min || 0),
                    Number(companyRepeater.min || 0),
                    Number(roleRepeater.min || 0),
                    Number(ratingRepeater.min || 0)
                );
                const maxItems = Math.max(
                    0,
                    Number(quoteRepeater.max || 0),
                    Number(nameRepeater.max || 0),
                    Number(companyRepeater.max || 0),
                    Number(roleRepeater.max || 0),
                    Number(ratingRepeater.max || 0)
                );

                let quoteItems = parseRepeaterValues(block.settings.quotes || '', quoteDelimiter);
                let nameItems = parseRepeaterValues(block.settings.names || '', lineDelimiter);
                let companyItems = parseRepeaterValues(block.settings.companies || '', lineDelimiter);
                let roleItems = parseRepeaterValues(block.settings.roles || '', lineDelimiter);
                let ratingItems = parseRepeaterValues(block.settings.ratings || '', lineDelimiter);
                let avatarItems = parseRepeaterValues(block.settings.avatars || '', lineDelimiter);
                let linkItems = parseRepeaterValues(block.settings.links || '', lineDelimiter);
                let targetItems = parseRepeaterValues(block.settings.targets || '', lineDelimiter);

                const normalizeTestimonialContentItems = (preserveLength) => {
                    const nextSettings = {
                        quotes: serializeRepeaterValues(quoteItems, quoteDelimiter),
                        names: serializeRepeaterValues(nameItems, lineDelimiter),
                        companies: serializeRepeaterValues(companyItems, lineDelimiter),
                        roles: serializeRepeaterValues(roleItems, lineDelimiter),
                        ratings: serializeRepeaterValues(ratingItems, lineDelimiter),
                        avatars: serializeRepeaterValues(avatarItems, lineDelimiter),
                        links: serializeRepeaterValues(linkItems, lineDelimiter),
                        targets: serializeRepeaterValues(targetItems, lineDelimiter),
                    };
                    normalizeWidgetLinkedRepeaters('testimonial_cards', nextSettings, {
                        compact: true,
                        minLength: Math.max(minItems, Number(preserveLength || 0)),
                    });
                    quoteItems = parseRepeaterValues(nextSettings.quotes || '', quoteDelimiter);
                    nameItems = parseRepeaterValues(nextSettings.names || '', lineDelimiter);
                    companyItems = parseRepeaterValues(nextSettings.companies || '', lineDelimiter);
                    roleItems = parseRepeaterValues(nextSettings.roles || '', lineDelimiter);
                    ratingItems = parseRepeaterValues(nextSettings.ratings || '', lineDelimiter);
                    avatarItems = parseRepeaterValues(nextSettings.avatars || '', lineDelimiter);
                    linkItems = parseRepeaterValues(nextSettings.links || '', lineDelimiter);
                    targetItems = parseRepeaterValues(nextSettings.targets || '', lineDelimiter);

                    const targetLength = Math.max(
                        minItems,
                        quoteItems.length,
                        nameItems.length,
                        companyItems.length,
                        roleItems.length,
                        ratingItems.length,
                        avatarItems.length,
                        linkItems.length,
                        targetItems.length
                    );
                    while (quoteItems.length < targetLength) quoteItems.push('');
                    while (nameItems.length < targetLength) nameItems.push('');
                    while (companyItems.length < targetLength) companyItems.push('');
                    while (roleItems.length < targetLength) roleItems.push('');
                    while (ratingItems.length < targetLength) ratingItems.push('5');
                    while (avatarItems.length < targetLength) avatarItems.push('');
                    while (linkItems.length < targetLength) linkItems.push('');
                    while (targetItems.length < targetLength) targetItems.push('_self');

                    if (maxItems > 0) {
                        quoteItems = quoteItems.slice(0, maxItems);
                        nameItems = nameItems.slice(0, maxItems);
                        companyItems = companyItems.slice(0, maxItems);
                        roleItems = roleItems.slice(0, maxItems);
                        ratingItems = ratingItems.slice(0, maxItems);
                        avatarItems = avatarItems.slice(0, maxItems);
                        linkItems = linkItems.slice(0, maxItems);
                        targetItems = targetItems.slice(0, maxItems);
                    }
                };

                const syncTestimonialContentItems = (refreshInspector) => {
                    normalizeTestimonialContentItems(quoteItems.length);
                    updateSettings(block.id, {
                        quotes: serializeRepeaterValues(quoteItems, quoteDelimiter),
                        names: serializeRepeaterValues(nameItems, lineDelimiter),
                        companies: serializeRepeaterValues(companyItems, lineDelimiter),
                        roles: serializeRepeaterValues(roleItems, lineDelimiter),
                        ratings: serializeRepeaterValues(ratingItems, lineDelimiter),
                        avatars: serializeRepeaterValues(avatarItems, lineDelimiter),
                        links: serializeRepeaterValues(linkItems, lineDelimiter),
                        targets: serializeRepeaterValues(targetItems, lineDelimiter),
                    });
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };

                normalizeTestimonialContentItems(quoteItems.length);

                const quoteItemLabel = String(quoteRepeater.itemLabel || field.label || label('fieldContent', 'Témoignage')).trim();
                const nameItemLabel = String((nameRepeater && nameRepeater.itemLabel) || (namesField && namesField.label) || label('fieldName', 'Nom')).trim();
                const companyItemLabel = String((companyRepeater && companyRepeater.itemLabel) || (companiesField && companiesField.label) || label('fieldCompany', 'Société')).trim();
                const roleItemLabel = String((roleRepeater && roleRepeater.itemLabel) || (rolesField && rolesField.label) || label('fieldRole', 'Rôle')).trim();
                const ratingItemLabel = String((ratingRepeater && ratingRepeater.itemLabel) || (ratingsField && ratingsField.label) || label('fieldRating', 'Note')).trim();
                const removeLabelText = label('confirmDelete', 'Supprimer');
                const buildIndexedLabel = (baseText, itemIndex) => `${String(baseText || '').trim()} ${itemIndex + 1}`.trim();

                const scaffold = createRepeaterCardScaffold({
                    bodyClass: 'fc-builder-card-editor-body pb-testimonial-cards-content-body',
                    listClass: 'fc-builder-card-editor-list pb-testimonial-cards-content-list',
                });
                const body = scaffold.body;
                const list = scaffold.list;

                const renderTestimonialContent = () => {
                    list.innerHTML = '';

                    quoteItems.forEach((quoteValue, itemIndex) => {
                        const cardParts = createRepeaterCard({
                            cardClass: 'fc-builder-card pb-feature-grid-content-card pb-testimonial-cards-content-card',
                            headClass: 'fc-builder-card-head pb-testimonial-cards-content-card-head',
                            titleClass: 'fc-builder-card-title pb-testimonial-cards-content-card-title',
                            gridClass: 'fc-builder-card-grid pb-testimonial-cards-content-grid',
                            title: buildIndexedLabel(quoteItemLabel, itemIndex),
                            removeButtonClass: 'pb-carousel-content-remove',
                        });
                        const card = cardParts.card;
                        const removeBtn = cardParts.removeButton;
                        removeBtn.title = `${removeLabelText} ${buildIndexedLabel(quoteItemLabel, itemIndex)}`;
                        removeBtn.setAttribute('aria-label', `${removeLabelText} ${buildIndexedLabel(quoteItemLabel, itemIndex)}`);
                        removeBtn.disabled = quoteItems.length <= minItems;
                        removeBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            if (quoteItems.length <= minItems) {
                                return;
                            }
                            confirmDeleteAction(
                                label('removeBlockConfirm', 'Supprimer ce bloc ?'),
                                () => {
                                    quoteItems.splice(itemIndex, 1);
                                    nameItems.splice(itemIndex, 1);
                                    companyItems.splice(itemIndex, 1);
                                    roleItems.splice(itemIndex, 1);
                                    ratingItems.splice(itemIndex, 1);
                                    avatarItems.splice(itemIndex, 1);
                                    linkItems.splice(itemIndex, 1);
                                    targetItems.splice(itemIndex, 1);
                                    syncTestimonialContentItems(true);
                                },
                                {
                                    confirmText: label('confirmDelete', 'Supprimer'),
                                    itemName: buildIndexedLabel(quoteItemLabel, itemIndex),
                                }
                            );
                        });
                        const grid = cardParts.grid;

                        const nameFieldWrap = document.createElement('div');
                        nameFieldWrap.className = 'pb-testimonial-cards-content-field';
                        const nameInput = document.createElement('input');
                        nameInput.className = 'form-input pb-testimonial-cards-content-input';
                        nameInput.type = 'text';
                        nameInput.value = String(nameItems[itemIndex] || '');
                        nameInput.placeholder = buildIndexedLabel(nameItemLabel, itemIndex);
                        nameInput.title = buildIndexedLabel(nameItemLabel, itemIndex);
                        nameInput.setAttribute('aria-label', buildIndexedLabel(nameItemLabel, itemIndex));
                        nameInput.addEventListener('input', () => {
                            nameItems[itemIndex] = String(nameInput.value || '');
                            syncTestimonialContentItems(false);
                        });
                        nameInput.addEventListener('change', () => {
                            nameItems[itemIndex] = String(nameInput.value || '');
                            syncTestimonialContentItems(false);
                        });
                        nameFieldWrap.appendChild(nameInput);
                        grid.appendChild(nameFieldWrap);

                        const companyFieldWrap = document.createElement('div');
                        companyFieldWrap.className = 'pb-testimonial-cards-content-field';
                        const companyInput = document.createElement('input');
                        companyInput.className = 'form-input pb-testimonial-cards-content-input';
                        companyInput.type = 'text';
                        companyInput.value = String(companyItems[itemIndex] || '');
                        companyInput.placeholder = buildIndexedLabel(companyItemLabel, itemIndex);
                        companyInput.title = buildIndexedLabel(companyItemLabel, itemIndex);
                        companyInput.setAttribute('aria-label', buildIndexedLabel(companyItemLabel, itemIndex));
                        companyInput.addEventListener('input', () => {
                            companyItems[itemIndex] = String(companyInput.value || '');
                            syncTestimonialContentItems(false);
                        });
                        companyInput.addEventListener('change', () => {
                            companyItems[itemIndex] = String(companyInput.value || '');
                            syncTestimonialContentItems(false);
                        });
                        companyFieldWrap.appendChild(companyInput);
                        grid.appendChild(companyFieldWrap);

                        const roleFieldWrap = document.createElement('div');
                        roleFieldWrap.className = 'pb-testimonial-cards-content-field';
                        const roleInput = document.createElement('input');
                        roleInput.className = 'form-input pb-testimonial-cards-content-input';
                        roleInput.type = 'text';
                        roleInput.value = String(roleItems[itemIndex] || '');
                        roleInput.placeholder = buildIndexedLabel(roleItemLabel, itemIndex);
                        roleInput.title = buildIndexedLabel(roleItemLabel, itemIndex);
                        roleInput.setAttribute('aria-label', buildIndexedLabel(roleItemLabel, itemIndex));
                        roleInput.addEventListener('input', () => {
                            roleItems[itemIndex] = String(roleInput.value || '');
                            syncTestimonialContentItems(false);
                        });
                        roleInput.addEventListener('change', () => {
                            roleItems[itemIndex] = String(roleInput.value || '');
                            syncTestimonialContentItems(false);
                        });
                        roleFieldWrap.appendChild(roleInput);
                        grid.appendChild(roleFieldWrap);

                        const ratingFieldWrap = document.createElement('div');
                        ratingFieldWrap.className = 'pb-testimonial-cards-content-field pb-testimonial-cards-content-field--rating';
                        const ratingControl = document.createElement('div');
                        ratingControl.className = 'pb-testimonial-cards-rating-control';
                        ratingControl.setAttribute('role', 'radiogroup');
                        ratingControl.setAttribute('aria-label', buildIndexedLabel(ratingItemLabel, itemIndex));

                        const starButtons = [];
                        const paintRating = (value) => {
                            const normalized = normalizeTestimonialRating(value);
                            starButtons.forEach((button) => {
                                const starValue = Number(button.dataset.value || '0');
                                const isActive = starValue <= normalized;
                                button.classList.toggle('is-active', isActive);
                                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                            });
                        };

                        for (let starValue = 1; starValue <= 5; starValue += 1) {
                            const starBtn = document.createElement('button');
                            starBtn.type = 'button';
                            starBtn.className = 'pb-testimonial-cards-rating-star';
                            starBtn.dataset.value = String(starValue);
                            starBtn.title = `${buildIndexedLabel(ratingItemLabel, itemIndex)} ${starValue}/5`;
                            starBtn.setAttribute('aria-label', `${buildIndexedLabel(ratingItemLabel, itemIndex)} ${starValue}/5`);
                            starBtn.innerHTML = '<i class="fas fa-star" aria-hidden="true"></i>';
                            starBtn.addEventListener('click', (event) => {
                                event.preventDefault();
                                const nextRating = normalizeTestimonialRating(starValue);
                                ratingItems[itemIndex] = String(nextRating);
                                paintRating(nextRating);
                                syncTestimonialContentItems(false);
                            });
                            starButtons.push(starBtn);
                            ratingControl.appendChild(starBtn);
                        }

                        paintRating(ratingItems[itemIndex] || '5');
                        ratingFieldWrap.appendChild(ratingControl);
                        grid.appendChild(ratingFieldWrap);

                        const quoteFieldWrap = document.createElement('div');
                        quoteFieldWrap.className = 'pb-testimonial-cards-content-field pb-testimonial-cards-content-field--quote';
                        const quoteInput = document.createElement('textarea');
                        quoteInput.className = 'form-input pb-testimonial-cards-content-textarea';
                        quoteInput.rows = 4;
                        quoteInput.value = String(quoteValue || '');
                        quoteInput.placeholder = buildIndexedLabel(quoteItemLabel, itemIndex);
                        quoteInput.title = buildIndexedLabel(quoteItemLabel, itemIndex);
                        quoteInput.setAttribute('aria-label', buildIndexedLabel(quoteItemLabel, itemIndex));
                        quoteInput.addEventListener('input', () => {
                            quoteItems[itemIndex] = String(quoteInput.value || '');
                            syncTestimonialContentItems(false);
                        });
                        quoteInput.addEventListener('change', () => {
                            quoteItems[itemIndex] = String(quoteInput.value || '');
                            syncTestimonialContentItems(false);
                        });
                        quoteFieldWrap.appendChild(quoteInput);
                        grid.appendChild(quoteFieldWrap);

                        card.appendChild(grid);
                        list.appendChild(card);
                    });
                };

                const addBtn = createRepeaterAddButton(`<i class="fas fa-plus"></i> ${escapeHtml(label('add', 'Ajouter'))}`);
                addBtn.disabled = maxItems > 0 && quoteItems.length >= maxItems;
                addBtn.addEventListener('click', () => {
                    if (maxItems > 0 && quoteItems.length >= maxItems) {
                        return;
                    }
                    queueInspectorFocus('.pb-testimonial-cards-content-card:last-child .pb-testimonial-cards-content-input');
                    quoteItems.push('');
                    nameItems.push('');
                    companyItems.push('');
                    roleItems.push('');
                    ratingItems.push('5');
                    avatarItems.push('');
                    linkItems.push('');
                    targetItems.push('_self');
                    syncTestimonialContentItems(true);
                });

                renderTestimonialContent();
                body.appendChild(addBtn);
                wrap.appendChild(body);
            } else if (useLogoCloudContentEditor) {
                wrap.classList.add('is-wide', 'pb-stats-section-content-editor');
                labelEl.remove();
                const delimiter = '\n';
                const labelRepeater = field.repeater || {};
                const minItems = Math.max(
                    1,
                    Number(labelRepeater.min || 0)
                );
                const maxItems = Math.max(
                    0,
                    Number(labelRepeater.max || 0)
                );

                let labelItems = parseRepeaterValues(block.settings.labels || '', delimiter);
                let logoItems = parseRepeaterValues(block.settings.logos || '', delimiter);
                let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                let targetItems = parseRepeaterValues(block.settings.targets || '', delimiter);

                const normalizeLogoCloudItems = (preserveLength) => {
                    const nextSettings = {
                        labels: serializeRepeaterValues(labelItems, delimiter),
                        logos: serializeRepeaterValues(logoItems, delimiter),
                        links: serializeRepeaterValues(linkItems, delimiter),
                        targets: serializeRepeaterValues(targetItems, delimiter),
                    };
                    normalizeWidgetLinkedRepeaters('logo_cloud', nextSettings, {
                        compact: true,
                        minLength: Math.max(minItems, Number(preserveLength || 0)),
                    });
                    labelItems = parseRepeaterValues(nextSettings.labels || '', delimiter);
                    logoItems = parseRepeaterValues(nextSettings.logos || '', delimiter);
                    linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                    targetItems = parseRepeaterValues(nextSettings.targets || '', delimiter);

                    const targetLength = Math.max(minItems, labelItems.length, logoItems.length, linkItems.length, targetItems.length);
                    while (labelItems.length < targetLength) labelItems.push('');
                    while (logoItems.length < targetLength) logoItems.push('');
                    while (linkItems.length < targetLength) linkItems.push('');
                    while (targetItems.length < targetLength) targetItems.push('_self');

                    if (maxItems > 0) {
                        labelItems = labelItems.slice(0, maxItems);
                        logoItems = logoItems.slice(0, maxItems);
                        linkItems = linkItems.slice(0, maxItems);
                        targetItems = targetItems.slice(0, maxItems);
                    }
                };

                const syncLogoCloudItems = (refreshInspector) => {
                    normalizeLogoCloudItems(labelItems.length);
                    updateSettings(block.id, {
                        labels: serializeRepeaterValues(labelItems, delimiter),
                        logos: serializeRepeaterValues(logoItems, delimiter),
                        links: serializeRepeaterValues(linkItems, delimiter),
                        targets: serializeRepeaterValues(targetItems, delimiter),
                    });
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };

                normalizeLogoCloudItems(labelItems.length);

                const labelItemText = String(labelRepeater.itemLabel || field.label || label('fieldLabel', 'Libellé')).trim();
                const removeLabelText = label('confirmDelete', 'Supprimer');
                const buildIndexedLabel = (baseText, itemIndex) => `${String(baseText || '').trim()} ${itemIndex + 1}`.trim();

                const scaffold = createRepeaterCardScaffold({
                    bodyClass: 'fc-builder-card-editor-body pb-stats-section-content-body',
                    listClass: 'fc-builder-card-editor-list pb-stats-section-content-list',
                });
                const body = scaffold.body;
                const list = scaffold.list;

                const renderLogoCloudContent = () => {
                    list.innerHTML = '';

                    labelItems.forEach((labelValue, itemIndex) => {
                        const cardParts = createRepeaterCard({
                            cardClass: 'fc-builder-card pb-feature-grid-content-card pb-stats-section-content-card',
                            headClass: 'fc-builder-card-head pb-stats-section-content-card-head',
                            titleClass: 'fc-builder-card-title pb-stats-section-content-card-title',
                            gridClass: 'fc-builder-card-grid pb-stats-section-content-grid',
                            title: buildIndexedLabel(labelItemText, itemIndex),
                            removeButtonClass: 'pb-carousel-content-remove',
                        });
                        const card = cardParts.card;
                        const removeBtn = cardParts.removeButton;
                        removeBtn.title = `${removeLabelText} ${buildIndexedLabel(labelItemText, itemIndex)}`;
                        removeBtn.setAttribute('aria-label', `${removeLabelText} ${buildIndexedLabel(labelItemText, itemIndex)}`);
                        removeBtn.disabled = labelItems.length <= minItems;
                        removeBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            if (labelItems.length <= minItems) {
                                return;
                            }
                            confirmDeleteAction(
                                label('removeBlockConfirm', 'Supprimer ce bloc ?'),
                                () => {
                                    labelItems.splice(itemIndex, 1);
                                    logoItems.splice(itemIndex, 1);
                                    linkItems.splice(itemIndex, 1);
                                    targetItems.splice(itemIndex, 1);
                                    syncLogoCloudItems(true);
                                },
                                {
                                    confirmText: label('confirmDelete', 'Supprimer'),
                                    itemName: buildIndexedLabel(labelItemText, itemIndex),
                                }
                            );
                        });
                        const grid = cardParts.grid;

                        const labelFieldWrap = document.createElement('div');
                        labelFieldWrap.className = 'pb-stats-section-content-field';
                        const labelInput = document.createElement('input');
                        labelInput.className = 'form-input pb-stats-section-content-input';
                        labelInput.type = 'text';
                        labelInput.value = String(labelValue || '');
                        labelInput.placeholder = buildIndexedLabel(labelItemText, itemIndex);
                        labelInput.title = buildIndexedLabel(labelItemText, itemIndex);
                        labelInput.setAttribute('aria-label', buildIndexedLabel(labelItemText, itemIndex));
                        labelInput.addEventListener('input', () => {
                            labelItems[itemIndex] = String(labelInput.value || '');
                            syncLogoCloudItems(false);
                        });
                        labelInput.addEventListener('change', () => {
                            labelItems[itemIndex] = String(labelInput.value || '');
                            syncLogoCloudItems(true);
                        });
                        labelFieldWrap.appendChild(labelInput);
                        grid.appendChild(labelFieldWrap);

                        card.appendChild(grid);
                        list.appendChild(card);
                    });
                };

                const addBtn = createRepeaterAddButton(`<i class="fas fa-plus"></i> ${escapeHtml(label('add', 'Ajouter'))}`);
                addBtn.disabled = maxItems > 0 && labelItems.length >= maxItems;
                addBtn.addEventListener('click', () => {
                    if (maxItems > 0 && labelItems.length >= maxItems) {
                        return;
                    }
                    queueInspectorFocus('.pb-stats-section-content-card:last-child .pb-stats-section-content-input');
                    labelItems.push('');
                    logoItems.push('');
                    linkItems.push('');
                    targetItems.push('_self');
                    syncLogoCloudItems(true);
                });

                renderLogoCloudContent();
                body.appendChild(addBtn);
                wrap.appendChild(body);
            } else if (useSnapCardsContentCards) {
                wrap.classList.add('is-wide', 'pb-snap-cards-content-editor', 'pb-carousel-content-editor');
                labelEl.remove();
                const textsField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'texts')
                    : null;
                const delimiter = '\n';
                const titleRepeater = field.repeater || {};
                const textRepeater = textsField && textsField.repeater ? textsField.repeater : {};
                const minItems = Math.max(
                    1,
                    Number(titleRepeater.min || 0),
                    Number(textRepeater.min || 0)
                );
                const maxItems = Math.max(
                    0,
                    Number(titleRepeater.max || 0),
                    Number(textRepeater.max || 0)
                );

                let titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                let textItems = parseRepeaterValues(block.settings.texts || '', delimiter);
                let backgroundItems = parseRepeaterValues(block.settings.backgrounds || '', delimiter);
                let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                let ctaEnabledItems = parseRepeaterValues(block.settings.ctaEnableds || '', delimiter);
                let ctaLabelItems = parseRepeaterValues(block.settings.ctaLabels || '', delimiter);
                let targetItems = parseRepeaterValues(block.settings.targets || '', delimiter);
                let buttonAlignItems = parseRepeaterValues(block.settings.buttonAligns || '', delimiter);
                const resolveSnapCardsBaseAlign = () => normalizeAlign(String((block.settings && block.settings.align) || 'left'));

                const normalizeSnapContentItems = (preserveLength) => {
                    const nextSettings = {
                        titles: serializeRepeaterValues(titleItems, delimiter),
                        texts: serializeRepeaterValues(textItems, delimiter),
                        backgrounds: serializeRepeaterValues(backgroundItems, delimiter),
                        links: serializeRepeaterValues(linkItems, delimiter),
                        ctaEnableds: serializeRepeaterValues(ctaEnabledItems, delimiter),
                        ctaLabels: serializeRepeaterValues(ctaLabelItems, delimiter),
                        targets: serializeRepeaterValues(targetItems, delimiter),
                        buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                        ctaLabel: String(block.settings.ctaLabel || ''),
                        target: String(block.settings.target || '_self'),
                        align: resolveSnapCardsBaseAlign(),
                    };
                    normalizeWidgetLinkedRepeaters('snap_cards', nextSettings, {
                        compact: true,
                        minLength: Math.max(0, Number(preserveLength || 0)),
                    });
                    titleItems = parseRepeaterValues(nextSettings.titles || '', delimiter);
                    textItems = parseRepeaterValues(nextSettings.texts || '', delimiter);
                    backgroundItems = parseRepeaterValues(nextSettings.backgrounds || '', delimiter);
                    linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                    ctaEnabledItems = parseRepeaterValues(nextSettings.ctaEnableds || '', delimiter);
                    ctaLabelItems = parseRepeaterValues(nextSettings.ctaLabels || '', delimiter);
                    targetItems = parseRepeaterValues(nextSettings.targets || '', delimiter);
                    buttonAlignItems = parseRepeaterValues(nextSettings.buttonAligns || '', delimiter);

                    const targetLength = Math.max(
                        minItems,
                        titleItems.length,
                        textItems.length,
                        backgroundItems.length,
                        linkItems.length,
                        ctaEnabledItems.length,
                        ctaLabelItems.length,
                        targetItems.length,
                        buttonAlignItems.length
                    );
                    const baseAlign = resolveSnapCardsBaseAlign();
                    while (titleItems.length < targetLength) titleItems.push('');
                    while (textItems.length < targetLength) textItems.push('');
                    while (backgroundItems.length < targetLength) backgroundItems.push('');
                    while (linkItems.length < targetLength) linkItems.push('');
                    while (ctaEnabledItems.length < targetLength) ctaEnabledItems.push('on');
                    while (ctaLabelItems.length < targetLength) ctaLabelItems.push('');
                    while (targetItems.length < targetLength) targetItems.push('_self');
                    while (buttonAlignItems.length < targetLength) buttonAlignItems.push(baseAlign);

                    if (maxItems > 0) {
                        titleItems = titleItems.slice(0, maxItems);
                        textItems = textItems.slice(0, maxItems);
                        backgroundItems = backgroundItems.slice(0, maxItems);
                        linkItems = linkItems.slice(0, maxItems);
                        ctaEnabledItems = ctaEnabledItems.slice(0, maxItems);
                        ctaLabelItems = ctaLabelItems.slice(0, maxItems);
                        targetItems = targetItems.slice(0, maxItems);
                        buttonAlignItems = buttonAlignItems.slice(0, maxItems);
                    }
                };

                const snapCardsStylePrefixes = ['itemTitleStyle', 'itemTextStyle'];
                const snapCardsStyleSuffixes = ['Align', 'Font', 'Size', 'Bold', 'Italic', 'Underline', 'Color', 'List', 'Icon', 'IconPosition'];
                const buildSnapCardsStyleRemovalPatch = (removedIndex) => {
                    if (removedIndex < 0) {
                        return {};
                    }

                    const sourceSettings = block.settings && typeof block.settings === 'object'
                        ? block.settings
                        : {};
                    const patch = {};
                    const nextLength = titleItems.length;

                    for (let position = removedIndex + 1; position <= nextLength; position += 1) {
                        const sourcePosition = position + 1;
                        snapCardsStylePrefixes.forEach((stylePrefix) => {
                            snapCardsStyleSuffixes.forEach((suffix) => {
                                const sourceKey = `${stylePrefix}${sourcePosition}${suffix}`;
                                const targetKey = `${stylePrefix}${position}${suffix}`;
                                patch[targetKey] = Object.prototype.hasOwnProperty.call(sourceSettings, sourceKey)
                                    ? sourceSettings[sourceKey]
                                    : '';
                            });
                        });
                    }

                    for (let position = nextLength + 1; position <= 12; position += 1) {
                        snapCardsStylePrefixes.forEach((stylePrefix) => {
                            snapCardsStyleSuffixes.forEach((suffix) => {
                                patch[`${stylePrefix}${position}${suffix}`] = '';
                            });
                        });
                    }

                    return patch;
                };

                const titleItemLabel = String(titleRepeater.itemLabel || field.label || label('fieldCardsTitleItem', '')).trim();
                const textItemLabel = String((textsField && textsField.repeater && textsField.repeater.itemLabel) || (textsField && textsField.label) || label('fieldCardsBodyItem', '')).trim();
                const shortDescriptionPlaceholder = label('fieldShortDescription', '');
                const removeLabelText = label('confirmDelete', '');

                const syncSnapCardsNavigationTitles = () => {
                    if (!inspector) {
                        return;
                    }
                    const navList = inspector.querySelector('.pb-snap-cards-navigation-list');
                    if (!navList) {
                        return;
                    }
                    const titleInputs = navList.querySelectorAll('.pb-snap-cards-navigation-title-input');
                    titleInputs.forEach((input, index) => {
                        if (!(input instanceof HTMLInputElement)) {
                            return;
                        }
                        const nextValue = String(titleItems[index] || '').trim();
                        input.value = nextValue !== '' ? nextValue : `${titleItemLabel} ${index + 1}`;
                    });
                };

                const syncSnapContentItems = (refreshInspector, extraPatch) => {
                    normalizeSnapContentItems(titleItems.length);
                    updateSettings(block.id, Object.assign({
                        titles: serializeRepeaterValues(titleItems, delimiter),
                        texts: serializeRepeaterValues(textItems, delimiter),
                        backgrounds: serializeRepeaterValues(backgroundItems, delimiter),
                        links: serializeRepeaterValues(linkItems, delimiter),
                        ctaEnableds: serializeRepeaterValues(ctaEnabledItems, delimiter),
                        ctaLabels: serializeRepeaterValues(ctaLabelItems, delimiter),
                        targets: serializeRepeaterValues(targetItems, delimiter),
                        buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                    }, extraPatch && typeof extraPatch === 'object' ? extraPatch : {}));
                    syncSnapCardsNavigationTitles();
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };

                normalizeSnapContentItems(titleItems.length);

                const scaffold = createRepeaterCardScaffold({
                    bodyClass: 'fc-builder-card-editor-body pb-feature-grid-content-body',
                    listClass: 'fc-builder-card-editor-list pb-feature-grid-content-list',
                });
                const body = scaffold.body;
                const list = scaffold.list;

                const renderSnapCardsContent = () => {
                    list.innerHTML = '';
                    titleItems.forEach((titleValue, itemIndex) => {
                        const cardParts = createRepeaterCard({
                            cardClass: 'fc-builder-card pb-feature-grid-content-card pb-carousel-content-card',
                            gridClass: 'fc-builder-card-grid pb-carousel-content-grid',
                            removeButtonClass: 'pb-carousel-content-remove',
                            attachHead: false,
                        });
                        const card = cardParts.card;
                        const removeBtn = cardParts.removeButton;
                        removeBtn.title = removeLabelText;
                        removeBtn.setAttribute('aria-label', removeLabelText);
                        removeBtn.disabled = titleItems.length <= minItems;
                        removeBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            if (titleItems.length <= minItems) {
                                return;
                            }
                            confirmDeleteAction(
                                label('removeBlockConfirm', ''),
                                () => {
                                    titleItems.splice(itemIndex, 1);
                                    textItems.splice(itemIndex, 1);
                                    backgroundItems.splice(itemIndex, 1);
                                    linkItems.splice(itemIndex, 1);
                                    ctaEnabledItems.splice(itemIndex, 1);
                                    ctaLabelItems.splice(itemIndex, 1);
                                    targetItems.splice(itemIndex, 1);
                                    buttonAlignItems.splice(itemIndex, 1);
                                    syncSnapContentItems(true, buildSnapCardsStyleRemovalPatch(itemIndex));
                                },
                                {
                                    confirmText: label('confirmDelete', ''),
                                    itemName: `${titleItemLabel} ${itemIndex + 1}`,
                                }
                            );
                        });
                        const grid = cardParts.grid;

                        const titleFieldWrap = document.createElement('div');
                        titleFieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field';
                        const titleInput = document.createElement('input');
                        titleInput.className = 'form-input pb-carousel-content-input';
                        titleInput.type = 'text';
                        titleInput.value = String(titleValue || '');
                        titleInput.placeholder = `${titleItemLabel} ${itemIndex + 1}`;
                        titleInput.title = `${titleItemLabel} ${itemIndex + 1}`;
                        titleInput.setAttribute('aria-label', `${titleItemLabel} ${itemIndex + 1}`);
                        titleInput.addEventListener('input', () => {
                            titleItems[itemIndex] = String(titleInput.value || '');
                            syncSnapContentItems(false);
                        });
                        titleInput.addEventListener('change', () => {
                            titleItems[itemIndex] = String(titleInput.value || '');
                            syncSnapContentItems(false);
                        });
                        titleFieldWrap.appendChild(titleInput);
                        grid.appendChild(titleFieldWrap);

                        const textFieldWrap = document.createElement('div');
                        textFieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field';
                        const textInput = document.createElement('input');
                        textInput.className = 'form-input pb-snap-cards-content-input pb-carousel-content-input';
                        textInput.type = 'text';
                        textInput.value = String(textItems[itemIndex] || '');
                        textInput.placeholder = shortDescriptionPlaceholder;
                        textInput.title = `${textItemLabel} ${itemIndex + 1}`;
                        textInput.setAttribute('aria-label', `${textItemLabel} ${itemIndex + 1}`);
                        textInput.addEventListener('input', () => {
                            textItems[itemIndex] = String(textInput.value || '');
                            syncSnapContentItems(false);
                        });
                        textInput.addEventListener('change', () => {
                            textItems[itemIndex] = String(textInput.value || '');
                            syncSnapContentItems(false);
                        });
                        textFieldWrap.appendChild(textInput);
                        grid.appendChild(textFieldWrap);

                        const textAlignField = {
                            key: 'itemTextAlign',
                            label: label('fieldAlign', ''),
                            type: 'select',
                            options: ['left', 'center', 'right'],
                        };
                        const textAlignControl = createAlignIconControl(
                            textAlignField,
                            resolveTextStyleState(block.settings || {}, `itemTextStyle${itemIndex + 1}`, resolveSnapCardsBaseAlign()).align,
                            (nextValue) => {
                                updateSetting(
                                    block.id,
                                    textStyleSettingKey(`itemTextStyle${itemIndex + 1}`, TEXT_STYLE_SUFFIX.align),
                                    normalizeAlign(nextValue)
                                );
                            }
                        );
                        textAlignControl.classList.add('pb-carousel-content-align');
                        const actionsWrap = createRepeaterCardActionsRow({
                            rowClass: 'pb-carousel-content-actions-wrap',
                            controls: [textAlignControl, removeBtn],
                        });
                        grid.appendChild(actionsWrap);

                        list.appendChild(card);
                    });
                };

                const addBtn = createRepeaterAddButton(`<i class="fas fa-plus"></i> ${escapeHtml(label('add', 'Ajouter'))}`);
                addBtn.disabled = maxItems > 0 && titleItems.length >= maxItems;
                addBtn.addEventListener('click', () => {
                    if (maxItems > 0 && titleItems.length >= maxItems) {
                        return;
                    }
                    queueInspectorFocus('.pb-carousel-content-card:last-child .pb-carousel-content-input');
                    titleItems.push('');
                    textItems.push('');
                    backgroundItems.push('');
                    linkItems.push('');
                    ctaEnabledItems.push('on');
                    ctaLabelItems.push('');
                    targetItems.push('_self');
                    buttonAlignItems.push(resolveSnapCardsBaseAlign());
                    syncSnapContentItems(true);
                });

                renderSnapCardsContent();
                body.appendChild(addBtn);
                wrap.appendChild(body);
            } else if (useSnapCardsAdvancedCards) {
                labelEl.remove();
                wrap.className = 'pb-feature-grid-advanced-editor pb-carousel-advanced-editor';
                const titleField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'titles')
                    : null;
                const titleRepeater = titleField && titleField.repeater ? titleField.repeater : {};
                const itemLabel = String(titleRepeater.itemLabel || label('fieldLabel', 'Titre')).trim();
                const titleStyleLabel = String((field && field.label) || label('fieldLabel', 'Titre')).trim();
                let titleItems = parseRepeaterValues(block.settings.titles || '', '\n')
                    .map((item) => String(item || '').trim());
                if (titleItems.length < 1) {
                    titleItems = [''];
                }
                const extractPreviewText = (rawValue) => {
                    const temp = document.createElement('div');
                    temp.innerHTML = String(rawValue || '').trim();
                    return String(temp.textContent || temp.innerText || '').replace(/\s+/g, ' ').trim();
                };
                const cardsList = document.createElement('div');
                cardsList.className = 'fc-builder-advanced-list pb-feature-grid-advanced-list pb-carousel-advanced-list';

                titleItems.forEach((titleValue, itemIndex) => {
                    const cardParts = createAdvancedTextStyleCard({
                        cardClass: 'fc-builder-advanced-card pb-feature-grid-advanced-card pb-carousel-advanced-card',
                        titleClass: 'fc-builder-advanced-card-title pb-feature-grid-advanced-card-title',
                        bodyClass: 'fc-builder-advanced-card-body pb-feature-grid-advanced-card-body',
                        title: `${itemLabel} ${itemIndex + 1}`,
                        fieldKey: `itemtitletextstyle${itemIndex + 1}`,
                    });
                    const itemField = cardParts.card;
                    const itemBody = cardParts.body;

                    itemBody.appendChild(createAdvancedTextStylePanel(titleStyleLabel, createTextStyleControl(block, {
                        key: `itemTitleTextStyle_${itemIndex}`,
                        type: 'text_style',
                        stylePrefix: `itemTitleStyle${itemIndex + 1}`,
                        previewText: extractPreviewText(titleValue) || label('textStylePreviewSample', 'Preview text'),
                        fallbackAlign: 'left',
                    }, (settingKey, nextValue) => {
                        updateSetting(block.id, settingKey, nextValue);
                    }), {
                        panelClass: 'fc-builder-advanced-panel pb-feature-grid-advanced-panel',
                        labelClass: 'fc-builder-advanced-panel-label pb-feature-grid-advanced-panel-label',
                    }));
                    cardsList.appendChild(itemField);
                });

                wrap.appendChild(cardsList);
            } else if (useFeatureGridContentCards) {
                wrap.classList.add('is-wide', 'pb-feature-grid-content-editor', 'pb-carousel-content-editor');
                labelEl.remove();
                const textsField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'texts')
                    : null;
                const titleRepeater = field.repeater || {};
                const textRepeater = textsField && textsField.repeater ? textsField.repeater : {};
                const delimiter = '\n';
                const minItems = Math.max(
                    1,
                    Number(titleRepeater.min || 0),
                    Number(textRepeater.min || 0)
                );
                const maxItems = Math.max(
                    0,
                    Number(titleRepeater.max || 0),
                    Number(textRepeater.max || 0)
                );
                let titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                let textItems = parseFeatureGridTextValues(block.settings.texts || '');
                let iconItems = parseRepeaterValues(block.settings.icons || '', delimiter);
                let iconEnabledItems = parseRepeaterValues(block.settings.iconEnableds || '', delimiter);
                let iconAlignItems = parseRepeaterValues(block.settings.iconAligns || '', delimiter);
                let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                let buttonEnabledItems = parseRepeaterValues(block.settings.buttonEnableds || '', delimiter);
                let buttonLabelItems = parseRepeaterValues(block.settings.buttonLabels || '', delimiter);
                let buttonTargetItems = parseRepeaterValues(block.settings.buttonTargets || '', delimiter);
                let buttonVariantItems = parseRepeaterValues(block.settings.buttonVariants || '', delimiter);
                let buttonAlignItems = parseRepeaterValues(block.settings.buttonAligns || '', delimiter);
                const resolveFeatureGridBaseAlign = () => normalizeAlign(String((block.settings && block.settings.align) || 'left'));
                const normalizeFeatureGridShortText = (nextValue) => {
                    const source = String(nextValue || '');
                    if (source === '') {
                        return '';
                    }
                    const plainSource = source.replace(/<br\s*\/?>/gi, ' ');
                    const probe = document.createElement('div');
                    probe.innerHTML = plainSource;
                    return String(probe.textContent || probe.innerText || '')
                        .replace(/\s+/g, ' ')
                        .trim();
                };

                const normalizeFeatureGridContentItems = (preserveLength) => {
                    const nextSettings = {
                        titles: serializeRepeaterValues(titleItems, delimiter),
                        texts: serializeFeatureGridTextValues(textItems),
                        icons: serializeRepeaterValues(iconItems, delimiter),
                        iconEnableds: serializeRepeaterValues(iconEnabledItems, delimiter),
                        iconAligns: serializeRepeaterValues(iconAlignItems, delimiter),
                        links: serializeRepeaterValues(linkItems, delimiter),
                        buttonLabel: String(block.settings.buttonLabel || ''),
                        buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                        buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                        buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                        buttonVariants: serializeRepeaterValues(buttonVariantItems, delimiter),
                        buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                    };
                    normalizeWidgetLinkedRepeaters('feature_grid', nextSettings, {
                        compact: true,
                        minLength: Math.max(0, Number(preserveLength || 0)),
                    });
                    titleItems = parseRepeaterValues(nextSettings.titles || '', delimiter);
                    textItems = parseFeatureGridTextValues(nextSettings.texts || '');
                    iconItems = parseRepeaterValues(nextSettings.icons || '', delimiter);
                    iconEnabledItems = parseRepeaterValues(nextSettings.iconEnableds || '', delimiter);
                    iconAlignItems = parseRepeaterValues(nextSettings.iconAligns || '', delimiter);
                    linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                    buttonEnabledItems = parseRepeaterValues(nextSettings.buttonEnableds || '', delimiter);
                    buttonLabelItems = parseRepeaterValues(nextSettings.buttonLabels || '', delimiter);
                    buttonTargetItems = parseRepeaterValues(nextSettings.buttonTargets || '', delimiter);
                    buttonVariantItems = parseRepeaterValues(nextSettings.buttonVariants || '', delimiter);
                    buttonAlignItems = parseRepeaterValues(nextSettings.buttonAligns || '', delimiter);
                    while (titleItems.length < minItems) {
                        titleItems.push('');
                    }
                    while (textItems.length < minItems) {
                        textItems.push('');
                    }
                    while (iconItems.length < minItems) {
                        iconItems.push('');
                    }
                    while (iconEnabledItems.length < minItems) {
                        iconEnabledItems.push('on');
                    }
                    while (iconAlignItems.length < minItems) {
                        iconAlignItems.push(resolveFeatureGridBaseAlign());
                    }
                    while (linkItems.length < minItems) {
                        linkItems.push('');
                    }
                    while (buttonLabelItems.length < minItems) {
                        buttonLabelItems.push('');
                    }
                    while (buttonTargetItems.length < minItems) {
                        buttonTargetItems.push('_self');
                    }
                    while (buttonVariantItems.length < minItems) {
                        buttonVariantItems.push('ghost');
                    }
                    while (buttonAlignItems.length < minItems) {
                        buttonAlignItems.push('left');
                    }
                    while (buttonEnabledItems.length < minItems) {
                        buttonEnabledItems.push('off');
                    }
                    if (maxItems > 0) {
                        titleItems = titleItems.slice(0, maxItems);
                        textItems = textItems.slice(0, maxItems);
                        iconItems = iconItems.slice(0, maxItems);
                        iconEnabledItems = iconEnabledItems.slice(0, maxItems);
                        iconAlignItems = iconAlignItems.slice(0, maxItems);
                        linkItems = linkItems.slice(0, maxItems);
                        buttonEnabledItems = buttonEnabledItems.slice(0, maxItems);
                        buttonLabelItems = buttonLabelItems.slice(0, maxItems);
                        buttonTargetItems = buttonTargetItems.slice(0, maxItems);
                        buttonVariantItems = buttonVariantItems.slice(0, maxItems);
                        buttonAlignItems = buttonAlignItems.slice(0, maxItems);
                    }
                    while (textItems.length < titleItems.length) {
                        textItems.push('');
                    }
                    while (titleItems.length < textItems.length) {
                        titleItems.push('');
                    }
                    while (iconItems.length < titleItems.length) {
                        iconItems.push('');
                    }
                    while (iconEnabledItems.length < titleItems.length) {
                        iconEnabledItems.push('on');
                    }
                    while (linkItems.length < titleItems.length) {
                        linkItems.push('');
                    }
                    while (iconAlignItems.length < titleItems.length) {
                        iconAlignItems.push(resolveFeatureGridBaseAlign());
                    }
                    while (buttonLabelItems.length < titleItems.length) {
                        buttonLabelItems.push('');
                    }
                    while (buttonTargetItems.length < titleItems.length) {
                        buttonTargetItems.push('_self');
                    }
                    while (buttonVariantItems.length < titleItems.length) {
                        buttonVariantItems.push('ghost');
                    }
                    while (buttonAlignItems.length < titleItems.length) {
                        buttonAlignItems.push('left');
                    }
                    while (buttonEnabledItems.length < titleItems.length) {
                        buttonEnabledItems.push('off');
                    }
                    while (titleItems.length < iconItems.length) {
                        titleItems.push('');
                        textItems.push('');
                        iconEnabledItems.push('on');
                        iconAlignItems.push(resolveFeatureGridBaseAlign());
                        linkItems.push('');
                        buttonEnabledItems.push('off');
                        buttonLabelItems.push('');
                        buttonTargetItems.push('_self');
                        buttonVariantItems.push('ghost');
                        buttonAlignItems.push('left');
                    }
                    while (titleItems.length < linkItems.length) {
                        titleItems.push('');
                        textItems.push('');
                        iconItems.push('');
                        iconEnabledItems.push('on');
                        iconAlignItems.push(resolveFeatureGridBaseAlign());
                        buttonEnabledItems.push('off');
                        buttonLabelItems.push('');
                        buttonTargetItems.push('_self');
                        buttonVariantItems.push('ghost');
                        buttonAlignItems.push('left');
                    }
                    while (titleItems.length < buttonEnabledItems.length) {
                        titleItems.push('');
                        textItems.push('');
                        iconItems.push('');
                        iconEnabledItems.push('on');
                        iconAlignItems.push(resolveFeatureGridBaseAlign());
                        linkItems.push('');
                        buttonLabelItems.push('');
                        buttonTargetItems.push('_self');
                        buttonVariantItems.push('ghost');
                        buttonAlignItems.push('left');
                    }
                    while (titleItems.length < buttonLabelItems.length) {
                        titleItems.push('');
                        textItems.push('');
                        iconItems.push('');
                        iconEnabledItems.push('on');
                        iconAlignItems.push(resolveFeatureGridBaseAlign());
                        linkItems.push('');
                        buttonEnabledItems.push('off');
                        buttonVariantItems.push('ghost');
                        buttonAlignItems.push('left');
                    }
                    while (titleItems.length < buttonTargetItems.length) {
                        titleItems.push('');
                        textItems.push('');
                        iconItems.push('');
                        iconEnabledItems.push('on');
                        iconAlignItems.push(resolveFeatureGridBaseAlign());
                        linkItems.push('');
                        buttonEnabledItems.push('off');
                        buttonLabelItems.push('');
                        buttonVariantItems.push('ghost');
                        buttonAlignItems.push('left');
                    }
                    while (titleItems.length < buttonVariantItems.length) {
                        titleItems.push('');
                        textItems.push('');
                        iconItems.push('');
                        iconEnabledItems.push('on');
                        iconAlignItems.push(resolveFeatureGridBaseAlign());
                        linkItems.push('');
                        buttonEnabledItems.push('off');
                        buttonLabelItems.push('');
                        buttonTargetItems.push('_self');
                        buttonAlignItems.push('left');
                    }
                    while (titleItems.length < buttonAlignItems.length) {
                        titleItems.push('');
                        textItems.push('');
                        iconItems.push('');
                        iconEnabledItems.push('on');
                        iconAlignItems.push(resolveFeatureGridBaseAlign());
                        linkItems.push('');
                        buttonEnabledItems.push('off');
                        buttonLabelItems.push('');
                        buttonTargetItems.push('_self');
                        buttonVariantItems.push('ghost');
                    }
                };

                const featureGridStylePrefixes = ['itemTitleStyle', 'itemTextStyle'];
                const featureGridStyleSuffixes = ['Align', 'Font', 'Size', 'Bold', 'Italic', 'Underline', 'Color', 'List', 'Icon', 'IconPosition'];
                const buildFeatureGridStyleRemovalPatch = (removedIndex) => {
                    if (removedIndex < 0) {
                        return {};
                    }

                    const sourceSettings = block.settings && typeof block.settings === 'object'
                        ? block.settings
                        : {};
                    const patch = {};
                    const nextLength = titleItems.length;

                    for (let position = removedIndex + 1; position <= nextLength; position += 1) {
                        const sourcePosition = position + 1;
                        featureGridStylePrefixes.forEach((stylePrefix) => {
                            featureGridStyleSuffixes.forEach((suffix) => {
                                const sourceKey = `${stylePrefix}${sourcePosition}${suffix}`;
                                const targetKey = `${stylePrefix}${position}${suffix}`;
                                patch[targetKey] = Object.prototype.hasOwnProperty.call(sourceSettings, sourceKey)
                                    ? sourceSettings[sourceKey]
                                    : '';
                            });
                        });
                    }

                    for (let position = nextLength + 1; position <= 8; position += 1) {
                        featureGridStylePrefixes.forEach((stylePrefix) => {
                            featureGridStyleSuffixes.forEach((suffix) => {
                                patch[`${stylePrefix}${position}${suffix}`] = '';
                            });
                        });
                    }

                    return patch;
                };

                const syncFeatureGridContentItems = (refreshInspector, extraPatch) => {
                    normalizeFeatureGridContentItems(titleItems.length);
                    updateSettings(block.id, Object.assign({
                        titles: serializeRepeaterValues(titleItems, delimiter),
                        texts: serializeFeatureGridTextValues(textItems),
                        icons: serializeRepeaterValues(iconItems, delimiter),
                        iconEnableds: serializeRepeaterValues(iconEnabledItems, delimiter),
                        iconAligns: serializeRepeaterValues(iconAlignItems, delimiter),
                        links: serializeRepeaterValues(linkItems, delimiter),
                        buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                        buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                        buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                        buttonVariants: serializeRepeaterValues(buttonVariantItems, delimiter),
                        buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                    }, extraPatch && typeof extraPatch === 'object' ? extraPatch : {}));
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };

                normalizeFeatureGridContentItems(titleItems.length);

                const scaffold = createRepeaterCardScaffold({
                    bodyClass: 'fc-builder-card-editor-body pb-feature-grid-content-body',
                    listClass: 'fc-builder-card-editor-list pb-feature-grid-content-list',
                });
                const body = scaffold.body;
                const list = scaffold.list;

                const titleItemLabel = String(titleRepeater.itemLabel || field.label || label('fieldLabel', 'Titre')).trim();
                const textItemLabel = String((textsField && textsField.repeater && textsField.repeater.itemLabel) || (textsField && textsField.label) || label('fieldDescription', 'Description')).trim();
                const textPlaceholder = label('fieldShortDescription', 'Description courte');
                const removeLabelText = label('confirmDelete', 'Supprimer');

                const renderFeatureGridContentCards = () => {
                    list.innerHTML = '';
                    titleItems.forEach((titleValue, itemIndex) => {
                        const cardParts = createRepeaterCard({
                            cardClass: 'fc-builder-card pb-feature-grid-content-card pb-carousel-content-card',
                            gridClass: 'fc-builder-card-grid pb-carousel-content-grid',
                            removeButtonClass: 'pb-carousel-content-remove',
                            attachHead: false,
                        });
                        const card = cardParts.card;
                        const removeBtn = cardParts.removeButton;
                        removeBtn.title = `${removeLabelText} ${titleItemLabel} ${itemIndex + 1}`;
                        removeBtn.setAttribute('aria-label', `${removeLabelText} ${titleItemLabel} ${itemIndex + 1}`);
                        removeBtn.disabled = titleItems.length <= minItems;
                        removeBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            if (titleItems.length <= minItems) {
                                return;
                            }
                            confirmDeleteAction(
                                label('removeBlockConfirm', 'Supprimer ce bloc ?'),
                                () => {
                                    titleItems.splice(itemIndex, 1);
                                    textItems.splice(itemIndex, 1);
                                    iconItems.splice(itemIndex, 1);
                                    iconEnabledItems.splice(itemIndex, 1);
                                    iconAlignItems.splice(itemIndex, 1);
                                    linkItems.splice(itemIndex, 1);
                                    buttonEnabledItems.splice(itemIndex, 1);
                                    buttonLabelItems.splice(itemIndex, 1);
                                    buttonTargetItems.splice(itemIndex, 1);
                                    buttonVariantItems.splice(itemIndex, 1);
                                    buttonAlignItems.splice(itemIndex, 1);
                                    syncFeatureGridContentItems(true, buildFeatureGridStyleRemovalPatch(itemIndex));
                                },
                                {
                                    confirmText: label('confirmDelete', 'Supprimer'),
                                    itemName: `${titleItemLabel} ${itemIndex + 1}`,
                                }
                            );
                        });
                        const grid = cardParts.grid;

                        const titleFieldWrap = document.createElement('div');
                        titleFieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field';
                        const titleInput = document.createElement('input');
                        titleInput.className = 'form-input pb-carousel-content-input';
                        titleInput.type = 'text';
                        titleInput.value = String(titleValue || '');
                        titleInput.placeholder = `${titleItemLabel} ${itemIndex + 1}`;
                        titleInput.title = `${titleItemLabel} ${itemIndex + 1}`;
                        titleInput.setAttribute('aria-label', `${titleItemLabel} ${itemIndex + 1}`);
                        titleInput.addEventListener('input', () => {
                            titleItems[itemIndex] = String(titleInput.value || '');
                            syncFeatureGridContentItems(false);
                        });
                        titleInput.addEventListener('change', () => {
                            titleItems[itemIndex] = String(titleInput.value || '');
                            syncFeatureGridContentItems(true);
                        });
                        titleFieldWrap.appendChild(titleInput);
                        grid.appendChild(titleFieldWrap);

                        const textFieldWrap = document.createElement('div');
                        textFieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field';
                        const textInput = document.createElement('input');
                        textInput.className = 'form-input pb-snap-cards-content-input pb-carousel-content-input';
                        textInput.type = 'text';
                        textInput.value = normalizeFeatureGridShortText(textItems[itemIndex] || '');
                        textInput.placeholder = textPlaceholder;
                        textInput.title = `${textItemLabel} ${itemIndex + 1}`;
                        textInput.setAttribute('aria-label', `${textItemLabel} ${itemIndex + 1}`);
                        textInput.addEventListener('input', () => {
                            textItems[itemIndex] = String(textInput.value || '');
                            syncFeatureGridContentItems(false);
                        });
                        textInput.addEventListener('change', () => {
                            textItems[itemIndex] = String(textInput.value || '');
                            syncFeatureGridContentItems(true);
                        });
                        textFieldWrap.appendChild(textInput);
                        grid.appendChild(textFieldWrap);

                        const textAlignField = {
                            key: 'itemTextAlign',
                            label: label('fieldAlign', 'Alignement'),
                            type: 'select',
                            options: ['left', 'center', 'right'],
                        };
                        const textAlignControl = createAlignIconControl(
                            textAlignField,
                            resolveTextStyleState(block.settings || {}, `itemTextStyle${itemIndex + 1}`, resolveFeatureGridBaseAlign()).align,
                            (nextValue) => {
                                updateSetting(
                                    block.id,
                                    textStyleSettingKey(`itemTextStyle${itemIndex + 1}`, TEXT_STYLE_SUFFIX.align),
                                    normalizeAlign(nextValue)
                                );
                            }
                        );
                        textAlignControl.classList.add('pb-carousel-content-align');
                        const actionsWrap = createRepeaterCardActionsRow({
                            rowClass: 'pb-carousel-content-actions-wrap',
                            controls: [textAlignControl, removeBtn],
                        });
                        grid.appendChild(actionsWrap);

                        list.appendChild(card);
                    });
                };

                const addBtn = createRepeaterAddButton(`<i class="fas fa-plus"></i> ${escapeHtml(label('add', 'Ajouter'))}`);
                addBtn.disabled = maxItems > 0 && titleItems.length >= maxItems;
                addBtn.addEventListener('click', () => {
                    if (maxItems > 0 && titleItems.length >= maxItems) {
                        return;
                    }
                    queueInspectorFocus('.pb-carousel-content-card:last-child .pb-carousel-content-input');
                    titleItems.push('');
                    textItems.push('');
                    iconItems.push('');
                    iconEnabledItems.push('on');
                    iconAlignItems.push(resolveFeatureGridBaseAlign());
                    linkItems.push('');
                    buttonEnabledItems.push('off');
                    buttonLabelItems.push('');
                    buttonTargetItems.push('_self');
                    buttonVariantItems.push('ghost');
                    buttonAlignItems.push(resolveFeatureGridBaseAlign());
                    syncFeatureGridContentItems(true);
                });

                renderFeatureGridContentCards();
                body.appendChild(addBtn);
                wrap.appendChild(body);
            } else if (usePricingPlansContentEditor) {
                wrap.classList.add('is-wide', 'pb-feature-grid-content-editor', 'pb-carousel-content-editor');
                labelEl.remove();
                const findPricingField = (keyName) => Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === keyName)
                    : null;
                const planPriceField = findPricingField('planprices');
                const planYearlyPriceField = findPricingField('planyearlyprices');
                const planDescriptionField = findPricingField('plandescriptions');
                const planFeaturesField = findPricingField('planfeatures');
                const planBadgeField = findPricingField('planbadges');
                const titleRepeater = field.repeater || {};
                const priceRepeater = planPriceField && planPriceField.repeater ? planPriceField.repeater : {};
                const minItems = Math.max(1, Number(titleRepeater.min || 0), Number(priceRepeater.min || 0));
                const maxItems = Math.max(0, Number(titleRepeater.max || 0), Number(priceRepeater.max || 0));
                const pricingState = createPricingPlansInspectorState(block.settings);

                const normalizePricingContentItems = (preserveLength) => {
                    normalizePricingPlansInspectorState(pricingState, block.settings, {
                        compact: true,
                        minLength: Math.max(0, Number(preserveLength || 0)),
                        minItems,
                        maxItems,
                    });
                };

                const syncPricingContentItems = (refreshInspector) => {
                    normalizePricingContentItems(pricingState.planNames.length);
                    updateSettings(block.id, buildPricingPlansInspectorPatch(pricingState));
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };

                normalizePricingContentItems(pricingState.planNames.length);

                const scaffold = createRepeaterCardScaffold({
                    bodyClass: 'fc-builder-card-editor-body pb-feature-grid-content-body',
                    listClass: 'fc-builder-card-editor-list pb-feature-grid-content-list',
                });
                const body = scaffold.body;
                const list = scaffold.list;

                const titleItemLabel = String(titleRepeater.itemLabel || field.label || label('fieldLabel', 'Plan')).trim();
                const priceItemLabel = String(((planPriceField && planPriceField.repeater && planPriceField.repeater.itemLabel) || (planPriceField && planPriceField.label) || label('fieldPrice', 'Prix'))).trim();
                const yearlyPriceItemLabel = String(((planYearlyPriceField && planYearlyPriceField.repeater && planYearlyPriceField.repeater.itemLabel) || (planYearlyPriceField && planYearlyPriceField.label) || label('fieldPrice', 'Prix annuel'))).trim();
                const descriptionItemLabel = String(((planDescriptionField && planDescriptionField.repeater && planDescriptionField.repeater.itemLabel) || (planDescriptionField && planDescriptionField.label) || label('fieldDescription', 'Description'))).trim();
                const featuresItemLabel = String(((planFeaturesField && planFeaturesField.repeater && planFeaturesField.repeater.itemLabel) || (planFeaturesField && planFeaturesField.label) || label('fieldList', 'Fonctionnalités'))).trim();
                const badgeItemLabel = String(((planBadgeField && planBadgeField.repeater && planBadgeField.repeater.itemLabel) || (planBadgeField && planBadgeField.label) || label('fieldBadge', 'Badge'))).trim();
                const removeLabelText = label('confirmDelete', 'Supprimer');

                const createPricingInputField = (value, placeholder, ariaLabel, onInputChange, extraClass) => {
                    const fieldWrap = document.createElement('div');
                    fieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field pb-pricing-plans-content-field';
                    if (extraClass) {
                        fieldWrap.classList.add(extraClass);
                    }
                    const input = document.createElement('input');
                    input.className = 'form-input pb-carousel-content-input';
                    input.type = 'text';
                    input.value = String(value || '');
                    input.placeholder = placeholder;
                    input.title = ariaLabel;
                    input.setAttribute('aria-label', ariaLabel);
                    input.addEventListener('input', () => onInputChange(String(input.value || ''), false));
                    input.addEventListener('change', () => onInputChange(String(input.value || ''), true));
                    fieldWrap.appendChild(input);
                    return fieldWrap;
                };

                const createPricingTextareaField = (value, placeholder, ariaLabel, rows, onInputChange, extraClass) => {
                    const fieldWrap = document.createElement('div');
                    fieldWrap.className = 'pb-feature-grid-content-field pb-carousel-content-field pb-pricing-plans-content-field';
                    if (extraClass) {
                        fieldWrap.classList.add(extraClass);
                    }
                    const textarea = document.createElement('textarea');
                    textarea.className = 'form-input pb-pricing-plans-content-textarea';
                    textarea.rows = rows;
                    textarea.setAttribute('data-no-editor', '1');
                    textarea.value = String(value || '');
                    textarea.placeholder = placeholder;
                    textarea.title = ariaLabel;
                    textarea.setAttribute('aria-label', ariaLabel);
                    textarea.addEventListener('input', () => onInputChange(String(textarea.value || ''), false));
                    textarea.addEventListener('change', () => onInputChange(String(textarea.value || ''), true));
                    fieldWrap.appendChild(textarea);
                    return fieldWrap;
                };

                const renderPricingPlansContentCards = () => {
                    list.innerHTML = '';
                    pricingState.planNames.forEach((planName, itemIndex) => {
                        const cardParts = createRepeaterCard({
                            cardClass: 'fc-builder-card pb-feature-grid-content-card pb-carousel-content-card pb-pricing-plans-content-card',
                            gridClass: 'fc-builder-card-grid pb-carousel-content-grid pb-pricing-plans-content-grid',
                            removeButtonClass: 'pb-carousel-content-remove',
                            attachHead: false,
                        });
                        const card = cardParts.card;
                        const removeBtn = cardParts.removeButton;
                        removeBtn.title = `${removeLabelText} ${titleItemLabel} ${itemIndex + 1}`;
                        removeBtn.setAttribute('aria-label', `${removeLabelText} ${titleItemLabel} ${itemIndex + 1}`);
                        removeBtn.disabled = pricingState.planNames.length <= minItems;
                        removeBtn.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            if (pricingState.planNames.length <= minItems) {
                                return;
                            }
                            confirmDeleteAction(
                                label('removeBlockConfirm', 'Supprimer ce bloc ?'),
                                () => {
                                    pricingState.planNames.splice(itemIndex, 1);
                                    pricingState.planPrices.splice(itemIndex, 1);
                                    pricingState.planYearlyPrices.splice(itemIndex, 1);
                                    pricingState.planDescriptions.splice(itemIndex, 1);
                                    pricingState.planFeatures.splice(itemIndex, 1);
                                    pricingState.planBadges.splice(itemIndex, 1);
                                    pricingState.planIcons.splice(itemIndex, 1);
                                    pricingState.featuredPlans.splice(itemIndex, 1);
                                    pricingState.ctaEnableds.splice(itemIndex, 1);
                                    pricingState.ctaLabels.splice(itemIndex, 1);
                                    pricingState.ctaLinks.splice(itemIndex, 1);
                                    pricingState.ctaTargets.splice(itemIndex, 1);
                                    pricingState.ctaVariants.splice(itemIndex, 1);
                                    pricingState.ctaAligns.splice(itemIndex, 1);
                                    syncPricingContentItems(true);
                                },
                                {
                                    confirmText: label('confirmDelete', 'Supprimer'),
                                    itemName: `${titleItemLabel} ${itemIndex + 1}`,
                                }
                            );
                        });
                        const grid = cardParts.grid;

                        grid.appendChild(createPricingInputField(
                            planName,
                            `${titleItemLabel} ${itemIndex + 1}`,
                            `${titleItemLabel} ${itemIndex + 1}`,
                            (nextValue, refreshInspector) => {
                                pricingState.planNames[itemIndex] = nextValue;
                                syncPricingContentItems(refreshInspector);
                            },
                            'pb-pricing-plans-content-field--name'
                        ));

                        grid.appendChild(createPricingInputField(
                            pricingState.planPrices[itemIndex] || '',
                            priceItemLabel,
                            `${priceItemLabel} ${itemIndex + 1}`,
                            (nextValue, refreshInspector) => {
                                pricingState.planPrices[itemIndex] = nextValue;
                                syncPricingContentItems(refreshInspector);
                            },
                            'pb-pricing-plans-content-field--price'
                        ));

                        grid.appendChild(createPricingInputField(
                            pricingState.planYearlyPrices[itemIndex] || '',
                            yearlyPriceItemLabel,
                            `${yearlyPriceItemLabel} ${itemIndex + 1}`,
                            (nextValue, refreshInspector) => {
                                pricingState.planYearlyPrices[itemIndex] = nextValue;
                                syncPricingContentItems(refreshInspector);
                            },
                            'pb-pricing-plans-content-field--yearly-price'
                        ));

                        grid.appendChild(createPricingInputField(
                            pricingState.planBadges[itemIndex] || '',
                            badgeItemLabel,
                            `${badgeItemLabel} ${itemIndex + 1}`,
                            (nextValue, refreshInspector) => {
                                pricingState.planBadges[itemIndex] = nextValue;
                                syncPricingContentItems(refreshInspector);
                            },
                            'pb-pricing-plans-content-field--badge'
                        ));

                        grid.appendChild(createPricingTextareaField(
                            pricingState.planDescriptions[itemIndex] || '',
                            descriptionItemLabel,
                            `${descriptionItemLabel} ${itemIndex + 1}`,
                            2,
                            (nextValue, refreshInspector) => {
                                pricingState.planDescriptions[itemIndex] = nextValue;
                                syncPricingContentItems(refreshInspector);
                            },
                            'pb-pricing-plans-content-field--description'
                        ));

                        grid.appendChild(createPricingTextareaField(
                            pricingState.planFeatures[itemIndex] || '',
                            featuresItemLabel,
                            `${featuresItemLabel} ${itemIndex + 1}`,
                            4,
                            (nextValue, refreshInspector) => {
                                pricingState.planFeatures[itemIndex] = nextValue;
                                syncPricingContentItems(refreshInspector);
                            },
                            'pb-pricing-plans-content-field--features'
                        ));

                        const actionsWrap = createRepeaterCardActionsRow({
                            rowClass: 'pb-carousel-content-actions-wrap pb-pricing-plans-content-actions',
                            controls: [removeBtn],
                        });
                        grid.appendChild(actionsWrap);

                        list.appendChild(card);
                    });
                };

                const addBtn = createRepeaterAddButton(`<i class="fas fa-plus"></i> ${escapeHtml(label('add', 'Ajouter'))}`);
                addBtn.disabled = maxItems > 0 && pricingState.planNames.length >= maxItems;
                addBtn.addEventListener('click', () => {
                    if (maxItems > 0 && pricingState.planNames.length >= maxItems) {
                        return;
                    }
                    queueInspectorFocus('.pb-carousel-content-card:last-child .pb-carousel-content-input');
                    pricingState.planNames.push('');
                    pricingState.planPrices.push('');
                    pricingState.planYearlyPrices.push('');
                    pricingState.planDescriptions.push('');
                    pricingState.planFeatures.push('');
                    pricingState.planBadges.push('');
                    pricingState.planIcons.push('');
                    pricingState.featuredPlans.push('off');
                    pricingState.ctaEnableds.push('on');
                    pricingState.ctaLabels.push('');
                    pricingState.ctaLinks.push('');
                    pricingState.ctaTargets.push('_self');
                    pricingState.ctaVariants.push('ghost');
                    pricingState.ctaAligns.push(normalizeAlign(String(block.settings.align || 'left')));
                    syncPricingContentItems(true);
                });

                renderPricingPlansContentCards();
                body.appendChild(addBtn);
                wrap.appendChild(body);
            } else if (useCarouselAdvancedCards || useNwCarrouselAdvancedCards) {
                labelEl.remove();
                wrap.className = 'pb-feature-grid-advanced-editor pb-carousel-advanced-editor';
                const titleField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'titles')
                    : null;
                const titleRepeater = titleField && titleField.repeater ? titleField.repeater : {};
                const itemLabel = String(titleRepeater.itemLabel || label('fieldLabel', 'Titre')).trim();
                const titleStyleLabel = String((field && field.label) || label('fieldLabel', 'Titre')).trim();
                const advancedDelimiter = useNwCarrouselAdvancedCards ? '\n---\n' : '\n';
                let titleItems = parseRepeaterValues(block.settings.titles || '', advancedDelimiter)
                    .map((item) => String(item || '').trim());
                if (titleItems.length < 1) {
                    titleItems = [''];
                }
                const extractPreviewText = (rawValue) => {
                    const temp = document.createElement('div');
                    temp.innerHTML = String(rawValue || '').trim();
                    return String(temp.textContent || temp.innerText || '').replace(/\s+/g, ' ').trim();
                };
                const cardsList = document.createElement('div');
                cardsList.className = 'fc-builder-advanced-list pb-feature-grid-advanced-list pb-carousel-advanced-list';

                titleItems.forEach((titleValue, itemIndex) => {
                    const cardParts = createAdvancedTextStyleCard({
                        cardClass: 'fc-builder-advanced-card pb-feature-grid-advanced-card pb-carousel-advanced-card',
                        titleClass: 'fc-builder-advanced-card-title pb-feature-grid-advanced-card-title',
                        bodyClass: 'fc-builder-advanced-card-body pb-feature-grid-advanced-card-body',
                        title: `${itemLabel} ${itemIndex + 1}`,
                        fieldKey: `itemtitletextstyle${itemIndex + 1}`,
                    });
                    const itemField = cardParts.card;
                    const itemBody = cardParts.body;

                    itemBody.appendChild(createAdvancedTextStylePanel(titleStyleLabel, createTextStyleControl(block, {
                        key: `itemTitleTextStyle_${itemIndex}`,
                        type: 'text_style',
                        stylePrefix: `itemTitleStyle${itemIndex + 1}`,
                        previewText: extractPreviewText(titleValue) || label('textStylePreviewSample', 'Preview text'),
                        fallbackAlign: 'left',
                    }, (settingKey, nextValue) => {
                        updateSetting(block.id, settingKey, nextValue);
                    }), {
                        panelClass: 'fc-builder-advanced-panel pb-feature-grid-advanced-panel',
                        labelClass: 'fc-builder-advanced-panel-label pb-feature-grid-advanced-panel-label',
                    }));
                    cardsList.appendChild(itemField);
                });

                wrap.appendChild(cardsList);
            } else if (useFeatureGridAdvancedCards) {
                labelEl.remove();
                wrap.className = 'pb-feature-grid-advanced-editor pb-carousel-advanced-editor';
                const titleField = Array.isArray(def.fields)
                    ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'titles')
                    : null;
                const titleRepeater = titleField && titleField.repeater ? titleField.repeater : {};
                const itemLabelText = String(titleRepeater.itemLabel || label('fieldLabel', 'Titre')).trim();
                const titleStyleLabel = String((field && field.label) || label('fieldLabel', 'Titre')).trim();
                let titleItems = parseRepeaterValues(block.settings.titles || '', '\n')
                    .map((item) => String(item || '').trim());
                if (titleItems.length < 1) {
                    titleItems = [''];
                }
                const extractPreviewText = (rawValue) => {
                    const temp = document.createElement('div');
                    temp.innerHTML = String(rawValue || '').trim();
                    return String(temp.textContent || temp.innerText || '').replace(/\s+/g, ' ').trim();
                };
                const cardsList = document.createElement('div');
                cardsList.className = 'fc-builder-advanced-list pb-feature-grid-advanced-list pb-carousel-advanced-list';

                titleItems.forEach((titleValue, itemIndex) => {
                    const cardParts = createAdvancedTextStyleCard({
                        cardClass: 'fc-builder-advanced-card pb-feature-grid-advanced-card pb-carousel-advanced-card',
                        titleClass: 'fc-builder-advanced-card-title pb-feature-grid-advanced-card-title',
                        bodyClass: 'fc-builder-advanced-card-body pb-feature-grid-advanced-card-body',
                        title: `${itemLabelText} ${itemIndex + 1}`,
                        fieldKey: `itemtitletextstyle${itemIndex + 1}`,
                    });
                    const itemField = cardParts.card;
                    const itemBody = cardParts.body;

                    itemBody.appendChild(createAdvancedTextStylePanel(titleStyleLabel, createTextStyleControl(block, {
                        key: `itemTitleTextStyle_${itemIndex}`,
                        type: 'text_style',
                        stylePrefix: `itemTitleStyle${itemIndex + 1}`,
                        previewText: extractPreviewText(titleValue) || label('textStylePreviewSample', 'Preview text'),
                        fallbackAlign: 'left',
                    }, (settingKey, nextValue) => {
                        updateSetting(block.id, settingKey, nextValue);
                    }), {
                        panelClass: 'fc-builder-advanced-panel pb-feature-grid-advanced-panel',
                        labelClass: 'fc-builder-advanced-panel-label pb-feature-grid-advanced-panel-label',
                    }));
                    cardsList.appendChild(itemField);
                });

                wrap.appendChild(cardsList);
            } else if (isRepeaterField) {
                const useLegalLinksPicker = blockType === 'legal_section' && fieldKey === 'links';
                if (useNwCarrouselNavigationEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-carousel-navigation-editor');
                    const delimiter = '\n---\n';
                    let titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                    let descriptionItems = parseRepeaterValues(block.settings.descriptions || '', delimiter);
                    let imageItems = parseRepeaterValues(block.settings.images || '', delimiter);
                    let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    let buttonEnabledItems = parseRepeaterValues(block.settings.buttonEnableds || '', delimiter);
                    let buttonLabelItems = parseRepeaterValues(block.settings.buttonLabels || '', delimiter);
                    let buttonTargetItems = parseRepeaterValues(block.settings.buttonTargets || '', delimiter);
                    let buttonAlignItems = parseRepeaterValues(block.settings.buttonAligns || '', delimiter);

                    const normalizeNavigationItems = () => {
                        const nextSettings = {
                            titles: serializeRepeaterValues(titleItems, delimiter),
                            descriptions: serializeRepeaterValues(descriptionItems, delimiter),
                            images: serializeRepeaterValues(imageItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                            buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                            buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                            buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                        };
                        normalizeWidgetLinkedRepeaters('nw_carrousel', nextSettings, {
                            compact: false,
                            minLength: Math.max(1, titleItems.length),
                        });
                        titleItems = parseRepeaterValues(nextSettings.titles || '', delimiter);
                        descriptionItems = parseRepeaterValues(nextSettings.descriptions || '', delimiter);
                        imageItems = parseRepeaterValues(nextSettings.images || '', delimiter);
                        linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                        buttonEnabledItems = parseRepeaterValues(nextSettings.buttonEnableds || '', delimiter);
                        buttonLabelItems = parseRepeaterValues(nextSettings.buttonLabels || '', delimiter);
                        buttonTargetItems = parseRepeaterValues(nextSettings.buttonTargets || '', delimiter);
                        buttonAlignItems = parseRepeaterValues(nextSettings.buttonAligns || '', delimiter);
                    };

                    const syncNavigationItems = (refreshInspector) => {
                        normalizeNavigationItems();
                        updateSettings(block.id, {
                            buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                            buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                            buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                        });
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeNavigationItems();

                    const shell = createNavigationEditorScaffold([
                        { text: '', blank: true },
                        { text: label('fieldButtonLabel', 'Texte du bouton') },
                        { text: label('fieldUrl', 'Lien') },
                        { text: label('fieldTarget', 'Ouverture') },
                        { text: label('fieldAlign', 'Alignement') },
                    ], {
                        bodyClass: 'fc-builder-navigation-body pb-carousel-navigation-body',
                        listClass: 'fc-builder-navigation-list pb-carousel-navigation-list',
                        headRowClass: 'fc-builder-navigation-row fc-builder-navigation-head pb-carousel-navigation-row pb-carousel-navigation-head',
                        headCellClass: 'fc-builder-navigation-head-cell pb-carousel-navigation-head-cell',
                    });
                    const body = shell.body;
                    const list = shell.list;

                    const toggles = document.createElement('div');
                    toggles.className = 'pb-carousel-navigation-toggles';
                    [
                        {
                            key: 'showIndicators',
                            labelText: label('fieldCarouselShowIndicators', 'Repères'),
                            styleKey: 'indicatorStyle',
                            styleKind: 'indicator',
                            styleOptions: ['dots', 'bars', 'numbers'],
                            defaultStyle: 'dots',
                            styleLabel: label('fieldCarouselIndicatorStyle', 'Style des repères'),
                            defaultToggle: 'off',
                        },
                        {
                            key: 'showArrows',
                            labelText: label('fieldCarouselShowArrows', 'Flèches de navigation'),
                            styleKey: 'arrowStyle',
                            styleKind: 'arrow',
                            styleOptions: ['filled', 'outline', 'minimal'],
                            defaultStyle: 'filled',
                            styleLabel: label('fieldCarouselArrowStyle', 'Style des flèches'),
                            defaultToggle: 'on',
                        },
                        {
                            key: 'autoplay',
                            labelText: label('fieldCarouselAutoplay', 'Défilement automatique'),
                            defaultToggle: 'off',
                        },
                        {
                            key: 'loop',
                            labelText: label('fieldCarouselLoop', 'Recommencer en continu'),
                            defaultToggle: 'on',
                        },
                    ].forEach((toggleMeta) => {
                        const toggleWrap = document.createElement('div');
                        toggleWrap.className = 'pb-carousel-navigation-toggle';
                        const toggleLabel = document.createElement('span');
                        toggleLabel.className = 'pb-carousel-navigation-toggle-label';
                        toggleLabel.textContent = toggleMeta.labelText;
                        toggleWrap.appendChild(toggleLabel);
                        const toggleBody = document.createElement('div');
                        toggleBody.className = 'pb-carousel-navigation-toggle-body';
                        if (toggleMeta.styleKey) {
                            toggleBody.classList.add('has-style-choice');
                        }
                        const toggleControl = createToggleSwitchControl({
                            key: toggleMeta.key,
                            label: '',
                            type: 'checkbox',
                        }, block.settings[toggleMeta.key] !== undefined ? block.settings[toggleMeta.key] : toggleMeta.defaultToggle);
                        toggleControl.element.classList.add('pb-carousel-navigation-toggle-switch');
                        const toggleText = toggleControl.element.querySelector('.pb-switch-text');
                        if (toggleText) {
                            toggleText.remove();
                        }
                        toggleControl.input.addEventListener('change', () => {
                            updateSetting(
                                block.id,
                                toggleMeta.key,
                                normalizeToggleSettingValue(toggleControl.input.checked ? 'on' : 'off', toggleMeta.defaultToggle)
                            );
                        });
                        toggleBody.appendChild(toggleControl.element);
                        if (toggleMeta.styleKey) {
                            const styleField = {
                                key: toggleMeta.styleKey,
                                label: toggleMeta.styleLabel,
                                options: toggleMeta.styleOptions,
                                renderOption(optionValue) {
                                    return createCarouselStylePreviewNode(toggleMeta.styleKind, optionValue);
                                },
                            };
                            const activeValue = toggleMeta.styleKind === 'indicator'
                                ? normalizeCarouselIndicatorStyle(block.settings[toggleMeta.styleKey] || toggleMeta.defaultStyle)
                                : normalizeCarouselArrowStyle(block.settings[toggleMeta.styleKey] || toggleMeta.defaultStyle);
                            const styleControl = createLayoutChoiceControl(styleField, activeValue, (nextValue) => {
                                const normalizedValue = toggleMeta.styleKind === 'indicator'
                                    ? normalizeCarouselIndicatorStyle(nextValue)
                                    : normalizeCarouselArrowStyle(nextValue);
                                updateSetting(block.id, toggleMeta.styleKey, normalizedValue);
                            });
                            styleControl.classList.add('pb-carousel-navigation-style-choice');
                            toggleBody.appendChild(styleControl);
                        }
                        toggleWrap.appendChild(toggleBody);
                        toggles.appendChild(toggleWrap);
                    });
                    body.appendChild(toggles);

                    for (let itemIndex = 0; itemIndex < titleItems.length; itemIndex += 1) {
                        const row = createNavigationEditorRow('pb-carousel-navigation-row');

                        const toggleControl = createNavigationSwitchCell({
                            key: 'buttonEnabled',
                            label: '',
                            type: 'checkbox',
                        }, String(buttonEnabledItems[itemIndex] || 'on'), {
                            cellClass: 'pb-carousel-navigation-cell pb-feature-grid-navigation-switch',
                        });
                        toggleControl.input.addEventListener('change', () => {
                            buttonEnabledItems[itemIndex] = normalizeToggleSettingValue(toggleControl.input.checked ? 'on' : 'off', 'on');
                            syncNavigationItems(true);
                        });
                        row.appendChild(toggleControl.element);

                        const labelInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell',
                            type: 'text',
                            value: String(buttonLabelItems[itemIndex] || ''),
                            placeholder: label('defaultCallToAction', 'En savoir plus'),
                        });
                        labelInput.addEventListener('input', () => {
                            buttonLabelItems[itemIndex] = String(labelInput.value || '');
                            syncNavigationItems(false);
                        });
                        labelInput.addEventListener('change', () => {
                            buttonLabelItems[itemIndex] = String(labelInput.value || '');
                            syncNavigationItems(true);
                        });
                        row.appendChild(labelInput);

                        const linkInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell',
                            type: 'text',
                            value: String(linkItems[itemIndex] || ''),
                            placeholder: label('fieldCarouselLinkPlaceholder', '/page/slug'),
                        });
                        linkInput.addEventListener('input', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(false);
                        });
                        linkInput.addEventListener('change', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(true);
                        });
                        row.appendChild(linkInput);

                        const targetControl = createTargetChoiceControl(
                            normalizeLinkTarget(String(buttonTargetItems[itemIndex] || '_self'), String(linkItems[itemIndex] || '')),
                            (nextValue) => {
                                buttonTargetItems[itemIndex] = normalizeLinkTarget(String(nextValue || ''), String(linkItems[itemIndex] || ''));
                                syncNavigationItems(true);
                            },
                            ['pb-carousel-navigation-cell', 'pb-carousel-navigation-target']
                        );
                        row.appendChild(targetControl);

                        const alignValue = normalizeAlign(String(buttonAlignItems[itemIndex] || 'left'));
                        const alignField = {
                            key: 'buttonAlign',
                            label: label('fieldAlign', ''),
                            type: 'select',
                            options: ['left', 'center', 'right'],
                        };
                        const alignControl = createAlignIconControl(
                            alignField,
                            alignValue,
                            (nextValue, refreshInspector) => {
                                buttonAlignItems[itemIndex] = normalizeAlign(nextValue);
                                syncNavigationItems(refreshInspector !== false);
                            }
                        );
                        alignControl.classList.add('pb-carousel-navigation-cell', 'pb-carousel-navigation-align', 'pb-feature-grid-navigation-align');
                        row.appendChild(alignControl);

                        shell.appendItem(row, 'fc-builder-navigation-item pb-carousel-navigation-item');
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useCarouselNavigationEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-carousel-navigation-editor');
                    const delimiter = '\n';
                    let titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                    let textItems = parseRepeaterValues(block.settings.texts || '', delimiter);
                    let imageItems = parseRepeaterValues(block.settings.images || '', delimiter);
                    let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    let buttonEnabledItems = parseRepeaterValues(block.settings.buttonEnableds || '', delimiter);
                    let buttonLabelItems = parseRepeaterValues(block.settings.buttonLabels || '', delimiter);
                    let buttonTargetItems = parseRepeaterValues(block.settings.buttonTargets || '', delimiter);
                    let buttonAlignItems = parseRepeaterValues(block.settings.buttonAligns || '', delimiter);

                    const normalizeNavigationItems = () => {
                        const nextSettings = {
                            titles: serializeRepeaterValues(titleItems, delimiter),
                            texts: serializeRepeaterValues(textItems, delimiter),
                            images: serializeRepeaterValues(imageItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                            buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                            buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                            buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                            buttonLabel: String(block.settings.buttonLabel || ''),
                            target: String(block.settings.target || '_self'),
                        };
                        normalizeWidgetLinkedRepeaters('carousel', nextSettings, { compact: false, minLength: Math.max(0, titleItems.length) });
                        titleItems = parseRepeaterValues(nextSettings.titles || '', delimiter);
                        textItems = parseRepeaterValues(nextSettings.texts || '', delimiter);
                        imageItems = parseRepeaterValues(nextSettings.images || '', delimiter);
                        linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                        buttonEnabledItems = parseRepeaterValues(nextSettings.buttonEnableds || '', delimiter);
                        buttonLabelItems = parseRepeaterValues(nextSettings.buttonLabels || '', delimiter);
                        buttonTargetItems = parseRepeaterValues(nextSettings.buttonTargets || '', delimiter);
                        buttonAlignItems = parseRepeaterValues(nextSettings.buttonAligns || '', delimiter);
                    };

                    const syncNavigationItems = (refreshInspector) => {
                        normalizeNavigationItems();
                        updateSettings(block.id, {
                            titles: serializeRepeaterValues(titleItems, delimiter),
                            texts: serializeRepeaterValues(textItems, delimiter),
                            images: serializeRepeaterValues(imageItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                            buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                            buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                            buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                        });
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeNavigationItems();

                    const shell = createNavigationEditorScaffold([
                        { text: '', blank: true },
                        { text: label('fieldButtonLabel', 'Texte du bouton') },
                        { text: label('fieldUrl', 'Lien') },
                        { text: label('fieldTarget', 'Ouverture') },
                        { text: label('fieldAlign', 'Alignement') },
                    ], {
                        bodyClass: 'fc-builder-navigation-body pb-carousel-navigation-body',
                        listClass: 'fc-builder-navigation-list pb-carousel-navigation-list',
                        headRowClass: 'fc-builder-navigation-row fc-builder-navigation-head pb-carousel-navigation-row pb-carousel-navigation-head',
                        headCellClass: 'fc-builder-navigation-head-cell pb-carousel-navigation-head-cell',
                    });
                    const body = shell.body;

                    const toggles = document.createElement('div');
                    toggles.className = 'pb-carousel-navigation-toggles';
                    [
                        {
                            key: 'showIndicators',
                            labelText: label('fieldCarouselShowIndicators', 'Repères'),
                            styleKey: 'indicatorStyle',
                            styleKind: 'indicator',
                            styleOptions: ['dots', 'bars', 'numbers'],
                            defaultStyle: 'dots',
                            styleLabel: label('fieldCarouselIndicatorStyle', ''),
                        },
                        {
                            key: 'showArrows',
                            labelText: label('fieldCarouselShowArrows', 'Flèches de navigation'),
                            styleKey: 'arrowStyle',
                            styleKind: 'arrow',
                            styleOptions: ['filled', 'outline', 'minimal'],
                            defaultStyle: 'filled',
                            styleLabel: label('fieldCarouselArrowStyle', ''),
                        },
                        { key: 'autoplay', labelText: label('fieldCarouselAutoplay', 'Défilement automatique') },
                        { key: 'loop', labelText: label('fieldCarouselLoop', 'Recommencer en continu') },
                    ].forEach((toggleMeta) => {
                        const toggleWrap = document.createElement('div');
                        toggleWrap.className = 'pb-carousel-navigation-toggle';
                        const toggleLabel = document.createElement('span');
                        toggleLabel.className = 'pb-carousel-navigation-toggle-label';
                        toggleLabel.textContent = toggleMeta.labelText;
                        toggleWrap.appendChild(toggleLabel);
                        const toggleBody = document.createElement('div');
                        toggleBody.className = 'pb-carousel-navigation-toggle-body';
                        if (toggleMeta.styleKey) {
                            toggleBody.classList.add('has-style-choice');
                        }
                        const toggleControl = createToggleSwitchControl({
                            key: toggleMeta.key,
                            label: '',
                            type: 'checkbox',
                        }, block.settings[toggleMeta.key] !== undefined ? block.settings[toggleMeta.key] : 'off');
                        toggleControl.element.classList.add('pb-carousel-navigation-toggle-switch');
                        const toggleText = toggleControl.element.querySelector('.pb-switch-text');
                        if (toggleText) {
                            toggleText.remove();
                        }
                        toggleControl.input.addEventListener('change', () => {
                            updateSetting(
                                block.id,
                                toggleMeta.key,
                                normalizeToggleSettingValue(toggleControl.input.checked ? 'on' : 'off', 'off')
                            );
                        });
                        toggleBody.appendChild(toggleControl.element);
                        if (toggleMeta.styleKey) {
                            const styleField = {
                                key: toggleMeta.styleKey,
                                label: toggleMeta.styleLabel,
                                options: toggleMeta.styleOptions,
                                renderOption(optionValue) {
                                    return createCarouselStylePreviewNode(toggleMeta.styleKind, optionValue);
                                },
                            };
                            const activeValue = toggleMeta.styleKind === 'indicator'
                                ? normalizeCarouselIndicatorStyle(block.settings[toggleMeta.styleKey] || toggleMeta.defaultStyle)
                                : normalizeCarouselArrowStyle(block.settings[toggleMeta.styleKey] || toggleMeta.defaultStyle);
                            const styleControl = createLayoutChoiceControl(styleField, activeValue, (nextValue) => {
                                const normalizedValue = toggleMeta.styleKind === 'indicator'
                                    ? normalizeCarouselIndicatorStyle(nextValue)
                                    : normalizeCarouselArrowStyle(nextValue);
                                updateSetting(block.id, toggleMeta.styleKey, normalizedValue);
                            });
                            styleControl.classList.add('pb-carousel-navigation-style-choice');
                            toggleBody.appendChild(styleControl);
                        }
                        toggleWrap.appendChild(toggleBody);
                        toggles.appendChild(toggleWrap);
                    });
                    body.appendChild(toggles);

                    const list = shell.list;

                    for (let itemIndex = 0; itemIndex < titleItems.length; itemIndex += 1) {
                        const row = createNavigationEditorRow('pb-carousel-navigation-row');

                        const toggleControl = createNavigationSwitchCell({
                            key: 'buttonEnabled',
                            label: '',
                            type: 'checkbox',
                        }, String(buttonEnabledItems[itemIndex] || 'on'), {
                            cellClass: 'pb-carousel-navigation-cell pb-feature-grid-navigation-switch',
                        });
                        toggleControl.input.addEventListener('change', () => {
                            buttonEnabledItems[itemIndex] = normalizeToggleSettingValue(toggleControl.input.checked ? 'on' : 'off', 'on');
                            syncNavigationItems(true);
                        });
                        row.appendChild(toggleControl.element);

                        const labelInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell',
                            type: 'text',
                            value: String(buttonLabelItems[itemIndex] || ''),
                            placeholder: label('defaultCallToAction', ''),
                        });
                        labelInput.addEventListener('input', () => {
                            buttonLabelItems[itemIndex] = String(labelInput.value || '');
                            syncNavigationItems(false);
                        });
                        labelInput.addEventListener('change', () => {
                            buttonLabelItems[itemIndex] = String(labelInput.value || '');
                            syncNavigationItems(true);
                        });
                        row.appendChild(labelInput);

                        const linkInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell',
                            type: 'text',
                            value: String(linkItems[itemIndex] || ''),
                            placeholder: label('fieldCarouselLinkPlaceholder', '/page/slug'),
                        });
                        linkInput.addEventListener('input', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(false);
                        });
                        linkInput.addEventListener('change', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(true);
                        });
                        row.appendChild(linkInput);

                        const targetControl = createTargetChoiceControl(
                            normalizeLinkTarget(String(buttonTargetItems[itemIndex] || ''), String(linkItems[itemIndex] || '')),
                            (nextValue) => {
                                buttonTargetItems[itemIndex] = normalizeLinkTarget(String(nextValue || ''), String(linkItems[itemIndex] || ''));
                                syncNavigationItems(true);
                            },
                            ['pb-carousel-navigation-cell', 'pb-carousel-navigation-target']
                        );
                        row.appendChild(targetControl);

                        const alignValue = normalizeAlign(String(buttonAlignItems[itemIndex] || 'left'));
                        const alignField = {
                            key: 'buttonAlign',
                            label: label('fieldAlign', ''),
                            type: 'select',
                            options: ['left', 'center', 'right'],
                        };
                        const alignControl = createAlignIconControl(
                            alignField,
                            alignValue,
                            (nextValue, refreshInspector) => {
                                buttonAlignItems[itemIndex] = normalizeAlign(nextValue);
                                syncNavigationItems(refreshInspector !== false);
                            }
                        );
                        alignControl.classList.add('pb-carousel-navigation-cell', 'pb-carousel-navigation-align', 'pb-feature-grid-navigation-align');
                        row.appendChild(alignControl);

                        shell.appendItem(row, 'fc-builder-navigation-item pb-carousel-navigation-item');
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useLogoCloudNavigationEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-carousel-navigation-editor');
                    const delimiter = '\n';
                    const labelsField = Array.isArray(def.fields)
                        ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'labels')
                        : null;
                    const itemLabel = String(((labelsField && labelsField.repeater && labelsField.repeater.itemLabel) || (labelsField && labelsField.label) || label('fieldLabel', 'Logo'))).trim();
                    let labelItems = parseRepeaterValues(block.settings.labels || '', delimiter);
                    let logoItems = parseRepeaterValues(block.settings.logos || '', delimiter);
                    let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    let targetItems = parseRepeaterValues(block.settings.targets || '', delimiter);

                    const normalizeNavigationItems = () => {
                        const nextSettings = {
                            labels: serializeRepeaterValues(labelItems, delimiter),
                            logos: serializeRepeaterValues(logoItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            targets: serializeRepeaterValues(targetItems, delimiter),
                        };
                        normalizeWidgetLinkedRepeaters('logo_cloud', nextSettings, {
                            compact: false,
                            minLength: Math.max(1, labelItems.length),
                        });
                        labelItems = parseRepeaterValues(nextSettings.labels || '', delimiter);
                        logoItems = parseRepeaterValues(nextSettings.logos || '', delimiter);
                        linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                        targetItems = parseRepeaterValues(nextSettings.targets || '', delimiter);
                    };

                    const syncNavigationItems = (refreshInspector) => {
                        normalizeNavigationItems();
                        updateSettings(block.id, {
                            links: serializeRepeaterValues(linkItems, delimiter),
                            targets: serializeRepeaterValues(targetItems, delimiter),
                        });
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeNavigationItems();

                    const shell = createNavigationEditorScaffold([
                        { text: label('fieldLabel', 'Libellé') },
                        { text: label('fieldUrl', 'Lien') },
                        { text: label('fieldTarget', 'Ouverture') },
                    ], {
                        bodyClass: 'fc-builder-navigation-body pb-carousel-navigation-body',
                        listClass: 'fc-builder-navigation-list pb-carousel-navigation-list',
                        headRowClass: 'fc-builder-navigation-row fc-builder-navigation-head pb-carousel-navigation-row pb-carousel-navigation-head',
                        headCellClass: 'fc-builder-navigation-head-cell pb-carousel-navigation-head-cell',
                    });
                    const body = shell.body;
                    const list = shell.list;

                    for (let itemIndex = 0; itemIndex < labelItems.length; itemIndex += 1) {
                        const row = createNavigationEditorRow('pb-carousel-navigation-row');

                        const nameInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell',
                            type: 'text',
                            value: String(labelItems[itemIndex] || '').trim() || `${itemLabel} ${itemIndex + 1}`,
                            readOnly: true,
                        });
                        row.appendChild(nameInput);

                        const linkInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell',
                            type: 'text',
                            value: String(linkItems[itemIndex] || ''),
                            placeholder: label('fieldCarouselLinkPlaceholder', '/page/slug'),
                        });
                        linkInput.addEventListener('input', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(false);
                        });
                        linkInput.addEventListener('change', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(true);
                        });
                        row.appendChild(linkInput);

                        const targetControl = createTargetChoiceControl(
                            normalizeLinkTarget(String(targetItems[itemIndex] || '_self'), String(linkItems[itemIndex] || '')),
                            (nextValue) => {
                                targetItems[itemIndex] = normalizeLinkTarget(String(nextValue || ''), String(linkItems[itemIndex] || ''));
                                syncNavigationItems(true);
                            },
                            ['pb-carousel-navigation-cell', 'pb-carousel-navigation-target']
                        );
                        row.appendChild(targetControl);

                        shell.appendItem(row, 'fc-builder-navigation-item pb-carousel-navigation-item');
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useTestimonialCardsNavigationEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-carousel-navigation-editor', 'pb-testimonial-cards-navigation-editor');
                    const delimiter = '\n';
                    const namesField = Array.isArray(def.fields)
                        ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'names')
                        : null;
                    let quoteItems = parseRepeaterValues(block.settings.quotes || '', '\n---\n');
                    let nameItems = parseRepeaterValues(block.settings.names || '', delimiter);
                    let companyItems = parseRepeaterValues(block.settings.companies || '', delimiter);
                    let roleItems = parseRepeaterValues(block.settings.roles || '', delimiter);
                    let ratingItems = parseRepeaterValues(block.settings.ratings || '', delimiter);
                    let avatarItems = parseRepeaterValues(block.settings.avatars || '', delimiter);
                    let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    let targetItems = parseRepeaterValues(block.settings.targets || '', delimiter);
                    const itemLabel = String(((namesField && namesField.repeater && namesField.repeater.itemLabel) || (namesField && namesField.label) || label('fieldName', 'Nom'))).trim();

                    const normalizeNavigationItems = () => {
                        const nextSettings = {
                            quotes: serializeRepeaterValues(quoteItems, '\n---\n'),
                            names: serializeRepeaterValues(nameItems, delimiter),
                            companies: serializeRepeaterValues(companyItems, delimiter),
                            roles: serializeRepeaterValues(roleItems, delimiter),
                            ratings: serializeRepeaterValues(ratingItems, delimiter),
                            avatars: serializeRepeaterValues(avatarItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            targets: serializeRepeaterValues(targetItems, delimiter),
                        };
                        normalizeWidgetLinkedRepeaters('testimonial_cards', nextSettings, {
                            compact: false,
                            minLength: Math.max(1, quoteItems.length, nameItems.length, companyItems.length, roleItems.length, ratingItems.length, avatarItems.length, linkItems.length, targetItems.length),
                        });
                        quoteItems = parseRepeaterValues(nextSettings.quotes || '', '\n---\n');
                        nameItems = parseRepeaterValues(nextSettings.names || '', delimiter);
                        companyItems = parseRepeaterValues(nextSettings.companies || '', delimiter);
                        roleItems = parseRepeaterValues(nextSettings.roles || '', delimiter);
                        ratingItems = parseRepeaterValues(nextSettings.ratings || '', delimiter);
                        avatarItems = parseRepeaterValues(nextSettings.avatars || '', delimiter);
                        linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                        targetItems = parseRepeaterValues(nextSettings.targets || '', delimiter);
                    };

                    const syncNavigationItems = (refreshInspector) => {
                        normalizeNavigationItems();
                        updateSettings(block.id, {
                            links: serializeRepeaterValues(linkItems, delimiter),
                            targets: serializeRepeaterValues(targetItems, delimiter),
                        });
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeNavigationItems();

                    const shell = createNavigationEditorScaffold([
                        { text: label('fieldName', 'Nom') },
                        { text: label('fieldUrl', 'Lien') },
                        { text: label('fieldTarget', 'Ouverture') },
                    ], {
                        bodyClass: 'fc-builder-navigation-body pb-carousel-navigation-body',
                        listClass: 'fc-builder-navigation-list pb-carousel-navigation-list',
                        headRowClass: 'fc-builder-navigation-row fc-builder-navigation-head pb-carousel-navigation-row pb-carousel-navigation-head',
                        headCellClass: 'fc-builder-navigation-head-cell pb-carousel-navigation-head-cell',
                    });
                    const body = shell.body;
                    const list = shell.list;

                    for (let itemIndex = 0; itemIndex < quoteItems.length; itemIndex += 1) {
                        const row = createNavigationEditorRow('pb-carousel-navigation-row');

                        const nameInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell',
                            type: 'text',
                            value: String(nameItems[itemIndex] || '').trim() || `${itemLabel} ${itemIndex + 1}`,
                            readOnly: true,
                        });
                        row.appendChild(nameInput);

                        const linkInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell',
                            type: 'text',
                            value: String(linkItems[itemIndex] || ''),
                            placeholder: label('fieldCarouselLinkPlaceholder', '/page/slug'),
                        });
                        linkInput.addEventListener('input', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(false);
                        });
                        linkInput.addEventListener('change', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(true);
                        });
                        row.appendChild(linkInput);

                        const targetControl = createTargetChoiceControl(
                            normalizeLinkTarget(String(targetItems[itemIndex] || '_self'), String(linkItems[itemIndex] || '')),
                            (nextValue) => {
                                targetItems[itemIndex] = normalizeLinkTarget(String(nextValue || ''), String(linkItems[itemIndex] || ''));
                                syncNavigationItems(true);
                            },
                            ['pb-carousel-navigation-cell', 'pb-carousel-navigation-target']
                        );
                        row.appendChild(targetControl);

                        shell.appendItem(row, 'fc-builder-navigation-item pb-carousel-navigation-item');
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useSnapCardsNavigationEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-snap-cards-navigation-editor');
                    const delimiter = '\n';
                    const titleField = Array.isArray(def.fields)
                        ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'titles')
                        : null;
                    const titleRepeater = titleField && titleField.repeater ? titleField.repeater : {};
                    const itemLabel = String(titleRepeater.itemLabel || label('fieldCardsTitleItem', '')).trim();
                    let titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                    let textItems = parseRepeaterValues(block.settings.texts || '', delimiter);
                    let backgroundItems = parseRepeaterValues(block.settings.backgrounds || '', delimiter);
                    let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    let ctaEnabledItems = parseRepeaterValues(block.settings.ctaEnableds || '', delimiter);
                    let ctaLabelItems = parseRepeaterValues(block.settings.ctaLabels || '', delimiter);
                    let targetItems = parseRepeaterValues(block.settings.targets || '', delimiter);
                    let buttonAlignItems = parseRepeaterValues(block.settings.buttonAligns || '', delimiter);
                    let globalCtaLabel = String(block.settings.ctaLabel || '').trim();
                    let globalTarget = ['_self', '_blank'].includes(String(block.settings.target || '').trim())
                        ? String(block.settings.target || '').trim()
                        : '_self';
                    const resolveSnapCardsBaseAlign = () => normalizeAlign(String((block.settings && block.settings.align) || 'left'));

                    const normalizeNavigationItems = () => {
                        const minLength = Math.max(
                            1,
                            titleItems.length,
                            textItems.length,
                            backgroundItems.length,
                            linkItems.length,
                            ctaEnabledItems.length,
                            ctaLabelItems.length,
                            targetItems.length,
                            buttonAlignItems.length
                        );
                        const nextSettings = {
                            titles: serializeRepeaterValues(titleItems, delimiter),
                            texts: serializeRepeaterValues(textItems, delimiter),
                            backgrounds: serializeRepeaterValues(backgroundItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            ctaEnableds: serializeRepeaterValues(ctaEnabledItems, delimiter),
                            ctaLabels: serializeRepeaterValues(ctaLabelItems, delimiter),
                            targets: serializeRepeaterValues(targetItems, delimiter),
                            buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                            ctaLabel: globalCtaLabel,
                            target: globalTarget,
                            align: resolveSnapCardsBaseAlign(),
                        };
                        normalizeWidgetLinkedRepeaters('snap_cards', nextSettings, { compact: false, minLength: minLength });
                        titleItems = parseRepeaterValues(nextSettings.titles || '', delimiter);
                        textItems = parseRepeaterValues(nextSettings.texts || '', delimiter);
                        backgroundItems = parseRepeaterValues(nextSettings.backgrounds || '', delimiter);
                        linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                        ctaEnabledItems = parseRepeaterValues(nextSettings.ctaEnableds || '', delimiter);
                        ctaLabelItems = parseRepeaterValues(nextSettings.ctaLabels || '', delimiter);
                        targetItems = parseRepeaterValues(nextSettings.targets || '', delimiter);
                        buttonAlignItems = parseRepeaterValues(nextSettings.buttonAligns || '', delimiter);
                        globalCtaLabel = String(nextSettings.ctaLabel || '').trim();
                        globalTarget = ['_self', '_blank'].includes(String(nextSettings.target || '').trim())
                            ? String(nextSettings.target || '').trim()
                            : '_self';
                    };

                    const syncNavigationItems = (refreshInspector) => {
                        normalizeNavigationItems();
                        updateSettings(block.id, {
                            links: serializeRepeaterValues(linkItems, delimiter),
                            ctaEnableds: serializeRepeaterValues(ctaEnabledItems, delimiter),
                            ctaLabels: serializeRepeaterValues(ctaLabelItems, delimiter),
                            targets: serializeRepeaterValues(targetItems, delimiter),
                            buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                            ctaLabel: globalCtaLabel,
                            target: globalTarget,
                        });
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeNavigationItems();

                    const shell = createNavigationEditorScaffold([
                        { text: '', blank: true },
                        { text: label('fieldButtonLabel', '') },
                        { text: label('fieldUrl', '') },
                        { text: label('fieldTarget', '') },
                        { text: label('fieldAlign', '') },
                    ], {
                        bodyClass: 'fc-builder-navigation-body pb-carousel-navigation-body pb-snap-cards-navigation-body',
                        listClass: 'fc-builder-navigation-list pb-carousel-navigation-list pb-snap-cards-navigation-list',
                        headRowClass: 'fc-builder-navigation-row fc-builder-navigation-head pb-carousel-navigation-row pb-carousel-navigation-head',
                        headCellClass: 'fc-builder-navigation-head-cell pb-carousel-navigation-head-cell',
                    });
                    const body = shell.body;
                    const list = shell.list;

                    for (let itemIndex = 0; itemIndex < titleItems.length; itemIndex += 1) {
                        const row = createNavigationEditorRow('pb-carousel-navigation-row');

                        const toggleField = {
                            key: 'ctaEnabled',
                            label: '',
                            type: 'checkbox',
                        };
                        const toggleControl = createNavigationSwitchCell(toggleField, String(ctaEnabledItems[itemIndex] || 'on'), {
                            cellClass: 'pb-carousel-navigation-cell pb-feature-grid-navigation-switch',
                        });
                        toggleControl.input.addEventListener('change', () => {
                            ctaEnabledItems[itemIndex] = normalizeToggleSettingValue(toggleControl.input.checked ? 'on' : 'off', 'on');
                            syncNavigationItems(true);
                        });
                        row.appendChild(toggleControl.element);

                        const labelInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell',
                            type: 'text',
                            value: String(ctaLabelItems[itemIndex] || ''),
                            placeholder: label('defaultCallToAction', ''),
                        });
                        labelInput.addEventListener('input', () => {
                            ctaLabelItems[itemIndex] = String(labelInput.value || '');
                            syncNavigationItems(false);
                        });
                        labelInput.addEventListener('change', () => {
                            ctaLabelItems[itemIndex] = String(labelInput.value || '');
                            syncNavigationItems(false);
                        });
                        row.appendChild(labelInput);

                        const linkInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell',
                            type: 'text',
                            value: String(linkItems[itemIndex] || ''),
                            placeholder: label('fieldCarouselLinkPlaceholder', '/page/slug'),
                        });
                        linkInput.addEventListener('input', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(false);
                        });
                        linkInput.addEventListener('change', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(false);
                        });
                        row.appendChild(linkInput);

                        const targetControl = createTargetChoiceControl(
                            normalizeLinkTarget(String(targetItems[itemIndex] || globalTarget), String(linkItems[itemIndex] || '')),
                            (nextValue) => {
                                targetItems[itemIndex] = normalizeLinkTarget(String(nextValue || ''), String(linkItems[itemIndex] || ''));
                                syncNavigationItems(true);
                            },
                            ['pb-carousel-navigation-cell', 'pb-carousel-navigation-target']
                        );
                        row.appendChild(targetControl);

                        const alignField = {
                            key: 'buttonAlign',
                            label: label('fieldAlign', ''),
                            type: 'select',
                            options: ['left', 'center', 'right'],
                        };
                        const alignControl = createAlignIconControl(
                            alignField,
                            normalizeAlign(String(buttonAlignItems[itemIndex] || ''), resolveSnapCardsBaseAlign()),
                            (nextValue, refreshInspector) => {
                                buttonAlignItems[itemIndex] = normalizeAlign(nextValue, resolveSnapCardsBaseAlign());
                                syncNavigationItems(refreshInspector !== false);
                            }
                        );
                        alignControl.classList.add('pb-carousel-navigation-cell', 'pb-carousel-navigation-align', 'pb-feature-grid-navigation-align');
                        row.appendChild(alignControl);

                        shell.appendItem(row, 'fc-builder-navigation-item pb-carousel-navigation-item');
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useNwCarrouselMediaEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-feature-grid-media-editor', 'pb-pricing-plans-media-editor');
                    const delimiter = '\n---\n';
                    const titlesField = Array.isArray(def.fields)
                        ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'titles')
                        : null;
                    const itemLabel = String(((titlesField && titlesField.repeater && titlesField.repeater.itemLabel) || (titlesField && titlesField.label) || label('fieldLabel', 'Slide'))).trim();
                    const titleColumnLabel = String(label('fieldTitle', 'Titre') || 'Titre').trim() || 'Titre';
                    const imageColumnLabel = String((field && field.label) || label('fieldImage', 'Image')).trim() || label('fieldImage', 'Image');
                    const previewColumnLabel = String(label('fieldPreview', 'Aperçu') || 'Aperçu').trim() || 'Aperçu';
                    const mediaOptions = normalizeMediaFieldOptions(field.media || { mode: 'images', folder: 'images' });
                    let titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                    let descriptionItems = parseRepeaterValues(block.settings.descriptions || '', delimiter);
                    let imageItems = parseRepeaterValues(block.settings.images || '', delimiter);
                    let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    let buttonLabelItems = parseRepeaterValues(block.settings.buttonLabels || '', delimiter);
                    let buttonTargetItems = parseRepeaterValues(block.settings.buttonTargets || '', delimiter);

                    const normalizeMediaItems = () => {
                        const nextSettings = {
                            titles: serializeRepeaterValues(titleItems, delimiter),
                            descriptions: serializeRepeaterValues(descriptionItems, delimiter),
                            images: serializeRepeaterValues(imageItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                            buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                        };
                        normalizeWidgetLinkedRepeaters('nw_carrousel', nextSettings, {
                            compact: false,
                            minLength: Math.max(1, titleItems.length),
                        });
                        titleItems = parseRepeaterValues(nextSettings.titles || '', delimiter);
                        descriptionItems = parseRepeaterValues(nextSettings.descriptions || '', delimiter);
                        imageItems = parseRepeaterValues(nextSettings.images || '', delimiter);
                        linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                        buttonLabelItems = parseRepeaterValues(nextSettings.buttonLabels || '', delimiter);
                        buttonTargetItems = parseRepeaterValues(nextSettings.buttonTargets || '', delimiter);
                    };

                    const syncMediaItems = (refreshInspector) => {
                        normalizeMediaItems();
                        updateSettings(block.id, {
                            images: serializeRepeaterValues(imageItems, delimiter),
                        });
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeMediaItems();

                    const body = document.createElement('div');
                    body.className = 'pb-feature-grid-repeater-body pb-feature-grid-media-body';

                    const head = document.createElement('div');
                    head.className = 'fc-builder-navigation-row fc-builder-navigation-head pb-carousel-navigation-head pb-nw-carrousel-media-row pb-nw-carrousel-media-head';
                    [
                        titleColumnLabel,
                        imageColumnLabel,
                        previewColumnLabel,
                        '',
                        '',
                    ].forEach((headerText, headerIndex) => {
                        const cell = document.createElement('div');
                        cell.className = 'fc-builder-navigation-head-cell pb-carousel-navigation-head-cell';
                        if (headerText === '' && headerIndex >= 3) {
                            cell.classList.add('is-blank');
                        }
                        cell.textContent = headerText;
                        head.appendChild(cell);
                    });

                    const list = document.createElement('div');
                    list.className = 'pb-feature-grid-media-list';
                    const primitives = window.FlatCMSUIPrimitives || {};
                    const chooseImageLabel = label('chooseImage', 'Choisir une image');
                    const removeMediaLabel = label('removeMedia', 'Supprimer le média');

                    for (let itemIndex = 0; itemIndex < titleItems.length; itemIndex += 1) {
                        const itemName = String(titleItems[itemIndex] || '').trim() || `${itemLabel} ${itemIndex + 1}`;
                        let row;
                        let mediaInput;
                        let pickBtn;
                        let clearBtn;
                        let updatePreview;

                        if (typeof primitives.createBuilderMediaPickerGridRow === 'function') {
                            const mediaRow = primitives.createBuilderMediaPickerGridRow({
                                rowClass: 'pb-feature-grid-media-row pb-nw-carrousel-media-row',
                                labelInputClass: 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name',
                                labelValue: itemName,
                                valueInputClass: 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name',
                                value: String(imageItems[itemIndex] || ''),
                                resolveSrc: resolveMediaSrc,
                                placeholder: chooseImageLabel,
                                previewWrapClass: 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell',
                                previewClass: 'pb-feature-grid-media-preview',
                                pickButtonClass: 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker',
                                pickButtonHtml: '<i class="fas fa-image" aria-hidden="true"></i>',
                                pickButtonTitle: chooseImageLabel,
                                clearButtonClass: 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear',
                                clearButtonHtml: '<i class="fas fa-trash" aria-hidden="true"></i>',
                                clearButtonTitle: removeMediaLabel,
                            });
                            row = mediaRow.element;
                            mediaInput = mediaRow.valueInput;
                            pickBtn = mediaRow.pickButton;
                            clearBtn = mediaRow.clearButton;
                            updatePreview = (nextValue) => mediaRow.setValue(nextValue);
                        } else {
                            row = document.createElement('div');
                            row.className = 'pb-feature-grid-media-row pb-nw-carrousel-media-row';

                            const nameInput = document.createElement('input');
                            nameInput.className = 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name';
                            nameInput.type = 'text';
                            nameInput.value = itemName;
                            nameInput.readOnly = true;
                            row.appendChild(nameInput);

                            mediaInput = document.createElement('input');
                            mediaInput.className = 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name';
                            mediaInput.type = 'text';
                            mediaInput.value = String(imageItems[itemIndex] || '');
                            mediaInput.placeholder = chooseImageLabel;
                            row.appendChild(mediaInput);

                            const previewWrap = document.createElement('div');
                            previewWrap.className = 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell';
                            const preview = document.createElement('img');
                            preview.className = 'pb-feature-grid-media-preview';
                            preview.alt = '';
                            updatePreview = (nextValue) => {
                                setInspectorMediaImagePreview(preview, nextValue);
                            };
                            updatePreview(mediaInput.value);
                            previewWrap.appendChild(preview);
                            row.appendChild(previewWrap);

                            pickBtn = document.createElement('button');
                            pickBtn.type = 'button';
                            pickBtn.className = 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker';
                            pickBtn.innerHTML = '<i class="fas fa-image" aria-hidden="true"></i>';
                            pickBtn.title = chooseImageLabel;
                            pickBtn.setAttribute('aria-label', chooseImageLabel);
                            row.appendChild(pickBtn);

                            clearBtn = document.createElement('button');
                            clearBtn.type = 'button';
                            clearBtn.className = 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear';
                            clearBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
                            clearBtn.title = removeMediaLabel;
                            clearBtn.setAttribute('aria-label', removeMediaLabel);
                            row.appendChild(clearBtn);
                        }

                        mediaInput.addEventListener('input', () => {
                            imageItems[itemIndex] = String(mediaInput.value || '').trim();
                            syncMediaItems(false);
                        });
                        mediaInput.addEventListener('change', () => {
                            imageItems[itemIndex] = String(mediaInput.value || '').trim();
                            syncMediaItems(true);
                        });
                        updatePreview(mediaInput.value);
                        pickBtn.addEventListener('click', () => {
                            openMediaPicker((file) => {
                                const nextValue = String((file && (file.url || file.path || '')) || '').trim();
                                mediaInput.value = nextValue;
                                imageItems[itemIndex] = nextValue;
                                updatePreview(nextValue);
                                syncMediaItems(true);
                            }, mediaOptions);
                        });
                        clearBtn.addEventListener('click', () => {
                            mediaInput.value = '';
                            imageItems[itemIndex] = '';
                            updatePreview('');
                            syncMediaItems(true);
                        });

                        list.appendChild(row);
                    }

                    body.appendChild(head);
                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useLogoCloudMediaEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-feature-grid-media-editor', 'pb-pricing-plans-media-editor');
                    const delimiter = '\n';
                    const labelsField = Array.isArray(def.fields)
                        ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'labels')
                        : null;
                    const itemLabel = String(((labelsField && labelsField.repeater && labelsField.repeater.itemLabel) || (labelsField && labelsField.label) || label('fieldLabel', 'Logo'))).trim();
                    const mediaOptions = normalizeMediaFieldOptions(field.media || { mode: 'images', folder: 'images' });
                    let labelItems = parseRepeaterValues(block.settings.labels || '', delimiter);
                    let logoItems = parseRepeaterValues(block.settings.logos || '', delimiter);
                    let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    let targetItems = parseRepeaterValues(block.settings.targets || '', delimiter);

                    const normalizeMediaItems = () => {
                        const nextSettings = {
                            labels: serializeRepeaterValues(labelItems, delimiter),
                            logos: serializeRepeaterValues(logoItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            targets: serializeRepeaterValues(targetItems, delimiter),
                        };
                        normalizeWidgetLinkedRepeaters('logo_cloud', nextSettings, {
                            compact: false,
                            minLength: Math.max(1, labelItems.length),
                        });
                        labelItems = parseRepeaterValues(nextSettings.labels || '', delimiter);
                        logoItems = parseRepeaterValues(nextSettings.logos || '', delimiter);
                        linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                        targetItems = parseRepeaterValues(nextSettings.targets || '', delimiter);
                    };

                    const syncMediaItems = (refreshInspector) => {
                        normalizeMediaItems();
                        updateSettings(block.id, {
                            logos: serializeRepeaterValues(logoItems, delimiter),
                        });
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeMediaItems();

                    const body = document.createElement('div');
                    body.className = 'pb-feature-grid-repeater-body pb-feature-grid-media-body';

                    const list = document.createElement('div');
                    list.className = 'pb-feature-grid-media-list';
                    const primitives = window.FlatCMSUIPrimitives || {};
                    const chooseImageLabel = label('chooseImage', 'Choisir une image');
                    const removeMediaLabel = label('removeMedia', 'Supprimer le média');

                    for (let itemIndex = 0; itemIndex < labelItems.length; itemIndex += 1) {
                        const itemName = String(labelItems[itemIndex] || '').trim() || `${itemLabel} ${itemIndex + 1}`;
                        let row;
                        let mediaInput;
                        let pickBtn;
                        let clearBtn;
                        let updatePreview;

                        if (typeof primitives.createBuilderMediaPickerGridRow === 'function') {
                            const mediaRow = primitives.createBuilderMediaPickerGridRow({
                                rowClass: 'pb-feature-grid-media-row',
                                labelInputClass: 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name',
                                labelValue: itemName,
                                valueInputClass: 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name',
                                value: String(logoItems[itemIndex] || ''),
                                resolveSrc: resolveMediaSrc,
                                placeholder: chooseImageLabel,
                                previewWrapClass: 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell',
                                previewClass: 'pb-feature-grid-media-preview',
                                pickButtonClass: 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker',
                                pickButtonHtml: '<i class="fas fa-image" aria-hidden="true"></i>',
                                pickButtonTitle: chooseImageLabel,
                                clearButtonClass: 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear',
                                clearButtonHtml: '<i class="fas fa-trash" aria-hidden="true"></i>',
                                clearButtonTitle: removeMediaLabel,
                            });
                            row = mediaRow.element;
                            mediaInput = mediaRow.valueInput;
                            pickBtn = mediaRow.pickButton;
                            clearBtn = mediaRow.clearButton;
                            updatePreview = (nextValue) => mediaRow.setValue(nextValue);
                        } else {
                            row = document.createElement('div');
                            row.className = 'pb-feature-grid-media-row';

                            const nameInput = document.createElement('input');
                            nameInput.className = 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name';
                            nameInput.type = 'text';
                            nameInput.value = itemName;
                            nameInput.readOnly = true;
                            row.appendChild(nameInput);

                            mediaInput = document.createElement('input');
                            mediaInput.className = 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name';
                            mediaInput.type = 'text';
                            mediaInput.value = String(logoItems[itemIndex] || '');
                            mediaInput.placeholder = chooseImageLabel;
                            row.appendChild(mediaInput);

                            const previewWrap = document.createElement('div');
                            previewWrap.className = 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell';
                            const preview = document.createElement('img');
                            preview.className = 'pb-feature-grid-media-preview';
                            preview.alt = '';
                            updatePreview = (nextValue) => {
                                setInspectorMediaImagePreview(preview, nextValue);
                            };
                            updatePreview(mediaInput.value);
                            previewWrap.appendChild(preview);
                            row.appendChild(previewWrap);

                            pickBtn = document.createElement('button');
                            pickBtn.type = 'button';
                            pickBtn.className = 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker';
                            pickBtn.innerHTML = '<i class="fas fa-image" aria-hidden="true"></i>';
                            pickBtn.title = chooseImageLabel;
                            pickBtn.setAttribute('aria-label', chooseImageLabel);
                            row.appendChild(pickBtn);

                            clearBtn = document.createElement('button');
                            clearBtn.type = 'button';
                            clearBtn.className = 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear';
                            clearBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
                            clearBtn.title = removeMediaLabel;
                            clearBtn.setAttribute('aria-label', removeMediaLabel);
                            row.appendChild(clearBtn);
                        }

                        mediaInput.addEventListener('input', () => {
                            logoItems[itemIndex] = String(mediaInput.value || '').trim();
                            syncMediaItems(false);
                        });
                        mediaInput.addEventListener('change', () => {
                            logoItems[itemIndex] = String(mediaInput.value || '').trim();
                            syncMediaItems(true);
                        });
                        updatePreview(mediaInput.value);
                        pickBtn.addEventListener('click', () => {
                            openMediaPicker((file) => {
                                const nextValue = String((file && (file.url || file.path || '')) || '').trim();
                                mediaInput.value = nextValue;
                                logoItems[itemIndex] = nextValue;
                                updatePreview(nextValue);
                                syncMediaItems(true);
                            }, mediaOptions);
                        });
                        clearBtn.addEventListener('click', () => {
                            mediaInput.value = '';
                            logoItems[itemIndex] = '';
                            updatePreview('');
                            syncMediaItems(true);
                        });

                        list.appendChild(row);
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useTestimonialCardsMediaEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-feature-grid-media-editor', 'pb-testimonial-cards-media-editor');
                    const delimiter = '\n';
                    const namesField = Array.isArray(def.fields)
                        ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'names')
                        : null;
                    const mediaOptions = normalizeMediaFieldOptions(field.media || { mode: 'images', folder: 'images' });
                    let quoteItems = parseRepeaterValues(block.settings.quotes || '', '\n---\n');
                    let nameItems = parseRepeaterValues(block.settings.names || '', delimiter);
                    let companyItems = parseRepeaterValues(block.settings.companies || '', delimiter);
                    let roleItems = parseRepeaterValues(block.settings.roles || '', delimiter);
                    let ratingItems = parseRepeaterValues(block.settings.ratings || '', delimiter);
                    let avatarItems = parseRepeaterValues(block.settings.avatars || '', delimiter);
                    let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    let targetItems = parseRepeaterValues(block.settings.targets || '', delimiter);
                    const itemLabel = String(((namesField && namesField.repeater && namesField.repeater.itemLabel) || (namesField && namesField.label) || label('fieldName', 'Nom'))).trim();

                    const normalizeMediaItems = () => {
                        const nextSettings = {
                            quotes: serializeRepeaterValues(quoteItems, '\n---\n'),
                            names: serializeRepeaterValues(nameItems, delimiter),
                            companies: serializeRepeaterValues(companyItems, delimiter),
                            roles: serializeRepeaterValues(roleItems, delimiter),
                            ratings: serializeRepeaterValues(ratingItems, delimiter),
                            avatars: serializeRepeaterValues(avatarItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            targets: serializeRepeaterValues(targetItems, delimiter),
                        };
                        normalizeWidgetLinkedRepeaters('testimonial_cards', nextSettings, {
                            compact: false,
                            minLength: Math.max(1, quoteItems.length, nameItems.length, companyItems.length, roleItems.length, ratingItems.length, avatarItems.length, linkItems.length, targetItems.length),
                        });
                        quoteItems = parseRepeaterValues(nextSettings.quotes || '', '\n---\n');
                        nameItems = parseRepeaterValues(nextSettings.names || '', delimiter);
                        companyItems = parseRepeaterValues(nextSettings.companies || '', delimiter);
                        roleItems = parseRepeaterValues(nextSettings.roles || '', delimiter);
                        ratingItems = parseRepeaterValues(nextSettings.ratings || '', delimiter);
                        avatarItems = parseRepeaterValues(nextSettings.avatars || '', delimiter);
                        linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                        targetItems = parseRepeaterValues(nextSettings.targets || '', delimiter);
                    };

                    const syncMediaItems = (refreshInspector) => {
                        normalizeMediaItems();
                        updateSettings(block.id, {
                            avatars: serializeRepeaterValues(avatarItems, delimiter),
                        });
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeMediaItems();

                    const body = document.createElement('div');
                    body.className = 'pb-feature-grid-repeater-body pb-feature-grid-media-body';
                    const list = document.createElement('div');
                    list.className = 'pb-feature-grid-media-list';
                    const primitives = window.FlatCMSUIPrimitives || {};
                    const chooseImageLabel = label('chooseImage', 'Choisir une image');
                    const removeMediaLabel = label('removeMedia', 'Supprimer le média');

                    for (let itemIndex = 0; itemIndex < quoteItems.length; itemIndex += 1) {
                        const itemName = String(nameItems[itemIndex] || '').trim() || `${itemLabel} ${itemIndex + 1}`;
                        let row;
                        let mediaInput;
                        let pickBtn;
                        let clearBtn;
                        let updatePreview;

                        if (typeof primitives.createBuilderMediaPickerGridRow === 'function') {
                            const mediaRow = primitives.createBuilderMediaPickerGridRow({
                                rowClass: 'pb-feature-grid-media-row',
                                labelInputClass: 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name',
                                labelValue: itemName,
                                valueInputClass: 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name',
                                value: String(avatarItems[itemIndex] || ''),
                                resolveSrc: resolveMediaSrc,
                                placeholder: chooseImageLabel,
                                previewWrapClass: 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell',
                                previewClass: 'pb-feature-grid-media-preview',
                                pickButtonClass: 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker',
                                pickButtonHtml: '<i class="fas fa-image" aria-hidden="true"></i>',
                                pickButtonTitle: chooseImageLabel,
                                clearButtonClass: 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear',
                                clearButtonHtml: '<i class="fas fa-trash" aria-hidden="true"></i>',
                                clearButtonTitle: removeMediaLabel,
                            });
                            row = mediaRow.element;
                            mediaInput = mediaRow.valueInput;
                            pickBtn = mediaRow.pickButton;
                            clearBtn = mediaRow.clearButton;
                            updatePreview = (nextValue) => mediaRow.setValue(nextValue);
                        } else {
                            row = document.createElement('div');
                            row.className = 'pb-feature-grid-media-row';

                            const nameInput = document.createElement('input');
                            nameInput.className = 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name';
                            nameInput.type = 'text';
                            nameInput.value = itemName;
                            nameInput.readOnly = true;
                            row.appendChild(nameInput);

                            mediaInput = document.createElement('input');
                            mediaInput.className = 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name';
                            mediaInput.type = 'text';
                            mediaInput.value = String(avatarItems[itemIndex] || '');
                            mediaInput.placeholder = chooseImageLabel;
                            row.appendChild(mediaInput);

                            const previewWrap = document.createElement('div');
                            previewWrap.className = 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell';
                            const preview = document.createElement('img');
                            preview.className = 'pb-feature-grid-media-preview';
                            preview.alt = '';
                            updatePreview = (nextValue) => {
                                setInspectorMediaImagePreview(preview, nextValue);
                            };
                            updatePreview(mediaInput.value);
                            previewWrap.appendChild(preview);
                            row.appendChild(previewWrap);

                            pickBtn = document.createElement('button');
                            pickBtn.type = 'button';
                            pickBtn.className = 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker';
                            pickBtn.innerHTML = '<i class="fas fa-image" aria-hidden="true"></i>';
                            pickBtn.title = chooseImageLabel;
                            pickBtn.setAttribute('aria-label', chooseImageLabel);
                            row.appendChild(pickBtn);

                            clearBtn = document.createElement('button');
                            clearBtn.type = 'button';
                            clearBtn.className = 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear';
                            clearBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
                            clearBtn.title = removeMediaLabel;
                            clearBtn.setAttribute('aria-label', removeMediaLabel);
                            row.appendChild(clearBtn);
                        }

                        mediaInput.addEventListener('input', () => {
                            avatarItems[itemIndex] = String(mediaInput.value || '').trim();
                            syncMediaItems(false);
                        });
                        mediaInput.addEventListener('change', () => {
                            avatarItems[itemIndex] = String(mediaInput.value || '').trim();
                            syncMediaItems(true);
                        });
                        updatePreview(mediaInput.value);
                        pickBtn.addEventListener('click', () => {
                            openMediaPicker((file) => {
                                const nextValue = String((file && (file.url || file.path || '')) || '').trim();
                                mediaInput.value = nextValue;
                                avatarItems[itemIndex] = nextValue;
                                updatePreview(nextValue);
                                syncMediaItems(true);
                            }, mediaOptions);
                        });
                        clearBtn.addEventListener('click', () => {
                            mediaInput.value = '';
                            avatarItems[itemIndex] = '';
                            updatePreview('');
                            syncMediaItems(true);
                        });

                        list.appendChild(row);
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useFeatureGridMediaEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-feature-grid-media-editor');
                    const delimiter = '\n';
                    const iconItemLabel = String((field.repeater && field.repeater.itemLabel) || field.label || '').trim();
                    const resolveFeatureGridBaseAlign = () => normalizeAlign(String(block.settings.align || 'left'));
                    let titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                    let textItems = parseFeatureGridTextValues(block.settings.texts || '');
                    let iconItems = parseRepeaterValues(block.settings.icons || '', delimiter);
                    let iconEnabledItems = parseRepeaterValues(block.settings.iconEnableds || '', delimiter);
                    let iconAlignItems = parseRepeaterValues(block.settings.iconAligns || '', delimiter);

                    const normalizeMediaItems = () => {
                        const nextSettings = {
                            titles: serializeRepeaterValues(titleItems, delimiter),
                            texts: serializeFeatureGridTextValues(textItems),
                            icons: serializeRepeaterValues(iconItems, delimiter),
                            iconEnableds: serializeRepeaterValues(iconEnabledItems, delimiter),
                            iconAligns: serializeRepeaterValues(iconAlignItems, delimiter),
                        };
                        normalizeWidgetLinkedRepeaters('feature_grid', nextSettings, {
                            compact: false,
                            minLength: Math.max(1, titleItems.length),
                        });
                        titleItems = parseRepeaterValues(nextSettings.titles || '', delimiter);
                        textItems = parseFeatureGridTextValues(nextSettings.texts || '');
                        iconItems = parseRepeaterValues(nextSettings.icons || '', delimiter);
                        iconEnabledItems = parseRepeaterValues(nextSettings.iconEnableds || '', delimiter);
                        iconAlignItems = parseRepeaterValues(nextSettings.iconAligns || '', delimiter);
                        const fallbackLength = Math.max(1, titleItems.length, textItems.length, iconItems.length, iconAlignItems.length);
                        while (titleItems.length < fallbackLength) titleItems.push('');
                        while (textItems.length < fallbackLength) textItems.push('');
                        while (iconItems.length < fallbackLength) iconItems.push('');
                        while (iconEnabledItems.length < fallbackLength) iconEnabledItems.push('on');
                        while (iconAlignItems.length < fallbackLength) iconAlignItems.push(resolveFeatureGridBaseAlign());
                    };

                    const syncMediaItems = (refreshInspector) => {
                        normalizeMediaItems();
                        updateSettings(block.id, {
                            icons: serializeRepeaterValues(iconItems, delimiter),
                            iconEnableds: serializeRepeaterValues(iconEnabledItems, delimiter),
                            iconAligns: serializeRepeaterValues(iconAlignItems, delimiter),
                        });
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeMediaItems();

                    const body = document.createElement('div');
                    body.className = 'pb-feature-grid-repeater-body pb-feature-grid-media-body';

                    const list = document.createElement('div');
                    list.className = 'pb-feature-grid-media-list';
                    const primitives = window.FlatCMSUIPrimitives || {};
                    const chooseIconLabel = label('chooseIcon', '');
                    const removeIconLabel = label('removeIcon', '');

                    for (let itemIndex = 0; itemIndex < titleItems.length; itemIndex += 1) {
                        const toggleField = {
                            key: 'iconEnabled',
                            label: '',
                            type: 'checkbox',
                        };
                        const toggleControl = createToggleSwitchControl(toggleField, String(iconEnabledItems[itemIndex] || 'on'));
                        toggleControl.element.classList.add('pb-feature-grid-media-cell', 'pb-feature-grid-media-switch');
                        const toggleText = toggleControl.element.querySelector('.pb-switch-text');
                        if (toggleText) {
                            toggleText.remove();
                        }
                        toggleControl.input.setAttribute('aria-label', iconItemLabel !== '' ? `${iconItemLabel} ${itemIndex + 1}` : String(itemIndex + 1));
                        toggleControl.input.addEventListener('change', () => {
                            iconEnabledItems[itemIndex] = normalizeToggleSettingValue(toggleControl.input.checked ? 'on' : 'off', 'on');
                            syncMediaItems(true);
                        });
                        const inputAriaLabel = iconItemLabel !== '' ? `${iconItemLabel} ${itemIndex + 1}` : String(itemIndex + 1);
                        let row;
                        let iconInput;
                        let pickBtn;
                        let clearBtn;
                        let updatePreview;

                        if (typeof primitives.createBuilderIconPickerGridRow === 'function') {
                            const iconRow = primitives.createBuilderIconPickerGridRow({
                                rowClass: 'pb-feature-grid-media-row',
                                leadElement: toggleControl.element,
                                valueInputClass: 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name',
                                value: String(iconItems[itemIndex] || ''),
                                inputAriaLabel,
                                previewWrapClass: 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell',
                                previewClass: 'pb-icon-preview pb-feature-grid-media-preview',
                                pickButtonClass: 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker',
                                pickButtonHtml: '<i class="fas fa-icons" aria-hidden="true"></i>',
                                pickButtonTitle: chooseIconLabel,
                                clearButtonClass: 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear',
                                clearButtonHtml: '<i class="fas fa-trash" aria-hidden="true"></i>',
                                clearButtonTitle: removeIconLabel,
                            });
                            row = iconRow.element;
                            iconInput = iconRow.valueInput;
                            pickBtn = iconRow.pickButton;
                            clearBtn = iconRow.clearButton;
                            updatePreview = (nextValue) => iconRow.setValue(nextValue);
                        } else {
                            row = document.createElement('div');
                            row.className = 'pb-feature-grid-media-row';
                            row.appendChild(toggleControl.element);

                            iconInput = document.createElement('input');
                            iconInput.className = 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name';
                            iconInput.type = 'text';
                            iconInput.value = String(iconItems[itemIndex] || '');
                            iconInput.setAttribute('aria-label', inputAriaLabel);
                            row.appendChild(iconInput);

                            const iconPreviewWrap = document.createElement('div');
                            iconPreviewWrap.className = 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell';
                            const iconPreview = document.createElement('span');
                            iconPreview.className = 'pb-icon-preview pb-feature-grid-media-preview';
                            updatePreview = (nextValue) => {
                                updateIconPreview(iconPreview, nextValue);
                            };
                            updatePreview(iconInput.value);
                            iconPreviewWrap.appendChild(iconPreview);
                            row.appendChild(iconPreviewWrap);

                            pickBtn = document.createElement('button');
                            pickBtn.type = 'button';
                            pickBtn.className = 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker';
                            pickBtn.innerHTML = '<i class="fas fa-icons" aria-hidden="true"></i>';
                            pickBtn.title = chooseIconLabel;
                            pickBtn.setAttribute('aria-label', chooseIconLabel);
                            row.appendChild(pickBtn);

                            clearBtn = document.createElement('button');
                            clearBtn.type = 'button';
                            clearBtn.className = 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear';
                            clearBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
                            clearBtn.title = removeIconLabel;
                            clearBtn.setAttribute('aria-label', removeIconLabel);
                            row.appendChild(clearBtn);
                        }

                        iconInput.addEventListener('input', () => {
                            iconItems[itemIndex] = String(iconInput.value || '').trim();
                            syncMediaItems(false);
                            updatePreview(iconInput.value);
                        });
                        iconInput.addEventListener('change', () => {
                            iconItems[itemIndex] = String(iconInput.value || '').trim();
                            syncMediaItems(true);
                            updatePreview(iconInput.value);
                        });
                        updatePreview(iconInput.value);
                        pickBtn.addEventListener('click', () => {
                            openIconPicker(iconInput.value || '', (picked) => {
                                const nextValue = String(picked || '').trim();
                                iconInput.value = nextValue;
                                iconItems[itemIndex] = nextValue;
                                updatePreview(nextValue);
                                syncMediaItems(true);
                            });
                        });
                        clearBtn.addEventListener('click', () => {
                            iconInput.value = '';
                            iconItems[itemIndex] = '';
                            updatePreview('');
                            syncMediaItems(true);
                        });

                        const alignField = {
                            key: 'iconAlign',
                            label: label('fieldAlign', ''),
                            type: 'select',
                            options: ['left', 'center', 'right'],
                        };
                        const alignControl = createAlignIconControl(
                            alignField,
                            normalizeAlign(String(iconAlignItems[itemIndex] || ''), resolveFeatureGridBaseAlign()),
                            (nextValue, refreshInspector) => {
                                iconAlignItems[itemIndex] = normalizeAlign(nextValue, resolveFeatureGridBaseAlign());
                                syncMediaItems(refreshInspector !== false);
                            }
                        );
                        alignControl.classList.add('pb-feature-grid-media-cell', 'pb-feature-grid-media-align');
                        row.appendChild(alignControl);

                        list.appendChild(row);
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (usePricingPlansMediaEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-feature-grid-media-editor');
                    const pricingState = createPricingPlansInspectorState(block.settings);
                    const iconItemLabel = String((field.repeater && field.repeater.itemLabel) || field.label || '').trim();

                    const normalizeMediaItems = () => {
                        normalizePricingPlansInspectorState(pricingState, block.settings, {
                            compact: false,
                            minLength: Math.max(1, pricingState.planNames.length),
                            minItems: 1,
                            maxItems: 8,
                        });
                    };

                    const syncMediaItems = (refreshInspector) => {
                        normalizeMediaItems();
                        updateSettings(block.id, buildPricingPlansInspectorPatch(pricingState));
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeMediaItems();

                    const body = document.createElement('div');
                    body.className = 'pb-feature-grid-repeater-body pb-feature-grid-media-body';

                    const list = document.createElement('div');
                    list.className = 'pb-feature-grid-media-list';
                    const primitives = window.FlatCMSUIPrimitives || {};
                    const chooseIconLabel = label('chooseIcon', '');
                    const removeIconLabel = label('removeIcon', '');

                    for (let itemIndex = 0; itemIndex < pricingState.planNames.length; itemIndex += 1) {
                        const nameBadge = document.createElement('span');
                        nameBadge.className = 'pb-feature-grid-media-cell pb-pricing-plans-item-label';
                        nameBadge.textContent = String(pricingState.planNames[itemIndex] || '').trim() || `${iconItemLabel} ${itemIndex + 1}`;
                        const inputAriaLabel = iconItemLabel !== '' ? `${iconItemLabel} ${itemIndex + 1}` : String(itemIndex + 1);
                        let row;
                        let iconInput;
                        let pickBtn;
                        let clearBtn;
                        let updatePreview;

                        if (typeof primitives.createBuilderIconPickerGridRow === 'function') {
                            const iconRow = primitives.createBuilderIconPickerGridRow({
                                rowClass: 'pb-feature-grid-media-row pb-pricing-plans-media-row',
                                leadElement: nameBadge,
                                valueInputClass: 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name',
                                value: String(pricingState.planIcons[itemIndex] || ''),
                                inputAriaLabel,
                                previewWrapClass: 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell',
                                previewClass: 'pb-icon-preview pb-feature-grid-media-preview',
                                pickButtonClass: 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker',
                                pickButtonHtml: '<i class="fas fa-icons" aria-hidden="true"></i>',
                                pickButtonTitle: chooseIconLabel,
                                clearButtonClass: 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear',
                                clearButtonHtml: '<i class="fas fa-trash" aria-hidden="true"></i>',
                                clearButtonTitle: removeIconLabel,
                            });
                            row = iconRow.element;
                            iconInput = iconRow.valueInput;
                            pickBtn = iconRow.pickButton;
                            clearBtn = iconRow.clearButton;
                            updatePreview = (nextValue) => iconRow.setValue(nextValue);
                        } else {
                            row = document.createElement('div');
                            row.className = 'pb-feature-grid-media-row pb-pricing-plans-media-row';
                            row.appendChild(nameBadge);

                            iconInput = document.createElement('input');
                            iconInput.className = 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name';
                            iconInput.type = 'text';
                            iconInput.value = String(pricingState.planIcons[itemIndex] || '');
                            iconInput.setAttribute('aria-label', inputAriaLabel);
                            row.appendChild(iconInput);

                            const iconPreviewWrap = document.createElement('div');
                            iconPreviewWrap.className = 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell';
                            const iconPreview = document.createElement('span');
                            iconPreview.className = 'pb-icon-preview pb-feature-grid-media-preview';
                            updatePreview = (nextValue) => {
                                updateIconPreview(iconPreview, nextValue);
                            };
                            updatePreview(iconInput.value);
                            iconPreviewWrap.appendChild(iconPreview);
                            row.appendChild(iconPreviewWrap);

                            pickBtn = document.createElement('button');
                            pickBtn.type = 'button';
                            pickBtn.className = 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker';
                            pickBtn.innerHTML = '<i class="fas fa-icons" aria-hidden="true"></i>';
                            pickBtn.title = chooseIconLabel;
                            pickBtn.setAttribute('aria-label', chooseIconLabel);
                            row.appendChild(pickBtn);

                            clearBtn = document.createElement('button');
                            clearBtn.type = 'button';
                            clearBtn.className = 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear';
                            clearBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
                            clearBtn.title = removeIconLabel;
                            clearBtn.setAttribute('aria-label', removeIconLabel);
                            row.appendChild(clearBtn);
                        }

                        iconInput.addEventListener('input', () => {
                            pricingState.planIcons[itemIndex] = String(iconInput.value || '').trim();
                            syncMediaItems(false);
                            updatePreview(iconInput.value);
                        });
                        iconInput.addEventListener('change', () => {
                            pricingState.planIcons[itemIndex] = String(iconInput.value || '').trim();
                            syncMediaItems(true);
                            updatePreview(iconInput.value);
                        });
                        updatePreview(iconInput.value);
                        pickBtn.addEventListener('click', () => {
                            openIconPicker(iconInput.value || '', (picked) => {
                                const nextValue = String(picked || '').trim();
                                iconInput.value = nextValue;
                                pricingState.planIcons[itemIndex] = nextValue;
                                updatePreview(nextValue);
                                syncMediaItems(true);
                            });
                        });
                        clearBtn.addEventListener('click', () => {
                            iconInput.value = '';
                            pricingState.planIcons[itemIndex] = '';
                            updatePreview('');
                            syncMediaItems(true);
                        });

                        list.appendChild(row);
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useButtonNavigationEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-feature-grid-navigation-editor', 'pb-carousel-navigation-editor', 'pb-button-navigation-editor');
                    let buttonState = applyWidgetDefaults(block.type, block.settings || {});

                    const resolveButtonState = () => ({
                        showButton: normalizeToggleSettingValue(String(buttonState.showButton || 'on'), 'on'),
                        label: String(buttonState.label || ''),
                        url: String(buttonState.url || ''),
                        target: normalizeLinkTarget(String(buttonState.target || ''), String(buttonState.url || '')),
                        variant: normalizeFeatureGridButtonVariant(String(buttonState.variant || 'primary')),
                        align: normalizeAlign(String(buttonState.align || 'left')),
                    });

                    const syncButtonState = (patch, refreshInspector) => {
                        updateSettings(block.id, patch);
                        buttonState = Object.assign({}, buttonState, patch);
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    const shell = createNavigationEditorScaffold([
                        { text: '', blank: true },
                        { text: label('fieldButtonLabel', 'Texte du bouton') },
                        { text: label('fieldUrl', 'Lien') },
                        { text: label('fieldTarget', 'Ouverture') },
                        { text: label('fieldVariant', 'Variante') },
                        { text: label('fieldAlign', 'Alignement') },
                    ], {
                        bodyClass: 'fc-builder-navigation-body pb-carousel-navigation-body pb-feature-grid-navigation-body',
                        listClass: 'fc-builder-navigation-list pb-carousel-navigation-list pb-feature-grid-navigation-list',
                        headRowClass: 'fc-builder-navigation-row fc-builder-navigation-head pb-carousel-navigation-row pb-carousel-navigation-head pb-feature-grid-navigation-head',
                        headCellClass: 'fc-builder-navigation-head-cell pb-carousel-navigation-head-cell pb-feature-grid-navigation-head-cell',
                    });
                    const body = shell.body;
                    const list = shell.list;
                    const row = createNavigationEditorRow('pb-carousel-navigation-row pb-feature-grid-navigation-row pb-button-navigation-row');
                    const currentState = resolveButtonState();

                    const toggleControl = createNavigationSwitchCell({
                        key: 'showButton',
                        label: '',
                        type: 'checkbox',
                    }, currentState.showButton, {
                        cellClass: 'pb-carousel-navigation-cell pb-feature-grid-navigation-cell pb-feature-grid-navigation-switch',
                    });
                    row.appendChild(toggleControl.element);

                    const labelInput = createNavigationInputCell({
                        className: 'form-input pb-carousel-navigation-cell pb-feature-grid-navigation-cell pb-feature-grid-navigation-input pb-feature-grid-navigation-input-label',
                        type: 'text',
                        value: currentState.label,
                        placeholder: label('defaultCallToAction', 'Découvrir'),
                    });
                    labelInput.addEventListener('input', () => {
                        syncButtonState({ label: String(labelInput.value || '') }, false);
                    });
                    labelInput.addEventListener('change', () => {
                        syncButtonState({ label: String(labelInput.value || '') }, true);
                    });
                    row.appendChild(labelInput);

                    const linkInput = createNavigationInputCell({
                        className: 'form-input pb-carousel-navigation-cell pb-feature-grid-navigation-cell pb-feature-grid-navigation-input pb-feature-grid-navigation-input-url',
                        type: 'text',
                        value: currentState.url,
                        placeholder: label('fieldCarouselLinkPlaceholder', '/page/votre-page'),
                    });
                    linkInput.addEventListener('input', () => {
                        const nextUrl = String(linkInput.value || '').trim();
                        buttonState.url = nextUrl;
                        syncButtonState({ url: nextUrl }, false);
                    });
                    linkInput.addEventListener('change', () => {
                        const nextUrl = String(linkInput.value || '').trim();
                        buttonState.url = nextUrl;
                        syncButtonState({ url: nextUrl }, true);
                    });
                    row.appendChild(linkInput);

                    const targetControl = createTargetChoiceControl(
                        currentState.target,
                        (nextValue) => {
                            const normalizedTarget = normalizeLinkTarget(String(nextValue || ''), String(buttonState.url || ''));
                            buttonState.target = normalizedTarget;
                            syncButtonState({ target: normalizedTarget }, true);
                        },
                        ['pb-carousel-navigation-cell', 'pb-feature-grid-navigation-cell', 'pb-feature-grid-navigation-target']
                    );
                    row.appendChild(targetControl);

                    const variantControl = createLayoutChoiceControl({
                        key: 'variant',
                        label: label('fieldVariant', 'Variante'),
                        type: 'select',
                        control: 'choice',
                        options: ['primary', 'secondary', 'ghost'],
                    }, currentState.variant, (nextValue, refreshInspector) => {
                        const normalizedVariant = normalizeFeatureGridButtonVariant(nextValue);
                        buttonState.variant = normalizedVariant;
                        syncButtonState({ variant: normalizedVariant }, refreshInspector !== false);
                    });
                    variantControl.classList.add('pb-carousel-navigation-cell', 'pb-feature-grid-navigation-cell', 'pb-feature-grid-navigation-variant');
                    row.appendChild(variantControl);

                    const alignControl = createAlignIconControl({
                        key: 'align',
                        label: label('fieldAlign', 'Alignement'),
                        type: 'select',
                        options: ['left', 'center', 'right'],
                    }, currentState.align, (nextValue, refreshInspector) => {
                        const normalizedAlign = normalizeAlign(nextValue);
                        buttonState.align = normalizedAlign;
                        syncButtonState({ align: normalizedAlign }, refreshInspector !== false);
                    });
                    alignControl.classList.add('pb-carousel-navigation-cell', 'pb-carousel-navigation-align', 'pb-feature-grid-navigation-cell', 'pb-feature-grid-navigation-align');
                    row.appendChild(alignControl);
                    toggleControl.input.addEventListener('change', () => {
                        const nextValue = normalizeToggleSettingValue(toggleControl.input.checked ? 'on' : 'off', 'on');
                        buttonState.showButton = nextValue;
                        syncButtonState({ showButton: nextValue }, true);
                    });

                    shell.appendItem(row, 'fc-builder-navigation-item pb-carousel-navigation-item');
                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useFeatureGridNavigationEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-feature-grid-navigation-editor', 'pb-carousel-navigation-editor', 'pb-pricing-plans-navigation-editor');
                    const delimiter = '\n';
                    const resolveFeatureGridBaseAlign = () => normalizeAlign(String(block.settings.align || 'left'));
                    let titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                    let textItems = parseFeatureGridTextValues(block.settings.texts || '');
                    let iconItems = parseRepeaterValues(block.settings.icons || '', delimiter);
                    let linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    let buttonEnabledItems = parseRepeaterValues(block.settings.buttonEnableds || '', delimiter);
                    let buttonLabelItems = parseRepeaterValues(block.settings.buttonLabels || '', delimiter);
                    let buttonTargetItems = parseRepeaterValues(block.settings.buttonTargets || '', delimiter);
                    let buttonVariantItems = parseRepeaterValues(block.settings.buttonVariants || '', delimiter);
                    let buttonAlignItems = parseRepeaterValues(block.settings.buttonAligns || '', delimiter);

                    const normalizeNavigationItems = () => {
                        const nextSettings = {
                            titles: serializeRepeaterValues(titleItems, delimiter),
                            texts: serializeFeatureGridTextValues(textItems),
                            icons: serializeRepeaterValues(iconItems, delimiter),
                            links: serializeRepeaterValues(linkItems, delimiter),
                            buttonLabel: String(block.settings.buttonLabel || ''),
                            buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                            buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                            buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                            buttonVariants: serializeRepeaterValues(buttonVariantItems, delimiter),
                            buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                        };
                        normalizeWidgetLinkedRepeaters('feature_grid', nextSettings, {
                            compact: false,
                            minLength: Math.max(1, titleItems.length),
                        });
                        titleItems = parseRepeaterValues(nextSettings.titles || '', delimiter);
                        textItems = parseFeatureGridTextValues(nextSettings.texts || '');
                        iconItems = parseRepeaterValues(nextSettings.icons || '', delimiter);
                        linkItems = parseRepeaterValues(nextSettings.links || '', delimiter);
                        buttonEnabledItems = parseRepeaterValues(nextSettings.buttonEnableds || '', delimiter);
                        buttonLabelItems = parseRepeaterValues(nextSettings.buttonLabels || '', delimiter);
                        buttonTargetItems = parseRepeaterValues(nextSettings.buttonTargets || '', delimiter);
                        buttonVariantItems = parseRepeaterValues(nextSettings.buttonVariants || '', delimiter);
                        buttonAlignItems = parseRepeaterValues(nextSettings.buttonAligns || '', delimiter);
                        const fallbackLength = Math.max(1, titleItems.length, textItems.length, iconItems.length, linkItems.length, buttonEnabledItems.length, buttonLabelItems.length, buttonTargetItems.length, buttonVariantItems.length, buttonAlignItems.length);
                        while (titleItems.length < fallbackLength) titleItems.push('');
                        while (textItems.length < fallbackLength) textItems.push('');
                        while (iconItems.length < fallbackLength) iconItems.push('');
                        while (linkItems.length < fallbackLength) linkItems.push('');
                        while (buttonEnabledItems.length < fallbackLength) buttonEnabledItems.push('off');
                        while (buttonLabelItems.length < fallbackLength) buttonLabelItems.push('');
                        while (buttonTargetItems.length < fallbackLength) buttonTargetItems.push('_self');
                        while (buttonVariantItems.length < fallbackLength) buttonVariantItems.push('ghost');
                        while (buttonAlignItems.length < fallbackLength) buttonAlignItems.push(resolveFeatureGridBaseAlign());
                    };

                    const syncNavigationItems = (refreshInspector) => {
                        normalizeNavigationItems();
                        updateSettings(block.id, {
                            links: serializeRepeaterValues(linkItems, delimiter),
                            buttonEnableds: serializeRepeaterValues(buttonEnabledItems, delimiter),
                            buttonLabels: serializeRepeaterValues(buttonLabelItems, delimiter),
                            buttonTargets: serializeRepeaterValues(buttonTargetItems, delimiter),
                            buttonVariants: serializeRepeaterValues(buttonVariantItems, delimiter),
                            buttonAligns: serializeRepeaterValues(buttonAlignItems, delimiter),
                        });
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeNavigationItems();

                    const shell = createNavigationEditorScaffold([
                        { text: '', blank: true },
                        { text: label('fieldButtonLabel', 'Texte du bouton') },
                        { text: label('fieldUrl', 'Lien') },
                        { text: label('fieldTarget', 'Ouverture') },
                        { text: label('fieldVariant', 'Variante') },
                        { text: label('fieldAlign', 'Alignement') },
                    ], {
                        bodyClass: 'fc-builder-navigation-body pb-carousel-navigation-body pb-feature-grid-navigation-body',
                        listClass: 'fc-builder-navigation-list pb-carousel-navigation-list pb-feature-grid-navigation-list',
                        headRowClass: 'fc-builder-navigation-row fc-builder-navigation-head pb-carousel-navigation-row pb-carousel-navigation-head pb-feature-grid-navigation-head',
                        headCellClass: 'fc-builder-navigation-head-cell pb-carousel-navigation-head-cell pb-feature-grid-navigation-head-cell',
                    });
                    const body = shell.body;
                    const list = shell.list;

                    for (let itemIndex = 0; itemIndex < titleItems.length; itemIndex += 1) {
                        const row = createNavigationEditorRow('pb-carousel-navigation-row pb-feature-grid-navigation-row');

                        const toggleField = {
                            key: 'buttonEnabled',
                            label: '',
                            type: 'checkbox',
                        };
                        const toggleControl = createNavigationSwitchCell(toggleField, String(buttonEnabledItems[itemIndex] || 'off'), {
                            cellClass: 'pb-carousel-navigation-cell pb-feature-grid-navigation-cell pb-feature-grid-navigation-switch',
                        });
                        toggleControl.input.addEventListener('change', () => {
                            buttonEnabledItems[itemIndex] = normalizeToggleSettingValue(toggleControl.input.checked ? 'on' : 'off', 'off');
                            syncNavigationItems(true);
                        });
                        row.appendChild(toggleControl.element);

                        const labelInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell pb-feature-grid-navigation-cell pb-feature-grid-navigation-input pb-feature-grid-navigation-input-label',
                            type: 'text',
                            value: String(buttonLabelItems[itemIndex] || ''),
                            placeholder: label('defaultCallToAction', ''),
                        });
                        labelInput.addEventListener('input', () => {
                            buttonLabelItems[itemIndex] = String(labelInput.value || '');
                            syncNavigationItems(false);
                        });
                        labelInput.addEventListener('change', () => {
                            buttonLabelItems[itemIndex] = String(labelInput.value || '');
                            syncNavigationItems(true);
                        });
                        row.appendChild(labelInput);

                        const linkInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell pb-feature-grid-navigation-cell pb-feature-grid-navigation-input pb-feature-grid-navigation-input-url',
                            type: 'text',
                            value: String(linkItems[itemIndex] || ''),
                            placeholder: label('fieldCarouselLinkPlaceholder', '/page/votre-page'),
                        });
                        linkInput.addEventListener('input', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(false);
                        });
                        linkInput.addEventListener('change', () => {
                            linkItems[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(true);
                        });
                        row.appendChild(linkInput);

                        const targetControl = createTargetChoiceControl(
                            normalizeLinkTarget(String(buttonTargetItems[itemIndex] || ''), String(linkItems[itemIndex] || '')),
                            (nextValue) => {
                                buttonTargetItems[itemIndex] = normalizeLinkTarget(String(nextValue || ''), String(linkItems[itemIndex] || ''));
                                syncNavigationItems(true);
                            },
                            ['pb-carousel-navigation-cell', 'pb-feature-grid-navigation-cell', 'pb-feature-grid-navigation-target']
                        );
                        row.appendChild(targetControl);

                        const variantValue = normalizeFeatureGridButtonVariant(String(buttonVariantItems[itemIndex] || 'ghost'));
                        const variantField = {
                            key: 'buttonVariant',
                            label: label('fieldVariant', 'Variante'),
                            type: 'select',
                            options: ['primary', 'secondary', 'ghost'],
                        };
                        const variantControl = createLayoutChoiceControl(
                            variantField,
                            variantValue,
                            (nextValue, refreshInspector) => {
                                buttonVariantItems[itemIndex] = normalizeFeatureGridButtonVariant(nextValue);
                                syncNavigationItems(refreshInspector !== false);
                            }
                        );
                        variantControl.classList.add('pb-carousel-navigation-cell', 'pb-feature-grid-navigation-cell', 'pb-feature-grid-navigation-variant');
                        row.appendChild(variantControl);

                        const alignValue = normalizeAlign(String(buttonAlignItems[itemIndex] || ''), resolveFeatureGridBaseAlign());
                        const alignField = {
                            key: 'buttonAlign',
                            label: label('fieldAlign', 'Alignement'),
                            type: 'select',
                            options: ['left', 'center', 'right'],
                        };
                        const alignControl = createAlignIconControl(
                            alignField,
                            alignValue,
                            (nextValue, refreshInspector) => {
                                buttonAlignItems[itemIndex] = normalizeAlign(nextValue, resolveFeatureGridBaseAlign());
                                syncNavigationItems(refreshInspector !== false);
                            }
                        );
                        alignControl.classList.add('pb-carousel-navigation-cell', 'pb-carousel-navigation-align', 'pb-feature-grid-navigation-cell', 'pb-feature-grid-navigation-align');
                        row.appendChild(alignControl);

                        shell.appendItem(row, 'fc-builder-navigation-item pb-carousel-navigation-item');
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (usePricingPlansNavigationEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-feature-grid-navigation-editor', 'pb-carousel-navigation-editor');
                    const pricingState = createPricingPlansInspectorState(block.settings);
                    const resolvePricingPlansBaseAlign = () => normalizeAlign(String(block.settings.align || 'left'));

                    const normalizeNavigationItems = () => {
                        normalizePricingPlansInspectorState(pricingState, block.settings, {
                            compact: false,
                            minLength: Math.max(1, pricingState.planNames.length),
                            minItems: 1,
                            maxItems: 8,
                        });
                    };

                    const syncNavigationItems = (refreshInspector) => {
                        normalizeNavigationItems();
                        updateSettings(block.id, buildPricingPlansInspectorPatch(pricingState));
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeNavigationItems();

                    const shell = createNavigationEditorScaffold([
                        { text: '', blank: true },
                        { text: label('fieldButtonLabel', 'Texte du bouton') },
                        { text: label('fieldUrl', 'Lien') },
                        { text: label('fieldTarget', 'Ouverture') },
                        { text: label('fieldVariant', 'Variante') },
                        { text: label('fieldAlign', 'Alignement') },
                    ], {
                        bodyClass: 'fc-builder-navigation-body pb-carousel-navigation-body pb-feature-grid-navigation-body',
                        listClass: 'fc-builder-navigation-list pb-carousel-navigation-list pb-feature-grid-navigation-list',
                        headRowClass: 'fc-builder-navigation-row fc-builder-navigation-head pb-carousel-navigation-row pb-carousel-navigation-head pb-feature-grid-navigation-head',
                        headCellClass: 'fc-builder-navigation-head-cell pb-carousel-navigation-head-cell pb-feature-grid-navigation-head-cell',
                    });
                    const body = shell.body;
                    const list = shell.list;

                    for (let itemIndex = 0; itemIndex < pricingState.planNames.length; itemIndex += 1) {
                        const row = createNavigationEditorRow('pb-carousel-navigation-row pb-feature-grid-navigation-row pb-pricing-plans-navigation-row');

                        const planBadge = document.createElement('span');
                        planBadge.className = 'pb-carousel-navigation-cell pb-pricing-plans-item-label';
                        planBadge.textContent = String(pricingState.planNames[itemIndex] || '').trim() || `${itemIndex + 1}`;
                        row.appendChild(planBadge);

                        const toggleField = {
                            key: 'ctaEnabled',
                            label: '',
                            type: 'checkbox',
                        };
                        const toggleControl = createNavigationSwitchCell(toggleField, String(pricingState.ctaEnableds[itemIndex] || 'on'), {
                            cellClass: 'pb-carousel-navigation-cell pb-feature-grid-navigation-cell pb-feature-grid-navigation-switch',
                            title: String(pricingState.planNames[itemIndex] || '').trim(),
                            ariaLabel: String(pricingState.planNames[itemIndex] || '').trim() || `${itemIndex + 1}`,
                        });
                        toggleControl.input.addEventListener('change', () => {
                            pricingState.ctaEnableds[itemIndex] = normalizeToggleSettingValue(toggleControl.input.checked ? 'on' : 'off', 'on');
                            syncNavigationItems(true);
                        });
                        row.appendChild(toggleControl.element);

                        const labelInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell pb-feature-grid-navigation-cell pb-feature-grid-navigation-input pb-feature-grid-navigation-input-label',
                            type: 'text',
                            value: String(pricingState.ctaLabels[itemIndex] || ''),
                            placeholder: String(pricingState.planNames[itemIndex] || '').trim() || label('defaultCallToAction', ''),
                        });
                        labelInput.addEventListener('input', () => {
                            pricingState.ctaLabels[itemIndex] = String(labelInput.value || '');
                            syncNavigationItems(false);
                        });
                        labelInput.addEventListener('change', () => {
                            pricingState.ctaLabels[itemIndex] = String(labelInput.value || '');
                            syncNavigationItems(true);
                        });
                        row.appendChild(labelInput);

                        const linkInput = createNavigationInputCell({
                            className: 'form-input pb-carousel-navigation-cell pb-feature-grid-navigation-cell pb-feature-grid-navigation-input pb-feature-grid-navigation-input-url',
                            type: 'text',
                            value: String(pricingState.ctaLinks[itemIndex] || ''),
                            placeholder: label('fieldCarouselLinkPlaceholder', '/page/votre-page'),
                        });
                        linkInput.addEventListener('input', () => {
                            pricingState.ctaLinks[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(false);
                        });
                        linkInput.addEventListener('change', () => {
                            pricingState.ctaLinks[itemIndex] = String(linkInput.value || '').trim();
                            syncNavigationItems(true);
                        });
                        row.appendChild(linkInput);

                        const targetControl = createTargetChoiceControl(
                            normalizeLinkTarget(String(pricingState.ctaTargets[itemIndex] || ''), String(pricingState.ctaLinks[itemIndex] || '')),
                            (nextValue) => {
                                pricingState.ctaTargets[itemIndex] = normalizeLinkTarget(String(nextValue || ''), String(pricingState.ctaLinks[itemIndex] || ''));
                                syncNavigationItems(true);
                            },
                            ['pb-carousel-navigation-cell', 'pb-feature-grid-navigation-cell', 'pb-feature-grid-navigation-target']
                        );
                        row.appendChild(targetControl);

                        const variantField = {
                            key: 'ctaVariant',
                            label: label('fieldVariant', 'Variante'),
                            type: 'select',
                            options: ['primary', 'secondary', 'ghost'],
                        };
                        const variantControl = createLayoutChoiceControl(
                            variantField,
                            normalizeFeatureGridButtonVariant(String(pricingState.ctaVariants[itemIndex] || 'ghost')),
                            (nextValue, refreshInspector) => {
                                pricingState.ctaVariants[itemIndex] = normalizeFeatureGridButtonVariant(nextValue);
                                syncNavigationItems(refreshInspector !== false);
                            }
                        );
                        variantControl.classList.add('pb-carousel-navigation-cell', 'pb-feature-grid-navigation-cell', 'pb-feature-grid-navigation-variant');
                        row.appendChild(variantControl);

                        const alignField = {
                            key: 'ctaAlign',
                            label: label('fieldAlign', 'Alignement'),
                            type: 'select',
                            options: ['left', 'center', 'right'],
                        };
                        const alignControl = createAlignIconControl(
                            alignField,
                            normalizeAlign(String(pricingState.ctaAligns[itemIndex] || ''), resolvePricingPlansBaseAlign()),
                            (nextValue, refreshInspector) => {
                                pricingState.ctaAligns[itemIndex] = normalizeAlign(nextValue, resolvePricingPlansBaseAlign());
                                syncNavigationItems(refreshInspector !== false);
                            }
                        );
                        alignControl.classList.add('pb-carousel-navigation-cell', 'pb-carousel-navigation-align', 'pb-feature-grid-navigation-cell', 'pb-feature-grid-navigation-align');
                        row.appendChild(alignControl);

                        shell.appendItem(row, 'fc-builder-navigation-item pb-carousel-navigation-item');
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (usePricingPlansLayoutEditor) {
                    labelEl.remove();
                    wrap.classList.add('is-wide', 'pb-carousel-navigation-editor', 'pb-pricing-plans-layout-editor');
                    const pricingState = createPricingPlansInspectorState(block.settings);
                    const itemLabel = String((field.repeater && field.repeater.itemLabel) || field.label || label('fieldLabel', 'Plan')).trim();

                    const normalizeLayoutItems = () => {
                        normalizePricingPlansInspectorState(pricingState, block.settings, {
                            compact: false,
                            minLength: Math.max(1, pricingState.planNames.length),
                            minItems: 1,
                            maxItems: 8,
                        });
                    };

                    const syncLayoutItems = (refreshInspector) => {
                        normalizeLayoutItems();
                        updateSettings(block.id, buildPricingPlansInspectorPatch(pricingState));
                        requestInteractiveSafeInspectorRefresh(refreshInspector);
                    };

                    normalizeLayoutItems();

                    const body = document.createElement('div');
                    body.className = 'pb-carousel-navigation-body';

                    const list = document.createElement('div');
                    list.className = 'pb-carousel-navigation-list';
                    const headerRow = document.createElement('div');
                    headerRow.className = 'pb-carousel-navigation-row pb-carousel-navigation-head pb-pricing-plans-layout-row pb-pricing-plans-layout-head';
                    [
                        label('fieldLabel', 'Libellé'),
                        label('fieldFeatured', 'Mis en avant'),
                    ].forEach((headerText) => {
                        const headerCell = document.createElement('div');
                        headerCell.className = 'pb-carousel-navigation-head-cell';
                        headerCell.textContent = String(headerText || '');
                        headerRow.appendChild(headerCell);
                    });
                    list.appendChild(headerRow);

                    for (let itemIndex = 0; itemIndex < pricingState.planNames.length; itemIndex += 1) {
                        const item = document.createElement('div');
                        item.className = 'pb-carousel-navigation-item';
                        const row = document.createElement('div');
                        row.className = 'pb-carousel-navigation-row pb-pricing-plans-layout-row';

                        const nameBadge = document.createElement('span');
                        nameBadge.className = 'pb-carousel-navigation-cell pb-pricing-plans-item-label';
                        nameBadge.textContent = String(pricingState.planNames[itemIndex] || '').trim() || `${itemLabel} ${itemIndex + 1}`;
                        row.appendChild(nameBadge);

                        const toggleField = {
                            key: 'featuredPlan',
                            label: '',
                            type: 'checkbox',
                        };
                        const toggleControl = createToggleSwitchControl(toggleField, String(pricingState.featuredPlans[itemIndex] || 'off'));
                        toggleControl.element.classList.add('pb-carousel-navigation-cell');
                        const toggleText = toggleControl.element.querySelector('.pb-switch-text');
                        if (toggleText) {
                            toggleText.remove();
                        }
                        toggleControl.input.setAttribute('aria-label', nameBadge.textContent);
                        toggleControl.input.addEventListener('change', () => {
                            pricingState.featuredPlans[itemIndex] = normalizeToggleSettingValue(toggleControl.input.checked ? 'on' : 'off', 'off');
                            syncLayoutItems(true);
                        });
                        row.appendChild(toggleControl.element);

                        item.appendChild(row);
                        list.appendChild(item);
                    }

                    body.appendChild(list);
                    wrap.appendChild(body);
                } else if (useLegalLinksPicker) {
                    const itemLabel = String((field.repeater && field.repeater.itemLabel) || field.label || 'Item');
                    const legalOptions = getLegalSectionLinkOptions();
                    let legalItems = parseLegalSectionLinkItems(value, legalOptions);
                    const list = document.createElement('div');
                    list.className = 'pb-repeater-list';

                    const syncLegalLinks = (refreshInspector) => {
                        const rows = legalItems.map((entry, index) => {
                            const fallback = legalOptions[index] || legalOptions[0] || { label: '', value: '/page/legal-notice' };
                            const safeUrl = normalizeLegalSectionLinkPath(entry && entry.url) || String(fallback.value || '/page/legal-notice');
                            const safeLabel = String((entry && entry.label) || '').trim() || String(fallback.label || '');
                            legalItems[index] = { label: safeLabel, url: safeUrl };
                            return `${safeLabel}|${safeUrl}`;
                        });
                        applyValue(rows.join('\n'), refreshInspector);
                    };

                    legalItems.forEach((entry, itemIndex) => {
                        const row = document.createElement('div');
                        row.className = 'pb-repeater-row is-legal-links-picker';

                        const rowLabel = document.createElement('span');
                        rowLabel.className = 'pb-repeater-label';
                        rowLabel.textContent = `${itemLabel} ${itemIndex + 1}`;
                        row.appendChild(rowLabel);

                        const textInput = document.createElement('input');
                        textInput.className = 'form-input';
                        textInput.type = 'text';
                        textInput.value = String((entry && entry.label) || '');
                        textInput.addEventListener('input', () => {
                            legalItems[itemIndex].label = String(textInput.value || '');
                            syncLegalLinks(false);
                        });
                        textInput.addEventListener('change', () => {
                            legalItems[itemIndex].label = String(textInput.value || '');
                            syncLegalLinks(true);
                        });
                        row.appendChild(textInput);

                        const targetSelect = document.createElement('select');
                        targetSelect.className = 'form-select';
                        legalOptions.forEach((optionEntry) => {
                            const option = document.createElement('option');
                            option.value = String(optionEntry.value || '');
                            option.textContent = String(optionEntry.label || optionEntry.value || '');
                            option.selected = String((entry && entry.url) || '') === option.value;
                            targetSelect.appendChild(option);
                        });
                        targetSelect.addEventListener('change', () => {
                            const nextUrl = normalizeLegalSectionLinkPath(targetSelect.value);
                            legalItems[itemIndex].url = nextUrl || String((legalOptions[itemIndex] && legalOptions[itemIndex].value) || '/page/legal-notice');
                            syncLegalLinks(true);
                        });
                        row.appendChild(targetSelect);

                        list.appendChild(row);
                    });

                    wrap.appendChild(list);
                } else {
                const repeater = field.repeater || {};
                const minItems = Math.max(0, Number(repeater.min || 0));
                const maxItems = Math.max(0, Number(repeater.max || 0));
                const delimiter = resolveRepeaterDelimiter(repeater.delimiter);
                const itemLabel = String(repeater.itemLabel || field.label || 'Item');
                const isFeatureGridLinkedRepeater = blockType === 'feature_grid'
                    && (
                        fieldKey === 'titles'
                        || fieldKey === 'texts'
                        || fieldKey === 'icons'
                        || fieldKey === 'iconenableds'
                        || fieldKey === 'iconaligns'
                        || fieldKey === 'links'
                        || fieldKey === 'buttonenableds'
                        || fieldKey === 'buttonlabels'
                        || fieldKey === 'buttontargets'
                        || fieldKey === 'buttonvariants'
                        || fieldKey === 'buttonaligns'
                    );
                const suppressInspectorRefreshOnChange = blockType === 'snap_cards' && fieldKey === 'backgrounds';
                const isFeatureGridSourceRepeater = blockType === 'feature_grid'
                    && (fieldKey === 'titles' || fieldKey === 'texts');
                const isFeatureGridMediaLinkedRepeater = blockType === 'feature_grid' && fieldKey === 'icons';
                const isFeatureGridLinkRepeater = blockType === 'feature_grid' && fieldKey === 'links';
                const isCarouselMediaLinkedRepeater = blockType === 'carousel' && fieldKey === 'images';
                const isSnapCardsMediaLinkedRepeater = blockType === 'snap_cards' && fieldKey === 'backgrounds';
                const hideLinkedRepeaterAddButton = isCarouselMediaLinkedRepeater || isSnapCardsMediaLinkedRepeater || isFeatureGridMediaLinkedRepeater;
                const featureGridLinkedKeys = ['titles', 'texts', 'icons', 'iconEnableds', 'iconAligns', 'links', 'buttonEnableds', 'buttonLabels', 'buttonTargets', 'buttonVariants', 'buttonAligns'];
                let items = parseRepeaterValues(value, delimiter);
                const resolveFeatureGridBaseAlign = () => normalizeAlign(String((block.settings && block.settings.align) || 'left'));
                let iconEnabledItems = isFeatureGridMediaLinkedRepeater
                    ? parseRepeaterValues(block.settings.iconEnableds || '', delimiter)
                    : [];
                let iconAlignItems = isFeatureGridMediaLinkedRepeater
                    ? parseRepeaterValues(block.settings.iconAligns || '', delimiter)
                    : [];
                const shouldTrimFeatureGridTrailingItems = blockType === 'feature_grid'
                    && (
                        fieldKey === 'titles'
                        || fieldKey === 'texts'
                        || fieldKey === 'icons'
                        || fieldKey === 'iconenableds'
                        || fieldKey === 'iconaligns'
                        || fieldKey === 'links'
                        || fieldKey === 'buttonenableds'
                        || fieldKey === 'buttonlabels'
                        || fieldKey === 'buttontargets'
                        || fieldKey === 'buttonvariants'
                        || fieldKey === 'buttonaligns'
                    );
                if (shouldTrimFeatureGridTrailingItems) {
                    items = trimTrailingEmptyRepeaterItems(items);
                }
                if (isFeatureGridMediaLinkedRepeater || isFeatureGridLinkRepeater) {
                    const titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                    const textItems = parseFeatureGridTextValues(block.settings.texts || '');
                    const featureGridIconEnabledItems = parseRepeaterValues(block.settings.iconEnableds || '', delimiter);
                    const featureGridIconAlignItems = parseRepeaterValues(block.settings.iconAligns || '', delimiter);
                    const targetLength = Math.max(titleItems.length, textItems.length, featureGridIconEnabledItems.length, featureGridIconAlignItems.length, items.length, minItems);
                    while (items.length < targetLength) {
                        items.push('');
                    }
                    if (isFeatureGridMediaLinkedRepeater) {
                        while (iconEnabledItems.length < targetLength) {
                            iconEnabledItems.push('on');
                        }
                        while (iconAlignItems.length < targetLength) {
                            iconAlignItems.push(resolveFeatureGridBaseAlign());
                        }
                    }
                }
                if (isCarouselMediaLinkedRepeater) {
                    const titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                    const textItems = parseRepeaterValues(block.settings.texts || '', delimiter);
                    const linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    const buttonEnabledItems = parseRepeaterValues(block.settings.buttonEnableds || '', delimiter);
                    const buttonLabelItems = parseRepeaterValues(block.settings.buttonLabels || '', delimiter);
                    const targetLength = Math.max(titleItems.length, textItems.length, linkItems.length, buttonEnabledItems.length, buttonLabelItems.length, items.length, minItems);
                    while (items.length < targetLength) {
                        items.push('');
                    }
                }
                if (isSnapCardsMediaLinkedRepeater) {
                    const titleItems = parseRepeaterValues(block.settings.titles || '', delimiter);
                    const textItems = parseRepeaterValues(block.settings.texts || '', delimiter);
                    const linkItems = parseRepeaterValues(block.settings.links || '', delimiter);
                    const ctaEnabledItems = parseRepeaterValues(block.settings.ctaEnableds || '', delimiter);
                    const ctaLabelItems = parseRepeaterValues(block.settings.ctaLabels || '', delimiter);
                    const targetItems = parseRepeaterValues(block.settings.targets || '', delimiter);
                    const targetLength = Math.max(
                        titleItems.length,
                        textItems.length,
                        linkItems.length,
                        ctaEnabledItems.length,
                        ctaLabelItems.length,
                        targetItems.length,
                        items.length,
                        minItems
                    );
                    while (items.length < targetLength) {
                        items.push('');
                    }
                }
                while (items.length < minItems) {
                    items.push('');
                }
                if (maxItems > 0 && items.length > maxItems) {
                    items = items.slice(0, maxItems);
                }

                const list = document.createElement('div');
                list.className = 'pb-repeater-list';

                const normalizeFeatureGridLinkedStateAfterRemove = () => {
                    if (!isFeatureGridLinkedRepeater || !block || !block.settings || typeof block.settings !== 'object') {
                        return;
                    }

                    normalizeWidgetLinkedRepeaters(block.type, block.settings, { compact: true });

                    featureGridLinkedKeys.forEach((linkedKey) => {
                        if (!Object.prototype.hasOwnProperty.call(block.settings, linkedKey)) {
                            return;
                        }
                        updateSetting(block.id, linkedKey, String(block.settings[linkedKey] || ''));
                    });

                    items = parseRepeaterValues(block.settings[fieldKey] || '', delimiter);
                    if (isFeatureGridMediaLinkedRepeater) {
                        iconEnabledItems = parseRepeaterValues(block.settings.iconEnableds || '', delimiter);
                        iconAlignItems = parseRepeaterValues(block.settings.iconAligns || '', delimiter);
                    }
                    while (items.length < minItems) {
                        items.push('');
                    }
                    if (maxItems > 0 && items.length > maxItems) {
                        items = items.slice(0, maxItems);
                    }
                    if (isFeatureGridMediaLinkedRepeater) {
                        while (iconEnabledItems.length < items.length) {
                            iconEnabledItems.push('on');
                        }
                        while (iconAlignItems.length < items.length) {
                            iconAlignItems.push(resolveFeatureGridBaseAlign());
                        }
                        if (maxItems > 0 && iconEnabledItems.length > maxItems) {
                            iconEnabledItems = iconEnabledItems.slice(0, maxItems);
                        }
                        if (maxItems > 0 && iconAlignItems.length > maxItems) {
                            iconAlignItems = iconAlignItems.slice(0, maxItems);
                        }
                    }
                };

                const syncRepeater = (refreshInspector) => {
                    let nextItems = items.slice();
                    while (nextItems.length < minItems) {
                        nextItems.push('');
                    }
                    if (maxItems > 0 && nextItems.length > maxItems) {
                        nextItems = nextItems.slice(0, maxItems);
                    }
                    applyValue(serializeRepeaterValues(nextItems, delimiter), refreshInspector);
                };
                const syncFeatureGridMediaRepeater = (refreshInspector) => {
                    let nextItems = items.slice();
                    while (nextItems.length < minItems) {
                        nextItems.push('');
                    }
                    if (maxItems > 0 && nextItems.length > maxItems) {
                        nextItems = nextItems.slice(0, maxItems);
                    }
                    let nextIconEnabledItems = iconEnabledItems.slice();
                    while (nextIconEnabledItems.length < nextItems.length) {
                        nextIconEnabledItems.push('on');
                    }
                    if (maxItems > 0 && nextIconEnabledItems.length > maxItems) {
                        nextIconEnabledItems = nextIconEnabledItems.slice(0, maxItems);
                    }
                    let nextIconAlignItems = iconAlignItems.slice();
                    while (nextIconAlignItems.length < nextItems.length) {
                        nextIconAlignItems.push(resolveFeatureGridBaseAlign());
                    }
                    if (maxItems > 0 && nextIconAlignItems.length > maxItems) {
                        nextIconAlignItems = nextIconAlignItems.slice(0, maxItems);
                    }
                    items = nextItems;
                    iconEnabledItems = nextIconEnabledItems;
                    iconAlignItems = nextIconAlignItems;
                    updateSettings(block.id, {
                        icons: serializeRepeaterValues(nextItems, delimiter),
                        iconEnableds: serializeRepeaterValues(nextIconEnabledItems, delimiter),
                        iconAligns: serializeRepeaterValues(nextIconAlignItems, delimiter),
                    });
                    requestInteractiveSafeInspectorRefresh(refreshInspector);
                };
                const syncFeatureGridPeerRepeaters = (action, itemIndex) => {
                    if (!isFeatureGridSourceRepeater || !def || !Array.isArray(def.fields)) {
                        return;
                    }

                    const peerKeys = ['titles', 'texts', 'icons', 'iconEnableds', 'iconAligns', 'links', 'buttonEnableds', 'buttonLabels', 'buttonTargets', 'buttonVariants', 'buttonAligns'].filter((key) => key.toLowerCase() !== fieldKey);
                    peerKeys.forEach((peerKey) => {
                        const peerField = def.fields.find((candidate) => {
                            const candidateKey = String((candidate && candidate.key) || '').trim().toLowerCase();
                            return candidateKey === peerKey;
                        });
                        if (!peerField || !peerField.repeater || !peerField.repeater.enabled) {
                            return;
                        }

                        const peerRepeater = peerField.repeater || {};
                        const peerDelimiter = resolveRepeaterDelimiter(peerRepeater.delimiter);
                        const peerMin = Math.max(0, Number(peerRepeater.min || 0));
                        const peerMax = Math.max(0, Number(peerRepeater.max || 0));
                        let peerItems = parseRepeaterValues(block.settings[peerKey] || '', peerDelimiter);

                        if (action === 'add') {
                            const peerRaw = String(block.settings[peerKey] || '').trim();
                            const shouldExpandPeer = peerItems.length > 0 || peerRaw !== '';
                            if (!shouldExpandPeer) {
                                return;
                            }
                            const targetLength = items.length;
                            while (peerItems.length < targetLength) {
                                if (peerKey === 'iconAligns') {
                                    peerItems.push(resolveFeatureGridBaseAlign());
                                } else if (peerKey === 'iconEnableds') {
                                    peerItems.push('on');
                                } else {
                                    peerItems.push('');
                                }
                            }
                        } else if (action === 'remove') {
                            if (itemIndex >= 0 && itemIndex < peerItems.length) {
                                peerItems.splice(itemIndex, 1);
                            }
                            if (peerItems.length > items.length) {
                                peerItems = peerItems.slice(0, items.length);
                            }
                        }

                        while (peerItems.length < peerMin) {
                            peerItems.push('');
                        }
                        if (peerMax > 0 && peerItems.length > peerMax) {
                            peerItems = peerItems.slice(0, peerMax);
                        }

                        updateSetting(block.id, peerKey, serializeRepeaterValues(peerItems, peerDelimiter));
                    });
                };

                const openFeatureGridLinkedMediaPicker = () => {
                    if (!isFeatureGridMediaLinkedRepeater) {
                        return false;
                    }
                    if (!Array.isArray(items) || items.length === 0) {
                        return false;
                    }
                    const emptyIndex = items.findIndex((entry) => String(entry || '').trim() === '');
                    const targetIndex = emptyIndex >= 0 ? emptyIndex : Math.max(0, items.length - 1);
                    openIconPicker(items[targetIndex] || '', (picked) => {
                        const nextValue = String(picked || '').trim();
                        items[targetIndex] = nextValue;
                        if (targetIndex >= iconEnabledItems.length) {
                            iconEnabledItems[targetIndex] = 'on';
                        }
                        if (targetIndex >= iconAlignItems.length) {
                            iconAlignItems[targetIndex] = resolveFeatureGridBaseAlign();
                        }
                        syncFeatureGridMediaRepeater(true);
                    });
                    return true;
                };

                const renderRepeaterRows = () => {
                    list.innerHTML = '';
                    items.forEach((itemValue, itemIndex) => {
                        const row = document.createElement('div');
                        row.className = 'pb-repeater-row';
                        const useFeatureGridMediaThirds = blockType === 'feature_grid' && fieldKey === 'icons';
                        if (useFeatureGridMediaThirds) {
                            row.classList.add('is-feature-grid-media-thirds');
                        }
                        if (field.iconPicker && !useFeatureGridMediaThirds) {
                            row.classList.add('is-icon-repeater');
                        }
                        if (field.media && !useFeatureGridMediaThirds) {
                            row.classList.add('is-media-repeater');
                        }

                        const rowLabel = document.createElement('span');
                        rowLabel.className = 'pb-repeater-label';
                        rowLabel.textContent = `${itemLabel} ${itemIndex + 1}`;
                        row.appendChild(rowLabel);

                        const itemType = resolveRepeaterInputType(field.type);
                        let itemInput = null;
                        if (itemType === 'textarea') {
                            const textarea = document.createElement('textarea');
                            textarea.className = 'form-input';
                            textarea.setAttribute('data-no-editor', '1');
                            textarea.rows = 2;
                            textarea.value = String(itemValue || '');
                            if (field.placeholder !== undefined) textarea.placeholder = String(field.placeholder || '');
                            itemInput = textarea;
                        } else {
                            const textInput = document.createElement('input');
                            textInput.className = 'form-input';
                            textInput.type = itemType;
                            textInput.value = String(itemValue || '');
                            if (field.min !== undefined && (itemType === 'number' || itemType === 'range')) textInput.min = String(field.min);
                            if (field.max !== undefined && (itemType === 'number' || itemType === 'range')) textInput.max = String(field.max);
                            if (field.step !== undefined && (itemType === 'number' || itemType === 'range')) textInput.step = String(field.step);
                            if (field.placeholder !== undefined) textInput.placeholder = String(field.placeholder || '');
                            itemInput = textInput;
                        }

                        const useLinkedMediaGridRow = (isCarouselMediaLinkedRepeater || isSnapCardsMediaLinkedRepeater)
                            && !!field.media
                            && itemInput instanceof HTMLInputElement;

                        const commitRepeaterItemValue = (nextValue, refreshInspector) => {
                            if (!row.isConnected) {
                                return;
                            }
                            if (itemIndex < 0 || itemIndex >= items.length) {
                                return;
                            }
                            const resolvedValue = nextValue !== undefined && nextValue !== null ? nextValue : itemInput.value;
                            items[itemIndex] = String(resolvedValue || '');
                            if (isFeatureGridMediaLinkedRepeater) {
                                syncFeatureGridMediaRepeater(refreshInspector);
                            } else {
                                syncRepeater(refreshInspector);
                            }
                        };

                        const attachRepeaterStandardListeners = () => {
                            itemInput.addEventListener('input', () => {
                                commitRepeaterItemValue(itemInput.value, false);
                            });
                            itemInput.addEventListener('change', () => {
                                commitRepeaterItemValue(itemInput.value, !suppressInspectorRefreshOnChange);
                            });
                        };

                        const useRepeaterInlineWysiwyg = itemInput instanceof HTMLTextAreaElement && shouldUseInlineWysiwyg(block, field);
                        if (useRepeaterInlineWysiwyg) {
                            const ready = initMinimalWysiwygField(
                                itemInput,
                                (nextHtml) => commitRepeaterItemValue(nextHtml, false),
                                (nextHtml) => commitRepeaterItemValue(nextHtml, false),
                                resolveInlineWysiwygOptions(field)
                            );
                            if (!ready) {
                                attachRepeaterStandardListeners();
                            }
                        } else {
                            attachRepeaterStandardListeners();
                        }
                        if (useLinkedMediaGridRow) {
                            row.className = [
                                'pb-feature-grid-media-row',
                                isCarouselMediaLinkedRepeater ? 'pb-carousel-media-row' : '',
                                isSnapCardsMediaLinkedRepeater ? 'pb-snap-cards-media-row' : '',
                            ].filter(Boolean).join(' ');

                            const nameInput = document.createElement('input');
                            nameInput.className = 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name';
                            nameInput.type = 'text';
                            nameInput.value = `${itemLabel} ${itemIndex + 1}`;
                            nameInput.readOnly = true;
                            row.replaceChild(nameInput, rowLabel);

                            itemInput.className = 'form-input pb-feature-grid-media-cell pb-feature-grid-media-name';
                            itemInput.type = 'text';
                            itemInput.placeholder = field.placeholder !== undefined
                                ? String(field.placeholder || '')
                                : label('chooseImage', 'Choose image');
                        }

                        let itemInputWrap = null;
                        if (useFeatureGridMediaThirds) {
                            itemInputWrap = document.createElement('div');
                            itemInputWrap.className = 'pb-feature-grid-media-input';
                            itemInputWrap.appendChild(itemInput);
                            row.appendChild(itemInputWrap);
                        } else {
                            row.appendChild(itemInput);
                        }

                        let actionsWrap = null;
                        if (useFeatureGridMediaThirds) {
                            actionsWrap = document.createElement('div');
                            actionsWrap.className = 'pb-feature-grid-media-actions';
                        }

                        if (field.iconPicker) {
                            const iconRow = document.createElement('div');
                            iconRow.className = 'pb-field-row pb-icon-row';

                            const iconPreview = document.createElement('span');
                            iconPreview.className = 'pb-icon-preview';
                            updateIconPreview(iconPreview, itemInput.value);

                            const pickBtn = document.createElement('button');
                            pickBtn.type = 'button';
                            pickBtn.className = 'btn btn-secondary btn-sm';
                            pickBtn.innerHTML = `<i class="fas fa-icons"></i> ${escapeHtml(label('chooseIcon', 'Choose icon'))}`;
                            pickBtn.addEventListener('click', () => {
                                openIconPicker(itemInput.value || '', (picked) => {
                                    const nextValue = String(picked || '').trim();
                                    itemInput.value = nextValue;
                                    items[itemIndex] = nextValue;
                                    if (itemIndex >= iconAlignItems.length) {
                                        iconAlignItems[itemIndex] = resolveFeatureGridBaseAlign();
                                    }
                                    syncFeatureGridMediaRepeater(true);
                                    updateIconPreview(iconPreview, nextValue);
                                });
                            });

                            const clearBtn = document.createElement('button');
                            clearBtn.type = 'button';
                            clearBtn.className = 'btn btn-ghost btn-sm';
                            clearBtn.innerHTML = `<i class="fas fa-times"></i> ${escapeHtml(label('removeIcon', 'Remove icon'))}`;
                            clearBtn.addEventListener('click', () => {
                                itemInput.value = '';
                                items[itemIndex] = '';
                                syncFeatureGridMediaRepeater(true);
                                updateIconPreview(iconPreview, '');
                            });

                            itemInput.addEventListener('input', () => {
                                updateIconPreview(iconPreview, itemInput.value);
                            });

                            if (useFeatureGridMediaThirds) {
                                iconRow.appendChild(iconPreview);
                            } else if (itemInputWrap) {
                                itemInputWrap.appendChild(iconPreview);
                            } else {
                                iconRow.appendChild(iconPreview);
                            }
                            iconRow.appendChild(pickBtn);
                            if (!useFeatureGridMediaThirds) {
                                iconRow.appendChild(clearBtn);
                            }
                            if (actionsWrap) {
                                actionsWrap.appendChild(iconRow);
                            } else {
                                row.appendChild(iconRow);
                            }

                            if (isFeatureGridMediaLinkedRepeater) {
                                const iconAlignField = {
                                    key: 'iconAlign',
                                    label: label('fieldAlign', 'Alignement'),
                                    type: 'select',
                                    options: ['left', 'center', 'right'],
                                };
                                const iconAlignControl = createAlignIconControl(
                                    iconAlignField,
                                    normalizeAlign(String(iconAlignItems[itemIndex] || ''), resolveFeatureGridBaseAlign()),
                                    (nextValue, refreshInspector) => {
                                        iconAlignItems[itemIndex] = normalizeAlign(nextValue, resolveFeatureGridBaseAlign());
                                        syncFeatureGridMediaRepeater(refreshInspector !== false);
                                    }
                                );
                                iconAlignControl.classList.add('pb-feature-grid-media-align');
                                row.appendChild(iconAlignControl);
                            }
                        }

                        if (field.media) {
                            const mediaOptions = normalizeMediaFieldOptions(field.media);
                            if (useLinkedMediaGridRow) {
                                const previewWrap = document.createElement('div');
                                previewWrap.className = 'pb-feature-grid-media-cell pb-feature-grid-media-preview-cell';

                                const preview = document.createElement('img');
                                preview.className = 'pb-feature-grid-media-preview';
                                preview.alt = '';
                                previewWrap.appendChild(preview);

                                const updatePreview = (nextValue) => {
                                    setInspectorMediaImagePreview(preview, nextValue);
                                };

                                const pickBtn = document.createElement('button');
                                pickBtn.type = 'button';
                                pickBtn.className = 'btn btn-secondary btn-sm pb-feature-grid-media-cell pb-feature-grid-media-picker';
                                pickBtn.innerHTML = '<i class="fas fa-image" aria-hidden="true"></i>';
                                pickBtn.title = mediaOptions.mode === 'images'
                                    ? label('chooseImage', 'Choose image')
                                    : label('chooseMedia', 'Choose file');
                                pickBtn.setAttribute('aria-label', pickBtn.title);
                                pickBtn.addEventListener('click', () => {
                                    openMediaPicker((file) => {
                                        if (!file) return;
                                        const srcValue = String(file.path || file.url || '').trim();
                                        if (!srcValue) return;
                                        itemInput.value = srcValue;
                                        items[itemIndex] = srcValue;
                                        updatePreview(srcValue);
                                        syncRepeater(!suppressInspectorRefreshOnChange);
                                    }, mediaOptions);
                                });

                                const clearBtn = document.createElement('button');
                                clearBtn.type = 'button';
                                clearBtn.className = 'btn btn-ghost btn-sm pb-feature-grid-media-cell pb-feature-grid-media-clear';
                                clearBtn.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
                                clearBtn.title = label('removeMedia', 'Remove media');
                                clearBtn.setAttribute('aria-label', clearBtn.title);
                                const syncClearButton = () => {
                                    clearBtn.disabled = String(itemInput.value || '').trim() === '';
                                };
                                clearBtn.addEventListener('click', () => {
                                    if (clearBtn.disabled) {
                                        return;
                                    }
                                    confirmDeleteAction(
                                        label('removeMediaConfirm', 'Delete this media?'),
                                        () => {
                                            itemInput.value = '';
                                            items[itemIndex] = '';
                                            updatePreview('');
                                            syncRepeater(!suppressInspectorRefreshOnChange);
                                            syncClearButton();
                                            const toast = window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function'
                                                ? window.FlatCMS.toast
                                                : null;
                                            if (toast) {
                                                toast.show(label('mediaRemoved', 'Media removed.'), 'success');
                                            }
                                        },
                                        {
                                            confirmText: label('confirmDelete', 'Supprimer'),
                                        }
                                    );
                                });
                                itemInput.addEventListener('input', () => {
                                    updatePreview(itemInput.value);
                                    syncClearButton();
                                });
                                itemInput.addEventListener('change', () => {
                                    updatePreview(itemInput.value);
                                    syncClearButton();
                                });
                                updatePreview(itemInput.value);
                                syncClearButton();

                                row.appendChild(previewWrap);
                                row.appendChild(pickBtn);
                                row.appendChild(clearBtn);
                            } else {
                            const mediaRow = document.createElement('div');
                            mediaRow.className = 'pb-field-row pb-field-row-media';

                            const mediaBtn = document.createElement('button');
                            mediaBtn.type = 'button';
                            mediaBtn.className = 'btn btn-secondary btn-sm';
                            mediaBtn.textContent = mediaOptions.mode === 'images'
                                ? label('chooseImage', 'Choose image')
                                : label('chooseMedia', 'Choose file');
                            mediaBtn.addEventListener('click', () => {
                                openMediaPicker((file) => {
                                    if (!file) return;
                                    const srcValue = String(file.path || file.url || '').trim();
                                    if (!srcValue) return;
                                    itemInput.value = srcValue;
                                    items[itemIndex] = srcValue;
                                    syncRepeater(!suppressInspectorRefreshOnChange);
                                }, mediaOptions);
                            });

                            const clearMediaBtn = document.createElement('button');
                            clearMediaBtn.type = 'button';
                            clearMediaBtn.className = 'btn btn-ghost btn-sm';
                            clearMediaBtn.innerHTML = `<i class="fas fa-trash"></i> ${escapeHtml(label('removeMedia', 'Remove media'))}`;
                            const syncClearMediaButton = () => {
                                clearMediaBtn.disabled = String(itemInput.value || '').trim() === '';
                            };
                            clearMediaBtn.addEventListener('click', () => {
                                if (clearMediaBtn.disabled) {
                                    return;
                                }
                                confirmDeleteAction(
                                    label('removeMediaConfirm', 'Delete this media?'),
                                    () => {
                                        itemInput.value = '';
                                        items[itemIndex] = '';
                                        syncRepeater(!suppressInspectorRefreshOnChange);
                                        syncClearMediaButton();
                                        const toast = window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function'
                                            ? window.FlatCMS.toast
                                            : null;
                                        if (toast) {
                                            toast.show(label('mediaRemoved', 'Media removed.'), 'success');
                                        }
                                    },
                                    {
                                        confirmText: label('confirmDelete', 'Supprimer'),
                                    }
                                );
                            });
                            itemInput.addEventListener('input', syncClearMediaButton);
                            itemInput.addEventListener('change', syncClearMediaButton);
                            syncClearMediaButton();

                            mediaRow.appendChild(mediaBtn);
                            mediaRow.appendChild(clearMediaBtn);
                            if (actionsWrap) {
                                actionsWrap.appendChild(mediaRow);
                            } else {
                                row.appendChild(mediaRow);
                            }
                            }
                        }

                        if (!isCarouselMediaLinkedRepeater && !isFeatureGridMediaLinkedRepeater && !isSnapCardsMediaLinkedRepeater) {
                            const removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-ghost btn-sm pb-repeater-remove';
                            removeBtn.innerHTML = '<i class="fas fa-trash"></i>';
                            removeBtn.disabled = (!isFeatureGridMediaLinkedRepeater && !isFeatureGridLinkRepeater) && items.length <= minItems;
                            removeBtn.addEventListener('click', (event) => {
                                event.preventDefault();
                                event.stopPropagation();
                                if (!isFeatureGridMediaLinkedRepeater && !isFeatureGridLinkRepeater && items.length <= minItems) {
                                    return;
                                }
                                if (isFeatureGridMediaLinkedRepeater || isFeatureGridLinkRepeater) {
                                    const confirmMessage = isFeatureGridMediaLinkedRepeater
                                        ? label('removeIcon', 'Supprimer l\'icône')
                                        : label('confirmDeleteTitle', 'Confirmer la suppression');
                                    confirmDeleteAction(
                                        confirmMessage,
                                        () => {
                                            items[itemIndex] = '';
                                            if (isFeatureGridMediaLinkedRepeater) {
                                                iconAlignItems[itemIndex] = resolveFeatureGridBaseAlign();
                                                syncFeatureGridMediaRepeater(true);
                                            } else {
                                                syncRepeater(true);
                                            }
                                            const toast = window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function'
                                                ? window.FlatCMS.toast
                                                : null;
                                            if (toast) {
                                                toast.show(
                                                    isFeatureGridMediaLinkedRepeater
                                                        ? label('mediaRemoved', 'Média supprimé.')
                                                        : label('saveSuccess', 'Enregistré'),
                                                    'success'
                                                );
                                            }
                                        },
                                        {
                                            confirmText: label('confirmDelete', 'Supprimer'),
                                        }
                                    );
                                    return;
                                }
                                items.splice(itemIndex, 1);
                                syncFeatureGridPeerRepeaters('remove', itemIndex);
                                syncRepeater(false);
                                normalizeFeatureGridLinkedStateAfterRemove();
                                renderRepeaterRows();
                                renderInspector();
                            });
                            if (actionsWrap) {
                                actionsWrap.appendChild(removeBtn);
                                row.appendChild(actionsWrap);
                            } else {
                                row.appendChild(removeBtn);
                            }
                        } else if (actionsWrap) {
                            row.appendChild(actionsWrap);
                        }

                        list.appendChild(row);
                    });
                };

                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'btn btn-ghost btn-sm pb-repeater-add';
                addBtn.innerHTML = `<i class="fas fa-plus"></i> ${escapeHtml(label('add', 'Ajouter'))}`;
                addBtn.disabled = (maxItems > 0 && items.length >= maxItems) || hideLinkedRepeaterAddButton;
                addBtn.addEventListener('click', () => {
                    if (hideLinkedRepeaterAddButton) {
                        return;
                    }
                    if (maxItems > 0 && items.length >= maxItems) {
                        return;
                    }
                    if (openFeatureGridLinkedMediaPicker()) {
                        return;
                    }
                    if (isFeatureGridLinkRepeater) {
                        const emptyIndex = items.findIndex((entry) => String(entry || '').trim() === '');
                        const targetIndex = emptyIndex >= 0 ? emptyIndex : 0;
                        const rowInput = list.querySelector(`.pb-repeater-row:nth-child(${targetIndex + 1}) input.form-input`);
                        if (rowInput && typeof rowInput.focus === 'function') {
                            rowInput.focus();
                        }
                        return;
                    }
                    queueInspectorFocus('.pb-repeater-row:last-child input.form-input, .pb-repeater-row:last-child textarea.form-input, .pb-repeater-row:last-child select.form-select');
                    items.push('');
                    syncFeatureGridPeerRepeaters('add', items.length - 1);
                    renderRepeaterRows();
                    syncRepeater(true);
                });

                renderRepeaterRows();
                if (isFeatureGridRepeater) {
                    const body = document.createElement('div');
                    body.className = 'pb-feature-grid-repeater-body';
                    body.appendChild(list);
                    if (!hideLinkedRepeaterAddButton) {
                        body.appendChild(addBtn);
                    }
                    wrap.appendChild(body);
                } else if (isFeatureGridMediaLinkedRepeater || isFeatureGridLinkRepeater) {
                    const body = document.createElement('div');
                    body.className = 'pb-feature-grid-repeater-body';
                    const toggles = document.createElement('div');
                    toggles.className = 'pb-feature-grid-toggle-stack';
                    const appendFeatureGridToggle = (toggleField) => {
                        if (!toggleField) {
                            return;
                        }
                        const toggleRow = document.createElement('div');
                        toggleRow.className = 'pb-feature-grid-toggle-row';
                        const switchControl = createToggleSwitchControl(
                            toggleField,
                            block.settings[toggleField.key] !== undefined ? block.settings[toggleField.key] : ''
                        );
                        switchControl.input.addEventListener('change', () => {
                            updateSetting(
                                block.id,
                                toggleField.key,
                                normalizeToggleSettingValue(switchControl.input.checked ? 'on' : 'off', 'off')
                            );
                        });
                        toggleRow.appendChild(switchControl.element);
                        toggles.appendChild(toggleRow);
                    };
                    if (isFeatureGridMediaLinkedRepeater) {
                        const showHeaderField = Array.isArray(def.fields)
                            ? def.fields.find((candidate) => String((candidate && candidate.key) || '').trim().toLowerCase() === 'showheader')
                            : null;
                        appendFeatureGridToggle(showHeaderField);
                    }
                    if (toggles.childNodes.length > 0 && !body.contains(toggles)) {
                        body.appendChild(toggles);
                    }
                    body.appendChild(list);
                    if (!hideLinkedRepeaterAddButton && (!isFeatureGridLinkRepeater || isFeatureGridMediaLinkedRepeater)) {
                        body.appendChild(addBtn);
                    }
                    wrap.appendChild(body);
                } else if (hideLinkedRepeaterAddButton) {
                    wrap.appendChild(list);
                } else {
                    wrap.appendChild(list);
                    wrap.appendChild(addBtn);
                }
                }
            } else if (field.type === 'text_style') {
                if (isStandaloneAdvancedTextStyleField) {
                    renderedStandaloneAdvancedTextStyleGroup = true;
                    if (labelEl && typeof labelEl.remove === 'function') {
                        labelEl.remove();
                    }
                    wrap.className = 'pb-feature-grid-advanced-editor pb-carousel-advanced-editor';
                    helpTarget = wrap;

                    const cardsList = document.createElement('div');
                    cardsList.className = 'fc-builder-advanced-list pb-feature-grid-advanced-list pb-carousel-advanced-list';

                    orderedFields.forEach((candidateField) => {
                        const candidateGroupKey = resolveInspectorGroupKey(candidateField);
                        const candidateType = String((candidateField && candidateField.type) || '').trim().toLowerCase();
                        const candidateKey = String((candidateField && candidateField.key) || '').trim().toLowerCase();
                        const candidateUsesSnapCardsAdvancedCards = blockType === 'snap_cards'
                            && candidateGroupKey === 'advanced'
                            && candidateKey === 'itemtitletextstyle';
                        const candidateUsesFeatureGridAdvancedCards = blockType === 'feature_grid'
                            && candidateGroupKey === 'advanced'
                            && candidateKey === 'itemtitletextstyle';
                        const candidateUsesCarouselAdvancedCards = blockType === 'carousel'
                            && candidateGroupKey === 'advanced'
                            && candidateKey === 'itemtitletextstyle';
                        const candidateUsesNwCarrouselAdvancedCards = blockType === 'nw_carrousel'
                            && candidateGroupKey === 'advanced'
                            && candidateKey === 'itemtitletextstyle';
                        const candidateIsStandaloneAdvancedTextStyle = candidateGroupKey === 'advanced'
                            && candidateType === 'text_style'
                            && !candidateUsesSnapCardsAdvancedCards
                            && !candidateUsesFeatureGridAdvancedCards
                            && !candidateUsesCarouselAdvancedCards
                            && !candidateUsesNwCarrouselAdvancedCards;

                        if (!candidateIsStandaloneAdvancedTextStyle) {
                            return;
                        }
                        if (!isFieldVisibleForInspector(candidateField, settings)
                            && !shouldKeepConditionalFieldVisible(block, candidateField, settings)) {
                            return;
                        }
                        if (!isFieldMatchingInspectorQuery(candidateField, queryTerms)) {
                            return;
                        }

                        const candidateDisabled = isConditionalFieldDisabled(block, candidateField, settings);
                        const advancedCard = createAdvancedTextStyleCard({
                            cardClass: 'fc-builder-advanced-card pb-feature-grid-advanced-card pb-carousel-advanced-card',
                            titleClass: 'fc-builder-advanced-card-title pb-feature-grid-advanced-card-title',
                            bodyClass: 'fc-builder-advanced-card-body pb-feature-grid-advanced-card-body',
                            title: String(candidateField.label || candidateField.key || ''),
                            fieldKey: candidateField.key || '',
                        });
                        if (candidateDisabled) {
                            advancedCard.card.classList.add('is-disabled');
                        }
                        advancedCard.card.classList.add('pb-field-textstyle');
                        advancedCard.body.appendChild(createTextStyleControl(block, candidateField, (settingKey, nextValue) => {
                            updateSetting(block.id, settingKey, nextValue);
                        }));

                        if (candidateField.help !== undefined && String(candidateField.help || '').trim() !== '') {
                            const helpText = typeof primitives.createBuilderFieldHelp === 'function'
                                ? primitives.createBuilderFieldHelp({
                                    text: String(candidateField.help || '').trim(),
                                    className: 'fc-builder-field-help',
                                })
                                : document.createElement('p');
                            if (typeof primitives.createBuilderFieldHelp !== 'function') {
                                helpText.className = 'pb-field-help fc-builder-field-help';
                                helpText.textContent = String(candidateField.help || '').trim();
                            }
                            advancedCard.body.appendChild(helpText);
                        }

                        cardsList.appendChild(advancedCard.card);
                    });

                    wrap.appendChild(cardsList);
                } else {
                    wrap.classList.add('pb-field-textstyle');
                    wrap.appendChild(createTextStyleControl(block, field, (settingKey, nextValue) => {
                        updateSetting(block.id, settingKey, nextValue);
                    }));
                }
            } else if (field.type === 'select') {
                if (isAlignField) {
                    wrap.appendChild(createAlignIconControl(field, value, (nextValue, refreshInspector) => {
                        applyValue(nextValue, refreshInspector !== false);
                    }));
                } else if (isChoiceSelectField(field)) {
                    wrap.appendChild(createLayoutChoiceControl(field, value, (nextValue, refreshInspector) => {
                        applyValue(nextValue, refreshInspector !== false);
                    }));
                    customFieldHandled = true;
                } else if (useLayoutUx && isLayoutSelectField(field)) {
                    wrap.appendChild(createLayoutChoiceControl(field, value, (nextValue, refreshInspector) => {
                        applyValue(nextValue, refreshInspector !== false);
                    }));
                    customFieldHandled = true;
                } else {
                    if (typeof primitives.createBuilderSelectControl === 'function') {
                        input = primitives.createBuilderSelectControl({
                            className: 'form-select',
                            value: value,
                            options: field.options || [],
                            optionLabels: Object.fromEntries((field.options || []).map((optionValue) => [
                                String(optionValue),
                                getSelectOptionLabel(field, optionValue),
                            ])),
                        });
                    } else {
                        input = document.createElement('select');
                        input.className = 'form-select';
                        (field.options || []).forEach((optionValue) => {
                            const option = document.createElement('option');
                            option.value = optionValue;
                            option.textContent = getSelectOptionLabel(field, optionValue);
                            option.selected = String(value) === String(optionValue);
                            input.appendChild(option);
                        });
                    }
                }
            } else if (field.type === 'textarea') {
                if (typeof primitives.createBuilderTextareaControl === 'function') {
                    input = primitives.createBuilderTextareaControl({
                        className: 'form-input',
                        rows: field.rows || 5,
                        value: String(value || ''),
                        placeholder: field.placeholder !== undefined ? String(field.placeholder || '') : undefined,
                        noEditor: true,
                    });
                } else {
                    input = document.createElement('textarea');
                    input.className = 'form-input';
                    input.setAttribute('data-no-editor', '1');
                    input.rows = field.rows || 5;
                    input.value = String(value || '');
                    if (field.placeholder !== undefined) input.placeholder = String(field.placeholder || '');
                }
            } else if (field.type === 'checkbox') {
                const switchControl = createToggleSwitchControl(field, value);
                input = switchControl.input;
                wrap.appendChild(switchControl.element);
            } else if (field.type === 'color') {
                const colorControl = createColorControl(value, (nextColor) => {
                    applyValue(nextColor);
                });
                wrap.appendChild(colorControl);
            } else if (useSectionRangeUx) {
                const rangeControl = createLayoutRangeControl(field, value, (nextValue, refreshInspector) => {
                    applyValue(nextValue, refreshInspector !== false);
                });
                input = rangeControl.input;
                customFieldController = rangeControl;
                customFieldHandled = true;
                wrap.appendChild(rangeControl.element);
            } else {
                if (typeof primitives.createBuilderInputControl === 'function') {
                    input = primitives.createBuilderInputControl({
                        className: 'form-input',
                        type: field.type || 'text',
                        value: String(value || ''),
                        min: field.min,
                        max: field.max,
                        step: field.step,
                        placeholder: field.placeholder !== undefined ? String(field.placeholder || '') : undefined,
                    });
                } else {
                    input = document.createElement('input');
                    input.className = 'form-input';
                    input.type = field.type || 'text';
                    input.value = String(value || '');
                    if (field.min !== undefined) input.min = String(field.min);
                    if (field.max !== undefined) input.max = String(field.max);
                    if (field.step !== undefined) input.step = String(field.step);
                    if (field.placeholder !== undefined) input.placeholder = String(field.placeholder || '');
                }
            }

            const useInlineWysiwyg = field.type === 'textarea' && shouldUseInlineWysiwyg(block, field);
            if (input) {
                if (field.required) {
                    input.required = true;
                }
                if (fieldDisabled) {
                    input.disabled = true;
                    if (customFieldController && typeof customFieldController.setDisabled === 'function') {
                        customFieldController.setDisabled(true);
                    }
                }
                const readInputValue = () => {
                    if (field.type === 'checkbox' && input instanceof HTMLInputElement) {
                        return input.checked ? 'on' : 'off';
                    }
                    return input.value;
                };
                const commitInputValue = (refreshInspector) => applyValue(readInputValue(), refreshInspector);
                const attachStandardInputListeners = () => {
                    input.addEventListener('change', () => commitInputValue(true));
                    input.addEventListener('input', () => commitInputValue(false));
                };
                if (!customFieldHandled && field.type === 'select') {
                    input.addEventListener('change', () => commitInputValue(true));
                    input.addEventListener('input', () => commitInputValue(false));
                } else if (!customFieldHandled && !useInlineWysiwyg && !useLinksQuickAdd) {
                    attachStandardInputListeners();
                }
                if (!customFieldHandled && !useLinksQuickAdd && field.type !== 'checkbox') {
                    wrap.appendChild(input);
                } else if (input instanceof HTMLTextAreaElement) {
                    input.classList.add('pb-links-source-input');
                    input.setAttribute('aria-hidden', 'true');
                    input.tabIndex = -1;
                }

                if (useInlineWysiwyg) {
                    input.setAttribute('data-no-editor', '1');
                    const ready = initMinimalWysiwygField(
                        input,
                        (nextHtml) => applyValue(nextHtml, false),
                        (nextHtml) => applyValue(nextHtml, false),
                        resolveInlineWysiwygOptions(field)
                    );
                    if (!ready) {
                        attachStandardInputListeners();
                    }
                }

                if (useLinksQuickAdd && input instanceof HTMLTextAreaElement) {
                    const quickAddPanel = createLinksQuickAddPanel(input, (nextValue, refreshInspector = false) => {
                        applyValue(nextValue, refreshInspector);
                    });
                    if (quickAddPanel) {
                        wrap.appendChild(quickAddPanel);
                    }
                }
            }

            if (field.responsive && !isRepeaterField && !field.media && !field.iconPicker) {
                const variants = [
                    { suffix: 'tablet', label: label('responsiveTablet', 'Tablet') },
                    { suffix: 'mobile', label: label('responsiveMobile', 'Mobile') },
                ];
                const createResponsiveVariantControl = (variant) => {
                    const responsiveKey = `${field.key}_${variant.suffix}`;
                    const responsiveValue = block.settings[responsiveKey] !== undefined ? block.settings[responsiveKey] : '';

                    let variantInput = null;
                    if (field.type === 'select') {
                        if (isAlignField) {
                            return createAlignIconControl(field, responsiveValue, (nextValue, refreshInspector) => {
                                updateSetting(block.id, responsiveKey, normalizeFieldValue(field, nextValue));
                                requestFieldCommitInspectorRefresh(field, refreshInspector !== false);
                            });
                        }
                        if (typeof primitives.createBuilderSelectControl === 'function') {
                            variantInput = primitives.createBuilderSelectControl({
                                className: 'form-select',
                                value: responsiveValue,
                                options: field.options || [],
                                optionLabels: Object.fromEntries((field.options || []).map((optionValue) => [
                                    String(optionValue),
                                    getSelectOptionLabel(field, optionValue),
                                ])),
                            });
                        } else {
                            const select = document.createElement('select');
                            select.className = 'form-select';
                            (field.options || []).forEach((optionValue) => {
                                const option = document.createElement('option');
                                option.value = optionValue;
                                option.textContent = getSelectOptionLabel(field, optionValue);
                                option.selected = String(responsiveValue) === String(optionValue);
                                select.appendChild(option);
                            });
                            variantInput = select;
                        }
                    } else if (field.type === 'textarea') {
                        if (typeof primitives.createBuilderTextareaControl === 'function') {
                            variantInput = primitives.createBuilderTextareaControl({
                                className: 'form-input',
                                rows: Math.max(2, Math.min(6, Number(field.rows || 3))),
                                value: String(responsiveValue || ''),
                                placeholder: field.placeholder !== undefined ? String(field.placeholder || '') : undefined,
                                noEditor: true,
                            });
                        } else {
                            const textarea = document.createElement('textarea');
                            textarea.className = 'form-input';
                            textarea.setAttribute('data-no-editor', '1');
                            textarea.rows = Math.max(2, Math.min(6, Number(field.rows || 3)));
                            textarea.value = String(responsiveValue || '');
                            if (field.placeholder !== undefined) textarea.placeholder = String(field.placeholder || '');
                            variantInput = textarea;
                        }
                    } else {
                        if (typeof primitives.createBuilderInputControl === 'function') {
                            variantInput = primitives.createBuilderInputControl({
                                className: 'form-input',
                                type: field.type || 'text',
                                value: String(responsiveValue || ''),
                                min: field.min !== undefined && (field.type === 'number' || field.type === 'range') ? field.min : undefined,
                                max: field.max !== undefined && (field.type === 'number' || field.type === 'range') ? field.max : undefined,
                                step: field.step !== undefined && (field.type === 'number' || field.type === 'range') ? field.step : undefined,
                                placeholder: field.placeholder !== undefined ? String(field.placeholder || '') : undefined,
                            });
                        } else {
                            const responsiveInput = document.createElement('input');
                            responsiveInput.className = 'form-input';
                            responsiveInput.type = field.type || 'text';
                            responsiveInput.value = String(responsiveValue || '');
                            if (field.min !== undefined && (field.type === 'number' || field.type === 'range')) responsiveInput.min = String(field.min);
                            if (field.max !== undefined && (field.type === 'number' || field.type === 'range')) responsiveInput.max = String(field.max);
                            if (field.step !== undefined && (field.type === 'number' || field.type === 'range')) responsiveInput.step = String(field.step);
                            if (field.placeholder !== undefined) responsiveInput.placeholder = String(field.placeholder || '');
                            variantInput = responsiveInput;
                        }
                    }

                    if (!variantInput) {
                        return null;
                    }

                    variantInput.addEventListener('input', () => {
                        updateSetting(block.id, responsiveKey, normalizeFieldValue(field, variantInput.value));
                    });
                    variantInput.addEventListener('change', () => {
                        updateSetting(block.id, responsiveKey, normalizeFieldValue(field, variantInput.value));
                        requestFieldCommitInspectorRefresh(field, true);
                    });

                    return variantInput;
                };

                if (typeof sharedPrimitives.createBuilderResponsiveEditor === 'function') {
                    const responsiveEditor = sharedPrimitives.createBuilderResponsiveEditor({
                        variants,
                        renderControl: createResponsiveVariantControl,
                    });
                    wrap.appendChild(responsiveEditor.element);
                } else {
                    const responsiveWrap = document.createElement('div');
                    responsiveWrap.className = 'pb-responsive-grid';

                    variants.forEach((variant) => {
                        const variantWrap = document.createElement('label');
                        variantWrap.className = 'pb-responsive-field';
                        const variantLabel = document.createElement('span');
                        variantLabel.textContent = variant.label;
                        variantWrap.appendChild(variantLabel);

                        const control = createResponsiveVariantControl(variant);
                        if (control) {
                            variantWrap.appendChild(control);
                        }

                        responsiveWrap.appendChild(variantWrap);
                    });

                    wrap.appendChild(responsiveWrap);
                }
            }

            if (field.iconPicker && input) {
                const primitives = window.FlatCMSUIPrimitives || {};
                if (typeof primitives.createBuilderIconPickerRow === 'function') {
                    const iconRow = primitives.createBuilderIconPickerRow({
                        value: input.value,
                        disabled: fieldDisabled,
                        pickButtonHtml: `<i class="fas fa-icons"></i> ${escapeHtml(label('chooseIcon', 'Choose icon'))}`,
                        clearButtonHtml: `<i class="fas fa-times"></i> ${escapeHtml(label('removeIcon', 'Remove icon'))}`,
                    });

                    iconRow.pickButton.addEventListener('click', () => {
                        if (iconRow.pickButton.disabled) {
                            return;
                        }
                        openIconPicker(input.value || '', (picked) => {
                            const nextValue = String(picked || '').trim();
                            input.value = nextValue;
                            applyValue(nextValue, true);
                            iconRow.setValue(nextValue);
                        });
                    });

                    iconRow.clearButton.addEventListener('click', () => {
                        if (iconRow.clearButton.disabled) {
                            return;
                        }
                        input.value = '';
                        applyValue('', true);
                        iconRow.setValue('');
                    });

                    input.addEventListener('input', () => {
                        iconRow.setValue(input.value);
                    });

                    wrap.appendChild(iconRow.element);
                } else {
                    const iconRow = document.createElement('div');
                    iconRow.className = 'pb-field-row pb-icon-row';

                    const iconPreview = document.createElement('span');
                    iconPreview.className = 'pb-icon-preview';
                    updateIconPreview(iconPreview, input.value);

                    const pickBtn = document.createElement('button');
                    pickBtn.type = 'button';
                    pickBtn.className = 'btn btn-secondary btn-sm';
                    pickBtn.innerHTML = `<i class="fas fa-icons"></i> ${escapeHtml(label('chooseIcon', 'Choose icon'))}`;
                    pickBtn.addEventListener('click', () => {
                        openIconPicker(input.value || '', (picked) => {
                            const nextValue = String(picked || '').trim();
                            input.value = nextValue;
                            applyValue(nextValue, true);
                            updateIconPreview(iconPreview, nextValue);
                        });
                    });

                    const clearBtn = document.createElement('button');
                    clearBtn.type = 'button';
                    clearBtn.className = 'btn btn-ghost btn-sm';
                    clearBtn.innerHTML = `<i class="fas fa-times"></i> ${escapeHtml(label('removeIcon', 'Remove icon'))}`;
                    clearBtn.addEventListener('click', () => {
                        input.value = '';
                        applyValue('', true);
                        updateIconPreview(iconPreview, '');
                    });

                    input.addEventListener('input', () => {
                        updateIconPreview(iconPreview, input.value);
                    });

                    iconRow.appendChild(iconPreview);
                    iconRow.appendChild(pickBtn);
                    iconRow.appendChild(clearBtn);
                    wrap.appendChild(iconRow);
                }
            }

            if (field.media && !isRepeaterField) {
                const mediaOptions = normalizeMediaFieldOptions(field.media);
                const primitives = window.FlatCMSUIPrimitives || {};
                if (typeof primitives.createBuilderMediaFieldControls === 'function') {
                    const mediaField = primitives.createBuilderMediaFieldControls({
                        value: value,
                        disabled: fieldDisabled,
                        previewEnabled: !!mediaOptions.preview,
                        mediaOptions: mediaOptions,
                        resolveSrc: resolveMediaSrc,
                        noMediaLabel: label('noMediaSelected', 'Aucun fichier sélectionné'),
                        pickButtonText: mediaOptions.mode === 'images'
                            ? label('chooseImage', 'Choose image')
                            : label('chooseMedia', 'Choose file'),
                        clearButtonHtml: `<i class="fas fa-trash"></i> ${escapeHtml(label('removeMedia', 'Remove media'))}`,
                    });

                    mediaField.pickButton.addEventListener('click', () => {
                        if (mediaField.pickButton.disabled) {
                            return;
                        }
                        openMediaPicker((file) => {
                            if (!file || !file.path) return;
                            const srcValue = String(file.path || '');
                            updateSetting(block.id, field.key, srcValue);
                            const labelField = String(mediaOptions.labelField || '');
                            if (labelField !== '') {
                                const currentLabel = String((block.settings && block.settings[labelField]) || '').trim();
                                if (currentLabel === '') {
                                    const inferredLabel = inferMediaLabel(file, srcValue);
                                    if (inferredLabel !== '') {
                                        updateSetting(block.id, labelField, inferredLabel);
                                    }
                                }
                            }
                            renderInspector();
                        }, mediaOptions);
                    });

                    mediaField.clearButton.addEventListener('click', () => {
                        if (!input || mediaField.clearButton.disabled) {
                            return;
                        }
                        confirmDeleteAction(
                            label('removeMediaConfirm', 'Delete this media?'),
                            () => {
                                input.value = '';
                                updateSetting(block.id, field.key, '');
                                mediaField.setValue('');
                                const toast = window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function'
                                    ? window.FlatCMS.toast
                                    : null;
                                if (toast) {
                                    toast.show(label('mediaRemoved', 'Media removed.'), 'success');
                                }
                                renderInspector();
                            },
                            {
                                confirmText: label('confirmDelete', 'Supprimer'),
                            }
                        );
                    });

                    if (input) {
                        input.addEventListener('input', () => {
                            mediaField.setValue(input.value);
                        });
                        input.addEventListener('change', () => {
                            mediaField.setValue(input.value);
                        });
                    }

                    if (mediaField.controls && input && input.parentNode === wrap) {
                        mediaField.controls.insertBefore(input, mediaField.row);
                    }
                    wrap.appendChild(mediaField.element);
                } else {
                    const row = document.createElement('div');
                    row.className = 'pb-field-row pb-field-row-media';

                    const pickBtn = document.createElement('button');
                    pickBtn.type = 'button';
                    pickBtn.className = 'btn btn-secondary btn-sm';
                    pickBtn.textContent = mediaOptions.mode === 'images'
                        ? label('chooseImage', 'Choose image')
                        : label('chooseMedia', 'Choose file');
                    pickBtn.addEventListener('click', () => {
                        openMediaPicker((file) => {
                            if (!file || !file.path) return;
                            const srcValue = String(file.path || '');
                            updateSetting(block.id, field.key, srcValue);
                            const labelField = String(mediaOptions.labelField || '');
                            if (labelField !== '') {
                                const currentLabel = String((block.settings && block.settings[labelField]) || '').trim();
                                if (currentLabel === '') {
                                    const inferredLabel = inferMediaLabel(file, srcValue);
                                    if (inferredLabel !== '') {
                                        updateSetting(block.id, labelField, inferredLabel);
                                    }
                                }
                            }
                            renderInspector();
                        }, mediaOptions);
                    });

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn-ghost btn-sm';
                    removeBtn.innerHTML = `<i class="fas fa-trash"></i> ${escapeHtml(label('removeMedia', 'Remove media'))}`;
                    const updateRemoveButtonState = () => {
                        removeBtn.disabled = !input || String(input.value || '').trim() === '';
                    };
                    removeBtn.addEventListener('click', () => {
                        if (!input || String(input.value || '').trim() === '') {
                            return;
                        }
                        confirmDeleteAction(
                            label('removeMediaConfirm', 'Delete this media?'),
                            () => {
                                input.value = '';
                                updateSetting(block.id, field.key, '');
                                updateRemoveButtonState();
                                const toast = window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function'
                                    ? window.FlatCMS.toast
                                    : null;
                                if (toast) {
                                    toast.show(label('mediaRemoved', 'Media removed.'), 'success');
                                }
                                renderInspector();
                            },
                            {
                                confirmText: label('confirmDelete', 'Supprimer'),
                            }
                        );
                    });

                    row.appendChild(pickBtn);
                    row.appendChild(removeBtn);
                    if (mediaOptions.preview) {
                        const mediaLayout = document.createElement('div');
                        mediaLayout.className = 'pb-field-media-layout';

                        const mediaControls = document.createElement('div');
                        mediaControls.className = 'pb-field-media-controls';
                        if (input && input.parentNode === wrap) {
                            mediaControls.appendChild(input);
                        }
                        mediaControls.appendChild(row);

                        const mediaPreview = createInspectorMediaPreview(mediaOptions, value);
                        if (input) {
                            input.addEventListener('input', () => {
                                mediaPreview.update(input.value);
                                updateRemoveButtonState();
                            });
                            input.addEventListener('change', () => {
                                mediaPreview.update(input.value);
                                updateRemoveButtonState();
                            });
                        }
                        updateRemoveButtonState();

                        mediaLayout.appendChild(mediaControls);
                        mediaLayout.appendChild(mediaPreview.element);
                        wrap.appendChild(mediaLayout);
                    } else {
                        updateRemoveButtonState();
                        wrap.appendChild(row);
                    }
                }
            }

            if (field.help !== undefined && String(field.help || '').trim() !== '') {
                const helpText = typeof primitives.createBuilderFieldHelp === 'function'
                    ? primitives.createBuilderFieldHelp({
                        text: String(field.help || '').trim(),
                        className: 'fc-builder-field-help',
                    })
                    : document.createElement('p');
                if (typeof primitives.createBuilderFieldHelp !== 'function') {
                    helpText.className = 'pb-field-help fc-builder-field-help';
                    helpText.textContent = String(field.help || '').trim();
                }
                (helpTarget || wrap).appendChild(helpText);
            }

            const container = resolveInspectorFieldContainer(
                field,
                groupContainers,
                inspector,
                String(block.type || ''),
                {
                    showStepLabels: inspectorContext === 'sheet' && activeSheetTab === 'all',
                    flattenGroups: false,
                    groupMapper: inspectorContext === 'sheet'
                        ? (groupKey) => resolveInspectorSheetTabGroup(groupKey, blockType)
                        : null,
                    labelOptions: {},
                }
            );
            container.appendChild(wrap);
            if (Array.isArray(wrap.__pbPostMountInitializers) && wrap.__pbPostMountInitializers.length) {
                wrap.__pbPostMountInitializers.forEach((initializer) => {
                    if (typeof initializer === 'function') {
                        initializer();
                    }
                });
            }
            } catch (error) {
                if (window.console && typeof window.console.error === 'function') {
                    window.console.error('[PagesBuilder] Inspector field render failed', {
                        blockType: blockType,
                        fieldKey: String((field && field.key) || ''),
                        error: error,
                    });
                }
            }
        });

        if (!hasVisibleFields) {
            const empty = document.createElement('div');
            empty.className = 'pb-inspector-empty';
            const widgetType = String((block && block.type) || '').trim().toLowerCase();
            const isFormWidget = widgetType === 'contact' || widgetType === 'newsletter';

            if (isFormWidget) {
                empty.classList.add('is-form-hint');
                const emptyText = document.createElement('p');
                emptyText.className = 'pb-field-help';
                emptyText.textContent = label(
                    'builder_form_settings_managed_in_contact',
                    ''
                );
                empty.appendChild(emptyText);

                const contactFormSettingsUrl = resolveContactFormSettingsUrl(block);
                if (contactFormSettingsUrl !== '') {
                    const openContactLink = document.createElement('a');
                    openContactLink.className = 'btn btn-secondary btn-sm';
                    openContactLink.href = contactFormSettingsUrl;
                    openContactLink.textContent = label(
                        'builder_form_settings_open_contact_module',
                        ''
                    );
                    empty.appendChild(openContactLink);
                }
            } else {
                empty.textContent = label('builder_inspector_sheet_empty', 'Aucun réglage complémentaire pour cet élément.');
            }
            inspector.appendChild(empty);
        }

        applyPendingInspectorFocus();

    }

    function resolveInspectorContext() {
        const sheetOpen = isInspectorSheetOpen();
        const inSheetBody = !!(inspector && inspectorSheetBody && inspector.parentNode === inspectorSheetBody);
        const sheetModeActive = !!(inspector && inspector.classList.contains('is-sheet-mode'));
        if (sheetOpen && inSheetBody && sheetModeActive) {
            return 'sheet';
        }
        return 'sidebar';
    }

    function buildSectionInspectorToolbar(inspectorContext) {
        const wrap = document.createElement('div');
        wrap.className = 'pb-inspector-toolbar';

        const head = document.createElement('div');
        head.className = 'pb-inspector-toolbar-head';

        const title = document.createElement('div');
        title.className = 'pb-inspector-widget-title';
        title.textContent = label('sectionSettingsTitle', 'Réglages de section');
        head.appendChild(title);
        wrap.appendChild(head);

        return wrap;
    }

    function createSectionInspectorGroup(root, groupKey, titleText) {
        const groupWrap = document.createElement('section');
        groupWrap.className = 'pb-inspector-group';
        groupWrap.dataset.group = String(groupKey || 'section');

        const groupHead = document.createElement('div');
        groupHead.className = 'pb-inspector-group-toggle';
        groupHead.innerHTML = `<span class="pb-inspector-group-title">${escapeHtml(String(titleText || ''))}</span>`;
        groupWrap.appendChild(groupHead);

        const fieldsWrap = document.createElement('div');
        fieldsWrap.className = 'pb-inspector-group-fields';
        groupWrap.appendChild(fieldsWrap);

        root.appendChild(groupWrap);
        return fieldsWrap;
    }

    function normalizeSectionInspectorTab(value) {
        const normalized = String(value || '').trim().toLowerCase();
        const allowed = ['all', 'background', 'container', 'spacing'];
        return allowed.includes(normalized) ? normalized : 'all';
    }

    function buildSectionInspectorTabbar() {
        const wrap = document.createElement('div');
        wrap.className = 'pb-inspector-tabbar';
        wrap.setAttribute('role', 'tablist');

        [
            { key: 'all', text: label('builder_inspector_sheet_tab_all', 'Tous') },
            { key: 'background', text: label('sectionBackground', 'Arrière-plan') },
            { key: 'container', text: label('sectionContainer', 'Conteneur') },
            { key: 'spacing', text: label('sectionSpacing', 'Espacement') },
        ].forEach((entry) => {
            const isActive = state.sectionInspectorTab === entry.key;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pb-inspector-tab' + (isActive ? ' is-active' : '');
            btn.setAttribute('role', 'tab');
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            btn.textContent = entry.text;
            btn.addEventListener('click', () => {
                if (state.sectionInspectorTab === entry.key) {
                    return;
                }
                state.sectionInspectorTab = entry.key;
                renderInspector();
            });
            wrap.appendChild(btn);
        });

        return wrap;
    }

    function buildSectionInspectorSidebarHint() {
        const empty = document.createElement('div');
        empty.className = 'pb-inspector-empty';

        const openBtn = document.createElement('button');
        openBtn.type = 'button';
        openBtn.className = 'btn btn-secondary btn-sm';
        openBtn.textContent = label('builder_inspector_show_full', 'Afficher tous les réglages');
        openBtn.addEventListener('click', () => {
            openInspectorSheet();
            renderInspector();
        });

        empty.appendChild(openBtn);
        return empty;
    }

    function createSectionField(labelText, fieldKey) {
        const wrap = document.createElement('div');
        wrap.className = 'pb-field';
        if (fieldKey) {
            wrap.dataset.fieldKey = String(fieldKey || '');
        }

        const title = document.createElement('label');
        title.textContent = String(labelText || '');
        wrap.appendChild(title);
        return wrap;
    }

    function buildSectionSelectField(field, currentValue, onChange) {
        const wrap = createSectionField(field.label || '', field.key || '');
        const select = document.createElement('select');
        select.className = 'form-select';

        (Array.isArray(field.options) ? field.options : []).forEach((optionValue) => {
            const option = document.createElement('option');
            option.value = String(optionValue || '');
            option.textContent = getSelectOptionLabel(field, optionValue);
            option.selected = String(currentValue || '') === String(optionValue || '');
            select.appendChild(option);
        });

        select.addEventListener('change', () => {
            if (typeof onChange === 'function') {
                onChange(select.value);
            }
        });

        wrap.appendChild(select);
        return wrap;
    }

    function buildSectionInspector(section, activeTab) {
        const panel = document.createElement('div');
        panel.className = 'pb-section-settings-panel';

        if (!section || typeof section !== 'object') {
            const empty = document.createElement('div');
            empty.className = 'pb-inspector-empty';
            empty.textContent = label('builder_inspector_sheet_empty', 'Aucun réglage complémentaire pour cet élément.');
            panel.appendChild(empty);
            return panel;
        }

        const sectionId = String(section.id || '');
        const settings = normalizeSectionSettings(section.settings || {});
        const currentTab = normalizeSectionInspectorTab(activeTab);

        const shouldRenderSectionGroup = (groupKey) => currentTab === 'all' || currentTab === String(groupKey || '');

        if (shouldRenderSectionGroup('background')) {
            const backgroundGroup = createSectionInspectorGroup(panel, 'background', label('sectionBackground', 'Arrière-plan'));

            const backgroundColorField = createSectionField(label('sectionBackgroundColor', 'Couleur de fond'), 'backgroundColor');
            backgroundColorField.classList.add('pb-section-background-color-field');
            backgroundColorField.appendChild(createColorControl(settings.backgroundColor, (nextColor) => {
                updateSectionSettings(sectionId, { backgroundColor: nextColor });
            }));

            const imageField = createSectionField(label('sectionBackgroundImage', 'Image de fond'), 'backgroundImage');
            imageField.classList.add('pb-section-background-media-field');
            const mediaLayout = document.createElement('div');
            mediaLayout.className = 'pb-section-background-path-layout';
            const mediaPathInput = document.createElement('input');
            mediaPathInput.type = 'text';
            mediaPathInput.className = 'form-input pb-section-background-path-input';
            mediaPathInput.placeholder = label('noMediaSelected', 'Aucun fichier sélectionné');
            mediaPathInput.value = String(settings.backgroundImage || '').trim();
            mediaPathInput.addEventListener('input', () => {
                const nextValue = String(mediaPathInput.value || '').trim();
                syncSectionMediaRemoveState(nextValue);
                updateSectionSettings(sectionId, { backgroundImage: nextValue });
            });
            mediaLayout.appendChild(mediaPathInput);
            imageField.appendChild(mediaLayout);

            const imageActionsField = createSectionField('\u00A0', 'backgroundImageActions');
            imageActionsField.classList.add('pb-section-background-actions-field');
            let pickBtn = null;
            let removeBtn = null;
            const syncSectionMediaRemoveState = (rawValue) => {
                if (removeBtn) {
                    removeBtn.disabled = String(rawValue || '').trim() === '';
                }
            };
            const pickImageAction = () => {
                openMediaPicker((file) => {
                    const nextValue = String(file && (file.path || file.url) || '').trim();
                    if (nextValue === '') {
                        return;
                    }
                    mediaPathInput.value = nextValue;
                    syncSectionMediaRemoveState(nextValue);
                    updateSectionSettings(sectionId, { backgroundImage: nextValue });
                }, { mode: 'images', folder: 'images', accept: 'image/*' });
            };
            const clearImageAction = () => {
                if (removeBtn && removeBtn.disabled) {
                    return;
                }
                confirmDeleteAction(
                    label('removeMediaConfirm', 'Delete this media?'),
                    () => {
                        mediaPathInput.value = '';
                        syncSectionMediaRemoveState('');
                        updateSectionSettings(sectionId, { backgroundImage: '' });
                    },
                    {
                        confirmText: label('confirmDelete', 'Supprimer'),
                    }
                );
            };
            let mediaActions = null;
            if (typeof sharedPrimitives.createBuilderActionsRow === 'function') {
                const actionsRow = sharedPrimitives.createBuilderActionsRow({
                    rowClass: 'pb-section-background-path-actions',
                    buttons: [
                        {
                            key: 'pick',
                            className: 'btn btn-secondary btn-sm',
                            label: label('chooseImage', 'Choose image'),
                            onClick: pickImageAction,
                        },
                        {
                            key: 'remove',
                            className: 'btn btn-ghost btn-sm',
                            html: `<i class="fas fa-trash"></i> ${escapeHtml(label('removeMedia', 'Remove media'))}`,
                            onClick: clearImageAction,
                        },
                    ],
                });
                mediaActions = actionsRow.element;
                pickBtn = actionsRow.buttons.pick || null;
                removeBtn = actionsRow.buttons.remove || null;
            } else {
                mediaActions = document.createElement('div');
                mediaActions.className = 'pb-section-background-path-actions';
                pickBtn = document.createElement('button');
                pickBtn.type = 'button';
                pickBtn.className = 'btn btn-secondary btn-sm';
                pickBtn.textContent = label('chooseImage', 'Choose image');
                removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-ghost btn-sm';
                removeBtn.innerHTML = `<i class="fas fa-trash"></i> ${escapeHtml(label('removeMedia', 'Remove media'))}`;
                pickBtn.addEventListener('click', pickImageAction);
                removeBtn.addEventListener('click', clearImageAction);
                mediaActions.appendChild(pickBtn);
                mediaActions.appendChild(removeBtn);
            }
            syncSectionMediaRemoveState(settings.backgroundImage);
            imageActionsField.appendChild(mediaActions);

            const overlayColorField = createSectionField(label('sectionOverlayColor', "Couleur de l'overlay"), 'overlayColor');
            overlayColorField.classList.add('pb-section-background-color-field');
            overlayColorField.appendChild(createColorControl(settings.overlayColor, (nextColor) => {
                updateSectionSettings(sectionId, { overlayColor: nextColor });
            }));

            const primaryRow = document.createElement('div');
            primaryRow.className = 'pb-section-background-row pb-section-background-row-primary';
            primaryRow.appendChild(backgroundColorField);
            primaryRow.appendChild(overlayColorField);
            primaryRow.appendChild(imageField);
            primaryRow.appendChild(imageActionsField);
            backgroundGroup.appendChild(primaryRow);

            const backgroundSizeField = buildSectionSelectField({
                key: 'sectionBackgroundSize',
                label: label('sectionBackgroundSize', 'Taille de fond'),
                options: ['cover', 'contain', 'auto'],
                optionLabels: {
                    cover: label('sectionBackgroundSizeCover', 'Couvrir'),
                    contain: label('sectionBackgroundSizeContain', 'Contenir'),
                    auto: label('sectionBackgroundSizeAuto', 'Automatique'),
                },
            }, settings.backgroundSize, (nextValue) => {
                updateSectionSettings(sectionId, { backgroundSize: nextValue });
            });

            const backgroundPositionField = buildSectionSelectField({
                key: 'sectionBackgroundPosition',
                label: label('sectionBackgroundPosition', 'Position du fond'),
                options: ['center center', 'left center', 'right center', 'center top', 'center bottom'],
                optionLabels: {
                    'center center': label('sectionBackgroundPositionCenter', 'Centre'),
                    'left center': label('sectionBackgroundPositionLeft', 'Gauche'),
                    'right center': label('sectionBackgroundPositionRight', 'Droite'),
                    'center top': label('sectionBackgroundPositionTop', 'Haut'),
                    'center bottom': label('sectionBackgroundPositionBottom', 'Bas'),
                },
            }, settings.backgroundPosition, (nextValue) => {
                updateSectionSettings(sectionId, { backgroundPosition: nextValue });
            });

            const backgroundRepeatField = buildSectionSelectField({
                key: 'sectionBackgroundRepeat',
                label: label('sectionBackgroundRepeat', 'Répétition du fond'),
                options: ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'],
                optionLabels: {
                    'no-repeat': label('sectionBackgroundRepeatNoRepeat', 'Sans répétition'),
                    repeat: label('sectionBackgroundRepeatRepeat', 'Répéter'),
                    'repeat-x': label('sectionBackgroundRepeatX', 'Répéter sur X'),
                    'repeat-y': label('sectionBackgroundRepeatY', 'Répéter sur Y'),
                },
            }, settings.backgroundRepeat, (nextValue) => {
                updateSectionSettings(sectionId, { backgroundRepeat: nextValue });
            });

            const overlayOpacityField = createSectionField(label('sectionOverlayOpacity', "Opacité de l'overlay"), 'overlayOpacity');
            overlayOpacityField.classList.add('pb-section-background-opacity-field');
            overlayOpacityField.appendChild(
                createLayoutRangeControl(
                    { min: 0, max: 100, step: 1, label: label('sectionOverlayOpacity', "Opacité de l'overlay") },
                    settings.overlayOpacity,
                    (nextValue) => {
                        updateSectionSettings(sectionId, { overlayOpacity: nextValue });
                    }
                ).element
            );

            const secondaryRow = document.createElement('div');
            secondaryRow.className = 'pb-section-background-row pb-section-background-row-secondary';
            secondaryRow.appendChild(backgroundSizeField);
            secondaryRow.appendChild(backgroundPositionField);
            secondaryRow.appendChild(backgroundRepeatField);
            secondaryRow.appendChild(overlayOpacityField);
            backgroundGroup.appendChild(secondaryRow);
        }

        if (shouldRenderSectionGroup('container')) {
            const containerGroup = createSectionInspectorGroup(panel, 'container', label('sectionContainer', 'Conteneur'));
            const containerModeField = createSectionField(label('sectionContainerMode', 'Mode du conteneur'), 'containerMode');
            containerModeField.appendChild(createLayoutChoiceControl({
                key: 'sectionContainerMode',
                label: label('sectionContainerMode', 'Mode du conteneur'),
                options: ['container', 'fluid'],
                optionLabels: {
                    container: label('sectionContainerModeContainer', 'Container'),
                    fluid: label('sectionContainerModeFluid', 'Container fluid'),
                },
            }, settings.containerMode, (nextValue) => {
                updateSectionSettings(sectionId, {
                    containerMode: nextValue,
                    containerModeExplicit: true,
                });
            }));
            containerGroup.appendChild(containerModeField);
        }

        if (shouldRenderSectionGroup('spacing')) {
            const spacingGroup = createSectionInspectorGroup(panel, 'spacing', label('sectionSpacing', 'Espacement'));
            const spacingGrid = document.createElement('div');
            spacingGrid.className = 'pb-section-spacing-grid';

            const paddingTopField = createSectionField(label('sectionPaddingTop', 'Padding haut'), 'paddingTop');
            paddingTopField.appendChild(
                createLayoutRangeControl(
                    { min: 0, max: 240, step: 4, label: label('sectionPaddingTop', 'Padding haut') },
                    settings.paddingTop,
                    (nextValue) => {
                        updateSectionSettings(sectionId, { paddingTop: nextValue });
                    }
                ).element
            );
            spacingGrid.appendChild(paddingTopField);

            const paddingBottomField = createSectionField(label('sectionPaddingBottom', 'Padding bas'), 'paddingBottom');
            paddingBottomField.appendChild(
                createLayoutRangeControl(
                    { min: 0, max: 240, step: 4, label: label('sectionPaddingBottom', 'Padding bas') },
                    settings.paddingBottom,
                    (nextValue) => {
                        updateSectionSettings(sectionId, { paddingBottom: nextValue });
                    }
                ).element
            );
            spacingGrid.appendChild(paddingBottomField);

            spacingGroup.appendChild(spacingGrid);
        }

        return panel;
    }

    function getInspectorToolbarTitleField(block, def) {
        if (!block || !def || !Array.isArray(def.fields)) {
            return null;
        }

        const settings = applyWidgetDefaults(block.type, block.settings || {});
        return def.fields.find((field) => {
            const fieldKey = String((field && field.key) || '').trim().toLowerCase();
            const fieldType = String((field && field.type) || '').trim().toLowerCase();
            if (fieldKey !== 'title') {
                return false;
            }
            if (fieldType === 'checkbox' || fieldType === 'number' || fieldType === 'range' || fieldType === 'color') {
                return false;
            }
            return isFieldVisibleForInspector(field, settings) || shouldKeepConditionalFieldVisible(block, field, settings);
        }) || null;
    }

    function buildInspectorToolbarTitleField(block, field) {
        if (!block || !field) {
            return null;
        }

        const fieldKey = String((field && field.key) || '').trim();
        if (fieldKey === '') {
            return null;
        }

        const titleLabel = String((field && field.label) || label('fieldLabel', 'Titre')).trim();
        const value = block.settings && Object.prototype.hasOwnProperty.call(block.settings, fieldKey)
            ? block.settings[fieldKey]
            : '';

        const primitives = window.FlatCMSUIPrimitives || {};
        const titleRow = typeof primitives.createBuilderInspectorToolbarTitleRow === 'function'
            ? primitives.createBuilderInspectorToolbarTitleRow({
                rowClass: 'pb-inspector-toolbar-title-row',
                inputClass: 'form-input pb-inspector-toolbar-title-input',
                value: String(value || ''),
                placeholder: titleLabel,
                title: titleLabel,
                ariaLabel: titleLabel,
            })
            : null;
        const row = titleRow ? titleRow.element : document.createElement('div');
        const input = titleRow ? titleRow.input : document.createElement('input');
        if (!titleRow) {
            row.className = 'pb-inspector-toolbar-title-row';
            input.type = 'text';
            input.className = 'form-input pb-inspector-toolbar-title-input';
            input.value = String(value || '');
            input.placeholder = titleLabel;
            input.title = titleLabel;
            input.setAttribute('aria-label', titleLabel);
            row.appendChild(input);
        }
        input.addEventListener('input', () => {
            updateSetting(block.id, fieldKey, normalizeFieldValue(field, input.value));
        });

        return row;
    }

    function buildInspectorToolbar(def, inspectorMode, inspectorContext, block) {
        const context = inspectorContext === 'sheet' ? 'sheet' : 'sidebar';
        const primitives = window.FlatCMSUIPrimitives || {};
        const buttons = [];
        if (context === 'sheet') {
            buttons.push({
                icon: 'fas fa-sliders-h',
                title: label('builder_inspector_widget_mode', 'Reglages'),
                active: inspectorMode === 'widget',
                onClick: () => {
                    if (normalizeInspectorMode(state.inspectorMode) === 'widget') return;
                    setInspectorMode('widget');
                    renderInspector();
                },
            });
            buttons.push({
                icon: 'fas fa-arrows-alt',
                title: label('spacingTitle', 'Espacement du widget'),
                active: inspectorMode === 'spacing',
                onClick: () => {
                    if (normalizeInspectorMode(state.inspectorMode) === 'spacing') return;
                    setInspectorMode('spacing');
                    renderInspector();
                },
            });
        }

        const titleField = buildInspectorToolbarTitleField(block, getInspectorToolbarTitleField(block, def));
        if (typeof primitives.createBuilderInspectorToolbar === 'function') {
            return primitives.createBuilderInspectorToolbar({
                title: String((def && def.label) || label('widget', 'Widget')),
                buttons,
                titleRowElement: titleField,
            }).element;
        }

        const wrap = document.createElement('div');
        wrap.className = 'pb-inspector-toolbar';

        const head = document.createElement('div');
        head.className = 'pb-inspector-toolbar-head';

        const title = document.createElement('div');
        title.className = 'pb-inspector-widget-title';
        title.textContent = String((def && def.label) || label('widget', 'Widget'));
        head.appendChild(title);

        if (buttons.length > 0) {
            const modeSwitch = document.createElement('div');
            modeSwitch.className = 'pb-inspector-mode-switch';
            buttons.forEach((entry) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-ghost btn-sm' + (entry.active ? ' is-active' : '');
                btn.title = entry.title;
                btn.setAttribute('aria-label', entry.title);
                btn.innerHTML = `<i class="${escapeAttr(entry.icon)}" aria-hidden="true"></i>`;
                btn.addEventListener('click', entry.onClick);
                modeSwitch.appendChild(btn);
            });
            head.appendChild(modeSwitch);
        }
        wrap.appendChild(head);

        if (titleField) {
            wrap.appendChild(titleField);
        }

        return wrap;
    }

    function buildSpacingInspector(block) {
        const box = getBlockBoxSettings(block.settings || {});
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderSpacingPanel === 'function') {
            return primitives.createBuilderSpacingPanel({
                labels: {
                    title: label('spacingTitle', 'Espacement du widget'),
                    reset: label('spacingReset', 'Réinitialiser'),
                    margin: label('spacingMargin', 'Marge'),
                    padding: label('spacingPadding', 'Padding'),
                    top: label('spacingTop', 'Haut'),
                    right: label('spacingRight', 'Droite'),
                    bottom: label('spacingBottom', 'Bas'),
                    left: label('spacingLeft', 'Gauche'),
                },
                values: box,
                onInput: (key, rawValue) => {
                    const found = findBlockLocation(block.id);
                    if (!found) return;

                    const nextBox = getBlockBoxSettings(found.block.settings || {});
                    nextBox[key] = normalizeBoxSettingValue(key, rawValue);
                    setBlockBoxSettings(found.block.settings, nextBox);
                    refreshBlockPreview(block.id);

                    if (isBoxEditorOpen()) {
                        refreshBlockBoxEditor();
                        positionBlockBoxEditor();
                    }
                },
                onReset: () => {
                    const found = findBlockLocation(block.id);
                    if (!found) return;

                    setBlockBoxSettings(found.block.settings, defaultBoxSettings());
                    renderCanvas();
                    renderInspector();
                    openInspectorSidebar();
                    focusSpacingInspectorInput('mt');
                },
            }).element;
        }

        const panel = document.createElement('div');
        panel.className = 'pb-spacing-panel';

        panel.innerHTML = `
            <div class="pb-spacing-header">
                <h4 class="pb-spacing-title">${escapeHtml(label('spacingTitle', 'Espacement du widget'))}</h4>
                <button type="button" class="btn btn-ghost btn-sm" data-action="spacing-reset">${escapeHtml(label('spacingReset', 'Réinitialiser'))}</button>
            </div>
            <div class="pb-spacing-group">
                <div class="pb-spacing-group-title">${escapeHtml(label('spacingMargin', 'Marge'))}</div>
                <div class="pb-spacing-grid">
                    ${buildSpacingField('mt', label('spacingTop', 'Haut'), false)}
                    ${buildSpacingField('mr', label('spacingRight', 'Droite'), false)}
                    ${buildSpacingField('mb', label('spacingBottom', 'Bas'), false)}
                    ${buildSpacingField('ml', label('spacingLeft', 'Gauche'), false)}
                </div>
            </div>
            <div class="pb-spacing-group">
                <div class="pb-spacing-group-title">${escapeHtml(label('spacingPadding', 'Padding'))}</div>
                <div class="pb-spacing-grid">
                    ${buildSpacingField('pt', label('spacingTop', 'Haut'), true)}
                    ${buildSpacingField('pr', label('spacingRight', 'Droite'), true)}
                    ${buildSpacingField('pb', label('spacingBottom', 'Bas'), true)}
                    ${buildSpacingField('pl', label('spacingLeft', 'Gauche'), true)}
                </div>
            </div>
        `;

        panel.querySelectorAll('[data-spacing-key]').forEach((input) => {
            const key = String(input.getAttribute('data-spacing-key') || '');
            input.value = String(box[key] ?? 0);

            input.addEventListener('input', () => {
                const found = findBlockLocation(block.id);
                if (!found) return;

                const nextBox = getBlockBoxSettings(found.block.settings || {});
                nextBox[key] = normalizeBoxSettingValue(key, input.value);
                setBlockBoxSettings(found.block.settings, nextBox);
                refreshBlockPreview(block.id);

                if (isBoxEditorOpen()) {
                    refreshBlockBoxEditor();
                    positionBlockBoxEditor();
                }
            });
        });

        const resetBtn = panel.querySelector('[data-action="spacing-reset"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                if (normalizeInspectorMode(state.inspectorMode) === 'spacing') return;
                const found = findBlockLocation(block.id);
                if (!found) return;

                setBlockBoxSettings(found.block.settings, defaultBoxSettings());
                renderCanvas();
                renderInspector();
                openInspectorSidebar();
                focusSpacingInspectorInput('mt');
            });
        }

        return panel;
    }

    function buildSpacingField(key, labelText, isPadding) {
        const min = isPadding ? 0 : -240;
        return `
            <label class="pb-spacing-field">
                <span>${escapeHtml(labelText)}</span>
                <input
                    type="number"
                    class="form-input pb-spacing-input"
                    data-spacing-key="${escapeAttr(key)}"
                    min="${min}"
                    max="240"
                    step="1"
                    aria-label="${escapeAttr(labelText)}"
                >
            </label>
        `;
    }

    function focusSpacingInspectorInput(key) {
        const targetKey = String(key || 'mt');
        window.requestAnimationFrame(() => {
            const field = inspector.querySelector(`[data-spacing-key="${escapeCssSelector(targetKey)}"]`);
            if (!field) return;
            field.focus();
            if (typeof field.select === 'function') {
                field.select();
            }
        });
    }

    function captureViewportSnapshot() {
        return {
            x: Number(window.scrollX || window.pageXOffset || 0),
            y: Number(window.scrollY || window.pageYOffset || 0),
        };
    }

    function restoreViewportSnapshot(snapshot) {
        if (!snapshot || typeof snapshot !== 'object') {
            return;
        }
        const targetX = Number(snapshot.x || 0);
        const targetY = Number(snapshot.y || 0);
        const currentX = Number(window.scrollX || window.pageXOffset || 0);
        const currentY = Number(window.scrollY || window.pageYOffset || 0);
        if (Math.abs(currentX - targetX) < 1 && Math.abs(currentY - targetY) < 1) {
            return;
        }
        window.scrollTo(targetX, targetY);
    }

    function withStableViewport(mutator) {
        const snapshot = captureViewportSnapshot();
        if (typeof mutator === 'function') {
            mutator();
        }
        const restore = () => restoreViewportSnapshot(snapshot);
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(() => {
                restore();
                window.requestAnimationFrame(restore);
            });
            return;
        }
        restore();
    }

    function updateSetting(blockId, key, value) {
        const found = findBlockLocation(blockId);
        if (!found) return;
        found.block.settings[key] = value;
        refreshBlockPreview(blockId);
    }

    function updateSettings(blockId, patch) {
        const found = findBlockLocation(blockId);
        if (!found) return;
        const safePatch = patch && typeof patch === 'object' ? patch : {};
        Object.keys(safePatch).forEach((key) => {
            found.block.settings[key] = safePatch[key];
        });
        refreshBlockPreview(blockId);
    }

    function updateSectionSettings(sectionId, patch, options) {
        const found = findSection(sectionId);
        if (!found) return null;

        const safePatch = patch && typeof patch === 'object' ? patch : {};
        found.section.settings = normalizeSectionSettings(
            Object.assign({}, found.section.settings || {}, safePatch)
        );

        const opts = options && typeof options === 'object' ? options : {};
        withStableViewport(() => {
            renderCanvas();
            if (opts.refreshInspector) {
                renderInspector();
            }
        });

        return found.section.settings;
    }

    function refreshBlockPreview(blockId) {
        const id = String(blockId || '');
        if (!id) return;

        const found = findBlockLocation(id);
        if (!found) return;

        const row = canvas.querySelector(`.pb-block-item[data-block-id="${escapeCssSelector(id)}"]`);
        if (!row) {
            withStableViewport(() => {
                renderCanvas();
            });
            return;
        }

        withStableViewport(() => {
            const preview = row.querySelector('.pb-block-preview');
            if (preview) {
                preview.innerHTML = renderBlockPreview(found.block);
                applyBlockPreviewPresentation(preview);
            }

            const boxInlineStyle = buildBlockBoxInlineStyle(found.block.settings || {});
            row.style.cssText = boxInlineStyle !== '' ? boxInlineStyle : '';
            publishBuilderState('render-block');
        });
    }

    function ensureSelection() {
        if (state.selection && state.selection.kind === 'section') {
            const stillExists = !!findSection(state.selection.sectionId);
            if (stillExists) return;
        }

        if (state.selection && state.selection.kind === 'block') {
            const stillExists = !!findBlockLocation(state.selection.blockId);
            if (stillExists) return;
        }

        const first = getFirstBlockLocation();
        if (first) {
            selectBlock(first.sectionId, first.columnId, first.blockId);
        } else {
            state.selection = null;
        }
    }

    function selectSection(sectionId) {
        state.preferredInsertTarget = null;
        const nextSectionId = String(sectionId || '');
        const previousSectionId = state.selection && state.selection.kind === 'section'
            ? String(state.selection.sectionId || '')
            : '';
        if (previousSectionId !== nextSectionId) {
            state.inspectorSearch = '';
            state.inspectorSheetTab = 'all';
            state.sectionInspectorTab = 'all';
        }
        state.selection = {
            kind: 'section',
            sectionId: nextSectionId,
        };
        setInspectorMode('widget');
    }

    function selectBlock(sectionId, columnId, blockId, inspectorMode) {
        state.preferredInsertTarget = null;
        const nextBlockId = String(blockId || '');
        const previousBlockId = state.selection && state.selection.kind === 'block'
            ? String(state.selection.blockId || '')
            : '';
        if (previousBlockId !== nextBlockId) {
            state.inspectorSearch = '';
            state.inspectorSheetTab = 'all';
        }
        state.selection = {
            kind: 'block',
            sectionId: String(sectionId || ''),
            columnId: String(columnId || ''),
            blockId: nextBlockId,
        };

        if (inspectorMode !== undefined) {
            setInspectorMode(inspectorMode);
        } else if (previousBlockId !== nextBlockId) {
            setInspectorMode('widget');
        }
    }

    function getSelectedBlock() {
        if (!state.selection || state.selection.kind !== 'block') {
            return null;
        }
        const found = findBlockLocation(state.selection.blockId);
        return found ? found.block : null;
    }

    function getSelectedSection() {
        if (!state.selection || state.selection.kind !== 'section') {
            return null;
        }
        const found = findSection(state.selection.sectionId);
        return found ? found.section : null;
    }

    function removeBlock(blockId) {
        const found = findBlockLocation(blockId);
        if (!found) return;

        const sections = getSections();
        sections[found.sectionIndex].columns[found.columnIndex].blocks.splice(found.blockIndex, 1);

        if (state.selection && state.selection.kind === 'block' && state.selection.blockId === blockId) {
            state.selection = null;
        }
        if (boxEditBlockId && String(boxEditBlockId) === String(blockId)) {
            closeBlockBoxEditor();
        }

        ensureSelection();
        renderCanvas();
        renderInspector();
    }

    function addSection(columnCount) {
        insertEmptySectionAt(getSections().length, columnCount);
        renderCanvas();
    }

    function clampSectionInsertIndex(index) {
        const sections = getSections();
        const raw = Number(index);
        if (Number.isNaN(raw)) return sections.length;
        return Math.max(0, Math.min(sections.length, Math.trunc(raw)));
    }

    function insertSectionAt(index, section) {
        const sections = getSections();
        const insertIndex = clampSectionInsertIndex(index);
        const nextSection = section && typeof section === 'object'
            ? Object.assign({}, section, {
                settings: normalizeSectionSettings(section.settings || {}),
            })
            : section;
        sections.splice(insertIndex, 0, nextSection);
        state.builder.version = 2;
        state.builder.sections = sections;
    }

    function insertEmptySectionAt(index, columnCount, layoutTemplate, sectionSettings) {
        const cols = sanitizeColumnCount(columnCount);
        const section = {
            id: makeId('sec'),
            layoutTemplate: sanitizeSectionLayoutTemplate(
                String(layoutTemplate || ''),
                cols
            ),
            settings: normalizeSectionSettings(sectionSettings || {}),
            columns: Array.from({ length: cols }, () => ({
                id: makeId('col'),
                blocks: [],
            })),
        };

        insertSectionAt(index, section);
    }

    function createTemplateBlock(spec) {
        if (!spec || typeof spec !== 'object') return null;
        const type = String(spec.type || '').trim().toLowerCase();
        if (!type) return null;

        const block = createBlockFromWidget(type);
        if (!block) return null;

        if (spec.settings && typeof spec.settings === 'object') {
            block.settings = Object.assign({}, block.settings || {}, spec.settings);
        }

        if (type === 'contact') {
            const safeSettings = block.settings && typeof block.settings === 'object' ? block.settings : {};
            safeSettings.formSlug = resolveContactFormSlugForInsert(safeSettings.formSlug);
            block.settings = safeSettings;
        }

        return block;
    }

    function createTemplateSection(spec) {
        if (!spec || typeof spec !== 'object') return null;
        const layoutId = String(spec.insertLayout || spec.layout || 'cols-1');
        const presetMap = getSectionLayoutPresets();
        const preset = presetMap[layoutId] || presetMap['cols-1'];
        if (!preset) return null;

        const columns = Array.from({ length: preset.columns }, (_, colIndex) => {
            const blockSpecs = Array.isArray(spec.columns && spec.columns[colIndex]) ? spec.columns[colIndex] : [];
            const blocks = blockSpecs
                .map((blockSpec) => createTemplateBlock(blockSpec))
                .filter((block) => !!block);

            return {
                id: makeId('col'),
                blocks,
            };
        });

        return {
            id: makeId('sec'),
            layoutTemplate: sanitizeSectionLayoutTemplate(preset.template, preset.columns),
            settings: normalizeSectionSettings(spec.settings || {}),
            columns,
        };
    }

    function applyQuickAddTemplate(templateId, index) {
        const templates = getQuickAddTemplatePresets();
        const template = templates[String(templateId || '')] || null;
        if (!template || !Array.isArray(template.sections)) {
            return;
        }

        const sections = template.sections
            .map((spec) => createTemplateSection(spec))
            .filter((section) => !!section);
        if (!sections.length) {
            return;
        }

        let firstBlockSelection = null;
        const insertIndex = clampSectionInsertIndex(index);
        sections.forEach((section, sectionOffset) => {
            insertSectionAt(insertIndex + sectionOffset, section);
            if (firstBlockSelection) {
                return;
            }
            const firstColumn = Array.isArray(section.columns) ? section.columns[0] : null;
            const firstBlock = firstColumn && Array.isArray(firstColumn.blocks) ? firstColumn.blocks[0] : null;
            if (!firstColumn || !firstBlock) {
                return;
            }
            firstBlockSelection = {
                sectionId: section.id,
                columnId: firstColumn.id,
                blockId: firstBlock.id,
            };
        });

        if (firstBlockSelection) {
            selectBlock(
                firstBlockSelection.sectionId,
                firstBlockSelection.columnId,
                firstBlockSelection.blockId
            );
        } else {
            ensureSelection();
        }

        if (String(state.page.title || '').trim() === '' && String(template.pageTitle || '').trim() !== '') {
            state.page.title = String(template.pageTitle || '').trim();
            state.page.slug = buildAutoSlug(state.page.title);
            syncPageMetaUi();
        }

        renderCanvas();
        renderInspector();
        if (firstBlockSelection) {
            openInspectorAfterWidgetInsert();
        }
    }

    function createBlockFromWidget(type, initialSettings) {
        const def = getWidgetDef(type);
        if (!def) return null;
        if (isWidgetDefLocked(def)) {
            notifyProWidgetLocked();
            return null;
        }

        const settings = applyWidgetDefaults(
            def.type,
            initialSettings && typeof initialSettings === 'object' ? initialSettings : {}
        );
        if (String(def.type || '').trim().toLowerCase() === 'contact') {
            settings.formSlug = resolveContactFormSlugForInsert(settings.formSlug);
        }

        return {
            id: makeId(),
            type: def.type,
            settings: settings,
        };
    }

    function insertWidgetAsSectionAt(type, index, initialSettings) {
        const block = createBlockFromWidget(type, initialSettings);
        if (!block) return;

        const section = {
            id: makeId('sec'),
            layoutTemplate: buildEqualSectionTemplate(1),
            settings: normalizeSectionSettings({}),
            columns: [
                {
                    id: makeId('col'),
                    blocks: [block],
                },
            ],
        };

        insertSectionAt(index, section);
        selectBlock(section.id, section.columns[0].id, block.id);
        renderCanvas();
        renderInspector();
        openInspectorAfterWidgetInsert();
    }

    function moveBlockAsSectionAt(blockId, index) {
        const movingId = String(blockId || '');
        if (!movingId) return;

        const source = findBlockLocation(movingId);
        if (!source) return;

        const movedBlock = source.column.blocks.splice(source.blockIndex, 1)[0];
        if (!movedBlock) return;

        const section = {
            id: makeId('sec'),
            layoutTemplate: buildEqualSectionTemplate(1),
            settings: normalizeSectionSettings({}),
            columns: [
                {
                    id: makeId('col'),
                    blocks: [movedBlock],
                },
            ],
        };

        insertSectionAt(index, section);
        selectBlock(section.id, section.columns[0].id, movedBlock.id);
        renderCanvas();
        renderInspector();
    }

    function removeSection(sectionId) {
        const id = String(sectionId || '');
        if (!id) return;

        state.builder.sections = getSections().filter((section) => String(section.id || '') !== id);

        if (state.selection && state.selection.kind === 'section' && String(state.selection.sectionId || '') === id) {
            state.selection = null;
        }

        if (state.selection && state.selection.kind === 'block') {
            const stillExists = !!findBlockLocation(state.selection.blockId);
            if (!stillExists) {
                state.selection = null;
            }
        }

        ensureSelection();
        if (boxEditBlockId && !findBlockLocation(boxEditBlockId)) {
            closeBlockBoxEditor();
        }
        renderCanvas();
        renderInspector();
    }

    function moveSection(sourceId, targetId, position) {
        const fromId = String(sourceId || '');
        const toId = String(targetId || '');
        if (!fromId || !toId || fromId === toId) return;

        const sections = getSections();
        const sourceIndex = sections.findIndex((s) => String(s.id || '') === fromId);
        const targetIndex = sections.findIndex((s) => String(s.id || '') === toId);
        if (sourceIndex === -1 || targetIndex === -1) return;

        const section = sections.splice(sourceIndex, 1)[0];
        let insertIndex = position === 'before' ? targetIndex : targetIndex + 1;
        if (sourceIndex < insertIndex) {
            insertIndex -= 1;
        }

        sections.splice(insertIndex, 0, section);
        state.builder.sections = sections;
        renderCanvas();
    }

    function moveSectionByOffset(sectionId, offset) {
        const safeId = String(sectionId || '');
        const delta = Number(offset);
        if (!safeId || !Number.isInteger(delta) || delta === 0) {
            return;
        }

        const sections = getSections();
        const sourceIndex = sections.findIndex((section) => String(section.id || '') === safeId);
        if (sourceIndex === -1) {
            return;
        }

        const targetIndex = sourceIndex + delta;
        if (targetIndex < 0 || targetIndex >= sections.length) {
            return;
        }

        const section = sections.splice(sourceIndex, 1)[0];
        sections.splice(targetIndex, 0, section);
        state.builder.sections = sections;
        renderCanvas();
    }

    function insertWidget(type, sectionId, columnId, targetBlockId, position, initialSettings) {
        const def = getWidgetDef(type);
        if (!def) return;
        if (isWidgetDefLocked(def)) {
            notifyProWidgetLocked();
            return;
        }

        const target = ensureColumnTarget(sectionId, columnId);
        if (!target) return;

        const newBlock = {
            id: makeId(),
            type: def.type,
            settings: applyWidgetDefaults(def.type, initialSettings && typeof initialSettings === 'object' ? initialSettings : {}),
        };

        const blocks = target.column.blocks;
        let insertIndex = blocks.length;
        if (targetBlockId) {
            const targetIndex = blocks.findIndex((b) => String(b.id || '') === String(targetBlockId));
            if (targetIndex !== -1) {
                insertIndex = position === 'before' ? targetIndex : targetIndex + 1;
            }
        }

        blocks.splice(insertIndex, 0, newBlock);
        selectBlock(target.section.id, target.column.id, newBlock.id);
        renderCanvas();
        renderInspector();
        openInspectorAfterWidgetInsert();
    }

    function moveBlock(blockId, sectionId, columnId, targetBlockId, position) {
        const movingId = String(blockId || '');
        if (!movingId) return;

        const source = findBlockLocation(movingId);
        if (!source) return;

        const target = ensureColumnTarget(sectionId, columnId);
        if (!target) return;

        if (targetBlockId && movingId === String(targetBlockId)) {
            return;
        }

        const sourceBlocks = source.column.blocks;
        const block = sourceBlocks.splice(source.blockIndex, 1)[0];

        const destBlocks = target.column.blocks;
        let insertIndex = destBlocks.length;

        if (targetBlockId) {
            const destTargetIndex = destBlocks.findIndex((b) => String(b.id || '') === String(targetBlockId));
            if (destTargetIndex !== -1) {
                insertIndex = position === 'before' ? destTargetIndex : destTargetIndex + 1;
            }
        }

        // Adjust when moving within the same column and the source index is before the insert index.
        if (source.sectionId === String(target.section.id || '') && source.columnId === String(target.column.id || '') && source.blockIndex < insertIndex) {
            insertIndex = Math.max(0, insertIndex - 1);
        }

        destBlocks.splice(insertIndex, 0, block);
        selectBlock(target.section.id, target.column.id, block.id);
        renderCanvas();
        renderInspector();
    }

    function appendWidgetToEnd(type, initialSettings) {
        const target = ensureColumnTarget('', '');
        if (!target) return;
        insertWidget(type, target.section.id, target.column.id, null, 'after', initialSettings);
    }

    function moveBlockToCanvasEnd(blockId) {
        const target = ensureColumnTarget('', '');
        if (!target) return;
        moveBlock(blockId, target.section.id, target.column.id, null, 'after');
    }

    function getSmartInsertTarget() {
        if (state.preferredInsertTarget) {
            const preferred = ensureColumnTarget(
                String(state.preferredInsertTarget.sectionId || ''),
                String(state.preferredInsertTarget.columnId || '')
            );
            if (preferred) {
                return {
                    sectionId: String(preferred.section.id || ''),
                    columnId: String(preferred.column.id || ''),
                    targetBlockId: null,
                    position: 'after',
                };
            }
        }

        if (state.selection && state.selection.kind === 'block') {
            return {
                sectionId: state.selection.sectionId,
                columnId: state.selection.columnId,
                targetBlockId: state.selection.blockId,
                position: 'after',
            };
        }

        const target = ensureColumnTarget('', '');
        if (!target) {
            return { sectionId: '', columnId: '', targetBlockId: null, position: 'after' };
        }

        return {
            sectionId: String(target.section.id || ''),
            columnId: String(target.column.id || ''),
            targetBlockId: null,
            position: 'after',
        };
    }

    function ensureColumnTarget(sectionId, columnId) {
        const desiredSectionId = String(sectionId || '').trim();
        const desiredColumnId = String(columnId || '').trim();

        const sections = getSections();
        const findSection = (id) => sections.find((s) => String(s.id || '') === id) || null;

        let section = desiredSectionId ? findSection(desiredSectionId) : null;
        if (!section && sections.length) {
            section = sections[sections.length - 1];
        }
        if (!section) {
            addSection(1);
            section = getSections()[0] || null;
        }
        if (!section) return null;

        const columns = Array.isArray(section.columns) ? section.columns : [];
        let column = desiredColumnId ? columns.find((c) => String(c.id || '') === desiredColumnId) : null;
        if (!column && columns.length) {
            column = columns[0];
        }
        if (!column) {
            column = { id: makeId('col'), blocks: [] };
            section.columns = [column];
        }

        if (!Array.isArray(column.blocks)) {
            column.blocks = [];
        }

        return { section, column };
    }

    function getSections() {
        if (!Array.isArray(state.builder.sections)) {
            state.builder.sections = [];
        }
        return state.builder.sections;
    }

    function getFirstBlockLocation() {
        const sections = getSections();
        for (let s = 0; s < sections.length; s += 1) {
            const cols = Array.isArray(sections[s].columns) ? sections[s].columns : [];
            for (let c = 0; c < cols.length; c += 1) {
                const blocks = Array.isArray(cols[c].blocks) ? cols[c].blocks : [];
                if (blocks.length) {
                    return {
                        sectionId: String(sections[s].id || ''),
                        columnId: String(cols[c].id || ''),
                        blockId: String(blocks[0].id || ''),
                    };
                }
            }
        }
        return null;
    }

    function findBlockLocation(blockId) {
        const id = String(blockId || '');
        if (!id) return null;

        const sections = getSections();
        for (let s = 0; s < sections.length; s += 1) {
            const section = sections[s];
            const cols = Array.isArray(section.columns) ? section.columns : [];
            for (let c = 0; c < cols.length; c += 1) {
                const column = cols[c];
                const blocks = Array.isArray(column.blocks) ? column.blocks : [];
                const b = blocks.findIndex((item) => String(item.id || '') === id);
                if (b !== -1) {
                    return {
                        sectionIndex: s,
                        columnIndex: c,
                        blockIndex: b,
                        sectionId: String(section.id || ''),
                        columnId: String(column.id || ''),
                        column,
                        block: blocks[b],
                    };
                }
            }
        }

        return null;
    }

    function findSection(sectionId) {
        const id = String(sectionId || '');
        if (!id) return null;

        const sections = getSections();
        const sectionIndex = sections.findIndex((section) => String(section.id || '') === id);
        if (sectionIndex === -1) {
            return null;
        }

        return {
            sectionIndex,
            section: sections[sectionIndex],
        };
    }

    function flushPendingEditorChanges(callback) {
        const commit = typeof callback === 'function' ? callback : () => {};
        const active = document.activeElement;
        const shouldBlurActive = !!(
            active
            && typeof active.blur === 'function'
            && active !== document.body
            && active !== document.documentElement
            && (
                active.isContentEditable
                || ['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON'].includes(String(active.tagName || '').toUpperCase())
            )
        );

        if (shouldBlurActive) {
            active.blur();
        }

        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(commit);
            });
            return;
        }

        window.setTimeout(commit, 0);
    }

    function readPageMetaTitle() {
        return pageMetaTitleInput
            ? String(pageMetaTitleInput.value || '').trim()
            : String(state.page.metaTitle || '').trim();
    }

    function readPageMetaDescription() {
        return pageMetaDescriptionInput
            ? String(pageMetaDescriptionInput.value || '').trim()
            : String(state.page.metaDescription || '').trim();
    }

    function performSaveBuilder() {
        if (state.isSaving || !config.saveUrl) return;

        const titleValue = pageTitleInput
            ? String(pageTitleInput.value || '').trim()
            : String(state.page.title || '').trim();
        if (titleValue === '') {
            setSaveStatus(label('titleRequired', 'Title is required.'), 'is-error');
            openInspectorSidebar();
            if (pageTitleInput) {
                pageTitleInput.focus();
            }
            return;
        }

        const slugValue = buildAutoSlug(titleValue);
        const metaTitleValue = readPageMetaTitle();
        const metaDescriptionValue = readPageMetaDescription();
        state.page.title = titleValue;
        state.page.slug = slugValue;
        state.page.metaTitle = metaTitleValue;
        state.page.metaDescription = metaDescriptionValue;
        syncPageMetaUi();
        const builderPayload = buildPersistableBuilderPayload(state.builder);

        state.isSaving = true;
        if (saveBtn) saveBtn.disabled = true;
        setSaveStatus(label('saving', 'Enregistrement...'), '');

        fetch(config.saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': String(config.csrfToken || ''),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                builder: builderPayload,
                title: titleValue,
                slug: slugValue,
                meta_title: metaTitleValue,
                meta_description: metaDescriptionValue,
                locale: String(config.activeLocale || ''),
            }),
        })
            .then((response) => response.json().catch(() => ({})).then((data) => ({ response, data })))
            .then(({ response, data }) => {
                if (!response.ok || !data.success) {
                    throw new Error(data.message || label('saveError', 'Unable to save builder page'));
                }

                if (data.page && typeof data.page === 'object') {
                    state.builder = normalizeBuilder(builderPayload);
                    if (typeof data.page.id === 'string' && data.page.id !== '') {
                        builderPageId = data.page.id;
                        config.pageId = data.page.id;
                    }
                    if (typeof data.page.title === 'string') {
                        state.page.title = data.page.title;
                        if (pageTitleInput) {
                            pageTitleInput.value = data.page.title;
                        }
                    }
                    if (typeof data.page.slug === 'string' && data.page.slug !== '') {
                        state.page.slug = data.page.slug;
                    } else if (state.page.slug === '') {
                        state.page.slug = buildAutoSlug(state.page.title);
                    }
                    if (typeof data.page.meta_title === 'string') {
                        state.page.metaTitle = data.page.meta_title;
                        if (pageMetaTitleInput) {
                            pageMetaTitleInput.value = data.page.meta_title;
                        }
                    }
                    if (typeof data.page.meta_description === 'string') {
                        state.page.metaDescription = data.page.meta_description;
                        if (pageMetaDescriptionInput) {
                            pageMetaDescriptionInput.value = data.page.meta_description;
                        }
                    }
                    if (typeof data.page.locale === 'string' && data.page.locale !== '') {
                        config.activeLocale = data.page.locale;
                    }
                    syncPageMetaUi();
                }

                setSaveStatus(data.message || label('saveSuccess', 'Builder page saved'), 'is-success');
                if (modeBadge) {
                    modeBadge.textContent = label('statusBuilderMode', 'Mode builder');
                    modeBadge.classList.remove('badge-warning');
                    modeBadge.classList.add('badge-info');
                }
        })
            .catch((error) => {
                console.error(error);
                setSaveStatus(error.message || label('saveError', 'Unable to save builder page'), 'is-error');
            })
            .finally(() => {
                state.isSaving = false;
                if (saveBtn) saveBtn.disabled = false;
            });
    }

    function saveBuilder() {
        flushPendingEditorChanges(() => {
            performSaveBuilder();
        });
    }

    function openDraftPreview() {
        flushPendingEditorChanges(() => {
            const previewUrl = resolvePreviewUrl();
            if (!previewUrl) {
                return;
            }

            const titleValue = pageTitleInput
                ? String(pageTitleInput.value || '').trim()
                : String(state.page.title || '').trim();
            if (titleValue === '') {
                setSaveStatus(label('titleRequired', 'Title is required.'), 'is-error');
                openInspectorSidebar();
                if (pageTitleInput) {
                    pageTitleInput.focus();
                }
                return;
            }

            const slugValue = buildAutoSlug(titleValue);
            const metaTitleValue = readPageMetaTitle();
            const metaDescriptionValue = readPageMetaDescription();
            state.page.title = titleValue;
            state.page.slug = slugValue;
            state.page.metaTitle = metaTitleValue;
            state.page.metaDescription = metaDescriptionValue;
            syncPageMetaUi();
            const builderPayload = buildPersistableBuilderPayload(state.builder);
            submitPreviewPayload(previewUrl, {
                title: titleValue,
                slug: slugValue,
                meta_title: metaTitleValue,
                meta_description: metaDescriptionValue,
                builder: builderPayload,
                locale: String(config.activeLocale || ''),
            });
        });
    }

    function openTemplatePreview(templateId) {
        const previewUrl = resolvePreviewUrl();
        if (!previewUrl) {
            return;
        }

        const template = getQuickAddTemplatePresets()[String(templateId || '')];
        if (!template || !Array.isArray(template.sections)) {
            return;
        }

        const sections = template.sections
            .map((sectionSpec) => createTemplateSection(sectionSpec))
            .filter((section) => !!section);
        if (!sections.length) {
            return;
        }

        const titleValue = String(template.pageTitle || template.label || state.page.title || '').trim() || 'Template';
        const slugValue = buildAutoSlug(titleValue);
        const metaTitleValue = readPageMetaTitle();
        const metaDescriptionValue = readPageMetaDescription();
        const builderPayload = buildPersistableBuilderPayload({
            version: 2,
            sections: sections,
        });

        submitPreviewPayload(previewUrl, {
            title: titleValue,
            slug: slugValue,
            meta_title: metaTitleValue,
            meta_description: metaDescriptionValue,
            builder: builderPayload,
            locale: String(config.activeLocale || ''),
        });
    }

    function resolvePreviewUrl() {
        return String(
            (previewDraftBtn && previewDraftBtn.getAttribute('data-preview-url'))
            || (previewDraftBtn && previewDraftBtn.getAttribute('href'))
            || ''
        ).trim();
    }

    function submitPreviewPayload(previewUrl, payload) {
        const targetUrl = String(previewUrl || '').trim();
        if (!targetUrl) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = targetUrl;
        form.target = '_blank';
        form.style.display = 'none';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = String(config.csrfToken || '');
        form.appendChild(tokenInput);

        const payloadInput = document.createElement('input');
        payloadInput.type = 'hidden';
        payloadInput.name = 'preview_payload';
        payloadInput.value = JSON.stringify(payload && typeof payload === 'object' ? payload : {});
        form.appendChild(payloadInput);

        document.body.appendChild(form);
        form.submit();
        form.remove();
    }

    function syncPageMetaUi() {
        if (pageSlugPreview) {
            const slug = String(state.page.slug || '').trim() || buildAutoSlug(state.page.title);
            pageSlugPreview.textContent = formatPageSlug(slug);
        }
    }

    function formatPageSlug(slug) {
        const locale = String(config.activeLocale || '').trim();
        const prefix = locale !== '' ? '/' + locale : '';
        if (slug === '' || slug === 'home') {
            return prefix || '/';
        }
        return prefix + '/page/' + slug;
    }

    function buildAutoSlug(rawTitle) {
        const value = String(rawTitle || '').trim().toLowerCase();
        if (value === '') {
            return 'page';
        }

        const normalized = typeof value.normalize === 'function' ? value.normalize('NFD') : value;
        const ascii = normalized.replace(/[\u0300-\u036f]/g, '');
        const cleaned = ascii
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .replace(/-{2,}/g, '-');

        return cleaned || 'page';
    }

    function registerBuilderBridge() {
        if (!builderPageId) return;

        if (!window.FlatCMSBuilderBridge || typeof window.FlatCMSBuilderBridge !== 'object') {
            window.FlatCMSBuilderBridge = {};
        }

        window.FlatCMSBuilderBridge.getBuilder = function(pageId) {
            const requested = String(pageId || '');
            if (requested !== '' && requested !== builderPageId) {
                return null;
            }
            return cloneBuilderPayload(state.builder);
        };

        window.FlatCMSBuilderBridge.getPageId = function() {
            return builderPageId;
        };
    }

    function publishBuilderState(reason) {
        if (!builderPageId) return;

        const detail = {
            pageId: builderPageId,
            reason: String(reason || 'update'),
            builder: cloneBuilderPayload(state.builder),
            updatedAt: Date.now(),
        };

        if (window.FlatCMSBuilderBridge && typeof window.FlatCMSBuilderBridge === 'object') {
            window.FlatCMSBuilderBridge.latest = detail;
        }

        try {
            document.dispatchEvent(new CustomEvent('flatcms:builder-state', { detail: detail }));
        } catch (e) {
            // ignore
        }
    }

    function cloneBuilderPayload(builder) {
        try {
            return JSON.parse(JSON.stringify(builder || { version: 2, sections: [] }));
        } catch (e) {
            return builder || { version: 2, sections: [] };
        }
    }

    function setSaveStatus(message, cssClass) {
        if (!saveStatus) return;
        saveStatus.textContent = message || '';
        saveStatus.classList.remove('is-error', 'is-success');
        if (cssClass) {
            saveStatus.classList.add(cssClass);
        }
    }

    function openMediaPicker(onSelect, fieldMediaOptions) {
        const modal = document.getElementById('mediaModal');
        if (!modal) {
            alert(label('invalidConfig', 'Invalid builder configuration'));
            return;
        }

        const mediaConfig = config.media || {};
        const mediaOptions = normalizeMediaFieldOptions(fieldMediaOptions);
        const modalOptions = {
            apiImagesUrl: mediaConfig.apiImagesUrl || '',
            apiFilesUrl: mediaConfig.apiFilesUrl || '',
            uploadUrl: mediaConfig.uploadUrl || '',
            uploadsBase: mediaConfig.uploadsBase || '/uploads',
            csrfToken: mediaConfig.csrfToken || config.csrfToken || '',
            mode: mediaOptions.mode,
            folder: mediaOptions.folder,
            accept: mediaOptions.accept,
            openUploadIfEmpty: true,
            initialTab: 'library',
            onSelect: function(file) {
                if (typeof onSelect === 'function') {
                    onSelect(file);
                }
                closeMediaModal();
            },
        };

        modal.classList.remove('hidden');
        modal.style.display = 'flex';

        if (typeof window.initMediaModal === 'function') {
            window.initMediaModal(modalOptions);
            return;
        }

        alert(label('invalidConfig', 'Invalid builder configuration'));
    }

    function closeMediaModal() {
        const modal = document.getElementById('mediaModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function createColorControl(currentValue, onChange) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderColorFieldRow === 'function') {
            const colorRow = primitives.createBuilderColorFieldRow({
                value: currentValue,
                clearLabel: label('clearColor', 'Clear'),
                normalizeHex: normalizeHexColor,
                onInput: (nextValue) => {
                    if (typeof onChange === 'function') {
                        onChange(nextValue);
                    }
                },
                onCommit: (nextValue) => {
                    if (typeof onChange === 'function') {
                        onChange(nextValue);
                    }
                },
            });
            return colorRow.element;
        }

        const row = document.createElement('div');
        row.className = 'pb-color-row';

        const picker = document.createElement('input');
        picker.type = 'color';
        picker.className = 'pb-color-picker';

        const textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.className = 'form-input';
        textInput.placeholder = '#6366f1';

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'btn btn-ghost btn-sm';
        clearBtn.textContent = label('clearColor', 'Clear');

        const initialValue = String(currentValue || '').trim();
        const initialHex = normalizeHexColor(initialValue);
        picker.value = initialHex || '#000000';
        textInput.value = initialValue;

        picker.addEventListener('input', () => {
            textInput.value = picker.value;
            if (typeof onChange === 'function') {
                onChange(picker.value);
            }
        });

        textInput.addEventListener('input', () => {
            const nextValue = String(textInput.value || '').trim();
            const normalized = normalizeHexColor(nextValue);
            if (normalized) {
                picker.value = normalized;
            }
            if (typeof onChange === 'function') {
                onChange(nextValue);
            }
        });

        clearBtn.addEventListener('click', () => {
            textInput.value = '';
            picker.value = '#000000';
            if (typeof onChange === 'function') {
                onChange('');
            }
        });

        row.appendChild(picker);
        row.appendChild(textInput);
        row.appendChild(clearBtn);
        return row;
    }

    function updateIconPreview(previewNode, rawValue) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.updateBuilderIconPreview === 'function') {
            primitives.updateBuilderIconPreview(previewNode, rawValue);
            return;
        }
        if (!previewNode) return;
        const value = String(rawValue || '').trim();
        const iconClass = value || 'fas fa-icons';
        previewNode.classList.toggle('is-empty', !value);
        previewNode.innerHTML = `<i class="${escapeAttr(iconClass)}"></i>`;

        if (window.FontAwesome && window.FontAwesome.dom && typeof window.FontAwesome.dom.i2svg === 'function') {
            window.FontAwesome.dom.i2svg({ node: previewNode });
        }
    }

    function normalizeTextStylePrefix(field) {
        const explicit = String((field && field.stylePrefix) || '').trim();
        if (explicit !== '') {
            return explicit.replace(/[^a-zA-Z0-9_]/g, '');
        }
        const fallback = String((field && field.key) || '').trim();
        return fallback.replace(/[^a-zA-Z0-9_]/g, '') || 'textStyle';
    }

    function textStyleSettingKey(prefix, suffix) {
        const safePrefix = String(prefix || '').replace(/[^a-zA-Z0-9_]/g, '') || 'textStyle';
        return `${safePrefix}${suffix}`;
    }

    function normalizeTextStyleFont(value) {
        const safe = String(value || '').trim().toLowerCase();
        return TEXT_STYLE_FONT_OPTIONS.includes(safe) ? safe : 'inherit';
    }

    function normalizeTextStyleIconPosition(value) {
        const safe = String(value || '').trim().toLowerCase();
        return safe === 'end' ? 'end' : 'start';
    }

    function normalizeTextStyleSize(value) {
        const safe = String(value || '').trim().toLowerCase();
        if (TEXT_STYLE_SIZE_OPTIONS.includes(safe)) {
            return safe;
        }
        return 'inherit';
    }

    function normalizeTextStyleList(value) {
        const safe = String(value || '').trim().toLowerCase();
        if (safe === 'disc' || safe === 'circle' || safe === 'square') {
            return safe;
        }
        return 'none';
    }

    function normalizeTextStyleToggle(value, fallback) {
        if (typeof value === 'boolean') {
            return value;
        }
        const safe = String(value || '').trim().toLowerCase();
        if (['1', 'true', 'on', 'yes'].includes(safe)) {
            return true;
        }
        if (['0', 'false', 'off', 'no', ''].includes(safe)) {
            return false;
        }
        return !!fallback;
    }

    function normalizeTextStyleAlign(value, fallback) {
        const safe = String(value || '').trim().toLowerCase();
        if (['left', 'center', 'right'].includes(safe)) {
            return safe;
        }
        return normalizeAlign(String(fallback || 'left'));
    }

    function textAlignToJustifySelf(value) {
        const safe = normalizeAlign(String(value || 'left'));
        if (safe === 'center') return 'center';
        if (safe === 'right') return 'end';
        return 'start';
    }

    function resolveTextStyleState(settings, prefix, fallbackAlign) {
        const source = settings && typeof settings === 'object' ? settings : {};
        const safePrefix = String(prefix || '').trim();
        const aliasPrefix = safePrefix === 'titleStyle'
            ? 'titleTextStyle'
            : (safePrefix === 'subtitleStyle' ? 'subtitleTextStyle' : '');
        const fallbackPrefix = /^itemTitleStyle\d+$/i.test(safePrefix)
            ? 'itemTitleStyle'
            : (/^itemTextStyle\d+$/i.test(safePrefix) ? 'itemTextStyle' : '');
        const readSetting = (suffix) => {
            const primary = source[textStyleSettingKey(safePrefix, suffix)];
            if (primary !== undefined && primary !== null && String(primary).trim() !== '') {
                return primary;
            }
            if (aliasPrefix !== '') {
                const alias = source[textStyleSettingKey(aliasPrefix, suffix)];
                if (alias !== undefined && alias !== null && String(alias).trim() !== '') {
                    return alias;
                }
            }
            if (fallbackPrefix !== '') {
                return source[textStyleSettingKey(fallbackPrefix, suffix)];
            }
            return primary;
        };
        const alignRaw = readSetting(TEXT_STYLE_SUFFIX.align);
        const fontRaw = readSetting(TEXT_STYLE_SUFFIX.font);
        const sizeRaw = readSetting(TEXT_STYLE_SUFFIX.size);
        const boldRaw = readSetting(TEXT_STYLE_SUFFIX.bold);
        const italicRaw = readSetting(TEXT_STYLE_SUFFIX.italic);
        const underlineRaw = readSetting(TEXT_STYLE_SUFFIX.underline);
        const colorRaw = readSetting(TEXT_STYLE_SUFFIX.color);
        const listRaw = readSetting(TEXT_STYLE_SUFFIX.list);
        const iconRaw = readSetting(TEXT_STYLE_SUFFIX.icon);
        const iconPositionRaw = readSetting(TEXT_STYLE_SUFFIX.iconPosition);

        return {
            align: normalizeTextStyleAlign(alignRaw, fallbackAlign),
            font: normalizeTextStyleFont(fontRaw),
            size: normalizeTextStyleSize(sizeRaw),
            bold: normalizeTextStyleToggle(boldRaw, false),
            italic: normalizeTextStyleToggle(italicRaw, false),
            underline: normalizeTextStyleToggle(underlineRaw, false),
            color: normalizeColor(String(colorRaw || '')),
            list: normalizeTextStyleList(listRaw),
            icon: String(iconRaw || '').trim(),
            iconPosition: normalizeTextStyleIconPosition(iconPositionRaw),
        };
    }

    function getTextStyleFontLabel(value) {
        const safe = normalizeTextStyleFont(value);
        if (safe === 'inherit') return label('textStyleFontInherit', 'Thème');
        if (safe === 'system') return label('textStyleFontSystem', 'Système');
        if (safe === 'sans') return label('textStyleFontSans', 'Sans-serif');
        if (safe === 'serif') return label('textStyleFontSerif', 'Serif');
        if (safe === 'mono') return label('textStyleFontMono', 'Monospace');
        return label('textStyleFontDisplay', 'Display');
    }

    function getTextStyleFontFamily(value) {
        const safe = normalizeTextStyleFont(value);
        if (safe === 'system') return 'var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif)';
        if (safe === 'sans') return '"Cabin", var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif)';
        if (safe === 'serif') return 'Georgia, "Times New Roman", Times, serif';
        if (safe === 'mono') return '"SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace';
        if (safe === 'display') return '"Cabin", var(--font-family-heading, var(--font-family-base, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif))';
        return '';
    }

    function getTextStyleSizeLabel(value) {
        const safe = normalizeTextStyleSize(value);
        if (safe === 'inherit') {
            return label('textStyleSizeInherit', 'Theme');
        }
        return safe.replace(/px$/i, '');
    }

    function getTextStyleListLabel(value) {
        const safe = normalizeTextStyleList(value);
        if (safe === 'circle') return label('textStyleListCircle', 'Puce cercle');
        if (safe === 'square') return label('textStyleListSquare', 'Puce carré');
        if (safe === 'none') return label('textStyleListNone', 'Aucune');
        return label('textStyleListDisc', 'Puce point');
    }

    function getTextStyleListGlyph(value) {
        const safe = normalizeTextStyleList(value);
        if (safe === 'circle') return '∘';
        if (safe === 'square') return '▪';
        if (safe === 'none') return '';
        return '•';
    }

    function createToolbarSelectIconControl(config) {
        const sharedPrimitives = window.FlatCMSUIPrimitives || null;
        if (sharedPrimitives && typeof sharedPrimitives.createCompactSelectControl === 'function') {
            return sharedPrimitives.createCompactSelectControl(config);
        }

        const cfg = config && typeof config === 'object' ? config : {};
        const wrapper = document.createElement('label');
        wrapper.className = 'pb-textstyle-toolbar-picker';

        const button = document.createElement('span');
        button.className = 'pb-textstyle-toolbar-btn';
        button.setAttribute('role', 'button');
        button.setAttribute('tabindex', '0');
        wrapper.appendChild(button);

        const select = document.createElement('select');
        select.className = 'pb-textstyle-toolbar-native-select';
        wrapper.appendChild(select);

        const iconClass = String(cfg.iconClass || 'fas fa-sliders-h').trim();
        const labelText = String(cfg.label || 'Option').trim() || 'Option';
        const ariaLabel = String(cfg.ariaLabel || labelText).trim() || labelText;
        button.innerHTML = `<i class="${escapeAttr(iconClass)}" aria-hidden="true"></i>`;
        button.setAttribute('aria-label', ariaLabel);

        const currentLabelFn = typeof cfg.currentLabel === 'function'
            ? cfg.currentLabel
            : () => '';
        const updateTitles = () => {
            const currentLabel = String(currentLabelFn(select.value) || '').trim();
            const title = currentLabel !== '' ? `${labelText}: ${currentLabel}` : labelText;
            wrapper.title = title;
            button.title = title;
            select.title = title;
            select.setAttribute('aria-label', title);
        };

        button.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                if (typeof select.showPicker === 'function') {
                    select.showPicker();
                    return;
                }
                select.focus();
            }
        });

        return {
            wrapper,
            button,
            select,
            updateTitles,
        };
    }

    function createToolbarColorControl(config) {
        const sharedPrimitives = window.FlatCMSUIPrimitives || null;
        if (sharedPrimitives && typeof sharedPrimitives.createCompactColorControl === 'function') {
            return sharedPrimitives.createCompactColorControl({
                ...config,
                clearLabel: label('clearColor', 'Clear'),
                emptyLabel: label('textStyleSizeInherit', 'Theme'),
                normalizeColor,
                normalizeHex: normalizeHexColor,
            });
        }

        const cfg = config && typeof config === 'object' ? config : {};
        const wrapper = document.createElement('div');
        wrapper.className = 'pb-textstyle-toolbar-color';

        const pickerWrap = document.createElement('div');
        pickerWrap.className = 'pb-textstyle-toolbar-picker pb-textstyle-toolbar-color-picker';
        wrapper.appendChild(pickerWrap);

        const swatch = document.createElement('button');
        swatch.type = 'button';
        swatch.className = 'pb-textstyle-toolbar-btn pb-textstyle-toolbar-color-swatch';
        pickerWrap.appendChild(swatch);

        const picker = document.createElement('input');
        picker.type = 'color';
        picker.className = 'pb-textstyle-toolbar-native-select pb-textstyle-toolbar-native-color';
        pickerWrap.appendChild(picker);

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'pb-textstyle-toolbar-btn';
        clearBtn.innerHTML = '<i class="fas fa-eraser" aria-hidden="true"></i>';
        clearBtn.title = label('clearColor', 'Clear');
        clearBtn.setAttribute('aria-label', label('clearColor', 'Clear'));
        wrapper.appendChild(clearBtn);

        const labelText = String(cfg.label || label('textStyleColor', 'Couleur')).trim() || label('textStyleColor', 'Couleur');
        const onUpdate = typeof cfg.onUpdate === 'function' ? cfg.onUpdate : null;

        const requestOpenPicker = () => {
            if (typeof picker.showPicker === 'function') {
                try {
                    picker.showPicker();
                    return;
                } catch (e) {
                    // fallback below
                }
            }
            picker.focus({ preventScroll: true });
            picker.click();
        };

        const setValue = (value) => {
            const safe = normalizeColor(String(value || ''));
            swatch.classList.toggle('is-empty', safe === '');
            swatch.style.setProperty('--pb-textstyle-swatch-color', safe || 'transparent');
            picker.value = normalizeHexColor(safe) || '#4f46e5';
            const title = safe !== ''
                ? `${labelText}: ${safe}`
                : `${labelText}: ${label('textStyleSizeInherit', 'Theme')}`;
            swatch.title = title;
            swatch.setAttribute('aria-label', title);
            pickerWrap.title = title;
            picker.title = title;
        };

        const emit = (value, refreshInspector) => {
            const safe = normalizeColor(String(value || ''));
            setValue(safe);
            if (onUpdate) {
                onUpdate(safe, refreshInspector !== false);
            }
        };

        setValue(String(cfg.value || ''));

        picker.addEventListener('input', () => {
            emit(picker.value, false);
        });
        picker.addEventListener('change', () => {
            emit(picker.value, true);
        });
        swatch.addEventListener('click', (event) => {
            event.preventDefault();
            requestOpenPicker();
        });
        swatch.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });
        clearBtn.addEventListener('click', () => {
            emit('', true);
        });
        clearBtn.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });

        return {
            wrapper,
            setValue,
        };
    }

    function resolveTextStylePreviewText(settings, prefix) {
        const fallback = label('textStylePreviewSample', 'Preview text');
        if (!settings || typeof settings !== 'object') {
            return fallback;
        }

        const firstMeaningfulLine = (value) => {
            const lines = String(value || '')
                .split(/\r\n|\r|\n/)
                .map((line) => String(line || '').trim())
                .filter((line) => line !== '');
            return lines.length ? lines[0] : '';
        };

        const keyMap = {
            titleStyle: 'title',
            headingStyle: 'text',
            subtitleStyle: 'subtitle',
            itemTitleStyle: 'titles',
            itemTextStyle: 'texts',
            questionStyle: 'questions',
            answerStyle: 'answers',
        };

        const candidates = [];
        const mapped = keyMap[String(prefix || '').trim()] || '';
        if (mapped !== '') {
            candidates.push(mapped);
        }
        const fallbackFromPrefix = String(prefix || '').trim().replace(/Style$/i, '');
        if (fallbackFromPrefix !== '' && !candidates.includes(fallbackFromPrefix)) {
            candidates.push(fallbackFromPrefix);
        }
        if (!candidates.includes(prefix)) {
            candidates.push(prefix);
        }

        const safePrefix = String(prefix || '').trim();
        const isFeatureGridContext = settings && typeof settings === 'object'
            && ('titles' in settings || 'texts' in settings);
        if (isFeatureGridContext) {
            const featureTitle = firstMeaningfulLine(settings.title || '');
            const firstTitle = firstMeaningfulLine(settings.titles || '');
            const firstText = firstMeaningfulLine(settings.texts || '');
            if (safePrefix === 'titleStyle' && featureTitle !== '') {
                return featureTitle;
            }
            if (safePrefix === 'itemTitleStyle' && firstTitle !== '') {
                return firstTitle;
            }
            if (safePrefix === 'itemTextStyle' && firstText !== '') {
                return firstText;
            }
        }

        let raw = '';
        for (let i = 0; i < candidates.length; i += 1) {
            const key = String(candidates[i] || '').trim();
            if (key === '') {
                continue;
            }
            const candidateValue = String(settings[key] || '').trim();
            if (candidateValue !== '') {
                raw = candidateValue;
                break;
            }
        }

        if (raw === '') {
            return fallback;
        }

        const firstLine = String(raw).split(/\r\n|\r|\n/)[0] || '';
        const temp = document.createElement('div');
        temp.innerHTML = firstLine;
        const normalized = String(temp.textContent || temp.innerText || '').replace(/\s+/g, ' ').trim();
        return normalized !== '' ? normalized : fallback;
    }

    function resolveTextStylePreviewItems(settings, prefix, field) {
        if (field && Array.isArray(field.previewItems) && field.previewItems.length) {
            return field.previewItems.map((entry) => ({
                label: String((entry && entry.label) || '').trim(),
                text: String((entry && entry.text) || '').trim() || label('textStylePreviewSample', 'Preview text'),
            }));
        }

        if (field && typeof field.previewSource === 'string' && String(field.previewSource).trim() !== '') {
            const sourceKey = String(field.previewSource).trim();
            const fallback = label('textStylePreviewSample', 'Preview text');
            const safeSettings = settings && typeof settings === 'object' ? settings : {};
            const sourceValue = String(safeSettings[sourceKey] || '').trim();
            if (sourceValue !== '') {
                const firstLine = sourceValue.split(/\r\n|\r|\n/)[0] || '';
                const temp = document.createElement('div');
                temp.innerHTML = firstLine;
                const normalized = String(temp.textContent || temp.innerText || '').replace(/\s+/g, ' ').trim();
                return [{ label: '', text: normalized !== '' ? normalized : fallback }];
            }
        }

        if (field && typeof field.previewText === 'string' && String(field.previewText).trim() !== '') {
            return [{ label: '', text: String(field.previewText).trim() }];
        }

        const fallback = label('textStylePreviewSample', 'Preview text');
        const safeSettings = settings && typeof settings === 'object' ? settings : {};
        return [{ label: '', text: resolveTextStylePreviewText(safeSettings, prefix) }];
    }

    function updateTextStylePreview(container, state, previewText) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.updateBuilderTextStylePreview === 'function') {
            primitives.updateBuilderTextStylePreview({
                container,
                state,
                previewText,
                normalizeAlign,
                normalizeFont: normalizeTextStyleFont,
                normalizeSize: normalizeTextStyleSize,
                normalizeToggle: normalizeTextStyleToggle,
                normalizeColor,
                normalizeList: normalizeTextStyleList,
                normalizeIconPosition: normalizeTextStyleIconPosition,
                getFontFamily: getTextStyleFontFamily,
                fallbackPreviewText: label('textStylePreviewSample', 'Preview text'),
            });
            return;
        }
        if (!container) {
            return;
        }

        const current = state && typeof state === 'object' ? state : {};
        const safeAlign = normalizeAlign(String(current.align || 'left'));
        const safeFont = normalizeTextStyleFont(String(current.font || 'inherit'));
        const safeSize = normalizeTextStyleSize(String(current.size || 'inherit'));
        const safeBold = normalizeTextStyleToggle(current.bold, false);
        const safeItalic = normalizeTextStyleToggle(current.italic, false);
        const safeUnderline = normalizeTextStyleToggle(current.underline, false);
        const safeColor = normalizeColor(String(current.color || ''));
        const safeList = normalizeTextStyleList(String(current.list || 'none'));
        const safeIconPosition = normalizeTextStyleIconPosition(String(current.iconPosition || 'start'));
        const safeIconClass = String(current.icon || '')
            .trim()
            .split(/\s+/)
            .filter((token) => /^[a-z0-9_-]+$/i.test(token))
            .join(' ');

        container.classList.remove('is-align-left', 'is-align-center', 'is-align-right');
        container.classList.add(`is-align-${safeAlign}`);
        container.classList.toggle('is-list-style', safeList !== 'none');
        container.style.color = safeColor !== '' ? safeColor : '';
        const fontFamily = getTextStyleFontFamily(safeFont);
        container.style.fontFamily = fontFamily !== '' ? fontFamily : '';
        container.style.fontSize = safeSize !== 'inherit' ? safeSize : '';
        container.style.setProperty('--pb-textstyle-marker-size', safeSize !== 'inherit' ? safeSize : '1em');

        container.innerHTML = '';

        const appendIcon = () => {
            if (safeIconClass === '') {
                return;
            }
            const icon = document.createElement('i');
            icon.className = `${safeIconClass} pb-textstyle-preview-icon`;
            icon.setAttribute('aria-hidden', 'true');
            container.appendChild(icon);
        };

        const appendListMarker = () => {
            if (safeList === 'none') {
                return;
            }
            const marker = document.createElement('span');
            marker.className = 'pb-textstyle-preview-list-marker';
            marker.setAttribute('aria-hidden', 'true');
            marker.textContent = safeList === 'circle'
                ? '○'
                : (safeList === 'square' ? '■' : '●');
            container.appendChild(marker);
        };

        const textNode = document.createElement('span');
        textNode.className = 'pb-textstyle-preview-text';
        textNode.textContent = String(previewText || '').trim() || label('textStylePreviewSample', 'Preview text');
        textNode.style.fontWeight = safeBold ? '700' : '';
        textNode.style.fontStyle = safeItalic ? 'italic' : '';
        textNode.style.textDecoration = safeUnderline ? 'underline' : '';

        appendListMarker();
        if (safeIconClass !== '' && safeIconPosition === 'start') {
            appendIcon();
        }
        container.appendChild(textNode);
        if (safeIconClass !== '' && safeIconPosition === 'end') {
            appendIcon();
        }
    }

    function createTextStyleControl(block, field, onChange) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderTextStyleControl === 'function') {
            return primitives.createBuilderTextStyleControl({
                settings: block && block.settings && typeof block.settings === 'object' ? block.settings : {},
                field,
                label,
                resolvePrefix: normalizeTextStylePrefix,
                resolveState: resolveTextStyleState,
                resolvePreviewItems: resolveTextStylePreviewItems,
                updatePreview: updateTextStylePreview,
                buildSettingKey: textStyleSettingKey,
                suffixes: TEXT_STYLE_SUFFIX,
                normalizeAlign,
                normalizeHexColor,
                normalizeColor,
                clampNumber,
                createSelectControl: createToolbarSelectIconControl,
                createColorControl: createToolbarColorControl,
                createAlignControl: (alignField, currentValue, handleChange) => createAlignIconControl(alignField, currentValue, handleChange),
                fontOptions: TEXT_STYLE_FONT_OPTIONS,
                getFontLabel: getTextStyleFontLabel,
                normalizeFont: normalizeTextStyleFont,
                sizeOptions: TEXT_STYLE_SIZE_OPTIONS,
                getSizeLabel: getTextStyleSizeLabel,
                normalizeSize: normalizeTextStyleSize,
                getListLabel: getTextStyleListLabel,
                getListGlyph: getTextStyleListGlyph,
                normalizeList: normalizeTextStyleList,
                normalizeToggle: normalizeTextStyleToggle,
                updateIconPreview,
                openIconPicker,
                normalizeIconPosition: normalizeTextStyleIconPosition,
                getIconPositionLabel: (value) => normalizeTextStyleIconPosition(value) === 'end'
                    ? label('textStyleIconEnd', 'Fin')
                    : label('textStyleIconStart', 'Début'),
                onChange: (settingKey, value, refreshInspector) => {
                    if (typeof onChange === 'function') {
                        onChange(settingKey, value, refreshInspector !== false);
                    }
                },
            });
        }

        return document.createElement('div');
    }

    function ensureIconModal() {
        if (iconModal) return;

        iconModal = document.createElement('div');
        iconModal.className = 'pb-icon-modal';
        iconModal.setAttribute('aria-hidden', 'true');
        iconModal.innerHTML = `
            <div class="pb-icon-dialog">
                <div class="pb-icon-header">
                    <h3>${escapeHtml(label('iconPicker', 'Icon picker'))}</h3>
                    <button type="button" class="pb-icon-close" data-role="pb-icon-close" aria-label="${escapeAttr(label('close', 'Close'))}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="pb-icon-search">
                    <input type="text" class="form-input" id="pbIconSearch" placeholder="${escapeAttr(label('iconSearch', 'Search icon'))}">
                </div>
                <div id="pbIconGrid" class="pb-icon-grid">
                    <div class="pb-icon-loading">${escapeHtml(label('iconLoading', 'Loading icons...'))}</div>
                </div>
            </div>
        `;

        document.body.appendChild(iconModal);
        iconGrid = iconModal.querySelector('#pbIconGrid');
        iconSearch = iconModal.querySelector('#pbIconSearch');

        iconModal.addEventListener('click', (event) => {
            if (event.target === iconModal) {
                closeIconPicker();
            }
        });

        const closeBtn = iconModal.querySelector('[data-role="pb-icon-close"]');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeIconPicker);
        }

        if (iconSearch) {
            iconSearch.addEventListener('input', () => {
                if (iconSearchTimer) {
                    window.clearTimeout(iconSearchTimer);
                }
                iconSearchTimer = window.setTimeout(() => {
                    renderIconCatalog(iconSearch.value.trim());
                }, 120);
            });
        }
    }

    function openIconPicker(currentValue, onSelect) {
        ensureIconModal();
        iconSelectCallback = typeof onSelect === 'function' ? onSelect : null;
        iconCurrentValue = String(currentValue || '').trim();

        if (iconSearch) {
            iconSearch.value = '';
        }

        if (!iconLoaded) {
            loadIconCatalog();
        } else {
            renderIconCatalog('');
        }

        iconModal.classList.add('is-open');
        iconModal.setAttribute('aria-hidden', 'false');
        if (iconSearch) {
            iconSearch.focus();
        }
    }

    function closeIconPicker() {
        if (!iconModal) return;
        iconModal.classList.remove('is-open');
        iconModal.setAttribute('aria-hidden', 'true');
    }

    function loadIconCatalog() {
        if (!iconGrid) return;

        const endpoint = String(config.iconsEndpoint || '').trim();
        if (!endpoint) {
            iconGrid.innerHTML = `<div class="pb-icon-loading">${escapeHtml(label('iconError', 'Unable to load icons'))}</div>`;
            return;
        }

        iconGrid.innerHTML = `<div class="pb-icon-loading">${escapeHtml(label('iconLoading', 'Loading icons...'))}</div>`;
        fetch(endpoint, { credentials: 'same-origin' })
            .then((res) => res.json())
            .then((data) => {
                iconList = Array.isArray(data) ? data : [];
                iconLoaded = true;
                renderIconCatalog('');
            })
            .catch(() => {
                iconGrid.innerHTML = `<div class="pb-icon-loading">${escapeHtml(label('iconError', 'Unable to load icons'))}</div>`;
            });
    }

    function renderIconCatalog(filter) {
        if (!iconGrid) return;

        iconGrid.innerHTML = '';
        const term = String(filter || '').toLowerCase();
        const icons = iconList.filter((iconClass) => String(iconClass || '').toLowerCase().includes(term)).slice(0, 300);

        if (!icons.length) {
            iconGrid.innerHTML = `<div class="pb-icon-loading">${escapeHtml(label('iconEmpty', 'No icons found'))}</div>`;
            return;
        }

        const fragment = document.createDocumentFragment();
        icons.forEach((iconClass) => {
            const value = String(iconClass || '').trim();
            if (!value) return;

            const card = document.createElement('button');
            card.type = 'button';
            card.className = 'pb-icon-card';
            if (value === iconCurrentValue) {
                card.classList.add('is-active');
            }

            const iconName = value.split(' ').find((cls) => cls.startsWith('fa-') && cls !== 'fa-solid' && cls !== 'fa-regular' && cls !== 'fa-brands');
            const iconLabel = iconName ? iconName.replace('fa-', '') : 'icon';
            card.innerHTML = `<i class="${escapeAttr(value)}"></i><span>${escapeHtml(iconLabel)}</span>`;
            card.addEventListener('click', () => {
                if (iconSelectCallback) {
                    iconSelectCallback(value);
                }
                closeIconPicker();
            });
            fragment.appendChild(card);
        });

        iconGrid.appendChild(fragment);

        if (window.FontAwesome && window.FontAwesome.dom && typeof window.FontAwesome.dom.i2svg === 'function') {
            window.FontAwesome.dom.i2svg({ node: iconGrid });
        }
    }

    function renderBlockPreview(block) {
        const type = block.type;
        const settings = block.settings || {};
        const def = getWidgetDef(type);
        const customPreview = renderCustomWidgetPreview(type, settings, def);
        if (customPreview !== null) {
            return customPreview;
        }

        if (type === 'video') {
            return renderVideoWidgetPreview(settings);
        }

        if (type === 'music') {
            return renderMusicWidgetPreview(settings);
        }

        if (isMediaLinkWidgetType(type)) {
            return renderMediaLinkWidgetPreview(type, settings);
        }

        if (type === 'cta_banner') {
            return renderCtaBannerWidgetPreview(settings);
        }

        if (type === 'faq') {
            return renderFaqWidgetPreview(settings);
        }

        if (type === 'legal_section') {
            return renderLegalSectionWidgetPreview(settings);
        }

        if (type === 'address') {
            return renderAddressWidgetPreview(settings);
        }

        if (type === 'map') {
            return renderMapWidgetPreview(settings);
        }

        if (type === 'sitemap') {
            return renderSitemapWidgetPreview(settings);
        }

        if (type === 'cards') {
            return renderCardsWidgetPreview(settings);
        }

        if (type === 'slider') {
            return renderSliderWidgetPreview(settings);
        }

        if (type === 'countdown') {
            return renderCountdownWidgetPreview(settings);
        }

        if (type === 'links') {
            const title = String(settings.title || '').trim();
            const align = normalizeAlign(String(settings.align || 'left'));
            const linkStyle = normalizeLinkStyle(String(settings.linkStyle || 'hover'));
            const items = parseLinks(String(settings.items || ''));
            const list = (items.length ? items : [{ label: 'Lien', url: '#' }])
                .slice(0, 6)
                .map((item) => {
                    const href = sanitizeUrl(item.url) || '#';
                    const target = normalizeLinkTarget(String(item.target || ''), href);
                    const targetAttr = target === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';
                    return `<li><a href="${escapeAttr(href)}"${targetAttr}>${escapeHtml(item.label || 'Lien')}</a></li>`;
                })
                .join('');
            return `<div class="pb-preview-align pb-preview-align-${escapeAttr(align)}">${title ? `<strong>${escapeHtml(title)}</strong>` : ''}<ul class="pb-preview-links pb-preview-links-style-${escapeAttr(linkStyle)}">${list}</ul></div>`;
        }

        if (type === 'icon') {
            const icon = escapeAttr(String(settings.icon || 'fas fa-star'));
            const size = clampNumber(settings.size, 12, 120, 32);
            const color = normalizeColor(String(settings.color || ''));
            const align = normalizeAlign(String(settings.align || 'left'));
            const colorAttr = color ? ` data-icon-color="${escapeAttr(color)}"` : '';
            return `<div class="pb-preview-icon-wrap pb-preview-align pb-preview-align-${escapeAttr(align)}"><i class="${icon} pb-preview-icon" data-icon-size="${size}"${colorAttr}></i></div>`;
        }

        return renderGenericWidgetPreview(type, settings);
    }

    function renderGenericWidgetPreview(type, settings) {
        const def = getWidgetDef(type);
        const safeSettings = applyWidgetDefaults(type, settings && typeof settings === 'object' ? settings : {});
        const title = escapeHtml(String((def && def.label) || type || label('widget', 'Widget')));

        if (def && Array.isArray(def.fields)) {
            const hasFormSlugField = def.fields.some((field) => String((field && field.key) || '').trim().toLowerCase() === 'formslug');
            if (hasFormSlugField) {
                return renderContactWidgetPreview(safeSettings);
            }
        }

        const controls = buildGenericWidgetPreviewControls(type, def, safeSettings).slice(0, 9);
        if (controls.length) {
            return `<div class="pb-preview-generic pb-preview-generic-rich"><strong class="pb-preview-generic-title">${title}</strong><div class="pb-preview-generic-form">${controls.join('')}</div></div>`;
        }

        const fieldLabels = buildGenericWidgetFieldLabelMap(def);
        const rows = collectGenericWidgetPreviewRows(safeSettings, fieldLabels).slice(0, 4);
        const rowsHtml = rows.length
            ? `<ul class="pb-preview-generic-list">${rows
                .map((row) => `<li class="pb-preview-generic-row"><span>${escapeHtml(row.key)}</span><strong>${escapeHtml(row.value)}</strong></li>`)
                .join('')}</ul>`
            : '';

        return `<div class="pb-preview-generic"><strong class="pb-preview-generic-title">${title}</strong>${rowsHtml}</div>`;
    }

    function buildGenericWidgetFieldLabelMap(def) {
        const map = {};
        if (!def || !Array.isArray(def.fields)) {
            return map;
        }

        def.fields.forEach((field) => {
            if (!field || typeof field !== 'object') {
                return;
            }
            const key = String(field.key || '').trim();
            if (!key) {
                return;
            }
            const labelText = String(field.label || '').trim();
            if (labelText !== '') {
                map[key] = labelText;
            }
        });

        return map;
    }

    function buildGenericWidgetPreviewControls(type, def, settings) {
        if (!def || !Array.isArray(def.fields)) {
            return [];
        }

        const safeSettings = settings && typeof settings === 'object' ? settings : {};
        const controls = [];
        def.fields.forEach((field) => {
            if (!field || typeof field !== 'object') {
                return;
            }

            const key = String(field.key || '').trim();
            if (!key || !isGenericWidgetPreviewFieldRenderable(field)) {
                return;
            }
            if (!isFieldVisibleForInspector(field, safeSettings)) {
                return;
            }

            const value = Object.prototype.hasOwnProperty.call(safeSettings, key) ? safeSettings[key] : '';
            const rendered = renderGenericWidgetPreviewControl(type, field, value);
            if (rendered) {
                controls.push(rendered);
            }
        });

        return controls;
    }

    function isGenericWidgetPreviewFieldRenderable(field) {
        const key = String((field && field.key) || '').trim().toLowerCase();
        if (key === '' || key === '__box' || key.startsWith('__')) {
            return false;
        }

        if (String((field && field.type) || '').trim().toLowerCase() === 'text_style') {
            return false;
        }

        if (isAdvancedInspectorField(field)) {
            return false;
        }

        const textStyleSuffixes = Object.values(TEXT_STYLE_SUFFIX).map((suffix) => String(suffix || '').trim().toLowerCase());
        if (textStyleSuffixes.some((suffix) => suffix !== '' && key.endsWith(suffix))) {
            return false;
        }

        if (/(^_|class$|classes$|id$|slug$|selector|token|nonce|debug|internal|style|css)/.test(key)) {
            return false;
        }

        return true;
    }

    function renderGenericWidgetPreviewControl(type, field, rawValue) {
        const safeType = String((field && field.type) || 'text').trim().toLowerCase();
        const key = String((field && field.key) || '').trim();
        if (!key) {
            return '';
        }

        const labelText = String(field.label || key).trim();
        const requiredHtml = field.required ? '<span class="pb-preview-generic-required" aria-hidden="true">*</span>' : '';
        const safeLabel = escapeHtml(labelText || key);
        let controlHtml = '';

        if (field.repeater && field.repeater.enabled) {
            controlHtml = renderGenericWidgetPreviewRepeaterControl(field, rawValue);
        } else if (field.media !== undefined) {
            controlHtml = renderGenericWidgetPreviewMediaControl(field, rawValue);
        } else if (field.iconPicker) {
            const iconValue = String(rawValue || '').trim();
            if (iconValue !== '') {
                controlHtml = `<div class="pb-preview-generic-icon-line"><span class="pb-preview-generic-icon-preview"><i class="${escapeAttr(iconValue)}" aria-hidden="true"></i></span><input class="pb-preview-generic-input" type="text" value="${escapeAttr(iconValue)}" disabled></div>`;
            } else {
                controlHtml = `<input class="pb-preview-generic-input" type="text" value="" placeholder="${escapeAttr(label('chooseIcon', 'Choose icon'))}" disabled>`;
            }
        } else if (safeType === 'select') {
            const options = Array.isArray(field.options) ? field.options.map((option) => String(option || '').trim()).filter((option) => option !== '') : [];
            const raw = String(rawValue === null || rawValue === undefined ? '' : rawValue).trim();
            const selected = raw !== '' ? raw : (options[0] || '');
            const optionMarkup = options.length
                ? options.map((optionValue) => {
                    const optionLabel = getSelectOptionLabel(field, optionValue);
                    const selectedAttr = String(optionValue) === selected ? ' selected' : '';
                    return `<option value="${escapeAttr(optionValue)}"${selectedAttr}>${escapeHtml(optionLabel)}</option>`;
                }).join('')
                : `<option value="">${escapeHtml(label('builder_inspector_sheet_empty', 'Aucun réglage disponible.'))}</option>`;
            controlHtml = `<select class="pb-preview-generic-select" disabled>${optionMarkup}</select>`;
        } else if (safeType === 'textarea') {
            const textareaValue = formatGenericWidgetPreviewInputValue(field, rawValue);
            controlHtml = `<textarea class="pb-preview-generic-input pb-preview-generic-textarea" rows="3" disabled>${escapeHtml(textareaValue)}</textarea>`;
        } else if (safeType === 'checkbox' || safeType === 'radio') {
            controlHtml = renderGenericWidgetPreviewChoiceControl(field, rawValue, safeType);
        } else if (safeType === 'color') {
            const color = normalizeColor(String(rawValue || '')) || '#64748b';
            controlHtml = `<div class="pb-preview-generic-color-line"><input class="pb-preview-generic-color-input" type="color" value="${escapeAttr(color)}" disabled><input class="pb-preview-generic-input" type="text" value="${escapeAttr(color)}" disabled></div>`;
        } else {
            controlHtml = renderGenericWidgetPreviewTextInputControl(field, rawValue);
        }

        if (!controlHtml) {
            return '';
        }

        return `<div class="pb-preview-generic-control pb-preview-generic-control-${escapeAttr(safeType || 'text')}" data-field-key="${escapeAttr(key)}"><label class="pb-preview-generic-label">${safeLabel}${requiredHtml}</label>${controlHtml}</div>`;
    }

    function renderGenericWidgetPreviewMediaControl(field, rawValue) {
        const src = resolveMediaSrc(String(rawValue || '').trim());
        if (!src) {
            return `<div class="pb-preview-generic-empty">${escapeHtml(label('noMediaSelected', 'Aucun fichier sélectionné'))}</div>`;
        }

        const mediaOptions = normalizeMediaFieldOptions(field.media);
        const isImageLike = mediaOptions.mode === 'images' || /\.(png|jpe?g|gif|svg|webp|avif|bmp|ico)(\?.*)?$/i.test(src);
        if (isImageLike) {
            return `<div class="pb-preview-generic-media"><img class="pb-preview-generic-media-thumb" src="${escapeAttr(src)}" alt=""></div>`;
        }

        const fileName = src.split('/').filter((part) => part !== '').pop() || src;
        return `<div class="pb-preview-generic-media-file"><i class="fas fa-file-alt" aria-hidden="true"></i><span>${escapeHtml(fileName)}</span></div>`;
    }

    function renderGenericWidgetPreviewRepeaterControl(field, rawValue) {
        const repeater = field && field.repeater && typeof field.repeater === 'object' ? field.repeater : {};
        const delimiter = resolveRepeaterDelimiter(repeater.delimiter);
        const items = parseRepeaterValues(rawValue, delimiter)
            .map((item) => String(item || '').trim())
            .filter((item) => item !== '');
        if (!items.length) {
            return `<div class="pb-preview-generic-empty">${escapeHtml(label('builder_inspector_sheet_empty', 'Aucun réglage disponible.'))}</div>`;
        }

        const visibleItems = items.slice(0, 4);
        const listHtml = visibleItems
            .map((item) => `<li>${escapeHtml(item)}</li>`)
            .join('');
        const moreHtml = items.length > visibleItems.length
            ? `<li class="is-more">+${items.length - visibleItems.length}</li>`
            : '';
        return `<ul class="pb-preview-generic-repeater-list">${listHtml}${moreHtml}</ul>`;
    }

    function renderGenericWidgetPreviewChoiceControl(field, rawValue, safeType) {
        const options = Array.isArray(field.options) ? field.options.map((option) => String(option || '').trim()).filter((option) => option !== '') : [];
        const selectedValues = normalizeGenericWidgetPreviewOptionValues(rawValue);
        if (!options.length) {
            const boolState = parseGenericWidgetPreviewToggle(rawValue);
            const boolLabel = boolState === null
                ? ''
                : (boolState ? label('optionOn', 'On') : label('optionOff', 'Off'));
            const checkedAttr = boolState === true ? ' checked' : '';
            return `<label class="pb-preview-generic-choice"><input type="${escapeAttr(safeType)}"${checkedAttr} disabled><span>${escapeHtml(boolLabel || label('optionOn', 'On'))}</span></label>`;
        }

        const choices = options.slice(0, 5).map((optionValue) => {
            const isChecked = selectedValues.includes(optionValue);
            const checkedAttr = isChecked ? ' checked' : '';
            return `<label class="pb-preview-generic-choice"><input type="${escapeAttr(safeType)}" name="pb-preview-generic-${escapeAttr(field.key || 'choice')}" value="${escapeAttr(optionValue)}"${checkedAttr} disabled><span>${escapeHtml(getSelectOptionLabel(field, optionValue))}</span></label>`;
        }).join('');
        return `<div class="pb-preview-generic-choice-list">${choices}</div>`;
    }

    function renderGenericWidgetPreviewTextInputControl(field, rawValue) {
        const safeType = String((field && field.type) || 'text').trim().toLowerCase();
        const inputType = ['text', 'email', 'url', 'tel', 'number', 'date', 'time', 'datetime-local', 'range', 'password'].includes(safeType)
            ? safeType
            : 'text';
        const value = formatGenericWidgetPreviewInputValue(field, rawValue);
        const placeholder = String((field && field.placeholder) || '').trim();
        const valueAttr = value !== '' ? ` value="${escapeAttr(value)}"` : '';
        const placeholderAttr = value === '' && placeholder !== '' ? ` placeholder="${escapeAttr(placeholder)}"` : '';
        return `<input class="pb-preview-generic-input" type="${escapeAttr(inputType)}"${valueAttr}${placeholderAttr} disabled>`;
    }

    function formatGenericWidgetPreviewInputValue(field, rawValue) {
        const safeType = String((field && field.type) || 'text').trim().toLowerCase();
        const text = String(rawValue === null || rawValue === undefined ? '' : rawValue).trim();

        if (safeType === 'select') {
            if (text === '') {
                return '';
            }
            return String(getSelectOptionLabel(field, text) || '').trim();
        }

        const boolState = parseGenericWidgetPreviewToggle(rawValue);
        if (boolState !== null && !['text', 'textarea', 'url', 'email', 'tel', 'number', 'range', 'date', 'time', 'datetime-local'].includes(safeType)) {
            return boolState ? label('optionOn', 'On') : label('optionOff', 'Off');
        }

        if (safeType === 'number' || safeType === 'range') {
            const numberValue = Number(rawValue);
            if (Number.isFinite(numberValue)) {
                return String(numberValue);
            }
        }

        if (text === '') {
            return '';
        }

        return text.length > 160 ? `${text.slice(0, 157)}...` : text;
    }

    function parseGenericWidgetPreviewToggle(rawValue) {
        if (typeof rawValue === 'boolean') {
            return rawValue;
        }
        const normalized = String(rawValue === null || rawValue === undefined ? '' : rawValue).trim().toLowerCase();
        if (normalized === '') {
            return null;
        }
        if (['1', 'true', 'on', 'yes', 'oui'].includes(normalized)) {
            return true;
        }
        if (['0', 'false', 'off', 'no', 'non'].includes(normalized)) {
            return false;
        }
        return null;
    }

    function normalizeGenericWidgetPreviewOptionValues(rawValue) {
        if (Array.isArray(rawValue)) {
            return rawValue
                .map((item) => String(item || '').trim())
                .filter((item) => item !== '');
        }

        const text = String(rawValue === null || rawValue === undefined ? '' : rawValue).trim();
        if (text === '') {
            return [];
        }

        return text
            .split(/[,\n|;]/)
            .map((item) => item.trim())
            .filter((item) => item !== '');
    }

    function collectGenericWidgetPreviewRows(settings, fieldLabels) {
        if (!settings || typeof settings !== 'object') {
            return [];
        }

        const rows = [];
        Object.keys(settings).forEach((key) => {
            if (key === '__box') {
                return;
            }

            const value = normalizeGenericWidgetPreviewValue(settings[key]);
            if (!value) {
                return;
            }

            const mappedLabel = fieldLabels && Object.prototype.hasOwnProperty.call(fieldLabels, key)
                ? String(fieldLabels[key] || '').trim()
                : '';
            const labelText = mappedLabel !== ''
                ? mappedLabel
                : String(key || '').replace(/[_-]+/g, ' ').trim();
            if (!labelText) {
                return;
            }

            rows.push({
                key: labelText,
                value: value,
            });
        });

        return rows;
    }

    function normalizeGenericWidgetPreviewValue(rawValue) {
        if (typeof rawValue === 'string') {
            const text = rawValue.trim();
            if (!text) return '';
            return text.length > 80 ? `${text.slice(0, 77)}...` : text;
        }

        if (typeof rawValue === 'number') {
            return Number.isFinite(rawValue) ? String(rawValue) : '';
        }

        if (typeof rawValue === 'boolean') {
            return rawValue ? label('optionOn', 'On') : label('optionOff', 'Off');
        }

        if (Array.isArray(rawValue)) {
            const parts = rawValue
                .map((item) => normalizeGenericWidgetPreviewValue(item))
                .filter((item) => !!item)
                .slice(0, 3);
            if (!parts.length) {
                return '';
            }
            const text = parts.join(', ');
            return text.length > 80 ? `${text.slice(0, 77)}...` : text;
        }

        return '';
    }

    function isMediaLinkWidgetType(type) {
        const safeType = String(type || '').trim().toLowerCase();
        return ['document', 'pdf', 'spreadsheet', 'archive'].includes(safeType);
    }

    function renderVideoWidgetPreview(settings) {
        const src = resolveMediaSrc(getMediaWidgetSource(settings));
        if (!src) {
            return `<div class="pb-empty-state pb-empty-state-lg">${escapeHtml(label('noMediaSelected', 'Aucun fichier sélectionné'))}</div>`;
        }

        const poster = resolveImageSrc(String(settings.poster || ''));
        const posterAttr = poster ? ` poster="${escapeAttr(poster)}"` : '';
        const align = normalizeAlign(String(settings.align || 'left'));
        const width = clampNumber(settings.width, 20, 100, 100);
        return `<div class="pb-preview-media pb-preview-media-video pb-preview-align pb-preview-align-${escapeAttr(align)}"><video controls preload="metadata" playsinline src="${escapeAttr(src)}"${posterAttr} class="pb-preview-media-el" data-media-width="${width}"></video></div>`;
    }

    function renderMusicWidgetPreview(settings) {
        const src = resolveMediaSrc(getMediaWidgetSource(settings));
        if (!src) {
            return `<div class="pb-empty-state pb-empty-state-sm">${escapeHtml(label('noMediaSelected', 'Aucun fichier sélectionné'))}</div>`;
        }

        const align = normalizeAlign(String(settings.align || 'left'));
        const width = clampNumber(settings.width, 20, 100, 100);
        return `<div class="pb-preview-media pb-preview-media-audio pb-preview-align pb-preview-align-${escapeAttr(align)}"><audio controls preload="metadata" src="${escapeAttr(src)}" class="pb-preview-media-el" data-media-width="${width}"></audio></div>`;
    }

    function renderMediaLinkWidgetPreview(type, settings) {
        const safeType = String(type || '').trim().toLowerCase();
        const iconClass = getMediaWidgetIcon(safeType);
        const fallbackLabel = getMediaWidgetDefaultLabel(safeType);
        const src = resolveMediaSrc(getMediaWidgetSource(settings));
        const target = ['_self', '_blank'].includes(String(settings.target || '')) ? String(settings.target) : '_self';
        const align = normalizeAlign(String(settings.align || 'left'));
        const autoLabel = inferMediaLabel({}, src);
        const labelText = escapeHtml(String(settings.label || autoLabel || fallbackLabel));
        const href = src || '#';

        return `<div class="pb-preview-align pb-preview-align-${escapeAttr(align)}"><a href="${escapeAttr(href)}" target="${escapeAttr(target)}" class="pb-media-link"><i class="${escapeAttr(iconClass)}" aria-hidden="true"></i> <span>${labelText}</span></a></div>`;
    }

    function parseWidgetPreviewLines(raw) {
        return String(raw || '')
            .split(/\r\n|\r|\n/)
            .map((line) => String(line || '').trim())
            .filter((line) => line !== '');
    }

    function renderStyledPreviewText(rawText, tag, className, styleState, allowRichContent) {
        const text = String(rawText || '').trim();
        if (!text) {
            return '';
        }

        const style = styleState && typeof styleState === 'object' ? styleState : {};
        const align = normalizeAlign(String(style.align || 'left'));
        const font = normalizeTextStyleFont(style.font || 'inherit');
        const color = normalizeColor(String(style.color || ''));
        const icon = String(style.icon || '').trim();
        const iconPosition = normalizeTextStyleIconPosition(style.iconPosition || 'start');
        const listStyle = normalizeTextStyleList(style.list || 'none');

        const attrs = [
            `class="${escapeAttr(className)} pb-preview-styled-text"`,
            `data-text-align="${escapeAttr(align)}"`,
            `data-text-font="${escapeAttr(font)}"`,
            `data-text-size="${escapeAttr(normalizeTextStyleSize(style.size || 'inherit'))}"`,
            `data-text-bold="${normalizeTextStyleToggle(style.bold, false) ? '1' : '0'}"`,
            `data-text-italic="${normalizeTextStyleToggle(style.italic, false) ? '1' : '0'}"`,
            `data-text-underline="${normalizeTextStyleToggle(style.underline, false) ? '1' : '0'}"`,
        ];
        if (color) {
            attrs.push(`data-text-color="${escapeAttr(color)}"`);
        }
        if (listStyle !== 'none') {
            attrs.push(`data-text-list="${escapeAttr(listStyle)}"`);
        }

        const content = allowRichContent ? sanitizeRichText(text) : escapeHtml(text);
        const listMarkerGlyph = !allowRichContent ? getTextStyleListGlyph(listStyle) : '';
        const listMarkerNode = listMarkerGlyph
            ? `<span class="pb-preview-text-list-marker pb-preview-text-list-marker-${escapeAttr(listStyle)}" aria-hidden="true">${escapeHtml(listMarkerGlyph)}</span>`
            : '';
        const iconNode = icon ? `<i class="${escapeAttr(icon)} pb-preview-text-icon pb-preview-text-icon-${escapeAttr(iconPosition)}" aria-hidden="true"></i>` : '';
        const textNode = `<span class="pb-preview-text-content">${content}</span>`;
        const contentNode = iconNode
            ? (iconPosition === 'end' ? `${textNode}${iconNode}` : `${iconNode}${textNode}`)
            : textNode;
        const inner = listMarkerNode ? `${listMarkerNode}${contentNode}` : contentNode;

        return `<${tag} ${attrs.join(' ')}>${inner}</${tag}>`;
    }

    function resolveWidgetTextStyle(settings, prefix, fallbackAlign) {
        const safePrefix = String(prefix || '').trim();
        if (!safePrefix) {
            return {
                align: normalizeAlign(String(fallbackAlign || 'left')),
                font: 'inherit',
                size: 'inherit',
                bold: false,
                italic: false,
                underline: false,
                color: '',
                list: 'none',
                icon: '',
                iconPosition: 'start',
            };
        }
        return resolveTextStyleState(settings, safePrefix, fallbackAlign);
    }

    function renderCtaBannerWidgetPreview(settings) {
        const title = String(settings.title || label('defaultCtaTitle', '')).trim();
        const text = String(settings.text || label('defaultCtaText', '')).trim();
        const showPrimaryCta = normalizeTextStyleToggle(settings.showPrimaryCta, true);
        const showSecondaryCta = normalizeTextStyleToggle(settings.showSecondaryCta, true);
        const primaryLabel = showPrimaryCta ? escapeHtml(String(settings.primaryLabel || label('defaultCtaPrimaryLabel', '')).trim()) : '';
        const secondaryLabel = showSecondaryCta ? escapeHtml(String(settings.secondaryLabel || label('defaultCtaSecondaryLabel', '')).trim()) : '';
        const align = normalizeAlign(String(settings.align || 'left'));
        const titleStyle = resolveWidgetTextStyle(settings, 'titleStyle', align);
        const textStyle = resolveWidgetTextStyle(settings, 'descriptionStyle', align);
        const variant = ['primary', 'secondary', 'ghost'].includes(String(settings.variant || ''))
            ? String(settings.variant || '')
            : 'primary';

        return `
            <div class="pb-preview-form pb-preview-align pb-preview-align-${escapeAttr(align)}">
                ${renderStyledPreviewText(title, 'strong', 'pb-preview-cta-title', titleStyle, false)}
                ${renderStyledPreviewText(text, 'p', 'pb-preview-cta-text', textStyle, false)}
                ${(primaryLabel || secondaryLabel) ? `<div class="pb-preview-actions">${primaryLabel ? `<span class="btn btn-${escapeAttr(variant)} pb-btn pb-btn-${escapeAttr(variant)}">${primaryLabel}</span>` : ''}${secondaryLabel ? `<span class="btn btn-ghost pb-btn pb-btn-ghost">${secondaryLabel}</span>` : ''}</div>` : ''}
            </div>
        `;
    }

    function renderFaqWidgetPreview(settings) {
        const title = String(settings.title || label('defaultFaqTitle', '')).trim();
        const align = normalizeAlign(String(settings.align || 'left'));
        const titleStyle = resolveWidgetTextStyle(settings, 'titleStyle', align);
        const questionStyle = resolveWidgetTextStyle(settings, 'questionStyle', align);
        const answerStyle = resolveWidgetTextStyle(settings, 'answerStyle', align);
        const questions = parseWidgetPreviewLines(settings.questions || label('defaultFaqQuestions', ''));
        const answers = parseWidgetPreviewLines(settings.answers || label('defaultFaqAnswers', ''));
        const count = Math.max(questions.length, answers.length, 1);
        const items = Array.from({ length: Math.min(count, 4) }, (_, index) => {
            const question = String(questions[index] || '').trim();
            const answer = String(answers[index] || '').trim();
            const questionNode = renderStyledPreviewText(question || label('widgetFaq', 'FAQ'), 'strong', 'pb-preview-faq-question', questionStyle, false);
            const answerNode = answer ? renderStyledPreviewText(answer, 'span', 'pb-preview-faq-answer', answerStyle, false) : '';
            return `<li>${questionNode}${answerNode}</li>`;
        }).join('');

        return `
            <div class="pb-preview-align pb-preview-align-${escapeAttr(align)}">
                ${renderStyledPreviewText(title, 'strong', 'pb-preview-faq-title', titleStyle, false)}
                <ul class="pb-preview-links pb-preview-links-style-none">${items}</ul>
            </div>
        `;
    }

    function renderLegalSectionWidgetPreview(settings) {
        const title = String(settings.title || label('defaultLegalTitle', '')).trim();
        const text = String(settings.text || label('defaultLegalText', '')).trim();
        const align = normalizeAlign(String(settings.align || 'left'));
        const titleStyle = resolveWidgetTextStyle(settings, 'titleStyle', align);
        const textStyle = resolveWidgetTextStyle(settings, 'descriptionStyle', align);
        const links = parseLinks(String(settings.links || label('defaultLegalLinks', '')));
        const linksHtml = links.slice(0, 3).map((entry) => {
            const href = sanitizeUrl(entry.url) || '#';
            return `<li><a href="${escapeAttr(href)}">${escapeHtml(entry.label || href)}</a></li>`;
        }).join('');

        return `
            <div class="pb-preview-align pb-preview-align-${escapeAttr(align)}">
                ${renderStyledPreviewText(title, 'strong', 'pb-preview-legal-title', titleStyle, false)}
                ${renderStyledPreviewText(text, 'p', 'pb-preview-legal-text', textStyle, true)}
                ${linksHtml ? `<ul class="pb-preview-links pb-preview-links-style-none">${linksHtml}</ul>` : ''}
            </div>
        `;
    }

    function renderAddressWidgetPreview(settings) {
        const title = escapeHtml(String(settings.title || label('defaultAddressTitle', '')).trim());
        const street = String(settings.street || '').trim();
        const postalCode = String(settings.postalCode || '').trim();
        const city = String(settings.city || '').trim();
        const country = String(settings.country || '').trim();
        const phone = String(settings.phone || '').trim();
        const email = String(settings.email || '').trim();
        const align = normalizeAlign(String(settings.align || 'left'));

        const addressParts = [];
        if (street) {
            addressParts.push(street);
        }
        const cityLine = `${postalCode} ${city}`.trim();
        if (cityLine) {
            addressParts.push(cityLine);
        }
        if (country) {
            addressParts.push(country);
        }
        const addressValue = addressParts.join(', ');

        const rows = [
            { icon: 'fas fa-location-dot', value: addressValue },
            { icon: 'fas fa-phone', value: phone },
            { icon: 'fas fa-envelope', value: email },
        ];

        const rowsHtml = rows.map((row) => `
            <div class="pb-preview-address-row">
                <span class="pb-preview-address-icon"><i class="${escapeAttr(row.icon)}" aria-hidden="true"></i></span>
                <span class="pb-preview-address-copy">
                    <span>${row.value ? escapeHtml(String(row.value)) : '&mdash;'}</span>
                </span>
            </div>
        `).join('');

        return `
            <div class="pb-preview-address pb-preview-align pb-preview-align-${escapeAttr(align)}">
                ${title ? `<strong>${title}</strong>` : ''}
                <div class="pb-preview-address-rows">${rowsHtml}</div>
            </div>
        `;
    }

    function renderMapWidgetPreview(settings) {
        const title = escapeHtml(String(settings.title || label('defaultMapTitle', 'Map')));
        const embedUrl = resolveEmbedUrl(String(settings.embedUrl || ''));
        const height = clampNumber(settings.height, 160, 640, 280);
        const align = normalizeAlign(String(settings.align || 'left'));

        if (!embedUrl) {
            return `<div class="pb-empty-state pb-empty-state-sm">${escapeHtml(label('mapEmpty', ''))}</div>`;
        }

        return `
            <div class="pb-preview-map pb-preview-align pb-preview-align-${escapeAttr(align)}">
                ${title ? `<strong>${title}</strong>` : ''}
                <div class="pb-preview-map-frame-wrap">
                    <iframe class="pb-preview-map-frame" src="${escapeAttr(embedUrl)}" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade" data-map-height="${height}"></iframe>
                </div>
            </div>
        `;
    }

    function renderSitemapWidgetPreview(settings) {
        const title = escapeHtml(String(settings.title || label('defaultSitemapTitle', 'Sitemap')).trim());
        const align = normalizeAlign(String(settings.align || 'left'));
        const pagesLimit = clampNumber(settings.pagesLimit, 0, 50, 8);
        const postsLimit = clampNumber(settings.postsLimit, 0, 50, 8);
        const categoriesLimit = clampNumber(settings.categoriesLimit, 0, 50, 8);

        const rows = [
            { label: 'Pages', value: String(Math.round(pagesLimit)) },
            { label: 'Posts', value: String(Math.round(postsLimit)) },
            { label: 'Categories', value: String(Math.round(categoriesLimit)) },
        ];

        return `
            <div class="pb-preview-sitemap pb-preview-align pb-preview-align-${escapeAttr(align)}">
                ${title ? `<strong>${title}</strong>` : ''}
                <ul class="pb-preview-sitemap-list">
                    ${rows.map((row) => `<li><span>${escapeHtml(row.label)}</span><strong>${escapeHtml(row.value)}</strong></li>`).join('')}
                </ul>
            </div>
        `;
    }

    function renderCardsWidgetPreview(settings) {
        const title = escapeHtml(String(settings.title || label('defaultCardsTitle', 'Cards')).trim());
        const align = normalizeAlign(String(settings.align || 'left'));
        const variantRaw = String(settings.variant || 'default').toLowerCase();
        const variant = ['default', 'outline', 'soft'].includes(variantRaw) ? variantRaw : 'default';
        const columns = clampNumber(settings.columns, 1, 4, 3);

        const headers = parseRepeaterLines(settings.headers);
        const itemsTitles = parseRepeaterLines(String(settings.titles || label('defaultCardsItemsTitles', '')));
        const itemsBodies = parseRepeaterLines(String(settings.bodies || label('defaultCardsItemsBodies', '')));
        const itemsFooters = parseRepeaterLines(settings.footers);
        const itemsImages = parseRepeaterLines(settings.images);
        const itemsIcons = parseRepeaterLines(settings.icons);
        const itemsLinks = parseRepeaterLines(settings.links);
        const linkLabel = String(settings.linkLabel || label('defaultCardsLinkLabel', 'Read more')).trim();
        const maxItems = Math.max(itemsTitles.length, itemsBodies.length, headers.length, itemsFooters.length, itemsImages.length, itemsIcons.length, itemsLinks.length, 1);
        const limit = Math.min(8, maxItems);
        const cards = [];
        for (let i = 0; i < limit; i += 1) {
            cards.push({
                header: headers[i] || '',
                title: itemsTitles[i] || '',
                body: itemsBodies[i] || '',
                footer: itemsFooters[i] || '',
                image: resolveImageSrc(itemsImages[i] || ''),
                icon: String(itemsIcons[i] || '').trim(),
                link: itemsLinks[i] || '',
            });
        }

        return `
            <div class="pb-preview-cards pb-preview-align pb-preview-align-${escapeAttr(align)}" data-cards-columns="${Math.round(columns)}">
                ${title ? `<strong>${title}</strong>` : ''}
                <div class="pb-preview-cards-grid pb-preview-cards-variant-${escapeAttr(variant)}">
                    ${cards.map((card) => {
                        const href = sanitizeUrl(card.link) || '#';
                        const hasLink = href !== '#';
                        const linkNode = hasLink && linkLabel
                            ? `<a class="pb-preview-card-link" href="${escapeAttr(href)}" target="_blank" rel="noopener noreferrer">${escapeHtml(linkLabel)}</a>`
                            : '';
                        const mediaNode = card.image
                            ? `<div class="pb-preview-card-media"><img class="pb-preview-card-image" src="${escapeAttr(card.image)}" alt="${escapeAttr(card.title || title || 'Card')}"></div>`
                            : '';
                        const iconNode = card.icon
                            ? `<i class="${escapeAttr(card.icon)} pb-preview-card-title-icon" aria-hidden="true"></i>`
                            : '';
                        const titleNode = (card.title || iconNode)
                            ? `<h4 class="pb-preview-card-title">${iconNode}${card.title ? `<span class="pb-preview-card-title-text">${escapeHtml(card.title)}</span>` : ''}</h4>`
                            : '';
                        return `
                            <article class="pb-preview-card">
                                ${card.header ? `<header class="pb-preview-card-head">${escapeHtml(card.header)}</header>` : ''}
                                ${mediaNode}
                                ${titleNode}
                                ${card.body ? `<div class="pb-preview-card-body">${escapeHtml(card.body)}</div>` : ''}
                                ${(card.footer || linkNode) ? `<footer class="pb-preview-card-foot">${card.footer ? `<span>${escapeHtml(card.footer)}</span>` : ''}${linkNode}</footer>` : ''}
                            </article>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    function renderSliderWidgetPreview(settings) {
        const title = escapeHtml(String(settings.title || label('defaultSliderTitle', 'Slider')).trim());
        const align = normalizeAlign(String(settings.align || 'left'));
        const slideTitles = parseRepeaterLines(String(settings.slideTitles || label('defaultSliderItemsTitles', '')));
        const slideTexts = parseRepeaterLines(String(settings.slideTexts || label('defaultSliderItemsTexts', '')));
        const slideImages = parseRepeaterLines(settings.slideImages);
        const slideLinks = parseRepeaterLines(settings.slideLinks);
        const linkLabel = String(settings.linkLabel || label('defaultSliderLinkLabel', 'Discover')).trim();
        const height = clampNumber(settings.height, 160, 720, 320);
        const showArrows = String(settings.showArrows || 'on').toLowerCase() !== 'off';
        const showDots = String(settings.showDots || 'on').toLowerCase() !== 'off';
        const maxSlides = Math.max(slideTitles.length, slideTexts.length, slideImages.length, slideLinks.length, 1);
        const limit = Math.min(8, maxSlides);
        const slides = [];
        for (let i = 0; i < limit; i += 1) {
            slides.push({
                title: slideTitles[i] || '',
                text: slideTexts[i] || '',
                image: resolveImageSrc(slideImages[i] || ''),
                link: sanitizeUrl(slideLinks[i] || ''),
            });
        }

        const firstSlide = slides[0] || { title: '', text: '', image: '', link: '' };
        const linkNode = firstSlide.link && linkLabel
            ? `<a class="pb-preview-slider-link" href="${escapeAttr(firstSlide.link)}" target="_blank" rel="noopener noreferrer">${escapeHtml(linkLabel)}</a>`
            : '';

        return `
            <div class="pb-preview-slider pb-preview-align pb-preview-align-${escapeAttr(align)}" data-slider-height="${Math.round(height)}">
                ${title ? `<strong>${title}</strong>` : ''}
                <div class="pb-preview-slider-shell">
                    ${firstSlide.image ? `<img class="pb-preview-slider-image" src="${escapeAttr(firstSlide.image)}" alt="${escapeAttr(firstSlide.title || title || 'Slide')}">` : ''}
                    <div class="pb-preview-slider-overlay">
                        ${firstSlide.title ? `<h4>${escapeHtml(firstSlide.title)}</h4>` : ''}
                        ${firstSlide.text ? `<p>${escapeHtml(firstSlide.text)}</p>` : ''}
                        ${linkNode}
                    </div>
                    ${showArrows ? '<div class="pb-preview-slider-arrows"><span>&lsaquo;</span><span>&rsaquo;</span></div>' : ''}
                </div>
                ${showDots ? `<div class="pb-preview-slider-dots">${slides.map((_slide, index) => `<span class="${index === 0 ? 'is-active' : ''}"></span>`).join('')}</div>` : ''}
            </div>
        `;
    }

    function renderCountdownWidgetPreview(settings) {
        const title = escapeHtml(String(settings.title || label('defaultCountdownTitle', 'Countdown')).trim());
        const align = normalizeAlign(String(settings.align || 'left'));
        const mode = String(settings.mode || 'fixed').toLowerCase() === 'evergreen' ? 'evergreen' : 'fixed';
        const showLabels = String(settings.showLabels || 'on').toLowerCase() !== 'off';
        const now = Date.now();
        let targetTs = now + (7 * 86400000);
        if (mode === 'fixed') {
            const rawTarget = String(settings.targetDate || '').trim();
            const parsed = rawTarget ? Date.parse(rawTarget) : NaN;
            if (Number.isFinite(parsed) && parsed > now) {
                targetTs = parsed;
            }
        } else {
            const days = clampNumber(settings.evergreenDays, 1, 365, 7);
            targetTs = now + (Math.round(days) * 86400000);
        }
        const total = Math.max(0, Math.floor((targetTs - now) / 1000));
        const days = Math.floor(total / 86400);
        const hours = Math.floor((total % 86400) / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        const seconds = total % 60;

        const expiredText = escapeHtml(String(settings.expiredText || label('defaultCountdownExpiredText', 'Expired')).trim());
        const units = [
            { value: String(days).padStart(2, '0'), label: label('quickTemplateCountdownDays', 'Days') },
            { value: String(hours).padStart(2, '0'), label: label('quickTemplateCountdownHours', 'Hours') },
            { value: String(minutes).padStart(2, '0'), label: label('quickTemplateCountdownMinutes', 'Minutes') },
            { value: String(seconds).padStart(2, '0'), label: label('quickTemplateCountdownSeconds', 'Seconds') },
        ];

        return `
            <div class="pb-preview-countdown pb-preview-align pb-preview-align-${escapeAttr(align)}">
                ${title ? `<strong>${title}</strong>` : ''}
                ${total > 0 ? `
                    <div class="pb-preview-countdown-grid">
                        ${units.map((unit) => `
                            <div class="pb-preview-countdown-item">
                                <span class="pb-preview-countdown-value">${escapeHtml(unit.value)}</span>
                                ${showLabels ? `<small class="pb-preview-countdown-label">${escapeHtml(unit.label)}</small>` : ''}
                            </div>
                        `).join('')}
                    </div>
                ` : `
                    ${expiredText ? `<div class="pb-preview-countdown-expired">${expiredText}</div>` : ''}
                `}
            </div>
        `;
    }

    function applyBlockPreviewPresentation(root) {
        if (!root || !(root instanceof Element)) {
            return;
        }

        root.querySelectorAll('[data-text-color]').forEach((el) => {
            const color = normalizeColor(String(el.getAttribute('data-text-color') || ''));
            el.style.color = color || '';
        });

        root.querySelectorAll('[data-text-align]').forEach((el) => {
            const align = normalizeAlign(String(el.getAttribute('data-text-align') || 'left'));
            el.style.textAlign = align;
            el.style.justifySelf = textAlignToJustifySelf(align);
        });

        root.querySelectorAll('[data-text-font]').forEach((el) => {
            const font = normalizeTextStyleFont(String(el.getAttribute('data-text-font') || 'inherit'));
            const family = getTextStyleFontFamily(font);
            el.style.fontFamily = family || '';
        });

        root.querySelectorAll('[data-text-size]').forEach((el) => {
            const size = normalizeTextStyleSize(String(el.getAttribute('data-text-size') || 'inherit'));
            const textTarget = el.querySelector('.pb-preview-text-content') || el;
            const listMarker = el.querySelector('.pb-preview-text-list-marker');
            textTarget.style.fontSize = size !== 'inherit' ? size : '';
            if (listMarker instanceof HTMLElement) {
                listMarker.style.fontSize = size !== 'inherit' ? size : '';
            }
        });

        root.querySelectorAll('[data-text-bold]').forEach((el) => {
            const enabled = normalizeTextStyleToggle(el.getAttribute('data-text-bold'), false);
            const textTarget = el.querySelector('.pb-preview-text-content') || el;
            textTarget.style.fontWeight = enabled ? '700' : '';
        });

        root.querySelectorAll('[data-text-italic]').forEach((el) => {
            const enabled = normalizeTextStyleToggle(el.getAttribute('data-text-italic'), false);
            const textTarget = el.querySelector('.pb-preview-text-content') || el;
            textTarget.style.fontStyle = enabled ? 'italic' : '';
        });

        root.querySelectorAll('[data-text-underline]').forEach((el) => {
            const enabled = normalizeTextStyleToggle(el.getAttribute('data-text-underline'), false);
            const textTarget = el.querySelector('.pb-preview-text-content') || el;
            textTarget.style.textDecoration = enabled ? 'underline' : '';
        });

        root.querySelectorAll('[data-text-list]').forEach((el) => {
            const listStyle = normalizeTextStyleList(el.getAttribute('data-text-list'));
            const targets = [];
            if (el.matches('ul')) {
                targets.push(el);
            }
            el.querySelectorAll('ul').forEach((listNode) => {
                targets.push(listNode);
            });
            targets.forEach((listNode) => {
                listNode.style.listStyleType = listStyle === 'none' ? '' : listStyle;
            });
        });

        root.querySelectorAll('[data-media-width]').forEach((el) => {
            const width = clampNumber(Number(el.getAttribute('data-media-width')), 10, 100, 100);
            el.style.maxWidth = `${width}%`;
            el.style.width = `${width}%`;
            el.style.height = 'auto';
        });

        root.querySelectorAll('[data-spacer-height]').forEach((el) => {
            const height = clampNumber(Number(el.getAttribute('data-spacer-height')), 8, 240, 32);
            el.style.height = `${height}px`;
        });

        root.querySelectorAll('[data-divider-weight], [data-divider-color], [data-divider-length], [data-divider-align]').forEach((el) => {
            if (el.classList.contains('pb-divider')) {
                const line = el.querySelector('.pb-divider-line');
                if (!(line instanceof HTMLElement)) {
                    return;
                }
                const weight = clampNumber(Number(el.getAttribute('data-divider-weight')), 1, 8, 1);
                const color = normalizeColor(String(el.getAttribute('data-divider-color') || ''));
                const length = clampNumber(Number(el.getAttribute('data-divider-length')), 10, 100, 100);
                const align = String(el.getAttribute('data-divider-align') || 'center').trim().toLowerCase();
                line.style.borderTopWidth = `${weight}px`;
                line.style.borderTopColor = color || '';
                line.style.width = `${length}%`;
                line.style.marginLeft = align === 'left' ? '0' : 'auto';
                line.style.marginRight = align === 'right' ? '0' : 'auto';
                el.style.border = 'none';
                el.style.background = 'transparent';
                return;
            }
            if (el.classList.contains('pb-preview-divider')) {
                const weight = clampNumber(Number(el.getAttribute('data-divider-weight')), 1, 8, 1);
                const color = normalizeColor(String(el.getAttribute('data-divider-color') || '#d1d5db')) || '#d1d5db';
                el.style.border = 'none';
                el.style.borderTop = `${weight}px solid ${color}`;
            }
        });

        root.querySelectorAll('[data-icon-size], [data-icon-color]').forEach((el) => {
            const size = clampNumber(Number(el.getAttribute('data-icon-size')), 12, 120, 32);
            const color = normalizeColor(String(el.getAttribute('data-icon-color') || ''));
            el.style.fontSize = `${size}px`;
            el.style.color = color || '';
        });

        root.querySelectorAll('[data-map-height]').forEach((el) => {
            const height = clampNumber(Number(el.getAttribute('data-map-height')), 160, 640, 280);
            el.style.height = `${height}px`;
        });

        root.querySelectorAll('[data-slider-height]').forEach((el) => {
            const height = clampNumber(Number(el.getAttribute('data-slider-height')), 160, 720, 320);
            const shell = el.querySelector('.pb-preview-slider-shell');
            if (shell) {
                shell.style.height = `${height}px`;
            }
        });

        root.querySelectorAll('[data-snap-card-height], [data-snap-card-overlay]').forEach((el) => {
            const cardHeight = clampNumber(Number(el.getAttribute('data-snap-card-height')), 220, 640, 360);
            const overlay = clampNumber(Number(el.getAttribute('data-snap-card-overlay')), 0, 85, 45);
            el.style.setProperty('--pb-preview-snap-card-height', `${Math.round(cardHeight)}px`);
            el.style.setProperty('--pb-preview-snap-overlay-opacity', String(Math.min(1, overlay / 85)));
        });

        initPreviewSnapCards(root);
        initPreviewWysiwyg(root);
    }

    function initPreviewSnapCards(root) {
        if (!root) {
            return;
        }

        root.querySelectorAll('[data-snap-cards-preview="1"]').forEach((widget) => {
            if (!(widget instanceof HTMLElement)) {
                return;
            }
            if (widget.getAttribute('data-preview-snap-cards-ready') === '1') {
                return;
            }

            const track = widget.querySelector('.pb-preview-snap-cards-track');
            if (!(track instanceof HTMLElement)) {
                return;
            }
            const cards = Array.from(track.querySelectorAll('.pb-preview-snap-card'));
            if (!cards.length) {
                return;
            }
            const controls = widget.querySelector('[data-preview-snap-cards-controls]');
            const prevButton = widget.querySelector('[data-preview-snap-cards-prev]');
            const nextButton = widget.querySelector('[data-preview-snap-cards-next]');
            const canMatchMedia = typeof window.matchMedia === 'function';
            const state = {
                activeIndex: -1,
                rafId: 0,
            };
            const isDesktopViewport = () => (canMatchMedia ? !window.matchMedia('(max-width: 900px)').matches : true);
            const hasOverflow = () => Math.ceil(track.scrollWidth - track.clientWidth) > 8;

            const scrollToCard = (card, smooth) => {
                if (!card) {
                    return;
                }
                const trackRect = track.getBoundingClientRect();
                const cardRect = card.getBoundingClientRect();
                const offsetLeft = (cardRect.left - trackRect.left) + track.scrollLeft;
                const targetLeft = offsetLeft - ((trackRect.width / 2) - (cardRect.width / 2));
                const maxLeft = Math.max(0, track.scrollWidth - track.clientWidth);
                const nextLeft = Math.max(0, Math.min(maxLeft, targetLeft));
                if (typeof track.scrollTo === 'function') {
                    track.scrollTo({
                        left: nextLeft,
                        behavior: smooth ? 'smooth' : 'auto',
                    });
                } else {
                    track.scrollLeft = nextLeft;
                }
            };

            const setActiveCard = (nextIndex, centerCard, smooth) => {
                const boundedIndex = Math.max(0, Math.min(cards.length - 1, nextIndex));
                if (state.activeIndex !== boundedIndex) {
                    state.activeIndex = boundedIndex;
                }

                cards.forEach((card, cardIndex) => {
                    card.classList.toggle('is-center', cardIndex === boundedIndex);
                });
                const activeCard = cards[boundedIndex] || null;

                if (centerCard && activeCard) {
                    scrollToCard(activeCard, smooth);
                }
            };

            const resolveCenteredIndex = () => {
                const trackRect = track.getBoundingClientRect();
                const trackCenter = trackRect.left + (trackRect.width / 2);
                let bestIndex = 0;
                let bestDistance = Number.POSITIVE_INFINITY;
                cards.forEach((card, cardIndex) => {
                    const cardRect = card.getBoundingClientRect();
                    const cardCenter = cardRect.left + (cardRect.width / 2);
                    const distance = Math.abs(cardCenter - trackCenter);
                    if (distance < bestDistance) {
                        bestDistance = distance;
                        bestIndex = cardIndex;
                    }
                });
                return bestIndex;
            };

            const syncControls = () => {
                const canNavigate = cards.length > 1 && hasOverflow();
                const showDesktopControls = isDesktopViewport() && canNavigate;

                if (controls instanceof HTMLElement) {
                    controls.hidden = !showDesktopControls;
                }

                if (prevButton instanceof HTMLButtonElement) {
                    prevButton.disabled = !canNavigate;
                }

                if (nextButton instanceof HTMLButtonElement) {
                    nextButton.disabled = !canNavigate;
                }
            };

            const queueCenterSync = () => {
                syncControls();
                if (!isDesktopViewport()) {
                    return;
                }

                if (state.rafId) {
                    return;
                }
                state.rafId = window.requestAnimationFrame(() => {
                    state.rafId = 0;
                    setActiveCard(resolveCenteredIndex(), false, false);
                });
            };

            const stepBy = (delta, smooth) => {
                if (cards.length <= 1 || !hasOverflow()) {
                    return;
                }
                const baseIndex = state.activeIndex >= 0 ? state.activeIndex : 0;
                const nextIndex = (baseIndex + delta + cards.length) % cards.length;
                setActiveCard(nextIndex, true, smooth);
            };

            if (prevButton instanceof HTMLButtonElement) {
                prevButton.addEventListener('click', () => {
                    stepBy(-1, true);
                });
            }

            if (nextButton instanceof HTMLButtonElement) {
                nextButton.addEventListener('click', () => {
                    stepBy(1, true);
                });
            }

            cards.forEach((card, cardIndex) => {
                card.addEventListener('click', (event) => {
                    if (event.target instanceof Element && event.target.closest('a')) {
                        return;
                    }
                    if (!isDesktopViewport()) {
                        return;
                    }
                    setActiveCard(cardIndex, true, true);
                });
                card.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }
                    event.preventDefault();
                    if (!isDesktopViewport()) {
                        return;
                    }
                    setActiveCard(cardIndex, true, true);
                });

                const image = card.querySelector('.pb-preview-snap-card-image');
                if (image instanceof HTMLImageElement && !image.complete) {
                    image.addEventListener('load', queueCenterSync, { once: true });
                    image.addEventListener('error', queueCenterSync, { once: true });
                }
            });

            track.addEventListener('scroll', queueCenterSync, { passive: true });
            track.addEventListener('pointerup', queueCenterSync);
            track.addEventListener('touchend', queueCenterSync, { passive: true });
            window.addEventListener('resize', queueCenterSync);

            const initialIndex = cards.length >= 2 ? 1 : 0;
            setActiveCard(initialIndex, false, false);
            syncControls();
            queueCenterSync();
            widget.setAttribute('data-preview-snap-cards-ready', '1');
        });
    }

    function initPreviewWysiwyg(root) {
        const sun = window.FlatCMSSunEditor;
        const canUseSunEditor = !!(sun && typeof sun.create === 'function');
        if (!canUseSunEditor) {
            return;
        }

        root.querySelectorAll('textarea.pb-preview-wysiwyg').forEach((textarea) => {
            if (!(textarea instanceof HTMLTextAreaElement)) {
                return;
            }

            if (textarea.getAttribute('data-pb-preview-wysiwyg-ready') === '1') {
                return;
            }

            try {
                if (textarea.__pbPreviewSunEditorHandle && typeof textarea.__pbPreviewSunEditorHandle.destroy === 'function') {
                    textarea.__pbPreviewSunEditorHandle.destroy();
                }
                textarea.__pbPreviewSunEditorHandle = sun.create(textarea, {
                    minHeight: '120px',
                    height: 170,
                    applyAccordion: false,
                    buttonList: getInlineSunEditorButtonList(),
                    onReady: (editorInstance) => {
                        if (textarea.__pbPreviewSunEditorHandle && textarea.__pbPreviewSunEditorHandle.editor !== editorInstance) {
                            textarea.__pbPreviewSunEditorHandle.editor = editorInstance;
                        }
                        attachInlineSunEditorMediaButtons(textarea, textarea.__pbPreviewSunEditorHandle || { editor: editorInstance });
                        attachInlineSunEditorLinkTools(textarea, textarea.__pbPreviewSunEditorHandle || { editor: editorInstance });
                    },
                    onInput: (nextHtml) => {
                        textarea.value = String(nextHtml || '');
                    },
                    onChange: (nextHtml) => {
                        textarea.value = String(nextHtml || '');
                    },
                });
                if (!textarea.__pbPreviewSunEditorHandle) {
                    throw new Error('SunEditor unavailable');
                }
                textarea.setAttribute('data-pb-preview-wysiwyg-ready', '1');
            } catch (error) {
                textarea.setAttribute('data-pb-preview-wysiwyg-ready', '0');
            }
        });
    }

    function getMediaWidgetSource(settings) {
        const srcValue = String((settings && settings.src) || '').trim();
        if (srcValue !== '') {
            return srcValue;
        }
        return String((settings && settings.url) || '').trim();
    }

    function inferMediaLabel(file, fallbackPath) {
        const candidates = [
            file && file.original_name,
            file && file.name,
            file && file.filename,
            fallbackPath,
        ];

        for (let i = 0; i < candidates.length; i += 1) {
            const raw = String(candidates[i] || '').trim();
            if (!raw) continue;
            const base = raw.split('/').pop() || raw;
            const cleaned = base.replace(/\.[a-z0-9]{2,10}$/i, '').trim();
            return cleaned || base;
        }

        return '';
    }

    function getMediaWidgetIcon(type) {
        if (type === 'video') return 'fas fa-video';
        if (type === 'music') return 'fas fa-music';
        if (type === 'document') return 'fas fa-file-alt';
        if (type === 'pdf') return 'fas fa-file-pdf';
        if (type === 'spreadsheet') return 'fas fa-file-excel';
        if (type === 'archive') return 'fas fa-file-archive';
        return 'fas fa-file';
    }

    function getMediaWidgetDefaultLabel(type) {
        if (type === 'document') return label('defaultDocumentLabel', 'defaultDocumentLabel');
        if (type === 'pdf') return label('defaultPdfLabel', 'defaultPdfLabel');
        if (type === 'spreadsheet') return label('defaultSpreadsheetLabel', 'defaultSpreadsheetLabel');
        if (type === 'archive') return label('defaultArchiveLabel', 'defaultArchiveLabel');
        return label('defaultCallToAction', 'defaultCallToAction');
    }

    function renderCustomWidgetPreview(type, settings, def) {
        if (!def) {
            return null;
        }

        const safeSettings = applyWidgetDefaults(type, settings);
        const handler = resolveWidgetPreviewHandler(def, type);
        if (typeof handler === 'function') {
            const result = normalizeWidgetPreviewResult(handler(safeSettings, buildWidgetPreviewContext(type, def)));
            if (result !== null) {
                return result;
            }
        }

        const template = resolveWidgetPreviewTemplate(def);
        if (template) {
            const html = renderWidgetPreviewTemplate(template, safeSettings);
            if (String(html || '').trim() !== '') {
                return html;
            }
        }

        return null;
    }

    function resolveWidgetPreviewHandler(def, type) {
        const handlerKey = String(def.previewHandler || '').trim();
        if (handlerKey !== '' && typeof widgetPreviewRegistry[handlerKey] === 'function') {
            return widgetPreviewRegistry[handlerKey];
        }
        const fallback = String(type || '').trim().toLowerCase();
        if (fallback !== '' && typeof widgetPreviewRegistry[fallback] === 'function') {
            return widgetPreviewRegistry[fallback];
        }
        return null;
    }

    function resolveWidgetPreviewTemplate(def) {
        if (typeof def.previewTemplate === 'string' && def.previewTemplate.trim() !== '') {
            return def.previewTemplate;
        }
        if (def.preview && typeof def.preview === 'object' && typeof def.preview.template === 'string') {
            return def.preview.template;
        }
        if (typeof def.preview === 'string') {
            return def.preview;
        }
        return '';
    }

    function buildWidgetPreviewContext(type, def) {
        return {
            type: String(type || ''),
            widget: def,
            helpers: {
                escape: escapeHtml,
                escapeAttr: escapeAttr,
                sanitizeRichText: sanitizeRichText,
                resolveImage: resolveImageSrc,
                label: label,
            },
        };
    }

    function normalizeWidgetPreviewResult(result) {
        if (typeof result === 'string') {
            return result;
        }
        if (result && typeof result === 'object' && typeof result.html === 'string') {
            return result.html;
        }
        return null;
    }

    function renderWidgetPreviewTemplate(template, settings) {
        const content = String(template || '');
        if (content.trim() === '') {
            return '';
        }

        return content.replace(/{{{\s*([a-zA-Z0-9_.-]+)\s*}}}|{{\s*([a-zA-Z0-9_.-]+)\s*}}/g, (_match, rawKey, safeKey) => {
            const key = String(rawKey || safeKey || '').trim();
            if (!key) {
                return '';
            }
            const value = resolvePreviewValue(settings, key);
            if (rawKey) {
                return sanitizeRichText(String(value || ''));
            }
            return escapeHtml(String(value || ''));
        });
    }

    function resolvePreviewValue(settings, key) {
        const parts = String(key || '').split('.').filter((part) => part !== '');
        let current = settings;
        for (let i = 0; i < parts.length; i++) {
            if (!current || typeof current !== 'object') {
                return '';
            }
            const next = current[parts[i]];
            if (next === undefined || next === null) {
                return '';
            }
            current = next;
        }

        if (Array.isArray(current)) {
            return current.map((entry) => (entry === null || entry === undefined ? '' : String(entry))).join(', ');
        }
        if (current && typeof current === 'object') {
            return '';
        }
        return current;
    }

    function normalizeConfigWidgetDefs(rawDefs) {
        if (!Array.isArray(rawDefs) || rawDefs.length === 0) {
            return [];
        }

        const normalized = rawDefs
            .map((item) => normalizeWidgetDef(item))
            .filter((item) => !!item);

        const seen = new Set();
        const unique = [];
        normalized.forEach((item) => {
            const type = String(item.type || '').trim().toLowerCase();
            if (type === '' || seen.has(type)) {
                return;
            }
            seen.add(type);
            unique.push(item);
        });

        return unique;
    }

    function normalizeWidgetDef(rawDef) {
        if (!rawDef || typeof rawDef !== 'object') {
            return null;
        }

        const type = String(rawDef.type || '').trim().toLowerCase();
        if (!type) {
            return null;
        }

        const categoryRaw = String(rawDef.category || '').trim().toLowerCase();
        const category = ['content', 'media', 'layout', 'advanced', 'navigation', 'forms'].includes(categoryRaw)
            ? categoryRaw
            : 'advanced';

        const fields = Array.isArray(rawDef.fields)
            ? rawDef.fields.map((field) => normalizeWidgetField(field)).filter((field) => !!field)
            : [];

        const defaults = rawDef.defaults && typeof rawDef.defaults === 'object'
            ? Object.assign({}, rawDef.defaults)
            : {};

        const previewConfig = rawDef.preview && typeof rawDef.preview === 'object' ? rawDef.preview : null;
        const previewTemplate = typeof rawDef.previewTemplate === 'string'
            ? rawDef.previewTemplate
            : (typeof rawDef.preview_template === 'string' ? rawDef.preview_template : (typeof rawDef.preview === 'string' ? rawDef.preview : ''));
        const previewHandler = typeof rawDef.previewHandler === 'string'
            ? rawDef.previewHandler
            : (typeof rawDef.preview_handler === 'string' ? rawDef.preview_handler : (previewConfig && typeof previewConfig.handler === 'string' ? previewConfig.handler : ''));

        applyPagesBuilderWidgetFieldOverrides(type, defaults, fields);

        return {
            type: type,
            label: String(rawDef.label || type),
            icon: String(rawDef.icon || 'fas fa-square'),
            category: category,
            locked: !!rawDef.locked,
            tier: String(rawDef.tier || ''),
            defaults: defaults,
            fields: fields,
            preview: previewConfig || (typeof rawDef.preview === 'string' ? rawDef.preview : null),
            previewTemplate: String(previewTemplate || ''),
            previewHandler: String(previewHandler || ''),
        };
    }

    function applyPagesBuilderWidgetFieldOverrides(type, defaults, fields) {
        const safeType = String(type || '').trim().toLowerCase();
        if (safeType !== 'cta_banner') {
            return;
        }

        defaults.showPrimaryCta = normalizeToggleSettingValue(defaults.showPrimaryCta, 'on');
        defaults.showSecondaryCta = normalizeToggleSettingValue(defaults.showSecondaryCta, 'on');

        const ctaToggleGroup = 'content';

        insertWidgetFieldBefore(fields, 'primaryLabel', {
            key: 'showPrimaryCta',
            label: label('fieldShowPrimaryCta', 'Afficher le bouton principal'),
            type: 'checkbox',
            group: ctaToggleGroup,
        });
        insertWidgetFieldBefore(fields, 'secondaryLabel', {
            key: 'showSecondaryCta',
            label: label('fieldShowSecondaryCta', 'Afficher le bouton secondaire'),
            type: 'checkbox',
            group: ctaToggleGroup,
        });

    }

    function insertWidgetFieldBefore(fields, beforeKey, nextField) {
        if (!Array.isArray(fields) || !nextField || typeof nextField !== 'object') {
            return;
        }

        const normalizedNextKey = String(nextField.key || '').trim().toLowerCase();
        if (normalizedNextKey === '') {
            return;
        }

        const existingIndex = fields.findIndex((field) => String((field && field.key) || '').trim().toLowerCase() === normalizedNextKey);
        const beforeIndex = fields.findIndex((field) => String((field && field.key) || '').trim().toLowerCase() === String(beforeKey || '').trim().toLowerCase());

        if (existingIndex >= 0) {
            fields[existingIndex] = Object.assign({}, fields[existingIndex], nextField);
            return;
        }

        if (beforeIndex >= 0) {
            fields.splice(beforeIndex, 0, nextField);
            return;
        }

        fields.push(nextField);
    }

    function normalizeWidgetField(rawField) {
        if (!rawField || typeof rawField !== 'object') {
            return null;
        }

        const key = String(rawField.key || '').trim();
        if (!key) {
            return null;
        }

        const type = String(rawField.type || 'text').trim().toLowerCase();
        const field = {
            key: key,
            label: String(rawField.label || key),
            type: type || 'text',
        };

        if (Array.isArray(rawField.options)) {
            field.options = rawField.options.map((option) => String(option));
        } else if (rawField.options && typeof rawField.options === 'object') {
            const optionLabels = {};
            const optionKeys = Object.keys(rawField.options);
            field.options = optionKeys.map((optionKey) => {
                optionLabels[optionKey] = String(rawField.options[optionKey] || optionKey);
                return String(optionKey);
            });
            field.optionLabels = optionLabels;
        }
        if (rawField.optionLabels && typeof rawField.optionLabels === 'object' && !Array.isArray(rawField.optionLabels)) {
            const optionLabels = field.optionLabels && typeof field.optionLabels === 'object'
                ? Object.assign({}, field.optionLabels)
                : {};
            Object.keys(rawField.optionLabels).forEach((optionKey) => {
                optionLabels[optionKey] = String(rawField.optionLabels[optionKey] || optionKey);
            });
            field.optionLabels = optionLabels;
        }
        if (rawField.control !== undefined) field.control = String(rawField.control || '').trim().toLowerCase();
        if (rawField.rows !== undefined) field.rows = Number(rawField.rows) || 0;
        if (rawField.min !== undefined) field.min = Number(rawField.min);
        if (rawField.max !== undefined) field.max = Number(rawField.max);
        if (rawField.step !== undefined) field.step = Number(rawField.step);
        if (rawField.placeholder !== undefined) field.placeholder = String(rawField.placeholder || '');
        if (rawField.help !== undefined) field.help = String(rawField.help || '');
        if (rawField.section !== undefined) field.section = String(rawField.section || '');
        if (rawField.sectionLabel !== undefined) field.sectionLabel = String(rawField.sectionLabel || '');
        if (rawField.sectionHelp !== undefined) field.sectionHelp = String(rawField.sectionHelp || '');
        if (rawField.required !== undefined) field.required = !!rawField.required;
        if (rawField.group !== undefined) field.group = String(rawField.group || '');
        if (rawField.wysiwyg !== undefined) field.wysiwyg = !!rawField.wysiwyg;
        if (rawField.wysiwygHeight !== undefined) field.wysiwygHeight = Number(rawField.wysiwygHeight) || 0;
        if (rawField.wysiwygMinHeight !== undefined) field.wysiwygMinHeight = Number(rawField.wysiwygMinHeight) || 0;
        if (rawField.condition && typeof rawField.condition === 'object' && !Array.isArray(rawField.condition)) {
            const conditionField = String(rawField.condition.field || rawField.condition.key || '').trim();
            const operatorRaw = String(rawField.condition.operator || 'equals').trim().toLowerCase();
            const operator = ['equals', 'not_equals', 'contains', 'not_contains', 'empty', 'not_empty', 'gt', 'gte', 'lt', 'lte', 'in', 'not_in'].includes(operatorRaw)
                ? operatorRaw
                : 'equals';
            if (conditionField !== '') {
                field.condition = {
                    field: conditionField,
                    operator: operator,
                    value: String(rawField.condition.value || ''),
                };
            }
        }
        const rawRepeater = rawField.repeater && typeof rawField.repeater === 'object' && !Array.isArray(rawField.repeater)
            ? rawField.repeater
            : null;
        const repeatable = rawField.repeatable !== undefined
            ? !!rawField.repeatable
            : !!(rawRepeater && rawRepeater.enabled);
        if (repeatable || rawRepeater) {
            const min = Math.max(0, Number(rawField.repeat_min !== undefined ? rawField.repeat_min : (rawRepeater ? rawRepeater.min : 0)) || 0);
            const max = Math.max(0, Number(rawField.repeat_max !== undefined ? rawField.repeat_max : (rawRepeater ? rawRepeater.max : 0)) || 0);
            field.repeater = {
                enabled: repeatable || !!(rawRepeater && rawRepeater.enabled),
                min: min,
                max: max,
                delimiter: String(rawField.repeat_delimiter !== undefined ? rawField.repeat_delimiter : (rawRepeater ? rawRepeater.delimiter : '\n')) || '\n',
                itemLabel: String(rawField.repeat_item_label !== undefined ? rawField.repeat_item_label : (rawRepeater ? rawRepeater.itemLabel : '')),
            };
        }
        if (rawField.responsive !== undefined) field.responsive = !!rawField.responsive;
        if (rawField.media !== undefined) {
            if (rawField.media && typeof rawField.media === 'object' && !Array.isArray(rawField.media)) {
                field.media = {
                    mode: String(rawField.media.mode || ''),
                    folder: String(rawField.media.folder || ''),
                    accept: String(rawField.media.accept || ''),
                    labelField: String(rawField.media.labelField || ''),
                    preview: String(rawField.media.preview || ''),
                };
            } else {
                field.media = !!rawField.media;
            }
        }
        if (rawField.iconPicker !== undefined) field.iconPicker = !!rawField.iconPicker;
        if (rawField.stylePrefix !== undefined) field.stylePrefix = String(rawField.stylePrefix || '');
        if (rawField.previewSource !== undefined) field.previewSource = String(rawField.previewSource || '');
        if (rawField.previewText !== undefined) field.previewText = String(rawField.previewText || '');
        if (Array.isArray(rawField.previewItems)) {
            field.previewItems = rawField.previewItems
                .filter((entry) => !!entry && typeof entry === 'object')
                .map((entry) => ({
                    label: String(entry.label || ''),
                    text: String(entry.text || ''),
                }));
        }
        if (rawField.fallbackAlign !== undefined) field.fallbackAlign = String(rawField.fallbackAlign || '');
        if (rawField.disableList !== undefined) field.disableList = !!rawField.disableList;
        if (rawField.disableIcon !== undefined) field.disableIcon = !!rawField.disableIcon;

        return field;
    }

    function resolveInspectorFieldContainer(field, groupContainers, root, blockType, options) {
        const opts = options || {};
        const groupMapper = typeof opts.groupMapper === 'function' ? opts.groupMapper : null;
        const flattenGroups = !!opts.flattenGroups;
        const groupKeyRaw = resolveInspectorGroupKey(field);
        const groupKey = groupMapper ? groupMapper(groupKeyRaw) : groupKeyRaw;
        const showStepLabels = !!opts.showStepLabels;
        const labelOptions = opts.labelOptions && typeof opts.labelOptions === 'object'
            ? opts.labelOptions
            : {};

        if (flattenGroups) {
            return root;
        }

        if (groupContainers.has(groupKey)) {
            const entry = groupContainers.get(groupKey);
            return resolveInspectorFieldSectionContainer(field, entry, root);
        }

        const groupWrap = document.createElement('section');
        groupWrap.className = 'pb-inspector-group';
        groupWrap.dataset.group = groupKey;

        const groupHead = document.createElement('div');
        groupHead.className = 'pb-inspector-group-toggle';
        groupHead.innerHTML = `
            <span class="pb-inspector-group-title">${escapeHtml(formatInspectorGroupLabel(groupKey, showStepLabels, labelOptions))}</span>
        `;
        groupWrap.appendChild(groupHead);

        const fieldsWrap = document.createElement('div');
        fieldsWrap.className = 'pb-inspector-group-fields';
        groupWrap.appendChild(fieldsWrap);

        root.appendChild(groupWrap);
        const entry = {
            fieldsWrap,
            groupWrap,
            groupHead,
            sectionContainers: new Map(),
        };
        groupContainers.set(groupKey, entry);
        return resolveInspectorFieldSectionContainer(field, entry, root);
    }

    function resolveInspectorFieldSectionContainer(field, groupEntry, root) {
        const entry = groupEntry && typeof groupEntry === 'object' ? groupEntry : null;
        const fieldsWrap = entry && entry.fieldsWrap ? entry.fieldsWrap : null;
        if (!fieldsWrap) {
            return root;
        }

        const sectionKey = normalizeInspectorSectionKey(field);
        if (sectionKey === '') {
            return fieldsWrap;
        }

        const sectionContainers = entry.sectionContainers instanceof Map
            ? entry.sectionContainers
            : new Map();
        entry.sectionContainers = sectionContainers;
        if (sectionContainers.has(sectionKey)) {
            const sectionEntry = sectionContainers.get(sectionKey);
            return sectionEntry && sectionEntry.fieldsWrap ? sectionEntry.fieldsWrap : fieldsWrap;
        }

        const sectionWrap = document.createElement('section');
        sectionWrap.className = 'pb-inspector-section';
        sectionWrap.dataset.section = sectionKey;

        const sectionHead = document.createElement('div');
        sectionHead.className = 'pb-inspector-section-head';
        const sectionTitle = String((field && field.sectionLabel) || '').trim() || formatInspectorSectionLabel(sectionKey);
        const sectionHelp = String((field && field.sectionHelp) || '').trim();

        if (sectionTitle !== '') {
            const titleEl = document.createElement('h4');
            titleEl.className = 'pb-inspector-section-title';
            titleEl.textContent = sectionTitle;
            sectionHead.appendChild(titleEl);
        }

        if (sectionHelp !== '') {
            const helpEl = document.createElement('p');
            helpEl.className = 'pb-inspector-section-help';
            helpEl.textContent = sectionHelp;
            sectionHead.appendChild(helpEl);
        }

        if (sectionHead.childNodes.length > 0) {
            sectionWrap.appendChild(sectionHead);
        }

        const sectionFields = document.createElement('div');
        sectionFields.className = 'pb-inspector-section-fields';
        sectionWrap.appendChild(sectionFields);

        fieldsWrap.appendChild(sectionWrap);
        sectionContainers.set(sectionKey, {
            sectionWrap,
            sectionHead,
            fieldsWrap: sectionFields,
        });
        return sectionFields;
    }

    function normalizeInspectorSectionKey(field) {
        const explicit = String((field && field.section) || '').trim().toLowerCase();
        if (explicit === '') {
            return '';
        }
        return explicit.replace(/[^a-z0-9_-]/g, '');
    }

    function formatInspectorSectionLabel(sectionKey) {
        const normalized = String(sectionKey || '').replace(/[_-]+/g, ' ').trim();
        if (normalized === '') {
            return '';
        }
        return normalized.charAt(0).toUpperCase() + normalized.slice(1);
    }

    function resolveInspectorGroupKey(field) {
        const explicit = String((field && field.group) || '').trim().toLowerCase();
        if (explicit !== '') {
            return explicit.replace(/[^a-z0-9_-]/g, '');
        }

        const key = String((field && field.key) || '').trim().toLowerCase();
        const type = String((field && field.type) || '').trim().toLowerCase();

        if (/(src|image|icon|embed|media|file|poster|background|avatar|thumb)/.test(key)) {
            return 'media';
        }
        if (/(align|variant|columns?|height|width|overlay|radius|size|mode|open|show|targetdate|evergreen|spacing|padding|margin|color)/.test(key) || type === 'color') {
            return 'layout';
        }
        if (/(form|slug|required|success|action|method|target|policy|terms|captcha)/.test(key)) {
            return 'advanced';
        }
        if (/(link|links|url|menu|nav|sitemap)/.test(key)) {
            return 'navigation';
        }
        if (/(email|phone|name|subject|message|placeholder|newsletter|contact)/.test(key)) {
            return 'forms';
        }
        return 'content';
    }

    function isAdvancedInspectorField(field) {
        return resolveInspectorGroupKey(field) === 'advanced';
    }

    function getInspectorSheetTabDisplayOrder() {
        return ['content', 'navigation', 'media', 'layout', 'design', 'advanced'];
    }

    function normalizeInspectorSheetTab(value, blockType) {
        const normalized = String(value || '').trim().toLowerCase();
        if (normalized === 'forms') {
            return 'advanced';
        }
        const allowed = ['all'].concat(getInspectorSheetTabDisplayOrder());
        return allowed.includes(normalized) ? normalized : 'all';
    }

    function resolveInspectorSheetTabGroup(groupKey, blockType) {
        const normalized = String(groupKey || '').trim().toLowerCase();
        if (normalized === 'forms') {
            return 'advanced';
        }
        return normalized;
    }

    function getOrderedInspectorFields(fields, inspectorContext, activeSheetTab, block) {
        const source = Array.isArray(fields) ? fields.slice() : [];
        if (!(inspectorContext === 'sheet' && activeSheetTab === 'all')) {
            return source;
        }

        const blockType = String((block && block.type) || '').trim().toLowerCase();
        const groupOrder = ['content', 'navigation', 'media', 'layout', 'design', 'advanced'];
        const groupedFields = new Map();

        source.forEach((field) => {
            const groupKey = resolveInspectorSheetTabGroup(resolveInspectorGroupKey(field), blockType);
            if (!groupedFields.has(groupKey)) {
                groupedFields.set(groupKey, []);
            }
            groupedFields.get(groupKey).push(field);
        });

        const ordered = [];
        groupOrder.forEach((groupKey) => {
            if (!groupedFields.has(groupKey)) {
                return;
            }
            ordered.push(...groupedFields.get(groupKey));
            groupedFields.delete(groupKey);
        });

        groupedFields.forEach((groupFields) => {
            ordered.push(...groupFields);
        });

        return ordered;
    }

    function collectInspectorSheetGroups(block, def) {
        if (!block || !def || !Array.isArray(def.fields)) {
            return [];
        }

        const blockType = String((block && block.type) || '').trim().toLowerCase();
        const order = ['content', 'navigation', 'media', 'layout', 'advanced'];
        let hasDesignSupport = false;

        const settings = applyWidgetDefaults(block.type, block.settings || {});
        def.fields.forEach((field) => {
            if (!isFieldVisibleForInspector(field, settings) && !shouldKeepConditionalFieldVisible(block, field, settings)) {
                return;
            }
            if (isEssentialInspectorField(block, field)) {
                return;
            }
            const groupKey = resolveInspectorSheetTabGroup(resolveInspectorGroupKey(field), blockType);
            const fieldKey = String((field && field.key) || '').trim().toLowerCase();
            if (groupKey === 'design' || fieldKey === 'usecustomdesign') {
                hasDesignSupport = true;
            }
        });

        return hasDesignSupport
            ? ['content', 'navigation', 'media', 'layout', 'design', 'advanced']
            : order;
    }

    function buildInspectorSheetTabbar(groups) {
        const tabs = Array.isArray(groups) ? groups : [];
        const primitives = window.FlatCMSUIPrimitives || {};
        const entries = [{
            label: label('builder_inspector_sheet_tab_all', 'Tous'),
            active: state.inspectorSheetTab === 'all',
            onClick: () => {
                if (state.inspectorSheetTab === 'all') return;
                state.inspectorSheetTab = 'all';
                renderInspector();
            },
        }];
        tabs.forEach((key) => {
            entries.push({
                label: formatInspectorGroupLabel(key),
                active: state.inspectorSheetTab === key,
                onClick: () => {
                    if (state.inspectorSheetTab === key) return;
                    state.inspectorSheetTab = key;
                    renderInspector();
                },
            });
        });

        if (typeof primitives.createBuilderInspectorTabbar === 'function') {
            return primitives.createBuilderInspectorTabbar({ tabs: entries });
        }

        const wrap = document.createElement('div');
        wrap.className = 'pb-inspector-tabbar';
        wrap.setAttribute('role', 'tablist');
        entries.forEach((entry) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pb-inspector-tab' + (entry.active ? ' is-active' : '');
            btn.setAttribute('role', 'tab');
            btn.setAttribute('aria-selected', entry.active ? 'true' : 'false');
            btn.textContent = entry.label;
            btn.addEventListener('click', entry.onClick);
            wrap.appendChild(btn);
        });
        return wrap;
    }

    function isEssentialInspectorField(block, field) {
        const blockType = String((block && block.type) || '').trim().toLowerCase();
        const fieldKey = String((field && field.key) || '').trim().toLowerCase();
        const fieldType = String((field && field.type) || '').trim().toLowerCase();

        const essentialsByWidget = {
            heading: [],
            text: [],
            image: [],
            video: ['src', 'poster', 'align'],
            music: ['src', 'align'],
            document: ['src', 'label'],
            pdf: ['src', 'label'],
            spreadsheet: ['src', 'label'],
            archive: ['src', 'label'],
            button: [],
            hero: ['title'],
            video_player: [],
            stats_section: ['title'],
            faq_accordion: ['title'],
            logo_cloud: ['title'],
            testimonial_cards: ['title'],
            snap_cards: ['title'],
            feature_grid: ['title'],
            pricing_plans: ['title'],
            content_split_media: ['title'],
            newsletter_section: ['title'],
            contact_section: ['title'],
            cta_banner: ['title', 'text'],
            faq: ['title', 'questions', 'answers'],
            legal_section: ['title', 'text'],
            cards: ['title', 'titles', 'bodies', 'columns', 'variant'],
            slider: ['title', 'titles', 'texts', 'images', 'height'],
            countdown: ['title', 'mode', 'targetdate', 'evergreendays', 'showlabels'],
            newsletter: ['title', 'description', 'placeholder', 'buttonlabel'],
            contact: ['formslug'],
            address: ['title', 'street', 'postalcode', 'city', 'country', 'phone', 'email'],
            map: ['title', 'embedurl', 'height'],
            sitemap: ['title', 'pageslimit', 'postslimit', 'categorieslimit'],
            links: ['title', 'items', 'linkstyle'],
            spacer: [],
            divider: [],
            icon: ['icon', 'size', 'color', 'align'],
            html: [],
            carousel: ['title'],
            nw_carrousel: [],
        };

        const explicitEssentials = essentialsByWidget[blockType];
        if (Array.isArray(explicitEssentials)) {
            return explicitEssentials.includes(fieldKey);
        }

        if (field && field.required) {
            return true;
        }
        if (fieldType === 'textarea' || fieldType === 'text' || fieldType === 'url' || fieldType === 'datetime-local') {
            return /(title|text|description|label|url|src|image|icon|slug|embed|items|questions|answers|name|email|subject|message)/.test(fieldKey);
        }
        return !/(align|variant|mode|target|method|width|height|overlay|radius|spacing|padding|margin|color|size|show|open|autoplay|interval|columns|evergreen|success|recipient|weight)/.test(fieldKey);
    }

    function formatInspectorGroupLabel(groupKey, withStepPrefix, options) {
        const normalized = String(groupKey || '').replace(/[_-]+/g, ' ').trim().toLowerCase();
        const opts = options && typeof options === 'object' ? options : {};
        const mergeContentNavigation = !!opts.mergeContentNavigation;
        const labels = {
            content: mergeContentNavigation
                ? `${label('catContent', 'Contenu')} / ${label('catNavigation', 'Navigation')}`
                : label('catContent', 'Contenu'),
            media: label('catMedia', 'Média'),
            forms: label('catForms', 'Formulaires'),
            navigation: label('catNavigation', 'Navigation'),
            layout: label('catLayout', 'Mise en page'),
            design: label('catDesign', 'Design'),
            advanced: label('catAdvanced', 'Avancé'),
        };
        const baseLabel = labels[normalized] || (normalized === '' ? labels.advanced : (normalized.charAt(0).toUpperCase() + normalized.slice(1)));

        if (!withStepPrefix) {
            return baseLabel;
        }

        const steps = mergeContentNavigation
            ? {
                content: 1,
                media: 2,
                layout: 3,
                design: 4,
                forms: 5,
                advanced: 6,
            }
            : {
                content: 1,
                navigation: 2,
                media: 3,
                layout: 4,
                design: 5,
                forms: 6,
                advanced: 7,
            };
        if (Object.prototype.hasOwnProperty.call(steps, normalized)) {
            return `${steps[normalized]}. ${baseLabel}`;
        }

        return baseLabel;
    }

    function isFieldMatchingInspectorQuery(field, queryTerms) {
        const terms = Array.isArray(queryTerms) ? queryTerms : [];
        if (!terms.length) {
            return true;
        }

        const labelText = String((field && field.label) || '').trim();
        const keyText = String((field && field.key) || '').trim();
        const haystack = normalizeSearchText(`${labelText} ${keyText}`);
        if (!haystack) {
            return false;
        }

        return terms.every((term) => haystack.includes(term));
    }

    function isInspectorFieldWide(field, isRepeaterField, useLinksQuickAdd) {
        if (isRepeaterField || useLinksQuickAdd) {
            return true;
        }

        const type = String((field && field.type) || '').trim().toLowerCase();
        if (type === 'textarea' || type === 'color' || type === 'text_style') {
            return true;
        }
        if (field && (field.iconPicker || field.media || field.responsive)) {
            return true;
        }
        if (field && field.help !== undefined && String(field.help || '').trim() !== '') {
            return true;
        }
        return false;
    }

    function isFieldVisibleForInspector(field, settings) {
        const condition = field && field.condition && typeof field.condition === 'object' ? field.condition : null;
        if (!condition || !condition.field) {
            return true;
        }

        const operator = String(condition.operator || 'equals').trim().toLowerCase();
        const rawValue = settings && Object.prototype.hasOwnProperty.call(settings, condition.field)
            ? settings[condition.field]
            : '';
        const left = String(rawValue === null || rawValue === undefined ? '' : rawValue).trim();
        const right = String(condition.value === null || condition.value === undefined ? '' : condition.value).trim();

        if (operator === 'empty') return left === '';
        if (operator === 'not_empty') return left !== '';
        if (operator === 'equals') return left === right;
        if (operator === 'not_equals') return left !== right;
        if (operator === 'contains') return right !== '' ? left.includes(right) : left !== '';
        if (operator === 'not_contains') return right !== '' ? !left.includes(right) : left === '';

        if (operator === 'gt' || operator === 'gte' || operator === 'lt' || operator === 'lte') {
            const leftNumber = Number(left);
            const rightNumber = Number(right);
            if (!Number.isFinite(leftNumber) || !Number.isFinite(rightNumber)) {
                return false;
            }
            if (operator === 'gt') return leftNumber > rightNumber;
            if (operator === 'gte') return leftNumber >= rightNumber;
            if (operator === 'lt') return leftNumber < rightNumber;
            return leftNumber <= rightNumber;
        }

        if (operator === 'in' || operator === 'not_in') {
            const candidates = right
                .split(/[,\n|;]/)
                .map((item) => item.trim())
                .filter((item) => item !== '');
            const contains = candidates.includes(left);
            return operator === 'in' ? contains : !contains;
        }

        return true;
    }

    function resolveConditionalFieldToggleState(block, field, settings) {
        const blockType = String((block && block.type) || '').trim().toLowerCase();
        const fieldKey = String((field && field.key) || '').trim().toLowerCase();
        if (['hero', 'cta_banner'].includes(blockType)) {
            if (fieldKey === 'primarylabel' || fieldKey === 'primaryurl' || fieldKey === 'primarytarget') {
                const rawPrimary = settings && Object.prototype.hasOwnProperty.call(settings, 'showPrimaryCta')
                    ? settings.showPrimaryCta
                    : 'on';
                return {
                    key: 'showPrimaryCta',
                    enabled: normalizeTextStyleToggle(rawPrimary, true),
                };
            }
            if (fieldKey === 'secondarylabel' || fieldKey === 'secondaryurl' || fieldKey === 'secondarytarget') {
                const rawSecondary = settings && Object.prototype.hasOwnProperty.call(settings, 'showSecondaryCta')
                    ? settings.showSecondaryCta
                    : 'on';
                return {
                    key: 'showSecondaryCta',
                    enabled: normalizeTextStyleToggle(rawSecondary, true),
                };
            }
        }

        return null;
    }

    function shouldKeepConditionalFieldVisible(block, field, settings) {
        return resolveConditionalFieldToggleState(block, field, settings) !== null;
    }

    function isConditionalFieldDisabled(block, field, settings) {
        const toggleState = resolveConditionalFieldToggleState(block, field, settings);
        if (!toggleState) {
            return false;
        }
        return !toggleState.enabled;
    }

    function createToggleSwitchControl(field, value) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderToggleSwitchControl === 'function') {
            return primitives.createBuilderToggleSwitchControl({
                label: String((field && field.label) || ''),
                checked: normalizeTextStyleToggle(value, false),
                wrapperClass: 'pb-switch-control',
                textClass: 'pb-switch-text',
                hitboxClass: 'pb-switch-hitbox',
                inputClass: 'pb-switch-input',
                uiClass: 'pb-switch-ui'
            });
        }

        const switchWrap = document.createElement('div');
        switchWrap.className = 'pb-switch-control';

        const text = document.createElement('span');
        text.className = 'pb-switch-text';
        text.textContent = String((field && field.label) || '');
        switchWrap.appendChild(text);

        const hitbox = document.createElement('label');
        hitbox.className = 'pb-switch-hitbox';
        switchWrap.appendChild(hitbox);

        const input = document.createElement('input');
        input.className = 'pb-switch-input';
        input.type = 'checkbox';
        input.checked = normalizeTextStyleToggle(value, false);
        hitbox.appendChild(input);

        const ui = document.createElement('span');
        ui.className = 'pb-switch-ui';
        ui.setAttribute('aria-hidden', 'true');
        hitbox.appendChild(ui);

        return { element: switchWrap, input };
    }

    function resolveRepeaterDelimiter(value) {
        const raw = String(value === null || value === undefined ? '' : value).trim();
        if (raw === '' || raw === '\\n' || raw.toLowerCase() === 'newline') {
            return '\n';
        }
        if (raw === '\\t') {
            return '\t';
        }
        return raw;
    }

    function parseRepeaterValues(value, delimiter) {
        const raw = String(value === null || value === undefined ? '' : value);
        if (raw === '') {
            return [];
        }
        if (delimiter === '\n') {
            return raw.split(/\r?\n/).map((item) => item.trim());
        }
        return raw.split(delimiter).map((item) => item.trim());
    }

    function parseFeatureGridTextValues(value) {
        const raw = String(value === null || value === undefined ? '' : value).trim();
        if (raw === '') {
            return [];
        }
        if (raw.startsWith('[')) {
            try {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    return parsed.map((item) => String(item === null || item === undefined ? '' : item).trim());
                }
            } catch (error) {
                // Keep legacy newline fallback for older payloads.
            }
        }
        return raw.split(/\r?\n/).map((item) => item.trim());
    }

    function trimTrailingEmptyRepeaterItems(values) {
        const safeValues = Array.isArray(values)
            ? values.map((item) => String(item === null || item === undefined ? '' : item))
            : [];

        let end = safeValues.length;
        while (end > 0 && String(safeValues[end - 1] || '').trim() === '') {
            end -= 1;
        }

        return safeValues.slice(0, end);
    }

    function serializeRepeaterValues(values, delimiter) {
        const safeValues = Array.isArray(values) ? values.map((item) => String(item === null || item === undefined ? '' : item).trim()) : [];
        return safeValues.join(delimiter);
    }

    function serializeFeatureGridTextValues(values) {
        const safeValues = Array.isArray(values)
            ? values.map((item) => String(item === null || item === undefined ? '' : item).trim())
            : [];
        if (safeValues.length === 0) {
            return '';
        }
        return JSON.stringify(safeValues);
    }

    function resolveRepeaterInputType(type) {
        const safeType = String(type || 'text').toLowerCase();
        if (safeType === 'textarea') {
            return 'textarea';
        }
        if (['number', 'range', 'email', 'url', 'tel', 'date', 'time', 'datetime-local'].includes(safeType)) {
            return safeType;
        }
        return 'text';
    }

    function normalizeMediaFieldOptions(rawMedia) {
        const validFolders = new Set(['images', 'videos', 'sounds', 'documents', 'pdf', 'spreadsheets', 'archives']);
        const defaultFolderByMode = (mode) => mode === 'images' ? 'images' : 'documents';
        const acceptByFolder = {
            images: 'image/*',
            videos: 'video/*',
            sounds: 'audio/*',
            documents: '.doc,.docx,.txt,.rtf,.odt',
            pdf: '.pdf',
            spreadsheets: '.xls,.xlsx,.csv,.ods',
            archives: '.zip,.rar,.7z,.tar,.gz',
        };

        if (rawMedia === undefined || rawMedia === null || rawMedia === false) {
            return {
                mode: 'images',
                folder: 'images',
                accept: acceptByFolder.images,
                labelField: '',
                preview: '',
            };
        }

        if (rawMedia === true) {
            return {
                mode: 'images',
                folder: 'images',
                accept: acceptByFolder.images,
                labelField: '',
                preview: '',
            };
        }

        const raw = (rawMedia && typeof rawMedia === 'object') ? rawMedia : {};
        const mode = String(raw.mode || '').toLowerCase() === 'files' ? 'files' : 'images';
        const folderInput = String(raw.folder || defaultFolderByMode(mode)).toLowerCase();
        const folder = validFolders.has(folderInput) ? folderInput : defaultFolderByMode(mode);
        const accept = String(raw.accept || '').trim() || (acceptByFolder[folder] || '*/*');
        const labelField = String(raw.labelField || '').trim();
        const preview = String(raw.preview || '').trim().toLowerCase();

        return {
            mode: mode,
            folder: folder,
            accept: accept,
            labelField: labelField,
            preview: preview === 'image' || preview === 'file' ? preview : '',
        };
    }

    function renderInspectorMediaPreviewContent(mediaOptions, rawValue) {
        const src = resolveMediaSrc(String(rawValue || '').trim());
        if (!src) {
            return `<div class="pb-field-media-preview-empty">${escapeHtml(label('noMediaSelected', 'Aucun fichier sélectionné'))}</div>`;
        }

        const isImageLike = mediaOptions.mode === 'images'
            || mediaOptions.preview === 'image'
            || /\.(png|jpe?g|gif|svg|webp|avif|bmp|ico)(\?.*)?$/i.test(src);
        if (isImageLike) {
            return `<div class="pb-field-media-preview-frame"><img class="pb-field-media-preview-image" src="${escapeAttr(src)}" alt=""></div>`;
        }

        const fileName = src.split('/').filter((part) => part !== '').pop() || src;
        return `<div class="pb-field-media-preview-file"><i class="fas fa-file-alt" aria-hidden="true"></i><span>${escapeHtml(fileName)}</span></div>`;
    }

    function createInspectorMediaPreview(mediaOptions, rawValue) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderMediaPreview === 'function') {
            return primitives.createBuilderMediaPreview({
                mediaOptions,
                rawValue,
                resolveSrc: resolveMediaSrc,
                noMediaLabel: label('noMediaSelected', 'Aucun fichier sélectionné'),
            });
        }
        const preview = document.createElement('div');
        preview.className = 'pb-field-media-preview';
        const update = (nextValue) => {
            preview.innerHTML = renderInspectorMediaPreviewContent(mediaOptions, nextValue);
        };
        update(rawValue);
        return { element: preview, update };
    }

    function setInspectorMediaImagePreview(preview, rawValue) {
        if (!(preview instanceof HTMLImageElement)) {
            return;
        }

        const safeValue = String(rawValue || '').trim();
        const resolvedValue = resolveMediaSrc(safeValue);
        if (resolvedValue !== '') {
            preview.src = resolvedValue;
        } else {
            preview.removeAttribute('src');
        }
        preview.hidden = resolvedValue === '';
    }

    function applyWidgetDefaults(type, settings) {
        const def = getWidgetDef(type);
        const safeSettings = settings && typeof settings === 'object' ? settings : {};
        if (!def) return Object.assign({}, safeSettings);
        const merged = Object.assign({}, def.defaults || {}, safeSettings);
        return normalizeWidgetLegacySettings(type, merged);
    }

    function normalizeWidgetLegacySettings(type, settings) {
        const safeType = String(type || '').trim().toLowerCase();
        const nextSettings = settings && typeof settings === 'object'
            ? Object.assign({}, settings)
            : {};

        if (safeType === 'carousel') {
            const parsed = Number.parseInt(String(nextSettings.autoplayDelay ?? ''), 10);
            if (Number.isFinite(parsed) && parsed > 100) {
                nextSettings.autoplayDelay = Math.max(2, Math.min(15, Math.round(parsed / 1000)));
            }
        }

        if (safeType === 'nw_carrousel') {
            const parsed = Number.parseInt(String(nextSettings.autoplayDelay ?? ''), 10);
            if (Number.isFinite(parsed) && parsed > 100) {
                nextSettings.autoplayDelay = Math.max(0, Math.min(10, Math.round(parsed / 1000)));
            }
        }

        return nextSettings;
    }

    function getWidgetDef(type) {
        return widgetDefs.find((item) => item.type === String(type || '')) || null;
    }

    function isWidgetDefLocked(def) {
        return !!(def && typeof def === 'object' && def.locked);
    }

    function notifyProWidgetLocked() {
        const message = label('widgetProLockedNotice', 'Activate a license to use this widget.');
        const modalAlert = window.FlatCMS && window.FlatCMS.modal && window.FlatCMS.modal.alert;
        if (typeof modalAlert === 'function') {
            modalAlert(message);
            return;
        }
        alert(message);
    }

    function normalizeFieldValue(field, value) {
        if (field.type === 'checkbox') {
            return normalizeToggleSettingValue(value, 'off');
        }
        if (field.type === 'number' || field.type === 'range') {
            const parsed = Number(value);
            if (Number.isNaN(parsed)) {
                return field.min !== undefined ? Number(field.min) : 0;
            }
            let next = parsed;
            if (field.min !== undefined) next = Math.max(Number(field.min), next);
            if (field.max !== undefined) next = Math.min(Number(field.max), next);
            return next;
        }
        return value;
    }

    function normalizeToggleSettingValue(value, fallback) {
        return normalizeTextStyleToggle(value, normalizeTextStyleToggle(fallback, false)) ? 'on' : 'off';
    }

    function makeId(prefix) {
        const safePrefix = String(prefix || 'pb').replace(/[^a-z0-9_-]/gi, '') || 'pb';
        return safePrefix + '_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
    }

    function label(key, fallback) {
        const labels = config.labels || {};
        const value = labels[key];
        if (value) {
            return String(value);
        }

        const localeCatalog = config.localeCatalog || {};
        const aliasMap = {
            catContent: 'builder_category_content',
            catMedia: 'builder_category_media',
            catNavigation: 'builder_category_navigation',
            catForms: 'builder_category_forms',
            catLayout: 'builder_category_layout',
            catAdvanced: 'builder_category_advanced',
            saveSuccess: 'builder_saved',
            saveError: 'builder_save_error',
            saving: 'builder_saving',
            clearColor: 'builder_clear_color',
            invalidConfig: 'builder_invalid_config',
            mediaModalUnavailable: 'builder_media_modal_unavailable',
            titleRequired: 'title_required',
            sourceEmpty: 'available_items_hint',
            hero_default_title: 'builder_default_hero_title',
            hero_default_subtitle: 'builder_default_hero_subtitle',
            hero_default_primary_label: 'builder_default_hero_primary_label',
            hero_default_secondary_label: 'builder_default_hero_secondary_label',
            hero_empty: 'builder_widget_hero',
        };

        const toSnake = (rawKey) => String(rawKey || '')
            .replace(/([a-z0-9])([A-Z])/g, '$1_$2')
            .replace(/[^a-zA-Z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '')
            .toLowerCase();

        const requested = String(key || '').trim();
        const candidates = [];
        if (requested !== '') {
            candidates.push(requested);
        }
        if (aliasMap[requested]) {
            candidates.push(aliasMap[requested]);
        }
        const snake = toSnake(requested);
        if (snake !== '') {
            candidates.push(snake, `builder_${snake}`);
        }

        for (let index = 0; index < candidates.length; index += 1) {
            const candidate = candidates[index];
            const translated = localeCatalog[candidate];
            if (translated) {
                return String(translated);
            }
        }

        return fallback;
    }

    function shouldUseInlineWysiwyg(block, field) {
        const blockType = String((block && block.type) || '').trim().toLowerCase();
        const fieldType = String((field && field.type) || '').trim().toLowerCase();
        const fieldKey = String((field && field.key) || '').trim().toLowerCase();
        const wantsWysiwyg = !!(field && field.wysiwyg === true);
        if (fieldType !== 'textarea') {
            return false;
        }
        if (wantsWysiwyg) {
            return true;
        }
        return blockType === 'text' && fieldKey === 'text';
    }

    function isLinksItemsField(block, field) {
        const blockType = String((block && block.type) || '').trim().toLowerCase();
        const fieldType = String((field && field.type) || '').trim().toLowerCase();
        const fieldKey = String((field && field.key) || '').trim().toLowerCase();
        return blockType === 'links' && fieldType === 'textarea' && fieldKey === 'items';
    }

    function createLinksQuickAddPanel(textarea, commit) {
        if (!(textarea instanceof HTMLTextAreaElement) || typeof commit !== 'function') {
            return null;
        }

        const libraryItems = getLinkSourceLibraryItems();
        const primitives = window.FlatCMSUIPrimitives || {};
        const quickAdd = typeof primitives.createBuilderLinksQuickAddScaffold === 'function'
            ? primitives.createBuilderLinksQuickAddScaffold({
                classes: {
                    panel: 'pb-links-quickadd',
                    title: 'pb-links-quickadd-title',
                    existingWrap: 'pb-links-existing',
                    existingList: 'pb-links-existing-list',
                    currentTitle: 'pb-links-quickadd-current-title',
                    controls: 'pb-links-quickadd-controls',
                    list: 'pb-links-quickadd-list',
                    actions: 'pb-links-quickadd-actions',
                    externalWrap: 'pb-links-quickadd-external',
                    externalGrid: 'pb-links-quickadd-external-grid',
                },
                labels: {
                    title: label('linkQuickAddTitle', 'Gestion des liens'),
                    currentTitle: label('linkQuickAddCurrentTitle', 'Liens existants'),
                    libraryTitle: label('linkQuickAddLibraryTitle', 'Ajouter depuis les éléments existants'),
                    externalTitle: label('linkQuickAddExternalTitle', 'Ajouter un lien externe'),
                    searchPlaceholder: label('linkQuickAddSearchPlaceholder', 'Rechercher une page ou un article...'),
                    listAria: label('linkQuickAddListAria', 'Éléments disponibles'),
                    externalLabelPlaceholder: label('linkQuickAddExternalLabelPlaceholder', 'Libellé du lien'),
                    externalUrlPlaceholder: label('linkQuickAddExternalUrlPlaceholder', 'https://exemple.com'),
                },
                typeOptions: [
                    { value: 'all', label: label('linkQuickAddTypeAll', 'Tous') },
                    { value: 'pages', label: label('sourcePages', 'Pages') },
                    { value: 'posts', label: label('sourcePosts', 'Articles') },
                    { value: 'categories', label: label('sourceCategories', 'Catégories') },
                    { value: 'cta', label: label('sourceCta', 'CTA') },
                ],
                targetOptions: [
                    { value: '_self', label: label('optionTargetSelf', 'Même onglet') },
                    { value: '_blank', label: label('optionTargetBlank', 'Nouvel onglet') },
                ],
                addButtonHtml: `<i class="fas fa-plus"></i> ${escapeHtml(label('linkQuickAddAdd', 'Ajouter la sélection'))}`,
                externalAddButtonHtml: `<i class="fas fa-link"></i> ${escapeHtml(label('linkQuickAddExternalAdd', 'Ajouter le lien externe'))}`,
            })
            : null;
        if (!quickAdd) {
            return null;
        }
        const panel = quickAdd.panel;
        const existingList = quickAdd.existingList;
        const typeSelect = quickAdd.typeSelect;
        const search = quickAdd.search;
        const list = quickAdd.list;
        const addBtn = quickAdd.addButton;
        const externalLabelInput = quickAdd.externalLabelInput;
        const externalUrlInput = quickAdd.externalUrlInput;
        const externalTargetSelect = quickAdd.externalTargetSelect;
        const externalAddBtn = quickAdd.externalAddButton;

        const writeRaw = (raw) => {
            const nextRaw = String(raw || '');
            textarea.value = nextRaw;
            commit(nextRaw, false);
        };

        const readLinks = () => parseLinks(String(textarea.value || ''));

        const renderExistingLinks = () => {
            const items = readLinks();
            existingList.innerHTML = '';
            const primitives = window.FlatCMSUIPrimitives || {};

            if (!items.length) {
                const empty = typeof primitives.createBuilderLinksQuickAddEmptyState === 'function'
                    ? primitives.createBuilderLinksQuickAddEmptyState({
                        className: 'pb-links-existing-empty',
                        text: label('linkQuickAddCurrentEmpty', 'Aucun lien ajouté'),
                    })
                    : document.createElement('div');
                if (!(typeof primitives.createBuilderLinksQuickAddEmptyState === 'function')) {
                    empty.className = 'pb-links-existing-empty';
                    empty.textContent = label('linkQuickAddCurrentEmpty', 'Aucun lien ajouté');
                }
                existingList.appendChild(empty);
                return;
            }

            items.forEach((entry, index) => {
                const existingItem = typeof primitives.createBuilderLinksQuickAddExistingItem === 'function'
                    ? primitives.createBuilderLinksQuickAddExistingItem({
                        rowClass: 'pb-links-existing-item',
                        textClass: 'pb-links-existing-text',
                        removeButtonClass: 'btn btn-ghost btn-sm pb-links-existing-remove',
                        text: String(entry.label || entry.url || 'Lien'),
                        title: label('linkQuickAddRemoveSelected', 'Supprimer'),
                        html: '<i class="fas fa-times"></i>',
                    })
                    : null;
                const row = existingItem ? existingItem.element : document.createElement('div');
                const removeBtn = existingItem ? existingItem.removeButton : document.createElement('button');
                if (!existingItem) {
                    row.className = 'pb-links-existing-item';
                    const text = document.createElement('span');
                    text.className = 'pb-links-existing-text';
                    text.textContent = String(entry.label || entry.url || 'Lien');
                    row.appendChild(text);
                    removeBtn.type = 'button';
                    removeBtn.className = 'btn btn-ghost btn-sm pb-links-existing-remove';
                    removeBtn.title = label('linkQuickAddRemoveSelected', 'Supprimer');
                    removeBtn.setAttribute('aria-label', label('linkQuickAddRemoveSelected', 'Supprimer'));
                    removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    row.appendChild(removeBtn);
                }
                removeBtn.addEventListener('click', () => {
                    confirmDeleteAction(
                        label('removeBlockConfirm', 'Supprimer ce bloc ?'),
                        () => {
                            const nextItems = readLinks();
                            if (index < 0 || index >= nextItems.length) {
                                return;
                            }
                            nextItems.splice(index, 1);
                            writeRaw(serializeLinks(nextItems));
                            renderExistingLinks();
                        },
                        {
                            confirmText: label('confirmDelete', 'Supprimer'),
                            itemName: String(entry.label || entry.url || ''),
                        }
                    );
                });
                existingList.appendChild(row);
            });
        };

        const renderOptions = () => {
            const selectedType = normalizeLinkSourceType(typeSelect.value);
            const searchText = String(search.value || '').trim();
            const entries = filterLinkSourceLibraryItems(libraryItems, selectedType, searchText).slice(0, 120);
            const primitives = window.FlatCMSUIPrimitives || {};

            if (typeof primitives.renderBuilderLinksQuickAddOptions === 'function') {
                primitives.renderBuilderLinksQuickAddOptions({
                    list,
                    addButton: addBtn,
                    entries,
                    emptyLabel: label('linkQuickAddEmpty', 'Aucun élément disponible'),
                    formatOptionLabel: (entry) => `${entry.label} (${getSourceTypeLabel(entry.type)})`,
                });
                return;
            }
        };

        const appendSelectedOptions = () => {
            const primitives = window.FlatCMSUIPrimitives || {};
            if (typeof primitives.appendBuilderLinksQuickAddSelection === 'function') {
                primitives.appendBuilderLinksQuickAddSelection({
                    addButton: addBtn,
                    list,
                    normalizeType: normalizeLinkSourceType,
                    appendToRaw: (sourceItem) => appendLinkSourceToRaw(String(textarea.value || ''), sourceItem),
                    writeRaw,
                    afterAppend: renderExistingLinks,
                });
                return;
            }
        };

        const appendExternalLink = () => {
            const primitives = window.FlatCMSUIPrimitives || {};
            if (typeof primitives.appendBuilderLinksQuickAddExternal === 'function') {
                primitives.appendBuilderLinksQuickAddExternal({
                    labelInput: externalLabelInput,
                    urlInput: externalUrlInput,
                    targetSelect: externalTargetSelect,
                    appendToRaw: (sourceItem) => appendLinkSourceToRaw(String(textarea.value || ''), sourceItem),
                    writeRaw,
                    afterAppend: renderExistingLinks,
                    defaultTarget: '_self',
                });
                return;
            }
        };

        typeSelect.addEventListener('change', renderOptions);
        search.addEventListener('input', renderOptions);
        addBtn.addEventListener('click', appendSelectedOptions);
        list.addEventListener('dblclick', appendSelectedOptions);
        externalAddBtn.addEventListener('click', appendExternalLink);
        externalUrlInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                appendExternalLink();
            }
        });

        renderOptions();
        renderExistingLinks();
        return panel;
    }

    function getLinkSourceLibraryItems() {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderLinkSourceLibraryItems === 'function') {
            return primitives.createBuilderLinkSourceLibraryItems({
                source: Array.isArray(config.availableItems) ? config.availableItems : [],
                sanitizeUrl,
                normalizeType: normalizeLinkSourceType,
                normalizeSearchText,
                compareText,
            });
        }
        return [];
    }

    function normalizeLinkSourceType(type) {
        const value = String(type || '').trim().toLowerCase();
        if (value === 'post' || value === 'posts' || value === 'article' || value === 'articles' || value === 'blog' || value === 'blogs') return 'posts';
        if (value === 'category' || value === 'categories' || value === 'taxonomy' || value === 'taxonomies') return 'categories';
        if (value === 'cta' || value === 'calltoaction' || value === 'call-to-action') return 'cta';
        if (value === 'page' || value === 'pages') return 'pages';
        if (value === 'all') {
            return 'all';
        }
        return 'pages';
    }

    function filterLinkSourceLibraryItems(items, type, searchText) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.filterBuilderLinkSourceLibraryItems === 'function') {
            return primitives.filterBuilderLinkSourceLibraryItems({
                items,
                type,
                searchText,
                normalizeType: normalizeLinkSourceType,
                tokenizeSearchText,
            });
        }
        return [];
    }

    function appendLinkSourceToRaw(raw, sourceItem) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.appendBuilderLinkSourceToRaw === 'function') {
            return primitives.appendBuilderLinkSourceToRaw({
                raw,
                sourceItem,
                sanitizeUrl,
                normalizeTarget: normalizeLinkTarget,
                normalizeSearchText,
                parseLinks,
            });
        }
        return { value: String(raw || ''), added: false };
    }

    function serializeLinks(links) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.serializeBuilderLinks === 'function') {
            return primitives.serializeBuilderLinks({
                links,
                sanitizeUrl,
                normalizeTarget: normalizeLinkTarget,
            });
        }
        return '';
    }

    function resolveActiveWysiwygProvider() {
        const root = document.body || document.documentElement;
        const raw = String(root && root.getAttribute ? root.getAttribute('data-wysiwyg-provider') : 'suneditor').toLowerCase();
        if (raw === 'tinymce') {
            return raw;
        }
        return 'suneditor';
    }

    function ensureWysiwygFieldId(textarea, prefix) {
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return '';
        }
        if (!textarea.id) {
            textarea.id = `${prefix}${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
        }
        return textarea.id;
    }

    function resolveTinyMceThemeOptions() {
        const root = document.body || document.documentElement;
        const adminTheme = String(root && root.getAttribute ? root.getAttribute('data-theme') : '').toLowerCase();
        const isModernPro = adminTheme === 'modern-pro';
        const isLightMode = !!(
            (root && root.classList && root.classList.contains('light-mode'))
            || document.documentElement.classList.contains('theme-light-init')
        );
        if (isModernPro && !isLightMode) {
            return { skin: 'oxide-dark', contentCss: 'dark' };
        }
        return { skin: 'oxide', contentCss: 'default' };
    }

    function getSidebarTinyPlugins() {
        return 'autolink autoresize code link lists table';
    }

    function getSidebarTinyToolbar() {
        return 'undo redo | bold italic underline | link unlink | flatcmsimage | bullist numlist | alignleft aligncenter alignright alignjustify | blocks table code';
    }

    function getInlineSunEditorButtonList() {
        return [
            ['font', 'fontSize', 'formatBlock', 'link', 'undo', 'redo', 'bold', 'underline', 'italic', 'strike', 'fontColor', 'hiliteColor', 'align', 'list', 'horizontalRule'],
            '/',
            ['image', 'table', 'removeFormat', 'codeView'],
        ];
    }

    function resolveInlineWysiwygOptions(field) {
        const height = Number(field && field.wysiwygHeight);
        const minHeight = Number(field && field.wysiwygMinHeight);
        const options = {};
        if (Number.isFinite(height) && height > 0) options.height = height;
        if (Number.isFinite(minHeight) && minHeight > 0) options.minHeight = minHeight;
        return options;
    }

    function showBuilderToast(message, type) {
        const text = String(message || '').trim();
        if (text === '') {
            return;
        }
        if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
            window.FlatCMS.toast.show(text, type || 'warning');
            return;
        }
        window.alert(text);
    }

    function insertInlineEditorHtml(editor, textarea, html) {
        const markup = String(html || '');
        if (markup === '') {
            return;
        }

        const readEditorHtml = () => {
            try {
                if (editor && typeof editor.getContent === 'function') {
                    return String(editor.getContent({ format: 'html' }) || '');
                }
                if (editor && typeof editor.getContents === 'function') {
                    return String(editor.getContents() || '');
                }
            } catch (error) {
                // fallback below
            }
            if (textarea instanceof HTMLTextAreaElement) {
                return String(textarea.value || '');
            }
            return '';
        };

        const beforeHtml = readEditorHtml();
        let inserted = false;
        const strategies = [
            () => {
                if (editor && typeof editor.focus === 'function') {
                    editor.focus();
                }
                if (editor && typeof editor.insertContent === 'function') {
                    editor.insertContent(markup);
                    return true;
                }
                return false;
            },
            () => {
                if (editor && typeof editor.execCommand === 'function') {
                    editor.execCommand('mceInsertContent', false, markup);
                    return true;
                }
                return false;
            },
            () => {
                if (editor && typeof editor.insertHTML === 'function') {
                    editor.insertHTML(markup, true, true, true);
                    return true;
                }
                return false;
            },
            () => {
                if (editor && typeof editor.appendContents === 'function') {
                    editor.appendContents(markup);
                    return true;
                }
                return false;
            },
        ];

        strategies.some((strategy) => {
            try {
                const attempted = strategy();
                if (!attempted) {
                    return false;
                }
                inserted = readEditorHtml() !== beforeHtml;
                return inserted;
            } catch (error) {
                inserted = false;
                return false;
            }
        });

        if (!inserted && textarea instanceof HTMLTextAreaElement) {
            textarea.value = beforeHtml + markup;
            try {
                if (editor && typeof editor.setContent === 'function') {
                    editor.setContent(textarea.value);
                    inserted = true;
                } else if (editor && typeof editor.setContents === 'function') {
                    editor.setContents(textarea.value);
                    inserted = true;
                }
            } catch (error) {
                inserted = false;
            }
        }

        if (!(textarea instanceof HTMLTextAreaElement)) {
            return;
        }

        textarea.value = readEditorHtml() || String(textarea.value || '');
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        textarea.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function buildInlineMediaHtml(file, src, kind) {
        const mediaKind = String(kind || '').trim().toLowerCase() === 'video' ? 'video' : 'image';
        if (mediaKind === 'video') {
            const mime = String((file && file.mime) || '').trim();
            const sourceAttrs = mime !== ''
                ? ` src="${escapeAttr(src)}" type="${escapeAttr(mime)}"`
                : ` src="${escapeAttr(src)}"`;
            return `<video controls preload="metadata"><source${sourceAttrs}></video>`;
        }

        const alt = String((file && (file.original_name || file.name || file.filename)) || '').trim();
        return `<img src="${escapeAttr(src)}" alt="${escapeAttr(alt)}">`;
    }

    function openInlineWysiwygMediaModal(editor, textarea, options) {
        const mediaKind = String((options && options.kind) || 'image').trim().toLowerCase() === 'video' ? 'video' : 'image';
        const unavailableLabel = label('mediaModalUnavailable', 'La mediathèque est indisponible.');
        const tinyBookmark = editor && editor.selection && typeof editor.selection.getBookmark === 'function'
            ? editor.selection.getBookmark(2, true)
            : null;
        const sunRange = editor && editor.core ? cloneSunEditorRange(editor.core) : null;
        const modal = document.getElementById('mediaModal');
        const mediaConfig = config.media || {};
        const modalOptions = {
            apiImagesUrl: mediaConfig.apiImagesUrl || '',
            apiFilesUrl: mediaConfig.apiFilesUrl || '',
            uploadUrl: mediaConfig.uploadUrl || '',
            uploadsBase: mediaConfig.uploadsBase || '/uploads',
            csrfToken: mediaConfig.csrfToken || config.csrfToken || '',
            mode: mediaKind === 'video' ? 'files' : 'images',
            folder: mediaKind === 'video' ? 'videos' : 'images',
            accept: mediaKind === 'video' ? 'video/*' : 'image/*',
            openUploadIfEmpty: true,
            initialTab: 'library',
        };

        if (!modal || typeof window.initMediaModal !== 'function') {
            showBuilderToast(unavailableLabel, 'warning');
            return;
        }

        window.initMediaModal(Object.assign({}, modalOptions, {
            onSelect(file) {
                if (!file) {
                    return;
                }
                const rawPath = String((file.path || file.url || '')).trim();
                const src = resolveMediaSrc(rawPath);
                if (src === '') {
                    return;
                }
                if (tinyBookmark && editor && editor.selection && typeof editor.selection.moveToBookmark === 'function') {
                    try {
                        editor.focus();
                        editor.selection.moveToBookmark(tinyBookmark);
                    } catch (error) {
                        // no-op
                    }
                } else if (sunRange && editor && editor.core) {
                    restoreSunEditorRange(editor.core, sunRange);
                }
                insertInlineEditorHtml(editor, textarea, buildInlineMediaHtml(file, src, mediaKind));
                closeMediaModal();
            },
        }));

        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function bindInlineSunEditorMediaButton(editor, textarea, buttonConfig, attempt) {
        if (!editor || !editor.core || !editor.core.context || !editor.core.context.element) {
            return;
        }

        const toolbar = editor.core.context.element.toolbar;
        if (!toolbar) {
            return;
        }

        const command = String((buttonConfig && buttonConfig.command) || '').trim();
        if (command === '') {
            return;
        }

        const selector = String((buttonConfig && buttonConfig.selector) || '').trim();
        let targetButton = null;
        if (selector !== '') {
            targetButton = toolbar.querySelector(selector);
        }
        if (!targetButton) {
            targetButton = toolbar.querySelector(`button[data-command="${command}"]`);
        }

        if (!targetButton) {
            const nextAttempt = Number(attempt || 0) + 1;
            if (nextAttempt <= 8) {
                window.setTimeout(() => {
                    bindInlineSunEditorMediaButton(editor, textarea, buttonConfig, nextAttempt);
                }, 60);
            }
            return;
        }

        const bindFlag = `data-flatcms-inline-media-bound-${command}`;
        if (targetButton.getAttribute(bindFlag) === '1') {
            return;
        }
        targetButton.setAttribute(bindFlag, '1');

        targetButton.addEventListener('mousedown', (event) => {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }, true);

        targetButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
            openInlineWysiwygMediaModal(editor, textarea, buttonConfig);
        }, true);
    }

    function attachInlineSunEditorMediaButtons(textarea, handle) {
        const editor = handle && handle.editor;
        if (!(textarea instanceof HTMLTextAreaElement) || !editor) {
            return;
        }

        bindInlineSunEditorMediaButton(editor, textarea, {
            command: 'image',
            selector: '.se-btn-module-image .se-btn',
            kind: 'image',
        });
    }

    function attachInlineSunEditorLinkTools(textarea, handle) {
        if (!(textarea instanceof HTMLTextAreaElement) || !handle || !handle.editor) {
            return;
        }
    }

    function initMinimalWysiwygField(textarea, onInput, onChange, options) {
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return false;
        }
        const editorOptions = Object.assign({}, options || {});
        const emitInput = (nextHtml) => {
            textarea.value = String(nextHtml || '');
            if (typeof onInput === 'function') {
                onInput(textarea.value);
            }
        };
        const emitChange = (nextHtml) => {
            textarea.value = String(nextHtml || '');
            if (typeof onChange === 'function') {
                onChange(textarea.value);
            }
        };
        const scheduleDeferredInit = () => {
            if (textarea.getAttribute('data-pb-wysiwyg-deferred') === '1') {
                return;
            }
            textarea.setAttribute('data-pb-wysiwyg-deferred', '1');
            let attempts = 0;
            const maxAttempts = 100;
            const intervalId = window.setInterval(() => {
                attempts += 1;

                if (!document.body || !document.body.contains(textarea)) {
                    if (attempts >= maxAttempts) {
                        window.clearInterval(intervalId);
                        textarea.removeAttribute('data-pb-wysiwyg-deferred');
                    }
                    return;
                }

                const active = resolveActiveWysiwygProvider();
                const tinyReady = active === 'tinymce' && window.tinymce && typeof window.tinymce.init === 'function';
                const sunReady = active === 'suneditor'
                    && window.FlatCMSSunEditor
                    && typeof window.FlatCMSSunEditor.create === 'function';

                if (tinyReady || sunReady) {
                    window.clearInterval(intervalId);
                    textarea.removeAttribute('data-pb-wysiwyg-deferred');
                    initMinimalWysiwygField(textarea, onInput, onChange, options);
                    return;
                }

                if (attempts >= maxAttempts) {
                    window.clearInterval(intervalId);
                    textarea.removeAttribute('data-pb-wysiwyg-deferred');
                }
            }, 120);
        };
        if (!document.body || !document.body.contains(textarea)) {
            scheduleDeferredInit();
            return false;
        }
        const initSunEditorInline = () => {
            const sun = window.FlatCMSSunEditor;
            if (!sun || typeof sun.create !== 'function') {
                return false;
            }

            const editorId = ensureWysiwygFieldId(textarea, 'pb-inline-wysiwyg-');
            if (!editorId) {
                return false;
            }

            if (textarea.__pbSunEditorHandle && typeof textarea.__pbSunEditorHandle.destroy === 'function') {
                textarea.__pbSunEditorHandle.destroy();
            }

            const existingTinyEditor = typeof window.tinymce !== 'undefined' && typeof window.tinymce.get === 'function'
                ? window.tinymce.get(editorId)
                : null;
            if (existingTinyEditor && typeof existingTinyEditor.remove === 'function') {
                existingTinyEditor.remove();
            }

            textarea.setAttribute('data-pb-wysiwyg-ready', '0');
            const sunMinHeight = Number.isFinite(Number(editorOptions.minHeight)) && Number(editorOptions.minHeight) > 0
                ? `${Number(editorOptions.minHeight)}px`
                : '180px';
            const sunHeight = Number.isFinite(Number(editorOptions.height)) && Number(editorOptions.height) > 0
                ? Number(editorOptions.height)
                : 220;

            textarea.__pbSunEditorHandle = sun.create(textarea, {
                minHeight: sunMinHeight,
                height: sunHeight,
                resizingBar: false,
                stickyToolbar: -1,
                applyAccordion: false,
                buttonList: getInlineSunEditorButtonList(),
                onReady: (editorInstance) => {
                    if (textarea.__pbSunEditorHandle && textarea.__pbSunEditorHandle.editor !== editorInstance) {
                        textarea.__pbSunEditorHandle.editor = editorInstance;
                    }
                    attachInlineSunEditorMediaButtons(textarea, textarea.__pbSunEditorHandle || { editor: editorInstance });
                    attachInlineSunEditorLinkTools(textarea, textarea.__pbSunEditorHandle || { editor: editorInstance });
                },
                onInput: (nextHtml) => {
                    emitInput(nextHtml);
                },
                onChange: (nextHtml) => {
                    emitChange(nextHtml);
                },
            });

            if (!textarea.__pbSunEditorHandle) {
                return false;
            }

            textarea.setAttribute('data-pb-wysiwyg-provider', 'suneditor');
            textarea.setAttribute('data-pb-wysiwyg-ready', '1');
            return true;
        };
        let provider = resolveActiveWysiwygProvider();
        const canUseSunEditor = () => !!(
            window.FlatCMSSunEditor
            && typeof window.FlatCMSSunEditor.create === 'function'
        );
        try {
            if (provider === 'tinymce' && (!window.tinymce || typeof window.tinymce.init !== 'function')) {
                if (canUseSunEditor()) {
                    provider = 'suneditor';
                } else {
                    scheduleDeferredInit();
                    return false;
                }
            }

            if (provider === 'tinymce' && window.tinymce && typeof window.tinymce.init === 'function') {
                const editorId = ensureWysiwygFieldId(textarea, 'pb-inline-wysiwyg-');
                if (!editorId) {
                    return false;
                }

                const existingTinyEditor = typeof window.tinymce.get === 'function' ? window.tinymce.get(editorId) : null;
                if (existingTinyEditor && typeof existingTinyEditor.remove === 'function') {
                    existingTinyEditor.remove();
                }

                const tinyTheme = resolveTinyMceThemeOptions();
                textarea.setAttribute('data-pb-wysiwyg-provider', 'tinymce');
                textarea.setAttribute('data-pb-wysiwyg-ready', '0');

                window.tinymce.init({
                    target: textarea,
                    menubar: false,
                    branding: false,
                    promotion: false,
                    statusbar: false,
                    toolbar_mode: 'sliding',
                    height: Number.isFinite(Number(editorOptions.height)) && Number(editorOptions.height) > 0 ? Number(editorOptions.height) : 220,
                    skin: tinyTheme.skin,
                    content_css: tinyTheme.contentCss,
                    convert_urls: false,
                    relative_urls: false,
                    plugins: getSidebarTinyPlugins(),
                    toolbar: getSidebarTinyToolbar(),
                    setup(editor) {
                        const readHtml = () => String(editor.getContent({ format: 'html' }) || '');
                        editor.ui.registry.addButton('flatcmsimage', {
                            icon: 'image',
                            tooltip: label('chooseImage', 'Choisir une image'),
                            onAction: () => {
                                openInlineWysiwygMediaModal(editor, textarea, { kind: 'image' });
                            },
                        });
                        editor.on('init', () => {
                            textarea.setAttribute('data-pb-wysiwyg-ready', '1');
                            textarea.value = readHtml();
                        });
                        editor.on('input keyup SetContent Undo Redo', () => emitInput(readHtml()));
                        editor.on('change blur', () => emitChange(readHtml()));
                    },
                });

                return true;
            }

            if (provider === 'suneditor') {
                if (canUseSunEditor()) {
                    return initSunEditorInline();
                }
                scheduleDeferredInit();
                return false;
            }

            scheduleDeferredInit();
            return false;
        } catch (error) {
            console.warn('FlatCMS: failed to initialize PagesBuilder WYSIWYG.', error);
            return false;
        }
    }

    function getSelectOptionLabel(field, optionValue) {
        const value = String(optionValue || '');
        const key = String((field && field.key) || '');
        const optionLabels = field && field.optionLabels && typeof field.optionLabels === 'object'
            ? field.optionLabels
            : null;

        if (optionLabels && Object.prototype.hasOwnProperty.call(optionLabels, value)) {
            return String(optionLabels[value] || value);
        }

        if (key === 'align' || key === 'buttonAlign' || key === 'iconAlign') {
            if (value === 'left') return label('optionAlignLeft', 'Gauche');
            if (value === 'center') return label('optionAlignCenter', 'Centre');
            if (value === 'right') return label('optionAlignRight', 'Droite');
        }

        if (key === 'contentAlign') {
            if (value === 'left') return label('optionAlignLeft', 'Gauche');
            if (value === 'center') return label('optionAlignCenter', 'Centre');
            if (value === 'right') return label('optionAlignRight', 'Droite');
        }

        if (key === 'target' || key === 'primaryTarget' || key === 'secondaryTarget') {
            if (value === '_self') return label('optionTargetSelf', 'Même onglet');
            if (value === '_blank') return label('optionTargetBlank', 'Nouvel onglet');
        }

        if (key === 'indicatorStyle') {
            if (value === 'dots') return label('optionCarouselIndicatorStyleDots', '');
            if (value === 'bars') return label('optionCarouselIndicatorStyleBars', '');
            if (value === 'numbers') return label('optionCarouselIndicatorStyleNumbers', '');
        }

        if (key === 'arrowStyle') {
            if (value === 'filled') return label('optionCarouselArrowStyleFilled', '');
            if (value === 'outline') return label('optionCarouselArrowStyleOutline', '');
            if (value === 'minimal') return label('optionCarouselArrowStyleMinimal', '');
        }

        if (key === 'variant' || key === 'buttonVariant') {
            if (value === 'primary') return label('optionVariantPrimary', 'Primaire');
            if (value === 'secondary') return label('optionVariantSecondary', 'Secondaire');
            if (value === 'ghost') return label('optionVariantGhost', 'Ghost');
            if (value === 'subtle') return label('optionFeatureGridBorderSubtle', 'Fin');
            if (value === 'strong') return label('optionFeatureGridBorderStrong', 'Accentué');
            if (value === 'dashed') return label('optionFeatureGridBorderDashed', 'Tirets');
            if (value === 'default') return label('optionHeroVariantDefault', label('optionCardsVariantDefault', 'Default'));
            if (value === 'outline') return label('optionFeatureVariantOutline', label('optionCardsVariantOutline', 'Outline'));
            if (value === 'soft') return label('optionHeroVariantSoft', label('optionCardsVariantSoft', 'Soft'));
            if (value === 'dark') return label('optionHeroVariantDark', 'Dark');
        }

        if (key === 'iconPosition') {
            if (value === 'left') return label('optionIconPositionLeft', 'Icône à gauche');
            if (value === 'right') return label('optionIconPositionRight', 'Icône à droite');
        }

        if (key === 'linkStyle') {
            if (value === 'none') return label('optionLinkStyleNone', 'Sans soulignement');
            if (value === 'underline') return label('optionLinkStyleUnderline', 'Toujours souligné');
            if (value === 'hover') return label('optionLinkStyleHover', 'Souligné au survol');
        }

        if (key === 'method') {
            if (value === 'post') return 'POST';
            if (value === 'get') return 'GET';
        }

        if (key === 'openFirst') {
            if (value === 'on') return label('optionFaqOpenFirstOn', label('optionOn', 'On'));
            if (value === 'off') return label('optionFaqOpenFirstOff', label('optionOff', 'Off'));
        }

        if (key === 'ambientMode' || key === 'autoplay' || key === 'showArrows' || key === 'showDots' || key === 'showLabels') {
            if (value === 'on') return label('optionOn', 'On');
            if (value === 'off') return label('optionOff', 'Off');
        }

        if (key === 'mode') {
            if (value === 'fixed') return label('optionCountdownModeFixed', 'Fixed date');
            if (value === 'evergreen') return label('optionCountdownModeEvergreen', 'Evergreen');
        }

        return value;
    }

    function isAlignSelectField(field) {
        if (!field || String(field.type || '').trim().toLowerCase() !== 'select') {
            return false;
        }

        const key = String(field.key || '').trim().toLowerCase();
        if (!/align/.test(key)) {
            return false;
        }

        const options = Array.isArray(field.options)
            ? field.options.map((option) => String(option || '').trim().toLowerCase()).filter((option) => option !== '')
            : [];
        if (!options.length) {
            return false;
        }

        const allowed = ['left', 'center', 'right'];
        const hasAll = allowed.every((option) => options.includes(option));
        const onlyAllowed = options.every((option) => allowed.includes(option));
        return hasAll && onlyAllowed;
    }

    function isLayoutSelectField(field) {
        if (!field || String(field.type || '').trim().toLowerCase() !== 'select') {
            return false;
        }
        const options = Array.isArray(field.options)
            ? field.options.map((option) => String(option || '').trim()).filter((option) => option !== '')
            : [];
        return options.length >= 2 && options.length <= 6;
    }

    function isChoiceSelectField(field) {
        if (!field || String(field.type || '').trim().toLowerCase() !== 'select') {
            return false;
        }

        const explicit = String((field && field.control) || '').trim().toLowerCase();
        if (explicit === 'choice') {
            return true;
        }
        if (explicit && explicit !== 'align') {
            return false;
        }
        if (isAlignSelectField(field)) {
            return false;
        }

        const key = String((field && field.key) || '').trim().toLowerCase();
        const group = String((field && field.group) || '').trim().toLowerCase();
        if (['target', 'primarytarget', 'secondarytarget', 'buttontarget', 'ctatarget'].includes(key)) {
            return true;
        }
        if (group === 'design' && isLayoutSelectField(field)) {
            return true;
        }

        const options = Array.isArray(field.options)
            ? field.options.map((option) => String(option || '').trim())
            : [];
        const normalized = options.filter((option) => option !== '');
        return normalized.length === 2 && normalized.includes('_self') && normalized.includes('_blank');
    }

    function hasFiniteNumberRange(field) {
        if (!field) {
            return false;
        }
        const min = Number(field.min);
        const max = Number(field.max);
        return Number.isFinite(min) && Number.isFinite(max) && max > min;
    }

    function createLayoutChoiceControl(field, currentValue, onChange) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderChoiceControl === 'function') {
            return primitives.createBuilderChoiceControl({
                field,
                options: Array.isArray(field && field.options) ? field.options : [],
                currentValue,
                onChange,
                labelResolver: getSelectOptionLabel,
            });
        }
        const options = Array.isArray(field && field.options)
            ? field.options.map((option) => String(option || '').trim()).filter((option) => option !== '')
            : [];
        const safeCurrent = String(currentValue || '').trim();
        let activeValue = options.includes(safeCurrent) ? safeCurrent : (options[0] || '');

        const group = document.createElement('div');
        group.className = 'pb-layout-choice';
        group.setAttribute('role', 'group');
        group.setAttribute('aria-label', String((field && field.label) || ''));

        const buttons = [];
        options.forEach((optionValue) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pb-layout-choice-btn';
            button.dataset.value = optionValue;
            const optionLabel = getSelectOptionLabel(field, optionValue);
            button.title = optionLabel;
            button.setAttribute('aria-label', optionLabel);
            const optionContent = typeof field.renderOption === 'function'
                ? field.renderOption(optionValue, optionLabel)
                : null;
            if (optionContent instanceof Node) {
                button.appendChild(optionContent);
                button.classList.add('has-custom-content');
            } else if (typeof optionContent === 'string' && optionContent.trim() !== '') {
                button.innerHTML = optionContent;
                button.classList.add('has-custom-content');
            } else {
                button.textContent = optionLabel;
            }
            button.addEventListener('click', () => {
                if (activeValue === optionValue) {
                    return;
                }
                activeValue = optionValue;
                updateButtons();
                if (typeof onChange === 'function') {
                    onChange(optionValue, true);
                }
            });
            buttons.push(button);
            group.appendChild(button);
        });

        const updateButtons = () => {
            buttons.forEach((button) => {
                const isActive = String(button.dataset.value || '') === activeValue;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        updateButtons();
        return group;
    }

    function createTargetChoiceControl(currentValue, onChange, extraClasses) {
        const primitives = window.FlatCMSUIPrimitives || {};
        const mergedClasses = ['fc-builder-navigation-target'];
        if (Array.isArray(extraClasses)) {
            extraClasses.forEach((className) => {
                const safeClass = String(className || '').trim();
                if (safeClass !== '') {
                    mergedClasses.push(safeClass);
                }
            });
        }
        let control = null;
        if (typeof primitives.createBuilderTargetChoiceControl === 'function') {
            const selfLabel = getSelectOptionLabel({
                key: 'target',
                label: label('fieldTarget', 'Ouverture'),
                type: 'select',
                options: ['_self', '_blank'],
            }, '_self');
            const blankLabel = getSelectOptionLabel({
                key: 'target',
                label: label('fieldTarget', 'Ouverture'),
                type: 'select',
                options: ['_self', '_blank'],
            }, '_blank');
            control = primitives.createBuilderTargetChoiceControl({
                label: label('fieldTarget', 'Ouverture'),
                currentValue,
                onChange,
                extraClasses: mergedClasses,
                selfLabel,
                blankLabel,
            });
        } else {
            control = createLayoutChoiceControl({
                key: 'target',
                label: label('fieldTarget', 'Ouverture'),
                type: 'select',
                control: 'choice',
                options: ['_self', '_blank'],
            }, currentValue, onChange);

            mergedClasses.forEach((className) => {
                const safeClass = String(className || '').trim();
                if (safeClass !== '') {
                    control.classList.add(safeClass);
                }
            });
        }

        return control;
    }

    function createNavigationEditorScaffold(headers, options) {
        const primitives = window.FlatCMSUIPrimitives || {};
        const safeHeaders = Array.isArray(headers) ? headers : [];
        const opts = options && typeof options === 'object' ? options : {};
        if (typeof primitives.createBuilderNavigationEditorScaffold === 'function') {
            return primitives.createBuilderNavigationEditorScaffold({
                headers: safeHeaders,
                bodyClass: opts.bodyClass,
                listClass: opts.listClass,
                headRowClass: opts.headRowClass,
                headCellClass: opts.headCellClass,
            });
        }

        const body = document.createElement('div');
        body.className = String(opts.bodyClass || '').trim();

        const list = document.createElement('div');
        list.className = String(opts.listClass || '').trim();
        body.appendChild(list);

        const headerRow = document.createElement('div');
        headerRow.className = String(opts.headRowClass || '').trim();
        safeHeaders.forEach((header, headerIndex) => {
            const meta = header && typeof header === 'object'
                ? header
                : { text: String(header || '') };
            const headerCell = document.createElement('div');
            headerCell.className = String(opts.headCellClass || '').trim();
            if (meta.blank === true || (!meta.className && headerIndex === 0 && String(meta.text || '') === '')) {
                headerCell.classList.add('is-blank');
            }
            if (String(meta.className || '').trim() !== '') {
                headerCell.classList.add(...String(meta.className || '').trim().split(/\s+/));
            }
            headerCell.textContent = String(meta.text || '');
            headerRow.appendChild(headerCell);
        });
        list.appendChild(headerRow);

        return {
            body,
            list,
            headerRow,
            createRow(className) {
                const row = document.createElement('div');
                row.className = String(className || '').trim();
                return row;
            },
            appendItem(row, itemClass) {
                const item = document.createElement('div');
                item.className = String(itemClass || '').trim();
                item.appendChild(row);
                list.appendChild(item);
                return item;
            }
        };
    }

    function createNavigationEditorRow(className) {
        const row = document.createElement('div');
        row.className = `fc-builder-navigation-row ${String(className || '').trim()}`.trim();
        return row;
    }

    function createNavigationInputCell(options) {
        const opts = options && typeof options === 'object' ? options : {};
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderNavigationInputCell === 'function') {
            return primitives.createBuilderNavigationInputCell({
                className: opts.className,
                type: opts.type,
                value: opts.value,
                placeholder: opts.placeholder,
                title: opts.title,
                ariaLabel: opts.ariaLabel,
                readOnly: opts.readOnly,
            });
        }

        if (typeof primitives.createBuilderInputControl === 'function') {
            return primitives.createBuilderInputControl({
                className: opts.className,
                type: opts.type,
                value: opts.value,
                placeholder: opts.placeholder,
                title: opts.title,
                ariaLabel: opts.ariaLabel,
                readOnly: opts.readOnly,
            });
        }

        const input = document.createElement('input');
        input.className = String(opts.className || 'form-input').trim();
        input.type = String(opts.type || 'text').trim() || 'text';
        input.value = String(opts.value || '');
        if (opts.placeholder !== undefined) input.placeholder = String(opts.placeholder || '');
        if (opts.title !== undefined) input.title = String(opts.title || '');
        if (opts.ariaLabel !== undefined) input.setAttribute('aria-label', String(opts.ariaLabel || ''));
        input.readOnly = opts.readOnly === true;
        return input;
    }

    function createNavigationSwitchCell(field, value, options) {
        const opts = options && typeof options === 'object' ? options : {};
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderNavigationSwitchCell === 'function') {
            return primitives.createBuilderNavigationSwitchCell({
                label: String((field && field.label) || ''),
                checked: normalizeTextStyleToggle(value, false),
                cellClass: opts.cellClass,
                title: opts.title,
                ariaLabel: opts.ariaLabel,
                hideText: opts.hideText,
                wrapperClass: 'pb-switch-control',
                textClass: 'pb-switch-text',
                hitboxClass: 'pb-switch-hitbox',
                inputClass: 'pb-switch-input',
                uiClass: 'pb-switch-ui',
            });
        }

        const control = createToggleSwitchControl(field, value);
        String(opts.cellClass || '').trim().split(/\s+/).filter(Boolean).forEach((className) => {
            control.element.classList.add(className);
        });
        if (opts.hideText !== false) {
            const textNode = control.element.querySelector('.pb-switch-text');
            if (textNode) {
                textNode.remove();
            }
        }
        if (opts.title !== undefined) {
            control.element.title = String(opts.title || '');
        }
        if (opts.ariaLabel !== undefined) {
            control.input.setAttribute('aria-label', String(opts.ariaLabel || ''));
        }
        return control;
    }

    function createRepeaterCardScaffold(options) {
        const opts = options && typeof options === 'object' ? options : {};
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderRepeaterCardScaffold === 'function') {
            return primitives.createBuilderRepeaterCardScaffold({
                bodyClass: opts.bodyClass,
                listClass: opts.listClass,
            });
        }

        const body = document.createElement('div');
        body.className = String(opts.bodyClass || '').trim();
        const list = document.createElement('div');
        list.className = String(opts.listClass || '').trim();
        body.appendChild(list);
        return { body, list };
    }

    function createRepeaterCard(options) {
        const opts = options && typeof options === 'object' ? options : {};
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderRepeaterCard === 'function') {
            return primitives.createBuilderRepeaterCard({
                cardClass: opts.cardClass,
                headClass: opts.headClass,
                titleClass: opts.titleClass,
                gridClass: opts.gridClass,
                title: opts.title,
                removeButtonClass: opts.removeButtonClass,
                attachHead: opts.attachHead,
            });
        }

        const card = document.createElement('div');
        card.className = String(opts.cardClass || '').trim();
        const head = document.createElement('div');
        head.className = String(opts.headClass || '').trim();
        if (opts.attachHead !== false) {
            card.appendChild(head);
        }
        const title = document.createElement('span');
        title.className = String(opts.titleClass || '').trim();
        title.textContent = String(opts.title || '');
        head.appendChild(title);
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = ['btn', 'btn-ghost', String(opts.removeButtonClass || '').trim()].filter(Boolean).join(' ');
        removeButton.innerHTML = '<i class="fas fa-trash" aria-hidden="true"></i>';
        head.appendChild(removeButton);
        const grid = document.createElement('div');
        grid.className = String(opts.gridClass || '').trim();
        card.appendChild(grid);
        return { card, head, title, removeButton, grid };
    }

    function createRepeaterAddButton(buttonHtml) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderRepeaterAddButton === 'function') {
            return primitives.createBuilderRepeaterAddButton({
                html: buttonHtml,
            });
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-ghost btn-sm pb-feature-grid-content-add';
        button.innerHTML = String(buttonHtml || '');
        return button;
    }

    function createRepeaterCardActionsRow(options) {
        const opts = options && typeof options === 'object' ? options : {};
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderCardActionsRow === 'function') {
            const parts = primitives.createBuilderCardActionsRow({
                rowClass: opts.rowClass,
                controls: Array.isArray(opts.controls) ? opts.controls : [],
            });
            return parts.element;
        }

        const row = document.createElement('div');
        row.className = String(opts.rowClass || '').trim();
        (Array.isArray(opts.controls) ? opts.controls : []).forEach((control) => {
            if (control instanceof Node) {
                row.appendChild(control);
            }
        });
        return row;
    }

    function createAdvancedTextStylePanel(panelLabelText, control, options) {
        const opts = options && typeof options === 'object' ? options : {};
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderAdvancedPanel === 'function') {
            const parts = primitives.createBuilderAdvancedPanel({
                label: panelLabelText,
                control,
                panelClass: opts.panelClass,
                labelClass: opts.labelClass,
            });
            return parts.panel;
        }

        const panel = document.createElement('div');
        panel.className = String(opts.panelClass || '').trim();
        const panelLabel = document.createElement('span');
        panelLabel.className = String(opts.labelClass || '').trim();
        panelLabel.textContent = String(panelLabelText || '');
        panel.appendChild(panelLabel);
        if (control instanceof Node) {
            panel.appendChild(control);
        }
        return panel;
    }

    function createAdvancedTextStyleCard(options) {
        const opts = options && typeof options === 'object' ? options : {};
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderAdvancedCard === 'function') {
            return primitives.createBuilderAdvancedCard({
                cardClass: opts.cardClass,
                titleClass: opts.titleClass,
                bodyClass: opts.bodyClass,
                title: opts.title,
                fieldKey: opts.fieldKey,
            });
        }

        const card = document.createElement('div');
        card.className = String(opts.cardClass || '').trim();
        if (opts.fieldKey) {
            card.dataset.fieldKey = String(opts.fieldKey);
        }
        const title = document.createElement('label');
        title.className = String(opts.titleClass || '').trim();
        title.textContent = String(opts.title || '');
        card.appendChild(title);
        const body = document.createElement('div');
        body.className = String(opts.bodyClass || '').trim();
        card.appendChild(body);
        return { card, title, body };
    }

    function createCarouselStylePreviewNode(kind, optionValue) {
        const preview = document.createElement('span');
        preview.className = `pb-carousel-style-preview pb-carousel-style-preview-${kind}`;
        preview.setAttribute('aria-hidden', 'true');

        if (kind === 'indicator') {
            const indicators = document.createElement('span');
            indicators.className = `fc-carousel-indicators fc-carousel-indicators-style-${optionValue}`;
            const marker = document.createElement('span');
            marker.className = 'fc-carousel-indicator is-active';
            const markText = document.createElement('span');
            markText.className = 'fc-carousel-indicator-mark';
            markText.textContent = optionValue === 'numbers' ? '1' : '01';
            marker.appendChild(markText);
            indicators.appendChild(marker);
            preview.appendChild(indicators);
            return preview;
        }

        const control = document.createElement('span');
        control.className = `fc-carousel-control fc-carousel-control-style-${optionValue} fc-carousel-control-next`;
        const icon = document.createElement('span');
        icon.className = 'fc-carousel-control-icon';
        icon.textContent = '›';
        control.appendChild(icon);
        preview.appendChild(control);

        return preview;
    }

    function createLayoutRangeControl(field, currentValue, onChange) {
        const min = Number(field.min);
        const max = Number(field.max);
        const step = Number(field.step);
        const stepValue = Number.isFinite(step) && step > 0 ? step : 1;

        const normalize = (raw) => {
            const parsed = Number(raw);
            if (!Number.isFinite(parsed)) {
                return Number.isFinite(min) ? min : 0;
            }
            let next = parsed;
            if (Number.isFinite(min)) next = Math.max(min, next);
            if (Number.isFinite(max)) next = Math.min(max, next);
            return next;
        };

        let activeValue = normalize(currentValue);

        const wrap = document.createElement('div');
        wrap.className = 'pb-layout-range';

        const rangeInput = document.createElement('input');
        rangeInput.type = 'range';
        rangeInput.className = 'pb-layout-range-track';
        rangeInput.min = String(min);
        rangeInput.max = String(max);
        rangeInput.step = String(stepValue);
        rangeInput.value = String(activeValue);

        const numberInput = document.createElement('input');
        numberInput.type = 'text';
        numberInput.className = 'form-input pb-layout-range-input';
        numberInput.readOnly = true;
        numberInput.setAttribute('aria-readonly', 'true');
        numberInput.value = String(activeValue);

        const sync = (nextValue) => {
            activeValue = normalize(nextValue);
            rangeInput.value = String(activeValue);
            numberInput.value = String(activeValue);
            return activeValue;
        };

        rangeInput.addEventListener('input', () => {
            const next = sync(rangeInput.value);
            if (typeof onChange === 'function') {
                onChange(next, false);
            }
        });
        rangeInput.addEventListener('change', () => {
            const next = sync(rangeInput.value);
            if (typeof onChange === 'function') {
                onChange(next, true);
            }
        });
        wrap.appendChild(rangeInput);
        wrap.appendChild(numberInput);

        return {
            element: wrap,
            input: numberInput,
            setDisabled(disabled) {
                const isDisabled = !!disabled;
                rangeInput.disabled = isDisabled;
                numberInput.disabled = isDisabled;
            },
        };
    }

    function createAlignIconControl(field, currentValue, onChange) {
        const primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderAlignControl === 'function') {
            return primitives.createBuilderAlignControl({
                field,
                options: Array.isArray(field && field.options) ? field.options : ['left', 'center', 'right'],
                currentValue,
                onChange,
                labelResolver: getSelectOptionLabel,
            });
        }
        const options = Array.isArray(field && field.options)
            ? field.options.map((option) => String(option || '').trim().toLowerCase()).filter((option) => option !== '')
            : ['left', 'center', 'right'];
        const iconMap = {
            left: 'fas fa-align-left',
            center: 'fas fa-align-center',
            right: 'fas fa-align-right',
        };
        const safeCurrent = String(currentValue || '').trim().toLowerCase();
        let activeValue = options.includes(safeCurrent) ? safeCurrent : (options[0] || 'left');

        const group = document.createElement('div');
        group.className = 'pb-align-control';
        group.setAttribute('role', 'group');
        group.setAttribute('aria-label', String((field && field.label) || label('fieldAlign', 'Alignement')));

        const buttons = [];
        options.forEach((optionValue) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pb-align-option';
            button.dataset.value = optionValue;
            button.title = getSelectOptionLabel(field, optionValue);
            button.setAttribute('aria-label', getSelectOptionLabel(field, optionValue));
            button.innerHTML = iconMap[optionValue]
                ? `<i class="${escapeAttr(iconMap[optionValue])}" aria-hidden="true"></i>`
                : escapeHtml(optionValue);
            let pointerHandled = false;
            const applyValue = () => {
                if (activeValue === optionValue) {
                    return;
                }
                activeValue = optionValue;
                updateButtons();
                if (typeof onChange === 'function') {
                    onChange(optionValue, true);
                }
            };
            button.addEventListener('mousedown', (event) => {
                if (event.button !== 0) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                pointerHandled = true;
                applyValue();
            });
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                if (pointerHandled) {
                    pointerHandled = false;
                    return;
                }
                applyValue();
            });
            button.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }
                event.preventDefault();
                applyValue();
            });
            buttons.push(button);
            group.appendChild(button);
        });

        const updateButtons = () => {
            buttons.forEach((button) => {
                const isActive = String(button.dataset.value || '') === activeValue;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        updateButtons();
        return group;
    }

    function resolveMediaSrc(raw) {
        const src = String(raw || '').trim();
        if (!src) return '';
        if (/^(https?:)?\/\//i.test(src) || src.startsWith('data:') || src.startsWith('blob:')) {
            return src;
        }

        const uploadsBase = String((config.media && config.media.uploadsBase) || '/uploads').replace(/\/$/, '');
        if (src.startsWith('/public/uploads/')) {
            return uploadsBase + '/' + src.replace(/^\/public\/uploads\/?/, '');
        }
        if (src.startsWith('/uploads/')) {
            return uploadsBase + '/' + src.replace(/^\/uploads\/?/, '');
        }
        if (src.startsWith('/')) {
            return src;
        }
        return uploadsBase + '/' + src.replace(/^\//, '');
    }

    function resolveImageSrc(raw) {
        return resolveMediaSrc(raw);
    }

    function sanitizeRichText(html) {
        const temp = document.createElement('div');
        temp.innerHTML = String(html || '');
        temp.querySelectorAll('script,style,iframe,object,embed').forEach((node) => node.remove());
        return temp.innerHTML;
    }

    function sanitizeUrl(url) {
        const value = String(url || '').trim();
        if (!value) return '';
        if (
            value.startsWith('/')
            || value.startsWith('#')
            || value.startsWith('?')
            || value.startsWith('./')
            || value.startsWith('../')
        ) {
            return value;
        }
        if (/^(https?:\/\/|mailto:|tel:)/i.test(value)) return value;
        if (/^[a-z][a-z0-9+.-]*:/i.test(value)) return '';
        if (/[<>"'\\\s]/.test(value)) return '';
        return value;
    }

    function resolveEmbedUrl(url) {
        const raw = String(url || '').trim();
        if (!raw) {
            return '';
        }

        const iframeSrc = extractIframeSrc(raw);
        const value = sanitizeUrl(iframeSrc || raw);
        if (!value) {
            return '';
        }
        if (/^(mailto:|tel:)/i.test(value)) {
            return '';
        }
        return value;
    }

    function extractIframeSrc(raw) {
        if (!/<iframe\b/i.test(raw)) {
            return '';
        }

        const quoted = raw.match(/\bsrc\s*=\s*(['"])(.*?)\1/i);
        if (quoted && quoted[2]) {
            return decodeHtmlEntities(String(quoted[2]).trim());
        }

        const plain = raw.match(/\bsrc\s*=\s*([^\s"'<>]+)/i);
        if (plain && plain[1]) {
            return decodeHtmlEntities(String(plain[1]).trim());
        }

        return '';
    }

    function decodeHtmlEntities(value) {
        const probe = document.createElement('textarea');
        probe.innerHTML = String(value || '');
        return String(probe.value || '').trim();
    }

    function parseLinks(raw) {
        const lines = String(raw || '').split(/\r?\n/);
        const links = [];
        lines.forEach((line) => {
            const trimmed = String(line || '').trim();
            if (!trimmed) return;

            const parts = trimmed.split('|');
            if (parts.length === 1) {
                links.push({ label: trimmed, url: '#', target: '_self' });
                return;
            }

            const labelValue = String(parts[0] || '').trim();
            const urlValue = String(parts[1] || '#').trim() || '#';
            const targetValue = normalizeLinkTarget(String(parts[2] || ''), urlValue);
            links.push({
                label: labelValue || urlValue || 'Lien',
                url: urlValue,
                target: targetValue,
            });
        });
        return links;
    }

    function normalizeLegalSectionLinkPath(url) {
        const raw = String(url || '').trim().toLowerCase();
        if (!raw) {
            return '';
        }

        const normalized = raw.replace(/^https?:\/\/[^/]+/i, '');
        if (
            normalized.includes('legal-notice')
            || normalized.includes('mentions-legales')
        ) {
            return '/page/legal-notice';
        }
        if (
            normalized.includes('privacy-policy')
            || normalized.includes('politique-confidentialite')
            || normalized.includes('politique-de-confidentialite')
        ) {
            return '/page/privacy-policy';
        }
        return '';
    }

    function getLegalSectionLinkOptions() {
        const fallbackRaw = 'Mentions legales|/page/legal-notice\nPolitique de confidentialite|/page/privacy-policy';
        const defaults = parseLinks(String(label('defaultLegalLinks', fallbackRaw) || fallbackRaw));
        const labelsByPath = new Map();

        defaults.forEach((entry) => {
            const path = normalizeLegalSectionLinkPath(entry && entry.url);
            if (!path || labelsByPath.has(path)) {
                return;
            }
            const optionLabel = String((entry && entry.label) || '').trim();
            if (optionLabel !== '') {
                labelsByPath.set(path, optionLabel);
            }
        });

        const fallbackDefaults = parseLinks(fallbackRaw);
        const fallbackLabelLegal = String((fallbackDefaults[0] && fallbackDefaults[0].label) || 'Mentions legales');
        const fallbackLabelPrivacy = String((fallbackDefaults[1] && fallbackDefaults[1].label) || 'Politique de confidentialite');

        return [
            {
                value: '/page/legal-notice',
                label: labelsByPath.get('/page/legal-notice') || fallbackLabelLegal,
            },
            {
                value: '/page/privacy-policy',
                label: labelsByPath.get('/page/privacy-policy') || fallbackLabelPrivacy,
            },
        ];
    }

    function parseLegalSectionLinkItems(raw, options) {
        const source = Array.isArray(options) && options.length ? options : getLegalSectionLinkOptions();
        const parsed = parseLinks(String(raw || ''));
        const items = [];

        source.forEach((entry, index) => {
            const row = parsed[index] || null;
            const fallbackUrl = normalizeLegalSectionLinkPath(entry && entry.value) || '/page/legal-notice';
            const fallbackLabel = String((entry && entry.label) || '').trim();
            const rowUrl = normalizeLegalSectionLinkPath(row && row.url);
            const rowLabel = String((row && row.label) || '').trim();
            items.push({
                label: rowLabel !== '' ? rowLabel : fallbackLabel,
                url: rowUrl || fallbackUrl,
            });
        });

        return items;
    }

    function parseRepeaterLines(raw) {
        return String(raw || '')
            .split(/\r\n|\r|\n/)
            .map((line) => String(line || '').trim())
            .filter((line) => line !== '');
    }

    function normalizeLinkTarget(target, url) {
        const value = String(target || '').trim().toLowerCase();
        if (value === '_blank' || value === 'blank') return '_blank';
        if (value === '_self' || value === 'self') return '_self';
        const safeUrl = sanitizeUrl(String(url || '').trim());
        if (/^(https?:\/\/|mailto:|tel:)/i.test(safeUrl)) {
            return '_self';
        }
        return '_self';
    }

    function normalizeAlign(align) {
        const value = String(align || '').toLowerCase();
        if (value === 'center' || value === 'right') return value;
        return 'left';
    }

    function normalizeCarouselIndicatorStyle(style) {
        const value = String(style || '').trim().toLowerCase();
        if (value === 'bars' || value === 'numbers') {
            return value;
        }
        return 'dots';
    }

    function normalizeCarouselArrowStyle(style) {
        const value = String(style || '').trim().toLowerCase();
        if (value === 'outline' || value === 'minimal') {
            return value;
        }
        return 'filled';
    }

    function normalizeFeatureGridButtonVariant(variant) {
        const value = String(variant || '').toLowerCase();
        if (value === 'primary' || value === 'secondary') {
            return value;
        }
        return 'ghost';
    }

    function normalizeTestimonialRating(value) {
        const safe = Math.trunc(Number(value || 0)) || 0;
        return Math.max(1, Math.min(5, safe));
    }

    function normalizeFeatureGridButtonEnabled(value, fallback) {
        const normalized = normalizeToggleSettingValue(value, fallback || 'off');
        return normalized === 'on' ? 'on' : 'off';
    }

    function normalizeLinkStyle(style) {
        const value = String(style || '').toLowerCase();
        if (value === 'none' || value === 'underline' || value === 'hover') return value;
        return 'hover';
    }

    function normalizeColor(color) {
        const value = String(color || '').trim();
        if (!value) return '';
        if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value)) return value;
        if (/^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/i.test(value)) return value;
        return '';
    }

    function normalizeHexColor(color) {
        const value = String(color || '').trim();
        const match = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (!match) return '';
        let hex = match[1].toLowerCase();
        if (hex.length === 3) {
            hex = hex.split('').map((char) => char + char).join('');
        }
        return '#' + hex;
    }

    function cloneSunEditorRange(core) {
        if (!core || typeof core.getRange !== 'function') {
            return null;
        }
        try {
            const range = core.getRange();
            if (!range) {
                return null;
            }
            return {
                startContainer: range.startContainer || null,
                startOffset: Number(range.startOffset || 0),
                endContainer: range.endContainer || null,
                endOffset: Number(range.endOffset || 0),
            };
        } catch (error) {
            return null;
        }
    }

    function restoreSunEditorRange(core, savedRange) {
        if (!core) {
            return;
        }
        try {
            if (
                savedRange
                && savedRange.startContainer
                && savedRange.endContainer
                && typeof core.setRange === 'function'
            ) {
                core.setRange(
                    savedRange.startContainer,
                    Number(savedRange.startOffset || 0),
                    savedRange.endContainer,
                    Number(savedRange.endOffset || 0)
                );
                return;
            }
        } catch (error) {
            // fallback below
        }
        try {
            if (typeof core.focus === 'function') {
                core.focus();
            } else if (typeof core.nativeFocus === 'function') {
                core.nativeFocus();
            }
        } catch (error) {
            // no-op
        }
    }

    function clampNumber(value, min, max, fallback) {
        const num = Number(value);
        if (Number.isNaN(num)) return fallback;
        return Math.max(min, Math.min(max, num));
    }

    function compareText(a, b) {
        const left = String(a || '');
        const right = String(b || '');
        return left.localeCompare(right, undefined, { sensitivity: 'base', numeric: true });
    }

    function normalizeSearchText(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function tokenizeSearchText(value) {
        const normalized = normalizeSearchText(value).replace(/[^a-z0-9]+/g, ' ').trim();
        return normalized === '' ? [] : normalized.split(/\s+/).filter(Boolean);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value || '');
        return div.innerHTML;
    }

    function escapeAttr(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escapeCssSelector(value) {
        const raw = String(value || '');
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(raw);
        }
        return raw.replace(/(["\\.#:[\]()>+~*^$|= ])/g, '\\$1');
    }
})();
