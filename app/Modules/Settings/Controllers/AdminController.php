<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Settings\Controllers;

use App\Core\BaseController;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Core\Mail\Mailer;
use App\Core\Security\SecretBox;
use App\Services\AI\AIManager;
use App\Services\AI\DTO\AiRequest;
use App\Services\AI\Exceptions\AiConfigurationException;
use App\Modules\Settings\Services\CacheManager;
use App\Modules\Settings\Services\EnvConfigManager;
use App\Modules\Settings\Services\IntegrationsDocumentationService;
use App\Modules\Settings\Services\PromoBannerService;
use App\Modules\Settings\Services\SiteBrandingTranslationService;
use App\Modules\Settings\Services\SiteLogoService;
use App\Modules\Settings\Services\SiteRoutingService;

class AdminController extends BaseController
{
    private const LOGO_ALLOWED_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'bmp', 'svg', 'ico'];
    private const LOGO_ALLOWED_MIME = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/gif',
        'image/bmp',
        'image/svg+xml',
        'image/x-icon',
        'image/vnd.microsoft.icon',
        'application/vnd.microsoft.icon',
        'application/octet-stream',
    ];
    private const LOGO_MAX_FILE_SIZE = 10 * 1024 * 1024;
    private const ROUTING_PROBE_TTL = 300;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Settings');
    }

    public function index(): void
    {
        if (!$this->authorize('settings.view')) {
            return;
        }

        $settings = FlatFile::settings();
        $languages = $this->availableLanguages();
        $brandingService = new SiteBrandingTranslationService();
        $promoBannerService = new PromoBannerService();
        $siteRoutingService = new SiteRoutingService();
        $adminThemes = $this->availableThemes('admin');
        $frontendThemes = $this->availableThemes('frontend');
        $dateFormats = $this->dateFormats();
        $timezones = \timezone_identifiers_list();
        $fallbackTimezone = (string) config('app.timezone', 'Europe/Paris');

        $pathChecks = [
            'data' => BASE_PATH . '/data',
            'storage' => BASE_PATH . '/storage',
            'uploads' => BASE_PATH . '/public/uploads',
        ];
        $paths = [];
        foreach ($pathChecks as $key => $path) {
            $exists = is_dir($path) || is_file($path);
            $writable = $exists && is_writable($path);
            $paths[$key] = [
                'path' => $path,
                'exists' => $exists,
                'writable' => $writable,
            ];
        }

        $writeIssues = 0;
        foreach ($paths as $info) {
            if (!$info['exists'] || !$info['writable']) {
                $writeIssues++;
            }
        }

        $extensions = [
            'openssl' => extension_loaded('openssl'),
            'curl' => extension_loaded('curl'),
            'zip' => class_exists(\ZipArchive::class),
        ];
        $extensionsAvailable = 0;
        foreach ($extensions as $ok) {
            if ($ok) {
                $extensionsAvailable++;
            }
        }

        $envManager = new EnvConfigManager();
        $documentationService = new IntegrationsDocumentationService();
        $integrationValues = $envManager->readCurrentValues();
        $integrationEnvStatus = $envManager->status();
        $aiProviderStatus = [];
        try {
            $aiProviderStatus = (new AIManager())->configurationStatus();
        } catch (\Throwable $e) {
            $aiProviderStatus = [
                'provider' => 'openai-responses',
                'configured' => false,
                'transport_ready' => false,
                'endpoint' => '',
                'model' => '',
                'timeout' => 0,
                'max_output_tokens' => 0,
                'supports_tools' => false,
                'supports_structured_outputs' => false,
                'supports_conversation_state' => false,
                'tool_count' => 0,
                'issues' => ['missing_http_transport'],
            ];
        }
        $routingInfo = $this->buildRoutingInfo($settings);

        $this->render('Settings/Views/admin/index', [
            'pageTitle' => __('settings', 'Settings'),
            'settings' => $settings,
            'languages' => $languages,
            'adminThemes' => $adminThemes,
            'frontendThemes' => $frontendThemes,
            'dateFormats' => $dateFormats,
            'timezones' => $timezones,
            'fallbackTimezone' => $fallbackTimezone,
            'integrationValues' => $integrationValues,
            'integrationEnvStatus' => $integrationEnvStatus,
            'integrationsFieldHelp' => $documentationService->buildFieldHelpIndex(I18n::getLocale()),
            'aiProviderStatus' => $aiProviderStatus,
            'routingInfo' => $routingInfo,
            'promoBannerUi' => [
                'config' => $promoBannerService->normalizeSettings($settings),
                'translation_ui' => $this->buildPromoBannerTranslationUi($settings, $languages, $promoBannerService, I18n::getLocale()),
            ],
            'siteBrandingUi' => $this->buildSiteBrandingTranslationUi($settings, $languages, $brandingService, I18n::getLocale()),
            'siteRoutingUi' => $this->buildSiteRoutingUi($siteRoutingService),
            'systemInfo' => [
                'flatcms_version' => flatcms_version(),
                'environment' => (string) env('APP_ENV', 'production'),
                'php_version' => PHP_VERSION,
                'timezone' => date_default_timezone_get(),
                'paths' => $paths,
                'write_issues' => $writeIssues,
                'write_ok' => $writeIssues === 0,
                'extensions' => $extensions,
                'extensions_available' => $extensionsAvailable,
                'extensions_total' => count($extensions),
            ],
        ], 'admin.main');
    }

    public function integrationsHelp(): void
    {
        if (!$this->authorize('settings.view')) {
            return;
        }

        $documentationService = new IntegrationsDocumentationService();

        $this->render('Settings/Views/admin/help', [
            'pageTitle' => __('integrations_docs_title', 'Settings'),
            'documentation' => $documentationService->buildHelpPage(I18n::getLocale()),
        ], 'admin.main');
    }

    public function advanced(): void
    {
        if (!$this->authorize('settings.view')) {
            return;
        }

        $cacheManager = new CacheManager();

        $this->render('Settings/Views/admin/advanced', [
            'pageTitle' => __('settings_advanced_title', 'Settings'),
            'diagnostics' => $cacheManager->diagnostics(),
            'advancedActions' => $this->advancedActions(),
        ], 'admin.main');
    }

    public function runAdvancedAction(): void
    {
        if (!$this->authorize('settings.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $action = trim((string) $this->request->input('action', ''));
        $actionLabels = $this->advancedActions();
        $labelKey = (string) ($actionLabels[$action]['label_key'] ?? '');
        $actionLabel = $labelKey !== '' ? __($labelKey, 'Settings') : __('settings_advanced', 'Settings');

        $cacheManager = new CacheManager();
        $result = $cacheManager->runAction($action);
        $success = !empty($result['success']);
        $removed = (int) ($result['removed'] ?? 0);
        $warnings = is_array($result['warnings'] ?? null) ? $result['warnings'] : [];

        if ($success) {
            $this->session->flash('success', __('settings_advanced_flash_success', 'Settings', [
                'action' => $actionLabel,
                'count' => (string) $removed,
            ]));
        } else {
            $this->session->flash('error', __('settings_advanced_flash_error', 'Settings', [
                'action' => $actionLabel,
            ]));
        }

        foreach ($warnings as $warningKey) {
            $warningKey = trim((string) $warningKey);
            if ($warningKey === '') {
                continue;
            }
            $this->session->flash('warning', __($warningKey, 'Settings'));
        }

        $this->redirect(url('/admin/settings/advanced'));
    }

    public function update(): void
    {
        if (!$this->authorize('settings.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $data = $this->request->only([
            'site_name', 'site_description', 'site_email', 'site_url',
            'site_slogan',
            'site_name_enabled',
            'site_slogan_enabled',
            'site_logo_variant',
            'site_logo_mode',
            'page_header_enabled',
            'meta_title', 'meta_description', 'meta_keywords',
            'posts_per_page', 'date_format', 'timezone',
            'default_language', 'admin_theme', 'frontend_theme',
            'site_logo', 'site_logo_light', 'site_logo_dark', 'site_favicon',
            'admin_guided_tour_enabled',
            'url_routing_mode',
            'promo_banner_enabled',
            'promo_banner_text',
            'promo_banner_cta_label',
            'promo_banner_cta_url',
            'promo_banner_cta_variant',
            'promo_banner_alignment',
            'promo_banner_position',
            'promo_banner_min_height',
            'promo_banner_background_color',
            'promo_banner_text_color',
            // Mail
            'mail_transport', 'mail_from_address', 'mail_from_name',
            'mail_smtp_host', 'mail_smtp_port', 'mail_smtp_encryption', 'mail_smtp_username',
            // Contact
            'contact_notification_enabled', 'contact_notification_email', 'contact_enable_captcha',
        ]);

        $existing = FlatFile::settings();
        $merged = array_merge($existing, $data);

        $availableLanguages = array_keys($this->availableLanguages());
        $availableAdminThemes = array_keys($this->availableThemes('admin'));
        $availableFrontendThemes = array_keys($this->availableThemes('frontend'));

        $defaultLanguage = trim((string) ($merged['default_language'] ?? ''));
        if ($defaultLanguage === '' || !in_array($defaultLanguage, $availableLanguages, true)) {
            $defaultLanguage = (string) ($existing['default_language'] ?? config('app.locale', 'fr-FR'));
        }
        $merged['default_language'] = $defaultLanguage;

        $brandingService = new SiteBrandingTranslationService();
        $promoBannerService = new PromoBannerService();
        $siteRoutingService = new SiteRoutingService();
        $brandingInput = $this->request->input('branding_translations', []);
        $promoBannerTranslationsInput = $this->request->input('promo_banner_translations', []);
        if (!is_array($brandingInput)) {
            $brandingInput = [];
        }
        if (!is_array($promoBannerTranslationsInput)) {
            $promoBannerTranslationsInput = [];
        }
        $activeAdminLocale = $brandingService->normalizeLocale(I18n::getLocale());
        if ($activeAdminLocale === '') {
            $activeAdminLocale = $defaultLanguage;
        }
        $brandingInput[$activeAdminLocale] = array_merge(
            is_array($brandingInput[$activeAdminLocale] ?? null) ? $brandingInput[$activeAdminLocale] : [],
            [
                'site_name' => trim((string) ($merged['site_name'] ?? '')),
                'site_description' => trim((string) ($merged['site_description'] ?? '')),
                'site_slogan' => trim((string) ($merged['site_slogan'] ?? '')),
            ]
        );

        $brandingState = $brandingService->prepareSavePayload($brandingInput, $merged, $defaultLanguage);
        $promoBannerState = $promoBannerService->prepareTranslationPayload(
            $promoBannerTranslationsInput,
            $merged
        );
        $sourceLocale = (string) ($brandingState['source_locale'] ?? $defaultLanguage);
        $sourceTranslation = $brandingState['translations'][$sourceLocale] ?? [];
        if (!is_array($sourceTranslation)) {
            $sourceTranslation = [];
        }
        $merged['site_name'] = trim((string) ($sourceTranslation['site_name'] ?? ($merged['site_name'] ?? '')));
        if ($merged['site_name'] === '') {
            $merged['site_name'] = (string) config('app.name', 'FlatCMS');
        }
        $merged['site_description'] = trim((string) ($sourceTranslation['site_description'] ?? ($merged['site_description'] ?? '')));
        $merged['site_slogan'] = trim((string) ($sourceTranslation['site_slogan'] ?? ($merged['site_slogan'] ?? '')));
        $promoBannerResolved = $promoBannerService->resolveForLocale(
            $merged,
            (string) ($promoBannerState['source_locale'] ?? $defaultLanguage),
            $promoBannerState
        );
        $merged['promo_banner_text'] = trim((string) ($promoBannerResolved['text'] ?? ($merged['promo_banner_text'] ?? '')));
        $merged['promo_banner_cta_label'] = trim((string) ($promoBannerResolved['cta_label'] ?? ($merged['promo_banner_cta_label'] ?? '')));
        $merged['promo_banner_cta_url'] = trim((string) ($promoBannerResolved['cta_url'] ?? ($merged['promo_banner_cta_url'] ?? '')));

        $adminTheme = trim((string) ($merged['admin_theme'] ?? ''));
        if ($adminTheme === '' || !in_array($adminTheme, $availableAdminThemes, true)) {
            $adminTheme = (string) ($existing['admin_theme'] ?? config('app.admin_theme', 'admin-modern-pro'));
        }
        $merged['admin_theme'] = $adminTheme;

        $frontendTheme = trim((string) ($merged['frontend_theme'] ?? ''));
        if ($frontendTheme === '' || !in_array($frontendTheme, $availableFrontendThemes, true)) {
            $frontendTheme = (string) ($existing['frontend_theme'] ?? config('app.frontend_theme', 'default'));
        }
        $merged['frontend_theme'] = $frontendTheme;

        // Normalize mail settings
        $transport = strtolower(trim((string) ($merged['mail_transport'] ?? 'mail')));
        $merged['mail_transport'] = in_array($transport, ['mail', 'smtp'], true) ? $transport : 'mail';

        $enc = strtolower(trim((string) ($merged['mail_smtp_encryption'] ?? 'tls')));
        $merged['mail_smtp_encryption'] = in_array($enc, ['', 'none', 'tls', 'ssl'], true) ? $enc : 'tls';

        $port = (int) ($merged['mail_smtp_port'] ?? 587);
        if ($port <= 0 || $port > 65535) {
            $port = 587;
        }
        $merged['mail_smtp_port'] = $port;

        $postsPerPage = (int) ($merged['posts_per_page'] ?? 10);
        if ($postsPerPage < 1 || $postsPerPage > 50) {
            $postsPerPage = 10;
        }
        $merged['posts_per_page'] = (string) $postsPerPage;

        $timezone = trim((string) ($merged['timezone'] ?? ''));
        if ($timezone !== '' && !in_array($timezone, \timezone_identifiers_list(), true)) {
            $timezone = '';
        }
        if ($timezone === '' && $defaultLanguage === 'fr-FR') {
            $timezone = 'Europe/Paris';
        }
        $merged['timezone'] = $timezone;

        $dateFormat = trim((string) ($merged['date_format'] ?? ''));
        if ($dateFormat === '') {
            $dateFormat = $defaultLanguage === 'fr-FR' ? 'd F Y' : 'Y-m-d';
        }
        $merged['date_format'] = $dateFormat;

        $tourEnabledRaw = (string) ($merged['admin_guided_tour_enabled'] ?? '1');
        $merged['admin_guided_tour_enabled'] = in_array($tourEnabledRaw, ['1', 'true', 'on', 'yes'], true) ? 1 : 0;

        $contactNotificationRaw = (string) ($merged['contact_notification_enabled'] ?? '1');
        $merged['contact_notification_enabled'] = in_array($contactNotificationRaw, ['1', 'true', 'on', 'yes'], true) ? 1 : 0;

        $contactCaptchaRaw = (string) ($merged['contact_enable_captcha'] ?? '0');
        $merged['contact_enable_captcha'] = in_array($contactCaptchaRaw, ['1', 'true', 'on', 'yes'], true) ? 1 : 0;

        $contactNotificationEmail = trim((string) ($merged['contact_notification_email'] ?? ''));
        if ($contactNotificationEmail !== '' && filter_var($contactNotificationEmail, FILTER_VALIDATE_EMAIL) === false) {
            $contactNotificationEmail = '';
        }
        $merged['contact_notification_email'] = $contactNotificationEmail;

        $siteLogoVariant = trim((string) ($merged['site_logo_variant'] ?? 'compact'));
        if (!in_array($siteLogoVariant, ['compact', 'banner', 'banner_framed'], true)) {
            $siteLogoVariant = 'compact';
        }
        $merged['site_logo_variant'] = $siteLogoVariant;

        $siteLogoService = new SiteLogoService();
        $siteLogoMode = $siteLogoService->normalizeAppearanceMode((string) ($merged['site_logo_mode'] ?? ($existing['site_logo_mode'] ?? SiteLogoService::MODE_LIGHT)));
        $legacyLogoRaw = (string) ($merged['site_logo'] ?? ($existing['site_logo'] ?? ''));
        $siteLogoLightRaw = (string) ($merged['site_logo_light'] ?? ($existing['site_logo_light'] ?? $legacyLogoRaw));
        $siteLogoDarkRaw = (string) ($merged['site_logo_dark'] ?? ($existing['site_logo_dark'] ?? ''));
        $siteLogoLight = $this->normalizeSiteMediaSetting($siteLogoLightRaw, 'logo');
        $siteLogoDark = $this->normalizeSiteMediaSetting($siteLogoDarkRaw, 'logo');

        $merged['site_logo_mode'] = $siteLogoMode;
        $merged['site_logo_light'] = $siteLogoLight;
        $merged['site_logo_dark'] = $siteLogoDark;
        $merged['site_logo'] = $siteLogoLight !== ''
            ? $siteLogoLight
            : $siteLogoDark;
        $merged['site_favicon'] = $this->normalizeSiteMediaSetting((string) ($merged['site_favicon'] ?? ''), 'favicon');
        $merged = $promoBannerService->applyToSettings($merged);
        $this->cleanupRemovedSiteMediaFiles($existing, $merged);

        $routingMode = $this->normalizeRoutingMode((string) ($merged['url_routing_mode'] ?? 'auto'));
        $merged['url_routing_mode'] = $routingMode;
        $routingProbe = $this->probeRoutingMode($routingMode, $merged);
        $merged['url_rewrite_last_status'] = $routingProbe['status'];
        $merged['url_rewrite_last_check_at'] = date('Y-m-d H:i:s');

        if ($routingMode === 'pretty' && !$routingProbe['ok']) {
            // Protection anti-casse: rollback automatique.
            $merged['url_routing_mode'] = 'fallback';
            $merged['url_rewrite_last_status'] = 'failed';
            $this->session->flash('error', __('routing_pretty_failed_fallback', 'Settings'));
        } elseif ($routingMode === 'auto' && !$routingProbe['ok']) {
            $this->session->flash('error', __('routing_auto_probe_failed', 'Settings'));
        }

        // Compat legacy (ancienne clé).
        $merged['pretty_urls'] = \flatcms_pretty_urls_enabled($merged);

        // SMTP password: keep existing unless explicitly provided.
        $secretBox = new SecretBox();
        $clearPassword = (string) $this->request->input('mail_smtp_password_clear', '') === '1';
        $postedPassword = (string) $this->request->input('mail_smtp_password', '');
        $postedPassword = trim($postedPassword);

        if ($clearPassword) {
            $merged['mail_smtp_password'] = '';
        } elseif ($postedPassword !== '') {
            $merged['mail_smtp_password'] = $secretBox->encrypt($postedPassword);
        } else {
            $merged['mail_smtp_password'] = $secretBox->normalizeStoredValue((string) ($merged['mail_smtp_password'] ?? ''));
        }

        $activeTab = strtolower(trim((string) $this->request->input('_settings_tab', 'general')));
        $allowedTabs = ['general', 'routing', 'localization', 'appearance', 'content', 'seo', 'mail', 'integrations', 'system'];
        if (!in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'general';
        }

        if ($activeTab === 'integrations') {
            $integrationPayload = $this->request->input('env', []);
            if (!is_array($integrationPayload)) {
                $integrationPayload = [];
            }

            $envManager = new EnvConfigManager();
            try {
                $envManager->writeValues($integrationPayload);
            } catch (\Throwable $e) {
                $reason = $e instanceof \RuntimeException ? (string) $e->getMessage() : '';
                $status = $envManager->status();
                $envPath = (string) ($status['path'] ?? (BASE_PATH . '/.env.local'));

                error_log(sprintf(
                    '[FlatCMS][Settings] Failed to save integrations env (reason=%s, path=%s): %s',
                    $reason !== '' ? $reason : 'unknown',
                    $envPath,
                    $e->getMessage()
                ));

                $notWritableReasons = [
                    EnvConfigManager::ERROR_ENV_LOCAL_DIR_MISSING,
                    EnvConfigManager::ERROR_ENV_LOCAL_DIR_NOT_WRITABLE,
                    EnvConfigManager::ERROR_ENV_LOCAL_NOT_WRITABLE,
                ];

                if (in_array($reason, $notWritableReasons, true)) {
                    $this->session->flash('error', __('integrations_env_save_failed_not_writable', 'Settings'));
                } elseif ($reason === EnvConfigManager::ERROR_ENV_LOCAL_READ_FAILED || $reason === EnvConfigManager::ERROR_ENV_LOCAL_WRITE_FAILED) {
                    $this->session->flash('error', __('integrations_env_save_failed_write', 'Settings'));
                } else {
                    $this->session->flash('error', __('integrations_env_save_failed', 'Settings'));
                }
                $this->redirect(url('/admin/settings#settings-integrations'));
                return;
            }
        }

        hook_run('settings.before_save', $merged);
        FlatFile::saveSettings($merged);
        $brandingService->save($brandingState);
        $promoBannerService->saveTranslations($promoBannerState);
        $siteRoutingService->save($siteRoutingService->prepareHomepagePayload(
            (string) $this->request->input('homepage_mode', 'native'),
            (string) $this->request->input('homepage_page_group', '')
        ));
        hook_run('settings.after_save', $merged);

        $sendTest = (string) $this->request->input('send_test_email', '') === '1';
        if ($sendTest) {
            $testTo = trim((string) $this->request->input('mail_test_to', ''));
            if ($testTo === '') {
                $testTo = trim((string) ($merged['site_email'] ?? ''));
            }
            if ($testTo === '' || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
                $this->session->flash('success', __('settings_saved', 'Settings'));
                $this->session->flash('error', __('mail_test_invalid_email', 'Settings'));
                $this->redirect(url('/admin/settings'));
                return;
            }

            $mailer = new Mailer();
            $subject = __('mail_test_subject', 'Settings');
            $body = __('mail_test_body', 'Settings', [
                'site' => (string) ($merged['site_name'] ?? config('app.name', 'FlatCMS')),
                'time' => date('Y-m-d H:i:s'),
            ]);

            $sent = $mailer->send($testTo, $subject, $body);
            if ($sent) {
                $this->session->flash('success', __('settings_saved', 'Settings') . ' ' . __('mail_test_sent', 'Settings'));
            } else {
                $this->session->flash('success', __('settings_saved', 'Settings'));
                $this->session->flash('error', __('mail_test_failed', 'Settings'));
            }
        } else {
            $this->session->flash('success', __('settings_saved', 'Settings'));
        }

        $testOpenAiConnection = (string) $this->request->input('test_openai_connection', '') === '1';
        if ($activeTab === 'integrations' && $testOpenAiConnection) {
            $this->runOpenAiConnectionTest();
            $this->redirect(url('/admin/settings#settings-integrations'));
            return;
        }

        $this->redirect(url('/admin/settings'));
    }

    private function runOpenAiConnectionTest(): void
    {
        try {
            $manager = new AIManager();
            $response = $manager->respond(new AiRequest(
                input: 'Reply with the single word OK.',
                instructions: 'Return only the single word OK.',
                maxOutputTokens: 32,
            ));

            if ($response->hasRefusal()) {
                $message = trim((string) ($response->refusal?->message ?? ''));
                if ($message === '') {
                    $message = __('integrations_ai_test_refusal_generic', 'Settings');
                }

                $this->session->flash('warning', __('integrations_ai_test_refusal', 'Settings', [
                    'message' => $message,
                ]));
                return;
            }

            $output = trim((string) $response->outputText);
            $this->session->flash('success', __('integrations_ai_test_success', 'Settings', [
                'provider' => $response->provider,
                'model' => $response->model,
                'output' => $output !== '' ? $output : __('integrations_ai_test_output_fallback', 'Settings'),
            ]));
        } catch (AiConfigurationException $e) {
            $this->session->flash('error', __('integrations_ai_test_not_configured', 'Settings'));
        } catch (\Throwable $e) {
            $message = trim($e->getMessage());
            if ($message === '') {
                $message = __('integrations_ai_test_error_generic', 'Settings');
            }

            $this->session->flash('error', __('integrations_ai_test_failed', 'Settings', [
                'message' => $message,
            ]));
        }
    }

    public function logoMediaFiles(): void
    {
        if (!$this->authorize('settings.view')) {
            return;
        }

        $directory = $this->ensureLogoUploadDirectory();
        if ($directory === null) {
            $this->json([
                'success' => false,
                'message' => __('logo_media_storage_failed', 'Settings'),
                'files' => [],
            ], 500);
            return;
        }

        $files = $this->scanLogoFiles($directory);

        $this->json([
            'success' => true,
            'files' => $files,
            'count' => count($files),
        ]);
    }

    public function logoMediaUpload(): void
    {
        if (!$this->authorize('settings.edit')) {
            return;
        }

        if (!$this->verifyApiCsrf()) {
            $this->json([
                'success' => false,
                'message' => __('error.csrf', 'Core'),
            ], 419);
            return;
        }

        $directory = $this->ensureLogoUploadDirectory();
        if ($directory === null) {
            $this->json([
                'success' => false,
                'message' => __('logo_media_storage_failed', 'Settings'),
            ], 500);
            return;
        }

        $file = $this->collectLogoUploadedFile();
        if ($file === null) {
            $this->json([
                'success' => false,
                'message' => __('logo_media_no_file', 'Settings'),
            ], 422);
            return;
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $this->json([
                'success' => false,
                'message' => __('logo_media_upload_failed', 'Settings'),
            ], 422);
            return;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::LOGO_MAX_FILE_SIZE) {
            $this->json([
                'success' => false,
                'message' => __('logo_media_too_large', 'Settings'),
            ], 422);
            return;
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, self::LOGO_ALLOWED_EXTENSIONS, true)) {
            $this->json([
                'success' => false,
                'message' => __('logo_media_invalid_extension', 'Settings'),
            ], 422);
            return;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_file($tmpName)) {
            $this->json([
                'success' => false,
                'message' => __('logo_media_upload_failed', 'Settings'),
            ], 422);
            return;
        }

        $mimeType = (string) (mime_content_type($tmpName) ?: '');
        if (!$this->isLogoMimeAllowed($mimeType, $extension)) {
            $this->json([
                'success' => false,
                'message' => __('logo_media_invalid_mime', 'Settings'),
            ], 422);
            return;
        }

        $targetName = $this->generateLogoFilename($originalName, $extension, $directory);
        $targetPath = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $targetName;

        if (!@move_uploaded_file($tmpName, $targetPath)) {
            $this->json([
                'success' => false,
                'message' => __('logo_media_upload_failed', 'Settings'),
            ], 500);
            return;
        }

        $media = $this->buildLogoFilePayload($targetPath, $targetName);

        $this->json([
            'success' => true,
            'message' => __('logo_media_upload_success', 'Settings'),
            'media' => $media,
            'files' => [$media],
        ]);
    }

    public function routingProbe(string $token = ''): void
    {
        $token = trim($token);
        $token = (string) preg_replace('/[^a-zA-Z0-9]/', '', $token);
        $timestamp = trim((string) $this->request->input('ts', ''));
        $signature = strtolower(trim((string) $this->request->input('sig', '')));

        if ($token === '' || strlen($token) < 8 || strlen($token) > 64) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'FLATCMS_ROUTING_BAD_TOKEN';
            return;
        }

        if (!$this->isValidRoutingProbeSignature($token, $timestamp, $signature)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'FLATCMS_ROUTING_FORBIDDEN';
            return;
        }

        header('Content-Type: text/plain; charset=UTF-8');
        echo 'FLATCMS_ROUTING_OK:' . $token;
    }

    public function markGuidedTourSeen(): void
    {
        if (!$this->verifyApiCsrf()) {
            $this->json([
                'success' => false,
                'message' => __('error.csrf', 'Core'),
            ], 419);
            return;
        }

        $user = $this->currentSessionUser();
        if ($user === null) {
            $this->json([
                'success' => false,
                'message' => __('error.unauthorized', 'Core'),
            ], 401);
            return;
        }

        $userId = (string) ($user['id'] ?? '');
        if ($userId === '') {
            $this->json([
                'success' => false,
                'message' => __('error.server', 'Core'),
            ], 500);
            return;
        }

        $seenAt = date('Y-m-d H:i:s');
        $version = trim((string) ($this->request->input('version', 'v1')));
        if ($version === '') {
            $version = 'v1';
        }
        $module = strtolower(trim((string) ($this->request->input('module', 'global'))));
        $module = (string) preg_replace('/[^a-z0-9_-]/', '', $module);
        if ($module === '') {
            $module = 'global';
        }
        $markGlobal = in_array((string) $this->request->input('mark_global', '0'), ['1', 'true', 'yes', 'on'], true);

        $seenModules = $this->normalizeSeenModules($user['admin_tour_seen_modules'] ?? []);
        if ($module !== 'global' && !in_array($module, $seenModules, true)) {
            $seenModules[] = $module;
            sort($seenModules);
        }

        $currentSeenAt = trim((string) ($user['admin_tour_seen_at'] ?? ''));
        $currentVersion = trim((string) ($user['admin_tour_version'] ?? ''));
        $usersStore = FlatFile::for('users');
        $updated = $usersStore->update($userId, [
            'admin_tour_seen_at' => $markGlobal ? $seenAt : $currentSeenAt,
            'admin_tour_version' => $markGlobal ? $version : $currentVersion,
            'admin_tour_seen_modules' => $seenModules,
            'admin_tour_force_next_login' => 0,
        ]);

        if (!is_array($updated)) {
            $this->json([
                'success' => false,
                'message' => __('error.server', 'Core'),
            ], 500);
            return;
        }

        $this->syncSessionUser($updated);

        $this->json([
            'success' => true,
            'seen_at' => $markGlobal ? $seenAt : $currentSeenAt,
            'version' => $markGlobal ? $version : $currentVersion,
            'module' => $module,
            'modules' => $seenModules,
        ]);
    }

    public function resetGuidedTourSeen(): void
    {
        if (!$this->verifyApiCsrf()) {
            $this->json([
                'success' => false,
                'message' => __('error.csrf', 'Core'),
            ], 419);
            return;
        }

        if (!can('settings.edit')) {
            $this->json([
                'success' => false,
                'message' => __('error.unauthorized', 'Core'),
            ], 403);
            return;
        }

        $user = $this->currentSessionUser();
        if ($user === null) {
            $this->json([
                'success' => false,
                'message' => __('error.unauthorized', 'Core'),
            ], 401);
            return;
        }

        $userId = (string) ($user['id'] ?? '');
        if ($userId === '') {
            $this->json([
                'success' => false,
                'message' => __('error.server', 'Core'),
            ], 500);
            return;
        }

        $usersStore = FlatFile::for('users');
        $updated = $usersStore->update($userId, [
            'admin_tour_seen_at' => '',
            'admin_tour_version' => '',
            'admin_tour_seen_modules' => [],
            'admin_tour_force_next_login' => 1,
        ]);

        if (!is_array($updated)) {
            $this->json([
                'success' => false,
                'message' => __('error.server', 'Core'),
            ], 500);
            return;
        }

        $this->syncSessionUser($updated);

        $this->json([
            'success' => true,
        ]);
    }

    private function verifyApiCsrf(): bool
    {
        $token = (string) ($this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN') ?? '');
        if ($token === '') {
            return false;
        }

        return $this->session->verifyToken($token);
    }

    private function currentSessionUser(): ?array
    {
        $user = auth();
        if (!is_array($user)) {
            return null;
        }

        return $user;
    }

    private function syncSessionUser(array $user): void
    {
        if (isset($user['password'])) {
            unset($user['password']);
        }

        $this->session->set('user', $user);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function advancedActions(): array
    {
        return [
            CacheManager::ACTION_CLEAR_APP_CACHE => [
                'label_key' => 'settings_advanced_action_clear_app_cache',
                'icon' => 'fas fa-broom',
                'variant' => 'secondary',
            ],
            CacheManager::ACTION_CLEAR_RUNTIME_CSS => [
                'label_key' => 'settings_advanced_action_clear_runtime_css',
                'icon' => 'fas fa-palette',
                'variant' => 'secondary',
            ],
            CacheManager::ACTION_RESET_OPCACHE => [
                'label_key' => 'settings_advanced_action_reset_opcache',
                'icon' => 'fas fa-bolt',
                'variant' => 'outline',
            ],
            CacheManager::ACTION_CLEAR_ALL => [
                'label_key' => 'settings_advanced_action_clear_all',
                'icon' => 'fas fa-rotate-left',
                'variant' => 'primary',
            ],
        ];
    }

    /**
     * @return array<int,string>
     */
    private function normalizeSeenModules(mixed $value): array
    {
        $raw = [];

        if (is_array($value)) {
            $raw = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $raw = $decoded;
            } else {
                $raw = preg_split('/[,;]+/', $value) ?: [];
            }
        }

        $modules = [];
        foreach ($raw as $item) {
            $module = strtolower(trim((string) $item));
            $module = (string) preg_replace('/[^a-z0-9_-]/', '', $module);
            if ($module === '' || $module === 'global') {
                continue;
            }
            $modules[$module] = $module;
        }

        $result = array_values($modules);
        sort($result);
        return $result;
    }

    /**
     * @return array<string,string>
     */
    private function availableLanguages(): array
    {
        $result = [];
        $directory = BASE_PATH . '/data/languages';
        if (!is_dir($directory)) {
            $fallback = I18n::getSupportedLocales();
            foreach ($fallback as $code) {
                $result[$code] = $code;
            }
            return $result;
        }

        foreach (glob($directory . '/*.json') ?: [] as $file) {
            $code = (string) basename((string) $file, '.json');
            $payload = \json_read($file);
            if (!is_array($payload)) {
                continue;
            }

            $localizedName = I18n::getLocalizedLanguageName($code, I18n::getLocale());
            $name = trim((string) ($localizedName !== '' ? $localizedName : ($payload['native'] ?? $payload['name'] ?? $code)));
            $result[$code] = $name !== '' ? $name : $code;
        }

        if (empty($result)) {
            foreach (I18n::getSupportedLocales() as $code) {
                $result[$code] = $code;
            }
        }

        ksort($result);
        return $result;
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, string> $languages
     * @return array<string, mixed>
     */
    private function buildSiteBrandingTranslationUi(array $settings, array $languages, SiteBrandingTranslationService $service, string $activeLocale): array
    {
        $state = $service->read($settings);
        $sourceLocale = (string) ($state['source_locale'] ?? $service->defaultLocale($settings));
        $translations = is_array($state['translations'] ?? null) ? $state['translations'] : [];
        $normalizedActiveLocale = $service->normalizeLocale($activeLocale);
        if ($normalizedActiveLocale === '') {
            $normalizedActiveLocale = $sourceLocale;
        }

        $tabs = [];
        foreach ($service->supportedLocales() as $locale) {
            $entry = $translations[$locale] ?? [];
            if (!is_array($entry)) {
                $entry = [];
            }

            $isSource = $locale === $sourceLocale;
            $status = $isSource ? 'source' : $this->siteBrandingStatus($entry);
            $formLabels = $this->siteBrandingFieldLabelsForLocale($locale);
            $uiLabels = $this->siteBrandingUiLabelsForLocale($locale);
            $tabs[] = [
                'code' => $locale,
                'label' => $service->localeLabel($locale, $locale),
                'flag' => $this->localeFlagEmoji($locale),
                'is_source' => $isSource,
                'is_active' => $locale === $normalizedActiveLocale,
                'status' => $status,
                'form_labels' => $formLabels,
                'ui_labels' => $uiLabels,
                'values' => [
                    'site_name' => trim((string) ($entry['site_name'] ?? '')),
                    'site_description' => trim((string) ($entry['site_description'] ?? '')),
                    'site_slogan' => trim((string) ($entry['site_slogan'] ?? '')),
                ],
            ];
        }

        $sourceValues = $translations[$sourceLocale] ?? [
            'site_name' => trim((string) ($settings['site_name'] ?? '')),
            'site_description' => trim((string) ($settings['site_description'] ?? '')),
            'site_slogan' => trim((string) ($settings['site_slogan'] ?? '')),
        ];
        $activeValues = $service->resolveForLocale($settings, $normalizedActiveLocale);

        return [
            'source_locale' => $sourceLocale,
            'source_label' => (string) ($languages[$sourceLocale] ?? $service->localeLabel($sourceLocale, I18n::getLocale())),
            'active_locale' => $normalizedActiveLocale,
            'active_label' => (string) ($languages[$normalizedActiveLocale] ?? $service->localeLabel($normalizedActiveLocale, I18n::getLocale())),
            'active_values' => [
                'site_name' => trim((string) ($activeValues['site_name'] ?? '')),
                'site_description' => trim((string) ($activeValues['site_description'] ?? '')),
                'site_slogan' => trim((string) ($activeValues['site_slogan'] ?? '')),
            ],
            'tabs' => $tabs,
            'summary' => [
                'site_name' => trim((string) ($sourceValues['site_name'] ?? '')),
                'site_description' => trim((string) ($sourceValues['site_description'] ?? '')),
                'site_slogan' => trim((string) ($sourceValues['site_slogan'] ?? '')),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSiteRoutingUi(SiteRoutingService $service): array
    {
        $state = $service->read();
        $homepage = is_array($state['homepage'] ?? null) ? $state['homepage'] : [];
        $options = $service->listHomepagePageOptions(I18n::getLocale());
        $selectedGroup = trim((string) ($homepage['ref_group'] ?? ''));

        $summary = null;
        foreach ($options as $option) {
            if ((string) ($option['translation_group'] ?? '') === $selectedGroup) {
                $summary = [
                    'title' => (string) ($option['title'] ?? ''),
                    'slug' => (string) ($option['slug'] ?? ''),
                    'locale' => (string) ($option['locale'] ?? ''),
                    'locale_label' => (string) ($option['locale_label'] ?? ''),
                    'editor_mode' => (string) ($option['editor_mode'] ?? 'classic'),
                ];
                break;
            }
        }

        return [
            'homepage' => [
                'mode' => (string) ($homepage['mode'] ?? 'native'),
                'ref_group' => $selectedGroup,
                'options' => $options,
                'summary' => $summary,
                'summary_missing' => $selectedGroup !== '' && !is_array($summary),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, string> $languages
     * @return array<string, mixed>
     */
    private function buildPromoBannerTranslationUi(array $settings, array $languages, PromoBannerService $service, string $activeLocale): array
    {
        $state = $service->readTranslations($settings);
        $sourceLocale = (string) ($state['source_locale'] ?? $service->defaultLocale($settings));
        $translations = is_array($state['translations'] ?? null) ? $state['translations'] : [];
        $normalizedActiveLocale = $service->normalizeLocale($activeLocale);
        if ($normalizedActiveLocale === '') {
            $normalizedActiveLocale = $sourceLocale;
        }

        $tabs = [];
        foreach ($service->supportedLocales() as $locale) {
            $entry = $translations[$locale] ?? [];
            if (!is_array($entry)) {
                $entry = [];
            }

            $isSource = $locale === $sourceLocale;
            $status = $isSource ? 'source' : $this->promoBannerStatus($entry);
            $formLabels = $this->promoBannerFieldLabelsForLocale($locale);
            $uiLabels = $this->promoBannerUiLabelsForLocale($locale);

            $tabs[] = [
                'code' => $locale,
                'label' => $service->localeLabel($locale, $locale),
                'flag' => $this->localeFlagEmoji($locale),
                'is_source' => $isSource,
                'is_active' => $locale === $normalizedActiveLocale,
                'status' => $status,
                'form_labels' => $formLabels,
                'ui_labels' => $uiLabels,
                'values' => [
                    'text' => trim((string) ($entry['text'] ?? '')),
                    'cta_label' => trim((string) ($entry['cta_label'] ?? '')),
                    'cta_url' => trim((string) ($entry['cta_url'] ?? '')),
                ],
            ];
        }

        return [
            'source_locale' => $sourceLocale,
            'source_label' => (string) ($languages[$sourceLocale] ?? $service->localeLabel($sourceLocale, I18n::getLocale())),
            'active_locale' => $normalizedActiveLocale,
            'active_label' => (string) ($languages[$normalizedActiveLocale] ?? $service->localeLabel($normalizedActiveLocale, I18n::getLocale())),
            'tabs' => $tabs,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function siteBrandingFieldLabelsForLocale(string $locale): array
    {
        $payload = $this->loadLanguagePayload(BASE_PATH . '/app/Modules/Settings/Languages/' . $locale . '.json');

        return [
            'site_name' => trim((string) ($payload['site_name'] ?? __('site_name', 'Settings'))),
            'site_description' => trim((string) ($payload['site_description'] ?? __('site_description', 'Settings'))),
            'site_slogan' => trim((string) ($payload['site_slogan'] ?? __('site_slogan', 'Settings'))),
            'site_slogan_placeholder' => trim((string) ($payload['site_slogan_placeholder'] ?? __('site_slogan_placeholder', 'Settings'))),
            'site_slogan_hint' => trim((string) ($payload['site_slogan_hint'] ?? __('site_slogan_hint', 'Settings'))),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function promoBannerFieldLabelsForLocale(string $locale): array
    {
        $payload = $this->loadLanguagePayload(BASE_PATH . '/app/Modules/Settings/Languages/' . $locale . '.json');

        return [
            'promo_banner_text' => trim((string) ($payload['promo_banner_text'] ?? __('promo_banner_text', 'Settings'))),
            'promo_banner_text_hint' => trim((string) ($payload['promo_banner_text_hint'] ?? __('promo_banner_text_hint', 'Settings'))),
            'promo_banner_cta_label' => trim((string) ($payload['promo_banner_cta_label'] ?? __('promo_banner_cta_label', 'Settings'))),
            'promo_banner_cta_label_hint' => trim((string) ($payload['promo_banner_cta_label_hint'] ?? __('promo_banner_cta_label_hint', 'Settings'))),
            'promo_banner_cta_url' => trim((string) ($payload['promo_banner_cta_url'] ?? __('promo_banner_cta_url', 'Settings'))),
            'promo_banner_cta_url_hint' => trim((string) ($payload['promo_banner_cta_url_hint'] ?? __('promo_banner_cta_url_hint', 'Settings'))),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function promoBannerUiLabelsForLocale(string $locale): array
    {
        $settingsPayload = $this->loadLanguagePayload(BASE_PATH . '/app/Modules/Settings/Languages/' . $locale . '.json');

        return [
            'translations_label' => trim((string) ($settingsPayload['promo_banner_translations'] ?? __('promo_banner_translations', 'Settings'))),
            'translation_source' => trim((string) ($settingsPayload['translation_source'] ?? __('translation_source', 'Settings'))),
            'translation_ready' => trim((string) ($settingsPayload['translation_ready'] ?? __('translation_ready', 'Settings'))),
            'translation_missing' => trim((string) ($settingsPayload['translation_missing'] ?? __('translation_missing', 'Settings'))),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function siteBrandingUiLabelsForLocale(string $locale): array
    {
        $settingsPayload = $this->loadLanguagePayload(BASE_PATH . '/app/Modules/Settings/Languages/' . $locale . '.json');
        $corePayload = $this->loadLanguagePayload(BASE_PATH . '/app/Modules/Core/Languages/' . $locale . '.json');

        return [
            'modal_title' => trim((string) ($settingsPayload['site_branding_translations_modal_title'] ?? __('site_branding_translations_modal_title', 'Settings'))),
            'modal_help' => trim((string) ($settingsPayload['site_branding_translations_modal_help'] ?? __('site_branding_translations_modal_help', 'Settings'))),
            'translations_label' => trim((string) ($settingsPayload['site_branding_translations'] ?? __('site_branding_translations', 'Settings'))),
            'translation_source' => trim((string) ($settingsPayload['translation_source'] ?? $settingsPayload['site_branding_translation_source'] ?? __('site_branding_translation_source', 'Settings'))),
            'translation_ready' => trim((string) ($settingsPayload['translation_ready'] ?? $settingsPayload['site_branding_translation_translated'] ?? __('site_branding_translation_translated', 'Settings'))),
            'translation_missing' => trim((string) ($settingsPayload['translation_missing'] ?? $settingsPayload['site_branding_translation_empty'] ?? __('site_branding_translation_empty', 'Settings'))),
            'close' => trim((string) ($corePayload['close'] ?? __('close', 'Core'))),
            'save' => trim((string) ($corePayload['save'] ?? __('save', 'Core'))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLanguagePayload(string $path): array
    {
        $payload = \json_read($path);
        return is_array($payload) ? $payload : [];
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function siteBrandingStatus(array $entry): string
    {
        foreach (['site_name', 'site_description', 'site_slogan'] as $field) {
            if (trim((string) ($entry[$field] ?? '')) !== '') {
                return 'translated';
            }
        }

        return 'empty';
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function promoBannerStatus(array $entry): string
    {
        foreach (['text', 'cta_label', 'cta_url'] as $field) {
            if (trim((string) ($entry[$field] ?? '')) !== '') {
                return 'translated';
            }
        }

        return 'empty';
    }

    private function localeFlagEmoji(string $locale): string
    {
        $value = trim($locale);
        if ($value === '') {
            return '🏳️';
        }

        $parts = preg_split('/[-_]/', $value) ?: [];
        $country = strtoupper((string) end($parts));
        if (!preg_match('/^[A-Z]{2}$/', $country)) {
            $country = strtoupper(substr($value, 0, 2));
        }

        if (!preg_match('/^[A-Z]{2}$/', $country)) {
            return '🏳️';
        }

        $first = 127397 + ord($country[0]);
        $second = 127397 + ord($country[1]);

        return html_entity_decode('&#' . $first . ';&#' . $second . ';', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @return array<string,string>
     */
    private function availableThemes(string $type): array
    {
        $result = [];
        $basePath = BASE_PATH . '/themes/' . $type;
        if (!is_dir($basePath)) {
            return $result;
        }

        foreach (glob($basePath . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $slug = (string) basename((string) $directory);
            $label = str_replace(['-', '_'], ' ', $slug);
            $result[$slug] = ucwords($label);
        }

        ksort($result);
        return $result;
    }

    /**
     * @return array<string,string>
     */
    private function dateFormats(): array
    {
        $now = date('Y-m-d H:i:s');

        return [
            'Y-m-d' => date('Y-m-d', strtotime($now)),
            'd/m/Y' => date('d/m/Y', strtotime($now)),
            'd.m.Y' => date('d.m.Y', strtotime($now)),
            'm/d/Y' => date('m/d/Y', strtotime($now)),
            'd F Y' => $this->formatFrenchLongDate($now),
            'F j, Y' => date('F j, Y', strtotime($now)),
        ];
    }

    private function formatFrenchLongDate(string $dateTime): string
    {
        $timestamp = strtotime($dateTime);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $months = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre',
        ];

        $day = date('d', $timestamp);
        $monthIndex = (int) date('n', $timestamp);
        $year = date('Y', $timestamp);
        $month = $months[$monthIndex] ?? date('F', $timestamp);

        return $day . ' ' . $month . ' ' . $year;
    }

    /**
     * @return array{mode:string,status:string,last_check:string,server_type:string,rewrite_support:string,rewrite_active_now:bool}
     */
    private function buildRoutingInfo(array $settings): array
    {
        $mode = $this->normalizeRoutingMode((string) ($settings['url_routing_mode'] ?? 'auto'));
        $status = strtolower(trim((string) ($settings['url_rewrite_last_status'] ?? 'unknown')));
        if (!in_array($status, ['ok', 'failed', 'disabled', 'unknown'], true)) {
            $status = 'unknown';
        }

        return [
            'mode' => $mode,
            'status' => $status,
            'last_check' => (string) ($settings['url_rewrite_last_check_at'] ?? ''),
            'server_type' => $this->detectServerType(),
            'rewrite_support' => $this->detectRewriteSupport(),
            'rewrite_active_now' => $this->isPrettyRequestActive(),
        ];
    }

    private function normalizeRoutingMode(string $mode): string
    {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['auto', 'pretty', 'fallback'], true)) {
            return 'auto';
        }
        return $mode;
    }

    /**
     * @param array<string,mixed> $settings
     * @return array{ok:bool,status:string}
     */
    private function probeRoutingMode(string $mode, array $settings): array
    {
        if ($mode === 'fallback') {
            return ['ok' => true, 'status' => 'disabled'];
        }

        // Si la requête courante prouve déjà que le rewrite fonctionne, inutile de retester.
        if ($this->isPrettyRequestActive()) {
            return ['ok' => true, 'status' => 'ok'];
        }

        try {
            $token = bin2hex(random_bytes(8));
        } catch (\Throwable $e) {
            $token = substr(md5(uniqid('flatcms-routing', true)), 0, 16);
        }
        $timestamp = (string) time();
        $signature = $this->buildRoutingProbeSignature($token, $timestamp);
        $probePath = '/admin/settings/routing-probe/' . rawurlencode($token)
            . '?ts=' . rawurlencode($timestamp)
            . '&sig=' . rawurlencode($signature);

        $candidates = $this->buildRoutingProbeCandidates($settings);

        foreach ($candidates as $base) {
            $url = $base . $probePath;
            $body = $this->httpGet($url, 2);
            if ($body === null) {
                continue;
            }
            if (str_contains($body, 'FLATCMS_ROUTING_OK:' . $token)) {
                return ['ok' => true, 'status' => 'ok'];
            }
        }

        return ['ok' => false, 'status' => 'failed'];
    }

    /**
     * @param array<string,mixed> $settings
     * @return string[]
     */
    private function buildRoutingProbeCandidates(array $settings): array
    {
        $candidates = [];

        $siteUrl = trim((string) ($settings['site_url'] ?? ''));
        if ($this->isValidHttpUrl($siteUrl)) {
            $candidates[] = rtrim($siteUrl, '/');
        }

        $baseUrl = trim((string) base_url());
        if ($this->isValidHttpUrl($baseUrl)) {
            $candidates[] = rtrim($baseUrl, '/');
        }

        $origin = $this->currentRequestOrigin();
        if ($origin !== '') {
            $basePath = trim((string) base_url());
            if ($basePath !== '' && !$this->isValidHttpUrl($basePath)) {
                $basePath = $this->normalizeProbePath($basePath);
                if ($basePath !== '') {
                    $candidates[] = rtrim($origin . $basePath, '/');
                }
            }

            $candidates[] = rtrim($origin, '/');
        }

        $candidates = array_values(array_unique(array_filter($candidates, function ($candidate): bool {
            return $this->isValidHttpUrl((string) $candidate);
        })));

        return $candidates;
    }

    private function normalizeProbePath(string $basePath): string
    {
        $basePath = trim($basePath);
        if ($basePath === '' || $basePath === '/') {
            return '';
        }

        if (preg_match('~^https?://~i', $basePath) === 1) {
            $parsed = parse_url($basePath);
            if (is_array($parsed) && isset($parsed['path'])) {
                $basePath = (string) $parsed['path'];
            }
        }

        if (!str_starts_with($basePath, '/')) {
            $basePath = '/' . $basePath;
        }

        $segments = explode('/', $basePath);
        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }
            $segments[$index] = rawurlencode(rawurldecode($segment));
        }

        $basePath = implode('/', $segments);
        return $basePath === '/' ? '' : $basePath;
    }

    private function currentRequestOrigin(): string
    {
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            return '';
        }

        $scheme = 'http';
        if (
            (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
        ) {
            $scheme = 'https';
        } else {
            $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
            if ($forwardedProto !== '' && in_array('https', array_map('trim', explode(',', $forwardedProto)), true)) {
                $scheme = 'https';
            } elseif (strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? '')) === 'https') {
                $scheme = 'https';
            }
        }

        return $scheme . '://' . $host;
    }

    private function buildRoutingProbeSignature(string $token, string $timestamp): string
    {
        return hash_hmac('sha256', $token . '|' . $timestamp, $this->routingProbeSecret());
    }

    private function isValidRoutingProbeSignature(string $token, string $timestamp, string $signature): bool
    {
        if ($timestamp === '' || preg_match('/^\d{10}$/', $timestamp) !== 1) {
            return false;
        }

        if ($signature === '' || preg_match('/^[a-f0-9]{64}$/', $signature) !== 1) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::ROUTING_PROBE_TTL) {
            return false;
        }

        return hash_equals($this->buildRoutingProbeSignature($token, $timestamp), $signature);
    }

    private function routingProbeSecret(): string
    {
        $secret = trim((string) env('FLATCMS_ROUTING_PROBE_KEY', ''));
        if ($secret === '') {
            $secret = trim((string) env('FLATCMS_LICENSE_VAULT_KEY', ''));
        }
        if ($secret === '') {
            $secret = flatcms_product_name()
                . '|'
                . trim((string) env('APP_URL', ''))
                . '|'
                . BASE_PATH;
        }

        return hash('sha256', $secret);
    }

    private function isValidHttpUrl(string $value): bool
    {
        if ($value === '' || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return preg_match('~^https?://~i', $value) === 1;
    }

    private function httpGet(string $url, int $timeoutSeconds = 2): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSeconds);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'FlatCMS-Routing-Probe/1.0');
            $response = curl_exec($ch);
            if (PHP_VERSION_ID < 80000) {
                curl_close($ch);
            }

            if ($response === false || !is_string($response)) {
                return null;
            }

            return $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeoutSeconds,
                'ignore_errors' => true,
                'header' => "User-Agent: FlatCMS-Routing-Probe/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false || !is_string($response)) {
            return null;
        }

        return $response;
    }

    private function isPrettyRequestActive(): bool
    {
        if (isset($_GET['path']) || isset($_GET['route'])) {
            return false;
        }

        $requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($requestPath === '') {
            return false;
        }

        return preg_match('#/index\\.php$#i', $requestPath) !== 1;
    }

    private function detectServerType(): string
    {
        $software = strtolower((string) ($_SERVER['SERVER_SOFTWARE'] ?? ''));
        if (str_contains($software, 'apache')) {
            return 'apache';
        }
        if (str_contains($software, 'nginx')) {
            return 'nginx';
        }
        if (str_contains($software, 'iis') || str_contains($software, 'microsoft')) {
            return 'iis';
        }
        if (str_contains($software, 'litespeed')) {
            return 'litespeed';
        }
        return 'unknown';
    }

    private function detectRewriteSupport(): string
    {
        $serverType = $this->detectServerType();
        if (in_array($serverType, ['nginx', 'litespeed'], true)) {
            return 'likely';
        }
        if ($serverType === 'iis') {
            return function_exists('iis_get_server_variable') ? 'likely' : 'unknown';
        }
        if ($serverType === 'apache') {
            if (function_exists('apache_get_modules')) {
                $modules = apache_get_modules();
                if (is_array($modules) && in_array('mod_rewrite', $modules, true)) {
                    return 'yes';
                }
                return 'no';
            }
            return 'unknown';
        }
        return 'unknown';
    }

    private function ensureLogoUploadDirectory(): ?string
    {
        $directory = BASE_PATH . '/public/uploads/logo';
        if (is_dir($directory)) {
            return $directory;
        }

        if (@mkdir($directory, 0755, true) && is_dir($directory)) {
            return $directory;
        }

        return null;
    }

    private function normalizeSiteMediaSetting(string $raw, string $mediaKind = ''): string
    {
        $value = trim(str_replace('\\', '/', $raw));
        if ($value === '') {
            return '';
        }

        if (
            str_starts_with($value, 'data:') ||
            str_starts_with($value, 'blob:')
        ) {
            return '';
        }

        if (preg_match('~^(https?:)?//~i', $value) === 1) {
            $normalizedPath = $this->normalizeSiteMediaLocalPath((string) (parse_url($value, PHP_URL_PATH) ?? ''));
            if ($normalizedPath !== '') {
                return $normalizedPath;
            }

            return $value;
        }

        $normalizedPath = $this->normalizeSiteMediaLocalPath($value);
        if ($normalizedPath !== '') {
            return $normalizedPath;
        }

        $normalizedMediaKind = strtolower(trim($mediaKind));
        $defaultFolder = in_array($normalizedMediaKind, ['logo', 'favicon'], true) ? 'logo' : '';
        if ($defaultFolder !== '' && !str_contains($value, '/')) {
            return '/uploads/' . $defaultFolder . '/' . ltrim($value, '/');
        }

        if (str_starts_with($value, '/')) {
            return '/' . ltrim($value, '/');
        }

        return '/uploads/' . ltrim($value, '/');
    }

    private function normalizeSiteMediaLocalPath(string $rawPath): string
    {
        $path = trim(str_replace('\\', '/', $rawPath));
        if ($path === '') {
            return '';
        }

        $path = '/' . ltrim($path, '/');
        if ($path === '/favicon.ico') {
            return '/favicon.ico';
        }

        if (preg_match('#^/public/uploads/(.+)$#i', $path, $match) === 1) {
            return '/uploads/' . ltrim((string) ($match[1] ?? ''), '/');
        }

        if (preg_match('#^/uploads/(.+)$#i', $path, $match) === 1) {
            return '/uploads/' . ltrim((string) ($match[1] ?? ''), '/');
        }

        if (preg_match('#^/logo/(.+)$#i', $path, $match) === 1) {
            return '/uploads/logo/' . ltrim((string) ($match[1] ?? ''), '/');
        }

        return '';
    }

    /**
     * @param array<string,mixed> $existing
     * @param array<string,mixed> $merged
     */
    private function cleanupRemovedSiteMediaFiles(array $existing, array $merged): void
    {
        $siteLogoService = new SiteLogoService();
        $existingLogoState = $siteLogoService->resolveLogoPaths($existing);
        $mergedLogoState = $siteLogoService->resolveLogoPaths($merged);
        $oldLogo = $this->normalizeSiteMediaSetting((string) ($existing['site_logo'] ?? ''), 'logo');
        $oldLightLogo = $this->normalizeSiteMediaSetting((string) ($existingLogoState['light'] ?? ''), 'logo');
        $oldDarkLogo = $this->normalizeSiteMediaSetting((string) ($existingLogoState['dark'] ?? ''), 'logo');
        $oldFavicon = $this->normalizeSiteMediaSetting((string) ($existing['site_favicon'] ?? ''), 'favicon');
        $newLogo = $this->normalizeSiteMediaSetting((string) ($merged['site_logo'] ?? ''), 'logo');
        $newLightLogo = $this->normalizeSiteMediaSetting((string) ($mergedLogoState['light'] ?? ''), 'logo');
        $newDarkLogo = $this->normalizeSiteMediaSetting((string) ($mergedLogoState['dark'] ?? ''), 'logo');
        $newFavicon = (string) ($merged['site_favicon'] ?? '');

        $protected = [];
        if ($newLogo !== '') {
            $protected[] = $newLogo;
        }
        if ($newLightLogo !== '') {
            $protected[] = $newLightLogo;
        }
        if ($newDarkLogo !== '') {
            $protected[] = $newDarkLogo;
        }
        if ($newFavicon !== '') {
            $protected[] = $newFavicon;
        }

        $oldValues = array_unique(array_filter([$oldLogo, $oldLightLogo, $oldDarkLogo, $oldFavicon], static fn(string $value): bool => $value !== ''));

        foreach ($oldValues as $oldValue) {
            if (in_array($oldValue, $protected, true)) {
                continue;
            }

            $absolutePath = $this->resolveManagedSiteMediaFilePath($oldValue);
            if ($absolutePath === null || !is_file($absolutePath)) {
                continue;
            }

            @unlink($absolutePath);
        }
    }

    private function resolveManagedSiteMediaFilePath(string $value): ?string
    {
        $normalized = $this->normalizeSiteMediaSetting($value);
        if ($normalized === '' || preg_match('#^/uploads/logo/(.+)$#i', $normalized) !== 1) {
            return null;
        }

        $relativePath = (string) preg_replace('#^/uploads/logo/#i', '', $normalized);
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        return BASE_PATH . '/public/uploads/logo/' . $relativePath;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function scanLogoFiles(string $directory): array
    {
        $items = [];
        $entries = @scandir($directory);
        if (!is_array($entries)) {
            return $items;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === '.gitkeep') {
                continue;
            }

            $filePath = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($filePath)) {
                continue;
            }

            $extension = strtolower((string) pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($extension, self::LOGO_ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $items[] = $this->buildLogoFilePayload($filePath, $entry);
        }

        usort($items, static function (array $a, array $b): int {
            return strtotime((string) ($b['created_at'] ?? '0')) <=> strtotime((string) ($a['created_at'] ?? '0'));
        });

        return $items;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function collectLogoUploadedFile(): ?array
    {
        $files = $_FILES['files'] ?? $_FILES['file'] ?? null;
        if (!is_array($files)) {
            return null;
        }

        if (isset($files['name']) && is_array($files['name'])) {
            $total = count($files['name']);
            for ($index = 0; $index < $total; $index++) {
                $candidate = [
                    'name' => (string) ($files['name'][$index] ?? ''),
                    'type' => (string) ($files['type'][$index] ?? ''),
                    'tmp_name' => (string) ($files['tmp_name'][$index] ?? ''),
                    'error' => (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                    'size' => (int) ($files['size'][$index] ?? 0),
                ];

                if ($candidate['name'] !== '') {
                    return $candidate;
                }
            }

            return null;
        }

        return [
            'name' => (string) ($files['name'] ?? ''),
            'type' => (string) ($files['type'] ?? ''),
            'tmp_name' => (string) ($files['tmp_name'] ?? ''),
            'error' => (int) ($files['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($files['size'] ?? 0),
        ];
    }

    private function isLogoMimeAllowed(string $mimeType, string $extension): bool
    {
        $mime = strtolower(trim($mimeType));
        if ($mime !== '' && in_array($mime, self::LOGO_ALLOWED_MIME, true)) {
            return true;
        }

        if ($mime !== '' && str_starts_with($mime, 'image/')) {
            return true;
        }

        return $extension === 'ico' && ($mime === '' || $mime === 'application/octet-stream');
    }

    private function generateLogoFilename(string $originalName, string $extension, string $directory): string
    {
        $baseName = (string) pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) ?? '';
        $baseName = trim((string) $baseName, '_');
        if ($baseName === '') {
            $baseName = 'logo';
        }
        $baseName = substr($baseName, 0, 80);

        $candidate = $baseName . '.' . $extension;
        $counter = 1;
        while (is_file(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $candidate)) {
            $candidate = $baseName . '_' . $counter . '.' . $extension;
            $counter++;
        }

        return $candidate;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildLogoFilePayload(string $filePath, string $filename): array
    {
        $path = 'logo/' . $filename;
        $mime = (string) (mime_content_type($filePath) ?: 'application/octet-stream');
        $size = (int) (filesize($filePath) ?: 0);

        $dimensions = null;
        if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml') {
            $imageInfo = @getimagesize($filePath);
            if (is_array($imageInfo) && isset($imageInfo[0], $imageInfo[1])) {
                $dimensions = [
                    'width' => (int) $imageInfo[0],
                    'height' => (int) $imageInfo[1],
                ];
            }
        }

        return [
            'id' => 0,
            'name' => $filename,
            'original_name' => $filename,
            'path' => $path,
            'url' => url('/uploads/' . $path),
            'folder' => 'logo',
            'type' => 'image',
            'mime' => $mime,
            'extension' => strtolower((string) pathinfo($filename, PATHINFO_EXTENSION)),
            'size' => $size,
            'dimensions' => $dimensions,
            'uploaded_by' => (int) ($this->session->get('user_id') ?? 0),
            'created_at' => date('Y-m-d H:i:s', (int) (filemtime($filePath) ?: time())),
        ];
    }
}
