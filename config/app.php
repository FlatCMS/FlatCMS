<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

return [
    // Application
    'name' => env('APP_NAME', \App\Core\CoreManifest::name('FlatCMS')),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Europe/Paris'),

    // Localization
    'locale' => 'fr-FR',
    'fallback_locale' => 'en-US',
    'locales' => ['fr-FR', 'en-US'],

    // Themes
    'admin_theme' => 'admin-modern-pro',
    'frontend_theme' => 'default',

    // Security
    'session_lifetime' => env('SESSION_LIFETIME', 120),

    // Cache
    'cache_enabled' => env('CACHE_ENABLED', true),
    'cache_ttl' => env('CACHE_TTL', 3600),

    // Uploads
    'upload_max_size' => 10 * 1024 * 1024, // 10MB
    'upload_allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'],

    // Extensions
    'extensions' => [
        'official_public_key' => env('EXTENSIONS_PUBLIC_KEY', <<<'KEY'
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAtOMWlP3vPyJC/AAT4Bav
aPlbaJ6vWNX/WmrL/DvL/gUzxSwockLsSggc/cn1V/1Sceb3wu5KpKcd8O9lwGIa
2t0PujmuEkHmyL2vIkboGmu7huDzRkc0wupBMg5NhNq4JAnrHt1vJvC5jRWqpVDP
G2v2i4lTDt0/hWKhfBwQ8/xVxqmWT5jrFkgPwTiJwFDYvw8LlAYBnBaLj878XzCW
YbCBVjFkUmm7UuO9APCiQoEOKurSP2HH31+S8muzFs3JUjo7QJtTI4QiCWNyPftk
3WS2qI0vlmaqfCQNwN2QEIOAjsB4xPBP/0vYgvlMrf6ak2IfMQjkhYBpk7XL6kBh
5HXYEGJp6LEa9xrszE55RQwGA/g0SlR/srhS3ycXdcdE2C6uuTsU3e5K4MVwN8ye
fr41TLTanM3Fdy7kDgnIpLLQS9b5ewSWPKuvDRBiM5+5bxvpJOb3Xb3rYipg5SUW
cvF5hPSeOHgTNCTcIr9Dfm04ByTzp58nXIWFAZxlw8WTq0uWRFbN6YMfKzHvp/g2
8E5f/JarpoBzvOd3lepwRp3XUMuoKcXf2uaSoo0C+F8WGTh+wNHFbsPQ+DCE4ze4
bma2ZFATlQC80uOlcD8zI+KXPdlK8rkqbRnpyBzrhKo9aC6UECmhi1G1u8gplDG3
1NHlItaqmPr9tZxTolae06ECAwEAAQ==
-----END PUBLIC KEY-----
KEY),
        'signature_algo' => env('EXTENSIONS_SIGNATURE_ALGO', 'sha256'),
    ],

    // Pagination
    'per_page' => 15,
];
