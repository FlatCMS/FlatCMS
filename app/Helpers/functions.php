<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

if (!function_exists('app')) {
    function app(): \App\Core\App
    {
        return \App\Core\App::getInstance();
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }

        // Convert string booleans
        switch (strtolower((string) $value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }

        return $value;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return app()->config($key, $default);
    }
}

if (!function_exists('flatcms_version')) {
    /**
     * Retourne le manifeste core FlatCMS, ou une clé spécifique.
     */
    function flatcms_manifest(?string $key = null, mixed $default = null): mixed
    {
        return \App\Core\CoreManifest::get($key, $default);
    }
}

if (!function_exists('flatcms_product_name')) {
    function flatcms_product_name(string $fallback = 'FlatCMS'): string
    {
        return \App\Core\CoreManifest::name($fallback);
    }
}

if (!function_exists('flatcms_version')) {
    /**
     * Retourne la version core FlatCMS depuis flatcms.json, avec fallback VERSION.
     */
    function flatcms_version(string $fallback = '1.0.0'): string
    {
        return \App\Core\CoreManifest::version($fallback);
    }
}

if (!function_exists('base_url')) {
    function base_url(): string
    {
        $isSecure = false;
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            $isSecure = true;
        } elseif ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            $isSecure = true;
        } else {
            $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
            if ($forwardedProto !== '' && in_array('https', array_map('trim', explode(',', $forwardedProto)), true)) {
                $isSecure = true;
            } elseif (strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https') {
                $isSecure = true;
            } else {
                $cfVisitor = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');
                if ($cfVisitor !== '') {
                    $decoded = json_decode($cfVisitor, true);
                    if (is_array($decoded) && strtolower((string) ($decoded['scheme'] ?? '')) === 'https') {
                        $isSecure = true;
                    }
                }
            }
        }

        $configured = rtrim((string) env('APP_URL', ''), '/');
        if ($configured !== '') {
            if (!preg_match('~^https?://~i', $configured) && !str_starts_with($configured, '//') && !str_starts_with($configured, '/')) {
                $configured = ($isSecure ? 'https://' : 'http://') . $configured;
            }
            $parsed = parse_url($configured);
            if (is_array($parsed)) {
                // If APP_URL host differs from current host, prefer auto-detection.
                $currentHostHeader = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
                $currentHost = strtolower($currentHostHeader);
                $currentPort = null;
                if ($currentHostHeader !== '') {
                    // IPv6 host in brackets: [::1]:8080
                    if (str_starts_with($currentHostHeader, '[')) {
                        $end = strpos($currentHostHeader, ']');
                        if ($end !== false) {
                            $currentHost = strtolower(substr($currentHostHeader, 1, $end - 1));
                            $rest = substr($currentHostHeader, $end + 1);
                            if (str_starts_with($rest, ':')) {
                                $portRaw = substr($rest, 1);
                                if ($portRaw !== '' && ctype_digit($portRaw)) {
                                    $currentPort = (int) $portRaw;
                                }
                            }
                        }
                    } else {
                        $firstColon = strpos($currentHostHeader, ':');
                        $lastColon = strrpos($currentHostHeader, ':');
                        if ($firstColon !== false && $lastColon !== false && $firstColon === $lastColon) {
                            $currentHost = strtolower(substr($currentHostHeader, 0, $lastColon));
                            $portRaw = substr($currentHostHeader, $lastColon + 1);
                            if ($portRaw !== '' && ctype_digit($portRaw)) {
                                $currentPort = (int) $portRaw;
                            }
                        }
                    }
                }

                $configuredHost = strtolower((string) ($parsed['host'] ?? ''));
                $configuredScheme = strtolower((string) ($parsed['scheme'] ?? ($isSecure ? 'https' : 'http')));
                $configuredPort = isset($parsed['port']) && is_int($parsed['port']) ? $parsed['port'] : null;

                $currentEffectivePort = $currentPort ?? ($isSecure ? 443 : 80);
                $configuredEffectivePort = $configuredPort ?? ($configuredScheme === 'https' ? 443 : 80);

                if ($currentHost !== '' && $configuredHost !== '' && ($currentHost !== $configuredHost || $currentEffectivePort !== $configuredEffectivePort)) {
                    $configured = '';
                } else {
                    // If APP_URL has no path but the app is served from a subdirectory, append it.
                    $path = (string) ($parsed['path'] ?? '');
                    $hasHostOrScheme = isset($parsed['host']) || isset($parsed['scheme']);
                    if ($hasHostOrScheme && ($path === '' || $path === '/')) {
                        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
                        $scriptName = str_replace('\\', '/', $scriptName);
                        $dir = rtrim(dirname($scriptName), '/');
                        if ($dir === '/' || $dir === '.' || $dir === '\\') {
                            $dir = '';
                        }
                        if ($dir !== '' && !str_starts_with($dir, '/')) {
                            $dir = '/' . $dir;
                        }
                        if ($dir !== '') {
                            $dir = str_replace(' ', '%20', $dir);
                        }
                        // Avoid leaking "/public" into URLs when requests are rewritten internally to /public/index.php.
                        if ($dir !== '' && str_ends_with($dir, '/public')) {
                            $requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
                            if ($requestPath === '') {
                                $requestPath = '/';
                            }
                            if (!str_starts_with($requestPath, $dir)) {
                                $dir = substr($dir, 0, -7);
                                if ($dir === '/') {
                                    $dir = '';
                                }
                            }
                        }

                        if ($dir !== '' && !str_ends_with($configured, $dir)) {
                            $configured .= $dir;
                        }
                    }
                }
            }

            if ($configured !== '') {
                return $configured;
            }
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptName = str_replace('\\', '/', $scriptName);
        $dir = rtrim(dirname($scriptName), '/');
        if ($dir === '/' || $dir === '.' || $dir === '\\') {
            $dir = '';
        }
        if ($dir !== '' && !str_starts_with($dir, '/') && !preg_match('~^https?://~i', $dir)) {
            $dir = '/' . $dir;
        }
        if ($dir !== '') {
            $dir = str_replace(' ', '%20', $dir);
        }
        // Avoid leaking "/public" into URLs when requests are rewritten internally to /public/index.php.
        if ($dir !== '' && str_ends_with($dir, '/public')) {
            $requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
            if ($requestPath === '') {
                $requestPath = '/';
            }
            if (!str_starts_with($requestPath, $dir)) {
                $dir = substr($dir, 0, -7);
                if ($dir === '/') {
                    $dir = '';
                }
            }
        }
        return $dir;
    }
}

if (!function_exists('flatcms_pretty_urls_enabled')) {
    /**
     * Détermine si les URL "propres" doivent être utilisées.
     */
    function flatcms_pretty_urls_enabled(?array $settings = null): bool
    {
        $settings = $settings ?? \App\Core\FlatFile::settings();

        $mode = strtolower(trim((string) ($settings['url_routing_mode'] ?? 'auto')));
        if (!in_array($mode, ['auto', 'pretty', 'fallback'], true)) {
            $mode = 'auto';
        }

        if ($mode === 'fallback') {
            return false;
        }

        if ($mode === 'pretty') {
            return true;
        }

        $lastStatus = strtolower(trim((string) ($settings['url_rewrite_last_status'] ?? 'unknown')));
        if ($lastStatus === 'ok') {
            return true;
        }
        if ($lastStatus === 'failed' || $lastStatus === 'disabled') {
            return false;
        }

        // Mode auto sans historique: détection best effort basée sur la requête courante.
        if (isset($_GET['path']) || isset($_GET['route'])) {
            return false;
        }

        $requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($requestPath !== '' && preg_match('#/index\\.php$#i', $requestPath) === 1) {
            return false;
        }

        return true;
    }
}

if (!function_exists('static_base_url')) {
    function static_base_url(): string
    {
        $base = rtrim(base_url(), '/');
        if ($base !== '' && str_ends_with($base, '/public')) {
            return $base;
        }

        $settings = \App\Core\FlatFile::settings();
        $pretty = flatcms_pretty_urls_enabled($settings);

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

        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $docRootReal = $docRoot !== '' ? (realpath($docRoot) ?: $docRoot) : '';
        $publicPath = defined('PUBLIC_PATH') ? PUBLIC_PATH : '';
        $publicReal = $publicPath !== '' ? (realpath($publicPath) ?: $publicPath) : '';
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        $baseReal = $basePath !== '' ? (realpath($basePath) ?: $basePath) : '';

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
        // If document root already points to /public, never add /public
        $needsPublic = ($docIsBase && !$docIsPublic) || (!$pretty && !$docIsPublic);
        if (!$needsPublic && !$docIsPublic) {
            $fallbackRequest = isset($_GET['path']) || isset($_GET['route']);
            if ($fallbackRequest) {
                $needsPublic = true;
            }
        }

        if ($needsPublic) {
            return $base === '' ? '/public' : ($base . '/public');
        }

        return $base;
    }
}

if (!function_exists('flatcms_normalize_upload_media_path')) {
    function flatcms_normalize_upload_media_path(string $rawPath): string
    {
        $value = trim(str_replace('\\', '/', $rawPath));
        if ($value === '') {
            return '';
        }

        $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
        if ($path === '') {
            $path = $value;
        }

        $path = '/' . ltrim(str_replace('\\', '/', $path), '/');

        $baseCandidates = [];
        $staticBase = rtrim(static_base_url(), '/');
        if ($staticBase !== '') {
            $baseCandidates[] = $staticBase;
        }
        $baseUrl = rtrim(base_url(), '/');
        if ($baseUrl !== '' && $baseUrl !== $staticBase) {
            $baseCandidates[] = $baseUrl;
        }

        foreach ($baseCandidates as $baseCandidate) {
            $basePath = (string) (parse_url($baseCandidate, PHP_URL_PATH) ?? $baseCandidate);
            if ($basePath === '' || $basePath === '/') {
                continue;
            }
            if (!str_starts_with($basePath, '/')) {
                $basePath = '/' . ltrim($basePath, '/');
            }
            if ($path === $basePath) {
                $path = '/';
                break;
            }
            if (str_starts_with($path, $basePath . '/')) {
                $path = substr($path, strlen($basePath));
                if ($path === '') {
                    $path = '/';
                }
                break;
            }
        }

        if ($path === '/favicon.ico') {
            return '/favicon.ico';
        }

        if (preg_match('#^/public/uploads/(.+)$#i', $path, $match) === 1) {
            return '/uploads/' . ltrim((string) ($match[1] ?? ''), '/');
        }

        if (preg_match('#^/uploads/(.+)$#i', $path, $match) === 1) {
            return '/uploads/' . ltrim((string) ($match[1] ?? ''), '/');
        }

        if (preg_match('#^/logo/(.+)$#i', $path, $match) === 1) {
            return '/uploads/logo/' . ltrim((string) ($match[1] ?? ''), '/');
        }

        return '';
    }
}

