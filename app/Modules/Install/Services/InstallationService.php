<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Install/Services/InstallationService.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Modules\Install\Services;

use App\Core\RuntimeAssetPublisher;
use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Modules\Install\Support\Lang;

final class InstallationService
{
    private const INSTALL_VERSION_FALLBACK = '1.0.0';
    private const SECURITY_HSTS = 'max-age=31536000; includeSubDomains; preload';
    private array $configFiles = [];

    public function __construct(
        private array $environment = [],
        private string $locale = 'fr-FR',
        private string $publicUrl = ''
    ) {
    }

    /** Shared finalization, deliberately independent of HTTP sessions and redirects. */
    public function install(array $admin, array $site, array $design, bool $sample = false): array
    {
        $locks = new FileLockManager(STORAGE_PATH . '/cache/locks/install', 10000);
        return $locks->synchronized('installation', function () use ($admin, $site, $design, $sample): array {
            if (is_file(DATA_PATH . '/installed.lock')) {
                throw new \RuntimeException('install.error_already_installed');
            }
            $this->assertFreshInstallation();
            $this->validate($admin, $site, $design);
            Lang::init($this->locale);
            \App\Core\I18n::init($this->locale);
            $this->resetInstallationStorage();
            $adminUser = $this->createAdminUser($admin);
            $this->createSiteSettings($site, $design, $admin);
            $this->ensureInstallationLocaleConfig($this->locale);
            $seedContext = $this->buildSeedContext($adminUser, $admin, $site);
            $this->applyInstallationSeedPack('base', $seedContext);
            $this->installRequiredSystemPages();
            if ($sample) {
                $demoContext = $this->buildDemoSeedContext($seedContext);
                $this->applyInstallationSeedPack('demo', $demoContext);
                $this->applyDemoLocalizedContent($demoContext);
                $this->applyDemoSettingsOverlay();
                $this->applyInstallationPublicAssetPack('demo');
                $this->syncInstallationMediaLibrary();
            }
            $this->generateServerConfigs($site['url']);
            (new RuntimeAssetPublisher())->publishAll();
            $this->ensureEnvLocalDefaults();
            $this->disableInstallModule();
            $this->createInstallLock();
            return ['admin' => $adminUser, 'config_files' => $this->configFiles, 'version' => $this->getInstallVersion()];
        });
    }

    public static function prepareAdmin(array $input): array
    {
        $first = trim((string) ($input['first_name'] ?? ''));
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        if ($first === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            throw new \RuntimeException('install_admin_invalid');
        }
        $hash = password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
        return ['first_name' => $first, 'name' => $name, 'email' => $email, 'password' => $hash];
    }

    private function validate(array $admin, array $site, array $design): void
    {
        if (version_compare(PHP_VERSION, '8.3.0', '<')) {
            throw new \RuntimeException('install_php_version_unsupported');
        }
        foreach (['json', 'mbstring', 'session', 'fileinfo'] as $extension) {
            if (!extension_loaded($extension)) { throw new \RuntimeException('install_extension_missing:' . $extension); }
        }
        if (trim((string) ($admin['first_name'] ?? '')) === ''
            || trim((string) ($admin['name'] ?? '')) === ''
            || !filter_var($admin['email'] ?? '', FILTER_VALIDATE_EMAIL)
            || empty(password_get_info((string) ($admin['password'] ?? ''))['algo'])) {
            throw new \RuntimeException('install_admin_invalid');
        }
        $url = (string) ($site['url'] ?? '');
        if (trim((string) ($site['name'] ?? '')) === ''
            || !filter_var($url, FILTER_VALIDATE_URL)
            || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            || parse_url($url, PHP_URL_USER) !== null
            || parse_url($url, PHP_URL_QUERY) !== null
            || parse_url($url, PHP_URL_FRAGMENT) !== null
            || !in_array($site['timezone'] ?? '', \DateTimeZone::listIdentifiers(), true)) {
            throw new \RuntimeException('install_site_invalid');
        }
        if (!in_array($this->locale, Lang::getAvailableLangs(), true)
            || !in_array($design['admin_theme'] ?? '', ['default', 'admin-modern-pro'], true)
            || !in_array($design['frontend_theme'] ?? '', ['default', 'modern-pro'], true)) {
            throw new \RuntimeException('install_configuration_invalid');
        }
    }

    private function writer(): AtomicFileWriter
    {
        return new AtomicFileWriter(BASE_PATH, new FileLockManager(STORAGE_PATH . '/cache/locks/installer-files'));
    }

