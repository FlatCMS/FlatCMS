<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$footer = is_array($footer ?? null) ? $footer : [];
$poweredBy = is_array($footer['powered_by'] ?? null) ? $footer['powered_by'] : [];
$footerEnabled = (bool) ($footer['enabled'] ?? true);

$brandText = trim((string) ($footer['brand_text'] ?? ($settings['site_name'] ?? __('app_name', 'Core'))));
$siteName = trim((string) ($settings['site_name'] ?? __('app_name', 'Core')));
if ($siteName === '') {
    $siteName = (string) config('app.name', flatcms_product_name());
}
$siteSlogan = trim((string) ($settings['site_slogan'] ?? ''));
$showSiteName = !array_key_exists('site_name_enabled', $settings ?? [])
    ? true
    : ((int) ($settings['site_name_enabled'] ?? 0) === 1);
$showSiteSlogan = !array_key_exists('site_slogan_enabled', $settings ?? [])
    ? true
    : ((int) ($settings['site_slogan_enabled'] ?? 0) === 1);
$renderSiteName = $showSiteName && $siteName !== '';
$renderSiteSlogan = $showSiteSlogan && $siteSlogan !== '';
$siteLogoService = new \App\Modules\Settings\Services\SiteLogoService();
$siteLogoState = $siteLogoService->resolveLogoUrls($settings ?? []);
$siteLogoUrl = trim((string) ($siteLogoState['default'] ?? ''));
$siteLogoDarkUrl = trim((string) ($siteLogoState['dark'] ?? ''));
$siteLogoVariantDefault = (!$renderSiteName && !$renderSiteSlogan) ? 'banner' : 'compact';
$siteLogoVariant = trim((string) ($settings['site_logo_variant'] ?? $siteLogoVariantDefault));
if (!in_array($siteLogoVariant, ['compact', 'banner', 'banner_framed'], true)) {
    $siteLogoVariant = $siteLogoVariantDefault;
}
$footerBrandClasses = ['footer-brand'];
$footerBrandImageClasses = ['site-logo-image', 'footer-brand-image'];
$renderFooterBrandText = $renderSiteName && $brandText !== '';
if ($siteLogoUrl !== '' && $siteLogoVariant !== 'compact') {
    $footerBrandClasses[] = 'footer-brand--banner';
    $footerBrandImageClasses[] = $siteLogoVariant === 'banner_framed'
        ? 'footer-brand-image--banner-framed'
        : 'footer-brand-image--banner';
}
if (!$renderFooterBrandText) {
    $footerBrandClasses[] = 'footer-brand--logo-only';
}
$copyrightHtml = trim((string) ($footer['copyright_html'] ?? ''));
$poweredEnabled = (bool) ($poweredBy['enabled'] ?? true);
$poweredLabel = trim((string) ($poweredBy['label'] ?? __('app_name', 'Core')));
$poweredUrl = trim((string) ($poweredBy['url'] ?? 'https://flat-cms.fr'));

$showFooter = $footerEnabled || $poweredEnabled;
if (!$showFooter) {
    return;
}

$indentHtml = static function (string $html, int $level): string {
    $value = trim($html);
    if ($value === '') {
        return '';
    }

    $lines = preg_split('/\r\n|\r|\n/', $value);
    if (!is_array($lines)) {
        return $value;
    }

    $prefix = str_repeat('    ', $level);
    foreach ($lines as &$line) {
        if ($line !== '') {
            $line = $prefix . $line;
        }
    }
    unset($line);

    return implode(PHP_EOL, $lines);
};

ob_start();
if ($siteLogoUrl !== ''): ?>
<picture>
<?php if ($siteLogoDarkUrl !== '' && $siteLogoDarkUrl !== $siteLogoUrl): ?>
    <source srcset="<?= e($siteLogoDarkUrl) ?>" media="(prefers-color-scheme: dark)">
<?php endif; ?>
    <img src="<?= e($siteLogoUrl) ?>" alt="<?= e($siteName) ?>" class="<?= e(implode(' ', $footerBrandImageClasses)) ?>" loading="lazy" decoding="async">
</picture>
<?php else: ?>
<span class="logo-icon">◆</span>
<?php endif;
$footerBrandMediaHtml = $indentHtml((string) ob_get_clean(), 5);

ob_start();
if ($footerEnabled): ?>
<div class="<?= e(implode(' ', $footerBrandClasses)) ?>">
<?= $footerBrandMediaHtml !== '' ? $footerBrandMediaHtml . PHP_EOL : '' ?>
<?php if ($renderFooterBrandText): ?>
    <span><?= e($brandText) ?></span>
<?php endif; ?>
</div>
<?php if ($copyrightHtml !== ''): ?>
<div class="footer-copy"><?= $copyrightHtml ?></div>
<?php endif; ?>
<?php endif;
$footerMainHtml = $indentHtml((string) ob_get_clean(), 4);

ob_start();
if ($poweredEnabled): ?>
<p class="powered">
    <?= __('powered_by', 'Core') ?>
    <a href="<?= e($poweredUrl) ?>" target="_blank" rel="noopener">
        <?= e($poweredLabel) ?>
    </a>
</p>
<?php endif;
$poweredHtml = $indentHtml((string) ob_get_clean(), 4);
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-content<?= !$footerEnabled && $poweredEnabled ? ' is-powered-only' : '' ?>">
<?= $footerMainHtml !== '' ? $footerMainHtml . PHP_EOL : '' ?>
<?= $poweredHtml !== '' ? $poweredHtml . PHP_EOL : '' ?>
        </div>
    </div>
</footer>
