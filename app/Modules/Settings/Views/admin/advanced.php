<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('Settings', 'css/settings.css') ?>">

<?php
$diagnostics = is_array($diagnostics ?? null) ? $diagnostics : [];
$advancedActions = is_array($advancedActions ?? null) ? $advancedActions : [];
$cacheGroups = is_array($diagnostics['cache_groups'] ?? null) ? $diagnostics['cache_groups'] : [];
$additionalIniFiles = is_array($diagnostics['additional_ini_files'] ?? null) ? $diagnostics['additional_ini_files'] : [];
$isLocalRequest = !empty($diagnostics['is_local_request']);
$assetVersionMode = (string) ($diagnostics['asset_version_mode'] ?? 'mtime');
$assetVersionModeLabel = $assetVersionMode === 'hash'
    ? __('settings_advanced_asset_version_mode_hash', 'Settings')
    : __('settings_advanced_asset_version_mode_mtime', 'Settings');
$opcacheEnabled = !empty($diagnostics['opcache_enabled']);
$localDevReady = !empty($diagnostics['local_dev_recommendation_ok']);
$opcacheStatusClass = $opcacheEnabled ? ($localDevReady ? 'is-ok' : 'is-warning') : 'is-warning';
$recommendationClass = $localDevReady ? 'is-ok' : 'is-warning';
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
        <p class="page-subtitle"><?= __('settings_advanced_subtitle', 'Settings') ?></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= url('/admin/settings') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <?= __('settings_back', 'Settings') ?>
        </a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-sliders-h"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('settings_help_badge', 'Settings') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('settings_advanced_title', 'Settings') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('settings_advanced_subtitle', 'Settings') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('settings_advanced_actions_hint', 'Settings') ?></li>
            <li><?= __('settings_advanced_diagnostics_hint', 'Settings') ?></li>
            <li><?= __('settings_advanced_recommended_hint', 'Settings') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/settings') ?>" class="btn btn-secondary"><?= __('settings_back', 'Settings') ?></a>
        </div>
    </div>
</div>

