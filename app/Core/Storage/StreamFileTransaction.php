<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/Storage/StreamFileTransaction.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Core\Storage;

/** Recoverable large-file batches, isolated from participating HTTP/CLI/task workers. */
final class StreamFileTransaction
{
    private const DIRECTORY = 'storage/transactions/stream-files';
    private AtomicFileWriter $writer;
    private AtomicFileWriter $staging;
    private JsonStore $journal;
    private FileLockManager $locks;
    private int $depth = 0;
    private string $scope;
    private ApplicationLock $application;

    public function __construct(string $basePath, string $scope)
    {
        if (preg_match('/^[a-z0-9-]+$/D', $scope) !== 1) {
            throw new StorageException('Invalid streamed transaction scope.');
        }
        $basePath = (new StoragePathGuard($basePath))->root();
        $this->application = ApplicationLock::for($basePath);
        $this->scope = $scope;
        $this->locks = new FileLockManager($basePath . '/storage/cache/locks/stream-files', 10000);
        $this->writer = new AtomicFileWriter($basePath, $this->locks);
        $this->staging = new AtomicFileWriter($basePath . '/' . self::DIRECTORY . '/' . $scope, $this->locks, 0700, 0600);
        $this->journal = new JsonStore($this->staging->root(), $this->staging);
    }

    public function synchronized(callable $operation): mixed
    {
        return $this->application->exclusive(fn (): mixed => $this->locks->synchronized('stream-files:' . $this->scope, function () use ($operation): mixed {
            $outer = $this->depth++ === 0;
            try {
                if ($outer) { $this->recoverUnlocked(); }
                return $operation();
            } finally { $this->depth--; }
        }), [$this->scope]);
    }

    public function recover(): void
    {
        $this->synchronized(static function (): void {});
    }

    /**
     * open returns a fresh readable stream owned and closed by this transaction;
     * null means deletion. No payload is retained in the JSON journal.
     *
     * @param array<int,array{path:string,open:callable|null,sha256?:string,size?:int,mode?:int}> $operations
     * @param callable():bool|null $accept
     * @param callable():void|null $afterRollback
     */
    public function commit(
        array $operations,
        ?callable $accept = null,
        ?callable $afterRollback = null
    ): void
    {
        $this->synchronized(function () use ($operations, $accept, $afterRollback): void {
            $entries = [];
            $sources = [];
            foreach ($operations as $operation) {
                if (!array_key_exists('open', $operation)) { throw new StorageException('Missing streamed operation kind.'); }
                $path = $this->canonicalPath((string) ($operation['path'] ?? ''));
                if (isset($entries[$path])) { throw new StorageException('Duplicate streamed target: ' . $path); }
                $open = $operation['open'] ?? null;
                if ($open !== null && !is_callable($open)) { throw new StorageException('Invalid stream opener.'); }
                $before = $this->generation($path);
                $after = $open === null ? null : [
                    'sha256' => $operation['sha256'] ?? '',
                    'size' => $operation['size'] ?? -1,
                    'mode' => $operation['mode'] ?? ($before['mode'] ?? 0644),
                ];
                $this->validateGeneration($after);
                if ($this->matches($before, $after)) { continue; }
                $entries[$path] = ['path' => $path, 'before' => $before, 'after' => $after];
                $sources[$path] = $open;
            }
            if ($entries === []) {
                try {
                    if ($accept !== null && $accept() !== true) {
                        throw new StorageException('runtime_transaction_acceptance_failed');
                    }
                } catch (\Throwable $exception) {
                    $this->runAfterRollback($afterRollback, $exception);
                }
                return;
            }
            $this->validateTargetSet(array_keys($entries));
            $state = ['schema' => 1, 'scope' => $this->scope, 'state' => 'staging', 'operations' => array_values($entries)];
            $this->journal->write('active.json', $state);
            try {
                foreach ($entries as $entry) {
                    if ($entry['before'] === null) { continue; }
                    $stream = fopen($this->writer->resolvePath($entry['path']), 'rb');
                    if (!is_resource($stream)) { throw new StorageException('Unable to snapshot streamed target.'); }
                    try {
                        $this->staging->writeStream($this->blob($entry['path']), $stream, $entry['before']['sha256'], $entry['before']['size'], 0600);
                    } finally { fclose($stream); }
                }
                // Staging never mutates canonical files. Prepared means every prior generation is durable.
                $state['state'] = 'prepared';
                $this->journal->write('active.json', $state);
                $this->verifyBeforeBlobs($state);
                $this->verifyCurrent($state, 'before');
                foreach ($entries as $entry) {
                    $this->writer->synchronized($entry['path'], function () use ($entry, $sources): void {
                        if (!$this->matches($this->generation($entry['path']), $entry['before'])) {
                            throw new StorageException('Streamed target changed during restoration: ' . $entry['path']);
                        }
                        if ($entry['after'] === null) {
                            $this->writer->delete($entry['path']);
                        } else {
                            $stream = $sources[$entry['path']]();
                            if (!is_resource($stream)) { throw new StorageException('Unable to open restoration stream.'); }
                            try {
                                $this->writer->writeStream($entry['path'], $stream, $entry['after']['sha256'], $entry['after']['size'], $entry['after']['mode']);
                            } finally { fclose($stream); }
                        }
                    });
                }
                $this->verifyCurrent($state, 'after');
                if ($accept !== null && $accept() !== true) {
                    throw new StorageException('runtime_transaction_acceptance_failed');
                }
                $state['state'] = 'committed';
                $this->journal->write('active.json', $state);
            } catch (\Throwable $exception) {
                try { $this->recoverUnlocked(); }
                catch (\Throwable $recoveryError) {
                    throw new StorageException('Streamed rollback requires recovery; journal retained.', 0, $recoveryError);
                }
                $this->runAfterRollback($afterRollback, $exception);
            }
            // A cleanup failure retains COMMITTED, never rolls back an already verified generation.
            $this->cleanup();
        });
    }

