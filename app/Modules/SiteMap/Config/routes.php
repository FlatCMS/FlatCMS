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

$router->get('/sitemap', [\App\Modules\SiteMap\Controllers\FrontController::class, 'html'])
    ->name('sitemap.html');
$router->get('/sitemap.html', [\App\Modules\SiteMap\Controllers\FrontController::class, 'html'])
    ->name('sitemap.file.html');
$router->get('/sitemap.xml', [\App\Modules\SiteMap\Controllers\FrontController::class, 'xml'])
    ->name('sitemap.xml');

$router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router): void {
    $router->get('/sitemap', [\App\Modules\SiteMap\Controllers\AdminController::class, 'index'])
        ->name('admin.sitemap');
    $router->post('/sitemap/generate-html', [\App\Modules\SiteMap\Controllers\AdminController::class, 'generateHtml'])
        ->name('admin.sitemap.generate_html');
    $router->post('/sitemap/generate-xml', [\App\Modules\SiteMap\Controllers\AdminController::class, 'generateXml'])
        ->name('admin.sitemap.generate_xml');
});
