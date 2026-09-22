<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/RuntimeProbe/worker.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

// This private CLI adapter is also captured verbatim by the recovery capsule.
if (PHP_SAPI !== 'cli' || !in_array(count($argv), [3, 4], true)) { exit(2); }
$root = realpath($argv[1]);
if (!is_string($root)) { exit(2); }
$recoverScopes = [];
if (($argv[3] ?? '') !== '') {
    $recoverScopes = array_values(array_unique(explode(',', $argv[3])));
    foreach ($recoverScopes as $scope) {
        if (!in_array($scope, ['core-update', 'full-restoration', 'site-restoration'], true)) { exit(2); }
    }
}
foreach (['StorageException', 'StoragePathGuard', 'ApplicationLock'] as $class) {
    require_once $root . '/app/Core/Storage/' . $class . '.php';
}
try { \App\Core\Storage\ApplicationLock::for($root)->inheritProbeLease($recoverScopes); }
catch (Throwable) { exit(2); }
// Only set after inheriting verified OS locks; no token, environment or HTTP switch.
define('FLATCMS_RUNTIME_PROBE', true);

$paths = new \App\Core\Storage\StoragePathGuard($root);
$settings = json_decode((string) file_get_contents($paths->resolve('data/settings.json')), true, 512, JSON_THROW_ON_ERROR);
$siteUrl = parse_url((string) ($settings['site_url'] ?? ''));
if (!is_array($siteUrl) || !in_array($siteUrl['scheme'] ?? '', ['http', 'https'], true)
    || empty($siteUrl['host']) || !is_file($paths->resolve('data/installed.lock'))) { exit(2); }
$prefix = rtrim((string) ($siteUrl['path'] ?? ''), '/');
$secure = $siteUrl['scheme'] === 'https';
$_SERVER = [
    'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $prefix . '/login',
    'SCRIPT_NAME' => $prefix . '/index.php', 'SCRIPT_FILENAME' => $root . '/public/index.php',
    'DOCUMENT_ROOT' => $root . '/public', 'SERVER_NAME' => $siteUrl['host'],
    'HTTP_HOST' => $siteUrl['host'] . (isset($siteUrl['port']) ? ':' . $siteUrl['port'] : ''),
    'SERVER_PORT' => (string) ($siteUrl['port'] ?? ($secure ? 443 : 80)),
    'HTTPS' => $secure ? 'on' : 'off', 'REQUEST_SCHEME' => $siteUrl['scheme'],
    'REMOTE_ADDR' => '127.0.0.1', 'HTTP_ACCEPT' => 'text/html',
];
$_COOKIE = $_GET = $_POST = $_REQUEST = $_FILES = [];
session_set_save_handler(new class implements SessionHandlerInterface {
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string|false { return ''; }
    public function write(string $id, string $data): bool { return true; }
    public function destroy(string $id): bool { return true; }
    public function gc(int $max_lifetime): int|false { return 0; }
}, true);

$html = '';
$overflow = false;
$returned = false;
http_response_code(200);
ob_start(static function (string $chunk) use (&$html, &$overflow): string {
    if (strlen($html) + strlen($chunk) > 2097152) { $overflow = true; }
    if (!$overflow) { $html .= $chunk; }
    return '';
}, 8192);
register_shutdown_function(static function () use (&$html, &$overflow, &$returned, $argv, $root): void {
    while (ob_get_level() > 0) { ob_end_flush(); }
    $error = error_get_last();
    $fatal = is_array($error) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR], true);
    $version = function_exists('flatcms_version') ? flatcms_version() : '';
    $ok = false;
    if ($returned && !$fatal && !$overflow && http_response_code() === 200
        && class_exists('App\\Core\\App', false) && $version === $argv[2]
        && trim((string) file_get_contents($root . '/VERSION')) === $argv[2] && class_exists('DOMDocument')) {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            if ($document->loadHTML($html, LIBXML_NONET)) {
                $xpath = new DOMXPath($document);
                $ok = $xpath->query('//form[translate(@method,"POST","post")="post"]'
                    . '[.//input[@type="password" and @name="password"]]'
                    . '[.//input[@name="email"]][.//input[@name="_token" and string-length(@value)>0]]')->length > 0;
            }
        } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    }
    // Never return page HTML, tokens, session content or exception details to the parent.
    fwrite(STDOUT, json_encode(['protocol' => 1, 'ok' => $ok, 'version' => $version,
        'status' => http_response_code(), 'returned' => $returned, 'fatal' => $fatal, 'overflow' => $overflow], JSON_THROW_ON_ERROR));
});
require $root . '/public/index.php';
$returned = true;
