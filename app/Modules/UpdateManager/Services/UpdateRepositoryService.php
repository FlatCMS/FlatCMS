<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateRepositoryService
{
    private const CATALOGS = ['core', 'modules', 'extensions', 'plugins', 'themes', 'appliances'];
    private const MAX_RESPONSE_BYTES = 2097152;

    /** @var array<string, string> */
    private array $repositories;

    /** @param array<string, string>|null $repositories */
    public function __construct(?array $repositories = null)
    {
        $config = $repositories ?? require BASE_PATH . '/app/Modules/UpdateManager/Config/repositories.php';
        $this->repositories = is_array($config) ? $config : [];
    }

    /** @return array<string, string> */
    public function repositories(): array
    {
        return $this->repositories;
    }

    /** @return array<string, mixed> */
    public function fetch(string $catalog): array
    {
        $catalog = strtolower(trim($catalog));
        if (!in_array($catalog, self::CATALOGS, true)) {
            throw new \RuntimeException('update_catalog_invalid');
        }

        $url = trim((string) ($this->repositories[$catalog] ?? ''));
        if ($url === '') {
            throw new \RuntimeException('update_repository_missing');
        }

        $this->assertSafeUrl($url);
        $body = function_exists('curl_init')
            ? $this->fetchWithCurl($url)
            : $this->fetchWithStreams($url);

        if (strlen($body) > self::MAX_RESPONSE_BYTES) {
            throw new \RuntimeException('update_repository_response_too_large');
        }

        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            throw new \RuntimeException('update_repository_invalid_json');
        }

        $declaredCatalog = strtolower(trim((string) ($payload['catalog'] ?? '')));
        if ($declaredCatalog !== $catalog || !is_array($payload['packages'] ?? null)) {
            throw new \RuntimeException('update_repository_invalid_catalog');
        }

        return $payload;
    }

    private function fetchWithCurl(string $url): string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('update_repository_unavailable');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'FlatCMS-UpdateManager/' . flatcms_version(),
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($body) || $body === '' || $status < 200 || $status >= 300) {
            throw new \RuntimeException($error !== '' ? 'update_repository_network_error' : 'update_repository_http_error');
        }

        return $body;
    }

    private function fetchWithStreams(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
                'ignore_errors' => false,
                'follow_location' => 0,
                'header' => "Accept: application/json\r\nUser-Agent: FlatCMS-UpdateManager/" . flatcms_version() . "\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context, 0, self::MAX_RESPONSE_BYTES + 1);
        if (!is_string($body) || $body === '') {
            throw new \RuntimeException('update_repository_unavailable');
        }

        return $body;
    }

    private function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || !in_array($scheme, ['https', 'http'], true)) {
            throw new \RuntimeException('update_repository_invalid_url');
        }

        if ($scheme !== 'https' && !is_local_host($host)) {
            throw new \RuntimeException('update_repository_https_required');
        }
    }
}
