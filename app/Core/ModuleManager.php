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

final class ModuleManager
{
    private const NON_MANAGEABLE_SIDEBAR_MODULES = [
        'AiAgent' => true,
        'Auth' => true,
        'Dashboard' => true,
        'Install' => true,
    ];

    private array $modulesPaths;
    private string $statePath;
    private array $modules = [];
    private array $state = [];
    private ?array $resolvedEnabled = null;

    /**
     * @param string|array|null $modulesPath
     */
    public function __construct($modulesPath = null, ?string $statePath = null)
    {
        $paths = $modulesPath ?? [
            BASE_PATH . '/app/Modules',
            BASE_PATH . '/app/Extensions',
        ];
        if (!is_array($paths)) {
            $paths = [$paths];
        }
        $this->modulesPaths = array_values(array_unique($paths));
        $this->statePath = $statePath ?? (BASE_PATH . '/data/modules.json');
        $this->loadState();
        $this->loadModules();
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function enabled(): array
    {
        return $this->resolveEnabledModules();
    }

    public function enabledNames(): array
    {
        return array_keys($this->resolveEnabledModules());
    }

    public function isEnabled(string $module): bool
    {
        $enabled = $this->resolveEnabledModules();
        return isset($enabled[$module]);
    }

    public function isSidebarVisible(string $module): bool
    {
        $meta = $this->modules[$module] ?? null;
        if (!is_array($meta)) {
            return true;
        }

        return (bool) ($meta['sidebar_visible'] ?? true);
    }

    public function isSidebarManageable(string $module): bool
    {
        $meta = $this->modules[$module] ?? null;
        if (!is_array($meta)) {
            return true;
        }

        return (bool) ($meta['sidebar_manageable'] ?? true);
    }

    public function get(string $module): ?array
    {
        return $this->modules[$module] ?? null;
    }

    public function widgets(bool $enabledOnly = true): array
    {
        $modules = $enabledOnly ? $this->resolveEnabledModules() : $this->modules;
        $widgets = [];

        foreach ($modules as $module => $meta) {
            foreach ($this->widgetsFor($module, $enabledOnly) as $widget) {
                $widgets[$widget['id']] = $widget;
            }
        }

        return array_values($widgets);
    }

    public function widgetsFor(string $module, bool $enabledOnly = false): array
    {
        $meta = $this->modules[$module] ?? null;
        if (!is_array($meta)) {
            return [];
        }

        if ($enabledOnly && !isset($this->resolveEnabledModules()[$module])) {
            return [];
        }

        return $this->discoverWidgetsForMeta($module, $meta);
    }

    private function loadState(): void
    {
        $cacheKey = 'modules_state';
        $cache = CacheManager::instance();
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            $this->state = $cached;
            return;
        }

        if (!file_exists($this->statePath)) {
            $this->state = [];
            return;
        }

        $handle = @fopen($this->statePath, 'r');
        if (!$handle) {
            $this->state = [];
            return;
        }

        flock($handle, LOCK_SH);
        $content = stream_get_contents($handle);
        fclose($handle);

        $data = json_decode($content, true);
        $this->state = is_array($data) ? $data : [];

        if (!empty($this->state)) {
            $cache->set($cacheKey, $this->state, 60);
        }
    }

    private function loadModules(): void
    {
        foreach ($this->modulesPaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            $location = strtolower(basename($basePath)) === 'extensions' ? 'extension' : 'module';

            foreach (glob($basePath . '/*', GLOB_ONLYDIR) as $dir) {
                $name = basename($dir);
                if (isset($this->modules[$name])) {
                    continue;
                }
                $meta = $this->loadManifest($dir, $name, $location);
                $meta = $this->normalizeManifest($meta, $name, $dir, $location);
                $meta['dependencies'] = $this->normalizeDeps($meta['dependencies'] ?? []);
                $meta['required'] = (bool)($meta['required'] ?? false);
                $meta['enabled'] = $this->resolveEnabledFlag($name, $meta);
                $meta['sidebar_visible'] = $this->resolveSidebarVisibleFlag($name, $meta);
                $meta['sidebar_manageable'] = $this->resolveSidebarManageableFlag($name, $meta);
                $meta['location'] = $location;
                $meta['path'] = $dir;

                if ($meta['required']) {
                    $meta['enabled'] = true;
                }

                $this->modules[$name] = $meta;
            }
        }

        $this->finalizeLifecycleMetadata();
    }

