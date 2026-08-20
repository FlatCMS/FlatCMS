<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateDownloadService
{
    private string $stagingRoot;
    /** @var array<string,string> */
    private array $repositories;

    public function __construct(
        private ?UpdateSignatureService $signatures = null,
        ?string $stagingRoot = null,
        ?array $repositories = null
    ) {
        $this->signatures ??= new UpdateSignatureService();
        $this->stagingRoot = $stagingRoot ?: BASE_PATH . '/storage/tmp/update-manager';
        $config = $repositories ?? require BASE_PATH . '/app/Modules/UpdateManager/Config/repositories.php';
        $this->repositories = is_array($config) ? $config : [];
    }

    /** @param array<string,mixed> $package @return array<string,mixed> */
    public function download(array $package): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('update_download_curl_required');
        }

        $url = trim((string) ($package['download_url'] ?? ''));
        $sha256 = strtolower(trim((string) ($package['sha256'] ?? '')));
        if ($url === '' || preg_match('/^[a-f0-9]{64}$/', $sha256) !== 1) {
            throw new \RuntimeException('update_download_metadata_invalid');
        }

        $this->assertTrustedUrl($url, (string) ($package['catalog'] ?? ''));
        $this->ensureStagingRoot();

        $token = bin2hex(random_bytes(12));
        $partPath = $this->stagingRoot . '/download-' . $token . '.zip.part';
        $finalPath = $this->stagingRoot . '/download-' . $token . '.zip';
        $handle = @fopen($partPath, 'wb');
        if (!is_resource($handle)) {
            throw new \RuntimeException('update_download_staging_failed');
        }

        try {
            $this->streamCurl($url, $handle, $package);
        } finally {
            fclose($handle);
        }

        $size = (int) @filesize($partPath);
        $expectedSize = max(0, (int) ($package['size_bytes'] ?? 0));
        if ($size < 1 || ($expectedSize > 0 && $size !== $expectedSize)) {
            @unlink($partPath);
            throw new \RuntimeException('update_download_size_mismatch');
        }

        $actualHash = strtolower((string) hash_file('sha256', $partPath));
        if ($actualHash === '' || !hash_equals($sha256, $actualHash)) {
            @unlink($partPath);
            throw new \RuntimeException('update_download_checksum_mismatch');
        }

        if (!empty($package['official']) && !$this->signatures->verify($package)) {
            @unlink($partPath);
            throw new \RuntimeException('update_download_signature_invalid');
        }

        if (!@rename($partPath, $finalPath)) {
            @unlink($partPath);
            throw new \RuntimeException('update_download_staging_failed');
        }

        return [
            'path' => $finalPath,
            'size_bytes' => $size,
            'sha256' => $actualHash,
        ];
    }

    /** @param resource $handle @param array<string,mixed> $package */
    private function streamCurl(string $url, $handle, array $package): void
    {
        $maxBytes = $this->maxBytes((string) ($package['catalog'] ?? ''));
        $written = 0;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('update_download_unavailable');
        }

        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'FlatCMS-UpdateManager/' . flatcms_version(),
            CURLOPT_HTTPHEADER => ['Accept: application/zip, application/octet-stream'],
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use ($handle, &$written, $maxBytes): int {
                $length = strlen($chunk);
                $written += $length;
                if ($written > $maxBytes) {
                    return 0;
                }
                $result = fwrite($handle, $chunk);
                return $result === false ? 0 : $result;
            },
        ]);

        $ok = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($ok === false || $status < 200 || $status >= 300 || $written > $maxBytes) {
            throw new \RuntimeException(
                $written > $maxBytes ? 'update_download_too_large' : ($error !== '' ? 'update_download_network_error' : 'update_download_http_error')
            );
        }
    }

    private function assertTrustedUrl(string $url, string $catalog): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || !in_array($scheme, ['https', 'http'], true)) {
            throw new \RuntimeException('update_download_url_invalid');
        }
        if ($scheme !== 'https' && !is_local_host($host)) {
            throw new \RuntimeException('update_download_https_required');
        }

        $catalogUrl = trim((string) ($this->repositories[strtolower($catalog)] ?? ''));
        $catalogHost = strtolower((string) (parse_url($catalogUrl, PHP_URL_HOST) ?: ''));
        if ($catalogHost !== '' && $host !== $catalogHost) {
            throw new \RuntimeException('update_download_host_untrusted');
        }
    }

    private function maxBytes(string $catalog): int
    {
        $configured = (int) env('FLATCMS_UPDATE_MAX_DOWNLOAD_BYTES', 536870912);
        if ($catalog === 'appliances') {
            $configured = (int) env('FLATCMS_UPDATE_MAX_APPLIANCE_BYTES', max($configured, 17179869184));
        }
        return max(1048576, $configured);
    }

    private function ensureStagingRoot(): void
    {
        if (!is_dir($this->stagingRoot) && !@mkdir($this->stagingRoot, 0750, true) && !is_dir($this->stagingRoot)) {
            throw new \RuntimeException('update_download_staging_failed');
        }
    }
}
