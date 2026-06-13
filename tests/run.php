<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$testsRoot = __DIR__;

$suites = [
    'regression' => [$testsRoot . '/regression/*.php'],
    'installer' => [$testsRoot . '/installer/*.php'],
    'runtime' => [$testsRoot . '/runtime/*.php'],
    'themes' => [$testsRoot . '/themes/*.php'],
    'generators' => [$testsRoot . '/generators/*.php'],
    'release' => [$testsRoot . '/release/*.php'],
];

$requestedSuites = array_slice($argv, 1);
if ($requestedSuites === []) {
    $requestedSuites = array_keys($suites);
}

$unknownSuites = array_values(array_diff($requestedSuites, array_keys($suites)));
if ($unknownSuites !== []) {
    fwrite(STDERR, 'FAIL: unknown test suite(s): ' . implode(', ', $unknownSuites) . PHP_EOL);
    exit(1);
}

$total = 0;
$failed = 0;
$skipped = 0;

foreach ($requestedSuites as $suite) {
    $files = discover_test_files($suites[$suite]);
    if ($files === []) {
        $skipped++;
        echo 'SKIP ' . $suite . ': no tests' . PHP_EOL;
        continue;
    }

    echo 'SUITE ' . $suite . PHP_EOL;
    foreach ($files as $file) {
        $total++;
        $result = run_test_file($file, $root);
        $label = $suite . '/' . basename($file);
        if ($result['exit_code'] === 0) {
            echo 'PASS ' . $label . PHP_EOL;
            continue;
        }

        $failed++;
        echo 'FAIL ' . $label . PHP_EOL;
        $output = trim($result['output']);
        if ($output !== '') {
            echo indent_output($output) . PHP_EOL;
        }
    }
}

if ($failed > 0) {
    echo 'FAIL: ' . $failed . ' failed, ' . $total . ' executed, ' . $skipped . ' suite(s) skipped' . PHP_EOL;
    exit(1);
}

echo 'PASS: ' . $total . ' executed, ' . $skipped . ' suite(s) skipped' . PHP_EOL;
exit(0);

/**
 * @param array<int, string> $patterns
 * @return array<int, string>
 */
function discover_test_files(array $patterns): array
{
    $files = [];
    foreach ($patterns as $pattern) {
        foreach (glob($pattern) ?: [] as $file) {
            if (is_file($file)) {
                $files[] = $file;
            }
        }
    }

    sort($files);
    return array_values(array_unique($files));
}

/**
 * @return array{exit_code: int, output: string}
 */
function run_test_file(string $file, string $cwd): array
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open([PHP_BINARY, $file], $descriptorSpec, $pipes, $cwd);
    if (!is_resource($process)) {
        return [
            'exit_code' => 1,
            'output' => 'Unable to start PHP test process.',
        ];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'exit_code' => is_int($exitCode) ? $exitCode : 1,
        'output' => trim((string) $stdout . "\n" . (string) $stderr),
    ];
}

function indent_output(string $output): string
{
    $lines = preg_split('/\R/', $output) ?: [];

    return implode(PHP_EOL, array_map(
        static fn (string $line): string => '  ' . $line,
        $lines
    ));
}
