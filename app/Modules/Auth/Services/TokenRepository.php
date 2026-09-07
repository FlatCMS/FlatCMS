<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\JsonStore;
use App\Core\Storage\StorageException;

class TokenRepository
{
    private JsonStore $store;
    private const MAX_ATTEMPTS = 5;
    private const BLOCK_DURATION = 900; // 15 minutes
    private const TOKEN_EXPIRY = 3600; // 1 hour
    private const ATTEMPT_RETENTION = 86400; // 24 hours

    public function __construct(?string $authPath = null, ?JsonStore $store = null)
    {
        $authPath ??= BASE_PATH . '/data/core/auth';

        if ($store === null) {
            $locks = new FileLockManager(BASE_PATH . '/storage/cache/locks/auth');
            $store = new JsonStore($authPath, new AtomicFileWriter($authPath, $locks));
        }

        $this->store = $store;
    }

    // --- Reset Tokens ---

    public function createResetToken(string $email): string
    {
        $token = bin2hex(random_bytes(32));
        $now = time();

        $this->mutateTokens(static function (array $tokens) use ($email, $token, $now): array {
            // A reset request replaces only the previous token for this address.
            $tokens = array_values(array_filter(
                $tokens,
                static fn (array $entry): bool => (string) ($entry['email'] ?? '') !== $email
            ));
            $tokens[] = [
                'email' => $email,
                'token' => hash('sha256', $token),
                'created_at' => $now,
                'expires_at' => $now + self::TOKEN_EXPIRY,
            ];

            return $tokens;
        });

        return $token;
    }

    public function verifyResetToken(string $token): ?array
    {
        $hashedToken = hash('sha256', $token);
        $tokens = $this->loadTokens();

        foreach ($tokens as $entry) {
            if (hash_equals((string) ($entry['token'] ?? ''), $hashedToken)
                && (int) ($entry['expires_at'] ?? 0) > time()) {
                return $entry;
            }
        }

        return null;
    }

    public function deleteToken(string $token): void
    {
        $hashedToken = hash('sha256', $token);
        $this->mutateTokens(static function (array $tokens) use ($hashedToken): array {
            return array_values(array_filter(
                $tokens,
                static fn (array $entry): bool => !hash_equals((string) ($entry['token'] ?? ''), $hashedToken)
            ));
        });
    }

    public function cleanExpiredTokens(): void
    {
        $now = time();
        $this->mutateTokens(static function (array $tokens) use ($now): array {
            return array_values(array_filter(
                $tokens,
                static fn (array $entry): bool => (int) ($entry['expires_at'] ?? 0) > $now
            ));
        });
    }

    // --- Login Attempts ---

    public function recordLoginAttempt(string $ip, string $email, bool $success): void
    {
        $now = time();
        $this->mutateAttempts(static function (array $attempts) use ($ip, $email, $success, $now): array {
            $attempts[] = [
                'ip' => $ip,
                'email' => $email,
                'success' => $success,
                'created_at' => $now,
            ];

            return $attempts;
        });
    }

    public function countFailedAttempts(string $ip): int
    {
        $attempts = $this->loadAttempts();
        $cutoff = time() - self::BLOCK_DURATION;
        $count = 0;

        foreach ($attempts as $attempt) {
            if ((string) ($attempt['ip'] ?? '') === $ip
                && !(bool) ($attempt['success'] ?? false)
                && (int) ($attempt['created_at'] ?? 0) > $cutoff) {
                $count++;
            }
        }

        return $count;
    }

    public function isBlocked(string $ip): bool
    {
        return $this->countFailedAttempts($ip) >= self::MAX_ATTEMPTS;
    }

    public function getRemainingBlockTime(string $ip): int
    {
        $attempts = $this->loadAttempts();
        $cutoff = time() - self::BLOCK_DURATION;
        $lastFailed = 0;

        foreach ($attempts as $attempt) {
            if ((string) ($attempt['ip'] ?? '') === $ip
                && !(bool) ($attempt['success'] ?? false)
                && (int) ($attempt['created_at'] ?? 0) > $cutoff) {
                $lastFailed = max($lastFailed, (int) ($attempt['created_at'] ?? 0));
            }
        }

        if ($lastFailed === 0) {
            return 0;
        }

        $unblockAt = $lastFailed + self::BLOCK_DURATION;
        return max(0, $unblockAt - time());
    }

    public function clearAttempts(string $ip): void
    {
        $this->mutateAttempts(static function (array $attempts) use ($ip): array {
            return array_values(array_filter(
                $attempts,
                static fn (array $entry): bool => (string) ($entry['ip'] ?? '') !== $ip
            ));
        });
    }

    public function cleanOldAttempts(): void
    {
        $cutoff = time() - self::ATTEMPT_RETENTION;
        $this->mutateAttempts(static function (array $attempts) use ($cutoff): array {
            return array_values(array_filter(
                $attempts,
                static fn (array $entry): bool => (int) ($entry['created_at'] ?? 0) > $cutoff
            ));
        });
    }

    // --- Storage helpers ---

    private function loadTokens(): array
    {
        return $this->normalizeRecords($this->store->read('tokens.json', []));
    }

    private function mutateTokens(callable $mutation): void
    {
        $this->mutateRecords('tokens.json', $mutation);
    }

    private function loadAttempts(): array
    {
        return $this->normalizeRecords($this->store->read('login_attempts.json', []));
    }

    private function mutateAttempts(callable $mutation): void
    {
        $this->mutateRecords('login_attempts.json', $mutation);
    }

    private function mutateRecords(string $path, callable $mutation): void
    {
        $this->store->mutate($path, function (array $records) use ($mutation): array {
            $updated = $mutation($this->normalizeRecords($records));
            if (!is_array($updated)) {
                throw new StorageException('Authentication record mutation must return an array.');
            }

            return $this->normalizeRecords($updated);
        }, []);
    }

    /**
     * @param array<int|string, mixed> $records
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRecords(array $records): array
    {
        $normalized = [];
        foreach ($records as $record) {
            if (is_array($record)) {
                $normalized[] = $record;
            }
        }

        return $normalized;
    }
}
