<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Media\Services;

use App\Core\FlatFile;
use App\Modules\Media\Models\MediaModel;
use App\Services\AI\AIManager;
use App\Services\AI\DTO\AiRequest;
use App\Services\AI\Exceptions\AiConfigurationException;
use App\Services\AI\Exceptions\AiProviderException;
use App\Services\AI\Responses\AiResponse;
use RuntimeException;
use Throwable;

final class MediaAiIndexService
{
    private const AI_FILE_LIMIT_BYTES = 50 * 1024 * 1024;

    private MediaModel $mediaModel;
    private AIManager $ai;

    public function __construct(?MediaModel $mediaModel = null, ?AIManager $ai = null)
    {
        $this->mediaModel = $mediaModel ?? new MediaModel();
        $this->ai = $ai ?? new AIManager();
    }

    public function isConfigured(): bool
    {
        return $this->ai->isConfigured();
    }

    /**
     * @param array<int, string> $paths
     * @return array{
     *   indexed:int,
     *   skipped:int,
     *   failed:int,
     *   completed_paths:array<int, string>,
     *   failed_paths:array<int, string>,
     *   results:array<int, array<string, mixed>>
     * }
     */
    public function index(?string $folder = null, array $paths = []): array
    {
        if (!$this->ai->isConfigured()) {
            throw new AiConfigurationException('OPENAI_API_KEY is not configured.');
        }

        $targets = $this->resolveTargets($folder, $paths);
        $result = [
            'indexed' => 0,
            'skipped' => 0,
            'failed' => 0,
            'completed_paths' => [],
            'failed_paths' => [],
            'results' => [],
        ];

        foreach ($targets as $target) {
            $path = trim((string) ($target['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            try {
                $persisted = $this->mediaModel->ensurePersisted($target);
                if (!is_array($persisted)) {
                    throw new RuntimeException('media_persist_failed');
                }

                $absolutePath = $this->mediaModel->getAbsolutePath($path);
                if ($absolutePath === null) {
                    throw new RuntimeException('media_file_missing');
                }

                $sourceHash = sha1_file($absolutePath) ?: sha1($path);
                $alreadyIndexed = trim((string) ($persisted['ai_index_status'] ?? '')) === 'indexed';
                $previousHash = trim((string) ($persisted['ai_source_hash'] ?? ''));

                if ($alreadyIndexed && $previousHash !== '' && hash_equals($previousHash, $sourceHash)) {
                    $result['skipped']++;
                    $result['completed_paths'][] = $path;
                    $result['results'][] = [
                        'path' => $path,
                        'status' => 'skipped',
                    ];
                    continue;
                }

                $metadata = $this->buildMetadata($persisted, $absolutePath);
                $updated = $this->mediaModel->update((int) $persisted['id'], [
                    'ai_index_status' => 'indexed',
                    'ai_indexed_at' => date('Y-m-d H:i:s'),
                    'ai_source_hash' => $sourceHash,
                    'ai_last_error' => null,
                    'ai_metadata' => $metadata,
                ]);

                if (!is_array($updated)) {
                    throw new RuntimeException('media_index_update_failed');
                }

                $result['indexed']++;
                $result['completed_paths'][] = $path;
                $result['results'][] = [
                    'path' => $path,
                    'status' => 'indexed',
                    'analysis_mode' => (string) ($metadata['analysis_mode'] ?? ''),
                ];
            } catch (Throwable $exception) {
                $persisted = $this->mediaModel->findByPath($path);
                if (is_array($persisted) && (int) ($persisted['id'] ?? 0) > 0) {
                    $this->mediaModel->update((int) $persisted['id'], [
                        'ai_index_status' => 'failed',
                        'ai_last_error' => $this->normalizeErrorMessage($exception),
                    ]);
                }

                $result['failed']++;
                $result['failed_paths'][] = $path;
                $result['results'][] = [
                    'path' => $path,
                    'status' => 'failed',
                    'error' => $this->normalizeErrorMessage($exception),
                ];
            }
        }

        $result['completed_paths'] = array_values(array_unique($result['completed_paths']));
        $result['failed_paths'] = array_values(array_unique($result['failed_paths']));

        return $result;
    }

    /**
     * @param array<int, string> $paths
     * @return array<int, array<string, mixed>>
     */
    private function resolveTargets(?string $folder, array $paths): array
    {
        $targets = [];

        foreach ($paths as $rawPath) {
            $path = trim(str_replace('\\', '/', (string) $rawPath), '/');
            if ($path === '') {
                continue;
            }

            $record = $this->mediaModel->findByPath($path);
            if (is_array($record)) {
                $targets[$path] = $record;
            }
        }

        if ($targets !== []) {
            return array_values($targets);
        }

        if (is_string($folder) && $folder !== '') {
            foreach ($this->mediaModel->scanFolder($folder) as $record) {
                $path = trim((string) ($record['path'] ?? ''));
                if ($path !== '') {
                    $targets[$path] = $record;
                }
            }

            return array_values($targets);
        }

        foreach ($this->mediaModel->scanAllFolders() as $record) {
            $path = trim((string) ($record['path'] ?? ''));
            if ($path !== '') {
                $targets[$path] = $record;
            }
        }

        return array_values($targets);
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function buildMetadata(array $record, string $absolutePath): array
    {
        $folder = trim((string) ($record['folder'] ?? ''));
        $mime = strtolower(trim((string) ($record['mime'] ?? 'application/octet-stream')));
        $size = (int) (@filesize($absolutePath) ?: 0);

        if ($size > self::AI_FILE_LIMIT_BYTES) {
            return $this->buildTechnicalMetadata($record, 'oversize_for_ai');
        }

        if ($folder === 'images' && str_starts_with($mime, 'image/')) {
            return $this->analyzeImage($record, $absolutePath);
        }

        if (in_array($folder, ['documents', 'pdf', 'spreadsheets'], true)) {
            return $this->analyzeDocument($record, $absolutePath);
        }

        return $this->buildTechnicalMetadata($record, 'technical_only');
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function analyzeImage(array $record, string $absolutePath): array
    {
        $mime = strtolower(trim((string) ($record['mime'] ?? 'image/png')));
        $binary = @file_get_contents($absolutePath);
        if (!is_string($binary) || $binary === '') {
            throw new RuntimeException('media_read_failed');
        }

        $siteContext = $this->siteContext();
        $input = [[
            'role' => 'user',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => $this->buildPromptContext($record, $siteContext, true),
                ],
                [
                    'type' => 'input_image',
                    'image_url' => 'data:' . $mime . ';base64,' . base64_encode($binary),
                    'detail' => 'low',
                ],
            ],
        ]];

        return $this->normalizeMetadata(
            $record,
            $this->requestStructuredMetadata($input, true),
            'ai_visual'
        );
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function analyzeDocument(array $record, string $absolutePath): array
    {
        $binary = @file_get_contents($absolutePath);
        if (!is_string($binary) || $binary === '') {
            throw new RuntimeException('media_read_failed');
        }

        $siteContext = $this->siteContext();
        $input = [[
            'role' => 'user',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => $this->buildPromptContext($record, $siteContext, false),
                ],
                [
                    'type' => 'input_file',
                    'filename' => trim((string) ($record['original_name'] ?? $record['name'] ?? 'document')),
                    'file_data' => base64_encode($binary),
                ],
            ],
        ]];

        return $this->normalizeMetadata(
            $record,
            $this->requestStructuredMetadata($input, false),
            'ai_file'
        );
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function buildTechnicalMetadata(array $record, string $mode): array
    {
        $folder = trim((string) ($record['folder'] ?? 'documents'));
        $extension = strtolower(trim((string) ($record['extension'] ?? pathinfo((string) ($record['name'] ?? ''), PATHINFO_EXTENSION))));
        $originalName = trim((string) ($record['original_name'] ?? $record['name'] ?? 'file'));
        $size = (int) ($record['size'] ?? 0);

        $summary = sprintf(
            '%s asset in the %s folder (%s).',
            ucfirst($folder),
            $folder,
            MediaModel::formatSize($size)
        );

        return [
            'analysis_mode' => $mode,
            'summary' => $summary,
            'tags' => array_values(array_filter([$folder, $extension])),
            'topics' => [$folder],
            'mood' => [],
            'entities' => [$originalName],
            'usable_for' => $this->defaultUsableFor($folder),
            'suggested_categories' => [],
            'suggested_filename' => $this->sanitizeSuggestedFilename($originalName, $extension),
            'alt_default' => '',
            'caption_default' => '',
            'description_default' => $summary,
            'site_locale' => $this->siteContext()['locale'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $input
     * @return array<string, mixed>
     */
    private function requestStructuredMetadata(array $input, bool $isImage): array
    {
        $response = $this->ai->respond(new AiRequest(
            input: $input,
            instructions: $this->buildSystemInstructions($isImage),
            textFormat: $this->jsonSchemaFormat(),
            maxOutputTokens: 900,
            metadata: [
                'flatcms_scope' => 'media_ai_index',
                'flatcms_media_mode' => $isImage ? 'image' : 'document',
            ],
        ));

        if ($response->hasRefusal()) {
            $message = trim((string) ($response->refusal?->message ?? ''));
            throw new RuntimeException($message !== '' ? $message : 'provider_refusal');
        }

        return $this->decodeJsonObject($this->extractOutputText($response));
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizeMetadata(array $record, array $payload, string $mode): array
    {
        $folder = trim((string) ($record['folder'] ?? 'documents'));
        $extension = strtolower(trim((string) ($record['extension'] ?? pathinfo((string) ($record['name'] ?? ''), PATHINFO_EXTENSION))));
        $originalName = trim((string) ($record['original_name'] ?? $record['name'] ?? 'file'));
        $summary = trim((string) ($payload['summary'] ?? ''));

        if ($summary === '') {
            $summary = trim((string) ($payload['description_default'] ?? ''));
        }
        if ($summary === '') {
            $summary = $originalName;
        }

        return [
            'analysis_mode' => $mode,
            'summary' => $summary,
            'tags' => $this->normalizeStringList($payload['tags'] ?? []),
            'topics' => $this->normalizeStringList($payload['topics'] ?? []),
            'mood' => $this->normalizeStringList($payload['mood'] ?? []),
            'entities' => $this->normalizeStringList($payload['entities'] ?? []),
            'usable_for' => $this->normalizeUsableFor($payload['usable_for'] ?? [], $folder),
            'suggested_categories' => $this->normalizeStringList($payload['suggested_categories'] ?? []),
            'suggested_filename' => $this->sanitizeSuggestedFilename(
                trim((string) ($payload['suggested_filename'] ?? '')),
                $extension,
                $summary
            ),
            'alt_default' => trim((string) ($payload['alt_default'] ?? '')),
            'caption_default' => trim((string) ($payload['caption_default'] ?? '')),
            'description_default' => trim((string) ($payload['description_default'] ?? '')),
            'site_locale' => $this->siteContext()['locale'],
        ];
    }

    /**
     * @param array<string, mixed> $record
     * @param array<string, string> $siteContext
     */
    private function buildPromptContext(array $record, array $siteContext, bool $isImage): string
    {
        $dimensions = $record['dimensions'] ?? null;
        $dimensionText = '';
        if (is_array($dimensions)) {
            $width = (int) ($dimensions['width'] ?? 0);
            $height = (int) ($dimensions['height'] ?? 0);
            if ($width > 0 && $height > 0) {
                $dimensionText = sprintf('%dx%d', $width, $height);
            }
        }

        $parts = [
            'FlatCMS media indexing request.',
            'Site name: ' . ($siteContext['site_name'] !== '' ? $siteContext['site_name'] : 'FlatCMS'),
            'Site description: ' . ($siteContext['site_description'] !== '' ? $siteContext['site_description'] : 'No description.'),
            'Site slogan: ' . ($siteContext['site_slogan'] !== '' ? $siteContext['site_slogan'] : 'No slogan.'),
            'Default locale: ' . $siteContext['locale'],
            'Folder: ' . trim((string) ($record['folder'] ?? 'documents')),
            'Original filename: ' . trim((string) ($record['original_name'] ?? $record['name'] ?? 'file')),
            'Extension: ' . strtolower(trim((string) ($record['extension'] ?? ''))),
            'MIME: ' . strtolower(trim((string) ($record['mime'] ?? 'application/octet-stream'))),
        ];

        if ($dimensionText !== '') {
            $parts[] = 'Dimensions: ' . $dimensionText;
        }

        $parts[] = $isImage
            ? 'Analyze the actual visual content, not the technical filename.'
            : 'Analyze the document or file content first, not just the filename.';

        return implode("\n", $parts);
    }

    private function buildSystemInstructions(bool $isImage): string
    {
        $target = $isImage ? 'image' : 'document';

        return implode("\n", [
            'You are the FlatCMS media indexing assistant.',
            'Return structured metadata only.',
            'Use the site default locale when writing summary, alt, caption, and description.',
            'Never invent a file path or category tree.',
            'If the asset is generic, keep the description factual and concise.',
            'For suggested_filename, return an SEO-friendly filename stem or filename with extension based on the asset meaning.',
            'For usable_for, choose only from: post_featured_image, page_content, hero, gallery, download, reference, attachment, branding, cover.',
            'For alt_default, write concise descriptive alt text only when it makes sense for the ' . $target . '.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonSchemaFormat(): array
    {
        return [
            'type' => 'json_schema',
            'name' => 'flatcms_media_metadata',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'summary' => ['type' => 'string'],
                    'tags' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'topics' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'mood' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'entities' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'usable_for' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'suggested_categories' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'suggested_filename' => ['type' => 'string'],
                    'alt_default' => ['type' => 'string'],
                    'caption_default' => ['type' => 'string'],
                    'description_default' => ['type' => 'string'],
                ],
                'required' => [
                    'summary',
                    'tags',
                    'topics',
                    'mood',
                    'entities',
                    'usable_for',
                    'suggested_categories',
                    'suggested_filename',
                    'alt_default',
                    'caption_default',
                    'description_default',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function siteContext(): array
    {
        $settings = FlatFile::settings();

        return [
            'site_name' => trim((string) ($settings['site_name'] ?? config('app.name', 'FlatCMS'))),
            'site_description' => trim((string) ($settings['site_description'] ?? '')),
            'site_slogan' => trim((string) ($settings['site_slogan'] ?? '')),
            'locale' => trim((string) ($settings['default_language'] ?? config('app.locale', 'fr-FR'))),
        ];
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            $text = trim((string) $item);
            if ($text === '') {
                continue;
            }

            $items[$text] = $text;
        }

        return array_values($items);
    }

    /**
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeUsableFor(mixed $value, string $folder): array
    {
        $allowed = [
            'post_featured_image',
            'page_content',
            'hero',
            'gallery',
            'download',
            'reference',
            'attachment',
            'branding',
            'cover',
        ];

        $items = array_values(array_intersect(
            $allowed,
            $this->normalizeStringList($value)
        ));

        if ($items !== []) {
            return $items;
        }

        return $this->defaultUsableFor($folder);
    }

    /**
     * @return array<int, string>
     */
    private function defaultUsableFor(string $folder): array
    {
        return match ($folder) {
            'images' => ['post_featured_image', 'page_content', 'gallery'],
            'documents', 'pdf', 'spreadsheets' => ['download', 'reference', 'attachment'],
            'sounds', 'videos' => ['attachment', 'reference'],
            'archives' => ['attachment'],
            default => ['reference'],
        };
    }

    private function sanitizeSuggestedFilename(string $candidate, string $extension, string $fallback = ''): string
    {
        $value = trim($candidate);
        if ($value === '') {
            $value = trim($fallback);
        }

        if ($value === '') {
            $value = 'flatcms-media';
        }

        $value = preg_replace('~\.[a-z0-9]{2,6}$~i', '', $value) ?? $value;
        $slug = str_slug($value);
        if ($slug === '') {
            $slug = 'flatcms-media';
        }

        $extension = strtolower(trim($extension));
        return $extension !== '' ? $slug . '.' . $extension : $slug;
    }

    private function extractOutputText(AiResponse $response): string
    {
        $text = trim($response->outputText);
        if ($text !== '') {
            return $text;
        }

        foreach ($response->outputItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $candidate = trim((string) ($item['text'] ?? $item['content'] ?? ''));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        throw new RuntimeException('empty_output');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $raw): array
    {
        $payload = trim($raw);
        if ($payload === '') {
            throw new RuntimeException('empty_output');
        }

        if (str_starts_with($payload, '```')) {
            $payload = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $payload) ?? $payload;
            $payload = trim($payload);
        }

        $decoded = json_decode($payload, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($payload, '{');
        $end = strrpos($payload, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('invalid_json_output');
        }

        $snippet = substr($payload, $start, $end - $start + 1);
        $decoded = json_decode($snippet, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('invalid_json_output');
        }

        return $decoded;
    }

    private function normalizeErrorMessage(Throwable $exception): string
    {
        if ($exception instanceof AiProviderException || $exception instanceof AiConfigurationException) {
            return trim($exception->getMessage());
        }

        $message = trim($exception->getMessage());
        return $message !== '' ? $message : 'media_ai_index_failed';
    }
}
