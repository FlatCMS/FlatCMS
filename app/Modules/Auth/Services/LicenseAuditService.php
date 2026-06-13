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

final class LicenseAuditService
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? (BASE_PATH . '/resources/licenses/audit.jsonl');
    }

    public function record(string $action, array $context = []): void
    {
        $this->ensureDirectory();

        $payload = [
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => $context,
        ];

        @file_put_contents(
            $this->path,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    private function ensureDirectory(): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}
