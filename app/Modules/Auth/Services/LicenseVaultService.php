<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Auth/Services/LicenseVaultService.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\JsonStore;
use App\Core\Storage\StorageException;

final class LicenseVaultService
{
    private const CIPHER = 'aes-256-gcm';

    private JsonStore $store;
    private string $recordPath;

    public function __construct(?string $path = null, ?JsonStore $store = null, ?string $lockRoot = null)
    {
        $path ??= BASE_PATH . '/resources/licenses/licenses.json';

        if ($store === null) {
            $vaultRoot = dirname($path);
            $lockRoot ??= BASE_PATH . '/storage/cache/locks/licenses';
            $store = new JsonStore($vaultRoot, new AtomicFileWriter(
                $vaultRoot,
                new FileLockManager($lockRoot)
            ));
        }

        $this->store = $store;
        $this->recordPath = basename($path);
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

        $storedRecord = [];
        $this->mutateRecords(function (array $records) use (
            $module,
            $plainKey,
            $domain,
            $status,
            $updatedAt,
            $ownerUserId,
            &$storedRecord
        ): array {
            foreach ($records as $index => $record) {
                if (($record['module'] ?? '') !== $module) {
                    continue;
                }

                $recordId = (string) ($record['id'] ?? '');
                $storedRecord = $this->buildRecord(
                    $recordId !== '' ? $recordId : $this->generateId(),
                    $module,
                    $plainKey,
                    $domain,
                    $status,
                    $updatedAt,
                    $ownerUserId !== '' ? $ownerUserId : (string) ($record['owner_user_id'] ?? ''),
                    $record
                );
                $records[$index] = $storedRecord;

                return $records;
            }

            $storedRecord = $this->buildRecord(
                $this->generateId(),
                $module,
                $plainKey,
                $domain,
                $status,
                $updatedAt,
                $ownerUserId,
                []
            );
            $records[] = $storedRecord;

            return $records;
        });

        return $this->toPublicSummary($storedRecord);
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
        $this->mutateRecords(static function (array $records) use ($module): array {
            return array_values(array_filter($records, static function (array $record) use ($module): bool {
                return ($record['module'] ?? '') !== $module;
            }));
        });

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
        return $this->licenseHostsMatch($domain, $targetHost) && (($summary['status'] ?? '') === 'active');
    }

    private function licenseHostsMatch(string $licensedHost, string $targetHost): bool
    {
        if ($licensedHost === '' || $targetHost === '') {
            return false;
        }

        if ($licensedHost === $targetHost) {
            return true;
        }

        $canonicalize = static fn (string $value): string => str_starts_with($value, 'www.')
            ? substr($value, 4)
            : $value;

        return $canonicalize($licensedHost) === $canonicalize($targetHost);
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
        $this->mutateRecords(static function (array $records) use ($module): array {
            foreach ($records as $index => $record) {
                if (($record['module'] ?? '') !== $module) {
                    continue;
                }

                $records[$index]['reveal_attempts'] = max(0, (int) ($record['reveal_attempts'] ?? 0)) + 1;
                break;
            }

            return $records;
        });
    }

    public function markModuleLicenseRevealed(string $module, string $userId): void
    {
        $revealedAt = date('Y-m-d H:i:s');
        $this->mutateRecords(static function (array $records) use ($module, $userId, $revealedAt): array {
            foreach ($records as $index => $record) {
                if (($record['module'] ?? '') !== $module) {
                    continue;
                }

                $records[$index]['last_reveal_at'] = $revealedAt;
                $records[$index]['last_reveal_by'] = $userId;
                break;
            }

            return $records;
        });
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function loadRecords(): array
    {
        $payload = $this->store->read($this->recordPath, ['licenses' => []]);
        return $this->normalizeRecords($payload['licenses'] ?? []);
    }

    /**
     * @param array<int,array<string,mixed>> $records
     */
    private function mutateRecords(callable $mutation): void
    {
        $this->store->mutate($this->recordPath, function (array $payload) use ($mutation): array {
            $records = $this->normalizeRecords($payload['licenses'] ?? []);
            $updated = $mutation($records);
            if (!is_array($updated)) {
                throw new StorageException('License vault mutation must return an array.');
            }

            return ['licenses' => $this->normalizeRecords($updated)];
        }, ['licenses' => []]);
    }

    /**
     * @param mixed $records
     * @return array<int,array<string,mixed>>
     */
    private function normalizeRecords(mixed $records): array
    {
        if (!is_array($records)) {
            return [];
        }

        return array_values(array_filter($records, static fn ($record): bool => is_array($record)));
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
