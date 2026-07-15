<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Categories\Controllers;

use App\Core\BaseController;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Core\ModuleManager;
use App\Modules\Auth\Services\RoleService;
use App\Modules\Categories\Services\CategoryTranslationService;
use App\Modules\Trash\Services\TrashService;

class AdminController extends BaseController
{
    private FlatFile $categories;
    private CategoryTranslationService $translations;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Categories');
        $this->categories = FlatFile::for('core/categories');
        $this->translations = new CategoryTranslationService($this->categories);
    }

    public function index(): void
    {
        if (!$this->authorize('categories.view')) {
            return;
        }

        $page = max(1, (int) $this->request->input('page', 1));
        $allCategories = $this->buildGroupedCategoriesForAdminList();
        $perPage = 10;
        $total = count($allCategories);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $categories = [
            'data' => array_slice($allCategories, $offset, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ];

        $this->render('Categories/Views/admin/index', [
            'pageTitle' => __('categories_list', 'Categories'),
            'categories' => $categories,
            'moduleOptions' => $this->getModuleOptions(I18n::getLocale()),
            'trashEnabled' => $this->isTrashEnabled(),
            'trashCount' => $this->getTrashCount(),
        ], 'admin.main');
    }

    public function create(): void
    {
        if (!$this->authorize('categories.create')) {
            return;
        }

        $activeLocale = $this->resolveRequestedTranslationLocale();
        $translationGroup = trim((string) $this->request->input('translation_group', ''));
        if ($translationGroup !== '') {
            $sourceCategory = $this->translations->resolveSourceCategory($translationGroup);
            if (is_array($sourceCategory)) {
                $this->redirect(url('/admin/categories/' . $sourceCategory['id'] . '/edit?locale=' . rawurlencode($activeLocale)));
                return;
            }
        }

        $this->render('Categories/Views/admin/form', [
            'pageTitle' => $this->buildComposePageTitle($activeLocale),
            'category' => null,
            'formLabels' => $this->buildFormLabels($activeLocale),
            'moduleOptions' => $this->getModuleOptions($activeLocale),
            'translationUi' => $this->buildTranslationUiForCreate($activeLocale),
        ], 'admin.main');
    }

    public function store(): void
    {
        if (!$this->authorize('categories.create')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $activeLocale = $this->resolveRequestedTranslationLocale();
        $sourceLocale = $this->translations->normalizeLocale((string) $this->request->input('source_locale', ''));
        if ($sourceLocale === '') {
            $sourceLocale = $activeLocale;
        }
        $globalStatus = $this->normalizeStatus((string) $this->request->input('status', 'active'));
        $globalModule = $this->normalizeModule((string) $this->request->input('module', 'blog'));

        $prepared = $this->prepareSubmittedTranslations([], $sourceLocale, '', $globalStatus, $globalModule, null);
        if (!empty($prepared['errors'])) {
            $error = $prepared['errors'][0];
            $errorLocale = (string) ($error['locale'] ?? $activeLocale);
            $this->session->flash('error', (string) ($error['message'] ?? __('name_required', 'Categories')));
            $this->flashTranslationOldInput($prepared['submitted'], $errorLocale, '', $sourceLocale, $globalStatus, $globalModule);
            $this->redirect(url('/admin/categories/create?locale=' . rawurlencode($errorLocale)));
            return;
        }

        $entries = is_array($prepared['entries'] ?? null) ? $prepared['entries'] : [];
        $sourcePayload = $entries[$sourceLocale] ?? null;
        if (!is_array($sourcePayload)) {
            $this->session->flash('error', __('name_required', 'Categories'));
            $this->flashTranslationOldInput($prepared['submitted'], $sourceLocale, '', $sourceLocale, $globalStatus, $globalModule);
            $this->redirect(url('/admin/categories/create?locale=' . rawurlencode($sourceLocale)));
            return;
        }

        hook_run('categories.before_save', $sourcePayload);
        $sourceCategory = $this->categories->create($sourcePayload);
        $translationGroup = (string) ($sourceCategory['id'] ?? '');
        $sourceCategory = $this->categories->update((string) $sourceCategory['id'], [
            'translation_group' => $translationGroup,
            'locale' => $sourceLocale,
            'source_locale' => $sourceLocale,
            'status' => $globalStatus,
            'module' => $globalModule,
        ]) ?? $sourceCategory;
        hook_run('categories.after_save', $sourceCategory);

        $savedByLocale = [
            $sourceLocale => $sourceCategory,
        ];

        foreach ($entries as $locale => $entry) {
            if ($locale === $sourceLocale) {
                continue;
            }

            $payload = array_merge($entry, [
                'translation_group' => $translationGroup,
                'source_locale' => $sourceLocale,
                'status' => $globalStatus,
                'module' => $globalModule,
                'author_id' => (string) ($sourceCategory['author_id'] ?? auth()['id'] ?? ''),
            ]);
            hook_run('categories.before_save', $payload);
            $savedByLocale[$locale] = $this->categories->create($payload);
            hook_run('categories.after_save', $savedByLocale[$locale]);
        }

        $redirectCategory = $savedByLocale[$activeLocale] ?? $sourceCategory;

        $this->session->flash('success', __('category_created', 'Categories'));
        $this->redirect(url('/admin/categories/' . $redirectCategory['id'] . '/edit?locale=' . rawurlencode($activeLocale)));
    }

    public function edit(string $id): void
    {
        if (!$this->authorize('categories.edit')) {
            return;
        }

        $category = $this->translations->find($id);
        if (!$category) {
            $this->session->flash('error', __('category_not_found', 'Categories'));
            $this->redirect(url('/admin/categories'));
            return;
        }

        $requestedLocale = $this->resolveRequestedTranslationLocale((string) ($category['locale'] ?? ''));

        $this->render('Categories/Views/admin/form', [
            'pageTitle' => $this->buildEditPageTitle($requestedLocale),
            'category' => $category,
            'formLabels' => $this->buildFormLabels($requestedLocale),
            'moduleOptions' => $this->getModuleOptions($requestedLocale),
            'translationUi' => $this->buildTranslationUiForEdit($category, $requestedLocale),
        ], 'admin.main');
    }

    public function update(string $id): void
    {
        if (!$this->authorize('categories.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $category = $this->translations->find($id);
        if (!$category) {
            $this->session->flash('error', __('category_not_found', 'Categories'));
            $this->redirect(url('/admin/categories'));
            return;
        }

        $activeLocale = $this->translations->normalizeLocale((string) $this->request->input('locale', ''));
        $translationGroup = trim((string) ($category['translation_group'] ?? $id));
        if ($translationGroup === '') {
            $translationGroup = $id;
        }

        $sourceCategory = $this->translations->resolveSourceCategory($translationGroup);
        if (!is_array($sourceCategory)) {
            $sourceCategory = $this->translations->normalizeCategory($category);
        }

        $sourceLocale = $this->translations->normalizeLocale((string) $this->request->input('source_locale', (string) ($sourceCategory['source_locale'] ?? $sourceCategory['locale'] ?? '')));
        if ($sourceLocale === '') {
            $sourceLocale = (string) ($sourceCategory['locale'] ?? $this->translations->defaultLocale());
        }
        if ($activeLocale === '') {
            $activeLocale = $sourceLocale;
        }

        $globalStatus = $this->normalizeStatus((string) $this->request->input('status', (string) ($sourceCategory['status'] ?? 'active')));
        $globalModule = $this->normalizeModule((string) $this->request->input('module', (string) ($sourceCategory['module'] ?? 'blog')));
        $existingTranslations = $this->translations->getTranslations($translationGroup, false);
        if ($existingTranslations === []) {
            $existingTranslations = [
                (string) ($category['locale'] ?? $sourceLocale) => $this->translations->normalizeCategory($category),
            ];
        }

        $prepared = $this->prepareSubmittedTranslations($existingTranslations, $sourceLocale, $translationGroup, $globalStatus, $globalModule, $sourceCategory);
        if (!empty($prepared['errors'])) {
            $error = $prepared['errors'][0];
            $errorLocale = (string) ($error['locale'] ?? $activeLocale);
            $this->session->flash('error', (string) ($error['message'] ?? __('name_required', 'Categories')));
            $this->flashTranslationOldInput($prepared['submitted'], $errorLocale, $translationGroup, $sourceLocale, $globalStatus, $globalModule);
            $this->redirect(url('/admin/categories/' . $id . '/edit?locale=' . rawurlencode($errorLocale)));
            return;
        }

        $entries = is_array($prepared['entries'] ?? null) ? $prepared['entries'] : [];
        $savedByLocale = [];
        $sourceId = (string) ($sourceCategory['id'] ?? '');
        $sourceAuthorId = trim((string) (($sourceCategory['author_id'] ?? '') ?: (auth()['id'] ?? '')));

        foreach ($this->translations->supportedLocales() as $locale) {
            $entry = $entries[$locale] ?? null;
            if (!is_array($entry)) {
                continue;
            }

            $existing = is_array($existingTranslations[$locale] ?? null)
                ? $this->translations->normalizeCategory($existingTranslations[$locale])
                : null;

            if (is_array($existing)) {
                $payload = array_merge($existing, $entry);
                hook_run('categories.before_save', $payload);
                $saved = $this->categories->update((string) $existing['id'], $entry);
                if (!is_array($saved)) {
                    continue;
                }
                hook_run('categories.after_save', $saved);
                $savedByLocale[$locale] = $saved;
                if ($locale === $sourceLocale) {
                    $sourceId = (string) ($saved['id'] ?? $sourceId);
                }
                continue;
            }

            $payload = array_merge($entry, [
                'translation_group' => $translationGroup,
                'source_locale' => $sourceLocale,
                'status' => $globalStatus,
                'module' => $globalModule,
                'author_id' => $sourceAuthorId,
            ]);
            hook_run('categories.before_save', $payload);
            $saved = $this->categories->create($payload);
            hook_run('categories.after_save', $saved);
            $savedByLocale[$locale] = $saved;
            if ($locale === $sourceLocale) {
                $sourceId = (string) ($saved['id'] ?? $sourceId);
            }
        }

        if ($sourceId !== '') {
            $this->syncTranslationState($translationGroup, $sourceId, $globalStatus, $globalModule);
        }

        $redirectCategory = $savedByLocale[$activeLocale]
            ?? ($sourceId !== '' ? $this->translations->find($sourceId) : null)
            ?? $category;

        $this->session->flash('success', __('category_updated', 'Categories'));
        $this->redirect(url('/admin/categories/' . $redirectCategory['id'] . '/edit?locale=' . rawurlencode($activeLocale)));
    }

    public function delete(string $id): void
    {
        $category = $this->translations->find($id);
        $groupCategories = $this->resolveDeletionGroup($category, $id);
        if ($groupCategories === []) {
            $this->session->flash('error', __('category_not_found', 'Categories'));
            $this->redirect(url('/admin/categories'));
            return;
        }

        foreach ($groupCategories as $groupCategory) {
            if (!$this->userCanDeleteCategory($groupCategory)) {
                $this->session->flash('error', __('error.unauthorized', 'Core'));
                $this->redirect(url('/admin/categories'));
                return;
            }
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        foreach ($groupCategories as $groupCategory) {
            hook_run('categories.before_delete', $groupCategory);
            $this->categories->delete((string) ($groupCategory['id'] ?? ''));
            hook_run('categories.after_delete', $groupCategory);
        }

        $this->session->flash('success', __('category_deleted', 'Categories'));
        $this->redirect(url('/admin/categories'));
    }

    public function batch(): void
    {
        $redirectUrl = $this->buildCategoriesIndexUrl();
        if (!can('categories.delete') && !can('categories.delete_own')) {
            $this->session->flash('error', __('error.unauthorized', 'Core'));
            $this->redirect($redirectUrl);
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $action = trim((string) $this->request->input('action', ''));
        $ids = $this->normalizeCategoryIds($this->request->input('ids', []));

        if ($ids === []) {
            $this->session->flash('warning', __('categories_batch_no_selection', 'Categories'));
            $this->redirect($redirectUrl);
            return;
        }

        if (!in_array($action, ['archive', 'delete'], true)) {
            $this->session->flash('warning', __('categories_batch_invalid_action', 'Categories'));
            $this->redirect($redirectUrl);
            return;
        }

        if ($action === 'archive' && !$this->isTrashEnabled()) {
            $this->session->flash('error', __('categories_trash_unavailable', 'Categories'));
            $this->redirect($redirectUrl);
            return;
        }

        $trash = $action === 'archive' ? new TrashService() : null;
        $processed = 0;
        $skipped = 0;
        $deletedBy = trim((string) (auth()['name'] ?? auth()['email'] ?? ''));

        foreach ($ids as $id) {
            $category = $this->translations->find($id);
            $groupCategories = $this->resolveDeletionGroup($category, $id);
            if ($groupCategories === []) {
                $skipped++;
                continue;
            }

            if (!$this->canDeleteCategoryGroup($groupCategories)) {
                $skipped++;
                continue;
            }

            if ($action === 'archive') {
                $archivedEntries = [];
                $archiveFailed = false;
                foreach ($groupCategories as $groupCategory) {
                    hook_run('categories.before_archive', $groupCategory);
                    $archived = $trash?->archiveCategory($groupCategory, $deletedBy);
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
                foreach ($groupCategories as $groupCategory) {
                    $groupId = (string) ($groupCategory['id'] ?? '');
                    if ($groupId === '' || !$this->categories->delete($groupId)) {
                        $deleteFailed = true;
                        break;
                    }
                    hook_run('categories.after_archive', $groupCategory);
                }
                if ($deleteFailed) {
                    $skipped++;
                    continue;
                }

                $processed++;
                continue;
            }

            $deleteFailed = false;
            foreach ($groupCategories as $groupCategory) {
                hook_run('categories.before_delete', $groupCategory);
                $groupId = (string) ($groupCategory['id'] ?? '');
                if ($groupId === '' || !$this->categories->delete($groupId)) {
                    $deleteFailed = true;
                    break;
                }
                hook_run('categories.after_delete', $groupCategory);
            }
            if ($deleteFailed) {
                $skipped++;
                continue;
            }
            $processed++;
        }

        if ($processed > 0) {
            $flashKey = $action === 'archive'
                ? 'categories_batch_archive_success'
                : 'categories_batch_delete_success';
            $this->session->flash('success', __($flashKey, 'Categories', ['count' => (string) $processed]));
        }

        if ($skipped > 0) {
            $this->session->flash('warning', __('categories_batch_skipped', 'Categories', ['count' => (string) $skipped]));
        }

        if ($processed === 0 && $skipped === 0) {
            $this->session->flash('warning', __('categories_batch_no_selection', 'Categories'));
        }

        $this->redirect($redirectUrl);
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

    /**
     * @return array<string, mixed>
     */
    private function buildTranslationUiForCreate(string $activeLocale): array
    {
        return $this->buildTranslationUi('', $activeLocale, $activeLocale, null, null);
    }

    /**
     * @param array<string, mixed> $category
     * @return array<string, mixed>
     */
    private function buildTranslationUiForEdit(array $category, string $activeLocale): array
    {
        $category = $this->translations->normalizeCategory($category);

        return $this->buildTranslationUi(
            (string) ($category['translation_group'] ?? ''),
            $activeLocale,
            (string) ($category['source_locale'] ?? $category['locale'] ?? $this->translations->defaultLocale()),
            $category,
            $this->translations->resolveSourceCategory((string) ($category['translation_group'] ?? ''))
        );
    }

    /**
     * @param array<string, mixed>|null $activeCategory
     * @param array<string, mixed>|null $sourceCategory
     * @return array<string, mixed>
     */
    private function buildTranslationUi(
        string $translationGroup,
        string $activeLocale,
        string $sourceLocale,
        ?array $activeCategory,
        ?array $sourceCategory
    ): array {
        $supportedLocales = $this->translations->supportedLocales();
        $tabs = [];
        $translations = $translationGroup !== ''
            ? $this->translations->getTranslations($translationGroup)
            : [];

        foreach ($supportedLocales as $locale) {
            $translation = $translations[$locale] ?? null;
            $exists = is_array($translation);
            $values = $this->translations->buildTranslationSeed($locale, $sourceCategory);
            if ($exists) {
                $values = array_merge($values, $translation);
            }
            $tabs[] = [
                'code' => $locale,
                'label' => $this->translations->getLocaleLabel($locale, I18n::getLocale()),
                'exists' => $exists,
                'is_active' => $locale === $activeLocale,
                'is_source' => $locale === $sourceLocale,
                'status' => $exists ? $this->translations->resolveEffectiveStatus($translation) : null,
                'values' => $values,
                'form_labels' => $this->buildFormLabels($locale),
                'category_id' => $exists ? (string) ($translation['id'] ?? '') : '',
            ];
        }

        return [
            'translation_group' => $translationGroup,
            'active_locale' => $activeLocale,
            'source_locale' => $sourceLocale,
            'active_is_source' => is_array($activeCategory)
                ? (string) ($activeCategory['id'] ?? '') === (string) ($sourceCategory['id'] ?? '')
                : $translationGroup === '',
            'source_status' => (string) ($sourceCategory['status'] ?? 'active'),
            'source_module' => $this->normalizeModule((string) ($sourceCategory['module'] ?? 'blog')),
            'active_category_id' => (string) ($activeCategory['id'] ?? ''),
            'source_category_id' => (string) ($sourceCategory['id'] ?? ''),
            'tabs' => $tabs,
        ];
    }

    private function buildComposePageTitle(string $locale): string
    {
        $label = $this->translations->getLocaleLabel($locale, I18n::getLocale());
        if ($label === '') {
            return __('create_category', 'Categories');
        }

        return __('compose_category_in_locale', 'Categories', ['locale' => $label]);
    }

    private function buildEditPageTitle(string $locale): string
    {
        $label = $this->translations->getLocaleLabel($locale, I18n::getLocale());
        if ($label === '') {
            return __('edit_category', 'Categories');
        }

        return __('edit_category_in_locale', 'Categories', ['locale' => $label]);
    }

    /**
     * @return array<string, string>
     */
    private function buildFormLabels(string $locale): array
    {
        $categoryTranslations = $this->loadTranslationsForLocale('Categories', $locale);
        $coreTranslations = $this->loadTranslationsForLocale('Core', $locale);

        return [
            'translations' => $this->translationValue($categoryTranslations, 'translations', __('translations', 'Categories')),
            'translation_save_first' => $this->translationValue($categoryTranslations, 'translation_save_first', __('translation_save_first', 'Categories')),
            'translation_source' => $this->translationValue($categoryTranslations, 'translation_source', __('translation_source', 'Categories')),
            'translation_missing' => $this->translationValue($categoryTranslations, 'translation_missing', __('translation_missing', 'Categories')),
            'translation_ready' => $this->translationValue($categoryTranslations, 'translation_ready', __('translation_ready', 'Categories')),
            'translation_status_follow_source' => $this->translationValue($categoryTranslations, 'translation_status_follow_source', __('translation_status_follow_source', 'Categories')),
            'translation_module_follow_source' => $this->translationValue($categoryTranslations, 'translation_module_follow_source', __('translation_module_follow_source', 'Categories')),
            'name' => $this->translationValue($categoryTranslations, 'name', __('name', 'Categories')),
            'slug' => $this->translationValue($categoryTranslations, 'slug', __('slug', 'Categories')),
            'description' => $this->translationValue($categoryTranslations, 'description', __('description', 'Categories')),
            'module' => $this->translationValue($categoryTranslations, 'module', __('module', 'Categories')),
            'module_blog' => $this->translationValue($categoryTranslations, 'module_blog', __('module_blog', 'Categories')),
            'module_downloads' => $this->translationValue($categoryTranslations, 'module_downloads', __('module_downloads', 'Categories')),
            'status' => $this->translationValue($categoryTranslations, 'status', __('status', 'Categories')),
            'status_active' => $this->translationValue($categoryTranslations, 'status_active', __('status_active', 'Categories')),
            'status_inactive' => $this->translationValue($categoryTranslations, 'status_inactive', __('status_inactive', 'Categories')),
            'back' => $this->translationValue($categoryTranslations, 'back_to_list', __('back', 'Core')),
            'save' => $this->translationValue($coreTranslations, 'save', __('save', 'Core')),
        ];
    }

    private function normalizeStatus(string $value): string
    {
        return in_array($value, ['active', 'inactive'], true) ? $value : 'active';
    }

    /**
     * @param array<string, array<string, mixed>> $existingTranslations
     * @param array<string, mixed>|null $sourceCategory
     * @return array{entries: array<string, array<string, mixed>>, errors: array<int, array<string, string>>, submitted: array<string, array<string, string>>}
     */
    private function prepareSubmittedTranslations(
        array $existingTranslations,
        string $sourceLocale,
        string $translationGroup,
        string $globalStatus,
        string $globalModule,
        ?array $sourceCategory
    ): array {
        $input = $this->request->input('translations', []);
        if (!is_array($input)) {
            $input = [];
        }

        $sourceAuthorId = trim((string) (($sourceCategory['author_id'] ?? '') ?: (auth()['id'] ?? '')));
        $submitted = [];
        $entries = [];
        $errors = [];

        foreach ($this->translations->supportedLocales() as $locale) {
            $entry = is_array($input[$locale] ?? null) ? $input[$locale] : [];
            $name = trim((string) ($entry['name'] ?? ''));
            $slug = trim((string) ($entry['slug'] ?? ''));
            $description = trim((string) ($entry['description'] ?? ''));
            $submitted[$locale] = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
            ];

            $existing = is_array($existingTranslations[$locale] ?? null)
                ? $this->translations->normalizeCategory($existingTranslations[$locale])
                : null;

            $hasInput = $name !== '' || $slug !== '' || $description !== '';
            $mustPersist = $locale === $sourceLocale || $hasInput || is_array($existing);
            if (!$mustPersist) {
                continue;
            }

            if ($name === '') {
                $errors[] = [
                    'locale' => $locale,
                    'message' => __('name_required', 'Categories'),
                ];
                continue;
            }

            $slugCandidate = str_slug($slug !== '' ? $slug : $name);
            if ($slugCandidate === '') {
                $slugCandidate = 'category';
            }

            $payload = [
                'name' => $name,
                'slug' => $this->translations->resolveUniqueSlug($slugCandidate, $locale, is_array($existing) ? (string) ($existing['id'] ?? '') : null),
                'description' => $description,
                'locale' => $locale,
                'source_locale' => $sourceLocale,
                'translation_group' => $translationGroup,
                'status' => $globalStatus,
                'module' => $globalModule,
            ];

            if (is_array($existing)) {
                $entries[$locale] = array_merge($existing, $payload);
                continue;
            }

            $entries[$locale] = array_merge($payload, [
                'author_id' => $sourceAuthorId,
            ]);
        }

        return [
            'entries' => $entries,
            'errors' => $errors,
            'submitted' => $submitted,
        ];
    }

    /**
     * @param array<string, array<string, string>> $translations
     */
    private function flashTranslationOldInput(
        array $translations,
        string $activeLocale,
        string $translationGroup,
        string $sourceLocale,
        string $status,
        string $module
    ): void {
        $this->session->flash('old', [
            'translations' => $translations,
            'locale' => $activeLocale,
            'translation_group' => $translationGroup,
            'source_locale' => $sourceLocale,
            'status' => $status,
            'module' => $module,
        ]);
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

    private function getModuleOptions(?string $locale = null): array
    {
        $labels = $this->buildFormLabels($locale ?? I18n::getLocale());

        return [
            'blog' => $labels['module_blog'],
            'downloads' => $labels['module_downloads'],
        ];
    }

    private function normalizeModule(string $value): string
    {
        $options = $this->getModuleOptions(I18n::getLocale());
        $safeValue = trim($value);
        if (array_key_exists($safeValue, $options)) {
            return $safeValue;
        }

        $keys = array_keys($options);
        return $keys[0] ?? 'blog';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildGroupedCategoriesForAdminList(): array
    {
        $groups = [];
        foreach ($this->translations->all() as $category) {
            $groupId = (string) ($category['translation_group'] ?? $category['id'] ?? '');
            if ($groupId === '') {
                continue;
            }
            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [];
            }
            $groups[$groupId][] = $category;
        }

        $rows = [];
        foreach ($groups as $groupId => $translations) {
            $sourceCategory = $this->translations->resolveSourceCategory($groupId);
            if (!is_array($sourceCategory)) {
                $sourceCategory = reset($translations) ?: null;
            }
            if (!is_array($sourceCategory)) {
                continue;
            }

            $row = $this->translations->normalizeCategory($sourceCategory);
            $row['status'] = $this->translations->resolveEffectiveStatus($row);
            $row['translation_count'] = count($translations);
            $row['translations_available'] = $this->buildTranslationFlags($translations, (string) ($row['source_locale'] ?? $row['locale'] ?? ''));
            $row['can_delete'] = $this->canDeleteCategoryGroup($translations);
            $rows[] = $row;
        }

        usort($rows, static fn($a, $b) => (string) ($b['created_at'] ?? '') <=> (string) ($a['created_at'] ?? ''));

        return $rows;
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
     * @param array<string, mixed>|null $category
     * @return array<int, array<string, mixed>>
     */
    private function resolveDeletionGroup(?array $category, string $fallbackId = ''): array
    {
        if (!is_array($category)) {
            $category = $fallbackId !== '' ? $this->translations->find($fallbackId) : null;
        }
        if (!is_array($category)) {
            return [];
        }

        $translationGroup = trim((string) ($category['translation_group'] ?? ''));
        if ($translationGroup === '') {
            return [$category];
        }

        $translations = $this->translations->getTranslations($translationGroup, false);
        if ($translations === []) {
            return [$category];
        }

        return array_values($translations);
    }

    private function syncTranslationState(string $translationGroup, string $sourceId, string $status, string $module): void
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

            $updates = [];
            if ((string) ($translation['status'] ?? 'active') !== $status) {
                $updates['status'] = $status;
            }
            if ($this->normalizeModule((string) ($translation['module'] ?? 'blog')) !== $module) {
                $updates['module'] = $module;
            }

            if ($updates !== []) {
                $this->categories->update($translationId, $updates);
            }
        }
    }

    private function isTrashEnabled(): bool
    {
        $manager = ModuleManager::instance();

        return $manager->isEnabled('Trash');
    }

    private function getTrashCount(): int
    {
        if (!$this->isTrashEnabled()) {
            return 0;
        }

        $trash = new TrashService();
        if (can('categories.delete')) {
            return $trash->countCategories();
        }

        if (!can('categories.delete_own')) {
            return 0;
        }

        $count = 0;
        foreach ($trash->all('category') as $item) {
            $payload = is_array($item['payload'] ?? null) ? $item['payload'] : null;
            if ($this->userCanDeleteCategory($payload)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param mixed $ids
     * @return array<int, string>
     */
    private function normalizeCategoryIds(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        $normalized = [];
        foreach ($ids as $id) {
            $safeId = trim((string) $id);
            if ($safeId === '') {
                continue;
            }

            $canonicalId = $this->translations->resolveCanonicalId($safeId);
            if ($canonicalId === '') {
                continue;
            }

            $normalized[] = $canonicalId;
        }

        return array_values(array_unique($normalized));
    }

    private function buildCategoriesIndexUrl(): string
    {
        return url('/admin/categories');
    }

    /**
     * @param array<int, array<string, mixed>> $categories
     */
    private function canDeleteCategoryGroup(array $categories): bool
    {
        if ($categories === []) {
            return false;
        }

        foreach ($categories as $category) {
            if (!$this->userCanDeleteCategory($category)) {
                return false;
            }
        }

        return true;
    }

    private function userCanDeleteCategory(?array $category): bool
    {
        $user = $this->session->get('user');
        if (!is_array($user)) {
            return false;
        }

        $role = (string) ($user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER);
        if (RoleService::hasPermission($role, 'categories.delete')) {
            return true;
        }

        $userId = trim((string) ($user['id'] ?? ''));
        $authorId = trim((string) (($category['author_id'] ?? '') ?: ''));
        $canOwnDelete = RoleService::hasPermission($role, 'categories.delete_own');

        return $canOwnDelete && $userId !== '' && $authorId !== '' && $userId === $authorId;
    }
}
