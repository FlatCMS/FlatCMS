<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare(strict_types=1);

namespace App\Modules\Backups\Services;

final class FullBackupService
{
    private const ARCHIVE_KIND = 'flatcms-full-backup';
    private const ARCHIVE_VERSION = 1;
    private const BACKUP_PREFIX = 'flatcms-full-backup';
    private const SECRET_CIPHER = 'aes-256-gcm';

    private string $basePath;
    private string $backupRoot;
    private string $keyRoot;

    /** @var array<int,string> */
    private array $excludedPrefixes = [
        '.git/',
        '.svn/',
        '.idea/',
        '.vscode/',
        'public/uploads/cache/runtime-css/',
        'storage/backups/',
        'storage/cache/',
        'storage/logs/',
        'storage/sessions/',
        'storage/tmp/',
        'storage/update-artifacts/',
        'storage/update-manager/',
        'storage/recovery/',
    ];

    public function __construct(?string $basePath = null, ?string $backupRoot = null, ?string $keyRoot = null)
    {
        $this->basePath = rtrim($basePath ?: BASE_PATH, '/\\');
        $this->backupRoot = $backupRoot ?: $this->basePath . '/storage/backups/full';
        $this->keyRoot = $keyRoot ?: $this->basePath . '/storage/recovery/keys';
    }

    /** @return array<int,array<string,mixed>> */
    public function listBackups(): array
    {
        $this->ensureDirectory($this->backupRoot, 0750);

        $items = [];
        foreach (glob($this->backupRoot . '/*.zip') ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $items[] = $this->backupItemFromPath($path);
        }

        usort($items, static fn (array $left, array $right): int =>
            ((int) ($right['created_ts'] ?? 0)) <=> ((int) ($left['created_ts'] ?? 0))
        );

        return $items;
    }

    public function resolveStoredBackupPath(string $filename): ?string
    {
        $filename = basename(trim($filename));
        if ($filename === '' || preg_match('/^flatcms-full-backup-[A-Za-z0-9._-]+\.zip$/', $filename) !== 1) {
            return null;
        }
        $path = $this->backupRoot . '/' . $filename;
        return is_file($path) ? $path : null;
    }

    public function deleteStoredBackup(string $filename): void
    {
        $path = $this->resolveStoredBackupPath($filename);
        if ($path === null) {
            throw new \RuntimeException('backups_archive_not_found');
        }
        $manifest = $this->readManifestOnly($path);
        $backupId = trim((string) ($manifest['backup_id'] ?? ''));
        if (!@unlink($path)) {
            throw new \RuntimeException('backups_delete_failed');
        }
        if ($backupId !== '') {
            $keyPath = $this->keyRoot . '/' . basename($backupId) . '.key';
            if (is_file($keyPath)) {
                @unlink($keyPath);
            }
        }
    }

    /** @param array<string,string> $context @return array<string,mixed> */
    public function restoreStoredBackup(string $filename, array $context = []): array
    {
        $path = $this->resolveStoredBackupPath($filename);
        if ($path === null) {
            throw new \RuntimeException('backups_archive_not_found');
        }
        $manifest = $this->readManifestOnly($path);
        $backupId = trim((string) ($manifest['backup_id'] ?? ''));
        $keyPath = $backupId !== '' ? $this->keyRoot . '/' . basename($backupId) . '.key' : '';
        if ($keyPath === '' || !is_file($keyPath)) {
            throw new \RuntimeException('backups_full_key_invalid');
        }

        $rollbackContext = $context;
        $rollbackContext['reason'] = 'pre_full_restore';
        $rollback = $this->createBackup($rollbackContext);
        $result = $this->restoreBackupTo($path, $this->basePath, $keyPath);
        $result['rollback'] = $rollback;
        return $result;
    }

