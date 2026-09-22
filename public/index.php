<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: public/index.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

/*
 * Disaster Recovery bridge.
 * The captured runtime uses its own namespace and does not autoload live Core
 * classes. Its journal lock is shared with the updater and recovery page.
 */
$flatcmsRecoveryStatePath = dirname(__DIR__) . '/storage/recovery/active.json';
if (is_file($flatcmsRecoveryStatePath)) {
    register_shutdown_function(static function () use ($flatcmsRecoveryStatePath): void {
        // The isolated CLI probe reports failure to its parent, which owns the journal lock.
        if (PHP_SAPI === 'cli' && defined('FLATCMS_RUNTIME_PROBE') && FLATCMS_RUNTIME_PROBE === true) { return; }
        $error = error_get_last();
        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
        if (!is_array($error) || !in_array((int) ($error['type'] ?? 0), $fatalTypes, true)) {
            return;
        }
        try {
            require_once dirname($flatcmsRecoveryStatePath) . '/runtime/recovery-runtime.php';
            $changed = false;
            flatcms_recovery_state_store(dirname(__DIR__))->mutate(static function (array $state) use ($error, &$changed): array {
                if (!in_array((string) ($state['status'] ?? ''), ['updating', 'monitoring', 'backup_ready'], true)) {
                    return $state;
                }
                $changed = true;
                $state['status'] = $state['status'] === 'monitoring' ? 'failed_post_update' : 'failed';
                $state['failed_at'] = gmdate('c');
                $state['error'] = 'fatal_php_after_update';
                $state['fatal'] = [
                    'type' => (int) ($error['type'] ?? 0),
                    'message' => (string) ($error['message'] ?? ''),
                    'file' => basename((string) ($error['file'] ?? '')),
                    'line' => (int) ($error['line'] ?? 0),
                ];
                return $state;
            });
            if (!$changed) { return; }
        } catch (Throwable) {
            // Preserve the original failure and journal if the capsule is unavailable.
            return;
        }
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $base = rtrim(dirname($scriptName), '/.');
        $recoveryUrl = ($base !== '' ? $base : '') . '/recovery.php';
        if (!headers_sent()) {
            header('Location: ' . $recoveryUrl, true, 302);
            return;
        }
        echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($recoveryUrl, ENT_QUOTES, 'UTF-8') . '">';
    });
}

// Admit before loading configuration, starting a session or reading canonical data.
try {
    foreach (['StorageException', 'StoragePathGuard', 'ApplicationLock'] as $class) {
        require_once dirname(__DIR__) . '/app/Core/Storage/' . $class . '.php';
    }
    \App\Core\Storage\ApplicationLock::for(dirname(__DIR__))->enter();
} catch (Throwable) {
    http_response_code(503);
    header('Retry-After: 5');
    header('Cache-Control: no-store');
    exit;
}

/**
 * Charge un fichier .env (format simple KEY=VALUE) dans $_ENV.
 */
function flatcms_load_env_file(string $path): void
{
    require_once dirname(__DIR__) . '/app/Core/EnvironmentFile.php';
    \App\Core\EnvironmentFile::load($path);
}

// Charger les variables d'environnement le plus tôt possible:
// 1) .env (base), 2) .env.local (override local admin)
flatcms_load_env_file(dirname(__DIR__) . '/.env');
flatcms_load_env_file(dirname(__DIR__) . '/.env.local');

/**
 * Retourne un chemin de requête normalisé.
 */
function flatcms_request_path(): string
{
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) parse_url($requestUri, PHP_URL_PATH);
    if ($path === '') {
        $path = '/';
    }

    $path = rawurldecode($path);
    $path = str_replace('\\', '/', $path);

    $segments = explode('/', $path);
    $normalized = [];
    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            array_pop($normalized);
            continue;
        }
        $normalized[] = $segment;
    }

    $cleanPath = '/' . implode('/', $normalized);
    return $cleanPath === '' ? '/' : $cleanPath;
}

/**
 * Vérifie si la requête cible un chemin sensible qui doit être masqué.
 */
