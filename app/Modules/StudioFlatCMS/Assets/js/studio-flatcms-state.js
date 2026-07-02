/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function (window) {
    'use strict';

    var namespace = window.FlatCMSStudioFlatCMS = window.FlatCMSStudioFlatCMS || {};

    function clone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function visitNodes(items, callback) {
        (items || []).forEach(function (item) {
            if (!item || typeof item !== 'object') {
                return;
            }
            callback(item);
            if (Array.isArray(item.children)) {
                visitNodes(item.children, callback);
            }
        });
    }

    function createStore(initialDocument) {
        var state = {
            document: clone(initialDocument || {}),
            selection: {
                nodeId: '',
                tab: 'design'
            },
            dirty: false
        };
        var listeners = [];

        function emit() {
            var snapshot = getSnapshot();
            listeners.forEach(function (listener) {
                listener(snapshot);
            });
        }

        function getSnapshot() {
            return {
                document: clone(state.document),
                selection: clone(state.selection),
                dirty: state.dirty
            };
        }

        function subscribe(listener) {
            listeners.push(listener);
            return function () {
                listeners = listeners.filter(function (entry) {
                    return entry !== listener;
                });
            };
        }

        function findRegionById(regionId) {
            return (state.document.regions || []).find(function (region) {
                return region && region.id === regionId;
            }) || null;
        }

        function findNodeById(nodeId) {
            var found = null;
            (state.document.regions || []).forEach(function (region) {
                if (found || !region) {
                    return;
                }
                if (region.id === nodeId) {
                    found = region;
                    return;
                }
                visitNodes(region.children || [], function (node) {
                    if (!found && node.id === nodeId) {
                        found = node;
                    }
                });
            });
            if (!found && state.document && state.document.id === nodeId) {
                found = state.document;
            }
            return found;
        }

        function updateDocument(mutator) {
            mutator(state.document);
            state.dirty = true;
            emit();
        }

        function updateNode(nodeId, mutator) {
            var target = findNodeById(nodeId);
            if (!target) {
                return;
            }
            mutator(target);
            state.dirty = true;
            emit();
        }

        function updateNodeSilently(nodeId, mutator) {
            var target = findNodeById(nodeId);
            if (!target) {
                return;
            }
            mutator(target);
            state.dirty = true;
        }

        function replaceDocument(nextDocument) {
            state.document = clone(nextDocument || {});
            state.dirty = false;
            emit();
        }

        function markSaved(nextDocument) {
            if (nextDocument && typeof nextDocument === 'object') {
                state.document = clone(nextDocument);
            }
            state.dirty = false;
            emit();
        }

        function select(nodeId) {
            state.selection.nodeId = String(nodeId || '');
            emit();
        }

        function setTab(tab) {
            state.selection.tab = String(tab || 'design');
            emit();
        }

        function createId(prefix) {
            return [prefix, Date.now(), Math.floor(Math.random() * 1000)].join('-');
        }

        function appendToMainSection(node) {
            updateDocument(function (documentData) {
                var mainRegion = (documentData.regions || []).find(function (region) {
                    return region && region.tag === 'main';
                });
                if (!mainRegion) {
                    return;
                }
                if (!Array.isArray(mainRegion.children)) {
                    mainRegion.children = [];
                }
                mainRegion.children.push(node);
                state.selection.nodeId = node.id;
            });
        }

        function appendChild(containerId, node) {
            var container = findNodeById(containerId);
            if (!container || !Object.prototype.hasOwnProperty.call(container, 'children')) {
                return false;
            }

            if (!Array.isArray(container.children)) {
                container.children = [];
            }

            container.children.push(node);
            state.selection.nodeId = node.id;
            state.dirty = true;
            emit();
            return true;
        }

        function removeNodeFromChildren(children, nodeId) {
            if (!Array.isArray(children)) {
                return false;
            }

            for (var index = 0; index < children.length; index += 1) {
                var child = children[index];
                if (!child || typeof child !== 'object') {
                    continue;
                }

                if (child.id === nodeId) {
                    children.splice(index, 1);
                    return true;
                }

                if (removeNodeFromChildren(child.children, nodeId)) {
                    return true;
                }
            }

            return false;
        }

        function removeNode(nodeId) {
            var removed = false;
            (state.document.regions || []).forEach(function (region) {
                if (removed || !region) {
                    return;
                }
                removed = removeNodeFromChildren(region.children, nodeId);
            });

            if (!removed) {
                return false;
            }

            state.selection.nodeId = state.document.id || '';
            state.dirty = true;
            emit();
            return true;
        }

        return {
            subscribe: subscribe,
            getSnapshot: getSnapshot,
            findNodeById: findNodeById,
            findRegionById: findRegionById,
            updateDocument: updateDocument,
            updateNode: updateNode,
            updateNodeSilently: updateNodeSilently,
            replaceDocument: replaceDocument,
            markSaved: markSaved,
            select: select,
            setTab: setTab,
            createId: createId,
            appendToMainSection: appendToMainSection,
            appendChild: appendChild,
            removeNode: removeNode
        };
    }

    namespace.state = {
        createStore: createStore
    };
}(window));
