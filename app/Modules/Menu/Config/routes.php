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
    $router->get('/menus', [\App\Modules\Menu\Controllers\AdminController::class, 'index'])->name('admin.menus');
    $router->post('/menus', [\App\Modules\Menu\Controllers\AdminController::class, 'update'])->name('admin.menus.update');
    $router->get('/menus/icons', [\App\Modules\Menu\Controllers\AdminController::class, 'icons'])->name('admin.menus.icons');
});
