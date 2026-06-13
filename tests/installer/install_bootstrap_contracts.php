<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

require_once __DIR__ . '/../support/TestHarness.php';

if (!defined('BASE_PATH')) {
    define('BASE_PATH', FlatCmsTest::repositoryRoot());
}
if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}

require_once BASE_PATH . '/app/Bootstrap/Autoloader.php';

$controllerPath = BASE_PATH . '/app/Modules/Install/Controllers/InstallController.php';
$publicIndex = (string) file_get_contents(BASE_PATH . '/public/index.php');
$controllerSource = (string) file_get_contents($controllerPath);

FlatCmsTest::assertFileExists($controllerPath, 'Installer controller should live in the namespaced Controllers directory.');
FlatCmsTest::assertTrue(
    class_exists(\App\Modules\Install\Controllers\InstallController::class),
    'Installer controller should autoload from App Modules namespace.'
);
FlatCmsTest::assertTrue(
    str_contains($publicIndex, '\App\Modules\Install\Controllers\InstallController'),
    'Fresh install bootstrap should instantiate the namespaced installer controller.'
);
FlatCmsTest::assertFalse(
    str_contains($controllerSource, 'Content-Security-Policy'),
    'Installer-generated server configs should not emit a static CSP that conflicts with runtime analytics sources.'
);
FlatCmsTest::assertFalse(
    str_contains($controllerSource, 'SECURITY_CSP'),
    'Installer should not keep a static CSP constant after runtime CSP became dynamic.'
);

echo 'PASS: install bootstrap contracts' . PHP_EOL;
