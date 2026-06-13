<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services;

final class UpdateArtifactService
{
    public function hasArtifactBinding(array $package): bool
    {
        return false;
    }

    /**
     * @param array<string, mixed> $package
     * @return array<string, mixed>|null
     */
    public function resolveArtifact(array $package): ?array
    {
        return null;
    }
}
