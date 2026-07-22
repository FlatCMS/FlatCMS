<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Backups\Services;

use App\Core\CoreManifest;
use App\Core\FlatFile;
use App\Core\Security\SecretBox;

final class SiteBackupService
{
    private const ARCHIVE_KIND = 'flatcms-site-backup';
    private const ARCHIVE_VERSION = 1;
    private const TMP_UPLOAD_PREFIX = 'site-upload-';
    private const BACKUP_PREFIX = 'flatcms-site-backup';
    private const ROLLBACK_PREFIX = 'flatcms-site-pre-restore';

    private string $dataRoot;
    private string $backupRoot;
    private string $tmpRoot;
    private string $cacheDataRoot;
    private string $cacheViewsRoot;
    private string $runtimeCssRoot;
    private string $publicUploadsRoot;
    private string $uploadsRoot;
    private string $storageAvatarsRoot;
    private string $storageSecretKeyPath;

    public function __construct()
    {
        $storageRoot = defined('STORAGE_PATH') ? (string) STORAGE_PATH : BASE_PATH . '/storage';

        $this->dataRoot = rtrim(BASE_PATH, '/') . '/data';
        $this->backupRoot = rtrim($storageRoot, '/') . '/backups/site';
        $this->tmpRoot = rtrim($storageRoot, '/') . '/tmp/backups';
        $this->cacheDataRoot = rtrim($storageRoot, '/') . '/cache/data';
        $this->cacheViewsRoot = rtrim($storageRoot, '/') . '/cache/views';
        $this->runtimeCssRoot = rtrim(PUBLIC_PATH, '/') . '/uploads/cache/runtime-css';
        $this->publicUploadsRoot = rtrim(PUBLIC_PATH, '/') . '/uploads';
        $this->uploadsRoot = rtrim(BASE_PATH, '/') . '/uploads';
        $this->storageAvatarsRoot = rtrim($storageRoot, '/') . '/uploads/avatars';
        $this->storageSecretKeyPath = (new SecretBox())->storagePath();
    }

