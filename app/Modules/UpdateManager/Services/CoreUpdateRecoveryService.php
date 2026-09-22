<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/UpdateManager/Services/CoreUpdateRecoveryService.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Core\Storage\ApplicationLock;
use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\StoragePathGuard;

/** A metadata-only Core rollback plan backed by the capsule's existing ZIP. */
final class CoreUpdateRecoveryService
{
    private StoragePathGuard $paths;
    private RecoveryStateStore $journal;
    private AtomicFileWriter $writer;
    private CoreUpdatePathPolicy $policy;

    public function __construct(string $basePath, ?RecoveryStateStore $journal = null)
    {
        $this->paths = new StoragePathGuard($basePath);
        $this->journal = $journal ?? new RecoveryStateStore($this->paths->root());
        $this->writer = new AtomicFileWriter($this->paths->root(), new FileLockManager($this->paths->root() . '/storage/cache/locks/core-update', 10000));
        $this->policy = new CoreUpdatePathPolicy();
    }

    public function prepare(array $manifest, string $extractPath, string $recoveryId): array
    {
        return $this->exclusive(function () use ($manifest, $extractPath, $recoveryId): array {
            $state = $this->journal->read();
            if (($state['recovery_id'] ?? '') !== $recoveryId || isset($state['core_transaction'])
                || ($state['target_version'] ?? '') !== ($manifest['version'] ?? null)
                || !in_array($state['status'] ?? '', ['backup_ready', 'updating'], true)
                || empty($manifest['files']['VERSION']) || empty($manifest['files']['flatcms.json'])) {
                throw new \RuntimeException('update_apply_plan_invalid');
            }
            $backup = (new RecoveryBackupReference($this->paths->root()))->verify($state);
            if (!is_dir($extractPath)) { throw new \RuntimeException('update_apply_source_missing'); }
            $source = new StoragePathGuard($extractPath);
            $entries = [];
            foreach ($manifest['files'] as $path => $hash) {
                $path = $this->path($path);
                $file = $source->resolve($path);
                $after = $this->inspect($file);
                if ($after === null || $after['sha256'] !== $hash) { throw new \RuntimeException('update_apply_source_invalid'); }
                $before = $this->generation($path);
                $after['mode'] = str_starts_with($path, 'bin/') ? 0755 : ($before['mode'] ?? 0644);
                $entries[$path] = ['path' => $path, 'before' => $before, 'after' => $after];
            }
            foreach ($manifest['remove'] ?? [] as $path) {
                $path = $this->path($path);
                if (isset($entries[$path])) { throw new \RuntimeException('update_apply_duplicate_target'); }
                $entries[$path] = ['path' => $path, 'before' => $this->generation($path), 'after' => null];
            }
            $plan = ['schema' => 1, 'recovery_id' => $recoveryId, 'phase' => 'prepared',
                'version' => $manifest['version'], 'operations' => array_values($entries)];
            $state['core_transaction'] = $plan;
            $this->validate($state, $backup['manifest']);
            $this->journal->write($state);
            return $plan;
        });
    }

    /** Caller retains application exclusion from preparation through acceptance. */
    public function applyFiles(string $extractPath, ?callable $checkpoint = null): void
    {
        $this->exclusive(function () use ($extractPath, $checkpoint): void {
            $state = $this->journal->read();
            $backup = (new RecoveryBackupReference($this->paths->root()))->verify($state);
            $plan = $this->validate($state, $backup['manifest']);
            if ($plan['phase'] !== 'prepared') { throw new \RuntimeException('update_apply_plan_invalid'); }
            $this->verify($plan, 'before');
            if (!is_dir($extractPath)) { throw new \RuntimeException('update_apply_source_missing'); }
            $source = new StoragePathGuard($extractPath);
            foreach ($plan['operations'] as $entry) {
                if ($this->matches($entry['before'], $entry['after'])) { continue; }
                if ($entry['after'] === null) { $this->writer->delete($entry['path']); }
                else {
                    $stream = fopen($source->resolve($entry['path']), 'rb');
                    if (!is_resource($stream)) { throw new \RuntimeException('update_apply_source_missing'); }
                    try { $this->replace($entry['path'], $stream, $entry['after']); }
                    finally { fclose($stream); }
                }
                if ($checkpoint !== null) { $checkpoint($entry['path']); }
            }
            $this->verify($plan, 'after');
        });
    }

    public function markCommitted(): void
    {
        $this->exclusive(function (): void {
            $state = $this->journal->read();
            $backup = (new RecoveryBackupReference($this->paths->root()))->verify($state);
            $plan = $this->validate($state, $backup['manifest']);
            if ($plan['phase'] !== 'prepared') { throw new \RuntimeException('update_apply_plan_invalid'); }
            $this->verify($plan, 'after');
            $state['core_transaction']['phase'] = 'committed';
            $this->journal->write($state);
        });
    }

