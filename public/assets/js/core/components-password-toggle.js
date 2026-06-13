/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    /**
     * Password Toggle Manager
     */
    class PasswordToggle {
        constructor(wrapper) {
            this.wrapper = wrapper;
            this.input = wrapper.querySelector('input[type="password"], input[type="text"]');
            this.button = wrapper.querySelector('[data-action="toggle-password"]');
            
            if (!this.input || !this.button) {
                console.warn('PasswordToggle: Missing input or button element', wrapper);
                return;
            }

            this.isVisible = false;
            this.init();
        }

        init() {
            // Set initial ARIA attributes
            this.button.setAttribute('aria-pressed', 'false');
            this.wrapper.setAttribute('data-visible', 'false');

            // Bind events
            this.button.addEventListener('click', (e) => this.toggle(e));
            
            // Keyboard support (Enter/Space)
            this.button.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggle(e);
                }
            });

            // Prevent form submission when clicking toggle
            this.button.addEventListener('mousedown', (e) => {
                e.preventDefault();
            });
        }

        toggle(event) {
            event.preventDefault();
            event.stopPropagation();

            this.isVisible = !this.isVisible;

            // Toggle input type
            if (this.isVisible) {
                this.input.type = 'text';
                this.wrapper.setAttribute('data-visible', 'true');
                this.button.setAttribute('aria-pressed', 'true');
                
                // Update aria-label if available
                const textVisible = this.button.getAttribute('data-text-visible');
                if (textVisible) {
                    this.button.setAttribute('aria-label', textVisible);
                }
            } else {
                this.input.type = 'password';
                this.wrapper.setAttribute('data-visible', 'false');
                this.button.setAttribute('aria-pressed', 'false');
                
                // Update aria-label if available
                const textHidden = this.button.getAttribute('data-text-hidden');
                if (textHidden) {
                    this.button.setAttribute('aria-label', textHidden);
                }
            }

            // Keep focus on input after toggle
            this.input.focus();

            // Trigger custom event for external listeners
            this.wrapper.dispatchEvent(new CustomEvent('password-visibility-changed', {
                detail: { visible: this.isVisible },
                bubbles: true
            }));
        }

        destroy() {
            // Cleanup if needed
            this.button.removeEventListener('click', this.toggle);
            this.button.removeEventListener('keydown', this.toggle);
        }
    }

    /**
     * Initialize all password toggle components
     */
    function initPasswordToggles() {
        const wrappers = document.querySelectorAll('[data-component="password-toggle"]');
        
        wrappers.forEach(wrapper => {
            // Check if already initialized
            if (wrapper.dataset.initialized === 'true') {
                return;
            }

            new PasswordToggle(wrapper);
            wrapper.dataset.initialized = 'true';
        });
    }

    /**
     * Auto-initialize on DOM ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggles);
    } else {
        initPasswordToggles();
    }

    /**
     * Support for dynamic content (HTMX, AJAX, etc.)
     * Re-initialize when new content is added
     */
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver((mutations) => {
            let shouldReinit = false;

            mutations.forEach(mutation => {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            if (node.matches('[data-component="password-toggle"]') ||
                                node.querySelector('[data-component="password-toggle"]')) {
                                shouldReinit = true;
                            }
                        }
                    });
                }
            });

            if (shouldReinit) {
                initPasswordToggles();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Expose API for manual initialization
     */
    window.FlatCMS = window.FlatCMS || {};
    window.FlatCMS.PasswordToggle = {
        init: initPasswordToggles,
        create: (element) => new PasswordToggle(element)
    };

})();
