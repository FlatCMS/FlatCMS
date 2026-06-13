<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$settings = \App\Core\FlatFile::settings();
$brandingLocale = trim((string) ($locale ?? \App\Core\I18n::getLocale()));
if (class_exists(\App\Modules\Settings\Services\SiteBrandingTranslationService::class)) {
    $settings = (new \App\Modules\Settings\Services\SiteBrandingTranslationService())->resolveForLocale(
        $settings,
        $brandingLocale
    );
}
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
$siteLogo = trim((string) ($settings['site_logo'] ?? ''));
$siteLogoUrl = $siteLogo !== '' ? site_media_url($siteLogo) : '';
$siteLogoVariantDefault = (!$renderSiteName && !$renderSiteSlogan) ? 'banner' : 'compact';
$siteLogoVariant = trim((string) ($settings['site_logo_variant'] ?? $siteLogoVariantDefault));
if (!in_array($siteLogoVariant, ['compact', 'banner', 'banner_framed'], true)) {
    $siteLogoVariant = $siteLogoVariantDefault;
}
$sidebarHeaderClasses = ['sidebar-header'];
$sidebarLogoClasses = ['sidebar-logo'];
$sidebarLogoImageClasses = ['sidebar-logo-image'];
if ($siteLogoUrl !== '' && $siteLogoVariant !== 'compact' && !$renderSiteName && !$renderSiteSlogan) {
    $sidebarHeaderClasses[] = 'sidebar-header--banner-logo';
    $sidebarLogoClasses[] = 'sidebar-logo--banner';
    $sidebarLogoImageClasses[] = $siteLogoVariant === 'banner_framed'
        ? 'sidebar-logo-image--banner-framed'
        : 'sidebar-logo-image--banner';
}
if (!$renderSiteName && !$renderSiteSlogan) {
    $sidebarLogoClasses[] = 'sidebar-logo--logo-only';
}
$menuItems = \App\Modules\Auth\Services\RoleService::getRoleMenus(user_role());
$blockModules = ['Accordion', 'DownloadManager', 'Slider', 'Tabs'];
$sectionIcons = [
    'content' => 'fas fa-file-lines',
    'system' => 'fas fa-gears',
    'shop' => 'fas fa-store',
    'sidebar_group_blocks' => 'fas fa-cubes',
];

$primaryItems = [];
$blockItems = [];
foreach ($menuItems as $item) {
    if (isset($item['section'])) {
        $primaryItems[] = $item;
        continue;
    }

    if (isset($item['permission']) && !can((string) $item['permission'])) {
        continue;
    }

    $moduleName = trim((string) ($item['module'] ?? ''));
    if (in_array($moduleName, $blockModules, true)) {
        $blockItems[] = $item;
        continue;
    }

    $primaryItems[] = $item;
}

$resolveSectionLabel = static function (array $sectionItem): string {
    $sectionKey = (string) ($sectionItem['section'] ?? 'system');
    $module = (string) ($sectionItem['module'] ?? 'Core');
    $groupKey = 'sidebar_group_' . $sectionKey;

    if (\App\Core\I18n::has($groupKey, 'Core')) {
        return __($groupKey, 'Core');
    }

    return __($sectionKey, $module);
};
?>
<aside class="sidebar" id="sidebar" data-tour="sidebar">
            <div class="<?= e(implode(' ', $sidebarHeaderClasses)) ?>">
                <a href="<?= url('/admin') ?>" class="<?= e(implode(' ', $sidebarLogoClasses)) ?>" data-tour-target="sidebar-branding">
                    <?php if ($siteLogoUrl !== ''): ?>
                        <img src="<?= e($siteLogoUrl) ?>" alt="<?= e($siteName) ?>" class="<?= e(implode(' ', $sidebarLogoImageClasses)) ?>" loading="lazy" decoding="async">
                    <?php else: ?>
                        <span class="logo-icon">◆</span>
                    <?php endif; ?>
                    <?php if ($renderSiteName || $renderSiteSlogan): ?>
                    <span class="sidebar-brand-text">
                        <?php if ($renderSiteName): ?>
                        <span class="logo-text"><?= e($siteName) ?></span>
                        <?php endif; ?>
                        <?php if ($renderSiteSlogan): ?>
                            <span class="logo-slogan"><?= e($siteSlogan) ?></span>
                        <?php endif; ?>
                    </span>
                    <?php endif; ?>
                </a>
            </div>

            <nav class="sidebar-nav" data-tour-target="sidebar-navigation">
                <ul class="nav-list">
                    <?php $pendingSection = null; ?>
                    <?php foreach ($primaryItems as $item): ?>
                        <?php if (isset($item['section'])): ?>
                            <?php $pendingSection = $item; ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php if ($pendingSection !== null): ?>
                            <?php $sectionKey = (string) ($pendingSection['section'] ?? 'system'); ?>
                            <li class="nav-section">
                                <i class="<?= e((string) ($sectionIcons[$sectionKey] ?? 'fas fa-folder-open')) ?> nav-section-icon" aria-hidden="true"></i>
                                <span><?= e($resolveSectionLabel($pendingSection)) ?></span>
                            </li>
                            <?php $pendingSection = null; ?>
                        <?php endif; ?>
                        <?php
                        $itemUrl = (string) ($item['url'] ?? '/admin');
                        $itemModule = (string) ($item['module'] ?? 'Core');
                        $itemLabel = __((string) ($item['label'] ?? ''), $itemModule);
                        ?>
                        <li class="nav-item">
                            <a href="<?= url($itemUrl) ?>" class="nav-link <?= active_class($itemUrl, 'active') ?>" title="<?= e($itemLabel) ?>">
                                <i class="<?= e((string) ($item['icon'] ?? 'fas fa-circle')) ?> nav-icon"></i>
                                <span><?= $itemLabel ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>

                    <?php if (!empty($blockItems)): ?>
                        <li class="nav-section">
                            <i class="<?= e((string) ($sectionIcons['sidebar_group_blocks'] ?? 'fas fa-cubes')) ?> nav-section-icon" aria-hidden="true"></i>
                            <span><?= __('sidebar_group_blocks', 'Core') ?></span>
                        </li>
                        <?php foreach ($blockItems as $item): ?>
                            <?php
                            $itemUrl = (string) ($item['url'] ?? '/admin');
                            $itemModule = (string) ($item['module'] ?? 'Core');
                            $itemLabel = __((string) ($item['label'] ?? ''), $itemModule);
                            ?>
                            <li class="nav-item">
                                <a href="<?= url($itemUrl) ?>" class="nav-link <?= active_class($itemUrl, 'active') ?>" title="<?= e($itemLabel) ?>">
                                    <i class="<?= e((string) ($item['icon'] ?? 'fas fa-circle')) ?> nav-icon"></i>
                                    <span><?= $itemLabel ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>
