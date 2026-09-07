<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\JsonStore;
use App\Core\Storage\StorageException;

final class UpdateCacheService
{
    private string $path;
    private string $fileName;
    private int $ttl;
    private JsonStore $store;

    public function __construct(
        ?string $path = null,
        ?int $ttl = null,
        ?JsonStore $store = null,
        ?string $lockRoot = null
    ) {
        $path ??= BASE_PATH . '/storage/cache/update-manager/status.json';
        $cacheRoot = dirname($path);
        $lockRoot ??= dirname($cacheRoot) . '/locks/update-manager';
        $this->store = $store ?? new JsonStore(
            $cacheRoot,
            new AtomicFileWriter($cacheRoot, new FileLockManager($lockRoot))
        );
        $this->fileName = basename($path);
        $this->path = $this->store->root() . '/' . $this->fileName;
        $configuredTtl = $ttl ?? (int) env('FLATCMS_UPDATE_CHECK_TTL', 86400);
        $this->ttl = max(300, min(604800, $configuredTtl));
    }

    /** @return array<string, mixed>|null */
    public function read(): ?array
    {
        if (!is_file($this->path)) {
            return null;
        }

        try {
            $payload = $this->store->read($this->fileName);
        } catch (StorageException) {
            return null;
        }

        return $payload === [] ? null : $payload;
    }

    public function isFresh(?array $payload = null): bool
    {
        $payload ??= $this->read();
        if (!is_array($payload)) {
            return false;
        }

        $checkedAt = trim((string) ($payload['checked_at'] ?? ''));
        $timestamp = $checkedAt !== '' ? strtotime($checkedAt) : false;
        if ($timestamp === false) {
            return false;
        }

        $hasErrors = is_array($payload['errors'] ?? null) && $payload['errors'] !== [];
        $effectiveTtl = $hasErrors ? min($this->ttl, 3600) : $this->ttl;

        return (time() - $timestamp) < $effectiveTtl;
    }

    /** @param array<string, mixed> $payload */
    public function write(array $payload): void
    {
        try {
            $this->store->write($this->fileName, $payload);
        } catch (StorageException $exception) {
            throw new \RuntimeException('update_cache_write_failed', 0, $exception);
        }
    }

    public function clear(): void
    {
        try {
            $this->store->delete($this->fileName);
        } catch (StorageException $exception) {
            throw new \RuntimeException('update_cache_clear_failed', 0, $exception);
        }
    }

    public function path(): string
    {
        return $this->path;
    }

    public function ttl(): int
    {
        return $this->ttl;
    }
}
