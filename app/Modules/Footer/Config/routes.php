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
    $router->get('/footer', [\App\Modules\Footer\Controllers\AdminController::class, 'index'])->name('admin.footer');
    $router->post('/footer', [\App\Modules\Footer\Controllers\AdminController::class, 'update'])->name('admin.footer.update');
});
