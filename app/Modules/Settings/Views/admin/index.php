<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('Modules', 'css/modules.css') ?>">
<link rel="stylesheet" href="<?= module_asset('Settings', 'css/settings.css') ?>">

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
        <p class="page-subtitle"><?= __('settings_subtitle', 'Settings') ?></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= url('/admin/settings/advanced') ?>" class="btn btn-secondary">
            <i class="fas fa-sliders" aria-hidden="true"></i>
            <?= __('settings_advanced', 'Settings') ?>
        </a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-sliders-h"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('settings_help_badge', 'Settings') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('settings_help_title', 'Settings') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('settings_help_intro', 'Settings') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('settings_help_step_general', 'Settings') ?></li>
            <li><?= __('settings_help_step_content', 'Settings') ?></li>
            <li><?= __('settings_help_step_mail', 'Settings') ?></li>
            <li><?= __('settings_help_step_system', 'Settings') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <button type="button" class="btn btn-primary" data-settings-open-tab="general"><?= __('settings_help_action_general', 'Settings') ?></button>
            <button type="button" class="btn btn-secondary" data-settings-open-tab="content"><?= __('settings_help_action_content', 'Settings') ?></button>
            <button type="button" class="btn btn-secondary" data-settings-open-tab="mail"><?= __('settings_help_action_mail', 'Settings') ?></button>
            <button type="button" class="btn btn-secondary" data-settings-open-tab="system"><?= __('settings_help_action_system', 'Settings') ?></button>
        </div>
    </div>
</div>

<?php
$selectedDefaultLanguage = (string) ($settings['default_language'] ?? config('app.locale', 'fr-FR'));
$selectedAdminTheme = (string) ($settings['admin_theme'] ?? config('app.admin_theme', 'admin-modern-pro'));
$selectedFrontendTheme = (string) ($settings['frontend_theme'] ?? config('app.frontend_theme', 'default'));
$selectedTimezone = (string) ($settings['timezone'] ?? (($selectedDefaultLanguage === 'fr-FR') ? 'Europe/Paris' : ($fallbackTimezone ?? config('app.timezone', 'Europe/Paris'))));
$dateFormatValue = (string) ($settings['date_format'] ?? (($selectedDefaultLanguage === 'fr-FR') ? 'd F Y' : 'Y-m-d'));
$postsPerPageValue = (string) ($settings['posts_per_page'] ?? '10');
$siteNameEnabled = !array_key_exists('site_name_enabled', $settings)
    ? true
    : ((int) ($settings['site_name_enabled'] ?? 0) === 1);
$siteSloganEnabled = !array_key_exists('site_slogan_enabled', $settings)
    ? true
    : ((int) ($settings['site_slogan_enabled'] ?? 0) === 1);
$siteLogoVariantDefault = (!$siteNameEnabled && !$siteSloganEnabled) ? 'banner' : 'compact';
$siteLogoVariant = trim((string) ($settings['site_logo_variant'] ?? $siteLogoVariantDefault));
if (!in_array($siteLogoVariant, ['compact', 'banner', 'banner_framed'], true)) {
    $siteLogoVariant = $siteLogoVariantDefault;
}
$siteLogoService = new \App\Modules\Settings\Services\SiteLogoService();
$siteLogoState = $siteLogoService->resolveLogoPaths($settings ?? []);
$siteLogoAppearanceMode = (string) ($siteLogoState['mode'] ?? \App\Modules\Settings\Services\SiteLogoService::MODE_LIGHT);
$pageHeaderEnabled = !array_key_exists('page_header_enabled', $settings)
    ? true
    : ((int) ($settings['page_header_enabled'] ?? 0) === 1);
$guidedTourEnabled = !array_key_exists('admin_guided_tour_enabled', $settings)
    ? true
    : ((int) ($settings['admin_guided_tour_enabled'] ?? 0) === 1);
$integrationValues = is_array($integrationValues ?? null) ? $integrationValues : [];
$integrationEnvStatus = is_array($integrationEnvStatus ?? null) ? $integrationEnvStatus : [];
$integrationsFieldHelp = is_array($integrationsFieldHelp ?? null) ? $integrationsFieldHelp : [];
$aiProviderStatus = is_array($aiProviderStatus ?? null) ? $aiProviderStatus : [];
$integrationEnvPath = (string) ($integrationEnvStatus['path'] ?? (BASE_PATH . '/.env.local'));
$integrationEnvWritable = !empty($integrationEnvStatus['writable']);
$turnstileEnabledGlobal = ((int) ($integrationValues['TURNSTILE_ENABLED'] ?? 0) === 1);
$aiProviderName = trim((string) ($aiProviderStatus['provider'] ?? 'openai-responses'));
$aiProviderLabel = $aiProviderName === 'openai-responses'
    ? __('integrations_ai_provider_openai_responses', 'Settings')
    : $aiProviderName;
$aiConfigured = !empty($aiProviderStatus['configured']);
$aiTransportReady = !empty($aiProviderStatus['transport_ready']);
$aiSupportsTools = !empty($aiProviderStatus['supports_tools']);
$aiToolCount = max(0, (int) ($aiProviderStatus['tool_count'] ?? 0));
$aiIssues = [];
foreach ((array) ($aiProviderStatus['issues'] ?? []) as $issueCode) {
    $issueCode = trim((string) $issueCode);
    if ($issueCode === '') {
        continue;
    }

    $aiIssues[] = __('integrations_ai_issue_' . $issueCode, 'Settings');
}
$routingInfo = is_array($routingInfo ?? null) ? $routingInfo : [];
$routingMode = strtolower(trim((string) ($routingInfo['mode'] ?? ($settings['url_routing_mode'] ?? 'auto'))));
if (!in_array($routingMode, ['auto', 'pretty', 'fallback'], true)) {
    $routingMode = 'auto';
}
$routingStatus = strtolower(trim((string) ($routingInfo['status'] ?? ($settings['url_rewrite_last_status'] ?? 'unknown'))));
if (!in_array($routingStatus, ['ok', 'failed', 'disabled', 'unknown'], true)) {
    $routingStatus = 'unknown';
}
$routingLastCheck = trim((string) ($routingInfo['last_check'] ?? ($settings['url_rewrite_last_check_at'] ?? '')));
$routingServerType = strtolower(trim((string) ($routingInfo['server_type'] ?? 'unknown')));
$routingSupport = strtolower(trim((string) ($routingInfo['rewrite_support'] ?? 'unknown')));
$routingActiveNow = !empty($routingInfo['rewrite_active_now']);
$missingThemeSuffix = __('theme_missing_suffix', 'Settings');
$siteLogoLightStoredValue = trim((string) ($siteLogoState['light'] ?? ''));
$siteLogoDarkStoredValue = trim((string) ($siteLogoState['dark'] ?? ''));
$siteFaviconStoredValue = trim((string) ($settings['site_favicon'] ?? ''));
$normalizeSiteMediaFieldValue = static function (string $storedValue): string {
    $fieldValue = trim($storedValue);
    if ($fieldValue !== '' && preg_match('~^(https?:)?//~i', $fieldValue) !== 1) {
        $fieldValue = basename((string) (parse_url('/' . ltrim($fieldValue, '/'), PHP_URL_PATH) ?: $fieldValue));
    }

    return $fieldValue;
};
$siteLogoLightFieldValue = $normalizeSiteMediaFieldValue($siteLogoLightStoredValue);
$siteLogoDarkFieldValue = $normalizeSiteMediaFieldValue($siteLogoDarkStoredValue);
$siteLogoFieldValue = $siteLogoAppearanceMode === \App\Modules\Settings\Services\SiteLogoService::MODE_DARK
    ? $siteLogoDarkFieldValue
    : $siteLogoLightFieldValue;
$siteFaviconFieldValue = $siteFaviconStoredValue;
$siteFaviconFieldValue = $normalizeSiteMediaFieldValue($siteFaviconFieldValue);

$renderIntegrationHelp = static function (string $topicKey) use ($integrationsFieldHelp): string {
    $help = $integrationsFieldHelp[$topicKey] ?? null;
    if (!is_array($help)) {
        return '';
    }

    $summary = trim((string) ($help['summary'] ?? ''));
    $linkUrl = trim((string) ($help['link_url'] ?? ''));
    $ariaLabel = trim((string) ($help['aria_label'] ?? ($help['title'] ?? '')));

    if ($summary === '') {
        return '';
    }

    ob_start();
    ?>
    <span class="fc-admin-help">
        <a
            href="<?= e($linkUrl !== '' ? $linkUrl : url('/admin/settings/help/integrations')) ?>"
            class="settings-contact-captcha-hover-help settings-contact-captcha-hover-help--always"
            data-tooltip="<?= e($summary) ?>"
            aria-label="<?= e($ariaLabel) ?>"
        >
            <i class="fas fa-circle-info" aria-hidden="true"></i>
        </a>
    </span>
    <?php

    return (string) ob_get_clean();
};

if ($selectedAdminTheme !== '' && !isset($adminThemes[$selectedAdminTheme])) {
    $adminThemes = [$selectedAdminTheme => $selectedAdminTheme . ' ' . $missingThemeSuffix] + $adminThemes;
}
if ($selectedFrontendTheme !== '' && !isset($frontendThemes[$selectedFrontendTheme])) {
    $frontendThemes = [$selectedFrontendTheme => $selectedFrontendTheme . ' ' . $missingThemeSuffix] + $frontendThemes;
}

$settingsMediaConfig = [
    'apiImagesUrl' => url('/admin/settings/logo-media/files'),
    'uploadUrl' => url('/admin/settings/logo-media/upload'),
    'uploadsBase' => url('/uploads'),
    'csrfToken' => csrf_token(),
    'mode' => 'images',
    'accept' => 'image/*,.ico',
];

