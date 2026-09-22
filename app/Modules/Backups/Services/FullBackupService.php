<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Backups/Services/FullBackupService.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Modules\Backups\Services;

use App\Core\Storage\StoragePathGuard;
use App\Core\Storage\StreamFileTransaction;

final class FullBackupService
{
    private const ARCHIVE_KIND = 'flatcms-full-backup';
    private const ARCHIVE_VERSION = 2;
    private const SUPPORTED_ARCHIVE_VERSIONS = [1, 2];
    private const BACKUP_PREFIX = 'flatcms-full-backup';
    private string $basePath;
    private string $backupRoot;
    private string $keyRoot;
    private BackupSecretCipher $secretCipher;

    /** @var null|callable(string,string):void */
    private $checkpoint;

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
        'storage/locks/',
        'storage/sessions/',
        'storage/sync/',
        'storage/tmp/',
        'storage/transactions/',
        'storage/trash/media-purge/',
        'storage/trash/media-transactions/',
        'storage/update-artifacts/',
        'storage/update-manager/',
        'storage/recovery/',
    ];

    public function __construct(
        ?string $basePath = null,
        ?string $backupRoot = null,
        ?string $keyRoot = null,
        ?callable $checkpoint = null
    )
    {
        $this->basePath = rtrim($basePath ?: BASE_PATH, '/\\');
        $this->backupRoot = $backupRoot ?: $this->basePath . '/storage/backups/full';
        $this->keyRoot = $keyRoot ?: $this->basePath . '/storage/recovery/keys';
        $this->checkpoint = $checkpoint;
        $this->secretCipher = new BackupSecretCipher(
            $this->basePath,
            $this->keyRoot,
            'full-backups',
            'backups_full'
        );
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

    public function resolveStoredKeyPath(string $filename): ?string
    {
        $path = $this->resolveStoredBackupPath($filename);
        if ($path === null) {
            return null;
        }
        $manifest = $this->readManifestOnly($path);
        $backupId = trim((string) ($manifest['backup_id'] ?? ''));
        if (preg_match('/^[0-9]{14}-[a-f0-9]{12}$/D', $backupId) !== 1) {
            return null;
        }
        $keyPath = $this->keyRoot . '/' . basename($backupId) . '.key';

        return is_file($keyPath) && !is_link($keyPath) ? $keyPath : null;
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

    /**
     * @param array<string,string> $context
     * @param callable(string):bool|null $accept
     * @return array<string,mixed>
     */
    public function restoreStoredBackup(string $filename, array $context = [], ?callable $accept = null): array
    {
        $path = $this->resolveStoredBackupPath($filename);
        if ($path === null) {
            throw new \RuntimeException('backups_archive_not_found');
        }
        $keyPath = $this->resolveStoredKeyPath(basename($path));
        if ($keyPath === null) {
            throw new \RuntimeException('backups_full_key_invalid');
        }

        // The streamed transaction owns one provisional before-generation, not another ZIP per retry.
        $manifest = $this->validateBackup($path, $keyPath);
        $expectedVersion = trim((string) ($manifest['flatcms_version'] ?? ''));
        $validator = $accept === null ? null : static fn (): bool => $accept($expectedVersion);

        return $this->restoreBackupTo($path, $this->basePath, $keyPath, $validator);
    }

    /** @param array<string,mixed> $context @return array<string,mixed> */
    public function createBackup(array $context = []): array
    {
        return \App\Core\Storage\ApplicationLock::for($this->basePath)->exclusive(fn (): array => $this->createBackupUnderLease($context));
    }

    private function createBackupUnderLease(array $context): array
    {
        $this->assertZip();
        $guard = new StoragePathGuard($this->basePath);
        $guard->ensureDirectory($this->backupRoot, 0750);
        $guard->ensureDirectory($this->keyRoot, 0700);

        $backupId = (string) ($context['backup_id'] ?? (gmdate('YmdHis') . '-' . bin2hex(random_bytes(6))));
        if (preg_match('/^[0-9]{14}-[a-f0-9]{12}$/D', $backupId) !== 1) {
            throw new \RuntimeException('backups_full_manifest_invalid');
        }
        $filename = self::BACKUP_PREFIX . '-' . $backupId . '.zip';
        $path = $this->backupRoot . '/' . $filename;
        $keyPath = $this->keyRoot . '/' . $backupId . '.key';
        $partial = $path . '.partial';
        foreach ([$path, $keyPath, $partial] as $candidate) {
            $guard->resolve($candidate);
            if (file_exists($candidate)) { throw new \RuntimeException('backups_full_create_failed'); }
        }
        $files = $this->collectFiles($context);
        if ($files === []) {
            throw new \RuntimeException('backups_full_no_files');
        }

        $manifest = $this->buildManifest($backupId, $files, $context);
        $zip = new \ZipArchive();
        try {
            $secretKey = $this->secretCipher->generate();
            $this->secretCipher->persist(basename($keyPath), $secretKey);
            $this->checkpoint('key_persisted', basename($keyPath));
            if ($zip->open($partial, \ZipArchive::CREATE | \ZipArchive::EXCL) !== true) {
                throw new \RuntimeException('backups_full_create_failed');
            }
            foreach ($files as $relative => $meta) {
                if (!empty($meta['secret'])) {
                    $plain = (string) @file_get_contents($this->basePath . '/' . $relative);
                    $payload = $this->secretCipher->encrypt($plain, $secretKey);
                    $entry = 'secrets/' . $this->secretCipher->entryName($relative) . '.json';
                    if (!$zip->addFromString($entry, json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '')) {
                        throw new \RuntimeException('backups_full_write_failed');
                    }
                    $manifest['files'][$relative]['entry'] = $entry;
                    $this->checkpoint('file_added', $relative);
                    continue;
                }
                $entry = 'files/' . $relative;
                if (!$zip->addFile($this->basePath . '/' . $relative, $entry)) {
                    throw new \RuntimeException('backups_full_write_failed');
                }
                $this->checkpoint('file_added', $relative);
            }
            $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (!is_string($encoded) || !$zip->addFromString('manifest.json', $encoded)) {
                throw new \RuntimeException('backups_full_manifest_failed');
            }
            if (!$zip->close()) { throw new \RuntimeException('backups_full_create_failed'); }
            @chmod($partial, 0600);
            $this->validateBackup($partial, $keyPath);
            if (!rename($partial, $path)) { throw new \RuntimeException('backups_full_create_failed'); }
        } catch (\Throwable $exception) {
            try { $zip->close(); } catch (\Throwable) {}
            @unlink($partial);
            @unlink($keyPath);
            throw $exception;
        }

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

    private function checkpoint(string $event, string $subject): void
    {
        if ($this->checkpoint !== null) {
            ($this->checkpoint)($event, $subject);
        }
    }

    /** @return array<string,mixed> */
    public function validateBackup(string $path, ?string $keyPath = null): array
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
            if (!is_array($manifest) || ($manifest['kind'] ?? '') !== self::ARCHIVE_KIND
                || !in_array((int) ($manifest['version'] ?? 0), self::SUPPORTED_ARCHIVE_VERSIONS, true)) {
                throw new \RuntimeException('backups_full_manifest_invalid');
            }
            $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
            if ($files === []) { throw new \RuntimeException('backups_full_manifest_invalid'); }
            $key = $keyPath !== null ? $this->secretCipher->read($keyPath) : null;
            foreach ($files as $relative => $meta) {
                $relative = $this->normalizeRelative((string) $relative);
                if ($relative === '') {
                    throw new \RuntimeException('backups_full_path_invalid');
                }
                if ((int) ($manifest['version'] ?? 0) >= 2
                    && (string) ($meta['family'] ?? '') !== $this->classifyFile($relative)) {
                    throw new \RuntimeException('backups_full_manifest_invalid');
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
                    $size = hash_update_stream($hash, $stream);
                    $complete = feof($stream);
                    fclose($stream);
                    $actual = strtolower(hash_final($hash));
                    if (!$complete || $size !== (int) ($meta['size_bytes'] ?? -1)
                        || !hash_equals(strtolower((string) ($meta['sha256'] ?? '')), $actual)) {
                        throw new \RuntimeException('backups_full_hash_mismatch');
                    }
                } elseif ($key !== null) {
                    $payload = json_decode((string) $zip->getFromName($entry), true, 512, JSON_THROW_ON_ERROR);
                    $plain = is_array($payload) ? $this->secretCipher->decrypt($payload, $key) : '';
                    if (!is_array($payload)
                        || strlen($plain) !== (int) ($meta['size_bytes'] ?? -1)
                        || !hash_equals(strtolower((string) ($meta['sha256'] ?? '')), hash('sha256', $plain))) {
                        throw new \RuntimeException('backups_full_secret_decrypt_failed');
                    }
                }
            }
            return $manifest;
        } finally {
            $zip->close();
        }
    }

    /**
     * @param callable():bool|null $accept
     * @return array<string,mixed>
     */
    public function restoreBackupTo(string $path, string $targetBase, string $keyPath, ?callable $accept = null): array
    {
        $guard = new StoragePathGuard($targetBase);
        $targetBase = $guard->root();
        $transaction = new StreamFileTransaction($targetBase, 'full-restoration');
        return $transaction->synchronized(fn (): array => $this->restoreBackupUnlocked(
            $path,
            $targetBase,
            $keyPath,
            $transaction,
            $accept
        ));
    }

    public function recoverRestoration(?string $targetBase = null): void
    {
        (new StreamFileTransaction($targetBase ?? $this->basePath, 'full-restoration'))->recover();
    }

    /**
     * @param callable():bool|null $accept
     */
    private function restoreBackupUnlocked(
        string $path,
        string $targetBase,
        string $keyPath,
        StreamFileTransaction $transaction,
        ?callable $accept = null
    ): array
    {
        $manifest = $this->validateBackup($path);
        $targetBase = rtrim($targetBase, '/\\');
        if ($targetBase === '') {
            throw new \RuntimeException('backups_full_restore_target_invalid');
        }
        $secretKey = $this->secretCipher->read($keyPath);
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('backups_full_open_failed');
        }
        $restored = 0;
        try {
            $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
            if ($files === []) { throw new \RuntimeException('backups_full_manifest_invalid'); }
            $manifestExcluded = $this->normalizeExcludedPrefixes($manifest['excluded_prefixes'] ?? []);
            $paths = new StoragePathGuard($targetBase);
            $secrets = [];
            // Resolve every destination and authenticate ALL secrets before the first write.
            foreach ($files as $relative => $meta) {
                if (!is_array($meta) || $this->normalizeRelative((string) $relative) !== (string) $relative
                    || $this->isExcluded((string) $relative, !empty($manifest['include_diagnostics']), $manifestExcluded)) {
                    throw new \RuntimeException('backups_full_path_invalid');
                }
                $target = $paths->resolve((string) $relative);
                if (is_dir($target)) { throw new \RuntimeException('backups_full_target_collision'); }
                if (!empty($meta['secret'])) {
                    $payload = json_decode((string) $zip->getFromName((string) ($meta['entry'] ?? '')), true);
                    if (!is_array($payload)) { throw new \RuntimeException('backups_full_secret_invalid'); }
                    $secrets[$relative] = $this->secretCipher->decrypt($payload, $secretKey);
                }
            }
            $operations = [];
            foreach ($files as $relative => $meta) {
                // Diagnostic snapshots are useful for inspection, never canonical restore targets.
                if ($this->isExcluded((string) $relative, false, $manifestExcluded)) { continue; }
                $relative = $this->normalizeRelative((string) $relative);
                $entry = (string) ($meta['entry'] ?? (!empty($meta['secret']) ? '' : 'files/' . $relative));
                if (!empty($meta['secret'])) {
                    $plain = $secrets[$relative];
                    $operations[] = ['path' => $relative, 'sha256' => hash('sha256', $plain), 'size' => strlen($plain),
                        'mode' => (int) ($meta['mode'] ?? 0600), 'open' => static function () use ($plain) {
                            $stream = fopen('php://temp', 'w+b');
                            if (!is_resource($stream)) { throw new \RuntimeException('backups_restore_write_failed'); }
                            if (fwrite($stream, $plain) !== strlen($plain) || !rewind($stream)) {
                                fclose($stream);
                                throw new \RuntimeException('backups_restore_write_failed');
                            }
                            return $stream;
                        }];
                } else {
                    $operations[] = ['path' => $relative, 'sha256' => (string) $meta['sha256'], 'size' => (int) $meta['size_bytes'],
                        'mode' => (int) ($meta['mode'] ?? 0644), 'open' => static fn () => $zip->getStream($entry)];
                }
                $restored++;
            }
            foreach ($this->obsoleteManagedFiles($targetBase, array_keys($files), $manifestExcluded) as $relative) {
                $operations[] = ['path' => $relative, 'open' => null];
            }
            $transaction->commit($operations, $accept);
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
        $keyPath = $this->resolveStoredKeyPath(basename($path));

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
            'key_required' => true,
            'key_available' => $keyPath !== null,
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
            if (!is_array($manifest) || ($manifest['kind'] ?? '') !== self::ARCHIVE_KIND
                || !in_array((int) ($manifest['version'] ?? 0), self::SUPPORTED_ARCHIVE_VERSIONS, true)) {
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
            $secret = $this->isSecretFile($relative);
            $files[$relative] = [
                'sha256' => strtolower((string) hash_file('sha256', $item->getPathname())),
                'size_bytes' => (int) $item->getSize(),
                'mode' => ((int) $item->getPerms()) & 0777,
                'secret' => $secret,
                'family' => $this->classifyFile($relative),
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
            'families' => $this->familySummary($files),
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

    private function isSecretFile(string $relative): bool
    {
        return in_array($relative, ['.env', '.env.local', 'storage/app/secretbox.key'], true)
            || str_starts_with($relative, 'resources/licenses/');
    }

    private function classifyFile(string $relative): string
    {
        if (in_array($relative, ['.env', '.env.local'], true)) {
            return 'persistent_data';
        }

        foreach ([
            'data/',
            'public/uploads/',
            'resources/downloads/',
            'resources/licenses/',
            'resources/uploads/',
            'storage/app/',
            'storage/uploads/',
            'storage/trash/media/',
            'storage/trash/themes/',
        ] as $prefix) {
            if (str_starts_with($relative, $prefix)) {
                return 'persistent_data';
            }
        }

        return 'product_code';
    }

    /**
     * @param array<string, array<string, mixed>> $files
     * @return array<string, array{files_count: int}>
     */
    private function familySummary(array $files): array
    {
        $summary = [
            'product_code' => ['files_count' => 0],
            'persistent_data' => ['files_count' => 0],
        ];
        foreach ($files as $meta) {
            $family = (string) ($meta['family'] ?? '');
            if (isset($summary[$family])) {
                $summary[$family]['files_count']++;
            }
        }

        return $summary;
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
        if ($relative === '' || str_contains($relative, "\0") || str_contains($relative, ':') || preg_match('#(^|/)\.\.?(/|$)#', $relative)) {
            return '';
        }
        return $relative;
    }

    /** @param array<int,string> $expected */
    private function obsoleteManagedFiles(string $targetBase, array $expected, array $manifestExcluded = []): array
    {
        $obsolete = [];
        $paths = new StoragePathGuard($targetBase);
        $expectedSet = array_fill_keys(array_map([$this, 'normalizeRelative'], $expected), true);
        foreach (['app', 'bin', 'config', 'data', 'public', 'resources', 'themes', 'storage'] as $root) {
            $absolute = $paths->resolve($root);
            if (!is_dir($absolute)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveCallbackFilterIterator(
                    new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS),
                    function (\SplFileInfo $item) use ($paths, $manifestExcluded): bool {
                        $relative = $paths->relative($item->getPathname());
                        if ($this->isExcluded($relative, false, $manifestExcluded)) { return false; }
                        $paths->resolve($relative);
                        return true;
                    }
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                if (!$item instanceof \SplFileInfo) {
                    continue;
                }
                $relative = $this->normalizeRelative(substr($item->getPathname(), strlen($targetBase) + 1));
                if ($relative === '' || $this->isExcluded($relative, false, $manifestExcluded)) {
                    continue;
                }
                if ($item->isFile() && !isset($expectedSet[$relative])) {
                    $obsolete[] = $relative;
                }
            }
        }
        sort($obsolete, SORT_STRING);
        return $obsolete;
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
        if (!$this->secretCipher->available()) {
            throw new \RuntimeException('backups_full_openssl_required');
        }
    }
}
