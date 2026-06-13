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

final class Turnstile
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function isEnabled(): bool
    {
        return (bool) env('TURNSTILE_ENABLED', false);
    }

    public function siteKey(): string
    {
        return trim((string) env('TURNSTILE_SITE_KEY', ''));
    }

    public function secretKey(): string
    {
        return trim((new SecretBox())->decrypt((string) env('TURNSTILE_SECRET_KEY', '')));
    }

    public function verify(string $responseToken, string $remoteIp = ''): array
    {
        $secret = $this->secretKey();
        $responseToken = trim($responseToken);
        $remoteIp = trim($remoteIp);

        if ($secret === '') {
            return [
                'success' => false,
                'error_codes' => ['missing-input-secret'],
            ];
        }

        if ($responseToken === '') {
            return [
                'success' => false,
                'error_codes' => ['missing-input-response'],
            ];
        }

        $payload = http_build_query(array_filter([
            'secret' => $secret,
            'response' => $responseToken,
            'remoteip' => $remoteIp !== '' ? $remoteIp : null,
        ], static fn($v) => $v !== null && $v !== ''), '', '&');

        $json = $this->postForm(self::VERIFY_URL, $payload);
        if ($json === null) {
            return [
                'success' => false,
                'error_codes' => ['turnstile-request-failed'],
            ];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'error_codes' => ['turnstile-invalid-response'],
            ];
        }

        return [
            'success' => (bool) ($decoded['success'] ?? false),
            'error_codes' => is_array($decoded['error-codes'] ?? null) ? $decoded['error-codes'] : [],
        ];
    }

    private function postForm(string $url, string $payload): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 4,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);

            $response = curl_exec($ch);
            $errNo = curl_errno($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false || $errNo !== 0) {
                return null;
            }

            if ($httpCode < 200 || $httpCode >= 300) {
                return null;
            }

            return (string) $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                    'Content-Length: ' . strlen($payload) . "\r\n",
                'content' => $payload,
                'timeout' => 4,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        // Best-effort: ensure HTTP 2xx when headers are available
        $status = (string) ($http_response_header[0] ?? '');
        if ($status !== '' && preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $status, $m) === 1) {
            $code = (int) $m[1];
            if ($code < 200 || $code >= 300) {
                return null;
            }
        }

        return (string) $response;
    }
}
