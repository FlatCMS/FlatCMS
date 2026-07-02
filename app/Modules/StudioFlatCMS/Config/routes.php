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

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], static function (Router $router): void {
    $router->get('/studio-flatcms', [\App\Modules\StudioFlatCMS\Controllers\AdminController::class, 'index'])->name('admin.studio-flatcms');
    $router->get('/studio-flatcms/data', [\App\Modules\StudioFlatCMS\Controllers\AdminController::class, 'document'])->name('admin.studio-flatcms.data');
    $router->post('/studio-flatcms/save', [\App\Modules\StudioFlatCMS\Controllers\AdminController::class, 'save'])->name('admin.studio-flatcms.save');
});
