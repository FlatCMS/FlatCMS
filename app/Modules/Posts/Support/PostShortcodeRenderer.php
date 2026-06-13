<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Posts\Support;

use App\Core\I18n;
use App\Modules\Posts\Services\PostTranslationService;

final class PostShortcodeRenderer
{
    /**
     * @param array<string, string> $attributes
     * @param array<string, mixed> $context
     */
    public static function render(array $attributes = [], array $context = []): string
    {
        I18n::load('Posts');

        $translations = new PostTranslationService();
        $id = trim((string) ($attributes['id'] ?? ''));
        $slug = trim((string) ($attributes['slug'] ?? ''));
        $postLocale = $translations->normalizeLocale(trim((string) ($context['locale'] ?? I18n::getLocale())));
        if ($postLocale === '') {
            $postLocale = $translations->defaultLocale();
        }

        $post = null;
        if ($id !== '') {
            $candidate = $translations->find($id);
            if (is_array($candidate)) {
                $localized = $translations->findByTranslationGroupAndLocale(
                    (string) ($candidate['translation_group'] ?? ''),
                    $postLocale,
                    true
                );
                $post = is_array($localized) ? $localized : $candidate;
            }
        } elseif ($slug !== '') {
            $candidate = $translations->findBySlugAndLocale($slug, $postLocale, true);
            if (!is_array($candidate)) {
                $candidate = $translations->findBySlug($slug, true);
            }
            if (is_array($candidate)) {
                $localized = $translations->findByTranslationGroupAndLocale(
                    (string) ($candidate['translation_group'] ?? ''),
                    $postLocale,
                    true
                );
                $post = is_array($localized) ? $localized : $candidate;
            }
        }

        if (!is_array($post) || $translations->resolveEffectiveStatus($post) !== 'published') {
            return '';
        }

        $postId = trim((string) ($post['id'] ?? ''));
        $postSlug = trim((string) ($post['slug'] ?? ''));
        $stackKey = $postId !== '' ? 'post:id:' . $postId : 'post:slug:' . $postSlug;

        $stack = $context['_shortcode_stack'] ?? [];
        if (!is_array($stack)) {
            $stack = [];
        }
        if (in_array($stackKey, $stack, true)) {
            return '';
        }

        $depth = (int) ($context['_shortcode_depth'] ?? 0);
        if ($depth >= 8) {
            return '';
        }

        $nextContext = $context;
        $nextContext['_shortcode_depth'] = $depth + 1;
        $nextContext['_shortcode_stack'] = $stack;
        $nextContext['_shortcode_stack'][] = $stackKey;

        $sourceUrl = trim((string) ($context['source_url'] ?? ''));
        if ($sourceUrl === '' && function_exists('flatcms_current_source_url')) {
            $sourceUrl = flatcms_current_source_url();
        }
        $nextContext['source_url'] = $sourceUrl;

        $mode = strtolower(trim((string) ($attributes['mode'] ?? 'content')));
        if (!in_array($mode, ['content', 'excerpt'], true)) {
            $mode = 'content';
        }

        $titleAttr = strtolower(trim((string) ($attributes['title'] ?? '1')));
        $showTitle = !in_array($titleAttr, ['0', 'false', 'no', 'off'], true);

        $title = trim((string) ($post['title'] ?? ''));
        $postUrl = $postSlug !== '' ? url('/' . $postLocale . '/blog/' . $postSlug) : '';

        $contentHtml = '';
        if ($mode === 'excerpt') {
            $excerpt = trim((string) ($post['excerpt'] ?? ''));
            if ($excerpt === '') {
                $fallback = trim(strip_tags((string) ($post['content'] ?? '')));
                $excerpt = str_limit($fallback, 240);
            }
            if ($excerpt !== '') {
                $contentHtml = '<p>' . e($excerpt) . '</p>';
            }
        } else {
            $rawContent = (string) ($post['content'] ?? '');
            if ($rawContent !== '' && function_exists('flatcms_render_shortcodes')) {
                $contentHtml = flatcms_render_shortcodes($rawContent, $nextContext);
            }
        }

        if ($contentHtml === '' && !$showTitle) {
            return '';
        }

        $html = '<article class="flatcms-shortcode-post">';
        if ($showTitle && $title !== '') {
            if ($postUrl !== '') {
                $html .= '<h3 class="flatcms-shortcode-post-title"><a href="' . e($postUrl) . '">' . e($title) . '</a></h3>';
            } else {
                $html .= '<h3 class="flatcms-shortcode-post-title">' . e($title) . '</h3>';
            }
        }
        if ($contentHtml !== '') {
            $html .= '<div class="flatcms-shortcode-post-content">' . $contentHtml . '</div>';
        }
        $html .= '</article>';

        return $html;
    }
}
