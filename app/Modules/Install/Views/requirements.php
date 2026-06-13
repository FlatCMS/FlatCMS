<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Étape 3 : Vérification des prérequis -->
<?php
// Récupérer les requirements si pas déjà définis
if (!isset($requirements)) {
    $requirements = [
        ['name' => \App\Modules\Install\Support\Lang::get('requirements.php_version'), 'required' => true, 'passed' => version_compare(PHP_VERSION, '8.2.0', '>='), 'current' => PHP_VERSION, 'minimum' => '8.2.0', 'message' => \App\Modules\Install\Support\Lang::get('requirements.php_version_msg')],
        ['name' => \App\Modules\Install\Support\Lang::get('requirements.ext_json'), 'required' => true, 'passed' => extension_loaded('json'), 'message' => \App\Modules\Install\Support\Lang::get('requirements.ext_json_msg')],
        ['name' => \App\Modules\Install\Support\Lang::get('requirements.ext_mbstring'), 'required' => true, 'passed' => extension_loaded('mbstring'), 'message' => \App\Modules\Install\Support\Lang::get('requirements.ext_mbstring_msg')],
        ['name' => \App\Modules\Install\Support\Lang::get('requirements.ext_session'), 'required' => true, 'passed' => extension_loaded('session'), 'message' => \App\Modules\Install\Support\Lang::get('requirements.ext_session_msg')],
        ['name' => \App\Modules\Install\Support\Lang::get('requirements.ext_fileinfo'), 'required' => true, 'passed' => extension_loaded('fileinfo'), 'message' => \App\Modules\Install\Support\Lang::get('requirements.ext_fileinfo_msg')],
        ['name' => \App\Modules\Install\Support\Lang::get('requirements.ext_openssl'), 'required' => false, 'passed' => extension_loaded('openssl'), 'message' => \App\Modules\Install\Support\Lang::get('requirements.ext_openssl_msg')],
        ['name' => \App\Modules\Install\Support\Lang::get('requirements.ext_gd'), 'required' => false, 'passed' => extension_loaded('gd'), 'message' => \App\Modules\Install\Support\Lang::get('requirements.ext_gd_msg')],
        ['name' => \App\Modules\Install\Support\Lang::get('requirements.ext_zip'), 'required' => false, 'passed' => extension_loaded('zip'), 'message' => \App\Modules\Install\Support\Lang::get('requirements.ext_zip_msg')],
        ['name' => \App\Modules\Install\Support\Lang::get('requirements.ext_curl'), 'required' => false, 'passed' => extension_loaded('curl'), 'message' => \App\Modules\Install\Support\Lang::get('requirements.ext_curl_msg')],
    ];
}

$allPassed = true;
foreach ($requirements as $req) {
    if ($req['required'] && !$req['passed']) {
        $allPassed = false;
        break;
    }
}
?>

<div class="p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <i class="fas fa-clipboard-check text-2xl text-white" aria-hidden="true"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('requirements.title') ?></h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('requirements.subtitle') ?></p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6 transition-colors duration-300">
            <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span class="font-medium"><?= \App\Modules\Install\Support\Lang::get('requirements.missing_requirements') ?></span>
            </div>
            <ul class="mt-2 text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Requirements List -->
    <div class="space-y-3 mb-6">
        <?php foreach ($requirements as $req): ?>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-xl border transition-colors duration-300 
                <?= $req['passed'] 
                    ? 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700' 
                    : ($req['required'] 
                        ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-800' 
                        : 'bg-amber-50 dark:bg-amber-900/20 border-amber-300 dark:border-amber-800') ?>">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors duration-300
                        <?= $req['passed'] 
                            ? 'bg-emerald-100 dark:bg-emerald-900/30' 
                            : ($req['required'] 
                                ? 'bg-red-100 dark:bg-red-900/30' 
                                : 'bg-amber-100 dark:bg-amber-900/30') ?>">
                        <?php if ($req['passed']): ?>
                            <i class="fas fa-check text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                        <?php elseif ($req['required']): ?>
                            <i class="fas fa-times text-red-600 dark:text-red-400" aria-hidden="true"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="font-medium text-slate-800 dark:text-white">
                            <?= htmlspecialchars($req['name']) ?>
                            <?php if (!$req['required']): ?>
                                <span class="text-xs text-slate-500 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('requirements.optional') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-400"><?= htmlspecialchars($req['message']) ?></div>
                    </div>
                </div>
                <div class="text-left sm:text-right pl-13 sm:pl-0">
                    <?php if (isset($req['current'])): ?>
                        <div class="text-sm font-medium <?= $req['passed'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' ?>">
                            <?= htmlspecialchars($req['current']) ?>
                        </div>
                        <?php if (isset($req['minimum'])): ?>
                            <div class="text-xs text-slate-500 dark:text-slate-400">min: <?= htmlspecialchars($req['minimum']) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors duration-300
                            <?= $req['passed'] 
                                ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300' 
                                : ($req['required'] 
                                    ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' 
                                    : 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300') ?>">
                            <?= $req['passed'] ? \App\Modules\Install\Support\Lang::get('requirements.status_ok') : ($req['required'] ? \App\Modules\Install\Support\Lang::get('requirements.status_missing') : \App\Modules\Install\Support\Lang::get('requirements.status_not_installed')) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Résumé -->
    <div class="border rounded-xl p-4 mb-6 transition-colors duration-300
        <?= $allPassed 
            ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' 
            : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' ?>">
        <div class="flex items-center gap-3">
            <?php if ($allPassed): ?>
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center transition-colors duration-300">
                    <i class="fas fa-check-circle text-2xl text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="font-semibold text-emerald-800 dark:text-emerald-300"><?= \App\Modules\Install\Support\Lang::get('requirements.summary_compatible') ?></div>
                    <div class="text-sm text-emerald-600 dark:text-emerald-400"><?= \App\Modules\Install\Support\Lang::get('requirements.summary_compatible_desc') ?></div>
                </div>
            <?php else: ?>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center transition-colors duration-300">
                    <i class="fas fa-times-circle text-2xl text-red-600 dark:text-red-400" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="font-semibold text-red-800 dark:text-red-300"><?= \App\Modules\Install\Support\Lang::get('requirements.summary_issues') ?></div>
                    <div class="text-sm text-red-600 dark:text-red-400"><?= \App\Modules\Install\Support\Lang::get('requirements.summary_issues_desc') ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions -->
    <form method="POST" action="<?= htmlspecialchars($installUrl) ?>">
        <input type="hidden" name="action" value="check_requirements">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <a href="<?= htmlspecialchars($installUrl) ?>?step=2" class="px-6 py-3 text-center sm:text-left text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                <?= \App\Modules\Install\Support\Lang::get('common.back') ?>
            </a>

            <?php if ($allPassed): ?>
                <button type="submit" class="px-6 sm:px-8 py-3 bg-gradient-to-r from-brand-600 to-purple-600 text-white font-semibold rounded-xl hover:from-brand-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                    <?= \App\Modules\Install\Support\Lang::get('common.continue') ?>
                    <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
                </button>
            <?php else: ?>
                <button type="button" data-action="reload-page" class="px-6 sm:px-8 py-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-xl hover:bg-slate-300 dark:hover:bg-slate-600 transition-all">
                    <i class="fas fa-sync-alt mr-2" aria-hidden="true"></i>
                    <?= \App\Modules\Install\Support\Lang::get('common.recheck') ?>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>
