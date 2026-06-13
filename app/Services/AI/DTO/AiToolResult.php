<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\AI\DTO;

final class AiToolResult
{
    /**
     * @param array<string, mixed> $output
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $name,
        public readonly string $callId = '',
        public readonly bool $success = true,
        public readonly array $output = [],
        public readonly array $metadata = [],
    ) {
    }
}
