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
$languagesIndexJsPath = BASE_PATH . '/app/Modules/Languages/Assets/js/languages-index.js';
$languagesCssVersion = is_file($languagesCssPath) ? (string) filemtime($languagesCssPath) : '';
$languagesIndexJsVersion = is_file($languagesIndexJsPath) ? (string) filemtime($languagesIndexJsPath) : '';
?>
<link rel="stylesheet" href="<?= module_asset('Languages', 'css/languages.css') ?><?= $languagesCssVersion !== '' ? ('?v=' . $languagesCssVersion) : '' ?>">

<div class="page-header">
    <h1 class="page-title"><?= e($pageTitle) ?></h1>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-language"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('languages_help_badge', 'Languages') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('languages_help_title', 'Languages') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('languages_help_intro', 'Languages') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('languages_help_step_add', 'Languages') ?></li>
            <li><?= __('languages_help_step_translate', 'Languages') ?></li>
            <li><?= __('languages_help_step_default', 'Languages') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="#languageQuickCreateCard" class="btn btn-primary"><?= __('languages_help_action_add', 'Languages') ?></a>
            <a href="#languageListCard" class="btn btn-secondary"><?= __('languages_help_action_list', 'Languages') ?></a>
        </div>
    </div>
</div>

<!-- Actions Section (2-column grid) -->
<div class="language-actions-grid">
    <!-- Create New Language -->
    <div class="card" id="languageQuickCreateCard">
        <div class="card-body">
            <div class="section-header-row">
                <div class="section-icon-box primary">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h2 class="section-title"><?= __('add_language', 'Languages') ?></h2>
            </div>

            <form method="POST" action="<?= url('/admin/languages') ?>">
                <?= csrf_field() ?>
                <div class="language-form-row">
                    <div class="form-group form-group-tight">
                        <label for="code_quick" class="form-label"><?= __('language_code', 'Languages') ?></label>
                        <input type="text" name="code" id="code_quick" required
                            placeholder="fr-FR" maxlength="5"
                            class="form-input">
                    </div>
                    <div class="form-group form-group-tight">
                        <label for="direction_quick" class="form-label"><?= __('direction', 'Languages') ?></label>
                        <select name="direction" id="direction_quick" class="form-select">
                            <option value="ltr"><?= __('ltr_short', 'Languages') ?> (<?= __('left_to_right', 'Languages') ?>)</option>
                            <option value="rtl"><?= __('rtl_short', 'Languages') ?> (<?= __('right_to_left', 'Languages') ?>)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group form-group-gap-sm">
                    <label for="name_quick" class="form-label"><?= __('language_name', 'Languages') ?></label>
                    <input type="text" name="name" id="name_quick" required
                        placeholder="<?= e((string) ($languageNamePlaceholder ?? '')) ?>" class="form-input">
                </div>
                <div class="form-group form-group-gap-md">
                    <label for="native_quick" class="form-label"><?= __('native_name', 'Languages') ?></label>
                    <input type="text" name="native" id="native_quick"
                        placeholder="<?= __('native_name_placeholder', 'Languages') ?>" class="form-input">
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-plus"></i>
                    <?= __('add_language', 'Languages') ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Upload Language File -->
    <div class="card" id="languageImportCard">
        <div class="card-body">
            <div class="section-header-row">
                <div class="section-icon-box success">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <h2 class="section-title"><?= __('import_language', 'Languages') ?></h2>
            </div>

            <form method="POST" action="<?= url('/admin/languages/import') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="form-group form-group-gap-sm">
                    <label for="language_file" class="form-label"><?= __('import', 'Languages') ?> (JSON)</label>
                    <input type="file" name="language_file" id="language_file" required
                        accept=".json" class="form-input">
                    <small class="form-hint-block">
                        JSON - <?= __('import_language', 'Languages') ?>
                    </small>
                </div>
                <div class="form-group form-group-gap-md">
                    <label for="import_code" class="form-label"><?= __('language_code', 'Languages') ?></label>
                    <input type="text" name="import_code" id="import_code" class="form-input"
                        placeholder="fr-FR" maxlength="5">
                    <small class="form-hint-block">
                        <?= __('import_no_code', 'Languages') ?>
                    </small>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-upload"></i>
                    <?= __('import', 'Languages') ?>
                </button>
            </form>
        </div>
    </div>
</div>

