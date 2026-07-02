/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function () {
    'use strict';

    function detectLocale() {
        var raw = String(document.documentElement.getAttribute('lang') || '').toLowerCase();
        if (raw.indexOf('fr') === 0) return 'fr';
        if (raw.indexOf('de') === 0) return 'de';
        if (raw.indexOf('es') === 0) return 'es';
        if (raw.indexOf('it') === 0) return 'it';
        if (raw.indexOf('pt') === 0) return 'pt_br';
        return 'en';
    }

    function isAvailable() {
        return !!(window.SUNEDITOR && typeof window.SUNEDITOR.create === 'function');
    }

    function resolveLangObject() {
        var key = detectLocale();
        if (window.SUNEDITOR_LANG && window.SUNEDITOR_LANG[key]) {
            return window.SUNEDITOR_LANG[key];
        }
        return null;
    }

    function initToolbarAccordion(editor, config) {
        if (!editor || !editor.core || !editor.core.context || !editor.core.context.element) {
            return;
        }

        var toolbar = editor.core.context.element.toolbar;
        if (!toolbar) {
            return;
        }

        var tray = toolbar.querySelector('.se-btn-tray');
        if (!tray || tray.getAttribute('data-flatcms-toolbar-accordion') === '1') {
            return;
        }

        var children = Array.prototype.slice.call(tray.children || []);
        if (!children.length) {
            return;
        }

        var splitSeen = false;
        var primaryItems = [];
        var secondaryItems = [];
        var staticItems = [];

        children.forEach(function (child) {
            if (!child || !child.classList) {
                return;
            }

            if (child.classList.contains('se-btn-module-enter')) {
                splitSeen = true;
                return;
            }

            if (child.classList.contains('se-menu-tray') || child.classList.contains('se-toolbar-more-layer')) {
                staticItems.push(child);
                return;
            }

            if (!splitSeen) {
                primaryItems.push(child);
            } else {
                secondaryItems.push(child);
            }
        });

        if (!primaryItems.length || !secondaryItems.length) {
            return;
        }

        var topRow = document.createElement('div');
        topRow.className = 'fc-se-toolbar-top';

        var primaryRow = document.createElement('div');
        primaryRow.className = 'fc-se-toolbar-row fc-se-toolbar-row-primary';

        var secondaryRow = document.createElement('div');
        secondaryRow.className = 'fc-se-toolbar-row fc-se-toolbar-row-secondary';

        primaryItems.forEach(function (item) {
            primaryRow.appendChild(item);
        });
        secondaryItems.forEach(function (item) {
            secondaryRow.appendChild(item);
        });

        var cfg = config && typeof config === 'object' ? config : {};
        var expandLabel = String(cfg.expandLabel || '').trim();
        var collapseLabel = String(cfg.collapseLabel || '').trim();

        var toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'se-btn fc-se-toolbar-toggle';
        toggleButton.setAttribute('aria-expanded', 'false');

        var chevron = document.createElement('span');
        chevron.className = 'fc-se-toolbar-chevron';
        chevron.setAttribute('aria-hidden', 'true');
        toggleButton.appendChild(chevron);

        var applyToggleState = function (expanded) {
            tray.classList.toggle('fc-se-expanded', expanded);
            toggleButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');

            if (expanded && collapseLabel !== '') {
                toggleButton.setAttribute('title', collapseLabel);
                toggleButton.setAttribute('aria-label', collapseLabel);
                return;
            }
            if (!expanded && expandLabel !== '') {
                toggleButton.setAttribute('title', expandLabel);
                toggleButton.setAttribute('aria-label', expandLabel);
                return;
            }
            toggleButton.removeAttribute('title');
            toggleButton.removeAttribute('aria-label');
        };

        applyToggleState(false);
        toggleButton.addEventListener('click', function (event) {
            event.preventDefault();
            applyToggleState(!tray.classList.contains('fc-se-expanded'));
        });

        while (tray.firstChild) {
            tray.removeChild(tray.firstChild);
        }

        topRow.appendChild(primaryRow);
        topRow.appendChild(toggleButton);
        tray.appendChild(topRow);
        tray.appendChild(secondaryRow);

        staticItems.forEach(function (item) {
            tray.appendChild(item);
        });

        tray.classList.add('fc-se-toolbar-accordion');
        tray.setAttribute('data-flatcms-toolbar-accordion', '1');
    }

    function normalizeColor(value) {
        var text = String(value || '').trim();
        if (text === '') {
            return '';
        }
        if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(text)) {
            return text;
        }
        if (/^rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)$/i.test(text)) {
            return text;
        }
        return '';
    }

    function normalizeHexColor(value) {
        var text = String(value || '').trim();
        var match = text.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
        if (!match) {
            return '';
        }

        var hex = String(match[1] || '').toLowerCase();
        if (hex.length === 3) {
            hex = hex.split('').map(function (char) {
                return char + char;
            }).join('');
        }
        return '#' + hex;
    }

    function cloneSunEditorRange(core) {
        if (!core || typeof core.getRange !== 'function') {
            return null;
        }

        try {
            var range = core.getRange();
            if (!range) {
                return null;
            }

            return {
                startContainer: range.startContainer || null,
                startOffset: Number(range.startOffset || 0),
                endContainer: range.endContainer || null,
                endOffset: Number(range.endOffset || 0)
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

    function findToolbarCommandModule(toolbar, command) {
        if (!toolbar || !command) {
            return null;
        }

        var target = toolbar.querySelector('[data-command="' + String(command) + '"]');
        if (!target) {
            return null;
        }

        return target.closest('.se-btn-module-border, .se-btn-module, li') || target;
    }

    function applyToolbarColor(editor, pluginName, color, savedRange) {
        var core = editor && editor.core;
        if (!core || !core.plugins || !core.plugins[pluginName]) {
            return;
        }

        restoreSunEditorRange(core, savedRange);

        var plugin = core.plugins[pluginName];
        if (!color) {
            if (typeof plugin.remove === 'function') {
                plugin.remove.call(core);
            }
            return;
        }

        if (typeof plugin.applyColor === 'function') {
            plugin.applyColor.call(core, color);
        }
    }

    function requestInputPicker(input) {
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        if (typeof input.showPicker === 'function') {
            try {
                input.showPicker();
                return;
            } catch (error) {
                // fallback below
            }
        }

        try {
            input.focus({ preventScroll: true });
        } catch (error) {
            input.focus();
        }
        input.click();
    }

    function createFallbackCompactColorControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var wrapper = document.createElement('div');
        wrapper.className = 'fc-ui-color-control';

        var pickerWrap = document.createElement('div');
        pickerWrap.className = 'fc-ui-color-picker';
        wrapper.appendChild(pickerWrap);

        var swatch = document.createElement('button');
        swatch.type = 'button';
        swatch.className = 'fc-ui-toolbar-btn fc-ui-color-swatch';
        pickerWrap.appendChild(swatch);

        var picker = document.createElement('input');
        picker.type = 'color';
        picker.className = 'fc-ui-native-color';
        pickerWrap.appendChild(picker);

        var clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'fc-ui-toolbar-btn';
        clearButton.innerHTML = '<i class="fas fa-eraser" aria-hidden="true"></i>';
        wrapper.appendChild(clearButton);

        var labelText = String(cfg.label || '').trim();
        var emptyLabel = String(cfg.emptyLabel || '').trim();
        var clearLabel = String(cfg.clearLabel || '').trim();
        var includeStateInTitle = cfg.includeStateInTitle !== false;
        var titleScope = String(cfg.titleScope || 'all').trim().toLowerCase() === 'button' ? 'button' : 'all';
        var onUpdate = typeof cfg.onUpdate === 'function' ? cfg.onUpdate : null;

        function setTitle(nodes, title) {
            nodes.forEach(function (node) {
                if (!node || !title) {
                    return;
                }
                node.setAttribute('title', title);
                node.setAttribute('aria-label', title);
            });
        }

        function clearPassiveTitles() {
            pickerWrap.removeAttribute('title');
            pickerWrap.removeAttribute('aria-label');
            picker.removeAttribute('title');
            picker.removeAttribute('aria-label');
        }

        function setValue(value) {
            var safe = normalizeColor(value);
            swatch.classList.toggle('is-empty', safe === '');
            swatch.style.setProperty('--pb-textstyle-swatch-color', safe || 'transparent');
            picker.value = normalizeHexColor(safe) || '#4f46e5';

            var title = '';
            if (labelText !== '') {
                title = includeStateInTitle
                    ? (safe !== ''
                        ? labelText + ': ' + safe
                        : (emptyLabel !== '' ? labelText + ': ' + emptyLabel : labelText))
                    : labelText;
            }
            if (title !== '') {
                setTitle(titleScope === 'button' ? [swatch] : [swatch, pickerWrap, picker], title);
            }
            clearPassiveTitles();
        }

        function emit(value, refreshInspector) {
            var safe = normalizeColor(value);
            setValue(safe);
            if (onUpdate) {
                onUpdate(safe, refreshInspector !== false);
            }
        }

        if (clearLabel !== '') {
            clearButton.setAttribute('title', clearLabel);
            clearButton.setAttribute('aria-label', clearLabel);
        }

        setValue(String(cfg.value || ''));

        picker.addEventListener('input', function () {
            emit(picker.value, false);
        });
        picker.addEventListener('change', function () {
            emit(picker.value, true);
        });
        swatch.addEventListener('click', function (event) {
            event.preventDefault();
            requestInputPicker(picker);
        });
        clearButton.addEventListener('click', function () {
            emit('', true);
        });

        return {
            wrapper: wrapper,
            setValue: setValue
        };
    }

    function createCompactColorControl(config) {
        var primitives = window.FlatCMSUIPrimitives || null;
        if (primitives && typeof primitives.createCompactColorControl === 'function') {
            return primitives.createCompactColorControl(config);
        }

        return createFallbackCompactColorControl(config);
    }

    function applySunEditorTooltip(button) {
        if (!(button instanceof HTMLElement)) {
            return;
        }

        var label = String(button.getAttribute('aria-label') || button.getAttribute('title') || '').trim();
        if (label === '') {
            return;
        }

        var existingTooltip = button.querySelector('.se-tooltip-inner');
        if (existingTooltip) {
            existingTooltip.remove();
        }

        button.classList.add('se-tooltip');
        button.removeAttribute('title');
        button.setAttribute('aria-label', label);

        var tooltipInner = document.createElement('span');
        tooltipInner.className = 'se-tooltip-inner';

        var tooltipText = document.createElement('span');
        tooltipText.className = 'se-tooltip-text';
        tooltipText.textContent = label;

        tooltipInner.appendChild(tooltipText);
        button.appendChild(tooltipInner);
    }

    function refreshSunEditorTooltips(editor) {
        var toolbar = editor && editor.core && editor.core.context && editor.core.context.element
            ? editor.core.context.element.toolbar
            : null;
        if (!(toolbar instanceof HTMLElement)) {
            return;
        }

        toolbar.querySelectorAll('button').forEach(function (button) {
            if (!(button instanceof HTMLElement)) {
                return;
            }

            if (button.querySelector('.se-tooltip-inner')) {
                button.classList.add('se-tooltip');
                button.removeAttribute('title');
                return;
            }

            applySunEditorTooltip(button);
        });
    }

    function createSunEditorColorControl(editor, pluginName, labels, captureRange, getSavedRange, extraClass, config) {
        var labelKey = pluginName === 'hiliteColor' ? 'highlight' : 'text';
        var cfg = config && typeof config === 'object' ? config : {};
        var control = createCompactColorControl({
            label: String(labels && labels[labelKey] || '').trim(),
            clearLabel: String(labels && labels.clear || '').trim(),
            emptyLabel: String(labels && labels.empty || '').trim(),
            includeStateInTitle: cfg.includeStateInTitle !== false,
            titleScope: 'button',
            value: '',
            normalizeColor: normalizeColor,
            normalizeHex: normalizeHexColor,
            onUpdate: function (nextColor) {
                applyToolbarColor(editor, pluginName, nextColor, getSavedRange());
            }
        });

        if (!control || !control.wrapper) {
            return null;
        }

        control.wrapper.classList.add('fc-se-color-control');
        if (extraClass) {
            control.wrapper.classList.add(extraClass);
        }
        control.wrapper.querySelectorAll('button, input').forEach(function (node) {
            node.addEventListener('mousedown', captureRange, true);
        });
        control.wrapper.querySelectorAll('button').forEach(function (button) {
            applySunEditorTooltip(button);
        });

        return control.wrapper;
    }

    function createToolbarColorGroup(command, control) {
        var listItem = document.createElement('li');
        listItem.className = 'fc-se-color-item fc-se-color-item-' + command.toLowerCase();

        var colorGroup = document.createElement('div');
        colorGroup.className = 'fc-se-color-group fc-se-color-group-' + command.toLowerCase();
        colorGroup.setAttribute('data-flatcms-command', command);
        colorGroup.appendChild(control);
        listItem.appendChild(colorGroup);

        return listItem;
    }

    function insertToolbarNode(toolbar, command, node, fallbackCommand, fallbackPosition, fallbackParent) {
        var module = findToolbarCommandModule(toolbar, command);
        if (module && module.parentNode) {
            module.parentNode.insertBefore(node, module);
            module.remove();
            return true;
        }

        var fallbackModule = fallbackCommand ? findToolbarCommandModule(toolbar, fallbackCommand) : null;
        if (fallbackModule && fallbackModule.parentNode) {
            if (fallbackPosition === 'after') {
                fallbackModule.parentNode.insertBefore(node, fallbackModule.nextSibling);
            } else {
                fallbackModule.parentNode.insertBefore(node, fallbackModule);
            }
            return true;
        }

        if (fallbackParent) {
            fallbackParent.appendChild(node);
            return true;
        }

        return false;
    }

    function enhanceInlineSunEditorColors(editor, labels, config) {
        var core = editor && editor.core;
        var toolbar = core && core.context && core.context.element ? core.context.element.toolbar : null;
        if (!toolbar || toolbar.getAttribute('data-flatcms-inline-colors') === '1') {
            return;
        }

        var primaryRow = toolbar.querySelector('.fc-se-toolbar-row-primary') || toolbar.querySelector('.se-btn-tray');
        if (!primaryRow) {
            return;
        }

        var fontColorModule = findToolbarCommandModule(toolbar, 'fontColor');
        var hiliteColorModule = findToolbarCommandModule(toolbar, 'hiliteColor');
        var cfg = config && typeof config === 'object' ? config : {};
        var enableHiliteColor = cfg.enableHiliteColor === true;
        var includeColorStateInTooltip = cfg.includeColorStateInTooltip !== false;
        var didEnhance = false;

        var savedRange = null;
        var captureRange = function () {
            savedRange = cloneSunEditorRange(core);
            return savedRange;
        };
        var getSavedRange = function () {
            return savedRange;
        };

        if (fontColorModule) {
            var textColorControl = createSunEditorColorControl(editor, 'fontColor', labels, captureRange, getSavedRange, 'fc-se-font-color-control', {
                includeStateInTitle: includeColorStateInTooltip
            });
            if (textColorControl) {
                insertToolbarNode(
                    toolbar,
                    'fontColor',
                    createToolbarColorGroup('fontColor', textColorControl),
                    'align',
                    'before',
                    primaryRow
                );
                didEnhance = true;
            }
        }

        if (hiliteColorModule) {
            if (enableHiliteColor) {
                var hiliteColorControl = createSunEditorColorControl(editor, 'hiliteColor', labels, captureRange, getSavedRange, 'fc-se-hilite-color-control', {
                    includeStateInTitle: includeColorStateInTooltip
                });
                if (hiliteColorControl) {
                    insertToolbarNode(
                        toolbar,
                        'hiliteColor',
                        createToolbarColorGroup('hiliteColor', hiliteColorControl),
                        'horizontalRule',
                        'after',
                        primaryRow
                    );
                    didEnhance = true;
                }
            } else {
                hiliteColorModule.remove();
                didEnhance = true;
            }
        }

        if (didEnhance) {
            toolbar.setAttribute('data-flatcms-inline-colors', '1');
        }
    }

    function getDefaultButtonList() {
        return [
            ['font', 'fontSize', 'formatBlock', 'link', 'undo', 'redo', 'bold', 'underline', 'italic', 'strike', 'fontColor', 'hiliteColor', 'align', 'list', 'horizontalRule'],
            '/',
            ['image', 'video', 'table', 'removeFormat', 'codeView'],
        ];
    }

    function findParentElement(node, matcher) {
        var current = node;
        while (current && current.nodeType) {
            if (typeof matcher === 'function' && matcher(current)) {
                return current;
            }
            current = current.parentNode || null;
        }
        return null;
    }

    function hasMeaningfulTextContent(value) {
        return String(value || '')
            .replace(/\u200B/g, '')
            .replace(/\s+/g, '')
            .length > 0;
    }

    function cellHasMeaningfulContent(cell) {
        if (!(cell instanceof HTMLElement)) {
            return false;
        }

        if (cell.querySelector('table, img, video, audio, iframe, figure, ul, ol, blockquote, pre, hr')) {
            return true;
        }

        return hasMeaningfulTextContent(cell.textContent);
    }

    function isPlaceholderTable(table) {
        if (!(table instanceof HTMLTableElement)) {
            return false;
        }

        var cells = table.querySelectorAll('td, th');
        if (!cells.length) {
            return false;
        }

        return !Array.prototype.some.call(cells, function (cell) {
            return cellHasMeaningfulContent(cell);
        });
    }

    function getSelectionAnchorNode(core) {
        if (!core) {
            return null;
        }

        try {
            if (typeof core.getSelection === 'function') {
                var selection = core.getSelection();
                if (selection && selection.rangeCount > 0) {
                    var nativeRange = selection.getRangeAt(0);
                    if (nativeRange && nativeRange.startContainer) {
                        return nativeRange.startContainer;
                    }
                }
            }
        } catch (error) {
            // fallback below
        }

        try {
            if (typeof core.getRange === 'function') {
                var editorRange = core.getRange();
                if (editorRange && editorRange.startContainer) {
                    return editorRange.startContainer;
                }
            }
        } catch (error) {
            // no-op
        }

        return null;
    }

    function setCaretInTable(core, tableElement) {
        if (!core || !(tableElement instanceof HTMLElement) || typeof core.setRange !== 'function') {
            return;
        }

        var cell = tableElement.querySelector('td, th');
        if (!(cell instanceof HTMLElement)) {
            return;
        }

        var textNode = cell.querySelector('div, p, span');
        if (textNode && textNode.firstChild && textNode.firstChild.nodeType === Node.TEXT_NODE) {
            var offset = String(textNode.firstChild.textContent || '').length;
            core.setRange(textNode.firstChild, offset, textNode.firstChild, offset);
            return;
        }

        var focusTarget = textNode || cell;
        core.setRange(focusTarget, 0, focusTarget, 0);
    }

    function replacePlaceholderTableFromPaste(editor, cleanData) {
        var core = editor && editor.core;
        var html = String(cleanData || '').trim();
        if (!core || html === '' || !/<table[\s>]/i.test(html)) {
            return false;
        }

        var anchorNode = getSelectionAnchorNode(core);
        var placeholderTable = findParentElement(anchorNode, function (node) {
            return node instanceof HTMLTableElement;
        });
        if (!isPlaceholderTable(placeholderTable)) {
            return false;
        }

        var doc = placeholderTable.ownerDocument || document;
        var temp = doc.createElement('div');
        temp.innerHTML = html;

        var firstElement = temp.firstElementChild;
        if (!firstElement) {
            return false;
        }

        var fragment = doc.createDocumentFragment();
        while (temp.firstChild) {
            fragment.appendChild(temp.firstChild);
        }

        if (!placeholderTable.parentNode) {
            return false;
        }

        placeholderTable.parentNode.replaceChild(fragment, placeholderTable);

        if (firstElement instanceof HTMLElement) {
            var focusTable = firstElement instanceof HTMLTableElement
                ? firstElement
                : firstElement.querySelector('table');
            if (focusTable instanceof HTMLElement) {
                setCaretInTable(core, focusTable);
            }
        }

        return true;
    }

    function create(textarea, config) {
        if (!(textarea instanceof HTMLTextAreaElement)) {
            return null;
        }
        if (!isAvailable()) {
            return null;
        }

        var cfg = config && typeof config === 'object' ? config : {};
        var lang = resolveLangObject();
        var options = {
            width: '100%',
            minHeight: String(cfg.minHeight || '180px'),
            height: cfg.height != null ? cfg.height : 220,
            resizingBar: typeof cfg.resizingBar === 'boolean' ? cfg.resizingBar : true,
            stickyToolbar: typeof cfg.stickyToolbar !== 'undefined' ? cfg.stickyToolbar : 0,
            charCounter: !!cfg.charCounter,
            charCounterType: 'char',
            defaultStyle: 'font-family: inherit; font-size: 14px; line-height: 1.65;',
            formats: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote'],
            buttonList: Array.isArray(cfg.buttonList) && cfg.buttonList.length ? cfg.buttonList : getDefaultButtonList(),
        };

        if (lang) {
            options.lang = lang;
        }
        if (typeof cfg.placeholder === 'string' && cfg.placeholder.trim() !== '') {
            options.placeholder = cfg.placeholder.trim();
        }

        var editor = window.SUNEDITOR.create(textarea, options);
        if (!editor) {
            return null;
        }

        if (cfg.applyAccordion !== false) {
            initToolbarAccordion(editor, cfg);
        }

        enhanceInlineSunEditorColors(editor, {
            text: lang && lang.toolbar ? String(lang.toolbar.fontColor || '') : '',
            highlight: lang && lang.toolbar ? String(lang.toolbar.hiliteColor || '') : '',
            empty: lang && lang.toolbar ? String(lang.toolbar.default || '') : '',
            clear: lang && lang.dialogBox ? String(lang.dialogBox.revertButton || '') : ''
        }, {
            enableHiliteColor: cfg.enableHiliteColor === true,
            includeColorStateInTooltip: cfg.includeColorStateInTooltip !== false
        });
        refreshSunEditorTooltips(editor);

        var onInput = typeof cfg.onInput === 'function' ? cfg.onInput : null;
        var onChange = typeof cfg.onChange === 'function' ? cfg.onChange : null;
        var onPaste = typeof cfg.onPaste === 'function' ? cfg.onPaste : null;

        var readHtml = function () {
            try {
                if (editor && typeof editor.getContents === 'function') {
                    return String(editor.getContents() || '');
                }
            } catch (error) {
                return String(textarea.value || '');
            }
            return String(textarea.value || '');
        };

        var syncValue = function (nextHtml, emitInput, emitChange) {
            var html = String(nextHtml || '');
            textarea.value = html;
            if (emitInput && onInput) {
                onInput(html);
            }
            if (emitChange && onChange) {
                onChange(html);
            }
        };

        editor.onPaste = function (event, cleanData, maxCharCount, core) {
            if (replacePlaceholderTableFromPaste(editor, cleanData)) {
                syncValue(readHtml(), true, true);
                return false;
            }

            if (onPaste) {
                return onPaste(event, cleanData, maxCharCount, core);
            }

            return undefined;
        };

        editor.onChange = function (contents) {
            syncValue(String(contents || ''), true, cfg.emitChangeOnInput === true);
        };

        editor.onBlur = function () {
            syncValue(readHtml(), false, true);
        };

        if (typeof cfg.onReady === 'function') {
            try {
                cfg.onReady(editor);
            } catch (error) {
                // no-op
            }
        }

        return {
            editor: editor,
            getHtml: readHtml,
            setHtml: function (nextHtml) {
                var html = String(nextHtml || '');
                try {
                    if (editor && typeof editor.setContents === 'function') {
                        editor.setContents(html);
                    }
                } catch (error) {
                    textarea.value = html;
                }
                syncValue(html, false, false);
            },
            destroy: function () {
                try {
                    if (editor && typeof editor.destroy === 'function') {
                        editor.destroy();
                    }
                } catch (error) {
                    // no-op
                }
            },
        };
    }

    window.FlatCMSSunEditor = {
        isAvailable: isAvailable,
        create: create,
        initToolbarAccordion: initToolbarAccordion,
    };
})();
