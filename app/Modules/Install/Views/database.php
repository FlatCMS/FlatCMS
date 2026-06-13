<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Étape 5 : Configuration du stockage -->
<div class="p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <i class="fas fa-database text-2xl text-white" aria-hidden="true"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('database.title') ?></h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('database.subtitle') ?></p>
    </div>

    <!-- Avantages Flat-File -->
    <div class="bg-gradient-to-r from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-xl p-4 sm:p-6 mb-6 transition-colors duration-300">
        <h3 class="font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
            <i class="fas fa-star text-violet-600 dark:text-violet-400 mr-2" aria-hidden="true"></i>
            <?= \App\Modules\Install\Support\Lang::get('database.advantages_title') ?>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-white dark:bg-slate-800 rounded-lg flex items-center justify-center shadow-sm flex-shrink-0 transition-colors duration-300">
                    <i class="fas fa-bolt text-amber-500 dark:text-amber-400" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="font-medium text-slate-800 dark:text-white"><?= \App\Modules\Install\Support\Lang::get('database.adv_fast_title') ?></div>
                    <div class="text-sm text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('database.adv_fast_desc') ?></div>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-white dark:bg-slate-800 rounded-lg flex items-center justify-center shadow-sm flex-shrink-0 transition-colors duration-300">
                    <i class="fas fa-shield-alt text-emerald-500 dark:text-emerald-400" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="font-medium text-slate-800 dark:text-white"><?= \App\Modules\Install\Support\Lang::get('database.adv_secure_title') ?></div>
                    <div class="text-sm text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('database.adv_secure_desc') ?></div>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-white dark:bg-slate-800 rounded-lg flex items-center justify-center shadow-sm flex-shrink-0 transition-colors duration-300">
                    <i class="fas fa-sync-alt text-blue-500 dark:text-blue-400" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="font-medium text-slate-800 dark:text-white"><?= \App\Modules\Install\Support\Lang::get('database.adv_backup_title') ?></div>
                    <div class="text-sm text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('database.adv_backup_desc') ?></div>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-white dark:bg-slate-800 rounded-lg flex items-center justify-center shadow-sm flex-shrink-0 transition-colors duration-300">
                    <i class="fas fa-code text-violet-500 dark:text-violet-400" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="font-medium text-slate-800 dark:text-white"><?= \App\Modules\Install\Support\Lang::get('database.adv_git_title') ?></div>
                    <div class="text-sm text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('database.adv_git_desc') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Structure des données -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 sm:p-6 mb-6 border border-slate-200 dark:border-slate-700 transition-colors duration-300">
        <h3 class="font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
            <i class="fas fa-folder-tree text-slate-600 dark:text-slate-400 mr-2" aria-hidden="true"></i>
            <?= \App\Modules\Install\Support\Lang::get('database.structure_title') ?>
        </h3>

        <div class="bg-slate-800 dark:bg-slate-900 rounded-lg p-4 font-mono text-sm text-slate-300 overflow-x-auto transition-colors duration-300">
            <div class="text-emerald-400 dark:text-emerald-300">data/</div>
            <div class="pl-4">
                <div class="text-blue-400 dark:text-blue-300">├── core/</div>
                <div class="pl-4">
                    <div>├── pages/        <span class="text-slate-500 dark:text-slate-600"># <?= \App\Modules\Install\Support\Lang::get('database.structure_pages') ?></span></div>
                    <div>├── posts/        <span class="text-slate-500 dark:text-slate-600"># <?= \App\Modules\Install\Support\Lang::get('database.structure_posts') ?></span></div>
                    <div>└── media/        <span class="text-slate-500 dark:text-slate-600"># <?= \App\Modules\Install\Support\Lang::get('database.structure_media') ?></span></div>
                </div>
                <div>├── users/           <span class="text-slate-500 dark:text-slate-600"># <?= \App\Modules\Install\Support\Lang::get('database.structure_users') ?></span></div>
                <div>├── menus/           <span class="text-slate-500 dark:text-slate-600"># <?= \App\Modules\Install\Support\Lang::get('database.structure_menus') ?></span></div>
                <div>└── settings.json    <span class="text-slate-500 dark:text-slate-600"># <?= \App\Modules\Install\Support\Lang::get('database.structure_settings') ?></span></div>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6 transition-colors duration-300">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-0.5" aria-hidden="true"></i>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <strong><?= \App\Modules\Install\Support\Lang::get('database.info') ?></strong><br>
                <?= \App\Modules\Install\Support\Lang::get('database.info_desc') ?>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <form method="POST" action="<?= htmlspecialchars($installUrl) ?>">
        <input type="hidden" name="action" value="save_database">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <a href="<?= htmlspecialchars($installUrl) ?>?step=4" class="px-6 py-3 text-center sm:text-left text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                <?= \App\Modules\Install\Support\Lang::get('common.back') ?>
            </a>
            <button type="submit" class="px-6 sm:px-8 py-3 bg-gradient-to-r from-brand-600 to-purple-600 text-white font-semibold rounded-xl hover:from-brand-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                <?= \App\Modules\Install\Support\Lang::get('common.continue') ?>
                <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
            </button>
        </div>
    </form>
</div>
