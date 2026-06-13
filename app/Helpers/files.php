<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

if (!function_exists('upload_file')) {
    function upload_file(array $file, string $destination, array $options = []): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowedTypes = $options['types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'pdf', 'doc', 'docx'];
        $maxSize = $options['max_size'] ?? 10 * 1024 * 1024; // 10MB default
        $filename = $options['filename'] ?? null;

        // Check size
        if ($file['size'] > $maxSize) {
            return null;
        }

        // Check extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) {
            return null;
        }

        // Generate filename
        if (!$filename) {
            $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        }

        // Ensure destination directory exists
        $destPath = BASE_PATH . '/' . trim($destination, '/');
        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $fullPath = $destPath . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return $filename;
        }

        return null;
    }
}

if (!function_exists('delete_file')) {
    function delete_file(string $path): bool
    {
        $fullPath = BASE_PATH . '/' . ltrim($path, '/');
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}

if (!function_exists('file_extension')) {
    function file_extension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}

if (!function_exists('is_image')) {
    function is_image(string $filename): bool
    {
        $ext = file_extension($filename);
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp']);
    }
}

if (!function_exists('get_mime_type')) {
    function get_mime_type(string $path): string
    {
        if (file_exists($path)) {
            return mime_content_type($path) ?: 'application/octet-stream';
        }
        
        $ext = file_extension($path);
        $mimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
            'json' => 'application/json',
            'txt' => 'text/plain',
            'css' => 'text/css',
            'js' => 'application/javascript',
        ];
        
        return $mimes[$ext] ?? 'application/octet-stream';
    }
}

if (!function_exists('ensure_directory')) {
    function ensure_directory(string $path): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }
}

if (!function_exists('list_files')) {
    function list_files(string $directory, array $extensions = []): array
    {
        $path = BASE_PATH . '/' . trim($directory, '/');
        
        if (!is_dir($path)) {
            return [];
        }

        $files = [];
        foreach (glob($path . '/*') as $file) {
            if (is_file($file)) {
                if (empty($extensions) || in_array(file_extension($file), $extensions)) {
                    $files[] = basename($file);
                }
            }
        }

        return $files;
    }
}

if (!function_exists('copy_file')) {
    function copy_file(string $source, string $destination): bool
    {
        $sourcePath = BASE_PATH . '/' . ltrim($source, '/');
        $destPath = BASE_PATH . '/' . ltrim($destination, '/');
        
        ensure_directory(dirname($destPath));
        
        return copy($sourcePath, $destPath);
    }
}
