<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Trash\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Modules\Auth\Services\RoleService;
use App\Modules\Trash\Services\TrashService;

class AdminController extends BaseController
{
    private TrashService $trash;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Trash');
        $this->trash = new TrashService();
    }

    public function index(): void
    {
        $allowedTypes = $this->resolveAllowedTypes();
        if ($allowedTypes === []) {
            $this->session->flash('error', __('error.unauthorized', 'Core'));
            $this->redirect(url('/admin'));
            return;
        }

        $type = $this->normalizeType((string) $this->request->input('type', 'all'));
        if ($type === 'all' && count($allowedTypes) === 1) {
            $type = $allowedTypes[0];
        } elseif ($type !== 'all' && !in_array($type, $allowedTypes, true)) {
            $type = count($allowedTypes) === 1 ? $allowedTypes[0] : 'all';
        }

        $page = max(1, (int) $this->request->input('page', 1));
        $items = $this->paginateManageableItems($type, $page, 20);

        $this->render('Trash/Views/admin/index', [
            'pageTitle' => __('trash_title', 'Trash'),
            'trashItems' => $items,
            'trashType' => $type,
            'trashAllowedTypes' => $allowedTypes,
            'trashBackUrl' => $this->resolveBackUrl($type),
            'trashBackLabel' => $this->resolveBackLabel($type),
        ], 'admin.main');
    }

    public function restore(string $id): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $item = $this->trash->findItem($id);
        if (!is_array($item)) {
            $this->session->flash('error', __('trash_item_not_found', 'Trash'));
            $this->redirect($this->trashUrl($this->normalizeType((string) $this->request->input('type', 'all'))));
            return;
        }

        if (!$this->canManageItem($item)) {
            $this->session->flash('error', __('error.unauthorized', 'Core'));
            $this->redirect($this->trashUrl((string) ($item['entity_type'] ?? 'all')));
            return;
        }

        $result = $this->trash->restoreItem($id);
        if (!empty($result['success'])) {
            $this->session->flash('success', __('trash_restore_success', 'Trash'));
            $this->redirect($this->trashUrl((string) ($item['entity_type'] ?? 'all')));
            return;
        }

        $code = (string) ($result['code'] ?? 'restore_failed');
        $messageKey = match ($code) {
            'not_found' => 'trash_item_not_found',
            'id_conflict' => 'trash_restore_conflict',
            default => 'trash_restore_failed',
        };

        $this->session->flash('error', __($messageKey, 'Trash'));
        $this->redirect($this->trashUrl((string) ($item['entity_type'] ?? 'all')));
    }

    public function delete(string $id): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $item = $this->trash->findItem($id);
        if (!is_array($item)) {
            $this->session->flash('error', __('trash_item_not_found', 'Trash'));
            $this->redirect($this->trashUrl($this->normalizeType((string) $this->request->input('type', 'all'))));
            return;
        }

        if (!$this->canManageItem($item)) {
            $this->session->flash('error', __('error.unauthorized', 'Core'));
            $this->redirect($this->trashUrl((string) ($item['entity_type'] ?? 'all')));
            return;
        }

        if (!$this->trash->delete($id)) {
            $this->session->flash('error', __('trash_delete_failed', 'Trash'));
            $this->redirect($this->trashUrl((string) ($item['entity_type'] ?? 'all')));
            return;
        }

        $this->session->flash('success', __('trash_delete_success', 'Trash'));
        $this->redirect($this->trashUrl((string) ($item['entity_type'] ?? 'all')));
    }

    public function batch(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $type = $this->normalizeType((string) $this->request->input('type', 'all'));
        $action = trim((string) $this->request->input('action', ''));
        $ids = $this->normalizeBatchIds($this->request->input('ids', []));

        if ($ids === []) {
            $this->session->flash('error', __('trash_batch_no_selection', 'Trash'));
            $this->redirect($this->trashUrl($type));
            return;
        }

        if (!in_array($action, ['restore', 'delete'], true)) {
            $this->session->flash('error', __('trash_batch_invalid_action', 'Trash'));
            $this->redirect($this->trashUrl($type));
            return;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($ids as $id) {
            $item = $this->trash->findItem($id);
            if (!is_array($item) || !$this->canManageItem($item)) {
                $errorCount++;
                continue;
            }

            if ($action === 'restore') {
                $result = $this->trash->restoreItem($id);
                if (!empty($result['success'])) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
                continue;
            }

            if ($this->trash->delete($id)) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        if ($successCount > 0) {
            $successKey = $action === 'restore' ? 'trash_batch_restore_success' : 'trash_batch_delete_success';
            $this->session->flash('success', __($successKey, 'Trash', ['count' => (string) $successCount]));
        }

        if ($errorCount > 0) {
            $errorKey = $action === 'restore' ? 'trash_batch_restore_error' : 'trash_batch_delete_error';
            $this->session->flash('error', __($errorKey, 'Trash', ['count' => (string) $errorCount]));
        }

        $this->redirect($this->trashUrl($type));
    }

    /**
     * @return array<int, string>
     */
    private function resolveAllowedTypes(): array
    {
        $allowed = [];
        if (can('pages.view')) {
            $allowed[] = 'page';
        }
        if (can('posts.view')) {
            $allowed[] = 'post';
        }
        if (can('categories.view')) {
            $allowed[] = 'category';
        }
        if (can('themes.edit')) {
            $allowed[] = 'theme';
        }
        if (can('media.delete')) {
            $allowed[] = 'media';
        }

        return $allowed;
    }

    private function canManageItem(array $item): bool
    {
        $type = (string) ($item['entity_type'] ?? '');
        if ($type === 'page') {
            return $this->canManageOwnedTrashEntity($item, 'pages.delete', 'pages.delete_own');
        }

        if ($type === 'category') {
            return $this->canManageOwnedTrashEntity($item, 'categories.delete', 'categories.delete_own');
        }

        if ($type === 'theme') {
            return can('themes.edit');
        }

        if ($type === 'media') {
            return can('media.delete');
        }

        if ($type !== 'post') {
            return false;
        }

        return $this->canManageOwnedTrashEntity($item, 'posts.delete', 'posts.delete_own');
    }

    private function normalizeType(string $type): string
    {
        return match (trim($type)) {
            'page', 'post', 'category', 'theme', 'media' => trim($type),
            default => 'all',
        };
    }

    private function trashUrl(string $type = 'all'): string
    {
        $type = $this->normalizeType($type);
        $baseUrl = url('/admin/trash');
        if ($type === 'all') {
            return $baseUrl;
        }

        return $baseUrl . '?type=' . urlencode($type);
    }

    private function resolveBackUrl(string $type): string
    {
        return match ($type) {
            'post' => url('/admin/posts'),
            'category' => url('/admin/categories'),
            'theme' => url('/admin/themes'),
            'media' => url('/admin/media'),
            default => url('/admin/pages'),
        };
    }

    private function resolveBackLabel(string $type): string
    {
        return match ($type) {
            'post' => __('trash_back_to_posts', 'Trash'),
            'category' => __('trash_back_to_categories', 'Trash'),
            'theme' => __('trash_back_to_themes', 'Trash'),
            'media' => __('trash_back_to_media', 'Trash'),
            default => __('trash_back_to_pages', 'Trash'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function paginateManageableItems(string $type, int $page, int $perPage): array
    {
        $allItems = array_values(array_filter(
            $this->trash->all($type),
            fn (array $item): bool => $this->canManageItem($item)
        ));

        $total = count($allItems);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'data' => array_slice($allItems, $offset, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
            'type' => $this->normalizeType($type),
        ];
    }

    private function canManageOwnedTrashEntity(array $item, string $deletePermission, string $ownDeletePermission): bool
    {
        if (can($deletePermission)) {
            return true;
        }

        if (!can($ownDeletePermission)) {
            return false;
        }

        $user = auth();
        if (!is_array($user)) {
            return false;
        }

        $role = (string) ($user['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER);
        if (!RoleService::hasPermission($role, $ownDeletePermission)) {
            return false;
        }

        $userId = trim((string) ($user['id'] ?? ''));
        $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];
        $authorId = trim((string) ($payload['author_id'] ?? ''));

        return $userId !== '' && $authorId !== '' && $userId === $authorId;
    }

    /**
     * @param mixed $rawIds
     * @return array<int, string>
     */
    private function normalizeBatchIds(mixed $rawIds): array
    {
        if (!is_array($rawIds)) {
            return [];
        }

        $ids = [];
        foreach ($rawIds as $rawId) {
            if (!is_string($rawId) && !is_numeric($rawId)) {
                continue;
            }

            $id = trim((string) $rawId);
            if ($id === '') {
                continue;
            }

            $ids[$id] = $id;
        }

        return array_values($ids);
    }
}
