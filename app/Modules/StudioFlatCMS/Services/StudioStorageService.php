<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\StudioFlatCMS\Services;

use RuntimeException;

final class StudioStorageService
{
    private StudioSchemaService $schema;

    public function __construct(StudioSchemaService $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function loadDocument(string $documentId, array $settings = []): array
    {
        return $this->loadDocumentForSource(null, $settings, $documentId);
    }

    /**
     * @param array<string, mixed>|null $sourcePage
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function loadDocumentForSource(?array $sourcePage, array $settings = [], string $fallbackDocumentId = 'home'): array
    {
        $documentId = is_array($sourcePage) ? $this->documentIdForSourcePage($sourcePage) : $fallbackDocumentId;
        $path = $this->documentPath($documentId);
        if (!is_file($path)) {
            return $this->schema->defaultDocument($documentId, $settings, is_array($sourcePage) ? $sourcePage : []);
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return $this->schema->defaultDocument($documentId, $settings, is_array($sourcePage) ? $sourcePage : []);
        }

        if ($this->shouldRefreshLegacySourceDocument($decoded, $sourcePage)) {
            return $this->schema->defaultDocument($documentId, $settings, is_array($sourcePage) ? $sourcePage : []);
        }

        $document = $this->schema->normalizeDocument($decoded, $documentId, $settings, is_array($sourcePage) ? $sourcePage : []);
        return $document;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function saveDocument(string $documentId, array $payload, array $settings = []): array
    {
        return $this->saveDocumentForSource(null, $payload, $settings, $documentId);
    }

    /**
     * @param array<string, mixed>|null $sourcePage
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function saveDocumentForSource(?array $sourcePage, array $payload, array $settings = [], string $fallbackDocumentId = 'home'): array
    {
        $documentId = is_array($sourcePage) ? $this->documentIdForSourcePage($sourcePage) : $fallbackDocumentId;
        $document = $this->schema->normalizeDocument($payload, $documentId, $settings, is_array($sourcePage) ? $sourcePage : []);
        $path = $this->documentPath($documentId);
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create StudioFlatCMS data directory.');
        }

        $encoded = json_encode(
            $document,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if ($encoded === false) {
            throw new RuntimeException('Unable to encode StudioFlatCMS document.');
        }

        if (file_put_contents($path, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write StudioFlatCMS document.');
        }

        return $document;
    }

    /**
     * @param array<string, mixed> $sourcePage
     */
    public function documentIdForSourcePage(array $sourcePage): string
    {
        $sourceId = trim((string) ($sourcePage['id'] ?? ''));
        return $sourceId !== '' ? $sourceId : 'home';
    }

    /**
     * @param array<string, mixed> $document
     * @param array<string, mixed>|null $sourcePage
     */
    private function shouldRefreshLegacySourceDocument(array $document, ?array $sourcePage): bool
    {
        if (!is_array($sourcePage) || $sourcePage === []) {
            return false;
        }

        $source = is_array($document['source'] ?? null) ? $document['source'] : [];
        $documentSourceId = trim((string) ($source['entity_id'] ?? $document['id'] ?? ''));
        $requestedSourceId = trim((string) ($sourcePage['id'] ?? ''));
        if ($documentSourceId === '' || $requestedSourceId === '' || $documentSourceId !== $requestedSourceId) {
            return false;
        }

        return trim((string) ($source['import_version'] ?? '')) === '';
    }

    private function documentPath(string $documentId): string
    {
        $id = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($documentId))) ?? '';
        $id = trim($id, '-');
        if ($id === '') {
            $id = 'home';
        }

        return BASE_PATH . '/data/modules/StudioFlatCMS/pages/' . $id . '.json';
    }
}
