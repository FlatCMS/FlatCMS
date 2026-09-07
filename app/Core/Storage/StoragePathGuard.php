<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core\Storage;

final class StoragePathGuard
{
    private string $configuredRoot;
    private string $root;

    public function __construct(string $root, int $directoryMode = 0755)
    {
        $root = rtrim(str_replace('\\', '/', trim($root)), '/');
        if ($root === '' || !str_starts_with($root, '/') || str_contains($root, "\0")) {
            throw new StorageException('Storage root must be an absolute path.');
        }

        $this->configuredRoot = $root;

        if (is_link($root)) {
            throw new StorageException('Storage root cannot be a symbolic link: ' . $root);
        }

        if (!is_dir($root) && !mkdir($root, $directoryMode, true) && !is_dir($root)) {
            throw new StorageException('Unable to create storage root: ' . $root);
        }

        $resolved = realpath($root);
        if ($resolved === false || !is_dir($resolved)) {
            throw new StorageException('Unable to resolve storage root: ' . $root);
        }

        $this->root = rtrim(str_replace('\\', '/', $resolved), '/');
    }

    public function root(): string
    {
        return $this->root;
    }

    public function resolve(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || str_contains($path, "\0")) {
            throw new StorageException('Storage path cannot be empty or contain null bytes.');
        }

        if (str_starts_with($path, '/')) {
            if (str_starts_with($path, $this->root . '/')) {
                $path = substr($path, strlen($this->root) + 1);
            } elseif (str_starts_with($path, $this->configuredRoot . '/')) {
                $path = substr($path, strlen($this->configuredRoot) + 1);
            } else {
                throw new StorageException('Storage path escapes its configured root: ' . $path);
            }
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '') {
                continue;
            }
            if ($segment === '.' || $segment === '..') {
                throw new StorageException('Storage path traversal is forbidden: ' . $path);
            }
            $segments[] = $segment;
        }

        if ($segments === []) {
            throw new StorageException('Storage path must identify an entry below its root.');
        }

        $candidate = $this->root;
        foreach ($segments as $segment) {
            $candidate .= '/' . $segment;
            if (is_link($candidate)) {
                throw new StorageException('Symbolic links are forbidden in canonical storage: ' . $candidate);
            }

            if (!file_exists($candidate)) {
                continue;
            }

            $resolved = realpath($candidate);
            if ($resolved === false || !$this->contains((string) $resolved)) {
                throw new StorageException('Storage path resolves outside its configured root: ' . $candidate);
            }
        }

        return $candidate;
    }

    public function relative(string $path): string
    {
        $resolved = $this->resolve($path);
        return substr($resolved, strlen($this->root) + 1);
    }

    public function ensureDirectory(string $path, int $directoryMode = 0755): string
    {
        $normalized = rtrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalized === $this->root || $normalized === $this->configuredRoot) {
            return $this->root;
        }

        $target = $this->resolve($path);
        $relative = substr($target, strlen($this->root) + 1);
        $current = $this->root;

        foreach (explode('/', $relative) as $segment) {
            $current .= '/' . $segment;
            if (is_link($current)) {
                throw new StorageException('Symbolic links are forbidden in canonical storage: ' . $current);
            }
            if (is_dir($current)) {
                continue;
            }
            if (file_exists($current) || (!mkdir($current, $directoryMode) && !is_dir($current))) {
                throw new StorageException('Unable to create storage directory: ' . $current);
            }
        }

        return $target;
    }

    private function contains(string $path): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        return $path === $this->root || str_starts_with($path, $this->root . '/');
    }
}
