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
            foreach ($this->scanFolder($folder) as $item) {
                $path = trim((string) ($item['path'] ?? ''));
                if ($path === '') {
                    continue;
                }

                $items[$path] = $item;
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
    public function upload(array $file, string $folder = 'images', int|string $uploadedBy = 1): array
    {
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
        $filename = $this->generateUniqueFilename($originalName, $folder);
        $targetPath = $this->uploadPath . '/' . $folder . '/' . $filename;

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
            'path' => $folder . '/' . $filename,
            'url' => $this->normalizeMediaUrl('/uploads/' . $folder . '/' . $filename, $folder, $filename),
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

    /**
     * Scanne un dossier et retourne les fichiers (physiques + JSON)
     */
    public function scanFolder(string $folder): array
    {
        if (!isset(self::FOLDERS[$folder])) {
            return [];
        }

        $folderPath = $this->uploadPath . '/' . $folder;
        if (!is_dir($folderPath)) {
            return [];
        }

        $files = [];
        $allowedExtensions = self::FOLDERS[$folder];

        foreach (scandir($folderPath) as $filename) {
            if ($filename === '.' || $filename === '..' || $filename === '.gitkeep') {
                continue;
            }

            $filePath = $folderPath . '/' . $filename;
            if (!is_file($filePath)) {
                continue;
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions)) {
                continue;
            }

            // Chercher dans le repository d'abord
            $existing = $this->repository->findByPath($folder . '/' . $filename);
            
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
                    'path' => $folder . '/' . $filename,
                    'url' => $this->normalizeMediaUrl('/uploads/' . $folder . '/' . $filename, $folder, $filename),
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
     * Retourne les statistiques
     */
    public function getStats(): array
    {
        $stats = [];
        
        foreach (array_keys(self::FOLDERS) as $folder) {
            $files = $this->scanFolder($folder);
            $stats[$folder] = count($files);
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
            $files = $this->scanFolder($folder);
            foreach ($files as $file) {
                if (($file['id'] ?? 0) === 0) {
                    // Fichier sans ID = pas dans le repository
                    unset($file['id']);
                    $this->repository->create($file);
                    $result['added']++;
                }
            }
        }
        
        return $result;
    }

    /**
     * Retourne uniquement les images (pour sélecteur avatar, etc.)
     */
    public function getImages(bool $includeAvatars = false): array
    {
        unset($includeAvatars);

        $images = [];

        $folders = ['images'];

        foreach ($folders as $folder) {
            $files = $this->scanFolder($folder);
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
     * Génère un nom de fichier unique
     */
    private function generateUniqueFilename(string $originalName, string $folder): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $baseName = substr($baseName, 0, 50);
        
        $filename = $baseName . '.' . $extension;
        $counter = 1;
        
        while (file_exists($this->uploadPath . '/' . $folder . '/' . $filename)) {
            $filename = $baseName . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        return $filename;
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
}
