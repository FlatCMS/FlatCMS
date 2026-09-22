<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Helpers/json.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

if (!function_exists('json_store_for_path')) {
    function json_store_for_path(string $path): \App\Core\Storage\JsonStore
    {
        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $dataRoot = defined('DATA_PATH') ? DATA_PATH : $root . '/data';
        $storageRoot = defined('STORAGE_PATH') ? STORAGE_PATH : $root . '/storage';
        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, rtrim($dataRoot, '/') . '/')) {
            return new \App\Core\Storage\JsonStore($dataRoot);
        }

        return new \App\Core\Storage\JsonStore($root, new \App\Core\Storage\AtomicFileWriter(
            $root,
            new \App\Core\Storage\FileLockManager($storageRoot . '/cache/locks/config-json')
        ));
    }
}

if (!function_exists('json_read')) {
    function json_read(string $path): ?array
    {
        $store = json_store_for_path($path);
        if (!$store->exists($path)) {
            return null;
        }
        return $store->read($path);
    }
}

if (!function_exists('json_write')) {
    function json_write(string $path, array $data): bool
    {
        json_store_for_path($path)->write($path, $data);
        return true;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('json_success')) {
    function json_success(string $message, array $data = []): void
    {
        json_response([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
}

if (!function_exists('json_error')) {
    function json_error(string $message, int $status = 400, array $errors = []): void
    {
        json_response([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}
