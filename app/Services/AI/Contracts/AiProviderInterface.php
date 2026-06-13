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

use App\Services\AI\DTO\AiRequest;
use App\Services\AI\Responses\AiResponse;

interface AiProviderInterface
{
    public function respond(AiRequest $request): AiResponse;

    public function isConfigured(): bool;

    public function getProviderName(): string;

    public function supportsTools(): bool;

    public function supportsStructuredOutputs(): bool;

    public function supportsConversationState(): bool;

    /**
     * @return array<string, mixed>
     */
    public function configurationStatus(): array;
}
