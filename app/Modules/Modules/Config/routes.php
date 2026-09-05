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
    $router->get('/modules', [\App\Modules\Modules\Controllers\AdminController::class, 'index'])->name('admin.modules');
    $router->post('/modules/{name}/toggle', [\App\Modules\Modules\Controllers\AdminController::class, 'toggle'])->name('admin.modules.toggle');
    $router->post('/modules/{name}/sidebar-toggle', [\App\Modules\Modules\Controllers\AdminController::class, 'toggleSidebar'])->name('admin.modules.toggleSidebar');
    $router->post('/modules/install', [\App\Modules\Modules\Controllers\AdminController::class, 'install'])->name('admin.modules.install');
    $router->post('/modules/{name}/delete', [\App\Modules\Modules\Controllers\AdminController::class, 'delete'])->name('admin.modules.delete');
});
