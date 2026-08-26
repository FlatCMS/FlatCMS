<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
?>

<div class="maintenance-banner is-on" data-tour-target="dashboard-updates">
    <div class="maintenance-info">
        <div class="maintenance-icon-box">
            <i class="fas fa-arrows-rotate" aria-hidden="true"></i>
        </div>
        <div class="maintenance-text">
            <strong class="maintenance-title"><?= __('updates_dashboard_title', 'UpdateManager') ?></strong>
            <div class="maintenance-status">
                <span class="maintenance-badge"><?= __('updates_dashboard_badge', 'UpdateManager', ['count' => (string) $dashboardUpdateCount]) ?></span>
            </div>
            <div class="maintenance-copy"><?= __('updates_dashboard_text', 'UpdateManager') ?></div>
        </div>
    </div>
    <div class="maintenance-actions">
        <?php if ($dashboardChangelogUrl !== ''): ?>
            <a href="<?= e($dashboardChangelogUrl) ?>" class="btn btn-outline btn-sm" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-list-check" aria-hidden="true"></i>
                <?= __('updates_view_changelog', 'UpdateManager') ?>
            </a>
        <?php endif; ?>
        <a href="<?= e($dashboardUpdatesUrl) ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
            <?= __('updates_dashboard_open', 'UpdateManager') ?>
        </a>
    </div>
</div>
