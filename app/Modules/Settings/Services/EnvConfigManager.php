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

use App\Core\Security\SecretBox;

final class EnvConfigManager
{
    private const ENV_LOCAL_PATH = BASE_PATH . '/.env.local';
    private const MANAGED_BLOCK_START = '# >>> FlatCMS managed integrations >>>';
    private const MANAGED_BLOCK_END = '# <<< FlatCMS managed integrations <<<';
    private const SECRET_MASK = '********';
    public const ERROR_ENV_LOCAL_DIR_MISSING = 'env_local_dir_missing';
    public const ERROR_ENV_LOCAL_DIR_NOT_WRITABLE = 'env_local_dir_not_writable';
    public const ERROR_ENV_LOCAL_NOT_WRITABLE = 'env_local_not_writable';
    public const ERROR_ENV_LOCAL_READ_FAILED = 'env_local_read_failed';
    public const ERROR_ENV_LOCAL_WRITE_FAILED = 'env_local_write_failed';

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
        'DEMO_FORCE_LICENSE_WARNING',
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
        'DEMO_FORCE_LICENSE_WARNING',
    ];

    /**
     * @var array<int,string>
     */
    private const SECRET_KEYS = [
        'OPENAI_API_KEY',
        'TURNSTILE_SECRET_KEY',
    ];

    /**
     * @return array<string,string|int>
     */
    public function readCurrentValues(): array
    {
        $values = [];
        $stored = $this->readStoredValues();

        foreach (self::ALLOWED_KEYS as $key) {
            $raw = (string) ($stored[$key] ?? '');

            if (in_array($key, self::BOOLEAN_KEYS, true)) {
                $values[$key] = $this->normalizeBoolean($raw);
                continue;
            }

            if (in_array($key, self::SECRET_KEYS, true)) {
                $values[$key] = $raw === '' ? '' : self::SECRET_MASK;
                continue;
            }

            $values[$key] = $raw;
        }

        return $values;
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
        $this->backupIfNeeded($existing);

        $withoutManagedBlock = $this->stripManagedBlock($existing);
        $withoutManagedBlock = trim($withoutManagedBlock);

        $content = '';
        if ($withoutManagedBlock !== '') {
            $content .= $withoutManagedBlock . PHP_EOL . PHP_EOL;
        }
        $content .= $block . PHP_EOL;

        $written = @file_put_contents($path, $content, LOCK_EX);
        if ($written === false) {
            throw new \RuntimeException(self::ERROR_ENV_LOCAL_WRITE_FAILED);
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

    public function ensureDefaults(): void
    {
        $status = $this->status();
        if (!empty($status['exists'])) {
            return;
        }

        $defaults = [];
        foreach (self::ALLOWED_KEYS as $key) {
            $defaults[$key] = '';
        }

        $this->writeValues($defaults, true);
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

        foreach (self::ALLOWED_KEYS as $key) {
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
                if ($resolvedValue === '' || $resolvedValue === self::SECRET_MASK) {
                    $resolvedValue = trim((string) ($existingValues[$key] ?? ''));
                }

                $sanitized[$key] = $resolvedValue === '' ? '' : $secretBox->normalizeStoredValue($resolvedValue);
                continue;
            }

            // Remove null bytes and normalize line breaks.
            $value = str_replace("\0", '', $value);
            $value = str_replace(["\r\n", "\r"], "\n", $value);

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * @return array<string,string>
     */
    private function readStoredValues(): array
    {
        $values = [];

        foreach (self::ALLOWED_KEYS as $key) {
            $values[$key] = trim((string) env($key, ''));
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

        foreach (self::ALLOWED_KEYS as $key) {
            $value = $values[$key] ?? '';
            $shouldKeep = $includeEmpty || in_array($key, self::BOOLEAN_KEYS, true) || $value !== '';

            if (!$shouldKeep) {
                continue;
            }

            $lines[] = $key . '=' . $this->formatEnvValue($value);
        }

        $lines[] = self::MANAGED_BLOCK_END;

        return implode(PHP_EOL, $lines);
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

    private function backupIfNeeded(string $existing): void
    {
        if ($existing === '') {
            return;
        }

        $backupDir = STORAGE_PATH . '/backups/env';
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
            return;
        }

        $backupPath = $backupDir . '/.env.local.backup.' . date('Ymd_His');
        @file_put_contents($backupPath, $existing, LOCK_EX);
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
