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

final class Hook
{
    private static array $definitions = [];
    private static array $listeners = [];
    private static bool $loaded = false;

    public static function define(string $hook, array $definition = []): void
    {
        $hook = trim($hook);
        if ($hook === '') {
            return;
        }

        self::loadDefinitions();
        $definition['name'] = $hook;
        $definition['group'] = $definition['group'] ?? 'system';
        $definition['label'] = $definition['label'] ?? $hook;
        $definition['description'] = $definition['description'] ?? '';
        $definition['params'] = $definition['params'] ?? [];

        self::$definitions[$hook] = $definition;
    }

    public static function register(string $hook, $callback, array $meta = []): void
    {
        $hook = trim($hook);
        if ($hook === '') {
            return;
        }

        self::loadDefinitions();

        $entry = [
            'hook' => $hook,
            'callback' => self::stringifyCallback($callback),
            'callable' => $callback,
            'module' => $meta['module'] ?? 'Core',
            'priority' => (int) ($meta['priority'] ?? 10),
            'label' => $meta['label'] ?? '',
        ];

        self::$listeners[$hook][] = $entry;
    }

    public static function run(string $hook, mixed $payload = null): array
    {
        self::loadDefinitions();
        $listeners = self::$listeners[$hook] ?? [];
        if (empty($listeners)) {
            return [];
        }

        usort($listeners, fn ($a, $b) => $a['priority'] <=> $b['priority']);
        $results = [];
        foreach ($listeners as $listener) {
            $callable = $listener['callable'] ?? null;
            if (!is_callable($callable)) {
                continue;
            }
            $results[] = $callable($payload, $hook);
        }

        return $results;
    }

    public static function definitions(): array
    {
        self::loadDefinitions();
        return self::$definitions;
    }

    public static function listeners(): array
    {
        self::loadDefinitions();
        return self::$listeners;
    }

    public static function all(): array
    {
        self::loadDefinitions();
        $all = [];

        foreach (self::$definitions as $name => $definition) {
            $all[$name] = $definition;
        }

        foreach (self::$listeners as $hook => $listeners) {
            if (!isset($all[$hook])) {
                $all[$hook] = [
                    'name' => $hook,
                    'label' => $hook,
                    'group' => 'system',
                    'description' => '',
                    'params' => [],
                ];
            }
            $all[$hook]['listeners'] = $listeners;
        }

        foreach ($all as $hook => $definition) {
            if (!isset($definition['listeners'])) {
                $definition['listeners'] = self::$listeners[$hook] ?? [];
                $all[$hook] = $definition;
            }
            $all[$hook]['count'] = count($definition['listeners']);
        }

        return $all;
    }

    private static function loadDefinitions(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $configPath = BASE_PATH . '/config/hooks.php';
        if (file_exists($configPath)) {
            $definitions = require $configPath;
            if (is_array($definitions)) {
                foreach ($definitions as $hook => $definition) {
                    if (!is_array($definition)) {
                        continue;
                    }
                    $definition['name'] = $hook;
                    $definition['group'] = $definition['group'] ?? 'system';
                    $definition['label'] = $definition['label'] ?? $hook;
                    $definition['description'] = $definition['description'] ?? '';
                    $definition['params'] = $definition['params'] ?? [];
                    self::$definitions[$hook] = $definition;
                }
            }
        }

        $manager = new ModuleManager([
            BASE_PATH . '/app/Modules',
            BASE_PATH . '/app/Extensions',
        ], BASE_PATH . '/data/modules.json');

        foreach ($manager->enabled() as $module => $meta) {
            $hooksFile = trim((string) ($meta['hook_definitions_path'] ?? ''));
            if ($hooksFile === '') {
                $status = (string) ($meta['hook_definitions_status'] ?? 'absent');
                if (($meta['hook_definitions_declared'] ?? false) && ($status === 'missing' || $status === 'invalid')) {
                    self::reportPathIssue($module, $meta, 'hook_definitions', $status);
                }
                continue;
            }
            $definitions = require $hooksFile;
            if (!is_array($definitions)) {
                continue;
            }
            foreach ($definitions as $hook => $definition) {
                if (!is_array($definition)) {
                    continue;
                }
                $definition['name'] = $hook;
                $definition['group'] = $definition['group'] ?? 'system';
                $definition['label'] = $definition['label'] ?? $hook;
                $definition['description'] = $definition['description'] ?? '';
                $definition['params'] = $definition['params'] ?? [];
                $definition['module'] = $definition['module'] ?? $module;
                self::$definitions[$hook] = $definition;
            }
        }
    }

    private static function stringifyCallback(mixed $callback): string
    {
        if (is_string($callback)) {
            return $callback;
        }
        if (is_array($callback)) {
            $class = is_object($callback[0] ?? null) ? get_class($callback[0]) : ($callback[0] ?? '');
            $method = $callback[1] ?? '';
            return $class . '::' . $method;
        }
        if ($callback instanceof \Closure) {
            return 'Closure';
        }
        return 'Callable';
    }

    private static function reportPathIssue(string $module, array $meta, string $kind, string $status): void
    {
        $basePath = (string) ($meta['base_path'] ?? '');
        $location = (string) ($meta['location'] ?? 'module');
        $declared = (string) ($meta[$kind] ?? '');
        $manifest = (string) ($meta['manifest_name'] ?? 'manifest');
        $moduleLabel = (string) ($meta['name'] ?? $module);

        error_log(sprintf(
            '[FlatCMS] %s path issue for %s "%s": status=%s, declared=%s, manifest=%s, base=%s',
            $kind,
            $location,
            $moduleLabel,
            $status,
            $declared !== '' ? $declared : '-',
            $manifest !== '' ? $manifest : '-',
            $basePath !== '' ? $basePath : '-'
        ));
    }
}
