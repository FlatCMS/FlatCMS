<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Media\Models;

use App\Modules\Media\Repositories\MediaRepository;

class MediaModel
{
    private MediaRepository $repository;
    private string $uploadPath;

    /**
     * Configuration des dossiers et extensions autorisées
     */
    public const FOLDERS = [
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'ico', 'bmp'],
        'videos' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'],
        'sounds' => ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a'],
        'documents' => ['doc', 'docx', 'txt', 'rtf', 'odt'],
        'pdf' => ['pdf'],
        'spreadsheets' => ['xls', 'xlsx', 'csv', 'ods'],
        'archives' => ['zip', 'rar', '7z', 'tar', 'gz']
    ];

    /**
     * Configuration des icônes et couleurs par dossier
     */
    public const FOLDER_CONFIG = [
        'images' => [
            'icon' => 'fa-image',
            'color' => 'blue',
            'accept' => 'image/*'
        ],
        'videos' => [
            'icon' => 'fa-video',
            'color' => 'red',
            'accept' => 'video/*'
        ],
        'sounds' => [
            'icon' => 'fa-music',
            'color' => 'green',
            'accept' => 'audio/*'
        ],
        'documents' => [
            'icon' => 'fa-file-alt',
            'color' => 'gray',
            'accept' => '.doc,.docx,.txt,.rtf,.odt'
        ],
        'pdf' => [
            'icon' => 'fa-file-pdf',
            'color' => 'orange',
            'accept' => '.pdf'
        ],
        'spreadsheets' => [
            'icon' => 'fa-file-excel',
            'color' => 'teal',
            'accept' => '.xls,.xlsx,.csv,.ods'
        ],
        'archives' => [
            'icon' => 'fa-file-archive',
            'color' => 'yellow',
            'accept' => '.zip,.rar,.7z,.tar,.gz'
        ]
    ];

    /**
     * Taille maximale par défaut (500 Mo)
     */
    public const MAX_FILE_SIZE = 500 * 1024 * 1024;

    public function __construct()
    {
        $this->repository = new MediaRepository();
        $this->uploadPath = $this->resolveUploadPath();
        $this->ensureDirectories();
    }

    private function resolveUploadPath(): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
        $basePath = str_replace('\\', '/', $basePath);

        $candidate = $basePath . '/public/uploads';
        if (is_dir($candidate)) {
            return $candidate;
        }

        $trimmed = $basePath;
        if (str_ends_with($trimmed, '/public')) {
            $trimmed = substr($trimmed, 0, -7);
        }
        $fallback = $trimmed . '/public/uploads';
        if (is_dir($fallback)) {
            return $fallback;
        }

        return $candidate;
    }

    private function normalizeMediaUrl(string $rawUrl, string $folder = '', string $filename = ''): string
    {
        $normalizedPath = flatcms_normalize_upload_media_path($rawUrl);
        if ($normalizedPath === '' && $folder !== '' && $filename !== '') {
            $normalizedPath = '/uploads/' . trim($folder, '/') . '/' . ltrim($filename, '/');
        }

        if ($normalizedPath === '') {
            return $rawUrl;
        }

        $base = rtrim(static_base_url(), '/');
        return $base === '' ? $normalizedPath : ($base . $normalizedPath);
    }

    private function normalizeMediaRecord(array $record, bool $persist = false): array
    {
        $folder = (string) ($record['folder'] ?? '');
        $name = (string) ($record['name'] ?? '');
        $currentUrl = (string) ($record['url'] ?? '');
        $normalizedUrl = $this->normalizeMediaUrl($currentUrl, $folder, $name);

        if ($normalizedUrl === $currentUrl || $normalizedUrl === '') {
            return $record;
        }

        $record['url'] = $normalizedUrl;

        $id = (int) ($record['id'] ?? 0);
        if ($persist && $id > 0) {
            $this->repository->update($id, ['url' => $normalizedUrl]);
        }

        return $record;
    }

    /**
     * S'assure que tous les dossiers d'upload existent
     */
    private function ensureDirectories(): void
    {
        foreach (array_keys(self::FOLDERS) as $folder) {
            $path = $this->uploadPath . '/' . $folder;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    /**
     * Retourne tous les médias
     */
    public function all(): array
    {
        return $this->repository->all();
    }

    /**
     * Retourne tous les medias scannes sur tous les dossiers.
     *
     * @return array<int, array<string, mixed>>
     */
    public function scanAllFolders(): array
    {
        $items = [];

        foreach (array_keys(self::FOLDERS) as $folder) {
            foreach ($this->listDirectories($folder) as $directory) {
                $context = (string) ($directory['path'] ?? '');
                foreach ($this->scanFolder($folder, $context) as $item) {
                    $path = trim((string) ($item['path'] ?? ''));
                    if ($path === '') {
                        continue;
                    }

                    $items[$path] = $item;
                }
            }
        }

        return array_values($items);
    }

    /**
     * Trouve un média par ID
     */
    public function find(int $id): ?array
    {
        return $this->repository->find($id);
    }

    /**
     * Trouve un média par son chemin, y compris s'il existe uniquement sur le disque.
     */
    public function findByPath(string $path): ?array
    {
        $normalizedPath = trim(str_replace('\\', '/', $path), '/');
        if ($normalizedPath === '') {
            return null;
        }

        $existing = $this->repository->findByPath($normalizedPath);
        if (is_array($existing)) {
            return $this->normalizeMediaRecord($existing, false);
        }

        $segments = explode('/', $normalizedPath, 2);
        $folder = trim((string) ($segments[0] ?? ''));
        $filename = trim((string) ($segments[1] ?? ''));
        if ($folder === '' || $filename === '' || !isset(self::FOLDERS[$folder])) {
            return null;
        }

        $filePath = $this->uploadPath . '/' . $normalizedPath;
        if (!is_file($filePath)) {
            return null;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = (string) (mime_content_type($filePath) ?: 'application/octet-stream');
        $size = (int) (filesize($filePath) ?: 0);

        $dimensions = null;
        if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml') {
            $imgInfo = @getimagesize($filePath);
            if ($imgInfo) {
                $dimensions = ['width' => $imgInfo[0], 'height' => $imgInfo[1]];
            }
        }

        return [
            'id' => 0,
            'name' => $filename,
            'original_name' => $filename,
            'path' => $normalizedPath,
            'url' => $this->normalizeMediaUrl('/uploads/' . $normalizedPath, $folder, $filename),
            'folder' => $folder,
            'type' => $this->getTypeByExtension($extension),
            'mime' => $mime,
            'extension' => $extension,
            'size' => $size,
            'dimensions' => $dimensions,
            'uploaded_by' => 1,
            'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
        ];
    }

    /**
     * Retourne les médias d'un dossier
     */
    public function getByFolder(string $folder): array
    {
        if (!isset(self::FOLDERS[$folder])) {
            return [];
        }
        $items = $this->repository->findByFolder($folder);
        return array_map(fn(array $item): array => $this->normalizeMediaRecord($item, false), $items);
    }

    /**
     * Upload un fichier
     */
    public function upload(array $file, string $folder = 'images', int|string $uploadedBy = 1, string $context = ''): array
    {
        $context = $this->sanitizeSubdirectory($context);

        // Validation du dossier
        if (!isset(self::FOLDERS[$folder])) {
            return ['success' => false, 'error' => 'invalid_folder'];
        }

        // Validation de l'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->getUploadError($file['error'])];
        }

        // Validation de la taille
        if ($file['size'] > self::MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'file_too_large'];
        }

        // Extraction et validation de l'extension
        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        if (!in_array($extension, self::FOLDERS[$folder])) {
            return ['success' => false, 'error' => 'invalid_extension'];
        }

        // Validation MIME type (finfo preferred, fallback to mime_content_type)
        $mimeType = '';
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = (string) @finfo_file($finfo, $file['tmp_name']);
                @finfo_close($finfo);
                $mimeType = $detected;
            }
        }

        if ($mimeType === '' && function_exists('mime_content_type')) {
            $mimeType = (string) @mime_content_type($file['tmp_name']);
        }

        $mimeType = strtolower(trim($mimeType));

        if (
            !$this->isValidMimeType($mimeType, $folder)
            && !$this->isFallbackMimeAllowed($mimeType, $folder, $extension)
        ) {
            return ['success' => false, 'error' => 'invalid_mime_type'];
        }

        // Génération du nom de fichier unique
        $filename = $this->generateUniqueFilename($originalName, $folder, $context);
        $targetDir = rtrim($this->uploadPath . '/' . $folder . '/' . $context, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $targetPath = $targetDir . '/' . $filename;
        $relativePath = $folder . '/' . ($context !== '' ? $context . '/' : '') . $filename;

        // Déplacement du fichier
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'move_failed'];
        }

        // Récupération des dimensions pour les images
        $dimensions = null;
        if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
            $imgInfo = @getimagesize($targetPath);
            if ($imgInfo) {
                $dimensions = ['width' => $imgInfo[0], 'height' => $imgInfo[1]];
            }
        }

        // Enregistrement dans le repository
        $media = $this->repository->create([
            'name' => $filename,
            'original_name' => $originalName,
            'path' => $relativePath,
            'url' => $this->normalizeMediaUrl('/uploads/' . $relativePath, $folder, $filename),
            'folder' => $folder,
            'type' => $this->getTypeByExtension($extension),
            'mime' => $mimeType,
            'extension' => $extension,
            'size' => $file['size'],
            'dimensions' => $dimensions,
            'uploaded_by' => $uploadedBy,
            'ai_index_status' => 'not_indexed',
            'ai_indexed_at' => null,
            'ai_source_hash' => null,
            'ai_last_error' => null,
            'ai_metadata' => [],
        ]);

        return ['success' => true, 'media' => $media];
    }

    public function contextualize(string $path, string $folder = 'images', int|string $uploadedBy = 1, string $context = ''): array
    {
        if (!isset(self::FOLDERS[$folder])) {
            return ['success' => false, 'error' => 'invalid_folder'];
        }

        $context = $this->sanitizeSubdirectory($context);
        $normalizedPath = trim(str_replace('\\', '/', $path), '/');
        $normalizedPath = preg_replace('#^(?:public/)?uploads/#', '', $normalizedPath) ?? $normalizedPath;
        if ($normalizedPath === '') {
            return ['success' => false, 'error' => 'media_not_found'];
        }

        $source = $this->findByPath($normalizedPath);
        if (!is_array($source)) {
            return ['success' => false, 'error' => 'media_not_found'];
        }

        if ($context === '') {
            return [
                'success' => true,
                'media' => $this->ensurePersisted($source) ?? $source,
                'created' => false,
            ];
        }

        $sourcePath = trim((string) ($source['path'] ?? $normalizedPath), '/');
        $targetPrefix = $folder . '/' . $context . '/';
        if (str_starts_with($sourcePath, $targetPrefix)) {
            return [
                'success' => true,
                'media' => $this->ensurePersisted($source) ?? $source,
                'created' => false,
            ];
        }

        $sourcePath = preg_replace('#^(?:public/)?uploads/#', '', $sourcePath) ?? $sourcePath;
        $sourceAbsolute = $this->getAbsolutePath($sourcePath);
        if ($sourceAbsolute === null) {
            return ['success' => false, 'error' => 'media_not_found'];
        }

        $originalName = trim((string) ($source['original_name'] ?? $source['name'] ?? ''));
        if ($originalName === '') {
            $originalName = basename($sourcePath);
        }

        $filename = $this->sanitizeFilename($originalName);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($extension, self::FOLDERS[$folder], true)) {
            return ['success' => false, 'error' => 'invalid_extension'];
        }

        $targetDir = rtrim($this->uploadPath . '/' . $folder . '/' . $context, '/');
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            return ['success' => false, 'error' => 'move_failed'];
        }

        $targetPath = $targetDir . '/' . $filename;
        $relativePath = $targetPrefix . $filename;
        if (is_file($targetPath)) {
            $existing = $this->findByPath($relativePath);
            if (is_array($existing)) {
                return ['success' => true, 'media' => $existing, 'created' => false];
            }

            $record = $this->buildFileRecord($targetPath, $relativePath, $folder, $filename, $uploadedBy, $source);
            $media = $this->ensurePersisted($record);

            return [
                'success' => is_array($media),
                'media' => $media,
                'created' => is_array($media),
                'error' => is_array($media) ? null : 'move_failed',
            ];
        }

        if (!copy($sourceAbsolute, $targetPath)) {
            return ['success' => false, 'error' => 'move_failed'];
        }

        $media = $this->repository->create(
            $this->buildFileRecord($targetPath, $relativePath, $folder, $filename, $uploadedBy, $source)
        );

        return ['success' => true, 'media' => $media, 'created' => true];
    }

    /**
     * Supprime un média
     */
    public function delete(int $id): bool
    {
        $media = $this->repository->find($id);
        if (!$media) {
            return false;
        }

        // Suppression du fichier physique
        $filePath = $this->uploadPath . '/' . ($media['path'] ?? '');
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Suppression de l'entrée JSON
        return $this->repository->delete($id);
    }

    /**
     * Supprime par chemin
     */
    public function deleteByPath(string $path): bool
    {
        $media = $this->repository->findByPath($path);
        if (!$media) {
            return false;
        }

        $filePath = $this->uploadPath . '/' . $path;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return $this->repository->deleteByPath($path);
    }

    public function deleteDirectory(string $path): bool
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            return false;
        }

        $segments = explode('/', $path);
        $folder = array_shift($segments) ?? '';
        if ($folder === '' || !isset(self::FOLDERS[$folder])) {
            return false;
        }

        $context = $this->sanitizeSubdirectory(implode('/', $segments));
        if ($context === '') {
            return false;
        }

        $relativePath = $folder . '/' . $context;
        $dir = $this->resolveSafeUploadDirectory($relativePath);
        if ($dir === null) {
            return false;
        }

        if (!$this->removeDirectoryRecursive($dir)) {
            return false;
        }

        foreach ($this->repository->all() as $media) {
            $mediaPath = trim((string) ($media['path'] ?? ''), '/');
            if ($mediaPath === $relativePath || str_starts_with($mediaPath, $relativePath . '/')) {
                $this->repository->delete((int) ($media['id'] ?? 0));
            }
        }

        return true;
    }

    /**
     * Déplace un fichier ou dossier vers un autre sous-dossier.
     *
     * @param string $folder     Famille (images, documents, etc.)
     * @param string $context    Contexte actuel du fichier/dossier source
     * @param string $itemName   Nom du fichier ou dossier à déplacer
     * @param string $targetContext  Contexte de destination
     * @param string $type       'file' ou 'directory'
     * @return array<string, mixed>
     */
    public function move(string $folder, string $context, string $itemName, string $targetContext, string $type): array
    {
        $folder = basename($folder);
        if ($folder === '' || $folder === '.' || $folder === '..' || !isset(self::FOLDERS[$folder])) {
            return ['success' => false, 'error' => 'invalid_folder'];
        }

        $context = $this->sanitizeSubdirectory($context);
        $targetContext = $this->sanitizeSubdirectory($targetContext);
        $itemName = basename($itemName);
        if ($itemName === '' || $itemName === '.' || $itemName === '..') {
            return ['success' => false, 'error' => 'invalid_name'];
        }

        if (!in_array($type, ['file', 'directory'], true)) {
            return ['success' => false, 'error' => 'invalid_type'];
        }

        $sourcePath = rtrim($this->uploadPath . '/' . $folder . '/' . ($context !== '' ? $context . '/' : ''), '/') . '/' . $itemName;
        $destPath = rtrim($this->uploadPath . '/' . $folder . '/' . ($targetContext !== '' ? $targetContext . '/' : ''), '/') . '/' . $itemName;

        if (!file_exists($sourcePath)) {
            return ['success' => false, 'error' => 'source_not_found'];
        }

        if (file_exists($destPath)) {
            return ['success' => false, 'error' => 'target_exists'];
        }

        if ($type === 'file') {
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                if (!mkdir($destDir, 0755, true)) {
                    return ['success' => false, 'error' => 'mkdir_failed'];
                }
            }

            if (!rename($sourcePath, $destPath)) {
                return ['success' => false, 'error' => 'rename_failed'];
            }

            $sourceRelative = $folder . '/' . ($context !== '' ? $context . '/' : '') . $itemName;
            $relativePath = $folder . '/' . ($targetContext !== '' ? $targetContext . '/' : '') . $itemName;
            $existing = $this->repository->findByPath($sourceRelative);
            if ($existing) {
                $url = $this->normalizeMediaUrl('/uploads/' . $relativePath, $folder, $itemName);
                $this->repository->update((int) ($existing['id'] ?? 0), [
                    'path' => $relativePath,
                    'url' => $url,
                ]);
            }

            return ['success' => true, 'type' => 'file'];
        }

        // Directory move
        $sourceContext = trim(($context !== '' ? $context . '/' : '') . $itemName, '/');
        if ($targetContext === $sourceContext || str_starts_with($targetContext . '/', $sourceContext . '/')) {
            return ['success' => false, 'error' => 'invalid_name'];
        }

        if (!is_dir($sourcePath)) {
            return ['success' => false, 'error' => 'source_not_directory'];
        }

        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            if (!mkdir($destDir, 0755, true)) {
                return ['success' => false, 'error' => 'mkdir_failed'];
            }
        }

        if (!rename($sourcePath, $destPath)) {
            return ['success' => false, 'error' => 'rename_failed'];
        }

        $oldPrefix = $folder . '/' . ($context !== '' ? $context . '/' : '') . $itemName;
        $newPrefix = $folder . '/' . ($targetContext !== '' ? $targetContext . '/' : '') . $itemName;

        foreach ($this->repository->all() as $media) {
            $path = (string) ($media['path'] ?? '');
            if (str_starts_with($path, $oldPrefix . '/') || $path === $oldPrefix) {
                $newPath = str_replace($oldPrefix, $newPrefix, $path);
                $url = $this->normalizeMediaUrl('/uploads/' . $newPath, $folder, basename($newPath));
                $this->repository->update((int) ($media['id'] ?? 0), [
                    'path' => $newPath,
                    'url' => $url,
                ]);
            }
        }

        return ['success' => true, 'type' => 'directory'];
    }

    /**
     * Scanne un dossier et retourne les fichiers (physiques + JSON)
     */
    public function scanFolder(string $folder, string $context = ''): array
    {
        // Sécurité : pas de traversal
        $folder = basename($folder);
        if ($folder === '' || $folder === '.' || $folder === '..') {
            return [];
        }

        $context = $this->sanitizeSubdirectory($context);
        $folderPath = rtrim($this->uploadPath . '/' . $folder . '/' . $context, '/');
        if (!is_dir($folderPath)) {
            return [];
        }

        $files = [];
        $isConfigured = isset(self::FOLDERS[$folder]);
        $allowedExtensions = $isConfigured ? self::FOLDERS[$folder] : null;

        foreach (scandir($folderPath) as $filename) {
            if ($filename === '.' || $filename === '..' || $filename === '.gitkeep') {
                continue;
            }

            $filePath = $folderPath . '/' . $filename;
            if (!is_file($filePath)) {
                continue;
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($allowedExtensions !== null && !in_array($extension, $allowedExtensions)) {
                continue;
            }

            // Chercher dans le repository d'abord
            $relativePath = $folder . '/' . ($context !== '' ? $context . '/' : '') . $filename;
            $existing = $this->repository->findByPath($relativePath);
            
            if ($existing) {
                $files[] = $this->normalizeMediaRecord($existing, true);
            } else {
                // Fichier physique sans entrée JSON - créer les métadonnées
                $mime = mime_content_type($filePath) ?: 'application/octet-stream';
                $size = filesize($filePath);
                
                $dimensions = null;
                if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml') {
                    $imgInfo = @getimagesize($filePath);
                    if ($imgInfo) {
                        $dimensions = ['width' => $imgInfo[0], 'height' => $imgInfo[1]];
                    }
                }
                
                $files[] = [
                    'id' => 0,
                    'name' => $filename,
                    'original_name' => $filename,
                    'path' => $relativePath,
                    'url' => $this->normalizeMediaUrl('/uploads/' . $relativePath, $folder, $filename),
                    'folder' => $folder,
                    'type' => $this->getTypeByExtension($extension),
                    'mime' => $mime,
                    'extension' => $extension,
                    'size' => $size,
                    'dimensions' => $dimensions,
                    'uploaded_by' => 1,
                    'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                ];
            }
        }

        // Trier par date de création décroissante
        usort($files, function($a, $b) {
            return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
        });

        return $files;
    }

    /**
     * Liste les sous-dossiers disponibles dans une famille de medias.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listDirectories(string $folder): array
    {
        $folder = basename($folder);
        if ($folder === '' || $folder === '.' || $folder === '..') {
            return [];
        }

        $rootPath = $this->uploadPath . '/' . $folder;
        if (!is_dir($rootPath)) {
            return [];
        }

        $directories = [
            $this->buildDirectoryRecord($folder, ''),
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo || !$item->isDir()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($rootPath) + 1));
            $context = $this->sanitizeSubdirectory($relative);
            if ($context === '') {
                continue;
            }

            $directories[] = $this->buildDirectoryRecord($folder, $context);
        }

        usort($directories, static function (array $left, array $right): int {
            $leftPath = (string) ($left['path'] ?? '');
            $rightPath = (string) ($right['path'] ?? '');

            if ($leftPath === '') {
                return -1;
            }
            if ($rightPath === '') {
                return 1;
            }

            return strnatcasecmp($leftPath, $rightPath);
        });

        return $directories;
    }

    /**
     * Cree un sous-dossier dans une famille de medias.
     *
     * @return array<string, mixed>
     */
    public function createDirectory(string $folder, string $context): array
    {
        if (!isset(self::FOLDERS[$folder])) {
            return ['success' => false, 'error' => 'invalid_folder'];
        }

        $context = $this->sanitizeSubdirectory($context);
        if ($context === '') {
            return ['success' => false, 'error' => 'directory_invalid'];
        }

        $targetPath = $this->uploadPath . '/' . $folder . '/' . $context;
        if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true)) {
            return ['success' => false, 'error' => 'directory_create_error'];
        }

        return [
            'success' => true,
            'directory' => $this->buildDirectoryRecord($folder, $context),
        ];
    }

    /**
     * Retourne les statistiques
     */
    public function getStats(): array
    {
        $stats = [];
        
        foreach (array_keys(self::FOLDERS) as $folder) {
            $stats[$folder] = $this->countFilesRecursive($folder);
        }
        
        $stats['total'] = array_sum($stats);
        
        return $stats;
    }

    /**
     * Synchronise le repository avec les fichiers physiques
     */
    public function sync(): array
    {
        $result = ['added' => 0, 'removed' => 0];
        
        // Synchroniser le repository
        $syncResult = $this->repository->sync($this->uploadPath);
        $result['removed'] = count($syncResult['removed']);
        
        // Ajouter les fichiers physiques manquants dans le repository
        foreach (array_keys(self::FOLDERS) as $folder) {
            foreach ($this->listDirectories($folder) as $directory) {
                $files = $this->scanFolder($folder, (string) ($directory['path'] ?? ''));
                foreach ($files as $file) {
                    if (($file['id'] ?? 0) === 0) {
                        // Fichier sans ID = pas dans le repository
                        unset($file['id']);
                        $this->repository->create($file);
                        $result['added']++;
                    }
                }
            }
        }
        
        return $result;
    }

    /**
     * Retourne uniquement les images (pour sélecteur avatar, etc.)
     */
    public function getImages(bool $includeAvatars = false, string $context = ''): array
    {
        unset($includeAvatars);

        $images = [];
        $context = $this->sanitizeSubdirectory($context);

        $folders = ['images'];

        foreach ($folders as $folder) {
            $files = $this->scanFolder($folder, $context);
            foreach ($files as $file) {
                if (str_starts_with($file['mime'] ?? '', 'image/')) {
                    $images[] = $file;
                }
            }
        }
        
        return $images;
    }

    /**
     * Retourne le chemin absolu d'un media.
     */
    public function getAbsolutePath(string $relativePath): ?string
    {
        $normalized = trim(str_replace('\\', '/', $relativePath), '/');
        if ($normalized === '') {
            return null;
        }

        $absolutePath = $this->uploadPath . '/' . $normalized;
        if (!is_file($absolutePath)) {
            return null;
        }

        return $absolutePath;
    }

    /**
     * Persiste un media detecte sur disque s'il n'existe pas encore dans media.json.
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed>|null
     */
    public function ensurePersisted(array $record): ?array
    {
        $path = trim((string) ($record['path'] ?? ''));
        if ($path === '') {
            return null;
        }

        $existing = $this->repository->findByPath($path);
        if (is_array($existing)) {
            return $this->normalizeMediaRecord($existing, false);
        }

        $payload = $record;
        unset($payload['id']);
        $payload['ai_index_status'] = 'not_indexed';
        $payload['ai_indexed_at'] = null;
        $payload['ai_source_hash'] = null;
        $payload['ai_last_error'] = null;
        $payload['ai_metadata'] = [];

        return $this->repository->create($payload);
    }

    /**
     * Met a jour un media du repository.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(int $id, array $data): ?array
    {
        return $this->repository->update($id, $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDirectoryRecord(string $folder, string $context): array
    {
        $directoryPath = rtrim($this->uploadPath . '/' . $folder . '/' . $context, '/');
        $segments = $context === '' ? [] : explode('/', $context);
        $name = $context === '' ? $folder : (string) end($segments);

        $subdirNames = [];
        $subdirCount = 0;
        if (is_dir($directoryPath)) {
            foreach (scandir($directoryPath) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..' || $entry === '.gitkeep' || $entry === 'gitkeep') {
                    continue;
                }
                if (in_array($entry, self::HIDDEN_DIRS, true) || str_starts_with($entry, '.')) {
                    continue;
                }
                $full = $directoryPath . '/' . $entry;
                if (is_dir($full)) {
                    $subdirCount++;
                    $subdirNames[] = $entry;
                }
            }
        }

        return [
            'folder' => $folder,
            'path' => $context,
            'name' => $name,
            'depth' => count($segments),
            'files_count' => $this->countFilesInDirectory($folder, $context),
            'has_children' => $subdirCount > 0,
            'subdir_count' => $subdirCount,
            'subdirs' => $subdirNames,
            'created_at' => is_dir($directoryPath) ? date('Y-m-d H:i:s', (int) filemtime($directoryPath)) : null,
        ];
    }

    private function countFilesInDirectory(string $folder, string $context = ''): int
    {
        $directoryPath = rtrim($this->uploadPath . '/' . $folder . '/' . $this->sanitizeSubdirectory($context), '/');
        if (!is_dir($directoryPath)) {
            return 0;
        }

        $count = 0;
        $allowedExtensions = self::FOLDERS[$folder] ?? null;
        foreach (scandir($directoryPath) ?: [] as $filename) {
            if ($filename === '.' || $filename === '..' || $filename === '.gitkeep') {
                continue;
            }

            $filePath = $directoryPath . '/' . $filename;
            if (!is_file($filePath)) {
                continue;
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($allowedExtensions === null || in_array($extension, $allowedExtensions, true)) {
                $count++;
            }
        }

        return $count;
    }

    private function countFilesRecursive(string $folder): int
    {
        if (!isset(self::FOLDERS[$folder])) {
            return 0;
        }

        $rootPath = $this->uploadPath . '/' . $folder;
        if (!is_dir($rootPath)) {
            return 0;
        }

        $count = 0;
        $allowedExtensions = self::FOLDERS[$folder];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo || !$item->isFile()) {
                continue;
            }

            $extension = strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION));
            if (in_array($extension, $allowedExtensions, true)) {
                $count++;
            }
        }

        return $count;
    }

    private function hasSubdirectories(string $directoryPath): bool
    {
        if (!is_dir($directoryPath)) {
            return false;
        }

        foreach (scandir($directoryPath) ?: [] as $filename) {
            if ($filename === '.' || $filename === '..') {
                continue;
            }

            if (is_dir($directoryPath . '/' . $filename)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Génère un nom de fichier unique
     */
    private function generateUniqueFilename(string $originalName, string $folder, string $context = ''): string
    {
        $filename = $this->sanitizeFilename($originalName);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $counter = 1;
        
        $targetDir = rtrim($this->uploadPath . '/' . $folder . '/' . $this->sanitizeSubdirectory($context), '/');
        while (file_exists($targetDir . '/' . $filename)) {
            $filename = $baseName . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        return $filename;
    }

    /**
     * @param array<string, mixed> $source
     * @return array<string, mixed>
     */
    private function buildFileRecord(
        string $filePath,
        string $relativePath,
        string $folder,
        string $filename,
        int|string $uploadedBy,
        array $source = []
    ): array {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = strtolower((string) (mime_content_type($filePath) ?: ($source['mime'] ?? 'application/octet-stream')));
        $size = (int) (filesize($filePath) ?: ($source['size'] ?? 0));

        $dimensions = null;
        if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml') {
            $imgInfo = @getimagesize($filePath);
            if ($imgInfo) {
                $dimensions = ['width' => $imgInfo[0], 'height' => $imgInfo[1]];
            }
        }

        return [
            'name' => $filename,
            'original_name' => (string) ($source['original_name'] ?? $source['name'] ?? $filename),
            'path' => $relativePath,
            'url' => $this->normalizeMediaUrl('/uploads/' . $relativePath, $folder, $filename),
            'folder' => $folder,
            'type' => $this->getTypeByExtension($extension),
            'mime' => $mime,
            'extension' => $extension,
            'size' => $size,
            'dimensions' => $dimensions,
            'uploaded_by' => $uploadedBy,
            'ai_index_status' => 'not_indexed',
            'ai_indexed_at' => null,
            'ai_source_hash' => null,
            'ai_last_error' => null,
            'ai_metadata' => [],
        ];
    }

    private function sanitizeFilename(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = (string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $baseName = trim($baseName, '_-');
        $baseName = substr($baseName !== '' ? $baseName : 'media', 0, 50);

        return $extension !== '' ? ($baseName . '.' . $extension) : $baseName;
    }

    private function sanitizeSubdirectory(string $context): string
    {
        $normalized = trim(str_replace('\\', '/', $context), '/');
        if ($normalized === '') {
            return '';
        }

        $segments = array_filter(explode('/', $normalized), static function (string $segment): bool {
            return $segment !== '' && !in_array($segment, ['.', '..'], true);
        });

        $safeSegments = [];
        foreach ($segments as $segment) {
            $safe = strtolower((string) preg_replace('/[^a-z0-9_-]+/i', '-', $segment));
            $safe = trim($safe, '-_');
            if ($safe !== '') {
                $safeSegments[] = $safe;
            }
        }

        return substr(implode('/', $safeSegments), 0, 160);
    }

    private function resolveSafeUploadDirectory(string $relativePath): ?string
    {
        $normalized = trim(str_replace('\\', '/', $relativePath), '/');
        if ($normalized === '' || str_contains($normalized, "\0")) {
            return null;
        }

        $root = realpath($this->uploadPath);
        $directory = realpath($this->uploadPath . '/' . $normalized);
        if ($root === false || $directory === false || !is_dir($directory)) {
            return null;
        }

        $root = rtrim(str_replace('\\', '/', $root), '/');
        $directory = rtrim(str_replace('\\', '/', $directory), '/');
        if ($directory === $root || !str_starts_with($directory . '/', $root . '/')) {
            return null;
        }

        return $directory;
    }

    private function removeDirectoryRecursive(string $directory): bool
    {
        $items = scandir($directory);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $directory . '/' . $item;
            if (is_dir($itemPath) && !is_link($itemPath)) {
                if (!$this->removeDirectoryRecursive($itemPath)) {
                    return false;
                }
                continue;
            }

            if (!@unlink($itemPath)) {
                return false;
            }
        }

        return @rmdir($directory);
    }

    /**
     * Valide le MIME type
     */
    private function isValidMimeType(string $mime, string $folder): bool
    {
        $validMimes = [
            'images' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/x-icon', 'image/bmp'],
            'videos' => ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-quicktime', 'video/mov', 'video/x-ms-wmv', 'video/x-flv', 'video/x-matroska', 'video/webm', 'video/x-msvideo', 'video/3gpp', 'video/3gpp2', 'video/ogg', 'video/x-m4v', 'application/octet-stream'],
            'sounds' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/aac', 'audio/flac', 'audio/mp4'],
            'documents' => ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                          'text/plain', 'text/rtf', 'application/rtf', 'application/vnd.oasis.opendocument.text'],
            'pdf' => ['application/pdf'],
            'spreadsheets' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                              'text/csv', 'application/vnd.oasis.opendocument.spreadsheet'],
            'archives' => [
                'application/zip',
                'application/x-zip',
                'application/x-zip-compressed',
                'multipart/x-zip',
                'application/vnd.rar',
                'application/rar',
                'application/x-rar',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/x-tar',
                'application/x-gtar',
                'application/x-compressed-tar',
                'application/gzip',
                'application/x-gzip',
                'application/gz',
            ]
        ];

        return in_array($mime, $validMimes[$folder] ?? []);
    }

    /**
     * Autorise certains MIME techniques/fallback lorsque l'extension est deja validee
     */
    private function isFallbackMimeAllowed(string $mime, string $folder, string $extension): bool
    {
        if ($folder !== 'archives') {
            return false;
        }

        if (!in_array($extension, self::FOLDERS['archives'], true)) {
            return false;
        }

        return in_array($mime, [
            '',
            'application/octet-stream',
            'binary/octet-stream',
            'application/x-binary',
        ], true);
    }

    /**
     * Retourne le type par extension
     */
    private function getTypeByExtension(string $extension): string
    {
        foreach (self::FOLDERS as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }
        return 'documents';
    }

    /**
     * Retourne le message d'erreur d'upload
     */
    private function getUploadError(int $code): string
    {
        return match($code) {
            UPLOAD_ERR_INI_SIZE => 'file_exceeds_ini_size',
            UPLOAD_ERR_FORM_SIZE => 'file_exceeds_form_size',
            UPLOAD_ERR_PARTIAL => 'file_partial_upload',
            UPLOAD_ERR_NO_FILE => 'no_file_uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'missing_temp_folder',
            UPLOAD_ERR_CANT_WRITE => 'failed_to_write',
            UPLOAD_ERR_EXTENSION => 'upload_stopped_by_extension',
            default => 'unknown_upload_error',
        };
    }

    /**
     * Retourne la configuration d'un dossier
     */
    public function getFolderConfig(string $folder): array
    {
        return self::FOLDER_CONFIG[$folder] ?? [];
    }

    /**
     * Retourne tous les dossiers avec leur configuration
     */
    public function getAllFoldersConfig(): array
    {
        $config = [];
        foreach (self::FOLDER_CONFIG as $folder => $data) {
            $config[$folder] = array_merge($data, [
                'name' => $folder,
                'extensions' => self::FOLDERS[$folder]
            ]);
        }
        return $config;
    }

    /**
     * Dossiers masqués - jamais affichés dans l'arborescence
     */
    private const HIDDEN_DIRS = ['cache', 'files', 'media', 'personal', 'logo', '.DS_Store'];

    /**
     * Scanne les vrais dossiers du filesystem dans public/uploads/
     *
     * @return array<int, array{path: string, name: string, icon: string, color: string, count: int}>
     */
    public function scanUploadDirectories(): array
    {
        if (!is_dir($this->uploadPath)) {
            return [];
        }

        $directories = [];
        $items = scandir($this->uploadPath);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $this->uploadPath . '/' . $item;
            if (!is_dir($fullPath)) {
                continue;
            }

            if (in_array($item, self::HIDDEN_DIRS, true) || str_starts_with($item, '.')) {
                continue;
            }

            $isConfigured = isset(self::FOLDER_CONFIG[$item]);
            $icon = $isConfigured ? (self::FOLDER_CONFIG[$item]['icon'] ?? 'fa-folder') : 'fa-folder';
            $color = $isConfigured ? (self::FOLDER_CONFIG[$item]['color'] ?? 'gray') : 'gray';
            $count = $this->countAllFiles($fullPath);

            $directories[] = [
                'path' => $item,
                'name' => $item,
                'icon' => $icon,
                'color' => $color,
                'count' => $count,
            ];
        }

        usort($directories, static function (array $left, array $right): int {
            return strnatcasecmp($left['name'], $right['name']);
        });

        return $directories;
    }

    /**
     * Compte tous les fichiers d'un dossier (sans filtre d'extension)
     */
    private function countAllFiles(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item instanceof \SplFileInfo && $item->isFile()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Formate la taille en unité lisible
     */
    public static function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }

    /**
     * Retourne l'arborescence complète de public/uploads/ avec fichiers et dossiers.
     *
     * @return array<string, mixed>
     */
    public function getDirectoryTree(): array
    {
        $rootPath = $this->uploadPath;
        if (!is_dir($rootPath)) {
            return ['name' => 'uploads', 'path' => '', 'type' => 'directory', 'count' => 0, 'children' => []];
        }

        return $this->buildTreeRecursive($rootPath, '');
    }

    /**
     * Construit récursivement l'arbre d'un dossier.
     *
     * @param string $absolutePath Chemin absolu sur le disque
     * @param string $relativePath Chemin relatif à uploads/
     * @return array<string, mixed>
     */
    private function buildTreeRecursive(string $absolutePath, string $relativePath): array
    {
        $name = $relativePath === '' ? 'uploads' : basename($absolutePath);
        $items = scandir($absolutePath);

        if ($items === false) {
            return ['name' => $name, 'path' => $relativePath, 'type' => 'directory', 'count' => 0, 'children' => []];
        }

        $children = [];
        $fileCount = 0;

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep' || $item === 'gitkeep') {
                continue;
            }

            if (in_array($item, self::HIDDEN_DIRS, true) || str_starts_with($item, '.')) {
                continue;
            }

            // Skip macOS metadata files that don't start with '.'
            if ($item === "Icon\r" || $item === 'Thumbs.db') {
                continue;
            }

            $fullPath = $absolutePath . '/' . $item;
            $itemRelative = $relativePath === '' ? $item : $relativePath . '/' . $item;

            if (is_dir($fullPath)) {
                $sub = $this->buildTreeRecursive($fullPath, $itemRelative);
                $fileCount += $sub['count'];
                $children[] = $sub;
            } elseif (is_file($fullPath)) {
                $fileCount++;
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                $folder = explode('/', $itemRelative)[0];
                $url = $this->normalizeMediaUrl('/uploads/' . $itemRelative, $folder, $item);
                $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
                $children[] = [
                    'name' => $item,
                    'path' => $itemRelative,
                    'type' => 'file',
                    'extension' => $ext,
                    'url' => $url,
                    'mime' => $mime,
                ];
            }
        }

        $isConfigured = $relativePath !== '' && isset(self::FOLDER_CONFIG[$name]);
        $icon = $isConfigured ? (self::FOLDER_CONFIG[$name]['icon'] ?? 'fa-folder') : 'fa-folder';
        $color = $isConfigured ? (self::FOLDER_CONFIG[$name]['color'] ?? 'gray') : 'gray';

        return [
            'name' => $name,
            'path' => $relativePath,
            'type' => 'directory',
            'count' => $fileCount,
            'icon' => $icon,
            'color' => $color,
            'children' => $children,
        ];
    }

    /**
     * Retourne le nombre total de fichiers dans uploads/
     */
    public function getTotalFileCount(): int
    {
        $tree = $this->getDirectoryTree();
        return $tree['count'] ?? 0;
    }
}
