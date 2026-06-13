<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$languages = available_languages();
$uiLocale = (string) ($locale ?? locale());
$flagMap = [
    'en-US' => 'us',
    'en-GB' => 'gb',
    'fr-FR' => 'fr',
    'de-DE' => 'de',
    'es-ES' => 'es',
    'it-IT' => 'it',
    'pt-PT' => 'pt',
    'pt-BR' => 'br',
];
$flagEmojiMap = [
    'us' => '🇺🇸',
    'gb' => '🇬🇧',
    'fr' => '🇫🇷',
    'de' => '🇩🇪',
    'es' => '🇪🇸',
    'it' => '🇮🇹',
    'pt' => '🇵🇹',
    'br' => '🇧🇷',
];
$normalizeLocaleTag = static function (string $localeTag): string {
    $localeTag = trim($localeTag);
    if ($localeTag === '') {
        return '';
    }
    return str_replace('-', '_', $localeTag);
};
$resolveLocalizedLabel = static function (string $code, array $lang, string $activeLocale) use ($normalizeLocaleTag): string {
    $fallback = trim((string) ($lang['name'] ?? $code));
    $activeLocale = $normalizeLocaleTag($activeLocale);
    if ($activeLocale !== '' && str_starts_with(strtolower($activeLocale), 'fr_')) {
        return $fallback;
    }
    $codeLocale = $normalizeLocaleTag($code);
    if (!class_exists('\Locale') || $codeLocale === '' || $activeLocale === '') {
        return $fallback;
    }
    $displayLanguage = \Locale::getDisplayLanguage($codeLocale, $activeLocale);
    if (!is_string($displayLanguage)) {
        return $fallback;
    }
    $displayLanguage = trim($displayLanguage);
    if ($displayLanguage === '') {
        return $fallback;
    }
    if (function_exists('mb_convert_case')) {
        return mb_convert_case($displayLanguage, MB_CASE_TITLE, 'UTF-8');
    }
    return ucfirst($displayLanguage);
};

$indentHtml = static function (string $html, int $level): string {
    $value = trim($html);
    if ($value === '') {
        return '';
    }

    $lines = preg_split('/\r\n|\r|\n/', $value);
    if (!is_array($lines)) {
        return $value;
    }

    $prefix = str_repeat('    ', $level);
    foreach ($lines as &$line) {
        if ($line !== '') {
            $line = $prefix . $line;
        }
    }
    unset($line);

    return implode(PHP_EOL, $lines);
};

ob_start();
foreach ($languages as $code => $lang) {
    if (isset($lang['active']) && !$lang['active']) {
        continue;
    }

    $label = $resolveLocalizedLabel($code, is_array($lang) ? $lang : [], $uiLocale);
    $flagCode = $flagMap[$code] ?? strtolower((string) (explode('-', $code, 2)[1] ?? substr($code, 0, 2)));
    $flagEmoji = $flagEmojiMap[$flagCode] ?? '🏳️';
    $isActive = $locale === $code;
    ?>
<a class="lang-menu-item<?= $isActive ? ' is-active' : '' ?>" href="<?= locale_url($code) ?>">
    <span class="lang-menu-emoji"><?= e($flagEmoji) ?></span>
    <span class="lang-menu-label"><?= e($label) ?></span>
    <?php if ($isActive): ?>
    <span class="lang-menu-check"><i class="fas fa-check"></i></span>
    <?php endif; ?>
</a>
<?php
}
$langMenuItemsHtml = $indentHtml((string) ob_get_clean(), 2);
?>
<div class="lang-switch">
    <button type="button" class="lang-trigger" aria-label="<?= __('languages', 'Languages') ?>">
        <i class="fas fa-globe"></i>
    </button>
    <div class="lang-menu" id="langMenu" aria-label="<?= __('languages', 'Languages') ?>">
<?= $langMenuItemsHtml !== '' ? $langMenuItemsHtml . PHP_EOL : '' ?>
    </div>
</div>
