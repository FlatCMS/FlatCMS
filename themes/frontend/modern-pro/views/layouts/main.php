<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<!DOCTYPE html>
<html lang="<?= $locale ?>" dir="<?= text_direction() ?>">
<?php include __DIR__ . '/../partials/head.php'; ?>
<?php
$bodyClasses = ['theme-modern-pro'];
$pageSlug = '';
if (isset($page) && is_array($page)) {
    $pageSlug = strtolower(trim((string) ($page['slug'] ?? '')));
    $pageSlugClass = preg_replace('/[^a-z0-9_-]+/i', '-', $pageSlug);
    $pageSlugClass = is_string($pageSlugClass) ? trim($pageSlugClass, '-') : '';
    if ($pageSlugClass !== '') {
        $bodyClasses[] = 'page-slug-' . $pageSlugClass;
    }
    if (in_array($pageSlug, [
        'pourquoi-flatcms',
        'fonctionnalites',
        'telechargements',
        'histoire-du-projet',
        'pourquoi-sans-framework',
        'architecture-hmvc-psr-4-native',
        'pour-qui-est-flatcms',
    ], true)) {
        $bodyClasses[] = 'page-promo';
    }
}

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
$promoBannerSlot = 'above_topbar';
include __DIR__ . '/../partials/promo-banner.php';
$promoBannerAboveTopbarHtml = $indentHtml((string) ob_get_clean(), 1);

ob_start();
$promoBannerSlot = 'below_topbar';
include __DIR__ . '/../partials/promo-banner.php';
$promoBannerBelowTopbarHtml = $indentHtml((string) ob_get_clean(), 1);

ob_start();
include __DIR__ . '/../partials/header.php';
$headerHtml = $indentHtml((string) ob_get_clean(), 1);

ob_start();
include __DIR__ . '/../partials/flash.php';
$flashHtml = $indentHtml((string) ob_get_clean(), 1);

$contentHtml = $indentHtml((string) $content, 2);

ob_start();
include __DIR__ . '/../partials/footer.php';
$footerHtml = $indentHtml((string) ob_get_clean(), 1);

ob_start();
$promoBannerSlot = 'above_footer';
include __DIR__ . '/../partials/promo-banner.php';
$promoBannerAboveFooterHtml = $indentHtml((string) ob_get_clean(), 1);

ob_start();
$promoBannerSlot = 'below_footer';
include __DIR__ . '/../partials/promo-banner.php';
$promoBannerBelowFooterHtml = $indentHtml((string) ob_get_clean(), 1);

ob_start();
include __DIR__ . '/../partials/scripts.php';
$scriptsHtml = $indentHtml((string) ob_get_clean(), 1);
$frontendRuntimeLabels = [
    'copy' => __('copy_action', 'Core'),
    'copied' => __('copied_action', 'Core'),
    'sending' => __('sending', 'Core'),
];
?>

<body id="flatcms"
    class="<?= e(implode(' ', $bodyClasses)) ?>"
    data-copy-label="<?= e((string) ($frontendRuntimeLabels['copy'] ?? '')) ?>"
    data-copied-label="<?= e((string) ($frontendRuntimeLabels['copied'] ?? '')) ?>"
    data-sending-label="<?= e((string) ($frontendRuntimeLabels['sending'] ?? '')) ?>">
    <!-- Animated Background -->
    <div class="bg-gradient"></div>

    <!-- Header -->
<?= $promoBannerAboveTopbarHtml !== '' ? $promoBannerAboveTopbarHtml . PHP_EOL : '' ?>
<?= $headerHtml !== '' ? $headerHtml . PHP_EOL : '' ?>
<?= $promoBannerBelowTopbarHtml !== '' ? $promoBannerBelowTopbarHtml . PHP_EOL : '' ?>
<?= $flashHtml !== '' ? $flashHtml . PHP_EOL : '' ?>

    <!-- Main -->
    <main class="site-main">
<?= $contentHtml !== '' ? $contentHtml . PHP_EOL : '' ?>
    </main>

    <!-- Footer -->
<?= $promoBannerAboveFooterHtml !== '' ? $promoBannerAboveFooterHtml . PHP_EOL : '' ?>
<?= $footerHtml !== '' ? $footerHtml . PHP_EOL : '' ?>
<?= $promoBannerBelowFooterHtml !== '' ? $promoBannerBelowFooterHtml . PHP_EOL : '' ?>
<?= $scriptsHtml !== '' ? $scriptsHtml . PHP_EOL : '' ?>
</body>
</html>
