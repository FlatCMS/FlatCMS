<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Extensions\GoogleForms\Services;

use App\Core\I18n;
use App\Core\Security\SecretBox;

final class GoogleFormsCryptoService
{
    private string $key;

    public function __construct()
    {
        I18n::load('GoogleForms');
        $this->key = $this->resolveKey();
    }

    private function requireKey(): string
    {
        if ($this->key === '') {
            throw new \RuntimeException(__('google_forms_error_encryption_key_missing', 'GoogleForms'));
        }

        return $this->key;
    }

    private function resolveKey(): string
    {
        $secretBox = new SecretBox();

        foreach ([
            'GOOGLE_OAUTH_ENCRYPTION_KEY',
            'GOOGLE_FORMS_ENCRYPTION_KEY',
            'FLATCMS_ENCRYPTION_KEY',
            'APP_KEY',
        ] as $key) {
            $value = trim((string) env($key, ''));

            if ($value === '') {
                continue;
            }

            if (str_starts_with($value, 'flatcms-secret:v1:')) {
                $value = trim($secretBox->decrypt($value));
            }

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    public function encrypt(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (!extension_loaded('openssl')) {
            return 'plain:' . base64_encode($value);
        }

        $iv = random_bytes(16);
        $key = hash('sha256', $this->requireKey(), true);
        $cipher = openssl_encrypt($value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($cipher === false) {
            return 'plain:' . base64_encode($value);
        }

        return 'enc:' . base64_encode($iv . $cipher);
    }

    public function decrypt(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'plain:')) {
            return base64_decode(substr($value, 6), true) ?: '';
        }

        if (!str_starts_with($value, 'enc:') || !extension_loaded('openssl')) {
            return $value;
        }

        $raw = base64_decode(substr($value, 4), true);

        if ($raw === false || strlen($raw) <= 16) {
            return '';
        }

        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $key = hash('sha256', $this->requireKey(), true);

        return openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv) ?: '';
    }
}
