<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

require_once __DIR__ . '/../support/TestHarness.php';

$repo = FlatCmsTest::repositoryRoot();

$controllerSource = read_source($repo . '/app/Modules/Themes/Controllers/AdminController.php');
$indexViewSource = read_source($repo . '/app/Modules/Themes/Views/admin/index.php');
$viewSource = read_source($repo . '/app/Modules/Themes/Views/admin/customize.php');
$scriptSource = read_source($repo . '/app/Modules/Themes/Assets/js/themes.js');
$styleSource = read_source($repo . '/app/Modules/Themes/Assets/css/themes-module.css');
$serviceSource = read_source($repo . '/app/Modules/Themes/Services/ThemeCustomizationService.php');

assert_contains($indexViewSource, 'themePriceFilter', 'Themes index view must keep the price filter control.');

assert_contains($viewSource, 'name="custom_css"', 'Themes customize view must keep the custom CSS field.');
assert_contains($viewSource, 'name="button_style"', 'Themes customize view must expose button style customization.');
assert_contains($viewSource, 'name="button_shape"', 'Themes customize view must expose button shape customization.');
assert_contains($viewSource, 'name="button_weight"', 'Themes customize view must expose button weight customization.');
assert_contains($viewSource, 'name="badge_style"', 'Themes customize view must expose badge style customization.');
assert_contains($viewSource, 'name="badge_shape"', 'Themes customize view must expose badge shape customization.');
assert_contains($viewSource, 'name="badge_weight"', 'Themes customize view must expose badge weight customization.');
assert_contains($viewSource, 'name="typography_body_family"', 'Themes customize view must expose typography body family customization.');
assert_contains($viewSource, 'name="typography_heading_family"', 'Themes customize view must expose typography heading family customization.');
assert_contains($viewSource, 'name="typography_scale"', 'Themes customize view must expose typography scale customization.');
assert_contains($viewSource, 'name="typography_heading_weight"', 'Themes customize view must expose typography heading weight customization.');
assert_contains($viewSource, 'data-theme-preview-box', 'Themes customize preview must expose the data preview selector used by JS.');

assert_contains($controllerSource, "'buttonCustomization'", 'Themes controller must pass saved button customization to the view.');
assert_contains($controllerSource, "'badgeCustomization'", 'Themes controller must pass saved badge customization to the view.');
assert_contains($controllerSource, "'typographyCustomization'", 'Themes controller must pass saved typography customization to the view.');
assert_contains($controllerSource, "'buttons' => [", 'Themes controller must persist button customization in JSON.');
assert_contains($controllerSource, "'badges' => [", 'Themes controller must persist badge customization in JSON.');
assert_contains($controllerSource, "'typography' => [", 'Themes controller must persist typography customization in JSON.');
assert_contains($controllerSource, "inputChoice('button_style'", 'Themes controller must validate button style values before saving.');
assert_contains($controllerSource, "inputChoice('badge_style'", 'Themes controller must validate badge style values before saving.');
assert_contains($controllerSource, "inputChoice('typography_body_family'", 'Themes controller must validate typography values before saving.');
assert_contains($controllerSource, '$themeCustomCss', 'Themes controller must prefill first-run custom CSS from theme.json.');
assert_contains($controllerSource, '!$customizationExists', 'Themes controller must only prefill generated custom CSS before a customization file exists.');
assert_contains($controllerSource, '$official !== $hasOfficialOrigin', 'Themes installer must reject inconsistent official/origin metadata.');
assert_contains($controllerSource, 'if ($official === true)', 'Themes installer must require signatures only for official themes.');

assert_contains($scriptSource, "querySelector('[data-theme-preview-box], #preview-box')", 'Themes JS must target the current customize preview selector.');
assert_contains($scriptSource, 'data-theme-button-control', 'Themes JS must listen to button customization controls.');
assert_contains($scriptSource, 'data-theme-badge-control', 'Themes JS must listen to badge customization controls.');
assert_contains($scriptSource, 'data-theme-typography-control', 'Themes JS must listen to typography customization controls.');
assert_contains($scriptSource, 'applyComponentCustomization', 'Themes JS must apply component customization to the preview.');

assert_contains($styleSource, '--fc-btn-radius', 'Themes CSS must apply modern-pro preview button radius variables.');
assert_contains($styleSource, '--fc-btn-primary-bg', 'Themes CSS must apply modern-pro preview primary button variables.');

assert_contains($serviceSource, 'buildComponentCustomizationCss', 'ThemeCustomizationService must render component customization CSS.');
assert_contains($serviceSource, "'buttons'", 'ThemeCustomizationService must read saved button customization.');
assert_contains($serviceSource, "'badges'", 'ThemeCustomizationService must read saved badge customization.');
assert_contains($serviceSource, "'typography'", 'ThemeCustomizationService must read saved typography customization.');