    public function zipAvailable(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listBackups(): array
    {
        $this->ensureDirectories();

        $items = [];
        foreach (glob($this->backupRoot . '/*.zip') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $items[] = $this->backupItemFromPath($path);
        }

        usort($items, static function (array $left, array $right): int {
            return ((int) ($right['created_ts'] ?? 0)) <=> ((int) ($left['created_ts'] ?? 0));
        });

        return $items;
    }

    /**
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    public function createBackup(array $context = []): array
    {
        $this->assertZipAvailable();
        $this->ensureDirectories();

        $files = $this->snapshotArchiveFiles();
        if ($files === []) {
            throw new \RuntimeException('backups_error_no_data');
        }

        $filename = $this->buildBackupFilename(self::BACKUP_PREFIX);
        $path = $this->backupRoot . '/' . $filename;
        $manifest = $this->buildManifest($files, $context);

        $this->writeArchive($path, $files, $manifest);

        return $this->backupItemFromPath($path);
    }

    /**
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    public function restoreStoredBackup(string $filename, array $context = []): array
    {
        $path = $this->resolveStoredBackupPath($filename);
        if ($path === null) {
            throw new \RuntimeException('backups_archive_not_found');
        }

        return $this->restoreArchive($path, $context);
    }

    /**
     * @param array<string, mixed>|null $upload
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    public function restoreUploadedBackup(?array $upload, array $context = []): array
    {
        $this->assertZipAvailable();
        $this->ensureDirectories();

        if (!is_array($upload) || empty($upload['tmp_name'])) {
            throw new \RuntimeException('backups_upload_missing');
        }

        $error = (int) ($upload['error'] ?? UPLOAD_ERR_OK);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($this->uploadErrorKey($error));
        }

        $originalName = trim((string) ($upload['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'zip') {
            throw new \RuntimeException('backups_upload_invalid_format');
        }

        $targetPath = $this->tmpRoot . '/' . $this->buildBackupFilename(self::TMP_UPLOAD_PREFIX);
        $tmpName = (string) ($upload['tmp_name'] ?? '');
        $moved = $tmpName !== '' && @move_uploaded_file($tmpName, $targetPath);
        if (!$moved && $tmpName !== '') {
            $moved = @rename($tmpName, $targetPath);
        }
        if (!$moved && $tmpName !== '') {
            $moved = @copy($tmpName, $targetPath);
        }
        if (!$moved) {
            throw new \RuntimeException('backups_upload_failed');
        }

        try {
            return $this->restoreArchive($targetPath, $context);
        } finally {
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }
        }
    }

    public function resolveStoredBackupPath(string $filename): ?string
    {
        $normalized = $this->normalizeBackupFilename($filename);
        if ($normalized === '') {
            return null;
        }

        $path = $this->backupRoot . '/' . $normalized;
        if (!is_file($path)) {
            return null;
        }

        return $path;
    }

    public function deleteStoredBackup(string $filename): void
    {
        $path = $this->resolveStoredBackupPath($filename);
        if ($path === null) {
            throw new \RuntimeException('backups_archive_not_found');
        }

        if (!@unlink($path)) {
            throw new \RuntimeException('backups_delete_failed');
        }
    }

    /**
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    public function resetSiteContent(array $context = []): array
    {
        $this->assertZipAvailable();
        $this->ensureDirectories();

        $currentFiles = $this->snapshotArchiveFiles();
        $rollback = $this->createRollbackBackup($currentFiles, array_merge($context, ['reason' => 'pre_reset']));
        $resetFiles = $this->buildResetSnapshot();

        try {
            $this->mirrorArchiveFiles($resetFiles);
            $this->clearRuntimeCaches();
        } catch (\Throwable $exception) {
            $this->mirrorArchiveFiles($currentFiles);
            $this->clearRuntimeCaches();
            throw $exception;
        }

        return [
            'reset_files_count' => count($resetFiles),
            'rollback' => $rollback,
        ];
    }

    /**
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    public function factoryResetSite(array $context = []): array
    {
        $this->assertZipAvailable();
        $this->ensureDirectories();

        $currentFiles = $this->snapshotArchiveFiles();
        $rollback = $this->createRollbackBackup($currentFiles, array_merge($context, ['reason' => 'pre_factory_reset']));
        $bootstrapFiles = $this->buildFactoryResetBootstrapSnapshot();

        try {
            $this->mirrorArchiveFiles($bootstrapFiles);
            $this->deleteFactoryResetResidualFiles();
            $this->clearRuntimeCaches();
        } catch (\Throwable $exception) {
            $this->mirrorArchiveFiles($currentFiles);
            $this->clearRuntimeCaches();
            throw $exception;
        }

        return [
            'bootstrap_files_count' => count($bootstrapFiles),
            'rollback' => $rollback,
        ];
    }

    /**
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    private function restoreArchive(string $archivePath, array $context = []): array
    {
        $this->assertZipAvailable();
        $this->ensureDirectories();

        $payload = $this->readArchivePayload($archivePath);
        $payload['files'] = $this->adaptRestoredFilesToCurrentInstallation($payload['files']);
        $currentFiles = $this->snapshotArchiveFiles();
        $rollback = $this->createRollbackBackup($currentFiles, $context);

        try {
            $this->mirrorArchiveFiles($payload['files']);
            $this->clearRuntimeCaches();
        } catch (\Throwable $exception) {
            $this->mirrorArchiveFiles($currentFiles);
            $this->clearRuntimeCaches();
            throw $exception;
        }

        return [
            'restored_files_count' => count($payload['files']),
            'manifest' => $payload['manifest'],
            'rollback' => $rollback,
        ];
    }

    /**
     * @param array<string, string> $files
     * @return array<string, string>
     */
    private function adaptRestoredFilesToCurrentInstallation(array $files): array
    {
        if (!isset($files['data/settings.json'])) {
            return $files;
        }

        $currentSiteUrl = $this->resolveCurrentInstallationUrl();
        if ($currentSiteUrl === '') {
            return $files;
        }

        $settings = json_decode($files['data/settings.json'], true);
        if (!is_array($settings)) {
            return $files;
        }

        $existingSiteUrl = trim((string) ($settings['site_url'] ?? ''));
        if ($this->normalizeComparableUrl($existingSiteUrl) === $this->normalizeComparableUrl($currentSiteUrl)) {
            return $files;
        }

        $settings['site_url'] = $currentSiteUrl;
        $files['data/settings.json'] = $this->encodeJson($settings);

        return $files;
    }

    /**
     * @param array<string, string> $files
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    private function createRollbackBackup(array $files, array $context = []): array
    {
        $manifest = $this->buildManifest($files, array_merge($context, [
            'reason' => (string) ($context['reason'] ?? 'pre_restore'),
        ]));

        if ($files === []) {
            $filename = $this->buildBackupFilename(self::ROLLBACK_PREFIX);
            $path = $this->backupRoot . '/' . $filename;
            $this->writeArchive($path, $files, $manifest);
            return $this->backupItemFromPath($path);
        }

        $filename = $this->buildBackupFilename(self::ROLLBACK_PREFIX);
        $path = $this->backupRoot . '/' . $filename;
        $this->writeArchive($path, $files, $manifest);

        return $this->backupItemFromPath($path);
    }

    /**
     * @param array<string, string> $files
     */
    private function mirrorArchiveFiles(array $files): void
    {
        $existingFiles = array_keys($this->snapshotArchiveFiles());
        $nextFiles = array_keys($files);

        foreach (array_diff($existingFiles, $nextFiles) as $relativePath) {
            $absolutePath = $this->absolutePathForRelative($relativePath);
            if (is_file($absolutePath) && !@unlink($absolutePath)) {
                throw new \RuntimeException('backups_restore_write_failed');
            }
        }

        foreach ($files as $relativePath => $content) {
            $absolutePath = $this->absolutePathForRelative($relativePath);
            $targetDir = dirname($absolutePath);
            if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                throw new \RuntimeException('backups_restore_write_failed');
            }

            if (@file_put_contents($absolutePath, $content, LOCK_EX) === false) {
                throw new \RuntimeException('backups_restore_write_failed');
            }
        }
    }

