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

final class HookSlots
{
    public static function collect(string $hook, array $payload = []): array
    {
        $fragments = [];

        foreach (Hook::run($hook, $payload) as $result) {
            foreach (self::normalizeResult($result) as $fragment) {
                $fragment = trim($fragment);
                if ($fragment !== '') {
                    $fragments[] = $fragment;
                }
            }
        }

        return $fragments;
    }

    public static function render(string $hook, array $payload = []): string
    {
        return implode(PHP_EOL, self::collect($hook, $payload));
    }

    private static function normalizeResult(mixed $result): array
    {
        if (is_string($result)) {
            return [$result];
        }

        if (!is_array($result) || $result === []) {
            return [];
        }

        if (isset($result['html']) && is_string($result['html'])) {
            return [$result['html']];
        }

        if (array_is_list($result)) {
            return array_values(array_filter($result, 'is_string'));
        }

        return [];
    }
}
