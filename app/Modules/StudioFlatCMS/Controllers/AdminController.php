<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\StudioFlatCMS\Controllers;

use App\Core\BaseController;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Core\ModuleManager;
use App\Modules\StudioFlatCMS\Services\StudioPageSourceService;
use App\Modules\StudioFlatCMS\Services\StudioSchemaService;
use App\Modules\StudioFlatCMS\Services\StudioStorageService;

final class AdminController extends BaseController
{
    private const DOCUMENT_ID = 'home';

    private StudioSchemaService $schema;
    private StudioStorageService $storage;
    private StudioPageSourceService $sourcePages;

    public function __construct()
    {
        parent::__construct();
        I18n::load('StudioFlatCMS');
        I18n::load('Pages');
        I18n::load('Media');

        $this->schema = new StudioSchemaService();
        $this->storage = new StudioStorageService($this->schema);
        $this->sourcePages = new StudioPageSourceService();
    }

    public function index(): void
    {
        if (!$this->authorize('studio-flatcms.view')) {
            return;
        }

        $settings = FlatFile::settings();
        $sourcePage = $this->resolveActiveSourcePage();
        $document = $this->storage->loadDocumentForSource($sourcePage, $settings, self::DOCUMENT_ID);

        $this->render('StudioFlatCMS/Views/admin/index', [
            'pageTitle' => __('studio_flatcms_title', 'StudioFlatCMS'),
            'headScriptUrls' => [
                theme_asset('js/theme-init.js', 'admin'),
            ],
            'headStyleUrls' => [
                asset('dists/fontawesome/css/all.min.css'),
                asset('css/admin/base.css'),
                asset('css/admin/themes/admin-modern-pro.css'),
                asset('dists/suneditor/suneditor.min.css'),
                asset('css/admin/suneditor.css'),
                theme_asset('css/admin.css', 'admin'),
            ],
            'scriptUrls' => [
                asset('dists/suneditor/suneditor.min.js'),
                asset('dists/suneditor/lang/en.min.js'),
                asset('dists/suneditor/lang/fr.min.js'),
                asset('dists/suneditor/lang/de.min.js'),
                asset('dists/suneditor/lang/es.min.js'),
                asset('dists/suneditor/lang/it.min.js'),
                asset('dists/suneditor/lang/pt_br.min.js'),
                asset('js/admin/suneditor-utils.js'),
                asset('js/admin/flatcms-ui-primitives.js'),
                theme_asset('js/admin.js', 'admin'),
                module_asset('StudioFlatCMS', 'js/studio-flatcms-api.js'),
                module_asset('StudioFlatCMS', 'js/studio-flatcms-state.js'),
                module_asset('StudioFlatCMS', 'js/studio-flatcms-render.js'),
                module_asset('StudioFlatCMS', 'js/studio-flatcms-app.js'),
            ],
            'stylesUrl' => module_asset('StudioFlatCMS', 'css/studio-flatcms.css'),
            'boot' => $this->bootPayload($document, $sourcePage),
            'mediaEnabled' => $this->isMediaEnabled(),
            'mediaModalConfig' => $this->mediaConfig(),
        ]);
    }

    public function document(): void
    {
        if (!$this->authorize('studio-flatcms.view')) {
            return;
        }

        $sourcePage = $this->resolveActiveSourcePage();
        $settings = FlatFile::settings();

        $this->json([
            'ok' => true,
            'document' => $this->storage->loadDocumentForSource($sourcePage, $settings, self::DOCUMENT_ID),
            'defaultDocument' => $this->schema->defaultDocument(
                is_array($sourcePage) ? (string) ($sourcePage['id'] ?? self::DOCUMENT_ID) : self::DOCUMENT_ID,
                $settings,
                is_array($sourcePage) ? $sourcePage : []
            ),
            'sources' => $this->sourcePages->listPages(),
            'currentSource' => $this->sourceOptionForPage($sourcePage),
        ]);
    }

    public function save(): void
    {
        if (!$this->authorize('studio-flatcms.edit')) {
            return;
        }

        $payload = $this->request->json();
        if (!is_array($payload)) {
            $this->json([
                'ok' => false,
                'message' => __('studio_flatcms_error_invalid_payload', 'StudioFlatCMS'),
            ], 400);
            return;
        }

        if (!$this->verifyApiCsrf($payload)) {
            $this->json([
                'ok' => false,
                'message' => __('error.csrf', 'Core'),
            ], 419);
            return;
        }

        $document = $payload['document'] ?? null;
        if (!is_array($document)) {
            $this->json([
                'ok' => false,
                'message' => __('studio_flatcms_error_missing_document', 'StudioFlatCMS'),
            ], 400);
            return;
        }

        $settings = FlatFile::settings();
        $sourcePage = $this->resolveSourcePageForPayload($document);
        $saved = $this->storage->saveDocumentForSource($sourcePage, $document, $settings, self::DOCUMENT_ID);

        $this->json([
            'ok' => true,
            'message' => __('studio_flatcms_save_success', 'StudioFlatCMS'),
            'document' => $saved,
            'currentSource' => $this->sourceOptionForPage($sourcePage),
        ]);
    }