    /**
     * @return array{manifest: array<string, mixed>, files: array<string, string>}
     */
    private function readArchivePayload(string $archivePath): array
    {
        if (!is_file($archivePath)) {
            throw new \RuntimeException('backups_archive_not_found');
        }

        $zip = new \ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException('backups_archive_open_failed');
        }

        try {
            $manifest = [];
            $files = [];

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entryName = $zip->getNameIndex($index);
                if (!is_string($entryName) || $entryName === '') {
                    continue;
                }

                $normalizedEntry = $this->normalizeRestorableArchiveEntry($entryName);
                if ($normalizedEntry === '' || str_ends_with($normalizedEntry, '/')) {
                    continue;
                }

                if (!$this->isAllowedArchiveEntry($normalizedEntry)) {
                    throw new \RuntimeException('backups_archive_invalid');
                }

                $content = $zip->getFromIndex($index);
                if (!is_string($content)) {
                    throw new \RuntimeException('backups_archive_invalid');
                }

                if ($normalizedEntry === 'manifest.json') {
                    $decoded = json_decode($content, true);
                    if (!is_array($decoded)) {
                        throw new \RuntimeException('backups_archive_invalid_manifest');
                    }
                    $manifest = $decoded;
                    continue;
                }

                if ($this->isDataJsonEntry($normalizedEntry) && !$this->isValidJson($content)) {
                    throw new \RuntimeException('backups_archive_invalid_json');
                }

                $files[$normalizedEntry] = $content;
            }

            if ($files === []) {
                throw new \RuntimeException('backups_archive_empty');
            }

