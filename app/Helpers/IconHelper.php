<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Helpers;

class IconHelper
{
    /**
     * Palette de classes CSS pour conserver la coloration sans style inline.
     *
     * @var array<string,string>
     */
    private const COLOR_CLASS_MAP = [
        '#3b82f6' => 'fc-icon-color-blue',
        '#ef4444' => 'fc-icon-color-red',
        '#10b981' => 'fc-icon-color-green',
        '#f97316' => 'fc-icon-color-orange',
        '#14b8a6' => 'fc-icon-color-teal',
        '#f59e0b' => 'fc-icon-color-amber',
        '#eab308' => 'fc-icon-color-yellow',
        '#6b7280' => 'fc-icon-color-gray',
        '#8b5cf6' => 'fc-icon-color-violet',
        '#6366f1' => 'fc-icon-color-indigo',
    ];

    /**
     * Liste des icônes FontAwesome disponibles (chargée depuis JSON)
     * @var array|null
     */
    private static ?array $availableIcons = null;

    /**
     * Charger la liste des icônes depuis le fichier JSON
     */
    private static function loadIcons(): void
    {
        if (self::$availableIcons === null) {
            $jsonPaths = [
                BASE_PATH . '/data/core/icons/fw-672.json',
                BASE_PATH . '/app/Modules/Core/Assets/icons/fw-672.json',
            ];

            foreach ($jsonPaths as $jsonPath) {
                if (!file_exists($jsonPath)) {
                    continue;
                }

                $content = file_get_contents($jsonPath);
                $decoded = json_decode($content, true);
                if (is_array($decoded) && !empty($decoded)) {
                    self::$availableIcons = $decoded;
                    return;
                }
            }

            // Fallback robuste pour éviter un icon-picker vide si le JSON a été supprimé.
            self::$availableIcons = self::getFallbackIcons();
        }
    }

    /**
     * Icônes minimales de secours si fw-672.json est absent.
     *
     * @return array<int,string>
     */
    private static function getFallbackIcons(): array
    {
        return [
            'fa-solid fa-house',
            'fa-solid fa-building',
            'fa-solid fa-folder',
            'fa-solid fa-file',
            'fa-solid fa-file-alt',
            'fa-solid fa-file-lines',
            'fa-solid fa-file-pdf',
            'fa-solid fa-file-word',
            'fa-solid fa-file-excel',
            'fa-solid fa-file-archive',
            'fa-solid fa-image',
            'fa-solid fa-images',
            'fa-solid fa-video',
            'fa-solid fa-music',
            'fa-solid fa-play',
            'fa-solid fa-circle-play',
            'fa-solid fa-link',
            'fa-solid fa-globe',
            'fa-solid fa-shop',
            'fa-solid fa-basket-shopping',
            'fa-solid fa-cart-shopping',
            'fa-solid fa-tag',
            'fa-solid fa-tags',
            'fa-solid fa-list',
            'fa-solid fa-list-check',
            'fa-solid fa-list-ul',
            'fa-solid fa-list-ol',
            'fa-solid fa-book',
            'fa-solid fa-book-open',
            'fa-solid fa-newspaper',
            'fa-solid fa-pen',
            'fa-solid fa-pen-to-square',
            'fa-solid fa-gear',
            'fa-solid fa-sliders',
            'fa-solid fa-wrench',
            'fa-solid fa-screwdriver-wrench',
            'fa-solid fa-cog',
            'fa-solid fa-user',
            'fa-solid fa-users',
            'fa-solid fa-user-gear',
            'fa-solid fa-comment',
            'fa-solid fa-comments',
            'fa-solid fa-envelope',
            'fa-solid fa-phone',
            'fa-solid fa-location-dot',
            'fa-solid fa-map',
            'fa-solid fa-map-location-dot',
            'fa-solid fa-language',
            'fa-solid fa-globe-americas',
            'fa-solid fa-globe-europe',
            'fa-solid fa-heart',
            'fa-solid fa-star',
            'fa-solid fa-check',
            'fa-solid fa-check-circle',
            'fa-solid fa-xmark',
            'fa-solid fa-triangle-exclamation',
            'fa-solid fa-circle-info',
            'fa-solid fa-shield-halved',
            'fa-solid fa-lock',
            'fa-solid fa-unlock',
            'fa-solid fa-key',
            'fa-solid fa-download',
            'fa-solid fa-upload',
            'fa-solid fa-cloud-arrow-up',
            'fa-solid fa-cloud-arrow-down',
            'fa-solid fa-magnifying-glass',
            'fa-solid fa-filter',
            'fa-solid fa-bars',
            'fa-solid fa-grip-lines',
            'fa-solid fa-grip-vertical',
            'fa-solid fa-chevron-down',
            'fa-solid fa-chevron-right',
            'fa-solid fa-chevron-left',
            'fa-solid fa-plus',
            'fa-solid fa-minus',
            'fa-solid fa-trash',
            'fa-solid fa-trash-can',
            'fa-solid fa-copy',
            'fa-solid fa-clone',
            'fa-solid fa-code',
            'fa-solid fa-terminal',
            'fa-solid fa-bolt',
            'fa-solid fa-fire',
            'fa-solid fa-gift',
            'fa-solid fa-crown',
            'fa-solid fa-medal',
            'fa-brands fa-github',
            'fa-brands fa-gitlab',
            'fa-brands fa-discord',
            'fa-brands fa-linkedin',
            'fa-brands fa-x-twitter',
            'fa-brands fa-facebook',
            'fa-brands fa-youtube',
            'fa-brands fa-instagram',
        ];
    }

