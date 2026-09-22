<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/InstallationDoctor.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Core;

final class InstallationDoctor
{
    /** @var array<int, string> */
    private const REQUIRED_DIRECTORIES = [
        'app',
        'data',
        'data/core',
        'data/core/pages',
        'data/core/posts',
        'public',
        'public/uploads',
        'storage',
        'themes',
        'resources',
    ];

    /** @var array<int, string> */
    private const PORTABLE_TREES = [
        'app',
        'data',
        'public',
        'themes',
        'resources',
    ];

    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $basePath ??= defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $basePath = rtrim(str_replace('\\', '/', trim($basePath)), '/');

        if ($basePath === '' || is_link($basePath) || !is_dir($basePath)) {
            throw new \RuntimeException('doctor_base_path_invalid');
        }

        $resolved = realpath($basePath);
        if ($resolved === false || !is_dir($resolved)) {
            throw new \RuntimeException('doctor_base_path_unresolved');
        }

        $this->basePath = rtrim(str_replace('\\', '/', $resolved), '/');
    }

    /**
     * @return array{ok: bool, checks: array<int, array{code: string, ok: bool, details: array<string, mixed>}>}
     */
    public function diagnose(): array
    {
        $checks = [
            $this->checkRequiredDirectories(),
            $this->checkRootUploadsAlias(),
            $this->checkPortableTreeSymlinks(),
            $this->checkDataJson(),
            $this->checkContentDocuments(),
            $this->checkThemeAssets(),
        ];

        return [
            'ok' => !in_array(false, array_column($checks, 'ok'), true),
            'checks' => $checks,
        ];
    }

    /** @return array{code: string, ok: bool, details: array<string, mixed>} */
    private function checkRequiredDirectories(): array
    {
        $missing = [];
        $links = [];

        foreach (self::REQUIRED_DIRECTORIES as $relativePath) {
            $path = $this->path($relativePath);
            if (is_link($path)) {
                $links[] = $relativePath;
                continue;
            }
            if (!is_dir($path)) {
                $missing[] = $relativePath;
            }
        }

        return $this->check('required_directories', $missing === [] && $links === [], [
            'missing' => $missing,
            'symbolic_links' => $links,
        ]);
    }

    /** @return array{code: string, ok: bool, details: array<string, mixed>} */
    private function checkRootUploadsAlias(): array
    {
        $path = $this->path('uploads');
        $present = is_link($path) || file_exists($path);

        return $this->check('root_uploads_alias_absent', !$present, [
            'present' => $present,
            'kind' => !$present ? null : (is_link($path) ? 'symbolic_link' : (is_dir($path) ? 'directory' : 'file')),
        ]);
    }

    /** @return array{code: string, ok: bool, details: array<string, mixed>} */
    private function checkPortableTreeSymlinks(): array
    {
        $links = [];
        $unreadable = [];

        foreach (self::PORTABLE_TREES as $relativeRoot) {
            $path = $this->path($relativeRoot);
            if (is_link($path)) {
                $links[] = $relativeRoot;
                continue;
            }
            if (!is_dir($path)) {
                continue;
            }

            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($iterator as $entry) {
                    if ($entry->isLink()) {
                        $links[] = $this->relativePath($entry->getPathname());
                    }
                }
            } catch (\UnexpectedValueException) {
                $unreadable[] = $relativeRoot;
            }
        }

        sort($links);
        sort($unreadable);

        return $this->check('portable_trees_without_symlinks', $links === [] && $unreadable === [], [
            'symbolic_links' => $links,
            'unreadable_roots' => $unreadable,
        ]);
    }

    /** @return array{code: string, ok: bool, details: array<string, mixed>} */
    private function checkDataJson(): array
    {
        $root = $this->path('data');
        $invalid = [];
        $count = 0;

        if (!is_dir($root)) {
            return $this->check('data_json_valid', false, [
                'count' => $count,
                'invalid' => ['data'],
            ]);
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $entry) {
                if (!$entry->isFile() || $entry->isLink() || strtolower($entry->getExtension()) !== 'json') {
                    continue;
                }

                ++$count;
                $contents = file_get_contents($entry->getPathname());
                if (!is_string($contents)) {
                    $invalid[] = $this->relativePath($entry->getPathname());
                    continue;
                }

                try {
                    json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $invalid[] = $this->relativePath($entry->getPathname());
                }
            }
        } catch (\UnexpectedValueException) {
            $invalid[] = 'data';
        }

        sort($invalid);

        return $this->check('data_json_valid', $invalid === [], [
            'count' => $count,
            'invalid' => $invalid,
        ]);
    }

    /** @return array{code: string, ok: bool, details: array<string, mixed>} */
    private function checkContentDocuments(): array
    {
        $invalid = [];
        $documents = 0;

        foreach (['pages' => 'page_', 'posts' => 'post_'] as $collection => $prefix) {
            $root = $this->path('data/core/' . $collection);
            if (!is_dir($root)) {
                $invalid[] = 'data/core/' . $collection;
                continue;
            }

            try {
                foreach (new \DirectoryIterator($root) as $entry) {
                    if ($entry->isDot() || !$entry->isDir() || $entry->isLink() || !str_starts_with($entry->getFilename(), $prefix)) {
                        continue;
                    }

                    ++$documents;
                    $document = $entry->getPathname();
                    $this->collectMissingDocumentFiles($document, $invalid);
                    $this->collectMissingTranslationFiles($document, $invalid);
                }
            } catch (\UnexpectedValueException) {
                $invalid[] = 'data/core/' . $collection;
            }
        }

        sort($invalid);

        return $this->check('content_documents_complete', $invalid === [], [
            'documents' => $documents,
            'invalid' => $invalid,
        ]);
    }

    /**
     * @param array<int, string> $invalid
     */
    private function collectMissingDocumentFiles(string $document, array &$invalid): void
    {
        foreach (['index.json', 'content.html'] as $filename) {
            if (!is_file($document . '/' . $filename) || is_link($document . '/' . $filename)) {
                $invalid[] = $this->relativePath($document . '/' . $filename);
            }
        }
    }

    /**
     * @param array<int, string> $invalid
     */
    private function collectMissingTranslationFiles(string $document, array &$invalid): void
    {
        $translations = $document . '/translations';
        if (!file_exists($translations) && !is_link($translations)) {
            return;
        }
        if (!is_dir($translations) || is_link($translations)) {
            $invalid[] = $this->relativePath($translations);
            return;
        }

        try {
            foreach (new \DirectoryIterator($translations) as $locale) {
                if ($locale->isDot()) {
                    continue;
                }
                if (!$locale->isDir() || $locale->isLink()) {
                    $invalid[] = $this->relativePath($locale->getPathname());
                    continue;
                }
                $this->collectMissingDocumentFiles($locale->getPathname(), $invalid);
            }
        } catch (\UnexpectedValueException) {
            $invalid[] = $this->relativePath($translations);
        }
    }

    /** @return array{code: string, ok: bool, details: array<string, mixed>} */
    private function checkThemeAssets(): array
    {
        $missing = [];
        $mismatched = [];
        $count = 0;

        foreach (['admin', 'frontend'] as $type) {
            $themesRoot = $this->path('themes/' . $type);
            if (!is_dir($themesRoot)) {
                $missing[] = 'themes/' . $type;
                continue;
            }

            try {
                foreach (new \DirectoryIterator($themesRoot) as $theme) {
                    if ($theme->isDot() || !$theme->isDir() || $theme->isLink()) {
                        continue;
                    }

                    $sourceAssets = $theme->getPathname() . '/assets';
                    if (!is_dir($sourceAssets) || is_link($sourceAssets)) {
                        continue;
                    }

                    $publicAssets = $this->path('public/themes/' . $type . '/' . $theme->getFilename() . '/assets');
                    try {
                        $iterator = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($sourceAssets, \FilesystemIterator::SKIP_DOTS),
                            \RecursiveIteratorIterator::LEAVES_ONLY
                        );
                        foreach ($iterator as $source) {
                            if (!$source->isFile() || $source->isLink()) {
                                continue;
                            }

                            ++$count;
                            $relative = substr($source->getPathname(), strlen($sourceAssets) + 1);
                            $target = $publicAssets . '/' . $relative;
                            $targetRelative = $this->relativePath($target);
                            if (!is_file($target) || is_link($target)) {
                                $missing[] = $targetRelative;
                                continue;
                            }
                            if (hash_file('sha256', $source->getPathname()) !== hash_file('sha256', $target)) {
                                $mismatched[] = $targetRelative;
                            }
                        }
                    } catch (\UnexpectedValueException) {
                        $missing[] = $this->relativePath($sourceAssets);
                    }
                }
            } catch (\UnexpectedValueException) {
                $missing[] = 'themes/' . $type;
            }
        }

        sort($missing);
        sort($mismatched);

        return $this->check('theme_assets_published', $missing === [] && $mismatched === [], [
            'assets' => $count,
            'missing' => $missing,
            'mismatched' => $mismatched,
        ]);
    }

    /** @param array<string, mixed> $details
     * @return array{code: string, ok: bool, details: array<string, mixed>}
     */
    private function check(string $code, bool $ok, array $details): array
    {
        return [
            'code' => $code,
            'ok' => $ok,
            'details' => $details,
        ];
    }

    private function path(string $relativePath): string
    {
        return $this->basePath . '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
    }

    private function relativePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $prefix = $this->basePath . '/';

        return str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : $path;
    }
}
