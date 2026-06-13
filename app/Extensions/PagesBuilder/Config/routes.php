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

/** @var Router $router */

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], static function (Router $router): void {
    $router->get('/pages-builder', [\App\Extensions\PagesBuilder\Controllers\AdminController::class, 'index'])->name('admin.pages-builder');
    $router->post('/pages-builder/batch', [\App\Extensions\PagesBuilder\Controllers\AdminController::class, 'batch'])->name('admin.pages-builder.batch');
    $router->get('/pages-builder/setup', [\App\Extensions\PagesBuilder\Controllers\AdminController::class, 'setup'])->name('admin.pages-builder.setup');
    $router->post('/pages-builder/setup', [\App\Extensions\PagesBuilder\Controllers\AdminController::class, 'initialize'])->name('admin.pages-builder.initialize');
    $router->get('/pages-builder/create', [\App\Extensions\PagesBuilder\Controllers\AdminController::class, 'create'])->name('admin.pages-builder.create');
    $router->get('/pages-builder/{id}', [\App\Extensions\PagesBuilder\Controllers\AdminController::class, 'edit'])->name('admin.pages-builder.edit');
    $router->post('/pages-builder/{id}', [\App\Extensions\PagesBuilder\Controllers\AdminController::class, 'update'])->name('admin.pages-builder.update');
    $router->post('/pages-builder/{id}/license', [\App\Extensions\PagesBuilder\Controllers\AdminController::class, 'updateLicense'])->name('admin.pages-builder.license');
    $router->post('/pages-builder/{id}/publish', [\App\Extensions\PagesBuilder\Controllers\AdminController::class, 'publish'])->name('admin.pages-builder.publish');
});
