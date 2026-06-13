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

$generatorRoot = getenv('FLATCMS_MODULE_STARTER_PATH') ?: '/Applications/MAMP/htdocs/flatcms-module-starter';
if (!is_dir($generatorRoot)) {
    echo 'PASS: module starter generator path not available' . PHP_EOL;
    return;
}

FlatCmsTest::assertFileExists($generatorRoot . '/src/SimpleZip.php', 'Module Starter SimpleZip source must exist.');
FlatCmsTest::assertFileExists($generatorRoot . '/src/ModuleValidator.php', 'Module Starter validator source must exist.');
FlatCmsTest::assertFileExists($generatorRoot . '/src/ModuleGenerator.php', 'Module Starter generator source must exist.');
FlatCmsTest::assertTrue(class_exists(ZipArchive::class), 'ZipArchive is required for generator contract extraction.');

require_once $generatorRoot . '/src/SimpleZip.php';
require_once $generatorRoot . '/src/ModuleValidator.php';
require_once $generatorRoot . '/src/ModuleGenerator.php';

$validator = new FlatCMS\ModuleStarter\ModuleValidator();
$result = $validator->validate([
    'name' => 'Audit Extension',
    'slug' => 'audit-extension',
    'class_name' => 'AuditExtension',
    'description' => 'Extension tierce de test.',
    'version' => '1.0.0-beta.1',
    'author' => 'FlatCMS Test',
    'vendor' => 'Community',
    'category' => 'utility',
    'scope' => 'mixed',
    'admin_route' => 'audit-extension',
    'front_route' => 'audit-extension',
    'sidebar_enabled' => '1',
    'menu_label' => 'Audit Extension',
    'menu_icon' => 'fas fa-puzzle-piece',
    'permission' => 'audit-extension.view',
    'priority' => '50',
    'components' => ['service', 'storage', 'routes', 'hooks', 'css', 'js', 'readme'],
    'dependencies' => ['Core', 'Auth'],
    'roles' => ['super_admin', 'admin'],
]);

FlatCmsTest::assertSame([], $result['errors'], 'Module Starter input should validate.');
FlatCmsTest::assertSame('App\\Extensions\\AuditExtension', $result['data']['namespace'] ?? null, 'Module Starter must produce an App Extensions namespace.');
FlatCmsTest::assertFalse(array_key_exists('signature', $result['data']), 'Third-party generator data must not carry a signature placeholder.');

$generator = new FlatCMS\ModuleStarter\ModuleGenerator();
$archive = $generator->generate($result['data']);
$extractRoot = FlatCmsTest::tempDir('flatcms_generator_contract');

