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

$repo = FlatCmsTest::repositoryRoot();

$requiredFiles = [
    'scripts/release/build-installer-package.sh',
    'scripts/release/templates/installer-kit/index.html',
    'scripts/release/templates/installer-kit/unpack.php',
];

foreach ($requiredFiles as $file) {
    FlatCmsTest::assertFileExists($repo . '/' . $file, 'LTS docs/scripts contract file must exist.');
}

foreach ([
    'scripts/content',
] as $forbiddenPath) {
    FlatCmsTest::assertFalse(file_exists($repo . '/' . $forbiddenPath), 'Legacy script must not be imported: ' . $forbiddenPath);
}

foreach ([
    'scripts/release/build-installer-package.sh',
] as $script) {
    FlatCmsTest::assertTrue(is_executable($repo . '/' . $script), 'Release shell script must be executable: ' . $script);
}

$buildScript = read_repo_file($repo, 'scripts/release/build-installer-package.sh');
foreach ([
    '--exclude=\'/.env.example\'',
    '--exclude=\'/docs/\'',
    '--exclude=\'/scripts/\'',
    '--exclude=\'/tests/\'',
    '--exclude=\'/app/Extensions/\'',
    '--exclude=\'/data/\'',
    'OUTER_PACKAGE_ZIP="$PACKAGE_DIR/package.zip"',
    'CORE_ZIP_PATH="$PACKAGE_DIR/flatcms.zip"',
    'PAGES_BUILDER_ZIP="$PACKAGE_DIR/PagesBuilder.zip"',
    'KIT_DIR="$PACKAGE_DIR/installer-kit"',
    'cp "$ROOT_DIR/README.md" "$RUNTIME_DIR/README.md"',
    'cp "$ROOT_DIR/LICENSING.md" "$RUNTIME_DIR/LICENSING.md"',
    'rsync -a',
    '"$ROOT_DIR/app/Extensions/PagesBuilder/" "$EXTENSION_DIR/"',
    'zip -rq "$PAGES_BUILDER_ZIP" PagesBuilder',
] as $expectedFragment) {
    FlatCmsTest::assertTrue(str_contains($buildScript, $expectedFragment), 'Build script is missing contract fragment: ' . $expectedFragment);
}

$unpackTemplate = read_repo_file($repo, 'scripts/release/templates/installer-kit/unpack.php');
FlatCmsTest::assertTrue(str_contains($unpackTemplate, 'const MIN_PHP_VERSION_ID = 80100;'), 'Unpacker must require PHP 8.1+.');
FlatCmsTest::assertTrue(str_contains($unpackTemplate, 'function is_safe_zip_entry'), 'Unpacker must validate ZIP entry paths.');
FlatCmsTest::assertTrue(str_contains($unpackTemplate, 'hash_equals'), 'Unpacker must validate CSRF token with hash_equals.');

$gitignore = read_repo_file($repo, '.gitignore');
FlatCmsTest::assertTrue((bool) preg_match('/^\/Package\/$/m', $gitignore), 'Package/ must remain ignored.');

$scannedFiles = collect_files($repo, [
    'README.md',
    'AGENTS.md',
    'FLATCMS_ROADMAP.md',
    'scripts/release',
]);

$forbiddenPatterns = [
    '/data\/shop|resources\/Store/',
    '/\bMarketPlace\b|\bMarketplace\b/',
];

foreach ($scannedFiles as $file) {
    $content = read_repo_file($repo, $file);
    foreach ($forbiddenPatterns as $pattern) {
        FlatCmsTest::assertFalse((bool) preg_match($pattern, $content), 'LTS docs/scripts contain forbidden legacy reference: ' . $file);
    }
}

echo 'PASS: package pipeline contracts' . PHP_EOL;

function read_repo_file(string $repo, string $path): string
{
    $content = file_get_contents($repo . '/' . $path);
    if ($content === false) {
        throw new FlatCmsTestFailure('Unable to read file: ' . $path);
    }

    return $content;
}

/**
 * @param array<int, string> $paths
 * @return array<int, string>
 */
function collect_files(string $repo, array $paths): array
{
    $files = [];
    foreach ($paths as $path) {
        $absolute = $repo . '/' . $path;
        if (is_file($absolute)) {
            $files[] = $path;
            continue;
        }

        if (!is_dir($absolute)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($item->getPathname(), strlen($repo))), '/');
            $files[] = $relative;
        }
    }

    sort($files);

    return array_values(array_unique($files));
}
