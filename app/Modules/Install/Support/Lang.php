<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Install\Support;

final class Lang
{
    private static ?array $translations = null;
    private static string $currentLang = 'fr-FR';
    private static array $availableLangs = ['fr-FR', 'en-US', 'es-ES', 'de-DE', 'it-IT', 'pt-PT'];
    private static array $aliases = [
        'en-EN' => 'en-US',
        'en' => 'en-US',
        'fr' => 'fr-FR',
        'es' => 'es-ES',
        'de' => 'de-DE',
        'it' => 'it-IT',
        'pt' => 'pt-PT',
    ];

    /**
     * Initialise les traductions
     */
    public static function init(string $lang = 'fr-FR'): void
    {
        $lang = self::normalizeLang($lang);

        self::$currentLang = $lang;
        $langFile = dirname(__DIR__) . '/Languages/' . $lang . '.json';

        if (!file_exists($langFile)) {
            throw new \RuntimeException("Fichier de langue introuvable : {$langFile}");
        }

        $content = file_get_contents($langFile);
        self::$translations = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("JSON invalide dans : {$langFile}");
        }
    }

    /**
     * Récupère une traduction par sa clé
     * 
     * @param string $key Clé (ex: 'welcome.title')
     * @param array $params Paramètres de remplacement
     * @return string
     */
    public static function get(string $key, array $params = []): string
    {
        if (self::$translations === null) {
            self::init();
        }

        $keys = explode('.', $key);
        $value = self::$translations;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $key;
            }
            $value = $value[$k];
        }

        if (!is_string($value)) {
            return $key;
        }

        // Remplacer les paramètres {variable}
        foreach ($params as $param => $replacement) {
            $value = str_replace('{' . $param . '}', (string)$replacement, $value);
        }

        return $value;
    }

    /**
     * Détecte la langue du navigateur
     */
    public static function detectBrowserLang(): string
    {
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        
        if (preg_match_all('/([a-z]{2})-?([a-z]{2})?/i', $acceptLang, $matches)) {
            foreach ($matches[0] as $lang) {
                $normalized = self::normalizeLang((string) $lang);
                if (in_array($normalized, self::$availableLangs, true)) {
                    return $normalized;
                }
            }
        }
        
        return 'fr-FR';
    }

    public static function getCurrentLang(): string
    {
        return self::$currentLang;
    }

    public static function getAvailableLangs(): array
    {
        return self::$availableLangs;
    }

    public static function isAvailable(string $lang): bool
    {
        $lang = self::normalizeLang($lang);
        return in_array($lang, self::$availableLangs, true);
    }

    private static function normalizeLang(string $lang): string
    {
        $lang = str_replace('_', '-', trim($lang));
        if ($lang === '') {
            return 'fr-FR';
        }

        foreach (self::$aliases as $alias => $canonical) {
            if (strcasecmp($alias, $lang) === 0) {
                return $canonical;
            }
        }

        foreach (self::$availableLangs as $available) {
            if (strcasecmp($available, $lang) === 0) {
                return $available;
            }
        }

        $short = strtolower((string) strtok($lang, '-'));
        if (isset(self::$aliases[$short])) {
            return self::$aliases[$short];
        }

        return 'fr-FR';
    }
}
