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
$trackedFiles = git_files($repo);
$trackedSet = array_fill_keys($trackedFiles, true);

foreach ([
    'VERSION',
    'FLATCMS_CCTP.md',
    'FLATCMS_ROADMAP.md',
    'config/app.php',
    'app/Bootstrap/Autoloader.php',
    'app/Core/ModuleManager.php',
    'public/index.php',
] as $requiredFile) {
    FlatCmsTest::assertTrue(isset($trackedSet[$requiredFile]), 'Release-critical tracked file is missing: ' . $requiredFile);
}

foreach ($trackedFiles as $file) {
    FlatCmsTest::assertFalse((bool) preg_match('/(^|\\/)(\\.DS_Store|Thumbs\\.db)$/', $file), 'Release must not track OS metadata: ' . $file);
    FlatCmsTest::assertFalse((bool) preg_match('/\\.(BAK|bak|tmp|orig|rej)$/', $file), 'Release must not track local backup artifacts: ' . $file);
    FlatCmsTest::assertFalse((bool) preg_match('/\\.(zip|tar|tgz|gz|rar|7z)$/i', $file), 'Release must not track packaged archives inside source tree: ' . $file);
    FlatCmsTest::assertFalse((bool) preg_match('/\\.(xlsx|xls|docx|pptx)$/i', $file), 'Release must not track local office artifacts: ' . $file);
}

foreach ($trackedFiles as $file) {
    if (!str_ends_with($file, '.json')) {
        continue;
    }

    json_from_git($repo, $file);
}

$publicKey = extract_public_key(git_blob($repo, 'config/app.php'));
FlatCmsTest::assertTrue($publicKey !== '', 'Release signature public key must be available in config/app.php.');
FlatCmsTest::assertTrue(extension_loaded('openssl'), 'OpenSSL extension is required to verify official signatures.');

foreach ($trackedFiles as $file) {
    if (preg_match('#^app/Modules/[^/]+/extension\\.json$#', $file) === 1) {
        throw new FlatCmsTestFailure('Official modules must not use extension.json: ' . $file);
    }

    if (preg_match('#^app/Extensions/[^/]+/module\\.json$#', $file) === 1) {
        throw new FlatCmsTestFailure('Extensions and builders must not use module.json: ' . $file);
    }

    if (preg_match('#^app/Modules/([^/]+)/module\\.json$#', $file, $moduleMatch) === 1) {
        $manifest = json_from_git($repo, $file);
        validate_manifest_contract($manifest, $file, 'module', $moduleMatch[1], $publicKey);
        continue;
    }

    if (preg_match('#^app/Extensions/([^/]+)/extension\\.json$#', $file, $extensionMatch) === 1) {
        $manifest = json_from_git($repo, $file);
        validate_manifest_contract($manifest, $file, 'extension', $extensionMatch[1], $publicKey);
    }
}

validate_language_key_sets($repo, $trackedFiles);

echo 'PASS: release hygiene contracts' . PHP_EOL;

/**
 * @return array<int, string>
 */
function git_files(string $repo): array
{
    $output = git_output($repo, ['ls-files', '-z']);
    $files = array_values(array_filter(explode("\0", $output), static fn(string $file): bool => $file !== ''));
    sort($files);

    return $files;
}

function git_blob(string $repo, string $path): string
{
    return git_output($repo, ['show', 'HEAD:' . $path]);
}

/**
 * @param array<int, string> $args
 */
function git_output(string $repo, array $args): string
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(array_merge(['git'], $args), $descriptorSpec, $pipes, $repo);
    if (!is_resource($process)) {
        throw new FlatCmsTestFailure('Unable to start git process.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        throw new FlatCmsTestFailure('Git command failed: git ' . implode(' ', $args) . ' ' . trim((string) $stderr));
    }

    return (string) $stdout;
}

/**
 * @return array<string, mixed>
 */
function json_from_git(string $repo, string $path): array
{
    $content = git_blob($repo, $path);
    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        throw new FlatCmsTestFailure('Tracked JSON must be valid: ' . $path . ' (' . json_last_error_msg() . ')');
    }

    return $decoded;
}

function extract_public_key(string $configSource): string
{
    if (preg_match('/-----BEGIN PUBLIC KEY-----.*?-----END PUBLIC KEY-----/s', $configSource, $matches) !== 1) {
        return '';
    }

    return trim($matches[0]);
}

/**
 * @param array<string, mixed> $manifest
 */
