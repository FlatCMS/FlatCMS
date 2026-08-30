<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$status = is_array($status ?? null) ? $status : [];
$catalogs = is_array($status['catalogs'] ?? null) ? $status['catalogs'] : [];
$errors = is_array($status['errors'] ?? null) ? $status['errors'] : [];
$updateCount = (int) ($status['update_count'] ?? 0);
$incompatibleCount = (int) ($status['incompatible_count'] ?? 0);
$externalCount = (int) ($status['external_count'] ?? 0);
$installedCount = (int) ($status['installed_count'] ?? 0);
$checkedAt = trim((string) ($status['checked_at'] ?? ''));
$checkedTimestamp = $checkedAt !== '' ? strtotime($checkedAt) : false;
$checkedLabel = $checkedTimestamp !== false ? date('d/m/Y H:i', $checkedTimestamp) : __('updates_never_checked', 'UpdateManager');
$canManageUpdates = !empty($canManageUpdates);
$updateInProgress = !empty($updateInProgress);
$updateMonitoring = !empty($updateMonitoring);
$recoveryRequired = !empty($recoveryRequired);
$updateOperationLocked = !empty($updateOperationLocked);
$cssPath = BASE_PATH . '/app/Modules/UpdateManager/Assets/css/update-manager.css';
$cssVersion = is_file($cssPath) ? (string) filemtime($cssPath) : '';

$familyOrder = ['core', 'modules', 'extensions', 'plugins', 'themes', 'appliances'];
$statusClass = static fn (string $value): string => match ($value) {
    'update_available' => 'badge-warning',
    'up_to_date' => 'badge-success',
    'incompatible_update' => 'badge-danger',
    'repository_unavailable' => 'badge-danger',
    default => 'badge-secondary',
};
?>

<link rel="stylesheet" href="<?= module_asset('UpdateManager', 'css/update-manager.css') ?><?= $cssVersion !== '' ? '?v=' . rawurlencode($cssVersion) : '' ?>">

<div class="page-header update-manager-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
        <p class="page-subtitle"><?= __('updates_subtitle', 'UpdateManager') ?></p>
    </div>
    <?php if ($canManageUpdates && !$updateOperationLocked): ?>
        <form method="POST" action="<?= e(url('/admin/updates/check')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-arrows-rotate" aria-hidden="true"></i>
                <?= __('updates_check_now', 'UpdateManager') ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<div class="alert alert-info update-manager-readonly">
    <i class="fas fa-shield-halved" aria-hidden="true"></i>
    <span><?= __('updates_read_only_notice', 'UpdateManager') ?></span>
</div>

<?php if ($updateInProgress): ?>
    <div class="alert alert-info update-manager-progress" role="status" aria-live="polite">
        <div class="update-manager-progress-label">
            <i class="fas fa-arrows-rotate update-manager-progress-spinner" aria-hidden="true"></i>
            <strong><?= __('recovery_updating_title', 'UpdateManager') ?></strong>
        </div>
        <div class="update-manager-progress-track" aria-hidden="true"><span></span></div>
    </div>
<?php elseif ($updateMonitoring): ?>
    <div class="alert alert-success update-manager-success" role="status">
        <i class="fas fa-circle-check" aria-hidden="true"></i>
        <strong><?= __('recovery_monitoring_title', 'UpdateManager') ?></strong>
    </div>
<?php elseif ($recoveryRequired): ?>
    <div class="alert alert-danger">
        <div class="alert-content">
            <strong><?= __('recovery_failure_title', 'UpdateManager') ?></strong>
            <span><?= __('recovery_failure_message', 'UpdateManager') ?></span>
            <?php if ($canManageUpdates): ?>
                <form method="POST" action="<?= e(url('/admin/updates/recovery')) ?>" class="update-row-action">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-life-ring" aria-hidden="true"></i>
                        <?= __('updates_recovery_open', 'UpdateManager') ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="update-manager-stats">
    <div class="card update-stat-card">
        <span><?= __('updates_installed_count', 'UpdateManager') ?></span>
        <strong><?= $installedCount ?></strong>
    </div>
    <div class="card update-stat-card">
        <span><?= __('updates_available_count', 'UpdateManager') ?></span>
        <strong><?= $updateCount ?></strong>
    </div>
    <div class="card update-stat-card">
        <span><?= __('updates_incompatible_count', 'UpdateManager') ?></span>
        <strong><?= $incompatibleCount ?></strong>
    </div>
    <div class="card update-stat-card">
        <span><?= __('updates_last_checked', 'UpdateManager') ?></span>
        <strong class="update-stat-date"><?= e($checkedLabel) ?></strong>
    </div>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert-warning update-manager-source-warning" role="status">
        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
        <strong><?= __('updates_source_errors', 'UpdateManager') ?></strong>
    </div>
<?php endif; ?>

<?php if ($errors === [] && $updateCount === 0 && $incompatibleCount === 0 && $externalCount === 0): ?>
    <div class="card update-manager-empty">
        <div class="card-body">
            <i class="fas fa-circle-check" aria-hidden="true"></i>
            <div>
                <h2><?= __('updates_no_updates_title', 'UpdateManager') ?></h2>
                <p><?= __('updates_no_updates_text', 'UpdateManager') ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="update-catalog-list">