    /**
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    private function bootPayload(array $document, ?array $sourcePage): array
    {
        $settings = FlatFile::settings();
        $currentSource = $this->sourceOptionForPage($sourcePage);

        return [
            'document' => $document,
            'defaultDocument' => $this->schema->defaultDocument(
                is_array($sourcePage) ? (string) ($sourcePage['id'] ?? self::DOCUMENT_ID) : self::DOCUMENT_ID,
                $settings,
                is_array($sourcePage) ? $sourcePage : []
            ),
            'sources' => $this->sourcePages->listPages(),
            'currentSource' => $currentSource,
            'routes' => [
                'data' => $this->routeUrl('/admin/studio-flatcms/data', $currentSource),
                'save' => $this->routeUrl('/admin/studio-flatcms/save', $currentSource),
            ],
            'config' => [
                'token' => $this->csrfToken(),
                'media' => $this->mediaConfig(),
            ],
            'labels' => [
                'drawerStructureTitle' => __('studio_flatcms_drawer_structure_title', 'StudioFlatCMS'),
                'drawerStructureSubtitle' => __('studio_flatcms_drawer_structure_subtitle', 'StudioFlatCMS'),
                'drawerElementsTitle' => __('studio_flatcms_drawer_elements_title', 'StudioFlatCMS'),
                'drawerElementsSubtitle' => __('studio_flatcms_drawer_elements_subtitle', 'StudioFlatCMS'),
                'drawerShellTitle' => __('studio_flatcms_drawer_shell_title', 'StudioFlatCMS'),
                'drawerShellSubtitle' => __('studio_flatcms_drawer_shell_subtitle', 'StudioFlatCMS'),
                'drawerPageTitle' => __('studio_flatcms_drawer_page_title', 'StudioFlatCMS'),
                'drawerPageSubtitle' => __('studio_flatcms_drawer_page_subtitle', 'StudioFlatCMS'),
                'tabContent' => __('studio_flatcms_inspector_tab_content', 'StudioFlatCMS'),
                'tabLayout' => __('studio_flatcms_inspector_tab_layout', 'StudioFlatCMS'),
                'tabDesign' => __('studio_flatcms_inspector_tab_design', 'StudioFlatCMS'),
                'tabEffects' => __('studio_flatcms_inspector_tab_effects', 'StudioFlatCMS'),
                'tabResponsive' => __('studio_flatcms_inspector_tab_responsive', 'StudioFlatCMS'),
                'fieldLabel' => __('studio_flatcms_field_label', 'StudioFlatCMS'),
                'fieldPageTitle' => __('studio_flatcms_field_page_title', 'StudioFlatCMS'),
                'fieldSourcePage' => __('studio_flatcms_field_source_page', 'StudioFlatCMS'),
                'fieldContent' => __('studio_flatcms_field_content', 'StudioFlatCMS'),
                'fieldEnabled' => __('studio_flatcms_field_enabled', 'StudioFlatCMS'),
                'fieldDirection' => __('studio_flatcms_field_direction', 'StudioFlatCMS'),
                'fieldHeadingLevel' => __('studio_flatcms_field_heading_level', 'StudioFlatCMS'),
                'fieldSurface' => __('studio_flatcms_field_surface', 'StudioFlatCMS'),
                'fieldVariant' => __('studio_flatcms_field_variant', 'StudioFlatCMS'),
                'fieldUrl' => __('studio_flatcms_field_url', 'StudioFlatCMS'),
                'fieldImageMedia' => __('studio_flatcms_field_image_media', 'StudioFlatCMS'),
                'fieldImageUrl' => __('studio_flatcms_field_image_url', 'StudioFlatCMS'),
                'fieldImageAlt' => __('studio_flatcms_field_image_alt', 'StudioFlatCMS'),
                'fieldWidth' => __('studio_flatcms_field_width', 'StudioFlatCMS'),
                'fieldHeight' => __('studio_flatcms_field_height', 'StudioFlatCMS'),
                'fieldOffsetX' => __('studio_flatcms_field_offset_x', 'StudioFlatCMS'),
                'fieldOffsetY' => __('studio_flatcms_field_offset_y', 'StudioFlatCMS'),
                'fieldButtonText' => __('studio_flatcms_field_button_text', 'StudioFlatCMS'),
                'fieldLogoText' => __('studio_flatcms_field_logo_text', 'StudioFlatCMS'),
                'groupContent' => __('studio_flatcms_group_content', 'StudioFlatCMS'),
                'groupFrame' => __('studio_flatcms_group_frame', 'StudioFlatCMS'),
                'groupBehavior' => __('studio_flatcms_group_behavior', 'StudioFlatCMS'),
                'directionVertical' => __('studio_flatcms_direction_vertical', 'StudioFlatCMS'),
                'directionHorizontal' => __('studio_flatcms_direction_horizontal', 'StudioFlatCMS'),
                'headingLevelH1' => __('studio_flatcms_heading_level_h1', 'StudioFlatCMS'),
                'headingLevelH2' => __('studio_flatcms_heading_level_h2', 'StudioFlatCMS'),
                'headingLevelH3' => __('studio_flatcms_heading_level_h3', 'StudioFlatCMS'),
                'headingLevelH4' => __('studio_flatcms_heading_level_h4', 'StudioFlatCMS'),
                'headingLevelH5' => __('studio_flatcms_heading_level_h5', 'StudioFlatCMS'),
                'headingLevelH6' => __('studio_flatcms_heading_level_h6', 'StudioFlatCMS'),
                'surfaceNone' => __('studio_flatcms_surface_none', 'StudioFlatCMS'),
                'surfaceSoft' => __('studio_flatcms_surface_soft', 'StudioFlatCMS'),
                'surfaceContrast' => __('studio_flatcms_surface_contrast', 'StudioFlatCMS'),
                'variantPrimary' => __('studio_flatcms_variant_primary', 'StudioFlatCMS'),
                'variantSecondary' => __('studio_flatcms_variant_secondary', 'StudioFlatCMS'),
                'variantLink' => __('studio_flatcms_variant_link', 'StudioFlatCMS'),
                'titleHint' => __('studio_flatcms_title_hint', 'StudioFlatCMS'),
                'textHint' => __('studio_flatcms_text_hint', 'StudioFlatCMS'),
                'menuHint' => __('studio_flatcms_menu_hint', 'StudioFlatCMS'),
                'effectsEmpty' => __('studio_flatcms_effects_empty', 'StudioFlatCMS'),
                'responsiveEmpty' => __('studio_flatcms_responsive_empty', 'StudioFlatCMS'),
                'previewPending' => __('studio_flatcms_preview_pending', 'StudioFlatCMS'),
                'saveSuccess' => __('studio_flatcms_save_success', 'StudioFlatCMS'),
                'saveError' => __('studio_flatcms_save_error', 'StudioFlatCMS'),
                'viewportDesktop' => __('studio_flatcms_viewport_desktop', 'StudioFlatCMS'),
                'viewportTablet' => __('studio_flatcms_viewport_tablet', 'StudioFlatCMS'),
                'viewportMobile' => __('studio_flatcms_viewport_mobile', 'StudioFlatCMS'),
                'mediaChooseImage' => __('studio_flatcms_media_choose_image', 'StudioFlatCMS'),
                'mediaRemoveMedia' => __('studio_flatcms_media_remove_media', 'StudioFlatCMS'),
                'mediaNoMedia' => __('studio_flatcms_media_no_media', 'StudioFlatCMS'),
                'mediaUnavailable' => __('studio_flatcms_media_unavailable', 'StudioFlatCMS'),
                'selectionEmpty' => __('studio_flatcms_empty_selection', 'StudioFlatCMS'),
                'emptyDropzone' => __('studio_flatcms_empty_dropzone', 'StudioFlatCMS'),
                'pageSourceHint' => __('studio_flatcms_page_source_hint', 'StudioFlatCMS'),
                'pageSourceEmpty' => __('studio_flatcms_page_source_empty', 'StudioFlatCMS'),
                'inspectorTitle' => __('studio_flatcms_inspector_title', 'StudioFlatCMS'),
                'actionAddSection' => __('studio_flatcms_action_add_section', 'StudioFlatCMS'),
                'actionAddTitle' => __('studio_flatcms_action_add_title', 'StudioFlatCMS'),
                'actionAddText' => __('studio_flatcms_action_add_text', 'StudioFlatCMS'),
                'actionAddImage' => __('studio_flatcms_action_add_image', 'StudioFlatCMS'),
                'actionAddButtons' => __('studio_flatcms_action_add_buttons', 'StudioFlatCMS'),
                'actionToggleAside' => __('studio_flatcms_action_toggle_aside', 'StudioFlatCMS'),
                'actionResetDocument' => __('studio_flatcms_action_reset_document', 'StudioFlatCMS'),
                'actionResetConfirm' => __('studio_flatcms_action_reset_confirm', 'StudioFlatCMS'),
                'actionSwitchSourceConfirm' => __('studio_flatcms_action_switch_source_confirm', 'StudioFlatCMS'),
                'actionDeleteNode' => __('studio_flatcms_action_delete_node', 'StudioFlatCMS'),
                'actionDuplicateNode' => __('studio_flatcms_action_duplicate_node', 'StudioFlatCMS'),
                'actionMoveNode' => __('studio_flatcms_action_move_node', 'StudioFlatCMS'),
                'actionMoveNodeUp' => __('studio_flatcms_action_move_node_up', 'StudioFlatCMS'),
                'actionMoveNodeDown' => __('studio_flatcms_action_move_node_down', 'StudioFlatCMS'),
                'actionOpenInspector' => __('studio_flatcms_action_open_inspector', 'StudioFlatCMS'),
                'actionMore' => __('studio_flatcms_action_more', 'StudioFlatCMS'),
                'cardAddSectionCopy' => __('studio_flatcms_card_add_section_copy', 'StudioFlatCMS'),
                'cardAddTitleCopy' => __('studio_flatcms_card_add_title_copy', 'StudioFlatCMS'),
                'cardAddTextCopy' => __('studio_flatcms_card_add_text_copy', 'StudioFlatCMS'),
                'cardAddImageCopy' => __('studio_flatcms_card_add_image_copy', 'StudioFlatCMS'),
                'cardAddButtonsCopy' => __('studio_flatcms_card_add_buttons_copy', 'StudioFlatCMS'),
                'cardToggleAsideCopy' => __('studio_flatcms_card_toggle_aside_copy', 'StudioFlatCMS'),
                'cardResetDocumentCopy' => __('studio_flatcms_card_reset_document_copy', 'StudioFlatCMS'),
                'modeCompose' => __('studio_flatcms_mode_compose', 'StudioFlatCMS'),
                'modeTheme' => __('studio_flatcms_mode_theme', 'StudioFlatCMS'),
                'nodeSection' => __('studio_flatcms_node_section', 'StudioFlatCMS'),
                'nodeStack' => __('studio_flatcms_node_stack', 'StudioFlatCMS'),
                'nodeText' => __('studio_flatcms_node_text', 'StudioFlatCMS'),
                'nodeTitle' => __('studio_flatcms_node_title', 'StudioFlatCMS'),
                'nodeButton' => __('studio_flatcms_node_button', 'StudioFlatCMS'),
                'nodeImage' => __('studio_flatcms_node_image', 'StudioFlatCMS'),
                'nodeMenu' => __('studio_flatcms_node_menu', 'StudioFlatCMS'),
                'nodeLogo' => __('studio_flatcms_node_logo', 'StudioFlatCMS'),
                'pageTitle' => __('studio_flatcms_document_title', 'StudioFlatCMS'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function verifyApiCsrf(array $payload): bool
    {
        $token = trim((string) ($this->request->header('X-CSRF-TOKEN', '') ?: ($payload['_token'] ?? '')));
        return $token !== '' && $this->session->verifyToken($token);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveActiveSourcePage(): ?array
    {
        $pageId = trim((string) $this->request->input('page', ''));
        return $this->sourcePages->resolveSelectedPage($pageId);
    }

    /**
     * @param array<string, mixed> $documentPayload
     * @return array<string, mixed>|null
     */
    private function resolveSourcePageForPayload(array $documentPayload): ?array
    {
        $source = is_array($documentPayload['source'] ?? null) ? $documentPayload['source'] : [];
        $sourceId = trim((string) ($source['entity_id'] ?? $this->request->input('page', '')));

        return $this->sourcePages->resolveSelectedPage($sourceId);
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
        foreach ($this->sourcePages->listPages() as $option) {
            if ((string) ($option['id'] ?? '') === $pageId) {
                return $option;
            }
        }

        return [
            'id' => $pageId,
            'title' => trim((string) ($page['title'] ?? '')),
            'slug' => trim((string) ($page['slug'] ?? '')),
            'locale' => trim((string) ($page['locale'] ?? '')),
            'locale_label' => I18n::getLocalizedLanguageName(
                trim((string) ($page['locale'] ?? '')),
                I18n::getLocale()
            ),
            'status' => trim((string) ($page['status'] ?? 'draft')),
            'status_label' => (string) ($page['status'] ?? 'draft') === 'published'
                ? __('status_published', 'Pages')
                : __('status_draft', 'Pages'),
            'studio_url' => $this->sourcePages->studioUrlForPage($page),
            'frontend_path' => $this->sourcePages->buildFrontendPath($page),
        ];
    }

    /**
     * @param array<string, string>|null $currentSource
     */
    private function routeUrl(string $path, ?array $currentSource): string
    {
        if (!is_array($currentSource) || trim((string) ($currentSource['id'] ?? '')) === '') {
            return url($path);
        }

        return url($path . '?page=' . rawurlencode((string) $currentSource['id']));
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
