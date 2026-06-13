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
    // Index - Vue des dossiers
    $router->get('/media', [\App\Modules\Media\Controllers\AdminController::class, 'index'])->name('admin.media');

    // Vue d'un dossier spécifique
    $router->get('/media/folder/{name}', [\App\Modules\Media\Controllers\AdminController::class, 'folder'])->name('admin.media.folder');

    // Upload de fichier(s)
    $router->post('/media/upload', [\App\Modules\Media\Controllers\AdminController::class, 'upload'])->name('admin.media.upload');

    // Suppression d'un média par ID
    $router->post('/media/{id}/delete', [\App\Modules\Media\Controllers\AdminController::class, 'delete'])->name('admin.media.delete');

    // Suppression par chemin (fichiers non indexés)
    $router->post('/media/delete-path', [\App\Modules\Media\Controllers\AdminController::class, 'deletePath'])->name('admin.media.deletePath');

    // Suppression groupée (envoi vers la corbeille)
    $router->post('/media/batch-delete', [\App\Modules\Media\Controllers\AdminController::class, 'batchDelete'])->name('admin.media.batchDelete');

    // Synchronisation
    $router->post('/media/sync', [\App\Modules\Media\Controllers\AdminController::class, 'sync'])->name('admin.media.sync');

    // Indexation IA
    $router->post('/media/ai-index', [\App\Modules\Media\Controllers\AdminController::class, 'aiIndex'])->name('admin.media.aiIndex');

    // API - Liste des fichiers d'un dossier (AJAX)
    $router->get('/media/api/files', [\App\Modules\Media\Controllers\AdminController::class, 'apiFiles'])->name('admin.media.api.files');

    // API - Liste des images uniquement (AJAX)
    $router->get('/media/api/images', [\App\Modules\Media\Controllers\AdminController::class, 'apiImages'])->name('admin.media.api.images');

    // API - Détails d'un média (AJAX)
    $router->get('/media/{id}/details', [\App\Modules\Media\Controllers\AdminController::class, 'details'])->name('admin.media.details');

    // API - Statistiques (AJAX)
    $router->get('/media/api/stats', [\App\Modules\Media\Controllers\AdminController::class, 'apiStats'])->name('admin.media.api.stats');
});
