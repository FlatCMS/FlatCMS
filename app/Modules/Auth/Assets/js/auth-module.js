/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    function initAutoDismissAlerts() {
        const alerts = document.querySelectorAll('.alert');
        if (!alerts.length) return;

        alerts.forEach(function(alert) {
            // Keep behavior predictable: auto-close flash messages quickly.
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert && alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }, 1500);
        });
    }

    function initPasswordStrength() {
        const containers = document.querySelectorAll('.password-strength');
        if (!containers.length) return;

        containers.forEach(container => {
            const inputId = container.dataset.strengthInput || 'password';
            const input = document.getElementById(inputId);
            if (!input) return;

            const bar = container.querySelector('.password-strength-fill');
            const text = container.querySelector('.password-strength-text');

            const labels = {
                weak: container.dataset.strengthWeak || 'Weak',
                medium: container.dataset.strengthMedium || 'Medium',
                strong: container.dataset.strengthStrong || 'Strong'
            };

            const update = () => {
                const pw = input.value || '';
                if (!pw) {
                    container.classList.remove('is-visible', 'is-weak', 'is-medium', 'is-strong');
                    if (bar) bar.style.width = '0%';
                    if (text) text.textContent = '';
                    return;
                }

                container.classList.add('is-visible');

                let score = 0;
                if (pw.length >= 8) score += 20;
                if (pw.length >= 12) score += 10;
                if (pw.length >= 16) score += 10;
                if (/[a-z]/.test(pw)) score += 10;
                if (/[A-Z]/.test(pw)) score += 15;
                if (/[0-9]/.test(pw)) score += 15;
                if (/[^A-Za-z0-9]/.test(pw)) score += 20;
                score = Math.min(100, score);

                if (bar) bar.style.width = score + '%';

                container.classList.remove('is-weak', 'is-medium', 'is-strong');
                if (score < 40) {
                    container.classList.add('is-weak');
                    if (text) text.textContent = labels.weak;
                } else if (score < 70) {
                    container.classList.add('is-medium');
                    if (text) text.textContent = labels.medium;
                } else {
                    container.classList.add('is-strong');
                    if (text) text.textContent = labels.strong;
                }
            };

            input.addEventListener('input', update);
            update();
        });
    }

    function initPasswordToggle() {
        const buttons = document.querySelectorAll('[data-toggle-password]');
        if (!buttons.length) return;

        buttons.forEach(btn => {
            if (btn.dataset.initialized === 'true') return;
            const targetId = btn.dataset.togglePassword;
            const input = document.getElementById(targetId);
            if (!input) return;

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const icon = btn.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    }
                } else {
                    input.type = 'password';
                    if (icon) {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
                input.focus();
            });

            btn.dataset.initialized = 'true';
        });
    }

    function initPasswordMatch() {
        const indicator = document.getElementById('match-indicator');
        const password = document.getElementById('password');
        const confirm = document.getElementById('password_confirmation');
        if (!indicator || !password || !confirm) return;

        const update = () => {
            if (confirm.value.length > 0 && confirm.value === password.value) {
                indicator.classList.add('visible');
            } else {
                indicator.classList.remove('visible');
            }
        };

        password.addEventListener('input', update);
        confirm.addEventListener('input', update);
        update();
    }

    function initProfileLicenses() {
        const configNode = document.getElementById('authProfileLicensesConfig');
        if (!configNode) return;

        let config = {};
        try {
            config = JSON.parse(configNode.dataset.authProfileLicenses || '{}');
        } catch (error) {
            config = {};
        }

        const csrfToken = config.csrfToken || '';
        const modal = document.getElementById('licenseRevealModal');
        const modalTitle = document.getElementById('licenseRevealModalTitle');
        const modalIntro = document.getElementById('licenseRevealModalIntro');
        const codeInput = document.getElementById('licenseRevealCodeInput');
        const codeHint = document.getElementById('licenseRevealCodeHint');
        const verifyBtn = document.getElementById('licenseRevealVerifyBtn');
        const resendBtn = document.getElementById('licenseRevealResendBtn');
        const keyGroup = document.getElementById('licenseRevealKeyGroup');
        const keyInput = document.getElementById('licenseRevealKeyInput');
        const copyBtn = document.getElementById('licenseRevealCopyBtn');
        const devBlock = document.getElementById('licenseRevealDevBlock');
        const devCode = document.getElementById('licenseRevealDevCode');
        const revealButtons = document.querySelectorAll('[data-license-reveal]');

        if (!modal || !codeInput || !verifyBtn || !resendBtn || !keyGroup || !keyInput || !copyBtn) {
            return;
        }

        let activeModule = '';
        let activeRequestUrl = '';
        let activeVerifyUrl = '';
        let activeTitle = '';

        function toast(message, type) {
            if (!message) return;
            if (window.FlatCMS && window.FlatCMS.toast && typeof window.FlatCMS.toast.show === 'function') {
                window.FlatCMS.toast.show(message, type || 'success');
                return;
            }
            window.alert(message);
        }

        function openModal() {
            modal.classList.remove('is-initially-hidden');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function resetModalState() {
            if (codeInput) codeInput.value = '';
            if (codeHint) codeHint.textContent = '';
            if (keyInput) keyInput.value = '';
            if (keyGroup) keyGroup.classList.add('hidden');
            if (devCode) devCode.textContent = '';
            if (devBlock) devBlock.classList.add('hidden');
        }

        function setLoading(button, isLoading) {
            if (!button) return;
            if (isLoading) {
                button.dataset.originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';
                return;
            }

            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
                delete button.dataset.originalHtml;
            }
            button.disabled = false;
        }

        function requestJson(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload || {})
            }).then(function(response) {
                return response.json().catch(function() {
                    return {};
                }).then(function(data) {
                    if (!response.ok) {
                        const error = new Error(data.message || 'Request failed');
                        error.payload = data;
                        throw error;
                    }
                    return data;
                });
            });
        }

        function updateModalTitle() {
            if (!modalTitle) return;
            const icon = '<i class="fas fa-key modal-icon-info"></i>';
            modalTitle.innerHTML = icon + ' ' + activeTitle;
        }

        function openRevealFlow(button) {
            activeModule = button.dataset.licenseReveal || '';
            activeRequestUrl = button.dataset.licenseRequestUrl || '';
            activeVerifyUrl = button.dataset.licenseVerifyUrl || '';
            activeTitle = button.dataset.licenseTitle || '';

            if (!activeModule || !activeRequestUrl || !activeVerifyUrl) {
                return;
            }

            resetModalState();
            updateModalTitle();
            openModal();
            setLoading(button, true);

            requestJson(activeRequestUrl, { module: activeModule })
                .then(function(data) {
                    if (modalIntro) {
                        modalIntro.textContent = data.message || '';
                    }
                    if (codeHint) {
                        codeHint.textContent = data.masked_email || '';
                    }
                    if (data.dev_code && devBlock && devCode) {
                        devCode.textContent = data.dev_code;
                        devBlock.classList.remove('hidden');
                    }
                    codeInput.focus();
                })
                .catch(function(error) {
                    toast((error.payload && error.payload.message) || error.message || '', 'error');
                    if (window.FlatCMS && window.FlatCMS.modal && typeof window.FlatCMS.modal.close === 'function') {
                        window.FlatCMS.modal.close('licenseRevealModal');
                    } else {
                        modal.style.display = 'none';
                        modal.setAttribute('aria-hidden', 'true');
                        document.body.style.overflow = '';
                    }
                })
                .finally(function() {
                    setLoading(button, false);
                });
        }

        revealButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                openRevealFlow(button);
            });
        });

        verifyBtn.addEventListener('click', function() {
            if (!activeVerifyUrl) return;
            const code = (codeInput.value || '').trim();
            if (!code) {
                if (codeInput) codeInput.focus();
                return;
            }

            setLoading(verifyBtn, true);
            requestJson(activeVerifyUrl, { code: code })
                .then(function(data) {
                    if (modalIntro) {
                        modalIntro.textContent = data.message || '';
                    }
                    if (keyInput) {
                        keyInput.value = data.key || '';
                    }
                    if (keyGroup) {
                        keyGroup.classList.remove('hidden');
                    }
                    toast(data.message || '', 'success');
                })
                .catch(function(error) {
                    toast((error.payload && error.payload.message) || error.message || '', 'error');
                })
                .finally(function() {
                    setLoading(verifyBtn, false);
                });
        });

        resendBtn.addEventListener('click', function() {
            if (!activeRequestUrl) return;

            setLoading(resendBtn, true);
            requestJson(activeRequestUrl, { module: activeModule })
                .then(function(data) {
                    if (modalIntro) {
                        modalIntro.textContent = data.message || '';
                    }
                    if (codeHint) {
                        codeHint.textContent = data.masked_email || '';
                    }
                    if (data.dev_code && devBlock && devCode) {
                        devCode.textContent = data.dev_code;
                        devBlock.classList.remove('hidden');
                    } else if (devBlock) {
                        devBlock.classList.add('hidden');
                    }
                    toast(data.message || '', 'success');
                })
                .catch(function(error) {
                    toast((error.payload && error.payload.message) || error.message || '', 'error');
                })
                .finally(function() {
                    setLoading(resendBtn, false);
                });
        });

        copyBtn.addEventListener('click', function() {
            const value = (keyInput.value || '').trim();
            if (!value) return;

            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                navigator.clipboard.writeText(value)
                    .then(function() {
                        toast(config.copySuccess || '', 'success');
                    })
                    .catch(function() {
                        toast(config.copyError || '', 'error');
                    });
                return;
            }

            toast(config.copyError || '', 'error');
        });

        codeInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                verifyBtn.click();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                resetModalState();
            }
        });

        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                resetModalState();
            }
        });

        document.querySelectorAll('[data-modal-close="licenseRevealModal"]').forEach(function(button) {
            button.addEventListener('click', function() {
                resetModalState();
            });
        });
    }

    function init() {
        initAutoDismissAlerts();
        initPasswordStrength();
        initPasswordToggle();
        initPasswordMatch();
        initProfileLicenses();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
