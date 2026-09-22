/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: public/assets/js/admin/flatcms-ui-primitives.js
 * Version: 2.0.0-dev
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
            sanitizeClassList(cfg.wrapperClass || '')
        ].filter(Boolean).join(' ');

        var button = document.createElement(cfg.buttonTag || 'span');
        button.className = [
            'fc-ui-toolbar-btn',
            sanitizeClassList(cfg.buttonClass || '')
        ].filter(Boolean).join(' ');
        button.setAttribute('role', cfg.role || 'button');
        button.setAttribute('tabindex', '0');
        wrapper.appendChild(button);

        var select = document.createElement('select');
        select.className = [
            'fc-ui-compact-select',
            sanitizeClassList(cfg.selectClass || '')
        ].filter(Boolean).join(' ');
        wrapper.appendChild(select);

        var iconClass = sanitizeClassList(cfg.iconClass || 'fas fa-sliders-h') || 'fas fa-sliders-h';
        var labelText = String(cfg.label || '').trim();
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
            sanitizeClassList(cfg.wrapperClass || '')
        ].filter(Boolean).join(' ');

        var pickerWrap = document.createElement('div');
        pickerWrap.className = [
            'fc-ui-color-picker',
            sanitizeClassList(cfg.pickerWrapClass || '')
        ].filter(Boolean).join(' ');
        wrapper.appendChild(pickerWrap);

        var swatch = document.createElement('button');
        swatch.type = 'button';
        swatch.className = [
            'fc-ui-toolbar-btn',
            'fc-ui-color-swatch',
            sanitizeClassList(cfg.swatchClass || '')
        ].filter(Boolean).join(' ');
        pickerWrap.appendChild(swatch);

        var picker = document.createElement('input');
        picker.type = 'color';
        picker.className = [
            'fc-ui-compact-select',
            'fc-ui-native-color',
            sanitizeClassList(cfg.inputClass || '')
        ].filter(Boolean).join(' ');
        pickerWrap.appendChild(picker);

        var clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = [
            'fc-ui-toolbar-btn',
            sanitizeClassList(cfg.clearButtonClass || '')
        ].filter(Boolean).join(' ');
        clearBtn.innerHTML = '<i class="fas fa-eraser" aria-hidden="true"></i>';
        wrapper.appendChild(clearBtn);

        var labelText = String(cfg.label || '').trim();
        var emptyLabel = String(cfg.emptyLabel || '').trim();
        var clearLabel = String(cfg.clearLabel || '').trim();
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
            swatch.style.setProperty('--fc-ui-swatch-color', safe || 'transparent');
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

    function normalizeIconSearchText(value) {
        return String(value == null ? '' : value)
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/fa-/g, ' ')
            .replace(/[^a-z0-9]+/g, ' ')
            .trim();
    }

    function iconName(iconClass) {
        var structural = [
            'fa', 'fas', 'far', 'fab', 'fa-classic', 'fa-sharp', 'fa-solid',
            'fa-regular', 'fa-light', 'fa-thin', 'fa-duotone', 'fa-brands', 'fa-fw'
        ];
        var token = String(iconClass || '').split(/\s+/).find(function (className) {
            return className.indexOf('fa-') === 0 && structural.indexOf(className) === -1;
        });
        return token ? token.slice(3) : '';
    }

    function fuzzyIconScore(haystack, needle) {
        if (!haystack || !needle) {
            return 0;
        }
        var index = 0;
        var matched = 0;
        for (var cursor = 0; cursor < haystack.length && index < needle.length; cursor += 1) {
            if (haystack[cursor] === needle[index]) {
                matched += 1;
                index += 1;
            }
        }
        return index === needle.length ? matched : 0;
    }

    function createIconBrowser(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var overlay = cfg.overlay || null;
        var grid = cfg.grid || null;
        var searchInput = cfg.searchInput || null;
        if (!overlay || !grid || !searchInput) {
            throw new TypeError('icon_browser_invalid_elements');
        }

        var endpoint = String(cfg.endpoint || '').trim();
        var aliases = cfg.aliases && typeof cfg.aliases === 'object' ? cfg.aliases : {};
        var labels = cfg.labels && typeof cfg.labels === 'object' ? cfg.labels : {};
        var maxResults = Math.max(1, Math.min(1000, Number(cfg.maxResults || 300)));
        var onSelect = typeof cfg.onSelect === 'function' ? cfg.onSelect : null;
        var icons = [];
        var loaded = false;
        var loading = null;
        var searchTimer = null;
        var previousFocus = null;

        function showState(label) {
            var state = document.createElement('div');
            state.className = 'fc-ui-icon-picker__state';
            state.textContent = String(label || '');
            grid.replaceChildren(state);
        }

        function searchTerms(iconClass) {
            var baseTerms = normalizeIconSearchText(iconClass).split(/\s+/).filter(Boolean);
            var expanded = new Set(baseTerms);
            baseTerms.forEach(function (term) {
                var values = aliases[term];
                if (!Array.isArray(values)) {
                    return;
                }
                values.forEach(function (alias) {
                    var normalized = normalizeIconSearchText(alias);
                    if (normalized !== '') {
                        expanded.add(normalized);
                    }
                });
            });
            return Array.from(expanded);
        }

        function score(iconClass, query) {
            var normalizedQuery = normalizeIconSearchText(query);
            if (normalizedQuery === '') {
                return 1;
            }
            var queryTerms = normalizedQuery.split(/\s+/).filter(Boolean);
            var terms = searchTerms(iconClass);
            var total = 0;
            for (var queryIndex = 0; queryIndex < queryTerms.length; queryIndex += 1) {
                var queryTerm = queryTerms[queryIndex];
                var best = 0;
                terms.forEach(function (term) {
                    if (term === queryTerm) {
                        best = Math.max(best, 120);
                    } else if (term.indexOf(queryTerm) === 0) {
                        best = Math.max(best, 90);
                    } else if (term.indexOf(queryTerm) !== -1) {
                        best = Math.max(best, 70);
                    } else {
                        var fuzzy = fuzzyIconScore(term, queryTerm);
                        if (fuzzy > 0) {
                            best = Math.max(best, 40 + fuzzy);
                        }
                    }
                });
                if (best === 0) {
                    return 0;
                }
                total += best;
            }
            return total;
        }

        function render(filter) {
            var query = String(filter || '').trim();
            var filtered = query === ''
                ? icons.slice(0, maxResults)
                : icons.map(function (iconClass) {
                    return { iconClass: iconClass, score: score(iconClass, query) };
                }).filter(function (entry) {
                    return entry.score > 0;
                }).sort(function (left, right) {
                    return right.score - left.score || left.iconClass.localeCompare(right.iconClass);
                }).slice(0, maxResults).map(function (entry) {
                    return entry.iconClass;
                });

            if (filtered.length === 0) {
                showState(labels.empty);
                return;
            }

            var fragment = document.createDocumentFragment();
            filtered.forEach(function (iconClass) {
                var card = document.createElement('button');
                var name = iconName(iconClass);
                var icon = document.createElement('i');
                var label = document.createElement('span');
                card.type = 'button';
                card.className = 'fc-ui-icon-picker__card';
                card.setAttribute('aria-label', name);
                icon.className = sanitizeClassList(iconClass);
                icon.setAttribute('aria-hidden', 'true');
                label.textContent = name;
                card.append(icon, label);
                card.addEventListener('click', function () {
                    if (onSelect) {
                        onSelect(iconClass);
                    }
                    if (cfg.closeOnSelect !== false) {
                        close();
                    }
                });
                fragment.appendChild(card);
            });
            grid.replaceChildren(fragment);
        }

        function setIcons(nextIcons) {
            var safePattern = /^(?:fa-classic )?fa-(?:solid|regular|brands) fa-[a-z0-9-]+(?: fa-fw)?$/;
            icons = Array.from(new Set((Array.isArray(nextIcons) ? nextIcons : []).filter(function (entry) {
                return typeof entry === 'string' && safePattern.test(entry);
            })));
            loaded = true;
            render(searchInput.value);
        }

        function load() {
            if (loaded) {
                render(searchInput.value);
                return Promise.resolve(icons.slice());
            }
            if (loading) {
                return loading;
            }
            if (endpoint === '' || typeof window.fetch !== 'function') {
                showState(labels.error);
                return Promise.resolve([]);
            }

            showState(labels.loading);
            loading = window.fetch(endpoint, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('icon_browser_request_failed');
                }
                return response.json();
            }).then(function (payload) {
                setIcons(payload);
                return icons.slice();
            }).catch(function () {
                showState(labels.error);
                return [];
            }).finally(function () {
                loading = null;
            });
            return loading;
        }

        function open(trigger) {
            previousFocus = trigger || document.activeElement || null;
            overlay.hidden = false;
            overlay.setAttribute('aria-hidden', 'false');
            load();
            if (typeof searchInput.focus === 'function') {
                searchInput.focus({ preventScroll: true });
            }
        }

        function close() {
            overlay.hidden = true;
            overlay.setAttribute('aria-hidden', 'true');
            if (previousFocus && typeof previousFocus.focus === 'function') {
                previousFocus.focus({ preventScroll: true });
            }
            previousFocus = null;
        }

        function onSearchInput() {
            if (searchTimer) {
                window.clearTimeout(searchTimer);
            }
            searchTimer = window.setTimeout(function () {
                render(searchInput.value);
            }, 150);
        }

        function onOverlayClick(event) {
            if (event.target === overlay) {
                close();
            }
        }

        function onKeydown(event) {
            if (event.key === 'Escape' && !overlay.hidden) {
                close();
            }
        }

        searchInput.addEventListener('input', onSearchInput);
        overlay.addEventListener('click', onOverlayClick);
        Array.from(overlay.querySelectorAll('[data-fc-icon-picker-close]')).forEach(function (button) {
            button.addEventListener('click', close);
        });
        document.addEventListener('keydown', onKeydown);

        if (Array.isArray(cfg.icons)) {
            setIcons(cfg.icons);
        }

        return {
            open: open,
            close: close,
            load: load,
            render: render,
            setIcons: setIcons,
            isOpen: function () { return !overlay.hidden; }
        };
    }

    function createInputControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var input = document.createElement('input');
        var allowedTypes = [
            'text', 'number', 'url', 'email', 'search', 'tel', 'password',
            'date', 'time', 'datetime-local', 'color', 'hidden'
        ];
        var type = String(cfg.type || 'text').trim().toLowerCase();
        input.type = allowedTypes.indexOf(type) !== -1 ? type : 'text';
        input.className = sanitizeClassList(cfg.className || 'form-input') || 'form-input';
        input.value = String(cfg.value == null ? '' : cfg.value);

        ['min', 'max', 'step', 'name', 'id', 'placeholder'].forEach(function (attribute) {
            if (cfg[attribute] !== undefined && cfg[attribute] !== null) {
                input.setAttribute(attribute, String(cfg[attribute]));
            }
        });
        if (cfg.ariaLabel) {
            input.setAttribute('aria-label', String(cfg.ariaLabel));
        }
        input.disabled = cfg.disabled === true;
        input.required = cfg.required === true;
        return input;
    }

    function createSelectControl(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var select = document.createElement('select');
        var labels = cfg.optionLabels && typeof cfg.optionLabels === 'object' ? cfg.optionLabels : {};
        select.className = sanitizeClassList(cfg.className || 'form-input') || 'form-input';
        if (cfg.name) {
            select.name = String(cfg.name);
        }
        if (cfg.id) {
            select.id = String(cfg.id);
        }
        if (cfg.ariaLabel) {
            select.setAttribute('aria-label', String(cfg.ariaLabel));
        }

        (Array.isArray(cfg.options) ? cfg.options : []).forEach(function (entry) {
            var descriptor = entry && typeof entry === 'object'
                ? entry
                : { value: entry, label: labels[String(entry)] };
            var value = String(descriptor.value == null ? '' : descriptor.value);
            var option = document.createElement('option');
            option.value = value;
            option.textContent = String(descriptor.label == null ? value : descriptor.label);
            option.disabled = descriptor.disabled === true;
            select.appendChild(option);
        });
        select.value = String(cfg.value == null ? '' : cfg.value);
        select.disabled = cfg.disabled === true;
        select.required = cfg.required === true;
        return select;
    }

    function createInspectorGroup(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var groupWrap = document.createElement('section');
        groupWrap.className = [
            'fc-ui-inspector-group',
            sanitizeClassList(cfg.className || '')
        ].filter(Boolean).join(' ');
        if (cfg.groupKey) {
            groupWrap.setAttribute('data-group-key', String(cfg.groupKey));
        }

        var heading = document.createElement('h4');
        heading.className = 'fc-ui-inspector-group__title';
        heading.textContent = String(cfg.title || '');
        var fieldsWrap = document.createElement('div');
        fieldsWrap.className = 'fc-ui-inspector-group__fields';
        groupWrap.append(heading, fieldsWrap);
        return { groupWrap: groupWrap, heading: heading, fieldsWrap: fieldsWrap };
    }

    function createInspectorFieldShell(config) {
        var cfg = config && typeof config === 'object' ? config : {};
        var element = document.createElement('div');
        element.className = [
            'fc-ui-inspector-field',
            cfg.wide === true ? 'is-wide' : '',
            sanitizeClassList(cfg.className || '')
        ].filter(Boolean).join(' ');
        var label = null;
        if (cfg.label) {
            label = document.createElement('label');
            label.className = 'fc-ui-inspector-field__label';
            label.textContent = String(cfg.label);
            if (cfg.forId) {
                label.setAttribute('for', String(cfg.forId));
            }
            element.appendChild(label);
        }
        return { element: element, label: label };
    }

    window.FlatCMSUIPrimitives = Object.assign({}, window.FlatCMSUIPrimitives || {}, {
        createCompactSelectControl: createCompactSelectControl,
        createCompactColorControl: createCompactColorControl,
        createInputControl: createInputControl,
        createSelectControl: createSelectControl,
        createInspectorGroup: createInspectorGroup,
        createInspectorFieldShell: createInspectorFieldShell
    });

    window.FlatCMS = window.FlatCMS || {};
    window.FlatCMS.AdminUI = Object.assign({}, window.FlatCMS.AdminUI || {}, {
        field: Object.assign({}, window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.field || {}, {
            createInput: createInputControl
        }),
        color: Object.assign({}, window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.color || {}, {
            createCompact: createCompactColorControl
        }),
        icon: Object.assign({}, window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.icon || {}, {
            createBrowser: createIconBrowser
        }),
        select: Object.assign({}, window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.select || {}, {
            create: createSelectControl
        }),
        inspector: Object.assign({}, window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.inspector || {}, {
            createGroup: createInspectorGroup,
            createFieldShell: createInspectorFieldShell
        })
    });
})(window, document);
