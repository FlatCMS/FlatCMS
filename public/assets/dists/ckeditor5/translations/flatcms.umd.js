/**
 * FlatCMS - CKEditor custom translations
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Complements the official CKEditor 5 translation bundles with labels used by
 * FlatCMS-specific editor plugins.
 */
(function (translations) {
    'use strict';

    var dictionaries = {
        de: {
            'Insert video': 'Video einfügen',
            'Media unavailable': 'Medien nicht verfügbar',
            'Image size': 'Bildgröße',
            'Original size': 'Originalgröße',
            'Custom size': 'Benutzerdefinierte Größe',
            'Apply': 'Anwenden',
        },
        en: {
            'Insert video': 'Insert video',
            'Media unavailable': 'Media unavailable',
            'Image size': 'Image size',
            'Original size': 'Original size',
            'Custom size': 'Custom size',
            'Apply': 'Apply',
        },
        es: {
            'Insert video': 'Insertar vídeo',
            'Media unavailable': 'Contenido multimedia no disponible',
            'Image size': 'Tamaño de imagen',
            'Original size': 'Tamaño original',
            'Custom size': 'Tamaño personalizado',
            'Apply': 'Aplicar',
        },
        fr: {
            'Insert video': 'Insérer une vidéo',
            'Media unavailable': 'Média indisponible',
            'Image size': 'Taille de l’image',
            'Original size': 'Taille d’origine',
            'Custom size': 'Taille personnalisée',
            'Apply': 'Appliquer',
        },
        it: {
            'Insert video': 'Inserisci video',
            'Media unavailable': 'Contenuto multimediale non disponibile',
            'Image size': 'Dimensione immagine',
            'Original size': 'Dimensione originale',
            'Custom size': 'Dimensione personalizzata',
            'Apply': 'Applica',
        },
        pt: {
            'Insert video': 'Inserir vídeo',
            'Media unavailable': 'Conteúdo multimédia indisponível',
            'Image size': 'Tamanho da imagem',
            'Original size': 'Tamanho original',
            'Custom size': 'Tamanho personalizado',
            'Apply': 'Aplicar',
        },
    };

    Object.keys(dictionaries).forEach(function (locale) {
        translations[locale] = translations[locale] || {
            dictionary: {},
            getPluralForm: null,
        };
        translations[locale].dictionary = Object.assign(
            translations[locale].dictionary || {},
            dictionaries[locale]
        );
    });
}(window.CKEDITOR_TRANSLATIONS = window.CKEDITOR_TRANSLATIONS || {}));
