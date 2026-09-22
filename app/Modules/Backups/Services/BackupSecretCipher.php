<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Backups/Services/BackupSecretCipher.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Backups\Services;

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\StoragePathGuard;

final class BackupSecretCipher
{
    private const CIPHER = 'aes-256-gcm';

    private string $basePath;
    private string $keyRoot;
    private string $errorPrefix;
    private AtomicFileWriter $writer;

    public function __construct(string $basePath, string $keyRoot, string $lockScope, string $errorPrefix)
    {
        $rawBase = rtrim(str_replace('\\', '/', $basePath), '/');
        $rawKeyRoot = rtrim(str_replace('\\', '/', $keyRoot), '/');
        $guard = new StoragePathGuard($basePath);
        $this->basePath = $guard->root();
        if ($rawKeyRoot === $rawBase || str_starts_with($rawKeyRoot, $rawBase . '/')) {
            $rawKeyRoot = ltrim(substr($rawKeyRoot, strlen($rawBase)), '/');
        }
        $this->keyRoot = $guard->resolve($rawKeyRoot);
        $this->errorPrefix = rtrim($errorPrefix, '_');
        $this->writer = new AtomicFileWriter(
            $this->keyRoot,
            new FileLockManager($this->basePath . '/storage/cache/locks/' . trim($lockScope, '/')),
            0700,
            0600
        );
    }

    public function available(): bool
    {
        return function_exists('openssl_encrypt') && function_exists('openssl_decrypt');
    }

    public function generate(): string
    {
        if (!$this->available()) {
            throw new \RuntimeException($this->error('openssl_required'));
        }

        return random_bytes(32);
    }

    public function persist(string $filename, string $key): string
    {
        if (strlen($key) !== 32 || basename($filename) !== $filename
            || preg_match('/^[A-Za-z0-9._-]+\.key$/D', $filename) !== 1) {
            throw new \RuntimeException($this->error('key_invalid'));
        }

        $path = $this->keyRoot . '/' . $filename;
        if (file_exists($path)) {
            throw new \RuntimeException($this->error('key_exists'));
        }

        try {
            $this->writer->write($filename, base64_encode($key));
        } catch (\Throwable $exception) {
            throw new \RuntimeException($this->error('key_write_failed'), 0, $exception);
        }

        return $path;
    }

    public function read(string $keyPath): string
    {
        $guard = new StoragePathGuard($this->basePath);
        $canonical = realpath($keyPath);
        if ($canonical === false || !is_file($canonical)) {
            throw new \RuntimeException($this->error('key_invalid'));
        }
        $resolved = $guard->resolve($canonical);
        $raw = trim((string) @file_get_contents($resolved));
        $key = base64_decode($raw, true);
        if (!is_string($key) || strlen($key) !== 32) {
            throw new \RuntimeException($this->error('key_invalid'));
        }

        return $key;
    }

    /** @return array{iv:string,tag:string,cipher:string} */
    public function encrypt(string $plain, string $key): array
    {
        if (!$this->available() || strlen($key) !== 32) {
            throw new \RuntimeException($this->error('secret_encrypt_failed'));
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if (!is_string($cipher)) {
            throw new \RuntimeException($this->error('secret_encrypt_failed'));
        }

        return [
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'cipher' => base64_encode($cipher),
        ];
    }

    /** @param array<string,mixed> $payload */
    public function decrypt(array $payload, string $key): string
    {
        if (!$this->available() || strlen($key) !== 32) {
            throw new \RuntimeException($this->error('secret_decrypt_failed'));
        }

        $iv = base64_decode((string) ($payload['iv'] ?? ''), true);
        $tag = base64_decode((string) ($payload['tag'] ?? ''), true);
        $cipher = base64_decode((string) ($payload['cipher'] ?? ''), true);
        if (!is_string($iv) || strlen($iv) !== 12 || !is_string($tag) || strlen($tag) !== 16 || !is_string($cipher)) {
            throw new \RuntimeException($this->error('secret_invalid'));
        }

        $plain = openssl_decrypt($cipher, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if (!is_string($plain)) {
            throw new \RuntimeException($this->error('secret_decrypt_failed'));
        }

        return $plain;
    }

    public function entryName(string $relative): string
    {
        return rtrim(strtr(base64_encode($relative), '+/', '-_'), '=');
    }

    private function error(string $suffix): string
    {
        return $this->errorPrefix . '_' . $suffix;
    }
}
