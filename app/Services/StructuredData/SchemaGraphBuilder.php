<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\StructuredData;

class SchemaGraphBuilder
{
    /** @var array<string, array<string, mixed>> */
    private array $nodes = [];

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    public function addNodes(array $nodes): void
    {
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $this->addNode($node);
        }
    }

    /**
     * @param array<string, mixed> $node
     */
    public function addNode(array $node): void
    {
        $sanitized = $this->sanitizeValue($node);
        if (!is_array($sanitized) || $sanitized === []) {
            return;
        }

        $identifier = trim((string) ($sanitized['@id'] ?? ''));
        if ($identifier === '') {
            $identifier = 'hash:' . sha1(json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        }

        if (isset($this->nodes[$identifier])) {
            $this->nodes[$identifier] = array_replace_recursive($this->nodes[$identifier], $sanitized);
            return;
        }

        $this->nodes[$identifier] = $sanitized;
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        if ($this->nodes === []) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values($this->nodes),
        ];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $isList = array_is_list($value);
            $result = [];
            foreach ($value as $key => $item) {
                $sanitized = $this->sanitizeValue($item);
                if ($sanitized === null || $sanitized === '' || $sanitized === []) {
                    continue;
                }
                if ($isList) {
                    $result[] = $sanitized;
                } else {
                    $result[(string) $key] = $sanitized;
                }
            }
            return $result;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        }

        return $value;
    }
}

