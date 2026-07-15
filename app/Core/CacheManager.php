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

class CacheManager
{
    private static ?self $instance = null;
    private array $memory = [];

    private function __construct() {}

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!env('CACHE_ENABLED', true)) {
            return $default;
        }

        if (array_key_exists($key, $this->memory)) {
            return $this->memory[$key];
        }

        $path = $this->cachePath($key);
        if (!file_exists($path)) {
            return $default;
        }

        $handle = @fopen($path, 'r');
        if (!$handle) {
            return $default;
        }

        flock($handle, LOCK_SH);
        $content = stream_get_contents($handle);
        fclose($handle);

        if ($content === false) {
            return $default;
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }

        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            $this->forget($key);
            error_log(sprintf('[FlatCMS][Cache] expired: %s', $key));
            return $default;
        }

        $value = $data['value'] ?? $default;
        $this->memory[$key] = $value;
        return $value;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!env('CACHE_ENABLED', true)) {
            return false;
        }

        $ttl = $ttl ?? (int) env('CACHE_TTL', 3600);
        $this->memory[$key] = $value;

        $path = $this->cachePath($key);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $payload = [
            'value' => $value,
            'created_at' => time(),
            'expires_at' => time() + $ttl,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $tmp = $path . '.tmp.' . getmypid();
        $written = @file_put_contents($tmp, $json);

        if ($written !== false) {
            @rename($tmp, $path);
            return true;
        }

        @unlink($tmp);
        return false;
    }

    public function forget(string $key): bool
    {
        unset($this->memory[$key]);
        $path = $this->cachePath($key);
        if (file_exists($path)) {
            return @unlink($path);
        }
        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key, '__CACHE_NULL__') !== '__CACHE_NULL__';
    }

    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key, '__CACHE_NULL__');
        if ($value !== '__CACHE_NULL__') {
            return $value;
        }
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function clear(): bool
    {
        $this->memory = [];

        $cachePath = BASE_PATH . '/storage/cache/data';
        if (!is_dir($cachePath)) {
            return true;
        }

        $files = glob($cachePath . '/*.json');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        return true;
    }

    public function cleanup(): int
    {
        $cachePath = BASE_PATH . '/storage/cache/data';
        if (!is_dir($cachePath)) {
            return 0;
        }

        $removed = 0;
        $now = time();
        $files = glob($cachePath . '/*.json');
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $handle = @fopen($file, 'r');
            if (!$handle) {
                continue;
            }

            flock($handle, LOCK_SH);
            $content = stream_get_contents($handle);
            fclose($handle);

            if ($content === false) {
                @unlink($file);
                $removed++;
                continue;
            }

            $data = json_decode($content, true);
            if (!is_array($data)) {
                @unlink($file);
                $removed++;
                continue;
            }

            if (isset($data['expires_at']) && $data['expires_at'] < $now) {
                @unlink($file);
                $removed++;
            }
        }

        $this->updateCleanupTimestamp();

        return $removed;
    }

    private static ?int $lastCleanup = null;

    public function autoCleanup(): int
    {
        $interval = 3600;

        // In-memory check first (no I/O)
        if (self::$lastCleanup !== null && (time() - self::$lastCleanup) < $interval) {
            return 0;
        }

        // File check (1 I/O per request max)
        $stampFile = BASE_PATH . '/storage/cache/.last_cleanup';
        if (file_exists($stampFile)) {
            $last = (int) @file_get_contents($stampFile);
            self::$lastCleanup = $last;
            if ((time() - $last) < $interval) {
                return 0;
            }
        }

        $removed = $this->cleanup();
        self::$lastCleanup = time();
        return $removed;
    }

    private function updateCleanupTimestamp(): void
    {
        $dir = BASE_PATH . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($dir . '/.last_cleanup', (string) time());
    }

    public function flush(): void
    {
        IndexManager::instance()->flush();
    }

    private function cachePath(string $key): string
    {
        return BASE_PATH . '/storage/cache/data/' . md5($key) . '.json';
    }
}
