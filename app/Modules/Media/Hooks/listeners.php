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
        'media' => [
            'routes' => ['admin/media'],
            'steps' => [
                guided_tour_step('[data-tour-target="media-toolbar"]', __('media_tour_toolbar_title', 'Media'), __('media_tour_toolbar_content', 'Media'), 'bottom'),
                guided_tour_step('[data-tour-target="media-folders"]', __('media_tour_folders_title', 'Media'), __('media_tour_folders_content', 'Media'), 'bottom'),
                guided_tour_step('[data-tour-target="media-initial-state"]', __('media_tour_next_action_title', 'Media'), __('media_tour_initial_content', 'Media'), 'top'),
                guided_tour_step('[data-tour-target="media-upload-zone"]', __('media_tour_upload_title', 'Media'), __('media_tour_upload_content', 'Media'), 'top'),
                guided_tour_step('[data-tour-target="media-files-grid"]', __('media_tour_files_title', 'Media'), __('media_tour_files_content', 'Media'), 'top'),
            ],
        ],
    ];
}, ['module' => 'Media', 'priority' => 10]);
