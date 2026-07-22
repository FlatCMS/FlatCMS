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

final class ContentDocumentStore
{
    private string $basePath;
    private string $entity;

    public function __construct(string $entity)
    {
        $this->entity = trim($entity, '/');
        $this->basePath = BASE_PATH . '/data/' . $this->entity;

        if (!is_dir($this->basePath) && !mkdir($this->basePath, 0755, true) && !is_dir($this->basePath)) {
            throw new \RuntimeException('Unable to create content document storage directory.');
        }
    }

    public static function for(string $entity): self
    {
        return new self($entity);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $items = [];
        $seen = [];

        foreach ($this->documentDirectories() as $directory) {
            $source = $this->readDocument($directory . '/index.json', $directory . '/content.html');
            if (is_array($source)) {
                $sourceId = trim((string) ($source['id'] ?? ''));
                if ($sourceId !== '') {
                    $items[] = $source;
                    $seen[$sourceId] = true;
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
        }

        return $items;
    }

    public function find(string $id): ?array
    {
        $location = $this->locate($id);
        if (!is_array($location)) {
            return null;
        }

        if (($location['type'] ?? '') === 'legacy') {
            return $this->readLegacyDocument((string) $location['index']);
        }

        return $this->readDocument((string) $location['index'], (string) $location['content']);
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
        if (trim((string) ($data['id'] ?? '')) === '') {
            $data['id'] = $this->generateDocumentId($data);
        }

        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;

        $this->saveDocument($data);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(string $id, array $data): ?array
    {
        $existing = $this->find($id);
        if (!is_array($existing)) {
            return null;
        }

        $updated = array_merge($existing, $data);
        $updated['id'] = $id;
        $updated['updated_at'] = date('Y-m-d H:i:s');

        $this->saveDocument($updated, $this->locate($id));

        return $updated;
    }

    public function delete(string $id): bool
    {
        $location = $this->locate($id);
        if (!is_array($location)) {
            return false;
        }

        if (($location['type'] ?? '') === 'legacy') {
            return is_file((string) $location['index']) && unlink((string) $location['index']);
        }

        $removed = false;
        foreach (['index', 'content'] as $key) {
            $path = (string) ($location[$key] ?? '');
            if ($path !== '' && is_file($path)) {
                $removed = unlink($path) || $removed;
            }
        }

        $directory = (string) ($location['directory'] ?? '');
        if ($directory !== '') {
            $this->removeEmptyDirectories($directory);
        }

        return $removed;
    }

    public function exists(string $id): bool
    {
        return is_array($this->locate($id));
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
     * @param array<string, mixed> $data
     * @param array<string, string>|null $existingLocation
     */
    private function saveDocument(array $data, ?array $existingLocation = null): void
    {
        $location = $this->resolveWriteLocation($data, $existingLocation);
        $directory = (string) $location['directory'];

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create content document directory.');
        }

        $content = (string) ($data['content'] ?? '');
        $metadata = $data;
        unset($metadata['content']);

        $json = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('Unable to encode content document metadata.');
        }

        if (file_put_contents((string) $location['index'], $json . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write content document metadata.');
        }
        if (file_put_contents((string) $location['content'], $content, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write content document HTML.');
        }

        if (is_array($existingLocation) && ($existingLocation['type'] ?? '') === 'legacy') {
            $legacyPath = (string) ($existingLocation['index'] ?? '');
            if ($legacyPath !== '' && is_file($legacyPath)) {
                unlink($legacyPath);
            }
        }
    }

    /**
     * @return array<string, string>|null
     */
    private function locate(string $id): ?array
    {
        $safeId = $this->sanitizeId($id);
        if ($safeId === '') {
            return null;
        }

        $sourceDirectory = $this->basePath . '/' . $safeId;
        $sourceIndex = $sourceDirectory . '/index.json';
        if (is_file($sourceIndex)) {
            return [
                'type' => 'source',
                'directory' => $sourceDirectory,
                'index' => $sourceIndex,
                'content' => $sourceDirectory . '/content.html',
            ];
        }

        foreach ($this->documentDirectories() as $directory) {
            $translationRoot = $directory . '/translations';
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
        if (is_file($legacyPath)) {
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

        $metadata['content'] = is_file($contentPath) ? (string) file_get_contents($contentPath) : '';

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
        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if (!is_string($content)) {
            return null;
        }

        $data = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($data) ? $data : null;
    }

    /**
     * @return array<int, string>
     */
    private function documentDirectories(): array
    {
        $directories = glob($this->basePath . '/*', GLOB_ONLYDIR) ?: [];
        sort($directories, SORT_NATURAL);

        return $directories;
    }

    /**
     * @return array<int, string>
     */
    private function translationDirectories(string $translationRoot): array
    {
        $directories = glob($translationRoot . '/*', GLOB_ONLYDIR) ?: [];
        sort($directories, SORT_NATURAL);

        return $directories;
    }

    /**
     * @return array<int, string>
     */
    private function legacyFiles(): array
    {
        $files = glob($this->basePath . '/*.json') ?: [];
        sort($files, SORT_NATURAL);

        return $files;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function generateDocumentId(array $data): string
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
        while ($this->exists($candidate)) {
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

    private function removeEmptyDirectories(string $directory): void
    {
        while ($directory !== $this->basePath && str_starts_with($directory, $this->basePath) && is_dir($directory)) {
            $entries = array_diff(scandir($directory) ?: [], ['.', '..']);
            if ($entries !== []) {
                break;
            }

            rmdir($directory);
            $directory = dirname($directory);
        }
    }
}
