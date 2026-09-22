<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Install/Controllers/InstallController.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Install\Controllers;

use App\Modules\Install\Services\InstallationService;
use App\Modules\Install\Support\Lang;
use App\Modules\Users\Support\UserName;

final class InstallController
{
    private const INSTALL_VERSION_FALLBACK = '1.0.0';

    /**
     * Liste des étapes de l'installation
     */
    private const STEPS = [
        1 => 'welcome',
        2 => 'license',
        3 => 'requirements',
        4 => 'permissions',
        5 => 'database',
        6 => 'admin',
        7 => 'site',
        8 => 'design',      // NOUVEAU : Choix des thèmes
        9 => 'sample',
        10 => 'complete',
    ];

    /**
     * Étape minimale requise pour chaque action POST.
     */
    private const ACTION_MIN_STEP = [
        'start' => 1,
        'accept_license' => 2,
        'check_requirements' => 3,
        'check_permissions' => 4,
        'save_database' => 5,
        'create_admin' => 6,
        'save_site' => 7,
        'save_design' => 8,
        'install_sample' => 9,
        'finalize' => 10,
    ];

    private int $currentStep = 1;
    private array $errors = [];
    private array $environment = [];

    public function __construct()
    {
        $this->detectEnvironment();
    }

    public function handle(string $uri): void
    {
        $uriPath = (string) parse_url($uri, PHP_URL_PATH);
        $path = str_replace('/install', '', $uriPath);
        $path = trim($path, '/');

        // Gérer le changement de langue
        if (isset($_GET['lang']) && Lang::isAvailable($_GET['lang'])) {
            $_SESSION['install_lang'] = $_GET['lang'];
        }

        // Initialiser le système de traduction dès le début
        $lang = $_SESSION['install_lang'] ?? Lang::detectBrowserLang();
        $_SESSION['install_lang'] = $lang;
        Lang::init($lang);
        $_SESSION['install_lang'] = Lang::getCurrentLang();

        // Défense en profondeur: le lock bloque toute réouverture de l'installateur.
        if (is_file($this->getInstallLockPath())) {
            http_response_code(403);
            $this->pushError(Lang::get('install.error_already_installed'));
            $this->render('complete', ['step' => 10]);
            return;
        }

        // Support du paramètre GET ?step=X (mode sans .htaccess)
        if (isset($_GET['step']) && is_numeric($_GET['step'])) {
            $path = (string)$_GET['step'];
        }

        // Accès direct sans ?step= : réinitialiser la session d'installation
        // (évite de reprendre une session périmée après suppression de installed.lock)
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' && !isset($_GET['step']) && (
            $path === ''
            || preg_match('#(^|/)index\.php$#i', $path) === 1
            || preg_match('#(^|/)public/index\.php$#i', $path) === 1
        )) {
            unset(
                $_SESSION['install_step'],
                $_SESSION['install_admin'],
                $_SESSION['install_site'],
                $_SESSION['install_design'],
                $_SESSION['install_sample'],
                $_SESSION['install_environment'],
                $_SESSION['install_error'],
                $_SESSION['install_errors'],
                $_SESSION['install_csrf_token'],
                $_SESSION['user']
            );
        }

        $this->currentStep = (int) ($_SESSION['install_step'] ?? 1);
        if ($this->currentStep < 1 || $this->currentStep > 10) {
            $this->currentStep = 1;
            $_SESSION['install_step'] = 1;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        } else {
            $this->handleGet($path);
        }
    }

    private function handleGet(string $path): void
    {
        $step = $this->currentStep;

        if ($path !== '' && is_numeric($path)) {
            $requestedStep = (int)$path;
            if ($requestedStep <= $this->currentStep) {
                $step = $requestedStep;
            }
        }

        $stepName = self::STEPS[$step] ?? 'welcome';
        $this->render($stepName, ['step' => $step]);
    }

    private function handlePost(): void
    {
        $action = trim((string) ($_POST['action'] ?? ''));

        if ($action === '' || !isset(self::ACTION_MIN_STEP[$action])) {
            $this->pushError(Lang::get('install.error_invalid_request'));
            $this->redirectToStep($this->currentStep);
            return;
        }

        if (!$this->validateCsrfToken((string) ($_POST['csrf_token'] ?? ''))) {
            $this->pushError(Lang::get('install.error_invalid_csrf'));
            $this->redirectToStep($this->currentStep);
            return;
        }

        if (!$this->isActionAllowedAtCurrentStep($action)) {
            $this->pushError(Lang::get('install.error_step_not_allowed'));
            $this->redirectToStep($this->currentStep);
            return;
        }

        match ($action) {
            'start' => $this->processStart(),
            'accept_license' => $this->processLicense(),
            'check_requirements' => $this->processRequirements(),
            'check_permissions' => $this->processPermissions(),
            'save_database' => $this->processDatabase(),
            'create_admin' => $this->processAdmin(),
            'save_site' => $this->processSite(),
            'save_design' => $this->processDesign(),
            'install_sample' => $this->processSample(),
            'finalize' => $this->processFinalize(),
            default => $this->redirectToStep($this->currentStep),
        };
    }

    private function detectEnvironment(): void
    {
        $this->environment = [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_type' => $this->detectServerType(),
            'os' => PHP_OS_FAMILY,
            'os_detail' => php_uname(),
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'script_path' => dirname($_SERVER['SCRIPT_FILENAME'] ?? ''),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => get_loaded_extensions(),
            'writable_paths' => $this->checkWritablePaths(),
        ];

        $_SESSION['install_environment'] = $this->environment;
    }

    private function getInstallVersion(): string
    {
        static $cached = null;

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $cached = \App\Core\CoreManifest::version(self::INSTALL_VERSION_FALLBACK);
        return $cached;
    }

    private function detectServerType(): string
    {
        $software = strtolower($_SERVER['SERVER_SOFTWARE'] ?? '');
        if (str_contains($software, 'apache')) return 'apache';
        if (str_contains($software, 'nginx')) return 'nginx';
        if (str_contains($software, 'iis') || str_contains($software, 'microsoft')) return 'iis';
        if (str_contains($software, 'litespeed')) return 'litespeed';
        return 'unknown';
    }

    private function checkWritablePaths(): array
    {
        $data_path = defined('DATA_PATH') ? DATA_PATH : $this->getBasePath() . '/data';
        $storage_path = defined('STORAGE_PATH') ? STORAGE_PATH : $this->getBasePath() . '/storage';
        $public_path = defined('PUBLIC_PATH') ? PUBLIC_PATH : $this->getBasePath() . '/public';

        $paths = [
            'data' => $data_path,
            'storage' => $storage_path,
            'public/themes' => $public_path . '/themes',
            'public/uploads' => $public_path . '/uploads',
            'public/modules' => $public_path . '/modules',
        ];

        $results = [];
        foreach ($paths as $name => $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
            $results[$name] = [
                'path' => $path,
                'exists' => is_dir($path),
                'writable' => is_dir($path) && is_writable($path),
            ];
        }
        return $results;
    }

    private function redirectToStep(int $step): void
    {
        // Rediriger vers le script d'entrée réellement exécuté (plus robuste selon la config Nginx/Apache).
        header('Location: ' . $this->getInstallEntryUrl() . '?step=' . $step);
        exit;
    }

    private function render(string $view, array $data = []): void
    {
        $app_path = defined('APP_PATH') ? APP_PATH : $this->getBasePath() . '/app';
        $viewFile = $app_path . '/Modules/Install/Views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            echo Lang::get('install.error_view_not_found', ['view' => $view]);
            return;
        }

        // Charger le système de traduction
        $lang = $_SESSION['install_lang'] ?? Lang::detectBrowserLang();
        $_SESSION['install_lang'] = $lang;
        Lang::init($lang);
        $_SESSION['install_lang'] = Lang::getCurrentLang();

        $publicUrl = $this->getPublicUrl();

        $data['environment'] = $this->environment;
        $data['steps'] = self::STEPS;
        $data['version'] = $this->getInstallVersion();
        $data['configFiles'] = $_SESSION['install_config_files'] ?? [];
        $data['errors'] = $this->consumeErrors();
        $data['csrfToken'] = $this->ensureCsrfToken();
        $data['requirements'] = $this->buildRequirements();
        
        // Utiliser l'entrée actuelle évite les erreurs de chemin (/public/index.php vs /index.php).
        $data['installUrl'] = $this->getInstallEntryUrl();
        $data['publicUrl'] = $publicUrl;
        
        // URL du site sans suffixes techniques (/public ou /install)
        if (!isset($data['siteUrl']) || trim((string) $data['siteUrl']) === '') {
            $computedSiteUrl = preg_replace('#/(public|install)/?$#i', '', (string) $publicUrl);
            $data['siteUrl'] = ($computedSiteUrl === '' || $computedSiteUrl === null) ? '/' : $computedSiteUrl;
        }

        extract($data);

        $layoutFile = $app_path . '/Modules/Install/Views/layout.php';
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            include $viewFile;
        }
    }

    private function getPublicUrl(): string
    {
        $url = defined('PUBLIC_URL') ? (string) PUBLIC_URL : '';
        if ($url !== '') {
            return rtrim($url, '/');
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $script = str_replace('\\', '/', $script);
        $dir = rtrim(dirname($script), '/');
        if ($dir === '/' || $dir === '.') {
            $dir = '';
        }
        return $dir;
    }

    private function getInstallEntryUrl(): string
    {
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $script = str_replace('\\', '/', trim($script));
        if ($script === '') {
            return '/index.php';
        }
        if (!str_starts_with($script, '/')) {
            $script = '/' . $script;
        }
        return $script;
    }

    private function processStart(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }
        unset($_SESSION['user']);
        $_SESSION['install_step'] = 2;
        $this->redirectToStep(2);
    }

    private function processLicense(): void
    {
        if (!isset($_POST['accept_license'])) {
            $this->pushError(Lang::get('license.error_must_accept'));
            $this->redirectToStep(2);
            return;
        }
        $_SESSION['install_step'] = 3;
        $this->redirectToStep(3);
    }

    private function processRequirements(): void
    {
        $requirements = $this->buildRequirements();
        $failed = [];

        foreach ($requirements as $requirement) {
            if (!empty($requirement['required']) && empty($requirement['passed'])) {
                $failed[] = (string) ($requirement['name'] ?? 'Requirement');
            }
        }

        if ($failed !== []) {
            $this->pushError(Lang::get('install.error_requirements_not_met'));
            $this->pushErrors($failed);
            $this->redirectToStep(3);
            return;
        }

        $_SESSION['install_step'] = 4;
        $this->redirectToStep(4);
    }

    private function processPermissions(): void
    {
        $paths = $this->checkWritablePaths();
        $invalidPaths = [];

        foreach ($paths as $name => $info) {
            if (empty($info['exists']) || empty($info['writable'])) {
                $invalidPaths[] = Lang::get('install.error_permissions_path', ['path' => $name]);
            }
        }

        if ($invalidPaths !== []) {
            $this->pushError(Lang::get('install.error_permissions_not_met'));
            $this->pushErrors($invalidPaths);
            $this->redirectToStep(4);
            return;
        }

        $_SESSION['install_step'] = 5;
        $this->redirectToStep(5);
    }

    private function processDatabase(): void
    {
        $paths = $this->checkWritablePaths();
        foreach ($paths as $info) {
            if (empty($info['exists']) || empty($info['writable'])) {
                $this->pushError(Lang::get('install.error_storage_not_ready'));
                $this->redirectToStep(4);
                return;
            }
        }

        $_SESSION['install_step'] = 6;
        $this->redirectToStep(6);
    }

    private function processAdmin(): void
    {
        $firstName = trim($_POST['admin_first_name'] ?? '');
        $name = trim($_POST['admin_name'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $passwordConfirm = $_POST['admin_password_confirm'] ?? '';

        // Validation
        if (empty($firstName) || empty($name) || empty($email) || empty($password)) {
            $this->pushError(Lang::get('admin.error_all_required'));
            $this->redirectToStep(6);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->pushError(Lang::get('admin.error_invalid_email'));
            $this->redirectToStep(6);
            return;
        }

        if (strlen($password) < 8) {
            $this->pushError(Lang::get('admin.error_password_short'));
            $this->redirectToStep(6);
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->pushError(Lang::get('admin.error_passwords_dont_match'));
            $this->redirectToStep(6);
            return;
        }

        // Sauvegarder les données admin en session
        $_SESSION['install_admin'] = InstallationService::prepareAdmin([
            'first_name' => $firstName,
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $_SESSION['install_step'] = 7;
        $this->redirectToStep(7);
    }

    private function processSite(): void
    {
        $siteName = trim($_POST['site_name'] ?? 'FlatCMS');
        $siteDescription = trim($_POST['site_description'] ?? '');
        $siteUrl = trim($_POST['site_url'] ?? '');
        $timezone = $_POST['timezone'] ?? 'Europe/Paris';

        if ($siteName === '') {
            $this->pushError(Lang::get('install.error_invalid_site_name'));
            $this->redirectToStep(7);
            return;
        }

        if ($siteUrl !== '' && !preg_match('#^https?://#i', $siteUrl)) {
            $siteUrl = 'https://' . ltrim($siteUrl, '/');
        }

        $siteUrl = $this->normalizeSiteUrl($siteUrl);

        if ($siteUrl !== '' && !filter_var($siteUrl, FILTER_VALIDATE_URL)) {
            $this->pushError(Lang::get('install.error_invalid_site_url'));
            $this->redirectToStep(7);
            return;
        }

        if ($siteUrl === '') {
            $siteUrl = $this->guessSiteUrl();
        }

        if (!$this->isValidTimezone((string) $timezone)) {
            $timezone = 'Europe/Paris';
        }

        $_SESSION['install_site'] = [
            'name' => $siteName,
            'description' => $siteDescription,
            'url' => $siteUrl,
            'timezone' => $timezone,
        ];

        $_SESSION['install_step'] = 8;
        $this->redirectToStep(8);
    }

    private function processDesign(): void
    {
        $adminTheme = $_POST['admin_theme'] ?? 'admin-modern-pro';
        $frontendTheme = $_POST['frontend_theme'] ?? 'modern-pro';

        $allowedAdminThemes = ['admin-modern-pro', 'default'];
        $allowedFrontendThemes = ['modern-pro', 'default'];

        if (!in_array($adminTheme, $allowedAdminThemes, true) || !in_array($frontendTheme, $allowedFrontendThemes, true)) {
            $this->pushError(Lang::get('install.error_invalid_theme_choice'));
            $this->redirectToStep(8);
            return;
        }

        $_SESSION['install_design'] = [
            'admin_theme' => $adminTheme,
            'frontend_theme' => $frontendTheme,
        ];

        $_SESSION['install_step'] = 9;
        $this->redirectToStep(9);
    }

    private function processSample(): void
    {
        $_SESSION['install_sample'] = isset($_POST['install_sample']);
        $_SESSION['install_step'] = 10;
        
        // Lancer la finalisation
        $this->doFinalize();
    }

    private function processFinalize(): void
    {
        $this->doFinalize();
    }

    private function doFinalize(): void
    {
        $admin = $_SESSION['install_admin'] ?? null;
        $site = $_SESSION['install_site'] ?? null;
        $design = $_SESSION['install_design'] ?? null;
        $installSample = $_SESSION['install_sample'] ?? false;

        if (!$admin || !$site || !$design) {
            $this->pushError(Lang::get('install.error_missing_data'));
            $this->redirectToStep(1);
            return;
        }

        try {
            $result = (new InstallationService(
                $this->environment,
                Lang::getCurrentLang(),
                $this->getPublicUrl()
            ))->install($admin, $site, $design, (bool) $installSample);
            $adminUser = $result['admin'];
            $_SESSION['install_config_files'] = $result['config_files'];

            // 8. Nettoyer la session
            unset(
                $_SESSION['install_step'],
                $_SESSION['install_admin'],
                $_SESSION['install_site'],
                $_SESSION['install_design'],
                $_SESSION['install_sample'],
                $_SESSION['install_environment'],
                $_SESSION['install_error']
            );

            // Session admin cohérente après installation (évite une session stale d'une ancienne install).
            if (!empty($adminUser)) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    @session_regenerate_id(true);
                }
                unset($adminUser['password']);
                $_SESSION['user'] = UserName::forSession($adminUser);
            }

            $this->render('complete', [
                'step' => 10,
                'success' => true,
                'admin_email' => $admin['email'],
                'site_name' => (string) (\App\Core\FlatFile::settings()['site_name'] ?? $site['name']),
                'siteUrl' => $site['url'],
                'adminUrl' => $this->buildApplicationUrl('/admin', (string) $site['url']),
                'homeUrl' => $this->buildApplicationUrl('/', (string) $site['url']),
                'admin_theme' => $design['admin_theme'],
                'frontend_theme' => $design['frontend_theme'],
            ]);
            exit;

        } catch (\Throwable $e) {
            $this->pushError(Lang::get('install.error_prefix', ['message' => $e->getMessage()]));
            $this->redirectToStep(9);
        }
    }

    private function buildApplicationUrl(string $path, string $siteUrl): string
    {
        $baseUrl = rtrim(trim($siteUrl), '/');
        if ($baseUrl === '') {
            $baseUrl = $this->guessSiteUrl();
            $baseUrl = rtrim(trim($baseUrl), '/');
        }

        $path = '/' . ltrim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        $pretty = function_exists('flatcms_pretty_urls_enabled') ? flatcms_pretty_urls_enabled() : true;

        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $docRootReal = $docRoot !== '' ? (realpath($docRoot) ?: $docRoot) : '';
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        $baseReal = $basePath !== '' ? (realpath($basePath) ?: $basePath) : '';
        $publicPath = defined('PUBLIC_PATH') ? PUBLIC_PATH : '';
        $publicReal = $publicPath !== '' ? (realpath($publicPath) ?: $publicPath) : '';
        $docIsBase = $docRootReal !== '' && $baseReal !== '' && rtrim($docRootReal, '/') === rtrim($baseReal, '/');
        $docIsPublic = $docRootReal !== '' && $publicReal !== '' && rtrim($docRootReal, '/') === rtrim($publicReal, '/');
        $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
        if ($scriptFile !== '') {
            $scriptDir = dirname($scriptFile);
            $scriptDirReal = realpath($scriptDir) ?: $scriptDir;
            if (!$docIsBase && $baseReal !== '' && rtrim($scriptDirReal, '/') === rtrim($baseReal, '/')) {
                $docIsBase = true;
            }
            if (!$docIsPublic && $publicReal !== '' && rtrim($scriptDirReal, '/') === rtrim($publicReal, '/')) {
                $docIsPublic = true;
            }
        }

        if ($docIsBase && !$docIsPublic) {
            $pretty = false;
        }

        if (!$pretty) {
            return $baseUrl . '/index.php?path=' . ltrim($path, '/');
        }

        return $path === '/' ? ($baseUrl . '/') : ($baseUrl . $path);
    }

    private function isActionAllowedAtCurrentStep(string $action): bool
    {
        $requiredStep = self::ACTION_MIN_STEP[$action] ?? 1;
        return $this->currentStep >= $requiredStep;
    }

    private function ensureCsrfToken(): string
    {
        $token = (string) ($_SESSION['install_csrf_token'] ?? '');
        if ($token === '') {
            try {
                $token = bin2hex(random_bytes(32));
            } catch (\Throwable $e) {
                $token = hash('sha256', uniqid('install-csrf', true));
            }
            $_SESSION['install_csrf_token'] = $token;
        }
        return $token;
    }

    private function validateCsrfToken(string $token): bool
    {
        $stored = (string) ($_SESSION['install_csrf_token'] ?? '');
        if ($stored === '' || $token === '') {
            return false;
        }

        $valid = hash_equals($stored, $token);
        if ($valid) {
            unset($_SESSION['install_csrf_token']);
        }

        return $valid;
    }

    private function pushError(string $message): void
    {
        $message = trim($message);
        if ($message === '') {
            return;
        }
        $_SESSION['install_errors'][] = $message;
    }

    private function pushErrors(array $messages): void
    {
        foreach ($messages as $message) {
            $this->pushError((string) $message);
        }
    }

    private function consumeErrors(): array
    {
        $errors = [];

        $fromBatch = $_SESSION['install_errors'] ?? [];
        if (is_array($fromBatch)) {
            foreach ($fromBatch as $error) {
                $value = trim((string) $error);
                if ($value !== '') {
                    $errors[] = $value;
                }
            }
        }
        unset($_SESSION['install_errors']);

        if (!empty($_SESSION['install_error'])) {
            $errors[] = trim((string) $_SESSION['install_error']);
        }
        unset($_SESSION['install_error']);

        return array_values(array_unique(array_filter($errors, static fn($item) => $item !== '')));
    }

    private function buildRequirements(): array
    {
        return [
            [
                'name' => Lang::get('requirements.php_version'),
                'required' => true,
                'passed' => version_compare(PHP_VERSION, '8.3.0', '>='),
                'current' => PHP_VERSION,
                'minimum' => '8.3.0',
                'message' => Lang::get('requirements.php_version_msg'),
            ],
            [
                'name' => Lang::get('requirements.ext_json'),
                'required' => true,
                'passed' => extension_loaded('json'),
                'message' => Lang::get('requirements.ext_json_msg'),
            ],
            [
                'name' => Lang::get('requirements.ext_mbstring'),
                'required' => true,
                'passed' => extension_loaded('mbstring'),
                'message' => Lang::get('requirements.ext_mbstring_msg'),
            ],
            [
                'name' => Lang::get('requirements.ext_session'),
                'required' => true,
                'passed' => extension_loaded('session'),
                'message' => Lang::get('requirements.ext_session_msg'),
            ],
            [
                'name' => Lang::get('requirements.ext_fileinfo'),
                'required' => true,
                'passed' => extension_loaded('fileinfo'),
                'message' => Lang::get('requirements.ext_fileinfo_msg'),
            ],
            [
                'name' => Lang::get('requirements.ext_openssl'),
                'required' => false,
                'passed' => extension_loaded('openssl'),
                'message' => Lang::get('requirements.ext_openssl_msg'),
            ],
            [
                'name' => Lang::get('requirements.ext_gd'),
                'required' => false,
                'passed' => extension_loaded('gd'),
                'message' => Lang::get('requirements.ext_gd_msg'),
            ],
            [
                'name' => Lang::get('requirements.ext_zip'),
                'required' => false,
                'passed' => extension_loaded('zip'),
                'message' => Lang::get('requirements.ext_zip_msg'),
            ],
            [
                'name' => Lang::get('requirements.ext_curl'),
                'required' => false,
                'passed' => extension_loaded('curl'),
                'message' => Lang::get('requirements.ext_curl_msg'),
            ],
        ];
    }

    private function isValidTimezone(string $timezone): bool
    {
        if ($timezone === '') {
            return false;
        }

        return in_array($timezone, \DateTimeZone::listIdentifiers(), true);
    }

    private function guessSiteUrl(): string
    {
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
            || str_contains(strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')), 'https')
        );

        $scheme = $isHttps ? 'https://' : 'http://';
        $host = $this->sanitizeHttpHostForUrl((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $base = preg_replace('#/(public|install)/?$#i', '', rtrim((string) $this->getPublicUrl(), '/'));

        if ($base === '') {
            return $scheme . $host;
        }

        if (str_starts_with($base, 'http://') || str_starts_with($base, 'https://')) {
            return $base;
        }

        if (!str_starts_with($base, '/')) {
            $base = '/' . $base;
        }

        return $scheme . $host . $base;
    }

    private function normalizeSiteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return rtrim($url, '/');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if ($scheme !== 'http' && $scheme !== 'https') {
            $scheme = 'https';
        }

        $host = $this->sanitizeHttpHostForUrl((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return rtrim($url, '/');
        }

        $port = isset($parts['port']) && is_int($parts['port']) ? $parts['port'] : null;
        $path = (string) ($parts['path'] ?? '');
        $path = preg_replace('#/(public|install)/?$#i', '', rtrim($path, '/')) ?? '';
        if ($path !== '' && !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        $normalized = $scheme . '://' . $host;
        if (
            $port !== null
            && !(($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80))
        ) {
            $normalized .= ':' . $port;
        }

        return $normalized . $path;
    }

    private function getInstallLockPath(): string
    {
        if (defined('DATA_PATH')) {
            return DATA_PATH . '/installed.lock';
        }

        return $this->getBasePath() . '/data/installed.lock';
    }

    private function getModulePath(): string
    {
        return defined('APP_PATH')
            ? rtrim((string) APP_PATH, '/\\') . '/Modules/Install'
            : dirname(__DIR__);
    }

    private function getBasePath(): string
    {
        if (defined('BASE_PATH')) {
            return rtrim((string) BASE_PATH, '/\\');
        }

        return dirname($this->getModulePath(), 3);
    }

    private function sanitizeHttpHostForUrl(string $host): string
    {
        $host = trim(explode(',', $host)[0] ?? '');
        $host = preg_replace('/\s+/', '', $host) ?? '';

        if ($host === '') {
            return 'localhost';
        }

        if (preg_match('/^\[[0-9a-fA-F:]+\](?::\d{1,5})?$/', $host) === 1) {
            return $host;
        }

        if (preg_match('/^[A-Za-z0-9.-]+(?::\d{1,5})?$/', $host) === 1) {
            return $host;
        }

        return 'localhost';
    }

}
