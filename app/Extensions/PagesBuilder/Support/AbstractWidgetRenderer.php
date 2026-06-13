<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Support;

abstract class AbstractWidgetRenderer
{
    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $context
     */
    final public static function render(array $settings, array $context): mixed
    {
        return (static::renderer())($settings, $context);
    }

    abstract protected static function renderer(): callable;

    /**
     * Keeps widget-scoped CSS selectors deterministic and safe.
     *
     * @param array<string, mixed> $context
     */
    protected static function blockId(array $context): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($context['id'] ?? ''));

        return is_string($safeId) ? $safeId : '';
    }

    protected static function blockSelector(string $safeId, string $selector): string
    {
        return '[data-block-id="' . htmlspecialchars($safeId, ENT_QUOTES, 'UTF-8') . '"] ' . $selector;
    }

    protected static function normalizeToggle(mixed $raw, bool $fallback = false): bool
    {
        if (is_bool($raw)) {
            return $raw;
        }

        $value = strtolower(trim((string) $raw));
        if (in_array($value, ['1', 'true', 'on', 'yes'], true)) {
            return true;
        }
        if (in_array($value, ['0', 'false', 'off', 'no', ''], true)) {
            return false;
        }

        return $fallback;
    }

    protected static function normalizeColor(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) === 1 || preg_match('/^rgb(a)?\([^)]+\)$/i', $value) === 1) {
            return $value;
        }

        return '';
    }

    protected static function normalizeAlign(mixed $raw, string $fallback = 'left'): string
    {
        $value = strtolower(trim((string) $raw));
        if (in_array($value, ['left', 'center', 'right'], true)) {
            return $value;
        }

        $safeFallback = strtolower(trim($fallback));

        return in_array($safeFallback, ['left', 'center', 'right'], true) ? $safeFallback : 'left';
    }

    protected static function normalizeBorderStyle(mixed $raw): string
    {
        $value = strtolower(trim((string) $raw));

        return in_array($value, ['inherit', 'none', 'solid', 'dashed', 'dotted'], true) ? $value : 'inherit';
    }

    protected static function normalizeHeadingTag(mixed $raw, string $fallback = 'h2'): string
    {
        $value = strtolower(trim((string) $raw));
        if (in_array($value, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
            return $value;
        }

        $safeFallback = strtolower(trim($fallback));

        return in_array($safeFallback, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $safeFallback : 'h2';
    }

    protected static function normalizeMediaFit(mixed $raw, string $fallback = 'cover'): string
    {
        $value = strtolower(trim((string) $raw));
        if (in_array($value, ['cover', 'contain'], true)) {
            return $value;
        }

        $safeFallback = strtolower(trim($fallback));

        return in_array($safeFallback, ['cover', 'contain'], true) ? $safeFallback : 'cover';
    }

    protected static function normalizeShadowPreset(mixed $raw): string
    {
        $value = strtolower(trim((string) $raw));

        return in_array($value, ['inherit', 'none', 'soft', 'medium', 'strong'], true) ? $value : 'inherit';
    }

    protected static function normalizeInt(mixed $raw, int $fallback, int $min, int $max): int
    {
        $value = filter_var($raw, FILTER_VALIDATE_INT);
        $number = is_int($value) ? $value : $fallback;

        return max($min, min($max, $number));
    }

    protected static function shadowValue(string $preset): string
    {
        return match ($preset) {
            'none' => 'none',
            'soft' => '0 12px 34px rgba(15,23,42,.10)',
            'medium' => '0 18px 48px rgba(15,23,42,.16)',
            'strong' => '0 26px 70px rgba(15,23,42,.24)',
            default => '',
        };
    }

    protected static function widgetTextFontRule(string $font): string
    {
        return match ($font) {
            'system' => 'font-family:var(--font-family-base,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif);',
            'sans' => 'font-family:"Cabin",var(--font-family-base,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif);',
            'serif' => 'font-family:Georgia,"Times New Roman",Times,serif;',
            'mono' => 'font-family:"SFMono-Regular",Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;',
            'display' => 'font-family:"Cabin",var(--font-family-heading,var(--font-family-base,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif));',
            default => '',
        };
    }

    protected static function normalizeTextStyleFont(mixed $raw): string
    {
        $value = strtolower(trim((string) $raw));

        return in_array($value, ['inherit', 'system', 'sans', 'serif', 'mono', 'display'], true) ? $value : 'inherit';
    }

    protected static function normalizeTextStyleSize(mixed $raw): string
    {
        $value = strtolower(trim((string) $raw));

        return in_array($value, ['inherit', '12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'], true) ? $value : 'inherit';
    }

    protected static function normalizeTextStyleList(mixed $raw): string
    {
        $value = strtolower(trim((string) $raw));

        return in_array($value, ['disc', 'circle', 'square'], true) ? $value : 'none';
    }

    protected static function sanitizeIconClass(mixed $raw): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim((string) $raw));
        if (!is_string($normalized) || $normalized === '') {
            return '';
        }

        $parts = [];
        foreach (explode(' ', $normalized) as $part) {
            $token = trim($part);
            if ($token === '' || preg_match('/^[a-zA-Z0-9_-]+$/', $token) !== 1) {
                continue;
            }
            $parts[] = $token;
        }

        return implode(' ', $parts);
    }

    protected static function sanitizeUrl(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }
        if ($value[0] === '#' || $value[0] === '/' || $value[0] === '?') {
            return $value;
        }
        if (preg_match('/^(https?:|mailto:|tel:)/i', $value) === 1) {
            return $value;
        }

        return '';
    }

    protected static function normalizeShortText(string $raw): string
    {
        $withSpaces = preg_replace('/<br\s*\/?>/i', ' ', $raw);
        $withoutTags = strip_tags(is_string($withSpaces) ? $withSpaces : $raw);
        $decoded = html_entity_decode($withoutTags, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $collapsed = preg_replace('/\s+/u', ' ', trim($decoded));

        return is_string($collapsed) ? $collapsed : trim($decoded);
    }

    protected static function widgetTextSizeRule(string $size): string
    {
        $normalized = self::normalizeTextStyleSize($size);
        if ($normalized === 'inherit') {
            return '';
        }

        return 'font-size:' . htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8') . ';';
    }

    /**
     * @param array<int, string> $surfaceSelectors
     * @param array<int, string> $textSelectors
     * @return array<int, string>
     */
    protected static function buildWidgetDesignRules(
        string $safeId,
        array $surfaceSelectors,
        array $textSelectors,
        bool $useCustomDesign,
        string $designSurfaceColor,
        string $designTextColor,
        string $designBorderStyle,
        int $designBorderWidth,
        string $designBorderColor,
        int $designRadius,
        string $designShadow
    ): array {
        if ($safeId === '' || !$useCustomDesign) {
            return [];
        }

        $css = [];
        $surfaceRules = ['border-radius:' . htmlspecialchars((string) $designRadius, ENT_QUOTES, 'UTF-8') . 'px;'];
        if ($designSurfaceColor !== '') {
            $surfaceRules[] = 'background:' . htmlspecialchars($designSurfaceColor, ENT_QUOTES, 'UTF-8') . ';';
        }
        if ($designBorderStyle !== 'inherit') {
            $surfaceRules[] = 'border-style:' . htmlspecialchars($designBorderStyle, ENT_QUOTES, 'UTF-8') . ';';
            $surfaceRules[] = 'border-width:' . htmlspecialchars((string) $designBorderWidth, ENT_QUOTES, 'UTF-8') . 'px;';
        }
        if ($designBorderColor !== '') {
            $surfaceRules[] = 'border-color:' . htmlspecialchars($designBorderColor, ENT_QUOTES, 'UTF-8') . ';';
            if ($designBorderStyle === 'inherit') {
                $surfaceRules[] = 'border-width:' . htmlspecialchars((string) $designBorderWidth, ENT_QUOTES, 'UTF-8') . 'px;';
            }
        }

        $shadowValue = self::shadowValue($designShadow);
        if ($shadowValue !== '') {
            $surfaceRules[] = 'box-shadow:' . htmlspecialchars($shadowValue, ENT_QUOTES, 'UTF-8') . ';';
        }

        $scopedSurfaceSelectors = [];
        foreach ($surfaceSelectors as $selector) {
            $selector = trim($selector);
            if ($selector !== '') {
                $scopedSurfaceSelectors[] = self::blockSelector($safeId, $selector);
            }
        }
        if ($scopedSurfaceSelectors !== []) {
            $css[] = implode(',', $scopedSurfaceSelectors) . '{' . implode('', $surfaceRules) . '}';
        }

        if ($designTextColor !== '') {
            $scopedTextSelectors = [];
            foreach ($textSelectors as $selector) {
                $selector = trim($selector);
                if ($selector !== '') {
                    $scopedTextSelectors[] = self::blockSelector($safeId, $selector);
                }
            }
            if ($scopedTextSelectors !== []) {
                $css[] = implode(',', $scopedTextSelectors) . '{color:' . htmlspecialchars($designTextColor, ENT_QUOTES, 'UTF-8') . ';}';
            }
        }

        return $css;
    }
}
