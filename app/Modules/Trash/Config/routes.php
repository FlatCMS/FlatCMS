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
    $router->get('/trash', [\App\Modules\Trash\Controllers\AdminController::class, 'index'])->name('admin.trash');
    $router->post('/trash/batch', [\App\Modules\Trash\Controllers\AdminController::class, 'batch'])->name('admin.trash.batch');
    $router->post('/trash/{id}/restore', [\App\Modules\Trash\Controllers\AdminController::class, 'restore'])->name('admin.trash.restore');
    $router->post('/trash/{id}/delete', [\App\Modules\Trash\Controllers\AdminController::class, 'delete'])->name('admin.trash.delete');
});
