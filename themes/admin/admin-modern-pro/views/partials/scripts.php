<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$adminToastConfig = [
    'title_success' => __('success', 'Core'),
    'title_warning' => __('info', 'Core'),
    'title_error' => __('toast_error_title', 'Core'),
    'close_label' => __('close', 'Core'),
];
?>
<div
    id="flatcms-admin-toast-config"
    data-title-success="<?= e((string) $adminToastConfig['title_success']) ?>"
    data-title-warning="<?= e((string) $adminToastConfig['title_warning']) ?>"
    data-title-error="<?= e((string) $adminToastConfig['title_error']) ?>"
    data-close-label="<?= e((string) $adminToastConfig['close_label']) ?>"
    hidden
></div>
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
<?php
$adminFooterAssetsHtml = \App\Core\HookAssets::render('admin.assets.footer', [
    'settings' => \App\Core\FlatFile::settings(),
    'locale' => $locale ?? locale(),
    'auth_user' => $auth_user ?? null,
]);
?>
<?= $adminFooterAssetsHtml !== '' ? $adminFooterAssetsHtml . PHP_EOL : '' ?>
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
$guidedTourModuleTours = guided_tour_collect_module_tours();

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
    'moduleTours' => $guidedTourModuleTours,
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
