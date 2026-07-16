<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\SiteMap\Services;

use App\Core\FlatFile;
use App\Core\I18n;
use App\Modules\Categories\Services\CategoryTranslationService;
use App\Modules\Pages\Services\PageTranslationService;
use App\Modules\Posts\Services\PostTranslationService;
use App\Modules\Settings\Services\SiteRoutingService;
use App\Modules\Themes\Services\ThemeCustomizationService;

final class SiteMapService
{
    /**
     * @return array<int, array{
     *     loc:string,
     *     lastmod:string,
     *     changefreq:string,
     *     priority:string,
     *     locale:string,
     *     type:string,
     *     label:string
     * }>
     */
    public function buildEntries(): array
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
            $this->addEntry($entries, $seen, [
                'loc' => $this->localizedHomeUrl($locale),
                'lastmod' => $homeLastmod,
                'changefreq' => 'weekly',
                'priority' => '1.0',
                'locale' => $locale,
                'type' => 'home',
                'label' => \__('sitemap_entry_home', 'SiteMap'),
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
                $this->addEntry($entries, $seen, [
                    'loc' => $this->localizedBlogUrl($locale),
                    'lastmod' => '',
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                    'locale' => $locale,
                    'type' => 'blog',
                    'label' => \__('sitemap_entry_blog', 'SiteMap'),
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
            $label = trim((string) ($page['title'] ?? ''));
            if ($label === '') {
                $label = $slug;
            }

            $this->addEntry($entries, $seen, [
                'loc' => $loc,
                'lastmod' => $this->lastmodFromRecord($page),
                'changefreq' => 'monthly',
                'priority' => $priority,
                'locale' => $locale,
                'type' => 'page',
                'label' => $label,
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

            $label = trim((string) ($post['title'] ?? ''));
            if ($label === '') {
                $label = $slug;
            }

            $this->addEntry($entries, $seen, [
                'loc' => $this->siteUrl($settings) . '/' . trim($locale, '/') . '/blog/' . rawurlencode($slug),
                'lastmod' => $this->lastmodFromRecord($post),
                'changefreq' => 'monthly',
                'priority' => '0.7',
                'locale' => $locale,
                'type' => 'post',
                'label' => $label,
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

            $label = trim((string) ($category['name'] ?? $category['title'] ?? ''));
            if ($label === '') {
                $label = $slug;
            }

            $this->addEntry($entries, $seen, [
                'loc' => $this->siteUrl($settings) . '/' . trim($locale, '/') . '/blog/categorie/' . rawurlencode($slug),
                'lastmod' => $this->lastmodFromRecord($category),
                'changefreq' => 'weekly',
                'priority' => '0.5',
                'locale' => $locale,
                'type' => 'category',
                'label' => $label,
            ]);
        }

        usort($entries, static fn (array $left, array $right): int => strcmp($left['loc'], $right['loc']));

        return $entries;
    }

    public function buildXml(): string
    {
        $entries = $this->buildEntries();
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

        return implode(PHP_EOL, $xml) . PHP_EOL;
    }

    public function buildHtml(): string
    {
        $grouped = $this->buildGroupedEntries();
        $settings = FlatFile::settings();
        $siteName = trim((string) ($settings['site_name'] ?? \config('app.name', 'FlatCMS')));
        $pageTitle = \__('sitemap_public_title', 'SiteMap', ['site_name' => $siteName]);
        $description = \__('sitemap_public_description', 'SiteMap');
        $themeCssUrl = function_exists('theme_asset')
            ? \theme_asset('css/style.css', 'frontend')
            : $this->siteUrl($settings) . '/themes/frontend/default/assets/css/style.css';
        $themeCustomizationAsset = (new ThemeCustomizationService())->assetForActiveTheme('frontend', $settings);
        $sitemapCssUrl = module_asset('SiteMap', 'css/sitemap-front.css');
        $baseUrl = $this->siteUrl($settings);
        $locale = trim((string) I18n::getLocale());
        if ($locale === '') {
            $locale = trim((string) ($settings['default_language'] ?? 'fr-FR'));
        }
        $frontendTheme = trim((string) ($settings['frontend_theme'] ?? 'default'));
        $bodyClass = 'theme-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $frontendTheme);

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="<?= $this->html($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index,follow">
    <title><?= $this->html($pageTitle) ?></title>
    <meta name="description" content="<?= $this->html($description) ?>">
    <link rel="canonical" href="<?= $this->html($baseUrl . '/sitemap') ?>">
    <link rel="stylesheet" href="<?= $this->html($themeCssUrl) ?>">
<?php if ($themeCustomizationAsset !== ''): ?>
    <link rel="stylesheet" href="<?= $this->html($themeCustomizationAsset) ?>">
<?php endif; ?>
    <link rel="stylesheet" href="<?= $this->html($sitemapCssUrl) ?>">
</head>
<body id="flatcms" class="<?= $this->html((string) $bodyClass) ?>">
    <main class="site-main sitemap-public sitemap-public--standalone">
        <section class="page-header sitemap-public-header">
            <div class="container sitemap-public-header-inner">
                <div class="sitemap-public-intro">
                    <p class="sitemap-public-kicker"><?= $this->html(\__('sitemap_title', 'SiteMap')) ?></p>
                    <h1><?= $this->html($pageTitle) ?></h1>
                    <p><?= $this->html($description) ?></p>
                </div>
                <div class="sitemap-public-summary">
                    <article class="sitemap-public-summary-card">
                        <span class="sitemap-public-summary-label"><?= $this->html(\__('sitemap_stat_entries', 'SiteMap')) ?></span>
                        <strong class="sitemap-public-summary-value"><?= $this->html((string) ((int) ($this->buildSummary()['entries'] ?? 0))) ?></strong>
                    </article>
                    <article class="sitemap-public-summary-card">
                        <span class="sitemap-public-summary-label"><?= $this->html(\__('sitemap_stat_locales', 'SiteMap')) ?></span>
                        <strong class="sitemap-public-summary-value"><?= $this->html((string) ((int) ($this->buildSummary()['locales'] ?? 0))) ?></strong>
                    </article>
                </div>
            </div>
        </section>

        <section class="page-content sitemap-public-content">
            <div class="container sitemap-public-stack">
        <?php foreach ($grouped as $localeSection): ?>
            <?php
            $localeCode = trim((string) ($localeSection['locale'] ?? ''));
            $localeLabel = trim((string) ($localeSection['label'] ?? $localeCode));
            $sections = is_array($localeSection['sections'] ?? null) ? $localeSection['sections'] : [];
            ?>
                <article class="card sitemap-public-locale-card">
                    <div class="card-body sitemap-public-locale-body">
                        <div class="sitemap-public-locale-head">
                            <h2 class="card-title"><?= $this->html(\__('sitemap_locale_heading', 'SiteMap', ['locale' => $localeLabel])) ?></h2>
                            <span class="badge"><?= $this->html($localeCode) ?></span>
                        </div>
                        <div class="sitemap-public-grid">
                    <?php foreach ($sections as $section): ?>
                        <?php
                        $items = is_array($section['items'] ?? null) ? $section['items'] : [];
                        if ($items === []) {
                            continue;
                        }
                        ?>
                            <section class="sitemap-public-section card">
                                <div class="card-body sitemap-public-section-body">
                                    <h3 class="card-title sitemap-public-section-title"><?= $this->html((string) ($section['label'] ?? '')) ?></h3>
                                    <ul class="sitemap-public-links">
                                <?php foreach ($items as $item): ?>
                                        <li>
                                            <a href="<?= $this->html((string) ($item['loc'] ?? '')) ?>">
                                                <?= $this->html((string) ($item['label'] ?? '')) ?>
                                            </a>
                                        </li>
                                <?php endforeach; ?>
                                    </ul>
                                </div>
                            </section>
                    <?php endforeach; ?>
                        </div>
                    </div>
                </article>
        <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @return array<int, array{
     *     locale:string,
     *     label:string,
     *     sections:array<int, array{
     *         type:string,
     *         label:string,
     *         items:array<int, array{loc:string,label:string}>
     *     }>
     * }>
     */
    public function buildGroupedEntries(): array
    {
        $grouped = $this->groupEntriesByLocale($this->buildEntries());
        $locale = trim((string) I18n::getLocale());
        if ($locale === '') {
            $locale = trim((string) (FlatFile::settings()['default_language'] ?? 'fr-FR'));
        }

        $sections = [];
        foreach ($grouped as $localeKey => $localeSections) {
            $sectionRows = [];
            foreach ($localeSections as $type => $items) {
                if ($items === []) {
                    continue;
                }

                $sectionRows[] = [
                    'type' => $type,
                    'label' => $this->typeLabel($type),
                    'items' => $items,
                ];
            }

            $sections[] = [
                'locale' => $localeKey,
                'label' => I18n::getLocalizedLanguageName($localeKey, $locale),
                'sections' => $sectionRows,
            ];
        }

        return $sections;
    }

    /**
     * @return array{entries:int,pages:int,posts:int,categories:int,locales:int}
     */
    public function buildSummary(): array
    {
        $entries = $this->buildEntries();
        $pages = 0;
        $posts = 0;
        $categories = 0;
        $locales = [];

        foreach ($entries as $entry) {
            $type = (string) ($entry['type'] ?? '');
            $locale = trim((string) ($entry['locale'] ?? ''));
            if ($locale !== '') {
                $locales[$locale] = true;
            }

            if ($type === 'page' || $type === 'home' || $type === 'blog') {
                $pages++;
            } elseif ($type === 'post') {
                $posts++;
            } elseif ($type === 'category') {
                $categories++;
            }
        }

        return [
            'entries' => count($entries),
            'pages' => $pages,
            'posts' => $posts,
            'categories' => $categories,
            'locales' => count($locales),
        ];
    }

    public function writeHtmlFile(): string
    {
        return $this->writePublicFile($this->htmlFilePath(), $this->buildHtml(), 'sitemap_write_failed_html');
    }

    public function writeXmlFile(): string
    {
        return $this->writePublicFile($this->xmlFilePath(), $this->buildXml(), 'sitemap_write_failed_xml');
    }

    public function htmlFilePath(): string
    {
        return rtrim((string) (defined('PUBLIC_PATH') ? PUBLIC_PATH : BASE_PATH . '/public'), '/') . '/sitemap.html';
    }

    public function xmlFilePath(): string
    {
        return rtrim((string) (defined('PUBLIC_PATH') ? PUBLIC_PATH : BASE_PATH . '/public'), '/') . '/sitemap.xml';
    }

    public function htmlUrl(): string
    {
        return $this->siteUrl(FlatFile::settings()) . '/sitemap';
    }

    public function xmlUrl(): string
    {
        return $this->siteUrl(FlatFile::settings()) . '/sitemap.xml';
    }

    private function writePublicFile(string $path, string $content, string $errorKey): string
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('sitemap_directory_missing');
        }

        if (file_put_contents($path, $content, LOCK_EX) === false) {
            throw new \RuntimeException($errorKey);
        }

        return $path;
    }

    /**
     * @param array<int, array{
     *     loc:string,
     *     lastmod:string,
     *     changefreq:string,
     *     priority:string,
     *     locale:string,
     *     type:string,
     *     label:string
     * }> $entries
     * @return array<string, array<string, array<int, array<string, string>>>>
     */
    private function groupEntriesByLocale(array $entries): array
    {
        $grouped = [];

        foreach ($entries as $entry) {
            $locale = trim((string) ($entry['locale'] ?? ''));
            $type = trim((string) ($entry['type'] ?? 'page'));
            if ($locale === '') {
                $locale = 'fr-FR';
            }

            if (!isset($grouped[$locale])) {
                $grouped[$locale] = [
                    'home' => [],
                    'blog' => [],
                    'page' => [],
                    'post' => [],
                    'category' => [],
                ];
            }

            if (!array_key_exists($type, $grouped[$locale])) {
                $grouped[$locale][$type] = [];
            }

            $grouped[$locale][$type][] = [
                'loc' => (string) ($entry['loc'] ?? ''),
                'label' => (string) ($entry['label'] ?? ''),
            ];
        }

        ksort($grouped);

        return $grouped;
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'home' => \__('sitemap_section_home', 'SiteMap'),
            'blog' => \__('sitemap_section_blog', 'SiteMap'),
            'post' => \__('sitemap_section_posts', 'SiteMap'),
            'category' => \__('sitemap_section_categories', 'SiteMap'),
            default => \__('sitemap_section_pages', 'SiteMap'),
        };
    }

    /**
     * @param array<int, array{
     *     loc:string,
     *     lastmod:string,
     *     changefreq:string,
     *     priority:string,
     *     locale:string,
     *     type:string,
     *     label:string
     * }> $entries
     * @param array<string, bool> $seen
     * @param array{
     *     loc:string,
     *     lastmod:string,
     *     changefreq:string,
     *     priority:string,
     *     locale:string,
     *     type:string,
     *     label:string
     * } $entry
     */
    private function addEntry(array &$entries, array &$seen, array $entry): void
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

    /**
     * @param array<string, mixed> $settings
     */
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

    private function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
