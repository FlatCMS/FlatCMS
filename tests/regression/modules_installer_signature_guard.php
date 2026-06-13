<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../app/Core/BaseController.php';
require_once __DIR__ . '/../../app/Core/ModuleManager.php';
require_once __DIR__ . '/../../app/Modules/Modules/Controllers/AdminController.php';

use App\Core\ModuleManager;
use App\Modules\Modules\Controllers\AdminController;

$controller = (new ReflectionClass(AdminController::class))->newInstanceWithoutConstructor();
$requiresOfficialSignature = new ReflectionMethod(AdminController::class, 'requiresOfficialSignature');
$requiresOfficialSignature->setAccessible(true);
$hasUnexpectedUnsignedSignature = new ReflectionMethod(AdminController::class, 'hasUnexpectedUnsignedSignature');
$hasUnexpectedUnsignedSignature->setAccessible(true);
$findModuleManifests = new ReflectionMethod(AdminController::class, 'findModuleManifests');
$findModuleManifests->setAccessible(true);
$hasUnsafeManifestPaths = new ReflectionMethod(AdminController::class, 'hasUnsafeManifestPaths');
$hasUnsafeManifestPaths->setAccessible(true);
$hasInvalidNamespaceBoundary = new ReflectionMethod(AdminController::class, 'hasInvalidNamespaceBoundary');
$hasInvalidNamespaceBoundary->setAccessible(true);
$verifyManifestFileIntegrity = new ReflectionMethod(AdminController::class, 'verifyManifestFileIntegrity');
$verifyManifestFileIntegrity->setAccessible(true);

$moduleManager = (new ReflectionClass(ModuleManager::class))->newInstanceWithoutConstructor();
$resolveManifestContract = new ReflectionMethod(ModuleManager::class, 'resolveManifestContract');
$resolveManifestContract->setAccessible(true);

$thirdPartyManifest = [
    'name' => 'AuditModule',
    'vendor' => 'ThirdPartyVendor',
    'origin' => 'extension',
    'official' => false,
];

$officialManifest = [
    'name' => 'OfficialModule',
    'vendor' => 'FlatCMS',
    'origin' => 'flatcms',
    'official' => true,
];

$signedThirdPartyManifest = $thirdPartyManifest + [
    'signature' => 'not-an-official-signature',
];

$tmpRoot = sys_get_temp_dir() . '/flatcms_modules_installer_guard_' . bin2hex(random_bytes(6));
mkdir($tmpRoot, 0755, true);

$ambiguousPackage = $tmpRoot . '/AmbiguousPackage';
mkdir($ambiguousPackage, 0755, true);
file_put_contents($ambiguousPackage . '/extension.json', '{}');
file_put_contents($ambiguousPackage . '/module.json', '{}');

$badNamespacePackage = $tmpRoot . '/AuditModule';
mkdir($badNamespacePackage . '/Controllers', 0755, true);
file_put_contents(
    $badNamespacePackage . '/Controllers/AdminController.php',
    "<?php\nnamespace App\\Modules\\AuditModule\\Controllers;\n"
);

$integrityPackage = $tmpRoot . '/IntegrityModule';
mkdir($integrityPackage, 0755, true);
file_put_contents($integrityPackage . '/payload.txt', 'trusted');
$integrityManifestPath = $integrityPackage . '/module.json';
file_put_contents($integrityManifestPath, '{}');
$integrityManifest = [
    'files' => [
        'payload.txt' => 'sha256:' . hash_file('sha256', $integrityPackage . '/payload.txt'),
    ],
];

$extensionRuntimeDir = $tmpRoot . '/RuntimeExtension';
mkdir($extensionRuntimeDir, 0755, true);
file_put_contents($extensionRuntimeDir . '/module.json', '{}');

$moduleRuntimeDir = $tmpRoot . '/RuntimeModule';
mkdir($moduleRuntimeDir, 0755, true);
file_put_contents($moduleRuntimeDir . '/extension.json', '{}');

$extensionFallback = $resolveManifestContract->invoke($moduleManager, $extensionRuntimeDir, 'extension');
$moduleFallback = $resolveManifestContract->invoke($moduleManager, $moduleRuntimeDir, 'module');

$assertions = [
    'third-party module.json requires official signature' => $requiresOfficialSignature->invoke($controller, $thirdPartyManifest, 'module') === true,
    'third-party extension.json remains unsigned extension' => $requiresOfficialSignature->invoke($controller, $thirdPartyManifest, 'extension') === false,
    'official extension metadata still requires signature' => $requiresOfficialSignature->invoke($controller, $officialManifest, 'extension') === true,
    'third-party extension.json cannot carry FlatCMS signature field' => $hasUnexpectedUnsignedSignature->invoke($controller, $signedThirdPartyManifest, 'extension') === true,
    'ambiguous packages expose every manifest for rejection' => count($findModuleManifests->invoke($controller, $ambiguousPackage)) === 2,
    'manifest runtime paths cannot escape package' => $hasUnsafeManifestPaths->invoke($controller, ['routes' => '../Config/routes.php']) === true,
    'extension package cannot declare App Modules namespace' => $hasInvalidNamespaceBoundary->invoke($controller, $badNamespacePackage, $thirdPartyManifest, 'extension', 'AuditModule') === true,
    'signed file integrity map verifies package files' => $verifyManifestFileIntegrity->invoke($controller, $integrityManifest, $integrityPackage, $integrityManifestPath) === true,
    'extension runtime refuses module.json fallback' => $extensionFallback[2] === 'missing' && $extensionFallback[1] === 'extension.json',
    'module runtime refuses extension.json fallback' => $moduleFallback[2] === 'missing' && $moduleFallback[1] === 'module.json',
];

foreach ($assertions as $label => $passed) {
    if (!$passed) {
        remove_tree($tmpRoot);
        fwrite(STDERR, 'FAIL: ' . $label . PHP_EOL);
        exit(1);
    }
}

file_put_contents($integrityPackage . '/extra.php', "<?php\n");
if ($verifyManifestFileIntegrity->invoke($controller, $integrityManifest, $integrityPackage, $integrityManifestPath) !== false) {
    remove_tree($tmpRoot);
    fwrite(STDERR, 'FAIL: signed file integrity map rejects extra files' . PHP_EOL);
    exit(1);
}

remove_tree($tmpRoot);
echo 'PASS: modules installer signature guard' . PHP_EOL;

function remove_tree(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
            continue;
        }

        unlink($item->getPathname());
    }

    rmdir($path);
}
