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

// Frontend routes
$router->get('/contact', [\App\Modules\Contact\Controllers\FrontController::class, 'index'])->name('contact.index');
$router->post('/contact/send', [\App\Modules\Contact\Controllers\FrontController::class, 'submit'])->name('contact.submit');

// Admin routes
$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router) {
    $router->get('/contact', [\App\Modules\Contact\Controllers\AdminController::class, 'index'])->name('admin.contact');
    $router->get('/contact/export', [\App\Modules\Contact\Controllers\AdminController::class, 'exportCsv'])->name('admin.contact.export');
    $router->get('/contact/forms', [\App\Modules\Contact\Controllers\AdminController::class, 'formsIndex'])->name('admin.contact.forms');
    $router->get('/contact/forms/create', [\App\Modules\Contact\Controllers\AdminController::class, 'createForm'])->name('admin.contact.forms.create');
    $router->post('/contact/forms', [\App\Modules\Contact\Controllers\AdminController::class, 'storeForm'])->name('admin.contact.forms.store');
    $router->get('/contact/forms/{id}/edit', [\App\Modules\Contact\Controllers\AdminController::class, 'editForm'])->name('admin.contact.forms.edit');
    $router->post('/contact/forms/{id}', [\App\Modules\Contact\Controllers\AdminController::class, 'updateForm'])->name('admin.contact.forms.update');
    $router->post('/contact/forms/{id}/toggle', [\App\Modules\Contact\Controllers\AdminController::class, 'toggleForm'])->name('admin.contact.forms.toggle');
    $router->post('/contact/forms/{id}/default', [\App\Modules\Contact\Controllers\AdminController::class, 'setDefaultForm'])->name('admin.contact.forms.default');
    $router->post('/contact/forms/{id}/delete', [\App\Modules\Contact\Controllers\AdminController::class, 'deleteForm'])->name('admin.contact.forms.delete');
    $router->get('/contact/{id}', [\App\Modules\Contact\Controllers\AdminController::class, 'show'])->name('admin.contact.show');
    $router->get('/contact/{id}/attachment/{index}/download', [\App\Modules\Contact\Controllers\AdminController::class, 'downloadAttachment'])->name('admin.contact.attachment.download');
    $router->post('/contact/{id}/read', [\App\Modules\Contact\Controllers\AdminController::class, 'markRead'])->name('admin.contact.read');
    $router->post('/contact/{id}/new', [\App\Modules\Contact\Controllers\AdminController::class, 'markNew'])->name('admin.contact.new');
    $router->post('/contact/{id}/archive', [\App\Modules\Contact\Controllers\AdminController::class, 'archive'])->name('admin.contact.archive');
    $router->post('/contact/{id}/delete', [\App\Modules\Contact\Controllers\AdminController::class, 'delete'])->name('admin.contact.delete');
});