function flatcms_is_sensitive_path(string $path): bool
{
    if ($path === '') {
        return false;
    }

    if (preg_match('#^/(data|storage|config)(/|$)#i', $path) === 1) {
        return true;
    }

    if (preg_match('#^/(vendor|\\.git|\\.svn)(/|$)#i', $path) === 1) {
        return true;
    }

    if (preg_match('#(^|/)\\.(?!well-known(/|$))#i', $path) === 1) {
        return true;
    }

    if (preg_match('#(^|/)(web\\.config|nginx\\.conf|composer\\.(json|lock)|\\.env(\\..*)?)$#i', $path) === 1) {
        return true;
    }

    if (preg_match('#\\.(bak|old|orig|save|swp|dist|example|backup(\\..*)?|md|log|lock)$#i', $path) === 1) {
        return true;
    }

    return false;
}

/**
 * Bloque les chemins sensibles au niveau applicatif avec une réponse neutre.
 */
function flatcms_block_sensitive_paths(): void
{
    $path = flatcms_request_path();
    if (!flatcms_is_sensitive_path($path)) {
        return;
    }

    if (!headers_sent()) {
        header_remove('X-Powered-By');
        header('Content-Type: text/html; charset=UTF-8');
    }
    http_response_code(404);
    exit;
}

/**
 * Détecte si la requête est HTTPS (direct ou via reverse proxy).
 */
function flatcms_is_secure_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwardedProto !== '' && in_array('https', array_map('trim', explode(',', $forwardedProto)), true)) {
        return true;
    }

    $requestScheme = strtolower((string) ($_SERVER['REQUEST_SCHEME'] ?? ''));
    if ($requestScheme === 'https') {
        return true;
    }

    $cfVisitor = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');
    if ($cfVisitor !== '') {
        $decoded = json_decode($cfVisitor, true);
        if (is_array($decoded) && strtolower((string) ($decoded['scheme'] ?? '')) === 'https') {
            return true;
        }
    }

    return false;
}

/**
 * Parse une option booléenne d'environnement.
 */
function flatcms_env_bool(string $value, bool $default): bool
{
    $v = strtolower(trim($value));
    if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    if (in_array($v, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }
    return $default;
}

/**
 * Retourne des sources CSP supplémentaires déclarées en environnement.
 *
 * Format accepté:
 * - séparation par espaces
 * - ou par virgules
 */
function flatcms_csp_env_sources(string $envName): array
{
    $raw = trim((string) ($_ENV[$envName] ?? ''));
    if ($raw === '') {
        return [];
    }

    $parts = preg_split('/[\s,]+/', $raw) ?: [];
    $sources = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        if ($part === '') {
            continue;
        }
        $sources[] = $part;
    }

    return array_values(array_unique($sources));
}

/**
 * Extrait une origine HTTP(S) utilisable dans une directive CSP.
 */
function flatcms_csp_url_origin(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $parts = parse_url($value);
    if (!is_array($parts)) {
        return '';
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));
    if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
        return '';
    }

    $origin = $scheme . '://' . $host;
    if (isset($parts['port']) && is_int($parts['port'])) {
        $origin .= ':' . $parts['port'];
    }

    return $origin;
}

/**
 * Retourne les sources CSP nécessaires aux intégrations analytics activées.
 *
 * @return array{script: string[], connect: string[], img: string[]}
 */
