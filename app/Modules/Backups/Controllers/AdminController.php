<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Backups\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Modules\Backups\Services\SiteBackupService;

final class AdminController extends BaseController
{
    private SiteBackupService $service;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Backups');
        $this->service = new SiteBackupService();
    }

    public function index(): void
    {
        if (!$this->authorize('backups.view')) {
            return;
        }

        $backups = $this->service->listBackups();
        $totalSize = 0;
        foreach ($backups as $backup) {
            $totalSize += (int) ($backup['size_bytes'] ?? 0);
        }

        $this->render('Backups/Views/admin/index', [
            'pageTitle' => __('backups_title', 'Backups'),
            'backups' => $backups,
            'zipAvailable' => $this->service->zipAvailable(),
            'canManageBackups' => can('backups.manage'),
            'backupStoragePath' => defined('STORAGE_PATH')
                ? rtrim((string) STORAGE_PATH, '/') . '/backups/site'
                : BASE_PATH . '/storage/backups/site',
            'totalBackupSize' => $totalSize,
        ], 'admin.main');
    }

    public function create(): void
    {
        if (!$this->authorize('backups.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $backup = $this->service->createBackup($this->backupContext('manual'));
            $this->session->flash('success', __('backups_create_success', 'Backups', [
                'backup' => (string) ($backup['filename'] ?? ''),
            ]));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', __($exception->getMessage(), 'Backups'));
        }

        $this->redirect(url('/admin/backups'));
    }

    public function download(string $filename): void
    {
        if (!$this->authorize('backups.view')) {
            return;
        }

        $path = $this->service->resolveStoredBackupPath($filename);
        if ($path === null) {
            $this->session->flash('error', __('backups_archive_not_found', 'Backups'));
            $this->redirect(url('/admin/backups'));
            return;
        }

        $this->response->download($path, basename($path));
    }

    public function restore(string $filename): void
    {
        if (!$this->authorize('backups.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $result = $this->service->restoreStoredBackup($filename, $this->backupContext('restore'));
            $rollbackName = (string) (($result['rollback']['filename'] ?? ''));
            $this->session->flash('success', __('backups_restore_success', 'Backups', [
                'count' => (string) ((int) ($result['restored_files_count'] ?? 0)),
                'backup' => $rollbackName,
            ]));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', __($exception->getMessage(), 'Backups'));
        }

        $this->redirect(url('/admin/backups'));
    }

    public function restoreUpload(): void
    {
        if (!$this->authorize('backups.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $result = $this->service->restoreUploadedBackup($this->request->file('backup_zip'), $this->backupContext('upload_restore'));
            $rollbackName = (string) (($result['rollback']['filename'] ?? ''));
            $this->session->flash('success', __('backups_restore_success', 'Backups', [
                'count' => (string) ((int) ($result['restored_files_count'] ?? 0)),
                'backup' => $rollbackName,
            ]));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', __($exception->getMessage(), 'Backups'));
        }

        $this->redirect(url('/admin/backups'));
    }

    public function delete(string $filename): void
    {
        if (!$this->authorize('backups.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $this->service->deleteStoredBackup($filename);
            $this->session->flash('success', __('backups_delete_success', 'Backups', [
                'backup' => $filename,
            ]));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', __($exception->getMessage(), 'Backups'));
        }

        $this->redirect(url('/admin/backups'));
    }

    public function reset(): void
    {
        if (!$this->authorize('backups.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $result = $this->service->resetSiteContent($this->backupContext('reset'));
            $rollbackName = (string) (($result['rollback']['filename'] ?? ''));
            $this->session->flash('success', __('backups_reset_success', 'Backups', [
                'backup' => $rollbackName,
            ]));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', __($exception->getMessage(), 'Backups'));
        }

        $this->redirect(url('/admin/backups'));
    }

    public function factoryReset(): void
    {
        if (!$this->authorize('backups.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $this->service->factoryResetSite($this->backupContext('factory_reset'));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', __($exception->getMessage(), 'Backups'));
            $this->redirect(url('/admin/backups'));
            return;
        }

        $this->session->destroy();
        $this->redirect(base_url() . '/?step=1');
    }

    /**
     * @return array<string, string>
     */
    private function backupContext(string $reason): array
    {
        $user = $this->session->get('user');

        return [
            'reason' => $reason,
            'created_by' => trim((string) ($user['name'] ?? $user['email'] ?? '')),
            'created_by_email' => trim((string) ($user['email'] ?? '')),
        ];
    }
}
