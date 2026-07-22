<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Pages\Controllers;

use App\Core\BaseController;
use App\Core\ContentDocumentStore;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Modules\Pages\Services\PageContentRenderer;
use App\Modules\Pages\Services\PageTranslationService;
use App\Modules\Pages\Support\SystemPages;
use App\Modules\Posts\Services\PostTranslationService;
use App\Modules\Settings\Services\SiteRoutingService;
use App\Modules\Settings\Services\SiteBrandingTranslationService;

class FrontController extends BaseController
{
    private ContentDocumentStore $pages;
    private PageTranslationService $translations;
    private SiteRoutingService $siteRouting;
    private ?PageContentRenderer $pageContentRenderer = null;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Pages');
        $this->pages = ContentDocumentStore::for('core/pages');
        $this->translations = new PageTranslationService($this->pages);
        $this->siteRouting = new SiteRoutingService($this->translations);
    }

    public function home(): void
    {
        I18n::load('Dashboard');
        I18n::load('Posts');
        $settings = $this->localizeFrontendSettings(FlatFile::settings());

        $configuredHomepage = $this->resolveConfiguredHomePage();
        $useConfiguredHomepage = is_array($configuredHomepage);
        $page = $configuredHomepage ?? $this->resolveHomePage();

        if (!$page) {
            $page = [
                'title' => __('welcome', 'Core'),
                'content' => '<p>' . __('welcome_description', 'Dashboard') . '</p>',
            ];
            $useConfiguredHomepage = false;
        }
        $page = $this->prepareRenderablePage($page, $this->request->uri());
        $frontendNotice = $this->buildTranslationFallbackNotice($page, (string) $this->request->locale());
        $pageNotices = $this->buildPageFrontendNotices($page, (string) $this->request->locale());

        if ($useConfiguredHomepage) {
            hook_run('pages.before_render', $page);
            $this->renderFrontend('pages/show', [
                'page' => $page,
                'settings' => $settings,
                'pageTitle' => $this->resolvePageTitle($page, $settings['site_name'] ?? __('app_name', 'Core')),
                'frontendNotice' => $frontendNotice,
                'pageNotices' => $pageNotices,
            ]);
            hook_run('pages.after_render', $page);
            return;
        }

        // Get recent posts for homepage
        $posts = ContentDocumentStore::for('core/posts');
        $postTranslations = new PostTranslationService($posts);
        $currentLocale = (string) $this->request->locale();
        $recentPosts = array_filter(
            $postTranslations->all(),
            fn (array $post): bool => $postTranslations->resolveEffectiveStatus($post) === 'published'
                && (string) ($post['locale'] ?? '') === $currentLocale
        );
        usort($recentPosts, fn($a, $b) => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));
        $recentPosts = array_slice($recentPosts, 0, 3);

        // Get site settings
        hook_run('pages.before_render', $page);
        $this->renderFrontend('pages/home', [
            'page' => $page,
            'recentPosts' => $recentPosts,
            'settings' => $settings,
            'pageTitle' => $this->resolvePageTitle($page, $settings['site_name'] ?? __('app_name', 'Core')),
            'frontendNotice' => $frontendNotice,
            'pageNotices' => $pageNotices,
        ]);
        hook_run('pages.after_render', $page);
    }

    public function show(string $slug): void
    {
        $this->syncRequiredPageForSlug($slug);
        $currentLocale = (string) $this->request->locale();
        $page = $this->translations->findBySlugAndLocale($slug, $currentLocale, false);

        if (!$page) {
            $anyLocalePage = $this->translations->findBySlug($slug, false);
            if (!is_array($anyLocalePage)) {
                $requiredKey = SystemPages::keyForSlug($slug);
                if ($requiredKey !== null) {
                    $anyLocalePage = SystemPages::findByKey($this->pages, $requiredKey);
                }
            }

            if (is_array($anyLocalePage) && $this->supportsTranslatedClassicFrontend($anyLocalePage)) {
                $translationGroup = (string) ($anyLocalePage['translation_group'] ?? '');
                $localizedPage = $this->translations->findByTranslationGroupAndLocale($translationGroup, $currentLocale, true);
                if (is_array($localizedPage)) {
                    $this->redirect(url($this->buildLocalizedPagePathForPage($localizedPage, $currentLocale)), 301);
                    return;
                }

                $sourcePage = $this->translations->resolveSourcePage($translationGroup);
                if (is_array($sourcePage) && $this->isPublishedPage($sourcePage) && trim((string) ($sourcePage['slug'] ?? '')) !== '') {
                    $page = $sourcePage;
                }
            } elseif (is_array($anyLocalePage)) {
                $page = $anyLocalePage;
            }
        }

        if (!$page) {
            $page = $this->findLegacyPageBySlug($slug);
            if (is_array($page)) {
                $canonicalSlug = trim((string) ($page['slug'] ?? ''));
                if ($canonicalSlug !== '' && $canonicalSlug !== $slug) {
                    $targetLocale = $this->supportsTranslatedClassicFrontend($page)
                        ? (string) ($page['locale'] ?? $currentLocale)
                        : $currentLocale;
                    $this->redirect(url($this->buildLocalizedPagePathForPage($page, $targetLocale)), 301);
                    return;
                }
            }
        }

        if (!$page || !$this->isPublishedPage($page)) {
            $this->renderNotFound();
            return;
        }

        if ($this->siteRouting->isHomepagePage($page)) {
            $this->redirect(url($this->buildLocalizedHomePath($currentLocale)), 301);
            return;
        }
        $page = $this->prepareRenderablePage($page, $this->request->uri());
        $frontendNotice = $this->buildTranslationFallbackNotice($page, $currentLocale);
        $pageNotices = $this->buildPageFrontendNotices($page, $currentLocale);

        hook_run('pages.before_render', $page);
        $this->renderFrontend('pages/show', [
            'page' => $page,
            'pageTitle' => $this->resolvePageTitle($page, __('app_name', 'Core')),
            'frontendNotice' => $frontendNotice,
            'pageNotices' => $pageNotices,
        ]);
        hook_run('pages.after_render', $page);
    }

    protected function renderFrontend(string $template, array $data = []): void
    {
        $settings = $this->localizeFrontendSettings(FlatFile::settings());
        
        // Add common frontend data
        $data['settings'] = $settings;
        $data['locale'] = $this->request->locale();
        $data = array_merge(
            $data,
            $this->getMenuPayload($settings),
            footer_render_payload($settings)
        );
        
        $this->view->render("frontend/{$template}", $data, 'frontend.main');
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

    protected function resolvePageTitle(array $page, string $fallback = ''): string
    {
        $metaTitle = trim((string) ($page['meta_title'] ?? ''));
        if ($metaTitle !== '') {
            return $metaTitle;
        }

        $title = trim((string) ($page['title'] ?? ''));
        if ($title !== '') {
            return $title;
        }

        return trim($fallback);
    }

    /**
     * @return array<string, string>|null
     */
    protected function buildTranslationFallbackNotice(array $page, string $requestedLocale): ?array
    {
        $normalizedRequestedLocale = $this->translations->normalizeLocale($requestedLocale);
        $pageLocale = $this->translations->normalizeLocale((string) ($page['locale'] ?? ''));

        if ($normalizedRequestedLocale === '' || $pageLocale === '' || $normalizedRequestedLocale === $pageLocale) {
            return null;
        }

        return [
            'type' => 'warning',
            'message' => __('frontend_translation_fallback_notice', 'Pages', [
                'requested_locale' => $this->translations->getLocaleLabel($normalizedRequestedLocale, $normalizedRequestedLocale),
                'source_locale' => $this->translations->getLocaleLabel($pageLocale, $normalizedRequestedLocale),
            ]),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function buildPageFrontendNotices(array $page, string $requestedLocale): array
    {
        $results = hook_run('pages.frontend.notices', [
            'page' => $page,
            'requested_locale' => $requestedLocale,
        ]);

        $notices = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            if (isset($result['notices']) && is_array($result['notices'])) {
                foreach ($result['notices'] as $notice) {
                    $normalized = $this->normalizePageFrontendNotice($notice);
                    if ($normalized !== null) {
                        $notices[] = $normalized;
                    }
                }
                continue;
            }

            $normalized = $this->normalizePageFrontendNotice($result);
            if ($normalized !== null) {
                $notices[] = $normalized;
            }
        }

        return $notices;
    }

    /**
     * @param mixed $notice
     * @return array<string, string>|null
     */
    private function normalizePageFrontendNotice(mixed $notice): ?array
    {
        if (!is_array($notice)) {
            return null;
        }

        $message = trim((string) ($notice['message'] ?? ''));
        if ($message === '') {
            return null;
        }

        $type = strtolower(trim((string) ($notice['type'] ?? 'warning')));
        if (!in_array($type, ['success', 'error', 'warning', 'info'], true)) {
            $type = 'warning';
        }

        return [
            'type' => $type,
            'title' => trim((string) ($notice['title'] ?? '')),
            'message' => $message,
        ];
    }

    protected function getMenuPayload(array $settings): array
    {
        $menus = FlatFile::settings('menus');
        return [
            'menuStandard' => $menus['main']['items'] ?? [],
        ];
    }

    private function findLegacyPageBySlug(string $slug): ?array
    {
        $legacyKey = $this->compactSlugKey($slug);
        if ($legacyKey === '') {
            return null;
        }

        foreach ($this->pages->all() as $page) {
            if (!is_array($page)) {
                continue;
            }

            $currentSlug = trim((string) ($page['slug'] ?? ''));
            if ($currentSlug === '' || $currentSlug === $slug) {
                continue;
            }

            if ($this->compactSlugKey($currentSlug) !== $legacyKey) {
                continue;
            }

            return $page;
        }

        return null;
    }

    private function compactSlugKey(string $slug): string
    {
        $value = trim(strtolower($slug));
        if ($value === '') {
            return '';
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (!is_string($ascii) || $ascii === '') {
            $ascii = $value;
        }

        return preg_replace('/[^a-z0-9]+/', '', $ascii) ?: '';
    }

    private function buildLocalizedHomePath(?string $locale = null): string
    {
        $resolvedLocale = trim((string) ($locale ?? $this->request->locale()), '/');
        $prefix = $resolvedLocale !== '' ? '/' . $resolvedLocale : '';
        return $prefix !== '' ? $prefix : '/';
    }

    private function buildLocalizedPagePathForPage(array $page, ?string $locale = null): string
    {
        $resolvedLocale = trim((string) ($locale ?? ($page['locale'] ?? $this->request->locale())), '/');
        $slug = trim((string) ($page['slug'] ?? ''));

        if ($slug === '' || $slug === 'home' || $this->siteRouting->isHomepagePage($page)) {
            return $this->buildLocalizedHomePath($resolvedLocale);
        }

        $prefix = $resolvedLocale !== '' ? '/' . $resolvedLocale : '';
        return $prefix . '/page/' . rawurlencode($slug);
    }

    private function resolveHomePage(): ?array
    {
        $sourcePage = $this->translations->findBySlug('home', false);
        if (!is_array($sourcePage)) {
            return null;
        }

        if (!$this->supportsTranslatedClassicFrontend($sourcePage)) {
            return $this->isPublishedPage($sourcePage) ? $sourcePage : null;
        }

        $currentLocale = (string) $this->request->locale();
        $translationGroup = (string) ($sourcePage['translation_group'] ?? '');
        $localizedPage = $this->translations->findByTranslationGroupAndLocale($translationGroup, $currentLocale, true);
        if (is_array($localizedPage)) {
            return $localizedPage;
        }

        $fallbackPage = $this->translations->resolveSourcePage($translationGroup);
        if (is_array($fallbackPage) && $this->isPublishedPage($fallbackPage)) {
            return $fallbackPage;
        }

        return null;
    }

    private function resolveConfiguredHomePage(): ?array
    {
        return $this->siteRouting->resolveHomepagePage((string) $this->request->locale());
    }

    private function isPublishedPage(array $page): bool
    {
        if ($this->supportsTranslatedClassicFrontend($page)) {
            return $this->translations->resolveEffectiveStatus($page) === 'published';
        }

        return (string) ($page['status'] ?? 'draft') === 'published';
    }

    private function supportsTranslatedClassicFrontend(array $page): bool
    {
        return trim((string) ($page['translation_group'] ?? $page['id'] ?? '')) !== '';
    }

    protected function renderNotFound(): void
    {
        http_response_code(404);
        $this->render('errors/404', [
            'pageTitle' => '404 - ' . __('error.not_found', 'Core'),
        ]);
    }

    protected function prepareRenderablePage(array $page, string $sourcePath): array
    {
        return $this->pageContentRenderer()->preparePage($page, $sourcePath, $this->request->locale());
    }

    protected function applyShortcodes(array $page): array
    {
        $content = (string) ($page['content'] ?? '');
        if ($content === '') {
            return $page;
        }

        $page['content'] = flatcms_render_shortcodes($content, [
            'source_url' => url($this->request->uri()),
            'locale' => $this->request->locale(),
        ]);
        $page['content'] = $this->normalizeContentMediaUrls((string) $page['content']);

        return $page;
    }

    protected function normalizeContentMediaUrls(string $content): string
    {
        if ($content === '') {
            return $content;
        }

        return (string) preg_replace_callback(
            '/\b(src|href|poster)\s*=\s*(["\'])(.*?)\2/i',
            function (array $matches): string {
                $attribute = (string) ($matches[1] ?? '');
                $quote = (string) ($matches[2] ?? '"');
                $rawValue = html_entity_decode((string) ($matches[3] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if ($rawValue === '' || str_starts_with($rawValue, 'data:') || str_starts_with($rawValue, 'blob:')) {
                    return $matches[0];
                }

                if (!$this->shouldNormalizeMediaUrl($attribute, $rawValue)) {
                    return $matches[0];
                }

                $normalized = site_media_url($rawValue);
                if ($normalized === '') {
                    return $matches[0];
                }

                return $attribute . '=' . $quote . htmlspecialchars($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8') . $quote;
            },
            $content
        );
    }

    private function shouldNormalizeMediaUrl(string $attribute, string $rawValue): bool
    {
        $attributeName = strtolower(trim($attribute));
        if ($attributeName === 'src' || $attributeName === 'poster') {
            return true;
        }

        if ($attributeName !== 'href') {
            return false;
        }

        $value = trim($rawValue);
        if ($value === '') {
            return false;
        }

        if (preg_match('~^(#|mailto:|tel:|javascript:)~i', $value) === 1) {
            return false;
        }

        if (preg_match('~^(https?:)?//~i', $value) === 1) {
            return false;
        }

        if (flatcms_normalize_upload_media_path($value) !== '') {
            return true;
        }

        $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
        if ($path !== '' && flatcms_normalize_upload_media_path($path) !== '') {
            return true;
        }

        return $path !== '' && preg_match(
            '~\.(?:avif|bmp|gif|ico|jpe?g|png|svg|webp|mp4|webm|ogv|mp3|wav|ogg|pdf|docx?|xlsx?|pptx?|zip|rar|7z|csv|txt)$~i',
            $path
        ) === 1;
    }

    protected function syncRequiredPageForSlug(string $slug): void
    {
        if (SystemPages::keyForSlug($slug) === null) {
            return;
        }

        SystemPages::ensureRequired(
            $this->pages,
            static function (string $key): string {
                return __($key, 'Pages');
            }
        );
    }

    protected function pageContentRenderer(): PageContentRenderer
    {
        if (!$this->pageContentRenderer instanceof PageContentRenderer) {
            $this->pageContentRenderer = new PageContentRenderer($this->translations);
        }

        return $this->pageContentRenderer;
    }
}
