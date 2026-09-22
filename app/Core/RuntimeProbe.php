<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/RuntimeProbe.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Core;

use App\Core\Storage\ApplicationLock;
use App\Core\Storage\StoragePathGuard;

/** Fresh login-route boot, not a substitute for HTTP/frontend acceptance. */
final class RuntimeProbe
{
    public function __construct(private ?string $phpBinary = null, private int $timeoutMilliseconds = 10000)
    {
    }

    public function check(string $basePath, string $expectedVersion, array $recoverScopes = []): bool
    {
        if (!function_exists('proc_open') || PHP_OS_FAMILY === 'Windows'
            || $this->timeoutMilliseconds < 1 || $this->timeoutMilliseconds > 60000
            || preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][0-9A-Za-z.-]+)?$/D', $expectedVersion) !== 1) {
            return false;
        }
        try {
            $recoverScopes = $this->normalizeRecoverScopes($recoverScopes);
            if ($recoverScopes === null) { return false; }
            $root = (new StoragePathGuard($basePath))->root();
            $lock = ApplicationLock::for($root);
            return $lock->exclusive(fn (): bool => $this->run($root, $expectedVersion, $lock, $recoverScopes), $recoverScopes);
        } catch (\Throwable) { return false; }
    }

    private function run(string $root, string $expectedVersion, ApplicationLock $lock, array $recoverScopes): bool
    {
        $binary = $this->resolveBinary();
        if ($binary === null) { return false; }
        $process = @proc_open([$binary, '-d', 'display_errors=0', '-d', 'opcache.enable_cli=0',
            __DIR__ . '/RuntimeProbe/worker.php', $root, $expectedVersion, implode(',', $recoverScopes)],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']] + $lock->probeDescriptors(),
            $pipes, $root, null, ['bypass_shell' => true]);
        if (!is_resource($process)) { return false; }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $bytes = 0;
        $exit = -1;
        $finished = false;
        $deadline = hrtime(true) + $this->timeoutMilliseconds * 1000000;
        try {
            do {
                foreach ([1, 2] as $fd) {
                    $chunk = fread($pipes[$fd], 16384);
                    if ($chunk === false) { return false; }
                    $bytes += strlen($chunk);
                    if ($bytes > 131072) { return false; }
                    if ($fd === 1) { $stdout .= $chunk; }
                }
                $status = proc_get_status($process);
                if (!$status['running']) {
                    $finished = true;
                    $exit = $status['exitcode'];
                    foreach ([1, 2] as $fd) {
                        $chunk = stream_get_contents($pipes[$fd], 131073);
                        if ($chunk === false) { return false; }
                        $bytes += strlen($chunk);
                        if ($fd === 1) { $stdout .= $chunk; }
                    }
                    break;
                }
                usleep(10000);
            } while (hrtime(true) < $deadline);
        } finally {
            if (!$finished) { proc_terminate($process, 9); }
            fclose($pipes[1]); fclose($pipes[2]);
            proc_close($process);
        }
        $result = json_decode($stdout, true);
        return $finished && $exit === 0 && $bytes <= 131072 && is_array($result)
            && ($result['protocol'] ?? null) === 1 && ($result['ok'] ?? null) === true
            && ($result['version'] ?? null) === $expectedVersion;
    }

    private function resolveBinary(): ?string
    {
        $configured = $this->phpBinary ?? trim((string) ($_ENV['FLATCMS_PHP_CLI'] ?? getenv('FLATCMS_PHP_CLI')));
        $candidates = $configured !== '' ? [$configured] : [PHP_SAPI === 'cli' ? PHP_BINARY : '',
            dirname(PHP_BINARY) . '/php', dirname(PHP_BINARY, 2) . '/bin/php'];
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    /** @return list<string>|null */
    private function normalizeRecoverScopes(array $recoverScopes): ?array
    {
        $allowed = ['core-update', 'full-restoration', 'site-restoration'];
        $normalized = [];
        foreach ($recoverScopes as $scope) {
            if (!is_string($scope) || !in_array($scope, $allowed, true)) { return null; }
            $normalized[$scope] = true;
        }
        return array_keys($normalized);
    }
}
