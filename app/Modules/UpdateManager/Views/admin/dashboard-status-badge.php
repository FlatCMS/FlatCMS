<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
?>
<a href="<?= e($welcomeStatusUrl) ?>"
   class="welcome-status-badge <?= e($welcomeStatusClass) ?>"
   title="<?= e($welcomeStatusLabel) ?>"
   aria-label="<?= e($welcomeStatusLabel) ?>">
    <i class="<?= e($welcomeStatusIcon) ?>" aria-hidden="true"></i>
    <span><?= e($welcomeStatusLabel) ?></span>
</a>
