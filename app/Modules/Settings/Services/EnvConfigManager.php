<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Settings/Services/EnvConfigManager.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Settings\Services;

use App\Core\Security\SecretBox;
use App\Core\Security\TrustedProxyPolicy;
use App\Core\EnvironmentFile;
use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\StorageException;

final class EnvConfigManager
{
    private const ENV_LOCAL_PATH = BASE_PATH . '/.env.local';
    private const MANAGED_BLOCK_START = '# >>> FlatCMS managed integrations >>>';
    private const MANAGED_BLOCK_END = '# <<< FlatCMS managed integrations <<<';
    public const ERROR_ENV_LOCAL_DIR_MISSING = 'env_local_dir_missing';
    public const ERROR_ENV_LOCAL_DIR_NOT_WRITABLE = 'env_local_dir_not_writable';
    public const ERROR_ENV_LOCAL_NOT_WRITABLE = 'env_local_not_writable';
    public const ERROR_ENV_LOCAL_READ_FAILED = 'env_local_read_failed';
    public const ERROR_ENV_LOCAL_WRITE_FAILED = 'env_local_write_failed';
    public const ERROR_TRUSTED_PROXY_CIDRS_REQUIRED = 'trusted_proxy_cidrs_required';
    public const ERROR_TRUSTED_PROXY_CIDRS_INVALID = 'trusted_proxy_cidrs_invalid';
    public const ERROR_TRUSTED_PROXY_CIDRS_TOO_BROAD = 'trusted_proxy_cidrs_too_broad';

    /**
     * @var array<int,string>
     */
    private const ALLOWED_KEYS = [
        'OPENAI_API_KEY',
        'OPENAI_API_BASE_URL',
        'OPENAI_RESPONSES_MODEL',
        'OPENAI_TIMEOUT',
        'OPENAI_MAX_OUTPUT_TOKENS',
        'TURNSTILE_ENABLED',
        'TURNSTILE_SITE_KEY',
        'TURNSTILE_SECRET_KEY',
        'TINYMCE_ENABLED',
        'TINYMCE_API_KEY',
        'FONTAWESOME_KIT',
        'COOKIE_BANNER_ENABLED',
        'COOKIE_REQUIRE_CONSENT',
        'AXEPTIO_CLIENT_ID',
        'AXEPTIO_COOKIES_VERSION',
        'MATOMO_ENABLED',
        'MATOMO_BASE_URL',
        'MATOMO_SITE_ID',
        'GOOGLE_ANALYTICS_ENABLED',
        'GOOGLE_ANALYTICS_MEASUREMENT_ID',
        'GOOGLE_OAUTH_CLIENT_ID',
        'GOOGLE_OAUTH_CLIENT_SECRET',
        'GOOGLE_OAUTH_ENCRYPTION_KEY',
        'AUTH_2FA_EMAIL_ENABLED',
        'AUTH_2FA_EMAIL_ROLES',
        'AUTH_2FA_EMAIL_TTL',
        'AUTH_2FA_EMAIL_RESEND_COOLDOWN',
        'AUTH_2FA_EMAIL_MAX_ATTEMPTS',
        'AUTH_2FA_EMAIL_DISABLE_REMEMBER',
        'AUTH_2FA_SLOW_LOG_THRESHOLD_MS',
        'DEMO_FORCE_LICENSE_WARNING',
        'TRUST_PROXY_HEADERS',
        'TRUSTED_PROXY_CIDRS',
    ];

    /**
     * @var array<int,string>
     */
    private const BOOLEAN_KEYS = [
        'TURNSTILE_ENABLED',
        'TINYMCE_ENABLED',
        'COOKIE_BANNER_ENABLED',
        'COOKIE_REQUIRE_CONSENT',
        'MATOMO_ENABLED',
        'GOOGLE_ANALYTICS_ENABLED',
        'AUTH_2FA_EMAIL_ENABLED',
        'AUTH_2FA_EMAIL_DISABLE_REMEMBER',
        'DEMO_FORCE_LICENSE_WARNING',
        'TRUST_PROXY_HEADERS',
    ];

    /** @var array<int,string> */
    private const NON_PORTABLE_KEYS = [
        'TRUST_PROXY_HEADERS',
        'TRUSTED_PROXY_CIDRS',
    ];

    /**
     * @var array<int,string>
     */
    private const SECRET_MASK = '********';

    /**
     * @var array<int,string>
     */
    private const SECRET_KEYS = [
        'OPENAI_API_KEY',
        'TURNSTILE_SECRET_KEY',
        'GOOGLE_OAUTH_CLIENT_SECRET',
        'GOOGLE_OAUTH_ENCRYPTION_KEY',
    ];

