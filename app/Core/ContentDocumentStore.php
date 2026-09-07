<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core;

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\JsonStore;
use App\Core\Storage\RecoverableFileTransaction;
use App\Core\Storage\StorageException;

final class ContentDocumentStore
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private static array $documentCache = [];

    /** @var array<string, array<string, array<string, mixed>>> */
    private static array $documentByIdCache = [];

    /** @var array<string, array{writer: AtomicFileWriter, locks: FileLockManager, journals: JsonStore}> */
    private static array $storageContexts = [];

    /** @var array<string, RecoverableFileTransaction> */
    private static array $transactions = [];

    private string $basePath;
    private string $entity;
    private AtomicFileWriter $writer;
    private RecoverableFileTransaction $transaction;

    public function __construct(string $entity)
    {
        $this->entity = trim($entity, '/');
        if ($this->entity === '') {
            throw new StorageException('Content document entity cannot be empty.');
        }

        $dataRoot = rtrim(BASE_PATH . '/data', '/');
        $contextKey = $dataRoot;
        if (!isset(self::$storageContexts[$contextKey])) {
            $locks = new FileLockManager(BASE_PATH . '/storage/cache/locks/content-documents');
            $writer = new AtomicFileWriter($dataRoot, $locks);

            $journalRoot = BASE_PATH . '/storage/transactions/content-documents';
            $journalWriter = new AtomicFileWriter(
                $journalRoot,
                new FileLockManager(BASE_PATH . '/storage/cache/locks/content-document-journals'),
                0700,
                0600
            );

            self::$storageContexts[$contextKey] = [
                'writer' => $writer,
                'locks' => $locks,
                'journals' => new JsonStore($journalRoot, $journalWriter),
            ];
        }

        $context = self::$storageContexts[$contextKey];
        $this->writer = $context['writer'];
        $this->basePath = $this->writer->ensureDirectory($this->entity);

        $transactionKey = $this->writer->root() . '|' . $this->entity;
        if (!isset(self::$transactions[$transactionKey])) {
            self::$transactions[$transactionKey] = new RecoverableFileTransaction(
                $this->writer,
                $context['journals'],
                $context['locks'],
                'content-documents:' . $this->writer->relativePath($this->basePath)
            );
        }
        $this->transaction = self::$transactions[$transactionKey];
    }

    public static function for(string $entity): self
    {
        return new self($entity);
    }

    public static function resetRequestCache(): void
    {
        self::$documentCache = [];
        self::$documentByIdCache = [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->synchronized(fn (): array => $this->allUnlocked());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allUnlocked(): array
    {
        if (array_key_exists($this->basePath, self::$documentCache)) {
            return self::$documentCache[$this->basePath];
        }

        $items = [];
        $seen = [];
        $itemsById = [];

        foreach ($this->documentDirectories() as $directory) {
            $source = $this->readDocument($directory . '/index.json', $directory . '/content.html');
            if (is_array($source)) {
                $sourceId = trim((string) ($source['id'] ?? ''));
                if ($sourceId !== '') {
                    $items[] = $source;
                    $seen[$sourceId] = true;
                    $itemsById[$sourceId] = $source;
                }
            }

            $translationRoot = $directory . '/translations';
            if (!is_dir($translationRoot)) {
                continue;
            }

            foreach ($this->translationDirectories($translationRoot) as $translationDirectory) {
                $translation = $this->readDocument(
                    $translationDirectory . '/index.json',
                    $translationDirectory . '/content.html'
                );
                if (!is_array($translation)) {
                    continue;
                }

                $translationId = trim((string) ($translation['id'] ?? ''));
                if ($translationId === '' || isset($seen[$translationId])) {
                    continue;
                }

                $items[] = $translation;
                $seen[$translationId] = true;
                $itemsById[$translationId] = $translation;
            }
        }

        foreach ($this->legacyFiles() as $legacyFile) {
            $legacy = $this->readLegacyDocument($legacyFile);
            if (!is_array($legacy)) {
                continue;
            }

            $legacyId = trim((string) ($legacy['id'] ?? ''));
            if ($legacyId === '' || isset($seen[$legacyId])) {
                continue;
            }

            $items[] = $legacy;
            $seen[$legacyId] = true;
            $itemsById[$legacyId] = $legacy;
        }

        self::$documentCache[$this->basePath] = $items;
        self::$documentByIdCache[$this->basePath] = $itemsById;

        return self::$documentCache[$this->basePath];
    }

    public function find(string $id): ?array
    {
        return $this->synchronized(fn (): ?array => $this->findUnlocked($id));
    }

    private function findUnlocked(string $id): ?array
    {
        $safeId = $this->sanitizeId($id);
        if ($safeId === '') {
            return null;
        }

        if (isset(self::$documentCache[$this->basePath])) {
            return self::$documentByIdCache[$this->basePath][$safeId] ?? null;
        }

        if (isset(self::$documentByIdCache[$this->basePath][$safeId])) {
            return self::$documentByIdCache[$this->basePath][$safeId];
        }

        $location = $this->locateUnlocked($id);
        if (!is_array($location)) {
            return null;
        }

        if (($location['type'] ?? '') === 'legacy') {
            $document = $this->readLegacyDocument((string) $location['index']);
        } else {
            $document = $this->readDocument((string) $location['index'], (string) $location['content']);
        }

        if (is_array($document)) {
            self::$documentByIdCache[$this->basePath][$safeId] = $document;
        }

        return $document;
    }

    public function findBy(string $field, mixed $value): ?array
    {
        foreach ($this->all() as $item) {
            if (isset($item[$field]) && $item[$field] === $value) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function where(string $field, mixed $value): array
    {
        return array_filter($this->all(), static function (array $item) use ($field, $value): bool {
            return isset($item[$field]) && $item[$field] === $value;
        });
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $created = $this->createMany([$data]);
        return $created[0];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(string $id, array $data): ?array
    {
        return $this->synchronized(function () use ($id, $data): ?array {
            $existing = $this->findUnlocked($id);
            if (!is_array($existing)) {
                return null;
            }

            $safeId = $this->sanitizeId($id);
            $saved = $this->persistMany([$safeId => array_merge($existing, $data, ['id' => $safeId])], false);
            return $saved[$safeId] ?? null;
        });
    }

    public function delete(string $id): bool
    {
        return $this->deleteMany([$id]);
    }

    /**
     * @param array<int|string, array<string, mixed>> $documents
     * @return array<int|string, array<string, mixed>>
     */
    public function createMany(array $documents): array
    {
        return $this->synchronized(fn (): array => $this->persistMany($documents, true));
    }

    /**
     * Creates a source document and its translations as one logical commit.
     *
     * @param array<int|string, array<string, mixed>> $documents
     * @return array<int|string, array<string, mixed>>
     */
    public function createTranslationGroup(array $documents, string $sourceLocale): array
    {
        return $this->synchronized(function () use ($documents, $sourceLocale): array {
            $sourceLocale = $this->sanitizeLocale($sourceLocale);
            if ($sourceLocale === '' || !is_array($documents[$sourceLocale] ?? null)) {
                throw new StorageException('Content document source locale is invalid.');
            }

            $source = $documents[$sourceLocale];
            $reservedIds = [];
            $sourceId = $this->sanitizeId((string) ($source['id'] ?? ''));
            if ($sourceId === '') {
                $sourceId = $this->generateDocumentId($source, $reservedIds);
            }
            if (is_array($this->locateUnlocked($sourceId))) {
                throw new StorageException('Content document already exists: ' . $sourceId);
            }

            $source['id'] = $sourceId;
            $source['translation_group'] = $sourceId;
            $source['locale'] = $sourceLocale;
            $source['source_locale'] = $sourceLocale;
            unset($documents[$sourceLocale]);
            $documents = [$sourceLocale => $source] + $documents;
            $reservedIds[$sourceId] = true;

            $normalized = [];
            foreach ($documents as $key => $document) {
                if (!is_array($document)) {
                    throw new StorageException('Content document group contains an invalid document.');
                }

                $locale = $this->sanitizeLocale((string) $key);
                if ($locale === '') {
                    $locale = $this->sanitizeLocale((string) ($document['locale'] ?? ''));
                }
                if ($locale === '') {
                    throw new StorageException('Content document translation locale is invalid.');
                }

                if ($locale === $sourceLocale) {
                    $normalized[$locale] = $source;
                    continue;
                }

                $document['translation_group'] = $sourceId;
                $document['locale'] = $locale;
                $document['source_locale'] = $sourceLocale;
                $documentId = $this->sanitizeId((string) ($document['id'] ?? ''));
                if ($documentId === '') {
                    $documentId = $this->generateDocumentId($document, $reservedIds);
                }
                if (isset($reservedIds[$documentId]) || is_array($this->locateUnlocked($documentId))) {
                    throw new StorageException('Content document already exists: ' . $documentId);
                }

                $document['id'] = $documentId;
                $reservedIds[$documentId] = true;
                $normalized[$locale] = $document;
            }

            return $this->persistMany($normalized, true);
        });
    }

    /**
     * Persists a mixed set of existing and new source or translation documents
     * in one recoverable transaction.
     *
     * @param array<int|string, array<string, mixed>> $documents
     * @return array<int|string, array<string, mixed>>
     */
    public function saveMany(array $documents): array
    {
        return $this->synchronized(fn (): array => $this->persistMany($documents, false));
    }

    /**
     * @param array<int, string> $ids
     */
    public function deleteMany(array $ids): bool
    {
        return $this->synchronized(function () use ($ids): bool {
            $locations = [];
            foreach ($ids as $id) {
                $safeId = $this->sanitizeId($id);
                if ($safeId === '' || isset($locations[$safeId])) {
                    continue;
                }

                $location = $this->locateUnlocked($safeId);
                if (!is_array($location)) {
                    return false;
                }
                $locations[$safeId] = $location;
            }

            if ($locations === []) {
                return false;
            }

            $operations = [];
            $cleanupDirectories = [];
            foreach ($locations as $location) {
                foreach (['index', 'content'] as $key) {
                    $path = trim((string) ($location[$key] ?? ''));
                    if ($path === '' || (!is_file($path) && !is_link($path))) {
                        continue;
                    }

                    $operations[] = [
                        'path' => $this->writer->relativePath($path),
                        'contents' => null,
                    ];
                }

                $directory = trim((string) ($location['directory'] ?? ''));
                if ($directory !== '') {
                    $cleanupDirectories = array_merge($cleanupDirectories, $this->documentCleanupDirectories($directory));
                }
            }

            if ($operations === []) {
                return false;
            }

            $this->transaction->commit($operations, $cleanupDirectories);
            $this->invalidateCache();

            return true;
        });
    }

    public function recover(): void
    {
        $this->transaction->recover();
        $this->invalidateCache();
    }

    public function exists(string $id): bool
    {
        return $this->synchronized(fn (): bool => is_array($this->locateUnlocked($id)));
    }

    public function count(): int
    {
        return count($this->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $all = $this->all();
        $total = count($all);
        $totalPages = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        usort($all, static function (array $a, array $b): int {
            return ((string) ($b['created_at'] ?? '')) <=> ((string) ($a['created_at'] ?? ''));
        });

        return [
            'data' => array_slice($all, $offset, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ];
    }

    /**
     * @param array<int, string> $fields
     * @return array<int|string, array<string, mixed>>
     */
    public function search(string $query, array $fields = ['title', 'name']): array
    {
        $normalizedQuery = strtolower($query);

        return array_filter($this->all(), static function (array $item) use ($normalizedQuery, $fields): bool {
            foreach ($fields as $field) {
                if (isset($item[$field]) && str_contains(strtolower((string) $item[$field]), $normalizedQuery)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * @param array<int|string, array<string, mixed>> $documents
     * @return array<int|string, array<string, mixed>>
     */
    private function persistMany(array $documents, bool $createOnly): array
    {
        if ($documents === []) {
            return [];
        }

        $prepared = [];
        $locations = [];
        $reservedIds = [];
        $now = date('Y-m-d H:i:s');

        foreach ($documents as $key => $data) {
            if (!is_array($data)) {
                throw new StorageException('Content document batch contains an invalid document.');
            }

            $id = $this->sanitizeId((string) ($data['id'] ?? ''));
            if ($id === '') {
                $id = $this->generateDocumentId($data, $reservedIds);
            }
            if (isset($reservedIds[$id])) {
                throw new StorageException('Content document batch contains a duplicate identifier: ' . $id);
            }

            $existingLocation = $this->locateUnlocked($id);
            if ($createOnly && is_array($existingLocation)) {
                throw new StorageException('Content document already exists: ' . $id);
            }

            $existing = $this->documentAtLocation($existingLocation);
            if (is_array($existing)) {
                $document = array_merge($existing, $data);
                $document['updated_at'] = $now;
            } else {
                $document = $data;
                $document['created_at'] = $document['created_at'] ?? $now;
                $document['updated_at'] = $document['updated_at'] ?? $now;
            }

            $document['id'] = $id;
            $prepared[$key] = $document;
            $locations[$key] = $existingLocation;
            $reservedIds[$id] = true;
        }

        $operations = [];
        foreach ($prepared as $key => $document) {
            $existingLocation = $locations[$key] ?? null;
            $location = $this->resolveWriteLocation($document, is_array($existingLocation) ? $existingLocation : null);
            $metadata = $document;
            unset($metadata['content']);

            $operations[] = [
                'path' => $this->writer->relativePath((string) $location['index']),
                'contents' => $this->encodeMetadata($metadata),
            ];
            $operations[] = [
                'path' => $this->writer->relativePath((string) $location['content']),
                'contents' => (string) ($document['content'] ?? ''),
            ];

            if (is_array($existingLocation) && ($existingLocation['type'] ?? '') === 'legacy') {
                $legacyPath = trim((string) ($existingLocation['index'] ?? ''));
                if ($legacyPath !== '') {
                    $operations[] = [
                        'path' => $this->writer->relativePath($legacyPath),
                        'contents' => null,
                    ];
                }
            }
        }

        $this->transaction->commit($operations);
        $this->invalidateCache();

        return $prepared;
    }

    /**
     * @param array<string, string>|null $location
     * @return array<string, mixed>|null
     */
    private function documentAtLocation(?array $location): ?array
    {
        if (!is_array($location)) {
            return null;
        }

        if (($location['type'] ?? '') === 'legacy') {
            return $this->readLegacyDocument((string) ($location['index'] ?? ''));
        }

        return $this->readDocument(
            (string) ($location['index'] ?? ''),
            (string) ($location['content'] ?? '')
        );
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function encodeMetadata(array $metadata): string
    {
        try {
            return json_encode(
                $metadata,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ) . PHP_EOL;
        } catch (\JsonException $exception) {
            throw new StorageException('Unable to encode content document metadata: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return array<int, string>
     */
    private function documentCleanupDirectories(string $directory): array
    {
        $directories = [];
        $current = rtrim($directory, '/');
        while ($current !== $this->basePath && str_starts_with($current, $this->basePath . '/')) {
            $directories[] = $this->writer->relativePath($current);
            $current = dirname($current);
        }

        return $directories;
    }

    private function synchronized(callable $operation): mixed
    {
        return $this->transaction->synchronized($operation);
    }

    /**
     * @return array<string, string>|null
     */
    private function locateUnlocked(string $id): ?array
    {
        $safeId = $this->sanitizeId($id);
        if ($safeId === '') {
            return null;
        }

        $sourceDirectory = $this->writer->resolvePath($this->entity . '/' . $safeId);
        $sourceIndex = $sourceDirectory . '/index.json';
        if (is_file($sourceIndex) && !is_link($sourceIndex)) {
            return [
                'type' => 'source',
                'directory' => $sourceDirectory,
                'index' => $sourceIndex,
                'content' => $sourceDirectory . '/content.html',
            ];
        }

        foreach ($this->documentDirectories() as $directory) {
            $translationRoot = $directory . '/translations';
            if (is_link($translationRoot)) {
                throw new StorageException('Content document translations cannot be a symbolic link: ' . $translationRoot);
            }
            if (!is_dir($translationRoot)) {
                continue;
            }

            foreach ($this->translationDirectories($translationRoot) as $translationDirectory) {
                $indexPath = $translationDirectory . '/index.json';
                $metadata = $this->readJson($indexPath);
                if (!is_array($metadata) || $this->sanitizeId((string) ($metadata['id'] ?? '')) !== $safeId) {
                    continue;
                }

                return [
                    'type' => 'translation',
                    'directory' => $translationDirectory,
                    'index' => $indexPath,
                    'content' => $translationDirectory . '/content.html',
                ];
            }
        }

        $legacyPath = $this->basePath . '/' . $safeId . '.json';
        if (is_file($legacyPath) && !is_link($legacyPath)) {
            return [
                'type' => 'legacy',
                'directory' => $this->basePath,
                'index' => $legacyPath,
                'content' => '',
            ];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string>|null $existingLocation
     * @return array<string, string>
     */
    private function resolveWriteLocation(array $data, ?array $existingLocation = null): array
    {
        if (is_array($existingLocation) && ($existingLocation['type'] ?? '') !== 'legacy') {
            return $existingLocation;
        }

        $id = $this->sanitizeId((string) ($data['id'] ?? ''));
        $translationGroup = $this->sanitizeId((string) ($data['translation_group'] ?? ''));
        $locale = $this->sanitizeLocale((string) ($data['locale'] ?? ''));
        $sourceLocale = $this->sanitizeLocale((string) ($data['source_locale'] ?? ''));
        $isTranslation = $translationGroup !== ''
            && $locale !== ''
            && $sourceLocale !== ''
            && strcasecmp($locale, $sourceLocale) !== 0;

        if ($isTranslation) {
            $translationDirectory = $this->basePath . '/' . $translationGroup . '/translations/' . $locale;
            return [
                'type' => 'translation',
                'directory' => $translationDirectory,
                'index' => $translationDirectory . '/index.json',
                'content' => $translationDirectory . '/content.html',
            ];
        }

        $sourceDirectory = $this->basePath . '/' . $id;
        return [
            'type' => 'source',
            'directory' => $sourceDirectory,
            'index' => $sourceDirectory . '/index.json',
            'content' => $sourceDirectory . '/content.html',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readDocument(string $indexPath, string $contentPath): ?array
    {
        $metadata = $this->readJson($indexPath);
        if (!is_array($metadata)) {
            return null;
        }

        $contentPath = $this->writer->resolvePath($contentPath);
        if (is_file($contentPath)) {
            $content = file_get_contents($contentPath);
            if (!is_string($content)) {
                throw new StorageException('Unable to read content document HTML: ' . $contentPath);
            }
            $metadata['content'] = $content;
        } else {
            $metadata['content'] = '';
        }

        return $metadata;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readLegacyDocument(string $path): ?array
    {
        return $this->readJson($path);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        $path = $this->writer->resolvePath($path);
        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if (!is_string($content)) {
            return null;
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new StorageException('Malformed content document metadata: ' . $path, 0, $exception);
        }

        if (!is_array($data)) {
            throw new StorageException('Content document metadata must decode to an array: ' . $path);
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function documentDirectories(): array
    {
        $directories = array_values(array_filter(
            glob($this->basePath . '/*', GLOB_ONLYDIR) ?: [],
            static fn (string $directory): bool => !is_link($directory)
        ));
        sort($directories, SORT_NATURAL);

        return $directories;
    }

    /**
     * @return array<int, string>
     */
    private function translationDirectories(string $translationRoot): array
    {
        if (is_link($translationRoot)) {
            throw new StorageException('Content document translations cannot be a symbolic link: ' . $translationRoot);
        }

        $directories = array_values(array_filter(
            glob($translationRoot . '/*', GLOB_ONLYDIR) ?: [],
            static fn (string $directory): bool => !is_link($directory)
        ));
        sort($directories, SORT_NATURAL);

        return $directories;
    }

    /**
     * @return array<int, string>
     */
    private function legacyFiles(): array
    {
        $files = array_values(array_filter(
            glob($this->basePath . '/*.json') ?: [],
            static fn (string $file): bool => !is_link($file)
        ));
        sort($files, SORT_NATURAL);

        return $files;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function generateDocumentId(array $data, array $reservedIds = []): string
    {
        $translationGroup = $this->sanitizeId((string) ($data['translation_group'] ?? ''));
        $locale = $this->sanitizeLocale((string) ($data['locale'] ?? ''));
        $sourceLocale = $this->sanitizeLocale((string) ($data['source_locale'] ?? ''));

        if ($translationGroup !== '' && $locale !== '' && $sourceLocale !== '' && strcasecmp($locale, $sourceLocale) !== 0) {
            return $translationGroup . '_' . strtolower(str_replace('-', '_', $locale));
        }

        $seed = trim((string) ($data['slug'] ?? ''));
        if ($seed === '') {
            $seed = trim((string) ($data['title'] ?? ''));
        }

        $slug = $this->slugifyId($seed);
        if ($slug === '') {
            $slug = date('YmdHis') . '_' . bin2hex(random_bytes(4));
        }

        $prefix = $this->entityPrefix();
        if (!str_starts_with($slug, $prefix . '_')) {
            $slug = $prefix . '_' . $slug;
        }

        $candidate = $slug;
        $suffix = 2;
        while (isset($reservedIds[$candidate]) || is_array($this->locateUnlocked($candidate))) {
            $candidate = $slug . '_' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function entityPrefix(): string
    {
        return str_contains($this->entity, 'posts') ? 'post' : 'page';
    }

    private function sanitizeId(string $id): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id)) ?? '';
    }

    private function sanitizeLocale(string $locale): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', trim($locale)) ?? '';
    }

    private function slugifyId(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('str_slug')) {
            $slug = str_slug($value);
        } else {
            $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
        }

        return str_replace('-', '_', $slug);
    }

    private function invalidateCache(): void
    {
        unset(self::$documentCache[$this->basePath]);
        unset(self::$documentByIdCache[$this->basePath]);
    }
}
