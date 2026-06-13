<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Comments Module CSS -->
<link rel="stylesheet" href="<?= module_asset('Comments', 'css/comments.css') ?>">

<?php $status = $status ?? 'all'; ?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
    </div>
    <div class="page-header-actions" data-tour-target="comments-toolbar">
        <form method="GET" action="<?= url('/admin/comments') ?>" class="comments-filter-form">
            <label for="statusFilter" class="form-label"><?= __('status', 'Comments') ?></label>
            <div class="comments-filter-controls">
                <select id="statusFilter" name="status" class="form-select">
                    <option value="all" <?= selected('all', $status) ?>>
                        <?= __('all_comments', 'Comments') ?> (<?= $counts['all'] ?>)
                    </option>
                    <option value="pending" <?= selected('pending', $status) ?>>
                        <?= __('pending', 'Comments') ?> (<?= $counts['pending'] ?>)
                    </option>
                    <option value="approved" <?= selected('approved', $status) ?>>
                        <?= __('approved', 'Comments') ?> (<?= $counts['approved'] ?>)
                    </option>
                    <option value="rejected" <?= selected('rejected', $status) ?>>
                        <?= __('rejected', 'Comments') ?> (<?= $counts['rejected'] ?>)
                    </option>
                </select>
                <button type="submit" class="btn btn-sm btn-secondary"><?= __('filter', 'Core') ?></button>
            </div>
        </form>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-comments"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('comments_help_badge', 'Comments') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('comments_help_title', 'Comments') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('comments_help_intro', 'Comments') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('comments_help_step_filter', 'Comments') ?></li>
            <li><?= __('comments_help_step_read', 'Comments') ?></li>
            <li><?= __('comments_help_step_moderate', 'Comments') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="#commentsTableCard" class="btn btn-primary"><?= __('comments_help_action_table', 'Comments') ?></a>
            <a href="<?= url('/admin/posts') ?>" class="btn btn-secondary"><?= __('comments_help_action_posts', 'Comments') ?></a>
        </div>
    </div>
</div>

