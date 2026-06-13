<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$category = is_array($category ?? null) ? $category : null;
$formLabels = is_array($formLabels ?? null) ? $formLabels : [];
$translationUi = is_array($translationUi ?? null) ? $translationUi : [];
$translationUi = array_merge([
    'source_status' => 'active',
    'source_module' => 'blog',
    'tabs' => [],
], $translationUi);
$moduleOptions = is_array($moduleOptions ?? null) ? $moduleOptions : [];
$translationTabs = is_array($translationUi['tabs'] ?? null) ? $translationUi['tabs'] : [];
$oldTranslations = old('translations', []);
if (!is_array($oldTranslations)) {
    $oldTranslations = [];
}
$activeLocale = (string) old('locale', $translationUi['active_locale'] ?? '');
$sourceLocale = (string) old('source_locale', $translationUi['source_locale'] ?? $activeLocale);
$translationGroupValue = (string) old('translation_group', $translationUi['translation_group'] ?? '');
$currentModule = (string) old('module', $translationUi['source_module'] ?? 'blog');
$currentStatus = (string) old('status', $translationUi['source_status'] ?? 'active');
$formActionId = $category ? (string) (($translationUi['source_category_id'] ?? '') ?: ($category['id'] ?? '')) : '';
$formAction = $category && $formActionId !== ''
    ? url('/admin/categories/' . $formActionId)
    : url('/admin/categories');

if ($activeLocale === '' && $translationTabs !== []) {
    $activeLocale = (string) ($translationTabs[0]['code'] ?? '');
}

$categoryLabel = static function (string $key, string $fallback = '') use ($formLabels): string {
    $value = $formLabels[$key] ?? null;
    return is_string($value) && trim($value) !== '' ? $value : $fallback;
};

$categoriesPanelLabel = static function (array $labelBag, string $key, string $fallback = ''): string {
    $value = $labelBag[$key] ?? null;
    return is_string($value) && trim($value) !== '' ? $value : $fallback;
};

$categoriesLocaleFlag = static function (string $locale): string {
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
};
?>

<link rel="stylesheet" href="<?= module_asset('Categories', 'css/categories.css') ?>">

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
    </div>
    <div class="page-header-actions">
        <a href="<?= url('/admin/categories') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <?= e($categoryLabel('back', __('back', 'Core'))) ?>
        </a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-tags"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('categories_help_badge', 'Categories') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('categories_tour_form_editor_title', 'Categories') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('categories_tour_form_editor_content', 'Categories') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('categories_tour_form_translations_content', 'Categories') ?></li>
            <li><?= __('categories_tour_form_fields_content', 'Categories') ?></li>
            <li><?= __('categories_tour_form_settings_content', 'Categories') ?></li>
            <li><?= __($category ? 'categories_tour_form_edit_next_content' : 'categories_tour_form_create_next_content', 'Categories') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/categories') ?>" class="btn btn-primary"><?= __('categories_list', 'Categories') ?></a>
        </div>
    </div>
</div>

