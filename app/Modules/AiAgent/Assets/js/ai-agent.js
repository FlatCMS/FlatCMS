/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
  'use strict';

  function showToast(message, type) {
    var text = String(message || '').trim();
    if (text === '') {
      return;
    }

    if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
      window.FlatCMS.toast.show(text, type || 'warning');
    }
  }

  function parseJsonAttribute(node, name) {
    if (!node) {
      return {};
    }

    try {
      var raw = String(node.getAttribute(name) || '').trim();
      return raw === '' ? {} : JSON.parse(raw);
    } catch (error) {
      return {};
    }
  }

  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return String(meta && meta.getAttribute ? meta.getAttribute('content') : '').trim();
  }

  function template(text, replacements) {
    var rendered = String(text || '');
    Object.keys(replacements || {}).forEach(function(key) {
      rendered = rendered.replace(new RegExp(':' + key, 'g'), String(replacements[key] || ''));
    });
    return rendered;
  }

  function lowerFirst(value) {
    var text = String(value || '').trim();
    if (text === '') {
      return '';
    }

    return text.charAt(0).toLowerCase() + text.slice(1);
  }

  function upperFirst(value) {
    var text = String(value || '').trim();
    if (text === '') {
      return '';
    }

    return text.charAt(0).toUpperCase() + text.slice(1);
  }

  function uniqueStringList(values) {
    var seen = Object.create(null);
    var items = Array.isArray(values) ? values : [];

    return items.reduce(function(result, value) {
      var normalized = String(value || '').replace(/\s+/g, ' ').trim();
      if (normalized === '' || seen[normalized]) {
        return result;
      }

      seen[normalized] = true;
      result.push(normalized);
      return result;
    }, []);
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escapeSelector(value) {
    var text = String(value || '');
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(text);
    }

    return text.replace(/([ #;?%&,.+*~\\':"!^$[\]()=>|/@])/g, '\\$1');
  }

  function readFieldValue(field, editorHandleName) {
    if (!(field instanceof HTMLElement)) {
      return '';
    }

    if (field instanceof HTMLTextAreaElement) {
      var handle = field[editorHandleName] || null;
      if (handle && typeof handle.getHtml === 'function') {
        try {
          return String(handle.getHtml() || '');
        } catch (error) {
          return String(field.value || '');
        }
      }

      return String(field.value || '');
    }

    if ('value' in field) {
      return String(field.value || '');
    }

    return '';
  }

  function setTextareaValue(field, value, editorHandleName) {
    if (!(field instanceof HTMLTextAreaElement)) {
      return;
    }

    var text = String(value || '');
    field.value = text;

    var handle = field[editorHandleName] || null;
    if (handle && handle.editor && typeof handle.editor.setContents === 'function') {
      try {
        handle.editor.setContents(text);
      } catch (error) {
        field.value = text;
      }
    }

    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function plainTextFromHtml(value) {
    var html = String(value || '').trim();
    if (html === '') {
      return '';
    }

    var sandbox = document.createElement('div');
    sandbox.innerHTML = html;
    return String(sandbox.textContent || sandbox.innerText || '').trim();
  }

  function truncateText(value, limit) {
    var text = String(value || '');
    if (text.length <= limit) {
      return text;
    }

    return text.slice(0, Math.max(0, limit - 1)).trim() + '…';
  }

  function cloneValues(values) {
    return JSON.parse(JSON.stringify(values || {}));
  }

  function initAiAgent() {
    var root = document.querySelector('[data-ai-agent-root]');
    if (!root) {
      return;
    }

    var i18n = parseJsonAttribute(root, 'data-i18n');
    var endpoint = String(root.getAttribute('data-endpoint') || '').trim();
    var iconDark = String(root.getAttribute('data-icon-dark') || '').trim();
    var iconLight = String(root.getAttribute('data-icon-light') || '').trim();
    var greetingSessionKey = 'flatcms.aiAgent.sessionGreetingSeen';
    var greetingTurnSessionKey = 'flatcms.aiAgent.sessionGreetingTurn';
    var greetingEntitySessionKey = 'flatcms.aiAgent.sessionGreetingEntities';
    var navigationSessionKey = 'flatcms.aiAgent.sessionNavigation';
    var navigationSessionLimit = 8;
    var pageVisitToken = String(Date.now()) + ':' + Math.random().toString(36).slice(2);
    var currentUser = parseJsonAttribute(root, 'data-user');
    var floating = root.querySelector('[data-ai-agent-floating]');
    var floatingButton = root.querySelector('[data-ai-agent-floating-button]');
    var floatingCard = root.querySelector('[data-ai-agent-floating-card]');
    var floatingContext = root.querySelector('[data-ai-agent-floating-context]');
    var floatingActions = root.querySelector('[data-ai-agent-floating-actions]');
    var floatingCloseButton = root.querySelector('[data-ai-agent-floating-close]');
    var drawer = root.querySelector('[data-ai-agent-drawer]');
    var backdrop = root.querySelector('.ai-agent-backdrop');
    var thread = root.querySelector('[data-ai-agent-thread]');
    var suggestions = root.querySelector('[data-ai-agent-suggestions]');
    var workspaceBody = root.querySelector('[data-ai-agent-workspace-body]');
    var workspaceMeta = root.querySelector('[data-ai-agent-workspace-meta]');
    var restoreButton = root.querySelector('[data-ai-agent-restore]');
    var applyButton = root.querySelector('[data-ai-agent-apply]');
    var sendButton = root.querySelector('[data-ai-agent-send]');
    var input = root.querySelector('[data-ai-agent-input]');
    var contextLabel = root.querySelector('[data-ai-agent-context-label]');
    var closeButtons = root.querySelectorAll('[data-ai-agent-close]');

    if (!floating || !floatingButton || !floatingCard || !floatingContext || !floatingActions || !floatingCloseButton || !drawer || !backdrop || !thread || !suggestions || !workspaceBody || !workspaceMeta || !restoreButton || !applyButton || !sendButton || !input || endpoint === '') {
      return;
    }

    var state = {
      open: false,
      miniOpen: false,
      context: null,
      currentTarget: null,
      snapshot: null,
      navigation: null,
      liveDirty: false,
      selectedVariant: -1,
      currentProposalType: '',
      currentProposal: null,
      selectedSuggestionAction: '',
      selectedSuggestionLabel: '',
      floatingContextKey: '',
      floatingMessage: '',
      floatingDragged: false
    };
    var floatingPlacementFrame = 0;
    var floatingHideTimer = 0;
    var floatingShowTimer = 0;
    var floatingHoverDelayMs = 900;
    var floatingFocusDelayMs = 520;
    var floatingHideDelayMs = 960;

    function getPageFieldId(locale, key) {
      return locale ? 'page_' + locale + '_' + key : key;
    }

    function getFieldNode(context, key) {
      if (!context || key === '') {
        return null;
      }

      if (context.module === 'pages') {
        return document.getElementById(getPageFieldId(context.locale, key));
      }

      return document.getElementById(key);
    }

    function getFieldValue(context, key) {
      var field = getFieldNode(context, key);
      if (!field) {
        return '';
      }

      if (context.module === 'pages' && key === 'content') {
        return readFieldValue(field, '__pageSunEditorHandle');
      }

      if (context.module === 'posts' && key === 'content') {
        return readFieldValue(field, '__postSunEditorHandle');
      }

      return readFieldValue(field, '');
    }

    function collectPageValues(locale) {
      return {
        title: String(document.getElementById(getPageFieldId(locale, 'title')) && document.getElementById(getPageFieldId(locale, 'title')).value || ''),
        slug: String(document.getElementById(getPageFieldId(locale, 'slug')) && document.getElementById(getPageFieldId(locale, 'slug')).value || ''),
        content: getFieldValue({ module: 'pages', locale: locale }, 'content'),
        meta_title: String(document.getElementById(getPageFieldId(locale, 'meta_title')) && document.getElementById(getPageFieldId(locale, 'meta_title')).value || ''),
        meta_description: String(document.getElementById(getPageFieldId(locale, 'meta_description')) && document.getElementById(getPageFieldId(locale, 'meta_description')).value || '')
      };
    }

    function collectPostValues() {
      var postCategories = collectPostCategories(document.querySelector('form[data-ai-agent-form="posts"]'));
      return {
        title: String(document.getElementById('title') && document.getElementById('title').value || ''),
        slug: String(document.getElementById('slug') && document.getElementById('slug').value || ''),
        excerpt: String(document.getElementById('excerpt') && document.getElementById('excerpt').value || ''),
        content: getFieldValue({ module: 'posts', locale: '' }, 'content'),
        categories: postCategories.selected_category_ids,
        featured_image: String(document.getElementById('featured_image') && document.getElementById('featured_image').value || ''),
        meta_title: String(document.getElementById('meta_title') && document.getElementById('meta_title').value || ''),
        meta_description: String(document.getElementById('meta_description') && document.getElementById('meta_description').value || '')
      };
    }

    function collectPostCategories(form) {
      if (!(form instanceof HTMLFormElement)) {
        return {
          selected_category_ids: [],
          selected_categories: [],
          available_categories: []
        };
      }

      var selectedIds = [];
      var selectedNames = [];
      var availableNames = [];
      var fields = form.querySelectorAll('input[name="categories[]"]');

      Array.prototype.forEach.call(fields, function(field) {
        if (!(field instanceof HTMLInputElement)) {
          return;
        }

        var categoryId = String(field.value || '').trim();
        var label = field.closest('label');
        var categoryName = String(label && label.textContent || '').replace(/\s+/g, ' ').trim();

        if (categoryName !== '') {
          availableNames.push(categoryName);
        }

        if (!field.checked) {
          return;
        }

        if (categoryId !== '') {
          selectedIds.push(categoryId);
        }

        if (categoryName !== '') {
          selectedNames.push(categoryName);
        }
      });

      return {
        selected_category_ids: uniqueStringList(selectedIds),
        selected_categories: uniqueStringList(selectedNames),
        available_categories: uniqueStringList(availableNames)
      };
    }

    function hydrateContext(context) {
      if (!context || !(context.form instanceof HTMLFormElement)) {
        return context;
      }

      if (context.module === 'pages') {
        var activeLocaleInput = context.form.querySelector('[data-pages-active-locale]');
        var sourceLocaleInput = context.form.querySelector('input[name="source_locale"]');
        var targetLocale = String(context.target_locale || '').trim();
        var currentLocale = targetLocale !== ''
          ? targetLocale
          : String(activeLocaleInput && activeLocaleInput.value || context.locale || '').trim();
        var currentSourceLocale = String(sourceLocaleInput && sourceLocaleInput.value || context.source_locale || currentLocale).trim();
        if (currentLocale === '') {
          currentLocale = currentSourceLocale;
        }

        context.locale = currentLocale;
        context.target_locale = currentLocale;
        context.source_locale = currentSourceLocale;
        context.current = collectPageValues(currentLocale);
        context.source = collectPageValues(currentSourceLocale);
        return context;
      }

      var localeInput = context.form.querySelector('input[name="locale"]');
      var postSourceLocaleInput = context.form.querySelector('input[name="source_locale"]');
      var sourceIdInput = context.form.querySelector('input[name="source_id"]');
      context.locale = String(localeInput && localeInput.value || context.locale || '').trim();
      context.source_locale = String(postSourceLocaleInput && postSourceLocaleInput.value || context.source_locale || context.locale).trim();
      context.source_id = String(sourceIdInput && sourceIdInput.value || context.source_id || '').trim();
      context.current = collectPostValues();
      context.source = context.source_locale !== '' && context.source_locale !== context.locale ? {} : cloneValues(context.current);
      var postCategories = collectPostCategories(context.form);
      context.selected_category_ids = postCategories.selected_category_ids;
      context.selected_categories = postCategories.selected_categories;
      context.available_categories = postCategories.available_categories;
      return context;
    }

    function getContextFromTarget(target) {
      var form = target.closest('form');
      var targetPanel = target.closest('[data-pages-panel]');
      var context = {
        target: target,
        form: form,
        module: String(target.getAttribute('data-ai-agent-module') || '').trim(),
        entity: String(target.getAttribute('data-ai-agent-entity') || '').trim(),
        entity_id: String(target.getAttribute('data-ai-agent-entity-id') || '').trim(),
        source_id: '',
        target_locale: String(targetPanel && targetPanel.getAttribute('data-pages-panel') || '').trim(),
        scope: String(target.getAttribute('data-ai-agent-scope') || '').trim() || 'field',
        block: String(target.getAttribute('data-ai-agent-block') || '').trim(),
        block_label: String(target.getAttribute('data-ai-agent-block-label') || '').trim(),
        field: String(target.getAttribute('data-ai-agent-field') || '').trim(),
        label: String(target.getAttribute('data-ai-agent-label') || '').trim(),
        field_kind: String(target.getAttribute('data-ai-agent-field-kind') || 'text').trim(),
        locale: '',
        source_locale: '',
        current: {},
        source: {},
        has_excerpt: String(target.getAttribute('data-ai-agent-module') || '').trim() === 'posts',
        selected_category_ids: [],
        selected_categories: [],
        available_categories: []
      };

      return hydrateContext(context);
    }

    function captureSnapshot(context) {
      hydrateContext(context);
      return cloneValues(context.current);
    }

    function setFieldValue(context, key, value) {
      if (!context) {
        return;
      }

      if (context.module === 'posts' && key === 'categories') {
        var selected = Array.isArray(value) ? value.map(function(item) {
          return String(item || '').trim();
        }).filter(function(item) {
          return item !== '';
        }) : [];
        var fields = context.form ? context.form.querySelectorAll('input[name="categories[]"]') : [];
        Array.prototype.forEach.call(fields, function(field) {
          if (!(field instanceof HTMLInputElement)) {
            return;
          }

          field.checked = selected.indexOf(String(field.value || '').trim()) !== -1;
          field.dispatchEvent(new Event('change', { bubbles: true }));
        });
        return;
      }

      var field = getFieldNode(context, key);
      if (!field) {
        return;
      }

      if (context.module === 'pages' && key === 'content') {
        setTextareaValue(field, value, '__pageSunEditorHandle');
        return;
      }

      if (context.module === 'posts' && key === 'content') {
        setTextareaValue(field, value, '__postSunEditorHandle');
        return;
      }

      if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
        field.value = String(value || '');
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }

    function restoreSnapshot(silent) {
      if (!state.context || !state.snapshot) {
        return;
      }

      Object.keys(state.snapshot).forEach(function(key) {
        setFieldValue(state.context, key, state.snapshot[key]);
      });

      hydrateContext(state.context);
      state.liveDirty = false;
      state.selectedVariant = -1;
      state.currentProposalType = '';
      state.currentProposal = null;
      updateActionButtons(false);
      renderCurrentWorkspace();
      if (!silent) {
        showToast(i18n.restored || '', 'warning');
      }
    }

    function commitDraft() {
      if (!state.context) {
        return;
      }

      hydrateContext(state.context);
      state.snapshot = captureSnapshot(state.context);
      state.liveDirty = false;
      state.selectedVariant = -1;
      updateActionButtons(false);
      renderCurrentWorkspace();
      showToast(i18n.applied || '', 'success');
    }

    function appendMessage(role, text, contentNode) {
      var row = document.createElement('div');
      row.className = 'ai-agent-message is-' + role;

      var bubble = document.createElement('div');
      bubble.className = 'ai-agent-bubble';
      setBubbleContent(bubble, text, contentNode || null);
      row.appendChild(bubble);
      thread.appendChild(row);
      thread.scrollTop = thread.scrollHeight;
      return bubble;
    }

    function setBubbleContent(bubble, text, contentNode) {
      if (!(bubble instanceof HTMLElement)) {
        return;
      }

      bubble.innerHTML = '';

      var textValue = String(text || '').trim();
      if (textValue !== '') {
        var body = document.createElement('div');
        body.className = 'ai-agent-bubble-body';
        body.textContent = textValue;
        bubble.appendChild(body);
      }

      if (contentNode instanceof Node) {
        var attachment = document.createElement('div');
        attachment.className = 'ai-agent-bubble-attachment';
        attachment.appendChild(contentNode);
        bubble.appendChild(attachment);
      }
    }

    function clearThread() {
      thread.innerHTML = '';
    }

    function clearWorkspace() {
      workspaceBody.innerHTML = '<p class="ai-agent-workspace-empty">' + escapeHtml(i18n.workspaceEmpty || '') + '</p>';
      workspaceMeta.textContent = String(i18n.workspaceMetaCurrent || '');
      updateActionButtons(false);
    }

    function resolveActionLabel(action, context) {
      var subjectLabel = getAssistantSubjectLabel(context);
      var keyMap = {
        field_fill: i18n.actionFieldFill,
        field_improve: i18n.actionFieldImprove,
        field_translate: i18n.actionFieldTranslate,
        block_generate: i18n.actionBlockGenerate,
        block_improve: i18n.actionBlockImprove,
        block_proofread: i18n.actionBlockProofread,
        block_translate: i18n.actionBlockTranslate,
        block_summary: i18n.actionBlockSummary,
        seo_generate: i18n.actionSeoGenerate
      };

      var contextualKeyMap = {
        field_fill: i18n.actionFieldFillContext,
        field_improve: i18n.actionFieldImproveContext,
        field_translate: i18n.actionFieldTranslateContext,
        block_generate: i18n.actionBlockGenerateContext,
        block_improve: i18n.actionBlockImproveContext,
        block_proofread: i18n.actionBlockProofreadContext,
        block_translate: i18n.actionBlockTranslateContext,
        block_summary: i18n.actionBlockSummaryContext,
        seo_generate: i18n.actionSeoGenerateContext
      };

      var contextual = template(String(contextualKeyMap[action] || '').trim(), {
        subject: subjectLabel
      }).trim();

      if (contextual !== '') {
        return contextual;
      }

      return String(keyMap[action] || action || '').trim();
    }

    function getDefaultActions(context) {
      if (!context) {
        return [];
      }

      if (context.block === 'seo') {
        return ['seo_generate'];
      }

      if (context.scope === 'field' && context.field_kind !== 'richtext') {
        var fieldActions = ['field_fill', 'field_improve'];
        if (context.locale !== '' && context.source_locale !== '' && context.locale !== context.source_locale) {
          fieldActions.push('field_translate');
        }
        return fieldActions;
      }

      var contentActions = [];
      if (String((context.current && context.current.content) || '').trim() === '') {
        contentActions.push('block_generate');
      } else {
        contentActions.push('block_improve', 'block_proofread', 'block_summary');
      }
      if (context.locale !== '' && context.source_locale !== '' && context.locale !== context.source_locale) {
        contentActions.push('block_translate');
      }
      if (contentActions.indexOf('block_generate') === -1) {
        contentActions.unshift('block_generate');
      }

      return contentActions;
    }

    function getEntityTitleFromContext(context) {
      if (!context) {
        return '';
      }

      var currentTitle = String((context.current && context.current.title) || '').replace(/\s+/g, ' ').trim();
      if (currentTitle !== '') {
        return currentTitle;
      }

      return String((context.source && context.source.title) || '').replace(/\s+/g, ' ').trim();
    }

    function getAssistantEntityTitle() {
      return getEntityTitleFromContext(state.context);
    }

    function isCreationContextValue(context) {
      if (!context || !(context.form instanceof HTMLFormElement)) {
        return false;
      }

      var formState = String(context.form.getAttribute('data-tour-state') || '').trim();
      if (formState === 'edit') {
        return false;
      }

      if (formState === 'create') {
        return true;
      }

      return String(context.entity_id || '').trim() === '';
    }

    function isCreationContext() {
      return isCreationContextValue(state.context);
    }

    function getEntityDescriptorFromContext(context) {
      if (!context) {
        return '';
      }

      var entityTitle = getEntityTitleFromContext(context);
      var isCreate = isCreationContextValue(context);

      if (context.module === 'posts') {
        if (entityTitle !== '' && !isCreate) {
          return template(i18n.entityPostExisting || '', { title: entityTitle }).trim();
        }

        if (!isCreate) {
          return String(i18n.entityPostCurrent || '').trim();
        }

        return String(i18n.entityPostNew || '').trim();
      }

      if (entityTitle !== '' && !isCreate) {
        return template(i18n.entityPageExisting || '', { title: entityTitle }).trim();
      }

      if (!isCreate) {
        return String(i18n.entityPageCurrent || '').trim();
      }

      return String(i18n.entityPageNew || '').trim();
    }

    function getAssistantEntityDescriptor() {
      return getEntityDescriptorFromContext(state.context);
    }

    function readNavigationHistory() {
      try {
        var raw = window.sessionStorage ? window.sessionStorage.getItem(navigationSessionKey) : '';
        if (!raw) {
          return [];
        }

        var parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed.filter(function(item) {
          return item && typeof item === 'object';
        }) : [];
      } catch (error) {
        return [];
      }
    }

    function writeNavigationHistory(entries) {
      try {
        if (!window.sessionStorage) {
          return;
        }

        window.sessionStorage.setItem(
          navigationSessionKey,
          JSON.stringify((Array.isArray(entries) ? entries : []).slice(0, navigationSessionLimit))
        );
      } catch (error) {
        return;
      }
    }

    function buildNavigationEntry(context) {
      if (!context) {
        return null;
      }

      var module = String(context.module || '').trim() || 'unknown';
      var locale = String(context.target_locale || context.locale || '').trim();
      var entityId = String(context.entity_id || '').trim();
      var sourceId = String(context.source_id || '').trim();
      var title = getEntityTitleFromContext(context);
      var isCreate = isCreationContextValue(context);
      var key = '';

      if (!isCreate && entityId !== '') {
        key = module + ':id:' + entityId;
      } else if (!isCreate && sourceId !== '') {
        key = module + ':source:' + sourceId + ':' + locale;
      } else if (title !== '') {
        key = module + ':title:' + title.toLowerCase() + ':' + locale;
      } else {
        key = module + ':path:' + String(window.location.pathname || '') + ':' + locale;
      }

      return {
        key: key,
        visit_token: pageVisitToken,
        module: module,
        locale: locale,
        descriptor: getEntityDescriptorFromContext(context),
        title: title,
        is_create: isCreate
      };
    }

    function captureNavigationState(context) {
      var currentEntry = buildNavigationEntry(context);
      var history = readNavigationHistory();

      if (!currentEntry) {
        return {
          current: null,
          previous: null,
          seen_before: false
        };
      }

      return {
        current: currentEntry,
        previous: history.find(function(item) {
          return item.key !== currentEntry.key && item.visit_token !== pageVisitToken;
        }) || null,
        seen_before: history.some(function(item) {
          return item.key === currentEntry.key && item.visit_token !== pageVisitToken;
        })
      };
    }

    function rememberContextNavigation(context) {
      var entry = buildNavigationEntry(context);
      if (!entry) {
        return;
      }

      var history = readNavigationHistory().filter(function(item) {
        return item.key !== entry.key;
      });

      history.unshift(entry);
      writeNavigationHistory(history);
    }

    function getAssistantSubjectLabel(context) {
      var activeContext = context || state.context;
      if (!activeContext) {
        return '';
      }

      if (activeContext.scope === 'field' && activeContext.field === 'excerpt') {
        return lowerFirst(String(i18n.fieldSubjectExcerpt || activeContext.label || '').trim());
      }

      if (activeContext.scope === 'field' && activeContext.label !== '') {
        return lowerFirst(activeContext.label);
      }

      if (activeContext.block_label !== '') {
        return lowerFirst(activeContext.block_label);
      }

      return lowerFirst(String(activeContext.block || '').trim());
    }

    function hasSeenGreetingInSession() {
      try {
        return !!(window.sessionStorage && window.sessionStorage.getItem(greetingSessionKey) === '1');
      } catch (error) {
        return false;
      }
    }

    function markGreetingSeenInSession() {
      try {
        if (window.sessionStorage) {
          window.sessionStorage.setItem(greetingSessionKey, '1');
        }
      } catch (error) {
        return;
      }
    }

    function consumeGreetingTurn() {
      try {
        if (!window.sessionStorage) {
          return 0;
        }

        var current = parseInt(String(window.sessionStorage.getItem(greetingTurnSessionKey) || '0'), 10);
        if (!isFinite(current) || current < 0) {
          current = 0;
        }

        window.sessionStorage.setItem(greetingTurnSessionKey, String(current + 1));
        return current;
      } catch (error) {
        return 0;
      }
    }

    function readGreetingEntitiesForVisit() {
      try {
        if (!window.sessionStorage) {
          return {
            visit_token: '',
            keys: []
          };
        }

        var raw = window.sessionStorage.getItem(greetingEntitySessionKey);
        if (!raw) {
          return {
            visit_token: '',
            keys: []
          };
        }

        var parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object') {
          return {
            visit_token: '',
            keys: []
          };
        }

        return {
          visit_token: String(parsed.visit_token || ''),
          keys: Array.isArray(parsed.keys) ? parsed.keys.map(function(item) {
            return String(item || '').trim();
          }).filter(function(item) {
            return item !== '';
          }) : []
        };
      } catch (error) {
        return {
          visit_token: '',
          keys: []
        };
      }
    }

    function hasGreetedEntityInCurrentVisit(entry) {
      if (!entry || !entry.key) {
        return false;
      }

      var stateForVisit = readGreetingEntitiesForVisit();
      if (stateForVisit.visit_token !== pageVisitToken) {
        return false;
      }

      return stateForVisit.keys.indexOf(String(entry.key || '').trim()) !== -1;
    }

    function markEntityGreetedInCurrentVisit(entry) {
      if (!entry || !entry.key) {
        return;
      }

      try {
        if (!window.sessionStorage) {
          return;
        }

        var stateForVisit = readGreetingEntitiesForVisit();
        var keys = stateForVisit.visit_token === pageVisitToken ? stateForVisit.keys.slice() : [];
        var key = String(entry.key || '').trim();
        if (key === '') {
          return;
        }

        if (keys.indexOf(key) === -1) {
          keys.push(key);
        }

        window.sessionStorage.setItem(greetingEntitySessionKey, JSON.stringify({
          visit_token: pageVisitToken,
          keys: keys
        }));
      } catch (error) {
        return;
      }
    }

    function renderGreetingVariant(baseKey, replacements, seed) {
      var keys = [baseKey, baseKey + 'Alt', baseKey + 'Alt2'];
      var variants = [];

      keys.forEach(function(key) {
        var candidate = String(i18n[key] || '').trim();
        if (candidate !== '') {
          variants.push(candidate);
        }
      });

      if (variants.length === 0) {
        return '';
      }

      var index = 0;
      if (variants.length > 1) {
        index = Math.abs(Number(seed) || 0) % variants.length;
      }

      return template(variants[index], replacements || {}).trim();
    }

    function getAssistantProposalMessage(context) {
      var activeContext = context || state.context;
      if (!activeContext) {
        return String(i18n.greetingProposalGeneric || '').trim();
      }

      var subjectLabel = getAssistantSubjectLabel(activeContext);

      if (activeContext.scope === 'field') {
        if (activeContext.field === 'title') {
          return String(i18n.greetingProposalFieldTitle || '').trim();
        }

        if (activeContext.field === 'excerpt') {
          return String(i18n.greetingProposalFieldExcerpt || '').trim();
        }

        if (activeContext.field === 'content') {
          return String(i18n.greetingProposalFieldContent || '').trim();
        }

        if (activeContext.field === 'slug') {
          return String(i18n.greetingProposalFieldSlug || '').trim();
        }

        if (activeContext.field === 'meta_title') {
          return String(i18n.greetingProposalFieldMetaTitle || '').trim();
        }

        if (activeContext.field === 'meta_description') {
          return String(i18n.greetingProposalFieldMetaDescription || '').trim();
        }

        if (activeContext.field === 'featured_image') {
          return String(i18n.greetingProposalFieldFeaturedImage || '').trim();
        }

        return template(i18n.greetingProposalFieldDefault || '', {
          label: subjectLabel
        }).trim();
      }

      if (activeContext.block === 'seo') {
        return String(i18n.greetingProposalBlockSeo || '').trim();
      }

      if (activeContext.block === 'content') {
        return String(i18n.greetingProposalBlockContent || '').trim();
      }

      return template(i18n.greetingProposalBlockDefault || '', {
        label: subjectLabel
      }).trim();
    }

    function getAssistantQuestionMessage(context, navigation, greetingTurn, entityAlreadyIntroduced) {
      var activeContext = context || state.context;
      if (!activeContext) {
        return renderGreetingVariant('greetingQuestionGeneric', {}, greetingTurn);
      }

      var subjectLabel = getAssistantSubjectLabel(activeContext);
      var hasField = activeContext.scope === 'field' && subjectLabel !== '';
      var hasLabel = subjectLabel !== '';
      var isReturn = !!(navigation && navigation.seen_before) || !!entityAlreadyIntroduced;
      var isCreate = isCreationContextValue(activeContext);
      var currentEntity = String((navigation && navigation.current && navigation.current.descriptor) || '').trim();
      var seed = greetingTurn + subjectLabel.length + currentEntity.length;

      if (hasField) {
        if (isReturn) {
          return renderGreetingVariant('greetingQuestionFieldReturn', {
            label: subjectLabel
          }, seed);
        }

        if (isCreate) {
          return renderGreetingVariant('greetingQuestionFieldNew', {
            label: subjectLabel
          }, seed);
        }

        return renderGreetingVariant('greetingQuestionField', {
          label: subjectLabel
        }, seed);
      }

      if (hasLabel) {
        if (isReturn) {
          return renderGreetingVariant('greetingQuestionBlockReturn', {
            label: subjectLabel
          }, seed);
        }

        if (isCreate) {
          return renderGreetingVariant('greetingQuestionBlockNew', {
            label: subjectLabel
          }, seed);
        }

        return renderGreetingVariant('greetingQuestionBlock', {
          label: subjectLabel
        }, seed);
      }

      return renderGreetingVariant('greetingQuestionGeneric', {}, seed);
    }

    function getAssistantGreetingMessage(context, navigation, options) {
      var activeContext = context || state.context;
      var config = options || {};
      if (!activeContext) {
        return '';
      }

      var greetingName = String(currentUser.greeting_name || currentUser.display_name || '').trim();
      var isFirstGreeting = !hasSeenGreetingInSession();
      var greetingTurn = typeof config.greetingTurn === 'number' ? config.greetingTurn : consumeGreetingTurn();
      var activeNavigation = navigation || state.navigation || captureNavigationState(activeContext);
      var entityAlreadyIntroduced = hasGreetedEntityInCurrentVisit(activeNavigation.current);
      var currentEntity = String((activeNavigation.current && activeNavigation.current.descriptor) || getEntityDescriptorFromContext(activeContext)).trim();
      var parts = [];

      if (isFirstGreeting) {
        var intro = String(i18n.greetingIntro || '').trim();
        if (greetingName !== '') {
          parts.push((template(i18n.greetingHello || '', { name: greetingName }).trim() + ' ' + intro).trim());
        } else if (intro !== '') {
          parts.push(upperFirst(intro));
        }
      }

      if (!entityAlreadyIntroduced && activeNavigation.seen_before && currentEntity !== '') {
        parts.push(template(i18n.greetingStateReturn || '', {
          entity: currentEntity
        }).trim());
      } else if (!entityAlreadyIntroduced && isCreationContextValue(activeContext) && currentEntity !== '') {
        parts.push(template(i18n.greetingStateNew || '', {
          entity: currentEntity
        }).trim());
      } else if (!entityAlreadyIntroduced && currentEntity !== '') {
        parts.push(template(i18n.greetingStateCurrent || '', {
          entity: currentEntity
        }).trim());
      }

      var proposalMessage = getAssistantProposalMessage(activeContext);
      if (proposalMessage !== '') {
        parts.push(proposalMessage);
      }

      var proposalQuestion = renderGreetingVariant('greetingProposalQuestion', {}, greetingTurn + currentEntity.length);
      if (proposalQuestion !== '') {
        parts.push(proposalQuestion);
      } else {
        parts.push(getAssistantQuestionMessage(activeContext, activeNavigation, greetingTurn, entityAlreadyIntroduced));
      }

      if (config.commit !== false) {
        markGreetingSeenInSession();
        markEntityGreetedInCurrentVisit(activeNavigation.current);
      }

      return parts.filter(function(part) {
        return String(part || '').trim() !== '';
      }).join(' ');
    }

    function getAssistantContextLabel() {
      if (!state.context) {
        return '';
      }

      var blockLabel = String(state.context.block_label || state.context.block || '').replace(/\s+/g, ' ').trim();
      var entityTitle = getAssistantEntityTitle();

      if (blockLabel === '') {
        return entityTitle;
      }

      if (entityTitle !== '') {
        if (state.context.module === 'posts') {
          return template(i18n.contextBlockPost || '', {
            block: blockLabel,
            title: entityTitle
          }).trim();
        }

        return template(i18n.contextBlockPage || '', {
          block: blockLabel,
          title: entityTitle
        }).trim();
      }

      return template(i18n.contextBlockGeneric || '', {
        block: blockLabel
      }).trim();
    }

    function resolveActionMeta(action) {
      var keyMap = {
        field_fill: i18n.actionMetaFieldFill,
        field_improve: i18n.actionMetaFieldImprove,
        field_translate: i18n.actionMetaFieldTranslate,
        block_generate: i18n.actionMetaBlockGenerate,
        block_improve: i18n.actionMetaBlockImprove,
        block_proofread: i18n.actionMetaBlockProofread,
        block_translate: i18n.actionMetaBlockTranslate,
        block_summary: i18n.actionMetaBlockSummary,
        seo_generate: i18n.actionMetaSeoGenerate
      };

      return String(keyMap[action] || '').trim();
    }

    function syncSuggestionButtons() {
      Array.prototype.forEach.call(suggestions.querySelectorAll('.ai-agent-suggestion'), function(button) {
        if (!(button instanceof HTMLButtonElement)) {
          return;
        }

        var action = String(button.getAttribute('data-ai-agent-suggestion-action') || '').trim();
        var isSelected = action !== '' && action === state.selectedSuggestionAction;
        button.classList.toggle('is-selected', isSelected);
        button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      });
    }

    function clearSuggestionSelection(options) {
      var preserveInput = !!(options && options.preserveInput);
      if (!preserveInput && input.value === state.selectedSuggestionLabel) {
        input.value = '';
      }

      state.selectedSuggestionAction = '';
      state.selectedSuggestionLabel = '';
      syncSuggestionButtons();
    }

    function selectSuggestion(action, options) {
      var actionValue = String(action || '').trim();
      if (actionValue === '') {
        clearSuggestionSelection({ preserveInput: true });
        return;
      }

      if (state.selectedSuggestionAction === actionValue) {
        clearSuggestionSelection();
        input.focus();
        return;
      }

      state.selectedSuggestionAction = actionValue;
      state.selectedSuggestionLabel = resolveActionLabel(actionValue);

      if (!options || options.populateInput !== false) {
        input.value = state.selectedSuggestionLabel;
      }

      syncSuggestionButtons();
      input.focus();
      if (typeof input.setSelectionRange === 'function') {
        input.setSelectionRange(input.value.length, input.value.length);
      }
    }

    function setSuggestions(actions) {
      suggestions.innerHTML = '';
      var normalizedActions = Array.isArray(actions) ? actions : [];
      suggestions.hidden = normalizedActions.length === 0;
      var hasSelectedAction = false;

      normalizedActions.forEach(function(action) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ai-agent-suggestion';
        button.setAttribute('data-ai-agent-suggestion-action', String(action || '').trim());
        button.setAttribute('aria-pressed', 'false');

        var marker = document.createElement('span');
        marker.className = 'ai-agent-suggestion-marker';
        marker.setAttribute('aria-hidden', 'true');
        button.appendChild(marker);

        var body = document.createElement('span');
        body.className = 'ai-agent-suggestion-body';

        var label = document.createElement('span');
        label.className = 'ai-agent-suggestion-label';
        label.textContent = resolveActionLabel(action);
        body.appendChild(label);

        var metaText = resolveActionMeta(String(action || '').trim());
        if (metaText !== '') {
          var meta = document.createElement('span');
          meta.className = 'ai-agent-suggestion-meta';
          meta.textContent = metaText;
          body.appendChild(meta);
        }

        button.appendChild(body);
        button.addEventListener('click', function() {
          selectSuggestion(action, { populateInput: true });
        });
        suggestions.appendChild(button);

        if (String(action || '').trim() === state.selectedSuggestionAction) {
          hasSelectedAction = true;
        }
      });

      if (!hasSelectedAction) {
        state.selectedSuggestionAction = '';
        state.selectedSuggestionLabel = '';
      }

      syncSuggestionButtons();
    }

    function renderContextLabel() {
      if (!state.context) {
        contextLabel.textContent = String(i18n.contextWaiting || '');
        return;
      }

      if (state.context.scope === 'field' && state.context.label !== '') {
        contextLabel.textContent = state.context.label + ' · ' + state.context.block_label;
        return;
      }

      contextLabel.textContent = state.context.block_label;
    }

    function resolveCurrentTarget() {
      var active = document.activeElement;
      if (active instanceof Element) {
        var activeTarget = active.closest('[data-ai-agent-target]');
        if (activeTarget instanceof HTMLElement) {
          return activeTarget;
        }
      }

      if (state.currentTarget instanceof HTMLElement && document.body.contains(state.currentTarget)) {
        return state.currentTarget;
      }

      var fallback = document.querySelector('[data-ai-agent-target]');
      return fallback instanceof HTMLElement ? fallback : null;
    }

    function setCurrentTarget(target) {
      if (!(target instanceof HTMLElement)) {
        return;
      }

      state.currentTarget = target;
      if (state.miniOpen && !state.floatingDragged) {
        positionFloatingForTarget(target);
      }

      if (state.miniOpen) {
        renderFloatingCard();
      }
    }

    function getContextStateKey(context) {
      if (!context) {
        return '';
      }

      return [
        String(context.module || '').trim(),
        String(context.entity_id || '').trim(),
        String(context.locale || '').trim(),
        String(context.block || '').trim(),
        String(context.field || '').trim()
      ].join(':');
    }

    function clearFloatingHideTimer() {
      if (floatingHideTimer) {
        window.clearTimeout(floatingHideTimer);
        floatingHideTimer = 0;
      }
    }

    function clearFloatingShowTimer() {
      if (floatingShowTimer) {
        window.clearTimeout(floatingShowTimer);
        floatingShowTimer = 0;
      }
    }

    function scheduleFloatingShow(target, delay) {
      clearFloatingShowTimer();
      clearFloatingHideTimer();

      if (!(target instanceof HTMLElement) || state.open) {
        return;
      }

      floatingShowTimer = window.setTimeout(function() {
        floatingShowTimer = 0;
        showFloatingForTarget(target);
      }, Math.max(0, Number(delay) || 0));
    }

    function scheduleFloatingHide() {
      clearFloatingShowTimer();
      clearFloatingHideTimer();

      if (state.open || floatingDragState) {
        return;
      }

      floatingHideTimer = window.setTimeout(function() {
        hideFloating();
      }, floatingHideDelayMs);
    }

    function renderFloatingCard() {
      var target = resolveCurrentTarget();
      var context = target ? getContextFromTarget(target) : null;
      var contextKey = getContextStateKey(context);
      var actions = context ? getDefaultActions(context).slice(0, 3) : [];

      if (contextKey === '') {
        floatingContext.textContent = String(i18n.floatingContextEmpty || i18n.contextWaiting || '').trim();
        floatingActions.innerHTML = '';
        return;
      }

      if (contextKey !== state.floatingContextKey) {
        var navigation = captureNavigationState(context);
        rememberContextNavigation(context);
        state.floatingContextKey = contextKey;
        state.floatingMessage = getAssistantGreetingMessage(context, navigation, { commit: true });
      }

      floatingContext.textContent = String(state.floatingMessage || i18n.floatingContextEmpty || i18n.contextWaiting || '').trim();
      floatingActions.innerHTML = '';

      actions.forEach(function(action) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ai-agent-floating-action';
        button.setAttribute('data-ai-agent-floating-action', String(action || '').trim());

        var label = document.createElement('span');
        label.className = 'ai-agent-floating-action-label';
        label.textContent = resolveActionLabel(action, context);
        button.appendChild(label);

        button.addEventListener('click', function(event) {
          event.preventDefault();
          event.stopPropagation();
          openDrawerForTarget(target, {
            appendGreeting: false,
            introMessage: state.floatingMessage,
            prefillAction: action
          });
        });

        floatingActions.appendChild(button);
      });
    }

    function setMiniOpen(isOpen) {
      state.miniOpen = !!isOpen;
      if (state.miniOpen) {
        floating.hidden = false;
        renderFloatingCard();
        floatingCard.hidden = false;
        scheduleFloatingPlacement();
        return;
      }

      floatingCard.hidden = true;
      floating.classList.remove('is-open-right');
      floating.classList.remove('is-above');
    }

    function clampFloatingPosition(x, y) {
      var margin = 16;
      var width = floatingButton.offsetWidth || 96;
      var height = floatingButton.offsetHeight || 56;

      return {
        x: Math.min(Math.max(margin, x), Math.max(margin, window.innerWidth - width - margin)),
        y: Math.min(Math.max(margin, y), Math.max(margin, window.innerHeight - height - margin))
      };
    }

    function applyFloatingPosition(x, y) {
      var clamped = clampFloatingPosition(x, y);
      floating.style.left = clamped.x + 'px';
      floating.style.top = clamped.y + 'px';

      if (state.miniOpen) {
        scheduleFloatingPlacement();
      }
    }

    function updateFloatingPlacement() {
      floatingPlacementFrame = 0;

      if (floating.hidden || !state.miniOpen || floatingCard.hidden) {
        return;
      }

      var gap = 12;
      var margin = 16;
      var buttonRect = floatingButton.getBoundingClientRect();
      var cardWidth = floatingCard.offsetWidth || Math.min(320, Math.max(0, window.innerWidth - (margin * 2)));
      var cardHeight = floatingCard.offsetHeight || 0;
      var spaceRight = window.innerWidth - buttonRect.left - margin;
      var spaceLeft = buttonRect.right - margin;
      var spaceAbove = buttonRect.top - margin;
      var spaceBelow = window.innerHeight - buttonRect.bottom - margin;
      var shouldOpenRight = spaceRight >= cardWidth || spaceRight >= spaceLeft;
      var shouldOpenAbove = spaceBelow < (cardHeight + gap) && spaceAbove > spaceBelow;

      floating.classList.toggle('is-open-right', shouldOpenRight);
      floating.classList.toggle('is-above', shouldOpenAbove);
    }

    function scheduleFloatingPlacement() {
      if (floatingPlacementFrame) {
        window.cancelAnimationFrame(floatingPlacementFrame);
      }

      floatingPlacementFrame = window.requestAnimationFrame(updateFloatingPlacement);
    }

    function positionFloatingForTarget(target) {
      if (!(target instanceof HTMLElement)) {
        return;
      }

      var rect = target.getBoundingClientRect();
      var width = floatingButton.offsetWidth || 96;
      var height = floatingButton.offsetHeight || 96;
      var gap = 12;
      var left = rect.right - Math.round(width * 0.35);
      var top = rect.top - height - gap;

      if (top < 16) {
        top = rect.bottom + gap;
      }

      applyFloatingPosition(left, top);
    }

    function showFloatingForTarget(target) {
      if (!(target instanceof HTMLElement) || state.open) {
        return;
      }

      clearFloatingHideTimer();
      state.floatingDragged = false;
      setCurrentTarget(target);
      floating.hidden = false;
      positionFloatingForTarget(target);
      setMiniOpen(true);
    }

    function hideFloating() {
      clearFloatingShowTimer();
      clearFloatingHideTimer();
      state.miniOpen = false;
      state.floatingDragged = false;
      state.floatingContextKey = '';
      state.floatingMessage = '';
      floating.hidden = true;
      floatingCard.hidden = true;
      floating.classList.remove('is-open-right');
      floating.classList.remove('is-above');
      floating.classList.remove('is-dragging');
    }

    function refreshFloatingAvailability() {
      var initialTarget = document.querySelector('[data-ai-agent-target]');
      var hasTargets = initialTarget instanceof HTMLElement;

      if (hasTargets) {
        state.currentTarget = initialTarget;
        floating.hidden = true;
        floatingCard.hidden = true;
        return;
      }

      state.currentTarget = null;
      hideFloating();
      closeDrawer();
    }

    function getFieldLabel(context, key) {
      if (context && context.scope === 'field' && context.field === key && context.label !== '') {
        return context.label;
      }

      var field = getFieldNode(context, key);
      var fieldId = field && field.id ? field.id : '';
      if (fieldId !== '') {
        var label = document.querySelector('label[for="' + escapeSelector(fieldId) + '"]');
        if (label) {
          return String(label.textContent || '').replace(/\*+/g, '').trim();
        }
      }

      return key.replace(/_/g, ' ');
    }

    function getPreviewKeys(context, proposalType) {
      if (!context) {
        return [];
      }

      if (proposalType === 'seo_block') {
        return ['meta_title', 'meta_description'];
      }

      if (proposalType === 'summary') {
        return context.module === 'posts' ? ['excerpt'] : [];
      }

      if (proposalType === 'content_block') {
        return context.module === 'posts'
          ? ['title', 'slug', 'excerpt', 'categories', 'featured_image', 'content']
          : ['title', 'slug', 'content'];
      }

      if (context.scope === 'field' && context.field !== '') {
        return [context.field];
      }

      return context.block === 'seo'
        ? ['meta_title', 'meta_description']
        : (context.module === 'posts' ? ['title', 'slug', 'excerpt', 'categories', 'featured_image', 'content'] : ['title', 'slug', 'content']);
    }

    function buildPreviewStack(context, values, keys, draft) {
      var stack = document.createElement('div');
      stack.className = 'ai-agent-preview-stack';

      keys.forEach(function(key) {
        var card = document.createElement('div');
        card.className = 'ai-agent-preview-card' + (draft ? ' is-draft' : '');

        var label = document.createElement('span');
        label.className = 'ai-agent-preview-label';
        label.textContent = getFieldLabel(context, key);
        card.appendChild(label);

        var valueNode = document.createElement('p');
        valueNode.className = 'ai-agent-preview-value' + (key === 'content' ? ' is-richtext' : '');

        var sourceValue = values ? values[key] : '';
        var rawValue = Array.isArray(sourceValue) ? sourceValue.join(', ') : String(sourceValue || '').trim();
        var displayValue = key === 'content' ? plainTextFromHtml(rawValue) : rawValue;
        displayValue = truncateText(displayValue, key === 'content' ? 900 : 280);
        valueNode.textContent = displayValue !== '' ? displayValue : String(i18n.previewEmptyValue || '');
        card.appendChild(valueNode);

        stack.appendChild(card);
      });

      return stack;
    }

    function activateInlinePreview(wrapper, selected) {
      if (!(wrapper instanceof HTMLElement)) {
        return;
      }

      Array.prototype.forEach.call(wrapper.querySelectorAll('.ai-agent-inline-preview'), function(node) {
        node.classList.toggle('is-active', node === selected);
      });
    }

    function bindClickablePreview(node, onActivate) {
      if (!(node instanceof HTMLElement) || typeof onActivate !== 'function') {
        return;
      }

      node.tabIndex = 0;
      node.setAttribute('role', 'button');
      node.addEventListener('click', function() {
        onActivate();
      });
      node.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          onActivate();
        }
      });
    }

    function buildInlineFieldVariants(proposal) {
      var variants = Array.isArray(proposal.variants) ? proposal.variants : [];
      var fieldKey = String(proposal.field_key || '').trim();
      if (variants.length === 0 || fieldKey === '') {
        return null;
      }

      var wrapper = document.createElement('div');
      wrapper.className = 'ai-agent-inline-panel';

      var list = document.createElement('div');
      list.className = 'ai-agent-variant-list ai-agent-inline-variant-list';
      var actionBar = buildInlineActionBar({
        canApply: false,
        onApply: function() {
          commitDraft();
        },
        onRestore: function() {
          restoreSnapshot();
          state.selectedVariant = -1;
          Array.prototype.forEach.call(list.querySelectorAll('.ai-agent-inline-variant'), function(item) {
            item.classList.remove('is-selected');
          });
          setInlineActionState(actionBar, false);
        }
      });

      variants.forEach(function(variant, index) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ai-agent-variant ai-agent-inline-variant' + (state.selectedVariant === index ? ' is-selected' : '');

        var label = document.createElement('span');
        label.className = 'ai-agent-variant-label';
        label.textContent = template(i18n.variantOption || '', {
          number: String(index + 1)
        });
        button.appendChild(label);

        var value = document.createElement('span');
        value.className = 'ai-agent-preview-value';
        value.textContent = String(variant || '').trim();
        button.appendChild(value);

        button.addEventListener('click', function() {
          state.selectedVariant = index;
          Array.prototype.forEach.call(list.querySelectorAll('.ai-agent-inline-variant'), function(item, itemIndex) {
            item.classList.toggle('is-selected', itemIndex === index);
          });
          applyDraftValues((function() {
            var payload = {};
            payload[fieldKey] = String(variant || '');
            return payload;
          }()), [fieldKey]);
          renderVariants(proposal);
          setInlineActionState(actionBar, true);
        });

        list.appendChild(button);
      });

      wrapper.appendChild(list);
      wrapper.appendChild(actionBar);
      return wrapper;
    }

    function buildInlineActionBar(options) {
      var config = options || {};
      var bar = document.createElement('div');
      bar.className = 'ai-agent-inline-actions';

      var restore = document.createElement('button');
      restore.type = 'button';
      restore.className = 'btn btn-secondary ai-agent-inline-action';
      restore.textContent = String(i18n.restore || '');
      restore.addEventListener('click', function() {
        if (typeof config.onRestore === 'function') {
          config.onRestore();
        }
      });

      var apply = document.createElement('button');
      apply.type = 'button';
      apply.className = 'btn btn-primary ai-agent-inline-action';
      apply.textContent = String(i18n.apply || '');
      apply.addEventListener('click', function() {
        if (typeof config.onApply === 'function') {
          config.onApply();
        }
      });

      bar.appendChild(restore);
      bar.appendChild(apply);
      setInlineActionState(bar, !!config.canApply);
      return bar;
    }

    function setInlineActionState(bar, enabled) {
      if (!(bar instanceof HTMLElement)) {
        return;
      }

      Array.prototype.forEach.call(bar.querySelectorAll('.ai-agent-inline-action'), function(button) {
        if (!(button instanceof HTMLButtonElement)) {
          return;
        }
        button.disabled = !enabled;
      });
    }

    function buildInlineDraftPreview(context, values, keys, noteText) {
      var previewKeys = Array.isArray(keys) ? keys.filter(function(key) {
        return Object.prototype.hasOwnProperty.call(values || {}, key);
      }) : [];
      if (previewKeys.length === 0) {
        return null;
      }

      var wrapper = document.createElement('div');
      wrapper.className = 'ai-agent-inline-panel';

      var preview = document.createElement('div');
      preview.className = 'ai-agent-inline-preview is-active';
      preview.appendChild(buildPreviewStack(context, values, previewKeys, true));
      bindClickablePreview(preview, function() {
        activateInlinePreview(wrapper, preview);
        applyDraftValues(values, previewKeys);
        renderDraftWorkspace(previewKeys, values, String(i18n.workspaceMetaDraft || ''), noteText || '');
      });
      wrapper.appendChild(preview);

      if (String(noteText || '').trim() !== '') {
        var note = document.createElement('p');
        note.className = 'ai-agent-preview-note';
        note.textContent = String(noteText || '').trim();
        wrapper.appendChild(note);
      }

      wrapper.appendChild(buildInlineActionBar({
        canApply: true,
        onApply: function() {
          commitDraft();
        },
        onRestore: function() {
          restoreSnapshot();
        }
      }));

      return wrapper;
    }

    function buildInlineInfoBlock(proposal) {
      var titleText = String(proposal && proposal.title || '').trim();
      var messageText = String(proposal && proposal.message || '').trim();
      var actionLabel = String(proposal && proposal.action_label || '').trim();
      var actionUrl = String(proposal && proposal.action_url || '').trim();

      if (titleText === '' && messageText === '' && actionLabel === '' && actionUrl === '') {
        return null;
      }

      var wrapper = document.createElement('div');
      wrapper.className = 'ai-agent-inline-panel';

      if (titleText !== '') {
        var card = document.createElement('div');
        card.className = 'ai-agent-preview-card is-draft';

        var title = document.createElement('span');
        title.className = 'ai-agent-preview-label';
        title.textContent = titleText;
        card.appendChild(title);

        if (messageText !== '') {
          var value = document.createElement('p');
          value.className = 'ai-agent-preview-value';
          value.textContent = messageText;
          card.appendChild(value);
        }

        wrapper.appendChild(card);
      } else if (messageText !== '') {
        var note = document.createElement('p');
        note.className = 'ai-agent-preview-note';
        note.textContent = messageText;
        wrapper.appendChild(note);
      }

      if (actionLabel !== '' && actionUrl !== '') {
        var actions = document.createElement('div');
        actions.className = 'ai-agent-inline-actions';

        var link = document.createElement('a');
        link.className = 'btn btn-secondary ai-agent-inline-action';
        link.href = actionUrl;
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = actionLabel;
        actions.appendChild(link);

        wrapper.appendChild(actions);
      }

      return wrapper;
    }

    function buildInlineSummary(summaryText, canApplyToExcerpt) {
      var text = String(summaryText || '').trim();
      var wrapper = document.createElement('div');
      wrapper.className = 'ai-agent-inline-panel';

      var preview = document.createElement('div');
      preview.className = 'ai-agent-inline-preview is-active';

      var card = document.createElement('div');
      card.className = 'ai-agent-preview-card is-draft';

      var label = document.createElement('span');
      label.className = 'ai-agent-preview-label';
      label.textContent = String(i18n.summaryTitle || '');
      card.appendChild(label);

      var value = document.createElement('p');
      value.className = 'ai-agent-preview-value';
      value.textContent = text !== '' ? text : String(i18n.previewEmptyValue || '');
      card.appendChild(value);
      preview.appendChild(card);

      if (canApplyToExcerpt) {
        bindClickablePreview(preview, function() {
          activateInlinePreview(wrapper, preview);
          applyDraftValues({ excerpt: text }, ['excerpt']);
          renderDraftWorkspace(['excerpt'], { excerpt: text }, String(i18n.workspaceMetaDraft || ''), String(i18n.summaryPostsNote || ''));
        });
      }

      wrapper.appendChild(preview);

      var note = document.createElement('p');
      note.className = 'ai-agent-preview-note';
      note.textContent = canApplyToExcerpt ? String(i18n.summaryPostsNote || '') : String(i18n.summaryPagesNote || '');
      wrapper.appendChild(note);

      if (canApplyToExcerpt) {
        wrapper.appendChild(buildInlineActionBar({
          canApply: true,
          onApply: function() {
            commitDraft();
          },
          onRestore: function() {
            restoreSnapshot();
          }
        }));
      }

      return wrapper;
    }

    function renderCurrentWorkspace() {
      if (!state.context) {
        clearWorkspace();
        return;
      }

      hydrateContext(state.context);
      workspaceMeta.textContent = String(i18n.workspaceMetaCurrent || '');
      workspaceBody.innerHTML = '';
      workspaceBody.appendChild(buildPreviewStack(state.context, state.context.current, getPreviewKeys(state.context, ''), false));
      updateActionButtons(false);
    }

    function updateActionButtons(canApply) {
      restoreButton.hidden = !state.liveDirty;
      applyButton.hidden = !canApply;
    }

    function applyDraftValues(values, keys) {
      if (!state.context || !values) {
        return;
      }

      Object.keys(values).forEach(function(key) {
        if (keys.indexOf(key) === -1) {
          return;
        }
        setFieldValue(state.context, key, values[key]);
      });

      hydrateContext(state.context);
      state.liveDirty = true;
      updateActionButtons(true);
    }

    function renderDraftWorkspace(keys, values, metaText, noteText) {
      workspaceMeta.textContent = metaText;
      workspaceBody.innerHTML = '';
      workspaceBody.appendChild(buildPreviewStack(state.context, values, keys, true));

      if (String(noteText || '').trim() !== '') {
        var note = document.createElement('p');
        note.className = 'ai-agent-preview-note';
        note.textContent = noteText;
        workspaceBody.appendChild(note);
      }
    }

    function renderVariants(proposal) {
      var variants = Array.isArray(proposal.variants) ? proposal.variants : [];
      var fieldKey = String(proposal.field_key || '').trim();
      if (variants.length === 0 || fieldKey === '') {
        throw new Error('Missing variants');
      }

      workspaceMeta.textContent = template(i18n.variantsTitle || '', {
        label: proposal.field_label || fieldKey
      });
      workspaceBody.innerHTML = '';

      var list = document.createElement('div');
      list.className = 'ai-agent-variant-list';

      variants.forEach(function(variant, index) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ai-agent-variant' + (state.selectedVariant === index ? ' is-selected' : '');

        var label = document.createElement('span');
        label.className = 'ai-agent-variant-label';
        label.textContent = template(i18n.variantOption || '', {
          number: String(index + 1)
        });
        button.appendChild(label);

        var value = document.createElement('span');
        value.className = 'ai-agent-preview-value';
        value.textContent = String(variant || '').trim();
        button.appendChild(value);

        button.addEventListener('click', function() {
          state.selectedVariant = index;
          renderVariants(proposal);
          applyDraftValues((function() {
            var payload = {};
            payload[fieldKey] = String(variant || '');
            return payload;
          }()), [fieldKey]);
        });

        list.appendChild(button);
      });

      workspaceBody.appendChild(list);

      if (state.selectedVariant >= 0 && variants[state.selectedVariant]) {
        var previewValues = {};
        previewValues[fieldKey] = String(variants[state.selectedVariant] || '');
        workspaceBody.appendChild(buildPreviewStack(state.context, previewValues, [fieldKey], true));
        updateActionButtons(true);
      } else {
        updateActionButtons(false);
      }
    }

    function renderSummary(summary) {
      var text = String(summary || '').trim();
      workspaceMeta.textContent = String(i18n.workspaceMetaSummary || '');
      workspaceBody.innerHTML = '';

      var card = document.createElement('div');
      card.className = 'ai-agent-preview-card is-draft';

      var label = document.createElement('span');
      label.className = 'ai-agent-preview-label';
      label.textContent = String(i18n.summaryTitle || '');
      card.appendChild(label);

      var value = document.createElement('p');
      value.className = 'ai-agent-preview-value';
      value.textContent = text !== '' ? text : String(i18n.previewEmptyValue || '');
      card.appendChild(value);
      workspaceBody.appendChild(card);

      if (state.context && state.context.module === 'posts' && state.context.has_excerpt) {
        applyDraftValues({ excerpt: text }, ['excerpt']);
        workspaceBody.appendChild((function() {
          var note = document.createElement('p');
          note.className = 'ai-agent-preview-note';
          note.textContent = String(i18n.summaryPostsNote || '');
          return note;
        }()));
        renderDraftWorkspace(['excerpt'], { excerpt: text }, String(i18n.workspaceMetaDraft || ''), String(i18n.summaryPostsNote || ''));
        return;
      }

      updateActionButtons(false);

      var pagesNote = document.createElement('p');
      pagesNote.className = 'ai-agent-preview-note';
      pagesNote.textContent = String(i18n.summaryPagesNote || '');
      workspaceBody.appendChild(pagesNote);
    }

    function renderProposal(intent, proposalType, proposal, bubble) {
      state.currentProposalType = String(proposalType || '');
      state.currentProposal = proposal || null;
      state.selectedVariant = -1;

      if (!state.context) {
        return;
      }

      if (proposalType === 'field_variants') {
        setBubbleContent(bubble, getAssistantReply(intent), buildInlineFieldVariants(proposal || {}));
        renderVariants(proposal || {});
        return;
      }

      if (proposalType === 'summary') {
        setBubbleContent(bubble, getAssistantReply(intent), buildInlineSummary(String(proposal && proposal.summary || ''), !!(state.context && state.context.module === 'posts' && state.context.has_excerpt)));
        renderSummary(String(proposal && proposal.summary || ''));
        return;
      }

      if (proposalType === 'info_block') {
        setBubbleContent(
          bubble,
          String(proposal && proposal.reply || '').trim() || getAssistantReply(intent),
          buildInlineInfoBlock(proposal || {})
        );
        renderCurrentWorkspace();
        return;
      }

      if (proposalType === 'content_block' || proposalType === 'seo_block') {
        var values = proposal && proposal.values ? proposal.values : {};
        var keys = getPreviewKeys(state.context, proposalType).filter(function(key) {
          return Object.prototype.hasOwnProperty.call(values, key);
        });
        var noteText = String(proposal && proposal.note || '').trim();

        applyDraftValues(values, keys);
        renderDraftWorkspace(keys, values, String(i18n.workspaceMetaDraft || ''), noteText);
        setBubbleContent(
          bubble,
          String(proposal && proposal.reply || '').trim() || getAssistantReply(intent),
          buildInlineDraftPreview(state.context, values, keys, noteText)
        );
        return;
      }

      setBubbleContent(bubble, getAssistantReply(intent), null);
      renderCurrentWorkspace();
    }

    function buildPayload(message, action) {
      if (!state.context) {
        return null;
      }

      hydrateContext(state.context);

      return {
        context: {
          module: state.context.module,
          entity: state.context.entity,
          entity_id: state.context.entity_id,
          source_id: state.context.source_id,
          scope: state.context.scope,
          block: state.context.block,
          field: state.context.field,
          label: state.context.label,
          field_kind: state.context.field_kind,
          locale: state.context.locale,
          source_locale: state.context.source_locale,
          current: state.context.current,
          source: state.context.source,
          has_excerpt: state.context.has_excerpt,
          selected_category_ids: state.context.selected_category_ids,
          selected_categories: state.context.selected_categories,
          available_categories: state.context.available_categories
        },
        message: String(message || '').trim(),
        action: String(action || '').trim()
      };
    }

    function getAssistantReply(intent) {
      if (!state.context) {
        return '';
      }

      if (intent === 'field_fill' || intent === 'field_improve' || intent === 'field_translate') {
        return template(i18n.replyField || '', {
          label: state.context.label || state.context.field,
          block: state.context.block_label || state.context.block
        });
      }

      if (intent === 'seo_generate') {
        return String(i18n.replySeo || '');
      }

      if (intent === 'block_summary') {
        return String(i18n.replySummary || '');
      }

      return template(i18n.replyContent || '', {
        block: state.context.block_label || state.context.block
      });
    }

    function sendMessage(message, action) {
      if (!state.context) {
        return;
      }

      var actionValue = String(action || '').trim();
      var messageValue = String(message || input.value || '').trim();
      var selectedAction = String(state.selectedSuggestionAction || '').trim();
      var selectedLabel = String(state.selectedSuggestionLabel || '').trim();

      if (actionValue === '' && selectedAction !== '') {
        if (messageValue === '' || messageValue === selectedLabel || messageValue.indexOf(selectedLabel) === 0) {
          actionValue = selectedAction;
        }
      }

      if (messageValue === '' && actionValue === '') {
        showToast(i18n.errorEmpty || '', 'warning');
        return;
      }

      var userText = messageValue !== '' ? messageValue : resolveActionLabel(actionValue);
      appendMessage('user', userText);
      input.value = '';
      clearSuggestionSelection({ preserveInput: true });
      setSuggestions([]);

      var thinkingBubble = appendMessage('assistant', i18n.thinking || '');
      var payload = buildPayload(messageValue, actionValue);
      if (!payload) {
        thinkingBubble.textContent = String(i18n.errorUnavailable || '');
        return;
      }

      fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      })
        .then(function(response) {
          return response.json().catch(function() {
            return {
              success: false,
              message: i18n.errorUnavailable || ''
            };
          });
        })
        .then(function(data) {
          if (!data || data.success !== true) {
            var errorMessage = String(data && data.message || i18n.errorUnavailable || '').trim();
            thinkingBubble.textContent = errorMessage;
            showToast(errorMessage, 'error');
            setSuggestions(getDefaultActions(state.context));
            return;
          }

          renderProposal(String(data.intent || '').trim(), String(data.proposal_type || '').trim(), data.proposal || {}, thinkingBubble);
          setSuggestions(Array.isArray(data.chips) && data.chips.length > 0 ? data.chips : getDefaultActions(state.context));
        })
        .catch(function() {
          var errorMessage = String(i18n.errorUnavailable || '').trim();
          thinkingBubble.textContent = errorMessage;
          showToast(errorMessage, 'error');
          setSuggestions(getDefaultActions(state.context));
        });
    }

    function updateOpenState(isOpen) {
      state.open = !!isOpen;
      drawer.hidden = !isOpen;
      backdrop.hidden = !isOpen;
      document.body.classList.toggle('ai-agent-open', !!isOpen);
    }

    function openDrawerForTarget(target, options) {
      var config = options || {};
      var resolvedTarget = target instanceof HTMLElement ? target : resolveCurrentTarget();
      if (!(resolvedTarget instanceof HTMLElement)) {
        return;
      }

      state.context = getContextFromTarget(resolvedTarget);
      state.currentTarget = resolvedTarget;
      state.navigation = captureNavigationState(state.context);
      rememberContextNavigation(state.context);
      state.snapshot = captureSnapshot(state.context);
      state.liveDirty = false;
      state.selectedVariant = -1;
      state.currentProposalType = '';
      state.currentProposal = null;
      state.selectedSuggestionAction = '';
      state.selectedSuggestionLabel = '';

      clearThread();
      input.value = '';
      renderContextLabel();
      renderCurrentWorkspace();
      setSuggestions(getDefaultActions(state.context));
      if (String(config.introMessage || '').trim() !== '') {
        appendMessage('assistant', String(config.introMessage || '').trim());
      } else if (config.appendGreeting !== false) {
        appendMessage('assistant', getAssistantGreetingMessage());
      }

      updateOpenState(true);
      hideFloating();
      window.setTimeout(function() {
        if (config.prefillAction) {
          selectSuggestion(String(config.prefillAction || '').trim(), { populateInput: true });
          return;
        }

        if (config.autoAction) {
          sendMessage('', String(config.autoAction || '').trim());
          return;
        }
        input.focus();
      }, 60);
    }

    function closeDrawer() {
      updateOpenState(false);
    }

    refreshFloatingAvailability();
    hideFloating();

    var floatingDragState = null;

    Array.prototype.forEach.call(document.querySelectorAll('[data-ai-agent-target]'), function(target) {
      if (!(target instanceof HTMLElement)) {
        return;
      }

      target.addEventListener('mouseenter', function() {
        scheduleFloatingShow(target, floatingHoverDelayMs);
      });

      target.addEventListener('focusin', function() {
        scheduleFloatingShow(target, floatingFocusDelayMs);
      });

      target.addEventListener('mouseleave', function() {
        scheduleFloatingHide();
      });

      target.addEventListener('focusout', function(event) {
        var next = event.relatedTarget;
        if (next instanceof Node && (target.contains(next) || floating.contains(next))) {
          return;
        }

        scheduleFloatingHide();
      });
    });

    floating.addEventListener('mouseenter', function() {
      clearFloatingShowTimer();
      clearFloatingHideTimer();
    });

    floating.addEventListener('mouseleave', function() {
      scheduleFloatingHide();
    });

    floatingButton.addEventListener('pointerdown', function(event) {
      if (event.button !== 0) {
        return;
      }

      clearFloatingShowTimer();
      clearFloatingHideTimer();

      floatingDragState = {
        pointerId: event.pointerId,
        startX: event.clientX,
        startY: event.clientY,
        baseX: floating.offsetLeft,
        baseY: floating.offsetTop,
        moved: false
      };

      floating.classList.add('is-dragging');
      if (typeof floatingButton.setPointerCapture === 'function') {
        floatingButton.setPointerCapture(event.pointerId);
      }
    });

    floatingButton.addEventListener('pointermove', function(event) {
      if (!floatingDragState || event.pointerId !== floatingDragState.pointerId) {
        return;
      }

      var deltaX = event.clientX - floatingDragState.startX;
      var deltaY = event.clientY - floatingDragState.startY;
      if (!floatingDragState.moved && (Math.abs(deltaX) > 4 || Math.abs(deltaY) > 4)) {
        floatingDragState.moved = true;
      }

      if (!floatingDragState.moved) {
        return;
      }

      event.preventDefault();
      state.floatingDragged = true;
      applyFloatingPosition(floatingDragState.baseX + deltaX, floatingDragState.baseY + deltaY);
    });

    function finishFloatingDrag(event) {
      if (!floatingDragState || event.pointerId !== floatingDragState.pointerId) {
        return;
      }

      if (typeof floatingButton.releasePointerCapture === 'function' && floatingButton.hasPointerCapture && floatingButton.hasPointerCapture(event.pointerId)) {
        floatingButton.releasePointerCapture(event.pointerId);
      }

      if (floatingDragState.moved) {
        floatingButton.setAttribute('data-ai-agent-drag-click', '1');
      }

      floating.classList.remove('is-dragging');
      floatingDragState = null;
    }

    floatingButton.addEventListener('pointerup', finishFloatingDrag);
    floatingButton.addEventListener('pointercancel', finishFloatingDrag);

    floatingButton.addEventListener('click', function(event) {
      if (floatingButton.getAttribute('data-ai-agent-drag-click') === '1') {
        floatingButton.removeAttribute('data-ai-agent-drag-click');
        event.preventDefault();
        return;
      }

      event.preventDefault();
      openDrawerForTarget(resolveCurrentTarget(), {
        appendGreeting: false,
        introMessage: state.floatingMessage
      });
    });

    floatingCloseButton.addEventListener('click', function(event) {
      event.preventDefault();
      hideFloating();
    });

    window.addEventListener('resize', function() {
      if (floating.hidden) {
        return;
      }

      if (!state.floatingDragged) {
        var activeTarget = resolveCurrentTarget();
        if (activeTarget instanceof HTMLElement) {
          positionFloatingForTarget(activeTarget);
        }
      } else {
        applyFloatingPosition(floating.offsetLeft, floating.offsetTop);
      }

      if (state.miniOpen) {
        renderFloatingCard();
      }
    });

    sendButton.addEventListener('click', function() {
      sendMessage('', '');
    });

    input.addEventListener('keydown', function(event) {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage('', '');
      }
    });

    restoreButton.addEventListener('click', function() {
      restoreSnapshot();
    });

    applyButton.addEventListener('click', function() {
      commitDraft();
    });

    Array.prototype.forEach.call(closeButtons, function(button) {
      button.addEventListener('click', function(event) {
        event.preventDefault();
        closeDrawer();
      });
    });

    document.addEventListener('keydown', function(event) {
      if (!state.open) {
        return;
      }

      if (event.key === 'Escape') {
        event.preventDefault();
        closeDrawer();
      }
    });

    document.addEventListener('mousedown', function(event) {
      var target = event.target;
      if (!(target instanceof Node)) {
        return;
      }

      if (state.open && drawer.contains(target)) {
        return;
      }

      if (target instanceof Element && target.closest('[data-ai-agent-floating]')) {
        return;
      }

      if (target instanceof Element && target.closest('[data-ai-agent-target]')) {
        return;
      }

      if (state.open) {
        closeDrawer();
      }

      if (state.miniOpen) {
        hideFloating();
      }
    });

    document.addEventListener('pages:locale-changed', function() {
      if (!state.open || !state.context || state.context.module !== 'pages') {
        if (state.miniOpen) {
          state.floatingContextKey = '';
          renderFloatingCard();
        }
        return;
      }

      closeDrawer();
      hideFloating();
    });
  }

  document.addEventListener('DOMContentLoaded', initAiAgent);
})();
