<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

if (!function_exists('json_read')) {
    function json_read(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $data : null;
    }
}

if (!function_exists('json_write')) {
    function json_write(string $path, array $data): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($path, $json, LOCK_EX) !== false;
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
