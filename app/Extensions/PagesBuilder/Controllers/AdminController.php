<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Controllers;

use App\Core\BaseController;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Core\ModuleManager;
use App\Extensions\PagesBuilder\Services\PageBuilderContactFormCatalogService;
use App\Extensions\PagesBuilder\Services\PageBuilderSetupService;
use App\Extensions\PagesBuilder\Services\PageBuilderStateService;
use App\Extensions\PagesBuilder\Services\PageBuilderWidgetRegistryService;
use App\Services\Licensing\ExtensionLicenseService;
use App\Modules\Auth\Services\LicenseVaultService;
use App\Modules\Auth\Services\RoleService;
use App\Modules\Pages\Services\PageTranslationService;
use App\Modules\Themes\Services\ThemeCustomizationService;
use App\Modules\Pages\Support\SystemPages;
use App\Modules\Trash\Services\TrashService;

final class AdminController extends BaseController
{
    private PageBuilderStateService $stateService;
    private PageBuilderSetupService $setupService;
    private ExtensionLicenseService $licenseService;
    private PageTranslationService $translations;
    private PageBuilderWidgetRegistryService $widgetRegistry;

    public function __construct()
    {
        parent::__construct();
        I18n::load('PagesBuilder');
        I18n::load('Pages');
        I18n::load('Menu');
        $this->stateService = new PageBuilderStateService();
        $this->setupService = new PageBuilderSetupService();
        $this->licenseService = new ExtensionLicenseService();
        $this->translations = new PageTranslationService();
        $this->widgetRegistry = new PageBuilderWidgetRegistryService();
    }

    public function index(): void
    {
        if (!$this->authorizePagesBuilderView()) {
            return;
        }

        if (!$this->setupService->isReady()) {
            $this->redirect(url('/admin/pages-builder/setup'));
            return;
        }

        $license = $this->licenseProfile();

        $this->render('PagesBuilder/Views/admin/index', [
            'pageTitle' => __('pages_builder_title', 'PagesBuilder'),
            'pagesBuilderRows' => $this->buildPagesBuilderRows(),
            'pagesBuilderLicense' => $license,
            'pagesBuilderCanEdit' => $this->canEditPagesBuilder(),
            'trashEnabled' => $this->isTrashEnabled(),
            'trashCount' => $this->getTrashCount(),
        ], 'admin.main');
    }

    public function setup(): void
    {
        if (!$this->authorizePagesBuilderView()) {
            return;
        }

        $license = $this->licenseProfile();

        $this->render('PagesBuilder/Views/admin/setup', [
            'pageTitle' => __('pages_builder_setup_title', 'PagesBuilder'),
            'pagesBuilderLicense' => $license,
            'pagesBuilderCanEdit' => $this->canEditPagesBuilder(),
            'pagesBuilderSetupState' => $this->setupService->state(),
        ], 'admin.main');
    }

    public function initialize(): void
    {
        if (!$this->authorizePagesBuilderEdit()) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $action = trim((string) $this->request->input('setup_action', 'convert'));
        if ($action === 'empty') {
            $this->setupService->initializeEmpty(auth());
        } else {
            $this->setupService->initializeByConvertingExistingPages(auth());
        }

        $this->session->flash('success', __('pages_builder_setup_success', 'PagesBuilder'));
        $this->redirect(url('/admin/pages-builder'));
    }

    public function create(): void
    {
        if (!$this->authorizePagesBuilderEdit()) {
            return;
        }

        if (!$this->setupService->isReady()) {
            $this->redirect(url('/admin/pages-builder/setup'));
            return;
        }

        $activeLocale = $this->resolveRequestedBuilderLocale();
        $created = $this->createBuilderDraft($activeLocale);
        if (!is_array($created)) {
            $this->session->flash('error', __('builder_save_error', 'PagesBuilder'));
            $this->redirect(url('/admin/pages-builder'));
            return;
        }

        $pageId = trim((string) ($created['id'] ?? ''));
        $this->redirect(url('/admin/pages-builder/' . rawurlencode($pageId) . '?locale=' . rawurlencode($activeLocale) . '&builder_context=create'));
    }

    public function edit(string $id): void
    {
        if (!$this->authorizePagesBuilderView()) {
            return;
        }

        if (!$this->setupService->isReady()) {
            $this->redirect(url('/admin/pages-builder/setup'));
            return;
        }

        $context = $this->resolveBuilderPageContext($id);
        if (!is_array($context)) {
            $this->session->flash('error', __('pages_builder_page_not_found', 'PagesBuilder'));
            $this->redirect(url('/admin/pages-builder'));
            return;
        }

        $page = $context['active_page'] ?? null;
        $sourcePage = $context['source_page'] ?? null;
        $routeId = trim((string) ($context['route_id'] ?? $id));
        $activeLocale = (string) ($context['active_locale'] ?? '');
        $sourceLocale = (string) ($context['source_locale'] ?? '');
        $translationUi = is_array($context['translation_ui'] ?? null) ? $context['translation_ui'] : [];

        if (!is_array($page) || !is_array($sourcePage)) {
            $this->session->flash('error', __('pages_builder_page_not_found', 'PagesBuilder'));
            $this->redirect(url('/admin/pages-builder'));
            return;
        }

        $state = $this->resolveEditorState($page, $sourcePage);
        $license = $this->licenseProfile();

        $this->render('PagesBuilder/Views/admin/edit', [
            'pageTitle' => $this->buildBuilderEditTitle($activeLocale),
            'page' => $page,
            'builderConfigJson' => $this->buildBuilderConfigJson($page, $sourcePage, $state, $routeId, $activeLocale, $sourceLocale),
            'widgetPreviewAssets' => $this->widgetPreviewAssets(),
            'builderPreviewThemeCssUrl' => $this->buildBuilderPreviewThemeCssUrl(),
            'translationUi' => $translationUi,
            'activeLocale' => $activeLocale,
            'sourceLocale' => $sourceLocale,
            'standardEditUrl' => url('/admin/pages-builder'),
            'previewUrl' => '',
            'publishUrl' => $this->publishUrl($routeId, $page, $activeLocale, $sourceLocale),
            'builderLicense' => $license,
            'builderLicenseSummary' => is_array($license['license_summary'] ?? null) ? $license['license_summary'] : [],
            'builderLicensed' => (string) ($license['status'] ?? '') === 'active',
            'isLocalHost' => is_local_host(),
        ], 'admin.main');
    }

