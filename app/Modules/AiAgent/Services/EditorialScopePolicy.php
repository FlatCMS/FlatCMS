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

use DOMDocument;
use DOMElement;
use DOMNode;

final class EditorialScopePolicy
{
    private const MAX_REWRITE_CHARACTERS = 8000;

    /** @var array<string, true> */
    private const ALLOWED_TAGS = [
        'p' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'ul' => true,
        'ol' => true,
        'li' => true,
        'strong' => true,
        'em' => true,
        'b' => true,
        'i' => true,
        'u' => true,
        's' => true,
        'blockquote' => true,
        'a' => true,
        'br' => true,
        'hr' => true,
        'code' => true,
        'pre' => true,
    ];

    public function isLayoutRequest(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));
        if ($normalized === '') {
            return false;
        }

        return preg_match(
            '/(?:\b(?:hero|heroes|banner|banni[eè]re|cards?|cartes?|grid|grille|columns?|colonnes?|responsive|breakpoints?|carousel|overlay|offcanvas|drag(?:\s*(?:and|&))?\s*drop|glisser[- ]d[ée]poser|positionnement|superpos(?:er|ition)|padding|margins?|marges?|spacing|espacement)\b|\b50\s*\/\s*50\b|mise\s+en\s+page|page\s+layout|visual\s+layout|layout\s+visual|zweispaltig|mehrspaltig|dise[nñ]o\s+de\s+p[aá]gina|impaginazione|disposi[cç][aã]o)/iu',
            $normalized
        ) === 1;
    }

    public function isProtectedContent(string $html): bool
    {
        $content = trim($html);
        if ($content === '') {
            return false;
        }

        if (preg_match('/<\s*\/?\s*(?:address|article|aside|button|details|div|figure|figcaption|footer|form|header|img|main|nav|picture|section|summary|table|template|video)\b/i', $content) === 1) {
            return true;
        }

        if (preg_match('/\s(?:class|id|style|data-[a-z0-9_-]+)\s*=/i', $content) === 1) {
            return true;
        }

        return preg_match('/\[[a-z][a-z0-9_-]*(?:\s[^\]]*)?\]/i', $content) === 1;
    }

    public function isTooLargeForRewrite(string $html): bool
    {
        return mb_strlen($html) > self::MAX_REWRITE_CHARACTERS;
    }

    public function sanitizeGeneratedContent(string $html): string
    {
        $sanitized = function_exists('flatcms_sanitize_editor_html')
            ? flatcms_sanitize_editor_html($html)
            : $html;
        $sanitized = trim($sanitized);

        if ($sanitized === '' || !$this->containsOnlyEditorialMarkup($sanitized)) {
            return '';
        }

        return $sanitized;
    }

    private function containsOnlyEditorialMarkup(string $html): bool
    {
        if (preg_match('/<!--|<!DOCTYPE|\[[a-z][a-z0-9_-]*(?:\s[^\]]*)?\]/i', $html) === 1) {
            return false;
        }

        if (!class_exists(DOMDocument::class)) {
            return $this->containsOnlyEditorialMarkupFallback($html);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="flatcms-ai-editorial-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return false;
        }

        $root = $document->getElementById('flatcms-ai-editorial-root');
        if (!$root instanceof DOMElement) {
            return false;
        }

        return $this->validateNodeChildren($root);
    }

    private function validateNodeChildren(DOMNode $parent): bool
    {
        foreach ($parent->childNodes as $node) {
            if ($node->nodeType === XML_COMMENT_NODE || $node->nodeType === XML_DOCUMENT_TYPE_NODE) {
                return false;
            }

            if (!$node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (!isset(self::ALLOWED_TAGS[$tag])) {
                return false;
            }

            foreach ($node->attributes as $attribute) {
                $name = strtolower($attribute->nodeName);
                if ($tag !== 'a' || !in_array($name, ['href', 'title'], true)) {
                    return false;
                }
            }

            if (!$this->validateNodeChildren($node)) {
                return false;
            }
        }

        return true;
    }

    private function containsOnlyEditorialMarkupFallback(string $html): bool
    {
        if (preg_match_all('/<\s*\/?\s*([a-z0-9]+)\b([^>]*)>/i', $html, $matches, PREG_SET_ORDER) === false) {
            return false;
        }

        foreach ($matches as $match) {
            $tag = strtolower((string) ($match[1] ?? ''));
            if (!isset(self::ALLOWED_TAGS[$tag])) {
                return false;
            }

            $attributes = trim((string) ($match[2] ?? ''), " \t\n\r\0\x0B/");
            if ($attributes === '') {
                continue;
            }

            if ($tag !== 'a' || preg_match('/^(?:\s*(?:href|title)\s*=\s*(?:"[^"]*"|\'[^\']*\'))+\s*$/i', $attributes) !== 1) {
                return false;
            }
        }

        return true;
    }
}
