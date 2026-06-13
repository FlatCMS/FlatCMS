<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>
<!-- Hero -->
<section class="hero">
    <div class="container">
        <h1 class="hero-title"><?= e($page['title'] ?? __('welcome', 'Core')) ?></h1>
        <p class="hero-description"><?= e($settings['site_description'] ?? __('welcome_description', 'Dashboard')) ?></p>
        <a href="<?= url('/' . $locale . '/blog') ?>" class="btn btn-primary"><?= __('read_blog', 'Posts') ?> →</a>
    </div>
</section>

<?php if (!empty($page['content'])): ?>
<section class="section">
    <div class="container">
        <article class="content prose"><?= $page['content'] ?></article>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($recentPosts)): ?>
<section class="section">
    <div class="container">
        <h2 class="section-title"><?= __('recent_posts', 'Posts') ?></h2>
        <div class="posts-grid">
<?php foreach ($recentPosts as $post): ?>
            <article class="card">
                <div class="card-image">
<?php if (!empty($post['featured_image'])): ?>
                    <img src="<?= site_media_url($post['featured_image']) ?>" alt="<?= e($post['title']) ?>">
<?php else: ?>
                    <div class="card-image-placeholder">
                        <?= strtoupper(substr($post['title'], 0, 1)) ?>
                    </div>
<?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="card-meta"><?= human_date($post['created_at'], 'd M Y') ?></p>
                    <h3 class="card-title">
                        <a href="<?= url('/' . $locale . '/blog/' . $post['slug']) ?>"><?= e($post['title']) ?></a>
                    </h3>
<?php if (!empty($post['excerpt'])): ?>
                    <p class="card-excerpt"><?= e(str_limit($post['excerpt'], 100)) ?></p>
<?php endif; ?>
                    <a href="<?= url('/' . $locale . '/blog/' . $post['slug']) ?>" class="btn btn-sm btn-ghost post-read-more-btn"><?= __('read_more', 'Posts') ?></a>
                </div>
            </article>
<?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
