<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Users\Support;

final class UserName
{
    public static function forStorage(array $data): array
    {
        $data['first_name'] = self::clean($data['first_name'] ?? '');
        $data['name'] = self::clean($data['name'] ?? '');

        return $data;
    }

    public static function forForm(array $user): array
    {
        if ($user === []) {
            return $user;
        }

        $user['first_name'] = self::firstNameForForm($user);
        $user['name'] = self::lastNameForForm($user);
        $user['display_name'] = self::display($user);
        $user['greeting_name'] = self::greeting($user);

        return $user;
    }

    public static function forSession(array $user): array
    {
        if ($user === []) {
            return $user;
        }

        $user = self::forStorage($user);
        $user['display_name'] = self::display($user);
        $user['greeting_name'] = self::greeting($user);

        if ($user['display_name'] !== '') {
            $user['name'] = $user['display_name'];
        }

        return $user;
    }

    public static function display(array $user): string
    {
        $displayName = self::clean($user['display_name'] ?? '');
        if ($displayName !== '') {
            return $displayName;
        }

        $firstName = self::clean($user['first_name'] ?? '');
        $lastName = self::clean($user['name'] ?? '');

        if ($firstName !== '' && $lastName !== '') {
            if (self::startsWithName($lastName, $firstName . ' ')) {
                return $lastName;
            }

            return $firstName . ' ' . $lastName;
        }

        if ($firstName !== '') {
            return $firstName;
        }

        if ($lastName !== '') {
            return $lastName;
        }

        return self::clean($user['email'] ?? '');
    }

    public static function greeting(array $user): string
    {
        $firstName = self::clean($user['first_name'] ?? '');
        if ($firstName !== '') {
            return $firstName;
        }

        $displayName = self::display($user);
        if ($displayName === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $displayName, 2) ?: [];
        $candidate = self::clean($parts[0] ?? $displayName);

        return $candidate !== '' ? $candidate : $displayName;
    }

    public static function initial(array $user): string
    {
        $seed = self::greeting($user);
        if ($seed === '') {
            $seed = self::display($user);
        }

        if ($seed === '') {
            return 'U';
        }

        $letter = function_exists('mb_substr')
            ? mb_substr($seed, 0, 1, 'UTF-8')
            : substr($seed, 0, 1);

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($letter, 'UTF-8')
            : strtoupper($letter);
    }

    private static function firstNameForForm(array $user): string
    {
        $firstName = self::clean($user['first_name'] ?? '');
        if ($firstName !== '') {
            return $firstName;
        }

        $legacyName = self::clean($user['name'] ?? '');
        if ($legacyName === '' || !preg_match('/\s/u', $legacyName)) {
            return '';
        }

        $parts = preg_split('/\s+/u', $legacyName, 2) ?: [];
        return self::clean($parts[0] ?? '');
    }

    private static function lastNameForForm(array $user): string
    {
        $firstName = self::clean($user['first_name'] ?? '');
        $name = self::clean($user['name'] ?? '');

        if ($firstName !== '') {
            return $name;
        }

        if ($name === '' || !preg_match('/\s/u', $name)) {
            return $name;
        }

        $parts = preg_split('/\s+/u', $name, 2) ?: [];
        return self::clean($parts[1] ?? $name);
    }

    private static function clean(mixed $value): string
    {
        return trim((string) $value);
    }

    private static function startsWithName(string $value, string $prefix): bool
    {
        if ($prefix === '') {
            return false;
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8') === mb_strtolower($prefix, 'UTF-8')
                || str_starts_with(mb_strtolower($value, 'UTF-8'), mb_strtolower($prefix, 'UTF-8'));
        }

        return str_starts_with(strtolower($value), strtolower($prefix));
    }
}
