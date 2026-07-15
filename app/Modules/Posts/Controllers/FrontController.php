<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Posts\Controllers;

use App\Modules\Categories\Services\CategoryTranslationService;
use App\Core\BaseController;
use App\Core\I18n;
use App\Core\FlatFile;
use App\Core\ModuleManager;
use App\Modules\Posts\Services\PostTranslationService;
use App\Modules\Settings\Services\SiteBrandingTranslationService;

class FrontController extends BaseController
{
    private FlatFile $posts;
    private FlatFile $categories;
    private CategoryTranslationService $categoryTranslations;
    private PostTranslationService $translations;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Posts');
        I18n::load('Comments');
        $this->posts = FlatFile::for('core/posts');
        $this->categories = FlatFile::for('core/categories');
        $this->categoryTranslations = new CategoryTranslationService($this->categories);
        $this->translations = new PostTranslationService($this->posts);
    }

    public function index(?string $categorySlug = null): void
    {
        $page = max(1, (int) $this->request->input('page', 1));
        $categoriesEnabled = $this->isCategoriesEnabled();
        $filterSlug = $categorySlug !== null
            ? trim($categorySlug)
            : trim((string) $this->request->input('category', ''));
        if (!$categoriesEnabled) {
            $filterSlug = '';
        }
        $categories = $categoriesEnabled ? $this->getActiveCategories() : [];
        $categoriesById = $this->mapCategoriesById($categories);
        $currentCategory = $this->findCategoryBySlug($categories, $filterSlug);
        
        // Get only published posts
        $currentLocale = $this->request->locale();
        $allPosts = array_filter(
            $this->translations->all(),
            fn (array $post): bool => $this->translations->resolveEffectiveStatus($post) === 'published'
                && (string) ($post['locale'] ?? '') === $currentLocale
        );
        if ($currentCategory) {
            $currentId = (string) ($currentCategory['id'] ?? '');
            $allPosts = array_filter($allPosts, function ($post) use ($currentId) {
                $postCategories = $post['categories'] ?? [];
                if (!is_array($postCategories)) {
                    $postCategories = [$postCategories];
                }
                $postCategories = array_map('strval', $postCategories);
                return in_array($currentId, $postCategories, true);
            });
        } elseif ($filterSlug !== '') {
            $allPosts = [];
        }
        usort($allPosts, fn($a, $b) => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));
        
        $settings = FlatFile::settings();
        $perPage = max(1, (int) ($settings['posts_per_page'] ?? 10));
        $total = count($allPosts);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $posts = array_slice($allPosts, ($page - 1) * $perPage, $perPage);

        $this->renderFrontend('posts/index', [
            'pageTitle' => __('blog', 'Posts'),
            'posts' => [
                'data' => $posts,
                'total' => $total,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
            ],
            'categories' => $categories,
            'categoriesById' => $categoriesById,
            'currentCategory' => $currentCategory,
            'currentCategorySlug' => $currentCategory['slug'] ?? $filterSlug,
            'categoriesEnabled' => $categoriesEnabled,
        ]);
    }

    public function category(string $slug): void
    {
        if (!$this->isCategoriesEnabled()) {
            $this->redirect(url('/' . I18n::getLocale() . '/blog'));
            return;
        }

        $currentLocale = $this->request->locale();
        $currentCategory = $this->categoryTranslations->findBySlugAndLocale($slug, $currentLocale, true);
        if (!is_array($currentCategory)) {
            $anyLocaleCategory = $this->categoryTranslations->findBySlug($slug, false);
            if (is_array($anyLocaleCategory)) {
                if ($this->categoryTranslations->resolveEffectiveStatus($anyLocaleCategory) !== 'active') {
                    $this->redirect(url('/' . $currentLocale . '/blog'));
                    return;
                }

                $translationGroup = (string) ($anyLocaleCategory['translation_group'] ?? '');
                $localizedCategory = $translationGroup !== ''
                    ? $this->categoryTranslations->findByTranslationGroupAndLocale($translationGroup, $currentLocale, true)
                    : null;
                if (!is_array($localizedCategory) && $translationGroup !== '') {
                    $localizedCategory = $this->categoryTranslations->resolveSourceCategory($translationGroup);
                }
                if (is_array($localizedCategory)) {
                    $localizedSlug = trim((string) ($localizedCategory['slug'] ?? ''));
                    $localizedLocale = trim((string) ($localizedCategory['locale'] ?? $currentLocale));
                    if ($localizedSlug !== '') {
                        $this->redirect(url('/' . $localizedLocale . '/blog/categorie/' . rawurlencode($localizedSlug)), 302);
                        return;
                    }
                }
            }
        }

        $this->index($slug);
    }

    public function show(string $slug): void
    {
        $currentLocale = $this->request->locale();
        $post = $this->translations->findBySlugAndLocale($slug, $currentLocale, true);

        if (!$post) {
            $anyLocalePost = $this->translations->findBySlug($slug, true);
            if (is_array($anyLocalePost)) {
                $localizedPost = $this->translations->findByTranslationGroupAndLocale(
                    (string) ($anyLocalePost['translation_group'] ?? ''),
                    $currentLocale,
                    true
                );

                if (is_array($localizedPost)) {
                    $this->redirect(url('/' . $currentLocale . '/blog/' . rawurlencode((string) ($localizedPost['slug'] ?? ''))), 301);
                    return;
                }

                $sourcePost = $this->translations->resolveSourcePost((string) ($anyLocalePost['translation_group'] ?? ''));
                if (is_array($sourcePost) && trim((string) ($sourcePost['slug'] ?? '')) !== '') {
                    $post = $sourcePost;
                }
            }
        }

        if (!$post || $this->translations->resolveEffectiveStatus($post) !== 'published') {
            http_response_code(404);
            $this->render('errors/404', ['pageTitle' => '404']);
            return;
        }

        $post['content'] = flatcms_render_shortcodes((string) ($post['content'] ?? ''), [
            'source_url' => url($this->request->uri()),
            'locale' => $this->request->locale(),
        ]);
        $post['content'] = $this->normalizeContentMediaUrls((string) $post['content']);

        // Get comments for this post (if Comments module enabled)
        $comments = [];
        if ($this->isCommentsEnabled()) {
            $comments = $this->getCommentsForPost($post['id']);
        }
        $categoriesEnabled = $this->isCategoriesEnabled();
        $categories = $categoriesEnabled ? $this->getActiveCategories() : [];
        $categoriesById = $this->mapCategoriesById($categories);
        $postCategories = $this->resolvePostCategories($post, $categoriesById);
        $relatedPosts = $this->findRelatedPosts($post);
        $pagination = $this->buildPostPagination($post);
        $frontendNotice = $this->buildTranslationFallbackNotice($post, $currentLocale);

        hook_run('posts.before_render', $post);
        $this->renderFrontend('posts/show', [
            'post' => $post,
            'comments' => $comments,
            'pageTitle' => $post['meta_title'] ?? $post['title'],
            'postCategories' => $postCategories,
            'relatedPosts' => $relatedPosts,
            'pagination' => $pagination,
            'categoriesEnabled' => $categoriesEnabled,
            'commentsEnabled' => $this->isCommentsEnabled(),
            'frontendNotice' => $frontendNotice,
        ]);
        hook_run('posts.after_render', $post);
    }

    private function isCommentsEnabled(): bool
    {
        $manager = ModuleManager::instance();
        $enabled = array_flip($manager->enabledNames());
        return isset($enabled['Comments']);
    }

    private function getCommentsForPost(string $postId): array
    {
        if (!$this->isCommentsEnabled()) {
            return [];
        }

        if (!class_exists(\App\Modules\Comments\Controllers\FrontController::class)) {
            return [];
        }

        return \App\Modules\Comments\Controllers\FrontController::getComments($postId, 'post');
    }

    private function isCategoriesEnabled(): bool
    {
        $manager = ModuleManager::instance();
        $enabled = array_flip($manager->enabledNames());
        return isset($enabled['Categories']);
    }

    protected function renderFrontend(string $template, array $data = []): void
    {
        $settings = (new SiteBrandingTranslationService())->resolveForLocale(
            FlatFile::settings(),
            (string) $this->request->locale()
        );
        
        $data['settings'] = $settings;
        $data['locale'] = $this->request->locale();
        $data = array_merge(
            $data,
            $this->getMenuPayload($settings),
            footer_render_payload($settings)
        );
        
        $this->view->render("frontend/{$template}", $data, 'frontend.main');
    }

    protected function getMenuPayload(array $settings): array
    {
        $menus = FlatFile::settings('menus');
        return [
            'menuStandard' => $menus['main']['items'] ?? [],
        ];
    }

    /**
     * @return array<string, string>|null
     */
    protected function buildTranslationFallbackNotice(array $post, string $requestedLocale): ?array
    {
        $normalizedRequestedLocale = $this->translations->normalizeLocale($requestedLocale);
        $postLocale = $this->translations->normalizeLocale((string) ($post['locale'] ?? ''));

        if ($normalizedRequestedLocale === '' || $postLocale === '' || $normalizedRequestedLocale === $postLocale) {
            return null;
        }

        return [
            'type' => 'warning',
            'message' => __('frontend_translation_fallback_notice', 'Posts', [
                'requested_locale' => $this->translations->getLocaleLabel($normalizedRequestedLocale, $normalizedRequestedLocale),
                'source_locale' => $this->translations->getLocaleLabel($postLocale, $normalizedRequestedLocale),
            ]),
        ];
    }

    private function getActiveCategories(): array
    {
        return $this->categoryTranslations->buildLocalizedCategories('blog', (string) $this->request->locale(), true);
    }

    private function mapCategoriesById(array $categories): array
    {
        $map = [];
        foreach ($categories as $cat) {
            if (!empty($cat['id'])) {
                $canonicalId = (string) $cat['id'];
                $map[$canonicalId] = $cat;

                $translationId = trim((string) ($cat['translation_id'] ?? ''));
                if ($translationId !== '') {
                    $map[$translationId] = $cat;
                }
            }
        }
        return $map;
    }

    private function findCategoryBySlug(array $categories, string $slug): ?array
    {
        if ($slug === '') {
            return null;
        }
        foreach ($categories as $cat) {
            if (($cat['slug'] ?? '') === $slug) {
                return $cat;
            }
        }
        return null;
    }

    private function resolvePostCategories(array $post, array $categoriesById): array
    {
        $postCategories = $post['categories'] ?? [];
        if (!is_array($postCategories)) {
            $postCategories = [$postCategories];
        }
        $postCategories = array_map('strval', $postCategories);

        $resolved = [];
        foreach ($postCategories as $catId) {
            if (isset($categoriesById[$catId])) {
                $resolved[] = $categoriesById[$catId];
            }
        }
        return $resolved;
    }

    private function findRelatedPosts(array $post, int $limit = 3): array
    {
        $currentId = (string) ($post['id'] ?? '');
        $currentSlug = (string) ($post['slug'] ?? '');
        $currentLocale = (string) ($post['locale'] ?? $this->request->locale());
        $currentCategories = $post['categories'] ?? [];
        if (!is_array($currentCategories)) {
            $currentCategories = [$currentCategories];
        }
        $currentCategories = array_values(array_filter(array_map('strval', $currentCategories)));

        $translations = $this->translations;
        $candidates = array_filter(
            $this->translations->all(),
            static function (array $candidate) use ($currentId, $currentSlug, $currentLocale, $translations): bool {
                return (string) ($candidate['id'] ?? '') !== $currentId
                    && (string) ($candidate['slug'] ?? '') !== ''
                    && (string) ($candidate['slug'] ?? '') !== $currentSlug
                    && $translations->resolveEffectiveStatus($candidate) === 'published'
                    && (string) ($candidate['locale'] ?? '') === $currentLocale;
            }
        );

        usort($candidates, static function (array $a, array $b) use ($currentCategories): int {
            $categoriesA = $a['categories'] ?? [];
            if (!is_array($categoriesA)) {
                $categoriesA = [$categoriesA];
            }
            $categoriesA = array_values(array_filter(array_map('strval', $categoriesA)));

            $categoriesB = $b['categories'] ?? [];
            if (!is_array($categoriesB)) {
                $categoriesB = [$categoriesB];
            }
            $categoriesB = array_values(array_filter(array_map('strval', $categoriesB)));

            $scoreA = count(array_intersect($currentCategories, $categoriesA));
            $scoreB = count(array_intersect($currentCategories, $categoriesB));

            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }

            return (string) ($b['created_at'] ?? '') <=> (string) ($a['created_at'] ?? '');
        });

        return array_slice(array_values($candidates), 0, max(1, $limit));
    }

    private function buildPostPagination(array $post): array
    {
        $currentLocale = (string) ($post['locale'] ?? $this->request->locale());
        $publishedPosts = array_values(array_filter(
            $this->translations->all(),
            fn (array $candidate): bool => $this->translations->resolveEffectiveStatus($candidate) === 'published'
                && (string) ($candidate['locale'] ?? '') === $currentLocale
                && trim((string) ($candidate['slug'] ?? '')) !== ''
        ));

        usort($publishedPosts, static fn (array $a, array $b): int => (string) ($b['created_at'] ?? '') <=> (string) ($a['created_at'] ?? ''));

        $total = count($publishedPosts);
        if ($total <= 1) {
            return [
                'total' => $total,
                'current_index' => 0,
                'items' => [],
                'previous' => null,
                'next' => null,
            ];
        }

        $indexBySlug = [];
        foreach ($publishedPosts as $index => $candidate) {
            $slug = (string) ($candidate['slug'] ?? '');
            if ($slug !== '') {
                $indexBySlug[$slug] = $index;
            }
        }

        $currentSlug = (string) ($post['slug'] ?? '');
        $currentIndex = isset($indexBySlug[$currentSlug]) ? (int) $indexBySlug[$currentSlug] : 0;

        $previous = null;
        if ($currentIndex > 0) {
            $previousPost = $publishedPosts[$currentIndex - 1] ?? null;
            if (is_array($previousPost)) {
                $previous = [
                    'index' => $currentIndex - 1,
                    'href' => url('/' . $currentLocale . '/blog/' . rawurlencode((string) ($previousPost['slug'] ?? ''))),
                ];
            }
        }

        $next = null;
        if ($currentIndex < ($total - 1)) {
            $nextPost = $publishedPosts[$currentIndex + 1] ?? null;
            if (is_array($nextPost)) {
                $next = [
                    'index' => $currentIndex + 1,
                    'href' => url('/' . $currentLocale . '/blog/' . rawurlencode((string) ($nextPost['slug'] ?? ''))),
                ];
            }
        }

        $visibleIndexes = [
            0,
            $total - 1,
            max(0, $currentIndex - 2),
            max(0, $currentIndex - 1),
            $currentIndex,
            min($total - 1, $currentIndex + 1),
            min($total - 1, $currentIndex + 2),
        ];
        $visibleIndexes = array_values(array_unique(array_filter($visibleIndexes, static fn ($value): bool => is_int($value))));
        sort($visibleIndexes);

        $items = [];
        $lastIndex = null;
        foreach ($visibleIndexes as $index) {
            if ($lastIndex !== null && $index > ($lastIndex + 1)) {
                $items[] = [
                    'type' => 'ellipsis',
                ];
            }

            $candidate = $publishedPosts[$index] ?? null;
            if (is_array($candidate)) {
                $items[] = [
                    'type' => 'page',
                    'index' => $index,
                    'number' => $index + 1,
                    'href' => url('/' . $currentLocale . '/blog/' . rawurlencode((string) ($candidate['slug'] ?? ''))),
                    'active' => $index === $currentIndex,
                ];
            }

            $lastIndex = $index;
        }

        return [
            'total' => $total,
            'current_index' => $currentIndex,
            'items' => $items,
            'previous' => $previous,
            'next' => $next,
        ];
    }

    private function normalizeContentMediaUrls(string $content): string
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
}
