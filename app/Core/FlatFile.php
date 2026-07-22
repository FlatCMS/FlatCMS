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
        $seen = [];
        $files = glob($this->basePath . '/*.json') ?: [];

        foreach ($files as $file) {
            $data = $this->readFile($file);
            if ($data) {
                $items[] = $data;
                $id = trim((string) ($data['id'] ?? ''));
                if ($id !== '') {
                    $seen[$id] = true;
                }
            }
        }

        $legacyBasePath = $this->legacyBasePath();
        if ($legacyBasePath !== null && is_dir($legacyBasePath)) {
            $legacyFiles = glob($legacyBasePath . '/*.json') ?: [];
            foreach ($legacyFiles as $file) {
                $data = $this->readFile($file);
                if (!$data) {
                    continue;
                }

                $id = trim((string) ($data['id'] ?? ''));
                if ($id !== '' && isset($seen[$id])) {
                    continue;
                }

                $items[] = $data;
                if ($id !== '') {
                    $seen[$id] = true;
                }
            }
        }

        return $items;
    }

    public function find(string $id): ?array
    {
        $path = $this->getFilePath($id);
        $data = $this->readFile($path);
        if ($data !== null) {
            return $data;
        }

        $legacyPath = $this->getLegacyFilePath($id);
        return $legacyPath !== null ? $this->readFile($legacyPath) : null;
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
        // Generate ID if not provided
        if (!isset($data['id'])) {
            $data['id'] = $this->generateId();
        }

        // Add timestamps
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        // Save
        $this->save($data['id'], $data);

        return $data;
    }

    public function update(string $id, array $data): ?array
    {
        $existing = $this->find($id);
        
        if (!$existing) {
            return null;
        }

        // Merge data
        $data = array_merge($existing, $data);
        $data['id'] = $id; // Ensure ID doesn't change
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Save
        $this->save($id, $data);

        return $data;
    }

    public function delete(string $id): bool
    {
        $path = $this->getFilePath($id);
        
        if (file_exists($path)) {
            return unlink($path);
        }

        $legacyPath = $this->getLegacyFilePath($id);
        if ($legacyPath !== null && file_exists($legacyPath)) {
            return unlink($legacyPath);
        }

        return false;
    }

    public function exists(string $id): bool
    {
        if (file_exists($this->getFilePath($id))) {
            return true;
        }

        $legacyPath = $this->getLegacyFilePath($id);
        return $legacyPath !== null && file_exists($legacyPath);
    }

    public function count(): int
    {
        return count($this->all());
    }

    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $all = $this->all();
        $total = count($all);
        $totalPages = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        // Sort by created_at desc by default
        usort($all, function ($a, $b) {
            return ($b['created_at'] ?? '') <=> ($a['created_at'] ?? '');
        });

        return [
            'data' => array_slice($all, $offset, $perPage),
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

    private function save(string $id, array $data): void
    {
        $path = $this->getFilePath($id);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $saved = file_put_contents($path, $json, LOCK_EX) !== false;
        if ($saved) {
            $this->cleanupLegacyFilePath($id, $path);
        }
    }

    private function readFile(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $data : null;
    }

    private function getFilePath(string $id): string
    {
        // Sanitize ID
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        return $this->basePath . '/' . $id . '.json';
    }

    private function getLegacyFilePath(string $id): ?string
    {
        $legacyBasePath = $this->legacyBasePath();
        if ($legacyBasePath === null) {
            return null;
        }

        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        return $legacyBasePath . '/' . $id . '.json';
    }

    private function legacyBasePath(): ?string
    {
        $legacyEntity = match ($this->entity) {
            'core/comments' => 'comments',
            default => null,
        };

        return $legacyEntity === null ? null : BASE_PATH . '/data/' . $legacyEntity;
    }

    private function cleanupLegacyFilePath(string $id, string $writtenPath): void
    {
        $legacyPath = $this->getLegacyFilePath($id);
        if ($legacyPath === null || $legacyPath === $writtenPath) {
            return;
        }

        if (file_exists($legacyPath)) {
            @unlink($legacyPath);
        }
    }

    private function generateId(): string
    {
        return date('YmdHis') . '_' . bin2hex(random_bytes(4));
    }

    // Settings helper - for single config files
    public static function settings(string $name = 'settings'): array
    {
        $path = self::resolveSettingsReadPath($name);

        if (!file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $data : [];
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
        }
        return $saved;
    }

    private static function resolveSettingsReadPath(string $name): string
    {
        $preferred = self::resolveSettingsWritePath($name);
        if (file_exists($preferred)) {
            return $preferred;
        }

        foreach (self::resolveLegacySettingsPaths($name) as $legacy) {
            if ($legacy !== $preferred && file_exists($legacy)) {
                return $legacy;
            }
        }

        return $preferred;
    }

    private static function resolveSettingsWritePath(string $name): string
    {
        if ($name === 'menus') {
            return BASE_PATH . '/data/core/menus/menus.json';
        }

        if ($name === 'footer') {
            return BASE_PATH . '/data/core/footer/footer.json';
        }

        return self::resolveLegacySettingsPath($name);
    }

    private static function resolveLegacySettingsPath(string $name): string
    {
        return BASE_PATH . '/data/' . $name . '.json';
    }

    private static function cleanupLegacySettingsPath(string $name, string $writtenPath): void
    {
        foreach (self::resolveLegacySettingsPaths($name) as $legacy) {
            if ($legacy === $writtenPath) {
                continue;
            }

            if (file_exists($legacy)) {
                @unlink($legacy);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private static function resolveLegacySettingsPaths(string $name): array
    {
        return match ($name) {
            'menus' => [
                BASE_PATH . '/data/menus/menus.json',
                self::resolveLegacySettingsPath($name),
            ],
            'footer' => [
                BASE_PATH . '/data/footer/footer.json',
                self::resolveLegacySettingsPath($name),
            ],
            default => [
                self::resolveLegacySettingsPath($name),
            ],
        };
    }
}
