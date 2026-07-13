<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\AiAgent\Services;

use App\Core\FlatFile;
use App\Modules\Categories\Services\CategoryTranslationService;
use App\Modules\Media\Models\MediaModel;
use App\Modules\Pages\Services\PageTranslationService;
use App\Modules\Posts\Services\PostTranslationService;
use App\Modules\AiAgent\Support\FlattyPersona;
use App\Services\AI\AIManager;
use App\Services\AI\DTO\AiRequest;
use App\Services\AI\EditorialAssistant;
use App\Services\AI\Responses\AiResponse;
use RuntimeException;

final class AdminAssistantService
{
    private AIManager $ai;
    private EditorialAssistant $editorial;

    public function __construct(?AIManager $ai = null, ?EditorialAssistant $editorial = null)
    {
        $this->ai = $ai ?? new AIManager();
        $this->editorial = $editorial ?? new EditorialAssistant($this->ai);
    }

    /**
     * @param array<string, mixed> $context
     * @return array{intent:string,proposal_type:string,proposal:array<string,mixed>,chips:array<int,string>}
     */
    public function handle(array $context, string $message, string $action = ''): array
    {
        if (!$this->ai->isConfigured()) {
            throw new RuntimeException('ai_not_configured');
        }

        $ctx = $this->normalizeContext($context);
        $intent = $this->resolveIntent($ctx, $message, $action);
        $entityType = $ctx['entity'];
        $locale = $ctx['locale'];
        $sourceLocale = $ctx['source_locale'];
        $current = $ctx['current'];
        $editorialContext = $this->buildEditorialContext($ctx);

        switch ($intent) {
            case 'field_fill':
            case 'field_improve':
            case 'field_translate':
                return [
                    'intent' => $intent,
                    'proposal_type' => 'field_variants',
                    'proposal' => [
                        'field_key' => $ctx['field'],
                        'field_label' => $ctx['label'],
                        'variants' => $this->generateFieldVariants($ctx, $message, $intent, $editorialContext),
                    ],
                    'chips' => $this->fieldFollowUpChips($ctx, $intent),
                ];

            case 'block_generate':
                $generatedValues = $this->editorial->generateContent($entityType, $locale, $current, $message, $ctx['has_excerpt'], $editorialContext);
                return [
                    'intent' => $intent,
                    'proposal_type' => 'content_block',
                    'proposal' => [
                        'values' => $this->finalizePostContentValues($ctx, $generatedValues),
                    ],
                    'chips' => $this->contentFollowUpChips($intent, $ctx),
                ];

            case 'block_improve':
                $improvedValues = $this->editorial->reviseContent($entityType, $locale, $current, 'enhance', $message, $ctx['has_excerpt'], $editorialContext);
                return [
                    'intent' => $intent,
                    'proposal_type' => 'content_block',
                    'proposal' => [
                        'values' => $this->finalizePostContentValues($ctx, $improvedValues),
                    ],
                    'chips' => $this->contentFollowUpChips($intent, $ctx),
                ];

            case 'block_proofread':
                $proofreadValues = $this->editorial->reviseContent($entityType, $locale, $current, 'proofread', $message, $ctx['has_excerpt'], $editorialContext);
                return [
                    'intent' => $intent,
                    'proposal_type' => 'content_block',
                    'proposal' => [
                        'values' => $this->finalizePostContentValues($ctx, $proofreadValues),
                    ],
                    'chips' => $this->contentFollowUpChips($intent, $ctx),
                ];

            case 'block_translate':
                if ($locale === $sourceLocale) {
                    throw new RuntimeException('same_locale');
                }

                $source = $this->resolveSourceFields($ctx);
                return [
                    'intent' => $intent,
                    'proposal_type' => 'content_block',
                    'proposal' => [
                        'values' => $this->finalizePostContentValues(
                            $ctx,
                            $this->editorial->translate($entityType, $sourceLocale, $locale, $source, $ctx['has_excerpt'], $message, $editorialContext)
                        ),
                    ],
                    'chips' => $this->contentFollowUpChips($intent, $ctx),
                ];

            case 'block_summary':
                $summaryBudget = ($ctx['module'] ?? '') === 'posts' ? 320 : 520;
                return [
                    'intent' => $intent,
                    'proposal_type' => 'summary',
                    'proposal' => [
                        'summary' => $this->editorial->summarize(
                            $entityType,
                            $locale,
                            (string) ($current['title'] ?? ''),
                            (string) ($current['content'] ?? ''),
                            (string) ($current['excerpt'] ?? ''),
                            $summaryBudget,
                            $message,
                            $editorialContext
                        ),
                    ],
                    'chips' => ['seo_generate', 'block_improve'],
                ];

            case 'seo_generate':
                return [
                    'intent' => $intent,
                    'proposal_type' => 'seo_block',
                    'proposal' => [
                        'values' => $this->editorial->generateSeo(
                            $entityType,
                            $locale,
                            (string) ($current['title'] ?? ''),
                            (string) ($current['content'] ?? ''),
                            (string) ($current['excerpt'] ?? ''),
                            $message,
                            $editorialContext
                        ),
                    ],
                    'chips' => ['seo_generate'],
                ];
        }

        throw new RuntimeException('missing_message');
    }