function validate_manifest_contract(array $manifest, string $path, string $kind, string $directoryName, string $publicKey): void
{
    $name = trim((string) ($manifest['name'] ?? ''));
    FlatCmsTest::assertTrue($name !== '', 'Manifest must declare a name: ' . $path);
    FlatCmsTest::assertSame($directoryName, preg_replace('/[^a-zA-Z0-9_-]/', '', $name) ?? '', 'Manifest name must resolve to its release directory: ' . $path);

    foreach (['routes', 'hooks', 'assets', 'widgets_path', 'languages'] as $field) {
        validate_manifest_path_value($manifest[$field] ?? null, $path, $field);
    }

    $origin = strtolower(trim((string) ($manifest['origin'] ?? '')));
    $official = (bool) ($manifest['official'] ?? false);
    $signature = trim((string) ($manifest['signature'] ?? ''));

    if ($kind === 'module') {
        FlatCmsTest::assertSame('flatcms', $origin, 'Official modules must declare origin=flatcms: ' . $path);
        FlatCmsTest::assertTrue($official, 'Official modules must declare official=true: ' . $path);
        FlatCmsTest::assertTrue($signature !== '', 'Official modules must carry a signature: ' . $path);
        FlatCmsTest::assertTrue(verify_manifest_signature($manifest, $publicKey), 'Official module signature must verify: ' . $path);
        return;
    }

    if ($official || $origin === 'flatcms') {
        FlatCmsTest::assertTrue($signature !== '', 'Official extension or builder must carry a signature: ' . $path);
        FlatCmsTest::assertTrue(verify_manifest_signature($manifest, $publicKey), 'Official extension signature must verify: ' . $path);
    }
}

function validate_manifest_path_value(mixed $value, string $manifestPath, string $field): void
{
    if ($value === null || $value === '') {
        return;
    }

    $values = is_array($value) ? array_values($value) : [$value];
    foreach ($values as $candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }

        $path = str_replace('\\\\', '/', trim($candidate));
        FlatCmsTest::assertFalse(str_starts_with($path, '/'), 'Manifest path must not be absolute: ' . $manifestPath . ' field=' . $field);
        FlatCmsTest::assertFalse((bool) preg_match('#^[A-Za-z]:#', $path), 'Manifest path must not be drive-absolute: ' . $manifestPath . ' field=' . $field);
        FlatCmsTest::assertFalse(str_contains($path, '://'), 'Manifest path must not be a URL: ' . $manifestPath . ' field=' . $field);
        FlatCmsTest::assertFalse(str_contains($path, '..'), 'Manifest path must not escape package root: ' . $manifestPath . ' field=' . $field);
        FlatCmsTest::assertFalse(str_starts_with($path, 'app/'), 'Manifest path must not target app directly: ' . $manifestPath . ' field=' . $field);
        FlatCmsTest::assertFalse(str_starts_with($path, 'public/'), 'Manifest path must not target public directly: ' . $manifestPath . ' field=' . $field);
    }
}

/**
 * @param array<string, mixed> $manifest
 */
function verify_manifest_signature(array $manifest, string $publicKey): bool
{
    $signature = (string) ($manifest['signature'] ?? '');
    if ($signature === '') {
        return false;
    }

    $payloadData = $manifest;
    unset($payloadData['signature']);
    $payloadData = normalize_manifest_data($payloadData);
    $payload = json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $decodedSignature = base64_decode($signature, true);

    if ($payload === false || $decodedSignature === false) {
        return false;
    }

    return openssl_verify($payload, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
}

/**
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function normalize_manifest_data(array $data): array
{
    ksort($data);
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = normalize_manifest_data($value);
        }
    }

    return $data;
}

/**
 * @param array<int, string> $trackedFiles
 */
function validate_language_key_sets(string $repo, array $trackedFiles): void
{
    $languageGroups = [];
    foreach ($trackedFiles as $file) {
        if (preg_match('#^(.*/Languages)/([a-z]{2}-[A-Z]{2})\\.json$#', $file, $matches) !== 1) {
            continue;
        }

        $languageGroups[$matches[1]][$matches[2]] = $file;
    }

    foreach ($languageGroups as $directory => $localeFiles) {
        if (count($localeFiles) < 2) {
            continue;
        }

        ksort($localeFiles);
        $expectedLocale = array_key_first($localeFiles);
        $expectedKeys = array_keys(json_from_git($repo, $localeFiles[$expectedLocale]));
        sort($expectedKeys);

        foreach ($localeFiles as $locale => $file) {
            $actualKeys = array_keys(json_from_git($repo, $file));
            sort($actualKeys);
            FlatCmsTest::assertSame($expectedKeys, $actualKeys, 'Language keys must stay synchronized in ' . $directory . ' for locale ' . $locale);
        }
    }
}
