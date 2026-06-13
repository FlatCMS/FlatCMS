<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\StructuredData;

use App\Core\FlatFile;
use App\Services\StructuredData\Contracts\StructuredDataProviderInterface;
use App\Services\StructuredData\Providers\PageSchemaProvider;
use App\Services\StructuredData\Providers\PostSchemaProvider;
use App\Services\StructuredData\Providers\SiteSchemaProvider;

class StructuredDataManager
{
    /** @var array<int, StructuredDataProviderInterface> */
    private array $providers;

    /**
     * @param array<int, StructuredDataProviderInterface>|null $providers
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?? [
            new SiteSchemaProvider(),
            new PageSchemaProvider(),
            new PostSchemaProvider(),
        ];
    }

    /**
     * @param array<string, mixed> $viewData
     * @return array<string, mixed>
     */
    public function graphForView(array $viewData): array
    {
        $context = $this->buildContext($viewData);
        $builder = new SchemaGraphBuilder();
        foreach ($this->providers as $provider) {
            $builder->addNodes($provider->provide($context));
        }
        return $builder->build();
    }

    /**
     * @param array<string, mixed> $viewData
     */
    public function payloadForView(array $viewData): string
    {
        $graph = $this->graphForView($viewData);
        if ($graph === []) {
            return '';
        }

        $json = json_encode(
            $graph,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRETTY_PRINT
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );

        return is_string($json) ? $json : '';
    }

    /**
     * @param array<string, mixed> $viewData
     * @return array<string, mixed>
     */
    private function buildContext(array $viewData): array
    {
        $settings = is_array($viewData['settings'] ?? null) ? $viewData['settings'] : [];
        $locale = trim((string) ($viewData['locale'] ?? locale()));
        $page = is_array($viewData['page'] ?? null) ? $viewData['page'] : null;
        $post = is_array($viewData['post'] ?? null) ? $viewData['post'] : null;
        $currentUrl = $this->currentUrl();
        $siteUrl = $this->siteUrl($settings);
        $siteName = trim((string) ($settings['site_name'] ?? ''));
        $siteDescription = trim((string) ($settings['site_description'] ?? $settings['meta_description'] ?? ''));

        $metaDescription = trim((string) ($settings['meta_description'] ?? ''));
        if ($page !== null) {
            $metaDescription = trim((string) ($page['meta_description'] ?? $metaDescription));
        }
        if ($post !== null) {
            $metaDescription = trim((string) ($post['meta_description'] ?? $post['excerpt'] ?? $metaDescription));
        }

        $pageTitle = trim((string) ($viewData['pageTitle'] ?? $page['meta_title'] ?? $page['title'] ?? $post['meta_title'] ?? $post['title'] ?? $siteName));
        $siteLogo = trim((string) ($settings['site_logo'] ?? ''));
        $siteLogoUrl = $siteLogo !== '' ? $this->absoluteAssetUrl(site_media_url($siteLogo), $siteUrl) : '';
        $postImage = '';
        if ($post !== null) {
            $featuredImage = trim((string) ($post['featured_image'] ?? ''));
            if ($featuredImage !== '') {
                $postImage = $this->absoluteAssetUrl(site_media_url($featuredImage), $siteUrl);
            }
        }

        $viewType = 'generic';
        if ($post !== null) {
            $viewType = 'post_show';
        } elseif ($page !== null) {
            $pageSlug = trim((string) ($page['slug'] ?? ''));
            $homePath = parse_url($this->homeUrl($locale), PHP_URL_PATH) ?: '';
            $currentPath = parse_url($currentUrl, PHP_URL_PATH) ?: '';
            if ($currentPath === $homePath || $pageSlug === '') {
                $viewType = 'home';
            } elseif ($pageSlug === 'contact') {
                $viewType = 'contact';
            } else {
                $viewType = 'page_show';
            }
        } elseif (str_contains($currentUrl, '/blog')) {
            $viewType = 'posts_index';
        } elseif (preg_match('~/contact/?(?:\\?.*)?$~', $currentUrl) === 1) {
            $viewType = 'contact';
        }

        return [
            'settings' => $settings,
            'page' => $page,
            'post' => $post,
            'post_categories' => is_array($viewData['postCategories'] ?? null) ? $viewData['postCategories'] : [],
            'current_category' => is_array($viewData['currentCategory'] ?? null) ? $viewData['currentCategory'] : null,
            'locale' => $locale,
            'content_locale' => $page['locale'] ?? $post['locale'] ?? $locale,
            'view_type' => $viewType,
            'current_url' => $currentUrl,
            'site_url' => $siteUrl,
            'home_url' => $this->absoluteUrl($this->homePath($locale), $siteUrl),
            'blog_url' => $this->absoluteUrl('/' . $locale . '/blog', $siteUrl),
            'site_name' => $siteName,
            'site_description' => $siteDescription,
            'site_logo_url' => $siteLogoUrl,
            'page_title' => $pageTitle,
            'meta_description' => $metaDescription,
            'post_image_url' => $postImage,
        ];
    }

    private function siteUrl(array $settings): string
    {
        $configured = trim((string) ($settings['site_url'] ?? ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $scheme = $this->isSecureRequest() ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return rtrim($scheme . '://' . $host, '/');
    }

    private function currentUrl(): string
    {
        $scheme = $this->isSecureRequest() ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $uri = trim((string) ($_SERVER['REQUEST_URI'] ?? '/'));
        if ($uri === '') {
            $uri = '/';
        }
        return $scheme . '://' . $host . $uri;
    }

    private function homeUrl(string $locale): string
    {
        return $this->absoluteUrl($this->homePath($locale), $this->siteUrl(FlatFile::settings()));
    }

    private function homePath(string $locale): string
    {
        $locale = trim($locale, '/');
        return $locale !== '' ? '/' . $locale : '/';
    }

    private function absoluteUrl(string $path, string $siteUrl): string
    {
        $normalizedPath = '/' . ltrim($path, '/');
        return rtrim($siteUrl, '/') . $normalizedPath;
    }

    private function absoluteAssetUrl(string $value, string $siteUrl): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('~^(https?:)?//~i', $value) === 1) {
            if (str_starts_with($value, '//')) {
                $scheme = $this->isSecureRequest() ? 'https:' : 'http:';
                return $scheme . $value;
            }

            return $value;
        }

        return $this->absoluteUrl($value, $siteUrl);
    }

    private function isSecureRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto !== '' && in_array('https', array_map('trim', explode(',', $forwardedProto)), true)) {
            return true;
        }

        return strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https';
    }
}