if (!function_exists('site_media_url')) {
    function site_media_url(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }

        if (
            str_starts_with($value, 'data:') ||
            str_starts_with($value, 'blob:')
        ) {
            return $value;
        }

        $normalizedUploadPath = flatcms_normalize_upload_media_path($value);
        if ($normalizedUploadPath !== '') {
            $staticBase = rtrim(static_base_url(), '/');
            $url = $staticBase === '' ? $normalizedUploadPath : ($staticBase . $normalizedUploadPath);
            $filePath = '';
            if (defined('PUBLIC_PATH')) {
                $filePath = rtrim((string) PUBLIC_PATH, '/') . $normalizedUploadPath;
            }
            if ($filePath === '' || !is_file($filePath)) {
                $basePath = defined('BASE_PATH') ? rtrim((string) BASE_PATH, '/') : '';
                if ($basePath !== '') {
                    $candidate = $basePath . '/public' . $normalizedUploadPath;
                    if (is_file($candidate)) {
                        $filePath = $candidate;
                    }
                }
            }
            return append_asset_version($url, $filePath);
        }

        if (preg_match('~^(https?:)?//~i', $value) === 1) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return url($value);
        }

        return url('/uploads/' . ltrim($value, '/'));
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $baseUrl = base_url();
        $raw = trim($path);
        [$pathOnly, $query] = array_pad(explode('?', $raw, 2), 2, '');
        $pathOnly = '/' . ltrim($pathOnly, '/');
        if ($pathOnly !== '/') {
            $pathOnly = rtrim($pathOnly, '/');
        }

        $settings = \App\Core\FlatFile::settings();
        $pretty = flatcms_pretty_urls_enabled($settings);
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $docRootReal = $docRoot !== '' ? (realpath($docRoot) ?: $docRoot) : '';
        $basePath = defined('BASE_PATH') ? BASE_PATH : '';
        $baseReal = $basePath !== '' ? (realpath($basePath) ?: $basePath) : '';
        $publicPath = defined('PUBLIC_PATH') ? PUBLIC_PATH : '';
        $publicReal = $publicPath !== '' ? (realpath($publicPath) ?: $publicPath) : '';
        $docIsBase = $docRootReal !== '' && $baseReal !== '' && rtrim($docRootReal, '/') === rtrim($baseReal, '/');
        $docIsPublic = $docRootReal !== '' && $publicReal !== '' && rtrim($docRootReal, '/') === rtrim($publicReal, '/');
        if ($docIsBase && !$docIsPublic) {
            $pretty = false;
        }

        $isStatic = preg_match('#^/(assets|themes|modules|widgets|uploads|release|favicon\\.ico|public/(assets|themes|modules|widgets|uploads|release|favicon\\.ico))#', $pathOnly) === 1;
        $baseForPath = $isStatic ? static_base_url() : $baseUrl;
        if ($isStatic && str_starts_with($pathOnly, '/public/')) {
            $pathOnly = substr($pathOnly, 7);
            if ($pathOnly === false || $pathOnly === '') {
                $pathOnly = '/';
            } elseif (!str_starts_with($pathOnly, '/')) {
                $pathOnly = '/' . $pathOnly;
            }
        }
        if (!$pretty && !$isStatic) {
            $pathParam = $pathOnly === '/' ? '/' : ltrim($pathOnly, '/');
            $url = $baseUrl . '/index.php?path=' . $pathParam;
            if ($query !== '') {
                $url .= '&' . $query;
            }
            return $url;
        }

        $url = $baseForPath . $pathOnly;
        if ($query !== '') {
            $url .= '?' . $query;
        }
        return $url;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $baseUrl = static_base_url();
        $relativePath = ltrim($path, '/');
        $url = $baseUrl . '/assets/' . $relativePath;
        $filePath = (defined('PUBLIC_PATH') ? PUBLIC_PATH : '') . '/assets/' . $relativePath;
        return append_asset_version($url, $filePath);
    }
}

if (!function_exists('runtime_css_asset')) {
    function runtime_css_asset(string $css, string $scope = 'runtime', string $entity = ''): string
    {
        $cleanCss = trim(str_replace('</style>', '', $css));
        if ($cleanCss === '') {
            return '';
        }

        $safeScope = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]/', '', $scope));
        if ($safeScope === '') {
            $safeScope = 'runtime';
        }

        $safeEntity = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]/', '', $entity));
        $hash = substr(sha1($safeScope . '|' . $cleanCss), 0, 16);
        $fileName = $safeScope . ($safeEntity !== '' ? '-' . $safeEntity : '') . '-' . $hash . '.css';

        $publicPath = defined('PUBLIC_PATH') ? PUBLIC_PATH : '';
        if ($publicPath === '') {
            return '';
        }

        $dir = rtrim($publicPath, '/') . '/uploads/cache/runtime-css';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return '';
        }

        $filePath = $dir . '/' . $fileName;
        if (!is_file($filePath)) {
            $payload = "/* generated runtime css */\n" . $cleanCss;
            if (@file_put_contents($filePath, $payload, LOCK_EX) === false) {
                return '';
            }
        }

        $baseUrl = static_base_url();
        $url = $baseUrl . '/uploads/cache/runtime-css/' . rawurlencode($fileName);
        return append_asset_version($url, $filePath);
    }
}

if (!function_exists('theme_asset')) {
    function theme_asset(string $path, string $type = 'admin'): string
    {
        // Read from settings.json (dynamic) first, fallback to config (static)
        $settings = \App\Core\FlatFile::settings();
        
        if ($type === 'admin') {
            $theme = $settings['admin_theme'] ?? config('app.admin_theme', 'admin-modern-pro');
        } else {
            $theme = $settings['frontend_theme'] ?? config('app.frontend_theme', 'default');
        }
        
        $relativePath = ltrim($path, '/');
        $baseUrl = static_base_url();
        $url = $baseUrl . "/themes/{$type}/{$theme}/assets/" . $relativePath;

        // URL /themes/... is served from PUBLIC_PATH, so prefer that file for cache-busting.
        $filePath = (defined('PUBLIC_PATH') ? PUBLIC_PATH : '') . "/themes/{$type}/{$theme}/assets/" . $relativePath;
        if (!is_file($filePath)) {
            $filePath = (defined('BASE_PATH') ? BASE_PATH : '') . "/themes/{$type}/{$theme}/assets/" . $relativePath;
        }

        return append_asset_version($url, $filePath);
    }
}

if (!function_exists('hook_register')) {
    function hook_register(string $hook, mixed $callback, array $meta = []): void
    {
        \App\Core\Hook::register($hook, $callback, $meta);
    }
}

if (!function_exists('hook_run')) {
    function hook_run(string $hook, mixed $payload = null): array
    {
        return \App\Core\Hook::run($hook, $payload);
    }
}

if (!function_exists('hook_definitions')) {
    function hook_definitions(): array
    {
        return \App\Core\Hook::definitions();
    }
}

if (!function_exists('guided_tour_step')) {
    function guided_tour_step(string $selector, string $title, string $content, string $placement = 'top', array $extra = []): array
    {
        $step = [
            'selector' => $selector,
            'title' => $title,
            'content' => $content,
            'placement' => $placement,
        ];

        foreach ($extra as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $step[$key] = $value;
        }

        return $step;
    }
}

if (!function_exists('guided_tour_collect_module_tours')) {
    function guided_tour_collect_module_tours(array $payload = []): array
    {
        $tours = [];

        foreach (hook_run('admin.guided_tour.module_tours', $payload) as $result) {
            if (!is_array($result) || $result === []) {
                continue;
            }

            foreach ($result as $rawKey => $tour) {
                if (!is_array($tour)) {
                    continue;
                }

                $key = strtolower(trim((string) $rawKey));
                $key = preg_replace('/[^a-z0-9_-]/', '', $key) ?? '';
                if ($key === '') {
                    continue;
                }

                $routes = array_values(array_filter(array_map(static function ($route): string {
                    return trim((string) $route);
                }, is_array($tour['routes'] ?? null) ? $tour['routes'] : []), static fn(string $route): bool => $route !== ''));
                $steps = array_values(array_filter(is_array($tour['steps'] ?? null) ? $tour['steps'] : [], 'is_array'));

                if ($routes === [] || $steps === []) {
                    continue;
                }

                if (!isset($tours[$key])) {
                    $tours[$key] = [
                        'routes' => [],
                        'steps' => [],
                    ];
                }

                $tours[$key]['routes'] = array_values(array_unique(array_merge($tours[$key]['routes'], $routes)));
                $tours[$key]['steps'] = array_merge($tours[$key]['steps'], $steps);
            }
        }

        return $tours;
    }
}

if (!function_exists('copy_module_assets_directory')) {
    function copy_module_assets_directory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination) && !@mkdir($destination, 0755, true) && !is_dir($destination)) {
            return false;
        }

        $sourceReal = realpath($source);
        if ($sourceReal === false) {
            return false;
        }

        $sourcePrefix = rtrim(str_replace('\\', '/', $sourceReal), '/') . '/';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceReal, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $pathname = str_replace('\\', '/', $item->getPathname());
            $relativePath = ltrim(substr($pathname, strlen($sourcePrefix)), '/');
            if ($relativePath === '') {
                continue;
            }

            $targetPath = rtrim($destination, '/') . '/' . $relativePath;
            if ($item->isDir()) {
                if (!is_dir($targetPath) && !@mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    return false;
                }
                continue;
            }

            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                return false;
            }

            if (!@copy($item->getPathname(), $targetPath)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('flatcms_resolve_module_asset_contract')) {
    function flatcms_resolve_module_asset_contract(string $module): array
    {
        $module = trim($module);
        if ($module === '') {
            return [
                'name' => '',
                'key' => '',
                'location' => 'module',
                'source_path' => '',
                'source_status' => 'absent',
                'public_base' => 'modules',
                'public_key' => '',
                'public_path' => '',
                'public_url' => static_base_url() . '/modules',
            ];
        }

        static $metaCache = [];
        if (!isset($metaCache[$module])) {
            $manager = new \App\Core\ModuleManager([
                BASE_PATH . '/app/Modules',
                BASE_PATH . '/app/Extensions',
                BASE_PATH . '/app/Plugins',
            ], BASE_PATH . '/data/modules.json');
            $metaCache[$module] = $manager->get($module) ?? [];
        }

        $meta = is_array($metaCache[$module]) ? $metaCache[$module] : [];
        $location = strtolower((string) ($meta['location'] ?? 'module'));
        $defaultPublicBase = match ($location) {
            'extension' => 'assets/extensions',
            'plugin' => 'assets/plugins',
            default => 'modules',
        };
        $publicBase = trim((string) ($meta['public_assets_base'] ?? $defaultPublicBase), '/');
        $publicKey = trim((string) ($meta['public_assets_key'] ?? strtolower($module)), '/');
        $sourcePath = trim((string) ($meta['assets_path'] ?? ''));
        $sourceStatus = trim((string) ($meta['assets_status'] ?? ($sourcePath !== '' ? 'ok' : 'absent')));
        $publicPath = trim((string) ($meta['public_assets_path'] ?? ''));

        if ($sourcePath === '') {
            $candidates = [
                (defined('APP_PATH') ? APP_PATH : '') . '/Modules/' . $module . '/Assets',
                (defined('APP_PATH') ? APP_PATH : '') . '/Extensions/' . $module . '/Assets',
                (defined('APP_PATH') ? APP_PATH : '') . '/Plugins/' . $module . '/Assets',
            ];
            foreach ($candidates as $candidate) {
                if (is_dir($candidate)) {
                    $sourcePath = $candidate;
                    $sourceStatus = 'ok';
                    if (str_contains($candidate, '/Extensions/')) {
                        $location = 'extension';
                        if ($publicBase === 'modules') {
                            $publicBase = 'assets/extensions';
                        }
                    } elseif (str_contains($candidate, '/Plugins/')) {
                        $location = 'plugin';
                        if ($publicBase === 'modules') {
                            $publicBase = 'assets/plugins';
                        }
                    }
                    break;
                }
            }
        }

        if ($publicPath === '' && defined('PUBLIC_PATH') && trim((string) PUBLIC_PATH) !== '') {
            $publicPath = rtrim((string) PUBLIC_PATH, '/') . '/' . $publicBase . '/' . $publicKey;
        }

        return [
            'name' => (string) ($meta['name'] ?? $module),
            'key' => (string) ($meta['key'] ?? $module),
            'location' => $location,
            'source_path' => $sourcePath,
            'source_status' => $sourceStatus,
            'public_base' => $publicBase,
            'public_key' => $publicKey,
            'public_path' => $publicPath,
            'public_url' => static_base_url() . '/' . $publicBase . '/' . $publicKey,
        ];
    }
}

if (!function_exists('ensure_module_assets_link')) {
    function ensure_module_assets_link(string $module): void
    {
        $contract = flatcms_resolve_module_asset_contract($module);
        $assetsPath = trim((string) ($contract['source_path'] ?? ''));
        if ($assetsPath === '') {
            return;
        }

        $linkPath = trim((string) ($contract['public_path'] ?? ''));
        if ($linkPath === '') {
            return;
        }

        $publicBasePath = dirname($linkPath);
        if (!is_dir($publicBasePath) && !@mkdir($publicBasePath, 0755, true) && !is_dir($publicBasePath)) {
            return;
        }
        $expectedReal = realpath($assetsPath);

        if (is_link($linkPath)) {
            $currentReal = realpath($linkPath);
            if ($expectedReal !== false && $currentReal === $expectedReal) {
                return;
            }
            if (!@unlink($linkPath) && file_exists($linkPath)) {
                return;
            }
        } elseif (is_file($linkPath)) {
            if (!@unlink($linkPath) && file_exists($linkPath)) {
                return;
            }
        } elseif (is_dir($linkPath)) {
            // Fallback mode (copied assets): keep directory and refresh contents.
            copy_module_assets_directory($assetsPath, $linkPath);
            return;
        }

        if (function_exists('symlink')) {
            $linked = @symlink($assetsPath, $linkPath);
            if ($linked !== false && is_link($linkPath)) {
                return;
            }
        }

        copy_module_assets_directory($assetsPath, $linkPath);
    }
}

