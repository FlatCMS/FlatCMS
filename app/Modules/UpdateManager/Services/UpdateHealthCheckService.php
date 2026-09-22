<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/UpdateManager/Services/UpdateHealthCheckService.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Core\RuntimeProbe;
use App\Core\Storage\ApplicationLock;
use App\Core\Storage\StoragePathGuard;

final class UpdateHealthCheckService
{
    public function __construct(private ?string $phpBinary = null)
    {
    }

    /** @param array<string,mixed> $manifest */
    public function check(string $basePath, array $manifest): bool
    {
        try {
            return ApplicationLock::for($basePath)->exclusive(function () use ($basePath, $manifest): bool {
                $paths = new StoragePathGuard($basePath);
                $expected = trim((string) ($manifest['version'] ?? ''));
                if ($expected === '' || trim((string) file_get_contents($paths->resolve('VERSION'))) !== $expected) { return false; }
                $core = json_decode((string) file_get_contents($paths->resolve('flatcms.json')), true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($core) || ($core['version'] ?? '') !== $expected || !$this->verifyAppliedManifest($basePath, $manifest)) { return false; }
                // No CLI/proc_open means unverified, never success based on hashes alone.
                return (new RuntimeProbe($this->phpBinary))->check($basePath, $expected, ['core-update']);
            }, ['core-update']);
        } catch (\Throwable) { return false; }
    }

    /** @param array<string,mixed> $manifest */
    private function verifyAppliedManifest(string $basePath, array $manifest): bool
    {
        $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
        if ($files === []) {
            return false;
        }
        $paths = new StoragePathGuard($basePath);
        foreach ($files as $relative => $expectedHash) {
            $target = $paths->resolve((string) $relative);
            $expected = strtolower(trim((string) $expectedHash));
            if (!is_file($target) || preg_match('/^[a-f0-9]{64}$/', $expected) !== 1) {
                return false;
            }
            $actual = hash_file('sha256', $target);
            if (!is_string($actual) || !hash_equals($expected, strtolower($actual))) {
                return false;
            }
        }
        foreach ((array) ($manifest['remove'] ?? []) as $relative) {
            if (file_exists($paths->resolve((string) $relative))) {
                return false;
            }
        }
        return true;
    }

}
