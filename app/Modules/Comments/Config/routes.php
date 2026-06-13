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

// Admin routes
$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router) {
    $router->get('/comments', [\App\Modules\Comments\Controllers\AdminController::class, 'index'])->name('admin.comments');
    $router->post('/comments/{id}/approve', [\App\Modules\Comments\Controllers\AdminController::class, 'approve'])->name('admin.comments.approve');
    $router->post('/comments/{id}/reject', [\App\Modules\Comments\Controllers\AdminController::class, 'reject'])->name('admin.comments.reject');
    $router->post('/comments/{id}/delete', [\App\Modules\Comments\Controllers\AdminController::class, 'delete'])->name('admin.comments.delete');
});

// Frontend routes (comment submission)
$router->post('/comments', [\App\Modules\Comments\Controllers\FrontController::class, 'store'])->name('comments.store');
