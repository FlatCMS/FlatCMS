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

class Response
{
    private int $statusCode = 200;
    private array $headers = [];

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function json(mixed $data, int $status = 200): void
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        
        $this->sendHeaders();
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public function html(string $content, int $status = 200): void
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        
        $this->sendHeaders();
        echo $content;
        exit;
    }

    public function redirect(string $url, int $status = 302): void
    {
        $this->statusCode = $status;
        $this->headers['Location'] = $this->sanitizeRedirectUrl($url);
        
        $this->sendHeaders();
        exit;
    }

    public function back(): void
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer === '') {
            $referer = url('/');
        }
        $this->redirect($referer);
    }

    public function download(string $filePath, ?string $filename = null): void
    {
        if (!file_exists($filePath)) {
            $this->status(404)->html(__('error.not_found', 'Core'));
        }

        $filename = $filename ?? basename($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

        $this->headers['Content-Type'] = $mimeType;
        $this->headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        $this->headers['Content-Length'] = (string) filesize($filePath);

        $this->sendHeaders();
        readfile($filePath);
        exit;
    }

    public function noContent(): void
    {
        $this->statusCode = 204;
        $this->sendHeaders();
        exit;
    }

    private function sendHeaders(): void
    {
        Hook::run('response.before_send', $this);

        if (headers_sent()) {
            return;
        }

        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
    }

    private function sanitizeRedirectUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return url('/');
        }

        if ($url[0] === '?') {
            return url('/') . $url;
        }

        if (str_starts_with($url, '//')) {
            return url('/');
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            $targetHost = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
            $targetPort = parse_url($url, PHP_URL_PORT);
            $targetScheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?? 'http'));

            [$currentHost, $currentPort] = $this->parseHostAndPort((string) ($_SERVER['HTTP_HOST'] ?? ''));
            $currentHost = strtolower($currentHost);

            $targetPort = is_int($targetPort) ? $targetPort : null;
            $currentPort = is_int($currentPort) ? $currentPort : null;

            $currentEffectivePort = $currentPort ?? ($this->isSecureRequest() ? 443 : 80);
            $targetEffectivePort = $targetPort ?? ($targetScheme === 'https' ? 443 : 80);

            if ($targetHost === '' || $currentHost === '' || $targetHost !== $currentHost || $targetEffectivePort !== $currentEffectivePort) {
                return url('/');
            }

            return $url;
        }

        if (!str_starts_with($url, '/')) {
            return '/' . ltrim($url, '/');
        }

        return $url;
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

    /**
     * @return array{0: string, 1: int|null}
     */
    private function parseHostAndPort(string $hostHeader): array
    {
        $hostHeader = trim($hostHeader);
        if ($hostHeader === '') {
            return ['', null];
        }

        // IPv6 host in brackets: [::1]:8080
        if (str_starts_with($hostHeader, '[')) {
            $end = strpos($hostHeader, ']');
            if ($end !== false) {
                $host = substr($hostHeader, 1, $end - 1);
                $rest = substr($hostHeader, $end + 1);
                if (str_starts_with($rest, ':')) {
                    $portRaw = substr($rest, 1);
                    if ($portRaw !== '' && ctype_digit($portRaw)) {
                        return [$host, (int) $portRaw];
                    }
                }
                return [$host, null];
            }
        }

        // Regular host[:port] (only split if there is a single colon).
        $firstColon = strpos($hostHeader, ':');
        $lastColon = strrpos($hostHeader, ':');
        if ($firstColon !== false && $lastColon !== false && $firstColon === $lastColon) {
            $host = substr($hostHeader, 0, $lastColon);
            $portRaw = substr($hostHeader, $lastColon + 1);
            if ($portRaw !== '' && ctype_digit($portRaw)) {
                return [$host, (int) $portRaw];
            }
            return [$hostHeader, null];
        }

        // IPv6 without brackets (port parsing not possible here).
        return [$hostHeader, null];
    }

    public static function make(): self
    {
        return new self();
    }
}
