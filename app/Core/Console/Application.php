<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Core/Console/Application.php
 * Version: 2.0.0-dev
 */
declare(strict_types=1);

namespace App\Core\Console;

use App\Core\InstallationDoctor;
use App\Core\ModuleManager;
use App\Core\StorageMaintenance;
use App\Modules\Install\Services\InstallationService;

final class Application
{
    public static function commands(): CommandRegistry
    {
        $registry = new CommandRegistry();
        $registry->register('doctor', 'doctor', static function (array $args): array {
            self::arguments($args, 0, 0);
            return (new InstallationDoctor())->diagnose();
        }, 6);
        $registry->register('assets:publish', 'assets:publish', static function (array $args): array {
            self::arguments($args, 0, 0);
            return ['ok' => true, 'result' => (new \App\Core\RuntimeAssetPublisher())->publishAll()];
        }, 5);
        $registry->register('backups:baseline', 'backups:baseline --write|--check', static function (array $args): array {
            $service = new \App\Modules\Backups\Services\SiteBackupBaselineService();
            if ($args === ['--write']) {
                return ['ok' => true, 'result' => $service->write()];
            }
            if ($args === ['--check']) {
                $result = $service->inspect();
                return ['ok' => !empty($result['portable']), 'result' => $result];
            }
            throw new \InvalidArgumentException('cli_backups_baseline_action_invalid');
        }, 5);
        $registry->register('tasks:run', 'tasks:run', static function (array $args): array {
            self::arguments($args, 0, 0);
            $result = \App\Core\TaskRunner::run();
            $result['ok'] = array_filter($result['tasks'] ?? [], static fn ($task) => empty($task['ok'])) === [];
            return $result;
        });
        $registry->register('updates:apply', 'updates:apply core flatcms <version>', static function (array $args): array {
            self::arguments($args, 3, 3);
            return ['ok' => true, 'result' => (new \App\Modules\UpdateManager\Services\UpdateApplyService())->apply(...$args)];
        }, 3);
        $registry->register('updates:finalize', 'updates:finalize <recovery-id> --verified', static function (array $args): array {
            self::arguments($args, 2, 2);
            if ($args[1] !== '--verified') { throw new \InvalidArgumentException('update_finalization_confirmation_required'); }
            return ['ok' => true, 'result' => (new \App\Modules\UpdateManager\Services\RecoveryFinalizationService(BASE_PATH))->finalize($args[0], true)];
        }, 3);
        $registry->register('updates:build-core', 'updates:build-core <version> [output.zip]', static function (array $args): array {
            self::arguments($args, 1, 2);
            $output = $args[1] ?? (STORAGE_PATH . '/update-artifacts/core/flatcms/' . $args[0] . '/flatcms.zip');
            return ['ok' => true, 'result' => (new \App\Modules\UpdateManager\Services\UpdateCoreReleaseBuilderService())->build($args[0], $output)];
        }, 4);
        $registry->register('modules:list', 'modules:list', static function (array $args): array {
            self::arguments($args, 0, 0);
            $modules = [];
            foreach ((new ModuleManager())->all() as $name => $meta) {
                $modules[] = ['name' => $name] + array_intersect_key($meta, array_flip([
                    'version', 'location', 'required', 'desired_enabled', 'resolved_enabled',
                    'integrity_valid', 'lifecycle_status', 'lifecycle_reasons',
                ]));
            }
            return ['ok' => true, 'modules' => $modules];
        });
        $registry->register('storage:verify', 'storage:verify', static function (array $args): array {
            self::arguments($args, 0, 0);
            return (new StorageMaintenance())->verify();
        });
        $registry->register('storage:repair', 'storage:repair --apply', static function (array $args): array {
            if ($args !== ['--apply']) { throw new \InvalidArgumentException('cli_repair_requires_apply'); }
            return (new StorageMaintenance())->recoverContent();
        });
        $registry->register('cache:clear', 'cache:clear', static function (array $args): array {
            self::arguments($args, 0, 0);
            \cache_store()->clear();
            \view_cache_store()->clear('html');
            return ['ok' => true, 'cleared' => ['data', 'views']];
        });
        $registry->register('install', 'install --stdin --accept-license', static function (array $args, $input): array {
            sort($args);
            if ($args !== ['--accept-license', '--stdin']) { throw new \InvalidArgumentException('cli_install_requires_stdin_and_license'); }
            $raw = stream_get_contents($input, 65537);
            if ($raw === false || strlen($raw) > 65536) { throw new \InvalidArgumentException('cli_install_input_invalid'); }
            try { $config = json_decode($raw, true, 16, JSON_THROW_ON_ERROR); }
            catch (\JsonException) { throw new \InvalidArgumentException('cli_install_input_invalid'); }
            if (!is_array($config) || !is_array($config['admin'] ?? null) || !is_array($config['site'] ?? null)
                || (isset($config['design']) && !is_array($config['design']))
                || (isset($config['sample']) && !is_bool($config['sample']))) {
                throw new \InvalidArgumentException('cli_install_input_invalid');
            }
            $server = $config['server'] ?? 'unknown';
            if (!in_array($server, ['apache', 'litespeed', 'nginx', 'iis', 'unknown'], true)) {
                throw new \InvalidArgumentException('cli_install_server_invalid');
            }
            $admin = InstallationService::prepareAdmin($config['admin']);
            unset($raw, $config['admin']);
            $site = $config['site'];
            $service = new InstallationService(['server_type' => $server], $config['locale'] ?? 'fr-FR', (string) ($site['url'] ?? ''));
            $result = $service->install($admin, $site,
                $config['design'] ?? ['admin_theme' => 'admin-modern-pro', 'frontend_theme' => 'modern-pro'],
                $config['sample'] ?? false);
            return ['ok' => true, 'version' => $result['version'], 'config_files' => array_keys($result['config_files'])];
        });
        return $registry;
    }

    private static function arguments(array $arguments, int $minimum, int $maximum): void
    {
        if (count($arguments) < $minimum || count($arguments) > $maximum) {
            throw new \InvalidArgumentException('cli_arguments_invalid');
        }
    }
}
