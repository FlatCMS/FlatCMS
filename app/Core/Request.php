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

class Request
{
    private array $params = [];
    private ?string $locale = null;
    private string $uri;
    private string $originalUri;

    public function __construct()
    {
        $this->originalUri = $this->parseUri();
        $this->uri = $this->processUri($this->originalUri);
    }

    private function parseUri(): string
    {
        if (isset($_GET['path']) || isset($_GET['route'])) {
            $raw = (string) ($_GET['path'] ?? $_GET['route']);
            $raw = rawurldecode($raw);
            $raw = trim($raw);

            if (str_contains($raw, '?')) {
                [$rawPath, $embeddedQuery] = array_pad(explode('?', $raw, 2), 2, '');
                $raw = trim($rawPath);
                if ($embeddedQuery !== '') {
                    parse_str($embeddedQuery, $embeddedParams);
                    if (is_array($embeddedParams)) {
                        foreach ($embeddedParams as $key => $value) {
                            if (is_string($key) && $key !== '' && !isset($_GET[$key])) {
                                $_GET[$key] = $value;
                            }
                        }
                    }
                }
            }

            if ($raw === '' || $raw === '/') {
                return '/';
            }
            return '/' . ltrim($raw, '/');
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Decode and clean
        $uri = rawurldecode($uri);
        
        // Remove base path (subfolder) from URI
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname(dirname($scriptName)); // Go up from /public/index.php to app root
        if ($basePath !== '/' && $basePath !== '\\' && $basePath !== '.' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        
        $uri = '/' . trim($uri, '/');

        $uri = $uri === '' ? '/' : $uri;

        return $uri;
    }

    private function processUri(string $uri): string
    {
        // Extract locale from first segment
        // Read settings and languages directly to avoid circular dependency with App
        $segments = explode('/', trim($uri, '/'));

        $supportedLocales = $this->detectSupportedLocales();
        $defaultLocale = $this->detectDefaultLocale($supportedLocales);

        if (!empty($segments[0]) && in_array($segments[0], $supportedLocales)) {
            $this->locale = array_shift($segments);
            $uri = '/' . implode('/', $segments);
        } else {
            $this->locale = $defaultLocale;
        }

        return $uri === '' ? '/' : $uri;
    }

    private function detectSupportedLocales(): array
    {
        $locales = [];
        $langDir = BASE_PATH . '/data/languages';

        if (is_dir($langDir)) {
            foreach (glob($langDir . '/*.json') as $file) {
                $locales[] = basename($file, '.json');
            }
        }

        return !empty($locales) ? $locales : ['fr-FR', 'en-US'];
    }

    private function detectDefaultLocale(array $supportedLocales): string
    {
        $settings = \App\Core\FlatFile::settings();

        if (!empty($settings['default_language'])) {
            $lang = $settings['default_language'];
            if (in_array($lang, $supportedLocales)) {
                return $lang;
            }
        }

        return $supportedLocales[0] ?? 'fr-FR';
    }

    public function method(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        
        // Support method override via _method field
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper((string) $_POST['_method']);
        }

        // HEAD doit se comporter comme GET pour le routage.
        if ($method === 'HEAD') {
            return 'GET';
        }
        
        return $method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function originalUri(): string
    {
        return $this->originalUri;
    }

    public function locale(): string
    {
        return $this->locale ?? $this->detectDefaultLocale($this->detectSupportedLocales());
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST, $this->params);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $this->params[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->input($key);
        }
        return $data;
    }

    public function except(array $keys): array
    {
        $data = $this->all();
        foreach ($keys as $key) {
            unset($data[$key]);
        }
        return $data;
    }

    public function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]) || isset($this->params[$key]);
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($contentType, 'application/json');
    }

    public function json(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? $default;
    }

    public function ip(): string
    {
        $cfRay = (string) ($_SERVER['HTTP_CF_RAY'] ?? '');
        $cfConnectingIp = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($cfRay !== '' && filter_var($cfConnectingIp, FILTER_VALIDATE_IP)) {
            return $cfConnectingIp;
        }

        $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if (filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            return $remoteAddr;
        }

        $trustProxy = strtolower((string) ($_ENV['TRUST_PROXY_HEADERS'] ?? '0'));
        if (in_array($trustProxy, ['1', 'true', 'yes', 'on'], true)) {
            $forwardedFor = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
            if ($forwardedFor !== '') {
                foreach (explode(',', $forwardedFor) as $candidate) {
                    $candidate = trim($candidate);
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }

            $realIp = trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''));
            if (filter_var($realIp, FILTER_VALIDATE_IP)) {
                return $realIp;
            }
        }

        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function isSecure(): bool
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

    public function fullUrl(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $requestUri = trim((string) ($_SERVER['REQUEST_URI'] ?? ''));

        if ($requestUri !== '') {
            if (!str_starts_with($requestUri, '/')) {
                $requestUri = '/' . ltrim($requestUri, '/');
            }

            return $scheme . '://' . $host . $requestUri;
        }

        return $scheme . '://' . $host . $this->originalUri;
    }
}
