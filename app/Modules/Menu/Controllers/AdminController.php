<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Menu\Controllers;

use App\Modules\Categories\Services\CategoryTranslationService;
use App\Core\BaseController;
use App\Core\ContentDocumentStore;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Helpers\IconHelper;
use App\Modules\Pages\Services\PageTranslationService;
use App\Modules\Posts\Services\PostTranslationService;

class AdminController extends BaseController
{
    protected const MAX_DEPTH = 3;
    protected const ROOT_ITEM_WARNING_THRESHOLD = 6;

    private ?PageTranslationService $pageTranslations = null;
    private ?PostTranslationService $postTranslations = null;
    private ?CategoryTranslationService $categoryTranslations = null;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Menu');
        I18n::load('Categories');
    }

    public function index(): void
    {
        if (!$this->authorize('menus.view')) {
            return;
        }

        if ($this->redirectToMenusAdminOverride('index')) {
            return;
        }

        I18n::load('Posts');

        $menus = FlatFile::settings('menus');
        $pages = ContentDocumentStore::for('core/pages')->all();
        $items = $menus['main']['items'] ?? [];
        $items = $this->ensureItemIds(is_array($items) ? $items : []);
        $library = $menus['main']['library'] ?? [];
        $availableItems = $this->buildAvailableItems($pages);
        $coreUrls = array_map(
            fn($item) => (string) ($item['url'] ?? ''),
            $availableItems
        );

        $menuItems = $this->flattenItems($items);
        $catalog = $this->buildReferenceCatalog();
        $this->syncFlatItems($menuItems, $catalog);
        foreach ($menuItems as &$menuItem) {
            $url = (string) ($menuItem['url'] ?? '');
            $menuItem['source'] = in_array($url, $coreUrls, true) ? 'core' : 'custom';
        }
        unset($menuItem);

        foreach ($availableItems as &$availableItem) {
            $availableItem['source'] = 'core';
            if (!isset($availableItem['type']) || $availableItem['type'] === '') {
                $availableItem['type'] = 'pages';
            }
        }
        unset($availableItem);

        if (is_array($library) && !empty($library)) {
            $libraryItems = $this->sanitizeLibrary($library);
            foreach ($libraryItems as &$libraryItem) {
                $libraryItem['source'] = 'custom';
                $libraryItem['type'] = 'cta';
            }
            unset($libraryItem);
            $availableItems = array_merge($availableItems, $libraryItems);
        }

        $menuSourceLocale = $this->siteSourceLocale();

        $this->render('Menu/Views/admin/index', [
            'pageTitle' => __('menus', 'Menu'),
            'menus' => $menus,
            'pages' => $pages,
            'menuItems' => $menuItems,
            'availableItems' => $availableItems,
            'menuSourceLocale' => $menuSourceLocale,
            'menuTranslationLocales' => $this->buildMenuTranslationLocales($menuSourceLocale),
            'canBrowseMenuIcons' => can('media.view'),
            'canUploadMenuIcons' => can('media.upload'),
        ], 'admin.main');
    }

    public function update(): void
    {
        if (!$this->authorize('menus.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $menus = FlatFile::settings('menus');
        if (!is_array($menus)) {
            $menus = [];
        }

        $rawJson = (string) $this->request->input('menu_data', '');
        $items = [];

        if ($rawJson !== '') {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }

        if (empty($items)) {
            $legacyItems = $this->request->input('items', []);
            if (is_array($legacyItems)) {
                $items = $legacyItems;
            }
        }

        $items = $this->sanitizeItems($items);
        if (count($items) > self::ROOT_ITEM_WARNING_THRESHOLD) {
            $this->session->flash('warning', __('menu_root_items_warning', 'Menu'));
        }
        if (!isset($menus['main']) || !is_array($menus['main'])) {
            $menus['main'] = [];
        }
        $menus['main']['items'] = $items;

        if ($this->request->has('menu_library')) {
            $libraryJson = trim((string) $this->request->input('menu_library', ''));
            if ($libraryJson === '') {
                unset($menus['main']['library']);
            } else {
                $libraryDecoded = json_decode($libraryJson, true);
                if (is_array($libraryDecoded)) {
                    $libraryItems = $this->sanitizeLibrary($libraryDecoded);
                    if (!empty($libraryItems)) {
                        $menus['main']['library'] = $libraryItems;
                    } else {
                        unset($menus['main']['library']);
                    }
                } else {
                    unset($menus['main']['library']);
                }
            }
        }

        $this->syncMenuReferencesWithContent($menus);

        hook_run('menus.before_save', $menus);
        FlatFile::saveSettings($menus, 'menus');
        hook_run('menus.after_save', $menus);

        $this->session->flash('success', __('menu_saved', 'Menu'));
        $this->redirect(url('/admin/menus'));
    }

    public function icons(): void
    {
        if (!$this->authorize('menus.view')) {
            return;
        }

        $icons = IconHelper::getAllIcons();
        $this->json($icons);
    }

    protected function buildAvailableItems(array $pages): array
    {
        $items = [];
        $sourceLocale = $this->siteSourceLocale();
        $siteRouting = class_exists(\App\Modules\Settings\Services\SiteRoutingService::class)
            ? new \App\Modules\Settings\Services\SiteRoutingService()
            : null;

        $canonicalPages = $this->resolveCanonicalPagesForMenu($pages, $sourceLocale);

        foreach ($canonicalPages as $page) {
            $slug = (string) ($page['slug'] ?? $page['id'] ?? '');
            $title = (string) ($page['title'] ?? $slug);

            if ($slug === '') {
                continue;
            }

            $url = ($slug === 'home' || ($siteRouting instanceof \App\Modules\Settings\Services\SiteRoutingService && $siteRouting->isHomepagePage($page)))
                ? ''
                : '/page/' . $slug;

            $items[] = [
                'label' => $title,
                'url' => $url,
                'icon' => '',
                'type' => 'pages',
                'labelMode' => 'auto',
                'refType' => 'page',
                'ref' => (string) ($page['id'] ?? $slug),
                'autoLabel' => $title,
                'autoUrl' => $url,
                'translationFallbacks' => $this->buildPageTranslationFallbacks($page, $sourceLocale),
            ];
        }

        foreach ($this->buildSystemNavigationItems() as $systemItem) {
            $items[] = $systemItem;
        }

        usort($items, fn($a, $b) => strcasecmp($a['label'], $b['label']));

        $categories = FlatFile::for('core/categories')->all();
        $categoryItems = $this->buildCategoryItems($categories, $sourceLocale);
        if (!empty($categoryItems)) {
            $items = array_merge($items, $categoryItems);
        }

        $postItems = $this->buildPostItems();
        if (!empty($postItems)) {
            array_unshift($postItems, [
                'label' => __('blog', 'Posts'),
                'url' => '/blog',
                'icon' => '',
                'type' => 'posts',
            ]);
            $items = array_merge($items, $postItems);
        }

        $mediaItems = $this->buildMediaItems();
        if (!empty($mediaItems)) {
            $items = array_merge($items, $mediaItems);
        }

        return $items;
    }

    protected function buildSystemNavigationItems(): array
    {
        return [];
    }

    protected function buildPostItems(): array
    {
        $items = [];
        $posts = ContentDocumentStore::for('core/posts')->all();
        $sourceLocale = $this->siteSourceLocale();
        $canonicalPosts = $this->resolveCanonicalPostsForMenu($posts, $sourceLocale);

        foreach ($canonicalPosts as $post) {
            $slug = trim((string) ($post['slug'] ?? $post['id'] ?? ''));
            $title = trim((string) ($post['title'] ?? $slug));
            if ($slug === '' || $title === '') {
                continue;
            }
            $items[] = [
                'label' => $title,
                'url' => '/blog/' . $slug,
                'icon' => '',
                'type' => 'posts',
                'labelMode' => 'auto',
                'refType' => 'post',
                'ref' => (string) ($post['id'] ?? $slug),
                'autoLabel' => $title,
                'autoUrl' => '/blog/' . $slug,
                'translationFallbacks' => $this->buildPostTranslationFallbacks($post),
            ];
        }
        return $items;
    }

    /**
     * @param array<int, mixed> $pages
     * @return array<int, array<string, mixed>>
     */
    protected function resolveCanonicalPagesForMenu(array $pages, string $preferredLocale): array
    {
        $translations = $this->pageTranslations();
        $groups = [];
        $canonical = [];

        foreach ($pages as $page) {
            if (!is_array($page)) {
                continue;
            }

            $normalized = $translations->normalizePage($page);
            if ($translations->resolveEffectiveStatus($normalized) !== 'published') {
                continue;
            }

            $groupId = trim((string) ($normalized['translation_group'] ?? $normalized['id'] ?? ''));
            if ($groupId === '') {
                $canonical[] = $normalized;
                continue;
            }

            $groups[$groupId][] = $normalized;
        }

        foreach ($groups as $groupPages) {
            $page = $this->pickCanonicalSourcePageForMenu($groupPages, $preferredLocale);
            if (is_array($page)) {
                $canonical[] = $page;
            }
        }

        return $canonical;
    }

    /**
     * @param array<int, array<string, mixed>> $entities
     * @return array<string, mixed>|null
     */
    protected function pickCanonicalSourcePageForMenu(array $entities, string $preferredLocale): ?array
    {
        if ($entities === []) {
            return null;
        }

        foreach ($entities as $entity) {
            $sourceLocale = trim((string) ($entity['source_locale'] ?? ''));
            if ($sourceLocale !== '' && (string) ($entity['locale'] ?? '') === $sourceLocale) {
                return $entity;
            }
        }

        return $this->pickCanonicalTranslatedEntity($entities, $preferredLocale);
    }

    /**
     * @param array<int, mixed> $posts
     * @return array<int, array<string, mixed>>
     */
    protected function resolveCanonicalPostsForMenu(array $posts, string $preferredLocale): array
    {
        $translations = $this->postTranslations();
        $groups = [];
        $canonical = [];

        foreach ($posts as $post) {
            if (!is_array($post)) {
                continue;
            }

            $normalized = $translations->normalizePost($post);
            if ($translations->resolveEffectiveStatus($normalized) !== 'published') {
                continue;
            }

            $groupId = trim((string) ($normalized['translation_group'] ?? $normalized['id'] ?? ''));
            if ($groupId === '') {
                $canonical[] = $normalized;
                continue;
            }

            $groups[$groupId][] = $normalized;
        }

        foreach ($groups as $groupPosts) {
            $post = $this->pickCanonicalTranslatedEntity($groupPosts, $preferredLocale);
            if (is_array($post)) {
                $canonical[] = $post;
            }
        }

        return $canonical;
    }

    /**
     * @param array<int, array<string, mixed>> $entities
     * @return array<string, mixed>|null
     */
    protected function pickCanonicalTranslatedEntity(array $entities, string $preferredLocale): ?array
    {
        if ($entities === []) {
            return null;
        }

        foreach ($entities as $entity) {
            if ((string) ($entity['locale'] ?? '') === $preferredLocale) {
                return $entity;
            }
        }

        foreach ($entities as $entity) {
            $sourceLocale = trim((string) ($entity['source_locale'] ?? ''));
            if ($sourceLocale !== '' && (string) ($entity['locale'] ?? '') === $sourceLocale) {
                return $entity;
            }
        }

        return $entities[0] ?? null;
    }

    protected function siteSourceLocale(): string
    {
        $settings = FlatFile::settings();
        $defaultLocale = trim((string) ($settings['default_language'] ?? ''));

        $pageLocale = $this->pageTranslations()->normalizeLocale($defaultLocale);
        if ($pageLocale !== '') {
            return $pageLocale;
        }

        return $this->pageTranslations()->defaultLocale();
    }

    protected function pageTranslations(): PageTranslationService
    {
        if (!$this->pageTranslations instanceof PageTranslationService) {
            $this->pageTranslations = new PageTranslationService(ContentDocumentStore::for('core/pages'));
        }

        return $this->pageTranslations;
    }

    protected function postTranslations(): PostTranslationService
    {
        if (!$this->postTranslations instanceof PostTranslationService) {
            $this->postTranslations = new PostTranslationService(ContentDocumentStore::for('core/posts'));
        }

        return $this->postTranslations;
    }

    protected function categoryTranslations(): CategoryTranslationService
    {
        if (!$this->categoryTranslations instanceof CategoryTranslationService) {
            $this->categoryTranslations = new CategoryTranslationService(FlatFile::for('core/categories'));
        }

        return $this->categoryTranslations;
    }

    protected function buildCategoryItems(array $categories, string $preferredLocale): array
    {
        $items = [];
        $translations = $this->categoryTranslations();
        $canonicalCategories = $translations->buildLocalizedCategories('blog', $preferredLocale, true);
        foreach ($canonicalCategories as $category) {
            $slug = trim((string) ($category['slug'] ?? ''));
            $name = trim((string) ($category['name'] ?? $slug));
            $module = trim((string) ($category['module'] ?? 'blog'));

            if ($slug === '' || $name === '') {
                continue;
            }

            $url = match ($module) {
                'blog' => '/blog/categorie/' . $slug,
                default => '',
            };

            if ($url === '') {
                continue;
            }

            $items[] = [
                'label' => $name,
                'url' => $url,
                'icon' => '',
                'type' => 'categories',
                'labelMode' => 'auto',
                'refType' => 'category',
                'ref' => (string) ($category['source_id'] ?? $category['id'] ?? $slug),
                'autoLabel' => $name,
                'autoUrl' => $url,
                'translationFallbacks' => $this->buildCategoryTranslationFallbacks($category),
            ];
        }

        return $items;
    }

    protected function buildMediaItems(): array
    {
        $items = [];
        $mediaFiles = FlatFile::for('core/media')->all();
        if (!is_array($mediaFiles) || empty($mediaFiles)) {
            return $items;
        }

        foreach ($mediaFiles as $file) {
            if (!is_array($file)) {
                continue;
            }

            $rawPath = trim((string) ($file['path'] ?? ''));
            if ($rawPath === '') {
                continue;
            }

            $folder = strtolower(trim((string) ($file['folder'] ?? '')));
            $mediaType = $this->normalizeMediaType($folder);
            if ($mediaType === '') {
                continue;
            }

            $url = $this->normalizeMediaUrl((string) ($file['url'] ?? ''), $rawPath);
            if ($url === '') {
                continue;
            }

            $mime = trim((string) ($file['mime'] ?? ''));
            $extension = strtolower(trim((string) ($file['extension'] ?? pathinfo($rawPath, PATHINFO_EXTENSION))));
            $iconData = IconHelper::getFileIcon($mime, $extension !== '' ? $extension : null);
            $icon = is_array($iconData) ? (string) ($iconData['class'] ?? '') : '';
            if ($icon === '') {
                $icon = 'fa-solid fa-file';
            }

            $name = trim((string) ($file['original_name'] ?? $file['name'] ?? basename($rawPath)));
            if ($name === '') {
                $name = basename($rawPath);
            }

            $id = trim((string) ($file['id'] ?? ''));

            $items[] = [
                'label' => $name,
                'url' => $url,
                'icon' => $icon,
                'type' => 'media',
                'mediaType' => $mediaType,
                'source' => 'core',
                'refType' => $id !== '' ? 'media' : '',
                'ref' => $id,
            ];
        }

        usort($items, static fn(array $a, array $b): int => strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? '')));

        return $items;
    }

    protected function normalizeMediaType(string $folder): string
    {
        return match ($folder) {
            'videos' => 'videos',
            'sounds' => 'music',
            'documents' => 'documents',
            'pdf' => 'pdf',
            'spreadsheets' => 'spreadsheets',
            'archives' => 'archives',
            'images' => 'images',
            default => '',
        };
    }

    protected function normalizeMediaUrl(string $url, string $path): string
    {
        $candidate = trim($url);
        if ($candidate === '') {
            return '/uploads/' . ltrim($path, '/');
        }

        if (preg_match('#^https?://#i', $candidate) === 1) {
            $parsedPath = (string) parse_url($candidate, PHP_URL_PATH);
            if ($parsedPath !== '') {
                $candidate = $parsedPath;
            }
        }

        $candidate = preg_replace('#^/public/uploads/#', '/uploads/', $candidate) ?? $candidate;
        if (!str_starts_with($candidate, '/')) {
            $candidate = '/' . ltrim($candidate, '/');
        }

        return $candidate;
    }

    protected function flattenItems(array $items, int $depth = 0): array
    {
        $flat = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $entry = $item;
            $entry['depth'] = $depth;
            $children = $item['children'] ?? [];
            unset($entry['children']);

            $flat[] = $entry;

            if (is_array($children) && $children !== [] && $depth < self::MAX_DEPTH) {
                $flat = array_merge($flat, $this->flattenItems($children, $depth + 1));
            }
        }

        return $flat;
    }

    protected function sanitizeItems(array $items, int $depth = 0): array
    {
        $sanitized = [];
        $sourceLocale = $this->siteSourceLocale();

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            $icon = trim((string) ($item['icon'] ?? ''));
            $target = trim((string) ($item['target'] ?? ''));
            $id = trim((string) ($item['id'] ?? ''));
            $labelMode = trim((string) ($item['labelMode'] ?? ''));
            $refType = trim((string) ($item['refType'] ?? ''));
            $ref = trim((string) ($item['ref'] ?? ''));
            $iconMedia = $this->sanitizeMenuIconMediaPath((string) ($item['iconMedia'] ?? ''));
            $iconType = $this->sanitizeIconType((string) ($item['iconType'] ?? ''), $iconMedia);
            $translations = $this->sanitizeMenuTranslations($item['translations'] ?? [], $label, $sourceLocale);

            if ($label === '') {
                continue;
            }

            if ($iconType === 'media') {
                $icon = '';
            } elseif ($icon !== '' && !IconHelper::iconExists($icon)) {
                $icon = '';
            }

            if (!in_array($target, ['_self', '_blank'], true)) {
                $target = '';
            }

            if ($id === '') {
                $id = $this->generateItemId();
            }

            $entry = [
                'id' => $id,
                'label' => $label,
                'url' => $url,
            ];
            if (in_array($labelMode, ['auto', 'custom'], true)) {
                $entry['labelMode'] = $labelMode;
            }
            if ($refType !== '' && $ref !== '') {
                $entry['refType'] = $refType;
                $entry['ref'] = $ref;
            }

            if ($icon !== '') {
                $entry['icon'] = $icon;
            }
            if ($iconType === 'media' && $iconMedia !== '') {
                $entry['iconType'] = 'media';
                $entry['iconMedia'] = $iconMedia;
            }

            if ($target !== '') {
                $entry['target'] = $target;
            }
            if ($translations !== []) {
                $entry['translations'] = $translations;
            }

            if ($depth < self::MAX_DEPTH) {
                $children = $item['children'] ?? [];
                if (is_array($children)) {
                    $children = $this->sanitizeItems($children, $depth + 1);
                    if ($children !== []) {
                        $entry['children'] = $children;
                    }
                }
            }

            $sanitized[] = $entry;
        }

        return $sanitized;
    }

    protected function sanitizeLibrary(array $items): array
    {
        $sanitized = [];
        $sourceLocale = $this->siteSourceLocale();

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            $icon = trim((string) ($item['icon'] ?? ''));
            $target = trim((string) ($item['target'] ?? ''));
            $displayType = trim((string) ($item['displayType'] ?? ''));
            $buttonStyle = trim((string) ($item['buttonStyle'] ?? ''));
            $labelMode = trim((string) ($item['labelMode'] ?? ''));
            $refType = trim((string) ($item['refType'] ?? ''));
            $ref = trim((string) ($item['ref'] ?? ''));
            $iconMedia = $this->sanitizeMenuIconMediaPath((string) ($item['iconMedia'] ?? ''));
            $iconType = $this->sanitizeIconType((string) ($item['iconType'] ?? ''), $iconMedia);
            $translations = $this->sanitizeMenuTranslations($item['translations'] ?? [], $label, $sourceLocale);

            if ($label === '') {
                continue;
            }

            if ($iconType === 'media') {
                $icon = '';
            } elseif ($icon !== '' && !IconHelper::iconExists($icon)) {
                $icon = '';
            }

            if (!in_array($target, ['_self', '_blank'], true)) {
                $target = '';
            }
            $displayType = $this->sanitizeCtaDisplayType($displayType);
            $buttonStyle = $this->sanitizeCtaButtonStyle($buttonStyle, $displayType);

            $entry = [
                'label' => $label,
                'url' => $url,
            ];
            if (in_array($labelMode, ['auto', 'custom'], true)) {
                $entry['labelMode'] = $labelMode;
            }
            if ($refType !== '' && $ref !== '') {
                $entry['refType'] = $refType;
                $entry['ref'] = $ref;
            }

            if ($icon !== '') {
                $entry['icon'] = $icon;
            }
            if ($iconType === 'media' && $iconMedia !== '') {
                $entry['iconType'] = 'media';
                $entry['iconMedia'] = $iconMedia;
            }

            if ($target !== '') {
                $entry['target'] = $target;
            }
            if ($displayType !== '') {
                $entry['displayType'] = $displayType;
            }
            if ($buttonStyle !== '') {
                $entry['buttonStyle'] = $buttonStyle;
            }
            if ($translations !== []) {
                $entry['translations'] = $translations;
            }

            $sanitized[] = $entry;
        }

        return $sanitized;
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    protected function buildMenuTranslationLocales(string $sourceLocale): array
    {
        $locales = [];
        $uiLocale = I18n::getLocale();

        foreach ($this->menuTranslationLocaleCodes() as $localeCode) {
            if ($localeCode === $sourceLocale) {
                continue;
            }

            $label = I18n::getLocalizedLanguageName($localeCode, $uiLocale);
            if ($label === '') {
                $label = $localeCode;
            }

            $locales[] = [
                'code' => $localeCode,
                'label' => $label,
            ];
        }

        return $locales;
    }

    /**
     * @return array<int, string>
     */
    protected function menuTranslationLocaleCodes(): array
    {
        $localeMap = [];
        $coreLocalesDir = BASE_PATH . '/app/Modules/Core/Languages';

        if (is_dir($coreLocalesDir)) {
            foreach (glob($coreLocalesDir . '/*.json') ?: [] as $file) {
                $code = basename((string) $file, '.json');
                if ($code !== '') {
                    $localeMap[$code] = $code;
                }
            }
        }

        if ($localeMap === []) {
            foreach ((array) config('app.locales', ['fr-FR', 'en-US']) as $localeCode) {
                $localeCode = trim((string) $localeCode);
                if ($localeCode !== '') {
                    $localeMap[$localeCode] = $localeCode;
                }
            }
        }

        ksort($localeMap);

        return array_values($localeMap);
    }

    protected function sanitizeIconType(string $iconType, string $iconMedia): string
    {
        $iconType = strtolower(trim($iconType));

        if ($iconMedia === '') {
            return '';
        }

        return $iconType === 'media' ? 'media' : '';
    }

    protected function sanitizeMenuIconMediaPath(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            $parsedPath = (string) parse_url($value, PHP_URL_PATH);
            if ($parsedPath !== '') {
                $value = $parsedPath;
            }
        }

        $normalized = function_exists('flatcms_normalize_upload_media_path')
            ? flatcms_normalize_upload_media_path($value)
            : $value;
        $normalized = trim((string) $normalized);

        if ($normalized === '') {
            return '';
        }

        if (preg_match('#^/uploads/images/.+\\.(png|gif|webp|avif)$#i', $normalized) !== 1) {
            return '';
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    protected function sanitizeMenuTranslations(mixed $translations, string $sourceLabel, string $sourceLocale): array
    {
        if (is_string($translations)) {
            $decoded = json_decode($translations, true);
            if (is_array($decoded)) {
                $translations = $decoded;
            }
        }

        if (!is_array($translations)) {
            return [];
        }

        $allowedLocales = array_flip($this->menuTranslationLocaleCodes());
        $sourceLabel = trim($sourceLabel);
        $sanitized = [];

        foreach ($translations as $localeCode => $translatedLabel) {
            $localeCode = trim((string) $localeCode);
            if ($localeCode === '' || $localeCode === $sourceLocale || !isset($allowedLocales[$localeCode])) {
                continue;
            }

            if (!is_string($translatedLabel)) {
                continue;
            }

            $translatedLabel = trim($translatedLabel);
            if ($translatedLabel === '' || $translatedLabel === $sourceLabel) {
                continue;
            }

            $sanitized[$localeCode] = $translatedLabel;
        }

        ksort($sanitized);

        return $sanitized;
    }

    protected function syncMenuReferencesWithContent(array &$menus): void
    {
        $catalog = $this->buildReferenceCatalog();

        if (isset($menus['main']['items']) && is_array($menus['main']['items'])) {
            $this->syncItemsRecursive($menus['main']['items'], $catalog);
        }

        if (isset($menus['main']['library']) && is_array($menus['main']['library'])) {
            $this->syncFlatItems($menus['main']['library'], $catalog);
        }

    }

    protected function buildReferenceCatalog(): array
    {
        $byRef = [];
        $byUrl = [];
        $siteRouting = class_exists(\App\Modules\Settings\Services\SiteRoutingService::class)
            ? new \App\Modules\Settings\Services\SiteRoutingService()
            : null;

        $pages = ContentDocumentStore::for('core/pages')->all();
        $pageTranslations = $this->pageTranslations();
        $canonicalPages = $this->resolveCanonicalPagesForMenu($pages, $this->siteSourceLocale());

        foreach ($canonicalPages as $page) {
            if (!is_array($page)) {
                continue;
            }
            $id = trim((string) ($page['id'] ?? ''));
            $slug = trim((string) ($page['slug'] ?? ''));
            $title = trim((string) ($page['title'] ?? $slug));
            if ($id === '' || $slug === '' || $title === '') {
                continue;
            }
            $url = ($slug === 'home' || ($siteRouting instanceof \App\Modules\Settings\Services\SiteRoutingService && $siteRouting->isHomepagePage($page)))
                ? ''
                : '/page/' . $slug;
            $entry = [
                'refType' => 'page',
                'ref' => $id,
                'url' => $url,
                'label' => $title,
                'slug' => $slug,
                'translationFallbacks' => $this->buildPageTranslationFallbacks($page, $this->siteSourceLocale()),
            ];
            $byRef['page:' . $id] = $entry;
            $byRef['page:' . $slug] = $entry;
            $byUrl[$this->normalizeInternalUrl($url)] = $entry;

            $translationGroup = trim((string) ($page['translation_group'] ?? $id));
            if ($translationGroup === '') {
                continue;
            }

            $translations = $pageTranslations->getTranslations($translationGroup, false);
            foreach ($translations as $translation) {
                if (!is_array($translation)) {
                    continue;
                }
                $translationId = trim((string) ($translation['id'] ?? ''));
                $translationSlug = trim((string) ($translation['slug'] ?? ''));
                if ($translationId !== '') {
                    $byRef['page:' . $translationId] = $entry;
                }
                if ($translationSlug !== '') {
                    $byRef['page:' . $translationSlug] = $entry;
                    $byUrl[$this->normalizeInternalUrl('/page/' . $translationSlug)] = $entry;
                }
            }
        }

        $posts = ContentDocumentStore::for('core/posts')->all();
        foreach ($posts as $post) {
            if (!is_array($post)) {
                continue;
            }
            if (($post['status'] ?? 'draft') !== 'published') {
                continue;
            }
            $id = trim((string) ($post['id'] ?? ''));
            $slug = trim((string) ($post['slug'] ?? ''));
            $title = trim((string) ($post['title'] ?? $slug));
            if ($id === '' || $slug === '' || $title === '') {
                continue;
            }
            $url = '/blog/' . $slug;
            $entry = [
                'refType' => 'post',
                'ref' => $id,
                'url' => $url,
                'label' => $title,
                'slug' => $slug,
                'translationFallbacks' => $this->buildPostTranslationFallbacks($post),
            ];
            $byRef['post:' . $id] = $entry;
            $byRef['post:' . $slug] = $entry;
            $byUrl[$this->normalizeInternalUrl($url)] = $entry;
        }

        $categoryTranslations = $this->categoryTranslations();
        foreach (['blog', 'shop', 'downloads'] as $module) {
            $canonicalCategories = $categoryTranslations->buildLocalizedCategories($module, $this->siteSourceLocale(), true);
            foreach ($canonicalCategories as $category) {
                $id = trim((string) ($category['source_id'] ?? $category['id'] ?? ''));
                $slug = trim((string) ($category['slug'] ?? ''));
                $name = trim((string) ($category['name'] ?? $slug));
                if ($id === '' || $slug === '' || $name === '') {
                    continue;
                }

                $url = match ($module) {
                    'blog' => '/blog/categorie/' . $slug,
                    'shop' => '/shop/categorie/' . $slug,
                    'downloads' => '/downloads/categorie/' . $slug,
                    default => '',
                };
                if ($url === '') {
                    continue;
                }

                $label = $name;
                $entry = [
                    'refType' => 'category',
                    'ref' => $id,
                    'url' => $url,
                    'label' => $label,
                    'slug' => $slug,
                    'translationFallbacks' => $this->buildCategoryTranslationFallbacks($category),
                ];
                $byRef['category:' . $id] = $entry;
                $byRef['category:' . $slug] = $entry;

                $translations = $categoryTranslations->getTranslations((string) ($category['translation_group'] ?? ''), false);
                foreach ($translations as $translation) {
                    $translationId = trim((string) ($translation['id'] ?? ''));
                    $translationSlug = trim((string) ($translation['slug'] ?? ''));
                    if ($translationId !== '') {
                        $byRef['category:' . $translationId] = $entry;
                    }
                    if ($translationSlug !== '') {
                        $byRef['category:' . $translationSlug] = $entry;
                    }
                }

                $byUrl[$this->normalizeInternalUrl($url)] = $entry;
            }
        }

        return [
            'byRef' => $byRef,
            'byUrl' => $byUrl,
        ];
    }

    protected function syncItemsRecursive(array &$items, array $catalog): void
    {
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->syncSingleItemReference($item, $catalog);

            if (isset($item['children']) && is_array($item['children'])) {
                $children = $item['children'];
                $this->syncItemsRecursive($children, $catalog);
                $item['children'] = $children;
            }

            $items[$index] = $item;
        }
    }

    protected function syncFlatItems(array &$items, array $catalog): void
    {
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $this->syncSingleItemReference($item, $catalog);
            $items[$index] = $item;
        }
    }

    protected function syncSingleItemReference(array &$item, array $catalog): void
    {
        $refType = trim((string) ($item['refType'] ?? ''));
        $ref = trim((string) ($item['ref'] ?? ''));
        $source = strtolower(trim((string) ($item['source'] ?? '')));
        $urlKey = $this->normalizeInternalUrl((string) ($item['url'] ?? ''));

        $resolved = null;
        if ($refType !== '' && $ref !== '') {
            $key = $refType . ':' . $ref;
            if (isset($catalog['byRef'][$key]) && is_array($catalog['byRef'][$key])) {
                $resolved = $catalog['byRef'][$key];
            }
        }
        if ($resolved === null && $refType === '' && $ref === '' && $source === 'custom') {
            return;
        }
        if ($resolved === null && $urlKey !== '' && isset($catalog['byUrl'][$urlKey]) && is_array($catalog['byUrl'][$urlKey])) {
            $resolved = $catalog['byUrl'][$urlKey];
        }

        if ($resolved === null) {
            return;
        }

        $translationFallbacks = is_array($resolved['translationFallbacks'] ?? null)
            ? $resolved['translationFallbacks']
            : [];
        if ($translationFallbacks !== []) {
            $item['translationFallbacks'] = $translationFallbacks;
        } else {
            unset($item['translationFallbacks']);
        }

        if (($item['refType'] ?? '') !== $resolved['refType']) {
            $item['refType'] = $resolved['refType'];
        }
        if (($item['ref'] ?? '') !== $resolved['ref']) {
            $item['ref'] = $resolved['ref'];
        }
        if (($item['url'] ?? '') !== $resolved['url']) {
            $item['url'] = $resolved['url'];
        }

        $resolvedLabel = trim((string) ($resolved['label'] ?? ''));
        $currentLabel = trim((string) ($item['label'] ?? ''));
        $labelMode = strtolower(trim((string) ($item['labelMode'] ?? '')));
        $normalizedCurrentLabel = preg_replace('/^[^·]+·\s*/u', '', $currentLabel) ?? $currentLabel;
        $normalizedCurrentLabel = preg_replace('/^[^-]+-\s*/u', '', (string) $normalizedCurrentLabel) ?? $normalizedCurrentLabel;
        if (!in_array($labelMode, ['auto', 'custom'], true) && $resolvedLabel !== '') {
            $item['labelMode'] = ($currentLabel !== '' && $currentLabel !== $resolvedLabel) ? 'custom' : 'auto';
            $labelMode = strtolower(trim((string) ($item['labelMode'] ?? '')));
        }

        if (
            in_array((string) ($resolved['refType'] ?? ''), ['page', 'post', 'category'], true)
            && $labelMode === 'custom'
            && $resolvedLabel !== ''
            && ($currentLabel === $resolvedLabel || trim((string) $normalizedCurrentLabel) === $resolvedLabel)
        ) {
            $item['labelMode'] = 'auto';
            $labelMode = 'auto';
        }

        if ($resolvedLabel !== '') {
            $item['autoLabel'] = $resolvedLabel;
        }
        $item['autoUrl'] = $resolved['url'];

        if (in_array((string) ($resolved['refType'] ?? ''), ['page', 'post', 'category'], true) && $labelMode === 'auto' && $resolvedLabel !== '' && $currentLabel !== $resolvedLabel) {
            $item['label'] = $resolvedLabel;
        }
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, string>
     */
    protected function buildPageTranslationFallbacks(array $page, string $sourceLocale): array
    {
        $translationGroup = trim((string) ($page['translation_group'] ?? $page['id'] ?? ''));
        if ($translationGroup === '') {
            return [];
        }

        $translations = $this->pageTranslations()->getTranslations($translationGroup, false);

        return $this->extractTranslationLabelMap($translations, $sourceLocale, 'title');
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, string>
     */
    protected function buildPostTranslationFallbacks(array $post): array
    {
        $translationGroup = trim((string) ($post['translation_group'] ?? $post['id'] ?? ''));
        if ($translationGroup === '') {
            return [];
        }

        $sourceLocale = trim((string) ($post['source_locale'] ?? $post['locale'] ?? $this->siteSourceLocale()));
        $translations = $this->postTranslations()->getTranslations($translationGroup, false);

        return $this->extractTranslationLabelMap($translations, $sourceLocale, 'title');
    }

    /**
     * @param array<string, mixed> $category
     * @return array<string, string>
     */
    protected function buildCategoryTranslationFallbacks(array $category): array
    {
        $translationGroup = trim((string) ($category['translation_group'] ?? $category['source_id'] ?? $category['id'] ?? ''));
        if ($translationGroup === '') {
            return [];
        }

        $sourceLocale = trim((string) ($category['source_locale'] ?? $category['locale'] ?? $this->siteSourceLocale()));
        $translations = $this->categoryTranslations()->getTranslations($translationGroup, false);

        return $this->extractTranslationLabelMap($translations, $sourceLocale, 'name');
    }

    /**
     * @param array<int, mixed> $translations
     * @return array<string, string>
     */
    protected function extractTranslationLabelMap(array $translations, string $sourceLocale, string $labelKey): array
    {
        $labels = [];
        $allowedLocales = array_flip($this->menuTranslationLocaleCodes());

        foreach ($translations as $translation) {
            if (!is_array($translation)) {
                continue;
            }

            $localeCode = trim((string) ($translation['locale'] ?? ''));
            $label = trim((string) ($translation[$labelKey] ?? ''));
            if ($localeCode === '' || $localeCode === $sourceLocale || $label === '' || !isset($allowedLocales[$localeCode])) {
                continue;
            }

            $labels[$localeCode] = $label;
        }

        ksort($labels);

        return $labels;
    }

    protected function normalizeInternalUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $url === '/') {
            return '';
        }

        $path = $url;
        $parsed = parse_url($url);
        if (is_array($parsed) && isset($parsed['path'])) {
            $path = (string) $parsed['path'];
        }

        $path = (string) preg_replace('~[?#].*$~', '', $path);
        if ($path === '' || $path === '/') {
            return '';
        }

        $path = rtrim($path, '/');
        $path = (string) preg_replace('~^/([a-z]{2}(?:-[A-Za-z]{2})?)(?=/|$)~', '', $path);
        if ($path === '' || $path === '/') {
            return '';
        }

        return $path;
    }

    protected function ensureItemIds(array $items): array
    {
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '') {
                $item['id'] = $this->generateItemId();
            }
            if (isset($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->ensureItemIds($item['children']);
            }
            $items[$index] = $item;
        }

        return $items;
    }

    protected function generateItemId(): string
    {
        try {
            return 'menu_' . bin2hex(random_bytes(6));
        } catch (\Throwable $e) {
            return 'menu_' . uniqid();
        }
    }

    private function sanitizeInternalReturnPath(string $value): string
    {
        $path = trim($value);
        if ($path === '') {
            return '';
        }

        if (preg_match('~^(?:[a-z][a-z0-9+.-]*:)?//~i', $path) === 1) {
            return '';
        }

        if (!str_starts_with($path, '/')) {
            return '';
        }

        if (preg_match('/[\r\n]/', $path) === 1) {
            return '';
        }

        return $path;
    }

    private function redirectToMenusAdminOverride(string $action): bool
    {
        $results = hook_run('menus.admin.route_override', [
            'action' => $action,
            'request_uri' => $this->request->uri(),
        ]);

        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            $redirectUrl = $this->sanitizeInternalReturnPath((string) ($result['redirect_url'] ?? ''));
            if ($redirectUrl === '') {
                continue;
            }

            $this->redirect(url($redirectUrl));
            return true;
        }

        return false;
    }
}
