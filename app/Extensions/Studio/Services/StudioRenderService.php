<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\Studio\Services;

final class StudioRenderService
{
    /**
     * @param array<string, mixed> $sourcePage
     * @param array<string, mixed> $document
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function buildRenderablePage(array $sourcePage, array $document, array $context = []): array
    {
        $page = $sourcePage;
        $page['content'] = $this->renderDocumentContent($sourcePage, $document, $context);
        $page['render_mode'] = 'studio';
        $page['editor_mode'] = 'studio';
        $page['studio_document'] = $document;
        $page['builder_assets'] = $this->buildAssetUrls($document, (string) ($sourcePage['id'] ?? ''));

        if ($this->containsStructuredSections($document)) {
            $page['page_header_enabled'] = false;
        }

        return $page;
    }

    /**
     * @param array<string, mixed> $document
     * @return array{css: array<int, string>, js: array<int, string>}
     */
    private function buildAssetUrls(array $document, string $entityId): array
    {
        $assets = [
            'css' => [],
            'js' => [],
        ];

        $baseCss = trim((string) module_asset('Studio', 'css/studio-front.css'));
        if ($baseCss !== '') {
            $assets['css'][] = $baseCss;
        }

        $runtimeCss = runtime_css_asset($this->buildRuntimeCss($document), 'studio-preview', $entityId);
        if ($runtimeCss !== '') {
            $assets['css'][] = $runtimeCss;
        }

        return $assets;
    }

    /**
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    private function design(array $document): array
    {
        return is_array($document['design']['global'] ?? null) ? $document['design']['global'] : [];
    }

    /**
     * @param array<string, mixed> $sourcePage
     * @param array<string, mixed> $document
     * @param array<string, mixed> $context
     */
    private function renderDocumentContent(array $sourcePage, array $document, array $context): string
    {
        $parts = [];
        $layout = is_array($document['layout'] ?? null) ? $document['layout'] : [];
        $sections = is_array($document['sections'] ?? null) ? $document['sections'] : [];
        $renderGlobalRegions = !array_key_exists('render_global_regions', $context) || (bool) ($context['render_global_regions'] ?? true);

        if ($renderGlobalRegions) {
            $parts[] = $this->renderLayoutRegion('header_before', is_array($layout['header_before']['blocks'] ?? null) ? $layout['header_before']['blocks'] : [], $context);
            $parts[] = $this->renderLayoutRegion('header_after', is_array($layout['header_after']['blocks'] ?? null) ? $layout['header_after']['blocks'] : [], $context);
        }

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $parts[] = $this->renderSection($section, $context);
        }

        $parts[] = $this->renderLayoutRegion('aside', is_array($layout['aside']['blocks'] ?? null) ? $layout['aside']['blocks'] : [], $context);
        if ($renderGlobalRegions) {
            $parts[] = $this->renderLayoutRegion('footer', is_array($layout['footer']['blocks'] ?? null) ? $layout['footer']['blocks'] : [], $context);
        }

        $html = implode('', array_values(array_filter($parts, static fn (string $part): bool => trim($part) !== '')));
        if ($html === '') {
            $html = $this->renderCanonicalRichtext((string) ($sourcePage['content'] ?? ''), $context);
        }