function flatcms_csp_analytics_sources(): array
{
    $sources = [
        'script' => [],
        'connect' => [],
        'img' => [],
    ];

    $googleEnabled = flatcms_env_bool((string) ($_ENV['GOOGLE_ANALYTICS_ENABLED'] ?? '0'), false);
    $googleMeasurementId = trim((string) ($_ENV['GOOGLE_ANALYTICS_MEASUREMENT_ID'] ?? ''));
    if ($googleEnabled && $googleMeasurementId !== '') {
        $sources['script'][] = 'https://www.googletagmanager.com';
        $sources['connect'][] = 'https://www.googletagmanager.com';
        $sources['connect'][] = 'https://www.google-analytics.com';
        $sources['connect'][] = 'https://*.google-analytics.com';
        $sources['connect'][] = 'https://stats.g.doubleclick.net';
        $sources['img'][] = 'https://www.google-analytics.com';
        $sources['img'][] = 'https://stats.g.doubleclick.net';
    }

    $matomoEnabled = flatcms_env_bool((string) ($_ENV['MATOMO_ENABLED'] ?? '0'), false);
    $matomoOrigin = flatcms_csp_url_origin((string) ($_ENV['MATOMO_BASE_URL'] ?? ''));
    if ($matomoEnabled && $matomoOrigin !== '') {
        $sources['script'][] = $matomoOrigin;
        $sources['connect'][] = $matomoOrigin;
        $sources['img'][] = $matomoOrigin;
    }

    foreach ($sources as $type => $values) {
        $sources[$type] = array_values(array_unique($values));
    }

    return $sources;
}

/**
 * Assemble une directive CSP.
 *
 * @param string[] $sources
 */
function flatcms_csp_directive(string $name, array $sources): string
{
    $clean = array_values(array_unique(array_filter(array_map('trim', $sources), static function ($value): bool {
        return $value !== '';
    })));

    return $name . ' ' . implode(' ', $clean);
}

/**
 * Retourne la CSP runtime compatible admin/frontend tout en restant plus stricte qu'un socle minimal.
 */
function flatcms_content_security_policy(): string
{
    $analyticsSources = flatcms_csp_analytics_sources();

    return implode('; ', [
        flatcms_csp_directive('default-src', ["'self'"]),
        flatcms_csp_directive('base-uri', ["'self'"]),
        flatcms_csp_directive('frame-ancestors', ["'self'"]),
        flatcms_csp_directive('form-action', ["'self'"]),
        flatcms_csp_directive('object-src', ["'none'"]),
        flatcms_csp_directive('script-src', array_merge([
            "'self'",
            'https://challenges.cloudflare.com',
            'https://static.axept.io',
            'https://cdn.tiny.cloud',
            'https://*.tiny.cloud',
        ], $analyticsSources['script'], flatcms_csp_env_sources('FLATCMS_CSP_SCRIPT_EXTRA'))),
        flatcms_csp_directive('style-src', array_merge([
            "'self'",
            "'unsafe-inline'",
            'https://fonts.axept.io',
            'https://fonts.googleapis.com',
            'https://cdn.tiny.cloud',
            'https://*.tiny.cloud',
        ], flatcms_csp_env_sources('FLATCMS_CSP_STYLE_EXTRA'))),
        flatcms_csp_directive('img-src', array_merge([
            "'self'",
            'data:',
            'blob:',
            'https:',
        ], $analyticsSources['img'], flatcms_csp_env_sources('FLATCMS_CSP_IMG_EXTRA'))),
        flatcms_csp_directive('font-src', array_merge([
            "'self'",
            'data:',
            'https://fonts.axept.io',
            'https://fonts.gstatic.com',
            'https://cdn.tiny.cloud',
            'https://*.tiny.cloud',
        ], flatcms_csp_env_sources('FLATCMS_CSP_FONT_EXTRA'))),
        flatcms_csp_directive('connect-src', array_merge([
            "'self'",
            'blob:',
            'https://challenges.cloudflare.com',
            'https://static.axept.io',
            'https://*.axept.io',
            'https://*.axeptio.eu',
            'https://*.axeptio.tech',
            'https://cdn.tiny.cloud',
            'https://*.tiny.cloud',
        ], $analyticsSources['connect'], flatcms_csp_env_sources('FLATCMS_CSP_CONNECT_EXTRA'))),
        flatcms_csp_directive('frame-src', array_merge([
            "'self'",
            'https://challenges.cloudflare.com',
            'https://static.axept.io',
            'https://*.axept.io',
            'https://www.google.com',
            'https://maps.google.com',
        ], flatcms_csp_env_sources('FLATCMS_CSP_FRAME_EXTRA'))),
        flatcms_csp_directive('media-src', array_merge([
            "'self'",
            'data:',
            'blob:',
            'https:',
        ], flatcms_csp_env_sources('FLATCMS_CSP_MEDIA_EXTRA'))),
        flatcms_csp_directive('manifest-src', array_merge([
            "'self'",
        ], flatcms_csp_env_sources('FLATCMS_CSP_MANIFEST_EXTRA'))),
        flatcms_csp_directive('worker-src', array_merge([
            "'self'",
            'blob:',
        ], flatcms_csp_env_sources('FLATCMS_CSP_WORKER_EXTRA'))),
    ]);
}

