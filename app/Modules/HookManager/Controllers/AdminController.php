<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\HookManager\Controllers;

use App\Core\BaseController;
use App\Core\Hook;
use App\Core\I18n;

class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        I18n::load('HookManager');
    }

    public function index(): void
    {
        if (!$this->authorize('hooks.view')) {
            return;
        }

        $hooks = Hook::all();
        $groups = [];

        foreach ($hooks as $hook) {
            $group = $hook['group'] ?? 'system';
            if (!isset($groups[$group])) {
                $groups[$group] = [
                    'name' => $group,
                    'hooks' => [],
                ];
            }
            $groups[$group]['hooks'][] = $hook;
        }

        ksort($groups);

        foreach ($groups as &$group) {
            usort($group['hooks'], fn($a, $b) => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
        }
        unset($group);

        $this->render('HookManager/Views/admin/index', [
            'pageTitle' => __('hooks_title', 'HookManager'),
            'hookGroups' => $groups,
            'hookTotal' => count($hooks),
        ], 'admin.main');
    }
}
