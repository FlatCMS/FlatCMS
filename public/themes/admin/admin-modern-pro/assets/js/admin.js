/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
  'use strict';

  // Sidebar toggle for desktop/mobile
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.createElement('div');
  overlay.className = 'sidebar-overlay';
  const sidebarCompactStorageKey = 'flatcms_admin_sidebar_compact';

  function isDesktopSidebar() {
    return window.matchMedia('(min-width: 1024px)').matches;
  }

  function syncSidebarToggleUi() {
    if (!sidebarToggle || !sidebar) {
      return;
    }

    const icon = sidebarToggle.querySelector('i');
    const isDesktop = isDesktopSidebar();
    const isCompact = sidebar.classList.contains('is-compact');
    const isOpen = sidebar.classList.contains('open');
    const label = isDesktop
      ? (isCompact ? sidebarToggle.dataset.labelExpand : sidebarToggle.dataset.labelCollapse)
      : (isOpen ? sidebarToggle.dataset.labelClose : sidebarToggle.dataset.labelOpen);
    const iconClass = isDesktop
      ? (isCompact ? 'fas fa-angles-right' : 'fas fa-angles-left')
      : (isOpen ? 'fas fa-xmark' : 'fas fa-bars');

    if (label) {
      sidebarToggle.setAttribute('aria-label', label);
      sidebarToggle.setAttribute('title', label);
    }

    if (isDesktop) {
      sidebarToggle.setAttribute('aria-pressed', isCompact ? 'true' : 'false');
      sidebarToggle.removeAttribute('aria-expanded');
    } else {
      sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      sidebarToggle.removeAttribute('aria-pressed');
    }

    if (icon) {
      icon.className = iconClass;
    }
  }

  function closeMobileSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
    syncSidebarToggleUi();
  }

  function restoreDesktopCompactState() {
    if (!sidebar) {
      return;
    }

    const isCompact = localStorage.getItem(sidebarCompactStorageKey) === '1';
    sidebar.classList.toggle('is-compact', isDesktopSidebar() && isCompact);
  }
  
  if (sidebarToggle && sidebar) {
    // Add overlay to body
    document.body.appendChild(overlay);
    restoreDesktopCompactState();
    syncSidebarToggleUi();
    
    sidebarToggle.addEventListener('click', function(e) {
      e.stopPropagation();
      if (isDesktopSidebar()) {
        const nextCompact = !sidebar.classList.contains('is-compact');
        sidebar.classList.toggle('is-compact', nextCompact);
        localStorage.setItem(sidebarCompactStorageKey, nextCompact ? '1' : '0');
        closeMobileSidebar();
        syncSidebarToggleUi();
        return;
      }

      sidebar.classList.toggle('open');
      overlay.classList.toggle('active');
      document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
      syncSidebarToggleUi();
    });

    // Close sidebar when clicking overlay
    overlay.addEventListener('click', function() {
      closeMobileSidebar();
    });

    // Close sidebar when clicking a nav link on mobile
    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
      link.addEventListener('click', function() {
        if (!isDesktopSidebar()) {
          closeMobileSidebar();
        }
      });
    });

    window.addEventListener('resize', function() {
      if (isDesktopSidebar()) {
        restoreDesktopCompactState();
        closeMobileSidebar();
      } else {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
      }
      syncSidebarToggleUi();
    });
  }

  // Theme Toggle (Dark/Light Mode)
  const themeToggle = document.getElementById('themeToggle');
  const themeDark = document.getElementById('themeDark');
  const themeLight = document.getElementById('themeLight');
  
  if (themeToggle && themeDark && themeLight) {
    // Check saved preference or default to dark
    const savedTheme = localStorage.getItem('admin-theme') || 'dark';
    
    // Remove init class and apply saved theme
    document.documentElement.classList.remove('theme-light-init');
    if (savedTheme === 'light') {
      document.body.classList.add('light-mode');
      themeToggle.classList.add('light-mode');
      themeDark.classList.remove('active');
      themeLight.classList.add('active');
    }
    
    // Click handlers
    themeDark.addEventListener('click', function() {
      if (!themeDark.classList.contains('active')) {
        document.body.classList.remove('light-mode');
        themeToggle.classList.remove('light-mode');
        themeDark.classList.add('active');
        themeLight.classList.remove('active');
        localStorage.setItem('admin-theme', 'dark');
      }
    });
    
    themeLight.addEventListener('click', function() {
      if (!themeLight.classList.contains('active')) {
        document.body.classList.add('light-mode');
        themeToggle.classList.add('light-mode');
        themeLight.classList.add('active');
        themeDark.classList.remove('active');
        localStorage.setItem('admin-theme', 'light');
      }
    });
  }

  // Dropdown Menus
  document.querySelectorAll('[data-component="dropdown"]').forEach(function(dropdown) {
    const btn = dropdown.querySelector('button');
    if (btn) {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('active');
      });
    }
  });

  document.addEventListener('click', function() {
    document.querySelectorAll('[data-component="dropdown"].active').forEach(function(d) {
      d.classList.remove('active');
    });
  });

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function normalizeToastType(type) {
    const key = String(type || '').toLowerCase();
    if (key === 'error' || key === 'danger') return 'error';
    if (key === 'warning' || key === 'warn' || key === 'info') return 'warning';
    return 'success';
  }

  function getToastContainer() {
    let container = document.getElementById('adminToastContainer');
    if (container) return container;

    container = document.createElement('div');
    container.id = 'adminToastContainer';
    container.className = 'menu-toast-container admin-toast-container';
    container.setAttribute('aria-live', 'polite');
    container.setAttribute('aria-atomic', 'false');
    document.body.appendChild(container);
    return container;
  }

  function resolveToastDuration(type, explicitDuration) {
    const duration = Number(explicitDuration);
    if (Number.isFinite(duration) && duration > 0) {
      return duration;
    }

    return normalizeToastType(type) === 'error' ? 20000 : 10000;
  }

  function showToast(message, type, duration) {
    const text = String(message || '').trim();
    if (!text) return;
    const toastType = normalizeToastType(type);
    const displayDuration = resolveToastDuration(toastType, duration);
    const container = getToastContainer();
    const toast = document.createElement('div');
    toast.className = 'menu-toast menu-toast-' + toastType;
    toast.setAttribute('role', 'status');

    const iconClass = toastType === 'error'
      ? 'fas fa-circle-exclamation'
      : (toastType === 'warning' ? 'fas fa-triangle-exclamation' : 'fas fa-circle-check');
    const title = toastType === 'error'
      ? 'Erreur'
      : (toastType === 'warning' ? 'Info' : 'Succes');

    toast.innerHTML = ''
      + '<span class="menu-toast-icon" aria-hidden="true"><i class="' + iconClass + '"></i></span>'
      + '<span class="menu-toast-content">'
      + '<span class="menu-toast-title">' + title + '</span>'
      + '<span class="menu-toast-message">' + escapeHtml(text) + '</span>'
      + '</span>';

    container.appendChild(toast);
    window.requestAnimationFrame(function() {
      toast.classList.add('is-visible');
    });

    window.setTimeout(function() {
      toast.classList.remove('is-visible');
      window.setTimeout(function() {
        toast.remove();
      }, 260);
    }, displayDuration);
  }

  function consumeFlashToasts() {
    document.querySelectorAll('[data-component="flash-toast"]').forEach(function(node) {
      showToast(node.dataset.message || '', node.dataset.type || 'success');
      node.remove();
    });
  }

  consumeFlashToasts();

  function dismissAlert(alert) {
    if (!alert) return;
    alert.style.opacity = '0';
    window.setTimeout(function() {
      alert.remove();
    }, 300);
  }

  function initAlerts() {
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function(alert) {
      if (alert.dataset.alertAutoDismissBound === '1') {
        return;
      }
      alert.dataset.alertAutoDismissBound = '1';
      const delay = parseInt(alert.dataset.autoDismiss || '', 10) || 2000;
      window.setTimeout(function() {
        dismissAlert(alert);
      }, delay);
    });
  }

  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.alert-close');
    if (!btn) return;
    const alert = btn.closest('.alert');
    if (!alert) return;
    e.preventDefault();
    dismissAlert(alert);
  });

  initAlerts();

  // ============================================
  // GLOBAL MODALS (ALERT + CONFIRM)
  // ============================================
  const alertModal = document.getElementById('alertModal');
  const alertModalMessage = document.getElementById('alertModalMessage');
  const alertModalOk = document.getElementById('alertModalOk');
  const confirmModal = document.getElementById('confirmModal');
  const confirmModalMessage = document.getElementById('confirmModalMessage');
  const confirmModalItemName = document.getElementById('confirmModalItemName');
  const confirmModalItemValue = document.getElementById('confirmModalItemValue');
  const confirmModalWarning = document.getElementById('confirmModalWarning');
  const confirmModalConfirm = document.getElementById('confirmModalConfirm');
  const helpModal = document.getElementById('helpModal');
  const helpModalBody = document.getElementById('helpModalBody');
  const helpModalTitleText = document.getElementById('helpModalTitleText');
  const defaultConfirmText = confirmModalConfirm ? confirmModalConfirm.textContent : null;
  const defaultConfirmMessage = confirmModal ? confirmModal.dataset.defaultMessage : null;
  const defaultConfirmWarning = confirmModal ? confirmModal.dataset.defaultWarning : null;
  const defaultHelpTitle = helpModal ? String(helpModal.dataset.defaultTitle || '').trim() : '';
  const defaultHelpTriggerLabel = helpModal ? String(helpModal.dataset.triggerLabel || '').trim() : '';
  let confirmCallback = null;
  let activeHelpTemplate = null;
  let activeHelpPlaceholder = null;

  function isModalVisible(modal) {
    if (!modal) return false;
    if (modal.style.display && modal.style.display !== 'none') return true;
    return window.getComputedStyle(modal).display !== 'none';
  }

  function updateBodyOverflow() {
    const anyVisibleModal = Array.from(document.querySelectorAll('.modal-overlay')).some(isModalVisible);
    document.body.style.overflow = anyVisibleModal ? 'hidden' : '';
  }

  function openModal(modal) {
    if (!modal) return;

    // Ensure only one global overlay is visible at once.
    document.querySelectorAll('.modal-overlay').forEach(function(other) {
      if (other === modal) return;
      closeModal(other);
    });

    modal.classList.remove('is-initially-hidden');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    updateBodyOverflow();
  }

  function restoreHelpTemplate() {
    if (!activeHelpTemplate) {
      if (helpModalTitleText) {
        helpModalTitleText.textContent = defaultHelpTitle;
      }
      return;
    }

    if (helpModalBody && helpModalBody.contains(activeHelpTemplate)) {
      helpModalBody.removeChild(activeHelpTemplate);
    }

    if (activeHelpPlaceholder && activeHelpPlaceholder.parentNode) {
      activeHelpPlaceholder.parentNode.insertBefore(activeHelpTemplate, activeHelpPlaceholder);
      activeHelpPlaceholder.remove();
    }

    activeHelpTemplate.hidden = true;
    activeHelpTemplate.setAttribute('aria-hidden', 'true');
    activeHelpTemplate = null;
    activeHelpPlaceholder = null;

    if (helpModalTitleText) {
      helpModalTitleText.textContent = defaultHelpTitle;
    }
  }

  function closeModal(modal) {
    if (!modal) return;
    if (modal === helpModal) {
      restoreHelpTemplate();
    }
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    updateBodyOverflow();
  }

  function isGlobalModal(modal) {
    return modal === alertModal || modal === confirmModal || modal === helpModal;
  }

  function showAlert(message) {
    if (alertModalMessage) {
      alertModalMessage.textContent = message || '';
    }
    openModal(alertModal);
  }

  function showConfirm(message, onConfirm, options) {
    const finalMessage = message || defaultConfirmMessage || '';
    const hasWarningOverride = !!(options && Object.prototype.hasOwnProperty.call(options, 'warning'));
    const finalWarning = hasWarningOverride ? String(options.warning || '') : (defaultConfirmWarning || '');
    const finalItemName = (options && options.itemName) ? options.itemName : '';

    if (confirmModalMessage) {
      confirmModalMessage.textContent = finalMessage;
    }
    if (confirmModalItemName && confirmModalItemValue) {
      if (finalItemName) {
        confirmModalItemValue.textContent = finalItemName;
        confirmModalItemName.style.display = 'block';
      } else {
        confirmModalItemValue.textContent = '';
        confirmModalItemName.style.display = 'none';
      }
    }
    if (confirmModalWarning) {
      confirmModalWarning.textContent = finalWarning;
      confirmModalWarning.style.display = finalWarning ? 'block' : 'none';
    }
    confirmCallback = typeof onConfirm === 'function' ? onConfirm : null;
    if (confirmModalConfirm) {
      confirmModalConfirm.textContent = (options && options.confirmText) ? options.confirmText : (defaultConfirmText || confirmModalConfirm.textContent);
    }
    openModal(confirmModal);
  }

  function createHelpTrigger(templateId, label) {
    const button = document.createElement('button');
    const icon = document.createElement('i');
    const text = document.createElement('span');

    button.type = 'button';
    button.className = 'btn btn-secondary admin-help-trigger';
    button.setAttribute('data-admin-help-open', templateId);
    button.setAttribute('aria-haspopup', 'dialog');

    icon.className = 'fas fa-lightbulb';
    icon.setAttribute('aria-hidden', 'true');

    text.textContent = label;

    button.appendChild(icon);
    button.appendChild(text);

    return button;
  }

  function ensureHelpTrigger(template, index) {
    const header = document.querySelector('.page-header');
    if (!header) {
      template.hidden = false;
      template.removeAttribute('aria-hidden');
      return;
    }

    let actions = header.querySelector('.page-header-actions');
    if (!actions) {
      actions = document.createElement('div');
      actions.className = 'page-header-actions page-header-actions-help-only';
      header.appendChild(actions);
    }

    if (actions.querySelector('[data-admin-help-open="' + template.id + '"]')) {
      return;
    }

    const target = header.querySelector('[data-admin-help-actions]') || actions;

    const titleNode = template.querySelector('.admin-guidance-card__title');
    const fallbackTitle = titleNode ? String(titleNode.textContent || '').trim() : '';
    const buttonLabel = defaultHelpTriggerLabel || fallbackTitle || defaultHelpTitle || '';

    if (!template.id) {
      template.id = 'adminHelpTemplate' + String(index + 1);
    }

    target.appendChild(createHelpTrigger(template.id, buttonLabel));
  }

  function openHelpModal(templateId) {
    if (!helpModal || !helpModalBody) return;

    const template = document.getElementById(templateId);
    if (!template || !template.parentNode) return;

    restoreHelpTemplate();

    activeHelpTemplate = template;
    activeHelpPlaceholder = document.createElement('div');
    activeHelpPlaceholder.hidden = true;
    activeHelpPlaceholder.setAttribute('data-admin-help-placeholder', '1');
    template.parentNode.insertBefore(activeHelpPlaceholder, template);

    const titleNode = template.querySelector('.admin-guidance-card__title');
    const nextTitle = titleNode ? String(titleNode.textContent || '').trim() : '';

    template.hidden = false;
    template.removeAttribute('aria-hidden');
    helpModalBody.appendChild(template);

    if (helpModalTitleText) {
      helpModalTitleText.textContent = nextTitle || defaultHelpTitle;
    }

    openModal(helpModal);
  }

  function initAdminHelp() {
    if (!helpModal) return;

    const templates = Array.from(document.querySelectorAll('.admin-guidance-card[data-admin-help-template]'));
    templates.forEach(function(template, index) {
      if (!template.id) {
        template.id = 'adminHelpTemplate' + String(index + 1);
      }
      template.hidden = true;
      template.setAttribute('aria-hidden', 'true');
      ensureHelpTrigger(template, index);
    });
  }

  if (alertModalOk) {
    alertModalOk.addEventListener('click', function() {
      closeModal(alertModal);
    });
  }

  if (confirmModalConfirm) {
    confirmModalConfirm.addEventListener('click', function() {
      const cb = confirmCallback;
      confirmCallback = null;
      closeModal(confirmModal);
      if (cb) cb();
    });
  }

  document.querySelectorAll('[data-modal-close]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const targetId = btn.getAttribute('data-modal-close');
      const targetModal = document.getElementById(targetId);
      if (!isGlobalModal(targetModal)) {
        return;
      }
      closeModal(targetModal);
    });
  });

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (!isGlobalModal(overlay)) {
        return;
      }
      if (e.target === overlay) {
        closeModal(overlay);
      }
    });
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeModal(alertModal);
      closeModal(confirmModal);
      closeModal(helpModal);
    }
  });

  document.addEventListener('click', function(e) {
    const helpTrigger = e.target.closest('[data-admin-help-open]');
    if (!helpTrigger) return;
    e.preventDefault();
    openHelpModal(helpTrigger.getAttribute('data-admin-help-open') || '');
  });

  document.addEventListener('click', function(e) {
    if (!helpModal || !helpModalBody || !isModalVisible(helpModal)) return;
    const action = e.target.closest('.admin-guidance-card__actions .btn');
    if (!action || !helpModalBody.contains(action)) return;
    if (action.tagName === 'BUTTON' && String(action.getAttribute('type') || 'button').toLowerCase() === 'submit') {
      return;
    }
    window.setTimeout(function() {
      closeModal(helpModal);
    }, 0);
  });

  window.FlatCMS = window.FlatCMS || {};
  window.FlatCMS.toast = {
    show: showToast
  };
  window.FlatCMS.modal = {
    alert: showAlert,
    confirm: showConfirm,
    help: openHelpModal,
    close: function(id) {
      closeModal(document.getElementById(id));
    }
  };

  initAdminHelp();

  // Confirm delete buttons (global)
  document.addEventListener('click', function(e) {
    if (e.defaultPrevented) return;
    const btn = e.target.closest('[data-action="confirm-delete"]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    const form = btn.closest('form');
    if (!form) return;

    const message = btn.dataset.message || defaultConfirmMessage || (confirmModalMessage ? confirmModalMessage.textContent : 'Êtes-vous sûr ?');
    const itemName = btn.dataset.itemName || btn.dataset.name || '';
    const warning = btn.dataset.warning || defaultConfirmWarning || '';
    showConfirm(message, function() {
      form.submit();
    }, { confirmText: btn.dataset.confirmText || (confirmModalConfirm ? confirmModalConfirm.textContent : undefined), warning, itemName });
  });

  console.log('FlatCMS Admin Modern Pro initialized');
})();