    /**
     * @var array<int,string>
     */
    private const SECRET_MASK_VALUES = [
        '********',
        '••••••••',
        '•••••••• — déjà configuré, laissez vide pour conserver la valeur enregistrée',
    ];

    /**
     * @return array<string,string|int>
     */
    public function readCurrentValues(): array
    {
        $values = [];
        $stored = $this->readStoredValues();

        foreach ($this->allowedKeys() as $key) {
            $raw = (string) ($stored[$key] ?? '');

            if (in_array($key, self::BOOLEAN_KEYS, true)) {
                $values[$key] = $this->normalizeBoolean($raw);
                continue;
            }

            if (in_array($key, self::SECRET_KEYS, true)) {
                $values[$key] = '';
                continue;
            }

            $values[$key] = $raw;
        }

        return $values;
    }

    /**
     * Export only values explicitly stored in .env.local and managed by Settings.
     * Secret values are normalized to SecretBox ciphertext before leaving the source.
     *
     * @return array<string,string>
     */
    public function exportPortableValues(): array
    {
        if (!is_file(self::ENV_LOCAL_PATH)) {
            return [];
        }

        $stored = EnvironmentFile::read(self::ENV_LOCAL_PATH);
        $values = [];
        $secretBox = new SecretBox();
        foreach ($this->allowedKeys() as $key) {
            if (in_array($key, self::NON_PORTABLE_KEYS, true)) {
                continue;
            }
            if (!array_key_exists($key, $stored)) {
                continue;
            }

            $value = trim((string) $stored[$key]);
            if (in_array($key, self::BOOLEAN_KEYS, true)) {
                $values[$key] = (string) $this->normalizeBoolean($value);
                continue;
            }
            if (in_array($key, self::SECRET_KEYS, true)) {
                if ($value === '') {
                    $values[$key] = '';
                    continue;
                }
                $plain = $secretBox->decrypt($value);
                if ($plain === '' && $value !== '') {
                    throw new \RuntimeException('backups_site_portable_settings_invalid');
                }
                $values[$key] = $plain;
                continue;
            }

            $value = str_replace("\0", '', $value);
            $values[$key] = str_replace(["\r\n", "\r"], "\n", $value);
        }

        return $values;
    }

    /**
     * Build the target .env.local while preserving all unmanaged target settings.
     *
     * @param array<string,mixed> $values
     */
    public function buildPortableEnvLocalContent(array $values, string $existingContent): string
    {
        $normalized = [];
        $secretBox = new SecretBox();

        $existingValues = EnvironmentFile::parse($existingContent);
        foreach (self::NON_PORTABLE_KEYS as $key) {
            if (!array_key_exists($key, $existingValues)) {
                continue;
            }
            $normalized[$key] = in_array($key, self::BOOLEAN_KEYS, true)
                ? (string) $this->normalizeBoolean((string) $existingValues[$key])
                : trim((string) $existingValues[$key]);
        }

        foreach ($this->allowedKeys() as $key) {
            if (in_array($key, self::NON_PORTABLE_KEYS, true)) {
                continue;
            }
            if (!array_key_exists($key, $values)) {
                continue;
            }
            if (is_array($values[$key]) || is_object($values[$key])) {
                throw new \RuntimeException('backups_site_portable_settings_invalid');
            }

            $value = trim((string) $values[$key]);
            if (in_array($key, self::BOOLEAN_KEYS, true)) {
                $normalized[$key] = (string) $this->normalizeBoolean($value);
                continue;
            }
            if (in_array($key, self::SECRET_KEYS, true)) {
                if ($value !== '' && $secretBox->isEncrypted($value)) {
                    throw new \RuntimeException('backups_site_portable_settings_invalid');
                }
                $stored = $value === '' ? '' : $secretBox->encrypt($value);
                if ($value !== '' && !$secretBox->isEncrypted($stored)) {
                    throw new \RuntimeException('backups_site_portable_settings_invalid');
                }
                $normalized[$key] = $stored;
                continue;
            }

            $value = str_replace("\0", '', $value);
            $normalized[$key] = str_replace(["\r\n", "\r"], "\n", $value);
        }

        $withoutManagedBlock = trim($this->stripManagedBlock($existingContent));
        $content = $withoutManagedBlock === ''
            ? ''
            : $withoutManagedBlock . PHP_EOL . PHP_EOL;
        $content .= $this->buildPortableManagedBlock($normalized) . PHP_EOL;

        return $content;
    }

    /** @param array<string,string> $values */
    private function buildPortableManagedBlock(array $values): string
    {
        $lines = [
            self::MANAGED_BLOCK_START,
            '# Restored from a portable FlatCMS SiteBackup',
        ];

        foreach ($this->allowedKeys() as $key) {
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $lines[] = $key . '=' . $this->formatEnvValue((string) $values[$key]);
        }

        $lines[] = self::MANAGED_BLOCK_END;
        return implode(PHP_EOL, $lines);
    }

