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
$translationUi = is_array($translationUi ?? null) ? $translationUi : [];
$translationTabs = is_array($translationUi['tabs'] ?? null) ? $translationUi['tabs'] : [];

$footerLabel = static function (array $labelBag, string $key, string $fallback = ''): string {
    $value = $labelBag[$key] ?? null;
    return is_string($value) && trim($value) !== '' ? $value : $fallback;
};

$footerLocaleFlag = static function (string $locale): string {
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

<link rel="stylesheet" href="<?= module_asset('Footer', 'css/footer-module.css') ?>">

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
        <p class="page-subtitle"><?= __('footer_subtitle', 'Footer') ?></p>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-window-maximize"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('footer_help_badge', 'Footer') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('footer_help_title', 'Footer') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('footer_help_intro', 'Footer') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('footer_help_step_translations', 'Footer') ?></li>
            <li><?= __('footer_help_step_branding', 'Footer') ?></li>
            <li><?= __('footer_help_step_powered', 'Footer') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="#footerTranslationsCard" class="btn btn-primary"><?= __('footer_help_action_translations', 'Footer') ?></a>
            <a href="#footerPoweredCard" class="btn btn-secondary"><?= __('footer_help_action_powered', 'Footer') ?></a>
        </div>
    </div>
</div>

<form method="POST" action="<?= url('/admin/footer') ?>" class="settings-form" data-footer-translations-root>
    <?= csrf_field() ?>
    <input
        type="hidden"
        name="locale"
        value="<?= e((string) ($translationUi['active_locale'] ?? '')) ?>"
        data-footer-active-locale
    >
    <input type="hidden" name="source_locale" value="<?= e((string) ($translationUi['source_locale'] ?? '')) ?>">

    <div class="card" id="footerTranslationsCard">
        <h3 class="card-title card-title-spaced"><?= __('footer_title', 'Footer') ?></h3>

        <input type="hidden" name="enabled" value="0">
        <label class="form-inline footer-settings-toggle">
            <input type="checkbox" class="form-check-input" name="enabled" value="1" <?= !empty($footer['enabled']) ? 'checked' : '' ?>>
            <?= __('footer_enabled', 'Footer') ?>
        </label>

        <div class="footer-translation-bar">
            <div class="footer-translation-tabs" role="tablist" aria-label="<?= e(__('translations', 'Footer')) ?>">
                <?php foreach ($translationTabs as $tab): ?>
                <?php
                    $tabClasses = ['footer-translation-tab'];
                    $labels = is_array($tab['form_labels'] ?? null) ? $tab['form_labels'] : [];
                    if (!empty($tab['is_active'])) {
                        $tabClasses[] = 'is-active';
                    }
                    if (empty($tab['exists'])) {
                        $tabClasses[] = 'is-missing';
                    }
                    if (!empty($tab['is_source'])) {
                        $tabClasses[] = 'is-source';
                    }
                    $tabBadge = !empty($tab['is_source'])
                        ? __('translation_source', 'Footer')
                        : (!empty($tab['exists']) ? __('translation_ready', 'Footer') : __('translation_missing', 'Footer'));
                    ?>
                    <button
                        type="button"
                        class="<?= e(implode(' ', $tabClasses)) ?>"
                        data-footer-tab-btn
                        data-tab="<?= e((string) ($tab['code'] ?? '')) ?>"
                        data-tab-state="<?= e(!empty($tab['is_source']) ? 'source' : (!empty($tab['exists']) ? 'ready' : 'missing')) ?>"
                        data-footer-label-source="<?= e($footerLabel($labels, 'translation_source', __('translation_source', 'Footer'))) ?>"
                        data-footer-label-ready="<?= e($footerLabel($labels, 'translation_ready', __('translation_ready', 'Footer'))) ?>"
                        data-footer-label-missing="<?= e($footerLabel($labels, 'translation_missing', __('translation_missing', 'Footer'))) ?>"
                        role="tab"
                        aria-selected="<?= !empty($tab['is_active']) ? 'true' : 'false' ?>"
                        title="<?= e((string) ($tab['label'] ?? '')) ?>"
                    >
                        <span class="footer-translation-tab-icon" aria-hidden="true">
                            <span class="footer-translation-flag"><?= $footerLocaleFlag((string) ($tab['code'] ?? '')) ?></span>
                        </span>
                        <span class="footer-translation-tab-badge"><?= e($tabBadge) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="footer-translation-panels">
            <?php foreach ($translationTabs as $tab): ?>
                <?php
                $localeCode = (string) ($tab['code'] ?? '');
                $values = is_array($tab['values'] ?? null) ? $tab['values'] : [];
                $labels = is_array($tab['form_labels'] ?? null) ? $tab['form_labels'] : [];
                ?>
                <section
                    class="footer-translation-panel<?= !empty($tab['is_active']) ? ' is-active' : '' ?>"
                    data-footer-panel="<?= e($localeCode) ?>"
                    role="tabpanel"
                    <?= !empty($tab['is_active']) ? '' : 'hidden' ?>
                >
                    <div class="form-group">
                        <label for="footer_<?= e($localeCode) ?>_brand_text" class="form-label"><?= e($footerLabel($labels, 'footer_brand_text', __('footer_brand_text', 'Footer'))) ?></label>
                        <input
                            type="text"
                            id="footer_<?= e($localeCode) ?>_brand_text"
                            name="translations[<?= e($localeCode) ?>][brand_text]"
                            class="form-input"
                            value="<?= e((string) ($values['brand_text'] ?? '')) ?>"
                        >
                        <div class="form-hint"><?= e($footerLabel($labels, 'footer_brand_text_hint', __('footer_brand_text_hint', 'Footer'))) ?></div>
                    </div>

                    <div class="form-group">
                        <label for="footer_<?= e($localeCode) ?>_copyright_text" class="form-label"><?= e($footerLabel($labels, 'footer_copyright_text', __('footer_copyright_text', 'Footer'))) ?></label>
                        <textarea
                            id="footer_<?= e($localeCode) ?>_copyright_text"
                            name="translations[<?= e($localeCode) ?>][copyright_text]"
                            class="form-input"
                            rows="4"
                            data-no-editor
                        ><?= e((string) ($values['copyright_text'] ?? '')) ?></textarea>
                        <div class="form-hint"><?= e($footerLabel($labels, 'footer_copyright_hint', __('footer_copyright_hint', 'Footer'))) ?></div>
                        <div class="form-hint"><?= e($footerLabel($labels, 'footer_tokens_hint', __('footer_tokens_hint', 'Footer'))) ?></div>
                    </div>

                    <div class="form-group">
                        <label for="footer_<?= e($localeCode) ?>_powered_by_label" class="form-label"><?= e($footerLabel($labels, 'footer_powered_by_label', __('footer_powered_by_label', 'Footer'))) ?></label>
                        <input
                            type="text"
                            id="footer_<?= e($localeCode) ?>_powered_by_label"
                            name="translations[<?= e($localeCode) ?>][powered_by_label]"
                            class="form-input"
                            value="<?= e((string) ($values['powered_by_label'] ?? '')) ?>"
                        >
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card" id="footerPoweredCard">
        <h3 class="card-title card-title-spaced"><?= __('footer_powered_by', 'Footer') ?></h3>

        <input type="hidden" name="powered_by_enabled" value="0">
        <label class="form-inline footer-settings-toggle">
            <input type="checkbox" class="form-check-input" name="powered_by_enabled" value="1" <?= !empty($poweredBy['enabled']) ? 'checked' : '' ?>>
            <?= __('footer_powered_by_enabled', 'Footer') ?>
        </label>

        <div class="form-group">
            <label for="powered_by_url" class="form-label"><?= __('footer_powered_by_url', 'Footer') ?></label>
            <input type="text" id="powered_by_url" name="powered_by_url" class="form-input" value="<?= e((string) ($poweredBy['url'] ?? '')) ?>" placeholder="https://flat-cms.fr">
        </div>
    </div>

    <div class="form-actions form-actions-divider">
        <button type="submit" class="btn btn-primary"><?= __('save', 'Core') ?></button>
    </div>
</form>

<script src="<?= module_asset('Footer', 'js/footer.js') ?>"></script>