    private function loadManifest(string $dir, string $name, string $location): array
    {
        [$manifestPath, $manifestName, $manifestStatus] = $this->resolveManifestContract($dir, $location);
        if ($manifestStatus !== 'ok' || $manifestPath === '') {
            $fallback = $this->defaultManifest($name, $location);
            $fallback['manifest_path'] = $manifestPath;
            $fallback['manifest_name'] = $manifestName;
            $fallback['manifest_status'] = $manifestStatus;
            return $fallback;
        }

        $content = file_get_contents($manifestPath);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            $fallback = $this->defaultManifest($name, $location);
            $fallback['manifest_path'] = $manifestPath;
            $fallback['manifest_name'] = $manifestName;
            $fallback['manifest_status'] = 'invalid';
            return $fallback;
        }

        $data['manifest_path'] = $manifestPath;
        $data['manifest_name'] = $manifestName;
        $data['manifest_status'] = 'ok';

        return $data;
    }

    private function resolveManifestContract(string $dir, string $location): array
    {
        $candidates = $location === 'extension'
            ? [$dir . '/extension.json']
            : [$dir . '/module.json'];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return [$candidate, basename($candidate), 'ok'];
            }
        }

        return ['', basename($candidates[0]), 'missing'];
    }

    private function defaultManifest(string $name, string $location): array
    {
        return [
            'name' => $name,
            'version' => '1.0.0',
            'description' => '',
            'dependencies' => [],
            'enabled' => true,
            'type' => $location === 'extension' ? 'extension' : 'module',
            'tier' => $location === 'extension' ? 'extension' : 'standard',
            'official' => $location !== 'extension',
            'manifest_name' => $location === 'extension' ? 'extension.json' : 'module.json',
            'manifest_path' => '',
            'routes' => 'Config/routes.php',
            'hooks' => 'Hooks/listeners.php',
            'hook_definitions' => 'Hooks/hooks.php',
            'widgets_path' => 'Widgets',
            'assets' => 'Assets',
            'sidebar_manageable' => true,
            'license' => [
                'required' => false,
                'gate' => 'authoring',
                'subject' => $name,
                'revealable' => false,
            ],
        ];
    }

    private function normalizeManifest(array $meta, string $name, string $dir, string $location): array
    {
        $normalized = $meta;
        $normalized['name'] = trim((string) ($normalized['name'] ?? '')) !== '' ? (string) $normalized['name'] : $name;
        $normalized['key'] = $this->normalizeKey((string) ($normalized['key'] ?? $normalized['name'] ?? $name));
        $normalized['type'] = $this->normalizeStringOrDefault($normalized['type'] ?? null, $location === 'extension' ? 'extension' : 'module');
        $normalized['tier'] = $this->normalizeStringOrDefault($normalized['tier'] ?? null, $location === 'extension' ? 'extension' : 'standard');
        $normalized['official'] = $this->resolveOfficialFlag($normalized, $location);
        $normalized['routes_declared'] = array_key_exists('routes', $meta);
        $normalized['hooks_declared'] = array_key_exists('hooks', $meta);
        $normalized['hook_definitions_declared'] = array_key_exists('hook_definitions', $meta);
        $normalized['widgets_declared'] = array_key_exists('widgets_path', $meta);
        $normalized['license_declared'] = array_key_exists('license', $meta);
        $normalized['routes'] = $this->normalizeRelativePath($normalized['routes'] ?? null, 'Config/routes.php');
        $normalized['hooks'] = $this->normalizeRelativePath($normalized['hooks'] ?? null, 'Hooks/listeners.php');
        $normalized['hook_definitions'] = $this->normalizeRelativePath($normalized['hook_definitions'] ?? null, 'Hooks/hooks.php');
        $normalized['widgets_path'] = $this->normalizeRelativePath($normalized['widgets_path'] ?? null, 'Widgets');
        $normalized['assets_declared'] = array_key_exists('assets', $meta);
        $normalized['public_assets_base_declared'] = array_key_exists('public_assets_base', $meta);
        $normalized['public_assets_key_declared'] = array_key_exists('public_assets_key', $meta);
        $normalized['assets'] = $this->normalizeRelativePath($normalized['assets'] ?? null, 'Assets');
        $normalized['manifest_name'] = trim((string) ($normalized['manifest_name'] ?? ''));
        $normalized['manifest_path'] = trim((string) ($normalized['manifest_path'] ?? ''));
        $normalized['manifest_status'] = $this->normalizeManifestStatus($normalized['manifest_status'] ?? null);
        $normalized['manifest_exists'] = $normalized['manifest_path'] !== '' && is_file($normalized['manifest_path']);
        $normalized['base_path'] = $dir;
        [$normalized['routes_path'], $normalized['routes_status']] = $this->resolveRuntimeFilePath($dir, $normalized['routes']);
        [$normalized['hooks_path'], $normalized['hooks_status']] = $this->resolveRuntimeFilePath($dir, $normalized['hooks']);
        [$normalized['hook_definitions_path'], $normalized['hook_definitions_status']] = $this->resolveRuntimeFilePath($dir, $normalized['hook_definitions']);
        [$normalized['widgets_root_path'], $normalized['widgets_status']] = $this->resolveRuntimeDirectoryPath($dir, $normalized['widgets_path']);
        [$normalized['assets_path'], $normalized['assets_status']] = $this->resolveRuntimeDirectoryPath($dir, $normalized['assets']);
        $normalized['public_assets_key'] = $this->normalizePublicAssetsKey((string) ($normalized['public_assets_key'] ?? $normalized['key']));
        $normalized['public_assets_base'] = $this->normalizePublicAssetsBase($normalized['public_assets_base'] ?? null, $location);
        $normalized['public_assets_path'] = $this->resolvePublicAssetsPath($normalized['public_assets_base'], $normalized['public_assets_key']);
        $normalized['license'] = $this->normalizeLicenseContract(
            $normalized['license'] ?? null,
            $normalized['tier'],
            $normalized['key'],
            $normalized['name'],
            $location
        );

        return $normalized;
    }

    private function normalizeManifestStatus(mixed $value): string
    {
        $status = strtolower(trim((string) $value));

        return in_array($status, ['ok', 'missing', 'invalid'], true) ? $status : 'missing';
    }

    private function normalizeStringOrDefault(mixed $value, string $default): string
    {
        $candidate = trim((string) $value);
        return $candidate !== '' ? $candidate : $default;
    }

    private function normalizeRelativePath(mixed $value, string $default): string
    {
        $candidate = trim(str_replace('\\', '/', (string) $value));
        if ($candidate === '') {
            return $default;
        }

        return ltrim($candidate, '/');
    }

    private function resolveOfficialFlag(array $meta, string $location): bool
    {
        if (array_key_exists('official', $meta)) {
            return (bool) $meta['official'];
        }

        $vendor = strtolower(trim((string) ($meta['vendor'] ?? $meta['author'] ?? '')));
        $origin = strtolower(trim((string) ($meta['origin'] ?? $meta['channel'] ?? '')));
        if ($vendor === 'flatcms' || in_array($origin, ['flatcms', 'official', 'core'], true)) {
            return true;
        }

        return $location !== 'extension';
    }

    private function normalizeKey(string $value): string
    {
        $value = preg_replace('/(?<!^)([A-Z])/', '-$1', $value) ?? $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
        $value = trim($value, '-');

        return $value !== '' ? $value : 'extension';
    }

    private function normalizePublicAssetsKey(string $value): string
    {
        return $this->normalizeKey($value);
    }

    private function normalizePublicAssetsBase(mixed $value, string $location): string
    {
        $default = $location === 'extension' ? 'assets/extensions' : 'modules';
        $candidate = trim(str_replace('\\', '/', (string) $value), '/');
        if ($candidate === '') {
            return $default;
        }

        $segments = explode('/', $candidate);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return $default;
            }
        }

        return $candidate;
    }

    private function resolveRuntimeFilePath(string $baseDir, string $relative): array
    {
        if ($relative === '') {
            return ['', 'absent'];
        }

        if (!$this->isSafeRelativePath($relative)) {
            return ['', 'invalid'];
        }

        $candidate = $baseDir . '/' . ltrim($relative, '/');
        if (!is_file($candidate)) {
            return ['', 'missing'];
        }

        return [$candidate, 'ok'];
    }

    private function resolveRuntimeDirectoryPath(string $baseDir, string $relative): array
    {
        if ($relative === '') {
            return ['', 'absent'];
        }

        if (!$this->isSafeRelativePath($relative)) {
            return ['', 'invalid'];
        }

        $candidate = $baseDir . '/' . ltrim($relative, '/');
        if (!is_dir($candidate)) {
            return ['', 'missing'];
        }

        return [$candidate, 'ok'];
    }

    private function isSafeRelativePath(string $relative): bool
    {
        $relative = str_replace('\\', '/', $relative);
        if ($relative === '' || str_starts_with($relative, '/')) {
            return false;
        }

        $segments = explode('/', $relative);
        foreach ($segments as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function resolvePublicAssetsPath(string $base, string $key): string
    {
        if (!defined('PUBLIC_PATH') || trim((string) PUBLIC_PATH) === '') {
            return '';
        }

        return rtrim((string) PUBLIC_PATH, '/') . '/' . trim($base, '/') . '/' . trim($key, '/');
    }

    private function resolveEnabledFlag(string $name, array $meta): bool
    {
        if (isset($this->state[$name]['enabled'])) {
            return (bool)$this->state[$name]['enabled'];
        }

        return (bool)($meta['enabled'] ?? true);
    }

    private function resolveSidebarVisibleFlag(string $name, array $meta): bool
    {
        if (isset($this->state[$name]['sidebar_visible'])) {
            return (bool) $this->state[$name]['sidebar_visible'];
        }

        return (bool) ($meta['sidebar_visible'] ?? true);
    }

    private function resolveSidebarManageableFlag(string $name, array $meta): bool
    {
        if (isset(self::NON_MANAGEABLE_SIDEBAR_MODULES[$name])) {
            return false;
        }

        return (bool) ($meta['sidebar_manageable'] ?? true);
    }

    private function normalizeDeps(array $deps): array
    {
        $clean = [];
        foreach ($deps as $dep) {
            if (!is_string($dep) || $dep === '') {
                continue;
            }
            $clean[] = $dep;
        }

        return array_values(array_unique($clean));
    }

    private function discoverWidgetsForMeta(string $module, array $meta): array
    {
        $widgetsRoot = trim((string) ($meta['widgets_root_path'] ?? ''));
        if ($widgetsRoot === '') {
            return [];
        }

        $widgets = [];
        foreach (glob($widgetsRoot . '/*', GLOB_ONLYDIR) ?: [] as $widgetDir) {
            $widget = $this->buildWidgetContract($module, $meta, $widgetDir);
            if (!is_array($widget)) {
                continue;
            }

            $widgets[$widget['id']] = $widget;
        }

        return array_values($widgets);
    }

    private function buildWidgetContract(string $module, array $meta, string $widgetDir): ?array
    {
        $widgetName = basename($widgetDir);
        $widgetKey = $this->normalizeKey($widgetName);
        if ($widgetKey === '') {
            return null;
        }

        $files = [
            'widget_php' => $this->resolveWidgetFileContract($widgetDir, 'widget.php'),
            'render_php' => $this->resolveWidgetFileContract($widgetDir, 'render.php'),
            'preview_js' => $this->resolveWidgetFileContract($widgetDir, 'preview.js'),
        ];

        $status = 'ok';
        if (($files['widget_php']['status'] ?? 'missing') !== 'ok' || ($files['render_php']['status'] ?? 'missing') !== 'ok') {
            $status = 'incomplete';
        } elseif (($files['preview_js']['status'] ?? 'missing') !== 'ok') {
            $status = 'partial';
        }

        return [
            'id' => $module . '.' . $widgetName,
            'module' => $module,
            'module_key' => (string) ($meta['key'] ?? $this->normalizeKey($module)),
            'module_type' => (string) ($meta['type'] ?? 'module'),
            'module_tier' => (string) ($meta['tier'] ?? 'standard'),
            'module_location' => (string) ($meta['location'] ?? 'module'),
            'module_enabled' => (bool) ($meta['enabled'] ?? false),
            'name' => $widgetName,
            'key' => $widgetKey,
            'path' => $widgetDir,
            'relative_path' => rtrim((string) ($meta['widgets_path'] ?? 'Widgets'), '/') . '/' . $widgetName,
            'status' => $status,
            'files' => $files,
        ];
    }

    private function resolveWidgetFileContract(string $widgetDir, string $filename): array
    {
        $path = rtrim($widgetDir, '/') . '/' . $filename;

        return [
            'path' => is_file($path) ? $path : '',
            'status' => is_file($path) ? 'ok' : 'missing',
        ];
    }

    private function finalizeLifecycleMetadata(): void
    {
        $this->resolvedEnabled = null;

        foreach ($this->modules as $name => $meta) {
            $this->modules[$name]['integrity_issues'] = $this->collectIntegrityIssues($meta);
            $this->modules[$name]['integrity_valid'] = $this->modules[$name]['integrity_issues'] === [];
            $this->modules[$name]['desired_enabled'] = (bool) ($meta['enabled'] ?? false) || !empty($meta['required']);
        }

        $resolved = $this->resolveEnabledModules();

        foreach ($this->modules as $name => $meta) {
            $dependencyIssues = $this->collectDependencyIssues($name);
            $lifecycleStatus = $this->resolveLifecycleStatus($meta, $dependencyIssues, isset($resolved[$name]));
            $lifecycleReasons = $this->buildLifecycleReasons($meta, $dependencyIssues, $lifecycleStatus);

            $this->modules[$name]['resolved_enabled'] = isset($resolved[$name]);
            $this->modules[$name]['dependency_issues'] = $dependencyIssues;
            $this->modules[$name]['lifecycle_status'] = $lifecycleStatus;
            $this->modules[$name]['lifecycle_reasons'] = $lifecycleReasons;
            $this->modules[$name]['lifecycle_reason'] = $lifecycleReasons[0] ?? '';
        }
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<int, string>
     */
    private function collectIntegrityIssues(array $meta): array
    {
        $issues = [];
        $manifestStatus = (string) ($meta['manifest_status'] ?? 'missing');
        $location = (string) ($meta['location'] ?? 'module');

        if ($manifestStatus === 'invalid') {
            $issues[] = 'manifest_invalid';
        } elseif ($manifestStatus === 'missing' && $location === 'extension') {
            $issues[] = 'manifest_missing';
        }

        $declaredPathKinds = [
            'routes' => (bool) ($meta['routes_declared'] ?? false),
            'hooks' => (bool) ($meta['hooks_declared'] ?? false),
            'hook_definitions' => (bool) ($meta['hook_definitions_declared'] ?? false),
            'widgets' => (bool) ($meta['widgets_declared'] ?? false),
            'assets' => (bool) ($meta['assets_declared'] ?? false),
        ];

        foreach ($declaredPathKinds as $kind => $declared) {
            if (!$declared) {
                continue;
            }

            $statusKey = $kind === 'widgets' ? 'widgets_status' : $kind . '_status';
            $status = (string) ($meta[$statusKey] ?? 'absent');
            if ($status === 'missing' || $status === 'invalid') {
                $issues[] = $kind . '_' . $status;
            }
        }

        return array_values(array_unique($issues));
    }

    /**
     * @return array<string, string>
     */
    private function collectDependencyIssues(string $module, array $stack = []): array
    {
        $meta = $this->modules[$module] ?? null;
        if (!is_array($meta)) {
            return [];
        }

        $desiredEnabled = (bool) ($meta['desired_enabled'] ?? false);
        if (!$desiredEnabled) {
            return [];
        }

        $stack[] = $module;
        $issues = [];

        foreach ($meta['dependencies'] ?? [] as $dependency) {
            $dependency = (string) $dependency;
            if ($dependency === '') {
                continue;
            }

            if (!isset($this->modules[$dependency])) {
                $issues[$dependency] = 'missing';
                continue;
            }

            $dependencyMeta = $this->modules[$dependency];
            if (!($dependencyMeta['integrity_valid'] ?? true)) {
                $issues[$dependency] = 'invalid';
                continue;
            }

            $dependencyDesiredEnabled = (bool) ($dependencyMeta['desired_enabled'] ?? false);
            if (!$dependencyDesiredEnabled) {
                $issues[$dependency] = 'disabled';
                continue;
            }

            if (in_array($dependency, $stack, true)) {
                $issues[$dependency] = 'cycle';
                continue;
            }

            $nestedIssues = $this->collectDependencyIssues($dependency, $stack);
            if ($nestedIssues !== []) {
                $issues[$dependency] = 'blocked';
            }
        }

        return $issues;
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, string> $dependencyIssues
     */
    private function resolveLifecycleStatus(array $meta, array $dependencyIssues, bool $resolvedEnabled): string
    {
        if (!(bool) ($meta['integrity_valid'] ?? true)) {
            return 'invalid';
        }

        if (!((bool) ($meta['desired_enabled'] ?? false))) {
            return 'disabled';
        }

        if ($dependencyIssues !== []) {
            return 'missing_dependencies';
        }

        return $resolvedEnabled ? 'enabled' : 'disabled';
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, string> $dependencyIssues
     * @return array<int, string>
     */
    private function buildLifecycleReasons(array $meta, array $dependencyIssues, string $lifecycleStatus): array
    {
        $reasons = [];

        if ($lifecycleStatus === 'invalid') {
            foreach ($meta['integrity_issues'] ?? [] as $issue) {
                if (is_string($issue) && $issue !== '') {
                    $reasons[] = $issue;
                }
            }
        }

        if ($lifecycleStatus === 'missing_dependencies') {
            foreach ($dependencyIssues as $dependency => $issue) {
                $reasons[] = 'dependency:' . $dependency . ':' . $issue;
            }
        }

        return $reasons;
    }

    private function normalizeLicenseContract(
        mixed $value,
        string $tier,
        string $key,
        string $name,
        string $location
    ): array {
        $contract = is_array($value) ? $value : [];
        $requiredByDefault = $location === 'extension' && $tier === 'premium';
        $required = array_key_exists('required', $contract) ? (bool) $contract['required'] : $requiredByDefault;
        $gate = strtolower(trim((string) ($contract['gate'] ?? 'authoring')));
        if (!in_array($gate, ['authoring'], true)) {
            $gate = 'authoring';
        }

        $subject = trim((string) ($contract['subject'] ?? ''));
        if ($subject === '') {
            $subject = $key !== '' ? $key : $name;
        }

        $revealable = array_key_exists('revealable', $contract)
            ? (bool) $contract['revealable']
            : $required;

        return [
            'required' => $required,
            'gate' => $gate,
            'subject' => $subject,
            'revealable' => $revealable,
        ];
    }

    private function resolveEnabledModules(): array
    {
        if ($this->resolvedEnabled !== null) {
            return $this->resolvedEnabled;
        }

        $resolved = [];
        foreach (array_keys($this->modules) as $module) {
            $this->resolveModule($module, $resolved, []);
        }

        $this->resolvedEnabled = $resolved;
        return $resolved;
    }

    private function resolveModule(string $module, array &$resolved, array $stack): bool
    {
        if (isset($resolved[$module])) {
            return true;
        }

        $meta = $this->modules[$module] ?? null;
        if (!$meta) {
            return false;
        }

        if (!($meta['enabled'] ?? false) && !($meta['required'] ?? false)) {
            return false;
        }

        if (!(bool) ($meta['integrity_valid'] ?? true)) {
            return false;
        }

        if (in_array($module, $stack, true)) {
            return false;
        }

        $stack[] = $module;

        foreach ($meta['dependencies'] as $dep) {
            if (!isset($this->modules[$dep])) {
                return false;
            }
            if (!$this->resolveModule($dep, $resolved, $stack)) {
                return false;
            }
        }

        $resolved[$module] = $meta;
        return true;
    }
}
