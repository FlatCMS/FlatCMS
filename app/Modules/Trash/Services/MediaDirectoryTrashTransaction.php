<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Trash/Services/MediaDirectoryTrashTransaction.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Trash\Services;

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\JsonStore;
use App\Core\Storage\RecoverableFileTransaction;
use App\Core\Storage\StorageException;
use App\Modules\Media\Models\MediaModel;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Coordinates Media directory archive, restoration and purge operations.
 *
 * A directory rename is only atomic on one filesystem. The operation therefore
 * fails closed when the upload and Trash roots are mounted on different devices
 * instead of falling back to a copy followed by a recursive deletion.
 */
final class MediaDirectoryTrashTransaction
{
    private const JOURNAL_SCHEMA = 1;
    private const LOCK_SCOPE = 'trash-media-directories';
    private const MEDIA_FILE = 'core/media/media.json';

    private string $basePath;
    private string $dataRoot;
    private string $uploadsPath;
    private string $archivesPath;
    private string $purgePath;
    private string $journalRoot;
    private FileLockManager $locks;
    private JsonStore $data;
    private JsonStore $journals;
    private RecoverableFileTransaction $dataTransactions;

    /** @var callable(string): void|null */
    private $checkpoint;

    public function __construct(?string $basePath = null, ?callable $checkpoint = null)
    {
        $this->basePath = $this->resolveBasePath($basePath);
        $this->dataRoot = $this->basePath . '/data';
        $this->uploadsPath = $this->basePath . '/public/uploads';
        $this->archivesPath = $this->basePath . '/storage/trash/media';
        $this->purgePath = $this->basePath . '/storage/trash/media-purge';
        $this->journalRoot = $this->basePath . '/storage/trash/media-transactions';
        $this->checkpoint = $checkpoint;

        $this->locks = new FileLockManager(
            $this->basePath . '/storage/cache/locks/trash-media-directories',
            5000
        );

        $dataWriter = new AtomicFileWriter($this->dataRoot, $this->locks);
        $this->data = new JsonStore($this->dataRoot, $dataWriter);

        $journalWriter = new AtomicFileWriter($this->journalRoot, $this->locks);
        $this->journals = new JsonStore($this->journalRoot, $journalWriter);
        $dataJournalRoot = $this->journalRoot . '/data';
        $dataJournals = new JsonStore(
            $dataJournalRoot,
            new AtomicFileWriter($dataJournalRoot, $this->locks)
        );
        $this->dataTransactions = new RecoverableFileTransaction(
            $dataWriter,
            $dataJournals,
            $this->locks,
            'trash-media-directories-data'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function archive(string $rawMediaPath, string $deletedBy = ''): ?array
    {
        try {
            return $this->locks->synchronized(self::LOCK_SCOPE, function () use ($rawMediaPath, $deletedBy): ?array {
                return $this->archiveUnlocked($rawMediaPath, $deletedBy);
            });
        } catch (StorageException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function restore(array $item): array
    {
        try {
            return $this->locks->synchronized(self::LOCK_SCOPE, function () use ($item): array {
                return $this->restoreUnlocked($item);
            });
        } catch (StorageException) {
            return ['success' => false, 'code' => 'restore_failed'];
        }
    }

    /**
     * @param array<string, mixed> $item
     */
    public function purge(array $item): bool
    {
        try {
            return $this->locks->synchronized(self::LOCK_SCOPE, function () use ($item): bool {
                return $this->purgeUnlocked($item);
            });
        } catch (StorageException) {
            return false;
        }
    }

    public function recover(): void
    {
        $this->locks->synchronized(self::LOCK_SCOPE, function (): void {
            $this->recoverPendingUnlocked();
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function archiveUnlocked(string $rawMediaPath, string $deletedBy): ?array
    {
        $this->recoverPendingUnlocked();

        $mediaPath = $this->normalizeMediaDirectoryPath($rawMediaPath);
        if ($mediaPath === null) {
            return null;
        }

        $sourcePath = $this->uploadsPath . '/' . $mediaPath;
        if (!$this->isSafeMediaDirectory($sourcePath, $mediaPath)) {
            return null;
        }

        if ($this->findRecordByEntityId('media-directory:' . $mediaPath) !== null) {
            return null;
        }

        $archiveSlug = $this->newArchiveSlug($mediaPath);
        $archiveRoot = $this->archiveRoot($archiveSlug);
        $archiveDirectory = $archiveRoot . '/directory';
        if (file_exists($archiveRoot) || is_link($archiveRoot)) {
            return null;
        }

        $mediaBefore = $this->data->snapshot(self::MEDIA_FILE, []);
        $repositoryBefore = $this->normalizeRepository($mediaBefore['data']);
        $repositoryEntries = $this->snapshotRepositoryEntries($repositoryBefore, $mediaPath);
        $repositoryAfter = $this->withoutRepositoryEntries($repositoryBefore, $mediaPath);

        $trashId = $this->newTrashId();
        $recordPath = $this->recordPath($trashId);
        $recordBefore = $this->data->snapshot($recordPath, []);
        if ($recordBefore['hash'] !== null) {
            return null;
        }

        $record = $this->buildArchiveRecord($trashId, $mediaPath, $archiveSlug, $repositoryEntries, $deletedBy);
        $mediaBeforeDescriptor = $this->descriptorFromSnapshot($mediaBefore);
        $recordBeforeDescriptor = $this->descriptorFromSnapshot($recordBefore);
        $mediaAfterDescriptor = $repositoryAfter === $repositoryBefore
            ? $mediaBeforeDescriptor
            : $this->descriptorFromContents(JsonStore::encodeCanonical($repositoryAfter));
        $recordAfterDescriptor = $this->descriptorFromContents(JsonStore::encodeCanonical($record));

        $journal = $this->buildJournal(
            'archive',
            $mediaPath,
            $archiveSlug,
            $record,
            $mediaBeforeDescriptor,
            $recordBeforeDescriptor,
            $mediaAfterDescriptor,
            $recordAfterDescriptor
        );
        $journalName = $this->writeJournal($journal);

        try {
            $this->checkpoint('archive.prepared');
            $this->moveDirectoryAtomically($sourcePath, $archiveDirectory, $this->archivesPath);
            $this->checkpoint('archive.after_move');
            $this->transitionDataState($journal, 'before', 'after');
            $this->checkpoint('archive.after_data');
            $this->markJournalCommitted($journalName, $journal);
            $this->checkpoint('archive.committed');
            $this->deleteJournal($journalName);
        } catch (StorageException) {
            $this->recoverJournalSilently($journalName);
            return null;
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function restoreUnlocked(array $item): array
    {
        $this->recoverPendingUnlocked();

        $recordState = $this->loadDirectoryRecord($item);
        if ($recordState === null) {
            return ['success' => false, 'code' => 'not_found'];
        }

        $record = $recordState['record'];
        $mediaPath = $this->normalizeMediaDirectoryPath((string) ($record['payload']['path'] ?? ''));
        $archiveSlug = $this->recordArchiveSlug($record);
        if ($mediaPath === null || $archiveSlug === null) {
            return ['success' => false, 'code' => 'not_found'];
        }

        $archiveRoot = $this->archiveRoot($archiveSlug);
        $archiveDirectory = $archiveRoot . '/directory';
        $targetPath = $this->uploadsPath . '/' . $mediaPath;
        if (
            !$this->isSafeArchivedDirectory($archiveDirectory)
            || file_exists($targetPath)
            || is_link($targetPath)
            || $this->hasSymlinkInMediaPath($mediaPath)
        ) {
            return ['success' => false, 'code' => 'id_conflict'];
        }

        $mediaBefore = $this->data->snapshot(self::MEDIA_FILE, []);
        $repositoryBefore = $this->normalizeRepository($mediaBefore['data']);
        $repositoryEntries = $this->normalizeRepositorySnapshots($record['payload']['repository_entries'] ?? []);
        if ($this->repositorySnapshotsConflict($repositoryEntries, $repositoryBefore)) {
            return ['success' => false, 'code' => 'id_conflict'];
        }

        $repositoryAfter = $this->restoreRepositorySnapshots($repositoryBefore, $repositoryEntries);
        $mediaBeforeDescriptor = $this->descriptorFromSnapshot($mediaBefore);
        $mediaAfterDescriptor = $repositoryAfter === $repositoryBefore
            ? $mediaBeforeDescriptor
            : $this->descriptorFromContents(JsonStore::encodeCanonical($repositoryAfter));
        $recordBeforeDescriptor = $recordState['descriptor'];
        $recordAfterDescriptor = $this->absentDescriptor();
        $journal = $this->buildJournal(
            'restore',
            $mediaPath,
            $archiveSlug,
            $record,
            $mediaBeforeDescriptor,
            $recordBeforeDescriptor,
            $mediaAfterDescriptor,
            $recordAfterDescriptor
        );
        $journalName = $this->writeJournal($journal);

        try {
            $this->checkpoint('restore.prepared');
            $this->moveDirectoryAtomically($archiveDirectory, $targetPath, $this->uploadsPath);
            $this->checkpoint('restore.after_move');
            $this->transitionDataState($journal, 'before', 'after');
            $this->checkpoint('restore.after_data');
            $this->markJournalCommitted($journalName, $journal);
            $this->checkpoint('restore.committed');
            $this->removeEmptyDirectory($archiveRoot, $this->archivesPath);
            $this->deleteJournal($journalName);
        } catch (StorageException) {
            $this->recoverJournalSilently($journalName);
            return ['success' => false, 'code' => 'restore_failed'];
        }

        return [
            'success' => true,
            'item' => ['path' => $mediaPath, 'kind' => 'directory'],
            'entity_type' => 'media',
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function purgeUnlocked(array $item): bool
    {
        $this->recoverPendingUnlocked();

        $recordState = $this->loadDirectoryRecord($item);
        if ($recordState === null) {
            return false;
        }

        $record = $recordState['record'];
        $mediaPath = $this->normalizeMediaDirectoryPath((string) ($record['payload']['path'] ?? ''));
        $archiveSlug = $this->recordArchiveSlug($record);
        if ($mediaPath === null || $archiveSlug === null) {
            return false;
        }

        $archiveRoot = $this->archiveRoot($archiveSlug);
        $stagingRoot = $this->purgeRoot($archiveSlug);
        if (
            !$this->isSafeArchivedDirectory($archiveRoot)
            || file_exists($stagingRoot)
            || is_link($stagingRoot)
        ) {
            return false;
        }

        $mediaBefore = $this->data->snapshot(self::MEDIA_FILE, []);
        $mediaDescriptor = $this->descriptorFromSnapshot($mediaBefore);
        $journal = $this->buildJournal(
            'purge',
            $mediaPath,
            $archiveSlug,
            $record,
            $mediaDescriptor,
            $recordState['descriptor'],
            $mediaDescriptor,
            $this->absentDescriptor()
        );
        $journalName = $this->writeJournal($journal);

        try {
            $this->checkpoint('purge.prepared');
            $this->moveDirectoryAtomically($archiveRoot, $stagingRoot, $this->purgePath);
            $this->checkpoint('purge.after_stage');
            $this->transitionDataState($journal, 'before', 'after');
            $this->checkpoint('purge.after_data');
            $this->markJournalCommitted($journalName, $journal);
            $this->checkpoint('purge.committed');
            $this->finishPurge($stagingRoot, $journalName);
        } catch (StorageException) {
            $this->recoverJournalSilently($journalName);
            return false;
        }

        return true;
    }

    private function recoverPendingUnlocked(): void
    {
        $this->dataTransactions->recover();

        foreach ($this->journalNames() as $journalName) {
            $journal = $this->normalizeJournal($this->journals->read($journalName));
            $this->recoverJournalUnlocked($journalName, $journal);
        }
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function recoverJournalUnlocked(string $journalName, array $journal): void
    {
        match ($journal['operation']) {
            'archive' => $this->recoverArchive($journalName, $journal),
            'restore' => $this->recoverRestore($journalName, $journal),
            'purge' => $this->recoverPurge($journalName, $journal),
            default => throw new StorageException('Unsupported media directory transaction operation.'),
        };
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function recoverArchive(string $journalName, array $journal): void
    {
        $dataState = $this->currentDataState($journal);
        $physicalState = $this->archivePhysicalState($journal);

        if ($dataState === 'after' && $physicalState === 'after') {
            $this->markJournalCommitted($journalName, $journal);
            $this->deleteJournal($journalName);
            return;
        }

        if ($dataState === 'before' && $physicalState === 'before') {
            $this->deleteJournal($journalName);
            return;
        }

        if ($dataState === 'before' && $physicalState === 'after') {
            $this->moveDirectoryAtomically(
                $this->archiveDirectory((string) $journal['archive_slug']),
                $this->uploadsPath . '/' . $journal['media_path'],
                $this->uploadsPath
            );
            $this->removeEmptyDirectory($this->archiveRoot((string) $journal['archive_slug']), $this->archivesPath);
            $this->deleteJournal($journalName);
            return;
        }

        if ($dataState === 'after' && $physicalState === 'before') {
            $this->transitionDataState($journal, 'after', 'before');
            $this->deleteJournal($journalName);
            return;
        }

        throw new StorageException('Media directory archive recovery found mixed state.');
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function recoverRestore(string $journalName, array $journal): void
    {
        $dataState = $this->currentDataState($journal);
        $physicalState = $this->restorePhysicalState($journal);

        if ($dataState === 'after' && $physicalState === 'after') {
            $this->markJournalCommitted($journalName, $journal);
            $this->removeEmptyDirectory($this->archiveRoot((string) $journal['archive_slug']), $this->archivesPath);
            $this->deleteJournal($journalName);
            return;
        }

        if ($dataState === 'before' && $physicalState === 'before') {
            $this->deleteJournal($journalName);
            return;
        }

        if ($dataState === 'before' && $physicalState === 'after') {
            $this->moveDirectoryAtomically(
                $this->uploadsPath . '/' . $journal['media_path'],
                $this->archiveDirectory((string) $journal['archive_slug']),
                $this->archivesPath
            );
            $this->deleteJournal($journalName);
            return;
        }

        if ($dataState === 'after' && $physicalState === 'before') {
            $this->transitionDataState($journal, 'after', 'before');
            $this->deleteJournal($journalName);
            return;
        }

        throw new StorageException('Media directory restore recovery found mixed state.');
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function recoverPurge(string $journalName, array $journal): void
    {
        $dataState = $this->currentDataState($journal);
        $physicalState = $this->purgePhysicalState($journal);
        $archiveRoot = $this->archiveRoot((string) $journal['archive_slug']);
        $stagingRoot = $this->purgeRoot((string) $journal['archive_slug']);

        if ($dataState === 'before' && $physicalState === 'before') {
            $this->deleteJournal($journalName);
            return;
        }

        if ($dataState === 'before' && $physicalState === 'staged') {
            $this->moveDirectoryAtomically($stagingRoot, $archiveRoot, $this->archivesPath);
            $this->deleteJournal($journalName);
            return;
        }

        if ($dataState === 'after' && $physicalState === 'before') {
            $this->transitionDataState($journal, 'after', 'before');
            $this->deleteJournal($journalName);
            return;
        }

        if ($dataState === 'after' && $physicalState === 'staged') {
            $this->markJournalCommitted($journalName, $journal);
            $this->finishPurge($stagingRoot, $journalName);
            return;
        }

        if ($dataState === 'after' && $physicalState === 'purged') {
            $this->deleteJournal($journalName);
            return;
        }

        throw new StorageException('Media directory purge recovery found mixed state.');
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function transitionDataState(array $journal, string $from, string $to): void
    {
        if (!in_array($from, ['before', 'after'], true) || !in_array($to, ['before', 'after'], true)) {
            throw new StorageException('Invalid media directory transaction state transition.');
        }
        if ($this->currentDataState($journal) !== $from) {
            throw new StorageException('Media directory transaction data changed concurrently.');
        }

        $fromState = $journal['data'][$from];
        $toState = $journal['data'][$to];
        $operations = [];
        foreach ([
            'media' => self::MEDIA_FILE,
            'record' => $this->recordPath((string) $journal['trash_id']),
        ] as $key => $path) {
            if ($this->descriptorsMatch($fromState[$key], $toState[$key])) {
                continue;
            }

            $operations[] = [
                'path' => $path,
                'contents' => $this->descriptorContents($toState[$key]),
            ];
        }

        if ($operations !== []) {
            $this->dataTransactions->commit($operations);
        }

        if ($this->currentDataState($journal) !== $to) {
            throw new StorageException('Media directory transaction data verification failed.');
        }
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function currentDataState(array $journal): string
    {
        $media = $this->descriptorFromSnapshot($this->data->snapshot(self::MEDIA_FILE, []));
        $record = $this->descriptorFromSnapshot(
            $this->data->snapshot($this->recordPath((string) $journal['trash_id']), [])
        );
        $before = $journal['data']['before'];
        $after = $journal['data']['after'];

        if ($this->descriptorsMatch($media, $before['media']) && $this->descriptorsMatch($record, $before['record'])) {
            return 'before';
        }
        if ($this->descriptorsMatch($media, $after['media']) && $this->descriptorsMatch($record, $after['record'])) {
            return 'after';
        }

        return 'mixed';
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function archivePhysicalState(array $journal): string
    {
        $source = $this->directoryState($this->uploadsPath . '/' . $journal['media_path']);
        $archive = $this->directoryState($this->archiveDirectory((string) $journal['archive_slug']));
        if ($source === 'directory' && $archive === 'absent') {
            return 'before';
        }
        if ($source === 'absent' && $archive === 'directory') {
            return 'after';
        }

        return 'mixed';
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function restorePhysicalState(array $journal): string
    {
        $source = $this->directoryState($this->archiveDirectory((string) $journal['archive_slug']));
        $target = $this->directoryState($this->uploadsPath . '/' . $journal['media_path']);
        if ($source === 'directory' && $target === 'absent') {
            return 'before';
        }
        if ($source === 'absent' && $target === 'directory') {
            return 'after';
        }

        return 'mixed';
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function purgePhysicalState(array $journal): string
    {
        $archive = $this->directoryState($this->archiveRoot((string) $journal['archive_slug']));
        $staging = $this->directoryState($this->purgeRoot((string) $journal['archive_slug']));
        if ($archive === 'directory' && $staging === 'absent') {
            return 'before';
        }
        if ($archive === 'absent' && $staging === 'directory') {
            return 'staged';
        }
        if ($archive === 'absent' && $staging === 'absent') {
            return 'purged';
        }

        return 'mixed';
    }

    private function finishPurge(string $stagingRoot, string $journalName): void
    {
        $this->removeDirectoryTree($stagingRoot, $this->purgePath);
        $this->deleteJournal($journalName);
    }

    /**
     * @param array<string, mixed> $record
     * @param array<int, array{position: int, entry: array<string, mixed>}> $repositoryEntries
     * @return array<string, mixed>
     */
    private function buildArchiveRecord(
        string $trashId,
        string $mediaPath,
        string $archiveSlug,
        array $repositoryEntries,
        string $deletedBy
    ): array {
        $now = date('Y-m-d H:i:s');

        return [
            'id' => $trashId,
            'entity_type' => 'media',
            'entity_id' => 'media-directory:' . $mediaPath,
            'entity_title' => basename($mediaPath),
            'entity_slug' => $mediaPath,
            'deleted_at' => $now,
            'deleted_by' => trim($deletedBy),
            'payload' => [
                'kind' => 'directory',
                'path' => $mediaPath,
                'folder' => explode('/', $mediaPath, 2)[0],
                'name' => basename($mediaPath),
                'archive_root' => 'storage/trash/media/' . $archiveSlug,
                'archive_directory' => 'storage/trash/media/' . $archiveSlug . '/directory',
                'repository_entries' => $repositoryEntries,
            ],
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, mixed> $mediaBefore
     * @param array<string, mixed> $recordBefore
     * @param array<string, mixed> $mediaAfter
     * @param array<string, mixed> $recordAfter
     * @return array<string, mixed>
     */
    private function buildJournal(
        string $operation,
        string $mediaPath,
        string $archiveSlug,
        array $record,
        array $mediaBefore,
        array $recordBefore,
        array $mediaAfter,
        array $recordAfter
    ): array {
        return [
            'schema' => self::JOURNAL_SCHEMA,
            'operation' => $operation,
            'state' => 'prepared',
            'created_at' => gmdate('c'),
            'media_path' => $mediaPath,
            'archive_slug' => $archiveSlug,
            'trash_id' => (string) $record['id'],
            'record' => $record,
            'data' => [
                'before' => ['media' => $mediaBefore, 'record' => $recordBefore],
                'after' => ['media' => $mediaAfter, 'record' => $recordAfter],
            ],
        ];
    }

    private function writeJournal(array $journal): string
    {
        $name = 'transaction-' . bin2hex(random_bytes(12)) . '.json';
        $this->journals->write($name, $journal);
        return $name;
    }

    /**
     * @param array<string, mixed> $journal
     */
    private function markJournalCommitted(string $journalName, array $journal): void
    {
        $journal['state'] = 'committed';
        $this->journals->write($journalName, $journal);
    }

    private function deleteJournal(string $journalName): void
    {
        $path = $this->journalRoot . '/' . $journalName;
        if (is_file($path) && !$this->journals->delete($journalName)) {
            throw new StorageException('Unable to finalize media directory transaction journal.');
        }
    }

    private function recoverJournalSilently(string $journalName): void
    {
        try {
            $path = $this->journalRoot . '/' . $journalName;
            if (!is_file($path) || is_link($path)) {
                return;
            }
            $this->recoverJournalUnlocked($journalName, $this->normalizeJournal($this->journals->read($journalName)));
        } catch (StorageException) {
            // Keep the journal for deterministic recovery on the next request.
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadDirectoryRecord(array $item): ?array
    {
        $trashId = $this->normalizeTrashId((string) ($item['id'] ?? ''));
        if ($trashId === null) {
            return null;
        }

        $snapshot = $this->data->snapshot($this->recordPath($trashId), []);
        if ($snapshot['hash'] === null || !is_array($snapshot['data'])) {
            return null;
        }

        $record = $snapshot['data'];
        $payload = $record['payload'] ?? null;
        if (
            (string) ($record['id'] ?? '') !== $trashId
            || (string) ($record['entity_type'] ?? '') !== 'media'
            || !is_array($payload)
            || (string) ($payload['kind'] ?? '') !== 'directory'
        ) {
            return null;
        }

        return [
            'record' => $record,
            'descriptor' => $this->descriptorFromSnapshot($snapshot),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findRecordByEntityId(string $entityId): ?array
    {
        $directory = $this->dataRoot . '/trash/media';
        if (!is_dir($directory)) {
            return null;
        }
        if (is_link($directory)) {
            throw new StorageException('Trash media records directory cannot be a symbolic link.');
        }

        foreach (glob($directory . '/*.json') ?: [] as $file) {
            if (is_link($file)) {
                throw new StorageException('Trash media record cannot be a symbolic link.');
            }
            if (!is_file($file)) {
                continue;
            }

            $record = $this->data->read('trash/media/' . basename($file));
            if ((string) ($record['entity_id'] ?? '') === $entityId) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function recordArchiveSlug(array $record): ?string
    {
        $payload = is_array($record['payload'] ?? null) ? $record['payload'] : [];
        $archiveRoot = trim((string) ($payload['archive_root'] ?? ''));
        $archiveDirectory = trim((string) ($payload['archive_directory'] ?? ''));
        if (!preg_match('#^storage/trash/media/([A-Za-z0-9_-]+)$#', $archiveRoot, $matches)) {
            return null;
        }

        $slug = $matches[1];
        if ($archiveDirectory !== 'storage/trash/media/' . $slug . '/directory') {
            return null;
        }

        return $slug;
    }

    private function normalizeMediaDirectoryPath(string $rawPath): ?string
    {
        $path = trim(str_replace('\\', '/', $rawPath), '/');
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        $parts = explode('/', $path);
        if (
            count($parts) < 2
            || in_array('', $parts, true)
            || in_array('.', $parts, true)
            || in_array('..', $parts, true)
            || !array_key_exists($parts[0], MediaModel::FOLDERS)
        ) {
            return null;
        }

        return implode('/', $parts);
    }

    private function normalizeTrashId(string $id): ?string
    {
        $id = trim($id);
        return preg_match('/^[A-Za-z0-9_-]+$/', $id) === 1 ? $id : null;
    }

    private function isSafeMediaDirectory(string $path, string $mediaPath): bool
    {
        if (
            is_link($this->uploadsPath)
            || !is_dir($path)
            || is_link($path)
            || $this->hasSymlinkInMediaPath($mediaPath)
        ) {
            return false;
        }

        try {
            $this->assertDirectoryContainsNoSymlink($path);
        } catch (StorageException) {
            return false;
        }

        return true;
    }

    private function isSafeArchivedDirectory(string $path): bool
    {
        if (!is_dir($path) || is_link($path)) {
            return false;
        }

        try {
            $this->assertDirectoryContainsNoSymlink($path);
        } catch (StorageException) {
            return false;
        }

        return true;
    }

    private function hasSymlinkInMediaPath(string $mediaPath): bool
    {
        $current = $this->uploadsPath;
        foreach (explode('/', $mediaPath) as $part) {
            $current .= '/' . $part;
            if (is_link($current)) {
                return true;
            }
        }

        return false;
    }

    private function assertDirectoryContainsNoSymlink(string $directory): void
    {
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } catch (\UnexpectedValueException $exception) {
            throw new StorageException('Unable to inspect media directory.', 0, $exception);
        }

        foreach ($iterator as $item) {
            if ($item instanceof SplFileInfo && $item->isLink()) {
                throw new StorageException('Media directory contains a symbolic link.');
            }
        }
    }

    private function moveDirectoryAtomically(string $source, string $destination, string $destinationRoot): void
    {
        if ($this->directoryState($source) !== 'directory') {
            throw new StorageException('Media directory source is unavailable.');
        }
        if (file_exists($destination) || is_link($destination)) {
            throw new StorageException('Media directory destination already exists.');
        }

        $this->assertDirectoryContainsNoSymlink($source);
        $this->ensureSafeDirectory(dirname($destination), $destinationRoot);

        $sourceStat = stat($source);
        $destinationStat = stat(dirname($destination));
        if (!is_array($sourceStat) || !is_array($destinationStat) || ($sourceStat['dev'] ?? null) !== ($destinationStat['dev'] ?? null)) {
            throw new StorageException('Media directory move requires one filesystem.');
        }

        if (!@rename($source, $destination)) {
            throw new StorageException('Unable to atomically move media directory.');
        }
        if ($this->directoryState($destination) !== 'directory' || file_exists($source)) {
            throw new StorageException('Media directory move verification failed.');
        }
    }

    private function ensureSafeDirectory(string $directory, string $root): void
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $directory = rtrim(str_replace('\\', '/', $directory), '/');
        if ($directory !== $root && !str_starts_with($directory, $root . '/')) {
            throw new StorageException('Media directory target escapes its root.');
        }

        if (is_link($root)) {
            throw new StorageException('Media directory root cannot be a symbolic link.');
        }
        if (!is_dir($root) && !mkdir($root, 0755, true) && !is_dir($root)) {
            throw new StorageException('Unable to create media directory root.');
        }

        $relative = ltrim(substr($directory, strlen($root)), '/');
        $current = $root;
        if ($relative === '') {
            return;
        }

        foreach (explode('/', $relative) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                throw new StorageException('Invalid media directory target.');
            }
            $current .= '/' . $part;
            if (is_link($current)) {
                throw new StorageException('Media directory target contains a symbolic link.');
            }
            if (!is_dir($current) && !mkdir($current, 0755, true) && !is_dir($current)) {
                throw new StorageException('Unable to create media directory target.');
            }
        }
    }

    private function removeDirectoryTree(string $directory, string $root): void
    {
        if ($this->directoryState($directory) === 'absent') {
            return;
        }
        $this->assertPathWithinRoot($directory, $root);
        $this->assertDirectoryContainsNoSymlink($directory);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if ($item->isLink()) {
                throw new StorageException('Purge target contains a symbolic link.');
            }
            if ($item->isDir()) {
                if (!rmdir($path)) {
                    throw new StorageException('Unable to remove archived media directory.');
                }
                continue;
            }
            if (!unlink($path)) {
                throw new StorageException('Unable to remove archived media file.');
            }
        }

        if (!rmdir($directory)) {
            throw new StorageException('Unable to remove archived media root.');
        }
    }

    private function removeEmptyDirectory(string $directory, string $root): void
    {
        if ($this->directoryState($directory) === 'absent') {
            return;
        }
        $this->assertPathWithinRoot($directory, $root);
        $entries = scandir($directory);
        if (!is_array($entries) || array_diff($entries, ['.', '..']) !== []) {
            return;
        }
        if (!rmdir($directory)) {
            throw new StorageException('Unable to remove empty media archive directory.');
        }
    }

    private function directoryState(string $path): string
    {
        clearstatcache(true, $path);
        if (is_link($path)) {
            throw new StorageException('Media directory path cannot be a symbolic link.');
        }
        if (!file_exists($path)) {
            return 'absent';
        }
        if (!is_dir($path)) {
            throw new StorageException('Media directory path is not a directory.');
        }

        return 'directory';
    }

    private function assertPathWithinRoot(string $path, string $root): void
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $root = rtrim(str_replace('\\', '/', $root), '/');
        if ($path !== $root && !str_starts_with($path, $root . '/')) {
            throw new StorageException('Media directory path escapes the allowed root.');
        }
    }

    /**
     * @param array<string|int, mixed> $repository
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRepository(array $repository): array
    {
        $normalized = [];
        foreach ($repository as $record) {
            if (!is_array($record)) {
                throw new StorageException('Media repository contains an invalid record.');
            }
            $normalized[] = $record;
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $repository
     * @return array<int, array{position: int, entry: array<string, mixed>}>
     */
    private function snapshotRepositoryEntries(array $repository, string $mediaPath): array
    {
        $snapshots = [];
        $prefix = rtrim($mediaPath, '/') . '/';
        foreach ($repository as $position => $entry) {
            $path = (string) ($entry['path'] ?? '');
            if (!str_starts_with($path, $prefix)) {
                continue;
            }
            $snapshots[] = ['position' => $position, 'entry' => $entry];
        }

        return $snapshots;
    }

    /**
     * @param array<int, array<string, mixed>> $repository
     * @return array<int, array<string, mixed>>
     */
    private function withoutRepositoryEntries(array $repository, string $mediaPath): array
    {
        $prefix = rtrim($mediaPath, '/') . '/';

        return array_values(array_filter($repository, static function (array $entry) use ($prefix): bool {
            return !str_starts_with((string) ($entry['path'] ?? ''), $prefix);
        }));
    }

    /**
     * @param mixed $snapshots
     * @return array<int, array{position: int, entry: array<string, mixed>}>
     */
    private function normalizeRepositorySnapshots(mixed $snapshots): array
    {
        if (!is_array($snapshots)) {
            throw new StorageException('Media directory Trash record has invalid repository snapshots.');
        }

        $normalized = [];
        foreach ($snapshots as $snapshot) {
            if (!is_array($snapshot) || !is_array($snapshot['entry'] ?? null)) {
                throw new StorageException('Media directory Trash record has invalid repository entry.');
            }
            $normalized[] = [
                'position' => max(0, (int) ($snapshot['position'] ?? 0)),
                'entry' => $snapshot['entry'],
            ];
        }

        usort($normalized, static fn (array $left, array $right): int => $left['position'] <=> $right['position']);
        return $normalized;
    }

    /**
     * @param array<int, array{position: int, entry: array<string, mixed>}> $snapshots
     * @param array<int, array<string, mixed>> $repository
     */
    private function repositorySnapshotsConflict(array $snapshots, array $repository): bool
    {
        $paths = [];
        $ids = [];
        foreach ($repository as $entry) {
            $path = trim((string) ($entry['path'] ?? ''));
            $id = trim((string) ($entry['id'] ?? ''));
            if ($path !== '') {
                $paths[$path] = true;
            }
            if ($id !== '') {
                $ids[$id] = true;
            }
        }

        foreach ($snapshots as $snapshot) {
            $entry = $snapshot['entry'];
            $path = trim((string) ($entry['path'] ?? ''));
            $id = trim((string) ($entry['id'] ?? ''));
            if (($path !== '' && isset($paths[$path])) || ($id !== '' && isset($ids[$id]))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $repository
     * @param array<int, array{position: int, entry: array<string, mixed>}> $snapshots
     * @return array<int, array<string, mixed>>
     */
    private function restoreRepositorySnapshots(array $repository, array $snapshots): array
    {
        foreach ($snapshots as $snapshot) {
            $position = min($snapshot['position'], count($repository));
            array_splice($repository, $position, 0, [$snapshot['entry']]);
        }

        return array_values($repository);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array{exists: bool, sha256: string, contents_b64?: string}
     */
    private function descriptorFromSnapshot(array $snapshot): array
    {
        $hash = $snapshot['hash'] ?? null;
        $contents = $snapshot['contents'] ?? null;
        if ($hash === null) {
            return $this->absentDescriptor();
        }
        if (!is_string($hash) || !is_string($contents) || !hash_equals($hash, hash('sha256', $contents))) {
            throw new StorageException('Invalid media directory transaction snapshot.');
        }

        return [
            'exists' => true,
            'sha256' => $hash,
            'contents_b64' => base64_encode($contents),
        ];
    }

    /**
     * @return array{exists: bool, sha256: string, contents_b64: string}
     */
    private function descriptorFromContents(string $contents): array
    {
        return [
            'exists' => true,
            'sha256' => hash('sha256', $contents),
            'contents_b64' => base64_encode($contents),
        ];
    }

    /**
     * @return array{exists: false, sha256: string}
     */
    private function absentDescriptor(): array
    {
        return ['exists' => false, 'sha256' => ''];
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function descriptorsMatch(array $left, array $right): bool
    {
        return (bool) ($left['exists'] ?? false) === (bool) ($right['exists'] ?? false)
            && hash_equals((string) ($left['sha256'] ?? ''), (string) ($right['sha256'] ?? ''));
    }

    /**
     * @param array<string, mixed> $descriptor
     */
    private function descriptorContents(array $descriptor): ?string
    {
        if (empty($descriptor['exists'])) {
            return null;
        }

        $encoded = $descriptor['contents_b64'] ?? null;
        if (!is_string($encoded)) {
            throw new StorageException('Media directory transaction snapshot has no contents.');
        }
        $contents = base64_decode($encoded, true);
        if (!is_string($contents) || !hash_equals((string) ($descriptor['sha256'] ?? ''), hash('sha256', $contents))) {
            throw new StorageException('Media directory transaction snapshot contents are invalid.');
        }

        return $contents;
    }

    /**
     * @param array<string, mixed> $journal
     * @return array<string, mixed>
     */
    private function normalizeJournal(array $journal): array
    {
        if (
            (int) ($journal['schema'] ?? 0) !== self::JOURNAL_SCHEMA
            || !in_array($journal['operation'] ?? null, ['archive', 'restore', 'purge'], true)
            || !in_array($journal['state'] ?? null, ['prepared', 'committed'], true)
            || !is_array($journal['record'] ?? null)
            || !is_array($journal['data'] ?? null)
        ) {
            throw new StorageException('Media directory transaction journal is invalid.');
        }

        $mediaPath = $this->normalizeMediaDirectoryPath((string) ($journal['media_path'] ?? ''));
        $archiveSlug = trim((string) ($journal['archive_slug'] ?? ''));
        $trashId = $this->normalizeTrashId((string) ($journal['trash_id'] ?? ''));
        if (
            $mediaPath === null
            || preg_match('/^[A-Za-z0-9_-]+$/', $archiveSlug) !== 1
            || $trashId === null
            || (string) ($journal['record']['id'] ?? '') !== $trashId
        ) {
            throw new StorageException('Media directory transaction journal identity is invalid.');
        }

        $record = $journal['record'];
        $payload = $record['payload'] ?? null;
        if (
            (string) ($record['entity_type'] ?? '') !== 'media'
            || (string) ($record['entity_id'] ?? '') !== 'media-directory:' . $mediaPath
            || !is_array($payload)
            || (string) ($payload['kind'] ?? '') !== 'directory'
            || $this->normalizeMediaDirectoryPath((string) ($payload['path'] ?? '')) !== $mediaPath
            || $this->recordArchiveSlug($record) !== $archiveSlug
        ) {
            throw new StorageException('Media directory transaction journal record does not match its operation.');
        }

        $journal['media_path'] = $mediaPath;
        $journal['archive_slug'] = $archiveSlug;
        $journal['trash_id'] = $trashId;
        foreach (['before', 'after'] as $state) {
            $stateData = $journal['data'][$state] ?? null;
            if (!is_array($stateData)) {
                throw new StorageException('Media directory transaction journal data state is invalid.');
            }
            $journal['data'][$state] = [
                'media' => $this->normalizeDescriptor($stateData['media'] ?? null),
                'record' => $this->normalizeDescriptor($stateData['record'] ?? null),
            ];
        }

        return $journal;
    }

    /**
     * @return array{exists: bool, sha256: string, contents_b64?: string}
     */
    private function normalizeDescriptor(mixed $descriptor): array
    {
        if (!is_array($descriptor) || !is_bool($descriptor['exists'] ?? null) || !is_string($descriptor['sha256'] ?? null)) {
            throw new StorageException('Media directory transaction snapshot is invalid.');
        }

        if (!$descriptor['exists']) {
            if ($descriptor['sha256'] !== '') {
                throw new StorageException('Media directory transaction missing snapshot has a checksum.');
            }
            return $this->absentDescriptor();
        }

        $contents = $this->descriptorContents($descriptor);
        if ($contents === null) {
            throw new StorageException('Media directory transaction existing snapshot has no contents.');
        }

        return $this->descriptorFromContents($contents);
    }

    /**
     * @return array<int, string>
     */
    private function journalNames(): array
    {
        $names = [];
        foreach (glob($this->journalRoot . '/transaction-*.json') ?: [] as $path) {
            if (is_link($path)) {
                throw new StorageException('Media directory transaction journal cannot be a symbolic link.');
            }
            if (is_file($path)) {
                $names[] = basename($path);
            }
        }

        sort($names, SORT_NATURAL);
        return $names;
    }

    private function recordPath(string $trashId): string
    {
        $trashId = $this->normalizeTrashId($trashId);
        if ($trashId === null) {
            throw new StorageException('Invalid media directory Trash identifier.');
        }

        return 'trash/media/' . $trashId . '.json';
    }

    private function archiveRoot(string $archiveSlug): string
    {
        return $this->archivesPath . '/' . $archiveSlug;
    }

    private function archiveDirectory(string $archiveSlug): string
    {
        return $this->archiveRoot($archiveSlug) . '/directory';
    }

    private function purgeRoot(string $archiveSlug): string
    {
        return $this->purgePath . '/' . $archiveSlug;
    }

    private function newArchiveSlug(string $mediaPath): string
    {
        $base = preg_replace('/[^A-Za-z0-9_-]/', '_', str_replace('/', '_', $mediaPath)) ?? 'media';
        return trim($base, '_') . '_' . bin2hex(random_bytes(12));
    }

    private function newTrashId(): string
    {
        return date('YmdHis') . '_' . bin2hex(random_bytes(4));
    }

    private function checkpoint(string $stage): void
    {
        if ($this->checkpoint !== null) {
            ($this->checkpoint)($stage);
        }
    }

    private function resolveBasePath(?string $basePath): string
    {
        $candidate = $basePath ?? (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4));
        $candidate = rtrim(str_replace('\\', '/', $candidate), '/');
        if ($candidate === '' || !str_starts_with($candidate, '/') || str_contains($candidate, "\0")) {
            throw new StorageException('Media directory transaction base path is invalid.');
        }

        if (str_ends_with($candidate, '/public') && is_dir($candidate . '/../data')) {
            $candidate = dirname($candidate);
        }

        return $candidate;
    }
}
