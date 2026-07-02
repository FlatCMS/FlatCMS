<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

$pageMeta = is_array($page['page'] ?? null) ? $page['page'] : [];
$design = is_array($page['design']['global'] ?? null) ? $page['design']['global'] : [];
$navbar = is_array($page['navbar'] ?? null) ? $page['navbar'] : ['settings' => ['mega_columns_desktop' => '5'], 'brand' => ['label' => ''], 'rows' => [], 'items' => []];
$navbarRows = is_array($navbar['rows'] ?? null) ? $navbar['rows'] : [];
$navbarSettings = is_array($navbar['settings'] ?? null) ? $navbar['settings'] : ['mega_columns_desktop' => '5'];
$megaColumnsDesktop = max(1, min(6, (int) ($navbarSettings['mega_columns_desktop'] ?? 5)));
$layout = is_array($page['layout'] ?? null) ? $page['layout'] : [];
$headerBeforeBlocks = is_array($layout['header_before']['blocks'] ?? null) ? $layout['header_before']['blocks'] : [];
$headerAfterBlocks = is_array($layout['header_after']['blocks'] ?? null) ? $layout['header_after']['blocks'] : [];
$asideBlocks = is_array($layout['aside']['blocks'] ?? null) ? $layout['aside']['blocks'] : [];
$footerBlocks = is_array($layout['footer']['blocks'] ?? null) ? $layout['footer']['blocks'] : [];
$sections = is_array($page['sections'] ?? null) ? $page['sections'] : [];
$previewItem = is_int($previewNavIndex) && isset($navbar['items'][$previewNavIndex]) && is_array($navbar['items'][$previewNavIndex])
    ? $navbar['items'][$previewNavIndex]
    : null;
$previewColumns = [];
if (is_array($previewItem['mega_menu']['columns'] ?? null)) {
    foreach ($previewItem['mega_menu']['columns'] as $column) {
        if (!is_array($column)) {
            continue;
        }
        $slot = max(0, min(5, (int) ($column['slot'] ?? 0)));
        $previewColumns[$slot] = $column;
    }
    ksort($previewColumns);
}

$resolveBlockZoneMode = static function (?string $regionName, bool $isSection): string {
    if ($isSection) {
        return 'section';
    }

    if (in_array($regionName, ['header_before', 'header_after', 'footer'], true)) {
        return 'inline';
    }

    return 'layout';
};