preg_match_all("/__\\('([^']+)',\\s*'Themes'\\)/", $viewSource . "\n" . $indexViewSource, $translationMatches);
$themeViewKeys = array_values(array_unique($translationMatches[1] ?? []));
sort($themeViewKeys);
foreach (glob($repo . '/app/Modules/Themes/Languages/*.json') ?: [] as $languageFile) {
    $translations = FlatCmsTest::assertJsonFile($languageFile, 'Themes language file must be valid JSON.');
    foreach ($themeViewKeys as $key) {
        FlatCmsTest::assertTrue(
            array_key_exists($key, $translations),
            basename($languageFile) . ' must define Themes view key: ' . $key
        );
    }
}

$tmpRoot = FlatCmsTest::tempDir('flatcms_theme_customization');

try {
    mkdir($tmpRoot . '/themes/frontend/modern-pro', 0755, true);
    mkdir($tmpRoot . '/data/themes', 0755, true);

    file_put_contents(
        $tmpRoot . '/themes/frontend/modern-pro/theme.json',
        json_encode([
            'colors' => [
                'primary' => '#2563EB',
                'secondary' => '#0F766E',
                'accent' => '#F97316',
                'background' => '#111827',
                'surface' => '#1F2937',
                'text' => '#F9FAFB',
                'text-muted' => '#CBD5E1',
                'border' => '#374151',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    file_put_contents(
        $tmpRoot . '/data/themes/frontend_modern-pro.json',
        json_encode([
            'colors' => [
                'primary' => '#123456',
                'background' => '#101827',
                'surface' => '#1B2535',
                'text' => '#F8FAFC',
            ],
            'light_colors' => [
                'background' => '#FFFFFF',
                'surface' => '#F8FAFC',
                'text' => '#111827',
            ],
            'custom_css' => '.btn-custom { border-radius: 2rem; }',
            'buttons' => [
                'style' => 'elevated',
                'shape' => 'pill',
                'weight' => 'bold',
            ],
            'badges' => [
                'style' => 'outline',
                'shape' => 'rounded',
                'weight' => 'semibold',
            ],
            'typography' => [
                'body_family' => 'system',
                'heading_family' => 'editorial',
                'scale' => 'comfortable',
                'heading_weight' => 'black',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    if (!defined('BASE_PATH')) {
        define('BASE_PATH', $tmpRoot);
    }

    require_once $repo . '/app/Modules/Themes/Services/ThemeCustomizationService.php';

    $service = new App\Modules\Themes\Services\ThemeCustomizationService();
    $css = $service->buildRuntimeCss('frontend', 'modern-pro');

    assert_contains($css, ':root {', 'Runtime customization CSS must contain a root block.');
    assert_contains($css, 'body.light-mode {', 'Modern frontend theme must include light mode customization CSS.');
    assert_contains($css, '--color-primary: #123456;', 'Runtime customization CSS must apply saved color values.');
    assert_contains($css, '.btn-custom { border-radius: 2rem; }', 'Runtime customization CSS must preserve custom CSS.');
    assert_contains($css, '--fc-btn-radius: 999px;', 'Runtime customization CSS must apply saved button shape.');
    assert_contains($css, '--fc-btn-font-weight: 700;', 'Runtime customization CSS must apply saved button weight.');
    assert_contains($css, '--theme-button-style: elevated;', 'Runtime customization CSS must apply saved button style.');
    assert_contains($css, '--theme-badge-radius: 0.75rem;', 'Runtime customization CSS must apply saved badge shape.');
    assert_contains($css, '--theme-badge-font-weight: 600;', 'Runtime customization CSS must apply saved badge weight.');
    assert_contains($css, '--theme-badge-style: outline;', 'Runtime customization CSS must apply saved badge style.');
    assert_contains($css, '--theme-body-font-family:', 'Runtime customization CSS must apply saved body typography.');
    assert_contains($css, '--theme-heading-font-family:', 'Runtime customization CSS must apply saved heading typography.');
    assert_contains($css, '--theme-typography-scale: comfortable;', 'Runtime customization CSS must apply saved typography scale.');
    assert_contains($css, '--theme-heading-font-weight: 900;', 'Runtime customization CSS must apply saved heading weight.');
    assert_contains($css, 'font-family: var(--theme-heading-font-family);', 'Runtime customization CSS must apply heading family selector.');
    assert_contains($css, 'border-radius: var(--theme-badge-radius);', 'Runtime customization CSS must apply badge radius selector.');

    FlatCmsTest::assertSame('', $service->buildRuntimeCss('frontend', '../'), 'Empty sanitized theme name must not produce CSS.');
    FlatCmsTest::assertSame('', $service->buildRuntimeCss('invalid', 'modern-pro'), 'Invalid theme type must not produce CSS.');
} finally {
    FlatCmsTest::removeTree($tmpRoot);
}

echo 'PASS: themes customization contracts' . PHP_EOL;

function read_source(string $path): string
{
    FlatCmsTest::assertFileExists($path, 'Required source file should exist.');

    return (string) file_get_contents($path);
}

function assert_contains(string $haystack, string $needle, string $message): void
{
    FlatCmsTest::assertTrue(str_contains($haystack, $needle), $message . ' Missing: ' . $needle);
}