$mediaModalPath = BASE_PATH . '/app/Modules/Media/Views/admin/partials/media-modal.php';
$mediaModalScriptPath = BASE_PATH . '/app/Modules/Media/Assets/js/media-modal.js';
$siteBrandingUi = is_array($siteBrandingUi ?? null) ? $siteBrandingUi : [];
$siteBrandingTabs = is_array($siteBrandingUi['tabs'] ?? null) ? $siteBrandingUi['tabs'] : [];
$siteBrandingActiveLocale = (string) ($siteBrandingUi['active_locale'] ?? $selectedDefaultLanguage);
$siteBrandingActiveValues = is_array($siteBrandingUi['active_values'] ?? null) ? $siteBrandingUi['active_values'] : [];
$siteRoutingUi = is_array($siteRoutingUi ?? null) ? $siteRoutingUi : [];
$siteHomepageUi = is_array($siteRoutingUi['homepage'] ?? null) ? $siteRoutingUi['homepage'] : [];
$siteHomepageOptions = is_array($siteHomepageUi['options'] ?? null) ? $siteHomepageUi['options'] : [];
$siteHomepageSummary = is_array($siteHomepageUi['summary'] ?? null) ? $siteHomepageUi['summary'] : null;
$siteHomepageMode = (string) old('homepage_mode', (string) ($siteHomepageUi['mode'] ?? 'native'));
if (!in_array($siteHomepageMode, ['native', 'page'], true)) {
    $siteHomepageMode = 'native';
}
$siteHomepageGroup = (string) old('homepage_page_group', (string) ($siteHomepageUi['ref_group'] ?? ''));
$siteHomepageSummaryMissing = !empty($siteHomepageUi['summary_missing']) && $siteHomepageMode === 'page';
$promoBannerUi = is_array($promoBannerUi ?? null) ? $promoBannerUi : [];
$promoBannerConfig = is_array($promoBannerUi['config'] ?? null) ? $promoBannerUi['config'] : [];
$promoBannerTranslationUi = is_array($promoBannerUi['translation_ui'] ?? null) ? $promoBannerUi['translation_ui'] : [];
$promoBannerTranslationTabs = is_array($promoBannerTranslationUi['tabs'] ?? null) ? $promoBannerTranslationUi['tabs'] : [];
$promoBannerActiveLocale = (string) ($promoBannerTranslationUi['active_locale'] ?? $selectedDefaultLanguage);
$promoBannerSourceLocale = (string) ($promoBannerTranslationUi['source_locale'] ?? $selectedDefaultLanguage);
$promoBannerEnabledValue = (string) old('promo_banner_enabled', !empty($promoBannerConfig['enabled']) ? '1' : '0');
$promoBannerEnabled = in_array($promoBannerEnabledValue, ['1', 'true', 'on', 'yes'], true);
$promoBannerCtaVariant = (string) old('promo_banner_cta_variant', (string) ($promoBannerConfig['cta_variant'] ?? 'primary'));
if (!in_array($promoBannerCtaVariant, ['primary', 'secondary', 'outline', 'ghost'], true)) {
    $promoBannerCtaVariant = 'primary';
}
$promoBannerAlignment = (string) old('promo_banner_alignment', (string) ($promoBannerConfig['alignment'] ?? 'left'));
if (!in_array($promoBannerAlignment, ['left', 'center', 'right'], true)) {
    $promoBannerAlignment = 'left';
}
$promoBannerBackgroundColor = (string) old('promo_banner_background_color', (string) ($promoBannerConfig['background_color'] ?? '#111827'));
$promoBannerTextColor = (string) old('promo_banner_text_color', (string) ($promoBannerConfig['text_color'] ?? '#FFFFFF'));
$siteBrandingInitialTab = $siteBrandingTabs[0] ?? [];
foreach ($siteBrandingTabs as $brandingTabCandidate) {
    if (!empty($brandingTabCandidate['is_active'])) {
        $siteBrandingInitialTab = $brandingTabCandidate;
        break;
    }
}
$siteBrandingInitialUiLabels = is_array($siteBrandingInitialTab['ui_labels'] ?? null) ? $siteBrandingInitialTab['ui_labels'] : [];
?>

