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

class FlatFile
{
    private string $basePath;
    private string $entity;

    public function __construct(string $entity)
    {
        $this->entity = $entity;
        $this->basePath = BASE_PATH . '/data/' . $entity;
        
        // Ensure directory exists
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    public static function for(string $entity): self
    {
        return new self($entity);
    }

    public function all(): array
    {
        $items = [];
        $files = glob($this->basePath . '/*.json');

        foreach ($files as $file) {
            $data = $this->readFile($file);
            if ($data) {
                $items[] = $data;
            }
        }

        return $items;
    }

    public function find(string $id): ?array
    {
        $path = $this->getFilePath($id);
        return $this->readFile($path);
    }

    public function findVerified(string $id): ?array
    {
        $data = $this->find($id);
        if ($data === null) {
            return null;
        }

        if (!IntegrityManager::instance()->verifyEntity($this->entity, $id)) {
            return null;
        }

        return $data;
    }

    public function integrityCheck(string $id): array
    {
        $path = $this->getFilePath($id);

        if (!file_exists($path)) {
            return ['status' => 'missing', 'entity' => $this->entity, 'id' => $id];
        }

        $valid = IntegrityManager::instance()->verifyEntity($this->entity, $id);

        return [
            'status' => $valid ? 'valid' : 'corrupted',
            'entity' => $this->entity,
            'id' => $id,
        ];
    }

    public function allIntegrityCheck(): array
    {
        $results = [
            'total' => 0,
            'valid' => 0,
            'corrupted' => 0,
            'missing' => 0,
            'details' => [],
        ];

        $files = glob($this->basePath . '/*.json');
        foreach ($files as $file) {
            $id = basename($file, '.json');
            $results['total']++;
            $check = $this->integrityCheck($id);

            if ($check['status'] === 'valid') {
                $results['valid']++;
            } elseif ($check['status'] === 'corrupted') {
                $results['corrupted']++;
                $results['details'][] = $check;
            } else {
                $results['missing']++;
                $results['details'][] = $check;
            }
        }

        return $results;
    }

    public function findBy(string $field, mixed $value): ?array
    {
        foreach ($this->all() as $item) {
            if (isset($item[$field]) && $item[$field] === $value) {
                return $item;
            }
        }
        return null;
    }

    public function where(string $field, mixed $value): array
    {
        return array_filter($this->all(), function ($item) use ($field, $value) {
            return isset($item[$field]) && $item[$field] === $value;
        });
    }

    public function create(array $data): array
    {
        if (!isset($data['id'])) {
            $data['id'] = $this->generateId();
        }

        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $this->save($data['id'], $data);
        IndexManager::instance()->onEntitySaved($this->entity, $data['id'], $data);
        IntegrityManager::instance()->recordEntity($this->entity, $data['id']);

        return $data;
    }

    public function update(string $id, array $data): ?array
    {
        $existing = $this->find($id);
        
        if (!$existing) {
            return null;
        }

        $data = array_merge($existing, $data);
        $data['id'] = $id;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->save($id, $data);
        IndexManager::instance()->onEntitySaved($this->entity, $id, $data);
        IntegrityManager::instance()->recordEntity($this->entity, $id);

        return $data;
    }

    public function delete(string $id): bool
    {
        $path = $this->getFilePath($id);
        
        if (!file_exists($path)) {
            return false;
        }

        $lockPath = $path . '.lock';
        $lock = @fopen($lockPath, 'c');
        if ($lock) {
            flock($lock, LOCK_EX);
        }

        $deleted = @unlink($path);

        if ($deleted) {
            IndexManager::instance()->onEntityDeleted($this->entity, $id);
            IntegrityManager::instance()->removeEntity($this->entity, $id);
        }

        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($lockPath);
        }

        return $deleted;
    }

    public function exists(string $id): bool
    {
        return file_exists($this->getFilePath($id));
    }

