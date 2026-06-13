<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<?php
$basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4);
$publicUrlBase = $publicUrl ?? (defined('PUBLIC_URL') ? PUBLIC_URL : '');

if (!function_exists('install_theme_screenshot')) {
    function install_theme_screenshot(string $themeDir, string $publicUrlBase, string $basePath): string
    {
        $screenshot = 'screenshot.png';
        $themeJson = $themeDir . '/theme.json';
        if (is_file($themeJson)) {
            $json = json_decode((string)file_get_contents($themeJson), true);
            if (!empty($json['screenshot'])) {
                $screenshot = (string)$json['screenshot'];
            }
        }

        $themeFile = rtrim($themeDir, '/') . '/' . ltrim($screenshot, '/');
        $relative = ltrim(str_replace($basePath, '', $themeDir), '/');
        $publicPath = rtrim($basePath, '/') . '/public/' . $relative . '/' . ltrim($screenshot, '/');
        $publicUrl = rtrim($publicUrlBase, '/') . '/' . $relative . '/' . ltrim($screenshot, '/');

        if (is_file($themeFile)) {
            $mime = function_exists('mime_content_type') ? mime_content_type($themeFile) : 'image/png';
            $data = base64_encode((string)file_get_contents($themeFile));
            return 'data:' . $mime . ';base64,' . $data;
        }

        if (is_file($publicPath)) {
            return $publicUrl;
        }

        return '';
    }
}

$adminModernShot = install_theme_screenshot($basePath . '/themes/admin/admin-modern-pro', $publicUrlBase, $basePath);
$adminDefaultShot = install_theme_screenshot($basePath . '/themes/admin/default', $publicUrlBase, $basePath);
$frontModernShot = install_theme_screenshot($basePath . '/themes/frontend/modern-pro', $publicUrlBase, $basePath);
$frontDefaultShot = install_theme_screenshot($basePath . '/themes/frontend/default', $publicUrlBase, $basePath);
?>

