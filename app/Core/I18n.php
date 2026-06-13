<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core;

class I18n
{
    private static string $locale = 'fr-FR';
    private static string $fallbackLocale = 'en-US';
    private static array $translations = [];
    private static array $loadedModules = [];

    public static function init(?string $locale = null): void
    {
        self::$locale = $locale ?? config('app.locale', 'fr-FR');
        self::$fallbackLocale = config('app.fallback_locale', 'en-US');
        
        // Load Core translations
        self::load('Core');
    }

    public static function setLocale(string $locale): void
    {
        $supportedLocales = self::getSupportedLocales();
        
        if (in_array($locale, $supportedLocales)) {
            self::$locale = $locale;
            self::$translations = [];
            self::$loadedModules = [];
            self::load('Core');
        }
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function load(string $module): void
    {
        if (in_array($module, self::$loadedModules)) {
            return;
        }

        $path = self::resolveTranslationPath($module, self::$locale);

        if (file_exists($path)) {
            $content = file_get_contents($path);
            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $data = self::filterEmptyTranslations($data);
                self::$translations[$module] = array_merge(
                    self::$translations[$module] ?? [],
                    $data
                );
            }
        }

        // Load fallback locale if different
        if (self::$locale !== self::$fallbackLocale) {
            $fallbackPath = self::resolveTranslationPath($module, self::$fallbackLocale);

            if (file_exists($fallbackPath)) {
                $content = file_get_contents($fallbackPath);
                $data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $data = self::filterEmptyTranslations($data);
                    // Only add keys that don't exist in main locale
                    foreach ($data as $key => $value) {
                        if (!isset(self::$translations[$module][$key])) {
                            self::$translations[$module][$key] = $value;
                        }
                    }
                }
            }
        }

