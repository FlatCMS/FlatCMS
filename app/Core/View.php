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

class View
{
    private array $data = [];
    private array $sections = [];
    private ?string $currentSection = null;
    private ?string $parentLayout = null;
    private static ?View $instance = null;

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function getInstance(): ?View
    {
        return self::$instance;
    }

    public function render(string $template, array $data = [], ?string $layout = null): void
    {
        $this->data = array_merge($this->data, $data);
        
        // Add global data
        $this->data['locale'] = I18n::getLocale();
        $this->data['csrf_token'] = app()->session()->token();
        $this->data['auth_user'] = app()->session()->get('user');
        $flash = app()->session()->consumeFlash();
        $this->data['flash'] = $flash;
        $this->data['errors'] = is_array($flash['errors'] ?? null) ? $flash['errors'] : [];
        $this->data['old'] = is_array($flash['old'] ?? null) ? $flash['old'] : [];

        // Resolve template path
        $templatePath = $this->resolvePath($template);
        
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("View template not found: {$template}");
        }

        // Render template
        $content = $this->renderFile($templatePath, $this->data);

        // If layout specified, wrap content
        if ($layout || $this->parentLayout) {
            $layoutName = $layout ?? $this->parentLayout;
            $layoutPath = $this->resolveLayoutPath($layoutName);
            
            if (file_exists($layoutPath)) {
                $this->data['content'] = $content;
                $this->sections['content'] = $content;
                $content = $this->renderFile($layoutPath, $this->data);
            }
        }

        echo $content;
    }

    private function resolvePath(string $template): string
    {
        // Check if it's a module view: "ModuleName/Views/path"
        if (preg_match('/^([A-Z][a-zA-Z]+)\/Views\/(.+)$/', $template, $matches)) {
            $module = $matches[1];
            $viewPath = $matches[2];
            $modulePath = BASE_PATH . "/app/Modules/{$module}/Views/{$viewPath}.php";
            if (file_exists($modulePath)) {
                return $modulePath;
            }
            return BASE_PATH . "/app/Extensions/{$module}/Views/{$viewPath}.php";
        }

        // Check if it's a theme view
        if (str_starts_with($template, 'admin/')) {
            $theme = $this->getActiveTheme('admin');
            $viewPath = substr($template, 6);
            $path = BASE_PATH . "/themes/admin/{$theme}/views/{$viewPath}.php";
            if (file_exists($path)) {
                return $path;
            }
            return BASE_PATH . "/public/themes/admin/{$theme}/views/{$viewPath}.php";
        }

        if (str_starts_with($template, 'frontend/')) {
            $theme = $this->getActiveTheme('frontend');
            $viewPath = substr($template, 9);
            $path = BASE_PATH . "/themes/frontend/{$theme}/views/{$viewPath}.php";
            if (file_exists($path)) {
                return $path;
            }
            return BASE_PATH . "/public/themes/frontend/{$theme}/views/{$viewPath}.php";
        }

        // Default: resources views (if present), fallback to Core module
        $resourcePath = BASE_PATH . "/resources/views/{$template}.php";
        if (file_exists($resourcePath)) {
            return $resourcePath;
        }

        return BASE_PATH . "/app/Modules/Core/Views/{$template}.php";
    }

    private function resolveLayoutPath(string $layout): string
    {
        // Admin layout
        if (str_starts_with($layout, 'admin.')) {
            $theme = $this->getActiveTheme('admin');
            $layoutName = substr($layout, 6);
            $path = BASE_PATH . "/themes/admin/{$theme}/views/layouts/{$layoutName}.php";
            if (file_exists($path)) {
                return $path;
            }
            return BASE_PATH . "/public/themes/admin/{$theme}/views/layouts/{$layoutName}.php";
        }

        // Frontend layout
        if (str_starts_with($layout, 'frontend.')) {
            $theme = $this->getActiveTheme('frontend');
            $layoutName = substr($layout, 9);
            $path = BASE_PATH . "/themes/frontend/{$theme}/views/layouts/{$layoutName}.php";
            if (file_exists($path)) {
                return $path;
            }
            return BASE_PATH . "/public/themes/frontend/{$theme}/views/layouts/{$layoutName}.php";
        }

        // Default layouts (resources, fallback to Core module)
        $resourceLayout = BASE_PATH . "/resources/views/layouts/{$layout}.php";
        if (file_exists($resourceLayout)) {
            return $resourceLayout;
        }

        return BASE_PATH . "/app/Modules/Core/Views/layouts/{$layout}.php";
    }

    private function getActiveTheme(string $type): string
    {
        // Read from settings.json (dynamic) first, fallback to config (static)
        $settings = FlatFile::settings();
        
        if ($type === 'admin') {
            return $settings['admin_theme'] ?? config('app.admin_theme', 'admin-modern-pro');
        }
        
        return $settings['frontend_theme'] ?? config('app.frontend_theme', 'default');
    }

    private function renderFile(string $path, array $data): string
    {
        extract($data, EXTR_SKIP);
        
        ob_start();
        include $path;
        return ob_get_clean();
    }

    public function extends(string $layout): void
    {
        $this->parentLayout = $layout;
    }

    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function include(string $partial, array $data = []): void
    {
        $mergedData = array_merge($this->data, $data);
        $path = $this->resolvePath($partial);
        
        if (file_exists($path)) {
            extract($mergedData, EXTR_SKIP);
            include $path;
        }
    }

    public function component(string $name, array $data = []): void
    {
        $theme = config('app.admin_theme', 'admin-modern-pro');
        $path = BASE_PATH . "/themes/admin/{$theme}/views/components/{$name}.php";
        if (!file_exists($path)) {
            $path = BASE_PATH . "/public/themes/admin/{$theme}/views/components/{$name}.php";
        }
        
        if (!file_exists($path)) {
            $path = BASE_PATH . "/app/Modules/Core/Views/components/{$name}.php";
        }

        if (file_exists($path)) {
            $mergedData = array_merge($this->data, $data);
            extract($mergedData, EXTR_SKIP);
            include $path;
        }
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
