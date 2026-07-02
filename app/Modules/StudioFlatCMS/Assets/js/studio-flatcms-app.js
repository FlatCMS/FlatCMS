/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function (window, document) {
    'use strict';

    var namespace = window.FlatCMSStudioFlatCMS = window.FlatCMSStudioFlatCMS || {};
    var root = document.getElementById('sfc-studio-app');
    var bootNode = document.getElementById('sfc-studio-boot');

    if (!root || !bootNode || !namespace.state || !namespace.render || !namespace.api) {
        return;
    }

    function parseBoot(node) {
        try {
            return JSON.parse(node.innerHTML || '{}');
        } catch (error) {
            return {};
        }
    }

    var boot = parseBoot(bootNode);
    var labels = boot.labels || {};
    var store = namespace.state.createStore(boot.document || {});
    var toastRoot = document.getElementById('sfc-studio-toast-root');
    var drawerRoot = document.getElementById('sfc-studio-drawer');
    var drawerBody = document.getElementById('sfc-studio-drawer-body');
    var drawerTitle = document.getElementById('sfc-studio-drawer-title');
    var drawerSubtitle = document.getElementById('sfc-studio-drawer-subtitle');
    var inspectorRoot = document.getElementById('sfc-studio-inspector');
    var inspectorBody = document.getElementById('sfc-studio-inspector-body');
    var inspectorTabs = document.getElementById('sfc-studio-inspector-tabs');
    var selectionName = document.getElementById('sfc-studio-selection-name');
    var stage = document.getElementById('sfc-studio-stage');
    var stageWrap = document.getElementById('sfc-studio-stage-wrap');
    var canvasScroll = root.querySelector('.sfc-studio-canvas-scroll');
    var inlineEditorRoot = document.getElementById('sfc-studio-inline-editor-root');
    var railButtons = root.querySelectorAll('.sfc-studio-rail-btn');
    var snapLayerRoot = null;
    var snapGuideVertical = null;
    var snapGuideHorizontal = null;
    var ui = {
        drawer: '',
        inspectorOpen: false,
        inlineEditorNodeId: '',
        mediaEnabled: !!(boot.config && boot.config.media && boot.config.media.uploadUrl),
        nodeMenuId: '',
        sources: Array.isArray(boot.sources) ? boot.sources : [],
        currentSource: boot.currentSource && typeof boot.currentSource === 'object' ? boot.currentSource : null,
        pendingFieldDraft: null,
        pendingInlineDraft: null,
        richTextEditorHandle: null,
        richTextEditorNodeId: '',
        selectedNode: null
    };
    var suppressClick = false;
    var interaction = null;
    var SNAP_DISTANCE = 6;
    var snapFeedbackTimer = null;

    namespace.pushToast = function (message, type) {
        if (!toastRoot) {
            return;
        }
        var toast = document.createElement('div');
        toast.className = 'sfc-studio-toast' + (type === 'error' ? ' is-error' : (type === 'warning' ? ' is-warning' : ''));
        toast.textContent = String(message || '');
        toastRoot.appendChild(toast);
        window.setTimeout(function () {
            toast.remove();
        }, 2600);
    };

    function drawerCopy(drawer) {
        if (!drawer) {
            return ['', ''];
        }
        if (drawer === 'elements') {
            return [labels.drawerElementsTitle, labels.drawerElementsSubtitle];
        }
        if (drawer === 'shell') {
            return [labels.drawerShellTitle, labels.drawerShellSubtitle];
        }
        if (drawer === 'page') {
            return [labels.drawerPageTitle, labels.drawerPageSubtitle];
        }
        return [labels.drawerStructureTitle, labels.drawerStructureSubtitle];
    }

    function currentSnapshot() {
        return store.getSnapshot();
    }

    function defaultFrame() {
        return {
            offsetX: 0,
            offsetY: 0,
            width: null,
            height: null
        };
    }

    function copyFrame(frame) {
        return {
            offsetX: Number(frame.offsetX || 0),
            offsetY: Number(frame.offsetY || 0),
            width: frame.width == null ? null : Number(frame.width || 0),
            height: frame.height == null ? null : Number(frame.height || 0)
        };
    }

    function nodeFrame(node) {
        if (!node || typeof node !== 'object') {
            return defaultFrame();
        }

        if (!node.frame || typeof node.frame !== 'object') {
            return defaultFrame();
        }

        return copyFrame(node.frame);
    }

    function ensureFrame(node) {
        if (!node || typeof node !== 'object') {
            return defaultFrame();
        }

        if (!node.frame || typeof node.frame !== 'object') {
            node.frame = defaultFrame();
        }

        node.frame.offsetX = Number(node.frame.offsetX || 0);
        node.frame.offsetY = Number(node.frame.offsetY || 0);
        node.frame.width = node.frame.width == null ? null : Number(node.frame.width || 0);
        node.frame.height = node.frame.height == null ? null : Number(node.frame.height || 0);

        return node.frame;
    }

    function clamp(value, min, max) {
        if (value < min) {
            return min;
        }
        if (value > max) {
            return max;
        }
        return value;
    }

    function parseSignedInt(value) {
        var normalized = String(value == null ? '' : value).trim();
        if (normalized === '') {
            return 0;
        }

        var parsed = parseInt(normalized, 10);
        return window.isFinite(parsed) ? parsed : 0;
    }

    function parseNullableSize(value) {
        var normalized = String(value == null ? '' : value).trim();
        if (normalized === '') {
            return null;
        }

        var parsed = parseInt(normalized, 10);
        if (!window.isFinite(parsed)) {
            return null;
        }

        return Math.max(48, parsed);
    }

    function fieldValue(node, field) {
        var frame = nodeFrame(node);

        if (field === 'frameWidth') {
            return frame.width == null ? '' : String(frame.width);
        }
        if (field === 'frameHeight') {
            return frame.height == null ? '' : String(frame.height);
        }
        if (field === 'frameOffsetX') {
            return String(frame.offsetX);
        }
        if (field === 'frameOffsetY') {
            return String(frame.offsetY);
        }
        if (field === 'enabled') {
            return node && node.enabled !== false ? '1' : '0';
        }

        return String(node && node[field] != null ? node[field] : '');
    }

    function applyFieldValue(node, field, value) {
        if (!node || typeof node !== 'object') {
            return;
        }

        if (field === 'frameWidth') {
            ensureFrame(node).width = parseNullableSize(value);
            return;
        }
        if (field === 'frameHeight') {
            ensureFrame(node).height = parseNullableSize(value);
            return;
        }
        if (field === 'frameOffsetX') {
            ensureFrame(node).offsetX = parseSignedInt(value);
            return;
        }
        if (field === 'frameOffsetY') {
            ensureFrame(node).offsetY = parseSignedInt(value);
            return;
        }
        if (field === 'enabled') {
            node.enabled = value !== false;
            return;
        }

        node[field] = value;
    }

    function mediaConfig() {
        return boot.config && boot.config.media && typeof boot.config.media === 'object'
            ? boot.config.media
            : {};
    }

    function resolveMediaSource(rawValue) {
        var src = String(rawValue || '').trim();
        if (src === '') {
            return '';
        }

        if (/^(https?:|data:|blob:)/i.test(src)) {
            return src;
        }

        var config = mediaConfig();
        var uploadsBase = String(config.uploadsBase || '/uploads').replace(/\/$/, '');
        if (src.indexOf('/public/uploads/') === 0) {
            return uploadsBase + '/' + src.replace(/^\/public\/uploads\/?/, '');
        }
        if (src.indexOf('/uploads/') === 0) {
            return uploadsBase + '/' + src.replace(/^\/uploads\/?/, '');
        }
        if (src.indexOf('uploads/') === 0) {
            return uploadsBase + '/' + src.replace(/^uploads\/?/, '');
        }
        if (src.indexOf('/') === 0) {
            return src;
        }

        return uploadsBase + '/' + src.replace(/^\//, '');
    }

    function normalizeSelectedMediaValue(file) {
        var rawPath = String((file && file.path) || '').trim().replace(/\\/g, '/');
        if (rawPath !== '') {
            rawPath = rawPath.replace(/^\/+/, '');
            if (rawPath.indexOf('public/uploads/') === 0) {
                return '/uploads/' + rawPath.replace(/^public\/uploads\/?/, '');
            }
            if (rawPath.indexOf('uploads/') === 0) {
                return '/uploads/' + rawPath.replace(/^uploads\/?/, '');
            }
            return '/uploads/' + rawPath;
        }

        var explicit = String((file && (file.url || file.src)) || '').trim();
        if (explicit === '') {
            return '';
        }

        try {
            var parsed = new window.URL(explicit, window.location.origin);
            if (parsed.origin === window.location.origin) {
                return parsed.pathname + parsed.search + parsed.hash;
            }
        } catch (error) {
            // Keep the original value when URL parsing fails.
        }

        return explicit;
    }

    function closeMediaModal() {
        var modal = document.getElementById('mediaModal');
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    var mediaRuntimePromise = null;

    function mediaScriptUrl() {
        var config = mediaConfig();
        var configured = String(config.scriptUrl || '').trim();
        if (configured !== '') {
            return configured;
        }

        var current = document.querySelector('script[src*="/modules/media/js/media-modal.js"]');
        return current ? String(current.getAttribute('src') || '').trim() : '';
    }

    function ensureMediaPickerRuntime() {
        if (typeof window.initMediaModal === 'function') {
            return Promise.resolve(true);
        }

        if (mediaRuntimePromise) {
            return mediaRuntimePromise;
        }

        var scriptUrl = mediaScriptUrl();
        if (scriptUrl === '') {
            return Promise.resolve(false);
        }

        mediaRuntimePromise = new Promise(function (resolve) {
            var script = document.createElement('script');
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

    function canUseMediaPicker() {
        var config = mediaConfig();
        return !!(config && config.uploadUrl && document.getElementById('mediaModal'));
    }

    function openMediaPicker(onSelect) {
        if (!canUseMediaPicker()) {
            namespace.api.showToast(labels.mediaUnavailable || '', 'warning');
            return;
        }

        var modal = document.getElementById('mediaModal');
        var config = mediaConfig();

        ensureMediaPickerRuntime().then(function (ready) {
            if (!ready || typeof window.initMediaModal !== 'function') {
                namespace.api.showToast(labels.mediaUnavailable || '', 'warning');
                return;
            }

            modal.classList.remove('hidden');
            modal.style.display = 'flex';

            window.initMediaModal({
                apiImagesUrl: config.apiImagesUrl || '',
                apiFilesUrl: config.apiFilesUrl || '',
                uploadUrl: config.uploadUrl || '',
                uploadsBase: config.uploadsBase || '/uploads',
                csrfToken: config.csrfToken || boot.config.token || '',
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

    function frameChanged(previousFrame, nextFrame) {
        return Number(previousFrame.offsetX || 0) !== Number(nextFrame.offsetX || 0)
            || Number(previousFrame.offsetY || 0) !== Number(nextFrame.offsetY || 0)
            || (previousFrame.width == null ? null : Number(previousFrame.width)) !== (nextFrame.width == null ? null : Number(nextFrame.width))
            || (previousFrame.height == null ? null : Number(previousFrame.height)) !== (nextFrame.height == null ? null : Number(nextFrame.height));
    }

    function nodeElement(nodeId) {
        if (!stage || !nodeId) {
            return null;
        }

        return stage.querySelector('.sfc-stage-node[data-node-id="' + nodeId + '"]');
    }

    function snapTargetElement(nodeId) {
        if (!stage || !nodeId) {
            return null;
        }

        return stage.querySelector('.sfc-stage-node[data-node-id="' + nodeId + '"], .sfc-stage-region[data-node-id="' + nodeId + '"]');
    }

    function ensureSnapLayer() {
        if (!stageWrap) {
            return;
        }

        if (!snapLayerRoot) {
            snapLayerRoot = document.getElementById('sfc-studio-snap-layer');
        }

        if (!snapLayerRoot) {
            snapLayerRoot = document.createElement('div');
            snapLayerRoot.id = 'sfc-studio-snap-layer';
            snapLayerRoot.className = 'sfc-studio-snap-layer';
            snapLayerRoot.innerHTML = ''
                + '<div class="sfc-stage-snap-guide sfc-stage-snap-guide-vertical" data-snap-guide="vertical"></div>'
                + '<div class="sfc-stage-snap-guide sfc-stage-snap-guide-horizontal" data-snap-guide="horizontal"></div>';
            stageWrap.appendChild(snapLayerRoot);
        }

        snapGuideVertical = snapLayerRoot.querySelector('[data-snap-guide="vertical"]');
        snapGuideHorizontal = snapLayerRoot.querySelector('[data-snap-guide="horizontal"]');
    }

    function hideGuide(guide) {
        if (!guide) {
            return;
        }

        guide.classList.remove('is-active');
        guide.style.removeProperty('left');
        guide.style.removeProperty('top');
        guide.style.removeProperty('width');
        guide.style.removeProperty('height');
    }

    function clearSnapFeedbackTimer() {
        if (snapFeedbackTimer) {
            window.clearTimeout(snapFeedbackTimer);
            snapFeedbackTimer = null;
        }
    }

    function clearSnapTargets() {
        if (!stage) {
            return;
        }

        stage.querySelectorAll('.sfc-stage-node.is-snap-target, .sfc-stage-region.is-snap-target').forEach(function (element) {
            element.classList.remove('is-snap-target');
        });
    }

    function clearSnapFeedback() {
        clearSnapFeedbackTimer();
        clearSnapTargets();
        ensureSnapLayer();
        hideGuide(snapGuideVertical);
        hideGuide(snapGuideHorizontal);
    }

    function scheduleSnapFeedbackClear(delay) {
        clearSnapFeedbackTimer();
        snapFeedbackTimer = window.setTimeout(function () {
            snapFeedbackTimer = null;
            if (!interaction) {
                clearSnapTargets();
                hideGuide(snapGuideVertical);
                hideGuide(snapGuideHorizontal);
            }
        }, Math.max(120, Number(delay || 0)));
    }

    function updateSnapTargets(nodeIds) {
        clearSnapTargets();
        (nodeIds || []).forEach(function (nodeId) {
            var target = snapTargetElement(nodeId);
            if (target) {
                target.classList.add('is-snap-target');
            }
        });
    }

    function applyFrameToElement(element, frame) {
        if (!element || !frame) {
            return;
        }

        element.style.setProperty('--sfc-node-offset-x', String(Number(frame.offsetX || 0)) + 'px');
        element.style.setProperty('--sfc-node-offset-y', String(Number(frame.offsetY || 0)) + 'px');

        if (frame.width == null) {
            element.classList.remove('has-frame-width');
            element.style.removeProperty('--sfc-node-width');
        } else {
            element.classList.add('has-frame-width');
            element.style.setProperty('--sfc-node-width', String(Number(frame.width || 0)) + 'px');
        }

        if (frame.height == null) {
            element.classList.remove('has-frame-height');
            element.style.removeProperty('--sfc-node-height');
        } else {
            element.classList.add('has-frame-height');
            element.style.setProperty('--sfc-node-height', String(Number(frame.height || 0)) + 'px');
        }
    }

    function syncCanvasFrames() {
        if (!stage) {
            return;
        }

        stage.querySelectorAll('.sfc-stage-node[data-node-id]').forEach(function (element) {
            var nodeId = String(element.getAttribute('data-node-id') || '');
            var targetNode = store.findNodeById(nodeId);
            applyFrameToElement(element, nodeFrame(targetNode));
        });
    }

    function syncCanvasImages() {
        if (!stage) {
            return;
        }

        stage.querySelectorAll('.sfc-stage-node[data-node-type="image"][data-node-id]').forEach(function (element) {
            var nodeId = String(element.getAttribute('data-node-id') || '');
            var node = store.findNodeById(nodeId);
            var image = element.querySelector('.sfc-stage-image img');
            var resolved = resolveMediaSource(node && node.src ? node.src : '');

            if (!(image instanceof window.HTMLImageElement)) {
                return;
            }

            if (resolved === '') {
                image.removeAttribute('src');
                return;
            }

            if (String(image.getAttribute('src') || '') !== resolved) {
                image.setAttribute('src', resolved);
            }
        });
    }

    function enhanceInspectorMediaFields() {
        if (!ui.inspectorOpen || !ui.selectedNode || ui.selectedNode.type !== 'image' || !inspectorBody) {
            return;
        }

        var host = inspectorBody.querySelector('[data-media-bind="src"]');
        if (!(host instanceof HTMLElement)) {
            return;
        }

        var primitives = window.FlatCMSUIPrimitives || {};
        if (typeof primitives.createBuilderMediaFieldControls !== 'function') {
            return;
        }

        var mediaField = primitives.createBuilderMediaFieldControls({
            value: fieldValue(ui.selectedNode, 'src'),
            disabled: false,
            previewEnabled: true,
            mediaOptions: { mode: 'images', preview: 'image' },
            resolveSrc: function (value) {
                return resolveMediaSource(value);
            },
            noMediaLabel: labels.mediaNoMedia || '',
            pickButtonClass: 'btn btn-secondary btn-sm',
            clearButtonClass: 'btn btn-ghost btn-sm',
            pickButtonText: labels.mediaChooseImage || ''
        });

        mediaField.clearButton.textContent = String(labels.mediaRemoveMedia || '');

        mediaField.pickButton.addEventListener('click', function () {
            openMediaPicker(function (file) {
                var nextValue = normalizeSelectedMediaValue(file);
                if (nextValue === '') {
                    return;
                }

                if (!ui.selectedNode) {
                    return;
                }

                store.updateNode(ui.selectedNode.id, function (node) {
                    applyFieldValue(node, 'src', nextValue);
                });
            });
        });

        mediaField.clearButton.addEventListener('click', function () {
            if (!ui.selectedNode) {
                return;
            }

            store.updateNode(ui.selectedNode.id, function (node) {
                applyFieldValue(node, 'src', '');
            });
        });

        host.innerHTML = '';
        host.appendChild(mediaField.element);
    }

    function collectSnapCandidates(nodeId) {
        if (!stage) {
            return [];
        }

        var currentElement = nodeElement(nodeId);
        var candidates = [];
        var seenIds = {};
        if (!currentElement) {
            return candidates;
        }

        function pushCandidate(element, priority) {
            if (!(element instanceof HTMLElement)) {
                return;
            }

            var candidateId = String(element.getAttribute('data-node-id') || '');
            if (candidateId === '' || seenIds[candidateId]) {
                return;
            }

            var rect = element.getBoundingClientRect();
            if (rect.width < 2 || rect.height < 2) {
                return;
            }

            seenIds[candidateId] = true;
            candidates.push({
                nodeId: candidateId,
                rect: rect,
                priority: Number(priority || 0)
            });
        }

        var parentSection = currentElement.parentElement
            ? currentElement.parentElement.closest('.sfc-stage-node[data-node-id][data-node-type="section"]')
            : null;
        var parentRegion = currentElement.closest('.sfc-stage-region[data-node-id]');
        var parentTarget = parentSection || parentRegion;
        if (parentTarget instanceof HTMLElement) {
            pushCandidate(parentTarget, 3);
        }

        Array.prototype.slice.call(stage.querySelectorAll('.sfc-stage-node[data-node-id][data-node-type="section"], .sfc-stage-region[data-node-id]')).forEach(function (candidate) {
            if (!(candidate instanceof HTMLElement)) {
                return;
            }

            if (candidate === parentTarget || candidate.contains(currentElement) || currentElement.contains(candidate)) {
                return;
            }

            pushCandidate(candidate, candidate.classList.contains('sfc-stage-region') ? 1 : 2);
        });

        Array.prototype.slice.call(stage.querySelectorAll('.sfc-stage-node[data-node-id]')).filter(function (candidate) {
            if (!(candidate instanceof HTMLElement) || candidate === currentElement) {
                return false;
            }

            if (candidate.getAttribute('data-node-type') === 'section') {
                return false;
            }

            if (currentElement.contains(candidate) || candidate.contains(currentElement)) {
                return false;
            }

            var rect = candidate.getBoundingClientRect();
            return rect.width >= 2 && rect.height >= 2;
        }).forEach(function (candidate) {
            pushCandidate(candidate, 0);
        });

        return candidates;
    }

    function snapPointsForRect(rect) {
        return {
            left: rect.left,
            centerX: rect.left + (rect.width / 2),
            right: rect.right,
            top: rect.top,
            centerY: rect.top + (rect.height / 2),
            bottom: rect.bottom
        };
    }

    function rectPoint(rect, edge) {
        var points = snapPointsForRect(rect);
        return Object.prototype.hasOwnProperty.call(points, edge) ? Number(points[edge]) : 0;
    }

    function bestSnapForAxis(axis, movingRect, candidates) {
        var movingPoints = axis === 'x'
            ? [
                { value: movingRect.left, edge: 'left' },
                { value: movingRect.left + (movingRect.width / 2), edge: 'centerX' },
                { value: movingRect.right, edge: 'right' }
            ]
            : [
                { value: movingRect.top, edge: 'top' },
                { value: movingRect.top + (movingRect.height / 2), edge: 'centerY' },
                { value: movingRect.bottom, edge: 'bottom' }
            ];
        var best = null;

        (candidates || []).forEach(function (candidate) {
            var candidatePoints = snapPointsForRect(candidate.rect);
            var targetPoints = axis === 'x'
                ? [
                    { value: candidatePoints.left, edge: 'left' },
                    { value: candidatePoints.centerX, edge: 'centerX' },
                    { value: candidatePoints.right, edge: 'right' }
                ]
                : [
                    { value: candidatePoints.top, edge: 'top' },
                    { value: candidatePoints.centerY, edge: 'centerY' },
                    { value: candidatePoints.bottom, edge: 'bottom' }
                ];

            movingPoints.forEach(function (movingPoint) {
                targetPoints.forEach(function (targetPoint) {
                    var delta = targetPoint.value - movingPoint.value;
                    var distance = Math.abs(delta);
                    if (distance > SNAP_DISTANCE) {
                        return;
                    }

                    if (!best || distance < best.distance || (distance === best.distance && Number(candidate.priority || 0) > Number(best.priority || 0))) {
                        best = {
                            axis: axis,
                            delta: delta,
                            distance: distance,
                            line: targetPoint.value,
                            targetEdge: targetPoint.edge,
                            nodeId: candidate.nodeId,
                            rect: candidate.rect,
                            priority: Number(candidate.priority || 0)
                        };
                    }
                });
            });
        });

        return best;
    }

    function refreshSnapMatch(snapMatch) {
        if (!snapMatch || !snapMatch.nodeId) {
            return null;
        }

        var target = snapTargetElement(snapMatch.nodeId);
        if (!(target instanceof HTMLElement)) {
            return null;
        }

        var rect = target.getBoundingClientRect();
        return {
            axis: snapMatch.axis,
            delta: snapMatch.delta,
            distance: snapMatch.distance,
            line: snapMatch.targetEdge ? rectPoint(rect, snapMatch.targetEdge) : snapMatch.line,
            targetEdge: snapMatch.targetEdge || '',
            nodeId: snapMatch.nodeId,
            rect: rect,
            priority: Number(snapMatch.priority || 0)
        };
    }

    function updateGuide(guide, axis, movingRect, snapMatch) {
        ensureSnapLayer();
        if (!guide || !stageWrap || !snapMatch) {
            return;
        }

        var wrapRect = stageWrap.getBoundingClientRect();
        var guideStart;
        var guideEnd;

        guide.classList.add('is-active');

        if (axis === 'x') {
            guideStart = Math.min(movingRect.top, snapMatch.rect.top) - wrapRect.top;
            guideEnd = Math.max(movingRect.bottom, snapMatch.rect.bottom) - wrapRect.top;
            guide.style.left = String(Math.round(snapMatch.line - wrapRect.left)) + 'px';
            guide.style.top = String(Math.round(guideStart)) + 'px';
            guide.style.height = String(Math.max(0, Math.round(guideEnd - guideStart))) + 'px';
            guide.style.width = '0px';
            return;
        }

        guideStart = Math.min(movingRect.left, snapMatch.rect.left) - wrapRect.left;
        guideEnd = Math.max(movingRect.right, snapMatch.rect.right) - wrapRect.left;
        guide.style.left = String(Math.round(guideStart)) + 'px';
        guide.style.top = String(Math.round(snapMatch.line - wrapRect.top)) + 'px';
        guide.style.width = String(Math.max(0, Math.round(guideEnd - guideStart))) + 'px';
        guide.style.height = '0px';
    }

    function updateSnapFeedback(movingRect, snapX, snapY) {
        var targetIds = [];

        if (snapX && snapX.nodeId) {
            targetIds.push(snapX.nodeId);
        }
        if (snapY && snapY.nodeId && targetIds.indexOf(snapY.nodeId) === -1) {
            targetIds.push(snapY.nodeId);
        }

        updateSnapTargets(targetIds);
        hideGuide(snapGuideVertical);
        hideGuide(snapGuideHorizontal);

        if (snapX) {
            updateGuide(snapGuideVertical, 'x', movingRect, snapX);
        }
        if (snapY) {
            updateGuide(snapGuideHorizontal, 'y', movingRect, snapY);
        }
    }

    function interactionBoundsElement(element) {
        var current = element ? element.parentElement : null;

        while (current && current !== stage) {
            if (current.classList.contains('sfc-stage-section')
                || current.classList.contains('sfc-stage-region-body')) {
                return current;
            }
            current = current.parentElement;
        }

        return element ? element.parentElement : null;
    }

    function frameBounds(element) {
        var boundsElement = interactionBoundsElement(element);
        if (!boundsElement) {
            return null;
        }

        return boundsElement.getBoundingClientRect();
    }

    function resolveMovePlacement(startRect, parentRect, candidatePool, deltaX, deltaY) {
        var left = clamp(startRect.left + deltaX, parentRect.left, parentRect.right - startRect.width);
        var top = clamp(startRect.top + deltaY, parentRect.top, parentRect.bottom - startRect.height);
        var movingRect = {
            left: left,
            top: top,
            width: startRect.width,
            height: startRect.height,
            right: left + startRect.width,
            bottom: top + startRect.height
        };
        var snapX = bestSnapForAxis('x', movingRect, candidatePool);
        var snapY = bestSnapForAxis('y', movingRect, candidatePool);

        if (snapX) {
            var snappedLeft = left + snapX.delta;
            var boundedLeft = clamp(snappedLeft, parentRect.left, parentRect.right - startRect.width);
            if (Math.abs(boundedLeft - snappedLeft) > 0.5) {
                snapX = null;
            } else {
                left = boundedLeft;
                movingRect.left = left;
                movingRect.right = left + startRect.width;
            }
        }

        if (snapY) {
            var snappedTop = top + snapY.delta;
            var boundedTop = clamp(snappedTop, parentRect.top, parentRect.bottom - startRect.height);
            if (Math.abs(boundedTop - snappedTop) > 0.5) {
                snapY = null;
            } else {
                top = boundedTop;
                movingRect.top = top;
                movingRect.bottom = top + startRect.height;
            }
        }

        return {
            left: left,
            top: top,
            movingRect: movingRect,
            snapX: snapX,
            snapY: snapY
        };
    }

    function beginInteraction(mode, nodeId, handle, event) {
        if (!nodeId) {
            return;
        }

        commitActiveEditor();

        if (currentSnapshot().selection.nodeId !== nodeId) {
            store.select(nodeId);
        }

        var targetNode = store.findNodeById(nodeId);
        var targetElement = nodeElement(nodeId);
        var bounds = frameBounds(targetElement);
        if (!targetNode || !targetElement || !bounds) {
            return;
        }

        var startFrame = nodeFrame(targetNode);
        var rect = targetElement.getBoundingClientRect();
        interaction = {
            mode: mode,
            handle: String(handle || ''),
            nodeId: nodeId,
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            startRect: rect,
            parentRect: bounds,
            baseLeft: rect.left - startFrame.offsetX,
            baseTop: rect.top - startFrame.offsetY,
            startFrame: startFrame,
            liveFrame: copyFrame(startFrame),
            snapCandidates: mode === 'move' ? collectSnapCandidates(nodeId) : [],
            moved: false
        };

        clearSnapFeedback();
        root.classList.add('is-dragging');
        event.preventDefault();
    }

    function computeInteractionFrame(nextX, nextY) {
        if (!interaction) {
            return null;
        }

        var deltaX = nextX - interaction.startX;
        var deltaY = nextY - interaction.startY;
        var startRect = interaction.startRect;
        var parentRect = interaction.parentRect;
        var minSize = 48;
        var nextFrame = copyFrame(interaction.startFrame);
        var left = startRect.left;
        var top = startRect.top;
        var right = startRect.right;
        var bottom = startRect.bottom;

        if (interaction.mode === 'move') {
            var movePlacement = resolveMovePlacement(startRect, parentRect, interaction.snapCandidates, deltaX, deltaY);
            left = movePlacement.left;
            top = movePlacement.top;
            updateSnapFeedback(movePlacement.movingRect, movePlacement.snapX, movePlacement.snapY);
            nextFrame.offsetX = Math.round(left - interaction.baseLeft);
            nextFrame.offsetY = Math.round(top - interaction.baseTop);
            return nextFrame;
        }

        if (interaction.handle.indexOf('e') !== -1) {
            right = clamp(startRect.right + deltaX, left + minSize, parentRect.right);
        }
        if (interaction.handle.indexOf('w') !== -1) {
            left = clamp(startRect.left + deltaX, parentRect.left, right - minSize);
        }
        if (interaction.handle.indexOf('s') !== -1) {
            bottom = clamp(startRect.bottom + deltaY, top + minSize, parentRect.bottom);
        }
        if (interaction.handle.indexOf('n') !== -1) {
            top = clamp(startRect.top + deltaY, parentRect.top, bottom - minSize);
        }

        nextFrame.offsetX = Math.round(left - interaction.baseLeft);
        nextFrame.offsetY = Math.round(top - interaction.baseTop);
        nextFrame.width = Math.round(Math.max(minSize, right - left));
        nextFrame.height = Math.round(Math.max(minSize, bottom - top));

        return nextFrame;
    }

    function finishInteraction(commitChange) {
        if (!interaction) {
            return;
        }

        var activeInteraction = interaction;
        interaction = null;
        root.classList.remove('is-dragging');
        clearSnapFeedback();

        if (!commitChange) {
            applyFrameToElement(nodeElement(activeInteraction.nodeId), activeInteraction.startFrame);
            return;
        }

        if (!frameChanged(activeInteraction.startFrame, activeInteraction.liveFrame)) {
            return;
        }

        suppressClick = activeInteraction.moved;
        store.updateNode(activeInteraction.nodeId, function (node) {
            var frame = ensureFrame(node);
            frame.offsetX = activeInteraction.liveFrame.offsetX;
            frame.offsetY = activeInteraction.liveFrame.offsetY;
            frame.width = activeInteraction.liveFrame.width;
            frame.height = activeInteraction.liveFrame.height;
        });
    }

    function keyboardEditableTarget(target) {
        if (!(target instanceof HTMLElement)) {
            return false;
        }

        if (target.isContentEditable || target.closest('[contenteditable="true"]')) {
            return true;
        }

        if (target instanceof window.HTMLInputElement
            || target instanceof window.HTMLTextAreaElement
            || target instanceof window.HTMLSelectElement) {
            return true;
        }

        return !!target.closest('.sfc-studio-inline-editor, [data-inline-content="true"]');
    }

    function activeSelectionNode() {
        var snapshot = currentSnapshot();
        var selectedNodeId = String(snapshot.selection.nodeId || '');
        if (!selectedNodeId || selectedNodeId === String(snapshot.document.id || '')) {
            return null;
        }

        var selectedNode = store.findNodeById(selectedNodeId);
        var selectedElement = nodeElement(selectedNodeId);
        if (!selectedNode || !selectedElement) {
            return null;
        }

        return {
            element: selectedElement,
            id: selectedNodeId,
            node: selectedNode,
            snapshot: snapshot
        };
    }

    function nudgeSelectedNode(deltaX, deltaY) {
        var selected = activeSelectionNode();
        if (!selected) {
            return false;
        }

        var targetElement = selected.element;
        var targetNode = selected.node;
        var selectedNodeId = selected.id;
        var bounds = frameBounds(targetElement);
        if (!targetElement || !targetNode || !bounds) {
            return false;
        }

        var currentFrame = nodeFrame(targetNode);
        var rect = targetElement.getBoundingClientRect();
        var baseLeft = rect.left - currentFrame.offsetX;
        var baseTop = rect.top - currentFrame.offsetY;
        var placement = resolveMovePlacement(rect, bounds, collectSnapCandidates(selectedNodeId), deltaX, deltaY);
        var nextOffsetX = Math.round(placement.left - baseLeft);
        var nextOffsetY = Math.round(placement.top - baseTop);

        if (nextOffsetX === currentFrame.offsetX && nextOffsetY === currentFrame.offsetY) {
            return false;
        }

        store.updateNode(selectedNodeId, function (node) {
            var frame = ensureFrame(node);
            frame.offsetX = nextOffsetX;
            frame.offsetY = nextOffsetY;
        });

        var refreshedElement = nodeElement(selectedNodeId);
        var refreshedRect = refreshedElement ? refreshedElement.getBoundingClientRect() : null;
        if (refreshedRect) {
            updateSnapFeedback(
                {
                    left: refreshedRect.left,
                    top: refreshedRect.top,
                    width: refreshedRect.width,
                    height: refreshedRect.height,
                    right: refreshedRect.right,
                    bottom: refreshedRect.bottom
                },
                refreshSnapMatch(placement.snapX),
                refreshSnapMatch(placement.snapY)
            );
            scheduleSnapFeedbackClear(260);
        }

        return true;
    }

    function deleteSelectedNode() {
        var selected = activeSelectionNode();
        if (!selected) {
            return false;
        }

        store.removeNode(selected.id);
        return true;
    }

    function duplicateSelectedNode() {
        var selected = activeSelectionNode();
        if (!selected) {
            return false;
        }

        store.duplicateNode(selected.id);
        return true;
    }

    function moveSelectedNode(direction) {
        var selected = activeSelectionNode();
        if (!selected) {
            return false;
        }

        return store.moveNode(selected.id, direction);
    }

    function openSelectedEditor() {
        var selected = activeSelectionNode();
        if (!selected) {
            return false;
        }

        if (supportsRichTextEditor(selected.node)) {
            openRichTextEditor(selected.id);
            return true;
        }

        if (supportsInlineEditor(selected.node)) {
            openInlineEditor(selected.id, {
                placeCaretAtEnd: true
            });
            return true;
        }

        return false;
    }

    function saveCurrentDocument() {
        commitActiveEditor();
        var snapshot = currentSnapshot();
        namespace.api.saveDocument(boot, snapshot.document).then(function (response) {
            store.markSaved(response.document || snapshot.document);
            if (response && response.currentSource && typeof response.currentSource === 'object') {
                ui.currentSource = response.currentSource;
            }
            namespace.api.showToast((response && response.message) || labels.saveSuccess || '', 'success');
        }).catch(function (error) {
            namespace.api.showToast((error && error.message) || labels.saveError || '', 'error');
        });
    }

    function handleEscape() {
        if (ui.richTextEditorNodeId !== '') {
            commitRichTextEditor();
            return true;
        }

        if (ui.inlineEditorNodeId !== '' || ui.pendingInlineDraft) {
            commitInlineEditor(document.activeElement);
            return true;
        }

        if (ui.nodeMenuId !== '') {
            ui.nodeMenuId = '';
            render();
            return true;
        }

        if (ui.drawer !== '') {
            ui.drawer = '';
            render();
            return true;
        }

        if (ui.inspectorOpen) {
            ui.inspectorOpen = false;
            render();
            return true;
        }

        return false;
    }

    function render() {
        var snapshot = currentSnapshot();
        ui.selectedNode = store.findNodeById(snapshot.selection.nodeId);
        ui.mediaEnabled = !!(boot.config && boot.config.media && boot.config.media.uploadUrl);
        root.classList.toggle('has-open-drawer', Boolean(ui.drawer));
        root.classList.toggle('has-open-inspector', ui.inspectorOpen);
        if (selectionName) {
            selectionName.textContent = ui.selectedNode
                ? String(ui.selectedNode.label || ui.selectedNode.title || ui.selectedNode.type || '')
                : String(labels.selectionEmpty || '');
        }

        var drawerTexts = drawerCopy(ui.drawer);
        if (drawerTitle) {
            drawerTitle.textContent = String(drawerTexts[0] || '');
        }
        if (drawerSubtitle) {
            drawerSubtitle.textContent = String(drawerTexts[1] || '');
        }

        drawerRoot.classList.toggle('is-open', Boolean(ui.drawer));
        inspectorRoot.classList.toggle('is-closed', !ui.inspectorOpen);
        railButtons.forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-drawer') === ui.drawer);
        });
        namespace.render.mount({
            stage: stage,
            drawerBody: drawerBody,
            inspectorBody: inspectorBody,
            inspectorTabs: inspectorTabs
        }, snapshot, ui, labels);
        syncCanvasFrames();
        syncCanvasImages();
        enhanceInspectorMediaFields();
        if (!interaction) {
            clearSnapFeedback();
        }
        syncRichTextEditor();
    }

    function richTextSurface(nodeId) {
        return stage
            ? stage.querySelector('.sfc-stage-node[data-node-id="' + String(nodeId || '') + '"][data-node-type="text"] > .sfc-stage-text')
            : null;
    }

    function isRichTextEditorShellTarget(target) {
        return target instanceof HTMLElement && !!target.closest('.sfc-studio-inline-editor');
    }

    function supportsRichTextEditor(node) {
        return !!node && node.type === 'text';
    }

    function setRichTextEditingState(nodeId, active) {
        var target = nodeElement(nodeId);
        if (!target) {
            return;
        }
        target.classList.toggle('is-richtext-editing', active === true);
    }

    function isInlineEditorTarget(target) {
        return target instanceof HTMLElement && target.getAttribute('data-inline-content') === 'true';
    }

    function isInlineEditorActive(nodeId) {
        return ui.inlineEditorNodeId !== '' && ui.inlineEditorNodeId === String(nodeId || '');
    }

    function setInlineEditorNode(nodeId) {
        ui.inlineEditorNodeId = String(nodeId || '');
    }

    function supportsInlineEditor(node) {
        return !!node && (node.type === 'button' || node.type === 'title');
    }

    function setCaretFromPoint(editor, x, y) {
        if (document.caretPositionFromPoint && window.getSelection) {
            var caretPosition = document.caretPositionFromPoint(x, y);
            if (caretPosition) {
                var positionRange = document.createRange();
                positionRange.setStart(caretPosition.offsetNode, caretPosition.offset);
                positionRange.collapse(true);
                var positionSelection = window.getSelection();
                if (positionSelection) {
                    positionSelection.removeAllRanges();
                    positionSelection.addRange(positionRange);
                }
                return true;
            }
        }

        if (document.caretRangeFromPoint && window.getSelection) {
            var legacyRange = document.caretRangeFromPoint(x, y);
            if (legacyRange) {
                var legacySelection = window.getSelection();
                if (legacySelection) {
                    legacySelection.removeAllRanges();
                    legacySelection.addRange(legacyRange);
                }
                return true;
            }
        }

        return false;
    }

    function focusInlineEditor(nodeId, options) {
        var focusOptions = options || {};
        window.requestAnimationFrame(function () {
            var editor = root.querySelector('[data-inline-content="true"][data-node-id="' + nodeId + '"]');
            if (!editor) {
                return;
            }

            editor.focus();

            if (focusOptions.point && setCaretFromPoint(editor, focusOptions.point.x, focusOptions.point.y)) {
                return;
            }

            if (focusOptions.placeCaretAtEnd !== true || !document.createRange || !window.getSelection) {
                return;
            }

            if (document.createRange && window.getSelection) {
                var range = document.createRange();
                range.selectNodeContents(editor);
                range.collapse(false);
                var selection = window.getSelection();
                if (selection) {
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
            }
        });
    }

    function openInlineEditor(nodeId, focusOptions) {
        var targetNodeId = String(nodeId || '');
        if (!targetNodeId) {
            return;
        }

        var targetNode = store.findNodeById(targetNodeId);
        if (!supportsInlineEditor(targetNode)) {
            return;
        }

        setInlineEditorNode(targetNodeId);

        if (currentSnapshot().selection.nodeId !== targetNodeId) {
            store.select(targetNodeId);
        } else {
            render();
        }

        focusInlineEditor(targetNodeId, focusOptions || {});
    }

    function renderRichTextPreview(html) {
        if (namespace.render && typeof namespace.render.richTextHtml === 'function') {
            return String(namespace.render.richTextHtml(html || '') || '');
        }
        return String(html || '');
    }

    function richTextEditorHtml(node) {
        return renderRichTextPreview(node && node.content ? node.content : '');
    }

    function richTextEditorLayout(nodeId) {
        if (!stageWrap) {
            return null;
        }

        var surface = richTextSurface(nodeId);
        var panel = inlineEditorRoot ? inlineEditorRoot.querySelector('.sfc-studio-inline-editor') : null;
        if (!surface) {
            return null;
        }

        var nodeRect = surface.getBoundingClientRect();
        var wrapRect = stageWrap.getBoundingClientRect();
        var toolbar = panel ? panel.querySelector('.se-toolbar') : null;
        var toolbarHeight = toolbar instanceof HTMLElement ? toolbar.offsetHeight : 0;
        var toolbarGap = 8;
        var width = Math.max(220, Math.round(nodeRect.width || 0));
        var height = Math.max(56, Math.round(nodeRect.height || 0));

        var left = Math.round(nodeRect.left - wrapRect.left);
        if (left + width > wrapRect.width) {
            left = Math.max(0, Math.round(wrapRect.width - width));
        }

        return {
            left: left,
            top: Math.round(nodeRect.top - wrapRect.top - toolbarHeight - toolbarGap),
            width: width,
            height: height
        };
    }

    function positionRichTextEditor() {
        if (!inlineEditorRoot || !ui.richTextEditorNodeId) {
            return;
        }

        var panel = inlineEditorRoot.querySelector('.sfc-studio-inline-editor');
        var layout = richTextEditorLayout(ui.richTextEditorNodeId);
        var surface = richTextSurface(ui.richTextEditorNodeId);
        if (!panel || !layout) {
            return;
        }

        panel.style.left = String(layout.left) + 'px';
        panel.style.top = String(layout.top) + 'px';
        panel.style.width = String(layout.width) + 'px';

        var toolbar = panel.querySelector('.sun-editor .se-toolbar');
        var wysiwyg = panel.querySelector('.sun-editor .se-wrapper .se-wrapper-wysiwyg');
        var wrapper = panel.querySelector('.sun-editor .se-wrapper');
        var inner = panel.querySelector('.sun-editor .se-wrapper .se-wrapper-inner');
        if (toolbar instanceof HTMLElement) {
            toolbar.style.maxWidth = String(Math.max(240, layout.width)) + 'px';
        }
        if (wrapper instanceof HTMLElement) {
            wrapper.style.minHeight = String(layout.height) + 'px';
            wrapper.style.height = String(layout.height) + 'px';
        }
        if (inner instanceof HTMLElement) {
            inner.style.minHeight = String(layout.height) + 'px';
            inner.style.height = String(layout.height) + 'px';
        }
        if (wysiwyg instanceof HTMLElement) {
            wysiwyg.style.minHeight = String(layout.height) + 'px';
            wysiwyg.style.height = String(layout.height) + 'px';
            if (surface instanceof HTMLElement) {
                var computed = window.getComputedStyle(surface);
                wysiwyg.style.fontFamily = computed.fontFamily;
                wysiwyg.style.fontSize = computed.fontSize;
                wysiwyg.style.lineHeight = computed.lineHeight;
                wysiwyg.style.fontWeight = computed.fontWeight;
                wysiwyg.style.letterSpacing = computed.letterSpacing;
                wysiwyg.style.color = computed.color;
                wysiwyg.style.textAlign = computed.textAlign;
            }
        }
    }

    function destroyRichTextEditor() {
        if (ui.richTextEditorNodeId) {
            setRichTextEditingState(ui.richTextEditorNodeId, false);
        }

        if (ui.richTextEditorHandle && typeof ui.richTextEditorHandle.destroy === 'function') {
            ui.richTextEditorHandle.destroy();
        }

        ui.richTextEditorHandle = null;
        ui.richTextEditorNodeId = '';

        if (inlineEditorRoot) {
            inlineEditorRoot.innerHTML = '';
        }
    }

    function syncRichTextEditor() {
        if (!inlineEditorRoot) {
            return;
        }

        var snapshot = currentSnapshot();
        var selectedNode = ui.selectedNode;
        if (
            snapshot.document.mode !== 'compose'
            || !supportsRichTextEditor(selectedNode)
            || ui.richTextEditorNodeId === ''
        ) {
            destroyRichTextEditor();
            return;
        }

        if (ui.richTextEditorNodeId !== selectedNode.id) {
            destroyRichTextEditor();
            return;
        }

        setRichTextEditingState(ui.richTextEditorNodeId, true);
        positionRichTextEditor();
    }

    function openRichTextEditor(nodeId) {
        var targetNodeId = String(nodeId || '');
        if (!targetNodeId || !inlineEditorRoot) {
            return;
        }

        var targetNode = store.findNodeById(targetNodeId);
        if (!supportsRichTextEditor(targetNode)) {
            return;
        }

        if (currentSnapshot().selection.nodeId !== targetNodeId) {
            store.select(targetNodeId);
            targetNode = store.findNodeById(targetNodeId);
        }

        destroyRichTextEditor();
        ui.richTextEditorNodeId = targetNodeId;
        setRichTextEditingState(targetNodeId, true);

        var panel = document.createElement('div');
        panel.className = 'sfc-studio-inline-editor';

        var textarea = document.createElement('textarea');
        textarea.className = 'sfc-studio-inline-editor-textarea';
        textarea.value = richTextEditorHtml(targetNode);

        panel.appendChild(textarea);
        inlineEditorRoot.appendChild(panel);
        positionRichTextEditor();

        if (!window.FlatCMSSunEditor || typeof window.FlatCMSSunEditor.create !== 'function') {
            return;
        }

        ui.richTextEditorHandle = window.FlatCMSSunEditor.create(textarea, {
            minHeight: '120px',
            height: Math.max(56, Math.round((richTextSurface(targetNodeId) || panel).getBoundingClientRect().height || 120)),
            resizingBar: false,
            stickyToolbar: -1,
            applyAccordion: false,
            enableHiliteColor: true,
            includeColorStateInTooltip: false,
            buttonList: [[
                'font',
                'fontSize',
                'formatBlock',
                'link',
                'undo',
                'redo',
                'bold',
                'underline',
                'italic',
                'strike',
                'fontColor',
                'align',
                'list',
                'horizontalRule',
                'hiliteColor'
            ]],
            onInput: function (html) {
                var nextHtml = String(html || '');
                store.updateNodeSilently(targetNodeId, function (node) {
                    node.content = nextHtml;
                });
                var surface = richTextSurface(targetNodeId);
                if (surface) {
                    surface.innerHTML = renderRichTextPreview(nextHtml);
                }
            },
            onChange: function (html) {
                var nextHtml = String(html || '');
                store.updateNodeSilently(targetNodeId, function (node) {
                    node.content = nextHtml;
                });
                var surface = richTextSurface(targetNodeId);
                if (surface) {
                    surface.innerHTML = renderRichTextPreview(nextHtml);
                }
            },
            onReady: function (editor) {
                positionRichTextEditor();
                panel.addEventListener('click', function () {
                    window.setTimeout(function () {
                        positionRichTextEditor();
                    }, 0);
                }, true);
                window.setTimeout(function () {
                    try {
                        if (editor && editor.core && typeof editor.core.focus === 'function') {
                            editor.core.focus();
                        }
                    } catch (error) {
                        // no-op
                    }
                }, 0);
            }
        });

        positionRichTextEditor();
    }

    function commitRichTextEditor() {
        if (!ui.richTextEditorNodeId) {
            return;
        }

        if (ui.richTextEditorHandle && typeof ui.richTextEditorHandle.getHtml === 'function') {
            var html = String(ui.richTextEditorHandle.getHtml() || '');
            store.updateNodeSilently(ui.richTextEditorNodeId, function (node) {
                node.content = html;
            });
            var surface = richTextSurface(ui.richTextEditorNodeId);
            if (surface) {
                surface.innerHTML = renderRichTextPreview(html);
            }
        }

        destroyRichTextEditor();
    }

    function queuePendingInspectorInput(target) {
        if (!ui.selectedNode) {
            return;
        }

        if (!(target instanceof window.HTMLInputElement)
            && !(target instanceof window.HTMLTextAreaElement)
            && !(target instanceof window.HTMLSelectElement)) {
            return;
        }

        if (target.getAttribute('data-action') !== 'field-input') {
            return;
        }

        var field = target.getAttribute('data-field');
        if (!field) {
            return;
        }

        ui.pendingFieldDraft = {
            field: field,
            nodeId: ui.selectedNode.id,
            value: target.value
        };
    }

    function commitPendingInspectorInput() {
        var activeField = document.activeElement;
        queuePendingInspectorInput(activeField);

        if (!ui.pendingFieldDraft) {
            return;
        }

        var fieldDraft = ui.pendingFieldDraft;
        var targetNode = store.findNodeById(fieldDraft.nodeId);
        ui.pendingFieldDraft = null;
        if (!targetNode || fieldValue(targetNode, fieldDraft.field) === String(fieldDraft.value)) {
            return;
        }

        store.updateNode(fieldDraft.nodeId, function (node) {
            applyFieldValue(node, fieldDraft.field, fieldDraft.value);
        });
    }

    function queueInlineEditor(target) {
        if (!isInlineEditorTarget(target)) {
            return;
        }

        var nodeId = String(target.getAttribute('data-node-id') || '');
        if (!nodeId || !isInlineEditorActive(nodeId)) {
            return;
        }

        ui.pendingInlineDraft = {
            content: String(target.textContent || ''),
            nodeId: nodeId
        };
    }

    function commitInlineEditor(target) {
        var activeInlineNodeId = ui.inlineEditorNodeId;
        if (!isInlineEditorTarget(target)) {
            if (activeInlineNodeId !== '') {
                target = root.querySelector('[data-inline-content="true"][data-node-id="' + activeInlineNodeId + '"]');
            } else if (supportsInlineEditor(ui.selectedNode)) {
                target = root.querySelector('[data-inline-content="true"][data-node-id="' + ui.selectedNode.id + '"]');
            }
        }

        queueInlineEditor(target);

        var shouldRerender = ui.inlineEditorNodeId !== '';
        setInlineEditorNode('');

        if (!ui.pendingInlineDraft) {
            if (shouldRerender) {
                render();
            }
            return;
        }

        var inlineDraft = ui.pendingInlineDraft;
        var node = store.findNodeById(inlineDraft.nodeId);
        ui.pendingInlineDraft = null;
        if (!node || node.content === inlineDraft.content) {
            if (shouldRerender) {
                render();
            }
            return;
        }

        store.updateNode(inlineDraft.nodeId, function (entry) {
            entry.content = inlineDraft.content;
        });
    }

    function commitActiveEditor() {
        var activeField = document.activeElement;
        if (ui.richTextEditorNodeId !== '') {
            commitRichTextEditor();
        }

        if (ui.inlineEditorNodeId !== '' || ui.pendingInlineDraft) {
            commitInlineEditor(activeField);
        } else if (activeField instanceof HTMLElement && activeField.getAttribute('data-inline-content') === 'true') {
            commitInlineEditor(null);
        }

        if (activeField instanceof HTMLElement) {
            queuePendingInspectorInput(activeField);
        }

        commitPendingInspectorInput();
    }

    function makeSectionWrapper(children, appearance, label) {
        return {
            id: store.createId('section'),
            type: 'section',
            label: label || labels.nodeSection || '',
            enabled: true,
            appearance: appearance || 'none',
            direction: 'vertical',
            frame: defaultFrame(),
            children: children
        };
    }

    function makeSingleNodeSection(node, label) {
        return makeSectionWrapper([node], 'none', label);
    }

    function preferredInsertionContainer(node) {
        if (!node || typeof node !== 'object') {
            return null;
        }

        if (node.type === 'stack') {
            return node;
        }

        if (node.type !== 'section' || !Array.isArray(node.children)) {
            return node.type === 'region' ? node : null;
        }

        if (node.children.length === 1
            && node.children[0]
            && node.children[0].type === 'stack') {
            return node.children[0];
        }

        return node;
    }

    function insertIntoCurrentFlow(node) {
        var selectedNode = ui.selectedNode;
        var wrappedNode = node.type === 'section' ? node : makeSingleNodeSection(node, labels.nodeSection || '');
        var parentNode = selectedNode ? store.findParentNode(selectedNode.id) : null;
        var preferredContainer = preferredInsertionContainer(selectedNode);

        if (preferredContainer && preferredContainer.type === 'region') {
            if (preferredContainer.tag === 'main' && store.appendChild(preferredContainer.id, wrappedNode)) {
                return;
            }

            if (store.appendChild(preferredContainer.id, node)) {
                return;
            }
        }

        if (preferredContainer && preferredContainer.type !== 'region' && store.appendChild(preferredContainer.id, node)) {
            return;
        }

        if (selectedNode && parentNode && parentNode.type !== 'region' && store.appendChild(parentNode.id, node)) {
            return;
        }

        if (selectedNode && parentNode && parentNode.type === 'region') {
            if (store.insertAfter(selectedNode.id, wrappedNode)) {
                return;
            }
        }

        store.appendToMainSection(wrappedNode);
    }

    function makeTextNode() {
        return {
            id: store.createId('text'),
            type: 'text',
            label: labels.nodeText || '',
            enabled: true,
            content: labels.actionAddText || '',
            frame: defaultFrame()
        };
    }

    function makeTitleNode() {
        return {
            id: store.createId('title'),
            type: 'title',
            label: labels.nodeTitle || '',
            enabled: true,
            content: labels.actionAddTitle || '',
            level: 'h2',
            frame: defaultFrame()
        };
    }

    function makeImageNode() {
        var frame = defaultFrame();
        frame.height = 240;

        return {
            id: store.createId('image'),
            type: 'image',
            label: labels.nodeImage || '',
            enabled: true,
            src: '',
            alt: '',
            frame: frame
        };
    }

    function makeButtonRow() {
        return {
            id: store.createId('actions'),
            type: 'stack',
            label: labels.actionAddButtons || '',
            enabled: true,
            appearance: 'none',
            direction: 'horizontal',
            frame: defaultFrame(),
            children: [
                {
                    id: store.createId('button'),
                    type: 'button',
                    label: labels.nodeButton || '',
                    enabled: true,
                    content: labels.variantPrimary || '',
                    url: '/contact',
                    variant: 'primary',
                    frame: defaultFrame()
                },
                {
                    id: store.createId('button'),
                    type: 'button',
                    label: labels.nodeButton || '',
                    enabled: true,
                    content: labels.variantSecondary || '',
                    url: '/page',
                    variant: 'secondary',
                    frame: defaultFrame()
                }
            ]
        };
    }

    function makeSection() {
        return makeSectionWrapper([
            {
                id: store.createId('stack'),
                type: 'stack',
                label: labels.nodeStack || '',
                enabled: true,
                appearance: 'none',
                direction: 'vertical',
                frame: defaultFrame(),
                children: [
                    makeTitleNode(),
                    makeTextNode()
                ]
            }
        ], 'soft', labels.nodeSection || '');
    }

    function toggleAsideRegion() {
        var snapshot = currentSnapshot();
        var region = (snapshot.document.regions || []).find(function (entry) {
            return entry && entry.tag === 'aside';
        });
        if (!region) {
            return;
        }
        store.updateNode(region.id, function (target) {
            target.enabled = target.enabled === false;
        });
        store.select(region.id);
    }

    function resetDocument() {
        if (!window.confirm(String(labels.actionResetConfirm || ''))) {
            return;
        }

        var resetDocumentData = boot.defaultDocument || boot.document || {};
        store.replaceDocument(resetDocumentData);
        store.select(resetDocumentData.id || '');
        ui.drawer = '';
        ui.inspectorOpen = false;
        render();
    }

    function onAction(action, trigger) {
        if (action !== 'zoom') {
            commitActiveEditor();
        }

        var snapshot = currentSnapshot();

        if (action === 'switch-mode') {
            store.updateDocument(function (documentData) {
                documentData.mode = String(trigger.getAttribute('data-mode') || 'compose');
            });
            return;
        }

        if (action === 'viewport') {
            store.updateDocument(function (documentData) {
                documentData.viewport = String(trigger.getAttribute('data-viewport') || 'desktop');
            });
            return;
        }

        if (action === 'zoom') {
            store.updateDocument(function (documentData) {
                documentData.zoom = Number(trigger.value || 100);
            });
            return;
        }

        if (action === 'toggle-drawer') {
            var nextDrawer = String(trigger.getAttribute('data-drawer') || '');
            ui.drawer = ui.drawer === nextDrawer ? '' : nextDrawer;
            render();
            return;
        }

        if (action === 'close-drawer') {
            ui.drawer = '';
            render();
            return;
        }

        if (action === 'close-inspector') {
            ui.inspectorOpen = false;
            render();
            return;
        }

        if (action === 'open-inspector') {
            ui.inspectorOpen = true;
            render();
            return;
        }

        if (action === 'select-page') {
            store.select(snapshot.document.id || '');
            return;
        }

        if (action === 'select-node') {
            var nodeId = String(trigger.getAttribute('data-node-id') || '');
            ui.nodeMenuId = '';
            store.select(nodeId);
            return;
        }

        if (action === 'delete-node') {
            var deleteNodeId = String(trigger.getAttribute('data-node-id') || '');
            if (deleteNodeId) {
                ui.nodeMenuId = '';
                store.removeNode(deleteNodeId);
            }
            return;
        }

        if (action === 'duplicate-node') {
            var duplicateNodeId = String(trigger.getAttribute('data-node-id') || '');
            if (duplicateNodeId) {
                ui.nodeMenuId = '';
                store.duplicateNode(duplicateNodeId);
            }
            return;
        }

        if (action === 'toggle-node-menu') {
            var menuNodeId = String(trigger.getAttribute('data-node-id') || '');
            if (!menuNodeId) {
                return;
            }
            ui.nodeMenuId = ui.nodeMenuId === menuNodeId ? '' : menuNodeId;
            if (snapshot.selection.nodeId !== menuNodeId) {
                store.select(menuNodeId);
                return;
            }
            render();
            return;
        }

        if (action === 'open-node-inspector') {
            var inspectorNodeId = String(trigger.getAttribute('data-node-id') || '');
            if (inspectorNodeId) {
                ui.nodeMenuId = '';
                if (snapshot.selection.nodeId !== inspectorNodeId) {
                    store.select(inspectorNodeId);
                }
                ui.inspectorOpen = true;
                render();
            }
            return;
        }

        if (action === 'move-node-up' || action === 'move-node-down') {
            var moveNodeId = String(trigger.getAttribute('data-node-id') || '');
            if (moveNodeId) {
                store.moveNode(moveNodeId, action === 'move-node-up' ? 'up' : 'down');
            }
            return;
        }

        if (action === 'switch-tab') {
            store.setTab(String(trigger.getAttribute('data-tab') || 'design'));
            ui.inspectorOpen = true;
            render();
            return;
        }

        if (action === 'add-section') {
            insertIntoCurrentFlow(makeSection());
            return;
        }

        if (action === 'add-text') {
            insertIntoCurrentFlow(makeTextNode());
            return;
        }

        if (action === 'add-title') {
            insertIntoCurrentFlow(makeTitleNode());
            return;
        }

        if (action === 'add-image') {
            insertIntoCurrentFlow(makeImageNode());
            return;
        }

        if (action === 'add-buttons') {
            insertIntoCurrentFlow(makeButtonRow());
            return;
        }

        if (action === 'toggle-aside') {
            toggleAsideRegion();
            return;
        }

        if (action === 'reset-document') {
            resetDocument();
            return;
        }

        if (action === 'preview') {
            namespace.api.showToast(labels.previewPending || '', 'warning');
            return;
        }

        if (action === 'save') {
            saveCurrentDocument();
        }
    }

    root.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (suppressClick) {
            event.preventDefault();
            event.stopPropagation();
            suppressClick = false;
            return;
        }

        if (isRichTextEditorShellTarget(target)) {
            return;
        }

        var composeButtonLink = target.closest('.sfc-stage-page[data-mode="compose"] .sfc-stage-button');
        if (composeButtonLink) {
            event.preventDefault();
        }

        var richTextNode = target.closest('.sfc-stage-node[data-node-type="text"][data-node-id]');
        if (richTextNode && event.detail >= 2) {
            event.preventDefault();
            event.stopPropagation();
            openRichTextEditor(String(richTextNode.getAttribute('data-node-id') || ''));
            return;
        }

        var inlineTarget = target.closest('[data-inline-content="true"][data-node-id]');
        if (inlineTarget && isInlineEditorActive(inlineTarget.getAttribute('data-node-id') || '')) {
            return;
        }

        if (inlineTarget && event.detail >= 2) {
            event.preventDefault();
            event.stopPropagation();
            openInlineEditor(String(inlineTarget.getAttribute('data-node-id') || ''), {
                point: {
                    x: event.clientX,
                    y: event.clientY
                }
            });
            return;
        }

        var trigger = target.closest('[data-action]');
        if (!trigger) {
            if (ui.nodeMenuId !== '') {
                ui.nodeMenuId = '';
                render();
            }
            return;
        }

        var action = String(trigger.getAttribute('data-action') || '');
        if (action === 'field-input' || action === 'field-toggle' || action === 'zoom') {
            return;
        }

        event.preventDefault();

        onAction(action, trigger);
    });

    root.addEventListener('pointerdown', function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (event.button !== 0) {
            return;
        }

        if (isRichTextEditorShellTarget(target)) {
            return;
        }

        var resizeTrigger = target.closest('[data-resize-handle][data-node-id]');
        if (resizeTrigger) {
            beginInteraction(
                'resize',
                String(resizeTrigger.getAttribute('data-node-id') || ''),
                String(resizeTrigger.getAttribute('data-resize-handle') || ''),
                event
            );
            return;
        }

        if (target.closest('[data-node-actionbar="true"]') || target.closest('.sfc-stage-node-menu-panel')) {
            return;
        }

        var activeInlineTarget = target.closest('[data-inline-content="true"][data-node-id]');
        if (activeInlineTarget && isInlineEditorActive(activeInlineTarget.getAttribute('data-node-id') || '')) {
            return;
        }

        var dragTrigger = target.closest('.sfc-stage-node[data-node-id]');
        if (dragTrigger) {
            beginInteraction(
                'move',
                String(dragTrigger.getAttribute('data-node-id') || ''),
                'move',
                event
            );
        }
    });

    window.addEventListener('pointermove', function (event) {
        if (!interaction) {
            return;
        }

        var nextFrame = computeInteractionFrame(event.clientX, event.clientY);
        if (!nextFrame) {
            return;
        }

        interaction.liveFrame = nextFrame;
        interaction.moved = Math.abs(event.clientX - interaction.startX) > 2 || Math.abs(event.clientY - interaction.startY) > 2;
        applyFrameToElement(nodeElement(interaction.nodeId), nextFrame);
        event.preventDefault();
    }, { passive: false });

    window.addEventListener('pointerup', function () {
        finishInteraction(true);
    });

    window.addEventListener('pointercancel', function () {
        finishInteraction(false);
    });

    window.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (handleEscape()) {
                event.preventDefault();
            }
            return;
        }

        if ((event.metaKey || event.ctrlKey) && !event.altKey && String(event.key || '').toLowerCase() === 's') {
            event.preventDefault();
            saveCurrentDocument();
            return;
        }

        if (interaction || ui.richTextEditorNodeId !== '' || ui.inlineEditorNodeId !== '') {
            return;
        }

        if (keyboardEditableTarget(event.target)) {
            return;
        }

        if ((event.metaKey || event.ctrlKey) && !event.altKey && String(event.key || '').toLowerCase() === 'd') {
            if (duplicateSelectedNode()) {
                event.preventDefault();
            }
            return;
        }

        if (!event.metaKey && !event.ctrlKey && !event.altKey && (event.key === 'Delete' || event.key === 'Backspace')) {
            if (deleteSelectedNode()) {
                event.preventDefault();
            }
            return;
        }

        if (event.altKey && !event.metaKey && !event.ctrlKey && (event.key === 'ArrowUp' || event.key === 'ArrowDown')) {
            if (moveSelectedNode(event.key === 'ArrowUp' ? 'up' : 'down')) {
                event.preventDefault();
            }
            return;
        }

        if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.altKey) {
            return;
        }

        if (event.key === 'Enter' && openSelectedEditor()) {
            event.preventDefault();
            return;
        }

        var step = event.shiftKey ? 10 : 1;
        var deltaX = 0;
        var deltaY = 0;

        if (event.key === 'ArrowLeft') {
            deltaX = -step;
        } else if (event.key === 'ArrowRight') {
            deltaX = step;
        } else if (event.key === 'ArrowUp') {
            deltaY = -step;
        } else if (event.key === 'ArrowDown') {
            deltaY = step;
        } else {
            return;
        }

        if (nudgeSelectedNode(deltaX, deltaY)) {
            event.preventDefault();
        }
    });

    root.addEventListener('change', function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        var action = target.getAttribute('data-action');
        if (action === 'zoom') {
            onAction('zoom', target);
            return;
        }

        if (action === 'switch-source-page') {
            commitActiveEditor();
            var currentUrl = ui.currentSource && ui.currentSource.studio_url ? String(ui.currentSource.studio_url) : '';
            var nextUrl = String(target.value || '').trim();

            if (nextUrl === '' || nextUrl === currentUrl) {
                target.value = currentUrl;
                return;
            }

            if (store.getSnapshot().dirty && !window.confirm(labels.actionSwitchSourceConfirm || '')) {
                target.value = currentUrl;
                return;
            }

            window.location.href = nextUrl;
            return;
        }

        if (action === 'field-input') {
            queuePendingInspectorInput(target);
            commitPendingInspectorInput();
            return;
        }

        var field = target.getAttribute('data-field');
        if (!field || !ui.selectedNode) {
            return;
        }

        store.updateNode(ui.selectedNode.id, function (node) {
            if (action === 'field-toggle') {
                applyFieldValue(node, field, Boolean(target.checked));
                return;
            }

            applyFieldValue(node, field, target.value);
        });
    });

    root.addEventListener('input', function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (target.getAttribute('data-inline-content') === 'true') {
            queueInlineEditor(target);
            var inlineNodeId = String(target.getAttribute('data-node-id') || '');
            if (inlineNodeId) {
                store.updateNodeSilently(inlineNodeId, function (node) {
                    node.content = String(target.textContent || '');
                });
            }
            return;
        }

        queuePendingInspectorInput(target);
        if (!ui.selectedNode) {
            return;
        }

        var field = target.getAttribute('data-field');
        if (!field) {
            return;
        }

        store.updateNodeSilently(ui.selectedNode.id, function (node) {
            applyFieldValue(node, field, target.value);
        });
        if (field.indexOf('frame') === 0) {
            applyFrameToElement(nodeElement(ui.selectedNode.id), nodeFrame(store.findNodeById(ui.selectedNode.id)));
        }
    });

    root.addEventListener('blur', function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (target.getAttribute('data-inline-content') === 'true') {
            commitInlineEditor(target);
        }
    }, true);

    root.addEventListener('blur', function (event) {
        var target = event.target;
        if (!(target instanceof window.HTMLInputElement)
            && !(target instanceof window.HTMLTextAreaElement)
            && !(target instanceof window.HTMLSelectElement)) {
            return;
        }

        queuePendingInspectorInput(target);
        commitPendingInspectorInput();
    }, true);

    if (canvasScroll) {
        canvasScroll.addEventListener('scroll', function () {
            if (interaction) {
                clearSnapFeedback();
            }
            positionRichTextEditor();
        }, { passive: true });
    }

    window.addEventListener('resize', function () {
        if (interaction) {
            clearSnapFeedback();
        }
        positionRichTextEditor();
    });

    ensureSnapLayer();
    store.subscribe(render);
    render();
}(window, document));
