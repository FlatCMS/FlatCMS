<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Media\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class MediaReferenceService
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $resolvedBasePath = $basePath ?? (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4));
        $this->basePath = rtrim(str_replace('\\', '/', $resolvedBasePath), '/');
    }

    /**
     * @return array{files: array<string, array{before:string,after:string}>, replacements:int}
     */
    public function plan(string $oldPath, string $newPath): array
    {
        $oldPath = $this->normalizePath($oldPath);
        $newPath = $this->normalizePath($newPath);
        if ($oldPath === '' || $newPath === '' || $oldPath === $newPath) {
            return ['files' => [], 'replacements' => 0];
        }

        $files = [];
        $replacementCount = 0;

        foreach ($this->candidateFiles() as $file) {
            $before = @file_get_contents($file);
            if (!is_string($before) || $before === '') {
                continue;
            }

            [$after, $count] = $this->replaceString($before, $oldPath, $newPath);
            if ($count < 1 || $after === $before) {
                continue;
            }

            $files[$file] = ['before' => $before, 'after' => $after];
            $replacementCount += $count;
        }

        return ['files' => $files, 'replacements' => $replacementCount];
    }

    /**
     * @param array{files?: array<string, array{before:string,after:string}>} $plan
     */
    public function apply(array $plan): bool
    {
        $written = [];
        foreach (($plan['files'] ?? []) as $file => $change) {
            $before = (string) ($change['before'] ?? '');
            $current = @file_get_contents($file);
            if (!is_string($current) || $current !== $before || is_link($file)) {
                $this->restoreWritten($written, $plan);
                return false;
            }

            if (!$this->atomicWrite($file, (string) ($change['after'] ?? ''))) {
                $this->restoreWritten($written, $plan);
                return false;
            }
            $written[] = $file;
        }

        return true;
    }

    /**
     * @param array{files?: array<string, array{before:string,after:string}>} $plan
     */
    public function rollback(array $plan): bool
    {
        $success = true;
        foreach (array_reverse(array_keys($plan['files'] ?? [])) as $file) {
            $change = $plan['files'][$file] ?? [];
            $current = @file_get_contents($file);
            if (
                !is_string($current)
                || $current !== (string) ($change['after'] ?? '')
                || is_link($file)
            ) {
                $success = false;
                continue;
            }

            $success = $this->atomicWrite($file, (string) ($change['before'] ?? '')) && $success;
        }
        return $success;
    }

    public function replaceValue(mixed $value, string $oldPath, string $newPath): mixed
    {
        if (is_string($value)) {
            return $this->replaceString($value, $this->normalizePath($oldPath), $this->normalizePath($newPath))[0];
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->replaceValue($item, $oldPath, $newPath);
        }

        return $value;
    }

    /**
     * @return array<int, string>
     */
    private function candidateFiles(): array
    {
        $roots = [
            'data/core/pages',
            'data/core/posts',
            'data/core/categories',
            'data/core/menus',
            'data/core/footer',
            'data/core/contact_forms',
            'data/themes',
        ];

        $files = [];
        foreach ($roots as $root) {
            $absoluteRoot = $this->basePath . '/' . $root;
            if (!is_dir($absoluteRoot)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absoluteRoot, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $item) {
                if (!$item->isFile() || $item->isLink()) {
                    continue;
                }

                $extension = strtolower((string) $item->getExtension());
                if (!in_array($extension, ['json', 'html'], true)) {
                    continue;
                }

                $files[] = str_replace('\\', '/', $item->getPathname());
            }
        }

        foreach ($this->fixedFiles() as $file) {
            if (is_file($file) && !is_link($file)) {
                $files[] = $file;
            }
        }

        $files = array_values(array_unique($files));
        sort($files, SORT_STRING);
        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function fixedFiles(): array
    {
        return array_map(
            fn (string $file): string => $this->basePath . '/data/' . $file,
            [
                'settings.json',
                'promo_banner_translations.json',
                'site_branding_translations.json',
                'site_routing.json',
            ]
        );
    }

    /**
     * @return array{0:string,1:int}
     */
    private function replaceString(string $value, string $oldPath, string $newPath): array
    {
        if ($oldPath === '' || $newPath === '' || $oldPath === $newPath) {
            return [$value, 0];
        }

        $escapedOldPath = str_replace('/', '\/', $oldPath);
        $escapedNewPath = str_replace('/', '\/', $newPath);
        $pairs = [
            'public\/uploads\/' . $escapedOldPath => 'public\/uploads\/' . $escapedNewPath,
            '\/uploads\/' . $escapedOldPath => '\/uploads\/' . $escapedNewPath,
            'uploads\/' . $escapedOldPath => 'uploads\/' . $escapedNewPath,
            $escapedOldPath => $escapedNewPath,
            'public/uploads/' . $oldPath => 'public/uploads/' . $newPath,
            '/uploads/' . $oldPath => '/uploads/' . $newPath,
            'uploads/' . $oldPath => 'uploads/' . $newPath,
            $oldPath => $newPath,
        ];

        $tokens = [];
        $count = 0;
        $index = 0;
        foreach ($pairs as $search => $replacement) {
            $pattern = '#(?<![A-Za-z0-9._~/-])' . preg_quote($search, '#') . '(?![A-Za-z0-9._~-])#';
            if (preg_match($pattern, $value) !== 1) {
                continue;
            }

            $token = "\x1AFLATCMS_MEDIA_" . $index++ . "\x1A";
            $updated = preg_replace($pattern, $token, $value, -1, $replaced);
            if (!is_string($updated) || $replaced < 1) {
                continue;
            }

            $value = $updated;
            $tokens[$token] = $replacement;
            $count += $replaced;
        }

        if ($tokens !== []) {
            $value = strtr($value, $tokens);
        }

        return [$value, $count];
    }

    private function normalizePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = preg_replace('#^(?:public/)?uploads/#', '', ltrim($path, '/')) ?? '';
        return trim($path, '/');
    }

    private function atomicWrite(string $file, string $content): bool
    {
        $directory = dirname($file);
        if (is_link($file) || !is_dir($directory) || !is_writable($directory)) {
            return false;
        }

        try {
            $suffix = bin2hex(random_bytes(6));
        } catch (\Throwable) {
            $suffix = str_replace('.', '', uniqid('', true));
        }

        $temp = $directory . '/.' . basename($file) . '.media-' . $suffix . '.tmp';
        if (@file_put_contents($temp, $content, LOCK_EX) === false) {
            return false;
        }

        $permissions = @fileperms($file);
        if (is_int($permissions)) {
            @chmod($temp, $permissions & 0777);
        }

        if (@rename($temp, $file)) {
            return true;
        }

        $backup = $directory . '/.' . basename($file) . '.media-' . $suffix . '.bak';
        if (!@rename($file, $backup)) {
            @unlink($temp);
            return false;
        }

        if (@rename($temp, $file)) {
            @unlink($backup);
            return true;
        }

        @rename($backup, $file);
        @unlink($temp);
        return false;
    }

    /**
     * @param array<int, string> $written
     * @param array{files?: array<string, array{before:string,after:string}>} $plan
     */
    private function restoreWritten(array $written, array $plan): bool
    {
        $success = true;
        foreach (array_reverse($written) as $writtenFile) {
            $change = $plan['files'][$writtenFile] ?? [];
            $current = @file_get_contents($writtenFile);
            if (
                !is_string($current)
                || $current !== (string) ($change['after'] ?? '')
                || is_link($writtenFile)
            ) {
                $success = false;
                continue;
            }

            $original = (string) ($change['before'] ?? '');
            $success = $this->atomicWrite($writtenFile, $original) && $success;
        }
        return $success;
    }
}
