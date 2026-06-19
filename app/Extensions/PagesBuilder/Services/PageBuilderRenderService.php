<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Services;

final class PageBuilderRenderService
{
    /**
     * Widgets backed by external mutable data must stay live-rendered.
     *
     * @var array<int, string>
     */
    private const CACHE_BYPASS_WIDGET_TYPES = [
        'contact',
        'contact_section',
        'newsletter',
        'newsletter_section',
    ];

    private PageBuilderWidgetRegistryService $widgetRegistry;
    /**
     * @var array<int, string>
     */
    private array $runtimeRules = [];
    /**
     * @var array<int, string>
     */
    private array $runtimeCssParts = [];
    /**
     * @var array{css: array<int, string>, js: array<int, string>}
     */
    private array $runtimeAssetUrls = ['css' => [], 'js' => []];
    private bool $renderContainsHero = false;
    private static ?string $renderCodeVersion = null;

    public function __construct(?PageBuilderWidgetRegistryService $widgetRegistry = null)
    {
        $this->widgetRegistry = $widgetRegistry ?? new PageBuilderWidgetRegistryService();
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $state
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function buildRenderablePage(array $page, array $state, array $context = []): array
    {
        $rendered = $this->loadCachedRenderedPage($page, $state);
        if (!is_array($rendered)) {
            $rendered = $this->renderBuilderHtml($page, $state, $context);
            $this->storeCachedRenderedPage($page, $state, $rendered);
        }

        return $this->applyRenderedPayload($page, $state, $rendered);
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $state
     * @param array<string, mixed> $rendered
     * @return array<string, mixed>
     */
    private function applyRenderedPayload(array $page, array $state, array $rendered): array
    {
        $builderHtml = (string) ($rendered['html'] ?? '');
        $builderCss = trim((string) ($rendered['css'] ?? ''));
        $assetUrls = is_array($rendered['assets'] ?? null) ? $rendered['assets'] : [];
        $hasHero = (bool) ($rendered['has_hero'] ?? false);

        $page['content'] = $builderHtml;
        $page['render_mode'] = 'builder';
        $page['editor_mode'] = 'builder';
        if ($hasHero) {
            $page['page_header_enabled'] = false;
        }
        $page['builder_state'] = $state;
        if ($assetUrls !== []) {
            $page['builder_assets'] = $assetUrls;
        }
        if ($builderCss !== '') {
            $page['builder_css'] = $builderCss;
            $cssHref = runtime_css_asset($builderCss, 'pages-builder', (string) ($page['id'] ?? ''));
            if ($cssHref !== '') {
                $page['builder_assets']['css'] = array_values(array_unique(array_merge(
                    is_array($page['builder_assets']['css'] ?? null) ? $page['builder_assets']['css'] : [],
                    [$cssHref]
                )));
            }
        }

        return $page;
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $state
     * @param array<string, mixed> $context
     */
    private function renderBuilderHtml(array $page, array $state, array $context): array
    {
        $builder = is_array($state['builder'] ?? null) ? $state['builder'] : null;
        $sections = $this->normalizeRenderableSections($builder);
        if ($sections === []) {
            return [
                'html' => $this->renderLegacyStateFallback($page, $state, $context),
                'css' => '',
                'assets' => ['css' => [], 'js' => []],
                'has_hero' => false,
            ];
        }

        $this->runtimeRules = [];
        $this->runtimeCssParts = [];
        $this->runtimeAssetUrls = ['css' => [], 'js' => []];
        $this->renderContainsHero = false;

        $parts = [];
        foreach ($sections as $section) {
            $parts[] = $this->renderSection($section, $page, $context);
        }

        $htmlParts = array_values(array_filter($parts, static fn(string $value): bool => trim($value) !== ''));
        if ($htmlParts === []) {
            return [
                'html' => $this->renderLegacyStateFallback($page, $state, $context),
                'css' => '',
                'assets' => ['css' => [], 'js' => []],
                'has_hero' => false,
            ];
        }

        $scope = 'pb-page';
        $pageId = trim((string) ($page['id'] ?? ''));
        if ($pageId !== '') {
            $scope .= ' pb-page-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $pageId);
        }

        $assetUrls = $this->mergeAssetUrls($this->runtimeAssetUrls, $this->resolveBuilderAssetUrls($state));
        $cssParts = array_values(array_filter(array_merge(
            $this->runtimeCssParts,
            $this->runtimeRules
        ), static fn(string $value): bool => trim($value) !== ''));

        return [
            'html' => '<div class="' . e($scope) . '">' . implode('', $htmlParts) . '</div>',
            'css' => implode("\n", $cssParts),
            'assets' => $assetUrls,
            'has_hero' => $this->renderContainsHero || $this->builderContainsBlockType($state, 'hero'),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function renderCanonicalContent(string $content, array $context): string
    {
        if ($content === '') {
            return '';
        }

        $rendered = flatcms_render_shortcodes($content, [
            'source_url' => (string) ($context['source_url'] ?? ''),
            'locale' => (string) ($context['locale'] ?? ''),
        ]);

        return $this->normalizeContentMediaUrls($rendered);
    }

    private function normalizeContentMediaUrls(string $content): string
    {
        if ($content === '') {
            return $content;
        }

        return (string) preg_replace_callback(
            '/\b(src|href|poster)\s*=\s*(["\'])(.*?)\2/i',
            function (array $matches): string {
                $attribute = (string) ($matches[1] ?? '');
                $quote = (string) ($matches[2] ?? '"');
                $rawValue = html_entity_decode((string) ($matches[3] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if ($rawValue === '' || str_starts_with($rawValue, 'data:') || str_starts_with($rawValue, 'blob:')) {
                    return $matches[0];
                }

                if (!$this->shouldNormalizeMediaUrl($attribute, $rawValue)) {
                    return $matches[0];
                }

                $normalized = site_media_url($rawValue);
                if ($normalized === '') {
                    return $matches[0];
                }

                return $attribute . '=' . $quote . htmlspecialchars($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8') . $quote;
            },
            $content
        );
    }

    private function shouldNormalizeMediaUrl(string $attribute, string $rawValue): bool
    {
        $attributeName = strtolower(trim($attribute));
        if ($attributeName === 'src' || $attributeName === 'poster') {
            return true;
        }

        if ($attributeName !== 'href') {
            return false;
        }

        $value = trim($rawValue);
        if ($value === '') {
            return false;
        }

        if (preg_match('~^(#|mailto:|tel:|javascript:)~i', $value) === 1) {
            return false;
        }

        if (preg_match('~^(https?:)?//~i', $value) === 1) {
            return false;
        }

        if (flatcms_normalize_upload_media_path($value) !== '') {
            return true;
        }

        $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
        if ($path !== '' && flatcms_normalize_upload_media_path($path) !== '') {
            return true;
        }

        return $path !== '' && preg_match(
            '~\.(?:avif|bmp|gif|ico|jpe?g|png|svg|webp|mp4|webm|ogv|mp3|wav|ogg|pdf|docx?|xlsx?|pptx?|zip|rar|7z|csv|txt)$~i',
            $path
        ) === 1;
    }

    /**
     * @param array<string, mixed>|null $builder
     * @return array<int, array<string, mixed>>
     */
    private function extractRenderableBlocks(?array $builder): array
    {
        if (!is_array($builder)) {
            return [];
        }

        $knownTypes = array_fill_keys($this->widgetRegistry->knownTypes(), true);

        $sections = $builder['sections'] ?? [];
        if (!is_array($sections)) {
            return [];
        }

        $blocks = [];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $columns = $section['columns'] ?? [];
            if (!is_array($columns)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!is_array($column)) {
                    continue;
                }

                $columnBlocks = $column['blocks'] ?? [];
                if (!is_array($columnBlocks)) {
                    continue;
                }

                foreach ($columnBlocks as $block) {
                    if (!is_array($block)) {
                        continue;
                    }

                    $type = strtolower(trim((string) ($block['type'] ?? '')));
                    if ($type === '' || !isset($knownTypes[$type])) {
                        continue;
                    }

                    $blocks[] = [
                        'id' => (string) ($block['id'] ?? ''),
                        'type' => $type,
                        'settings' => is_array($block['settings'] ?? null) ? $block['settings'] : [],
                    ];
                }
            }
        }

        return $blocks;
    }

    /**
     * @param array<string, mixed>|null $builder
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRenderableSections(?array $builder): array
    {
        if (!is_array($builder)) {
            return [];
        }

        $sections = $builder['sections'] ?? null;
        if (!is_array($sections)) {
            $blocks = $builder['blocks'] ?? [];
            if (!is_array($blocks)) {
                $blocks = [];
            }

            $sections = $blocks !== []
                ? [[
                    'id' => 'sec_1',
                    'settings' => [],
                    'columns' => [[
                        'id' => 'col_1',
                        'blocks' => $blocks,
                    ]],
                ]]
                : [];
        }

        $knownTypes = array_fill_keys($this->widgetRegistry->knownTypes(), true);
        $normalized = [];
        $totalBlocks = 0;

        foreach ($sections as $index => $section) {
            if (!is_array($section)) {
                continue;
            }

            $columnsInput = $section['columns'] ?? [];
            if (!is_array($columnsInput)) {
                $columnsInput = [];
            }

            $columns = [];
            foreach ($columnsInput as $columnIndex => $column) {
                if (!is_array($column)) {
                    continue;
                }

                $blocksInput = $column['blocks'] ?? [];
                if (!is_array($blocksInput)) {
                    $blocksInput = [];
                }

                $blocks = [];
                foreach ($blocksInput as $block) {
                    if ($totalBlocks >= 200 || !is_array($block)) {
                        continue;
                    }

                    $type = strtolower(trim((string) ($block['type'] ?? '')));
                    if ($type === '' || !isset($knownTypes[$type])) {
                        continue;
                    }

                    $blocks[] = [
                        'id' => (string) ($block['id'] ?? ''),
                        'type' => $type,
                        'settings' => is_array($block['settings'] ?? null) ? $block['settings'] : [],
                    ];
                    $totalBlocks++;
                }

                $columns[] = [
                    'id' => $this->sanitizeSectionId((string) ($column['id'] ?? ''), 'col_' . ($index + 1) . '_' . ($columnIndex + 1)),
                    'blocks' => $blocks,
                ];

                if (count($columns) >= 4 || $totalBlocks >= 200) {
                    break;
                }
            }

            if ($columns === []) {
                $columns[] = [
                    'id' => $this->sanitizeSectionId('', 'col_' . ($index + 1) . '_1'),
                    'blocks' => [],
                ];
            }

            $normalized[] = [
                'id' => $this->sanitizeSectionId((string) ($section['id'] ?? ''), 'sec_' . ($index + 1)),
                'layoutTemplate' => $this->sanitizeSectionLayoutTemplate(
                    (string) ($section['layoutTemplate'] ?? $section['template'] ?? ''),
                    count($columns)
                ),
                'settings' => $this->normalizeSectionSettings($section['settings'] ?? []),
                'columns' => $columns,
            ];

            if (count($normalized) >= 60 || $totalBlocks >= 200) {
                break;
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $section
     * @param array<string, mixed> $page
     * @param array<string, mixed> $context
     */
    private function renderSection(array $section, array $page, array $context): string
    {
        $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
        $columnCount = max(1, min(4, count($columns)));
        if ($columns === []) {
            $columns = [[
                'id' => $this->sanitizeSectionId('', 'col_1'),
                'blocks' => [],
            ]];
        }

        $colParts = [];
        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }

            $blockParts = [];
            $columnBlocks = is_array($column['blocks'] ?? null) ? $column['blocks'] : [];
            foreach ($columnBlocks as $block) {
                if (!is_array($block)) {
                    continue;
                }

                $html = $this->renderRenderableBlock($block, $page, $context);
                if ($html !== '') {
                    $blockParts[] = $html;
                }
            }

            $colParts[] = '<div class="pb-col">' . implode('', $blockParts) . '</div>';
        }

        if ($colParts === []) {
            $colParts[] = '<div class="pb-col"></div>';
        }

        $layoutTemplate = $this->sanitizeSectionLayoutTemplate(
            (string) ($section['layoutTemplate'] ?? ''),
            $columnCount
        );
        $sectionSettings = $this->normalizeSectionSettings($section['settings'] ?? []);
        $containerMode = (string) ($sectionSettings['containerMode'] ?? 'container');
        $backgroundColor = $this->normalizeSectionCssValue((string) ($sectionSettings['backgroundColor'] ?? ''), 120);
        $backgroundImage = site_media_url((string) ($sectionSettings['backgroundImage'] ?? ''));
        $backgroundSize = (string) ($sectionSettings['backgroundSize'] ?? 'cover');
        $backgroundPosition = (string) ($sectionSettings['backgroundPosition'] ?? 'center center');
        $backgroundRepeat = (string) ($sectionSettings['backgroundRepeat'] ?? 'no-repeat');
        $overlayColor = $this->normalizeSectionCssValue((string) ($sectionSettings['overlayColor'] ?? ''), 120);
        $overlayOpacity = max(0, min(100, (int) ($sectionSettings['overlayOpacity'] ?? 0)));
        $paddingTop = max(0, min(240, (int) ($sectionSettings['paddingTop'] ?? 0)));
        $paddingBottom = max(0, min(240, (int) ($sectionSettings['paddingBottom'] ?? 0)));
        $sectionId = $this->sanitizeSectionId((string) ($section['id'] ?? ''), 'pb_sec_1');
        $sectionShellSelector = '[data-section-id="' . $sectionId . '"] > .pb-section-shell';
        $sectionInnerSelector = $sectionShellSelector . ' > .pb-section-inner';
        $sectionOverlaySelector = $sectionShellSelector . ' > .pb-section-overlay';

        $this->addRuntimeRule(
            $sectionInnerSelector . ' > .pb-row',
            'grid-template-columns:' . $layoutTemplate . ';'
        );
        $this->addRuntimeRule(
            $sectionInnerSelector,
            'padding-top:' . $paddingTop . 'px;padding-bottom:' . $paddingBottom . 'px;'
        );
        if ($backgroundColor !== '') {
            $this->addRuntimeRule($sectionShellSelector, 'background-color:' . $backgroundColor . ';');
        }
        if ($backgroundImage !== '') {
            $this->addRuntimeRule(
                $sectionShellSelector,
                "background-image:url('" . $this->escapeCssUrl($backgroundImage) . "');background-size:" . $backgroundSize . ';background-position:' . $backgroundPosition . ';background-repeat:' . $backgroundRepeat . ';'
            );
        }
        if ($overlayOpacity > 0) {
            $effectiveOverlayColor = $overlayColor !== '' ? $overlayColor : '#000000';
            $this->addRuntimeRule(
                $sectionOverlaySelector,
                'background-color:' . $effectiveOverlayColor . ';opacity:' . min(1, $overlayOpacity / 100) . ';'
            );
        }

        return '<section class="pb-section pb-section-mode-' . e($containerMode) . '" data-section-id="' . e($sectionId) . '">'
            . '<div class="pb-section-shell">'
            . '<div class="pb-section-overlay" aria-hidden="true"></div>'
            . '<div class="pb-section-inner pb-section-inner-' . e($containerMode) . '">'
            . '<div class="pb-row pb-row-cols-' . e((string) $columnCount) . '">' . implode('', $colParts) . '</div>'
            . '</div>'
            . '</div>'
            . '</section>';
    }

    /**
     * @param array<string, mixed> $block
     * @param array<string, mixed> $page
     * @param array<string, mixed> $context
     */
    private function renderRenderableBlock(array $block, array $page, array $context): string
    {
        $type = strtolower(trim((string) ($block['type'] ?? '')));
        $blockId = trim((string) ($block['id'] ?? ''));
        $settings = is_array($block['settings'] ?? null) ? $block['settings'] : [];
        $blockContext = $context;
        if (empty($blockContext['source_url'])) {
            $sourceUrl = $this->resolveContactSourceUrl($page, $context);
            if ($sourceUrl !== '') {
                $blockContext['source_url'] = $sourceUrl;
            }
        }
        if (empty($blockContext['locale']) && !empty($page['locale'])) {
            $blockContext['locale'] = (string) $page['locale'];
        }

        if ($type === 'contact') {
            $customRendered = $this->renderContactShortcodeBlock($settings, $page, $blockContext);
        } else {
            $customRendered = $this->widgetRegistry->renderBlock(
                $blockId,
                $type,
                $settings,
                $page,
                $blockContext,
                fn (string $content): string => $this->renderCanonicalContent($content, $blockContext)
            );
        }
        if ($customRendered === null) {
            return '';
        }

        $html = trim((string) ($customRendered['html'] ?? ''));
        $css = trim((string) ($customRendered['css'] ?? ''));
        $assets = is_array($customRendered['assets'] ?? null) ? $customRendered['assets'] : [];
        if ($css !== '') {
            $this->runtimeCssParts[] = $css;
        }
        $this->runtimeAssetUrls = $this->mergeAssetUrls($this->runtimeAssetUrls, $assets);
        if ($type === 'hero') {
            $this->renderContainsHero = true;
        }

        return $this->renderBlockContainer($blockId, $type, $html, $settings);
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $page
     * @param array<string, mixed> $context
     * @return array{html: string, css: string, assets: array{css: array<int, string>, js: array<int, string>}}|null
     */
    private function renderContactShortcodeBlock(array $settings, array $page, array $context): ?array
    {
        $slug = $this->normalizeContactFormSlug((string) ($settings['formSlug'] ?? $settings['slug'] ?? ''));
        $shortcode = '[contact-form slug="' . $slug . '"]';

        $shortcodeContext = $context;
        $sourceUrl = $this->resolveContactSourceUrl($page, $context);
        if ($sourceUrl !== '') {
            $shortcodeContext['source_url'] = $sourceUrl;
        }
        if (empty($shortcodeContext['locale']) && !empty($page['locale'])) {
            $shortcodeContext['locale'] = (string) $page['locale'];
        }

        try {
            $html = trim($this->renderCanonicalContent($shortcode, $shortcodeContext));
        } catch (\Throwable) {
            return null;
        }

        if ($html === '' || trim($html) === $shortcode || str_contains($html, '[contact-form')) {
            return null;
        }

        return [
            'html' => $html,
            'css' => '',
            'assets' => [
                'css' => $this->safeModuleAssetUrls('Contact', ['css/contact-front.css']),
                'js' => $this->safeModuleAssetUrls('Contact', ['js/contact-front.js']),
            ],
        ];
    }

    private function normalizeContactFormSlug(string $value): string
    {
        $slug = trim($value);
        if ($slug === '') {
            return 'contact-main';
        }

        $normalized = preg_replace('/[^a-zA-Z0-9_-]+/', '', $slug);
        $normalized = is_string($normalized) ? trim($normalized) : '';

        return $normalized !== '' ? $normalized : 'contact-main';
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $context
     */
    private function resolveContactSourceUrl(array $page, array $context): string
    {
        $sourceUrl = trim((string) ($context['source_url'] ?? ''));
        if ($sourceUrl !== '') {
            return $sourceUrl;
        }

        if (function_exists('flatcms_current_source_url')) {
            try {
                $sourceUrl = trim((string) flatcms_current_source_url());
                if ($sourceUrl !== '') {
                    return $sourceUrl;
                }
            } catch (\Throwable) {
                // Keep rendering even if the helper is unavailable in this context.
            }
        }

        $locale = trim((string) ($page['locale'] ?? ''));
        $slug = trim((string) ($page['slug'] ?? ''));
        if ($slug === '') {
            return '';
        }

        $path = ($locale !== '' ? '/' . $locale : '') . '/page/' . $slug;
        if (!function_exists('url')) {
            return $path;
        }

        try {
            return (string) url($path);
        } catch (\Throwable) {
            return $path;
        }
    }

    /**
     * @param array<int, string> $paths
     * @return array<int, string>
     */
    private function safeModuleAssetUrls(string $module, array $paths): array
    {
        if (!function_exists('module_asset')) {
            return [];
        }

        $urls = [];
        foreach ($paths as $path) {
            try {
                $url = trim((string) module_asset($module, $path));
            } catch (\Throwable) {
                $url = '';
            }

            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    private function buildEqualSectionTemplate(int $columnCount): string
    {
        $cols = max(1, min(4, $columnCount));
        if ($cols === 1) {
            return 'minmax(0, 1fr)';
        }

        return 'repeat(' . $cols . ', minmax(0, 1fr))';
    }

    private function sanitizeSectionLayoutTemplate(string $template, int $columnCount): string
    {
        $fallback = $this->buildEqualSectionTemplate($columnCount);
        $raw = trim($template);
        if ($raw === '' || strlen($raw) > 120) {
            return $fallback;
        }

        if (preg_match('/^[0-9a-zA-Z(),.%\s-]+$/', $raw) !== 1) {
            return $fallback;
        }

        $normalized = trim((string) preg_replace('/\s+/', ' ', $raw));
        if ($normalized === '') {
            return $fallback;
        }

        if (preg_match('/(repeat|minmax|fr)/i', $normalized) !== 1) {
            return $fallback;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSectionSettings(mixed $settingsInput): array
    {
        $settings = is_array($settingsInput) ? $settingsInput : [];

        return [
            'backgroundColor' => $this->normalizeSectionCssValue((string) ($settings['backgroundColor'] ?? ''), 120),
            'backgroundImage' => $this->normalizeSectionCssValue((string) ($settings['backgroundImage'] ?? ''), 2048),
            'backgroundSize' => $this->normalizeSectionKeyword((string) ($settings['backgroundSize'] ?? 'cover'), ['auto', 'cover', 'contain'], 'cover'),
            'backgroundPosition' => $this->normalizeSectionCssValue((string) ($settings['backgroundPosition'] ?? 'center center'), 80) ?: 'center center',
            'backgroundRepeat' => $this->normalizeSectionKeyword((string) ($settings['backgroundRepeat'] ?? 'no-repeat'), ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'], 'no-repeat'),
            'overlayColor' => $this->normalizeSectionCssValue((string) ($settings['overlayColor'] ?? ''), 120),
            'overlayOpacity' => $this->normalizeSectionRange($settings['overlayOpacity'] ?? 0, 0, 100, 0),
            'containerMode' => !empty($settings['containerModeExplicit'])
                ? $this->normalizeSectionKeyword((string) ($settings['containerMode'] ?? 'container'), ['container', 'fluid'], 'container')
                : 'container',
            'containerModeExplicit' => !empty($settings['containerModeExplicit']),
            'paddingTop' => $this->normalizeSectionRange($settings['paddingTop'] ?? 0, 0, 240, 0),
            'paddingBottom' => $this->normalizeSectionRange($settings['paddingBottom'] ?? 0, 0, 240, 0),
        ];
    }

    private function normalizeSectionCssValue(string $value, int $maxLength): string
    {
        $normalized = trim((string) preg_replace('/\s+/', ' ', $value));
        if ($normalized === '' || strlen($normalized) > $maxLength) {
            return '';
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $allowed
     */
    private function normalizeSectionKeyword(string $value, array $allowed, string $fallback): string
    {
        $normalized = strtolower(trim($value));
        return in_array($normalized, $allowed, true) ? $normalized : $fallback;
    }

    private function normalizeSectionRange(mixed $value, int $min, int $max, int $fallback): int
    {
        if (!is_numeric($value)) {
            return $fallback;
        }

        $number = (int) round((float) $value);
        return max($min, min($max, $number));
    }

    private function sanitizeSectionId(string $raw, string $fallback): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($raw));
        return $normalized !== '' ? $normalized : $fallback;
    }

    private function addRuntimeRule(string $selector, string $declarations): void
    {
        $selector = trim($selector);
        $declarations = trim($declarations);
        if ($selector === '' || $declarations === '') {
            return;
        }

        $this->runtimeRules[] = $selector . '{' . $declarations . '}';
    }

    private function escapeCssUrl(string $value): string
    {
        return str_replace(
            ["\\", "'", "\n", "\r", "\f"],
            ["\\\\", "\\'", '', '', ''],
            $value
        );
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $state
     * @param array<string, mixed> $context
     */
    private function renderLegacyStateFallback(array $page, array $state, array $context): string
    {
        $contentHtml = $this->renderCanonicalContent((string) ($page['content'] ?? ''), $context);
        $widgetPath = BASE_PATH . '/app/Extensions/PagesBuilder/Widgets/Hero/legacy-fallback.php';

        $pagesBuilderPage = $page;
        $pagesBuilderState = $state;
        $pagesBuilderContentHtml = $contentHtml;

        ob_start();
        include $widgetPath;

        return (string) ob_get_clean();
    }

    private function renderBlockContainer(string $blockId, string $type, string $innerHtml, array $settings): string
    {
        $safeBlockId = trim($blockId) !== '' ? trim($blockId) : 'pb_block_' . substr(md5($type . $innerHtml), 0, 8);
        $className = 'pb-block-' . $this->sanitizeCssIdentifier($type);
        $boxStyle = $this->buildBoxStyle($settings);
        if ($boxStyle !== '') {
            $this->addRuntimeRule('[data-block-id="' . $safeBlockId . '"]', $boxStyle);
        }

        return '<section class="pb-block ' . e($className) . '" data-block-id="' . e($safeBlockId) . '">'
            . $innerHtml
            . '</section>';
    }

    private function sanitizeCssIdentifier(string $type): string
    {
        $normalized = strtolower(trim($type));
        $normalized = preg_replace('/[^a-z0-9_-]+/', '-', $normalized);
        $normalized = is_string($normalized) ? trim($normalized, '-') : '';

        return $normalized !== '' ? $normalized : 'generic-widget';
    }

    private function buildBoxStyle(array $settings): string
    {
        $box = $this->normalizeBoxSettings($settings['__box'] ?? null);
        if ($box === []) {
            return '';
        }

        return sprintf(
            'margin:%dpx %dpx %dpx %dpx;padding:%dpx %dpx %dpx %dpx;',
            $box['mt'],
            $box['mr'],
            $box['mb'],
            $box['ml'],
            $box['pt'],
            $box['pr'],
            $box['pb'],
            $box['pl']
        );
    }

    /**
     * @return array<string, int>
     */
    private function normalizeBoxSettings(mixed $boxInput): array
    {
        if (!is_array($boxInput)) {
            return [];
        }

        $limits = [
            'mt' => [-240, 240],
            'mr' => [-240, 240],
            'mb' => [-240, 240],
            'ml' => [-240, 240],
            'pt' => [0, 240],
            'pr' => [0, 240],
            'pb' => [0, 240],
            'pl' => [0, 240],
        ];

        $normalized = [];
        $hasValue = false;
        foreach ($limits as $key => [$min, $max]) {
            $raw = $boxInput[$key] ?? 0;
            $value = is_numeric($raw) ? (int) round((float) $raw) : 0;
            $value = max($min, min($max, $value));
            if ($value !== 0) {
                $hasValue = true;
            }
            $normalized[$key] = $value;
        }

        return $hasValue ? $normalized : [];
    }

    private function sanitizeUrl(string $value): string
    {
        $url = trim($value);
        if ($url === '') {
            return '';
        }

        if ($url[0] === '#' || $url[0] === '/' || $url[0] === '?') {
            return $url;
        }

        if (preg_match('/^(https?:|mailto:|tel:)/i', $url) === 1) {
            return $url;
        }

        return '';
    }

    private function normalizeTarget(string $value): string
    {
        $target = strtolower(trim($value));
        return in_array($target, ['_self', '_blank'], true) ? $target : '_self';
    }

    /**
     * @param array<string, mixed> $state
     * @return array{css?: array<int, string>, js?: array<int, string>}
     */
    private function resolveBuilderAssetUrls(array $state): array
    {
        $assets = [];
        if ($this->builderHasRenderableBlocks($state)) {
            $assets['css'][] = module_asset('PagesBuilder', 'css/runtime.css');
            $assets['css'][] = module_asset('PagesBuilder', 'css/runtime-primitives.css');
            $assets['js'][] = module_asset('PagesBuilder', 'js/runtime-primitives.js');
        }
        if ($this->builderContainsBlockType($state, 'hero')) {
            $assets['css'][] = module_asset('PagesBuilder', 'css/hero.css');
        }

        return $assets;
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $state
     * @return array<string, mixed>|null
     */
    private function loadCachedRenderedPage(array $page, array $state): ?array
    {
        if (!$this->canUsePersistentRenderCache($state)) {
            return null;
        }

        $cachePath = $this->resolveRenderCachePath($page, $state);
        if ($cachePath === '' || !is_file($cachePath)) {
            return null;
        }

        $raw = @file_get_contents($cachePath);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $html = trim((string) ($decoded['html'] ?? ''));
        if ($html === '') {
            return null;
        }

        return [
            'html' => $html,
            'css' => trim((string) ($decoded['css'] ?? '')),
            'assets' => is_array($decoded['assets'] ?? null) ? $decoded['assets'] : ['css' => [], 'js' => []],
            'has_hero' => !empty($decoded['has_hero']),
        ];
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $state
     * @param array<string, mixed> $rendered
     */
    private function storeCachedRenderedPage(array $page, array $state, array $rendered): void
    {
        if (!$this->canUsePersistentRenderCache($state)) {
            return;
        }

        $html = trim((string) ($rendered['html'] ?? ''));
        if ($html === '') {
            return;
        }

        $cachePath = $this->resolveRenderCachePath($page, $state);
        if ($cachePath === '') {
            return;
        }

        $cacheDir = dirname($cachePath);
        if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
            return;
        }

        $payload = json_encode([
            'html' => $html,
            'css' => trim((string) ($rendered['css'] ?? '')),
            'assets' => is_array($rendered['assets'] ?? null) ? $rendered['assets'] : ['css' => [], 'js' => []],
            'has_hero' => !empty($rendered['has_hero']),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($payload) || $payload === '') {
            return;
        }

        if (@file_put_contents($cachePath, $payload, LOCK_EX) === false) {
            return;
        }

        $this->purgeStaleRenderCaches((string) ($page['id'] ?? ''), $cachePath);
    }

    private function canUsePersistentRenderCache(array $state): bool
    {
        if (!$this->builderHasRenderableBlocks($state)) {
            return false;
        }

        return !$this->builderContainsAnyBlockTypes($state, self::CACHE_BYPASS_WIDGET_TYPES);
    }

    /**
     * @param array<int, string> $types
     */
    private function builderContainsAnyBlockTypes(array $state, array $types): bool
    {
        foreach ($types as $type) {
            if ($this->builderContainsBlockType($state, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $state
     */
    private function resolveRenderCachePath(array $page, array $state): string
    {
        if (!defined('BASE_PATH')) {
            return '';
        }

        $pageId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($page['id'] ?? ''));
        if (!is_string($pageId) || $pageId === '') {
            return '';
        }

        $payload = [
            'page_id' => $pageId,
            'page_locale' => (string) ($page['locale'] ?? ''),
            'page_updated_at' => (string) ($page['updated_at'] ?? ''),
            'state_updated_at' => (string) ($state['updated_at'] ?? ''),
            'builder_version' => (int) ($state['builder_version'] ?? 2),
            'builder' => is_array($state['builder'] ?? null) ? $state['builder'] : [],
            'render_code_version' => self::resolveRenderCodeVersion(),
            'asset_url_version' => self::resolveAssetUrlVersion(),
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded) || $encoded === '') {
            return '';
        }

        $hash = substr(sha1($encoded), 0, 20);
        $dir = rtrim(BASE_PATH, '/') . '/storage/cache/pages-builder-render';

        return $dir . '/' . $pageId . '-' . $hash . '.json';
    }

    private static function resolveRenderCodeVersion(): string
    {
        if (self::$renderCodeVersion !== null) {
            return self::$renderCodeVersion;
        }

        $paths = [__FILE__];
        foreach (glob(rtrim((string) BASE_PATH, '/') . '/app/Extensions/PagesBuilder/Widgets/*/{render.php,Renderer.php}', GLOB_BRACE) ?: [] as $path) {
            if (is_string($path) && $path !== '') {
                $paths[] = $path;
            }
        }

        $version = 0;
        foreach (array_unique($paths) as $path) {
            $mtime = @filemtime($path);
            if (is_int($mtime) && $mtime > $version) {
                $version = $mtime;
            }
        }

        self::$renderCodeVersion = (string) $version;
        return self::$renderCodeVersion;
    }

    private static function resolveAssetUrlVersion(): string
    {
        $parts = [];

        if (function_exists('url')) {
            try {
                $parts[] = (string) url('/');
            } catch (\Throwable) {
                $parts[] = '';
            }
        }

        if (function_exists('module_asset')) {
            foreach ([
                ['PagesBuilder', 'css/runtime.css'],
                ['PagesBuilder', 'css/runtime-primitives.css'],
                ['PagesBuilder', 'js/runtime-primitives.js'],
            ] as $asset) {
                try {
                    $parts[] = (string) module_asset((string) $asset[0], (string) $asset[1]);
                } catch (\Throwable) {
                    $parts[] = '';
                }
            }
        }

        return sha1(implode('|', $parts));
    }

    private function purgeStaleRenderCaches(string $pageId, string $currentCachePath): void
    {
        $safePageId = preg_replace('/[^a-zA-Z0-9_-]/', '', $pageId);
        if (!is_string($safePageId) || $safePageId === '') {
            return;
        }

        $cacheDir = dirname($currentCachePath);
        $pattern = $cacheDir . '/' . $safePageId . '-*.json';
        foreach (glob($pattern) ?: [] as $candidate) {
            if (!is_string($candidate) || $candidate === $currentCachePath || !is_file($candidate)) {
                continue;
            }
            @unlink($candidate);
        }
    }

    /**
     * @param array{css?: array<int, string>, js?: array<int, string>} $left
     * @param array{css?: array<int, string>, js?: array<int, string>} $right
     * @return array{css: array<int, string>, js: array<int, string>}
     */
    private function mergeAssetUrls(array $left, array $right): array
    {
        return [
            'css' => array_values(array_unique(array_merge(
                is_array($left['css'] ?? null) ? $left['css'] : [],
                is_array($right['css'] ?? null) ? $right['css'] : []
            ))),
            'js' => array_values(array_unique(array_merge(
                is_array($left['js'] ?? null) ? $left['js'] : [],
                is_array($right['js'] ?? null) ? $right['js'] : []
            ))),
        ];
    }

    private function builderContainsBlockType(array $state, string $type): bool
    {
        $builder = is_array($state['builder'] ?? null) ? $state['builder'] : null;
        $blocks = $this->extractRenderableBlocks($builder);
        foreach ($blocks as $block) {
            if (strtolower(trim((string) ($block['type'] ?? ''))) === $type) {
                return true;
            }
        }

        return false;
    }

    private function builderHasRenderableBlocks(array $state): bool
    {
        $builder = is_array($state['builder'] ?? null) ? $state['builder'] : null;
        return $this->extractRenderableBlocks($builder) !== [];
    }

    private function toBool(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $safe = strtolower(trim((string) $value));
        if ($safe === '') {
            return $default;
        }

        if (in_array($safe, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($safe, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    /**
     * @param array<int, string> $allowed
     */
    private function normalizeOption(string $value, array $allowed, string $fallback): string
    {
        $safe = strtolower(trim($value));
        return in_array($safe, $allowed, true) ? $safe : $fallback;
    }
}
