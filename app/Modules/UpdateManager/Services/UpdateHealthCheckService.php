<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateHealthCheckService
{
    public function __construct(private ?string $phpBinary = null)
    {
        $this->phpBinary ??= PHP_BINARY;
    }

    /** @param array<string,mixed> $manifest */
    public function check(string $basePath, array $manifest): bool
    {
        $expected = trim((string) ($manifest['version'] ?? ''));
        if ($expected === '' || trim((string) @file_get_contents($basePath . '/VERSION')) !== $expected) {
            return false;
        }
        $coreManifest = json_decode((string) @file_get_contents($basePath . '/flatcms.json'), true);
        if (!is_array($coreManifest) || trim((string) ($coreManifest['version'] ?? '')) !== $expected) {
            return false;
        }
        foreach (['app/Bootstrap/Autoloader.php', 'app/Core/App.php', 'app/Helpers/functions.php', 'public/index.php'] as $critical) {
            if (!is_file($basePath . '/' . $critical)) {
                return false;
            }
        }

        if (is_file((string) $this->phpBinary)) {
            if (function_exists('proc_open')) {
                return $this->probeWithProcOpen($basePath, $expected);
            }
            if (function_exists('shell_exec')) {
                return $this->probeWithShellExec($basePath, $expected);
            }
        }

        return $this->verifyAppliedManifest($basePath, $manifest);
    }

    private function probeWithProcOpen(string $basePath, string $expected): bool
    {
        $pipes = [];
        $process = @proc_open(
            [(string) $this->phpBinary, '-d', 'display_errors=0', '-r', $this->probeCode($basePath)],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (!is_resource($process)) {
            return false;
        }
        $stdout = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        return $exit === 0 && $this->reportedVersionMatches((string) $stdout, $expected);
    }

    private function probeWithShellExec(string $basePath, string $expected): bool
    {
        $command = escapeshellarg((string) $this->phpBinary)
            . ' -d display_errors=0 -r '
            . escapeshellarg($this->probeCode($basePath))
            . ' 2>/dev/null';
        $stdout = @shell_exec($command);
        return is_string($stdout) && $this->reportedVersionMatches($stdout, $expected);
    }

    private function reportedVersionMatches(string $reported, string $expected): bool
    {
        if (preg_match('/[0-9]+(?:\\.[0-9A-Za-z_-]+)+/', trim($reported), $match) !== 1) {
            return false;
        }
        return version_compare((string) $match[0], $expected, '==');
    }

    /** @param array<string,mixed> $manifest */
    private function verifyAppliedManifest(string $basePath, array $manifest): bool
    {
        $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
        if ($files === []) {
            return false;
        }
        foreach ($files as $relative => $expectedHash) {
            $target = $basePath . '/' . ltrim((string) $relative, '/');
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
            if (file_exists($basePath . '/' . ltrim((string) $relative, '/'))) {
                return false;
            }
        }
        return true;
    }

    private function probeCode(string $basePath): string
    {
        $base = var_export($basePath, true);
        return "define('BASE_PATH', {$base});"
            . "define('APP_PATH', BASE_PATH . '/app');"
            . "define('PUBLIC_PATH', BASE_PATH . '/public');"
            . "define('DATA_PATH', BASE_PATH . '/data');"
            . "define('STORAGE_PATH', BASE_PATH . '/storage');"
            . "define('CONFIG_PATH', BASE_PATH . '/config');"
            . "require APP_PATH . '/Bootstrap/Autoloader.php';"
            . "\\App\\Core\\I18n::init('fr-FR');"
            . "echo flatcms_version();";
    }
}
