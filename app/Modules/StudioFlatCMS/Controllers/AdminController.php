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
use App\Modules\StudioFlatCMS\Services\StudioSchemaService;
use App\Modules\StudioFlatCMS\Services\StudioStorageService;

final class AdminController extends BaseController
{
    private const DOCUMENT_ID = 'home';

    private StudioSchemaService $schema;
    private StudioStorageService $storage;

    public function __construct()
    {
        parent::__construct();
        I18n::load('StudioFlatCMS');

        $this->schema = new StudioSchemaService();
        $this->storage = new StudioStorageService($this->schema);
    }

    public function index(): void
    {
        if (!$this->authorize('studio-flatcms.view')) {
            return;
        }

        $settings = FlatFile::settings();
        $document = $this->storage->loadDocument(self::DOCUMENT_ID, $settings);

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
            'boot' => $this->bootPayload($document),
        ]);
    }

    public function document(): void
    {
        if (!$this->authorize('studio-flatcms.view')) {
            return;
        }

        $this->json([
            'ok' => true,
            'document' => $this->storage->loadDocument(self::DOCUMENT_ID, FlatFile::settings()),
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

        $saved = $this->storage->saveDocument(self::DOCUMENT_ID, $document, FlatFile::settings());

        $this->json([
            'ok' => true,
            'message' => __('studio_flatcms_save_success', 'StudioFlatCMS'),
            'document' => $saved,
        ]);
    }

    /**
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    private function bootPayload(array $document): array
    {
        return [
            'document' => $document,
            'defaultDocument' => $this->schema->defaultDocument(self::DOCUMENT_ID, FlatFile::settings()),
            'routes' => [
                'data' => url('/admin/studio-flatcms/data'),
                'save' => url('/admin/studio-flatcms/save'),
            ],
            'config' => [
                'token' => $this->csrfToken(),
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
                'fieldContent' => __('studio_flatcms_field_content', 'StudioFlatCMS'),
                'fieldEnabled' => __('studio_flatcms_field_enabled', 'StudioFlatCMS'),
                'fieldDirection' => __('studio_flatcms_field_direction', 'StudioFlatCMS'),
                'fieldSurface' => __('studio_flatcms_field_surface', 'StudioFlatCMS'),
                'fieldVariant' => __('studio_flatcms_field_variant', 'StudioFlatCMS'),
                'fieldUrl' => __('studio_flatcms_field_url', 'StudioFlatCMS'),
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
                'surfaceNone' => __('studio_flatcms_surface_none', 'StudioFlatCMS'),
                'surfaceSoft' => __('studio_flatcms_surface_soft', 'StudioFlatCMS'),
                'surfaceContrast' => __('studio_flatcms_surface_contrast', 'StudioFlatCMS'),
                'variantPrimary' => __('studio_flatcms_variant_primary', 'StudioFlatCMS'),
                'variantSecondary' => __('studio_flatcms_variant_secondary', 'StudioFlatCMS'),
                'variantLink' => __('studio_flatcms_variant_link', 'StudioFlatCMS'),
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
                'selectionEmpty' => __('studio_flatcms_empty_selection', 'StudioFlatCMS'),
                'emptyDropzone' => __('studio_flatcms_empty_dropzone', 'StudioFlatCMS'),
                'inspectorTitle' => __('studio_flatcms_inspector_title', 'StudioFlatCMS'),
                'actionAddSection' => __('studio_flatcms_action_add_section', 'StudioFlatCMS'),
                'actionAddText' => __('studio_flatcms_action_add_text', 'StudioFlatCMS'),
                'actionAddButtons' => __('studio_flatcms_action_add_buttons', 'StudioFlatCMS'),
                'actionToggleAside' => __('studio_flatcms_action_toggle_aside', 'StudioFlatCMS'),
                'actionResetDocument' => __('studio_flatcms_action_reset_document', 'StudioFlatCMS'),
                'actionResetConfirm' => __('studio_flatcms_action_reset_confirm', 'StudioFlatCMS'),
                'actionDeleteNode' => __('studio_flatcms_action_delete_node', 'StudioFlatCMS'),
                'actionMoveNode' => __('studio_flatcms_action_move_node', 'StudioFlatCMS'),
                'actionOpenInspector' => __('studio_flatcms_action_open_inspector', 'StudioFlatCMS'),
                'actionMore' => __('studio_flatcms_action_more', 'StudioFlatCMS'),
                'cardAddSectionCopy' => __('studio_flatcms_card_add_section_copy', 'StudioFlatCMS'),
                'cardAddTextCopy' => __('studio_flatcms_card_add_text_copy', 'StudioFlatCMS'),
                'cardAddButtonsCopy' => __('studio_flatcms_card_add_buttons_copy', 'StudioFlatCMS'),
                'cardToggleAsideCopy' => __('studio_flatcms_card_toggle_aside_copy', 'StudioFlatCMS'),
                'cardResetDocumentCopy' => __('studio_flatcms_card_reset_document_copy', 'StudioFlatCMS'),
                'modeCompose' => __('studio_flatcms_mode_compose', 'StudioFlatCMS'),
                'modeTheme' => __('studio_flatcms_mode_theme', 'StudioFlatCMS'),
                'nodeSection' => __('studio_flatcms_node_section', 'StudioFlatCMS'),
                'nodeStack' => __('studio_flatcms_node_stack', 'StudioFlatCMS'),
                'nodeText' => __('studio_flatcms_node_text', 'StudioFlatCMS'),
                'nodeButton' => __('studio_flatcms_node_button', 'StudioFlatCMS'),
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
}
