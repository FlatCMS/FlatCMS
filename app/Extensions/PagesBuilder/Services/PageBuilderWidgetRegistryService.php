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

use App\Core\ModuleManager;

final class PageBuilderWidgetRegistryService
{
    private const MODULE = 'PagesBuilder';

    /**
     * Official PagesBuilder widget catalog, aligned with
     * `updates/app/Modules/PagesBuilder/Config/widgets.php`.
     *
     * @var array<int, string>
     */
    private const OFFICIAL_WIDGET_TYPES = [
        'heading',
        'text',
        'hero',
        'stats_section',
        'logo_cloud',
        'faq_accordion',
        'testimonial_cards',
        'pricing_plans',
        'content_split_media',
        'newsletter_section',
        'contact_section',
        'video_player',
        'feature_grid',
        'snap_cards',
        'carousel',
        'image',
        'button',
        'newsletter',
        'contact',
        'spacer',
        'divider',
    ];

    private ModuleManager $modules;

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $widgetContracts = null;

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $fileWidgetDefinitions = null;

    public function __construct(?ModuleManager $modules = null)
    {
        $this->modules = $modules ?? new ModuleManager();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return array_values($this->filterDefinitionsByVisibility(true));
    }

    /**
     * Internal builder blocks that must stay editable but hidden from the
     * visible widget catalog.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lockedDefinitions(): array
    {
        $definitions = [];
        foreach ($this->filterDefinitionsByVisibility(false) as $type => $definition) {
            $definition['locked'] = true;
            $definitions[$type] = $definition;
        }

        return array_values($definitions);
    }

    /**
     * @return array<int, string>
     */
    public function knownTypes(): array
    {
        return array_keys($this->loadFileWidgetDefinitions());
    }

    /**
     * @return array{css: array<int, string>, js: array<int, string>}
     */
    public function previewAssets(): array
    {
        $assets = [
            'css' => [],
            'js' => [],
        ];

        foreach ($this->loadFileWidgetDefinitions() as $definition) {
            $previewCssAssets = $this->normalizeAssetUrls($definition['preview_css_assets'] ?? ($definition['preview_css_asset'] ?? null));
            foreach ($previewCssAssets as $previewCssAsset) {
                $assets['css'][] = $previewCssAsset;
            }

            $previewJsAssets = $this->normalizeAssetUrls($definition['preview_js_assets'] ?? ($definition['preview_js_asset'] ?? null));
            foreach ($previewJsAssets as $previewJsAsset) {
                $assets['js'][] = $previewJsAsset;
            }
        }

        $assets['css'] = array_values(array_unique($assets['css']));
        $assets['js'] = array_values(array_unique($assets['js']));

        return $assets;
    }

