/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: public/assets/js/admin/admin-modern-pro.js
 * Version: 2.0.0-dev
 */

(function() {
  'use strict';

  // ============================================
  // SIDEBAR TOGGLE
  // ============================================
  const sidebar = document.querySelector('.admin-sidebar');
  const sidebarToggle = document.querySelector('.sidebar-toggle');
  const navbarToggle = document.querySelector('.navbar-toggle');
  const sidebarOverlay = document.querySelector('.sidebar-overlay');

  // Toggle sidebar collapse (desktop)
  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('collapsed');
      localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    });
  }

  // Toggle sidebar open/close (mobile)
  if (navbarToggle) {
    navbarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('open');
      sidebarOverlay?.classList.toggle('active');
    });
  }

  // Close sidebar when clicking overlay
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function() {
      sidebar.classList.remove('open');
      sidebarOverlay.classList.remove('active');
    });
  }

  // Restore sidebar state from localStorage
  if (localStorage.getItem('sidebar-collapsed') === 'true') {
    sidebar?.classList.add('collapsed');
  }

  // ============================================
  // DROPDOWN MENUS
  // ============================================
  document.querySelectorAll('.dropdown').forEach(function(dropdown) {
    const trigger = dropdown.querySelector('[data-dropdown-trigger]');
    
    if (trigger) {
      trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        
        // Close other dropdowns
        document.querySelectorAll('.dropdown.active').forEach(function(d) {
          if (d !== dropdown) d.classList.remove('active');
        });
        
        dropdown.classList.toggle('active');
      });
    }
  });

  // Close dropdowns when clicking outside
  document.addEventListener('click', function() {
    document.querySelectorAll('.dropdown.active').forEach(function(d) {
      d.classList.remove('active');
    });
  });

  // ============================================
  // MODAL HANDLING
  // ============================================
  window.openModal = function(modalId) {
    const backdrop = document.getElementById(modalId);
    const modal = window.FlatCMS && window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.modal;
    if (backdrop && modal) {
      modal.open(backdrop);
    }
  };

  window.closeModal = function(modalId) {
    const backdrop = document.getElementById(modalId);
    const modal = window.FlatCMS && window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.modal;
    if (backdrop && modal) {
      modal.close(backdrop);
    }
  };

  document.querySelectorAll('.modal-backdrop').forEach(function(backdrop) {
    const modal = window.FlatCMS && window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.modal;
    if (modal) {
      modal.attach(backdrop, { activeClass: 'active' });
    }
  });

  // ============================================
  // TABS
  // ============================================
  document.querySelectorAll('.tabs').forEach(function(tabs) {
    const tabsApi = window.FlatCMS && window.FlatCMS.AdminUI && window.FlatCMS.AdminUI.tabs;
    if (tabsApi) {
      tabsApi.attach({
        root: tabs,
        tabSelector: '.tab-btn',
        panelSelector: '.tab-panel',
        tabAttribute: 'data-tab',
        panelAttribute: 'id',
        activeClass: 'active',
        panelActiveClass: 'active'
      });
    }
  });

  // ============================================
  // ALERTS AUTO-DISMISS
  // ============================================
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function(alert) {
    const delay = parseInt(alert.dataset.autoDismiss) || 2000;
    setTimeout(function() {
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 300);
    }, delay);
  });

  // Alert close buttons
  document.querySelectorAll('.alert-close').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const alert = btn.closest('.alert');
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 300);
    });
  });

  // ============================================
  // TOGGLE SWITCHES
  // ============================================
  document.querySelectorAll('.toggle-input').forEach(function(toggle) {
    toggle.addEventListener('change', function() {
      const event = new CustomEvent('toggle-change', {
        detail: { checked: toggle.checked, name: toggle.name }
      });
      toggle.dispatchEvent(event);
    });
  });

  // ============================================
  // CONFIRM DIALOGS
  // ============================================
  document.querySelectorAll('[data-confirm]').forEach(function(element) {
    element.addEventListener('click', function(e) {
      const confirmModal = document.getElementById('confirmModal');
      const message = element.dataset.confirm
        || (confirmModal && confirmModal.dataset.defaultMessage)
        || '';
      if (!confirm(message)) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  });

  // ============================================
  // TOOLTIPS INITIALIZATION
  // ============================================
  // Tooltips are CSS-only, no JS needed

  // ============================================
  // FORM VALIDATION STYLING
  // ============================================
  document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      const requiredFields = form.querySelectorAll('[required]');
      let valid = true;
      
      requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
          field.classList.add('form-input-error');
          valid = false;
        } else {
          field.classList.remove('form-input-error');
        }
      });
      
      if (!valid) {
        e.preventDefault();
      }
    });
  });

  // Remove error class on input
  document.querySelectorAll('.form-input, .form-textarea, .form-select').forEach(function(input) {
    input.addEventListener('input', function() {
      input.classList.remove('form-input-error');
    });
  });

  // ============================================
  // INIT COMPLETE
  // ============================================
  console.log('FlatCMS Admin Modern Pro theme initialized');

})();
