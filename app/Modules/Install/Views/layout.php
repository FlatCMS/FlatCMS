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
$installUrl = (string) ($installUrl ?? '/index.php');
$installPath = (string) parse_url($installUrl, PHP_URL_PATH);
$requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if ($installPath === '') {
    $installPath = '/index.php';
}

// Certains environnements Nginx exposent SCRIPT_NAME tronqué (/index.php)
// alors que REQUEST_URI contient le sous-dossier réel (/flatcms/index.php).
if ($requestPath !== '' && in_array($installPath, ['/index.php', '/install/index.php'], true)) {
    if (str_ends_with($requestPath, '/index.php')) {
        $installPath = $requestPath;
    } elseif (!str_ends_with($requestPath, '.php') && $requestPath !== '/') {
        $requestDir = rtrim($requestPath, '/');
        $installPath = $requestDir . '/index.php';
    }
}

$installBase = str_replace('\\', '/', dirname($installPath));
if ($installBase === '/' || $installBase === '.') {
    $installBase = '';
}

// Determine site base and asset roots for both deployment modes:
// - docroot = project root  -> /public/assets
// - docroot = public/       -> /assets
$siteBase = $installBase;
if ($siteBase === '/install') {
    $siteBase = '';
} elseif (str_ends_with($siteBase, '/install')) {
    $siteBase = substr($siteBase, 0, -strlen('/install'));
}

if ($siteBase === '/public') {
    $siteBase = '';
} elseif (str_ends_with($siteBase, '/public')) {
    $siteBase = substr($siteBase, 0, -strlen('/public'));
}

if ($siteBase === '/') {
    $siteBase = '';
}

$assetCandidateA = ($siteBase !== '' ? $siteBase : '') . '/public/assets';
$assetCandidateB = ($siteBase !== '' ? $siteBase : '') . '/assets';

// Detect valid asset base server-side (no inline onerror fallback required).
$documentRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
$chosenAssetBase = $assetCandidateA;
$assetCandidates = [];
if (!in_array($assetCandidateA, $assetCandidates, true)) {
    $assetCandidates[] = $assetCandidateA;
}
if (!in_array($assetCandidateB, $assetCandidates, true)) {
    $assetCandidates[] = $assetCandidateB;
}

foreach ($assetCandidates as $candidate) {
    if ($candidate === '') {
        continue;
    }
    $candidateFs = $documentRoot . $candidate;
    if (
        is_file($candidateFs . '/install/main.js')
        && is_file($candidateFs . '/install/style.css')
        && is_file($candidateFs . '/install/theme-init.js')
    ) {
        $chosenAssetBase = $candidate;
        break;
    }
}

$fontAwesomeHref = $chosenAssetBase . '/dists/fontawesome/css/all.min.css';
$installThemeInitSrc = $chosenAssetBase . '/install/theme-init.js';
$installTailwindStaticHref = $chosenAssetBase . '/install/tailwind-static.css';
$installStyleHref = $chosenAssetBase . '/install/style.css';
$installMainSrc = $chosenAssetBase . '/install/main.js';

$basePath = defined('BASE_PATH') ? rtrim((string) BASE_PATH, '/\\') : '';
$assetVersion = static function (string $fsPath): string {
    return is_file($fsPath) ? (string) filemtime($fsPath) : (string) time();
};
$installAssetFsRoot = $basePath !== '' ? ($basePath . '/public/assets/install') : '';
$vendorAssetFsRoot = $basePath !== '' ? ($basePath . '/public/assets/dists') : '';
$themeInitVersion = $assetVersion($installAssetFsRoot . '/theme-init.js');
$tailwindVersion = $assetVersion($installAssetFsRoot . '/tailwind-static.css');
$installStyleVersion = $assetVersion($installAssetFsRoot . '/style.css');
$installMainVersion = $assetVersion($installAssetFsRoot . '/main.js');
$fontAwesomeVersion = $assetVersion($vendorAssetFsRoot . '/fontawesome/css/all.min.css');