    public function count(): int
    {
        return IndexManager::instance()->count($this->entity);
    }

    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $indexManager = IndexManager::instance();
        $index = $indexManager->getIndex($this->entity);

        $total = count($index);
        $totalPages = (int) ceil($total / $perPage);

        $sorted = $index;
        uasort($sorted, function ($a, $b) {
            return ($b['created_at'] ?? '') <=> ($a['created_at'] ?? '');
        });

        $offset = ($page - 1) * $perPage;
        $sliced = array_slice($sorted, $offset, $perPage, true);

        $data = [];
        foreach (array_keys($sliced) as $id) {
            $item = $this->find($id);
            if ($item) {
                $data[] = $item;
            }
        }

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ];
    }

    public function search(string $query, array $fields = ['title', 'name']): array
    {
        $query = strtolower($query);
        
        return array_filter($this->all(), function ($item) use ($query, $fields) {
            foreach ($fields as $field) {
                if (isset($item[$field]) && str_contains(strtolower($item[$field]), $query)) {
                    return true;
                }
            }
            return false;
        });
    }

    public function entity(): string
    {
        return $this->entity;
    }

    private function save(string $id, array $data): void
    {
        $path = $this->getFilePath($id);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $lockPath = $path . '.lock';
        $lock = @fopen($lockPath, 'c');
        if ($lock) {
            flock($lock, LOCK_EX);
        }

        $tmp = $path . '.tmp.' . getmypid();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $written = @file_put_contents($tmp, $json);

        if ($written !== false) {
            @rename($tmp, $path);
        } else {
            @unlink($tmp);
        }

        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($lockPath);
        }
    }

    private function readFile(string $path): ?array
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

    private function getFilePath(string $id): string
    {
        // Sanitize ID
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        return $this->basePath . '/' . $id . '.json';
    }

    private function generateId(): string
    {
        return date('YmdHis') . '_' . bin2hex(random_bytes(4));
    }

    // Settings helper - for single config files
    public static function settings(string $name = 'settings'): array
    {
        $cacheKey = 'settings_' . $name;
        $cache = CacheManager::instance();
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $path = self::resolveSettingsReadPath($name);

        if (!file_exists($path)) {
            return [];
        }

        $handle = @fopen($path, 'r');
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
        $result = json_last_error() === JSON_ERROR_NONE ? $data : [];

        if (!empty($result)) {
            $cache->set($cacheKey, $result, 60);
        }

        return $result;
    }

    public static function saveSettings(array $data, string $name = 'settings'): bool
    {
        $path = self::resolveSettingsWritePath($name);
        $dir = dirname($path);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $saved = file_put_contents($path, $json, LOCK_EX) !== false;
        if ($saved) {
            self::cleanupLegacySettingsPath($name, $path);
            CacheManager::instance()->forget('settings_' . $name);
        }
        return $saved;
    }

    private static function resolveSettingsReadPath(string $name): string
    {
        $preferred = self::resolveSettingsWritePath($name);
        if (file_exists($preferred)) {
            return $preferred;
        }

        // Backward compatibility for legacy files: /data/{name}.json
        $legacy = self::resolveLegacySettingsPath($name);
        if ($legacy !== $preferred && file_exists($legacy)) {
            return $legacy;
        }

        return $preferred;
    }

    private static function resolveSettingsWritePath(string $name): string
    {
        if ($name === 'menus') {
            return BASE_PATH . '/data/menus/menus.json';
        }

        if ($name === 'footer') {
            return BASE_PATH . '/data/footer/footer.json';
        }

        return self::resolveLegacySettingsPath($name);
    }

    private static function resolveLegacySettingsPath(string $name): string
    {
        return BASE_PATH . '/data/' . $name . '.json';
    }

    private static function cleanupLegacySettingsPath(string $name, string $writtenPath): void
    {
        $legacy = self::resolveLegacySettingsPath($name);
        if ($legacy === $writtenPath) {
            return;
        }

        if (file_exists($legacy)) {
            @unlink($legacy);
        }
    }
}
