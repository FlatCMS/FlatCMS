<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Menu\Services;

use App\Core\FlatFile;

final class MenuSyncService
{
    private static array $previous = [
        'page' => [],
        'post' => [],
        'category' => [],
    ];

    public static function captureBeforeSave(string $type, array $payload): void
    {
        $id = trim((string) ($payload['id'] ?? ''));
        if ($id === '') {
            return;
        }
        $record = self::loadRecord($type, $id);
        if (!$record) {
            return;
        }
        self::$previous[$type][$id] = [
            'slug' => (string) ($record['slug'] ?? ''),
            'title' => (string) ($record['title'] ?? ''),
            'name' => (string) ($record['name'] ?? ''),
            'module' => (string) ($record['module'] ?? ''),
            'label' => self::resolveLabel(
                $type,
                (string) ($record['title'] ?? ''),
                (string) ($record['name'] ?? ''),
                (string) ($record['module'] ?? '')
            ),
        ];
    }

    public static function syncAfterSave(string $type, array $payload): void
    {
        $id = trim((string) ($payload['id'] ?? ''));
        if ($id === '') {
            return;
        }

        $slug = trim((string) ($payload['slug'] ?? ''));
        $module = trim((string) ($payload['module'] ?? ''));

        $old = self::$previous[$type][$id] ?? [];
        $oldSlug = trim((string) ($old['slug'] ?? ''));
        $oldModule = trim((string) ($old['module'] ?? ''));
        $oldTitle = trim((string) ($old['title'] ?? ''));
        $oldName = trim((string) ($old['name'] ?? ''));
        $oldLabel = trim((string) ($old['label'] ?? self::resolveLabel($type, $oldTitle, $oldName, $oldModule)));
        $oldUrl = self::buildUrl($type, $oldSlug, $oldModule, $id);
        $newUrl = self::buildUrl($type, $slug, $module, $id);
        $newTitle = trim((string) ($payload['title'] ?? $oldTitle));
        $newName = trim((string) ($payload['name'] ?? $oldName));
        $newLabel = self::resolveLabel(
            $type,
            $newTitle !== '' ? $newTitle : $oldTitle,
            $newName !== '' ? $newName : $oldName,
            $module !== '' ? $module : $oldModule
        );

        $menus = FlatFile::settings('menus');
        if (!is_array($menus)) {
            return;
        }

        $changed = self::syncMenus($menus, $type, $id, $slug, $oldSlug, $newUrl, $oldUrl, $newLabel, $oldLabel);
        if ($changed) {
            FlatFile::saveSettings($menus, 'menus');
        }
    }

    private static function resolveLabel(string $type, string $title, string $name, string $module): string
    {
        $safeType = strtolower(trim($type));
        if ($safeType === 'category') {
            $safeName = trim($name);
            if ($safeName === '') {
                return '';
            }

            return $safeName;
        }

        $safeTitle = trim($title);
        if ($safeTitle !== '') {
            return $safeTitle;
        }

        return trim($name);
    }

    private static function loadRecord(string $type, string $id): ?array
    {
        $path = match ($type) {
            'post' => 'core/posts',
            'category' => 'core/categories',
            default => 'core/pages',
        };
        $store = FlatFile::for($path);
        $record = $store->find($id);
        return is_array($record) ? $record : null;
    }

    private static function buildUrl(string $type, string $slug, string $module = '', string $id = ''): string
    {
        if ($slug === '') {
            return '';
        }
        if ($type === 'page') {
            if ($id !== '' && class_exists(\App\Modules\Settings\Services\SiteRoutingService::class)) {
                $record = self::loadRecord('page', $id);
                if (
                    is_array($record)
                    && (new \App\Modules\Settings\Services\SiteRoutingService())->isHomepagePage($record)
                ) {
                    return '';
                }
            }
            return $slug === 'home' ? '' : '/page/' . $slug;
        }
        if ($type === 'post') {
            return '/blog/' . $slug;
        }
        if ($type === 'category') {
            return match ($module !== '' ? $module : 'blog') {
                'blog' => '/blog/categorie/' . $slug,
                'downloads' => '/downloads/categorie/' . $slug,
                default => '',
            };
        }
        return '';
    }