            return [
                'manifest' => $manifest,
                'files' => $files,
            ];
        } finally {
            $zip->close();
        }
    }

    /**
     * @param array<string, string> $files
     * @param array<string, mixed> $manifest
     */
    private function writeArchive(string $archivePath, array $files, array $manifest): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('backups_archive_write_failed');
        }

        try {
            $manifestContent = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if (!is_string($manifestContent) || !$zip->addFromString('manifest.json', $manifestContent)) {
                throw new \RuntimeException('backups_archive_write_failed');
            }

            foreach ($files as $relativePath => $content) {
                if (!$zip->addFromString($relativePath, $content)) {
                    throw new \RuntimeException('backups_archive_write_failed');
                }
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @param array<string, string> $files
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    private function buildManifest(array $files, array $context = []): array
    {
        $settings = FlatFile::settings();
        $siteName = trim((string) ($settings['site_name'] ?? config('app.name', 'FlatCMS')));
        $defaultLanguage = trim((string) ($settings['default_language'] ?? config('app.locale', 'fr-FR')));
        $reason = trim((string) ($context['reason'] ?? 'manual'));
        $createdBy = trim((string) ($context['created_by'] ?? ''));
        $createdByEmail = trim((string) ($context['created_by_email'] ?? ''));
        $createdAt = date('Y-m-d H:i:s');
        $sourceUrl = $this->resolveCurrentInstallationUrl();
        if ($sourceUrl === '') {
            $sourceUrl = trim((string) config('app.url', ''));
        }

        return [
            'kind' => self::ARCHIVE_KIND,
            'version' => self::ARCHIVE_VERSION,
            'created_at' => $createdAt,
            'created_unix' => time(),
            'reason' => $reason,
            'flatcms_version' => CoreManifest::version('1.0.0'),
            'site_name' => $siteName,
            'default_language' => $defaultLanguage,
            'source_url' => $sourceUrl,
            'json_files_count' => $this->countFilesByPrefix($files, 'data/'),
            'media_files_count' => $this->countMediaFiles($files),
            'total_files_count' => count($files),
            'created_by' => $createdBy,
            'created_by_email' => $createdByEmail,
            'scope' => 'data-json-public-uploads-storage-avatars',
            'includes_media' => true,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function snapshotArchiveFiles(): array
    {
        $files = [];
        $files += $this->snapshotJsonDirectory($this->dataRoot, 'data');
        $files += $this->snapshotFileDirectory($this->publicUploadsRoot, 'public/uploads', [
            'cache/runtime-css/',
        ]);
        $files += $this->snapshotFileDirectory($this->storageAvatarsRoot, 'storage/uploads/avatars');
        $files += $this->snapshotExactFile($this->storageSecretKeyPath, 'storage/app/secretbox.key');

        if (!$this->uploadsAliasesPublicUploads()) {
            $files += $this->snapshotFileDirectory($this->uploadsRoot, 'uploads');
        }

        ksort($files);

        return $files;
    }

    /**
     * @return array<string, mixed>
     */
    private function backupItemFromPath(string $path): array
    {
        $filename = basename($path);
        $manifest = $this->readManifestFromArchive($path);
        $filemtime = @filemtime($path) ?: time();
        $createdTs = (int) ($manifest['created_unix'] ?? $filemtime);
        $createdAt = trim((string) ($manifest['created_at'] ?? ''));
        if ($createdAt === '') {
            $createdAt = date('Y-m-d H:i:s', $createdTs);
        }

        $reason = trim((string) ($manifest['reason'] ?? 'manual'));

        return [
            'filename' => $filename,
            'path' => $path,
            'size_bytes' => (int) (@filesize($path) ?: 0),
            'created_at' => $createdAt,
            'created_ts' => $createdTs,
            'flatcms_version' => trim((string) ($manifest['flatcms_version'] ?? '')),
            'site_name' => trim((string) ($manifest['site_name'] ?? '')),
            'default_language' => trim((string) ($manifest['default_language'] ?? '')),
            'source_url' => trim((string) ($manifest['source_url'] ?? '')),
            'json_files_count' => (int) ($manifest['json_files_count'] ?? $this->countArchiveJsonFiles($path)),
            'media_files_count' => (int) ($manifest['media_files_count'] ?? $this->countArchiveMediaFiles($path)),
            'total_files_count' => (int) ($manifest['total_files_count'] ?? ($this->countArchiveJsonFiles($path) + $this->countArchiveMediaFiles($path))),
            'created_by' => trim((string) ($manifest['created_by'] ?? '')),
            'created_by_email' => trim((string) ($manifest['created_by_email'] ?? '')),
            'reason' => $reason,
            'is_rollback' => $reason === 'pre_restore',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifestFromArchive(string $path): array
    {
        if (!$this->zipAvailable() || !is_file($path)) {
            return [];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        try {
            $raw = $zip->getFromName('manifest.json');
            if (!is_string($raw)) {
                return [];
            }

            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        } finally {
            $zip->close();
        }
    }

    private function countArchiveJsonFiles(string $path): int
    {
        if (!$this->zipAvailable() || !is_file($path)) {
            return 0;
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return 0;
        }

        $count = 0;
        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entryName = $zip->getNameIndex($index);
                if (!is_string($entryName)) {
                    continue;
                }

                $normalized = $this->normalizeArchiveEntry($entryName);
                if ($normalized !== '' && $normalized !== 'manifest.json' && $this->isDataJsonEntry($normalized)) {
                    $count++;
                }
            }
        } finally {
            $zip->close();
        }

        return $count;
    }

    private function countArchiveMediaFiles(string $path): int
    {
        if (!$this->zipAvailable() || !is_file($path)) {
            return 0;
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return 0;
        }

        $count = 0;
        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entryName = $zip->getNameIndex($index);
                if (!is_string($entryName)) {
                    continue;
                }

                $normalized = $this->normalizeArchiveEntry($entryName);
                if ($normalized !== '' && $normalized !== 'manifest.json' && $this->isMediaEntry($normalized)) {
                    $count++;
                }
            }
        } finally {
            $zip->close();
        }

        return $count;
    }

    private function normalizeBackupFilename(string $filename): string
    {
        $value = basename(trim($filename));
        if ($value === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z0-9._-]+\.zip$/', $value) !== 1) {
            return '';
        }

        return $value;
    }

    private function resolveCurrentInstallationUrl(): string
    {
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            $configured = trim((string) config('app.url', ''));
            return $configured !== '' ? rtrim($configured, '/') : '';
        }

        $scheme = $this->detectCurrentRequestScheme();
        $base = base_url();
        $path = (string) (parse_url($base, PHP_URL_PATH) ?? '');
        if ($path === '' || $path === '.') {
            $path = '';
        }

        $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '/') {
            $path = '';
        }
        if ($path !== '' && str_ends_with($path, '/public')) {
            $path = substr($path, 0, -7);
            if ($path === false || $path === '/') {
                $path = '';
            }
        }

        return rtrim($scheme . '://' . $host . $path, '/');
    }

    private function detectCurrentRequestScheme(): string
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return 'https';
        }

        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return 'https';
        }

        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto !== '') {
            foreach (array_map('trim', explode(',', $forwardedProto)) as $proto) {
                if ($proto === 'https') {
                    return 'https';
                }
            }
        }

        if (strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https') {
            return 'https';
        }

        $cfVisitor = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');
        if ($cfVisitor !== '') {
            $decoded = json_decode($cfVisitor, true);
            if (is_array($decoded) && strtolower((string) ($decoded['scheme'] ?? '')) === 'https') {
                return 'https';
            }
        }

        return 'http';
    }

    private function normalizeComparableUrl(string $value): string
    {
        return rtrim(strtolower(trim($value)), '/');
    }

    private function buildBackupFilename(string $prefix): string
    {
        $safePrefix = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($prefix))) ?? 'backup';
        $safePrefix = trim($safePrefix, '-');
        if ($safePrefix === '') {
            $safePrefix = 'backup';
        }

        return $safePrefix . '-' . date('Ymd_His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.zip';
    }

    private function absolutePathForRelative(string $relativePath): string
    {
        $normalized = $this->normalizeArchiveEntry($relativePath);
        if (!$this->isDataJsonEntry($normalized) && !$this->isMediaEntry($normalized) && !$this->isSecretEntry($normalized)) {
            throw new \RuntimeException('backups_restore_write_failed');
        }

        return rtrim(BASE_PATH, '/') . '/' . $normalized;
    }

    private function normalizeArchiveEntry(string $entryName): string
    {
        $normalized = str_replace('\\', '/', trim($entryName));
        $normalized = ltrim($normalized, '/');
        $normalized = preg_replace('#/+#', '/', $normalized) ?? '';

        return $normalized;
    }

    private function normalizeRestorableArchiveEntry(string $entryName): string
    {
        $normalized = $this->normalizeArchiveEntry($entryName);
        if ($normalized === '' || str_starts_with($normalized, '__MACOSX/')) {
            return '';
        }

        $basename = basename($normalized);
        if ($basename === '.DS_Store' || str_starts_with($basename, '._')) {
            return '';
        }

        if ($this->isAllowedArchiveEntry($normalized)) {
            return $normalized;
        }

        $firstSlash = strpos($normalized, '/');
        if ($firstSlash === false) {
            return $normalized;
        }

        $stripped = ltrim(substr($normalized, $firstSlash + 1), '/');
        if ($stripped === '' || str_starts_with($stripped, '__MACOSX/')) {
            return '';
        }

        $strippedBasename = basename($stripped);
        if ($strippedBasename === '.DS_Store' || str_starts_with($strippedBasename, '._')) {
            return '';
        }

        return $stripped;
    }

    private function isAllowedArchiveEntry(string $entryName): bool
    {
        if ($entryName === '' || str_contains($entryName, '../') || str_starts_with($entryName, '../')) {
            return false;
        }

        if ($entryName === 'manifest.json') {
            return true;
        }

        if ($this->isDataJsonEntry($entryName)) {
            return true;
        }

        if ($this->isMediaEntry($entryName)) {
            return true;
        }

        if ($this->isSecretEntry($entryName)) {
            return true;
        }

        return false;
    }

    private function isValidJson(string $content): bool
    {
        json_decode($content, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function clearRuntimeCaches(): void
    {
        $this->purgeDirectoryContents($this->cacheDataRoot);
        $this->purgeDirectoryContents($this->cacheViewsRoot);
        $this->purgeDirectoryContents($this->runtimeCssRoot);
        clearstatcache(true);
    }

    /**
     * @return array<string, string>
     */
    private function snapshotJsonDirectory(string $absoluteRoot, string $archiveRoot): array
    {
        $files = [];
        if (!is_dir($absoluteRoot)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absoluteRoot, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $pathname = $item->getPathname();
            if (strtolower(pathinfo($pathname, PATHINFO_EXTENSION)) !== 'json') {
                continue;
            }

            $content = @file_get_contents($pathname);
            if (!is_string($content) || !$this->isValidJson($content)) {
                continue;
            }

            $files[$this->buildArchiveRelativePath($absoluteRoot, $archiveRoot, $pathname)] = $content;
        }

        return $files;
    }

    /**
     * @param array<int, string> $excludedPrefixes
     * @return array<string, string>
     */
    private function snapshotFileDirectory(string $absoluteRoot, string $archiveRoot, array $excludedPrefixes = []): array
    {
        $files = [];
        if (!is_dir($absoluteRoot)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absoluteRoot, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile() || $item->isLink()) {
                continue;
            }

            $pathname = $item->getPathname();
            $relativeWithinRoot = ltrim(str_replace('\\', '/', substr($pathname, strlen($absoluteRoot))), '/');
            if ($relativeWithinRoot === '' || $this->shouldSkipMediaRelativePath($relativeWithinRoot, $excludedPrefixes)) {
                continue;
            }

            $basename = (string) $item->getBasename();
            if ($basename === '.gitkeep' || $basename === '.DS_Store') {
                continue;
            }

            $content = @file_get_contents($pathname);
            if (!is_string($content)) {
                continue;
            }

            $files[$archiveRoot . '/' . $relativeWithinRoot] = $content;
        }

        return $files;
    }

    /**
     * @return array<string, string>
     */
    private function snapshotExactFile(string $absolutePath, string $archivePath): array
    {
        if (!is_file($absolutePath) || is_link($absolutePath)) {
            return [];
        }

        $content = @file_get_contents($absolutePath);
        if (!is_string($content)) {
            return [];
        }

        return [$archivePath => $content];
    }

    private function buildArchiveRelativePath(string $absoluteRoot, string $archiveRoot, string $pathname): string
    {
        return $archiveRoot . '/' . ltrim(str_replace('\\', '/', substr($pathname, strlen($absoluteRoot))), '/');
    }

    /**
     * @param array<int, string> $excludedPrefixes
     */
    private function shouldSkipMediaRelativePath(string $relativePath, array $excludedPrefixes): bool
    {
        foreach ($excludedPrefixes as $prefix) {
            $normalizedPrefix = trim(str_replace('\\', '/', $prefix), '/');
            if ($normalizedPrefix !== '' && ($relativePath === $normalizedPrefix || str_starts_with($relativePath, $normalizedPrefix . '/'))) {
                return true;
            }
        }

        return false;
    }

    private function uploadsAliasesPublicUploads(): bool
    {
        if (!file_exists($this->uploadsRoot) || !file_exists($this->publicUploadsRoot)) {
            return false;
        }

        $uploadsReal = realpath($this->uploadsRoot);
        $publicReal = realpath($this->publicUploadsRoot);

        return is_string($uploadsReal)
            && is_string($publicReal)
            && $uploadsReal !== ''
            && $uploadsReal === $publicReal;
    }

    private function isDataJsonEntry(string $entryName): bool
    {
        return str_starts_with($entryName, 'data/') && str_ends_with($entryName, '.json');
    }

    private function isSecretEntry(string $entryName): bool
    {
        return $entryName === 'storage/app/secretbox.key';
    }

    private function isMediaEntry(string $entryName): bool
    {
        if (str_starts_with($entryName, 'public/uploads/')) {
            return !str_starts_with($entryName, 'public/uploads/cache/runtime-css/');
        }

        if (str_starts_with($entryName, 'storage/uploads/avatars/')) {
            return true;
        }

        return str_starts_with($entryName, 'uploads/');
    }

    /**
     * @param array<string, string> $files
     */
    private function countFilesByPrefix(array $files, string $prefix): int
    {
        $count = 0;
        foreach (array_keys($files) as $relativePath) {
            if (str_starts_with($relativePath, $prefix)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, string> $files
     */
    private function countMediaFiles(array $files): int
    {
        $count = 0;
        foreach (array_keys($files) as $relativePath) {
            if ($this->isMediaEntry($relativePath)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array<string, string>
     */
    private function buildResetSnapshot(): array
    {
        $settings = $this->readJsonFile(BASE_PATH . '/data/settings.json');
        $defaultLanguage = trim((string) ($settings['default_language'] ?? $settings['language'] ?? 'fr-FR'));
        if ($defaultLanguage === '') {
            $defaultLanguage = 'fr-FR';
        }

        $files = [];
        $files += $this->snapshotJsonDirectory($this->dataRoot . '/languages', 'data/languages');
        $files += $this->snapshotJsonDirectory($this->dataRoot . '/themes', 'data/themes');
        $files += $this->snapshotJsonDirectory($this->dataRoot . '/users', 'data/users');
        $files += $this->snapshotFileDirectory($this->publicUploadsRoot . '/logo', 'public/uploads/logo');
        $files += $this->snapshotExactFile($this->storageSecretKeyPath, 'storage/app/secretbox.key');

        if (!$this->uploadsAliasesPublicUploads()) {
            $files += $this->snapshotFileDirectory($this->uploadsRoot . '/logo', 'uploads/logo');
        }

        $files['data/settings.json'] = $this->encodeJson($this->buildResetSettingsPayload($settings));
        $files['data/modules.json'] = $this->readJsonFileContent(BASE_PATH . '/data/modules.json', []);
        $files['data/auth/login_attempts.json'] = $this->encodeJson([]);
        $files['data/site_branding_translations.json'] = $this->readJsonFileContent(BASE_PATH . '/data/site_branding_translations.json', [
            'source_locale' => $defaultLanguage,
            'updated_at' => date('Y-m-d H:i:s'),
            'translations' => [],
        ]);
        $files['data/site_routing.json'] = $this->encodeJson([
            'homepage' => [
                'mode' => 'native',
                'ref_type' => '',
                'ref_group' => '',
            ],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $files['data/promo_banner_translations.json'] = $this->encodeJson([
            'updated_at' => date('Y-m-d H:i:s'),
            'translations' => (object) [],
        ]);
        $files['data/core/menus/menus.json'] = $this->encodeJson($this->buildResetMenusPayload());
        $files['data/core/footer/footer.json'] = $this->encodeJson($this->buildResetFooterPayload($settings, $defaultLanguage));
        $files['data/core/media/media.json'] = $this->encodeJson([]);

        ksort($files);

        return $files;
    }

    /**
     * @return array<string, string>
     */
    private function buildFactoryResetBootstrapSnapshot(): array
    {
        return [
            'data/modules.json' => $this->encodeJson($this->buildFactoryResetModulesPayload()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFactoryResetModulesPayload(): array
    {
        $state = [];
        $roots = [
            BASE_PATH . '/app/Modules',
            BASE_PATH . '/app/Extensions',
        ];

        foreach ($roots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
                $name = basename($dir);
                if ($name === '') {
                    continue;
                }

                $manifest = $this->readJsonFile($dir . '/module.json');
                $enabled = (bool) ($manifest['enabled'] ?? true);
                if ((bool) ($manifest['required'] ?? false)) {
                    $enabled = true;
                }
                if ($name === 'Install') {
                    $enabled = true;
                }

                $item = [
                    'enabled' => $enabled,
                ];

                if ($name !== 'Install') {
                    $item['sidebar_visible'] = (bool) ($manifest['sidebar_visible'] ?? true);
                }

                $state[$name] = $item;
            }
        }

        ksort($state);

        return $state;
    }

    private function deleteFactoryResetResidualFiles(): void
    {
        foreach ([
            $this->dataRoot . '/installed.lock',
            BASE_PATH . '/resources/uploads/contact',
        ] as $path) {
            if (is_dir($path)) {
                $this->purgeDirectoryContents($path);
                continue;
            }

            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function buildResetSettingsPayload(array $settings): array
    {
        $settings['maintenance_mode'] = false;
        $settings['promo_banner_enabled'] = 0;
        $settings['promo_banner_text'] = '';
        $settings['promo_banner_cta_label'] = '';
        $settings['promo_banner_cta_url'] = '';
        $settings['promo_banner_position'] = 'above_topbar';
        $settings['promo_banner_min_height'] = 52;

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResetMenusPayload(): array
    {
        return [
            'main' => [
                'items' => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function buildResetFooterPayload(array $settings, string $defaultLanguage): array
    {
        $siteName = trim((string) ($settings['site_name'] ?? 'FlatCMS'));
        if ($siteName === '') {
            $siteName = 'FlatCMS';
        }
        $poweredByLabel = 'FlatCMS v' . flatcms_version('1.0.0');

        return [
            'enabled' => true,
            'source_locale' => $defaultLanguage,
            'translations' => [
                $defaultLanguage => [
                    'brand_text' => $siteName,
                    'copyright_text' => '© {year} {site_name}',
                    'powered_by_label' => $poweredByLabel,
                ],
            ],
            'brand_text' => $siteName,
            'copyright_text' => '© {year} {site_name}',
            'powered_by' => [
                'enabled' => true,
                'label' => $poweredByLabel,
                'url' => 'https://flat-cms.fr',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $content = @file_get_contents($path);
        if (!is_string($content) || !$this->isValidJson($content)) {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function readJsonFileContent(string $path, array $fallback): string
    {
        $payload = $this->readJsonFile($path);
        if ($payload === []) {
            $payload = $fallback;
        }

        return $this->encodeJson($payload);
    }

    /**
     * @param array<string, mixed>|list<mixed> $payload
     */
    private function encodeJson(array $payload): string
    {
        $content = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($content)) {
            throw new \RuntimeException('backups_archive_write_failed');
        }

        return $content;
    }

    private function purgeDirectoryContents(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $basename = (string) $item->getBasename();
            if ($basename === '.gitkeep') {
                continue;
            }

            $pathname = $item->getPathname();
            if ($item->isDir()) {
                @rmdir($pathname);
                continue;
            }

            @unlink($pathname);
        }
    }

    private function ensureDirectories(): void
    {
        foreach ([$this->backupRoot, $this->tmpRoot] as $path) {
            if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
                throw new \RuntimeException('backups_storage_unavailable');
            }
        }
    }

    private function assertZipAvailable(): void
    {
        if (!$this->zipAvailable()) {
            throw new \RuntimeException('backups_zip_missing');
        }
    }

    private function uploadErrorKey(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'backups_upload_too_large',
            UPLOAD_ERR_PARTIAL => 'backups_upload_partial',
            UPLOAD_ERR_NO_FILE => 'backups_upload_missing',
            default => 'backups_upload_failed',
        };
    }
}