<form method="POST" action="<?= url('/admin/settings') ?>" class="settings-form" data-settings-form>
    <?= csrf_field() ?>
    <input type="hidden" name="_settings_tab" value="general" data-settings-active-tab>

    <div class="settings-tabs" data-settings-tabs role="tablist" aria-label="<?= e(__('settings_tabs_aria', 'Settings')) ?>">
        <button type="button" class="settings-tab-btn is-active" data-settings-tab-btn data-tab="general" role="tab" aria-selected="true">
            <span class="settings-tab-icon" aria-hidden="true"><i class="fas fa-sliders-h"></i></span>
            <span class="settings-tab-label"><?= __('tab_general', 'Settings') ?></span>
        </button>
        <button type="button" class="settings-tab-btn" data-settings-tab-btn data-tab="routing" role="tab" aria-selected="false">
            <span class="settings-tab-icon" aria-hidden="true"><i class="fas fa-route"></i></span>
            <span class="settings-tab-label"><?= __('tab_routing', 'Settings') ?></span>
        </button>
        <button type="button" class="settings-tab-btn" data-settings-tab-btn data-tab="localization" role="tab" aria-selected="false">
            <span class="settings-tab-icon" aria-hidden="true"><i class="fas fa-language"></i></span>
            <span class="settings-tab-label"><?= __('tab_localization', 'Settings') ?></span>
        </button>
        <button type="button" class="settings-tab-btn" data-settings-tab-btn data-tab="appearance" role="tab" aria-selected="false">
            <span class="settings-tab-icon" aria-hidden="true"><i class="fas fa-palette"></i></span>
            <span class="settings-tab-label"><?= __('tab_appearance', 'Settings') ?></span>
        </button>
        <button type="button" class="settings-tab-btn" data-settings-tab-btn data-tab="content" role="tab" aria-selected="false">
            <span class="settings-tab-icon" aria-hidden="true"><i class="fas fa-file-alt"></i></span>
            <span class="settings-tab-label"><?= __('tab_content', 'Settings') ?></span>
        </button>
        <button type="button" class="settings-tab-btn" data-settings-tab-btn data-tab="seo" role="tab" aria-selected="false">
            <span class="settings-tab-icon" aria-hidden="true"><i class="fas fa-chart-line"></i></span>
            <span class="settings-tab-label"><?= __('tab_seo', 'Settings') ?></span>
        </button>
        <button type="button" class="settings-tab-btn" data-settings-tab-btn data-tab="mail" role="tab" aria-selected="false">
            <span class="settings-tab-icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
            <span class="settings-tab-label"><?= __('tab_mail', 'Settings') ?></span>
        </button>
        <button type="button" class="settings-tab-btn" data-settings-tab-btn data-tab="integrations" role="tab" aria-selected="false">
            <span class="settings-tab-icon" aria-hidden="true"><i class="fas fa-plug"></i></span>
            <span class="settings-tab-label"><?= __('tab_integrations', 'Settings') ?></span>
        </button>
        <button type="button" class="settings-tab-btn" data-settings-tab-btn data-tab="system" role="tab" aria-selected="false">
            <span class="settings-tab-icon" aria-hidden="true"><i class="fas fa-server"></i></span>
            <span class="settings-tab-label"><?= __('tab_system', 'Settings') ?></span>
        </button>
    </div>

    <div class="settings-tab-panels">
        <section class="settings-tab-panel is-active" data-settings-panel="general" role="tabpanel">
            <div class="card" data-tour-target="settings-branding">
                <div class="settings-section-head">
                    <h3 class="card-title card-title-spaced"><?= __('general', 'Settings') ?></h3>
                    <button
                        type="button"
                        class="btn btn-secondary btn-sm"
                        data-site-branding-open
                        data-tour-target="settings-branding-translations"
                        aria-haspopup="dialog"
                        aria-controls="siteBrandingModal"
                    >
                        <i class="fas fa-language"></i>
                        <?= __('site_branding_translations_open', 'Settings') ?>
                    </button>
                </div>
                <div class="form-group">
                    <label for="site_name" class="form-label"><?= __('site_name', 'Settings') ?></label>
                    <input
                        type="text"
                        id="site_name"
                        name="site_name"
                        class="form-input"
                        value="<?= e((string) ($siteBrandingActiveValues['site_name'] ?? '')) ?>"
                        data-site-branding-main-field="site_name"
                    >
                </div>
                <div class="form-group">
                    <label for="site_description" class="form-label"><?= __('site_description', 'Settings') ?></label>
                    <textarea
                        id="site_description"
                        name="site_description"
                        class="form-input"
                        rows="2"
                        data-no-editor
                        data-site-branding-main-field="site_description"
                    ><?= e((string) ($siteBrandingActiveValues['site_description'] ?? '')) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="site_slogan" class="form-label"><?= __('site_slogan', 'Settings') ?></label>
                    <input
                        type="text"
                        id="site_slogan"
                        name="site_slogan"
                        class="form-input"
                        value="<?= e((string) ($siteBrandingActiveValues['site_slogan'] ?? '')) ?>"
                        placeholder="<?= e(__('site_slogan_placeholder', 'Settings')) ?>"
                        data-site-branding-main-field="site_slogan"
                    >
                    <div class="form-hint"><?= __('site_slogan_hint', 'Settings') ?></div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('topbar_branding_title', 'Settings') ?></label>
                    <div class="settings-inline-toggles">
                        <label class="form-inline">
                            <input type="hidden" name="site_name_enabled" value="0">
                            <input type="checkbox" id="site_name_enabled" class="form-check-input" name="site_name_enabled" value="1" <?= $siteNameEnabled ? 'checked' : '' ?>>
                            <span><?= __('site_name_enabled', 'Settings') ?></span>
                        </label>
                        <label class="form-inline">
                            <input type="hidden" name="site_slogan_enabled" value="0">
                            <input type="checkbox" id="site_slogan_enabled" class="form-check-input" name="site_slogan_enabled" value="1" <?= $siteSloganEnabled ? 'checked' : '' ?>>
                            <span><?= __('site_slogan_enabled', 'Settings') ?></span>
                        </label>
                    </div>
                    <div class="form-hint"><?= __('topbar_branding_hint', 'Settings') ?></div>
                </div>
                <div class="form-group">
                    <label for="site_email" class="form-label"><?= __('site_email', 'Settings') ?></label>
                    <input type="email" id="site_email" name="site_email" class="form-input" value="<?= e($settings['site_email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="site_url" class="form-label"><?= __('site_url', 'Settings') ?></label>
                    <input type="url" id="site_url" name="site_url" class="form-input" value="<?= e($settings['site_url'] ?? '') ?>" placeholder="<?= e(__('site_url_placeholder', 'Settings')) ?>">
                    <div class="form-hint"><?= __('site_url_hint', 'Settings') ?></div>
                </div>

                <div class="form-group settings-guided-tour-group" data-tour-target="settings-guided-tour">
                    <div class="settings-guided-tour-head">
                        <label for="admin_guided_tour_enabled" class="form-label"><?= __('guided_tour_title', 'Settings') ?></label>
                        <label class="form-inline settings-guided-tour-switch">
                            <input type="hidden" name="admin_guided_tour_enabled" value="0">
                            <input type="checkbox" id="admin_guided_tour_enabled" class="form-check-input" name="admin_guided_tour_enabled" value="1" <?= $guidedTourEnabled ? 'checked' : '' ?>>
                            <span><?= __('guided_tour_enabled', 'Settings') ?></span>
                        </label>
                    </div>
                    <div class="form-hint"><?= __('guided_tour_enabled_hint', 'Settings') ?></div>
                    <div class="settings-inline-actions settings-guided-tour-actions">
                        <button type="button" class="btn btn-secondary" data-action="guided-tour-start">
                            <i class="fas fa-route"></i>
                            <?= __('guided_tour_start_now', 'Settings') ?>
                        </button>
                        <button type="button" class="btn btn-outline" data-action="guided-tour-reset">
                            <i class="fas fa-rotate-left"></i>
                            <?= __('guided_tour_reset', 'Settings') ?>
                        </button>
                    </div>
                    <div class="form-hint"><?= __('guided_tour_start_hint', 'Settings') ?></div>
                </div>
            </div>
        </section>

        <section class="settings-tab-panel" data-settings-panel="routing" role="tabpanel" hidden>
            <div class="card">
                <h3 class="card-title card-title-spaced"><?= __('routing_title', 'Settings') ?></h3>

                <div class="form-group">
                    <label for="url_routing_mode" class="form-label"><?= __('routing_mode', 'Settings') ?></label>
                    <select id="url_routing_mode" name="url_routing_mode" class="form-input">
                        <option value="auto" <?= selected('auto', $routingMode) ?>><?= __('routing_mode_auto', 'Settings') ?></option>
                        <option value="pretty" <?= selected('pretty', $routingMode) ?>><?= __('routing_mode_pretty', 'Settings') ?></option>
                        <option value="fallback" <?= selected('fallback', $routingMode) ?>><?= __('routing_mode_fallback', 'Settings') ?></option>
                    </select>
                    <div class="form-hint"><?= __('routing_mode_hint', 'Settings') ?></div>
                </div>

                <?php
                $routingStatusClassMap = [
                    'ok' => 'is-ok',
                    'failed' => 'is-error',
                    'disabled' => 'is-warning',
                    'unknown' => 'is-warning',
                ];
                $routingStatusLabelMap = [
                    'ok' => __('routing_status_ok', 'Settings'),
                    'failed' => __('routing_status_failed', 'Settings'),
                    'disabled' => __('routing_status_disabled', 'Settings'),
                    'unknown' => __('routing_status_unknown', 'Settings'),
                ];
                $routingSupportLabelMap = [
                    'yes' => __('routing_support_yes', 'Settings'),
                    'likely' => __('routing_support_likely', 'Settings'),
                    'no' => __('routing_support_no', 'Settings'),
                    'unknown' => __('routing_support_unknown', 'Settings'),
                ];
                $routingServerLabelMap = [
                    'apache' => __('routing_server_apache', 'Settings'),
                    'nginx' => __('routing_server_nginx', 'Settings'),
                    'iis' => __('routing_server_iis', 'Settings'),
                    'litespeed' => __('routing_server_litespeed', 'Settings'),
                    'unknown' => __('routing_server_unknown', 'Settings'),
                ];
                $routingStatusClass = $routingStatusClassMap[$routingStatus] ?? 'is-warning';
                $routingStatusLabel = $routingStatusLabelMap[$routingStatus] ?? __('routing_status_unknown', 'Settings');
                $routingSupportLabel = $routingSupportLabelMap[$routingSupport] ?? __('routing_support_unknown', 'Settings');
                $routingServerLabel = $routingServerLabelMap[$routingServerType] ?? __('routing_server_unknown', 'Settings');
                ?>

                <div class="settings-routing-grid">
                    <div class="settings-routing-item">
                        <span class="settings-system-stat-label"><?= __('routing_status', 'Settings') ?></span>
                        <span class="settings-status-badge <?= e($routingStatusClass) ?>"><?= e($routingStatusLabel) ?></span>
                    </div>
                    <div class="settings-routing-item">
                        <span class="settings-system-stat-label"><?= __('routing_rewrite_active_now', 'Settings') ?></span>
                        <?php if ($routingActiveNow): ?>
                            <span class="settings-status-badge is-ok"><?= __('routing_yes', 'Settings') ?></span>
                        <?php else: ?>
                            <span class="settings-status-badge is-warning"><?= __('routing_no', 'Settings') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="settings-routing-item">
                        <span class="settings-system-stat-label"><?= __('routing_server_type', 'Settings') ?></span>
                        <span class="settings-system-stat-value"><?= e($routingServerLabel) ?></span>
                    </div>
                    <div class="settings-routing-item">
                        <span class="settings-system-stat-label"><?= __('routing_rewrite_support', 'Settings') ?></span>
                        <span class="settings-system-stat-value"><?= e($routingSupportLabel) ?></span>
                    </div>
                    <div class="settings-routing-item settings-routing-item-wide">
                        <span class="settings-system-stat-label"><?= __('routing_last_check', 'Settings') ?></span>
                        <span class="settings-system-stat-value">
                            <?= $routingLastCheck !== '' ? e($routingLastCheck) : e(__('routing_last_check_none', 'Settings')) ?>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="settings-tab-panel" data-settings-panel="localization" role="tabpanel" hidden>
            <div class="card">
                <h3 class="card-title card-title-spaced"><?= __('tab_localization', 'Settings') ?></h3>
                <div class="form-group">
                    <label for="default_language" class="form-label"><?= __('default_language', 'Settings') ?></label>
                    <select id="default_language" name="default_language" class="form-input">
                        <?php foreach ($languages as $langCode => $langLabel): ?>
                            <option value="<?= e($langCode) ?>" <?= selected($langCode, $selectedDefaultLanguage) ?>><?= e($langLabel) ?> (<?= e($langCode) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint"><?= __('default_language_hint', 'Settings') ?></div>
                    <div class="form-hint"><?= __('default_language_admin_hint', 'Settings') ?></div>
                </div>
                <div class="form-group">
                    <label for="timezone" class="form-label"><?= __('timezone', 'Settings') ?></label>
                    <select id="timezone" name="timezone" class="form-input">
                        <?php foreach ($timezones as $timezone): ?>
                            <option value="<?= e($timezone) ?>" <?= selected($timezone, $selectedTimezone) ?>><?= e($timezone) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint"><?= __('timezone_hint', 'Settings') ?></div>
                </div>
                <div class="form-group">
                    <label for="date_format" class="form-label"><?= __('date_format', 'Settings') ?></label>
                    <select id="date_format" name="date_format" class="form-input">
                        <?php if ($dateFormatValue !== '' && !array_key_exists($dateFormatValue, $dateFormats)): ?>
                            <option value="<?= e($dateFormatValue) ?>" selected><?= e($dateFormatValue) ?></option>
                        <?php endif; ?>
                        <?php foreach ($dateFormats as $format => $preview): ?>
                            <option value="<?= e($format) ?>" <?= selected($format, $dateFormatValue) ?>><?= e($format) ?> (<?= e($preview) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint"><?= __('date_format_hint', 'Settings') ?></div>
                </div>
            </div>
        </section>

        <section class="settings-tab-panel" data-settings-panel="appearance" role="tabpanel" hidden>
            <div class="card">
                <h3 class="card-title card-title-spaced"><?= __('tab_appearance', 'Settings') ?></h3>
                <div class="form-group">
                    <label for="admin_theme" class="form-label"><?= __('admin_theme', 'Settings') ?></label>
                    <select id="admin_theme" name="admin_theme" class="form-input">
                        <?php foreach ($adminThemes as $themeSlug => $themeLabel): ?>
                            <option value="<?= e($themeSlug) ?>" <?= selected($themeSlug, $selectedAdminTheme) ?>><?= e($themeLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint"><?= __('admin_theme_hint', 'Settings') ?></div>
                </div>
                <div class="form-group">
                    <label for="frontend_theme" class="form-label"><?= __('frontend_theme', 'Settings') ?></label>
                    <select id="frontend_theme" name="frontend_theme" class="form-input">
                        <?php foreach ($frontendThemes as $themeSlug => $themeLabel): ?>
                            <option value="<?= e($themeSlug) ?>" <?= selected($themeSlug, $selectedFrontendTheme) ?>><?= e($themeLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint"><?= __('frontend_theme_hint', 'Settings') ?></div>
                </div>
                <div class="settings-media-grid">
                    <div class="form-group settings-media-field" data-settings-media-field data-media-kind="logo">
                        <label for="site_logo" class="form-label"><?= __('site_logo', 'Settings') ?></label>
                        <input type="hidden" name="site_logo_light" value="<?= e($siteLogoLightFieldValue) ?>" data-settings-logo-mode-value="light">
                        <input type="hidden" name="site_logo_dark" value="<?= e($siteLogoDarkFieldValue) ?>" data-settings-logo-mode-value="dark">
                        <input
                            type="text"
                            id="site_logo"
                            class="form-input"
                            value="<?= e($siteLogoFieldValue) ?>"
                            placeholder=""
                            data-settings-media-input
                            data-settings-logo-active-input
                        >
                        <div class="settings-media-actions">
                            <button type="button" class="btn btn-secondary btn-sm" data-settings-media-open>
                                <i class="fas fa-photo-film"></i>
                                <?= __('site_media_open', 'Settings') ?>
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" data-settings-media-clear>
                                <i class="fas fa-eraser"></i>
                                <?= __('site_media_clear', 'Settings') ?>
                            </button>
                        </div>
                        <div class="settings-media-preview" data-settings-media-preview hidden>
                            <img src="" alt="" class="settings-media-preview-image" data-settings-media-image hidden>
                        </div>
                        <div class="settings-media-options-grid">
                            <div class="settings-media-variant-group">
                                <label for="site_logo_variant" class="form-label"><?= __('site_logo_variant', 'Settings') ?></label>
                                <select id="site_logo_variant" name="site_logo_variant" class="form-input">
                                    <option value="compact" <?= selected('compact', $siteLogoVariant) ?>><?= __('site_logo_variant_compact', 'Settings') ?></option>
                                    <option value="banner" <?= selected('banner', $siteLogoVariant) ?>><?= __('site_logo_variant_banner', 'Settings') ?></option>
                                    <option value="banner_framed" <?= selected('banner_framed', $siteLogoVariant) ?>><?= __('site_logo_variant_banner_framed', 'Settings') ?></option>
                                </select>
                                <div class="form-hint"><?= __('site_logo_variant_hint', 'Settings') ?></div>
                            </div>
                            <div class="settings-media-variant-group">
                                <label for="site_logo_mode" class="form-label"><?= __('site_logo_mode', 'Settings') ?></label>
                                <select id="site_logo_mode" name="site_logo_mode" class="form-input" data-settings-logo-mode>
                                    <option value="light" <?= selected('light', $siteLogoAppearanceMode) ?>><?= __('site_logo_mode_light', 'Settings') ?></option>
                                    <option value="dark" <?= selected('dark', $siteLogoAppearanceMode) ?>><?= __('site_logo_mode_dark', 'Settings') ?></option>
                                </select>
                                <div class="form-hint"><?= __('site_logo_mode_hint', 'Settings') ?></div>
                            </div>
                        </div>
                        <div class="form-hint"><?= __('site_logo_hint', 'Settings') ?></div>
                        <div class="form-hint"><?= __('site_logo_topbar_size_hint', 'Settings') ?></div>
                    </div>
                    <div class="form-group settings-media-field" data-settings-media-field data-media-kind="favicon">
                        <label for="site_favicon" class="form-label"><?= __('site_favicon', 'Settings') ?></label>
                        <input
                            type="text"
                            id="site_favicon"
                            name="site_favicon"
                            class="form-input"
                            value="<?= e($siteFaviconFieldValue) ?>"
                            placeholder=""
                            data-settings-media-input
                        >
                        <div class="settings-media-actions">
                            <button type="button" class="btn btn-secondary btn-sm" data-settings-media-open>
                                <i class="fas fa-photo-film"></i>
                                <?= __('site_media_open', 'Settings') ?>
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" data-settings-media-clear>
                                <i class="fas fa-eraser"></i>
                                <?= __('site_media_clear', 'Settings') ?>
                            </button>
                        </div>
                        <div class="settings-media-preview settings-media-preview-favicon" data-settings-media-preview hidden>
                            <img src="" alt="" class="settings-media-preview-image settings-media-preview-image-favicon" data-settings-media-image hidden>
                        </div>
                        <div class="form-hint"><?= __('site_favicon_hint', 'Settings') ?></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="settings-tab-panel" data-settings-panel="content" role="tabpanel" hidden>
            <div class="card">
                <h3 class="card-title card-title-spaced"><?= __('tab_content', 'Settings') ?></h3>
                <div class="form-group">
                    <label for="posts_per_page" class="form-label"><?= __('posts_per_page', 'Settings') ?></label>
                    <input type="number" id="posts_per_page" name="posts_per_page" class="form-input" value="<?= e($postsPerPageValue) ?>" min="1" max="50">
                    <div class="form-hint"><?= __('posts_per_page_hint', 'Settings') ?></div>
                </div>

                <div class="form-group">
                    <div class="settings-guided-tour-head">
                        <label for="page_header_enabled" class="form-label"><?= __('page_header_title', 'Settings') ?></label>
                        <label class="form-inline settings-guided-tour-switch">
                            <input type="hidden" name="page_header_enabled" value="0">
                            <input
                                type="checkbox"
                                id="page_header_enabled"
                                class="form-check-input"
                                name="page_header_enabled"
                                value="1"
                                <?= $pageHeaderEnabled ? 'checked' : '' ?>
                            >
                            <span><?= __('page_header_enabled', 'Settings') ?></span>
                        </label>
                    </div>
                    <div class="form-hint"><?= __('page_header_enabled_hint', 'Settings') ?></div>
                </div>

                <div class="settings-subsection settings-homepage-section">
                    <div class="settings-subsection-head">
                        <h4 class="settings-subsection-title"><?= __('homepage_title', 'Settings') ?></h4>
                    </div>

                    <div class="form-group">
                        <label for="homepage_mode" class="form-label"><?= __('homepage_mode', 'Settings') ?></label>
                        <select id="homepage_mode" name="homepage_mode" class="form-input" data-homepage-mode>
                            <option value="native" <?= selected('native', $siteHomepageMode) ?>><?= __('homepage_mode_native', 'Settings') ?></option>
                            <option value="page" <?= selected('page', $siteHomepageMode) ?>><?= __('homepage_mode_page', 'Settings') ?></option>
                        </select>
                        <div class="form-hint"><?= __('homepage_mode_hint', 'Settings') ?></div>
                    </div>

                    <div class="form-group" data-homepage-page-field <?= $siteHomepageMode === 'page' ? '' : 'hidden' ?>>
                        <label for="homepage_page_group" class="form-label"><?= __('homepage_page', 'Settings') ?></label>
                        <select id="homepage_page_group" name="homepage_page_group" class="form-input">
                            <option value=""><?= __('homepage_page_placeholder', 'Settings') ?></option>
                            <?php foreach ($siteHomepageOptions as $homepageOption): ?>
                                <?php
                                    $homepageGroup = trim((string) ($homepageOption['translation_group'] ?? ''));
                                    if ($homepageGroup === '') {
                                        continue;
                                    }
                                    $homepageTitle = trim((string) ($homepageOption['title'] ?? ''));
                                    $homepageSlug = trim((string) ($homepageOption['slug'] ?? ''));
                                    $homepageLocaleLabel = trim((string) ($homepageOption['locale_label'] ?? ''));
                                    $optionLabel = $homepageTitle;
                                    if ($homepageSlug !== '') {
                                        $optionLabel .= ' · ' . $homepageSlug;
                                    }
                                    if ($homepageLocaleLabel !== '') {
                                        $optionLabel .= ' · ' . $homepageLocaleLabel;
                                    }
                                ?>
                                <option value="<?= e($homepageGroup) ?>" <?= selected($homepageGroup, $siteHomepageGroup) ?>><?= e($optionLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint"><?= __('homepage_page_hint', 'Settings') ?></div>
                    </div>

                    <div class="settings-homepage-summary">
                        <div class="settings-homepage-summary-title"><?= __('homepage_summary_title', 'Settings') ?></div>
                        <div class="settings-homepage-summary-grid">
                            <div class="settings-homepage-summary-row">
                                <span class="settings-system-stat-label"><?= __('homepage_summary_mode', 'Settings') ?></span>
                                <strong class="settings-system-stat-value">
                                    <?= $siteHomepageMode === 'page' ? __('homepage_mode_page', 'Settings') : __('homepage_mode_native', 'Settings') ?>
                                </strong>
                            </div>
                            <div class="settings-homepage-summary-row">
                                <span class="settings-system-stat-label"><?= __('homepage_summary_target', 'Settings') ?></span>
                                <strong class="settings-system-stat-value">
                                    <?php if ($siteHomepageMode !== 'page'): ?>
                                        <?= __('homepage_summary_native', 'Settings') ?>
                                    <?php elseif ($siteHomepageSummaryMissing): ?>
                                        <?= __('homepage_target_missing', 'Settings') ?>
                                    <?php elseif (is_array($siteHomepageSummary)): ?>
                                        <?= e((string) ($siteHomepageSummary['title'] ?? '')) ?>
                                    <?php else: ?>
                                        <?= __('homepage_summary_none', 'Settings') ?>
                                    <?php endif; ?>
                                </strong>
                            </div>
                            <div class="settings-homepage-summary-row">
                                <span class="settings-system-stat-label"><?= __('homepage_summary_locale', 'Settings') ?></span>
                                <strong class="settings-system-stat-value">
                                    <?= is_array($siteHomepageSummary) ? e((string) ($siteHomepageSummary['locale_label'] ?? '')) : __('homepage_summary_none', 'Settings') ?>
                                </strong>
                            </div>
                            <div class="settings-homepage-summary-row">
                                <span class="settings-system-stat-label"><?= __('homepage_summary_editor', 'Settings') ?></span>
                                <strong class="settings-system-stat-value">
                                    <?php if (!is_array($siteHomepageSummary)): ?>
                                        <?= __('homepage_summary_none', 'Settings') ?>
                                    <?php else: ?>
                                        <?= (string) ($siteHomepageSummary['editor_mode'] ?? 'classic') === 'builder'
                                            ? __('homepage_editor_builder', 'Settings')
                                            : __('homepage_editor_classic', 'Settings') ?>
                                    <?php endif; ?>
                                </strong>
                            </div>
                        </div>
                        <div class="form-hint"><?= __('homepage_canonical_hint', 'Settings') ?></div>
                    </div>
                </div>

                <div class="settings-subsection settings-promo-banner-section">
                    <div class="settings-subsection-head">
                        <h4 class="settings-subsection-title"><?= __('promo_banner_title', 'Settings') ?></h4>
                    </div>

                    <input
                        type="hidden"
                        name="promo_banner_active_locale"
                        value="<?= e($promoBannerActiveLocale) ?>"
                        data-promo-banner-active-locale
                    >
                    <input type="hidden" name="promo_banner_source_locale" value="<?= e($promoBannerSourceLocale) ?>">

                    <div class="form-group">
                        <label class="form-inline">
                            <input type="hidden" name="promo_banner_enabled" value="0">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                name="promo_banner_enabled"
                                value="1"
                                <?= $promoBannerEnabled ? 'checked' : '' ?>
                            >
                            <?= __('promo_banner_enabled', 'Settings') ?>
                        </label>
                        <div class="form-hint"><?= __('promo_banner_enabled_hint', 'Settings') ?></div>
                    </div>

                    <div class="settings-branding-translation-bar promo-banner-translation-bar" data-promo-banner-translations-root>
                        <div class="settings-branding-translation-tabs promo-banner-translation-tabs" role="tablist" aria-label="<?= e(__('promo_banner_translations', 'Settings')) ?>">
                            <?php foreach ($promoBannerTranslationTabs as $tab): ?>
                            <?php
                                $tabClasses = ['settings-branding-translation-tab', 'promo-banner-translation-tab'];
                                $labels = is_array($tab['ui_labels'] ?? null) ? $tab['ui_labels'] : [];
                                if (!empty($tab['is_active'])) {
                                    $tabClasses[] = 'is-active';
                                }
                                if ((string) ($tab['status'] ?? '') === 'empty') {
                                    $tabClasses[] = 'is-missing';
                                }
                                $tabBadge = !empty($tab['is_source'])
                                    ? __('translation_source', 'Settings')
                                    : (((string) ($tab['status'] ?? '') === 'translated') ? __('translation_ready', 'Settings') : __('translation_missing', 'Settings'));
                            ?>
                            <button
                                type="button"
                                class="<?= e(implode(' ', $tabClasses)) ?>"
                                data-promo-banner-tab-btn
                                data-tab="<?= e((string) ($tab['code'] ?? '')) ?>"
                                data-tab-state="<?= e(!empty($tab['is_source']) ? 'source' : (((string) ($tab['status'] ?? '') === 'translated') ? 'ready' : 'missing')) ?>"
                                data-promo-banner-label-source="<?= e((string) ($labels['translation_source'] ?? __('translation_source', 'Settings'))) ?>"
                                data-promo-banner-label-ready="<?= e((string) ($labels['translation_ready'] ?? __('translation_ready', 'Settings'))) ?>"
                                data-promo-banner-label-missing="<?= e((string) ($labels['translation_missing'] ?? __('translation_missing', 'Settings'))) ?>"
                                role="tab"
                                aria-selected="<?= !empty($tab['is_active']) ? 'true' : 'false' ?>"
                                title="<?= e((string) ($tab['label'] ?? '')) ?>"
                            >
                                <span class="settings-branding-translation-tab-icon" aria-hidden="true">
                                    <span class="settings-branding-translation-flag"><?= e((string) ($tab['flag'] ?? '🏳️')) ?></span>
                                </span>
                                <span class="settings-branding-translation-tab-badge promo-banner-translation-tab-badge"><?= e($tabBadge) ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="promo-banner-translation-panels">
                        <?php foreach ($promoBannerTranslationTabs as $tab): ?>
                        <?php
                            $localeCode = (string) ($tab['code'] ?? '');
                            $values = is_array($tab['values'] ?? null) ? $tab['values'] : [];
                            $labels = is_array($tab['form_labels'] ?? null) ? $tab['form_labels'] : [];
                        ?>
                        <section
                            class="promo-banner-translation-panel<?= !empty($tab['is_active']) ? ' is-active' : '' ?>"
                            data-promo-banner-panel="<?= e($localeCode) ?>"
                            role="tabpanel"
                            <?= !empty($tab['is_active']) ? '' : 'hidden' ?>
                        >
                            <div class="form-group">
                                <label for="promo_banner_<?= e($localeCode) ?>_text" class="form-label"><?= e((string) ($labels['promo_banner_text'] ?? __('promo_banner_text', 'Settings'))) ?></label>
                                <textarea
                                    id="promo_banner_<?= e($localeCode) ?>_text"
                                    name="promo_banner_translations[<?= e($localeCode) ?>][text]"
                                    class="form-input"
                                    rows="2"
                                    data-no-editor
                                ><?= e((string) ($values['text'] ?? '')) ?></textarea>
                                <div class="form-hint"><?= e((string) ($labels['promo_banner_text_hint'] ?? __('promo_banner_text_hint', 'Settings'))) ?></div>
                            </div>

                            <div class="settings-promo-banner-grid">
                                <div class="form-group">
                                    <label for="promo_banner_<?= e($localeCode) ?>_cta_label" class="form-label"><?= e((string) ($labels['promo_banner_cta_label'] ?? __('promo_banner_cta_label', 'Settings'))) ?></label>
                                    <input
                                        type="text"
                                        id="promo_banner_<?= e($localeCode) ?>_cta_label"
                                        name="promo_banner_translations[<?= e($localeCode) ?>][cta_label]"
                                        class="form-input"
                                        value="<?= e((string) ($values['cta_label'] ?? '')) ?>"
                                    >
                                    <div class="form-hint"><?= e((string) ($labels['promo_banner_cta_label_hint'] ?? __('promo_banner_cta_label_hint', 'Settings'))) ?></div>
                                </div>

                                <div class="form-group">
                                    <label for="promo_banner_<?= e($localeCode) ?>_cta_url" class="form-label"><?= e((string) ($labels['promo_banner_cta_url'] ?? __('promo_banner_cta_url', 'Settings'))) ?></label>
                                    <input
                                        type="text"
                                        id="promo_banner_<?= e($localeCode) ?>_cta_url"
                                        name="promo_banner_translations[<?= e($localeCode) ?>][cta_url]"
                                        class="form-input"
                                        value="<?= e((string) ($values['cta_url'] ?? '')) ?>"
                                    >
                                    <div class="form-hint"><?= e((string) ($labels['promo_banner_cta_url_hint'] ?? __('promo_banner_cta_url_hint', 'Settings'))) ?></div>
                                </div>
                            </div>
                        </section>
                        <?php endforeach; ?>
                    </div>

                    <div class="settings-promo-banner-grid">
                        <div class="form-group">
                            <label class="form-label"><?= __('promo_banner_alignment', 'Settings') ?></label>
                            <div class="settings-align-control" data-align-control role="radiogroup" aria-label="<?= e(__('promo_banner_alignment', 'Settings')) ?>">
                                <label class="settings-align-option<?= $promoBannerAlignment === 'left' ? ' is-active' : '' ?>" title="<?= e(__('promo_banner_alignment_left', 'Settings')) ?>">
                                    <input
                                        type="radio"
                                        name="promo_banner_alignment"
                                        value="left"
                                        class="settings-align-option-input"
                                        <?= checked('left', $promoBannerAlignment) ?>
                                    >
                                    <i class="fas fa-align-left" aria-hidden="true"></i>
                                    <span class="sr-only"><?= __('promo_banner_alignment_left', 'Settings') ?></span>
                                </label>
                                <label class="settings-align-option<?= $promoBannerAlignment === 'center' ? ' is-active' : '' ?>" title="<?= e(__('promo_banner_alignment_center', 'Settings')) ?>">
                                    <input
                                        type="radio"
                                        name="promo_banner_alignment"
                                        value="center"
                                        class="settings-align-option-input"
                                        <?= checked('center', $promoBannerAlignment) ?>
                                    >
                                    <i class="fas fa-align-center" aria-hidden="true"></i>
                                    <span class="sr-only"><?= __('promo_banner_alignment_center', 'Settings') ?></span>
                                </label>
                                <label class="settings-align-option<?= $promoBannerAlignment === 'right' ? ' is-active' : '' ?>" title="<?= e(__('promo_banner_alignment_right', 'Settings')) ?>">
                                    <input
                                        type="radio"
                                        name="promo_banner_alignment"
                                        value="right"
                                        class="settings-align-option-input"
                                        <?= checked('right', $promoBannerAlignment) ?>
                                    >
                                    <i class="fas fa-align-right" aria-hidden="true"></i>
                                    <span class="sr-only"><?= __('promo_banner_alignment_right', 'Settings') ?></span>
                                </label>
                            </div>
                            <div class="form-hint"><?= __('promo_banner_alignment_hint', 'Settings') ?></div>
                        </div>

                        <div class="form-group">
                            <label for="promo_banner_cta_variant" class="form-label"><?= __('promo_banner_cta_variant', 'Settings') ?></label>
                            <select id="promo_banner_cta_variant" name="promo_banner_cta_variant" class="form-input">
                                <option value="primary" <?= selected('primary', $promoBannerCtaVariant) ?>><?= __('promo_banner_cta_variant_primary', 'Settings') ?></option>
                                <option value="secondary" <?= selected('secondary', $promoBannerCtaVariant) ?>><?= __('promo_banner_cta_variant_secondary', 'Settings') ?></option>
                                <option value="outline" <?= selected('outline', $promoBannerCtaVariant) ?>><?= __('promo_banner_cta_variant_outline', 'Settings') ?></option>
                                <option value="ghost" <?= selected('ghost', $promoBannerCtaVariant) ?>><?= __('promo_banner_cta_variant_ghost', 'Settings') ?></option>
                            </select>
                            <div class="form-hint"><?= __('promo_banner_cta_variant_hint', 'Settings') ?></div>
                        </div>
                    </div>

                    <div class="settings-promo-banner-grid">
                        <div class="form-group">
                            <label for="promo_banner_background_color" class="form-label"><?= __('promo_banner_background_color', 'Settings') ?></label>
                            <input
                                type="color"
                                id="promo_banner_background_color"
                                name="promo_banner_background_color"
                                class="settings-color-input"
                                value="<?= e($promoBannerBackgroundColor) ?>"
                            >
                            <div class="form-hint"><?= __('promo_banner_background_color_hint', 'Settings') ?></div>
                        </div>

                        <div class="form-group">
                            <label for="promo_banner_text_color" class="form-label"><?= __('promo_banner_text_color', 'Settings') ?></label>
                            <input
                                type="color"
                                id="promo_banner_text_color"
                                name="promo_banner_text_color"
                                class="settings-color-input"
                                value="<?= e($promoBannerTextColor) ?>"
                            >
                            <div class="form-hint"><?= __('promo_banner_text_color_hint', 'Settings') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="settings-tab-panel" data-settings-panel="seo" role="tabpanel" hidden>
            <div class="card">
                <h3 class="card-title card-title-spaced"><?= __('seo', 'Settings') ?></h3>
                <div class="form-group">
                    <label for="meta_title" class="form-label"><?= __('meta_title', 'Settings') ?></label>
                    <input type="text" id="meta_title" name="meta_title" class="form-input" value="<?= e($settings['meta_title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="meta_description" class="form-label"><?= __('meta_description', 'Settings') ?></label>
                    <textarea id="meta_description" name="meta_description" class="form-input" rows="2" data-no-editor><?= e($settings['meta_description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="meta_keywords" class="form-label"><?= __('meta_keywords', 'Settings') ?></label>
                    <input type="text" id="meta_keywords" name="meta_keywords" class="form-input" value="<?= e($settings['meta_keywords'] ?? '') ?>">
                </div>
            </div>
        </section>

        <section class="settings-tab-panel" data-settings-panel="mail" role="tabpanel" hidden>
            <div class="card">
                <h3 class="card-title card-title-spaced"><?= __('mail', 'Settings') ?></h3>

                <div class="form-group">
                    <label for="mail_transport" class="form-label"><?= __('mail_transport', 'Settings') ?></label>
                    <?php $transport = (string) ($settings['mail_transport'] ?? 'mail'); ?>
                    <select id="mail_transport" name="mail_transport" class="form-input">
                        <option value="mail" <?= selected('mail', $transport) ?>><?= __('mail_transport_mail', 'Settings') ?></option>
                        <option value="smtp" <?= selected('smtp', $transport) ?>><?= __('mail_transport_smtp', 'Settings') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mail_from_address" class="form-label"><?= __('mail_from_address', 'Settings') ?></label>
                    <input type="email" id="mail_from_address" name="mail_from_address" class="form-input" value="<?= e($settings['mail_from_address'] ?? '') ?>" placeholder="<?= e(__('mail_from_address_placeholder', 'Settings')) ?>">
                    <div class="form-hint"><?= __('mail_from_address_hint', 'Settings') ?></div>
                </div>

                <div class="form-group">
                    <label for="mail_from_name" class="form-label"><?= __('mail_from_name', 'Settings') ?></label>
                    <input type="text" id="mail_from_name" name="mail_from_name" class="form-input" value="<?= e($settings['mail_from_name'] ?? '') ?>" placeholder="<?= e($settings['site_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="mail_smtp_host" class="form-label"><?= __('mail_smtp_host', 'Settings') ?></label>
                    <input type="text" id="mail_smtp_host" name="mail_smtp_host" class="form-input" value="<?= e($settings['mail_smtp_host'] ?? '') ?>" placeholder="<?= e(__('mail_smtp_host_placeholder', 'Settings')) ?>">
                </div>

                <div class="form-group">
                    <label for="mail_smtp_port" class="form-label"><?= __('mail_smtp_port', 'Settings') ?></label>
                    <input type="number" id="mail_smtp_port" name="mail_smtp_port" class="form-input" value="<?= e((string) ($settings['mail_smtp_port'] ?? '587')) ?>" min="1" max="65535">
                </div>

                <div class="form-group">
                    <label for="mail_smtp_encryption" class="form-label"><?= __('mail_smtp_encryption', 'Settings') ?></label>
                    <?php $enc = (string) ($settings['mail_smtp_encryption'] ?? 'tls'); ?>
                    <select id="mail_smtp_encryption" name="mail_smtp_encryption" class="form-input">
                        <option value="tls" <?= selected('tls', $enc) ?>><?= __('mail_smtp_encryption_tls', 'Settings') ?></option>
                        <option value="ssl" <?= selected('ssl', $enc) ?>><?= __('mail_smtp_encryption_ssl', 'Settings') ?></option>
                        <option value="" <?= selected('', $enc) ?>><?= __('mail_smtp_encryption_none', 'Settings') ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mail_smtp_username" class="form-label"><?= __('mail_smtp_username', 'Settings') ?></label>
                    <input type="text" id="mail_smtp_username" name="mail_smtp_username" class="form-input" value="<?= e($settings['mail_smtp_username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="mail_smtp_password" class="form-label"><?= __('mail_smtp_password', 'Settings') ?></label>
                    <?= form_password('mail_smtp_password', [
                        'placeholder' => __('mail_smtp_password_placeholder', 'Settings'),
                        'autocomplete' => 'new-password',
                    ]) ?>
                    <div class="form-hint"><?= __('mail_smtp_password_hint', 'Settings') ?></div>
                    <label class="form-inline">
                        <input type="checkbox" class="form-check-input" name="mail_smtp_password_clear" value="1">
                        <?= __('mail_smtp_password_clear', 'Settings') ?>
                    </label>
                </div>

                <div class="form-group">
                    <label for="mail_test_to" class="form-label"><?= __('mail_test_to', 'Settings') ?></label>
                    <input type="email" id="mail_test_to" name="mail_test_to" class="form-input" value="" placeholder="<?= e(__('mail_test_to_placeholder', 'Settings')) ?>">
                    <div class="form-hint"><?= __('mail_test_hint', 'Settings') ?></div>
                </div>

                <hr class="settings-mail-separator">
                <h4 class="card-title card-title-spaced"><?= __('mail_contact_notifications_title', 'Settings') ?></h4>
                <div class="form-hint"><?= __('mail_contact_notifications_hint', 'Settings') ?></div>

                <div class="form-group">
                    <label class="form-inline">
                        <input type="hidden" name="contact_notification_enabled" value="0">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            name="contact_notification_enabled"
                            value="1"
                            <?= ((int) ($settings['contact_notification_enabled'] ?? 1) === 1) ? 'checked' : '' ?>
                        >
                        <?= __('contact_notification_enabled', 'Settings') ?>
                    </label>
                    <div class="form-hint"><?= __('contact_notification_enabled_hint', 'Settings') ?></div>
                </div>

                <div class="form-group">
                    <label for="contact_notification_email" class="form-label"><?= __('contact_notification_email', 'Settings') ?></label>
                    <input
                        type="email"
                        id="contact_notification_email"
                        name="contact_notification_email"
                        class="form-input"
                        value="<?= e((string) ($settings['contact_notification_email'] ?? '')) ?>"
                        placeholder="<?= e(__('contact_notification_email_placeholder', 'Settings')) ?>"
                    >
                    <div class="form-hint"><?= __('contact_notification_email_hint', 'Settings') ?></div>
                </div>

                <div
                    class="form-group settings-contact-captcha-group<?= $turnstileEnabledGlobal ? '' : ' is-disabled' ?>"
                    data-contact-captcha-group
                    data-hint-default="<?= e(__('contact_captcha_enabled_hint', 'Settings')) ?>"
                    data-hint-disabled="<?= e(__('contact_captcha_disabled_by_turnstile', 'Settings')) ?>"
                    data-tooltip-disabled="<?= e(__('contact_captcha_disabled_by_turnstile_tooltip', 'Settings')) ?>"
                >
                    <label
                        class="form-inline"
                        data-contact-captcha-label
                        <?= $turnstileEnabledGlobal ? '' : ('title="' . e(__('contact_captcha_disabled_by_turnstile_tooltip', 'Settings')) . '"') ?>
                    >
                        <input type="hidden" name="contact_enable_captcha" value="0">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            name="contact_enable_captcha"
                            value="1"
                            <?= ((int) ($settings['contact_enable_captcha'] ?? 0) === 1) ? 'checked' : '' ?>
                            <?= $turnstileEnabledGlobal ? '' : 'disabled' ?>
                        >
                        <?= __('contact_captcha_enabled', 'Settings') ?>
                        <span
                            class="settings-contact-captcha-hover-help"
                            data-contact-captcha-hover-help
                            data-tooltip="<?= e(__('contact_captcha_disabled_by_turnstile_tooltip', 'Settings')) ?>"
                            aria-label="<?= e(__('contact_captcha_disabled_by_turnstile_tooltip', 'Settings')) ?>"
                            tabindex="0"
                        >
                            <i class="fas fa-circle-info" aria-hidden="true"></i>
                        </span>
                    </label>
                    <div class="form-hint" data-contact-captcha-hint>
                        <?= $turnstileEnabledGlobal ? __('contact_captcha_enabled_hint', 'Settings') : __('contact_captcha_disabled_by_turnstile', 'Settings') ?>
                    </div>
                </div>

                <div class="settings-inline-actions">
                    <button type="submit" class="btn btn-secondary" name="send_test_email" value="1"><?= __('mail_test_button', 'Settings') ?></button>
                </div>
            </div>
        </section>

        <section class="settings-tab-panel" data-settings-panel="integrations" role="tabpanel" hidden>
            <div class="card">
                <h3 class="card-title card-title-spaced"><?= __('integrations_title', 'Settings') ?></h3>
                <div class="form-hint settings-integrations-hint"><?= __('integrations_hint', 'Settings') ?></div>

                <div class="settings-integrations-meta">
                    <span class="settings-system-stat-label"><?= __('integrations_env_path', 'Settings') ?></span>
                    <code class="settings-path-code"><?= e($integrationEnvPath) ?></code>
                    <?php if ($integrationEnvWritable): ?>
                        <span class="settings-status-badge is-ok"><?= __('status_ok', 'Settings') ?></span>
                    <?php else: ?>
                        <span class="settings-status-badge is-warning"><?= __('status_not_writable', 'Settings') ?></span>
                    <?php endif; ?>
                </div>

                <div class="settings-integrations-grid module-card-list" data-settings-integrations-accordion>
                    <section class="settings-integrations-group module-card" data-settings-integration-card>
                        <div class="settings-integrations-group-toggle module-card-header" data-settings-integration-toggle>
                            <div class="module-card-info">
                                <div class="module-card-icon settings-integrations-group-icon">
                                    <i class="fas fa-brain"></i>
                                </div>
                                <div class="module-card-text">
                                    <h4 class="module-card-title settings-integrations-group-title"><?= __('integrations_group_ai', 'Settings') ?></h4>
                                </div>
                            </div>
                            <div class="module-card-summary">
                                <i class="fas fa-chevron-down module-card-chevron settings-integrations-group-chevron" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="module-card-content" data-settings-integration-content>
                            <div class="module-card-body settings-integrations-group-body">
                                <div class="form-group">
                                    <div class="form-hint"><?= __('integrations_ai_hint', 'Settings') ?></div>
                                </div>

                                <div class="settings-system-overview settings-integrations-status-overview">
                                    <div class="settings-system-stat">
                                        <span class="settings-system-stat-label"><?= __('integrations_ai_provider_label', 'Settings') ?></span>
                                        <div class="settings-system-stat-value"><?= e($aiProviderLabel) ?></div>
                                    </div>
                                    <div class="settings-system-stat">
                                        <span class="settings-system-stat-label"><?= __('integrations_ai_configuration_label', 'Settings') ?></span>
                                        <div class="settings-system-stat-value">
                                            <span class="settings-status-badge <?= $aiConfigured ? 'is-ok' : 'is-warning' ?>">
                                                <?= $aiConfigured ? __('status_ok', 'Settings') : __('status_missing', 'Settings') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="settings-system-stat">
                                        <span class="settings-system-stat-label"><?= __('integrations_ai_transport_label', 'Settings') ?></span>
                                        <div class="settings-system-stat-value">
                                            <span class="settings-status-badge <?= $aiTransportReady ? 'is-ok' : 'is-warning' ?>">
                                                <?= $aiTransportReady ? __('status_ok', 'Settings') : __('status_missing', 'Settings') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="settings-system-stat">
                                        <span class="settings-system-stat-label"><?= __('integrations_ai_tools_label', 'Settings') ?></span>
                                        <div class="settings-system-stat-value"><?= e((string) $aiToolCount) ?></div>
                                    </div>
                                </div>

                                <div class="settings-integrations-ai-support">
                                    <div class="settings-integrations-ai-support-item">
                                        <span class="settings-system-stat-label"><?= __('integrations_ai_responses_api_label', 'Settings') ?></span>
                                        <span class="settings-status-badge is-ok"><?= __('status_ok', 'Settings') ?></span>
                                    </div>
                                    <div class="settings-integrations-ai-support-item">
                                        <span class="settings-system-stat-label"><?= __('integrations_ai_endpoint_label', 'Settings') ?></span>
                                        <code class="settings-path-code"><?= e((string) ($aiProviderStatus['endpoint'] ?? '')) ?></code>
                                    </div>
                                    <div class="settings-integrations-ai-support-item">
                                        <span class="settings-system-stat-label"><?= __('integrations_ai_tools_support_label', 'Settings') ?></span>
                                        <span class="settings-status-badge <?= $aiSupportsTools ? 'is-ok' : 'is-warning' ?>">
                                            <?= $aiSupportsTools ? __('status_ok', 'Settings') : __('status_missing', 'Settings') ?>
                                        </span>
                                    </div>
                                    <div class="settings-integrations-ai-support-item">
                                        <span class="settings-system-stat-label"><?= __('integrations_openai_responses_model', 'Settings') ?></span>
                                        <span class="settings-integrations-ai-support-value"><?= e((string) ($aiProviderStatus['model'] ?? '')) ?></span>
                                    </div>
                                </div>

                                <?php if ($aiIssues !== []): ?>
                                    <div class="settings-integrations-ai-issues">
                                        <div class="settings-system-stat-label"><?= __('integrations_ai_issues_label', 'Settings') ?></div>
                                        <ul class="settings-integrations-ai-issues-list">
                                            <?php foreach ($aiIssues as $aiIssue): ?>
                                                <li><?= e($aiIssue) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <div class="fc-admin-help-row">
                                        <label for="env_openai_api_key" class="form-label"><?= __('integrations_openai_api_key', 'Settings') ?></label>
                                        <?= $renderIntegrationHelp('OPENAI_API_KEY') ?>
                                    </div>
                                    <input type="text" id="env_openai_api_key" name="env[OPENAI_API_KEY]" class="form-input" value="<?= e((string) ($integrationValues['OPENAI_API_KEY'] ?? '')) ?>" placeholder="<?= e(__('integrations_openai_api_key_placeholder', 'Settings')) ?>" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                    <div class="form-hint"><?= __('integrations_openai_api_key_hint', 'Settings') ?></div>
                                </div>

                                <div class="form-group">
                                    <div class="fc-admin-help-row">
                                        <label for="env_openai_base_url" class="form-label"><?= __('integrations_openai_base_url', 'Settings') ?></label>
                                        <?= $renderIntegrationHelp('OPENAI_API_BASE_URL') ?>
                                    </div>
                                    <input type="url" id="env_openai_base_url" name="env[OPENAI_API_BASE_URL]" class="form-input" value="<?= e((string) ($integrationValues['OPENAI_API_BASE_URL'] ?? '')) ?>" placeholder="<?= e(__('integrations_openai_base_url_placeholder', 'Settings')) ?>" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                    <div class="form-hint"><?= __('integrations_openai_base_url_hint', 'Settings') ?></div>
                                </div>

                                <div class="form-group">
                                    <div class="fc-admin-help-row">
                                        <label for="env_openai_responses_model" class="form-label"><?= __('integrations_openai_responses_model', 'Settings') ?></label>
                                        <?= $renderIntegrationHelp('OPENAI_RESPONSES_MODEL') ?>
                                    </div>
                                    <input type="text" id="env_openai_responses_model" name="env[OPENAI_RESPONSES_MODEL]" class="form-input" value="<?= e((string) ($integrationValues['OPENAI_RESPONSES_MODEL'] ?? '')) ?>" placeholder="<?= e(__('integrations_openai_responses_model_placeholder', 'Settings')) ?>" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                    <div class="form-hint"><?= __('integrations_openai_responses_model_hint', 'Settings') ?></div>
                                </div>

                                <div class="settings-promo-banner-grid">
                                    <div class="form-group">
                                        <div class="fc-admin-help-row">
                                            <label for="env_openai_timeout" class="form-label"><?= __('integrations_openai_timeout', 'Settings') ?></label>
                                            <?= $renderIntegrationHelp('OPENAI_TIMEOUT') ?>
                                        </div>
                                        <input type="number" id="env_openai_timeout" name="env[OPENAI_TIMEOUT]" class="form-input" value="<?= e((string) ($integrationValues['OPENAI_TIMEOUT'] ?? '')) ?>" min="5" step="1" autocomplete="on" autocorrect="off" spellcheck="false">
                                        <div class="form-hint"><?= __('integrations_openai_timeout_hint', 'Settings') ?></div>
                                    </div>

                                    <div class="form-group">
                                        <div class="fc-admin-help-row">
                                            <label for="env_openai_max_output_tokens" class="form-label"><?= __('integrations_openai_max_output_tokens', 'Settings') ?></label>
                                            <?= $renderIntegrationHelp('OPENAI_MAX_OUTPUT_TOKENS') ?>
                                        </div>
                                        <input type="number" id="env_openai_max_output_tokens" name="env[OPENAI_MAX_OUTPUT_TOKENS]" class="form-input" value="<?= e((string) ($integrationValues['OPENAI_MAX_OUTPUT_TOKENS'] ?? '')) ?>" min="64" step="1" autocomplete="on" autocorrect="off" spellcheck="false">
                                        <div class="form-hint"><?= __('integrations_openai_max_output_tokens_hint', 'Settings') ?></div>
                                    </div>
                                </div>

                                <div class="settings-inline-actions settings-integrations-ai-actions">
                                    <button type="submit" class="btn btn-secondary" name="test_openai_connection" value="1">
                                        <i class="fas fa-plug"></i>
                                        <?= __('integrations_ai_test_button', 'Settings') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="settings-integrations-group module-card" data-settings-integration-card>
                        <div class="settings-integrations-group-toggle module-card-header" data-settings-integration-toggle>
                            <div class="module-card-info">
                                <div class="module-card-icon settings-integrations-group-icon">
                                    <i class="fas fa-cookie-bite"></i>
                                </div>
                                <div class="module-card-text">
                                    <h4 class="module-card-title settings-integrations-group-title"><?= __('integrations_group_cookies', 'Settings') ?></h4>
                                </div>
                            </div>
                            <div class="module-card-summary">
                                <i class="fas fa-chevron-down module-card-chevron settings-integrations-group-chevron" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="module-card-content" data-settings-integration-content>
                            <div class="module-card-body settings-integrations-group-body">
                            <div class="form-group">
                                <div class="fc-admin-help-row fc-admin-help-row--inline">
                                    <label class="form-inline">
                                        <input type="hidden" name="env[COOKIE_BANNER_ENABLED]" value="0">
                                        <input type="checkbox" class="form-check-input" name="env[COOKIE_BANNER_ENABLED]" value="1" <?= ((int) ($integrationValues['COOKIE_BANNER_ENABLED'] ?? 0) === 1) ? 'checked' : '' ?>>
                                        <?= __('integrations_cookie_banner_enabled', 'Settings') ?>
                                    </label>
                                    <?= $renderIntegrationHelp('COOKIE_BANNER_ENABLED') ?>
                                </div>
                                <div class="form-hint"><?= __('integrations_cookie_banner_enabled_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row fc-admin-help-row--inline">
                                    <label class="form-inline">
                                        <input type="hidden" name="env[COOKIE_REQUIRE_CONSENT]" value="0">
                                        <input type="checkbox" class="form-check-input" name="env[COOKIE_REQUIRE_CONSENT]" value="1" <?= ((int) ($integrationValues['COOKIE_REQUIRE_CONSENT'] ?? 0) === 1) ? 'checked' : '' ?>>
                                        <?= __('integrations_cookie_require_consent', 'Settings') ?>
                                    </label>
                                    <?= $renderIntegrationHelp('COOKIE_REQUIRE_CONSENT') ?>
                                </div>
                                <div class="form-hint"><?= __('integrations_cookie_require_consent_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row">
                                    <label for="env_axeptio_client_id" class="form-label"><?= __('integrations_axeptio_client_id', 'Settings') ?></label>
                                    <?= $renderIntegrationHelp('AXEPTIO_CLIENT_ID') ?>
                                </div>
                                <input type="text" id="env_axeptio_client_id" name="env[AXEPTIO_CLIENT_ID]" class="form-input" value="<?= e((string) ($integrationValues['AXEPTIO_CLIENT_ID'] ?? '')) ?>" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                <div class="form-hint"><?= __('integrations_axeptio_client_id_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row">
                                    <label for="env_axeptio_cookies_version" class="form-label"><?= __('integrations_axeptio_cookies_version', 'Settings') ?></label>
                                    <?= $renderIntegrationHelp('AXEPTIO_COOKIES_VERSION') ?>
                                </div>
                                <input type="text" id="env_axeptio_cookies_version" name="env[AXEPTIO_COOKIES_VERSION]" class="form-input" value="<?= e((string) ($integrationValues['AXEPTIO_COOKIES_VERSION'] ?? '')) ?>" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                <div class="form-hint"><?= __('integrations_axeptio_cookies_version_hint', 'Settings') ?></div>
                            </div>
                            </div>
                        </div>
                    </section>

                    <section class="settings-integrations-group module-card" data-settings-integration-card>
                        <div class="settings-integrations-group-toggle module-card-header" data-settings-integration-toggle>
                            <div class="module-card-info">
                                <div class="module-card-icon settings-integrations-group-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="module-card-text">
                                    <h4 class="module-card-title settings-integrations-group-title"><?= __('integrations_group_analytics', 'Settings') ?></h4>
                                </div>
                            </div>
                            <div class="module-card-summary">
                                <i class="fas fa-chevron-down module-card-chevron settings-integrations-group-chevron" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="module-card-content" data-settings-integration-content>
                            <div class="module-card-body settings-integrations-group-body">
                            <div class="form-group">
                                <div class="form-hint"><?= __('integrations_analytics_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row fc-admin-help-row--inline">
                                    <label class="form-inline">
                                        <input type="hidden" name="env[MATOMO_ENABLED]" value="0">
                                        <input type="checkbox" class="form-check-input" name="env[MATOMO_ENABLED]" value="1" <?= ((int) ($integrationValues['MATOMO_ENABLED'] ?? 0) === 1) ? 'checked' : '' ?>>
                                        <?= __('integrations_matomo_enabled', 'Settings') ?>
                                    </label>
                                    <?= $renderIntegrationHelp('MATOMO_ENABLED') ?>
                                </div>
                                <div class="form-hint"><?= __('integrations_matomo_enabled_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row">
                                    <label for="env_matomo_base_url" class="form-label"><?= __('integrations_matomo_base_url', 'Settings') ?></label>
                                    <?= $renderIntegrationHelp('MATOMO_BASE_URL') ?>
                                </div>
                                <input type="url" id="env_matomo_base_url" name="env[MATOMO_BASE_URL]" class="form-input" value="<?= e((string) ($integrationValues['MATOMO_BASE_URL'] ?? '')) ?>" placeholder="https://stats.example.com" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                <div class="form-hint"><?= __('integrations_matomo_base_url_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row">
                                    <label for="env_matomo_site_id" class="form-label"><?= __('integrations_matomo_site_id', 'Settings') ?></label>
                                    <?= $renderIntegrationHelp('MATOMO_SITE_ID') ?>
                                </div>
                                <input type="text" id="env_matomo_site_id" name="env[MATOMO_SITE_ID]" class="form-input" value="<?= e((string) ($integrationValues['MATOMO_SITE_ID'] ?? '')) ?>" placeholder="1" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                <div class="form-hint"><?= __('integrations_matomo_site_id_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row fc-admin-help-row--inline">
                                    <label class="form-inline">
                                        <input type="hidden" name="env[GOOGLE_ANALYTICS_ENABLED]" value="0">
                                        <input type="checkbox" class="form-check-input" name="env[GOOGLE_ANALYTICS_ENABLED]" value="1" <?= ((int) ($integrationValues['GOOGLE_ANALYTICS_ENABLED'] ?? 0) === 1) ? 'checked' : '' ?>>
                                        <?= __('integrations_google_analytics_enabled', 'Settings') ?>
                                    </label>
                                    <?= $renderIntegrationHelp('GOOGLE_ANALYTICS_ENABLED') ?>
                                </div>
                                <div class="form-hint"><?= __('integrations_google_analytics_enabled_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row">
                                    <label for="env_google_analytics_measurement_id" class="form-label"><?= __('integrations_google_analytics_measurement_id', 'Settings') ?></label>
                                    <?= $renderIntegrationHelp('GOOGLE_ANALYTICS_MEASUREMENT_ID') ?>
                                </div>
                                <input type="text" id="env_google_analytics_measurement_id" name="env[GOOGLE_ANALYTICS_MEASUREMENT_ID]" class="form-input" value="<?= e((string) ($integrationValues['GOOGLE_ANALYTICS_MEASUREMENT_ID'] ?? '')) ?>" placeholder="G-XXXXXXXXXX" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                <div class="form-hint"><?= __('integrations_google_analytics_measurement_id_hint', 'Settings') ?></div>
                            </div>
                            </div>
                        </div>
                    </section>

                    <section class="settings-integrations-group module-card" data-settings-integration-card>
                        <div class="settings-integrations-group-toggle module-card-header" data-settings-integration-toggle>
                            <div class="module-card-info">
                                <div class="module-card-icon settings-integrations-group-icon">
                                    <i class="fas fa-pen-ruler"></i>
                                </div>
                                <div class="module-card-text">
                                    <h4 class="module-card-title settings-integrations-group-title"><?= __('integrations_group_editors', 'Settings') ?></h4>
                                </div>
                            </div>
                            <div class="module-card-summary">
                                <i class="fas fa-chevron-down module-card-chevron settings-integrations-group-chevron" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="module-card-content" data-settings-integration-content>
                            <div class="module-card-body settings-integrations-group-body">
                            <div class="form-group">
                                <div class="fc-admin-help-row fc-admin-help-row--inline">
                                    <label class="form-inline">
                                        <input type="hidden" name="env[TINYMCE_ENABLED]" value="0">
                                        <input type="checkbox" class="form-check-input" name="env[TINYMCE_ENABLED]" value="1" <?= ((int) ($integrationValues['TINYMCE_ENABLED'] ?? 0) === 1) ? 'checked' : '' ?>>
                                        <?= __('integrations_tinymce_enabled', 'Settings') ?>
                                    </label>
                                    <?= $renderIntegrationHelp('TINYMCE_ENABLED') ?>
                                </div>
                                <div class="form-hint"><?= __('integrations_tinymce_enabled_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row">
                                    <label for="env_tinymce_api_key" class="form-label"><?= __('integrations_tinymce_api_key', 'Settings') ?></label>
                                    <?= $renderIntegrationHelp('TINYMCE_API_KEY') ?>
                                </div>
                                <input type="text" id="env_tinymce_api_key" name="env[TINYMCE_API_KEY]" class="form-input" value="<?= e((string) ($integrationValues['TINYMCE_API_KEY'] ?? '')) ?>" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                <div class="form-hint"><?= __('integrations_tinymce_api_key_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row">
                                    <label for="env_fontawesome_kit" class="form-label"><?= __('integrations_fontawesome_kit', 'Settings') ?></label>
                                    <?= $renderIntegrationHelp('FONTAWESOME_KIT') ?>
                                </div>
                                <input type="text" id="env_fontawesome_kit" name="env[FONTAWESOME_KIT]" class="form-input" value="<?= e((string) ($integrationValues['FONTAWESOME_KIT'] ?? '')) ?>" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                <div class="form-hint"><?= __('integrations_fontawesome_kit_hint', 'Settings') ?></div>
                            </div>
                            </div>
                        </div>
                    </section>

                    <section class="settings-integrations-group module-card" data-settings-integration-card>
                        <div class="settings-integrations-group-toggle module-card-header" data-settings-integration-toggle>
                            <div class="module-card-info">
                                <div class="module-card-icon settings-integrations-group-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="module-card-text">
                                    <h4 class="module-card-title settings-integrations-group-title"><?= __('integrations_group_security', 'Settings') ?></h4>
                                </div>
                            </div>
                            <div class="module-card-summary">
                                <i class="fas fa-chevron-down module-card-chevron settings-integrations-group-chevron" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="module-card-content" data-settings-integration-content>
                            <div class="module-card-body settings-integrations-group-body">
                            <div class="form-group">
                                <div class="fc-admin-help-row fc-admin-help-row--inline">
                                    <label class="form-inline">
                                        <input type="hidden" name="env[TURNSTILE_ENABLED]" value="0">
                                        <input type="checkbox" class="form-check-input" name="env[TURNSTILE_ENABLED]" value="1" <?= ((int) ($integrationValues['TURNSTILE_ENABLED'] ?? 0) === 1) ? 'checked' : '' ?>>
                                        <?= __('integrations_turnstile_enabled', 'Settings') ?>
                                    </label>
                                    <?= $renderIntegrationHelp('TURNSTILE_ENABLED') ?>
                                </div>
                                <div class="form-hint"><?= __('integrations_turnstile_enabled_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row">
                                    <label for="env_turnstile_site_key" class="form-label"><?= __('integrations_turnstile_site_key', 'Settings') ?></label>
                                    <?= $renderIntegrationHelp('TURNSTILE_SITE_KEY') ?>
                                </div>
                                <input type="text" id="env_turnstile_site_key" name="env[TURNSTILE_SITE_KEY]" class="form-input" value="<?= e((string) ($integrationValues['TURNSTILE_SITE_KEY'] ?? '')) ?>" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                <div class="form-hint"><?= __('integrations_turnstile_site_key_hint', 'Settings') ?></div>
                            </div>

                            <div class="form-group">
                                <div class="fc-admin-help-row">
                                    <label for="env_turnstile_secret_key" class="form-label"><?= __('integrations_turnstile_secret_key', 'Settings') ?></label>
                                    <?= $renderIntegrationHelp('TURNSTILE_SECRET_KEY') ?>
                                </div>
                                <input type="text" id="env_turnstile_secret_key" name="env[TURNSTILE_SECRET_KEY]" class="form-input" value="<?= e((string) ($integrationValues['TURNSTILE_SECRET_KEY'] ?? '')) ?>" autocomplete="on" autocapitalize="none" autocorrect="off" spellcheck="false">
                                <div class="form-hint"><?= __('integrations_turnstile_secret_key_hint', 'Settings') ?></div>
                            </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </section>

        <section class="settings-tab-panel" data-settings-panel="system" role="tabpanel" hidden>
            <div class="card">
                <h3 class="card-title card-title-spaced"><?= __('system_information', 'Settings') ?></h3>
                <div class="settings-system-overview">
                    <div class="settings-system-stat">
                        <span class="settings-system-stat-label"><?= __('version', 'Settings') ?></span>
                        <div class="settings-system-stat-value"><?= e((string) ($systemInfo['flatcms_version'] ?? __('not_available', 'Settings'))) ?></div>
                    </div>
                    <div class="settings-system-stat">
                        <span class="settings-system-stat-label"><?= __('system_environment', 'Settings') ?></span>
                        <div class="settings-system-stat-value"><?= e((string) ($systemInfo['environment'] ?? __('not_available', 'Settings'))) ?></div>
                    </div>
                    <div class="settings-system-stat">
                        <span class="settings-system-stat-label"><?= __('system_php_version', 'Settings') ?></span>
                        <div class="settings-system-stat-value"><?= e((string) ($systemInfo['php_version'] ?? __('not_available', 'Settings'))) ?></div>
                    </div>
                    <div class="settings-system-stat">
                        <span class="settings-system-stat-label"><?= __('system_write_status', 'Settings') ?></span>
                        <div class="settings-system-stat-value">
                            <?php if (!empty($systemInfo['write_ok'])): ?>
                                <span class="settings-status-badge is-ok"><?= __('system_write_status_ok', 'Settings') ?></span>
                            <?php else: ?>
                                <span class="settings-status-badge is-warning"><?= __('system_write_status_warn', 'Settings') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="settings-system-block">
                    <div class="settings-system-block-title"><?= __('system_paths', 'Settings') ?></div>
                    <div class="settings-path-list">
                        <?php
                        $pathLabelMap = [
                            'data' => __('system_path_data', 'Settings'),
                            'storage' => __('system_path_storage', 'Settings'),
                            'uploads' => __('system_path_uploads', 'Settings'),
                        ];
                        ?>
                        <?php foreach (($systemInfo['paths'] ?? []) as $pathKey => $pathInfo): ?>
                            <?php
                            $exists = !empty($pathInfo['exists']);
                            $writable = !empty($pathInfo['writable']);
                            $rowClass = $exists && $writable ? 'is-ok' : ($exists ? 'is-warning' : 'is-error');
                            ?>
                            <div class="settings-path-item <?= $rowClass ?>">
                                <div class="settings-path-head">
                                    <span class="settings-path-name"><?= e((string) ($pathLabelMap[$pathKey] ?? $pathKey)) ?></span>
                                    <?php if (!$exists): ?>
                                        <span class="settings-status-badge is-error"><?= __('status_missing', 'Settings') ?></span>
                                    <?php elseif (!$writable): ?>
                                        <span class="settings-status-badge is-warning"><?= __('status_not_writable', 'Settings') ?></span>
                                    <?php else: ?>
                                        <span class="settings-status-badge is-ok"><?= __('status_ok', 'Settings') ?></span>
                                    <?php endif; ?>
                                </div>
                                <code class="settings-path-code"><?= e((string) ($pathInfo['path'] ?? '')) ?></code>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="settings-system-block">
                    <div class="settings-system-block-title"><?= __('system_runtime', 'Settings') ?></div>
                    <div class="settings-runtime-badges">
                        <?php
                        $extensions = (array) ($systemInfo['extensions'] ?? []);
                        $extensionLabelMap = [
                            'openssl' => __('system_extension_openssl', 'Settings'),
                            'curl' => __('system_extension_curl', 'Settings'),
                            'zip' => __('system_extension_zip', 'Settings'),
                        ];
                        ?>
                        <?php foreach ($extensions as $extKey => $isAvailable): ?>
                            <div class="settings-runtime-item">
                                <span class="settings-runtime-name"><?= e((string) ($extensionLabelMap[$extKey] ?? strtoupper((string) $extKey))) ?></span>
                                <?php if ($isAvailable): ?>
                                    <span class="settings-status-badge is-ok"><?= __('status_active', 'Settings') ?></span>
                                <?php else: ?>
                                    <span class="settings-status-badge is-error"><?= __('status_inactive', 'Settings') ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <dl class="settings-system-list">
                    <div class="settings-system-row">
                        <dt><?= __('installed_at', 'Settings') ?></dt>
                        <dd><?= e((string) ($settings['installed_at'] ?? __('not_available', 'Settings'))) ?></dd>
                    </div>
                    <div class="settings-system-row">
                        <dt><?= __('updated_at', 'Settings') ?></dt>
                        <dd><?= e((string) ($settings['updated_at'] ?? __('not_available', 'Settings'))) ?></dd>
                    </div>
                    <div class="settings-system-row">
                        <dt><?= __('system_timezone_current', 'Settings') ?></dt>
                        <dd><?= e((string) ($systemInfo['timezone'] ?? __('not_available', 'Settings'))) ?></dd>
                    </div>
                </dl>
            </div>
        </section>
    </div>

    <div class="modal-overlay is-initially-hidden" id="siteBrandingModal" aria-hidden="true" data-site-branding-modal data-site-branding-active-locale="<?= e($siteBrandingActiveLocale) ?>">
        <div class="modal-container settings-branding-modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-language modal-icon-info"></i>
                    <span data-site-branding-modal-title><?= e((string) ($siteBrandingInitialUiLabels['modal_title'] ?? __('site_branding_translations_modal_title', 'Settings'))) ?></span>
                </h3>
                <button
                    type="button"
                    class="modal-close"
                    data-modal-close="siteBrandingModal"
                    data-site-branding-close-icon
                    aria-label="<?= e((string) ($siteBrandingInitialUiLabels['close'] ?? __('close', 'Core'))) ?>"
                >&times;</button>
            </div>
            <div class="modal-body">
                <div class="settings-branding-translation-bar">
                <div
                    class="settings-branding-translation-tabs"
                    role="tablist"
                    data-site-branding-tablist
                    aria-label="<?= e((string) ($siteBrandingInitialUiLabels['translations_label'] ?? __('site_branding_translations', 'Settings'))) ?>"
                >
                    <?php foreach ($siteBrandingTabs as $index => $tab): ?>
                        <?php
                        $status = (string) ($tab['status'] ?? 'empty');
                        $tabUiLabels = is_array($tab['ui_labels'] ?? null) ? $tab['ui_labels'] : [];
                        $tabClasses = ['settings-branding-translation-tab'];
                        if (!empty($tab['is_active'])) {
                            $tabClasses[] = 'is-active';
                        }
                        if ($status === 'empty') {
                            $tabClasses[] = 'is-missing';
                        }
                        $statusLabelMap = [
                            'source' => (string) ($siteBrandingInitialUiLabels['translation_source'] ?? __('translation_source', 'Settings')),
                            'translated' => (string) ($siteBrandingInitialUiLabels['translation_ready'] ?? __('translation_ready', 'Settings')),
                            'empty' => (string) ($siteBrandingInitialUiLabels['translation_missing'] ?? __('translation_missing', 'Settings')),
                        ];
                        ?>
                        <button
                            type="button"
                            class="<?= e(implode(' ', $tabClasses)) ?>"
                            data-site-branding-tab-btn
                            data-tab="<?= e((string) ($tab['code'] ?? '')) ?>"
                            data-status="<?= e($status) ?>"
                            role="tab"
                            aria-selected="<?= !empty($tab['is_active']) ? 'true' : 'false' ?>"
                            title="<?= e((string) ($tab['label'] ?? '')) ?>"
                        >
                            <span class="settings-branding-translation-tab-icon" aria-hidden="true">
                                <span class="settings-branding-translation-flag"><?= e((string) ($tab['flag'] ?? '🏳️')) ?></span>
                            </span>
                            <span class="settings-branding-translation-tab-badge">
                                <?= e((string) ($statusLabelMap[$status] ?? __('translation_missing', 'Settings'))) ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
                </div>

                <div class="settings-branding-panels">
                    <?php foreach ($siteBrandingTabs as $index => $tab): ?>
                        <?php
                        $localeCode = (string) ($tab['code'] ?? '');
                        $values = is_array($tab['values'] ?? null) ? $tab['values'] : [];
                        $tabFormLabels = is_array($tab['form_labels'] ?? null) ? $tab['form_labels'] : [];
                        $tabUiLabels = is_array($tab['ui_labels'] ?? null) ? $tab['ui_labels'] : [];
                        ?>
                        <section
                            class="settings-branding-panel<?= !empty($tab['is_active']) ? ' is-active' : '' ?>"
                            data-site-branding-panel="<?= e($localeCode) ?>"
                            data-site-branding-ui="<?= e((string) json_encode($tabUiLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                            role="tabpanel"
                            <?= !empty($tab['is_active']) ? '' : 'hidden' ?>
                        >
                            <div class="settings-branding-panel-head">
                                <div class="settings-branding-panel-title">
                                    <span class="settings-branding-panel-flag" aria-hidden="true"><?= e((string) ($tab['flag'] ?? '🏳️')) ?></span>
                                    <strong><?= e((string) ($tab['label'] ?? $localeCode)) ?></strong>
                                </div>
                                <?php if (!empty($tab['is_source'])): ?>
                                    <span class="settings-status-badge is-ok"><?= e((string) ($tabUiLabels['translation_source'] ?? __('translation_source', 'Settings'))) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="branding_<?= e($localeCode) ?>_site_name" class="form-label"><?= e((string) ($tabFormLabels['site_name'] ?? __('site_name', 'Settings'))) ?></label>
                                <input
                                    type="text"
                                    id="branding_<?= e($localeCode) ?>_site_name"
                                    name="branding_translations[<?= e($localeCode) ?>][site_name]"
                                    class="form-input"
                                    value="<?= e((string) ($values['site_name'] ?? '')) ?>"
                                    data-site-branding-locale-field="site_name"
                                    <?= !empty($tab['is_source']) ? 'data-site-branding-source-field="site_name"' : '' ?>
                                >
                            </div>
                            <div class="form-group">
                                <label for="branding_<?= e($localeCode) ?>_site_description" class="form-label"><?= e((string) ($tabFormLabels['site_description'] ?? __('site_description', 'Settings'))) ?></label>
                                <textarea
                                    id="branding_<?= e($localeCode) ?>_site_description"
                                    name="branding_translations[<?= e($localeCode) ?>][site_description]"
                                    class="form-input"
                                    rows="3"
                                    data-no-editor
                                    data-site-branding-locale-field="site_description"
                                    <?= !empty($tab['is_source']) ? 'data-site-branding-source-field="site_description"' : '' ?>
                                ><?= e((string) ($values['site_description'] ?? '')) ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="branding_<?= e($localeCode) ?>_site_slogan" class="form-label"><?= e((string) ($tabFormLabels['site_slogan'] ?? __('site_slogan', 'Settings'))) ?></label>
                                <input
                                    type="text"
                                    id="branding_<?= e($localeCode) ?>_site_slogan"
                                    name="branding_translations[<?= e($localeCode) ?>][site_slogan]"
                                    class="form-input"
                                    value="<?= e((string) ($values['site_slogan'] ?? '')) ?>"
                                    placeholder="<?= e((string) ($tabFormLabels['site_slogan_placeholder'] ?? __('site_slogan_placeholder', 'Settings'))) ?>"
                                    data-site-branding-locale-field="site_slogan"
                                    <?= !empty($tab['is_source']) ? 'data-site-branding-source-field="site_slogan"' : '' ?>
                                >
                                <div class="form-hint"><?= e((string) ($tabFormLabels['site_slogan_hint'] ?? __('site_slogan_hint', 'Settings'))) ?></div>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <div class="modal-footer-info" data-site-branding-footer-info><?= e((string) ($siteBrandingInitialUiLabels['modal_help'] ?? __('site_branding_translations_modal_help', 'Settings'))) ?></div>
                <button type="button" class="btn btn-secondary" data-modal-close="siteBrandingModal" data-site-branding-close-btn><?= e((string) ($siteBrandingInitialUiLabels['close'] ?? __('close', 'Core'))) ?></button>
                <button type="submit" class="btn btn-primary" data-site-branding-save-btn><?= e((string) ($siteBrandingInitialUiLabels['save'] ?? __('save', 'Core'))) ?></button>
            </div>
        </div>
    </div>

    <div class="form-actions form-actions-divider">
        <button type="submit" class="btn btn-primary"><?= __('save', 'Core') ?></button>
    </div>
</form>

<div
    class="hidden"
    data-settings-media-config
    data-config="<?= e((string) json_encode($settingsMediaConfig)) ?>"
    data-modal-error="<?= e((string) __('site_media_modal_unavailable', 'Settings')) ?>"
></div>

<?php if (is_file($mediaModalPath)): ?>
    <?php include $mediaModalPath; ?>
    <?php if (is_file($mediaModalScriptPath)): ?>
        <script src="<?= module_asset('Media', 'js/media-modal.js') ?>"></script>
    <?php endif; ?>
<?php endif; ?>

<script src="<?= module_asset('Settings', 'js/settings.js') ?>"></script>
