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
    $router->get('/themes', [\App\Modules\Themes\Controllers\AdminController::class, 'index'])->name('admin.themes');
    $router->post('/themes/activate/{type}/{name}', [\App\Modules\Themes\Controllers\AdminController::class, 'activate'])->name('admin.themes.activate');
    $router->post('/themes/trash/{type}/{name}', [\App\Modules\Themes\Controllers\AdminController::class, 'trash'])->name('admin.themes.trash');
    $router->get('/themes/{type}/{name}/customize', [\App\Modules\Themes\Controllers\AdminController::class, 'customize'])->name('admin.themes.customize');
    $router->post('/themes/{type}/{name}/customize', [\App\Modules\Themes\Controllers\AdminController::class, 'saveCustomization'])->name('admin.themes.customize.save');
    $router->post('/themes/{type}/{name}/customize/reset', [\App\Modules\Themes\Controllers\AdminController::class, 'resetCustomization'])->name('admin.themes.customize.reset');
    $router->post('/themes/install', [\App\Modules\Themes\Controllers\AdminController::class, 'install'])->name('admin.themes.install');
});
