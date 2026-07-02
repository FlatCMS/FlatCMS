<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\Studio\Services;

use App\Core\I18n;

final class StudioPreviewService
{
    private const SESSION_KEY = 'studio_preview_documents';
    private const MAX_PREVIEWS = 6;
    private const TTL_SECONDS = 1800;

    public function __construct(
        private readonly ?StudioRenderService $renderer = null,
        private readonly ?StudioPageSourceService $sourcePages = null
    ) {
    }

    public function store(array $sourcePage, array $document): string
    {
        $sourceId = trim((string) ($sourcePage['id'] ?? ''));
        if ($sourceId === '') {
            return '';
        }

        $store = $this->readStore();
        $now = time();
        $token = bin2hex(random_bytes(24));

        foreach ($store as $existingToken => $preview) {
            if (!is_array($preview)) {
                unset($store[$existingToken]);
                continue;
            }

            if ((string) ($preview['source_id'] ?? '') === $sourceId) {
                unset($store[$existingToken]);
            }
        }

        $store[$token] = [
            'source_id' => $sourceId,
            'document' => $document,
            'created_at' => $now,
            'expires_at' => $now + self::TTL_SECONDS,
        ];

        $this->writeStore($this->trimStore($store));

        return $token;
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $context
     * @return array<string, mixed>|null
     */
    public function buildRenderablePage(array $page, array $context = []): ?array
    {
        $record = $this->resolvePreviewRecord($page);
        if ($record === null) {
            return null;
        }

        $renderer = $this->renderer ?? new StudioRenderService();
        $sourcePages = $this->sourcePages ?? new StudioPageSourceService();
        $document = is_array($record['document'] ?? null) ? $record['document'] : [];

        $resolvedContext = $context;
        $resolvedContext['locale'] = trim((string) ($resolvedContext['locale'] ?? $page['locale'] ?? I18n::getLocale()));
        $resolvedContext['source_url'] = trim((string) ($resolvedContext['source_url'] ?? url($sourcePages->buildFrontendPath($page))));
        $resolvedContext['render_global_regions'] = false;

        return $renderer->buildRenderablePage($page, $document, $resolvedContext);
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, string>|null
     */
    public function buildPreviewNotice(array $page): ?array
    {
        if ($this->resolvePreviewRecord($page) === null) {
            return null;
        }

        I18n::load('Studio');

        return [
            'type' => 'warning',
            'message' => __('studio_preview_banner', 'Studio'),
        ];
    }

    /**
     * @param array<string, mixed> $page
     * @return array<string, mixed>|null
     */
    private function resolvePreviewRecord(array $page): ?array
    {
        $token = trim((string) app()->request()->input('studio_preview', ''));
        if ($token === '') {
            return null;
        }

        $store = $this->readStore();
        $record = $store[$token] ?? null;
        if (!is_array($record)) {
            return null;
        }

        $sourceId = trim((string) ($page['id'] ?? ''));
        if ($sourceId === '' || (string) ($record['source_id'] ?? '') !== $sourceId) {
            return null;
        }

        return $record;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readStore(): array
    {
        $raw = app()->session()->get(self::SESSION_KEY, []);
        if (!is_array($raw)) {
            $raw = [];
        }

        $clean = [];
        $changed = false;
        $now = time();

        foreach ($raw as $token => $preview) {
            if (!is_string($token) || !is_array($preview)) {
                $changed = true;
                continue;
            }

            $expiresAt = (int) ($preview['expires_at'] ?? 0);
            $sourceId = trim((string) ($preview['source_id'] ?? ''));
            if ($sourceId === '' || $expiresAt < $now) {
                $changed = true;
                continue;
            }

            $clean[$token] = $preview;
        }

        if ($changed) {
            $this->writeStore($clean);
        }

        return $clean;
    }

    /**
     * @param array<string, array<string, mixed>> $store
     * @return array<string, array<string, mixed>>
     */
    private function trimStore(array $store): array
    {
        uasort($store, static function (array $left, array $right): int {
            return (int) ($right['created_at'] ?? 0) <=> (int) ($left['created_at'] ?? 0);
        });

        if (count($store) <= self::MAX_PREVIEWS) {
            return $store;
        }

        return array_slice($store, 0, self::MAX_PREVIEWS, true);
    }

    /**
     * @param array<string, array<string, mixed>> $store
     */
    private function writeStore(array $store): void
    {
        app()->session()->set(self::SESSION_KEY, $store);
    }
}
