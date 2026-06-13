<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Étape 1 : Bienvenue -->
<div class="p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-brand-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <i class="fas fa-rocket text-2xl sm:text-3xl text-white"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('welcome.title') ?></h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('welcome.subtitle', ['version' => $version]) ?></p>
    </div>

    <!-- Environnement détecté -->
    <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 sm:p-6 mb-6 border border-slate-200 dark:border-slate-700 transition-colors duration-300">
        <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
            <i class="fas fa-server text-brand-600 dark:text-brand-400 mr-2"></i>
            <?= \App\Modules\Install\Support\Lang::get('welcome.environment_detected') ?>
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Serveur -->
            <div class="flex items-center gap-3 p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors duration-300">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center transition-colors duration-300">
                    <?php
                    $serverIcon = match($environment['server_type']) {
                        'apache' => 'fa-feather',
                        'nginx' => 'fa-n',
                        'iis' => 'fa-windows',
                        'litespeed' => 'fa-bolt',
                        default => 'fa-server'
                    };
                    ?>
                    <i class="fas <?= $serverIcon ?> text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('welcome.web_server') ?></div>
                    <div class="font-medium text-slate-800 dark:text-white">
                        <?= ucfirst($environment['server_type']) ?>
                        <?php if ($environment['server_type'] === 'unknown'): ?>
                            <span class="text-xs text-amber-600 dark:text-amber-400"><?= \App\Modules\Install\Support\Lang::get('welcome.not_detected') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- PHP -->
            <div class="flex items-center gap-3 p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors duration-300">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center transition-colors duration-300">
                    <i class="fab fa-php text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('welcome.php_version') ?></div>
                    <div class="font-medium text-slate-800 dark:text-white"><?= $environment['php_version'] ?></div>
                </div>
            </div>

            <!-- OS -->
            <div class="flex items-center gap-3 p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors duration-300">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center transition-colors duration-300">
                    <?php
                    $osIcon = match(strtolower($environment['os'])) {
                        'darwin' => 'fa-apple',
                        'linux' => 'fa-linux',
                        'windows' => 'fa-windows',
                        default => 'fa-desktop'
                    };
                    ?>
                    <i class="fab <?= $osIcon ?> text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('welcome.system') ?></div>
                    <div class="font-medium text-slate-800 dark:text-white"><?= $environment['os'] ?></div>
                </div>
            </div>

            <!-- Mémoire -->
            <div class="flex items-center gap-3 p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors duration-300">
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center transition-colors duration-300">
                    <i class="fas fa-memory text-amber-600 dark:text-amber-400"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('welcome.php_memory') ?></div>
                    <div class="font-medium text-slate-800 dark:text-white"><?= $environment['memory_limit'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fonctionnalités -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 sm:mb-8">
        <div class="text-center p-4">
            <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center mx-auto mb-3 transition-colors duration-300">
                <i class="fas fa-database text-emerald-600 dark:text-emerald-400"></i>
            </div>
            <h4 class="font-medium text-slate-800 dark:text-white"><?= \App\Modules\Install\Support\Lang::get('welcome.feature_no_db_title') ?></h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><?= \App\Modules\Install\Support\Lang::get('welcome.feature_no_db_desc') ?></p>
        </div>
        <div class="text-center p-4">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mx-auto mb-3 transition-colors duration-300">
                <i class="fas fa-palette text-blue-600 dark:text-blue-400"></i>
            </div>
            <h4 class="font-medium text-slate-800 dark:text-white"><?= \App\Modules\Install\Support\Lang::get('welcome.feature_themes_title') ?></h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><?= \App\Modules\Install\Support\Lang::get('welcome.feature_themes_desc') ?></p>
        </div>
        <div class="text-center p-4">
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mx-auto mb-3 transition-colors duration-300">
                <i class="fas fa-bolt text-purple-600 dark:text-purple-400"></i>
            </div>
            <h4 class="font-medium text-slate-800 dark:text-white"><?= \App\Modules\Install\Support\Lang::get('welcome.feature_fast_title') ?></h4>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><?= \App\Modules\Install\Support\Lang::get('welcome.feature_fast_desc') ?></p>
        </div>
    </div>

    <!-- Action -->
    <form method="POST" action="<?= htmlspecialchars($installUrl) ?>">
        <input type="hidden" name="action" value="start">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        
        <div class="flex flex-col items-center">
            <button type="submit" class="w-full sm:w-auto px-6 sm:px-8 py-3 sm:py-4 bg-gradient-to-r from-brand-600 to-purple-600 text-white font-semibold rounded-xl hover:from-brand-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <i class="fas fa-arrow-right mr-2"></i>
                <?= \App\Modules\Install\Support\Lang::get('welcome.start_installation') ?>
            </button>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-4">
                <i class="fas fa-clock mr-1"></i>
                <?= \App\Modules\Install\Support\Lang::get('welcome.installation_time') ?>
            </p>
        </div>
    </form>
</div>
