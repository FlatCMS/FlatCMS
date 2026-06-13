<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Étape 9 : Données d'exemple -->
<div class="p-4 sm:p-6 lg:p-8">
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <i class="fas fa-box-open text-2xl text-white"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('sample.title') ?></h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('sample.subtitle') ?></p>
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
        <input type="hidden" name="action" value="install_sample">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

        <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl p-4 sm:p-6 mb-6 border-2 border-transparent hover:border-amber-300 dark:hover:border-amber-700 transition-all cursor-pointer" data-action="toggle-checkbox" data-target="install_sample">
            <label class="flex items-start gap-4 cursor-pointer">
                <div class="pt-1">
                    <input type="checkbox" id="install_sample" name="install_sample" value="1"
                           class="w-5 h-5 text-amber-600 bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 rounded focus:ring-amber-500 transition-colors duration-300">
                </div>
                <div class="flex-1">
                    <div class="font-semibold text-slate-800 dark:text-white text-base sm:text-lg mb-2">
                        <i class="fas fa-magic text-amber-600 dark:text-amber-400 mr-2"></i>
                        <?= \App\Modules\Install\Support\Lang::get('sample.install_sample') ?>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">
                        <?= \App\Modules\Install\Support\Lang::get('sample.install_sample_desc') ?>
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="bg-white/70 dark:bg-slate-800/70 rounded-lg p-3 flex items-center gap-3 transition-colors duration-300">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors duration-300">
                                <i class="fas fa-file-alt text-blue-600 dark:text-blue-400"></i>
                            </div>
                            <div>
                                <div class="font-medium text-slate-800 dark:text-white text-sm"><?= \App\Modules\Install\Support\Lang::get('sample.content_1_page') ?></div>
                                <div class="text-xs text-slate-500 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('sample.content_1_page_detail') ?></div>
                            </div>
                        </div>

                        <div class="bg-white/70 dark:bg-slate-800/70 rounded-lg p-3 flex items-center gap-3 transition-colors duration-300">
                            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors duration-300">
                                <i class="fas fa-newspaper text-emerald-600 dark:text-emerald-400"></i>
                            </div>
                            <div>
                                <div class="font-medium text-slate-800 dark:text-white text-sm"><?= \App\Modules\Install\Support\Lang::get('sample.content_1_post') ?></div>
                                <div class="text-xs text-slate-500 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('sample.content_1_post_detail') ?></div>
                            </div>
                        </div>

                        <div class="bg-white/70 dark:bg-slate-800/70 rounded-lg p-3 flex items-center gap-3 transition-colors duration-300">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors duration-300">
                                <i class="fas fa-bars text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div>
                                <div class="font-medium text-slate-800 dark:text-white text-sm"><?= \App\Modules\Install\Support\Lang::get('sample.content_1_menu') ?></div>
                                <div class="text-xs text-slate-500 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('sample.content_1_menu_detail') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </label>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 sm:p-6 mb-6 border-2 border-transparent hover:border-slate-300 dark:hover:border-slate-600 transition-all">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-xl flex items-center justify-center flex-shrink-0 transition-colors duration-300">
                    <i class="fas fa-leaf text-slate-500 dark:text-slate-400"></i>
                </div>
                <div>
                    <div class="font-semibold text-slate-800 dark:text-white mb-1"><?= \App\Modules\Install\Support\Lang::get('sample.blank_title') ?></div>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">
                        <?= \App\Modules\Install\Support\Lang::get('sample.blank_desc') ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6 transition-colors duration-300">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-0.5"></i>
                <div class="text-sm text-blue-700 dark:text-blue-300">
                    <strong><?= \App\Modules\Install\Support\Lang::get('sample.info') ?></strong>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <a href="<?= htmlspecialchars($installUrl) ?>?step=8" class="px-6 py-3 text-center sm:text-left text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                <?= \App\Modules\Install\Support\Lang::get('common.back') ?>
            </a>
            <button type="submit" class="px-6 sm:px-8 py-3 bg-gradient-to-r from-brand-600 to-purple-600 text-white font-semibold rounded-xl hover:from-brand-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                <?= \App\Modules\Install\Support\Lang::get('sample.launch_installation') ?>
                <i class="fas fa-rocket ml-2"></i>
            </button>
        </div>
    </form>
</div>
