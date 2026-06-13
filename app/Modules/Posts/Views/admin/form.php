<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('Posts', 'css/posts.css') ?>">
<link rel="stylesheet" href="<?= module_asset('Posts', 'css/posts-suneditor.css') ?>?v=<?= filemtime(BASE_PATH . '/app/Modules/Posts/Assets/css/posts-suneditor.css') ?>">

<?php
$categories = $categories ?? [];
$categoriesEnabled = $categoriesEnabled ?? true;
$formData = is_array($formData ?? null) ? $formData : (is_array($post ?? null) ? $post : []);
$translationUi = is_array($translationUi ?? null) ? $translationUi : [];
$formLabels = is_array($formLabels ?? null) ? $formLabels : [];
$translationUi = array_merge([
    'active_is_source' => true,
    'source_status' => 'draft',
], $translationUi);
$selectedCategories = old('categories', $formData['categories'] ?? []);
if (!is_array($selectedCategories)) {
    $selectedCategories = [$selectedCategories];
}
$selectedCategories = array_map('strval', $selectedCategories);
$featuredImageValue = (string) old('featured_image', $formData['featured_image'] ?? '');
$mediaEnabled = (new \App\Core\ModuleManager([
    BASE_PATH . '/app/Modules',
    BASE_PATH . '/app/Extensions',
], BASE_PATH . '/data/modules.json'))->isEnabled('Media');
$postsAiActiveLocaleLabel = (string) ($translationUi['active_locale_label'] ?? '');
$postsAiSourceLocaleLabel = (string) ($translationUi['source_locale_label'] ?? '');

$postLabel = static function (string $key, string $fallback = '') use ($formLabels): string {
    $value = $formLabels[$key] ?? null;
    return is_string($value) && trim($value) !== '' ? $value : $fallback;
};