if (!function_exists('module_asset')) {
    function module_asset(string $module, string $path): string
    {
        ensure_module_assets_link($module);

        $relativePath = ltrim($path, '/');
        $contract = flatcms_resolve_module_asset_contract($module);
        $baseUrl = rtrim((string) ($contract['public_url'] ?? ''), '/');
        if ($baseUrl === '') {
            $baseUrl = rtrim(static_base_url() . '/modules/' . strtolower($module), '/');
        }
        $url = $baseUrl . '/' . $relativePath;

        $candidates = [
            trim((string) ($contract['public_path'] ?? '')) . '/' . $relativePath,
            (defined('APP_PATH') ? APP_PATH : '') . '/Modules/' . $module . '/Assets/' . $relativePath,
            (defined('APP_PATH') ? APP_PATH : '') . '/Extensions/' . $module . '/Assets/' . $relativePath,
            (defined('APP_PATH') ? APP_PATH : '') . '/Plugins/' . $module . '/Assets/' . $relativePath,
        ];

        $filePath = '';
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $filePath = $candidate;
                break;
            }
        }

        return append_asset_version($url, $filePath);
    }
}

if (!function_exists('flatcms_is_dev_runtime')) {
    function flatcms_is_dev_runtime(): bool
    {
        $appEnv = strtolower(trim((string) env('APP_ENV', '')));
        if (in_array($appEnv, ['dev', 'development', 'local'], true)) {
            return true;
        }

        $forced = strtolower(trim((string) env('FLATCMS_FORCE_DEV_CACHE_BUST', '')));
        if (in_array($forced, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        return is_local_host();
    }
}

if (!function_exists('flatcms_asset_version_mode')) {
    function flatcms_asset_version_mode(): string
    {
        return flatcms_is_dev_runtime() ? 'hash' : 'mtime';
    }
}

if (!function_exists('append_asset_version')) {
    function append_asset_version(string $url, string $filePath = ''): string
    {
        if ($filePath === '' || !is_file($filePath)) {
            return $url;
        }

        $mode = flatcms_asset_version_mode();
        if ($mode === 'hash') {
            $hash = @md5_file($filePath);
            if (is_string($hash) && $hash !== '') {
                $separator = str_contains($url, '?') ? '&' : '?';
                return $url . $separator . 'v=' . substr($hash, 0, 12);
            }
        }

        $mtime = @filemtime($filePath);
        if ($mtime === false) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'v=' . $mtime;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void
    {
        (new \App\Core\Response())->redirect($url);
    }
}

if (!function_exists('back')) {
    function back(): void
    {
        (new \App\Core\Response())->back();
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = app()->session();
        
        if ($key === null) {
            return $session;
        }

        return $session->get($key, $default);
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        app()->session()->flash($type, $message);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = null): mixed
    {
        // Prefer view-scoped data (flash is consumed once at render time).
        $view = \App\Core\View::getInstance();
        if ($view) {
            $old = $view->get('old', []);
            if (is_array($old) && array_key_exists($key, $old)) {
                return $old[$key];
            }
        }

        // Fallback: session flash (non-view contexts).
        $old = app()->session()->getFlash('old', []);
        return is_array($old) ? ($old[$key] ?? $default) : $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return app()->session()->token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('method_field')) {
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

if (!function_exists('__')) {
    function __(string $key, string $module = 'Core', array $replace = []): string
    {
        return \App\Core\I18n::get($key, $module, $replace);
    }
}

if (!function_exists('locale')) {
    function locale(): string
    {
        return \App\Core\I18n::getLocale();
    }
}

if (!function_exists('locale_url')) {
    function locale_url(string $locale): string
    {
        return \App\Core\I18n::getLocaleUrl($locale);
    }
}

if (!function_exists('available_languages')) {
    function available_languages(): array
    {
        $languages = [];
        $langPath = BASE_PATH . '/data/languages';

        if (is_dir($langPath)) {
            foreach (glob($langPath . '/*.json') as $file) {
                $code = basename($file, '.json');
                $config = json_read($file);
                if (is_array($config)) {
                    $languages[$code] = $config;
                } else {
                    $languages[$code] = [
                        'name' => strtoupper($code),
                        'native' => strtoupper($code),
                        'active' => true,
                    ];
                }
            }
        }

        if (empty($languages)) {
            foreach (\App\Core\I18n::getSupportedLocales() as $code) {
                $languages[$code] = [
                    'name' => strtoupper($code),
                    'native' => strtoupper($code),
                    'active' => true,
                ];
            }
        }

        $languages = \App\Core\I18n::localizeLanguageCatalog($languages, \App\Core\I18n::getLocale());
        ksort($languages);
        return $languages;
    }
}

if (!function_exists('module_enabled')) {
    function module_enabled(string $module): bool
    {
        $module = trim($module);
        if ($module === '') {
            return false;
        }

        static $enabledMap = null;
        if (!is_array($enabledMap)) {
            try {
                $manager = new \App\Core\ModuleManager([
                    BASE_PATH . '/app/Modules',
                    BASE_PATH . '/app/Extensions',
                    BASE_PATH . '/app/Plugins',
                ], BASE_PATH . '/data/modules.json');
                $enabledMap = $manager->enabled();
            } catch (\Throwable) {
                $enabledMap = [];
            }
        }

        return isset($enabledMap[$module]);
    }
}

if (!function_exists('flatcms_parse_shortcode_attributes')) {
    /**
     * @return array<string,string>
     */
    function flatcms_parse_shortcode_attributes(string $raw): array
    {
        $attributes = [];
        $raw = trim($raw);
        if ($raw === '') {
            return $attributes;
        }

        if (!preg_match_all('/([a-zA-Z_][a-zA-Z0-9_-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s\]]+))/', $raw, $matches, PREG_SET_ORDER)) {
            return $attributes;
        }

        foreach ($matches as $match) {
            $name = strtolower(trim((string) ($match[1] ?? '')));
            if ($name === '') {
                continue;
            }

            $value = '';
            if (array_key_exists(2, $match) && $match[2] !== '') {
                $value = (string) $match[2];
            } elseif (array_key_exists(3, $match) && $match[3] !== '') {
                $value = (string) $match[3];
            } elseif (array_key_exists(4, $match) && $match[4] !== '') {
                $value = (string) $match[4];
            }

            $attributes[$name] = $value;
        }

        return $attributes;
    }
}

if (!function_exists('flatcms_shortcode_handlers')) {
    /**
     * @return array<string, callable(array<string,string>, array<string,mixed>):string>
     */
    function flatcms_shortcode_handlers(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $handlers = [];
        $results = \App\Core\Hook::run('shortcodes.register', []);

        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }

            foreach ($result as $tag => $handler) {
                if (!is_string($tag)) {
                    continue;
                }

                $normalizedTag = strtolower(trim($tag));
                if ($normalizedTag === '' || !preg_match('/^[a-z][a-z0-9_-]*$/', $normalizedTag)) {
                    continue;
                }

                if (!is_callable($handler)) {
                    continue;
                }

                $handlers[$normalizedTag] = $handler;
            }
        }

        $cache = $handlers;
        return $cache;
    }
}

if (!function_exists('flatcms_render_shortcodes')) {
    /**
     * @param array<string,mixed> $context
     */
    function flatcms_render_shortcodes(string $content, array $context = []): string
    {
        if ($content === '' || !str_contains($content, '[')) {
            return $content;
        }

        $depth = (int) ($context['_shortcode_depth'] ?? 0);
        if ($depth >= 10) {
            return $content;
        }

        $rendered = $content;
        $handlers = flatcms_shortcode_handlers();

        foreach ($handlers as $tag => $handler) {
            if (!is_string($tag) || $tag === '' || !is_callable($handler)) {
                continue;
            }

            $pattern = '/\[' . preg_quote($tag, '/') . '(?:\s+([^\]]*))?\]/i';
            $rendered = preg_replace_callback(
                $pattern,
                static function (array $matches) use ($context, $handler): string {
                    $rawAttributes = (string) ($matches[1] ?? '');
                    $attributes = flatcms_parse_shortcode_attributes($rawAttributes);

                    try {
                        return (string) $handler($attributes, $context);
                    } catch (\Throwable) {
                        return '';
                    }
                },
                $rendered
            );

            if (!is_string($rendered)) {
                return $content;
            }
        }

        return $rendered;
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        return app()->router()->url($name, $params);
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return app()->session()->get('user');
    }
}

if (!function_exists('avatar_url')) {
    function avatar_url(?array $user = null): ?string
    {
        $user = $user ?? auth();
        if (!$user || empty($user['id']) || empty($user['avatar'])) {
            return null;
        }

        $auth = auth();
        if (!$auth || (string) ($auth['id'] ?? '') !== (string) $user['id']) {
            return null;
        }

        return url('/avatar/' . $user['id']);
    }
}

if (!function_exists('normalize_host')) {
    function normalize_host(string $host): string
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return '';
        }

        if (str_starts_with($host, '[')) {
            $closingBracket = strpos($host, ']');
            if ($closingBracket !== false) {
                return substr($host, 1, $closingBracket - 1);
            }
        }

        if (substr_count($host, ':') > 1) {
            return $host;
        }

        return preg_replace('/:\\d+$/', '', $host) ?? $host;
    }
}

if (!function_exists('is_local_host')) {
    function is_private_or_reserved_ip(?string $ip): bool
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}

if (!function_exists('is_local_host')) {
    function is_local_host(?string $host = null): bool
    {
        $providedHost = $host !== null;
        $host = $host ?? ($_SERVER['HTTP_HOST'] ?? '');
        $host = normalize_host($host);
        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }
        if (str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return true;
        }

        if ($providedHost) {
            return false;
        }

        return is_private_or_reserved_ip($_SERVER['REMOTE_ADDR'] ?? '');
    }
}

if (!function_exists('menu_front_is_external_url')) {
    function menu_front_is_external_url(string $url): bool
    {
        return (bool) preg_match('#^https?://#i', $url)
            || (bool) preg_match('#^(mailto:|tel:|//)#i', $url);
    }
}

if (!function_exists('menu_front_resolve_url')) {
    function menu_front_resolve_url(string $itemUrl, string $locale): string
    {
        if (menu_front_is_external_url($itemUrl)) {
            return $itemUrl;
        }

        if (str_starts_with($itemUrl, '/admin')) {
            return url($itemUrl);
        }

        if (preg_match('#^/(assets|themes|modules|uploads|release|favicon\.ico|public/(assets|themes|modules|uploads|release|favicon\.ico))#', $itemUrl) === 1) {
            return url($itemUrl);
        }

        return url('/' . $locale . $itemUrl);
    }
}

if (!function_exists('menu_front_resolve_target')) {
    function menu_front_resolve_target(string $target, string $url): string
    {
        $normalized = strtolower(trim($target));
        if (in_array($normalized, ['_blank', 'blank', 'new'], true)) {
            return '_blank';
        }

        return menu_front_is_external_url($url) ? '_blank' : '_self';
    }
}

