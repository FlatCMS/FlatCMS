<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/TaskRunner.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Core;

final class TaskRunner
{
    private static bool $listenersLoaded = false;

    public static function run(?\DateTimeImmutable $now = null): array
    {
        return \App\Core\Storage\ApplicationLock::for(BASE_PATH)->shared(fn (): array => self::runUnderLease($now));
    }

    private static function runUnderLease(?\DateTimeImmutable $now): array
    {
        self::loadListeners();
        $timezone = new \DateTimeZone(date_default_timezone_get());
        $now ??= new \DateTimeImmutable('now', $timezone);
        $payload = [
            'now' => $now,
            'timestamp' => $now->format(DATE_ATOM),
        ];

        $listeners = Hook::listeners()['tasks.run'] ?? [];
        usort($listeners, static fn (array $a, array $b): int =>
            ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10)
        );

        $results = [];
        foreach ($listeners as $listener) {
            $callback = $listener['callable'] ?? null;
            if (!is_callable($callback)) {
                continue;
            }

            $package = (string) ($listener['module'] ?? 'Core');
            try {
                $result = $callback($payload, 'tasks.run');
                $results[] = ['package' => $package, 'ok' => true, 'result' => $result];
            } catch (\Throwable $e) {
                $results[] = ['package' => $package, 'ok' => false, 'error' => $e->getMessage()];
            }
        }

        return ['ran_at' => $now->format(DATE_ATOM), 'tasks' => $results];
    }

    private static function loadListeners(): void
    {
        if (self::$listenersLoaded) {
            return;
        }
        self::$listenersLoaded = true;

        $manager = new ModuleManager();
        foreach ($manager->enabled() as $meta) {
            $path = trim((string) ($meta['hooks_path'] ?? ''));
            if ($path !== '' && is_file($path)) {
                require_once $path;
            }
        }
    }
}
