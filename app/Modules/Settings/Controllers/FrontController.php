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
use App\Modules\Categories\Services\CategoryTranslationService;
use App\Modules\Pages\Services\PageTranslationService;
use App\Modules\Posts\Services\PostTranslationService;
use App\Modules\Settings\Services\SiteRoutingService;

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
        $settings = FlatFile::settings();
        $entries = $this->buildSitemapEntries();

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($entries as $entry) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . $this->xml((string) ($entry['loc'] ?? '')) . '</loc>';

            $lastmod = trim((string) ($entry['lastmod'] ?? ''));
            if ($lastmod !== '') {
                $xml[] = '    <lastmod>' . $this->xml($lastmod) . '</lastmod>';
            }

            $changefreq = trim((string) ($entry['changefreq'] ?? ''));
            if ($changefreq !== '') {
                $xml[] = '    <changefreq>' . $this->xml($changefreq) . '</changefreq>';
            }

            $priority = trim((string) ($entry['priority'] ?? ''));
            if ($priority !== '') {
                $xml[] = '    <priority>' . $this->xml($priority) . '</priority>';
            }

            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=UTF-8');
            header('X-Robots-Tag: all');
        }

        http_response_code(200);
        echo implode(PHP_EOL, $xml) . PHP_EOL;
        exit;
    }

    /**
     * @return array<int, array{loc:string,lastmod:string,changefreq:string,priority:string}>
     */
    private function buildSitemapEntries(): array
    {
        $settings = FlatFile::settings();
        $siteRouting = new SiteRoutingService();
        $pages = new PageTranslationService();
        $posts = new PostTranslationService();
        $categories = new CategoryTranslationService();
        $entries = [];
        $seen = [];
        $locales = I18n::getSupportedLocales();

        foreach ($locales as $locale) {
            $homePage = $siteRouting->resolveHomepagePage($locale);
            $homeLastmod = is_array($homePage) ? $this->lastmodFromRecord($homePage) : '';
            $this->addSitemapEntry($entries, $seen, [
                'loc' => $this->localizedHomeUrl($locale),
                'lastmod' => $homeLastmod,
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ]);

            $hasPostsForLocale = false;
            foreach ($posts->all() as $post) {
                if (
                    (string) ($post['locale'] ?? '') === $locale
                    && $posts->resolveEffectiveStatus($post) === 'published'
                ) {
                    $hasPostsForLocale = true;
                    break;
                }
            }

            if ($hasPostsForLocale) {
                $this->addSitemapEntry($entries, $seen, [
                    'loc' => $this->localizedBlogUrl($locale),
                    'lastmod' => '',
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ]);
            }
        }

        foreach ($pages->all() as $page) {
            if ($pages->resolveEffectiveStatus($page) !== 'published') {
                continue;
            }

            $locale = trim((string) ($page['locale'] ?? ''));
            $slug = trim((string) ($page['slug'] ?? ''));
            if ($locale === '' || $slug === '') {
                continue;
            }

            $loc = ($slug === 'home' || $siteRouting->isHomepagePage($page))
                ? $this->localizedHomeUrl($locale)
                : $this->siteUrl($settings) . '/' . trim($locale, '/') . '/page/' . rawurlencode($slug);

            $priority = ($slug === 'contact') ? '0.7' : '0.6';

            $this->addSitemapEntry($entries, $seen, [
                'loc' => $loc,
                'lastmod' => $this->lastmodFromRecord($page),
                'changefreq' => 'monthly',
                'priority' => $priority,
            ]);
        }

        foreach ($posts->all() as $post) {
            if ($posts->resolveEffectiveStatus($post) !== 'published') {
                continue;
            }

            $locale = trim((string) ($post['locale'] ?? ''));
            $slug = trim((string) ($post['slug'] ?? ''));
            if ($locale === '' || $slug === '') {
                continue;
            }

            $this->addSitemapEntry($entries, $seen, [
                'loc' => $this->siteUrl($settings) . '/' . trim($locale, '/') . '/blog/' . rawurlencode($slug),
                'lastmod' => $this->lastmodFromRecord($post),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ]);
        }

        foreach ($categories->all() as $category) {
            if ($categories->resolveEffectiveStatus($category) !== 'active') {
                continue;
            }

            if (trim((string) ($category['module'] ?? 'blog')) !== 'blog') {
                continue;
            }

            $locale = trim((string) ($category['locale'] ?? ''));
            $slug = trim((string) ($category['slug'] ?? ''));
            if ($locale === '' || $slug === '') {
                continue;
            }

            $this->addSitemapEntry($entries, $seen, [
                'loc' => $this->siteUrl($settings) . '/' . trim($locale, '/') . '/blog/categorie/' . rawurlencode($slug),
                'lastmod' => $this->lastmodFromRecord($category),
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ]);
        }

        usort($entries, static fn (array $left, array $right): int => strcmp($left['loc'], $right['loc']));

        return $entries;
    }

    /**
     * @param array<int, array{loc:string,lastmod:string,changefreq:string,priority:string}> $entries
     * @param array<string, bool> $seen
     * @param array{loc:string,lastmod:string,changefreq:string,priority:string} $entry
     */
    private function addSitemapEntry(array &$entries, array &$seen, array $entry): void
    {
        $loc = trim((string) ($entry['loc'] ?? ''));
        if (!$this->isAbsoluteUrl($loc) || isset($seen[$loc])) {
            return;
        }

        $seen[$loc] = true;
        $entries[] = $entry;
    }

    private function lastmodFromRecord(array $record): string
    {
        $candidate = trim((string) ($record['updated_at'] ?? $record['created_at'] ?? ''));
        if ($candidate === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($candidate))->format('c');
        } catch (\Throwable) {
            return '';
        }
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

    private function isAbsoluteUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
