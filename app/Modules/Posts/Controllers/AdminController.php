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
use App\Core\ContentDocumentStore;
use App\Core\I18n;
use App\Core\FlatFile;
use App\Core\ModuleManager;
use App\Modules\Auth\Services\RoleService;
use App\Modules\Posts\Services\PostTranslationService;
use App\Modules\Trash\Services\TrashService;

class AdminController extends BaseController
{
    private ContentDocumentStore $posts;
    private FlatFile $categories;
    private CategoryTranslationService $categoryTranslations;
    private PostTranslationService $translations;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Posts');
        $this->posts = ContentDocumentStore::for('core/posts');
        $this->categories = FlatFile::for('core/categories');
        $this->categoryTranslations = new CategoryTranslationService($this->categories);
        $this->translations = new PostTranslationService($this->posts);
    }

    public function index(): void
    {
        if (!$this->authorize('posts.view')) {
            return;
        }

        $page = max(1, (int) $this->request->input('page', 1));
        $filterCategory = trim((string) $this->request->input('category', ''));
        $categoriesEnabled = $this->isCategoriesEnabled();
        $categories = $categoriesEnabled ? $this->getCategoriesList(I18n::getLocale()) : [];
        $categoriesById = [];
        foreach ($categories as $cat) {
            if (!empty($cat['id'])) {
                $categoriesById[(string) $cat['id']] = $cat['name'] ?? '';
            }
        }

        $validIds = array_keys($categoriesById);
        if ($filterCategory !== '' && !in_array($filterCategory, $validIds, true)) {
            $filterCategory = '';
        }

        $allPosts = $this->buildGroupedPostsForAdminList();

        if ($filterCategory !== '') {
            $allPosts = array_filter($allPosts, function ($post) use ($filterCategory) {
                $postCategories = $post['categories'] ?? [];
                if (!is_array($postCategories)) {
                    $postCategories = [$postCategories];
                }
                $postCategories = array_map('strval', $postCategories);
                return in_array($filterCategory, $postCategories, true);
            });
        }

        $perPage = 10;
        $total = count($allPosts);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $posts = [
            'data' => array_slice($allPosts, $offset, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ];

        $this->render('Posts/Views/admin/index', [
            'pageTitle' => __('posts_list', 'Posts'),
            'posts' => $posts,
            'categories' => $categories,
            'categoriesById' => $categoriesById,
            'filterCategory' => $filterCategory,
            'categoriesEnabled' => $categoriesEnabled,
            'trashEnabled' => $this->isTrashEnabled(),
            'trashCount' => $this->getTrashCount(),
        ], 'admin.main');
    }

    public function create(): void
    {
        if (!$this->authorize('posts.create')) {
            return;
        }

        $this->render('Posts/Views/admin/form', [
            'pageTitle' => $this->buildComposePageTitle($this->resolveRequestedTranslationLocale()),
            'post' => null,
            'formData' => $this->buildCreateFormData(),
            'formLabels' => $this->buildFormLabels($this->resolveRequestedTranslationLocale()),
            'categories' => $this->isCategoriesEnabled() ? $this->getCategoriesList($this->resolveRequestedTranslationLocale()) : [],
            'categoriesEnabled' => $this->isCategoriesEnabled(),
            'menuCustomAlert' => null,
            'translationUi' => $this->buildTranslationUiForCreate(),
        ], 'admin.main');
    }

    public function store(): void
    {
        if (!$this->authorize('posts.create')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $data = $this->request->only(['title', 'slug', 'excerpt', 'content', 'featured_image', 'meta_title', 'meta_description', 'status']);
        $data['categories'] = $this->isCategoriesEnabled()
            ? $this->normalizeCategoryIds($this->request->input('categories', []))
            : [];

        $activeLocale = $this->resolveRequestedTranslationLocale();
        $translationGroup = trim((string) $this->request->input('translation_group', ''));
        $sourceLocale = $this->translations->normalizeLocale((string) $this->request->input('source_locale', ''));
        if ($sourceLocale === '') {
            $sourceLocale = $activeLocale;
        }

        if (empty($data['title'])) {
            $this->session->flash('error', __('title_required', 'Posts'));
            $this->session->flash('old', $data);
            $this->redirect($this->buildCreateUrl($activeLocale, $translationGroup, (string) $this->request->input('source_id', '')));
            return;
        }

        if (empty($data['slug'])) {
            $data['slug'] = str_slug($data['title']);
        }

        if ($translationGroup !== '') {
            $existingTranslation = $this->translations->findByTranslationGroupAndLocale($translationGroup, $activeLocale);
            if (is_array($existingTranslation)) {
                $this->session->flash('warning', __('translation_already_exists', 'Posts', [
                    'locale' => $this->translations->getLocaleLabel($activeLocale, I18n::getLocale()),
                ]));
                $this->redirect(url('/admin/posts/' . $existingTranslation['id'] . '/edit'));
                return;
            }
        }

        $data['locale'] = $activeLocale;
        $data['source_locale'] = $sourceLocale;
        if ($translationGroup !== '') {
            $data['translation_group'] = $translationGroup;
            $sourcePost = $this->translations->resolveSourcePost($translationGroup);
            if (is_array($sourcePost)) {
                $data['status'] = (string) ($sourcePost['status'] ?? 'draft');
            }
        }
        $data['slug'] = $this->translations->resolveUniqueSlug((string) $data['slug'], $activeLocale);

        $data['author_id'] = auth()['id'];
        $data['status'] = $data['status'] ?? 'draft';

        $isPublishing = $data['status'] === 'published';
        if ($isPublishing) {
            hook_run('posts.before_publish', $data);
        }
        hook_run('posts.before_save', $data);
        $post = $this->posts->create($data);
        if (trim((string) ($post['translation_group'] ?? '')) === '') {
            $post = $this->posts->update((string) $post['id'], [
                'translation_group' => (string) $post['id'],
                'locale' => $activeLocale,
                'source_locale' => $sourceLocale,
            ]) ?? $post;
        }
        hook_run('posts.after_save', $post);
        if ($isPublishing) {
            hook_run('posts.after_publish', $post);
        }

        $this->session->flash('success', __('post_created', 'Posts'));
        $this->redirect(url('/admin/posts/' . $post['id'] . '/edit'));
    }

    public function edit(string $id): void
    {
        $post = $this->translations->find($id);

        if (!$post) {
            $this->session->flash('error', __('post_not_found', 'Posts'));
            $this->redirect(url('/admin/posts'));
            return;
        }

        if (!$this->canEditPost($post)) {
            return;
        }

        $requestedLocale = $this->resolveRequestedTranslationLocale((string) ($post['locale'] ?? ''));
        $translationGroup = (string) ($post['translation_group'] ?? '');
        if ($translationGroup !== '' && $requestedLocale !== (string) ($post['locale'] ?? '')) {
            $localizedPost = $this->translations->findByTranslationGroupAndLocale($translationGroup, $requestedLocale);
            if (is_array($localizedPost)) {
                $this->redirect(url('/admin/posts/' . $localizedPost['id'] . '/edit'));
                return;
            }
        }

        $this->render('Posts/Views/admin/form', [
            'pageTitle' => $this->buildEditPageTitle((string) ($post['locale'] ?? '')),
            'post' => $post,
            'formData' => $post,
            'formLabels' => $this->buildFormLabels((string) ($post['locale'] ?? '')),
            'categories' => $this->isCategoriesEnabled() ? $this->getCategoriesList((string) ($post['locale'] ?? I18n::getLocale())) : [],
            'categoriesEnabled' => $this->isCategoriesEnabled(),
            'menuCustomAlert' => $this->getMenuCustomAlert($post),
            'translationUi' => $this->buildTranslationUiForEdit($post),
        ], 'admin.main');
    }

    public function update(string $id): void
    {
        $post = $this->translations->find($id);
        if (!$post) {
            $this->session->flash('error', __('post_not_found', 'Posts'));
            $this->redirect(url('/admin/posts'));
            return;
        }

        if (!$this->canEditPost($post)) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $data = $this->request->only(['title', 'slug', 'excerpt', 'content', 'featured_image', 'meta_title', 'meta_description', 'status']);
        $data['categories'] = $this->isCategoriesEnabled()
            ? $this->normalizeCategoryIds($this->request->input('categories', []))
            : [];

        $activeLocale = $this->translations->normalizeLocale((string) $this->request->input('locale', (string) ($post['locale'] ?? '')));
        if ($activeLocale === '') {
            $activeLocale = (string) ($post['locale'] ?? $this->translations->defaultLocale());
        }
        if (empty($data['slug'])) {
            $data['slug'] = str_slug((string) ($data['title'] ?? ''));
        }

        $data['translation_group'] = trim((string) $this->request->input('translation_group', (string) ($post['translation_group'] ?? $id)));
        if ($data['translation_group'] === '') {
            $data['translation_group'] = $id;
        }
        $data['locale'] = $activeLocale;
        $data['source_locale'] = $this->translations->normalizeLocale((string) $this->request->input('source_locale', (string) ($post['source_locale'] ?? $activeLocale)));
        if ($data['source_locale'] === '') {
            $data['source_locale'] = $activeLocale;
        }
        $data['slug'] = $this->translations->resolveUniqueSlug((string) $data['slug'], $activeLocale, $id);
        $sourcePost = $this->translations->resolveSourcePost((string) $data['translation_group']);
        $isSourcePost = is_array($sourcePost)
            ? (string) ($sourcePost['id'] ?? '') === $id
            : true;
        if (!$isSourcePost && is_array($sourcePost)) {
            $data['status'] = (string) ($sourcePost['status'] ?? ($post['status'] ?? 'draft'));
        }

        $payload = array_merge($post, $data);
        $isPublishing = ($post['status'] ?? 'draft') !== 'published'
            && ($data['status'] ?? ($post['status'] ?? 'draft')) === 'published';
        if ($isPublishing) {
            hook_run('posts.before_publish', $payload);
        }
        hook_run('posts.before_save', $payload);
        $updated = $this->posts->update($id, $data);
        if ($updated) {
            hook_run('posts.after_save', $updated);
            if ($isPublishing) {
                hook_run('posts.after_publish', $updated);
            }
            if ($isSourcePost) {
                $this->syncTranslationStatuses(
                    (string) ($updated['translation_group'] ?? $id),
                    $id,
                    (string) ($updated['status'] ?? 'draft')
                );
            }
        }

        $this->session->flash('success', __('post_updated', 'Posts'));
        $this->redirect(url('/admin/posts/' . $id . '/edit'));
    }

    public function delete(string $id): void
    {
        $post = $this->translations->find($id);
        $groupPosts = $this->resolveDeletionGroup($post, $id);
        if ($groupPosts === []) {
            $this->session->flash('error', __('post_not_found', 'Posts'));
            $this->redirect(url('/admin/posts'));
            return;
        }

        foreach ($groupPosts as $groupPost) {
            if (!$this->userCanDeletePost($groupPost)) {
                $this->session->flash('error', __('error.unauthorized', 'Core'));
                $this->redirect(url('/admin/posts'));
                return;
            }
        }

        if (!$this->verifyCsrf()) return;

        foreach ($groupPosts as $groupPost) {
            hook_run('posts.before_delete', $groupPost);
            $this->posts->delete((string) ($groupPost['id'] ?? ''));
            hook_run('posts.after_delete', $groupPost);
        }

        $this->session->flash('success', __('post_deleted', 'Posts'));
        $this->redirect(url('/admin/posts'));
    }

    public function batch(): void
    {
        $redirectCategory = trim((string) $this->request->input('return_category', ''));
        $redirectUrl = $this->buildPostsIndexUrl($redirectCategory);

        if (!can('posts.delete') && !can('posts.delete_own')) {
            $this->session->flash('error', __('error.unauthorized', 'Core'));
            $this->redirect($redirectUrl);
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $action = trim((string) $this->request->input('action', ''));
        $ids = $this->normalizePostIds($this->request->input('ids', []));

        if ($ids === []) {
            $this->session->flash('warning', __('posts_batch_no_selection', 'Posts'));
            $this->redirect($redirectUrl);
            return;
        }

        if (!in_array($action, ['archive', 'delete'], true)) {
            $this->session->flash('warning', __('posts_batch_invalid_action', 'Posts'));
            $this->redirect($redirectUrl);
            return;
        }

        if ($action === 'archive' && !$this->isTrashEnabled()) {
            $this->session->flash('error', __('posts_trash_unavailable', 'Posts'));
            $this->redirect($redirectUrl);
            return;
        }

        $trash = $action === 'archive' ? new TrashService() : null;
        $processed = 0;
        $skipped = 0;
        $deletedBy = trim((string) (auth()['name'] ?? auth()['email'] ?? ''));

        foreach ($ids as $id) {
            $post = $this->translations->find($id);
            $groupPosts = $this->resolveDeletionGroup($post, $id);
            if ($groupPosts === []) {
                $skipped++;
                continue;
            }
            $canDeleteGroup = true;
            foreach ($groupPosts as $groupPost) {
                if (!$this->userCanDeletePost($groupPost)) {
                    $canDeleteGroup = false;
                    break;
                }
            }
            if (!$canDeleteGroup) {
                $skipped++;
                continue;
            }

            if ($action === 'archive') {
                $archivedEntries = [];
                $archiveFailed = false;
                foreach ($groupPosts as $groupPost) {
                    hook_run('posts.before_archive', $groupPost);
                    $archived = $trash?->archivePost($groupPost, $deletedBy);
                    if (!is_array($archived)) {
                        $archiveFailed = true;
                        break;
                    }
                    $archivedEntries[] = $archived;
                }
                if ($archiveFailed) {
                    foreach ($archivedEntries as $archivedEntry) {
                        $trash?->delete((string) ($archivedEntry['id'] ?? ''));
                    }
                    $skipped++;
                    continue;
                }

                $deleteFailed = false;
                foreach ($groupPosts as $groupPost) {
                    $groupId = (string) ($groupPost['id'] ?? '');
                    if ($groupId === '' || !$this->posts->delete($groupId)) {
                        $deleteFailed = true;
                        break;
                    }
                    hook_run('posts.after_archive', $groupPost);
                }
                if ($deleteFailed) {
                    $skipped++;
                    continue;
                }

                $processed++;
                continue;
            }

            $deleteFailed = false;
            foreach ($groupPosts as $groupPost) {
                hook_run('posts.before_delete', $groupPost);
                $groupId = (string) ($groupPost['id'] ?? '');
                if ($groupId === '' || !$this->posts->delete($groupId)) {
                    $deleteFailed = true;
                    break;
                }
                hook_run('posts.after_delete', $groupPost);
            }
            if ($deleteFailed) {
                $skipped++;
                continue;
            }
            $processed++;
        }

        if ($processed > 0) {
            $flashKey = $action === 'archive'
                ? 'posts_batch_archive_success'
                : 'posts_batch_delete_success';
            $this->session->flash('success', __($flashKey, 'Posts', ['count' => (string) $processed]));
        }

        if ($skipped > 0) {
            $this->session->flash('warning', __('posts_batch_skipped', 'Posts', ['count' => (string) $skipped]));
        }

        if ($processed === 0 && $skipped === 0) {
            $this->session->flash('warning', __('posts_batch_no_selection', 'Posts'));
        }

        $this->redirect($redirectUrl);
    }

    private function getMenuCustomAlert(array $post): ?string
    {
        $id = (string) ($post['id'] ?? '');
        if ($id === '') {
            return null;
        }
        $slug = (string) ($post['slug'] ?? '');
        $menus = FlatFile::settings('menus');
        if (!is_array($menus)) {
            return null;
        }
        if ($this->hasCustomMenuLabel($menus, 'post', $id, $slug)) {
            return __('menu_custom_label_warning', 'Posts');
        }
        return null;
    }

    private function hasCustomMenuLabel(array $menus, string $type, string $id, string $slug): bool
    {
        $matchesRef = static function (array $item) use ($type, $id, $slug): bool {
            $refType = (string) ($item['refType'] ?? '');
            $ref = (string) ($item['ref'] ?? '');
            if ($refType !== $type || $ref === '') {
                return false;
            }
            return $ref === $id || ($slug !== '' && $ref === $slug);
        };
        $isCustom = static function (array $item) use ($matchesRef): bool {
            if (!$matchesRef($item)) {
                return false;
            }
            return (string) ($item['labelMode'] ?? '') === 'custom';
        };

        $items = $menus['main']['items'] ?? [];
        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item) && $isCustom($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getCategoriesList(?string $locale = null): array
    {
        return $this->categoryTranslations->buildLocalizedCategories('blog', $locale ?? I18n::getLocale(), true);
    }

    private function isCategoriesEnabled(): bool
    {
        $manager = new ModuleManager();
        $enabled = array_flip($manager->enabledNames());
        return isset($enabled['Categories']);
    }

    private function normalizeCategoryIds(mixed $input): array
    {
        $ids = is_array($input) ? $input : [$input];
        $ids = array_values(array_filter(array_map('strval', $ids), static function ($id) {
            return $id !== '';
        }));

        if (empty($ids)) {
            return [];
        }

        $validIds = [];
        foreach ($ids as $id) {
            $canonicalId = $this->categoryTranslations->resolveCanonicalId($id);
            if ($canonicalId !== '') {
                $validIds[] = $canonicalId;
            }
        }

        return array_values(array_unique($validIds));
    }

    private function canEditPost(?array $post): bool
    {
        $user = $this->session->get('user');
        if (!is_array($user)) {
            $this->redirect(url('/login'));
            return false;
        }

        $role = (string) ($user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER);
        if (RoleService::hasPermission($role, 'posts.edit')) {
            return true;
        }

        $userId = (string) ($user['id'] ?? '');
        $authorId = (string) (($post['author_id'] ?? '') ?: '');
        $canOwnEdit = RoleService::hasPermission($role, 'posts.edit_own');
        if ($canOwnEdit && $userId !== '' && $authorId !== '' && $userId === $authorId) {
            return true;
        }

        $this->session->flash('error', __('error.unauthorized', 'Core'));
        $this->redirect(url('/admin/posts'));
        return false;
    }

    private function canDeletePost(?array $post): bool
    {
        if ($this->userCanDeletePost($post)) {
            return true;
        }

        $this->session->flash('error', __('error.unauthorized', 'Core'));
        $this->redirect(url('/admin/posts'));
        return false;
    }

    private function userCanDeletePost(?array $post): bool
    {
        $user = $this->session->get('user');
        if (!is_array($user)) {
            return false;
        }

        $role = (string) ($user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER);
        if (RoleService::hasPermission($role, 'posts.delete')) {
            return true;
        }

        $userId = (string) ($user['id'] ?? '');
        $authorId = (string) (($post['author_id'] ?? '') ?: '');
        $canOwnDelete = RoleService::hasPermission($role, 'posts.delete_own');
        if ($canOwnDelete && $userId !== '' && $authorId !== '' && $userId === $authorId) {
            return true;
        }

        return false;
    }

    /**
     * @param mixed $input
     * @return array<int, string>
     */
    private function normalizePostIds(mixed $input): array
    {
        $ids = is_array($input) ? $input : [$input];
        $ids = array_map(static fn ($id): string => trim((string) $id), $ids);
        $ids = array_values(array_filter($ids, static fn (string $id): bool => $id !== ''));

        return array_values(array_unique($ids));
    }

    private function isTrashEnabled(): bool
    {
        $manager = new ModuleManager([BASE_PATH . '/app/Modules', BASE_PATH . '/app/Extensions'], BASE_PATH . '/data/modules.json');
        return $manager->isEnabled('Trash');
    }

    private function getTrashCount(): int
    {
        if (!$this->isTrashEnabled()) {
            return 0;
        }

        $trash = new TrashService();
        if (can('posts.delete')) {
            return $trash->countPosts();
        }

        if (!can('posts.delete_own')) {
            return 0;
        }

        $count = 0;
        foreach ($trash->all('post') as $item) {
            $payload = is_array($item['payload'] ?? null) ? $item['payload'] : null;
            if ($this->userCanDeletePost($payload)) {
                $count++;
            }
        }

        return $count;
    }

    private function buildPostsIndexUrl(string $filterCategory = ''): string
    {
        $baseUrl = url('/admin/posts');
        if ($filterCategory === '') {
            return $baseUrl;
        }

        return $baseUrl . '?category=' . urlencode($filterCategory);
    }

    private function resolveRequestedTranslationLocale(string $fallback = ''): string
    {
        $locale = $this->translations->normalizeLocale((string) $this->request->input('locale', ''));
        if ($locale !== '') {
            return $locale;
        }

        $fallbackLocale = $this->translations->normalizeLocale($fallback);
        if ($fallbackLocale !== '') {
            return $fallbackLocale;
        }

        return $this->translations->defaultLocale();
    }

    private function buildCreateFormData(): array
    {
        $activeLocale = $this->resolveRequestedTranslationLocale();
        $translationGroup = trim((string) $this->request->input('translation_group', ''));
        $sourceId = trim((string) $this->request->input('source_id', ''));
        $sourcePost = $sourceId !== '' ? $this->translations->find($sourceId) : null;

        if (!is_array($sourcePost) && $translationGroup !== '') {
            $sourcePost = $this->translations->resolveSourcePost($translationGroup);
        }

        return $this->translations->buildTranslationSeed($activeLocale, $sourcePost);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTranslationUiForCreate(): array
    {
        $activeLocale = $this->resolveRequestedTranslationLocale();
        $translationGroup = trim((string) $this->request->input('translation_group', ''));
        $sourceId = trim((string) $this->request->input('source_id', ''));
        $sourcePost = $sourceId !== '' ? $this->translations->find($sourceId) : null;

        if (!is_array($sourcePost) && $translationGroup !== '') {
            $sourcePost = $this->translations->resolveSourcePost($translationGroup);
        }

        $sourceLocale = is_array($sourcePost)
            ? (string) ($sourcePost['source_locale'] ?? $sourcePost['locale'] ?? $activeLocale)
            : $activeLocale;

        return $this->buildTranslationUi(
            $translationGroup,
            $activeLocale,
            $sourceLocale,
            null,
            $sourcePost
        );
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function buildTranslationUiForEdit(array $post): array
    {
        $post = $this->translations->normalizePost($post);

        return $this->buildTranslationUi(
            (string) ($post['translation_group'] ?? ''),
            (string) ($post['locale'] ?? $this->translations->defaultLocale()),
            (string) ($post['source_locale'] ?? $post['locale'] ?? $this->translations->defaultLocale()),
            $post,
            $this->translations->resolveSourcePost((string) ($post['translation_group'] ?? ''))
        );
    }

    /**
     * @param array<string, mixed>|null $activePost
     * @param array<string, mixed>|null $sourcePost
     * @return array<string, mixed>
     */
    private function buildTranslationUi(
        string $translationGroup,
        string $activeLocale,
        string $sourceLocale,
        ?array $activePost,
        ?array $sourcePost
    ): array {
        $supportedLocales = $this->translations->supportedLocales();
        $tabs = [];
        $translations = $translationGroup !== ''
            ? $this->translations->getTranslations($translationGroup)
            : [];

        foreach ($supportedLocales as $locale) {
            $translation = $translations[$locale] ?? null;
            $exists = is_array($translation);
            $tabs[] = [
                'code' => $locale,
                'label' => $this->translations->getLocaleLabel($locale, I18n::getLocale()),
                'exists' => $exists,
                'is_active' => $locale === $activeLocale,
                'is_source' => $locale === $sourceLocale,
                'status' => $exists ? $this->translations->resolveEffectiveStatus($translation) : null,
                'url' => $exists
                    ? url('/admin/posts/' . $translation['id'] . '/edit')
                    : ($translationGroup !== '' ? $this->buildCreateUrl($locale, $translationGroup, (string) ($sourcePost['id'] ?? '')) : ''),
            ];
        }

        return [
            'translation_group' => $translationGroup,
            'active_locale' => $activeLocale,
            'active_locale_label' => $this->translations->getLocaleLabel($activeLocale, I18n::getLocale()),
            'source_locale' => $sourceLocale,
            'source_locale_label' => $this->translations->getLocaleLabel($sourceLocale, I18n::getLocale()),
            'active_is_source' => is_array($activePost)
                ? (string) ($activePost['id'] ?? '') === (string) ($sourcePost['id'] ?? '')
                : $translationGroup === '',
            'source_status' => (string) ($sourcePost['status'] ?? 'draft'),
            'active_post_id' => (string) ($activePost['id'] ?? ''),
            'source_post_id' => (string) ($sourcePost['id'] ?? ''),
            'tabs' => $tabs,
            'can_create_additional' => $translationGroup !== '',
            'is_translation_create' => !is_array($activePost),
        ];
    }

    private function buildCreateUrl(string $locale, string $translationGroup = '', string $sourceId = ''): string
    {
        $query = ['locale' => $locale];
        if ($translationGroup !== '') {
            $query['translation_group'] = $translationGroup;
        }
        if ($sourceId !== '') {
            $query['source_id'] = $sourceId;
        }

        return url('/admin/posts/create?' . http_build_query($query));
    }

    private function buildComposePageTitle(string $locale): string
    {
        $label = $this->translations->getLocaleLabel($locale, I18n::getLocale());
        if ($label === '') {
            return __('create_post', 'Posts');
        }

        return __('compose_post_in_locale', 'Posts', ['locale' => $label]);
    }

    private function buildEditPageTitle(string $locale): string
    {
        $label = $this->translations->getLocaleLabel($locale, I18n::getLocale());
        if ($label === '') {
            return __('edit_post', 'Posts');
        }

        return __('edit_post_in_locale', 'Posts', ['locale' => $label]);
    }

    /**
     * @return array<string, string>
     */
    private function buildFormLabels(string $locale): array
    {
        $postsTranslations = $this->loadTranslationsForLocale('Posts', $locale);
        $coreTranslations = $this->loadTranslationsForLocale('Core', $locale);

        return [
            'translations' => $this->translationValue($postsTranslations, 'translations', __('translations', 'Posts')),
            'translation_save_first' => $this->translationValue($postsTranslations, 'translation_save_first', __('translation_save_first', 'Posts')),
            'translation_source' => $this->translationValue($postsTranslations, 'translation_source', __('translation_source', 'Posts')),
            'translation_missing' => $this->translationValue($postsTranslations, 'translation_missing', __('translation_missing', 'Posts')),
            'translation_ready' => $this->translationValue($postsTranslations, 'translation_ready', __('translation_ready', 'Posts')),
            'translation_status_follow_source' => $this->translationValue($postsTranslations, 'translation_status_follow_source', __('translation_status_follow_source', 'Posts')),
            'status_draft' => $this->translationValue($postsTranslations, 'status_draft', __('status_draft', 'Posts')),
            'status_published' => $this->translationValue($postsTranslations, 'status_published', __('status_published', 'Posts')),
            'title' => $this->translationValue($postsTranslations, 'title', __('title', 'Posts')),
            'slug' => $this->translationValue($postsTranslations, 'slug', __('slug', 'Posts')),
            'excerpt' => $this->translationValue($postsTranslations, 'excerpt', __('excerpt', 'Posts')),
            'content' => $this->translationValue($postsTranslations, 'content', __('content', 'Posts')),
            'status' => $this->translationValue($postsTranslations, 'status', __('status', 'Posts')),
            'featured_image' => $this->translationValue($postsTranslations, 'featured_image', __('featured_image', 'Posts')),
            'featured_image_placeholder' => $this->translationValue($postsTranslations, 'featured_image_placeholder', __('featured_image_placeholder', 'Posts')),
            'featured_image_open' => $this->translationValue($postsTranslations, 'featured_image_open', __('featured_image_open', 'Posts')),
            'featured_image_clear' => $this->translationValue($postsTranslations, 'featured_image_clear', __('featured_image_clear', 'Posts')),
            'featured_image_hint' => $this->translationValue($postsTranslations, 'featured_image_hint', __('featured_image_hint', 'Posts')),
            'featured_image_modal_unavailable' => $this->translationValue($postsTranslations, 'featured_image_modal_unavailable', __('featured_image_modal_unavailable', 'Posts')),
            'suneditor_toolbar_expand' => $this->translationValue($postsTranslations, 'suneditor_toolbar_expand', __('suneditor_toolbar_expand', 'Posts')),
            'suneditor_toolbar_collapse' => $this->translationValue($postsTranslations, 'suneditor_toolbar_collapse', __('suneditor_toolbar_collapse', 'Posts')),
            'categories' => $this->translationValue($postsTranslations, 'categories', __('categories', 'Posts')),
            'no_categories' => $this->translationValue($postsTranslations, 'no_categories', __('no_categories', 'Posts')),
            'seo_section' => $this->translationValue($postsTranslations, 'seo_section', __('seo_section', 'Posts')),
            'meta_title' => $this->translationValue($postsTranslations, 'meta_title', __('meta_title', 'Posts')),
            'meta_description' => $this->translationValue($postsTranslations, 'meta_description', __('meta_description', 'Posts')),
            'back' => $this->translationValue($postsTranslations, 'back_to_list', __('back', 'Core')),
            'save' => $this->translationValue($coreTranslations, 'save', __('save', 'Core')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadTranslationsForLocale(string $namespace, string $locale): array
    {
        $resolvedLocale = $this->translations->normalizeLocale($locale);
        if ($resolvedLocale === '') {
            $resolvedLocale = $this->translations->defaultLocale();
        }

        $path = I18n::resolveTranslationPathForNamespace($namespace, $resolvedLocale);
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function translationValue(array $catalog, string $key, string $fallback): string
    {
        $value = $catalog[$key] ?? null;
        return is_string($value) && trim($value) !== '' ? $value : $fallback;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildGroupedPostsForAdminList(): array
    {
        $groups = [];
        foreach ($this->translations->all() as $post) {
            $groupId = (string) ($post['translation_group'] ?? $post['id'] ?? '');
            if ($groupId === '') {
                continue;
            }
            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [];
            }
            $groups[$groupId][] = $post;
        }

        $rows = [];
        foreach ($groups as $groupId => $translations) {
            $sourcePost = $this->translations->resolveSourcePost($groupId);
            if (!is_array($sourcePost)) {
                $sourcePost = reset($translations) ?: null;
            }
            if (!is_array($sourcePost)) {
                continue;
            }

            $row = $this->translations->normalizePost($sourcePost);
            $row['translation_count'] = count($translations);
            $row['translations_available'] = $this->buildTranslationFlags($translations, (string) ($row['source_locale'] ?? $row['locale'] ?? ''));
            $row['can_delete'] = $this->canDeletePostGroup($translations);
            $rows[] = $row;
        }

        usort($rows, fn($a, $b) => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $posts
     */
    private function canDeletePostGroup(array $posts): bool
    {
        if ($posts === []) {
            return false;
        }

        foreach ($posts as $post) {
            if (!$this->userCanDeletePost($post)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $translations
     * @return array<int, array<string, mixed>>
     */
    private function buildTranslationFlags(array $translations, string $sourceLocale): array
    {
        $byLocale = [];
        foreach ($translations as $translation) {
            $locale = (string) ($translation['locale'] ?? '');
            if ($locale === '') {
                continue;
            }
            $byLocale[$locale] = [
                'code' => $locale,
                'label' => $this->translations->getLocaleLabel($locale, I18n::getLocale()),
                'is_source' => $locale === $sourceLocale,
            ];
        }

        $flags = [];
        foreach ($this->translations->supportedLocales() as $locale) {
            if (isset($byLocale[$locale])) {
                $flags[] = $byLocale[$locale];
            }
        }

        return $flags;
    }

    /**
     * @param array<string, mixed>|null $post
     * @return array<int, array<string, mixed>>
     */
    private function resolveDeletionGroup(?array $post, string $fallbackId = ''): array
    {
        if (!is_array($post)) {
            $post = $fallbackId !== '' ? $this->translations->find($fallbackId) : null;
        }
        if (!is_array($post)) {
            return [];
        }

        $translationGroup = trim((string) ($post['translation_group'] ?? ''));
        if ($translationGroup === '') {
            return [$post];
        }

        $translations = $this->translations->getTranslations($translationGroup, false);
        if ($translations === []) {
            return [$post];
        }

        return array_values($translations);
    }

    private function syncTranslationStatuses(string $translationGroup, string $sourceId, string $status): void
    {
        if ($translationGroup === '') {
            return;
        }

        $translations = $this->translations->getTranslations($translationGroup, false);
        foreach ($translations as $translation) {
            $translationId = (string) ($translation['id'] ?? '');
            if ($translationId === '' || $translationId === $sourceId) {
                continue;
            }
            if ((string) ($translation['status'] ?? 'draft') === $status) {
                continue;
            }
            $this->posts->update($translationId, ['status' => $status]);
        }
    }
}
