<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core\Security;

final class SecretBox
{
    private const CIPHER = 'aes-256-gcm';
    private const PREFIX = 'flatcms-secret:v1:';

    private string $storagePath;

    public function __construct(?string $storagePath = null)
    {
        $storageRoot = defined('STORAGE_PATH') ? (string) STORAGE_PATH : (BASE_PATH . '/storage');
        $this->storagePath = $storagePath ?? (rtrim($storageRoot, '/') . '/app/secretbox.key');
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

        if (is_file($this->storagePath)) {
            $stored = trim((string) @file_get_contents($this->storagePath));
            if ($stored !== '') {
                return $stored;
            }
        }

        if (!$createStorageSecret) {
            return '';
        }

        $directory = dirname($this->storagePath);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            return '';
        }

        $generated = base64_encode(random_bytes(32));
        if (@file_put_contents($this->storagePath, $generated, LOCK_EX) === false) {
            return '';
        }

        return $generated;
    }
}