    /**
     * Obtenir l'icône appropriée pour un type de fichier
     * 
     * @param string $mime Type MIME du fichier
     * @param string|null $extension Extension du fichier
     * @return array ['class' => 'fas fa-xxx', 'color' => '#xxxxxx']
     */
    public static function getFileIcon(string $mime, ?string $extension = null): array
    {
        self::loadIcons();

        // Icônes par type MIME
        if (str_starts_with($mime, 'image/')) {
            return ['class' => 'fa-solid fa-image', 'color' => '#3b82f6'];
        }
        
        if (str_starts_with($mime, 'video/')) {
            return ['class' => 'fa-solid fa-video', 'color' => '#ef4444'];
        }
        
        if (str_starts_with($mime, 'audio/')) {
            return ['class' => 'fa-solid fa-music', 'color' => '#10b981'];
        }

        // Icônes par type de document
        if ($mime === 'application/pdf') {
            return ['class' => 'fa-solid fa-file-pdf', 'color' => '#f97316'];
        }

        if (str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel') || $extension === 'csv') {
            return ['class' => 'fa-solid fa-file-excel', 'color' => '#14b8a6'];
        }

        if (str_contains($mime, 'word') || str_contains($mime, 'document')) {
            return ['class' => 'fa-solid fa-file-word', 'color' => '#3b82f6'];
        }

        if (str_contains($mime, 'powerpoint') || str_contains($mime, 'presentation')) {
            return ['class' => 'fa-solid fa-file-powerpoint', 'color' => '#f97316'];
        }

        // Archives
        if (str_contains($mime, 'zip') || str_contains($mime, 'rar') || str_contains($mime, 'archive')) {
            return ['class' => 'fa-solid fa-file-archive', 'color' => '#f59e0b'];
        }

        // Code
        if (str_contains($mime, 'javascript') || str_contains($mime, 'json') || 
            in_array($extension, ['js', 'json', 'ts', 'jsx', 'tsx'])) {
            return ['class' => 'fa-solid fa-file-code', 'color' => '#eab308'];
        }

        // Par défaut
        return ['class' => 'fa-solid fa-file', 'color' => '#6b7280'];
    }

