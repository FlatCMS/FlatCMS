<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<?php
$modulesCssVersion = file_exists(BASE_PATH . '/app/Modules/Modules/Assets/css/modules.css')
    ? (string) filemtime(BASE_PATH . '/app/Modules/Modules/Assets/css/modules.css')
    : '';
?>
<link rel="stylesheet" href="<?= module_asset('Modules', 'css/modules.css') ?><?= $modulesCssVersion !== '' ? '?v=' . rawurlencode($modulesCssVersion) : '' ?>">

<?php
$moduleTypes = [];
$moduleLocations = [];
$modulesList = is_array($modulesList ?? null) ? $modulesList : [];
$moduleEntries = $modulesList;
$initialStatusFilter = $initialStatusFilter ?? 'enabled';
$autoDeleteModuleName = strtolower((string) ($autoDeleteModuleName ?? ''));
$autoOpenModuleName = strtolower((string) ($autoOpenModuleName ?? ''));
$resolveLocationLabel = static function (string $location): string {
    return match (strtolower($location)) {
        'extension' => __('module_location_extension', 'Modules'),
        default => __('module_location_module', 'Modules'),
    };
};
$resolveDependencyLabel = static function (string $dependency): string {
    $dependencyKey = 'module_name_' . strtolower($dependency);
    if (\App\Core\I18n::has($dependencyKey, 'Modules')) {
        return __($dependencyKey, 'Modules');
    }

    return $dependency;
};
$resolveLifecycleLabel = static function (string $status, bool $isRequired, bool $isEnabled): string {
    return match ($status) {
        'invalid' => __('module_invalid', 'Modules'),
        'missing_dependencies' => __('module_missing_dependencies', 'Modules'),
        'enabled' => $isRequired ? __('module_required', 'Modules') : __('module_enabled', 'Modules'),
        default => __('module_disabled', 'Modules'),
    };
};
foreach ($moduleEntries as $meta) {
    $type = strtolower((string) ($meta['type'] ?? ''));
    if ($type !== '') {
        $moduleTypes[$type] = true;
    }
    $location = strtolower((string) ($meta['location'] ?? 'module'));
    if ($location !== '') {
        $moduleLocations[$location] = true;
    }
}
$moduleTypes = array_keys($moduleTypes);
sort($moduleTypes);
$moduleLocations = array_keys($moduleLocations);
sort($moduleLocations);
$renderModuleCards = static function (array $items) use ($enabledModules, $lockedModules, $resolveLocationLabel, $resolveDependencyLabel, $resolveLifecycleLabel): void {
    foreach ($items as $name => $meta):
        $isEnabled = isset($enabledModules[$name]);
        $isRequired = !empty($meta['required']);
        $lockedBy = $lockedModules[$name] ?? null;
        $deps = $meta['dependencies'] ?? [];
        $dependencyIssues = is_array($meta['dependency_issues'] ?? null) ? $meta['dependency_issues'] : [];
        $nameKey = 'module_name_' . strtolower($name);
        $descKey = 'module_desc_' . strtolower($name);
        $typeKey = 'module_type_' . strtolower($meta['type'] ?? '');
        $lockedLabel = null;
        if ($lockedBy) {
            $lockedKey = 'module_name_' . strtolower($lockedBy);
            $lockedLabel = \App\Core\I18n::has($lockedKey, 'Modules') ? __($lockedKey, 'Modules') : $lockedBy;
        }
        $isLegacy = !empty($meta['legacy']);
        $replacedBy = trim((string) ($meta['replaced_by'] ?? ''));
        $replacedByLabel = '';
        if ($replacedBy !== '') {
            $replacedByKey = 'module_name_' . strtolower($replacedBy);
            $replacedByLabel = \App\Core\I18n::has($replacedByKey, 'Modules') ? __($replacedByKey, 'Modules') : $replacedBy;
        }
        $replacementEnabled = $replacedBy !== '' && isset($enabledModules[$replacedBy]);
        $translatedName = \App\Core\I18n::has($nameKey, 'Modules') ? __($nameKey, 'Modules') : ($meta['name'] ?? $name);
        $translatedDesc = \App\Core\I18n::has($descKey, 'Modules') ? __($descKey, 'Modules') : ($meta['description'] ?? '');
        $translatedType = \App\Core\I18n::has($typeKey, 'Modules') ? __($typeKey, 'Modules') : ($meta['type'] ?? '-');
        $typeSlug = strtolower((string) ($meta['type'] ?? ''));
        $location = strtolower((string) ($meta['location'] ?? 'module'));
        $moduleIconClass = 'fas fa-puzzle-piece';
        $lifecycleStatus = strtolower((string) ($meta['lifecycle_status'] ?? ($isEnabled ? 'enabled' : 'disabled')));
        $filterStatus = $lifecycleStatus === 'enabled' ? ($isRequired ? 'required' : 'enabled') : 'disabled';
        $status = $lockedBy ? 'locked' : $filterStatus;
        $searchValue = strtolower(trim($translatedName . ' ' . $translatedDesc . ' ' . $translatedType . ' ' . $name));
        $locationLabel = $resolveLocationLabel($location);
        $isSidebarVisible = (bool) ($meta['sidebar_visible'] ?? true);
        $isSidebarManageable = (bool) ($meta['sidebar_manageable'] ?? true);
        $dependencyIssueLabels = [];
        foreach (array_keys($dependencyIssues) as $dependencyName) {
            $dependencyIssueLabels[] = $resolveDependencyLabel((string) $dependencyName);
        }
        $dependencyIssueLabels = array_values(array_unique($dependencyIssueLabels));
        ?>
        <section class="module-card" data-module-card data-module-name="<?= e(strtolower($name)) ?>" data-status="<?= e($status) ?>" data-type="<?= e($typeSlug) ?>" data-location="<?= e($location) ?>" data-search="<?= e($searchValue) ?>">
            <div class="module-card-header" data-module-toggle>
                <div class="module-card-info">
                    <div class="module-card-icon">
                        <i class="<?= e($moduleIconClass) ?>"></i>
                    </div>
                    <div class="module-card-text">
                        <h3 class="module-card-title"><?= e($translatedName) ?></h3>
                        <?php if (!empty($translatedDesc)): ?>
                            <p class="module-meta"><?= e($translatedDesc) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="module-card-summary">
                    <?php if ($lockedBy): ?>
                        <span class="badge badge-warning"><?= __('module_locked', 'Modules') ?></span>
                    <?php elseif ($lifecycleStatus === 'invalid'): ?>
                        <span class="badge badge-danger"><?= __('module_invalid', 'Modules') ?></span>
                    <?php elseif ($lifecycleStatus === 'missing_dependencies'): ?>
                        <span class="badge badge-warning"><?= __('module_missing_dependencies', 'Modules') ?></span>
                    <?php elseif ($isRequired): ?>
                        <span class="badge badge-primary"><?= __('module_required', 'Modules') ?></span>
                    <?php elseif ($isEnabled): ?>
                        <span class="badge badge-success"><?= __('module_enabled', 'Modules') ?></span>
                    <?php else: ?>
                        <span class="badge badge-warning"><?= __('module_disabled', 'Modules') ?></span>
                    <?php endif; ?>
                    <?php if ($isLegacy): ?>
                        <span class="badge badge-warning"><?= __('module_legacy', 'Modules') ?></span>
                    <?php endif; ?>
                    <span class="module-card-version"><?= e($meta['version'] ?? '-') ?></span>
                    <i class="fas fa-chevron-down module-card-chevron"></i>
                </div>
            </div>
            <div class="module-card-content">
                <div class="module-card-body">
                    <div class="module-detail-grid">
                        <div class="module-detail">
                            <span class="module-detail-label"><?= __('module_type', 'Modules') ?></span>
                            <span class="module-detail-value"><?= e($translatedType) ?></span>
                        </div>
                        <div class="module-detail">
                            <span class="module-detail-label"><?= __('module_version', 'Modules') ?></span>
                            <span class="module-detail-value"><?= e($meta['version'] ?? '-') ?></span>
                        </div>
                        <div class="module-detail">
                            <span class="module-detail-label"><?= __('module_status', 'Modules') ?></span>
                            <span class="module-detail-value">
                                <?php if ($lockedBy): ?>
                                    <?= __('module_locked', 'Modules') ?>
                                <?php else: ?>
                                    <?= $resolveLifecycleLabel($lifecycleStatus, $isRequired, $isEnabled) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="module-detail">
                            <span class="module-detail-label"><?= __('module_filter_location', 'Modules') ?></span>
                            <span class="module-detail-value"><?= e($locationLabel) ?></span>
                        </div>
                        <div class="module-detail">
                            <span class="module-detail-label"><?= __('module_sidebar_visibility', 'Modules') ?></span>
                            <span class="module-detail-value"><?= __($isSidebarManageable ? ($isSidebarVisible ? 'module_sidebar_visible' : 'module_sidebar_hidden') : 'module_sidebar_not_applicable', 'Modules') ?></span>
                        </div>
                        <?php if ($replacedBy !== ''): ?>
                            <div class="module-detail">
                                <span class="module-detail-label"><?= __('module_replaced_by', 'Modules') ?></span>
                                <span class="module-detail-value"><?= e($replacedByLabel) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="module-detail-block">
                        <span class="module-detail-label"><?= __('module_dependencies', 'Modules') ?></span>
                        <?php if (empty($deps)): ?>
                            <span class="text-muted">-</span>
                        <?php else: ?>
                            <div class="module-deps">
                                <?php foreach ($deps as $dep): ?>
                                    <span class="module-dep"><?= e($resolveDependencyLabel((string) $dep)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($lifecycleStatus === 'missing_dependencies' && $dependencyIssueLabels !== []): ?>
                        <p class="text-muted"><?= __('module_dependencies_missing', 'Modules', ['modules' => implode(', ', $dependencyIssueLabels)]) ?></p>
                    <?php elseif ($lifecycleStatus === 'invalid'): ?>
                        <p class="text-muted"><?= __('module_invalid_state', 'Modules') ?></p>
                    <?php endif; ?>
                    <div class="module-actions">
                        <?php if ($lockedBy): ?>
                            <?php if ($isSidebarManageable): ?>
                                <form method="POST" action="<?= url('/admin/modules/' . $name . '/sidebar-toggle') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-ghost">
                                        <?= __($isSidebarVisible ? 'module_sidebar_hide' : 'module_sidebar_show', 'Modules') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-ghost" disabled>
                                <?= __('module_locked_by', 'Modules', ['module' => $lockedLabel ?? $lockedBy]) ?>
                            </button>
                        <?php elseif ($isRequired): ?>
                            <?php if ($isSidebarManageable): ?>
                                <form method="POST" action="<?= url('/admin/modules/' . $name . '/sidebar-toggle') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-ghost">
                                        <?= __($isSidebarVisible ? 'module_sidebar_hide' : 'module_sidebar_show', 'Modules') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-ghost" disabled>
                                <?= __('module_required', 'Modules') ?>
                            </button>
                        <?php else: ?>
                            <?php if ($isSidebarManageable): ?>
                                <form method="POST" action="<?= url('/admin/modules/' . $name . '/sidebar-toggle') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-ghost">
                                        <?= __($isSidebarVisible ? 'module_sidebar_hide' : 'module_sidebar_show', 'Modules') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($isEnabled): ?>
                                <form method="POST" action="<?= url('/admin/modules/' . $name . '/toggle') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-danger" data-action="confirm-delete" data-message="<?= __('confirm_disable_module', 'Modules') ?>" data-confirm-text="<?= __('disable', 'Modules') ?>" data-warning="<?= __('disable_warning', 'Modules') ?>" data-item-name="<?= e($meta['name'] ?? $name) ?>">
                                        <?= __('disable', 'Modules') ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="<?= url('/admin/modules/' . $name . '/toggle') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <?= __('enable', 'Modules') ?>
                                    </button>
                                </form>
                                <form method="POST" action="<?= url('/admin/modules/' . $name . '/delete') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-ghost btn-danger" data-action="confirm-delete" data-module-delete-target="<?= e(strtolower($name)) ?>" data-message="<?= __('confirm_delete_module', 'Modules') ?>" data-confirm-text="<?= __('delete', 'Core') ?>" data-warning="<?= __('delete_warning', 'Core') ?>" data-item-name="<?= e($meta['name'] ?? $name) ?>">
                                        <?= __('delete', 'Core') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($isLegacy && $isEnabled && $replacementEnabled): ?>
                        <p class="text-muted"><?= __('module_legacy_disable_hint', 'Modules', ['module' => $replacedByLabel]) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php
    endforeach;
};
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-boxes"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('modules_help_badge', 'Modules') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('modules_help_title', 'Modules') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('modules_help_intro', 'Modules') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('modules_help_step_filters', 'Modules') ?></li>
            <li><?= __('modules_help_step_install', 'Modules') ?></li>
            <li><?= __('modules_help_step_dependencies', 'Modules') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="#modulesToolbar" class="btn btn-primary"><?= __('modules_help_action_filters', 'Modules') ?></a>
            <a href="#modulesInstallerCard" class="btn btn-secondary"><?= __('modules_help_action_install', 'Modules') ?></a>
        </div>
    </div>
</div>

<div class="modules-toolbar" id="modulesToolbar">
    <div class="modules-filter-group">
        <button type="button" class="btn btn-sm btn-ghost is-active" data-filter-status="enabled"><?= __('module_filter_enabled', 'Modules') ?></button>
        <button type="button" class="btn btn-sm btn-ghost" data-filter-status="all"><?= __('module_filter_all', 'Modules') ?></button>
        <button type="button" class="btn btn-sm btn-ghost" data-filter-status="disabled"><?= __('module_filter_disabled', 'Modules') ?></button>
        <button type="button" class="btn btn-sm btn-ghost" data-filter-status="required"><?= __('module_filter_required', 'Modules') ?></button>
    </div>
    <div class="modules-filter-group modules-filter-group-right">
        <div class="modules-filter-field">
            <label class="form-label" for="moduleTypeFilter"><?= __('module_filter_type', 'Modules') ?></label>
            <select id="moduleTypeFilter" class="form-select">
                <option value=""><?= __('module_filter_type_all', 'Modules') ?></option>
                <?php foreach ($moduleTypes as $type): ?>
                    <?php $typeKey = 'module_type_' . $type; ?>
                    <option value="<?= e($type) ?>"><?= \App\Core\I18n::has($typeKey, 'Modules') ? __($typeKey, 'Modules') : e(ucfirst($type)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="modules-filter-field">
            <label class="form-label" for="moduleLocationFilter"><?= __('module_filter_location', 'Modules') ?></label>
            <select id="moduleLocationFilter" class="form-select">
                <option value=""><?= __('module_filter_location_all', 'Modules') ?></option>
                <?php foreach ($moduleLocations as $location): ?>
                    <option value="<?= e($location) ?>"><?= $resolveLocationLabel($location) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="modules-filter-field modules-filter-search">
            <label class="form-label" for="moduleSearchInput"><?= __('module_filter_search', 'Modules') ?></label>
            <input type="text" id="moduleSearchInput" class="form-input" placeholder="<?= __('module_filter_search_placeholder', 'Modules') ?>">
        </div>
    </div>
</div>

<div class="card module-installer-card" id="modulesInstallerCard">
    <div class="card-header">
        <h2 class="card-title"><?= __('extensions_installer_title', 'Modules') ?></h2>
        <span class="module-installer-hint"><?= __('extensions_installer_hint', 'Modules') ?></span>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/modules/install') ?>" enctype="multipart/form-data" class="module-installer-form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="extension_zip"><?= __('extensions_installer_file', 'Modules') ?></label>
                <input type="file" name="extension_zip" id="extension_zip" class="form-input" accept=".zip" required>
                <span class="form-hint"><?= __('extensions_installer_hint_file', 'Modules') ?></span>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-cloud-upload-alt"></i>
                <?= __('extensions_installer_action', 'Modules') ?>
            </button>
        </form>
    </div>
</div>

<div class="modules-columns" id="modulesColumns" data-module-index data-initial-status="<?= e($initialStatusFilter) ?>" data-auto-delete-module="<?= e($autoDeleteModuleName) ?>" data-auto-open-module="<?= e($autoOpenModuleName) ?>">
    <section class="modules-column">
        <div class="card modules-column-card">
            <div class="card-header">
                <h2 class="card-title"><?= __('module_name_modules', 'Modules') ?></h2>
            </div>
            <div class="card-body">
                <div class="module-card-list" data-module-list="modules">
                    <?php if (empty($modulesList)): ?>
                        <div class="card module-empty" data-module-empty>
                            <div class="card-body">
                                <div class="admin-empty-state-panel">
                                    <div class="admin-empty-state-panel__icon" aria-hidden="true">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <h2 class="admin-empty-state-panel__title"><?= __('modules_empty_title', 'Modules') ?></h2>
                                    <p class="admin-empty-state-panel__text"><?= __('modules_empty_text', 'Modules') ?></p>
                                    <div class="admin-empty-state-panel__actions">
                                        <a href="#modulesInstallerCard" class="btn btn-primary btn-sm"><?= __('modules_empty_action_install', 'Modules') ?></a>
                                        <a href="#modulesToolbar" class="btn btn-ghost btn-sm"><?= __('modules_empty_action_filters', 'Modules') ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php $renderModuleCards($modulesList); ?>
                        <div class="card module-empty hidden" data-module-empty>
                            <div class="card-body">
                                <div class="admin-empty-state-panel">
                                    <div class="admin-empty-state-panel__icon" aria-hidden="true">
                                        <i class="fas fa-filter-circle-xmark"></i>
                                    </div>
                                    <h2 class="admin-empty-state-panel__title"><?= __('modules_filter_empty_title', 'Modules') ?></h2>
                                    <p class="admin-empty-state-panel__text"><?= __('modules_filter_empty_text', 'Modules') ?></p>
                                    <div class="admin-empty-state-panel__actions">
                                        <a href="#modulesToolbar" class="btn btn-primary btn-sm"><?= __('modules_empty_action_filters', 'Modules') ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="<?= module_asset('Modules', 'js/modules.js') ?>?v=<?= filemtime(BASE_PATH . '/app/Modules/Modules/Assets/js/modules.js') ?>"></script>
