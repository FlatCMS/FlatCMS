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
use App\Modules\Dashboard\Controllers\AdminController;

/** @var Router $router */

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router) {
    $router->get('/', [AdminController::class, 'index'])->name('admin.dashboard');
    $router->get('/dashboard', [AdminController::class, 'index']);
    $router->post('/maintenance/toggle', [AdminController::class, 'toggleMaintenance']);
});
