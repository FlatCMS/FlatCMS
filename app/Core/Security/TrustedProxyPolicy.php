<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/Security/TrustedProxyPolicy.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Core\Security;

final class TrustedProxyPolicy
{
    public const LOCAL_CIDRS = '127.0.0.1/32,::1/128';
    public const ERROR_INVALID_CIDR = 'trusted_proxy_cidr_invalid';
    public const ERROR_CIDR_TOO_BROAD = 'trusted_proxy_cidr_too_broad';

    public static function normalizeCidrs(string $configured): string
    {
        $normalized = [];
        foreach (preg_split('/[\s,]+/', trim($configured)) ?: [] as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            [$network, $prefix] = self::parseCidr($candidate);
            $normalized[] = $network . '/' . $prefix;
        }

        return implode(',', array_values(array_unique($normalized)));
    }

    public static function isTrusted(string $ip, string $configured): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        foreach (preg_split('/[\s,]+/', trim($configured)) ?: [] as $candidate) {
            if ($candidate === '') {
                continue;
            }

            try {
                [$network, $prefix] = self::parseCidr($candidate);
            } catch (\InvalidArgumentException) {
                continue;
            }

            if (self::matches($ip, $network, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public static function shouldTrustHeaders(string $remoteAddress, string $enabled, string $configured): bool
    {
        $trustEnabled = in_array(strtolower(trim($enabled)), ['1', 'true', 'yes', 'on'], true);

        return $trustEnabled && self::isTrusted($remoteAddress, $configured);
    }

    /** @param array<string,mixed> $server */
    public static function isSecureRequest(array $server, string $enabled, string $configured): bool
    {
        if (!empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off') {
            return true;
        }
        if ((string) ($server['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        $requestScheme = strtolower((string) ($server['REQUEST_SCHEME'] ?? ''));
        if ($requestScheme === 'https') {
            return true;
        }

        $remoteAddress = trim((string) ($server['REMOTE_ADDR'] ?? ''));
        if (!self::shouldTrustHeaders($remoteAddress, $enabled, $configured)) {
            return false;
        }

        $forwardedProto = strtolower((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto !== '' && in_array('https', array_map('trim', explode(',', $forwardedProto)), true)) {
            return true;
        }

        $cfVisitor = (string) ($server['HTTP_CF_VISITOR'] ?? '');
        if ($cfVisitor !== '') {
            $decoded = json_decode($cfVisitor, true);
            if (is_array($decoded) && strtolower((string) ($decoded['scheme'] ?? '')) === 'https') {
                return true;
            }
        }

        return false;
    }

    /** @return array{0:string,1:int} */
    private static function parseCidr(string $candidate): array
    {
        [$network, $prefix] = array_pad(explode('/', trim($candidate), 2), 2, null);
        if (filter_var($network, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException(self::ERROR_INVALID_CIDR);
        }

        $binary = inet_pton($network);
        if ($binary === false) {
            throw new \InvalidArgumentException(self::ERROR_INVALID_CIDR);
        }

        $bitCount = strlen($binary) * 8;
        if ($prefix === null || $prefix === '') {
            $prefixLength = $bitCount;
        } elseif (preg_match('/^\d+$/D', $prefix) !== 1) {
            throw new \InvalidArgumentException(self::ERROR_INVALID_CIDR);
        } else {
            $prefixLength = (int) $prefix;
            if ($prefixLength < 0 || $prefixLength > $bitCount) {
                throw new \InvalidArgumentException(self::ERROR_INVALID_CIDR);
            }
        }

        if ($prefixLength === 0) {
            throw new \InvalidArgumentException(self::ERROR_CIDR_TOO_BROAD);
        }

        return [$network, $prefixLength];
    }

    private static function matches(string $ip, string $network, int $prefixLength): bool
    {
        $ipBinary = inet_pton($ip);
        $networkBinary = inet_pton($network);
        if ($ipBinary === false || $networkBinary === false || strlen($ipBinary) !== strlen($networkBinary)) {
            return false;
        }

        $wholeBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;
        if ($wholeBytes > 0 && substr($ipBinary, 0, $wholeBytes) !== substr($networkBinary, 0, $wholeBytes)) {
            return false;
        }
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($ipBinary[$wholeBytes]) & $mask) === (ord($networkBinary[$wholeBytes]) & $mask);
    }
}
