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

$tmpRoot = FlatCmsTest::tempDir('flatcms_runtime_contracts');

if (!defined('BASE_PATH')) {
    define('BASE_PATH', FlatCmsTest::repositoryRoot());
}
if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', $tmpRoot . '/public');
}

require_once BASE_PATH . '/app/Bootstrap/Autoloader.php';

use App\Core\ModuleManager;

try {
    $modulesRoot = $tmpRoot . '/app/Modules';
    $extensionsRoot = $tmpRoot . '/app/Extensions';
    mkdir($modulesRoot, 0755, true);
    mkdir($extensionsRoot, 0755, true);
    mkdir(PUBLIC_PATH, 0755, true);

    write_module_like($modulesRoot . '/OfficialOk', 'module.json', [
        'name' => 'OfficialOk',
        'enabled' => true,
        'required' => true,
        'dependencies' => [],
        'routes' => 'Config/routes.php',
        'hooks' => 'Hooks/listeners.php',
        'assets' => 'Assets',
        'widgets_path' => 'Widgets',
        'vendor' => 'FlatCMS',
        'origin' => 'flatcms',
        'official' => true,
    ], true);

    write_module_like($modulesRoot . '/Auth', 'module.json', [
        'name' => 'Auth',
        'enabled' => true,
        'required' => true,
        'dependencies' => [],
    ], true);

    write_module_like($extensionsRoot . '/ThirdPartyOk', 'extension.json', [
        'name' => 'ThirdPartyOk',
        'key' => 'third-party-ok',
        'enabled' => true,
        'dependencies' => ['OfficialOk'],
        'routes' => 'Config/routes.php',
        'hooks' => 'Hooks/listeners.php',
        'assets' => 'Assets',
        'widgets_path' => 'Widgets',
        'origin' => 'extension',
        'official' => false,
        'sidebar_manageable' => false,
    ], true);

    write_module_like($extensionsRoot . '/WrongManifestExtension', 'module.json', [
        'name' => 'WrongManifestExtension',
        'enabled' => true,
    ], false);

    write_module_like($modulesRoot . '/WrongManifestModule', 'extension.json', [
        'name' => 'WrongManifestModule',
        'enabled' => true,
    ], false);

    write_module_like($extensionsRoot . '/MissingRoutesExtension', 'extension.json', [
        'name' => 'MissingRoutesExtension',
        'enabled' => true,
        'routes' => 'Config/missing.php',
    ], false);

    write_module_like($extensionsRoot . '/BlockedDependencyExtension', 'extension.json', [
        'name' => 'BlockedDependencyExtension',
        'enabled' => true,
        'dependencies' => ['MissingDependency'],
    ], false);

    file_put_contents($tmpRoot . '/state.json', json_encode([
        'ThirdPartyOk' => ['enabled' => true],
        'BlockedDependencyExtension' => ['enabled' => true],
    ], JSON_PRETTY_PRINT));

    $manager = new ModuleManager([$modulesRoot, $extensionsRoot], $tmpRoot . '/state.json');
    $all = $manager->all();

    FlatCmsTest::assertTrue(class_exists(ModuleManager::class), 'Autoloader should resolve App Core classes.');
    FlatCmsTest::assertTrue(isset($all['OfficialOk']), 'Official module should be discovered from app Modules path.');
    FlatCmsTest::assertTrue(isset($all['ThirdPartyOk']), 'Third-party extension should be discovered from app Extensions path.');

    FlatCmsTest::assertSame('module', $all['OfficialOk']['location'], 'Official module location should be module.');
    FlatCmsTest::assertSame('module.json', $all['OfficialOk']['manifest_name'], 'Official module must use module.json.');
    FlatCmsTest::assertSame('extension', $all['ThirdPartyOk']['location'], 'Third-party package location should be extension.');
    FlatCmsTest::assertSame('extension.json', $all['ThirdPartyOk']['manifest_name'], 'Extension must use extension.json.');
    FlatCmsTest::assertSame('assets/extensions', $all['ThirdPartyOk']['public_assets_base'], 'Extensions should default public assets under assets/extensions.');
    FlatCmsTest::assertTrue($manager->isSidebarManageable('OfficialOk'), 'Modules should default to manageable sidebar entries.');
    FlatCmsTest::assertFalse($manager->isSidebarManageable('Auth'), 'Known system modules should stay non-manageable without changing signed manifests.');
    FlatCmsTest::assertFalse($manager->isSidebarManageable('ThirdPartyOk'), 'Manifest should be able to mark sidebar entries as not manageable.');

    FlatCmsTest::assertSame('missing', $all['WrongManifestExtension']['manifest_status'], 'Extension must not fallback to module.json at runtime.');
    FlatCmsTest::assertFalse((bool) $all['WrongManifestExtension']['integrity_valid'], 'Extension with module.json only should be invalid.');
    FlatCmsTest::assertSame('missing', $all['WrongManifestModule']['manifest_status'], 'Module must not fallback to extension.json at runtime.');
    FlatCmsTest::assertSame('module.json', $all['WrongManifestModule']['manifest_name'], 'Module fallback contract should still expect module.json.');

    FlatCmsTest::assertSame('missing', $all['MissingRoutesExtension']['routes_status'], 'Declared missing routes should be detected.');
    FlatCmsTest::assertFalse((bool) $all['MissingRoutesExtension']['integrity_valid'], 'Extension with declared missing routes should be invalid.');
    FlatCmsTest::assertSame('missing_dependencies', $all['BlockedDependencyExtension']['lifecycle_status'], 'Missing dependency should block extension lifecycle.');
    FlatCmsTest::assertSame('dependency:MissingDependency:missing', $all['BlockedDependencyExtension']['lifecycle_reason'], 'Missing dependency reason should be explicit.');

    FlatCmsTest::assertTrue($manager->isEnabled('OfficialOk'), 'Required official module should resolve enabled.');
    FlatCmsTest::assertTrue($manager->isEnabled('ThirdPartyOk'), 'Valid enabled extension with dependency should resolve enabled.');
    FlatCmsTest::assertFalse($manager->isEnabled('WrongManifestExtension'), 'Invalid extension should not resolve enabled.');
    FlatCmsTest::assertFalse($manager->isEnabled('MissingRoutesExtension'), 'Extension with integrity issue should not resolve enabled.');
    FlatCmsTest::assertFalse($manager->isEnabled('BlockedDependencyExtension'), 'Extension with missing dependency should not resolve enabled.');

    $widgets = $manager->widgetsFor('ThirdPartyOk', true);
    FlatCmsTest::assertSame(1, count($widgets), 'Valid extension widget should be discovered when enabled.');
    FlatCmsTest::assertSame('ok', $widgets[0]['status'], 'Widget with widget.php, render.php and preview.js should be complete.');
    FlatCmsTest::assertSame('extension', $widgets[0]['module_location'], 'Widget contract should preserve extension location.');

    FlatCmsTest::assertSame([], $manager->widgetsFor('WrongManifestExtension', true), 'Invalid extension should expose no enabled widgets.');
} finally {
    FlatCmsTest::removeTree($tmpRoot);
}

echo 'PASS: runtime module manager contracts' . PHP_EOL;

/**
 * @param array<string, mixed> $manifest
 */
function write_module_like(string $root, string $manifestName, array $manifest, bool $withRuntimeFiles): void
{
    mkdir($root, 0755, true);
    file_put_contents($root . '/' . $manifestName, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    if (!$withRuntimeFiles) {
        return;
    }

    mkdir($root . '/Config', 0755, true);
    mkdir($root . '/Hooks', 0755, true);
    mkdir($root . '/Assets', 0755, true);
    mkdir($root . '/Widgets/Card', 0755, true);
    file_put_contents($root . '/Config/routes.php', "<?php\n");
    file_put_contents($root . '/Hooks/listeners.php', "<?php\n");
    file_put_contents($root . '/Assets/app.css', '');
    file_put_contents($root . '/Widgets/Card/widget.php', "<?php\n");
    file_put_contents($root . '/Widgets/Card/render.php', "<?php\n");
    file_put_contents($root . '/Widgets/Card/preview.js', '');
}
