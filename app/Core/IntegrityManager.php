<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Core;

class IntegrityManager
{
    private string $checksumsPath;

    public function __construct()
    {
        $this->checksumsPath = BASE_PATH . '/data/.integrity/checksums.json';
    }

    public static function instance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    public function verifyEntity(string $entity, string $id): bool
    {
        $path = BASE_PATH . '/data/' . $entity . '/' . $id . '.json';
        if (!file_exists($path)) {
            return !isset($this->loadChecksums()[$entity][$id]);
        }

        $currentHash = $this->computeHash($path);
        $stored = $this->loadChecksums();

        return isset($stored[$entity][$id]) && $stored[$entity][$id] === $currentHash;
    }

    public function recordEntity(string $entity, string $id): void
    {
        $path = BASE_PATH . '/data/' . $entity . '/' . $id . '.json';
        if (!file_exists($path)) {
            return;
        }

        $checksums = $this->loadChecksums();
        $checksums[$entity][$id] = $this->computeHash($path);
        $this->saveChecksums($checksums);
    }

    public function removeEntity(string $entity, string $id): void
    {
        $checksums = $this->loadChecksums();
        if (isset($checksums[$entity][$id])) {
            unset($checksums[$entity][$id]);
            if (empty($checksums[$entity])) {
                unset($checksums[$entity]);
            }
            $this->saveChecksums($checksums);
        }
    }

    public function verifyAll(): array
    {
        $results = [
            'total' => 0,
            'valid' => 0,
            'corrupted' => 0,
            'missing' => 0,
            'details' => [],
        ];

        $stored = $this->loadChecksums();

        foreach ($stored as $entity => $ids) {
            foreach ($ids as $id => $expectedHash) {
                $results['total']++;
                $path = BASE_PATH . '/data/' . $entity . '/' . $id . '.json';

                if (!file_exists($path)) {
                    $results['missing']++;
                    $results['details'][] = [
                        'entity' => $entity,
                        'id' => $id,
                        'status' => 'missing',
                    ];
                    continue;
                }

                $currentHash = $this->computeHash($path);
                if ($currentHash === $expectedHash) {
                    $results['valid']++;
                } else {
                    $results['corrupted']++;
                    $results['details'][] = [
                        'entity' => $entity,
                        'id' => $id,
                        'status' => 'corrupted',
                        'expected' => $expectedHash,
                        'actual' => $currentHash,
                    ];
                }
            }
        }

        return $results;
    }

    public function recordAll(): void
    {
        $dataPath = BASE_PATH . '/data';
        if (!is_dir($dataPath)) {
            return;
        }

        $checksums = [];
        $dirs = glob($dataPath . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            $entity = basename($dir);
            if ($entity === '.index' || $entity === 'cache' || $entity === 'sessions') {
                continue;
            }

            $files = glob($dir . '/*.json');
            foreach ($files as $file) {
                $id = basename($file, '.json');
                $checksums[$entity][$id] = $this->computeHash($file);
            }
        }

        $this->saveChecksums($checksums);
    }

    private function computeHash(string $path): string
    {
        return hash_file('sha256', $path);
    }

    private function loadChecksums(): array
    {
        if (!file_exists($this->checksumsPath)) {
            return [];
        }

        $handle = @fopen($this->checksumsPath, 'r');
        if (!$handle) {
            return [];
        }

        flock($handle, LOCK_SH);
        $content = stream_get_contents($handle);
        fclose($handle);

        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return json_last_error() === JSON_ERROR_NONE ? $data : [];
    }

    private function saveChecksums(array $checksums): void
    {
        $dir = dirname($this->checksumsPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $json = json_encode($checksums, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $tmp = $this->checksumsPath . '.tmp.' . getmypid();
        $written = @file_put_contents($tmp, $json);

        if ($written !== false) {
            @rename($tmp, $this->checksumsPath);
        } else {
            @unlink($tmp);
        }
    }
}
