<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Services\AI\Tools;

use App\Services\AI\Contracts\AiToolInterface;

final class ToolRegistry
{
    /** @var array<string, AiToolInterface> */
    private array $tools = [];

    /**
     * @param array<int, AiToolInterface> $tools
     */
    public function __construct(array $tools = [])
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }
    }

    public function register(AiToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): ?AiToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * @return array<int, AiToolInterface>
     */
    public function all(): array
    {
        return array_values($this->tools);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        $definitions = [];

        foreach ($this->all() as $tool) {
            $definitions[] = [
                'type' => 'function',
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => $tool->getParametersSchema(),
            ];
        }

        return $definitions;
    }
}
