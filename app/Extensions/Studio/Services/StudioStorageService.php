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

use RuntimeException;

final class StudioStorageService
{
    public function __construct(
        private readonly StudioSchemaService $schema,
        private readonly ?StudioStructureImportService $structureImport = null
    ) {
    }

    public function loadPageForSource(array $sourcePage): array
    {
        $path = $this->pagePathForSource($sourcePage);
        if (!is_file($path)) {
            return $this->seedImportedStructure($this->schema->defaultPage($sourcePage), $sourcePage);
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return $this->seedImportedStructure($this->schema->defaultPage($sourcePage), $sourcePage);
        }

        return $this->seedImportedStructure(
            $this->repairLegacyImportedContent(
                $this->schema->normalizePage($decoded, $sourcePage),
                $sourcePage
            ),
            $sourcePage
        );
    }

    public function savePageForSource(array $sourcePage, array $payload): array
    {
        $page = $this->schema->normalizePage($payload, $sourcePage);
        $page['page']['updated_at'] = gmdate('c');

        $path = $this->pagePathForSource($sourcePage);
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('studio_storage_directory_create_failed');
        }

        $json = json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || @file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('studio_storage_write_failed');
        }

        return $page;
    }

    public function pagePathForSource(array $sourcePage): string
    {
        $base = defined('DATA_PATH') ? (string) DATA_PATH : BASE_PATH . '/data';
        $entityId = $this->sourceEntityId($sourcePage);
        return rtrim($base, '/') . '/extensions/studio/pages/' . $entityId . '.json';
    }

    private function sourceEntityId(array $sourcePage): string
    {
        $rawId = trim((string) ($sourcePage['id'] ?? ''));
        $cleanId = preg_replace('/[^a-zA-Z0-9_-]/', '', $rawId) ?? '';

        if ($cleanId === '') {
            throw new RuntimeException('studio_storage_missing_source_id');
        }

        return $cleanId;
    }

    private function repairLegacyImportedContent(array $page, array $sourcePage): array
    {
        $sourceHtml = trim((string) ($sourcePage['content'] ?? ''));
        if ($sourceHtml === '' || preg_match('/<[^>]+>/', $sourceHtml) !== 1) {
            return $page;
        }

        $sourceText = $this->compactText(strip_tags($sourceHtml));
        if ($sourceText === '') {
            return $page;
        }

        $sections = is_array($page['sections'] ?? null) ? $page['sections'] : [];
        foreach ($sections as $index => $section) {
            if (!is_array($section) || trim((string) ($section['type'] ?? '')) !== 'content') {
                continue;
            }

            $settings = is_array($section['settings'] ?? null) ? $section['settings'] : [];
            $storedHtml = trim((string) ($settings['html'] ?? ''));
            $storedBlocks = is_array($section['blocks'] ?? null) ? $section['blocks'] : [];
            if ($storedHtml === '' || preg_match('/<[^>]+>/', $storedHtml) === 1) {
                $effectiveHtml = $storedHtml;
            } else {
                if ($this->compactText($storedHtml) !== $sourceText) {
                    continue;
                }

                $page['sections'][$index]['settings']['html'] = $sourceHtml;
                $effectiveHtml = $sourceHtml;
            }

            if ($storedBlocks === [] && $effectiveHtml !== '') {
                $importedBlocks = $this->schema->importContentBlocks($effectiveHtml);
                if ($importedBlocks !== []) {
                    $page['sections'][$index]['blocks'] = $importedBlocks;
                }
            }
        }

        return $page;
    }

    private function seedImportedStructure(array $page, array $sourcePage): array
    {
        $import = $this->structureImport?->buildForSource($sourcePage);
        if (!is_array($import)) {
            return $page;
        }

        if (!$this->navbarHasContent($page['navbar'] ?? [])) {
            $page['navbar'] = is_array($import['navbar'] ?? null) ? $import['navbar'] : ($page['navbar'] ?? []);
        }

        $currentLayout = is_array($page['layout'] ?? null) ? $page['layout'] : [];
        $importedLayout = is_array($import['layout'] ?? null) ? $import['layout'] : [];

        foreach (['header_before', 'header_after', 'footer'] as $regionName) {
            if ($this->layoutRegionHasBlocks($currentLayout, $regionName)) {
                continue;
            }

            if ($this->layoutRegionHasBlocks($importedLayout, $regionName)) {
                $page['layout'][$regionName] = $importedLayout[$regionName];
            }
        }

        $page = $this->repairLegacyNavbarStructure($page, $import);
        $page = $this->repairLegacyFooterStructure($page);
        $page = $this->syncManagedFooterStructure($page, $import);

        return $this->schema->normalizePage($page, $sourcePage);
    }

    private function repairLegacyNavbarStructure(array $page, array $import): array
    {
        $page = $this->ensureManagedNavbarBrand($page, $import);
        $currentNavbar = is_array($page['navbar'] ?? null) ? $page['navbar'] : [];
        $importedNavbar = is_array($import['navbar'] ?? null) ? $import['navbar'] : [];
        $currentBrandIndex = $this->findMainBrandIndex($currentNavbar);
        $importedBrandIndex = $this->findMainBrandIndex($importedNavbar);

        if ($currentBrandIndex !== null && $importedBrandIndex !== null) {
            $importedBrand = $import['navbar']['rows']['main']['left'][$importedBrandIndex] ?? [];
            $page['navbar']['rows']['main']['left'][$currentBrandIndex] = array_merge(
                is_array($page['navbar']['rows']['main']['left'][$currentBrandIndex] ?? null)
                    ? $page['navbar']['rows']['main']['left'][$currentBrandIndex]
                    : [],
                [
                    'id' => StudioStructureImportService::MANAGED_NAVBAR_BRAND_ID,
                    'label' => (string) ($importedBrand['label'] ?? ''),
                    'subtitle' => (string) ($importedBrand['subtitle'] ?? ''),
                    'src' => (string) ($importedBrand['src'] ?? ''),
                    'alt' => (string) ($importedBrand['alt'] ?? ''),
                    'variant' => (string) ($importedBrand['variant'] ?? 'compact'),
                ]
            );
        }

        $importedBrandMeta = is_array($import['navbar']['brand'] ?? null) ? $import['navbar']['brand'] : [];
        if (!isset($page['navbar']) || !is_array($page['navbar'])) {
            $page['navbar'] = [];
        }
        if (!isset($page['navbar']['brand']) || !is_array($page['navbar']['brand'])) {
            $page['navbar']['brand'] = [];
        }
        foreach (['label', 'subtitle', 'variant'] as $key) {
            if (array_key_exists($key, $importedBrandMeta)) {
                $page['navbar']['brand'][$key] = (string) $importedBrandMeta[$key];
            }
        }

        return $this->removeLegacyNavbarSlogan($page, (string) ($importedBrandMeta['subtitle'] ?? ''));
    }

    private function repairLegacyFooterStructure(array $page): array
    {
        $footerBlocks = is_array($page['layout']['footer']['blocks'] ?? null)
            ? $page['layout']['footer']['blocks']
            : [];
        $footerBlocks = $this->tagLegacyManagedFooterBlocks($footerBlocks);

        foreach ($footerBlocks as $index => $block) {
            if (!is_array($block) || ($block['type'] ?? '') !== 'image') {
                continue;
            }

            $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];
            if (trim((string) ($settings['height'] ?? '')) === '180') {
                $footerBlocks[$index]['settings']['height'] = 'auto';
            }
        }

        $page['layout']['footer']['blocks'] = $footerBlocks;

        return $page;
    }

    private function syncManagedFooterStructure(array $page, array $import): array
    {
        $currentBlocks = is_array($page['layout']['footer']['blocks'] ?? null)
            ? $page['layout']['footer']['blocks']
            : [];
        $importedBlocks = is_array($import['layout']['footer']['blocks'] ?? null)
            ? $import['layout']['footer']['blocks']
            : [];
        $managedIds = $this->managedFooterBlockIds();

        $managedBlocks = array_values(array_filter($importedBlocks, function ($block) use ($managedIds): bool {
            return is_array($block)
                && in_array(trim((string) ($block['id'] ?? '')), $managedIds, true);
        }));
        $customBlocks = array_values(array_filter($currentBlocks, function ($block) use ($managedIds): bool {
            return !is_array($block)
                || !in_array(trim((string) ($block['id'] ?? '')), $managedIds, true);
        }));

        $page['layout']['footer']['blocks'] = array_merge($managedBlocks, $customBlocks);

        return $page;
    }

    /**
     * @param array<string, mixed> $navbar
     */
    private function findMainBrandIndex(array $navbar): ?int
    {
        $rows = is_array($navbar['rows'] ?? null) ? $navbar['rows'] : [];
        $mainLeft = is_array($rows['main']['left'] ?? null) ? $rows['main']['left'] : [];

        foreach ($mainLeft as $index => $element) {
            if (is_array($element) && ($element['kind'] ?? '') === 'brand') {
                return $index;
            }
        }

        return null;
    }

    private function ensureManagedNavbarBrand(array $page, array $import): array
    {
        if (!isset($page['navbar']['rows']['main']['left']) || !is_array($page['navbar']['rows']['main']['left'])) {
            $page['navbar']['rows']['main']['left'] = [];
        }

        $currentBrandIndex = $this->findMainBrandIndex(is_array($page['navbar'] ?? null) ? $page['navbar'] : []);
        if ($currentBrandIndex === null) {
            $importedNavbar = is_array($import['navbar'] ?? null) ? $import['navbar'] : [];
            $importedBrandIndex = $this->findMainBrandIndex($importedNavbar);
            if ($importedBrandIndex !== null) {
                array_unshift(
                    $page['navbar']['rows']['main']['left'],
                    $import['navbar']['rows']['main']['left'][$importedBrandIndex]
                );
                $currentBrandIndex = 0;
            }
        }

        if ($currentBrandIndex !== null) {
            $page['navbar']['rows']['main']['left'][$currentBrandIndex]['id'] = StudioStructureImportService::MANAGED_NAVBAR_BRAND_ID;
        }

        return $page;
    }

    private function removeLegacyNavbarSlogan(array $page, string $importedSubtitle): array
    {
        $rightZone = is_array($page['navbar']['rows']['main']['right'] ?? null)
            ? $page['navbar']['rows']['main']['right']
            : [];

        if (count($rightZone) !== 1 || !is_array($rightZone[0]) || ($rightZone[0]['kind'] ?? '') !== 'slogan') {
            return $page;
        }

        $legacyText = trim((string) ($rightZone[0]['text'] ?? ''));
        if ($legacyText !== '' && trim($importedSubtitle) !== '' && $legacyText !== trim($importedSubtitle)) {
            return $page;
        }

        $page['navbar']['rows']['main']['right'] = [];

        return $page;
    }

    /**
     * @param array<int, mixed> $blocks
     * @return array<int, mixed>
     */
    private function tagLegacyManagedFooterBlocks(array $blocks): array
    {
        if ($this->footerHasManagedBlockIds($blocks)) {
            return $blocks;
        }

        $cursor = 0;
        $managedIds = $this->managedFooterBlockIds();

        if (isset($blocks[$cursor]) && is_array($blocks[$cursor]) && ($blocks[$cursor]['type'] ?? '') === 'image') {
            $blocks[$cursor]['id'] = $managedIds[0];
            $cursor++;
        }

        if (isset($blocks[$cursor]) && is_array($blocks[$cursor]) && ($blocks[$cursor]['type'] ?? '') === 'text') {
            $blocks[$cursor]['id'] = $managedIds[1];
            $cursor++;
        }

        if (isset($blocks[$cursor]) && is_array($blocks[$cursor]) && ($blocks[$cursor]['type'] ?? '') === 'text') {
            $blocks[$cursor]['id'] = $managedIds[2];
            $cursor++;
        }

        if (isset($blocks[$cursor]) && is_array($blocks[$cursor]) && ($blocks[$cursor]['type'] ?? '') === 'button') {
            $blocks[$cursor]['id'] = $managedIds[3];
        }

        return $blocks;
    }

    /**
     * @param array<int, mixed> $blocks
     */
    private function footerHasManagedBlockIds(array $blocks): bool
    {
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            if (in_array(trim((string) ($block['id'] ?? '')), $this->managedFooterBlockIds(), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function managedFooterBlockIds(): array
    {
        return [
            StudioStructureImportService::MANAGED_FOOTER_LOGO_ID,
            StudioStructureImportService::MANAGED_FOOTER_BRAND_ID,
            StudioStructureImportService::MANAGED_FOOTER_COPYRIGHT_ID,
            StudioStructureImportService::MANAGED_FOOTER_POWERED_ID,
        ];
    }

    private function navbarHasContent(mixed $navbar): bool
    {
        if (!is_array($navbar)) {
            return false;
        }

        if (trim((string) ($navbar['brand']['label'] ?? '')) !== '') {
            return true;
        }

        if (is_array($navbar['items'] ?? null) && $navbar['items'] !== []) {
            return true;
        }

        $rows = is_array($navbar['rows'] ?? null) ? $navbar['rows'] : [];
        foreach (['top', 'main', 'bottom'] as $rowName) {
            foreach (['left', 'center', 'right'] as $zoneName) {
                if (is_array($rows[$rowName][$zoneName] ?? null) && $rows[$rowName][$zoneName] !== []) {
                    return true;
                }
            }
        }

        return false;
    }

    private function layoutRegionHasBlocks(array $layout, string $regionName): bool
    {
        return is_array($layout[$regionName]['blocks'] ?? null) && $layout[$regionName]['blocks'] !== [];
    }

    private function compactText(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        return trim($normalized);
    }
}
