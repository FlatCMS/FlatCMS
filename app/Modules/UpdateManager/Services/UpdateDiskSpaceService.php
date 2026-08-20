<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateDiskSpaceService
{
    private const MIN_MARGIN_BYTES = 67108864;

    public function assertBeforeDownload(array $package, string $basePath): void
    {
        $artifactBytes = max(1, (int) ($package['size_bytes'] ?? 0));
        $required = max(self::MIN_MARGIN_BYTES, ($artifactBytes * 4) + self::MIN_MARGIN_BYTES);
        $this->assertFree($basePath, $required);
    }

    /** @param array<string,mixed> $prepared */
    public function assertBeforeApply(array $prepared, string $basePath): void
    {
        $manifest = is_array($prepared['manifest'] ?? null) ? $prepared['manifest'] : [];
        $extractPath = rtrim((string) ($prepared['extract_path'] ?? ''), '/\\');
        if ($manifest === [] || $extractPath === '') {
            throw new \RuntimeException('update_disk_preflight_invalid');
        }

        $payloadBytes = 0;
        $backupBytes = 0;
        $largestFile = 0;
        foreach (array_keys((array) ($manifest['files'] ?? [])) as $relative) {
            $source = $extractPath . '/' . $relative;
            $size = is_file($source) ? max(0, (int) @filesize($source)) : 0;
            $payloadBytes += $size;
            $largestFile = max($largestFile, $size);

            $target = rtrim($basePath, '/\\') . '/' . $relative;
            if (is_file($target)) {
                $backupBytes += max(0, (int) @filesize($target));
            }
        }
        foreach ((array) ($manifest['remove'] ?? []) as $relative) {
            $target = rtrim($basePath, '/\\') . '/' . $relative;
            if (is_file($target)) {
                $backupBytes += max(0, (int) @filesize($target));
            }
        }

        $required = $backupBytes + $largestFile + self::MIN_MARGIN_BYTES;
        $required = max($required, (int) ceil($payloadBytes * 0.25) + self::MIN_MARGIN_BYTES);
        $this->assertFree($basePath, $required);
    }

    private function assertFree(string $basePath, int $required): void
    {
        $probe = is_dir($basePath . '/storage') ? $basePath . '/storage' : $basePath;
        $free = @disk_free_space($probe);
        if ($free === false) {
            throw new \RuntimeException('update_disk_space_unknown');
        }
        if ((float) $free < (float) $required) {
            throw new \RuntimeException('update_disk_space_insufficient');
        }
    }
}
