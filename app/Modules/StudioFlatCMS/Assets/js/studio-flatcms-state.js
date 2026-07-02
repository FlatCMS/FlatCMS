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

        function findNodePositionInChildren(children, nodeId, parent) {
            if (!Array.isArray(children)) {
                return null;
            }

            for (var index = 0; index < children.length; index += 1) {
                var child = children[index];
                if (!child || typeof child !== 'object') {
                    continue;
                }

                if (child.id === nodeId) {
                    return {
                        parent: parent,
                        collection: children,
                        index: index,
                        node: child
                    };
                }

                var nested = findNodePositionInChildren(child.children, nodeId, child);
                if (nested) {
                    return nested;
                }
            }

            return null;
        }

        function findNodePosition(nodeId) {
            var regions = Array.isArray(state.document.regions) ? state.document.regions : [];
            for (var index = 0; index < regions.length; index += 1) {
                var region = regions[index];
                if (!region || typeof region !== 'object') {
                    continue;
                }

                if (region.id === nodeId) {
                    return {
                        parent: state.document,
                        collection: regions,
                        index: index,
                        node: region
                    };
                }

                var nested = findNodePositionInChildren(region.children, nodeId, region);
                if (nested) {
                    return nested;
                }
            }

            return null;
        }

        function findParentNode(nodeId) {
            var position = findNodePosition(nodeId);
            return position ? position.parent : null;
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

        function insertAfter(nodeId, node) {
            var position = findNodePosition(nodeId);
            if (!position || !Array.isArray(position.collection)) {
                return false;
            }

            position.collection.splice(position.index + 1, 0, node);
            state.selection.nodeId = node.id;
            state.dirty = true;
            emit();
            return true;
        }

        function refreshNodeIds(node) {
            if (!node || typeof node !== 'object') {
                return;
            }

            node.id = createId(String(node.type || 'node'));

            if (!Array.isArray(node.children)) {
                return;
            }

            node.children.forEach(function (child) {
                refreshNodeIds(child);
            });
        }

        function offsetDuplicatedNode(node) {
            if (!node || typeof node !== 'object') {
                return;
            }

            if (node.type === 'section' || node.type === 'region') {
                return;
            }

            if (!node.frame || typeof node.frame !== 'object') {
                return;
            }

            node.frame.offsetX = Number(node.frame.offsetX || 0) + 24;
            node.frame.offsetY = Number(node.frame.offsetY || 0) + 24;
        }

        function duplicateNode(nodeId) {
            var position = findNodePosition(nodeId);
            if (!position || !Array.isArray(position.collection)) {
                return false;
            }

            var duplicated = clone(position.node);
            refreshNodeIds(duplicated);
            offsetDuplicatedNode(duplicated);
            position.collection.splice(position.index + 1, 0, duplicated);
            state.selection.nodeId = duplicated.id;
            state.dirty = true;
            emit();
            return true;
        }

        function moveNode(nodeId, direction) {
            var position = findNodePosition(nodeId);
            if (!position || !Array.isArray(position.collection)) {
                return false;
            }

            var nextIndex = direction === 'up'
                ? position.index - 1
                : position.index + 1;

            if (nextIndex < 0 || nextIndex >= position.collection.length) {
                return false;
            }

            position.collection.splice(position.index, 1);
            position.collection.splice(nextIndex, 0, position.node);
            state.selection.nodeId = nodeId;
            state.dirty = true;
            emit();
            return true;
        }

        function removeNode(nodeId) {
            var position = findNodePosition(nodeId);
            if (!position || !Array.isArray(position.collection) || position.parent === state.document) {
                return false;
            }

            var fallback = state.document.id || '';
            if (position.collection[position.index - 1] && position.collection[position.index - 1].id) {
                fallback = position.collection[position.index - 1].id;
            } else if (position.collection[position.index + 1] && position.collection[position.index + 1].id) {
                fallback = position.collection[position.index + 1].id;
            } else if (position.parent && position.parent.id) {
                fallback = position.parent.id;
            }

            position.collection.splice(position.index, 1);
            state.selection.nodeId = fallback;
            state.dirty = true;
            emit();
            return true;
        }

        return {
            subscribe: subscribe,
            getSnapshot: getSnapshot,
            findNodeById: findNodeById,
            findRegionById: findRegionById,
            findParentNode: findParentNode,
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
            insertAfter: insertAfter,
            duplicateNode: duplicateNode,
            moveNode: moveNode,
            removeNode: removeNode
        };
    }

    namespace.state = {
        createStore: createStore
    };
}(window));
