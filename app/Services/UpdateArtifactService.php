<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services;

final class UpdateArtifactService
{
    /** @param array<string, mixed> $package */
    public function hasArtifactBinding(array $package): bool
    {
        $artifact = is_array($package['artifact'] ?? null) ? $package['artifact'] : [];
        return trim((string) ($artifact['file'] ?? '')) !== '';
    }

    /**
     * Resolve an artifact declared relative to the private update-artifact root.
     *
     * @param array<string, mixed> $package
     * @return array<string, mixed>|null
     */
    public function resolveArtifact(array $package): ?array
    {
        if (!$this->hasArtifactBinding($package)) {
            return null;
        }

        $artifact = (array) $package['artifact'];
        $relativeFile = $this->normalizeRelativeFile((string) ($artifact['file'] ?? ''));
        if ($relativeFile === '') {
            return null;
        }

        $root = $this->artifactRoot();
        $rootReal = realpath($root);
        if ($rootReal === false || !is_dir($rootReal)) {
            return null;
        }

        $candidate = $rootReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFile);
        $fileReal = realpath($candidate);
        if ($fileReal === false || !is_file($fileReal)) {
            return null;
        }

        $rootPrefix = rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($fileReal, $rootPrefix)) {
            return null;
        }

        $size = (int) @filesize($fileReal);
        if ($size < 1) {
            return null;
        }

        $sha256 = strtolower(trim((string) ($package['sha256'] ?? $artifact['sha256'] ?? '')));
        if ($sha256 === '' || preg_match('/^[a-f0-9]{64}$/', $sha256) !== 1) {
            return null;
        }

        $filename = basename(trim((string) ($artifact['filename'] ?? basename($fileReal))));
        if ($filename === '' || $filename === '.' || $filename === '..') {
            $filename = basename($fileReal);
        }

        return [
            'product_id' => trim((string) ($package['slug'] ?? '')),
            'version' => trim((string) ($package['version'] ?? '')),
            'file_path' => $fileReal,
            'filename' => $filename,
            'size_bytes' => $size,
            'sha256' => $sha256,
            'published_at' => trim((string) ($package['published_at'] ?? '')),
            'release_status' => trim((string) ($package['availability'] ?? 'draft')),
        ];
    }

    public function artifactRoot(): string
    {
        $configured = trim((string) env('FLATCMS_UPDATE_ARTIFACT_ROOT', ''));
        if ($configured !== '') {
            return $this->absolutePath($configured);
        }

        return BASE_PATH . '/storage/update-artifacts';
    }

    private function normalizeRelativeFile(string $file): string
    {
        $file = trim(str_replace('\\', '/', $file));
        if ($file === '' || str_contains($file, "\0") || str_starts_with($file, '/')) {
            return '';
        }

        $parts = [];
        foreach (explode('/', $file) as $part) {
            $part = trim($part);
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                return '';
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    private function absolutePath(string $path): string
    {
        if ($path === '') {
            return BASE_PATH . '/storage/update-artifacts';
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return BASE_PATH . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}
