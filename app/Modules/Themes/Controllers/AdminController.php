<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Themes\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Core\FlatFile;
use App\Modules\Trash\Services\TrashService;

class AdminController extends BaseController
{
    private string $themesPath;
    private string $publicThemesPath;
    private string $tmpPath;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Themes');
        $this->themesPath = BASE_PATH . '/themes';
        $this->publicThemesPath = BASE_PATH . '/public/themes';
        $this->tmpPath = BASE_PATH . '/storage/tmp/themes';
    }

    public function index(): void
    {
        $this->syncThemesFromPublic('admin');
        $this->syncThemesFromPublic('frontend');

        $adminThemes = $this->getThemes('admin');
        $frontendThemes = $this->getThemes('frontend');

        $settings = FlatFile::settings();
        $activeAdmin = $settings['admin_theme'] ?? 'admin-modern-pro';
        $activeFrontend = $settings['frontend_theme'] ?? 'default';

        $this->render('Themes/Views/admin/index', [
            'pageTitle' => __('themes', 'Themes'),
            'adminThemes' => $adminThemes,
            'frontendThemes' => $frontendThemes,
            'activeAdmin' => $activeAdmin,
            'activeFrontend' => $activeFrontend,
        ], 'admin.main');
    }

    public function install(): void
    {
        if (!$this->authorize('themes.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        if (!class_exists(\ZipArchive::class)) {
            $this->session->flash('error', __('themes_zip_missing', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $file = $_FILES['theme_zip'] ?? null;
        if (!is_array($file) || empty($file['tmp_name'])) {
            $this->session->flash('error', __('themes_no_file', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->session->flash('error', __('themes_upload_failed', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $extension = strtolower(
            pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)
        );

        if ($extension !== 'zip') {
            $this->session->flash('error', __('themes_invalid_format', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        if (!is_dir($this->tmpPath)) {
            if (!mkdir($this->tmpPath, 0755, true) && !is_dir($this->tmpPath)) {
                $this->session->flash('error', __('themes_upload_failed', 'Themes'));
                $this->redirect(url('/admin/themes'));
                return;
            }
        }

        $zipPath = $this->tmpPath . '/' . uniqid('theme_', true) . '.zip';
        if (!move_uploaded_file($file['tmp_name'], $zipPath)) {
            $this->session->flash('error', __('themes_upload_failed', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $extractDir = $this->tmpPath . '/' . uniqid('theme_', true);
        if (!mkdir($extractDir, 0755, true) && !is_dir($extractDir)) {
            @unlink($zipPath);
            $this->session->flash('error', __('themes_zip_extract_failed', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_zip_open_failed', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        if (!$this->validateZipEntries($zip)) {
            $zip->close();
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_zip_invalid', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_zip_extract_failed', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $zip->close();

        $themeJson = $this->findThemeManifest($extractDir);
        if ($themeJson === null) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_manifest_missing', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $manifest = $this->readManifest($themeJson);
        if ($manifest === []) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_manifest_invalid', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $themeDir = dirname($themeJson);
        $themeName = basename($themeDir);

        if ($themeDir === $extractDir) {
            $themeName = $this->resolveThemeName($manifest);
        }

        $themeName = preg_replace(
            '/[^a-zA-Z0-9_-]/',
            '',
            (string) $themeName
        );

        if ($themeName === '') {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_manifest_invalid', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $type = strtolower(trim((string) ($manifest['type'] ?? '')));
        if (!in_array($type, ['admin', 'frontend'], true)) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('invalid_theme_type', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        if (
            $this->hasExternalFiles($extractDir, $themeDir)
            || $this->containsSymlinks($themeDir)
        ) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_zip_outside_scope', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        /*
         * Canaux de distribution reconnus.
         *
         * - flatcms    : thème officiel FlatCMS signé
         * - extension  : thème tiers
         * - community  : thème communautaire
         * - marketplace: thème distribué via la marketplace
         */
        $origin = strtolower(trim(
            (string) ($manifest['origin'] ?? 'extension')
        ));

        $allowedOrigins = [
            'flatcms',
            'extension',
            'community',
            'marketplace',
        ];

        if (!in_array($origin, $allowedOrigins, true)) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_manifest_invalid', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        /*
         * Lecture stricte du marqueur officiel.
         *
         * Avec JSON, ce champ doit normalement être un booléen.
         * FILTER_NULL_ON_FAILURE permet de refuser une valeur incohérente.
         */
        $official = filter_var(
            $manifest['official'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($official === null) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_manifest_invalid', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        /*
         * Le couple official/origin doit être cohérent :
         *
         * officiel :
         *   official = true
         *   origin   = flatcms
         *
         * tiers :
         *   official = false
         *   origin   = extension, community ou marketplace
         */
        $hasOfficialOrigin = $origin === 'flatcms';

        if ($official !== $hasOfficialOrigin) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_manifest_invalid', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        /*
         * La signature cryptographique est obligatoire uniquement
         * pour un thème officiel FlatCMS.
         */
        if ($official === true) {
            $signature = trim((string) ($manifest['signature'] ?? ''));

            if ($signature === '') {
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('themes_signature_missing', 'Themes'));
                $this->redirect(url('/admin/themes'));
                return;
            }

            if (!extension_loaded('openssl')) {
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('themes_openssl_missing', 'Themes'));
                $this->redirect(url('/admin/themes'));
                return;
            }

            $publicKey = trim((string) config(
                'extensions.official_public_key',
                ''
            ));

            if ($publicKey === '') {
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('themes_public_key_missing', 'Themes'));
                $this->redirect(url('/admin/themes'));
                return;
            }

            if (!$this->verifyManifestSignature($manifest, $publicKey)) {
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('themes_signature_invalid', 'Themes'));
                $this->redirect(url('/admin/themes'));
                return;
            }
        }

        $targetBase = $this->themesPath . '/' . $type;
        if (!is_dir($targetBase)) {
            if (!mkdir($targetBase, 0755, true) && !is_dir($targetBase)) {
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('themes_copy_failed', 'Themes'));
                $this->redirect(url('/admin/themes'));
                return;
            }
        }

        $destination = $targetBase . '/' . $themeName;
        if (file_exists($destination)) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash(
                'error',
                __('themes_exists', 'Themes', ['theme' => $themeName])
            );
            $this->redirect(url('/admin/themes'));
            return;
        }

        if (!$this->copyDirectory($themeDir, $destination)) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->session->flash('error', __('themes_copy_failed', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $this->syncThemeAssetsToPublic(
            $type,
            $themeName,
            $destination
        );

        $this->cleanupInstall($zipPath, $extractDir);
        $this->session->flash(
            'success',
            __('themes_install_success', 'Themes', ['theme' => $themeName])
        );
        $this->redirect(url('/admin/themes'));
    }

    public function activate(string $type, string $name): void
    {
        if (!$this->authorize('themes.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        if (!in_array($type, ['admin', 'frontend'], true)) {
            $this->session->flash('error', __('invalid_theme_type', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $themePath = $this->resolveThemePath($type, $name);
        if ($themePath === null) {
            $this->session->flash('error', __('theme_not_found', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $settings = FlatFile::settings();
        hook_run('themes.before_activate', [
            'type' => $type,
            'theme' => $name,
            'settings' => $settings,
        ]);

        $settings[$type . '_theme'] = $name;
        FlatFile::saveSettings($settings);

        hook_run('themes.after_activate', [
            'type' => $type,
            'theme' => $name,
            'settings' => $settings,
        ]);

        $this->session->flash('success', __('theme_activated', 'Themes'));
        $this->redirect(url('/admin/themes'));
    }

    public function trash(string $type, string $name): void
    {
        if (!$this->authorize('themes.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        if (!in_array($type, ['admin', 'frontend'], true)) {
            $this->session->flash('error', __('invalid_theme_type', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $this->syncThemesFromPublic($type);

        $themePath = $this->resolveThemePath($type, $name);
        if ($themePath === null) {
            $this->session->flash('error', __('theme_not_found', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $settings = FlatFile::settings();
        $activeTheme = $type === 'admin'
            ? (string) ($settings['admin_theme'] ?? 'admin-modern-pro')
            : (string) ($settings['frontend_theme'] ?? 'default');

        if ($activeTheme === $name) {
            $this->session->flash(
                'error',
                __('theme_move_to_trash_active_blocked', 'Themes')
            );
            $this->redirect(url('/admin/themes'));
            return;
        }

        $themes = $this->getThemes($type);
        $theme = is_array($themes[$name] ?? null) ? $themes[$name] : [];

        $rootPath = $this->themesPath . '/' . $type . '/' . $name;
        if (!is_dir($rootPath)) {
            $rootPath = $themePath;
        }

        $trash = new TrashService();
        $deletedBy = trim((string) (
            auth()['name']
            ?? auth()['email']
            ?? ''
        ));

        $archived = $trash->archiveTheme([
            'theme_type' => $type,
            'theme_name' => $name,
            'name' => (string) ($theme['name'] ?? $name),
            'description' => (string) ($theme['description'] ?? ''),
            'version' => (string) ($theme['version'] ?? ''),
            'author' => (string) ($theme['author'] ?? ''),
            'root_path' => $rootPath,
            'public_path' => $this->publicThemesPath . '/' . $type . '/' . $name,
            'customization_path' => BASE_PATH . '/data/themes/' . $type . '_' . $name . '.json',
        ], $deletedBy);

        if (!is_array($archived)) {
            $this->session->flash(
                'error',
                __('theme_move_to_trash_failed', 'Themes')
            );
            $this->redirect(url('/admin/themes'));
            return;
        }

        $this->session->flash(
            'success',
            __('theme_move_to_trash_success', 'Themes')
        );
        $this->redirect(url('/admin/themes'));
    }

    public function customize(string $type, string $name): void
    {
        if (!$this->authorize('themes.edit')) {
            return;
        }

        $themePath = $this->resolveThemePath($type, $name);
        if ($themePath === null) {
            $this->session->flash('error', __('theme_not_found', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $configPath = $themePath . '/theme.json';
        $config = [];

        if (file_exists($configPath)) {
            $config = json_read($configPath) ?? [];
        }

        $config = $this->localizeThemeConfig($config);

        if (!$this->themeSupportsCustomization($config)) {
            $this->session->flash(
                'error',
                __('theme_customization_unavailable', 'Themes')
            );
            $this->redirect(url('/admin/themes'));
            return;
        }

        $customPath = BASE_PATH . "/data/themes/{$type}_{$name}.json";
        $customizationExists = is_file($customPath);
        $custom = $customizationExists ? (json_read($customPath) ?? []) : [];

        // First-run fallback: themes generated by FlatCMS Theme Generator v1.3+
        // may ship with a custom_css entry in theme.json. Pre-fill the admin
        // textarea only before any customization file exists, so clearing and
        // saving the field later remains a deliberate user choice.
        $themeCustomCss = trim((string) ($config['custom_css'] ?? ''));
        if (
            !$customizationExists
            && trim((string) ($custom['custom_css'] ?? '')) === ''
            && $themeCustomCss !== ''
        ) {
            $custom['custom_css'] = $themeCustomCss;
        }

        $this->render('Themes/Views/admin/customize', [
            'pageTitle' => __('customize_theme', 'Themes'),
            'type' => $type,
            'name' => $name,
            'displayName' => (string) ($config['name'] ?? ucfirst($name)),
            'config' => $config,
            'custom' => $custom,
            'buttonCustomization' => is_array($custom['buttons'] ?? null) ? $custom['buttons'] : [],
            'badgeCustomization' => is_array($custom['badges'] ?? null) ? $custom['badges'] : [],
            'typographyCustomization' => is_array($custom['typography'] ?? null) ? $custom['typography'] : [],
            'supportsDualModeCustomization' => $this->themeSupportsDualModeCustomization($type, $name),
        ], 'admin.main');
    }

    public function saveCustomization(string $type, string $name): void
    {
        if (!$this->authorize('themes.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $themePath = $this->resolveThemePath($type, $name);
        if ($themePath === null) {
            $this->session->flash('error', __('theme_not_found', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $configPath = $themePath . '/theme.json';
        $config = file_exists($configPath)
            ? (json_read($configPath) ?? [])
            : [];

        if (!$this->themeSupportsCustomization($config)) {
            $this->session->flash(
                'error',
                __('theme_customization_unavailable', 'Themes')
            );
            $this->redirect(url('/admin/themes'));
            return;
        }

        $custom = [
            'colors' => [
                'primary' => $this->request->input('primary'),
                'secondary' => $this->request->input('secondary'),
                'accent' => $this->request->input('accent'),
                'background' => $this->request->input('background'),
                'surface' => $this->request->input('surface'),
                'text' => $this->request->input('text'),
                'text_muted' => $this->request->input('text_muted'),
                'border' => $this->request->input('border'),
            ],
            'custom_css' => $this->request->input('custom_css'),
            'buttons' => [
                'style' => $this->inputChoice('button_style', ['theme', 'classic', 'soft', 'elevated'], 'theme'),
                'shape' => $this->inputChoice('button_shape', ['theme', 'sharp', 'rounded', 'pill'], 'theme'),
                'weight' => $this->inputChoice('button_weight', ['theme', 'medium', 'semibold', 'bold'], 'theme'),
            ],
            'badges' => [
                'style' => $this->inputChoice('badge_style', ['theme', 'soft', 'solid', 'outline'], 'theme'),
                'shape' => $this->inputChoice('badge_shape', ['theme', 'sharp', 'rounded', 'pill'], 'theme'),
                'weight' => $this->inputChoice('badge_weight', ['theme', 'medium', 'semibold', 'bold'], 'theme'),
            ],
            'typography' => [
                'body_family' => $this->inputChoice('typography_body_family', ['theme', 'system', 'sans', 'geometric', 'editorial'], 'theme'),
                'heading_family' => $this->inputChoice('typography_heading_family', ['theme', 'system', 'sans', 'geometric', 'editorial'], 'theme'),
                'scale' => $this->inputChoice('typography_scale', ['theme', 'compact', 'balanced', 'comfortable'], 'theme'),
                'heading_weight' => $this->inputChoice('typography_heading_weight', ['theme', 'semibold', 'bold', 'black'], 'theme'),
            ],
        ];

        if ($this->themeSupportsDualModeCustomization($type, $name)) {
            $custom['light_colors'] = [
                'primary' => $this->request->input('light_primary'),
                'secondary' => $this->request->input('light_secondary'),
                'accent' => $this->request->input('light_accent'),
                'background' => $this->request->input('light_background'),
                'surface' => $this->request->input('light_surface'),
                'text' => $this->request->input('light_text'),
                'text_muted' => $this->request->input('light_text_muted'),
                'border' => $this->request->input('light_border'),
            ];
        }

        $customPath = BASE_PATH . '/data/themes';
        if (!is_dir($customPath)) {
            mkdir($customPath, 0755, true);
        }

        json_write(
            "{$customPath}/{$type}_{$name}.json",
            $custom
        );

        $this->session->flash(
            'success',
            __('customization_saved', 'Themes')
        );
        $this->redirect(
            url("/admin/themes/{$type}/{$name}/customize")
        );
    }

    private function inputChoice(string $key, array $allowed, string $default): string
    {
        $value = trim((string) $this->request->input($key, $default));
        return in_array($value, $allowed, true) ? $value : $default;
    }

    public function resetCustomization(string $type, string $name): void
    {
        if (!$this->authorize('themes.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $themePath = $this->resolveThemePath($type, $name);
        if ($themePath === null) {
            $this->session->flash('error', __('theme_not_found', 'Themes'));
            $this->redirect(url('/admin/themes'));
            return;
        }

        $customPath = BASE_PATH . "/data/themes/{$type}_{$name}.json";

        if (is_file($customPath)) {
            @unlink($customPath);
        }

        $this->session->flash(
            'success',
            __('customization_reset_success', 'Themes')
        );
        $this->redirect(
            url("/admin/themes/{$type}/{$name}/customize")
        );
    }

    private function getThemes(string $type): array
    {
        $themes = [];
        $themesPath = $this->themesPath . "/{$type}";

        if (!is_dir($themesPath)) {
            return $themes;
        }

        foreach (glob($themesPath . '/*', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            $configFile = $dir . '/theme.json';

            $config = [
                'name' => ucfirst($name),
                'description' => '',
                'version' => '1.0.0',
                'author' => 'Unknown',
                'screenshot' => null,
            ];

            if (file_exists($configFile)) {
                $config = array_merge(
                    $config,
                    json_read($configFile) ?? []
                );
            }

            $config = $this->localizeThemeConfig($config);

            $this->syncThemeAssetsToPublic($type, $name, $dir);
            $publicThemeDir = $this->publicThemesPath . "/{$type}/{$name}";

            foreach (
                ['screenshot.png', 'screenshot.jpg', 'preview.png', 'preview.jpg']
                as $img
            ) {
                if (file_exists($publicThemeDir . '/assets/' . $img)) {
                    $config['screenshot'] = "/themes/{$type}/{$name}/assets/{$img}";
                    break;
                }
            }

            $themes[$name] = $config;
        }

        uksort($themes, function (string $a, string $b): int {
            if ($a === 'default') {
                return -1;
            }

            if ($b === 'default') {
                return 1;
            }

            return strcasecmp($a, $b);
        });

        return $themes;
    }

    private function resolveThemePath(string $type, string $name): ?string
    {
        $rootPath = $this->themesPath . "/{$type}/{$name}";
        if (is_dir($rootPath)) {
            return $rootPath;
        }

        $legacyPath = $this->publicThemesPath . "/{$type}/{$name}";
        if (is_dir($legacyPath)) {
            return $legacyPath;
        }

        return null;
    }

    private function syncThemesFromPublic(string $type): void
    {
        $legacyPath = $this->publicThemesPath . "/{$type}";

        if (!is_dir($legacyPath)) {
            return;
        }

        $rootPath = $this->themesPath . "/{$type}";
        if (!is_dir($rootPath)) {
            mkdir($rootPath, 0755, true);
        }

        foreach (glob($legacyPath . '/*', GLOB_ONLYDIR) as $legacyThemeDir) {
            $name = basename($legacyThemeDir);
            $hasManifest = file_exists($legacyThemeDir . '/theme.json');
            $hasViews = is_dir($legacyThemeDir . '/views');

            if (!$hasManifest && !$hasViews) {
                continue;
            }

            $target = $rootPath . '/' . $name;

            if (!is_dir($target)) {
                $this->copyDirectory($legacyThemeDir, $target);
            }

            $this->syncThemeAssetsToPublic(
                $type,
                $name,
                $target
            );
        }
    }

    private function syncThemeAssetsToPublic(
        string $type,
        string $name,
        string $rootThemeDir
    ): void {
        $publicThemeDir = $this->publicThemesPath . "/{$type}/{$name}";

        if (!is_dir($publicThemeDir)) {
            mkdir($publicThemeDir, 0755, true);
        }

        $assetsDir = $rootThemeDir . '/assets';
        $publicAssetsDir = $publicThemeDir . '/assets';

        if (is_dir($assetsDir)) {
            $this->copyDirectory($assetsDir, $publicAssetsDir);
        }

        foreach (
            ['screenshot.png', 'screenshot.jpg', 'preview.png', 'preview.jpg']
            as $img
        ) {
            $source = $rootThemeDir . '/' . $img;

            if (!file_exists($source) && is_dir($assetsDir)) {
                $alt = $assetsDir . '/' . $img;

                if (file_exists($alt)) {
                    $source = $alt;
                }
            }

            if (file_exists($source)) {
                @copy($source, $publicAssetsDir . '/' . $img);
            }
        }
    }

    private function validateZipEntries(\ZipArchive $zip): bool
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);

            if ($entry === false) {
                continue;
            }

            $entry = str_replace('\\', '/', $entry);

            if (
                str_contains($entry, '../')
                || str_starts_with($entry, '/')
                || str_starts_with($entry, '../')
            ) {
                return false;
            }

            if (preg_match('#^[A-Za-z]:#', $entry)) {
                return false;
            }

            if (
                str_contains($entry, 'app/')
                || str_contains($entry, 'public/')
                || str_contains($entry, 'themes/')
            ) {
                return false;
            }
        }

        return true;
    }

    private function hasExternalFiles(
        string $extractDir,
        string $themeDir
    ): bool {
        $extractReal = realpath($extractDir);
        $themeReal = realpath($themeDir);

        if ($extractReal === false || $themeReal === false) {
            return true;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $extractReal,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();

            if ($path === $extractReal) {
                continue;
            }

            $real = realpath($path);
            if ($real === false) {
                return true;
            }

            if (!str_starts_with($real, $themeReal)) {
                return true;
            }
        }

        return false;
    }

    private function containsSymlinks(string $themeDir): bool
    {
        $themeReal = realpath($themeDir);

        if ($themeReal === false) {
            return true;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $themeReal,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isLink()) {
                return true;
            }
        }

        return false;
    }

    private function findThemeManifest(string $basePath): ?string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $basePath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {
            if ($file->getFilename() === 'theme.json') {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function readManifest(string $manifestPath): array
    {
        $content = file_get_contents($manifestPath);
        $data = json_decode($content ?: '', true);

        return is_array($data) ? $data : [];
    }

    private function resolveThemeName(array $manifest): string
    {
        $slug = trim((string) ($manifest['slug'] ?? ''));
        $name = $slug !== ''
            ? $slug
            : trim((string) ($manifest['name'] ?? ''));

        $name = preg_replace(
            '/[^a-zA-Z0-9_-]/',
            '',
            $name
        );

        return $name ?? '';
    }

    private function themeSupportsCustomization(array $config): bool
    {
        $supports = $config['supports'] ?? null;

        if (
            is_array($supports)
            && array_key_exists('customization', $supports)
        ) {
            return (bool) $supports['customization'];
        }

        $themeColors = $config['colors'] ?? null;
        if (is_array($themeColors) && $themeColors !== []) {
            return true;
        }

        $features = $config['features'] ?? null;
        if (is_array($features)) {
            foreach ($features as $feature) {
                if (
                    in_array(
                        (string) $feature,
                        ['customization', 'theme-customization'],
                        true
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    private function themeSupportsDualModeCustomization(
        string $type,
        string $name
    ): bool {
        if (!in_array($type, ['admin', 'frontend'], true)) {
            return false;
        }

        return in_array(
            $name,
            ['modern-pro', 'admin-modern-pro'],
            true
        );
    }

    private function localizeThemeConfig(array $config): array
    {
        $translations = $config['translations'] ?? null;

        if (!is_array($translations) || $translations === []) {
            return $config;
        }

        $uiLocale = locale();
        $normalizedLocale = \App\Core\I18n::normalizeLocaleTag($uiLocale);

        $fallbackLocales = [
            $uiLocale,
            str_replace('_', '-', $normalizedLocale),
            str_replace('-', '_', $uiLocale),
            'en-US',
            'fr-FR',
        ];

        $selected = null;

        foreach ($fallbackLocales as $candidate) {
            if (!is_string($candidate) || $candidate === '') {
                continue;
            }

            if (is_array($translations[$candidate] ?? null)) {
                $selected = $translations[$candidate];
                break;
            }
        }

        if (!is_array($selected)) {
            return $config;
        }

        foreach (['name', 'description'] as $field) {
            $value = trim((string) ($selected[$field] ?? ''));

            if ($value !== '') {
                $config[$field] = $value;
            }
        }

        return $config;
    }

    private function verifyManifestSignature(
        array $manifest,
        string $publicKey
    ): bool {
        $signature = (string) ($manifest['signature'] ?? '');

        if ($signature === '') {
            return false;
        }

        $payloadData = $manifest;
        unset($payloadData['signature']);

        $payloadData = $this->normalizeManifestData($payloadData);

        $payload = json_encode(
            $payloadData,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($payload === false) {
            return false;
        }

        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            return false;
        }

        $algo = $this->getSignatureAlgo();

        return openssl_verify(
            $payload,
            $decodedSignature,
            $publicKey,
            $algo
        ) === 1;
    }

    private function normalizeManifestData(array $data): array
    {
        ksort($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalizeManifestData($value);
            }
        }

        return $data;
    }

    private function getSignatureAlgo(): int
    {
        $algo = strtolower(
            (string) config('extensions.signature_algo', 'sha256')
        );

        return match ($algo) {
            'sha384' => OPENSSL_ALGO_SHA384,
            'sha512' => OPENSSL_ALGO_SHA512,
            default => OPENSSL_ALGO_SHA256,
        };
    }

    private function copyDirectory(
        string $source,
        string $destination
    ): bool {
        $source = rtrim($source, '/\\');
        $destination = rtrim($destination, '/\\');

        if (!is_dir($source)) {
            return false;
        }

        if (
            !is_dir($destination)
            && !mkdir($destination, 0755, true)
            && !is_dir($destination)
        ) {
            return false;
        }

        $sourcePrefixLength = strlen($source) + 1;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $source,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $sourcePath = $item->getPathname();
            $relativePath = substr(
                $sourcePath,
                $sourcePrefixLength
            );

            if ($relativePath === false || $relativePath === '') {
                continue;
            }

            $target = $destination
                . DIRECTORY_SEPARATOR
                . str_replace(
                    ['/', '\\'],
                    DIRECTORY_SEPARATOR,
                    $relativePath
                );

            if ($item->isDir()) {
                if (
                    !is_dir($target)
                    && !mkdir($target, 0755, true)
                    && !is_dir($target)
                ) {
                    return false;
                }

                continue;
            }

            $targetDirectory = dirname($target);

            if (
                !is_dir($targetDirectory)
                && !mkdir($targetDirectory, 0755, true)
                && !is_dir($targetDirectory)
            ) {
                return false;
            }

            if (!copy($sourcePath, $target)) {
                return false;
            }
        }

        return true;
    }

    private function cleanupInstall(
        string $zipPath,
        string $extractDir
    ): void {
        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }

        $this->removeDirectory($extractDir);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }
}
