<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/UpdateManager/Recovery/recovery-runtime.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

if (!function_exists('flatcms_recovery_run')) {
    function flatcms_recovery_run(string $basePath): void
    {
        try {
            $store = flatcms_recovery_state_store($basePath);
            $state = $store->read();
            if (($state['kind'] ?? '') !== 'flatcms-recovery-state') { flatcms_recovery_not_found(); }
            $cookie = (string) ($state['cookie_name'] ?? 'flatcms_recovery');
            $token = trim((string) ($_GET['token'] ?? $_COOKIE[$cookie] ?? ''));
            if ($token === '' || !hash_equals((string) ($state['token_hash'] ?? ''), hash('sha256', $token))) {
                flatcms_recovery_forbidden();
            }
            // Application barrier precedes journal locks; otherwise draining workers can deadlock.
            flatcms_recovery_application_lock($basePath)->exclusive(
                static fn () => $store->exclusive(static fn () => flatcms_recovery_run_locked($basePath)), ['full-restoration', 'core-update']
            );
        } catch (Throwable) {
            // Fail closed without discarding an unreadable recovery journal.
            http_response_code(503);
            exit;
        }
    }

    function flatcms_recovery_run_locked(string $basePath): void
    {
        $basePath = rtrim($basePath, '/\\');
        $recoveryRoot = $basePath . '/storage/recovery';
        $statePath = $recoveryRoot . '/active.json';
        $runtimeService = $recoveryRoot . '/runtime/FullBackupService.php';
        $state = flatcms_recovery_state_store($basePath)->read();
        if ($state === [] || ($state['kind'] ?? '') !== 'flatcms-recovery-state') {
            flatcms_recovery_not_found();
        }
        flatcms_recovery_boot_i18n($recoveryRoot, $state);

        $cookieName = trim((string) ($state['cookie_name'] ?? 'flatcms_recovery')) ?: 'flatcms_recovery';
        $token = trim((string) ($_GET['token'] ?? $_COOKIE[$cookieName] ?? ''));
        $expected = trim((string) ($state['token_hash'] ?? ''));
        if ($token === '' || $expected === '' || !hash_equals($expected, hash('sha256', $token))) {
            flatcms_recovery_forbidden();
        }
        if ((int) ($state['expires_at'] ?? 0) < time() && (string) ($state['status'] ?? '') !== 'recovered') {
            flatcms_recovery_render($state, 'expired', flatcms_recovery_t('recovery_access_expired_title'), '', false);
        }

        flatcms_recovery_headers();
        $status = (string) ($state['status'] ?? 'armed');
        if ($status === 'finalizing') { flatcms_recovery_forbidden(); }
        if (in_array($status, ['armed', 'backup_ready', 'updating'], true) && flatcms_recovery_update_lock_held($basePath)) {
            flatcms_recovery_render($state, 'updating', flatcms_recovery_t('recovery_updating_title'), flatcms_recovery_t('recovery_updating_message'), false, true);
        }

        if (in_array($status, ['armed', 'backup_ready', 'updating'], true)) {
            $state['status'] = 'failed';
            $state['failed_at'] = gmdate('c');
            $state['error'] = trim((string) ($state['error'] ?? '')) ?: 'update_process_interrupted';
            $state['updated_at'] = gmdate('c');
            flatcms_recovery_write_json($statePath, $state);
            $status = 'failed';
        }

        if ($status === 'monitoring') {
            flatcms_recovery_render(
                $state,
                'monitoring',
                flatcms_recovery_t('recovery_monitoring_title'),
                flatcms_recovery_t('recovery_monitoring_message'),
                false
            );
        }

        if ($status === 'recovered') {
            flatcms_recovery_render($state, 'success', flatcms_recovery_t('recovery_success_title'), flatcms_recovery_t('recovery_success_message'), false);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = trim((string) ($_POST['recovery_csrf'] ?? ''));
            $expectedCsrf = hash_hmac('sha256', 'flatcms-recover', $token);
            if (!hash_equals($expectedCsrf, $csrf)) {
                flatcms_recovery_render($state, 'failed', flatcms_recovery_t('recovery_invalid_request_title'), flatcms_recovery_t('recovery_invalid_request_message'), true);
            }
            flatcms_recovery_restore($basePath, $statePath, $runtimeService, $state);
        }

        try { $hasBackup = is_file(flatcms_recovery_backup_reference($basePath)->path((string) ($state['full_backup_path'] ?? ''))); }
        catch (Throwable) { $hasBackup = false; }
        $message = !empty($state['auto_rollback_succeeded'])
            ? flatcms_recovery_t('recovery_rollback_done_message')
            : flatcms_recovery_t('recovery_failure_message');
        flatcms_recovery_render($state, 'failed', flatcms_recovery_t('recovery_failure_title'), $message, $hasBackup, false, $token);
    }

    /** @param array<string,mixed> $state */
    function flatcms_recovery_restore(string $basePath, string $statePath, string $runtimeService, array $state): void
    {
        flatcms_recovery_application_lock($basePath)->exclusive(static fn () => flatcms_recovery_state_store($basePath)->exclusive(static function () use ($basePath, $statePath, $runtimeService, $state): void {
            $current = flatcms_recovery_state_store($basePath)->read();
            if ($current === [] || ($current['recovery_id'] ?? '') !== ($state['recovery_id'] ?? '')
                || ($current['token_hash'] ?? '') !== ($state['token_hash'] ?? '')
                || in_array($current['status'] ?? '', ['finalizing', 'recovered', 'success'], true)
                || flatcms_recovery_update_lock_held($basePath)) {
                flatcms_recovery_forbidden();
            }
            flatcms_recovery_restore_locked($basePath, $statePath, $runtimeService, $current);
        }), ['full-restoration', 'core-update']);
    }

    function flatcms_recovery_restore_locked(string $basePath, string $statePath, string $runtimeService, array $state): void
    {
        $lockPath = dirname($statePath) . '/recovery.lock';
        $lock = @fopen($lockPath, 'c+');
        if (!is_resource($lock) || !flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) fclose($lock);
            flatcms_recovery_render($state, 'failed', flatcms_recovery_t('recovery_in_progress_title'), flatcms_recovery_t('recovery_in_progress_message'), false);
        }
        try {
            $verified = flatcms_recovery_backup_reference($basePath)->verify($state);
            $archive = $verified['path'];
            $keyPath = $verified['key_path'];
            if (!is_file($runtimeService)) {
                throw new RuntimeException('recovery_runtime_missing');
            }
            $barrierRuntime = dirname($runtimeService) . '/ApplicationLock.php';
            if (!is_file($barrierRuntime)) { throw new RuntimeException('recovery_runtime_missing'); }
            require_once $barrierRuntime;
            $streamRuntime = dirname($runtimeService) . '/StreamFileTransaction.php';
            if (!is_file($streamRuntime)) { throw new RuntimeException('recovery_runtime_missing'); }
            require_once $streamRuntime;
            $cipherRuntime = dirname($runtimeService) . '/BackupSecretCipher.php';
            if (!is_file($cipherRuntime)) { throw new RuntimeException('recovery_runtime_missing'); }
            require_once $cipherRuntime;
            require_once $runtimeService;
            if (!class_exists('FlatCMS\\RecoverySnapshot\\FullBackupService', false)) {
                throw new RuntimeException('recovery_runtime_invalid');
            }

            $state['status'] = 'restoring';
            $state['restore_started_at'] = gmdate('c');
            $state['updated_at'] = gmdate('c');
            flatcms_recovery_write_json($statePath, $state);

            if (isset($state['core_transaction'])) {
                foreach (['CoreUpdatePathPolicy', 'CoreUpdateRecoveryService'] as $class) {
                    $path = dirname($runtimeService) . '/' . $class . '.php';
                    if (!is_file($path)) { throw new RuntimeException('recovery_runtime_missing'); }
                    require_once $path;
                }
                // A Core operation never restores an older generation of user content or licences.
                $result = (new \FlatCMS\RecoverySnapshot\CoreUpdateRecoveryService($basePath, flatcms_recovery_state_store($basePath)))->rollback();
                $state = flatcms_recovery_state_store($basePath)->read();
                if (isset($state['previous_maintenance']) && is_bool($state['previous_maintenance'])) {
                    $settings = new \FlatCMS\RecoverySnapshot\Storage\JsonStore($basePath . '/data',
                        new \FlatCMS\RecoverySnapshot\Storage\AtomicFileWriter($basePath . '/data',
                            new \FlatCMS\RecoverySnapshot\Storage\FileLockManager($basePath . '/storage/cache/locks/update-settings')));
                    $settings->mutate('settings.json', static function (array $value) use ($state): array {
                        if ($value === []) { throw new RuntimeException('update_maintenance_settings_invalid'); }
                        $value['maintenance_mode'] = $state['previous_maintenance'];
                        return $value;
                    });
                }
            } else {
                // Compatibility for capsules created before targeted Core plans existed.
                $service = new \FlatCMS\RecoverySnapshot\FullBackupService($basePath);
                $service->recoverRestoration($basePath);
                $result = $service->restoreBackupTo($archive, $basePath, $keyPath);
            }
            if (!flatcms_recovery_health_check($basePath, (string) ($state['from_version'] ?? ''))) {
                throw new RuntimeException('recovery_health_check_failed');
            }

            $state['status'] = 'recovered';
            $state['recovered_at'] = gmdate('c');
            $state['restored_files_count'] = (int) ($result['restored_files_count'] ?? 0);
            $state['updated_at'] = gmdate('c');
            $state['error'] = '';
            $state['token_hash'] = hash('sha256', random_bytes(32));
            $state['expires_at'] = time();
            flatcms_recovery_write_json($statePath, $state);
            $cookieName = trim((string) ($state['cookie_name'] ?? 'flatcms_recovery')) ?: 'flatcms_recovery';
            setcookie($cookieName, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
            flatcms_recovery_render($state, 'success', flatcms_recovery_t('recovery_success_title'), flatcms_recovery_t('recovery_success_message'), false);
        } catch (Throwable $exception) {
            $state['status'] = 'recovery_failed';
            $state['recovery_error'] = $exception->getMessage();
            $state['updated_at'] = gmdate('c');
            flatcms_recovery_write_json($statePath, $state);
            flatcms_recovery_render($state, 'failed', flatcms_recovery_t('recovery_automatic_failed_title'), flatcms_recovery_t('recovery_automatic_failed_message'), true);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @param array<string,mixed> $state */
    function flatcms_recovery_boot_i18n(string $recoveryRoot, array $state): void
    {
        $locale = trim((string) ($state['locale'] ?? 'en-US'));
        if (preg_match('/^[a-z]{2}-[A-Z]{2}$/', $locale) !== 1) {
            $locale = 'en-US';
        }
        $languageRoot = $recoveryRoot . '/runtime/Languages';
        $fallback = flatcms_recovery_read_json($languageRoot . '/en-US.json');
        $current = $locale === 'en-US' ? $fallback : flatcms_recovery_read_json($languageRoot . '/' . $locale . '.json');
        $GLOBALS['flatcms_recovery_i18n'] = array_merge($fallback, $current);
        $GLOBALS['flatcms_recovery_locale'] = $locale;
    }

    /** @param array<string,string> $replace */
    function flatcms_recovery_t(string $key, array $replace = []): string
    {
        $dictionary = is_array($GLOBALS['flatcms_recovery_i18n'] ?? null) ? $GLOBALS['flatcms_recovery_i18n'] : [];
        $value = trim((string) ($dictionary[$key] ?? ''));
        if ($value === '') {
            $value = $key;
        }
        foreach ($replace as $name => $replacement) {
            $value = str_replace(':' . $name, (string) $replacement, $value);
        }
        return $value;
    }

    function flatcms_recovery_locale(): string
    {
        $locale = trim((string) ($GLOBALS['flatcms_recovery_locale'] ?? 'en-US'));
        return preg_match('/^[a-z]{2}-[A-Z]{2}$/', $locale) === 1 ? $locale : 'en-US';
    }

    function flatcms_recovery_health_check(string $basePath, string $expectedVersion): bool
    {
        flatcms_recovery_application_lock($basePath);
        $probe = $basePath . '/storage/recovery/runtime/RuntimeProbe.php';
        if (!is_file($probe)) { return false; }
        require_once $probe;
        return (new \FlatCMS\RecoverySnapshot\RuntimeProbe())->check($basePath, $expectedVersion, ['core-update']);
    }

    function flatcms_recovery_update_lock_held(string $basePath): bool
    {
        $path = $basePath . '/storage/cache/update-manager/apply.lock';
        $dir = dirname($path);
        if (!is_dir($dir)) return false;
        $handle = @fopen($path, 'c+');
        if (!is_resource($handle)) return false;
        $acquired = flock($handle, LOCK_EX | LOCK_NB);
        if ($acquired) flock($handle, LOCK_UN);
        fclose($handle);
        return !$acquired;
    }

    /** @return array<string,mixed> */
    function flatcms_recovery_read_json(string $path): array
    {
        $decoded = json_decode((string) @file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string,mixed> $data */
    function flatcms_recovery_write_json(string $path, array $data): void
    {
        $basePath = dirname($path, 3);
        if ($path !== $basePath . '/storage/recovery/active.json') {
            throw new RuntimeException('recovery_state_path_invalid');
        }
        flatcms_recovery_state_store($basePath)->write($data);
    }

    function flatcms_recovery_state_store(string $basePath): \FlatCMS\RecoverySnapshot\RecoveryStateStore
    {
        static $stores = [];
        $basePath = rtrim($basePath, '/\\');
        if (!isset($stores[$basePath])) {
            $runtime = $basePath . '/storage/recovery/runtime/';
            foreach (['StorageException', 'StoragePathGuard', 'FileLockManager', 'AtomicFileWriter', 'JsonStore', 'RecoveryStateStore'] as $class) {
                if (!is_file($runtime . $class . '.php')) { throw new RuntimeException('recovery_runtime_missing'); }
                require_once $runtime . $class . '.php';
            }
            $stores[$basePath] = new \FlatCMS\RecoverySnapshot\RecoveryStateStore($basePath);
        }
        return $stores[$basePath];
    }

    function flatcms_recovery_application_lock(string $basePath): \FlatCMS\RecoverySnapshot\Storage\ApplicationLock
    {
        flatcms_recovery_state_store($basePath);
        $path = $basePath . '/storage/recovery/runtime/ApplicationLock.php';
        if (!is_file($path)) { throw new RuntimeException('recovery_runtime_missing'); }
        require_once $path;
        return \FlatCMS\RecoverySnapshot\Storage\ApplicationLock::for($basePath);
    }

    function flatcms_recovery_backup_reference(string $basePath): \FlatCMS\RecoverySnapshot\RecoveryBackupReference
    {
        flatcms_recovery_application_lock($basePath);
        foreach (['StreamFileTransaction', 'BackupSecretCipher', 'FullBackupService', 'RecoveryBackupReference'] as $class) {
            $path = $basePath . '/storage/recovery/runtime/' . $class . '.php';
            if (!is_file($path)) { throw new RuntimeException('recovery_runtime_missing'); }
            require_once $path;
        }
        return new \FlatCMS\RecoverySnapshot\RecoveryBackupReference($basePath);
    }

    function flatcms_recovery_headers(): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header("Content-Security-Policy: default-src 'none'; style-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
        header('Referrer-Policy: no-referrer');
    }

    function flatcms_recovery_not_found(): never
    {
        http_response_code(404); exit;
    }

    function flatcms_recovery_forbidden(): never
    {
        http_response_code(403); exit;
    }

    /** @param array<string,mixed> $state */
    function flatcms_recovery_render(array $state, string $mode, string $title, string $message, bool $button, bool $refresh = false, string $token = ''): never
    {
        flatcms_recovery_headers();
        if ($refresh) header('Refresh: 3');
        $from = htmlspecialchars((string) ($state['from_version'] ?? ''), ENT_QUOTES, 'UTF-8');
        $target = htmlspecialchars((string) ($state['target_version'] ?? ''), ENT_QUOTES, 'UTF-8');
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $messageEsc = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $badge = $mode === 'success' ? flatcms_recovery_t('recovery_badge_success') : ($mode === 'updating' ? flatcms_recovery_t('recovery_badge_updating') : ($mode === 'monitoring' ? flatcms_recovery_t('recovery_badge_monitoring') : flatcms_recovery_t('recovery_badge_recovery')));
        $csrf = $token !== '' ? hash_hmac('sha256', 'flatcms-recover', $token) : '';
        $action = htmlspecialchars((string) ($_SERVER['REQUEST_URI'] ?? '/recovery.php'), ENT_QUOTES, 'UTF-8');
        $localeEsc = htmlspecialchars(flatcms_recovery_locale(), ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="' . $localeEsc . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>' . $titleEsc . ' — FlatCMS</title><link rel="stylesheet" href="recovery.css"></head><body><main class="card">';
        echo '<span class="badge">' . htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') . '</span><div class="icon">' . ($mode === 'success' ? '✅' : ($mode === 'updating' ? '⏳' : ($mode === 'monitoring' ? '🛡️' : '🛟'))) . '</div>';
        echo '<h1>' . $titleEsc . '</h1><p>' . $messageEsc . '</p>';
        if ($from !== '' || $target !== '') echo '<div class="versions"><span>' . htmlspecialchars(flatcms_recovery_t('recovery_version_before'), ENT_QUOTES, 'UTF-8') . ' : <strong>' . $from . '</strong></span><span>' . htmlspecialchars(flatcms_recovery_t('recovery_version_target'), ENT_QUOTES, 'UTF-8') . ' : <strong>' . $target . '</strong></span></div>';
        if ($button) {
            echo '<form method="post" action="' . $action . '"><input type="hidden" name="recovery_csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '"><button class="btn" type="submit">' . htmlspecialchars(flatcms_recovery_t('recovery_action_restore'), ENT_QUOTES, 'UTF-8') . '</button></form>';
            echo '<p class="note">' . htmlspecialchars(flatcms_recovery_t('recovery_diagnostic_note'), ENT_QUOTES, 'UTF-8') . '</p>';
        } elseif ($mode === 'success') {
            echo '<p><a class="btn btn-link" href="/">' . htmlspecialchars(flatcms_recovery_t('recovery_action_return'), ENT_QUOTES, 'UTF-8') . '</a></p>';
        }
        echo '</main></body></html>'; exit;
    }
}
