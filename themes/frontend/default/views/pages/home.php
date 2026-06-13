<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1><?= e($page['title'] ?? __('welcome', 'Core')) ?></h1>
        <p><?= e($settings['site_description'] ?? __('welcome_description', 'Dashboard')) ?></p>
        <a href="<?= url('/' . $locale . '/blog') ?>" class="btn"><?= __('read_blog', 'Posts') ?></a>
    </div>
</section>

<!-- About Section -->
<?php if (!empty($page['content'])): ?>
<section class="section">
    <div class="container">
        <article class="content prose">
            <?= $page['content'] ?>
        </article>
    </div>
</section>
<?php endif; ?>

<!-- Recent Posts -->
<?php if (!empty($recentPosts)): ?>
<section class="section section-alt">
    <div class="container">
        <h2 class="section-title"><?= __('recent_posts', 'Posts') ?></h2>
        
        <div class="posts-grid">
            <?php foreach ($recentPosts as $post): ?>
                <article class="card">
                    <?php if (!empty($post['featured_image'])): ?>
                        <div class="card-image">
                            <img src="<?= site_media_url($post['featured_image']) ?>" alt="<?= e($post['title']) ?>">
                        </div>
                    <?php else: ?>
                        <div class="card-image">
                            <div class="card-image-placeholder">
                                <?= strtoupper(substr($post['title'], 0, 1)) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <p class="card-meta"><?= human_date($post['created_at'], 'd M Y') ?></p>
                        <h3 class="card-title">
                            <a href="<?= url('/' . $locale . '/blog/' . $post['slug']) ?>"><?= e($post['title']) ?></a>
                        </h3>
                        <?php if (!empty($post['excerpt'])): ?>
                            <p class="card-excerpt"><?= e(str_limit($post['excerpt'], 120)) ?></p>
                        <?php endif; ?>
                        <a href="<?= url('/' . $locale . '/blog/' . $post['slug']) ?>" class="btn btn-sm btn-secondary">
                            <?= __('read_more', 'Posts') ?> →
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
