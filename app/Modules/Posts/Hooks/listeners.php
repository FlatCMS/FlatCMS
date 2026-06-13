<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Modules\Posts\Support\PostShortcodeRenderer;

hook_register('shortcodes.register', static function (): array {
    return [
        'post' => static function (array $attributes = [], array $context = []): string {
            try {
                return PostShortcodeRenderer::render($attributes, $context);
            } catch (\Throwable) {
                return '';
            }
        },
    ];
}, ['module' => 'Posts', 'priority' => 10]);