    /**
     * @return array{css: array<int, string>, js: array<int, string>}
     */
    public function frontendAssetsForType(string $type): array
    {
        $definition = $this->loadFileWidgetDefinitions()[strtolower(trim($type))] ?? null;
        if (!is_array($definition)) {
            return ['css' => [], 'js' => []];
        }

        return [
            'css' => $this->normalizeAssetUrls($definition['css_assets'] ?? null),
            'js' => $this->normalizeAssetUrls($definition['js_assets'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $page
     * @param array<string, mixed> $context
     */
    public function renderBlock(
        string $blockId,
        string $type,
        array $settings,
        array $page,
        array $context,
        callable $renderCanonicalContent
    ): ?array {
        $safeType = strtolower(trim($type));
        $contract = $this->loadWidgetContracts()[$safeType] ?? null;
        if (!is_array($contract)) {
            return null;
        }

        $renderPath = trim((string) ($contract['files']['render_php']['path'] ?? ''));
        if ($renderPath === '' || !is_file($renderPath)) {
            return null;
        }

        $pagesBuilderBlockType = $safeType;
        $pagesBuilderBlockSettings = $settings;
        $pagesBuilderPage = $page;
        $pagesBuilderRenderContext = $context;
        $pagesBuilderRenderCanonicalContent = $renderCanonicalContent;
        $pagesBuilderBlockId = $blockId;

        ob_start();
        $result = include $renderPath;
        $output = ob_get_clean();

        $normalized = [
            'html' => is_string($output) ? $output : '',
            'css' => '',
            'assets' => $this->frontendAssetsForType($safeType),
        ];

        if (is_callable($result)) {
            $rendered = $result(
                $settings,
                [
                    'id' => $blockId,
                    'type' => $safeType,
                    'page' => $page,
                    'helpers' => [
                        'escape' => static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                        'escape_attr' => static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8'),
                        'resolve_image' => static fn(string $value): string => site_media_url($value),
                        'sanitize_rich_text' => static fn(string $value): string => (string) $renderCanonicalContent($value),
                        'render_content' => static fn(string $value): string => (string) $renderCanonicalContent($value),
                    ],
                    'source_url' => (string) ($context['source_url'] ?? ''),
                    'locale' => (string) ($context['locale'] ?? ''),
                    'render_context' => $context,
                ]
            );

            $normalized = $this->normalizeRenderResult($rendered, $normalized['assets']);
            if ($normalized['html'] === '' && is_string($output)) {
                $normalized['html'] = $output;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadFileWidgetDefinitions(): array
    {
        if (is_array($this->fileWidgetDefinitions)) {
            return $this->fileWidgetDefinitions;
        }

        $definitions = [];
        foreach ($this->loadWidgetContracts() as $type => $contract) {
            $definitionPath = trim((string) ($contract['files']['widget_php']['path'] ?? ''));
            if ($definitionPath === '' || !is_file($definitionPath)) {
                continue;
            }

            $definition = require $definitionPath;
            if (!is_array($definition)) {
                continue;
            }

            $definition = $this->normalizeDefinition($contract, $definition);
            $definitionType = strtolower(trim((string) ($definition['type'] ?? $type)));
            if ($definitionType === '') {
                continue;
            }

            $definition['type'] = $definitionType;
            $definitions[$definitionType] = $definition;
        }

        $this->fileWidgetDefinitions = $definitions;

        return $this->fileWidgetDefinitions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function filterDefinitionsByVisibility(bool $publicOnly): array
    {
        $definitions = [];
        foreach ($this->loadFileWidgetDefinitions() as $type => $definition) {
            $isOfficial = in_array($type, self::OFFICIAL_WIDGET_TYPES, true);
            if ($publicOnly !== $isOfficial) {
                continue;
            }

            $definitions[$type] = $definition;
        }

        return $definitions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadWidgetContracts(): array
    {
        if (is_array($this->widgetContracts)) {
            return $this->widgetContracts;
        }

        $contracts = [];
        foreach ($this->modules->widgetsFor(self::MODULE) as $widget) {
            if (!is_array($widget)) {
                continue;
            }

            $key = strtolower(trim((string) ($widget['key'] ?? '')));
            if ($key === '') {
                continue;
            }

            $contracts[$key] = $widget;

            $typeAlias = str_replace('-', '_', $key);
            if ($typeAlias !== '' && !isset($contracts[$typeAlias])) {
                $contracts[$typeAlias] = $widget;
            }
        }

        $this->widgetContracts = $contracts;

        return $this->widgetContracts;
    }

    /**
     * @param array<string, mixed> $contract
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function normalizeDefinition(array $contract, array $definition): array
    {
        if (isset($definition['definition']) && is_array($definition['definition'])) {
            $widgetName = (string) ($contract['name'] ?? $contract['key'] ?? '');
            $exported = is_string($widgetName) && $widgetName !== ''
                ? PageBuilderWidgetLocaleService::resolveSpecValue($widgetName, $definition['definition'])
                : $definition['definition'];

            $normalized = is_array($exported) ? $exported : [];
            if ($normalized !== []) {
                $assets = is_array($normalized['assets'] ?? null) ? $normalized['assets'] : [];
                $normalized['preview_css_asset'] = $this->firstAssetPath($assets['preview_css'] ?? null);
                $normalized['preview_js_asset'] = $this->firstAssetPath($assets['preview_js'] ?? null);
                $normalized['preview_css_assets'] = $this->assetPaths($assets['preview_css'] ?? null);
                $normalized['preview_js_assets'] = $this->assetPaths($assets['preview_js'] ?? null);
                $normalized['css_assets'] = $this->assetPaths($assets['css'] ?? null);
                $normalized['js_assets'] = $this->assetPaths($assets['js'] ?? null);
                return $normalized;
            }
        }

        return $definition;
    }

    /**
     * @param array<string, mixed>|null $defaultAssets
     * @return array{html: string, css: string, assets: array{css: array<int, string>, js: array<int, string>}}
     */
    private function normalizeRenderResult(mixed $rendered, ?array $defaultAssets = null): array
    {
        $assets = [
            'css' => [],
            'js' => [],
        ];

        if (is_array($defaultAssets)) {
            $assets['css'] = array_values(array_unique(array_map('strval', is_array($defaultAssets['css'] ?? null) ? $defaultAssets['css'] : [])));
            $assets['js'] = array_values(array_unique(array_map('strval', is_array($defaultAssets['js'] ?? null) ? $defaultAssets['js'] : [])));
        }

        if (is_string($rendered)) {
            return [
                'html' => $rendered,
                'css' => '',
                'assets' => $assets,
            ];
        }

        if (!is_array($rendered)) {
            return [
                'html' => '',
                'css' => '',
                'assets' => $assets,
            ];
        }

        $resultAssets = is_array($rendered['assets'] ?? null) ? $rendered['assets'] : [];
        $assets['css'] = array_values(array_unique(array_merge($assets['css'], $this->normalizeAssetUrls($resultAssets['css'] ?? null))));
        $assets['js'] = array_values(array_unique(array_merge($assets['js'], $this->normalizeAssetUrls($resultAssets['js'] ?? null))));

        return [
            'html' => trim((string) ($rendered['html'] ?? '')),
            'css' => trim((string) ($rendered['css'] ?? '')),
            'assets' => $assets,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeAssetUrls(mixed $raw): array
    {
        $paths = is_array($raw) ? $raw : (is_string($raw) && trim($raw) !== '' ? [$raw] : []);
        $urls = [];

        foreach ($paths as $path) {
            $assetPath = $this->normalizeAssetPath((string) $path);
            if ($assetPath === '') {
                continue;
            }

            $urls[] = module_asset(self::MODULE, $assetPath);
        }

        return array_values(array_unique($urls));
    }

    /**
     * @return array<int, string>
     */
    private function assetPaths(mixed $raw): array
    {
        $paths = is_array($raw) ? $raw : (is_string($raw) && trim($raw) !== '' ? [$raw] : []);
        $normalized = [];
        foreach ($paths as $path) {
            $assetPath = $this->normalizeAssetPath((string) $path);
            if ($assetPath !== '') {
                $normalized[] = $assetPath;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function firstAssetPath(mixed $raw): string
    {
        $paths = $this->assetPaths($raw);
        return $paths[0] ?? '';
    }

    private function normalizeAssetPath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path));
        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('~^Assets/~i', '', $normalized);
        return is_string($normalized) ? ltrim($normalized, '/') : '';
    }
}
