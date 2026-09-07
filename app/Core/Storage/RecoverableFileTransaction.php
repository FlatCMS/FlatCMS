<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core\Storage;

/**
 * Coordinates a recoverable transaction over several canonical files.
 *
 * A filesystem cannot atomically replace multiple files together. The journal
 * records the previous generation before the first replacement and is kept
 * until every replacement is verified. A later process can then either finish
 * a committed transaction or restore a prepared one without guessing.
 */
final class RecoverableFileTransaction
{
    private const JOURNAL_SCHEMA = 1;

    private AtomicFileWriter $writer;
    private JsonStore $journals;
    private FileLockManager $locks;
    private string $scope;
    private string $journalPrefix;
    private int $synchronizationDepth = 0;

    public function __construct(
        AtomicFileWriter $writer,
        JsonStore $journals,
        FileLockManager $locks,
        string $scope
    ) {
        $scope = trim($scope);
        if ($scope === '' || str_contains($scope, "\0")) {
            throw new StorageException('Transaction scope cannot be empty or contain null bytes.');
        }

        $this->writer = $writer;
        $this->journals = $journals;
        $this->locks = $locks;
        $this->scope = $scope;
        $this->journalPrefix = hash('sha256', $scope) . '-';
    }

    public function synchronized(callable $operation): mixed
    {
        return $this->locks->synchronized('recoverable-transaction:' . $this->scope, function () use ($operation): mixed {
            $isOuterSynchronization = $this->synchronizationDepth === 0;
            $this->synchronizationDepth++;

            try {
                if ($isOuterSynchronization) {
                    $this->recoverPendingTransactions();
                }

                return $operation();
            } finally {
                $this->synchronizationDepth--;
            }
        });
    }

    /**
     * @param array<int, array{path: string, contents: string|null}> $operations
     * @param array<int, string> $cleanupDirectories
     */
    public function commit(array $operations, array $cleanupDirectories = []): void
    {
        $this->synchronized(function () use ($operations, $cleanupDirectories): void {
            $this->commitUnlocked($operations, $cleanupDirectories);
        });
    }

    public function recover(): void
    {
        $this->synchronized(static function (): void {
        });
    }

    /**
     * @param array<int, array{path: string, contents: string|null}> $operations
     * @param array<int, string> $cleanupDirectories
     */
    private function commitUnlocked(array $operations, array $cleanupDirectories): void
    {
        $preparedOperations = $this->prepareOperations($operations);
        if ($preparedOperations === []) {
            $this->cleanupEmptyDirectories($cleanupDirectories);
            return;
        }

        $journalName = $this->journalPrefix . bin2hex(random_bytes(12)) . '.json';
        $journal = [
            'schema' => self::JOURNAL_SCHEMA,
            'scope' => $this->scope,
            'state' => 'prepared',
            'created_at' => gmdate('c'),
            'operations' => array_map(static function (array $operation): array {
                return [
                    'path' => $operation['path'],
                    'before' => $operation['before'],
                    'after' => $operation['after'],
                ];
            }, $preparedOperations),
            'cleanup_directories' => $this->normalizeCleanupDirectories($cleanupDirectories),
        ];

        $this->journals->write($journalName, $journal);

        try {
            foreach ($preparedOperations as $operation) {
                $this->applyOperation($operation);
            }

            $journal['state'] = 'committed';
            $this->journals->write($journalName, $journal);
        } catch (\Throwable $exception) {
            try {
                $this->rollbackJournal($journal);
                $this->journals->delete($journalName);
            } catch (\Throwable $rollbackException) {
                throw new StorageException(
                    'Transactional storage rollback failed; recovery journal retained: ' . $journalName,
                    0,
                    $rollbackException
                );
            }

            if ($exception instanceof StorageException) {
                throw $exception;
            }

            throw new StorageException('Transactional storage write failed: ' . $exception->getMessage(), 0, $exception);
        }

        try {
            $this->cleanupEmptyDirectories($journal['cleanup_directories']);
            $this->journals->delete($journalName);
        } catch (\Throwable) {
            // A committed journal remains authoritative and is finalized on the next access.
        }
    }

    private function recoverPendingTransactions(): void
    {
        foreach ($this->journalFiles() as $journalName) {
            $journal = $this->normalizeJournal($this->journals->read($journalName));
            $state = $journal['state'];

            if ($state === 'prepared') {
                $this->rollbackJournal($journal);
                $this->cleanupEmptyDirectories($journal['cleanup_directories']);
                $this->journals->delete($journalName);
                continue;
            }

            $this->assertJournalState($journal, 'after');
            $this->cleanupEmptyDirectories($journal['cleanup_directories']);
            $this->journals->delete($journalName);
        }
    }

