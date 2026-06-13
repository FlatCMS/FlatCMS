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

use App\Services\AI\DTO\AiRequest;
use RuntimeException;

final class EditorialAssistant
{
    private AIManager $ai;

    public function __construct(?AIManager $ai = null)
    {
        $this->ai = $ai ?? new AIManager();
    }

    /**
     * @return array{meta_title:string,meta_description:string}
     */
    public function generateSeo(
        string $entityType,
        string $locale,
        string $title,
        string $content,
        string $excerpt = '',
        string $userInstruction = '',
        array $editorialContext = []
    ): array
    {
        $plainSource = $this->buildPlainSource($title, $content, $excerpt, 7000);
        if ($plainSource === '') {
            throw new RuntimeException('missing_content');
        }

        $payload = $this->requestJson(
            instructions: 'You are a senior editorial SEO assistant. Return only a valid JSON object with the keys meta_title and meta_description. Write in the requested locale. The meta_title must stay under 60 characters and the meta_description under 160 characters. Be specific, natural, and faithful to the provided content. When editorial_context is provided, treat it as a hard guardrail for brand, subject matter, and vocabulary. No markdown, no commentary, no code fences.',
            input: [
                'task' => 'generate_seo',
                'entity_type' => $entityType,
                'locale' => $locale,
                'title' => trim($title),
                'source_text' => $plainSource,
                'editorial_context' => $this->normalizeEditorialContext($editorialContext),
                'user_instruction' => trim($userInstruction),
            ],
            expectedKeys: ['meta_title', 'meta_description'],
            maxOutputTokens: 220
        );

        return [
            'meta_title' => str_limit(trim((string) ($payload['meta_title'] ?? '')), 60, ''),
            'meta_description' => str_limit(trim((string) ($payload['meta_description'] ?? '')), 160, ''),
        ];
    }

    /**
     * @param array<string, string> $currentFields
     * @return array<string, string>
     */
    public function generateContent(
        string $entityType,
        string $locale,
        array $currentFields,
        string $userInstruction = '',
        bool $includeExcerpt = false,
        array $editorialContext = []
    ): array {
        $title = trim((string) ($currentFields['title'] ?? ''));
        $slug = trim((string) ($currentFields['slug'] ?? ''));
        $content = trim((string) ($currentFields['content'] ?? ''));
        $excerpt = trim((string) ($currentFields['excerpt'] ?? ''));
        $instruction = trim($userInstruction);

        if ($title === '' && $slug === '' && $content === '' && $excerpt === '' && $instruction === '') {
            throw new RuntimeException('missing_brief');
        }

        $expectedKeys = ['title', 'content'];
        if ($includeExcerpt) {
            $expectedKeys[] = 'excerpt';
        }
        if ($entityType === 'post') {
            $expectedKeys[] = 'featured_image';
            $expectedKeys[] = 'categories';
        }

        $payload = $this->requestJson(
            instructions: 'You are a senior editorial writer for a CMS. Return only a valid JSON object with the requested keys. Write in the requested locale. Produce polished editorial content, faithful to the provided brief, with clean body HTML only inside the content field. Use paragraphs, headings, and lists only when useful. When editorial_context is provided, treat it as a hard guardrail for brand, subject matter, tone, categories, and vocabulary. Stay aligned with the site editorial line. If the brief is vague, infer a topic from editorial_context and recent_posts instead of inventing an unrelated business, industry, or marketing niche. For posts, categories must be an array of 1 to 3 exact category names chosen from editorial_context.available_categories whenever that list is provided. If editorial_context.available_images is provided, you may only use image paths from that list. Never invent or rewrite image paths. For posts, set featured_image to one relevant unused image path when possible. For pages, if an unused image is relevant, you may include one <img> block inside content using one of those exact paths. Do not output a full HTML document, markdown, commentary, or code fences.',
            input: [
                'task' => 'generate_content',
                'entity_type' => $entityType,
                'locale' => $locale,
                'editorial_context' => $this->normalizeEditorialContext($editorialContext),
                'brief' => [
                    'title' => $title,
                    'slug_hint' => $slug,
                    'existing_excerpt' => $includeExcerpt ? $excerpt : '',
                    'existing_categories' => $this->normalizeAiStringList($currentFields['categories'] ?? []),
                    'existing_content' => $this->limitHtmlForPrompt($content, 9000),
                    'user_instruction' => $instruction,
                ],
            ],
            expectedKeys: $expectedKeys,
            maxOutputTokens: 2200
        );

        $generatedTitle = trim((string) ($payload['title'] ?? ''));
        if ($generatedTitle === '') {
            $generatedTitle = $title;
        }

        $generatedExcerpt = $includeExcerpt ? trim((string) ($payload['excerpt'] ?? '')) : '';
        if ($includeExcerpt && $generatedExcerpt === '') {
            $generatedExcerpt = $excerpt;
        }

        $generatedFeaturedImage = trim((string) ($payload['featured_image'] ?? ''));
        $availableImages = $this->extractAvailableImagePaths($editorialContext);
        if ($generatedFeaturedImage !== '' && !in_array($generatedFeaturedImage, $availableImages, true)) {
            $generatedFeaturedImage = '';
        }
        if ($entityType === 'post' && $generatedFeaturedImage === '') {
            $generatedFeaturedImage = trim((string) ($currentFields['featured_image'] ?? ''));
        }

        $generatedContent = trim((string) ($payload['content'] ?? ''));
        if ($generatedContent === '') {
            $generatedContent = $content;
        }

        $generatedCategories = $entityType === 'post' ? $this->normalizeAiStringList($payload['categories'] ?? []) : [];

        return [
            'title' => $generatedTitle,
            'slug' => $generatedTitle !== '' ? str_slug($generatedTitle) : ($slug !== '' ? $slug : ''),
            'excerpt' => $generatedExcerpt,
            'categories' => $generatedCategories,
            'featured_image' => $entityType === 'post' ? $generatedFeaturedImage : '',
            'content' => $generatedContent,
        ];
    }

