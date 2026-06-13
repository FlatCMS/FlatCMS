<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\DTO\AiToolResult;

interface AiToolInterface
{
    public function getName(): string;

    public function getDescription(): string;

    /**
     * @return array<string, mixed>
     */
    public function getParametersSchema(): array;

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $context
     */
    public function execute(array $arguments, array $context = []): AiToolResult;
}
