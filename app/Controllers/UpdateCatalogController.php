<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\UpdateCatalogService;

final class UpdateCatalogController
{
    public function index(): void
    {
        $payload = (new UpdateCatalogService())->discovery();

        Response::make()
            ->header('Cache-Control', 'public, max-age=300, s-maxage=300')
            ->json($payload);
    }

    public function show(string $catalog): void
    {
        $payload = (new UpdateCatalogService())->catalog($catalog);
        if ($payload === null) {
            Response::make()
                ->status(404)
                ->json([
                    'success' => false,
                    'code' => 'catalog_not_found',
                ], 404);
        }

        Response::make()
            ->header('Cache-Control', 'public, max-age=300, s-maxage=300')
            ->json($payload);
    }

    public function download(string $catalog, string $slug, string $version): void
    {
        $payload = (new UpdateCatalogService())->resolveDownload($catalog, $slug, $version);
        if (!is_array($payload)) {
            Response::make()
                ->status(404)
                ->json([
                    'success' => false,
                    'code' => 'package_not_found',
                ], 404);
        }

        Response::make()
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=3600, immutable')
            ->header('X-Content-Type-Options', 'nosniff')
            ->download((string) ($payload['file_path'] ?? ''), (string) ($payload['filename'] ?? null));
    }

    public function changelog(string $catalog, string $slug, string $version): void
    {
        $contents = (new UpdateCatalogService())->resolveChangelog($catalog, $slug, $version);
        if (!is_string($contents) || $contents === '') {
            Response::make()
                ->status(404)
                ->json([
                    'success' => false,
                    'code' => 'changelog_not_found',
                ], 404);
        }

        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: public, max-age=300, s-maxage=300');
            header('X-Content-Type-Options: nosniff');
        }

        echo $contents;
        exit;
    }
}