    /**
     * @param array<int, array{path: string, contents: string|null}> $operations
     * @return array<int, array{path: string, contents: string|null, before: array<string, mixed>, after: array<string, mixed>}>
     */
    private function prepareOperations(array $operations): array
    {
        $prepared = [];
        $seen = [];

        foreach ($operations as $operation) {
            if (!is_array($operation) || !is_string($operation['path'] ?? null) || !array_key_exists('contents', $operation)) {
                throw new StorageException('Invalid transactional storage operation.');
            }

            $contents = $operation['contents'];
            if ($contents !== null && !is_string($contents)) {
                throw new StorageException('Transactional storage contents must be a string or null.');
            }

            $target = $this->writer->resolvePath((string) $operation['path']);
            if (is_dir($target)) {
                throw new StorageException('Transactional storage target is a directory: ' . $target);
            }

            $path = $this->writer->relativePath($target);
            if (isset($seen[$path])) {
                throw new StorageException('Transactional storage target is duplicated: ' . $path);
            }
            $seen[$path] = true;

            $before = $this->snapshot($path);
            $after = [
                'exists' => $contents !== null,
                'sha256' => $contents === null ? '' : hash('sha256', $contents),
            ];

            if ($this->snapshotMatches($before, $after)) {
                continue;
            }

            $prepared[] = [
                'path' => $path,
                'contents' => $contents,
                'before' => $before,
                'after' => $after,
            ];
        }

        return $prepared;
    }

