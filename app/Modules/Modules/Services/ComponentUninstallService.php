<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Modules/Services/ComponentUninstallService.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Modules\Services;

use App\Core\ModuleManager;
use App\Core\ModuleStateRepository;
use App\Core\RuntimeAssetPublisher;

final class ComponentUninstallService
{
    private string $basePath;
    private RuntimeAssetPublisher $assetPublisher;
    private ModuleStateRepository $stateRepository;

    public function __construct(
        ?string $basePath = null,
        ?RuntimeAssetPublisher $assetPublisher = null,
        ?ModuleStateRepository $stateRepository = null
    ) {
        $this->basePath = rtrim($basePath ?: BASE_PATH, '/\\');
        $this->assetPublisher = $assetPublisher ?? new RuntimeAssetPublisher($this->basePath);
        $this->stateRepository = $stateRepository ?? new ModuleStateRepository(
            $this->basePath . '/data/modules.json'
        );
    }

    /**
     * @param array<string, mixed> $meta
     * @return array{component: string, code_removed: bool, assets_removed: bool, state_removed: bool, cleanup_pending: bool, preserved_data: array<int, string>}
     */
    public function uninstall(string $name, array $meta): array
    {
        $name = trim($name);
        if ($name === '' || str_contains($name, "\0")) {
            throw new \InvalidArgumentException('component_uninstall_name_invalid');
        }

        $componentPath = $this->componentPath($meta);
        $state = $this->stateRepository->all();
        $hadState = array_key_exists($name, $state);
        $previousState = is_array($state[$name] ?? null) ? $state[$name] : [];
        $quarantine = $this->quarantinePath($componentPath);

        if (!@rename($componentPath, $quarantine)) {
            throw new \RuntimeException('component_uninstall_quarantine_failed');
        }

        $committed = false;
        try {
            $this->assetPublisher->removeComponentAssets($meta);
            $this->stateRepository->remove($name);
            $committed = true;
        } catch (\Throwable $exception) {
            $this->rollback($name, $componentPath, $quarantine, $hadState, $previousState, $exception);
        }

        $cleanupPending = false;
        try {
            $this->removeTree($quarantine);
            foreach ($this->ephemeralPaths($meta) as $path) {
                $this->removeTree($path);
            }
        } catch (\Throwable) {
            $cleanupPending = true;
        }

        ModuleManager::clearCatalogCache();

        return [
            'component' => $name,
            'code_removed' => $committed && !file_exists($componentPath) && !is_link($componentPath),
            'assets_removed' => $committed,
            'state_removed' => $committed,
            'cleanup_pending' => $cleanupPending,
            'preserved_data' => $this->preservedDataPaths($name, $meta),
        ];
    }

    /** @param array<string, mixed> $meta */
    private function componentPath(array $meta): string
    {
        $path = trim((string) ($meta['path'] ?? ''));
        $realPath = $path !== '' ? realpath($path) : false;
        if ($realPath === false || is_link($path) || !is_dir($realPath)) {
            throw new \RuntimeException('component_uninstall_path_invalid');
        }

        foreach (['Modules', 'Extensions', 'Plugins'] as $type) {
            $root = realpath($this->basePath . '/app/' . $type);
            if ($root !== false && dirname($realPath) === $root) {
                return $realPath;
            }
        }

        throw new \RuntimeException('component_uninstall_path_outside_components');
    }

    private function quarantinePath(string $componentPath): string
    {
        $root = $this->basePath . '/storage/tmp/component-uninstall';
        $this->ensureDirectory($root);

        return $root . '/' . basename($componentPath) . '-' . bin2hex(random_bytes(8));
    }

    /**
     * @param array<string, mixed> $previousState
     * @return never
     */
    private function rollback(
        string $name,
        string $componentPath,
        string $quarantine,
        bool $hadState,
        array $previousState,
        \Throwable $cause
    ): void {
        $errors = [];

        if (!file_exists($componentPath) && is_dir($quarantine) && !@rename($quarantine, $componentPath)) {
            $errors[] = 'code';
        }

        try {
            if ($hadState) {
                $this->stateRepository->merge([$name => $previousState]);
            } else {
                $this->stateRepository->remove($name);
            }
        } catch (\Throwable) {
            $errors[] = 'state';
        }

        ModuleManager::clearCatalogCache();
        if (is_dir($componentPath)) {
            try {
                $this->assetPublisher->publishComponent($name);
            } catch (\Throwable) {
                $errors[] = 'assets';
            }
        }

        if ($errors !== []) {
            throw new \RuntimeException(
                'component_uninstall_rollback_failed:' . implode(',', $errors),
                0,
                $cause
            );
        }

        throw new \RuntimeException('component_uninstall_failed', 0, $cause);
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<int, string>
     */
    private function ephemeralPaths(array $meta): array
    {
        $key = trim((string) ($meta['public_assets_key'] ?? $meta['key'] ?? ''));
        if ($key === '' || preg_match('/^[a-z0-9][a-z0-9_-]*$/i', $key) !== 1) {
            return [];
        }

        return [
            $this->basePath . '/storage/cache/components/' . $key,
            $this->basePath . '/storage/tmp/components/' . $key,
        ];
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<int, string>
     */
    private function preservedDataPaths(string $name, array $meta): array
    {
        $location = strtolower(trim((string) ($meta['location'] ?? 'module')));
        $family = match ($location) {
            'extension' => 'extensions',
            'plugin' => 'plugins',
            default => 'modules',
        };
        $paths = [$this->basePath . '/data/' . $family . '/' . $name];

        return array_values(array_map(function (string $path): string {
            return ltrim(str_replace($this->basePath, '', $path), '/\\');
        }, array_filter($paths, static fn(string $path): bool => file_exists($path) || is_link($path))));
    }

    private function ensureDirectory(string $path): void
    {
        if (is_link($path) || (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path))) {
            throw new \RuntimeException('component_uninstall_directory_failed');
        }
    }

    private function removeTree(string $path): void
    {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }
        if (is_link($path) || is_file($path)) {
            if (!@unlink($path)) {
                throw new \RuntimeException('component_uninstall_cleanup_failed');
            }
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            $removed = $item->isLink() || !$item->isDir()
                ? @unlink($itemPath)
                : @rmdir($itemPath);
            if (!$removed && (file_exists($itemPath) || is_link($itemPath))) {
                throw new \RuntimeException('component_uninstall_cleanup_failed');
            }
        }
        if (!@rmdir($path) && is_dir($path)) {
            throw new \RuntimeException('component_uninstall_cleanup_failed');
        }
    }
}
