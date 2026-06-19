<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use App\Core\Router;

/** @var Router $router */

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router) {
    

// Routes directes de sécurité : évitent tout problème de préfixe/group sur certaines configurations.
$router->get('/admin/google-forms', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'index']);
$router->get('/admin/google-forms/settings', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'settings']);
$router->post('/admin/google-forms/settings/save', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'saveSettings']);
$router->get('/admin/google-forms/connect', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'connect']);
$router->get('/admin/google-forms/oauth/callback', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'oauthCallback']);
$router->post('/admin/google-forms/disconnect', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'disconnect']);
$router->post('/admin/google-forms/forms/refresh', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'refreshForms']);
$router->post('/admin/google-forms/forms/select', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'selectForm']);
$router->post('/admin/google-forms/responses/sync', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'syncResponses']);

$router->get('/google-forms', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'index'])->name('admin.google-forms');
    $router->get('/google-forms/settings', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'settings'])->name('admin.google-forms.settings');
    $router->post('/google-forms/settings/save', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'saveSettings'])->name('admin.google-forms.settings.save');

    $router->get('/google-forms/connect', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'connect'])->name('admin.google-forms.connect');
    $router->get('/google-forms/oauth/callback', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'oauthCallback'])->name('admin.google-forms.oauth.callback');
    $router->post('/google-forms/disconnect', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'disconnect'])->name('admin.google-forms.disconnect');

    $router->post('/google-forms/forms/refresh', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'refreshForms'])->name('admin.google-forms.forms.refresh');
    $router->post('/google-forms/forms/select', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'selectForm'])->name('admin.google-forms.forms.select');
    $router->post('/google-forms/responses/sync', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'syncResponses'])->name('admin.google-forms.responses.sync');
});



// Routes directes de sécurité : évitent tout problème de préfixe/group sur certaines configurations.
$router->get('/admin/google-forms', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'index']);
$router->get('/admin/google-forms/settings', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'settings']);
$router->post('/admin/google-forms/settings/save', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'saveSettings']);
$router->get('/admin/google-forms/connect', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'connect']);
$router->get('/admin/google-forms/oauth/callback', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'oauthCallback']);
$router->post('/admin/google-forms/disconnect', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'disconnect']);
$router->post('/admin/google-forms/forms/refresh', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'refreshForms']);
$router->post('/admin/google-forms/forms/select', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'selectForm']);
$router->post('/admin/google-forms/responses/sync', [\App\Extensions\GoogleForms\Controllers\AdminController::class, 'syncResponses']);

$router->get('/google-forms', [\App\Extensions\GoogleForms\Controllers\FrontController::class, 'index'])->name('google-forms.index');
