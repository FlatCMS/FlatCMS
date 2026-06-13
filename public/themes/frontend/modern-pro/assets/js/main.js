/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
  'use strict';

  // Mobile menu toggle
  const menuToggle = document.getElementById('menuToggle');
  const mainNav = document.getElementById('mainNav');
  
  if (menuToggle && mainNav) {
    const isMobileMenu = () => window.matchMedia('(max-width: 1024px)').matches;

	    const getDirectToggle = (item) => {
	      if (!item) return null;
	      try {
	        const scoped = item.querySelector(':scope > .submenu-toggle');
	        if (scoped) return scoped;
	      } catch (e) {
	        // Older browsers: ignore and fallback below.
	      }
	      return Array.from(item.children).find(child => child.classList && child.classList.contains('submenu-toggle')) || null;
	    };

	    const getDirectPanel = (item) => {
	      if (!item) return null;
	      try {
	        const scoped = item.querySelector(':scope > .submenu');
	        if (scoped) return scoped;
	      } catch (e) {
	        // Older browsers: ignore and fallback below.
	      }
	      return Array.from(item.children).find(child =>
	        child.classList && (child.classList.contains('submenu'))
	      ) || null;
	    };

		    const openPanel = (item) => {
		      if (!isMobileMenu()) return;
		      const panel = getDirectPanel(item);
		      if (!panel) return;
		      panel.style.maxHeight = '0px';
		      // Force reflow so the transition always kicks in.
		      panel.offsetHeight;
		      panel.style.maxHeight = panel.scrollHeight + 'px';

		      const onEnd = (event) => {
		        if (event.propertyName !== 'max-height') return;
		        panel.removeEventListener('transitionend', onEnd);
		        if (item.classList.contains('submenu-open')) {
		          panel.style.maxHeight = 'none';
		        }
		      };
		      panel.addEventListener('transitionend', onEnd);
		    };

		    const closePanel = (item) => {
		      if (!isMobileMenu()) return;
		      const panel = getDirectPanel(item);
		      if (!panel) return;
		      if (panel.style.maxHeight === 'none' || panel.style.maxHeight === '') {
		        panel.style.maxHeight = panel.scrollHeight + 'px';
		      }
		      panel.offsetHeight;
		      panel.style.maxHeight = '0px';
		    };

	    const closeItem = (item) => {
	      if (!item) return;
	      closePanel(item);
	      item.classList.remove('submenu-open');
	      const toggle = getDirectToggle(item);
	      if (toggle) toggle.setAttribute('aria-expanded', 'false');

	      item.querySelectorAll('.nav-item.submenu-open').forEach(child => {
	        closePanel(child);
	        child.classList.remove('submenu-open');
	        const childToggle = getDirectToggle(child);
	        if (childToggle) childToggle.setAttribute('aria-expanded', 'false');
	      });
	    };

    const closeSiblings = (item) => {
      const parent = item && item.parentElement;
      if (!parent) return;
      Array.from(parent.children).forEach(sibling => {
        if (sibling === item) return;
        if (!sibling.classList || !sibling.classList.contains('submenu-open')) return;
        closeItem(sibling);
      });
    };

	    const closeSubmenus = () => {
	      mainNav.querySelectorAll('.nav-item.submenu-open').forEach(closeItem);
	    };

	    const isDesktopMenu = () => window.matchMedia('(min-width: 1025px)').matches;
	    const headerRoot = document.querySelector('.site-header') || document.body;

	    const getTopLevelNavList = () => {
	      try {
	        const scoped = mainNav.querySelector(':scope > .main-nav-list');
	        if (scoped) {
	          return scoped;
	        }
	      } catch (e) {
	        // Older browsers: ignore and fallback below.
	      }
	      return Array.from(mainNav.children).find(child =>
	        child.classList && child.classList.contains('main-nav-list')
	      ) || null;
	    };

	    const getHeaderContainer = () => {
	      try {
	        const scoped = headerRoot.querySelector(':scope > .container');
	        if (scoped) {
	          return scoped;
	        }
	      } catch (e) {
	        // Older browsers: ignore and fallback below.
	      }
	      return headerRoot.querySelector('.container');
	    };

	    const topLevelNavWraps = (navList) => {
	      if (!navList) {
	        return false;
	      }

	      const items = Array.from(navList.children).filter(child => child instanceof HTMLElement && child.offsetParent !== null);
	      let baselineTop = null;
	      for (let i = 0; i < items.length; i += 1) {
	        const itemTop = Math.round(items[i].getBoundingClientRect().top);
	        if (baselineTop === null) {
	          baselineTop = itemTop;
	          continue;
	        }
	        if (Math.abs(itemTop - baselineTop) > 2) {
	          return true;
	        }
	      }

	      return false;
	    };

	    const headerContainerWraps = (container) => {
	      if (!container) {
	        return false;
	      }

	      const children = Array.from(container.children).filter(child => child instanceof HTMLElement && child.offsetParent !== null);
	      let baselineCenter = null;
	      for (let i = 0; i < children.length; i += 1) {
	        const rect = children[i].getBoundingClientRect();
	        const childCenter = Math.round(rect.top + (rect.height / 2));
	        if (baselineCenter === null) {
	          baselineCenter = childCenter;
	          continue;
	        }
	        if (Math.abs(childCenter - baselineCenter) > 2) {
	          return true;
	        }
	      }

	      return false;
	    };

	    const refreshHeaderDensity = () => {
	      if (!headerRoot) {
	        return;
	      }
	      if (!isDesktopMenu()) {
	        headerRoot.classList.remove('site-header--dense-nav');
	        return;
	      }

	      const navList = getTopLevelNavList();
	      if (!navList) {
	        headerRoot.classList.remove('site-header--dense-nav');
	        return;
	      }

	      headerRoot.classList.remove('site-header--dense-nav');
	      const headerContainer = getHeaderContainer();
	      const needsDenseLayout = headerContainerWraps(headerContainer) || topLevelNavWraps(navList);
	      headerRoot.classList.toggle('site-header--dense-nav', needsDenseLayout);
	    };

	    const scheduleHeaderDensityRefresh = () => {
	      window.requestAnimationFrame(refreshHeaderDensity);
	    };

	    const refreshDesktopSubmenuDirection = () => {
	      const nestedItems = mainNav.querySelectorAll('.submenu .nav-item.has-children');
	      if (!isDesktopMenu()) {
	        nestedItems.forEach(item => item.classList.remove('submenu-dropstart'));
	        return;
	      }

	      nestedItems.forEach(item => {
	        const panel = getDirectPanel(item);
	        if (!panel || !panel.classList.contains('submenu')) {
	          item.classList.remove('submenu-dropstart');
	          return;
	        }

	        item.classList.remove('submenu-dropstart');

	        const viewportPadding = 16;
	        const rect = panel.getBoundingClientRect();
	        if (rect.right <= window.innerWidth - viewportPadding) {
	          return;
	        }

	        item.classList.add('submenu-dropstart');
	        const flippedRect = panel.getBoundingClientRect();
	        if (flippedRect.left < viewportPadding) {
	          item.classList.remove('submenu-dropstart');
	        }
	      });
	    };

	    const scheduleDesktopSubmenuDirection = () => {
	      window.requestAnimationFrame(refreshDesktopSubmenuDirection);
	    };

	    let navOverlay = document.getElementById('navOverlay');
	    if (!navOverlay) {
	      navOverlay = document.createElement('div');
	      navOverlay.id = 'navOverlay';
	      navOverlay.className = 'nav-overlay';
	      navOverlay.setAttribute('aria-hidden', 'true');
	    }
	    if (navOverlay.parentElement !== headerRoot) {
	      headerRoot.appendChild(navOverlay);
	    }

	    menuToggle.setAttribute('aria-controls', 'mainNav');
	    menuToggle.setAttribute('aria-expanded', 'false');

	    const setMenuOpen = (open) => {
	      mainNav.classList.toggle('active', open);
	      menuToggle.classList.toggle('active', open);
	      navOverlay.classList.toggle('active', open);

	      menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
	      navOverlay.setAttribute('aria-hidden', open ? 'false' : 'true');

	      // Prevent body scroll when menu is open
	      document.body.style.overflow = open ? 'hidden' : '';
	      if (!open) {
	        closeSubmenus();
	      }
	    };

	    menuToggle.addEventListener('click', function() {
	      setMenuOpen(!mainNav.classList.contains('active'));
	    });

	    navOverlay.addEventListener('click', function() {
	      setMenuOpen(false);
	    });

	    document.addEventListener('keydown', function(e) {
	      if (e.key === 'Escape' && mainNav.classList.contains('active')) {
	        setMenuOpen(false);
	      }
	    });

	    window.addEventListener('resize', function() {
	      if (!isMobileMenu() && mainNav.classList.contains('active')) {
	        setMenuOpen(false);
	      }
	      scheduleDesktopSubmenuDirection();
	      scheduleHeaderDensityRefresh();
	    });

	    // Swipe right to close on mobile
	    const SWIPE_CLOSE_THRESHOLD = 80;
	    const SWIPE_MAX_OFF_AXIS = 80;
	    let swipeStartX = 0;
	    let swipeStartY = 0;
	    let swipeLastX = 0;
	    let swipeLastY = 0;
	    let swipeTracking = false;
	    let swipeAxis = null;

	    const onNavTouchStart = (event) => {
	      if (!isMobileMenu() || !mainNav.classList.contains('active')) return;
	      if (!event.touches || event.touches.length !== 1) return;
	      const touch = event.touches[0];
	      swipeTracking = true;
	      swipeAxis = null;
	      swipeStartX = swipeLastX = touch.clientX;
	      swipeStartY = swipeLastY = touch.clientY;
	    };

	    const onNavTouchMove = (event) => {
	      if (!swipeTracking) return;
	      if (!event.touches || event.touches.length !== 1) return;
	      const touch = event.touches[0];
	      swipeLastX = touch.clientX;
	      swipeLastY = touch.clientY;

	      const dx = swipeLastX - swipeStartX;
	      const dy = swipeLastY - swipeStartY;
	      if (swipeAxis === null) {
	        if (Math.abs(dx) < 10 && Math.abs(dy) < 10) return;
	        swipeAxis = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
	      }
	    };

	    const onNavTouchEnd = (event) => {
	      if (!swipeTracking) return;
	      swipeTracking = false;

	      const endTouch = event.changedTouches && event.changedTouches[0] ? event.changedTouches[0] : null;
	      if (endTouch) {
	        swipeLastX = endTouch.clientX;
	        swipeLastY = endTouch.clientY;
	      }

	      if (swipeAxis !== 'x') return;
	      const dx = swipeLastX - swipeStartX;
	      const dy = swipeLastY - swipeStartY;
	      if (dx > SWIPE_CLOSE_THRESHOLD && Math.abs(dy) < SWIPE_MAX_OFF_AXIS) {
	        setMenuOpen(false);
	      }
	    };

	    mainNav.addEventListener('touchstart', onNavTouchStart, { passive: true });
	    mainNav.addEventListener('touchmove', onNavTouchMove, { passive: true });
	    mainNav.addEventListener('touchend', onNavTouchEnd);

	    // Close menu when clicking on a link
	    const navLinks = mainNav.querySelectorAll('a');
	    navLinks.forEach(link => {
	      link.addEventListener('click', function() {
	        setMenuOpen(false);
	      });
	    });

    // Toggle submenus
    const submenuToggles = mainNav.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(toggle => {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const item = toggle.closest('.nav-item');
        if (!item) return;
        const willOpen = !item.classList.contains('submenu-open');
        if (willOpen && isMobileMenu()) {
          closeSiblings(item);
        }

	        if (willOpen) {
	          item.classList.add('submenu-open');
	          toggle.setAttribute('aria-expanded', 'true');
	          openPanel(item);
	        } else {
	          closeItem(item);
	        }
	      });
	    });

    // Close menu when clicking outside
	    document.addEventListener('click', function(e) {
	      if (!mainNav.classList.contains('active')) return;
	      if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
	        setMenuOpen(false);
	      }
	    });

	    scheduleDesktopSubmenuDirection();
	    scheduleHeaderDensityRefresh();
	    window.addEventListener('load', scheduleDesktopSubmenuDirection, { once: true });
	    window.addEventListener('load', scheduleHeaderDensityRefresh, { once: true });
	    if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
	      document.fonts.ready.then(scheduleHeaderDensityRefresh).catch(() => {});
	    }
	  }

  // Language switcher (custom menu)
  const langSwitch = document.querySelector('.lang-switch');
  const langTrigger = document.querySelector('.lang-trigger');
  if (langSwitch && langTrigger) {
    const openMenu = () => langSwitch.classList.add('is-open');
    const closeMenu = () => langSwitch.classList.remove('is-open');
    const canHover = window.matchMedia('(hover: hover) and (pointer: fine)');

    if (canHover.matches) {
      langSwitch.addEventListener('mouseenter', openMenu);
      langSwitch.addEventListener('mouseleave', closeMenu);
    }

    langTrigger.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      langSwitch.classList.toggle('is-open');
    });

    document.addEventListener('click', (event) => {
      if (!langSwitch.contains(event.target)) {
        closeMenu();
      }
    });
  }

  // Header auth dropdown
  const authDropdown = document.querySelector('.header-auth');
  const authTrigger = authDropdown ? authDropdown.querySelector('.header-auth-trigger') : null;
  if (authDropdown && authTrigger) {
    const setAuthOpen = (open) => {
      authDropdown.classList.toggle('is-open', open);
      authDropdown.classList.toggle('active', open);
      authTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const canHover = window.matchMedia('(hover: hover) and (pointer: fine)');
    if (canHover.matches) {
      let authCloseTimer = null;
      const clearAuthCloseTimer = () => {
        if (!authCloseTimer) return;
        clearTimeout(authCloseTimer);
        authCloseTimer = null;
      };

      authDropdown.addEventListener('mouseenter', () => {
        clearAuthCloseTimer();
        setAuthOpen(true);
      });

      authDropdown.addEventListener('mouseleave', () => {
        clearAuthCloseTimer();
        authCloseTimer = setTimeout(() => {
          setAuthOpen(false);
        }, 180);
      });
    }

    authTrigger.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      setAuthOpen(!authDropdown.classList.contains('is-open'));
    });

    document.addEventListener('click', (event) => {
      if (!authDropdown.contains(event.target)) {
        setAuthOpen(false);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setAuthOpen(false);
      }
    });
  }

  // Smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const targetId = this.getAttribute('href');
      if (!targetId || targetId === '#') {
        e.preventDefault();
        return;
      }
      let target = null;
      try {
        target = document.querySelector(targetId);
      } catch (error) {
        target = null;
      }
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

  // Header scroll effect
  const header = document.querySelector('.site-header');
  if (header) {
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 50);
    });
  }

  console.log('FlatCMS Modern Pro frontend initialized');
})();
