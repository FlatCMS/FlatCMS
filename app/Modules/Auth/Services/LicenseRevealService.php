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

use App\Core\Mail\Mailer;
use App\Core\Session;

final class LicenseRevealService
{
    private const SESSION_KEY = 'license_reveal_challenge';

    private Session $session;
    private LicenseVaultService $vault;
    private LicenseAuditService $audit;

    public function __construct(
        ?Session $session = null,
        ?LicenseVaultService $vault = null,
        ?LicenseAuditService $audit = null
    ) {
        $this->session = $session ?? app()->session();
        $this->vault = $vault ?? new LicenseVaultService();
        $this->audit = $audit ?? new LicenseAuditService();
    }

    public function beginChallenge(array $user, string $module, string $ipAddress, string $userAgent = ''): array
    {
        $userId = trim((string) ($user['id'] ?? ''));
        $email = trim((string) ($user['email'] ?? ''));
        if ($userId === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failure('license_profile_reveal_unavailable');
        }

        $license = $this->vault->getModuleLicense($module);
        if (trim((string) ($license['license_id'] ?? '')) === '') {
            return $this->failure('license_profile_reveal_unavailable');
        }

        $ttl = $this->envInt('LICENSE_REVEAL_EMAIL_TTL', 600, 60);
        $cooldown = $this->envInt('LICENSE_REVEAL_EMAIL_RESEND_COOLDOWN', 60, 10);
        $maxAttempts = $this->envInt('LICENSE_REVEAL_EMAIL_MAX_ATTEMPTS', 5, 3);
        $existing = $this->currentChallenge();
        $now = time();

        if (
            $existing !== []
            && (string) ($existing['user_id'] ?? '') === $userId
            && (string) ($existing['module'] ?? '') === $module
            && (int) ($existing['expires_at'] ?? 0) > $now
            && (int) ($existing['resend_available_at'] ?? 0) > $now
        ) {
            return [
                'success' => false,
                'message' => __('license_profile_reveal_wait', 'Auth', [
                    'seconds' => (int) ((int) $existing['resend_available_at'] - $now),
                ]),
                'status' => 429,
            ];
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->session->set(self::SESSION_KEY, [
            'user_id' => $userId,
            'module' => $module,
            'license_id' => (string) ($license['license_id'] ?? ''),
            'sent_to' => $email,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'expires_at' => $now + $ttl,
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'resend_available_at' => $now + $cooldown,
            'ip' => $ipAddress,
        ]);

        $this->vault->incrementRevealAttempts($module);
        $this->audit->record('license_reveal_requested', [
            'module' => $module,
            'license_id' => (string) ($license['license_id'] ?? ''),
            'user_id' => $userId,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        $subject = (string) config('app.name', 'FlatCMS') . ' - ' . __('license_profile_email_subject', 'Auth');
        $body = __('license_profile_email_intro', 'Auth') . "\n\n" .
            $code . "\n\n" .
            __('license_profile_email_expires', 'Auth', ['minutes' => (int) ceil($ttl / 60)]) . "\n\n" .
            __('license_profile_email_security', 'Auth');

        $mailer = new Mailer();
        $sent = $mailer->send($email, $subject, $body);
        $devCode = '';

        if (!$sent) {
            if ((bool) env('APP_DEBUG', false)) {
                $devCode = $code;
                $this->audit->record('license_reveal_mail_debug_fallback', [
                    'module' => $module,
                    'license_id' => (string) ($license['license_id'] ?? ''),
                    'user_id' => $userId,
                ]);
            } else {
                $this->session->remove(self::SESSION_KEY);
                return $this->failure('license_profile_reveal_send_failed', 500);
            }
        }

        return [
            'success' => true,
            'message' => __('license_profile_reveal_sent', 'Auth', [
                'email' => $this->maskEmail($email),
            ]),
            'masked_email' => $this->maskEmail($email),
            'expires_in' => $ttl,
            'resend_in' => $cooldown,
            'dev_code' => $devCode,
        ];
    }

    public function verifyChallenge(array $user, string $module, string $code, string $ipAddress, string $userAgent = ''): array
    {
        $challenge = $this->currentChallenge();
        $userId = trim((string) ($user['id'] ?? ''));
        $license = $this->vault->getModuleLicense($module);

        if (
            $challenge === []
            || (string) ($challenge['user_id'] ?? '') !== $userId
            || (string) ($challenge['module'] ?? '') !== $module
        ) {
            return $this->failure('license_profile_reveal_session_missing', 422);
        }

        if ((int) ($challenge['expires_at'] ?? 0) < time()) {
            $this->session->remove(self::SESSION_KEY);
            return $this->failure('license_profile_reveal_expired', 422);
        }

        $attempts = max(0, (int) ($challenge['attempts'] ?? 0));
        $maxAttempts = max(3, (int) ($challenge['max_attempts'] ?? 5));
        if ($attempts >= $maxAttempts) {
            $this->session->remove(self::SESSION_KEY);
            return $this->failure('license_profile_reveal_too_many_attempts', 429);
        }

        if (!password_verify($code, (string) ($challenge['code_hash'] ?? ''))) {
            $challenge['attempts'] = $attempts + 1;
            $this->session->set(self::SESSION_KEY, $challenge);

            $this->audit->record('license_reveal_failed', [
                'module' => $module,
                'license_id' => (string) ($license['license_id'] ?? ''),
                'user_id' => $userId,
                'ip' => $ipAddress,
                'user_agent' => $userAgent,
                'attempts' => (int) $challenge['attempts'],
            ]);

            if ((int) $challenge['attempts'] >= $maxAttempts) {
                $this->session->remove(self::SESSION_KEY);
                return $this->failure('license_profile_reveal_too_many_attempts', 429);
            }

            return $this->failure('license_profile_reveal_invalid_code', 422);
        }

        $plainKey = $this->vault->decryptModuleLicenseKey($module);
        if ($plainKey === '') {
            $this->session->remove(self::SESSION_KEY);
            return $this->failure('license_profile_reveal_unavailable', 404);
        }

        $this->vault->markModuleLicenseRevealed($module, $userId);
        $this->audit->record('license_reveal_success', [
            'module' => $module,
            'license_id' => (string) ($license['license_id'] ?? ''),
            'user_id' => $userId,
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        $this->session->remove(self::SESSION_KEY);

        return [
            'success' => true,
            'message' => __('license_profile_reveal_success', 'Auth'),
            'key' => $plainKey,
            'masked_key' => (string) ($license['masked_key'] ?? ''),
        ];
    }

    private function currentChallenge(): array
    {
        $challenge = $this->session->get(self::SESSION_KEY, []);
        return is_array($challenge) ? $challenge : [];
    }

    private function envInt(string $key, int $default, int $minimum): int
    {
        $value = (int) env($key, $default);
        return $value < $minimum ? $minimum : $value;
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);
        $prefix = substr((string) $local, 0, 2);
        if ($prefix === false) {
            $prefix = '';
        }

        return $prefix . str_repeat('*', max(3, strlen((string) $local) - strlen($prefix))) . '@' . $domain;
    }

    private function failure(string $messageKey, int $status = 422): array
    {
        return [
            'success' => false,
            'message' => __($messageKey, 'Auth'),
            'status' => $status,
        ];
    }
}
