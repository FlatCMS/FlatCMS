<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\Studio\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Core\ModuleManager;
use App\Extensions\Studio\Services\StudioPageSourceService;
use App\Extensions\Studio\Services\StudioPreviewService;
use App\Extensions\Studio\Services\StudioSchemaService;
use App\Extensions\Studio\Services\StudioStorageService;
use App\Extensions\Studio\Services\StudioStructureImportService;
use RuntimeException;
use Throwable;

final class StudioController extends BaseController
{
    private StudioSchemaService $schema;
    private StudioStorageService $storage;
    private StudioPageSourceService $sourcePages;
    private StudioPreviewService $preview;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Studio');

        $this->schema = new StudioSchemaService();
        $this->storage = new StudioStorageService($this->schema, new StudioStructureImportService());
        $this->sourcePages = new StudioPageSourceService();
        $this->preview = new StudioPreviewService();
    }

    public function index(): void
    {
        if (!$this->authorize('studio.view')) {
            return;
        }

        $mediaEnabled = $this->isMediaEnabled();
        if ($mediaEnabled) {
            I18n::load('Media');
        }

        $sourcePage = $this->resolveActiveSourcePage();
        $page = $sourcePage !== null
            ? $this->storage->loadPageForSource($sourcePage)
            : $this->schema->defaultPage();

        $headStyleUrls = [
            asset('dists/fontawesome/css/all.min.css'),
            asset('css/admin/base.css'),
        ];
        $scriptUrls = [
            theme_asset('js/admin.js', 'admin'),
            asset('js/admin/flatcms-ui-primitives.js'),
        ];
        if ($mediaEnabled) {
            $scriptUrls[] = module_asset('Media', 'js/media-modal.js');
        }

        $scriptUrls = array_merge($scriptUrls, [
            module_asset('Studio', 'js/studio-core.js'),
            module_asset('Studio', 'js/studio-state.js'),
            module_asset('Studio', 'js/studio-api.js'),
            module_asset('Studio', 'js/studio-nav.js'),
            module_asset('Studio', 'js/studio-render.js'),
            module_asset('Studio', 'js/studio-dnd.js'),
            module_asset('Studio', 'js/studio-app.js'),
        ]);

        $this->render('Studio/Views/admin/index', [
            'pageTitle' => __('studio_title', 'Studio'),
            'boot' => $this->bootPayload($page, $sourcePage),
            'stylesUrl' => module_asset('Studio', 'css/studio.css'),
            'headStyleUrls' => $headStyleUrls,
            'scriptUrls' => $scriptUrls,
            'mediaEnabled' => $mediaEnabled,
        ]);
    }

    public function data(): void
    {
        if (!$this->authorize('studio.view')) {
            return;
        }

        $sourcePage = $this->resolveActiveSourcePage();
        $page = $sourcePage !== null
            ? $this->storage->loadPageForSource($sourcePage)
            : $this->schema->defaultPage();

        $this->json([
            'ok' => true,
            'page' => $page,
            'sources' => $this->sourcePages->listPages(I18n::getLocale()),
            'currentSource' => $this->sourceOptionForPage($sourcePage),
            'ui' => $this->schema->ui(),
            'library' => $this->schema->library(),
        ]);
    }

    public function save(): void
    {
        if (!$this->authorize('studio.edit')) {
            return;
        }

        $payload = $this->request->json();
        if (!is_array($payload)) {
            $this->json([
                'ok' => false,
                'message' => __('studio_error_invalid_payload', 'Studio'),
            ], 400);
            return;
        }

        if (!$this->verifyApiCsrf($payload)) {
            return;
        }

        $pagePayload = $payload['page'] ?? null;
        if (!is_array($pagePayload)) {
            $this->json([
                'ok' => false,
                'message' => __('studio_error_missing_page', 'Studio'),
            ], 400);
            return;
        }

        $sourcePage = $this->resolveSourcePageForPayload($pagePayload);
        if ($sourcePage === null) {
            $this->json([
                'ok' => false,
                'message' => __('studio_error_missing_page', 'Studio'),
            ], 400);
            return;
        }

        try {
            $page = $this->storage->savePageForSource($sourcePage, $pagePayload);
        } catch (RuntimeException $e) {
            error_log('[Studio][save] ' . $e->getMessage());
            $this->json([
                'ok' => false,
                'message' => __('studio_error_save_failed', 'Studio'),
            ], 500);
            return;
        } catch (Throwable $e) {
            error_log('[Studio][save] ' . $e->getMessage());
            $this->json([
                'ok' => false,
                'message' => __('studio_error_unexpected', 'Studio'),
            ], 500);
            return;
        }

        $this->json([
            'ok' => true,
            'message' => __('studio_saved', 'Studio'),
            'page' => $page,
            'currentSource' => $this->sourceOptionForPage($sourcePage),
        ]);
    }

    public function preview(): void
    {
        if (!$this->authorize('studio.view')) {
            return;
        }

        $sourcePage = $this->resolveActiveSourcePage();
        if ($sourcePage === null) {
            $this->redirect(url('/admin/studio'));
            return;
        }

        $page = $this->storage->loadPageForSource($sourcePage);
        $token = $this->preview->store($sourcePage, $page);
        if ($token === '') {
            $this->redirect($this->sourcePages->studioUrlForPage($sourcePage));
            return;
        }

        $path = $this->sourcePages->buildFrontendPath($sourcePage);
        $query = ['studio_preview' => $token];
        $nav = $this->request->input('nav');
        if (is_scalar($nav)) {
            $navValue = trim((string) $nav);
            if ($navValue !== '') {
                $query['studio_nav'] = $navValue;
            }
        }

        $separator = str_contains($path, '?') ? '&' : '?';
        $this->redirect(url($path . $separator . http_build_query($query)));
    }

    public function previewUrl(): void
    {
        if (!$this->authorize('studio.edit')) {
            return;
        }

        $payload = $this->request->json();
        if (!is_array($payload)) {
            $this->json([
                'ok' => false,
                'message' => __('studio_error_invalid_payload', 'Studio'),
            ], 400);
            return;
        }

        if (!$this->verifyApiCsrf($payload)) {
            return;
        }

        $pagePayload = $payload['page'] ?? null;
        if (!is_array($pagePayload)) {
            $this->json([
                'ok' => false,
                'message' => __('studio_error_missing_page', 'Studio'),
            ], 400);
            return;
        }

        $sourcePage = $this->resolveSourcePageForPayload($pagePayload);
        if ($sourcePage === null) {
            $this->json([
                'ok' => false,
                'message' => __('studio_error_missing_page', 'Studio'),
            ], 400);
            return;
        }

        try {
            $token = $this->preview->store($sourcePage, $pagePayload);
        } catch (Throwable $e) {
            error_log('[Studio][previewUrl] ' . $e->getMessage());
            $this->json([
                'ok' => false,
                'message' => __('studio_error_unexpected', 'Studio'),
            ], 500);
            return;
        }

        if ($token === '') {
            $this->json([
                'ok' => false,
                'message' => __('studio_error_missing_page', 'Studio'),
            ], 400);
            return;
        }

        $query = ['studio_preview' => $token];
        $navIndex = $payload['nav_index'] ?? null;
        if (is_scalar($navIndex)) {
            $navValue = trim((string) $navIndex);
            if ($navValue !== '') {
                $query['studio_nav'] = $navValue;
            }
        }

        $path = $this->sourcePages->buildFrontendPath($sourcePage);
        $separator = str_contains($path, '?') ? '&' : '?';

        $this->json([
            'ok' => true,
            'url' => url($path . $separator . http_build_query($query)),
        ]);
    }

    private function bootPayload(array $page, ?array $sourcePage): array
    {
        $currentSource = $this->sourceOptionForPage($sourcePage);

        return [
            'config' => [
                'token' => $this->csrfToken(),
                'locale' => I18n::getLocale(),
                'backUrl' => url('/admin'),
                'dataUrl' => $currentSource !== null ? url('/admin/studio/data?page=' . rawurlencode((string) ($currentSource['id'] ?? ''))) : url('/admin/studio/data'),
                'saveUrl' => url('/admin/studio/save'),
                'previewRenderUrl' => url('/admin/studio/preview-url'),
                'previewUrl' => $currentSource['preview_url'] ?? url('/admin/studio/preview'),
                'media' => $this->mediaConfig(),
            ],
            'page' => $page,
            'sources' => $this->sourcePages->listPages(I18n::getLocale()),
            'currentSource' => $currentSource,
            'ui' => $this->schema->ui(),
            'library' => $this->schema->library(),
        ];
    }

    private function verifyApiCsrf(array $payload): bool
    {
        $token = trim((string) ($this->request->header('X-CSRF-TOKEN', '') ?: ($payload['_token'] ?? '')));
        if ($token === '' || !$this->session->verifyToken($token)) {
            $this->json([
                'ok' => false,
                'message' => __('studio_error_invalid_token', 'Studio'),
            ], 419);
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveActiveSourcePage(): ?array
    {
        $requestedId = $this->request->input('page');
        return $this->sourcePages->resolveSelectedPage(is_scalar($requestedId) ? (string) $requestedId : null);
    }

    /**
     * @param array<string, mixed> $pagePayload
     * @return array<string, mixed>|null
     */
    private function resolveSourcePageForPayload(array $pagePayload): ?array
    {
        $requestedId = trim((string) ($pagePayload['source']['entity_id'] ?? $pagePayload['page']['id'] ?? ''));
        if ($requestedId === '') {
            $requestedInput = $this->request->input('page');
            $requestedId = is_scalar($requestedInput) ? trim((string) $requestedInput) : '';
        }

        return $this->sourcePages->resolveSelectedPage($requestedId !== '' ? $requestedId : null);
    }

    /**
     * @param array<string, mixed>|null $page
     * @return array<string, string>|null
     */
    private function sourceOptionForPage(?array $page): ?array
    {
        if (!is_array($page)) {
            return null;
        }

        $pageId = trim((string) ($page['id'] ?? ''));
        if ($pageId === '') {
            return null;
        }

        foreach ($this->sourcePages->listPages(I18n::getLocale()) as $option) {
            if ((string) ($option['id'] ?? '') === $pageId) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function mediaConfig(): array
    {
        if (!$this->isMediaEnabled()) {
            return [];
        }

        $uploadUrl = url('/admin/media/upload');
        $adminFront = strtok($uploadUrl, '?') ?: $uploadUrl;

        return [
            'apiImagesUrl' => $adminFront . '?path=admin/media/api/images',
            'apiFilesUrl' => $adminFront . '?path=admin/media/api/files',
            'uploadUrl' => $uploadUrl,
            'scriptUrl' => module_asset('Media', 'js/media-modal.js'),
            'uploadsBase' => url('/uploads'),
            'csrfToken' => $this->csrfToken(),
        ];
    }

    private function isMediaEnabled(): bool
    {
        return (new ModuleManager())->isEnabled('Media');
    }

}
