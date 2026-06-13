<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Auth\Middleware;

use App\Core\Request;
use App\Modules\Auth\Services\RoleService;

class GuestMiddleware
{
    public function handle(Request $request): bool
    {
        if (is_auth()) {
            $role = (string) (auth()['role'] ?? RoleService::ROLE_MEMBER);
            redirect(url(RoleService::getLoginRedirect($role)));
            return false;
        }

        return true;
    }
}
