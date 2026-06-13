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

    if (!drawer || !backdrop || !thread || !suggestions || !workspaceBody || !workspaceMeta || !restoreButton || !applyButton || !sendButton || !input || endpoint === '') {
      return;
    }

    var state = {
      open: false,
      context: null,
      snapshot: null,
      liveDirty: false,
      selectedVariant: -1,
      currentProposalType: '',
      currentProposal: null
    };

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
        var currentLocale = String(activeLocaleInput && activeLocaleInput.value || context.locale || '').trim();
        var currentSourceLocale = String(sourceLocaleInput && sourceLocaleInput.value || context.source_locale || currentLocale).trim();
        if (currentLocale === '') {
          currentLocale = currentSourceLocale;
        }

        context.locale = currentLocale;
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
      var context = {
        target: target,
        form: form,
        module: String(target.getAttribute('data-ai-agent-module') || '').trim(),
        entity: String(target.getAttribute('data-ai-agent-entity') || '').trim(),
        entity_id: String(target.getAttribute('data-ai-agent-entity-id') || '').trim(),
        source_id: '',
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

    function resolveActionLabel(action) {
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

    function setSuggestions(actions) {
      suggestions.innerHTML = '';
      (actions || []).forEach(function(action) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'ai-agent-suggestion';
        button.textContent = resolveActionLabel(action);
        button.addEventListener('click', function() {
          sendMessage('', action);
        });
        suggestions.appendChild(button);
      });
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

      if (proposalType === 'content_block' || proposalType === 'seo_block') {
        var values = proposal && proposal.values ? proposal.values : {};
        var keys = getPreviewKeys(state.context, proposalType).filter(function(key) {
          return Object.prototype.hasOwnProperty.call(values, key);
        });

        applyDraftValues(values, keys);
        renderDraftWorkspace(keys, values, String(i18n.workspaceMetaDraft || ''), '');
        setBubbleContent(bubble, getAssistantReply(intent), buildInlineDraftPreview(state.context, values, keys, ''));
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
      if (messageValue === '' && actionValue === '') {
        showToast(i18n.errorEmpty || '', 'warning');
        return;
      }

      var userText = messageValue !== '' ? messageValue : resolveActionLabel(actionValue);
      appendMessage('user', userText);
      input.value = '';
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

    function openDrawerForTarget(target) {
      if (!(target instanceof HTMLElement)) {
        return;
      }

      state.context = getContextFromTarget(target);
      state.snapshot = captureSnapshot(state.context);
      state.liveDirty = false;
      state.selectedVariant = -1;
      state.currentProposalType = '';
      state.currentProposal = null;

      clearThread();
      input.value = '';
      renderContextLabel();
      renderCurrentWorkspace();
      setSuggestions(getDefaultActions(state.context));

      if (state.context.scope === 'field' && state.context.label !== '') {
        appendMessage('assistant', template(i18n.greetingField || '', {
          label: state.context.label,
          block: state.context.block_label || state.context.block
        }));
      } else {
        appendMessage('assistant', template(i18n.greetingBlock || '', {
          block: state.context.block_label || state.context.block
        }));
      }

      updateOpenState(true);
      window.setTimeout(function() {
        input.focus();
      }, 60);
    }

    function closeDrawer() {
      updateOpenState(false);
    }

    function createTargetButton(target) {
      if (!(target instanceof HTMLElement) || target.querySelector('[data-ai-agent-trigger-button]')) {
        return;
      }

      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'ai-agent-trigger';
      button.setAttribute('data-ai-agent-trigger-button', '1');
      button.setAttribute('aria-label', String(i18n.title || 'AI'));
      button.innerHTML = ''
        + '<img src="' + escapeHtml(iconDark) + '" alt="" class="ai-agent-trigger-icon is-dark" aria-hidden="true">'
        + '<img src="' + escapeHtml(iconLight) + '" alt="" class="ai-agent-trigger-icon is-light" aria-hidden="true">';
      button.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        openDrawerForTarget(target);
      });

      target.appendChild(button);
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-ai-agent-target]'), function(target) {
      createTargetButton(target);
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
      if (!state.open) {
        return;
      }

      var target = event.target;
      if (!(target instanceof Node)) {
        return;
      }

      if (drawer.contains(target)) {
        return;
      }

      if (target instanceof Element && target.closest('[data-ai-agent-trigger-button]')) {
        return;
      }

      closeDrawer();
    });
  }

  document.addEventListener('DOMContentLoaded', initAiAgent);
})();
