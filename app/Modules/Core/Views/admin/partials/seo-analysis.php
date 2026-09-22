<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Core/Views/admin/partials/seo-analysis.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

$seoAnalysisEntity = ($seoAnalysisEntity ?? 'page') === 'post' ? 'post' : 'page';
$seoAnalysisFields = is_array($seoAnalysisFields ?? null) ? $seoAnalysisFields : [];
$seoAnalysisFieldsJson = json_encode(
    $seoAnalysisFields,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
?>

<section
    class="seo-analysis"
    data-seo-analysis
    data-endpoint="<?= e(url('/admin/ui/seo/analyze')) ?>"
    data-entity="<?= e($seoAnalysisEntity) ?>"
    data-fields="<?= e($seoAnalysisFieldsJson) ?>"
    data-loading-label="<?= e(__('seo_analysis_loading', 'Core')) ?>"
    data-unavailable-label="<?= e(__('seo_analysis_unavailable', 'Core')) ?>"
>
    <div class="seo-analysis__header">
        <h4 class="seo-analysis__title"><?= e(__('seo_analysis_title', 'Core')) ?></h4>
        <div class="seo-analysis__summary">
            <output class="seo-analysis__score" data-seo-analysis-score aria-label="<?= e(__('seo_analysis_score_label', 'Core')) ?>"></output>
            <span class="badge badge-neutral" data-seo-analysis-rating hidden></span>
        </div>
    </div>
    <progress
        class="seo-analysis__progress"
        data-seo-analysis-progress
        max="100"
        value="0"
        aria-label="<?= e(__('seo_analysis_score_label', 'Core')) ?>"
    ></progress>
    <p class="seo-analysis__status" data-seo-analysis-status role="status" hidden></p>
    <ul class="seo-analysis__checks" data-seo-analysis-checks></ul>
</section>
