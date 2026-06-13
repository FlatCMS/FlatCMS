<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Services;

use App\Core\I18n;

final class PageBuilderWidgetLocaleService
{
    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    private static array $cache = [];

    public static function translate(string $widget, string $key, string $fallback = '', array $replace = []): string
    {
        $value = self::lookup($widget, $key, I18n::getLocale());
        if ($value === null && I18n::getLocale() !== 'en-US') {
            $value = self::lookup($widget, $key, 'en-US');
        }

        $translation = is_string($value) && trim($value) !== ''
            ? $value
            : ($fallback !== '' ? $fallback : $key);

        foreach ($replace as $placeholder => $replacement) {
            $translation = str_replace(':' . $placeholder, (string) $replacement, $translation);
        }

        return $translation;
    }

    public static function resolveSpecValue(string $widget, mixed $value): mixed
    {
        if (is_array($value)) {
            if (($value['__label'] ?? false) === true) {
                $key = trim((string) ($value['key'] ?? ''));
                $fallback = (string) ($value['fallback'] ?? '');
                return self::translate($widget, $key, $fallback);
            }

            $resolved = [];
            foreach ($value as $itemKey => $itemValue) {
                $resolved[$itemKey] = self::resolveSpecValue($widget, $itemValue);
            }

            return $resolved;
        }

        return $value;
    }

    private static function lookup(string $widget, string $key, string $locale): mixed
    {
        $catalog = self::catalog($widget, $locale);
        if ($catalog === []) {
            return null;
        }

        return $catalog[$key] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function catalog(string $widget, string $locale): array
    {
        $widgetKey = trim($widget);
        $localeKey = trim($locale);
        if ($widgetKey === '' || $localeKey === '') {
            return [];
        }

        if (isset(self::$cache[$widgetKey][$localeKey])) {
            return self::$cache[$widgetKey][$localeKey];
        }

        $path = BASE_PATH . '/app/Extensions/PagesBuilder/Widgets/' . $widgetKey . '/Languages/' . $localeKey . '.json';
        if (!is_file($path)) {
            self::$cache[$widgetKey][$localeKey] = [];
            return self::$cache[$widgetKey][$localeKey];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        self::$cache[$widgetKey][$localeKey] = is_array($decoded) ? $decoded : [];

        return self::$cache[$widgetKey][$localeKey];
    }
}
