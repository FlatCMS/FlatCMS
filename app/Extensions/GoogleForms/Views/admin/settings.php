<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

$settings = is_array($settings ?? null) ? $settings : [];

$gfAdminPath = static function (string $path): string {
    $fragment = '';

    if (str_contains($path, '#')) {
        [$path, $fragment] = explode('#', $path, 2);
        $fragment = '#' . $fragment;
    }

    $path = '/' . ltrim($path, '/');

    if (function_exists('url')) {
        return url($path) . $fragment;
    }

    if (defined('BASE_URL') && BASE_URL !== '') {
        $base = rtrim((string) BASE_URL, '/');
        $parts = parse_url($base);
        $prefix = is_array($parts) && isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';

        return $prefix . $path . $fragment;
    }

    return $path . $fragment;
};
?>

<link rel="stylesheet" href="<?= module_asset('GoogleForms', 'css/google-forms.css') ?>">

<div class="google-forms-admin-page google-forms-settings-page">
    <div class="page-header google-forms-page-header">
        <div class="page-header-content">
            <h1 class="page-title"><?= e($pageTitle) ?></h1>
            <p class="page-subtitle"><?= __('google_forms_settings_help', 'GoogleForms') ?></p>
        </div>
        <div class="page-header-actions">
            <a class="btn btn-secondary" href="<?= $gfAdminPath('/admin/google-forms') ?>">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <?= __('back', 'Core') ?>
            </a>
        </div>
    </div>

    <section class="google-forms-command-center google-forms-settings-hero" aria-labelledby="googleFormsSettingsTitle">
        <div class="google-forms-command-main">
            <span class="google-forms-eyebrow"><?= __('google_forms_global_oauth_label', 'GoogleForms') ?></span>
            <h2 id="googleFormsSettingsTitle"><?= __('google_forms_settings_overview_title', 'GoogleForms') ?></h2>
            <p><?= __('google_forms_settings_overview_help', 'GoogleForms') ?></p>
        </div>
        <div class="google-forms-status-list">
            <div class="google-forms-status-item is-ok">
                <span><?= __('google_forms_redirect_uri', 'GoogleForms') ?></span>
                <strong><?= e((string) ($settings['redirect_uri'] ?? $redirectUri)) ?></strong>
            </div>
        </div>
    </section>

    <div class="card google-forms-card">
        <div class="card-body">
            <div class="google-forms-section-head">
                <div>
                    <span class="google-forms-section-kicker"><?= __('google_forms_configuration', 'GoogleForms') ?></span>
                    <h2 class="card-title card-title-spaced"><?= __('google_forms_oauth_settings', 'GoogleForms') ?></h2>
                </div>
                <a class="btn btn-primary btn-sm" href="<?= $gfAdminPath('/admin/settings#settings-integrations') ?>">
                    <i class="fas fa-sliders" aria-hidden="true"></i>
                    <?= __('google_forms_global_oauth_action', 'GoogleForms') ?>
                </a>
            </div>

            <div class="alert alert-info google-forms-oauth-core-notice">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                <div>
                    <strong><?= __('google_forms_global_oauth_title', 'GoogleForms') ?></strong>
                    <p><?= __('google_forms_global_oauth_hint', 'GoogleForms') ?></p>
                </div>
            </div>

            <div class="form-group google-forms-copy-group">
                <label for="gf_redirect_uri" class="form-label"><?= __('google_forms_redirect_uri', 'GoogleForms') ?></label>
                <div class="google-forms-copy-field">
                    <input id="gf_redirect_uri" class="form-input" value="<?= e($redirectUri) ?>" readonly>
                    <button class="btn btn-secondary" type="button" data-copy-target="#gf_redirect_uri" data-copy-done="<?= e(__('google_forms_copied', 'GoogleForms')) ?>" data-copy-failed="<?= e(__('google_forms_copy_failed', 'GoogleForms')) ?>">
                        <i class="fas fa-copy" aria-hidden="true"></i>
                        <span><?= __('google_forms_copy', 'GoogleForms') ?></span>
                    </button>
                </div>
                <div class="form-hint google-forms-redirect-hint">
                    <?= __('google_forms_redirect_uri_hint', 'GoogleForms') ?>
                </div>
            </div>
        </div>
    </div>

<script src="<?= module_asset('GoogleForms', 'js/google-forms.js') ?>"></script>
