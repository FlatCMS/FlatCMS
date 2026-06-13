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

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router) {
    $router->get('/categories', [\App\Modules\Categories\Controllers\AdminController::class, 'index'])->name('admin.categories');
    $router->get('/categories/create', [\App\Modules\Categories\Controllers\AdminController::class, 'create'])->name('admin.categories.create');
    $router->post('/categories', [\App\Modules\Categories\Controllers\AdminController::class, 'store'])->name('admin.categories.store');
    $router->post('/categories/batch', [\App\Modules\Categories\Controllers\AdminController::class, 'batch'])->name('admin.categories.batch');
    $router->get('/categories/{id}/edit', [\App\Modules\Categories\Controllers\AdminController::class, 'edit'])->name('admin.categories.edit');
    $router->post('/categories/{id}', [\App\Modules\Categories\Controllers\AdminController::class, 'update'])->name('admin.categories.update');
    $router->post('/categories/{id}/delete', [\App\Modules\Categories\Controllers\AdminController::class, 'delete'])->name('admin.categories.delete');
});
