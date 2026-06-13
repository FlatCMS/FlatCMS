<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Settings\Services;

final class SiteLogoService
{
    public const MODE_LIGHT = 'light';
    public const MODE_DARK = 'dark';

    /**
     * @param array<string, mixed>|null $settings
     * @return array{light:string,dark:string,default:string,mode:string,legacy:string}
     */
    public function resolveLogoPaths(?array $settings = null): array
    {
        $settings = is_array($settings) ? $settings : [];

        $legacy = trim((string) ($settings['site_logo'] ?? ''));
        $light = trim((string) ($settings['site_logo_light'] ?? ''));
        $dark = trim((string) ($settings['site_logo_dark'] ?? ''));

        if ($light === '' && $legacy !== '') {
            $light = $legacy;
        }

        return [
            'light' => $light,
            'dark' => $dark,
            'default' => $light !== '' ? $light : $dark,
            'mode' => $this->normalizeAppearanceMode((string) ($settings['site_logo_mode'] ?? self::MODE_LIGHT)),
            'legacy' => $legacy,
        ];
    }

    /**
     * @param array<string, mixed>|null $settings
     * @return array{light:string,dark:string,default:string,mode:string,legacy:string}
     */
    public function resolveLogoUrls(?array $settings = null): array
    {
        $paths = $this->resolveLogoPaths($settings);

        return [
            'light' => $paths['light'] !== '' ? site_media_url($paths['light']) : '',
            'dark' => $paths['dark'] !== '' ? site_media_url($paths['dark']) : '',
            'default' => $paths['default'] !== '' ? site_media_url($paths['default']) : '',
            'mode' => $paths['mode'],
            'legacy' => $paths['legacy'] !== '' ? site_media_url($paths['legacy']) : '',
        ];
    }

    public function normalizeAppearanceMode(string $mode): string
    {
        $value = strtolower(trim($mode));
        if ($value === self::MODE_DARK) {
            return self::MODE_DARK;
        }

        return self::MODE_LIGHT;
    }
}
