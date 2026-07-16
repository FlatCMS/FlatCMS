<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Settings\Controllers;

use App\Core\BaseController;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Modules\Settings\Services\SiteRoutingService;
use App\Modules\SiteMap\Services\SiteMapService;

class FrontController extends BaseController
{
    public function robots(): void
    {
        $settings = FlatFile::settings();
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            'Disallow: /admin/',
            'Disallow: /login',
            'Disallow: /install/',
            'Disallow: /data/',
            'Disallow: /storage/',
            'Disallow: /config/',
        ];

        $sitemapUrl = $this->siteUrl($settings) . '/sitemap.xml';
        if ($this->isAbsoluteUrl($sitemapUrl)) {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . $sitemapUrl;
        }

        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-Robots-Tag: all');
        }

        http_response_code(200);
        echo implode(PHP_EOL, $lines) . PHP_EOL;
        exit;
    }

    public function llms(): void
    {
        $settings = FlatFile::settings();
        $locale = $this->documentationLocale($settings);
        $previousLocale = I18n::getLocale();
        I18n::setLocale($locale);
        I18n::load('Settings');

        $siteName = trim((string) ($settings['site_name'] ?? config('app.name', 'FlatCMS')));
        $siteDescription = trim((string) ($settings['site_description'] ?? ''));
        $homeUrl = $this->localizedHomeUrl($locale);
        $blogUrl = $this->localizedBlogUrl($locale);
        $contactUrl = $this->localizedContactUrl($locale);
        $sitemapUrl = $this->siteUrl($settings) . '/sitemap.xml';
        $robotsUrl = $this->siteUrl($settings) . '/robots.txt';

        $lines = [
            __('llms_title', 'Settings', ['site_name' => $siteName]),
        ];

        if ($siteDescription !== '') {
            $lines[] = __('llms_description', 'Settings', ['description' => $siteDescription]);
            $lines[] = '';
        }

        $lines[] = __('llms_scope_heading', 'Settings');
        $lines[] = __('llms_scope_public', 'Settings');
        $lines[] = __('llms_scope_canonical', 'Settings');
        $lines[] = '';
        $lines[] = __('llms_access_heading', 'Settings');
        $lines[] = __('llms_access_public', 'Settings');
        $lines[] = __('llms_access_private', 'Settings');
        $lines[] = '';
        $lines[] = __('llms_sources_heading', 'Settings');
        $lines[] = __('llms_source_home', 'Settings', ['url' => $homeUrl]);
        $lines[] = __('llms_source_blog', 'Settings', ['url' => $blogUrl]);
        $lines[] = __('llms_source_contact', 'Settings', ['url' => $contactUrl]);
        $lines[] = __('llms_source_sitemap', 'Settings', ['url' => $sitemapUrl]);
        $lines[] = __('llms_source_robots', 'Settings', ['url' => $robotsUrl]);

        I18n::setLocale($previousLocale);
        I18n::load('Settings');

        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-Robots-Tag: all');
        }

        http_response_code(200);
        echo implode(PHP_EOL, $lines) . PHP_EOL;
        exit;
    }

    public function sitemap(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=UTF-8');
            header('X-Robots-Tag: all');
        }

        http_response_code(200);
        echo (new SiteMapService())->buildXml();
        exit;
    }

    private function siteUrl(array $settings): string
    {
        $configured = trim((string) ($settings['site_url'] ?? ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $scheme = $this->isSecureRequest() ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return rtrim($scheme . '://' . $host, '/');
    }

    private function localizedHomeUrl(string $locale): string
    {
        $locale = trim($locale, '/');
        return $this->siteUrl(FlatFile::settings()) . ($locale !== '' ? '/' . $locale : '');
    }

    private function localizedBlogUrl(string $locale): string
    {
        $locale = trim($locale, '/');
        return $this->siteUrl(FlatFile::settings()) . '/' . $locale . '/blog';
    }

    private function localizedContactUrl(string $locale): string
    {
        $locale = trim($locale, '/');
        return $this->siteUrl(FlatFile::settings()) . '/' . $locale . '/contact';
    }

    private function documentationLocale(array $settings): string
    {
        $locale = trim((string) ($settings['default_language'] ?? config('app.locale', 'fr-FR')));
        $supported = I18n::getSupportedLocales();
        return in_array($locale, $supported, true) ? $locale : ($supported[0] ?? 'fr-FR');
    }

    private function isSecureRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto !== '' && in_array('https', array_map('trim', explode(',', $forwardedProto)), true)) {
            return true;
        }

        return strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https';
    }

}
