<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$page = is_array($page ?? null) ? $page : null;
$formLabels = is_array($formLabels ?? null) ? $formLabels : [];
$translationUi = is_array($translationUi ?? null) ? $translationUi : [];
$translationUi = array_merge([
    'source_status' => 'draft',
    'tabs' => [],
], $translationUi);
$translationTabs = is_array($translationUi['tabs'] ?? null) ? $translationUi['tabs'] : [];
$hasTranslationTabs = !empty($translationTabs);
$oldTranslations = old('translations', []);
if (!is_array($oldTranslations)) {
    $oldTranslations = [];
}
$activeLocale = (string) old('locale', $translationUi['active_locale'] ?? '');
$sourceLocale = (string) old('source_locale', $translationUi['source_locale'] ?? $activeLocale);
$translationGroupValue = (string) old('translation_group', $translationUi['translation_group'] ?? '');
$currentStatus = (string) old('status', $translationUi['source_status'] ?? 'draft');
$formAction = $page ? url('/admin/pages/' . $page['id']) : url('/admin/pages');
$pagesAiActiveLocaleLabel = (string) ($translationUi['active_locale_label'] ?? $activeLocale);
$pagesAiSourceLocaleLabel = (string) ($translationUi['source_locale_label'] ?? $sourceLocale);

if ($activeLocale === '' && $translationTabs !== []) {
    $activeLocale = (string) ($translationTabs[0]['code'] ?? '');
}

$pageLabel = static function (string $key, string $fallback = '') use ($formLabels): string {
    $value = $formLabels[$key] ?? null;
    return is_string($value) && trim($value) !== '' ? $value : $fallback;
};

$pagesPanelLabel = static function (array $labelBag, string $key, string $fallback = ''): string {
    $value = $labelBag[$key] ?? null;
    return is_string($value) && trim($value) !== '' ? $value : $fallback;
};

$pagesMediaContext = static function (array $values = []) use ($page): string {
    $slug = trim((string) ($values['slug'] ?? ''));
    if ($slug === '' && is_array($page)) {
        $slug = trim((string) ($page['slug'] ?? $page['id'] ?? ''));
    }

    $slug = str_slug($slug);
    return 'pages/' . ($slug !== '' ? $slug : 'draft');
};