    /**
     * @param array<string, mixed> $context
     * @return array{module:string,entity:string,entity_id:string,source_id:string,scope:string,block:string,field:string,label:string,field_kind:string,locale:string,source_locale:string,current:array<string,mixed>,source:array<string,mixed>,has_excerpt:bool,selected_category_ids:array<int,string>,selected_categories:array<int,string>,available_categories:array<int,string>}
     */
    private function normalizeContext(array $context): array
    {
        $current = is_array($context['current'] ?? null) ? $context['current'] : [];
        $source = is_array($context['source'] ?? null) ? $context['source'] : $current;
        $field = strtolower(trim((string) ($context['field'] ?? '')));
        $scope = strtolower(trim((string) ($context['scope'] ?? '')));
        if ($scope !== 'block') {
            $scope = $field !== '' ? 'field' : 'block';
        }

        return [
            'module' => strtolower(trim((string) ($context['module'] ?? 'pages'))),
            'entity' => strtolower(trim((string) ($context['entity'] ?? 'page'))),
            'entity_id' => trim((string) ($context['entity_id'] ?? '')),
            'source_id' => trim((string) ($context['source_id'] ?? '')),
            'scope' => $scope,
            'block' => strtolower(trim((string) ($context['block'] ?? 'content'))),
            'field' => $field,
            'label' => trim((string) ($context['label'] ?? '')),
            'field_kind' => strtolower(trim((string) ($context['field_kind'] ?? 'text'))),
            'locale' => trim((string) ($context['locale'] ?? 'fr-FR')),
            'source_locale' => trim((string) ($context['source_locale'] ?? ($context['locale'] ?? 'fr-FR'))),
            'current' => $this->normalizeFieldMap($current),
            'source' => $this->normalizeFieldMap($source),
            'has_excerpt' => !empty($context['has_excerpt']),
            'selected_category_ids' => $this->normalizeStringList($context['selected_category_ids'] ?? []),
            'selected_categories' => $this->normalizeStringList($context['selected_categories'] ?? []),
            'available_categories' => $this->normalizeStringList($context['available_categories'] ?? []),
        ];
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function normalizeFieldMap(array $values): array
    {
        return [
            'title' => trim((string) ($values['title'] ?? '')),
            'slug' => trim((string) ($values['slug'] ?? '')),
            'excerpt' => trim((string) ($values['excerpt'] ?? '')),
            'content' => trim((string) ($values['content'] ?? '')),
            'categories' => $this->normalizeStringList($values['categories'] ?? []),
            'featured_image' => trim((string) ($values['featured_image'] ?? '')),
            'meta_title' => trim((string) ($values['meta_title'] ?? '')),
            'meta_description' => trim((string) ($values['meta_description'] ?? '')),
        ];
    }

    /**
     * @param mixed $values
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $values): array
    {
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

        return $normalized;
    }

    /**
     * @param array<string, mixed> $ctx
     */
    private function resolveIntent(array $ctx, string $message, string $action): string
    {
        $explicit = trim($action);
        if ($explicit !== '') {
            return $explicit;
        }

        $normalized = mb_strtolower(trim($message));
        if ($normalized === '') {
            throw new RuntimeException('missing_message');
        }

        if ($ctx['block'] === 'seo') {
            return 'seo_generate';
        }

        $referencesWholeBlock = preg_match('/\b(page|article|post|bloc|block|section|contenu|content|texte)\b/u', $normalized) === 1;
        $referencesWholeGeneration = preg_match('/\b(complet|compl[eè]te|entier|enti[eè]re|complete|full|whole|entire)\b/u', $normalized) === 1;

        if (preg_match('/trad|übersetz|translate|tradu|traduc|traduz/u', $normalized) === 1) {
            if ($ctx['scope'] === 'field' && $ctx['field_kind'] !== 'richtext' && !$referencesWholeBlock) {
                return 'field_translate';
            }
            return 'block_translate';
        }
        if (
            $ctx['field'] === 'featured_image'
            || preg_match('/\b(image|images|photo|photos|visuel|visuels|illustration|illustrations|cover|thumbnail|hero)\b/u', $normalized) === 1
        ) {
            if ($ctx['scope'] === 'field' && $ctx['field'] === 'featured_image') {
                return 'field_fill';
            }
            if ($ctx['block'] === 'content') {
                return 'block_improve';
            }
        }
        if (preg_match('/résum|resum|summary|riass|zusammen/u', $normalized) === 1) {
            return 'block_summary';
        }
        if (preg_match('/orth|spell|gramm|corrig|proof/u', $normalized) === 1) {
            return 'block_proofread';
        }
        if (preg_match('/champ|field|titre|meta|slug/u', $normalized) === 1 && $ctx['scope'] === 'field') {
            if (preg_match('/amélior|improv|rewrite|reform|optim/u', $normalized) === 1) {
                return 'field_improve';
            }
            return 'field_fill';
        }
        if (preg_match('/génér|generat|create|write|rédig|compose|rempli/u', $normalized) === 1) {
            if (($ctx['field_kind'] === 'richtext' || $referencesWholeBlock || $referencesWholeGeneration) && $ctx['block'] === 'content') {
                return 'block_generate';
            }
            if ($ctx['scope'] === 'field' && $ctx['field_kind'] !== 'richtext') {
                return 'field_fill';
            }
            return 'block_generate';
        }
        if ($ctx['scope'] === 'field' && $ctx['field_kind'] !== 'richtext') {
            return 'field_improve';
        }

        return 'block_improve';
    }

    /**
     * @param array<string, mixed> $ctx
     * @return array<int, string>
     */
    private function generateFieldVariants(array $ctx, string $message, string $intent, array $editorialContext): array
    {
        $fieldKey = $ctx['field'];
        $fieldLabel = $ctx['label'] !== '' ? $ctx['label'] : $fieldKey;
        $currentValue = (string) ($ctx['current'][$fieldKey] ?? '');
        $sourceFields = $intent === 'field_translate' ? $this->resolveSourceFields($ctx) : $ctx['source'];
        $sourceValue = (string) ($sourceFields[$fieldKey] ?? '');
        $modeInstruction = match ($intent) {
            'field_translate' => 'Translate the field into the requested locale and return three polished variants.',
            'field_improve' => 'Improve the field and return three polished variants with different tones or lengths.',
            default => 'Fill the field and return three useful variants.',
        };

        $fieldSpecific = '';
        if ($fieldKey === 'slug') {
            $fieldSpecific = 'Each variant must be a lowercase URL slug using only letters, numbers, and hyphens.';
        } elseif ($fieldKey === 'meta_title') {
            $fieldSpecific = 'Keep each variant under 60 characters.';
        } elseif ($fieldKey === 'meta_description') {
            $fieldSpecific = 'Keep each variant under 160 characters.';
        } elseif ($fieldKey === 'featured_image') {
            $fieldSpecific = 'Each variant must be one exact image path from editorial_context.available_images. Never invent, rewrite, or describe image paths.';
        }

        $payload = $this->requestJson(
            instructions: FlattyPersona::promptPreamble() . ' Return only valid JSON with a variants array of exactly 3 strings. ' . $modeInstruction . ' ' . $fieldSpecific . ' Give three genuinely distinct and useful options whenever possible. No commentary, no markdown, no code fences.',
            input: [
                'task' => 'field_variants',
                'locale' => $ctx['locale'],
                'source_locale' => $ctx['source_locale'],
                'field_key' => $fieldKey,
                'field_label' => $fieldLabel,
                'current_value' => $currentValue,
                'source_value' => $intent === 'field_translate' && $currentValue === '' ? $sourceValue : '',
                'entity_type' => $ctx['entity'],
                'context' => $this->buildEntityContext($ctx['current']),
                'editorial_context' => $editorialContext,
                'user_instruction' => trim($message),
            ],
            maxOutputTokens: 500
        );

        $variants = $payload['variants'] ?? [];
        if (!is_array($variants)) {
            $variants = [];
        }

        $normalized = [];
        foreach ($variants as $variant) {
            $value = trim((string) $variant);
            if ($value === '') {
                continue;
            }
            if ($fieldKey === 'slug') {
                $normalized[] = str_slug($value);
                continue;
            }

            if ($fieldKey === 'featured_image') {
                $allowedPaths = array_map(static fn(array $item): string => trim((string) ($item['path'] ?? '')), is_array($editorialContext['available_images'] ?? null) ? $editorialContext['available_images'] : []);
                if (!in_array($value, $allowedPaths, true)) {
                    continue;
                }
            }

            $normalized[] = $value;
        }

        if ($normalized === []) {
            throw new RuntimeException('missing_content');
        }

        return array_values(array_slice(array_unique($normalized), 0, 3));
    }

    /**
     * @param array<string, mixed> $current
     * @return array<string, mixed>
     */
    private function buildEntityContext(array $current): array
    {
        return [
            'title' => (string) ($current['title'] ?? ''),
            'excerpt' => (string) ($current['excerpt'] ?? ''),
            'content' => $this->limitText((string) ($current['content'] ?? ''), 5000),
            'categories' => $this->normalizeStringList($current['categories'] ?? []),
            'featured_image' => (string) ($current['featured_image'] ?? ''),
            'meta_title' => (string) ($current['meta_title'] ?? ''),
            'meta_description' => (string) ($current['meta_description'] ?? ''),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function fieldFollowUpChips(array $ctx, string $intent): array
    {
        if ($intent === 'field_translate') {
            return ['field_improve', 'field_fill'];
        }

        if (($ctx['field'] ?? '') === 'excerpt') {
            return ['field_improve', 'field_fill'];
        }

        if (($ctx['field'] ?? '') === 'title') {
            return ['field_improve', 'field_fill'];
        }

        if (($ctx['field'] ?? '') === 'meta_title' || ($ctx['field'] ?? '') === 'meta_description') {
            return ['field_improve', 'seo_generate'];
        }

        return ['field_improve', 'field_fill'];
    }

    /**
     * @return array<int, string>
     */
    private function contentFollowUpChips(string $intent, array $ctx): array
    {
        $chips = match ($intent) {
            'block_generate' => ['block_improve', 'block_proofread'],
            'block_improve' => ['block_proofread', 'block_summary'],
            'block_proofread' => ['block_improve', 'block_summary'],
            'block_translate' => ['block_improve', 'block_proofread'],
            default => ['block_improve', 'block_proofread'],
        };

        if (($ctx['locale'] ?? '') !== '' && ($ctx['source_locale'] ?? '') !== '' && ($ctx['locale'] ?? '') !== ($ctx['source_locale'] ?? '')) {
            if (!in_array('block_translate', $chips, true) && count($chips) < 2) {
                $chips[] = 'block_translate';
            }
        }

        return array_values(array_slice(array_unique($chips), 0, 2));
    }

    /**
     * @param array<string, mixed> $ctx
     * @return array<string, mixed>
     */
    private function buildEditorialContext(array $ctx): array
    {
        $settings = FlatFile::settings();
        $context = [
            'site_name' => trim((string) ($settings['site_name'] ?? '')),
            'site_description' => trim((string) ($settings['site_description'] ?? '')),
            'site_slogan' => trim((string) ($settings['site_slogan'] ?? '')),
            'assistant_persona' => FlattyPersona::editorialContext(),
            'selected_categories' => [],
            'available_categories' => [],
        ];

        if (($ctx['module'] ?? '') !== 'posts') {
            return $context;
        }

        $locale = trim((string) ($ctx['locale'] ?? ''));
        $categories = $this->buildLocalizedCategoryMap($locale);
        $selected = $this->normalizeStringList($ctx['selected_categories'] ?? []);
        if ($selected === []) {
            $selected = $this->resolveSelectedCategoriesFromIds(
                $this->normalizeStringList($ctx['selected_category_ids'] ?? []),
                $categories
            );
        }

        $available = $this->normalizeStringList($ctx['available_categories'] ?? []);
        if ($available === []) {
            $available = array_values($categories);
        }

        $context['selected_categories'] = $selected;
        $context['available_categories'] = $available;
        $context['recent_posts'] = $this->buildRecentPostsContext($locale, $categories);
        $context['available_images'] = $this->buildAvailableImagesContext();

        return $context;
    }

    /**
     * @return array<string, string>
     */
    private function buildLocalizedCategoryMap(string $locale): array
    {
        $items = (new CategoryTranslationService())->buildLocalizedCategories('blog', $locale, true);
        $map = [];
        foreach ($items as $item) {
            $id = trim((string) ($item['id'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            if ($id === '' || $name === '') {
                continue;
            }
            $map[$id] = $name;
        }

        return $map;
    }

    /**
     * @param array<int, string> $selectedIds
     * @param array<string, string> $categoriesById
     * @return array<int, string>
     */
    private function resolveSelectedCategoriesFromIds(array $selectedIds, array $categoriesById): array
    {
        $selected = [];
        foreach ($selectedIds as $id) {
            if (!isset($categoriesById[$id])) {
                continue;
            }
            $selected[] = $categoriesById[$id];
        }

        return $this->normalizeStringList($selected);
    }

    /**
     * @param array<string, string> $categoriesById
     * @return array<int, array<string, mixed>>
     */
    private function buildRecentPostsContext(string $locale, array $categoriesById): array
    {
        $translations = new PostTranslationService();
        $posts = [];
        foreach ($translations->all() as $post) {
            $group = trim((string) ($post['translation_group'] ?? $post['id'] ?? ''));
            if ($group === '') {
                continue;
            }

            $localized = $translations->findByTranslationGroupAndLocale($group, $locale, false);
            if (!is_array($localized)) {
                $localized = $translations->resolveSourcePost($group);
            }
            if (!is_array($localized)) {
                $localized = $post;
            }

            $posts[$group] = $localized;
        }

        $items = array_values($posts);
        usort($items, static function (array $a, array $b): int {
            $left = (string) ($a['updated_at'] ?? $a['created_at'] ?? '');
            $right = (string) ($b['updated_at'] ?? $b['created_at'] ?? '');
            return strcmp($right, $left);
        });

        $recent = [];
        foreach ($items as $post) {
            if (($post['status'] ?? 'draft') !== 'published') {
                continue;
            }

            $title = trim((string) ($post['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $categoryNames = [];
            foreach ((array) ($post['categories'] ?? []) as $categoryId) {
                $id = trim((string) $categoryId);
                if ($id === '' || !isset($categoriesById[$id])) {
                    continue;
                }
                $categoryNames[] = $categoriesById[$id];
            }

            $recent[] = [
                'title' => $title,
                'excerpt' => $this->limitText((string) ($post['excerpt'] ?? ''), 240),
                'categories' => $this->normalizeStringList($categoryNames),
            ];

            if (count($recent) >= 4) {
                break;
            }
        }

        return $recent;
    }

    /**
     * @param array<string, mixed> $ctx
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function finalizePostContentValues(array $ctx, array $values): array
    {
        if (($ctx['module'] ?? '') !== 'posts') {
            return $values;
        }

        $categoriesById = $this->buildLocalizedCategoryMap((string) ($ctx['locale'] ?? ''));
        $categoryIds = $this->resolveSuggestedCategoryIds($values['categories'] ?? [], $categoriesById);
        if ($categoryIds === []) {
            $categoryIds = $this->normalizeStringList($ctx['selected_category_ids'] ?? []);
        }

        $values['categories'] = $categoryIds;
        return $values;
    }

    /**
     * @param mixed $suggested
     * @param array<string, string> $categoriesById
     * @return array<int, string>
     */
    private function resolveSuggestedCategoryIds(mixed $suggested, array $categoriesById): array
    {
        $requested = $this->normalizeStringList($suggested);
        if ($requested === []) {
            return [];
        }

        $resolved = [];
        foreach ($requested as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            if (isset($categoriesById[$candidate])) {
                if (!in_array($candidate, $resolved, true)) {
                    $resolved[] = $candidate;
                }
                continue;
            }

            $needle = mb_strtolower($candidate);
            if ($needle === '') {
                continue;
            }

            foreach ($categoriesById as $id => $name) {
                if (mb_strtolower(trim($name)) !== $needle) {
                    continue;
                }

                if (!in_array($id, $resolved, true)) {
                    $resolved[] = $id;
                }
                break;
            }
        }

        return array_slice($resolved, 0, 3);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildAvailableImagesContext(): array
    {
        $media = new MediaModel();
        $images = $media->getImages();
        $usedPaths = $this->collectUsedImagePaths();
        $available = [];

        foreach ($images as $image) {
            $path = $this->normalizeUploadImagePath((string) ($image['url'] ?? ''));
            if ($path === '') {
                $path = $this->normalizeUploadImagePath('/uploads/' . ltrim((string) ($image['path'] ?? ''), '/'));
            }

            if ($path === '' || isset($usedPaths[$path])) {
                continue;
            }

            $available[] = [
                'path' => $path,
                'label' => $this->humanizeImageName((string) ($image['original_name'] ?? $image['name'] ?? basename($path))),
            ];

            if (count($available) >= 12) {
                break;
            }
        }

        return $available;
    }

    /**
     * @return array<string, true>
     */
    private function collectUsedImagePaths(): array
    {
        $used = [];

        foreach (FlatFile::for('core/posts')->all() as $post) {
            $featured = $this->normalizeUploadImagePath((string) ($post['featured_image'] ?? ''));
            if ($featured !== '') {
                $used[$featured] = true;
            }

            foreach ($this->extractImagePathsFromHtml((string) ($post['content'] ?? '')) as $path) {
                $used[$path] = true;
            }
        }

        foreach (FlatFile::for('core/pages')->all() as $page) {
            foreach ($this->extractImagePathsFromHtml((string) ($page['content'] ?? '')) as $path) {
                $used[$path] = true;
            }
        }

        return $used;
    }

    /**
     * @return array<int, string>
     */
    private function extractImagePathsFromHtml(string $html): array
    {
        $matches = [];
        if (trim($html) === '' || preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches) !== 1) {
            return [];
        }

        $paths = [];
        foreach ($matches[1] as $candidate) {
            $path = $this->normalizeUploadImagePath((string) $candidate);
            if ($path === '' || in_array($path, $paths, true)) {
                continue;
            }
            $paths[] = $path;
        }

        return $paths;
    }

    private function normalizeUploadImagePath(string $path): string
    {
        $normalized = function_exists('flatcms_normalize_upload_media_path')
            ? flatcms_normalize_upload_media_path($path)
            : trim($path);

        $normalized = trim(str_replace('\\', '/', (string) $normalized));
        if ($normalized === '' || !str_starts_with($normalized, '/uploads/images/')) {
            return '';
        }

        return $normalized;
    }

    private function humanizeImageName(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $base = preg_replace('/[_-]+/', ' ', $base) ?? $base;
        $base = preg_replace('/\s+/', ' ', $base) ?? $base;
        $base = trim($base);

        return $base !== '' ? $base : $filename;
    }

    /**
     * @param array<string, mixed> $ctx
     * @return array<string, string>
     */
    private function resolveSourceFields(array $ctx): array
    {
        $source = $this->normalizeFieldMap(is_array($ctx['source'] ?? null) ? $ctx['source'] : []);
        if ($source['title'] !== '' || $source['content'] !== '' || $source['excerpt'] !== '' || $source['meta_title'] !== '' || $source['meta_description'] !== '') {
            return $source;
        }

        $sourceId = trim((string) ($ctx['source_id'] ?? ''));
        if ($sourceId === '') {
            return $source;
        }

        if (($ctx['module'] ?? '') === 'posts') {
            $record = (new PostTranslationService())->find($sourceId);
            return is_array($record) ? $this->normalizeFieldMap($record) : $source;
        }

        if (($ctx['module'] ?? '') === 'pages') {
            $record = (new PageTranslationService())->find($sourceId);
            return is_array($record) ? $this->normalizeFieldMap($record) : $source;
        }

        return $source;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function requestJson(string $instructions, array $input, int $maxOutputTokens): array
    {
        $response = $this->ai->respond(new AiRequest(
            input: json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            instructions: $instructions,
            maxOutputTokens: $maxOutputTokens,
            metadata: [
                'flatcms_scope' => 'admin_ai_agent',
            ]
        ));

        if ($response->hasRefusal()) {
            throw new RuntimeException('provider_refusal');
        }

        return $this->decodeJsonObject($this->extractOutputText($response));
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

        throw new RuntimeException('missing_content');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $payload): array
    {
        $trimmed = trim($payload);
        if ($trimmed === '') {
            throw new RuntimeException('missing_content');
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $trimmed, $matches) === 1) {
            $decoded = json_decode((string) $matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('missing_content');
    }

    private function limitText(string $value, int $limit): string
    {
        $plain = trim(strip_tags($value));
        if ($plain === '') {
            return '';
        }

        return mb_strlen($plain) > $limit ? (string) mb_substr($plain, 0, $limit) : $plain;
    }
}
