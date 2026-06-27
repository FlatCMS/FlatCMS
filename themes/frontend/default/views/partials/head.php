<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>
<head>
    <?php
    $themeCustomizationService = new \App\Modules\Themes\Services\ThemeCustomizationService();
    $themeCustomizationAsset = $themeCustomizationService->assetForActiveTheme('frontend', $settings ?? null);
    $promoBannerService = new \App\Modules\Settings\Services\PromoBannerService();
    $promoBannerAsset = $promoBannerService->assetForSettings($settings ?? null);
    $structuredDataManager = new \App\Services\StructuredData\StructuredDataManager();
    $structuredDataPayload = $structuredDataManager->payloadForView([
        'settings' => $settings ?? [],
        'locale' => $locale ?? locale(),
        'page' => $page ?? null,
        'post' => $post ?? null,
        'pageTitle' => $pageTitle ?? '',
        'postCategories' => $postCategories ?? [],
        'currentCategory' => $currentCategory ?? null,
    ]);
    $siteFavicon = trim((string) ($settings['site_favicon'] ?? ''));
    $siteFaviconUrl = $siteFavicon !== '' ? site_media_url($siteFavicon) : '';
    if ($siteFaviconUrl === '') {
        $siteFaviconUrl = url('/favicon.ico');
    }
    $siteName = trim((string) ($settings['site_name'] ?? __('app_name', 'Core')));
    $pageTitleValue = trim((string) ($pageTitle ?? ''));
    $metaDescription = '';
    if (is_array($page ?? null)) {
        $metaDescription = trim((string) ($page['meta_description'] ?? ''));
    }
    if ($metaDescription === '' && is_array($post ?? null)) {
        $metaDescription = trim((string) ($post['meta_description'] ?? ''));
    }
    if ($metaDescription === '') {
        $metaDescription = trim((string) ($settings['meta_description'] ?? ''));
    }
    if ($metaDescription === '') {
        $metaDescription = trim((string) ($settings['site_description'] ?? ''));
    }
    $metaKeywords = trim((string) ($settings['meta_keywords'] ?? ''));
    $documentTitle = $pageTitleValue;
    if ($documentTitle === '') {
        $documentTitle = $siteName !== '' ? $siteName : __('app_name', 'Core');
    } elseif (
        $siteName !== ''
        && strcasecmp($documentTitle, $siteName) !== 0
        && !preg_match('/(?:\\s[-|]\\s|\\s)'. preg_quote($siteName, '/') .'$/iu', $documentTitle)
    ) {
        $documentTitle .= ' - ' . $siteName;
    }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="FlatCMS">
    <meta name="author" content="Alain BROYE">
    <meta name="csrf-token" content="<?= e((string) ($csrf_token ?? '')) ?>">
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?php if ($metaKeywords !== ''): ?>
        <meta name="keywords" content="<?= e($metaKeywords) ?>">
    <?php endif; ?>
    <link rel="icon" href="<?= e($siteFaviconUrl) ?>">
    <title><?= e($documentTitle) ?></title>
    <link rel="stylesheet" href="<?= asset('dists/fontawesome/css/all.min.css') ?>">

    <link rel="stylesheet" href="<?= theme_asset('css/style.css', 'frontend') ?>">
    <?php if ($themeCustomizationAsset !== ''): ?>
        <link rel="stylesheet" href="<?= e($themeCustomizationAsset) ?>">
    <?php endif; ?>
<?php if ($promoBannerAsset !== ''): ?>
        <link rel="stylesheet" href="<?= e($promoBannerAsset) ?>">
<?php endif; ?>
<?php
$frontendHeadAssetsHtml = \App\Core\HookAssets::render('frontend.assets.head', [
    'settings' => is_array($settings ?? null) ? $settings : [],
    'locale' => $locale ?? locale(),
    'page' => $page ?? null,
    'post' => $post ?? null,
]);
?>
<?= $frontendHeadAssetsHtml !== '' ? $frontendHeadAssetsHtml . PHP_EOL : '' ?>
<?php
$toEnvBool = static function (mixed $value): bool {
    $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
    };
    $cookieBannerEnabled = $toEnvBool(env('COOKIE_BANNER_ENABLED', 0));
    $cookieRequireConsent = $toEnvBool(env('COOKIE_REQUIRE_CONSENT', 0));
    $axeptioClientId = trim((string) env('AXEPTIO_CLIENT_ID', ''));
    $axeptioCookiesVersion = trim((string) env('AXEPTIO_COOKIES_VERSION', ''));
    $matomoEnabled = $toEnvBool(env('MATOMO_ENABLED', 0));
    $matomoBaseUrl = trim((string) env('MATOMO_BASE_URL', ''));
    $matomoSiteId = trim((string) env('MATOMO_SITE_ID', ''));
    $googleAnalyticsEnabled = $toEnvBool(env('GOOGLE_ANALYTICS_ENABLED', 0));
    $googleAnalyticsMeasurementId = trim((string) env('GOOGLE_ANALYTICS_MEASUREMENT_ID', ''));
    ?>
    <script src="<?= asset('js/core/integrations-runtime.js') ?>" data-flatcms-integrations-runtime="1" data-cookie-banner-enabled="<?= $cookieBannerEnabled ? '1' : '0' ?>" data-cookie-require-consent="<?= $cookieRequireConsent ? '1' : '0' ?>" data-axeptio-client-id="<?= e($axeptioClientId) ?>" data-axeptio-cookies-version="<?= e($axeptioCookiesVersion) ?>" data-matomo-enabled="<?= $matomoEnabled ? '1' : '0' ?>" data-matomo-base-url="<?= e($matomoBaseUrl) ?>" data-matomo-site-id="<?= e($matomoSiteId) ?>" data-google-analytics-enabled="<?= $googleAnalyticsEnabled ? '1' : '0' ?>" data-google-analytics-measurement-id="<?= e($googleAnalyticsMeasurementId) ?>"></script>
    <?php if ($structuredDataPayload !== ''): ?>
        <script type="application/json" data-flatcms-structured-data><?= $structuredDataPayload ?></script>
        <script src="<?= asset('js/core/structured-data-runtime.js') ?>" defer></script>
    <?php endif; ?>
</head>