if (!function_exists('menu_front_resolve_reference_item')) {
    function menu_front_resolve_reference_item(array $item, string $locale): array
    {
        $refType = trim((string) ($item['refType'] ?? ''));
        $ref = trim((string) ($item['ref'] ?? ''));
        if ($refType === '' || $ref === '') {
            return $item;
        }

        if ($refType === 'post' && class_exists(\App\Modules\Posts\Services\PostTranslationService::class)) {
            static $postTranslations = null;
            if (!$postTranslations instanceof \App\Modules\Posts\Services\PostTranslationService) {
                $postTranslations = new \App\Modules\Posts\Services\PostTranslationService();
            }

            $referencePost = $postTranslations->find($ref);
            if (!is_array($referencePost)) {
                $referencePost = $postTranslations->findBySlug($ref, false);
            }
            if (!is_array($referencePost)) {
                return $item;
            }

            $translationGroup = trim((string) ($referencePost['translation_group'] ?? ''));
            $localizedPost = $translationGroup !== ''
                ? $postTranslations->findByTranslationGroupAndLocale($translationGroup, $locale, true)
                : null;
            if (!is_array($localizedPost) && $translationGroup !== '') {
                $localizedPost = $postTranslations->resolveSourcePost($translationGroup);
            }
            if (!is_array($localizedPost)) {
                $localizedPost = $referencePost;
            }

            $labelMode = strtolower(trim((string) ($item['labelMode'] ?? '')));
            $currentLabel = trim((string) ($item['label'] ?? ''));
            $autoLabel = trim((string) ($item['autoLabel'] ?? ''));
            $referenceLabel = trim((string) ($referencePost['title'] ?? ''));
            $localizedLabel = trim((string) ($localizedPost['title'] ?? ''));
            $shouldUseAutoLabel = $labelMode !== 'custom'
                || ($currentLabel !== '' && (
                    ($referenceLabel !== '' && $currentLabel === $referenceLabel)
                    || ($localizedLabel !== '' && $currentLabel === $localizedLabel)
                    || ($autoLabel !== '' && $currentLabel === $autoLabel)
                ));
            if ($shouldUseAutoLabel && $localizedLabel !== '') {
                $item['label'] = $localizedLabel;
            }

            $localizedSlug = trim((string) ($localizedPost['slug'] ?? ''));
            if ($localizedSlug !== '') {
                $item['url'] = '/blog/' . $localizedSlug;
            }

            return $item;
        }

        if ($refType === 'page' && class_exists(\App\Modules\Pages\Services\PageTranslationService::class)) {
            static $pageTranslations = null;
            static $siteRouting = null;
            if (!$pageTranslations instanceof \App\Modules\Pages\Services\PageTranslationService) {
                $pageTranslations = new \App\Modules\Pages\Services\PageTranslationService();
            }
            if (!$siteRouting instanceof \App\Modules\Settings\Services\SiteRoutingService
                && class_exists(\App\Modules\Settings\Services\SiteRoutingService::class)
            ) {
                $siteRouting = new \App\Modules\Settings\Services\SiteRoutingService($pageTranslations);
            }

            $referencePage = $pageTranslations->find($ref);
            if (!is_array($referencePage)) {
                $referencePage = $pageTranslations->findBySlug($ref, false);
            }
            if (!is_array($referencePage)) {
                return $item;
            }

            $translationGroup = trim((string) ($referencePage['translation_group'] ?? ''));
            if ($translationGroup !== '') {
                $sourcePage = $pageTranslations->resolveSourcePage($translationGroup);
                if (is_array($sourcePage)) {
                    $referencePage = $sourcePage;
                }
            }
            $localizedPage = $translationGroup !== ''
                ? $pageTranslations->findByTranslationGroupAndLocale($translationGroup, $locale, true)
                : null;
            if (!is_array($localizedPage) && $translationGroup !== '') {
                $localizedPage = $pageTranslations->resolveSourcePage($translationGroup);
            }
            if (!is_array($localizedPage)) {
                $localizedPage = $referencePage;
            }

            $labelMode = strtolower(trim((string) ($item['labelMode'] ?? '')));
            $currentLabel = trim((string) ($item['label'] ?? ''));
            $autoLabel = trim((string) ($item['autoLabel'] ?? ''));
            $referenceLabel = trim((string) ($referencePage['title'] ?? ''));
            $localizedLabel = trim((string) ($localizedPage['title'] ?? ''));
            $shouldUseAutoLabel = $labelMode !== 'custom'
                || ($currentLabel !== '' && (
                    ($referenceLabel !== '' && $currentLabel === $referenceLabel)
                    || ($localizedLabel !== '' && $currentLabel === $localizedLabel)
                    || ($autoLabel !== '' && $currentLabel === $autoLabel)
                ));
            if ($shouldUseAutoLabel && $localizedLabel !== '') {
                $item['label'] = $localizedLabel;
            }

            $localizedSlug = trim((string) ($localizedPage['slug'] ?? ''));
            if (
                $localizedSlug === ''
                || $localizedSlug === 'home'
                || ($siteRouting instanceof \App\Modules\Settings\Services\SiteRoutingService
                    && $siteRouting->isHomepagePage($localizedPage))
            ) {
                $item['url'] = '/';
            } else {
                $item['url'] = '/page/' . $localizedSlug;
            }

            return $item;
        }

        if ($refType === 'category' && class_exists(\App\Modules\Categories\Services\CategoryTranslationService::class)) {
            static $categoryTranslations = null;
            if (!$categoryTranslations instanceof \App\Modules\Categories\Services\CategoryTranslationService) {
                $categoryTranslations = new \App\Modules\Categories\Services\CategoryTranslationService();
            }

            $referenceCategory = $categoryTranslations->find($ref);
            if (!is_array($referenceCategory)) {
                $referenceCategory = $categoryTranslations->findBySlug($ref, false);
            }
            if (!is_array($referenceCategory)) {
                return $item;
            }

            $translationGroup = trim((string) ($referenceCategory['translation_group'] ?? ''));
            $localizedCategory = $translationGroup !== ''
                ? $categoryTranslations->findByTranslationGroupAndLocale($translationGroup, $locale, true)
                : null;
            if (!is_array($localizedCategory) && $translationGroup !== '') {
                $localizedCategory = $categoryTranslations->resolveSourceCategory($translationGroup);
            }
            if (!is_array($localizedCategory)) {
                $localizedCategory = $referenceCategory;
            }

            $localizedSlug = trim((string) ($localizedCategory['slug'] ?? ''));
            if ($localizedSlug !== '') {
                $item['url'] = '/blog/categorie/' . $localizedSlug;
            }

            $labelMode = strtolower(trim((string) ($item['labelMode'] ?? '')));
            $currentLabel = trim((string) ($item['label'] ?? ''));
            $autoLabel = trim((string) ($item['autoLabel'] ?? ''));
            $referenceName = trim((string) ($referenceCategory['name'] ?? ''));
            $localizedName = trim((string) ($localizedCategory['name'] ?? ''));
            $normalizedAutoLabel = preg_replace('/^[^·]+·\s*/u', '', $autoLabel) ?? $autoLabel;
            $normalizedAutoLabel = preg_replace('/^[^-]+-\s*/u', '', (string) $normalizedAutoLabel) ?? $normalizedAutoLabel;
            $normalizedCurrentLabel = preg_replace('/^[^·]+·\s*/u', '', $currentLabel) ?? $currentLabel;
            $normalizedCurrentLabel = preg_replace('/^[^-]+-\s*/u', '', (string) $normalizedCurrentLabel) ?? $normalizedCurrentLabel;
            $shouldUseAutoLabel = $labelMode !== 'custom'
                || ($currentLabel !== '' && (
                    ($referenceName !== '' && $currentLabel === $referenceName)
                    || ($localizedName !== '' && $currentLabel === $localizedName)
                    || ($normalizedAutoLabel !== '' && $currentLabel === trim($normalizedAutoLabel))
                    || ($referenceName !== '' && trim($normalizedCurrentLabel) === $referenceName)
                    || ($localizedName !== '' && trim($normalizedCurrentLabel) === $localizedName)
                ));

            if ($shouldUseAutoLabel && $localizedName !== '') {
                $item['label'] = $localizedName;
            }

            return $item;
        }

        return $item;
    }
}

if (!function_exists('menu_front_apply_item_translation')) {
    function menu_front_apply_item_translation(array $item, string $locale): array
    {
        $translations = $item['translations'] ?? [];
        if (!is_array($translations) || $translations === []) {
            return $item;
        }

        $translatedLabel = trim((string) ($translations[$locale] ?? ''));
        if ($translatedLabel === '') {
            return $item;
        }

        $item['label'] = $translatedLabel;

        return $item;
    }
}

if (!function_exists('menu_front_render_icon_html')) {
    function menu_front_render_icon_html(array $item): string
    {
        $iconType = strtolower(trim((string) ($item['iconType'] ?? '')));
        $iconMedia = trim((string) ($item['iconMedia'] ?? ''));

        if ($iconType === 'media' && $iconMedia !== '') {
            $mediaUrl = site_media_url($iconMedia);
            if ($mediaUrl !== '') {
                return '<span class="nav-icon-media"><img src="' . e($mediaUrl) . '" alt=""></span>';
            }
        }

        $icon = trim((string) ($item['icon'] ?? ''));
        if ($icon === '') {
            return '';
        }

        return '<i class="' . e($icon) . ' nav-icon"></i>';
    }
}

if (!function_exists('menu_front_normalize_path')) {
    function menu_front_normalize_path(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            return '/';
        }

        $path = '/' . ltrim(rawurldecode($path), '/');
        $path = preg_replace('#/+#', '/', $path) ?: '/';

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}

if (!function_exists('menu_front_canonical_route_path')) {
    /**
     * Return the functional route path independently of its optional locale
     * prefix. A neutral internal link and its localized counterpart must mark
     * the same menu item as current.
     */
    function menu_front_canonical_route_path(string $url): string
    {
        $path = menu_front_normalize_path($url);
        if ($path === '/') {
            return '/';
        }

        $segments = explode('/', ltrim($path, '/'));
        $firstSegment = (string) ($segments[0] ?? '');
        static $supportedLocales = null;
        if (!is_array($supportedLocales)) {
            $supportedLocales = class_exists(\App\Core\I18n::class)
                ? \App\Core\I18n::getSupportedLocales()
                : [];
        }

        if (in_array($firstSegment, $supportedLocales, true)) {
            array_shift($segments);
            $path = '/' . implode('/', $segments);
        }

        return menu_front_normalize_path($path);
    }
}

if (!function_exists('menu_front_is_current_url')) {
    function menu_front_is_current_url(string $url): bool
    {
        if ($url === '' || preg_match('#^(?:https?:)?//#i', $url) === 1) {
            return false;
        }

        $current = menu_front_canonical_route_path((string) ($_SERVER['REQUEST_URI'] ?? '/'));
        $target = menu_front_canonical_route_path($url);

        return $current === $target;
    }
}

