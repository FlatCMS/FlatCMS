<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/Storage/ApplicationLock.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Core\Storage;

/** Request/worker admission barrier. Stable lock files are never cache payloads. */
final class ApplicationLock
{
    private static array $instances = [];
    private StoragePathGuard $paths;
    private mixed $admission = null;
    private mixed $activity = null;
    private int $frames = 0;
    private int $exclusiveDepth = 0;
    private bool $exclusiveHeld = false;
    private bool $borrowed = false;

    private function __construct(string $basePath)
    {
        $this->paths = new StoragePathGuard($basePath);
    }

    public static function for(string $basePath): self
    {
        $root = (new StoragePathGuard($basePath))->root();
        $key = PHP_OS_FAMILY === 'Windows' ? StoragePathGuard::foldWindowsCase($root) : $root;
        return self::$instances[$key] ??= new self($root);
    }

    /** Keep the lease through shutdown when called by an HTTP/CLI entry point. */
    public function enter(int $timeoutMilliseconds = 250, bool $allowPending = false, bool $readOnly = false): void
    {
        if ($this->frames > 0 || $this->exclusiveHeld) { $this->frames++; return; }
        if (!$this->openHandles($readOnly)) {
            if (!$allowPending) { $this->assertSettled([]); }
            return;
        }
        try {
            $this->acquire($this->admission, LOCK_SH, $timeoutMilliseconds);
            try { $this->acquire($this->activity, LOCK_SH, $timeoutMilliseconds); }
            finally { flock($this->admission, LOCK_UN); }
            if (!$allowPending) { $this->assertSettled([]); }
            $this->frames = 1;
        } catch (\Throwable $error) { $this->closeHandles(); throw $error; }
    }

    public function leave(): void
    {
        if ($this->frames < 1) { throw new StorageException('runtime_lease_unbalanced'); }
        $this->frames--;
        if ($this->frames === 0 && $this->exclusiveDepth === 0) { $this->closeHandles(); }
    }

    public function holdsLease(): bool
    {
        return $this->frames > 0 || $this->exclusiveHeld;
    }

    /** Only an exclusive owner may delegate its OS lease to a fresh PHP probe. */
    public function probeDescriptors(): array
    {
        if (!$this->exclusiveHeld || $this->borrowed || PHP_OS_FAMILY === 'Windows') {
            throw new StorageException('runtime_probe_lease_unavailable');
        }
        return [3 => $this->admission, 4 => $this->activity];
    }

    /** No HTTP/environment bypass: the CLI child must possess the actual lock descriptors. */
    public function inheritProbeLease(array $recoverScopes = []): void
    {
        if (PHP_SAPI !== 'cli' || PHP_OS_FAMILY === 'Windows' || $this->holdsLease()
            || is_resource($this->activity)) {
            throw new StorageException('runtime_probe_lease_invalid');
        }
        foreach ($recoverScopes as $scope) {
            if (!is_string($scope) || !in_array($scope, ['core-update', 'full-restoration', 'site-restoration'], true)) {
                throw new StorageException('runtime_probe_scope_invalid');
            }
        }
        $handles = [];
        try {
            foreach ([3 => 'admission', 4 => 'activity'] as $fd => $name) {
                $handle = @fopen('php://fd/' . $fd, 'r+b');
                if (!is_resource($handle)) { throw new StorageException('runtime_probe_lease_invalid'); }
                $handles[$name] = $handle;
                $path = $this->paths->resolve('storage/cache/locks/application/' . $name . '.lock');
                $actual = fstat($handle);
                $expected = @stat($path);
                if (!is_array($actual) || !is_array($expected) || ($actual['mode'] & 0170000) !== 0100000
                    || $actual['dev'] !== $expected['dev'] || $actual['ino'] !== $expected['ino']
                    || !flock($handle, LOCK_EX | LOCK_NB)) {
                    throw new StorageException('runtime_probe_lease_invalid');
                }
            }
        } catch (\Throwable $error) {
            // LOCK_UN here would also unlock the parent's inherited open-file description.
            foreach ($handles as $handle) { fclose($handle); }
            throw $error;
        }
        $this->admission = $handles['admission'];
        $this->activity = $handles['activity'];
        $this->exclusiveHeld = true;
        $this->borrowed = true;
        $this->assertSettled(array_values(array_unique($recoverScopes)));
    }

    public function shared(callable $operation): mixed
    {
        $this->enter();
        try { return $operation(); }
        finally { $this->leave(); }
    }

