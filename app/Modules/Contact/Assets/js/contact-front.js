/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(() => {
    const resolveCsrfToken = () => {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (!tokenMeta) {
            return '';
        }

        return String(tokenMeta.getAttribute('content') || '').trim();
    };

    const appendHidden = (form, name, value) => {
        let input = form.querySelector(`input[name="${name}"]`);
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            form.appendChild(input);
        }

        input.value = value;
    };

    const getRequiredMessage = (form) => {
        const custom = String(form.getAttribute('data-validation-required') || '').trim();
        return custom;
    };

    const getFieldContainer = (field) => {
        if (!(field instanceof HTMLElement)) {
            return null;
        }

        return field.closest('.form-group') || field.parentElement;
    };

    const shouldValidateRequiredField = (field) => {
        if (!(field instanceof HTMLElement)) {
            return false;
        }

        if (field.closest('.cf-turnstile')) {
            return false;
        }

        if (field instanceof HTMLInputElement) {
            const type = String(field.type || '').trim().toLowerCase();
            if (type === 'hidden' || type === 'submit' || type === 'button' || type === 'reset') {
                return false;
            }

            if (String(field.name || '').trim() === 'cf-turnstile-response') {
                return false;
            }
        }

        return true;
    };

    const getVisualTarget = (field) => {
        if (!(field instanceof HTMLElement)) {
            return null;
        }

        if (field instanceof HTMLInputElement && field.type === 'radio') {
            return field.closest('.flatcms-contact-choice-list') || field.closest('.flatcms-contact-choice-item') || field;
        }

        if (field instanceof HTMLInputElement && field.type === 'checkbox') {
            return field.closest('.flatcms-contact-choice-item') || field;
        }

        if (field instanceof HTMLInputElement && field.type === 'file') {
            const group = field.closest('[data-contact-attachments]');
            if (group) {
                return group.querySelector('[data-contact-dropzone]') || field;
            }
        }

        return field;
    };

    const clearFieldError = (field) => {
        const container = getFieldContainer(field);
        const visualTarget = getVisualTarget(field);
        if (visualTarget && visualTarget.classList) {
            visualTarget.classList.remove('is-invalid');
        }
        if (field && field.classList) {
            field.classList.remove('is-invalid');
            field.setAttribute('aria-invalid', 'false');
        }

        if (!container) {
            return;
        }

        const errorNode = container.querySelector('[data-contact-field-error]');
        if (errorNode) {
            errorNode.remove();
        }
    };

    const showFieldError = (field, message) => {
        const container = getFieldContainer(field);
        const visualTarget = getVisualTarget(field);

        clearFieldError(field);

        if (visualTarget && visualTarget.classList) {
            visualTarget.classList.add('is-invalid');
        }
        if (field && field.classList) {
            field.classList.add('is-invalid');
            field.setAttribute('aria-invalid', 'true');
        }

        if (!container) {
            return;
        }

        const errorNode = document.createElement('small');
        errorNode.className = 'flatcms-contact-field-error';
        errorNode.setAttribute('data-contact-field-error', '1');
        errorNode.textContent = message;
        container.appendChild(errorNode);
    };

    const validateRequiredField = (field, message) => {
        if (!(field instanceof HTMLElement)) {
            return true;
        }

        if ('disabled' in field && field.disabled) {
            return true;
        }

        if (field instanceof HTMLInputElement) {
            if (field.type === 'radio') {
                return true;
            }

            if (field.type === 'checkbox') {
                if (!field.checked) {
                    showFieldError(field, message);
                    return false;
                }
                clearFieldError(field);
                return true;
            }

            if (field.type === 'file') {
                if (!field.files || field.files.length === 0) {
                    showFieldError(field, message);
                    return false;
                }
                clearFieldError(field);
                return true;
            }
        }

        const rawValue = String((field.value ?? '')).trim();

        if (rawValue === '') {
            showFieldError(field, message);
            return false;
        }

        clearFieldError(field);
        return true;
    };

    const focusInvalidField = (field) => {
        if (!(field instanceof HTMLElement)) {
            return;
        }

        if (field instanceof HTMLInputElement && field.type === 'file') {
            const dropzone = getVisualTarget(field);
            if (dropzone instanceof HTMLElement) {
                dropzone.focus();
                return;
            }
        }

        field.focus();
    };

    const validateRequiredFields = (form) => {
        const requiredMessage = getRequiredMessage(form);
        const requiredFields = Array.from(form.querySelectorAll('[required]')).filter((field) => shouldValidateRequiredField(field));
        const handledRadioNames = new Set();
        let firstInvalid = null;

        requiredFields.forEach((field) => {
            if (field instanceof HTMLInputElement && field.type === 'radio') {
                const radioName = String(field.name || '').trim();
                if (radioName === '' || handledRadioNames.has(radioName)) {
                    return;
                }
                handledRadioNames.add(radioName);

                const radioGroup = requiredFields.filter((item) => item instanceof HTMLInputElement && item.type === 'radio' && item.name === radioName);
                const checked = radioGroup.some((item) => item.checked);
                if (!checked) {
                    radioGroup.forEach((item) => showFieldError(item, requiredMessage));
                    if (!firstInvalid) {
                        firstInvalid = radioGroup[0];
                    }
                    return;
                }

                radioGroup.forEach((item) => clearFieldError(item));
                return;
            }

            const valid = validateRequiredField(field, requiredMessage);
            if (!valid && !firstInvalid) {
                firstInvalid = field;
            }
        });

        if (firstInvalid) {
            focusInvalidField(firstInvalid);
            return false;
        }

        return true;
    };

    const bindLiveValidation = (form) => {
        if (form.hasAttribute('data-contact-validation-bound')) {
            return;
        }

        form.setAttribute('data-contact-validation-bound', '1');
        const requiredFields = Array.from(form.querySelectorAll('[required]')).filter((field) => shouldValidateRequiredField(field));

        requiredFields.forEach((field) => {
            if (!(field instanceof HTMLElement)) {
                return;
            }

            const eventName = field instanceof HTMLInputElement && (field.type === 'checkbox' || field.type === 'radio' || field.type === 'file')
                ? 'change'
                : 'input';

            field.addEventListener(eventName, () => {
                if (field instanceof HTMLInputElement && field.type === 'radio') {
                    const name = String(field.name || '').trim();
                    if (name === '') {
                        clearFieldError(field);
                        return;
                    }
                    const radios = requiredFields.filter((item) => item instanceof HTMLInputElement && item.type === 'radio' && item.name === name);
                    radios.forEach((radio) => clearFieldError(radio));
                    return;
                }

                clearFieldError(field);
            });
        });
    };

    const setupContactForm = (form, csrfToken) => {
        if (csrfToken !== '' && !form.querySelector('input[name="_token"]')) {
            appendHidden(form, '_token', csrfToken);
        }

        if (!form.querySelector('input[name="source_url"]')) {
            appendHidden(form, 'source_url', window.location.href);
        }

        if (!form.querySelector('input[name="company"]')) {
            appendHidden(form, 'company', '');
        }

        form.setAttribute('novalidate', 'novalidate');
        bindLiveValidation(form);

        if (!form.hasAttribute('data-contact-submit-bound')) {
            form.addEventListener('submit', (event) => {
                if (!validateRequiredFields(form)) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
            form.setAttribute('data-contact-submit-bound', '1');
        }
    };

    const parseAcceptTokens = (acceptValue) => String(acceptValue || '')
        .split(',')
        .map((token) => token.trim().toLowerCase())
        .filter(Boolean);

    const fileMatchesAccept = (file, acceptTokens) => {
        if (!acceptTokens.length) {
            return true;
        }

        const name = String(file.name || '').toLowerCase();
        const type = String(file.type || '').toLowerCase();

        return acceptTokens.some((token) => {
            if (token.startsWith('.')) {
                return name.endsWith(token);
            }
            if (token.endsWith('/*')) {
                const mimePrefix = token.slice(0, -1);
                return type.startsWith(mimePrefix);
            }
            return type === token;
        });
    };

    const fileSignature = (file) => [
        String(file.name || ''),
        String(file.size || 0),
        String(file.lastModified || 0),
        String(file.type || ''),
    ].join('::');

    const setupAttachmentInput = (input) => {
        if (!(input instanceof HTMLInputElement) || input.type !== 'file') {
            return;
        }

        const wrapper = input.closest('[data-contact-attachments]') || input.closest('.form-group');
        if (!wrapper || wrapper.getAttribute('data-contact-attachments-ready') === '1') {
            return;
        }

        wrapper.setAttribute('data-contact-attachments-ready', '1');

        const linkedLabel = input.id ? wrapper.querySelector(`label[for="${input.id}"]`) : null;
        const dropTitle = String(input.getAttribute('data-drop-title') || '').trim() || String(linkedLabel ? linkedLabel.textContent : '').trim();
        const dropHint = String(input.getAttribute('data-drop-hint') || '').trim();
        const selectedNone = String(input.getAttribute('data-selected-none') || '').trim();
        const selectedCountPattern = String(input.getAttribute('data-selected-count') || '').trim() || ':count';
        const removeLabel = String(input.getAttribute('data-remove-label') || '').trim();
        const allowMultiple = input.hasAttribute('multiple');
        const maxFiles = Math.max(1, Number.parseInt(String(input.getAttribute('data-max-files') || '1'), 10) || 1);
        const canMutateFileList = typeof window.DataTransfer === 'function';
        const acceptTokens = parseAcceptTokens(input.getAttribute('accept'));
        const selectedFiles = [];

        const dropzone = document.createElement('button');
        dropzone.type = 'button';
        dropzone.className = 'flatcms-contact-dropzone';
        dropzone.setAttribute('data-contact-dropzone', '1');

        const iconWrap = document.createElement('span');
        iconWrap.className = 'flatcms-contact-dropzone-icon';
        iconWrap.setAttribute('aria-hidden', 'true');
        iconWrap.innerHTML = '<i class="fas fa-cloud-upload-alt"></i>';

        dropzone.appendChild(iconWrap);
        if (dropTitle !== '') {
            const titleText = document.createElement('span');
            titleText.className = 'flatcms-contact-dropzone-text';
            titleText.textContent = dropTitle;
            dropzone.appendChild(titleText);
        }
        if (dropHint !== '') {
            const hintText = document.createElement('span');
            hintText.className = 'flatcms-contact-dropzone-hint';
            hintText.textContent = dropHint;
            dropzone.appendChild(hintText);
        }

        const feedback = document.createElement('div');
        feedback.className = 'flatcms-contact-attachments-feedback';
        feedback.setAttribute('data-contact-attachments-feedback', '1');

        const list = document.createElement('ul');
        list.className = 'flatcms-contact-attachments-list';
        list.setAttribute('data-contact-attachments-list', '1');

        input.insertAdjacentElement('beforebegin', dropzone);
        input.insertAdjacentElement('afterend', feedback);
        feedback.insertAdjacentElement('afterend', list);
        wrapper.classList.add('flatcms-contact-attachments', 'is-enhanced');

        const syncNativeFileInput = () => {
            if (!canMutateFileList) {
                return;
            }

            const transfer = new window.DataTransfer();
            selectedFiles.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
        };

        const renderSelectedFiles = () => {
            list.innerHTML = '';
            const count = selectedFiles.length;

            if (!count) {
                feedback.textContent = selectedNone;
                list.hidden = true;
                return;
            }

            feedback.textContent = selectedCountPattern.includes(':count')
                ? selectedCountPattern.replace(':count', String(count))
                : selectedCountPattern;

            selectedFiles.forEach((file, index) => {
                const item = document.createElement('li');
                item.className = 'flatcms-contact-attachments-item';

                const name = document.createElement('span');
                name.className = 'flatcms-contact-attachments-name';
                name.textContent = String(file.name || '');

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'flatcms-contact-attachments-remove';
                removeBtn.setAttribute('data-contact-remove-index', String(index));
                if (removeLabel !== '') {
                    removeBtn.setAttribute('aria-label', removeLabel);
                    removeBtn.setAttribute('title', removeLabel);
                }
                removeBtn.innerHTML = '<i class="fas fa-xmark"></i>';

                item.appendChild(name);
                item.appendChild(removeBtn);
                list.appendChild(item);
            });

            list.hidden = false;
        };

        const commitFiles = (files) => {
            selectedFiles.splice(0, selectedFiles.length, ...files);
            syncNativeFileInput();
            renderSelectedFiles();
        };

        const addFiles = (incomingFileList) => {
            if (!incomingFileList || !incomingFileList.length) {
                return;
            }

            const incoming = Array.from(incomingFileList)
                .filter((file) => file instanceof File)
                .filter((file) => fileMatchesAccept(file, acceptTokens));

            if (!incoming.length) {
                return;
            }

            if (!allowMultiple || maxFiles <= 1) {
                commitFiles([incoming[incoming.length - 1]]);
                return;
            }

            const seen = new Set(selectedFiles.map((file) => fileSignature(file)));
            const merged = selectedFiles.slice();

            incoming.forEach((file) => {
                const signature = fileSignature(file);
                if (seen.has(signature)) {
                    return;
                }
                seen.add(signature);
                merged.push(file);
            });

            commitFiles(merged.slice(0, maxFiles));
        };

        dropzone.addEventListener('click', () => input.click());

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            event.preventDefault();
            const droppedFiles = event.dataTransfer ? event.dataTransfer.files : null;
            addFiles(droppedFiles);
        });

        input.addEventListener('change', () => {
            if (!input.files || !input.files.length) {
                commitFiles([]);
                return;
            }
            addFiles(input.files);
        });

        list.addEventListener('click', (event) => {
            const target = event.target.closest('[data-contact-remove-index]');
            if (!target) {
                return;
            }

            const index = Number.parseInt(String(target.getAttribute('data-contact-remove-index') || '-1'), 10);
            if (!Number.isFinite(index) || index < 0 || index >= selectedFiles.length) {
                return;
            }

            const next = selectedFiles.slice();
            next.splice(index, 1);
            commitFiles(next);
        });

        if (input.form) {
            input.form.addEventListener('reset', () => {
                window.setTimeout(() => commitFiles([]), 0);
            });
        }

        renderSelectedFiles();
    };

    const initAttachmentDropzones = (scope = document) => {
        const inputs = scope.querySelectorAll('input[type="file"][name="attachments[]"], input[type="file"][data-contact-attachments-input]');
        inputs.forEach((input) => setupAttachmentInput(input));
    };

    const init = () => {
        const forms = document.querySelectorAll('form.flatcms-contact-form, form.pb-form-contact');
        if (!forms.length) {
            return;
        }

        const csrfToken = resolveCsrfToken();
        forms.forEach((form) => setupContactForm(form, csrfToken));
        forms.forEach((form) => initAttachmentDropzones(form));

        const widgets = Array.from(document.querySelectorAll('.cf-turnstile'));
        if (!widgets.length) {
            return;
        }

        if (window.turnstile && typeof window.turnstile.render === 'function') {
            return;
        }

        const existingScript = document.querySelector('script[data-flatcms-turnstile]');
        if (existingScript) {
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
        script.async = true;
        script.defer = true;
        script.setAttribute('data-flatcms-turnstile', '1');
        document.head.appendChild(script);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
        return;
    }

    init();
})();
