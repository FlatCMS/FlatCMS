<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Modules\Menu\Services\MenuSyncService;

hook_register('pages.before_save', function ($payload) {
    if (is_array($payload)) {
        MenuSyncService::captureBeforeSave('page', $payload);
    }
}, ['module' => 'Menu', 'priority' => 10]);

hook_register('pages.after_save', function ($payload) {
    if (is_array($payload)) {
        MenuSyncService::syncAfterSave('page', $payload);
    }
}, ['module' => 'Menu', 'priority' => 10]);

hook_register('posts.before_save', function ($payload) {
    if (is_array($payload)) {
        MenuSyncService::captureBeforeSave('post', $payload);
    }
}, ['module' => 'Menu', 'priority' => 10]);

hook_register('posts.after_save', function ($payload) {
    if (is_array($payload)) {
        MenuSyncService::syncAfterSave('post', $payload);
    }
}, ['module' => 'Menu', 'priority' => 10]);

hook_register('categories.before_save', function ($payload) {
    if (is_array($payload)) {
        MenuSyncService::captureBeforeSave('category', $payload);
    }
}, ['module' => 'Menu', 'priority' => 10]);

hook_register('categories.after_save', function ($payload) {
    if (is_array($payload)) {
        MenuSyncService::syncAfterSave('category', $payload);
    }
}, ['module' => 'Menu', 'priority' => 10]);

return [
    'pages.before_save' => [
        'group' => 'content',
        'label' => 'Pages: before save',
        'description' => 'Capture l’ancien slug/titre avant sauvegarde.',
        'params' => [],
        'module' => 'Menu',
    ],
    'pages.after_save' => [
        'group' => 'content',
        'label' => 'Pages: after save',
        'description' => 'Synchronise les liens du menu après sauvegarde.',
        'params' => [],
        'module' => 'Menu',
    ],
    'posts.before_save' => [
        'group' => 'content',
        'label' => 'Posts: before save',
        'description' => 'Capture l’ancien slug/titre avant sauvegarde.',
        'params' => [],
        'module' => 'Menu',
    ],
    'posts.after_save' => [
        'group' => 'content',
        'label' => 'Posts: after save',
        'description' => 'Synchronise les liens du menu après sauvegarde.',
        'params' => [],
        'module' => 'Menu',
    ],
    'categories.before_save' => [
        'group' => 'content',
        'label' => 'Categories: before save',
        'description' => 'Capture l’ancien slug/nom avant sauvegarde.',
        'params' => [],
        'module' => 'Menu',
    ],
    'categories.after_save' => [
        'group' => 'content',
        'label' => 'Categories: after save',
        'description' => 'Synchronise les liens catégories du menu après sauvegarde.',
        'params' => [],
        'module' => 'Menu',
    ],
];
