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

abstract class AbstractWidgetDefinition
{
    public static function overrides(): array
    {
        return [];
    }

    public static function export(): array
    {
        $spec = [
            'key' => static::key(),
            'definition' => static::definition(),
        ];

        $overrides = static::overrides();
        if ($overrides !== []) {
            $spec['overrides'] = $overrides;
        }

        return $spec;
    }

    abstract public static function key(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public static function definition(): array;
}