$loadingLabel = (string) \App\Modules\Install\Support\Lang::get('common.loading');
$commandCopiedLabel = (string) \App\Modules\Install\Support\Lang::get('common.command_copied');
$currentInstallLang = \App\Modules\Install\Support\Lang::getCurrentLang();
$htmlLang = strtolower(str_replace('_', '-', $currentInstallLang));
if ($htmlLang === 'en-en') {
    $htmlLang = 'en-us';
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($htmlLang, ENT_QUOTES, 'UTF-8') ?>" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Installation - FlatCMS V<?= $version ?></title>

    <script src="<?= htmlspecialchars($installThemeInitSrc, ENT_QUOTES, 'UTF-8') ?>?v=<?= htmlspecialchars($themeInitVersion, ENT_QUOTES, 'UTF-8') ?>"></script>

    <!-- Tailwind precompiled CSS (local, CSP-friendly) -->
    <link rel="stylesheet" href="<?= htmlspecialchars($installTailwindStaticHref, ENT_QUOTES, 'UTF-8') ?>?v=<?= htmlspecialchars($tailwindVersion, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($installStyleHref, ENT_QUOTES, 'UTF-8') ?>?v=<?= htmlspecialchars($installStyleVersion, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Font Awesome (local with Nginx path fallback) -->
    <link rel="stylesheet" href="<?= htmlspecialchars($fontAwesomeHref, ENT_QUOTES, 'UTF-8') ?>?v=<?= htmlspecialchars($fontAwesomeVersion, ENT_QUOTES, 'UTF-8') ?>">
    
</head>

<body
    class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800 font-sans antialiased transition-colors duration-300"
    data-loading-label="<?= htmlspecialchars($loadingLabel, ENT_QUOTES, 'UTF-8') ?>"
    data-command-copied-label="<?= htmlspecialchars($commandCopiedLabel, ENT_QUOTES, 'UTF-8') ?>"
>

    <div class="flex flex-col lg:flex-row min-h-screen">

        <!-- Sidebar Navigation (cachée sur mobile, visible sur desktop) -->
        <aside class="hidden lg:flex lg:w-80 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-700 flex-col shadow-xl transition-colors duration-300">
            
            <!-- Header Sidebar -->
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-brand-600 to-purple-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-layer-group text-lg text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-slate-800 dark:text-white">FlatCMS</h1>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Version <?= $version ?></span>
                    </div>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300"><?= \App\Modules\Install\Support\Lang::get('navigation.install_wizard') ?></p>
            </div>

            <!-- Steps Navigation -->
            <nav class="flex-1 p-6 overflow-y-auto">
                <ul class="space-y-1">
                    <?php
                    $stepKeys = [
                        1 => 'welcome',
                        2 => 'license',
                        3 => 'requirements',
                        4 => 'permissions',
                        5 => 'database',
                        6 => 'admin',
                        7 => 'site',
                        8 => 'design',
                        9 => 'sample',
                        10 => 'complete'
                    ];
                    
                    $stepIcons = [
                        'welcome' => 'fa-rocket',
                        'license' => 'fa-file-contract',
                        'requirements' => 'fa-check-circle',
                        'permissions' => 'fa-lock',
                        'database' => 'fa-database',
                        'admin' => 'fa-user-shield',
                        'site' => 'fa-globe',
                        'design' => 'fa-palette',
                        'sample' => 'fa-box',
                        'complete' => 'fa-flag-checkered'
                    ];
                    
                    foreach ($stepKeys as $num => $key):
                        $label = \App\Modules\Install\Support\Lang::get('navigation.steps.' . $key);
                        $icon = $stepIcons[$key] ?? 'fa-circle';
                        $isActive = ($step ?? 1) == $num;
                        $isCompleted = ($step ?? 1) > $num;
                        $isClickable = $isCompleted || $isActive;
                        
                        // Classes CSS
                        $liClass = 'group relative';
                        $linkClass = 'flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 ';
                        
                        if ($isCompleted) {
                            $linkClass .= 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30';
                        } elseif ($isActive) {
                            $linkClass .= 'bg-brand-600 dark:bg-brand-700 text-white shadow-lg shadow-brand-600/30';
                        } else {
                            $linkClass .= 'text-slate-400 dark:text-slate-600 cursor-not-allowed';
                        }
                    ?>
                        <li class="<?= $liClass ?>">
                            <?php if ($isClickable): ?>
                                <a href="<?= $installUrl ?>?step=<?= $num ?>" class="<?= $linkClass ?>">
                            <?php else: ?>
                                <div class="<?= $linkClass ?>">
                            <?php endif; ?>
                            
                                <!-- Icon avec statut -->
                                <div class="relative flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center transition-all
                                        <?= $isCompleted ? 'bg-emerald-500 text-white' : ($isActive ? 'bg-white text-brand-600' : 'bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-600') ?>">
                                        <?php if ($isCompleted): ?>
                                            <i class="fas fa-check text-sm"></i>
                                        <?php else: ?>
                                            <span class="text-xs font-semibold"><?= $num ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Icône du module (petit) -->
                                    <?php if (!$isCompleted && $isActive): ?>
                                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-brand-600 rounded-full flex items-center justify-center">
                                            <i class="fas <?= $icon ?> text-white install-step-icon-mini"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Label -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium truncate"><?= $label ?></span>
                                        <?php if ($isActive): ?>
                                            <span class="flex-shrink-0 w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isActive): ?>
                                        <span class="text-xs opacity-90"><?= \App\Modules\Install\Support\Lang::get('navigation.in_progress') ?></span>
                                    <?php elseif ($isCompleted): ?>
                                        <span class="text-xs opacity-75"><?= \App\Modules\Install\Support\Lang::get('navigation.completed') ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Arrow pour étape active -->
                                <?php if ($isActive): ?>
                                    <i class="fas fa-chevron-right text-xs"></i>
                                <?php endif; ?>
                                
                            <?php if ($isClickable): ?>
                                </a>
                            <?php else: ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Ligne de connexion verticale -->
                            <?php if ($num < 10): ?>
                                <div class="absolute left-8 top-14 w-0.5 h-4 
                                    <?= $isCompleted ? 'bg-emerald-300 dark:bg-emerald-700' : 'bg-slate-200 dark:bg-slate-700' ?>">
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Footer Sidebar (simplifié) -->
            <div class="p-6 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 transition-colors duration-300">
                <p class="text-xs text-slate-500 dark:text-slate-400 text-center">
                    <i class="fas fa-code mr-1"></i>
                    PHP <?= PHP_VERSION ?>
                </p>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0">
            
            <!-- Header avec Langue + Dark Mode -->
            <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 px-4 sm:px-6 lg:px-8 py-4 lg:py-6 transition-colors duration-300">
                <div class="max-w-4xl mx-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <!-- Left: Étape info -->
                    <div class="w-full sm:w-auto">
                        <?php
                        $currentStepKey = $stepKeys[$step ?? 1] ?? 'welcome';
                        $currentStepLabel = \App\Modules\Install\Support\Lang::get('navigation.steps.' . $currentStepKey);
                        ?>
                        <div class="flex items-center gap-2 sm:gap-3 text-slate-600 dark:text-slate-400 text-xs sm:text-sm mb-2">
                            <span><?= \App\Modules\Install\Support\Lang::get('navigation.step_of', ['current' => $step ?? 1, 'total' => 10]) ?></span>
                            <span class="text-slate-300 dark:text-slate-600">•</span>
                            <span><?= \App\Modules\Install\Support\Lang::get('navigation.percent_complete', ['percent' => round((($step ?? 1) / 10) * 100)]) ?></span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-white"><?= $currentStepLabel ?></h2>
                    </div>
                    
                    <!-- Right: Langue + Dark Mode -->
                    <div class="flex items-center gap-3 sm:gap-4 w-full sm:w-auto justify-end">
                        <!-- Dark Mode Toggle -->
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <span class="text-xs text-slate-600 dark:text-slate-400 install-theme-mode-icon" aria-hidden="true">
                                <i id="installThemeModeIcon" class="fas fa-sun"></i>
                            </span>
                            <div class="relative">
                                <input type="checkbox" id="darkModeToggle" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-300 dark:bg-slate-600 rounded-full peer peer-checked:bg-brand-600 transition-colors duration-300"></div>
                                <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform duration-300 peer-checked:translate-x-5 shadow-md"></div>
                            </div>
                        </label>
                        
                        <!-- Sélecteur de langue -->
                        <form method="GET" action="<?= $installUrl ?>" id="lang-form">
                            <?php if (isset($_GET['step'])): ?>
                                <input type="hidden" name="step" value="<?= htmlspecialchars($_GET['step']) ?>">
                            <?php endif; ?>
                            <select name="lang" data-action="submit-on-change"
                                    class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-sm text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-transparent transition-colors duration-300 cursor-pointer">
                                <option value="fr-FR" <?= ($currentInstallLang === 'fr-FR') ? 'selected' : '' ?>>🇫🇷 Français</option>
                                <option value="en-US" <?= (in_array($currentInstallLang, ['en-US', 'en-EN'], true)) ? 'selected' : '' ?>>🇺🇸 English</option>
                                <option value="es-ES" <?= ($currentInstallLang === 'es-ES') ? 'selected' : '' ?>>🇪🇸 Español</option>
                                <option value="de-DE" <?= ($currentInstallLang === 'de-DE') ? 'selected' : '' ?>>🇩🇪 Deutsch</option>
                                <option value="it-IT" <?= ($currentInstallLang === 'it-IT') ? 'selected' : '' ?>>🇮🇹 Italiano</option>
                                <option value="pt-PT" <?= ($currentInstallLang === 'pt-PT') ? 'selected' : '' ?>>🇵🇹 Português</option>
                            </select>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Progress Bar -->
            <div class="bg-slate-200 dark:bg-slate-700 h-1 transition-colors duration-300">
                <div class="bg-gradient-to-r from-brand-600 to-purple-600 h-full transition-all duration-500 install-progress-fill"
                     data-progress="<?= round((($step ?? 1) / 10) * 100) ?>"></div>
            </div>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900/50 transition-colors duration-300">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-700 overflow-hidden fade-in transition-colors duration-300">
                        <?php include __DIR__ . '/' . $view . '.php'; ?>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700 px-8 py-4 transition-colors duration-300">
                <div class="max-w-4xl mx-auto text-center text-slate-500 dark:text-slate-400 text-sm">
                    <p>FlatCMS V<?= $version ?> &copy; <?= date('Y') ?> - <?= \App\Modules\Install\Support\Lang::get('common.all_rights_reserved') ?></p>
                </div>
            </footer>
        </main>
    </div>

    <!-- Scripts -->
    <script src="<?= htmlspecialchars($installMainSrc, ENT_QUOTES, 'UTF-8') ?>?v=<?= htmlspecialchars($installMainVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>

</html>
