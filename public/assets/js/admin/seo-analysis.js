/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: public/assets/js/admin/seo-analysis.js
 * Version: 2.0.0-dev
 */

(function() {
  'use strict';

  function parseJson(value) {
    try {
      var parsed = JSON.parse(String(value || ''));
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
      return {};
    }
  }

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return String(meta && meta.getAttribute('content') || '').trim();
  }

  function fieldElement(id) {
    var value = String(id || '').trim();
    return value === '' ? null : document.getElementById(value);
  }

  function editorValue(element) {
    if (!(element instanceof HTMLTextAreaElement)) {
      return String(element && element.value || '');
    }

    if (window.FlatCMSCKEditor && typeof window.FlatCMSCKEditor.getEditor === 'function') {
      var handle = window.FlatCMSCKEditor.getEditor(element);
      if (handle && typeof handle.getHtml === 'function') {
        return String(handle.getHtml() || '');
      }
    }

    if (window.tinymce && typeof window.tinymce.get === 'function' && element.id) {
      var tinyEditor = window.tinymce.get(element.id);
      if (tinyEditor && typeof tinyEditor.getContent === 'function') {
        return String(tinyEditor.getContent() || '');
      }
    }

    return String(element.value || '');
  }

  function stateBadgeClass(state) {
    if (state === 'pass') {
      return 'badge badge-success';
    }
    if (state === 'fail') {
      return 'badge badge-danger';
    }
    return 'badge badge-warning';
  }

  function ratingBadgeClass(rating) {
    if (rating === 'good') {
      return 'badge badge-success';
    }
    if (rating === 'weak') {
      return 'badge badge-danger';
    }
    return 'badge badge-warning';
  }

  function mappedElements(fields) {
    return Object.keys(fields).reduce(function(elements, name) {
      var element = fieldElement(fields[name]);
      if (element && elements.indexOf(element) === -1) {
        elements.push(element);
      }
      return elements;
    }, []);
  }

  function updateFlattyRecommendations(form, fields, checks) {
    Object.keys(fields).forEach(function(fieldName) {
      var element = fieldElement(fields[fieldName]);
      var target = element && element.closest('[data-ai-agent-target]');
      if (target && form.contains(target)) {
        target.removeAttribute('data-ai-agent-recommended');
      }
    });

    (Array.isArray(checks) ? checks : []).forEach(function(check) {
      if (!check || check.state === 'pass') {
        return;
      }
      var element = fieldElement(fields[String(check.field || '')]);
      var target = element && element.closest('[data-ai-agent-target]');
      if (target && form.contains(target)) {
        target.setAttribute('data-ai-agent-recommended', '1');
      }
    });
  }

  function renderChecks(list, checks) {
    list.replaceChildren();
    (Array.isArray(checks) ? checks : []).forEach(function(check) {
      var state = String(check && check.state || 'warning');
      var item = document.createElement('li');
      var indicator = document.createElement('span');
      var message = document.createElement('span');
      var points = document.createElement('span');

      item.className = 'seo-analysis__check is-' + state;
      indicator.className = 'seo-analysis__check-indicator';
      indicator.setAttribute('aria-hidden', 'true');
      message.className = 'seo-analysis__check-message';
      message.textContent = String(check && check.message || '');
      points.className = stateBadgeClass(state);
      points.textContent = String(Number(check && check.earned || 0)) + '/' + String(Number(check && check.weight || 0));

      item.appendChild(indicator);
      item.appendChild(message);
      item.appendChild(points);
      list.appendChild(item);
    });
  }

  function initialize(root) {
    var form = root.closest('form');
    var endpoint = String(root.getAttribute('data-endpoint') || '').trim();
    var entity = String(root.getAttribute('data-entity') || 'page').trim();
    var fields = parseJson(root.getAttribute('data-fields'));
    var elements = mappedElements(fields);
    var score = root.querySelector('[data-seo-analysis-score]');
    var rating = root.querySelector('[data-seo-analysis-rating]');
    var progress = root.querySelector('[data-seo-analysis-progress]');
    var status = root.querySelector('[data-seo-analysis-status]');
    var checks = root.querySelector('[data-seo-analysis-checks]');
    var timer = 0;
    var requestSequence = 0;
    var controller = null;

    if (!form || endpoint === '' || !score || !rating || !progress || !status || !checks || elements.length === 0) {
      return;
    }

    function setStatus(message) {
      status.textContent = String(message || '');
      status.hidden = status.textContent === '';
    }

    function payload() {
      var documentData = { entity: entity };
      Object.keys(fields).forEach(function(name) {
        var element = fieldElement(fields[name]);
        documentData[name] = element ? editorValue(element) : '';
      });
      return documentData;
    }

    function render(analysis) {
      var numericScore = Math.max(0, Math.min(100, Number(analysis && analysis.score || 0)));
      score.textContent = String(numericScore) + '/100';
      progress.value = numericScore;
      root.setAttribute('data-rating', String(analysis && analysis.rating || 'improvable'));
      rating.className = ratingBadgeClass(String(analysis && analysis.rating || 'improvable'));
      rating.textContent = String(analysis && analysis.rating_label || '');
      rating.hidden = rating.textContent === '';
      setStatus('');
      renderChecks(checks, analysis && analysis.checks);
      updateFlattyRecommendations(form, fields, analysis && analysis.checks);
      document.dispatchEvent(new CustomEvent('flatcms:seo-analysis-updated', {
        detail: { root: root, analysis: analysis }
      }));
    }

    function analyze() {
      requestSequence += 1;
      var sequence = requestSequence;
      if (controller) {
        controller.abort();
      }
      controller = typeof AbortController === 'function' ? new AbortController() : null;
      setStatus(root.getAttribute('data-loading-label'));

      fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload()),
        signal: controller ? controller.signal : undefined
      }).then(function(response) {
        if (!response.ok) {
          throw new Error(String(response.status));
        }
        return response.json();
      }).then(function(response) {
        if (sequence !== requestSequence || !response || response.success !== true || !response.analysis) {
          return;
        }
        render(response.analysis);
      }).catch(function(error) {
        if (error && error.name === 'AbortError') {
          return;
        }
        if (sequence === requestSequence) {
          setStatus(root.getAttribute('data-unavailable-label'));
        }
      });
    }

    function schedule() {
      window.clearTimeout(timer);
      timer = window.setTimeout(analyze, 420);
    }

    elements.forEach(function(element) {
      element.addEventListener('input', schedule);
      element.addEventListener('change', schedule);
      element.addEventListener('flatcms:editor-change', schedule);
    });

    schedule();
  }

  function bootstrap() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-seo-analysis]'), initialize);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
  } else {
    bootstrap();
  }
})();
