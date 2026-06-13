<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('HookManager', 'css/hook-manager.css') ?>">

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
        <p class="page-subtitle"><?= __('hooks_subtitle', 'HookManager') ?></p>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-plug"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('hooks_help_badge', 'HookManager') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('hooks_help_title', 'HookManager') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('hooks_help_intro', 'HookManager') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('hooks_help_step_search', 'HookManager') ?></li>
            <li><?= __('hooks_help_step_groups', 'HookManager') ?></li>
            <li><?= __('hooks_help_step_listeners', 'HookManager') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="#hooksControls" class="btn btn-primary"><?= __('hooks_help_action_filters', 'HookManager') ?></a>
            <a href="#hooksGroups" class="btn btn-secondary"><?= __('hooks_help_action_groups', 'HookManager') ?></a>
        </div>
    </div>
</div>

<?php if (empty($hookGroups ?? [])): ?>
    <div class="card">
        <div class="card-body">
            <div class="admin-empty-state-panel">
                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                    <i class="fas fa-plug-circle-xmark"></i>
                </div>
                <h2 class="admin-empty-state-panel__title"><?= __('hooks_empty_title', 'HookManager') ?></h2>
                <p class="admin-empty-state-panel__text"><?= __('hooks_empty_text', 'HookManager') ?></p>
                <div class="admin-empty-state-panel__actions">
                    <a href="<?= url('/admin/modules') ?>" class="btn btn-primary btn-sm"><?= __('hooks_empty_action_modules', 'HookManager') ?></a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="hook-toolbar" id="hooksToolbar">
        <div class="hook-summary">
            <span class="hook-summary-count"><?= (int) ($hookTotal ?? 0) ?></span>
            <span class="hook-summary-label"><?= __('hooks_total', 'HookManager') ?></span>
        </div>
        <div class="hook-toolbar-actions">
            <button type="button" id="hookExpandAll" class="btn btn-sm btn-secondary">
                <i class="fas fa-chevron-down"></i> <?= __('hooks_expand_all', 'HookManager') ?>
            </button>
            <button type="button" id="hookCollapseAll" class="btn btn-sm btn-secondary">
                <i class="fas fa-chevron-up"></i> <?= __('hooks_collapse_all', 'HookManager') ?>
            </button>
        </div>
    </div>

    <div class="hook-controls" id="hooksControls">
        <div class="search-bar hook-search">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="hookSearchInput" placeholder="<?= __('hooks_search_placeholder', 'HookManager') ?>" autocomplete="on">
        </div>

        <div class="hook-filter-row">
            <div class="hook-filter">
                <label class="form-label"><?= __('hooks_filter_group', 'HookManager') ?></label>
                <select id="hookGroupFilter" class="form-select">
                    <option value="all"><?= __('hooks_filter_all', 'HookManager') ?></option>
                    <?php foreach ($hookGroups as $group): ?>
                        <?php $groupName = $group['name'] ?? 'system'; ?>
                        <option value="<?= e($groupName) ?>"><?= e($groupName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="hook-filter">
                <label class="form-label"><?= __('hooks_filter_listeners', 'HookManager') ?></label>
                <select id="hookListenerFilter" class="form-select">
                    <option value="all"><?= __('hooks_filter_all', 'HookManager') ?></option>
                    <option value="with"><?= __('hooks_filter_with_listeners', 'HookManager') ?></option>
                    <option value="without"><?= __('hooks_filter_without_listeners', 'HookManager') ?></option>
                </select>
            </div>
            <div class="hook-filter hook-filter-checks">
                <label class="form-label"><?= __('hooks_filter_fields', 'HookManager') ?></label>
                <div class="hook-filter-options">
                    <label class="hook-check">
                        <input type="checkbox" id="hookSearchInHook" checked>
                        <span><?= __('hooks_field_hook', 'HookManager') ?></span>
                    </label>
                    <label class="hook-check">
                        <input type="checkbox" id="hookSearchInDescription" checked>
                        <span><?= __('hooks_field_description', 'HookManager') ?></span>
                    </label>
                    <label class="hook-check">
                        <input type="checkbox" id="hookSearchInParams" checked>
                        <span><?= __('hooks_field_params', 'HookManager') ?></span>
                    </label>
                    <label class="hook-check">
                        <input type="checkbox" id="hookSearchInListeners" checked>
                        <span><?= __('hooks_field_listeners', 'HookManager') ?></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div id="hooksGroups">
    <?php foreach ($hookGroups as $group): ?>
        <?php
            $groupName = $group['name'] ?? 'system';
            $groupKey = 'hooks_group_' . $groupName;
            $groupLabel = __($groupKey, 'HookManager');
            if ($groupLabel === $groupKey) {
                $groupLabel = ucfirst($groupName);
            }
            $hooks = $group['hooks'] ?? [];
        ?>
        <section class="hook-accordion" data-group="<?= e($groupName) ?>">
            <div class="hook-accordion-header" data-group="<?= e($groupName) ?>">
                <div class="hook-accordion-info">
                    <div class="hook-accordion-icon">
                        <i class="fas fa-plug"></i>
                    </div>
                    <div>
                        <h2 class="hook-accordion-title"><?= e($groupLabel) ?></h2>
                        <p class="hook-accordion-subtitle"><?= e($groupName) ?></p>
                    </div>
                </div>
                <div class="hook-accordion-right">
                    <span class="badge badge-primary"><?= count($hooks) ?></span>
                    <i class="fas fa-chevron-down hook-accordion-chevron"></i>
                </div>
            </div>
            <div class="hook-accordion-content" data-group="<?= e($groupName) ?>">
                <div class="table-wrapper hook-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><?= __('hooks_table_hook', 'HookManager') ?></th>
                                <th><?= __('hooks_table_description', 'HookManager') ?></th>
                                <th><?= __('hooks_table_params', 'HookManager') ?></th>
                                <th><?= __('hooks_table_listeners', 'HookManager') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hooks as $hook): ?>
                                <?php
                                    $listeners = $hook['listeners'] ?? [];
                                    $listenerCount = (int) ($hook['count'] ?? count($listeners));
                                    $params = $hook['params'] ?? [];
                                    $hookName = (string) ($hook['name'] ?? '');
                                    $hookKey = str_replace('.', '_', $hookName);
                                    $labelKey = 'hook_label_' . $hookKey;
                                    $descKey = 'hook_desc_' . $hookKey;
                                    $hookLabel = __($labelKey, 'HookManager');
                                    if ($hookLabel === $labelKey) {
                                        $hookLabel = $hook['label'] ?? $hookName;
                                    }
                                    $hookDescription = __($descKey, 'HookManager');
                                    if ($hookDescription === $descKey) {
                                        $hookDescription = $hook['description'] ?? '';
                                    }
                                    $paramLabels = [];
                                    foreach ($params as $param) {
                                        $paramKey = 'hook_param_' . $hookKey . '_' . $param;
                                        $paramLabel = __($paramKey, 'HookManager');
                                        if ($paramLabel === $paramKey) {
                                            $genericKey = 'hook_param_' . $param;
                                            $paramLabel = __($genericKey, 'HookManager');
                                            if ($paramLabel === $genericKey) {
                                                $paramLabel = (string) $param;
                                            }
                                        }
                                        $paramLabels[] = $paramLabel;
                                    }
                                    $listenerText = '';
                                    if (!empty($listeners)) {
                                        $listenerText = implode(' ', array_map(function ($listener) {
                                            $module = $listener['module'] ?? 'Core';
                                            $callback = $listener['callback'] ?? '';
                                            return $module . ' ' . $callback;
                                        }, $listeners));
                                    }
                                ?>
                                <tr class="hook-row"
                                    data-group="<?= e($groupName) ?>"
                                    data-hook="<?= e($hookName) ?>"
                                    data-label="<?= e((string) $hookLabel) ?>"
                                    data-description="<?= e((string) $hookDescription) ?>"
                                    data-params="<?= e(implode(' ', $paramLabels)) ?>"
                                    data-listeners="<?= e($listenerText) ?>"
                                    data-listener-count="<?= $listenerCount ?>">
                                    <td>
                                        <div class="hook-name"><?= e($hookName) ?></div>
                                        <?php if (!empty($hookLabel)): ?>
                                            <div class="hook-label"><?= e($hookLabel) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="hook-desc"><?= e($hookDescription) ?></td>
                                    <td>
                                        <?php if (empty($paramLabels)): ?>
                                            <span class="hook-muted">&mdash;</span>
                                        <?php else: ?>
                                            <div class="hook-params">
                                                <?php foreach ($paramLabels as $paramLabel): ?>
                                                    <span class="hook-param"><?= e((string) $paramLabel) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $listenerCount > 0 ? 'badge-success' : 'badge-warning' ?>">
                                            <?= $listenerCount ?>
                                        </span>
                                        <?php if (!empty($listeners)): ?>
                                            <div class="hook-listeners">
                                                <?php foreach ($listeners as $listener): ?>
                                                    <div class="hook-listener">
                                                        <span class="hook-listener-module"><?= e($listener['module'] ?? 'Core') ?></span>
                                                        <span class="hook-listener-callback"><?= e($listener['callback'] ?? '') ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
    </div>

    <div id="hookNoResults" class="hook-no-results hidden">
        <i class="fas fa-search hook-no-results-icon"></i>
        <?= __('hooks_no_results', 'HookManager') ?>
    </div>
<?php endif; ?>

<script src="<?= module_asset('HookManager', 'js/hook-manager.js') ?>"></script>