<div class="settings-advanced-grid">
    <div class="settings-advanced-stack">
        <div class="card">
            <h3 class="card-title card-title-spaced"><?= __('settings_advanced_actions_title', 'Settings') ?></h3>
            <p class="form-hint settings-advanced-intro"><?= __('settings_advanced_actions_hint', 'Settings') ?></p>

            <div class="settings-advanced-actions">
                <?php foreach ($advancedActions as $actionKey => $actionMeta): ?>
                    <form method="POST" action="<?= url('/admin/settings/advanced/actions') ?>" class="settings-advanced-action-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="<?= e((string) $actionKey) ?>">
                        <div class="settings-advanced-action-copy">
                            <strong><?= __((string) ($actionMeta['label_key'] ?? ''), 'Settings') ?></strong>
                        </div>
                        <?php if (can('settings.edit')): ?>
                            <button type="submit" class="btn btn-<?= e((string) ($actionMeta['variant'] ?? 'secondary')) ?>">
                                <i class="<?= e((string) ($actionMeta['icon'] ?? 'fas fa-wrench')) ?>" aria-hidden="true"></i>
                                <?= __('settings_advanced_action_run', 'Settings') ?>
                            </button>
                        <?php endif; ?>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title card-title-spaced"><?= __('settings_advanced_recommended_title', 'Settings') ?></h3>
            <div class="settings-system-block">
                <div class="settings-path-head">
                    <span class="settings-path-name"><?= __('settings_advanced_recommended_hint', 'Settings') ?></span>
                    <span class="settings-status-badge <?= e($recommendationClass) ?>">
                        <?= $localDevReady ? __('settings_advanced_recommended_ok', 'Settings') : __('settings_advanced_recommended_warning', 'Settings') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="settings-advanced-stack">
        <div class="card">
            <h3 class="card-title card-title-spaced"><?= __('settings_advanced_diagnostics_title', 'Settings') ?></h3>
            <p class="form-hint settings-advanced-intro"><?= __('settings_advanced_diagnostics_hint', 'Settings') ?></p>

            <div class="settings-routing-grid settings-advanced-stats">
                <div class="settings-routing-item">
                    <span class="settings-system-stat-label"><?= __('settings_advanced_environment', 'Settings') ?></span>
                    <span class="settings-system-stat-value"><?= e((string) ($diagnostics['environment'] ?? 'production')) ?></span>
                </div>
                <div class="settings-routing-item">
                    <span class="settings-system-stat-label"><?= __('settings_advanced_local_request', 'Settings') ?></span>
                    <span class="settings-status-badge <?= $isLocalRequest ? 'is-ok' : 'is-warning' ?>">
                        <?= $isLocalRequest ? __('settings_advanced_status_local', 'Settings') : __('settings_advanced_status_remote', 'Settings') ?>
                    </span>
                </div>
                <div class="settings-routing-item">
                    <span class="settings-system-stat-label"><?= __('settings_advanced_asset_version_mode', 'Settings') ?></span>
                    <span class="settings-system-stat-value"><?= e($assetVersionModeLabel) ?></span>
                </div>
                <div class="settings-routing-item">
                    <span class="settings-system-stat-label"><?= __('settings_advanced_opcache_enabled', 'Settings') ?></span>
                    <span class="settings-status-badge <?= e($opcacheStatusClass) ?>">
                        <?= $opcacheEnabled ? __('status_active', 'Settings') : __('status_inactive', 'Settings') ?>
                    </span>
                </div>
            </div>

            <dl class="settings-system-list">
                <div class="settings-system-row">
                    <dt><?= __('settings_advanced_php_sapi', 'Settings') ?></dt>
                    <dd><?= e((string) ($diagnostics['php_sapi'] ?? '')) ?></dd>
                </div>
                <div class="settings-system-row">
                    <dt><?= __('settings_advanced_php_ini', 'Settings') ?></dt>
                    <dd class="settings-advanced-code"><?= e((string) ($diagnostics['php_ini'] ?? '')) ?></dd>
                </div>
                <div class="settings-system-row">
                    <dt><?= __('settings_advanced_php_scan_dir', 'Settings') ?></dt>
                    <dd class="settings-advanced-code"><?= e((string) ($diagnostics['php_scan_dir'] ?? '')) ?></dd>
                </div>
                <div class="settings-system-row">
                    <dt><?= __('settings_advanced_additional_ini', 'Settings') ?></dt>
                    <dd>
                        <?php if (!empty($additionalIniFiles)): ?>
                            <div class="settings-advanced-code-list">
                                <?php foreach ($additionalIniFiles as $iniFile): ?>
                                    <span class="settings-advanced-code-item"><?= e((string) $iniFile) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="settings-system-stat-value"><?= __('settings_advanced_none', 'Settings') ?></span>
                        <?php endif; ?>
                    </dd>
                </div>
                <div class="settings-system-row">
                    <dt><?= __('settings_advanced_validate_timestamps', 'Settings') ?></dt>
                    <dd><?= e(!empty($diagnostics['opcache_validate_timestamps']) ? __('routing_yes', 'Settings') : __('routing_no', 'Settings')) ?></dd>
                </div>
                <div class="settings-system-row">
                    <dt><?= __('settings_advanced_revalidate_freq', 'Settings') ?></dt>
                    <dd><?= e((string) ($diagnostics['opcache_revalidate_freq'] ?? 0)) ?></dd>
                </div>
                <div class="settings-system-row">
                    <dt><?= __('settings_advanced_file_update_protection', 'Settings') ?></dt>
                    <dd><?= e((string) ($diagnostics['opcache_file_update_protection'] ?? 0)) ?></dd>
                </div>
                <div class="settings-system-row">
                    <dt><?= __('settings_advanced_realpath_ttl', 'Settings') ?></dt>
                    <dd><?= e((string) ($diagnostics['realpath_cache_ttl'] ?? 0)) ?></dd>
                </div>
                <div class="settings-system-row">
                    <dt><?= __('settings_advanced_opcache_reset_available', 'Settings') ?></dt>
                    <dd><?= e(!empty($diagnostics['opcache_reset_available']) ? __('routing_yes', 'Settings') : __('routing_no', 'Settings')) ?></dd>
                </div>
            </dl>
        </div>

        <div class="card">
            <h3 class="card-title card-title-spaced"><?= __('settings_advanced_cache_groups_title', 'Settings') ?></h3>
            <div class="settings-path-list">
                <?php foreach ($cacheGroups as $group): ?>
                    <div class="settings-path-item">
                        <div class="settings-path-head">
                            <span class="settings-path-name"><?= __((string) ($group['label_key'] ?? ''), 'Settings') ?></span>
                            <span class="settings-status-badge <?= ((int) ($group['entries'] ?? 0) > 0) ? 'is-warning' : 'is-ok' ?>">
                                <?= e((string) (($group['entries'] ?? 0))) ?>
                            </span>
                        </div>
                        <dl class="settings-system-list">
                            <div class="settings-system-row">
                                <dt><?= __('settings_advanced_cache_path', 'Settings') ?></dt>
                                <dd class="settings-advanced-code"><?= e((string) ($group['path'] ?? '')) ?></dd>
                            </div>
                            <div class="settings-system-row">
                                <dt><?= __('settings_advanced_cache_entries', 'Settings') ?></dt>
                                <dd><?= e((string) ($group['entries'] ?? 0)) ?></dd>
                            </div>
                            <div class="settings-system-row">
                                <dt><?= __('settings_advanced_cache_size', 'Settings') ?></dt>
                                <dd><?= e(human_size((int) ($group['size_bytes'] ?? 0))) ?></dd>
                            </div>
                        </dl>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
