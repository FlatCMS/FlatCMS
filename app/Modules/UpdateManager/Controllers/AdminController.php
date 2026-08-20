<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Modules\UpdateManager\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Modules\UpdateManager\Services\RecoveryCapsuleService;
use App\Modules\UpdateManager\Services\UpdateManagerService;
use App\Modules\UpdateManager\Services\UpdateWorkerService;

final class AdminController extends BaseController
{
    private UpdateManagerService $manager;
    private RecoveryCapsuleService $recovery;

    public function __construct()
    {
        parent::__construct();
        I18n::load('UpdateManager');
        $this->manager = new UpdateManagerService();
        $this->recovery = new RecoveryCapsuleService();
    }

    public function index(): void
    {
        if (!$this->authorize('updates.view')) {
            return;
        }

        try {
            $status = $this->manager->status();
        } catch (\Throwable $exception) {
            $status = [
                'checked_at' => '',
                'core_version' => flatcms_version(),
                'php_version' => PHP_VERSION,
                'update_count' => 0,
                'incompatible_count' => 0,
                'installed_count' => 0,
                'errors' => ['system' => $exception->getMessage()],
                'catalogs' => [],
            ];
        }

        $recoveryState = $this->recovery->state();
        $recoveryStatus = (string) ($recoveryState['status'] ?? '');
        $updateInProgress = in_array($recoveryStatus, ['armed', 'backup_ready', 'updating'], true);
        $updateMonitoring = $recoveryStatus === 'monitoring'
            && (int) ($recoveryState['monitor_until'] ?? 0) >= time();
        $recoveryRequired = in_array($recoveryStatus, ['failed', 'failed_post_update', 'restoring', 'recovery_failed'], true)
            && trim((string) ($recoveryState['full_backup_path'] ?? '')) !== '';
        $updateOperationLocked = $updateInProgress || $updateMonitoring || $recoveryRequired;
        if ($updateInProgress && !headers_sent()) {
            header('Refresh: 3');
        }

        $this->render('UpdateManager/Views/admin/index', [
            'pageTitle' => __('updates_title', 'UpdateManager'),
            'status' => $status,
            'canManageUpdates' => function_exists('can') && can('updates.manage'),
            'recoveryState' => $recoveryState,
            'updateInProgress' => $updateInProgress,
            'updateMonitoring' => $updateMonitoring,
            'recoveryRequired' => $recoveryRequired,
            'updateOperationLocked' => $updateOperationLocked,
        ], 'admin.main');
    }

    public function check(): void
    {
        if (!$this->authorize('updates.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $status = $this->manager->checkNow();
            $updates = (int) ($status['update_count'] ?? 0);
            $errors = is_array($status['errors'] ?? null) ? count($status['errors']) : 0;

            $messageKey = $errors > 0
                ? 'updates_check_partial'
                : ($updates > 0 ? 'updates_check_found' : 'updates_check_current');
            $this->session->flash('success', __($messageKey, 'UpdateManager', [
                'count' => (string) $updates,
                'errors' => (string) $errors,
            ]));
        } catch (\Throwable) {
            $this->session->flash('error', __('updates_check_failed', 'UpdateManager'));
        }

        $this->redirect(url('/admin/updates'));
    }

    public function installCore(string $version): void
    {
        if (!$this->authorize('updates.manage') || !$this->verifyCsrf()) {
            return;
        }

        @set_time_limit(0);
        $armed = false;
        try {
            $access = $this->recovery->arm($version);
            $armed = true;
            $token = (string) ($access['token'] ?? '');
            $state = is_array($access['state'] ?? null) ? $access['state'] : [];
            $this->setRecoveryCookie($token, (int) ($state['expires_at'] ?? (time() + 21600)));

            $result = (new UpdateWorkerService())->dispatchCore($version);
            if ((string) ($result['status'] ?? '') === 'started') {
                $this->recovery->markDispatched(
                    (int) ($result['pid'] ?? 0),
                    (string) ($result['log_path'] ?? '')
                );
                $this->session->flash('success', __('recovery_updating_title', 'UpdateManager'));
                $this->redirect(url('/admin/updates'));
                return;
            }

            $installed = (string) ($result['version'] ?? $version);
            $this->session->flash('success', __('updates_install_success', 'UpdateManager', ['version' => $installed]));
            $this->redirect(url('/admin/updates'));
            return;
        } catch (\Throwable $exception) {
            if ($exception->getMessage() === 'update_recovery_pending') {
                try {
                    $access = $this->recovery->resumeAccess();
                    $token = (string) ($access['token'] ?? '');
                    $state = is_array($access['state'] ?? null) ? $access['state'] : [];
                    $this->setRecoveryCookie($token, (int) ($state['expires_at'] ?? (time() + 21600)));
                    $this->redirect(url('/recovery.php'));
                    return;
                } catch (\Throwable) {
                    // Preserve any existing Recovery cookie; never make a pending recovery less accessible.
                }
                $this->session->flash('error', __('updates_install_failed', 'UpdateManager', ['error' => $this->formatInstallError($exception->getMessage())]));
                $this->redirect(url('/admin/updates'));
                return;
            }
            if ($armed && $this->recovery->requiresRecovery()) {
                $this->redirect(url('/recovery.php'));
                return;
            }
            if ($armed) {
                $this->recovery->cancelIfSafe();
                $this->recovery->cancelIfUnprepared();
            }
            $this->clearRecoveryCookie();
            $this->session->flash('error', __('updates_install_failed', 'UpdateManager', ['error' => $this->formatInstallError($exception->getMessage())]));
            $this->redirect(url('/admin/updates'));
        }
    }

    public function resumeRecovery(): void
    {
        if (!$this->authorize('updates.manage') || !$this->verifyCsrf()) {
            return;
        }

        try {
            $access = $this->recovery->resumeAccess();
            $token = (string) ($access['token'] ?? '');
            $state = is_array($access['state'] ?? null) ? $access['state'] : [];
            $this->setRecoveryCookie($token, (int) ($state['expires_at'] ?? (time() + 21600)));
            $this->redirect(url('/recovery.php'));
            return;
        } catch (\Throwable $exception) {
            $this->session->flash('error', __('updates_install_failed', 'UpdateManager', [
                'error' => $this->formatInstallError($exception->getMessage()),
            ]));
            $this->redirect(url('/admin/updates'));
        }
    }

    private function formatInstallError(string $error): string
    {
        if ($error === 'update_worker_async_unavailable') {
            return __('updates_worker_async_unavailable', 'UpdateManager');
        }
        if ($error === 'update_worker_dispatch_log_failed') {
            return __('updates_worker_dispatch_log_failed', 'UpdateManager');
        }
        if (str_starts_with($error, 'update_worker_dispatch_failed')) {
            return __('updates_worker_dispatch_failed', 'UpdateManager');
        }
        if (!str_starts_with($error, 'update_preflight_permissions_failed:')) {
            return $error;
        }
        $parts = explode(':', $error, 3);
        $count = (string) ($parts[1] ?? '0');
        $paths = str_replace('|', ', ', (string) ($parts[2] ?? ''));
        return __('updates_preflight_permissions_failed', 'UpdateManager', [
            'count' => $count,
            'paths' => $paths,
        ]);
    }

    private function setRecoveryCookie(string $token, int $expiresAt): void
    {
        if ($token === '') {
            return;
        }
        setcookie(RecoveryCapsuleService::COOKIE_NAME, $token, [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    private function clearRecoveryCookie(): void
    {
        setcookie(RecoveryCapsuleService::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

}