$pagesLocaleFlag = static function (string $locale): string {
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

<link rel="stylesheet" href="<?= module_asset('Pages', 'css/pages.css') ?>">

<div class="page-header">
    <h1 class="page-title" data-pages-dynamic-page-title><?= e($pageTitle) ?></h1>
    <div class="page-header-actions">
        <a href="<?= url('/admin/pages') ?>" class="btn btn-secondary" data-pages-dynamic-back-label>
            <?= e($pageLabel('back', __('back', 'Core'))) ?>
        </a>
    </div>
</div>

<?php if (!empty($menuCustomAlert)): ?>
    <div class="alert alert-warning" data-auto-dismiss="5000">
        <span><?= e($menuCustomAlert) ?></span>
        <button type="button" class="alert-close" aria-label="<?= e(__('close', 'Core')) ?>">&times;</button>
    </div>
<?php endif; ?>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-file-alt"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('pages_help_badge', 'Pages') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('pages_tour_form_editor_title', 'Pages') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('pages_tour_form_editor_content', 'Pages') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('pages_tour_form_translations_content', 'Pages') ?></li>
            <li><?= __('pages_tour_form_fields_content', 'Pages') ?></li>
            <li><?= __('pages_tour_form_status_content', 'Pages') ?></li>
            <li><?= __('pages_tour_form_seo_content', 'Pages') ?></li>
            <li><?= __($page ? 'pages_tour_form_edit_next_content' : 'pages_tour_form_create_next_content', 'Pages') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/pages') ?>" class="btn btn-primary"><?= __('pages_list', 'Pages') ?></a>
        </div>
    </div>
</div>

<form
    method="POST"
    action="<?= $formAction ?>"
    <?= $hasTranslationTabs ? ' data-pages-translations-root' : '' ?>
    data-ai-agent-form="pages"
    data-tour-state="<?= $page ? 'edit' : 'create' ?>"
>
    <?= csrf_field() ?>
    <?php if ($hasTranslationTabs): ?>
        <input type="hidden" name="locale" value="<?= e($activeLocale) ?>" data-pages-active-locale>
        <input type="hidden" name="translation_group" value="<?= e($translationGroupValue) ?>">
        <input type="hidden" name="source_locale" value="<?= e($sourceLocale) ?>">
    <?php endif; ?>

    <?php if ($hasTranslationTabs): ?>
        <div class="pages-translation-bar" data-tour-target="pages-translation-tabs">
            <div class="pages-translation-tabs" role="tablist" aria-label="<?= e($pageLabel('translations', __('translations', 'Pages'))) ?>">
                <?php foreach ($translationTabs as $tab): ?>
                    <?php
                    $localeCode = (string) ($tab['code'] ?? '');
                    $tabClasses = ['pages-translation-tab'];
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
                        ? $pageLabel('translation_source', __('translation_source', 'Pages'))
                        : (!empty($tab['exists'])
                            ? $pageLabel('translation_ready', __('translation_ready', 'Pages'))
                            : $pageLabel('translation_missing', __('translation_missing', 'Pages')));
                    ?>
                    <button
                        type="button"
                        class="<?= e(implode(' ', $tabClasses)) ?>"
                        data-pages-tab-btn
                        data-tab="<?= e($localeCode) ?>"
                        data-tab-state="<?= e(!empty($tab['is_source']) ? 'source' : (!empty($tab['exists']) ? 'ready' : 'missing')) ?>"
                        data-pages-label-source="<?= e($pagesPanelLabel($tab['form_labels'] ?? [], 'translation_source', __('translation_source', 'Pages'))) ?>"
                        data-pages-label-ready="<?= e($pagesPanelLabel($tab['form_labels'] ?? [], 'translation_ready', __('translation_ready', 'Pages'))) ?>"
                        data-pages-label-missing="<?= e($pagesPanelLabel($tab['form_labels'] ?? [], 'translation_missing', __('translation_missing', 'Pages'))) ?>"
                        data-pages-page-title="<?= e((string) ($tab['page_title'] ?? $pageTitle)) ?>"
                        data-pages-back-label="<?= e($pagesPanelLabel($tab['form_labels'] ?? [], 'back', __('back', 'Core'))) ?>"
                        data-pages-status-label="<?= e($pagesPanelLabel($tab['form_labels'] ?? [], 'status', __('status', 'Pages'))) ?>"
                        data-pages-status-draft-label="<?= e($pagesPanelLabel($tab['form_labels'] ?? [], 'status_draft', __('status_draft', 'Pages'))) ?>"
                        data-pages-status-published-label="<?= e($pagesPanelLabel($tab['form_labels'] ?? [], 'status_published', __('status_published', 'Pages'))) ?>"
                        data-pages-save-label="<?= e($pagesPanelLabel($tab['form_labels'] ?? [], 'save', __('save', 'Core'))) ?>"
                        role="tab"
                        aria-selected="<?= $localeCode === $activeLocale ? 'true' : 'false' ?>"
                        title="<?= e((string) ($tab['label'] ?? '')) ?>"
                    >
                        <span class="pages-translation-tab-icon" aria-hidden="true">
                            <span class="pages-translation-flag"><?= $pagesLocaleFlag($localeCode) ?></span>
                        </span>
                        <span class="pages-translation-tab-badge"><?= e($tabBadge) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-layout-sidebar">
        <div data-tour-target="pages-primary-editor">
            <?php if ($hasTranslationTabs): ?>
                <div class="card pages-translation-panels" data-tour-target="pages-translation-content" data-tour-section="pages-form-fields">
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
                            class="pages-translation-panel<?= $isActivePanel ? ' is-active' : '' ?>"
                            data-pages-panel="<?= e($localeCode) ?>"
                            role="tabpanel"
                            <?= $isActivePanel ? '' : 'hidden' ?>
                        >
                            <div
                                class="form-group"
                                data-ai-agent-target
                                data-ai-agent-module="pages"
                                data-ai-agent-entity="page"
                                data-ai-agent-block="content"
                                data-ai-agent-block-label="<?= e(__('content', 'Pages')) ?>"
                                data-ai-agent-field="title"
                                data-ai-agent-field-kind="text"
                                data-ai-agent-label="<?= e($pagesPanelLabel($panelLabels, 'title', __('title', 'Pages'))) ?>"
                            >
                                <label for="page_<?= e($localeCode) ?>_title" class="form-label"><?= e($pagesPanelLabel($panelLabels, 'title', __('title', 'Pages'))) ?><?= $localeCode === $sourceLocale ? ' *' : '' ?></label>
                                <input
                                    type="text"
                                    id="page_<?= e($localeCode) ?>_title"
                                    name="translations[<?= e($localeCode) ?>][title]"
                                    class="form-input"
                                    value="<?= e((string) ($tabValues['title'] ?? '')) ?>"
                                >
                            </div>

                            <div
                                class="form-group"
                                data-ai-agent-target
                                data-ai-agent-module="pages"
                                data-ai-agent-entity="page"
                                data-ai-agent-block="content"
                                data-ai-agent-block-label="<?= e(__('content', 'Pages')) ?>"
                                data-ai-agent-field="slug"
                                data-ai-agent-field-kind="text"
                                data-ai-agent-label="<?= e($pagesPanelLabel($panelLabels, 'slug', __('slug', 'Pages'))) ?>"
                            >
                                <label for="page_<?= e($localeCode) ?>_slug" class="form-label"><?= e($pagesPanelLabel($panelLabels, 'slug', __('slug', 'Pages'))) ?></label>
                                <input
                                    type="text"
                                    id="page_<?= e($localeCode) ?>_slug"
                                    name="translations[<?= e($localeCode) ?>][slug]"
                                    class="form-input"
                                    value="<?= e((string) ($tabValues['slug'] ?? '')) ?>"
                                    placeholder="<?= e(__('placeholder_slug', 'Pages')) ?>"
                                >
                            </div>

                            <div
                                class="form-group"
                                data-ai-agent-target
                                data-ai-agent-module="pages"
                                data-ai-agent-entity="page"
                                data-ai-agent-block="content"
                                data-ai-agent-block-label="<?= e(__('content', 'Pages')) ?>"
                                data-ai-agent-field="content"
                                data-ai-agent-field-kind="richtext"
                                data-ai-agent-label="<?= e($pagesPanelLabel($panelLabels, 'content', __('content', 'Pages'))) ?>"
                            >
                                <label for="page_<?= e($localeCode) ?>_content" class="form-label"><?= e($pagesPanelLabel($panelLabels, 'content', __('content', 'Pages'))) ?></label>
                                <textarea
                                    id="page_<?= e($localeCode) ?>_content"
                                    name="translations[<?= e($localeCode) ?>][content]"
                                    class="form-input"
                                    rows="15"
                                    data-page-suneditor
                                    data-suneditor-media-context="<?= e($pagesMediaContext($tabValues)) ?>"
                                    data-suneditor-toolbar-expand="<?= e($pagesPanelLabel($panelLabels, 'suneditor_toolbar_expand', __('suneditor_toolbar_expand', 'Pages'))) ?>"
                                    data-suneditor-toolbar-collapse="<?= e($pagesPanelLabel($panelLabels, 'suneditor_toolbar_collapse', __('suneditor_toolbar_collapse', 'Pages'))) ?>"
                                    data-suneditor-media-modal-error="<?= e($pagesPanelLabel($panelLabels, 'suneditor_media_modal_unavailable', __('suneditor_media_modal_unavailable', 'Pages'))) ?>"
                                ><?= e((string) ($tabValues['content'] ?? '')) ?></textarea>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card" data-tour-target="pages-form-fields">
                    <div
                        class="form-group"
                        data-ai-agent-target
                        data-ai-agent-module="pages"
                        data-ai-agent-entity="page"
                        data-ai-agent-block="content"
                        data-ai-agent-block-label="<?= e(__('content', 'Pages')) ?>"
                        data-ai-agent-field="title"
                        data-ai-agent-field-kind="text"
                        data-ai-agent-label="<?= e($pageLabel('title', __('title', 'Pages'))) ?>"
                    >
                        <label for="title" class="form-label"><?= e($pageLabel('title', __('title', 'Pages'))) ?> *</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="form-input"
                            value="<?= e(old('title', $page['title'] ?? '')) ?>"
                            required
                        >
                    </div>

                    <div
                        class="form-group"
                        data-ai-agent-target
                        data-ai-agent-module="pages"
                        data-ai-agent-entity="page"
                        data-ai-agent-block="content"
                        data-ai-agent-block-label="<?= e(__('content', 'Pages')) ?>"
                        data-ai-agent-field="slug"
                        data-ai-agent-field-kind="text"
                        data-ai-agent-label="<?= e($pageLabel('slug', __('slug', 'Pages'))) ?>"
                    >
                        <label for="slug" class="form-label"><?= e($pageLabel('slug', __('slug', 'Pages'))) ?></label>
                        <input
                            type="text"
                            id="slug"
                            name="slug"
                            class="form-input"
                            value="<?= e(old('slug', $page['slug'] ?? '')) ?>"
                            placeholder="<?= __('placeholder_slug', 'Pages') ?>"
                        >
                    </div>

                    <div
                        class="form-group"
                        data-ai-agent-target
                        data-ai-agent-module="pages"
                        data-ai-agent-entity="page"
                        data-ai-agent-block="content"
                        data-ai-agent-block-label="<?= e(__('content', 'Pages')) ?>"
                        data-ai-agent-field="content"
                        data-ai-agent-field-kind="richtext"
                        data-ai-agent-label="<?= e($pageLabel('content', __('content', 'Pages'))) ?>"
                    >
                        <label for="content" class="form-label"><?= e($pageLabel('content', __('content', 'Pages'))) ?></label>
                        <textarea
                            id="content"
                            name="content"
                            class="form-input"
                            rows="15"
                            data-page-suneditor
                            data-suneditor-media-context="<?= e($pagesMediaContext(['slug' => old('slug', $page['slug'] ?? '')])) ?>"
                            data-suneditor-toolbar-expand="<?= e($pageLabel('suneditor_toolbar_expand', __('suneditor_toolbar_expand', 'Pages'))) ?>"
                            data-suneditor-toolbar-collapse="<?= e($pageLabel('suneditor_toolbar_collapse', __('suneditor_toolbar_collapse', 'Pages'))) ?>"
                            data-suneditor-media-modal-error="<?= e($pageLabel('suneditor_media_modal_unavailable', __('suneditor_media_modal_unavailable', 'Pages'))) ?>"
                        ><?= e(old('content', $page['content'] ?? '')) ?></textarea>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div data-tour-target="pages-form-sidebar">
            <div class="card" data-tour-target="pages-form-status">
                <h3 class="card-title card-title-spaced" data-pages-dynamic-status-label><?= e($pageLabel('status', __('status', 'Pages'))) ?></h3>

                <div class="form-group">
                    <select id="status" name="status" class="form-select">
                        <option value="draft" data-pages-dynamic-status-draft-label <?= selected('draft', $currentStatus) ?>>
                            <?= e($pageLabel('status_draft', __('status_draft', 'Pages'))) ?>
                        </option>
                        <option value="published" data-pages-dynamic-status-published-label <?= selected('published', $currentStatus) ?>>
                            <?= e($pageLabel('status_published', __('status_published', 'Pages'))) ?>
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block" data-tour-target="pages-form-save" data-pages-dynamic-save-label>
                    <?= e($pageLabel('save', __('save', 'Core'))) ?>
                </button>
            </div>

            <?php if ($hasTranslationTabs): ?>
                <div class="card pages-translation-panels" data-tour-target="pages-form-seo">
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
                            class="pages-translation-panel<?= $isActivePanel ? ' is-active' : '' ?>"
                            data-pages-panel="<?= e($localeCode) ?>"
                            role="tabpanel"
                            <?= $isActivePanel ? '' : 'hidden' ?>
                        >
                            <h3 class="card-title card-title-spaced"><?= e($pagesPanelLabel($panelLabels, 'seo_section', __('seo_section', 'Pages'))) ?></h3>

                            <div
                                class="form-group"
                                data-ai-agent-target
                                data-ai-agent-module="pages"
                                data-ai-agent-entity="page"
                                data-ai-agent-block="seo"
                                data-ai-agent-block-label="<?= e($pagesPanelLabel($panelLabels, 'seo_section', __('seo_section', 'Pages'))) ?>"
                                data-ai-agent-field="meta_title"
                                data-ai-agent-field-kind="text"
                                data-ai-agent-label="<?= e($pagesPanelLabel($panelLabels, 'meta_title', __('meta_title', 'Pages'))) ?>"
                            >
                                <label for="page_<?= e($localeCode) ?>_meta_title" class="form-label"><?= e($pagesPanelLabel($panelLabels, 'meta_title', __('meta_title', 'Pages'))) ?></label>
                                <input
                                    type="text"
                                    id="page_<?= e($localeCode) ?>_meta_title"
                                    name="translations[<?= e($localeCode) ?>][meta_title]"
                                    class="form-input"
                                    value="<?= e((string) ($tabValues['meta_title'] ?? '')) ?>"
                                >
                            </div>

                            <div
                                class="form-group"
                                data-ai-agent-target
                                data-ai-agent-module="pages"
                                data-ai-agent-entity="page"
                                data-ai-agent-block="seo"
                                data-ai-agent-block-label="<?= e($pagesPanelLabel($panelLabels, 'seo_section', __('seo_section', 'Pages'))) ?>"
                                data-ai-agent-field="meta_description"
                                data-ai-agent-field-kind="textarea"
                                data-ai-agent-label="<?= e($pagesPanelLabel($panelLabels, 'meta_description', __('meta_description', 'Pages'))) ?>"
                            >
                                <label for="page_<?= e($localeCode) ?>_meta_description" class="form-label"><?= e($pagesPanelLabel($panelLabels, 'meta_description', __('meta_description', 'Pages'))) ?></label>
                                <textarea
                                    id="page_<?= e($localeCode) ?>_meta_description"
                                    name="translations[<?= e($localeCode) ?>][meta_description]"
                                    class="form-input"
                                    rows="3"
                                    data-no-editor
                                ><?= e((string) ($tabValues['meta_description'] ?? '')) ?></textarea>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card" data-tour-target="pages-form-seo">
                    <h3 class="card-title card-title-spaced"><?= e($pageLabel('seo_section', __('seo_section', 'Pages'))) ?></h3>

                    <div
                        class="form-group"
                        data-ai-agent-target
                        data-ai-agent-module="pages"
                        data-ai-agent-entity="page"
                        data-ai-agent-block="seo"
                        data-ai-agent-block-label="<?= e($pageLabel('seo_section', __('seo_section', 'Pages'))) ?>"
                        data-ai-agent-field="meta_title"
                        data-ai-agent-field-kind="text"
                        data-ai-agent-label="<?= e($pageLabel('meta_title', __('meta_title', 'Pages'))) ?>"
                    >
                        <label for="meta_title" class="form-label"><?= e($pageLabel('meta_title', __('meta_title', 'Pages'))) ?></label>
                        <input
                            type="text"
                            id="meta_title"
                            name="meta_title"
                            class="form-input"
                            value="<?= e(old('meta_title', $page['meta_title'] ?? '')) ?>"
                        >
                    </div>

                    <div
                        class="form-group"
                        data-ai-agent-target
                        data-ai-agent-module="pages"
                        data-ai-agent-entity="page"
                        data-ai-agent-block="seo"
                        data-ai-agent-block-label="<?= e($pageLabel('seo_section', __('seo_section', 'Pages'))) ?>"
                        data-ai-agent-field="meta_description"
                        data-ai-agent-field-kind="textarea"
                        data-ai-agent-label="<?= e($pageLabel('meta_description', __('meta_description', 'Pages'))) ?>"
                    >
                        <label for="meta_description" class="form-label"><?= e($pageLabel('meta_description', __('meta_description', 'Pages'))) ?></label>
                        <textarea
                            id="meta_description"
                            name="meta_description"
                            class="form-input"
                            rows="3"
                            data-no-editor
                        ><?= e(old('meta_description', $page['meta_description'] ?? '')) ?></textarea>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<script src="<?= module_asset('Pages', 'js/pages-suneditor.js') ?>"></script>
<script src="<?= module_asset('Pages', 'js/pages.js') ?>"></script>

<?php
$mediaEnabled = (new \App\Core\ModuleManager([
    BASE_PATH . '/app/Modules',
    BASE_PATH . '/app/Extensions',
], BASE_PATH . '/data/modules.json'))->isEnabled('Media');
?>

<?php if ($mediaEnabled): ?>
    <?php include BASE_PATH . '/app/Modules/Media/Views/admin/partials/media-modal.php'; ?>
    <script src="<?= module_asset('Media', 'js/media-modal.js') ?>"></script>
<?php endif; ?>
