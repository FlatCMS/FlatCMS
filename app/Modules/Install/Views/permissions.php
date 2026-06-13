<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Étape 4 : Vérification des permissions -->
<?php
$paths = $environment['writable_paths'] ?? [];
$allWritable = true;
foreach ($paths as $info) {
    if (!$info['writable']) {
        $allWritable = false;
        break;
    }
}
?>

<div class="p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <i class="fas fa-folder-open text-2xl text-white" aria-hidden="true"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('permissions.title') ?></h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('permissions.subtitle') ?></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6 transition-colors duration-300">
        <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span class="font-medium"><?= \App\Modules\Install\Support\Lang::get('permissions.permission_issues') ?></span>
        </div>
        <ul class="mt-2 text-sm text-red-600 dark:text-red-400 list-disc list-inside">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Info Box -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6 transition-colors duration-300">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-0.5" aria-hidden="true"></i>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <strong><?= \App\Modules\Install\Support\Lang::get('permissions.info') ?></strong>
                <?php if (strtolower($environment['os']) !== 'windows'): ?>
                    <br><?= \App\Modules\Install\Support\Lang::get('permissions.chmod_help') ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Permissions List -->
    <div class="space-y-2 mb-6">
        <?php foreach ($paths as $name => $info): ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-lg border transition-colors duration-300
            <?= $info['writable'] 
                ? 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700' 
                : 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-800' ?>">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors duration-300
                    <?= $info['writable'] 
                        ? 'bg-emerald-100 dark:bg-emerald-900/30' 
                        : 'bg-red-100 dark:bg-red-900/30' ?>">
                    <?php if ($info['writable']): ?>
                        <i class="fas fa-check text-sm text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                    <?php else: ?>
                        <i class="fas fa-times text-sm text-red-600 dark:text-red-400" aria-hidden="true"></i>
                    <?php endif; ?>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="font-medium text-slate-800 dark:text-white text-sm"><?= htmlspecialchars($name) ?></div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 font-mono truncate"><?= htmlspecialchars($info['path']) ?></div>
                </div>
            </div>
            <div class="flex items-center gap-2 pl-11 sm:pl-0">
                <?php if (!$info['exists']): ?>
                    <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-xs rounded-full transition-colors duration-300"><?= \App\Modules\Install\Support\Lang::get('permissions.status_not_exist') ?></span>
                <?php elseif (!$info['writable']): ?>
                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs rounded-full transition-colors duration-300"><?= \App\Modules\Install\Support\Lang::get('permissions.status_not_writable') ?></span>
                <?php else: ?>
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs rounded-full transition-colors duration-300"><?= \App\Modules\Install\Support\Lang::get('requirements.status_ok') ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Résumé -->
    <div class="border rounded-xl p-4 mb-6 transition-colors duration-300
        <?= $allWritable 
            ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' 
            : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' ?>">
        <div class="flex items-center gap-3">
            <?php if ($allWritable): ?>
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center transition-colors duration-300">
                    <i class="fas fa-check-circle text-2xl text-emerald-600 dark:text-emerald-400" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="font-semibold text-emerald-800 dark:text-emerald-300"><?= \App\Modules\Install\Support\Lang::get('permissions.summary_correct') ?></div>
                    <div class="text-sm text-emerald-600 dark:text-emerald-400"><?= \App\Modules\Install\Support\Lang::get('permissions.summary_correct_desc') ?></div>
                </div>
            <?php else: ?>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center transition-colors duration-300">
                    <i class="fas fa-times-circle text-2xl text-red-600 dark:text-red-400" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="font-semibold text-red-800 dark:text-red-300"><?= \App\Modules\Install\Support\Lang::get('permissions.summary_issues') ?></div>
                    <div class="text-sm text-red-600 dark:text-red-400"><?= \App\Modules\Install\Support\Lang::get('permissions.summary_issues_desc') ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Commande d'aide -->
    <?php if (!$allWritable && strtolower($environment['os']) !== 'windows'): ?>
    <div class="bg-slate-800 dark:bg-slate-900 border border-slate-700 dark:border-slate-600 rounded-xl p-4 mb-6 transition-colors duration-300">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
            <span class="text-slate-300 dark:text-slate-400 text-sm"><?= \App\Modules\Install\Support\Lang::get('permissions.fix_command') ?></span>
            <button type="button" data-action="copy-command" data-cmd="chmod -R 755 data storage public/themes public/uploads public/modules" class="text-xs text-brand-400 dark:text-brand-300 hover:text-brand-300 dark:hover:text-brand-200 self-start sm:self-auto transition-colors">
                <i class="fas fa-copy mr-1" aria-hidden="true"></i><?= \App\Modules\Install\Support\Lang::get('common.copy') ?>
            </button>
        </div>
        <code class="text-emerald-400 dark:text-emerald-300 text-xs sm:text-sm font-mono block overflow-x-auto">chmod -R 755 data storage public/themes public/uploads public/modules</code>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <form method="POST" action="<?= htmlspecialchars($installUrl) ?>">
        <input type="hidden" name="action" value="check_permissions">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <a href="<?= htmlspecialchars($installUrl) ?>?step=3" class="px-6 py-3 text-center sm:text-left text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                <?= \App\Modules\Install\Support\Lang::get('common.back') ?>
            </a>
            
            <?php if ($allWritable): ?>
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
