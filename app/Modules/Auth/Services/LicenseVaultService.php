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

final class LicenseVaultService
{
    private const CIPHER = 'aes-256-gcm';

    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? (BASE_PATH . '/resources/licenses/licenses.json');
    }

    /**
     * @return array{
     *     license_id: string,
     *     module: string,
     *     key: string,
     *     masked_key: string,
     *     domain: string,
     *     status: string,
     *     updated_at: string,
     *     owner_user_id: string,
     *     created_at: string,
     *     last_reveal_at: string,
     *     last_reveal_by: string,
     *     reveal_attempts: int
     * }
     */
    public function getModuleLicense(string $module, ?string $host = null, ?array $legacyLicense = null): array
    {
        $host = normalize_host($host ?? ($_SERVER['HTTP_HOST'] ?? ''));
        $records = $this->loadRecords();

        foreach ($records as $record) {
            if (($record['module'] ?? '') !== $module) {
                continue;
            }

            return $this->toPublicSummary($record);
        }

        if (is_array($legacyLicense) && trim((string) ($legacyLicense['key'] ?? '')) !== '') {
            return $this->storeModuleLicense(
                $module,
                trim((string) ($legacyLicense['key'] ?? '')),
                normalize_host((string) ($legacyLicense['domain'] ?? $host)),
                (string) ($legacyLicense['status'] ?? 'active'),
                (string) ($legacyLicense['updated_at'] ?? ''),
                ''
            );
        }

        return $this->emptySummary($module, $host);
    }

    /**
     * @return array{
     *     license_id: string,
     *     module: string,
     *     key: string,
     *     masked_key: string,
     *     domain: string,
     *     status: string,
     *     updated_at: string,
     *     owner_user_id: string,
     *     created_at: string,
     *     last_reveal_at: string,
     *     last_reveal_by: string,
     *     reveal_attempts: int
     * }
     */
    public function storeModuleLicense(
        string $module,
        string $plainKey,
        string $domain,
        string $status = 'active',
        string $updatedAt = '',
        string $ownerUserId = ''
    ): array {
        $plainKey = trim($plainKey);
        $domain = normalize_host($domain);
        $status = trim($status) !== '' ? trim($status) : 'active';
        $updatedAt = trim($updatedAt) !== '' ? trim($updatedAt) : date('Y-m-d H:i:s');

        $records = $this->loadRecords();
        $recordId = '';

        foreach ($records as $index => $record) {
            if (($record['module'] ?? '') !== $module) {
                continue;
            }

            $recordId = (string) ($record['id'] ?? '');
            $records[$index] = $this->buildRecord(
                $recordId !== '' ? $recordId : $this->generateId(),
                $module,
                $plainKey,
                $domain,
                $status,
                $updatedAt,
                $ownerUserId !== '' ? $ownerUserId : (string) ($record['owner_user_id'] ?? ''),
                $record
            );
            $this->saveRecords($records);
            return $this->toPublicSummary($records[$index]);
        }

        $record = $this->buildRecord(
            $this->generateId(),
            $module,
            $plainKey,
            $domain,
            $status,
            $updatedAt,
            $ownerUserId,
            []
        );
        $records[] = $record;
        $this->saveRecords($records);

        return $this->toPublicSummary($record);
    }

    /**
     * @return array{
     *     license_id: string,
     *     module: string,
     *     key: string,
     *     masked_key: string,
     *     domain: string,
     *     status: string,
     *     updated_at: string,
     *     owner_user_id: string,
     *     created_at: string,
     *     last_reveal_at: string,
     *     last_reveal_by: string,
     *     reveal_attempts: int
     * }
     */
    public function clearModuleLicense(string $module): array
    {
        $records = $this->loadRecords();
        $records = array_values(array_filter($records, static function (array $record) use ($module): bool {
            return ($record['module'] ?? '') !== $module;
        }));
        $this->saveRecords($records);

        return $this->emptySummary($module, normalize_host((string) ($_SERVER['HTTP_HOST'] ?? '')));
    }

    public function isModuleLicenseValid(
        string $module,
        ?string $host = null,
        ?array $legacyLicense = null,
        bool $allowLocalBypass = false
    ): bool
    {
        if ($allowLocalBypass && is_local_host($host)) {
            return true;
        }

        $summary = $this->getModuleLicense($module, $host, $legacyLicense);
        if (($summary['license_id'] ?? '') === '' && trim((string) ($summary['key'] ?? '')) === '') {
            return false;
        }

        $domain = normalize_host((string) ($summary['domain'] ?? ''));
        $targetHost = normalize_host($host ?? ($_SERVER['HTTP_HOST'] ?? ''));
        return $domain !== '' && $targetHost !== '' && $domain === $targetHost && (($summary['status'] ?? '') === 'active');
    }

    public function decryptModuleLicenseKey(string $module): string
    {
        $records = $this->loadRecords();
        foreach ($records as $record) {
            if (($record['module'] ?? '') !== $module) {
                continue;
            }

            $encrypted = (string) ($record['encrypted_key'] ?? '');
            if ($encrypted === '') {
                return '';
            }

            return $this->decrypt($encrypted);
        }

        return '';
    }

    /**
     * @return array<int, array{
     *     license_id: string,
     *     module: string,
     *     key: string,
     *     masked_key: string,
     *     domain: string,
     *     status: string,
     *     updated_at: string,
     *     owner_user_id: string,
     *     created_at: string,
     *     last_reveal_at: string,
     *     last_reveal_by: string,
     *     reveal_attempts: int
     * }>
     */
    public function listModuleLicenses(array $modules = []): array
    {
        $moduleFilter = array_values(array_filter(array_map('strval', $modules), static fn (string $value): bool => trim($value) !== ''));
        $moduleFilter = array_values(array_unique($moduleFilter));

        $records = $this->loadRecords();
        $summaries = [];
        foreach ($records as $record) {
            $module = (string) ($record['module'] ?? '');
            if ($module === '') {
                continue;
            }
            if ($moduleFilter !== [] && !in_array($module, $moduleFilter, true)) {
                continue;
            }

            $summaries[] = $this->toPublicSummary($record);
        }

        return $summaries;
    }

    public function incrementRevealAttempts(string $module): void
    {
        $records = $this->loadRecords();
        foreach ($records as $index => $record) {
            if (($record['module'] ?? '') !== $module) {
                continue;
            }

            $records[$index]['reveal_attempts'] = max(0, (int) ($record['reveal_attempts'] ?? 0)) + 1;
            $this->saveRecords($records);
            return;
        }
    }

    public function markModuleLicenseRevealed(string $module, string $userId): void
    {
        $records = $this->loadRecords();
        foreach ($records as $index => $record) {
            if (($record['module'] ?? '') !== $module) {
                continue;
            }

            $records[$index]['last_reveal_at'] = date('Y-m-d H:i:s');
            $records[$index]['last_reveal_by'] = $userId;
            $this->saveRecords($records);
            return;
        }
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create license vault directory.');
        }

        if (!is_file($this->path)) {
            $payload = ['licenses' => []];
            $written = @file_put_contents($this->path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
            if ($written === false) {
                throw new \RuntimeException('Unable to initialize license vault.');
            }
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function loadRecords(): array
    {
        $this->ensureStorage();
        $raw = @file_get_contents($this->path);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $licenses = $decoded['licenses'] ?? [];
        if (!is_array($licenses)) {
            return [];
        }

        return array_values(array_filter($licenses, static fn ($record): bool => is_array($record)));
    }

    /**
     * @param array<int,array<string,mixed>> $records
     */
    private function saveRecords(array $records): void
    {
        $this->ensureStorage();
        $payload = ['licenses' => array_values($records)];
        $written = @file_put_contents(
            $this->path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );

        if ($written === false) {
            throw new \RuntimeException('Unable to save license vault.');
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function buildRecord(
        string $id,
        string $module,
        string $plainKey,
        string $domain,
        string $status,
        string $updatedAt,
        string $ownerUserId,
        array $existing = []
    ): array {
        $createdAt = trim((string) ($existing['created_at'] ?? ''));
        if ($createdAt === '') {
            $createdAt = $updatedAt;
        }

        return [
            'id' => $id,
            'module' => $module,
            'encrypted_key' => $this->encrypt($plainKey),
            'masked_key' => $this->maskKey($plainKey),
            'domain' => $domain,
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'owner_user_id' => $ownerUserId,
            'last_reveal_at' => (string) ($existing['last_reveal_at'] ?? ''),
            'last_reveal_by' => (string) ($existing['last_reveal_by'] ?? ''),
            'reveal_attempts' => max(0, (int) ($existing['reveal_attempts'] ?? 0)),
        ];
    }

    /**
     * @param array<string,mixed> $record
     * @return array{
     *     license_id: string,
     *     module: string,
     *     key: string,
     *     masked_key: string,
     *     domain: string,
     *     status: string,
     *     updated_at: string,
     *     owner_user_id: string,
     *     created_at: string,
     *     last_reveal_at: string,
     *     last_reveal_by: string,
     *     reveal_attempts: int
     * }
     */
    private function toPublicSummary(array $record): array
    {
        return [
            'license_id' => (string) ($record['id'] ?? ''),
            'module' => (string) ($record['module'] ?? ''),
            'key' => '',
            'masked_key' => (string) ($record['masked_key'] ?? ''),
            'domain' => normalize_host((string) ($record['domain'] ?? '')),
            'status' => trim((string) ($record['status'] ?? 'inactive')),
            'updated_at' => (string) ($record['updated_at'] ?? ''),
            'owner_user_id' => (string) ($record['owner_user_id'] ?? ''),
            'created_at' => (string) ($record['created_at'] ?? ''),
            'last_reveal_at' => (string) ($record['last_reveal_at'] ?? ''),
            'last_reveal_by' => (string) ($record['last_reveal_by'] ?? ''),
            'reveal_attempts' => max(0, (int) ($record['reveal_attempts'] ?? 0)),
        ];
    }

    /**
     * @return array{
     *     license_id: string,
     *     module: string,
     *     key: string,
     *     masked_key: string,
     *     domain: string,
     *     status: string,
     *     updated_at: string,
     *     owner_user_id: string,
     *     created_at: string,
     *     last_reveal_at: string,
     *     last_reveal_by: string,
     *     reveal_attempts: int
     * }
     */
    private function emptySummary(string $module, string $domain = ''): array
    {
        return [
            'license_id' => '',
            'module' => $module,
            'key' => '',
            'masked_key' => '',
            'domain' => $domain,
            'status' => 'inactive',
            'updated_at' => '',
            'owner_user_id' => '',
            'created_at' => '',
            'last_reveal_at' => '',
            'last_reveal_by' => '',
            'reveal_attempts' => 0,
        ];
    }

    private function generateId(): string
    {
        return 'lic_' . date('YmdHis') . '_' . substr(bin2hex(random_bytes(6)), 0, 12);
    }

    private function maskKey(string $plainKey): string
    {
        $plainKey = trim($plainKey);
        if ($plainKey === '') {
            return '';
        }

        $length = strlen($plainKey);
        if ($length <= 8) {
            return str_repeat('*', max(0, $length - 2)) . substr($plainKey, -2);
        }

        return substr($plainKey, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($plainKey, -4);
    }

    private function encrypt(string $plainText): string
    {
        if (!function_exists('openssl_encrypt')) {
            throw new \RuntimeException('OpenSSL extension is required for license vault encryption.');
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            $this->secretKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if (!is_string($cipherText) || $cipherText === '') {
            throw new \RuntimeException('Unable to encrypt license key.');
        }

        return base64_encode(json_encode([
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'cipher' => base64_encode($cipherText),
        ], JSON_UNESCAPED_SLASHES));
    }

    private function decrypt(string $payload): string
    {
        if (!function_exists('openssl_decrypt')) {
            throw new \RuntimeException('OpenSSL extension is required for license vault decryption.');
        }

        $decoded = json_decode((string) base64_decode($payload, true), true);
        if (!is_array($decoded)) {
            return '';
        }

        $iv = base64_decode((string) ($decoded['iv'] ?? ''), true);
        $tag = base64_decode((string) ($decoded['tag'] ?? ''), true);
        $cipher = base64_decode((string) ($decoded['cipher'] ?? ''), true);
        if (!is_string($iv) || !is_string($tag) || !is_string($cipher)) {
            return '';
        }

        $plainText = openssl_decrypt(
            $cipher,
            self::CIPHER,
            $this->secretKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return is_string($plainText) ? $plainText : '';
    }

    private function secretKey(): string
    {
        $secret = trim((string) env('FLATCMS_LICENSE_VAULT_KEY', ''));
        if ($secret === '') {
            $secret = flatcms_product_name() . '|' . trim((string) env('APP_URL', ''));
        }

        return hash('sha256', $secret, true);
    }
}
