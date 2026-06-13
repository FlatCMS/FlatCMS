<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>
<?php
$pageHeaderEnabled = !array_key_exists('page_header_enabled', $settings ?? [])
    ? true
    : ((int) ($settings['page_header_enabled'] ?? 0) === 1);
$pagination = is_array($pagination ?? null) ? $pagination : [];
$paginationItems = is_array($pagination['items'] ?? null) ? $pagination['items'] : [];
$paginationPrevious = is_array($pagination['previous'] ?? null) ? $pagination['previous'] : null;
$paginationNext = is_array($pagination['next'] ?? null) ? $pagination['next'] : null;
$paginationTotal = (int) ($pagination['total'] ?? 0);
?>
<?php if ($pageHeaderEnabled): ?>
<header class="page-header">
    <div class="container">
        <p class="meta"><?= human_date($post['created_at'], 'd M Y') ?></p>
        <h1><?= e($post['title']) ?></h1>
        <?php if (!empty($postCategories) && ($categoriesEnabled ?? true)): ?>
            <div class="post-categories">
                <?php foreach ($postCategories as $cat): ?>
                    <a class="post-category" href="<?= url('/' . $locale . '/blog/categorie/' . $cat['slug']) ?>">
                        <?= e($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>
<div class="content-wrapper">
    <div class="container">
        <article class="content prose">
            <?php if (!$pageHeaderEnabled): ?>
                <h1 class="sr-only"><?= e($post['title']) ?></h1>
            <?php endif; ?>
            <?php if (!empty($post['featured_image'])): ?>
                <img src="<?= site_media_url($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" class="post-featured-image">
            <?php endif; ?>
            <?= $post['content'] ?>
        </article>
        <?php if ($paginationTotal > 1): ?>
        <nav class="post-pagination-nav" aria-label="<?= e(__('posts_pagination_aria', 'Posts')) ?>">
            <ul class="pagination">
                <li class="page-item<?= $paginationPrevious === null ? ' disabled' : '' ?>">
                    <?php if ($paginationPrevious === null): ?>
                        <span class="page-link" aria-hidden="true">&laquo;</span>
                    <?php else: ?>
                        <a class="page-link" href="<?= e((string) ($paginationPrevious['href'] ?? '')) ?>" aria-label="<?= e(__('posts_pagination_previous_aria', 'Posts')) ?>">&laquo;</a>
                    <?php endif; ?>
                </li>

                <?php foreach ($paginationItems as $item): ?>
                    <?php if ((string) ($item['type'] ?? '') === 'ellipsis'): ?>
                        <li class="page-item disabled">
                            <span class="page-link">…</span>
                        </li>
                        <?php continue; ?>
                    <?php endif; ?>

                    <?php
                    $isActivePage = (bool) ($item['active'] ?? false);
                    $pageNumber = (int) ($item['number'] ?? 0);
                    $pageHref = (string) ($item['href'] ?? '');
                    ?>
                    <li class="page-item<?= $isActivePage ? ' active' : '' ?>">
                        <?php if ($isActivePage): ?>
                            <span class="page-link" aria-current="page"><?= $pageNumber ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= e($pageHref) ?>" aria-label="<?= e(sprintf('%s %d', __('posts_pagination_page', 'Posts'), $pageNumber)) ?>"><?= $pageNumber ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>

                <li class="page-item<?= $paginationNext === null ? ' disabled' : '' ?>">
                    <?php if ($paginationNext === null): ?>
                        <span class="page-link" aria-hidden="true">&raquo;</span>
                    <?php else: ?>
                        <a class="page-link" href="<?= e((string) ($paginationNext['href'] ?? '')) ?>" aria-label="<?= e(__('posts_pagination_next_aria', 'Posts')) ?>">&raquo;</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        <div class="post-back-link-wrap"><a href="<?= url('/' . $locale . '/blog') ?>" class="btn btn-ghost">← <?= __('back_to_blog', 'Posts') ?></a></div>
        <?php if (($commentsEnabled ?? true)): ?>
        <section class="comments-section">
            <h2 class="comments-title"><?= __('comments_on_post', 'Comments') ?> (<?= count($comments ?? []) ?>)</h2>
            <?php if (!empty($comments)): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-header"><span class="comment-author"><?= e($comment['author_name']) ?></span><span class="comment-date"> | <?= human_date($comment['created_at'], 'd F Y H:i') ?></span></div>
                        <div class="comment-content"><?= nl2br(e($comment['content'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="post-comments-empty"><?= __('no_comments', 'Comments') ?></p>
            <?php endif; ?>
            <div class="comment-form">
                <h3><?= __('leave_comment', 'Comments') ?></h3>
                <form method="POST" action="<?= url('/comments') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="post_id" value="<?= e($post['id']) ?>">
                    <input type="hidden" name="post_type" value="post">
                    <div class="form-row">
                        <div class="form-group"><label class="form-label"><?= __('your_name', 'Comments') ?> *</label><input type="text" name="author_name" class="form-input" autocomplete="name" required></div>
                        <div class="form-group"><label class="form-label"><?= __('your_email', 'Comments') ?> *</label><input type="email" name="author_email" class="form-input" autocomplete="email" required></div>
                    </div>
                    <div class="form-group"><label class="form-label"><?= __('your_comment', 'Comments') ?> *</label><textarea name="content" class="form-input" rows="5" autocomplete="on" required></textarea></div>
                    <button type="submit" class="btn btn-primary"><?= __('submit_comment', 'Comments') ?></button>
                </form>
            </div>
        </section>
        <?php endif; ?>
        <?php if (!empty($relatedPosts)): ?>
        <section class="posts-related-section">
            <div class="posts-related-header">
                <h2><?= __('continue_reading', 'Posts') ?></h2>
                <p><?= __('continue_reading_subtitle', 'Posts') ?></p>
            </div>
            <div class="posts-grid">
                <?php foreach ($relatedPosts as $related): ?>
                    <?php $relatedUrl = url('/' . $locale . '/blog/' . ($related['slug'] ?? '')); ?>
                    <article class="card">
                        <div class="card-image">
                            <?php if (!empty($related['featured_image'])): ?>
                                <a href="<?= $relatedUrl ?>"><img src="<?= site_media_url((string) $related['featured_image']) ?>" alt="<?= e((string) ($related['title'] ?? '')) ?>"></a>
                            <?php else: ?>
                                <a href="<?= $relatedUrl ?>" class="card-image-placeholder-link"><?= e(strtoupper(substr((string) ($related['title'] ?? '?'), 0, 1))) ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <p class="card-meta"><?= human_date((string) ($related['created_at'] ?? ''), 'd M Y') ?></p>
                            <h3 class="card-title"><a href="<?= $relatedUrl ?>"><?= e((string) ($related['title'] ?? '')) ?></a></h3>
                            <?php if (!empty($related['excerpt'])): ?><p class="card-excerpt"><?= e(str_limit((string) $related['excerpt'], 120)) ?></p><?php endif; ?>
                            <a href="<?= $relatedUrl ?>" class="btn btn-sm btn-ghost post-read-more-btn"><?= __('read_more', 'Posts') ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>