<?php foreach ($familyOrder as $family): ?>
    <?php
    $catalog = is_array($catalogs[$family] ?? null) ? $catalogs[$family] : [];
    $packages = is_array($catalog['packages'] ?? null) ? $catalog['packages'] : [];
    $available = !empty($catalog['available']);
    $uncataloguedCount = (int) ($catalog['uncatalogued_count'] ?? 0);
    $sourceKey = in_array($family, ['core', 'appliances'], true)
        ? 'updates_repository_flatcms'
        : 'updates_repository_marketplace';
    ?>
    <section class="card update-catalog-card" data-update-family="<?= e($family) ?>">
        <div class="card-header update-catalog-header">
            <div>
                <h2 class="card-title"><?= __('updates_family_' . $family, 'UpdateManager') ?></h2>
                <span class="update-catalog-source"><?= __($sourceKey, 'UpdateManager') ?></span>
            </div>
            <div class="update-catalog-badges">
                <?php if (!$available): ?>
                    <span class="badge badge-danger"><?= __('updates_status_repository_unavailable', 'UpdateManager') ?></span>
                <?php elseif ((int) ($catalog['update_count'] ?? 0) > 0): ?>
                    <span class="badge badge-warning"><?= (int) ($catalog['update_count'] ?? 0) ?> × <?= __('updates_status_update_available', 'UpdateManager') ?></span>
                <?php elseif ((int) ($catalog['incompatible_count'] ?? 0) > 0): ?>
                    <span class="badge badge-danger"><?= (int) ($catalog['incompatible_count'] ?? 0) ?> × <?= __('updates_status_incompatible_update', 'UpdateManager') ?></span>
                <?php elseif (in_array($family, ['extensions', 'plugins'], true) && $uncataloguedCount > 0): ?>
                    <span class="badge badge-secondary">
                        <?= __('updates_status_external_catalog', 'UpdateManager', ['count' => $uncataloguedCount]) ?>
                    </span>
                <?php else: ?>
                    <span class="badge badge-success"><?= __('updates_status_up_to_date', 'UpdateManager') ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body update-catalog-body">
            <?php if ($packages === []): ?>
                <div class="update-catalog-placeholder">—</div>
            <?php else: ?>
                <div class="update-table-wrap">
                    <table class="table update-table">
                        <colgroup>
                            <col class="update-table-col-component">
                            <col class="update-table-col-vendor">
                            <col class="update-table-col-current">
                            <col class="update-table-col-latest">
                            <col class="update-table-col-status">
                        </colgroup>
                        <thead>
                            <tr>
                                <th><?= __('updates_component', 'UpdateManager') ?></th>
                                <th><?= __('updates_vendor', 'UpdateManager') ?></th>
                                <th><?= __('updates_current_version', 'UpdateManager') ?></th>
                                <th><?= __('updates_latest_version', 'UpdateManager') ?></th>
                                <th><?= __('updates_status', 'UpdateManager') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($packages as $package): ?>
                            <?php
                            $packageStatus = (string) ($package['status'] ?? 'not_in_catalog');
                            $themeType = (string) ($package['theme_type'] ?? '');
                            $latestVersion = (string) ($package['latest_version'] ?? '');
                            $compatibleVersion = (string) ($package['compatible_version'] ?? '');
                            $reasons = is_array($package['compatibility_reasons'] ?? null) ? $package['compatibility_reasons'] : [];
                            $changelog = trim((string) ($package['changelog'] ?? ''));
                            $changelogId = 'update-changelog-' . preg_replace(
                                '/[^a-z0-9_-]+/',
                                '-',
                                strtolower($family . '-' . (string) ($package['slug'] ?? 'package'))
                            );
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e((string) ($package['name'] ?? $package['slug'] ?? '')) ?></strong>
                                    <?php if ($themeType !== ''): ?>
                                        <small><?= __('updates_theme_' . $themeType, 'UpdateManager') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e((string) ($package['vendor'] ?? '')) ?: '—' ?></td>
                                <td><code><?= e((string) ($package['current_version'] ?? '')) ?></code></td>
                                <td><?= $latestVersion !== '' ? '<code>' . e($latestVersion) . '</code>' : '—' ?></td>
                                <td>
                                    <div class="update-status-stack">
                                        <span class="badge <?= e($statusClass($packageStatus)) ?>">
                                            <?= __('updates_status_' . $packageStatus, 'UpdateManager') ?>
                                        </span>
                                        <?php foreach ($reasons as $reason): ?>
                                            <small class="update-compatibility-reason"><?= __('updates_compatibility_' . $reason, 'UpdateManager') ?></small>
                                        <?php endforeach; ?>
                                        <?php if ($changelog !== ''): ?>
                                            <details class="update-changelog" id="<?= e($changelogId) ?>">
                                                <summary class="btn btn-outline btn-sm">
                                                    <i class="fas fa-list-check" aria-hidden="true"></i>
                                                    <?= __('updates_view_changelog', 'UpdateManager') ?>
                                                </summary>
                                                <div class="update-changelog-content">
                                                    <p><?= nl2br(e($changelog)) ?></p>
                                                </div>
                                            </details>
                                        <?php endif; ?>
                                        <?php if ($canManageUpdates && !$updateOperationLocked && $family === 'core' && $packageStatus === 'update_available' && $compatibleVersion !== ''): ?>
                                            <form method="POST" action="<?= e(url('/admin/updates/install/core/' . rawurlencode($compatibleVersion))) ?>" class="update-row-action">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-primary btn-sm"
                                                        data-action="confirm-delete"
                                                        data-message="<?= e(__('updates_install_confirm', 'UpdateManager', ['version' => $compatibleVersion])) ?>"
                                                        data-confirm-text="<?= e(__('updates_install_core', 'UpdateManager')) ?>"
                                                        data-warning="<?= e(__('updates_install_warning', 'UpdateManager')) ?>"
                                                        data-item-name="FlatCMS <?= e($compatibleVersion) ?>">
                                                    <i class="fas fa-download" aria-hidden="true"></i>
                                                    <?= __('updates_install_core', 'UpdateManager') ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endforeach; ?>
</div>
