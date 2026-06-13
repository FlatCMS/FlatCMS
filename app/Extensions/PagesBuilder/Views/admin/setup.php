<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$pagesBuilderLicense = is_array($pagesBuilderLicense ?? null) ? $pagesBuilderLicense : [];
$pagesBuilderCanEdit = !empty($pagesBuilderCanEdit);
$pagesBuilderSetupState = is_array($pagesBuilderSetupState ?? null) ? $pagesBuilderSetupState : [];
$existingPagesCount = (int) ($pagesBuilderSetupState['existing_pages_count'] ?? 0);
$managedPagesCount = (int) ($pagesBuilderSetupState['managed_pages_count'] ?? 0);
?>

<link rel="stylesheet" href="<?= module_asset('PagesBuilder', 'css/pages-builder.css') ?>">

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e(__('pages_builder_setup_title', 'PagesBuilder')) ?></h1>
        <p class="page-subtitle"><?= e(__('pages_builder_setup_subtitle', 'PagesBuilder')) ?></p>
    </div>
</div>

<?php if (!$pagesBuilderCanEdit): ?>
    <div class="alert alert-warning">
        <strong><?= e(__('pages_builder_license_locked', 'PagesBuilder')) ?></strong>
        <span><?= e(__('pages_builder_license_locked_body', 'PagesBuilder')) ?></span>
    </div>
<?php endif; ?>

<div class="form-layout-sidebar pages-builder-layout">
    <div>
        <div class="card pages-builder-card">
            <div class="card-body">
                <h2 class="card-title"><?= e(__('pages_builder_setup_heading', 'PagesBuilder')) ?></h2>
                <p class="pages-builder-card-copy"><?= e(__('pages_builder_setup_copy', 'PagesBuilder')) ?></p>

                <dl class="pages-builder-definition-list">
                    <div>
                        <dt><?= e(__('pages_builder_setup_existing_pages', 'PagesBuilder')) ?></dt>
                        <dd><?= e((string) $existingPagesCount) ?></dd>
                    </div>
                    <div>
                        <dt><?= e(__('pages_builder_setup_managed_pages', 'PagesBuilder')) ?></dt>
                        <dd><?= e((string) $managedPagesCount) ?></dd>
                    </div>
                </dl>

                <div class="pages-builder-empty-state">
                    <p class="pages-builder-card-copy"><?= e($existingPagesCount > 0 ? __('pages_builder_setup_conversion_required', 'PagesBuilder') : __('pages_builder_setup_empty_site', 'PagesBuilder')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card pages-builder-card">
            <div class="card-body">
                <h2 class="card-title"><?= e(__('pages_builder_setup_actions', 'PagesBuilder')) ?></h2>
                <p class="pages-builder-card-copy"><?= e(__('pages_builder_setup_actions_copy', 'PagesBuilder')) ?></p>

                <?php if ($pagesBuilderCanEdit): ?>
                    <form method="POST" action="<?= url('/admin/pages-builder/setup') ?>" class="pages-builder-setup-action-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="setup_action" value="<?= e($existingPagesCount > 0 ? 'convert' : 'empty') ?>">
                        <button type="submit" class="btn btn-primary">
                            <?= e($existingPagesCount > 0 ? __('pages_builder_setup_convert_button', 'PagesBuilder') : __('pages_builder_setup_initialize_button', 'PagesBuilder')) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
