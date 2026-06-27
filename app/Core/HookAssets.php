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

final class HookAssets
{
    public static function collect(string $hook, array $payload = []): array
    {
        $assets = [];
        $sequence = 0;

        foreach (Hook::run($hook, $payload) as $result) {
            foreach (self::normalizeResult($result) as $asset) {
                $normalized = self::normalizeAsset($asset);
                if ($normalized === null) {
                    continue;
                }

                $normalized['_sequence'] = $sequence++;
                $assets[] = $normalized;
            }
        }

        usort($assets, static function (array $left, array $right): int {
            $priorityCompare = ($left['priority'] ?? 10) <=> ($right['priority'] ?? 10);
            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            return ($left['_sequence'] ?? 0) <=> ($right['_sequence'] ?? 0);
        });

        $unique = [];
        foreach ($assets as $asset) {
            $key = (string) ($asset['id'] ?? '');
            if ($key === '') {
                $key = sha1((string) ($asset['type'] ?? '') . '|' . (string) ($asset['src'] ?? ''));
            }

            if (!array_key_exists($key, $unique)) {
                $unique[$key] = $asset;
            }
        }

        return array_values($unique);
    }

    public static function render(string $hook, array $payload = []): string
    {
        $html = [];

        foreach (self::collect($hook, $payload) as $asset) {
            $tag = self::renderAsset($asset);
            if ($tag !== '') {
                $html[] = $tag;
            }
        }

        return implode(PHP_EOL, $html);
    }

    private static function normalizeResult(mixed $result): array
    {
        if (!is_array($result) || $result === []) {
            return [];
        }

        if (self::isAssetDescriptor($result)) {
            return [$result];
        }

        if (isset($result['assets']) && is_array($result['assets'])) {
            return array_values(array_filter($result['assets'], 'is_array'));
        }

        if (array_is_list($result)) {
            return array_values(array_filter($result, 'is_array'));
        }

        return [];
    }

    private static function isAssetDescriptor(array $candidate): bool
    {
        return isset($candidate['type'], $candidate['src']);
    }

    private static function normalizeAsset(array $asset): ?array
    {
        $type = strtolower(trim((string) ($asset['type'] ?? '')));
        if (!in_array($type, ['css', 'js'], true)) {
            return null;
        }

        $src = trim((string) ($asset['src'] ?? ''));
        if ($src === '') {
            return null;
        }

        $attrs = [];
        $rawAttrs = $asset['attrs'] ?? [];
        if (is_array($rawAttrs)) {
            foreach ($rawAttrs as $name => $value) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }
                $attrs[$name] = $value;
            }
        }

        if ($type === 'css') {
            $media = trim((string) ($asset['media'] ?? ''));
            if ($media !== '') {
                $attrs['media'] = $media;
            }
        }

        if ($type === 'js') {
            if (!empty($asset['defer'])) {
                $attrs['defer'] = true;
            }
            if (!empty($asset['async'])) {
                $attrs['async'] = true;
            }
        }

        return [
            'id' => trim((string) ($asset['id'] ?? '')),
            'type' => $type,
            'src' => $src,
            'priority' => (int) ($asset['priority'] ?? 10),
            'attrs' => $attrs,
        ];
    }

    private static function renderAsset(array $asset): string
    {
        $type = (string) ($asset['type'] ?? '');
        $src = (string) ($asset['src'] ?? '');
        if ($type === '' || $src === '') {
            return '';
        }

        $attrs = self::renderAttributes($asset['attrs'] ?? []);

        if ($type === 'css') {
            return '<link rel="stylesheet" href="' . e($src) . '"' . $attrs . '>';
        }

        if ($type === 'js') {
            return '<script src="' . e($src) . '"' . $attrs . '></script>';
        }

        return '';
    }

    private static function renderAttributes(array $attrs): string
    {
        $html = '';

        foreach ($attrs as $name => $value) {
            $name = trim((string) $name);
            if ($name === '' || $value === false || $value === null) {
                continue;
            }

            if ($value === true) {
                $html .= ' ' . $name;
                continue;
            }

            $html .= ' ' . $name . '="' . e((string) $value) . '"';
        }

        return $html;
    }
}