    public function updateLicense(string $id): void
    {
        if (!$this->authorizePagesBuilderEdit()) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $context = $this->resolveBuilderPageContext($id);
        if (!is_array($context)) {
            $this->session->flash('error', __('pages_builder_page_not_found', 'PagesBuilder'));
            $this->redirect(url('/admin/pages-builder'));
            return;
        }

        $vault = new LicenseVaultService();
        $currentUserId = (string) (auth()['id'] ?? '');
        $licenseKey = trim((string) $this->request->input('pages_builder_license_key', ''));

        if ($licenseKey !== '') {
            $vault->storeModuleLicense(
                'PagesBuilder',
                $licenseKey,
                normalize_host((string) ($_SERVER['HTTP_HOST'] ?? '')),
                'active',
                date('Y-m-d H:i:s'),
                $currentUserId
            );
        } elseif ($this->request->has('pages_builder_license_key')) {
            $vault->clearModuleLicense('PagesBuilder');
        }

        $activeLocale = $this->resolveRequestedBuilderLocale((string) ($context['active_locale'] ?? ''));
        $redirectUrl = url('/admin/pages-builder/' . rawurlencode($id));
        if ($activeLocale !== '') {
            $redirectUrl .= '?locale=' . rawurlencode($activeLocale);
        }

        $this->session->flash('success', __('builder_license_saved', 'PagesBuilder'));
        $this->redirect($redirectUrl);
    }

    public function update(string $id): void
    {
        if (!$this->authorizePagesBuilderEdit()) {
            return;
        }

        if (!$this->setupService->isReady()) {
            $this->json([
                'success' => false,
                'message' => __('pages_builder_setup_required', 'PagesBuilder'),
            ], 409);
            return;
        }

        if (!$this->verifyApiCsrf()) {
            $this->json([
                'success' => false,
                'message' => __('error.csrf', 'Core'),
            ], 419);
            return;
        }

        $context = $this->resolveBuilderPageContext($id);
        if (!is_array($context)) {
            $this->json([
                'success' => false,
                'message' => __('pages_builder_page_not_found', 'PagesBuilder'),
            ], 404);
            return;
        }

        $payload = $this->request->isJson() ? $this->request->json() : $this->request->all();
        $sourcePage = $context['source_page'] ?? null;
        $activePage = $context['active_page'] ?? null;
        $activeLocale = $this->translations->normalizeLocale((string) ($payload['locale'] ?? ($context['active_locale'] ?? '')));
        $sourceLocale = (string) ($context['source_locale'] ?? '');
        $translationGroup = trim((string) ($context['translation_group'] ?? ''));

        if (!is_array($sourcePage)) {
            $this->json([
                'success' => false,
                'message' => __('pages_builder_page_not_found', 'PagesBuilder'),
            ], 404);
            return;
        }

        if ($activeLocale === '') {
            $activeLocale = $sourceLocale !== '' ? $sourceLocale : $this->translations->defaultLocale();
        }
        if ($translationGroup === '') {
            $translationGroup = trim((string) ($sourcePage['translation_group'] ?? $sourcePage['id'] ?? $id));
        }

        $pageTitle = trim((string) ($payload['title'] ?? ($activePage['title'] ?? $sourcePage['title'] ?? '')));
        if ($pageTitle === '') {
            $this->json([
                'success' => false,
                'message' => __('title_required', 'Pages'),
            ], 422);
            return;
        }

        $requestedSlug = trim((string) ($payload['slug'] ?? ''));
        if ($requestedSlug === '') {
            $requestedSlug = str_slug($pageTitle);
        }
        if ($requestedSlug === '') {
            $requestedSlug = 'page';
        }

        $activePageId = trim((string) ($activePage['id'] ?? ''));
        $pageSlug = $this->translations->resolveUniqueSlug($requestedSlug, $activeLocale, $activePageId !== '' ? $activePageId : null);
        if ($pageSlug === '') {
            $pageSlug = $requestedSlug;
        }

        $builder = $this->normalizeBuilderPayload($payload['builder'] ?? null);
        $metaTitle = trim((string) ($payload['meta_title'] ?? ''));
        $metaDescription = trim((string) ($payload['meta_description'] ?? ''));

        $savedPage = $this->persistCanonicalPage(
            $sourcePage,
            is_array($activePage) ? $activePage : null,
            $translationGroup,
            $sourceLocale,
            $activeLocale,
            $pageTitle,
            $pageSlug,
            $metaTitle,
            $metaDescription
        );

        if (!is_array($savedPage)) {
            $this->json([
                'success' => false,
                'message' => __('builder_save_error', 'PagesBuilder'),
            ], 500);
            return;
        }

        $savedState = $this->stateService->saveStateForPage($savedPage, [
            'active' => true,
            'builder' => $builder,
        ], auth());

        $this->json([
            'success' => true,
            'message' => __('builder_saved', 'PagesBuilder'),
            'page' => [
                'id' => (string) ($savedPage['id'] ?? ''),
                'title' => (string) ($savedPage['title'] ?? $pageTitle),
                'slug' => (string) ($savedPage['slug'] ?? $pageSlug),
                'meta_title' => (string) ($savedPage['meta_title'] ?? $metaTitle),
                'meta_description' => (string) ($savedPage['meta_description'] ?? $metaDescription),
                'status' => (string) ($savedPage['status'] ?? 'draft'),
                'locale' => (string) ($savedPage['locale'] ?? $activeLocale),
                'builder_active' => !empty($savedState['active']),
            ],
        ]);
    }

