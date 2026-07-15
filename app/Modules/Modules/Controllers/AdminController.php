<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Modules\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Core\ModuleManager;

class AdminController extends BaseController
{
    private string $modulesPath;
    private string $extensionsPath;
    private string $statePath;
    private string $publicModulesPath;
    private string $tmpPath;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Modules');
        $this->modulesPath = BASE_PATH . '/app/Modules';
        $this->extensionsPath = BASE_PATH . '/app/Extensions';
        $this->statePath = BASE_PATH . '/data/modules.json';
        $this->publicModulesPath = BASE_PATH . '/public/modules';
        $this->tmpPath = BASE_PATH . '/storage/tmp/extensions';
    }

    public function index(): void
    {
        if (!$this->authorize('modules.view')) {
            return;
        }

        $manager = new ModuleManager([$this->modulesPath, $this->extensionsPath], $this->statePath);
        $modules = $manager->all();
        $enabled = $manager->enabled();
        $lockedModules = $this->resolveLockedModules($enabled);
        $moduleEntries = [];
        foreach ($modules as $name => $meta) {
            if (!is_array($meta)) {
                continue;
            }
            $moduleEntries[$name] = $meta;
        }

        $this->sortEntries($moduleEntries);

        $allowedStatuses = ['enabled', 'all', 'disabled', 'required'];
        $initialStatusFilter = strtolower((string) $this->request->input('status', 'enabled'));
        if (!in_array($initialStatusFilter, $allowedStatuses, true)) {
            $initialStatusFilter = 'enabled';
        }

        $moduleIndex = $moduleEntries;
        $autoDeleteModuleName = $this->resolveModuleIndexName($moduleIndex, (string) $this->request->input('prompt_delete', ''));
        $autoOpenModuleName = $this->resolveModuleIndexName($moduleIndex, (string) $this->request->input('installed', ''));

        $this->render('Modules/Views/admin/index', [
            'pageTitle' => __('modules_list', 'Modules'),
            'modulesList' => $moduleEntries,
            'enabledModules' => $enabled,
            'lockedModules' => $lockedModules,
            'initialStatusFilter' => $initialStatusFilter,
            'autoDeleteModuleName' => $autoDeleteModuleName,
            'autoOpenModuleName' => $autoOpenModuleName,
        ], 'admin.main');
    }

    public function toggle(string $name): void
    {
        if (!$this->authorize('modules.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $manager = new ModuleManager([$this->modulesPath, $this->extensionsPath], $this->statePath);
        $modules = $manager->all();

        if (!isset($modules[$name])) {
            $this->session->flash('error', __('module_not_found', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $meta = $modules[$name];
        if (!empty($meta['required'])) {
            $this->session->flash('error', __('module_required_error', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $state = $this->readState();
        $isEnabled = $manager->isEnabled($name);
        $lockedModules = $this->resolveLockedModules($manager->enabled());
        if (isset($lockedModules[$name])) {
            $lockedBy = $lockedModules[$name];
            $lockedKey = 'module_name_' . strtolower($lockedBy);
            $lockedLabel = I18n::has($lockedKey, 'Modules') ? __($lockedKey, 'Modules') : $lockedBy;
            $this->session->flash('error', __('module_locked_by', 'Modules', [
                'module' => $lockedLabel
            ]));
            $this->redirect(url('/admin/modules'));
            return;
        }

        if (($meta['lifecycle_status'] ?? '') === 'invalid') {
            $this->session->flash('error', __('module_invalid_state', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        if ($isEnabled) {
            $dependents = $this->getEnabledDependentsRecursive($name, $modules, $manager);
            $blockingDependents = [];

            foreach ($dependents as $dependentName) {
                $dependentMeta = $modules[$dependentName] ?? null;
                if (!is_array($dependentMeta)) {
                    continue;
                }
                if (!empty($dependentMeta['required'])) {
                    $blockingDependents[] = $this->resolveModuleLabel($dependentName);
                }
            }

            if (!empty($blockingDependents)) {
                $this->session->flash('error', __('module_has_dependents', 'Modules', [
                    'modules' => implode(', ', $blockingDependents)
                ]));
                $this->redirect(url('/admin/modules'));
                return;
            }

            foreach ($dependents as $dependentName) {
                $dependentMeta = $modules[$dependentName] ?? null;
                if (!is_array($dependentMeta)) {
                    continue;
                }

                hook_run('modules.before_disable', $dependentMeta);
                $this->setStateFlag($state, $dependentName, 'enabled', false);
                hook_run('modules.after_disable', $dependentMeta);
            }

            hook_run('modules.before_disable', $meta);
            $this->setStateFlag($state, $name, 'enabled', false);
            $this->writeState($state);
            hook_run('modules.after_disable', $meta);
            $this->session->flash('success', __('module_disabled_success', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $missing = $this->getUnavailableDependencyLabels($meta, $modules);
        if (!empty($missing)) {
            $this->session->flash('error', __('module_dependencies_missing', 'Modules', [
                'modules' => implode(', ', $missing)
            ]));
            $this->redirect(url('/admin/modules'));
            return;
        }

        foreach ($meta['dependencies'] ?? [] as $dep) {
            $this->setStateFlag($state, $dep, 'enabled', true);
        }

        hook_run('modules.before_enable', $meta);
        $this->setStateFlag($state, $name, 'enabled', true);
        $this->writeState($state);
        hook_run('modules.after_enable', $meta);
        $this->session->flash('success', __('module_enabled_success', 'Modules'));
        $this->redirect(url('/admin/modules'));
    }

    public function toggleSidebar(string $name): void
    {
        if (!$this->authorize('modules.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $manager = new ModuleManager([$this->modulesPath, $this->extensionsPath], $this->statePath);
        $modules = $manager->all();

        if (!isset($modules[$name])) {
            $this->session->flash('error', __('module_not_found', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $meta = $modules[$name];
        if (!$manager->isSidebarManageable($name)) {
            $this->session->flash('error', __('module_sidebar_not_manageable_error', 'Modules', [
                'module' => $this->resolveModuleLabel($name),
            ]));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $sidebarVisible = (bool) ($meta['sidebar_visible'] ?? true);
        $state = $this->readState();
        $this->setStateFlag($state, $name, 'sidebar_visible', !$sidebarVisible);
        $this->writeState($state);

        $flashKey = $sidebarVisible
            ? 'module_sidebar_hidden_success'
            : 'module_sidebar_visible_success';

        $this->session->flash('success', __($flashKey, 'Modules', [
            'module' => $this->resolveModuleLabel($name),
        ]));
        $this->redirect(url('/admin/modules'));
    }

    private function resolveLockedModules(array $enabled): array
    {
        return [];
    }

    public function install(): void
    {
        if (!$this->authorize('modules.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        if (!class_exists(\ZipArchive::class)) {
            $this->session->flash('error', __('extensions_zip_missing', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $file = $_FILES['extension_zip'] ?? null;
        if (!is_array($file) || empty($file['tmp_name'])) {
            $this->session->flash('error', __('extensions_no_file', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->session->flash('error', __('extensions_upload_failed', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension !== 'zip') {
            $this->session->flash('error', __('extensions_invalid_format', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $zipPath = '';
        $extractDir = '';

        try {
            if (!is_dir($this->tmpPath) && !mkdir($this->tmpPath, 0755, true) && !is_dir($this->tmpPath)) {
                throw new \RuntimeException('Unable to create temp directory for extension install.');
            }

            $zipPath = $this->tmpPath . '/' . uniqid('extension_', true) . '.zip';
            if (!move_uploaded_file($file['tmp_name'], $zipPath)) {
                $this->session->flash('error', __('extensions_upload_failed', 'Modules'));
                $this->redirect(url('/admin/modules'));
                return;
            }

            $extractDir = $this->tmpPath . '/' . uniqid('extension_', true);
            if (!is_dir($extractDir) && !mkdir($extractDir, 0755, true) && !is_dir($extractDir)) {
                throw new \RuntimeException('Unable to create extraction directory.');
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('extensions_zip_open_failed', 'Modules'));
                $this->redirect(url('/admin/modules'));
                return;
            }

            if (!$this->validateZipEntries($zip)) {
                $zip->close();
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('extensions_zip_invalid', 'Modules'));
                $this->redirect(url('/admin/modules'));
                return;
            }

            if (!$zip->extractTo($extractDir)) {
                $zip->close();
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('extensions_zip_extract_failed', 'Modules'));
                $this->redirect(url('/admin/modules'));
                return;
            }
            $zip->close();

            $manifestFiles = $this->findModuleManifests($extractDir);
            if ($manifestFiles === []) {
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('extensions_manifest_missing', 'Modules'));
                $this->redirect(url('/admin/modules'));
                return;
            }

            if (count($manifestFiles) !== 1) {
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('extensions_manifest_invalid', 'Modules'));
                $this->redirect(url('/admin/modules'));
                return;
            }

            $manifestFile = $manifestFiles[0];
            if ($manifestFile) {
                $manifest = $this->readManifest($manifestFile);
                $packageKind = $this->resolvePackageKind($manifestFile);
                if ($packageKind === '') {
                    $this->cleanupInstall($zipPath, $extractDir);
                    $this->session->flash('error', __('extensions_manifest_invalid', 'Modules'));
                    $this->redirect(url('/admin/modules'));
                    return;
                }

                $moduleDir = dirname($manifestFile);
                $moduleName = basename($moduleDir);
                if ($moduleDir === $extractDir) {
                    $moduleName = $this->resolveModuleName($manifest);
                }
                $moduleName = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $moduleName);
                if ($moduleName === '') {
                    $this->cleanupInstall($zipPath, $extractDir);
                    $this->session->flash('error', __('extensions_manifest_invalid', 'Modules'));
                    $this->redirect(url('/admin/modules'));
                    return;
                }

                if ($this->hasUnsafeManifestPaths($manifest)) {
                    $this->cleanupInstall($zipPath, $extractDir);
                    $this->session->flash('error', __('extensions_manifest_invalid', 'Modules'));
                    $this->redirect(url('/admin/modules'));
                    return;
                }

                if ($this->hasExternalFiles($extractDir, $moduleDir) || $this->containsSymlinks($moduleDir)) {
                    $this->cleanupInstall($zipPath, $extractDir);
                    $this->session->flash('error', __('extensions_zip_outside_scope', 'Modules'));
                    $this->redirect(url('/admin/modules'));
                    return;
                }

                if ($packageKind === 'extension' && $this->hasInvalidOfficialOrigin($manifest)) {
                    $this->cleanupInstall($zipPath, $extractDir);
                    $this->session->flash('error', __('extensions_manifest_official', 'Modules'));
                    $this->redirect(url('/admin/modules'));
                    return;
                }

                if ($this->hasInvalidNamespaceBoundary($moduleDir, $manifest, $packageKind, $moduleName)) {
                    $this->cleanupInstall($zipPath, $extractDir);
                    $this->session->flash('error', __('extensions_manifest_invalid', 'Modules'));
                    $this->redirect(url('/admin/modules'));
                    return;
                }

                if ($this->hasUnexpectedUnsignedSignature($manifest, $packageKind)) {
                    $this->cleanupInstall($zipPath, $extractDir);
                    $this->session->flash('error', __('extensions_signature_invalid', 'Modules'));
                    $this->redirect(url('/admin/modules'));
                    return;
                }

                $requiresSignature = $this->requiresOfficialSignature($manifest, $packageKind);
                if ($requiresSignature) {
                    $signature = trim((string) ($manifest['signature'] ?? ''));
                    if ($signature === '') {
                        $this->cleanupInstall($zipPath, $extractDir);
                        $this->session->flash('error', __('extensions_manifest_official', 'Modules'));
                        $this->redirect(url('/admin/modules'));
                        return;
                    }

                    if (!extension_loaded('openssl')) {
                        $this->cleanupInstall($zipPath, $extractDir);
                        $this->session->flash('error', __('extensions_openssl_missing', 'Modules'));
                        $this->redirect(url('/admin/modules'));
                        return;
                    }

                    $publicKey = trim((string) config('extensions.official_public_key', ''));
                    if ($publicKey === '') {
                        $this->cleanupInstall($zipPath, $extractDir);
                        $this->session->flash('error', __('extensions_public_key_missing', 'Modules'));
                        $this->redirect(url('/admin/modules'));
                        return;
                    }

                    if (!$this->verifyManifestSignature($manifest, $publicKey)) {
                        $this->cleanupInstall($zipPath, $extractDir);
                        $this->session->flash('error', __('extensions_signature_invalid', 'Modules'));
                        $this->redirect(url('/admin/modules'));
                        return;
                    }

                    if (!$this->verifyManifestFileIntegrity($manifest, $moduleDir, $manifestFile)) {
                        $this->cleanupInstall($zipPath, $extractDir);
                        $this->session->flash('error', __('extensions_signature_invalid', 'Modules'));
                        $this->redirect(url('/admin/modules'));
                        return;
                    }
                }

                $targetBase = $packageKind === 'extension' ? $this->extensionsPath : $this->modulesPath;

                if (!is_dir($targetBase) && !mkdir($targetBase, 0755, true) && !is_dir($targetBase)) {
                    throw new \RuntimeException('Unable to create target module directory.');
                }

                $destination = $targetBase . '/' . $moduleName;
                if (file_exists($destination)) {
                    $this->cleanupInstall($zipPath, $extractDir);
                    $this->session->flash('error', __('extensions_exists', 'Modules', ['module' => $moduleName]));
                    $this->redirect(url('/admin/modules'));
                    return;
                }

                if (!$this->copyDirectory($moduleDir, $destination)) {
                    $this->cleanupInstall($zipPath, $extractDir);
                    $this->session->flash('error', __('extensions_copy_failed', 'Modules'));
                    $this->redirect(url('/admin/modules'));
                    return;
                }

                if (!$this->createAssetsSymlink($moduleName, $destination)) {
                    $this->removeDirectory($destination);
                    $this->cleanupInstall($zipPath, $extractDir);
                    $this->session->flash('error', __('extensions_copy_failed', 'Modules'));
                    $this->redirect(url('/admin/modules'));
                    return;
                }

                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('success', __('extensions_install_success', 'Modules', ['module' => $moduleName]));
                $this->redirect(url('/admin/modules?status=disabled&installed=' . rawurlencode($moduleName)));
                return;
            }

            if (!$manifestFile) {
                $this->cleanupInstall($zipPath, $extractDir);
                $this->session->flash('error', __('extensions_manifest_missing', 'Modules'));
                $this->redirect(url('/admin/modules'));
                return;
            }
        } catch (\Throwable $e) {
            $this->cleanupInstall($zipPath, $extractDir);
            $this->logInstallException($e);
            $this->session->flash('error', __('extensions_copy_failed', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }
    }

    public function delete(string $name): void
    {
        if (!$this->authorize('modules.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $manager = new ModuleManager([$this->modulesPath, $this->extensionsPath], $this->statePath);
        $modules = $manager->all();

        if (!isset($modules[$name])) {
            $this->session->flash('error', __('module_not_found', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $meta = $modules[$name];
        if (!empty($meta['required'])) {
            $this->session->flash('error', __('module_required_error', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        if ($manager->isEnabled($name)) {
            $this->session->flash('error', __('module_disable_first', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $dependents = $this->getDependents($name, $modules);
        if (!empty($dependents)) {
            $this->session->flash('error', __('module_has_dependents', 'Modules', [
                'modules' => implode(', ', $dependents)
            ]));
            $this->redirect(url('/admin/modules'));
            return;
        }

        $path = $meta['path'] ?? '';
        if ($path === '' || !$this->isAllowedModulePath($path)) {
            $this->session->flash('error', __('module_delete_failed', 'Modules'));
            $this->redirect(url('/admin/modules'));
            return;
        }

        hook_run('modules.before_delete', $meta);
        $this->removeModuleTranslations($name);
        $this->removeDirectory($path);
        $this->removeAssetsLink($name);

        $state = $this->readState();
        unset($state[$name]);
        $this->writeState($state);

        hook_run('modules.after_delete', $meta);
        $this->session->flash('success', __('module_deleted_success', 'Modules', ['module' => $meta['name'] ?? $name]));
        $this->redirect(url('/admin/modules'));
    }

    private function readState(): array
    {
        if (!file_exists($this->statePath)) {
            return [];
        }

        $content = file_get_contents($this->statePath);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private function writeState(array $state): void
    {
        if (!is_dir(dirname($this->statePath))) {
            mkdir(dirname($this->statePath), 0755, true);
        }

        file_put_contents(
            $this->statePath,
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );

        \App\Core\CacheManager::instance()->forget('modules_state');
    }

    private function setStateFlag(array &$state, string $name, string $flag, bool $value): void
    {
        $entry = $state[$name] ?? [];
        if (!is_array($entry)) {
            $entry = [];
        }

        $entry[$flag] = $value;
        $state[$name] = $entry;
    }

    private function getEnabledDependents(string $module, array $modules, ModuleManager $manager): array
    {
        $dependents = [];
        foreach ($modules as $name => $meta) {
            if ($name === $module) {
                continue;
            }
            $deps = $meta['dependencies'] ?? [];
            if (in_array($module, $deps, true) && $manager->isEnabled($name)) {
                $dependents[] = $name;
            }
        }
        return $dependents;
    }

    private function resolveModuleIndexName(array $moduleIndex, string $requestedName): string
    {
        $requestedName = trim($requestedName);
        if ($requestedName === '') {
            return '';
        }

        foreach (array_keys($moduleIndex) as $moduleName) {
            if (strcasecmp((string) $moduleName, $requestedName) === 0) {
                return (string) $moduleName;
            }
        }

        return '';
    }

    private function getEnabledDependentsRecursive(string $module, array $modules, ModuleManager $manager): array
    {
        $dependents = [];
        $visited = [];

        $walk = function (string $target) use (&$walk, &$dependents, &$visited, $modules, $manager): void {
            foreach ($modules as $name => $meta) {
                if ($name === $target || isset($visited[$name])) {
                    continue;
                }
                $deps = $meta['dependencies'] ?? [];
                if (!in_array($target, $deps, true) || !$manager->isEnabled($name)) {
                    continue;
                }

                $visited[$name] = true;
                $walk($name);
                $dependents[] = $name;
            }
        };

        $walk($module);

        return $dependents;
    }

    private function getMissingDependencies(array $meta, array $modules): array
    {
        $missing = [];
        foreach ($meta['dependencies'] ?? [] as $dep) {
            if (!isset($modules[$dep])) {
                $missing[] = $dep;
            }
        }
        return $missing;
    }

    private function getUnavailableDependencyLabels(array $meta, array $modules): array
    {
        $issues = $meta['dependency_issues'] ?? [];
        if (!is_array($issues) || $issues === []) {
            return $this->getMissingDependencies($meta, $modules);
        }

        $labels = [];
        foreach (array_keys($issues) as $dependency) {
            $dependency = (string) $dependency;
            if ($dependency === '') {
                continue;
            }

            $labels[] = $this->resolveModuleLabel($dependency);
        }

        return array_values(array_unique($labels));
    }

    private function getDependents(string $module, array $modules): array
    {
        $dependents = [];
        foreach ($modules as $name => $meta) {
            if ($name === $module) {
                continue;
            }
            $deps = $meta['dependencies'] ?? [];
            if (in_array($module, $deps, true)) {
                $dependents[] = $name;
            }
        }
        return $dependents;
    }

    private function resolveModuleLabel(string $moduleName): string
    {
        $labelKey = 'module_name_' . strtolower($moduleName);
        if (I18n::has($labelKey, 'Modules')) {
            return __($labelKey, 'Modules');
        }

        return $moduleName;
    }

    /**
     * @param array<string, array<string, mixed>> $entries
     */
    private function sortEntries(array &$entries): void
    {
        uasort($entries, function ($a, $b) {
            $aRequired = !empty($a['required']);
            $bRequired = !empty($b['required']);
            if ($aRequired !== $bRequired) {
                return $aRequired ? -1 : 1;
            }

            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });
    }

    private function validateZipEntries(\ZipArchive $zip): bool
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false) {
                continue;
            }
            $entry = rtrim(str_replace('\\', '/', $entry), '/');
            if (!$this->isSafeRelativePackagePath($entry)) {
                return false;
            }
            if ($entry === 'app/Modules' || str_contains($entry, 'app/Modules/')
                || $entry === 'app/Extensions' || str_contains($entry, 'app/Extensions/')) {
                return false;
            }
        }
        return true;
    }

    private function hasExternalFiles(string $extractDir, string $moduleDir): bool
    {
        $extractReal = realpath($extractDir);
        $moduleReal = realpath($moduleDir);
        if ($extractReal === false || $moduleReal === false) {
            return true;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractReal, \RecursiveDirectoryIterator::SKIP_DOTS),
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
            if (!str_starts_with($real, $moduleReal)) {
                return true;
            }
        }

        return false;
    }

    private function containsSymlinks(string $moduleDir): bool
    {
        $moduleReal = realpath($moduleDir);
        if ($moduleReal === false) {
            return true;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($moduleReal, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isLink()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function findModuleManifests(string $basePath): array
    {
        $preferred = [
            'extension.json',
            'module.json',
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $matches = [];
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $filename = $file->getFilename();
            if (in_array($filename, $preferred, true)) {
                $matches[] = $file->getPathname();
            }
        }

        usort($matches, static function (string $left, string $right) use ($preferred): int {
            $leftRank = array_search(basename($left), $preferred, true);
            $rightRank = array_search(basename($right), $preferred, true);

            return ((int) $leftRank <=> (int) $rightRank) ?: strcmp($left, $right);
        });

        return $matches;
    }

    private function resolvePackageKind(string $manifestPath): string
    {
        return match (basename($manifestPath)) {
            'extension.json' => 'extension',
            'module.json' => 'module',
            default => '',
        };
    }

    private function readManifest(string $manifestPath): array
    {
        $content = file_get_contents($manifestPath);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private function resolveModuleName(array $manifest): string
    {
        $name = trim((string) ($manifest['name'] ?? ''));
        if ($name === '') {
            return '';
        }
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        return $name ?? '';
    }

    private function requiresOfficialSignature(array $manifest, string $packageKind = ''): bool
    {
        if ($packageKind === 'module') {
            return true;
        }

        $vendor = strtolower(trim((string) ($manifest['vendor'] ?? $manifest['author'] ?? '')));
        $origin = strtolower(trim((string) ($manifest['origin'] ?? '')));

        return (bool) ($manifest['official'] ?? false)
            || $vendor === 'flatcms'
            || in_array($origin, ['flatcms', 'official', 'core'], true);
    }

    private function hasUnexpectedUnsignedSignature(array $manifest, string $packageKind): bool
    {
        if ($this->requiresOfficialSignature($manifest, $packageKind)) {
            return false;
        }

        return trim((string) ($manifest['signature'] ?? '')) !== '';
    }

    private function hasInvalidOfficialOrigin(array $manifest): bool
    {
        $origin = strtolower(trim((string) ($manifest['origin'] ?? '')));
        if ($origin === '') {
            return false;
        }

        if (in_array($origin, ['flatcms', 'official', 'core'], true)) {
            return false;
        }

        $vendor = strtolower(trim((string) ($manifest['vendor'] ?? $manifest['author'] ?? '')));
        return (bool) ($manifest['official'] ?? false) || $vendor === 'flatcms';
    }

    private function verifyManifestSignature(array $manifest, string $publicKey): bool
    {
        $signature = (string) ($manifest['signature'] ?? '');
        if ($signature === '') {
            return false;
        }

        $payloadData = $manifest;
        unset($payloadData['signature']);
        $payloadData = $this->normalizeManifestData($payloadData);
        $payload = json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return false;
        }

        $decodedSignature = base64_decode($signature, true);
        if ($decodedSignature === false) {
            return false;
        }

        $algo = $this->getSignatureAlgo();
        return openssl_verify($payload, $decodedSignature, $publicKey, $algo) === 1;
    }

    private function verifyManifestFileIntegrity(array $manifest, string $moduleDir, string $manifestFile): bool
    {
        if (!array_key_exists('files', $manifest)) {
            return true;
        }

        $files = $manifest['files'];
        if (!is_array($files) || $files === []) {
            return false;
        }

        $moduleRoot = realpath($moduleDir);
        $manifestReal = realpath($manifestFile);
        if ($moduleRoot === false || $manifestReal === false) {
            return false;
        }

        $expectedPaths = [];
        foreach ($files as $relativePath => $expectedHash) {
            $relativePath = str_replace('\\', '/', (string) $relativePath);
            if (!$this->isSafeRelativePackagePath($relativePath) || !is_string($expectedHash)) {
                return false;
            }

            $target = realpath($moduleRoot . '/' . ltrim($relativePath, '/'));
            if ($target === false || !is_file($target) || !str_starts_with($target, $moduleRoot . DIRECTORY_SEPARATOR)) {
                return false;
            }

            $expected = strtolower(trim($expectedHash));
            if (str_starts_with($expected, 'sha256:')) {
                $expected = substr($expected, 7);
            }
            if (!preg_match('/^[a-f0-9]{64}$/', $expected)) {
                return false;
            }

            if (!hash_equals($expected, hash_file('sha256', $target) ?: '')) {
                return false;
            }

            $expectedPaths[str_replace('\\', '/', $target)] = true;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($moduleRoot, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $path = str_replace('\\', '/', $item->getPathname());
            if ($path === str_replace('\\', '/', $manifestReal)) {
                continue;
            }

            if (!isset($expectedPaths[$path])) {
                return false;
            }
        }

        return true;
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
        $algo = strtolower((string) config('extensions.signature_algo', 'sha256'));
        return match ($algo) {
            'sha384' => OPENSSL_ALGO_SHA384,
            'sha512' => OPENSSL_ALGO_SHA512,
            default => OPENSSL_ALGO_SHA256,
        };
    }

    private function hasUnsafeManifestPaths(array $manifest): bool
    {
        $pathKeys = [
            'routes',
            'hooks',
            'hook_definitions',
            'widgets_path',
            'assets',
            'public_assets_base',
            'public_assets_key',
        ];

        foreach ($pathKeys as $pathKey) {
            if (!array_key_exists($pathKey, $manifest)) {
                continue;
            }

            if (!$this->isSafeRelativePackagePath((string) $manifest[$pathKey])) {
                return true;
            }
        }

        return false;
    }

    private function hasInvalidNamespaceBoundary(string $moduleDir, array $manifest, string $packageKind, string $moduleName): bool
    {
        $forbiddenRoot = $packageKind === 'extension' ? 'App\\Modules\\' : 'App\\Extensions\\';
        $namespaceCandidates = $this->namespaceNameCandidates($moduleName, $manifest);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($moduleDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile() || strtolower($item->getExtension()) !== 'php') {
                continue;
            }

            $contents = file_get_contents($item->getPathname());
            if (!is_string($contents)) {
                return true;
            }

            if (preg_match('/\\bnamespace\\s+' . preg_quote($forbiddenRoot, '/') . '/', $contents) === 1) {
                return true;
            }

            foreach ($namespaceCandidates as $namespaceCandidate) {
                if (str_contains($contents, $forbiddenRoot . $namespaceCandidate . '\\')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function namespaceNameCandidates(string $moduleName, array $manifest): array
    {
        $values = [
            $moduleName,
            (string) ($manifest['name'] ?? ''),
            (string) ($manifest['key'] ?? ''),
        ];

        $candidates = [];
        foreach ($values as $value) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }

            $compact = preg_replace('/[^a-zA-Z0-9_]/', '', $value) ?: '';
            if ($compact !== '') {
                $candidates[] = $compact;
            }

            $parts = preg_split('/[^a-zA-Z0-9]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $studly = implode('', array_map(
                static fn (string $part): string => ucfirst(strtolower($part)),
                $parts
            ));
            if ($studly !== '') {
                $candidates[] = $studly;
            }
        }

        return array_values(array_unique($candidates));
    }

    private function isSafeRelativePackagePath(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        if ($relativePath === '' || str_starts_with($relativePath, '/') || str_contains($relativePath, "\0")) {
            return false;
        }

        if (preg_match('/^[a-zA-Z]:\\//', $relativePath) === 1 || str_contains($relativePath, '://')) {
            return false;
        }

        $segments = explode('/', $relativePath);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function copyDirectory(string $source, string $destination): bool
    {
        $sourceReal = realpath($source);
        if ($sourceReal === false) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceReal, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        if (!is_dir($destination) && !mkdir($destination, 0755, true)) {
            return false;
        }

        $sourcePrefix = rtrim(str_replace('\\', '/', $sourceReal), '/') . '/';

        foreach ($iterator as $item) {
            $pathname = str_replace('\\', '/', $item->getPathname());
            $relativePath = ltrim(substr($pathname, strlen($sourcePrefix)), '/');
            if ($relativePath === '') {
                continue;
            }
            $target = rtrim($destination, '/') . '/' . $relativePath;
            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0755, true)) {
                    return false;
                }
            } else {
                if (!copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }
        return true;
    }

    private function createAssetsSymlink(string $moduleName, string $modulePath): bool
    {
        $assetsPath = $modulePath . '/Assets';
        if (!is_dir($assetsPath)) {
            return true;
        }

        $contract = flatcms_resolve_module_asset_contract($moduleName);
        $linkPath = trim((string) ($contract['public_path'] ?? ''));
        if ($linkPath === '') {
            return false;
        }

        $publicBasePath = dirname($linkPath);
        if (!is_dir($publicBasePath) && !@mkdir($publicBasePath, 0755, true) && !is_dir($publicBasePath)) {
            return false;
        }

        if (is_link($linkPath)) {
            $currentTarget = @readlink($linkPath);
            $currentReal = $currentTarget !== false ? realpath(dirname($linkPath) . '/' . $currentTarget) : false;
            $expectedReal = realpath($assetsPath);
            if ($currentReal === $expectedReal && $expectedReal !== false) {
                return true;
            }
            if (!@unlink($linkPath) && file_exists($linkPath)) {
                return false;
            }
        } elseif (is_dir($linkPath)) {
            if (function_exists('symlink')) {
                $this->removeDirectory($linkPath);
            } else {
                // Shared hosting fallback: keep a real folder and refresh files in place.
                return $this->copyDirectory($assetsPath, $linkPath);
            }
        } elseif (file_exists($linkPath)) {
            if (!@unlink($linkPath) && file_exists($linkPath)) {
                return false;
            }
        }

        if (function_exists('symlink')) {
            $linked = @symlink($assetsPath, $linkPath);
            if ($linked !== false && is_link($linkPath)) {
                return true;
            }
        }

        // Symlink unavailable (Nginx shared hosting, Windows, etc.): copy assets.
        return $this->copyDirectory($assetsPath, $linkPath);
    }

    private function removeAssetsLink(string $moduleName): void
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $moduleName) ?: $moduleName;
        $contract = flatcms_resolve_module_asset_contract($moduleName);
        $declaredPath = trim((string) ($contract['public_path'] ?? ''));
        $candidates = array_unique([
            $declaredPath,
            $this->publicModulesPath . '/' . strtolower($moduleName),
            $this->publicModulesPath . '/' . strtolower($sanitized),
        ]);

        foreach ($candidates as $candidate) {
            $linkPath = $candidate;
            if ($linkPath === '') {
                continue;
            }
            if (is_link($linkPath)) {
                @unlink($linkPath);
                continue;
            }

            if (is_dir($linkPath)) {
                // Fallback mode (assets copied instead of symlink): remove directory on uninstall.
                $this->removeDirectory($linkPath);
                continue;
            }

            if (file_exists($linkPath)) {
                @unlink($linkPath);
            }
        }

        // Safety net: remove any remaining symlink/directory that points to this module assets.
        if (!is_dir($this->publicModulesPath)) {
            return;
        }

        $entries = scandir($this->publicModulesPath);
        if ($entries === false) {
            return;
        }

        $expectedTargets = [
            realpath($this->modulesPath . '/' . $sanitized . '/Assets'),
            realpath($this->extensionsPath . '/' . $sanitized . '/Assets'),
            realpath($this->modulesPath . '/' . $moduleName . '/Assets'),
            realpath($this->extensionsPath . '/' . $moduleName . '/Assets'),
        ];
        $expectedTargets = array_values(array_filter(array_unique($expectedTargets)));
        if (empty($expectedTargets)) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $this->publicModulesPath . '/' . $entry;
            if (!is_link($path)) {
                continue;
            }
            $target = @readlink($path);
            if ($target === false) {
                continue;
            }
            $targetReal = realpath(dirname($path) . '/' . $target);
            if ($targetReal !== false && in_array($targetReal, $expectedTargets, true)) {
                @unlink($path);
            }
        }

        $extensionAssetsRoot = BASE_PATH . '/public/assets/extensions';
        if (!is_dir($extensionAssetsRoot)) {
            return;
        }

        $iterator = scandir($extensionAssetsRoot);
        if ($iterator === false) {
            return;
        }

        foreach ($iterator as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $extensionAssetsRoot . '/' . $entry;
            if (is_link($path)) {
                $target = @readlink($path);
                if ($target === false) {
                    continue;
                }
                $targetReal = realpath(dirname($path) . '/' . $target);
                if ($targetReal !== false && in_array($targetReal, $expectedTargets, true)) {
                    @unlink($path);
                }
                continue;
            }

            if (is_dir($path) && in_array(realpath($path), $expectedTargets, true)) {
                $this->removeDirectory($path);
            }
        }
    }

    private function removeModuleTranslations(string $moduleName): void
    {
        $moduleName = preg_replace('/[^a-zA-Z0-9_-]/', '', $moduleName) ?: $moduleName;
        $paths = [
            $this->modulesPath . '/' . $moduleName . '/Languages',
            $this->extensionsPath . '/' . $moduleName . '/Languages',
        ];

        foreach ($paths as $langDir) {
            if (!is_dir($langDir)) {
                continue;
            }
            foreach (glob($langDir . '/*.json') as $file) {
                @unlink($file);
            }
            // remove empty dir
            @rmdir($langDir);
        }
    }

    private function cleanupInstall(string $zipPath, string $extractDir): void
    {
        if ($zipPath !== '' && file_exists($zipPath)) {
            @unlink($zipPath);
        }
        if ($extractDir !== '') {
            $this->removeDirectory($extractDir);
        }
    }

    private function removeDirectory(string $path): void
    {
        if ($path === '') {
            return;
        }

        if (is_link($path)) {
            @unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    @rmdir($item->getPathname());
                } else {
                    @unlink($item->getPathname());
                }
            }
        } catch (\Throwable $e) {
            // Symlink unavailable (Nginx shared hosting, Windows, etc.): copy assets.
            // Ignore cleanup errors to avoid breaking module workflows.
        }
        @rmdir($path);
    }

    private function logInstallException(\Throwable $e): void
    {
        error_log(sprintf(
            '[Modules][install] %s in %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }

    private function isAllowedModulePath(string $path): bool
    {
        $realPath = realpath($path);
        if ($realPath === false) {
            return false;
        }
        $modulesRoot = realpath($this->modulesPath);
        $extensionsRoot = realpath($this->extensionsPath);
        if ($modulesRoot && str_starts_with($realPath, $modulesRoot)) {
            return true;
        }
        if ($extensionsRoot && str_starts_with($realPath, $extensionsRoot)) {
            return true;
        }
        return false;
    }

}
