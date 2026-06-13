<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\AI\Providers;

use App\Core\Security\SecretBox;
use App\Services\AI\Contracts\AiProviderInterface;
use App\Services\AI\DTO\AiRefusal;
use App\Services\AI\DTO\AiRequest;
use App\Services\AI\DTO\AiToolCall;
use App\Services\AI\DTO\AiUsage;
use App\Services\AI\Exceptions\AiConfigurationException;
use App\Services\AI\Exceptions\AiProviderException;
use App\Services\AI\Responses\AiResponse;

final class OpenAiResponsesProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private int $timeout;
    private int $maxOutputTokens;

    public function __construct()
    {
        $this->apiKey = trim((new SecretBox())->decrypt((string) env('OPENAI_API_KEY', '')));
        $configuredBaseUrl = trim((string) env('OPENAI_API_BASE_URL', ''));
        if ($configuredBaseUrl === '') {
            $configuredBaseUrl = 'https://api.openai.com/v1';
        }
        $this->baseUrl = rtrim($configuredBaseUrl, '/');
        $this->model = trim((string) env('OPENAI_RESPONSES_MODEL', 'gpt-5.4-mini'));
        $this->timeout = max(5, (int) env('OPENAI_TIMEOUT', 20));
        $this->maxOutputTokens = max(64, (int) env('OPENAI_MAX_OUTPUT_TOKENS', 800));
    }

    public function respond(AiRequest $request): AiResponse
    {
        if (!$this->isConfigured()) {
            throw new AiConfigurationException('OPENAI_API_KEY is not configured.');
        }

        $payload = [
            'model' => $request->model !== null && trim($request->model) !== '' ? trim($request->model) : $this->model,
            'input' => $request->input,
        ];

        if ($request->instructions !== '') {
            $payload['instructions'] = $request->instructions;
        }

        if ($request->previousResponseId !== null && trim($request->previousResponseId) !== '') {
            $payload['previous_response_id'] = trim($request->previousResponseId);
        }

        if ($request->tools !== []) {
            $payload['tools'] = $request->tools;
        }

        if ($request->toolChoice !== null && $request->toolChoice !== '') {
            $payload['tool_choice'] = $request->toolChoice;
        }

        if ($request->textFormat !== null && $request->textFormat !== []) {
            $payload['text'] = [
                'format' => $request->textFormat,
            ];
        }

        $maxOutputTokens = $request->maxOutputTokens ?? $this->maxOutputTokens;
        if ($maxOutputTokens > 0) {
            $payload['max_output_tokens'] = $maxOutputTokens;
        }

        if ($request->metadata !== []) {
            $payload['metadata'] = $request->metadata;
        }

        $decoded = $this->postJson($this->baseUrl . '/responses', $payload);

        return $this->normalizeResponse($decoded);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function getProviderName(): string
    {
        return 'openai-responses';
    }

    public function supportsTools(): bool
    {
        return true;
    }

    public function supportsStructuredOutputs(): bool
    {
        return true;
    }

    public function supportsConversationState(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function configurationStatus(): array
    {
        $issues = [];
        if ($this->apiKey === '') {
            $issues[] = 'missing_api_key';
        }
        if ($this->model === '') {
            $issues[] = 'missing_model';
        }
        if (!$this->transportReady()) {
            $issues[] = 'missing_http_transport';
        }

        return [
            'provider' => $this->getProviderName(),
            'configured' => $this->isConfigured() && $this->model !== '',
            'transport_ready' => $this->transportReady(),
            'endpoint' => $this->baseUrl . '/responses',
            'model' => $this->model,
            'timeout' => $this->timeout,
            'max_output_tokens' => $this->maxOutputTokens,
            'supports_tools' => $this->supportsTools(),
            'supports_structured_outputs' => $this->supportsStructuredOutputs(),
            'supports_conversation_state' => $this->supportsConversationState(),
            'issues' => $issues,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function postJson(string $url, array $payload): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($body)) {
            throw new AiProviderException('Unable to encode request payload.');
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new AiProviderException('Unable to initialize cURL.');
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
            ]);

            $response = curl_exec($ch);
            $errNo = curl_errno($ch);
            $error = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $errNo !== 0) {
                throw new AiProviderException($error !== '' ? $error : 'OpenAI request failed.');
            }

            return $this->decodeResponse((string) $response, $httpCode);
        }

        if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL)) {
            throw new AiProviderException('No supported HTTP transport is available.');
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                    'Content-Length: ' . strlen($body),
                ]),
                'content' => $body,
                'timeout' => $this->timeout,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new AiProviderException('OpenAI request failed.');
        }

        $status = (string) ($http_response_header[0] ?? '');
        $httpCode = 200;
        if ($status !== '' && preg_match('#^HTTP/\S+\s+(\d{3})#', $status, $matches) === 1) {
            $httpCode = (int) $matches[1];
        }

        return $this->decodeResponse((string) $response, $httpCode);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(string $response, int $httpCode): array
    {
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new AiProviderException('Invalid OpenAI response payload.');
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $message = trim((string) ($decoded['error']['message'] ?? 'OpenAI request failed.'));
            throw new AiProviderException($message);
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function normalizeResponse(array $decoded): AiResponse
    {
        $outputItems = is_array($decoded['output'] ?? null) ? $decoded['output'] : [];
        $outputText = trim((string) ($decoded['output_text'] ?? ''));
        $toolCalls = [];
        $refusal = null;

        foreach ($outputItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemType = trim((string) ($item['type'] ?? ''));
            if ($itemType === 'message') {
                $contentItems = is_array($item['content'] ?? null) ? $item['content'] : [];
                foreach ($contentItems as $contentItem) {
                    if (!is_array($contentItem)) {
                        continue;
                    }

                    $contentType = trim((string) ($contentItem['type'] ?? ''));
                    if ($contentType === 'output_text') {
                        $text = trim((string) ($contentItem['text'] ?? ''));
                        if ($text !== '') {
                            $outputText .= ($outputText !== '' ? "\n\n" : '') . $text;
                        }
                    } elseif ($contentType === 'refusal') {
                        $message = trim((string) ($contentItem['refusal'] ?? $contentItem['text'] ?? ''));
                        if ($message !== '') {
                            $refusal = new AiRefusal($message, 'refusal');
                        }
                    }
                }
                continue;
            }

            if ($itemType === 'function_call') {
                $rawArguments = trim((string) ($item['arguments'] ?? ''));
                $arguments = [];
                if ($rawArguments !== '') {
                    $decodedArgs = json_decode($rawArguments, true);
                    if (is_array($decodedArgs)) {
                        $arguments = $decodedArgs;
                    }
                }

                $toolCalls[] = new AiToolCall(
                    trim((string) ($item['call_id'] ?? '')),
                    trim((string) ($item['name'] ?? '')),
                    $arguments,
                    $rawArguments
                );
                continue;
            }

            if ($itemType === 'refusal') {
                $message = trim((string) ($item['refusal'] ?? $item['message'] ?? ''));
                if ($message !== '') {
                    $refusal = new AiRefusal($message, 'refusal');
                }
            }
        }

        $usageData = is_array($decoded['usage'] ?? null) ? $decoded['usage'] : [];
        $usage = new AiUsage(
            (int) ($usageData['input_tokens'] ?? 0),
            (int) ($usageData['output_tokens'] ?? 0),
            (int) ($usageData['total_tokens'] ?? 0),
        );

        return new AiResponse(
            trim((string) ($decoded['id'] ?? '')),
            $this->getProviderName(),
            trim((string) ($decoded['model'] ?? $this->model)),
            $outputText,
            $outputItems,
            $toolCalls,
            $refusal,
            $usage,
            $decoded,
        );
    }

    private function transportReady(): bool
    {
        if (function_exists('curl_init')) {
            return true;
        }

        return filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL);
    }
}
