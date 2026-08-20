<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateCacheService
{
    private string $path;
    private int $ttl;

    public function __construct(?string $path = null, ?int $ttl = null)
    {
        $this->path = $path ?? (BASE_PATH . '/storage/cache/update-manager/status.json');
        $configuredTtl = $ttl ?? (int) env('FLATCMS_UPDATE_CHECK_TTL', 86400);
        $this->ttl = max(300, min(604800, $configuredTtl));
    }

    /** @return array<string, mixed>|null */
    public function read(): ?array
    {
        if (!is_file($this->path)) {
            return null;
        }

        $raw = @file_get_contents($this->path);
        $payload = json_decode((string) $raw, true);
        return is_array($payload) ? $payload : null;
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
        $directory = dirname($this->path);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('update_cache_directory_failed');
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            throw new \RuntimeException('update_cache_encode_failed');
        }

        $temporary = $this->path . '.tmp-' . bin2hex(random_bytes(4));
        if (@file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('update_cache_write_failed');
        }

        if (!@rename($temporary, $this->path)) {
            @unlink($temporary);
            throw new \RuntimeException('update_cache_commit_failed');
        }
    }

    public function clear(): void
    {
        if (is_file($this->path) && !@unlink($this->path)) {
            throw new \RuntimeException('update_cache_clear_failed');
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