$postsLocaleFlag = static function (string $locale): string {
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

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
    </div>
    <div class="page-header-actions">
        <a href="<?= url('/admin/posts') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <?= e($postLabel('back', __('back', 'Core'))) ?>
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
                    <i class="fas fa-newspaper"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('posts_help_badge', 'Posts') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('posts_tour_form_editor_title', 'Posts') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('posts_tour_form_sidebar_content', 'Posts') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('posts_tour_form_translations_content', 'Posts') ?></li>
            <li><?= __('posts_tour_form_fields_content', 'Posts') ?></li>
            <li><?= __('posts_tour_form_media_content', 'Posts') ?></li>
            <li><?= __('posts_tour_form_taxonomies_content', 'Posts') ?></li>
            <li><?= __('posts_tour_form_seo_content', 'Posts') ?></li>
            <li><?= __($post ? 'posts_tour_form_edit_next_content' : 'posts_tour_form_create_next_content', 'Posts') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/posts') ?>" class="btn btn-primary"><?= __('posts_list', 'Posts') ?></a>
        </div>
    </div>
</div>
<form
    method="POST"
    action="<?= $post ? url('/admin/posts/' . $post['id']) : url('/admin/posts') ?>"
    data-ai-agent-form="posts"
    data-tour-state="<?= $post ? 'edit' : 'create' ?>"
>
    <?= csrf_field() ?>
    <input type="hidden" name="locale" value="<?= e((string) ($translationUi['active_locale'] ?? '')) ?>">
    <input type="hidden" name="translation_group" value="<?= e((string) ($translationUi['translation_group'] ?? '')) ?>">
    <input type="hidden" name="source_locale" value="<?= e((string) ($translationUi['source_locale'] ?? '')) ?>">
    <input type="hidden" name="source_id" value="<?= e((string) ($translationUi['source_post_id'] ?? '')) ?>">
    <div class="posts-translation-bar" data-tour-target="posts-translation-tabs">
        <div class="posts-translation-tabs" role="tablist" aria-label="<?= e($postLabel('translations', __('translations', 'Posts'))) ?>">
            <?php foreach (($translationUi['tabs'] ?? []) as $tab): ?>
                <?php
                $tabClasses = ['posts-translation-tab'];
                if (!empty($tab['is_active'])) {
                    $tabClasses[] = 'is-active';
                }
                if (empty($tab['exists'])) {
                    $tabClasses[] = 'is-missing';
                }
                if (!empty($tab['is_source'])) {
                    $tabClasses[] = 'is-source';
                }
                if (empty($tab['url'])) {
                    $tabClasses[] = 'is-disabled';
                }
                $tabBadge = !empty($tab['is_source'])
                    ? $postLabel('translation_source', __('translation_source', 'Posts'))
                    : (!empty($tab['exists'])
                        ? $postLabel('translation_ready', __('translation_ready', 'Posts'))
                        : $postLabel('translation_missing', __('translation_missing', 'Posts')));
                $tabTitle = trim((string) ($tab['label'] ?? ''));
                ?>
                <?php if (!empty($tab['url'])): ?>
                    <a
                        href="<?= e((string) $tab['url']) ?>"
                        class="<?= e(implode(' ', $tabClasses)) ?>"
                        role="tab"
                        aria-selected="<?= !empty($tab['is_active']) ? 'true' : 'false' ?>"
                        title="<?= e($tabTitle) ?>"
                    >
                        <span class="posts-translation-tab-icon" aria-hidden="true">
                            <span class="posts-translation-flag"><?= $postsLocaleFlag((string) ($tab['code'] ?? '')) ?></span>
                        </span>
                        <span class="posts-translation-tab-badge"><?= e($tabBadge) ?></span>
                    </a>
                <?php else: ?>
                    <span
                        class="<?= e(implode(' ', $tabClasses)) ?>"
                        role="tab"
                        aria-selected="false"
                        aria-disabled="true"
                        title="<?= e($tabTitle) ?>"
                    >
                        <span class="posts-translation-tab-icon" aria-hidden="true">
                            <span class="posts-translation-flag"><?= $postsLocaleFlag((string) ($tab['code'] ?? '')) ?></span>
                        </span>
                        <span class="posts-translation-tab-badge"><?= e($tabBadge) ?></span>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php if (empty($translationUi['can_create_additional'])): ?>
            <div class="posts-translation-hint"><?= e($postLabel('translation_save_first', __('translation_save_first', 'Posts'))) ?></div>
        <?php endif; ?>
    </div>
    <div class="form-layout-sidebar">
        <div data-tour-target="posts-primary-editor">
            <div class="card" data-tour-target="posts-form-fields">
                <div
                    class="form-group"
                    data-ai-agent-target
                    data-ai-agent-module="posts"
                    data-ai-agent-entity="post"
                    data-ai-agent-block="content"
                    data-ai-agent-block-label="<?= e(__('content', 'Posts')) ?>"
                    data-ai-agent-field="title"
                    data-ai-agent-field-kind="text"
                    data-ai-agent-label="<?= e($postLabel('title', __('title', 'Posts'))) ?>"
                >
                    <label for="title" class="form-label"><?= e($postLabel('title', __('title', 'Posts'))) ?> *</label>
                    <input type="text" id="title" name="title" class="form-input" value="<?= e(old('title', $formData['title'] ?? '')) ?>" required>
                </div>
                <div
                    class="form-group"
                    data-ai-agent-target
                    data-ai-agent-module="posts"
                    data-ai-agent-entity="post"
                    data-ai-agent-block="content"
                    data-ai-agent-block-label="<?= e(__('content', 'Posts')) ?>"
                    data-ai-agent-field="slug"
                    data-ai-agent-field-kind="text"
                    data-ai-agent-label="<?= e($postLabel('slug', __('slug', 'Posts'))) ?>"
                >
                    <label for="slug" class="form-label"><?= e($postLabel('slug', __('slug', 'Posts'))) ?></label>
                    <input type="text" id="slug" name="slug" class="form-input" value="<?= e(old('slug', $formData['slug'] ?? '')) ?>">
                </div>
                <div
                    class="form-group"
                    data-ai-agent-target
                    data-ai-agent-module="posts"
                    data-ai-agent-entity="post"
                    data-ai-agent-block="content"
                    data-ai-agent-block-label="<?= e(__('content', 'Posts')) ?>"
                    data-ai-agent-field="excerpt"
                    data-ai-agent-field-kind="textarea"
                    data-ai-agent-label="<?= e($postLabel('excerpt', __('excerpt', 'Posts'))) ?>"
                >
                    <label for="excerpt" class="form-label"><?= e($postLabel('excerpt', __('excerpt', 'Posts'))) ?></label>
                    <textarea id="excerpt" name="excerpt" class="form-input" rows="3" data-no-editor><?= e(old('excerpt', $formData['excerpt'] ?? '')) ?></textarea>
                </div>
                <div
                    class="form-group"
                    data-ai-agent-target
                    data-ai-agent-module="posts"
                    data-ai-agent-entity="post"
                    data-ai-agent-block="content"
                    data-ai-agent-block-label="<?= e(__('content', 'Posts')) ?>"
                    data-ai-agent-field="content"
                    data-ai-agent-field-kind="richtext"
                    data-ai-agent-label="<?= e($postLabel('content', __('content', 'Posts'))) ?>"
                >
                    <label for="content" class="form-label"><?= e($postLabel('content', __('content', 'Posts'))) ?></label>
                    <textarea
                        id="content"
                        name="content"
                        class="form-input"
                        rows="15"
                        data-post-suneditor
                        data-suneditor-media-modal-error="<?= e($postLabel('featured_image_modal_unavailable', __('featured_image_modal_unavailable', 'Posts'))) ?>"
                        data-suneditor-toolbar-expand="<?= e($postLabel('suneditor_toolbar_expand', __('suneditor_toolbar_expand', 'Posts'))) ?>"
                        data-suneditor-toolbar-collapse="<?= e($postLabel('suneditor_toolbar_collapse', __('suneditor_toolbar_collapse', 'Posts'))) ?>"
                    ><?= e(old('content', $formData['content'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>
        <div data-tour-target="posts-form-sidebar">
            <div class="card" data-tour-target="posts-form-status">
                <h3 class="card-title card-title-spaced"><?= e($postLabel('status', __('status', 'Posts'))) ?></h3>
                <div class="form-group">
                    <?php if (!empty($translationUi['active_is_source'])): ?>
                        <select id="status" name="status" class="form-select">
                            <option value="draft" <?= selected('draft', old('status', $formData['status'] ?? 'draft')) ?>><?= e($postLabel('status_draft', __('status_draft', 'Posts'))) ?></option>
                            <option value="published" <?= selected('published', old('status', $formData['status'] ?? '')) ?>><?= e($postLabel('status_published', __('status_published', 'Posts'))) ?></option>
                        </select>
                    <?php else: ?>
                        <?php $sourceStatus = (string) old('status', $translationUi['source_status'] ?? 'draft'); ?>
                        <input type="hidden" name="status" value="<?= e($sourceStatus) ?>">
                        <div class="posts-status-lock">
                            <span class="badge <?= $sourceStatus === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                <?= e($postLabel('status_' . $sourceStatus, __('status_' . $sourceStatus, 'Posts'))) ?>
                            </span>
                            <div class="form-hint"><?= e($postLabel('translation_status_follow_source', __('translation_status_follow_source', 'Posts'))) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-block" data-tour-target="posts-form-save"><?= e($postLabel('save', __('save', 'Core'))) ?></button>
            </div>
            <div class="card" data-tour-target="posts-form-media">
                <h3 class="card-title card-title-spaced"><?= e($postLabel('featured_image', __('featured_image', 'Posts'))) ?></h3>
                <div
                    class="form-group posts-featured-image-field"
                    data-post-featured-media
                    data-modal-error="<?= e($postLabel('featured_image_modal_unavailable', __('featured_image_modal_unavailable', 'Posts'))) ?>"
                    data-ai-agent-target
                    data-ai-agent-module="posts"
                    data-ai-agent-entity="post"
                    data-ai-agent-block="content"
                    data-ai-agent-block-label="<?= e(__('content', 'Posts')) ?>"
                    data-ai-agent-field="featured_image"
                    data-ai-agent-field-kind="text"
                    data-ai-agent-label="<?= e($postLabel('featured_image', __('featured_image', 'Posts'))) ?>"
                >
                    <input
                        type="text"
                        id="featured_image"
                        name="featured_image"
                        class="form-input"
                        value="<?= e($featuredImageValue) ?>"
                        placeholder="<?= e($postLabel('featured_image_placeholder', __('featured_image_placeholder', 'Posts'))) ?>"
                        data-post-featured-input
                    >
                    <?php if ($mediaEnabled): ?>
                        <div class="posts-featured-image-actions">
                            <button type="button" class="btn btn-secondary btn-sm" data-post-featured-open>
                                <i class="fas fa-photo-film"></i>
                                <?= e($postLabel('featured_image_open', __('featured_image_open', 'Posts'))) ?>
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" data-post-featured-clear>
                                <i class="fas fa-eraser"></i>
                                <?= e($postLabel('featured_image_clear', __('featured_image_clear', 'Posts'))) ?>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="posts-featured-image-preview" data-post-featured-preview hidden>
                        <img src="" alt="<?= e($postLabel('featured_image', __('featured_image', 'Posts'))) ?>" data-post-featured-preview-img hidden>
                    </div>
                    <div class="form-hint"><?= e($postLabel('featured_image_hint', __('featured_image_hint', 'Posts'))) ?></div>
                </div>
            </div>
            <?php if ($categoriesEnabled): ?>
            <div class="card" data-tour-target="posts-form-taxonomies">
                <h3 class="card-title card-title-spaced"><?= e($postLabel('categories', __('categories', 'Posts'))) ?></h3>
                <?php if (empty($categories)): ?>
                    <p class="posts-category-empty"><?= e($postLabel('no_categories', __('no_categories', 'Posts'))) ?></p>
                <?php else: ?>
                    <div class="posts-category-list">
                        <?php foreach ($categories as $cat): ?>
                            <?php $catId = (string) ($cat['id'] ?? ''); ?>
                            <label class="form-check">
                                <input type="checkbox" name="categories[]" value="<?= e($catId) ?>" class="form-checkbox"
                                    <?= in_array($catId, $selectedCategories, true) ? 'checked' : '' ?>>
                                <span><?= e($cat['name'] ?? '') ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="card" data-tour-target="posts-form-seo">
                <h3 class="card-title card-title-spaced"><?= e($postLabel('seo_section', __('seo_section', 'Posts'))) ?></h3>
                <div
                    class="form-group"
                    data-ai-agent-target
                    data-ai-agent-module="posts"
                    data-ai-agent-entity="post"
                    data-ai-agent-block="seo"
                    data-ai-agent-block-label="<?= e($postLabel('seo_section', __('seo_section', 'Posts'))) ?>"
                    data-ai-agent-field="meta_title"
                    data-ai-agent-field-kind="text"
                    data-ai-agent-label="<?= e($postLabel('meta_title', __('meta_title', 'Posts'))) ?>"
                >
                    <label for="meta_title" class="form-label"><?= e($postLabel('meta_title', __('meta_title', 'Posts'))) ?></label>
                    <input type="text" id="meta_title" name="meta_title" class="form-input" value="<?= e(old('meta_title', $formData['meta_title'] ?? '')) ?>">
                </div>
                <div
                    class="form-group"
                    data-ai-agent-target
                    data-ai-agent-module="posts"
                    data-ai-agent-entity="post"
                    data-ai-agent-block="seo"
                    data-ai-agent-block-label="<?= e($postLabel('seo_section', __('seo_section', 'Posts'))) ?>"
                    data-ai-agent-field="meta_description"
                    data-ai-agent-field-kind="textarea"
                    data-ai-agent-label="<?= e($postLabel('meta_description', __('meta_description', 'Posts'))) ?>"
                >
                    <label for="meta_description" class="form-label"><?= e($postLabel('meta_description', __('meta_description', 'Posts'))) ?></label>
                    <textarea id="meta_description" name="meta_description" class="form-input" rows="3" data-no-editor><?= e(old('meta_description', $formData['meta_description'] ?? '')) ?></textarea>
                </div>
            </div>
        </div>
    </div>
</form>

<script src="<?= module_asset('Posts', 'js/posts-suneditor.js') ?>?v=<?= filemtime(BASE_PATH . '/app/Modules/Posts/Assets/js/posts-suneditor.js') ?>"></script>
<script src="<?= module_asset('Posts', 'js/posts.js') ?>"></script>

<?php if ($mediaEnabled): ?>
    <?php include BASE_PATH . '/app/Modules/Media/Views/admin/partials/media-modal.php'; ?>
    <script src="<?= module_asset('Media', 'js/media-modal.js') ?>"></script>
<?php endif; ?>
