<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Install\Controllers;

use App\Modules\Install\Support\Lang;

final class InstallController
{
    private const INSTALL_VERSION_FALLBACK = '1.0.0';
    private const SECURITY_HSTS = 'max-age=31536000; includeSubDomains; preload';

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

    /**
     * Retourne la version FlatCMS depuis le manifeste core.
     */
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

    /**
     * Redirige vers une étape (compatible tous serveurs)
     * 
     * CORRECTION CRITIQUE : Utilise index.php?step=X au lieu de install.php?step=X
     * pour fonctionner sans configuration serveur (Zero Config).
     */
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

    /**
     * Retourne l'URL publique (ou le chemin) de base.
     * Fallback basé sur SCRIPT_NAME pour fonctionner sans PUBLIC_URL.
     */
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

    /**
     * URL du script d'installation courant.
     * Exemples: /index.php, /public/index.php, /sous-dossier/index.php
     */
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

    // ========================================
    // PROCESS METHODS
    // ========================================

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
        $name = trim($_POST['admin_name'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $passwordConfirm = $_POST['admin_password_confirm'] ?? '';

        // Validation
        if (empty($name) || empty($email) || empty($password)) {
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
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $passwordHash = password_hash($password, $algo);
        if ($passwordHash === false) {
            $this->pushError(Lang::get('admin.error_password_short'));
            $this->redirectToStep(6);
            return;
        }

        $_SESSION['install_admin'] = [
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
        ];

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
            // 0. Nettoyer les datasets préexistants pour repartir d'une base propre
            $this->resetInstallationStorage();

            // 1. Créer l'utilisateur admin
            $adminUser = $this->createAdminUser($admin);

            // 2. Créer les paramètres du site (avec thèmes)
            $this->createSiteSettings($site, $design, $admin);
            $installLocale = $this->resolveInstallationLocale();
            $this->ensureInstallationLocaleConfig($installLocale);

            // 3. Installer les seeds de base + (optionnel) les données d'exemple classiques
            $seedContext = $this->buildSeedContext($adminUser, $admin, $site);
            $this->applyInstallationSeedPack('base', $seedContext);
            $this->installRequiredSystemPages();
            if ($installSample) {
                $demoSeedContext = $this->buildDemoSeedContext($seedContext);
                $this->applyInstallationSeedPack('demo', $demoSeedContext);
                $this->applyDemoLocalizedContent($demoSeedContext);
                $this->applyDemoSettingsOverlay();
                $this->applyInstallationPublicAssetPack('demo');
                $this->syncInstallationMediaLibrary();
            }

            // 4. Désactiver le module Install après installation
            $this->disableInstallModule();

            // 5. Générer les fichiers de configuration serveur
            $this->generateServerConfigs($site['url']);

            // 6. Créer les liens d'assets des modules/extensions
            $this->createModuleAssetLinks();

            // 6.1 Préparer un .env.local minimal pour les intégrations (si absent)
            $this->ensureEnvLocalDefaults();

            // 7. Créer le fichier de verrouillage
            $this->createInstallLock();

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
                $_SESSION['user'] = $adminUser;
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

    private function resetInstallationStorage(): void
    {
        $directoriesToReset = [
            DATA_PATH . '/users',
            DATA_PATH . '/auth',
            DATA_PATH . '/languages',
            DATA_PATH . '/core/pages',
            DATA_PATH . '/core/posts',
            DATA_PATH . '/core/categories',
            DATA_PATH . '/core/contact_forms',
            DATA_PATH . '/core/contact_messages',
            DATA_PATH . '/core/comments',
            DATA_PATH . '/comments',
            DATA_PATH . '/menus',
            DATA_PATH . '/pages',
            DATA_PATH . '/footer',
            PUBLIC_PATH . '/uploads/images',
            PUBLIC_PATH . '/uploads/files',
            PUBLIC_PATH . '/uploads/media',
            PUBLIC_PATH . '/uploads/logo',
            PUBLIC_PATH . '/uploads/cache/runtime-css',
            BASE_PATH . '/resources/uploads/contact',
        ];

        foreach ($directoriesToReset as $directory) {
            $this->resetDirectory($directory);
        }

        // Réinitialiser le registre media natif
        $this->ensureDirectory(DATA_PATH . '/core/media', 'Unable to create media directory.');
        $this->writeJsonFile(
            DATA_PATH . '/core/media/media.json',
            [],
            'Unable to reset media registry.'
        );
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

    private function resetDirectory(string $path): void
    {
        if (is_dir($path)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if (!$item instanceof \SplFileInfo) {
                    continue;
                }

                $itemPath = $item->getPathname();
                if ($item->isDir()) {
                    @rmdir($itemPath);
                    continue;
                }

                @unlink($itemPath);
            }

            @rmdir($path);
        }

        if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to reset directory: ' . $path);
        }
    }

    private function createAdminUser(array $admin): array
    {
        $usersDir = DATA_PATH . '/users';
        if (!is_dir($usersDir) && !mkdir($usersDir, 0755, true) && !is_dir($usersDir)) {
            throw new \RuntimeException('Unable to create users directory.');
        }

        $id = date('YmdHis') . '_' . bin2hex(random_bytes(4));
        $now = date('Y-m-d H:i:s');

        $userData = [
            'id' => $id,
            'name' => $admin['name'],
            'email' => $admin['email'],
            'password' => $admin['password'],
            'role' => 'super_admin',
            'status' => 'active',
            'bio' => '',
            'phone' => '',
            'company' => '',
            'avatar' => '',
            'last_login' => '',
            'last_login_at' => '',
            'last_login_ip' => '',
            'remember_token' => '',
            'remember_expires' => null,
            'admin_tour_seen_at' => '',
            'admin_tour_version' => '',
            'admin_tour_seen_modules' => [],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $written = file_put_contents(
            $usersDir . '/' . $id . '.json',
            json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        if ($written === false) {
            throw new \RuntimeException('Unable to write administrator user file.');
        }

        return $userData;
    }

    private function createSiteSettings(array $site, array $design, array $admin = []): void
    {
        $settingsFile = DATA_PATH . '/settings.json';

        $settings = [
            'site_name' => $site['name'],
            'site_description' => $site['description'],
            'site_slogan' => '',
            'site_name_enabled' => 1,
            'site_slogan_enabled' => 1,
            'site_logo_variant' => 'compact',
            'site_url' => $site['url'],
            'site_email' => '',
            'timezone' => $site['timezone'],
            'language' => $_SESSION['install_lang'] ?? 'fr-FR',
            'default_language' => $_SESSION['install_lang'] ?? 'fr-FR',
            'admin_theme' => $design['admin_theme'],
            'frontend_theme' => $design['frontend_theme'],
            'mail_from_name' => $site['name'],
            'contact_notification_enabled' => 1,
            'contact_notification_email' => '',
            'contact_enable_captcha' => 0,
            'url_routing_mode' => 'auto',
            'url_rewrite_last_status' => 'unknown',
            'url_rewrite_last_check_at' => date('Y-m-d H:i:s'),
            'admin_guided_tour_enabled' => 1,
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => $this->getInstallVersion(),
        ];

        $written = file_put_contents(
            $settingsFile,
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        if ($written === false) {
            throw new \RuntimeException('Unable to write settings file.');
        }
    }

    /**
     * @param array<string, mixed> $adminUser
     * @param array<string, mixed> $adminInput
     * @param array<string, mixed> $site
     * @return array<string, string>
     */
    private function buildSeedContext(array $adminUser, array $adminInput, array $site): array
    {
        $adminId = trim((string) ($adminUser['id'] ?? ''));
        if ($adminId === '') {
            $adminId = '1';
        }

        $adminEmail = trim((string) ($adminUser['email'] ?? $adminInput['email'] ?? ''));
        $siteName = trim((string) ($site['name'] ?? \App\Core\CoreManifest::name('FlatCMS')));
        if ($siteName === '') {
            $siteName = \App\Core\CoreManifest::name('FlatCMS');
        }
        $siteDescription = trim((string) ($site['description'] ?? ''));
        $siteUrl = rtrim(trim((string) ($site['url'] ?? '')), '/');

        return [
            '{{ADMIN_ID}}' => $adminId,
            '{{ADMIN_EMAIL}}' => $adminEmail,
            '{{SITE_NAME}}' => $siteName,
            '{{SITE_DESCRIPTION}}' => $siteDescription,
            '{{SITE_URL}}' => $siteUrl,
            '{{FLATCMS_VERSION}}' => $this->getInstallVersion(),
            '{{YEAR}}' => date('Y'),
            '{{NOW}}' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array<string, string> $baseContext
     * @return array<string, string>
     */
    private function buildDemoSeedContext(array $baseContext): array
    {
        $overlay = $this->readDemoSettingsOverlay();
        if ($overlay === []) {
            return $baseContext;
        }

        $siteName = trim((string) ($overlay['site_name'] ?? ''));
        if ($siteName !== '') {
            $baseContext['{{SITE_NAME}}'] = $siteName;
        }

        $siteDescription = trim((string) ($overlay['site_description'] ?? ''));
        if ($siteDescription !== '') {
            $baseContext['{{SITE_DESCRIPTION}}'] = $siteDescription;
        }

        return $baseContext;
    }

    private function applyDemoSettingsOverlay(): void
    {
        $overlay = $this->readDemoSettingsOverlay();
        if ($overlay === []) {
            return;
        }

        $settings = \App\Core\FlatFile::settings();
        if (!is_array($settings) || $settings === []) {
            $settings = [];
        }

        $preservedKeys = [
            'site_url',
            'timezone',
            'language',
            'default_language',
            'admin_theme',
            'frontend_theme',
            'url_routing_mode',
            'url_rewrite_last_status',
            'url_rewrite_last_check_at',
            'admin_guided_tour_enabled',
            'installed_at',
            'version',
        ];

        $merged = array_merge($settings, $overlay);
        foreach ($preservedKeys as $key) {
            if (array_key_exists($key, $settings)) {
                $merged[$key] = $settings[$key];
            }
        }

        if (!\App\Core\FlatFile::saveSettings($merged)) {
            throw new \RuntimeException('Unable to write demo settings overlay.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readDemoSettingsOverlay(): array
    {
        $path = $this->getModulePath() . '/Seeds/demo/meta/settings.json';
        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Unable to read demo settings overlay.');
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid demo settings overlay.');
        }

        return $decoded;
    }

    private function resolveInstallationLocale(): string
    {
        $candidate = trim((string) ($_SESSION['install_lang'] ?? ''));
        if ($candidate === '') {
            $candidate = trim((string) (($_SESSION['install_site']['default_language'] ?? '') ?: ''));
        }
        if ($candidate === '') {
            $candidate = trim((string) (config('app.locale', 'fr-FR')));
        }

        $moduleTranslationPath = BASE_PATH . '/app/Modules/Core/Languages/' . $candidate . '.json';
        if (is_file($moduleTranslationPath)) {
            return $candidate;
        }

        return 'fr-FR';
    }

    private function ensureInstallationLocaleConfig(string $locale): void
    {
        $locale = trim($locale);
        if ($locale === '') {
            return;
        }

        $configPath = BASE_PATH . '/data/languages';
        if (!is_dir($configPath) && !mkdir($configPath, 0755, true) && !is_dir($configPath)) {
            throw new \RuntimeException('Unable to create languages directory.');
        }

        $filePath = $configPath . '/' . $locale . '.json';
        $existing = is_file($filePath) ? (json_read($filePath) ?? []) : [];
        if (!is_array($existing)) {
            $existing = [];
        }

        $displayName = '';
        $nativeName = '';
        if (class_exists('\\Locale')) {
            $normalizedLocale = str_replace('-', '_', $locale);
            $displayName = trim((string) \Locale::getDisplayLanguage($normalizedLocale, $normalizedLocale));
            $nativeName = $displayName;
        }

        if ($displayName === '') {
            $displayName = strtoupper($locale);
        }

        if ($nativeName === '') {
            $nativeName = $displayName;
        }

        $languagePrefix = strtolower((string) strtok($locale, '-'));
        $direction = in_array($languagePrefix, ['ar', 'fa', 'he', 'ur'], true) ? 'rtl' : 'ltr';

        $payload = array_merge($existing, [
            'name' => $displayName,
            'native' => $nativeName,
            'direction' => $direction,
            'active' => true,
        ]);

        if (!isset($payload['created_at']) || trim((string) $payload['created_at']) === '') {
            $payload['created_at'] = date('Y-m-d H:i:s');
        }

        json_write($filePath, $payload);
    }

    /**
     * Copie un pack de seeds depuis app/Modules/Install/Seeds/{pack}/data vers DATA_PATH.
     *
     * @param array<string, string> $replacements
     */
    private function applyInstallationSeedPack(string $pack, array $replacements = []): void
    {
        $seedRoot = $this->getModulePath() . '/Seeds/' . trim($pack, '/') . '/data';
        if (!is_dir($seedRoot)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($seedRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $sourceRoot = rtrim($seedRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                continue;
            }

            $sourcePath = $entry->getPathname();
            $relativePath = str_replace($sourceRoot, '', $sourcePath);
            if ($relativePath === $sourcePath || $relativePath === '') {
                continue;
            }
            $targetPath = DATA_PATH . '/' . str_replace('\\', '/', $relativePath);

            if ($entry->isDir()) {
                $this->ensureDirectory($targetPath, 'Unable to create seed directory: ' . $targetPath);
                continue;
            }

            $targetDir = dirname($targetPath);
            $this->ensureDirectory($targetDir, 'Unable to create seed directory: ' . $targetDir);

            $content = file_get_contents($sourcePath);
            if ($content === false) {
                throw new \RuntimeException('Unable to read seed file: ' . $sourcePath);
            }

            if ($replacements !== []) {
                $content = strtr($content, $replacements);
            }

            if (file_put_contents($targetPath, $content, LOCK_EX) === false) {
                throw new \RuntimeException('Unable to write seed file: ' . $targetPath);
            }
        }
    }

    private function applyInstallationPublicAssetPack(string $pack): void
    {
        $assetRoot = $this->getModulePath() . '/Seeds/' . trim($pack, '/') . '/assets/public';
        if (!is_dir($assetRoot)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($assetRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $sourceRoot = rtrim($assetRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        foreach ($iterator as $entry) {
            if (!$entry instanceof \SplFileInfo) {
                continue;
            }

            $sourcePath = $entry->getPathname();
            $relativePath = str_replace($sourceRoot, '', $sourcePath);
            if ($relativePath === $sourcePath || $relativePath === '') {
                continue;
            }

            $targetPath = PUBLIC_PATH . '/' . str_replace('\\', '/', $relativePath);
            if ($entry->isDir()) {
                $this->ensureDirectory($targetPath, 'Unable to create asset directory: ' . $targetPath);
                continue;
            }

            $targetDir = dirname($targetPath);
            $this->ensureDirectory($targetDir, 'Unable to create asset directory: ' . $targetDir);

            if (!@copy($sourcePath, $targetPath)) {
                throw new \RuntimeException('Unable to copy seed asset: ' . $sourcePath);
            }
        }
    }

    private function syncInstallationMediaLibrary(): void
    {
        if (!class_exists(\App\Modules\Media\Models\MediaModel::class)) {
            return;
        }

        try {
            $mediaModel = new \App\Modules\Media\Models\MediaModel();
            $mediaModel->sync();
        } catch (\Throwable $exception) {
            error_log('[FlatCMS][Install] Demo media sync failed: ' . $exception->getMessage());
        }
    }

    /**
     * @param array<string, string> $replacements
     */
    private function applyDemoLocalizedContent(array $replacements = []): void
    {
        $catalog = $this->readDemoContentTranslationCatalog($replacements);
        if ($catalog === []) {
            return;
        }

        $this->materializeDemoTranslatedRecords(
            DATA_PATH . '/core/pages',
            is_array($catalog['pages'] ?? null) ? $catalog['pages'] : [],
            ['title', 'slug', 'content', 'meta_title', 'meta_description']
        );

        $this->materializeDemoTranslatedRecords(
            DATA_PATH . '/core/posts',
            is_array($catalog['posts'] ?? null) ? $catalog['posts'] : [],
            ['title', 'slug', 'excerpt', 'content', 'meta_title', 'meta_description']
        );

        $this->materializeDemoTranslatedRecords(
            DATA_PATH . '/core/categories',
            is_array($catalog['categories'] ?? null) ? $catalog['categories'] : [],
            ['name', 'slug', 'description']
        );

    }

    /**
     * @param array<string, string> $replacements
     * @return array<string, mixed>
     */
    private function readDemoContentTranslationCatalog(array $replacements = []): array
    {
        $path = $this->getModulePath() . '/Seeds/demo/meta/content_translations.json';
        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Unable to read demo translation catalog.');
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid demo translation catalog.');
        }

        if ($replacements === []) {
            return $decoded;
        }

        $resolved = $this->replaceDemoCatalogPlaceholders($decoded, $replacements);
        return is_array($resolved) ? $resolved : [];
    }

    /**
     * @param array<string, mixed> $catalog
     * @param array<int, string> $translatableFields
     */
    private function materializeDemoTranslatedRecords(string $directory, array $catalog, array $translatableFields): void
    {
        foreach ($catalog as $sourceId => $translations) {
            $normalizedSourceId = trim((string) $sourceId);
            if ($normalizedSourceId === '' || !is_array($translations)) {
                continue;
            }

            $sourcePath = $directory . '/' . $normalizedSourceId . '.json';
            $source = is_file($sourcePath) ? json_read($sourcePath) : null;
            if (!is_array($source)) {
                continue;
            }

            $translationGroup = trim((string) ($source['translation_group'] ?? $normalizedSourceId));
            $sourceLocale = trim((string) ($source['source_locale'] ?? $source['locale'] ?? 'fr-FR'));
            if ($translationGroup === '') {
                $translationGroup = $normalizedSourceId;
            }
            if ($sourceLocale === '') {
                $sourceLocale = 'fr-FR';
            }

            $source['translation_group'] = $translationGroup;
            $source['locale'] = trim((string) ($source['locale'] ?? $sourceLocale)) !== ''
                ? trim((string) $source['locale'])
                : $sourceLocale;
            $source['source_locale'] = $sourceLocale;
            json_write($sourcePath, $source);

            foreach ($translations as $locale => $override) {
                $normalizedLocale = trim((string) $locale);
                if ($normalizedLocale === '' || $normalizedLocale === $sourceLocale || !is_array($override)) {
                    continue;
                }

                $localized = $source;
                $localized['id'] = $this->buildLocalizedSeedId($normalizedSourceId, $normalizedLocale);
                $localized['translation_group'] = $translationGroup;
                $localized['locale'] = $normalizedLocale;
                $localized['source_locale'] = $sourceLocale;

                foreach ($translatableFields as $field) {
                    if (array_key_exists($field, $override)) {
                        $value = $override[$field];
                        if ($field === 'content' && is_string($value)) {
                            $value = $this->localizeDemoContentLinks($value, $normalizedLocale);
                        }
                        $localized[$field] = $value;
                    }
                }

                json_write($directory . '/' . $localized['id'] . '.json', $localized);
            }
        }
    }

    /**
     * @param array<string, mixed> $catalog
     */
    private function materializeDemoTranslatedComments(string $directory, array $catalog): void
    {
        foreach ($catalog as $sourceId => $translations) {
            $normalizedSourceId = trim((string) $sourceId);
            if ($normalizedSourceId === '' || !is_array($translations)) {
                continue;
            }

            $sourcePath = $directory . '/' . $normalizedSourceId . '.json';
            $source = is_file($sourcePath) ? json_read($sourcePath) : null;
            if (!is_array($source)) {
                continue;
            }

            $sourcePostId = trim((string) ($source['post_id'] ?? ''));
            if ($sourcePostId === '') {
                continue;
            }

            foreach ($translations as $locale => $override) {
                $normalizedLocale = trim((string) $locale);
                if ($normalizedLocale === '' || !is_array($override)) {
                    continue;
                }

                $localized = $source;
                $localized['id'] = $this->buildLocalizedSeedId($normalizedSourceId, $normalizedLocale);
                $localized['post_id'] = $this->buildLocalizedSeedId($sourcePostId, $normalizedLocale);

                if (array_key_exists('content', $override)) {
                    $localized['content'] = (string) $override['content'];
                }

                json_write($directory . '/' . $localized['id'] . '.json', $localized);
            }
        }
    }

    private function buildLocalizedSeedId(string $sourceId, string $locale): string
    {
        $normalizedSource = preg_replace('/[^a-zA-Z0-9_-]/', '', $sourceId) ?? $sourceId;
        $normalizedLocale = strtolower(str_replace('-', '_', trim($locale)));
        $normalizedLocale = preg_replace('/[^a-z0-9_]/', '', $normalizedLocale) ?? $normalizedLocale;

        return rtrim($normalizedSource, '_') . '_' . $normalizedLocale;
    }

    private function localizeDemoContentLinks(string $html, string $locale): string
    {
        $prefix = '/' . trim($locale, '/');
        if ($prefix === '/') {
            return $html;
        }

        return preg_replace_callback(
            '#href=(["\'])/(?!' . preg_quote(trim($locale, '/'), '#') . '/)([^"\']*)\\1#u',
            static function (array $matches) use ($prefix): string {
                $quote = $matches[1] ?? '"';
                $path = $matches[2] ?? '';
                if ($path === '' || preg_match('#^(assets|themes|modules|uploads|release|favicon\\.ico|public/)#', $path) === 1) {
                    return 'href=' . $quote . '/' . $path . $quote;
                }

                return 'href=' . $quote . $prefix . '/' . ltrim($path, '/') . $quote;
            },
            $html
        ) ?? $html;
    }

    /**
     * @param mixed $value
     * @param array<string, string> $replacements
     * @return mixed
     */
    private function replaceDemoCatalogPlaceholders(mixed $value, array $replacements): mixed
    {
        if (is_string($value)) {
            return strtr($value, $replacements);
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $entry) {
            $value[$key] = $this->replaceDemoCatalogPlaceholders($entry, $replacements);
        }

        return $value;
    }

    private function installRequiredSystemPages(): void
    {
        if (!class_exists(\App\Modules\Pages\Support\SystemPages::class)) {
            return;
        }

        \App\Core\I18n::load('Pages');
        \App\Modules\Pages\Support\SystemPages::ensureRequired(
            \App\Core\FlatFile::for('core/pages'),
            static fn (string $key): string => (string) \App\Core\I18n::get($key, 'Pages')
        );
    }

    private function createSampleData(string $adminUserId = ''): void
    {
        $authorId = $adminUserId !== '' ? $adminUserId : '1';
        $now = new \DateTimeImmutable('now');

        $pages = $this->createSamplePages($authorId, $now);
        $categories = $this->createSampleCategories($now);
        $posts = $this->createSamplePosts($authorId, $categories, $now);
        $this->createSampleContactForms($now);
        $this->createSampleComments($posts, $now);
        $this->createSampleMenu($pages);
        $this->createSampleFooter();
    }

    /**
     * @return array<string, string>
     */
    private function createSamplePages(string $authorId, \DateTimeImmutable $now): array
    {
        $pagesDir = DATA_PATH . '/core/pages';
        $this->ensureDirectory($pagesDir, 'Unable to create sample pages directory.');

        $homeContent = '<p>' . $this->sampleText('sample.sample_home_intro', 'Bienvenue sur FlatCMS. Cette page d\'accueil de démonstration vous montre une structure prête à personnaliser.') . '</p>'
            . '<p>' . $this->sampleText('sample.sample_home_cta', 'Commencez par adapter les pages, puis organisez votre menu et votre footer depuis l\'administration.') . '</p>'
            . '<h2>' . $this->sampleText('sample.sample_home_section_start_title', 'Par quoi commencer ?') . '</h2>'
            . '<ul>'
            . '<li>' . $this->sampleText('sample.sample_home_step_one', 'Configurer les réglages globaux (nom du site, thème, langues).') . '</li>'
            . '<li>' . $this->sampleText('sample.sample_home_step_two', 'Créer vos pages stratégiques et votre navigation.') . '</li>'
            . '<li>' . $this->sampleText('sample.sample_home_step_three', 'Publier vos premiers articles et ouvrir votre formulaire de contact.') . '</li>'
            . '</ul>'
            . '<p><a href="/page/demarrer">' . $this->sampleText('sample.sample_link_getting_started', 'Lire le guide de démarrage') . '</a> · <a href="/blog">' . $this->sampleText('sample.sample_link_view_blog', 'Voir les articles') . '</a> · <a href="/page/contact">' . $this->sampleText('sample.sample_link_contact', 'Contacter l\'équipe') . '</a></p>';

        $gettingStartedContent = '<p>' . $this->sampleText('sample.sample_getting_started_intro', 'Ce guide vous aide à prendre en main FlatCMS rapidement, même sans profil technique.') . '</p>'
            . '<h2>' . $this->sampleText('sample.sample_getting_started_checklist_title', 'Checklist de configuration') . '</h2>'
            . '<ol>'
            . '<li>' . $this->sampleText('sample.sample_getting_started_step_one', 'Validez les réglages généraux dans Paramètres.') . '</li>'
            . '<li>' . $this->sampleText('sample.sample_getting_started_step_two', 'Structurez vos pages principales (Accueil, Contact, À propos).') . '</li>'
            . '<li>' . $this->sampleText('sample.sample_getting_started_step_three', 'Créez vos catégories et vos premiers articles.') . '</li>'
            . '<li>' . $this->sampleText('sample.sample_getting_started_step_four', 'Personnalisez le menu et le footer pour clarifier la navigation.') . '</li>'
            . '</ol>'
            . '<p>' . $this->sampleText('sample.sample_getting_started_tip', 'Astuce: gardez une version brouillon de vos contenus avant publication.') . '</p>';

        $blogPageContent = '<p>' . $this->sampleText('sample.sample_blog_page_intro', 'Le blog centralise vos tutoriels, annonces et retours d\'expérience.') . '</p>'
            . '<p>' . $this->sampleText('sample.sample_blog_page_description', 'Utilisez les catégories pour filtrer les sujets et faciliter la lecture.') . '</p>'
            . '<p><a href="/blog">' . $this->sampleText('sample.sample_link_view_blog', 'Consulter le blog') . '</a></p>';

        $contactContent = '<p>' . $this->sampleText('sample.sample_contact_intro', 'Cette page de contact est prête à recevoir les demandes de vos visiteurs.') . '</p>'
            . '[contact-form slug="contact-main"]'
            . '<p><small>' . $this->sampleText('sample.sample_contact_notice', 'Ce formulaire est relié à la boîte de réception du module Contact dans l\'administration.') . '</small></p>'
            . '<p><small>' . $this->sampleText('sample.sample_contact_secondary_notice', 'Un second formulaire « Demande de devis » est aussi disponible dans l\'administration si vous souhaitez l\'utiliser dans un article ou une landing page.') . '</small></p>';

        $aboutContent = '<p>' . $this->sampleText('sample.sample_about_intro', 'FlatCMS est un CMS flat-file moderne qui vous permet de publier rapidement sans base de données.') . '</p>'
            . '<h2>' . $this->sampleText('sample.sample_about_section_title', 'Pourquoi ce projet ?') . '</h2>'
            . '<p>' . $this->sampleText('sample.sample_about_section_body', 'Offrir une base simple, performante et évolutive pour les créateurs de sites vitrines, blogs et sites éditoriaux.') . '</p>';

        $pages = [
            [
                '__key' => 'home',
                'id' => '1',
                'title' => $this->sampleText('sample.sample_home_title', 'Accueil'),
                'slug' => 'home',
                'content' => $homeContent,
                'meta_title' => $this->sampleText('sample.sample_home_meta_title', 'Accueil - FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_home_meta_description', 'Page d\'accueil de démonstration FlatCMS prête à personnaliser.'),
                'status' => 'published',
                'author_id' => $authorId,
                'created_at' => $now->modify('-12 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-2 days')->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'getting_started',
                'id' => '2',
                'title' => $this->sampleText('sample.sample_getting_started_title', 'Démarrer'),
                'slug' => 'demarrer',
                'content' => $gettingStartedContent,
                'meta_title' => $this->sampleText('sample.sample_getting_started_meta_title', 'Démarrer avec FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_getting_started_meta_description', 'Guide de prise en main rapide pour configurer FlatCMS.'),
                'status' => 'published',
                'author_id' => $authorId,
                'created_at' => $now->modify('-11 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'blog',
                'id' => '3',
                'title' => $this->sampleText('sample.sample_blog_page_title', 'Blog'),
                'slug' => 'blog',
                'content' => $blogPageContent,
                'meta_title' => $this->sampleText('sample.sample_blog_page_meta_title', 'Blog - FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_blog_page_meta_description', 'Actualités, tutoriels et conseils de configuration FlatCMS.'),
                'status' => 'published',
                'author_id' => $authorId,
                'created_at' => $now->modify('-10 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'contact',
                'id' => '4',
                'title' => $this->sampleText('sample.sample_contact_page_title', 'Contact'),
                'slug' => 'contact',
                'content' => $contactContent,
                'meta_title' => $this->sampleText('sample.sample_contact_meta_title', 'Contact - FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_contact_meta_description', 'Page contact prête à l\'emploi avec formulaire natif FlatCMS.'),
                'status' => 'published',
                'author_id' => $authorId,
                'created_at' => $now->modify('-9 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'about',
                'id' => '5',
                'title' => $this->sampleText('sample.sample_about_title', 'À propos'),
                'slug' => 'a-propos',
                'content' => $aboutContent,
                'meta_title' => $this->sampleText('sample.sample_about_meta_title', 'À propos - FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_about_meta_description', 'Présentation du projet et de la philosophie FlatCMS.'),
                'status' => 'published',
                'author_id' => $authorId,
                'created_at' => $now->modify('-8 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-2 days')->format('Y-m-d H:i:s'),
            ],
        ];

        $pageIds = [];
        foreach ($pages as $page) {
            $pageKey = (string) ($page['__key'] ?? '');
            unset($page['__key']);
            $this->writeJsonFile(
                $pagesDir . '/' . $page['id'] . '.json',
                $page,
                'Unable to write sample page.'
            );
            if ($pageKey !== '') {
                $pageIds[$pageKey] = (string) $page['id'];
            }
        }

        return $pageIds;
    }

    /**
     * @return array<string, string>
     */
    private function createSampleCategories(\DateTimeImmutable $now): array
    {
        $categoriesDir = DATA_PATH . '/core/categories';
        $this->ensureDirectory($categoriesDir, 'Unable to create sample categories directory.');

        $categories = [
            [
                '__key' => 'guides',
                'id' => '1',
                'name' => $this->sampleText('sample.sample_category_guides_name', 'Guides'),
                'slug' => $this->sampleText('sample.sample_category_guides_slug', 'guides'),
                'description' => $this->sampleText('sample.sample_category_guides_description', 'Guides de prise en main et tutoriels pas à pas.'),
                'module' => 'blog',
                'status' => 'active',
                'created_at' => $now->modify('-12 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'configuration',
                'id' => '2',
                'name' => $this->sampleText('sample.sample_category_configuration_name', 'Configuration'),
                'slug' => $this->sampleText('sample.sample_category_configuration_slug', 'configuration'),
                'description' => $this->sampleText('sample.sample_category_configuration_description', 'Réglages système, thèmes et modules essentiels.'),
                'module' => 'blog',
                'status' => 'active',
                'created_at' => $now->modify('-11 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'content',
                'id' => '3',
                'name' => $this->sampleText('sample.sample_category_content_name', 'Contenu'),
                'slug' => $this->sampleText('sample.sample_category_content_slug', 'contenu'),
                'description' => $this->sampleText('sample.sample_category_content_description', 'Méthodes de rédaction et workflow éditorial.'),
                'module' => 'blog',
                'status' => 'active',
                'created_at' => $now->modify('-10 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'seo',
                'id' => '4',
                'name' => $this->sampleText('sample.sample_category_seo_name', 'SEO & Performance'),
                'slug' => $this->sampleText('sample.sample_category_seo_slug', 'seo-performance'),
                'description' => $this->sampleText('sample.sample_category_seo_description', 'Bonnes pratiques de visibilité et rapidité.'),
                'module' => 'blog',
                'status' => 'active',
                'created_at' => $now->modify('-9 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'deployment',
                'id' => '5',
                'name' => $this->sampleText('sample.sample_category_deployment_name', 'Déploiement'),
                'slug' => $this->sampleText('sample.sample_category_deployment_slug', 'deploiement'),
                'description' => $this->sampleText('sample.sample_category_deployment_description', 'Mise en ligne, sécurité et supervision.'),
                'module' => 'blog',
                'status' => 'active',
                'created_at' => $now->modify('-8 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'ai',
                'id' => '6',
                'name' => $this->sampleText('sample.sample_category_ai_name', 'IA & Automatisation'),
                'slug' => $this->sampleText('sample.sample_category_ai_slug', 'ia-automatisation'),
                'description' => $this->sampleText('sample.sample_category_ai_description', 'Intégration d\'OpenAI, ChatGPT et Codex dans vos process.'),
                'module' => 'blog',
                'status' => 'active',
                'created_at' => $now->modify('-7 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
        ];

        $categoryIds = [];
        foreach ($categories as $category) {
            $categoryKey = (string) ($category['__key'] ?? '');
            unset($category['__key']);
            $this->writeJsonFile(
                $categoriesDir . '/' . $category['id'] . '.json',
                $category,
                'Unable to write sample category.'
            );
            if ($categoryKey !== '') {
                $categoryIds[$categoryKey] = (string) $category['id'];
            }
        }

        return $categoryIds;
    }

    /**
     * @param array<string, string> $categoryIds
     * @return array<string, string>
     */
    private function createSamplePosts(string $authorId, array $categoryIds, \DateTimeImmutable $now): array
    {
        $postsDir = DATA_PATH . '/core/posts';
        $this->ensureDirectory($postsDir, 'Unable to create sample posts directory.');

        $posts = [
            [
                '__key' => 'getting_started',
                'id' => '1',
                'title' => $this->sampleText('sample.sample_post_getting_started_title', 'Bien démarrer avec FlatCMS en 10 minutes'),
                'slug' => $this->sampleText('sample.sample_post_getting_started_slug', 'demarrer-flatcms-10-minutes'),
                'excerpt' => $this->sampleText('sample.sample_post_getting_started_excerpt', 'Une feuille de route simple pour publier votre premier site rapidement.'),
                'content' => '<p>' . $this->sampleText('sample.sample_post_getting_started_intro', 'FlatCMS peut être opérationnel en quelques minutes avec une méthode claire.') . '</p>'
                    . '<h2>' . $this->sampleText('sample.sample_post_getting_started_section_title', 'Plan d\'action conseillé') . '</h2>'
                    . '<ol>'
                    . '<li>' . $this->sampleText('sample.sample_post_getting_started_step_one', 'Configurer le nom du site, le slogan et le thème actif.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_getting_started_step_two', 'Créer vos pages de base et structurer le menu principal.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_getting_started_step_three', 'Publier 2 à 3 articles de référence pour lancer le blog.') . '</li>'
                    . '</ol>',
                'featured_image' => '',
                'meta_title' => $this->sampleText('sample.sample_post_getting_started_meta_title', 'Bien démarrer avec FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_post_getting_started_meta_description', 'Guide de démarrage rapide pour installer et configurer FlatCMS.'),
                'categories' => [(string) ($categoryIds['guides'] ?? '1'), (string) ($categoryIds['configuration'] ?? '2')],
                'author_id' => $authorId,
                'status' => 'published',
                'created_at' => $now->modify('-7 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-2 days')->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'navigation',
                'id' => '2',
                'title' => $this->sampleText('sample.sample_post_navigation_title', 'Construire une navigation claire: pages + menu'),
                'slug' => $this->sampleText('sample.sample_post_navigation_slug', 'construire-arborescence-navigation'),
                'excerpt' => $this->sampleText('sample.sample_post_navigation_excerpt', 'Comment organiser vos pages pour éviter les menus complexes.'),
                'content' => '<p>' . $this->sampleText('sample.sample_post_navigation_intro', 'Une bonne navigation repose sur une arborescence simple et des intitulés explicites.') . '</p>'
                    . '<h2>' . $this->sampleText('sample.sample_post_navigation_section_title', 'Structure recommandée') . '</h2>'
                    . '<ul>'
                    . '<li>' . $this->sampleText('sample.sample_post_navigation_tip_one', 'Un menu principal court (4 à 6 entrées).') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_navigation_tip_two', 'Des pages piliers: Accueil, Démarrer, Blog, Contact.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_navigation_tip_three', 'Un footer simple pour les liens secondaires.') . '</li>'
                    . '</ul>',
                'featured_image' => '',
                'meta_title' => $this->sampleText('sample.sample_post_navigation_meta_title', 'Navigation et arborescence FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_post_navigation_meta_description', 'Méthode pratique pour structurer pages et menu dans FlatCMS.'),
                'categories' => [(string) ($categoryIds['guides'] ?? '1'), (string) ($categoryIds['content'] ?? '3')],
                'author_id' => $authorId,
                'status' => 'published',
                'created_at' => $now->modify('-6 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'editorial_workflow',
                'id' => '3',
                'title' => $this->sampleText('sample.sample_post_editorial_title', 'Workflow éditorial: du brouillon à la publication'),
                'slug' => $this->sampleText('sample.sample_post_editorial_slug', 'workflow-editorial-flatcms'),
                'excerpt' => $this->sampleText('sample.sample_post_editorial_excerpt', 'Une méthode de production pour garder un rythme éditorial régulier.'),
                'content' => '<p>' . $this->sampleText('sample.sample_post_editorial_intro', 'Travailler en brouillon réduit les erreurs et facilite les relectures.') . '</p>'
                    . '<ol>'
                    . '<li>' . $this->sampleText('sample.sample_post_editorial_step_one', 'Créer un plan de contenu par catégorie.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_editorial_step_two', 'Rédiger, puis relire avec une checklist qualité.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_editorial_step_three', 'Publier et vérifier le rendu frontend mobile/desktop.') . '</li>'
                    . '</ol>',
                'featured_image' => '',
                'meta_title' => $this->sampleText('sample.sample_post_editorial_meta_title', 'Workflow éditorial FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_post_editorial_meta_description', 'Process conseillé pour planifier et publier du contenu dans FlatCMS.'),
                'categories' => [(string) ($categoryIds['content'] ?? '3')],
                'author_id' => $authorId,
                'status' => 'published',
                'created_at' => $now->modify('-5 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'contact_form',
                'id' => '4',
                'title' => $this->sampleText('sample.sample_post_contact_title', 'Configurer le formulaire de contact natif'),
                'slug' => $this->sampleText('sample.sample_post_contact_slug', 'configurer-formulaire-contact-flatcms'),
                'excerpt' => $this->sampleText('sample.sample_post_contact_excerpt', 'Activez les bons champs, les pièces jointes et les notifications en quelques clics.'),
                'content' => '<p>' . $this->sampleText('sample.sample_post_contact_intro', 'Le module Contact permet de créer des formulaires structurés sans plugin externe.') . '</p>'
                    . '<ul>'
                    . '<li>' . $this->sampleText('sample.sample_post_contact_tip_one', 'Définissez le formulaire principal pour le frontend.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_contact_tip_two', 'Ajoutez des champs personnalisés selon votre activité.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_contact_tip_three', 'Activez les pièces jointes si nécessaire.') . '</li>'
                    . '</ul>',
                'featured_image' => '',
                'meta_title' => $this->sampleText('sample.sample_post_contact_meta_title', 'Formulaire de contact FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_post_contact_meta_description', 'Guide de configuration du module Contact FlatCMS.'),
                'categories' => [(string) ($categoryIds['configuration'] ?? '2')],
                'author_id' => $authorId,
                'status' => 'published',
                'created_at' => $now->modify('-4 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'seo',
                'id' => '5',
                'title' => $this->sampleText('sample.sample_post_seo_title', 'SEO de base sans plugin externe'),
                'slug' => $this->sampleText('sample.sample_post_seo_slug', 'seo-technique-flatcms'),
                'excerpt' => $this->sampleText('sample.sample_post_seo_excerpt', 'Les réglages essentiels pour améliorer votre visibilité naturelle.'),
                'content' => '<p>' . $this->sampleText('sample.sample_post_seo_intro', 'Un SEO propre commence par des contenus bien structurés et des métadonnées cohérentes.') . '</p>'
                    . '<ul>'
                    . '<li>' . $this->sampleText('sample.sample_post_seo_tip_one', 'Renseigner title + meta description pour chaque page/article.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_seo_tip_two', 'Utiliser des URLs lisibles et des titres explicites.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_seo_tip_three', 'Optimiser les médias avant upload.') . '</li>'
                    . '</ul>',
                'featured_image' => '',
                'meta_title' => $this->sampleText('sample.sample_post_seo_meta_title', 'SEO FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_post_seo_meta_description', 'Checklist SEO de base pour améliorer la visibilité d\'un site FlatCMS.'),
                'categories' => [(string) ($categoryIds['seo'] ?? '4')],
                'author_id' => $authorId,
                'status' => 'published',
                'created_at' => $now->modify('-3 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'performance',
                'id' => '6',
                'title' => $this->sampleText('sample.sample_post_performance_title', 'Performance: checklist avant mise en ligne'),
                'slug' => $this->sampleText('sample.sample_post_performance_slug', 'performance-checklist-mise-en-ligne'),
                'excerpt' => $this->sampleText('sample.sample_post_performance_excerpt', 'Les vérifications clés pour un site rapide et stable.'),
                'content' => '<p>' . $this->sampleText('sample.sample_post_performance_intro', 'Avant publication, validez la rapidité et la stabilité du site sur mobile et desktop.') . '</p>'
                    . '<ol>'
                    . '<li>' . $this->sampleText('sample.sample_post_performance_step_one', 'Compresser les images et vérifier les poids de pages.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_performance_step_two', 'Tester les parcours clés (menu, blog, contact).') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_performance_step_three', 'Contrôler les erreurs et logs côté serveur.') . '</li>'
                    . '</ol>',
                'featured_image' => '',
                'meta_title' => $this->sampleText('sample.sample_post_performance_meta_title', 'Performance FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_post_performance_meta_description', 'Checklist de performance pour finaliser une mise en ligne FlatCMS.'),
                'categories' => [(string) ($categoryIds['seo'] ?? '4'), (string) ($categoryIds['deployment'] ?? '5')],
                'author_id' => $authorId,
                'status' => 'published',
                'created_at' => $now->modify('-2 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'deployment',
                'id' => '7',
                'title' => $this->sampleText('sample.sample_post_deployment_title', 'Déploiement Apache et Nginx: méthode sûre'),
                'slug' => $this->sampleText('sample.sample_post_deployment_slug', 'deploiement-apache-nginx-flatcms'),
                'excerpt' => $this->sampleText('sample.sample_post_deployment_excerpt', 'Un déroulé clair pour publier FlatCMS sur les deux environnements.'),
                'content' => '<p>' . $this->sampleText('sample.sample_post_deployment_intro', 'Préparez un package propre, testez en local puis validez en pré-production.') . '</p>'
                    . '<ul>'
                    . '<li>' . $this->sampleText('sample.sample_post_deployment_tip_one', 'Vérifier les permissions de data/, public/uploads/ et resources/uploads/contact/.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_deployment_tip_two', 'Contrôler la réécriture d\'URL selon Apache ou Nginx.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_deployment_tip_three', 'Activer les sauvegardes automatiques des fichiers JSON.') . '</li>'
                    . '</ul>',
                'featured_image' => '',
                'meta_title' => $this->sampleText('sample.sample_post_deployment_meta_title', 'Déploiement FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_post_deployment_meta_description', 'Bonnes pratiques pour déployer FlatCMS sur Apache et Nginx.'),
                'categories' => [(string) ($categoryIds['deployment'] ?? '5')],
                'author_id' => $authorId,
                'status' => 'published',
                'created_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
            [
                '__key' => 'ai',
                'id' => '8',
                'title' => $this->sampleText('sample.sample_post_ai_title', 'Automatiser vos contenus avec OpenAI, ChatGPT et Codex'),
                'slug' => $this->sampleText('sample.sample_post_ai_slug', 'automatiser-contenu-openai-chatgpt-codex'),
                'excerpt' => $this->sampleText('sample.sample_post_ai_excerpt', 'Comment intégrer l\'IA pour accélérer rédaction, support et documentation.'),
                'content' => '<p>' . $this->sampleText('sample.sample_post_ai_intro', 'Les assistants IA peuvent accélérer vos workflows éditoriaux et support client.') . '</p>'
                    . '<h2>' . $this->sampleText('sample.sample_post_ai_section_title', 'Cas d\'usage') . '</h2>'
                    . '<ul>'
                    . '<li>' . $this->sampleText('sample.sample_post_ai_use_case_one', 'Préparer des plans d\'articles et FAQ.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_ai_use_case_two', 'Créer des réponses support de premier niveau.') . '</li>'
                    . '<li>' . $this->sampleText('sample.sample_post_ai_use_case_three', 'Générer des checklists de recette avant livraison.') . '</li>'
                    . '</ul>',
                'featured_image' => '',
                'meta_title' => $this->sampleText('sample.sample_post_ai_meta_title', 'OpenAI, ChatGPT et Codex avec FlatCMS'),
                'meta_description' => $this->sampleText('sample.sample_post_ai_meta_description', 'Exemples d\'automatisation éditoriale avec OpenAI, ChatGPT et Codex.'),
                'categories' => [(string) ($categoryIds['ai'] ?? '6'), (string) ($categoryIds['guides'] ?? '1')],
                'author_id' => $authorId,
                'status' => 'published',
                'created_at' => $now->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
        ];

        $postIds = [];
        foreach ($posts as $post) {
            $postKey = (string) ($post['__key'] ?? '');
            unset($post['__key']);
            $this->writeJsonFile(
                $postsDir . '/' . $post['id'] . '.json',
                $post,
                'Unable to write sample post.'
            );
            if ($postKey !== '') {
                $postIds[$postKey] = (string) $post['id'];
            }
        }

        return $postIds;
    }

    private function createSampleContactForms(\DateTimeImmutable $now): void
    {
        $formsDir = DATA_PATH . '/core/contact_forms';
        $this->ensureDirectory($formsDir, 'Unable to create sample contact forms directory.');

        $forms = [
            [
                'id' => 'contact_form_main',
                'name' => $this->sampleText('sample.sample_contact_form_main_name', 'Formulaire principal'),
                'slug' => 'contact-main',
                'description' => '',
                'recipient_email' => '',
                'submit_label' => $this->sampleText('sample.sample_contact_submit', 'Envoyer le message'),
                'success_message' => $this->sampleText('sample.sample_contact_form_success', 'Merci, votre message a bien été envoyé.'),
                'is_active' => true,
                'is_default' => true,
                'fields' => [
                    'name' => true,
                    'email' => true,
                    'subject' => true,
                    'phone' => true,
                    'message' => true,
                ],
                'custom_fields' => [],
                'attachments' => [
                    'enabled' => false,
                    'required' => false,
                    'max_files' => 1,
                    'max_size_mb' => 5,
                    'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'zip'],
                ],
                'created_at' => $now->modify('-9 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 'contact_form_quote',
                'name' => $this->sampleText('sample.sample_contact_form_quote_name', 'Demande de devis'),
                'slug' => 'demande-devis',
                'description' => '',
                'recipient_email' => '',
                'submit_label' => $this->sampleText('sample.sample_contact_form_quote_submit', 'Envoyer la demande'),
                'success_message' => $this->sampleText('sample.sample_contact_form_quote_success', 'Votre demande de devis a bien été transmise.'),
                'is_active' => true,
                'is_default' => false,
                'fields' => [
                    'name' => true,
                    'email' => true,
                    'subject' => true,
                    'phone' => true,
                    'message' => true,
                ],
                'custom_fields' => [
                    [
                        'key' => 'societe',
                        'label' => $this->sampleText('sample.sample_contact_custom_company', 'Société'),
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '',
                        'help' => '',
                        'position_after' => 'name',
                        'width' => 'half',
                        'options' => [],
                    ],
                    [
                        'key' => 'budget',
                        'label' => $this->sampleText('sample.sample_contact_custom_budget', 'Budget estimé'),
                        'type' => 'select',
                        'required' => true,
                        'placeholder' => '',
                        'help' => '',
                        'position_after' => 'subject',
                        'width' => 'half',
                        'options' => [
                            '< 2 000 €',
                            '2 000 € - 5 000 €',
                            '5 000 € - 10 000 €',
                            '> 10 000 €',
                        ],
                    ],
                    [
                        'key' => 'delai',
                        'label' => $this->sampleText('sample.sample_contact_custom_deadline', 'Délai souhaité'),
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '',
                        'help' => '',
                        'position_after' => 'message',
                        'width' => 'full',
                        'options' => [],
                    ],
                ],
                'attachments' => [
                    'enabled' => true,
                    'required' => false,
                    'max_files' => 2,
                    'max_size_mb' => 8,
                    'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'zip'],
                ],
                'created_at' => $now->modify('-8 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->format('Y-m-d H:i:s'),
            ],
        ];

        foreach ($forms as $form) {
            $this->writeJsonFile(
                $formsDir . '/' . $form['id'] . '.json',
                $form,
                'Unable to write sample contact form.'
            );
        }
    }

    /**
     * @param array<string, string> $postIds
     */
    private function createSampleComments(array $postIds, \DateTimeImmutable $now): void
    {
        $commentsDir = DATA_PATH . '/comments';
        $this->ensureDirectory($commentsDir, 'Unable to create sample comments directory.');

        $gettingStartedPostId = (string) ($postIds['getting_started'] ?? '1');
        $aiPostId = (string) ($postIds['ai'] ?? '8');

        $comments = [
            [
                'id' => 'sample_comment_1',
                'post_id' => $gettingStartedPostId,
                'post_type' => 'post',
                'author_name' => 'OpenAI ChatGPT',
                'author_email' => 'demo-chatgpt@flatcms.local',
                'content' => $this->sampleText('sample.sample_comment_getting_started_one', 'Excellente introduction: la checklist est claire et facile à appliquer pour un premier lancement.'),
                'status' => 'approved',
                'ip' => '127.0.0.1',
                'user_agent' => 'FlatCMS Sample Data',
                'created_at' => $now->modify('-3 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-3 days')->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 'sample_comment_2',
                'post_id' => $gettingStartedPostId,
                'post_type' => 'post',
                'author_name' => 'Codex Assistant',
                'author_email' => 'demo-codex@flatcms.local',
                'content' => $this->sampleText('sample.sample_comment_getting_started_two', 'Le passage sur la navigation est très utile pour éviter les menus surchargés au démarrage.'),
                'status' => 'approved',
                'ip' => '127.0.0.1',
                'user_agent' => 'FlatCMS Sample Data',
                'created_at' => $now->modify('-2 days')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-2 days')->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 'sample_comment_3',
                'post_id' => $aiPostId,
                'post_type' => 'post',
                'author_name' => 'OpenAI API',
                'author_email' => 'demo-openai@flatcms.local',
                'content' => $this->sampleText('sample.sample_comment_ai_one', 'Bon angle: combiner génération de brouillons et validation éditoriale améliore vraiment la productivité.'),
                'status' => 'approved',
                'ip' => '127.0.0.1',
                'user_agent' => 'FlatCMS Sample Data',
                'created_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
                'updated_at' => $now->modify('-1 day')->format('Y-m-d H:i:s'),
            ],
        ];

        foreach ($comments as $comment) {
            $this->writeJsonFile(
                $commentsDir . '/' . $comment['id'] . '.json',
                $comment,
                'Unable to write sample comment.'
            );
        }
    }

    /**
     * @param array<string, string> $pages
     */
    private function createSampleMenu(array $pages): void
    {
        $menuItems = [
            [
                'id' => 'menu_home',
                'label' => $this->sampleText('sample.sample_menu_home_label', 'Accueil'),
                'url' => '',
                'icon' => 'fas fa-house',
                'target' => '_self',
                'type' => 'pages',
                'refType' => 'page',
                'ref' => (string) ($pages['home'] ?? '1'),
                'children' => [],
            ],
            [
                'id' => 'menu_start',
                'label' => $this->sampleText('sample.sample_menu_start_label', 'Démarrer'),
                'url' => '/page/demarrer',
                'icon' => 'fas fa-rocket',
                'target' => '_self',
                'type' => 'pages',
                'refType' => 'page',
                'ref' => (string) ($pages['getting_started'] ?? '2'),
                'children' => [],
            ],
            [
                'id' => 'menu_blog',
                'label' => $this->sampleText('sample.sample_menu_blog_label', 'Blog'),
                'url' => '/blog',
                'icon' => 'fas fa-newspaper',
                'target' => '_self',
                'type' => 'posts',
                'children' => [],
            ],
            [
                'id' => 'menu_contact',
                'label' => $this->sampleText('sample.sample_menu_contact_label', 'Contact'),
                'url' => '/page/contact',
                'icon' => 'fas fa-envelope',
                'target' => '_self',
                'type' => 'pages',
                'refType' => 'page',
                'ref' => (string) ($pages['contact'] ?? '4'),
                'children' => [],
            ],
            [
                'id' => 'menu_about',
                'label' => $this->sampleText('sample.sample_menu_about_label', 'À propos'),
                'url' => '/page/a-propos',
                'icon' => 'fas fa-circle-info',
                'target' => '_self',
                'type' => 'pages',
                'refType' => 'page',
                'ref' => (string) ($pages['about'] ?? '5'),
                'children' => [],
            ],
        ];

        $menus = [
            'main' => [
                'items' => $menuItems,
                'library' => [],
            ],
        ];

        if (!\App\Core\FlatFile::saveSettings($menus, 'menus')) {
            throw new \RuntimeException('Unable to write sample menu settings.');
        }
    }

    private function createSampleFooter(): void
    {
        $settings = \App\Core\FlatFile::settings();
        $siteName = trim((string) ($settings['site_name'] ?? \App\Core\CoreManifest::name('FlatCMS')));
        if ($siteName === '') {
            $siteName = \App\Core\CoreManifest::name('FlatCMS');
        }

        $footer = [
            'enabled' => true,
            'brand_text' => $siteName,
            'copyright_text' => '© {year} {site_name}. ' . $this->sampleText('sample.sample_footer_rights', 'All rights reserved.'),
            'powered_by' => [
                'enabled' => true,
                'label' => 'FlatCMS',
                'url' => 'https://flat-cms.fr',
            ],
        ];

        if (!\App\Core\FlatFile::saveSettings($footer, 'footer')) {
            throw new \RuntimeException('Unable to write sample footer settings.');
        }
    }

    private function sampleText(string $key, string $fallback): string
    {
        $value = Lang::get(trim($key));
        if ($value === trim($key) || trim($value) === '') {
            return $fallback;
        }
        return $value;
    }

    private function ensureDirectory(string $path, string $errorMessage): void
    {
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException($errorMessage);
        }
    }

    private function writeJsonFile(string $path, array $payload, string $errorMessage): void
    {
        $written = file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );

        if ($written === false) {
            throw new \RuntimeException($errorMessage);
        }
    }

    private function createInstallLock(): void
    {
        $lockPath = DATA_PATH . '/installed.lock';
        if (is_file($lockPath)) {
            throw new \RuntimeException('Install lock already exists.');
        }

        $lockData = [
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => $this->getInstallVersion(),
            'php_version' => PHP_VERSION,
            'server' => $this->environment['server_type'] ?? 'unknown',
        ];

        $written = file_put_contents(
            $lockPath,
            json_encode($lockData, JSON_PRETTY_PRINT),
            LOCK_EX
        );
        if ($written === false) {
            throw new \RuntimeException('Unable to write install lock file.');
        }
    }

    private function disableInstallModule(): void
    {
        $statePath = DATA_PATH . '/modules.json';
        $state = [];

        if (file_exists($statePath)) {
            $raw = file_get_contents($statePath);
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $state = $decoded;
            }
        }

        $installState = $state['Install'] ?? [];
        if (!is_array($installState)) {
            $installState = [];
        }
        $installState['enabled'] = false;
        $state['Install'] = $installState;

        $written = file_put_contents(
            $statePath,
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );

        if ($written === false) {
            throw new \RuntimeException('Unable to persist Install module state.');
        }
    }

    /**
     * Crée les liens d'assets des modules/extensions dans public/modules
     * (fallback en copie si symlink indisponible).
     */
    private function createModuleAssetLinks(): void
    {
        $publicModules = PUBLIC_PATH . '/modules';
        if (!is_dir($publicModules) && !mkdir($publicModules, 0755, true) && !is_dir($publicModules)) {
            throw new \RuntimeException('Unable to create public/modules directory.');
        }

        $roots = [APP_PATH . '/Modules', APP_PATH . '/Extensions'];
        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }
            $entries = scandir($root);
            if ($entries === false) {
                continue;
            }
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $moduleDir = $root . '/' . $entry;
                if (!is_dir($moduleDir)) {
                    continue;
                }
                $assetsPath = $moduleDir . '/Assets';
                if (!is_dir($assetsPath)) {
                    continue;
                }
                $linkName = strtolower($entry);
                $linkPath = $publicModules . '/' . $linkName;
                $this->refreshAssetLink($assetsPath, $linkPath);
            }
        }
    }

    private function refreshAssetLink(string $assetsPath, string $linkPath): void
    {
        if (is_link($linkPath) || is_file($linkPath)) {
            @unlink($linkPath);
        } elseif (is_dir($linkPath)) {
            $this->removeDirectory($linkPath);
        }

        $canSymlink = function_exists('symlink') && is_callable('symlink');
        if ($canSymlink) {
            if (@\symlink($assetsPath, $linkPath) === false) {
                $this->copyDirectory($assetsPath, $linkPath);
            }
            return;
        }

        $this->copyDirectory($assetsPath, $linkPath);
    }

    private function ensureEnvLocalDefaults(): void
    {
        if (!class_exists(\App\Modules\Settings\Services\EnvConfigManager::class)) {
            return;
        }

        try {
            $manager = new \App\Modules\Settings\Services\EnvConfigManager();
            $manager->ensureDefaults();
        } catch (\Throwable $exception) {
            error_log('[FlatCMS][Install] Unable to initialize .env.local: ' . $exception->getMessage());
        }
    }

    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($dst) && !mkdir($dst, 0755, true) && !is_dir($dst)) {
            throw new \RuntimeException('Unable to create directory: ' . $dst);
        }
        $items = scandir($src);
        if ($items === false) {
            throw new \RuntimeException('Unable to scan directory: ' . $src);
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $from = $src . '/' . $item;
            $to = $dst . '/' . $item;
            if (is_dir($from)) {
                $this->copyDirectory($from, $to);
            } else {
                if (!@copy($from, $to)) {
                    throw new \RuntimeException('Unable to copy file: ' . $from);
                }
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * Génère les fichiers de configuration serveur (Apache, Nginx, IIS)
     */
    private function generateServerConfigs(string $siteUrl): void
    {
        $files = [];
        $serverType = $this->environment['server_type'] ?? 'unknown';

        if ($this->generateHtaccess($siteUrl)) {
            $files['htaccess'] = PUBLIC_PATH . '/.htaccess';
        }
        if ($serverType === 'iis') {
            if ($this->generatePublicWebConfig($siteUrl)) {
                $files['web_config_public'] = PUBLIC_PATH . '/web.config';
            }
            if ($this->generateRootWebConfig()) {
                $files['web_config_root'] = BASE_PATH . '/web.config';
            }
        }
        if ($this->generateNginxConfig($siteUrl)) {
            $files['nginx'] = BASE_PATH . '/nginx.conf';
        }

        $_SESSION['install_config_files'] = $files;
    }

    /**
     * Génère le fichier .htaccess avec le bon RewriteBase
     * (Apache et LiteSpeed uniquement)
     */
    private function generateHtaccess(string $siteUrl): bool
    {
        $serverType = $this->environment['server_type'] ?? 'unknown';
        
        // Ignorer si ce n'est pas Apache/LiteSpeed
        if (!in_array($serverType, ['apache', 'litespeed', 'unknown'])) {
            return false;
        }

        // Base du .htaccess (chemin URL du dossier /public si possible)
        $publicUrl = (string) $this->getPublicUrl();
        $publicPath = $publicUrl;
        if ($publicUrl !== '') {
            $parsedPublic = parse_url($publicUrl);
            if (is_array($parsedPublic)) {
                $publicPath = (string) ($parsedPublic['path'] ?? '');
            }
        }

        if ($publicPath === '' || $publicPath === '/') {
            $rewriteBase = '/';
        } else {
            $rewriteBase = rtrim($publicPath, '/') . '/';
        }

        $installVersion = $this->getInstallVersion();
        $hstsHeader = self::SECURITY_HSTS;
        $htaccessContent = <<<HTACCESS
# FlatCMS V{$installVersion} - Auto-generated .htaccess
# Généré le : {$this->getCurrentDateTime()}
# Serveur détecté : {$serverType}

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase {$rewriteBase}

    # Rediriger vers HTTPS (décommenter si nécessaire)
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Si le fichier ou dossier existe, ne rien faire
    RewriteCond %{REQUEST_URI} (^|/)(web\.config|nginx\.conf|composer\.(json|lock)|\.env(\..*)?)($|/) [NC,OR]
    RewriteCond %{REQUEST_URI} (^|/).*\.(bak|old|orig|save|swp|dist|example|backup(\..*)?)($|/) [NC,OR]
    RewriteCond %{REQUEST_URI} (^|/)\.(?!well-known(/|$)) [NC]
    RewriteRule ^ - [F,L]

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    # Rediriger tout vers index.php
    RewriteRule ^ index.php [L]
</IfModule>

# Sécurité - Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
    Header set Permissions-Policy "geolocation=(), microphone=(), camera=(), interest-cohort=()"
    Header always set Strict-Transport-Security "{$hstsHeader}"
    Header set Cross-Origin-Opener-Policy "same-origin"
    Header set Cross-Origin-Resource-Policy "same-origin"
    Header set X-Permitted-Cross-Domain-Policies "none"
    Header set X-Download-Options "noopen"
</IfModule>

# Désactiver le listing des répertoires
Options -Indexes

# Protéger les fichiers sensibles
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(json|lock|log|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "^(web\.config|nginx\.conf|composer\.(json|lock)|\.env(\..*)?)$">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(bak|old|orig|save|swp|dist|example)$">
    Order allow,deny
    Deny from all
</FilesMatch>

RewriteRule (^|/).*\.(backup)(\..*)?$ - [F,L,NC]

# Compression GZIP
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

# Cache des assets statiques
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/webp "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
    ExpiresByType font/woff2 "access plus 1 month"
</IfModule>
HTACCESS;

        $htaccessPath = PUBLIC_PATH . '/.htaccess';
        
        // Sauvegarder l'ancien fichier si existant
        $this->backupConfigFile($htaccessPath);

        if (file_put_contents($htaccessPath, $htaccessContent, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write public/.htaccess.');
        }
        return true;
    }

    /**
     * Génère un web.config pour IIS dans /public
     */
    private function generatePublicWebConfig(string $siteUrl): bool
    {
        $serverType = $this->environment['server_type'] ?? 'unknown';
        if ($serverType !== 'iis') {
            return false;
        }

        $webConfigPath = PUBLIC_PATH . '/web.config';
        $hstsHeader = self::SECURITY_HSTS;
        $content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <rewrite>
      <rules>
        <rule name="FlatCMS" stopProcessing="true">
          <match url=".*" />
          <conditions>
            <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
          </conditions>
          <action type="Rewrite" url="index.php" />
        </rule>
      </rules>
    </rewrite>
    <security>
      <requestFiltering>
        <hiddenSegments>
          <add segment=".git" />
          <add segment=".svn" />
        </hiddenSegments>
        <fileExtensions>
          <add fileExtension=".bak" allowed="false" />
          <add fileExtension=".backup" allowed="false" />
          <add fileExtension=".old" allowed="false" />
          <add fileExtension=".orig" allowed="false" />
          <add fileExtension=".md" allowed="false" />
          <add fileExtension=".log" allowed="false" />
          <add fileExtension=".lock" allowed="false" />
        </fileExtensions>
      </requestFiltering>
    </security>
    <httpProtocol>
      <customHeaders>
        <add name="X-Content-Type-Options" value="nosniff" />
        <add name="X-Frame-Options" value="SAMEORIGIN" />
        <add name="Referrer-Policy" value="strict-origin-when-cross-origin" />
        <add name="Permissions-Policy" value="geolocation=(), microphone=(), camera=(), interest-cohort=()" />
        <add name="Strict-Transport-Security" value="{$hstsHeader}" />
        <add name="Cross-Origin-Opener-Policy" value="same-origin" />
        <add name="Cross-Origin-Resource-Policy" value="same-origin" />
        <add name="X-Permitted-Cross-Domain-Policies" value="none" />
      </customHeaders>
    </httpProtocol>
    <directoryBrowse enabled="false" />
  </system.webServer>
</configuration>
XML;

        $this->backupConfigFile($webConfigPath);
        if (file_put_contents($webConfigPath, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write public/web.config.');
        }
        return true;
    }

    /**
     * Génère un web.config racine pour rediriger vers /public
     */
    private function generateRootWebConfig(): bool
    {
        $serverType = $this->environment['server_type'] ?? 'unknown';
        if ($serverType !== 'iis') {
            return false;
        }

        $rootConfigPath = BASE_PATH . '/web.config';
        $content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <rewrite>
      <rules>
        <rule name="FlatCMSRoot" stopProcessing="true">
          <match url="(.*)" />
          <conditions>
            <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
          </conditions>
          <action type="Rewrite" url="public/{R:1}" />
        </rule>
      </rules>
    </rewrite>
    <security>
      <requestFiltering>
        <hiddenSegments>
          <add segment=".git" />
          <add segment=".svn" />
          <add segment="data" />
          <add segment="storage" />
          <add segment="config" />
        </hiddenSegments>
        <fileExtensions>
          <add fileExtension=".bak" allowed="false" />
          <add fileExtension=".backup" allowed="false" />
          <add fileExtension=".old" allowed="false" />
          <add fileExtension=".orig" allowed="false" />
          <add fileExtension=".md" allowed="false" />
          <add fileExtension=".log" allowed="false" />
          <add fileExtension=".lock" allowed="false" />
        </fileExtensions>
      </requestFiltering>
    </security>
    <httpProtocol>
      <customHeaders>
        <add name="X-Content-Type-Options" value="nosniff" />
        <add name="X-Permitted-Cross-Domain-Policies" value="none" />
      </customHeaders>
    </httpProtocol>
    <directoryBrowse enabled="false" />
  </system.webServer>
</configuration>
XML;

        $this->backupConfigFile($rootConfigPath);
        if (file_put_contents($rootConfigPath, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write root web.config.');
        }
        return true;
    }

    /**
     * Génère un fichier nginx.conf à la racine
     */
    private function generateNginxConfig(string $siteUrl): bool
    {
        $parsed = parse_url($siteUrl);
        $host = $this->sanitizeServerName((string) ($parsed['host'] ?? 'example.com'));
        $projectPath = BASE_PATH;
        $hstsHeader = self::SECURITY_HSTS;
        $content = <<<NGINX
# FlatCMS auto-generated Nginx template
# IMPORTANT: adapt fastcgi_pass to your PHP-FPM socket/host before enabling.
# This template is aligned with public docroot installs (docroot = project_root/public).

server {
    listen 80;
    server_name {$host};

    root {$projectPath}/public;
    index index.php index.html;
    server_tokens off;

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=(), interest-cohort=()" always;
    add_header Strict-Transport-Security "{$hstsHeader}" always;
    add_header Cross-Origin-Opener-Policy "same-origin" always;
    add_header Cross-Origin-Resource-Policy "same-origin" always;
    add_header X-Permitted-Cross-Domain-Policies "none" always;
    add_header X-Download-Options "noopen" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        # Example TCP (common):
        # fastcgi_pass 127.0.0.1:9000;
        # Example Unix socket:
        # fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_pass 127.0.0.1:9000;
    }

    location ~ \\.php\$ {
        return 404;
    }

    location ~* (^|/)\\.(?!well-known/) {
        deny all;
    }

    location ~* \\.\\./ {
        return 400;
    }

    location ~* \\.((bak|old|orig|save|swp|dist|example)|backup(\\..*)?)\$ {
        deny all;
    }

    location ~* (^|/)(web\\.config|nginx\\.conf|composer\\.(json|lock)|\\.env(\\..*)?)\$ {
        return 404;
    }

    location ~* ^/(data|storage|config)/ {
        deny all;
    }

    location ~* ^/(app|resources|vendor)/ {
        deny all;
    }

    # Optional hardening once installation is complete:
    # location ^~ /install/ {
    #     return 403;
    # }
}
NGINX;

        $path = BASE_PATH . '/nginx.conf';
        $this->backupConfigFile($path);
        if (file_put_contents($path, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write nginx.conf template.');
        }
        return true;
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

    /**
     * @param array<int,string> $messages
     */
    private function pushErrors(array $messages): void
    {
        foreach ($messages as $message) {
            $this->pushError((string) $message);
        }
    }

    /**
     * @return array<int,string>
     */
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

    /**
     * @return array<int,array{name:string,required:bool,passed:bool}>
     */
    private function buildRequirements(): array
    {
        return [
            [
                'name' => Lang::get('requirements.php_version'),
                'required' => true,
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
                'minimum' => '8.2.0',
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

    /**
     * Retourne la date/heure actuelle formatée
     */
    private function getCurrentDateTime(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Sauvegarde un fichier de config dans storage/backups (hors webroot public).
     */
    private function backupConfigFile(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $backupDir = STORAGE_PATH . '/backups/server-config';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        $backupName = basename($path) . '.backup.' . date('Ymd_His');
        @copy($path, $backupDir . '/' . $backupName);
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

    private function sanitizeServerName(string $host): string
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return 'example.com';
        }

        if (preg_match('/^\[[0-9a-f:]+\]$/', $host) === 1) {
            return $host;
        }

        $host = explode(':', $host)[0] ?? '';
        if ($host === '' || preg_match('/^[a-z0-9.-]+$/', $host) !== 1) {
            return 'example.com';
        }

        return $host;
    }
}
