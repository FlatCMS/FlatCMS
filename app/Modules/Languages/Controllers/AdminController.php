<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Languages\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Core\FlatFile;
use App\Core\TranslationScanner;

class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        I18n::load('Languages');
    }

    public function index(): void
    {
        if (!$this->authorize('languages.view')) {
            return;
        }

        $languages = $this->getLanguages();
        $languages = $this->localizeLanguageNamesForUi($languages, I18n::getLocale());
        $languageNamePlaceholder = $this->getLocalizedLanguageName('fr-FR', I18n::getLocale());
        if ($languageNamePlaceholder === '') {
            $languageNamePlaceholder = (string) ($languages['fr-FR']['name'] ?? 'fr-FR');
        }
        $settings = FlatFile::settings();
        $defaultLang = $settings['default_language'] ?? 'fr-FR';

        // Compute completion stats per language based on code usage
        $completionStats = [];
        $usage = TranslationScanner::scanCodeUsage();
        foreach ($languages as $code => $lang) {
            $totalUsed = 0;
            $totalDefined = 0;

            foreach ($usage as $moduleKey => $keys) {
                $trans = $this->loadTranslations($code, $moduleKey);
                $flatTrans = $this->flattenArray($trans);
                foreach ($keys as $key) {
                    $totalUsed++;
                    $value = $flatTrans[$key] ?? '';
                    if (trim((string) $value) !== '') {
                        $totalDefined++;
                    }
                }
            }

            $missing = max(0, $totalUsed - $totalDefined);
            $percentage = $totalUsed > 0 ? (int) floor(($totalDefined / $totalUsed) * 100) : 100;
            if ($missing > 0 && $percentage >= 100) {
                $percentage = 99;
            }

            $completionStats[$code] = [
                'total' => $totalUsed,
                'translated' => $totalDefined,
                'missing' => $missing,
                'percentage' => (int) $percentage,
            ];
        }

        $this->render('Languages/Views/admin/index', [
            'pageTitle' => __('languages', 'Languages'),
            'languages' => $languages,
            'defaultLang' => $defaultLang,
            'completionStats' => $completionStats,
            'languageNamePlaceholder' => $languageNamePlaceholder,
        ], 'admin.main');
    }

    public function create(): void
    {
        if (!$this->authorize('languages.create')) {
            return;
        }

        $this->render('Languages/Views/admin/form', [
            'pageTitle' => __('add_language', 'Languages'),
            'language' => null,
            'availableLanguages' => $this->getAvailableLanguages(),
        ], 'admin.main');
    }

    public function store(): void
    {
        if (!$this->authorize('languages.create')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $code = $this->request->input('code');
        $name = $this->request->input('name');

        if (empty($code) || empty($name)) {
            $this->session->flash('error', __('code_name_required', 'Languages'));
            $this->redirect(url('/admin/languages/create'));
            return;
        }

        // Handle direction for new language
        $direction = $this->request->input('direction', 'ltr');

        // Create language directory and base files
        $this->createLanguageFiles($code, $name);

        // Update direction if not ltr
        if ($direction !== 'ltr') {
            $configPath = BASE_PATH . "/data/languages/{$code}.json";
            $config = json_read($configPath) ?? [];
            $config['direction'] = $direction;
            json_write($configPath, $config);
        }

        $this->session->flash('success', __('language_created', 'Languages'));
        $this->redirect(url('/admin/languages'));
    }

    public function edit(string $code): void
    {
        if (!$this->authorize('languages.edit')) {
            return;
        }

        $languages = $this->getLanguages();
        $language = $languages[$code] ?? null;

        if (!$language) {
            $this->session->flash('error', __('language_not_found', 'Languages'));
            $this->redirect(url('/admin/languages'));
            return;
        }

        $this->render('Languages/Views/admin/form', [
            'pageTitle' => __('edit_language', 'Languages'),
            'language' => array_merge(['code' => $code], $language),
            'availableLanguages' => [],
        ], 'admin.main');
    }

    public function update(string $code): void
    {
        if (!$this->authorize('languages.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $name = $this->request->input('name');
        $active = $this->request->input('active') ? true : false;

        // Update language config
        $configPath = BASE_PATH . "/data/languages/{$code}.json";
        $config = json_read($configPath) ?? [];
        $config['name'] = $name;
        $config['active'] = $active;
        $direction = $this->request->input('direction', 'ltr');
        $native = $this->request->input('native', $name);
        $config['direction'] = $direction;
        $config['native'] = $native;
        json_write($configPath, $config);

        $this->session->flash('success', __('language_updated', 'Languages'));
        $this->redirect(url('/admin/languages'));
    }

    public function delete(string $code): void
    {
        if (!$this->authorize('languages.delete')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $settings = FlatFile::settings();
        if (($settings['default_language'] ?? 'fr-FR') === $code) {
            $this->session->flash('error', __('cannot_delete_default', 'Languages'));
            $this->redirect(url('/admin/languages'));
            return;
        }

        // Delete language config
        $configPath = BASE_PATH . "/data/languages/{$code}.json";
        if (file_exists($configPath)) {
            unlink($configPath);
        }

        // Delete Core translations
        $corePath = BASE_PATH . "/app/Modules/Core/Languages/{$code}.json";
        if (file_exists($corePath)) {
            unlink($corePath);
        }

        // Delete module translations
        $paths = [
            BASE_PATH . '/app/Modules',
            BASE_PATH . '/app/Extensions',
        ];
        foreach ($paths as $modulesPath) {
            foreach (glob($modulesPath . '/*/Languages/' . $code . '.json') as $file) {
                unlink($file);
            }
        }

        $this->session->flash('success', __('language_deleted', 'Languages'));
        $this->redirect(url('/admin/languages'));
    }

    public function setDefault(string $code): void
    {
        if (!$this->authorize('languages.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $settings = FlatFile::settings();
        $settings['default_language'] = $code;
        FlatFile::saveSettings($settings);

        $this->session->flash('success', __('default_language_set', 'Languages'));
        $this->redirect(url('/admin/languages'));
    }

    public function translations(string $code): void
    {
        if (!$this->authorize('languages.translations')) {
            return;
        }

        $modules = $this->getModulesWithTranslations();
        $usage = TranslationScanner::scanCodeUsage();

        // Reference language for editor UI / copy from reference
        $settings = FlatFile::settings();
        $defaultLang = $settings['default_language'] ?? 'fr-FR';
        if ($code === $defaultLang) {
            $fallback = config('app.fallback_locale', 'en-US');
            $referenceLang = ($fallback !== $code) ? $fallback : 'en-US';
        } else {
            $referenceLang = $defaultLang;
        }

        // Compute stats for ALL modules
        $modulesStats = [];
        $globalTotal = 0;
        $globalTranslated = 0;

        foreach ($modules as $moduleKey => $moduleLabel) {
            $keys = $this->getTranslationKeysForTarget((string) $moduleKey, $usage, $referenceLang);
            $trans = $this->loadTranslations($code, $moduleKey);
            $flatTrans = $this->flattenArray($trans);
            $missing = 0;
            foreach ($keys as $key) {
                $value = $flatTrans[$key] ?? '';
                if (trim((string) $value) === '') {
                    $missing++;
                }
            }

            $total = count($keys);
            $translated = $total - $missing;
            $percentage = $total > 0 ? (int) floor(($translated / $total) * 100) : 100;
            if ($missing > 0 && $percentage >= 100) {
                $percentage = 99;
            }

            $modulesStats[$moduleKey] = [
                'label' => $moduleLabel,
                'total' => $total,
                'translated' => $translated,
                'missing' => $missing,
                'percentage' => $percentage,
            ];

            $globalTotal += $total;
            $globalTranslated += $translated;
        }

        $globalPercentage = $globalTotal > 0 ? (int) floor(($globalTranslated / $globalTotal) * 100) : 100;
        $globalMissing = $globalTotal - $globalTranslated;
        if ($globalMissing > 0 && $globalPercentage >= 100) {
            $globalPercentage = 99;
        }

        $this->render('Languages/Views/admin/translations', [
            'pageTitle' => __('edit_translations', 'Languages'),
            'code' => $code,
            'modules' => $modules,
            'modulesStats' => $modulesStats,
            'referenceLang' => $referenceLang,
            'globalTotal' => $globalTotal,
            'globalTranslated' => $globalTranslated,
            'globalMissing' => $globalTotal - $globalTranslated,
            'globalPercentage' => $globalPercentage,
        ], 'admin.main');
    }

    public function moduleTranslations(string $code): void
    {
        if (!$this->authorize('languages.translations')) {
            return;
        }

        $module = $this->request->input('module', 'Core');

        // Determine reference language
        $settings = FlatFile::settings();
        $defaultLang = $settings['default_language'] ?? 'fr-FR';

        if ($code === $defaultLang) {
            $fallback = config('app.fallback_locale', 'en-US');
            $referenceLang = ($fallback !== $code) ? $fallback : 'en-US';
        } else {
            $referenceLang = $defaultLang;
        }

        $translations = $this->loadTranslations($code, $module);
        $reference = $this->loadTranslations($referenceLang, $module);

        $flatTranslations = $this->flattenArray($translations);
        $flatReference = $this->flattenArray($reference);

        $usage = TranslationScanner::scanCodeUsage();
        $usageKeys = $this->getTranslationKeysForTarget($module, $usage, $referenceLang);
        $usedSet = array_flip($usageKeys);

        $allKeys = array_unique(array_merge(array_keys($flatReference), array_keys($flatTranslations), $usageKeys));
        $allKeys = array_map(static fn($key): string => (string) $key, $allKeys);
        sort($allKeys);

        // Group keys: nested keys (containing dot) grouped by prefix, flat keys under "general"
        $groups = [];
        foreach ($allKeys as $key) {
            if (str_contains((string) $key, '.')) {
                $group = explode('.', $key)[0];
            } else {
                $group = '_general';
            }

            $refValue = $flatReference[$key] ?? '';
            $transValue = $flatTranslations[$key] ?? '';
            $isMissing = (trim($transValue) === '' && isset($usedSet[$key]));

            $groups[$group][] = [
                'key' => $key,
                'reference' => $refValue,
                'translation' => $transValue,
                'missing' => $isMissing,
            ];
        }

        // Put _general first
        if (isset($groups['_general'])) {
            $general = $groups['_general'];
            unset($groups['_general']);
            $groups = array_merge(['_general' => $general], $groups);
        }

        $this->json([
            'success' => true,
            'module' => $module,
            'referenceLang' => $referenceLang,
            'groups' => $groups,
        ]);
    }

    public function saveTranslations(string $code): void
    {
        if (!$this->authorize('languages.translations')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        $module = $this->request->input('module', 'Core');
        $translations = $this->request->input('translations', []);

        // Unflatten dot-notation keys back to nested arrays
        $nested = $this->unflattenArray($translations);

        // Save to module Languages directory (Core included)
        $path = $this->resolveTranslationPath($module, $code);

        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        hook_run('languages.before_save', [
            'locale' => $code,
            'module' => $module,
            'translations' => $nested,
        ]);
        json_write($path, $nested);
        hook_run('languages.after_save', [
            'locale' => $code,
            'module' => $module,
            'translations' => $nested,
        ]);

        if ($this->request->isAjax()) {
            json_success(__('translations_saved', 'Languages'));
            return;
        }

        $this->session->flash('success', __('translations_saved', 'Languages'));
        $this->redirect(url("/admin/languages/{$code}/translations?module={$module}"));
    }

    public function scan(string $code): void
    {
        if (!$this->authorize('languages.translations')) {
            return;
        }

        hook_run('languages.before_scan', $code);

        $modules = $this->getModulesWithTranslations();
        $usage = TranslationScanner::scanCodeUsage();
        $missing = [];
        $total = 0;
        $translated = 0;

        foreach ($modules as $moduleKey => $moduleLabel) {
            $translations = $this->flattenArray($this->loadTranslations($code, $moduleKey));

            // Load default language as reference
            $settings = FlatFile::settings();
            $defaultLang = $settings['default_language'] ?? 'fr-FR';
            $refLang = ($code === $defaultLang) ? 'en-US' : $defaultLang;
            $reference = $this->flattenArray($this->loadTranslations($refLang, $moduleKey));
            $keys = $this->getTranslationKeysForTarget((string) $moduleKey, $usage, $refLang);

            foreach ($keys as $key) {
                $total++;
                if (trim((string) ($translations[$key] ?? '')) === '') {
                    $missing[] = [
                        'module' => $moduleKey,
                        'key' => $key,
                        'reference' => (string) ($reference[$key] ?? ''),
                    ];
                } else {
                    $translated++;
                }
            }
        }

        $percentage = $total > 0 ? (int) floor(($translated / $total) * 100) : 100;
        if (count($missing) > 0 && $percentage >= 100) {
            $percentage = 99;
        }

        $summary = [
            'success' => true,
            'total' => $total,
            'translated' => $translated,
            'missing' => count($missing),
            'percentage' => $percentage,
            'details' => $missing,
        ];

        hook_run('languages.after_scan', $summary);
        $this->json($summary);
    }

    public function export(string $code): void
    {
        if (!$this->authorize('languages.translations')) {
            return;
        }

        $modules = $this->getModulesWithTranslations();
        $allTranslations = [];

        foreach ($modules as $moduleKey => $moduleLabel) {
            $translations = $this->loadTranslations($code, $moduleKey);
            if (!empty($translations)) {
                $allTranslations[$moduleKey] = $translations;
            }
        }

        $json = json_encode($allTranslations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="lang_' . $code . '.json"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }

    public function import(): void
    {
        if (!$this->authorize('languages.translations')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        if (empty($_FILES['language_file']['tmp_name'])) {
            $this->session->flash('error', __('import_no_file', 'Languages'));
            $this->redirect(url('/admin/languages'));
            return;
        }

        $file = $_FILES['language_file'];

        if ($file['type'] !== 'application/json' && !str_ends_with($file['name'], '.json')) {
            $this->session->flash('error', __('import_invalid_format', 'Languages'));
            $this->redirect(url('/admin/languages'));
            return;
        }

        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $this->session->flash('error', __('import_invalid_json', 'Languages'));
            $this->redirect(url('/admin/languages'));
            return;
        }

        $code = $this->request->input('import_code', '');
        if (empty($code)) {
            // Try to extract from filename (lang_fr.json -> fr)
            if (preg_match('/lang_([a-z]{2}(?:-[A-Z]{2})?)\.json/', $file['name'], $matches)) {
                $code = $matches[1];
            } else {
                $this->session->flash('error', __('import_no_code', 'Languages'));
                $this->redirect(url('/admin/languages'));
                return;
            }
        }

        // Ensure language config exists
        $configPath = BASE_PATH . "/data/languages/{$code}.json";
        if (!file_exists($configPath)) {
            $languages = $this->getLanguages();
            $name = $data['_meta']['name'] ?? strtoupper($code);
            $this->createLanguageFiles($code, $name);
        }

        // Import translations per module
        $imported = 0;
        foreach ($data as $moduleKey => $translations) {
            if ($moduleKey === '_meta' || !is_array($translations)) continue;

            $path = $this->resolveTranslationPath($moduleKey, $code);
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Merge with existing translations
            $existing = $this->loadTranslations($code, $moduleKey);
            $merged = array_merge($existing, $translations);
            json_write($path, $merged);
            $imported += count($translations);
        }

        $this->session->flash('success', __('import_success', 'Languages', ['count' => $imported]));
        $this->redirect(url('/admin/languages'));
    }

    private function getLanguages(): array
    {
        $languages = [];
        $langPath = BASE_PATH . '/data/languages';

        if (is_dir($langPath)) {
            foreach (glob($langPath . '/*.json') as $file) {
                $code = basename($file, '.json');
                $config = json_read($file);

                if ($config) {
                    $languages[$code] = $config;
                } else {
                    $languages[$code] = [
                        'name' => strtoupper($code),
                        'native' => strtoupper($code),
                        'active' => true,
                    ];
                }
            }
        }

        return $languages;
    }

    private function localizeLanguageNamesForUi(array $languages, string $uiLocale): array
    {
        foreach ($languages as $code => $languageData) {
            if (!is_array($languageData)) {
                continue;
            }

            $localizedName = $this->getLocalizedLanguageName($code, $uiLocale);
            if ($localizedName === '') {
                continue;
            }

            $languages[$code]['name'] = $localizedName;
        }

        return $languages;
    }

    private function getLocalizedLanguageName(string $languageCode, string $uiLocale): string
    {
        if (!class_exists('\\Locale')) {
            return '';
        }

        $normalizedLanguageCode = $this->normalizeLocaleTag($languageCode);
        $normalizedUiLocale = $this->normalizeLocaleTag($uiLocale);

        if ($normalizedLanguageCode === '' || $normalizedUiLocale === '') {
            return '';
        }

        $displayLanguage = \Locale::getDisplayLanguage($normalizedLanguageCode, $normalizedUiLocale);
        if (!is_string($displayLanguage)) {
            return '';
        }

        $displayLanguage = trim($displayLanguage);
        if ($displayLanguage === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($displayLanguage, MB_CASE_TITLE, 'UTF-8');
        }

        return ucfirst($displayLanguage);
    }

    private function normalizeLocaleTag(string $locale): string
    {
        $locale = trim($locale);
        if ($locale === '') {
            return '';
        }

        return str_replace('-', '_', $locale);
    }

    private function getAvailableLanguages(): array
    {
        return [
            'en-US' => 'English (US)',
            'en-GB' => 'English (UK)',
            'fr-FR' => 'Français (France)',
            'fr-CA' => 'Français (Canada)',
            'de-DE' => 'Deutsch (Deutschland)',
            'es-ES' => 'Español (España)',
            'es-MX' => 'Español (México)',
            'it-IT' => 'Italiano (Italia)',
            'pt-PT' => 'Português (Portugal)',
            'pt-BR' => 'Português (Brasil)',
            'nl-NL' => 'Nederlands (Nederland)',
            'pl-PL' => 'Polski (Polska)',
            'ru-RU' => 'Русский (Россия)',
            'zh-CN' => '中文 (简体)',
            'zh-TW' => '中文 (繁體)',
            'ja-JP' => '日本語 (日本)',
            'ar-SA' => 'العربية (السعودية)',
        ];
    }

    private function getModulesWithTranslations(): array
    {
        $modules = ['Core' => 'Core (Global)'];
        $manager = new \App\Core\ModuleManager([
            BASE_PATH . '/app/Modules',
            BASE_PATH . '/app/Extensions',
        ], BASE_PATH . '/data/modules.json');
        foreach ($manager->enabledNames() as $moduleName) {
            if ($moduleName === 'Core') {
                continue;
            }
            $meta = $manager->get($moduleName);
            $dir = $meta['path'] ?? (BASE_PATH . '/app/Modules/' . $moduleName);
            if (is_dir($dir . '/Languages')) {
                $modules[$moduleName] = $moduleName;
            }
        }

        return $modules;
    }

    private function loadTranslations(string $code, string $module): array
    {
        $path = I18n::resolveTranslationPathForNamespace($module, $code);
        return json_read($path) ?? [];
    }

    private function resolveTranslationPath(string $module, string $code): string
    {
        return I18n::resolveTranslationPathForNamespace($module, $code);
    }

    /**
     * @param array<string, array<int, string>> $usage
     * @return array<int, string>
     */
    private function getTranslationKeysForTarget(string $moduleKey, array $usage, string $referenceLang): array
    {
        $keys = $usage[$moduleKey] ?? [];
        return is_array($keys) ? array_values($keys) : [];
    }

    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    private function unflattenArray(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (str_contains($key, '.')) {
                $parts = explode('.', $key);
                $current = &$result;
                foreach ($parts as $i => $part) {
                    if ($i === count($parts) - 1) {
                        $current[$part] = $value;
                    } else {
                        if (!isset($current[$part]) || !is_array($current[$part])) {
                            $current[$part] = [];
                        }
                        $current = &$current[$part];
                    }
                }
                unset($current);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function scanAndFill(string $code): void
    {
        if (!$this->authorize('languages.translations')) {
            return;
        }

        if (!$this->verifyCsrf()) return;

        hook_run('languages.before_scan', $code);

        $module = $this->request->input('module');
        $usage = TranslationScanner::scanCodeUsage();
        $settings = FlatFile::settings();
        $defaultLang = $settings['default_language'] ?? 'fr-FR';
        $fallback = config('app.fallback_locale', 'en-US');
        $referenceLang = ($code === $defaultLang)
            ? (($fallback !== $code) ? $fallback : 'en-US')
            : $defaultLang;

        $totalAdded = 0;
        $details = [];

        $modulesToScan = $module ? [$module => $module] : $this->getModulesWithTranslations();

        foreach ($modulesToScan as $mod => $label) {
            $keys = $this->getTranslationKeysForTarget((string) $mod, $usage, $referenceLang);
            if (empty($keys)) {
                continue;
            }

            $path = $this->resolveTranslationPath($mod, $code);
            $existing = json_read($path) ?? [];
            $flat = $this->flattenArray($existing);

            $added = [];
            foreach ($keys as $key) {
                if (!array_key_exists($key, $flat)) {
                    $flat[$key] = '';
                    $added[] = $key;
                }
            }

            if (!empty($added)) {
                $nested = $this->unflattenArray($flat);
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                json_write($path, $nested);
                $totalAdded += count($added);
                $details[$mod] = $added;
            }
        }

        $summary = [
            'success' => true,
            'total_added' => $totalAdded,
            'details' => $details,
        ];

        hook_run('languages.after_scan', $summary);
        $this->json($summary);
    }

    private function createLanguageFiles(string $code, string $name): void
    {
        // Create language config in /data/languages/
        $configPath = BASE_PATH . '/data/languages';
        if (!is_dir($configPath)) {
            mkdir($configPath, 0755, true);
        }

        $config = [
            'name' => $name,
            'native' => $name,
            'direction' => 'ltr',
            'active' => true,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        json_write($configPath . "/{$code}.json", $config);

        // Create Core translations in /app/Modules/Core/Languages/
        $corePath = BASE_PATH . '/app/Modules/Core/Languages';
        if (!is_dir($corePath)) {
            mkdir($corePath, 0755, true);
        }

        $core = [
            'app_name' => 'FlatCMS',
            'welcome' => 'Welcome',
            'home' => 'Home',
        ];
        json_write($corePath . "/{$code}.json", $core);
    }
}