    /**
     * @return array{path:string,exists:bool,writable:bool}
     */
    public function status(): array
    {
        $path = self::ENV_LOCAL_PATH;
        $exists = is_file($path);
        $writable = $exists ? is_writable($path) : is_writable(dirname($path));

        return [
            'path' => $path,
            'exists' => $exists,
            'writable' => $writable,
        ];
    }

    /**
     * @param array<string,mixed> $input
     */
    public function writeValues(array $input, bool $includeEmpty = false): void
    {
        $writer = $this->writer();
        $writer->synchronized(self::ENV_LOCAL_PATH, function () use ($input, $includeEmpty, $writer): void {
            $this->writeLockedValues($input, $includeEmpty, $writer);
        });
    }

    private function writer(): AtomicFileWriter
    {
        return new AtomicFileWriter(BASE_PATH, new FileLockManager(STORAGE_PATH . '/cache/locks/env'), 0755, 0600);
    }

    private function writeLockedValues(array $input, bool $includeEmpty, AtomicFileWriter $writer): void
    {
        $existingValues = $this->readStoredValues();
        $sanitized = $this->sanitizeInput($input, $existingValues);
        $block = $this->buildManagedBlock($sanitized, $includeEmpty);

        $path = self::ENV_LOCAL_PATH;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            throw new \RuntimeException(self::ERROR_ENV_LOCAL_DIR_MISSING);
        }

        $exists = is_file($path);
        if ($exists) {
            if (!is_writable($path)) {
                throw new \RuntimeException(self::ERROR_ENV_LOCAL_NOT_WRITABLE);
            }
        } elseif (!is_writable($dir)) {
            throw new \RuntimeException(self::ERROR_ENV_LOCAL_DIR_NOT_WRITABLE);
        }

        $existing = '';
        if ($exists) {
            $raw = @file_get_contents($path);
            if ($raw === false) {
                throw new \RuntimeException(self::ERROR_ENV_LOCAL_READ_FAILED);
            }
            $existing = (string) $raw;
        }
        $withoutManagedBlock = $this->stripManagedBlock($existing);
        $withoutManagedBlock = trim($withoutManagedBlock);

        $content = '';
        if ($withoutManagedBlock !== '') {
            $content .= $withoutManagedBlock . PHP_EOL . PHP_EOL;
        }
        $content .= $block . PHP_EOL;

        try {
            $writer->write($path, $content);
        } catch (StorageException $exception) {
            throw new \RuntimeException(self::ERROR_ENV_LOCAL_WRITE_FAILED, 0, $exception);
        }