    /** @param array<string,mixed> $context @return array<string,mixed> */
    public function createBackup(array $context = []): array
    {
        $this->assertZip();
        $this->ensureDirectory($this->backupRoot, 0750);
        $this->ensureDirectory($this->keyRoot, 0700);

        $backupId = gmdate('YmdHis') . '-' . bin2hex(random_bytes(6));
        $filename = self::BACKUP_PREFIX . '-' . date('Ymd_His') . '-' . substr($backupId, -12) . '.zip';
        $path = $this->backupRoot . '/' . $filename;
        $keyPath = $this->keyRoot . '/' . $backupId . '.key';
        $secretKey = random_bytes(32);
        if (@file_put_contents($keyPath, base64_encode($secretKey), LOCK_EX) === false) {
            throw new \RuntimeException('backups_full_key_create_failed');
        }
        @chmod($keyPath, 0600);

        $files = $this->collectFiles($context);
        if ($files === []) {
            @unlink($keyPath);
            throw new \RuntimeException('backups_full_no_files');
        }

        $manifest = $this->buildManifest($backupId, $files, $context);
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) {
            @unlink($keyPath);
            throw new \RuntimeException('backups_full_create_failed');
        }

        try {
            foreach ($files as $relative => $meta) {
                if (!empty($meta['secret'])) {
                    $plain = (string) @file_get_contents($this->basePath . '/' . $relative);
                    $payload = $this->encryptSecret($plain, $secretKey);
                    $entry = 'secrets/' . $this->secretEntryName($relative) . '.json';
                    if (!$zip->addFromString($entry, json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '')) {
                        throw new \RuntimeException('backups_full_write_failed');
                    }
                    $manifest['files'][$relative]['entry'] = $entry;
                    continue;
                }
                $entry = 'files/' . $relative;
                if (!$zip->addFile($this->basePath . '/' . $relative, $entry)) {
                    throw new \RuntimeException('backups_full_write_failed');
                }
            }
            $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (!is_string($encoded) || !$zip->addFromString('manifest.json', $encoded)) {
                throw new \RuntimeException('backups_full_manifest_failed');
            }
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($path);
            @unlink($keyPath);
            throw $exception;
        }
        $zip->close();

        if (!is_file($path) || (int) @filesize($path) < 1) {
            @unlink($keyPath);
            throw new \RuntimeException('backups_full_create_failed');
        }

