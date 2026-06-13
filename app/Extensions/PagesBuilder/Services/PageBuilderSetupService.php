<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Services;

use App\Core\FlatFile;
use App\Modules\Pages\Services\PageTranslationService;

final class PageBuilderSetupService
{
    private const SETTINGS_KEY = 'extensions/pages-builder/setup';

    private PageTranslationService $translations;
    private PageBuilderStateService $states;

    public function __construct(?PageTranslationService $translations = null, ?PageBuilderStateService $states = null)
    {
        $this->translations = $translations ?? new PageTranslationService(FlatFile::for('core/pages'));
        $this->states = $states ?? new PageBuilderStateService();
    }

    /**
     * @return array<string, mixed>
     */
    public function state(): array
    {
        $saved = FlatFile::settings(self::SETTINGS_KEY);
        $status = trim((string) ($saved['status'] ?? ''));
        if (!in_array($status, ['not_initialized', 'ready'], true)) {
            $status = 'not_initialized';
        }

        return [
            'status' => $status,
            'mode' => trim((string) ($saved['mode'] ?? '')),
            'initialized_at' => trim((string) ($saved['initialized_at'] ?? '')),
            'initialized_by' => trim((string) ($saved['initialized_by'] ?? '')),
            'existing_pages_count' => $this->countSourcePages(),
            'managed_pages_count' => $this->countManagedPages(),
        ];
    }

    public function isReady(): bool
    {
        return (string) ($this->state()['status'] ?? 'not_initialized') === 'ready';
    }

    /**
     * @param array<string, mixed>|null $user
     */
    public function initializeByConvertingExistingPages(?array $user = null): array
    {
        $converted = 0;
        foreach ($this->sourcePages() as $page) {
            if (!is_array($page)) {
                continue;
            }

            $existing = $this->states->stateForPage($page);
            if (!empty($existing['exists'])) {
                continue;
            }

            $this->states->saveStateForPage($page, [
                'active' => false,
                'builder' => $this->states->buildInitialBuilderForPage($page),
            ], $user);
            $converted++;
        }

        $this->persistReadyState('converted', $user, $converted);
        return $this->state();
    }

    /**
     * @param array<string, mixed>|null $user
     */
    public function initializeEmpty(?array $user = null): array
    {
        $this->persistReadyState('empty', $user, 0);
        return $this->state();
    }

    private function countSourcePages(): int
    {
        return count($this->sourcePages());
    }

    private function countManagedPages(): int
    {
        $count = 0;
        foreach (FlatFile::for('extensions/pages-builder/pages')->all() as $row) {
            if (is_array($row) && trim((string) ($row['page_id'] ?? '')) !== '') {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sourcePages(): array
    {
        $rows = [];
        $seenGroups = [];

        foreach ($this->translations->all() as $page) {
            if (!is_array($page)) {
                continue;
            }

            $normalized = $this->translations->normalizePage($page);
            $group = trim((string) ($normalized['translation_group'] ?? $normalized['id'] ?? ''));
            if ($group === '' || isset($seenGroups[$group])) {
                continue;
            }

            $source = $this->translations->resolveSourcePage($group);
            if (!is_array($source)) {
                $source = $normalized;
            }

            $rows[] = $source;
            $seenGroups[$group] = true;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed>|null $user
     */
    private function persistReadyState(string $mode, ?array $user, int $convertedCount): void
    {
        FlatFile::saveSettings([
            'status' => 'ready',
            'mode' => $mode,
            'converted_count' => $convertedCount,
            'initialized_at' => date('Y-m-d H:i:s'),
            'initialized_by' => trim((string) ($user['id'] ?? '')),
        ], self::SETTINGS_KEY);
    }
}