        foreach ($sanitized as $key => $value) {
            $stringValue = (string) $value;
            $_ENV[$key] = $stringValue;
            if (function_exists('putenv')) {
                $ok = @putenv($key . '=' . $stringValue);
                if ($ok === false) {
                    error_log(sprintf('[FlatCMS][Settings] putenv failed for key %s', $key));
                }
            }
        }
    }

    /**
     * @param array<string,mixed> $input
     */
    public function writePartialValues(array $input): void
    {
        $writer = $this->writer();
        $writer->synchronized(self::ENV_LOCAL_PATH, function () use ($input, $writer): void {
            $payload = $this->readStoredValues();
            foreach ($this->allowedKeys() as $key) {
                if (array_key_exists($key, $input)) {
                    $payload[$key] = $input[$key];
                }
            }
            $this->writeLockedValues($payload, false, $writer);
        });
    }

    public function ensureDefaults(): void
    {
        return;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,string> $existingValues
     * @return array<string,string>
     */
    private function sanitizeInput(array $input, array $existingValues = []): array
    {
        $sanitized = [];
        $secretBox = new SecretBox();

        foreach ($this->allowedKeys() as $key) {
            $value = $input[$key] ?? '';

            if (is_array($value)) {
                $value = '';
            }

            $value = trim((string) $value);

            if (in_array($key, self::BOOLEAN_KEYS, true)) {
                $sanitized[$key] = (string) $this->normalizeBoolean($value);
                continue;
            }

            if (in_array($key, self::SECRET_KEYS, true)) {
                $resolvedValue = $value;
                if ($resolvedValue === '' || $this->isSecretMask($resolvedValue)) {
                    $resolvedValue = trim((string) ($existingValues[$key] ?? ''));
                }

                if (
                    $key === 'GOOGLE_OAUTH_ENCRYPTION_KEY'
                    && $resolvedValue === ''
                    && $this->shouldEnsureGoogleOAuthEncryptionKey($input, $existingValues, $sanitized)
                ) {
                    $resolvedValue = base64_encode(random_bytes(32));
                }

                $sanitized[$key] = $resolvedValue === '' ? '' : $secretBox->normalizeStoredValue($resolvedValue);
                continue;
            }

            // Remove null bytes and normalize line breaks.
            $value = str_replace("\0", '', $value);
            $value = str_replace(["\r\n", "\r"], "\n", $value);

            $sanitized[$key] = $value;
        }

        try {
            $sanitized['TRUSTED_PROXY_CIDRS'] = TrustedProxyPolicy::normalizeCidrs(
                (string) ($sanitized['TRUSTED_PROXY_CIDRS'] ?? '')
            );
        } catch (\InvalidArgumentException $exception) {
            $reason = $exception->getMessage() === TrustedProxyPolicy::ERROR_CIDR_TOO_BROAD
                ? self::ERROR_TRUSTED_PROXY_CIDRS_TOO_BROAD
                : self::ERROR_TRUSTED_PROXY_CIDRS_INVALID;
            throw new \RuntimeException($reason, 0, $exception);
        }

        if (($sanitized['TRUST_PROXY_HEADERS'] ?? '0') === '1' && $sanitized['TRUSTED_PROXY_CIDRS'] === '') {
            throw new \RuntimeException(self::ERROR_TRUSTED_PROXY_CIDRS_REQUIRED);
        }

        return $sanitized;
    }

    /**
     * @return array<int,string>
     */
    private function allowedKeys(): array
    {
        return array_values(array_unique(self::ALLOWED_KEYS));
    }

    private function isSecretMask(string $value): bool
    {
        $value = trim($value);
        if (in_array($value, self::SECRET_MASK_VALUES, true)) {
            return true;
        }

        return preg_match('/^\*{8,}$/', $value) === 1;
    }

    /**
     * @return array<string,string>
     */
    private function readStoredValues(): array
    {
        $values = [];

        foreach ($this->allowedKeys() as $key) {
            $values[$key] = trim((string) env($key, ''));
        }

        // Read disk after acquiring the write lock, not the request's stale env snapshot.
        foreach ([BASE_PATH . '/.env', self::ENV_LOCAL_PATH] as $path) {
            foreach (EnvironmentFile::read($path) as $key => $value) {
                if (array_key_exists($key, $values)) {
                    $values[$key] = $value;
                }
            }
        }

        return $values;
    }

    /**
     * @param array<string,string> $values
     */
    private function buildManagedBlock(array $values, bool $includeEmpty = false): string
    {
        $lines = [];
        $lines[] = self::MANAGED_BLOCK_START;
        $lines[] = '# Generated from /admin/settings (Integrations & API)';

        $writtenKeys = [];
        foreach ($this->allowedKeys() as $key) {
            if (isset($writtenKeys[$key])) {
                continue;
            }

            $value = $values[$key] ?? '';
            if (!$includeEmpty && !$this->shouldWriteManagedKey($key, (string) $value)) {
                continue;
            }

            $lines[] = $key . '=' . $this->formatEnvValue($value);
            $writtenKeys[$key] = true;
        }

        $lines[] = self::MANAGED_BLOCK_END;

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,string> $existingValues
     * @param array<string,string> $sanitized
     */
    private function shouldEnsureGoogleOAuthEncryptionKey(array $input, array $existingValues, array $sanitized): bool
    {
        $clientId = trim((string) ($sanitized['GOOGLE_OAUTH_CLIENT_ID'] ?? $input['GOOGLE_OAUTH_CLIENT_ID'] ?? $existingValues['GOOGLE_OAUTH_CLIENT_ID'] ?? ''));
        $clientSecret = trim((string) ($sanitized['GOOGLE_OAUTH_CLIENT_SECRET'] ?? $input['GOOGLE_OAUTH_CLIENT_SECRET'] ?? $existingValues['GOOGLE_OAUTH_CLIENT_SECRET'] ?? ''));

        return $clientId !== '' || $clientSecret !== '';
    }

    private function shouldWriteManagedKey(string $key, string $value): bool
    {
        if (in_array($key, ['AUTH_2FA_EMAIL_DISABLE_REMEMBER', 'DEMO_FORCE_LICENSE_WARNING'], true)) {
            return trim($value) !== '';
        }

        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            return true;
        }

        return trim($value) !== '';
    }

    private function stripManagedBlock(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $pattern = '/'
            . preg_quote(self::MANAGED_BLOCK_START, '/')
            . '.*?'
            . preg_quote(self::MANAGED_BLOCK_END, '/')
            . '\R*/s';

        return (string) preg_replace($pattern, '', $content);
    }

    private function formatEnvValue(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        if (preg_match('/^[A-Za-z0-9_\\.\\-:\\/]+$/', $value) === 1) {
            return $value;
        }

        $escaped = str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], $value);
        return '"' . $escaped . '"';
    }

    private function normalizeBoolean(string $value): int
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true) ? 1 : 0;
    }
}
