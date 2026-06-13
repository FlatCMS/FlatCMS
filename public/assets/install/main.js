/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    const root = document.documentElement;
    const body = document.body;
    const loadingLabel = (body && body.dataset && body.dataset.loadingLabel) ? body.dataset.loadingLabel : 'Loading...';
    const copiedLabel = (body && body.dataset && body.dataset.commandCopiedLabel) ? body.dataset.commandCopiedLabel : 'Command copied';

    // ============================================
    // Auto-submit forms avec animation
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        let formModified = false;
        let bypassBeforeUnload = false;
        const markBypassBeforeUnload = function() {
            bypassBeforeUnload = true;
            formModified = false;
        };

        // Initialiser la barre de progression (valeur stockée en data-attribute)
        const progressFills = document.querySelectorAll('.install-progress-fill[data-progress]');
        progressFills.forEach(fill => {
            const progress = Number(fill.getAttribute('data-progress') || 0);
            const safeProgress = Number.isFinite(progress) ? Math.max(0, Math.min(100, progress)) : 0;
            fill.style.width = safeProgress + '%';
        });

        // Auto-submit des champs configurés (langue install)
        const autoSubmitFields = document.querySelectorAll('[data-action="submit-on-change"]');
        autoSubmitFields.forEach(field => {
            field.addEventListener('change', function() {
                if (this.form) {
                    markBypassBeforeUnload();
                    if (typeof this.form.requestSubmit === 'function') {
                        this.form.requestSubmit();
                    } else {
                        this.form.submit();
                    }
                }
            });
        });

        const langForm = document.getElementById('lang-form');
        if (langForm) {
            langForm.addEventListener('submit', markBypassBeforeUnload);
        }

        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const themeModeIcon = document.getElementById('installThemeModeIcon');
        const syncInstallThemeVisualState = function(isDark) {
            root.classList.toggle('dark', isDark);
            if (darkModeToggle) {
                darkModeToggle.checked = isDark;
            }
            if (themeModeIcon) {
                themeModeIcon.classList.remove('fa-sun', 'fa-moon');
                themeModeIcon.classList.add(isDark ? 'fa-moon' : 'fa-sun');
            }
        };

        if (darkModeToggle) {
            syncInstallThemeVisualState(root.classList.contains('dark'));
            darkModeToggle.addEventListener('change', function() {
                const isDark = !!this.checked;
                syncInstallThemeVisualState(isDark);
                try {
                    localStorage.setItem('flatcms_install_dark', isDark ? 'true' : 'false');
                } catch (error) {
                    // Ignore localStorage errors (private mode / restricted env)
                }
            });
        }

        // Gestion des fallbacks d'images (sans onerror inline)
        const fallbackImages = document.querySelectorAll('img[data-install-fallback]');
        fallbackImages.forEach(img => {
            img.addEventListener('error', function onImageError() {
                const fallbackId = this.getAttribute('data-install-fallback');
                if (!fallbackId) {
                    return;
                }

                const fallbackNode = document.getElementById(fallbackId);
                if (!fallbackNode) {
                    return;
                }

                this.classList.add('hidden');
                fallbackNode.hidden = false;
            }, { once: true });
        });
        
        // Ajouter un loader sur les soumissions de formulaire
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> ' + loadingLabel;
                }
            });
        });

        // Actions UI via data-action (sans handlers inline)
        document.addEventListener('click', function(event) {
            const trigger = event.target.closest('[data-action]');
            if (!trigger) {
                return;
            }

            const action = trigger.getAttribute('data-action');
            if (!action) {
                return;
            }

            if (action === 'reload-page') {
                event.preventDefault();
                window.location.reload();
                return;
            }

            if (action === 'copy-command') {
                event.preventDefault();
                const command = trigger.getAttribute('data-cmd') || '';
                if (!command) {
                    return;
                }
                navigator.clipboard.writeText(command).then(() => {
                    if (typeof window.showToast === 'function') {
                        window.showToast(copiedLabel, 'success');
                    }
                }).catch(() => {});
                return;
            }

            if (action === 'toggle-checkbox') {
                event.preventDefault();
                const targetId = trigger.getAttribute('data-target');
                if (!targetId) {
                    return;
                }
                const checkbox = document.getElementById(targetId);
                if (!checkbox || checkbox.disabled) {
                    return;
                }
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                return;
            }

            if (action === 'toggle-password') {
                event.preventDefault();
                const targetId = trigger.getAttribute('data-toggle-target');
                if (!targetId) {
                    return;
                }
                const input = document.getElementById(targetId);
                if (!input) {
                    return;
                }
                const icon = trigger.querySelector('i');
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                if (icon) {
                    icon.classList.toggle('fa-eye', !isPassword);
                    icon.classList.toggle('fa-eye-slash', isPassword);
                }
            }
        });

        // Preview en direct (étape configuration site)
        const siteNameInput = document.getElementById('site_name');
        const siteDescriptionInput = document.getElementById('site_description');
        const siteUrlInput = document.getElementById('site_url');
        const previewName = document.getElementById('preview_name');
        const previewDesc = document.getElementById('preview_desc');
        const previewUrl = document.getElementById('preview_url');

        if (previewName || previewDesc || previewUrl) {
            const updateSitePreview = () => {
                if (previewName) {
                    const fallback = previewName.getAttribute('data-preview-default') || 'FlatCMS';
                    const value = (siteNameInput && siteNameInput.value.trim()) ? siteNameInput.value.trim() : fallback;
                    previewName.textContent = value;
                }

                if (previewDesc) {
                    const fallback = previewDesc.getAttribute('data-preview-default') || '';
                    const value = (siteDescriptionInput && siteDescriptionInput.value.trim()) ? siteDescriptionInput.value.trim() : fallback;
                    previewDesc.textContent = value;
                }

                if (previewUrl) {
                    const fallback = previewUrl.getAttribute('data-preview-default') || '';
                    const value = (siteUrlInput && siteUrlInput.value.trim()) ? siteUrlInput.value.trim() : fallback;
                    previewUrl.textContent = value;
                }
            };

            [siteNameInput, siteDescriptionInput, siteUrlInput].forEach(field => {
                if (!field) return;
                field.addEventListener('input', updateSitePreview);
                field.addEventListener('change', updateSitePreview);
            });
            updateSitePreview();
        }

        // ============================================
        // Validation en temps réel
        // ============================================
        const passwordInput = document.querySelector('input[name="admin_password"]');
        const passwordConfirm = document.querySelector('input[name="admin_password_confirm"]');
        
        if (passwordInput && passwordConfirm) {
            const validatePasswords = function() {
                if (passwordConfirm.value.length > 0) {
                    if (passwordInput.value === passwordConfirm.value) {
                        passwordConfirm.classList.remove('border-red-500');
                        passwordConfirm.classList.add('border-green-500');
                    } else {
                        passwordConfirm.classList.remove('border-green-500');
                        passwordConfirm.classList.add('border-red-500');
                    }
                }
            };
            
            passwordInput.addEventListener('input', validatePasswords);
            passwordConfirm.addEventListener('input', validatePasswords);
        }

        // ============================================
        // Force de mot de passe
        // ============================================
        if (passwordInput) {
            const strengthIndicator = document.getElementById('password-strength');
            
            if (strengthIndicator) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    
                    if (password.length >= 8) strength++;
                    if (password.length >= 12) strength++;
                    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                    if (/[0-9]/.test(password)) strength++;
                    if (/[^a-zA-Z0-9]/.test(password)) strength++;
                    
                    const levels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
                    const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500', 'bg-emerald-500'];
                    
                    strengthIndicator.className = 'h-2 rounded-full transition-all ' + (colors[strength] || 'bg-gray-300');
                    strengthIndicator.style.width = (strength * 20) + '%';
                });
            }
        }

        // ============================================
        // Smooth scroll vers les erreurs
        // ============================================
        const errorElement = document.querySelector('.alert-error');
        if (errorElement) {
            errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // ============================================
        // Animation des cartes
        // ============================================
        const cards = document.querySelectorAll('.card-hover');
        cards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
            card.classList.add('fade-in');
        });

        // ============================================
        // Détection automatique de timezone
        // ============================================
        const timezoneSelect = document.querySelector('select[name="timezone"]');
        if (timezoneSelect) {
            const detectedTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            
            // Chercher l'option correspondante
            for (let option of timezoneSelect.options) {
                if (option.value === detectedTimezone) {
                    option.selected = true;
                    break;
                }
            }
        }

        // ============================================
        // Auto-remplissage URL du site
        // ============================================
        if (siteUrlInput && !siteUrlInput.value) {
            siteUrlInput.value = window.location.origin + window.location.pathname.replace(/\/install.*/, '');
        }

        // ============================================
        // Confirmation avant navigation (si formulaire modifié)
        // ============================================
        const trackedPostForms = Array.from(forms).filter(form => {
            const method = (form.getAttribute('method') || 'get').toLowerCase();
            return method === 'post';
        });

        const inputs = trackedPostForms.reduce((acc, form) => {
            return acc.concat(Array.from(form.querySelectorAll('input, textarea, select')));
        }, []);
        const trackField = function(field) {
            if (!field) {
                return false;
            }
            if (field.id === 'darkModeToggle') {
                return false;
            }
            if (field.closest('#lang-form')) {
                return false;
            }
            return true;
        };
        
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                if (trackField(this)) {
                    formModified = true;
                }
            });
        });

        window.addEventListener('beforeunload', function(e) {
            if (!formModified || bypassBeforeUnload) {
                return;
            }
            e.preventDefault();
            e.returnValue = '';
        });

        // Désactiver l'avertissement lors de la soumission
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                if (this.id === 'lang-form') {
                    markBypassBeforeUnload();
                }
                formModified = false;
            });
        });

        // ============================================
        // Animation de progression des étapes
        // ============================================
        const progressSteps = document.querySelectorAll('.step-indicator');
        progressSteps.forEach((step, index) => {
            step.style.animationDelay = (index * 0.05) + 's';
        });

        // ============================================
        // Vérification en temps réel (Requirements)
        // ============================================
        const requirementChecks = document.querySelectorAll('[data-requirement]');
        if (requirementChecks.length > 0) {
            // Ajouter des icônes animées
            requirementChecks.forEach(check => {
                const icon = check.querySelector('i');
                if (icon && icon.classList.contains('fa-check')) {
                    setTimeout(() => {
                        icon.style.animation = 'fadeIn 0.5s ease-in-out';
                    }, 100);
                }
            });
        }

        // ============================================
        // Copy to clipboard (pour la page finale)
        // ============================================
        const copyButtons = document.querySelectorAll('[data-copy]');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const textToCopy = this.getAttribute('data-copy');
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check mr-2"></i> ' + copiedLabel;
                    this.classList.add('bg-green-600');
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.classList.remove('bg-green-600');
                    }, 2000);
                });
            });
        });

        // ============================================
        // Config modal (page finale)
        // ============================================
        const configModal = document.getElementById('config-modal');
        if (configModal) {
            const modalTitle = document.getElementById('config-modal-title');
            const modalPath = document.getElementById('config-modal-path');
            const modalBody = document.getElementById('config-modal-body');
            const emptyText = configModal.getAttribute('data-config-empty') || 'No content';
            const openButtons = document.querySelectorAll('[data-config-open]');
            const closeButtons = configModal.querySelectorAll('[data-config-close]');
            const copyBtn = configModal.querySelector('[data-config-copy]');

            const openModal = (btn) => {
                const id = btn.getAttribute('data-config-id');
                const title = btn.getAttribute('data-config-title') || '';
                const path = btn.getAttribute('data-config-path') || '';
                const pre = document.getElementById('config-' + id);
                const content = pre ? pre.textContent.trim() : '';

                modalTitle.textContent = title;
                modalPath.textContent = path;
                modalBody.textContent = content || emptyText;
                configModal.classList.remove('hidden');
                configModal.classList.add('flex');
            };

            const closeModal = () => {
                configModal.classList.add('hidden');
                configModal.classList.remove('flex');
            };

            openButtons.forEach(btn => {
                btn.addEventListener('click', () => openModal(btn));
            });
            closeButtons.forEach(btn => {
                btn.addEventListener('click', closeModal);
            });
            configModal.addEventListener('click', (e) => {
                if (e.target === configModal) closeModal();
            });
            if (copyBtn) {
                copyBtn.addEventListener('click', () => {
                    navigator.clipboard.writeText(modalBody.textContent).then(() => {
                        const original = copyBtn.innerHTML;
                        copyBtn.innerHTML = '<i class="fas fa-check mr-2"></i> ' + copiedLabel;
                        copyBtn.classList.add('bg-green-600', 'text-white');
                        setTimeout(() => {
                            copyBtn.innerHTML = original;
                            copyBtn.classList.remove('bg-green-600', 'text-white');
                        }, 1500);
                    });
                });
            }
        }

        // ============================================
        // Easter Egg (Konami Code)
        // ============================================
        let konamiCode = [];
        const konamiSequence = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65];
        
        document.addEventListener('keydown', function(e) {
            konamiCode.push(e.keyCode);
            konamiCode = konamiCode.slice(-10);
            
            if (konamiCode.toString() === konamiSequence.toString()) {
                document.body.classList.add('install-rainbow');
                setTimeout(() => {
                    document.body.classList.remove('install-rainbow');
                }, 5000);
            }
        });

    });

    // ============================================
    // Helper: Afficher un message toast
    // ============================================
    window.showToast = function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 fade-in`;
        
        const colors = {
            success: 'bg-green-600',
            error: 'bg-red-600',
            warning: 'bg-orange-600',
            info: 'bg-blue-600'
        };
        
        toast.classList.add(colors[type] || colors.info);
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

})();
