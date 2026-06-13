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
require_once __DIR__ . '/../../app/Core/BaseController.php';
require_once __DIR__ . '/../../app/Modules/Modules/Controllers/AdminController.php';

use App\Modules\Modules\Controllers\AdminController;

$controller = (new ReflectionClass(AdminController::class))->newInstanceWithoutConstructor();
$validateZipEntries = reflected_method(AdminController::class, 'validateZipEntries');
$findModuleManifests = reflected_method(AdminController::class, 'findModuleManifests');
$hasUnsafeManifestPaths = reflected_method(AdminController::class, 'hasUnsafeManifestPaths');
$hasInvalidOfficialOrigin = reflected_method(AdminController::class, 'hasInvalidOfficialOrigin');
$hasInvalidNamespaceBoundary = reflected_method(AdminController::class, 'hasInvalidNamespaceBoundary');
$hasUnexpectedUnsignedSignature = reflected_method(AdminController::class, 'hasUnexpectedUnsignedSignature');
$verifyManifestFileIntegrity = reflected_method(AdminController::class, 'verifyManifestFileIntegrity');
$resolvePackageKind = reflected_method(AdminController::class, 'resolvePackageKind');
$readManifest = reflected_method(AdminController::class, 'readManifest');
$resolveModuleName = reflected_method(AdminController::class, 'resolveModuleName');

$tmpRoot = FlatCmsTest::tempDir('flatcms_installer_guards');