    /**
     * @param array{path: string, contents: string|null, before: array<string, mixed>, after: array<string, mixed>} $operation
     */
    private function applyOperation(array $operation): void
    {
        $this->writer->synchronized($operation['path'], function () use ($operation): void {
            if (!$this->snapshotMatches($this->snapshot($operation['path']), $operation['before'])) {
                throw new StorageException('Transactional storage target changed outside its lock: ' . $operation['path']);
            }

            if ($operation['contents'] === null) {
                $this->writer->delete($operation['path']);
            } else {
                $this->writer->write($operation['path'], $operation['contents']);
            }

            if (!$this->snapshotMatches($this->snapshot($operation['path']), $operation['after'])) {
                throw new StorageException('Transactional storage verification failed: ' . $operation['path']);
            }
        });
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function rollbackJournal(array $journal): void
    {
        $operations = $journal['operations'];
        if (!is_array($operations)) {
            throw new StorageException('Transactional storage journal has no operations.');
        }

        foreach (array_reverse($operations) as $operation) {
            if (!is_array($operation)) {
                throw new StorageException('Transactional storage journal contains an invalid operation.');
            }

            $this->restoreOperation($operation);
        }

        $this->assertJournalState($journal, 'before');
    }

    /**
     * @param array<string, mixed> $operation
     */
    private function restoreOperation(array $operation): void
    {
        $path = (string) ($operation['path'] ?? '');
        $before = is_array($operation['before'] ?? null) ? $operation['before'] : null;
        $after = is_array($operation['after'] ?? null) ? $operation['after'] : null;
        if ($path === '' || $before === null || $after === null) {
            throw new StorageException('Transactional storage journal cannot be restored.');
        }

        $this->writer->synchronized($path, function () use ($path, $before, $after): void {
            $current = $this->snapshot($path);
            if ($this->snapshotMatches($current, $before)) {
                return;
            }
            if (!$this->snapshotMatches($current, $after)) {
                throw new StorageException('Transactional storage recovery found a concurrent change: ' . $path);
            }

            if (!empty($before['exists'])) {
                $contents = $this->snapshotContents($before, $path);
                $this->writer->write($path, $contents);
            } else {
                $this->writer->delete($path);
            }

            if (!$this->snapshotMatches($this->snapshot($path), $before)) {
                throw new StorageException('Transactional storage recovery verification failed: ' . $path);
            }
        });
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function assertJournalState(array $journal, string $stateKey): void
    {
        foreach ($journal['operations'] as $operation) {
            $path = (string) ($operation['path'] ?? '');
            $expected = is_array($operation[$stateKey] ?? null) ? $operation[$stateKey] : null;
            if ($path === '' || $expected === null || !$this->snapshotMatches($this->snapshot($path), $expected)) {
                throw new StorageException('Transactional storage journal state cannot be verified.');
            }
        }
    }

    /**
     * @return array{exists: bool, sha256: string, contents_b64?: string}
     */
    private function snapshot(string $path): array
    {
        $target = $this->writer->resolvePath($path);
        clearstatcache(true, $target);

        if (is_link($target)) {
            throw new StorageException('Symbolic links are forbidden in transactional storage: ' . $target);
        }
        if (!file_exists($target)) {
            return ['exists' => false, 'sha256' => ''];
        }
        if (!is_file($target)) {
            throw new StorageException('Transactional storage target is not a regular file: ' . $target);
        }

        $contents = file_get_contents($target);
        if (!is_string($contents)) {
            throw new StorageException('Unable to read transactional storage target: ' . $target);
        }

        return [
            'exists' => true,
            'sha256' => hash('sha256', $contents),
            'contents_b64' => base64_encode($contents),
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $expected
     */
    private function snapshotMatches(array $snapshot, array $expected): bool
    {
        return (bool) ($snapshot['exists'] ?? false) === (bool) ($expected['exists'] ?? false)
            && hash_equals((string) ($snapshot['sha256'] ?? ''), (string) ($expected['sha256'] ?? ''));
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    private function snapshotContents(array $snapshot, string $path): string
    {
        $encoded = $snapshot['contents_b64'] ?? null;
        if (!is_string($encoded)) {
            throw new StorageException('Transactional storage journal has no previous contents: ' . $path);
        }

        $contents = base64_decode($encoded, true);
        if (!is_string($contents) || !hash_equals((string) ($snapshot['sha256'] ?? ''), hash('sha256', $contents))) {
            throw new StorageException('Transactional storage journal previous contents are invalid: ' . $path);
        }

        return $contents;
    }

    /**
     * @param array<string, mixed> $journal
     * @return array<string, mixed>
     */
    private function normalizeJournal(array $journal): array
    {
        if ((int) ($journal['schema'] ?? 0) !== self::JOURNAL_SCHEMA
            || !hash_equals($this->scope, (string) ($journal['scope'] ?? ''))
            || !in_array($journal['state'] ?? null, ['prepared', 'committed'], true)
            || !is_array($journal['operations'] ?? null)) {
            throw new StorageException('Transactional storage journal is invalid.');
        }

        $operations = [];
        foreach ($journal['operations'] as $operation) {
            if (!is_array($operation)) {
                throw new StorageException('Transactional storage journal contains an invalid operation.');
            }

            $path = $this->writer->relativePath($this->writer->resolvePath((string) ($operation['path'] ?? '')));
            $before = $this->normalizeSnapshot($operation['before'] ?? null, true);
            $after = $this->normalizeSnapshot($operation['after'] ?? null, false);
            $operations[] = [
                'path' => $path,
                'before' => $before,
                'after' => $after,
            ];
        }

        if ($operations === []) {
            throw new StorageException('Transactional storage journal has no operations.');
        }

        $journal['operations'] = $operations;
        $journal['cleanup_directories'] = $this->normalizeCleanupDirectories(
            is_array($journal['cleanup_directories'] ?? null) ? $journal['cleanup_directories'] : []
        );

        return $journal;
    }

    /**
     * @return array{exists: bool, sha256: string, contents_b64?: string}
     */
    private function normalizeSnapshot(mixed $snapshot, bool $requiresContents): array
    {
        if (!is_array($snapshot) || !is_bool($snapshot['exists'] ?? null) || !is_string($snapshot['sha256'] ?? null)) {
            throw new StorageException('Transactional storage journal snapshot is invalid.');
        }

        $normalized = [
            'exists' => $snapshot['exists'],
            'sha256' => $snapshot['sha256'],
        ];
        if (!$normalized['exists']) {
            if ($normalized['sha256'] !== '') {
                throw new StorageException('Transactional storage absent snapshot has a checksum.');
            }
            return $normalized;
        }

        if (!preg_match('/^[a-f0-9]{64}$/', $normalized['sha256'])) {
            throw new StorageException('Transactional storage snapshot checksum is invalid.');
        }

        $encoded = $snapshot['contents_b64'] ?? null;
        if ($requiresContents) {
            if (!is_string($encoded)) {
                throw new StorageException('Transactional storage previous snapshot has no contents.');
            }
            $contents = base64_decode($encoded, true);
            if (!is_string($contents) || !hash_equals($normalized['sha256'], hash('sha256', $contents))) {
                throw new StorageException('Transactional storage previous snapshot contents are invalid.');
            }
            $normalized['contents_b64'] = $encoded;
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $directories
     * @return array<int, string>
     */
    private function normalizeCleanupDirectories(array $directories): array
    {
        $normalized = [];
        foreach ($directories as $directory) {
            if (!is_string($directory) || trim($directory) === '') {
                continue;
            }

            $target = $this->writer->resolvePath($directory);
            $relative = $this->writer->relativePath($target);
            $normalized[$relative] = $relative;
        }

        $normalized = array_values($normalized);
        usort($normalized, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        return $normalized;
    }

    /**
     * @param array<int, string> $directories
     */
    private function cleanupEmptyDirectories(array $directories): void
    {
        foreach ($this->normalizeCleanupDirectories($directories) as $directory) {
            $target = $this->writer->resolvePath($directory);
            if (is_link($target) || !is_dir($target)) {
                continue;
            }

            $entries = scandir($target);
            if (!is_array($entries) || array_diff($entries, ['.', '..']) !== []) {
                continue;
            }

            @rmdir($target);
        }
    }

    /**
     * @return array<int, string>
     */
    private function journalFiles(): array
    {
        $pattern = $this->journals->root() . '/' . $this->journalPrefix . '*.json';
        $files = glob($pattern) ?: [];
        $journalNames = [];

        foreach ($files as $file) {
            if (is_link($file)) {
                throw new StorageException('Transactional storage journal cannot be a symbolic link: ' . $file);
            }
            if (is_file($file)) {
                $journalNames[] = basename($file);
            }
        }

        sort($journalNames, SORT_NATURAL);
        return $journalNames;
    }
}
