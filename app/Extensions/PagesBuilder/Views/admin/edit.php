<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

?>

<?php
$builderLicense = is_array($builderLicense ?? null) ? $builderLicense : [];
$builderLicenseSummary = is_array($builderLicenseSummary ?? null)
    ? $builderLicenseSummary
    : (is_array($builderLicense['license_summary'] ?? null) ? $builderLicense['license_summary'] : []);
$builderLicenseStatus = trim((string) ($builderLicense['status'] ?? 'missing'));
$builderLicensed = $builderLicenseStatus === 'active';
$isLocalHost = !empty($isLocalHost);
$widgetPreviewAssets = is_array($widgetPreviewAssets ?? null) ? $widgetPreviewAssets : [];
$builderPreviewThemeCssUrl = trim((string) ($builderPreviewThemeCssUrl ?? ''));
$translationUi = is_array($translationUi ?? null) ? $translationUi : [];
$activeLocale = trim((string) ($activeLocale ?? ''));
$sourceLocale = trim((string) ($sourceLocale ?? ''));
$standardEditUrl = trim((string) ($standardEditUrl ?? ''));
$previewUrl = trim((string) ($previewUrl ?? ''));
$publishUrl = trim((string) ($publishUrl ?? ''));
$disableUrl = trim((string) ($disableUrl ?? ''));
$translationTabs = is_array($translationUi) ? $translationUi : [];
$flatcmsFrontendSettings = \App\Core\FlatFile::settings();
$frontendTheme = is_array($flatcmsFrontendSettings)
    ? (string) ($flatcmsFrontendSettings['frontend_theme'] ?? config('app.frontend_theme', 'default'))
    : (string) config('app.frontend_theme', 'default');
if (!in_array($frontendTheme, ['default', 'modern-pro', 'FlatCMS'], true)) {
    $frontendTheme = 'default';
}
$pageStatus = (string) ($page['status'] ?? 'draft');
$isDraftPage = $pageStatus !== 'published';
$isSourceLocale = $activeLocale !== '' && $activeLocale === $sourceLocale;
$showPreviewDraft = $isDraftPage || trim((string) ($page['id'] ?? '')) === '';
$showPublishButton = $isSourceLocale && $isDraftPage;
$modeBadgeClass = 'badge-info';
$modeBadgeLabel = __('builder_mode_pro', 'PagesBuilder');
$pagesBuilderCssPath = BASE_PATH . '/app/Extensions/PagesBuilder/Assets/css/pages-builder.css';
$pagesBuilderJsPath = BASE_PATH . '/app/Extensions/PagesBuilder/Assets/js/pages-builder.js';
$pagesBuilderMobileLockJsPath = BASE_PATH . '/app/Extensions/PagesBuilder/Assets/js/pages-builder-mobile-lock.js';
$pagesBuilderCssVersion = is_file($pagesBuilderCssPath) ? (string) filemtime($pagesBuilderCssPath) : '';
$pagesBuilderJsVersion = is_file($pagesBuilderJsPath) ? (string) filemtime($pagesBuilderJsPath) : '';
$pagesBuilderMobileLockJsVersion = is_file($pagesBuilderMobileLockJsPath) ? (string) filemtime($pagesBuilderMobileLockJsPath) : '';
$licenseDomain = normalize_host((string) ($builderLicenseSummary['domain'] ?? ''));
if ($licenseDomain === '') {
    $licenseDomain = normalize_host((string) ($_SERVER['HTTP_HOST'] ?? ''));
}
if ($builderLicensed) {
    $licenseBadgeClass = 'badge-success';
    $licenseBadgeLabel = __('builder_license_badge_active_domain', 'PagesBuilder', ['domain' => $licenseDomain]);
} elseif ($isLocalHost || $builderLicenseStatus === 'local_bypass') {
    $licenseBadgeClass = 'badge-info';
    $licenseBadgeLabel = __('builder_license_badge_dev', 'PagesBuilder');
} else {
    $licenseBadgeClass = 'badge-danger';
    $licenseBadgeLabel = __('builder_license_badge_locked', 'PagesBuilder');
}

