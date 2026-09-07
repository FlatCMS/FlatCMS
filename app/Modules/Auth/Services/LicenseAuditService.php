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

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\JsonLineStore;

final class LicenseAuditService
{
    private JsonLineStore $store;
    private string $recordPath;

    public function __construct(?string $path = null, ?JsonLineStore $store = null, ?string $lockRoot = null)
    {
        $path ??= BASE_PATH . '/resources/licenses/audit.jsonl';

        if ($store === null) {
            $auditRoot = dirname($path);
            $lockRoot ??= BASE_PATH . '/storage/cache/locks/licenses';
            $store = new JsonLineStore($auditRoot, new AtomicFileWriter(
                $auditRoot,
                new FileLockManager($lockRoot)
            ));
        }

        $this->store = $store;
        $this->recordPath = basename($path);
    }

    public function record(string $action, array $context = []): void
    {
        $payload = [
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => $context,
        ];

        $this->store->append($this->recordPath, $payload);
    }
}
