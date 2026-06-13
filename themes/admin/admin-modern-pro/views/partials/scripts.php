<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>
<script src="<?= theme_asset('js/admin.js', 'admin') ?>"></script>
<script src="<?= asset('js/core/components-password-toggle.js') ?>" defer></script>
<script src="<?= asset('dists/jquery/jquery.min.js') ?>"></script>
<?php
$activeWysiwygProvider = in_array((string) ($flatcmsWysiwygProvider ?? ''), ['suneditor', 'tinymce'], true)
    ? (string) $flatcmsWysiwygProvider
    : 'suneditor';
$tinyApiKeyRaw = trim((string) env('TINYMCE_API_KEY', ''));
$tinyApiKeySafe = preg_replace('/[^a-zA-Z0-9_-]/', '', $tinyApiKeyRaw) ?? '';
$tinyApiKeyForCdn = $tinyApiKeySafe !== '' ? $tinyApiKeySafe : 'no-api-key';
?>
<?php if ($activeWysiwygProvider === 'tinymce'): ?>
    <script src="https://cdn.tiny.cloud/1/<?= e($tinyApiKeyForCdn) ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<?php endif; ?>
<script src="<?= asset('dists/suneditor/suneditor.min.js') ?>"></script>
<script src="<?= asset('dists/suneditor/lang/en.min.js') ?>"></script>
<script src="<?= asset('dists/suneditor/lang/fr.min.js') ?>"></script>
<script src="<?= asset('dists/suneditor/lang/de.min.js') ?>"></script>
<script src="<?= asset('dists/suneditor/lang/es.min.js') ?>"></script>
<script src="<?= asset('dists/suneditor/lang/it.min.js') ?>"></script>
<script src="<?= asset('dists/suneditor/lang/pt_br.min.js') ?>"></script>
<script src="<?= asset('js/admin/suneditor-utils.js') ?>"></script>
<script src="<?= asset('js/admin/flatcms-ui-primitives.js') ?>"></script>
<script src="<?= asset('js/admin/editor-provider-init.js') ?>"></script>
<?php if (module_enabled('AiAgent')): ?>
    <script src="<?= module_asset('AiAgent', 'js/ai-agent.js') ?>"></script>
<?php endif; ?>
<?php
$guidedTourSettings = \App\Core\FlatFile::settings();
$guidedTourEnabled = !array_key_exists('admin_guided_tour_enabled', $guidedTourSettings)
    ? true
    : (bool) ((int) ($guidedTourSettings['admin_guided_tour_enabled'] ?? 0));
$guidedTourUser = is_array($auth_user ?? null) ? $auth_user : (auth() ?? []);
$guidedTourSeenAt = trim((string) ($guidedTourUser['admin_tour_seen_at'] ?? ''));
$guidedTourVersion = 'v5';
$guidedTourSeenVersion = trim((string) ($guidedTourUser['admin_tour_version'] ?? ''));
$guidedTourVersionChanged = $guidedTourSeenAt !== '' && $guidedTourSeenVersion !== $guidedTourVersion;
$guidedTourForceNextLogin = (bool) app()->session()->get('admin_tour_force_next_login', false);
if ($guidedTourForceNextLogin) {
    app()->session()->remove('admin_tour_force_next_login');
}
$guidedTourSeenModulesRaw = $guidedTourUser['admin_tour_seen_modules'] ?? [];
$guidedTourSeenModules = [];
if (is_array($guidedTourSeenModulesRaw)) {
    $guidedTourSeenModules = $guidedTourSeenModulesRaw;
} elseif (is_string($guidedTourSeenModulesRaw) && trim($guidedTourSeenModulesRaw) !== '') {
    $decodedModules = json_decode($guidedTourSeenModulesRaw, true);
    if (is_array($decodedModules)) {
        $guidedTourSeenModules = $decodedModules;
    } else {
        $guidedTourSeenModules = preg_split('/[,;]+/', $guidedTourSeenModulesRaw) ?: [];
    }
}

$guidedTourSeenModules = array_values(array_filter(array_map(static function ($module): string {
    $normalized = strtolower(trim((string) $module));
    $normalized = preg_replace('/[^a-z0-9_-]/', '', $normalized) ?? '';
    return $normalized;
}, $guidedTourSeenModules), static fn(string $module): bool => $module !== '' && $module !== 'global'));

$guidedTourCurrentPath = trim((string) ($_GET['path'] ?? ''));
if ($guidedTourCurrentPath === '') {
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $requestPath = trim((string) parse_url($requestUri, PHP_URL_PATH), '/');
    if ($requestPath !== '') {
        $guidedTourCurrentPath = $requestPath;
    }
    if ($guidedTourCurrentPath === '' && $requestUri !== '') {
        $requestQuery = (string) parse_url($requestUri, PHP_URL_QUERY);
        if ($requestQuery !== '') {
            parse_str($requestQuery, $queryVars);
            if (is_array($queryVars) && isset($queryVars['path'])) {
                $guidedTourCurrentPath = trim((string) $queryVars['path']);
            }
        }
    }
}
$guidedTourCurrentPath = strtolower(trim($guidedTourCurrentPath, '/'));
$guidedTourAutoStart = !empty($guidedTourUser['id']) && ($guidedTourEnabled || $guidedTourForceNextLogin);