    /**
     * @param array<string, string> $currentFields
     * @return array<string, string>
     */
    public function reviseContent(
        string $entityType,
        string $locale,
        array $currentFields,
        string $mode,
        string $userInstruction = '',
        bool $includeExcerpt = false,
        array $editorialContext = []
    ): array {
        $title = trim((string) ($currentFields['title'] ?? ''));
        $slug = trim((string) ($currentFields['slug'] ?? ''));
        $content = trim((string) ($currentFields['content'] ?? ''));
        $excerpt = trim((string) ($currentFields['excerpt'] ?? ''));
        $instruction = trim($userInstruction);

        if ($title === '' && $content === '' && $excerpt === '') {
            throw new RuntimeException('missing_content');
        }

        $expectedKeys = ['title', 'content'];
        if ($includeExcerpt) {
            $expectedKeys[] = 'excerpt';
        }
        if ($entityType === 'post') {
            $expectedKeys[] = 'featured_image';
            $expectedKeys[] = 'categories';
        }

        $modeInstructions = $mode === 'proofread'
            ? 'Correct spelling, grammar, punctuation, and minor stylistic mistakes only. Preserve meaning, tone, approximate length, and HTML structure.'
            : 'Improve clarity, rhythm, and structure while preserving the core meaning. Keep the editorial tone natural. Preserve existing HTML structure when relevant and add clean HTML structure only when it improves readability.';

        $payload = $this->requestJson(
            instructions: 'You are a careful editorial assistant. Return only a valid JSON object with the requested keys. Write in the requested locale. ' . $modeInstructions . ' When editorial_context is provided, keep the result aligned with the site brand, subject matter, and vocabulary. For posts, if editorial_context.available_categories exists, categories must stay an array of exact category names from that list, and you may adjust them when the user asks for stronger coherence. If the user asks for an image and editorial_context.available_images is provided, choose one exact unused path from that list and set featured_image accordingly. You may also insert one relevant <img> block in content using one of those exact paths, but never invent or rewrite image URLs. Translate nothing unless explicitly requested in the user instruction. Do not output markdown, commentary, or code fences.',
            input: [
                'task' => $mode === 'proofread' ? 'proofread_content' : 'enhance_content',
                'entity_type' => $entityType,
                'locale' => $locale,
                'editorial_context' => $this->normalizeEditorialContext($editorialContext),
                'fields' => [
                    'title' => $title,
                    'excerpt' => $includeExcerpt ? $excerpt : '',
                    'categories' => $this->normalizeAiStringList($currentFields['categories'] ?? []),
                    'content' => $this->limitHtmlForPrompt($content, 12000),
                ],
                'user_instruction' => $instruction,
            ],
            expectedKeys: $expectedKeys,
            maxOutputTokens: 2400
        );

        $updatedTitle = trim((string) ($payload['title'] ?? ''));
        if ($updatedTitle === '') {
            $updatedTitle = $title;
        }

        $updatedExcerpt = $includeExcerpt ? trim((string) ($payload['excerpt'] ?? '')) : '';
        if ($includeExcerpt && $updatedExcerpt === '') {
            $updatedExcerpt = $excerpt;
        }

        $updatedFeaturedImage = trim((string) ($payload['featured_image'] ?? ''));
        $availableImages = $this->extractAvailableImagePaths($editorialContext);
        if ($updatedFeaturedImage !== '' && !in_array($updatedFeaturedImage, $availableImages, true)) {
            $updatedFeaturedImage = '';
        }
        if ($entityType === 'post' && $updatedFeaturedImage === '') {
            $updatedFeaturedImage = trim((string) ($currentFields['featured_image'] ?? ''));
        }

        $updatedContent = trim((string) ($payload['content'] ?? ''));
        if ($updatedContent === '') {
            $updatedContent = $content;
        }

        $updatedCategories = $entityType === 'post' ? $this->normalizeAiStringList($payload['categories'] ?? []) : $this->normalizeAiStringList($currentFields['categories'] ?? []);

        return [
            'title' => $updatedTitle,
            'slug' => $updatedTitle !== '' ? str_slug($updatedTitle) : ($slug !== '' ? $slug : ''),
            'excerpt' => $updatedExcerpt,
            'categories' => $updatedCategories,
            'featured_image' => $entityType === 'post' ? $updatedFeaturedImage : trim((string) ($currentFields['featured_image'] ?? '')),
            'content' => $updatedContent,
        ];
    }

