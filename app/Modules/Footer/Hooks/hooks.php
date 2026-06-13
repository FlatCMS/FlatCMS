<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

return [
    'footer.after_save' => [
        'group' => 'content',
        'label' => 'Footer: after save',
        'description' => 'Declenche apres la sauvegarde des reglages du footer.',
        'params' => [],
        'module' => 'Footer',
    ],
    'footer.before_save' => [
        'group' => 'content',
        'label' => 'Footer: before save',
        'description' => 'Declenche avant la sauvegarde des reglages du footer.',
        'params' => [],
        'module' => 'Footer',
    ],
];
