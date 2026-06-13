/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    var config = null;
    var configEl = document.getElementById('translationsConfig');
    if (configEl && configEl.dataset.translationsConfig) {
        try {
            config = JSON.parse(configEl.dataset.translationsConfig);
        } catch (e) {
            config = null;
        }
    }

    if (!config && window.TranslationsConfig) {
        config = window.TranslationsConfig;
    }

    if (!config) return;

    // State
    var loadedModules = {};       // module -> { groups, rendered: true }
    var unsavedModules = {};      // module -> Set of changed keys
    var searchTerm = '';
    var filterMissing = false;

    // =============================================
    // DOM Ready
    // =============================================
    document.addEventListener('DOMContentLoaded', function() {
        initProgressBars();
        initModuleHeaders();
        initControls();
        initBeforeUnload();
    });

    function initProgressBars() {
        var fills = document.querySelectorAll('.progress-fill[data-progress]');
        fills.forEach(function(fill) {
            var val = parseInt(fill.dataset.progress, 10);
            if (!isNaN(val)) {
                fill.style.width = val + '%';
            }
        });
    }

    // =============================================
    // Module Accordion Headers
    // =============================================
    function initModuleHeaders() {
        var headers = document.querySelectorAll('.module-card-header');
        headers.forEach(function(header) {
            header.addEventListener('click', function() {
                var moduleName = this.dataset.module;
                toggleModule(moduleName, this);
            });
        });
    }

    function toggleModule(moduleName, headerEl) {
        var content = headerEl.nextElementSibling;
        var isActive = headerEl.classList.contains('active');

        if (isActive) {
            // Collapse
            content.style.maxHeight = content.scrollHeight + 'px';
            content.offsetHeight; // force reflow
            content.style.maxHeight = '0';
            headerEl.classList.remove('active');
            content.classList.remove('active');
        } else {
            // Expand immediately, then load if needed
            headerEl.classList.add('active');
            content.classList.add('active');
            expandContent(headerEl, content);

            if (!loadedModules[moduleName]) {
                loadModuleTranslations(moduleName, function() {
                    applyFilters(moduleName);
                });
            } else {
                applyFilters(moduleName);
            }
        }
    }

    function expandContent(headerEl, content) {
        content.style.maxHeight = 'none';
        var height = content.scrollHeight;
        content.style.maxHeight = '0';
        content.offsetHeight; // force reflow
        content.style.maxHeight = height + 'px';

        // After transition, set to none so content can grow dynamically
        setTimeout(function() {
            if (headerEl.classList.contains('active')) {
                content.style.maxHeight = 'none';
            }
        }, 350);
    }

    // =============================================
    // Load Module Translations (AJAX)
    // =============================================
    function loadModuleTranslations(moduleName, callback) {
        var content = document.querySelector('.module-card-content[data-module="' + moduleName + '"]');
        content.innerHTML = '<div class="module-loading"><i class="fas fa-spinner fa-spin"></i> ' + config.i18n.loading + '</div>';

        fetchJson(appendQuery(config.moduleTranslationsUrl, { module: moduleName }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function(data) {
            if (!data || data.success !== true) {
                throw new Error('Invalid JSON payload');
            }
            loadedModules[moduleName] = { groups: data.groups };
            renderModuleContent(moduleName, data.groups, data.referenceLang);
            if (callback) callback();
        })
        .catch(function(err) {
            console.error('Languages moduleTranslations error:', moduleName, err);
            var message = config.i18n.loadingError || 'Erreur de chargement des traductions';
            content.innerHTML = '<div class="module-loading module-loading-error"><i class="fas fa-exclamation-triangle"></i> ' + escapeHtml(message) + '</div>';
        });
    }

    // =============================================
    // Render Module Content
    // =============================================
    function renderModuleContent(moduleName, groups, referenceLang) {
        var content = document.querySelector('.module-card-content[data-module="' + moduleName + '"]');
        var html = '<div class="module-card-body">';

        for (var groupName in groups) {
            if (!groups.hasOwnProperty(groupName)) continue;

            var items = groups[groupName];
            var groupTitle = groupName === '_general' ? config.i18n.generalKeys : config.i18n.group + ' "' + groupName + '"';

            html += '<div class="translation-group-title"><i class="fas fa-folder-open"></i> ' + escapeHtml(groupTitle) + '</div>';

            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var missingClass = item.missing ? ' missing' : '';

                html += '<div class="translation-row' + missingClass + '" data-key="' + escapeAttr(item.key) + '" data-module="' + escapeAttr(moduleName) + '">';

                // Key column
                html += '<div class="translation-key' + missingClass + '">';
                html += escapeHtml(item.key);
                if (item.missing) {
                    html += ' <i class="fas fa-exclamation-triangle translation-missing-icon"></i>';
                }
                html += '</div>';

                // Input column
                html += '<div>';
                html += '<input type="text"';
                html += ' class="translation-input' + missingClass + '"';
                html += ' data-key="' + escapeAttr(item.key) + '"';
                html += ' data-module="' + escapeAttr(moduleName) + '"';
                html += ' data-original="' + escapeAttr(item.translation) + '"';
                html += ' value="' + escapeAttr(item.translation) + '"';
                html += ' placeholder="' + escapeAttr(item.reference) + '"';
                html += '>';

                if (item.reference) {
                    html += '<div class="translation-ref">';
                    html += '<i class="fas fa-info-circle"></i> ';
                    html += config.i18n.reference + ' (' + escapeHtml(referenceLang.toUpperCase()) + '): ' + escapeHtml(item.reference);
                    if (item.missing) {
                        html += ' <button type="button" class="btn btn-sm btn-secondary copy-ref-btn"';
                        html += ' data-key="' + escapeAttr(item.key) + '"';
                        html += ' data-value="' + escapeAttr(item.reference) + '"';
                        html += ' data-module="' + escapeAttr(moduleName) + '">';
                        html += '<i class="fas fa-copy"></i> ' + config.i18n.copyRef;
                        html += '</button>';
                    }
                    html += '</div>';
                }

                html += '</div>';
                html += '</div>';
            }
        }

        // Save button for this module
        html += '<div class="module-save-footer">';
        html += '<button type="button" class="btn btn-primary btn-sm save-module-btn" data-module="' + escapeAttr(moduleName) + '">';
        html += '<i class="fas fa-save"></i> ' + config.i18n.saveModule;
        html += '</button>';
        html += '</div>';

        html += '</div>';
        content.innerHTML = html;

        // Attach events
        attachModuleEvents(moduleName, content);
    }

    // =============================================
    // Attach Events to Module Content
    // =============================================
    function attachModuleEvents(moduleName, container) {
        // Input change tracking
        var inputs = container.querySelectorAll('.translation-input');
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                var key = this.dataset.key;
                var original = this.dataset.original || '';
                var mod = this.dataset.module;

                if (this.value !== original) {
                    if (!unsavedModules[mod]) unsavedModules[mod] = new Set();
                    unsavedModules[mod].add(key);
                    this.classList.add('modified');
                    markModuleUnsaved(mod, true);
                } else {
                    if (unsavedModules[mod]) unsavedModules[mod].delete(key);
                    this.classList.remove('modified');
                    if (!unsavedModules[mod] || unsavedModules[mod].size === 0) {
                        markModuleUnsaved(mod, false);
                    }
                }

                syncTranslationRowState(this);
            });
        });

        // Copy reference buttons (event delegation to support dynamic add/remove)
        container.addEventListener('click', function(e) {
            var btn = e.target.closest('.copy-ref-btn');
            if (!btn || !container.contains(btn)) {
                return;
            }
            e.stopPropagation();
            var key = btn.dataset.key;
            var value = btn.dataset.value;
            var mod = btn.dataset.module;
            var input = container.querySelector('input[data-key="' + key + '"][data-module="' + mod + '"]');
            if (input) {
                input.value = value;
                input.dispatchEvent(new Event('input'));
            }
        });

        // Save module button
        var saveBtn = container.querySelector('.save-module-btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                saveModule(this.dataset.module, this);
            });
        }
    }

    // =============================================
    // Mark Module as Unsaved
    // =============================================
    function markModuleUnsaved(moduleName, unsaved) {
        var card = document.getElementById('module-' + moduleName);
        if (!card) return;

        if (unsaved) {
            card.classList.add('has-unsaved');
            var saveBtn = card.querySelector('.save-module-btn');
            if (saveBtn) saveBtn.classList.add('pulse');
        } else {
            card.classList.remove('has-unsaved');
            var saveBtn2 = card.querySelector('.save-module-btn');
            if (saveBtn2) saveBtn2.classList.remove('pulse');
        }

        // Update global save button
        var hasAnyUnsaved = Object.keys(unsavedModules).some(function(m) {
            return unsavedModules[m] && unsavedModules[m].size > 0;
        });
        var saveAllBtn = document.getElementById('saveAllBtn');
        if (saveAllBtn) {
            if (hasAnyUnsaved) {
                saveAllBtn.classList.add('pulse');
            } else {
                saveAllBtn.classList.remove('pulse');
            }
        }
    }

    // =============================================
    // Save Module (AJAX)
    // =============================================
    function saveModule(moduleName, btnEl) {
        var card = document.getElementById('module-' + moduleName);
        if (!card) return;

        var inputs = card.querySelectorAll('.translation-input[data-module="' + moduleName + '"]');
        var translations = {};
        inputs.forEach(function(input) {
            translations[input.dataset.key] = input.value;
        });

        if (btnEl) {
            btnEl.disabled = true;
            btnEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';
        }

        var body = '_token=' + encodeURIComponent(config.csrfToken)
            + '&module=' + encodeURIComponent(moduleName);

        for (var key in translations) {
            if (translations.hasOwnProperty(key)) {
                body += '&translations[' + encodeURIComponent(key) + ']=' + encodeURIComponent(translations[key]);
            }
        }

        return fetchJson(config.saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: body
        })
        .then(function(data) {
            if (!data || data.success !== true) {
                throw new Error(data && data.message ? data.message : 'Save failed');
            }

            // Update originals
            inputs.forEach(function(input) {
                input.dataset.original = input.value;
                input.classList.remove('modified');
            });

            // Clear unsaved state
            delete unsavedModules[moduleName];
            markModuleUnsaved(moduleName, false);

            // Update stats for this module
            updateModuleStats(moduleName);

            showToast(config.i18n.moduleSaved.replace(':module', moduleName), 'success');

            if (btnEl) {
                btnEl.disabled = false;
                btnEl.innerHTML = '<i class="fas fa-save"></i> ' + config.i18n.saveModule;
            }
        })
        .catch(function() {
            if (btnEl) {
                btnEl.disabled = false;
                btnEl.innerHTML = '<i class="fas fa-save"></i> ' + config.i18n.saveModule;
            }
            showToast(config.i18n.saveModuleError || "Erreur lors de l'enregistrement du module", 'error');
        });
    }

    // =============================================
    // Update Module Stats After Save
    // =============================================
    function missingLabel(count) {
        if (count > 1) {
            return config.i18n.translationMissingPlural || config.i18n.translationMissing;
        }
        return config.i18n.translationMissingSingular || config.i18n.translationMissing;
    }

    function syncTranslationRowState(input) {
        if (!input) return false;

        var row = input.closest('.translation-row');
        if (!row) return false;

        var keyEl = row.querySelector('.translation-key');
        var refBlock = row.querySelector('.translation-ref');
        var key = input.dataset.key || '';
        var mod = input.dataset.module || '';
        var referenceValue = (input.placeholder || '').trim();
        var isMissing = input.value.trim() === '' && referenceValue !== '';

        input.classList.toggle('missing', isMissing);
        row.classList.toggle('missing', isMissing);
        if (keyEl) {
            keyEl.classList.toggle('missing', isMissing);
        }

        var missingIcon = row.querySelector('.translation-missing-icon');
        if (isMissing) {
            if (!missingIcon && keyEl) {
                missingIcon = document.createElement('i');
                missingIcon.className = 'fas fa-exclamation-triangle translation-missing-icon';
                keyEl.appendChild(missingIcon);
            }
        } else if (missingIcon) {
            missingIcon.remove();
        }

        if (refBlock) {
            var copyBtn = refBlock.querySelector('.copy-ref-btn');
            if (isMissing && referenceValue !== '') {
                if (!copyBtn) {
                    copyBtn = document.createElement('button');
                    copyBtn.type = 'button';
                    copyBtn.className = 'btn btn-sm btn-secondary copy-ref-btn';
                    copyBtn.dataset.key = key;
                    copyBtn.dataset.value = referenceValue;
                    copyBtn.dataset.module = mod;
                    copyBtn.innerHTML = '<i class="fas fa-copy"></i> ' + (config.i18n.copyRef || 'Copy from reference');
                    refBlock.appendChild(copyBtn);
                } else {
                    copyBtn.dataset.value = referenceValue;
                }
            } else if (copyBtn) {
                copyBtn.remove();
            }
        }

        return isMissing;
    }

    function updateModuleStats(moduleName) {
        var card = document.getElementById('module-' + moduleName);
        if (!card) return;

        var inputs = card.querySelectorAll('.translation-input[data-module="' + moduleName + '"]');
        var total = inputs.length;
        var missing = 0;

        inputs.forEach(function(input) {
            if (syncTranslationRowState(input)) {
                missing++;
            }
        });

        var translated = total - missing;
        var percentage = total > 0 ? Math.floor((translated / total) * 100) : 100;
        if (missing > 0 && percentage >= 100) {
            percentage = 99;
        }

        // Update header stats
        var header = card.querySelector('.module-card-header');
        var translatedCountEl = header.querySelector('.module-translated-count');
        var totalCountEl = header.querySelector('.module-total-count');
        var missingCountEl = header.querySelector('.module-missing-count');
        var progressFill = header.querySelector('.module-progress-mini .progress-fill');
        var percentageEl = header.querySelector('.module-percentage');
        var icon = header.querySelector('.module-icon');

        if (translatedCountEl) translatedCountEl.textContent = translated;
        if (totalCountEl) totalCountEl.textContent = total;

        if (missingCountEl) {
            if (missing > 0) {
                missingCountEl.textContent = missing + ' ' + missingLabel(missing);
            } else {
                missingCountEl.textContent = '';
                // Remove the bullet before it
                var statsText = header.querySelector('.module-stats-text');
                if (statsText) {
                    statsText.innerHTML = '<span class="module-translated-count">' + translated + '</span> / <span class="module-total-count">' + total + '</span> ' + config.i18n.keys;
                }
            }
        }

        if (progressFill) {
            progressFill.style.width = percentage + '%';
            progressFill.classList.toggle('is-complete', percentage >= 100);
            progressFill.classList.toggle('is-partial', percentage < 100);
        }
        if (percentageEl) {
            percentageEl.textContent = percentage + '%';
            percentageEl.classList.toggle('is-complete', percentage >= 100);
            percentageEl.classList.toggle('is-partial', percentage < 100);
        }

        if (icon) {
            if (missing > 0) {
                icon.className = 'module-icon has-missing';
                icon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
            } else {
                icon.className = 'module-icon complete';
                icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            }
        }

        // Update global stats
        updateGlobalStats();
    }

    // =============================================
    // Update Global Stats
    // =============================================
    function updateGlobalStats() {
        var allCards = document.querySelectorAll('.module-card');
        var totalGlobal = 0;
        var translatedGlobal = 0;

        allCards.forEach(function(card) {
            var header = card.querySelector('.module-card-header');
            var translatedEl = header.querySelector('.module-translated-count');
            var totalEl = header.querySelector('.module-total-count');

            if (translatedEl && totalEl) {
                translatedGlobal += parseInt(translatedEl.textContent) || 0;
                totalGlobal += parseInt(totalEl.textContent) || 0;
            }
        });

        var globalMissing = totalGlobal - translatedGlobal;
        var globalPct = totalGlobal > 0 ? Math.floor((translatedGlobal / totalGlobal) * 100) : 100;
        if (globalMissing > 0 && globalPct >= 100) {
            globalPct = 99;
        }

        var pctEl = document.getElementById('globalPercentage');
        var fillEl = document.getElementById('globalProgressFill');

        if (pctEl) {
            pctEl.textContent = globalPct + '%';
            pctEl.classList.toggle('is-complete', globalPct >= 100);
            pctEl.classList.toggle('is-partial', globalPct < 100);
        }
        if (fillEl) {
            fillEl.style.width = globalPct + '%';
            fillEl.classList.toggle('is-complete', globalPct >= 100);
            fillEl.classList.toggle('is-partial', globalPct < 100);
        }

        var translatedCountEl = document.getElementById('globalTranslatedCount');
        var totalCountEl = document.getElementById('globalTotalCount');
        if (translatedCountEl) {
            translatedCountEl.textContent = translatedGlobal;
        }
        if (totalCountEl) {
            totalCountEl.textContent = totalGlobal;
        }

        var globalMissingBadge = document.getElementById('globalMissingBadge');
        var globalMissingBadgeText = document.getElementById('globalMissingBadgeText');
        if (globalMissingBadge && globalMissingBadgeText) {
            if (globalMissing > 0) {
                globalMissingBadge.classList.remove('badge-complete');
                globalMissingBadge.classList.add('badge-missing');
                globalMissingBadge.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <span id="globalMissingBadgeText">' + globalMissing + ' ' + escapeHtml(missingLabel(globalMissing)) + '</span>';
            } else {
                globalMissingBadge.classList.remove('badge-missing');
                globalMissingBadge.classList.add('badge-complete');
                globalMissingBadge.innerHTML = '<i class="fas fa-check-circle"></i> <span id="globalMissingBadgeText">100%</span>';
            }
        }
    }

    // =============================================
    // Controls (Search, Filter, Expand/Collapse, Scan)
    // =============================================
    function initControls() {
        // Search
        var searchInput = document.getElementById('searchInput');
        if (searchInput) {
            var debounceTimer = null;
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                var val = this.value;
                debounceTimer = setTimeout(function() {
                    searchTerm = val.toLowerCase().trim();
                    applyAllFilters();
                }, 200);
            });
        }

        // Filter missing
        var missingCheckbox = document.getElementById('showOnlyMissing');
        if (missingCheckbox) {
            missingCheckbox.addEventListener('change', function() {
                filterMissing = this.checked;
                applyAllFilters();
                if (filterMissing) {
                    expandVisibleModulesForFilter();
                }
            });
        }

        // Expand all
        var btnExpand = document.getElementById('btnExpandAll');
        if (btnExpand) {
            btnExpand.addEventListener('click', expandAll);
        }

        // Collapse all
        var btnCollapse = document.getElementById('btnCollapseAll');
        if (btnCollapse) {
            btnCollapse.addEventListener('click', collapseAll);
        }

        // Scan & Fill
        var btnScan = document.getElementById('btnScanFill');
        if (btnScan) {
            btnScan.addEventListener('click', scanFillMissing);
        }

        // Save all
        var btnSaveAll = document.getElementById('saveAllBtn');
        if (btnSaveAll) {
            btnSaveAll.addEventListener('click', saveAll);
        }
    }

    // =============================================
    // Expand / Collapse All
    // =============================================
    function expandAll() {
        var headers = document.querySelectorAll('.module-card-header');
        var pending = [];

        headers.forEach(function(header) {
            var moduleName = header.dataset.module;
            var card = header.closest('.module-card');

            // Skip hidden cards
            if (card && card.classList.contains('hidden')) return;

            if (!header.classList.contains('active')) {
                pending.push(function() {
                    toggleModule(moduleName, header);
                });
            }
        });

        // Stagger expansions slightly for smoother UX
        pending.forEach(function(fn, i) {
            setTimeout(fn, i * 50);
        });
    }

    function collapseAll() {
        var headers = document.querySelectorAll('.module-card-header.active');
        headers.forEach(function(header) {
            var content = header.nextElementSibling;
            content.style.maxHeight = content.scrollHeight + 'px';
            content.offsetHeight;
            content.style.maxHeight = '0';
            header.classList.remove('active');
            content.classList.remove('active');
        });
    }

    // =============================================
    // Apply Filters (Search + Missing)
    // =============================================
    function applyAllFilters() {
        var moduleCards = document.querySelectorAll('.module-card');
        var anyVisible = false;

        moduleCards.forEach(function(card) {
            var moduleName = card.dataset.module;

            // If module is loaded, filter its rows
            if (loadedModules[moduleName]) {
                applyFilters(moduleName);
            }

            // Determine if this module card should be visible
            var shouldShow = shouldShowModuleCard(card, moduleName);
            card.classList.toggle('hidden', !shouldShow);
            if (shouldShow) anyVisible = true;

            // If search is active and module matches, auto-expand
            if (searchTerm && shouldShow && !card.querySelector('.module-card-header').classList.contains('active')) {
                if (loadedModules[moduleName]) {
                    var header = card.querySelector('.module-card-header');
                    toggleModule(moduleName, header);
                }
            }
        });

        // Show/hide no results message
        var noResults = document.getElementById('noResults');
        if (noResults) {
            var showNoResults = !anyVisible && (searchTerm || filterMissing);
            noResults.classList.toggle('hidden', !showNoResults);
        }

        if (filterMissing) {
            expandVisibleModulesForFilter();
        }
    }

    function expandVisibleModulesForFilter() {
        var headers = document.querySelectorAll('.module-card-header');
        var pending = [];

        headers.forEach(function(header) {
            var card = header.closest('.module-card');
            if (!card || card.classList.contains('hidden')) {
                return;
            }

            if (header.classList.contains('active')) {
                return;
            }

            var moduleName = header.dataset.module;
            pending.push(function() {
                toggleModule(moduleName, header);
            });
        });

        pending.forEach(function(fn, i) {
            setTimeout(fn, i * 50);
        });
    }

    function shouldShowModuleCard(card, moduleName) {
        // Filter: missing only -> hide 100% complete modules
        if (filterMissing) {
            var header = card.querySelector('.module-card-header');
            var pctEl = header.querySelector('.module-percentage');
            if (pctEl && parseInt(pctEl.textContent) >= 100) {
                return false;
            }
        }

        // Search: check module name
        if (searchTerm) {
            var moduleLower = moduleName.toLowerCase();
            if (moduleLower.indexOf(searchTerm) !== -1) return true;

            // Check loaded keys/values
            if (loadedModules[moduleName]) {
                var groups = loadedModules[moduleName].groups;
                for (var g in groups) {
                    if (!groups.hasOwnProperty(g)) continue;
                    for (var i = 0; i < groups[g].length; i++) {
                        var item = groups[g][i];
                        if (item.key.toLowerCase().indexOf(searchTerm) !== -1) return true;
                        if (item.translation.toLowerCase().indexOf(searchTerm) !== -1) return true;
                        if (item.reference.toLowerCase().indexOf(searchTerm) !== -1) return true;
                    }
                }
                return false;
            }

            // Not loaded yet - show it (it might contain matches)
            return true;
        }

        return true;
    }

    function applyFilters(moduleName) {
        var card = document.getElementById('module-' + moduleName);
        if (!card) return;

        var rows = card.querySelectorAll('.translation-row[data-module="' + moduleName + '"]');
        var groupTitles = card.querySelectorAll('.translation-group-title');

        rows.forEach(function(row) {
            var show = true;
            var key = row.dataset.key || '';
            var input = row.querySelector('.translation-input');
            var value = input ? input.value : '';
            var placeholder = input ? input.placeholder : '';

            // Filter: missing
            if (filterMissing) {
                var isMissing = row.classList.contains('missing');
                if (!isMissing) show = false;
            }

            // Search
            if (show && searchTerm) {
                var keyLower = key.toLowerCase();
                var valLower = value.toLowerCase();
                var refLower = placeholder.toLowerCase();
                if (keyLower.indexOf(searchTerm) === -1 &&
                    valLower.indexOf(searchTerm) === -1 &&
                    refLower.indexOf(searchTerm) === -1) {
                    show = false;
                }
            }

            row.classList.toggle('hidden', !show);
        });

        // Hide group titles if all their rows are hidden
        groupTitles.forEach(function(title) {
            var nextEl = title.nextElementSibling;
            var hasVisible = false;
            while (nextEl && !nextEl.classList.contains('translation-group-title') && !nextEl.classList.contains('module-save-footer')) {
                if (nextEl.classList.contains('translation-row') && !nextEl.classList.contains('hidden')) {
                    hasVisible = true;
                    break;
                }
                nextEl = nextEl.nextElementSibling;
            }
            title.classList.toggle('hidden', !hasVisible);
        });
    }

    // =============================================
    // Save All Modules
    // =============================================
    function saveAll() {
        var saveAllBtn = document.getElementById('saveAllBtn');
        var originalHtml = saveAllBtn ? saveAllBtn.innerHTML : '';

        var modulesWithChanges = Object.keys(unsavedModules).filter(function(m) {
            return unsavedModules[m] && unsavedModules[m].size > 0;
        });

        if (modulesWithChanges.length === 0) {
            showToast(config.i18n.allSaved, 'success');
            return;
        }

        if (saveAllBtn) {
            saveAllBtn.disabled = true;
            saveAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';
        }

        var promises = modulesWithChanges.map(function(mod) {
            return saveModule(mod, null);
        });

        Promise.all(promises).then(function() {
            showToast(config.i18n.allSaved, 'success');
        }).catch(function() {
            showToast(config.i18n.saveAllError || "Erreur lors de l'enregistrement des traductions", 'error');
        }).finally(function() {
            if (saveAllBtn) {
                saveAllBtn.disabled = false;
                saveAllBtn.innerHTML = originalHtml;
            }
        });
    }

    // =============================================
    // Scan & Fill Missing Keys
    // =============================================
    function scanFillMissing() {
        var btn = document.getElementById('btnScanFill');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';
        }

        fetchJson(config.scanFillUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: '_token=' + encodeURIComponent(config.csrfToken)
        })
        .then(function(data) {
            if (!data || data.success !== true) {
                throw new Error(data && data.message ? data.message : 'Scan failed');
            }
            if (data.total_added > 0) {
                showToast(config.i18n.scanFillSuccess.replace(':count', data.total_added), 'success');
                // Reload the page to get updated stats
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showToast(config.i18n.scanFillNone, 'success');
            }
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search-plus"></i> ' + config.i18n.scanFillMissing;
            }
        })
        .catch(function() {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search-plus"></i> ' + config.i18n.scanFillMissing;
            }
        });
    }

    // =============================================
    // Before Unload Protection
    // =============================================
    function initBeforeUnload() {
        window.addEventListener('beforeunload', function(e) {
            var hasChanges = Object.keys(unsavedModules).some(function(m) {
                return unsavedModules[m] && unsavedModules[m].size > 0;
            });
            if (hasChanges) {
                e.preventDefault();
            }
        });
    }

    // =============================================
    // Toast Notification
    // =============================================
    function showToast(message, type) {
        var container = document.querySelector('.menu-toast-container[data-source="languages"]');
        if (!container) {
            container = document.createElement('div');
            container.className = 'menu-toast-container';
            container.dataset.source = 'languages';
            document.body.appendChild(container);
        }

        var toastType = (type === 'error' || type === 'warning') ? type : 'success';
        var iconClass = toastType === 'error'
            ? 'fas fa-circle-exclamation'
            : (toastType === 'warning' ? 'fas fa-triangle-exclamation' : 'fas fa-circle-check');
        var title = toastType === 'error'
            ? 'Erreur'
            : (toastType === 'warning' ? 'Info' : 'Succes');

        var toast = document.createElement('div');
        toast.className = 'menu-toast menu-toast-' + toastType;
        toast.setAttribute('role', 'status');
        toast.innerHTML =
            '<span class="menu-toast-icon" aria-hidden="true"><i class="' + iconClass + '"></i></span>' +
            '<span class="menu-toast-content">' +
            '<span class="menu-toast-title">' + title + '</span>' +
            '<span class="menu-toast-message">' + escapeHtml(String(message || '')) + '</span>' +
            '</span>';

        container.appendChild(toast);
        requestAnimationFrame(function() { toast.classList.add('is-visible'); });

        setTimeout(function() {
            toast.classList.remove('is-visible');
            setTimeout(function() { toast.remove(); }, 260);
        }, 3000);
    }

    // =============================================
    // Utilities
    // =============================================
    function toLocalUrl(rawUrl) {
        var value = String(rawUrl || '').trim();
        if (value === '') return value;
        try {
            var u = new URL(value, window.location.origin);
            return (u.pathname || '/') + (u.search || '') + (u.hash || '');
        } catch (e) {
            return value;
        }
    }

    function appendQuery(rawUrl, params) {
        var localUrl = toLocalUrl(rawUrl);
        var hash = '';
        var hashIndex = localUrl.indexOf('#');
        if (hashIndex !== -1) {
            hash = localUrl.slice(hashIndex);
            localUrl = localUrl.slice(0, hashIndex);
        }

        var queryIndex = localUrl.indexOf('?');
        var basePath = queryIndex === -1 ? localUrl : localUrl.slice(0, queryIndex);
        var queryString = queryIndex === -1 ? '' : localUrl.slice(queryIndex + 1);
        var searchParams = new URLSearchParams(queryString);

        Object.keys(params || {}).forEach(function(key) {
            var value = params[key];
            if (value === undefined || value === null) {
                return;
            }
            searchParams.set(key, String(value));
        });

        var nextQuery = searchParams.toString();
        return basePath + (nextQuery ? ('?' + nextQuery) : '') + hash;
    }

    function parseJsonSafe(text) {
        var src = String(text || '').replace(/^\uFEFF/, '').trim();
        if (src === '') {
            throw new Error('Empty response');
        }

        try {
            return JSON.parse(src);
        } catch (e) {
            var start = src.indexOf('{');
            var end = src.lastIndexOf('}');
            if (start !== -1 && end > start) {
                var candidate = src.slice(start, end + 1);
                return JSON.parse(candidate);
            }
            throw new Error('Invalid JSON payload');
        }
    }

    function fetchJson(url, options) {
        return fetch(toLocalUrl(url), options || {})
            .then(function(r) {
                return r.text().then(function(text) {
                    if (!r.ok) {
                        throw new Error('HTTP ' + r.status + ' - ' + (text || '').slice(0, 160));
                    }
                    return parseJsonSafe(text);
                });
            });
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escapeAttr(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

})();
