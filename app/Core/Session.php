<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core;

class Session
{
    private bool $started = false;

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        // Configure session
        $lifetime = (int) env('SESSION_LIFETIME', 120) * 60;
        $secure = $this->resolveCookieSecure($this->isSecureRequest());
        $httpOnly = $this->envBool((string) env('SESSION_COOKIE_HTTPONLY', '1'), true);
        $sameSite = $this->normalizeSameSite((string) env('SESSION_SAMESITE', 'Lax'));
        $cookieName = trim((string) env('SESSION_COOKIE_NAME', 'flatcms_session'));
        if ($cookieName === '') {
            $cookieName = 'flatcms_session';
        }
        session_name($cookieName);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_httponly', $httpOnly ? '1' : '0');
        ini_set('session.cookie_samesite', $sameSite);

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);

        // Set session save path
        $sessionPath = BASE_PATH . '/storage/sessions';
        if (is_dir($sessionPath) && is_writable($sessionPath)) {
            session_save_path($sessionPath);
        }

        session_start();

        // Restore flashed values explicitly kept for the next request.
        if (isset($_SESSION['_flash_keep']) && is_array($_SESSION['_flash_keep'])) {
            $current = $_SESSION['_flash'] ?? [];
            if (!is_array($current)) {
                $current = [];
            }
            $_SESSION['_flash'] = array_merge($_SESSION['_flash_keep'], $current);
            unset($_SESSION['_flash_keep']);
        }
        
        $this->started = true;

        // Regenerate session ID periodically for security
        $this->regenerateIfNeeded();
    }

    private function regenerateIfNeeded(): void
    {
        $regenerateInterval = 300; // 5 minutes
        
        if (!isset($_SESSION['_last_regenerate'])) {
            $_SESSION['_last_regenerate'] = time();
            return;
        }

        if (time() - $_SESSION['_last_regenerate'] > $regenerateInterval) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerate'] = time();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $_SESSION = [];
    }

    public function destroy(): void
    {
        $this->clear();
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            $secure = $this->resolveCookieSecure($this->isSecureRequest());
            $httpOnly = $this->envBool((string) env('SESSION_COOKIE_HTTPONLY', '1'), true);
            $sameSite = $this->normalizeSameSite((string) env('SESSION_SAMESITE', 'Lax'));
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => $sameSite,
            ]);
        }

        $this->started = false;
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_last_regenerate'] = time();
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function keepFlash(string ...$keys): void
    {
        foreach ($keys as $key) {
            if (isset($_SESSION['_flash'][$key])) {
                $_SESSION['_flash_keep'][$key] = $_SESSION['_flash'][$key];
            }
        }
    }

    /**
     * Consume all flash data in one shot (so custom flash keys also work in views).
     *
     * @return array<string, mixed>
     */
    public function consumeFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return is_array($flash) ? $flash : [];
    }

    public function token(): string
    {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_token'];
    }

    public function verifyToken(string $token): bool
    {
        return hash_equals($this->token(), $token);
    }

    public function id(): string
    {
        return session_id();
    }

    public function all(): array
    {
        return $_SESSION;
    }

    private function normalizeSameSite(string $value): string
    {
        $normalized = ucfirst(strtolower(trim($value)));
        return in_array($normalized, ['Lax', 'Strict', 'None'], true) ? $normalized : 'Lax';
    }

    private function envBool(string $value, bool $default): bool
    {
        $v = strtolower(trim($value));
        if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
        return $default;
    }

    private function resolveCookieSecure(bool $isSecureRequest): bool
    {
        $mode = strtolower(trim((string) env('SESSION_COOKIE_SECURE', 'auto')));
        if ($mode === 'auto') {
            return $isSecureRequest;
        }
        return $this->envBool($mode, $isSecureRequest);
    }

    private function isSecureRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto !== '' && in_array('https', array_map('trim', explode(',', $forwardedProto)), true)) {
            return true;
        }

        $requestScheme = strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? ''));
        if ($requestScheme === 'https') {
            return true;
        }

        $cfVisitor = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');
        if ($cfVisitor !== '') {
            $decoded = json_decode($cfVisitor, true);
            if (is_array($decoded) && strtolower((string) ($decoded['scheme'] ?? '')) === 'https') {
                return true;
            }
        }

        return false;
    }
}