<div class="card" id="commentsTableCard">
    <?php if (empty($comments['data'])): ?>
        <div class="empty-state" data-tour-target="comments-empty">
            <div class="admin-empty-state-panel">
                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                    <i class="fas fa-comment-slash"></i>
                </div>
                <h2 class="admin-empty-state-panel__title"><?= __('comments_empty_title', 'Comments') ?></h2>
                <p class="admin-empty-state-panel__text"><?= __('comments_empty_text', 'Comments') ?></p>
                <div class="admin-empty-state-panel__actions">
                    <a href="<?= url('/admin/comments') ?>" class="btn btn-primary btn-sm"><?= __('comments_empty_action_reset', 'Comments') ?></a>
                    <a href="<?= url('/admin/posts') ?>" class="btn btn-secondary btn-sm"><?= __('comments_empty_action_posts', 'Comments') ?></a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrapper" data-tour-target="comments-table" data-tour-state="<?= empty($comments['data']) ? 'empty' : 'ready' ?>">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= __('author', 'Comments') ?></th>
                        <th><?= __('content', 'Comments') ?></th>
                        <th><?= __('status', 'Comments') ?></th>
                        <th><?= __('date', 'Comments') ?></th>
                        <th><?= __('actions', 'Core') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments['data'] as $comment): ?>
                        <tr>
                            <td data-label="<?= __('author', 'Comments') ?>">
                                <strong><?= e($comment['author_name']) ?></strong><br>
                                <small class="text-muted"><?= e($comment['author_email']) ?></small>
                            </td>
                            <td data-label="<?= __('content', 'Comments') ?>">
                                <button
                                    type="button"
                                    class="comment-content-link"
                                    data-comment-open
                                    data-comment-content="<?= e((string) ($comment['content'] ?? '')) ?>"
                                    data-comment-author="<?= e((string) ($comment['author_name'] ?? '')) ?>"
                                    data-comment-email="<?= e((string) ($comment['author_email'] ?? '')) ?>"
                                    data-comment-date="<?= e((string) human_date($comment['created_at'] ?? '')) ?>"
                                    data-comment-post-type="<?= e((string) ($comment['post_type'] ?? 'post')) ?>"
                                    data-comment-post-id="<?= e((string) ($comment['post_id'] ?? '')) ?>"
                                    aria-label="<?= e(__('content', 'Comments')) ?>"
                                >
                                    <?= e(str_limit($comment['content'], 200)) ?>
                                </button>
                                <small class="text-muted">
                                    <?= __('post', 'Comments') ?>: <?= e($comment['post_type'] ?? 'post') ?> #<?= e($comment['post_id']) ?>
                                </small>
                            </td>
                            <td data-label="<?= __('status', 'Comments') ?>">
                                <?php if ($comment['status'] === 'approved'): ?>
                                    <span class="badge badge-success"><?= __('approved', 'Comments') ?></span>
                                <?php elseif ($comment['status'] === 'rejected'): ?>
                                    <span class="badge badge-danger"><?= __('rejected', 'Comments') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= __('pending', 'Comments') ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= __('date', 'Comments') ?>"><?= human_date($comment['created_at']) ?></td>
                            <td data-label="<?= __('actions', 'Core') ?>" class="comment-actions-cell">
                                <div class="table-actions table-actions-compact comment-actions">
                                    <?php if ($comment['status'] !== 'approved'): ?>
                                        <form action="<?= url('/admin/comments/' . $comment['id'] . '/approve') ?>" method="POST">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="table-action table-action-approve"
                                                title="<?= e(__('approve', 'Comments')) ?>"
                                                aria-label="<?= e(__('approve', 'Comments')) ?>"
                                            >
                                                <i class="fas fa-check" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($comment['status'] !== 'rejected'): ?>
                                        <form action="<?= url('/admin/comments/' . $comment['id'] . '/reject') ?>" method="POST">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="table-action table-action-reject"
                                                title="<?= e(__('reject', 'Comments')) ?>"
                                                aria-label="<?= e(__('reject', 'Comments')) ?>"
                                            >
                                                <i class="fas fa-xmark" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="<?= url('/admin/comments/' . $comment['id'] . '/delete') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <button
                                            type="submit"
                                            class="table-action table-action-delete"
                                            data-action="confirm-delete"
                                            data-item-name="<?= e(str_limit($comment['content'], 60)) ?>"
                                            title="<?= e(__('delete', 'Core')) ?>"
                                            aria-label="<?= e(__('delete', 'Core')) ?>"
                                        >
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($comments['total_pages'] > 1): ?>
            <?= pagination($comments, url('/admin/comments?status=' . $status)) ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div
    class="comments-read-modal"
    id="commentsReadModal"
    hidden
    aria-hidden="true"
    data-label-author="<?= e(__('author', 'Comments')) ?>"
    data-label-date="<?= e(__('date', 'Comments')) ?>"
    data-label-post="<?= e(__('post', 'Comments')) ?>"
>
    <div class="comments-read-modal__backdrop" data-comment-modal-close></div>
    <div class="comments-read-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="commentsReadModalTitle">
        <button type="button" class="comments-read-modal__close" data-comment-modal-close aria-label="<?= e(__('close', 'Core')) ?>">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
        <div class="comments-read-modal__head">
            <h2 class="comments-read-modal__title" id="commentsReadModalTitle"><?= __('comments', 'Comments') ?></h2>
            <p class="comments-read-modal__meta" data-comment-modal-author></p>
            <p class="comments-read-modal__meta" data-comment-modal-date></p>
            <p class="comments-read-modal__meta" data-comment-modal-post></p>
        </div>
        <div class="comments-read-modal__content" data-comment-modal-content></div>
    </div>
</div>

<script src="<?= module_asset('Comments', 'js/comments-admin.js') ?>"></script>
