<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\JsonLineStore;

final class UpdateHistoryService
{
    private string $path;
    private string $recordPath;
    private string $logRoot;
    private string $lockRoot;
    private ?JsonLineStore $store;
    private bool $storeUnavailable = false;

    public function __construct(?string $path = null, ?JsonLineStore $store = null, ?string $lockRoot = null)
    {
        if ($path === null) {
            $basePath = defined('BASE_PATH') ? (string) BASE_PATH : dirname(__DIR__, 4);
            $path = $basePath . '/storage/logs/update-manager/history.jsonl';
        }

        $this->path = rtrim(str_replace('\\', '/', $path), '/');
        $this->recordPath = basename($this->path);
        $this->logRoot = dirname($this->path);
        $this->lockRoot = $lockRoot ?? dirname(dirname($this->logRoot)) . '/cache/locks/update-manager';
        $this->store = $store;
    }

    /** @param array<string,mixed> $entry */
    public function append(array $entry): void
    {
        $store = $this->store();
        if ($store === null) {
            return;
        }

        $entry['recorded_at'] = $entry['recorded_at'] ?? gmdate('c');
        try {
            $store->append($this->recordPath, $entry);
        } catch (\Throwable) {
            // Update history is operational; it must never block the update transaction.
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function recent(int $limit = 50): array
    {
        $store = $this->store();
        if ($store === null) {
            return [];
        }

        try {
            $items = $store->read($this->recordPath);
        } catch (\Throwable) {
            return [];
        }

        return array_reverse(array_slice($items, -max(1, min(200, $limit))));
    }

    private function store(): ?JsonLineStore
    {
        if ($this->store !== null) {
            return $this->store;
        }
        if ($this->storeUnavailable) {
            return null;
        }

        try {
            $this->store = new JsonLineStore(
                $this->logRoot,
                new AtomicFileWriter(
                    $this->logRoot,
                    new FileLockManager($this->lockRoot),
                    0750,
                    0640
                )
            );
            return $this->store;
        } catch (\Throwable) {
            $this->storeUnavailable = true;
            return null;
        }
    }
}
