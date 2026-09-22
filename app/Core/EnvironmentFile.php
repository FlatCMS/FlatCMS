<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/EnvironmentFile.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Core;

/** The same non-interpolating env codec is used before web and CLI bootstrap. */
final class EnvironmentFile
{
    /** @return array<string, string> */
    public static function read(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }
        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            throw new \RuntimeException('environment_file_read_failed');
        }
        $values = [];
        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $key) !== 1) {
                continue;
            }
            $value = trim($value);
            if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
                $value = strtr(substr($value, 1, -1), ['\\\\' => '\\', '\\"' => '"', '\\n' => "\n", '\\r' => "\r", '\\t' => "\t"]);
            } elseif (strlen($value) >= 2 && $value[0] === "'" && str_ends_with($value, "'")) {
                $value = substr($value, 1, -1);
            }
            $values[$key] = $value;
        }
        return $values;
    }

    public static function load(string $path): void
    {
        foreach (self::read($path) as $key => $value) {
            $_ENV[$key] = $value;
            if (function_exists('putenv')) {
                putenv($key . '=' . $value);
            }
        }
    }
}
