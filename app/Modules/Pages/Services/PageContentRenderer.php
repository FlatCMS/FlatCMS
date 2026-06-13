<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Pages\Services;

final class PageContentRenderer
{
    private PageTranslationService $translations;

    public function __construct(?PageTranslationService $translations = null)
    {
        $this->translations = $translations ?? new PageTranslationService();
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    public function preparePage(array $page, string $sourcePath, string $locale): array
    {
        $resolved = $this->resolveExternalRenderer($page, $sourcePath, $locale);
        if (is_array($resolved)) {
            return $resolved;
        }

        $page['render_mode'] = 'classic';
        return $this->applyShortcodes($page, $sourcePath, $locale);
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>|null
     */
    private function resolveExternalRenderer(array $page, string $sourcePath, string $locale): ?array
    {
        $payload = [
            'domain' => 'pages',
            'entity_type' => 'page',
            'entity' => $page,
            'locale' => $locale,
            'source_path' => $sourcePath,
            'source_url' => url($sourcePath),
            'fallback_render_mode' => 'classic',
        ];

        foreach (hook_run('content.renderer.resolve', $payload) as $result) {
            $resolved = $this->normalizeResolvedRendererResult($result, $page);
            if (is_array($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>|null
     */
    private function normalizeResolvedRendererResult(mixed $result, array $page): ?array
    {
        if (!is_array($result) || (($result['handled'] ?? false) !== true)) {
            return null;
        }

        $resolved = $result['entity'] ?? $result['page'] ?? null;
        if (!is_array($resolved)) {
            return null;
        }

        $resolved['render_mode'] = $this->normalizeResolvedRenderMode($result, $resolved);
        $resolved['renderer_provider'] = trim((string) ($result['provider'] ?? $result['renderer'] ?? ''));

        if (!array_key_exists('id', $resolved) && array_key_exists('id', $page)) {
            $resolved['id'] = $page['id'];
        }

        if (!array_key_exists('slug', $resolved) && array_key_exists('slug', $page)) {
            $resolved['slug'] = $page['slug'];
        }

        if (array_key_exists('content', $resolved)) {
            $resolved['content'] = $this->normalizeContentMediaUrls((string) ($resolved['content'] ?? ''));
        }

        return $resolved;
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $resolved
     */
    private function normalizeResolvedRenderMode(array $result, array $resolved): string
    {
        $renderMode = trim((string) ($result['render_mode'] ?? $resolved['render_mode'] ?? ''));

        return $renderMode !== '' ? $renderMode : 'extension';
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private function applyShortcodes(array $page, string $sourcePath, string $locale): array
    {
        $content = (string) ($page['content'] ?? '');
        if ($content === '') {
            return $page;
        }

        $page['content'] = flatcms_render_shortcodes($content, [
            'source_url' => url($sourcePath),
            'locale' => $locale,
        ]);
        $page['content'] = $this->normalizeContentMediaUrls((string) $page['content']);

        return $page;
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

}