if (!function_exists('menu_front_items_contain_current_url')) {
    function menu_front_items_contain_current_url(array $items, string $locale): bool
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $item = menu_front_resolve_reference_item($item, $locale);
            $item = menu_front_apply_item_translation($item, $locale);
            $href = menu_front_resolve_url((string) ($item['url'] ?? ''), $locale);
            if (menu_front_is_current_url($href)) {
                return true;
            }

            $children = is_array($item['children'] ?? null) ? $item['children'] : [];
            if ($children !== [] && menu_front_items_contain_current_url($children, $locale)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('menu_front_render_menu')) {
    function menu_front_render_menu(array $items, string $locale, array $context = []): string
    {
        $payload = [
            'items' => $items,
            'locale' => $locale,
            'context' => $context,
        ];

        foreach (hook_run('menus.render.resolve', $payload) as $result) {
            if (!is_array($result) || empty($result['handled'])) {
                continue;
            }

            return (string) ($result['html'] ?? '');
        }

        hook_run('menus.before_render', $payload);

        if ($items === []) {
            hook_run('menus.after_render', $payload + ['html' => '']);
            return '';
        }

        $toggleLabel = (string) ($context['toggleLabel'] ?? __('toggle_submenu', 'Core'));
        $isSub = (bool) ($context['isSub'] ?? false);
        $depth = max(0, (int) ($context['depth'] ?? 0));
        $indentUnit = '    ';
        $indent = static fn(int $level): string => str_repeat($indentUnit, $level);
        $indentHtml = static function (string $html, int $level) use ($indent): string {
            $lines = preg_split('/\r\n|\r|\n/', $html);
            if (!is_array($lines)) {
                return $html;
            }

            $prefix = $indent($level);
            foreach ($lines as &$line) {
                if ($line !== '') {
                    $line = $prefix . $line;
                }
            }
            unset($line);

            return implode(PHP_EOL, $lines);
        };

        $listClass = $isSub ? 'submenu' : 'main-nav-list';
        $lines = [];
        $lines[] = $indent($depth) . '<ul class="' . $listClass . '">';

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $item = menu_front_resolve_reference_item($item, $locale);
            $item = menu_front_apply_item_translation($item, $locale);

            $itemUrl = (string) ($item['url'] ?? '');
            $label = trim((string) ($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $icon = trim((string) ($item['icon'] ?? ''));
            $target = (string) ($item['target'] ?? '');
            $children = $item['children'] ?? [];
            $hasChildren = is_array($children) && $children !== [];
            $hoverOnly = $hasChildren && !empty($item['hoverOnly']);
            $href = menu_front_resolve_url($itemUrl, $locale);
            $resolvedTarget = menu_front_resolve_target($target, $itemUrl);

            $isCurrent = menu_front_is_current_url($href);
            $hasActiveChild = $hasChildren && menu_front_items_contain_current_url($children, $locale);
            $itemClasses = ['nav-item'];
            if ($hasChildren) {
                $itemClasses[] = 'has-children';
                if ($hoverOnly) {
                    $itemClasses[] = 'submenu-hover-only';
                }
            }
            if ($isCurrent || $hasActiveChild) {
                $itemClasses[] = 'is-active';
            }
            $itemClass = implode(' ', $itemClasses);
            $targetAttr = $resolvedTarget === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';
            $currentAttr = $isCurrent ? ' aria-current="page"' : '';
            $iconHtml = menu_front_render_icon_html($item);

            $lines[] = $indent($depth + 1) . '<li class="' . $itemClass . '">';
            $lines[] = $indent($depth + 2) . '<a class="nav-link" href="' . e($href) . '"' . $targetAttr . $currentAttr . '>' . $iconHtml . e($label) . '</a>';

            if ($hasChildren) {
                if (!$hoverOnly) {
                    $lines[] = $indent($depth + 2) . '<button type="button" class="submenu-toggle" aria-expanded="false" aria-label="' . e($toggleLabel . ' - ' . $label) . '">';
                    $lines[] = $indent($depth + 3) . '<i class="fas fa-chevron-down"></i>';
                    $lines[] = $indent($depth + 2) . '</button>';
                }
                $lines[] = menu_front_render_menu($children, $locale, [
                    'toggleLabel' => $toggleLabel,
                    'isSub' => true,
                    'depth' => $depth + 2,
                ]);
            }

            $lines[] = $indent($depth + 1) . '</li>';
        }

        $lines[] = $indent($depth) . '</ul>';
        $html = implode(PHP_EOL, $lines);

        foreach (hook_run('menus.after_render', $payload + ['html' => $html]) as $result) {
            if (is_array($result) && array_key_exists('html', $result) && is_string($result['html'])) {
                return $result['html'];
            }
        }

        return $html;
    }
}

if (!function_exists('footer_default_config')) {
    function footer_default_config(?array $settings = null): array
    {
        $settings = $settings ?? \App\Core\FlatFile::settings();
        $siteName = trim((string) ($settings['site_name'] ?? __('app_name', 'Core')));
        if ($siteName === '') {
            $siteName = __('app_name', 'Core');
        }

        return [
            'enabled' => true,
            'brand_text' => $siteName,
            'copyright_text' => '© {year} {site_name}. ' . __('all_rights_reserved', 'Core'),
            'powered_by' => [
                'enabled' => true,
                'label' => __('app_name', 'Core'),
                'url' => 'https://flat-cms.fr',
            ],
        ];
    }
}

if (!function_exists('flatcms_content_is_theme_structured')) {
    /**
     * Detect theme-authored structural markers only: structural tags and
     * theme-specific classes. SunEditor round-trip artifacts (se-component,
     * __se__) are a damage signal, not a structure signal.
     */
    function flatcms_content_is_theme_structured(string $html): bool
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return false;
        }

        if (preg_match('#<\s*(section|article|figure|aside|main|nav|picture|template|header|footer|summary|details)\b#i', $html) === 1) {
            return true;
        }

        if (preg_match('#class\s*=\s*(["\'])[^"\']*?\b(creations-|project-|architecture-|fco-|code-window|reveal|manifesto|eyebrow|wrap)\b#i', $html) === 1) {
            return true;
        }

        return false;
    }
}

if (!function_exists('flatcms_count_structural_tags')) {
    /**
     * Count opening structural tags that SunEditor's bundled build would drop
     * on a DOM round-trip. A drop to zero is the signature of a flattened
     * visual-editor save.
     */
    function flatcms_count_structural_tags(string $html): int
    {
        $count = preg_match_all(
            '#<\s*(section|article|figure|aside|main|nav|picture|template|header|footer|summary|details)\b(?!/)#i',
            $html
        );

        return $count === false ? 0 : $count;
    }
}

if (!function_exists('flatcms_strip_suneditor_artifacts')) {
    /**
     * Remove SunEditor-internal class tokens (se-component, __se__*) that get
     * persisted when a WYSIWYG round-trip re-serializes structured content.
     */
    function flatcms_strip_suneditor_artifacts(string $html): string
    {
        return preg_replace_callback(
            '/\sclass\s*=\s*("[^"]*"|\'[^\']*\')/i',
            static function (array $matches): string {
                $quote = (string) ($matches[1][0] ?? '"');
                $raw = trim(substr($matches[1], 1, -1));
                if ($raw === '') {
                    return $matches[0];
                }

                $tokens = preg_split('/\s+/', $raw) ?: [];
                $kept = array_values(array_filter($tokens, static function (string $token): bool {
                    return $token !== 'se-component'
                        && $token !== 'se-image-container'
                        && $token !== 'se-video-container'
                        && strpos($token, '__se__') !== 0;
                }));

                if ($kept === $tokens) {
                    return $matches[0];
                }

                return ' class=' . $quote . implode(' ', $kept) . $quote;
            },
            $html
        ) ?? $html;
    }
}

