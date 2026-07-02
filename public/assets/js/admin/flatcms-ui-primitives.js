/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function (window, document) {
    'use strict';

    function escapeAttr(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escapeHtml(value) {
        return escapeAttr(value);
    }

    function sanitizeClassList(value) {
        return String(value == null ? '' : value)
            .trim()
            .replace(/[^a-zA-Z0-9_ -]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function setSharedTitle(targets, title) {
        const safeTitle = String(title || '').trim();
        targets.forEach(function (target) {
            if (!target || typeof target !== 'object') {
                return;
            }
            target.title = safeTitle;
            if (typeof target.setAttribute === 'function' && safeTitle !== '') {
                target.setAttribute('aria-label', safeTitle);
            }
        });
    }

    function requestInputPicker(input) {
        if (!input) {
            return;
        }
        if (typeof input.showPicker === 'function') {
            try {
                input.showPicker();
                return;
            } catch (error) {
                // Fallback below.
            }
        }
        if (typeof input.focus === 'function') {
            input.focus({ preventScroll: true });
        }
        if (typeof input.click === 'function') {
            input.click();
        }
    }

    function normalizeHexColor(value) {
        var raw = String(value == null ? '' : value).trim();
        if (raw === '') {
            return '';
        }
        if (/^#[0-9a-fA-F]{6}$/.test(raw)) {
            return raw.toLowerCase();
        }
        if (/^#[0-9a-fA-F]{3}$/.test(raw)) {
            return '#' + raw.slice(1).split('').map(function (part) {
                return part + part;
            }).join('').toLowerCase();
        }
        return '';
    }

    function createCompactSelectControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var wrapper = document.createElement(cfg.wrapperTag || 'label');
        wrapper.className = [
            'fc-ui-compact-picker',
            'pb-textstyle-toolbar-picker',
            sanitizeClassList(cfg.wrapperClass || '')
        ].filter(Boolean).join(' ');

        var button = document.createElement(cfg.buttonTag || 'span');
        button.className = [
            'fc-ui-toolbar-btn',
            'pb-textstyle-toolbar-btn',
            sanitizeClassList(cfg.buttonClass || '')
        ].filter(Boolean).join(' ');
        button.setAttribute('role', cfg.role || 'button');
        button.setAttribute('tabindex', '0');
        wrapper.appendChild(button);

        var select = document.createElement('select');
        select.className = [
            'fc-ui-compact-select',
            'pb-textstyle-toolbar-native-select',
            sanitizeClassList(cfg.selectClass || '')
        ].filter(Boolean).join(' ');
        wrapper.appendChild(select);

        var iconClass = sanitizeClassList(cfg.iconClass || 'fas fa-sliders-h') || 'fas fa-sliders-h';
        var labelText = String(cfg.label || 'Option').trim() || 'Option';
        var ariaLabel = String(cfg.ariaLabel || labelText).trim() || labelText;
        button.innerHTML = '<i class="' + escapeAttr(iconClass) + '" aria-hidden="true"></i>';
        button.setAttribute('aria-label', ariaLabel);

        var currentLabelFn = typeof cfg.currentLabel === 'function'
            ? cfg.currentLabel
            : function () { return ''; };
        var updateTitles = function () {
            var currentLabel = String(currentLabelFn(select.value) || '').trim();
            var title = currentLabel !== '' ? labelText + ': ' + currentLabel : labelText;
            setSharedTitle([wrapper, button, select], title);
        };

        button.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                requestInputPicker(select);
            }
        });

        return {
            wrapper: wrapper,
            button: button,
            select: select,
            updateTitles: updateTitles
        };
    }

    function createCompactColorControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var wrapper = document.createElement('div');
        wrapper.className = [
            'fc-ui-color-control',
            'pb-textstyle-toolbar-color',
            sanitizeClassList(cfg.wrapperClass || '')
        ].filter(Boolean).join(' ');

        var pickerWrap = document.createElement('div');
        pickerWrap.className = [
            'fc-ui-color-picker',
            'pb-textstyle-toolbar-picker',
            'pb-textstyle-toolbar-color-picker',
            sanitizeClassList(cfg.pickerWrapClass || '')
        ].filter(Boolean).join(' ');
        wrapper.appendChild(pickerWrap);

        var swatch = document.createElement('button');
        swatch.type = 'button';
        swatch.className = [
            'fc-ui-toolbar-btn',
            'fc-ui-color-swatch',
            'pb-textstyle-toolbar-btn',
            'pb-textstyle-toolbar-color-swatch',
            sanitizeClassList(cfg.swatchClass || '')
        ].filter(Boolean).join(' ');
        pickerWrap.appendChild(swatch);

        var picker = document.createElement('input');
        picker.type = 'color';
        picker.className = [
            'fc-ui-compact-select',
            'fc-ui-native-color',
            'pb-textstyle-toolbar-native-select',
            'pb-textstyle-toolbar-native-color',
            sanitizeClassList(cfg.inputClass || '')
        ].filter(Boolean).join(' ');
        pickerWrap.appendChild(picker);

        var clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = [
            'fc-ui-toolbar-btn',
            'pb-textstyle-toolbar-btn',
            sanitizeClassList(cfg.clearButtonClass || '')
        ].filter(Boolean).join(' ');
        clearBtn.innerHTML = '<i class="fas fa-eraser" aria-hidden="true"></i>';
        wrapper.appendChild(clearBtn);

        var labelText = String(cfg.label || 'Color').trim() || 'Color';
        var emptyLabel = String(cfg.emptyLabel || 'Theme').trim() || 'Theme';
        var clearLabel = String(cfg.clearLabel || 'Clear').trim() || 'Clear';
        var includeStateInTitle = cfg.includeStateInTitle !== false;
        var titleScope = String(cfg.titleScope || 'all').trim().toLowerCase() === 'button' ? 'button' : 'all';
        var onUpdate = typeof cfg.onUpdate === 'function' ? cfg.onUpdate : null;
        var normalizeColor = typeof cfg.normalizeColor === 'function'
            ? cfg.normalizeColor
            : function (value) { return String(value == null ? '' : value).trim(); };
        var normalizeHex = typeof cfg.normalizeHex === 'function'
            ? cfg.normalizeHex
            : normalizeHexColor;

        clearBtn.title = clearLabel;
        clearBtn.setAttribute('aria-label', clearLabel);

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
            picker.value = normalizeHex(safe) || '#4f46e5';
            var title = includeStateInTitle
                ? (safe !== '' ? labelText + ': ' + safe : labelText + ': ' + emptyLabel)
                : labelText;
            setSharedTitle(titleScope === 'button' ? [swatch] : [swatch, pickerWrap, picker], title);
            clearPassiveTitles();
        }

        function emit(value, refreshInspector) {
            var safe = normalizeColor(value);
            setValue(safe);
            if (onUpdate) {
                onUpdate(safe, refreshInspector !== false);
            }
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
        clearBtn.addEventListener('click', function () {
            emit('', true);
        });

        return {
            wrapper: wrapper,
            pickerWrap: pickerWrap,
            swatch: swatch,
            picker: picker,
            clearButton: clearBtn,
            setValue: setValue
        };
    }

    function createBuilderChoiceControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var field = cfg.field && typeof cfg.field === 'object' ? cfg.field : {};
        var options = Array.isArray(cfg.options)
            ? cfg.options.map(function (option) { return String(option || '').trim(); }).filter(function (option) { return option !== ''; })
            : [];
        var safeCurrent = String(cfg.currentValue || '').trim();
        var activeValue = options.indexOf(safeCurrent) !== -1 ? safeCurrent : (options[0] || '');
        var resolveLabel = typeof cfg.labelResolver === 'function'
            ? cfg.labelResolver
            : function (_, optionValue) { return String(optionValue || ''); };
        var onChange = typeof cfg.onChange === 'function' ? cfg.onChange : null;

        var group = document.createElement('div');
        group.className = sanitizeClassList(cfg.groupClass || 'pb-layout-choice') || 'pb-layout-choice';
        group.setAttribute('role', 'group');
        group.setAttribute('aria-label', String((field && field.label) || cfg.ariaLabel || ''));

        var buttons = [];
        options.forEach(function (optionValue) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = sanitizeClassList(cfg.buttonClass || 'pb-layout-choice-btn') || 'pb-layout-choice-btn';
            button.dataset.value = optionValue;
            var optionLabel = String(resolveLabel(field, optionValue) || optionValue).trim() || optionValue;
            button.title = optionLabel;
            button.setAttribute('aria-label', optionLabel);
            var optionContent = typeof field.renderOption === 'function'
                ? field.renderOption(optionValue, optionLabel)
                : null;
            if (optionContent && typeof optionContent === 'object' && typeof optionContent.nodeType === 'number') {
                button.appendChild(optionContent);
                button.classList.add('has-custom-content');
            } else if (typeof optionContent === 'string' && optionContent.trim() !== '') {
                button.innerHTML = optionContent;
                button.classList.add('has-custom-content');
            } else {
                button.textContent = optionLabel;
            }
            button.addEventListener('click', function () {
                if (activeValue === optionValue) {
                    return;
                }
                activeValue = optionValue;
                updateButtons();
                if (onChange) {
                    onChange(optionValue, true);
                }
            });
            buttons.push(button);
            group.appendChild(button);
        });

        function updateButtons() {
            buttons.forEach(function (button) {
                var isActive = String(button.dataset.value || '') === activeValue;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        updateButtons();
        return group;
    }

    function createBuilderTargetChoiceControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var labelText = String(cfg.label || 'Target').trim() || 'Target';
        var selfLabel = String(cfg.selfLabel || '').trim() || 'Same tab';
        var blankLabel = String(cfg.blankLabel || '').trim() || 'New tab';
        var onChange = typeof cfg.onChange === 'function' ? cfg.onChange : null;
        var classList = Array.isArray(cfg.extraClasses) ? cfg.extraClasses : [];
        var control = createBuilderChoiceControl({
            field: {
                key: 'target',
                label: labelText,
                type: 'select',
                control: 'choice',
                options: ['_self', '_blank']
            },
            options: ['_self', '_blank'],
            currentValue: String(cfg.currentValue || '').trim() || '_self',
            onChange: onChange,
            labelResolver: typeof cfg.labelResolver === 'function'
                ? cfg.labelResolver
                : function (_, optionValue) {
                    if (optionValue === '_blank') {
                        return blankLabel;
                    }
                    return selfLabel;
                }
        });

        classList.forEach(function (className) {
            var safeClass = String(className || '').trim();
            if (safeClass !== '') {
                control.classList.add(safeClass);
            }
        });

        return control;
    }

    function createBuilderNavigationEditorScaffold(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var headers = Array.isArray(cfg.headers) ? cfg.headers : [];

        var body = document.createElement('div');
        body.className = [
            'fc-builder-navigation-body',
            sanitizeClassList(cfg.bodyClass || '')
        ].filter(Boolean).join(' ');

        var list = document.createElement('div');
        list.className = [
            'fc-builder-navigation-list',
            sanitizeClassList(cfg.listClass || '')
        ].filter(Boolean).join(' ');
        body.appendChild(list);

        var headerRow = document.createElement('div');
        headerRow.className = [
            'fc-builder-navigation-row',
            'fc-builder-navigation-head',
            sanitizeClassList(cfg.headRowClass || '')
        ].filter(Boolean).join(' ');

        headers.forEach(function (header, headerIndex) {
            var meta = header && typeof header === 'object'
                ? header
                : { text: String(header || '') };
            var headerCell = document.createElement('div');
            headerCell.className = [
                'fc-builder-navigation-head-cell',
                sanitizeClassList(cfg.headCellClass || ''),
                sanitizeClassList(meta.className || '')
            ].filter(Boolean).join(' ');
            if (meta.blank === true || (!meta.className && headerIndex === 0 && String(meta.text || '') === '')) {
                headerCell.classList.add('is-blank');
            }
            headerCell.textContent = String(meta.text || '');
            headerRow.appendChild(headerCell);
        });
        list.appendChild(headerRow);

        function createRow(className) {
            var row = document.createElement('div');
            row.className = [
                'fc-builder-navigation-row',
                sanitizeClassList(className || '')
            ].filter(Boolean).join(' ');
            return row;
        }

        function appendItem(row, itemClass) {
            var item = document.createElement('div');
            item.className = [
                'fc-builder-navigation-item',
                sanitizeClassList(itemClass || '')
            ].filter(Boolean).join(' ');
            item.appendChild(row);
            list.appendChild(item);
            return item;
        }

        return {
            body: body,
            list: list,
            headerRow: headerRow,
            createRow: createRow,
            appendItem: appendItem
        };
    }

    function createBuilderNavigationInputCell(config) {
        return createBuilderInputControl(config);
    }

    function createBuilderNavigationSwitchCell(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var control = createBuilderToggleSwitchControl({
            label: String(cfg.label || ''),
            checked: !!cfg.checked,
            wrapperClass: cfg.wrapperClass || 'pb-switch-control',
            textClass: cfg.textClass || 'pb-switch-text',
            hitboxClass: cfg.hitboxClass || 'pb-switch-hitbox',
            inputClass: cfg.inputClass || 'pb-switch-input',
            uiClass: cfg.uiClass || 'pb-switch-ui'
        });

        String(cfg.cellClass || '').trim().split(/\s+/).filter(Boolean).forEach(function (className) {
            control.element.classList.add(className);
        });

        if (cfg.hideText !== false) {
            var textNode = control.element.querySelector('.pb-switch-text, .fc-builder-switch-text');
            if (textNode && textNode.parentNode) {
                textNode.parentNode.removeChild(textNode);
            }
        }

        if (cfg.title !== undefined) {
            control.element.title = String(cfg.title || '');
        }
        if (cfg.ariaLabel !== undefined) {
            control.input.setAttribute('aria-label', String(cfg.ariaLabel || ''));
        }

        return control;
    }

    function createBuilderInputControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var input = document.createElement('input');
        input.type = String(cfg.type || 'text').trim() || 'text';
        input.className = sanitizeClassList(cfg.className || 'form-input') || 'form-input';
        input.value = String(cfg.value || '');
        if (cfg.placeholder !== undefined) input.placeholder = String(cfg.placeholder || '');
        if (cfg.title !== undefined) input.title = String(cfg.title || '');
        if (cfg.ariaLabel !== undefined) input.setAttribute('aria-label', String(cfg.ariaLabel || ''));
        if (cfg.min !== undefined) input.min = String(cfg.min);
        if (cfg.max !== undefined) input.max = String(cfg.max);
        if (cfg.step !== undefined) input.step = String(cfg.step);
        if (cfg.inputMode !== undefined) input.inputMode = String(cfg.inputMode || '');
        if (cfg.autocomplete !== undefined) input.autocomplete = String(cfg.autocomplete || '');
        if (cfg.autocapitalize !== undefined) input.autocapitalize = String(cfg.autocapitalize || '');
        if (cfg.spellcheck !== undefined) input.spellcheck = !!cfg.spellcheck;
        if (cfg.disabled === true) input.disabled = true;
        if (cfg.readOnly === true) input.readOnly = true;
        return input;
    }

    function createBuilderTextareaControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var textarea = document.createElement('textarea');
        textarea.className = sanitizeClassList(cfg.className || 'form-input') || 'form-input';
        textarea.rows = Math.max(1, Number(cfg.rows || 3));
        textarea.value = String(cfg.value || '');
        if (cfg.placeholder !== undefined) textarea.placeholder = String(cfg.placeholder || '');
        if (cfg.title !== undefined) textarea.title = String(cfg.title || '');
        if (cfg.ariaLabel !== undefined) textarea.setAttribute('aria-label', String(cfg.ariaLabel || ''));
        if (cfg.disabled === true) textarea.disabled = true;
        if (cfg.noEditor === true) textarea.setAttribute('data-no-editor', '1');
        return textarea;
    }

    function createBuilderInspectorToolbarTitleRow(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var row = document.createElement('div');
        row.className = [
            'fc-builder-inspector-toolbar-title-row',
            sanitizeClassList(cfg.rowClass || '')
        ].filter(Boolean).join(' ');

        var input = createBuilderInputControl({
            className: cfg.inputClass || 'form-input',
            type: 'text',
            value: cfg.value,
            placeholder: cfg.placeholder,
            title: cfg.title,
            ariaLabel: cfg.ariaLabel
        });

        row.appendChild(input);

        return {
            element: row,
            input: input
        };
    }

    function createBuilderSpacingPanel(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var labels = cfg.labels && typeof cfg.labels === 'object' ? cfg.labels : {};
        var values = cfg.values && typeof cfg.values === 'object' ? cfg.values : {};
        var onInput = typeof cfg.onInput === 'function' ? cfg.onInput : null;
        var onReset = typeof cfg.onReset === 'function' ? cfg.onReset : null;

        var panel = document.createElement('div');
        panel.className = [
            'pb-spacing-panel',
            sanitizeClassList(cfg.panelClass || '')
        ].filter(Boolean).join(' ');

        var header = document.createElement('div');
        header.className = 'pb-spacing-header';
        panel.appendChild(header);

        var title = document.createElement('h4');
        title.className = 'pb-spacing-title';
        title.textContent = String(labels.title || '').trim();
        header.appendChild(title);

        var resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = sanitizeClassList(cfg.resetButtonClass || 'btn btn-ghost btn-sm') || 'btn btn-ghost btn-sm';
        resetBtn.setAttribute('data-action', 'spacing-reset');
        resetBtn.textContent = String(labels.reset || '').trim();
        header.appendChild(resetBtn);

        var inputs = {};

        function appendGroup(groupTitle, isPadding) {
            var group = document.createElement('div');
            group.className = 'pb-spacing-group';

            var groupTitleNode = document.createElement('div');
            groupTitleNode.className = 'pb-spacing-group-title';
            groupTitleNode.textContent = String(groupTitle || '').trim();
            group.appendChild(groupTitleNode);

            var grid = document.createElement('div');
            grid.className = 'pb-spacing-grid';
            group.appendChild(grid);

            [
                { key: isPadding ? 'pt' : 'mt', label: String(labels.top || '').trim() },
                { key: isPadding ? 'pr' : 'mr', label: String(labels.right || '').trim() },
                { key: isPadding ? 'pb' : 'mb', label: String(labels.bottom || '').trim() },
                { key: isPadding ? 'pl' : 'ml', label: String(labels.left || '').trim() }
            ].forEach(function (fieldConfig) {
                var fieldWrap = document.createElement('label');
                fieldWrap.className = 'pb-spacing-field';

                var text = document.createElement('span');
                text.textContent = fieldConfig.label;
                fieldWrap.appendChild(text);

                var input = createBuilderInputControl({
                    className: 'form-input pb-spacing-input',
                    type: 'number',
                    min: isPadding ? 0 : -240,
                    max: 240,
                    step: 1,
                    value: String(values[fieldConfig.key] == null ? 0 : values[fieldConfig.key]),
                    ariaLabel: fieldConfig.label
                });
                input.setAttribute('data-spacing-key', fieldConfig.key);

                if (onInput) {
                    input.addEventListener('input', function () {
                        onInput(fieldConfig.key, input.value, false);
                    });
                    input.addEventListener('change', function () {
                        onInput(fieldConfig.key, input.value, true);
                    });
                }

                inputs[fieldConfig.key] = input;
                fieldWrap.appendChild(input);
                grid.appendChild(fieldWrap);
            });

            panel.appendChild(group);
        }

        appendGroup(labels.margin || '', false);
        appendGroup(labels.padding || '', true);

        if (onReset) {
            resetBtn.addEventListener('click', function () {
                onReset();
            });
        }

        return {
            element: panel,
            resetButton: resetBtn,
            inputs: inputs,
            getInput: function (key) {
                return inputs[String(key || '').trim()] || null;
            }
        };
    }

    function createBuilderLinksQuickAddScaffold(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var classes = cfg.classes && typeof cfg.classes === 'object' ? cfg.classes : {};
        var labels = cfg.labels && typeof cfg.labels === 'object' ? cfg.labels : {};
        var typeOptions = Array.isArray(cfg.typeOptions) ? cfg.typeOptions : [];
        var targetOptions = Array.isArray(cfg.targetOptions) ? cfg.targetOptions : [];
        var selectClass = sanitizeClassList(cfg.selectClass || 'form-select') || 'form-select';
        var inputClass = sanitizeClassList(cfg.inputClass || 'form-input') || 'form-input';

        function className(key) {
            return sanitizeClassList(classes[key] || '');
        }

        var panel = document.createElement('div');
        panel.className = className('panel');

        var title = document.createElement('div');
        title.className = className('title');
        title.textContent = String(labels.title || '').trim();
        panel.appendChild(title);

        var existingWrap = document.createElement('div');
        existingWrap.className = className('existingWrap');
        var existingTitle = document.createElement('div');
        existingTitle.className = className('currentTitle');
        existingTitle.textContent = String(labels.currentTitle || '').trim();
        existingWrap.appendChild(existingTitle);
        var existingList = document.createElement('div');
        existingList.className = className('existingList');
        existingWrap.appendChild(existingList);
        panel.appendChild(existingWrap);

        var sourceTitle = document.createElement('div');
        sourceTitle.className = className('currentTitle');
        sourceTitle.textContent = String(labels.libraryTitle || '').trim();
        panel.appendChild(sourceTitle);

        var controls = document.createElement('div');
        controls.className = className('controls');

        var typeSelect = createBuilderSelectControl({
            className: selectClass,
            value: typeOptions.length ? typeOptions[0].value : '',
            options: typeOptions.map(function (entry) { return String((entry && entry.value) || ''); }),
            optionLabels: typeOptions.reduce(function (carry, entry) {
                if (entry && entry.value !== undefined) {
                    carry[String(entry.value)] = String(entry.label || '');
                }
                return carry;
            }, {})
        });
        controls.appendChild(typeSelect);

        var search = createBuilderInputControl({
            className: inputClass,
            type: 'search',
            placeholder: labels.searchPlaceholder
        });
        controls.appendChild(search);
        panel.appendChild(controls);

        var list = createBuilderSelectControl({
            className: [selectClass, className('list')].filter(Boolean).join(' '),
            value: '',
            options: [],
            ariaLabel: labels.listAria
        });
        panel.appendChild(list);

        var actions = document.createElement('div');
        actions.className = className('actions');
        var addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = sanitizeClassList(cfg.addButtonClass || 'btn btn-ghost btn-sm') || 'btn btn-ghost btn-sm';
        addButton.innerHTML = String(cfg.addButtonHtml || '');
        actions.appendChild(addButton);
        panel.appendChild(actions);

        var externalWrap = document.createElement('div');
        externalWrap.className = className('externalWrap');
        var externalTitle = document.createElement('div');
        externalTitle.className = className('currentTitle');
        externalTitle.textContent = String(labels.externalTitle || '').trim();
        externalWrap.appendChild(externalTitle);

        var externalGrid = document.createElement('div');
        externalGrid.className = className('externalGrid');

        var externalLabelInput = createBuilderInputControl({
            className: inputClass,
            type: 'text',
            placeholder: labels.externalLabelPlaceholder
        });
        externalGrid.appendChild(externalLabelInput);

        var externalUrlInput = createBuilderInputControl({
            className: inputClass,
            type: String(cfg.externalUrlType || 'url'),
            placeholder: labels.externalUrlPlaceholder
        });
        externalGrid.appendChild(externalUrlInput);

        var externalTargetSelect = createBuilderSelectControl({
            className: selectClass,
            value: targetOptions.length ? targetOptions[0].value : '',
            options: targetOptions.map(function (entry) { return String((entry && entry.value) || ''); }),
            optionLabels: targetOptions.reduce(function (carry, entry) {
                if (entry && entry.value !== undefined) {
                    carry[String(entry.value)] = String(entry.label || '');
                }
                return carry;
            }, {})
        });
        externalGrid.appendChild(externalTargetSelect);
        externalWrap.appendChild(externalGrid);

        var externalActions = document.createElement('div');
        externalActions.className = className('actions');
        var externalAddButton = document.createElement('button');
        externalAddButton.type = 'button';
        externalAddButton.className = sanitizeClassList(cfg.externalAddButtonClass || 'btn btn-secondary btn-sm') || 'btn btn-secondary btn-sm';
        externalAddButton.innerHTML = String(cfg.externalAddButtonHtml || '');
        externalActions.appendChild(externalAddButton);
        externalWrap.appendChild(externalActions);
        panel.appendChild(externalWrap);

        return {
            panel: panel,
            existingList: existingList,
            typeSelect: typeSelect,
            search: search,
            list: list,
            addButton: addButton,
            externalLabelInput: externalLabelInput,
            externalUrlInput: externalUrlInput,
            externalTargetSelect: externalTargetSelect,
            externalAddButton: externalAddButton
        };
    }

    function createBuilderLinksQuickAddEmptyState(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var empty = document.createElement('div');
        empty.className = sanitizeClassList(cfg.className || '') || '';
        empty.textContent = String(cfg.text || '').trim();
        return empty;
    }

    function createBuilderLinksQuickAddExistingItem(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var row = document.createElement('div');
        row.className = sanitizeClassList(cfg.rowClass || '') || '';

        var text = document.createElement('span');
        text.className = sanitizeClassList(cfg.textClass || '') || '';
        text.textContent = String(cfg.text || '').trim();
        row.appendChild(text);

        var removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = sanitizeClassList(cfg.removeButtonClass || 'btn btn-ghost btn-sm') || 'btn btn-ghost btn-sm';
        if (cfg.title !== undefined) {
            removeButton.title = String(cfg.title || '');
            removeButton.setAttribute('aria-label', String(cfg.title || ''));
        }
        if (cfg.html !== undefined) {
            removeButton.innerHTML = String(cfg.html || '');
        } else {
            removeButton.textContent = String(cfg.buttonText || '').trim();
        }
        row.appendChild(removeButton);

        return {
            element: row,
            text: text,
            removeButton: removeButton
        };
    }

    function createBuilderLinkSourceLibraryItems(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var source = Array.isArray(cfg.source) ? cfg.source : [];
        var sanitizeUrl = typeof cfg.sanitizeUrl === 'function' ? cfg.sanitizeUrl : function (value) { return String(value || '').trim(); };
        var normalizeType = typeof cfg.normalizeType === 'function' ? cfg.normalizeType : function (value) { return String(value || '').trim().toLowerCase(); };
        var normalizeSearchText = typeof cfg.normalizeSearchText === 'function' ? cfg.normalizeSearchText : function (value) { return String(value || '').trim().toLowerCase(); };
        var compareText = typeof cfg.compareText === 'function'
            ? cfg.compareText
            : function (left, right) { return String(left || '').localeCompare(String(right || '')); };

        var items = [];
        var seen = new Set();

        source.forEach(function (entry) {
            if (!entry || typeof entry !== 'object') {
                return;
            }

            var labelText = String(entry.label || '').trim();
            var rawUrl = String(entry.url || '').trim();
            if (!labelText || !rawUrl) {
                return;
            }

            var safeUrl = sanitizeUrl(rawUrl) || '#';
            var type = normalizeType(entry.type);
            var dedupeKey = normalizeSearchText(labelText) + '|' + safeUrl + '|' + type;
            if (seen.has(dedupeKey)) {
                return;
            }
            seen.add(dedupeKey);

            items.push({
                label: labelText,
                url: safeUrl,
                type: type
            });
        });

        return items.sort(function (a, b) {
            return compareText(a.label, b.label);
        });
    }

    function filterBuilderLinkSourceLibraryItems(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var sourceItems = Array.isArray(cfg.items) ? cfg.items : [];
        var selectedType = String(cfg.type || 'all').toLowerCase();
        var normalizeType = typeof cfg.normalizeType === 'function' ? cfg.normalizeType : function (value) { return String(value || '').trim().toLowerCase(); };
        var tokenizeSearchText = typeof cfg.tokenizeSearchText === 'function'
            ? cfg.tokenizeSearchText
            : function (value) {
                return String(value || '').trim().toLowerCase().split(/\s+/).filter(Boolean);
            };
        var terms = tokenizeSearchText(cfg.searchText);

        return sourceItems.filter(function (entry) {
            if (!entry || typeof entry !== 'object') {
                return false;
            }
            if (selectedType !== 'all' && normalizeType(entry.type) !== selectedType) {
                return false;
            }
            if (!terms.length) {
                return true;
            }
            var target = String(entry.label || '') + ' ' + String(entry.url || '') + ' ' + String(entry.type || '');
            var tokens = tokenizeSearchText(target);
            if (!tokens.length) {
                return false;
            }
            return terms.every(function (term) {
                return tokens.some(function (token) {
                    return token.indexOf(term) !== -1;
                });
            });
        });
    }

    function formatBuilderLinkLine(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var sanitizeUrl = typeof cfg.sanitizeUrl === 'function' ? cfg.sanitizeUrl : function (value) { return String(value || '').trim(); };
        var normalizeTarget = typeof cfg.normalizeTarget === 'function'
            ? cfg.normalizeTarget
            : function (value) { return String(value || '').trim(); };
        var label = String(cfg.label || '').trim();
        var url = sanitizeUrl(String(cfg.url || '').trim()) || '#';
        var target = normalizeTarget(String(cfg.target || ''), url);
        if (!label) {
            return '';
        }
        if (target === '_blank') {
            return label + '|' + url + '|_blank';
        }
        return label + '|' + url;
    }

    function serializeBuilderLinks(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var links = Array.isArray(cfg.links) ? cfg.links : [];
        return links.map(function (entry) {
            return formatBuilderLinkLine({
                label: entry && entry.label,
                url: entry && entry.url,
                target: entry && entry.target,
                sanitizeUrl: cfg.sanitizeUrl,
                normalizeTarget: cfg.normalizeTarget
            });
        }).filter(function (line) {
            return line !== '';
        }).join('\n');
    }

    function appendBuilderLinkSourceToRaw(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var sourceItem = cfg.sourceItem && typeof cfg.sourceItem === 'object' ? cfg.sourceItem : {};
        var labelText = String(sourceItem.label || '').trim();
        if (!labelText) {
            return { value: String(cfg.raw || ''), added: false };
        }

        var sanitizeUrl = typeof cfg.sanitizeUrl === 'function' ? cfg.sanitizeUrl : function (value) { return String(value || '').trim(); };
        var normalizeTarget = typeof cfg.normalizeTarget === 'function'
            ? cfg.normalizeTarget
            : function (value) { return String(value || '').trim(); };
        var normalizeSearchText = typeof cfg.normalizeSearchText === 'function'
            ? cfg.normalizeSearchText
            : function (value) { return String(value || '').trim().toLowerCase(); };
        var parseLinks = typeof cfg.parseLinks === 'function' ? cfg.parseLinks : function () { return []; };

        var safeUrl = sanitizeUrl(String(sourceItem.url || '').trim()) || '#';
        var safeTarget = normalizeTarget(String(sourceItem.target || ''), safeUrl);
        var current = parseLinks(cfg.raw);
        var exists = current.some(function (entry) {
            var existingLabel = normalizeSearchText(String((entry && entry.label) || '').trim());
            var existingUrl = sanitizeUrl(String((entry && entry.url) || '').trim()) || '#';
            var existingTarget = normalizeTarget(String((entry && entry.target) || ''), existingUrl);
            return existingLabel === normalizeSearchText(labelText) && existingUrl === safeUrl && existingTarget === safeTarget;
        });

        if (exists) {
            return { value: String(cfg.raw || ''), added: false };
        }

        var lines = String(cfg.raw || '')
            .split(/\r\n|\r|\n/)
            .map(function (line) { return String(line || '').trim(); })
            .filter(function (line) { return line !== ''; });

        lines.push(formatBuilderLinkLine({
            label: labelText,
            url: safeUrl,
            target: safeTarget,
            sanitizeUrl: sanitizeUrl,
            normalizeTarget: normalizeTarget
        }));

        return { value: lines.join('\n'), added: true };
    }

    function renderBuilderLinksQuickAddOptions(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var list = cfg.list;
        var addButton = cfg.addButton;
        var entries = Array.isArray(cfg.entries) ? cfg.entries : [];
        var emptyLabel = String(cfg.emptyLabel || '').trim();
        var formatOptionLabel = typeof cfg.formatOptionLabel === 'function'
            ? cfg.formatOptionLabel
            : function (entry) { return String((entry && entry.label) || ''); };

        if (!list) {
            return;
        }

        list.innerHTML = '';
        if (!entries.length) {
            var emptyOption = document.createElement('option');
            emptyOption.disabled = true;
            emptyOption.value = '';
            emptyOption.textContent = emptyLabel;
            list.appendChild(emptyOption);
            if (addButton) {
                addButton.disabled = true;
            }
            return;
        }

        entries.forEach(function (entry, entryIndex) {
            var option = document.createElement('option');
            option.value = String(entryIndex);
            option.textContent = String(formatOptionLabel(entry, entryIndex) || '');
            option.dataset.label = String((entry && entry.label) || '');
            option.dataset.url = String((entry && entry.url) || '');
            option.dataset.type = String((entry && entry.type) || '');
            list.appendChild(option);
        });

        if (addButton) {
            addButton.disabled = false;
        }
    }

    function appendBuilderLinksQuickAddSelection(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var addButton = cfg.addButton;
        var list = cfg.list;
        var normalizeType = typeof cfg.normalizeType === 'function'
            ? cfg.normalizeType
            : function (value) { return String(value || '').trim().toLowerCase(); };
        var appendToRaw = typeof cfg.appendToRaw === 'function' ? cfg.appendToRaw : null;
        var writeRaw = typeof cfg.writeRaw === 'function' ? cfg.writeRaw : null;
        var afterAppend = typeof cfg.afterAppend === 'function' ? cfg.afterAppend : null;

        if (!list || !appendToRaw || !writeRaw) {
            return false;
        }
        if (addButton && addButton.disabled) {
            return false;
        }

        var selectedOption = list.options[list.selectedIndex];
        if (!selectedOption || selectedOption.disabled) {
            return false;
        }

        var sourceItem = {
            label: String(selectedOption.dataset.label || '').trim(),
            url: String(selectedOption.dataset.url || '').trim(),
            type: normalizeType(selectedOption.dataset.type),
            target: '_self'
        };
        var merged = appendToRaw(sourceItem);
        if (!merged || !merged.added) {
            return false;
        }

        writeRaw(merged.value);
        if (afterAppend) {
            afterAppend();
        }
        return true;
    }

    function appendBuilderLinksQuickAddExternal(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var labelInput = cfg.labelInput;
        var urlInput = cfg.urlInput;
        var targetSelect = cfg.targetSelect;
        var appendToRaw = typeof cfg.appendToRaw === 'function' ? cfg.appendToRaw : null;
        var writeRaw = typeof cfg.writeRaw === 'function' ? cfg.writeRaw : null;
        var afterAppend = typeof cfg.afterAppend === 'function' ? cfg.afterAppend : null;
        var defaultTarget = String(cfg.defaultTarget || '_self');

        if (!labelInput || !urlInput || !targetSelect || !appendToRaw || !writeRaw) {
            return false;
        }

        var labelText = String(labelInput.value || '').trim();
        var urlText = String(urlInput.value || '').trim();
        if (!labelText || !urlText) {
            return false;
        }

        var merged = appendToRaw({
            label: labelText,
            url: urlText,
            type: 'external',
            target: targetSelect.value
        });
        if (!merged || !merged.added) {
            return false;
        }

        writeRaw(merged.value);
        if (afterAppend) {
            afterAppend();
        }
        labelInput.value = '';
        urlInput.value = '';
        targetSelect.value = defaultTarget;
        return true;
    }

    function createBuilderModalShell(config) {
        var cfg = config && typeof config === 'object' ? config : {};

        var overlay = document.createElement('div');
        overlay.className = sanitizeClassList(cfg.overlayClass || 'modal-overlay') || 'modal-overlay';

        var container = document.createElement('div');
        container.className = sanitizeClassList(cfg.containerClass || 'modal-container modal-sm') || 'modal-container modal-sm';
        overlay.appendChild(container);

        var header = document.createElement('div');
        header.className = sanitizeClassList(cfg.headerClass || 'modal-header') || 'modal-header';
        container.appendChild(header);

        var title = document.createElement('h3');
        title.className = sanitizeClassList(cfg.titleClass || 'modal-title') || 'modal-title';
        title.textContent = String(cfg.title || '').trim();
        header.appendChild(title);

        var closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = sanitizeClassList(cfg.closeButtonClass || 'modal-close') || 'modal-close';
        closeButton.innerHTML = String(cfg.closeButtonHtml || '&times;');
        header.appendChild(closeButton);

        var body = document.createElement('div');
        body.className = sanitizeClassList(cfg.bodyClass || 'modal-body') || 'modal-body';
        container.appendChild(body);

        var footer = document.createElement('div');
        footer.className = sanitizeClassList(cfg.footerClass || 'modal-footer') || 'modal-footer';
        container.appendChild(footer);

        return {
            overlay: overlay,
            container: container,
            header: header,
            title: title,
            closeButton: closeButton,
            body: body,
            footer: footer
        };
    }

    function createBuilderSyncPickerModal(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var list = Array.isArray(cfg.list) ? cfg.list : [];
        if (!list.length) {
            return null;
        }

        var shell = createBuilderModalShell({
            overlayClass: cfg.overlayClass,
            containerClass: cfg.containerClass,
            headerClass: cfg.headerClass,
            titleClass: cfg.titleClass,
            closeButtonClass: cfg.closeButtonClass,
            closeButtonHtml: cfg.closeButtonHtml,
            bodyClass: cfg.bodyClass,
            footerClass: cfg.footerClass,
            title: cfg.title
        });
        var overlay = shell.overlay;
        var body = shell.body;
        var footer = shell.footer;

        var formGroup = document.createElement('div');
        formGroup.className = sanitizeClassList(cfg.formGroupClass || 'form-group') || 'form-group';
        body.appendChild(formGroup);

        var label = document.createElement('label');
        label.className = sanitizeClassList(cfg.labelClass || 'form-label') || 'form-label';
        label.textContent = String(cfg.chooseLabel || '').trim();
        formGroup.appendChild(label);

        var select = createBuilderSelectControl({
            className: cfg.selectClass || 'form-select',
            value: String(cfg.initialIndex == null ? 0 : cfg.initialIndex),
            options: list.map(function (_, index) { return String(index); }),
            optionLabels: list.reduce(function (carry, entry, index) {
                var item = entry && typeof entry === 'object' ? entry : {};
                var optionLabel = typeof cfg.formatOptionLabel === 'function'
                    ? cfg.formatOptionLabel(item, index)
                    : String(item.label || '');
                carry[String(index)] = optionLabel;
                return carry;
            }, {})
        });
        if (cfg.selectExtraClass) {
            String(cfg.selectExtraClass).trim().split(/\s+/).filter(Boolean).forEach(function (className) {
                select.classList.add(className);
            });
        }
        formGroup.appendChild(select);

        var cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.className = sanitizeClassList(cfg.cancelButtonClass || 'btn btn-ghost') || 'btn btn-ghost';
        cancelButton.textContent = String(cfg.cancelLabel || '').trim();
        footer.appendChild(cancelButton);

        var applyButton = document.createElement('button');
        applyButton.type = 'button';
        applyButton.className = sanitizeClassList(cfg.applyButtonClass || 'btn btn-primary') || 'btn btn-primary';
        applyButton.textContent = String(cfg.applyLabel || '').trim();
        footer.appendChild(applyButton);

        return {
            overlay: overlay,
            select: select,
            closeButton: shell.closeButton,
            cancelButton: cancelButton,
            applyButton: applyButton
        };
    }

    function createBuilderSelectControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var select = document.createElement('select');
        select.className = sanitizeClassList(cfg.className || 'form-select') || 'form-select';
        var options = Array.isArray(cfg.options) ? cfg.options : [];
        var optionLabels = cfg.optionLabels && typeof cfg.optionLabels === 'object' ? cfg.optionLabels : {};
        var currentValue = cfg.value;
        options.forEach(function (optionValue) {
            var option = document.createElement('option');
            option.value = String(optionValue);
            option.textContent = Object.prototype.hasOwnProperty.call(optionLabels, optionValue)
                ? String(optionLabels[optionValue] || '')
                : String(optionValue || '');
            option.selected = String(currentValue) === String(optionValue);
            select.appendChild(option);
        });
        if (cfg.title !== undefined) select.title = String(cfg.title || '');
        if (cfg.ariaLabel !== undefined) select.setAttribute('aria-label', String(cfg.ariaLabel || ''));
        if (cfg.disabled === true) select.disabled = true;
        return select;
    }

    function createBuilderResponsiveEditor(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var variants = Array.isArray(cfg.variants) ? cfg.variants : [];
        var renderControl = typeof cfg.renderControl === 'function' ? cfg.renderControl : null;

        var grid = document.createElement('div');
        grid.className = [
            'pb-responsive-grid',
            'fc-builder-responsive-grid',
            sanitizeClassList(cfg.gridClass || '')
        ].filter(Boolean).join(' ');

        function appendVariant(variantConfig) {
            var variant = variantConfig && typeof variantConfig === 'object' ? variantConfig : {};
            var fieldWrap = document.createElement('label');
            fieldWrap.className = [
                'pb-responsive-field',
                'fc-builder-responsive-field',
                sanitizeClassList(variant.className || '')
            ].filter(Boolean).join(' ');

            var fieldLabel = document.createElement('span');
            fieldLabel.textContent = String(variant.label || '').trim();
            fieldWrap.appendChild(fieldLabel);

            if (renderControl) {
                var renderedControl = renderControl(variant);
                var controlNode = renderedControl && renderedControl.element ? renderedControl.element : renderedControl;
                if (controlNode && typeof controlNode.nodeType === 'number') {
                    fieldWrap.appendChild(controlNode);
                }
            }

            grid.appendChild(fieldWrap);
            return fieldWrap;
        }

        variants.forEach(appendVariant);

        return {
            element: grid,
            appendVariant: appendVariant
        };
    }

    function createBuilderActionsRow(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var buttonsConfig = Array.isArray(cfg.buttons) ? cfg.buttons : [];
        var row = document.createElement('div');
        row.className = [
            'pb-field-row',
            'fc-builder-field-row',
            sanitizeClassList(cfg.rowClass || '')
        ].filter(Boolean).join(' ');

        var buttonsByKey = {};

        buttonsConfig.forEach(function (buttonConfig, index) {
            var entry = buttonConfig && typeof buttonConfig === 'object' ? buttonConfig : {};
            var button = document.createElement('button');
            button.type = 'button';
            button.className = String(entry.className || 'btn btn-ghost btn-sm').trim() || 'btn btn-ghost btn-sm';
            if (entry.html) {
                button.innerHTML = String(entry.html);
            } else if (entry.icon) {
                button.innerHTML = '<i class="' + escapeAttr(String(entry.icon).trim()) + '" aria-hidden="true"></i> ' + escapeAttr(String(entry.label || '').trim());
            } else {
                button.textContent = String(entry.label || '').trim();
            }
            button.disabled = !!entry.disabled;
            if (entry.title) {
                button.title = String(entry.title).trim();
                button.setAttribute('aria-label', String(entry.title).trim());
            } else if (entry.label) {
                button.setAttribute('aria-label', String(entry.label).trim());
            }
            if (typeof entry.onClick === 'function') {
                button.addEventListener('click', function () {
                    if (button.disabled) {
                        return;
                    }
                    entry.onClick(button);
                });
            }
            row.appendChild(button);

            var buttonKey = String(entry.key || '').trim();
            if (buttonKey !== '') {
                buttonsByKey[buttonKey] = button;
            } else {
                buttonsByKey[String(index)] = button;
            }
        });

        return {
            element: row,
            buttons: buttonsByKey
        };
    }

    function createBuilderColorFieldRow(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var normalizeHex = typeof cfg.normalizeHex === 'function'
            ? cfg.normalizeHex
            : function (value) { return String(value || '').trim(); };

        var row = document.createElement('div');
        row.className = [
            'pb-color-row',
            'fc-builder-color-row',
            sanitizeClassList(cfg.rowClass || '')
        ].filter(Boolean).join(' ');

        var picker = document.createElement('input');
        picker.type = 'color';
        picker.className = [
            'pb-color-picker',
            'fc-builder-color-picker',
            sanitizeClassList(cfg.pickerClass || '')
        ].filter(Boolean).join(' ');

        var textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.className = sanitizeClassList(cfg.inputClass || 'form-input') || 'form-input';
        textInput.placeholder = String(cfg.placeholder || '#6366f1');

        var clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = sanitizeClassList(cfg.clearButtonClass || 'btn btn-ghost btn-sm') || 'btn btn-ghost btn-sm';
        clearBtn.textContent = String(cfg.clearLabel || 'Clear');

        var initialValue = String(cfg.value || '').trim();
        var initialHex = normalizeHex(initialValue);
        picker.value = initialHex || '#000000';
        textInput.value = initialValue;

        picker.addEventListener('input', function () {
            textInput.value = picker.value;
            if (typeof cfg.onInput === 'function') {
                cfg.onInput(picker.value);
            }
        });

        picker.addEventListener('change', function () {
            if (typeof cfg.onCommit === 'function') {
                cfg.onCommit(picker.value);
            }
        });

        textInput.addEventListener('input', function () {
            var nextValue = String(textInput.value || '').trim();
            var normalized = normalizeHex(nextValue);
            if (normalized) {
                picker.value = normalized;
            }
            if (typeof cfg.onInput === 'function') {
                cfg.onInput(nextValue);
            }
        });

        textInput.addEventListener('change', function () {
            if (typeof cfg.onCommit === 'function') {
                cfg.onCommit(String(textInput.value || '').trim());
            }
        });

        clearBtn.addEventListener('click', function () {
            textInput.value = '';
            picker.value = '#000000';
            if (typeof cfg.onCommit === 'function') {
                cfg.onCommit('');
            }
        });

        row.appendChild(picker);
        row.appendChild(textInput);
        row.appendChild(clearBtn);

        return {
            element: row,
            picker: picker,
            input: textInput,
            clearButton: clearBtn
        };
    }

    function createBuilderRepeaterCardScaffold(config) {
        var cfg = config && typeof config === 'object' ? config : {};

        var body = document.createElement('div');
        body.className = [
            'fc-builder-card-editor-body',
            sanitizeClassList(cfg.bodyClass || '')
        ].filter(Boolean).join(' ');

        var list = document.createElement('div');
        list.className = [
            'fc-builder-card-editor-list',
            sanitizeClassList(cfg.listClass || '')
        ].filter(Boolean).join(' ');
        body.appendChild(list);

        return {
            body: body,
            list: list
        };
    }

    function createBuilderRepeaterCard(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var attachHead = cfg.attachHead !== false;

        var card = document.createElement('div');
        card.className = [
            'fc-builder-card',
            sanitizeClassList(cfg.cardClass || '')
        ].filter(Boolean).join(' ');

        var head = document.createElement('div');
        head.className = [
            'fc-builder-card-head',
            sanitizeClassList(cfg.headClass || '')
        ].filter(Boolean).join(' ');
        if (attachHead) {
            card.appendChild(head);
        }

        var title = document.createElement('span');
        title.className = [
            'fc-builder-card-title',
            sanitizeClassList(cfg.titleClass || '')
        ].filter(Boolean).join(' ');
        title.textContent = String(cfg.title || '');
        head.appendChild(title);

        var removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = [
            'btn',
            'btn-ghost',
            'fc-builder-card-remove',
            sanitizeClassList(cfg.removeButtonClass || '')
        ].filter(Boolean).join(' ');
        removeButton.innerHTML = String(cfg.removeButtonHtml || '<i class="fas fa-trash" aria-hidden="true"></i>');
        head.appendChild(removeButton);

        var grid = document.createElement('div');
        grid.className = [
            'fc-builder-card-grid',
            sanitizeClassList(cfg.gridClass || '')
        ].filter(Boolean).join(' ');
        card.appendChild(grid);

        return {
            card: card,
            head: head,
            title: title,
            removeButton: removeButton,
            grid: grid
        };
    }

    function createBuilderRepeaterAddButton(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var button = document.createElement('button');
        button.type = 'button';
        button.className = [
            'btn',
            'btn-ghost',
            'btn-sm',
            'pb-feature-grid-content-add',
            sanitizeClassList(cfg.className || '')
        ].filter(Boolean).join(' ');
        button.innerHTML = String(cfg.html || '<i class="fas fa-plus" aria-hidden="true"></i>');
        if (cfg.title) {
            button.title = String(cfg.title);
            button.setAttribute('aria-label', String(cfg.title));
        }
        return button;
    }

    function createBuilderCardActionsRow(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var row = document.createElement('div');
        row.className = [
            'fc-builder-card-actions',
            sanitizeClassList(cfg.rowClass || '')
        ].filter(Boolean).join(' ');
        var controls = Array.isArray(cfg.controls) ? cfg.controls : [];
        controls.forEach(function (control) {
            if (control && typeof control.nodeType === 'number') {
                row.appendChild(control);
            }
        });
        return {
            element: row
        };
    }

    function createBuilderAdvancedPanel(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var panel = document.createElement('div');
        panel.className = [
            'fc-builder-advanced-panel',
            sanitizeClassList(cfg.panelClass || '')
        ].filter(Boolean).join(' ');

        var label = document.createElement('span');
        label.className = [
            'fc-builder-advanced-panel-label',
            sanitizeClassList(cfg.labelClass || '')
        ].filter(Boolean).join(' ');
        label.textContent = String(cfg.label || '');
        panel.appendChild(label);

        if (cfg.control instanceof Node) {
            panel.appendChild(cfg.control);
        }

        return {
            panel: panel,
            label: label
        };
    }

    function createBuilderAdvancedCard(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var card = document.createElement('div');
        card.className = [
            'fc-builder-advanced-card',
            sanitizeClassList(cfg.cardClass || '')
        ].filter(Boolean).join(' ');
        if (cfg.fieldKey) {
            card.dataset.fieldKey = String(cfg.fieldKey);
        }

        var title = document.createElement('label');
        title.className = [
            'fc-builder-advanced-card-title',
            sanitizeClassList(cfg.titleClass || '')
        ].filter(Boolean).join(' ');
        title.textContent = String(cfg.title || '');
        card.appendChild(title);

        var body = document.createElement('div');
        body.className = [
            'fc-builder-advanced-card-body',
            sanitizeClassList(cfg.bodyClass || '')
        ].filter(Boolean).join(' ');
        card.appendChild(body);

        return {
            card: card,
            title: title,
            body: body
        };
    }

    function createBuilderTextStyleControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var safeSettings = cfg.settings && typeof cfg.settings === 'object' ? cfg.settings : {};
        var safeField = cfg.field && typeof cfg.field === 'object' ? cfg.field : {};
        var label = typeof cfg.label === 'function'
            ? cfg.label
            : function (_, fallback) { return String(fallback || ''); };
        var resolvePrefix = typeof cfg.resolvePrefix === 'function' ? cfg.resolvePrefix : function () { return ''; };
        var resolveFallbackAlign = typeof cfg.resolveFallbackAlign === 'function'
            ? cfg.resolveFallbackAlign
            : function (settings, field) {
                return field && field.fallbackAlign !== undefined
                    ? field.fallbackAlign
                    : (settings.align || settings.contentAlign || 'left');
            };
        var normalizeAlign = typeof cfg.normalizeAlign === 'function'
            ? cfg.normalizeAlign
            : function (value) { return String(value || 'left').trim() || 'left'; };
        var prefix = String(resolvePrefix(safeField) || '').trim();
        var fallbackAlign = normalizeAlign(String(resolveFallbackAlign(safeSettings, safeField) || 'left'));
        var resolveState = typeof cfg.resolveState === 'function' ? cfg.resolveState : function () { return {}; };
        var current = resolveState(safeSettings, prefix, fallbackAlign) || {};
        var resolvePreviewItems = typeof cfg.resolvePreviewItems === 'function'
            ? cfg.resolvePreviewItems
            : function () { return []; };
        var previewItems = resolvePreviewItems(safeSettings, prefix, safeField) || [];
        var updatePreview = typeof cfg.updatePreview === 'function'
            ? cfg.updatePreview
            : function () {};
        var buildSettingKey = typeof cfg.buildSettingKey === 'function'
            ? cfg.buildSettingKey
            : function (_, suffix) { return String(suffix || ''); };
        var suffixes = cfg.suffixes && typeof cfg.suffixes === 'object' ? cfg.suffixes : {};
        var emit = function (suffix, value, refreshInspector) {
            if (typeof cfg.onChange === 'function') {
                cfg.onChange(buildSettingKey(prefix, suffix), value, refreshInspector !== false);
            }
        };
        var normalizeHexColor = typeof cfg.normalizeHexColor === 'function'
            ? cfg.normalizeHexColor
            : function (value) { return String(value || '').trim(); };
        var normalizeColor = typeof cfg.normalizeColor === 'function'
            ? cfg.normalizeColor
            : function (value) { return String(value || '').trim(); };
        var clampNumber = typeof cfg.clampNumber === 'function'
            ? cfg.clampNumber
            : function (value, min, max, fallback) {
                var number = Number(value);
                if (!Number.isFinite(number)) {
                    return Number.isFinite(fallback) ? fallback : min;
                }
                return Math.min(max, Math.max(min, number));
            };
        var createSelectControl = typeof cfg.createSelectControl === 'function' ? cfg.createSelectControl : null;
        var createColorControl = typeof cfg.createColorControl === 'function' ? cfg.createColorControl : null;
        var createAlignControl = typeof cfg.createAlignControl === 'function' ? cfg.createAlignControl : null;
        var updateIconPreview = typeof cfg.updateIconPreview === 'function' ? cfg.updateIconPreview : function () {};
        var openIconPicker = typeof cfg.openIconPicker === 'function' ? cfg.openIconPicker : function (_, callback) {
            if (typeof callback === 'function') {
                callback('');
            }
        };
        var fontOptions = Array.isArray(cfg.fontOptions) ? cfg.fontOptions : [];
        var getFontLabel = typeof cfg.getFontLabel === 'function'
            ? cfg.getFontLabel
            : function (value) { return String(value || ''); };
        var normalizeFont = typeof cfg.normalizeFont === 'function'
            ? cfg.normalizeFont
            : function (value) { return String(value || '').trim() || 'inherit'; };
        var sizeOptions = Array.isArray(cfg.sizeOptions) ? cfg.sizeOptions : [];
        var getSizeLabel = typeof cfg.getSizeLabel === 'function'
            ? cfg.getSizeLabel
            : function (value) { return String(value || ''); };
        var normalizeSize = typeof cfg.normalizeSize === 'function'
            ? cfg.normalizeSize
            : function (value) { return String(value || '').trim() || 'inherit'; };
        var getListLabel = typeof cfg.getListLabel === 'function'
            ? cfg.getListLabel
            : function (value) { return String(value || ''); };
        var getListGlyph = typeof cfg.getListGlyph === 'function'
            ? cfg.getListGlyph
            : function (value) { return String(value || ''); };
        var normalizeList = typeof cfg.normalizeList === 'function'
            ? cfg.normalizeList
            : function (value) { return String(value || '').trim() || 'none'; };
        var normalizeToggle = typeof cfg.normalizeToggle === 'function'
            ? cfg.normalizeToggle
            : function (value) { return !!value; };
        var normalizeIconPosition = typeof cfg.normalizeIconPosition === 'function'
            ? cfg.normalizeIconPosition
            : function (value) { return String(value || '').trim() === 'end' ? 'end' : 'start'; };
        var getIconPositionLabel = typeof cfg.getIconPositionLabel === 'function'
            ? cfg.getIconPositionLabel
            : function (value) { return String(value || ''); };

        var panel = document.createElement('div');
        panel.className = 'pb-textstyle-panel';

        var previewWrap = document.createElement('div');
        previewWrap.className = 'pb-textstyle-preview';
        var previewHead = document.createElement('div');
        previewHead.className = 'pb-textstyle-preview-head';
        var previewTitle = document.createElement('span');
        previewTitle.className = 'pb-textstyle-preview-title';
        previewTitle.textContent = label('textStylePreviewLabel', 'Aperçu');
        previewHead.appendChild(previewTitle);
        var previewColorInput = document.createElement('input');
        previewColorInput.type = 'text';
        previewColorInput.className = 'form-input pb-textstyle-preview-color-input';
        previewColorInput.placeholder = '#RRGGBB';
        previewColorInput.maxLength = 7;
        previewColorInput.autocomplete = 'on';
        previewColorInput.spellcheck = false;
        previewColorInput.setAttribute('aria-label', label('textStyleColor', 'Couleur'));
        previewColorInput.title = label('textStyleColor', 'Couleur');
        previewHead.appendChild(previewColorInput);
        previewWrap.appendChild(previewHead);

        var previewValues = [];
        if (previewItems.length > 1) {
            var previewList = document.createElement('div');
            previewList.className = 'pb-textstyle-preview-list';
            previewItems.forEach(function (item) {
                var card = document.createElement('div');
                card.className = 'pb-textstyle-preview-card';
                var itemLabel = document.createElement('div');
                itemLabel.className = 'pb-textstyle-preview-item-label';
                itemLabel.textContent = String(item && item.label ? item.label : '').trim();
                if (itemLabel.textContent !== '') {
                    card.appendChild(itemLabel);
                }
                var previewValue = document.createElement('div');
                previewValue.className = 'pb-textstyle-preview-value';
                card.appendChild(previewValue);
                previewList.appendChild(card);
                previewValues.push({
                    node: previewValue,
                    text: String(item && item.text ? item.text : '').trim() || label('textStylePreviewSample', 'Preview text')
                });
            });
            previewWrap.appendChild(previewList);
        } else {
            var singlePreviewValue = document.createElement('div');
            singlePreviewValue.className = 'pb-textstyle-preview-value';
            previewWrap.appendChild(singlePreviewValue);
            previewValues.push({
                node: singlePreviewValue,
                text: String((previewItems[0] && previewItems[0].text) || '').trim() || label('textStylePreviewSample', 'Preview text')
            });
        }
        panel.appendChild(previewWrap);

        previewValues.forEach(function (entry) {
            updatePreview(entry.node, current, entry.text);
        });
        var primaryPreviewNode = previewValues[0] ? previewValues[0].node : null;
        var refreshPreviews = function () {
            previewValues.forEach(function (entry) {
                updatePreview(entry.node, current, entry.text);
            });
        };

        var controls = document.createElement('div');
        controls.className = 'pb-textstyle-controls';
        panel.appendChild(controls);

        var toolbar = document.createElement('div');
        toolbar.className = 'pb-textstyle-toolbar';
        controls.appendChild(toolbar);

        var toolbarGroup = document.createElement('div');
        toolbarGroup.className = 'pb-textstyle-toolbar-group';
        toolbar.appendChild(toolbarGroup);

        var colorControl = null;
        var rgbStringToHex = function (value) {
            var match = String(value || '').trim().match(/^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i);
            if (!match) {
                return '';
            }
            var channels = [match[1], match[2], match[3]]
                .map(function (part) { return clampNumber(Number(part), 0, 255, 0); })
                .map(function (num) { return Math.round(num).toString(16).padStart(2, '0'); });
            return '#' + channels.join('');
        };
        var resolveDisplayHexColor = function (value) {
            var directHex = normalizeHexColor(value);
            if (directHex !== '') {
                return String(directHex).toUpperCase();
            }
            var safeColor = normalizeColor(value);
            if (safeColor !== '') {
                var rgbHex = rgbStringToHex(safeColor);
                if (rgbHex !== '') {
                    return rgbHex.toUpperCase();
                }
            }
            if (primaryPreviewNode && window.getComputedStyle) {
                var computedColor = String(window.getComputedStyle(primaryPreviewNode).color || '').trim();
                var computedHex = rgbStringToHex(computedColor);
                if (computedHex !== '') {
                    return computedHex.toUpperCase();
                }
            }
            return '';
        };
        var syncPreviewColorInput = function (value) {
            previewColorInput.value = resolveDisplayHexColor(value);
            previewColorInput.classList.remove('is-invalid');
        };
        var resolveHexCandidate = function (value) {
            var trimmed = String(value || '').trim();
            if (trimmed === '') {
                return '';
            }
            var candidate = trimmed.charAt(0) === '#' ? trimmed : ('#' + trimmed);
            return normalizeHexColor(candidate);
        };
        var applyTextColor = function (nextColor, refreshInspector) {
            var safeColor = normalizeColor(nextColor);
            current.color = safeColor;
            if (colorControl && typeof colorControl.setValue === 'function') {
                colorControl.setValue(safeColor);
            }
            refreshPreviews();
            syncPreviewColorInput(safeColor);
            emit(suffixes.color, safeColor, refreshInspector !== false);
        };

        syncPreviewColorInput(current.color);
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () {
                syncPreviewColorInput(current.color);
            });
        }
        previewColorInput.addEventListener('input', function () {
            var rawValue = String(previewColorInput.value || '').trim();
            if (rawValue === '') {
                current.color = '';
                if (colorControl && typeof colorControl.setValue === 'function') {
                    colorControl.setValue('');
                }
                refreshPreviews();
                previewColorInput.classList.remove('is-invalid');
                return;
            }
            var safeHex = resolveHexCandidate(rawValue);
            if (safeHex !== '') {
                current.color = String(safeHex).toUpperCase();
                if (colorControl && typeof colorControl.setValue === 'function') {
                    colorControl.setValue(current.color);
                }
                refreshPreviews();
                previewColorInput.classList.remove('is-invalid');
                return;
            }
            previewColorInput.classList.add('is-invalid');
        });
        var commitPreviewColorInput = function (refreshInspector) {
            var rawValue = String(previewColorInput.value || '').trim();
            if (rawValue === '') {
                applyTextColor('', refreshInspector !== false);
                return;
            }
            var safeHex = resolveHexCandidate(rawValue);
            if (safeHex === '') {
                syncPreviewColorInput(current.color);
                return;
            }
            applyTextColor(String(safeHex).toUpperCase(), refreshInspector !== false);
        };
        previewColorInput.addEventListener('change', function () {
            commitPreviewColorInput(true);
        });
        previewColorInput.addEventListener('blur', function () {
            commitPreviewColorInput(true);
        });
        previewColorInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }
            event.preventDefault();
            commitPreviewColorInput(true);
            previewColorInput.blur();
        });

        var fontSection = document.createElement('div');
        fontSection.className = 'pb-textstyle-toolbar-section pb-textstyle-toolbar-section-font';
        var fontSectionHead = document.createElement('div');
        fontSectionHead.className = 'pb-textstyle-toolbar-section-head';
        var fontSectionTitle = document.createElement('span');
        fontSectionTitle.className = 'pb-textstyle-toolbar-section-title';
        fontSectionTitle.textContent = label('textStyleGroupFont', 'Police');
        fontSectionHead.appendChild(fontSectionTitle);
        fontSection.appendChild(fontSectionHead);
        var fontSectionBody = document.createElement('div');
        fontSectionBody.className = 'pb-textstyle-toolbar-section-body';
        fontSection.appendChild(fontSectionBody);
        toolbarGroup.appendChild(fontSection);

        var fontPicker = createSelectControl ? createSelectControl({
            iconClass: 'fas fa-font',
            label: label('textStyleFont', 'Police'),
            ariaLabel: label('textStyleFont', 'Police'),
            currentLabel: function (value) { return getFontLabel(value); }
        }) : null;
        if (fontPicker && fontPicker.select) {
            fontOptions.forEach(function (optionValue) {
                var option = document.createElement('option');
                option.value = optionValue;
                option.textContent = getFontLabel(optionValue);
                option.selected = current.font === optionValue;
                fontPicker.select.appendChild(option);
            });
            var handleFontUpdate = function (refreshInspector) {
                current.font = normalizeFont(fontPicker.select.value);
                refreshPreviews();
                if (typeof fontPicker.updateTitles === 'function') {
                    fontPicker.updateTitles();
                }
                emit(suffixes.font, current.font, refreshInspector !== false);
            };
            fontPicker.select.addEventListener('input', function () {
                handleFontUpdate(false);
            });
            fontPicker.select.addEventListener('change', function () {
                handleFontUpdate(true);
            });
            if (typeof fontPicker.updateTitles === 'function') {
                fontPicker.updateTitles();
            }
            fontSectionBody.appendChild(fontPicker.wrapper);
        }

        var getComputedPreviewFontPx = function () {
            if (!primaryPreviewNode || !window.getComputedStyle) {
                return '';
            }
            var raw = String(window.getComputedStyle(primaryPreviewNode).fontSize || '').trim();
            var match = raw.match(/^(\d+(?:\.\d+)?)px$/i);
            if (!match) {
                return '';
            }
            var number = Number(match[1]);
            if (!Number.isFinite(number)) {
                return '';
            }
            return String(Math.round(number));
        };
        var getSizePickerDisplayValue = function (value) {
            var safeSize = normalizeSize(value);
            if (safeSize !== 'inherit') {
                return getSizeLabel(safeSize);
            }
            var computed = getComputedPreviewFontPx();
            return computed !== '' ? computed : '16';
        };

        if (!(safeField.disableSize === true) && createSelectControl) {
            var sizePicker = createSelectControl({
                iconClass: 'fas fa-text-height',
                label: label('textStyleSize', 'Taille'),
                ariaLabel: label('textStyleSize', 'Taille'),
                currentLabel: function (value) { return getSizePickerDisplayValue(value); }
            });
            if (sizePicker && sizePicker.wrapper && sizePicker.select) {
                sizePicker.wrapper.classList.add('pb-textstyle-toolbar-picker-size');
                var syncSizePickerButton = function () {
                    if (!sizePicker.button) {
                        return;
                    }
                    sizePicker.button.innerHTML = '<span class="pb-textstyle-size-value" aria-hidden="true">' + escapeHtml(getSizePickerDisplayValue(current.size)) + '</span>';
                };
                sizeOptions.forEach(function (optionValue) {
                    var option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = getSizeLabel(optionValue);
                    option.selected = current.size === optionValue;
                    sizePicker.select.appendChild(option);
                });
                var handleSizeUpdate = function (refreshInspector) {
                    current.size = normalizeSize(sizePicker.select.value);
                    refreshPreviews();
                    syncSizePickerButton();
                    if (typeof sizePicker.updateTitles === 'function') {
                        sizePicker.updateTitles();
                    }
                    emit(suffixes.size, current.size, refreshInspector !== false);
                };
                sizePicker.select.addEventListener('input', function () {
                    handleSizeUpdate(false);
                });
                sizePicker.select.addEventListener('change', function () {
                    handleSizeUpdate(true);
                });
                syncSizePickerButton();
                if (typeof sizePicker.updateTitles === 'function') {
                    sizePicker.updateTitles();
                }
                fontSectionBody.appendChild(sizePicker.wrapper);
            }
        }

        if (createColorControl) {
            colorControl = createColorControl({
                label: label('textStyleColor', 'Couleur'),
                value: current.color,
                onUpdate: function (nextColor, refreshInspector) {
                    applyTextColor(nextColor, refreshInspector !== false);
                }
            });
            if (colorControl && colorControl.wrapper) {
                fontSectionBody.appendChild(colorControl.wrapper);
            }
        }

        var fontSep = document.createElement('span');
        fontSep.className = 'pb-textstyle-toolbar-sep';
        fontSectionBody.appendChild(fontSep);

        var formatControls = document.createElement('div');
        formatControls.className = 'pb-textstyle-toolbar-buttons';
        var createFormatToggle = function (suffix, iconClass, labelKey, fallbackLabel, stateKey) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'pb-textstyle-toolbar-btn';
            button.title = label(labelKey, fallbackLabel);
            button.setAttribute('aria-label', label(labelKey, fallbackLabel));
            button.innerHTML = '<i class="' + escapeAttr(iconClass) + '" aria-hidden="true"></i>';
            var refreshActive = function () {
                var active = normalizeToggle(current[stateKey], false);
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            };
            button.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });
            button.addEventListener('click', function () {
                current[stateKey] = !normalizeToggle(current[stateKey], false);
                refreshActive();
                refreshPreviews();
                emit(suffix, current[stateKey] ? '1' : '0', true);
            });
            refreshActive();
            return button;
        };
        formatControls.appendChild(createFormatToggle(suffixes.bold, 'fas fa-bold', 'textStyleBold', 'Bold', 'bold'));
        formatControls.appendChild(createFormatToggle(suffixes.italic, 'fas fa-italic', 'textStyleItalic', 'Italic', 'italic'));
        formatControls.appendChild(createFormatToggle(suffixes.underline, 'fas fa-underline', 'textStyleUnderline', 'Underline', 'underline'));
        fontSectionBody.appendChild(formatControls);

        var paragraphSep = document.createElement('span');
        paragraphSep.className = 'pb-textstyle-toolbar-sep';
        toolbarGroup.appendChild(paragraphSep);

        var paragraphSection = document.createElement('div');
        paragraphSection.className = 'pb-textstyle-toolbar-section pb-textstyle-toolbar-section-paragraph';
        var paragraphSectionHead = document.createElement('div');
        paragraphSectionHead.className = 'pb-textstyle-toolbar-section-head';
        var paragraphSectionTitle = document.createElement('span');
        paragraphSectionTitle.className = 'pb-textstyle-toolbar-section-title';
        paragraphSectionTitle.textContent = label('textStyleGroupParagraph', 'Paragraphe');
        paragraphSectionHead.appendChild(paragraphSectionTitle);
        paragraphSection.appendChild(paragraphSectionHead);
        var paragraphSectionBody = document.createElement('div');
        paragraphSectionBody.className = 'pb-textstyle-toolbar-section-body';
        paragraphSection.appendChild(paragraphSectionBody);

        if (createAlignControl) {
            var alignField = {
                key: prefix + 'Align',
                label: label('textStyleAlign', 'Alignement du texte'),
                type: 'select',
                control: 'align',
                options: ['left', 'center', 'right']
            };
            var alignControl = createAlignControl(alignField, current.align, function (nextValue, refreshInspector) {
                current.align = normalizeAlign(nextValue);
                refreshPreviews();
                emit(suffixes.align, current.align, refreshInspector !== false);
            });
            if (alignControl) {
                alignControl.classList.remove('pb-align-control');
                alignControl.classList.add('pb-textstyle-toolbar-align');
                paragraphSectionBody.appendChild(alignControl);
            }
        }

        if (!(safeField.disableList === true) && createSelectControl) {
            var listPicker = createSelectControl({
                iconClass: 'fas fa-list-ul',
                label: label('textStyleList', 'Style de puce'),
                ariaLabel: label('textStyleList', 'Style de puce'),
                currentLabel: function (value) { return getListLabel(value); }
            });
            if (listPicker && listPicker.wrapper && listPicker.select) {
                listPicker.wrapper.classList.add('pb-textstyle-toolbar-picker-list');
                listPicker.select.classList.add('pb-textstyle-toolbar-native-select-list');
                var updateListPickerButton = function () {
                    if (listPicker.button) {
                        listPicker.button.innerHTML = '<i class="fas fa-list-ul" aria-hidden="true"></i>';
                    }
                };
                updateListPickerButton();
                ['none', 'disc', 'circle', 'square'].forEach(function (optionValue) {
                    var option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = optionValue === 'none' ? '' : getListGlyph(optionValue);
                    option.selected = normalizeList(current.list || 'none') === optionValue;
                    option.title = getListLabel(optionValue);
                    listPicker.select.appendChild(option);
                });
                var handleListUpdate = function (refreshInspector) {
                    current.list = normalizeList(listPicker.select.value);
                    refreshPreviews();
                    updateListPickerButton();
                    if (typeof listPicker.updateTitles === 'function') {
                        listPicker.updateTitles();
                    }
                    emit(suffixes.list, current.list, refreshInspector !== false);
                };
                listPicker.select.addEventListener('input', function () {
                    handleListUpdate(false);
                });
                listPicker.select.addEventListener('change', function () {
                    handleListUpdate(true);
                });
                if (typeof listPicker.updateTitles === 'function') {
                    listPicker.updateTitles();
                }
                var listSep = document.createElement('span');
                listSep.className = 'pb-textstyle-toolbar-sep';
                paragraphSectionBody.appendChild(listSep);
                paragraphSectionBody.appendChild(listPicker.wrapper);
            }
        }
        toolbarGroup.appendChild(paragraphSection);

        if (!(safeField.disableIcon === true)) {
            var pushSep = document.createElement('span');
            pushSep.className = 'pb-textstyle-toolbar-sep pb-textstyle-toolbar-sep-push';
            toolbarGroup.appendChild(pushSep);

            var iconSection = document.createElement('div');
            iconSection.className = 'pb-textstyle-toolbar-section pb-textstyle-toolbar-section-icons';
            var iconSectionHead = document.createElement('div');
            iconSectionHead.className = 'pb-textstyle-toolbar-section-head';
            var iconSectionTitle = document.createElement('span');
            iconSectionTitle.className = 'pb-textstyle-toolbar-section-title';
            iconSectionTitle.textContent = label('textStyleIcon', 'Icône');
            iconSectionHead.appendChild(iconSectionTitle);
            iconSection.appendChild(iconSectionHead);
            var iconSectionBody = document.createElement('div');
            iconSectionBody.className = 'pb-textstyle-toolbar-section-body';
            iconSection.appendChild(iconSectionBody);

            var iconPreview = document.createElement('span');
            iconPreview.className = 'pb-icon-preview pb-textstyle-toolbar-icon-preview';
            updateIconPreview(iconPreview, current.icon);
            iconSectionBody.appendChild(iconPreview);

            var pickBtn = document.createElement('button');
            pickBtn.type = 'button';
            pickBtn.className = 'pb-textstyle-toolbar-btn';
            pickBtn.title = label('chooseIcon', 'Choisir une icône');
            pickBtn.setAttribute('aria-label', label('chooseIcon', 'Choisir une icône'));
            pickBtn.innerHTML = '<i class="fas fa-plus" aria-hidden="true"></i>';
            pickBtn.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });
            pickBtn.addEventListener('click', function () {
                openIconPicker(current.icon, function (picked) {
                    current.icon = String(picked || '').trim();
                    updateIconPreview(iconPreview, current.icon);
                    refreshPreviews();
                    emit(suffixes.icon, current.icon, true);
                });
            });
            iconSectionBody.appendChild(pickBtn);

            var clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'pb-textstyle-toolbar-btn';
            clearBtn.title = label('removeIcon', 'Retirer l\'icône');
            clearBtn.setAttribute('aria-label', label('removeIcon', 'Retirer l\'icône'));
            clearBtn.innerHTML = '<i class="fas fa-eraser" aria-hidden="true"></i>';
            clearBtn.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });
            clearBtn.addEventListener('click', function () {
                current.icon = '';
                updateIconPreview(iconPreview, '');
                refreshPreviews();
                emit(suffixes.icon, '', true);
            });
            iconSectionBody.appendChild(clearBtn);

            if (createSelectControl) {
                var iconPositionPicker = createSelectControl({
                    iconClass: 'fas fa-right-left',
                    label: label('textStyleIconPosition', 'Position de l\'icône'),
                    ariaLabel: label('textStyleIconPosition', 'Position de l\'icône'),
                    currentLabel: function (value) { return getIconPositionLabel(value); }
                });
                if (iconPositionPicker && iconPositionPicker.select) {
                    var optionStart = document.createElement('option');
                    optionStart.value = 'start';
                    optionStart.textContent = getIconPositionLabel('start');
                    optionStart.selected = current.iconPosition === 'start';
                    iconPositionPicker.select.appendChild(optionStart);
                    var optionEnd = document.createElement('option');
                    optionEnd.value = 'end';
                    optionEnd.textContent = getIconPositionLabel('end');
                    optionEnd.selected = current.iconPosition === 'end';
                    iconPositionPicker.select.appendChild(optionEnd);
                    var handleIconPositionUpdate = function (refreshInspector) {
                        current.iconPosition = normalizeIconPosition(iconPositionPicker.select.value);
                        refreshPreviews();
                        if (typeof iconPositionPicker.updateTitles === 'function') {
                            iconPositionPicker.updateTitles();
                        }
                        emit(suffixes.iconPosition, current.iconPosition, refreshInspector !== false);
                    };
                    iconPositionPicker.select.addEventListener('input', function () {
                        handleIconPositionUpdate(false);
                    });
                    iconPositionPicker.select.addEventListener('change', function () {
                        handleIconPositionUpdate(true);
                    });
                    if (typeof iconPositionPicker.updateTitles === 'function') {
                        iconPositionPicker.updateTitles();
                    }
                    iconSectionBody.appendChild(iconPositionPicker.wrapper);
                }
            }

            toolbarGroup.appendChild(iconSection);
        }
        refreshPreviews();
        return panel;
    }

    function updateBuilderTextStylePreview(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var container = cfg.container;
        if (!container) {
            return;
        }

        var state = cfg.state && typeof cfg.state === 'object' ? cfg.state : {};
        var normalizeAlign = typeof cfg.normalizeAlign === 'function'
            ? cfg.normalizeAlign
            : function (value) { return String(value || 'left').trim() || 'left'; };
        var normalizeFont = typeof cfg.normalizeFont === 'function'
            ? cfg.normalizeFont
            : function (value) { return String(value || 'inherit').trim() || 'inherit'; };
        var normalizeSize = typeof cfg.normalizeSize === 'function'
            ? cfg.normalizeSize
            : function (value) { return String(value || 'inherit').trim() || 'inherit'; };
        var normalizeToggle = typeof cfg.normalizeToggle === 'function'
            ? cfg.normalizeToggle
            : function (value) { return !!value; };
        var normalizeColor = typeof cfg.normalizeColor === 'function'
            ? cfg.normalizeColor
            : function (value) { return String(value || '').trim(); };
        var normalizeList = typeof cfg.normalizeList === 'function'
            ? cfg.normalizeList
            : function (value) { return String(value || 'none').trim() || 'none'; };
        var normalizeIconPosition = typeof cfg.normalizeIconPosition === 'function'
            ? cfg.normalizeIconPosition
            : function (value) { return String(value || 'start').trim() === 'end' ? 'end' : 'start'; };
        var getFontFamily = typeof cfg.getFontFamily === 'function'
            ? cfg.getFontFamily
            : function () { return ''; };
        var fallbackPreviewText = String(cfg.fallbackPreviewText || 'Preview text');

        var safeAlign = normalizeAlign(String(state.align || 'left'));
        var safeFont = normalizeFont(String(state.font || 'inherit'));
        var safeSize = normalizeSize(String(state.size || 'inherit'));
        var safeBold = normalizeToggle(state.bold, false);
        var safeItalic = normalizeToggle(state.italic, false);
        var safeUnderline = normalizeToggle(state.underline, false);
        var safeColor = normalizeColor(String(state.color || ''));
        var safeList = normalizeList(String(state.list || 'none'));
        var safeIconPosition = normalizeIconPosition(String(state.iconPosition || 'start'));
        var safeIconClass = String(state.icon || '')
            .trim()
            .split(/\s+/)
            .filter(function (token) { return /^[a-z0-9_-]+$/i.test(token); })
            .join(' ');

        container.classList.remove('is-align-left', 'is-align-center', 'is-align-right');
        container.classList.add('is-align-' + safeAlign);
        container.classList.toggle('is-list-style', safeList !== 'none');
        container.style.color = safeColor !== '' ? safeColor : '';
        var fontFamily = getFontFamily(safeFont);
        container.style.fontFamily = fontFamily !== '' ? fontFamily : '';
        container.style.fontSize = safeSize !== 'inherit' ? safeSize : '';
        container.style.setProperty('--pb-textstyle-marker-size', safeSize !== 'inherit' ? safeSize : '1em');
        container.innerHTML = '';

        var appendIcon = function () {
            if (safeIconClass === '') {
                return;
            }
            var icon = document.createElement('i');
            icon.className = safeIconClass + ' pb-textstyle-preview-icon';
            icon.setAttribute('aria-hidden', 'true');
            container.appendChild(icon);
        };

        var appendListMarker = function () {
            if (safeList === 'none') {
                return;
            }
            var marker = document.createElement('span');
            marker.className = 'pb-textstyle-preview-list-marker';
            marker.setAttribute('aria-hidden', 'true');
            marker.textContent = safeList === 'circle' ? '○' : (safeList === 'square' ? '■' : '●');
            container.appendChild(marker);
        };

        var textNode = document.createElement('span');
        textNode.className = 'pb-textstyle-preview-text';
        textNode.textContent = String(cfg.previewText || '').trim() || fallbackPreviewText;
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

    function createBuilderAlignControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var field = cfg.field && typeof cfg.field === 'object' ? cfg.field : {};
        var options = Array.isArray(cfg.options)
            ? cfg.options.map(function (option) { return String(option || '').trim().toLowerCase(); }).filter(function (option) { return option !== ''; })
            : ['left', 'center', 'right'];
        var resolveLabel = typeof cfg.labelResolver === 'function'
            ? cfg.labelResolver
            : function (_, optionValue) { return String(optionValue || ''); };
        var onChange = typeof cfg.onChange === 'function' ? cfg.onChange : null;
        var iconMap = cfg.iconMap && typeof cfg.iconMap === 'object'
            ? cfg.iconMap
            : {
                left: 'fas fa-align-left',
                center: 'fas fa-align-center',
                right: 'fas fa-align-right'
            };
        var safeCurrent = String(cfg.currentValue || '').trim().toLowerCase();
        var activeValue = options.indexOf(safeCurrent) !== -1 ? safeCurrent : (options[0] || 'left');

        var group = document.createElement('div');
        group.className = sanitizeClassList(cfg.groupClass || 'pb-align-control') || 'pb-align-control';
        group.setAttribute('role', 'group');
        group.setAttribute('aria-label', String((field && field.label) || cfg.ariaLabel || 'Alignement'));

        var buttons = [];
        options.forEach(function (optionValue) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = sanitizeClassList(cfg.buttonClass || 'pb-align-option') || 'pb-align-option';
            button.dataset.value = optionValue;
            var optionLabel = String(resolveLabel(field, optionValue) || optionValue).trim() || optionValue;
            button.title = optionLabel;
            button.setAttribute('aria-label', optionLabel);
            button.innerHTML = iconMap[optionValue]
                ? '<i class="' + escapeAttr(iconMap[optionValue]) + '" aria-hidden="true"></i>'
                : escapeAttr(optionValue);
            var pointerHandled = false;

            function applyValue() {
                if (activeValue === optionValue) {
                    return;
                }
                activeValue = optionValue;
                updateButtons();
                if (onChange) {
                    onChange(optionValue, true);
                }
            }

            button.addEventListener('mousedown', function (event) {
                if (event.button !== 0) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                pointerHandled = true;
                applyValue();
            });
            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                if (pointerHandled) {
                    pointerHandled = false;
                    return;
                }
                applyValue();
            });
            button.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }
                event.preventDefault();
                applyValue();
            });
            buttons.push(button);
            group.appendChild(button);
        });

        function updateButtons() {
            buttons.forEach(function (button) {
                var isActive = String(button.dataset.value || '') === activeValue;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        updateButtons();
        return group;
    }

    function createBuilderInspectorSheet(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var titleText = String(cfg.title || '').trim();
        var closeLabel = String(cfg.closeLabel || '').trim();
        var requestClose = typeof cfg.onRequestClose === 'function' ? cfg.onRequestClose : null;

        var sheet = document.createElement('div');
        sheet.className = [
            'fc-builder-sheet',
            sanitizeClassList(cfg.sheetClass || '')
        ].filter(Boolean).join(' ');
        sheet.setAttribute('aria-hidden', 'true');

        var backdrop = document.createElement('div');
        backdrop.className = [
            'fc-builder-sheet-backdrop',
            sanitizeClassList(cfg.backdropClass || '')
        ].filter(Boolean).join(' ');
        backdrop.setAttribute('data-fc-builder-sheet-close', '1');
        sheet.appendChild(backdrop);

        var panel = document.createElement('section');
        panel.className = [
            'fc-builder-sheet-panel',
            sanitizeClassList(cfg.panelClass || '')
        ].filter(Boolean).join(' ');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        panel.setAttribute('aria-label', titleText);
        sheet.appendChild(panel);

        var head = document.createElement('header');
        head.className = [
            'fc-builder-sheet-head',
            sanitizeClassList(cfg.headClass || '')
        ].filter(Boolean).join(' ');
        panel.appendChild(head);

        var title = document.createElement('h3');
        title.className = [
            'fc-builder-sheet-title',
            sanitizeClassList(cfg.titleClass || '')
        ].filter(Boolean).join(' ');
        title.textContent = titleText;
        head.appendChild(title);

        var closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = [
            'btn',
            'btn-ghost',
            'btn-sm',
            sanitizeClassList(cfg.closeButtonClass || '')
        ].filter(Boolean).join(' ');
        closeButton.setAttribute('aria-label', closeLabel);
        closeButton.setAttribute('data-fc-builder-sheet-close', '1');
        closeButton.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
        head.appendChild(closeButton);

        var body = document.createElement('div');
        body.className = [
            'fc-builder-sheet-body',
            sanitizeClassList(cfg.bodyClass || '')
        ].filter(Boolean).join(' ');
        panel.appendChild(body);

        function setTitle(nextTitle) {
            var safeTitle = String(nextTitle || '').trim();
            title.textContent = safeTitle;
            panel.setAttribute('aria-label', safeTitle);
        }

        function open() {
            sheet.classList.add('is-open');
            sheet.setAttribute('aria-hidden', 'false');
        }

        function close() {
            sheet.classList.remove('is-open');
            sheet.setAttribute('aria-hidden', 'true');
        }

        function isOpen() {
            return sheet.classList.contains('is-open');
        }

        sheet.addEventListener('click', function (event) {
            var closeTrigger = event.target && event.target.closest
                ? event.target.closest('[data-fc-builder-sheet-close="1"]')
                : null;
            if (!closeTrigger) {
                return;
            }
            event.preventDefault();
            if (requestClose) {
                requestClose();
                return;
            }
            close();
        });

        return {
            element: sheet,
            backdrop: backdrop,
            panel: panel,
            head: head,
            title: title,
            body: body,
            closeButton: closeButton,
            setTitle: setTitle,
            open: open,
            close: close,
            isOpen: isOpen
        };
    }

    function createBuilderToggleSwitchControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var switchWrap = document.createElement('div');
        switchWrap.className = [
            'fc-builder-switch-control',
            sanitizeClassList(cfg.wrapperClass || '')
        ].filter(Boolean).join(' ');

        var text = document.createElement('span');
        text.className = [
            'fc-builder-switch-text',
            sanitizeClassList(cfg.textClass || '')
        ].filter(Boolean).join(' ');
        text.textContent = String(cfg.label || '');
        switchWrap.appendChild(text);

        var hitbox = document.createElement('label');
        hitbox.className = [
            'fc-builder-switch-hitbox',
            sanitizeClassList(cfg.hitboxClass || '')
        ].filter(Boolean).join(' ');
        switchWrap.appendChild(hitbox);

        var input = document.createElement('input');
        input.className = [
            'fc-builder-switch-input',
            sanitizeClassList(cfg.inputClass || '')
        ].filter(Boolean).join(' ');
        input.type = 'checkbox';
        input.checked = !!cfg.checked;
        hitbox.appendChild(input);

        var ui = document.createElement('span');
        ui.className = [
            'fc-builder-switch-ui',
            sanitizeClassList(cfg.uiClass || '')
        ].filter(Boolean).join(' ');
        ui.setAttribute('aria-hidden', 'true');
        hitbox.appendChild(ui);

        function syncSwitchState() {
            switchWrap.classList.toggle('is-checked', input.checked);
            switchWrap.classList.toggle('is-disabled', input.disabled);
            hitbox.classList.toggle('is-checked', input.checked);
            hitbox.classList.toggle('is-disabled', input.disabled);
            hitbox.setAttribute('role', 'switch');
            hitbox.setAttribute('tabindex', input.disabled ? '-1' : '0');
            hitbox.setAttribute('aria-checked', input.checked ? 'true' : 'false');
            hitbox.setAttribute('aria-disabled', input.disabled ? 'true' : 'false');
        }

        function emitToggle(nextChecked) {
            if (input.disabled) {
                return;
            }
            input.checked = !!nextChecked;
            syncSwitchState();
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        hitbox.addEventListener('click', function (event) {
            event.preventDefault();
            emitToggle(!input.checked);
        });

        hitbox.addEventListener('keydown', function (event) {
            if (event.key === ' ' || event.key === 'Enter') {
                event.preventDefault();
                emitToggle(!input.checked);
            }
        });

        input.addEventListener('change', syncSwitchState);
        syncSwitchState();

        return { element: switchWrap, input: input };
    }

    function createBuilderInspectorToolbar(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var toolbar = document.createElement('div');
        toolbar.className = [
            'fc-builder-inspector-toolbar',
            sanitizeClassList(cfg.toolbarClass || 'pb-inspector-toolbar')
        ].filter(Boolean).join(' ');

        var head = document.createElement('div');
        head.className = [
            'fc-builder-inspector-toolbar-head',
            sanitizeClassList(cfg.headClass || 'pb-inspector-toolbar-head')
        ].filter(Boolean).join(' ');
        toolbar.appendChild(head);

        var title = document.createElement('div');
        title.className = [
            'fc-builder-inspector-widget-title',
            sanitizeClassList(cfg.titleClass || 'pb-inspector-widget-title')
        ].filter(Boolean).join(' ');
        title.textContent = String(cfg.title || '').trim();
        head.appendChild(title);

        var actions = document.createElement('div');
        actions.className = [
            'fc-builder-inspector-mode-switch',
            sanitizeClassList(cfg.actionsClass || 'pb-inspector-mode-switch')
        ].filter(Boolean).join(' ');
        head.appendChild(actions);

        var actionButtons = Array.isArray(cfg.buttons) ? cfg.buttons : [];
        actionButtons.forEach(function (entry) {
            if (!entry || typeof entry !== 'object') {
                return;
            }
            var button = document.createElement('button');
            button.type = 'button';
            button.className = [
                sanitizeClassList(entry.className || 'btn btn-ghost btn-sm'),
                entry.active ? 'is-active' : ''
            ].filter(Boolean).join(' ');
            if (entry.title) {
                button.title = String(entry.title).trim();
                button.setAttribute('aria-label', String(entry.title).trim());
            }
            if (entry.icon) {
                button.innerHTML = '<i class="' + escapeAttr(String(entry.icon).trim()) + '" aria-hidden="true"></i>';
            } else if (entry.label) {
                button.textContent = String(entry.label).trim();
            }
            if (entry.disabled) {
                button.disabled = true;
            }
            if (typeof entry.onClick === 'function') {
                button.addEventListener('click', function () {
                    if (button.disabled) {
                        return;
                    }
                    entry.onClick();
                });
            }
            actions.appendChild(button);
        });

        if (cfg.titleRowElement instanceof HTMLElement) {
            toolbar.appendChild(cfg.titleRowElement);
        }

        return {
            element: toolbar,
            head: head,
            title: title,
            actions: actions,
            titleRow: cfg.titleRowElement instanceof HTMLElement ? cfg.titleRowElement : null
        };
    }

    function createBuilderInspectorTabbar(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var tabbar = document.createElement('div');
        tabbar.className = [
            'fc-builder-inspector-tabbar',
            sanitizeClassList(cfg.tabbarClass || 'pb-inspector-tabbar')
        ].filter(Boolean).join(' ');
        tabbar.setAttribute('role', 'tablist');

        var tabs = Array.isArray(cfg.tabs) ? cfg.tabs : [];
        tabs.forEach(function (tab) {
            if (!tab || typeof tab !== 'object') {
                return;
            }
            var button = document.createElement('button');
            button.type = 'button';
            button.className = [
                'fc-builder-inspector-tab',
                sanitizeClassList(cfg.tabClass || 'pb-inspector-tab'),
                tab.active ? 'is-active' : ''
            ].filter(Boolean).join(' ');
            button.setAttribute('role', 'tab');
            button.setAttribute('aria-selected', tab.active ? 'true' : 'false');
            button.textContent = String(tab.label || '').trim();
            if (typeof tab.onClick === 'function') {
                button.addEventListener('click', function () {
                    tab.onClick();
                });
            }
            tabbar.appendChild(button);
        });

        return tabbar;
    }

    function createBuilderInspectorGroup(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var groupWrap = document.createElement('section');
        groupWrap.className = [
            'fc-builder-inspector-group',
            sanitizeClassList(cfg.groupClass || 'pb-inspector-group')
        ].filter(Boolean).join(' ');
        groupWrap.dataset.group = String(cfg.groupKey || '').trim();

        var groupHead = document.createElement('div');
        groupHead.className = [
            'fc-builder-inspector-group-toggle',
            sanitizeClassList(cfg.headClass || 'pb-inspector-group-toggle')
        ].filter(Boolean).join(' ');
        groupWrap.appendChild(groupHead);

        var groupTitle = document.createElement('span');
        groupTitle.className = [
            'fc-builder-inspector-group-title',
            sanitizeClassList(cfg.titleClass || 'pb-inspector-group-title')
        ].filter(Boolean).join(' ');
        groupTitle.textContent = String(cfg.title || '').trim();
        groupHead.appendChild(groupTitle);

        var fieldsWrap = document.createElement('div');
        fieldsWrap.className = [
            'fc-builder-inspector-group-fields',
            sanitizeClassList(cfg.fieldsClass || 'pb-inspector-group-fields')
        ].filter(Boolean).join(' ');
        groupWrap.appendChild(fieldsWrap);

        return {
            groupWrap: groupWrap,
            fieldsWrap: fieldsWrap,
            head: groupHead,
            title: groupTitle
        };
    }

    function createBuilderFieldShell(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var wrap = document.createElement(cfg.tag || 'div');
        wrap.className = [
            'pb-field',
            sanitizeClassList(cfg.wrapperClass || '')
        ].filter(Boolean).join(' ');

        var fieldKey = String(cfg.fieldKey || '').trim();
        if (fieldKey !== '') {
            wrap.dataset.fieldKey = fieldKey;
        }
        if (cfg.wide) {
            wrap.classList.add('is-wide');
        }
        if (cfg.disabled) {
            wrap.classList.add('is-disabled');
        }
        if (cfg.switchField) {
            wrap.classList.add('pb-field-switch');
        }

        var labelEl = null;
        var labelText = String(cfg.label || '').trim();
        if (!cfg.hideLabel && labelText !== '') {
            labelEl = document.createElement('label');
            labelEl.textContent = labelText;
            wrap.appendChild(labelEl);
        }

        return {
            element: wrap,
            label: labelEl
        };
    }

    function createBuilderFieldHelp(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var help = document.createElement(cfg.tag || 'p');
        help.className = [
            'pb-field-help',
            sanitizeClassList(cfg.className || '')
        ].filter(Boolean).join(' ');
        help.textContent = String(cfg.text || '').trim();
        return help;
    }

    function updateBuilderIconPreview(previewNode, rawValue, options) {
        var opts = options && typeof options === 'object' ? options : {};
        if (!previewNode) {
            return;
        }
        var value = String(rawValue || '').trim();
        var iconClass = value || String(opts.emptyIconClass || 'fas fa-icons');
        var emptyClassName = String(opts.emptyClassName || 'is-empty').trim() || 'is-empty';
        previewNode.classList.toggle(emptyClassName, !value);
        previewNode.innerHTML = '<i class="' + escapeAttr(iconClass) + '"></i>';

        if (window.FontAwesome && window.FontAwesome.dom && typeof window.FontAwesome.dom.i2svg === 'function') {
            window.FontAwesome.dom.i2svg({ node: previewNode });
        }
    }

    function renderBuilderMediaPreviewContent(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var mediaOptions = cfg.mediaOptions && typeof cfg.mediaOptions === 'object' ? cfg.mediaOptions : {};
        var resolveSrc = typeof cfg.resolveSrc === 'function'
            ? cfg.resolveSrc
            : function (value) { return String(value || '').trim(); };
        var noMediaLabel = String(cfg.noMediaLabel || 'No file selected').trim() || 'No file selected';
        var rawValue = String(cfg.rawValue || '').trim();
        var src = resolveSrc(rawValue);
        if (!src) {
            return '<div class="pb-field-media-preview-empty">' + escapeAttr(noMediaLabel) + '</div>';
        }

        var isImageLike = mediaOptions.mode === 'images'
            || mediaOptions.preview === 'image'
            || /\.(png|jpe?g|gif|svg|webp|avif|bmp|ico)(\?.*)?$/i.test(src);
        if (isImageLike) {
            return '<div class="pb-field-media-preview-frame"><img class="pb-field-media-preview-image" src="' + escapeAttr(src) + '" alt=""></div>';
        }

        var fileName = src.split('/').filter(function (part) { return part !== ''; }).pop() || src;
        return '<div class="pb-field-media-preview-file"><i class="fas fa-file-alt" aria-hidden="true"></i><span>' + escapeAttr(fileName) + '</span></div>';
    }

    function createBuilderMediaPreview(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var preview = document.createElement('div');
        preview.className = [
            'pb-field-media-preview',
            sanitizeClassList(cfg.previewClass || '')
        ].filter(Boolean).join(' ');

        function update(nextValue) {
            preview.innerHTML = renderBuilderMediaPreviewContent({
                mediaOptions: cfg.mediaOptions,
                rawValue: nextValue,
                resolveSrc: cfg.resolveSrc,
                noMediaLabel: cfg.noMediaLabel
            });
        }

        update(cfg.rawValue);
        return { element: preview, update: update };
    }

    function createBuilderMediaPickerGridRow(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var resolveSrc = typeof cfg.resolveSrc === 'function'
            ? cfg.resolveSrc
            : function (value) { return String(value || '').trim(); };
        var row = document.createElement('div');
        row.className = sanitizeClassList(cfg.rowClass || '') || 'pb-feature-grid-media-row';

        var labelInput = document.createElement('input');
        labelInput.className = sanitizeClassList(cfg.labelInputClass || '') || 'form-input';
        labelInput.type = 'text';
        labelInput.value = String(cfg.labelValue || '');
        labelInput.readOnly = true;
        row.appendChild(labelInput);

        var valueInput = document.createElement('input');
        valueInput.className = sanitizeClassList(cfg.valueInputClass || '') || 'form-input';
        valueInput.type = 'text';
        valueInput.value = String(cfg.value || '');
        valueInput.placeholder = String(cfg.placeholder || '');
        row.appendChild(valueInput);

        var previewWrap = document.createElement('div');
        previewWrap.className = sanitizeClassList(cfg.previewWrapClass || '') || 'pb-feature-grid-media-preview-cell';
        var preview = document.createElement('img');
        preview.className = sanitizeClassList(cfg.previewClass || '') || 'pb-feature-grid-media-preview';
        preview.alt = String(cfg.previewAlt || '');
        previewWrap.appendChild(preview);
        row.appendChild(previewWrap);

        var pickButton = document.createElement('button');
        pickButton.type = 'button';
        pickButton.className = String(cfg.pickButtonClass || 'btn btn-secondary btn-sm').trim() || 'btn btn-secondary btn-sm';
        pickButton.innerHTML = String(cfg.pickButtonHtml || '<i class="fas fa-image" aria-hidden="true"></i>');
        setSharedTitle([pickButton], String(cfg.pickButtonTitle || ''));
        row.appendChild(pickButton);

        var clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = String(cfg.clearButtonClass || 'btn btn-ghost btn-sm').trim() || 'btn btn-ghost btn-sm';
        clearButton.innerHTML = String(cfg.clearButtonHtml || '<i class="fas fa-trash" aria-hidden="true"></i>');
        setSharedTitle([clearButton], String(cfg.clearButtonTitle || ''));
        row.appendChild(clearButton);

        function setValue(nextValue) {
            var safeValue = String(nextValue || '').trim();
            var resolvedValue = resolveSrc(safeValue);
            valueInput.value = safeValue;
            if (resolvedValue !== '') {
                preview.src = resolvedValue;
            } else {
                preview.removeAttribute('src');
            }
            preview.hidden = resolvedValue === '';
        }

        setValue(cfg.value);

        return {
            element: row,
            labelInput: labelInput,
            valueInput: valueInput,
            previewWrap: previewWrap,
            preview: preview,
            pickButton: pickButton,
            clearButton: clearButton,
            setValue: setValue
        };
    }

    function createBuilderIconPickerGridRow(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var row = document.createElement('div');
        row.className = sanitizeClassList(cfg.rowClass || '') || 'pb-feature-grid-media-row';

        if (cfg.leadElement && typeof cfg.leadElement.nodeType === 'number') {
            row.appendChild(cfg.leadElement);
        }

        var valueInput = document.createElement('input');
        valueInput.className = sanitizeClassList(cfg.valueInputClass || '') || 'form-input';
        valueInput.type = 'text';
        valueInput.value = String(cfg.value || '');
        if (cfg.inputAriaLabel) {
            valueInput.setAttribute('aria-label', String(cfg.inputAriaLabel));
        }
        row.appendChild(valueInput);

        var previewWrap = document.createElement('div');
        previewWrap.className = sanitizeClassList(cfg.previewWrapClass || '') || 'pb-feature-grid-media-preview-cell';
        var preview = document.createElement('span');
        preview.className = sanitizeClassList(cfg.previewClass || '') || 'pb-icon-preview';
        previewWrap.appendChild(preview);
        row.appendChild(previewWrap);

        var pickButton = document.createElement('button');
        pickButton.type = 'button';
        pickButton.className = String(cfg.pickButtonClass || 'btn btn-secondary btn-sm').trim() || 'btn btn-secondary btn-sm';
        pickButton.innerHTML = String(cfg.pickButtonHtml || '<i class="fas fa-icons" aria-hidden="true"></i>');
        setSharedTitle([pickButton], String(cfg.pickButtonTitle || ''));
        row.appendChild(pickButton);

        var clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = String(cfg.clearButtonClass || 'btn btn-ghost btn-sm').trim() || 'btn btn-ghost btn-sm';
        clearButton.innerHTML = String(cfg.clearButtonHtml || '<i class="fas fa-trash" aria-hidden="true"></i>');
        setSharedTitle([clearButton], String(cfg.clearButtonTitle || ''));
        row.appendChild(clearButton);

        function setValue(nextValue) {
            var safeValue = String(nextValue || '').trim();
            valueInput.value = safeValue;
            updateBuilderIconPreview(preview, safeValue, {});
        }

        setValue(cfg.value);

        return {
            element: row,
            valueInput: valueInput,
            previewWrap: previewWrap,
            preview: preview,
            pickButton: pickButton,
            clearButton: clearButton,
            setValue: setValue
        };
    }

    function createBuilderIconPickerRow(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var row = document.createElement('div');
        row.className = [
            'pb-field-row',
            'pb-icon-row',
            sanitizeClassList(cfg.rowClass || '')
        ].filter(Boolean).join(' ');

        var preview = document.createElement('span');
        preview.className = [
            'pb-icon-preview',
            sanitizeClassList(cfg.previewClass || '')
        ].filter(Boolean).join(' ');
        row.appendChild(preview);

        var pickButton = document.createElement('button');
        pickButton.type = 'button';
        pickButton.className = String(cfg.pickButtonClass || 'btn btn-secondary btn-sm').trim() || 'btn btn-secondary btn-sm';
        pickButton.innerHTML = String(cfg.pickButtonHtml || '<i class="fas fa-icons"></i>');
        row.appendChild(pickButton);

        var clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = String(cfg.clearButtonClass || 'btn btn-ghost btn-sm').trim() || 'btn btn-ghost btn-sm';
        clearButton.innerHTML = String(cfg.clearButtonHtml || '<i class="fas fa-times"></i>');
        row.appendChild(clearButton);

        function setValue(nextValue) {
            updateBuilderIconPreview(preview, nextValue, {
                emptyIconClass: cfg.emptyIconClass,
                emptyClassName: cfg.emptyClassName
            });
        }

        function setDisabled(nextDisabled) {
            var disabled = !!nextDisabled;
            pickButton.disabled = disabled;
            clearButton.disabled = disabled;
        }

        setValue(cfg.value);
        setDisabled(cfg.disabled);

        return {
            element: row,
            preview: preview,
            pickButton: pickButton,
            clearButton: clearButton,
            setValue: setValue,
            setDisabled: setDisabled
        };
    }

    function createBuilderMediaFieldControls(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var previewEnabled = !!cfg.previewEnabled;
        var currentValue = String(cfg.value || '');
        var disabled = !!cfg.disabled;

        var row = document.createElement('div');
        row.className = [
            'pb-field-row',
            'pb-field-row-media',
            sanitizeClassList(cfg.rowClass || '')
        ].filter(Boolean).join(' ');

        var pickButton = document.createElement('button');
        pickButton.type = 'button';
        pickButton.className = String(cfg.pickButtonClass || 'btn btn-secondary btn-sm').trim() || 'btn btn-secondary btn-sm';
        pickButton.textContent = String(cfg.pickButtonText || 'Choose file');
        row.appendChild(pickButton);

        var clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = String(cfg.clearButtonClass || 'btn btn-ghost btn-sm').trim() || 'btn btn-ghost btn-sm';
        clearButton.innerHTML = String(cfg.clearButtonHtml || '<i class="fas fa-trash"></i> Remove media');
        row.appendChild(clearButton);

        var element = row;
        var controls = null;
        var preview = null;

        if (previewEnabled) {
            element = document.createElement('div');
            element.className = [
                'pb-field-media-layout',
                sanitizeClassList(cfg.layoutClass || '')
            ].filter(Boolean).join(' ');

            controls = document.createElement('div');
            controls.className = [
                'pb-field-media-controls',
                sanitizeClassList(cfg.controlsClass || '')
            ].filter(Boolean).join(' ');
            controls.appendChild(row);
            element.appendChild(controls);

            preview = createBuilderMediaPreview({
                mediaOptions: cfg.mediaOptions,
                rawValue: currentValue,
                resolveSrc: cfg.resolveSrc,
                noMediaLabel: cfg.noMediaLabel,
                previewClass: cfg.previewClass
            });
            element.appendChild(preview.element);
        }

        function updateRemoveState() {
            clearButton.disabled = disabled || String(currentValue || '').trim() === '';
        }

        function setDisabled(nextDisabled) {
            disabled = !!nextDisabled;
            pickButton.disabled = disabled;
            updateRemoveState();
        }

        function setValue(nextValue) {
            currentValue = String(nextValue || '');
            if (preview && typeof preview.update === 'function') {
                preview.update(currentValue);
            }
            updateRemoveState();
        }

        setDisabled(disabled);
        setValue(currentValue);

        return {
            element: element,
            controls: controls,
            row: row,
            pickButton: pickButton,
            clearButton: clearButton,
            preview: preview,
            setValue: setValue,
            setDisabled: setDisabled,
            updateRemoveState: updateRemoveState
        };
    }

    function readNullableBoolStorage(key) {
        try {
            var raw = window.localStorage.getItem(String(key || ''));
            if (raw === null) return null;
            if (raw === '1' || raw === 'true') return true;
            if (raw === '0' || raw === 'false') return false;
            return null;
        } catch (error) {
            return null;
        }
    }

    function createBuilderShellController(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var root = cfg.root || null;
        var overlay = cfg.overlay || null;
        var topHeader = cfg.topHeader || null;
        var topbar = cfg.topbar || null;
        var adminSidebar = cfg.adminSidebar || null;
        var adminSidebarOverlay = cfg.adminSidebarOverlay || null;
        var bodyClass = String(cfg.bodyClass || '').trim();
        var offsetCssVar = String(cfg.offsetCssVar || '').trim() || '--fc-builder-drawer-top';
        var sideConfigs = cfg.sides && typeof cfg.sides === 'object' ? cfg.sides : {};
        var offsetTargets = Array.isArray(cfg.offsetTargets) ? cfg.offsetTargets.filter(Boolean) : [];
        if (!offsetTargets.length && root) {
            offsetTargets.push(root);
        }

        var adminSidebarState = null;

        function addBodyClass() {
            if (bodyClass) {
                document.body.classList.add(bodyClass);
            }
        }

        function removeBodyClass() {
            if (bodyClass) {
                document.body.classList.remove(bodyClass);
            }
        }

        function updateToggle(sideConfig, isOpen) {
            if (!sideConfig) {
                return;
            }
            if (typeof sideConfig.updateToggle === 'function') {
                sideConfig.updateToggle(isOpen);
                return;
            }
            var toggleButton = sideConfig.toggleButton || null;
            if (!toggleButton) {
                return;
            }
            var icon = toggleButton.querySelector && toggleButton.querySelector('i');
            if (!icon) {
                return;
            }
            var openIconClass = String(sideConfig.openIconClass || '').trim();
            var closedIconClass = String(sideConfig.closedIconClass || '').trim();
            if (!openIconClass || !closedIconClass) {
                return;
            }
            ['fa-chevron-left', 'fa-chevron-right', 'fa-chevron-up', 'fa-chevron-down'].forEach(function (className) {
                icon.classList.remove(className);
            });
            icon.classList.add(isOpen ? openIconClass : closedIconClass);
        }

        function getSideConfig(side) {
            return sideConfigs[String(side || '').trim()] || null;
        }

        function collapseAdminSidebar() {
            addBodyClass();
            if (!adminSidebar || adminSidebarState) {
                return;
            }

            adminSidebarState = {
                wasCollapsed: adminSidebar.classList.contains('collapsed'),
                wasOpen: adminSidebar.classList.contains('open'),
                overlayWasActive: adminSidebarOverlay ? adminSidebarOverlay.classList.contains('active') : false,
                bodyOverflow: document.body.style.overflow || ''
            };

            adminSidebar.classList.add('collapsed');
            adminSidebar.classList.remove('open');

            if (adminSidebarOverlay) {
                adminSidebarOverlay.classList.remove('active');
            }

            if (document.body.style.overflow === 'hidden') {
                document.body.style.overflow = '';
            }
        }

        function restoreAdminSidebar() {
            removeBodyClass();
            if (!adminSidebar || !adminSidebarState) {
                return;
            }

            var previousState = adminSidebarState;
            adminSidebarState = null;

            adminSidebar.classList.toggle('collapsed', !!previousState.wasCollapsed);
            adminSidebar.classList.toggle('open', !!previousState.wasOpen);

            if (adminSidebarOverlay) {
                adminSidebarOverlay.classList.toggle('active', !!previousState.overlayWasActive);
            }

            document.body.style.overflow = previousState.bodyOverflow || '';
        }

        function computeOffset() {
            var headerHeight = topHeader && typeof topHeader.getBoundingClientRect === 'function'
                ? topHeader.getBoundingClientRect().height
                : 0;
            var topbarHeight = topbar && typeof topbar.getBoundingClientRect === 'function'
                ? topbar.getBoundingClientRect().height
                : 0;
            return Math.max(0, Math.round(Number(headerHeight || 0) + Number(topbarHeight || 0)));
        }

        function updateOffsets() {
            var total = computeOffset();
            offsetTargets.forEach(function (target) {
                if (!target || !target.style || typeof target.style.setProperty !== 'function') {
                    return;
                }
                target.style.setProperty(offsetCssVar, total + 'px');
            });
            if (typeof cfg.onOffsetsChange === 'function') {
                cfg.onOffsetsChange(total);
            }
            return total;
        }

        function readState(side) {
            var sideConfig = getSideConfig(side);
            if (!sideConfig) {
                return { open: false };
            }

            var open = readNullableBoolStorage(sideConfig.storageKey || '');
            if (open === null && sideConfig.legacyCollapsedKey) {
                var legacyCollapsed = readNullableBoolStorage(sideConfig.legacyCollapsedKey);
                if (legacyCollapsed !== null) {
                    open = !legacyCollapsed;
                }
            }

            return {
                open: open === null ? !!sideConfig.defaultOpen : open
            };
        }

        function isOpen(side) {
            var sideConfig = getSideConfig(side);
            if (!sideConfig) {
                return false;
            }
            if (typeof sideConfig.isOpen === 'function') {
                return !!sideConfig.isOpen();
            }
            if (root && sideConfig.rootOpenClass) {
                return root.classList.contains(sideConfig.rootOpenClass);
            }
            if (sideConfig.sidebar) {
                return sideConfig.sidebar.classList.contains(sideConfig.sidebarOpenClass || 'is-open');
            }
            return false;
        }

        function updateOverlayState() {
            var isActive = typeof cfg.isOverlayActive === 'function' ? !!cfg.isOverlayActive() : false;

            if (typeof cfg.onOverlayUpdate === 'function') {
                cfg.onOverlayUpdate(isActive);
                return;
            }

            if (root && cfg.overlayRootClass) {
                root.classList.toggle(String(cfg.overlayRootClass), isActive);
            }
            if (overlay) {
                overlay.classList.toggle(String(cfg.overlayActiveClass || 'is-active'), isActive);
                if (typeof overlay.setAttribute === 'function') {
                    overlay.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                }
            }
        }

        function applyState(side, nextState, options) {
            var sideConfig = getSideConfig(side);
            if (!sideConfig) {
                return;
            }

            var opts = options && typeof options === 'object' ? options : {};
            var shouldPersist = opts.persist === true || opts.persist === false ? opts.persist : false;
            var nextOpen = !!(nextState && nextState.open);

            if (root && sideConfig.rootOpenClass) {
                root.classList.toggle(sideConfig.rootOpenClass, nextOpen);
            }

            if (sideConfig.sidebar) {
                sideConfig.sidebar.classList.toggle(sideConfig.sidebarOpenClass || 'is-open', nextOpen);
                if (sideConfig.manageAria !== false && typeof sideConfig.sidebar.setAttribute === 'function') {
                    sideConfig.sidebar.setAttribute('aria-hidden', nextOpen ? 'false' : 'true');
                }
            }

            updateToggle(sideConfig, nextOpen);

            if (typeof sideConfig.onApply === 'function') {
                sideConfig.onApply(nextOpen, opts);
            }

            if (shouldPersist && sideConfig.storageKey) {
                try {
                    window.localStorage.setItem(String(sideConfig.storageKey), nextOpen ? '1' : '0');
                } catch (error) {
                    // ignore persistence failures
                }
            }

            updateOverlayState();

            if (typeof cfg.onStateChange === 'function' && opts.update !== false) {
                cfg.onStateChange(String(side), nextOpen, opts);
            }
        }

        function setState(side, patch, options) {
            var current = { open: isOpen(side) };
            var next = Object.assign({}, current, patch || {});
            applyState(side, next, options);
        }

        function toggle(side, options) {
            setState(side, { open: !isOpen(side) }, options);
        }

        function closeAll(options) {
            Object.keys(sideConfigs).forEach(function (side) {
                if (!isOpen(side)) {
                    return;
                }
                applyState(side, { open: false }, options);
            });
        }

        function restoreDrawerState(options) {
            var opts = options && typeof options === 'object' ? options : {};
            Object.keys(sideConfigs).forEach(function (side) {
                applyState(side, readState(side), {
                    persist: false,
                    update: opts.update === true
                });
            });
            updateOverlayState();
        }

        function init() {
            collapseAdminSidebar();
            updateOffsets();
            restoreDrawerState({ update: false });
        }

        return {
            init: init,
            collapseAdminSidebar: collapseAdminSidebar,
            restoreAdminSidebar: restoreAdminSidebar,
            updateOffsets: updateOffsets,
            readState: readState,
            isOpen: isOpen,
            applyState: applyState,
            setState: setState,
            toggle: toggle,
            closeAll: closeAll,
            restoreDrawerState: restoreDrawerState,
            updateOverlayState: updateOverlayState
        };
    }

    window.FlatCMSUIPrimitives = Object.assign({}, window.FlatCMSUIPrimitives || {}, {
        createCompactSelectControl: createCompactSelectControl,
        createCompactColorControl: createCompactColorControl,
        createBuilderChoiceControl: createBuilderChoiceControl,
        createBuilderTargetChoiceControl: createBuilderTargetChoiceControl,
        createBuilderNavigationEditorScaffold: createBuilderNavigationEditorScaffold,
        createBuilderNavigationInputCell: createBuilderNavigationInputCell,
        createBuilderNavigationSwitchCell: createBuilderNavigationSwitchCell,
        createBuilderInputControl: createBuilderInputControl,
        createBuilderTextareaControl: createBuilderTextareaControl,
        createBuilderInspectorToolbarTitleRow: createBuilderInspectorToolbarTitleRow,
        createBuilderSpacingPanel: createBuilderSpacingPanel,
        createBuilderLinksQuickAddScaffold: createBuilderLinksQuickAddScaffold,
        createBuilderLinksQuickAddEmptyState: createBuilderLinksQuickAddEmptyState,
        createBuilderLinksQuickAddExistingItem: createBuilderLinksQuickAddExistingItem,
        createBuilderLinkSourceLibraryItems: createBuilderLinkSourceLibraryItems,
        filterBuilderLinkSourceLibraryItems: filterBuilderLinkSourceLibraryItems,
        appendBuilderLinkSourceToRaw: appendBuilderLinkSourceToRaw,
        formatBuilderLinkLine: formatBuilderLinkLine,
        serializeBuilderLinks: serializeBuilderLinks,
        renderBuilderLinksQuickAddOptions: renderBuilderLinksQuickAddOptions,
        appendBuilderLinksQuickAddSelection: appendBuilderLinksQuickAddSelection,
        appendBuilderLinksQuickAddExternal: appendBuilderLinksQuickAddExternal,
        createBuilderModalShell: createBuilderModalShell,
        createBuilderSyncPickerModal: createBuilderSyncPickerModal,
        createBuilderSelectControl: createBuilderSelectControl,
        createBuilderResponsiveEditor: createBuilderResponsiveEditor,
        createBuilderActionsRow: createBuilderActionsRow,
        createBuilderColorFieldRow: createBuilderColorFieldRow,
        createBuilderRepeaterCardScaffold: createBuilderRepeaterCardScaffold,
        createBuilderRepeaterCard: createBuilderRepeaterCard,
        createBuilderRepeaterAddButton: createBuilderRepeaterAddButton,
        createBuilderCardActionsRow: createBuilderCardActionsRow,
        createBuilderAdvancedPanel: createBuilderAdvancedPanel,
        createBuilderAdvancedCard: createBuilderAdvancedCard,
        createBuilderTextStyleControl: createBuilderTextStyleControl,
        updateBuilderTextStylePreview: updateBuilderTextStylePreview,
        createBuilderAlignControl: createBuilderAlignControl,
        createBuilderInspectorSheet: createBuilderInspectorSheet,
        createBuilderInspectorToolbar: createBuilderInspectorToolbar,
        createBuilderInspectorTabbar: createBuilderInspectorTabbar,
        createBuilderInspectorGroup: createBuilderInspectorGroup,
        createBuilderFieldShell: createBuilderFieldShell,
        createBuilderFieldHelp: createBuilderFieldHelp,
        createBuilderToggleSwitchControl: createBuilderToggleSwitchControl,
        createBuilderShellController: createBuilderShellController,
        updateBuilderIconPreview: updateBuilderIconPreview,
        createBuilderMediaPickerGridRow: createBuilderMediaPickerGridRow,
        createBuilderIconPickerGridRow: createBuilderIconPickerGridRow,
        createBuilderIconPickerRow: createBuilderIconPickerRow,
        createBuilderMediaPreview: createBuilderMediaPreview,
        createBuilderMediaFieldControls: createBuilderMediaFieldControls
    });
})(window, document);