    public function summarize(
        string $entityType,
        string $locale,
        string $title,
        string $content,
        string $excerpt = '',
        int $maxCharacters = 260,
        string $userInstruction = '',
        array $editorialContext = []
    ): string
    {
        $plainSource = $this->buildPlainSource($title, $content, $excerpt, 7000);
        if ($plainSource === '') {
            throw new RuntimeException('missing_content');
        }

        $payload = $this->requestJson(
            instructions: 'You are a concise editorial assistant. Return only a valid JSON object with the single key summary. Write in the requested locale. The summary must stay faithful to the source, avoid hype, and stay under the requested character budget. When editorial_context is provided, keep the wording aligned with the site brand and subject matter. No markdown, no commentary, no code fences.',
            input: [
                'task' => 'summarize_content',
                'entity_type' => $entityType,
                'locale' => $locale,
                'title' => trim($title),
                'character_budget' => max(120, $maxCharacters),
                'source_text' => $plainSource,
                'editorial_context' => $this->normalizeEditorialContext($editorialContext),
                'user_instruction' => trim($userInstruction),
            ],
            expectedKeys: ['summary'],
            maxOutputTokens: 220
        );

        return str_limit(trim((string) ($payload['summary'] ?? '')), max(120, $maxCharacters), '');
    }

