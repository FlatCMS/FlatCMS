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
$router->get('/blog', [\App\Modules\Posts\Controllers\FrontController::class, 'index'])->name('blog.index');
$router->get('/blog/categorie/{slug}', [\App\Modules\Posts\Controllers\FrontController::class, 'category'])->name('blog.category');
$router->get('/blog/{slug}', [\App\Modules\Posts\Controllers\FrontController::class, 'show'])->name('blog.show');

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router) {
    $router->get('/posts', [\App\Modules\Posts\Controllers\AdminController::class, 'index'])->name('admin.posts');
    $router->get('/posts/create', [\App\Modules\Posts\Controllers\AdminController::class, 'create'])->name('admin.posts.create');
    $router->post('/posts', [\App\Modules\Posts\Controllers\AdminController::class, 'store'])->name('admin.posts.store');
    $router->post('/posts/batch', [\App\Modules\Posts\Controllers\AdminController::class, 'batch'])->name('admin.posts.batch');
    $router->get('/posts/{id}/edit', [\App\Modules\Posts\Controllers\AdminController::class, 'edit'])->name('admin.posts.edit');
    $router->post('/posts/{id}', [\App\Modules\Posts\Controllers\AdminController::class, 'update'])->name('admin.posts.update');
    $router->post('/posts/{id}/delete', [\App\Modules\Posts\Controllers\AdminController::class, 'delete'])->name('admin.posts.delete');
});
