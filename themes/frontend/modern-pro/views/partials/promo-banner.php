<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$promoBannerService = new \App\Modules\Settings\Services\PromoBannerService();
$promoBanner = $promoBannerService->resolveForLocale($settings ?? null, $locale ?? locale());
$bannerText = trim((string) ($promoBanner['text'] ?? ''));
$ctaLabel = trim((string) ($promoBanner['cta_label'] ?? ''));
$ctaUrl = trim((string) ($promoBanner['cta_url'] ?? ''));
$ctaVariant = trim((string) ($promoBanner['cta_variant'] ?? 'primary'));
$alignment = trim((string) ($promoBanner['alignment'] ?? 'left'));
$hasCta = $ctaLabel !== '' && $ctaUrl !== '';

if (empty($promoBanner['enabled']) || ($bannerText === '' && !$hasCta)) {
    return;
}

$ctaClassMap = [
    'primary' => 'btn btn-primary btn-sm',
    'secondary' => 'btn btn-secondary btn-sm',
    'outline' => 'btn btn-outline btn-sm',
    'ghost' => 'btn btn-ghost btn-sm',
];
$ctaClass = $ctaClassMap[$ctaVariant] ?? $ctaClassMap['primary'];
?>
<div class="site-promo-banner site-promo-banner-align-<?= e($alignment) ?>">
    <div class="container site-promo-banner-inner">
        <?php if ($bannerText !== ''): ?>
            <div class="site-promo-banner-copy"><?= e($bannerText) ?></div>
        <?php endif; ?>
        <?php if ($hasCta): ?>
            <a href="<?= e($ctaUrl) ?>" class="site-promo-banner-cta <?= e($ctaClass) ?>"><?= e($ctaLabel) ?></a>
        <?php endif; ?>
    </div>
</div>