        return [
            'id' => $backupId,
            'filename' => $filename,
            'path' => $path,
            'key_path' => $keyPath,
            'size_bytes' => (int) @filesize($path),
            'sha256' => strtolower((string) hash_file('sha256', $path)),
            'files_count' => count($files),
            'flatcms_version' => (string) ($manifest['flatcms_version'] ?? ''),
            'created_at' => (string) ($manifest['created_at'] ?? ''),
            'reason' => (string) ($manifest['reason'] ?? ''),
        ];
    }

    /** @return array<string,mixed> */
    public function validateBackup(string $path): array
    {
        $this->assertZip();
        if (!is_file($path)) {
            throw new \RuntimeException('backups_full_not_found');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('backups_full_open_failed');
        }
        try {
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
            if (!is_array($manifest) || ($manifest['kind'] ?? '') !== self::ARCHIVE_KIND || (int) ($manifest['version'] ?? 0) !== self::ARCHIVE_VERSION) {
                throw new \RuntimeException('backups_full_manifest_invalid');
            }
            $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
            foreach ($files as $relative => $meta) {
                $relative = $this->normalizeRelative((string) $relative);
                if ($relative === '') {
                    throw new \RuntimeException('backups_full_path_invalid');
                }
                $entry = trim((string) ($meta['entry'] ?? (!empty($meta['secret']) ? '' : 'files/' . $relative)));
                if ($entry === '' || $zip->locateName($entry) === false) {
                    throw new \RuntimeException('backups_full_entry_missing');
                }
                if (empty($meta['secret'])) {
                    $stream = $zip->getStream($entry);
                    if (!is_resource($stream)) {
                        throw new \RuntimeException('backups_full_entry_missing');
                    }
                    $hash = hash_init('sha256');
                    hash_update_stream($hash, $stream);
                    fclose($stream);
                    $actual = strtolower(hash_final($hash));
                    if (!hash_equals(strtolower((string) ($meta['sha256'] ?? '')), $actual)) {
                        throw new \RuntimeException('backups_full_hash_mismatch');
                    }
                }
            }
            return $manifest;
        } finally {
            $zip->close();
        }
    }

    /** @return array<string,mixed> */
    public function restoreBackupTo(string $path, string $targetBase, string $keyPath): array
    {
        $manifest = $this->validateBackup($path);
        $targetBase = rtrim($targetBase, '/\\');
        if ($targetBase === '') {
            throw new \RuntimeException('backups_full_restore_target_invalid');
        }
        $secretKey = $this->readSecretKey($keyPath);
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('backups_full_open_failed');
        }
        $restored = 0;
        try {
            $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
            $manifestExcluded = $this->normalizeExcludedPrefixes($manifest['excluded_prefixes'] ?? []);
            $this->pruneManagedFiles($targetBase, array_keys($files), $manifestExcluded);
            foreach ($files as $relative => $meta) {
                $relative = $this->normalizeRelative((string) $relative);
                $target = $targetBase . '/' . $relative;
                $this->ensureDirectory(dirname($target), 0755);
                $entry = (string) ($meta['entry'] ?? (!empty($meta['secret']) ? '' : 'files/' . $relative));
                if (!empty($meta['secret'])) {
                    $payload = json_decode((string) $zip->getFromName($entry), true);
                    if (!is_array($payload)) {
                        throw new \RuntimeException('backups_full_secret_invalid');
                    }
                    $content = $this->decryptSecret($payload, $secretKey);
                    $this->atomicWrite($target, $content, (int) ($meta['mode'] ?? 0600));
                } else {
                    $stream = $zip->getStream($entry);
                    if (!is_resource($stream)) {
                        throw new \RuntimeException('backups_full_entry_missing');
                    }
                    $this->atomicWriteStream($target, $stream, (int) ($meta['mode'] ?? 0644));
                    fclose($stream);
                    $actual = strtolower((string) hash_file('sha256', $target));
                    if (!hash_equals(strtolower((string) ($meta['sha256'] ?? '')), $actual)) {
                        throw new \RuntimeException('backups_full_restore_verify_failed');
                    }
                }
                $restored++;
            }
        } finally {
            $zip->close();
        }
        return ['restored_files_count' => $restored, 'flatcms_version' => (string) ($manifest['flatcms_version'] ?? '')];
    }

    /** @return array<string,mixed> */
    private function backupItemFromPath(string $path): array
    {
        $manifest = $this->readManifestOnly($path);
        $createdTs = (int) ($manifest['created_unix'] ?? (@filemtime($path) ?: time()));
        $backupId = trim((string) ($manifest['backup_id'] ?? ''));
        $keyPath = $backupId !== '' ? $this->keyRoot . '/' . basename($backupId) . '.key' : '';

        return [
            'backup_type' => 'full',
            'filename' => basename($path),
            'created_at' => (string) ($manifest['created_at'] ?? date('c', $createdTs)),
            'created_ts' => $createdTs,
            'reason' => (string) ($manifest['reason'] ?? 'manual_full'),
            'created_by' => (string) ($manifest['created_by'] ?? 'Backups'),
            'flatcms_version' => (string) ($manifest['flatcms_version'] ?? ''),
            'target_version' => (string) ($manifest['target_version'] ?? ''),
            'files_count' => (int) ($manifest['files_count'] ?? 0),
            'total_files_count' => (int) ($manifest['files_count'] ?? 0),
            'size_bytes' => (int) (@filesize($path) ?: 0),
            'scope' => (string) ($manifest['scope'] ?? ''),
            'key_available' => $keyPath !== '' && is_file($keyPath),
        ];
    }

    /** @return array<string,mixed> */
    private function readManifestOnly(string $path): array
    {
        $this->assertZip();
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('backups_full_open_failed');
        }
        try {
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
            if (!is_array($manifest) || ($manifest['kind'] ?? '') !== self::ARCHIVE_KIND || (int) ($manifest['version'] ?? 0) !== self::ARCHIVE_VERSION) {
                throw new \RuntimeException('backups_full_manifest_invalid');
            }
            return $manifest;
        } finally {
            $zip->close();
        }
    }

    /** @return array<string,array<string,mixed>> */
    private function collectFiles(array $context = []): array
    {
        $files = [];
        $includeDiagnostics = !empty($context['include_diagnostics']);
        $contextExcluded = $this->normalizeExcludedPrefixes($context['exclude_prefixes'] ?? []);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo || !$item->isFile() || $item->isLink()) {
                continue;
            }
            $relative = $this->normalizeRelative(substr($item->getPathname(), strlen($this->basePath) + 1));
            if ($relative === '' || $this->isExcluded($relative, $includeDiagnostics, $contextExcluded) || basename($relative) === '.DS_Store') {
                continue;
            }
            $secret = in_array($relative, ['.env', '.env.local'], true);
            $files[$relative] = [
                'sha256' => $secret ? '' : strtolower((string) hash_file('sha256', $item->getPathname())),
                'size_bytes' => (int) $item->getSize(),
                'mode' => ((int) $item->getPerms()) & 0777,
                'secret' => $secret,
            ];
        }
        ksort($files);
        return $files;
    }

    /** @param array<string,array<string,mixed>> $files @param array<string,mixed> $context @return array<string,mixed> */
    private function buildManifest(string $backupId, array $files, array $context): array
    {
        return [
            'kind' => self::ARCHIVE_KIND,
            'version' => self::ARCHIVE_VERSION,
            'backup_id' => $backupId,
            'created_at' => gmdate('c'),
            'created_unix' => time(),
            'reason' => trim((string) ($context['reason'] ?? 'manual_full')),
            'created_by' => trim((string) ($context['created_by'] ?? 'Backups')),
            'target_version' => trim((string) ($context['target_version'] ?? '')),
            'flatcms_version' => $this->readFlatCmsVersion(),
            'files_count' => count($files),
            'scope' => trim((string) ($context['scope'] ?? 'full-installation-excluding-runtime')),
            'include_diagnostics' => !empty($context['include_diagnostics']),
            'excluded_prefixes' => $this->effectiveExcludedPrefixes($context),
            'files' => $files,
        ];
    }

    private function readFlatCmsVersion(): string
    {
        $raw = trim((string) @file_get_contents($this->basePath . '/VERSION'));
        if ($raw !== '') {
            if (preg_match('/["\']([^"\']+)["\']/', $raw, $match) === 1) {
                return trim((string) $match[1]);
            }
            return $raw;
        }
        $manifestPath = $this->basePath . '/flatcms.json';
        if (is_file($manifestPath)) {
            $decoded = json_decode((string) @file_get_contents($manifestPath), true);
            if (is_array($decoded)) {
                $value = trim((string) ($decoded['version'] ?? $decoded['core']['version'] ?? ''));
                if ($value !== '') return $value;
            }
        }
        return 'unknown';
    }

    /** @param array<int,string> $extraExcluded */
    private function isExcluded(string $relative, bool $includeDiagnostics = false, array $extraExcluded = []): bool
    {
        if ($includeDiagnostics && (
            str_starts_with($relative, 'storage/logs/')
            || str_starts_with($relative, 'storage/cache/update-manager/')
            || str_starts_with($relative, 'storage/update-manager/')
        )) {
            return false;
        }
        foreach (array_values(array_unique(array_merge($this->excludedPrefixes, $extraExcluded))) as $prefix) {
            $prefix = rtrim($prefix, '/') . '/';
            if ($relative === rtrim($prefix, '/') || str_starts_with($relative, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string,mixed> $context @return array<int,string> */
    private function effectiveExcludedPrefixes(array $context): array
    {
        return array_values(array_unique(array_merge(
            $this->excludedPrefixes,
            $this->normalizeExcludedPrefixes($context['exclude_prefixes'] ?? [])
        )));
    }

    /** @return array<int,string> */
    private function normalizeExcludedPrefixes(mixed $prefixes): array
    {
        if (!is_array($prefixes)) {
            return [];
        }
        $normalized = [];
        foreach ($prefixes as $prefix) {
            $prefix = $this->normalizeRelative((string) $prefix);
            if ($prefix === '') {
                continue;
            }
            $normalized[] = rtrim($prefix, '/') . '/';
        }
        return array_values(array_unique($normalized));
    }

    private function normalizeRelative(string $relative): string
    {
        $relative = ltrim(str_replace('\\', '/', trim($relative)), '/');
        if ($relative === '' || str_contains($relative, "\0") || preg_match('#(^|/)\.\.(/|$)#', $relative)) {
            return '';
        }
        return $relative;
    }

    /** @return array<string,string> */
    private function encryptSecret(string $plain, string $key): array
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, self::SECRET_CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if (!is_string($cipher)) {
            throw new \RuntimeException('backups_full_secret_encrypt_failed');
        }
        return ['iv' => base64_encode($iv), 'tag' => base64_encode($tag), 'cipher' => base64_encode($cipher)];
    }

    /** @param array<string,mixed> $payload */
    private function decryptSecret(array $payload, string $key): string
    {
        $iv = base64_decode((string) ($payload['iv'] ?? ''), true);
        $tag = base64_decode((string) ($payload['tag'] ?? ''), true);
        $cipher = base64_decode((string) ($payload['cipher'] ?? ''), true);
        if (!is_string($iv) || !is_string($tag) || !is_string($cipher)) {
            throw new \RuntimeException('backups_full_secret_invalid');
        }
        $plain = openssl_decrypt($cipher, self::SECRET_CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if (!is_string($plain)) {
            throw new \RuntimeException('backups_full_secret_decrypt_failed');
        }
        return $plain;
    }

    private function readSecretKey(string $keyPath): string
    {
        $raw = trim((string) @file_get_contents($keyPath));
        $key = base64_decode($raw, true);
        if (!is_string($key) || strlen($key) !== 32) {
            throw new \RuntimeException('backups_full_key_invalid');
        }
        return $key;
    }

    private function secretEntryName(string $relative): string
    {
        return rtrim(strtr(base64_encode($relative), '+/', '-_'), '=');
    }

    /** @param array<int,string> $expected */
    private function pruneManagedFiles(string $targetBase, array $expected, array $manifestExcluded = []): void
    {
        $expectedSet = array_fill_keys(array_map([$this, 'normalizeRelative'], $expected), true);
        foreach (['app', 'bin', 'config', 'data', 'public', 'resources', 'themes', 'storage'] as $root) {
            $absolute = $targetBase . '/' . $root;
            if (!is_dir($absolute)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                if (!$item instanceof \SplFileInfo || $item->isLink()) {
                    continue;
                }
                $relative = $this->normalizeRelative(substr($item->getPathname(), strlen($targetBase) + 1));
                if ($relative === '' || $this->isExcluded($relative, false, $manifestExcluded)) {
                    continue;
                }
                if ($item->isFile() && !isset($expectedSet[$relative])) {
                    @unlink($item->getPathname());
                } elseif ($item->isDir()) {
                    @rmdir($item->getPathname());
                }
            }
        }
    }

    private function atomicWrite(string $target, string $content, int $mode): void
    {
        $tmp = $target . '.flatcms-full-restore.tmp';
        if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
            throw new \RuntimeException('backups_full_restore_write_failed');
        }
        @chmod($tmp, $mode > 0 ? $mode : 0644);
        $this->swap($tmp, $target);
    }

    /** @param resource $stream */
    private function atomicWriteStream(string $target, $stream, int $mode): void
    {
        $tmp = $target . '.flatcms-full-restore.tmp';
        $out = @fopen($tmp, 'wb');
        if (!is_resource($out)) {
            throw new \RuntimeException('backups_full_restore_write_failed');
        }
        $ok = stream_copy_to_stream($stream, $out);
        fclose($out);
        if ($ok === false) {
            @unlink($tmp);
            throw new \RuntimeException('backups_full_restore_write_failed');
        }
        @chmod($tmp, $mode > 0 ? $mode : 0644);
        $this->swap($tmp, $target);
    }

    private function swap(string $tmp, string $target): void
    {
        if (!@rename($tmp, $target)) {
            if (is_file($target)) {
                @unlink($target);
            }
            if (!@rename($tmp, $target)) {
                @unlink($tmp);
                throw new \RuntimeException('backups_full_restore_swap_failed');
            }
        }
    }

    private function ensureDirectory(string $path, int $mode): void
    {
        if (!is_dir($path) && !@mkdir($path, $mode, true) && !is_dir($path)) {
            throw new \RuntimeException('backups_full_directory_failed');
        }
    }

    private function assertZip(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('backups_full_zip_required');
        }
        if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
            throw new \RuntimeException('backups_full_openssl_required');
        }
    }
}
