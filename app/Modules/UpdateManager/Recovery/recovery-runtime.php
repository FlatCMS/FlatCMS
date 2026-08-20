<?php
/** FlatCMS standalone recovery runtime. No Core/bootstrap dependency. */
declare(strict_types=1);

if (!function_exists('flatcms_recovery_run')) {
    function flatcms_recovery_run(string $basePath): void
    {
        $basePath = rtrim($basePath, '/\\');
        $recoveryRoot = $basePath . '/storage/recovery';
        $statePath = $recoveryRoot . '/active.json';
        $runtimeService = $recoveryRoot . '/runtime/FullBackupService.php';
        $state = flatcms_recovery_read_json($statePath);
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
            if ((int) ($state['monitor_until'] ?? 0) < time()) {
                @unlink($statePath);
                setcookie($cookieName, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
                flatcms_recovery_not_found();
            }
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

        $hasBackup = is_file((string) ($state['full_backup_path'] ?? ''));
        $message = !empty($state['auto_rollback_succeeded'])
            ? flatcms_recovery_t('recovery_rollback_done_message')
            : flatcms_recovery_t('recovery_failure_message');
        flatcms_recovery_render($state, 'failed', flatcms_recovery_t('recovery_failure_title'), $message, $hasBackup, false, $token);
    }

    /** @param array<string,mixed> $state */
    function flatcms_recovery_restore(string $basePath, string $statePath, string $runtimeService, array $state): void
    {
        $lockPath = dirname($statePath) . '/recovery.lock';
        $lock = @fopen($lockPath, 'c+');
        if (!is_resource($lock) || !flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) fclose($lock);
            flatcms_recovery_render($state, 'failed', flatcms_recovery_t('recovery_in_progress_title'), flatcms_recovery_t('recovery_in_progress_message'), false);
        }
        try {
            $archive = (string) ($state['full_backup_path'] ?? '');
            $keyPath = (string) ($state['full_backup_key_path'] ?? '');
            $expectedSha = strtolower(trim((string) ($state['full_backup_sha256'] ?? '')));
            if (!is_file($archive) || !is_file($keyPath) || $expectedSha === '') {
                throw new RuntimeException('recovery_backup_missing');
            }
            $actualSha = strtolower((string) hash_file('sha256', $archive));
            if (!hash_equals($expectedSha, $actualSha)) {
                throw new RuntimeException('recovery_backup_hash_mismatch');
            }
            if (!is_file($runtimeService)) {
                throw new RuntimeException('recovery_runtime_missing');
            }
            require_once $runtimeService;
            if (!class_exists('App\\Modules\\Backups\\Services\\FullBackupService')) {
                throw new RuntimeException('recovery_runtime_invalid');
            }

            $state['status'] = 'restoring';
            $state['restore_started_at'] = gmdate('c');
            $state['updated_at'] = gmdate('c');
            flatcms_recovery_write_json($statePath, $state);

            $failedRoot = $basePath . '/storage/backups/recovery/failed-state';
            $keyRoot = $basePath . '/storage/recovery/keys';
            $service = new \App\Modules\Backups\Services\FullBackupService($basePath, $failedRoot, $keyRoot);
            $failed = $service->createBackup([
                'reason' => 'failed_update_state',
                'created_by' => 'RecoveryCapsule',
                'target_version' => (string) ($state['target_version'] ?? ''),
                'include_diagnostics' => true,
            ]);
            $state['failed_state_backup_path'] = (string) ($failed['path'] ?? '');
            $state['failed_state_backup_key_path'] = (string) ($failed['key_path'] ?? '');
            $state['failed_state_backup_sha256'] = (string) ($failed['sha256'] ?? '');
            $state['failed_state_created_at'] = gmdate('c');
            flatcms_recovery_write_json($statePath, $state);

            $result = $service->restoreBackupTo($archive, $basePath, $keyPath);
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
            flatcms_recovery_render($state, 'success', flatcms_recovery_t('recovery_success_title'), flatcms_recovery_t('recovery_success_diagnostic_message'), false);
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
        foreach (['public/index.php', 'app/Core/App.php', 'data/settings.json', 'VERSION'] as $relative) {
            if (!is_file($basePath . '/' . $relative)) return false;
        }
        $raw = trim((string) @file_get_contents($basePath . '/VERSION'));
        if ($expectedVersion !== '' && $expectedVersion !== 'unknown' && !str_contains($raw, $expectedVersion)) return false;
        $settings = json_decode((string) @file_get_contents($basePath . '/data/settings.json'), true);
        return is_array($settings);
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
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) throw new RuntimeException('recovery_state_encode_failed');
        $tmp = $path . '.tmp';
        if (@file_put_contents($tmp, $json, LOCK_EX) === false || !@rename($tmp, $path)) {
            @unlink($tmp); throw new RuntimeException('recovery_state_write_failed');
        }
        @chmod($path, 0640);
    }

    function flatcms_recovery_headers(): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
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
        $failedName = basename((string) ($state['failed_state_backup_path'] ?? ''));
        $localeEsc = htmlspecialchars(flatcms_recovery_locale(), ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="' . $localeEsc . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>' . $titleEsc . ' — FlatCMS</title><style>';
        echo ':root{color-scheme:dark}*{box-sizing:border-box}body{margin:0;background:#0f172a;color:#e2e8f0;font:16px/1.55 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;min-height:100vh;display:grid;place-items:center;padding:24px}.card{width:min(720px,100%);background:#111c33;border:1px solid #334155;border-radius:24px;padding:34px;box-shadow:0 28px 80px #02061799}.badge{display:inline-block;padding:7px 12px;border-radius:999px;background:#312e81;color:#c7d2fe;font-weight:700;font-size:13px}.icon{font-size:42px;margin:22px 0 8px}h1{font-size:clamp(28px,5vw,44px);line-height:1.08;margin:8px 0 16px;color:white}p{color:#cbd5e1}.versions{display:flex;gap:12px;flex-wrap:wrap;margin:24px 0}.versions span{background:#0b1220;border:1px solid #334155;padding:10px 13px;border-radius:12px}.btn{appearance:none;border:0;border-radius:14px;background:#4f46e5;color:white;font-weight:800;font-size:17px;padding:15px 20px;cursor:pointer;width:100%}.btn:hover{filter:brightness(1.08)}.note{font-size:13px;color:#94a3b8;margin-top:18px}.ok{color:#86efac}.warn{color:#fcd34d}</style></head><body><main class="card">';
        echo '<span class="badge">' . htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') . '</span><div class="icon">' . ($mode === 'success' ? '✅' : ($mode === 'updating' ? '⏳' : ($mode === 'monitoring' ? '🛡️' : '🛟'))) . '</div>';
        echo '<h1>' . $titleEsc . '</h1><p>' . $messageEsc . '</p>';
        if ($from !== '' || $target !== '') echo '<div class="versions"><span>' . htmlspecialchars(flatcms_recovery_t('recovery_version_before'), ENT_QUOTES, 'UTF-8') . ' : <strong>' . $from . '</strong></span><span>' . htmlspecialchars(flatcms_recovery_t('recovery_version_target'), ENT_QUOTES, 'UTF-8') . ' : <strong>' . $target . '</strong></span></div>';
        if ($button) {
            echo '<form method="post" action="' . $action . '"><input type="hidden" name="recovery_csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '"><button class="btn" type="submit">' . htmlspecialchars(flatcms_recovery_t('recovery_action_restore'), ENT_QUOTES, 'UTF-8') . '</button></form>';
            echo '<p class="note">' . htmlspecialchars(flatcms_recovery_t('recovery_diagnostic_note'), ENT_QUOTES, 'UTF-8') . '</p>';
        } elseif ($mode === 'success') {
            echo '<p><a class="btn" style="display:block;text-align:center;text-decoration:none" href="/">' . htmlspecialchars(flatcms_recovery_t('recovery_action_return'), ENT_QUOTES, 'UTF-8') . '</a></p>';
            if ($failedName !== '') echo '<p class="note">' . htmlspecialchars(flatcms_recovery_t('recovery_failed_state_note', ['archive' => $failedName]), ENT_QUOTES, 'UTF-8') . '</p>';
        }
        echo '</main></body></html>'; exit;
    }
}
