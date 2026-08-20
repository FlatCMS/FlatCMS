<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

$basePath = dirname(__DIR__, 4);
define('BASE_PATH', $basePath);
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('DATA_PATH', BASE_PATH . '/data');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('CONFIG_PATH', BASE_PATH . '/config');

require APP_PATH . '/Bootstrap/Autoloader.php';
date_default_timezone_set((string) env('APP_TIMEZONE', 'Europe/Paris'));
\App\Core\I18n::init((string) env('APP_LOCALE', 'fr-FR'));

$catalog = (string) ($argv[1] ?? '');
$slug = (string) ($argv[2] ?? '');
$version = (string) ($argv[3] ?? '');

try {
    $result = (new \App\Modules\UpdateManager\Services\UpdateApplyService())->apply($catalog, $slug, $version);
    fwrite(STDOUT, '__FLATCMS_RESULT__' . json_encode(['ok' => true, 'result' => $result], JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(0);
} catch (\Throwable $exception) {
    fwrite(STDERR, '__FLATCMS_ERROR__' . json_encode(['ok' => false, 'error' => $exception->getMessage()], JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(3);
}
