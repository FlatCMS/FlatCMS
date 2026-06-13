<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\AI\Responses;

use App\Services\AI\DTO\AiRefusal;
use App\Services\AI\DTO\AiToolCall;
use App\Services\AI\DTO\AiUsage;

final class AiResponse
{
    /**
     * @param array<int, array<string, mixed>> $outputItems
     * @param array<int, AiToolCall> $toolCalls
     * @param array<string, mixed> $rawMetadata
     */
    public function __construct(
        public readonly string $responseId,
        public readonly string $provider,
        public readonly string $model,
        public readonly string $outputText = '',
        public readonly array $outputItems = [],
        public readonly array $toolCalls = [],
        public readonly ?AiRefusal $refusal = null,
        public readonly ?AiUsage $usage = null,
        public readonly array $rawMetadata = [],
    ) {
    }

    public function hasRefusal(): bool
    {
        return $this->refusal instanceof AiRefusal;
    }
}
