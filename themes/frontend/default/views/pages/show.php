<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: themes/frontend/default/views/pages/show.php
 * Version: 2.0.0-dev
 */

?>

<?php
$pageRenderMode = trim((string) ($page['render_mode'] ?? 'classic'));
$pageRenderModeClass = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($pageRenderMode)) ?: 'classic';
$pageHeaderEnabled = array_key_exists('page_header_enabled', $page ?? [])
    ? (bool) ($page['page_header_enabled'] ?? false)
    : (!array_key_exists('page_header_enabled', $settings ?? [])
        ? true
        : ((int) ($settings['page_header_enabled'] ?? 0) === 1));
$pageArticleClasses = ['content'];
if ($pageRenderMode === 'classic') {
    $pageArticleClasses[] = 'prose';
} else {
    $pageArticleClasses[] = 'content-layout';
}
$pageNotices = is_array($pageNotices ?? null) ? $pageNotices : [];
?>


<?php if ($pageHeaderEnabled): ?>
<!-- Page Header -->
<header class="page-header">
    <div class="container">
        <h1><?= e($page['title']) ?></h1>
    </div>
</header>
<?php endif; ?>

<!-- Page Content -->
<div class="content-wrapper content-wrapper-<?= e($pageRenderModeClass) ?>">
    <div class="container">
        <?php foreach ($pageNotices as $notice): ?>
            <?php
            $noticeType = trim((string) ($notice['type'] ?? 'warning'));
            $noticeTitle = trim((string) ($notice['title'] ?? ''));
            $noticeMessage = trim((string) ($notice['message'] ?? ''));
            ?>
            <?php if ($noticeMessage !== ''): ?>
                <div class="alert alert-<?= e($noticeType) ?> page-runtime-notice" role="status">
                    <?php if ($noticeTitle !== ''): ?>
                        <div class="page-runtime-notice-title"><strong><?= e($noticeTitle) ?></strong></div>
                    <?php endif; ?>
                    <div class="page-runtime-notice-message"><?= e($noticeMessage) ?></div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!empty($draftPreviewMode) && !empty($draftPreviewBannerText)): ?>
            <div class="alert alert-warning draft-preview-banner" role="status">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <span><?= e((string) $draftPreviewBannerText) ?></span>
            </div>
        <?php endif; ?>
        <article class="<?= e(implode(' ', $pageArticleClasses)) ?>">
            <?php if (!$pageHeaderEnabled): ?>
                <h1 class="sr-only"><?= e($page['title']) ?></h1>
            <?php endif; ?>
            <?= $page['content'] ?>
        </article>
    </div>
</div>
