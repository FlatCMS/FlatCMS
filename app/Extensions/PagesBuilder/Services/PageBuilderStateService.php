<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Services;

use App\Core\FlatFile;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use App\Modules\Pages\Services\PageTranslationService;

final class PageBuilderStateService
{
    private FlatFile $pages;
    private FlatFile $states;
    private PageTranslationService $translations;

    public function __construct(?FlatFile $pages = null, ?FlatFile $states = null, ?PageTranslationService $translations = null)
    {
        $this->pages = $pages ?? FlatFile::for('core/pages');
        $this->states = $states ?? FlatFile::for('extensions/pages-builder/pages');
        $this->translations = $translations ?? new PageTranslationService($this->pages);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPageSummaries(): array
    {
        $rows = [];
        $seenGroups = [];
        foreach ($this->translations->all() as $page) {
            if (!is_array($page)) {
                continue;
            }

            $normalized = $this->translations->normalizePage($page);
            $group = trim((string) ($normalized['translation_group'] ?? $normalized['id'] ?? ''));
            if ($group === '' || isset($seenGroups[$group])) {
                continue;
            }

            $sourcePage = $this->translations->resolveSourcePage($group);
            if (!is_array($sourcePage)) {
                $sourcePage = $normalized;
            }

            $state = $this->stateForPage($sourcePage);
            $rows[] = [
                'id' => (string) ($sourcePage['id'] ?? ''),
                'title' => trim((string) ($sourcePage['title'] ?? '')),
                'slug' => trim((string) ($sourcePage['slug'] ?? '')),
                'status' => (string) ($sourcePage['status'] ?? 'draft'),
                'locale' => (string) ($sourcePage['locale'] ?? ''),
                'translation_group' => (string) ($sourcePage['translation_group'] ?? $sourcePage['id'] ?? ''),
                'author_id' => (string) ($sourcePage['author_id'] ?? ''),
                'system_required' => !empty($sourcePage['system_required']),
                'locale_label' => $this->translations->getLocaleLabel((string) ($sourcePage['locale'] ?? '')),
                'builder_active' => (bool) ($state['active'] ?? false),
                'builder_exists' => !empty($state['exists']),
                'builder_updated_at' => (string) ($state['updated_at'] ?? ''),
                'page_updated_at' => (string) ($sourcePage['updated_at'] ?? ''),
            ];
            $seenGroups[$group] = true;
        }

        usort($rows, static function (array $a, array $b): int {
            $aDate = (string) ($a['page_updated_at'] ?? '');
            $bDate = (string) ($b['page_updated_at'] ?? '');
            if ($aDate !== $bDate) {
                return $bDate <=> $aDate;
            }

            return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPage(string $id): ?array
    {
        $page = $this->pages->find($id);
        return is_array($page) ? $this->translations->normalizePage($page) : null;
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    public function stateForPage(array $page): array
    {
        $pageId = trim((string) ($page['id'] ?? ''));
        $savedState = $pageId !== '' ? $this->states->find($pageId) : null;
        $state = is_array($savedState) ? $savedState : [];
        $builder = $this->normalizeBuilder($state['builder'] ?? null);
        if ($builder === null) {
            $builder = $this->builderFromLegacyState($page, $state);
        }
        if ($this->shouldRefreshGeneratedCanonicalBuilder($builder, $state)) {
            $builder = $this->builderFromCanonicalPage($page);
        }
        if (($builder['sections'] ?? []) === []) {
            $builder = $this->builderFromCanonicalPage($page);
        }
        $builder = $this->applyGeneratedBuilderMigrations($builder);

        $resolvedState = array_merge([
            'exists' => is_array($savedState),
            'id' => $pageId,
            'page_id' => $pageId,
            'translation_group' => (string) ($page['translation_group'] ?? $pageId),
            'page_locale' => (string) ($page['locale'] ?? ''),
            'source_locale' => (string) ($page['source_locale'] ?? $page['locale'] ?? ''),
            'active' => false,
            'layout' => 'builder',
            'builder_version' => (int) (($builder['version'] ?? 2)),
            'builder' => $builder,
            'eyebrow' => '',
            'intro' => '',
            'highlight_title' => '',
            'highlight_body' => '',
            'cta_label' => '',
            'cta_url' => '',
        ], $state);
        $resolvedState['exists'] = is_array($savedState);
        $resolvedState['id'] = $pageId;
        $resolvedState['page_id'] = $pageId;
        $resolvedState['translation_group'] = (string) ($page['translation_group'] ?? $pageId);
        $resolvedState['page_locale'] = (string) ($page['locale'] ?? '');
        $resolvedState['source_locale'] = (string) ($page['source_locale'] ?? $page['locale'] ?? '');
        $resolvedState['builder_version'] = (int) ($builder['version'] ?? 2);
        $resolvedState['builder'] = $builder;

        return $resolvedState;
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>|null
     */
    public function getActiveStateForPage(array $page): ?array
    {
        $state = $this->stateForPage($page);

        return (bool) ($state['active'] ?? false) ? $state : null;
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $user
     * @return array<string, mixed>
     */
    public function saveStateForPage(array $page, array $input, ?array $user = null): array
    {
        $page = $this->translations->normalizePage($page);
        $pageId = (string) ($page['id'] ?? '');
        $existing = $pageId !== '' ? $this->states->find($pageId) : null;
        $existingState = is_array($existing) ? $existing : [];
        $existingBuilder = $this->normalizeBuilder($existingState['builder'] ?? null);
        if ($existingBuilder === null) {
            $existingBuilder = $this->builderFromLegacyState($page, $existingState);
        }
        if (($existingBuilder['sections'] ?? []) === []) {
            $existingBuilder = $this->builderFromCanonicalPage($page);
        }
        $inputBuilder = $this->normalizeBuilder($input['builder'] ?? null);
        $builder = $inputBuilder ?? $existingBuilder ?? $this->emptyBuilder();

        $payload = [
            'id' => $pageId,
            'page_id' => $pageId,
            'translation_group' => (string) ($page['translation_group'] ?? $pageId),
            'page_locale' => (string) ($page['locale'] ?? ''),
            'source_locale' => (string) ($page['source_locale'] ?? $page['locale'] ?? ''),
            'page_title' => (string) ($page['title'] ?? ''),
            'page_slug' => (string) ($page['slug'] ?? ''),
            'layout' => 'builder',
            'active' => !empty($input['active']),
            'builder_version' => (int) ($builder['version'] ?? 2),
            'builder' => $builder,
            'eyebrow' => $this->sanitizeText($input['eyebrow'] ?? '', 120),
            'intro' => $this->sanitizeText($input['intro'] ?? '', 360),
            'highlight_title' => $this->sanitizeText($input['highlight_title'] ?? '', 160),
            'highlight_body' => $this->sanitizeText($input['highlight_body'] ?? '', 400),
            'cta_label' => $this->sanitizeText($input['cta_label'] ?? '', 80),
            'cta_url' => $this->sanitizeUrl($input['cta_url'] ?? ''),
            'updated_by' => (string) ($user['id'] ?? ''),
        ];

        if (is_array($existing)) {
            $saved = $this->states->update($pageId, $payload);
            return is_array($saved) ? $saved : $payload;
        }

        return $this->states->create($payload);
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    public function buildInitialBuilderForPage(array $page): array
    {
        $page = $this->translations->normalizePage($page);
        $builder = $this->builderFromLegacyState($page, []);
        if (($builder['sections'] ?? []) !== []) {
            return $builder;
        }

        return $this->builderFromCanonicalPage($page);
    }

    /**
     * @param array<string, mixed> $page
     */
    public function frontendPathForPage(array $page): string
    {
        $locale = trim((string) ($page['locale'] ?? ''));
        $slug = trim((string) ($page['slug'] ?? ''));
        if ($slug === '') {
            return '/';
        }

        return $locale !== '' ? '/' . $locale . '/page/' . $slug : '/page/' . $slug;
    }

    private function sanitizeText(mixed $value, int $max): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $max, 'UTF-8');
        }

        return substr($text, 0, $max);
    }

    private function sanitizeUrl(mixed $value): string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return '';
        }

        if (
            str_starts_with($url, '/')
            || str_starts_with($url, '#')
            || preg_match('~^https?://~i', $url) === 1
        ) {
            return $url;
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyBuilder(): array
    {
        return [
            'version' => 2,
            'sections' => [],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeBuilder(mixed $builder): ?array
    {
        if (!is_array($builder)) {
            return null;
        }

        $version = (int) ($builder['version'] ?? 2);
        $sections = $builder['sections'] ?? [];
        if (!is_array($sections)) {
            $sections = [];
        }

        return [
            'version' => max(2, $version),
            'sections' => array_values($sections),
        ];
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function builderFromLegacyState(array $page, array $state): array
    {
        $title = trim((string) ($page['title'] ?? ''));
        $subtitle = trim((string) ($state['intro'] ?? ''));
        $primaryLabel = trim((string) ($state['cta_label'] ?? ''));
        $primaryUrl = trim((string) ($state['cta_url'] ?? ''));
        $hasLegacyContent = $subtitle !== '' || ($primaryLabel !== '' && $primaryUrl !== '');

        if (!$hasLegacyContent) {
            return $this->emptyBuilder();
        }

        return [
            'version' => 2,
            'sections' => [
                [
                    'id' => 'sec_legacy',
                    'layoutTemplate' => 'minmax(0,1fr)',
                    'settings' => [],
                    'columns' => [
                        [
                            'id' => 'col_legacy',
                            'blocks' => [
                                [
                                    'id' => 'pb_legacy_hero',
                                    'type' => 'hero',
                                    'settings' => [
                                        'title' => $title,
                                        'headingTag' => 'h1',
                                        'subtitle' => $subtitle,
                                        'showPrimaryCta' => $primaryLabel !== '' && $primaryUrl !== '' ? 'on' : 'off',
                                        'primaryLabel' => $primaryLabel,
                                        'primaryUrl' => $primaryUrl,
                                        'primaryTarget' => '_self',
                                        'showSecondaryCta' => 'off',
                                        'secondaryLabel' => '',
                                        'secondaryUrl' => '',
                                        'secondaryTarget' => '_self',
                                        'backgroundImage' => '',
                                        'mediaFit' => 'cover',
                                        'align' => 'left',
                                        'variant' => 'soft',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private function builderFromCanonicalPage(array $page): array
    {
        $content = trim((string) ($page['content'] ?? ''));
        if ($content === '') {
            return $this->emptyBuilder();
        }

        $sections = [];
        $hero = $this->extractCanonicalHero($page, $content);
        if (is_array($hero)) {
            $sections[] = $this->wrapBlockInSection('canonical_hero', [
                'id' => 'pb_canonical_hero',
                'type' => 'hero',
                'settings' => [
                    'title' => (string) ($hero['title'] ?? ''),
                    'headingTag' => 'h1',
                    'subtitle' => (string) ($hero['subtitle'] ?? ''),
                    'showPrimaryCta' => !empty($hero['primaryLabel']) && !empty($hero['primaryUrl']) ? 'on' : 'off',
                    'primaryLabel' => (string) ($hero['primaryLabel'] ?? ''),
                    'primaryUrl' => (string) ($hero['primaryUrl'] ?? ''),
                    'primaryTarget' => '_self',
                    'showSecondaryCta' => !empty($hero['secondaryLabel']) && !empty($hero['secondaryUrl']) ? 'on' : 'off',
                    'secondaryLabel' => (string) ($hero['secondaryLabel'] ?? ''),
                    'secondaryUrl' => (string) ($hero['secondaryUrl'] ?? ''),
                    'secondaryTarget' => '_self',
                    'backgroundImage' => (string) ($hero['backgroundImage'] ?? ''),
                    'mediaFit' => 'cover',
                    'align' => 'left',
                    'variant' => !empty($hero['backgroundImage']) ? 'soft' : 'default',
                ],
            ]);
            $content = trim((string) ($hero['remaining_html'] ?? ''));
        }

        $contentSections = $this->splitCanonicalContentSections($content);
        if ($contentSections === [] && $content !== '') {
            $contentSections = [$content];
        }

        foreach ($contentSections as $index => $sectionHtml) {
            foreach ($this->buildCanonicalBlocksFromSection((string) $sectionHtml, $index + 1) as $blockIndex => $block) {
                $suffix = ($index + 1) . '_' . ($blockIndex + 1);
                $sections[] = $this->wrapBlockInSection('canonical_' . $suffix, $block);
            }
        }

        if ($sections === []) {
            return $this->emptyBuilder();
        }

        return [
            'version' => 2,
            'sections' => $sections,
        ];
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>|null
     */
    private function extractCanonicalHero(array $page, string $content): ?array
    {
        if (!class_exists(DOMDocument::class)) {
            return null;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body><div id="pb-canonical-root">' . $content . '</div></body></html>';
        $internalErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if (!$loaded) {
            return null;
        }

        $xpath = new DOMXPath($dom);
        $root = $xpath->query("//*[@id='pb-canonical-root']")->item(0);
        if (!$root instanceof DOMElement) {
            return null;
        }

        $title = trim((string) ($page['title'] ?? ''));
        $subtitle = '';
        $backgroundImage = '';
        $primaryLabel = '';
        $primaryUrl = '';
        $secondaryLabel = '';
        $secondaryUrl = '';

        $heroParagraph = $this->findFirstTextParagraph($xpath, $root);
        if ($heroParagraph instanceof DOMElement) {
            $subtitle = trim((string) $heroParagraph->textContent);
            if ($subtitle !== '') {
                $heroParagraph->parentNode?->removeChild($heroParagraph);
            }
        }

        $imageNode = $this->findFirstImageNode($xpath, $root);
        if ($imageNode instanceof DOMElement) {
            $backgroundImage = trim((string) $imageNode->getAttribute('src'));
            if ($backgroundImage !== '') {
                $imageWrapper = $imageNode->parentNode instanceof DOMElement ? $imageNode->parentNode : null;
                if ($imageWrapper instanceof DOMElement && strtolower($imageWrapper->tagName) === 'p' && trim((string) $imageWrapper->textContent) === '') {
                    $imageWrapper->parentNode?->removeChild($imageWrapper);
                } else {
                    $imageNode->parentNode?->removeChild($imageNode);
                }
            }
        }

        $ctaWrapper = $this->findHeroCtaWrapper($xpath, $root);
        if ($ctaWrapper instanceof DOMElement) {
            $links = [];
            foreach ($ctaWrapper->getElementsByTagName('a') as $anchor) {
                if (!$anchor instanceof DOMElement) {
                    continue;
                }

                $label = trim((string) $anchor->textContent);
                $url = trim((string) $anchor->getAttribute('href'));
                if ($label === '' || $url === '') {
                    continue;
                }
                $links[] = ['label' => $label, 'url' => $url];
            }

            if (count($links) > 0 && count($links) <= 2) {
                $primaryLabel = (string) ($links[0]['label'] ?? '');
                $primaryUrl = (string) ($links[0]['url'] ?? '');
                $secondaryLabel = (string) ($links[1]['label'] ?? '');
                $secondaryUrl = (string) ($links[1]['url'] ?? '');
                $ctaWrapper->parentNode?->removeChild($ctaWrapper);
            }
        }

        if ($subtitle === '' && $backgroundImage === '' && $primaryLabel === '' && $secondaryLabel === '') {
            return null;
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'backgroundImage' => $backgroundImage,
            'primaryLabel' => $primaryLabel,
            'primaryUrl' => $primaryUrl,
            'secondaryLabel' => $secondaryLabel,
            'secondaryUrl' => $secondaryUrl,
            'remaining_html' => $this->innerHtml($root),
        ];
    }

    private function findFirstTextParagraph(DOMXPath $xpath, DOMElement $root): ?DOMElement
    {
        foreach ($xpath->query('.//p', $root) ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            if (str_contains(' ' . $node->getAttribute('class') . ' ', ' prose-action-links ')) {
                continue;
            }

            $text = trim((string) $node->textContent);
            if ($text !== '') {
                return $node;
            }
        }

        return null;
    }

    private function findFirstImageNode(DOMXPath $xpath, DOMElement $root): ?DOMElement
    {
        foreach ($xpath->query('.//img', $root) ?: [] as $node) {
            if ($node instanceof DOMElement) {
                return $node;
            }
        }

        return null;
    }

    private function findHeroCtaWrapper(DOMXPath $xpath, DOMElement $root): ?DOMElement
    {
        foreach ($xpath->query('.//p[contains(concat(" ", normalize-space(@class), " "), " prose-action-links ")]', $root) ?: [] as $node) {
            if ($node instanceof DOMElement) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function splitCanonicalContentSections(string $content): array
    {
        if ($content === '') {
            return [];
        }

        $chunks = preg_split('/(?=<h2\b)/i', $content);
        if (!is_array($chunks)) {
            return [trim($content)];
        }

        $sections = [];
        foreach ($chunks as $chunk) {
            $chunk = trim((string) $chunk);
            if ($chunk === '') {
                continue;
            }

            if (!preg_match('/^<h2\b/i', $chunk) && $sections !== []) {
                $sections[array_key_last($sections)] .= "\n" . $chunk;
                continue;
            }

            $sections[] = $chunk;
        }

        return $sections !== [] ? $sections : [trim($content)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildCanonicalBlocksFromSection(string $sectionHtml, int $position): array
    {
        $html = trim($sectionHtml);
        if ($html === '') {
            return [];
        }

        $standaloneContactSettings = $this->extractStandaloneContactWidgetSettings($html);
        if ($standaloneContactSettings !== null) {
            return [[
                'id' => 'pb_canonical_contact_' . $position,
                'type' => 'contact',
                'settings' => $standaloneContactSettings,
            ]];
        }

        $blocks = [];
        if (preg_match('/^\s*<(h[2-4])\b[^>]*>(.*?)<\/\1>/is', $html, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $tag = strtolower((string) ($matches[1][0] ?? 'h2'));
            $headingHtml = (string) ($matches[2][0] ?? '');
            $headingText = trim(html_entity_decode(strip_tags($headingHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $fullMatch = (string) ($matches[0][0] ?? '');
            $fullOffset = (int) ($matches[0][1] ?? 0);
            $rest = trim(substr($html, $fullOffset + strlen($fullMatch)));

            if ($headingText !== '') {
                $blocks[] = [
                    'id' => 'pb_canonical_heading_' . $position,
                    'type' => 'heading',
                    'settings' => [
                        'text' => $headingText,
                        'tag' => in_array($tag, ['h2', 'h3', 'h4'], true) ? $tag : 'h2',
                    ],
                ];
            }

            if ($rest !== '') {
                $restContactSettings = $this->extractStandaloneContactWidgetSettings($rest);
                if ($restContactSettings !== null) {
                    $blocks[] = [
                        'id' => 'pb_canonical_contact_' . $position,
                        'type' => 'contact',
                        'settings' => $restContactSettings,
                    ];

                    return $blocks;
                }

                $blocks[] = [
                    'id' => 'pb_canonical_text_' . $position,
                    'type' => 'text',
                    'settings' => [
                        'text' => $rest,
                    ],
                ];
            }

            return $blocks;
        }

        return [[
            'id' => 'pb_canonical_text_' . $position,
            'type' => 'text',
            'settings' => [
                'text' => $html,
            ],
        ]];
    }

    /**
     * @param array<string, mixed> $builder
     * @param array<string, mixed> $state
     */
    private function shouldRefreshGeneratedCanonicalBuilder(array $builder, array $state): bool
    {
        if (!empty($state['active'])) {
            return false;
        }

        $sections = $builder['sections'] ?? [];
        if (!is_array($sections) || $sections === []) {
            return false;
        }

        foreach ($sections as $section) {
            if (!is_array($section)) {
                return false;
            }
            $columns = $section['columns'] ?? [];
            if (!is_array($columns) || $columns === []) {
                return false;
            }

            foreach ($columns as $column) {
                if (!is_array($column)) {
                    return false;
                }
                $blocks = $column['blocks'] ?? [];
                if (!is_array($blocks) || $blocks === []) {
                    return false;
                }

                foreach ($blocks as $block) {
                    if (!is_array($block)) {
                        return false;
                    }
                    $id = trim((string) ($block['id'] ?? ''));
                    if (!str_starts_with($id, 'pb_canonical_')) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $builder
     * @return array<string, mixed>
     */
    private function applyGeneratedBuilderMigrations(array $builder): array
    {
        $sections = $builder['sections'] ?? [];
        if (!is_array($sections) || $sections === []) {
            return $builder;
        }

        foreach ($sections as $sectionIndex => $section) {
            if (!is_array($section)) {
                continue;
            }

            $columns = $section['columns'] ?? [];
            if (!is_array($columns) || $columns === []) {
                continue;
            }

            foreach ($columns as $columnIndex => $column) {
                if (!is_array($column)) {
                    continue;
                }

                $blocks = $column['blocks'] ?? [];
                if (!is_array($blocks) || $blocks === []) {
                    continue;
                }

                foreach ($blocks as $blockIndex => $block) {
                    if (!is_array($block)) {
                        continue;
                    }

                    $id = trim((string) ($block['id'] ?? ''));
                    $type = strtolower(trim((string) ($block['type'] ?? '')));

                    if (in_array($id, ['pb_canonical_hero', 'pb_legacy_hero'], true) && $type === 'hero') {
                        $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];
                        $headingTag = strtolower(trim((string) ($settings['headingTag'] ?? '')));
                        if ($headingTag !== '') {
                            continue;
                        }

                        $settings['headingTag'] = 'h1';
                        $builder['sections'][$sectionIndex]['columns'][$columnIndex]['blocks'][$blockIndex]['settings'] = $settings;
                        continue;
                    }

                    if (!str_starts_with($id, 'pb_canonical_text_') || $type !== 'text') {
                        continue;
                    }

                    $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];
                    $text = (string) ($settings['text'] ?? '');
                    $contactSettings = $this->extractStandaloneContactWidgetSettings($text);
                    if ($contactSettings === null) {
                        continue;
                    }

                    $builder['sections'][$sectionIndex]['columns'][$columnIndex]['blocks'][$blockIndex] = [
                        'id' => preg_replace('/^pb_canonical_text_/', 'pb_canonical_contact_', $id) ?: $id,
                        'type' => 'contact',
                        'settings' => $contactSettings,
                    ];
                }
            }
        }

        return $builder;
    }

    /**
     * @param array<string, mixed> $block
     * @return array<string, mixed>
     */
    private function wrapBlockInSection(string $id, array $block): array
    {
        return [
            'id' => 'sec_' . $id,
            'layoutTemplate' => 'minmax(0,1fr)',
            'settings' => [],
            'columns' => [
                [
                    'id' => 'col_' . $id,
                    'blocks' => [$block],
                ],
            ],
        ];
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            if (!$child instanceof DOMNode) {
                continue;
            }
            $html .= $element->ownerDocument?->saveHTML($child) ?? '';
        }

        return trim($html);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractStandaloneContactWidgetSettings(string $html): ?array
    {
        $plain = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($plain === '') {
            return null;
        }

        if (!preg_match('/^\[contact-form\b([^\]]*)\]$/i', $plain, $matches)) {
            return null;
        }

        $attributes = (string) ($matches[1] ?? '');
        $slug = '';
        if (preg_match('/\bslug\s*=\s*(["\'])(.*?)\1/i', $attributes, $slugMatch) === 1) {
            $slug = trim((string) ($slugMatch[2] ?? ''));
        }

        if ($slug === '' && preg_match('/\bslug\s*=\s*([^\s"\']+)/i', $attributes, $slugMatch) === 1) {
            $slug = trim((string) ($slugMatch[1] ?? ''));
        }

        if ($slug === '') {
            $slug = 'contact-main';
        }

        return [
            'title' => '',
            'formSlug' => $slug,
            'align' => 'left',
            'variant' => 'subtle',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 1,
            'designBorderColor' => '',
            'designRadius' => 20,
            'designShadow' => 'inherit',
        ];
    }
}
