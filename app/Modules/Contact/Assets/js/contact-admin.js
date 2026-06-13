/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function () {
    'use strict';

    function initFloatingOptionsCard() {
        var sidebar = document.querySelector('.contact-form-sidebar--sticky');
        var card = sidebar ? sidebar.querySelector('.contact-form-sidebar-card--sticky') : null;
        var layout = sidebar ? sidebar.closest('.contact-form-layout') : null;
        if (!sidebar || !card || !layout) {
            return;
        }

        var desktopBreakpoint = 0;
        var topHeader = document.querySelector('.top-header');
        var frameId = 0;
        var state = 'normal';
        var metrics = null;

        function getScrollY() {
            return window.pageYOffset || document.documentElement.scrollTop || 0;
        }

        function resolveTopOffset() {
            if (topHeader) {
                var headerRect = topHeader.getBoundingClientRect();
                if (headerRect.height > 0) {
                    return Math.round(headerRect.height + 12);
                }
            }

            var computed = window.getComputedStyle(layout);
            var rawValue = String(computed.getPropertyValue('--contact-sticky-top') || '').trim();
            if (rawValue === '') {
                return 76;
            }

            var probe = document.createElement('div');
            probe.style.position = 'absolute';
            probe.style.visibility = 'hidden';
            probe.style.pointerEvents = 'none';
            probe.style.height = rawValue;
            document.body.appendChild(probe);
            var value = probe.getBoundingClientRect().height;
            probe.remove();

            return Number.isFinite(value) && value > 0 ? value : 76;
        }

        function clearStateClasses() {
            sidebar.classList.remove('is-floating-active');
            sidebar.classList.remove('is-floating-fixed');
        }

        function clearInlineFloatingStyles() {
            sidebar.style.removeProperty('min-height');

            card.style.removeProperty('position');
            card.style.removeProperty('top');
            card.style.removeProperty('left');
            card.style.removeProperty('width');
            card.style.removeProperty('bottom');
            card.style.removeProperty('z-index');
        }

        function applyInlineFixedStyles() {
            if (!metrics) {
                return;
            }

            sidebar.style.minHeight = metrics.cardHeight + 'px';

            card.style.position = 'fixed';
            card.style.top = metrics.topOffset + 'px';
            card.style.left = metrics.left + 'px';
            card.style.width = metrics.width + 'px';
            card.style.bottom = 'auto';
            card.style.zIndex = '34';
        }

        function resetFloating() {
            clearStateClasses();
            state = 'normal';
            metrics = null;
            layout.style.removeProperty('--contact-sticky-top');
            layout.style.removeProperty('--contact-floating-left');
            layout.style.removeProperty('--contact-floating-width');
            layout.style.removeProperty('--contact-floating-height');
            clearInlineFloatingStyles();
        }

        function measureFloating() {
            clearStateClasses();

            var scrollY = getScrollY();
            var sidebarRect = sidebar.getBoundingClientRect();
            var topOffset = resolveTopOffset();
            var sidebarTop = sidebarRect.top + scrollY;
            var cardHeight = card.offsetHeight;
            var start = sidebarTop - topOffset;

            metrics = {
                start: start,
                topOffset: topOffset,
                width: sidebarRect.width,
                left: sidebarRect.left,
                cardHeight: cardHeight,
            };

            layout.style.setProperty('--contact-sticky-top', topOffset + 'px');
            layout.style.setProperty('--contact-floating-left', sidebarRect.left + 'px');
            layout.style.setProperty('--contact-floating-width', sidebarRect.width + 'px');
            layout.style.setProperty('--contact-floating-height', cardHeight + 'px');

            if (state === 'fixed') {
                applyInlineFixedStyles();
            }
        }

        function applyState(nextState) {
            if (state === nextState) {
                return;
            }

            state = nextState;
            clearStateClasses();

            if (nextState === 'fixed') {
                sidebar.classList.add('is-floating-active', 'is-floating-fixed');
                applyInlineFixedStyles();
                return;
            }

            clearInlineFloatingStyles();
        }

        function updateFloating() {
            if (window.innerWidth <= desktopBreakpoint) {
                resetFloating();
                return;
            }

            if (!metrics) {
                measureFloating();
            }

            if (!metrics) {
                return;
            }

            var scrollY = getScrollY();
            var nextState = 'normal';

            if (scrollY > metrics.start) {
                nextState = 'fixed';
            }

            applyState(nextState);
        }

        function scheduleUpdate() {
            if (frameId !== 0) {
                return;
            }

            frameId = window.requestAnimationFrame(function () {
                frameId = 0;
                updateFloating();
            });
        }

        function remeasureAndUpdate() {
            if (window.innerWidth <= desktopBreakpoint) {
                resetFloating();
                return;
            }

            measureFloating();
            updateFloating();
        }

        measureFloating();
        updateFloating();

        window.addEventListener('scroll', scheduleUpdate, { passive: true });
        window.addEventListener('resize', remeasureAndUpdate);
        window.addEventListener('orientationchange', remeasureAndUpdate);

        if (typeof ResizeObserver === 'function') {
            var stickyObserver = new ResizeObserver(remeasureAndUpdate);
            stickyObserver.observe(sidebar);
            stickyObserver.observe(card);
            if (topHeader) {
                stickyObserver.observe(topHeader);
            }
        }

        window.setTimeout(remeasureAndUpdate, 80);
    }

    function initMessagesModal() {
        var modal = document.querySelector('[data-contact-messages-modal]');
        if (!modal) {
            return;
        }

        var openButtons = document.querySelectorAll('[data-contact-open-messages]');
        var closeButtons = modal.querySelectorAll('[data-contact-modal-close]');
        var detailButtons = modal.querySelectorAll('[data-contact-open-detail]');
        var archiveForms = modal.querySelectorAll('[data-contact-archive-form]');
        var deleteForms = modal.querySelectorAll('[data-contact-delete-form]');
        var listPanel = modal.querySelector('[data-contact-messages-list]');
        var detailPanel = modal.querySelector('[data-contact-message-detail]');
        var backButton = modal.querySelector('[data-contact-detail-back]');
        var dataNode = document.getElementById('contactMessagesData');

        var detailName = modal.querySelector('[data-contact-detail-name]');
        var detailEmail = modal.querySelector('[data-contact-detail-email]');
        var detailPhone = modal.querySelector('[data-contact-detail-phone]');
        var detailReceived = modal.querySelector('[data-contact-detail-received]');
        var detailSubject = modal.querySelector('[data-contact-detail-subject]');
        var detailStatus = modal.querySelector('[data-contact-detail-status]');
        var detailFormType = modal.querySelector('[data-contact-detail-form-type]');
        var detailSource = modal.querySelector('[data-contact-detail-source]');
        var detailMessage = modal.querySelector('[data-contact-detail-message]');
        var detailCustomValues = modal.querySelector('[data-contact-detail-custom-values]');
        var detailAttachments = modal.querySelector('[data-contact-detail-attachments]');
        var readUrlTemplate = String(modal.getAttribute('data-read-url-template') || '').trim();
        var csrfToken = String(modal.getAttribute('data-csrf-token') || '').trim();
        var canManageStatusUpdate = String(modal.getAttribute('data-can-manage-status-update') || '') === '1';
        var deleteConfirmMessage = String(modal.getAttribute('data-delete-confirm-message') || '').trim();
        var deleteSuccessMessage = String(modal.getAttribute('data-delete-success-message') || '').trim();
        var listEmptyMessage = String(modal.getAttribute('data-list-empty-message') || '').trim();
        var openCountBadge = document.querySelector('.contact-open-messages-btn [data-contact-count-trigger]');
        var modalCountAllBadge = modal.querySelector('.contact-messages-modal__status-bar [data-contact-count-all]');
        var modalCountNewBadge = modal.querySelector('.contact-messages-modal__status-bar [data-contact-count-new]');
        var modalCountReadBadge = modal.querySelector('.contact-messages-modal__status-bar [data-contact-count-read]');
        var modalCountArchivedBadge = modal.querySelector('.contact-messages-modal__status-bar [data-contact-count-archived]');

        var messagesMap = {};
        if (dataNode) {
            try {
                messagesMap = JSON.parse(dataNode.textContent || '{}');
            } catch (error) {
                messagesMap = {};
            }
        }

        function statusLabel(status) {
            var normalized = String(status || '').toLowerCase();
            if (normalized === 'read') {
                return modal.getAttribute('data-status-read') || 'Read';
            }
            if (normalized === 'archived') {
                return modal.getAttribute('data-status-archived') || 'Archived';
            }
            return modal.getAttribute('data-status-new') || 'New';
        }

        function normalizeStatus(status) {
            var normalized = String(status || '').toLowerCase();
            if (normalized === 'read' || normalized === 'archived') {
                return normalized;
            }
            return 'new';
        }

        function extractBadgeLabel(node, fallback) {
            if (!node) {
                return fallback;
            }
            var text = String(node.textContent || '').trim();
            if (text === '') {
                return fallback;
            }
            var parts = text.split(':');
            var label = String(parts[0] || '').trim();
            return label !== '' ? label : fallback;
        }

        var modalAllLabel = extractBadgeLabel(modalCountAllBadge, 'All');
        var modalNewLabel = extractBadgeLabel(modalCountNewBadge, statusLabel('new'));
        var modalReadLabel = extractBadgeLabel(modalCountReadBadge, statusLabel('read'));
        var modalArchivedLabel = extractBadgeLabel(modalCountArchivedBadge, statusLabel('archived'));

        function setCountBadge(node, label, count, compact) {
            if (!node) {
                return;
            }
            if (compact) {
                node.textContent = String(count);
                return;
            }
            node.textContent = String(label) + ': ' + String(count);
        }

        function normalizeCounts(rawCounts) {
            var counts = rawCounts && typeof rawCounts === 'object' ? rawCounts : {};
            return {
                all: Number(counts.all || 0),
                new: Number(counts.new || 0),
                read: Number(counts.read || 0),
                archived: Number(counts.archived || 0),
            };
        }

        function applyServerCounts(rawCounts) {
            var counts = normalizeCounts(rawCounts);
            setCountBadge(openCountBadge, '', counts.new, true);
            setCountBadge(modalCountAllBadge, modalAllLabel, counts.all, false);
            setCountBadge(modalCountNewBadge, modalNewLabel, counts.new, false);
            setCountBadge(modalCountReadBadge, modalReadLabel, counts.read, false);
            setCountBadge(modalCountArchivedBadge, modalArchivedLabel, counts.archived, false);
        }

        function syncCountBadges() {
            var counts = {
                all: 0,
                new: 0,
                read: 0,
                archived: 0,
            };

            var rows = modal.querySelectorAll('[data-contact-message-row]');
            if (rows.length > 0) {
                for (var i = 0; i < rows.length; i += 1) {
                    counts.all += 1;
                    counts[normalizeStatus(rows[i].getAttribute('data-message-status') || 'new')] += 1;
                }
            } else {
                var ids = Object.keys(messagesMap);
                for (var j = 0; j < ids.length; j += 1) {
                    var item = messagesMap[ids[j]] || {};
                    counts.all += 1;
                    counts[normalizeStatus(item.status)] += 1;
                }
            }

            setCountBadge(openCountBadge, '', counts.new, true);
            setCountBadge(modalCountAllBadge, modalAllLabel, counts.all, false);
            setCountBadge(modalCountNewBadge, modalNewLabel, counts.new, false);
            setCountBadge(modalCountReadBadge, modalReadLabel, counts.read, false);
            setCountBadge(modalCountArchivedBadge, modalArchivedLabel, counts.archived, false);
        }

        function findMessageRow(messageId) {
            var rows = modal.querySelectorAll('[data-contact-message-row]');
            for (var i = 0; i < rows.length; i += 1) {
                if (String(rows[i].getAttribute('data-message-id') || '') === String(messageId || '')) {
                    return rows[i];
                }
            }
            return null;
        }

        function applyRowStatus(row, status) {
            if (!row) {
                return;
            }

            var normalized = normalizeStatus(status);
            row.setAttribute('data-message-status', normalized);
            var badge = row.querySelector('[data-contact-message-status-badge]');
            if (!badge) {
                return;
            }

            badge.classList.remove('badge-warning', 'badge-success', 'badge-secondary');
            if (normalized === 'read') {
                badge.classList.add('badge-success');
            } else if (normalized === 'archived') {
                badge.classList.add('badge-secondary');
            } else {
                badge.classList.add('badge-warning');
            }
            badge.textContent = statusLabel(normalized);

            var archiveButtons = row.querySelectorAll('[data-contact-action-archive-btn]');
            for (var i = 0; i < archiveButtons.length; i += 1) {
                archiveButtons[i].disabled = normalized === 'archived';
            }
        }

        function syncMessageRows() {
            var rows = modal.querySelectorAll('[data-contact-message-row]');
            for (var i = 0; i < rows.length; i += 1) {
                var row = rows[i];
                var messageId = String(row.getAttribute('data-message-id') || '');
                if (messageId === '' || !Object.prototype.hasOwnProperty.call(messagesMap, messageId)) {
                    continue;
                }
                var item = messagesMap[messageId] || {};
                applyRowStatus(row, item.status);
            }
        }

        function ensureListPlaceholder() {
            if (!listPanel) {
                return;
            }

            var tbody = listPanel.querySelector('tbody');
            if (!tbody) {
                return;
            }

            var existingPlaceholder = tbody.querySelector('[data-contact-empty-row]');
            var dataRows = tbody.querySelectorAll('[data-contact-message-row]');
            if (dataRows.length > 0) {
                if (existingPlaceholder) {
                    existingPlaceholder.remove();
                }
                return;
            }

            if (existingPlaceholder) {
                return;
            }

            var row = document.createElement('tr');
            row.setAttribute('data-contact-empty-row', '1');
            var cell = document.createElement('td');
            cell.colSpan = 6;
            cell.className = 'empty-state-cell';
            cell.textContent = listEmptyMessage !== '' ? listEmptyMessage : '-';
            row.appendChild(cell);
            tbody.appendChild(row);
        }

        function buildActionUrl(template, id) {
            if (String(template || '').trim() === '') {
                return '';
            }
            return String(template).replace('__ID__', encodeURIComponent(String(id || '')));
        }

        function postWithCsrf(url) {
            var targetUrl = String(url || '').trim();
            if (targetUrl === '' || csrfToken === '') {
                return Promise.resolve({
                    success: false,
                    payload: null,
                });
            }

            var payload = new URLSearchParams();
            payload.append('_token', csrfToken);

            return fetch(targetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: payload.toString(),
                credentials: 'same-origin',
            }).then(function (response) {
                return response.text().then(function (text) {
                    var json = null;
                    try {
                        json = JSON.parse(text);
                    } catch (error) {
                        json = null;
                    }

                    if (json && typeof json === 'object') {
                        return {
                            success: response.ok && json.success === true,
                            payload: json,
                        };
                    }

                    return {
                        success: false,
                        payload: null,
                    };
                });
            }).catch(function () {
                return {
                    success: false,
                    payload: null,
                };
            });
        }

        function archiveMessageInModal(messageId, actionUrl, button) {
            var key = String(messageId || '');
            if (key === '' || !Object.prototype.hasOwnProperty.call(messagesMap, key)) {
                return;
            }

            var row = findMessageRow(key);
            var previousStatus = normalizeStatus((messagesMap[key] || {}).status);
            if (previousStatus === 'archived') {
                return;
            }

            if (button) {
                button.disabled = true;
            }

            postWithCsrf(actionUrl).then(function (result) {
                if (!result.success) {
                    if (button) {
                        button.disabled = false;
                    }
                    return;
                }

                messagesMap[key].status = 'archived';
                applyRowStatus(row, 'archived');
                if (detailPanel && !detailPanel.hidden) {
                    var detailId = String(detailPanel.getAttribute('data-contact-detail-id') || '');
                    if (detailId === key) {
                        setText(detailStatus, statusLabel('archived'));
                    }
                }

                if (result.payload && result.payload.counts) {
                    applyServerCounts(result.payload.counts);
                } else {
                    syncCountBadges();
                }
            });
        }

        function deleteMessageInModal(messageId, actionUrl, button) {
            var key = String(messageId || '');
            if (key === '' || !Object.prototype.hasOwnProperty.call(messagesMap, key)) {
                return;
            }

            if (button) {
                button.disabled = true;
            }

            postWithCsrf(actionUrl).then(function (result) {
                if (!result.success) {
                    if (button) {
                        button.disabled = false;
                    }
                    return;
                }

                delete messagesMap[key];
                var row = findMessageRow(key);
                if (row) {
                    row.remove();
                }

                if (detailPanel && !detailPanel.hidden) {
                    var detailId = String(detailPanel.getAttribute('data-contact-detail-id') || '');
                    if (detailId === key) {
                        showList();
                    }
                }

                ensureListPlaceholder();
                if (result.payload && result.payload.counts) {
                    applyServerCounts(result.payload.counts);
                } else {
                    syncCountBadges();
                }

                if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function' && deleteSuccessMessage !== '') {
                    window.FlatCMS.toast.show(deleteSuccessMessage, 'success');
                }
            });
        }

        function openModal() {
            modal.hidden = false;
            modal.classList.add('is-open');
            showList();
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.classList.remove('is-reading');
            modal.hidden = true;
            showList();
        }

        function showList() {
            if (listPanel) {
                listPanel.hidden = false;
            }
            if (detailPanel) {
                detailPanel.hidden = true;
            }
            modal.classList.remove('is-reading');
            ensureListPlaceholder();
            syncMessageRows();
            syncCountBadges();
        }

        function setText(element, value) {
            if (!element) {
                return;
            }

            var text = String(value || '').trim();
            element.textContent = text !== '' ? text : '-';
        }

        function setEmailLink(element, value) {
            if (!element) {
                return;
            }

            clearNode(element);
            var email = String(value || '').trim();
            if (email === '' || email.indexOf('@') === -1) {
                element.textContent = '-';
                return;
            }

            var link = document.createElement('a');
            link.href = 'mailto:' + email;
            link.className = 'contact-message-email-link';
            link.textContent = email;
            element.appendChild(link);
        }

        function clearNode(node) {
            if (!node) {
                return;
            }

            while (node.firstChild) {
                node.removeChild(node.firstChild);
            }
        }

        function normalizeCustomValues(values) {
            if (Array.isArray(values)) {
                return values;
            }

            if (values && typeof values === 'object') {
                var mapped = [];
                var keys = Object.keys(values);
                for (var i = 0; i < keys.length; i += 1) {
                    var item = values[keys[i]];
                    if (!item || typeof item !== 'object') {
                        continue;
                    }
                    mapped.push(item);
                }
                return mapped;
            }

            return [];
        }

        function renderCustomValues(values) {
            if (!detailCustomValues) {
                return;
            }

            clearNode(detailCustomValues);
            var normalizedValues = normalizeCustomValues(values);
            if (!normalizedValues.length) {
                detailCustomValues.textContent = '-';
                return;
            }

            var list = document.createElement('dl');
            list.className = 'contact-detail-list';

            for (var i = 0; i < normalizedValues.length; i += 1) {
                var item = normalizedValues[i] || {};
                var label = String(item.label || item.key || '').trim();
                var value = String(item.value || '').trim();
                if (!label || !value) {
                    continue;
                }

                var term = document.createElement('dt');
                term.textContent = label;
                list.appendChild(term);

                var desc = document.createElement('dd');
                desc.textContent = value;
                list.appendChild(desc);
            }

            if (!list.childNodes.length) {
                detailCustomValues.textContent = '-';
                return;
            }

            detailCustomValues.appendChild(list);
        }

        function renderAttachments(files) {
            if (!detailAttachments) {
                return;
            }

            clearNode(detailAttachments);
            if (!Array.isArray(files) || !files.length) {
                detailAttachments.textContent = '-';
                return;
            }

            var list = document.createElement('ul');
            list.className = 'contact-attachments-list';

            for (var i = 0; i < files.length; i += 1) {
                var file = files[i] || {};
                var name = String(file.name || '').trim();
                var url = String(file.download_url || file.url || '').trim();
                if (!name) {
                    continue;
                }

                var listItem = document.createElement('li');
                if (url) {
                    var link = document.createElement('a');
                    link.href = url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.textContent = name;
                    listItem.appendChild(link);
                } else {
                    listItem.textContent = name;
                }

                list.appendChild(listItem);
            }

            if (!list.childNodes.length) {
                detailAttachments.textContent = '-';
                return;
            }

            detailAttachments.appendChild(list);
        }

        function showDetail(messageId) {
            var key = String(messageId || '');
            if (key === '' || !Object.prototype.hasOwnProperty.call(messagesMap, key)) {
                return;
            }

            var item = messagesMap[key] || {};
            setText(detailName, item.name);
            setEmailLink(detailEmail, item.email);
            setText(detailPhone, item.phone);
            setText(detailReceived, item.received);
            setText(detailSubject, item.subject);
            setText(detailStatus, statusLabel(item.status));
            setText(detailFormType, item.form_type_label);
            setText(detailSource, item.source);
            setText(detailMessage, item.message);
            renderCustomValues(item.custom_values || []);
            renderAttachments(item.attachments || []);

            if (listPanel) {
                listPanel.hidden = true;
            }
            if (detailPanel) {
                detailPanel.hidden = false;
            }
            modal.classList.add('is-reading');
            if (detailPanel) {
                detailPanel.setAttribute('data-contact-detail-id', key);
            }

            if (canManageStatusUpdate && normalizeStatus(item.status) === 'new') {
                item.status = 'read';
                var row = findMessageRow(key);
                applyRowStatus(row, item.status);
                setText(detailStatus, statusLabel(item.status));
                syncCountBadges();
                postWithCsrf(buildActionUrl(readUrlTemplate, key)).then(function (result) {
                    if (result && result.success && result.payload && result.payload.counts) {
                        applyServerCounts(result.payload.counts);
                    }
                });
            }
        }

        function currentLocationUrl() {
            try {
                return new URL(window.location.href);
            } catch (error) {
                return null;
            }
        }

        function openViaFreshReload() {
            var url = currentLocationUrl();
            if (!url) {
                openModal();
                return;
            }

            url.searchParams.set('contact_messages', '1');
            window.location.assign(url.toString());
        }

        function consumeAutoOpenFlag() {
            var url = currentLocationUrl();
            if (!url) {
                return false;
            }

            if (url.searchParams.get('contact_messages') !== '1') {
                return false;
            }

            url.searchParams.delete('contact_messages');
            if (window.history && typeof window.history.replaceState === 'function') {
                window.history.replaceState({}, '', url.pathname + (url.search ? url.search : '') + url.hash);
            }

            return true;
        }

        for (var i = 0; i < openButtons.length; i += 1) {
            openButtons[i].addEventListener('click', function (event) {
                event.preventDefault();
                openViaFreshReload();
            });
        }

        for (var j = 0; j < closeButtons.length; j += 1) {
            closeButtons[j].addEventListener('click', function (event) {
                var button = event.currentTarget;
                var isBackdrop = button && button.classList && button.classList.contains('contact-messages-modal__backdrop');
                if (modal.classList.contains('is-reading') && !isBackdrop) {
                    showList();
                    return;
                }
                closeModal();
            });
        }

        for (var k = 0; k < detailButtons.length; k += 1) {
            detailButtons[k].addEventListener('click', function (event) {
                var button = event.currentTarget;
                var messageId = button.getAttribute('data-message-id') || '';
                showDetail(messageId);
            });
        }

        for (var q = 0; q < archiveForms.length; q += 1) {
            archiveForms[q].addEventListener('submit', function (event) {
                event.preventDefault();
                var formNode = event.currentTarget;
                var messageId = String(formNode.getAttribute('data-message-id') || '');
                var actionUrl = String(formNode.getAttribute('action') || '').trim();
                var actionButton = formNode.querySelector('[data-contact-action-archive-btn]');
                archiveMessageInModal(messageId, actionUrl, actionButton);
            });
        }

        for (var x = 0; x < deleteForms.length; x += 1) {
            deleteForms[x].addEventListener('submit', function (event) {
                event.preventDefault();
                var formNode = event.currentTarget;
                var messageId = String(formNode.getAttribute('data-message-id') || '');
                var actionUrl = String(formNode.getAttribute('action') || '').trim();
                var actionButton = formNode.querySelector('[data-contact-delete-btn]');
                var itemName = actionButton ? String(actionButton.getAttribute('data-item-name') || '').trim() : '';
                var confirmMessage = deleteConfirmMessage;

                if (confirmMessage === '') {
                    deleteMessageInModal(messageId, actionUrl, actionButton);
                    return;
                }

                if (window.FlatCMS && window.FlatCMS.modal && typeof window.FlatCMS.modal.confirm === 'function') {
                    window.FlatCMS.modal.confirm(confirmMessage, function () {
                        deleteMessageInModal(messageId, actionUrl, actionButton);
                    }, { itemName: itemName });
                    return;
                }

                if (window.confirm(confirmMessage)) {
                    deleteMessageInModal(messageId, actionUrl, actionButton);
                }
            });
        }

        if (backButton) {
            backButton.addEventListener('click', showList);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) {
                if (modal.classList.contains('is-reading')) {
                    showList();
                    return;
                }
                closeModal();
            }
        });

        ensureListPlaceholder();
        syncMessageRows();
        syncCountBadges();

        if (consumeAutoOpenFlag()) {
            openModal();
        }
    }

    function initShortcodeCopy() {
        var copyButtons = document.querySelectorAll('[data-contact-copy-shortcode]');
        if (!copyButtons.length) {
            return;
        }

        var activePopover = null;
        var popoverTimer = 0;

        function fallbackCopy(text) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.pointerEvents = 'none';
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);

            var success = false;
            try {
                success = document.execCommand('copy');
            } catch (error) {
                success = false;
            }

            textarea.remove();
            return success;
        }

        function writeClipboard(text) {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                return navigator.clipboard.writeText(text);
            }

            return new Promise(function (resolve, reject) {
                if (fallbackCopy(text)) {
                    resolve();
                    return;
                }
                reject(new Error('copy_failed'));
            });
        }

        function resolveCopyValue(button) {
            var explicit = String(button.getAttribute('data-copy-text') || '').trim();
            if (explicit !== '') {
                return explicit;
            }

            return String(button.textContent || '').trim();
        }

        function clearPopover() {
            if (popoverTimer) {
                window.clearTimeout(popoverTimer);
                popoverTimer = 0;
            }

            if (activePopover) {
                activePopover.remove();
                activePopover = null;
            }
        }

        function showPopover(button, message) {
            if (String(message || '').trim() === '') {
                return;
            }

            clearPopover();

            var popover = document.createElement('span');
            popover.className = 'contact-inline-popover';
            popover.textContent = message;
            document.body.appendChild(popover);

            var buttonRect = button.getBoundingClientRect();
            var popoverRect = popover.getBoundingClientRect();
            var horizontalPadding = 12;
            var viewportWidth = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);

            var top = buttonRect.top - popoverRect.height - 12;
            var left = buttonRect.left + (buttonRect.width / 2) - (popoverRect.width / 2);

            if (left < horizontalPadding) {
                left = horizontalPadding;
            } else if ((left + popoverRect.width) > (viewportWidth - horizontalPadding)) {
                left = viewportWidth - popoverRect.width - horizontalPadding;
            }

            popover.style.top = Math.max(8, top) + 'px';
            popover.style.left = Math.max(8, left) + 'px';

            window.requestAnimationFrame(function () {
                popover.classList.add('is-visible');
            });

            activePopover = popover;
            popoverTimer = window.setTimeout(function () {
                if (!activePopover) {
                    return;
                }
                activePopover.classList.remove('is-visible');
                window.setTimeout(clearPopover, 180);
            }, 1650);
        }

        function pulseCopied(button) {
            button.classList.add('is-copied');
            window.setTimeout(function () {
                button.classList.remove('is-copied');
            }, 900);
        }

        for (var i = 0; i < copyButtons.length; i += 1) {
            copyButtons[i].addEventListener('click', function (event) {
                event.preventDefault();

                var button = event.currentTarget;
                var text = resolveCopyValue(button);
                if (text === '') {
                    return;
                }

                var defaultLabel = String(button.getAttribute('data-label-default') || '').trim() || String(button.textContent || '').trim();
                var copiedLabel = String(button.getAttribute('data-label-copied') || '').trim() || defaultLabel;
                var popoverMessage = String(button.getAttribute('data-popover-message') || '').trim() || copiedLabel;

                writeClipboard(text).then(function () {
                    pulseCopied(button);
                    showPopover(button, popoverMessage);
                }).catch(function () {
                    // noop
                });
            });
        }

        window.addEventListener('scroll', clearPopover, { passive: true });
        window.addEventListener('resize', clearPopover);
    }

    function initFormBuilder() {
        var form = document.querySelector('[data-contact-field-builder]');
        if (!form) {
            return;
        }

        function slugifyFormSlug(value) {
            return String(value || '')
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s_]+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function initFormSlugSync() {
            var nameInput = form.querySelector('#contactFormName');
            var slugInput = form.querySelector('#contactFormSlug');
            if (!(nameInput instanceof HTMLInputElement) || !(slugInput instanceof HTMLInputElement)) {
                return;
            }

            var autoEnabled = String(slugInput.value || '').trim() === '';
            slugInput.setAttribute('data-contact-form-slug-auto', autoEnabled ? '1' : '0');

            function applyAutoSlug() {
                if (!autoEnabled) {
                    return;
                }
                slugInput.value = slugifyFormSlug(nameInput.value);
            }

            nameInput.addEventListener('input', applyAutoSlug);
            nameInput.addEventListener('change', applyAutoSlug);

            slugInput.addEventListener('input', function () {
                autoEnabled = String(slugInput.value || '').trim() === '';
                slugInput.setAttribute('data-contact-form-slug-auto', autoEnabled ? '1' : '0');
            });
        }

        initFormSlugSync();

        var rowsContainer = form.querySelector('[data-contact-custom-fields]');
        var rowTemplate = document.getElementById('contactCustomFieldTemplate');
        var canvas = form.querySelector('[data-contact-builder-canvas]');
        var inspector = form.querySelector('[data-contact-field-inspector]');
        var addButton = form.querySelector('[data-contact-add-field]');
        var attachmentsToggle = form.querySelector('[data-contact-attachments-enabled]');
        var attachmentsConfig = form.querySelector('[data-contact-attachments-config]');
        var formTypeSelect = form.querySelector('[data-contact-form-type]');
        var submitLabelInput = form.querySelector('#contactFormSubmitLabel');
        var successMessageInput = form.querySelector('#contactFormSuccessMessage');
        var newsletterLegalGroup = form.querySelector('[data-contact-newsletter-legal-group]');
        var newsletterPrivacyGroup = form.querySelector('[data-contact-newsletter-privacy-group]');
        var translationModal = form.querySelector('[data-contact-translations-modal]');
        var translationOpenButtons = form.querySelectorAll('[data-contact-translation-open]');
        var translationTabs = translationModal ? translationModal.querySelectorAll('[data-contact-translation-tab]') : [];
        var translationPanels = translationModal ? translationModal.querySelectorAll('[data-contact-translation-panel]') : [];
        var sourceTranslationFieldsContainer = translationModal ? translationModal.querySelector('[data-contact-source-translation-fields]') : null;
        var sourceModalFields = translationModal ? translationModal.querySelectorAll('[data-contact-source-locale-field]') : [];
        var translationModalTitle = translationModal ? translationModal.querySelector('[data-contact-translation-modal-title]') : null;
        var translationTablist = translationModal ? translationModal.querySelector('[data-contact-translation-tablist]') : null;
        var translationFooterInfo = translationModal ? translationModal.querySelector('[data-contact-translation-footer-info]') : null;
        var translationCloseIcon = translationModal ? translationModal.querySelector('[data-contact-translation-close-icon]') : null;
        var translationCloseButton = translationModal ? translationModal.querySelector('[data-contact-translation-close-btn]') : null;
        var translationSaveButton = translationModal ? translationModal.querySelector('[data-contact-translation-save-btn]') : null;
        var optionsModal = form.querySelector('[data-contact-options-modal]');
        var optionsModalList = optionsModal ? optionsModal.querySelector('[data-contact-options-modal-list]') : null;
        var optionsModalTitle = optionsModal ? optionsModal.querySelector('[data-contact-options-modal-title]') : null;
        var optionsModalField = optionsModal ? optionsModal.querySelector('[data-contact-options-modal-field]') : null;
        var optionsModalSaveButton = optionsModal ? optionsModal.querySelector('[data-contact-options-save]') : null;
        var optionsModalAddButton = optionsModal ? optionsModal.querySelector('[data-contact-options-add]') : null;
        var optionsModalCloseButtons = optionsModal ? optionsModal.querySelectorAll('[data-contact-options-close]') : [];
        var optionsModalTemplate = document.getElementById('contactOptionModalItemTemplate');
        var activeLocaleInput = form.querySelector('[data-contact-translation-active-locale]');
        var sourceLocaleInput = form.querySelector('input[name="source_locale"]');
        var sourceLocale = sourceLocaleInput ? String(sourceLocaleInput.value || '').trim() : '';

        if (!rowsContainer || !rowTemplate || !canvas) {
            return;
        }

        var inspectorFields = inspector ? inspector.querySelector('[data-contact-inspector-fields]') : null;
        var inspectorEmpty = inspector ? inspector.querySelector('[data-contact-inspector-empty]') : null;
        var inspectorTitle = inspector ? inspector.querySelector('[data-contact-inspector-title]') : null;
        var inspectorOptionsGroup = inspector ? inspector.querySelector('[data-contact-inspector-options-group]') : null;
        var inspectorActions = inspector ? inspector.querySelector('[data-contact-inspector-actions]') : null;
        var inspectorCloseButtons = inspector ? inspector.querySelectorAll('[data-contact-inspector-close]') : [];
        var inspectorDuplicateButton = inspector ? inspector.querySelector('[data-contact-duplicate-field]') : null;
        var inspectorDeleteButton = inspector ? inspector.querySelector('[data-contact-delete-field]') : null;
        var inspectorInputs = {
            label: inspector ? inspector.querySelector('[data-contact-inspector-input="label"]') : null,
            key: inspector ? inspector.querySelector('[data-contact-inspector-input="key"]') : null,
            type: inspector ? inspector.querySelector('[data-contact-inspector-input="type"]') : null,
            width: inspector ? inspector.querySelector('[data-contact-inspector-input="width"]') : null,
            required: inspector ? inspector.querySelector('[data-contact-inspector-input="required"]') : null,
            placeholder: inspector ? inspector.querySelector('[data-contact-inspector-input="placeholder"]') : null,
            help: inspector ? inspector.querySelector('[data-contact-inspector-input="help"]') : null,
            options: inspector ? inspector.querySelector('[data-contact-inspector-input="options"]') : null,
        };
        var inspectorOptionsModalButton = inspector ? inspector.querySelector('[data-contact-open-options-modal]') : null;

        var hasInspector = !!(inspector && inspectorFields && inspectorEmpty && inspectorTitle);
        var state = {
            activeIndex: -1,
        };
        var optionsModalState = {
            fieldIndex: -1,
        };

        function getLabel(attribute, fallback) {
            var value = form.getAttribute(attribute);
            if (typeof value === 'string' && value.trim() !== '') {
                return value.trim();
            }
            return fallback;
        }

        var labels = {
            emptyTitle: getLabel('data-builder-empty-title', ''),
            emptyHelp: getLabel('data-builder-empty-help', ''),
            unnamedField: getLabel('data-builder-unnamed-field', ''),
            fieldPrefix: getLabel('data-builder-field-prefix', ''),
            inspectorTitle: getLabel('data-builder-inspector-title', ''),
            inspectorNone: getLabel('data-builder-inspector-none', ''),
            actionEdit: getLabel('data-builder-action-edit', ''),
            actionDuplicate: getLabel('data-builder-action-duplicate', ''),
            actionDelete: getLabel('data-builder-action-delete', ''),
            actionMoveUp: getLabel('data-builder-action-move-up', ''),
            actionMoveDown: getLabel('data-builder-action-move-down', ''),
            deleteConfirm: getLabel('data-builder-delete-confirm', ''),
            optionsManage: getLabel('data-builder-options-manage', ''),
            selectPlaceholder: getLabel('data-builder-select-placeholder', ''),
            optionSampleOne: getLabel('data-builder-option-sample-one', ''),
            optionSampleTwo: getLabel('data-builder-option-sample-two', ''),
            presetConfirm: getLabel('data-form-preset-confirm', ''),
            presetConfirmText: getLabel('data-form-preset-confirm-text', ''),
        };

        function parseJsonAttribute(attributeName) {
            var raw = form.getAttribute(attributeName);
            if (typeof raw !== 'string' || raw.trim() === '') {
                return {};
            }

            try {
                var parsed = JSON.parse(raw);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        }

        var formTypePresets = parseJsonAttribute('data-form-type-presets');

        function parseNodeJson(node, attributeName) {
            if (!node) {
                return {};
            }

            var rawValue = String(node.getAttribute(attributeName) || '').trim();
            if (rawValue === '') {
                return {};
            }

            try {
                var parsed = JSON.parse(rawValue);
                return parsed && typeof parsed === 'object' ? parsed : {};
            } catch (error) {
                return {};
            }
        }

        function slugify(value) {
            return String(value || '')
                .toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '')
                .replace(/[\s-]+/g, '_')
                .replace(/^_+|_+$/g, '');
        }

        function isAutoKeyEnabled(input) {
            if (!input) {
                return false;
            }
            return String(input.getAttribute('data-contact-key-auto') || '') === '1';
        }

        function setAutoKeyEnabled(input, enabled) {
            if (!input) {
                return;
            }
            input.setAttribute('data-contact-key-auto', enabled ? '1' : '0');
        }

        function shouldAutoFillKey(input) {
            if (!input) {
                return false;
            }
            var value = String(input.value || '').trim();
            return value === '' || isAutoKeyEnabled(input);
        }

        function syncAutoKeyState(source, target) {
            if (!target) {
                return;
            }
            setAutoKeyEnabled(target, isAutoKeyEnabled(source));
        }

        function parseOptions(value) {
            if (Array.isArray(value)) {
                return value
                    .map(function (item) { return String(item || '').trim(); })
                    .filter(function (item) { return item !== ''; });
            }

            return String(value || '')
                .split(/\r\n|\r|\n|,|;/)
                .map(function (item) { return String(item || '').trim(); })
                .filter(function (item) { return item !== ''; });
        }

        function summarizeOptions(value) {
            return parseOptions(value).join(', ');
        }

        function isChoiceFieldType(type) {
            var normalized = String(type || '').trim().toLowerCase();
            return normalized === 'select' || normalized === 'radio' || normalized === 'checkbox';
        }

        function getRows() {
            return Array.prototype.slice.call(rowsContainer.querySelectorAll('[data-contact-custom-field-row]'));
        }

        function normalizePresetField(rawField) {
            var field = rawField && typeof rawField === 'object' ? rawField : {};
            var type = String(field.type || 'text').trim().toLowerCase();
            var width = String(field.width || 'full').trim().toLowerCase();
            var options = Array.isArray(field.options) ? field.options : [];

            if (width !== 'half') {
                width = 'full';
            }

            return {
                key: String(field.key || '').trim(),
                label: String(field.label || '').trim(),
                type: type === '' ? 'text' : type,
                required: field.required === true,
                width: width,
                placeholder: String(field.placeholder || '').trim(),
                help: String(field.help || '').trim(),
                options: options.map(function (item) {
                    return String(item || '').trim();
                }).filter(function (item) {
                    return item !== '';
                }),
            };
        }

        function createRowFromData(data) {
            var rowsCount = getRows().length;
            var html = rowTemplate.innerHTML.replace(/__INDEX__/g, String(rowsCount));
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            var row = wrapper.firstElementChild;
            if (!row) {
                return null;
            }

            clearRow(row);
            var refs = getRowRefs(row);
            if (refs) {
                if (refs.label) {
                    refs.label.value = data.label;
                }
                if (refs.key) {
                    refs.key.value = data.key;
                    setAutoKeyEnabled(refs.key, data.key === '');
                }
                if (refs.type) {
                    refs.type.value = data.type;
                }
                if (refs.required) {
                    refs.required.checked = !!data.required;
                }
                if (refs.width) {
                    refs.width.value = data.width;
                }
                if (refs.placeholder) {
                    refs.placeholder.value = data.placeholder;
                }
                if (refs.help) {
                    refs.help.value = data.help;
                }
                if (refs.options) {
                    refs.options.value = data.options.join('\n');
                }
            }

            updateOptionsVisibility(row);
            rowsContainer.appendChild(row);
            return row;
        }

        function applyPresetByType(type, forceTextValues) {
            var typeKey = String(type || '').trim();
            if (!Object.prototype.hasOwnProperty.call(formTypePresets, typeKey)) {
                return;
            }

            var preset = formTypePresets[typeKey] && typeof formTypePresets[typeKey] === 'object' ? formTypePresets[typeKey] : {};
            var fields = Array.isArray(preset.custom_fields) ? preset.custom_fields : [];

            rowsContainer.innerHTML = '';

            for (var i = 0; i < fields.length; i += 1) {
                var normalizedField = normalizePresetField(fields[i]);
                createRowFromData(normalizedField);
            }

            reindexRows();

            if (submitLabelInput) {
                if (forceTextValues || String(submitLabelInput.value || '').trim() === '') {
                    submitLabelInput.value = String(preset.submit_label || '').trim();
                }
            }

            if (successMessageInput) {
                if (forceTextValues || String(successMessageInput.value || '').trim() === '') {
                    successMessageInput.value = String(preset.success_message || '').trim();
                }
            }

            if (getRows().length > 0) {
                setActiveField(0, false);
            } else {
                setActiveField(-1, false);
            }

            renderCanvas();
            syncInspectorFromActiveRow();
        }

        function toggleNewsletterOptionsByType() {
            var selectedType = formTypeSelect ? String(formTypeSelect.value || '').trim() : '';
            var isNewsletter = selectedType === 'newsletter_rgpd';

            if (newsletterLegalGroup) {
                newsletterLegalGroup.classList.toggle('is-hidden', !isNewsletter);
            }
            if (newsletterPrivacyGroup) {
                newsletterPrivacyGroup.classList.toggle('is-hidden', !isNewsletter);
            }
        }

        function getRowRefs(row) {
            if (!row) {
                return null;
            }

            return {
                label: row.querySelector('[data-contact-field-label]'),
                key: row.querySelector('[data-contact-field-key]'),
                type: row.querySelector('[data-contact-field-type]'),
                required: row.querySelector('input[name$="[required]"]'),
                width: row.querySelector('select[name$="[width]"]'),
                placeholder: row.querySelector('input[name$="[placeholder]"]'),
                help: row.querySelector('input[name$="[help]"]'),
                options: row.querySelector('textarea[name$="[options]"]'),
            };
        }

        function updateOptionsVisibility(row) {
            var refs = getRowRefs(row);
            if (!refs || !refs.type) {
                return;
            }

            var optionsGroup = row.querySelector('[data-contact-field-options-group]');
            if (!optionsGroup) {
                return;
            }

            var show = refs.type.value === 'select' || refs.type.value === 'radio' || refs.type.value === 'checkbox';
            optionsGroup.classList.toggle('is-hidden', !show);
        }

        function clearRow(row) {
            var inputs = row.querySelectorAll('input[type="text"], textarea');
            for (var i = 0; i < inputs.length; i += 1) {
                inputs[i].value = '';
            }

            var selects = row.querySelectorAll('select');
            for (var j = 0; j < selects.length; j += 1) {
                var defaultOption = selects[j].querySelector('[data-contact-field-default]');
                if (defaultOption) {
                    selects[j].value = defaultOption.value;
                    continue;
                }

                if (selects[j].options.length > 0) {
                    selects[j].selectedIndex = 0;
                }
            }

            var checkboxes = row.querySelectorAll('input[type="checkbox"]');
            for (var k = 0; k < checkboxes.length; k += 1) {
                checkboxes[k].checked = false;
            }

            var keyInput = row.querySelector('[data-contact-field-key]');
            if (keyInput) {
                setAutoKeyEnabled(keyInput, true);
            }

            updateOptionsVisibility(row);
        }

        function reindexRows() {
            var rows = getRows();
            for (var i = 0; i < rows.length; i += 1) {
                var fields = rows[i].querySelectorAll('input[name], textarea[name], select[name]');
                for (var j = 0; j < fields.length; j += 1) {
                    var current = fields[j].getAttribute('name') || '';
                    fields[j].setAttribute('name', current.replace(/custom_fields\[(\d+|__INDEX__)\]/, 'custom_fields[' + i + ']'));
                }
                updateOptionsVisibility(rows[i]);
            }
        }

        function rowData(row, index) {
            var refs = getRowRefs(row);
            var labelValue = refs && refs.label ? String(refs.label.value || '') : '';
            var keyValue = refs && refs.key ? String(refs.key.value || '').trim() : '';
            var placeholderValue = refs && refs.placeholder ? String(refs.placeholder.value || '') : '';
            var helpValue = refs && refs.help ? String(refs.help.value || '') : '';
            var data = {
                index: index,
                label: labelValue,
                key: keyValue,
                type: refs && refs.type ? String(refs.type.value || 'text').trim() : 'text',
                required: refs && refs.required ? !!refs.required.checked : false,
                width: refs && refs.width ? String(refs.width.value || 'full').trim() : 'full',
                placeholder: placeholderValue,
                help: helpValue,
                options: refs && refs.options ? parseOptions(refs.options.value) : [],
            };

            if (data.width !== 'half') {
                data.width = 'full';
            }

            return data;
        }

        function translationPanelNode(localeCode) {
            if (!translationModal) {
                return null;
            }

            return translationModal.querySelector('[data-contact-translation-panel="' + localeCode + '"]');
        }

        function modalLocaleUiLabels(localeCode) {
            return parseNodeJson(translationPanelNode(localeCode), 'data-contact-translation-ui');
        }

        function applyTranslationModalUi(localeCode) {
            var labelsMap = modalLocaleUiLabels(localeCode);
            if (!labelsMap || typeof labelsMap !== 'object') {
                return;
            }

            if (translationModalTitle && labelsMap.translations) {
                translationModalTitle.textContent = String(labelsMap.translations);
            }

            if (translationTablist && labelsMap.translations) {
                translationTablist.setAttribute('aria-label', String(labelsMap.translations));
            }

            if (translationFooterInfo && labelsMap.contact_form_translations_help) {
                translationFooterInfo.textContent = String(labelsMap.contact_form_translations_help);
            }

            if (translationCloseIcon && labelsMap.close) {
                translationCloseIcon.setAttribute('aria-label', String(labelsMap.close));
            }

            if (translationCloseButton && labelsMap.close) {
                translationCloseButton.textContent = String(labelsMap.close);
            }

            if (translationSaveButton && labelsMap.save) {
                translationSaveButton.textContent = String(labelsMap.save);
            }
        }

        function getSourceModalField(fieldName) {
            if (!translationModal || !fieldName) {
                return null;
            }

            return translationModal.querySelector('[data-contact-source-locale-field="' + fieldName + '"]');
        }

        function fieldTypeLabel(type, labelsMap) {
            var normalized = String(type || '').trim().toLowerCase();
            if (normalized === '') {
                return '';
            }

            var translationKey = 'contact_form_custom_type_' + normalized;
            var translated = labelsMap && typeof labelsMap[translationKey] === 'string' ? String(labelsMap[translationKey]).trim() : '';
            if (translated !== '') {
                return translated;
            }

            return normalized;
        }

        function syncMainTextFieldsToSourceModal() {
            var submitMirror = getSourceModalField('submit_label');
            var successMirror = getSourceModalField('success_message');

            if (submitMirror && submitLabelInput) {
                submitMirror.value = String(submitLabelInput.value || '');
            }

            if (successMirror && successMessageInput) {
                successMirror.value = String(successMessageInput.value || '');
            }
        }

        function syncSourceModalFieldToMain(fieldName, value) {
            var normalizedField = String(fieldName || '').trim();
            if (normalizedField === 'submit_label' && submitLabelInput) {
                submitLabelInput.value = String(value || '');
                return;
            }

            if (normalizedField === 'success_message' && successMessageInput) {
                successMessageInput.value = String(value || '');
            }
        }

        function buildSourceTranslationPanel() {
            if (!sourceTranslationFieldsContainer) {
                return;
            }

            sourceTranslationFieldsContainer.innerHTML = '';
            var sourceLabels = modalLocaleUiLabels(sourceLocale);
            var rows = getRows();

            function bindRowFieldInput(control, rowIndex, fieldName) {
                control.addEventListener('input', function () {
                    var nextRows = getRows();
                    var nextRow = nextRows[rowIndex] || null;
                    var nextRefs = getRowRefs(nextRow);
                    if (!nextRefs) {
                        return;
                    }

                    var nextValue = String(control.value || '');
                    if (fieldName === 'label' && nextRefs.label) {
                        nextRefs.label.value = nextValue;
                    } else if (fieldName === 'placeholder' && nextRefs.placeholder) {
                        nextRefs.placeholder.value = nextValue;
                    } else if (fieldName === 'help' && nextRefs.help) {
                        nextRefs.help.value = nextValue;
                    } else if (fieldName === 'options' && nextRefs.options) {
                        nextRefs.options.value = nextValue;
                    }

                    if (nextRow) {
                        updateOptionsVisibility(nextRow);
                    }
                    syncInspectorFromActiveRow();
                    renderCanvas();
                });
            }

            function appendBoundField(gridNode, rowIndex, fieldName, controlType, currentValue, labelText, placeholderValue) {
                var group = document.createElement('div');
                group.className = 'form-group';

                var fieldId = 'contactSourceTranslation' + fieldName.charAt(0).toUpperCase() + fieldName.slice(1) + rowIndex;
                var fieldLabel = document.createElement('label');
                fieldLabel.className = 'form-label';
                fieldLabel.setAttribute('for', fieldId);
                fieldLabel.textContent = labelText;
                group.appendChild(fieldLabel);

                var control = controlType === 'textarea'
                    ? document.createElement('textarea')
                    : document.createElement('input');

                control.id = fieldId;
                control.className = 'form-input';

                if (controlType === 'textarea') {
                    control.rows = 3;
                    control.value = String(currentValue || '');
                    if (String(placeholderValue || '').trim() !== '') {
                        control.setAttribute('placeholder', String(placeholderValue));
                    }
                } else {
                    control.type = 'text';
                    control.value = String(currentValue || '');
                    if (String(placeholderValue || '').trim() !== '') {
                        control.setAttribute('placeholder', String(placeholderValue));
                    }
                }

                bindRowFieldInput(control, rowIndex, fieldName);
                group.appendChild(control);
                gridNode.appendChild(group);
            }

            for (var rowIndex = 0; rowIndex < rows.length; rowIndex += 1) {
                var row = rows[rowIndex];
                var refs = getRowRefs(row);
                if (!refs) {
                    continue;
                }

                var data = rowData(row, rowIndex);
                var card = document.createElement('div');
                card.className = 'contact-form-translation-field-card';

                var head = document.createElement('div');
                head.className = 'contact-form-translation-field-head';

                var title = document.createElement('strong');
                var sourceLabel = String(data.label || '').trim();
                var unnamedFieldLabel = String(sourceLabels.contact_form_builder_unnamed_field || labels.unnamedField || '').trim();
                title.textContent = sourceLabel !== '' ? sourceLabel : (unnamedFieldLabel + ' #' + (rowIndex + 1));
                head.appendChild(title);

                var typeBadge = document.createElement('span');
                typeBadge.className = 'contact-form-translation-field-type';
                typeBadge.textContent = fieldTypeLabel(data.type, sourceLabels);
                head.appendChild(typeBadge);
                card.appendChild(head);

                var grid = document.createElement('div');
                grid.className = 'contact-custom-field-grid contact-custom-field-grid--translation';

                appendBoundField(
                    grid,
                    rowIndex,
                    'label',
                    'input',
                    refs.label ? refs.label.value : '',
                    String(sourceLabels.contact_form_custom_label || ''),
                    ''
                );
                appendBoundField(
                    grid,
                    rowIndex,
                    'placeholder',
                    'input',
                    refs.placeholder ? refs.placeholder.value : '',
                    String(sourceLabels.contact_form_custom_placeholder || ''),
                    ''
                );
                appendBoundField(
                    grid,
                    rowIndex,
                    'help',
                    'input',
                    refs.help ? refs.help.value : '',
                    String(sourceLabels.contact_form_custom_help || ''),
                    ''
                );

                if (data.type === 'select' || data.type === 'radio' || data.type === 'checkbox') {
                    appendBoundField(
                        grid,
                        rowIndex,
                        'options',
                        'textarea',
                        refs.options ? refs.options.value : '',
                        String(sourceLabels.contact_form_custom_options_label || ''),
                        String(sourceLabels.contact_form_custom_options_placeholder || '')
                    );
                }

                card.appendChild(grid);
                sourceTranslationFieldsContainer.appendChild(card);
            }
        }

        function syncSourceModalState() {
            syncMainTextFieldsToSourceModal();
            buildSourceTranslationPanel();
        }

        function getActiveRow() {
            var rows = getRows();
            if (state.activeIndex < 0 || state.activeIndex >= rows.length) {
                return null;
            }

            return rows[state.activeIndex];
        }

        function closeOptionsModal() {
            if (!optionsModal) {
                return;
            }

            optionsModal.style.display = 'none';
            optionsModal.setAttribute('aria-hidden', 'true');
            optionsModalState.fieldIndex = -1;
            updateBodyOverflow();
        }

        function createOptionsModalItem(value) {
            if (!optionsModalTemplate) {
                return null;
            }

            var wrapper = document.createElement('div');
            wrapper.innerHTML = optionsModalTemplate.innerHTML;
            var item = wrapper.firstElementChild;
            if (!item) {
                return null;
            }

            var input = item.querySelector('[data-contact-options-input]');
            if (input) {
                input.value = String(value || '');
            }

            return item;
        }

        function getOptionsModalValues() {
            if (!optionsModalList) {
                return [];
            }

            return Array.prototype.slice.call(optionsModalList.querySelectorAll('[data-contact-options-input]'))
                .map(function (input) {
                    return String(input.value || '').trim();
                })
                .filter(function (value) {
                    return value !== '';
                });
        }

        function appendOptionsModalItem(value, shouldFocus) {
            if (!optionsModalList) {
                return;
            }

            var item = createOptionsModalItem(value);
            if (!item) {
                return;
            }

            optionsModalList.appendChild(item);

            if (shouldFocus) {
                var input = item.querySelector('[data-contact-options-input]');
                if (input && typeof input.focus === 'function') {
                    window.requestAnimationFrame(function () {
                        input.focus();
                    });
                }
            }
        }

        function populateOptionsModal(values) {
            if (!optionsModalList) {
                return;
            }

            optionsModalList.innerHTML = '';
            var normalizedValues = Array.isArray(values) ? values : [];

            if (!normalizedValues.length) {
                appendOptionsModalItem('', true);
                return;
            }

            for (var index = 0; index < normalizedValues.length; index += 1) {
                appendOptionsModalItem(normalizedValues[index], index === 0);
            }
        }

        function openOptionsModal(fieldIndex) {
            if (!optionsModal) {
                return;
            }

            var rows = getRows();
            if (fieldIndex < 0 || fieldIndex >= rows.length) {
                return;
            }

            var row = rows[fieldIndex];
            var data = rowData(row, fieldIndex);
            if (!isChoiceFieldType(data.type)) {
                return;
            }

            Array.prototype.slice.call(document.querySelectorAll('.modal-overlay')).forEach(function (otherModal) {
                if (otherModal === optionsModal) {
                    return;
                }
                otherModal.style.display = 'none';
                otherModal.setAttribute('aria-hidden', 'true');
            });

            optionsModalState.fieldIndex = fieldIndex;
            setActiveField(fieldIndex, false);

            if (hasInspector) {
                setInspectorOpen(false);
            }

            populateOptionsModal(data.options);

            if (optionsModalTitle) {
                optionsModalTitle.textContent = labels.optionsManage || '';
            }

            if (optionsModalField) {
                var computedLabel = String(data.label || '').trim();
                optionsModalField.textContent = computedLabel !== '' ? computedLabel : (labels.unnamedField + ' #' + (fieldIndex + 1));
            }

            optionsModal.classList.remove('is-initially-hidden');
            optionsModal.style.display = 'flex';
            optionsModal.setAttribute('aria-hidden', 'false');
            updateBodyOverflow();
        }

        function saveOptionsModal() {
            var fieldIndex = optionsModalState.fieldIndex;
            var rows = getRows();
            if (fieldIndex < 0 || fieldIndex >= rows.length) {
                closeOptionsModal();
                return;
            }

            var row = rows[fieldIndex];
            var refs = getRowRefs(row);
            if (!refs || !refs.options) {
                closeOptionsModal();
                return;
            }

            refs.options.value = getOptionsModalValues().join('\n');
            updateOptionsVisibility(row);

            if (state.activeIndex === fieldIndex && inspectorInputs.options) {
                inspectorInputs.options.value = summarizeOptions(refs.options.value);
            }

            syncInspectorFromActiveRow();
            renderCanvas();
            closeOptionsModal();
        }

        function toggleInspectorOptions(type) {
            if (!inspectorOptionsGroup) {
                return;
            }

            var show = isChoiceFieldType(type);
            inspectorOptionsGroup.classList.toggle('is-hidden', !show);
        }

        function setInspectorOpen(open) {
            if (!hasInspector) {
                return;
            }

            inspector.classList.toggle('is-open', open);
            inspector.setAttribute('aria-hidden', open ? 'false' : 'true');
            document.body.classList.toggle('contact-builder-inspector-open', open);
        }

        function syncInspectorVisibility(row) {
            if (!hasInspector) {
                return;
            }

            var hasRow = !!row;
            inspectorEmpty.hidden = hasRow;
            inspectorFields.hidden = !hasRow;
            if (inspectorActions) {
                inspectorActions.hidden = !hasRow;
            }
        }

        function syncInspectorFromActiveRow() {
            if (!hasInspector) {
                return;
            }

            var row = getActiveRow();
            syncInspectorVisibility(row);

            if (!row) {
                inspectorTitle.textContent = labels.inspectorTitle;
                inspectorEmpty.textContent = labels.inspectorNone;
                return;
            }

            var data = rowData(row, state.activeIndex);
            var refs = getRowRefs(row);
            if (inspectorInputs.label) { inspectorInputs.label.value = data.label; }
            if (inspectorInputs.key) { inspectorInputs.key.value = data.key; }
            if (inspectorInputs.type) { inspectorInputs.type.value = data.type; }
            if (inspectorInputs.width) { inspectorInputs.width.value = data.width; }
            if (inspectorInputs.required) { inspectorInputs.required.checked = data.required; }
            if (inspectorInputs.placeholder) { inspectorInputs.placeholder.value = data.placeholder; }
            if (inspectorInputs.help) { inspectorInputs.help.value = data.help; }
            if (inspectorInputs.options) { inspectorInputs.options.value = summarizeOptions(data.options); }
            if (refs && refs.key && inspectorInputs.key) {
                syncAutoKeyState(refs.key, inspectorInputs.key);
            }

            toggleInspectorOptions(data.type);
            var computedLabel = String(data.label || '').trim();
            computedLabel = computedLabel !== '' ? computedLabel : (labels.unnamedField + ' #' + (data.index + 1));
            inspectorTitle.textContent = labels.inspectorTitle + ' · ' + computedLabel;
        }

        function createActionButton(action, title, iconClass, extraClass) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'contact-builder-item-action' + (extraClass ? ' ' + extraClass : '');
            button.setAttribute('data-contact-builder-action', action);
            button.setAttribute('title', title);
            button.setAttribute('aria-label', title);

            var icon = document.createElement('i');
            icon.className = iconClass;
            button.appendChild(icon);

            return button;
        }

        function buildPreviewControl(data) {
            var wrapper = document.createElement('div');
            wrapper.className = 'contact-builder-preview-control';

            if (data.type === 'textarea') {
                var textarea = document.createElement('textarea');
                textarea.className = 'form-input contact-builder-preview-textarea';
                textarea.rows = 3;
                textarea.readOnly = true;
                textarea.placeholder = data.placeholder || '';
                wrapper.appendChild(textarea);
                return wrapper;
            }

            if (data.type === 'select') {
                var select = document.createElement('select');
                select.disabled = true;

                var placeholderOption = document.createElement('option');
                placeholderOption.textContent = labels.selectPlaceholder;
                select.appendChild(placeholderOption);

                var selectOptions = data.options.length ? data.options : [labels.optionSampleOne, labels.optionSampleTwo].filter(function (value) {
                    return String(value || '').trim() !== '';
                });
                for (var s = 0; s < selectOptions.length; s += 1) {
                    var option = document.createElement('option');
                    option.textContent = selectOptions[s];
                    select.appendChild(option);
                }

                wrapper.appendChild(select);
                return wrapper;
            }

            if (data.type === 'radio') {
                var radioList = document.createElement('div');
                radioList.className = 'contact-builder-preview-choices';
                var radioOptions = data.options.length ? data.options : [labels.optionSampleOne, labels.optionSampleTwo].filter(function (value) {
                    return String(value || '').trim() !== '';
                });
                for (var r = 0; r < radioOptions.length; r += 1) {
                    var radioItem = document.createElement('label');
                    radioItem.className = 'contact-builder-preview-choice';
                    var radioInput = document.createElement('input');
                    radioInput.type = 'radio';
                    radioInput.disabled = true;
                    radioItem.appendChild(radioInput);
                    var radioText = document.createElement('span');
                    radioText.textContent = radioOptions[r];
                    radioItem.appendChild(radioText);
                    radioList.appendChild(radioItem);
                }
                wrapper.appendChild(radioList);
                return wrapper;
            }

            if (data.type === 'checkbox') {
                var checkboxOptions = data.options.length ? data.options : [];
                if (checkboxOptions.length) {
                    var checkboxList = document.createElement('div');
                    checkboxList.className = 'contact-builder-preview-choices';
                    for (var c = 0; c < checkboxOptions.length; c += 1) {
                        var checkboxItem = document.createElement('label');
                        checkboxItem.className = 'contact-builder-preview-choice';
                        var checkboxInput = document.createElement('input');
                        checkboxInput.type = 'checkbox';
                        checkboxInput.disabled = true;
                        checkboxItem.appendChild(checkboxInput);
                        var checkboxOptionText = document.createElement('span');
                        checkboxOptionText.textContent = checkboxOptions[c];
                        checkboxItem.appendChild(checkboxOptionText);
                        checkboxList.appendChild(checkboxItem);
                    }
                    wrapper.appendChild(checkboxList);
                    return wrapper;
                }

                var checkItem = document.createElement('label');
                checkItem.className = 'contact-builder-preview-choice';
                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.disabled = true;
                checkItem.appendChild(checkbox);
                var checkboxText = document.createElement('span');
                var checkboxLabelText = String(data.label || '').trim();
                checkboxText.textContent = checkboxLabelText !== '' ? checkboxLabelText : labels.unnamedField;
                checkItem.appendChild(checkboxText);
                wrapper.appendChild(checkItem);
                return wrapper;
            }

            var input = document.createElement('input');
            input.type = ['email', 'tel', 'url', 'number', 'date'].indexOf(data.type) !== -1 ? data.type : 'text';
            input.disabled = true;
            input.placeholder = data.placeholder || '';
            wrapper.appendChild(input);

            return wrapper;
        }

        function setTranslationPanel(locale) {
            var normalized = String(locale || '').trim();
            if (normalized === '') {
                normalized = translationTabs.length
                    ? String(translationTabs[0].getAttribute('data-contact-translation-tab') || '').trim()
                    : '';
            }

            if (normalized === '') {
                normalized = sourceLocale;
            }

            var activeButton = null;
            for (var buttonIndex = 0; buttonIndex < translationTabs.length; buttonIndex += 1) {
                var currentLocale = String(translationTabs[buttonIndex].getAttribute('data-contact-translation-tab') || '').trim();
                if (currentLocale === normalized) {
                    activeButton = translationTabs[buttonIndex];
                    break;
                }
            }

            if (activeButton) {
                var sourceLabel = String(activeButton.getAttribute('data-contact-label-source') || '').trim();
                var readyLabel = String(activeButton.getAttribute('data-contact-label-ready') || '').trim();
                var missingLabel = String(activeButton.getAttribute('data-contact-label-missing') || '').trim();

                for (var badgeIndex = 0; badgeIndex < translationTabs.length; badgeIndex += 1) {
                    var badge = translationTabs[badgeIndex].querySelector('.contact-form-translation-badge');
                    if (!badge) {
                        continue;
                    }

                    var stateLabel = String(translationTabs[badgeIndex].getAttribute('data-tab-state') || '').trim();
                    if (stateLabel === 'source') {
                        badge.textContent = sourceLabel;
                        continue;
                    }
                    if (stateLabel === 'ready') {
                        badge.textContent = readyLabel;
                        continue;
                    }
                    badge.textContent = missingLabel;
                }
            }

            for (var i = 0; i < translationTabs.length; i += 1) {
                var tabLocale = String(translationTabs[i].getAttribute('data-contact-translation-tab') || '').trim();
                var isActiveTab = tabLocale === normalized;
                translationTabs[i].classList.toggle('is-active', isActiveTab);
                translationTabs[i].setAttribute('aria-selected', isActiveTab ? 'true' : 'false');
            }

            for (var j = 0; j < translationPanels.length; j += 1) {
                var panelLocale = String(translationPanels[j].getAttribute('data-contact-translation-panel') || '').trim();
                var isActivePanel = panelLocale === normalized;
                translationPanels[j].classList.toggle('is-active', isActivePanel);
                translationPanels[j].hidden = !isActivePanel;
            }

            if (activeLocaleInput) {
                activeLocaleInput.value = normalized;
            }

            applyTranslationModalUi(normalized);

            if (normalized === sourceLocale) {
                syncSourceModalState();
            }
        }

        function updateBodyOverflow() {
            var anyVisibleModal = Array.prototype.slice.call(document.querySelectorAll('.modal-overlay')).some(function (modalNode) {
                return window.getComputedStyle(modalNode).display !== 'none';
            });
            document.body.style.overflow = anyVisibleModal ? 'hidden' : '';
        }

        function openTranslationModal() {
            if (!translationModal) {
                return;
            }

            Array.prototype.slice.call(document.querySelectorAll('.modal-overlay')).forEach(function (otherModal) {
                if (otherModal === translationModal) {
                    return;
                }
                otherModal.style.display = 'none';
                otherModal.setAttribute('aria-hidden', 'true');
            });

            var targetLocale = activeLocaleInput ? String(activeLocaleInput.value || '').trim() : '';
            if (targetLocale === '') {
                targetLocale = translationTabs.length
                    ? String(translationTabs[0].getAttribute('data-contact-translation-tab') || '').trim()
                    : '';
            }

            setTranslationPanel(targetLocale);
            translationModal.classList.remove('is-initially-hidden');
            translationModal.style.display = 'flex';
            translationModal.setAttribute('aria-hidden', 'false');
            updateBodyOverflow();

            var activePanel = null;
            for (var panelIndex = 0; panelIndex < translationPanels.length; panelIndex += 1) {
                if (translationPanels[panelIndex].classList.contains('is-active')) {
                    activePanel = translationPanels[panelIndex];
                    break;
                }
            }

            var firstInput = activePanel ? activePanel.querySelector('input, textarea') : null;
            if (firstInput && typeof firstInput.focus === 'function') {
                window.requestAnimationFrame(function () {
                    firstInput.focus();
                });
            }
        }

        function closeTranslationModal() {
            if (!translationModal) {
                return;
            }

            translationModal.style.display = 'none';
            translationModal.setAttribute('aria-hidden', 'true');
            translationModal.classList.add('is-initially-hidden');
            updateBodyOverflow();
        }

        function renderCanvas() {
            canvas.innerHTML = '';
            var rows = getRows();
            var customData = [];

            for (var c = 0; c < rows.length; c += 1) {
                customData.push(rowData(rows[c], c));
            }

            if (!customData.length) {
                var empty = document.createElement('div');
                empty.className = 'contact-builder-empty';

                var emptyTitle = document.createElement('strong');
                emptyTitle.textContent = labels.emptyTitle;
                empty.appendChild(emptyTitle);

                var emptyHelp = document.createElement('p');
                emptyHelp.textContent = labels.emptyHelp;
                empty.appendChild(emptyHelp);

                canvas.appendChild(empty);
                if (translationModal && activeLocaleInput && String(activeLocaleInput.value || '').trim() === sourceLocale) {
                    syncSourceModalState();
                }
                return;
            }

            var grid = document.createElement('div');
            grid.className = 'contact-builder-grid';

            for (var i = 0; i < customData.length; i += 1) {
                var data = customData[i];
                var item = document.createElement('article');
                item.className = 'contact-builder-item ' + (data.width === 'half' ? 'is-half' : 'is-full');
                item.setAttribute('data-contact-builder-item', '');
                item.setAttribute('data-field-index', String(i));
                if (state.activeIndex === i) {
                    item.classList.add('is-active');
                }

                var controls = document.createElement('div');
                controls.className = 'contact-builder-item-controls';

                var editButton = createActionButton('edit', labels.actionEdit, 'fas fa-pen');
                var duplicateButton = createActionButton('duplicate', labels.actionDuplicate, 'fas fa-copy');
                var moveUpButton = createActionButton('move-up', labels.actionMoveUp, 'fas fa-arrow-up');
                var moveDownButton = createActionButton('move-down', labels.actionMoveDown, 'fas fa-arrow-down');
                var removeButton = createActionButton('remove', labels.actionDelete, 'fas fa-trash-alt', 'is-danger');

                moveUpButton.disabled = i === 0;
                moveDownButton.disabled = i === customData.length - 1;

                controls.appendChild(editButton);
                controls.appendChild(duplicateButton);
                controls.appendChild(moveUpButton);
                controls.appendChild(moveDownButton);
                controls.appendChild(removeButton);
                item.appendChild(controls);

                var header = document.createElement('header');
                header.className = 'contact-builder-item-head';

                var title = document.createElement('strong');
                var labelText = String(data.label || '').trim();
                title.textContent = labelText !== '' ? labelText : (labels.unnamedField + ' #' + (i + 1));
                header.appendChild(title);

                if (data.required) {
                    var requiredMark = document.createElement('span');
                    requiredMark.className = 'contact-builder-item-required';
                    requiredMark.textContent = '*';
                    header.appendChild(requiredMark);
                }

                item.appendChild(header);

                var meta = document.createElement('div');
                meta.className = 'contact-builder-item-meta';

                var keyBadge = document.createElement('span');
                keyBadge.className = 'contact-builder-item-badge';
                keyBadge.textContent = data.key !== '' ? data.key : (labels.fieldPrefix.toLowerCase() + '_' + (i + 1));
                meta.appendChild(keyBadge);

                var typeBadge = document.createElement('span');
                typeBadge.className = 'contact-builder-item-badge';
                typeBadge.textContent = data.type;
                meta.appendChild(typeBadge);
                item.appendChild(meta);

                item.appendChild(buildPreviewControl(data));

                if (data.help !== '') {
                    var help = document.createElement('small');
                    help.className = 'contact-builder-item-help';
                    help.textContent = data.help;
                    item.appendChild(help);
                }

                grid.appendChild(item);
            }

            canvas.appendChild(grid);
            if (translationModal && activeLocaleInput && String(activeLocaleInput.value || '').trim() === sourceLocale) {
                syncSourceModalState();
            }
        }

        function setActiveField(index, openInspectorPanel) {
            var rows = getRows();
            if (index < 0 || index >= rows.length) {
                state.activeIndex = -1;
                if (openInspectorPanel) {
                    setInspectorOpen(false);
                }
                syncInspectorFromActiveRow();
                renderCanvas();
                return;
            }

            state.activeIndex = index;
            if (openInspectorPanel) {
                setInspectorOpen(true);
            }
            syncInspectorFromActiveRow();
            renderCanvas();
        }

        function addRow() {
            var rowsCount = getRows().length;
            var html = rowTemplate.innerHTML.replace(/__INDEX__/g, String(rowsCount));
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            var row = wrapper.firstElementChild;
            if (!row) {
                return;
            }

            clearRow(row);
            rowsContainer.appendChild(row);
            reindexRows();
            setActiveField(getRows().length - 1, true);
        }

        function duplicateRow(index) {
            var rows = getRows();
            if (index < 0 || index >= rows.length) {
                return;
            }

            var clone = rows[index].cloneNode(true);
            rowsContainer.insertBefore(clone, rows[index].nextSibling);
            reindexRows();
            setActiveField(index + 1, true);
        }

        function moveRow(index, direction) {
            var rows = getRows();
            if (index < 0 || index >= rows.length) {
                return;
            }

            var targetIndex = direction === 'up' ? index - 1 : index + 1;
            if (targetIndex < 0 || targetIndex >= rows.length) {
                return;
            }

            var source = rows[index];
            var target = rows[targetIndex];
            if (!source || !target) {
                return;
            }

            if (direction === 'up') {
                rowsContainer.insertBefore(source, target);
            } else {
                rowsContainer.insertBefore(target, source);
            }

            reindexRows();
            setActiveField(targetIndex, false);
        }

        function removeRow(index, withConfirm) {
            var rows = getRows();
            if (index < 0 || index >= rows.length) {
                return;
            }

            var proceed = function () {
                if (hasInspector) {
                    setInspectorOpen(false);
                }
                if (rows[index] && rows[index].parentNode === rowsContainer) {
                    rowsContainer.removeChild(rows[index]);
                }
                reindexRows();
                renderCanvas();
                var nextRows = getRows();
                if (!nextRows.length) {
                    setActiveField(-1, true);
                    return;
                }
                setActiveField(Math.min(index, nextRows.length - 1), false);
            };

            if (!withConfirm) {
                proceed();
                return;
            }

            if (window.FlatCMS && window.FlatCMS.modal && typeof window.FlatCMS.modal.confirm === 'function') {
                window.FlatCMS.modal.confirm(labels.deleteConfirm, proceed);
                return;
            }

            if (window.confirm(labels.deleteConfirm)) {
                proceed();
            }
        }

        function syncRowFromInspector(changedField) {
            if (!hasInspector) {
                return;
            }

            var row = getActiveRow();
            var refs = getRowRefs(row);
            if (!refs) {
                return;
            }

            if (changedField === 'key' && inspectorInputs.key) {
                setAutoKeyEnabled(inspectorInputs.key, String(inspectorInputs.key.value || '').trim() === '');
            }

            if (changedField === 'label' && inspectorInputs.label && inspectorInputs.key && shouldAutoFillKey(inspectorInputs.key)) {
                inspectorInputs.key.value = slugify(inspectorInputs.label.value);
                setAutoKeyEnabled(inspectorInputs.key, true);
            }

            if (refs.label && inspectorInputs.label) { refs.label.value = inspectorInputs.label.value; }
            if (refs.key && inspectorInputs.key) {
                refs.key.value = inspectorInputs.key.value;
                syncAutoKeyState(inspectorInputs.key, refs.key);
            }
            if (refs.type && inspectorInputs.type) { refs.type.value = inspectorInputs.type.value; }
            if (refs.width && inspectorInputs.width) { refs.width.value = inspectorInputs.width.value; }
            if (refs.required && inspectorInputs.required) { refs.required.checked = !!inspectorInputs.required.checked; }
            if (refs.placeholder && inspectorInputs.placeholder) { refs.placeholder.value = inspectorInputs.placeholder.value; }
            if (refs.help && inspectorInputs.help) { refs.help.value = inspectorInputs.help.value; }
            if (changedField === 'options' && refs.options && inspectorInputs.options) {
                refs.options.value = inspectorInputs.options.value;
            }

            updateOptionsVisibility(row);
            if (optionsModalState.fieldIndex === state.activeIndex && inspectorInputs.type && !isChoiceFieldType(inspectorInputs.type.value)) {
                closeOptionsModal();
            }
            if (inspectorInputs.type) {
                toggleInspectorOptions(inspectorInputs.type.value);
            }
            syncInspectorFromActiveRow();
            renderCanvas();
        }

        function syncAttachmentVisibility() {
            if (!attachmentsToggle || !attachmentsConfig) {
                return;
            }
            attachmentsConfig.classList.toggle('is-hidden', !attachmentsToggle.checked);
        }

        canvas.addEventListener('click', function (event) {
            var actionButton = event.target.closest('[data-contact-builder-action]');
            if (actionButton) {
                var parentItem = actionButton.closest('[data-contact-builder-item]');
                var index = Number(parentItem ? parentItem.getAttribute('data-field-index') : -1);
                if (!Number.isFinite(index) || index < 0) {
                    return;
                }

                var action = actionButton.getAttribute('data-contact-builder-action') || '';
                if (action === 'edit') {
                    setActiveField(index, true);
                    return;
                }
                if (action === 'duplicate') {
                    duplicateRow(index);
                    return;
                }
                if (action === 'move-up') {
                    moveRow(index, 'up');
                    return;
                }
                if (action === 'move-down') {
                    moveRow(index, 'down');
                    return;
                }
                if (action === 'remove') {
                    removeRow(index, true);
                }
                return;
            }

            var item = event.target.closest('[data-contact-builder-item]');
            if (!item) {
                return;
            }

            var itemIndex = Number(item.getAttribute('data-field-index') || -1);
            if (!Number.isFinite(itemIndex) || itemIndex < 0) {
                return;
            }

            var rows = getRows();
            var row = rows[itemIndex] || null;
            var data = row ? rowData(row, itemIndex) : null;
            if (data && isChoiceFieldType(data.type)) {
                openOptionsModal(itemIndex);
                return;
            }

            setActiveField(itemIndex, true);
        });

        rowsContainer.addEventListener('change', function (event) {
            var typeSelect = event.target.closest('[data-contact-field-type]');
            if (!typeSelect) {
                return;
            }

            var parentRow = typeSelect.closest('[data-contact-custom-field-row]');
            if (!parentRow) {
                return;
            }

            updateOptionsVisibility(parentRow);
            renderCanvas();
        });

        rowsContainer.addEventListener('input', function (event) {
            var row = event.target.closest('[data-contact-custom-field-row]');
            if (!row) {
                return;
            }

            var keyInput = row.querySelector('[data-contact-field-key]');
            var labelInput = event.target.closest('[data-contact-field-label]');

            if (labelInput) {
                if (keyInput && shouldAutoFillKey(keyInput)) {
                    keyInput.value = slugify(labelInput.value);
                    setAutoKeyEnabled(keyInput, true);
                }

                syncInspectorFromActiveRow();
                renderCanvas();
                return;
            }

            var typedKeyInput = event.target.closest('[data-contact-field-key]');
            if (typedKeyInput) {
                setAutoKeyEnabled(typedKeyInput, String(typedKeyInput.value || '').trim() === '');
                syncInspectorFromActiveRow();
                renderCanvas();
            }
        });

        if (addButton) {
            addButton.addEventListener('click', addRow);
        }

        if (hasInspector) {
            if (inspectorInputs.label) {
                inspectorInputs.label.addEventListener('input', function () { syncRowFromInspector('label'); });
            }
            if (inspectorInputs.key) {
                inspectorInputs.key.addEventListener('input', function () { syncRowFromInspector('key'); });
            }
            if (inspectorInputs.type) {
                inspectorInputs.type.addEventListener('change', function () { syncRowFromInspector('type'); });
            }
            if (inspectorInputs.width) {
                inspectorInputs.width.addEventListener('change', function () { syncRowFromInspector('width'); });
            }
            if (inspectorInputs.required) {
                inspectorInputs.required.addEventListener('change', function () { syncRowFromInspector('required'); });
            }
            if (inspectorInputs.placeholder) {
                inspectorInputs.placeholder.addEventListener('input', function () { syncRowFromInspector('placeholder'); });
            }
            if (inspectorInputs.help) {
                inspectorInputs.help.addEventListener('input', function () { syncRowFromInspector('help'); });
            }
            if (inspectorOptionsModalButton) {
                inspectorOptionsModalButton.addEventListener('click', function () {
                    if (state.activeIndex < 0) {
                        return;
                    }

                    var activeRow = getActiveRow();
                    var activeData = activeRow ? rowData(activeRow, state.activeIndex) : null;
                    if (!activeData || !isChoiceFieldType(activeData.type)) {
                        return;
                    }

                    openOptionsModal(state.activeIndex);
                });
            }
            if (inspectorInputs.options) {
                inspectorInputs.options.addEventListener('click', function () {
                    if (state.activeIndex < 0) {
                        return;
                    }

                    var activeRow = getActiveRow();
                    var activeData = activeRow ? rowData(activeRow, state.activeIndex) : null;
                    if (!activeData || !isChoiceFieldType(activeData.type)) {
                        return;
                    }

                    openOptionsModal(state.activeIndex);
                });
            }

            for (var c = 0; c < inspectorCloseButtons.length; c += 1) {
                inspectorCloseButtons[c].addEventListener('click', function () {
                    setActiveField(-1, true);
                });
            }

            if (inspectorDuplicateButton) {
                inspectorDuplicateButton.addEventListener('click', function () {
                    if (state.activeIndex < 0) {
                        return;
                    }
                    duplicateRow(state.activeIndex);
                });
            }

            if (inspectorDeleteButton) {
                inspectorDeleteButton.addEventListener('click', function () {
                    if (state.activeIndex < 0) {
                        return;
                    }
                    removeRow(state.activeIndex, true);
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && inspector.classList.contains('is-open')) {
                    setActiveField(-1, true);
                }
            });
        }

        if (attachmentsToggle) {
            attachmentsToggle.addEventListener('change', syncAttachmentVisibility);
            syncAttachmentVisibility();
        }

        Array.prototype.slice.call(sourceModalFields).forEach(function (field) {
            var fieldName = String(field.getAttribute('data-contact-source-locale-field') || '').trim();
            if (fieldName === '') {
                return;
            }

            field.addEventListener('input', function () {
                syncSourceModalFieldToMain(fieldName, field.value);
            });
            field.addEventListener('change', function () {
                syncSourceModalFieldToMain(fieldName, field.value);
            });
        });

        if (submitLabelInput) {
            submitLabelInput.addEventListener('input', syncMainTextFieldsToSourceModal);
            submitLabelInput.addEventListener('change', syncMainTextFieldsToSourceModal);
        }

        if (successMessageInput) {
            successMessageInput.addEventListener('input', syncMainTextFieldsToSourceModal);
            successMessageInput.addEventListener('change', syncMainTextFieldsToSourceModal);
        }

        if (formTypeSelect) {
            var previousFormType = String(formTypeSelect.value || '').trim();
            formTypeSelect.addEventListener('change', function () {
                var nextFormType = String(formTypeSelect.value || '').trim();
                if (nextFormType === previousFormType) {
                    toggleNewsletterOptionsByType();
                    return;
                }

                var applyChange = function () {
                    applyPresetByType(nextFormType, true);
                    previousFormType = nextFormType;
                    toggleNewsletterOptionsByType();
                };

                var hasExistingRows = getRows().length > 0;
                if (!hasExistingRows) {
                    applyChange();
                    return;
                }

                if (labels.presetConfirm === '') {
                    applyChange();
                    return;
                }

                if (window.FlatCMS && window.FlatCMS.modal && typeof window.FlatCMS.modal.confirm === 'function') {
                    window.FlatCMS.modal.confirm(labels.presetConfirm, function () {
                        formTypeSelect.value = nextFormType;
                        applyChange();
                    }, {
                        confirmText: labels.presetConfirmText,
                        warning: '',
                    });
                    formTypeSelect.value = previousFormType;
                    toggleNewsletterOptionsByType();
                    return;
                }

                applyChange();
            });
        }

        reindexRows();
        renderCanvas();
        syncInspectorFromActiveRow();
        toggleNewsletterOptionsByType();

        for (var openIndex = 0; openIndex < translationOpenButtons.length; openIndex += 1) {
            translationOpenButtons[openIndex].addEventListener('click', function (event) {
                event.preventDefault();
                openTranslationModal();
            });
        }

        for (var t = 0; t < translationTabs.length; t += 1) {
            translationTabs[t].addEventListener('click', function (event) {
                event.preventDefault();
                var target = event.currentTarget;
                var locale = String(target.getAttribute('data-contact-translation-tab') || '').trim();
                setTranslationPanel(locale);
            });
        }

        if (translationModal) {
            translationModal.addEventListener('click', function (event) {
                if (event.target === translationModal) {
                    closeTranslationModal();
                }
            });

            Array.prototype.slice.call(translationModal.querySelectorAll('[data-modal-close="contactTranslationsModal"]')).forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    closeTranslationModal();
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') {
                    return;
                }

                if (translationModal.getAttribute('aria-hidden') === 'true') {
                    return;
                }

                closeTranslationModal();
            });
        }

        if (optionsModal) {
            for (var closeIndex = 0; closeIndex < optionsModalCloseButtons.length; closeIndex += 1) {
                optionsModalCloseButtons[closeIndex].addEventListener('click', function (event) {
                    event.preventDefault();
                    closeOptionsModal();
                });
            }

            if (optionsModalAddButton) {
                optionsModalAddButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    appendOptionsModalItem('', true);
                });
            }

            if (optionsModalSaveButton) {
                optionsModalSaveButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    saveOptionsModal();
                });
            }

            if (optionsModalList) {
                optionsModalList.addEventListener('click', function (event) {
                    var removeButton = event.target.closest('[data-contact-options-remove]');
                    if (!removeButton) {
                        return;
                    }

                    event.preventDefault();
                    var item = removeButton.closest('[data-contact-options-item]');
                    if (item && item.parentNode === optionsModalList) {
                        optionsModalList.removeChild(item);
                    }

                    if (!optionsModalList.querySelector('[data-contact-options-item]')) {
                        appendOptionsModalItem('', true);
                    }
                });
            }

            optionsModal.addEventListener('click', function (event) {
                if (event.target === optionsModal) {
                    closeOptionsModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && optionsModal.getAttribute('aria-hidden') === 'false') {
                    closeOptionsModal();
                }
            });
        }

        if (translationTabs.length) {
            setTranslationPanel(activeLocaleInput ? String(activeLocaleInput.value || '').trim() : '');
        }
    }

    function init() {
        try {
            initFloatingOptionsCard();
        } catch (error) {
            // noop
        }

        try {
            initMessagesModal();
        } catch (error) {
            // noop
        }

        try {
            initShortcodeCopy();
        } catch (error) {
            // noop
        }

        try {
            initFormBuilder();
        } catch (error) {
            // noop
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
        return;
    }

    init();
})();
