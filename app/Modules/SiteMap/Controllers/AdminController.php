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
use App\Core\I18n;
use App\Modules\SiteMap\Services\SiteMapService;

final class AdminController extends BaseController
{
    private SiteMapService $service;

    public function __construct()
    {
        parent::__construct();
        I18n::load('SiteMap');
        $this->service = new SiteMapService();
    }

    public function index(): void
    {
        if (!$this->authorize('sitemap.view')) {
            return;
        }

        $summary = $this->service->buildSummary();

        $this->render('SiteMap/Views/admin/index', [
            'pageTitle' => \__('sitemap_title', 'SiteMap'),
            'summary' => $summary,
            'canManageSiteMap' => can('sitemap.manage'),
            'htmlFile' => $this->describeFile($this->service->htmlFilePath(), $this->service->htmlUrl()),
            'xmlFile' => $this->describeFile($this->service->xmlFilePath(), $this->service->xmlUrl()),
        ], 'admin.main');
    }

    public function generateHtml(): void
    {
        if (!$this->authorize('sitemap.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $path = $this->service->writeHtmlFile();
            $this->session->flash('success', \__('sitemap_generate_html_success', 'SiteMap', [
                'path' => $path,
            ]));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', \__($exception->getMessage(), 'SiteMap'));
        }

        $this->redirect(\url('/admin/sitemap'));
    }

    public function generateXml(): void
    {
        if (!$this->authorize('sitemap.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $path = $this->service->writeXmlFile();
            $this->session->flash('success', \__('sitemap_generate_xml_success', 'SiteMap', [
                'path' => $path,
            ]));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', \__($exception->getMessage(), 'SiteMap'));
        }

        $this->redirect(\url('/admin/sitemap'));
    }

    /**
     * @return array{exists:bool,path:string,url:string,updated_at:string,status_label:string}
     */
    private function describeFile(string $path, string $url): array
    {
        $exists = is_file($path);
        $updatedAt = '';

        if ($exists) {
            $timestamp = filemtime($path);
            if (is_int($timestamp) && $timestamp > 0) {
                $updatedAt = (new \DateTimeImmutable('@' . $timestamp))
                    ->setTimezone(new \DateTimeZone((string) \config('app.timezone', 'Europe/Paris')))
                    ->format('Y-m-d H:i:s');
            }
        }

        return [
            'exists' => $exists,
            'path' => $path,
            'url' => $url,
            'updated_at' => $updatedAt,
            'status_label' => $exists ? \__('sitemap_status_ready', 'SiteMap') : \__('sitemap_status_missing', 'SiteMap'),
        ];
    }
}
