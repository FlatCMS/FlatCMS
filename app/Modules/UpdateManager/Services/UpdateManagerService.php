<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Services;

final class UpdateManagerService
{
    public function __construct(private ?UpdateCheckService $checks = null)
    {
        $this->checks ??= new UpdateCheckService();
    }

    /** @return array<string, mixed> */
    public function status(): array
    {
        return $this->checks->status(false);
    }

    /** @return array<string, mixed> */
    public function checkNow(): array
    {
        return $this->checks->status(true);
    }

    /** @return array<string, mixed>|null */
    public function cachedStatus(): ?array
    {
        return $this->checks->cachedStatus();
    }
}
