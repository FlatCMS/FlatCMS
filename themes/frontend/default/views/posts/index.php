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
$filterSlug = $currentCategorySlug ?? '';
$blogBase = '/' . $locale . '/blog';
$categoryBase = '/' . $locale . '/blog/categorie/';
?>
<?php if ($pageHeaderEnabled): ?>
<!-- Page Header -->
<header class="page-header">
    <div class="container">
        <h1><?= __('blog', 'Posts') ?></h1>
        <p class="meta"><?= __('blog_subtitle', 'Posts') ?></p>
        <?php if (!empty($categories) && ($categoriesEnabled ?? true)): ?>
            <div class="blog-filters">
                <span class="blog-filters-label"><?= __('filter_by_category', 'Posts') ?></span>
                <a href="<?= url($blogBase) ?>" class="blog-filter-link <?= $filterSlug === '' ? 'active' : '' ?>"><?= __('all_categories', 'Posts') ?></a>
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= url($categoryBase . $cat['slug']) ?>" class="blog-filter-link <?= $filterSlug === ($cat['slug'] ?? '') ? 'active' : '' ?>">
                        <?= e($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>

<!-- Posts List -->
<section class="section">
    <div class="container">
        <?php if (!$pageHeaderEnabled): ?>
            <h1 class="sr-only"><?= __('blog', 'Posts') ?></h1>
            <?php if (!empty($categories) && ($categoriesEnabled ?? true)): ?>
                <div class="blog-filters">
                    <span class="blog-filters-label"><?= __('filter_by_category', 'Posts') ?></span>
                    <a href="<?= url($blogBase) ?>" class="blog-filter-link <?= $filterSlug === '' ? 'active' : '' ?>"><?= __('all_categories', 'Posts') ?></a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="<?= url($categoryBase . $cat['slug']) ?>" class="blog-filter-link <?= $filterSlug === ($cat['slug'] ?? '') ? 'active' : '' ?>">
                            <?= e($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (empty($posts['data'])): ?>
            <div class="posts-empty-state">
                <p class="posts-empty-text"><?= __('no_posts', 'Posts') ?></p>
            </div>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($posts['data'] as $post): ?>
                    <article class="card">
                        <?php if (!empty($post['featured_image'])): ?>
                            <div class="card-image">
                                <a href="<?= url('/' . $locale . '/blog/' . $post['slug']) ?>">
                                    <img src="<?= site_media_url($post['featured_image']) ?>" alt="<?= e($post['title']) ?>">
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="card-image">
                                <a href="<?= url('/' . $locale . '/blog/' . $post['slug']) ?>" class="card-image-placeholder-link">
                                    <?= strtoupper(substr($post['title'], 0, 1)) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <p class="card-meta"><?= human_date($post['created_at'], 'd M Y') ?></p>
                            <h2 class="card-title">
                                <a href="<?= url('/' . $locale . '/blog/' . $post['slug']) ?>"><?= e($post['title']) ?></a>
                            </h2>
                            <?php
                            $postCategories = $post['categories'] ?? [];
                            if (!is_array($postCategories)) {
                                $postCategories = [$postCategories];
                            }
                            $resolvedCategories = [];
                            foreach ($postCategories as $catId) {
                                $cat = $categoriesById[(string) $catId] ?? null;
                                if ($cat) {
                                    $resolvedCategories[] = $cat;
                                }
                            }
                            ?>
                            <?php if (!empty($resolvedCategories)): ?>
                                <div class="post-categories">
                                    <?php foreach ($resolvedCategories as $cat): ?>
                                        <a class="post-category" href="<?= url('/' . $locale . '/blog/categorie/' . $cat['slug']) ?>">
                                            <?= e($cat['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($post['excerpt'])): ?>
                                <p class="card-excerpt"><?= e(str_limit($post['excerpt'], 150)) ?></p>
                            <?php endif; ?>
                            <a href="<?= url('/' . $locale . '/blog/' . $post['slug']) ?>" class="btn btn-sm btn-secondary">
                                <?= __('read_more', 'Posts') ?> →
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($posts['total_pages'] > 1): ?>
                <?php
                $filterSlug = $currentCategorySlug ?? '';
                $pageBase = $filterSlug !== '' ? url('/' . $locale . '/blog/categorie/' . $filterSlug) : url('/' . $locale . '/blog');
                $currentPage = max(1, (int) ($posts['current_page'] ?? 1));
                $totalPages = max(1, (int) ($posts['total_pages'] ?? 1));
                $windowSize = 2;
                $windowStart = max(1, $currentPage - $windowSize);
                $windowEnd = min($totalPages, $currentPage + $windowSize);
                $querySeparator = str_contains($pageBase, '?') ? '&' : '?';
                $pageUrl = static function (int $targetPage) use ($pageBase, $querySeparator): string {
                    return $targetPage <= 1 ? $pageBase : ($pageBase . $querySeparator . 'page=' . $targetPage);
                };
                ?>
                <nav class="blog-pagination-nav" aria-label="<?= e(__('posts_list', 'Posts')) ?>">
                    <ul class="pagination">
                        <li class="page-item <?= $currentPage > 1 ? '' : 'disabled' ?>">
                            <?php if ($currentPage > 1): ?>
                                <a class="page-link" href="<?= $pageUrl($currentPage - 1) ?>" rel="prev">
                                    <span aria-hidden="true">&larr;</span>
                                </a>
                            <?php else: ?>
                                <span class="page-link" aria-hidden="true">&larr;</span>
                            <?php endif; ?>
                        </li>

                        <?php if ($windowStart > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= $pageUrl(1) ?>">1</a></li>
                            <?php if ($windowStart > 2): ?>
                                <li class="page-item disabled"><span class="page-link page-link-ellipsis" aria-hidden="true">…</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
                            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                <?php if ($i === $currentPage): ?>
                                    <span class="page-link" aria-current="page"><?= $i ?></span>
                                <?php else: ?>
                                    <a class="page-link" href="<?= $pageUrl($i) ?>"><?= $i ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>

                        <?php if ($windowEnd < $totalPages): ?>
                            <?php if ($windowEnd < ($totalPages - 1)): ?>
                                <li class="page-item disabled"><span class="page-link page-link-ellipsis" aria-hidden="true">…</span></li>
                            <?php endif; ?>
                            <li class="page-item"><a class="page-link" href="<?= $pageUrl($totalPages) ?>"><?= $totalPages ?></a></li>
                        <?php endif; ?>

                        <li class="page-item <?= $currentPage < $totalPages ? '' : 'disabled' ?>">
                            <?php if ($currentPage < $totalPages): ?>
                                <a class="page-link" href="<?= $pageUrl($currentPage + 1) ?>" rel="next">
                                    <span aria-hidden="true">&rarr;</span>
                                </a>
                            <?php else: ?>
                                <span class="page-link" aria-hidden="true">&rarr;</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