    public function publish(string $id): void
    {
        if (!$this->authorize('pagesbuilder.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        if (!$this->setupService->isReady()) {
            $this->redirect(url('/admin/pages-builder/setup'));
            return;
        }

        $context = $this->resolveBuilderPageContext($id);
        if (!is_array($context)) {
            $this->session->flash('error', __('pages_builder_page_not_found', 'PagesBuilder'));
            $this->redirect(url('/admin/pages-builder'));
            return;
        }

        $sourcePage = $context['source_page'] ?? null;
        $translationGroup = trim((string) ($context['translation_group'] ?? ''));
        $activeLocale = (string) ($context['active_locale'] ?? '');
        $routeId = trim((string) ($context['route_id'] ?? $id));

        if (!is_array($sourcePage) || trim((string) ($sourcePage['id'] ?? '')) === '') {
            $this->session->flash('error', __('pages_builder_page_not_found', 'PagesBuilder'));
            $this->redirect(url('/admin/pages-builder'));
            return;
        }

        $pages = FlatFile::for('core/pages');
        $sourceId = (string) ($sourcePage['id'] ?? '');
        $payload = array_merge($sourcePage, ['status' => 'published']);
        hook_run('pages.before_save', $payload);
        $updated = $pages->update($sourceId, ['status' => 'published']);
        if (is_array($updated)) {
            hook_run('pages.after_save', $updated);
        }

        if ($translationGroup !== '') {
            foreach ($this->translations->getTranslations($translationGroup, false) as $translation) {
                $translationId = trim((string) ($translation['id'] ?? ''));
                if ($translationId === '' || $translationId === $sourceId) {
                    continue;
                }
                $pages->update($translationId, ['status' => 'published']);
            }
        }

        $this->session->flash('success', __('builder_publish_success', 'PagesBuilder'));
        $this->redirect(url('/admin/pages-builder/' . rawurlencode($routeId) . '?locale=' . rawurlencode($activeLocale)));
    }

    public function batch(): void
    {
        $redirectUrl = url('/admin/pages-builder');
        if (!can('pages.delete') && !can('pages.delete_own')) {
            $this->session->flash('error', __('error.unauthorized', 'Core'));
            $this->redirect($redirectUrl);
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        if (!$this->setupService->isReady()) {
            $this->redirect(url('/admin/pages-builder/setup'));
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

        $pages = FlatFile::for('core/pages');
        $states = FlatFile::for('extensions/pages-builder/pages');
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
            if ($hasSystemPage || !$this->canDeletePageGroup($groupPages)) {
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
                    $groupId = trim((string) ($groupPage['id'] ?? ''));
                    if ($groupId === '' || !$pages->delete($groupId)) {
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
                $groupId = trim((string) ($groupPage['id'] ?? ''));
                if ($groupId === '' || !$pages->delete($groupId)) {
                    $deleteFailed = true;
                    break;
                }
                $states->delete($groupId);
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

    /**
     * @return array<string, mixed>
     */
    private function licenseProfile(): array
    {
        $profile = $this->licenseService->describe('PagesBuilder');

        return is_array($profile) ? $profile : [
            'status' => 'missing',
            'authoring_enabled' => false,
        ];
    }

    private function authorizePagesBuilderView(): bool
    {
        return $this->authorizeAnyPermission(['pagesbuilder.view', 'pages.view']);
    }

    private function authorizePagesBuilderEdit(): bool
    {
        return $this->authorizeAnyPermission(['pagesbuilder.edit', 'pages.edit']);
    }

    private function canEditPagesBuilder(): bool
    {
        return can('pagesbuilder.edit') || can('pages.edit') || can('pages.create');
    }

    /**
     * @param array<int,string> $permissions
     */
    private function authorizeAnyPermission(array $permissions): bool
    {
        $user = $this->session->get('user');
        if (!$user) {
            $this->redirect(url('/login'));
            return false;
        }

        $role = (string) ($user['role'] ?? RoleService::ROLE_MEMBER);
        foreach ($permissions as $permission) {
            if (RoleService::hasPermission($role, $permission)) {
                return true;
            }
        }

        $this->session->flash('error', __('error.unauthorized', 'Core'));
        if (RoleService::hasPermission($role, 'profile.view')) {
            $this->redirect(url(RoleService::getLoginRedirect($role)));
            return false;
        }

        if (RoleService::canAccessAdmin($role)) {
            $this->redirect(url('/admin'));
            return false;
        }

        $this->redirect(url('/'));
        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildPagesBuilderRows(): array
    {
        $rows = $this->stateService->listPageSummaries();
        $dateFormat = $this->pagesBuilderDateTimeFormat();
        $activeLocale = locale();

        foreach ($rows as &$row) {
            if (!is_array($row)) {
                continue;
            }

            $status = trim((string) ($row['status'] ?? 'draft'));
            $updatedAt = (string) (($row['builder_updated_at'] ?? '') !== '' ? $row['builder_updated_at'] : ($row['page_updated_at'] ?? ''));

            $row['status_label'] = $this->translatePageStatus($status);
            $row['updated_at_label'] = $updatedAt !== '' ? human_date($updatedAt, $dateFormat, $activeLocale) : '';

            $pageId = trim((string) ($row['id'] ?? ''));
            $sourcePage = $pageId !== '' ? $this->translations->find($pageId) : null;
            $groupPages = $this->resolveDeletionGroup(is_array($sourcePage) ? $sourcePage : null, $pageId);
            $hasRequiredPage = false;
            foreach ($groupPages as $groupPage) {
                if (SystemPages::isRequiredPage($groupPage)) {
                    $hasRequiredPage = true;
                    break;
                }
            }

            $row['system_required'] = !empty($row['system_required']) || $hasRequiredPage;
            $row['can_delete'] = !$row['system_required'] && $this->canDeletePageGroup($groupPages);
            $row['translations_available'] = $this->buildTranslationFlags(
                $groupPages,
                (string) ($row['source_locale'] ?? $row['locale'] ?? '')
            );
        }
        unset($row);

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

    private function translatePageStatus(string $status): string
    {
        $normalizedStatus = $status !== '' ? $status : 'draft';
        $translationKey = 'status_' . $normalizedStatus;
        $label = __($translationKey, 'Pages');

        if ($label === $translationKey) {
            return ucfirst($normalizedStatus);
        }

        return $label;
    }

    private function pagesBuilderDateTimeFormat(): string
    {
        return locale() === 'en-US' ? 'm/d/Y H:i:s' : 'd/m/Y H:i:s';
    }

    private function resolveRequestedBuilderLocale(string $fallback = ''): string
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
     * @return array<string, mixed>|null
     */
    private function resolveBuilderPageContext(string $id): ?array
    {
        $routePage = $this->translations->find($id);
        if (!is_array($routePage)) {
            return null;
        }

        $translationGroup = trim((string) ($routePage['translation_group'] ?? $routePage['id'] ?? ''));
        if ($translationGroup === '') {
            $translationGroup = trim((string) ($routePage['id'] ?? ''));
        }

        $sourcePage = $translationGroup !== ''
            ? $this->translations->resolveSourcePage($translationGroup)
            : null;
        if (!is_array($sourcePage)) {
            $sourcePage = $this->translations->normalizePage($routePage);
        }

        $sourceLocale = $this->translations->normalizeLocale((string) ($sourcePage['source_locale'] ?? $sourcePage['locale'] ?? ''));
        if ($sourceLocale === '') {
            $sourceLocale = $this->translations->defaultLocale();
        }

        $activeLocale = $this->resolveRequestedBuilderLocale((string) ($routePage['locale'] ?? $sourceLocale));
        $activePage = $translationGroup !== ''
            ? $this->translations->findByTranslationGroupAndLocale($translationGroup, $activeLocale, false)
            : null;
        if (!is_array($activePage) && (string) ($routePage['locale'] ?? '') === $activeLocale) {
            $activePage = $this->translations->normalizePage($routePage);
        }
        if (!is_array($activePage) && $activeLocale === $sourceLocale) {
            $activePage = $sourcePage;
        }
        if (!is_array($activePage)) {
            $activePage = $this->buildBuilderTranslationDraft($sourcePage, $activeLocale);
        }

        $routeId = trim((string) ($sourcePage['id'] ?? $routePage['id'] ?? $id));

        return [
            'route_page' => $routePage,
            'source_page' => $sourcePage,
            'active_page' => $activePage,
            'translation_group' => $translationGroup,
            'source_locale' => $sourceLocale,
            'active_locale' => $activeLocale,
            'route_id' => $routeId,
            'translation_ui' => $this->buildBuilderTranslationUi($sourcePage, $activeLocale, $sourceLocale),
        ];
    }

    /**
     * @param array<string, mixed> $sourcePage
     * @return array<int, array<string, mixed>>
     */
    private function buildBuilderTranslationUi(array $sourcePage, string $activeLocale, string $sourceLocale): array
    {
        $translationGroup = trim((string) ($sourcePage['translation_group'] ?? $sourcePage['id'] ?? ''));
        $routeId = trim((string) ($sourcePage['id'] ?? ''));
        $translations = $translationGroup !== ''
            ? $this->translations->getTranslations($translationGroup, false)
            : [];
        $tabs = [];

        foreach ($this->translations->supportedLocales() as $locale) {
            $translation = $translations[$locale] ?? null;
            $exists = is_array($translation);
            $state = $locale === $sourceLocale ? 'source' : ($exists ? 'ready' : 'missing');
            $tabs[] = [
                'code' => $locale,
                'label' => $this->translations->getLocaleLabel($locale, I18n::getLocale()),
                'exists' => $exists,
                'is_active' => $locale === $activeLocale,
                'is_source' => $locale === $sourceLocale,
                'state' => $state,
                'badge_label' => $this->builderTranslationBadgeLabel($activeLocale, $state),
                'url' => url('/admin/pages-builder/' . $routeId . '?locale=' . rawurlencode($locale)),
            ];
        }

        return $tabs;
    }

    /**
     * @param array<string, mixed> $sourcePage
     * @return array<string, mixed>
     */
    private function buildBuilderTranslationDraft(array $sourcePage, string $locale): array
    {
        return array_merge(
            $sourcePage,
            $this->translations->buildTranslationSeed($locale, $sourcePage),
            [
                'id' => '',
                'title' => (string) ($sourcePage['title'] ?? ''),
                'slug' => (string) ($sourcePage['slug'] ?? ''),
                'content' => (string) ($sourcePage['content'] ?? ''),
            ]
        );
    }

    private function buildBuilderEditTitle(string $locale): string
    {
        $localeLabel = $this->translations->getLocaleLabel($locale, I18n::getLocale());
        if ($localeLabel === '') {
            return __('builder_title', 'PagesBuilder');
        }

        return __('edit_page_in_locale', 'Pages', ['locale' => $localeLabel]) . ' · ' . __('builder_title', 'PagesBuilder');
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

        $translationGroup = trim((string) ($page['translation_group'] ?? $page['id'] ?? ''));
        if ($translationGroup === '') {
            return [$page];
        }

        $translations = $this->translations->getTranslations($translationGroup, false);
        if ($translations === []) {
            return [$page];
        }

        return array_values($translations);
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

    /**
     * @param array<string, mixed>|null $page
     */
    private function userCanDeletePage(?array $page): bool
    {
        $user = $this->session->get('user');
        if (!is_array($user)) {
            return false;
        }

        $role = (string) ($user['role'] ?? RoleService::ROLE_MEMBER);
        if (RoleService::hasPermission($role, 'pages.delete')) {
            return true;
        }

        $userId = trim((string) ($user['id'] ?? ''));
        $authorId = trim((string) (($page['author_id'] ?? '') ?: ''));
        $canOwnDelete = RoleService::hasPermission($role, 'pages.delete_own');

        return $canOwnDelete && $userId !== '' && $authorId !== '' && $userId === $authorId;
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

    /**
     * @return array<string, mixed>|null
     */
    private function createBuilderDraft(string $locale): ?array
    {
        $title = $this->builderDefaultDraftTitle($locale);
        $slugCandidate = str_slug($title);
        if ($slugCandidate === '') {
            $slugCandidate = 'page';
        }

        $pageSlug = $this->translations->resolveUniqueSlug($slugCandidate, $locale);
        if ($pageSlug === '') {
            $pageSlug = $slugCandidate;
        }

        $pages = FlatFile::for('core/pages');
        $createPayload = [
            'title' => $title,
            'slug' => $pageSlug,
            'content' => '',
            'meta_title' => $title,
            'meta_description' => '',
            'status' => 'draft',
            'translation_group' => '',
            'locale' => $locale,
            'source_locale' => $locale,
            'editor_mode' => 'builder',
            'render_mode' => 'classic',
            'author_id' => trim((string) (auth()['id'] ?? '')),
        ];

        hook_run('pages.before_save', $createPayload);
        $created = $pages->create($createPayload);
        if (!is_array($created)) {
            return null;
        }

        $pageId = trim((string) ($created['id'] ?? ''));
        $created = $pages->update($pageId, [
            'translation_group' => $pageId,
            'editor_mode' => 'builder',
            'render_mode' => 'classic',
        ]) ?? array_merge($created, [
            'translation_group' => $pageId,
            'editor_mode' => 'builder',
            'render_mode' => 'classic',
        ]);
        hook_run('pages.after_save', $created);

        $this->stateService->saveStateForPage($created, [
            'active' => true,
            'builder' => $this->stateService->emptyBuilder(),
        ], auth());

        return $created;
    }

    private function builderDefaultDraftTitle(string $locale): string
    {
        $catalog = $this->builderLocaleCatalog($locale);
        $value = $catalog['create_page'] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return __('create_page', 'Pages');
    }

    private function builderTranslationBadgeLabel(string $uiLocale, string $state): string
    {
        $catalog = $this->builderLocaleCatalog($uiLocale);
        $key = match ($state) {
            'source' => 'translation_source',
            'ready' => 'translation_ready',
            default => 'translation_missing',
        };
        $fallback = __($key, 'Pages');
        $value = $catalog[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private function builderLocaleCatalog(string $locale): array
    {
        $resolvedLocale = $this->translations->normalizeLocale($locale);
        if ($resolvedLocale === '') {
            $resolvedLocale = $this->translations->defaultLocale();
        }

        $path = I18n::resolveTranslationPathForNamespace('Pages', $resolvedLocale);
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $sourcePage
     * @return array<string, mixed>
     */
    private function resolveEditorState(array $page, array $sourcePage): array
    {
        $state = $this->stateService->stateForPage($page);
        if ($this->stateHasRenderableBuilder($state)) {
            return $state;
        }

        $sourceState = $this->stateService->stateForPage($sourcePage);
        if ($this->stateHasRenderableBuilder($sourceState)) {
            $state['builder'] = $sourceState['builder'];
            $state['builder_version'] = $sourceState['builder_version'] ?? 2;
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function stateHasRenderableBuilder(array $state): bool
    {
        $builder = $state['builder'] ?? null;
        if (!is_array($builder)) {
            return false;
        }

        $sections = $builder['sections'] ?? [];
        return is_array($sections) && $sections !== [];
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $sourcePage
     * @param array<string, mixed> $state
     */
    private function buildBuilderConfigJson(array $page, array $sourcePage, array $state, string $routeId, string $activeLocale, string $sourceLocale): string
    {
        $mediaUploadUrl = url('/admin/media/upload');
        $mediaAdminFront = strtok($mediaUploadUrl, '?') ?: $mediaUploadUrl;
        $mediaApiImagesUrl = $mediaAdminFront . '?path=admin/media/api/images';
        $mediaApiFilesUrl = $mediaAdminFront . '?path=admin/media/api/files';
        $builder = is_array($state['builder'] ?? null) ? $state['builder'] : $this->stateService->emptyBuilder();
        $formCatalog = new PageBuilderContactFormCatalogService();

        $config = [
            'pageId' => trim((string) ($page['id'] ?? '')) !== '' ? (string) ($page['id'] ?? '') : $routeId,
            'pageTitle' => (string) ($page['title'] ?? $sourcePage['title'] ?? ''),
            'pageSlug' => (string) ($page['slug'] ?? $sourcePage['slug'] ?? ''),
            'activeLocale' => $activeLocale,
            'sourceLocale' => $sourceLocale,
            'csrfToken' => csrf_token(),
            'saveUrl' => url('/admin/pages-builder/' . rawurlencode($routeId)),
            'iconsEndpoint' => url('/admin/menus/icons'),
            'builder' => $builder,
            'availableItems' => [],
            'contactForms' => $formCatalog->builderConfigForms(),
            'newsletterForms' => $formCatalog->builderConfigForms(PageBuilderContactFormCatalogService::SCOPE_NEWSLETTER),
            'contactFormsAdminUrl' => url('/admin/contact/forms'),
            'widgetDefs' => $this->builderWidgetDefs(),
            'lockedWidgetDefs' => $this->builderLockedWidgetDefs(),
            'media' => [
                'apiImagesUrl' => $mediaApiImagesUrl,
                'apiFilesUrl' => $mediaApiFilesUrl,
                'uploadUrl' => $mediaUploadUrl,
                'uploadsBase' => url('/uploads'),
                'csrfToken' => csrf_token(),
            ],
            'labels' => $this->buildEditorLabels(),
            'localeCatalog' => $this->loadPagesBuilderLocaleCatalog(),
        ];

        return htmlspecialchars((string) json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @return array{css: array<int, string>, js: array<int, string>}
     */
    private function widgetPreviewAssets(): array
    {
        return $this->widgetRegistry->previewAssets();
    }

    private function buildBuilderPreviewThemeCssUrl(): string
    {
        $settings = FlatFile::settings();
        $frontendTheme = trim((string) ($settings['frontend_theme'] ?? config('app.frontend_theme', 'default')));
        if ($frontendTheme === '') {
            return '';
        }

        $service = new ThemeCustomizationService();
        $runtimeCss = trim($service->buildRuntimeCss('frontend', $frontendTheme));
        if ($runtimeCss === '') {
            return '';
        }

        $scopedCss = trim($this->scopeThemeCustomizationCssForPreview($runtimeCss));
        if ($scopedCss === '') {
            return '';
        }

        return runtime_css_asset($scopedCss, 'pages-builder-preview-theme', $frontendTheme);
    }

    private function scopeThemeCustomizationCssForPreview(string $css): string
    {
        $source = trim($css);
        if ($source === '') {
            return '';
        }

        $blocks = [];

        if (preg_match('/:root\s*\{([^}]*)\}/s', $source, $matches) === 1) {
            $declarations = trim((string) ($matches[1] ?? ''));
            if ($declarations !== '') {
                $formTokens = implode('', [
                    '--fc-form-shell-bg:var(--color-bg-secondary);',
                    '--fc-form-shell-border:var(--color-border);',
                    '--fc-form-label-color:var(--color-text-secondary);',
                    '--fc-form-input-bg:var(--color-bg-primary);',
                    '--fc-form-input-border:var(--color-border);',
                    '--fc-form-input-color:var(--color-text-primary);',
                    '--fc-form-placeholder-color:var(--color-text-muted);',
                ]);
                if (!str_contains($declarations, '--fc-form-shell-bg:')) {
                    $declarations .= $formTokens;
                }
                $blocks[] = '.pb-block-preview {' . $declarations . '}';
            }
        }

        $source = preg_replace('/:root\s*\{[^}]*\}\s*/s', '', $source) ?? $source;

        foreach (preg_split('/}\s*/', $source) ?: [] as $rawRule) {
            $rule = trim((string) $rawRule);
            if ($rule === '' || !str_contains($rule, '{')) {
                continue;
            }

            [$rawSelectors, $rawDeclarations] = array_pad(explode('{', $rule, 2), 2, '');
            $selectorsText = trim($rawSelectors);
            $declarations = trim($rawDeclarations);
            if ($selectorsText === '' || $declarations === '') {
                continue;
            }

            if (str_contains($selectorsText, ':is(')
                || str_contains($selectorsText, 'site-header')
                || str_contains($selectorsText, 'site-footer')
                || str_contains($selectorsText, '::-webkit-scrollbar')
                || str_contains($selectorsText, 'body::before')
            ) {
                continue;
            }

            $selectors = [];
            foreach (explode(',', $selectorsText) as $selectorPart) {
                $selector = trim($selectorPart);
                if ($selector === '') {
                    continue;
                }

                if ($selector === 'body' || $selector === 'html') {
                    $selectors[] = '.pb-block-preview';
                    continue;
                }

                if ($selector === 'body.light-mode') {
                    $selectors[] = '.pb-editor-shell.theme-light-init .pb-block-preview';
                    continue;
                }

                if ($selector === 'html.theme-light-init body') {
                    $selectors[] = '.pb-editor-shell.theme-light-init .pb-block-preview';
                    continue;
                }

                if (str_starts_with($selector, 'body.light-mode ')) {
                    $selectors[] = '.pb-editor-shell.theme-light-init .pb-block-preview ' . ltrim(substr($selector, strlen('body.light-mode ')));
                    continue;
                }

                if (str_starts_with($selector, 'html.theme-light-init body ')) {
                    $selectors[] = '.pb-editor-shell.theme-light-init .pb-block-preview ' . ltrim(substr($selector, strlen('html.theme-light-init body ')));
                    continue;
                }

                $selectors[] = '.pb-block-preview ' . $selector;
            }

            if ($selectors === []) {
                continue;
            }

            $blocks[] = implode(', ', $selectors) . ' {' . $declarations . '}';
        }

        return implode("\n", $blocks);
    }

    /**
     * @return array<string, string>
     */
    private function buildEditorLabels(): array
    {
        return [
            'saveSuccess' => __('builder_saved', 'PagesBuilder'),
            'saveError' => __('builder_save_error', 'PagesBuilder'),
            'invalidConfig' => __('builder_invalid_config', 'PagesBuilder'),
            'mediaModalUnavailable' => __('builder_media_modal_unavailable', 'PagesBuilder'),
            'titleRequired' => __('title_required', 'Pages'),
            'saving' => __('builder_saving', 'PagesBuilder'),
            'statusBuilderMode' => __('builder_mode_pro', 'PagesBuilder'),
            'catContent' => __('builder_category_content', 'PagesBuilder'),
            'catMedia' => __('builder_category_media', 'PagesBuilder'),
            'catNavigation' => __('builder_category_navigation', 'PagesBuilder'),
            'catForms' => __('builder_category_forms', 'PagesBuilder'),
            'catLayout' => __('builder_category_layout', 'PagesBuilder'),
            'catAdvanced' => __('builder_category_advanced', 'PagesBuilder'),
            'chooseImage' => __('builder_choose_image', 'PagesBuilder'),
            'chooseMedia' => __('builder_choose_media', 'PagesBuilder'),
            'removeMedia' => __('builder_remove_media', 'PagesBuilder'),
            'removeMediaConfirm' => __('builder_remove_media_confirm', 'PagesBuilder'),
            'mediaRemoved' => __('builder_media_removed', 'PagesBuilder'),
            'clearColor' => __('builder_clear_color', 'PagesBuilder'),
            'sourceEmpty' => __('available_items_hint', 'Menu'),
            'close' => __('close', 'Core'),
            'add' => __('add', 'Core'),
            'widgetHero' => __('builder_widget_hero', 'PagesBuilder'),
            'widgetHeading' => __('builder_widget_heading', 'PagesBuilder'),
            'widgetText' => __('builder_widget_text', 'PagesBuilder'),
            'widgetImage' => __('builder_widget_image', 'PagesBuilder'),
            'widgetButton' => __('builder_widget_button', 'PagesBuilder'),
            'widgetSpacer' => __('builder_widget_spacer', 'PagesBuilder'),
            'widgetDivider' => __('builder_widget_divider', 'PagesBuilder'),
            'fieldText' => __('builder_field_text', 'PagesBuilder'),
            'fieldTag' => __('builder_field_tag', 'PagesBuilder'),
            'fieldAlign' => __('builder_field_align', 'PagesBuilder'),
            'fieldSource' => __('builder_field_source', 'PagesBuilder'),
            'fieldAltText' => __('builder_field_alt_text', 'PagesBuilder'),
            'fieldLabel' => __('builder_field_label', 'PagesBuilder'),
            'fieldUrl' => __('builder_field_url', 'PagesBuilder'),
            'fieldVariant' => __('builder_field_variant', 'PagesBuilder'),
            'fieldHeightPx' => __('builder_field_height_px', 'PagesBuilder'),
            'fieldWeightPx' => __('builder_field_weight_px', 'PagesBuilder'),
            'fieldColor' => __('builder_field_color', 'PagesBuilder'),
            'fieldWidthPercent' => __('builder_field_width_percent', 'PagesBuilder'),
            'optionAlignLeft' => __('builder_option_align_left', 'PagesBuilder'),
            'optionAlignCenter' => __('builder_option_align_center', 'PagesBuilder'),
            'optionAlignRight' => __('builder_option_align_right', 'PagesBuilder'),
            'optionTargetSelf' => __('builder_option_target_self', 'PagesBuilder'),
            'optionTargetBlank' => __('builder_option_target_blank', 'PagesBuilder'),
            'optionVariantPrimary' => __('builder_option_variant_primary', 'PagesBuilder'),
            'optionVariantSecondary' => __('builder_option_variant_secondary', 'PagesBuilder'),
            'optionVariantGhost' => __('builder_option_variant_ghost', 'PagesBuilder'),
            'hero_default_title' => __('builder_default_hero_title', 'PagesBuilder'),
            'hero_default_subtitle' => __('builder_default_hero_subtitle', 'PagesBuilder'),
            'hero_default_primary_label' => __('builder_default_hero_primary_label', 'PagesBuilder'),
            'hero_default_secondary_label' => __('builder_default_hero_secondary_label', 'PagesBuilder'),
            'hero_empty' => __('builder_widget_hero', 'PagesBuilder'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function loadPagesBuilderLocaleCatalog(): array
    {
        $path = BASE_PATH . '/app/Extensions/PagesBuilder/Languages/' . I18n::getLocale() . '.json';
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function builderWidgetDefs(): array
    {
        return $this->widgetRegistry->definitions();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function builderLockedWidgetDefs(): array
    {
        return $this->widgetRegistry->lockedDefinitions();
    }

    /**
     * @param array<string, mixed> $sourcePage
     * @param array<string, mixed>|null $activePage
     * @return array<string, mixed>|null
     */
    private function persistCanonicalPage(
        array $sourcePage,
        ?array $activePage,
        string $translationGroup,
        string $sourceLocale,
        string $activeLocale,
        string $pageTitle,
        string $pageSlug,
        string $metaTitle,
        string $metaDescription
    ): ?array {
        $pages = FlatFile::for('core/pages');
        $resolvedMetaTitle = $this->resolveBuilderMetaTitle($metaTitle, $pageTitle, $activePage, $sourcePage);
        $resolvedMetaDescription = $this->resolveBuilderMetaDescription($metaDescription, $activePage, $sourcePage);

        $update = [
            'title' => $pageTitle,
            'slug' => $pageSlug,
            'meta_title' => $resolvedMetaTitle,
            'meta_description' => $resolvedMetaDescription,
            'translation_group' => $translationGroup,
            'locale' => $activeLocale,
            'source_locale' => $sourceLocale,
        ];

        if (is_array($activePage) && trim((string) ($activePage['id'] ?? '')) !== '') {
            $activeId = (string) ($activePage['id'] ?? '');
            $hookPayload = array_merge($activePage, $update);
            hook_run('pages.before_save', $hookPayload);
            $updated = $pages->update($activeId, $update);
            if (!is_array($updated)) {
                return null;
            }
            hook_run('pages.after_save', $updated);
            return $updated;
        }

        $seed = $this->translations->buildTranslationSeed($activeLocale, $sourcePage);
        $createPayload = array_merge($seed, $update, [
            'content' => (string) ($sourcePage['content'] ?? ''),
            'author_id' => trim((string) (($sourcePage['author_id'] ?? '') ?: (auth()['id'] ?? ''))),
        ]);
        hook_run('pages.before_save', $createPayload);
        $created = $pages->create($createPayload);
        if (!is_array($created)) {
            return null;
        }
        hook_run('pages.after_save', $created);
        return $created;
    }

    /**
     * @param array<string, mixed>|null $activePage
     * @param array<string, mixed> $sourcePage
     */
    private function resolveBuilderMetaTitle(string $requestedMetaTitle, string $pageTitle, ?array $activePage, array $sourcePage): string
    {
        $requested = trim($requestedMetaTitle);
        if ($requested !== '') {
            return $requested;
        }

        $active = trim((string) ($activePage['meta_title'] ?? ''));
        if ($active !== '') {
            return $active;
        }

        $source = trim((string) ($sourcePage['meta_title'] ?? ''));
        if ($source !== '') {
            return $source;
        }

        return trim($pageTitle);
    }

    /**
     * @param array<string, mixed>|null $activePage
     * @param array<string, mixed> $sourcePage
     */
    private function resolveBuilderMetaDescription(string $requestedMetaDescription, ?array $activePage, array $sourcePage): string
    {
        $requested = trim($requestedMetaDescription);
        if ($requested !== '') {
            return $requested;
        }

        $active = trim((string) ($activePage['meta_description'] ?? ''));
        if ($active !== '') {
            return $active;
        }

        return trim((string) ($sourcePage['meta_description'] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeBuilderPayload(mixed $builder): array
    {
        if (!is_array($builder)) {
            return $this->stateService->emptyBuilder();
        }

        $sections = $builder['sections'] ?? [];
        if (!is_array($sections)) {
            $sections = [];
        }

        return [
            'version' => max(2, (int) ($builder['version'] ?? 2)),
            'sections' => array_values($sections),
        ];
    }

    private function verifyApiCsrf(): bool
    {
        $token = $this->request->header('X-CSRF-TOKEN');
        if (!is_string($token) || $token === '') {
            $token = (string) ($this->request->input('_token') ?? '');
        }

        return $token !== '' && $this->session->verifyToken($token);
    }

    /**
     * @param array<string, mixed> $page
     */
    private function publishUrl(string $routeId, array $page, string $activeLocale, string $sourceLocale): string
    {
        $status = (string) ($page['status'] ?? 'draft');
        if ($activeLocale !== $sourceLocale || $status === 'published') {
            return '';
        }

        return url('/admin/pages-builder/' . rawurlencode($routeId) . '/publish?locale=' . rawurlencode($activeLocale));
    }
}
