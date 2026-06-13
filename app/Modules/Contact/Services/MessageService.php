<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Contact\Services;

use App\Core\FlatFile;

class MessageService
{
    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_ARCHIVED = 'archived';

    private FlatFile $storage;

    public function __construct()
    {
        $this->storage = FlatFile::for('core/contact_messages');
    }

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_READ,
            self::STATUS_ARCHIVED,
        ];
    }

    public function all(): array
    {
        $items = $this->storage->all();

        usort($items, static fn(array $a, array $b): int => ($b['created_at'] ?? '') <=> ($a['created_at'] ?? ''));

        return array_values($items);
    }

    public function find(string $id): ?array
    {
        return $this->storage->find($id);
    }

    public function create(array $payload): array
    {
        if (empty($payload['status']) || !in_array((string) $payload['status'], self::allowedStatuses(), true)) {
            $payload['status'] = self::STATUS_NEW;
        }

        return $this->storage->create($payload);
    }

    public function updateStatus(string $id, string $status): ?array
    {
        if (!in_array($status, self::allowedStatuses(), true)) {
            return null;
        }

        return $this->storage->update($id, ['status' => $status]);
    }

    public function delete(string $id): bool
    {
        return $this->storage->delete($id);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function filter(array $items, string $status = 'all', string $query = ''): array
    {
        $status = trim($status);
        $query = trim($query);

        if ($status !== '' && $status !== 'all') {
            $items = array_filter($items, static function (array $item) use ($status): bool {
                return (string) ($item['status'] ?? self::STATUS_NEW) === $status;
            });
        }

        if ($query !== '') {
            $needle = mb_strtolower($query);
            $items = array_filter($items, static function (array $item) use ($needle): bool {
                $haystacks = [
                    (string) ($item['name'] ?? ''),
                    (string) ($item['email'] ?? ''),
                    (string) ($item['subject'] ?? ''),
                    (string) ($item['message'] ?? ''),
                ];

                foreach ($haystacks as $haystack) {
                    if (str_contains(mb_strtolower($haystack), $needle)) {
                        return true;
                    }
                }

                return false;
            });
        }

        return array_values($items);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, int|bool|array<int, array<string, mixed>>>
     */
    public function paginate(array $items, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $total = count($items);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        return [
            'data' => array_slice($items, $offset, $perPage),
            'total' => $total,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_more' => $page < $totalPages,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, int>
     */
    public function counts(array $items): array
    {
        $counts = [
            'all' => count($items),
            self::STATUS_NEW => 0,
            self::STATUS_READ => 0,
            self::STATUS_ARCHIVED => 0,
        ];

        foreach ($items as $item) {
            $status = (string) ($item['status'] ?? self::STATUS_NEW);
            if (!isset($counts[$status])) {
                continue;
            }
            $counts[$status]++;
        }

        return $counts;
    }
}
