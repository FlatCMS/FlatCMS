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

    public function __construct()
    {
        $basePath = $this->resolveBasePath();
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
        $this->data = json_decode($content, true) ?: [];
    }

    /**
     * Sauvegarde les données dans le fichier JSON
     */
    private function save(): bool
    {
        return file_put_contents(
            $this->dataFile, 
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ) !== false;
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
        $data['id'] = $this->getNextId();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->data[] = $data;
        $this->save();
        
        return $data;
    }

    /**
     * Met à jour un média
     */
    public function update(int $id, array $data): ?array
    {
        foreach ($this->data as $key => $item) {
            if (($item['id'] ?? 0) === $id) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $this->data[$key] = array_merge($item, $data);
                $this->save();
                return $this->data[$key];
            }
        }
        return null;
    }

    /**
     * Supprime un média
     */
    public function delete(int $id): bool
    {
        foreach ($this->data as $key => $item) {
            if (($item['id'] ?? 0) === $id) {
                unset($this->data[$key]);
                $this->data = array_values($this->data);
                return $this->save();
            }
        }
        return false;
    }

    /**
     * Supprime par chemin
     */
    public function deleteByPath(string $path): bool
    {
        foreach ($this->data as $key => $item) {
            if (($item['path'] ?? '') === $path) {
                unset($this->data[$key]);
                $this->data = array_values($this->data);
                return $this->save();
            }
        }
        return false;
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
        $removed = [];
        $added = [];
        
        // Supprimer les entrées sans fichier physique
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
        $this->save();
        
        return [
            'removed' => $removed,
            'added' => $added
        ];
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