$tourNavigationTitle = __('admin_tour_step_navigation_title', 'Core');
$tourNavigationContent = __('admin_tour_step_navigation_content', 'Core');
$tourBrandingTitle = __('admin_tour_step_branding_title', 'Core');
$tourBrandingContent = __('admin_tour_step_branding_content', 'Core');
$tourTopbarTitle = __('admin_tour_step_topbar_title', 'Core');
$tourTopbarContent = __('admin_tour_step_topbar_content', 'Core');
$tourUserMenuTitle = __('admin_tour_step_user_menu_title', 'Core');
$tourUserMenuContent = __('admin_tour_step_user_menu_content', 'Core');
$tourWorkspaceTitle = __('admin_tour_step_workspace_title', 'Core');
$tourWorkspaceContent = __('admin_tour_step_workspace_content', 'Core');
$tourSettingsTitle = __('admin_tour_step_settings_title', 'Core');
$tourSettingsContent = __('admin_tour_step_settings_content', 'Core');
$tourModuleFiltersTitle = __('admin_tour_module_filters_title', 'Dashboard');
$tourModuleFiltersContent = __('admin_tour_module_filters_content', 'Dashboard');
$tourModuleListTitle = __('admin_tour_module_list_title', 'Dashboard');
$tourModuleListContent = __('admin_tour_module_list_content', 'Dashboard');
$tourModuleActionsTitle = __('admin_tour_module_actions_title', 'Dashboard');
$tourModuleActionsContent = __('admin_tour_module_actions_content', 'Dashboard');
$tourModuleStatsTitle = __('admin_tour_module_stats_title', 'Dashboard');
$tourModuleStatsContent = __('admin_tour_module_stats_content', 'Dashboard');
$tourModuleInstallerTitle = __('admin_tour_module_installer_title', 'Dashboard');
$tourModuleInstallerContent = __('admin_tour_module_installer_content', 'Dashboard');
$tourModuleControlsTitle = __('admin_tour_module_controls_title', 'Dashboard');
$tourModuleControlsContent = __('admin_tour_module_controls_content', 'Dashboard');
$tourModuleAuditTitle = __('admin_tour_module_audit_title', 'Dashboard');
$tourModuleAuditContent = __('admin_tour_module_audit_content', 'Dashboard');
$tourModuleReportTitle = __('admin_tour_module_report_title', 'Dashboard');
$tourModuleReportContent = __('admin_tour_module_report_content', 'Dashboard');
$tourModuleTabsTitle = __('admin_tour_module_tabs_title', 'Dashboard');
$tourModuleTabsContent = __('admin_tour_module_tabs_content', 'Dashboard');
$tourModuleWorkflowTitle = __('admin_tour_module_workflow_title', 'Dashboard');
$tourModuleWorkflowContent = __('admin_tour_module_workflow_content', 'Dashboard');
$tourModuleTranslationsTitle = __('admin_tour_module_translations_title', 'Dashboard');
$tourModuleTranslationsContent = __('admin_tour_module_translations_content', 'Dashboard');
$tourDashboardWelcomeTitle = __('admin_tour_dashboard_welcome_title', 'Dashboard');
$tourDashboardWelcomeContent = __('admin_tour_dashboard_welcome_content', 'Dashboard');
$tourDashboardMaintenanceTitle = __('admin_tour_dashboard_maintenance_title', 'Dashboard');
$tourDashboardMaintenanceContent = __('admin_tour_dashboard_maintenance_content', 'Dashboard');
$tourDashboardBackupsTitle = __('admin_tour_dashboard_backups_title', 'Dashboard');
$tourDashboardBackupsContent = __('admin_tour_dashboard_backups_content', 'Dashboard');
$tourDashboardStatsTitle = __('admin_tour_dashboard_stats_title', 'Dashboard');
$tourDashboardStatsContent = __('admin_tour_dashboard_stats_content', 'Dashboard');
$tourDashboardChartTitle = __('admin_tour_dashboard_chart_title', 'Dashboard');
$tourDashboardChartContent = __('admin_tour_dashboard_chart_content', 'Dashboard');
$tourDashboardRecentTitle = __('admin_tour_dashboard_recent_title', 'Dashboard');
$tourDashboardRecentContent = __('admin_tour_dashboard_recent_content', 'Dashboard');
$tourDashboardQuickActionsTitle = __('admin_tour_dashboard_quick_actions_title', 'Dashboard');
$tourDashboardQuickActionsContent = __('admin_tour_dashboard_quick_actions_content', 'Dashboard');
$tourDashboardExtensionsTitle = __('admin_tour_dashboard_extensions_title', 'Dashboard');
$tourDashboardExtensionsContent = __('admin_tour_dashboard_extensions_content', 'Dashboard');
$tourDashboardSystemTitle = __('admin_tour_dashboard_system_title', 'Dashboard');
$tourDashboardSystemContent = __('admin_tour_dashboard_system_content', 'Dashboard');
$tourDashboardFinalTitle = __('admin_tour_dashboard_final_title', 'Dashboard');
$tourDashboardFinalContent = __('admin_tour_dashboard_final_content', 'Dashboard');
$tourCustomStep = static function (string $selector, string $title, string $content, string $placement = 'top'): array {
    return [
        'selector' => $selector,
        'title' => $title,
        'content' => $content,
        'placement' => $placement,
    ];
};

