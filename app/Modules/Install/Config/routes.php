<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Core\Router;

/** @var Router $router */

// The installer is bootstrapped directly from public/index.php before the
// application router is available. This module keeps an explicit routes file to
// stay structurally aligned with the rest of the HMVC modules.