try {
    $safeZip = make_zip($tmpRoot . '/safe.zip', [
        'AuditExtension/extension.json' => '{"name":"AuditExtension"}',
        'AuditExtension/Config/routes.php' => "<?php\n",
        'AuditExtension/Assets/app.css' => '',
    ]);
    FlatCmsTest::assertTrue($validateZipEntries->invoke($controller, $safeZip), 'Safe package ZIP entries should be accepted.');
    $safeZip->close();

    foreach ([
        '../escape.php',
        '/absolute.php',
        'C:/absolute.php',
        'http://example.test/payload.php',
        'Audit/app/Modules/Evil/module.json',
        'Audit/app/Extensions/Evil/extension.json',
    ] as $entry) {
        $zip = make_zip($tmpRoot . '/' . sha1($entry) . '.zip', [$entry => 'x']);
        FlatCmsTest::assertFalse($validateZipEntries->invoke($controller, $zip), 'Unsafe ZIP entry must be rejected: ' . $entry);
        $zip->close();
    }

    $singleManifest = $tmpRoot . '/SingleManifest';
    mkdir($singleManifest, 0755, true);
    file_put_contents($singleManifest . '/extension.json', '{}');
    FlatCmsTest::assertSame(1, count($findModuleManifests->invoke($controller, $singleManifest)), 'Single manifest package should expose exactly one manifest.');

    $ambiguousManifest = $tmpRoot . '/AmbiguousManifest';
    mkdir($ambiguousManifest, 0755, true);
    file_put_contents($ambiguousManifest . '/extension.json', '{}');
    file_put_contents($ambiguousManifest . '/module.json', '{}');
    FlatCmsTest::assertSame(2, count($findModuleManifests->invoke($controller, $ambiguousManifest)), 'Ambiguous package should expose every manifest for rejection.');

    FlatCmsTest::assertSame('extension', $resolvePackageKind->invoke($controller, $singleManifest . '/extension.json'), 'extension.json should resolve to extension package kind.');
    FlatCmsTest::assertSame('module', $resolvePackageKind->invoke($controller, $ambiguousManifest . '/module.json'), 'module.json should resolve to module package kind.');

    $invalidJson = $tmpRoot . '/invalid.json';
    file_put_contents($invalidJson, '{');
    FlatCmsTest::assertSame([], $readManifest->invoke($controller, $invalidJson), 'Invalid manifest JSON should read as an empty invalid manifest.');
    FlatCmsTest::assertSame('AuditModule', $resolveModuleName->invoke($controller, ['name' => 'Audit Module!']), 'Manifest names should be sanitized for destination folders.');

    FlatCmsTest::assertTrue($hasUnsafeManifestPaths->invoke($controller, ['routes' => '../Config/routes.php']), 'Routes path escaping the package must be rejected.');
    FlatCmsTest::assertTrue($hasUnsafeManifestPaths->invoke($controller, ['assets' => '/Assets']), 'Absolute manifest paths must be rejected.');
    FlatCmsTest::assertFalse($hasUnsafeManifestPaths->invoke($controller, ['routes' => 'Config/routes.php', 'assets' => 'Assets']), 'Safe relative manifest paths should be accepted.');

    FlatCmsTest::assertTrue($hasInvalidOfficialOrigin->invoke($controller, [
        'origin' => 'community',
        'official' => true,
    ]), 'Community package cannot claim official=true.');
    FlatCmsTest::assertTrue($hasInvalidOfficialOrigin->invoke($controller, [
        'origin' => 'community',
        'vendor' => 'FlatCMS',
    ]), 'Community package cannot claim FlatCMS vendor.');

    $thirdPartyManifest = [
        'name' => 'AuditExtension',
        'key' => 'audit-extension',
        'origin' => 'extension',
        'official' => false,
    ];
    FlatCmsTest::assertTrue($hasUnexpectedUnsignedSignature->invoke(
        $controller,
        $thirdPartyManifest + ['signature' => 'unexpected'],
        'extension'
    ), 'Unsigned third-party extension cannot carry a signature field.');

    $extensionPackage = $tmpRoot . '/AuditExtension';
    mkdir($extensionPackage . '/Controllers', 0755, true);
    file_put_contents(
        $extensionPackage . '/Controllers/AdminController.php',
        "<?php\nnamespace App\\Modules\\AuditExtension\\Controllers;\n"
    );
    FlatCmsTest::assertTrue(
        $hasInvalidNamespaceBoundary->invoke($controller, $extensionPackage, $thirdPartyManifest, 'extension', 'AuditExtension'),
        'Extension package cannot declare its own App Modules namespace.'
    );

    file_put_contents(
        $extensionPackage . '/Controllers/AdminController.php',
        "<?php\nnamespace App\\Extensions\\AuditExtension\\Controllers;\nuse App\\Modules\\Auth\\Services\\RoleService;\n"
    );
    FlatCmsTest::assertFalse(
        $hasInvalidNamespaceBoundary->invoke($controller, $extensionPackage, $thirdPartyManifest, 'extension', 'AuditExtension'),
        'Extension package may depend on official module namespaces without owning App Modules code.'
    );

    $modulePackage = $tmpRoot . '/OfficialModule';
    mkdir($modulePackage . '/Controllers', 0755, true);
    file_put_contents(
        $modulePackage . '/Controllers/AdminController.php',
        "<?php\nnamespace App\\Extensions\\OfficialModule\\Controllers;\n"
    );
    FlatCmsTest::assertTrue(
        $hasInvalidNamespaceBoundary->invoke($controller, $modulePackage, ['name' => 'OfficialModule'], 'module', 'OfficialModule'),
        'Official module package cannot declare its own App Extensions namespace.'
    );

    $integrityPackage = $tmpRoot . '/IntegrityModule';
    mkdir($integrityPackage, 0755, true);
    file_put_contents($integrityPackage . '/payload.txt', 'trusted');
    $manifestPath = $integrityPackage . '/module.json';
    file_put_contents($manifestPath, '{}');
    $manifest = [
        'files' => [
            'payload.txt' => hash_file('sha256', $integrityPackage . '/payload.txt'),
        ],
    ];
    FlatCmsTest::assertTrue($verifyManifestFileIntegrity->invoke($controller, $manifest, $integrityPackage, $manifestPath), 'Valid signed files map should pass.');

    $badHashManifest = [
        'files' => [
            'payload.txt' => str_repeat('0', 64),
        ],
    ];
    FlatCmsTest::assertFalse($verifyManifestFileIntegrity->invoke($controller, $badHashManifest, $integrityPackage, $manifestPath), 'Invalid file hash should fail integrity validation.');

    file_put_contents($integrityPackage . '/extra.php', "<?php\n");
    FlatCmsTest::assertFalse($verifyManifestFileIntegrity->invoke($controller, $manifest, $integrityPackage, $manifestPath), 'Extra file outside signed files map should fail integrity validation.');
} finally {
    FlatCmsTest::removeTree($tmpRoot);
}

echo 'PASS: installer module and extension package guards' . PHP_EOL;

function reflected_method(string $className, string $methodName): ReflectionMethod
{
    $method = new ReflectionMethod($className, $methodName);
    $method->setAccessible(true);

    return $method;
}

/**
 * @param array<string, string> $entries
 */
function make_zip(string $path, array $entries): ZipArchive
{
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new FlatCmsTestFailure('Unable to create test ZIP: ' . $path);
    }

    foreach ($entries as $entry => $content) {
        $zip->addFromString($entry, $content);
    }

    return $zip;
}
