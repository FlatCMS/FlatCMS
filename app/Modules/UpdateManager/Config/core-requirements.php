<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/UpdateManager/Config/core-requirements.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

return [
    '1.1.5' => [
        ['catalog' => 'modules', 'slug' => 'modules', 'version' => '>=1.0.1'],
        ['catalog' => 'modules', 'slug' => 'backups', 'version' => '>=1.0.1'],
        ['catalog' => 'modules', 'slug' => 'update-manager', 'version' => '>=0.4.4'],
    ],
    '1.1.6' => [
        ['catalog' => 'modules', 'slug' => 'modules', 'version' => '>=1.0.3'],
        ['catalog' => 'modules', 'slug' => 'backups', 'version' => '>=1.0.2'],
        ['catalog' => 'modules', 'slug' => 'languages', 'version' => '>=1.0.1'],
        ['catalog' => 'modules', 'slug' => 'update-manager', 'version' => '>=0.4.7'],
    ],
    '1.1.7' => [
        ['catalog' => 'modules', 'slug' => 'modules', 'version' => '>=1.0.3'],
        ['catalog' => 'modules', 'slug' => 'backups', 'version' => '>=1.0.2'],
        ['catalog' => 'modules', 'slug' => 'languages', 'version' => '>=1.0.1'],
        ['catalog' => 'modules', 'slug' => 'update-manager', 'version' => '>=0.4.7'],
    ],
    '2.0.0' => [
        ['catalog' => 'modules', 'slug' => 'modules', 'version' => '>=1.0.4'],
        ['catalog' => 'modules', 'slug' => 'backups', 'version' => '>=1.1.0'],
        ['catalog' => 'modules', 'slug' => 'languages', 'version' => '>=1.0.1'],
        ['catalog' => 'modules', 'slug' => 'update-manager', 'version' => '>=0.4.9'],
    ],
];
