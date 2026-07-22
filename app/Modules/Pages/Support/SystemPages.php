<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Pages\Support;

use App\Core\ContentDocumentStore;
use App\Core\FlatFile;
use App\Core\I18n;

final class SystemPages
{
    public const LEGAL_NOTICE_KEY = 'legal_notice';
    public const PRIVACY_POLICY_KEY = 'privacy_policy';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(callable $translate): array
    {
        return [
            self::LEGAL_NOTICE_KEY => [
                'key' => self::LEGAL_NOTICE_KEY,
                'slug' => 'legal-notice',
                'slug_aliases' => ['legal-notice', 'mentions-legales'],
                'title' => (string) $translate('system_page_legal_notice_title'),
                'lead' => (string) $translate('system_page_legal_notice_lead'),
                'content' => (string) $translate('system_page_legal_notice_content'),
                'meta_title' => (string) $translate('system_page_legal_notice_meta_title'),
                'meta_description' => (string) $translate('system_page_legal_notice_meta_description'),
            ],
            self::PRIVACY_POLICY_KEY => [
                'key' => self::PRIVACY_POLICY_KEY,
                'slug' => 'privacy-policy',
                'slug_aliases' => ['privacy-policy', 'politique-confidentialite', 'politique-de-confidentialite'],
                'title' => (string) $translate('system_page_privacy_policy_title'),
                'lead' => (string) $translate('system_page_privacy_policy_lead'),
                'content' => (string) $translate('system_page_privacy_policy_content'),
                'meta_title' => (string) $translate('system_page_privacy_policy_meta_title'),
                'meta_description' => (string) $translate('system_page_privacy_policy_meta_description'),
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function ensureRequired(ContentDocumentStore $pages, callable $translate): array
    {
        $definitions = self::definitions($translate);
        $resolved = [];

        foreach ($definitions as $definition) {
            $key = (string) ($definition['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $page = self::findExisting($pages, $key, $definition);
            if (!is_array($page)) {
                $page = $pages->create(self::buildCreatePayload(
                    $definition,
                    $key,
                    self::definitionLocale()
                ));

                $pageId = trim((string) ($page['id'] ?? ''));
                if ($pageId !== '') {
                    $updatePayload = self::buildUpdatePayload($pages, $page, $definition, $key);
                    if ($updatePayload !== []) {
                        $updated = $pages->update($pageId, $updatePayload);
                        if (is_array($updated)) {
                            $page = $updated;
                        }
                    }
                }
            } else {
                $pageId = trim((string) ($page['id'] ?? ''));
                if ($pageId !== '') {
                    $updatePayload = self::buildUpdatePayload($pages, $page, $definition, $key);

                    if ($updatePayload !== []) {
                        $updated = $pages->update($pageId, $updatePayload);
                        if (is_array($updated)) {
                            $page = $updated;
                        }
                    }
                }
            }

            if (is_array($page)) {
                $resolved[$key] = $page;
            }
        }

        return $resolved;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function findByKey(ContentDocumentStore $pages, string $key): ?array
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        $allPages = $pages->all();
        foreach ($allPages as $page) {
            if (!is_array($page)) {
                continue;
            }
            if ((string) ($page['system_page_key'] ?? '') === $key) {
                return $page;
            }
        }

        $aliases = self::legacyAliases($key);
        if ($aliases === []) {
            return null;
        }

        foreach ($allPages as $page) {
            if (!is_array($page)) {
                continue;
            }
            $slug = trim((string) ($page['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            if (in_array($slug, $aliases, true)) {
                return $page;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $page
     */
    public static function isRequiredPage(array $page): bool
    {
        $key = trim((string) ($page['system_page_key'] ?? ''));
        if (in_array($key, [self::LEGAL_NOTICE_KEY, self::PRIVACY_POLICY_KEY], true)) {
            return true;
        }

        if (!empty($page['system_required'])) {
            return true;
        }

        $slug = trim((string) ($page['slug'] ?? ''));
        if ($slug === '') {
            return false;
        }

        return in_array($slug, array_merge(
            self::legacyAliases(self::LEGAL_NOTICE_KEY),
            self::legacyAliases(self::PRIVACY_POLICY_KEY)
        ), true);
    }

    /**
     * @param array<string,mixed> $page
     */
    public static function frontendUrl(array $page): string
    {
        $slug = trim((string) ($page['slug'] ?? ''));
        if ($slug === '') {
            return '';
        }

        return url('/page/' . $slug);
    }

    /**
     * @param array<string,mixed> $page
     */
    public static function adminEditUrl(array $page): string
    {
        $id = trim((string) ($page['id'] ?? ''));
        if ($id === '') {
            return '';
        }

        if (self::isRequiredPage($page)) {
            return url('/admin/pages/' . $id . '/edit');
        }

        return url('/admin/pages/' . $id . '/edit');
    }

    public static function keyForSlug(string $slug): ?string
    {
        $normalized = trim($slug);
        if ($normalized === '') {
            return null;
        }

        foreach ([self::LEGAL_NOTICE_KEY, self::PRIVACY_POLICY_KEY] as $key) {
            if (in_array($normalized, self::legacyAliases($key), true)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $definition
     * @return array<string,mixed>|null
     */
    private static function findExisting(ContentDocumentStore $pages, string $key, array $definition): ?array
    {
        $found = self::findByKey($pages, $key);
        if (is_array($found)) {
            return $found;
        }

        $slug = trim((string) ($definition['slug'] ?? ''));
        if ($slug !== '') {
            $bySlug = $pages->findBy('slug', $slug);
            if (is_array($bySlug)) {
                return $bySlug;
            }
        }

        return null;
    }

    /**
     * @return array<int,string>
     */
    private static function legacyAliases(string $key): array
    {
        if ($key === self::LEGAL_NOTICE_KEY) {
            return ['legal-notice', 'mentions-legales'];
        }

        if ($key === self::PRIVACY_POLICY_KEY) {
            return ['privacy-policy', 'politique-confidentialite', 'politique-de-confidentialite'];
        }

        return [];
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,mixed>|null $builderState
     * @return array<string,mixed>
     */
    private static function buildCreatePayload(
        array $definition,
        string $key,
        string $sourceLocale
    ): array
    {
        $payload = [
            'title' => (string) ($definition['title'] ?? ''),
            'slug' => (string) ($definition['slug'] ?? ''),
            'content' => (string) ($definition['content'] ?? ''),
            'meta_title' => (string) ($definition['meta_title'] ?? ''),
            'meta_description' => (string) ($definition['meta_description'] ?? ''),
            'status' => 'published',
            'system_page_key' => $key,
            'system_required' => true,
            'author_id' => '',
            'locale' => $sourceLocale,
            'source_locale' => $sourceLocale,
            'editor_mode' => 'classic',
        ];

        return $payload;
    }

    /**
     * @param array<string,mixed> $page
     * @param array<string,mixed> $definition
     * @param array<string,mixed>|null $builderState
     * @return array<string,mixed>
     */
    private static function buildUpdatePayload(ContentDocumentStore $pages, array $page, array $definition, string $key): array
    {
        $updatePayload = [];

        if ((string) ($page['system_page_key'] ?? '') !== $key) {
            $updatePayload['system_page_key'] = $key;
        }

        if (empty($page['system_required'])) {
            $updatePayload['system_required'] = true;
        }

        $canonicalSlug = (string) ($definition['slug'] ?? '');
        if ($canonicalSlug !== '' && (string) ($page['slug'] ?? '') !== $canonicalSlug) {
            $updatePayload['slug'] = $canonicalSlug;
        }

        if ((string) ($page['status'] ?? 'draft') !== 'published') {
            $updatePayload['status'] = 'published';
        }

        $pageId = trim((string) ($page['id'] ?? ''));
        if ($pageId !== '' && trim((string) ($page['translation_group'] ?? '')) === '') {
            $updatePayload['translation_group'] = $pageId;
        }

        $sourceLocale = self::inferSourceLocale($pages, $page);
        if ($sourceLocale !== '') {
            if (trim((string) ($page['locale'] ?? '')) === '') {
                $updatePayload['locale'] = $sourceLocale;
            }

            if (trim((string) ($page['source_locale'] ?? '')) === '') {
                $updatePayload['source_locale'] = $sourceLocale;
            }
        }

        if (trim((string) ($page['title'] ?? '')) === '') {
            $updatePayload['title'] = (string) ($definition['title'] ?? '');
        }

        if (trim((string) ($page['content'] ?? '')) === '') {
            $updatePayload['content'] = (string) ($definition['content'] ?? '');
        }

        if (trim((string) ($page['meta_title'] ?? '')) === '') {
            $updatePayload['meta_title'] = (string) ($definition['meta_title'] ?? '');
        }

        if (trim((string) ($page['meta_description'] ?? '')) === '') {
            $updatePayload['meta_description'] = (string) ($definition['meta_description'] ?? '');
        }

        return $updatePayload;
    }

    private static function inferSourceLocale(ContentDocumentStore $pages, array $page): string
    {
        $sourceLocale = self::normalizeLocale((string) ($page['source_locale'] ?? ''));
        if ($sourceLocale !== '') {
            return $sourceLocale;
        }

        $locale = self::normalizeLocale((string) ($page['locale'] ?? ''));
        if ($locale !== '') {
            return $locale;
        }

        $group = trim((string) ($page['translation_group'] ?? $page['id'] ?? ''));
        if ($group !== '') {
            foreach ($pages->all() as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }

                if (trim((string) ($candidate['translation_group'] ?? '')) !== $group) {
                    continue;
                }

                $candidateSourceLocale = self::normalizeLocale((string) ($candidate['source_locale'] ?? ''));
                if ($candidateSourceLocale !== '') {
                    return $candidateSourceLocale;
                }
            }

            foreach ($pages->all() as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }

                if (trim((string) ($candidate['translation_group'] ?? '')) !== $group) {
                    continue;
                }

                $candidateLocale = self::normalizeLocale((string) ($candidate['locale'] ?? ''));
                if ($candidateLocale !== '') {
                    return $candidateLocale;
                }
            }
        }

        return self::definitionLocale();
    }

    private static function definitionLocale(): string
    {
        $currentLocale = self::normalizeLocale(I18n::getLocale());
        if ($currentLocale !== '') {
            return $currentLocale;
        }

        $settings = FlatFile::settings();
        $defaultLocale = self::normalizeLocale((string) ($settings['default_language'] ?? ''));
        if ($defaultLocale !== '') {
            return $defaultLocale;
        }

        $supported = I18n::getSupportedLocales();
        foreach ($supported as $locale) {
            $normalized = self::normalizeLocale((string) $locale);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return 'fr-FR';
    }

    private static function normalizeLocale(string $locale): string
    {
        $value = trim($locale);
        if ($value === '') {
            return '';
        }

        foreach (I18n::getSupportedLocales() as $candidate) {
            if (strcasecmp($candidate, $value) === 0) {
                return $candidate;
            }
        }

        $prefix = strtolower(substr($value, 0, 2));
        if ($prefix === '') {
            return '';
        }

        foreach (I18n::getSupportedLocales() as $candidate) {
            if (strtolower(substr((string) $candidate, 0, 2)) === $prefix) {
                return (string) $candidate;
            }
        }

        return '';
    }
}
