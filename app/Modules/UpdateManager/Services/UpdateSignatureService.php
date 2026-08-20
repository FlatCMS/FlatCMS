<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateSignatureService
{
    /** @var array<int,string> */
    private array $publicKeys = [];
    private int $algorithm;

    public function __construct(?string $publicKey = null, ?int $algorithm = null)
    {
        $signing = is_file(BASE_PATH . '/app/Modules/UpdateManager/Config/signing.php')
            ? require BASE_PATH . '/app/Modules/UpdateManager/Config/signing.php'
            : [];

        if ($publicKey !== null && trim($publicKey) !== '') {
            $this->publicKeys = [trim($publicKey)];
        } else {
            $keyFile = trim((string) env('FLATCMS_UPDATE_PUBLIC_KEY_FILE', ''));
            $fromFile = $keyFile !== '' && is_file($keyFile) ? @file_get_contents($keyFile) : false;
            $fromEnv = trim((string) env('FLATCMS_UPDATE_PUBLIC_KEY', ''));
            if (is_string($fromFile) && trim($fromFile) !== '') {
                $this->publicKeys = [trim($fromFile)];
            } elseif ($fromEnv !== '') {
                $this->publicKeys = [$fromEnv];
            } else {
                $configured = is_array($signing['public_keys'] ?? null) ? $signing['public_keys'] : [];
                if ($configured === [] && trim((string) ($signing['public_key'] ?? '')) !== '') {
                    $configured[] = (string) $signing['public_key'];
                }
                foreach ($configured as $key) {
                    $key = trim((string) $key);
                    if ($key !== '' && !in_array($key, $this->publicKeys, true)) {
                        $this->publicKeys[] = $key;
                    }
                }
            }
        }

        $this->algorithm = $algorithm ?? $this->resolveAlgorithm((string) ($signing['algorithm'] ?? 'sha256'));
    }

    /** @param array<string, mixed> $package */
    public function verify(array $package): bool
    {
        if (!extension_loaded('openssl') || $this->publicKeys === []) {
            return false;
        }

        $signature = trim((string) ($package['signature'] ?? ''));
        $sha256 = strtolower(trim((string) ($package['sha256'] ?? '')));
        if ($signature === '' || preg_match('/^[a-f0-9]{64}$/', $sha256) !== 1) {
            return false;
        }

        $decoded = base64_decode($signature, true);
        if ($decoded === false) {
            return false;
        }

        $payload = self::payload($package);
        foreach ($this->publicKeys as $key) {
            if (openssl_verify($payload, $decoded, $key, $this->algorithm) === 1) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string, mixed> $package */
    public static function payload(array $package): string
    {
        return implode("\n", [
            'flatcms-update-signature-v1',
            strtolower(trim((string) ($package['catalog'] ?? ''))),
            trim((string) ($package['slug'] ?? '')),
            trim((string) ($package['version'] ?? $package['compatible_version'] ?? '')),
            strtolower(trim((string) ($package['sha256'] ?? ''))),
            '',
        ]);
    }

    private function resolveAlgorithm(string $default): int
    {
        return match (strtolower((string) env('FLATCMS_UPDATE_SIGNATURE_ALGO', $default))) {
            'sha384' => OPENSSL_ALGO_SHA384,
            'sha512' => OPENSSL_ALGO_SHA512,
            default => OPENSSL_ALGO_SHA256,
        };
    }
}
