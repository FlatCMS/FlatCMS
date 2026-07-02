(function (window, document) {
  'use strict';

  const Studio = window.FlatCMSStudio || (window.FlatCMSStudio = {});

  function parseBoot() {
    const node = document.getElementById('flatcms-studio-boot');
    if (!node) {
      return {};
    }

    try {
      const raw = node.tagName === 'TEMPLATE' && node.content
        ? (node.content.textContent || node.innerHTML || '{}')
        : (node.textContent || '{}');
      return JSON.parse(raw);
    } catch (error) {
      return {};
    }
  }

  function deepClone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function shortId() {
    return Math.random().toString(16).slice(2, 10);
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escapeAttr(value) {
    return escapeHtml(value);
  }

  function createDownload(filename, content) {
    const blob = new Blob([content], { type: 'application/json;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  }

  Studio.core = {
    parseBoot,
    deepClone,
    shortId,
    escapeHtml,
    escapeAttr,
    createDownload
  };
})(window, document);