<form method="POST" action="<?= $formAction ?>" data-categories-translations-root data-tour-state="<?= $category ? 'edit' : 'create' ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="locale" value="<?= e($activeLocale) ?>" data-categories-active-locale>
    <input type="hidden" name="translation_group" value="<?= e($translationGroupValue) ?>">
    <input type="hidden" name="source_locale" value="<?= e($sourceLocale) ?>">

    <div class="categories-translation-bar" data-tour-target="categories-translation-tabs">
        <div class="categories-translation-tabs" role="tablist" aria-label="<?= e($categoryLabel('translations', __('translations', 'Categories'))) ?>">
            <?php foreach ($translationTabs as $tab): ?>
                <?php
                $localeCode = (string) ($tab['code'] ?? '');
                $tabClasses = ['categories-translation-tab'];
                if ($localeCode === $activeLocale) {
                    $tabClasses[] = 'is-active';
                }
                if (empty($tab['exists'])) {
                    $tabClasses[] = 'is-missing';
                }
                if (!empty($tab['is_source'])) {
                    $tabClasses[] = 'is-source';
                }
                $tabBadge = !empty($tab['is_source'])
                    ? $categoryLabel('translation_source', __('translation_source', 'Categories'))
                    : (!empty($tab['exists'])
                        ? $categoryLabel('translation_ready', __('translation_ready', 'Categories'))
                        : $categoryLabel('translation_missing', __('translation_missing', 'Categories')));
                ?>
                <button
                    type="button"
                    class="<?= e(implode(' ', $tabClasses)) ?>"
                    data-categories-tab-btn
                    data-tab="<?= e($localeCode) ?>"
                    data-tab-state="<?= e(!empty($tab['is_source']) ? 'source' : (!empty($tab['exists']) ? 'ready' : 'missing')) ?>"
                    data-categories-label-source="<?= e($categoriesPanelLabel($tab['form_labels'] ?? [], 'translation_source', __('translation_source', 'Categories'))) ?>"
                    data-categories-label-ready="<?= e($categoriesPanelLabel($tab['form_labels'] ?? [], 'translation_ready', __('translation_ready', 'Categories'))) ?>"
                    data-categories-label-missing="<?= e($categoriesPanelLabel($tab['form_labels'] ?? [], 'translation_missing', __('translation_missing', 'Categories'))) ?>"
                    role="tab"
                    aria-selected="<?= $localeCode === $activeLocale ? 'true' : 'false' ?>"
                    title="<?= e((string) ($tab['label'] ?? '')) ?>"
                >
                    <span class="categories-translation-tab-icon" aria-hidden="true">
                        <span class="categories-translation-flag"><?= $categoriesLocaleFlag($localeCode) ?></span>
                    </span>
                    <span class="categories-translation-tab-badge"><?= e($tabBadge) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-layout-sidebar">
        <div data-tour-target="categories-primary-editor">
            <div class="card categories-translation-panels" data-tour-target="categories-translation-content" data-tour-section="categories-form-fields">
                <?php foreach ($translationTabs as $tab): ?>
                    <?php
                    $localeCode = (string) ($tab['code'] ?? '');
                    $tabValues = is_array($tab['values'] ?? null) ? $tab['values'] : [];
                    if (is_array($oldTranslations[$localeCode] ?? null)) {
                        $tabValues = array_merge($tabValues, $oldTranslations[$localeCode]);
                    }
                    $panelLabels = is_array($tab['form_labels'] ?? null) ? $tab['form_labels'] : [];
                    $isActivePanel = $localeCode === $activeLocale;
                    ?>
                    <section
                        class="categories-translation-panel<?= $isActivePanel ? ' is-active' : '' ?>"
                        data-categories-panel="<?= e($localeCode) ?>"
                        role="tabpanel"
                        <?= $isActivePanel ? '' : 'hidden' ?>
                    >
                        <div class="form-group">
                            <label for="category_<?= e($localeCode) ?>_name" class="form-label"><?= e($categoriesPanelLabel($panelLabels, 'name', __('name', 'Categories'))) ?><?= $localeCode === $sourceLocale ? ' *' : '' ?></label>
                            <input
                                type="text"
                                id="category_<?= e($localeCode) ?>_name"
                                name="translations[<?= e($localeCode) ?>][name]"
                                class="form-input"
                                value="<?= e((string) ($tabValues['name'] ?? '')) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="category_<?= e($localeCode) ?>_slug" class="form-label"><?= e($categoriesPanelLabel($panelLabels, 'slug', __('slug', 'Categories'))) ?></label>
                            <input
                                type="text"
                                id="category_<?= e($localeCode) ?>_slug"
                                name="translations[<?= e($localeCode) ?>][slug]"
                                class="form-input"
                                value="<?= e((string) ($tabValues['slug'] ?? '')) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="category_<?= e($localeCode) ?>_description" class="form-label"><?= e($categoriesPanelLabel($panelLabels, 'description', __('description', 'Categories'))) ?></label>
                            <textarea
                                id="category_<?= e($localeCode) ?>_description"
                                name="translations[<?= e($localeCode) ?>][description]"
                                class="form-input"
                                rows="5"
                                data-no-editor
                            ><?= e((string) ($tabValues['description'] ?? '')) ?></textarea>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>

        <div data-tour-target="categories-form-sidebar">
            <div class="card" data-tour-target="categories-form-settings">
                <div class="form-group">
                    <label for="module" class="form-label"><?= e($categoryLabel('module', __('module', 'Categories'))) ?></label>
                    <select id="module" name="module" class="form-select">
                        <?php foreach ($moduleOptions as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= selected($value, $currentModule) ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h3 class="card-title card-title-spaced"><?= e($categoryLabel('status', __('status', 'Categories'))) ?></h3>
                <div class="form-group">
                    <select id="status" name="status" class="form-select">
                        <option value="active" <?= selected('active', $currentStatus) ?>><?= e($categoryLabel('status_active', __('status_active', 'Categories'))) ?></option>
                        <option value="inactive" <?= selected('inactive', $currentStatus) ?>><?= e($categoryLabel('status_inactive', __('status_inactive', 'Categories'))) ?></option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block" data-tour-target="categories-form-save"><?= e($categoryLabel('save', __('save', 'Core'))) ?></button>
            </div>
        </div>
    </div>
</form>

<script src="<?= module_asset('Categories', 'js/categories.js') ?>"></script>
