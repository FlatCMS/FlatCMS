<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Settings\Services;

use App\Core\I18n;

final class IntegrationsDocumentationService
{
    private const DOC_LOCALE_FALLBACK = 'en-US';

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $catalogCache = [];

    /**
     * @return array<string, array<string, string>>
     */
    public function buildFieldHelpIndex(string $uiLocale): array
    {
        $docLocale = $this->resolveDocumentationLocale($uiLocale);
        $helpIndex = [];

        foreach ($this->topicDefinitions() as $topicKey => $topic) {
            $title = trim((string) __($topic['label_key'], 'Settings'));
            if ($title === '') {
                $title = $this->translateForLocale($docLocale, $topic['label_key']);
            }

            $summary = trim((string) __($topic['hint_key'], 'Settings'));
            if ($summary === '') {
                $summary = $this->translateForLocale($docLocale, $topic['hint_key']);
            }

            $helpIndex[$topicKey] = [
                'anchor' => $topic['anchor'],
                'title' => $title,
                'summary' => $summary,
                'link_url' => url('/admin/settings/help/integrations#' . $topic['anchor']),
                'aria_label' => __('integrations_help_icon_label', 'Settings', [
                    'field' => $title,
                ]),
            ];
        }

        return $helpIndex;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildHelpPage(string $uiLocale): array
    {
        $docLocale = $this->resolveDocumentationLocale($uiLocale);
        $groups = [];

        foreach ($this->groupDefinitions() as $groupKey => $group) {
            $topics = [];

            foreach ($this->topicDefinitions() as $topic) {
                if ($topic['group'] !== $groupKey) {
                    continue;
                }

                $topics[] = [
                    'anchor' => $topic['anchor'],
                    'title' => $this->translateForLocale($docLocale, $topic['label_key']),
                    'summary' => $this->translateForLocale($docLocale, $topic['hint_key']),
                    'env_key' => $topic['env_key'],
                    'example' => $this->resolveExample($topic, $docLocale),
                    'official_doc_url' => $this->resolveOfficialDocumentationUrl($topic, $docLocale),
                ];
            }

            $groups[] = [
                'id' => 'settings-docs-group-' . $groupKey,
                'icon' => $group['icon'],
                'title' => $this->translateForLocale($docLocale, $group['title_key']),
                'intro' => $this->translateForLocale($docLocale, $group['intro_key']),
                'topics' => $topics,
            ];
        }

        $isFallback = $this->normalizeLocale($docLocale) !== $this->normalizeLocale($uiLocale);

        return [
            'title' => __('integrations_docs_title', 'Settings'),
            'intro' => __('integrations_docs_intro', 'Settings'),
            'back_url' => url('/admin/settings#settings-integrations'),
            'back_label' => __('integrations_docs_back', 'Settings'),
            'doc_locale' => $docLocale,
            'fallback_notice' => $isFallback
                ? __('integrations_docs_locale_notice', 'Settings', ['locale' => $docLocale])
                : '',
            'groups' => $groups,
        ];
    }

    private function resolveDocumentationLocale(string $uiLocale): string
    {
        $normalized = $this->normalizeLocale($uiLocale);

        return match ($normalized) {
            'fr', 'fr-fr' => 'fr-FR',
            'en', 'en-us', 'en-gb' => 'en-US',
            default => self::DOC_LOCALE_FALLBACK,
        };
    }

    /**
     * @param array<string, mixed> $topic
     */
    private function resolveExample(array $topic, string $docLocale): string
    {
        $placeholderKey = trim((string) ($topic['placeholder_key'] ?? ''));
        if ($placeholderKey !== '') {
            return $this->translateForLocale($docLocale, $placeholderKey);
        }

        return trim((string) ($topic['example'] ?? ''));
    }

    private function translateForLocale(string $locale, string $key): string
    {
        $catalog = $this->loadCatalog($locale);
        $value = trim((string) ($catalog[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }

        if ($locale !== self::DOC_LOCALE_FALLBACK) {
            $fallbackCatalog = $this->loadCatalog(self::DOC_LOCALE_FALLBACK);
            $fallbackValue = trim((string) ($fallbackCatalog[$key] ?? ''));
            if ($fallbackValue !== '') {
                return $fallbackValue;
            }
        }

        return $key;
    }

    /**
     * @param array<string, mixed> $topic
     */
    private function resolveOfficialDocumentationUrl(array $topic, string $docLocale): string
    {
        $urlMap = $topic['official_doc_urls'] ?? null;
        if (is_array($urlMap)) {
            $canonicalLocale = $this->resolveDocumentationLocale($docLocale);
            $normalizedLocale = $this->normalizeLocale($canonicalLocale);

            $candidates = [
                $canonicalLocale,
                $normalizedLocale,
                substr($canonicalLocale, 0, 2),
                self::DOC_LOCALE_FALLBACK,
                $this->normalizeLocale(self::DOC_LOCALE_FALLBACK),
                'default',
            ];

            foreach ($candidates as $candidate) {
                $value = trim((string) ($urlMap[$candidate] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return trim((string) ($topic['official_doc_url'] ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function loadCatalog(string $locale): array
    {
        $canonicalLocale = $this->resolveDocumentationLocale($locale);
        if (isset($this->catalogCache[$canonicalLocale])) {
            return $this->catalogCache[$canonicalLocale];
        }

        $path = I18n::resolveTranslationPathForNamespace('Settings', $canonicalLocale);
        if (!is_file($path)) {
            $this->catalogCache[$canonicalLocale] = [];
            return [];
        }

        $content = file_get_contents($path);
        $data = is_string($content) ? json_decode($content, true) : null;
        $catalog = is_array($data) ? $data : [];

        $this->catalogCache[$canonicalLocale] = $catalog;

        return $catalog;
    }

    private function normalizeLocale(string $locale): string
    {
        return strtolower(trim($locale));
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function groupDefinitions(): array
    {
        return [
            'ai' => [
                'icon' => 'fas fa-brain',
                'title_key' => 'integrations_group_ai',
                'intro_key' => 'integrations_docs_group_ai_intro',
            ],
            'cookies' => [
                'icon' => 'fas fa-cookie-bite',
                'title_key' => 'integrations_group_cookies',
                'intro_key' => 'integrations_docs_group_cookies_intro',
            ],
            'analytics' => [
                'icon' => 'fas fa-chart-line',
                'title_key' => 'integrations_group_analytics',
                'intro_key' => 'integrations_docs_group_analytics_intro',
            ],
            'editors' => [
                'icon' => 'fas fa-pen-ruler',
                'title_key' => 'integrations_group_editors',
                'intro_key' => 'integrations_docs_group_editors_intro',
            ],
            'security' => [
                'icon' => 'fas fa-shield-alt',
                'title_key' => 'integrations_group_security',
                'intro_key' => 'integrations_docs_group_security_intro',
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function topicDefinitions(): array
    {
        return [
            'OPENAI_API_KEY' => [
                'group' => 'ai',
                'anchor' => 'openai-api-key',
                'env_key' => 'OPENAI_API_KEY',
                'label_key' => 'integrations_openai_api_key',
                'hint_key' => 'integrations_openai_api_key_hint',
                'placeholder_key' => 'integrations_openai_api_key_placeholder',
                'official_doc_url' => 'https://help.openai.com/en/articles/4936850-where-do-i-find-my-openai-api-key',
            ],
            'OPENAI_API_BASE_URL' => [
                'group' => 'ai',
                'anchor' => 'openai-api-base-url',
                'env_key' => 'OPENAI_API_BASE_URL',
                'label_key' => 'integrations_openai_base_url',
                'hint_key' => 'integrations_openai_base_url_hint',
                'placeholder_key' => 'integrations_openai_base_url_placeholder',
            ],
            'OPENAI_RESPONSES_MODEL' => [
                'group' => 'ai',
                'anchor' => 'openai-responses-model',
                'env_key' => 'OPENAI_RESPONSES_MODEL',
                'label_key' => 'integrations_openai_responses_model',
                'hint_key' => 'integrations_openai_responses_model_hint',
                'placeholder_key' => 'integrations_openai_responses_model_placeholder',
            ],
            'OPENAI_TIMEOUT' => [
                'group' => 'ai',
                'anchor' => 'openai-timeout',
                'env_key' => 'OPENAI_TIMEOUT',
                'label_key' => 'integrations_openai_timeout',
                'hint_key' => 'integrations_openai_timeout_hint',
                'example' => '30',
            ],
            'OPENAI_MAX_OUTPUT_TOKENS' => [
                'group' => 'ai',
                'anchor' => 'openai-max-output-tokens',
                'env_key' => 'OPENAI_MAX_OUTPUT_TOKENS',
                'label_key' => 'integrations_openai_max_output_tokens',
                'hint_key' => 'integrations_openai_max_output_tokens_hint',
                'example' => '4096',
            ],
            'COOKIE_BANNER_ENABLED' => [
                'group' => 'cookies',
                'anchor' => 'cookie-banner-enabled',
                'env_key' => 'COOKIE_BANNER_ENABLED',
                'label_key' => 'integrations_cookie_banner_enabled',
                'hint_key' => 'integrations_cookie_banner_enabled_hint',
            ],
            'COOKIE_REQUIRE_CONSENT' => [
                'group' => 'cookies',
                'anchor' => 'cookie-require-consent',
                'env_key' => 'COOKIE_REQUIRE_CONSENT',
                'label_key' => 'integrations_cookie_require_consent',
                'hint_key' => 'integrations_cookie_require_consent_hint',
            ],
            'AXEPTIO_CLIENT_ID' => [
                'group' => 'cookies',
                'anchor' => 'axeptio-client-id',
                'env_key' => 'AXEPTIO_CLIENT_ID',
                'label_key' => 'integrations_axeptio_client_id',
                'hint_key' => 'integrations_axeptio_client_id_hint',
                'example' => 'a1b2c3d4e5f6',
                'official_doc_url' => 'https://support.axeptio.eu/en/articles/273994-custom-integration-synchronisation',
            ],
            'AXEPTIO_COOKIES_VERSION' => [
                'group' => 'cookies',
                'anchor' => 'axeptio-cookies-version',
                'env_key' => 'AXEPTIO_COOKIES_VERSION',
                'label_key' => 'integrations_axeptio_cookies_version',
                'hint_key' => 'integrations_axeptio_cookies_version_hint',
                'example' => 'flatcms-fr-v1',
                'official_doc_url' => 'https://support.axeptio.eu/en/articles/273994-custom-integration-synchronisation',
            ],
            'MATOMO_ENABLED' => [
                'group' => 'analytics',
                'anchor' => 'matomo-enabled',
                'env_key' => 'MATOMO_ENABLED',
                'label_key' => 'integrations_matomo_enabled',
                'hint_key' => 'integrations_matomo_enabled_hint',
            ],
            'MATOMO_BASE_URL' => [
                'group' => 'analytics',
                'anchor' => 'matomo-base-url',
                'env_key' => 'MATOMO_BASE_URL',
                'label_key' => 'integrations_matomo_base_url',
                'hint_key' => 'integrations_matomo_base_url_hint',
                'example' => 'https://stats.example.com',
                'official_doc_url' => 'https://matomo.org/faq/how-to/how-do-i-install-the-matomo-analytics-tracking-code-on-an-html-website/',
            ],
            'MATOMO_SITE_ID' => [
                'group' => 'analytics',
                'anchor' => 'matomo-site-id',
                'env_key' => 'MATOMO_SITE_ID',
                'label_key' => 'integrations_matomo_site_id',
                'hint_key' => 'integrations_matomo_site_id_hint',
                'example' => '1',
                'official_doc_url' => 'https://matomo.org/faq/general/faq_19212/',
            ],
            'GOOGLE_ANALYTICS_ENABLED' => [
                'group' => 'analytics',
                'anchor' => 'google-analytics-enabled',
                'env_key' => 'GOOGLE_ANALYTICS_ENABLED',
                'label_key' => 'integrations_google_analytics_enabled',
                'hint_key' => 'integrations_google_analytics_enabled_hint',
            ],
            'GOOGLE_ANALYTICS_MEASUREMENT_ID' => [
                'group' => 'analytics',
                'anchor' => 'google-analytics-measurement-id',
                'env_key' => 'GOOGLE_ANALYTICS_MEASUREMENT_ID',
                'label_key' => 'integrations_google_analytics_measurement_id',
                'hint_key' => 'integrations_google_analytics_measurement_id_hint',
                'example' => 'G-XXXXXXXXXX',
                'official_doc_urls' => [
                    'fr-FR' => 'https://support.google.com/analytics/answer/12270356?hl=fr',
                    'en-US' => 'https://support.google.com/analytics/answer/12270356?hl=en',
                ],
            ],
            'TINYMCE_ENABLED' => [
                'group' => 'editors',
                'anchor' => 'tinymce-enabled',
                'env_key' => 'TINYMCE_ENABLED',
                'label_key' => 'integrations_tinymce_enabled',
                'hint_key' => 'integrations_tinymce_enabled_hint',
            ],
            'TINYMCE_API_KEY' => [
                'group' => 'editors',
                'anchor' => 'tinymce-api-key',
                'env_key' => 'TINYMCE_API_KEY',
                'label_key' => 'integrations_tinymce_api_key',
                'hint_key' => 'integrations_tinymce_api_key_hint',
                'example' => 'tiny-xxxxxxxxxxxxxxxx',
                'official_doc_url' => 'https://www.tiny.cloud/docs/tinymce/latest/cloud-quick-start/',
            ],
            'FONTAWESOME_KIT' => [
                'group' => 'editors',
                'anchor' => 'fontawesome-kit',
                'env_key' => 'FONTAWESOME_KIT',
                'label_key' => 'integrations_fontawesome_kit',
                'hint_key' => 'integrations_fontawesome_kit_hint',
                'example' => 'xxxxxxxxxx',
                'official_doc_url' => 'https://docs.fontawesome.com/web/setup/get-started',
            ],
            'TURNSTILE_ENABLED' => [
                'group' => 'security',
                'anchor' => 'turnstile-enabled',
                'env_key' => 'TURNSTILE_ENABLED',
                'label_key' => 'integrations_turnstile_enabled',
                'hint_key' => 'integrations_turnstile_enabled_hint',
            ],
            'TURNSTILE_SITE_KEY' => [
                'group' => 'security',
                'anchor' => 'turnstile-site-key',
                'env_key' => 'TURNSTILE_SITE_KEY',
                'label_key' => 'integrations_turnstile_site_key',
                'hint_key' => 'integrations_turnstile_site_key_hint',
                'example' => '0x4AAAAAAA-site-key',
                'official_doc_url' => 'https://developers.cloudflare.com/turnstile/get-started/',
            ],
            'TURNSTILE_SECRET_KEY' => [
                'group' => 'security',
                'anchor' => 'turnstile-secret-key',
                'env_key' => 'TURNSTILE_SECRET_KEY',
                'label_key' => 'integrations_turnstile_secret_key',
                'hint_key' => 'integrations_turnstile_secret_key_hint',
                'example' => '0x4AAAAAAA-secret-key',
                'official_doc_url' => 'https://developers.cloudflare.com/turnstile/get-started/',
            ],
        ];
    }
}
