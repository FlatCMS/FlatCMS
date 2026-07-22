<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Trash\Services;

use App\Core\ContentDocumentStore;
use App\Core\FlatFile;
use App\Modules\Media\Repositories\MediaRepository;

final class TrashService
{
    private FlatFile $trashPages;
    private FlatFile $trashPosts;
    private FlatFile $trashCategories;
    private FlatFile $trashThemes;
    private FlatFile $trashMedia;
    private ContentDocumentStore $pages;
    private ContentDocumentStore $posts;
    private FlatFile $categories;
    private MediaRepository $mediaRepository;
    private string $themeArchivesPath;
    private string $mediaUploadsPath;
    private string $mediaArchivesPath;

    public function __construct()
    {
        $this->trashPages = FlatFile::for('trash/pages');
        $this->trashPosts = FlatFile::for('trash/posts');
        $this->trashCategories = FlatFile::for('trash/categories');
        $this->trashThemes = FlatFile::for('trash/themes');
        $this->trashMedia = FlatFile::for('trash/media');
        $this->pages = ContentDocumentStore::for('core/pages');
        $this->posts = ContentDocumentStore::for('core/posts');
        $this->categories = FlatFile::for('core/categories');
        $this->mediaRepository = new MediaRepository();
        $this->themeArchivesPath = BASE_PATH . '/storage/trash/themes';
        $this->mediaUploadsPath = BASE_PATH . '/public/uploads';
        $this->mediaArchivesPath = BASE_PATH . '/storage/trash/media';
    }

    public function count(string $type = 'all'): int
    {
        return count($this->allByType($type));
    }

    public function countPages(): int
    {
        return count($this->allByType('page'));
    }

    public function countPosts(): int
    {
        return count($this->allByType('post'));
    }

    public function countCategories(): int
    {
        return count($this->allByType('category'));
    }

    public function countThemes(): int
    {
        return count($this->allByType('theme'));
    }

