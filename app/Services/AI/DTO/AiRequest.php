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

final class AiRequest
{
    /**
     * @param string|array<int, array<string, mixed>> $input
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed>|null $textFormat
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string|array $input,
        public readonly string $instructions = '',
        public readonly ?string $model = null,
        public readonly ?string $previousResponseId = null,
        public readonly array $tools = [],
        public readonly array|string|null $toolChoice = null,
        public readonly ?array $textFormat = null,
        public readonly ?int $maxOutputTokens = null,
        public readonly array $metadata = [],
    ) {
    }
}
