<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateCorePackageService
{
    private const SCHEMA = 'flatcms-core-update-v1';
    private const MANIFEST = 'flatcms-update.json';
    private string $extractRoot;
    private CoreUpdatePathPolicy $pathPolicy;
    private UpdateRequirementService $requirements;

    public function __construct(?string $extractRoot = null)
    {
        $this->extractRoot = $extractRoot ?: BASE_PATH . '/storage/tmp/update-manager';
        $this->pathPolicy = new CoreUpdatePathPolicy();
        $this->requirements = new UpdateRequirementService();
    }

    /** @return array<string,mixed> */
    public function prepare(string $archivePath, string $expectedVersion): array
    {
        if (!class_exists(\ZipArchive::class) || !is_file($archivePath)) {
            throw new \RuntimeException('update_package_unavailable');
        }

        $zip = new \ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('update_package_open_failed');
        }

        try {
            $manifestRaw = $zip->getFromName(self::MANIFEST);
            $manifest = json_decode(is_string($manifestRaw) ? $manifestRaw : '', true);
            if (!is_array($manifest)) {
                throw new \RuntimeException('update_package_manifest_missing');
            }
            $this->validateManifest($manifest, $expectedVersion);
            $this->validateEntries($zip, $manifest);

            $extractPath = $this->newExtractPath();
            $this->extractFiles($zip, $manifest, $extractPath);
            $this->verifyExtractedFiles($manifest, $extractPath);

            return [
                'extract_path' => $extractPath,
                'manifest' => $manifest,
            ];
        } finally {
            $zip->close();
        }
    }

    /** @param array<string,mixed> $manifest */
    private function validateManifest(array $manifest, string $expectedVersion): void
    {
        if ((string) ($manifest['schema'] ?? '') !== self::SCHEMA
            || (string) ($manifest['product'] ?? '') !== 'flatcms'
            || trim((string) ($manifest['version'] ?? '')) !== trim($expectedVersion)) {
            throw new \RuntimeException('update_package_manifest_invalid');
        }

        $files = $manifest['files'] ?? null;
        if (!is_array($files) || $files === [] || !array_key_exists('VERSION', $files)) {
            throw new \RuntimeException('update_package_files_invalid');
        }

        foreach ($files as $path => $hash) {
            $rawPath = (string) $path;
            $path = $this->pathPolicy->normalize($rawPath);
            $hash = strtolower(trim((string) $hash));
            if (!$this->pathPolicy->isCanonical($rawPath) || !$this->pathPolicy->isAllowed($path)
                || preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
                throw new \RuntimeException('update_package_files_invalid:' . ($path !== '' ? $path : $rawPath));
            }
        }

        $this->requirements->normalize($manifest['requires_packages'] ?? []);

        $remove = $manifest['remove'] ?? [];
        if (!is_array($remove)) {
            throw new \RuntimeException('update_package_remove_invalid');
        }
        foreach ($remove as $path) {
            $rawPath = (string) $path;
            $path = $this->pathPolicy->normalize($rawPath);
            if (!$this->pathPolicy->isCanonical($rawPath) || !$this->pathPolicy->isAllowed($path)
                || array_key_exists($path, $files)) {
                throw new \RuntimeException('update_package_remove_invalid:' . ($path !== '' ? $path : $rawPath));
            }
        }
    }

    /** @param array<string,mixed> $manifest */
    private function validateEntries(\ZipArchive $zip, array $manifest): void
    {
        $expected = [self::MANIFEST => true];
        foreach (array_keys((array) $manifest['files']) as $path) {
            $expected[$this->pathPolicy->normalize((string) $path)] = true;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (!is_string($entry) || $entry === '') {
                continue;
            }
            $normalized = $this->pathPolicy->normalize($entry);
            if ($normalized === '') {
                throw new \RuntimeException('update_package_path_invalid');
            }
            if (str_ends_with(str_replace('\\', '/', $entry), '/')) {
                $rawDirectory = rtrim(trim(str_replace('\\', '/', $entry)), '/');
                if ($rawDirectory !== $normalized || !$this->pathPolicy->isAllowedDirectory($normalized)) {
                    throw new \RuntimeException('update_package_entry_invalid:' . $normalized);
                }
                continue;
            }
            $isManifest = $normalized === self::MANIFEST;
            if (!$this->pathPolicy->isCanonical($entry)
                || (!$isManifest && !$this->pathPolicy->isAllowed($normalized))
                || $this->isSymlinkEntry($zip, $i) || !isset($expected[$normalized])) {
                throw new \RuntimeException('update_package_entry_invalid:' . $normalized);
            }
            unset($expected[$normalized]);
        }

        if ($expected !== []) {
            throw new \RuntimeException('update_package_entry_missing');
        }
    }

    /** @param array<string,mixed> $manifest */
    private function extractFiles(\ZipArchive $zip, array $manifest, string $extractPath): void
    {
        foreach (array_keys((array) $manifest['files']) as $relative) {
            $relative = $this->pathPolicy->normalize((string) $relative);
            $stream = $zip->getStream($relative);
            if (!is_resource($stream)) {
                throw new \RuntimeException('update_package_extract_failed');
            }
            $target = $extractPath . '/' . $relative;
            $parent = dirname($target);
            if (!is_dir($parent) && !@mkdir($parent, 0750, true) && !is_dir($parent)) {
                fclose($stream);
                throw new \RuntimeException('update_package_extract_failed');
            }

            $out = @fopen($target, 'wb');
            if (!is_resource($out)) {
                fclose($stream);
                throw new \RuntimeException('update_package_extract_failed');
            }
            $copied = stream_copy_to_stream($stream, $out);
            fclose($stream);
            fclose($out);
            if ($copied === false) {
                throw new \RuntimeException('update_package_extract_failed');
            }
        }
    }

    /** @param array<string,mixed> $manifest */
    private function verifyExtractedFiles(array $manifest, string $extractPath): void
    {
        foreach ((array) $manifest['files'] as $relative => $expectedHash) {
            $relative = $this->pathPolicy->normalize((string) $relative);
            $target = $extractPath . '/' . $relative;
            $actual = is_file($target) ? strtolower((string) hash_file('sha256', $target)) : '';
            if ($actual === '' || !hash_equals(strtolower((string) $expectedHash), $actual)) {
                throw new \RuntimeException('update_package_file_hash_invalid');
            }
        }

        $version = trim((string) @file_get_contents($extractPath . '/VERSION'));
        if ($version !== trim((string) ($manifest['version'] ?? ''))) {
            throw new \RuntimeException('update_package_version_invalid');
        }
    }

    private function newExtractPath(): string
    {
        if (!is_dir($this->extractRoot) && !@mkdir($this->extractRoot, 0750, true) && !is_dir($this->extractRoot)) {
            throw new \RuntimeException('update_package_extract_failed');
        }
        $path = $this->extractRoot . '/extract-' . bin2hex(random_bytes(12));
        if (!@mkdir($path, 0750, true) && !is_dir($path)) {
            throw new \RuntimeException('update_package_extract_failed');
        }
        return $path;
    }

    private function isSymlinkEntry(\ZipArchive $zip, int $index): bool
    {
        $opsys = 0;
        $attributes = 0;
        if (!$zip->getExternalAttributesIndex($index, $opsys, $attributes)) {
            return false;
        }
        if ($opsys !== \ZipArchive::OPSYS_UNIX) {
            return false;
        }
        $mode = ($attributes >> 16) & 0xF000;
        return $mode === 0xA000;
    }
}
