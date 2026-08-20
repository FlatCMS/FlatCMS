<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateWorkerService
{
    /** @return array<string,mixed> */
    public function applyCore(string $version): array
    {
        $worker = BASE_PATH . '/app/Modules/UpdateManager/bin/update-worker.php';
        $php = $this->resolvePhpCli();

        if ($php !== '' && is_file($worker)) {
            if (function_exists('proc_open')) {
                return $this->applyWithProcOpen($php, $worker, $version);
            }
            if (function_exists('shell_exec')) {
                return $this->applyWithShellExec($php, $worker, $version);
            }
        }

        return (new UpdateApplyService())->apply('core', 'flatcms', $version);
    }

    /** @return array<string,mixed> */
    public function dispatchCore(string $version): array
    {
        $worker = BASE_PATH . '/app/Modules/UpdateManager/bin/update-worker.php';
        $php = $this->resolvePhpCli();

        if (PHP_OS_FAMILY === 'Windows') {
            return $this->applyCore($version);
        }
        if ($php === '' || !is_file($worker)) {
            throw new \RuntimeException('update_worker_async_unavailable');
        }
        if (function_exists('proc_open')) {
            return $this->dispatchUnix($php, $worker, $version);
        }
        if (function_exists('shell_exec')) {
            return $this->dispatchUnixWithShellExec($php, $worker, $version);
        }
        throw new \RuntimeException('update_worker_async_unavailable');
    }

    /** @return array<string,mixed> */
    private function dispatchUnix(string $php, string $worker, string $version): array
    {
        $logDir = BASE_PATH . '/storage/logs/update-manager';
        if (!is_dir($logDir) && !@mkdir($logDir, 0750, true) && !is_dir($logDir)) {
            throw new \RuntimeException('update_worker_dispatch_log_failed');
        }

        $jobId = gmdate('YmdHis') . '-' . bin2hex(random_bytes(6));
        $logPath = $logDir . '/worker-' . $jobId . '.log';
        if (@file_put_contents($logPath, '') === false) {
            throw new \RuntimeException('update_worker_dispatch_log_failed');
        }
        @chmod($logPath, 0640);

        $parts = array_map('escapeshellarg', [$php, $worker, 'core', 'flatcms', $version]);
        $command = 'nohup ' . implode(' ', $parts) . ' >> ' . escapeshellarg($logPath) . ' 2>&1 < /dev/null & echo $!';
        $pipes = [];
        $process = @proc_open(['/bin/sh', '-c', $command], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        if (!is_resource($process)) {
            throw new \RuntimeException('update_worker_dispatch_failed');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        $pid = (int) trim((string) $stdout);
        if ($exit !== 0 || $pid < 1) {
            throw new \RuntimeException('update_worker_dispatch_failed' . (trim((string) $stderr) !== '' ? ':' . trim((string) $stderr) : ''));
        }

        return [
            'status' => 'started',
            'version' => $version,
            'job_id' => $jobId,
            'pid' => $pid,
            'log_path' => $logPath,
        ];
    }

    /** @return array<string,mixed> */
    private function dispatchUnixWithShellExec(string $php, string $worker, string $version): array
    {
        $logDir = BASE_PATH . '/storage/logs/update-manager';
        if (!is_dir($logDir) && !@mkdir($logDir, 0750, true) && !is_dir($logDir)) {
            throw new \RuntimeException('update_worker_dispatch_log_failed');
        }

        $jobId = gmdate('YmdHis') . '-' . bin2hex(random_bytes(6));
        $logPath = $logDir . '/worker-' . $jobId . '.log';
        if (@file_put_contents($logPath, '') === false) {
            throw new \RuntimeException('update_worker_dispatch_log_failed');
        }
        @chmod($logPath, 0640);

        $parts = array_map('escapeshellarg', [$php, $worker, 'core', 'flatcms', $version]);
        $command = 'nohup ' . implode(' ', $parts) . ' >> ' . escapeshellarg($logPath) . ' 2>&1 < /dev/null & echo $!';
        $output = @shell_exec($command);
        $pid = is_string($output) ? (int) trim($output) : 0;
        if ($pid < 1) {
            throw new \RuntimeException('update_worker_dispatch_failed');
        }

        return [
            'status' => 'started',
            'version' => $version,
            'job_id' => $jobId,
            'pid' => $pid,
            'log_path' => $logPath,
        ];
    }

    /** @return array<string,mixed> */
    private function applyWithProcOpen(string $php, string $worker, string $version): array
    {
        $command = [$php, $worker, 'core', 'flatcms', $version];
        $pipes = [];
        $process = @proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        if (!is_resource($process)) {
            return (new UpdateApplyService())->apply('core', 'flatcms', $version);
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        return $this->decodeWorkerResult((string) $stdout, (string) $stderr, $exit);
    }

    /** @return array<string,mixed> */
    private function applyWithShellExec(string $php, string $worker, string $version): array
    {
        $parts = array_map('escapeshellarg', [$php, $worker, 'core', 'flatcms', $version]);
        $command = implode(' ', $parts) . ' 2>&1; code=$?; printf "\\n__FLATCMS_EXIT__%s\\n" "$code"';
        $output = @shell_exec($command);
        if (!is_string($output) || preg_match('/^(.*)\\n__FLATCMS_EXIT__(\\d+)\\s*$/s', $output, $match) !== 1) {
            return (new UpdateApplyService())->apply('core', 'flatcms', $version);
        }

        $body = trim((string) $match[1]);
        $exit = (int) $match[2];
        $stdout = $this->extractMarkedPayload($body, '__FLATCMS_RESULT__');
        $stderr = $this->extractMarkedPayload($body, '__FLATCMS_ERROR__');
        if ($stdout === '' && $stderr === '') {
            $plain = $this->extractJsonPayload($body);
            if ($plain !== '') {
                $decoded = json_decode($plain, true);
                if (is_array($decoded) && !empty($decoded['ok'])) {
                    $stdout = $plain;
                } else {
                    $stderr = $plain;
                }
            }
        }
        if ($stdout === '' && $stderr === '') {
            return (new UpdateApplyService())->apply('core', 'flatcms', $version);
        }
        return $this->decodeWorkerResult($stdout, $stderr, $exit);
    }

    private function extractMarkedPayload(string $output, string $marker): string
    {
        $position = strrpos($output, $marker);
        if ($position === false) {
            return '';
        }
        $payload = substr($output, $position + strlen($marker));
        $nextMarker = strpos($payload, '__FLATCMS_');
        if ($nextMarker !== false) {
            $payload = substr($payload, 0, $nextMarker);
        }
        return trim($payload);
    }

    private function extractJsonPayload(string $output): string
    {
        $lines = preg_split('/\R/', trim($output)) ?: [];
        foreach (array_reverse($lines) as $line) {
            $line = trim((string) $line);
            if ($line === '' || $line[0] !== '{') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded) && array_key_exists('ok', $decoded)) {
                return $line;
            }
        }
        return '';
    }

    /** @return array<string,mixed> */
    private function decodeWorkerResult(string $stdout, string $stderr, int $exit): array
    {
        $marked = $this->extractMarkedPayload($stdout, '__FLATCMS_RESULT__');
        if ($marked !== '') {
            $stdout = $marked;
        }
        $markedError = $this->extractMarkedPayload($stderr, '__FLATCMS_ERROR__');
        if ($markedError !== '') {
            $stderr = $markedError;
        }
        $payload = json_decode(trim($stdout), true);
        if ($exit !== 0 || !is_array($payload) || empty($payload['ok'])) {
            $errorPayload = json_decode(trim($stderr), true);
            $error = is_array($errorPayload) ? (string) ($errorPayload['error'] ?? '') : trim($stderr);
            throw new \RuntimeException($error !== '' ? $error : 'update_worker_failed');
        }
        return is_array($payload['result'] ?? null) ? $payload['result'] : [];
    }

    private function resolvePhpCli(): string
    {
        $candidates = [];
        $configured = trim((string) env('FLATCMS_PHP_CLI', ''));
        if ($configured !== '') {
            $candidates[] = $configured;
        }
        if (basename(PHP_BINARY) === 'php' || basename(PHP_BINARY) === 'php.exe') {
            $candidates[] = PHP_BINARY;
        }
        $candidates[] = dirname(PHP_BINARY) . '/php';

        $versionDigits = PHP_MAJOR_VERSION . PHP_MINOR_VERSION;
        $candidates[] = '/www/server/php/' . $versionDigits . '/bin/php';
        foreach (['/usr/bin/php', '/usr/local/bin/php', '/www/server/php/85/bin/php', '/www/server/php/84/bin/php', '/www/server/php/83/bin/php'] as $candidate) {
            $candidates[] = $candidate;
        }

        foreach (array_values(array_unique($candidates)) as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }
            if ($this->isRunnablePhpCli($candidate)) {
                return $candidate;
            }
        }
        return '';
    }

    private function isRunnablePhpCli(string $candidate): bool
    {
        if (@is_file($candidate) && @is_executable($candidate)) {
            return true;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            return false;
        }

        $probe = escapeshellarg($candidate) . ' -r ' . escapeshellarg('echo PHP_SAPI === "cli" ? PHP_VERSION_ID : "";');
        if (function_exists('proc_open')) {
            $pipes = [];
            $process = @proc_open(['/bin/sh', '-c', $probe], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
            if (is_resource($process)) {
                $stdout = stream_get_contents($pipes[1]);
                stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($process);
                if ($exit === 0 && preg_match('/^\d+$/', trim((string) $stdout)) === 1) {
                    return true;
                }
            }
        }
        if (!function_exists('shell_exec')) {
            return false;
        }
        $output = @shell_exec($probe . ' 2>/dev/null');
        return is_string($output) && preg_match('/^\d+$/', trim($output)) === 1;
    }
}
