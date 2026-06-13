<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Contracts\AiProviderInterface;
use App\Services\AI\DTO\AiRequest;
use App\Services\AI\Exceptions\AiConfigurationException;
use App\Services\AI\Providers\OpenAiResponsesProvider;
use App\Services\AI\Responses\AiResponse;
use App\Services\AI\Tools\ToolRegistry;

final class AIManager
{
    private AiProviderInterface $provider;
    private ToolRegistry $tools;

    public function __construct(?AiProviderInterface $provider = null, ?ToolRegistry $tools = null)
    {
        $this->provider = $provider ?? new OpenAiResponsesProvider();
        $this->tools = $tools ?? new ToolRegistry();
    }

    public function provider(): AiProviderInterface
    {
        return $this->provider;
    }

    public function isConfigured(): bool
    {
        return $this->provider->isConfigured();
    }

    /**
     * @return array<string, mixed>
     */
    public function configurationStatus(): array
    {
        $status = $this->provider->configurationStatus();
        $status['tool_count'] = count($this->tools->all());
        return $status;
    }

    public function respond(AiRequest $request): AiResponse
    {
        if (!$this->provider->isConfigured()) {
            throw new AiConfigurationException('AI provider is not configured.');
        }

        if ($request->tools === [] && $this->tools->all() !== []) {
            $request = new AiRequest(
                input: $request->input,
                instructions: $request->instructions,
                model: $request->model,
                previousResponseId: $request->previousResponseId,
                tools: $this->tools->definitions(),
                toolChoice: $request->toolChoice,
                textFormat: $request->textFormat,
                maxOutputTokens: $request->maxOutputTokens,
                metadata: $request->metadata,
            );
        }

        return $this->provider->respond($request);
    }
}