$pagesBuilderLocaleFlag = static function (string $locale): string {
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

<link rel="stylesheet" href="<?= module_asset('PagesBuilder', 'css/pages-builder.css') ?><?= $pagesBuilderCssVersion !== '' ? ('?v=' . rawurlencode($pagesBuilderCssVersion)) : '' ?>">
<?php if ($builderPreviewThemeCssUrl !== ''): ?>
    <link rel="stylesheet" href="<?= e($builderPreviewThemeCssUrl) ?>" data-pages-builder-preview-theme>
<?php endif; ?>
<?php foreach (($widgetPreviewAssets['css'] ?? []) as $href): ?>
    <link rel="stylesheet" href="<?= e((string) $href) ?>" data-pages-builder-widget-asset>
<?php endforeach; ?>

<div class="pb-editor-shell" id="pbEditorShell" data-frontend-theme="<?= e($frontendTheme) ?>">
    <div class="pb-editor-topbar" id="pbBuilderTopbar">
        <div class="page-header pb-editor-header">
            <div class="page-header-content">
                <p class="pb-editor-subtitle"><?= __('builder_subtitle', 'PagesBuilder') ?></p>
            </div>
            <div class="page-header-actions pb-editor-actions">
                <div class="pb-editor-action-row" data-admin-help-actions>
                    <a href="<?= e($standardEditUrl !== '' ? $standardEditUrl : url('/admin/pages-builder')) ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        <?= __('builder_back', 'PagesBuilder') ?>
                    </a>
                    <?php if ($showPreviewDraft && $previewUrl !== ''): ?>
                    <a
                        href="<?= e($previewUrl) ?>"
                        class="btn btn-ghost"
                        id="pbPreviewDraftBtn"
                        data-preview-url="<?= e($previewUrl) ?>"
                        target="_blank"
                        rel="noopener"
                    >
                        <i class="fas fa-eye"></i>
                        <?= __('builder_preview_draft', 'PagesBuilder') ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($showPublishButton && $publishUrl !== ''): ?>
                        <form method="POST" action="<?= e($publishUrl) ?>" class="pb-editor-inline-form">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-upload"></i>
                                <?= __('builder_publish', 'PagesBuilder') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                    <button type="button" id="pbSaveBtn" class="btn btn-primary"><?= __('builder_save', 'PagesBuilder') ?></button>
                </div>
            </div>
        </div>

        <div class="pb-editor-meta">
            <span class="badge <?= $modeBadgeClass ?>" id="pbModeBadge">
                <?= e($modeBadgeLabel) ?>
            </span>
            <span class="badge <?= $licenseBadgeClass ?>"><?= e($licenseBadgeLabel) ?></span>
            <span class="pb-save-status" id="pbSaveStatus"></span>
        </div>
    </div>

    <div class="card admin-guidance-card" data-admin-help-template hidden>
        <div class="card-body">
            <div class="admin-guidance-card__head">
                <div class="admin-guidance-card__eyebrow-row">
                    <span class="admin-guidance-card__icon" aria-hidden="true">
                        <i class="fas fa-layer-group"></i>
                    </span>
                    <span class="admin-guidance-card__eyebrow"><?= e($modeBadgeLabel) ?></span>
                </div>
                <h2 class="admin-guidance-card__title"><?= __('builder_title', 'PagesBuilder') ?></h2>
                <p class="admin-guidance-card__copy"><?= __('builder_subtitle', 'PagesBuilder') ?></p>
            </div>
            <ul class="admin-guidance-card__list">
                <li><?= __('builder_help_step_page_settings', 'PagesBuilder') ?></li>
                <li><?= __('builder_help_step_templates', 'PagesBuilder') ?></li>
                <li><?= __('builder_help_step_layout', 'PagesBuilder') ?></li>
                <li><?= __('builder_help_step_widgets', 'PagesBuilder') ?></li>
                <li><?= __('builder_help_step_widget_settings', 'PagesBuilder') ?></li>
                <li><?= __('builder_help_step_publication', 'PagesBuilder') ?></li>
            </ul>
            <div class="admin-guidance-card__actions">
                <a href="<?= e($standardEditUrl !== '' ? $standardEditUrl : url('/admin/pages-builder')) ?>" class="btn btn-primary"><?= __('builder_back', 'PagesBuilder') ?></a>
                <?php if ($showPreviewDraft && $previewUrl !== ''): ?>
                    <a href="<?= e($previewUrl) ?>" class="btn btn-secondary" target="_blank" rel="noopener"><?= __('builder_preview_draft', 'PagesBuilder') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="pb-editor-layout" id="pbEditorRoot">
        <button type="button" class="pb-sidebar-handle pb-sidebar-handle-left" id="pbToggleWidgets" aria-label="<?= __('builder_toggle_widgets', 'PagesBuilder') ?>">
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
        </button>

        <button type="button" class="pb-sidebar-handle pb-sidebar-handle-right" id="pbToggleInspector" aria-label="<?= __('builder_toggle_settings', 'PagesBuilder') ?>">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>

        <aside class="pb-sidebar pb-sidebar-left" id="pbWidgetsSidebar" data-side="left">
            <div class="pb-sidebar-body">
                <div class="pb-sidebar-topbar">
                    <h3><?= __('builder_widgets', 'PagesBuilder') ?></h3>
                </div>
                <div class="pb-widget-search-wrap">
                    <input type="text" id="pbWidgetSearch" class="form-input" placeholder="<?= __('builder_search_widget', 'PagesBuilder') ?>">
                </div>
                <div id="pbWidgetCatalog" class="pb-widget-catalog"></div>
                <section class="pb-source-section">
                    <h4 class="pb-sidebar-section-title"><?= __('available_items', 'Menu') ?></h4>
                    <div class="pb-widget-search-wrap pb-source-search-wrap">
                        <input type="text" id="pbSourceSearch" class="form-input" placeholder="<?= __('search', 'Core') ?>...">
                    </div>
                    <div id="pbSourceCatalog" class="pb-source-catalog"></div>
                    <div class="fc-builder-source-actions">
                        <button type="button" id="pbSourceAddSelected" class="btn btn-primary btn-sm" disabled>
                            <?= __('add', 'Core') ?>
                        </button>
                    </div>
                </section>
            </div>
        </aside>

        <main class="pb-canvas-wrap">
            <?php if ($translationTabs !== []): ?>
                <div class="pb-translation-bar" data-tour-target="pages-builder-translations">
                    <div class="pb-translation-tabs" role="tablist" aria-label="<?= e(__('translations', 'Pages')) ?>">
                        <?php foreach ($translationTabs as $tab): ?>
                            <?php
                            $localeCode = (string) ($tab['code'] ?? '');
                            $tabClasses = ['pb-translation-tab'];
                            if (!empty($tab['is_active'])) {
                                $tabClasses[] = 'is-active';
                            }
                            if (empty($tab['exists'])) {
                                $tabClasses[] = 'is-missing';
                            }
                            if (!empty($tab['is_source'])) {
                                $tabClasses[] = 'is-source';
                            }
                            $tabBadge = (string) ($tab['badge_label'] ?? __('translation_missing', 'Pages'));
                            ?>
                            <a
                                href="<?= e((string) ($tab['url'] ?? '#')) ?>"
                                class="<?= e(implode(' ', $tabClasses)) ?>"
                                role="tab"
                                aria-selected="<?= !empty($tab['is_active']) ? 'true' : 'false' ?>"
                                title="<?= e((string) ($tab['label'] ?? $localeCode)) ?>"
                            >
                                <span class="pb-translation-tab-icon" aria-hidden="true">
                                    <span class="pb-translation-flag"><?= $pagesBuilderLocaleFlag($localeCode) ?></span>
                                </span>
                                <span class="pb-translation-tab-badge"><?= e($tabBadge) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div id="pbCanvas" class="pb-canvas" aria-label="<?= e(__('builder_canvas_label', 'PagesBuilder')) ?>" data-tour-target="pages-builder-canvas"></div>
        </main>

        <aside class="pb-sidebar pb-sidebar-right" id="pbSettingsSidebar">
            <div class="pb-sidebar-body">
                <div class="pb-page-settings" id="pbPageSettingsPanel">
                    <div class="pb-sidebar-topbar">
                        <h3><?= __('builder_page_settings_title', 'PagesBuilder') ?></h3>
                    </div>

                    <section class="pb-inspector-group pb-page-settings-group" data-group="page" data-tour-target="pages-builder-page-settings">
                        <div class="pb-inspector-group-toggle">
                            <span class="pb-inspector-group-title"><?= __('builder_page_settings_group_page', 'PagesBuilder') ?></span>
                        </div>
                        <div class="pb-inspector-group-fields">
                            <div class="form-group">
                                <label for="pbPageTitleInput" class="form-label"><?= __('title', 'Pages') ?></label>
                                <input
                                    type="text"
                                    id="pbPageTitleInput"
                                    class="form-input"
                                    value="<?= e((string) ($page['title'] ?? '')) ?>"
                                    placeholder="<?= e(__('title', 'Pages')) ?>"
                                    autocomplete="on"
                                >
                            </div>

                            <div class="form-group">
                                <span class="form-label"><?= __('slug', 'Pages') ?></span>
                                <code class="pb-page-settings-slug-value" id="pbPageSlugPreview"><?= e((string) ($page['slug'] ?? '')) ?></code>
                            </div>
                        </div>
                    </section>

                    <section class="pb-inspector-group pb-page-settings-group" data-group="seo" data-tour-target="pages-builder-seo-settings">
                        <div class="pb-inspector-group-toggle">
                            <span class="pb-inspector-group-title"><?= __('seo_section', 'Pages') ?></span>
                        </div>
                        <div class="pb-inspector-group-fields">
                            <div class="form-group">
                                <label for="pbPageMetaTitleInput" class="form-label"><?= __('meta_title', 'Pages') ?></label>
                                <input
                                    type="text"
                                    id="pbPageMetaTitleInput"
                                    class="form-input"
                                    value="<?= e((string) ($page['meta_title'] ?? '')) ?>"
                                    autocomplete="on"
                                >
                            </div>

                            <div class="form-group">
                                <label for="pbPageMetaDescriptionInput" class="form-label"><?= __('meta_description', 'Pages') ?></label>
                                <textarea id="pbPageMetaDescriptionInput" class="form-textarea" rows="5"><?= e((string) ($page['meta_description'] ?? '')) ?></textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <div id="pbInspectorStorage" class="pb-inspector-storage" hidden aria-hidden="true">
                    <div id="pbInspector" class="pb-inspector"></div>
                </div>
            </div>
        </aside>

        <div class="pb-drawer-overlay" id="pbDrawerOverlay" aria-hidden="true"></div>
    </div>

    <form method="POST" action="<?= url('/admin/pages-builder/' . ($page['id'] ?? '') . '/license' . ($activeLocale !== '' ? ('?locale=' . rawurlencode($activeLocale)) : '')) ?>" class="pb-license-card">
        <?= csrf_field() ?>

        <div class="pb-license-content">
            <h3 class="pb-license-title"><?= __('builder_license_title', 'PagesBuilder') ?></h3>
            <p class="pb-license-hint"><?= __('builder_license_hint', 'PagesBuilder') ?></p>
            <div class="pb-license-status">
                <span class="badge <?= $licenseBadgeClass ?>"><?= e($licenseBadgeLabel) ?></span>
            </div>
        </div>

        <div class="pb-license-actions">
            <div class="form-group pb-license-field">
                <label for="pages_builder_license_key" class="form-label"><?= __('builder_license_key', 'PagesBuilder') ?></label>
                <input
                    type="text"
                    id="pages_builder_license_key"
                    name="pages_builder_license_key"
                    class="form-input"
                    value=""
                    placeholder="<?= __('builder_license_placeholder', 'PagesBuilder') ?>"
                >
                <?php if (!empty($builderLicenseSummary['masked_key'])): ?>
                    <span class="form-hint"><?= __('builder_license_saved_masked', 'PagesBuilder', ['key' => (string) $builderLicenseSummary['masked_key']]) ?></span>
                <?php endif; ?>
            </div>
            <div class="pb-license-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i>
                    <?= __('builder_license_activate', 'PagesBuilder') ?>
                </button>
                <a class="btn btn-secondary" href="https://flat-cms.fr" target="_blank" rel="noopener">
                    <i class="fas fa-shopping-cart"></i>
                    <?= __('builder_license_buy', 'PagesBuilder') ?>
                </a>
            </div>
        </div>
    </form>

</div>

<div
    id="pagesBuilderMobileLockModal"
    class="modal-overlay is-initially-hidden"
    data-redirect-url="<?= e(url('/admin/pages-builder')) ?>"
    aria-hidden="true"
>
    <div class="modal-container modal-sm" role="dialog" aria-modal="true" aria-labelledby="pagesBuilderMobileLockTitle">
        <div class="modal-header">
            <h3 id="pagesBuilderMobileLockTitle" class="modal-title"><?= __('builder_mobile_block_title', 'PagesBuilder') ?></h3>
            <button type="button" class="modal-close" data-action="pb-mobile-lock-close" aria-label="<?= __('close', 'Core') ?>">&times;</button>
        </div>
        <div class="modal-body">
            <p><?= __('builder_mobile_block_message', 'PagesBuilder') ?></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-action="pb-mobile-lock-close">
                <?= __('builder_mobile_block_button', 'PagesBuilder') ?>
            </button>
        </div>
    </div>
</div>

<div id="pagesBuilderConfig" data-pages-builder-config='<?= $builderConfigJson ?>'></div>

<?php foreach (($widgetPreviewAssets['js'] ?? []) as $src): ?>
    <script src="<?= e((string) $src) ?>" data-pages-builder-widget-asset></script>
<?php endforeach; ?>
<?php if (is_file($pagesBuilderMobileLockJsPath)): ?>
    <script src="<?= module_asset('PagesBuilder', 'js/pages-builder-mobile-lock.js') ?><?= $pagesBuilderMobileLockJsVersion !== '' ? ('?v=' . rawurlencode($pagesBuilderMobileLockJsVersion)) : '' ?>"></script>
<?php endif; ?>
<script src="<?= module_asset('PagesBuilder', 'js/pages-builder.js') ?><?= $pagesBuilderJsVersion !== '' ? ('?v=' . rawurlencode($pagesBuilderJsVersion)) : '' ?>"></script>

<?php
$mediaModalPath = BASE_PATH . '/app/Modules/Media/Views/admin/partials/media-modal.php';
$mediaModalScriptPath = BASE_PATH . '/app/Modules/Media/Assets/js/media-modal.js';
$mediaModalScriptVersion = is_file($mediaModalScriptPath) ? (string) filemtime($mediaModalScriptPath) : '';
?>
<?php if (is_file($mediaModalPath)): ?>
    <?php include $mediaModalPath; ?>
    <?php if (is_file($mediaModalScriptPath)): ?>
        <script src="<?= module_asset('Media', 'js/media-modal.js') ?><?= $mediaModalScriptVersion !== '' ? ('?v=' . $mediaModalScriptVersion) : '' ?>"></script>
    <?php endif; ?>
<?php endif; ?>
