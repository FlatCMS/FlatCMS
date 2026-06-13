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

// Frontend routes
$router->get('/', [\App\Modules\Pages\Controllers\FrontController::class, 'home'])->name('home');
$router->get('/page/{slug}', [\App\Modules\Pages\Controllers\FrontController::class, 'show'])->name('page.show');

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router) {
    $router->get('/pages', [\App\Modules\Pages\Controllers\AdminController::class, 'index'])->name('admin.pages');
    $router->get('/pages/create', [\App\Modules\Pages\Controllers\AdminController::class, 'create'])->name('admin.pages.create');
    $router->post('/pages', [\App\Modules\Pages\Controllers\AdminController::class, 'store'])->name('admin.pages.store');
    $router->post('/pages/batch', [\App\Modules\Pages\Controllers\AdminController::class, 'batch'])->name('admin.pages.batch');
    $router->get('/pages/{id}/edit', [\App\Modules\Pages\Controllers\AdminController::class, 'edit'])->name('admin.pages.edit');
    $router->post('/pages/{id}', [\App\Modules\Pages\Controllers\AdminController::class, 'update'])->name('admin.pages.update');
    $router->post('/pages/{id}/delete', [\App\Modules\Pages\Controllers\AdminController::class, 'delete'])->name('admin.pages.delete');
});