    private function runAfterRollback(?callable $afterRollback, \Throwable $cause): never
    {
        if ($afterRollback !== null) {
            try {
                $afterRollback();
            } catch (\Throwable $rollbackError) {
                throw new StorageException(
                    'Streamed rollback completed but its post-rollback reconciliation failed.',
                    0,
                    $rollbackError
                );
            }
        }

        throw new StorageException(
            'Streamed transaction reported an error; journal recovery completed.',
            0,
            $cause
        );
    }

    private function recoverUnlocked(): void
    {
        if (!$this->journal->exists('active.json')) {
            $this->cleanup();
            return;
        }
        $state = $this->journal->read('active.json');
        $this->validateJournal($state);
        if ($state['state'] === 'staging') {
            $this->cleanup();
            return;
        }
        if (in_array($state['state'], ['committed', 'rolled_back'], true)) {
            $this->verifyCurrent($state, $state['state'] === 'committed' ? 'after' : 'before');
            $this->cleanup();
            return;
        }
        $this->verifyBeforeBlobs($state);
        // Refuse an unrelated edit or corrupt snapshot before rolling back ANY file.
        foreach ($state['operations'] as $entry) {
            $current = $this->generation($entry['path']);
            if (!$this->matches($current, $entry['before']) && !$this->matches($current, $entry['after'])) {
                throw new StorageException('Conflicting generation during streamed recovery: ' . $entry['path']);
            }
        }
        foreach (array_reverse($state['operations']) as $entry) {
            if ($this->matches($this->generation($entry['path']), $entry['before'])) { continue; }
            if ($entry['before'] === null) {
                $this->writer->delete($entry['path']);
                continue;
            }
            $stream = fopen($this->staging->resolvePath($this->blob($entry['path'])), 'rb');
            if (!is_resource($stream)) { throw new StorageException('Unable to open prior streamed generation.'); }
            try {
                $this->writer->writeStream($entry['path'], $stream, $entry['before']['sha256'], $entry['before']['size'], $entry['before']['mode']);
            } finally { fclose($stream); }
        }
        $this->verifyCurrent($state, 'before');
        $state['state'] = 'rolled_back';
        $this->journal->write('active.json', $state);
        $this->cleanup();
    }

    private function canonicalPath(string $path): string
    {
        $path = $this->writer->relativePath($path);
        $key = $this->pathKey($path);
        if (in_array($key, ['storage/transactions', 'storage/cache'], true)
            || str_starts_with($key, 'storage/transactions/') || str_starts_with($key, 'storage/cache/')) {
            throw new StorageException('A streamed transaction cannot replace its journal or lock roots.');
        }
        return $path;
    }

