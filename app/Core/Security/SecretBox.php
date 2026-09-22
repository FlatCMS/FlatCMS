<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/Security/SecretBox.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Core\Security;

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;

final class SecretBox
{
    private const CIPHER = 'aes-256-gcm';
    private const PREFIX = 'flatcms-secret:v1:';

    private string $storagePath;
    private string $fileName;
    private AtomicFileWriter $writer;

    public function __construct(
        ?string $storagePath = null,
        ?AtomicFileWriter $writer = null,
        ?string $lockRoot = null
    )
    {
        $applicationRoot = defined('BASE_PATH') ? (string) BASE_PATH : dirname(__DIR__, 3);
        $storageRoot = defined('STORAGE_PATH') ? (string) STORAGE_PATH : ($applicationRoot . '/storage');
        $storagePath ??= rtrim($storageRoot, '/') . '/app/secretbox.key';
        $secretRoot = dirname($storagePath);
        $lockRoot ??= dirname($secretRoot) . '/cache/locks/secrets';
        $this->writer = $writer ?? new AtomicFileWriter(
            $secretRoot,
            new FileLockManager($lockRoot),
            0700,
            0600
        );
        $this->fileName = basename($storagePath);
        $this->storagePath = $this->writer->root() . '/' . $this->fileName;
    }

    public function storagePath(): string
    {
        return $this->storagePath;
    }

    public function isEncrypted(string $value): bool
    {
        return str_starts_with(trim($value), self::PREFIX);
    }

    public function encrypt(string $plainText): string
    {
        $plainText = trim($plainText);
        if ($plainText === '' || $this->isEncrypted($plainText)) {
            return $plainText;
        }

        if (!function_exists('openssl_encrypt')) {
            return $plainText;
        }

        $secret = $this->resolveSecret(true);
        if ($secret === '') {
            return $plainText;
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            hash('sha256', $secret, true),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if (!is_string($cipherText) || $cipherText === '') {
            return $plainText;
        }

        $payload = json_encode([
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'cipher' => base64_encode($cipherText),
        ], JSON_UNESCAPED_SLASHES);

        if (!is_string($payload) || $payload === '') {
            return $plainText;
        }

        return self::PREFIX . base64_encode($payload);
    }

    public function decrypt(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (!$this->isEncrypted($value)) {
            return $value;
        }

        if (!function_exists('openssl_decrypt')) {
            return '';
        }

        $secret = $this->resolveSecret(false);
        if ($secret === '') {
            return '';
        }

        $payload = substr($value, strlen(self::PREFIX));
        $decodedPayload = base64_decode($payload, true);
        if (!is_string($decodedPayload) || $decodedPayload === '') {
            return '';
        }

        $decoded = json_decode($decodedPayload, true);
        if (!is_array($decoded)) {
            return '';
        }

        $iv = base64_decode((string) ($decoded['iv'] ?? ''), true);
        $tag = base64_decode((string) ($decoded['tag'] ?? ''), true);
        $cipher = base64_decode((string) ($decoded['cipher'] ?? ''), true);
        if (!is_string($iv) || !is_string($tag) || !is_string($cipher)) {
            return '';
        }

        $plainText = openssl_decrypt(
            $cipher,
            self::CIPHER,
            hash('sha256', $secret, true),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return is_string($plainText) ? $plainText : '';
    }

    public function normalizeStoredValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ($this->isEncrypted($value)) {
            return $value;
        }

        return $this->encrypt($value);
    }

    private function resolveSecret(bool $createStorageSecret): string
    {
        $configured = trim((string) env('FLATCMS_SECRETS_KEY', ''));
        if ($configured !== '') {
            return $configured;
        }

        $configured = trim((string) env('APP_KEY', ''));
        if ($configured !== '') {
            return $configured;
        }

        $configured = trim((string) env('FLATCMS_LICENSE_VAULT_KEY', ''));
        if ($configured !== '') {
            return $configured;
        }

        try {
            return $this->writer->synchronized($this->fileName, function () use ($createStorageSecret): string {
                $target = $this->writer->resolvePath($this->fileName);
                if (is_file($target)) {
                    $stored = file_get_contents($target);
                    if (is_string($stored) && trim($stored) !== '') {
                        @chmod($target, 0600);
                        return trim($stored);
                    }
                } elseif (file_exists($target) || is_link($target)) {
                    return '';
                }

                if (!$createStorageSecret) {
                    return '';
                }

                $generated = base64_encode(random_bytes(32));
                $this->writer->write($this->fileName, $generated);
                return $generated;
            });
        } catch (\Throwable) {
            return '';
        }
    }
}
