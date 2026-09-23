<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Contact/Support/CsvCellSanitizer.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Contact\Support;

final class CsvCellSanitizer
{
    public static function sanitize(string $value): string
    {
        if (preg_match('/^[\s]*[=+\-@]/u', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }

    /** @param array<int, string> $row */
    public static function sanitizeRow(array $row): array
    {
        return array_map(self::sanitize(...), $row);
    }
}
