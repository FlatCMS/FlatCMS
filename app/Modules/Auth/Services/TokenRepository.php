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

class TokenRepository
{
    private string $tokensPath;
    private string $attemptsPath;
    private const MAX_ATTEMPTS = 5;
    private const BLOCK_DURATION = 900; // 15 minutes
    private const TOKEN_EXPIRY = 3600; // 1 hour
    private const ATTEMPT_RETENTION = 86400; // 24 hours

    public function __construct()
    {
        $this->tokensPath = BASE_PATH . '/data/core/auth/tokens.json';
        $this->attemptsPath = BASE_PATH . '/data/core/auth/login_attempts.json';
    }

    // --- Reset Tokens ---

    public function createResetToken(string $email): string
    {
        $token = bin2hex(random_bytes(32));
        $tokens = $this->loadTokens();

        // Remove existing tokens for this email
        $tokens = array_filter($tokens, fn($t) => $t['email'] !== $email);

        $tokens[] = [
            'email' => $email,
            'token' => hash('sha256', $token),
            'created_at' => time(),
            'expires_at' => time() + self::TOKEN_EXPIRY,
        ];

        $this->saveTokens(array_values($tokens));

        return $token;
    }

    public function verifyResetToken(string $token): ?array
    {
        $hashedToken = hash('sha256', $token);
        $tokens = $this->loadTokens();

        foreach ($tokens as $entry) {
            if ($entry['token'] === $hashedToken && $entry['expires_at'] > time()) {
                return $entry;
            }
        }

        return null;
    }

    public function deleteToken(string $token): void
    {
        $hashedToken = hash('sha256', $token);
        $tokens = $this->loadTokens();
        $tokens = array_filter($tokens, fn($t) => $t['token'] !== $hashedToken);
        $this->saveTokens(array_values($tokens));
    }

    public function cleanExpiredTokens(): void
    {
        $tokens = $this->loadTokens();
        $tokens = array_filter($tokens, fn($t) => $t['expires_at'] > time());
        $this->saveTokens(array_values($tokens));
    }

    // --- Login Attempts ---

    public function recordLoginAttempt(string $ip, string $email, bool $success): void
    {
        $attempts = $this->loadAttempts();

        $attempts[] = [
            'ip' => $ip,
            'email' => $email,
            'success' => $success,
            'created_at' => time(),
        ];

        $this->saveAttempts($attempts);
    }

    public function countFailedAttempts(string $ip): int
    {
        $attempts = $this->loadAttempts();
        $cutoff = time() - self::BLOCK_DURATION;
        $count = 0;

        foreach ($attempts as $attempt) {
            if ($attempt['ip'] === $ip && !$attempt['success'] && $attempt['created_at'] > $cutoff) {
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
            if ($attempt['ip'] === $ip && !$attempt['success'] && $attempt['created_at'] > $cutoff) {
                $lastFailed = max($lastFailed, $attempt['created_at']);
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
        $attempts = $this->loadAttempts();
        $attempts = array_filter($attempts, fn($a) => $a['ip'] !== $ip);
        $this->saveAttempts(array_values($attempts));
    }

    public function cleanOldAttempts(): void
    {
        $attempts = $this->loadAttempts();
        $cutoff = time() - self::ATTEMPT_RETENTION;
        $attempts = array_filter($attempts, fn($a) => $a['created_at'] > $cutoff);
        $this->saveAttempts(array_values($attempts));
    }

    // --- Storage helpers ---

    private function loadTokens(): array
    {
        if (!file_exists($this->tokensPath)) {
            return [];
        }
        $data = json_decode(file_get_contents($this->tokensPath), true);
        return is_array($data) ? $data : [];
    }

    private function saveTokens(array $tokens): void
    {
        $dir = dirname($this->tokensPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->tokensPath, json_encode($tokens, JSON_PRETTY_PRINT));
    }

    private function loadAttempts(): array
    {
        if (!file_exists($this->attemptsPath)) {
            return [];
        }
        $data = json_decode(file_get_contents($this->attemptsPath), true);
        return is_array($data) ? $data : [];
    }

    private function saveAttempts(array $attempts): void
    {
        $dir = dirname($this->attemptsPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->attemptsPath, json_encode($attempts, JSON_PRETTY_PRINT));
    }
}
