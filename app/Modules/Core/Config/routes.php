<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Core/Config/routes.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

use App\Core\Router;
use App\Modules\Core\Controllers\AdminUiController;

/** @var Router $router */

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router): void {
    $router->get('/ui/icons', [AdminUiController::class, 'icons'])->name('admin.ui.icons');
    $router->post('/ui/seo/analyze', [AdminUiController::class, 'analyzeSeo'])->name('admin.ui.seo.analyze');
});
