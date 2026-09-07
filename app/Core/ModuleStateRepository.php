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

use App\Core\Storage\AtomicFileWriter;
use App\Core\Storage\FileLockManager;
use App\Core\Storage\JsonStore;
use App\Core\Storage\StorageException;

final class ModuleStateRepository
{
    private JsonStore $store;
    private string $recordPath;

    public function __construct(?string $path = null, ?JsonStore $store = null, ?string $lockRoot = null)
    {
        $path ??= BASE_PATH . '/data/modules.json';

        if ($store === null) {
            $stateRoot = dirname($path);
            $instanceRoot = basename($stateRoot) === 'data' ? dirname($stateRoot) : $stateRoot;
            $lockRoot ??= $instanceRoot . '/storage/cache/locks/module-state';
            $store = new JsonStore($stateRoot, new AtomicFileWriter(
                $stateRoot,
                new FileLockManager($lockRoot)
            ));
        }

        $this->store = $store;
        $this->recordPath = basename($path);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->store->read($this->recordPath, []);
    }

    /**
     * @param array<string, array<string, mixed>> $changes
     * @return array<string, mixed>
     */
    public function merge(array $changes): array
    {
        $this->assertChanges($changes);

        return $this->store->mutate($this->recordPath, static function (array $state) use ($changes): array {
            foreach ($changes as $module => $patch) {
                $entry = is_array($state[$module] ?? null) ? $state[$module] : [];
                foreach ($patch as $flag => $value) {
                    $entry[$flag] = $value;
                }
                $state[$module] = $entry;
            }

            return $state;
        }, []);
    }

    /**
     * @return array<string, mixed>
     */
    public function remove(string $module): array
    {
        $this->assertModuleName($module);

        return $this->store->mutate($this->recordPath, static function (array $state) use ($module): array {
            unset($state[$module]);

            return $state;
        }, []);
    }

    /**
     * @param array<string, array<string, mixed>> $changes
     */
    private function assertChanges(array $changes): void
    {
        foreach ($changes as $module => $patch) {
            $this->assertModuleName((string) $module);
            if (!is_array($patch) || $patch === []) {
                throw new StorageException('Module state changes must contain a non-empty patch.');
            }
        }
    }

    private function assertModuleName(string $module): void
    {
        if (trim($module) === '' || str_contains($module, "\0")) {
            throw new StorageException('Module state requires a non-empty module name.');
        }
    }
}
