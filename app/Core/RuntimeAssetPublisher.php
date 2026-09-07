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

final class RuntimeAssetPublisher
{
    private string $basePath;
    private string $publicPath;
    private string $lockPath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = rtrim($basePath ?: BASE_PATH, '/\\');
        $this->publicPath = $this->basePath . '/public';
        $this->lockPath = $this->basePath . '/storage/cache/assets/publish.lock';
    }

    /** @return array{components: array<int, string>, themes: array<int, string>} */
    public function publishAll(): array
    {
        $lock = $this->acquireLock();

        try {
            ModuleManager::clearCatalogCache();
            $manager = $this->moduleManager();
            $this->assertUniqueComponentDestinations($manager->all());
            $components = [];

            foreach ($manager->all() as $name => $meta) {
                if (!is_array($meta) || !(bool) ($meta['integrity_valid'] ?? true)) {
                    continue;
                }

                if ($this->publishComponentMetadata((string) $name, $meta)) {
                    $components[] = (string) $name;
                }
            }

            $this->pruneComponentAssets($manager->all());

            return [
                'components' => $components,
                'themes' => $this->publishThemesUnlocked(),
            ];
        } finally {
            $this->releaseLock($lock);
        }
    }

    public function publishComponent(string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('runtime_assets_component_name_required');
        }

        $lock = $this->acquireLock();

        try {
            ModuleManager::clearCatalogCache();
            $meta = $this->moduleManager()->get($name);
            if (!is_array($meta)) {
                throw new \RuntimeException('runtime_assets_component_not_found');
            }
            if (!(bool) ($meta['integrity_valid'] ?? true)) {
                throw new \RuntimeException('runtime_assets_component_invalid');
            }

            return $this->publishComponentMetadata($name, $meta);
        } finally {
            $this->releaseLock($lock);
        }
    }

    /** @param array<string, mixed> $meta */
    public function removeComponentAssets(array $meta): void
    {
        $lock = $this->acquireLock();

        try {
            $destination = $this->componentDestination($meta);
            if ($destination !== '') {
                $this->removePath($destination);
            }
        } finally {
            $this->releaseLock($lock);
        }
    }

    public function publishTheme(string $type, string $name): bool
    {
        $lock = $this->acquireLock();

        try {
            return $this->publishThemeUnlocked($type, $name);
        } finally {
            $this->releaseLock($lock);
        }
    }

    private function moduleManager(): ModuleManager
    {
        return new ModuleManager([
            $this->basePath . '/app/Modules',
            $this->basePath . '/app/Extensions',
            $this->basePath . '/app/Plugins',
        ], $this->basePath . '/data/modules.json');
    }

    /** @param array<string, mixed> $meta */
    private function publishComponentMetadata(string $name, array $meta): bool
    {
        $destination = $this->componentDestination($meta);
        if ($destination === '') {
            throw new \RuntimeException('runtime_assets_destination_invalid:' . $name);
        }

        $source = trim((string) ($meta['assets_path'] ?? ''));
        $status = trim((string) ($meta['assets_status'] ?? 'missing'));
        if ($source === '' || $status === 'missing' || $status === 'absent') {
            $this->removePath($destination);
            return false;
        }
        if ($status !== 'ok' || !is_dir($source) || is_link($source)) {
            throw new \RuntimeException('runtime_assets_source_invalid:' . $name);
        }

        $this->publishDirectory($source, $destination);
        return true;
    }

    /** @param array<string, mixed> $meta */
    private function componentDestination(array $meta): string
    {
        $location = strtolower(trim((string) ($meta['location'] ?? 'module')));
        $defaultBase = match ($location) {
            'extension' => 'assets/extensions',
            'plugin' => 'assets/plugins',
            default => 'modules',
        };
        $base = $this->safeRelativePath((string) ($meta['public_assets_base'] ?? $defaultBase));
        $key = $this->safeRelativePath((string) ($meta['public_assets_key'] ?? $meta['key'] ?? ''));
        if ($base === '' || $key === '') {
            return '';
        }

        $destination = $this->publicPath . '/' . $base . '/' . $key;
        return $this->isInsidePublicPath($destination) ? $destination : '';
    }

    /** @param array<string, array<string, mixed>> $components */
    private function assertUniqueComponentDestinations(array $components): void
    {
        $destinations = [];

        foreach ($components as $name => $meta) {
            if (!is_array($meta) || !(bool) ($meta['integrity_valid'] ?? true)) {
                continue;
            }

            if ((string) ($meta['assets_status'] ?? 'missing') !== 'ok') {
                continue;
            }

            $destination = $this->componentDestination($meta);
            if ($destination === '') {
                continue;
            }

            if (isset($destinations[$destination])) {
                throw new \RuntimeException(
                    'runtime_assets_destination_conflict:'
                    . ltrim(str_replace($this->publicPath, '', $destination), '/')
                    . ':' . $destinations[$destination] . ':' . $name
                );
            }

            $destinations[$destination] = (string) $name;
        }
    }

    /** @param array<string, array<string, mixed>> $components */
    private function pruneComponentAssets(array $components): void
    {
        $managedRoots = [
            'modules' => $this->publicPath . '/modules',
            'assets/extensions' => $this->publicPath . '/assets/extensions',
            'assets/plugins' => $this->publicPath . '/assets/plugins',
        ];
        $expected = array_fill_keys(array_keys($managedRoots), []);

        foreach ($components as $meta) {
            if (!is_array($meta) || !(bool) ($meta['integrity_valid'] ?? true)) {
                continue;
            }
            $base = $this->safeRelativePath((string) ($meta['public_assets_base'] ?? ''));
            $key = $this->safeRelativePath((string) ($meta['public_assets_key'] ?? ''));
            $source = trim((string) ($meta['assets_path'] ?? ''));
            if (!isset($managedRoots[$base]) || $key === '' || str_contains($key, '/') || !is_dir($source)) {
                continue;
            }
            $expected[$base][$key] = true;
        }

        foreach ($managedRoots as $base => $root) {
            if (!is_dir($root) || is_link($root)) {
                continue;
            }
            foreach (scandir($root) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..' || str_starts_with($entry, '.')) {
                    continue;
                }
                if (!isset($expected[$base][$entry])) {
                    $this->removePath($root . '/' . $entry);
                }
            }
        }
    }

    /** @return array<int, string> */
    private function publishThemesUnlocked(): array
    {
        $published = [];

        foreach (['admin', 'frontend'] as $type) {
            $root = $this->basePath . '/themes/' . $type;
            foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $themePath) {
                $name = basename($themePath);
                if ($this->publishThemeUnlocked($type, $name)) {
                    $published[] = $type . '/' . $name;
                }
            }
        }

        return $published;
    }

    private function publishThemeUnlocked(string $type, string $name): bool
    {
        if (!in_array($type, ['admin', 'frontend'], true)) {
            throw new \InvalidArgumentException('runtime_assets_theme_type_invalid');
        }

        $safeName = $this->safeRelativePath($name);
        if ($safeName === '' || str_contains($safeName, '/')) {
            throw new \InvalidArgumentException('runtime_assets_theme_name_invalid');
        }

        $source = $this->basePath . '/themes/' . $type . '/' . $safeName;
        if (!is_dir($source) || is_link($source)) {
            throw new \RuntimeException('runtime_assets_theme_not_found:' . $type . '/' . $safeName);
        }

        $assets = $source . '/assets';
        $screenshots = [
            'screenshot.png',
            'screenshot.webp',
            'screenshot.jpg',
            'preview.png',
            'preview.webp',
            'preview.jpg',
        ];
        $hasScreenshot = false;
        foreach ($screenshots as $filename) {
            if (is_file($source . '/' . $filename) || is_file($assets . '/' . $filename)) {
                $hasScreenshot = true;
                break;
            }
        }

        $destination = $this->publicPath . '/themes/' . $type . '/' . $safeName;
        if (!is_dir($assets) && !$hasScreenshot) {
            $this->removePath($destination);
            return false;
        }

        $stage = $this->createStagePath($destination);
        try {
            $stageAssets = $stage . '/assets';
            $this->ensureDirectory($stageAssets);
            if (is_dir($assets)) {
                $this->copyTree($assets, $stageAssets);
            }

            foreach ($screenshots as $filename) {
                $candidate = is_file($source . '/' . $filename)
                    ? $source . '/' . $filename
                    : $assets . '/' . $filename;
                if (is_file($candidate) && !@copy($candidate, $stageAssets . '/' . $filename)) {
                    throw new \RuntimeException('runtime_assets_copy_failed:' . $filename);
                }
            }

            $this->swapDirectory($stage, $destination);
        } catch (\Throwable $exception) {
            $this->removePath($stage);
            throw $exception;
        }

        return true;
    }

    private function publishDirectory(string $source, string $destination): void
    {
        if (!$this->isInsidePublicPath($destination)) {
            throw new \RuntimeException('runtime_assets_destination_outside_public');
        }

        $stage = $this->createStagePath($destination);
        try {
            $this->ensureDirectory($stage);
            $this->copyTree($source, $stage);
            $this->swapDirectory($stage, $destination);
        } catch (\Throwable $exception) {
            $this->removePath($stage);
            throw $exception;
        }
    }

    private function copyTree(string $source, string $destination): void
    {
        if (is_link($source)) {
            throw new \RuntimeException('runtime_assets_symlink_forbidden:' . $source);
        }

        $source = rtrim($source, '/\\');
        $prefixLength = strlen($source) + 1;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isLink()) {
                throw new \RuntimeException('runtime_assets_symlink_forbidden:' . $item->getPathname());
            }

            $relative = substr($item->getPathname(), $prefixLength);
            if (!is_string($relative) || $relative === '') {
                continue;
            }

            $target = $destination . '/' . str_replace('\\', '/', $relative);
            if ($item->isDir()) {
                $this->ensureDirectory($target);
                continue;
            }

            $this->ensureDirectory(dirname($target));
            if (!@copy($item->getPathname(), $target)) {
                throw new \RuntimeException('runtime_assets_copy_failed:' . $relative);
            }
            @chmod($target, 0644);
        }
    }

    private function swapDirectory(string $stage, string $destination): void
    {
        $this->ensureDirectory(dirname($destination));
        $backup = dirname($destination) . '/.flatcms-assets-backup-' . basename($destination) . '-' . bin2hex(random_bytes(6));
        $hadDestination = file_exists($destination) || is_link($destination);

        if ($hadDestination && !@rename($destination, $backup)) {
            throw new \RuntimeException('runtime_assets_backup_failed:' . $destination);
        }

        if (!@rename($stage, $destination)) {
            if ($hadDestination) {
                @rename($backup, $destination);
            }
            throw new \RuntimeException('runtime_assets_swap_failed:' . $destination);
        }

        @chmod($destination, 0755);
        if ($hadDestination) {
            $this->removePath($backup);
        }
    }

    private function createStagePath(string $destination): string
    {
        $parent = dirname($destination);
        $this->ensureDirectory($parent);
        $stage = $parent . '/.flatcms-assets-stage-' . basename($destination) . '-' . bin2hex(random_bytes(6));
        $this->ensureDirectory($stage);
        return $stage;
    }

    private function ensureDirectory(string $path): void
    {
        if (is_link($path)) {
            throw new \RuntimeException('runtime_assets_directory_symlink_forbidden:' . $path);
        }
        if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException('runtime_assets_directory_create_failed:' . $path);
        }
    }

    private function removePath(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            if (!@unlink($path) && (file_exists($path) || is_link($path))) {
                throw new \RuntimeException('runtime_assets_remove_failed:' . $path);
            }
            return;
        }
        if (!is_dir($path)) {
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
                throw new \RuntimeException('runtime_assets_remove_failed:' . $itemPath);
            }
        }
        if (!@rmdir($path) && is_dir($path)) {
            throw new \RuntimeException('runtime_assets_remove_failed:' . $path);
        }
    }

    private function safeRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            return '';
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || preg_match('/^[A-Za-z]:$/', $segment)) {
                return '';
            }
        }

        return $path;
    }

    private function isInsidePublicPath(string $path): bool
    {
        $public = rtrim(str_replace('\\', '/', $this->publicPath), '/');
        $candidate = str_replace('\\', '/', $path);
        return str_starts_with($candidate, $public . '/');
    }

    /** @return resource */
    private function acquireLock()
    {
        $this->ensureDirectory(dirname($this->lockPath));
        $lock = @fopen($this->lockPath, 'c+');
        if (!is_resource($lock) || !@flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                @fclose($lock);
            }
            throw new \RuntimeException('runtime_assets_lock_failed');
        }

        return $lock;
    }

    /** @param resource $lock */
    private function releaseLock($lock): void
    {
        @flock($lock, LOCK_UN);
        @fclose($lock);
    }
}
