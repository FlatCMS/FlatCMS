<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

hook_register('admin.guided_tour.module_tours', static function (): array {
    return [
        'footer' => [
            'routes' => ['admin/footer'],
            'steps' => [
                guided_tour_step('.settings-form', __('footer_title', 'Footer'), __('footer_help_intro', 'Footer'), 'top'),
                guided_tour_step('.settings-form .card', __('translations', 'Footer'), __('footer_help_step_branding', 'Footer'), 'top'),
                guided_tour_step('.form-actions', __('footer_title', 'Footer'), __('footer_help_step_powered', 'Footer'), 'top'),
            ],
        ],
    ];
}, ['module' => 'Footer', 'priority' => 10]);
