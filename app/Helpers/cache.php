<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Core\Storage\CacheStore;

if (!function_exists('cache_store')) {
    function cache_store(): CacheStore
    {
        static $store = null;

        return $store ??= new CacheStore(BASE_PATH . '/storage/cache/data');
    }
}

if (!function_exists('view_cache_store')) {
    function view_cache_store(): CacheStore
    {
        static $store = null;

        return $store ??= new CacheStore(BASE_PATH . '/storage/cache/views');
    }
}

if (!function_exists('cache_get')) {
    function cache_get(string $key, mixed $default = null): mixed
    {
        if (!env('CACHE_ENABLED', true)) {
            return $default;
        }

        try {
            $data = cache_store()->readJson($key);
        } catch (\Throwable) {
            return $default;
        }

        if (!is_array($data)) {
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

        $ttl = $ttl ?? (int) env('CACHE_TTL', 3600);
        
        $data = [
            'value' => $value,
            'created_at' => time(),
            'expires_at' => time() + $ttl,
        ];

        try {
            cache_store()->writeJson($key, $data);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

if (!function_exists('cache_forget')) {
    function cache_forget(string $key): bool
    {
        try {
            cache_store()->forget($key);
            return true;
        } catch (\Throwable) {
            return false;
        }
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
        try {
            cache_store()->clear();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

if (!function_exists('cache_path')) {
    function cache_path(string $key): string
    {
        return cache_store()->pathFor($key);
    }
}

if (!function_exists('view_cache_get')) {
    function view_cache_get(string $key): ?string
    {
        try {
            return view_cache_store()->readText($key);
        } catch (\Throwable) {
            return null;
        }
    }
}

if (!function_exists('view_cache_set')) {
    function view_cache_set(string $key, string $content): bool
    {
        try {
            view_cache_store()->writeText($key, $content);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
