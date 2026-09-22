<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/StorageMaintenance.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Core;

final class StorageMaintenance
{
    public function verify(): array
    {
        $report = (new InstallationDoctor())->diagnose();
        $checks = array_values(array_filter($report['checks'], static fn ($check) => in_array($check['code'], [
            'data_json_valid', 'content_documents_complete', 'portable_trees_without_symlinks',
        ], true)));
        $pending = $this->pendingJournals();
        $checks[] = ['code' => 'storage_transactions_settled', 'ok' => $pending === [], 'details' => ['pending' => $pending]];
        return ['ok' => !in_array(false, array_column($checks, 'ok'), true), 'checks' => $checks];
    }

    /** Replay only registered journals, while application workers are stopped. Never invent metadata. */
    public function recoverContent(): array
    {
        return \App\Core\Storage\ApplicationLock::for(BASE_PATH)->exclusive(
            fn (): array => $this->recoverUnderLease(), ['full-restoration', 'site-restoration']
        );
    }

    private function recoverUnderLease(): array
    {
        $paths = new \App\Core\Storage\StoragePathGuard(BASE_PATH);
        foreach (['full-restoration', 'site-restoration'] as $scope) {
            if (is_file($paths->resolve('storage/transactions/stream-files/' . $scope . '/active.json'))) {
                (new \App\Core\Storage\StreamFileTransaction(BASE_PATH, $scope))->recover();
                ContentDocumentStore::resetRequestCache();
                \App\Core\Storage\JsonStore::resetRequestCache(BASE_PATH . '/data');
            }
        }
        foreach (['core/pages', 'core/posts'] as $entity) {
            ContentDocumentStore::for($entity)->recover();
        }
        return $this->verify();
    }

    private function pendingJournals(): array
    {
        $pending = [];
        $paths = new \App\Core\Storage\StoragePathGuard(BASE_PATH);
        foreach (['storage/transactions', 'storage/trash/media-transactions'] as $relative) {
            $path = $paths->resolve($relative);
            if (!is_dir($path)) { continue; }
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $entry) {
                if ($entry->isLink() || ($entry->isFile() && $entry->getExtension() === 'json')) {
                    $pending[] = substr($entry->getPathname(), strlen(BASE_PATH) + 1);
                }
            }
        }
        sort($pending);
        return $pending;
    }
}
