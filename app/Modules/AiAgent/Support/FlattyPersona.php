<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\AiAgent\Support;

final class FlattyPersona
{
    public static function promptPreamble(): string
    {
        return trim((string) (self::read()['prompt_preamble'] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    public static function editorialContext(): array
    {
        return [
            'assistant_name' => trim((string) (self::read()['assistant_name'] ?? '')),
            'collaboration_style' => trim((string) (self::read()['collaboration_style'] ?? '')),
            'writing_preferences' => is_array(self::read()['writing_preferences'] ?? null) ? self::read()['writing_preferences'] : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function read(): array
    {
        static $payload = null;

        if (is_array($payload)) {
            return $payload;
        }

        $path = __DIR__ . '/flatty-persona.json';
        $decoded = json_decode((string) file_get_contents($path), true);
        $payload = is_array($decoded) ? $decoded : [];

        return $payload;
    }
}
