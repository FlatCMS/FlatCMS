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
    $router->get('/backups', [\App\Modules\Backups\Controllers\AdminController::class, 'index'])->name('admin.backups');
    $router->post('/backups/create', [\App\Modules\Backups\Controllers\AdminController::class, 'create'])->name('admin.backups.create');
    $router->get('/backups/download/{filename}', [\App\Modules\Backups\Controllers\AdminController::class, 'download'])->name('admin.backups.download');
    $router->post('/backups/restore-upload', [\App\Modules\Backups\Controllers\AdminController::class, 'restoreUpload'])->name('admin.backups.restore_upload');
    $router->post('/backups/{filename}/restore', [\App\Modules\Backups\Controllers\AdminController::class, 'restore'])->name('admin.backups.restore');
    $router->post('/backups/{filename}/delete', [\App\Modules\Backups\Controllers\AdminController::class, 'delete'])->name('admin.backups.delete');
    $router->post('/backups/reset', [\App\Modules\Backups\Controllers\AdminController::class, 'reset'])->name('admin.backups.reset');
    $router->post('/backups/factory-reset', [\App\Modules\Backups\Controllers\AdminController::class, 'factoryReset'])->name('admin.backups.factory_reset');
});