    public function rollback(): array
    {
        return $this->exclusive(function (): array {
            $state = $this->journal->read();
            $backup = (new RecoveryBackupReference($this->paths->root()))->verify($state);
            $plan = $this->validate($state, $backup['manifest']);
            foreach ($plan['operations'] as $entry) {
                $current = $this->generation($entry['path']);
                if (!$this->matches($current, $entry['before']) && !$this->matches($current, $entry['after'])) {
                    throw new \RuntimeException('update_rollback_generation_conflict');
                }
            }
            $zip = new \ZipArchive();
            if ($zip->open($backup['path']) !== true) { throw new \RuntimeException('update_rollback_backup_open_failed'); }
            $count = 0;
            try {
                foreach (array_reverse($plan['operations']) as $entry) {
                    if ($this->matches($this->generation($entry['path']), $entry['before'])) { continue; }
                    if ($entry['before'] === null) { $this->writer->delete($entry['path']); }
                    else {
                        $stream = $zip->getStream('files/' . $entry['path']);
                        if (!is_resource($stream)) { throw new \RuntimeException('update_rollback_file_missing'); }
                        try { $this->replace($entry['path'], $stream, $entry['before']); }
                        finally { fclose($stream); }
                    }
                    $count++;
                }
                $this->verify($plan, 'before');
                $state['core_transaction']['phase'] = 'rolled_back';
                $this->journal->write($state);
            } finally { $zip->close(); }
            return ['restored_files_count' => $count, 'flatcms_version' => $state['from_version']];
        });
    }

    private function exclusive(callable $operation): mixed
    {
        return ApplicationLock::for($this->paths->root())->exclusive(
            fn () => $this->journal->exclusive($operation), ['core-update']);
    }

    private function validate(array $state, array $backup): array
    {
        $plan = $state['core_transaction'] ?? null;
        if (!is_array($plan) || ($plan['schema'] ?? null) !== 1
            || preg_match('/^[0-9]{14}-[a-f0-9]{12}$/D', (string) ($state['recovery_id'] ?? '')) !== 1
            || ($plan['recovery_id'] ?? null) !== ($state['recovery_id'] ?? '')
            || ($state['recovery_id'] ?? null) !== ($state['full_backup_id'] ?? '')
            || ($backup['backup_id'] ?? null) !== ($state['full_backup_id'] ?? '')
            || ($plan['version'] ?? null) !== ($state['target_version'] ?? '')
            || !in_array($plan['phase'] ?? '', ['prepared', 'committed', 'rolled_back'], true)
            || !is_array($plan['operations'] ?? null) || $plan['operations'] === []) {
            throw new \RuntimeException('update_rollback_plan_invalid');
        }
        $seen = [];
        foreach ($plan['operations'] as $entry) {
            if (!is_array($entry) || !isset($entry['path']) || !array_key_exists('before', $entry) || !array_key_exists('after', $entry)) {
                throw new \RuntimeException('update_rollback_plan_invalid');
            }
            $path = $this->path($entry['path']);
            $key = StoragePathGuard::foldWindowsCase($path);
            if (isset($seen[$key])) { throw new \RuntimeException('update_apply_duplicate_target'); }
            $seen[$key] = true;
            foreach (['before', 'after'] as $generation) {
                $value = $entry[$generation];
                if ($value !== null && (!is_array($value) || !is_string($value['sha256'] ?? null)
                    || preg_match('/^[a-f0-9]{64}$/D', $value['sha256']) !== 1
                    || !is_int($value['size'] ?? null) || $value['size'] < 0
                    || !is_int($value['mode'] ?? null) || $value['mode'] < 0 || $value['mode'] > 0777)) {
                    throw new \RuntimeException('update_rollback_plan_invalid');
                }
            }
            $meta = $backup['files'][$path] ?? null;
            $archived = is_array($meta) && empty($meta['secret'])
                ? ['sha256' => $meta['sha256'], 'size' => $meta['size_bytes'], 'mode' => $meta['mode']] : null;
            if (!$this->matches($entry['before'], $archived)) { throw new \RuntimeException('update_rollback_backup_generation_mismatch'); }
        }
        foreach (array_keys($seen) as $path) {
            for ($parent = dirname($path); $parent !== '.'; $parent = dirname($parent)) {
                if (isset($seen[$parent])) { throw new \RuntimeException('update_apply_duplicate_target'); }
            }
        }
        return $plan;
    }

    private function path(mixed $path): string
    {
        if (!is_string($path)) { throw new \RuntimeException('update_apply_plan_invalid'); }
        $this->policy->assertAllowed($path, 'update_apply_core_path_forbidden');
        return $this->paths->relative($path);
    }

    private function generation(string $path): ?array { return $this->inspect($this->paths->resolve($path)); }

    private function inspect(string $path): ?array
    {
        clearstatcache(true, $path);
        if (!file_exists($path)) { return null; }
        $stat = lstat($path);
        if (!is_file($path) || is_link($path) || !is_array($stat) || $stat['nlink'] !== 1) { throw new \RuntimeException('update_apply_target_invalid'); }
        $hash = hash_file('sha256', $path);
        if (!is_string($hash)) { throw new \RuntimeException('update_apply_target_invalid'); }
        return ['sha256' => $hash, 'size' => $stat['size'], 'mode' => $stat['mode'] & 0777];
    }

    private function matches(?array $left, ?array $right): bool
    {
        if ($left === null || $right === null) { return $left === $right; }
        return $left['sha256'] === $right['sha256'] && $left['size'] === $right['size']
            && (PHP_OS_FAMILY === 'Windows' || $left['mode'] === $right['mode']);
    }

    private function verify(array $plan, string $generation): void
    {
        foreach ($plan['operations'] as $entry) {
            if (!$this->matches($this->generation($entry['path']), $entry[$generation])) { throw new \RuntimeException('update_apply_verify_failed'); }
        }
    }

    private function replace(string $path, $stream, array $generation): void
    {
        $this->writer->writeStream($path, $stream, $generation['sha256'], $generation['size'], $generation['mode']);
    }
}
