<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Étape 10 : Installation terminée -->
<div class="p-4 sm:p-6 lg:p-8">
    <?php if (!empty($errors)): ?>
    <div class="text-center mb-6">
        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <i class="fas fa-times text-2xl sm:text-3xl text-white"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('complete.error_title') ?></h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('complete.error_subtitle') ?></p>
    </div>

    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6 transition-colors duration-300">
        <ul class="text-sm text-red-600 dark:text-red-400 list-disc list-inside">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="text-center">
        <a href="<?= htmlspecialchars($installUrl ?? '') ?>?step=1" class="px-6 sm:px-8 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-xl hover:bg-slate-300 dark:hover:bg-slate-600 transition-all inline-block">
            <i class="fas fa-redo mr-2"></i>
            <?= \App\Modules\Install\Support\Lang::get('complete.restart') ?>
        </a>
    </div>

    <?php else: ?>
    <div class="text-center mb-6 sm:mb-8">
        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl sm:rounded-3xl flex items-center justify-center mx-auto mb-4 sm:mb-6 shadow-xl">
            <i class="fas fa-check text-3xl sm:text-4xl text-white"></i>
        </div>
        <h2 class="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-white mb-2 sm:mb-3"><?= \App\Modules\Install\Support\Lang::get('complete.success_title') ?></h2>
        <p class="text-sm sm:text-lg text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('complete.success_subtitle') ?></p>
    </div>

    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-4 sm:p-6 mb-6 sm:mb-8 transition-colors duration-300">
        <h3 class="font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
            <i class="fas fa-clipboard-check text-emerald-600 dark:text-emerald-400 mr-2"></i>
            <?= \App\Modules\Install\Support\Lang::get('complete.summary_title') ?>
        </h3>

        <div class="space-y-3">
            <div class="flex items-center justify-between py-2 border-b border-emerald-200/50 dark:border-emerald-800/50">
                <span class="text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('complete.summary_version') ?></span>
                <span class="font-medium text-slate-800 dark:text-white"><?= htmlspecialchars((string) ($version ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-emerald-200/50 dark:border-emerald-800/50">
                <span class="text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('complete.summary_server') ?></span>
                <span class="font-medium text-slate-800 dark:text-white"><?= htmlspecialchars(ucfirst($environment['server_type'] ?? 'Apache')) ?></span>
            </div>
            <div class="flex items-center justify-between py-2 border-b border-emerald-200/50 dark:border-emerald-800/50">
                <span class="text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('complete.summary_php') ?></span>
                <span class="font-medium text-slate-800 dark:text-white"><?= PHP_VERSION ?></span>
            </div>
            <?php if (($environment['server_type'] ?? '') === 'apache' || ($environment['server_type'] ?? '') === 'litespeed'): ?>
            <div class="flex items-center justify-between py-2 border-b border-emerald-200/50 dark:border-emerald-800/50">
                <span class="text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('complete.summary_htaccess') ?></span>
                <span class="font-medium text-emerald-600 dark:text-emerald-400"><i class="fas fa-check-circle mr-1"></i><?= \App\Modules\Install\Support\Lang::get('complete.summary_htaccess_generated') ?></span>
            </div>
            <?php endif; ?>
            <div class="flex items-center justify-between py-2">
                <span class="text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('complete.summary_admin_email') ?></span>
                <span class="font-medium text-slate-800 dark:text-white"><?= htmlspecialchars($admin_email ?? '') ?></span>
            </div>
        </div>
    </div>

    <?php
        $siteBase = rtrim((string) ($siteUrl ?? ''), '/');
        if ($siteBase === '') {
            $siteBase = '.';
        }
        $adminLink = trim((string) ($adminUrl ?? '')) !== '' ? (string) $adminUrl : url('/admin');
        $homeLink = trim((string) ($homeUrl ?? '')) !== '' ? (string) $homeUrl : url('/');
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 sm:mb-8">
        <a href="<?= htmlspecialchars($adminLink) ?>"
           class="flex items-center justify-center gap-3 p-4 sm:p-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-cogs text-lg sm:text-xl"></i>
            </div>
            <div class="text-left">
                <div class="font-bold text-base sm:text-lg"><?= \App\Modules\Install\Support\Lang::get('complete.btn_admin') ?></div>
                <div class="text-sm text-white/80"><?= \App\Modules\Install\Support\Lang::get('complete.btn_admin_desc') ?></div>
            </div>
        </a>

        <a href="<?= htmlspecialchars($homeLink) ?>"
           class="flex items-center justify-center gap-3 p-4 sm:p-6 bg-gradient-to-r from-slate-700 to-slate-800 text-white rounded-xl hover:from-slate-800 hover:to-slate-900 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-globe text-lg sm:text-xl"></i>
            </div>
            <div class="text-left">
                <div class="font-bold text-base sm:text-lg"><?= \App\Modules\Install\Support\Lang::get('complete.btn_site') ?></div>
                <div class="text-sm text-white/80"><?= \App\Modules\Install\Support\Lang::get('complete.btn_site_desc') ?></div>
            </div>
        </a>
    </div>

    <?php if (!empty($configFiles)): ?>
    <?php
        $configContents = [];
        foreach (['htaccess', 'web_config_public', 'web_config_root', 'nginx'] as $key) {
            $path = $configFiles[$key] ?? '';
            if ($path && is_file($path)) {
                $configContents[$key] = (string)file_get_contents($path);
            } else {
                $configContents[$key] = '';
            }
        }
    ?>
    <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 rounded-xl p-4 sm:p-6 mb-6 sm:mb-8">
        <h3 class="font-semibold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('complete.config_title') ?></h3>
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-4"><?= \App\Modules\Install\Support\Lang::get('complete.config_hint') ?></p>
        <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-200">
            <?php if (!empty($configFiles['htaccess'])): ?>
                <li class="flex items-start gap-2">
                    <i class="fas fa-file-code mt-0.5 text-emerald-500"></i>
                    <div>
                        <button type="button"
                                class="text-brand-600 dark:text-brand-400 hover:underline"
                                data-config-open
                                data-config-id="htaccess"
                                data-config-title="<?= \App\Modules\Install\Support\Lang::get('complete.config_htaccess') ?>"
                                data-config-path="<?= htmlspecialchars($configFiles['htaccess']) ?>">
                            <?= \App\Modules\Install\Support\Lang::get('complete.config_htaccess') ?>
                        </button>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?= htmlspecialchars($configFiles['htaccess']) ?></div>
                    </div>
                </li>
            <?php endif; ?>
            <?php if (!empty($configFiles['web_config_public'])): ?>
                <li class="flex items-start gap-2">
                    <i class="fas fa-file-code mt-0.5 text-indigo-500"></i>
                    <div>
                        <button type="button"
                                class="text-brand-600 dark:text-brand-400 hover:underline"
                                data-config-open
                                data-config-id="web_config_public"
                                data-config-title="<?= \App\Modules\Install\Support\Lang::get('complete.config_iis_public') ?>"
                                data-config-path="<?= htmlspecialchars($configFiles['web_config_public']) ?>">
                            <?= \App\Modules\Install\Support\Lang::get('complete.config_iis_public') ?>
                        </button>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?= htmlspecialchars($configFiles['web_config_public']) ?></div>
                    </div>
                </li>
            <?php endif; ?>
            <?php if (!empty($configFiles['web_config_root'])): ?>
                <li class="flex items-start gap-2">
                    <i class="fas fa-file-code mt-0.5 text-indigo-500"></i>
                    <div>
                        <button type="button"
                                class="text-brand-600 dark:text-brand-400 hover:underline"
                                data-config-open
                                data-config-id="web_config_root"
                                data-config-title="<?= \App\Modules\Install\Support\Lang::get('complete.config_iis_root') ?>"
                                data-config-path="<?= htmlspecialchars($configFiles['web_config_root']) ?>">
                            <?= \App\Modules\Install\Support\Lang::get('complete.config_iis_root') ?>
                        </button>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?= htmlspecialchars($configFiles['web_config_root']) ?></div>
                    </div>
                </li>
            <?php endif; ?>
            <?php if (!empty($configFiles['nginx'])): ?>
                <li class="flex items-start gap-2">
                    <i class="fas fa-file-code mt-0.5 text-sky-500"></i>
                    <div>
                        <button type="button"
                                class="text-brand-600 dark:text-brand-400 hover:underline"
                                data-config-open
                                data-config-id="nginx"
                                data-config-title="<?= \App\Modules\Install\Support\Lang::get('complete.config_nginx') ?>"
                                data-config-path="<?= htmlspecialchars($configFiles['nginx']) ?>">
                            <?= \App\Modules\Install\Support\Lang::get('complete.config_nginx') ?>
                        </button>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?= htmlspecialchars($configFiles['nginx']) ?></div>
                    </div>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div id="config-modal"
         class="fixed inset-0 hidden items-center justify-center bg-slate-900/60 p-4 z-50"
         data-config-empty="<?= \App\Modules\Install\Support\Lang::get('complete.config_empty') ?>">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl w-full max-w-3xl shadow-2xl">
            <div class="flex items-start justify-between gap-4 p-4 border-b border-slate-200 dark:border-slate-700">
                <div>
                    <div id="config-modal-title" class="font-semibold text-slate-800 dark:text-white"></div>
                    <div id="config-modal-path" class="text-xs text-slate-500 dark:text-slate-400 mt-1"></div>
                </div>
                <button type="button"
                        data-config-close
                        class="h-8 w-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4">
                <pre id="config-modal-body" class="text-xs bg-slate-900 text-slate-100 rounded-lg p-4 overflow-auto max-h-96 whitespace-pre-wrap"></pre>
            </div>
            <div class="p-4 pt-0 flex items-center justify-end gap-2">
                <button type="button"
                        data-config-copy
                        class="px-3 py-2 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    <?= \App\Modules\Install\Support\Lang::get('common.copy') ?>
                </button>
                <button type="button"
                        data-config-close
                        class="px-3 py-2 text-xs font-semibold rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition-colors">
                    <?= \App\Modules\Install\Support\Lang::get('complete.config_close') ?>
                </button>
            </div>
        </div>
    </div>

    <pre id="config-htaccess" class="hidden"><?= htmlspecialchars($configContents['htaccess'] ?? '') ?></pre>
    <pre id="config-web_config_public" class="hidden"><?= htmlspecialchars($configContents['web_config_public'] ?? '') ?></pre>
    <pre id="config-web_config_root" class="hidden"><?= htmlspecialchars($configContents['web_config_root'] ?? '') ?></pre>
    <pre id="config-nginx" class="hidden"><?= htmlspecialchars($configContents['nginx'] ?? '') ?></pre>
    <?php endif; ?>

    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 transition-colors duration-300">
        <div class="flex items-start gap-3">
            <i class="fas fa-shield-alt text-amber-600 dark:text-amber-400 mt-0.5"></i>
            <div class="text-amber-800 dark:text-amber-300 text-sm">
                <div class="font-semibold mb-2"><?= \App\Modules\Install\Support\Lang::get('complete.security_recommendations_title') ?></div>
                <ul class="space-y-1 list-disc list-inside">
                    <li><?= \App\Modules\Install\Support\Lang::get('complete.security_recommendation_lock') ?></li>
                    <li><?= \App\Modules\Install\Support\Lang::get('complete.security_recommendation_admin') ?></li>
                    <li><?= \App\Modules\Install\Support\Lang::get('complete.security_recommendation_install_module') ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