$renderBlockZone = static function (array $blocks, ?string $regionName = null, bool $isSection = false) use ($resolveBlockZoneMode): string {
    $mode = $resolveBlockZoneMode($regionName, $isSection);

    ob_start();
    ?>
    <div class="studio-block-zone studio-preview-block-zone studio-block-zone--<?= e($mode) ?><?= $blocks === [] ? ' is-empty' : '' ?>">
        <?php if ($blocks === []): ?>
            <div class="studio-canvas-empty studio-canvas-empty-block studio-canvas-empty-block--<?= e($mode) ?>">
                <div class="studio-canvas-empty-copy"><?= e(__('studio_canvas_block_drop', 'Studio')) ?></div>
            </div>
        <?php else: ?>
            <?php foreach ($blocks as $block): ?>
                <?php if (!is_array($block)): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <?php
                $blockType = (string) ($block['type'] ?? 'text');
                $blockLabel = (string) ($block['label'] ?? '');
                $blockSettings = is_array($block['settings'] ?? null) ? $block['settings'] : [];
                $blockItems = is_array($block['items'] ?? null) ? $block['items'] : [];
                ?>
                <div class="studio-block">
                    <div class="studio-block-label"><?= e($blockLabel) ?></div>
                    <?php if ($blockType === 'heading'): ?>
                        <h3><?= e($blockSettings['text'] ?? '') ?></h3>
                    <?php elseif ($blockType === 'text'): ?>
                        <p><?= e($blockSettings['text'] ?? '') ?></p>
                    <?php elseif ($blockType === 'button'): ?>
                        <a class="studio-block-button" href="<?= e($blockSettings['url'] ?? '#') ?>"><?= e($blockSettings['text'] ?? '') ?></a>
                    <?php elseif ($blockType === 'image'): ?>
                        <?php
                        $previewImageSource = trim((string) ($blockSettings['src'] ?? ''));
                        $previewImageHeight = trim((string) ($blockSettings['height'] ?? 'auto'));
                        $previewImageHeight = in_array($previewImageHeight, ['auto', '180', '240', '320', '420', '560'], true) ? $previewImageHeight : 'auto';
                        ?>
                        <?php if ($previewImageSource !== ''): ?>
                            <figure class="studio-block-image" data-image-height="<?= e($previewImageHeight) ?>">
                                <img src="<?= e(site_media_url($previewImageSource) ?: $previewImageSource) ?>" alt="<?= e($blockSettings['alt'] ?? '') ?>">
                            </figure>
                        <?php else: ?>
                            <div class="studio-block-image is-empty" data-image-height="<?= e($previewImageHeight) ?>"><?= e(__('studio_canvas_fake_media', 'Studio')) ?></div>
                        <?php endif; ?>
                    <?php elseif ($blockType === 'cards'): ?>
                        <div class="studio-block-cards">
                            <?php foreach ($blockItems as $item): ?>
                                <?php if (!is_array($item)): ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <article>
                                    <strong><?= e($item['title'] ?? '') ?></strong>
                                    <span><?= e($item['text'] ?? '') ?></span>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($blockType === 'form'): ?>
                        <div class="studio-block-placeholder">@ <?= e($blockSettings['text'] ?? '') ?></div>
                    <?php elseif ($blockType === 'map'): ?>
                        <div class="studio-block-placeholder">⌖ <?= e($blockSettings['address'] ?? '') ?></div>
                    <?php elseif ($blockType === 'plugin'): ?>
                        <div class="studio-block-placeholder">⚙ <?= e($blockSettings['plugin'] ?? '') ?></div>
                    <?php else: ?>
                        <div class="studio-block-spacer"><?= e(__('studio_canvas_spacer', 'Studio')) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php

    return (string) ob_get_clean();
};
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>" dir="<?= text_direction() ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageMeta['title'] ?? $pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e($stylesUrl) ?>">
</head>
<body class="studio-preview-body">
    <div class="studio-preview-shell">
        <header class="studio-preview-topbar">
            <a class="studio-back" href="<?= e($backUrl) ?>"><?= e(__('studio_back_studio', 'Studio')) ?></a>
            <div class="studio-preview-title">
                <strong><?= e($pageMeta['title'] ?? $pageTitle) ?></strong>
                <span><?= e(__('studio_preview_caption', 'Studio')) ?></span>
            </div>
        </header>

        <main class="studio-preview-main">
            <div
                class="studio-stage studio-preview-stage"
                data-preview-stage="1"
                data-design-primary="<?= e($design['primary'] ?? '#4F46E5') ?>"
                data-design-accent="<?= e($design['accent'] ?? '#111827') ?>"
                data-design-ink="<?= e($design['ink'] ?? '#111827') ?>"
                data-design-paper="<?= e($design['paper'] ?? '#FFFFFF') ?>"
                data-design-soft="<?= e($design['soft'] ?? '#F7F8FA') ?>"
                data-design-radius="<?= e((string) ($design['radius'] ?? '8')) ?>"
                data-design-width="<?= e((string) ($design['width'] ?? '1180')) ?>"
                data-design-font="<?= e($design['font'] ?? '') ?>"
            >
                <div class="studio-layout-canvas studio-preview-layout">
                    <header class="studio-layout-region studio-layout-region-header studio-preview-region is-expanded">
                        <div class="studio-layout-surface studio-layout-surface-header">
                            <div class="studio-header-stack">
                                <section class="studio-layout-slot studio-layout-slot-header_before">
                                    <div class="studio-layout-slot-head">
                                        <strong><?= e(__('studio_layout_header_before_title', 'Studio')) ?></strong>
                                        <span>&lt;<?= e(__('studio_layout_header_before_tag', 'Studio')) ?>&gt;</span>
                                    </div>
                                    <?= $renderBlockZone($headerBeforeBlocks, 'header_before') ?>
                                </section>

                                <section class="studio-layout-slot studio-layout-slot-nav">
                                    <div class="studio-layout-slot-head">
                                        <strong><?= e(__('studio_layout_navigation_title', 'Studio')) ?></strong>
                                        <span>&lt;<?= e(__('studio_layout_navigation_tag', 'Studio')) ?>&gt;</span>
                                    </div>
                                    <div class="studio-layout-surface studio-layout-surface-nav">
                                        <div class="studio-canvas-nav studio-preview-nav">
                                            <?php foreach (['top', 'main', 'bottom'] as $rowName): ?>
                                                <?php
                                                $row = is_array($navbarRows[$rowName] ?? null) ? $navbarRows[$rowName] : [];
                                                $hasRowContent = false;
                                                foreach (['left', 'center', 'right'] as $zoneName) {
                                                    if (is_array($row[$zoneName] ?? null) && $row[$zoneName] !== []) {
                                                        $hasRowContent = true;
                                                        break;
                                                    }
                                                }
                                                if (!$hasRowContent && $rowName !== 'main') {
                                                    continue;
                                                }
                                                ?>
                                                <div class="studio-nav-row studio-nav-row-<?= e($rowName) ?>">
                                                    <?php foreach (['left', 'center', 'right'] as $zoneName): ?>
                                                        <?php $zone = is_array($row[$zoneName] ?? null) ? $row[$zoneName] : []; ?>
                                                        <div class="studio-nav-zone">
                                                            <?php foreach ($zone as $element): ?>
                                                                <?php if (!is_array($element)): ?>
                                                                    <?php continue; ?>
                                                                <?php endif; ?>
                                                                <?php
                                                                $kind = (string) ($element['kind'] ?? 'text');
                                                                $brandImage = trim((string) ($element['src'] ?? ''));
                                                                $brandAlt = trim((string) ($element['alt'] ?? ($element['label'] ?? ($navbar['brand']['label'] ?? ''))));
                                                                ?>
                                                                <div class="studio-nav-element studio-nav-element-<?= e($kind) ?>">
                                                                    <?php if ($kind === 'menu'): ?>
                                                                        <div class="studio-canvas-nav-items studio-canvas-nav-menu">
                                                                            <?php foreach (($navbar['items'] ?? []) as $item): ?>
                                                                                <?php if (!is_array($item)): ?>
                                                                                    <?php continue; ?>
                                                                                <?php endif; ?>
                                                                                <a class="studio-canvas-nav-item" href="<?= e($item['url'] ?? '#') ?>" target="<?= e($item['target'] ?? '_self') ?>"><?= e($item['label'] ?? '') ?></a>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php elseif ($kind === 'brand'): ?>
                                                                        <span class="studio-nav-brand">
                                                                            <?php if ($brandImage !== ''): ?>
                                                                                <img class="studio-nav-brand-media" src="<?= e(site_media_url($brandImage) ?: $brandImage) ?>" alt="<?= e($brandAlt) ?>">
                                                                            <?php endif; ?>
                                                                            <strong class="studio-nav-brand-text"><?= e($element['label'] ?? ($navbar['brand']['label'] ?? '')) ?></strong>
                                                                        </span>
                                                                    <?php elseif ($kind === 'button'): ?>
                                                                        <span class="studio-nav-button-chip"><?= e($element['label'] ?? '') ?></span>
                                                                    <?php elseif (in_array($kind, ['language', 'cart', 'account'], true)): ?>
                                                                        <span class="studio-nav-utility-chip"><?= e($element['label'] ?? '') ?></span>
                                                                    <?php else: ?>
                                                                        <span class="studio-nav-inline-text"><?= e($element['text'] ?? '') ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (is_array($previewItem) && ($previewItem['mega_menu']['enabled'] ?? false) === true): ?>
                                                <div class="studio-mega-preview studio-preview-mega">
                                                    <div class="studio-mega-preview-head">
                                                        <strong><?= e(__('studio_canvas_mega_prefix', 'Studio')) ?> <?= e($previewItem['label'] ?? '') ?></strong>
                                                    </div>
                                                    <div class="studio-mega-columns is-cols-<?= e((string) $megaColumnsDesktop) ?>">
                                                        <?php for ($slotIndex = 0; $slotIndex < $megaColumnsDesktop; $slotIndex++): ?>
                                                            <?php $column = $previewColumns[$slotIndex] ?? null; ?>
                                                            <div class="studio-mega-slot is-slot-<?= e((string) ($slotIndex + 1)) ?> <?= $column ? 'is-occupied' : 'is-empty' ?>">
                                                                <?php if (is_array($column)): ?>
                                                                    <div class="studio-mega-column">
                                                                        <span class="studio-mega-slot-badge"><?= e((string) ($slotIndex + 1)) ?></span>
                                                                        <strong><?= e($column['title'] ?? '') ?></strong>
                                                                        <?php foreach (($column['elements'] ?? []) as $element): ?>
                                                                            <?php if (!is_array($element)): ?>
                                                                                <?php continue; ?>
                                                                            <?php endif; ?>
                                                                            <?php $kind = (string) ($element['kind'] ?? 'link'); ?>
                                                                            <?php if ($kind === 'text'): ?>
                                                                                <div class="studio-preview-mega-text">
                                                                                    <strong><?= e($element['title'] ?? '') ?></strong>
                                                                                    <p><?= e($element['text'] ?? '') ?></p>
                                                                                </div>
                                                                            <?php elseif ($kind === 'button'): ?>
                                                                                <a class="studio-block-button" href="<?= e($element['url'] ?? '#') ?>" target="<?= e($element['target'] ?? '_self') ?>"><?= e($element['label'] ?? '') ?></a>
                                                                            <?php else: ?>
                                                                                <a class="studio-preview-mega-link" href="<?= e($element['url'] ?? '#') ?>" target="<?= e($element['target'] ?? '_self') ?>"><?= e($element['label'] ?? '') ?></a>
                                                                            <?php endif; ?>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="studio-mega-slot-empty" aria-hidden="true"></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </section>

                                <section class="studio-layout-slot studio-layout-slot-header_after">
                                    <div class="studio-layout-slot-head">
                                        <strong><?= e(__('studio_layout_header_after_title', 'Studio')) ?></strong>
                                        <span>&lt;<?= e(__('studio_layout_header_after_tag', 'Studio')) ?>&gt;</span>
                                    </div>
                                    <?= $renderBlockZone($headerAfterBlocks, 'header_after') ?>
                                </section>
                            </div>
                        </div>
                    </header>

                    <div class="studio-layout-main-shell studio-preview-main-shell<?= $asideBlocks === [] ? ' is-main-only' : '' ?>">
                        <main class="studio-layout-region studio-layout-region-main studio-preview-region">
                            <div class="studio-layout-surface studio-layout-surface-main">
                                <?php if ($sections === []): ?>
                                    <div class="studio-canvas-empty studio-canvas-empty-section">
                                        <div class="studio-canvas-empty-copy"><?= e(__('studio_canvas_section_drop', 'Studio')) ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php foreach ($sections as $section): ?>
                                    <?php if (!is_array($section)): ?>
                                        <?php continue; ?>
                                    <?php endif; ?>
                                    <?php
                                    $type = (string) ($section['type'] ?? 'hero');
                                    $settings = is_array($section['settings'] ?? null) ? $section['settings'] : [];
                                    $items = is_array($section['items'] ?? null) ? $section['items'] : [];
                                    $blocks = is_array($section['blocks'] ?? null) ? $section['blocks'] : [];
                                    ?>
                                    <section class="studio-canvas-section studio-preview-section">
                                        <div class="studio-section-frame">
                                            <div class="studio-section-preview studio-section-<?= e($type) ?>">
                                                <?php if ($type === 'hero'): ?>
                                                    <div class="studio-hero-preview">
                                                        <small><?= e($settings['eyebrow'] ?? '') ?></small>
                                                        <h1><?= e($settings['title'] ?? '') ?></h1>
                                                        <p><?= e($settings['text'] ?? '') ?></p>
                                                        <?php if (($settings['button_label'] ?? '') !== ''): ?>
                                                            <a href="<?= e($settings['button_url'] ?? '#') ?>"><?= e($settings['button_label'] ?? '') ?></a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($type === 'services' || $type === 'blog'): ?>
                                                    <div class="studio-heading-preview">
                                                        <small><?= e($settings['eyebrow'] ?? '') ?></small>
                                                        <h2><?= e($settings['title'] ?? '') ?></h2>
                                                    </div>
                                                    <div class="studio-card-grid">
                                                        <?php foreach ($items as $item): ?>
                                                            <?php if (!is_array($item)): ?>
                                                                <?php continue; ?>
                                                            <?php endif; ?>
                                                            <article>
                                                                <strong><?= e($item['title'] ?? '') ?></strong>
                                                                <p><?= e($item['text'] ?? '') ?></p>
                                                            </article>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php elseif ($type === 'split'): ?>
                                                    <div class="studio-split-preview">
                                                        <div>
                                                            <small><?= e($settings['eyebrow'] ?? '') ?></small>
                                                            <h2><?= e($settings['title'] ?? '') ?></h2>
                                                            <p><?= e($settings['text'] ?? '') ?></p>
                                                            <?php if (($settings['button_label'] ?? '') !== ''): ?>
                                                                <a href="<?= e($settings['button_url'] ?? '#') ?>"><?= e($settings['button_label'] ?? '') ?></a>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="studio-fake-media"><?= e(__('studio_canvas_fake_media', 'Studio')) ?></div>
                                                    </div>
                                                <?php elseif ($type === 'stats'): ?>
                                                    <div class="studio-stats-preview">
                                                        <?php foreach ($items as $item): ?>
                                                            <?php if (!is_array($item)): ?>
                                                                <?php continue; ?>
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?= e($item['value'] ?? '') ?></strong>
                                                                <span><?= e($item['label'] ?? '') ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php elseif ($type === 'testimonial'): ?>
                                                    <blockquote class="studio-testimonial-preview">
                                                        “<?= e($settings['quote'] ?? '') ?>”
                                                        <cite><?= e($settings['author'] ?? '') ?></cite>
                                                    </blockquote>
                                                <?php elseif ($type === 'faq'): ?>
                                                    <div class="studio-heading-preview">
                                                        <small><?= e($settings['eyebrow'] ?? '') ?></small>
                                                        <h2><?= e($settings['title'] ?? '') ?></h2>
                                                    </div>
                                                    <?php foreach ($items as $item): ?>
                                                        <?php if (!is_array($item)): ?>
                                                            <?php continue; ?>
                                                        <?php endif; ?>
                                                        <details class="studio-faq-preview" open>
                                                            <summary><?= e($item['question'] ?? '') ?></summary>
                                                            <p><?= e($item['answer'] ?? '') ?></p>
                                                        </details>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="studio-cta-preview">
                                                        <h2><?= e($settings['title'] ?? '') ?></h2>
                                                        <p><?= e($settings['text'] ?? '') ?></p>
                                                        <?php if (($settings['button_label'] ?? '') !== ''): ?>
                                                            <a href="<?= e($settings['button_url'] ?? '#') ?>"><?= e($settings['button_label'] ?? '') ?></a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($blocks !== []): ?>
                                                    <?= $renderBlockZone($blocks, null, true) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </section>
                                <?php endforeach; ?>
                            </div>
                        </main>

                        <?php if ($asideBlocks !== []): ?>
                            <aside class="studio-layout-region studio-layout-region-aside studio-preview-region is-expanded">
                                <div class="studio-layout-surface">
                                    <?= $renderBlockZone($asideBlocks, 'aside') ?>
                                </div>
                            </aside>
                        <?php endif; ?>
                    </div>

                    <?php if ($footerBlocks !== []): ?>
                        <footer class="studio-layout-region studio-layout-region-footer studio-preview-region is-expanded">
                            <div class="studio-layout-surface">
                                <?= $renderBlockZone($footerBlocks, 'footer') ?>
                            </div>
                        </footer>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= e($previewScriptUrl) ?>" defer></script>
</body>
</html>
