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
    public function defaultDocument(string $documentId = 'home', array $settings = []): array
    {
        $brandText = trim((string) ($settings['site_name'] ?? ''));
        if ($brandText === '') {
            $brandText = __('app_name', 'Core');
        }

        return [
            'version' => 1,
            'id' => $this->sanitizeDocumentId($documentId),
            'title' => __('studio_flatcms_document_title', 'StudioFlatCMS'),
            'mode' => 'compose',
            'viewport' => 'desktop',
            'zoom' => 100,
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
                $this->region('main', true, [
                    $this->section('hero-section', __('studio_flatcms_default_hero_label', 'StudioFlatCMS'), 'soft', [
                        $this->stack('hero-copy', __('studio_flatcms_default_copy_label', 'StudioFlatCMS'), 'vertical', [
                            $this->text('hero-title', __('studio_flatcms_default_heading', 'StudioFlatCMS')),
                            $this->text('hero-body', __('studio_flatcms_default_body', 'StudioFlatCMS')),
                            $this->stack('hero-actions', __('studio_flatcms_default_actions_label', 'StudioFlatCMS'), 'horizontal', [
                                $this->button('hero-primary', __('studio_flatcms_default_primary_cta', 'StudioFlatCMS'), '/contact', 'primary'),
                                $this->button('hero-secondary', __('studio_flatcms_default_secondary_cta', 'StudioFlatCMS'), '/page', 'secondary'),
                            ]),
                        ]),
                    ]),
                ]),
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
    public function normalizeDocument(array $payload, string $documentId = 'home', array $settings = []): array
    {
        $default = $this->defaultDocument($documentId, $settings);
        $regions = is_array($payload['regions'] ?? null) ? $payload['regions'] : [];

        $normalized = [
            'version' => 1,
            'id' => $this->sanitizeDocumentId((string) ($payload['id'] ?? $default['id'])),
            'title' => $this->sanitizeText((string) ($payload['title'] ?? $default['title']), 180, (string) $default['title']),
            'mode' => $this->sanitizeEnum((string) ($payload['mode'] ?? $default['mode']), ['compose', 'theme'], (string) $default['mode']),
            'viewport' => $this->sanitizeEnum((string) ($payload['viewport'] ?? $default['viewport']), ['desktop', 'tablet', 'mobile'], (string) $default['viewport']),
            'zoom' => $this->sanitizeZoom($payload['zoom'] ?? $default['zoom']),
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
        $type = $this->sanitizeEnum((string) ($node['type'] ?? ''), ['section', 'stack', 'text', 'button', 'menu', 'logo'], 'text');
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

        if (str_starts_with($clean, '/') || filter_var($clean, FILTER_VALIDATE_URL)) {
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
