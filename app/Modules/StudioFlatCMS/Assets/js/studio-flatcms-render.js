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

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function hasRichTextMarkup(value) {
        return /<([a-z][^>]*)>/i.test(String(value || ''));
    }

    function isAllowedRichTextTag(tagName) {
        return [
            'a',
            'b',
            'blockquote',
            'br',
            'div',
            'em',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'i',
            'li',
            'ol',
            'p',
            's',
            'span',
            'strike',
            'strong',
            'u',
            'ul'
        ].indexOf(String(tagName || '').toLowerCase()) !== -1;
    }

    function isAllowedRichTextHref(value) {
        var href = String(value || '').trim();
        if (href === '') {
            return false;
        }

        if (href.indexOf('/') === 0 || href.indexOf('#') === 0) {
            return true;
        }

        return /^(https?:|mailto:|tel:)/i.test(href);
    }

    function sanitizeRichTextAttributes(element) {
        if (!(element instanceof HTMLElement)) {
            return;
        }

        var tagName = String(element.tagName || '').toLowerCase();
        var allowedAttributes = tagName === 'a' ? ['href', 'target', 'rel'] : [];

        Array.prototype.slice.call(element.attributes || []).forEach(function (attribute) {
            if (allowedAttributes.indexOf(attribute.name) === -1) {
                element.removeAttribute(attribute.name);
            }
        });

        if (tagName !== 'a') {
            return;
        }

        var href = String(element.getAttribute('href') || '').trim();
        if (!isAllowedRichTextHref(href)) {
            element.removeAttribute('href');
        } else {
            element.setAttribute('href', href);
        }

        var target = String(element.getAttribute('target') || '').trim();
        if (target === '_blank') {
            element.setAttribute('target', '_blank');
            element.setAttribute('rel', 'noopener noreferrer');
            return;
        }

        element.removeAttribute('target');
        element.removeAttribute('rel');
    }

    function sanitizeRichTextNode(node) {
        Array.prototype.slice.call(node.childNodes || []).forEach(function (child) {
            if (child.nodeType === window.Node.TEXT_NODE) {
                return;
            }

            if (child.nodeType !== window.Node.ELEMENT_NODE) {
                node.removeChild(child);
                return;
            }

            var tagName = String(child.tagName || '').toLowerCase();
            if (!isAllowedRichTextTag(tagName)) {
                while (child.firstChild) {
                    node.insertBefore(child.firstChild, child);
                }
                node.removeChild(child);
                return;
            }

            sanitizeRichTextAttributes(child);
            sanitizeRichTextNode(child);
        });
    }

    function plainTextToRichHtml(value) {
        var normalized = String(value || '').replace(/\r\n/g, '\n').trim();
        if (normalized === '') {
            return '';
        }

        return normalized.split(/\n{2,}/).map(function (chunk) {
            return '<p>' + escapeHtml(chunk).replace(/\n/g, '<br>') + '</p>';
        }).join('');
    }

    function renderRichTextContent(value) {
        var raw = String(value || '').trim();
        if (raw === '') {
            return '';
        }

        if (!hasRichTextMarkup(raw)) {
            return plainTextToRichHtml(raw);
        }

        if (typeof window.DOMParser === 'undefined') {
            return plainTextToRichHtml(raw);
        }

        try {
            var parser = new window.DOMParser();
            var doc = parser.parseFromString('<div>' + raw + '</div>', 'text/html');
            var root = doc.body && doc.body.firstElementChild;
            if (!root) {
                return plainTextToRichHtml(raw);
            }
            sanitizeRichTextNode(root);
            return String(root.innerHTML || '');
        } catch (error) {
            return plainTextToRichHtml(raw);
        }
    }

    function viewportWidth(viewport) {
        if (viewport === 'tablet') {
            return '820px';
        }
        if (viewport === 'mobile') {
            return '420px';
        }
        return '1180px';
    }

    function viewportLabel(labels, viewport) {
        if (viewport === 'tablet') {
            return labels.viewportTablet || '';
        }
        if (viewport === 'mobile') {
            return labels.viewportMobile || '';
        }
        return labels.viewportDesktop || '';
    }

    function renderMenu(items) {
        return '<nav class="sfc-stage-menu">' + (items || []).map(function (item) {
            return '<a href="' + escapeHtml(item.url || '#') + '">' + escapeHtml(item.label || '') + '</a>';
        }).join('') + '</nav>';
    }

    function numberValue(value) {
        return value == null ? '' : String(value);
    }

    function nodeFrame(node) {
        var frame = node && typeof node === 'object' && node.frame && typeof node.frame === 'object'
            ? node.frame
            : {};

        return {
            offsetX: Number(frame.offsetX || 0),
            offsetY: Number(frame.offsetY || 0),
            width: frame.width == null ? null : Number(frame.width || 0),
            height: frame.height == null ? null : Number(frame.height || 0)
        };
    }

    function renderNodeChrome(node, selection, mode, labels, ui) {
        if (mode !== 'compose') {
            return '';
        }

        var isActive = selection.nodeId === node.id;
        var activeClass = isActive ? ' is-active' : '';
        var menuOpenClass = ui && ui.nodeMenuId === node.id ? ' is-open' : '';
        var handles = ['n', 'e', 's', 'w'];
        var nodeTitle = String(node.label || node.title || node.type || '');

        return '<div class="sfc-stage-node-chrome' + activeClass + '">'
            + '<div class="sfc-stage-node-actionbar" data-node-actionbar="true">'
            + '<span class="sfc-stage-node-actiontitle">' + escapeHtml(nodeTitle) + '</span>'
            + '<div class="sfc-stage-node-actions">'
            + '<button type="button" class="sfc-stage-node-tool sfc-stage-node-menu-toggle" data-action="toggle-node-menu" data-node-id="' + escapeHtml(node.id) + '" aria-label="' + escapeHtml(labels.actionMore || '') + '" aria-expanded="' + (menuOpenClass !== '' ? 'true' : 'false') + '">'
            + '<i class="fa-solid fa-ellipsis" aria-hidden="true"></i>'
            + '</button>'
            + '<div class="sfc-stage-node-menu-panel' + menuOpenClass + '">'
            + '<button type="button" class="sfc-stage-node-menu-item" data-action="open-node-inspector" data-node-id="' + escapeHtml(node.id) + '">'
            + escapeHtml(labels.actionOpenInspector || '')
            + '</button>'
            + '<button type="button" class="sfc-stage-node-menu-item is-danger" data-action="delete-node" data-node-id="' + escapeHtml(node.id) + '">'
            + escapeHtml(labels.actionDeleteNode || '')
            + '</button>'
            + '</div>'
            + '</div>'
            + '</div>'
            + '<div class="sfc-stage-node-handles">' + handles.map(function (handle) {
                return '<button type="button" class="sfc-stage-node-handle sfc-stage-node-handle-' + handle + '" data-resize-handle="' + handle + '" data-node-id="' + escapeHtml(node.id) + '" aria-label="' + escapeHtml(labels.groupFrame || '') + '"></button>';
            }).join('') + '</div>'
            + '</div>';
    }

    function renderNode(node, selection, mode, labels, ui) {
        if (!node || node.enabled === false) {
            return '';
        }

        var selectedClass = selection.nodeId === node.id ? ' is-selected' : '';
        var commonAttrs = ' class="sfc-stage-node sfc-stage-node-' + escapeHtml(node.type) + selectedClass + ' is-clickable" data-action="select-node" data-node-id="' + escapeHtml(node.id) + '"';

        if (node.type === 'section') {
            return '<section' + commonAttrs + ' data-node-type="section" data-appearance="' + escapeHtml(node.appearance || 'none') + '">'
                + '<div class="sfc-stage-section">'
                + renderChildren(node.children || [], selection, mode, labels, ui)
                + '</div>'
                + renderNodeChrome(node, selection, mode, labels, ui)
                + '</section>';
        }

        if (node.type === 'stack') {
            return '<div' + commonAttrs + ' data-node-type="stack" data-direction="' + escapeHtml(node.direction || 'vertical') + '">'
                + '<div class="sfc-stage-stack" data-direction="' + escapeHtml(node.direction || 'vertical') + '">'
                + renderChildren(node.children || [], selection, mode, labels, ui)
                + '</div>'
                + renderNodeChrome(node, selection, mode, labels, ui)
                + '</div>';
        }

        if (node.type === 'logo') {
            return '<div' + commonAttrs + ' data-node-type="logo"><div class="sfc-stage-logo">' + escapeHtml(node.content || '') + '</div>' + renderNodeChrome(node, selection, mode, labels, ui) + '</div>';
        }

        if (node.type === 'menu') {
            return '<div' + commonAttrs + ' data-node-type="menu">' + renderMenu(node.items || []) + renderNodeChrome(node, selection, mode, labels, ui) + '</div>';
        }

        if (node.type === 'button') {
            var isButtonInlineEditing = ui && ui.inlineEditorNodeId === node.id;
            return '<div' + commonAttrs + ' data-node-type="button">'
                + '<a class="sfc-stage-button' + (isButtonInlineEditing ? ' is-inline-editing' : '') + '" data-variant="' + escapeHtml(node.variant || 'primary') + '" href="' + escapeHtml(node.url || '#') + '" contenteditable="' + (isButtonInlineEditing ? 'true' : 'false') + '" data-inline-content="true" data-node-id="' + escapeHtml(node.id) + '">'
                + escapeHtml(node.content || '')
                + '</a>'
                + renderNodeChrome(node, selection, mode, labels, ui)
                + '</div>';
        }

        var textClass = String(node.id || '').indexOf('title') !== -1 ? ' is-heading' : '';
        return '<div' + commonAttrs + ' data-node-type="text"><div class="sfc-stage-text' + textClass + '" data-node-text="true" data-node-id="' + escapeHtml(node.id) + '">' + renderRichTextContent(node.content || '') + '</div>' + renderNodeChrome(node, selection, mode, labels, ui) + '</div>';
    }

    function renderChildren(children, selection, mode, labels, ui) {
        var html = (children || []).map(function (child) {
            return renderNode(child, selection, mode, labels, ui);
        }).join('');

        if (html === '' && mode === 'compose') {
            return '<div class="sfc-stage-empty">' + escapeHtml(labels.emptyDropzone || '') + '</div>';
        }

        return html;
    }

    function renderRegions(documentData, selection, labels, ui) {
        return (documentData.regions || []).map(function (region) {
            var selectedClass = selection.nodeId === region.id ? ' is-selected' : '';
            return '<section class="sfc-stage-region sfc-stage-region-' + escapeHtml(region.tag || 'section') + selectedClass + (region.enabled === false ? ' is-disabled' : '') + ' is-clickable" data-action="select-node" data-node-id="' + escapeHtml(region.id || '') + '">'
                + '<div class="sfc-stage-region-body">'
                + renderChildren(region.children || [], selection, documentData.mode || 'compose', labels, ui)
                + '</div>'
                + '</section>';
        }).join('');
    }

    function renderCanvas(root, snapshot, labels, ui) {
        root.innerHTML = '<div class="sfc-stage-page" data-mode="' + escapeHtml(snapshot.document.mode || 'compose') + '">'
            + renderRegions(snapshot.document, snapshot.selection, labels, ui)
            + '</div>';
    }

    function drawerCards(labels, drawer) {
        if (drawer === 'elements') {
            return [
                ['add-text', labels.actionAddText, labels.cardAddTextCopy],
                ['add-buttons', labels.actionAddButtons, labels.cardAddButtonsCopy]
            ];
        }
        if (drawer === 'shell') {
            return [
                ['toggle-aside', labels.actionToggleAside, labels.cardToggleAsideCopy]
            ];
        }
        if (drawer === 'page') {
            return [
                ['reset-document', labels.actionResetDocument, labels.cardResetDocumentCopy]
            ];
        }

        return [
            ['add-section', labels.actionAddSection, labels.cardAddSectionCopy]
        ];
    }

    function renderDrawer(root, labels, drawer) {
        if (!drawer) {
            root.innerHTML = '';
            return;
        }

        var cards = drawerCards(labels, drawer);
        root.innerHTML = '<div class="sfc-studio-drawer-group">' + cards.map(function (card) {
            return '<button type="button" class="sfc-studio-card-action" data-action="' + escapeHtml(card[0]) + '">'
                + '<span class="sfc-studio-card-action-title">' + escapeHtml(card[1]) + '</span>'
                + '<span class="sfc-studio-card-action-copy">' + escapeHtml(card[2]) + '</span>'
                + '</button>';
        }).join('') + '</div>';
    }

    function inspectorTabs(labels, activeTab) {
        var tabs = [
            ['design', labels.tabDesign],
            ['effects', labels.tabEffects],
            ['responsive', labels.tabResponsive]
        ];

        return tabs.map(function (tab) {
            return '<button type="button" class="sfc-studio-tab-btn' + (activeTab === tab[0] ? ' is-active' : '') + '" data-action="switch-tab" data-tab="' + escapeHtml(tab[0]) + '">'
                + escapeHtml(tab[1] || tab[0])
                + '</button>';
        }).join('');
    }

    function renderField(label, inputHtml) {
        return '<label class="sfc-studio-field"><span class="sfc-studio-field-label">' + escapeHtml(label) + '</span>' + inputHtml + '</label>';
    }

    function renderHelper(copy) {
        return '<p class="sfc-studio-helper">' + escapeHtml(copy || '') + '</p>';
    }

    function renderGroup(title, body) {
        return '<section class="sfc-studio-group"><div class="sfc-studio-group-title">' + escapeHtml(title) + '</div><div class="sfc-studio-group-body">' + body + '</div></section>';
    }

    function renderFrameFields(labels, selectedNode) {
        var frame = nodeFrame(selectedNode);

        return ''
            + renderField(labels.fieldWidth || '', '<input type="number" class="sfc-studio-input" data-action="field-input" data-field="frameWidth" value="' + escapeHtml(numberValue(frame.width)) + '">')
            + renderField(labels.fieldHeight || '', '<input type="number" class="sfc-studio-input" data-action="field-input" data-field="frameHeight" value="' + escapeHtml(numberValue(frame.height)) + '">')
            + renderField(labels.fieldOffsetX || '', '<input type="number" class="sfc-studio-input" data-action="field-input" data-field="frameOffsetX" value="' + escapeHtml(numberValue(frame.offsetX)) + '">')
            + renderField(labels.fieldOffsetY || '', '<input type="number" class="sfc-studio-input" data-action="field-input" data-field="frameOffsetY" value="' + escapeHtml(numberValue(frame.offsetY)) + '">');
    }

    function renderInspector(root, tabsRoot, snapshot, labels, inspectorOpen, selectedNode) {
        var tab = snapshot.selection.tab || 'design';
        if (['design', 'effects', 'responsive'].indexOf(tab) === -1) {
            tab = 'design';
        }

        tabsRoot.innerHTML = inspectorTabs(labels, tab);

        if (!inspectorOpen || !selectedNode) {
            root.innerHTML = '<p class="sfc-studio-helper">' + escapeHtml(labels.selectionEmpty || '') + '</p>';
            return;
        }

        var isDocumentSelection = selectedNode.id === snapshot.document.id;
        var html = '<div class="sfc-studio-form-grid">';

        if (tab === 'design') {
            var contentGroup = '';
            var behaviorGroup = '';

            if (isDocumentSelection) {
                contentGroup += renderField(labels.fieldPageTitle || '', '<input class="sfc-studio-input" data-action="field-input" data-field="title" value="' + escapeHtml(selectedNode.title || '') + '">');
            } else {
                contentGroup += renderField(labels.fieldLabel || '', '<input class="sfc-studio-input" data-action="field-input" data-field="label" value="' + escapeHtml(selectedNode.label || '') + '">');
            }

            if (!isDocumentSelection && selectedNode.type === 'text') {
                contentGroup += renderHelper(labels.textHint || '');
            }

            if (!isDocumentSelection && selectedNode.type === 'button') {
                contentGroup += renderField(labels.fieldButtonText || '', '<input class="sfc-studio-input" data-action="field-input" data-field="content" value="' + escapeHtml(selectedNode.content || '') + '">');
                contentGroup += renderField(labels.fieldUrl, '<input class="sfc-studio-input" data-action="field-input" data-field="url" value="' + escapeHtml(selectedNode.url || '') + '">');
                behaviorGroup += renderField(labels.fieldVariant,
                    '<select class="sfc-studio-select" data-action="field-input" data-field="variant">'
                    + '<option value="primary"' + ((selectedNode.variant || '') === 'primary' ? ' selected' : '') + '>' + escapeHtml(labels.variantPrimary || '') + '</option>'
                    + '<option value="secondary"' + ((selectedNode.variant || '') === 'secondary' ? ' selected' : '') + '>' + escapeHtml(labels.variantSecondary || '') + '</option>'
                    + '<option value="link"' + ((selectedNode.variant || '') === 'link' ? ' selected' : '') + '>' + escapeHtml(labels.variantLink || '') + '</option>'
                    + '</select>');
            }

            if (!isDocumentSelection && selectedNode.type === 'logo') {
                contentGroup += renderField(labels.fieldLogoText || '', '<input class="sfc-studio-input" data-action="field-input" data-field="content" value="' + escapeHtml(selectedNode.content || '') + '">');
            }

            if (!isDocumentSelection && selectedNode.type === 'menu') {
                contentGroup += renderHelper(labels.menuHint || '');
            }

            if (!isDocumentSelection) {
                behaviorGroup += '<label class="sfc-studio-toggle"><input type="checkbox" data-action="field-toggle" data-field="enabled" ' + (selectedNode.enabled !== false ? 'checked' : '') + '> ' + escapeHtml(labels.fieldEnabled || '') + '</label>';
            }

            if (selectedNode.type === 'stack') {
                behaviorGroup += renderField(labels.fieldDirection,
                    '<select class="sfc-studio-select" data-action="field-input" data-field="direction">'
                    + '<option value="vertical"' + ((selectedNode.direction || '') === 'vertical' ? ' selected' : '') + '>' + escapeHtml(labels.directionVertical || '') + '</option>'
                    + '<option value="horizontal"' + ((selectedNode.direction || '') === 'horizontal' ? ' selected' : '') + '>' + escapeHtml(labels.directionHorizontal || '') + '</option>'
                    + '</select>');
            }

            if (selectedNode.type === 'section' || selectedNode.type === 'stack') {
                behaviorGroup += renderField(labels.fieldSurface,
                    '<select class="sfc-studio-select" data-action="field-input" data-field="appearance">'
                    + '<option value="none"' + ((selectedNode.appearance || '') === 'none' ? ' selected' : '') + '>' + escapeHtml(labels.surfaceNone || '') + '</option>'
                    + '<option value="soft"' + ((selectedNode.appearance || '') === 'soft' ? ' selected' : '') + '>' + escapeHtml(labels.surfaceSoft || '') + '</option>'
                    + '<option value="contrast"' + ((selectedNode.appearance || '') === 'contrast' ? ' selected' : '') + '>' + escapeHtml(labels.surfaceContrast || '') + '</option>'
                    + '</select>');
            }

            if (contentGroup !== '') {
                html += renderGroup(labels.groupContent || '', contentGroup);
            }

            if (!isDocumentSelection) {
                html += renderGroup(labels.groupFrame || '', renderFrameFields(labels, selectedNode));
            }

            if (behaviorGroup !== '') {
                html += renderGroup(labels.groupBehavior || '', behaviorGroup);
            }
        } else if (tab === 'effects') {
            html += renderHelper(labels.effectsEmpty || '');
        } else {
            html += renderHelper(labels.responsiveEmpty || '');
        }

        html += '</div>';
        root.innerHTML = html;
    }

    function renderTopbar(snapshot, labels) {
        var modeButtons = document.querySelectorAll('.sfc-studio-mode-btn');
        var viewportButtons = document.querySelectorAll('.sfc-studio-viewport-btn');
        var labelNode = document.getElementById('sfc-studio-viewport-label');
        var sizeNode = document.getElementById('sfc-studio-viewport-size');
        var zoomNode = document.getElementById('sfc-studio-zoom');
        var mode = String(snapshot.document.mode || 'compose');
        var viewport = String(snapshot.document.viewport || 'desktop');

        modeButtons.forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-mode') === mode);
        });
        viewportButtons.forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-viewport') === viewport);
        });
        if (labelNode) {
            labelNode.textContent = viewportLabel(labels, viewport);
        }
        if (sizeNode) {
            sizeNode.textContent = viewportWidth(viewport);
        }
        if (zoomNode) {
            zoomNode.value = String(snapshot.document.zoom || 100);
        }
        document.documentElement.style.setProperty('--sfc-stage-width', viewportWidth(viewport));
        document.documentElement.style.setProperty('--sfc-stage-zoom', String((Number(snapshot.document.zoom || 100) / 100).toFixed(2)));
    }

    namespace.render = {
        richTextHtml: renderRichTextContent,
        mount: function (elements, snapshot, ui, labels) {
            renderTopbar(snapshot, labels);
            renderCanvas(elements.stage, snapshot, labels, ui);
            renderDrawer(elements.drawerBody, labels, ui.drawer);
            renderInspector(elements.inspectorBody, elements.inspectorTabs, snapshot, labels, ui.inspectorOpen, ui.selectedNode);
        }
    };
}(window, document));
