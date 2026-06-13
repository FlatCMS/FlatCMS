<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Languages Module CSS & JS -->
<?php
$languagesCssPath = BASE_PATH . '/app/Modules/Languages/Assets/css/languages.css';
$languagesJsPath = BASE_PATH . '/app/Modules/Languages/Assets/js/languages.js';
$languagesCssVersion = is_file($languagesCssPath) ? (string) filemtime($languagesCssPath) : '';
$languagesJsVersion = is_file($languagesJsPath) ? (string) filemtime($languagesJsPath) : '';
?>
<link rel="stylesheet" href="<?= module_asset('Languages', 'css/languages.css') ?><?= $languagesCssVersion !== '' ? ('?v=' . $languagesCssVersion) : '' ?>">

<div class="language-editor">
    <!-- Header -->
    <div class="page-header">
        <div class="section-header-row section-header-row--tight">
            <a href="<?= url('/admin/languages') ?>" class="text-muted link-muted">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="page-title"><?= e($pageTitle) ?> - <?= e(strtoupper($code)) ?></h1>
        </div>
    </div>

    <div class="card admin-guidance-card" data-admin-help-template hidden>
        <div class="card-body">
            <div class="admin-guidance-card__head">
                <div class="admin-guidance-card__eyebrow-row">
                    <span class="admin-guidance-card__icon" aria-hidden="true">
                        <i class="fas fa-language"></i>
                    </span>
                    <span class="admin-guidance-card__eyebrow"><?= __('translations_help_badge', 'Languages') ?></span>
                </div>
                <h2 class="admin-guidance-card__title"><?= __('translations_help_title', 'Languages') ?></h2>
                <p class="admin-guidance-card__copy"><?= __('translations_help_intro', 'Languages') ?></p>
            </div>
            <ul class="admin-guidance-card__list">
                <li><?= __('translations_help_step_progress', 'Languages') ?></li>
                <li><?= __('translations_help_step_modules', 'Languages') ?></li>
                <li><?= __('translations_help_step_save', 'Languages') ?></li>
            </ul>
            <div class="admin-guidance-card__actions">
                <a href="#translationsControls" class="btn btn-primary"><?= __('translations_help_action_filters', 'Languages') ?></a>
                <a href="#translationsModulesList" class="btn btn-secondary"><?= __('translations_help_action_modules', 'Languages') ?></a>
            </div>
        </div>
    </div>

    <!-- Global Stats -->
    <div class="global-stats">
        <div class="card">
            <div class="card-body">
                <div class="language-card-row language-card-row--spaced">
                    <div>
                        <h2 class="section-title">
                            <?= __('global_completion', 'Languages') ?>
                        </h2>
                        <p class="text-muted language-stats-subtext">
                            <span id="globalTranslatedCount"><?= $globalTranslated ?></span> / <span id="globalTotalCount"><?= $globalTotal ?></span> <?= __('translations', 'Languages') ?>
                            &bull; <?= count($modulesStats) ?> <?= __('all_modules', 'Languages') ?>
                        </p>
                    </div>
                    <div class="lang-text-right">
                        <div id="globalPercentage" class="global-percentage <?= $globalPercentage >= 100 ? 'is-complete' : 'is-partial' ?>">
                            <?= $globalPercentage ?>%
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="progress-bar progress-bar--spaced">
                    <div class="progress-fill <?= $globalPercentage >= 100 ? 'is-complete' : 'is-partial' ?>" id="globalProgressFill" data-progress="<?= $globalPercentage ?>"></div>
                </div>

                <?php
                    $globalLabel = ($globalMissing > 1) ? __('translation_missing_plural', 'Languages') : __('translation_missing_singular', 'Languages');
                    $globalBadgeClass = $globalMissing > 0 ? 'badge-missing' : 'badge-complete';
                    $globalBadgeIcon = $globalMissing > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle';
                    $globalBadgeText = $globalMissing > 0 ? ($globalMissing . ' ' . $globalLabel) : '100%';
                ?>
                <span id="globalMissingBadge" class="<?= $globalBadgeClass ?>">
                    <i class="fas <?= $globalBadgeIcon ?>"></i>
                    <span id="globalMissingBadgeText"><?= e($globalBadgeText) ?></span>
                </span>
            </div>
        </div>
    </div>

    <!-- Controls Bar -->
    <div class="translations-controls" id="translationsControls">
        <div class="search-bar">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" placeholder="<?= __('search_translations', 'Languages') ?>" autocomplete="on">
        </div>

        <label class="lang-controls-label">
            <input type="checkbox" id="showOnlyMissing">
            <span><?= __('show_missing_only', 'Languages') ?></span>
        </label>

        <button type="button" id="btnExpandAll" class="btn btn-sm btn-secondary">
            <i class="fas fa-chevron-down"></i> <?= __('expand_all', 'Languages') ?>
        </button>
        <button type="button" id="btnCollapseAll" class="btn btn-sm btn-secondary">
            <i class="fas fa-chevron-up"></i> <?= __('collapse_all', 'Languages') ?>
        </button>

        <button type="button" id="btnScanFill" class="btn btn-sm btn-secondary">
            <i class="fas fa-search-plus"></i> <?= __('scan_fill_missing', 'Languages') ?>
        </button>
    </div>

    <!-- Module Cards List -->
    <div id="translationsModulesList">
    <div id="modulesList">
        <?php foreach ($modulesStats as $moduleKey => $stats): ?>
        <div class="module-card" data-module="<?= e($moduleKey) ?>" id="module-<?= e($moduleKey) ?>">
            <div class="module-card-header" data-module="<?= e($moduleKey) ?>">
                <div class="module-info">
                    <div class="module-icon <?= $stats['missing'] > 0 ? 'has-missing' : 'complete' ?>">
                        <i class="fas <?= $stats['missing'] > 0 ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                    </div>
                    <div>
                        <h3 class="module-name"><?= e($stats['label']) ?></h3>
                        <p class="module-stats-text">
                            <span class="module-translated-count"><?= $stats['translated'] ?></span> / <span class="module-total-count"><?= $stats['total'] ?></span> <?= __('keys', 'Languages') ?>
                            <?php if ($stats['missing'] > 0): ?>
                                <?php $missingLabel = ($stats['missing'] > 1) ? __('translation_missing_plural', 'Languages') : __('translation_missing_singular', 'Languages'); ?>
                                &bull; <span class="missing-count module-missing-count"><?= $stats['missing'] ?> <?= $missingLabel ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="module-right">
                    <div class="module-progress-mini">
                        <div class="progress-fill <?= $stats['percentage'] >= 100 ? 'is-complete' : 'is-partial' ?>" data-progress="<?= $stats['percentage'] ?>"></div>
                    </div>
                    <span class="module-percentage <?= $stats['percentage'] >= 100 ? 'is-complete' : 'is-partial' ?>">
                        <?= $stats['percentage'] ?>%
                    </span>
                    <i class="fas fa-chevron-down chevron-icon"></i>
                </div>
            </div>

            <div class="module-card-content" data-module="<?= e($moduleKey) ?>">
                <div class="module-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <?= __('loading_translations', 'Languages') ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    </div>

    <!-- No results -->
    <div id="noResults" class="no-results-message hidden">
        <div class="admin-empty-state-panel">
            <div class="admin-empty-state-panel__icon" aria-hidden="true">
                <i class="fas fa-search"></i>
            </div>
            <h2 class="admin-empty-state-panel__title"><?= __('translations_empty_title', 'Languages') ?></h2>
            <p class="admin-empty-state-panel__text"><?= __('translations_empty_text', 'Languages') ?></p>
            <div class="admin-empty-state-panel__actions">
                <a href="#translationsControls" class="btn btn-primary"><?= __('translations_empty_action_filters', 'Languages') ?></a>
                <a href="<?= url('/admin/languages') ?>" class="btn btn-secondary"><?= __('translations_empty_action_languages', 'Languages') ?></a>
            </div>
        </div>
    </div>

    <!-- Sticky Actions Bar -->
    <div class="sticky-actions">
        <div class="language-card-row">
            <a href="<?= url('/admin/languages') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> <?= __('back', 'Core') ?>
            </a>

            <button type="button" id="saveAllBtn" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= __('save_all', 'Languages') ?>
            </button>
        </div>
    </div>
