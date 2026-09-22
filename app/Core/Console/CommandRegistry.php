<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/Console/CommandRegistry.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Core\Console;

final class CommandRegistry
{
    private array $commands = [];

    public function register(string $name, string $usage, callable $handler, int $errorCode = 2): void
    {
        if (!preg_match('/^[a-z][a-z0-9:-]*$/D', $name) || isset($this->commands[$name])) {
            throw new \InvalidArgumentException('cli_command_invalid');
        }
        $this->commands[$name] = compact('usage', 'handler', 'errorCode');
    }

    /** JSON protocol only: no secrets or HTML, predictable exit codes for automation. */
    public function run(array $arguments, $input, $output, $error): int
    {
        $name = array_shift($arguments) ?? 'help';
        if (in_array($name, ['help', '--help', '-h'], true)) {
            $this->emit($output, ['ok' => true, 'commands' => array_map(static fn ($item) => $item['usage'], $this->commands)]);
            return 0;
        }
        if (!isset($this->commands[$name])) {
            $this->emit($error, ['ok' => false, 'error' => 'cli_unknown_command']);
            return 1;
        }
        $command = $this->commands[$name];
        try {
            $result = ($command['handler'])($arguments, $input);
            $this->emit($output, $result);
            return ($result['ok'] ?? true) ? 0 : 2;
        } catch (\Throwable $exception) {
            // Install input includes a password; exception payloads must never echo input.
            $code = preg_match('/^[a-z][a-z0-9_.:-]*$/D', $exception->getMessage()) ? $exception->getMessage() : 'cli_operation_failed';
            $this->emit($error, ['ok' => false, 'error' => $code]);
            return $command['errorCode'];
        }
    }

    private function emit($stream, array $value): void
    {
        fwrite($stream, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);
    }
}
