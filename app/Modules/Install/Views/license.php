<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Étape 2 : Licence FlatCMS -->
<?php
// Charger le fichier de licence selon la langue active (avec fallback robuste)
$lang = \App\Modules\Install\Support\Lang::getCurrentLang();
$licenseDir = __DIR__ . '/../Languages/';
$licenseCandidates = [
    $licenseDir . 'LICENSE-' . $lang . '.txt',
];

if (str_contains($lang, '-')) {
    $langShort = strtolower((string) strtok($lang, '-'));
    if ($langShort === 'en') {
        $licenseCandidates[] = $licenseDir . 'LICENSE-en-US.txt';
    }
}

$licenseCandidates[] = $licenseDir . 'LICENSE-en-US.txt';
$licenseCandidates[] = $licenseDir . 'LICENSE-fr-FR.txt';

$licenseContent = '';
foreach ($licenseCandidates as $candidate) {
    if (is_file($candidate)) {
        $content = (string) file_get_contents($candidate);
        if ($content !== '') {
            $licenseContent = $content;
            break;
        }
    }
}
?>
<div class="p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <i class="fas fa-file-contract text-2xl text-white" aria-hidden="true"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('license.title') ?></h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('license.subtitle') ?></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6 transition-colors duration-300">
        <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span class="font-medium"><?= \App\Modules\Install\Support\Lang::get('common.error') ?></span>
        </div>
        <ul class="mt-2 text-sm text-red-600 dark:text-red-400">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Licence Text -->
    <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-4 sm:p-6 mb-6 max-h-60 sm:max-h-80 overflow-y-auto transition-colors duration-300">
        <pre class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap font-mono leading-relaxed"><?= htmlspecialchars($licenseContent) ?></pre>
    </div>

    <!-- Acceptation -->
    <form method="POST" action="<?= htmlspecialchars($installUrl) ?>">
        <input type="hidden" name="action" value="accept_license">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        
        <div class="bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800 rounded-xl p-4 mb-6 transition-colors duration-300">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="accept_license" value="1" 
                       class="mt-1 w-5 h-5 text-brand-600 bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 rounded focus:ring-brand-500 transition-colors duration-300" required aria-required="true">
                <span class="text-slate-700 dark:text-slate-200">
                    <strong class="text-slate-800 dark:text-white"><?= \App\Modules\Install\Support\Lang::get('license.accept_label') ?></strong>
                    <br>
                    <span class="text-sm text-slate-600 dark:text-slate-400">
                        <?= \App\Modules\Install\Support\Lang::get('license.accept_confirmation') ?>
                    </span>
                </span>
            </label>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <a href="<?= htmlspecialchars($installUrl) ?>?step=1" class="px-6 py-3 text-center sm:text-left text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                <?= \App\Modules\Install\Support\Lang::get('common.back') ?>
            </a>
            <button type="submit" class="px-6 sm:px-8 py-3 bg-gradient-to-r from-brand-600 to-purple-600 text-white font-semibold rounded-xl hover:from-brand-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                <?= \App\Modules\Install\Support\Lang::get('license.accept_and_continue') ?>
                <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
            </button>
        </div>
    </form>
</div>