<?php if (empty($languages)): ?>
    <div class="card lang-empty-state">
        <div class="admin-empty-state-panel">
            <div class="admin-empty-state-panel__icon" aria-hidden="true">
                <i class="fas fa-language"></i>
            </div>
            <h2 class="admin-empty-state-panel__title"><?= __('languages_empty_title', 'Languages') ?></h2>
            <p class="admin-empty-state-panel__text"><?= __('languages_empty_text', 'Languages') ?></p>
            <div class="admin-empty-state-panel__actions">
                <a href="#languageQuickCreateCard" class="btn btn-primary btn-sm"><?= __('languages_empty_action_add', 'Languages') ?></a>
                <a href="#languageImportCard" class="btn btn-ghost btn-sm"><?= __('languages_empty_action_import', 'Languages') ?></a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Languages List (Card-based) -->
    <div class="card language-card-list" id="languageListCard">
        <div class="card-body language-card-list-header">
            <h2 class="section-title"><?= __('languages', 'Languages') ?></h2>
        </div>

        <div class="language-default-notice alert alert-info">
            <i class="fas fa-circle-info alert-icon" aria-hidden="true"></i>
            <div class="alert-content">
                <div class="alert-title"><?= __('default_language_scope_title', 'Languages') ?></div>
                <div class="alert-message">
                    <?= __('default_language_scope_notice', 'Languages') ?>
                    <a href="<?= url('/admin/profile') ?>"><?= __('default_language_scope_profile_link', 'Languages') ?></a>.
                </div>
            </div>
        </div>

        <?php foreach ($languages as $code => $lang):
            $stats = $completionStats[$code] ?? [];
            $total = $stats['total'] ?? 0;
            $translated = $stats['translated'] ?? 0;
            $missing = $stats['missing'] ?? max($total - $translated, 0);
            $completionPct = $stats['percentage'] ?? ($total > 0 ? (int) round(($translated / $total) * 100) : 100);

            $hasMissing = $missing > 0;
            $pctClass = $hasMissing ? 'badge-missing' : 'badge-complete';
            $pctIcon = $hasMissing ? 'fa-exclamation-triangle' : 'fa-check-circle';
        ?>
            <div class="language-card">
                <div class="language-card-row">
                    <!-- Left: Badge + Info -->
                    <div class="language-card-info">
                        <div class="language-badge">
                            <?= strtoupper(substr($code, 0, 2)) ?>
                        </div>

                        <div>
                            <div class="language-card-row language-card-row--compact">
                                <h3 class="section-title section-title--sm">
                                    <?= e($lang['name'] ?? $code) ?>
                                </h3>
                                <?php if ($code === $defaultLang): ?>
                                    <span class="badge badge-info"><?= __('default', 'Languages') ?></span>
                                <?php endif; ?>
                                <?php if (!($lang['active'] ?? true)): ?>
                                    <span class="badge badge-warning"><?= __('inactive', 'Languages') ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="language-card-meta">
                                <span><i class="fas fa-code"></i><?= e($code) ?></span>
                                <span><i class="fas fa-text-width"></i><?= strtoupper($lang['direction'] ?? 'ltr') ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Actions + Completion -->
                    <div class="language-card-actions">
                        <a href="<?= url("/admin/languages/{$code}/translations") ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> <?= __('translations', 'Languages') ?>
                        </a>
                        <a href="<?= url("/admin/languages/{$code}/export") ?>" class="btn btn-sm btn-secondary" title="<?= __('export', 'Languages') ?>">
                            <i class="fas fa-download"></i>
                        </a>
                        <a href="<?= url("/admin/languages/{$code}/edit") ?>" class="btn btn-sm btn-secondary">
                            <?= __('edit', 'Core') ?>
                        </a>
                        <button type="button"
                            class="btn btn-sm btn-secondary"
                            data-action="scan-fill"
                            data-code="<?= e($code) ?>"
                            data-url="<?= url('/admin/languages') ?>"
                            data-token="<?= csrf_token() ?>"
                            data-msg-success="<?= __('scan_fill_success', 'Languages') ?>"
                            data-msg-none="<?= __('scan_fill_none', 'Languages') ?>"
                            data-modal-title="<?= __('scan_fill_missing', 'Languages') ?>"
                            data-modal-close="<?= __('close', 'Core') ?>"
                            title="<?= __('scan_fill_missing', 'Languages') ?>">
                            <i class="fas fa-search-plus"></i>
                        </button>

                        <?php if ($code !== $defaultLang): ?>
                            <form action="<?= url("/admin/languages/{$code}/set-default") ?>" method="POST" class="form-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-secondary"><?= __('set_as_default', 'Languages') ?></button>
                            </form>
                            <form action="<?= url("/admin/languages/{$code}/delete") ?>" method="POST" class="form-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger" data-action="confirm-delete" data-item-name="<?= e($lang['native'] ?? $lang['name'] ?? $code) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Completion Badge -->
                        <span class="<?= $pctClass ?>">
                            <i class="fas <?= $pctIcon ?>"></i> <?= $completionPct ?>%
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script src="<?= module_asset('Languages', 'js/languages-index.js') ?><?= $languagesIndexJsVersion !== '' ? ('?v=' . $languagesIndexJsVersion) : '' ?>"></script>
