<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

final class FlatCmsTestFailure extends RuntimeException
{
}

final class FlatCmsTest
{
    public static function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new FlatCmsTestFailure($message);
        }
    }

    public static function assertFalse(bool $condition, string $message): void
    {
        self::assertTrue(!$condition, $message);
    }

    public static function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new FlatCmsTestFailure($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
        }
    }

    public static function assertFileExists(string $path, string $message): void
    {
        self::assertTrue(is_file($path), $message . ' Missing file: ' . $path);
    }

    public static function assertDirectoryExists(string $path, string $message): void
    {
        self::assertTrue(is_dir($path), $message . ' Missing directory: ' . $path);
    }

    public static function assertJsonFile(string $path, string $message): array
    {
        self::assertFileExists($path, $message);
        $data = json_decode((string) file_get_contents($path), true);
        self::assertTrue(is_array($data), $message . ' Invalid JSON: ' . $path);

        return $data;
    }

    public static function tempDir(string $prefix): string
    {
        $root = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $prefix . '_' . bin2hex(random_bytes(6));
        if (!mkdir($root, 0755, true) && !is_dir($root)) {
            throw new FlatCmsTestFailure('Unable to create temporary test directory: ' . $root);
        }

        return $root;
    }

    public static function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir() && !$item->isLink()) {
                rmdir($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($path);
    }

    public static function repositoryRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
