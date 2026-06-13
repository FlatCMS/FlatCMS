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

$source = (string) file_get_contents(FlatCmsTest::repositoryRoot() . '/public/index.php');
$start = strpos($source, 'function flatcms_env_bool');
$end = strpos($source, "/**\n * Applique un socle de sécurité HTTP", $start === false ? 0 : $start);

FlatCmsTest::assertTrue($start !== false, 'Runtime CSP helpers should expose flatcms_env_bool.');
FlatCmsTest::assertTrue($end !== false && $end > $start, 'Runtime CSP helper block should be isolated before bootstrap execution.');

eval(substr($source, (int) $start, (int) $end - (int) $start));

reset_analytics_environment();
$defaultCsp = flatcms_content_security_policy();
FlatCmsTest::assertFalse(csp_has_source($defaultCsp, 'script-src', 'https://www.googletagmanager.com'), 'Google scripts should not be allowed when analytics is disabled.');
FlatCmsTest::assertFalse(csp_has_source($defaultCsp, 'script-src', 'https://stats.example.test'), 'Matomo scripts should not be allowed when analytics is disabled.');

reset_analytics_environment();
$_ENV['GOOGLE_ANALYTICS_ENABLED'] = '1';
$_ENV['GOOGLE_ANALYTICS_MEASUREMENT_ID'] = 'G-TEST123';
$googleCsp = flatcms_content_security_policy();
FlatCmsTest::assertTrue(csp_has_source($googleCsp, 'script-src', 'https://www.googletagmanager.com'), 'Google Tag Manager script source should be allowed when GA is enabled.');
FlatCmsTest::assertTrue(csp_has_source($googleCsp, 'connect-src', 'https://www.googletagmanager.com'), 'Google Tag Manager connect source should be allowed when GA is enabled.');
FlatCmsTest::assertTrue(csp_has_source($googleCsp, 'connect-src', 'https://www.google-analytics.com'), 'Google Analytics connect source should be allowed when GA is enabled.');
FlatCmsTest::assertTrue(csp_has_source($googleCsp, 'connect-src', 'https://*.google-analytics.com'), 'Google Analytics wildcard connect source should be allowed when GA is enabled.');
FlatCmsTest::assertTrue(csp_has_source($googleCsp, 'connect-src', 'https://stats.g.doubleclick.net'), 'DoubleClick connect source should be allowed when GA is enabled.');

reset_analytics_environment();
$_ENV['GOOGLE_ANALYTICS_ENABLED'] = '1';
$missingIdCsp = flatcms_content_security_policy();
FlatCmsTest::assertFalse(csp_has_source($missingIdCsp, 'script-src', 'https://www.googletagmanager.com'), 'Google sources should require a measurement ID.');

reset_analytics_environment();
$_ENV['MATOMO_ENABLED'] = '1';
$_ENV['MATOMO_BASE_URL'] = 'https://stats.example.test/matomo/';
$matomoCsp = flatcms_content_security_policy();
FlatCmsTest::assertTrue(csp_has_source($matomoCsp, 'script-src', 'https://stats.example.test'), 'Matomo script source should use the configured origin.');
FlatCmsTest::assertTrue(csp_has_source($matomoCsp, 'connect-src', 'https://stats.example.test'), 'Matomo tracking endpoint origin should be allowed.');
FlatCmsTest::assertTrue(csp_has_source($matomoCsp, 'img-src', 'https://stats.example.test'), 'Matomo tracking pixel origin should be allowed.');
FlatCmsTest::assertFalse(csp_has_source($matomoCsp, 'script-src', 'https://stats.example.test/matomo/'), 'Matomo CSP source should not include a path.');

reset_analytics_environment();
$_ENV['MATOMO_ENABLED'] = '1';
$_ENV['MATOMO_BASE_URL'] = 'javascript:alert(1)';
$invalidMatomoCsp = flatcms_content_security_policy();
FlatCmsTest::assertFalse(csp_has_source($invalidMatomoCsp, 'script-src', 'javascript:alert(1)'), 'Invalid Matomo URL should not be injected into CSP.');

echo 'PASS: analytics CSP contracts' . PHP_EOL;

function reset_analytics_environment(): void
{
    foreach ([
        'GOOGLE_ANALYTICS_ENABLED',
        'GOOGLE_ANALYTICS_MEASUREMENT_ID',
        'MATOMO_ENABLED',
        'MATOMO_BASE_URL',
        'FLATCMS_CSP_SCRIPT_EXTRA',
        'FLATCMS_CSP_CONNECT_EXTRA',
        'FLATCMS_CSP_IMG_EXTRA',
    ] as $name) {
        unset($_ENV[$name]);
    }
}

function csp_has_source(string $policy, string $directive, string $source): bool
{
    return in_array($source, csp_sources($policy, $directive), true);
}

/**
 * @return string[]
 */
function csp_sources(string $policy, string $directive): array
{
    $directives = preg_split('/;\s*/', $policy) ?: [];
    foreach ($directives as $entry) {
        $entry = trim((string) $entry);
        if ($entry === '' || !str_starts_with($entry, $directive . ' ')) {
            continue;
        }

        $sources = preg_split('/\s+/', substr($entry, strlen($directive) + 1)) ?: [];
        return array_values(array_filter($sources, static fn (string $value): bool => $value !== ''));
    }

    return [];
}
