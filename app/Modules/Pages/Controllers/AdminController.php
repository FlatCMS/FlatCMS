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
use App\Core\I18n;
use App\Core\FlatFile;
use App\Core\ModuleManager;
use App\Modules\Auth\Services\RoleService;
use App\Modules\Pages\Services\PageTranslationService;
use App\Modules\Pages\Support\SystemPages;
use App\Modules\Trash\Services\TrashService;

class AdminController extends BaseController
{
    private FlatFile $pages;
    private PageTranslationService $translations;

    /** @var array<string,array<string,mixed>>|null */
    private ?array $requiredPagesCache = null;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Pages');
        $this->pages = FlatFile::for('core/pages');
        $this->translations = new PageTranslationService($this->pages);
    }

    public function index(): void
    {
        if ($this->redirectToPagesAdminOverride('index')) {
            return;
        }

        if (!$this->authorize('pages.view')) {
            return;
        }

        $requiredPages = $this->ensureRequiredPages();
        $requiredPageIds = [];
        foreach ($requiredPages as $requiredPage) {
            if (!is_array($requiredPage)) {
                continue;
            }
            $requiredId = trim((string) ($requiredPage['id'] ?? ''));
            if ($requiredId !== '') {
                $requiredPageIds[$requiredId] = true;
            }
        }

        $page = max(1, (int) $this->request->input('page', 1));
        $status = trim((string) $this->request->input('status', 'all'));
        if (!in_array($status, ['all', 'draft', 'published'], true)) {
            $status = 'all';
        }

        $allPages = $this->buildGroupedPagesForAdminList($requiredPageIds);
        if ($status !== 'all') {
            $allPages = array_values(array_filter($allPages, static function (array $item) use ($status): bool {
                return (string) ($item['status'] ?? 'draft') === $status;
            }));
        }

        $perPage = 10;
        $total = count($allPages);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $pages = [
            'data' => array_slice($allPages, $offset, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ];

        $this->render('Pages/Views/admin/index', [
            'pageTitle' => __('pages_list', 'Pages'),
            'pages' => $pages,
            'status' => $status,
            'trashEnabled' => $this->isTrashEnabled(),
            'trashCount' => $this->getTrashCount(),
        ], 'admin.main');
    }

    public function create(): void
    {
        if ($this->redirectToPagesAdminOverride('create')) {
            return;
        }

        if (!$this->authorize('pages.create')) {
            return;
        }

        $this->ensureRequiredPages();

        $activeLocale = $this->resolveRequestedTranslationLocale();
        $translationGroup = trim((string) $this->request->input('translation_group', ''));
        if ($translationGroup !== '') {
            $sourcePage = $this->translations->resolveSourcePage($translationGroup);
            if (is_array($sourcePage) && $this->supportsTranslationsForPage($sourcePage)) {
                $this->redirect(url('/admin/pages/' . $sourcePage['id'] . '/edit?locale=' . rawurlencode($activeLocale)));
                return;
            }
        }
        $this->render('Pages/Views/admin/form', [
            'pageTitle' => $this->buildComposePageTitle($activeLocale),
            'page' => null,
            'formLabels' => $this->buildFormLabels($activeLocale),
            'menuCustomAlert' => null,
            'translationUi' => $this->buildTranslationUiForCreate($activeLocale),
        ], 'admin.main');
    }

    public function store(): void
    {
        if (!$this->authorize('pages.create')) {
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
        $globalStatus = $this->normalizePageStatus((string) $this->request->input('status', 'draft'));

        $prepared = $this->prepareSubmittedPageTranslations([], $sourceLocale, '', $globalStatus, null);
        if (!empty($prepared['errors'])) {
            $error = $prepared['errors'][0];
            $errorLocale = (string) ($error['locale'] ?? $activeLocale);
            $this->session->flash('error', (string) ($error['message'] ?? __('title_required', 'Pages')));
            $this->flashPageTranslationOldInput($prepared['submitted'], $errorLocale, '', $sourceLocale, $globalStatus);
            $this->redirect(url('/admin/pages/create?locale=' . rawurlencode($errorLocale)));
            return;
        }

        $entries = is_array($prepared['entries'] ?? null) ? $prepared['entries'] : [];
        $sourcePayload = $entries[$sourceLocale] ?? null;
        if (!is_array($sourcePayload)) {
            $this->session->flash('error', __('title_required', 'Pages'));
            $this->flashPageTranslationOldInput($prepared['submitted'], $sourceLocale, '', $sourceLocale, $globalStatus);
            $this->redirect(url('/admin/pages/create?locale=' . rawurlencode($sourceLocale)));
            return;
        }

        hook_run('pages.before_save', $sourcePayload);
        $page = $this->pages->create($sourcePayload);
        $translationGroup = (string) ($page['id'] ?? '');
        $page = $this->pages->update((string) $page['id'], [
            'translation_group' => $translationGroup,
            'locale' => $sourceLocale,
            'source_locale' => $sourceLocale,
            'status' => $globalStatus,
        ]) ?? $page;
        hook_run('pages.after_save', $page);

        $savedByLocale = [
            $sourceLocale => $page,
        ];

        foreach ($entries as $locale => $entry) {
            if ($locale === $sourceLocale) {
                continue;
            }

            $payload = array_merge($entry, [
                'translation_group' => $translationGroup,
                'source_locale' => $sourceLocale,
                'status' => $globalStatus,
                'author_id' => (string) ($page['author_id'] ?? auth()['id'] ?? ''),
            ]);
            hook_run('pages.before_save', $payload);
            $savedByLocale[$locale] = $this->pages->create($payload);
            hook_run('pages.after_save', $savedByLocale[$locale]);
        }

        $redirectPage = $savedByLocale[$activeLocale] ?? $page;

        $this->session->flash('success', __('page_created', 'Pages'));
        $this->redirect(url('/admin/pages/' . $redirectPage['id'] . '/edit?locale=' . rawurlencode($activeLocale)));
    }

    public function edit(string $id): void
    {
        $requestedLocale = $this->resolveRequestedTranslationLocale();
        if ($this->redirectToPagesAdminOverride('edit', $id, $requestedLocale)) {
            return;
        }

        if (!$this->authorize('pages.edit')) {
            return;
        }

        $this->ensureRequiredPages();
        $page = $this->translations->find($id);

        if (!$page) {
            $this->session->flash('error', __('page_not_found', 'Pages'));
            $this->redirect(url('/admin/pages'));
            return;
        }

        if ($this->supportsTranslationsForPage($page)) {
            $requestedLocale = $this->resolveRequestedTranslationLocale((string) ($page['locale'] ?? ''));
            $activeLocale = $requestedLocale;
        } else {
            $activeLocale = I18n::getLocale();
        }

        $this->render('Pages/Views/admin/form', [
            'pageTitle' => $this->supportsTranslationsForPage($page)
                ? $this->buildEditPageTitle($activeLocale)
                : __('edit_page', 'Pages'),
            'page' => $page,
            'formLabels' => $this->buildFormLabels($activeLocale),
            'menuCustomAlert' => $this->getMenuCustomAlert($page),
            'translationUi' => $this->supportsTranslationsForPage($page)
                ? $this->buildTranslationUiForEdit($page, $activeLocale)
                : [],
        ], 'admin.main');
    }

    public function update(string $id): void
    {
        if (!$this->authorize('pages.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $page = $this->translations->find($id);
        if (!$page) {
            $this->session->flash('error', __('page_not_found', 'Pages'));
            $this->redirect(url('/admin/pages'));
            return;
        }

        $data = $this->request->only(['title', 'slug', 'content', 'meta_title', 'meta_description', 'status']);
        $supportsTranslations = $this->supportsTranslationsForPage($page);

        $title = trim((string) ($data['title'] ?? $page['title'] ?? ''));
        if ($supportsTranslations) {
            $activeLocale = $this->translations->normalizeLocale((string) $this->request->input('locale', ''));
            $translationGroup = trim((string) ($page['translation_group'] ?? $id));
            if ($translationGroup === '') {
                $translationGroup = $id;
            }

            $sourcePage = $this->translations->resolveSourcePage($translationGroup);
            if (!is_array($sourcePage)) {
                $sourcePage = $this->translations->normalizePage($page);
            }

            $sourceLocale = $this->translations->normalizeLocale((string) $this->request->input('source_locale', (string) ($sourcePage['source_locale'] ?? $sourcePage['locale'] ?? '')));
            if ($sourceLocale === '') {
                $sourceLocale = (string) ($sourcePage['locale'] ?? $this->translations->defaultLocale());
            }
            if ($activeLocale === '') {
                $activeLocale = $sourceLocale;
            }

            $globalStatus = $this->normalizePageStatus((string) $this->request->input('status', (string) ($sourcePage['status'] ?? 'draft')));
            $existingTranslations = $this->translations->getTranslations($translationGroup, false);
            if ($existingTranslations === []) {
                $existingTranslations = [
                    (string) ($page['locale'] ?? $sourceLocale) => $this->translations->normalizePage($page),
                ];
            }

            $prepared = $this->prepareSubmittedPageTranslations($existingTranslations, $sourceLocale, $translationGroup, $globalStatus, $sourcePage);
            if (!empty($prepared['errors'])) {
                $error = $prepared['errors'][0];
                $errorLocale = (string) ($error['locale'] ?? $activeLocale);
                $this->session->flash('error', (string) ($error['message'] ?? __('title_required', 'Pages')));
                $this->flashPageTranslationOldInput($prepared['submitted'], $errorLocale, $translationGroup, $sourceLocale, $globalStatus);
                $this->redirect(url('/admin/pages/' . $id . '/edit?locale=' . rawurlencode($errorLocale)));
                return;
            }

            $entries = is_array($prepared['entries'] ?? null) ? $prepared['entries'] : [];
            $savedByLocale = [];
            $sourceId = (string) ($sourcePage['id'] ?? '');
            $sourceAuthorId = trim((string) (($sourcePage['author_id'] ?? '') ?: (auth()['id'] ?? '')));

            foreach ($this->translations->supportedLocales() as $locale) {
                $entry = $entries[$locale] ?? null;
                if (!is_array($entry)) {
                    continue;
                }

                $existing = is_array($existingTranslations[$locale] ?? null)
                    ? $this->translations->normalizePage($existingTranslations[$locale])
                    : null;

                if (is_array($existing)) {
                    $payload = array_merge($existing, $entry);
                    hook_run('pages.before_save', $payload);
                    $saved = $this->pages->update((string) $existing['id'], $entry);
                    if (!is_array($saved)) {
                        continue;
                    }
                    hook_run('pages.after_save', $saved);
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
                    'author_id' => $sourceAuthorId,
                ]);
                hook_run('pages.before_save', $payload);
                $saved = $this->pages->create($payload);
                hook_run('pages.after_save', $saved);
                $savedByLocale[$locale] = $saved;
                if ($locale === $sourceLocale) {
                    $sourceId = (string) ($saved['id'] ?? $sourceId);
                }
            }

            if ($sourceId !== '') {
                $this->syncTranslationStatuses($translationGroup, $sourceId, $globalStatus);
            }

            $redirectPage = $savedByLocale[$activeLocale]
                ?? ($sourceId !== '' ? $this->translations->find($sourceId) : null)
                ?? $page;

            $this->session->flash('success', __('page_updated', 'Pages'));
            $this->redirect(url('/admin/pages/' . $redirectPage['id'] . '/edit?locale=' . rawurlencode($activeLocale)));
            return;
        }

        $slugInput = trim((string) ($data['slug'] ?? ''));
        $normalizedSlug = str_slug($slugInput !== '' ? $slugInput : $title);
        if ($normalizedSlug === '') {
            $normalizedSlug = trim((string) ($page['slug'] ?? ''));
        }
        if ($normalizedSlug === '') {
            $normalizedSlug = 'page';
        }
        $data['slug'] = $normalizedSlug;

        $existing = $this->pages->findBy('slug', $data['slug']);
        if ($existing && ($existing['id'] ?? '') !== $id) {
            $data['slug'] .= '-' . time();
        }

        $data['editor_mode'] = 'classic';
        $data['render_mode'] = 'classic';

        $payload = array_merge($page, $data);
        hook_run('pages.before_save', $payload);
        $updated = $this->pages->update($id, $data);
        if ($updated) {
            hook_run('pages.after_save', $updated);
        }

        $this->session->flash('success', __('page_updated', 'Pages'));
        $this->redirect(url('/admin/pages'));
    }

    public function delete(string $id): void
    {
        $this->ensureRequiredPages();
        $page = $this->translations->find($id);
        $groupPages = $this->resolveDeletionGroup($page, $id);
        if ($groupPages === []) {
            $this->session->flash('error', __('page_not_found', 'Pages'));
            $this->redirect(url('/admin/pages'));
            return;
        }

        foreach ($groupPages as $groupPage) {
            if (SystemPages::isRequiredPage($groupPage)) {
                $this->session->flash('error', __('system_page_delete_forbidden', 'Pages'));
                $this->redirect(url('/admin/pages'));
                return;
            }
        }

        foreach ($groupPages as $groupPage) {
            if (!$this->userCanDeletePage($groupPage)) {
                $this->session->flash('error', __('error.unauthorized', 'Core'));
                $this->redirect(url('/admin/pages'));
                return;
            }
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        foreach ($groupPages as $groupPage) {
            hook_run('pages.before_delete', $groupPage);
            $this->pages->delete((string) ($groupPage['id'] ?? ''));
            hook_run('pages.after_delete', $groupPage);
        }

        $this->session->flash('success', __('page_deleted', 'Pages'));
        $this->redirect(url('/admin/pages'));
    }

    public function batch(): void
    {
        $redirectUrl = $this->buildPagesIndexUrl(trim((string) $this->request->input('status', 'all')));
        if (!can('pages.delete') && !can('pages.delete_own')) {
            $this->session->flash('error', __('error.unauthorized', 'Core'));
            $this->redirect($redirectUrl);
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $action = trim((string) $this->request->input('action', ''));
        $ids = $this->normalizePageIds($this->request->input('ids', []));

        if ($ids === []) {
            $this->session->flash('warning', __('pages_batch_no_selection', 'Pages'));
            $this->redirect($redirectUrl);
            return;
        }

        if (!in_array($action, ['archive', 'delete'], true)) {
            $this->session->flash('warning', __('pages_batch_invalid_action', 'Pages'));
            $this->redirect($redirectUrl);
            return;
        }

        if ($action === 'archive' && !$this->isTrashEnabled()) {
            $this->session->flash('error', __('pages_trash_unavailable', 'Pages'));
            $this->redirect($redirectUrl);
            return;
        }

        $this->ensureRequiredPages();
        $trash = $action === 'archive' ? new TrashService() : null;
        $processed = 0;
        $skipped = 0;
        $deletedBy = trim((string) (auth()['name'] ?? auth()['email'] ?? ''));

        foreach ($ids as $id) {
            $page = $this->translations->find($id);
            $groupPages = $this->resolveDeletionGroup($page, $id);
            if ($groupPages === []) {
                $skipped++;
                continue;
            }

            $hasSystemPage = false;
            foreach ($groupPages as $groupPage) {
                if (SystemPages::isRequiredPage($groupPage)) {
                    $hasSystemPage = true;
                    break;
                }
            }
            if ($hasSystemPage) {
                $skipped++;
                continue;
            }

            if (!$this->canDeletePageGroup($groupPages)) {
                $skipped++;
                continue;
            }

            if ($action === 'archive') {
                $archivedEntries = [];
                $archiveFailed = false;
                foreach ($groupPages as $groupPage) {
                    hook_run('pages.before_archive', $groupPage);
                    $archived = $trash?->archivePage($groupPage, $deletedBy);
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
                foreach ($groupPages as $groupPage) {
                    $groupId = (string) ($groupPage['id'] ?? '');
                    if ($groupId === '' || !$this->pages->delete($groupId)) {
                        $deleteFailed = true;
                        break;
                    }
                    hook_run('pages.after_archive', $groupPage);
                }
                if ($deleteFailed) {
                    $skipped++;
                    continue;
                }

                $processed++;
                continue;
            }

            $deleteFailed = false;
            foreach ($groupPages as $groupPage) {
                hook_run('pages.before_delete', $groupPage);
                $groupId = (string) ($groupPage['id'] ?? '');
                if ($groupId === '' || !$this->pages->delete($groupId)) {
                    $deleteFailed = true;
                    break;
                }
                hook_run('pages.after_delete', $groupPage);
            }
            if ($deleteFailed) {
                $skipped++;
                continue;
            }
            $processed++;
        }

        if ($processed > 0) {
            $flashKey = $action === 'archive'
                ? 'pages_batch_archive_success'
                : 'pages_batch_delete_success';
            $this->session->flash('success', __($flashKey, 'Pages', ['count' => (string) $processed]));
        }

        if ($skipped > 0) {
            $this->session->flash('warning', __('pages_batch_skipped', 'Pages', ['count' => (string) $skipped]));
        }

        if ($processed === 0 && $skipped === 0) {
            $this->session->flash('warning', __('pages_batch_no_selection', 'Pages'));
        }

        $this->redirect($redirectUrl);
    }

    private function getMenuCustomAlert(array $page): ?string
    {
        $id = (string) ($page['id'] ?? '');
        if ($id === '') {
            return null;
        }
        $slug = (string) ($page['slug'] ?? '');
        $menus = FlatFile::settings('menus');
        if (!is_array($menus)) {
            return null;
        }
        if ($this->hasCustomMenuLabel($menus, 'page', $id, $slug)) {
            return __('menu_custom_label_warning', 'Pages');
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

    private function generateUniqueSlug(string $title): string
    {
        $base = str_slug($title);
        if ($base === '') {
            $base = 'page';
        }

        $candidate = $base;
        if ($this->pages->findBy('slug', $candidate)) {
            $candidate = $base . '-' . date('YmdHis');
        }

        while ($this->pages->findBy('slug', $candidate)) {
            $candidate = $base . '-' . date('YmdHis') . '-' . random_int(100, 999);
        }

        return $candidate;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function ensureRequiredPages(): array
    {
        if (is_array($this->requiredPagesCache)) {
            return $this->requiredPagesCache;
        }

        $this->requiredPagesCache = SystemPages::ensureRequired($this->pages, static function (string $key): string {
            return __($key, 'Pages');
        });

        return $this->requiredPagesCache;
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
        if (can('pages.delete')) {
            return $trash->countPages();
        }

        if (!can('pages.delete_own')) {
            return 0;
        }

        $count = 0;
        foreach ($trash->all('page') as $item) {
            $payload = is_array($item['payload'] ?? null) ? $item['payload'] : null;
            if ($this->userCanDeletePage($payload)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param mixed $ids
     * @return array<int, string>
     */
    private function normalizePageIds(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }

        $normalized = [];
        foreach ($ids as $id) {
            $value = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $id) ?? '';
            if ($value === '') {
                continue;
            }

            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
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
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private function buildTranslationUiForEdit(array $page, string $activeLocale): array
    {
        $page = $this->translations->normalizePage($page);

        return $this->buildTranslationUi(
            (string) ($page['translation_group'] ?? ''),
            $activeLocale,
            (string) ($page['source_locale'] ?? $page['locale'] ?? $this->translations->defaultLocale()),
            $page,
            $this->translations->resolveSourcePage((string) ($page['translation_group'] ?? ''))
        );
    }

    /**
     * @param array<string, mixed>|null $activePage
     * @param array<string, mixed>|null $sourcePage
     * @return array<string, mixed>
     */
    private function buildTranslationUi(
        string $translationGroup,
        string $activeLocale,
        string $sourceLocale,
        ?array $activePage,
        ?array $sourcePage
    ): array {
        $supportedLocales = $this->translations->supportedLocales();
        $tabs = [];
        $translations = $translationGroup !== ''
            ? $this->translations->getTranslations($translationGroup)
            : [];
        $builderRouteId = trim((string) ($sourcePage['id'] ?? $activePage['id'] ?? ''));

        foreach ($supportedLocales as $locale) {
            $translation = $translations[$locale] ?? null;
            $exists = is_array($translation);
            $values = $this->translations->buildTranslationSeed($locale, $sourcePage);
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
                'page_title' => (is_array($activePage) || is_array($sourcePage) || $translationGroup !== '')
                    ? $this->buildEditPageTitle($locale)
                    : $this->buildComposePageTitle($locale),
                'page_id' => $exists ? (string) ($translation['id'] ?? '') : '',
            ];
        }

        return [
            'translation_group' => $translationGroup,
            'active_locale' => $activeLocale,
            'active_locale_label' => $this->translations->getLocaleLabel($activeLocale, I18n::getLocale()),
            'source_locale' => $sourceLocale,
            'source_locale_label' => $this->translations->getLocaleLabel($sourceLocale, I18n::getLocale()),
            'active_is_source' => is_array($activePage)
                ? (string) ($activePage['id'] ?? '') === (string) ($sourcePage['id'] ?? '')
                : $translationGroup === '',
            'source_status' => (string) ($sourcePage['status'] ?? 'draft'),
            'tabs' => $tabs,
        ];
    }

    private function buildComposePageTitle(string $locale): string
    {
        $label = $this->translations->getLocaleLabel($locale, I18n::getLocale());
        if ($label === '') {
            return __('create_page', 'Pages');
        }

        return __('compose_page_in_locale', 'Pages', ['locale' => $label]);
    }

    private function buildEditPageTitle(string $locale): string
    {
        $label = $this->translations->getLocaleLabel($locale, I18n::getLocale());
        if ($label === '') {
            return __('edit_page', 'Pages');
        }

        return __('edit_page_in_locale', 'Pages', ['locale' => $label]);
    }

    /**
     * @return array<string, string>
     */
    private function buildFormLabels(string $locale): array
    {
        $pagesTranslations = $this->loadTranslationsForLocale('Pages', $locale);
        $coreTranslations = $this->loadTranslationsForLocale('Core', $locale);

        return [
            'translations' => $this->translationValue($pagesTranslations, 'translations', __('translations', 'Pages')),
            'translation_save_first' => $this->translationValue($pagesTranslations, 'translation_save_first', __('translation_save_first', 'Pages')),
            'translation_source' => $this->translationValue($pagesTranslations, 'translation_source', __('translation_source', 'Pages')),
            'translation_missing' => $this->translationValue($pagesTranslations, 'translation_missing', __('translation_missing', 'Pages')),
            'translation_ready' => $this->translationValue($pagesTranslations, 'translation_ready', __('translation_ready', 'Pages')),
            'translation_status_follow_source' => $this->translationValue($pagesTranslations, 'translation_status_follow_source', __('translation_status_follow_source', 'Pages')),
            'status_draft' => $this->translationValue($pagesTranslations, 'status_draft', __('status_draft', 'Pages')),
            'status_published' => $this->translationValue($pagesTranslations, 'status_published', __('status_published', 'Pages')),
            'title' => $this->translationValue($pagesTranslations, 'title', __('title', 'Pages')),
            'slug' => $this->translationValue($pagesTranslations, 'slug', __('slug', 'Pages')),
            'content' => $this->translationValue($pagesTranslations, 'content', __('content', 'Pages')),
            'status' => $this->translationValue($pagesTranslations, 'status', __('status', 'Pages')),
            'seo_section' => $this->translationValue($pagesTranslations, 'seo_section', __('seo_section', 'Pages')),
            'meta_title' => $this->translationValue($pagesTranslations, 'meta_title', __('meta_title', 'Pages')),
            'meta_description' => $this->translationValue($pagesTranslations, 'meta_description', __('meta_description', 'Pages')),
            'suneditor_toolbar_expand' => $this->translationValue($pagesTranslations, 'suneditor_toolbar_expand', __('suneditor_toolbar_expand', 'Pages')),
            'suneditor_toolbar_collapse' => $this->translationValue($pagesTranslations, 'suneditor_toolbar_collapse', __('suneditor_toolbar_collapse', 'Pages')),
            'suneditor_media_modal_unavailable' => $this->translationValue($pagesTranslations, 'suneditor_media_modal_unavailable', __('suneditor_media_modal_unavailable', 'Pages')),
            'back' => $this->translationValue($coreTranslations, 'back', __('back', 'Core')),
            'save' => $this->translationValue($coreTranslations, 'save', __('save', 'Core')),
        ];
    }

    private function normalizePageStatus(string $value): string
    {
        return in_array($value, ['draft', 'published'], true) ? $value : 'draft';
    }

    /**
     * @param array<string, array<string, mixed>> $existingTranslations
     * @param array<string, mixed>|null $sourcePage
     * @return array{entries: array<string, array<string, mixed>>, errors: array<int, array<string, string>>, submitted: array<string, array<string, string>>}
     */
    private function prepareSubmittedPageTranslations(
        array $existingTranslations,
        string $sourceLocale,
        string $translationGroup,
        string $globalStatus,
        ?array $sourcePage
    ): array {
        $input = $this->request->input('translations', []);
        if (!is_array($input)) {
            $input = [];
        }

        $sourceAuthorId = trim((string) (($sourcePage['author_id'] ?? '') ?: (auth()['id'] ?? '')));
        $submitted = [];
        $entries = [];
        $errors = [];

        foreach ($this->translations->supportedLocales() as $locale) {
            $entry = is_array($input[$locale] ?? null) ? $input[$locale] : [];
            $title = trim((string) ($entry['title'] ?? ''));
            $slug = trim((string) ($entry['slug'] ?? ''));
            $content = (string) ($entry['content'] ?? '');
            $metaTitle = trim((string) ($entry['meta_title'] ?? ''));
            $metaDescription = trim((string) ($entry['meta_description'] ?? ''));
            $submitted[$locale] = [
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
            ];

            $existing = is_array($existingTranslations[$locale] ?? null)
                ? $this->translations->normalizePage($existingTranslations[$locale])
                : null;

            $hasInput = $title !== '' || $slug !== '' || trim($content) !== '' || $metaTitle !== '' || $metaDescription !== '';
            $mustPersist = $locale === $sourceLocale || $hasInput || is_array($existing);
            if (!$mustPersist) {
                continue;
            }

            if ($title === '') {
                $errors[] = [
                    'locale' => $locale,
                    'message' => __('title_required', 'Pages'),
                ];
                continue;
            }

            $slugCandidate = str_slug($slug !== '' ? $slug : $title);
            if ($slugCandidate === '') {
                $slugCandidate = 'page';
            }

            $payload = [
                'title' => $title,
                'slug' => $this->translations->resolveUniqueSlug($slugCandidate, $locale, is_array($existing) ? (string) ($existing['id'] ?? '') : null),
                'content' => $content,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'locale' => $locale,
                'source_locale' => $sourceLocale,
                'translation_group' => $translationGroup,
                'status' => $globalStatus,
                'editor_mode' => 'classic',
                'render_mode' => 'classic',
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
    private function flashPageTranslationOldInput(
        array $translations,
        string $activeLocale,
        string $translationGroup,
        string $sourceLocale,
        string $status
    ): void {
        $this->session->flash('old', [
            'translations' => $translations,
            'locale' => $activeLocale,
            'translation_group' => $translationGroup,
            'source_locale' => $sourceLocale,
            'status' => $status,
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

    /**
     * @param array<string, bool> $requiredPageIds
     * @return array<int, array<string, mixed>>
     */
    private function buildGroupedPagesForAdminList(array $requiredPageIds): array
    {
        $groups = [];
        $rows = [];

        foreach ($this->translations->all() as $page) {
            $pageId = (string) ($page['id'] ?? '');
            $page['system_required'] = !empty($page['system_required']) || isset($requiredPageIds[$pageId]);

            if (!$this->groupsTranslationsForPage($page)) {
                $row = $this->translations->normalizePage($page);
                $row['status'] = (string) ($row['status'] ?? 'draft');
                $row['translation_count'] = 1;
                $row['translations_available'] = [];
                $row['translations_supported'] = false;
                $row['can_delete'] = !$row['system_required'] && $this->userCanDeletePage($row);
                $rows[] = $row;
                continue;
            }

            $groupId = (string) ($page['translation_group'] ?? $page['id'] ?? '');
            if ($groupId === '') {
                continue;
            }

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [];
            }
            $groups[$groupId][] = $page;
        }

        foreach ($groups as $groupId => $translations) {
            $sourcePage = $this->translations->resolveSourcePage($groupId);
            if (!is_array($sourcePage)) {
                $sourcePage = reset($translations) ?: null;
            }
            if (!is_array($sourcePage)) {
                continue;
            }

            $row = $this->translations->normalizePage($sourcePage);
            $row['system_required'] = !empty($row['system_required']) || $this->hasRequiredPageInGroup($translations);
            $row['status'] = $this->translations->resolveEffectiveStatus($row);
            $row['translation_count'] = count($translations);
            $row['translations_available'] = $this->buildTranslationFlags($translations, (string) ($row['source_locale'] ?? $row['locale'] ?? ''));
            $row['translations_supported'] = true;
            $row['can_delete'] = !$row['system_required'] && $this->canDeletePageGroup($translations);
            $rows[] = $row;
        }

        usort($rows, static function (array $a, array $b): int {
            $aDate = (string) ($a['updated_at'] ?? $a['created_at'] ?? '');
            $bDate = (string) ($b['updated_at'] ?? $b['created_at'] ?? '');
            return $bDate <=> $aDate;
        });

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $pages
     */
    private function canDeletePageGroup(array $pages): bool
    {
        if ($pages === []) {
            return false;
        }

        foreach ($pages as $page) {
            if (!$this->userCanDeletePage($page)) {
                return false;
            }
        }

        return true;
    }

    private function userCanDeletePage(?array $page): bool
    {
        $user = $this->session->get('user');
        if (!is_array($user)) {
            return false;
        }

        $role = (string) ($user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER);
        if (RoleService::hasPermission($role, 'pages.delete')) {
            return true;
        }

        $userId = trim((string) ($user['id'] ?? ''));
        $authorId = trim((string) (($page['author_id'] ?? '') ?: ''));
        $canOwnDelete = RoleService::hasPermission($role, 'pages.delete_own');

        return $canOwnDelete && $userId !== '' && $authorId !== '' && $userId === $authorId;
    }

    /**
     * @param array<int, array<string, mixed>> $translations
     */
    private function hasRequiredPageInGroup(array $translations): bool
    {
        foreach ($translations as $translation) {
            if (SystemPages::isRequiredPage($translation)) {
                return true;
            }
        }

        return false;
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
     * @param array<string, mixed>|null $page
     * @return array<int, array<string, mixed>>
     */
    private function resolveDeletionGroup(?array $page, string $fallbackId = ''): array
    {
        if (!is_array($page)) {
            $page = $fallbackId !== '' ? $this->translations->find($fallbackId) : null;
        }
        if (!is_array($page)) {
            return [];
        }

        if (!$this->groupsTranslationsForPage($page)) {
            return [$page];
        }

        $translationGroup = trim((string) ($page['translation_group'] ?? ''));
        if ($translationGroup === '') {
            return [$page];
        }

        $translations = $this->translations->getTranslations($translationGroup, false);
        if ($translations === []) {
            return [$page];
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
            if (!$this->supportsTranslationsForPage($translation)) {
                continue;
            }
            if ((string) ($translation['status'] ?? 'draft') === $status) {
                continue;
            }
            $this->pages->update($translationId, ['status' => $status]);
        }
    }

    private function buildPagesIndexUrl(string $status = 'all'): string
    {
        $baseUrl = url('/admin/pages');
        if ($status === '' || $status === 'all') {
            return $baseUrl;
        }

        return $baseUrl . '?status=' . urlencode($status);
    }

    /**
     * Builder pages remain outside the multilingual authoring scope for now.
     *
     * @param array<string, mixed> $page
     */
    private function supportsTranslationsForPage(array $page): bool
    {
        return $this->groupsTranslationsForPage($page);
    }

    /**
     * @param array<string, mixed> $page
     */
    private function groupsTranslationsForPage(array $page): bool
    {
        return trim((string) ($page['translation_group'] ?? $page['id'] ?? '')) !== '';
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

    private function redirectToPagesAdminOverride(string $action, string $id = '', string $locale = ''): bool
    {
        $results = hook_run('pages.admin.route_override', [
            'action' => $action,
            'id' => $id,
            'locale' => $locale,
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