    private function assertFreshInstallation(): void
    {
        $guard = new \App\Core\Storage\StoragePathGuard(BASE_PATH);
        foreach (['data', 'public/uploads', 'resources/uploads/contact'] as $relative) {
            $path = $guard->resolve($relative);
            if (!is_dir($path)) { continue; }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $entry) {
                if ($entry->isLink()) { throw new \RuntimeException('install_storage_symlink_forbidden'); }
            }
        }
        // A missing lock must never turn an existing site into a fresh install.
        if ((glob(DATA_PATH . '/users/*.json') ?: []) !== []) {
            throw new \RuntimeException('install_existing_users');
        }
        $settings = json_read(DATA_PATH . '/settings.json') ?? [];
        if (!empty($settings['installed_at'])) {
            throw new \RuntimeException('install_existing_settings');
        }
    }

    private function writeTextFile(string $path, string $contents, string $error): void
    {
        try {
            $this->writer()->write($path, $contents);
        } catch (\App\Core\Storage\StorageException $exception) {
            throw new \RuntimeException($error, 0, $exception);
        }
    }

    private function getPublicUrl(): string
    {
        return $this->publicUrl;
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

        $this->writeJsonFile($lockPath, $lockData, 'Unable to write install lock file.');
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

    private function generateServerConfigs(string $siteUrl): void
    {
        $files = [];
        $serverType = $this->environment['server_type'] ?? 'unknown';

        if ($this->generatePublicHtaccess()) {
            $files['htaccess'] = PUBLIC_PATH . '/.htaccess';
        }
        if ($this->generateRootHtaccess()) {
            $files['htaccess_root'] = BASE_PATH . '/.htaccess';
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

        $this->configFiles = $files;
    }

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
        # fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_pass 127.0.0.1:9000;
    }

    # Autonomous disaster-recovery endpoint. It returns 404 unless a recovery capsule is active.
    location = /recovery.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root/recovery.php;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
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
        $this->writeTextFile($path, $content, 'Unable to write nginx.conf template.');
        return true;
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
        $this->writeTextFile($rootConfigPath, $content, 'Unable to write root web.config.');
        return true;
    }

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
        $this->writeTextFile($webConfigPath, $content, 'Unable to write public/web.config.');
        return true;
    }

    private function generatePublicHtaccess(): bool
    {
        $serverType = $this->environment['server_type'] ?? 'unknown';

        // Ignorer si ce n'est pas Apache/LiteSpeed
        if (!in_array($serverType, ['apache', 'litespeed', 'unknown'])) {
            return false;
        }

        $installVersion = $this->getInstallVersion();
        $hstsHeader = self::SECURITY_HSTS;
        $htaccessContent = <<<HTACCESS
# FlatCMS V{$installVersion} - Auto-generated .htaccess
# Généré le : {$this->getCurrentDateTime()}
# Serveur détecté : {$serverType}

<IfModule mod_rewrite.c>
    RewriteEngine On
    # Relative rewrites remain valid after copying or mounting the installation elsewhere.

    # Only the two public entry points may execute PHP.
    RewriteRule ^(?!index\.php$|recovery\.php$).*\.(?:php[0-9]*|phtml|phar)(?:/|$) - [F,L,NC]

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

# Protéger les fichiers sensibles
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

<FilesMatch "\.(json|lock|log|md)$">
    Require all denied
</FilesMatch>

<FilesMatch "^(web\.config|nginx\.conf|composer\.(json|lock)|\.env(\..*)?)$">
    Require all denied
</FilesMatch>

<FilesMatch "\.(bak|old|orig|save|swp|dist|example)$">
    Require all denied
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

        $this->writeTextFile($htaccessPath, $htaccessContent, 'Unable to write public/.htaccess.');
        return true;
    }

    private function generateRootHtaccess(): bool
    {
        $serverType = $this->environment['server_type'] ?? 'unknown';
        if (!in_array($serverType, ['apache', 'litespeed', 'unknown'], true)
            || !$this->usesProjectRootDocumentRoot()) {
            return false;
        }

        $content = <<<'HTACCESS'
# FlatCMS: project-root document root. Public assets stay under public/.
RewriteEngine On

# Never expose private application trees, including on a copied installation.
RewriteRule ^(?:app|bin|config|data|resources|storage|themes|vendor)(?:/|$) - [F,L,NC]
RewriteRule (^|/)\.(?!well-known(?:/|$)) - [F,L]
RewriteRule (^|/)(?:web\.config|nginx\.conf|composer\.(?:json|lock))$ - [F,L,NC]
RewriteRule \.(?:bak|backup(?:\..*)?|old|orig|save|swp|dist|example|log|lock)$ - [F,L,NC]

RewriteRule ^(?:index|recovery)\.php$ - [L]
RewriteRule ^public(?:/|$) - [L]
# Keep content image URLs portable without creating an uploads alias on disk.
RewriteRule ^(uploads|assets|modules|widgets)/(.*)$ public/$1/$2 [L]
RewriteRule ^ index.php [L,QSA]
HTACCESS;

        $this->writeTextFile(BASE_PATH . '/.htaccess', $content . PHP_EOL, 'Unable to write root .htaccess.');
        return true;
    }

    private function usesProjectRootDocumentRoot(): bool
    {
        $documentRoot = trim((string) ($this->environment['document_root'] ?? ''));
        if ($documentRoot === 'root') {
            return true;
        }
        if ($documentRoot === '' || $documentRoot === 'public') {
            return false;
        }

        $resolvedDocumentRoot = realpath($documentRoot) ?: $documentRoot;
        $resolvedBasePath = realpath(BASE_PATH) ?: BASE_PATH;

        return rtrim(str_replace('\\', '/', $resolvedDocumentRoot), '/')
            === rtrim(str_replace('\\', '/', $resolvedBasePath), '/');
    }

    private function getCurrentDateTime(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function disableInstallModule(): void
    {
        (new \App\Core\ModuleStateRepository())->merge(['Install' => ['enabled' => false]]);
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

    private function ensureDirectory(string $path, string $errorMessage): void
    {
        try {
            $this->writer()->ensureDirectory($path);
        } catch (\App\Core\Storage\StorageException $exception) {
            throw new \RuntimeException($errorMessage, 0, $exception);
        }
    }

    private function getModulePath(): string
    {
        return defined('APP_PATH')
            ? rtrim((string) APP_PATH, '/\\') . '/Modules/Install'
            : dirname(__DIR__);
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

    private function materializeDemoTranslatedRecords(string $directory, array $catalog, array $translatableFields): void
    {
        if ($this->isContentDocumentSeedDirectory($directory)) {
            $this->materializeDemoTranslatedContentDocuments($directory, $catalog, $translatableFields);
            return;
        }

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

    private function buildLocalizedSeedId(string $sourceId, string $locale): string
    {
        $normalizedSource = preg_replace('/[^a-zA-Z0-9_-]/', '', $sourceId) ?? $sourceId;
        $normalizedLocale = strtolower(str_replace('-', '_', trim($locale)));
        $normalizedLocale = preg_replace('/[^a-z0-9_]/', '', $normalizedLocale) ?? $normalizedLocale;

        return rtrim($normalizedSource, '_') . '_' . $normalizedLocale;
    }

    private function materializeDemoTranslatedContentDocuments(string $directory, array $catalog, array $translatableFields): void
    {
        $entity = $this->contentDocumentEntityFromDirectory($directory);
        if ($entity === '') {
            return;
        }

        $store = \App\Core\ContentDocumentStore::for($entity);

        foreach ($catalog as $sourceId => $translations) {
            $normalizedSourceId = trim((string) $sourceId);
            if ($normalizedSourceId === '' || !is_array($translations)) {
                continue;
            }

            $source = $store->find($normalizedSourceId);
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

            $source = $store->update($normalizedSourceId, [
                'translation_group' => $translationGroup,
                'locale' => trim((string) ($source['locale'] ?? $sourceLocale)) !== ''
                    ? trim((string) $source['locale'])
                    : $sourceLocale,
                'source_locale' => $sourceLocale,
            ]) ?? $source;

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

                $localizedId = (string) $localized['id'];
                if ($store->exists($localizedId)) {
                    $store->update($localizedId, $localized);
                    continue;
                }

                $store->create($localized);
            }
        }
    }

    private function contentDocumentEntityFromDirectory(string $directory): string
    {
        $normalized = str_replace('\\', '/', rtrim($directory, '/'));
        $dataPrefix = str_replace('\\', '/', rtrim(DATA_PATH, '/')) . '/';

        if (!str_starts_with($normalized, $dataPrefix)) {
            return '';
        }

        return trim(substr($normalized, strlen($dataPrefix)), '/');
    }

    private function isContentDocumentSeedDirectory(string $directory): bool
    {
        return in_array($this->contentDocumentEntityFromDirectory($directory), ['core/pages', 'core/posts'], true);
    }

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

    private function installRequiredSystemPages(): void
    {
        if (!class_exists(\App\Modules\Pages\Support\SystemPages::class)) {
            return;
        }

        \App\Core\I18n::load('Pages');
        \App\Modules\Pages\Support\SystemPages::ensureRequired(
            \App\Core\ContentDocumentStore::for('core/pages'),
            static fn (string $key): string => (string) \App\Core\I18n::get($key, 'Pages')
        );
    }

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

            if (str_ends_with($targetPath, '.json')) {
                $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($data)) {
                    throw new \RuntimeException('install_seed_json_invalid');
                }
                $this->writeJsonFile($targetPath, $this->replaceDemoCatalogPlaceholders($data, $replacements), 'install_seed_write_failed');
            } else {
                $escaped = array_map(static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $replacements);
                $this->writeTextFile($targetPath, strtr($content, $escaped), 'install_seed_write_failed');
            }
        }
    }

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

    private function resolveInstallationLocale(): string
    {
        return $this->locale;
    }

    private function createSiteSettings(array $site, array $design, array $admin = []): void
    {
        $settingsFile = DATA_PATH . '/settings.json';

        $settings = [
            'site_name' => $site['name'],
            'site_description' => $site['description'] ?? '',
            'site_slogan' => '',
            'site_name_enabled' => 1,
            'site_slogan_enabled' => 1,
            'site_logo_variant' => 'compact',
            'site_url' => $site['url'],
            'site_email' => '',
            'timezone' => $site['timezone'],
            'language' => $this->locale,
            'default_language' => $this->locale,
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

        $this->writeJsonFile($settingsFile, $settings, 'Unable to write settings file.');
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
            'first_name' => $admin['first_name'],
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

        $this->writeJsonFile($usersDir . '/' . $id . '.json', $userData, 'Unable to write administrator user file.');

        return $userData;
    }

    private function resetInstallationStorage(): void
    {
        $directoriesToReset = [
            DATA_PATH . '/users',
            DATA_PATH . '/core/auth',
            DATA_PATH . '/languages',
            DATA_PATH . '/core/pages',
            DATA_PATH . '/core/posts',
            DATA_PATH . '/core/categories',
            DATA_PATH . '/core/contact_forms',
            DATA_PATH . '/core/contact_messages',
            DATA_PATH . '/core/comments',
            DATA_PATH . '/core/menus',
            DATA_PATH . '/core/footer',
            DATA_PATH . '/core/media',
            PUBLIC_PATH . '/uploads/archives',
            PUBLIC_PATH . '/uploads/documents',
            PUBLIC_PATH . '/uploads/images',
            PUBLIC_PATH . '/uploads/pdf',
            PUBLIC_PATH . '/uploads/sounds',
            PUBLIC_PATH . '/uploads/spreadsheets',
            PUBLIC_PATH . '/uploads/videos',
            PUBLIC_PATH . '/uploads/logo',
            PUBLIC_PATH . '/uploads/cache/runtime-css',
            BASE_PATH . '/resources/uploads/contact',
        ];

        foreach ($directoriesToReset as $directory) {
            $this->resetDirectory($directory);
        }

        foreach ($this->legacyStorageDirectories() as $directory) {
            if (is_dir($directory)) {
                $this->removeDirectory($directory);
            }
        }

        // Réinitialiser le registre media natif
        $this->ensureDirectory(DATA_PATH . '/core/media', 'Unable to create media directory.');
        $this->writeJsonFile(
            DATA_PATH . '/core/media/media.json',
            [],
            'Unable to reset media registry.'
        );
    }

    private function writeJsonFile(string $path, array $payload, string $errorMessage): void
    {
        try {
            json_write($path, $payload);
        } catch (\App\Core\Storage\StorageException $exception) {
            throw new \RuntimeException($errorMessage, 0, $exception);
        }
    }

    private function removeDirectory(string $dir): void
    {
        $pathGuard = new \App\Core\Storage\StoragePathGuard(BASE_PATH);
        $dir = $pathGuard->resolve($dir);
        $items = scandir($dir);
        if ($items === false) {
            throw new \RuntimeException('install_directory_unreadable');
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                if (!unlink($path)) { throw new \RuntimeException('install_file_removal_failed'); }
            }
        }
        if (!rmdir($dir)) { throw new \RuntimeException('install_directory_removal_failed'); }
    }

    private function legacyStorageDirectories(): array
    {
        return [
            DATA_PATH . '/auth',
            DATA_PATH . '/comments',
            DATA_PATH . '/menus',
            DATA_PATH . '/pages',
            DATA_PATH . '/footer',
            PUBLIC_PATH . '/uploads/files',
            PUBLIC_PATH . '/uploads/media',
        ];
    }

    private function resetDirectory(string $path): void
    {
        $pathGuard = new \App\Core\Storage\StoragePathGuard(BASE_PATH);
        $path = $pathGuard->resolve($path);
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
                    if (!rmdir($itemPath)) { throw new \RuntimeException('install_directory_removal_failed'); }
                    continue;
                }

                if (!unlink($itemPath)) { throw new \RuntimeException('install_file_removal_failed'); }
            }

            if (!rmdir($path)) { throw new \RuntimeException('install_directory_removal_failed'); }
        }

        if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to reset directory: ' . $path);
        }
    }
}
