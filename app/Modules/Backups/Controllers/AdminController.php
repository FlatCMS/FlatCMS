<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Backups/Controllers/AdminController.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Backups\Controllers;

use App\Core\BaseController;
use App\Core\CoreManifest;
use App\Core\I18n;
use App\Core\RuntimeProbe;
use App\Modules\Backups\Services\SiteBackupService;
use App\Modules\Backups\Services\FullBackupService;

final class AdminController extends BaseController
{
    private SiteBackupService $service;
    private FullBackupService $fullService;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Backups');
        $this->service = new SiteBackupService();
        $this->fullService = new FullBackupService();
    }

    public function index(): void
    {
        if (!$this->authorize('backups.view')) {
            return;
        }

        $backups = array_merge(
            $this->service->listBackups(),
            $this->fullService->listBackups()
        );
        usort($backups, static fn (array $left, array $right): int =>
            ((int) ($right['created_ts'] ?? 0)) <=> ((int) ($left['created_ts'] ?? 0))
        );
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
            'fullBackupStoragePath' => defined('STORAGE_PATH')
                ? rtrim((string) STORAGE_PATH, '/') . '/backups/full'
                : BASE_PATH . '/storage/backups/full',
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
            $this->session->flash('error', $this->failureMessage($exception));
        }

        $this->redirect(url('/admin/backups'));
    }

    public function download(string $filename): void
    {
        if (!$this->authorize('backups.view')) {
            return;
        }

        $path = $this->service->resolveStoredBackupPath($filename)
            ?? $this->fullService->resolveStoredBackupPath($filename);
        if ($path === null) {
            $this->session->flash('error', __('backups_archive_not_found', 'Backups'));
            $this->redirect(url('/admin/backups'));
            return;
        }

        $this->response->download($path, basename($path));
    }

    public function downloadKey(string $filename): void
    {
        if (!$this->authorize('backups.manage')) {
            return;
        }

        $path = $this->service->resolveStoredKeyPath($filename)
            ?? $this->fullService->resolveStoredKeyPath($filename);
        if ($path === null) {
            $this->session->flash('error', __('backups_key_not_found', 'Backups'));
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
            if ($this->fullService->resolveStoredBackupPath($filename) !== null) {
                $result = $this->fullService->restoreStoredBackup(
                    $filename,
                    $this->backupContext('restore'),
                    fn (string $version): bool => $this->acceptRestoredRuntime($version, 'full-restoration')
                );
            } else {
                $result = $this->service->restoreStoredBackup(
                    $filename,
                    $this->backupContext('restore'),
                    fn (): bool => $this->acceptRestoredRuntime(CoreManifest::version(), 'site-restoration')
                );
            }
            $rollbackName = (string) (($result['rollback']['filename'] ?? ''));
            $this->session->flash('success', __($rollbackName === '' ? 'backups_restore_verified' : 'backups_restore_success', 'Backups', [
                'count' => (string) ((int) ($result['restored_files_count'] ?? 0)),
                'backup' => $rollbackName,
            ]));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', $this->failureMessage($exception));
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
            $result = $this->service->restoreUploadedBackup(
                $this->request->file('backup_zip'),
                $this->backupContext('upload_restore'),
                fn (): bool => $this->acceptRestoredRuntime(CoreManifest::version(), 'site-restoration'),
                $this->request->file('backup_key')
            );
            $rollbackName = (string) (($result['rollback']['filename'] ?? ''));
            $this->session->flash('success', __($rollbackName === '' ? 'backups_restore_verified' : 'backups_restore_success', 'Backups', [
                'count' => (string) ((int) ($result['restored_files_count'] ?? 0)),
                'backup' => $rollbackName,
            ]));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', $this->failureMessage($exception));
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
            if ($this->fullService->resolveStoredBackupPath($filename) !== null) {
                $this->fullService->deleteStoredBackup($filename);
            } else {
                $this->service->deleteStoredBackup($filename);
            }
            $this->session->flash('success', __('backups_delete_success', 'Backups', [
                'backup' => $filename,
            ]));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', $this->failureMessage($exception));
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
            $this->service->resetSiteContent($this->backupContext('reset'));
            $this->session->flash('success', __('backups_reset_success', 'Backups'));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', $this->failureMessage($exception));
        }

        $this->redirect(url('/admin/backups'));
    }

    public function siteReset(): void
    {
        if (!$this->authorize('backups.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $this->service->resetSiteData($this->backupContext('site_reset'));
            $this->session->flash('success', __('backups_site_reset_success', 'Backups'));
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', $this->failureMessage($exception));
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

        $deleteSensitive = (string) $this->request->input('delete_sensitive', '0') === '1';

        try {
            $this->service->factoryResetSite($this->backupContext('factory_reset'), $deleteSensitive);
        } catch (\RuntimeException $exception) {
            $this->session->flash('error', $this->failureMessage($exception));
            $this->redirect(url('/admin/backups'));
            return;
        }

        $this->session->destroy();
        $this->redirect(base_url() . '/?step=1');
    }

    private function failureMessage(\RuntimeException $exception): string
    {
        $translatedKey = '';
        for ($cause = $exception; $cause !== null; $cause = $cause->getPrevious()) {
            if ($cause->getMessage() === 'runtime_transaction_acceptance_failed') {
                return __('backups_restore_health_check_failed', 'Backups');
            }
            if ($translatedKey === ''
                && preg_match('/^backups_[a-z0-9_]+$/D', $cause->getMessage()) === 1) {
                $translatedKey = $cause->getMessage();
            }
        }
        if ($translatedKey === '') {
            error_log('[Backups] ' . $exception::class . ': ' . $exception->getMessage());
            $translatedKey = 'backups_operation_failed';
        }
        return __($translatedKey, 'Backups');
    }

    private function acceptRestoredRuntime(string $version, string $scope): bool
    {
        return (new RuntimeProbe())->check(BASE_PATH, trim($version), [$scope]);
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
