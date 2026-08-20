<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use App\Core\Router;

/** @var Router $router */
$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router): void {
    $router->get('/updates', [\App\Modules\UpdateManager\Controllers\AdminController::class, 'index'])
        ->name('admin.updates');
    $router->post('/updates/check', [\App\Modules\UpdateManager\Controllers\AdminController::class, 'check'])
        ->name('admin.updates.check');
    $router->post('/updates/recovery', [\App\Modules\UpdateManager\Controllers\AdminController::class, 'resumeRecovery'])
        ->name('admin.updates.recovery');
    $router->post('/updates/install/core/{version}', [\App\Modules\UpdateManager\Controllers\AdminController::class, 'installCore'])
        ->name('admin.updates.install.core');
});
