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

$router->get('/robots.txt', [\App\Modules\Settings\Controllers\FrontController::class, 'robots'])
    ->name('robots');

$router->get('/llms.txt', [\App\Modules\Settings\Controllers\FrontController::class, 'llms'])
    ->name('llms');

$router->get('/admin/settings/routing-probe/{token}', [\App\Modules\Settings\Controllers\AdminController::class, 'routingProbe'])
    ->name('admin.settings.routing_probe');

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router) {
    $router->get('/settings', [\App\Modules\Settings\Controllers\AdminController::class, 'index'])->name('admin.settings');
    $router->get('/settings/help/integrations', [\App\Modules\Settings\Controllers\AdminController::class, 'integrationsHelp'])->name('admin.settings.help.integrations');
    $router->post('/settings', [\App\Modules\Settings\Controllers\AdminController::class, 'update'])->name('admin.settings.update');
    $router->get('/settings/advanced', [\App\Modules\Settings\Controllers\AdminController::class, 'advanced'])->name('admin.settings.advanced');
    $router->post('/settings/advanced/actions', [\App\Modules\Settings\Controllers\AdminController::class, 'runAdvancedAction'])->name('admin.settings.advanced.actions');
    $router->get('/settings/logo-media/files', [\App\Modules\Settings\Controllers\AdminController::class, 'logoMediaFiles'])->name('admin.settings.logo_media.files');
    $router->post('/settings/logo-media/upload', [\App\Modules\Settings\Controllers\AdminController::class, 'logoMediaUpload'])->name('admin.settings.logo_media.upload');
    $router->post('/settings/guided-tour/complete', [\App\Modules\Settings\Controllers\AdminController::class, 'markGuidedTourSeen'])->name('admin.settings.guided_tour.complete');
    $router->post('/settings/guided-tour/reset', [\App\Modules\Settings\Controllers\AdminController::class, 'resetGuidedTourSeen'])->name('admin.settings.guided_tour.reset');
});