try {
    $zip = new ZipArchive();
    FlatCmsTest::assertTrue($zip->open($archive['path']) === true, 'Generated ZIP must open.');
    FlatCmsTest::assertTrue($zip->extractTo($extractRoot), 'Generated ZIP must extract.');
    $zip->close();

    $packageRoot = $extractRoot . '/AuditExtension';
    FlatCmsTest::assertDirectoryExists($packageRoot, 'Generated ZIP must contain a single extension root.');
    FlatCmsTest::assertFileExists($packageRoot . '/extension.json', 'Third-party generator must produce extension.json.');
    FlatCmsTest::assertFalse(is_file($packageRoot . '/module.json'), 'Third-party generator must never produce module.json.');

    $manifest = FlatCmsTest::assertJsonFile($packageRoot . '/extension.json', 'Generated extension manifest must be valid JSON.');
    FlatCmsTest::assertSame(false, $manifest['official'] ?? null, 'Generated extension must declare official=false.');
    FlatCmsTest::assertSame('extension', $manifest['origin'] ?? null, 'Generated extension must declare origin=extension.');
    FlatCmsTest::assertSame(false, $manifest['required'] ?? null, 'Generated extension must declare required=false.');
    FlatCmsTest::assertFalse(array_key_exists('signature', $manifest), 'Generated third-party extension manifest must not carry a signature field.');
    FlatCmsTest::assertSame('Config/routes.php', $manifest['routes'] ?? null, 'Generated extension manifest must declare routes.');
    FlatCmsTest::assertSame('Hooks/listeners.php', $manifest['hooks'] ?? null, 'Generated extension manifest must declare hooks.');
    FlatCmsTest::assertSame('Assets', $manifest['assets'] ?? null, 'Generated extension manifest must declare assets.');
    FlatCmsTest::assertSame('Languages', $manifest['languages'] ?? null, 'Generated extension manifest must declare languages.');

    $controller = (new ReflectionClass(AdminController::class))->newInstanceWithoutConstructor();
    FlatCmsTest::assertSame(1, count(reflected_method(AdminController::class, 'findModuleManifests')->invoke($controller, $packageRoot)), 'Generated package must expose exactly one manifest.');
    FlatCmsTest::assertSame('extension', reflected_method(AdminController::class, 'resolvePackageKind')->invoke($controller, $packageRoot . '/extension.json'), 'Generated package must resolve as an extension.');
    FlatCmsTest::assertFalse(reflected_method(AdminController::class, 'hasUnsafeManifestPaths')->invoke($controller, $manifest), 'Generated manifest paths must be safe.');
    FlatCmsTest::assertFalse(reflected_method(AdminController::class, 'hasInvalidOfficialOrigin')->invoke($controller, $manifest), 'Generated extension must not claim official FlatCMS origin.');
    FlatCmsTest::assertFalse(reflected_method(AdminController::class, 'hasUnexpectedUnsignedSignature')->invoke($controller, $manifest, 'extension'), 'Generated unsigned extension must not carry a signature.');
    FlatCmsTest::assertFalse(reflected_method(AdminController::class, 'hasInvalidNamespaceBoundary')->invoke($controller, $packageRoot, $manifest, 'extension', 'AuditExtension'), 'Generated extension must stay inside App Extensions namespace.');

    $phpSources = implode("\n", array_map(
        static fn(string $file): string => (string) file_get_contents($file),
        glob($packageRoot . '/{Config,Controllers,Hooks,Services,Views}/{*,*/*}.php', GLOB_BRACE) ?: []
    ));

    FlatCmsTest::assertTrue(str_contains($phpSources, 'App\\Extensions\\AuditExtension'), 'Generated PHP must use App Extensions namespace.');
    FlatCmsTest::assertFalse(str_contains($phpSources, 'namespace App\\Modules\\AuditExtension'), 'Generated PHP must not declare App Modules namespace.');
    FlatCmsTest::assertFalse(str_contains($phpSources, 'use App\\Modules\\AuditExtension'), 'Generated PHP must not import its own App Modules namespace.');
    FlatCmsTest::assertTrue(str_contains($phpSources, 'auth.permissions.extend'), 'Generated hooks must declare permissions through hooks.');
    FlatCmsTest::assertTrue(str_contains($phpSources, 'auth.menus.extend'), 'Generated hooks must declare sidebar through hooks.');
    FlatCmsTest::assertTrue(str_contains($phpSources, 'audit-extension.view'), 'Generated hooks/controllers must keep the declared permission.');

    validate_generated_languages($packageRoot . '/Languages');
} finally {
    FlatCmsTest::removeTree($extractRoot);
    if (is_string($archive['cleanup_dir'] ?? null)) {
        $generator->removeDirectory($archive['cleanup_dir']);
    }
}

echo 'PASS: module starter generator contracts' . PHP_EOL;

function reflected_method(string $className, string $methodName): ReflectionMethod
{
    $method = new ReflectionMethod($className, $methodName);
    $method->setAccessible(true);

    return $method;
}

function validate_generated_languages(string $languageRoot): void
{
    $expectedLocales = ['fr-FR', 'en-US', 'de-DE', 'es-ES', 'it-IT', 'pt-PT'];
    $expectedKeys = null;

    foreach ($expectedLocales as $locale) {
        $path = $languageRoot . '/' . $locale . '.json';
        $translations = FlatCmsTest::assertJsonFile($path, 'Generated language file must be valid JSON.');
        $keys = array_keys($translations);
        sort($keys);

        if ($expectedKeys === null) {
            $expectedKeys = $keys;
            continue;
        }

        FlatCmsTest::assertSame($expectedKeys, $keys, 'Generated language keys must be synchronized for locale ' . $locale);
    }
}
