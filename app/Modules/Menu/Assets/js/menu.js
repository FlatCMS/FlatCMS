/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Menu/Assets/js/menu.js
 * Version: 2.0.0-dev
 */

(function() {
    'use strict';

    const config = getMenuConfig();
    const activeList = document.getElementById('menuActive');
    const availableList = document.getElementById('menuAvailable');
    const availableSidebar = document.querySelector('.menu-sidebar-column--sticky');
    const availableCard = document.getElementById('menuAvailablePanel');
    const availableCustomCard = availableCard ? availableCard.querySelector('.menu-available-custom') : null;
    const form = document.getElementById('menuForm');
    const menuEditorLayout = availableSidebar ? availableSidebar.closest('.menu-editor') : null;
    const topHeader = document.querySelector('.top-header');
    const dataField = document.getElementById('menuDataField');
    const libraryField = document.getElementById('menuLibraryField');
    const indentGuide = document.getElementById('menuIndentGuide');
    const emptyState = document.getElementById('menuEmptyState');
    const itemTemplate = document.getElementById('menuItemTemplate');

    const iconModal = document.getElementById('menuIconModal');
    const iconGrid = document.getElementById('menuIconGrid');
    const iconSearch = document.getElementById('menuIconSearch');
    const translationModal = document.getElementById('menuTranslationModal');
    const translationSourceLabel = document.getElementById('menuTranslationSourceLabel');
    const translationFields = translationModal
        ? Array.from(translationModal.querySelectorAll('[data-locale]'))
        : [];

    const maxDepth = Number(config.maxDepth || 3);
    const rootItemWarningThreshold = Number(config.rootItemWarningThreshold || config.maxRootItems || 6);
    const indentStep = Number(config.indentStep || 28);
    const iconsEndpoint = String(config.iconsEndpoint || '').trim();
    const iconImagesEndpoint = String(config.iconImagesEndpoint || '').trim();
    const iconUploadEndpoint = String(config.iconUploadEndpoint || '').trim();
    const csrfToken = String(config.csrfToken || '').trim();
    const customIconAccept = String(config.customIconAccept || '.png,.gif,.webp,.avif,image/png,image/gif,image/webp,image/avif');
    const sourceLocale = String(config.sourceLocale || '').trim();
    const translationLocales = Array.isArray(config.translationLocales) ? config.translationLocales : [];
    const translationLocaleCodes = translationLocales
        .map((localeEntry) => String(localeEntry && localeEntry.code ? localeEntry.code : '').trim())
        .filter(Boolean);
    const levelLabels = Array.isArray(config.levelLabels) ? config.levelLabels : [];
    const messages = config.messages && typeof config.messages === 'object' ? config.messages : {};
    const message = (key) => String(messages[key] || '');
    const confirmRemove = message('confirmRemove');
    const confirmDelete = message('confirmDelete');
    const labelRequired = message('labelRequired');
    const maxRootItemsMessage = message('maxRootItemsReached');
    const defaultIcon = (config.defaults && config.defaults.icon) || '';
    const toastDuration = Number(config.toastDuration || 1500);
    const toastItemAdded = message('toastItemAdded');
    const toastItemMoved = message('toastItemMoved');
    const toastItemRemoved = message('toastItemRemoved');
    const toastItemReturned = message('toastItemReturned');
    const toastCustomAdded = message('toastCustomAdded');
    const toastIconUpdated = message('toastIconUpdated');
    const toastIconRemoved = message('toastIconRemoved');
    const toastTranslationSaved = message('toastTranslationSaved');
    const toastCustomIconSelected = message('toastCustomIconSelected');
    const toastCustomIconUploaded = message('toastCustomIconUploaded');
    const customIconInvalidType = message('customIconInvalidType');
    const customIconUploadError = message('customIconUploadError');
    const customIconUploadUnavailable = message('customIconUploadUnavailable');
    const customIconEmpty = message('customIconEmpty');
    const customIconUnavailable = message('customIconUnavailable');
    const mediaModalUnavailable = message('mediaModalUnavailable');

    const FONT_AWESOME_ALIASES = {
        home: ['accueil', 'maison', 'domicile', 'homepage'],
        house: ['accueil', 'maison', 'domicile'],
        user: ['compte', 'profil', 'personne', 'member', 'login'],
        users: ['equipe', 'groupes', 'membres', 'team'],
        envelope: ['mail', 'email', 'courriel', 'message'],
        phone: ['telephone', 'appel', 'contact'],
        image: ['photo', 'picture', 'visuel', 'media'],
        camera: ['photo', 'picture', 'visuel'],
        cart: ['panier', 'boutique', 'shop', 'store', 'commerce'],
        bag: ['panier', 'boutique', 'shop', 'store', 'commerce'],
        shop: ['boutique', 'store', 'commerce'],
        globe: ['langue', 'language', 'monde', 'international'],
        language: ['langue', 'translation', 'translate'],
        blog: ['article', 'post', 'journal', 'actualite'],
        newspaper: ['article', 'post', 'journal', 'actualite'],
        file: ['document', 'piece', 'pj'],
        download: ['telecharger', 'download'],
        upload: ['televerser', 'upload'],
        search: ['recherche', 'chercher', 'loupe'],
        cog: ['reglages', 'settings', 'configuration'],
        gear: ['reglages', 'settings', 'configuration'],
        heart: ['favori', 'love', 'favorite'],
        star: ['favori', 'favorite'],
        location: ['adresse', 'map', 'carte', 'marker'],
        map: ['adresse', 'map', 'carte', 'marker'],
        calendar: ['agenda', 'date', 'planning'],
        clock: ['heure', 'temps', 'time'],
        comment: ['message', 'avis', 'review'],
        play: ['video', 'lecture', 'start'],
    };

    let draggedEl = null;
    let dragPreview = null;
    let startX = 0;
    let startY = 0;
    let startDepth = 0;
    let currentDepth = 0;
    let dragHasMoved = false;
    let dragOriginParent = null;
    let dragOriginNextSibling = null;
    let iconTarget = null;
    let translationTarget = null;
    let idSeed = Date.now();
    let availableFloatingFrame = 0;
    let availableFloatingResizeObserver = null;
    let availableFloatingState = 'normal';
    let availableFloatingMetrics = null;
    let availableAccordionScrollFrame = 0;
    const iconBrowserFactory = window.FlatCMS
        && window.FlatCMS.AdminUI
        && window.FlatCMS.AdminUI.icon
        && window.FlatCMS.AdminUI.icon.createBrowser;
    const iconBrowser = typeof iconBrowserFactory === 'function' && iconModal && iconGrid && iconSearch
        ? iconBrowserFactory({
            overlay: iconModal,
            grid: iconGrid,
            searchInput: iconSearch,
            endpoint: iconsEndpoint,
            aliases: FONT_AWESOME_ALIASES,
            labels: {
                loading: config.messages && config.messages.iconsLoading,
                error: config.messages && config.messages.iconsError,
                empty: config.messages && config.messages.iconsEmpty,
            },
            onSelect: applyIcon,
            closeOnSelect: false,
        })
        : null;
    const translationModalController = translationModal
        && window.FlatCMS
        && window.FlatCMS.AdminUI
        && window.FlatCMS.AdminUI.modal
        ? window.FlatCMS.AdminUI.modal.attach(translationModal, {
            onClose: function() {
                translationTarget = null;
            }
        })
        : null;
    if (form) {
        form.addEventListener('submit', handleSubmit);
    }

    document.addEventListener('click', handleClick);
    document.addEventListener('input', handleInput);
    document.addEventListener('pointerdown', handlePointerDown);
    document.addEventListener('pointermove', handlePointerMove);
    document.addEventListener('pointerup', handlePointerUp);
    document.addEventListener('keydown', handleKeydown);

    const customLabelInput = document.getElementById('menuCustomLabel');
    const customUrlInput = document.getElementById('menuCustomUrl');
    [customLabelInput, customUrlInput].forEach((input) => {
        if (!input) return;
        input.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addCustomItem();
            }
        });
    });

    updateEmptyState();
    initAvailableAccordion();
    syncActiveStates();
    syncReferenceStates();
    removeAvailableDuplicates();
    refreshAvailableGroupCounts();
    syncIcons();
    ensureItemIds();
    initAvailableFloatingPanel();

    function getMenuConfig() {
        const holder = document.getElementById('menuConfig');
        if (holder && holder.dataset.menuConfig) {
            try {
                return JSON.parse(holder.dataset.menuConfig);
            } catch (e) {
                console.warn('Invalid menu config', e);
            }
        }
        return {};
    }

    function showToast(message, type) {
        const text = (message || '').trim();
        if (!text) return;
        const toast = window.FlatCMS && window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.toast;
        if (toast && typeof toast.show === 'function') {
            toast.show(text, typeof type === 'string' && type ? type : 'success', toastDuration);
        }
    }

    function handleClick(event) {
        const availableToggle = event.target.closest('[data-action="menu-available-toggle"]');
        if (availableToggle) {
            toggleAvailableAccordion(availableToggle);
            return;
        }

        const toggleBtn = event.target.closest('[data-action="toggle-config"]');
        if (toggleBtn) {
            toggleConfig(toggleBtn);
            return;
        }

        const removeBtn = event.target.closest('[data-action="remove-item"]');
        if (removeBtn) {
            removeItem(removeBtn);
            return;
        }

        const returnBtn = event.target.closest('[data-action="return-item"]');
        if (returnBtn) {
            returnItem(returnBtn);
            return;
        }

        const iconBtn = event.target.closest('[data-action="icon-picker"]');
        if (iconBtn) {
            openIconPicker(iconBtn);
            return;
        }

        const iconClearBtn = event.target.closest('[data-action="icon-clear"]');
        if (iconClearBtn) {
            clearIcon(iconClearBtn);
            return;
        }

        const translationBtn = event.target.closest('[data-action="open-translation-modal"]');
        if (translationBtn) {
            openTranslationModal(translationBtn);
            return;
        }

        const addCustomBtn = event.target.closest('[data-action="add-custom-item"]');
        if (addCustomBtn) {
            addCustomItem();
            return;
        }

        const translationSaveBtn = event.target.closest('[data-action="translation-modal-save"]');
        if (translationSaveBtn) {
            saveTranslations();
            return;
        }

        const translationClose = event.target.closest('[data-action="translation-modal-close"]');
        if (translationClose) {
            closeTranslationModal();
            return;
        }

        const customUploadTrigger = event.target.closest('[data-action="icon-custom-media-modal"]');
        if (customUploadTrigger) {
            openCustomIconMediaModal();
        }
    }

    function handleInput(event) {
        const input = event.target;

        if (input.matches('[data-field="label"]')) {
            const item = input.closest('.menu-item');
            updateItemDisplay(item);
            syncReferenceState(item);
            input.classList.remove('is-invalid');
            if (translationTarget && item === translationTarget) {
                syncTranslationSourceLabel(item);
            }
        }

        if (input.matches('[data-field="url"]')) {
            const item = input.closest('.menu-item');
            updateItemDisplay(item);
            syncReferenceState(item);
        }

    }

    function handleKeydown(event) {
        if (event.key !== 'Escape') {
            return;
        }

    }

    function handlePointerDown(event) {
        const handle = event.target.closest('[data-action="drag-handle"]');
        if (!handle) return;

        const item = handle.closest('.menu-item');
        if (!item) return;

        draggedEl = item;
        startX = event.clientX;
        startY = event.clientY;
        startDepth = parseInt(draggedEl.dataset.indent || '0', 10);
        currentDepth = startDepth;
        dragHasMoved = false;
        dragOriginParent = draggedEl.parentElement;
        dragOriginNextSibling = draggedEl.nextElementSibling;

        if (draggedEl.setPointerCapture) {
            draggedEl.setPointerCapture(event.pointerId);
        }
    }

    function handlePointerMove(event) {
        if (!draggedEl) return;

        const deltaX = event.clientX - startX;
        const deltaY = event.clientY - startY;
        if (!dragHasMoved) {
            if (Math.abs(deltaX) < 2 && Math.abs(deltaY) < 2) {
                return;
            }
            dragHasMoved = true;

            draggedEl.classList.add('is-dragging');
            createDragPreview(event);
            createPlaceholder();

            const placeholder = getDropPlaceholder();
            if (placeholder) {
                placeholder.style.height = `${draggedEl.offsetHeight}px`;
                placeholder.style.marginLeft = `${currentDepth * indentStep}px`;
                if (dragOriginParent === activeList) {
                    activeList.insertBefore(placeholder, draggedEl);
                } else if (activeList) {
                    activeList.appendChild(placeholder);
                }
            }

            draggedEl.style.display = 'none';
        }

        currentDepth = Math.max(0, Math.min(startDepth + Math.floor(deltaX / indentStep), maxDepth));

        updatePlaceholderPosition(event);
        updateDragPreviewPosition(event);
        updateIndentGuide(event);
    }

    function handlePointerUp() {
        if (!draggedEl) return;

        const placeholder = getDropPlaceholder();
        const wasMoved = dragHasMoved;
        if (dragHasMoved && placeholder && placeholder.parentElement) {
            placeholder.parentElement.insertBefore(draggedEl, placeholder);
        } else if (!dragHasMoved && dragOriginParent && draggedEl.parentElement !== dragOriginParent) {
            if (dragOriginNextSibling && dragOriginNextSibling.parentElement === dragOriginParent) {
                dragOriginParent.insertBefore(draggedEl, dragOriginNextSibling);
            } else {
                dragOriginParent.appendChild(draggedEl);
            }
        }

        if (placeholder) placeholder.remove();
        if (indentGuide) indentGuide.style.display = 'none';

        if (dragPreview) {
            dragPreview.remove();
            dragPreview = null;
        }

        draggedEl.style.display = '';
        draggedEl.style.opacity = '1';
        draggedEl.classList.remove('is-dragging');

        const fromActive = dragOriginParent === activeList;
        const movedItem = draggedEl;
        if (activeList && activeList.contains(movedItem)) {
            const normalizedDepth = normalizeDepth(draggedEl, currentDepth);
            setIndent(draggedEl, normalizedDepth);
            setItemActiveState(draggedEl, true);
        } else {
            setIndent(draggedEl, 0);
            setItemActiveState(draggedEl, false);
        }

        draggedEl = null;
        dragHasMoved = false;
        dragOriginParent = null;
        dragOriginNextSibling = null;
        updateEmptyState();
        refreshAvailableGroupCounts();

        if (!movedItem || !wasMoved) return;
        const nowActive = activeList && activeList.contains(movedItem);
        const warnForDenseRoot = !fromActive
            && nowActive
            && parseInt(movedItem.dataset.indent || '0', 10) === 0
            && exceedsRootItemWarningThreshold();
        if (!fromActive && nowActive) {
            showToast(toastItemAdded, 'success');
            if (warnForDenseRoot) {
                window.setTimeout(() => notifyMaxRootItems(), 180);
            }
            return;
        }
        if (fromActive && nowActive) {
            showToast(toastItemMoved, 'success');
        }
    }

    function countRootItems(container) {
        if (!container) return 0;
        return Array.from(container.querySelectorAll('.menu-item'))
            .filter((item) => parseInt(item.dataset.indent || '0', 10) === 0)
            .length;
    }

    function exceedsRootItemWarningThreshold() {
        if (!activeList) return false;
        if (!Number.isFinite(rootItemWarningThreshold) || rootItemWarningThreshold <= 0) return false;
        return countRootItems(activeList) > rootItemWarningThreshold;
    }

    function notifyMaxRootItems() {
        if (window.FlatCMS && window.FlatCMS.modal && typeof window.FlatCMS.modal.alert === 'function') {
            window.FlatCMS.modal.alert(maxRootItemsMessage);
            return;
        }
        showToast(maxRootItemsMessage, 'warning');
    }

    function restoreDraggedToOrigin() {
        if (!draggedEl || !dragOriginParent) return;
        if (dragOriginNextSibling && dragOriginNextSibling.parentElement === dragOriginParent) {
            dragOriginParent.insertBefore(draggedEl, dragOriginNextSibling);
            return;
        }
        dragOriginParent.appendChild(draggedEl);
    }

    function initAvailableAccordion() {
        if (!availableList) return;
        const groups = getAvailableAccordionGroups();
        if (!groups.length) return;

        groups.forEach(group => {
            closeAvailableAccordionGroup(group, { immediate: true });
        });
    }

    function getAvailableAccordionGroups() {
        if (!availableList) return [];
        return Array.from(availableList.querySelectorAll('.menu-accordion-group'));
    }

    function getAvailableAccordionPanel(group) {
        if (!group) return null;
        return group.querySelector('.menu-accordion-panel');
    }

    function openAvailableAccordionGroup(group, options = {}) {
        if (!group) return;
        const panel = getAvailableAccordionPanel(group);
        const toggle = group.querySelector('[data-action="menu-available-toggle"]');
        const immediate = !!options.immediate;

        group.classList.add('is-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
        if (!panel) return;

        if (immediate) {
            panel.style.maxHeight = 'none';
            scheduleAvailableAccordionScrollLayout();
            return;
        }

        if (panel.style.maxHeight === 'none') {
            return;
        }

        panel.style.maxHeight = '0px';
        panel.offsetHeight;
        panel.style.maxHeight = panel.scrollHeight + 'px';

        const onEnd = (event) => {
            if (event.propertyName !== 'max-height') return;
            panel.removeEventListener('transitionend', onEnd);
            if (group.classList.contains('is-open')) {
                panel.style.maxHeight = 'none';
                scheduleAvailableAccordionScrollLayout();
            }
        };
        panel.addEventListener('transitionend', onEnd);
    }

    function closeAvailableAccordionGroup(group, options = {}) {
        if (!group) return;
        const panel = getAvailableAccordionPanel(group);
        const toggle = group.querySelector('[data-action="menu-available-toggle"]');
        const immediate = !!options.immediate;

        group.classList.remove('is-open');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
        if (!panel) return;

        if (immediate) {
            panel.style.maxHeight = '0px';
            scheduleAvailableAccordionScrollLayout();
            return;
        }

        if (panel.style.maxHeight === 'none' || panel.style.maxHeight === '') {
            panel.style.maxHeight = panel.scrollHeight + 'px';
        }
        panel.offsetHeight;
        panel.style.maxHeight = '0px';
        scheduleAvailableAccordionScrollLayout();
    }

    function toggleAvailableAccordion(button) {
        const group = button.closest('.menu-accordion-group');
        if (!group) return;

        const willOpen = !group.classList.contains('is-open');
        if (willOpen) {
            closeAvailableGroupsExcept(group);
            openAvailableAccordionGroup(group);
        } else {
            closeAvailableAccordionGroup(group);
        }
        scheduleAvailableFloatingUpdate();
    }

    function openAvailableGroup(groupName) {
        const groups = getAvailableAccordionGroups();
        const target = groups.find(group => (group.dataset.group || '') === groupName);
        if (!target) return;

        groups.forEach(group => {
            if (group === target) {
                openAvailableAccordionGroup(group);
            } else {
                closeAvailableAccordionGroup(group);
            }
        });
        scheduleAvailableAccordionScrollLayout();
    }

    function closeAvailableGroupsExcept(exceptGroup) {
        const groups = getAvailableAccordionGroups();
        groups.forEach(group => {
            if (group === exceptGroup) return;
            closeAvailableAccordionGroup(group);
        });
    }

    function createDragPreview(event) {
        if (!draggedEl) return;
        dragPreview = draggedEl.cloneNode(true);
        dragPreview.classList.add('menu-drag-preview');
        const configPanel = dragPreview.querySelector('.menu-item-config');
        if (configPanel) {
            configPanel.classList.remove('is-open');
            configPanel.style.display = 'none';
        }
        dragPreview.style.width = `${draggedEl.offsetWidth}px`;
        document.body.appendChild(dragPreview);
        updateDragPreviewPosition(event);
    }

    function updateDragPreviewPosition(event) {
        if (!dragPreview) return;
        const placeholder = getDropPlaceholder();
        if (placeholder && placeholder.parentElement) {
            const box = placeholder.getBoundingClientRect();
            dragPreview.style.left = `${box.left}px`;
        } else {
            const rail = activeList ? activeList.getBoundingClientRect() : null;
            const railLeft = rail ? rail.left + (currentDepth * indentStep) : (event.clientX - 40);
            dragPreview.style.left = `${railLeft}px`;
        }
        dragPreview.style.top = `${event.clientY - 30}px`;
    }

    function createPlaceholder() {
        getDropPlaceholder();
    }

    function getDropPlaceholder() {
        if (!activeList) return null;
        let placeholder = document.getElementById('menuDropPlaceholder');
        if (!placeholder) {
            placeholder = document.createElement('div');
            placeholder.id = 'menuDropPlaceholder';
            placeholder.className = 'menu-drop-placeholder';
        }
        return placeholder;
    }

    function updatePlaceholderPosition(event) {
        if (!activeList || !draggedEl) return;
        const placeholder = getDropPlaceholder();
        if (!placeholder) return;
        let after = getAfter(activeList, event.clientY);

        if (!after) {
            activeList.appendChild(placeholder);
        } else {
            activeList.insertBefore(placeholder, after);
        }

        placeholder.style.marginLeft = `${currentDepth * indentStep}px`;
    }

    function updateIndentGuide(event) {
        if (!indentGuide || !activeList) return;
        const rect = activeList.getBoundingClientRect();
        indentGuide.style.display = 'block';
        indentGuide.style.top = `${event.clientY - 28}px`;
        indentGuide.style.left = `${rect.left + currentDepth * indentStep}px`;
        indentGuide.style.width = `${Math.max(120, rect.width - currentDepth * indentStep)}px`;
    }

    function getAfter(container, y) {
        const items = [...container.querySelectorAll('.menu-item:not(.is-dragging)')];
        return items.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset, element: child };
            }
            return closest;
        }, { offset: -Infinity }).element;
    }

    function normalizeDepth(item, targetDepth) {
        if (!item) return 0;
        const prev = item.previousElementSibling;
        if (!prev || !prev.classList.contains('menu-item')) {
            return 0;
        }
        const prevDepth = parseInt(prev.dataset.indent || '0', 10);
        return Math.min(targetDepth, prevDepth + 1, maxDepth);
    }

    function setIndent(item, depth) {
        const safeDepth = Math.max(0, Math.min(depth, maxDepth));
        item.dataset.indent = String(safeDepth);
        item.classList.remove('menu-indent-0', 'menu-indent-1', 'menu-indent-2', 'menu-indent-3');
        item.classList.add(`menu-indent-${safeDepth}`);
        updateLevelLabel(item, safeDepth);
    }

    function updateLevelLabel(item, depth) {
        const label = item.querySelector('[data-role="level"]');
        if (!label) return;
        label.textContent = levelLabels[depth] || levelLabels[0] || '';
    }

    function updateItemDisplay(item) {
        if (!item) return;
        const labelInput = item.querySelector('[data-field="label"]');
        const title = item.querySelector('[data-role="title"]');

        if (title && labelInput) {
            title.textContent = labelInput.value.trim() || title.dataset.fallback || title.textContent;
        }
    }

    function toggleConfig(button) {
        const item = button.closest('.menu-item');
        if (!item) return;
        const panel = item.querySelector('[data-role="config"]');
        if (!panel) return;

        panel.classList.toggle('is-open');
        const expanded = panel.classList.contains('is-open');
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }

    function confirmDeleteAction(message, onConfirm, options) {
        if (typeof onConfirm !== 'function') {
            return;
        }

        const finalMessage = String(message || confirmRemove);
        const opts = options || {};
        const finalConfirmText = String(opts.confirmText || confirmDelete);
        const finalWarning = String(opts.warning || '');
        const finalItemName = String(opts.itemName || '');
        const modal = window.FlatCMS && window.FlatCMS.modal && window.FlatCMS.modal.confirm;

        if (typeof modal === 'function') {
            modal(finalMessage, onConfirm, {
                confirmText: finalConfirmText,
                warning: finalWarning,
                itemName: finalItemName,
            });
            return;
        }

        if (confirm(finalMessage)) {
            onConfirm();
        }
    }

    function confirmAction(message, onConfirm) {
        confirmDeleteAction(message || confirmRemove, onConfirm);
    }

    function removeItem(button) {
        const item = button.closest('.menu-item');
        if (!item) return;

        confirmAction(confirmRemove, () => {
            item.remove();
            updateEmptyState();
            refreshAvailableGroupCounts();
            showToast(toastItemRemoved, 'success');
        });
    }

    function returnItem(button) {
        if (!availableList) return;
        const item = button.closest('.menu-item');
        if (!item) return;

        const targetGroup = resolveItemAvailableGroup(item);
        const targetList = getAvailableGroupList(targetGroup);
        if (!targetList) return;

        const order = parseInt(item.dataset.order || '0', 10);
        setIndent(item, 0);
        setItemActiveState(item, false);

        const panel = item.querySelector('[data-role="config"]');
        if (panel) panel.classList.remove('is-open');

        removeAvailableByKey(getItemKey(item));
        insertByOrder(targetList, item, order);
        openAvailableGroup(targetGroup);
        updateEmptyState();
        refreshAvailableGroupCounts();
        showToast(toastItemReturned, 'success');
    }

    function setItemActiveState(item, isActive) {
        if (isActive) {
            item.classList.add('menu-item--active');
            item.dataset.origin = 'active';
        } else {
            item.classList.remove('menu-item--active');
            item.dataset.origin = 'available';
        }

        const returnBtn = item.querySelector('.menu-return-btn');
        if (returnBtn) {
            returnBtn.style.display = isActive ? 'inline-flex' : 'none';
        }
    }

    function syncActiveStates() {
        if (!activeList || !availableList) return;
        activeList.querySelectorAll('.menu-item').forEach(item => setItemActiveState(item, true));
        availableList.querySelectorAll('.menu-item').forEach(item => setItemActiveState(item, false));
    }

    function removeAvailableDuplicates() {
        if (!activeList || !availableList) return;
        const activeKeys = new Set();
        activeList.querySelectorAll('.menu-item').forEach(item => {
            const key = getItemKey(item);
            if (key) activeKeys.add(key);
        });
        availableList.querySelectorAll('.menu-item').forEach(item => {
            const key = getItemKey(item);
            if (key && activeKeys.has(key)) {
                item.remove();
            }
        });
        refreshAvailableGroupCounts();
    }

    function updateEmptyState() {
        if (!emptyState || !activeList) return;
        const hasItems = activeList.querySelector('.menu-item');
        emptyState.classList.toggle('menu-empty-hidden', !!hasItems);
    }

    function normalizeAvailableGroup(type, source) {
        const sourceKey = String(source || '').toLowerCase();
        const typeKey = String(type || '').toLowerCase();
        if (sourceKey === 'custom' || typeKey === 'cta') return 'cta';
        if (typeKey === 'posts') return 'posts';
        if (typeKey === 'categories') return 'categories';
        return 'pages';
    }

    function resolveItemAvailableGroup(item) {
        if (!item) return 'pages';
        return normalizeAvailableGroup(item.dataset.type || '', item.dataset.source || '');
    }

    function getAvailableGroupList(group) {
        if (!availableList) return null;
        const normalized = normalizeAvailableGroup(group, group);
        return availableList.querySelector(`[data-available-group="${normalized}"]`)
            || availableList.querySelector('[data-available-group]');
    }

    function refreshAvailableGroupCounts() {
        if (!availableList) return;
        const groups = getAvailableAccordionGroups();
        groups.forEach((group) => {
            const list = group.querySelector('[data-available-group]');
            const count = list ? list.querySelectorAll('.menu-item').length : 0;
            const counter = group.querySelector('[data-role="available-group-count"]');
            if (counter) {
                counter.textContent = String(count);
            }
        });
        scheduleAvailableFloatingUpdate();
        scheduleAvailableAccordionScrollLayout();
    }

    function clearAvailableAccordionScrollLayout() {
        const groups = getAvailableAccordionGroups();
        groups.forEach((group) => {
            const panel = getAvailableAccordionPanel(group);
            const body = panel ? panel.querySelector('.menu-accordion-body') : null;
            if (!body) {
                return;
            }

            body.classList.remove('is-scrollable');
            body.style.removeProperty('height');
            body.style.removeProperty('max-height');
        });
    }

    function layoutAvailableAccordionScroll() {
        clearAvailableAccordionScrollLayout();

        if (!availableSidebar || !availableCard || !availableList) {
            return;
        }

        const isFloatingFixed = availableFloatingState === 'fixed'
            || availableSidebar.classList.contains('is-floating-fixed')
            || availableCard.classList.contains('is-floating-fixed')
            || availableCard.style.position === 'fixed';
        if (!isFloatingFixed) {
            return;
        }

        const openGroup = availableList.querySelector('.menu-accordion-group.is-open');
        if (!openGroup) {
            return;
        }

        const panel = getAvailableAccordionPanel(openGroup);
        const body = panel ? panel.querySelector('.menu-accordion-body') : null;
        if (!panel || !body) {
            return;
        }

        const bodyRect = body.getBoundingClientRect();
        const viewportMargin = getCssVarPx('--spacing-4', 16);
        let bottomLimit = window.innerHeight - viewportMargin;

        if (availableCustomCard) {
            const customRect = availableCustomCard.getBoundingClientRect();
            if (customRect.top > bodyRect.top && customRect.top < bottomLimit) {
                bottomLimit = Math.min(bottomLimit, customRect.top - getCssVarPx('--spacing-4', 16));
            }
        }

        const availableHeight = Math.floor(bottomLimit - bodyRect.top);
        const contentHeight = Math.ceil(body.scrollHeight);

        if (!Number.isFinite(contentHeight) || contentHeight <= 0) {
            return;
        }

        if (contentHeight <= availableHeight) {
            return;
        }

        if (availableHeight <= 120) {
            return;
        }

        body.style.maxHeight = `${availableHeight}px`;
        body.classList.add('is-scrollable');
    }

    function scheduleAvailableAccordionScrollLayout() {
        if (availableAccordionScrollFrame !== 0) {
            return;
        }

        availableAccordionScrollFrame = window.requestAnimationFrame(() => {
            availableAccordionScrollFrame = 0;
            layoutAvailableAccordionScroll();
        });
    }

    function initAvailableFloatingPanel() {
        if (!availableSidebar || !availableCard || !menuEditorLayout) {
            return;
        }

        window.addEventListener('scroll', scheduleAvailableFloatingUpdate, { passive: true });
        window.addEventListener('resize', remeasureAvailableFloatingPanel);
        window.addEventListener('orientationchange', remeasureAvailableFloatingPanel);

        if (typeof ResizeObserver === 'function') {
            availableFloatingResizeObserver = new ResizeObserver(remeasureAvailableFloatingPanel);
            availableFloatingResizeObserver.observe(availableSidebar);
            availableFloatingResizeObserver.observe(availableCard);
            if (topHeader) {
                availableFloatingResizeObserver.observe(topHeader);
            }
        }

        measureAvailableFloatingPanel();
        updateAvailableFloatingPanel();
        window.setTimeout(remeasureAvailableFloatingPanel, 80);
    }

    function getAvailableFloatingScrollY() {
        return window.pageYOffset || document.documentElement.scrollTop || 0;
    }

    function resolveAvailableFloatingTopOffset() {
        if (topHeader) {
            const headerRect = topHeader.getBoundingClientRect();
            if (headerRect.height > 0) {
                return Math.round(headerRect.height + 12);
            }
        }

        const computed = window.getComputedStyle(menuEditorLayout || document.documentElement);
        const rawValue = String(computed.getPropertyValue('--menu-sticky-top') || '').trim();
        if (rawValue === '') {
            return 76;
        }

        const probe = document.createElement('div');
        probe.style.position = 'absolute';
        probe.style.visibility = 'hidden';
        probe.style.pointerEvents = 'none';
        probe.style.height = rawValue;
        document.body.appendChild(probe);
        const value = probe.getBoundingClientRect().height;
        probe.remove();

        return Number.isFinite(value) && value > 0 ? value : 76;
    }

    function clearAvailableFloatingClasses() {
        availableSidebar.classList.remove('is-floating-active');
        availableSidebar.classList.remove('is-floating-fixed');
        availableCard.classList.remove('is-floating-fixed');
    }

    function syncAvailableFloatingClasses() {
        if (availableFloatingState !== 'fixed') {
            return;
        }

        availableSidebar.classList.add('is-floating-active', 'is-floating-fixed');
        availableCard.classList.add('is-floating-fixed');
    }

    function clearAvailableFloatingInlineStyles() {
        availableSidebar.style.removeProperty('min-height');

        availableCard.style.removeProperty('position');
        availableCard.style.removeProperty('top');
        availableCard.style.removeProperty('left');
        availableCard.style.removeProperty('width');
        availableCard.style.removeProperty('max-width');
        availableCard.style.removeProperty('bottom');
        availableCard.style.removeProperty('z-index');
    }

    function applyAvailableFloatingInlineStyles() {
        if (!availableFloatingMetrics) {
            return;
        }

        availableSidebar.style.minHeight = `${availableFloatingMetrics.cardHeight}px`;

        availableCard.style.position = 'fixed';
        availableCard.style.top = `${availableFloatingMetrics.topOffset}px`;
        availableCard.style.left = `${availableFloatingMetrics.left}px`;
        availableCard.style.width = `${availableFloatingMetrics.width}px`;
        availableCard.style.maxWidth = `${availableFloatingMetrics.width}px`;
        availableCard.style.bottom = 'auto';
        availableCard.style.zIndex = '34';
    }

    function resetAvailableFloatingPanel() {
        clearAvailableFloatingClasses();
        availableFloatingState = 'normal';
        availableFloatingMetrics = null;

        menuEditorLayout.style.removeProperty('--menu-sticky-top');
        menuEditorLayout.style.removeProperty('--menu-floating-left');
        menuEditorLayout.style.removeProperty('--menu-floating-width');
        menuEditorLayout.style.removeProperty('--menu-floating-height');
        clearAvailableAccordionScrollLayout();
        clearAvailableFloatingInlineStyles();
    }

    function measureAvailableFloatingPanel() {
        clearAvailableFloatingClasses();

        const scrollY = getAvailableFloatingScrollY();
        const sidebarRect = availableSidebar.getBoundingClientRect();
        const topOffset = resolveAvailableFloatingTopOffset();
        const sidebarTop = sidebarRect.top + scrollY;
        const cardHeight = availableCard.offsetHeight;
        const start = sidebarTop - topOffset;

        availableFloatingMetrics = {
            start,
            topOffset,
            width: sidebarRect.width,
            left: sidebarRect.left,
            cardHeight,
        };

        menuEditorLayout.style.setProperty('--menu-sticky-top', `${topOffset}px`);
        menuEditorLayout.style.setProperty('--menu-floating-left', `${sidebarRect.left}px`);
        menuEditorLayout.style.setProperty('--menu-floating-width', `${sidebarRect.width}px`);
        menuEditorLayout.style.setProperty('--menu-floating-height', `${cardHeight}px`);

        if (availableFloatingState === 'fixed') {
            syncAvailableFloatingClasses();
            applyAvailableFloatingInlineStyles();
        }
    }

    function applyAvailableFloatingState(nextState) {
        if (availableFloatingState === nextState) {
            syncAvailableFloatingClasses();
            if (nextState === 'fixed') {
                applyAvailableFloatingInlineStyles();
            }
            scheduleAvailableAccordionScrollLayout();
            return;
        }

        availableFloatingState = nextState;
        clearAvailableFloatingClasses();

        if (nextState === 'fixed') {
            availableSidebar.classList.add('is-floating-active', 'is-floating-fixed');
            availableCard.classList.add('is-floating-fixed');
            applyAvailableFloatingInlineStyles();
            scheduleAvailableAccordionScrollLayout();
            return;
        }

        clearAvailableAccordionScrollLayout();
        clearAvailableFloatingInlineStyles();
    }

    function updateAvailableFloatingPanel() {
        if (!availableSidebar || !availableCard || !menuEditorLayout) {
            return;
        }

        if (window.matchMedia('(max-width: 1024px)').matches) {
            resetAvailableFloatingPanel();
            return;
        }

        if (!availableFloatingMetrics) {
            measureAvailableFloatingPanel();
        }

        if (!availableFloatingMetrics) {
            return;
        }

        const scrollY = getAvailableFloatingScrollY();
        const nextState = scrollY > availableFloatingMetrics.start ? 'fixed' : 'normal';
        applyAvailableFloatingState(nextState);
        scheduleAvailableAccordionScrollLayout();
    }

    function scheduleAvailableFloatingUpdate() {
        if (availableFloatingFrame !== 0) {
            return;
        }

        availableFloatingFrame = window.requestAnimationFrame(() => {
            availableFloatingFrame = 0;
            updateAvailableFloatingPanel();
        });
    }

    function remeasureAvailableFloatingPanel() {
        if (window.matchMedia('(max-width: 1024px)').matches) {
            resetAvailableFloatingPanel();
            return;
        }

        measureAvailableFloatingPanel();
        updateAvailableFloatingPanel();
        scheduleAvailableAccordionScrollLayout();
    }

    function insertByOrder(container, item, order) {
        const siblings = Array.from(container.querySelectorAll('.menu-item'));
        const next = siblings.find(el => parseInt(el.dataset.order || '0', 10) > order);
        if (next) {
            container.insertBefore(item, next);
        } else {
            container.appendChild(item);
        }
    }

    function addCustomItem() {
        if (!availableList || !itemTemplate) return;
        const labelInput = document.getElementById('menuCustomLabel');
        const urlInput = document.getElementById('menuCustomUrl');

        if (!labelInput || !urlInput) return;

        const label = labelInput.value.trim();
        const url = urlInput.value.trim();
        const target = /^https?:\/\//i.test(url) ? '_blank' : '_self';

        if (!label) {
            showToast(labelRequired, 'error');
            labelInput.focus();
            return;
        }

        const order = getNextOrder();
        const targetGroup = 'cta';
        const targetList = getAvailableGroupList(targetGroup);
        if (!targetList) return;

        const item = createItemFromTemplate({ label, url, icon: '', target, source: 'custom', type: 'cta' }, order);
        if (item) {
            removeAvailableByKey(getItemKey(item));
            targetList.appendChild(item);
            openAvailableGroup(targetGroup);
            labelInput.value = '';
            urlInput.value = '';
            refreshAvailableGroupCounts();
            showToast(toastCustomAdded, 'success');
        }
    }

    function getNextOrder() {
        if (!availableList) return 0;
        const orders = Array.from(availableList.querySelectorAll('.menu-item'))
            .map(el => parseInt(el.dataset.order || '0', 10));
        return orders.length ? Math.max(...orders) + 1 : 0;
    }

    function createItemFromTemplate(data, order) {
        if (!itemTemplate) return null;
        const node = itemTemplate.content.firstElementChild.cloneNode(true);
        node.dataset.id = data.id || generateId();
        node.dataset.order = String(order);
        node.dataset.indent = '0';
        node.dataset.origin = 'available';
        node.dataset.source = data.source || 'custom';
        node.dataset.type = normalizeAvailableGroup(data.type || '', data.source || '');
        node.dataset.labelMode = normalizeLabelMode(data.labelMode || '');
        node.dataset.refType = String(data.refType || '').trim();
        node.dataset.ref = String(data.ref || '').trim();
        node.dataset.autoLabel = String(data.autoLabel || data.label || '').trim();
        node.dataset.autoUrl = String(data.autoUrl || data.url || '').trim();
        setItemActiveState(node, false);

        const labelInput = node.querySelector('[data-field="label"]');
        const urlInput = node.querySelector('[data-field="url"]');
        const iconInput = node.querySelector('[data-field="icon"]');
        const iconTypeInput = node.querySelector('[data-field="iconType"]');
        const iconMediaInput = node.querySelector('[data-field="iconMedia"]');
        const translationsInput = node.querySelector('[data-field="translations"]');
        const translationFallbacksInput = node.querySelector('[data-field="translationFallbacks"]');
        const targetInput = node.querySelector('[data-field="target"]');

        if (labelInput) labelInput.value = data.label || '';
        if (urlInput) urlInput.value = data.url || '';
        const resolvedIconType = normalizeIconType(data.iconType || '', data.iconMedia || '');
        const resolvedIcon = resolvedIconType === 'media' ? '' : (data.icon || defaultIcon);
        const resolvedIconMedia = resolvedIconType === 'media' ? normalizeMediaPath(data.iconMedia || '') : '';
        if (iconInput) iconInput.value = resolvedIcon;
        if (iconTypeInput) iconTypeInput.value = resolvedIconType;
        if (iconMediaInput) iconMediaInput.value = resolvedIconMedia;
        if (translationsInput) {
            translationsInput.value = stringifyTranslations(data.translations || {});
        }
        if (translationFallbacksInput) {
            translationFallbacksInput.value = stringifyTranslations(data.translationFallbacks || {});
        }
        if (targetInput) targetInput.value = data.target || '_self';
        renderItemIcon(node);

        updateItemDisplay(node);
        syncReferenceState(node);
        return node;
    }

    function handleSubmit(event) {
        if (!dataField || !activeList) return;

        const data = buildMenuData();
        if (!data) {
            event.preventDefault();
            return;
        }

        dataField.value = JSON.stringify(data);

        if (libraryField && availableList) {
            const libraryData = buildLibraryData();
            libraryField.value = JSON.stringify(libraryData);
        }
    }

    function buildMenuData() {
        const items = [];
        const stack = [{ depth: -1, children: items }];
        let valid = true;

        const nodes = Array.from(activeList.querySelectorAll('.menu-item'));
        nodes.forEach((node) => {
            const data = getItemData(node);
            const label = data.label;

            if (!label) {
                valid = false;
                const labelInput = node.querySelector('[data-field="label"]');
                if (labelInput) labelInput.classList.add('is-invalid');
                return;
            }

            let depth = parseInt(node.dataset.indent || '0', 10);
            depth = Math.max(0, Math.min(depth, maxDepth));

            while (stack.length && depth <= stack[stack.length - 1].depth) {
                stack.pop();
            }

            const entry = { id: data.id || generateId(), label, url: data.url };
            if (data.icon) entry.icon = data.icon;
            if (data.iconType === 'media' && data.iconMedia) {
                entry.iconType = 'media';
                entry.iconMedia = data.iconMedia;
            }
            if (data.target && (data.target === '_self' || data.target === '_blank')) entry.target = data.target;
            if (data.labelMode) entry.labelMode = data.labelMode;
            if (data.refType && data.ref) {
                entry.refType = data.refType;
                entry.ref = data.ref;
            }
            if (hasTranslations(data.translations)) {
                entry.translations = data.translations;
            }
            entry.children = [];

            stack[stack.length - 1].children.push(entry);
            stack.push({ depth, children: entry.children });
        });

        if (!valid) {
            showToast(labelRequired, 'error');
            return null;
        }

        return stripEmptyChildren(items);
    }

    function buildLibraryData() {
        if (!availableList) return [];
        const nodes = Array.from(availableList.querySelectorAll('.menu-item'));
        const items = [];
        nodes.forEach((node) => {
            const source = (node.dataset.source || '').toLowerCase();
            if (source !== 'custom') return;
            const data = getItemData(node);
            if (!data.label) return;
            const entry = { label: data.label, url: data.url };
            if (data.icon) entry.icon = data.icon;
            if (data.iconType === 'media' && data.iconMedia) {
                entry.iconType = 'media';
                entry.iconMedia = data.iconMedia;
            }
            if (data.target) entry.target = data.target;
            if (data.labelMode) entry.labelMode = data.labelMode;
            if (data.refType && data.ref) {
                entry.refType = data.refType;
                entry.ref = data.ref;
            }
            if (hasTranslations(data.translations)) {
                entry.translations = data.translations;
            }
            items.push(entry);
        });
        return items;
    }

    function stripEmptyChildren(items) {
        return items.map(item => {
            const cloned = { id: item.id, label: item.label, url: item.url };
            if (item.icon) cloned.icon = item.icon;
            if (item.iconType === 'media' && item.iconMedia) {
                cloned.iconType = 'media';
                cloned.iconMedia = item.iconMedia;
            }
            if (item.target) cloned.target = item.target;
            if (item.labelMode) cloned.labelMode = item.labelMode;
            if (item.refType && item.ref) {
                cloned.refType = item.refType;
                cloned.ref = item.ref;
            }
            if (hasTranslations(item.translations)) {
                cloned.translations = item.translations;
            }
            if (item.children && item.children.length) {
                cloned.children = stripEmptyChildren(item.children);
            }
            return cloned;
        });
    }

    function getItemLabel(item) {
        const input = item.querySelector('[data-field="label"]');
        if (input) return input.value.trim();
        const title = item.querySelector('[data-role="title"]');
        return title ? title.textContent.trim() : '';
    }

    function getItemUrl(item) {
        const input = item.querySelector('[data-field="url"]');
        if (input) return input.value.trim();
        const url = item.querySelector('[data-role="url"]');
        return url ? url.textContent.trim() : '';
    }

    function getItemKey(item) {
        if (!item) return '';
        const url = getItemUrl(item);
        const base = url !== '' ? url : '__home__';
        return base.toLowerCase();
    }

    function removeAvailableByKey(key) {
        if (!availableList || !key) return;
        const items = Array.from(availableList.querySelectorAll('.menu-item'));
        items.forEach(item => {
            if (getItemKey(item) === key) {
                item.remove();
            }
        });
    }

    function openIconPicker(button) {
        const item = button.closest('.menu-item');
        if (!item || !iconBrowser) return;
        iconTarget = item;
        iconBrowser.open(button);
    }

    function closeIconModal() {
        if (iconBrowser) {
            iconBrowser.close();
        }
    }

    function applyIcon(iconClass) {
        if (!iconTarget) return;
        setIconState(iconTarget, {
            icon: iconClass,
            iconType: '',
            iconMedia: '',
        });
        showToast(toastIconUpdated, 'success');
        closeIconModal();
    }

    function applyCustomIcon(iconMedia) {
        if (!iconTarget) return;
        const normalizedPath = normalizeMediaPath(iconMedia);
        if (!normalizedPath) return;
        setIconState(iconTarget, {
            icon: '',
            iconType: 'media',
            iconMedia: normalizedPath,
        });
        showToast(toastCustomIconSelected, 'success');
        closeIconModal();
    }

    function clearIcon(button) {
        const item = button.closest('.menu-item');
        if (!item) return;
        const data = getItemData(item);
        if (!data.icon && !(data.iconType === 'media' && data.iconMedia)) return;
        setIconState(item, {
            icon: '',
            iconType: '',
            iconMedia: '',
        });
        showToast(toastIconRemoved, 'success');
    }

    function setIconState(item, state) {
        if (!item) return;
        const iconInput = item.querySelector('[data-field="icon"]');
        const iconTypeInput = item.querySelector('[data-field="iconType"]');
        const iconMediaInput = item.querySelector('[data-field="iconMedia"]');
        const icon = String((state && state.icon) || '').trim();
        const iconMedia = normalizeMediaPath(String((state && state.iconMedia) || ''));
        const iconType = normalizeIconType(String((state && state.iconType) || ''), iconMedia);

        if (iconInput) {
            iconInput.value = iconType === 'media' ? '' : icon;
        }
        if (iconTypeInput) {
            iconTypeInput.value = iconType;
        }
        if (iconMediaInput) {
            iconMediaInput.value = iconType === 'media' ? iconMedia : '';
        }

        renderItemIcon(item);
    }

    function renderItemIcon(item) {
        if (!item) return;
        const iconInput = item.querySelector('[data-field="icon"]');
        const iconTypeInput = item.querySelector('[data-field="iconType"]');
        const iconMediaInput = item.querySelector('[data-field="iconMedia"]');
        const iconWrap = item.querySelector('.menu-item-icon');
        if (!iconWrap) return;

        const iconValue = iconInput ? iconInput.value.trim() : '';
        const iconMedia = iconMediaInput ? normalizeMediaPath(iconMediaInput.value) : '';
        const iconType = iconTypeInput ? normalizeIconType(iconTypeInput.value, iconMedia) : '';

        iconWrap.innerHTML = '';
        if (iconType === 'media' && iconMedia) {
            const media = document.createElement('span');
            media.className = 'menu-item-icon-media';
            media.innerHTML = `<img src="${escapeHtml(iconMedia)}" alt="">`;
            iconWrap.appendChild(media);
        } else {
            const replacement = document.createElement('i');
            replacement.className = iconValue ? `${iconValue} menu-item-icon-preview` : 'menu-item-icon-preview is-empty';
            iconWrap.appendChild(replacement);
        }

        iconWrap.classList.toggle('is-empty', !(iconType === 'media' && iconMedia) && !iconValue);

        if (window.FontAwesome && window.FontAwesome.dom && typeof window.FontAwesome.dom.i2svg === 'function') {
            window.FontAwesome.dom.i2svg({ node: iconWrap });
        }
    }

    function getItemData(node) {
        const labelInput = node.querySelector('[data-field="label"]');
        const urlInput = node.querySelector('[data-field="url"]');
        const iconInput = node.querySelector('[data-field="icon"]');
        const iconTypeInput = node.querySelector('[data-field="iconType"]');
        const iconMediaInput = node.querySelector('[data-field="iconMedia"]');
        const translationsInput = node.querySelector('[data-field="translations"]');
        const translationFallbacksInput = node.querySelector('[data-field="translationFallbacks"]');
        const targetInput = node.querySelector('[data-field="target"]');
        const id = (node.dataset.id || '').trim() || generateId();
        const refType = (node.dataset.refType || '').trim();
        const ref = (node.dataset.ref || '').trim();
        const labelMode = normalizeLabelMode(node.dataset.labelMode || '');

        const label = labelInput ? labelInput.value.trim() : '';
        const url = urlInput ? urlInput.value.trim() : '';
        const icon = iconInput ? iconInput.value.trim() : '';
        const iconMedia = iconMediaInput ? normalizeMediaPath(iconMediaInput.value) : '';
        const iconType = iconTypeInput ? normalizeIconType(iconTypeInput.value, iconMedia) : '';
        const target = targetInput ? targetInput.value.trim() : '';
        const translations = translationsInput ? parseTranslationsInput(translationsInput.value) : {};
        const translationFallbacks = translationFallbacksInput ? parseTranslationsInput(translationFallbacksInput.value) : {};

        node.dataset.id = id;
        return { id, label, url, icon, iconType, iconMedia, target, refType, ref, labelMode, translations, translationFallbacks };
    }

    function normalizeLabelMode(value) {
        const mode = String(value || '').trim().toLowerCase();
        return mode === 'custom' ? 'custom' : (mode === 'auto' ? 'auto' : '');
    }

    function syncReferenceState(item) {
        if (!item) return;

        const refType = String(item.dataset.refType || '').trim();
        const ref = String(item.dataset.ref || '').trim();
        if (!refType || !ref) {
            item.dataset.labelMode = '';
            return;
        }

        const labelInput = item.querySelector('[data-field="label"]');
        const autoLabel = String(item.dataset.autoLabel || '').trim();
        const currentLabel = labelInput ? labelInput.value.trim() : '';

        if (autoLabel === '') {
            item.dataset.labelMode = normalizeLabelMode(item.dataset.labelMode || 'auto') || 'auto';
            return;
        }

        item.dataset.labelMode = currentLabel !== '' && currentLabel !== autoLabel ? 'custom' : 'auto';
    }

    function syncReferenceStates() {
        const items = document.querySelectorAll('.menu-item');
        items.forEach((item) => {
            syncReferenceState(item);
        });
    }

    function syncIcons() {
        const items = document.querySelectorAll('.menu-item');
        items.forEach(item => {
            renderItemIcon(item);
        });
    }

    function openTranslationModal(button) {
        const item = button.closest('.menu-item');
        if (!item || !translationModal) return;

        translationTarget = item;
        syncTranslationSourceLabel(item);

        const itemData = getItemData(item);
        const currentTranslations = itemData.translations || {};
        const fallbackTranslations = itemData.translationFallbacks || {};
        translationFields.forEach((field) => {
            const localeCode = String(field.dataset.locale || '').trim();
            if (!localeCode) {
                field.value = '';
                return;
            }
            const explicitLabel = typeof currentTranslations[localeCode] === 'string'
                ? String(currentTranslations[localeCode]).trim()
                : '';
            const fallbackLabel = typeof fallbackTranslations[localeCode] === 'string'
                ? String(fallbackTranslations[localeCode]).trim()
                : '';
            field.value = explicitLabel || fallbackLabel || '';
        });

        if (translationModalController) {
            translationModalController.open(button, {
                initialFocus: '[data-locale]'
            });
        }
    }

    function closeTranslationModal() {
        if (!translationModal) return;
        if (translationModalController) {
            translationModalController.close();
        }
    }

    function saveTranslations() {
        if (!translationTarget) return;

        const translationsInput = translationTarget.querySelector('[data-field="translations"]');
        if (!translationsInput) {
            closeTranslationModal();
            return;
        }

        const sourceLabel = getItemLabel(translationTarget);
        const translations = {};

        translationFields.forEach((field) => {
            const localeCode = String(field.dataset.locale || '').trim();
            const value = field.value.trim();
            if (!localeCode || localeCode === sourceLocale || value === '' || value === sourceLabel) {
                return;
            }
            translations[localeCode] = value;
        });

        translationsInput.value = stringifyTranslations(translations);
        showToast(toastTranslationSaved, 'success');
        closeTranslationModal();
    }

    function syncTranslationSourceLabel(item) {
        if (!translationSourceLabel) return;
        translationSourceLabel.textContent = getItemLabel(item) || translationSourceLabel.textContent;
    }

    function openCustomIconMediaModal() {
        if (!iconTarget || !window.FlatCMS || !window.FlatCMS.AdminUI || !window.FlatCMS.AdminUI.media) {
            showToast(mediaModalUnavailable, 'warning');
            return;
        }

        closeIconModal();

        if (!window.FlatCMS.AdminUI.media.open({
            mode: 'images',
            accept: customIconAccept || '.png,.gif,.webp,.avif,image/png,image/gif,image/webp,image/avif',
            initialTab: 'library',
            openUploadIfEmpty: false,
            onSelect: function(file) {
                if (!isAllowedCustomIconFile(file)) {
                    showToast(customIconInvalidType, 'error');
                    return;
                }

                const mediaPath = normalizeMediaPathFromFile(file || {});
                if (!mediaPath) {
                    showToast(customIconInvalidType, 'error');
                    return;
                }

                setIconState(iconTarget, {
                    icon: '',
                    iconType: 'media',
                    iconMedia: mediaPath,
                });
                window.FlatCMS.AdminUI.media.close();
                showToast(toastCustomIconSelected, 'success');
            },
            onUploadComplete: function(payload) {
                const results = Array.isArray(payload && payload.results) ? payload.results : [];
                const successResult = results.find((result) => result && result.success && result.media) || null;
                const media = successResult && successResult.media ? successResult.media : null;
                if (!media) {
                    return;
                }
                if (!isAllowedCustomIconFile(media)) {
                    showToast(customIconInvalidType, 'error');
                    return;
                }
                showToast(toastCustomIconUploaded, 'success');
            },
        })) {
            showToast(mediaModalUnavailable, 'warning');
        }
    }

    function isAllowedCustomIconFile(file) {
        if (!file || typeof file !== 'object') return false;
        const mime = String(file.mime || '').toLowerCase();
        const extension = String(file.extension || '').toLowerCase();
        return mime === 'image/png'
            || mime === 'image/gif'
            || mime === 'image/webp'
            || mime === 'image/avif'
            || extension === 'png'
            || extension === 'gif'
            || extension === 'webp'
            || extension === 'avif';
    }

    function normalizeMediaPathFromFile(file) {
        if (!file || typeof file !== 'object') return '';
        const path = String(file.path || '').trim();
        if (path) {
            return normalizeMediaPath(path);
        }
        const url = String(file.url || '').trim();
        return normalizeMediaPath(url);
    }

    function normalizeMediaPath(value) {
        let candidate = String(value || '').trim();
        if (!candidate) {
            return '';
        }

        if (/^https?:\/\//i.test(candidate)) {
            const parsed = new URL(candidate, window.location.origin);
            candidate = parsed.pathname || '';
        }

        candidate = candidate.replace(/^\/public\/uploads\//, '/uploads/');
        candidate = candidate.replace(/^public\/uploads\//, '/uploads/');
        if (/^uploads\//i.test(candidate)) {
            candidate = `/${candidate}`;
        } else if (/^images\//i.test(candidate)) {
            candidate = `/uploads/${candidate}`;
        }

        if (!candidate.startsWith('/uploads/images/')) {
            return '';
        }

        if (!/\.(png|gif|webp|avif)$/i.test(candidate)) {
            return '';
        }

        return candidate;
    }

    function normalizeIconType(value, iconMedia) {
        return String(value || '').trim().toLowerCase() === 'media' && iconMedia ? 'media' : '';
    }

    function parseTranslationsInput(value) {
        const allowedLocales = translationLocaleCodes.length ? new Set(translationLocaleCodes) : null;
        try {
            const decoded = JSON.parse(String(value || '{}'));
            if (!decoded || typeof decoded !== 'object' || Array.isArray(decoded)) {
                return {};
            }

            const sanitized = {};
            Object.keys(decoded).forEach((localeCode) => {
                const normalizedLocale = String(localeCode || '').trim();
                const translatedLabel = decoded[localeCode];
                if (
                    !normalizedLocale
                    || normalizedLocale === sourceLocale
                    || (allowedLocales && !allowedLocales.has(normalizedLocale))
                    || typeof translatedLabel !== 'string'
                ) {
                    return;
                }
                const normalizedLabel = translatedLabel.trim();
                if (!normalizedLabel) {
                    return;
                }
                sanitized[normalizedLocale] = normalizedLabel;
            });

            return sanitized;
        } catch (error) {
            return {};
        }
    }

    function stringifyTranslations(translations) {
        return JSON.stringify(parseTranslationsInput(JSON.stringify(translations || {})));
    }

    function hasTranslations(translations) {
        return !!translations && typeof translations === 'object' && !Array.isArray(translations) && Object.keys(translations).length > 0;
    }

    function ensureItemIds() {
        const items = document.querySelectorAll('.menu-item');
        items.forEach(item => {
            if (!item.dataset.id) {
                item.dataset.id = generateId();
            }
        });
    }

    function getCssVarPx(name, fallback) {
        const value = window.getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        if (!value) {
            return fallback;
        }

        if (/^-?\d+(\.\d+)?px$/i.test(value) || /^-?\d+(\.\d+)?$/i.test(value)) {
            const parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : fallback;
        }

        const probe = document.createElement('div');
        probe.style.position = 'absolute';
        probe.style.visibility = 'hidden';
        probe.style.pointerEvents = 'none';
        probe.style.width = value;
        document.body.appendChild(probe);
        const resolved = probe.getBoundingClientRect().width;
        probe.remove();

        return Number.isFinite(resolved) && resolved > 0 ? resolved : fallback;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function generateId() {
        idSeed += 1;
        return `menu_${idSeed.toString(36)}`;
    }
})();
