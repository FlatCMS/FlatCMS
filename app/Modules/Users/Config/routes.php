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
    $router->get('/users', [\App\Modules\Users\Controllers\AdminController::class, 'index'])->name('admin.users');
    $router->get('/users/create', [\App\Modules\Users\Controllers\AdminController::class, 'create'])->name('admin.users.create');
    $router->post('/users', [\App\Modules\Users\Controllers\AdminController::class, 'store'])->name('admin.users.store');
    $router->get('/users/{id}/edit', [\App\Modules\Users\Controllers\AdminController::class, 'edit'])->name('admin.users.edit');
    $router->post('/users/{id}', [\App\Modules\Users\Controllers\AdminController::class, 'update'])->name('admin.users.update');
    $router->post('/users/{id}/delete', [\App\Modules\Users\Controllers\AdminController::class, 'delete'])->name('admin.users.delete');
    // Profile routes moved to Auth module (AuthController)
});
