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

$router->get('/release/{file*}', static function (string $file): void {
    $safeFile = ltrim($file, '/');
    if ($safeFile === '') {
        http_response_code(404);
        return;
    }

    redirect(url('/release/' . $safeFile));
});

$router->get('/api/updates/index.json', [\App\Controllers\UpdateCatalogController::class, 'index']);
$router->get('/api/updates/{catalog}.json', [\App\Controllers\UpdateCatalogController::class, 'show']);
$router->get('/api/updates/download/{catalog}/{slug}/{version}', [\App\Controllers\UpdateCatalogController::class, 'download']);
$router->get('/api/updates/changelog/{catalog}/{slug}/{version}', [\App\Controllers\UpdateCatalogController::class, 'changelog']);
