<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$defaultUrl = trim((string) ($siteUrl ?? ''));
if ($defaultUrl === '') {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || str_contains(strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')), 'https')
    );
    $scheme = $isHttps ? 'https://' : 'http://';
    $defaultUrl = $scheme . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
$timezones = [
    'Europe/Paris' => \App\Modules\Install\Support\Lang::get('site.tz_paris'),
    'Europe/London' => \App\Modules\Install\Support\Lang::get('site.tz_london'),
    'Europe/Berlin' => \App\Modules\Install\Support\Lang::get('site.tz_berlin'),
    'Europe/Brussels' => \App\Modules\Install\Support\Lang::get('site.tz_brussels'),
    'Europe/Zurich' => \App\Modules\Install\Support\Lang::get('site.tz_zurich'),
    'America/New_York' => \App\Modules\Install\Support\Lang::get('site.tz_newyork'),
    'America/Los_Angeles' => \App\Modules\Install\Support\Lang::get('site.tz_losangeles'),
    'America/Montreal' => \App\Modules\Install\Support\Lang::get('site.tz_montreal'),
    'Asia/Tokyo' => \App\Modules\Install\Support\Lang::get('site.tz_tokyo'),
    'Asia/Shanghai' => \App\Modules\Install\Support\Lang::get('site.tz_shanghai'),
    'Australia/Sydney' => \App\Modules\Install\Support\Lang::get('site.tz_sydney'),
    'UTC' => \App\Modules\Install\Support\Lang::get('site.tz_utc'),
];
?>

<div class="p-4 sm:p-6 lg:p-8">
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <i class="fas fa-globe text-2xl text-white"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('site.title') ?></h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('site.subtitle') ?></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6 transition-colors duration-300">
        <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
            <i class="fas fa-exclamation-circle"></i>
            <span class="font-medium"><?= \App\Modules\Install\Support\Lang::get('common.errors') ?></span>
        </div>
        <ul class="mt-2 text-sm text-red-600 dark:text-red-400 list-disc list-inside">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($installUrl) ?>">
        <input type="hidden" name="action" value="save_site">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

        <div class="space-y-5">
            <div>
                <label for="site_name" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    <?= \App\Modules\Install\Support\Lang::get('site.field_name') ?> <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-heading text-slate-400 dark:text-slate-500"></i>
                    </div>
                    <input type="text" id="site_name" name="site_name" required
                           class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                           placeholder="<?= \App\Modules\Install\Support\Lang::get('site.field_name_placeholder') ?>"
                           value="<?= htmlspecialchars($_POST['site_name'] ?? 'FlatCMS') ?>">
                </div>
            </div>

            <div>
                <label for="site_description" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    <?= \App\Modules\Install\Support\Lang::get('site.field_description') ?> <span class="text-slate-400 dark:text-slate-500">(<?= \App\Modules\Install\Support\Lang::get('requirements.optional') ?>)</span>
                </label>
                <div class="relative">
                    <div class="absolute top-3 left-0 pl-4 flex items-start pointer-events-none">
                        <i class="fas fa-align-left text-slate-400 dark:text-slate-500"></i>
                    </div>
                    <textarea id="site_description" name="site_description" rows="3"
                              class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all resize-none"
                              placeholder="<?= \App\Modules\Install\Support\Lang::get('site.field_description_placeholder') ?>"><?= htmlspecialchars($_POST['site_description'] ?? '') ?></textarea>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= \App\Modules\Install\Support\Lang::get('site.field_description_help') ?></p>
            </div>

            <div>
                <label for="site_url" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    <?= \App\Modules\Install\Support\Lang::get('site.field_url') ?> <span class="text-slate-400 dark:text-slate-500"><?= \App\Modules\Install\Support\Lang::get('site.field_url_auto') ?></span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-link text-slate-400 dark:text-slate-500"></i>
                    </div>
                    <input type="url" id="site_url" name="site_url"
                           class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                           placeholder="<?= \App\Modules\Install\Support\Lang::get('site.field_url_placeholder') ?>"
                           value="<?= htmlspecialchars($_POST['site_url'] ?? $defaultUrl) ?>">
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= \App\Modules\Install\Support\Lang::get('site.field_url_help') ?></p>
            </div>

            <div>
                <label for="timezone" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    <?= \App\Modules\Install\Support\Lang::get('site.field_timezone') ?>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-clock text-slate-400 dark:text-slate-500"></i>
                    </div>
                    <select id="timezone" name="timezone"
                            class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all appearance-none">
                        <?php foreach ($timezones as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($_POST['timezone'] ?? 'Europe/Paris') === $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-slate-400 dark:text-slate-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 mt-6 mb-6 border border-slate-200 dark:border-slate-700 transition-colors duration-300">
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-eye text-slate-500 dark:text-slate-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200"><?= \App\Modules\Install\Support\Lang::get('site.preview') ?></span>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-slate-200 dark:border-slate-700 transition-colors duration-300">
                <div class="text-lg font-bold text-slate-800 dark:text-white" id="preview_name" data-preview-default="FlatCMS">FlatCMS</div>
                <div class="text-sm text-slate-500 dark:text-slate-400" id="preview_desc" data-preview-default="<?= htmlspecialchars(\App\Modules\Install\Support\Lang::get('site.preview_default_desc'), ENT_QUOTES, 'UTF-8') ?>"><?= \App\Modules\Install\Support\Lang::get('site.preview_default_desc') ?></div>
                <div class="text-xs text-brand-600 dark:text-brand-400 mt-1" id="preview_url" data-preview-default="<?= htmlspecialchars($defaultUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($defaultUrl) ?></div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <a href="<?= htmlspecialchars($installUrl) ?>?step=6" class="px-6 py-3 text-center sm:text-left text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                <?= \App\Modules\Install\Support\Lang::get('common.back') ?>
            </a>
            <button type="submit" class="px-6 sm:px-8 py-3 bg-gradient-to-r from-brand-600 to-purple-600 text-white font-semibold rounded-xl hover:from-brand-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                <?= \App\Modules\Install\Support\Lang::get('common.continue') ?>
                <i class="fas fa-arrow-right ml-2"></i>
            </button>
        </div>
    </form>
</div>
