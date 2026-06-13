/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
  'use strict';

  const documentBody = document.body;
  const copyLabel = String((documentBody && documentBody.dataset.copyLabel) || '').trim();
  const copiedLabel = String((documentBody && documentBody.dataset.copiedLabel) || '').trim();
  const sendingLabel = String((documentBody && documentBody.dataset.sendingLabel) || '').trim();

  // ============================================
  // MOBILE NAVIGATION
  // ============================================
  const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
  const mainNav = document.querySelector('.main-nav');

  if (mobileMenuToggle && mainNav) {
    mobileMenuToggle.addEventListener('click', function() {
      mainNav.classList.toggle('active');
      mobileMenuToggle.classList.toggle('active');
    });
  }

  // ============================================
  // HEADER SCROLL EFFECT
  // ============================================
  const header = document.querySelector('.site-header');
  
  if (header) {
    let lastScroll = 0;
    
    window.addEventListener('scroll', function() {
      const currentScroll = window.pageYOffset;
      
      if (currentScroll > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
      
      // Hide/show header on scroll direction
      if (currentScroll > lastScroll && currentScroll > 200) {
        header.classList.add('hidden');
      } else {
        header.classList.remove('hidden');
      }
      
      lastScroll = currentScroll;
    });
  }

  // ============================================
  // BACK TO TOP BUTTON
  // ============================================
  const backToTop = document.querySelector('.back-to-top');
  
  if (backToTop) {
    window.addEventListener('scroll', function() {
      if (window.pageYOffset > 500) {
        backToTop.classList.add('visible');
      } else {
        backToTop.classList.remove('visible');
      }
    });
    
    backToTop.addEventListener('click', function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  // ============================================
  // SMOOTH SCROLL FOR ANCHOR LINKS
  // ============================================
  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;
      
      const target = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // ============================================
  // LAZY LOADING IMAGES
  // ============================================
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver(function(entries, observer) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.remove('img-placeholder');
          observer.unobserve(img);
        }
      });
    });

    document.querySelectorAll('img[data-src]').forEach(function(img) {
      imageObserver.observe(img);
    });
  }

  // ============================================
  // SCROLL ANIMATIONS
  // ============================================
  if ('IntersectionObserver' in window) {
    const animationObserver = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-in');
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    });

    document.querySelectorAll('[data-animate]').forEach(function(element) {
      animationObserver.observe(element);
    });
  }

  // ============================================
  // FORM HANDLING
  // ============================================
  document.querySelectorAll('form.contact-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      const submitBtn = form.querySelector('[type="submit"]');
      if (submitBtn) {
        submitBtn.disabled = true;
        const spinner = document.createElement('span');
        spinner.className = 'spinner spinner-sm';
        submitBtn.replaceChildren(spinner);
        if (sendingLabel !== '') {
          submitBtn.append(document.createTextNode(` ${sendingLabel}`));
        }
      }
    });
  });

  // ============================================
  // SHARE BUTTONS
  // ============================================
  document.querySelectorAll('[data-share]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const platform = btn.dataset.share;
      const url = encodeURIComponent(window.location.href);
      const title = encodeURIComponent(document.title);
      
      let shareUrl = '';
      
      switch (platform) {
        case 'twitter':
          shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
          break;
        case 'facebook':
          shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
          break;
        case 'linkedin':
          shareUrl = `https://www.linkedin.com/shareArticle?mini=true&url=${url}&title=${title}`;
          break;
      }
      
      if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
      }
    });
  });

  // ============================================
  // READING PROGRESS BAR
  // ============================================
  const progressBar = document.querySelector('.reading-progress');
  
  if (progressBar) {
    window.addEventListener('scroll', function() {
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const progress = (window.pageYOffset / docHeight) * 100;
      progressBar.style.width = progress + '%';
    });
  }

  // ============================================
  // COPY CODE BLOCKS
  // ============================================
  document.querySelectorAll('pre code').forEach(function(codeBlock) {
    const wrapper = codeBlock.closest('pre');
    if (!wrapper || copyLabel === '' || copiedLabel === '') {
      return;
    }
    const copyBtn = document.createElement('button');
    copyBtn.className = 'copy-code-btn';
    copyBtn.textContent = copyLabel;
    
    copyBtn.addEventListener('click', function() {
      navigator.clipboard.writeText(codeBlock.textContent).then(function() {
        copyBtn.textContent = copiedLabel;
        setTimeout(() => {
          copyBtn.textContent = copyLabel;
        }, 2000);
      });
    });
    
    wrapper.style.position = 'relative';
    wrapper.appendChild(copyBtn);
  });

  // ============================================
  // INIT COMPLETE
  // ============================================
  console.log('FlatCMS Modern Pro frontend theme initialized');

})();
