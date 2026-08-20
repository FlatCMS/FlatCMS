<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateCoreReleaseBuilderService
{
    private const ARCHIVE_MTIME = 946684800; // 2000-01-01T00:00:00Z for reproducible ZIPs.
    private CoreUpdatePathPolicy $pathPolicy;
    private UpdateRequirementService $requirements;

    public function __construct(private ?string $basePath = null)
    {
        $this->basePath = rtrim($this->basePath ?: BASE_PATH, '/\\');
        $this->pathPolicy = new CoreUpdatePathPolicy();
        $this->requirements = new UpdateRequirementService();
    }

    /** @param array<int,string> $remove @return array<string,mixed> */
    public function build(string $version, string $outputPath, array $remove = []): array
    {
        $version = trim($version);
        if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][0-9A-Za-z.-]+)?$/', $version) !== 1) {
            throw new \RuntimeException('update_release_version_invalid');
        }
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('update_release_zip_required');
        }

        $files = $this->collectFiles();
        $removals = [];
        foreach ($remove as $path) {
            $normalized = $this->pathPolicy->assertAllowed((string) $path, 'update_release_remove_path_forbidden');
            if (isset($files[$normalized])) {
                throw new \RuntimeException('update_release_remove_conflicts_with_file:' . $normalized);
            }
            $removals[$normalized] = true;
        }
        $payloads = [];
        $hashes = [];
        foreach ($files as $relative => $absolute) {
            $contents = $this->releaseContents($relative, $absolute, $version);
            $payloads[$relative] = $contents;
            $hashes[$relative] = hash('sha256', $contents);
        }

        $manifest = [
            'schema' => 'flatcms-core-update-v1',
            'product' => 'flatcms',
            'version' => $version,
            'files' => $hashes,
            'remove' => array_keys($removals),
        ];
        $requirements = $this->releaseRequirements($version);
        if ($requirements !== []) {
            $manifest['requires_packages'] = $requirements;
        }

        $candidatePath = $outputPath . '.candidate-' . bin2hex(random_bytes(6));
        try {
            $this->writeArchive($candidatePath, $manifest, $payloads);
            $candidateSha = strtolower((string) hash_file('sha256', $candidatePath));
            $candidateSize = (int) @filesize($candidatePath);
            if ($candidateSha === '' || $candidateSize < 1) {
                throw new \RuntimeException('update_release_archive_invalid');
            }

            if (is_file($outputPath)) {
                $existingSha = strtolower((string) hash_file('sha256', $outputPath));
                if ($existingSha === '' || !hash_equals($existingSha, $candidateSha)) {
                    throw new \RuntimeException('update_release_version_immutable');
                }
                @unlink($candidatePath);
            } elseif (!@rename($candidatePath, $outputPath)) {
                throw new \RuntimeException('update_release_output_failed');
            }
        } finally {
            if (is_file($candidatePath)) {
                @unlink($candidatePath);
            }
        }

        $sha256 = strtolower((string) hash_file('sha256', $outputPath));
        $size = (int) @filesize($outputPath);
        if ($sha256 === '' || $size < 1) {
            throw new \RuntimeException('update_release_archive_invalid');
        }

        $package = [
            'catalog' => 'core',
            'slug' => 'flatcms',
            'version' => $version,
            'sha256' => $sha256,
            'size_bytes' => $size,
        ];
        $signature = $this->sign($package);

        return [
            'version' => $version,
            'file_path' => realpath($outputPath) ?: $outputPath,
            'filename' => basename($outputPath),
            'size_bytes' => $size,
            'sha256' => $sha256,
            'signature' => $signature,
            'file_count' => count($hashes),
            'signature_payload' => UpdateSignatureService::payload($package),
        ];
    }

    /** @return array<int,array{catalog:string,slug:string,version:string}> */
    private function releaseRequirements(string $version): array
    {
        $path = dirname(__DIR__) . '/Config/core-requirements.php';
        $map = is_file($path) ? require $path : [];
        if (!is_array($map)) {
            throw new \RuntimeException('update_release_requirements_invalid');
        }
        return $this->requirements->normalize($map[$version] ?? []);
    }

    /** @return array<string,string> */
    private function collectFiles(): array
    {
        $files = [];

        foreach ($this->pathPolicy->topFiles() as $relative) {
            $absolute = $this->basePath . '/' . $relative;
            if (is_file($absolute) && !is_link($absolute)) {
                $relative = $this->pathPolicy->assertAllowed($relative, 'update_release_core_path_forbidden');
                $files[$relative] = $absolute;
            }
        }

        foreach ($this->pathPolicy->roots() as $root) {
            $absoluteRoot = $this->basePath . '/' . $root;
            if (!is_dir($absoluteRoot)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absoluteRoot, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $item) {
                if (!$item->isFile() || $item->isLink()) {
                    continue;
                }
                $absolute = $item->getPathname();
                $relative = str_replace('\\', '/', ltrim(substr($absolute, strlen($this->basePath)), '/\\'));
                if ($this->excludeReleasePath($relative)) {
                    continue;
                }
                $relative = $this->pathPolicy->assertAllowed($relative, 'update_release_core_path_forbidden');
                $files[$relative] = $absolute;
            }
        }
        ksort($files);
        return $files;
    }

    private function releaseContents(string $relative, string $absolute, string $version): string
    {
        if ($relative === 'VERSION') {
            return $version . PHP_EOL;
        }
        $contents = @file_get_contents($absolute);
        if (!is_string($contents)) {
            throw new \RuntimeException('update_release_file_read_failed:' . $relative);
        }
        if ($relative === 'flatcms.json') {
            $manifest = json_decode($contents, true);
            if (!is_array($manifest)) {
                throw new \RuntimeException('update_release_core_manifest_invalid');
            }
            $manifest['version'] = $version;
            $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($json)) {
                throw new \RuntimeException('update_release_core_manifest_invalid');
            }
            return $json . PHP_EOL;
        }
        return $contents;
    }

    private function excludeReleasePath(string $relative): bool
    {
        return str_starts_with($relative, 'resources/updates/catalogs/')
            || str_starts_with($relative, 'resources/Store/')
            || str_starts_with($relative, 'app/Modules/Store/')
            || basename($relative) === '.DS_Store'
            || str_ends_with($relative, '.tmp');
    }

    /** @param array<string,mixed> $manifest @param array<string,string> $payloads */
    private function writeArchive(string $outputPath, array $manifest, array $payloads): void
    {
        $parent = dirname($outputPath);
        if (!is_dir($parent) && !@mkdir($parent, 0750, true) && !is_dir($parent)) {
            throw new \RuntimeException('update_release_output_failed');
        }
        $zip = new \ZipArchive();
        if ($zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('update_release_output_failed');
        }
        try {
            $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!method_exists($zip, 'setMtimeName')) {
                throw new \RuntimeException('update_release_zip_mtime_required');
            }
            if (!is_string($json) || !$zip->addFromString('flatcms-update.json', $json . PHP_EOL)) {
                throw new \RuntimeException('update_release_output_failed');
            }
            if (!$zip->setMtimeName('flatcms-update.json', self::ARCHIVE_MTIME)) {
                throw new \RuntimeException('update_release_output_failed:flatcms-update.json');
            }
            foreach ($payloads as $relative => $contents) {
                if (!$zip->addFromString($relative, $contents)) {
                    throw new \RuntimeException('update_release_output_failed:' . $relative);
                }
                if (!$zip->setMtimeName($relative, self::ARCHIVE_MTIME)) {
                    throw new \RuntimeException('update_release_output_failed:' . $relative);
                }
                if (str_starts_with($relative, 'bin/')) {
                    $zip->setExternalAttributesName($relative, \ZipArchive::OPSYS_UNIX, 0755 << 16);
                }
            }
        } finally {
            $zip->close();
        }
    }

    /** @param array<string,mixed> $package */
    private function sign(array $package): string
    {
        $keyPath = trim((string) env('FLATCMS_UPDATE_SIGNING_PRIVATE_KEY_FILE', ''));
        if ($keyPath === '') {
            return '';
        }
        if (!str_starts_with($keyPath, '/')) {
            $keyPath = $this->basePath . '/' . ltrim($keyPath, '/\\');
        }
        $privateKey = @file_get_contents($keyPath);
        if (!is_string($privateKey) || $privateKey === '' || !extension_loaded('openssl')) {
            throw new \RuntimeException('update_release_signing_key_invalid');
        }
        $algo = match (strtolower((string) config('extensions.signature_algo', 'sha256'))) {
            'sha384' => OPENSSL_ALGO_SHA384,
            'sha512' => OPENSSL_ALGO_SHA512,
            default => OPENSSL_ALGO_SHA256,
        };
        $signature = '';
        if (!openssl_sign(UpdateSignatureService::payload($package), $signature, $privateKey, $algo)) {
            throw new \RuntimeException('update_release_sign_failed');
        }
        return base64_encode($signature);
    }
}