        return '<div class="studio-front">' . $html . '</div>';
    }

    /**
     * @param array<int, mixed> $blocks
     * @param array<string, mixed> $context
     */
    private function renderLayoutRegion(string $name, array $blocks, array $context): string
    {
        $html = $this->renderBlocks($blocks, $context);
        if ($html === '') {
            return '';
        }

        return '<section class="studio-front-region studio-front-region-' . $this->escapeAttr($name) . '">' . $html . '</section>';
    }

    /**
     * @param array<string, mixed> $section
     * @param array<string, mixed> $context
     */
    private function renderSection(array $section, array $context): string
    {
        $type = trim((string) ($section['type'] ?? 'content'));
        $label = trim((string) ($section['label'] ?? ''));
        $settings = is_array($section['settings'] ?? null) ? $section['settings'] : [];
        $items = is_array($section['items'] ?? null) ? $section['items'] : [];
        $blocks = is_array($section['blocks'] ?? null) ? $section['blocks'] : [];

        if ($type === 'content') {
            $contentHtml = $this->renderCanonicalRichtext((string) ($settings['html'] ?? ''), $context);
            $blocksHtml = $this->renderBlocks($blocks, $context);
            $body = $blocksHtml !== '' ? $blocksHtml : $contentHtml;
            if ($body === '') {
                return '';
            }

            return '<section class="studio-front-section studio-front-section-content">' .
                ($label !== '' ? '<div class="studio-front-section-label">' . $this->escape($label) . '</div>' : '') .
                $body .
                '</section>';
        }

        $body = match ($type) {
            'hero' => $this->renderHeroSection($settings),
            'services', 'blog' => $this->renderCardSection($type, $settings, $items),
            'split' => $this->renderSplitSection($settings),
            'stats' => $this->renderStatsSection($items),
            'testimonial' => $this->renderTestimonialSection($settings),
            'faq' => $this->renderFaqSection($settings, $items),
            'cta' => $this->renderCtaSection($settings),
            default => '',
        };

        $primaryAction = in_array($type, ['hero', 'split', 'cta'], true)
            ? $this->renderLink((string) ($settings['button_label'] ?? ''), (string) ($settings['button_url'] ?? '#'), true)
            : '';
        $blocksHtml = $this->renderBlocks($blocks, $context, $primaryAction);
        if ($body === '' && $blocksHtml === '') {
            return '';
        }

        return '<section class="studio-front-section studio-front-section-' . $this->escapeAttr($type) . '">' .
            ($label !== '' ? '<div class="studio-front-section-label">' . $this->escape($label) . '</div>' : '') .
            $body .
            $blocksHtml .
            '</section>';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderHeroSection(array $settings): string
    {
        return '<div class="studio-front-hero prose">' .
            $this->renderEyebrow((string) ($settings['eyebrow'] ?? '')) .
            $this->renderHeading('h1', (string) ($settings['title'] ?? '')) .
            $this->renderText((string) ($settings['text'] ?? '')) .
            '</div>';
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<int, mixed> $items
     */
    private function renderCardSection(string $type, array $settings, array $items): string
    {
        $cards = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $cards[] = '<article class="studio-front-card prose">' .
                $this->renderHeading('h3', (string) ($item['title'] ?? '')) .
                $this->renderText((string) ($item['text'] ?? '')) .
                '</article>';
        }

        return '<div class="studio-front-stack prose">' .
            $this->renderEyebrow((string) ($settings['eyebrow'] ?? '')) .
            $this->renderHeading('h2', (string) ($settings['title'] ?? '')) .
            ($cards !== [] ? '<div class="studio-front-grid studio-front-grid-' . $this->escapeAttr($type) . '">' . implode('', $cards) . '</div>' : '') .
            '</div>';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderSplitSection(array $settings): string
    {
        return '<div class="studio-front-split">' .
            '<div class="studio-front-split-copy prose">' .
            $this->renderEyebrow((string) ($settings['eyebrow'] ?? '')) .
            $this->renderHeading('h2', (string) ($settings['title'] ?? '')) .
            $this->renderText((string) ($settings['text'] ?? '')) .
            '</div>' .
            '<div class="studio-front-split-media" aria-hidden="true"></div>' .
            '</div>';
    }

    /**
     * @param array<int, mixed> $items
     */
    private function renderStatsSection(array $items): string
    {
        $stats = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $stats[] = '<article class="studio-front-stat">' .
                '<strong>' . $this->escape((string) ($item['value'] ?? '')) . '</strong>' .
                '<span>' . $this->escape((string) ($item['label'] ?? '')) . '</span>' .
                '</article>';
        }

        return $stats !== [] ? '<div class="studio-front-stats">' . implode('', $stats) . '</div>' : '';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderTestimonialSection(array $settings): string
    {
        $quote = trim((string) ($settings['quote'] ?? ''));
        $author = trim((string) ($settings['author'] ?? ''));
        if ($quote === '' && $author === '') {
            return '';
        }

        return '<blockquote class="studio-front-testimonial prose">' .
            ($quote !== '' ? '<p>' . $this->escape($quote) . '</p>' : '') .
            ($author !== '' ? '<cite>' . $this->escape($author) . '</cite>' : '') .
            '</blockquote>';
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<int, mixed> $items
     */
    private function renderFaqSection(array $settings, array $items): string
    {
        $rows = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));
            if ($question === '' && $answer === '') {
                continue;
            }

            $rows[] = '<details class="studio-front-faq-item">' .
                '<summary>' . $this->escape($question) . '</summary>' .
                ($answer !== '' ? '<div>' . $this->renderText($answer) . '</div>' : '') .
                '</details>';
        }

        return '<div class="studio-front-stack prose">' .
            $this->renderEyebrow((string) ($settings['eyebrow'] ?? '')) .
            $this->renderHeading('h2', (string) ($settings['title'] ?? '')) .
            implode('', $rows) .
            '</div>';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderCtaSection(array $settings): string
    {
        return '<div class="studio-front-cta prose">' .
            $this->renderHeading('h2', (string) ($settings['title'] ?? '')) .
            $this->renderText((string) ($settings['text'] ?? '')) .
            '</div>';
    }

    /**
     * @param array<int, mixed> $blocks
     * @param array<string, mixed> $context
     */
    private function renderBlocks(array $blocks, array $context, string $leadingButton = ''): string
    {
        $parts = [];
        $buttons = [];

        if ($leadingButton !== '') {
            $buttons[] = $leadingButton;
        }

        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $type = trim((string) ($block['type'] ?? ''));
            $rendered = $this->renderBlock($block, $context);
            if ($rendered === '') {
                continue;
            }

            if ($type === 'button') {
                $buttons[] = $rendered;
                continue;
            }

            if ($buttons !== []) {
                $parts[] = '<div class="studio-front-actions">' . implode('', $buttons) . '</div>';
                $buttons = [];
            }

            $parts[] = $rendered;
        }

        if ($buttons !== []) {
            $parts[] = '<div class="studio-front-actions">' . implode('', $buttons) . '</div>';
        }

        return $parts !== [] ? '<div class="studio-front-blocks">' . implode('', $parts) . '</div>' : '';
    }

    /**
     * @param array<string, mixed> $block
     * @param array<string, mixed> $context
     */
    private function renderBlock(array $block, array $context): string
    {
        $type = trim((string) ($block['type'] ?? 'text'));
        $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];
        $items = is_array($block['items'] ?? null) ? $block['items'] : [];

        return match ($type) {
            'heading' => '<div class="studio-front-copy prose">' . $this->renderHeading('h3', (string) ($settings['text'] ?? '')) . '</div>',
            'text' => '<div class="studio-front-copy prose">' . $this->renderText((string) ($settings['text'] ?? '')) . '</div>',
            'button' => $this->renderLink((string) ($settings['text'] ?? ''), (string) ($settings['url'] ?? '#'), true),
            'image' => $this->renderImageBlock($settings),
            'cards' => $this->renderCardsBlock($items),
            'form' => $this->renderPlaceholderBlock('@', (string) ($settings['text'] ?? '')),
            'map' => $this->renderPlaceholderBlock('⌖', (string) ($settings['address'] ?? '')),
            'plugin' => $this->renderPlaceholderBlock('⚙', (string) ($settings['plugin'] ?? '')),
            'spacer' => '<div class="studio-front-spacer" aria-hidden="true"></div>',
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderImageBlock(array $settings): string
    {
        $src = trim((string) ($settings['src'] ?? ''));
        $alt = trim((string) ($settings['alt'] ?? ''));
        $height = $this->normalizeImageHeight((string) ($settings['height'] ?? 'auto'));
        if ($src === '') {
            return '<div class="studio-front-placeholder">' . $this->escape(__('studio_canvas_fake_media', 'Studio')) . '</div>';
        }

        return '<figure class="studio-front-image" data-image-height="' . $this->escapeAttr($height) . '"><img src="' . $this->escapeAttr(site_media_url($src) ?: $src) . '" alt="' . $this->escapeAttr($alt) . '"></figure>';
    }

    private function normalizeImageHeight(string $value): string
    {
        $allowed = ['auto', '180', '240', '320', '420', '560'];
        $normalized = trim($value);

        return in_array($normalized, $allowed, true) ? $normalized : 'auto';
    }

    /**
     * @param array<int, mixed> $items
     */
    private function renderCardsBlock(array $items): string
    {
        $cards = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $cards[] = '<article class="studio-front-card prose">' .
                $this->renderHeading('h3', (string) ($item['title'] ?? '')) .
                $this->renderText((string) ($item['text'] ?? '')) .
                '</article>';
        }

        return $cards !== [] ? '<div class="studio-front-grid studio-front-grid-cards">' . implode('', $cards) . '</div>' : '';
    }

    private function renderPlaceholderBlock(string $prefix, string $text): string
    {
        $value = trim($text);
        if ($value === '') {
            return '';
        }

        return '<div class="studio-front-placeholder">' . $this->escape($prefix . ' ' . $value) . '</div>';
    }

    private function renderEyebrow(string $value): string
    {
        $value = trim($value);
        return $value !== '' ? '<p class="studio-front-eyebrow">' . $this->escape($value) . '</p>' : '';
    }

    private function renderHeading(string $tag, string $value): string
    {
        $value = trim($value);
        return $value !== '' ? '<' . $tag . '>' . $this->escape($value) . '</' . $tag . '>' : '';
    }

    private function renderText(string $value): string
    {
        return $this->renderPlainText($value);
    }

    private function renderPlainText(string $value): string
    {
        $normalized = preg_replace("/\r\n?/", "\n", trim($value)) ?? '';
        if ($normalized === '') {
            return '';
        }

        $lines = explode("\n", $normalized);
        $parts = [];
        $paragraph = [];
        $listItems = [];
        $listTag = '';

        $flushParagraph = function () use (&$parts, &$paragraph): void {
            if ($paragraph === []) {
                return;
            }

            $parts[] = '<p>' . implode('<br>', array_map([$this, 'escape'], $paragraph)) . '</p>';
            $paragraph = [];
        };

        $flushList = function () use (&$parts, &$listItems, &$listTag): void {
            if ($listItems === [] || $listTag === '') {
                $listItems = [];
                $listTag = '';
                return;
            }

            $parts[] = '<' . $listTag . '>' . implode('', $listItems) . '</' . $listTag . '>';
            $listItems = [];
            $listTag = '';
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                $flushParagraph();
                $flushList();
                continue;
            }

            if (preg_match('/^(?:([*\-•])|(\d+)\.)\s+(.+)$/u', $trimmed, $matches) === 1) {
                $flushParagraph();
                $nextListTag = !empty($matches[2]) ? 'ol' : 'ul';
                if ($listTag !== '' && $listTag !== $nextListTag) {
                    $flushList();
                }
                $listTag = $nextListTag;
                $listItems[] = '<li>' . $this->escape((string) ($matches[3] ?? '')) . '</li>';
                continue;
            }

            $flushList();
            $paragraph[] = $trimmed;
        }

        $flushParagraph();
        $flushList();

        return implode('', $parts);
    }

    private function renderLink(string $label, string $url, bool $button = false): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        $class = $button ? ' class="studio-front-button btn btn-primary"' : '';
        return '<a' . $class . ' href="' . $this->escapeAttr(trim($url) !== '' ? $url : '#') . '">' . $this->escape($label) . '</a>';
    }

    /**
     * @param array<string, mixed> $document
     */
    private function containsStructuredSections(array $document): bool
    {
        $sections = is_array($document['sections'] ?? null) ? $document['sections'] : [];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            if (trim((string) ($section['type'] ?? '')) !== 'content') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $document
     */
    private function buildRuntimeCss(array $document): string
    {
        $design = $this->design($document);
        $primary = $this->normalizeColor((string) ($design['primary'] ?? '#4F46E5'), '#4F46E5');
        $accent = $this->normalizeColor((string) ($design['accent'] ?? '#111827'), '#111827');
        $ink = $this->normalizeColor((string) ($design['ink'] ?? '#111827'), '#111827');
        $paper = $this->normalizeColor((string) ($design['paper'] ?? '#FFFFFF'), '#FFFFFF');
        $soft = $this->normalizeColor((string) ($design['soft'] ?? '#F7F8FA'), '#F7F8FA');
        $radius = max(0, min(24, (int) ($design['radius'] ?? 8)));
        $font = trim((string) ($design['font'] ?? ''));

        $rules = [
            '.studio-front{--studio-primary:' . $primary . ';--studio-accent:' . $accent . ';--studio-ink:' . $ink . ';--studio-paper:' . $paper . ';--studio-soft:' . $soft . ';--studio-radius:' . $radius . 'px;}',
        ];

        if ($font !== '') {
            $rules[] = '.studio-front{font-family:' . $this->sanitizeFontFamily($font) . ';}';
        }

        return implode("\n", $rules);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderCanonicalContent(string $content, array $context): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        $rendered = flatcms_render_shortcodes($content, [
            'source_url' => (string) ($context['source_url'] ?? ''),
            'locale' => (string) ($context['locale'] ?? ''),
        ]);

        return $this->stripExecutableContent($this->normalizeContentMediaUrls($rendered));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderCanonicalRichtext(string $content, array $context): string
    {
        $html = $this->renderCanonicalContent($content, $context);
        if ($html === '') {
            return '';
        }

        return '<div class="studio-front-richtext prose">' . $html . '</div>';
    }

    private function stripExecutableContent(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $pattern = '~<scr' . 'ipt\b[^>]*>.*?</scr' . 'ipt>~is';
        return (string) preg_replace($pattern, '', $content);
    }

    private function normalizeContentMediaUrls(string $content): string
    {
        if ($content === '') {
            return $content;
        }

        return (string) preg_replace_callback(
            '/\b(src|href|poster)\s*=\s*(["\'])(.*?)\2/i',
            function (array $matches): string {
                $attribute = (string) ($matches[1] ?? '');
                $quote = (string) ($matches[2] ?? '"');
                $rawValue = html_entity_decode((string) ($matches[3] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if ($rawValue === '' || str_starts_with($rawValue, 'data:') || str_starts_with($rawValue, 'blob:')) {
                    return $matches[0];
                }

                if (!$this->shouldNormalizeMediaUrl($attribute, $rawValue)) {
                    return $matches[0];
                }

                $normalized = site_media_url($rawValue);
                if ($normalized === '') {
                    return $matches[0];
                }

                return $attribute . '=' . $quote . htmlspecialchars($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8') . $quote;
            },
            $content
        );
    }

    private function shouldNormalizeMediaUrl(string $attribute, string $rawValue): bool
    {
        $attributeName = strtolower(trim($attribute));
        if ($attributeName === 'src' || $attributeName === 'poster') {
            return true;
        }

        if ($attributeName !== 'href') {
            return false;
        }

        $value = trim($rawValue);
        if ($value === '') {
            return false;
        }

        if (preg_match('~^(#|mailto:|tel:|javascript:)~i', $value) === 1) {
            return false;
        }

        if (preg_match('~^(https?:)?//~i', $value) === 1) {
            return false;
        }

        if (flatcms_normalize_upload_media_path($value) !== '') {
            return true;
        }

        $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
        if ($path !== '' && flatcms_normalize_upload_media_path($path) !== '') {
            return true;
        }

        return $path !== '' && preg_match(
            '~\.(?:avif|bmp|gif|ico|jpe?g|png|svg|webp|mp4|webm|ogv|mp3|wav|ogg|pdf|docx?|xlsx?|pptx?|zip|rar|7z|csv|txt)$~i',
            $path
        ) === 1;
    }

    private function normalizeColor(string $value, string $fallback): string
    {
        $trimmed = trim($value);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $trimmed) === 1) {
            return strtoupper($trimmed);
        }

        return strtoupper($fallback);
    }

    private function sanitizeFontFamily(string $value): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9,\-_"\'\s]/', '', $value) ?? '';
        return trim($sanitized) !== '' ? $sanitized : 'inherit';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function escapeAttr(string $value): string
    {
        return $this->escape($value);
    }
}
