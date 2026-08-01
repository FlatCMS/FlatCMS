<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Media\Repositories;

class MediaRepository
{
    private string $dataFile;
    private array $data = [];
    private ?string $sourceHash = null;

    public function __construct(?string $basePath = null)
    {
        $basePath = $basePath !== null
            ? rtrim(str_replace('\\', '/', $basePath), '/')
            : $this->resolveBasePath();
        $this->dataFile = $basePath . '/data/core/media/media.json';
        $this->ensureDirectory();
        $this->load();
    }

    private function resolveBasePath(): string
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
        $basePath = str_replace('\\', '/', $basePath);

        if (str_ends_with($basePath, '/public') && is_dir($basePath . '/../data')) {
            $resolved = realpath($basePath . '/..');
            if ($resolved) {
                return str_replace('\\', '/', $resolved);
            }
            return rtrim($basePath, '/public');
        }

        return $basePath;
    }

    /**
     * S'assure que le répertoire existe
     */
    private function ensureDirectory(): void
    {
        $dir = dirname($this->dataFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Charge les données depuis le fichier JSON
     */
    private function load(): void
    {
        $content = file_get_contents($this->dataFile);
        $this->sourceHash = is_string($content) ? hash('sha256', $content) : null;
        $this->data = is_string($content) ? (json_decode($content, true) ?: []) : [];
    }

    /**
     * Sauvegarde les données dans le fichier JSON
     */
    private function save(): bool
    {
        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return false;
        }

        if (is_link($this->dataFile)) {
            return false;
        }
        $current = @file_get_contents($this->dataFile);
        if (!is_string($current) || ($this->sourceHash !== null && hash('sha256', $current) !== $this->sourceHash)) {
            return false;
        }

        $payload = $json . PHP_EOL;

        $directory = dirname($this->dataFile);
        try {
            $suffix = bin2hex(random_bytes(6));
        } catch (\Throwable) {
            $suffix = str_replace('.', '', uniqid('', true));
        }
        $temp = $directory . '/.' . basename($this->dataFile) . '.' . $suffix . '.tmp';
        if (@file_put_contents($temp, $payload, LOCK_EX) === false) {
            return false;
        }

        $permissions = @fileperms($this->dataFile);
        if (is_int($permissions)) {
            @chmod($temp, $permissions & 0777);
        }

        if (@rename($temp, $this->dataFile)) {
            $this->sourceHash = hash('sha256', $payload);
            return true;
        }

        $backup = $directory . '/.' . basename($this->dataFile) . '.' . $suffix . '.bak';
        if (!@rename($this->dataFile, $backup)) {
            @unlink($temp);
            return false;
        }

        if (@rename($temp, $this->dataFile)) {
            @unlink($backup);
            $this->sourceHash = hash('sha256', $payload);
            return true;
        }

        @rename($backup, $this->dataFile);
        @unlink($temp);
        return false;
    }

    /**
     * Retourne tous les médias
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Trouve un média par son ID
     */
    public function find(int $id): ?array
    {
        foreach ($this->data as $item) {
            if (($item['id'] ?? 0) === $id) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Trouve un média par son chemin
     */
    public function findByPath(string $path): ?array
    {
        foreach ($this->data as $item) {
            if (($item['path'] ?? '') === $path) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Trouve les médias par type
     */
    public function findByType(string $type): array
    {
        return array_values(array_filter($this->data, function($item) use ($type) {
            return ($item['type'] ?? '') === $type;
        }));
    }

    /**
     * Trouve les médias par dossier
     */
    public function findByFolder(string $folder): array
    {
        return array_values(array_filter($this->data, function($item) use ($folder) {
            return ($item['folder'] ?? '') === $folder;
        }));
    }

    /**
     * Crée un nouveau média
     */
    public function create(array $data): array
    {
        $before = $this->data;
        $data['id'] = $this->getNextId();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->data[] = $data;
        if (!$this->save()) {
            $this->data = $before;
            return [];
        }

        return $data;
    }

    /**
     * Met à jour un média
     */
    public function update(int $id, array $data): ?array
    {
        foreach ($this->data as $key => $item) {
            if (($item['id'] ?? 0) !== $id) {
                continue;
            }

            $before = $this->data;
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->data[$key] = array_merge($item, $data);
            if (!$this->save()) {
                $this->data = $before;
                return null;
            }

            return $this->data[$key];
        }
        return null;
    }

    /**
     * Supprime un média
     */
    public function delete(int $id): bool
    {
        foreach ($this->data as $key => $item) {
            if (($item['id'] ?? 0) !== $id) {
                continue;
            }

            $before = $this->data;
            unset($this->data[$key]);
            $this->data = array_values($this->data);
            if (!$this->save()) {
                $this->data = $before;
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Supprime par chemin
     */
    public function deleteByPath(string $path): bool
    {
        foreach ($this->data as $key => $item) {
            if (($item['path'] ?? '') !== $path) {
                continue;
            }

            $before = $this->data;
            unset($this->data[$key]);
            $this->data = array_values($this->data);
            if (!$this->save()) {
                $this->data = $before;
                return false;
            }
            return true;
        }
        return false;
    }

    public function replaceAll(array $data): bool
    {
        $before = $this->data;
        $this->data = array_values($data);
        if (!$this->save()) {
            $this->data = $before;
            return false;
        }
        return true;
    }

    /**
     * Vérifie si un chemin existe
     */
    public function exists(string $path): bool
    {
        return $this->findByPath($path) !== null;
    }

    /**
     * Statistiques par dossier
     */
    public function getStats(): array
    {
        $stats = [
            'images' => 0,
            'videos' => 0,
            'sounds' => 0,
            'documents' => 0,
            'pdf' => 0,
            'spreadsheets' => 0,
            'archives' => 0,
            'total' => count($this->data)
        ];

        foreach ($this->data as $item) {
            $folder = $item['folder'] ?? 'documents';
            if (isset($stats[$folder])) {
                $stats[$folder]++;
            }
        }

        return $stats;
    }

    /**
     * Compte le nombre de médias
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Compte par dossier
     */
    public function countByFolder(string $folder): int
    {
        return count($this->findByFolder($folder));
    }

    /**
     * Obtient le prochain ID disponible
     */
    private function getNextId(): int
    {
        $maxId = 0;
        foreach ($this->data as $item) {
            if (($item['id'] ?? 0) > $maxId) {
                $maxId = $item['id'];
            }
        }
        return $maxId + 1;
    }

    /**
     * Synchronise les médias avec les fichiers physiques
     * Supprime les entrées dont le fichier n'existe plus
     */
    public function sync(string $uploadPath): array
    {
        $before = $this->data;
        $removed = [];
        $added = [];

        foreach ($this->data as $key => $item) {
            $relativePath = trim((string) ($item['path'] ?? ''), '/');
            if ($relativePath === '') {
                $relativePath = trim((string) ($item['folder'] ?? ''), '/') . '/' . ltrim((string) ($item['name'] ?? ''), '/');
            }

            $fullPath = rtrim($uploadPath, '/') . '/' . $relativePath;
            if (!file_exists($fullPath)) {
                $removed[] = $item['name'] ?? 'unknown';
                unset($this->data[$key]);
            }
        }

        $this->data = array_values($this->data);
        if (!$this->save()) {
            $this->data = $before;
            return ['removed' => [], 'added' => [], 'success' => false];
        }

        return ['removed' => $removed, 'added' => $added, 'success' => true];
    }

    /**
     * Recherche de médias
     */
    public function search(string $query, ?string $folder = null): array
    {
        $query = strtolower($query);
        
        return array_values(array_filter($this->data, function($item) use ($query, $folder) {
            if ($folder && ($item['folder'] ?? '') !== $folder) {
                return false;
            }
            
            $name = strtolower($item['name'] ?? '');
            $originalName = strtolower($item['original_name'] ?? '');
            
            return str_contains($name, $query) || str_contains($originalName, $query);
        }));
    }
}