    /**
     * Obtenir l'icône pour un dossier
     * 
     * @param string $folderName Nom du dossier
     * @return array ['class' => 'fas fa-xxx', 'color' => '#xxxxxx']
     */
    public static function getFolderIcon(string $folderName): array
    {
        self::loadIcons();

        $icons = [
            'images' => ['class' => 'fa-solid fa-image', 'color' => '#3b82f6'],
            'videos' => ['class' => 'fa-solid fa-video', 'color' => '#ef4444'],
            'sounds' => ['class' => 'fa-solid fa-music', 'color' => '#10b981'],
            'documents' => ['class' => 'fa-solid fa-file-alt', 'color' => '#6b7280'],
            'pdf' => ['class' => 'fa-solid fa-file-pdf', 'color' => '#f97316'],
            'spreadsheets' => ['class' => 'fa-solid fa-file-excel', 'color' => '#14b8a6'],
            'archives' => ['class' => 'fa-solid fa-file-archive', 'color' => '#f59e0b'],
            'avatars' => ['class' => 'fa-solid fa-user-circle', 'color' => '#8b5cf6'],
            'personal' => ['class' => 'fa-solid fa-lock', 'color' => '#6366f1'],
        ];

        return $icons[$folderName] ?? ['class' => 'fa-solid fa-folder', 'color' => '#6b7280'];
    }

    /**
     * Vérifier si une icône existe dans la liste
     * 
     * @param string $iconClass Classe de l'icône (ex: "fa-solid fa-image")
     * @return bool
     */
    public static function iconExists(string $iconClass): bool
    {
        self::loadIcons();

        if (empty(self::$availableIcons)) {
            return true; // Si le fichier JSON n'est pas chargé, on suppose que toutes les icônes existent
        }

        // Normaliser la classe (ex: "fas fa-image" ou "fa-solid fa-image")
        $normalizedClass = str_replace(['fas', 'fa-classic fa-solid'], 'fa-solid', $iconClass);
        $normalizedClass = str_replace('far', 'fa-regular', $normalizedClass);
        $normalizedClass = preg_replace('/\s+/', ' ', trim($normalizedClass));

        foreach (self::$availableIcons as $icon) {
            if (str_contains($icon, $normalizedClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rendre une icône HTML avec fallback
     * 
     * @param string $iconClass Classe de l'icône
     * @param string|null $color Couleur optionnelle
     * @param array $attributes Attributs HTML supplémentaires
     * @return string HTML de l'icône
     */
    public static function render(string $iconClass, ?string $color = null, array $attributes = []): string
    {
        $classes = trim($iconClass);
        if (isset($attributes['class'])) {
            $classes = trim($classes . ' ' . (string) $attributes['class']);
            unset($attributes['class']);
        }

        $colorClass = self::resolveColorClass($color);
        if ($colorClass !== '') {
            $classes = trim($classes . ' ' . $colorClass);
        } elseif ($color !== null && trim($color) !== '') {
            // Keep color info available to JS/CSS pipelines without inline style.
            $attributes['data-icon-color'] = trim($color);
        }

        $attrs = [];
        foreach ($attributes as $key => $value) {
            if ($value === null) {
                continue;
            }
            $attrs[] = htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8')
                . '="'
                . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
                . '"';
        }
        $attrString = implode(' ', $attrs);

        return '<i class="'
            . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8')
            . '"'
            . ($attrString !== '' ? ' ' . $attrString : '')
            . '></i>';
    }

    private static function resolveColorClass(?string $color): string
    {
        if ($color === null) {
            return '';
        }

        $normalized = strtolower(str_replace(' ', '', trim($color)));
        return self::COLOR_CLASS_MAP[$normalized] ?? '';
    }

    /**
     * Obtenir toutes les icônes disponibles
     * 
     * @return array
     */
    public static function getAllIcons(): array
    {
        self::loadIcons();
        return self::$availableIcons ?? [];
    }

    /**
     * Rechercher des icônes par mot-clé
     * 
     * @param string $keyword Mot-clé de recherche
     * @param int $limit Nombre maximum de résultats
     * @return array
     */
    public static function searchIcons(string $keyword, int $limit = 50): array
    {
        self::loadIcons();

        if (empty(self::$availableIcons)) {
            return [];
        }

        $keyword = strtolower($keyword);
        $results = [];

        foreach (self::$availableIcons as $icon) {
            if (str_contains(strtolower($icon), $keyword)) {
                $results[] = $icon;
                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }
}
