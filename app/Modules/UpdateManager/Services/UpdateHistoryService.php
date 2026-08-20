<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateHistoryService
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?: BASE_PATH . '/storage/logs/update-manager/history.jsonl';
    }

    /** @param array<string,mixed> $entry */
    public function append(array $entry): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            return;
        }

        $entry['recorded_at'] = $entry['recorded_at'] ?? gmdate('c');
        $json = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return;
        }
        @file_put_contents($this->path, $json . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /** @return array<int,array<string,mixed>> */
    public function recent(int $limit = 50): array
    {
        if (!is_file($this->path)) {
            return [];
        }
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $items = [];
        foreach (array_slice($lines, -max(1, min(200, $limit))) as $line) {
            $decoded = json_decode((string) $line, true);
            if (is_array($decoded)) {
                $items[] = $decoded;
            }
        }
        return array_reverse($items);
    }
}