    public function countMedia(): int
    {
        return count($this->allByType('media'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function paginate(string $type = 'all', int $page = 1, int $perPage = 20): array
    {
        $all = $this->allByType($type);
        $total = count($all);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'data' => array_slice($all, $offset, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
            'type' => $this->normalizeType($type),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $type = 'all'): array
    {
        return $this->allByType($type);
    }

    public function findItem(string $trashId): ?array
    {
        return $this->findPage($trashId)
            ?? $this->findPost($trashId)
            ?? $this->findCategory($trashId)
            ?? $this->findTheme($trashId)
            ?? $this->findMedia($trashId);
    }

    public function findPage(string $trashId): ?array
    {
        return $this->findEntity($this->trashPages, 'page', $trashId);
    }

    public function findPost(string $trashId): ?array
    {
        return $this->findEntity($this->trashPosts, 'post', $trashId);
    }

    public function findCategory(string $trashId): ?array
    {
        return $this->findEntity($this->trashCategories, 'category', $trashId);
    }

    public function findTheme(string $trashId): ?array
    {
        return $this->findEntity($this->trashThemes, 'theme', $trashId);
    }

    public function findMedia(string $trashId): ?array
    {
        return $this->findEntity($this->trashMedia, 'media', $trashId);
    }

    public function archivePage(array $page, string $deletedBy = ''): ?array
    {
        return $this->archiveEntity('page', $this->trashPages, $page, $deletedBy);
    }

    public function archivePost(array $post, string $deletedBy = ''): ?array
    {
        return $this->archiveEntity('post', $this->trashPosts, $post, $deletedBy);
    }

    public function archiveCategory(array $category, string $deletedBy = ''): ?array
    {
        return $this->archiveEntity('category', $this->trashCategories, $category, $deletedBy);
    }

    public function archiveTheme(array $theme, string $deletedBy = ''): ?array
    {
        $themeType = trim((string) ($theme['theme_type'] ?? ''));
        $themeName = trim((string) ($theme['theme_name'] ?? ''));
        $rootPath = trim((string) ($theme['root_path'] ?? ''));
        $publicPath = trim((string) ($theme['public_path'] ?? ''));
        $customizationPath = trim((string) ($theme['customization_path'] ?? ''));

        if (!in_array($themeType, ['admin', 'frontend'], true) || $themeName === '' || $rootPath === '' || !is_dir($rootPath)) {
            return null;
        }

        $entityId = $themeType . ':' . $themeName;
        $existing = $this->findEntityByEntityId($this->trashThemes, 'theme', $entityId);
        if ($existing !== null) {
            return $existing;
        }

        $archiveSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $themeType . '_' . $themeName . '_' . uniqid('', true)) ?? '';
        if ($archiveSlug === '') {
            return null;
        }

        $archiveRoot = $this->themeArchivesPath . '/' . $archiveSlug;
        $archiveThemePath = $archiveRoot . '/theme';
        $archiveCustomizationPath = $archiveRoot . '/customization.json';

        $movedPaths = [];

        if (!$this->movePath($rootPath, $archiveThemePath)) {
            return null;
        }
        $movedPaths[] = ['from' => $archiveThemePath, 'to' => $rootPath];

        if ($customizationPath !== '' && is_file($customizationPath)) {
            if (!$this->movePath($customizationPath, $archiveCustomizationPath)) {
                $this->rollbackMovedPaths($movedPaths);
                $this->removePath($archiveRoot);
                return null;
            }
            $movedPaths[] = ['from' => $archiveCustomizationPath, 'to' => $customizationPath];
        }

        if ($publicPath !== '' && file_exists($publicPath) && !$this->removePath($publicPath)) {
            $this->rollbackMovedPaths($movedPaths);
            $this->removePath($archiveRoot);
            return null;
        }

        $record = [
            'entity_type' => 'theme',
            'entity_id' => $entityId,
            'entity_title' => (string) ($theme['name'] ?? $themeName),
            'entity_slug' => $themeType . '/' . $themeName,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deletedBy,
            'payload' => [
                'theme_type' => $themeType,
                'theme_name' => $themeName,
                'name' => (string) ($theme['name'] ?? $themeName),
                'description' => (string) ($theme['description'] ?? ''),
                'version' => (string) ($theme['version'] ?? ''),
                'author' => (string) ($theme['author'] ?? ''),
                'archive_root' => 'storage/trash/themes/' . $archiveSlug,
                'has_customization' => is_file($archiveCustomizationPath),
            ],
        ];

        $created = $this->trashThemes->create($record);
        if (!is_array($created)) {
            $this->rollbackMovedPaths($movedPaths);
            $this->removePath($archiveRoot);
            return null;
        }

        return $created;
    }

    public function archiveMedia(array $media, string $deletedBy = ''): ?array
    {
        $mediaPath = trim((string) ($media['path'] ?? ''));
        $folder = trim((string) ($media['folder'] ?? ''));
        $filename = trim((string) ($media['name'] ?? basename($mediaPath)));
        $sourcePath = $this->resolveMediaUploadPath($mediaPath);

        if ($mediaPath === '' || $folder === '' || $filename === '' || $sourcePath === '' || !is_file($sourcePath)) {
            return null;
        }

        $entityId = 'media:' . $mediaPath;
        $existing = $this->findEntityByEntityId($this->trashMedia, 'media', $entityId);
        if ($existing !== null) {
            return null;
        }

        $archiveSlug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $folder . '_' . pathinfo($filename, PATHINFO_FILENAME) . '_' . uniqid('', true)) ?? '';
        if ($archiveSlug === '') {
            return null;
        }

        $archiveRoot = $this->mediaArchivesPath . '/' . $archiveSlug;
        $archiveFilePath = $archiveRoot . '/' . $filename;
        if (!$this->movePath($sourcePath, $archiveFilePath)) {
            return null;
        }

        $record = [
            'entity_type' => 'media',
            'entity_id' => $entityId,
            'entity_title' => (string) ($media['original_name'] ?? $filename),
            'entity_slug' => $mediaPath,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deletedBy,
            'payload' => [
                'path' => $mediaPath,
                'folder' => $folder,
                'name' => $filename,
                'original_name' => (string) ($media['original_name'] ?? $filename),
                'type' => (string) ($media['type'] ?? ''),
                'mime' => (string) ($media['mime'] ?? ''),
                'extension' => (string) ($media['extension'] ?? ''),
                'size' => (int) ($media['size'] ?? 0),
                'dimensions' => is_array($media['dimensions'] ?? null) ? $media['dimensions'] : null,
                'uploaded_by' => $media['uploaded_by'] ?? 1,
                'archive_root' => 'storage/trash/media/' . $archiveSlug,
                'archive_file' => 'storage/trash/media/' . $archiveSlug . '/' . $filename,
                'had_repository_entry' => (int) ($media['id'] ?? 0) > 0,
            ],
        ];

        $created = $this->trashMedia->create($record);
        if (!is_array($created)) {
            $this->movePath($archiveFilePath, $sourcePath);
            $this->removePath($archiveRoot);
            return null;
        }

        if ((int) ($media['id'] ?? 0) > 0 && !$this->mediaRepository->deleteByPath($mediaPath)) {
            $this->trashMedia->delete((string) ($created['id'] ?? ''));
            $this->movePath($archiveFilePath, $sourcePath);
            $this->removePath($archiveRoot);
            return null;
        }

        return $created;
    }

    public function restoreItem(string $trashId): array
    {
        $item = $this->findItem($trashId);
        if ($item === null) {
            return ['success' => false, 'code' => 'not_found'];
        }

        $type = (string) ($item['entity_type'] ?? '');
        return match ($type) {
            'page' => $this->restoreEntity($item, $this->pages, $this->trashPages),
            'post' => $this->restoreEntity($item, $this->posts, $this->trashPosts),
            'category' => $this->restoreEntity($item, $this->categories, $this->trashCategories),
            'theme' => $this->restoreTheme($item),
            'media' => $this->restoreMedia($item),
            default => ['success' => false, 'code' => 'invalid_type'],
        };
    }

    public function delete(string $trashId): bool
    {
        if ($this->findPage($trashId) !== null) {
            return $this->trashPages->delete($trashId);
        }

        if ($this->findPost($trashId) !== null) {
            return $this->trashPosts->delete($trashId);
        }

        if ($this->findCategory($trashId) !== null) {
            return $this->trashCategories->delete($trashId);
        }

        $themeItem = $this->findTheme($trashId);
        if ($themeItem !== null) {
            $payload = is_array($themeItem['payload'] ?? null) ? $themeItem['payload'] : [];
            $archiveRoot = $this->resolveArchivePath((string) ($payload['archive_root'] ?? ''));
            if ($archiveRoot !== '' && file_exists($archiveRoot) && !$this->removePath($archiveRoot)) {
                return false;
            }

            return $this->trashThemes->delete($trashId);
        }

        $mediaItem = $this->findMedia($trashId);
        if ($mediaItem !== null) {
            $payload = is_array($mediaItem['payload'] ?? null) ? $mediaItem['payload'] : [];
            $archiveRoot = $this->resolveArchivePath((string) ($payload['archive_root'] ?? ''));
            if ($archiveRoot !== '' && file_exists($archiveRoot) && !$this->removePath($archiveRoot)) {
                return false;
            }

            return $this->trashMedia->delete($trashId);
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allByType(string $type): array
    {
        $type = $this->normalizeType($type);

        $items = match ($type) {
            'page' => $this->collectEntityItems($this->trashPages, 'page'),
            'post' => $this->collectEntityItems($this->trashPosts, 'post'),
            'category' => $this->collectEntityItems($this->trashCategories, 'category'),
            'theme' => $this->collectEntityItems($this->trashThemes, 'theme'),
            'media' => $this->collectEntityItems($this->trashMedia, 'media'),
            default => array_merge(
                $this->collectEntityItems($this->trashPages, 'page'),
                $this->collectEntityItems($this->trashPosts, 'post'),
                $this->collectEntityItems($this->trashCategories, 'category'),
                $this->collectEntityItems($this->trashThemes, 'theme'),
                $this->collectEntityItems($this->trashMedia, 'media')
            ),
        };

        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($b['deleted_at'] ?? ''), (string) ($a['deleted_at'] ?? ''));
        });

        return $items;
    }

    private function findEntity(FlatFile $trashStore, string $type, string $trashId): ?array
    {
        $item = $trashStore->find($trashId);
        if (!is_array($item) || (string) ($item['entity_type'] ?? '') !== $type) {
            return null;
        }

        return $item;
    }

    /**
     * @param array<string, mixed> $entity
     */
    private function archiveEntity(string $type, FlatFile $trashStore, array $entity, string $deletedBy = ''): ?array
    {
        $entityId = trim((string) ($entity['id'] ?? ''));
        if ($entityId === '') {
            return null;
        }

        $existing = $this->findEntityByEntityId($trashStore, $type, $entityId);
        if ($existing !== null) {
            return $existing;
        }

        $record = [
            'entity_type' => $type,
            'entity_id' => $entityId,
            'entity_title' => (string) ($entity['title'] ?? $entity['name'] ?? $entityId),
            'entity_slug' => (string) ($entity['slug'] ?? ''),
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deletedBy,
            'payload' => $entity,
        ];

        $created = $trashStore->create($record);

        return is_array($created) ? $created : null;
    }

    private function findEntityByEntityId(FlatFile $trashStore, string $type, string $entityId): ?array
    {
        foreach ($this->collectEntityItems($trashStore, $type) as $item) {
            if ((string) ($item['entity_id'] ?? '') === $entityId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectEntityItems(FlatFile $trashStore, string $type): array
    {
        return array_values(array_filter($trashStore->all(), static function ($item) use ($type): bool {
            return is_array($item) && (string) ($item['entity_type'] ?? '') === $type;
        }));
    }

    private function restoreEntity(array $item, ContentDocumentStore|FlatFile $liveStore, FlatFile $trashStore): array
    {
        $payload = $item['payload'] ?? null;
        if (!is_array($payload)) {
            return ['success' => false, 'code' => 'invalid_payload'];
        }

        $entityId = trim((string) ($payload['id'] ?? ''));
        if ($entityId === '') {
            return ['success' => false, 'code' => 'invalid_payload'];
        }

        if ($liveStore->exists($entityId)) {
            return ['success' => false, 'code' => 'id_conflict'];
        }

        $payload = $this->ensureUniqueSlug($payload, $entityId, $liveStore);
        $restored = $liveStore->create($payload);
        if (!is_array($restored)) {
            return ['success' => false, 'code' => 'write_failed'];
        }

        $trashStore->delete((string) ($item['id'] ?? ''));

        return [
            'success' => true,
            'item' => $restored,
            'entity_type' => (string) ($item['entity_type'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function ensureUniqueSlug(array $payload, string $entityId, ContentDocumentStore|FlatFile $liveStore): array
    {
        $slug = trim((string) ($payload['slug'] ?? ''));
        if ($slug === '') {
            return $payload;
        }

        $existing = $liveStore->findBy('slug', $slug);
        if (!is_array($existing) || (string) ($existing['id'] ?? '') === $entityId) {
            return $payload;
        }

        $payload['slug'] = $slug . '-restored-' . date('His');

        return $payload;
    }

    private function normalizeType(string $type): string
    {
        return match (trim($type)) {
            'page', 'post', 'category', 'theme', 'media' => trim($type),
            default => 'all',
        };
    }

    private function restoreTheme(array $item): array
    {
        $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];
        $themeType = trim((string) ($payload['theme_type'] ?? ''));
        $themeName = trim((string) ($payload['theme_name'] ?? ''));
        $archiveRoot = $this->resolveArchivePath((string) ($payload['archive_root'] ?? ''));

        if (!in_array($themeType, ['admin', 'frontend'], true) || $themeName === '' || $archiveRoot === '' || !is_dir($archiveRoot)) {
            return ['success' => false, 'code' => 'not_found'];
        }

        $archiveThemePath = $archiveRoot . '/theme';
        if (!is_dir($archiveThemePath)) {
            return ['success' => false, 'code' => 'not_found'];
        }

        $targetThemePath = BASE_PATH . '/themes/' . $themeType . '/' . $themeName;
        $customizationPath = BASE_PATH . '/data/themes/' . $themeType . '_' . $themeName . '.json';
        $archiveCustomizationPath = $archiveRoot . '/customization.json';
        $publicPath = BASE_PATH . '/public/themes/' . $themeType . '/' . $themeName;

        if (file_exists($targetThemePath) || file_exists($customizationPath)) {
            return ['success' => false, 'code' => 'id_conflict'];
        }

        if (file_exists($publicPath) && !$this->removePath($publicPath)) {
            return ['success' => false, 'code' => 'restore_failed'];
        }

        $movedBack = [];

        if (!$this->movePath($archiveThemePath, $targetThemePath)) {
            return ['success' => false, 'code' => 'restore_failed'];
        }
        $movedBack[] = ['from' => $targetThemePath, 'to' => $archiveThemePath];

        if (is_file($archiveCustomizationPath)) {
            if (!$this->movePath($archiveCustomizationPath, $customizationPath)) {
                $this->rollbackMovedPaths($movedBack);
                return ['success' => false, 'code' => 'restore_failed'];
            }
            $movedBack[] = ['from' => $customizationPath, 'to' => $archiveCustomizationPath];
        }

        $this->trashThemes->delete((string) ($item['id'] ?? ''));
        $this->removePath($archiveRoot);

        return [
            'success' => true,
            'item' => [
                'theme_type' => $themeType,
                'theme_name' => $themeName,
            ],
            'entity_type' => 'theme',
        ];
    }

    private function restoreMedia(array $item): array
    {
        $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];
        $mediaPath = trim((string) ($payload['path'] ?? ''));
        $archiveRoot = $this->resolveArchivePath((string) ($payload['archive_root'] ?? ''));
        $archiveFile = $this->resolveArchivePath((string) ($payload['archive_file'] ?? ''));
        $targetPath = $this->resolveMediaUploadPath($mediaPath);

        if ($mediaPath === '' || $archiveRoot === '' || $archiveFile === '' || $targetPath === '' || !is_file($archiveFile)) {
            return ['success' => false, 'code' => 'not_found'];
        }

        if (file_exists($targetPath) || $this->mediaRepository->findByPath($mediaPath) !== null) {
            return ['success' => false, 'code' => 'id_conflict'];
        }

        if (!$this->movePath($archiveFile, $targetPath)) {
            return ['success' => false, 'code' => 'restore_failed'];
        }

        if (!empty($payload['had_repository_entry'])) {
            $this->mediaRepository->create([
                'name' => (string) ($payload['name'] ?? basename($mediaPath)),
                'original_name' => (string) ($payload['original_name'] ?? basename($mediaPath)),
                'path' => $mediaPath,
                'url' => '/uploads/' . ltrim($mediaPath, '/'),
                'folder' => (string) ($payload['folder'] ?? ''),
                'type' => (string) ($payload['type'] ?? ''),
                'mime' => (string) ($payload['mime'] ?? ''),
                'extension' => (string) ($payload['extension'] ?? ''),
                'size' => (int) ($payload['size'] ?? 0),
                'dimensions' => is_array($payload['dimensions'] ?? null) ? $payload['dimensions'] : null,
                'uploaded_by' => $payload['uploaded_by'] ?? 1,
            ]);
        }

        $this->trashMedia->delete((string) ($item['id'] ?? ''));
        $this->removePath($archiveRoot);

        return [
            'success' => true,
            'item' => ['path' => $mediaPath],
            'entity_type' => 'media',
        ];
    }

    private function resolveArchivePath(string $relativePath): string
    {
        $clean = trim($relativePath);
        if ($clean === '') {
            return '';
        }

        if (str_starts_with($clean, BASE_PATH . '/')) {
            return $clean;
        }

        return BASE_PATH . '/' . ltrim($clean, '/');
    }

    private function resolveMediaUploadPath(string $relativePath): string
    {
        $clean = trim(str_replace('\\', '/', $relativePath), '/');
        if ($clean === '') {
            return '';
        }

        return $this->mediaUploadsPath . '/' . $clean;
    }

    private function rollbackMovedPaths(array $moves): void
    {
        for ($i = count($moves) - 1; $i >= 0; $i--) {
            $move = $moves[$i];
            $from = (string) ($move['from'] ?? '');
            $to = (string) ($move['to'] ?? '');
            if ($from === '' || $to === '' || !file_exists($from)) {
                continue;
            }
            $this->movePath($from, $to);
        }
    }

    private function movePath(string $source, string $destination): bool
    {
        if ($source === '' || $destination === '' || !file_exists($source)) {
            return false;
        }

        $parent = dirname($destination);
        if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
            return false;
        }

        if (@rename($source, $destination)) {
            return true;
        }

        if (is_dir($source)) {
            if (!$this->copyDirectory($source, $destination)) {
                return false;
            }

            return $this->removePath($source);
        }

        if (!@copy($source, $destination)) {
            return false;
        }

        return @unlink($source);
    }

    private function copyDirectory(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination . '/' . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                    return false;
                }
                continue;
            }

            if (!@copy($item->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }

    private function removePath(string $path): bool
    {
        if ($path === '' || !file_exists($path)) {
            return true;
        }

        if (is_file($path) || is_link($path)) {
            return @unlink($path);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $pathname = $item->getPathname();
            if ($item->isDir()) {
                if (!@rmdir($pathname)) {
                    return false;
                }
                continue;
            }

            if (!@unlink($pathname)) {
                return false;
            }
        }

        return @rmdir($path);
    }
}