<!-- Étape 8 : Choix du Design / Thèmes -->
<div class="p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <div class="text-center mb-6 sm:mb-8">
        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <i class="fas fa-palette text-2xl sm:text-3xl text-white"></i>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white mb-2"><?= \App\Modules\Install\Support\Lang::get('design.title') ?></h2>
        <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('design.subtitle') ?></p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6 flex items-center gap-3 transition-colors duration-300">
            <i class="fas fa-exclamation-circle text-xl text-red-600 dark:text-red-400"></i>
            <div class="text-red-700 dark:text-red-300 text-sm">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($installUrl) ?>">
        <input type="hidden" name="action" value="save_design">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

        <!-- Thème Admin -->
        <div class="mb-6 sm:mb-8">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
                <i class="fas fa-cog text-brand-600 dark:text-brand-400 mr-2"></i>
                <?= \App\Modules\Install\Support\Lang::get('design.admin_theme') ?>
            </h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4"><?= \App\Modules\Install\Support\Lang::get('design.admin_theme_desc') ?></p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Thème Admin Modern Pro -->
                <label class="relative cursor-pointer group install-theme-option">
                    <input type="radio" name="admin_theme" value="admin-modern-pro" checked class="peer sr-only">
                    <div class="install-theme-card border-2 border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-all peer-checked:border-brand-600 peer-checked:ring-4 peer-checked:ring-brand-600/20 hover:border-slate-300 dark:hover:border-slate-600">
                        <!-- Preview Image -->
                        <div class="install-theme-preview aspect-video bg-slate-100 dark:bg-slate-800 relative overflow-hidden">
                            <img src="<?= $adminModernShot !== '' ? $adminModernShot : ($publicUrl . '/themes/admin/admin-modern-pro/screenshot.png') ?>"
                                 alt="Admin Modern Pro"
                                 class="w-full h-full object-cover"
                                 data-install-fallback="install-fallback-admin-modern">
                            <div id="install-fallback-admin-modern" class="install-theme-fallback absolute inset-0 flex items-center justify-center bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900" hidden>
                                <div class="text-center text-white">
                                    <i class="fas fa-sparkles text-4xl mb-2 opacity-80"></i>
                                    <p class="text-sm font-medium">Admin Modern Pro</p>
                                </div>
                            </div>
                            <div class="absolute top-2 right-2">
                                <span class="badge-success">
                                    <i class="fas fa-star text-xs mr-1"></i>
                                    <?= \App\Modules\Install\Support\Lang::get('design.recommended') ?>
                                </span>
                            </div>
                        </div>
                        <!-- Info -->
                        <div class="p-4 bg-white dark:bg-slate-800 transition-colors duration-300">
                            <h4 class="font-semibold text-slate-800 dark:text-white mb-1">Admin Modern Pro</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('design.theme_admin_modern_desc') ?></p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-palette mr-1"></i> Indigo
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-mobile-alt mr-1"></i> Responsive
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-sparkles mr-1"></i> Glassmorphism
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Checkmark -->
                    <div class="absolute top-3 left-3 w-6 h-6 bg-brand-600 rounded-full items-center justify-center text-white hidden peer-checked:flex shadow-lg">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                </label>

                <!-- Thème Admin Default -->
                <label class="relative cursor-pointer group install-theme-option">
                    <input type="radio" name="admin_theme" value="default" class="peer sr-only">
                    <div class="install-theme-card border-2 border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-all peer-checked:border-brand-600 peer-checked:ring-4 peer-checked:ring-brand-600/20 hover:border-slate-300 dark:hover:border-slate-600">
                        <!-- Preview Image -->
                        <div class="install-theme-preview aspect-video bg-slate-100 dark:bg-slate-800 relative overflow-hidden">
                            <img src="<?= $adminDefaultShot !== '' ? $adminDefaultShot : ($publicUrl . '/themes/admin/default/screenshot.png') ?>"
                                 alt="Admin Default"
                                 class="w-full h-full object-cover"
                                 data-install-fallback="install-fallback-admin-default">
                            <div id="install-fallback-admin-default" class="install-theme-fallback absolute inset-0 flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800" hidden>
                                <div class="text-center text-slate-700 dark:text-slate-300">
                                    <i class="fas fa-th-large text-4xl mb-2 opacity-60"></i>
                                    <p class="text-sm font-medium">Admin Default</p>
                                </div>
                            </div>
                        </div>
                        <!-- Info -->
                        <div class="p-4 bg-white dark:bg-slate-800 transition-colors duration-300">
                            <h4 class="font-semibold text-slate-800 dark:text-white mb-1">Default</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('design.theme_admin_default_desc') ?></p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-palette mr-1"></i> <?= \App\Modules\Install\Support\Lang::get('design.tag_neutral') ?>
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-feather mr-1"></i> <?= \App\Modules\Install\Support\Lang::get('design.tag_light') ?>
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-clock mr-1"></i> <?= \App\Modules\Install\Support\Lang::get('design.tag_classic') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Checkmark -->
                    <div class="absolute top-3 left-3 w-6 h-6 bg-brand-600 rounded-full items-center justify-center text-white hidden peer-checked:flex shadow-lg">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                </label>
            </div>
        </div>

        <!-- Thème Frontend -->
        <div class="mb-6 sm:mb-8">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
                <i class="fas fa-desktop text-brand-600 dark:text-brand-400 mr-2"></i>
                <?= \App\Modules\Install\Support\Lang::get('design.frontend_theme') ?>
            </h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4"><?= \App\Modules\Install\Support\Lang::get('design.frontend_theme_desc') ?></p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Thème Frontend Modern Pro -->
                <label class="relative cursor-pointer group install-theme-option">
                    <input type="radio" name="frontend_theme" value="modern-pro" checked class="peer sr-only">
                    <div class="install-theme-card border-2 border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-all peer-checked:border-brand-600 peer-checked:ring-4 peer-checked:ring-brand-600/20 hover:border-slate-300 dark:hover:border-slate-600">
                        <!-- Preview Image -->
                        <div class="install-theme-preview aspect-video bg-slate-100 dark:bg-slate-800 relative overflow-hidden">
                            <img src="<?= $frontModernShot !== '' ? $frontModernShot : ($publicUrl . '/themes/frontend/modern-pro/screenshot.png') ?>"
                                 alt="Frontend Modern Pro"
                                 class="w-full h-full object-cover"
                                 data-install-fallback="install-fallback-front-modern">
                            <div id="install-fallback-front-modern" class="install-theme-fallback absolute inset-0 flex items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600" hidden>
                                <div class="text-center text-white">
                                    <i class="fas fa-magic text-4xl mb-2 opacity-80"></i>
                                    <p class="text-sm font-medium">Modern Pro</p>
                                </div>
                            </div>
                            <div class="absolute top-2 right-2">
                                <span class="badge-success">
                                    <i class="fas fa-star text-xs mr-1"></i>
                                    <?= \App\Modules\Install\Support\Lang::get('design.recommended') ?>
                                </span>
                            </div>
                        </div>
                        <!-- Info -->
                        <div class="p-4 bg-white dark:bg-slate-800 transition-colors duration-300">
                            <h4 class="font-semibold text-slate-800 dark:text-white mb-1">Modern Pro</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('design.theme_frontend_modern_desc') ?></p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-mobile-alt mr-1"></i> Mobile First
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-bolt mr-1"></i> <?= \App\Modules\Install\Support\Lang::get('design.tag_fast') ?>
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-search mr-1"></i> SEO
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Checkmark -->
                    <div class="absolute top-3 left-3 w-6 h-6 bg-brand-600 rounded-full items-center justify-center text-white hidden peer-checked:flex shadow-lg">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                </label>

                <!-- Thème Frontend Default -->
                <label class="relative cursor-pointer group install-theme-option">
                    <input type="radio" name="frontend_theme" value="default" class="peer sr-only">
                    <div class="install-theme-card border-2 border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden transition-all peer-checked:border-brand-600 peer-checked:ring-4 peer-checked:ring-brand-600/20 hover:border-slate-300 dark:hover:border-slate-600">
                        <!-- Preview Image -->
                        <div class="install-theme-preview aspect-video bg-slate-100 dark:bg-slate-800 relative overflow-hidden">
                            <img src="<?= $frontDefaultShot !== '' ? $frontDefaultShot : ($publicUrl . '/themes/frontend/default/screenshot.png') ?>"
                                 alt="Frontend Default"
                                 class="w-full h-full object-cover"
                                 data-install-fallback="install-fallback-front-default">
                            <div id="install-fallback-front-default" class="install-theme-fallback absolute inset-0 flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-slate-700 dark:to-slate-800" hidden>
                                <div class="text-center text-slate-700 dark:text-slate-300">
                                    <i class="fas fa-laptop text-4xl mb-2 opacity-60"></i>
                                    <p class="text-sm font-medium">Default</p>
                                </div>
                            </div>
                        </div>
                        <!-- Info -->
                        <div class="p-4 bg-white dark:bg-slate-800 transition-colors duration-300">
                            <h4 class="font-semibold text-slate-800 dark:text-white mb-1">Default</h4>
                            <p class="text-xs text-slate-600 dark:text-slate-400"><?= \App\Modules\Install\Support\Lang::get('design.theme_frontend_default_desc') ?></p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-mobile-alt mr-1"></i> Responsive
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-feather mr-1"></i> <?= \App\Modules\Install\Support\Lang::get('design.tag_minimalist') ?>
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">
                                    <i class="fas fa-search mr-1"></i> SEO
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Checkmark -->
                    <div class="absolute top-3 left-3 w-6 h-6 bg-brand-600 rounded-full items-center justify-center text-white hidden peer-checked:flex shadow-lg">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                </label>
            </div>
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6 flex items-center gap-3 transition-colors duration-300">
            <i class="fas fa-info-circle text-xl text-blue-600 dark:text-blue-400"></i>
            <div>
                <p class="font-medium text-blue-700 dark:text-blue-300"><?= \App\Modules\Install\Support\Lang::get('design.info_title') ?></p>
                <p class="text-sm mt-1 text-blue-600 dark:text-blue-400"><?= \App\Modules\Install\Support\Lang::get('design.info_message') ?></p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
            <a href="<?= $installUrl ?>?step=7" class="px-6 py-3 text-center sm:text-left text-slate-600 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white font-medium transition-colors">
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
