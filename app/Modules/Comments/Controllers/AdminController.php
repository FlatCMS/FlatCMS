<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Comments\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Core\FlatFile;

class AdminController extends BaseController
{
    private FlatFile $comments;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Comments');
        $this->comments = FlatFile::for('comments');
    }

    public function index(): void
    {
        if (!$this->authorize('comments.view')) {
            return;
        }

        $status = $this->request->input('status', 'all');
        $page = (int) $this->request->input('page', 1);
        
        if ($status === 'all') {
            $allComments = $this->comments->all();
        } else {
            $allComments = $this->comments->where('status', $status);
        }

        $allComments = array_map([$this, 'normalizeCommentForDisplay'], $allComments);

        // Sort by date desc
        usort($allComments, fn($a, $b) => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));

        // Paginate
        $perPage = 20;
        $total = count($allComments);
        $comments = [
            'data' => array_slice($allComments, ($page - 1) * $perPage, $perPage),
            'total' => $total,
            'current_page' => $page,
            'total_pages' => (int) ceil($total / $perPage),
            'has_more' => $page < ceil($total / $perPage),
        ];

        // Count by status
        $counts = [
            'all' => $this->comments->count(),
            'pending' => count($this->comments->where('status', 'pending')),
            'approved' => count($this->comments->where('status', 'approved')),
            'rejected' => count($this->comments->where('status', 'rejected')),
        ];

        $this->render('Comments/Views/admin/index', [
            'pageTitle' => __('comments', 'Comments'),
            'comments' => $comments,
            'status' => $status,
            'counts' => $counts,
        ], 'admin.main');
    }

    public function approve(string $id): void
    {
        if (!$this->authorize('comments.moderate')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $comment = $this->comments->find($id);
        if ($comment) {
            hook_run('comments.before_approve', $comment);
        }
        $updated = $this->comments->update($id, ['status' => 'approved']);
        if ($updated) {
            hook_run('comments.after_approve', $updated);
        }
        
        $this->session->flash('success', __('comment_approved', 'Comments'));
        $this->redirect(url('/admin/comments'));
    }

    public function reject(string $id): void
    {
        if (!$this->authorize('comments.moderate')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $this->comments->update($id, ['status' => 'rejected']);
        
        $this->session->flash('success', __('comment_rejected', 'Comments'));
        $this->redirect(url('/admin/comments'));
    }

    public function delete(string $id): void
    {
        if (!$this->authorize('comments.delete')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $comment = $this->comments->find($id);
        if ($comment) {
            hook_run('comments.before_delete', $comment);
        }
        $this->comments->delete($id);
        if ($comment) {
            hook_run('comments.after_delete', $comment);
        }
        
        $this->session->flash('success', __('comment_deleted', 'Comments'));
        $this->redirect(url('/admin/comments'));
    }

    private function normalizeCommentForDisplay(array $comment): array
    {
        if (isset($comment['author_name'])) {
            $comment['author_name'] = html_entity_decode((string) $comment['author_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (isset($comment['content'])) {
            $comment['content'] = html_entity_decode((string) $comment['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $comment;
    }
}
