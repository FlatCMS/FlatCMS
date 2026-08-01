<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\Seo;

final class SeoHeadRenderer
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function render(array $metadata): string
    {
        $lines = [];
        $robots = trim((string) ($metadata['robots'] ?? ''));
        $canonical = trim((string) ($metadata['canonical_url'] ?? ''));
        $alternates = is_array($metadata['alternates'] ?? null) ? $metadata['alternates'] : [];
        $xDefault = trim((string) ($metadata['x_default_url'] ?? ''));

        if ($robots !== '') {
            $lines[] = $this->meta('name', 'robots', $robots);
        }
        if ($canonical !== '') {
            $lines[] = $this->link('canonical', $canonical);
        }

        foreach ($alternates as $locale => $url) {
            $normalizedLocale = trim((string) $locale);
            $normalizedUrl = trim((string) $url);
            if ($normalizedLocale === '' || $normalizedUrl === '') {
                continue;
            }
            $lines[] = '<link rel="alternate" hreflang="' . $this->escape($normalizedLocale)
                . '" href="' . $this->escape($normalizedUrl) . '">';
        }
        if ($xDefault !== '') {
            $lines[] = '<link rel="alternate" hreflang="x-default" href="' . $this->escape($xDefault) . '">';
        }

        $openGraph = is_array($metadata['open_graph'] ?? null) ? $metadata['open_graph'] : [];
        foreach (['title', 'description', 'type', 'url', 'site_name', 'locale', 'image'] as $key) {
            $value = trim((string) ($openGraph[$key] ?? ''));
            if ($value !== '') {
                $lines[] = $this->meta('property', 'og:' . $key, $value);
            }
        }
        foreach (($openGraph['locale_alternates'] ?? []) as $locale) {
            $value = trim((string) $locale);
            if ($value !== '') {
                $lines[] = $this->meta('property', 'og:locale:alternate', $value);
            }
        }
        foreach (['published_time', 'modified_time'] as $key) {
            $value = trim((string) ($openGraph[$key] ?? ''));
            if ($value !== '') {
                $lines[] = $this->meta('property', 'article:' . $key, $value);
            }
        }

        $twitter = is_array($metadata['twitter'] ?? null) ? $metadata['twitter'] : [];
        foreach (['card', 'title', 'description', 'image'] as $key) {
            $value = trim((string) ($twitter[$key] ?? ''));
            if ($value !== '') {
                $lines[] = $this->meta('name', 'twitter:' . $key, $value);
            }
        }

        return implode(PHP_EOL, $lines);
    }

    private function meta(string $attribute, string $key, string $value): string
    {
        return '<meta ' . $attribute . '="' . $this->escape($key) . '" content="' . $this->escape($value) . '">';
    }

    private function link(string $rel, string $href): string
    {
        return '<link rel="' . $this->escape($rel) . '" href="' . $this->escape($href) . '">';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
