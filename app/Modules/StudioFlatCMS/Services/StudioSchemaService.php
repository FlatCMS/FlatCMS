<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\StudioFlatCMS\Services;

final class StudioSchemaService
{
    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function defaultDocument(string $documentId = 'home', array $settings = [], array $sourcePage = []): array
    {
        $brandText = trim((string) ($settings['site_name'] ?? ''));
        if ($brandText === '') {
            $brandText = __('app_name', 'Core');
        }

        $defaultSource = $this->defaultSource($documentId, $sourcePage);
        $pageTitle = trim((string) ($defaultSource['title'] ?? ''));
        $heroTitle = $pageTitle !== '' ? $pageTitle : __('studio_flatcms_default_heading', 'StudioFlatCMS');
        $heroBody = $this->defaultBodyContent($sourcePage);
        $documentTitle = $pageTitle !== '' ? $pageTitle : __('studio_flatcms_document_title', 'StudioFlatCMS');

        return [
            'version' => 1,
            'id' => $this->sanitizeDocumentId($documentId),
            'title' => $documentTitle,
            'mode' => 'compose',
            'viewport' => 'desktop',
            'zoom' => 100,
            'source' => $defaultSource,
            'regions' => [
                $this->region('header', true, [
                    $this->stack('header-stack', __('studio_flatcms_region_header', 'StudioFlatCMS'), 'horizontal', [
                        $this->logo('brand-logo', $brandText),
                        $this->menu('brand-menu', [
                            ['label' => __('home', 'Core'), 'url' => '/'],
                            ['label' => __('pages', 'Core'), 'url' => '/page'],
                            ['label' => __('contact', 'Core'), 'url' => '/contact'],
                        ]),
                    ]),
                ]),
                $this->region('main', true, $this->buildMainRegionChildren($heroTitle, $heroBody, $sourcePage)),
                $this->region('aside', false, [
                    $this->section('aside-section', __('studio_flatcms_region_aside', 'StudioFlatCMS'), 'none', [
                        $this->text('aside-note', __('studio_flatcms_default_aside', 'StudioFlatCMS')),
                    ]),
                ]),
                $this->region('footer', true, [
                    $this->stack('footer-stack', __('studio_flatcms_region_footer', 'StudioFlatCMS'), 'horizontal', [
                        $this->text('footer-brand', $brandText),
                        $this->text('footer-links', __('studio_flatcms_default_footer_links', 'StudioFlatCMS')),
                        $this->text('footer-legal', __('studio_flatcms_default_footer_legal', 'StudioFlatCMS')),
                    ]),
                ]),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public function normalizeDocument(array $payload, string $documentId = 'home', array $settings = [], array $sourcePage = []): array
    {
        $default = $this->defaultDocument($documentId, $settings, $sourcePage);
        $regions = is_array($payload['regions'] ?? null) ? $payload['regions'] : [];

        $normalized = [
            'version' => 1,
            'id' => $this->sanitizeDocumentId((string) ($payload['id'] ?? $default['id'])),
            'title' => $this->sanitizeText((string) ($payload['title'] ?? $default['title']), 180, (string) $default['title']),
            'mode' => $this->sanitizeEnum((string) ($payload['mode'] ?? $default['mode']), ['compose', 'theme'], (string) $default['mode']),
            'viewport' => $this->sanitizeEnum((string) ($payload['viewport'] ?? $default['viewport']), ['desktop', 'tablet', 'mobile'], (string) $default['viewport']),
            'zoom' => $this->sanitizeZoom($payload['zoom'] ?? $default['zoom']),
            'source' => $this->normalizeSource(
                is_array($payload['source'] ?? null) ? $payload['source'] : [],
                is_array($default['source'] ?? null) ? $default['source'] : $this->defaultSource($documentId, $sourcePage)
            ),
            'regions' => [],
        ];

        $defaultRegions = is_array($default['regions']) ? $default['regions'] : [];
        foreach ($defaultRegions as $index => $regionDefault) {
            $candidate = $regions[$index] ?? null;
            if (!is_array($candidate)) {
                $candidate = [];
            }
            $normalized['regions'][] = $this->normalizeRegion($candidate, $regionDefault);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $sourcePage
     * @return array<string, string>
     */
    private function defaultSource(string $documentId, array $sourcePage = []): array
    {
        return [
            'entity_id' => trim((string) ($sourcePage['id'] ?? $documentId)),
            'title' => trim((string) ($sourcePage['title'] ?? '')),
            'slug' => trim((string) ($sourcePage['slug'] ?? '')),
            'locale' => trim((string) ($sourcePage['locale'] ?? '')),
            'frontend_path' => trim((string) ($sourcePage['frontend_path'] ?? '')),
            'import_version' => $this->sourceImportVersion(),
            'content_hash' => $this->sourceContentHash($sourcePage),
        ];
    }

    /**
     * @param array<string, mixed> $source
     * @param array<string, string> $default
     * @return array<string, string>
     */
    private function normalizeSource(array $source, array $default): array
    {
        return [
            'entity_id' => $this->sanitizeText((string) ($source['entity_id'] ?? $default['entity_id'] ?? ''), 180, (string) ($default['entity_id'] ?? '')),
            'title' => $this->sanitizeText((string) ($source['title'] ?? $default['title'] ?? ''), 180, (string) ($default['title'] ?? '')),
            'slug' => $this->sanitizeText((string) ($source['slug'] ?? $default['slug'] ?? ''), 180, (string) ($default['slug'] ?? '')),
            'locale' => $this->sanitizeText((string) ($source['locale'] ?? $default['locale'] ?? ''), 24, (string) ($default['locale'] ?? '')),
            'frontend_path' => $this->sanitizeUrl((string) ($source['frontend_path'] ?? $default['frontend_path'] ?? '')),
            'import_version' => $this->sanitizeText((string) ($source['import_version'] ?? $default['import_version'] ?? ''), 48, (string) ($default['import_version'] ?? '')),
            'content_hash' => $this->sanitizeText((string) ($source['content_hash'] ?? $default['content_hash'] ?? ''), 64, (string) ($default['content_hash'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $sourcePage
     */
    private function defaultBodyContent(array $sourcePage): string
    {
        $content = $this->sanitizeRichText((string) ($sourcePage['content'] ?? ''), 12000, '');
        if ($content !== '') {
            return $content;
        }

        return __('studio_flatcms_default_body', 'StudioFlatCMS');
    }

    /**
     * @param array<string, mixed> $sourcePage
     * @return array<int, array<string, mixed>>
     */
    private function buildMainRegionChildren(string $heroTitle, string $heroBody, array $sourcePage): array
    {
        $importedSection = $this->buildImportedHeroSection($sourcePage, $heroTitle);
        if ($importedSection !== null) {
            return [$importedSection];
        }

        return [$this->defaultHeroSection($heroTitle, $heroBody)];
    }

    /**
     * @param array<string, mixed> $sourcePage
     * @return array<string, mixed>|null
     */
    private function buildImportedHeroSection(array $sourcePage, string $heroTitle): ?array
    {
        if (!is_array($sourcePage) || $sourcePage === []) {
            return null;
        }

        $children = [];
        if ($heroTitle !== '') {
            $children[] = $this->text('hero-title', '<h1>' . $this->escapeHtml($heroTitle) . '</h1>');
        }

        $children = array_merge($children, $this->importedContentNodes($sourcePage));
        if ($children === []) {
            return null;
        }

        return $this->section('hero-section', __('studio_flatcms_default_hero_label', 'StudioFlatCMS'), 'soft', [
            $this->stack('hero-copy', __('studio_flatcms_default_copy_label', 'StudioFlatCMS'), 'vertical', $children),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultHeroSection(string $heroTitle, string $heroBody): array
    {
        return $this->section('hero-section', __('studio_flatcms_default_hero_label', 'StudioFlatCMS'), 'soft', [
            $this->stack('hero-copy', __('studio_flatcms_default_copy_label', 'StudioFlatCMS'), 'vertical', [
                $this->text('hero-title', $heroTitle),
                $this->text('hero-body', $heroBody),
                $this->stack('hero-actions', __('studio_flatcms_default_actions_label', 'StudioFlatCMS'), 'horizontal', [
                    $this->button('hero-primary', __('studio_flatcms_default_primary_cta', 'StudioFlatCMS'), '/contact', 'primary'),
                    $this->button('hero-secondary', __('studio_flatcms_default_secondary_cta', 'StudioFlatCMS'), '/page', 'secondary'),
                ]),
            ]),
        ]);
    }

    /**
     * @param array<string, mixed> $sourcePage
     * @return array<int, array<string, mixed>>
     */
    private function importedContentNodes(array $sourcePage): array
    {
        $content = trim((string) ($sourcePage['content'] ?? ''));
        if ($content === '') {
            return [];
        }

        if (!class_exists(\DOMDocument::class)) {
            $fallback = $this->sanitizeRichText($content, 12000, '');
            return $fallback !== '' ? [$this->text('hero-body', $fallback)] : [];
        }

        $internalErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><body><div id="studio-flatcms-import-root">' . $content . '</div></body></html>';
        $encoded = function_exists('mb_convert_encoding')
            ? (string) mb_convert_encoding($wrapped, 'HTML-ENTITIES', 'UTF-8')
            : $wrapped;
        $loaded = $dom->loadHTML($encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if ($loaded === false) {
            $fallback = $this->sanitizeRichText($content, 12000, '');
            return $fallback !== '' ? [$this->text('hero-body', $fallback)] : [];
        }

        $root = $dom->getElementById('studio-flatcms-import-root');
        if (!$root instanceof \DOMElement) {
            $fallback = $this->sanitizeRichText($content, 12000, '');
            return $fallback !== '' ? [$this->text('hero-body', $fallback)] : [];
        }

        $nodes = [];
        $textBuffer = '';
        $sequence = [
            'text' => 0,
            'image' => 0,
            'actions' => 0,
            'button' => 0,
        ];

        foreach ($root->childNodes as $child) {
            $this->appendImportedContentNode($child, $nodes, $textBuffer, $sequence);
        }

        $this->flushImportedTextBuffer($nodes, $textBuffer, $sequence);

        return $nodes;
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<string, int> $sequence
     */
    private function appendImportedContentNode(\DOMNode $node, array &$nodes, string &$textBuffer, array &$sequence): void
    {
        if ($node instanceof \DOMText) {
            $text = $this->normalizeImportedText($node->nodeValue ?? '');
            if ($text !== '') {
                $textBuffer .= '<p>' . $this->escapeHtml($text) . '</p>';
            }
            return;
        }

        if (!$node instanceof \DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style'], true)) {
            return;
        }

        if ($this->isButtonLinkGroupElement($node)) {
            $this->flushImportedTextBuffer($nodes, $textBuffer, $sequence);
            $stack = $this->makeImportedButtonStack($node, $sequence);
            if ($stack !== null) {
                $nodes[] = $stack;
            }
            return;
        }

        $image = $tag === 'img' ? $node : $this->extractStandaloneImageElement($node);
        if ($image instanceof \DOMElement) {
            $this->flushImportedTextBuffer($nodes, $textBuffer, $sequence);
            $imageNode = $this->makeImportedImageNode($image, $sequence);
            if ($imageNode !== null) {
                $nodes[] = $imageNode;
            }
            return;
        }

        $fragment = $this->sanitizeRichText($this->outerHtml($node), 12000, '');
        if ($fragment !== '') {
            $textBuffer .= $fragment;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @param array<string, int> $sequence
     */
    private function flushImportedTextBuffer(array &$nodes, string &$textBuffer, array &$sequence): void
    {
        $html = $this->sanitizeRichText($textBuffer, 12000, '');
        $textBuffer = '';
        if ($html === '') {
            return;
        }

        $id = $sequence['text'] === 0 ? 'hero-body' : 'hero-body-' . $sequence['text'];
        $sequence['text']++;
        $nodes[] = $this->text($id, $html);
    }

    /**
     * @param array<string, int> $sequence
     * @return array<string, mixed>|null
     */
    private function makeImportedImageNode(\DOMElement $image, array &$sequence): ?array
    {
        $src = $this->sanitizeUrl(trim((string) $image->getAttribute('src')));
        if ($src === '') {
            return null;
        }

        $id = $sequence['image'] === 0 ? 'hero-image' : 'hero-image-' . $sequence['image'];
        $sequence['image']++;

        return $this->image(
            $id,
            $src,
            $this->sanitizeText((string) $image->getAttribute('alt'), 280, '')
        );
    }

    /**
     * @param array<string, int> $sequence
     * @return array<string, mixed>|null
     */
    private function makeImportedButtonStack(\DOMElement $element, array &$sequence): ?array
    {
        $children = [];
        foreach ($element->getElementsByTagName('a') as $anchor) {
            if (!$anchor instanceof \DOMElement) {
                continue;
            }

            $button = $this->makeImportedButtonNode($anchor, $sequence);
            if ($button !== null) {
                $children[] = $button;
            }
        }

        if ($children === []) {
            return null;
        }

        $id = $sequence['actions'] === 0 ? 'hero-actions' : 'hero-actions-' . $sequence['actions'];
        $sequence['actions']++;

        return $this->stack($id, __('studio_flatcms_default_actions_label', 'StudioFlatCMS'), 'horizontal', $children);
    }

    /**
     * @param array<string, int> $sequence
     * @return array<string, mixed>|null
     */
    private function makeImportedButtonNode(\DOMElement $anchor, array &$sequence): ?array
    {
        $label = $this->normalizeImportedText($anchor->textContent ?? '');
        $url = $this->sanitizeUrl(trim((string) $anchor->getAttribute('href')));
        if ($label === '' || $url === '') {
            return null;
        }

        $id = $sequence['button'] === 0 ? 'hero-primary' : 'hero-button-' . $sequence['button'];
        $sequence['button']++;

        return $this->button($id, $label, $url, $this->buttonVariantFromClasses($anchor));
    }

    private function extractStandaloneImageElement(\DOMElement $element): ?\DOMElement
    {
        $images = $element->getElementsByTagName('img');
        if ($images->length !== 1) {
            return null;
        }

        $image = $images->item(0);
        if (!$image instanceof \DOMElement) {
            return null;
        }

        $text = $this->normalizeImportedText($element->textContent ?? '');
        return $text === '' ? $image : null;
    }

    private function isButtonLinkGroupElement(\DOMElement $element): bool
    {
        $anchors = $element->getElementsByTagName('a');
        if ($anchors->length === 0) {
            return false;
        }

        foreach ($anchors as $anchor) {
            if (!$anchor instanceof \DOMElement || !$this->elementHasClass($anchor, 'btn')) {
                return false;
            }
        }

        return true;
    }

    private function buttonVariantFromClasses(\DOMElement $anchor): string
    {
        if ($this->elementHasClass($anchor, 'btn-link')) {
            return 'link';
        }

        if ($this->elementHasClass($anchor, 'btn-secondary')) {
            return 'secondary';
        }

        return 'primary';
    }

    private function elementHasClass(\DOMElement $element, string $className): bool
    {
        $classAttribute = trim((string) $element->getAttribute('class'));
        if ($classAttribute === '') {
            return false;
        }

        $classes = preg_split('/\s+/', $classAttribute) ?: [];
        return in_array($className, $classes, true);
    }

    private function normalizeImportedText(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        return trim($normalized);
    }

    private function outerHtml(\DOMNode $node): string
    {
        $document = $node->ownerDocument;
        if (!$document instanceof \DOMDocument) {
            return '';
        }

        return (string) $document->saveHTML($node);
    }

    private function sourceImportVersion(): string
    {
        return 'native-source-v2';
    }

    /**
     * @param array<string, mixed> $sourcePage
     */
    private function sourceContentHash(array $sourcePage): string
    {
        $parts = [
            (string) ($sourcePage['id'] ?? ''),
            (string) ($sourcePage['title'] ?? ''),
            (string) ($sourcePage['slug'] ?? ''),
            (string) ($sourcePage['locale'] ?? ''),
            (string) ($sourcePage['content'] ?? ''),
        ];

        foreach ($parts as $part) {
            if ($part !== '') {
                return sha1(implode('|', $parts));
            }
        }

        return '';
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $candidate
     * @param array<string, mixed> $default
     * @return array<string, mixed>
     */
    private function normalizeRegion(array $candidate, array $default): array
    {
        $children = is_array($candidate['children'] ?? null) ? $candidate['children'] : [];
        $normalizedChildren = [];
        foreach ($children as $child) {
            if (!is_array($child)) {
                continue;
            }
            $normalizedChildren[] = $this->normalizeNode($child);
        }

        if ($normalizedChildren === []) {
            $normalizedChildren = is_array($default['children'] ?? null) ? $default['children'] : [];
        }

        return [
            'id' => (string) ($default['id'] ?? 'region'),
            'type' => 'region',
            'tag' => (string) ($default['tag'] ?? 'section'),
            'label' => $this->sanitizeText((string) ($candidate['label'] ?? $default['label'] ?? ''), 120, (string) ($default['label'] ?? '')),
            'enabled' => array_key_exists('enabled', $candidate) ? (bool) $candidate['enabled'] : (bool) ($default['enabled'] ?? true),
            'frame' => $this->normalizeFrame($candidate['frame'] ?? ($default['frame'] ?? [])),
            'children' => $normalizedChildren,
        ];
    }

    /**
     * @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private function normalizeNode(array $node): array
    {
        $type = $this->sanitizeEnum((string) ($node['type'] ?? ''), ['section', 'stack', 'text', 'button', 'image', 'menu', 'logo'], 'text');
        $normalized = [
            'id' => $this->sanitizeNodeId((string) ($node['id'] ?? $type)),
            'type' => $type,
            'label' => $this->sanitizeText((string) ($node['label'] ?? ''), 120, ''),
            'enabled' => array_key_exists('enabled', $node) ? (bool) $node['enabled'] : true,
            'frame' => $this->normalizeFrame($node['frame'] ?? []),
        ];

        if (in_array($type, ['section', 'stack'], true)) {
            $normalized['appearance'] = $this->sanitizeEnum((string) ($node['appearance'] ?? 'none'), ['none', 'soft', 'contrast'], 'none');
            $normalized['direction'] = $type === 'stack'
                ? $this->sanitizeEnum((string) ($node['direction'] ?? 'vertical'), ['vertical', 'horizontal'], 'vertical')
                : 'vertical';
            $children = is_array($node['children'] ?? null) ? $node['children'] : [];
            $normalized['children'] = [];
            foreach ($children as $child) {
                if (!is_array($child)) {
                    continue;
                }
                $normalized['children'][] = $this->normalizeNode($child);
            }
        }

        if ($type === 'text') {
            $normalized['content'] = $this->sanitizeRichText((string) ($node['content'] ?? ''), 12000, '');
        }

        if ($type === 'logo') {
            $normalized['content'] = $this->sanitizeText((string) ($node['content'] ?? ''), 1200, '');
        }

        if ($type === 'button') {
            $normalized['content'] = $this->sanitizeText((string) ($node['content'] ?? ''), 180, '');
            $normalized['url'] = $this->sanitizeUrl((string) ($node['url'] ?? ''));
            $normalized['variant'] = $this->sanitizeEnum((string) ($node['variant'] ?? 'primary'), ['primary', 'secondary', 'link'], 'primary');
        }

        if ($type === 'image') {
            $normalized['src'] = $this->sanitizeUrl((string) ($node['src'] ?? ''));
            $normalized['alt'] = $this->sanitizeText((string) ($node['alt'] ?? ''), 280, '');
        }

        if ($type === 'menu') {
            $normalized['items'] = [];
            $items = is_array($node['items'] ?? null) ? $node['items'] : [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $normalized['items'][] = [
                    'label' => $this->sanitizeText((string) ($item['label'] ?? ''), 120, ''),
                    'url' => $this->sanitizeUrl((string) ($item['url'] ?? '')),
                ];
            }
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $children
     * @return array<string, mixed>
     */
    private function region(string $tag, bool $enabled, array $children): array
    {
        return [
            'id' => 'region-' . $tag,
            'type' => 'region',
            'tag' => $tag,
            'label' => __('studio_flatcms_region_' . $tag, 'StudioFlatCMS'),
            'enabled' => $enabled,
            'frame' => $this->defaultFrame(),
            'children' => $children,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $children
     * @return array<string, mixed>
     */
    private function section(string $id, string $label, string $appearance, array $children): array
    {
        return [
            'id' => $id,
            'type' => 'section',
            'label' => $label,
            'enabled' => true,
            'appearance' => $appearance,
            'direction' => 'vertical',
            'frame' => $this->defaultFrame(),
            'children' => $children,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $children
     * @return array<string, mixed>
     */
    private function stack(string $id, string $label, string $direction, array $children): array
    {
        return [
            'id' => $id,
            'type' => 'stack',
            'label' => $label,
            'enabled' => true,
            'appearance' => 'none',
            'direction' => $direction,
            'frame' => $this->defaultFrame(),
            'children' => $children,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function text(string $id, string $content): array
    {
        return [
            'id' => $id,
            'type' => 'text',
            'label' => __('studio_flatcms_node_text', 'StudioFlatCMS'),
            'enabled' => true,
            'frame' => $this->defaultFrame(),
            'content' => $content,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function button(string $id, string $label, string $url, string $variant): array
    {
        return [
            'id' => $id,
            'type' => 'button',
            'label' => __('studio_flatcms_node_button', 'StudioFlatCMS'),
            'enabled' => true,
            'frame' => $this->defaultFrame(),
            'content' => $label,
            'url' => $url,
            'variant' => $variant,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function image(string $id, string $src, string $alt): array
    {
        return [
            'id' => $id,
            'type' => 'image',
            'label' => __('studio_flatcms_node_image', 'StudioFlatCMS'),
            'enabled' => true,
            'frame' => $this->defaultFrame(),
            'src' => $src,
            'alt' => $alt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $id, array $items): array
    {
        return [
            'id' => $id,
            'type' => 'menu',
            'label' => __('studio_flatcms_node_menu', 'StudioFlatCMS'),
            'enabled' => true,
            'frame' => $this->defaultFrame(),
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function logo(string $id, string $content): array
    {
        return [
            'id' => $id,
            'type' => 'logo',
            'label' => __('studio_flatcms_node_logo', 'StudioFlatCMS'),
            'enabled' => true,
            'frame' => $this->defaultFrame(),
            'content' => $content,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string, int|null>
     */
    private function normalizeFrame(mixed $value): array
    {
        $frame = is_array($value) ? $value : [];

        return [
            'offsetX' => $this->sanitizeSignedInt($frame['offsetX'] ?? 0),
            'offsetY' => $this->sanitizeSignedInt($frame['offsetY'] ?? 0),
            'width' => $this->sanitizeNullablePositiveInt($frame['width'] ?? null),
            'height' => $this->sanitizeNullablePositiveInt($frame['height'] ?? null),
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function defaultFrame(): array
    {
        return [
            'offsetX' => 0,
            'offsetY' => 0,
            'width' => null,
            'height' => null,
        ];
    }

    private function sanitizeDocumentId(string $value): string
    {
        $clean = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($value))) ?? '';
        $clean = trim($clean, '-');
        return $clean !== '' ? $clean : 'home';
    }

    private function sanitizeNodeId(string $value): string
    {
        $clean = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower(trim($value))) ?? '';
        $clean = trim($clean, '-');
        return $clean !== '' ? $clean : 'node';
    }

    private function sanitizeText(string $value, int $maxLength, string $fallback): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return $fallback;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($clean, 0, $maxLength);
        }

        return substr($clean, 0, $maxLength);
    }

    private function sanitizeRichText(string $value, int $maxLength, string $fallback): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return $fallback;
        }

        if (function_exists('mb_substr')) {
            $clean = (string) mb_substr($clean, 0, $maxLength);
        } else {
            $clean = substr($clean, 0, $maxLength);
        }

        $allowedTags = '<p><br><strong><b><em><i><u><s><strike><ul><ol><li><a><blockquote><h1><h2><h3><h4><h5><h6><div><span>';
        $clean = strip_tags($clean, $allowedTags);
        if ($clean === '') {
            return $fallback;
        }

        if (!class_exists(\DOMDocument::class)) {
            return $clean;
        }

        $internalErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><body><div id="studio-flatcms-rich-root">' . $clean . '</div></body></html>';
        $encoded = function_exists('mb_convert_encoding')
            ? (string) mb_convert_encoding($wrapped, 'HTML-ENTITIES', 'UTF-8')
            : $wrapped;
        $loaded = $dom->loadHTML($encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if ($loaded === false) {
            return $clean;
        }

        $root = $dom->getElementById('studio-flatcms-rich-root');
        if (!$root instanceof \DOMElement) {
            return $clean;
        }

        $this->sanitizeRichTextNode($root);

        $html = '';
        foreach ($root->childNodes as $childNode) {
            $html .= (string) $dom->saveHTML($childNode);
        }

        $html = trim($html);
        return $html !== '' ? $html : $fallback;
    }

    private function sanitizeRichTextNode(\DOMNode $node): void
    {
        for ($child = $node->firstChild; $child !== null; $child = $nextSibling) {
            $nextSibling = $child->nextSibling;

            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);
                if (!$this->isAllowedRichTextTag($tag)) {
                    while ($child->firstChild !== null) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }

                $this->sanitizeRichTextAttributes($child);
                $this->sanitizeRichTextNode($child);
                continue;
            }

            if ($child instanceof \DOMText) {
                continue;
            }

            $node->removeChild($child);
        }
    }

    private function isAllowedRichTextTag(string $tag): bool
    {
        return in_array($tag, [
            'p',
            'br',
            'strong',
            'b',
            'em',
            'i',
            'u',
            's',
            'strike',
            'ul',
            'ol',
            'li',
            'a',
            'blockquote',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'div',
            'span',
        ], true);
    }

    private function sanitizeRichTextAttributes(\DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $allowedAttributes = $tag === 'a' ? ['href', 'target', 'rel'] : [];
        $attributeNames = [];

        foreach ($element->attributes as $attribute) {
            $attributeNames[] = $attribute->name;
        }

        foreach ($attributeNames as $attributeName) {
            if (!in_array($attributeName, $allowedAttributes, true)) {
                $element->removeAttribute($attributeName);
            }
        }

        if ($tag !== 'a') {
            return;
        }

        $href = trim((string) $element->getAttribute('href'));
        if (!$this->isAllowedRichTextHref($href)) {
            $element->removeAttribute('href');
        } else {
            $element->setAttribute('href', $href);
        }

        $target = trim((string) $element->getAttribute('target'));
        if ($target === '_blank') {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
            return;
        }

        $element->removeAttribute('target');
        $element->removeAttribute('rel');
    }

    private function isAllowedRichTextHref(string $href): bool
    {
        if ($href === '') {
            return false;
        }

        if (str_starts_with($href, '/') || str_starts_with($href, '#')) {
            return true;
        }

        return preg_match('~^(https?:|mailto:|tel:)~i', $href) === 1;
    }

    /**
     * @param array<int, string> $allowed
     */
    private function sanitizeEnum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function sanitizeZoom(mixed $value): int
    {
        $zoom = (int) $value;
        if ($zoom < 50) {
            return 50;
        }
        if ($zoom > 150) {
            return 150;
        }

        return $zoom;
    }

    private function sanitizeUrl(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return '';
        }

        if (str_starts_with($clean, '/') || str_starts_with($clean, '#') || filter_var($clean, FILTER_VALIDATE_URL)) {
            return $clean;
        }

        return '';
    }

    private function sanitizeSignedInt(mixed $value): int
    {
        $number = (int) $value;
        if ($number < -5000) {
            return -5000;
        }
        if ($number > 5000) {
            return 5000;
        }

        return $number;
    }

    private function sanitizeNullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $number = (int) $value;
        if ($number <= 0) {
            return null;
        }
        if ($number > 5000) {
            return 5000;
        }

        return $number;
    }
}
