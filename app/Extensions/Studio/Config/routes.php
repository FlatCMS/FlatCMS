<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Core\Router;
use App\Extensions\Studio\Controllers\StudioController;

/** @var Router $router */

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], static function (Router $router): void {
    $router->get('/studio', [StudioController::class, 'index'])->name('admin.studio');
    $router->get('/studio/data', [StudioController::class, 'data'])->name('admin.studio.data');
    $router->post('/studio/save', [StudioController::class, 'save'])->name('admin.studio.save');
    $router->post('/studio/preview-url', [StudioController::class, 'previewUrl'])->name('admin.studio.preview-url');
    $router->get('/studio/preview', [StudioController::class, 'preview'])->name('admin.studio.preview');
});
