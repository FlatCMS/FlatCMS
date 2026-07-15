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

class IndexManager
{
    private static ?self $instance = null;
    private array $indexes = [];
    private bool $dirty = false;

    private function __construct() {}

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getIndex(string $entity): array
    {
        if (isset($this->indexes[$entity])) {
            return $this->indexes[$entity];
        }

        $indexPath = $this->indexPath($entity);

        if (file_exists($indexPath)) {
            $json = $this->safeRead($indexPath);
            if (is_array($json)) {
                $this->indexes[$entity] = $json;
                return $json;
            }
        }

        $this->rebuildIndex($entity);
        return $this->indexes[$entity];
    }

    public function onEntitySaved(string $entity, string $id, array $data): void
    {
        $index = $this->getIndex($entity);
        $index[$id] = $this->extractMeta($data);
        $this->indexes[$entity] = $index;
        $this->dirty = true;
        $this->writeIndex($entity);
    }

    public function onEntityDeleted(string $entity, string $id): void
    {
        $index = $this->getIndex($entity);

        if (isset($index[$id])) {
            unset($index[$id]);
            $this->indexes[$entity] = $index;
            $this->dirty = true;
            $this->writeIndex($entity);
        }
    }

    public function count(string $entity): int
    {
        return count($this->getIndex($entity));
    }

    public function rebuildIndex(string $entity): void
    {
        $basePath = BASE_PATH . '/data/' . $entity;
        $index = [];

        if (is_dir($basePath)) {
            $files = glob($basePath . '/*.json');
            foreach ($files as $file) {
                $data = $this->safeRead($file);
                if (is_array($data) && isset($data['id'])) {
                    $index[$data['id']] = $this->extractMeta($data);
                }
            }
        }

        $this->indexes[$entity] = $index;
        $this->writeIndex($entity);
    }

    public function rebuildAll(): void
    {
        $dataPath = BASE_PATH . '/data';
        if (!is_dir($dataPath)) {
            return;
        }

        $dirs = glob($dataPath . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $basename = basename($dir);
            if ($basename === '.index' || $basename === 'cache' || $basename === 'sessions') {
                continue;
            }
            $this->rebuildIndex($basename);
        }
    }

    public function flush(): void
    {
        if ($this->dirty) {
            foreach ($this->indexes as $entity => $index) {
                $this->writeIndex($entity);
            }
            $this->dirty = false;
        }
    }

    private function writeIndex(string $entity): void
    {
        $dir = BASE_PATH . '/data/.index';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . $entity . '.json';
        $json = json_encode($this->indexes[$entity] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $tmp = $path . '.tmp.' . getmypid();
        $written = @file_put_contents($tmp, $json);

        if ($written !== false) {
            @rename($tmp, $path);
        } else {
            @unlink($tmp);
        }
    }

    private function indexPath(string $entity): string
    {
        return BASE_PATH . '/data/.index/' . $entity . '.json';
    }

    private function extractMeta(array $data): array
    {
        return [
            'id' => $data['id'] ?? '',
            'title' => $data['title'] ?? $data['name'] ?? $data['label'] ?? '',
            'slug' => $data['slug'] ?? '',
            'status' => $data['status'] ?? 'published',
            'created_at' => $data['created_at'] ?? '',
            'updated_at' => $data['updated_at'] ?? '',
        ];
    }

    private function safeRead(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $handle = @fopen($path, 'r');
        if (!$handle) {
            return null;
        }

        flock($handle, LOCK_SH);
        $content = stream_get_contents($handle);
        fclose($handle);

        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        return json_last_error() === JSON_ERROR_NONE ? $data : null;
    }
}