        self::$loadedModules[] = $module;
    }

    public static function resolveTranslationPathForNamespace(string $module, string $locale): string
    {
        if (self::isRealModuleNamespace($module)) {
            return BASE_PATH . "/app/Modules/{$module}/Languages/" . $locale . '.json';
        }

        if (self::isRealExtensionNamespace($module)) {
            return BASE_PATH . "/app/Extensions/{$module}/Languages/" . $locale . '.json';
        }

        return BASE_PATH . "/app/Modules/{$module}/Languages/" . $locale . '.json';
    }

    private static function resolveTranslationPath(string $module, string $locale): string
    {
        return self::resolveTranslationPathForNamespace($module, $locale);
    }

    private static function isRealModuleNamespace(string $module): bool
    {
        return self::isRealModuleLikeNamespace(BASE_PATH . "/app/Modules/{$module}", ['module.json', 'extension.json']);
    }

    private static function isRealExtensionNamespace(string $module): bool
    {
        return self::isRealModuleLikeNamespace(BASE_PATH . "/app/Extensions/{$module}", ['extension.json', 'module.json']);
    }

    private static function isRealModuleLikeNamespace(string $root, array $manifests): bool
    {
        if (!is_dir($root)) {
            return false;
        }

        foreach ($manifests as $manifest) {
            if (is_file($root . DIRECTORY_SEPARATOR . $manifest)) {
                return true;
            }
        }

        foreach (['Config', 'Controllers', 'Views', 'Models', 'Services', 'Hooks'] as $dir) {
            if (is_dir($root . DIRECTORY_SEPARATOR . $dir)) {
                return true;
            }
        }

        return false;
    }

    public static function get(string $key, string $module = 'Core', array $replace = []): string
    {
        // Load module if not loaded
        if (!in_array($module, self::$loadedModules)) {
            self::load($module);
        }

        // Get translation (supports dot notation for nested keys)
        $translation = self::resolve($key, $module) ?? $key;

        // Replace placeholders :name
        foreach ($replace as $placeholder => $value) {
            $translation = str_replace(':' . $placeholder, (string) $value, $translation);
        }

        return $translation;
    }

    public static function has(string $key, string $module = 'Core'): bool
    {
        if (!in_array($module, self::$loadedModules)) {
            self::load($module);
        }

        return self::resolve($key, $module) !== null;
    }

    private static function filterEmptyTranslations(array $data): array
    {
        $filtered = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = self::filterEmptyTranslations($value);
                if (!empty($child)) {
                    $filtered[$key] = $child;
                }
                continue;
            }

            if (is_string($value)) {
                if (trim($value) === '') {
                    continue;
                }
                $filtered[$key] = $value;
                continue;
            }

            if ($value === null) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    private static function resolve(string $key, string $module): mixed
    {
        $data = self::$translations[$module] ?? [];

        // Direct lookup first
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        // Dot notation: traverse nested array
        $segments = explode('.', $key);
        $current = $data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public static function all(string $module = 'Core'): array
    {
        if (!in_array($module, self::$loadedModules)) {
            self::load($module);
        }

        return self::$translations[$module] ?? [];
    }

    public static function getSupportedLocales(): array
    {
        $locales = [];
        $langDir = BASE_PATH . '/data/languages';

        if (is_dir($langDir)) {
            foreach (glob($langDir . '/*.json') as $file) {
                $locales[] = basename($file, '.json');
            }
        }

        if (!empty($locales)) {
            sort($locales);
            return $locales;
        }

        return config('app.locales', ['fr-FR', 'en-US']);
    }

    public static function localizeLanguageCatalog(array $languages, ?string $uiLocale = null): array
    {
        $resolvedLocale = is_string($uiLocale) && trim($uiLocale) !== ''
            ? trim($uiLocale)
            : self::$locale;

        foreach ($languages as $code => $languageData) {
            if (!is_array($languageData)) {
                continue;
            }

            $localizedName = self::getLocalizedLanguageName((string) $code, $resolvedLocale);
            if ($localizedName === '') {
                continue;
            }

            $languages[$code]['name'] = $localizedName;
        }

        return $languages;
    }

    public static function getLocalizedLanguageName(string $languageCode, ?string $uiLocale = null): string
    {
        if (!class_exists('\\Locale')) {
            return '';
        }

        $normalizedLanguageCode = self::normalizeLocaleTag($languageCode);
        $normalizedUiLocale = self::normalizeLocaleTag($uiLocale ?? self::$locale);

        if ($normalizedLanguageCode === '' || $normalizedUiLocale === '') {
            return '';
        }

        $displayLanguage = \Locale::getDisplayLanguage($normalizedLanguageCode, $normalizedUiLocale);
        if (!is_string($displayLanguage)) {
            return '';
        }

        $displayLanguage = trim($displayLanguage);
        if ($displayLanguage === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($displayLanguage, MB_CASE_TITLE, 'UTF-8');
        }

        return ucfirst($displayLanguage);
    }

    public static function normalizeLocaleTag(string $locale): string
    {
        $locale = trim($locale);
        if ($locale === '') {
            return '';
        }

        return str_replace('-', '_', $locale);
    }

    public static function getLocaleUrl(string $locale): string
    {
        $request = app()->request();
        $uri = $request->uri();
        
        return url('/' . $locale . $uri);
    }

    public static function toJson(string $module = 'Core'): string
    {
        return json_encode(self::all($module), JSON_UNESCAPED_UNICODE);
    }

    public static function getDirection(): string
    {
        $locale = self::$locale;
        $configPath = BASE_PATH . "/data/languages/{$locale}.json";

        if (file_exists($configPath)) {
            $content = file_get_contents($configPath);
            $config = json_decode($content, true);
            if (is_array($config) && isset($config['direction'])) {
                return $config['direction'];
            }
        }

        // Default RTL languages (match by language prefix for ar-SA, he-IL, etc.)
        $rtlLanguages = ['ar', 'he', 'fa', 'ur'];
        $langPrefix = substr($locale, 0, 2);
        if (in_array($langPrefix, $rtlLanguages, true)) {
            return 'rtl';
        }

        return 'ltr';
    }

    public static function isRtl(): bool
    {
        return self::getDirection() === 'rtl';
    }
}
