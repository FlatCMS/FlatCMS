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
        return \App\Core\CacheManager::instance()->get($key, $default);
    }
}

if (!function_exists('cache_set')) {
    function cache_set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return \App\Core\CacheManager::instance()->set($key, $value, $ttl);
    }
}

if (!function_exists('cache_forget')) {
    function cache_forget(string $key): bool
    {
        return \App\Core\CacheManager::instance()->forget($key);
    }
}

if (!function_exists('cache_has')) {
    function cache_has(string $key): bool
    {
        return \App\Core\CacheManager::instance()->has($key);
    }
}

if (!function_exists('cache_remember')) {
    function cache_remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return \App\Core\CacheManager::instance()->remember($key, $callback, $ttl);
    }
}

if (!function_exists('cache_clear')) {
    function cache_clear(): bool
    {
        return \App\Core\CacheManager::instance()->clear();
    }
}

if (!function_exists('cache_path')) {
    function cache_path(string $key): string
    {
        return BASE_PATH . '/storage/cache/data/' . md5($key) . '.json';
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