$guidedTourConfig = [
    'enabled' => $guidedTourEnabled,
    'autoStart' => $guidedTourAutoStart,
    'forceAutoStart' => $guidedTourForceNextLogin,
    'globalSeen' => !$guidedTourVersionChanged && $guidedTourSeenAt !== '',
    'seenModules' => $guidedTourVersionChanged ? [] : $guidedTourSeenModules,
    'currentPath' => $guidedTourCurrentPath,
    'csrfToken' => (string) ($csrf_token ?? app()->session()->token()),
    'markSeenUrl' => url('/admin/settings/guided-tour/complete'),
    'resetUrl' => url('/admin/settings/guided-tour/reset'),
    'version' => $guidedTourVersion,
    'steps' => [
        [
            'selector' => '[data-tour-target="sidebar-branding"]',
            'title' => $tourBrandingTitle,
            'content' => $tourBrandingContent,
            'placement' => 'right',
        ],
        [
            'selector' => '[data-tour-target="sidebar-navigation"]',
            'title' => $tourNavigationTitle,
            'content' => $tourNavigationContent,
            'placement' => 'right',
        ],
        [
            'selector' => '[data-tour="topbar-actions"]',
            'title' => $tourTopbarTitle,
            'content' => $tourTopbarContent,
            'placement' => 'bottom',
        ],
        [
            'selector' => '[data-tour-target="topbar-user-menu"]',
            'title' => $tourUserMenuTitle,
            'content' => $tourUserMenuContent,
            'placement' => 'left',
        ],
        [
            'selector' => '[data-tour="content"]',
            'title' => $tourWorkspaceTitle,
            'content' => $tourWorkspaceContent,
            'placement' => 'top',
        ],
        [
            'selector' => 'a.nav-link[href*="/admin/settings"]',
            'title' => $tourSettingsTitle,
            'content' => $tourSettingsContent,
            'placement' => 'right',
        ],
    ],
    'moduleTours' => [
        'dashboard' => [
            'routes' => ['admin', 'admin/dashboard'],
            'steps' => [
                $tourCustomStep('.welcome-banner', $tourDashboardWelcomeTitle, $tourDashboardWelcomeContent, 'bottom'),
                $tourCustomStep('.maintenance-banner', $tourDashboardMaintenanceTitle, $tourDashboardMaintenanceContent, 'bottom'),
                $tourCustomStep('[data-tour-target="dashboard-backups"]', $tourDashboardBackupsTitle, $tourDashboardBackupsContent, 'bottom'),
                $tourCustomStep('.stats-grid', $tourDashboardStatsTitle, $tourDashboardStatsContent, 'top'),
                $tourCustomStep('.chart-container', $tourDashboardChartTitle, $tourDashboardChartContent, 'top'),
                $tourCustomStep('.recent-list', $tourDashboardRecentTitle, $tourDashboardRecentContent, 'top'),
                $tourCustomStep('.quick-actions', $tourDashboardQuickActionsTitle, $tourDashboardQuickActionsContent, 'left'),
                $tourCustomStep('.system-info, .disk-usage', $tourDashboardSystemTitle, $tourDashboardSystemContent, 'left'),
                $tourCustomStep('.page-header', $tourDashboardFinalTitle, $tourDashboardFinalContent, 'bottom'),
            ],
        ],
        'pages' => [
            'routes' => ['admin/pages'],
            'steps' => [
                array_merge(
                    $tourCustomStep('[data-tour-target="pages-list-create"]', __('pages_tour_next_action_title', 'Pages'), __('pages_tour_list_empty_content', 'Pages'), 'left'),
                    ['whenVisible' => '[data-tour-target="pages-list-table"][data-tour-state="empty"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="pages-list-toolbar"]', __('pages_tour_list_toolbar_title', 'Pages'), __('pages_tour_list_toolbar_content', 'Pages'), 'bottom'),
                    ['whenVisible' => '[data-tour-target="pages-list-table"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="pages-list-batch"]', __('pages_tour_list_batch_title', 'Pages'), __('pages_tour_list_batch_content', 'Pages'), 'bottom'),
                    ['whenVisible' => '[data-tour-target="pages-list-table"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="pages-list-table"]', __('pages_tour_list_table_title', 'Pages'), __('pages_tour_list_table_content', 'Pages'), 'top'),
                    ['whenVisible' => '[data-tour-target="pages-list-table"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="pages-list-create"]', __('pages_tour_next_action_title', 'Pages'), __('pages_tour_list_ready_next_content', 'Pages'), 'left'),
                    ['whenVisible' => '[data-tour-target="pages-list-table"][data-tour-state="ready"]']
                ),
                $tourCustomStep('[data-tour-target="pages-translation-tabs"]', __('pages_tour_form_translations_title', 'Pages'), __('pages_tour_form_translations_content', 'Pages'), 'bottom'),
                $tourCustomStep('[data-tour-section="pages-form-fields"], [data-tour-target="pages-form-fields"]', __('pages_tour_form_fields_title', 'Pages'), __('pages_tour_form_fields_content', 'Pages'), 'top'),
                $tourCustomStep('[data-tour-target="pages-form-status"]', __('pages_tour_form_status_title', 'Pages'), __('pages_tour_form_status_content', 'Pages'), 'left'),
                $tourCustomStep('[data-tour-target="pages-form-seo"]', __('pages_tour_form_seo_title', 'Pages'), __('pages_tour_form_seo_content', 'Pages'), 'left'),
                array_merge(
                    $tourCustomStep('[data-tour-target="pages-form-save"]', __('pages_tour_next_action_title', 'Pages'), __('pages_tour_form_create_next_content', 'Pages'), 'left'),
                    ['whenVisible' => 'form[data-tour-state="create"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="pages-form-save"]', __('pages_tour_next_action_title', 'Pages'), __('pages_tour_form_edit_next_content', 'Pages'), 'left'),
                    ['whenVisible' => 'form[data-tour-state="edit"]']
                ),
            ],
        ],
        'posts' => [
            'routes' => ['admin/posts'],
            'steps' => [
                array_merge(
                    $tourCustomStep('[data-tour-target="posts-list-create"]', __('posts_tour_next_action_title', 'Posts'), __('posts_tour_list_empty_content', 'Posts'), 'left'),
                    ['whenVisible' => '[data-tour-target="posts-list-table"][data-tour-state="empty"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="posts-list-toolbar"]', __('posts_tour_list_toolbar_title', 'Posts'), __('posts_tour_list_toolbar_content', 'Posts'), 'bottom'),
                    ['whenVisible' => '[data-tour-target="posts-list-table"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="posts-list-batch"]', __('posts_tour_list_batch_title', 'Posts'), __('posts_tour_list_batch_content', 'Posts'), 'bottom'),
                    ['whenVisible' => '[data-tour-target="posts-list-table"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="posts-list-table"]', __('posts_tour_list_table_title', 'Posts'), __('posts_tour_list_table_content', 'Posts'), 'top'),
                    ['whenVisible' => '[data-tour-target="posts-list-table"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="posts-list-create"]', __('posts_tour_next_action_title', 'Posts'), __('posts_tour_list_ready_next_content', 'Posts'), 'left'),
                    ['whenVisible' => '[data-tour-target="posts-list-table"][data-tour-state="ready"]']
                ),
                $tourCustomStep('[data-tour-target="posts-translation-tabs"]', __('posts_tour_form_translations_title', 'Posts'), __('posts_tour_form_translations_content', 'Posts'), 'bottom'),
                $tourCustomStep('[data-tour-target="posts-form-fields"]', __('posts_tour_form_fields_title', 'Posts'), __('posts_tour_form_fields_content', 'Posts'), 'top'),
                $tourCustomStep('[data-tour-target="posts-form-status"]', __('posts_tour_form_status_title', 'Posts'), __('posts_tour_form_status_content', 'Posts'), 'left'),
                $tourCustomStep('[data-tour-target="posts-form-media"]', __('posts_tour_form_media_title', 'Posts'), __('posts_tour_form_media_content', 'Posts'), 'left'),
                $tourCustomStep('[data-tour-target="posts-form-taxonomies"]', __('posts_tour_form_taxonomies_title', 'Posts'), __('posts_tour_form_taxonomies_content', 'Posts'), 'left'),
                $tourCustomStep('[data-tour-target="posts-form-seo"]', __('posts_tour_form_seo_title', 'Posts'), __('posts_tour_form_seo_content', 'Posts'), 'left'),
                array_merge(
                    $tourCustomStep('[data-tour-target="posts-form-save"]', __('posts_tour_next_action_title', 'Posts'), __('posts_tour_form_create_next_content', 'Posts'), 'left'),
                    ['whenVisible' => 'form[data-tour-state="create"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="posts-form-save"]', __('posts_tour_next_action_title', 'Posts'), __('posts_tour_form_edit_next_content', 'Posts'), 'left'),
                    ['whenVisible' => 'form[data-tour-state="edit"]']
                ),
            ],
        ],
        'categories' => [
            'routes' => ['admin/categories'],
            'steps' => [
                array_merge(
                    $tourCustomStep('[data-tour-target="categories-list-create"]', __('categories_tour_next_action_title', 'Categories'), __('categories_tour_list_empty_content', 'Categories'), 'left'),
                    ['whenVisible' => '[data-tour-target="categories-list-table"][data-tour-state="empty"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="categories-list-toolbar"]', __('categories_tour_list_toolbar_title', 'Categories'), __('categories_tour_list_toolbar_content', 'Categories'), 'bottom'),
                    ['whenVisible' => '[data-tour-target="categories-list-table"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="categories-list-batch"]', __('categories_tour_list_batch_title', 'Categories'), __('categories_tour_list_batch_content', 'Categories'), 'bottom'),
                    ['whenVisible' => '[data-tour-target="categories-list-table"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="categories-list-table"]', __('categories_tour_list_table_title', 'Categories'), __('categories_tour_list_table_content', 'Categories'), 'top'),
                    ['whenVisible' => '[data-tour-target="categories-list-table"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="categories-list-create"]', __('categories_tour_next_action_title', 'Categories'), __('categories_tour_list_ready_next_content', 'Categories'), 'left'),
                    ['whenVisible' => '[data-tour-target="categories-list-table"][data-tour-state="ready"]']
                ),
                $tourCustomStep('[data-tour-target="categories-translation-tabs"]', __('categories_tour_form_translations_title', 'Categories'), __('categories_tour_form_translations_content', 'Categories'), 'bottom'),
                $tourCustomStep('[data-tour-section="categories-form-fields"]', __('categories_tour_form_fields_title', 'Categories'), __('categories_tour_form_fields_content', 'Categories'), 'top'),
                $tourCustomStep('[data-tour-target="categories-form-settings"]', __('categories_tour_form_settings_title', 'Categories'), __('categories_tour_form_settings_content', 'Categories'), 'left'),
                array_merge(
                    $tourCustomStep('[data-tour-target="categories-form-save"]', __('categories_tour_next_action_title', 'Categories'), __('categories_tour_form_create_next_content', 'Categories'), 'left'),
                    ['whenVisible' => 'form[data-tour-state="create"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="categories-form-save"]', __('categories_tour_next_action_title', 'Categories'), __('categories_tour_form_edit_next_content', 'Categories'), 'left'),
                    ['whenVisible' => 'form[data-tour-state="edit"]']
                ),
            ],
        ],
        'comments' => [
            'routes' => ['admin/comments'],
            'steps' => [
                $tourCustomStep('[data-tour-target="comments-toolbar"]', __('comments_tour_filter_title', 'Comments'), __('comments_tour_filter_content', 'Comments'), 'bottom'),
                array_merge(
                    $tourCustomStep('[data-tour-target="comments-table"]', __('comments_tour_table_title', 'Comments'), __('comments_tour_table_content', 'Comments'), 'top'),
                    ['whenVisible' => '[data-tour-target="comments-table"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="comments-empty"]', __('comments_tour_next_action_title', 'Comments'), __('comments_tour_empty_content', 'Comments'), 'top'),
                    ['whenVisible' => '[data-tour-target="comments-empty"]']
                ),
                array_merge(
                    $tourCustomStep('.comment-actions, .comment-actions-cell', __('comments_tour_actions_title', 'Comments'), __('comments_tour_actions_content', 'Comments'), 'left'),
                    ['whenVisible' => '[data-tour-target="comments-table"][data-tour-state="ready"]']
                ),
                $tourCustomStep('.pagination', $tourModuleWorkflowTitle, $tourModuleWorkflowContent, 'top'),
            ],
        ],
        'contact_forms' => [
            'routes' => ['admin/contact/forms'],
            'steps' => [
                $tourCustomStep('[data-tour-target="contact-form-header"]', __('contact_tour_form_main_title', 'Contact'), __('contact_tour_form_main_content', 'Contact'), 'bottom'),
                $tourCustomStep('[data-tour-section="contact-form-identity"]', __('contact_tour_form_identity_title', 'Contact'), __('contact_tour_form_identity_content', 'Contact'), 'top'),
                array_merge(
                    $tourCustomStep('[data-tour-target="contact-form-legal"]', __('contact_tour_form_legal_title', 'Contact'), __('contact_tour_form_legal_content', 'Contact'), 'top'),
                    ['whenVisible' => '[data-tour-target="contact-form-legal"]:not(.is-hidden)']
                ),
                $tourCustomStep('[data-tour-target="contact-form-builder"]', __('contact_tour_form_builder_title', 'Contact'), __('contact_tour_form_builder_content', 'Contact'), 'top'),
                $tourCustomStep('[data-tour-target="contact-form-builder-canvas"]', __('contact_tour_form_canvas_title', 'Contact'), __('contact_tour_form_canvas_content', 'Contact'), 'top'),
                $tourCustomStep('[data-tour-target="contact-form-translations-trigger"]', __('contact_tour_form_translations_title', 'Contact'), __('contact_tour_form_translations_content', 'Contact'), 'left'),
                $tourCustomStep('[data-tour-target="contact-form-delivery"]', __('contact_tour_form_sidebar_title', 'Contact'), __('contact_tour_form_sidebar_content', 'Contact'), 'left'),
                array_merge(
                    $tourCustomStep('[data-tour-target="contact-form-save"]', __('contact_tour_next_action_title', 'Contact'), __('contact_tour_form_create_next_content', 'Contact'), 'left'),
                    ['whenVisible' => 'form[data-tour-state="create"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="contact-form-save"]', __('contact_tour_next_action_title', 'Contact'), __('contact_tour_form_edit_next_content', 'Contact'), 'left'),
                    ['whenVisible' => 'form[data-tour-state="edit"]']
                ),
            ],
        ],
        'contact' => [
            'routes' => ['admin/contact'],
            'steps' => [
                array_merge(
                    $tourCustomStep('[data-tour-target="contact-forms-create"]', __('contact_tour_next_action_title', 'Contact'), __('contact_tour_list_empty_content', 'Contact'), 'left'),
                    ['whenVisible' => '[data-tour-target="contact-forms-list"][data-tour-state="empty"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="contact-forms-toolbar"]', __('contact_tour_list_toolbar_title', 'Contact'), __('contact_tour_list_toolbar_content', 'Contact'), 'bottom'),
                    ['whenVisible' => '[data-tour-target="contact-forms-list"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="contact-forms-list"]', __('contact_tour_list_table_title', 'Contact'), __('contact_tour_list_table_content', 'Contact'), 'top'),
                    ['whenVisible' => '[data-tour-target="contact-forms-list"][data-tour-state="ready"]']
                ),
                array_merge(
                    $tourCustomStep('[data-tour-target="contact-forms-create"]', __('contact_tour_next_action_title', 'Contact'), __('contact_tour_list_ready_next_content', 'Contact'), 'left'),
                    ['whenVisible' => '[data-tour-target="contact-forms-list"][data-tour-state="ready"]']
                ),
            ],
        ],
        'media' => [
            'routes' => ['admin/media'],
            'steps' => [
                $tourCustomStep('[data-tour-target="media-toolbar"]', __('media_tour_toolbar_title', 'Media'), __('media_tour_toolbar_content', 'Media'), 'bottom'),
                $tourCustomStep('[data-tour-target="media-folders"]', __('media_tour_folders_title', 'Media'), __('media_tour_folders_content', 'Media'), 'bottom'),
                $tourCustomStep('[data-tour-target="media-initial-state"]', __('media_tour_next_action_title', 'Media'), __('media_tour_initial_content', 'Media'), 'top'),
                $tourCustomStep('[data-tour-target="media-upload-zone"]', __('media_tour_upload_title', 'Media'), __('media_tour_upload_content', 'Media'), 'top'),
                $tourCustomStep('[data-tour-target="media-files-grid"]', __('media_tour_files_title', 'Media'), __('media_tour_files_content', 'Media'), 'top'),
            ],
        ],
        'menu' => [
            'routes' => ['admin/menus'],
            'steps' => [
                $tourCustomStep('.menu-page-header .page-header-actions, #menuForm', $tourModuleActionsTitle, $tourModuleActionsContent, 'bottom'),
                $tourCustomStep('#menuActive', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('#menuAvailable, .menu-available-accordion', $tourModuleWorkflowTitle, $tourModuleWorkflowContent, 'top'),
                $tourCustomStep('.menu-item-config, .menu-custom-card', $tourModuleControlsTitle, $tourModuleControlsContent, 'left'),
            ],
        ],
        'footer' => [
            'routes' => ['admin/footer'],
            'steps' => [
                $tourCustomStep('.settings-form', $tourModuleWorkflowTitle, $tourModuleWorkflowContent, 'top'),
                $tourCustomStep('.settings-form .card', $tourModuleTabsTitle, $tourModuleTabsContent, 'top'),
                $tourCustomStep('.form-actions', $tourModuleActionsTitle, $tourModuleActionsContent, 'top'),
            ],
        ],
        'users' => [
            'routes' => ['admin/users'],
            'steps' => [
                $tourCustomStep('.users-filter-form', $tourModuleFiltersTitle, $tourModuleFiltersContent, 'left'),
                $tourCustomStep('#roleFilter, #statusFilter', $tourModuleControlsTitle, $tourModuleControlsContent, 'bottom'),
                $tourCustomStep('.users-filter-controls .btn', $tourModuleActionsTitle, $tourModuleActionsContent, 'bottom'),
                $tourCustomStep('.user-stats-row', $tourModuleStatsTitle, $tourModuleStatsContent, 'top'),
                $tourCustomStep('.user-stat-card', $tourModuleStatsTitle, $tourModuleStatsContent, 'top'),
                $tourCustomStep('.table-wrapper', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('.user-cell', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('.user-actions, .table-actions', $tourModuleActionsTitle, $tourModuleActionsContent, 'left'),
                $tourCustomStep('.pagination', $tourModuleWorkflowTitle, $tourModuleWorkflowContent, 'top'),
                $tourCustomStep('form[action*="/admin/users"] .form-layout-columns', $tourModuleWorkflowTitle, $tourModuleWorkflowContent, 'top'),
                $tourCustomStep('#name, #email, #role', $tourModuleControlsTitle, $tourModuleControlsContent, 'bottom'),
                $tourCustomStep('.avatar-upload-container', $tourModuleTabsTitle, $tourModuleTabsContent, 'left'),
                $tourCustomStep('form[action*="/admin/users"] .form-actions', $tourModuleActionsTitle, $tourModuleActionsContent, 'top'),
            ],
        ],
        'settings' => [
            'routes' => ['admin/settings'],
            'steps' => [
                $tourCustomStep('[data-settings-tabs]', $tourModuleTabsTitle, $tourModuleTabsContent, 'bottom'),
                $tourCustomStep('[data-tour-target="settings-branding"]', $tourBrandingTitle, $tourBrandingContent, 'top'),
                $tourCustomStep('[data-tour-target="settings-branding-translations"]', $tourModuleTranslationsTitle, $tourModuleTranslationsContent, 'left'),
                [
                    'selector' => '[data-tour-target="settings-guided-tour"]',
                    'title' => $tourSettingsTitle,
                    'content' => $tourSettingsContent,
                    'placement' => 'top',
                ],
                $tourCustomStep('.settings-inline-actions, .settings-guided-tour-actions', $tourModuleActionsTitle, $tourModuleActionsContent, 'top'),
                $tourCustomStep('.settings-system-overview, .settings-path-list', $tourModuleReportTitle, $tourModuleReportContent, 'top'),
                $tourCustomStep('.form-actions.form-actions-divider', $tourModuleActionsTitle, $tourModuleActionsContent, 'top'),
            ],
        ],
        'languages' => [
            'routes' => ['admin/languages'],
            'steps' => [
                $tourCustomStep('.language-actions-grid', $tourModuleActionsTitle, $tourModuleActionsContent, 'bottom'),
                $tourCustomStep('#code_quick, #name_quick, #direction_quick', $tourModuleControlsTitle, $tourModuleControlsContent, 'bottom'),
                $tourCustomStep('#language_file, #import_code', $tourModuleInstallerTitle, $tourModuleInstallerContent, 'bottom'),
                $tourCustomStep('.language-card-list', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('.language-card', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('.global-stats', $tourModuleStatsTitle, $tourModuleStatsContent, 'top'),
                $tourCustomStep('[data-action="scan-fill"], #btnScanFill', $tourModuleAuditTitle, $tourModuleAuditContent, 'left'),
                $tourCustomStep('.translations-controls', $tourModuleControlsTitle, $tourModuleControlsContent, 'bottom'),
                $tourCustomStep('#searchInput, #showOnlyMissing', $tourModuleFiltersTitle, $tourModuleFiltersContent, 'bottom'),
                $tourCustomStep('.module-card, .module-card-header', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('#modulesList', $tourModuleReportTitle, $tourModuleReportContent, 'top'),
                $tourCustomStep('.sticky-actions', $tourModuleWorkflowTitle, $tourModuleWorkflowContent, 'top'),
                $tourCustomStep('form[action*="/admin/languages"]', $tourModuleWorkflowTitle, $tourModuleWorkflowContent, 'top'),
                $tourCustomStep('#code, #name, #direction', $tourModuleControlsTitle, $tourModuleControlsContent, 'bottom'),
                $tourCustomStep('form[action*="/admin/languages"] .form-actions', $tourModuleActionsTitle, $tourModuleActionsContent, 'top'),
            ],
        ],
        'modules' => [
            'routes' => ['admin/modules'],
            'steps' => [
                $tourCustomStep('.modules-toolbar', $tourModuleFiltersTitle, $tourModuleFiltersContent, 'bottom'),
                $tourCustomStep('.modules-filter-group [data-filter-status]', $tourModuleFiltersTitle, $tourModuleFiltersContent, 'bottom'),
                $tourCustomStep('#moduleSearchInput, .modules-filter-group-right', $tourModuleControlsTitle, $tourModuleControlsContent, 'top'),
                $tourCustomStep('#moduleTypeFilter, #moduleLocationFilter', $tourModuleControlsTitle, $tourModuleControlsContent, 'top'),
                $tourCustomStep('.module-installer-card', $tourModuleInstallerTitle, $tourModuleInstallerContent, 'top'),
                $tourCustomStep('.module-installer-form', $tourModuleWorkflowTitle, $tourModuleWorkflowContent, 'top'),
                $tourCustomStep('.module-card-list', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('.module-card-header', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('.module-detail-grid, .module-detail-block', $tourModuleReportTitle, $tourModuleReportContent, 'top'),
                $tourCustomStep('.module-actions', $tourModuleActionsTitle, $tourModuleActionsContent, 'left'),
            ],
        ],
        'hooks' => [
            'routes' => ['admin/hooks'],
            'steps' => [
                $tourCustomStep('.hook-toolbar', $tourModuleStatsTitle, $tourModuleStatsContent, 'bottom'),
                $tourCustomStep('.hook-summary, .hook-summary-count', $tourModuleStatsTitle, $tourModuleStatsContent, 'bottom'),
                $tourCustomStep('.hook-controls, #hookSearchInput', $tourModuleControlsTitle, $tourModuleControlsContent, 'top'),
                $tourCustomStep('.hook-filter-row', $tourModuleFiltersTitle, $tourModuleFiltersContent, 'top'),
                $tourCustomStep('#hookGroupFilter, #hookListenerFilter', $tourModuleFiltersTitle, $tourModuleFiltersContent, 'top'),
                $tourCustomStep('.hook-accordion', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('.hook-accordion-header', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('.hook-table', $tourModuleReportTitle, $tourModuleReportContent, 'top'),
                $tourCustomStep('.hook-row, .hook-listeners', $tourModuleReportTitle, $tourModuleReportContent, 'top'),
            ],
        ],
        'themes' => [
            'routes' => ['admin/themes'],
            'steps' => [
                $tourCustomStep('.themes-toolbar', $tourModuleFiltersTitle, $tourModuleFiltersContent, 'bottom'),
                $tourCustomStep('.themes-filter-group [data-theme-status]', $tourModuleFiltersTitle, $tourModuleFiltersContent, 'bottom'),
                $tourCustomStep('#themeSearchInput, .themes-filter-group-right', $tourModuleControlsTitle, $tourModuleControlsContent, 'top'),
                $tourCustomStep('#themeTypeFilter, #themeCategoryFilter, #themeColorFilter, #themePriceFilter', $tourModuleControlsTitle, $tourModuleControlsContent, 'top'),
                $tourCustomStep('.theme-installer-card', $tourModuleInstallerTitle, $tourModuleInstallerContent, 'top'),
                $tourCustomStep('.module-installer-form', $tourModuleWorkflowTitle, $tourModuleWorkflowContent, 'top'),
                $tourCustomStep('.themes-grid', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('.theme-card', $tourModuleListTitle, $tourModuleListContent, 'top'),
                $tourCustomStep('.theme-actions', $tourModuleActionsTitle, $tourModuleActionsContent, 'left'),
                $tourCustomStep('.theme-customize-grid', $tourModuleWorkflowTitle, $tourModuleWorkflowContent, 'top'),
                $tourCustomStep('.theme-color-row', $tourModuleControlsTitle, $tourModuleControlsContent, 'bottom'),
                $tourCustomStep('#custom_css', $tourModuleTabsTitle, $tourModuleTabsContent, 'top'),
                $tourCustomStep('#preview-box', $tourModuleReportTitle, $tourModuleReportContent, 'top'),
                $tourCustomStep('.theme-customize-actions', $tourModuleActionsTitle, $tourModuleActionsContent, 'top'),
            ],
        ],
    ],
    'labels' => [
        'next' => __('next', 'Core'),
        'previous' => __('previous', 'Core'),
        'skip' => __('admin_tour_skip', 'Core'),
        'finish' => __('admin_tour_finish', 'Core'),
        'close' => __('close', 'Core'),
        'promptTitle' => __('admin_tour_prompt_title', 'Core'),
        'promptMessage' => __('admin_tour_prompt_message', 'Core'),
        'promptStart' => __('admin_tour_prompt_start', 'Core'),
        'promptQuit' => __('admin_tour_prompt_quit', 'Core'),
        'promptDisableHint' => __('admin_tour_prompt_disable_hint', 'Core'),
        'stepCounter' => __('admin_tour_step_counter', 'Core'),
        'completedToast' => __('admin_tour_completed_toast', 'Core'),
        'resetToast' => __('admin_tour_reset_toast', 'Core'),
        'errorToast' => __('admin_tour_error_toast', 'Core'),
    ],
];
$guidedTourConfigJson = json_encode(
    $guidedTourConfig,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<div id="flatcms-guided-tour-config" data-guided-tour-config="<?= htmlspecialchars((string) $guidedTourConfigJson, ENT_QUOTES, 'UTF-8') ?>" hidden></div>
<script src="<?= asset('js/admin/guided-tour.js') ?>?v=<?= filemtime(BASE_PATH . '/public/assets/js/admin/guided-tour.js') ?>"></script>