    private function generation(string $path): ?array
    {
        $target = $this->writer->resolvePath($path);
        clearstatcache(true, $target);
        if (!file_exists($target)) { return null; }
        if (!is_file($target)) { throw new StorageException('Streamed target is not a regular file: ' . $path); }
        $hash = hash_file('sha256', $target);
        $size = filesize($target);
        $mode = fileperms($target);
        if (!is_string($hash) || !is_int($size) || !is_int($mode)) {
            throw new StorageException('Unable to inspect streamed target: ' . $path);
        }
        return ['sha256' => $hash, 'size' => $size, 'mode' => $mode & 0777];
    }

    private function validateGeneration(mixed $generation): void
    {
        if ($generation === null) { return; }
        if (!is_array($generation) || !is_string($generation['sha256'] ?? null)
            || preg_match('/^[a-f0-9]{64}$/D', $generation['sha256']) !== 1
            || !is_int($generation['size'] ?? null) || $generation['size'] < 0
            || !is_int($generation['mode'] ?? null) || $generation['mode'] < 0 || $generation['mode'] > 0777) {
            throw new StorageException('Invalid streamed generation metadata.');
        }
    }

    private function validateJournal(array $state): void
    {
        if (($state['schema'] ?? null) !== 1 || ($state['scope'] ?? null) !== $this->scope
            || !in_array($state['state'] ?? null, ['staging', 'prepared', 'committed', 'rolled_back'], true)
            || !is_array($state['operations'] ?? null) || $state['operations'] === []) {
            throw new StorageException('Invalid streamed transaction journal.');
        }
        $seen = [];
        foreach ($state['operations'] as $entry) {
            if (!is_array($entry) || !is_string($entry['path'] ?? null)
                || !array_key_exists('before', $entry) || !array_key_exists('after', $entry)
                || $this->canonicalPath($entry['path']) !== $entry['path'] || isset($seen[$entry['path']])) {
                throw new StorageException('Invalid streamed journal entry.');
            }
            $this->validateGeneration($entry['before']);
            $this->validateGeneration($entry['after']);
            $seen[$entry['path']] = true;
        }
        $this->validateTargetSet(array_keys($seen));
    }

    private function pathKey(string $path): string
    {
        return PHP_OS_FAMILY === 'Windows' ? StoragePathGuard::foldWindowsCase($path) : $path;
    }

    private function validateTargetSet(array $paths): void
    {
        $seen = [];
        foreach ($paths as $path) {
            $key = $this->pathKey($path);
            if (isset($seen[$key])) { throw new StorageException('Duplicate streamed target: ' . $path); }
            $seen[$key] = true;
        }
        foreach (array_keys($seen) as $path) {
            for ($parent = dirname($path); $parent !== '.'; $parent = dirname($parent)) {
                if (isset($seen[$parent])) { throw new StorageException('Conflicting streamed file and directory targets: ' . $path); }
            }
        }
    }

    private function matches(?array $left, ?array $right): bool
    {
        if ($left === null || $right === null) { return $left === $right; }
        return $left['size'] === $right['size'] && hash_equals($left['sha256'], $right['sha256'])
            && (PHP_OS_FAMILY === 'Windows' || $left['mode'] === $right['mode']);
    }

    private function verifyCurrent(array $state, string $generation): void
    {
        foreach ($state['operations'] as $entry) {
            if (!$this->matches($this->generation($entry['path']), $entry[$generation])) {
                throw new StorageException('Streamed generation verification failed: ' . $entry['path']);
            }
        }
    }

    private function verifyBeforeBlobs(array $state): void
    {
        foreach ($state['operations'] as $entry) {
            if ($entry['before'] === null) { continue; }
            $file = $this->staging->resolvePath($this->blob($entry['path']));
            if (!is_file($file) || filesize($file) !== $entry['before']['size']
                || hash_file('sha256', $file) !== $entry['before']['sha256']) {
                throw new StorageException('Missing or corrupt streamed recovery copy: ' . $entry['path']);
            }
        }
    }

    private function blob(string $path): string
    {
        return 'before/' . hash('sha256', $path) . '.bin';
    }

    private function cleanup(): void
    {
        $entries = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->staging->root(), \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            $path = $this->staging->resolvePath($entry->getPathname());
            if ($path === $this->staging->root() . '/active.json') { continue; }
            $entries[] = [$path, $entry->isDir()];
        }
        foreach ($entries as [$path, $directory]) {
            if ($directory ? !rmdir($path) : !unlink($path)) {
                throw new StorageException('Unable to clean streamed transaction staging.');
            }
        }
        $this->journal->delete('active.json');
    }
}
