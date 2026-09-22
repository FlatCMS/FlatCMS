<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Services/Seo/SeoAnalysisService.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Services\Seo;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class SeoAnalysisService
{
    public const MAX_CONTENT_BYTES = 2_000_000;

    /**
     * @param array<string, mixed> $document
     * @return array{score:int,rating:string,checks:array<int,array<string,mixed>>,metrics:array<string,int>}
     */
    public function analyze(array $document): array
    {
        $entity = strtolower(trim((string) ($document['entity'] ?? 'page')));
        $entity = $entity === 'post' ? 'post' : 'page';
        $title = $this->plainText((string) ($document['title'] ?? ''), 300);
        $slug = trim((string) ($document['slug'] ?? ''));
        $metaTitle = $this->plainText((string) ($document['meta_title'] ?? ''), 300);
        $metaDescription = $this->plainText((string) ($document['meta_description'] ?? ''), 1_000);
        $content = substr((string) ($document['content'] ?? ''), 0, self::MAX_CONTENT_BYTES);
        $status = strtolower(trim((string) ($document['status'] ?? 'draft')));
        $robots = strtolower(trim((string) ($document['robots'] ?? '')));

        $contentMetrics = $this->contentMetrics($content);
        $checks = [
            $this->titleCheck($title),
            $this->slugCheck($slug),
            $this->metaTitleCheck($metaTitle),
            $this->metaDescriptionCheck($metaDescription),
            $this->contentCheck($contentMetrics['word_count'], $entity),
            $this->headingCheck($contentMetrics),
            $this->linkCheck($contentMetrics['link_count']),
            $this->mediaCheck($contentMetrics['image_count'], $contentMetrics['missing_alt_count']),
            $this->indexabilityCheck($status, $robots),
        ];

        $score = array_sum(array_map(
            static fn (array $check): int => (int) ($check['earned'] ?? 0),
            $checks
        ));

        return [
            'score' => max(0, min(100, $score)),
            'rating' => $score >= 80 ? 'good' : ($score >= 50 ? 'improvable' : 'weak'),
            'checks' => $checks,
            'metrics' => [
                'word_count' => $contentMetrics['word_count'],
                'heading_count' => $contentMetrics['heading_count'],
                'link_count' => $contentMetrics['link_count'],
                'image_count' => $contentMetrics['image_count'],
                'missing_alt_count' => $contentMetrics['missing_alt_count'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function titleCheck(string $title): array
    {
        $length = $this->length($title);
        if ($length === 0) {
            return $this->check('title', 'title', 10, 0, 'fail', 'seo_analysis_title_missing');
        }
        if ($length > 70) {
            return $this->check('title', 'title', 10, 5, 'warning', 'seo_analysis_title_long', ['count' => $length]);
        }

        return $this->check('title', 'title', 10, 10, 'pass', 'seo_analysis_title_good', ['count' => $length]);
    }

    /** @return array<string, mixed> */
    private function slugCheck(string $slug): array
    {
        $length = $this->length($slug);
        if ($slug === '') {
            return $this->check('slug', 'slug', 10, 0, 'fail', 'seo_analysis_slug_missing');
        }
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) !== 1 || $length > 75) {
            return $this->check('slug', 'slug', 10, 5, 'warning', 'seo_analysis_slug_improve');
        }

        return $this->check('slug', 'slug', 10, 10, 'pass', 'seo_analysis_slug_good');
    }

    /** @return array<string, mixed> */
    private function metaTitleCheck(string $metaTitle): array
    {
        $length = $this->length($metaTitle);
        if ($length === 0) {
            return $this->check('meta_title', 'meta_title', 15, 0, 'fail', 'seo_analysis_meta_title_missing');
        }
        if ($length < 30 || $length > 60) {
            return $this->check('meta_title', 'meta_title', 15, 8, 'warning', 'seo_analysis_meta_title_length', ['count' => $length]);
        }

        return $this->check('meta_title', 'meta_title', 15, 15, 'pass', 'seo_analysis_meta_title_good', ['count' => $length]);
    }

    /** @return array<string, mixed> */
    private function metaDescriptionCheck(string $description): array
    {
        $length = $this->length($description);
        if ($length === 0) {
            return $this->check('meta_description', 'meta_description', 15, 0, 'fail', 'seo_analysis_meta_description_missing');
        }
        if ($length < 110 || $length > 160) {
            return $this->check('meta_description', 'meta_description', 15, 8, 'warning', 'seo_analysis_meta_description_length', ['count' => $length]);
        }

        return $this->check('meta_description', 'meta_description', 15, 15, 'pass', 'seo_analysis_meta_description_good', ['count' => $length]);
    }

    /** @return array<string, mixed> */
    private function contentCheck(int $wordCount, string $entity): array
    {
        $target = $entity === 'post' ? 300 : 120;
        $minimum = $entity === 'post' ? 150 : 60;
        if ($wordCount >= $target) {
            return $this->check('content', 'content', 20, 20, 'pass', 'seo_analysis_content_good', ['count' => $wordCount]);
        }
        if ($wordCount >= $minimum) {
            return $this->check('content', 'content', 20, 10, 'warning', 'seo_analysis_content_short', ['count' => $wordCount, 'target' => $target]);
        }

        return $this->check('content', 'content', 20, 0, 'fail', 'seo_analysis_content_thin', ['count' => $wordCount, 'target' => $minimum]);
    }

    /** @param array<string, int|bool> $metrics @return array<string, mixed> */
    private function headingCheck(array $metrics): array
    {
        if ((int) $metrics['heading_count'] === 0) {
            return $this->check('headings', 'content', 10, 0, 'fail', 'seo_analysis_headings_missing');
        }
        if ((bool) $metrics['has_h1'] || (bool) $metrics['heading_jump']) {
            return $this->check('headings', 'content', 10, 5, 'warning', 'seo_analysis_headings_improve');
        }

        return $this->check('headings', 'content', 10, 10, 'pass', 'seo_analysis_headings_good');
    }

    /** @return array<string, mixed> */
    private function linkCheck(int $linkCount): array
    {
        return $linkCount > 0
            ? $this->check('links', 'content', 5, 5, 'pass', 'seo_analysis_links_good', ['count' => $linkCount])
            : $this->check('links', 'content', 5, 0, 'warning', 'seo_analysis_links_missing');
    }

    /** @return array<string, mixed> */
    private function mediaCheck(int $imageCount, int $missingAltCount): array
    {
        if ($imageCount === 0) {
            return $this->check('media_alt', 'content', 5, 5, 'pass', 'seo_analysis_media_none');
        }
        if ($missingAltCount > 0) {
            return $this->check('media_alt', 'content', 5, 0, 'fail', 'seo_analysis_media_alt_missing', ['count' => $missingAltCount]);
        }

        return $this->check('media_alt', 'content', 5, 5, 'pass', 'seo_analysis_media_alt_good', ['count' => $imageCount]);
    }

    /** @return array<string, mixed> */
    private function indexabilityCheck(string $status, string $robots): array
    {
        if (str_contains($robots, 'noindex')) {
            return $this->check('indexability', 'status', 10, 0, 'fail', 'seo_analysis_indexability_blocked');
        }
        if ($status !== 'published') {
            return $this->check('indexability', 'status', 10, 5, 'warning', 'seo_analysis_indexability_draft');
        }

        return $this->check('indexability', 'status', 10, 10, 'pass', 'seo_analysis_indexability_good');
    }

    /**
     * @param array<string, int|string> $params
     * @return array<string, mixed>
     */
    private function check(
        string $id,
        string $field,
        int $weight,
        int $earned,
        string $state,
        string $messageKey,
        array $params = []
    ): array {
        return compact('id', 'field', 'weight', 'earned', 'state', 'messageKey', 'params');
    }

    /** @return array{word_count:int,heading_count:int,link_count:int,image_count:int,missing_alt_count:int,has_h1:bool,heading_jump:bool} */
    private function contentMetrics(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="flatcms-seo-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return [
                'word_count' => $this->wordCount(strip_tags($html)),
                'heading_count' => 0,
                'link_count' => 0,
                'image_count' => 0,
                'missing_alt_count' => 0,
                'has_h1' => false,
                'heading_jump' => false,
            ];
        }

        $xpath = new DOMXPath($document);
        $headings = $xpath->query('//*[@id="flatcms-seo-root"]//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]');
        $links = $xpath->query('//*[@id="flatcms-seo-root"]//a[@href]');
        $images = $xpath->query('//*[@id="flatcms-seo-root"]//img');
        $hasH1 = false;
        $headingJump = false;
        $previousLevel = 0;

        foreach ($headings ?: [] as $heading) {
            $level = (int) substr(strtolower($heading->nodeName), 1);
            $hasH1 = $hasH1 || $level === 1;
            if ($previousLevel > 0 && $level > $previousLevel + 1) {
                $headingJump = true;
            }
            $previousLevel = $level;
        }

        $missingAlt = 0;
        foreach ($images ?: [] as $image) {
            if (!$image instanceof DOMElement || trim($image->getAttribute('alt')) === '') {
                $missingAlt++;
            }
        }

        $root = $document->getElementById('flatcms-seo-root');

        return [
            'word_count' => $this->wordCount($root?->textContent ?? strip_tags($html)),
            'heading_count' => $headings?->length ?? 0,
            'link_count' => $links?->length ?? 0,
            'image_count' => $images?->length ?? 0,
            'missing_alt_count' => $missingAlt,
            'has_h1' => $hasH1,
            'heading_jump' => $headingJump,
        ];
    }

    private function plainText(string $value, int $maxLength): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
        return $this->slice($text, $maxLength);
    }

    private function wordCount(string $value): int
    {
        preg_match_all('/[\p{L}\p{N}]+(?:[’\'_-][\p{L}\p{N}]+)*/u', $value, $matches);
        return count($matches[0] ?? []);
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function slice(string $value, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
    }
}