    /**
     * @param array<string, string> $sourceFields
     * @return array<string, string>
     */
    public function translate(
        string $entityType,
        string $sourceLocale,
        string $targetLocale,
        array $sourceFields,
        bool $includeExcerpt = false,
        string $userInstruction = '',
        array $editorialContext = []
    ): array
    {
        $title = trim((string) ($sourceFields['title'] ?? ''));
        $content = trim((string) ($sourceFields['content'] ?? ''));
        $excerpt = trim((string) ($sourceFields['excerpt'] ?? ''));
        $metaTitle = trim((string) ($sourceFields['meta_title'] ?? ''));
        $metaDescription = trim((string) ($sourceFields['meta_description'] ?? ''));

        if ($title === '' && $content === '' && $excerpt === '' && $metaTitle === '' && $metaDescription === '') {
            throw new RuntimeException('missing_source_content');
        }

        $expectedKeys = ['title', 'content', 'meta_title', 'meta_description'];
        if ($includeExcerpt) {
            $expectedKeys[] = 'excerpt';
        }

        $payload = $this->requestJson(
            instructions: 'You are a careful editorial translator. Return only a valid JSON object with the requested keys. Translate into the requested target locale. Preserve HTML structure inside content: keep tags, attributes, and hierarchy stable, and translate only the visible editorial text. When editorial_context is provided, preserve the site brand, craft vocabulary, and tone. Keep empty source fields empty in the output. Do not return markdown, comments, or code fences.',
            input: [
                'task' => 'translate_content',
                'entity_type' => $entityType,
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'editorial_context' => $this->normalizeEditorialContext($editorialContext),
                'fields' => [
                    'title' => $title,
                    'excerpt' => $includeExcerpt ? $excerpt : '',
                    'content' => $this->limitHtmlForPrompt($content, 12000),
                    'meta_title' => $metaTitle,
                    'meta_description' => $metaDescription,
                ],
                'user_instruction' => trim($userInstruction),
            ],
            expectedKeys: $expectedKeys,
            maxOutputTokens: 2200
        );

        $translatedTitle = trim((string) ($payload['title'] ?? ''));
        if ($translatedTitle === '') {
            $translatedTitle = $title;
        }

        $translatedExcerpt = $includeExcerpt ? trim((string) ($payload['excerpt'] ?? '')) : '';
        if ($includeExcerpt && $translatedExcerpt === '') {
            $translatedExcerpt = $excerpt;
        }

        $translatedContent = trim((string) ($payload['content'] ?? ''));
        if ($translatedContent === '') {
            $translatedContent = $content;
        }

        $translatedMetaTitle = trim((string) ($payload['meta_title'] ?? ''));
        if ($translatedMetaTitle === '' && $metaTitle !== '') {
            $translatedMetaTitle = $metaTitle;
        }

        $translatedMetaDescription = trim((string) ($payload['meta_description'] ?? ''));
        if ($translatedMetaDescription === '' && $metaDescription !== '') {
            $translatedMetaDescription = $metaDescription;
        }

        return [
            'title' => $translatedTitle,
            'slug' => $translatedTitle !== '' ? str_slug($translatedTitle) : str_slug($title),
            'excerpt' => $translatedExcerpt,
            'categories' => $this->normalizeAiStringList($sourceFields['categories'] ?? []),
            'featured_image' => trim((string) ($sourceFields['featured_image'] ?? '')),
            'content' => $translatedContent,
            'meta_title' => $translatedMetaTitle,
            'meta_description' => $translatedMetaDescription,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<int, string> $expectedKeys
     * @return array<string, mixed>
     */
    private function requestJson(string $instructions, array $input, array $expectedKeys, int $maxOutputTokens): array
    {
        $response = $this->ai->respond(new AiRequest(
            input: json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            instructions: $instructions,
            maxOutputTokens: $maxOutputTokens,
            metadata: [
                'flatcms_scope' => 'editorial_assistant',
            ],
        ));

        if ($response->hasRefusal()) {
            $message = trim((string) ($response->refusal?->message ?? ''));
            throw new RuntimeException($message !== '' ? $message : 'provider_refusal');
        }

        $decoded = $this->decodeJsonObject($this->extractOutputText($response));
        $normalized = [];
        foreach ($expectedKeys as $key) {
            $value = $decoded[$key] ?? '';
            if (is_array($value)) {
                $normalized[$key] = $value;
                continue;
            }
            $normalized[$key] = trim((string) $value);
        }

        return $normalized;
    }

    private function extractOutputText(\App\Services\AI\Responses\AiResponse $response): string
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

    private function buildPlainSource(string $title, string $content, string $excerpt, int $limit): string
    {
        $parts = [];
        $title = trim($title);
        if ($title !== '') {
            $parts[] = $title;
        }

        $excerpt = $this->plainText($excerpt, 1200);
        if ($excerpt !== '') {
            $parts[] = $excerpt;
        }

        $content = $this->plainText($content, $limit);
        if ($content !== '') {
            $parts[] = $content;
        }

        return trim(implode("\n\n", $parts));
    }

    private function plainText(string $value, int $limit): string
    {
        $text = trim($value);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('~<\s*br\s*/?>~i', "\n", $text) ?? $text;
        $text = preg_replace('~</p\s*>~i', "\n\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = trim($text);

        return str_limit($text, max(400, $limit), '');
    }

    private function limitHtmlForPrompt(string $html, int $limit): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        return str_limit($html, max(1200, $limit), '');
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function normalizeEditorialContext(array $context): array
    {
        if ($context === []) {
            return [];
        }

        $normalized = [
            'site_name' => trim((string) ($context['site_name'] ?? '')),
            'site_description' => $this->plainText((string) ($context['site_description'] ?? ''), 280),
            'site_slogan' => $this->plainText((string) ($context['site_slogan'] ?? ''), 160),
            'selected_categories' => [],
            'available_categories' => [],
            'recent_posts' => [],
            'available_images' => [],
        ];

        foreach (['selected_categories', 'available_categories'] as $key) {
            $values = is_array($context[$key] ?? null) ? $context[$key] : [];
            $normalized[$key] = array_values(array_slice(array_filter(array_map(static function ($value): string {
                return trim((string) $value);
            }, $values), static function (string $value): bool {
                return $value !== '';
            }), 0, 10));
        }

        $recentPosts = is_array($context['recent_posts'] ?? null) ? $context['recent_posts'] : [];
        foreach (array_slice($recentPosts, 0, 4) as $post) {
            if (!is_array($post)) {
                continue;
            }

            $title = trim((string) ($post['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $normalized['recent_posts'][] = [
                'title' => $title,
                'excerpt' => $this->plainText((string) ($post['excerpt'] ?? ''), 260),
                'categories' => array_values(array_slice(array_filter(array_map(static function ($value): string {
                    return trim((string) $value);
                }, is_array($post['categories'] ?? null) ? $post['categories'] : []), static function (string $value): bool {
                    return $value !== '';
                }), 0, 6)),
            ];
        }

        $availableImages = is_array($context['available_images'] ?? null) ? $context['available_images'] : [];
        foreach (array_slice($availableImages, 0, 12) as $image) {
            if (!is_array($image)) {
                continue;
            }

            $path = trim((string) ($image['path'] ?? ''));
            if ($path === '' || !str_starts_with($path, '/uploads/images/')) {
                continue;
            }

            $normalized['available_images'][] = [
                'path' => $path,
                'label' => $this->plainText((string) ($image['label'] ?? ''), 120),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, string>
     */
    private function extractAvailableImagePaths(array $context): array
    {
        $items = is_array($context['available_images'] ?? null) ? $context['available_images'] : [];
        $paths = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $path = trim((string) ($item['path'] ?? ''));
            if ($path === '' || !str_starts_with($path, '/uploads/images/') || in_array($path, $paths, true)) {
                continue;
            }
            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * @param mixed $values
     * @return array<int, string>
     */
    private function normalizeAiStringList(mixed $values): array
    {
        if (is_string($values)) {
            $values = preg_split('/\s*,\s*/', trim($values)) ?: [];
        }

        if (!is_array($values)) {
            return [];
        }

        $normalized = [];
        foreach ($values as $value) {
            $text = trim((string) $value);
            if ($text === '' || in_array($text, $normalized, true)) {
                continue;
            }
            $normalized[] = $text;
        }

        return array_slice($normalized, 0, 3);
    }
}
