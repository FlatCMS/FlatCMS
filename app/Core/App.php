<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core;

class App
{
    private static ?App $instance = null;
    private static bool $initializing = false;
    private array $config = [];
    private array $services = [];
    private ?Router $router = null;
    private ?Request $request = null;
    private ?Session $session = null;
    private bool $booted = false;

    private function __construct()
    {
        // Load config first (no dependencies)
        $this->loadConfig();
    }

    public static function getInstance(): App
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;
        $this->session = new Session();
        $this->request = new Request();
        $this->router = new Router();
    }

    private function loadConfig(): void
    {
        $configPath = BASE_PATH . '/config/app.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
        }
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        if (($keys[0] ?? '') === 'app' && !array_key_exists('app', $this->config)) {
            array_shift($keys);
        }
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    public function router(): Router
    {
        $this->boot();
        return $this->router;
    }

    public function request(): Request
    {
        $this->boot();
        return $this->request;
    }

    public function session(): Session
    {
        $this->boot();
        return $this->session;
    }

    public function register(string $name, callable $resolver): void
    {
        $this->services[$name] = $resolver;
    }

    public function resolve(string $name): mixed
    {
        if (isset($this->services[$name])) {
            return $this->services[$name]($this);
        }
        throw new \RuntimeException("Service '{$name}' not found.");
    }

    public function run(): void
    {
        try {
            // Boot the application (lazy load components)
            $this->boot();

            // Start session
            $this->session->start();

            // Check maintenance mode (before routing)
            $this->checkMaintenance();

            // Initialize i18n (admin can override with preferred language)
            $locale = $this->request->locale();
            $user = $this->session->get('user');
            if ($user && str_starts_with($this->request->uri(), '/admin')) {
                $preferred = $user['admin_language'] ?? '';
                if ($preferred !== '' && in_array($preferred, I18n::getSupportedLocales(), true)) {
                    $locale = $preferred;
                }
            }
            I18n::init($locale);

            // Load hooks + listeners
            $this->loadHooks();
            Hook::run('app.booting', $this);

            // Load routes
            $this->loadRoutes();

            Hook::run('app.booted', $this);

            CacheManager::instance()->autoCleanup();

            // Dispatch request
            $this->router->dispatch($this->request);

        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    private function checkMaintenance(): void
    {
        $settings = FlatFile::settings();

        if (empty($settings['maintenance_mode'])) {
            return;
        }

        // Allow admin routes, login and logout
        $uri = $this->request->uri();
        if (str_starts_with($uri, '/admin') || str_starts_with($uri, '/login') || str_starts_with($uri, '/logout')) {
            return;
        }

        // Allow logged-in admins
        $user = $this->session->get('user');
        if ($user && \App\Modules\Auth\Services\RoleService::canAccessAdmin((string) ($user['role'] ?? ''))) {
            return;
        }

        // Show 503 maintenance page
        http_response_code(503);
        header('Retry-After: 600');
        $siteName = $settings['site_name'] ?? __('app_name', 'Core');

        $resourcePath = BASE_PATH . '/resources/views/errors/503.php';
        $corePath = BASE_PATH . '/app/Modules/Core/Views/errors/503.php';

        if (file_exists($resourcePath)) {
            include $resourcePath;
        } elseif (file_exists($corePath)) {
            include $corePath;
        } else {
            echo '<h1>' . e(__('error.server', 'Core')) . '</h1>';
        }
        exit;
    }

    private function loadRoutes(): void
    {
        $router = $this->router;
        
        // Load module routes FIRST (specific routes before generic ones)
        $manager = new ModuleManager([
            BASE_PATH . '/app/Modules',
            BASE_PATH . '/app/Extensions',
        ], BASE_PATH . '/data/modules.json');

        foreach ($manager->enabled() as $module => $meta) {
            $moduleRoutes = trim((string) ($meta['routes_path'] ?? ''));
            if ($moduleRoutes !== '') {
                require $moduleRoutes;
                continue;
            }

            $routesStatus = (string) ($meta['routes_status'] ?? 'absent');
            if (($meta['routes_declared'] ?? false) && ($routesStatus === 'missing' || $routesStatus === 'invalid')) {
                $this->reportRuntimePathIssue($module, $meta, 'routes', $routesStatus);
            }
        }

        // Load main routes AFTER (generic frontend routes with {lang} prefix)
        $routesPath = BASE_PATH . '/config/routes.php';
        if (file_exists($routesPath)) {
            require $routesPath;
        }
    }

    private function loadHooks(): void
    {
        $manager = new ModuleManager([
            BASE_PATH . '/app/Modules',
            BASE_PATH . '/app/Extensions',
        ], BASE_PATH . '/data/modules.json');

        foreach ($manager->enabled() as $module => $meta) {
            $listenersFile = trim((string) ($meta['hooks_path'] ?? ''));
            if ($listenersFile !== '') {
                require_once $listenersFile;
                continue;
            }

            $hooksStatus = (string) ($meta['hooks_status'] ?? 'absent');
            if (($meta['hooks_declared'] ?? false) && ($hooksStatus === 'missing' || $hooksStatus === 'invalid')) {
                $this->reportRuntimePathIssue($module, $meta, 'hooks', $hooksStatus);
            }
        }
    }

    private function reportRuntimePathIssue(string $module, array $meta, string $kind, string $status): void
    {
        $basePath = (string) ($meta['base_path'] ?? '');
        $location = (string) ($meta['location'] ?? 'module');
        $declared = (string) ($meta[$kind] ?? '');
        $manifest = (string) ($meta['manifest_name'] ?? 'manifest');
        $moduleLabel = (string) ($meta['name'] ?? $module);

        error_log(sprintf(
            '[FlatCMS] %s path issue for %s "%s": status=%s, declared=%s, manifest=%s, base=%s',
            $kind,
            $location,
            $moduleLabel,
            $status,
            $declared !== '' ? $declared : '-',
            $manifest !== '' ? $manifest : '-',
            $basePath !== '' ? $basePath : '-'
        ));
    }

    private function handleException(\Throwable $e): void
    {
        $debug = env('APP_DEBUG', false);
        
        if ($debug) {
            echo '<h1>' . e(__('error.server', 'Core')) . '</h1>';
            echo '<p><strong>' . get_class($e) . ':</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . $e->getFile() . ':' . $e->getLine() . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        } else {
            http_response_code(500);
            echo e(__('error.server', 'Core'));
        }
    }
}
