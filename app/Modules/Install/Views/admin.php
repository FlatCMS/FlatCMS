<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!-- Étape 6 : Création du compte administrateur -->
<div class="p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <i class="fas fa-user-shield text-2xl text-white" aria-hidden="true"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('admin.title') ?></h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('admin.subtitle') ?></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6 transition-colors duration-300">
        <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span class="font-medium"><?= \App\Modules\Install\Support\Lang::get('admin.validation_errors') ?></span>
        </div>
        <ul class="mt-2 text-sm text-red-600 dark:text-red-400 list-disc list-inside">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($installUrl) ?>">
        <input type="hidden" name="action" value="create_admin">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

        <div class="space-y-5">
            <!-- Identité -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="admin_first_name" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                        <?= \App\Modules\Install\Support\Lang::get('admin.field_first_name') ?> <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-user text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                        </div>
                        <input type="text" id="admin_first_name" name="admin_first_name" required
                               class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                               placeholder="<?= \App\Modules\Install\Support\Lang::get('admin.field_first_name_placeholder') ?>"
                               value="<?= htmlspecialchars($_POST['admin_first_name'] ?? '') ?>">
                    </div>
                </div>

                <div>
                    <label for="admin_name" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                        <?= \App\Modules\Install\Support\Lang::get('admin.field_last_name') ?> <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-user text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                        </div>
                        <input type="text" id="admin_name" name="admin_name" required
                               class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                               placeholder="<?= \App\Modules\Install\Support\Lang::get('admin.field_last_name_placeholder') ?>"
                               value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="admin_email" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    <?= \App\Modules\Install\Support\Lang::get('admin.field_email') ?> <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                    </div>
                    <input type="email" id="admin_email" name="admin_email" required
                           class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                           placeholder="<?= \App\Modules\Install\Support\Lang::get('admin.field_email_placeholder') ?>"
                           value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>">
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= \App\Modules\Install\Support\Lang::get('admin.field_email_help') ?></p>
            </div>

            <!-- Mot de passe -->
            <div>
                <label for="admin_password" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    <?= \App\Modules\Install\Support\Lang::get('admin.field_password') ?> <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                    </div>
                    <input type="password" id="admin_password" name="admin_password" required minlength="8"
                           class="w-full pl-11 pr-12 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                           placeholder="<?= \App\Modules\Install\Support\Lang::get('admin.field_password_placeholder') ?>">
                    <button type="button" data-action="toggle-password" data-toggle-target="admin_password"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?= \App\Modules\Install\Support\Lang::get('admin.field_password_help') ?></p>
            </div>

            <!-- Confirmation mot de passe -->
            <div>
                <label for="admin_password_confirm" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                    <?= \App\Modules\Install\Support\Lang::get('admin.field_password_confirm') ?> <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-slate-400 dark:text-slate-500" aria-hidden="true"></i>
                    </div>
                    <input type="password" id="admin_password_confirm" name="admin_password_confirm" required minlength="8"
                           class="w-full pl-11 pr-12 py-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all"
                           placeholder="<?= \App\Modules\Install\Support\Lang::get('admin.field_password_placeholder') ?>">
                    <button type="button" data-action="toggle-password" data-toggle-target="admin_password_confirm"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Info sécurité -->
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mt-6 mb-6 transition-colors duration-300">
            <div class="flex items-start gap-3">
                <i class="fas fa-shield-alt text-amber-600 dark:text-amber-400 mt-0.5" aria-hidden="true"></i>
                <div class="text-sm text-amber-700 dark:text-amber-300">
                    <strong><?= \App\Modules\Install\Support\Lang::get('admin.security_info') ?></strong>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <a href="<?= htmlspecialchars($installUrl) ?>?step=5" class="px-6 py-3 text-center sm:text-left text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                <?= \App\Modules\Install\Support\Lang::get('common.back') ?>
            </a>
            <button type="submit" class="px-6 sm:px-8 py-3 bg-gradient-to-r from-brand-600 to-purple-600 text-white font-semibold rounded-xl hover:from-brand-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                <?= \App\Modules\Install\Support\Lang::get('admin.create_account') ?>
                <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
            </button>
        </div>
    </form>
</div>
