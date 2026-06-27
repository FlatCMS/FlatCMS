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
        'comments' => [
            'routes' => ['admin/comments'],
            'steps' => [
                guided_tour_step('[data-tour-target="comments-toolbar"]', __('comments_tour_filter_title', 'Comments'), __('comments_tour_filter_content', 'Comments'), 'bottom'),
                guided_tour_step('[data-tour-target="comments-table"]', __('comments_tour_table_title', 'Comments'), __('comments_tour_table_content', 'Comments'), 'top', [
                    'whenVisible' => '[data-tour-target="comments-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="comments-empty"]', __('comments_tour_next_action_title', 'Comments'), __('comments_tour_empty_content', 'Comments'), 'top', [
                    'whenVisible' => '[data-tour-target="comments-empty"]',
                ]),
                guided_tour_step('.comment-actions, .comment-actions-cell', __('comments_tour_actions_title', 'Comments'), __('comments_tour_actions_content', 'Comments'), 'left', [
                    'whenVisible' => '[data-tour-target="comments-table"][data-tour-state="ready"]',
                ]),
                guided_tour_step('.pagination', __('comments_help_title', 'Comments'), __('comments_help_intro', 'Comments'), 'top'),
            ],
        ],
    ];
}, ['module' => 'Comments', 'priority' => 10]);
