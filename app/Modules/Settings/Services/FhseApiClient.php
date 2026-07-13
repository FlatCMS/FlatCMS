<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Settings\Services;

final class FhseApiClient
{
    private const DEFAULT_BASE_URL = 'http://127.0.0.1:8080';

    /**
     * @return array{ok:bool,capabilities?:array<string,mixed>,error?:string,details?:string}
     */
    public function getCapabilities(): array
    {
        return $this->request('GET', '/api/fhse/capabilities', null, 5);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,capabilities?:array<string,mixed>,result?:string,error?:string,details?:string}
     */
    public function configureTunnel(array $payload): array
    {
        return $this->request('POST', '/api/fhse/cloudflare/tunnel/configure', $payload, 30);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,capabilities?:array<string,mixed>,result?:string,error?:string,details?:string}
     */
    public function enableTunnel(array $payload): array
    {
        return $this->request('POST', '/api/fhse/cloudflare/tunnel/enable', $payload, 180);
    }

    /**
     * @return array{ok:bool,capabilities?:array<string,mixed>,result?:string,error?:string,details?:string}
     */
    public function disableTunnel(): array
    {
        return $this->request('POST', '/api/fhse/cloudflare/tunnel/disable', [], 30);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,capabilities?:array<string,mixed>,result?:string,error?:string,details?:string}
     */
    public function restartTunnel(array $payload): array
    {
        return $this->request('POST', '/api/fhse/cloudflare/tunnel/restart', $payload, 45);
    }

    /**
     * @param array<string,mixed>|null $payload
     * @return array<string,mixed>
     */
    private function request(string $method, string $path, ?array $payload, int $timeoutSeconds): array
    {
        $url = rtrim($this->baseUrl(), '/') . $path;
        $body = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload !== null && !is_string($body)) {
            return [
                'ok' => false,
                'error' => 'fhse_payload_encode_failed',
            ];
        }

        if (function_exists('curl_init')) {
            return $this->requestWithCurl($method, $url, $body, $timeoutSeconds);
        }

        return $this->requestWithStream($method, $url, $body, $timeoutSeconds);
    }

    private function baseUrl(): string
    {
        $candidate = trim((string) env('FHSE_API_BASE_URL', self::DEFAULT_BASE_URL));

        return $candidate !== '' ? $candidate : self::DEFAULT_BASE_URL;
    }

    /**
     * @return array<string,mixed>
     */
    private function requestWithCurl(string $method, string $url, ?string $body, int $timeoutSeconds): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return [
                'ok' => false,
                'error' => 'fhse_transport_unavailable',
            ];
        }

        $headers = [
            'Accept: application/json',
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeoutSeconds),
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_FAILONERROR => false,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $error !== '') {
            return [
                'ok' => false,
                'error' => 'fhse_api_unreachable',
                'details' => $error,
            ];
        }

        return $this->decodeResponse((string) $raw, $httpCode);
    }

    /**
     * @return array<string,mixed>
     */
    private function requestWithStream(string $method, string $url, ?string $body, int $timeoutSeconds): array
    {
        $headers = [
            'Accept: application/json',
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($body);
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
                'timeout' => $timeoutSeconds,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            return [
                'ok' => false,
                'error' => 'fhse_api_unreachable',
            ];
        }

        $httpCode = 0;
        foreach ((array) ($http_response_header ?? []) as $headerLine) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~', (string) $headerLine, $matches) === 1) {
                $httpCode = (int) $matches[1];
                break;
            }
        }

        return $this->decodeResponse((string) $raw, $httpCode);
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeResponse(string $raw, int $httpCode): array
    {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'error' => 'fhse_invalid_response',
                'details' => $raw,
            ];
        }

        $ok = !empty($decoded['ok']) && $httpCode < 400;
        if ($ok) {
            return $decoded;
        }

        return [
            'ok' => false,
            'error' => trim((string) ($decoded['error'] ?? 'fhse_request_failed')) ?: 'fhse_request_failed',
            'details' => trim((string) ($decoded['details'] ?? '')),
            'capabilities' => is_array($decoded['capabilities'] ?? null) ? $decoded['capabilities'] : null,
        ];
    }
}