if (!function_exists('flatcms_sanitize_editor_html')) {
    /**
     * Remove active HTML without reserializing safe editor markup.
     */
    function flatcms_sanitize_editor_html(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $clean = str_replace("\0", '', $html);
        $clean = preg_replace(
            '#<\s*(script|style|object)\b[^>]*>.*?<\s*/\s*\1\s*>#is',
            '',
            $clean
        ) ?? '';
        $clean = preg_replace(
            '#<\s*/?\s*(script|style|object|embed|base|meta|link)\b[^>]*>#is',
            '',
            $clean
        ) ?? '';

        $attributeValue = '(?:"[^"]*"|\'[^\']*\'|[^\s>]+)';
        $clean = preg_replace('/\s+on[a-z0-9:_-]+\s*=\s*' . $attributeValue . '/i', '', $clean) ?? '';
        $clean = preg_replace('/\s+srcdoc\s*=\s*' . $attributeValue . '/i', '', $clean) ?? '';

        $clean = preg_replace_callback(
            '/\s+(href|src|action|formaction|poster|xlink:href)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
            static function (array $matches): string {
                $value = (string) (($matches[3] ?? '') !== '' ? $matches[3] : (($matches[4] ?? '') !== '' ? $matches[4] : ($matches[5] ?? '')));
                $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $normalized = strtolower((string) preg_replace('/[\x00-\x20\x7f]+/', '', $decoded));
                $dangerous = str_starts_with($normalized, 'javascript:')
                    || str_starts_with($normalized, 'vbscript:')
                    || (str_starts_with($normalized, 'data:')
                        && preg_match('#^data:image/(?:png|gif|jpe?g|webp|avif);base64,#i', $normalized) !== 1);

                return $dangerous ? '' : (string) $matches[0];
            },
            $clean
        ) ?? '';

        $clean = preg_replace_callback(
            '/\s+style\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
            static function (array $matches): string {
                $value = (string) (($matches[2] ?? '') !== '' ? $matches[2] : (($matches[3] ?? '') !== '' ? $matches[3] : ($matches[4] ?? '')));
                $decoded = strtolower(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $dangerous = preg_match('/(?:expression\s*\(|javascript\s*:|vbscript\s*:|-moz-binding\s*:|behavior\s*:|data\s*:\s*text\/html)/i', $decoded) === 1;

                return $dangerous ? '' : (string) $matches[0];
            },
            $clean
        ) ?? '';

        return flatcms_strip_suneditor_artifacts($clean);
    }
}

if (!function_exists('flatcms_merge_submitted_text_into_structure')) {
    /**
     * Keep the theme-authored structure and apply the editor's text edits on
     * top of it. Used when a visual-editor round-trip flattened the structural
     * tags (section/article/...): instead of reverting the save silently, the
     * meaningful texts submitted in document order are injected back into the
     * original structure.
     */
    function flatcms_merge_submitted_text_into_structure(string $existingHtml, string $submittedHtml): string
    {
        $existingHtml = (string) $existingHtml;
        $submittedHtml = (string) $submittedHtml;

        $tokenize = static function (string $html): array {
            $tokens = preg_split(
                '/(<!--.*?-->|<![^>]*>|<[^>]+>)/s',
                $html,
                -1,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
            );

            return is_array($tokens) ? $tokens : [];
        };
        $isMarkup = static fn (string $token): bool => str_starts_with($token, '<');
        $isMeaningfulText = static fn (string $token): bool => preg_match('/^\s*$/u', $token) !== 1;
        $normalizeText = static fn (string $token): string => preg_replace('/\s+/u', ' ', trim($token)) ?? '';

        $existingTokens = $tokenize($existingHtml);
        $submittedTexts = [];
        foreach ($tokenize($submittedHtml) as $token) {
            if (!$isMarkup($token) && $isMeaningfulText($token)) {
                $submittedTexts[] = $token;
            }
        }
        if ($submittedTexts === []) {
            return $existingHtml;
        }

        $existingSlots = [];
        foreach ($existingTokens as $index => $token) {
            if (!$isMarkup($token) && $isMeaningfulText($token)) {
                $existingSlots[] = $index;
            }
        }
        if ($existingSlots === []) {
            return $existingHtml;
        }

        $submittedCandidates = [];
        foreach ($submittedTexts as $position => $token) {
            $submittedCandidates[] = [
                'position' => $position,
                'raw' => $token,
                'normalized' => $normalizeText($token),
            ];
        }
        $used = array_fill(0, count($submittedCandidates), false);

        $assignments = array_fill(0, count($existingSlots), null);

        foreach ($existingSlots as $slot => $index) {
            $existingNormalized = $normalizeText($existingTokens[$index]);
            if ($existingNormalized === '') {
                continue;
            }
            foreach ($submittedCandidates as $position => $candidate) {
                if ($used[$position] || $candidate['normalized'] !== $existingNormalized) {
                    continue;
                }
                $assignments[$slot] = $position;
                $used[$position] = true;
                break;
            }
        }

        foreach ($existingSlots as $slot => $index) {
            if ($assignments[$slot] !== null) {
                continue;
            }
            $existingNormalized = $normalizeText($existingTokens[$index]);
            if ($existingNormalized === '') {
                continue;
            }
            $bestPosition = null;
            $bestDistance = PHP_INT_MAX;
            foreach ($submittedCandidates as $position => $candidate) {
                if ($used[$position] || $candidate['normalized'] === '') {
                    continue;
                }
                $distance = function_exists('levenshtein')
                    ? levenshtein($existingNormalized, $candidate['normalized'])
                    : PHP_INT_MAX;
                if ($distance !== -1 && $distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestPosition = $position;
                }
            }
            if ($bestPosition !== null) {
                $assignments[$slot] = $bestPosition;
                $used[$bestPosition] = true;
            }
        }

        foreach ($existingSlots as $slot => $index) {
            $position = $assignments[$slot];
            if ($position !== null) {
                $existingTokens[$index] = $submittedTexts[$position];
            }
        }

        return str_replace(["\r\n", "\r"], "\n", implode('', $existingTokens));
    }
}

if (!function_exists('flatcms_unwrap_editor_inline_wrappers')) {
    /**
     * Remove paragraph wrappers that CKEditor 5's model layer synthesizes
     * around generic inline elements (span/a/strong/...) placed as direct
     * children of block containers (div/section/article/...). The editor's
     * schema has no generic inline element, so such an inline child is moved
     * into a <p> on load. This restores the theme-authored structure at save
     * time so child combinators like "> .eyebrow" keep working.
     *
     * Only a <p> whose direct content is exclusively inline elements (no
     * direct text, no block children) is unwrapped, so real paragraphs are
     * never touched. Also strips CKEditor's list bookkeeping attribute
     * (data-list-item-id) added to <li> elements.
     */
    function flatcms_unwrap_editor_inline_wrappers(string $html): string
    {
        $tokens = preg_split(
            '/(<!--.*?-->|<![^>]*>|<[^>]+>)/s',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        if (!is_array($tokens) || $tokens === []) {
            return $html;
        }

        $inline = [
            'a', 'abbr', 'audio', 'b', 'bdi', 'bdo', 'button', 'canvas', 'cite', 'code', 'data',
            'del', 'dfn', 'em', 'i', 'ins', 'kbd', 'label', 'mark', 'meter', 'output', 'picture',
            'progress', 'q', 'ruby', 's', 'samp', 'select', 'small', 'span', 'strong', 'sub',
            'sup', 'textarea', 'time', 'u', 'var', 'video',
        ];
        $void = ['area', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'svg', 'track', 'wbr'];
        $containers = [
            'address', 'article', 'aside', 'blockquote', 'dd', 'details', 'div', 'dl', 'dt',
            'figcaption', 'figure', 'footer', 'form', 'header', 'li', 'main', 'nav', 'section',
            'summary', 'td', 'th', 'tr',
        ];

        $isInline = static fn (string $name): bool => in_array($name, $inline, true) || in_array($name, $void, true);
        $isContainer = static fn (string $name): bool => in_array($name, $containers, true);

        $rangesByOpen = [];
        $syntheticByOpen = [];
        $stack = [];
        $parents = [];

        foreach ($tokens as $index => $token) {
            if (preg_match('#^<([a-zA-Z0-9]+)[\s/>]#', $token, $matches) === 1) {
                $name = strtolower($matches[1]);
                $parents[$index] = $stack === [] ? null : $stack[count($stack) - 1]['name'];
                $stack[] = ['name' => $name, 'index' => $index];
                continue;
            }

            if (preg_match('#^</\s*([a-zA-Z0-9]+)\s*>#', $token, $matches) === 1) {
                $name = strtolower($matches[1]);
                for ($i = count($stack) - 1; $i >= 0; $i--) {
                    if ($stack[$i]['name'] !== $name) {
                        continue;
                    }

                    $openIndex = $stack[$i]['index'];
                    array_splice($stack, $i);
                    if ($name !== 'p') {
                        break;
                    }

                    $rangesByOpen[$openIndex] = $index;
                    $parent = $parents[$openIndex] ?? null;
                    if ($parent === null || !$isContainer($parent)) {
                        break;
                    }

                    $hasInline = false;
                    $hasBlock = false;
                    $directText = false;
                    $depth = 0;

                    for ($j = $openIndex + 1; $j < $index; $j++) {
                        $inner = $tokens[$j];
                        if (str_starts_with($inner, '<')) {
                            if (preg_match('#^<([a-zA-Z0-9]+)[\s/>]#', $inner, $innerMatches) === 1) {
                                $innerName = strtolower($innerMatches[1]);
                                if ($depth === 0) {
                                    if ($isInline($innerName)) {
                                        $hasInline = true;
                                    } else {
                                        $hasBlock = true;
                                    }
                                } elseif (!$isInline($innerName)) {
                                    $hasBlock = true;
                                }
                                if ($isInline($innerName) && !in_array($innerName, $void, true)) {
                                    $depth++;
                                }
                            } elseif (preg_match('#^</\s*([a-zA-Z0-9]+)\s*>#', $inner, $innerMatches) === 1) {
                                $innerName = strtolower($innerMatches[1]);
                                if ($isInline($innerName) && $depth > 0) {
                                    $depth--;
                                }
                            }
                        } elseif ($depth === 0 && preg_match('/\S/u', $inner) === 1) {
                            $directText = true;
                        }
                    }

                    if ($hasInline && !$hasBlock && !$directText) {
                        $syntheticByOpen[$openIndex] = true;
                    }

                    break;
                }
            }
        }

        $stripListIds = static fn (string $token): string => (string) preg_replace(
            '/\s+data-list-item-id\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            $token
        );

        $output = [];
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if (isset($syntheticByOpen[$i])) {
                $close = $rangesByOpen[$i];
                for ($j = $i + 1; $j < $close; $j++) {
                    $output[] = $stripListIds($tokens[$j]);
                }
                $i = $close;
                continue;
            }
            $output[] = $stripListIds($tokens[$i]);
        }

        return implode('', $output);
    }
}

if (!function_exists('flatcms_editor_html_tokens')) {
    /**
     * Tokenize HTML without reserializing it. Whitespace and indentation remain
     * attached to their original text tokens.
     *
     * @return array<int, string>
     */
    function flatcms_editor_html_tokens(string $html): array
    {
        $tokens = preg_split(
            '/(<!--.*?-->|<![^>]*>|<[^>]+>)/s',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        return is_array($tokens) ? $tokens : [];
    }
}

if (!function_exists('flatcms_editor_text_nodes')) {
    /**
     * @return array<int, array{token_index:int, raw:string, normalized:string}>
     */
    function flatcms_editor_text_nodes(string $html): array
    {
        $nodes = [];
        foreach (flatcms_editor_html_tokens($html) as $index => $token) {
            if (str_starts_with($token, '<') || preg_match('/^\s*$/u', $token) === 1) {
                continue;
            }

            $decoded = html_entity_decode($token, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $normalized = preg_replace('/\s+/u', ' ', trim($decoded)) ?? '';
            $nodes[] = [
                'token_index' => $index,
                'raw' => $token,
                'normalized' => $normalized,
            ];
        }

        return $nodes;
    }
}

if (!function_exists('flatcms_editor_tag_skeleton')) {
    /**
     * Compare editor structures while ignoring attribute serialization changes.
     *
     * @return array<int, string>
     */
    function flatcms_editor_tag_skeleton(string $html): array
    {
        $skeleton = [];
        foreach (flatcms_editor_html_tokens($html) as $token) {
            if (!str_starts_with($token, '<')) {
                continue;
            }
            if (str_starts_with($token, '<!--')) {
                $skeleton[] = '#comment';
                continue;
            }
            if (preg_match('/^<\s*(\/?)\s*([a-zA-Z0-9:_-]+)/', $token, $matches) === 1) {
                $skeleton[] = ($matches[1] === '/' ? '/' : '') . strtolower($matches[2]);
            }
        }

        return $skeleton;
    }
}

if (!function_exists('flatcms_editor_lcs_pairs')) {
    /**
     * Ordered exact matches used to align CKEditor's normalized text nodes with
     * the original human-authored source nodes.
     *
     * @param array<int, string> $left
     * @param array<int, string> $right
     * @return array<int, array{0:int,1:int}>
     */
    function flatcms_editor_lcs_pairs(array $left, array $right): array
    {
        $leftCount = count($left);
        $rightCount = count($right);
        $matrix = array_fill(0, $leftCount + 1, array_fill(0, $rightCount + 1, 0));

        for ($i = $leftCount - 1; $i >= 0; $i--) {
            for ($j = $rightCount - 1; $j >= 0; $j--) {
                $matrix[$i][$j] = $left[$i] === $right[$j]
                    ? 1 + $matrix[$i + 1][$j + 1]
                    : max($matrix[$i + 1][$j], $matrix[$i][$j + 1]);
            }
        }

        $pairs = [];
        $i = 0;
        $j = 0;
        while ($i < $leftCount && $j < $rightCount) {
            if ($left[$i] === $right[$j]) {
                $pairs[] = [$i, $j];
                $i++;
                $j++;
                continue;
            }
            if ($matrix[$i + 1][$j] >= $matrix[$i][$j + 1]) {
                $i++;
            } else {
                $j++;
            }
        }

        return $pairs;
    }
}

if (!function_exists('flatcms_apply_text_value_delta')) {
    /**
     * Apply a CKEditor text-node change to the original raw text while keeping
     * unchanged entity spellings such as &egrave; and surrounding whitespace.
     */
    function flatcms_apply_text_value_delta(string $existingRaw, string $baselineRaw, string $submittedRaw): string
    {
        $decode = static fn (string $value): string => html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $splitWhitespace = static function (string $value): array {
            if (preg_match('/^(\s*)(.*?)(\s*)$/su', $value, $matches) !== 1) {
                return ['', $value, ''];
            }
            return [$matches[1], $matches[2], $matches[3]];
        };

        [$existingLeading, $existingCore, $existingTrailing] = $splitWhitespace($existingRaw);
        [, $baselineCore] = $splitWhitespace($baselineRaw);
        [, $submittedCore] = $splitWhitespace($submittedRaw);

        $existingDecoded = $decode($existingCore);
        $baselineDecoded = $decode($baselineCore);
        $submittedDecoded = $decode($submittedCore);

        if ($existingDecoded !== $baselineDecoded) {
            return $existingLeading . $submittedCore . $existingTrailing;
        }

        $toChars = static fn (string $value): array => preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $existingChars = $toChars($existingDecoded);
        $baselineChars = $toChars($baselineDecoded);
        $submittedChars = $toChars($submittedDecoded);

        $rawSegments = [];
        if (preg_match_all('/&(?:#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]+);|./isu', $existingCore, $matches) === 1 || !empty($matches[0])) {
            foreach ($matches[0] as $segment) {
                $decodedSegment = $decode($segment);
                $segmentChars = $toChars($decodedSegment);
                if (count($segmentChars) === 1) {
                    $rawSegments[] = $segment;
                    continue;
                }
                foreach ($segmentChars as $position => $char) {
                    $rawSegments[] = $position === 0 ? $segment : '';
                }
            }
        }

        if (count($rawSegments) !== count($existingChars)) {
            return $existingLeading . $submittedCore . $existingTrailing;
        }

        $prefix = 0;
        $prefixLimit = min(count($baselineChars), count($submittedChars));
        while ($prefix < $prefixLimit && $baselineChars[$prefix] === $submittedChars[$prefix]) {
            $prefix++;
        }

        $suffix = 0;
        while (
            $suffix < count($baselineChars) - $prefix
            && $suffix < count($submittedChars) - $prefix
            && $baselineChars[count($baselineChars) - 1 - $suffix] === $submittedChars[count($submittedChars) - 1 - $suffix]
        ) {
            $suffix++;
        }

        $rawPrefix = implode('', array_slice($rawSegments, 0, $prefix));
        $rawSuffix = $suffix > 0 ? implode('', array_slice($rawSegments, -$suffix)) : '';
        $submittedMiddleLength = count($submittedChars) - $prefix - $suffix;
        $submittedMiddle = $submittedMiddleLength > 0
            ? implode('', array_slice($submittedChars, $prefix, $submittedMiddleLength))
            : '';

        return $existingLeading . $rawPrefix . $submittedMiddle . $rawSuffix . $existingTrailing;
    }
}

if (!function_exists('flatcms_editor_tag_attributes')) {
    /**
     * Parse one opening tag without normalizing the original HTML source.
     *
     * @return array<string, string>
     */
    function flatcms_editor_tag_attributes(string $tag): array
    {
        $attributes = [];
        $count = preg_match_all(
            '/\s+([a-zA-Z_:][a-zA-Z0-9:._-]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+)))?/s',
            $tag,
            $matches,
            PREG_SET_ORDER
        );
        if ($count === false || $count === 0) {
            return $attributes;
        }

        foreach ($matches as $match) {
            $name = strtolower((string) ($match[1] ?? ''));
            if ($name === '') {
                continue;
            }
            $value = '';
            if (array_key_exists(2, $match) && $match[2] !== '') {
                $value = (string) $match[2];
            } elseif (array_key_exists(3, $match) && $match[3] !== '') {
                $value = (string) $match[3];
            } elseif (array_key_exists(4, $match)) {
                $value = (string) $match[4];
            }
            $attributes[$name] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $attributes;
    }
}

if (!function_exists('flatcms_editor_style_declarations')) {
    /** @return array<string, string> */
    function flatcms_editor_style_declarations(string $style): array
    {
        $declarations = [];
        foreach (preg_split('/;/', $style) ?: [] as $declaration) {
            $declaration = trim($declaration);
            if ($declaration === '' || !str_contains($declaration, ':')) {
                continue;
            }
            [$name, $value] = array_map('trim', explode(':', $declaration, 2));
            $name = strtolower($name);
            if ($name !== '') {
                $declarations[$name] = $value;
            }
        }

        return $declarations;
    }
}

if (!function_exists('flatcms_editor_valid_image_width')) {
    function flatcms_editor_valid_image_width(string $width): bool
    {
        $width = trim($width);
        if (preg_match('/^(?:100(?:\.0+)?|(?:[1-9]?\d)(?:\.\d+)?)%$/', $width) !== 1) {
            return false;
        }

        $numeric = (float) rtrim($width, '%');
        return $numeric > 0.0 && $numeric <= 100.0;
    }
}

if (!function_exists('flatcms_editor_update_tag_width')) {
    function flatcms_editor_update_tag_width(string $tag, ?string $width): string
    {
        $width = $width !== null ? trim($width) : null;
        $stylePattern = '/\s+style\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i';
        if (preg_match($stylePattern, $tag, $match, PREG_OFFSET_CAPTURE) === 1) {
            $style = (string) (($match[2][0] ?? '') !== ''
                ? $match[2][0]
                : (($match[3][0] ?? '') !== '' ? $match[3][0] : ($match[4][0] ?? '')));
            $declarations = flatcms_editor_style_declarations(
                html_entity_decode($style, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            );
            unset($declarations['width']);
            if ($width !== null) {
                $declarations['width'] = $width;
            }

            $replacement = '';
            if ($declarations !== []) {
                $parts = [];
                foreach ($declarations as $name => $value) {
                    $parts[] = $name . ': ' . $value;
                }
                $replacement = ' ' . 'style' . '="' . htmlspecialchars(
                    implode('; ', $parts) . ';',
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                ) . '"';
            }

            return substr_replace(
                $tag,
                $replacement,
                (int) $match[0][1],
                strlen((string) $match[0][0])
            );
        }

        if ($width === null) {
            return $tag;
        }

        $position = strrpos($tag, '/>');
        if ($position === false) {
            $position = strrpos($tag, '>');
        }
        if ($position === false) {
            return $tag;
        }

        return substr($tag, 0, $position)
            . ' ' . 'style' . '="width: '
            . htmlspecialchars($width, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            . ';"'
            . substr($tag, $position);
    }
}

if (!function_exists('flatcms_apply_editor_image_width_delta_to_source')) {
    /**
     * Reapply only image width changes produced by the FlatCMS CKEditor control.
     * Other attributes and the surrounding human-authored source stay untouched.
     */
    function flatcms_apply_editor_image_width_delta_to_source(
        string $existingHtml,
        string $baselineHtml,
        string $submittedHtml
    ): ?string {
        $collect = static function (string $html): array {
            $tokens = flatcms_editor_html_tokens($html);
            $images = [];
            foreach ($tokens as $tokenIndex => $token) {
                if (preg_match('/^<\s*img\b/i', $token) !== 1) {
                    continue;
                }
                $attributes = flatcms_editor_tag_attributes($token);
                $styles = flatcms_editor_style_declarations((string) ($attributes['style'] ?? ''));
                unset($attributes['style']);
                $width = $styles['width'] ?? null;
                unset($styles['width']);
                ksort($attributes);
                ksort($styles);
                $images[] = [
                    'token_index' => $tokenIndex,
                    'src' => (string) ($attributes['src'] ?? ''),
                    'alt' => (string) ($attributes['alt'] ?? ''),
                    'attributes' => $attributes,
                    'styles' => $styles,
                    'width' => $width !== null ? trim((string) $width) : null,
                ];
            }

            return [$tokens, $images];
        };

        [$existingTokens, $existingImages] = $collect($existingHtml);
        [, $baselineImages] = $collect($baselineHtml);
        [, $submittedImages] = $collect($submittedHtml);
        if (count($baselineImages) !== count($submittedImages)) {
            return null;
        }
        if ($baselineImages === []) {
            return $existingHtml;
        }

        $usedExisting = [];
        foreach ($baselineImages as $index => $baselineImage) {
            $submittedImage = $submittedImages[$index];
            if (
                $baselineImage['attributes'] !== $submittedImage['attributes']
                || $baselineImage['styles'] !== $submittedImage['styles']
            ) {
                return null;
            }

            $baselineWidth = $baselineImage['width'];
            $submittedWidth = $submittedImage['width'];
            if ($baselineWidth === $submittedWidth) {
                continue;
            }
            if ($submittedWidth !== null && !flatcms_editor_valid_image_width($submittedWidth)) {
                return null;
            }

            $existingIndex = null;
            foreach ($existingImages as $candidateIndex => $candidate) {
                if (isset($usedExisting[$candidateIndex])) {
                    continue;
                }
                if (
                    $candidate['src'] === $baselineImage['src']
                    && ($baselineImage['alt'] === '' || $candidate['alt'] === $baselineImage['alt'])
                ) {
                    $existingIndex = $candidateIndex;
                    break;
                }
            }
            if ($existingIndex === null && isset($existingImages[$index]) && !isset($usedExisting[$index])) {
                $existingIndex = $index;
            }
            if ($existingIndex === null || !isset($existingImages[$existingIndex])) {
                return null;
            }

            $usedExisting[$existingIndex] = true;
            $tokenIndex = $existingImages[$existingIndex]['token_index'];
            $existingTokens[$tokenIndex] = flatcms_editor_update_tag_width(
                $existingTokens[$tokenIndex],
                $submittedWidth
            );
        }

        return implode('', $existingTokens);
    }
}

if (!function_exists('flatcms_apply_editor_delta_to_source')) {
    /**
     * Reapply real CKEditor text changes to the original source instead of
     * storing CKEditor's normalized/minified round-trip representation.
     */
    function flatcms_apply_editor_delta_to_source(
        string $existingHtml,
        string $baselineHtml,
        string $submittedHtml
    ): ?string {
        if (flatcms_editor_tag_skeleton($baselineHtml) !== flatcms_editor_tag_skeleton($submittedHtml)) {
            return null;
        }

        $existingHtml = flatcms_apply_editor_image_width_delta_to_source(
            $existingHtml,
            $baselineHtml,
            $submittedHtml
        );
        if ($existingHtml === null) {
            return null;
        }

        $existingNodes = flatcms_editor_text_nodes($existingHtml);
        $baselineNodes = flatcms_editor_text_nodes($baselineHtml);
        $submittedNodes = flatcms_editor_text_nodes($submittedHtml);
        $existingValues = array_column($existingNodes, 'normalized');
        $baselineValues = array_column($baselineNodes, 'normalized');
        $submittedValues = array_column($submittedNodes, 'normalized');

        $baselineToExisting = [];
        foreach (flatcms_editor_lcs_pairs($existingValues, $baselineValues) as [$existingIndex, $baselineIndex]) {
            $baselineToExisting[$baselineIndex] = $existingIndex;
        }

        $anchors = [[-1, -1]];
        foreach (flatcms_editor_lcs_pairs($baselineValues, $submittedValues) as $pair) {
            $anchors[] = $pair;
        }
        $anchors[] = [count($baselineNodes), count($submittedNodes)];

        $changes = [];
        for ($anchorIndex = 1; $anchorIndex < count($anchors); $anchorIndex++) {
            [$previousBaseline, $previousSubmitted] = $anchors[$anchorIndex - 1];
            [$nextBaseline, $nextSubmitted] = $anchors[$anchorIndex];
            $baselineStart = $previousBaseline + 1;
            $submittedStart = $previousSubmitted + 1;
            $baselineLength = $nextBaseline - $baselineStart;
            $submittedLength = $nextSubmitted - $submittedStart;

            if ($baselineLength === 0 && $submittedLength === 0) {
                continue;
            }
            if ($baselineLength === $submittedLength) {
                for ($offset = 0; $offset < $baselineLength; $offset++) {
                    $changes[] = [
                        'type' => 'replace',
                        'baseline' => $baselineStart + $offset,
                        'submitted' => $submittedStart + $offset,
                    ];
                }
                continue;
            }
            if ($submittedLength === 0) {
                for ($offset = 0; $offset < $baselineLength; $offset++) {
                    $changes[] = [
                        'type' => 'delete',
                        'baseline' => $baselineStart + $offset,
                        'submitted' => null,
                    ];
                }
                continue;
            }

            return null;
        }

        if ($changes === []) {
            return $existingHtml;
        }

        $existingTokens = flatcms_editor_html_tokens($existingHtml);
        $usedExisting = array_fill_keys(array_values($baselineToExisting), true);
        foreach ($changes as $change) {
            $baselineIndex = $change['baseline'];
            $existingIndex = $baselineToExisting[$baselineIndex] ?? null;
            if ($existingIndex === null) {
                $target = $baselineNodes[$baselineIndex]['normalized'] ?? '';
                $bestIndex = null;
                $bestDistance = PHP_INT_MAX;
                foreach ($existingNodes as $candidateIndex => $candidate) {
                    if (isset($usedExisting[$candidateIndex]) || $candidate['normalized'] !== $target) {
                        continue;
                    }
                    $distance = abs($candidateIndex - $baselineIndex);
                    if ($distance < $bestDistance) {
                        $bestDistance = $distance;
                        $bestIndex = $candidateIndex;
                    }
                }
                $existingIndex = $bestIndex;
            }
            if ($existingIndex === null || !isset($existingNodes[$existingIndex])) {
                return null;
            }
            $usedExisting[$existingIndex] = true;
            $tokenIndex = $existingNodes[$existingIndex]['token_index'];

            if ($change['type'] === 'delete') {
                $existingTokens[$tokenIndex] = '';
                continue;
            }

            $submittedIndex = $change['submitted'];
            if (!is_int($submittedIndex) || !isset($submittedNodes[$submittedIndex])) {
                return null;
            }
            $existingTokens[$tokenIndex] = flatcms_apply_text_value_delta(
                $existingNodes[$existingIndex]['raw'],
                $baselineNodes[$baselineIndex]['raw'],
                $submittedNodes[$submittedIndex]['raw']
            );
        }

        return implode('', $existingTokens);
    }
}

if (!function_exists('flatcms_reconcile_editor_html')) {
    /**
     * Preserve the human-authored content.html source. When CKEditor supplies
     * its normalized baseline, only real text edits are reapplied to the
     * original tokens; indentation, entities and structural markup stay intact.
     */
    function flatcms_reconcile_editor_html(
        string $submittedHtml,
        ?string $existingHtml = null,
        ?string $editorBaselineHtml = null
    ): string {
        $submittedHtml = flatcms_sanitize_editor_html($submittedHtml);
        $normalizeLineEndings = static fn (string $html): string => str_replace(["\r\n", "\r"], "\n", $html);

        if ($existingHtml === null) {
            return $normalizeLineEndings($submittedHtml);
        }

        $safeExistingHtml = flatcms_sanitize_editor_html($existingHtml);
        $safeBaselineHtml = $editorBaselineHtml !== null
            ? flatcms_sanitize_editor_html($editorBaselineHtml)
            : '';
        if ($safeBaselineHtml !== '') {
            $reconciled = flatcms_apply_editor_delta_to_source(
                $safeExistingHtml,
                $safeBaselineHtml,
                $submittedHtml
            );
            if ($reconciled !== null) {
                return $normalizeLineEndings($reconciled);
            }
        }
        $existingStructured = flatcms_content_is_theme_structured($safeExistingHtml);
        if ($existingStructured) {
            $submittedHtml = flatcms_unwrap_editor_inline_wrappers($submittedHtml);

            $existingTags = flatcms_count_structural_tags($safeExistingHtml);
            $submittedTags = flatcms_count_structural_tags($submittedHtml);
            $tagsFlattened = $existingTags > 0 && $submittedTags === 0;
            $markersLost = !flatcms_content_is_theme_structured($submittedHtml);
            $effectivelyEmpty = trim((string) strip_tags($submittedHtml)) === '';
            if (($tagsFlattened || $markersLost) && !$effectivelyEmpty) {
                return flatcms_merge_submitted_text_into_structure($safeExistingHtml, $submittedHtml);
            }
        }

        if (
            $safeExistingHtml === $existingHtml
            && $normalizeLineEndings($submittedHtml) === $normalizeLineEndings($safeExistingHtml)
        ) {
            return $existingHtml;
        }

        if ($safeExistingHtml === $existingHtml) {
            $tokenize = static function (string $html): array {
                $tokens = preg_split(
                    '/(<!--.*?-->|<![^>]*>|<[^>]+>)/s',
                    $html,
                    -1,
                    PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
                );

                return is_array($tokens) ? $tokens : [];
            };
            $isMarkup = static fn (string $token): bool => str_starts_with($token, '<');
            $isMeaningfulText = static fn (string $token): bool => preg_match('/^\s*$/u', $token) !== 1;
            $normalizeMarkup = static function (string $token): string {
                if (str_starts_with($token, '<!--')) {
                    return '<!--' . trim(substr($token, 4, -3)) . '-->';
                }

                return strtolower((string) preg_replace('/\s+/u', ' ', trim($token)));
            };

            $submittedTokens = $tokenize($submittedHtml);
            $existingTokens = $tokenize($safeExistingHtml);
            $submittedStructure = [];
            $existingStructure = [];
            $submittedText = [];

            foreach ($submittedTokens as $token) {
                if ($isMarkup($token)) {
                    $submittedStructure[] = $normalizeMarkup($token);
                } elseif ($isMeaningfulText($token)) {
                    $submittedText[] = $token;
                }
            }

            $existingTextCount = 0;
            foreach ($existingTokens as $token) {
                if ($isMarkup($token)) {
                    $existingStructure[] = $normalizeMarkup($token);
                } elseif ($isMeaningfulText($token)) {
                    $existingTextCount++;
                }
            }

            if ($submittedStructure === $existingStructure && count($submittedText) === $existingTextCount) {
                $textIndex = 0;
                foreach ($existingTokens as $index => $token) {
                    if (!$isMarkup($token) && $isMeaningfulText($token)) {
                        $existingTokens[$index] = $submittedText[$textIndex++] ?? $token;
                    }
                }

                return implode('', $existingTokens);
            }
        }

        if ($existingStructured) {
            return $safeExistingHtml;
        }

        return $normalizeLineEndings($submittedHtml);
    }
}

if (!function_exists('footer_sanitize_fragment')) {
    function footer_sanitize_fragment(string $html): string
    {
        $allowed = '<p><br><strong><em><b><i><u><ul><ol><li><a><span><small><blockquote><pre><code>';
        $clean = preg_replace('#<\s*(script|style|iframe|object|embed)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html) ?? '';
        $clean = strip_tags((string) $clean, $allowed);
        $clean = preg_replace('/\son[a-z]+\s*=\s*("|\').*?\1/i', '', (string) $clean);
        $clean = preg_replace('/href\s*=\s*("|\')\s*javascript:.*?\1/i', 'href="#"', (string) $clean);
        return trim((string) $clean);
    }
}

if (!function_exists('footer_settings')) {
    function footer_settings(?array $footer = null, ?array $settings = null): array
    {
        $settings = $settings ?? \App\Core\FlatFile::settings();

        if ($footer === null) {
            $footer = \App\Core\FlatFile::settings('footer');
        }
        if (!is_array($footer)) {
            $footer = [];
        }

        if (class_exists(\App\Modules\Footer\Services\FooterTranslationService::class)) {
            return (new \App\Modules\Footer\Services\FooterTranslationService())->normalizeSettings($footer, $settings);
        }

        $defaults = footer_default_config($settings);

        $powered = is_array($footer['powered_by'] ?? null) ? $footer['powered_by'] : [];
        $defaultPowered = $defaults['powered_by'];

        $enabledRaw = $footer['enabled'] ?? $defaults['enabled'];
        $enabled = filter_var($enabledRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($enabled === null) {
            $enabled = (bool) $defaults['enabled'];
        }

        $poweredEnabledRaw = $powered['enabled'] ?? $defaultPowered['enabled'];
        $poweredEnabled = filter_var($poweredEnabledRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($poweredEnabled === null) {
            $poweredEnabled = (bool) $defaultPowered['enabled'];
        }

        $brandText = trim((string) ($footer['brand_text'] ?? ''));
        $copyrightText = trim((string) ($footer['copyright_text'] ?? ''));
        $poweredLabel = trim((string) ($powered['label'] ?? ''));
        $poweredUrl = trim((string) ($powered['url'] ?? ''));

        return [
            'enabled' => $enabled,
            'brand_text' => $brandText !== '' ? $brandText : (string) $defaults['brand_text'],
            'copyright_text' => $copyrightText !== '' ? $copyrightText : (string) $defaults['copyright_text'],
            'powered_by' => [
                'enabled' => $poweredEnabled,
                'label' => $poweredLabel !== '' ? $poweredLabel : (string) $defaultPowered['label'],
                'url' => $poweredUrl !== '' ? $poweredUrl : (string) $defaultPowered['url'],
            ],
        ];
    }
}

if (!function_exists('footer_render_payload')) {
    function footer_render_payload(?array $settings = null, ?array $footer = null): array
    {
        $settings = $settings ?? \App\Core\FlatFile::settings();
        $footer = footer_settings($footer, $settings);
        if (class_exists(\App\Modules\Footer\Services\FooterTranslationService::class)) {
            $footer = (new \App\Modules\Footer\Services\FooterTranslationService())->resolveForLocale(
                $footer,
                $settings,
                locale()
            );
        }

        $siteName = trim((string) ($settings['site_name'] ?? __('app_name', 'Core')));
        if ($siteName === '') {
            $siteName = __('app_name', 'Core');
        }

        $copyrightTemplate = trim((string) ($footer['copyright_text'] ?? ''));
        if ($copyrightTemplate === '') {
            $copyrightTemplate = (string) (footer_default_config($settings)['copyright_text'] ?? '');
        }

        $copyrightHtml = strtr($copyrightTemplate, [
            '{site_name}' => $siteName,
            '{year}' => date('Y'),
        ]);
        $copyrightHtml = footer_sanitize_fragment($copyrightHtml);
        $copyrightText = trim(strip_tags($copyrightHtml));

        return [
            'footer' => [
                'enabled' => (bool) ($footer['enabled'] ?? true),
                'brand_text' => trim((string) ($footer['brand_text'] ?? $siteName)),
                'copyright_text' => $copyrightText,
                'copyright_html' => $copyrightHtml,
                'powered_by' => [
                    'enabled' => (bool) (($footer['powered_by']['enabled'] ?? true)),
                    'label' => trim((string) ($footer['powered_by']['label'] ?? __('app_name', 'Core'))),
                    'url' => trim((string) ($footer['powered_by']['url'] ?? 'https://flat-cms.fr')),
                ],
            ],
        ];
    }
}

if (!function_exists('is_auth')) {
    function is_auth(): bool
    {
        return app()->session()->has('user');
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        $user = auth();
        if (!$user) {
            return false;
        }
        $role = $user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER;
        return \App\Modules\Auth\Services\RoleService::hasPermission($role, $permission);
    }
}

if (!function_exists('can_any')) {
    function can_any(array $permissions): bool
    {
        $user = auth();
        if (!$user) {
            return false;
        }
        $role = $user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER;
        return \App\Modules\Auth\Services\RoleService::hasAnyPermission($role, $permissions);
    }
}

if (!function_exists('user_role')) {
    function user_role(): string
    {
        $user = auth();
        return $user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER;
    }
}

if (!function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return \App\Core\I18n::isRtl();
    }
}

if (!function_exists('text_direction')) {
    function text_direction(): string
    {
        return \App\Core\I18n::getDirection();
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        echo '<pre class="debug-dump">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n---\n";
        }
        echo '</pre>';
        exit;
    }
}

if (!function_exists('dump')) {
    function dump(mixed ...$vars): void
    {
        echo '<pre class="debug-dump">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n---\n";
        }
        echo '</pre>';
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('flatcms_front_external_script')) {
    /**
     * Rend un tag script externe conforme à la politique de consentement frontend.
     *
     * Options:
     * - id: string
     * - async: bool
     * - defer: bool
     * - crossorigin: string
     * - referrerpolicy: string
     * - integrity: string
     * - vendors|vendor|categories|category: string|array
     * - essential: bool (ne jamais bloquer avant consentement)
     * - data: array<string, scalar> (ajout d'attributs data-*)
     */
    function flatcms_front_external_script(string $src, array $options = []): string
    {
        $src = trim($src);
        if ($src === '') {
            return '';
        }

        $parsed = parse_url($src);
        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
        if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $normalizeBool = static function (mixed $value): bool {
            $normalized = strtolower(trim((string) $value));
            return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
        };

        $consentRuntimeEnabled = $normalizeBool(env('COOKIE_BANNER_ENABLED', 0))
            && $normalizeBool(env('COOKIE_REQUIRE_CONSENT', 0))
            && trim((string) env('AXEPTIO_CLIENT_ID', '')) !== ''
            && trim((string) env('AXEPTIO_COOKIES_VERSION', '')) !== '';

        $essential = $normalizeBool($options['essential'] ?? false);

        $requirementsRaw = $options['vendors']
            ?? $options['vendor']
            ?? $options['categories']
            ?? $options['category']
            ?? [];

        if (!is_array($requirementsRaw)) {
            $requirementsRaw = preg_split('/\s*,\s*/', (string) $requirementsRaw) ?: [];
        }

        $requirements = array_values(array_filter(array_map(static function (mixed $value): string {
            return strtolower(trim((string) $value));
        }, $requirementsRaw), static fn (string $item): bool => $item !== ''));

        $attrs = [];
        if (isset($options['id']) && trim((string) $options['id']) !== '') {
            $attrs[] = 'id="' . e(trim((string) $options['id'])) . '"';
        }
        if (array_key_exists('async', $options) && (bool) $options['async']) {
            $attrs[] = 'async';
        }
        if (array_key_exists('defer', $options) && (bool) $options['defer']) {
            $attrs[] = 'defer';
        }

        $crossorigin = trim((string) ($options['crossorigin'] ?? ''));
        if ($crossorigin !== '') {
            $attrs[] = 'crossorigin="' . e($crossorigin) . '"';
        }

        $referrerPolicy = trim((string) ($options['referrerpolicy'] ?? ''));
        if ($referrerPolicy !== '') {
            $attrs[] = 'referrerpolicy="' . e($referrerPolicy) . '"';
        }

        $integrity = trim((string) ($options['integrity'] ?? ''));
        if ($integrity !== '') {
            $attrs[] = 'integrity="' . e($integrity) . '"';
        }

        $dataAttrs = is_array($options['data'] ?? null) ? $options['data'] : [];
        foreach ($dataAttrs as $key => $value) {
            $dataKey = strtolower(trim((string) $key));
            $dataKey = preg_replace('/[^a-z0-9_-]+/', '-', $dataKey) ?? '';
            if ($dataKey === '') {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            $attrs[] = 'data-' . $dataKey . '="' . e((string) $value) . '"';
        }

        if ($essential) {
            $attrs[] = 'data-flatcms-consent-essential="1"';
        }

        if (!empty($requirements)) {
            $attrs[] = 'data-flatcms-consent-vendors="' . e(implode(',', $requirements)) . '"';
        }

        $openScriptTag = '<' . 'script ';
        $closeScriptTag = '</' . 'script>';

        if ($consentRuntimeEnabled && !$essential) {
            $attrs[] = 'type="text/plain"';
            $attrs[] = 'data-flatcms-consent-src="' . e($src) . '"';
            return $openScriptTag . implode(' ', $attrs) . '>' . $closeScriptTag;
        }

        $attrs[] = 'src="' . e($src) . '"';
        return $openScriptTag . implode(' ', $attrs) . '>' . $closeScriptTag;
    }
}

if (!function_exists('str_slug')) {
    function str_slug(string $text, string $separator = '-'): string
    {
        // Convert to ASCII
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        // Apostrophes should collapse, not create extra words in French slugs.
        $text = str_replace(["'", '`'], '', $text);
        // Replace every non-alphanumeric sequence with a single separator.
        $text = preg_replace('/[^a-zA-Z0-9]+/', $separator, $text);
        // Lowercase
        $text = strtolower(trim($text, $separator));
        // Remove duplicate separators
        return preg_replace('/' . preg_quote($separator) . '+/', $separator, $text);
    }
}

if (!function_exists('str_limit')) {
    function str_limit(string $text, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit) . $end;
    }
}

if (!function_exists('now')) {
    function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }
}

if (!function_exists('php_date_to_icu')) {
    function php_date_to_icu(string $format): string
    {
        $map = [
            'd' => 'dd',
            'j' => 'd',
            'm' => 'MM',
            'n' => 'M',
            'Y' => 'yyyy',
            'y' => 'yy',
            'H' => 'HH',
            'h' => 'hh',
            'i' => 'mm',
            's' => 'ss',
            'M' => 'MMM',
            'F' => 'MMMM',
        ];

        $pattern = '';
        $len = strlen($format);

        for ($i = 0; $i < $len; $i++) {
            $ch = $format[$i];

            if ($ch === '\\' && $i + 1 < $len) {
                $i++;
                $literal = $format[$i];
                $pattern .= "'" . str_replace("'", "''", $literal) . "'";
                continue;
            }

            if (isset($map[$ch])) {
                $pattern .= $map[$ch];
                continue;
            }

            if (ctype_alpha($ch)) {
                $pattern .= "'" . str_replace("'", "''", $ch) . "'";
                continue;
            }

            $pattern .= $ch;
        }

        return $pattern;
    }
}

if (!function_exists('human_date')) {
    function human_date(string $date, string $format = 'd/m/Y H:i', ?string $locale = null): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        if ($locale === null && class_exists('\\App\\Core\\I18n')) {
            $locale = \App\Core\I18n::getLocale();
        }
        $locale = $locale ? str_replace('-', '_', $locale) : 'en_US';

        if (class_exists('IntlDateFormatter')) {
            $pattern = php_date_to_icu($format);
            $formatter = new \IntlDateFormatter(
                $locale,
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                date_default_timezone_get(),
                \IntlDateFormatter::GREGORIAN,
                $pattern
            );
            $output = $formatter->format($timestamp);
            if ($output !== false) {
                return $output;
            }
        }

        return date($format, $timestamp);
    }
}

if (!function_exists('human_size')) {
    function human_size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
