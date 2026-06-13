<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<div class="maintenance-banner is-off" data-tour-target="dashboard-backups">
    <div class="maintenance-info">
        <div class="maintenance-icon-box">
            <i class="fas fa-database" aria-hidden="true"></i>
        </div>
        <div class="maintenance-text">
            <strong class="maintenance-title"><?= __('backups_dashboard_title', 'Backups') ?></strong>
            <div class="maintenance-status">
                <span class="maintenance-badge"><?= __('backups_dashboard_badge', 'Backups', ['count' => (string) $dashboardBackupsCount]) ?></span>
            </div>
            <div class="maintenance-copy"><?= __('backups_dashboard_text', 'Backups') ?></div>
        </div>
    </div>
    <div class="maintenance-actions">
        <a href="<?= e($dashboardBackupsUrl) ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-box-archive" aria-hidden="true"></i>
            <?= __('backups_dashboard_open', 'Backups') ?>
        </a>
        <?php if ($dashboardBackupsManage): ?>
            <a href="<?= e($dashboardBackupsUrl) ?>#backups-reset-card" class="btn btn-outline btn-sm">
                <i class="fas fa-rotate-left" aria-hidden="true"></i>
                <?= __('backups_dashboard_reset', 'Backups') ?>
            </a>
        <?php endif; ?>
    </div>
</div>