/**
 * Applique un socle de sécurité HTTP + durcissement cookie de session.
 */
function flatcms_bootstrap_security(): void
{
    $isSecure = flatcms_is_secure_request();

    $sameSite = ucfirst(strtolower((string) ($_ENV['SESSION_SAMESITE'] ?? 'Lax')));
    if (!in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
        $sameSite = 'Lax';
    }

    $cookieSecureMode = strtolower((string) ($_ENV['SESSION_COOKIE_SECURE'] ?? 'auto'));
    if ($cookieSecureMode === 'auto') {
        $cookieSecure = $isSecure;
    } else {
        $cookieSecure = flatcms_env_bool($cookieSecureMode, $isSecure);
    }

    $cookieHttpOnly = flatcms_env_bool((string) ($_ENV['SESSION_COOKIE_HTTPONLY'] ?? '1'), true);
    $cookieName = trim((string) ($_ENV['SESSION_COOKIE_NAME'] ?? 'flatcms_session'));
    if ($cookieName === '') {
        $cookieName = 'flatcms_session';
    }
    session_name($cookieName);

    $lifetimeMinutes = (int) ($_ENV['SESSION_LIFETIME'] ?? 120);
    if ($lifetimeMinutes <= 0) {
        $lifetimeMinutes = 120;
    }
    $lifetime = $lifetimeMinutes * 60;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_secure', $cookieSecure ? '1' : '0');
    ini_set('session.cookie_httponly', $cookieHttpOnly ? '1' : '0');
    ini_set('session.cookie_samesite', $sameSite);

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'domain' => '',
        'secure' => $cookieSecure,
        'httponly' => $cookieHttpOnly,
        'samesite' => $sameSite,
    ]);

    if (!headers_sent()) {
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()');
        header('Content-Security-Policy: ' . flatcms_content_security_policy());
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('X-Download-Options: noopen');

        if ($isSecure) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
}

flatcms_block_sensitive_paths();
flatcms_bootstrap_security();

// Démarrer la session
session_start();

// Définir les constantes de base
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('DATA_PATH', BASE_PATH . '/data');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('CONFIG_PATH', BASE_PATH . '/config');

// Détecter l'URL publique automatiquement
$protocol = flatcms_is_secure_request() ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$scriptName = ($scriptName === '/' || $scriptName === '\\') ? '' : $scriptName;
define('PUBLIC_URL', $protocol . $host . $scriptName);

// ============================================
// AUTO-DÉTECTION : Installation nécessaire ?
// ============================================
$lockFile = DATA_PATH . '/installed.lock';

if (!file_exists($lockFile)) {
    // ========================================
    // MODE INSTALLATION
    // ========================================

    // L'installateur utilise aussi les helpers globaux (json_write, json_read, etc.).
    require_once APP_PATH . '/Bootstrap/Autoloader.php';

    // Lancer l'installateur
    $installer = new \App\Modules\Install\Controllers\InstallController();
    $installer->handle($_SERVER['REQUEST_URI'] ?? '/');
    exit;
}

// ============================================
// MODE NORMAL : Application installée
// ============================================

// Choisir l'autoloader (Composer OU natif)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    // OPTION 1 : Utiliser Composer si disponible (pour développeurs)
    require BASE_PATH . '/vendor/autoload.php';
} else {
    // OPTION 2 : Utiliser l'autoloader natif (pour production sans Composer)
    require_once APP_PATH . '/Bootstrap/Autoloader.php';
}

// Bootstrapper l'application
$app = require_once APP_PATH . '/Bootstrap/app.php';
$app->run();
