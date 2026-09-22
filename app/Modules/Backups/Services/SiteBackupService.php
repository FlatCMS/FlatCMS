<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Backups/Services/SiteBackupService.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Backups\Services;

use App\Core\CoreManifest;
use App\Core\FlatFile;
use App\Core\Security\SecretBox;
use App\Core\Storage\StoragePathGuard;
use App\Core\Storage\StreamFileTransaction;

final class SiteBackupService
{
    private const ARCHIVE_KIND = 'flatcms-site-backup';
    private const ARCHIVE_VERSION = 3;
    private const SUPPORTED_ARCHIVE_VERSIONS = [1, 2, 3];
    private const TMP_UPLOAD_PREFIX = 'site-upload-';
    private const BACKUP_PREFIX = 'flatcms-site-backup';
    private const PORTABLE_SECRETS_ENTRY = 'transport/site-secrets.json';
    private ?StreamFileTransaction $restoration = null;

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
    private string $keyRoot;
    private BackupSecretCipher $secretCipher;
    private SiteBackupBaselineService $baselineService;

    public function __construct(?SiteBackupBaselineService $baselineService = null)
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
        $this->keyRoot = rtrim($storageRoot, '/') . '/recovery/keys';
        $this->secretCipher = new BackupSecretCipher(
            BASE_PATH,
            $this->keyRoot,
            'site-backups',
            'backups_site'
        );
        $this->baselineService = $baselineService ?? new SiteBackupBaselineService();
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
        return $this->transaction()->synchronized(fn (): array => $this->createBackupUnlocked($context));
    }

    private function createBackupUnlocked(array $context): array
    {
        $this->assertZipAvailable();
        $this->ensureDirectories();

        $portability = $this->baselineService->inspect();
        if (empty($portability['portable'])) {
            throw new \RuntimeException('backups_site_portability_blocked');
        }

        $files = $this->snapshotArchiveFiles($portability);
        if ($files === []) {
            throw new \RuntimeException('backups_error_no_data');
        }

        $backupId = $this->buildBackupId();
        $filename = self::BACKUP_PREFIX . '-' . $backupId . '.zip';
        $path = $this->backupRoot . '/' . $filename;
        $partial = $path . '.partial';
        $keyPath = '';
        $secretKey = null;
        $manifest = $this->buildManifest($backupId, $files, $context, $portability);

        try {
            if ($this->containsSecretEntries($files)) {
                $secretKey = $this->secretCipher->generate();
                $keyPath = $this->secretCipher->persist('site-' . $backupId . '.key', $secretKey);
            }
            $this->writeArchive($partial, $files, $manifest, $secretKey, $keyPath !== '' ? $keyPath : null);
            if (!@rename($partial, $path)) {
                throw new \RuntimeException('backups_archive_write_failed');
            }
            @chmod($path, 0600);
        } catch (\Throwable $exception) {
            @unlink($partial);
            if ($keyPath !== '') {
                @unlink($keyPath);
            }
            throw $exception;
        }

        return $this->backupItemFromPath($path);
    }

    /**
     * @param array<string, string> $context
     * @param callable():bool|null $accept
     * @return array<string, mixed>
     */
    public function restoreStoredBackup(string $filename, array $context = [], ?callable $accept = null): array
    {
        $path = $this->resolveStoredBackupPath($filename);
        if ($path === null) {
            throw new \RuntimeException('backups_archive_not_found');
        }

        return $this->restoreArchive($path, $context, $accept, $this->resolveStoredKeyPath($filename));
    }

    /**
     * @param array<string, mixed>|null $upload
     * @param array<string, string> $context
     * @param callable():bool|null $accept
     * @return array<string, mixed>
     */
    public function restoreUploadedBackup(
        ?array $upload,
        array $context = [],
        ?callable $accept = null,
        ?array $keyUpload = null
    ): array
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

        $targetKeyPath = null;
        try {
            if (is_array($keyUpload) && (int) ($keyUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $targetKeyPath = $this->moveUploadedKey($keyUpload);
            }
            return $this->restoreArchive($targetPath, $context, $accept, $targetKeyPath);
        } finally {
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }
            if (is_string($targetKeyPath) && is_file($targetKeyPath)) {
                @unlink($targetKeyPath);
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

    public function resolveStoredKeyPath(string $filename): ?string
    {
        $path = $this->resolveStoredBackupPath($filename);
        if ($path === null) {
            return null;
        }

        $manifest = $this->readManifestFromArchive($path);
        if ((int) ($manifest['version'] ?? 1) < 2 || empty($manifest['secret_key_required'])) {
            return null;
        }
        $backupId = trim((string) ($manifest['backup_id'] ?? ''));
        if (preg_match('/^[0-9]{14}-[a-f0-9]{12}$/D', $backupId) !== 1) {
            return null;
        }
        $keyPath = $this->keyRoot . '/site-' . $backupId . '.key';

        return is_file($keyPath) && !is_link($keyPath) ? $keyPath : null;
    }

    public function deleteStoredBackup(string $filename): void
    {
        $path = $this->resolveStoredBackupPath($filename);
        if ($path === null) {
            throw new \RuntimeException('backups_archive_not_found');
        }

        $keyPath = $this->resolveStoredKeyPath($filename);
        if (!@unlink($path)) {
            throw new \RuntimeException('backups_delete_failed');
        }
        if ($keyPath !== null && is_file($keyPath)) {
            @unlink($keyPath);
        }
    }

    /**
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    public function resetSiteContent(array $context = []): array
    {
        return $this->transaction()->synchronized(function (): array {
            $resetFiles = $this->buildResetSnapshot();
            $this->mirrorArchiveFiles($resetFiles);
            $this->clearRuntimeCaches();
            return ['reset_files_count' => count($resetFiles)];
        });
    }

    /**
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    public function factoryResetSite(array $context = [], bool $deleteSensitive = false): array
    {
        return $this->transaction()->synchronized(function () use ($deleteSensitive): array {
            $bootstrapFiles = $this->buildFactoryResetBootstrapSnapshot();
            if (!$deleteSensitive) {
                $bootstrapFiles += $this->snapshotExactFile(
                    $this->storageSecretKeyPath,
                    'storage/app/secretbox.key'
                );
            }

            $this->mirrorArchiveFiles(
                $bootstrapFiles,
                null,
                $this->factoryResetResidualFiles($deleteSensitive)
            );
            $this->clearRuntimeCaches();

            return [
                'bootstrap_files_count' => count($bootstrapFiles),
                'sensitive_data_deleted' => $deleteSensitive,
            ];
        });
    }

    public function recoverRestoration(): void
    {
        $this->transaction()->recover();
        $this->clearRuntimeCaches();
    }

    private function transaction(): StreamFileTransaction
    {
        return $this->restoration ??= new StreamFileTransaction(BASE_PATH, 'site-restoration');
    }

    /**
     * @param array<string, string> $context
     * @param callable():bool|null $accept
     * @return array<string, mixed>
     */
    private function restoreArchive(
        string $archivePath,
        array $context = [],
        ?callable $accept = null,
        ?string $keyPath = null
    ): array
    {
        $this->assertZipAvailable();
        $this->ensureDirectories();

        return $this->transaction()->synchronized(function () use ($archivePath, $accept, $keyPath): array {
            $zip = new \ZipArchive();
            if ($zip->open($archivePath) !== true) { throw new \RuntimeException('backups_archive_open_failed'); }
            try {
                $payload = $this->readArchivePayload($archivePath, $zip, $keyPath);
                $archiveVersion = max(1, (int) ($payload['manifest']['version'] ?? 1));
                $internalWrites = $archiveVersion >= 3
                    ? $this->preparePortableInternalWrites($payload['files'])
                    : [];
                $payload['files'] = $this->adaptRestoredFilesToCurrentInstallation($payload['files']);
                $portableRoots = is_array($payload['portable_roots'] ?? null)
                    ? $payload['portable_roots']
                    : [];
                $this->mirrorArchiveFiles(
                    $payload['files'],
                    $zip,
                    [],
                    $accept,
                    $archiveVersion,
                    $portableRoots,
                    $internalWrites
                );
                if ($archiveVersion >= 3) {
                    (new \App\Core\RuntimeAssetPublisher())->publishAll();
                }
                $this->clearRuntimeCaches();
                return [
                    'restored_files_count' => count($payload['files']) + count($internalWrites),
                    'manifest' => $payload['manifest'],
                ];
            } finally { $zip->close(); }
        });
    }

    /**
     * Consume the encrypted logical transport and convert it to target-specific writes.
     *
     * @param array<string,string|array<string,mixed>> $files
     * @return array<string,string>
     */
    private function preparePortableInternalWrites(array &$files): array
    {
        if (!array_key_exists(self::PORTABLE_SECRETS_ENTRY, $files)) {
            return [];
        }

        $raw = $files[self::PORTABLE_SECRETS_ENTRY];
        unset($files[self::PORTABLE_SECRETS_ENTRY]);
        if (!is_string($raw)) {
            throw new \RuntimeException('backups_site_portable_settings_invalid');
        }

        try {
            $transport = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('backups_site_portable_settings_invalid', 0, $exception);
        }
        if (!is_array($transport)
            || (int) ($transport['schema'] ?? 0) !== 1
            || !is_array($transport['integrations'] ?? null)
            || !is_array($transport['settings_secrets'] ?? null)
            || !is_array($transport['licenses'] ?? null)
            || array_diff(array_keys($transport), ['schema', 'integrations', 'settings_secrets', 'licenses']) !== []) {
            throw new \RuntimeException('backups_site_portable_settings_invalid');
        }

        $writes = [];
        $integrations = (array) $transport['integrations'];
        if ($integrations !== []) {
            $envPath = BASE_PATH . '/.env.local';
            $existingEnv = '';
            if (is_file($envPath)) {
                $existingEnv = @file_get_contents($envPath);
                if (!is_string($existingEnv)) {
                    throw new \RuntimeException('backups_restore_write_failed');
                }
            }
            $writes['.env.local'] = (new \App\Modules\Settings\Services\EnvConfigManager())
                ->buildPortableEnvLocalContent($integrations, $existingEnv);
        }

        $settingsSecrets = (array) $transport['settings_secrets'];
        if (array_diff(array_keys($settingsSecrets), ['mail_smtp_password']) !== []) {
            throw new \RuntimeException('backups_site_portable_settings_invalid');
        }
        if (array_key_exists('mail_smtp_password', $settingsSecrets)) {
            $plainPassword = $settingsSecrets['mail_smtp_password'];
            if (!is_string($plainPassword) || !isset($files['data/settings.json'])
                || !is_string($files['data/settings.json'])) {
                throw new \RuntimeException('backups_site_portable_settings_invalid');
            }

            $settings = json_decode($files['data/settings.json'], true);
            if (!is_array($settings)) {
                throw new \RuntimeException('backups_archive_invalid_json');
            }

            $secretBox = new SecretBox();
            $stored = $plainPassword === '' ? '' : $secretBox->encrypt($plainPassword);
            if ($plainPassword !== '' && !$secretBox->isEncrypted($stored)) {
                throw new \RuntimeException('backups_site_portable_settings_invalid');
            }
            $settings['mail_smtp_password'] = $stored;
            $files['data/settings.json'] = $this->encodeJson($settings);
        }

        $licenses = (array) $transport['licenses'];
        if ($licenses !== []) {
            $writes['resources/licenses/licenses.json'] =
                (new \App\Modules\Auth\Services\LicenseVaultService())
                    ->buildPortableVaultContent($licenses);
        }

        return $writes;
    }

    /**
     * @param array<string, string|array<string,mixed>> $files
     * @return array<string, string|array<string,mixed>>
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
     * Text stays readable; media payloads are stream descriptors, never an in-memory site image.
     * @param array<string, string|array<string,mixed>> $files
     * @param list<string> $extraDeletes Private files owned by factory reset only.
     * @param callable():bool|null $accept
     */
    private function mirrorArchiveFiles(
        array $files,
        ?\ZipArchive $zip = null,
        array $extraDeletes = [],
        ?callable $accept = null,
        int $scopeVersion = 2,
        array $portableRoots = [],
        array $internalWrites = []
    ): void
    {
        $this->transaction()->synchronized(function () use (
            $files,
            $zip,
            $extraDeletes,
            $accept,
            $scopeVersion,
            $portableRoots,
            $internalWrites
        ): void {
            $operations = [];
            $pruneRoots = $scopeVersion >= 3
                ? array_values(array_unique(array_merge($portableRoots, $this->currentPortableRoots())))
                : [];
            $existing = $this->existingArchivePaths($scopeVersion, $pruneRoots);
            foreach ($files as $relative => $content) {
                $this->absolutePathForRelative($relative, $scopeVersion, $portableRoots);
                $operation = ['path' => $relative];
                if ($this->isSecretEntry($relative)) { $operation['mode'] = 0600; }
                if (is_string($content)) {
                    $operation += ['sha256' => hash('sha256', $content), 'size' => strlen($content),
                        'open' => static function () use ($content) {
                            $stream = fopen('php://temp', 'w+b');
                            if (!is_resource($stream)) { throw new \RuntimeException('backups_restore_write_failed'); }
                            if (fwrite($stream, $content) !== strlen($content) || !rewind($stream)) {
                                fclose($stream);
                                throw new \RuntimeException('backups_restore_write_failed');
                            }
                            return $stream;
                        }];
                } else {
                    $operation += ['sha256' => $content['sha256'], 'size' => $content['size'],
                        'open' => isset($content['source'])
                            ? static fn () => fopen((new StoragePathGuard(BASE_PATH))->resolve($content['source']), 'rb')
                            : static fn () => $zip?->getStreamIndex($content['zip_index'])];
                    if (isset($content['mode'])) {
                        $operation['mode'] = (int) $content['mode'];
                    }
                }
                $operations[] = $operation;
            }
            foreach (array_diff($existing, array_keys($files)) as $relative) {
                $this->absolutePathForRelative($relative, $scopeVersion, $pruneRoots);
                $operations[] = ['path' => $relative, 'open' => null];
            }
            foreach ($extraDeletes as $relative) {
                $allowedFactoryResetDelete = $relative === 'data/installed.lock'
                    || $relative === '.env.local'
                    || str_starts_with($relative, 'resources/uploads/contact/')
                    || str_starts_with($relative, 'resources/licenses/');
                if (!$allowedFactoryResetDelete) {
                    throw new \RuntimeException('backups_restore_write_failed');
                }
                $operations[] = ['path' => $relative, 'open' => null];
            }
            foreach ($internalWrites as $relative => $content) {
                if (!in_array($relative, ['.env.local', 'resources/licenses/licenses.json'], true)
                    || !is_string($content)) {
                    throw new \RuntimeException('backups_restore_write_failed');
                }
                (new StoragePathGuard(BASE_PATH))->resolve($relative);
                $operations[] = [
                    'path' => $relative,
                    'sha256' => hash('sha256', $content),
                    'size' => strlen($content),
                    'mode' => 0600,
                    'open' => static function () use ($content) {
                        $stream = fopen('php://temp', 'w+b');
                        if (!is_resource($stream)
                            || fwrite($stream, $content) !== strlen($content)
                            || !rewind($stream)) {
                            if (is_resource($stream)) {
                                fclose($stream);
                            }
                            throw new \RuntimeException('backups_restore_write_failed');
                        }
                        return $stream;
                    },
                ];
            }
            $this->transaction()->commit($operations, $accept);

            if ($scopeVersion >= 3) {
                $obsoleteRoots = array_values(array_diff($pruneRoots, $portableRoots));
                $this->pruneEmptyPortableRoots($obsoleteRoots);
            }
        });
    }

    /** @param list<string> $roots */
    private function pruneEmptyPortableRoots(array $roots): void
    {
        $guard = new StoragePathGuard(BASE_PATH);

        foreach ($roots as $relativeRoot) {
            $relativeRoot = trim((string) $relativeRoot, '/');
            if ($relativeRoot === '') {
                continue;
            }

            $absoluteRoot = $guard->resolve($relativeRoot);
            if (!is_dir($absoluteRoot) || is_link($absoluteRoot)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absoluteRoot, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if (!$item->isDir() || $item->isLink()) {
                    continue;
                }
                $path = $item->getPathname();
                $entries = scandir($path);
                if (is_array($entries) && count($entries) === 2) {
                    @rmdir($path);
                }
            }

            $entries = scandir($absoluteRoot);
            if (is_array($entries) && count($entries) === 2) {
                @rmdir($absoluteRoot);
            }
        }
    }

    /**
     * @return array{manifest: array<string, mixed>, files: array<string, string|array<string,mixed>>}
     */
    private function readArchivePayload(
        string $archivePath,
        ?\ZipArchive $openedZip = null,
        ?string $keyPath = null
    ): array
    {
        if (!is_file($archivePath)) {
            throw new \RuntimeException('backups_archive_not_found');
        }

        $zip = $openedZip ?? new \ZipArchive();
        if ($openedZip === null && $zip->open($archivePath) !== true) {
            throw new \RuntimeException('backups_archive_open_failed');
        }

        try {
            $rawManifest = $zip->getFromName('manifest.json');
            $manifest = [];
            if (is_string($rawManifest)) {
                $manifest = json_decode($rawManifest, true);
                if (!is_array($manifest)) {
                    throw new \RuntimeException('backups_archive_invalid_manifest');
                }
            }

            $kind = trim((string) ($manifest['kind'] ?? ''));
            $version = (int) ($manifest['version'] ?? 0);
            if ($kind === self::ARCHIVE_KIND && in_array($version, [2, 3], true)) {
                return $this->readVersionedPayload($zip, $manifest, $keyPath);
            }
            if (($kind !== '' && $kind !== self::ARCHIVE_KIND)
                || ($version !== 0 && !in_array($version, self::SUPPORTED_ARCHIVE_VERSIONS, true))) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }

            return $this->readLegacyPayload($zip, $manifest);
        } finally {
            if ($openedZip === null) { $zip->close(); }
        }
    }

    /**
     * @param array<string,mixed> $manifest
     * @return array{manifest:array<string,mixed>,files:array<string,string|array<string,mixed>>}
     */
    private function readVersionedPayload(\ZipArchive $zip, array $manifest, ?string $keyPath): array
    {
        $backupId = trim((string) ($manifest['backup_id'] ?? ''));
        $inventory = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
        if (preg_match('/^[0-9]{14}-[a-f0-9]{12}$/D', $backupId) !== 1 || $inventory === []) {
            throw new \RuntimeException('backups_archive_invalid_manifest');
        }

        $version = (int) ($manifest['version'] ?? 0);
        $portableRoots = $version >= 3
            ? $this->validatePortableManifestScope($manifest, $inventory)
            : [];

        $secretRequired = !empty($manifest['secret_key_required']);
        $secretKey = null;
        if ($secretRequired) {
            if ($keyPath === null || !is_file($keyPath)) {
                throw new \RuntimeException('backups_site_key_invalid');
            }
            $secretKey = $this->secretCipher->read($keyPath);
        }

        $files = [];
        $declaredEntries = ['manifest.json' => true];
        $secretCount = 0;
        foreach ($inventory as $relative => $meta) {
            if (!is_string($relative) || !is_array($meta)) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $normalized = $this->normalizeArchiveEntry($relative);
            if ($normalized !== $relative
                || !$this->isAllowedArchiveEntryForVersion($normalized, $version, $portableRoots)
                || $normalized === 'manifest.json'
                || isset($files[$normalized])) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $secret = !empty($meta['secret']);
            if ($version >= 3 && $secret !== $this->isSecretEntry($normalized)) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $entry = trim((string) ($meta['entry'] ?? ''));
            $expectedEntry = $secret
                ? 'secrets/' . $this->secretCipher->entryName($normalized) . '.json'
                : $normalized;
            $sha256 = strtolower(trim((string) ($meta['sha256'] ?? '')));
            $size = (int) ($meta['size_bytes'] ?? -1);
            $mode = (int) ($meta['mode'] ?? 0644);
            if ($entry !== $expectedEntry || isset($declaredEntries[$entry])
                || preg_match('/^[a-f0-9]{64}$/D', $sha256) !== 1 || $size < 0
                || $mode < 0 || $mode > 0777) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $index = $zip->locateName($entry);
            if (!is_int($index)) {
                throw new \RuntimeException('backups_archive_invalid');
            }
            $declaredEntries[$entry] = true;

            if ($secret) {
                $secretCount++;
                if (!$secretRequired || !is_string($secretKey)) {
                    throw new \RuntimeException('backups_archive_invalid_manifest');
                }
                try {
                    $payload = json_decode((string) $zip->getFromIndex($index), true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    throw new \RuntimeException('backups_site_secret_invalid', 0, $exception);
                }
                if (!is_array($payload)) {
                    throw new \RuntimeException('backups_site_secret_invalid');
                }
                $plain = $this->secretCipher->decrypt($payload, $secretKey);
                if (strlen($plain) !== $size || !hash_equals($sha256, hash('sha256', $plain))) {
                    throw new \RuntimeException('backups_site_secret_decrypt_failed');
                }
                $files[$normalized] = $plain;
                continue;
            }

            if ($this->isMediaEntry($normalized)
                || ($version >= 3 && $this->isPortableRootEntry($normalized, $portableRoots))) {
                $metadata = $this->streamMetadata($zip->getStreamIndex($index));
                if ($metadata['size'] !== $size || !hash_equals($sha256, $metadata['sha256'])) {
                    throw new \RuntimeException('backups_archive_invalid');
                }
                $files[$normalized] = $metadata + ['zip_index' => $index, 'mode' => $mode];
                continue;
            }

            $content = $zip->getFromIndex($index);
            if (!is_string($content) || strlen($content) !== $size || !hash_equals($sha256, hash('sha256', $content))) {
                throw new \RuntimeException('backups_archive_invalid');
            }
            if ($this->isDataJsonEntry($normalized) && !$this->isValidJson($content)) {
                throw new \RuntimeException('backups_archive_invalid_json');
            }
            $files[$normalized] = $content;
        }

        if (($secretCount > 0) !== $secretRequired) {
            throw new \RuntimeException('backups_archive_invalid_manifest');
        }
        if ((int) ($manifest['total_files_count'] ?? -1) !== count($files)
            || (int) ($manifest['json_files_count'] ?? -1) !== $this->countDataFilesByExtension($files, 'json')
            || (int) ($manifest['html_files_count'] ?? -1) !== $this->countDataFilesByExtension($files, 'html')
            || (int) ($manifest['media_files_count'] ?? -1) !== $this->countMediaFiles($files)) {
            throw new \RuntimeException('backups_archive_invalid_manifest');
        }
        $this->assertNoUndeclaredArchiveEntries($zip, $declaredEntries);

        return ['manifest' => $manifest, 'files' => $files, 'portable_roots' => $portableRoots];
    }

    /**
     * @param array<string,mixed> $manifest
     * @return array{manifest:array<string,mixed>,files:array<string,string|array<string,mixed>>}
     */
    private function readLegacyPayload(\ZipArchive $zip, array $manifest): array
    {
        $files = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);
            if (!is_string($entryName) || $entryName === '') {
                continue;
            }
            $normalized = $this->normalizeRestorableArchiveEntry($entryName);
            if ($normalized === '' || str_ends_with($normalized, '/') || $normalized === 'manifest.json') {
                continue;
            }
            if (!$this->isAllowedArchiveEntryForVersion($normalized, 1, []) || isset($files[$normalized])) {
                throw new \RuntimeException('backups_archive_invalid');
            }
            if ($this->isMediaEntry($normalized)) {
                $metadata = $this->streamMetadata($zip->getStreamIndex($index));
                $stat = $zip->statIndex($index);
                if (!is_array($stat) || $metadata['size'] !== $stat['size']) {
                    throw new \RuntimeException('backups_archive_invalid');
                }
                $files[$normalized] = $metadata + ['zip_index' => $index];
                continue;
            }
            $content = $zip->getFromIndex($index);
            if (!is_string($content)) {
                throw new \RuntimeException('backups_archive_invalid');
            }
            if ($this->isDataJsonEntry($normalized) && !$this->isValidJson($content)) {
                throw new \RuntimeException('backups_archive_invalid_json');
            }
            $files[$normalized] = $content;
        }
        if ($files === []) {
            throw new \RuntimeException('backups_archive_empty');
        }

        return ['manifest' => $manifest, 'files' => $files];
    }

    /** @param array<string,bool> $declared */
    private function assertNoUndeclaredArchiveEntries(\ZipArchive $zip, array $declared): void
    {
        $seen = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->getNameIndex($index);
            if (!is_string($entry) || $entry === '' || str_ends_with($entry, '/')) {
                continue;
            }
            $normalized = $this->normalizeArchiveEntry($entry);
            if ($normalized !== $entry || isset($seen[$normalized]) || !isset($declared[$normalized])) {
                throw new \RuntimeException('backups_archive_invalid');
            }
            $seen[$normalized] = true;
        }
    }

    /** @param resource|false $stream @return array{sha256:string,size:int} */
    private function streamMetadata(mixed $stream): array
    {
        if (!is_resource($stream)) { throw new \RuntimeException('backups_archive_invalid'); }
        try {
            $hash = hash_init('sha256');
            $size = hash_update_stream($hash, $stream);
            if (!is_int($size) || !feof($stream)) { throw new \RuntimeException('backups_archive_invalid'); }
            return ['sha256' => hash_final($hash), 'size' => $size];
        } finally { fclose($stream); }
    }

    /**
     * List the prune scope without reading old payloads or treating corrupt JSON as missing.
     * @param list<string> $portableRoots
     * @return list<string>
     */
    private function existingArchivePaths(int $scopeVersion = 2, array $portableRoots = []): array
    {
        $guard = new StoragePathGuard(BASE_PATH);
        $roots = ['data', 'public/uploads', 'storage/uploads/avatars'];
        if (!$this->uploadsAliasesPublicUploads()) {
            $roots[] = 'uploads';
        }
        if ($scopeVersion >= 3) {
            $roots[] = 'resources/uploads/contact';
            foreach ($portableRoots as $portableRoot) {
                $portableRoot = trim((string) $portableRoot, '/');
                if ($portableRoot !== '') {
                    $roots[] = $portableRoot;
                }
            }
        }

        $files = [];
        foreach (array_values(array_unique($roots)) as $relativeRoot) {
            $root = $guard->resolve($relativeRoot);
            if (!is_dir($root)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $item) {
                $guard->resolve($item->getPathname());
                if (!$item->isFile() || $item->isLink()
                    || in_array($item->getBasename(), ['.gitkeep', '.DS_Store'], true)) {
                    continue;
                }
                $relative = $this->buildArchiveRelativePath($root, $relativeRoot, $item->getPathname());
                if ($this->isAllowedArchiveEntryForVersion($relative, $scopeVersion, $portableRoots)) {
                    $files[] = $relative;
                }
            }
        }
        if ($scopeVersion <= 2) {
            $key = $guard->resolve($this->storageSecretKeyPath);
            if (is_file($key)) {
                $files[] = 'storage/app/secretbox.key';
            }
        }
        sort($files);
        return array_values(array_unique($files));
    }

    /** @return list<string> */
    private function currentPortableRoots(): array
    {
        $inspection = $this->baselineService->inspect();
        if (empty($inspection['portable'])) {
            throw new \RuntimeException('backups_site_target_portability_blocked');
        }

        $roots = [];
        foreach (['modules', 'extensions', 'plugins', 'themes'] as $catalog) {
            foreach ((array) ($inspection['added'][$catalog] ?? []) as $meta) {
                if (!is_array($meta)) {
                    continue;
                }
                $path = trim((string) ($meta['path'] ?? ''), '/');
                if ($path !== '') {
                    $roots[] = $path;
                }
            }
        }
        sort($roots);
        return array_values(array_unique($roots));
    }

    /**
     * @param array<string, string|array<string,mixed>> $files
     * @param array<string, mixed> $manifest
     */
    private function writeArchive(
        string $archivePath,
        array $files,
        array $manifest,
        ?string $secretKey,
        ?string $keyPath
    ): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('backups_archive_write_failed');
        }

        try {
            foreach ($files as $relativePath => $content) {
                $meta = $manifest['files'][$relativePath] ?? null;
                if (!is_array($meta)) {
                    throw new \RuntimeException('backups_archive_write_failed');
                }
                $entry = (string) ($meta['entry'] ?? '');
                if (!empty($meta['secret'])) {
                    if (!is_string($content) || !is_string($secretKey)) {
                        throw new \RuntimeException('backups_site_secret_encrypt_failed');
                    }
                    $encoded = json_encode(
                        $this->secretCipher->encrypt($content, $secretKey),
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
                    );
                    $added = $zip->addFromString($entry, $encoded);
                } else {
                    $added = is_string($content) ? $zip->addFromString($entry, $content)
                        : $zip->addFile((new StoragePathGuard(BASE_PATH))->resolve($content['source']), $entry);
                }
                if (!$added) {
                    throw new \RuntimeException('backups_archive_write_failed');
                }
            }
            $manifestContent = json_encode(
                $manifest,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            if (!$zip->addFromString('manifest.json', $manifestContent)) {
                throw new \RuntimeException('backups_archive_write_failed');
            }
            if (!$zip->close()) { throw new \RuntimeException('backups_archive_write_failed'); }
            $this->readArchivePayload($archivePath, null, $keyPath);
        } catch (\Throwable $exception) {
            try { $zip->close(); } catch (\Throwable) {}
            @unlink($archivePath);
            throw $exception;
        }
    }

    /**
     * @param array<string, string|array<string,mixed>> $files
     * @param array<string, string> $context
     * @return array<string, mixed>
     */
    private function buildManifest(
        string $backupId,
        array $files,
        array $context = [],
        array $portability = []
    ): array {
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

        $components = $this->portableManifestComponents($portability);
        $themes = $this->portableManifestThemes($portability);

        return [
            'kind' => self::ARCHIVE_KIND,
            'version' => self::ARCHIVE_VERSION,
            'backup_id' => $backupId,
            'created_at' => $createdAt,
            'created_unix' => time(),
            'reason' => $reason,
            'flatcms_version' => CoreManifest::version('1.0.0'),
            'baseline_schema' => 1,
            'site_name' => $siteName,
            'default_language' => $defaultLanguage,
            'source_url' => $sourceUrl,
            'json_files_count' => $this->countDataFilesByExtension($files, 'json'),
            'html_files_count' => $this->countDataFilesByExtension($files, 'html'),
            'media_files_count' => $this->countMediaFiles($files),
            'total_files_count' => count($files),
            'created_by' => $createdBy,
            'created_by_email' => $createdByEmail,
            'scope' => 'portable-site-data-media-private-components-themes-licenses-secrets',
            'includes_media' => true,
            'secret_key_required' => $this->containsSecretEntries($files),
            'components' => $components,
            'themes' => $themes,
            'families' => $this->buildPortableFamilySummary($files, $components, $themes),
            'files' => $this->buildFileInventory($files),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function portableManifestComponents(array $portability): array
    {
        $result = [];
        foreach (['modules' => 'module', 'extensions' => 'extension', 'plugins' => 'plugin'] as $catalog => $type) {
            foreach ((array) ($portability['added'][$catalog] ?? []) as $meta) {
                if (!is_array($meta)) {
                    continue;
                }
                $path = trim((string) ($meta['path'] ?? ''));
                if ($path === '') {
                    continue;
                }
                $result[] = [
                    'catalog' => $catalog,
                    'type' => $type,
                    'key' => basename($path),
                    'path' => $path,
                    'status' => 'added',
                    'sha256' => (string) ($meta['sha256'] ?? ''),
                    'files_count' => (int) ($meta['files_count'] ?? 0),
                    'size_bytes' => (int) ($meta['size_bytes'] ?? 0),
                    'identity' => is_array($meta['identity'] ?? null) ? $meta['identity'] : [],
                ];
            }
        }
        return $result;
    }

    /** @return list<array<string,mixed>> */
    private function portableManifestThemes(array $portability): array
    {
        $result = [];
        foreach ((array) ($portability['added']['themes'] ?? []) as $meta) {
            if (!is_array($meta)) {
                continue;
            }
            $path = trim((string) ($meta['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $relative = preg_replace('#^themes/#', '', $path) ?? '';
            $parts = explode('/', $relative, 2);
            $result[] = [
                'type' => 'theme',
                'theme_type' => (string) ($parts[0] ?? ''),
                'key' => (string) ($parts[1] ?? basename($path)),
                'path' => $path,
                'status' => 'added',
                'sha256' => (string) ($meta['sha256'] ?? ''),
                'files_count' => (int) ($meta['files_count'] ?? 0),
                'size_bytes' => (int) ($meta['size_bytes'] ?? 0),
                'identity' => is_array($meta['identity'] ?? null) ? $meta['identity'] : [],
            ];
        }
        return $result;
    }

    /** @param list<array<string,mixed>> $components @param list<array<string,mixed>> $themes */
    private function buildPortableFamilySummary(array $files, array $components, array $themes): array
    {
        $licenses = 0;
        $secrets = 0;
        $private = 0;
        foreach ($files as $relative => $content) {
            if (str_starts_with($relative, 'resources/uploads/contact/')) {
                $private++;
            }
            if ($this->isSecretEntry($relative)) {
                $secrets++;
            }
            if ($relative === self::PORTABLE_SECRETS_ENTRY && is_string($content)) {
                $transport = json_decode($content, true);
                if (is_array($transport) && is_array($transport['licenses'] ?? null)) {
                    $licenses = count($transport['licenses']);
                }
            }
        }

        return [
            'data_files' => $this->countDataFilesByExtension($files, 'json')
                + $this->countDataFilesByExtension($files, 'html'),
            'media_files' => $this->countMediaFiles($files),
            'private_files' => $private,
            'component_count' => count($components),
            'theme_count' => count($themes),
            'license_files' => $licenses,
            'secret_files' => $secrets,
        ];
    }

    /**
     * @param array<string,string|array<string,mixed>> $files
     * @return array<string,array{sha256:string,size_bytes:int,mode:int,secret:bool,entry:string}>
     */
    private function buildFileInventory(array $files): array
    {
        $inventory = [];
        foreach ($files as $relative => $content) {
            $secret = $this->isSecretEntry($relative);
            $sha256 = is_string($content) ? hash('sha256', $content) : (string) ($content['sha256'] ?? '');
            $size = is_string($content) ? strlen($content) : (int) ($content['size'] ?? -1);
            $mode = $secret ? 0600 : 0644;
            if (!is_string($content) && isset($content['source'])) {
                $permissions = @fileperms((string) $content['source']);
                if (is_int($permissions)) {
                    $mode = $permissions & 0777;
                }
            }
            if (preg_match('/^[a-f0-9]{64}$/D', $sha256) !== 1 || $size < 0) {
                throw new \RuntimeException('backups_archive_write_failed');
            }
            $inventory[$relative] = [
                'sha256' => $sha256,
                'size_bytes' => $size,
                'mode' => $mode,
                'secret' => $secret,
                'entry' => $secret
                    ? 'secrets/' . $this->secretCipher->entryName($relative) . '.json'
                    : $relative,
            ];
        }

        return $inventory;
    }

    /**
     * @return array<string, string|array<string,mixed>>
     */
    private function snapshotArchiveFiles(array $portability = []): array
    {
        $files = [];
        $files += $this->snapshotJsonDirectory($this->dataRoot, 'data');
        $files += $this->snapshotFileDirectory($this->publicUploadsRoot, 'public/uploads', [
            'cache/runtime-css/',
        ]);
        $files += $this->snapshotFileDirectory($this->storageAvatarsRoot, 'storage/uploads/avatars');
        $files += $this->snapshotFileDirectory(
            BASE_PATH . '/resources/uploads/contact',
            'resources/uploads/contact'
        );

        $this->appendPortableSecretsTransport($files, $portability);

        if (!$this->uploadsAliasesPublicUploads()) {
            $files += $this->snapshotFileDirectory($this->uploadsRoot, 'uploads');
        }

        $developmentExclusions = ['.git/', '.svn/', '.idea/', '.vscode/'];
        foreach (['modules', 'extensions', 'plugins', 'themes'] as $catalog) {
            foreach ((array) ($portability['added'][$catalog] ?? []) as $meta) {
                if (!is_array($meta)) {
                    continue;
                }
                $relative = trim((string) ($meta['path'] ?? ''), '/');
                if ($relative === '') {
                    continue;
                }
                $files += $this->snapshotFileDirectory(
                    BASE_PATH . '/' . $relative,
                    $relative,
                    $developmentExclusions
                );
            }
        }

        ksort($files);

        return $files;
    }

    /**
     * @param array<string,string|array<string,mixed>> $files
     * @param array<string,mixed> $portability
     */
    private function appendPortableSecretsTransport(array &$files, array $portability): void
    {
        $integrations = (new \App\Modules\Settings\Services\EnvConfigManager())
            ->exportPortableValues();

        $settingsSecrets = [];
        if (isset($files['data/settings.json']) && is_string($files['data/settings.json'])) {
            $settings = json_decode($files['data/settings.json'], true);
            if (!is_array($settings)) {
                throw new \RuntimeException('backups_archive_invalid_json');
            }

            $storedSmtpPassword = trim((string) ($settings['mail_smtp_password'] ?? ''));
            if ($storedSmtpPassword !== '') {
                $plainSmtpPassword = (new SecretBox())->decrypt($storedSmtpPassword);
                if ($plainSmtpPassword === '') {
                    throw new \RuntimeException('backups_site_portable_settings_invalid');
                }
                $settingsSecrets['mail_smtp_password'] = $plainSmtpPassword;
                $settings['mail_smtp_password'] = '';
                $files['data/settings.json'] = $this->encodeJson($settings);
            }
        }

        $licenseCandidates = [];
        foreach (['modules', 'extensions', 'plugins', 'themes'] as $catalog) {
            foreach ((array) ($portability['added'][$catalog] ?? []) as $meta) {
                if (!is_array($meta)) {
                    continue;
                }
                $path = trim((string) ($meta['path'] ?? ''), '/');
                if ($path !== '') {
                    $licenseCandidates[] = basename($path);
                }
                $identity = is_array($meta['identity'] ?? null) ? $meta['identity'] : [];
                foreach (['name', 'slug'] as $field) {
                    $candidate = trim((string) ($identity[$field] ?? ''));
                    if ($candidate !== '') {
                        $licenseCandidates[] = $candidate;
                    }
                }
            }
        }
        $licenseCandidates = array_values(array_unique($licenseCandidates));

        $licenses = $licenseCandidates === []
            ? []
            : (new \App\Modules\Auth\Services\LicenseVaultService())
                ->exportPortableLicenses($licenseCandidates);

        if ($integrations === [] && $settingsSecrets === [] && $licenses === []) {
            return;
        }

        $transport = [
            'schema' => 1,
            'integrations' => $integrations,
            'settings_secrets' => $settingsSecrets,
            'licenses' => $licenses,
        ];
        $encoded = json_encode(
            $transport,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        $files[self::PORTABLE_SECRETS_ENTRY] = $encoded . PHP_EOL;
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
        $keyRequired = (int) ($manifest['version'] ?? 1) >= 2 && !empty($manifest['secret_key_required']);
        $keyPath = $keyRequired ? $this->resolveStoredKeyPath($filename) : null;

        return [
            'backup_type' => 'site',
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
            'html_files_count' => (int) ($manifest['html_files_count'] ?? 0),
            'media_files_count' => (int) ($manifest['media_files_count'] ?? $this->countArchiveMediaFiles($path)),
            'total_files_count' => (int) ($manifest['total_files_count'] ?? ($this->countArchiveJsonFiles($path) + $this->countArchiveMediaFiles($path))),
            'created_by' => trim((string) ($manifest['created_by'] ?? '')),
            'created_by_email' => trim((string) ($manifest['created_by_email'] ?? '')),
            'reason' => $reason,
            'is_rollback' => $reason === 'pre_restore',
            'key_required' => $keyRequired,
            'key_available' => !$keyRequired || $keyPath !== null,
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

    private function buildBackupId(): string
    {
        return gmdate('YmdHis') . '-' . bin2hex(random_bytes(6));
    }

    /** @param list<string> $portableRoots */
    private function absolutePathForRelative(
        string $relativePath,
        int $scopeVersion = 2,
        array $portableRoots = []
    ): string {
        $normalized = $this->normalizeArchiveEntry($relativePath);
        if (!$this->isAllowedArchiveEntryForVersion($normalized, $scopeVersion, $portableRoots)
            || $normalized === 'manifest.json') {
            throw new \RuntimeException('backups_restore_write_failed');
        }

        return (new StoragePathGuard(BASE_PATH))->resolve($normalized);
    }

    private function normalizeArchiveEntry(string $entryName): string
    {
        $normalized = str_replace('\\', '/', trim($entryName));
        if (str_starts_with($normalized, '/') || str_contains($normalized, ':') || str_contains($normalized, "\0")
            || preg_match('#(^|/)\.{1,2}(/|$)#', $normalized)) {
            throw new \RuntimeException('backups_archive_invalid');
        }
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

        if ($this->isAllowedArchiveEntryForVersion($normalized, 1, [])) {
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

    /**
     * @param array<string,mixed> $manifest
     * @param array<string,mixed> $inventory
     * @return list<string>
     */
    private function validatePortableManifestScope(array $manifest, array $inventory): array
    {
        if ((int) ($manifest['baseline_schema'] ?? 0) !== 1) {
            throw new \RuntimeException('backups_archive_invalid_manifest');
        }

        $sourceVersion = trim((string) ($manifest['flatcms_version'] ?? ''));
        $targetVersion = CoreManifest::version('1.0.0');
        if ($sourceVersion === '' || !hash_equals($targetVersion, $sourceVersion)) {
            throw new \RuntimeException('backups_site_version_incompatible');
        }

        $targetInspection = $this->baselineService->inspect();
        if (empty($targetInspection['portable'])) {
            throw new \RuntimeException('backups_site_target_portability_blocked');
        }

        $baseline = $this->baselineService->read();
        $roots = [];
        $seen = [];

        $components = $manifest['components'] ?? null;
        $themes = $manifest['themes'] ?? null;
        if (!is_array($components) || !is_array($themes)) {
            throw new \RuntimeException('backups_archive_invalid_manifest');
        }

        $componentRules = [
            'modules' => ['type' => 'module', 'prefix' => 'app/Modules', 'manifest' => 'module.json'],
            'extensions' => ['type' => 'extension', 'prefix' => 'app/Extensions', 'manifest' => 'extension.json'],
            'plugins' => ['type' => 'plugin', 'prefix' => 'app/Plugins', 'manifest' => 'plugin.json'],
        ];

        foreach ($components as $item) {
            if (!is_array($item)) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $catalog = trim((string) ($item['catalog'] ?? ''));
            $rule = $componentRules[$catalog] ?? null;
            if (!is_array($rule) || (string) ($item['type'] ?? '') !== $rule['type']) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $root = $this->validatePortableManifestItem(
                $item,
                (string) $rule['prefix'],
                (string) $rule['manifest'],
                (array) ($baseline['components'][$catalog] ?? []),
                $inventory
            );
            if (isset($seen[$root])) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $seen[$root] = true;
            $roots[] = $root;
        }

        foreach ($themes as $item) {
            if (!is_array($item) || (string) ($item['type'] ?? '') !== 'theme') {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $themeType = trim((string) ($item['theme_type'] ?? ''));
            if (!in_array($themeType, ['frontend', 'admin'], true)) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $baselineThemes = (array) ($baseline['components']['themes'] ?? []);
            $root = $this->validatePortableManifestItem(
                $item,
                'themes/' . $themeType,
                'theme.json',
                $baselineThemes,
                $inventory,
                $themeType . '/'
            );
            if (isset($seen[$root])) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $seen[$root] = true;
            $roots[] = $root;
        }

        sort($roots);
        return $roots;
    }

    /**
     * @param array<string,mixed> $item
     * @param array<string,mixed> $baselineCatalog
     * @param array<string,mixed> $inventory
     */
    private function validatePortableManifestItem(
        array $item,
        string $prefix,
        string $manifestName,
        array $baselineCatalog,
        array $inventory,
        string $baselineKeyPrefix = ''
    ): string {
        $key = trim((string) ($item['key'] ?? ''));
        $path = trim((string) ($item['path'] ?? ''), '/');
        if ($key === '' || str_contains($key, '/') || str_contains($key, '\\')
            || preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/D', $key) !== 1
            || $path !== $prefix . '/' . $key
            || (string) ($item['status'] ?? '') !== 'added') {
            throw new \RuntimeException('backups_archive_invalid_manifest');
        }

        $baselineKey = $baselineKeyPrefix . $key;
        if (isset($baselineCatalog[$baselineKey])) {
            throw new \RuntimeException('backups_archive_invalid_manifest');
        }
        if (!isset($inventory[$path . '/' . $manifestName])) {
            throw new \RuntimeException('backups_archive_invalid_manifest');
        }

        $fingerprint = $this->fingerprintInventoryRoot($inventory, $path);
        $expectedHash = strtolower(trim((string) ($item['sha256'] ?? '')));
        if (preg_match('/^[a-f0-9]{64}$/D', $expectedHash) !== 1
            || !hash_equals($expectedHash, $fingerprint['sha256'])
            || (int) ($item['files_count'] ?? -1) !== $fingerprint['files_count']
            || (int) ($item['size_bytes'] ?? -1) !== $fingerprint['size_bytes']) {
            throw new \RuntimeException('backups_archive_invalid_manifest');
        }

        return $path;
    }

    /**
     * @param array<string,mixed> $inventory
     * @return array{sha256:string,files_count:int,size_bytes:int}
     */
    private function fingerprintInventoryRoot(array $inventory, string $root): array
    {
        $prefix = rtrim($root, '/') . '/';
        $entries = [];
        foreach ($inventory as $relative => $meta) {
            if (!is_string($relative) || !is_array($meta) || !str_starts_with($relative, $prefix)) {
                continue;
            }
            $child = substr($relative, strlen($prefix));
            $sha256 = strtolower(trim((string) ($meta['sha256'] ?? '')));
            $size = (int) ($meta['size_bytes'] ?? -1);
            if ($child === '' || preg_match('/^[a-f0-9]{64}$/D', $sha256) !== 1 || $size < 0) {
                throw new \RuntimeException('backups_archive_invalid_manifest');
            }
            $entries[$child] = ['sha256' => $sha256, 'size' => $size];
        }
        if ($entries === []) {
            throw new \RuntimeException('backups_archive_invalid_manifest');
        }
        ksort($entries);

        $hash = hash_init('sha256');
        $bytes = 0;
        foreach ($entries as $child => $meta) {
            $bytes += $meta['size'];
            hash_update($hash, $child . "\0" . $meta['size'] . "\0" . $meta['sha256'] . "\n");
        }

        return [
            'sha256' => hash_final($hash),
            'files_count' => count($entries),
            'size_bytes' => $bytes,
        ];
    }

    /** @param list<string> $portableRoots */
    private function isAllowedArchiveEntryForVersion(string $entryName, int $version, array $portableRoots): bool
    {
        if ($entryName === '' || str_contains($entryName, '../') || str_starts_with($entryName, '../')) {
            return false;
        }
        if ($entryName === 'manifest.json') {
            return true;
        }
        if ($this->isDataJsonEntry($entryName) || $this->isDataHtmlEntry($entryName)) {
            return true;
        }

        if ($version <= 2) {
            return $this->isLegacyMediaEntry($entryName)
                || $entryName === 'storage/app/secretbox.key';
        }

        if ($entryName === self::PORTABLE_SECRETS_ENTRY) {
            return true;
        }
        if ($entryName === 'storage/app/secretbox.key'
            || str_starts_with($entryName, 'resources/licenses/')) {
            return false;
        }
        if ($this->isMediaEntry($entryName)) {
            return true;
        }

        foreach ($portableRoots as $root) {
            $root = rtrim((string) $root, '/');
            if ($root !== '' && str_starts_with($entryName, $root . '/')) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $portableRoots */
    private function isPortableRootEntry(string $entryName, array $portableRoots): bool
    {
        foreach ($portableRoots as $root) {
            $root = rtrim((string) $root, '/');
            if ($root !== '' && str_starts_with($entryName, $root . '/')) {
                return true;
            }
        }
        return false;
    }

    private function isLegacyMediaEntry(string $entryName): bool
    {
        if (str_starts_with($entryName, 'public/uploads/')) {
            return !str_starts_with($entryName, 'public/uploads/cache/runtime-css/');
        }
        if (str_starts_with($entryName, 'storage/uploads/avatars/')) {
            return true;
        }
        return str_starts_with($entryName, 'uploads/');
    }

    private function isValidJson(string $content): bool
    {
        json_decode($content, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function clearRuntimeCaches(): void
    {
        \App\Core\ContentDocumentStore::resetRequestCache();
        \App\Core\Storage\JsonStore::resetRequestCache(BASE_PATH . '/data');
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
        $paths = new StoragePathGuard(BASE_PATH);
        $paths->resolve($absoluteRoot);
        if (!is_dir($absoluteRoot)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absoluteRoot, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            $paths->resolve($item->getPathname());
            if (!$item->isFile()) {
                continue;
            }

            $pathname = $item->getPathname();
            $extension = strtolower(pathinfo($pathname, PATHINFO_EXTENSION));
            if (!in_array($extension, ['json', 'html'], true)) {
                continue;
            }

            $content = @file_get_contents($pathname);
            if (!is_string($content) || ($extension === 'json' && !$this->isValidJson($content))) {
                throw new \RuntimeException('backups_archive_invalid_json');
            }

            $files[$this->buildArchiveRelativePath($absoluteRoot, $archiveRoot, $pathname)] = $content;
        }

        return $files;
    }

    /**
     * @param array<int, string> $excludedPrefixes
     * @return array<string, array{sha256:string,size:int,source:string}>
     */
    private function snapshotFileDirectory(string $absoluteRoot, string $archiveRoot, array $excludedPrefixes = []): array
    {
        $files = [];
        $paths = new StoragePathGuard(BASE_PATH);
        $paths->resolve($absoluteRoot);
        if (!is_dir($absoluteRoot)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absoluteRoot, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            $paths->resolve($item->getPathname());
            if (!$item->isFile() || $item->isLink()) {
                continue;
            }

            $pathname = $item->getPathname();
            $relativeWithinRoot = ltrim(str_replace('\\', '/', substr($pathname, strlen($absoluteRoot))), '/');
            if ($relativeWithinRoot === '' || $this->shouldSkipMediaRelativePath($relativeWithinRoot, $excludedPrefixes)) {
                continue;
            }

            $basename = (string) $item->getBasename();
            if ($basename === '.gitkeep' || $basename === '.DS_Store' || str_starts_with($basename, '._')) {
                continue;
            }

            $files[$archiveRoot . '/' . $relativeWithinRoot] =
                $this->streamMetadata(fopen($pathname, 'rb')) + ['source' => $pathname];
        }

        return $files;
    }

    /**
     * @return array<string, string>
     */
    private function snapshotExactFile(string $absolutePath, string $archivePath): array
    {
        (new StoragePathGuard(BASE_PATH))->resolve($absolutePath);
        if (!is_file($absolutePath) || is_link($absolutePath)) {
            return [];
        }

        $content = @file_get_contents($absolutePath);
        if (!is_string($content)) {
            throw new \RuntimeException('backups_archive_write_failed');
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

    private function isDataHtmlEntry(string $entryName): bool
    {
        return str_starts_with($entryName, 'data/') && str_ends_with($entryName, '.html');
    }

    private function isSecretEntry(string $entryName): bool
    {
        return $entryName === 'storage/app/secretbox.key'
            || $entryName === self::PORTABLE_SECRETS_ENTRY
            || str_starts_with($entryName, 'resources/licenses/');
    }

    private function containsSecretEntries(array $files): bool
    {
        foreach (array_keys($files) as $relative) {
            if ($this->isSecretEntry((string) $relative)) {
                return true;
            }
        }
        return false;
    }

    private function isMediaEntry(string $entryName): bool
    {
        if (str_starts_with($entryName, 'public/uploads/')) {
            return !str_starts_with($entryName, 'public/uploads/cache/runtime-css/');
        }

        if (str_starts_with($entryName, 'storage/uploads/avatars/')) {
            return true;
        }

        if (str_starts_with($entryName, 'resources/uploads/contact/')) {
            return true;
        }

        return str_starts_with($entryName, 'uploads/');
    }

    /**
     * @param array<string, string|array<string,mixed>> $files
     */
    private function countDataFilesByExtension(array $files, string $extension): int
    {
        $count = 0;
        foreach (array_keys($files) as $relativePath) {
            if (str_starts_with($relativePath, 'data/') && str_ends_with($relativePath, '.' . $extension)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param array<string, string|array<string,mixed>> $files
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
     * @return array<string, string|array<string,mixed>>
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
        $files += $this->snapshotFileDirectory($this->storageAvatarsRoot, 'storage/uploads/avatars');
        $files += $this->snapshotExactFile($this->storageSecretKeyPath, 'storage/app/secretbox.key');

        if (!$this->uploadsAliasesPublicUploads()) {
            $files += $this->snapshotFileDirectory($this->uploadsRoot . '/logo', 'uploads/logo');
        }

        $files['data/settings.json'] = $this->encodeJson($this->buildResetSettingsPayload($settings));
        $files['data/modules.json'] = $this->readJsonFileContent(BASE_PATH . '/data/modules.json', []);
        $files['data/core/auth/login_attempts.json'] = $this->encodeJson([]);
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
            BASE_PATH . '/app/Modules' => 'module.json',
            BASE_PATH . '/app/Extensions' => 'extension.json',
            BASE_PATH . '/app/Plugins' => 'plugin.json',
        ];

        foreach ($roots as $root => $manifestName) {
            (new StoragePathGuard(BASE_PATH))->resolve($root);
            if (!is_dir($root)) {
                continue;
            }

            foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
                (new StoragePathGuard(BASE_PATH))->resolve($dir);
                $name = basename($dir);
                if ($name === '') {
                    continue;
                }

                $manifestPath = $dir . '/' . $manifestName;
                if (!is_file($manifestPath)) { continue; }
                try { $manifest = $this->readJsonFile($manifestPath); }
                catch (\RuntimeException) { continue; }
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

    /** @return list<string> */
    private function factoryResetResidualFiles(bool $deleteSensitive = false): array
    {
        $guard = new StoragePathGuard(BASE_PATH);
        $installed = $guard->resolve('data/installed.lock');
        if (is_dir($installed)) { throw new \RuntimeException('backups_restore_write_failed'); }
        $files = is_file($installed) ? ['data/installed.lock'] : [];

        $privateAttachmentsRoot = $guard->resolve('resources/uploads/contact');
        if (is_file($privateAttachmentsRoot)) { throw new \RuntimeException('backups_restore_write_failed'); }
        if (is_dir($privateAttachmentsRoot)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($privateAttachmentsRoot, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $item) {
                $guard->resolve($item->getPathname());
                if ($item->isFile()) {
                    $files[] = $this->buildArchiveRelativePath(
                        $privateAttachmentsRoot,
                        'resources/uploads/contact',
                        $item->getPathname()
                    );
                }
            }
        }

        if ($deleteSensitive) {
            $envLocal = $guard->resolve('.env.local');
            if (is_dir($envLocal)) { throw new \RuntimeException('backups_restore_write_failed'); }
            if (is_file($envLocal) && !is_link($envLocal)) {
                $files[] = '.env.local';
            }

            $licensesRoot = $guard->resolve('resources/licenses');
            if (is_file($licensesRoot)) { throw new \RuntimeException('backups_restore_write_failed'); }
            if (is_dir($licensesRoot)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($licensesRoot, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $item) {
                    $guard->resolve($item->getPathname());
                    if ($item->isFile()) {
                        $files[] = $this->buildArchiveRelativePath(
                            $licensesRoot,
                            'resources/licenses',
                            $item->getPathname()
                        );
                    }
                }
            }
        }

        $files = array_values(array_unique($files));
        sort($files);
        return $files;
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
        (new StoragePathGuard(BASE_PATH))->resolve($path);
        if (!is_file($path)) {
            return [];
        }

        $content = @file_get_contents($path);
        if (!is_string($content) || !$this->isValidJson($content)) {
            throw new \RuntimeException('backups_archive_invalid_json');
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) { throw new \RuntimeException('backups_archive_invalid_json'); }
        return $decoded;
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
        $guard = new StoragePathGuard(BASE_PATH);
        $guard->resolve($path);
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
            $guard->resolve($pathname);
            if ($item->isDir()) {
                @rmdir($pathname);
                continue;
            }

            @unlink($pathname);
        }
    }

    /** @param array<string,mixed> $upload */
    private function moveUploadedKey(array $upload): string
    {
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($error === UPLOAD_ERR_NO_FILE
                ? 'backups_site_key_invalid'
                : $this->uploadErrorKey($error));
        }
        $name = trim((string) ($upload['name'] ?? ''));
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'key') {
            throw new \RuntimeException('backups_site_key_invalid');
        }
        $source = (string) ($upload['tmp_name'] ?? '');
        $target = $this->tmpRoot . '/site-restore-' . bin2hex(random_bytes(8)) . '.key';
        $moved = $source !== '' && @move_uploaded_file($source, $target);
        if (!$moved && $source !== '') {
            $moved = @rename($source, $target);
        }
        if (!$moved && $source !== '') {
            $moved = @copy($source, $target);
        }
        if (!$moved) {
            throw new \RuntimeException('backups_upload_failed');
        }
        @chmod($target, 0600);
        try {
            $this->secretCipher->read($target);
        } catch (\Throwable $exception) {
            @unlink($target);
            throw new \RuntimeException('backups_site_key_invalid', 0, $exception);
        }

        return $target;
    }

    private function ensureDirectories(): void
    {
        foreach ([$this->backupRoot => 0750, $this->tmpRoot => 0750, $this->keyRoot => 0700] as $path => $mode) {
            if (!is_dir($path) && !@mkdir($path, $mode, true) && !is_dir($path)) {
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
