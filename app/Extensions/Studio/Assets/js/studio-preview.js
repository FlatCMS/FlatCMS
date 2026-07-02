(function (document) {
  'use strict';

  const stage = document.querySelector('[data-preview-stage="1"]');
  if (!stage) {
    return;
  }

  stage.style.setProperty('--page-primary', stage.dataset.designPrimary || '#4F46E5');
  stage.style.setProperty('--page-accent', stage.dataset.designAccent || '#111827');
  stage.style.setProperty('--page-ink', stage.dataset.designInk || '#111827');
  stage.style.setProperty('--page-paper', stage.dataset.designPaper || '#FFFFFF');
  stage.style.setProperty('--page-soft', stage.dataset.designSoft || '#F7F8FA');
  stage.style.setProperty('--page-radius', String(Number(stage.dataset.designRadius || 8)) + 'px');
  stage.style.maxWidth = String(Number(stage.dataset.designWidth || 1180)) + 'px';
  if (stage.dataset.designFont) {
    stage.style.fontFamily = stage.dataset.designFont;
  } else {
    stage.style.removeProperty('font-family');
  }
})(document);
