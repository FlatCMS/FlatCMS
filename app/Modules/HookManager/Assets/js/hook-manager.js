/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
  'use strict';

  const $ = (selector, scope = document) => scope.querySelector(selector);
  const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));

  const accordions = $$('.hook-accordion');
  const rows = $$('.hook-row');

  const searchInput = $('#hookSearchInput');
  const groupFilter = $('#hookGroupFilter');
  const listenerFilter = $('#hookListenerFilter');
  const fieldHook = $('#hookSearchInHook');
  const fieldDesc = $('#hookSearchInDescription');
  const fieldParams = $('#hookSearchInParams');
  const fieldListeners = $('#hookSearchInListeners');
  const noResults = $('#hookNoResults');
  const btnExpandAll = $('#hookExpandAll');
  const btnCollapseAll = $('#hookCollapseAll');

  const getSearchFields = () => {
    const fields = [];
    if (fieldHook && fieldHook.checked) fields.push('hook', 'label');
    if (fieldDesc && fieldDesc.checked) fields.push('description');
    if (fieldParams && fieldParams.checked) fields.push('params');
    if (fieldListeners && fieldListeners.checked) fields.push('listeners');
    if (fields.length === 0) {
      return ['hook', 'label', 'description', 'params', 'listeners'];
    }
    return fields;
  };

  const toggleAccordion = (header, open = null) => {
    const group = header.getAttribute('data-group');
    const content = document.querySelector(`.hook-accordion-content[data-group="${group}"]`);
    if (!content) return;
    const isOpen = header.classList.contains('active');
    const shouldOpen = open === null ? !isOpen : open;
    header.classList.toggle('active', shouldOpen);
    content.classList.toggle('active', shouldOpen);
  };

  const filterRows = () => {
    const query = (searchInput?.value || '').trim().toLowerCase();
    const groupValue = groupFilter?.value || 'all';
    const listenerValue = listenerFilter?.value || 'all';
    const fields = getSearchFields();

    let visibleCount = 0;

    rows.forEach((row) => {
      const group = row.getAttribute('data-group') || '';
      const listenerCount = parseInt(row.getAttribute('data-listener-count') || '0', 10);

      if (groupValue !== 'all' && groupValue !== group) {
        row.classList.add('hidden');
        return;
      }

      if (listenerValue === 'with' && listenerCount === 0) {
        row.classList.add('hidden');
        return;
      }

      if (listenerValue === 'without' && listenerCount > 0) {
        row.classList.add('hidden');
        return;
      }

      if (query !== '') {
        let haystack = '';
        fields.forEach((field) => {
          haystack += ' ' + (row.getAttribute(`data-${field}`) || '');
        });
        if (!haystack.toLowerCase().includes(query)) {
          row.classList.add('hidden');
          return;
        }
      }

      row.classList.remove('hidden');
      visibleCount += 1;
    });

    accordions.forEach((accordion) => {
      const group = accordion.getAttribute('data-group') || '';
      const groupRows = $$('.hook-row', accordion);
      const hasVisible = groupRows.some((row) => !row.classList.contains('hidden'));
      accordion.classList.toggle('hidden', !hasVisible);
      if (groupValue !== 'all') {
        const header = $('.hook-accordion-header', accordion);
        if (header) {
          toggleAccordion(header, hasVisible);
        }
      }
      if (groupValue === 'all' && query !== '') {
        const header = $('.hook-accordion-header', accordion);
        if (header) {
          toggleAccordion(header, hasVisible);
        }
      }
    });

    if (noResults) {
      noResults.classList.toggle('hidden', visibleCount > 0);
    }
  };

  if (accordions.length) {
    accordions.forEach((accordion) => {
      const header = $('.hook-accordion-header', accordion);
      if (!header) return;
      header.addEventListener('click', () => toggleAccordion(header));
    });
  }

  if (btnExpandAll) {
    btnExpandAll.addEventListener('click', () => {
      $$('.hook-accordion-header').forEach((header) => toggleAccordion(header, true));
    });
  }

  if (btnCollapseAll) {
    btnCollapseAll.addEventListener('click', () => {
      $$('.hook-accordion-header').forEach((header) => toggleAccordion(header, false));
    });
  }

  const bindFilter = (el) => {
    if (!el) return;
    el.addEventListener('input', filterRows);
    el.addEventListener('change', filterRows);
  };

  [searchInput, groupFilter, listenerFilter, fieldHook, fieldDesc, fieldParams, fieldListeners].forEach(bindFilter);

  filterRows();
})();