    private static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $path = $url;
        $parsed = parse_url($url);
        if (is_array($parsed) && isset($parsed['path'])) {
            $path = (string) $parsed['path'];
        }

        $path = preg_replace('~[?#].*$~', '', (string) $path);
        if ($path === '/' || $path === '') {
            return '';
        }
        $path = rtrim($path, '/');
        $path = preg_replace('~^/([a-z]{2}(?:-[A-Za-z]{2})?)(?=/|$)~', '', $path);
        if ($path === '' || $path === '/') {
            return '';
        }

        if (preg_match('~(/page/[^/]+|/blog/[^/]+|/blog|/(?:blog|downloads)/categorie/[^/]+)$~', $path, $match)) {
            return $match[1];
        }

        return $path;
    }

    private static function syncMenus(array &$menus, string $type, string $id, string $newSlug, string $oldSlug, string $newUrl, string $oldUrl, string $newLabel, string $oldLabel): bool
    {
        $changed = false;

        if (isset($menus['main']['items']) && is_array($menus['main']['items'])) {
            foreach ($menus['main']['items'] as $idx => $item) {
                if (!is_array($item)) {
                    continue;
                }
                if (self::syncItem($item, $type, $id, $newSlug, $oldSlug, $newUrl, $oldUrl, $newLabel, $oldLabel)) {
                    $menus['main']['items'][$idx] = $item;
                    $changed = true;
                }
            }
        }

        return $changed;
    }

    private static function syncItem(array &$item, string $type, string $id, string $newSlug, string $oldSlug, string $newUrl, string $oldUrl, string $newLabel, string $oldLabel): bool
    {
        $itemUrl = (string) ($item['url'] ?? '');
        $refType = (string) ($item['refType'] ?? '');
        $ref = (string) ($item['ref'] ?? '');
        $normItemUrl = self::normalizeUrl($itemUrl);
        $normOldUrl = self::normalizeUrl($oldUrl);
        $normNewUrl = self::normalizeUrl($newUrl);

        $matched = false;
        if ($refType === $type && $ref !== '') {
            if ($ref === $id || ($oldSlug !== '' && $ref === $oldSlug) || ($newSlug !== '' && $ref === $newSlug)) {
                $matched = true;
            }
        }

        if (!$matched && $oldUrl !== '' && $itemUrl === $oldUrl) {
            $matched = true;
        }
        if (!$matched && $newUrl !== '' && $itemUrl === $newUrl) {
            $matched = true;
        }
        if (!$matched && $normOldUrl !== '' && $normItemUrl === $normOldUrl) {
            $matched = true;
        }
        if (!$matched && $normNewUrl !== '' && $normItemUrl === $normNewUrl) {
            $matched = true;
        }
        if (!$matched && $normItemUrl === '' && ($newSlug === 'home' || $oldSlug === 'home')) {
            $matched = true;
        }

        if (!$matched) {
            return false;
        }

        $changed = false;

        if ($refType !== $type || $ref !== $id) {
            $item['refType'] = $type;
            $item['ref'] = $id;
            $changed = true;
        }

        if ($itemUrl !== $newUrl) {
            $item['url'] = $newUrl;
            $changed = true;
        }

        if (self::shouldSyncItemLabel($item, $newLabel, $oldLabel) && (string) ($item['label'] ?? '') !== $newLabel) {
            $item['label'] = $newLabel;
            $changed = true;
        }

        return $changed;
    }

    private static function shouldSyncItemLabel(array $item, string $newLabel, string $oldLabel): bool
    {
        $safeNewLabel = trim($newLabel);
        if ($safeNewLabel === '') {
            return false;
        }

        $labelMode = strtolower(trim((string) ($item['labelMode'] ?? '')));
        if ($labelMode === 'custom') {
            return false;
        }
        if ($labelMode === 'auto') {
            return true;
        }

        $currentLabel = trim((string) ($item['label'] ?? ''));
        if ($currentLabel === '') {
            return true;
        }

        $safeOldLabel = trim($oldLabel);
        return $safeOldLabel !== '' && $currentLabel === $safeOldLabel;
    }
}
