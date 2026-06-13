/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        maxFileSize: 2 * 1024 * 1024, // 2 Mo
        allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        messages: {
            invalidType: 'Veuillez sélectionner une image.',
            fileTooLarge: 'Le fichier est trop volumineux (max 2 Mo).',
            confirmRemove: 'Voulez-vous vraiment supprimer votre avatar ?',
            readonly: 'Action désactivée.'
        }
    };

    /**
     * Initialise le composant d'upload d'avatar
     */
    function initAvatarUpload() {
        const elements = getElements();
        
        if (!elements.input || !elements.preview) {
            console.warn('Avatar upload: Required elements not found');
            return;
        }

        elements.isReadonly = !!(elements.uploadContainer && elements.uploadContainer.dataset.avatarReadonly === '1');
        if (elements.isReadonly && elements.uploadContainer) {
            elements.uploadContainer.classList.add('is-readonly');
        }

        if (elements.isReadonly && elements.input) {
            elements.input.disabled = true;
        }

        // Charger les messages traduits depuis les attributs data
        loadTranslations(elements.uploadContainer);

        // Attacher les événements
        attachEvents(elements);

        console.log('Avatar upload initialized');
    }

    /**
     * Récupère tous les éléments DOM nécessaires
     */
    function getElements() {
        return {
            input: document.getElementById('avatarInput'),
            preview: document.getElementById('avatarPreview'),
            image: document.getElementById('avatarImage'),
            placeholder: document.getElementById('avatarPlaceholder'),
            filename: document.getElementById('avatarFilename'),
            btnSelect: document.getElementById('btnSelectAvatar'),
            btnRemove: document.getElementById('btnRemoveAvatar'),
            uploadContainer: document.querySelector('.avatar-upload-container'),
            removeFlag: document.getElementById('avatarRemove')
        };
    }

    /**
     * Charge les traductions depuis les attributs data
     */
    function loadTranslations(container) {
        if (!container) return;

        if (container.dataset.msgInvalidType) {
            CONFIG.messages.invalidType = container.dataset.msgInvalidType;
        }
        if (container.dataset.msgFileTooLarge) {
            CONFIG.messages.fileTooLarge = container.dataset.msgFileTooLarge;
        }
        if (container.dataset.msgConfirmRemove) {
            CONFIG.messages.confirmRemove = container.dataset.msgConfirmRemove;
        }
        if (container.dataset.msgReadonly) {
            CONFIG.messages.readonly = container.dataset.msgReadonly;
        }
    }

    /**
     * Attache tous les événements
     */
    function attachEvents(elements) {
        function blockIfReadonly(event) {
            if (!elements.isReadonly) return false;
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            showError(CONFIG.messages.readonly);
            return true;
        }

        // Click sur le bouton pour ouvrir le sélecteur
        if (elements.btnSelect) {
            elements.btnSelect.addEventListener('click', (e) => {
                if (blockIfReadonly(e)) return;
                elements.input.click();
            });
        }

        // Click sur la preview pour ouvrir le sélecteur
        if (elements.preview) {
            elements.preview.addEventListener('click', (e) => {
                if (blockIfReadonly(e)) return;
                elements.input.click();
            });
        }

        // Changement de fichier
        if (elements.input) {
            elements.input.addEventListener('change', (e) => {
                if (elements.isReadonly) return;
                handleFileSelect(e.target.files[0], elements);
            });
        }

        // Drag & Drop
        if (elements.uploadContainer) {
            elements.uploadContainer.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (elements.isReadonly) return;
                elements.uploadContainer.classList.add('drag-over');
            });

            elements.uploadContainer.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (elements.isReadonly) return;
                elements.uploadContainer.classList.remove('drag-over');
            });

            elements.uploadContainer.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (elements.isReadonly) {
                    showError(CONFIG.messages.readonly);
                    return;
                }
                elements.uploadContainer.classList.remove('drag-over');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelect(files[0], elements);
                    
                    // Mettre le fichier dans l'input
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(files[0]);
                    elements.input.files = dataTransfer.files;
                }
            });
        }

        // Supprimer l'avatar
        if (elements.btnRemove) {
            elements.btnRemove.addEventListener('click', (e) => {
                if (blockIfReadonly(e)) return;
                handleRemoveAvatar(elements);
            });
        }
    }

    /**
     * Gère la sélection de fichier
     */
    function handleFileSelect(file, elements) {
        if (!file) return;

        // Validation du type
        if (!validateFileType(file)) {
            showError(CONFIG.messages.invalidType);
            return;
        }

        // Validation de la taille
        if (!validateFileSize(file)) {
            showError(CONFIG.messages.fileTooLarge);
            return;
        }

        // Afficher le nom du fichier
        if (elements.filename) {
            elements.filename.textContent = file.name;
        }

        if (elements.removeFlag) {
            elements.removeFlag.value = '0';
        }

        // Prévisualiser l'image
        previewImage(file, elements);
    }

    /**
     * Valide le type de fichier
     */
    function validateFileType(file) {
        return CONFIG.allowedTypes.includes(file.type);
    }

    /**
     * Valide la taille du fichier
     */
    function validateFileSize(file) {
        return file.size <= CONFIG.maxFileSize;
    }

    /**
     * Prévisualise l'image
     */
    function previewImage(file, elements) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Cacher le placeholder
            if (elements.placeholder) {
                elements.placeholder.style.display = 'none';
            }

            // Créer ou mettre à jour l'image
            if (elements.image) {
                elements.image.src = e.target.result;
            } else {
                const img = document.createElement('img');
                img.id = 'avatarImage';
                img.className = 'avatar-image';
                img.src = e.target.result;
                img.alt = 'Avatar';
                elements.preview.insertBefore(img, elements.preview.firstChild);
                elements.image = img;
            }

            // Afficher le bouton de suppression
            if (elements.btnRemove) {
                elements.btnRemove.style.display = 'inline-flex';
            }
        };

        reader.readAsDataURL(file);
    }

    /**
     * Gère la suppression de l'avatar
     */
    function handleRemoveAvatar(elements) {
        const proceed = function() {
            // Reset l'input
            if (elements.input) {
                elements.input.value = '';
            }

            // Reset le nom de fichier
            if (elements.filename) {
                elements.filename.textContent = '';
            }

            // Supprimer l'image et afficher le placeholder
            if (elements.image) {
                elements.image.remove();
                elements.image = null;
            }

            if (elements.placeholder) {
                elements.placeholder.style.display = 'flex';
            } else {
                // Créer un nouveau placeholder
                const placeholder = createPlaceholder();
                elements.preview.insertBefore(placeholder, elements.preview.firstChild);
            }

            // Cacher le bouton de suppression
            if (elements.btnRemove) {
                elements.btnRemove.style.display = 'none';
            }

            if (elements.removeFlag) {
                elements.removeFlag.value = '1';
            }
        };

        confirmDeleteAction(CONFIG.messages.confirmRemove, proceed, {
            confirmText: 'Supprimer',
            warning: '',
            itemName: ''
        });
    }

    function confirmDeleteAction(message, onConfirm, options) {
        if (typeof onConfirm !== 'function') {
            return;
        }

        const finalMessage = String(message || CONFIG.messages.confirmRemove || 'Êtes-vous sûr ?');
        const opts = options || {};
        const finalConfirmText = String(opts.confirmText || 'Supprimer');
        const finalWarning = String(opts.warning || '');
        const finalItemName = String(opts.itemName || '');

        if (window.FlatCMS && window.FlatCMS.modal && typeof window.FlatCMS.modal.confirm === 'function') {
            window.FlatCMS.modal.confirm(finalMessage, onConfirm, {
                confirmText: finalConfirmText,
                warning: finalWarning,
                itemName: finalItemName
            });
            return;
        }

        if (confirm(finalMessage)) {
            onConfirm();
        }
    }

    /**
     * Crée un placeholder pour l'avatar
     */
    function createPlaceholder() {
        const placeholder = document.createElement('div');
        placeholder.className = 'avatar-placeholder';
        placeholder.id = 'avatarPlaceholder';
        placeholder.innerHTML = `
            <svg class="avatar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
        `;
        return placeholder;
    }

    /**
     * Affiche un message d'erreur
     */
    function showError(message) {
        if (window.FlatCMS && window.FlatCMS.modal && typeof window.FlatCMS.modal.alert === 'function') {
            window.FlatCMS.modal.alert(message);
            return;
        }
        alert(message);
    }

    // Initialiser au chargement de la page
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAvatarUpload);
    } else {
        initAvatarUpload();
    }

    // Exposer l'API publique si nécessaire
    window.FlatCMS = window.FlatCMS || {};
    window.FlatCMS.AvatarUpload = {
        init: initAvatarUpload
    };

})();
