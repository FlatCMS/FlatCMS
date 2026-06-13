<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

if (!function_exists('cache_get')) {
    function cache_get(string $key, mixed $default = null): mixed
    {
        if (!env('CACHE_ENABLED', true)) {
            return $default;
        }

        $path = cache_path($key);
        
        if (!file_exists($path)) {
            return $default;
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }

        // Check expiration
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            cache_forget($key);
            return $default;
        }

        return $data['value'] ?? $default;
    }
}

if (!function_exists('cache_set')) {
    function cache_set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!env('CACHE_ENABLED', true)) {
            return false;
        }

        $path = cache_path($key);
        $dir = dirname($path);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ttl = $ttl ?? (int) env('CACHE_TTL', 3600);
        
        $data = [
            'value' => $value,
            'created_at' => time(),
            'expires_at' => time() + $ttl,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($path, $json, LOCK_EX) !== false;
    }
}

if (!function_exists('cache_forget')) {
    function cache_forget(string $key): bool
    {
        $path = cache_path($key);
        
        if (file_exists($path)) {
            return unlink($path);
        }

        return true;
    }
}

if (!function_exists('cache_has')) {
    function cache_has(string $key): bool
    {
        return cache_get($key, '__CACHE_NULL__') !== '__CACHE_NULL__';
    }
}

if (!function_exists('cache_remember')) {
    function cache_remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = cache_get($key, '__CACHE_NULL__');
        
        if ($value !== '__CACHE_NULL__') {
            return $value;
        }

        $value = $callback();
        cache_set($key, $value, $ttl);
        
        return $value;
    }
}

if (!function_exists('cache_clear')) {
    function cache_clear(): bool
    {
        $cachePath = BASE_PATH . '/storage/cache/data';
        
        if (!is_dir($cachePath)) {
            return true;
        }

        $files = glob($cachePath . '/*.json');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }
}

if (!function_exists('cache_path')) {
    function cache_path(string $key): string
    {
        $filename = md5($key) . '.json';
        return BASE_PATH . '/storage/cache/data/' . $filename;
    }
}

if (!function_exists('view_cache_get')) {
    function view_cache_get(string $key): ?string
    {
        $path = BASE_PATH . '/storage/cache/views/' . md5($key) . '.php';
        
        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return null;
    }
}

if (!function_exists('view_cache_set')) {
    function view_cache_set(string $key, string $content): bool
    {
        $path = BASE_PATH . '/storage/cache/views/' . md5($key) . '.php';
        $dir = dirname($path);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($path, $content, LOCK_EX) !== false;
    }
}
