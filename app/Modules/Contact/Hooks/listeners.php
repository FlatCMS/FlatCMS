<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Modules\Contact\Support\ContactFormRenderer;

hook_register('shortcodes.register', static function (): array {
    return [
        'contact-form' => static function (array $attributes = [], array $context = []): string {
            $slug = trim((string) ($attributes['slug'] ?? ''));

            try {
                return ContactFormRenderer::render($slug, $context);
            } catch (\Throwable) {
                return '';
            }
        },
    ];
}, ['module' => 'Contact', 'priority' => 10]);
