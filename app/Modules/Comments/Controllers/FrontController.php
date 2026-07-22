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

class FrontController extends BaseController
{
    private FlatFile $comments;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Comments');
        $this->comments = FlatFile::for('core/comments');
    }

    public function store(): void
    {
        if (!$this->verifyCsrf()) return;

        $data = $this->request->only(['post_id', 'post_type', 'author_name', 'author_email', 'content']);

        $authorName = $this->sanitizeCommentText((string) ($data['author_name'] ?? ''));
        $content = $this->sanitizeCommentText((string) ($data['content'] ?? ''), true);
        $authorEmail = trim((string) ($data['author_email'] ?? ''));

        // Validate
        if ($authorName === '' || $authorEmail === '' || $content === '') {
            $this->session->flash('error', __('all_fields_required', 'Comments'));
            $this->back();
            return;
        }

        if (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
            $this->session->flash('error', __('invalid_email', 'Comments'));
            $this->back();
            return;
        }

        // Simple spam check
        if (mb_strlen($content) < 5 || mb_strlen($content) > 5000) {
            $this->session->flash('error', __('invalid_content_length', 'Comments'));
            $this->back();
            return;
        }

        // Get settings for auto-approve
        $settings = FlatFile::settings();
        $autoApprove = $settings['comments_auto_approve'] ?? false;

        $comment = [
            'post_id' => $data['post_id'],
            'post_type' => $data['post_type'] ?? 'post',
            'author_name' => $authorName,
            'author_email' => $authorEmail,
            'content' => $content,
            'status' => $autoApprove ? 'approved' : 'pending',
            'ip' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ];

        $this->comments->create($comment);

        if ($autoApprove) {
            $this->session->flash('success', __('comment_posted', 'Comments'));
        } else {
            $this->session->flash('success', __('comment_pending', 'Comments'));
        }

        $this->back();
    }

    public static function getComments(string $postId, string $postType = 'post'): array
    {
        $comments = FlatFile::for('core/comments');
        $all = $comments->where('post_id', $postId);
        
        // Filter approved only and matching post type
        $filtered = array_filter($all, function($c) use ($postType) {
            return ($c['status'] ?? '') === 'approved' && ($c['post_type'] ?? 'post') === $postType;
        });

        // Sort by date
        usort($filtered, fn($a, $b) => ($a['created_at'] ?? '') <=> ($b['created_at'] ?? ''));
        $normalized = array_map([self::class, 'normalizeCommentForDisplay'], $filtered);

        return array_values($normalized);
    }

    private function sanitizeCommentText(string $value, bool $allowLineBreaks = false): string
    {
        $clean = strip_tags($value);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = str_replace(["\r\n", "\r"], "\n", $clean);

        if (!$allowLineBreaks) {
            $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;
        }

        return trim($clean);
    }

    private static function normalizeCommentForDisplay(array $comment): array
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