    /**
     * Stop admissions, drain existing workers, then run with exclusive access.
     * A promoted request keeps exclusivity until exit: its loaded PHP may be obsolete.
     * @param list<string> $recoverScopes Only the owner may replay its pending journal.
     */
    public function exclusive(callable $operation, array $recoverScopes = []): mixed
    {
        if ($this->exclusiveHeld) {
            $this->assertSettled($recoverScopes);
            $this->exclusiveDepth++;
            try { return $operation(); }
            finally { $this->exclusiveDepth--; }
        }
        $this->openHandles();
        $shared = $this->frames > 0;
        $sessionClosed = false;
        $admitted = false;
        $releasedShared = false;
        try {
            // A competing upgrader must fail immediately, not hold SH while the owner drains it.
            $this->acquire($this->admission, LOCK_EX, $shared ? 0 : 10000);
            $admitted = true;
            if ($shared && session_status() === PHP_SESSION_ACTIVE) {
                if (!session_write_close()) { throw new StorageException('runtime_session_release_failed'); }
                $sessionClosed = true;
            }
            if ($shared) { flock($this->activity, LOCK_UN); $releasedShared = true; }
            $this->acquire($this->activity, LOCK_EX, 10000);
            $this->exclusiveHeld = true;
            $this->exclusiveDepth++;
            if ($sessionClosed) {
                if (!session_start()) { throw new StorageException('runtime_session_resume_failed'); }
                $sessionClosed = false;
            }
            $this->assertSettled($recoverScopes);
            return $operation();
        } finally {
            if ($this->exclusiveHeld) {
                $this->exclusiveDepth--;
                if ($this->frames === 0) { $this->closeHandles(); }
            } elseif ($shared) {
                if ($releasedShared) { $this->acquire($this->activity, LOCK_SH, 10000); }
                if ($admitted) { flock($this->admission, LOCK_UN); }
            } else { $this->closeHandles(); }
            if ($sessionClosed && session_status() !== PHP_SESSION_ACTIVE) {
                if (!session_start()) { throw new StorageException('runtime_session_resume_failed'); }
            }
        }
    }

    /** Read only under a lease; an interrupted batch must not reach the normal bootstrap. */
    private function assertSettled(array $recoverScopes): void
    {
        foreach (['full-restoration', 'site-restoration'] as $scope) {
            $path = $this->paths->resolve('storage/transactions/stream-files/' . $scope . '/active.json');
            clearstatcache(true, $path);
            if (file_exists($path) && !in_array($scope, $recoverScopes, true)) {
                throw new StorageException('runtime_restoration_pending');
            }
        }
        if (!$this->borrowed && !in_array('core-update', $recoverScopes, true)) {
            $path = $this->paths->resolve('storage/recovery/active.json');
            clearstatcache(true, $path);
            if (file_exists($path)) {
                if (!is_file($path) || filesize($path) > 16777216) { throw new StorageException('runtime_recovery_state_invalid'); }
                try { $state = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR); }
                catch (\Throwable $error) { throw new StorageException('runtime_recovery_state_invalid', 0, $error); }
                if (!is_array($state)) { throw new StorageException('runtime_recovery_state_invalid'); }
                if (isset($state['core_transaction'])) {
                    $phase = $state['core_transaction']['phase'] ?? '';
                    if ($phase !== 'committed' && !($phase === 'rolled_back' && in_array($state['status'] ?? '', ['recovered', 'finalizing'], true))) {
                        throw new StorageException('runtime_core_update_pending');
                    }
                }
            }
        }
    }

    private function openHandles(bool $readOnly = false): bool
    {
        if (is_resource($this->activity)) { return true; }
        $root = $this->paths->resolve('storage/cache/locks/application');
        if ($readOnly && !file_exists($root . '/admission.lock') && !file_exists($root . '/activity.lock')) {
            return false;
        }
        if (!$readOnly) { $this->paths->ensureDirectory($root, 0700); }
        try {
            foreach (['admission', 'activity'] as $name) {
                $path = $this->paths->resolve($root . '/' . $name . '.lock');
                $this->{$name} = fopen($path, $readOnly ? 'rb' : 'c+b');
                if (!is_resource($this->{$name})) { throw new StorageException('runtime_lock_unavailable'); }
                if (!$readOnly) { @chmod($path, 0600); }
            }
        } catch (\Throwable $error) { $this->closeHandles(); throw $error; }
        return true;
    }

    private function acquire(mixed $handle, int $mode, int $timeoutMilliseconds): void
    {
        if ($timeoutMilliseconds < 0) { throw new StorageException('runtime_lock_timeout_invalid'); }
        $deadline = hrtime(true) + $timeoutMilliseconds * 1000000;
        do {
            if (flock($handle, $mode | LOCK_NB)) { return; }
            if (hrtime(true) >= $deadline) { break; }
            usleep(10000);
        } while (true);
        throw new StorageException('runtime_busy');
    }

    private function closeHandles(): void
    {
        foreach (['activity', 'admission'] as $name) {
            if (is_resource($this->{$name})) {
                if (!$this->borrowed) { flock($this->{$name}, LOCK_UN); }
                fclose($this->{$name});
            }
            $this->{$name} = null;
        }
        $this->exclusiveHeld = false;
        $this->borrowed = false;
    }
}
