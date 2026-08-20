<?php
/** Native Posts status editor. Replaced by plugins through posts.form.status. */
?>
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
