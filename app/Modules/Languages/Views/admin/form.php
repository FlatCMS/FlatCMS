<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?></h1>
    </div>
    <div class="page-header-actions">
        <a href="<?= url('/admin/languages') ?>" class="btn btn-secondary"><?= __('back', 'Core') ?></a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-language"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('languages_help_badge', 'Languages') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('languages_form_help_title', 'Languages') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('languages_form_help_intro', 'Languages') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('languages_form_help_step_identity', 'Languages') ?></li>
            <li><?= __('languages_form_help_step_direction', 'Languages') ?></li>
            <li><?= __('languages_form_help_step_activation', 'Languages') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/languages') ?>" class="btn btn-primary"><?= __('languages', 'Languages') ?></a>
        </div>
    </div>
</div>

<div class="card">
    <form method="POST" action="<?= $language ? url('/admin/languages/' . $language['code']) : url('/admin/languages') ?>">
        <?= csrf_field() ?>

        <?php if (!$language && !empty($availableLanguages)): ?>
            <div class="form-group">
                <label for="code" class="form-label"><?= __('language_code', 'Languages') ?> *</label>
                <select id="code" name="code" class="form-select" required data-action="lang-select">
                    <option value="">-- <?= __('select', 'Core') ?> --</option>
                    <?php foreach ($availableLanguages as $code => $name): ?>
                        <option value="<?= e($code) ?>" data-name="<?= e($name) ?>"><?= e($code) ?> - <?= e($name) ?></option>
                    <?php endforeach; ?>
                    <option value="custom"><?= __('other_custom', 'Languages') ?></option>
                </select>
            </div>

            <div class="form-group hidden" id="customCodeGroup">
                <label for="customCode" class="form-label"><?= __('language_code', 'Languages') ?> (ex: pt-BR)</label>
                <input type="text" id="customCode" class="form-input" placeholder="<?= __('placeholder_locale_code', 'Languages') ?>" maxlength="5">
            </div>
        <?php else: ?>
            <div class="form-group">
                <label class="form-label"><?= __('language_code', 'Languages') ?></label>
                <input type="text" class="form-input" value="<?= e($language['code']) ?>" disabled>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="name" class="form-label"><?= __('language_name', 'Languages') ?> *</label>
            <input type="text" id="name" name="name" class="form-input" value="<?= e(old('name', $language['name'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label for="native" class="form-label"><?= __('native_name', 'Languages') ?></label>
            <input type="text" id="native" name="native" class="form-input" value="<?= e(old('native', $language['native'] ?? '')) ?>" placeholder="<?= __('native_name_placeholder', 'Languages') ?>">
        </div>

        <div class="form-group">
            <label for="direction" class="form-label"><?= __('direction', 'Languages') ?></label>
            <select id="direction" name="direction" class="form-select">
                <option value="ltr" <?= selected('ltr', old('direction', $language['direction'] ?? 'ltr')) ?>><?= __('ltr_short', 'Languages') ?> (<?= __('left_to_right', 'Languages') ?>)</option>
                <option value="rtl" <?= selected('rtl', old('direction', $language['direction'] ?? 'ltr')) ?>><?= __('rtl_short', 'Languages') ?> (<?= __('right_to_left', 'Languages') ?>)</option>
            </select>
        </div>

        <?php if ($language): ?>
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="active" name="active" class="form-checkbox" <?= checked(true, $language['active'] ?? true) ?>>
                    <label for="active" class="form-check-label"><?= __('active', 'Languages') ?></label>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= __('save', 'Core') ?></button>
            <a href="<?= url('/admin/languages') ?>" class="btn btn-secondary"><?= __('cancel', 'Core') ?></a>
        </div>
    </form>
</div>

<script src="<?= module_asset('Languages', 'js/languages-form.js') ?>"></script>
