<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\SiteMap\Controllers;

use App\Core\BaseController;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Modules\Settings\Services\SiteBrandingTranslationService;
use App\Modules\SiteMap\Services\SiteMapService;

final class FrontController extends BaseController
{
    private SiteMapService $service;

    public function __construct()
    {
        parent::__construct();
        I18n::load('SiteMap');
        $this->service = new SiteMapService();
    }

    public function html(): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Robots-Tag: all');
        }

        http_response_code(200);
        $settings = $this->localizeFrontendSettings(FlatFile::settings());
        $siteName = trim((string) ($settings['site_name'] ?? __('app_name', 'Core')));
        $pageTitle = __('sitemap_public_title', 'SiteMap', ['site_name' => $siteName]);
        $pageDescription = __('sitemap_public_description', 'SiteMap');
        $page = [
            'slug' => 'sitemap',
            'title' => $pageTitle,
            'meta_description' => $pageDescription,
        ];

        $this->renderFrontend('SiteMap/Views/frontend/index', [
            'page' => $page,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'groupedEntries' => $this->service->buildGroupedEntries(),
            'sitemapSummary' => $this->service->buildSummary(),
            'sitemap_page' => true,
        ]);
    }

    public function xml(): void
    {
        $content = is_file($this->service->xmlFilePath())
            ? (string) file_get_contents($this->service->xmlFilePath())
            : $this->service->buildXml();

        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=UTF-8');
            header('X-Robots-Tag: all');
        }

        http_response_code(200);
        echo $content;
        exit;
    }

    protected function renderFrontend(string $template, array $data = []): void
    {
        $settings = $this->localizeFrontendSettings(FlatFile::settings());
        $data['settings'] = $settings;
        $data['locale'] = $this->request->locale();
        $data = array_merge(
            $data,
            $this->getMenuPayload($settings),
            footer_render_payload($settings)
        );

        $this->view->render($template, $data, 'frontend.main');
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    protected function localizeFrontendSettings(array $settings): array
    {
        $service = new SiteBrandingTranslationService();
        return $service->resolveForLocale($settings, (string) $this->request->locale());
    }

    protected function getMenuPayload(array $settings): array
    {
        $menus = FlatFile::settings('menus');
        return [
            'menuStandard' => $menus['main']['items'] ?? [],
        ];
    }
}