</div>

<?php
$translationsConfig = [
    'code' => $code,
    'referenceLang' => $referenceLang,
    'csrfToken' => csrf_token(),
    'baseUrl' => url('/admin/languages'),
    'moduleTranslationsUrl' => url("/admin/languages/{$code}/module-translations"),
    'saveUrl' => url("/admin/languages/{$code}/translations"),
    'scanFillUrl' => url("/admin/languages/{$code}/scan-fill"),
    'i18n' => [
        'loading' => __('loading_translations', 'Languages'),
        'loadingError' => __('loading_error', 'Languages'),
        'reference' => __('reference', 'Languages'),
        'copyRef' => __('copy_from_reference', 'Languages'),
        'saveModule' => __('save_module', 'Languages'),
        'saveModuleError' => __('save_module_error', 'Languages'),
        'moduleSaved' => __('module_saved', 'Languages'),
        'allSaved' => __('all_saved', 'Languages'),
        'saveAllError' => __('save_all_error', 'Languages'),
        'noResults' => __('no_results', 'Languages'),
        'generalKeys' => __('general_keys', 'Languages'),
        'group' => __('group', 'Languages'),
        'scanFillSuccess' => __('scan_fill_success', 'Languages'),
        'scanFillNone' => __('scan_fill_none', 'Languages'),
        'scanFillMissing' => __('scan_fill_missing', 'Languages'),
        'translationMissingSingular' => __('translation_missing_singular', 'Languages'),
        'translationMissingPlural' => __('translation_missing_plural', 'Languages'),
        'keys' => __('keys', 'Languages')
    ]
];
$translationsConfigJson = e(json_encode($translationsConfig));
?>
<div id="translationsConfig" class="hidden" data-translations-config="<?= $translationsConfigJson ?>"></div>
<script src="<?= module_asset('Languages', 'js/languages.js') ?><?= $languagesJsVersion !== '' ? ('?v=' . $languagesJsVersion) : '' ?>"></script>
