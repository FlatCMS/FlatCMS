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
    $router->get('/languages', [\App\Modules\Languages\Controllers\AdminController::class, 'index'])->name('admin.languages');
    $router->get('/languages/create', [\App\Modules\Languages\Controllers\AdminController::class, 'create'])->name('admin.languages.create');
    $router->post('/languages', [\App\Modules\Languages\Controllers\AdminController::class, 'store'])->name('admin.languages.store');
    $router->get('/languages/{code}/edit', [\App\Modules\Languages\Controllers\AdminController::class, 'edit'])->name('admin.languages.edit');
    $router->post('/languages/{code}', [\App\Modules\Languages\Controllers\AdminController::class, 'update'])->name('admin.languages.update');
    $router->post('/languages/{code}/delete', [\App\Modules\Languages\Controllers\AdminController::class, 'delete'])->name('admin.languages.delete');
    $router->post('/languages/{code}/set-default', [\App\Modules\Languages\Controllers\AdminController::class, 'setDefault'])->name('admin.languages.setDefault');
    $router->get('/languages/{code}/translations', [\App\Modules\Languages\Controllers\AdminController::class, 'translations'])->name('admin.languages.translations');
    $router->get('/languages/{code}/module-translations', [\App\Modules\Languages\Controllers\AdminController::class, 'moduleTranslations'])->name('admin.languages.moduleTranslations');
    $router->post('/languages/{code}/translations', [\App\Modules\Languages\Controllers\AdminController::class, 'saveTranslations'])->name('admin.languages.translations.save');
    $router->get('/languages/{code}/scan', [\App\Modules\Languages\Controllers\AdminController::class, 'scan'])->name('admin.languages.scan');
    $router->post('/languages/{code}/scan-fill', [\App\Modules\Languages\Controllers\AdminController::class, 'scanAndFill'])->name('admin.languages.scanFill');
    $router->get('/languages/{code}/export', [\App\Modules\Languages\Controllers\AdminController::class, 'export'])->name('admin.languages.export');
    $router->post('/languages/import', [\App\Modules\Languages\Controllers\AdminController::class, 'import'])->name('admin.languages.import');
});
