<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/Storage/StoragePathGuard.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Core\Storage;

final class StoragePathGuard
{
    private string $configuredRoot;
    private string $root;

    public function __construct(string $root, int $directoryMode = 0755)
    {
        $root = self::normalizeAbsoluteRoot($root);

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

        $this->root = self::normalizeAbsoluteRoot($resolved);
    }

    public function root(): string
    {
        return $this->root;
    }

    public function resolve(string $path): string
    {
        $path = self::relativeEntry($path, $this->root, $this->configuredRoot);
        $segments = explode('/', $path);

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
        if (self::samePath($normalized, $this->root) || self::samePath($normalized, $this->configuredRoot)) {
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
        return self::samePath($path, $this->root) || self::startsWithPath($path, $this->root . '/');
    }

    /** Pure lexical validation; the platform argument also permits cross-platform contract tests. */
    public static function normalizeAbsoluteRoot(string $path, ?bool $windows = null): string
    {
        $windows ??= PHP_OS_FAMILY === 'Windows';
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $prefixLength = $windows ? 3 : 1;
        $absolute = $windows ? preg_match('~^[A-Za-z]:/[^/]~', $path) === 1
            : str_starts_with($path, '/') && !str_starts_with($path, '//');
        if (!$absolute || strlen($path) <= $prefixLength || $path !== trim($path)) {
            throw new StorageException('Storage root must be an absolute local directory, not a filesystem root.');
        }
        self::validateSegments(substr($path, $prefixLength));
        return $path;
    }

    /** Resolves only the lexical boundary. resolve() additionally checks existing filesystem entries. */
    public static function relativeEntry(string $path, string $root, ?string $configuredRoot = null, ?bool $windows = null): string
    {
        $windows ??= PHP_OS_FAMILY === 'Windows';
        $root = self::normalizeAbsoluteRoot($root, $windows);
        $configuredRoot = self::normalizeAbsoluteRoot($configuredRoot ?? $root, $windows);
        $path = str_replace('\\', '/', $path);
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) === 1) {
            self::normalizeAbsoluteRoot($path, $windows);
            if (self::startsWithPath($path, $root . '/', $windows)) {
                $path = substr($path, strlen($root) + 1);
            } elseif (self::startsWithPath($path, $configuredRoot . '/', $windows)) {
                $path = substr($path, strlen($configuredRoot) + 1);
            } else {
                throw new StorageException('Storage path escapes its configured root: ' . $path);
            }
        }
        self::validateSegments($path);
        return $path;
    }

    private static function validateSegments(string $path): void
    {
        foreach (explode('/', $path) as $segment) {
            // Use the portable intersection, including on Unix, so a copy to NTFS stays unambiguous.
            if ($segment === '' || $segment === '.' || $segment === '..'
                || preg_match('/[<>:"|?*\x00-\x1f\x7f]/', $segment) === 1
                || rtrim($segment, '. ') !== $segment
                || preg_match('//u', $segment) !== 1
                || preg_match('/^(?:con|prn|aux|nul|conin\$|conout\$|(?:com|lpt)[1-9\x{00b9}\x{00b2}\x{00b3}])(?:\.|$)/uiD', $segment) === 1) {
                throw new StorageException('Storage path contains a non-portable or unsafe segment: ' . $path);
            }
        }
    }

    private static function startsWithPath(string $path, string $prefix, ?bool $windows = null): bool
    {
        return ($windows ?? PHP_OS_FAMILY === 'Windows')
            ? str_starts_with(self::foldWindowsCase($path), self::foldWindowsCase($prefix))
            : str_starts_with($path, $prefix);
    }

    private static function samePath(string $left, string $right): bool
    {
        return PHP_OS_FAMILY === 'Windows' ? self::foldWindowsCase($left) === self::foldWindowsCase($right) : $left === $right;
    }

    public static function foldWindowsCase(string $value): string
    {
        if (preg_match('//u', $value) !== 1) {
            throw new StorageException('Windows storage paths and lock scopes must be valid UTF-8.');
        }
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }
        if (preg_match('/[^\x00-\x7f]/', $value) === 1) {
            throw new StorageException('The mbstring extension is required for non-ASCII Windows storage paths.');
        }
        return strtolower($value);
    }
}
