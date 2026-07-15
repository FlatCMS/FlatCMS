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
$themesCssPath = BASE_PATH . '/app/Modules/Themes/Assets/css/themes-module.css';
$themesCssVersion = file_exists($themesCssPath) ? (string) filemtime($themesCssPath) : '';
$themesCssHref = module_asset('Themes', 'css/themes-module.css') . ($themesCssVersion !== '' ? '?v=' . rawurlencode($themesCssVersion) : '');

$themesJsPath = BASE_PATH . '/app/Modules/Themes/Assets/js/themes.js';
$themesJsVersion = file_exists($themesJsPath) ? (string) filemtime($themesJsPath) : '';
$themesJsSrc = module_asset('Themes', 'js/themes.js') . ($themesJsVersion !== '' ? '?v=' . rawurlencode($themesJsVersion) : '');

$themeColors = is_array($config['colors'] ?? null) ? $config['colors'] : [];
$customColors = is_array($custom['colors'] ?? null) ? $custom['colors'] : [];
$customLightColors = is_array($custom['light_colors'] ?? null) ? $custom['light_colors'] : [];
$buttonCustomization = is_array($buttonCustomization ?? null) ? $buttonCustomization : [];
$buttonDefaults = is_array($buttonCustomization['defaults'] ?? null) ? $buttonCustomization['defaults'] : [];
$currentButtonStyle = (string) ($buttonCustomization['style'] ?? 'theme');
$currentButtonShape = (string) ($buttonCustomization['shape'] ?? 'theme');
$currentButtonWeight = (string) ($buttonCustomization['weight'] ?? 'theme');
$badgeCustomization = is_array($badgeCustomization ?? null) ? $badgeCustomization : [];
$badgeDefaults = is_array($badgeCustomization['defaults'] ?? null) ? $badgeCustomization['defaults'] : [];
$currentBadgeStyle = (string) ($badgeCustomization['style'] ?? 'theme');
$currentBadgeShape = (string) ($badgeCustomization['shape'] ?? 'theme');
$currentBadgeWeight = (string) ($badgeCustomization['weight'] ?? 'theme');
$typographyCustomization = is_array($typographyCustomization ?? null) ? $typographyCustomization : [];
$typographyDefaults = is_array($typographyCustomization['defaults'] ?? null) ? $typographyCustomization['defaults'] : [];
$currentTypographyBodyFamily = (string) ($typographyCustomization['body_family'] ?? 'theme');
$currentTypographyHeadingFamily = (string) ($typographyCustomization['heading_family'] ?? 'theme');
$currentTypographyScale = (string) ($typographyCustomization['scale'] ?? 'theme');
$currentTypographyHeadingWeight = (string) ($typographyCustomization['heading_weight'] ?? 'theme');
$supportsDualModeCustomization = !empty($supportsDualModeCustomization);
$customCssValue = (string) ($custom['custom_css'] ?? '');

$resolveColor = static function (mixed $customValue, mixed $themeValue, string $fallback): string {
    $value = '';
    if (is_string($customValue)) {
        $value = trim($customValue);
    }
    if ($value === '' && is_string($themeValue)) {
        $value = trim($themeValue);
    }
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value) !== 1) {
        return $fallback;
    }
    return strtoupper($value);
};

$mixColor = static function (string $fromHex, string $toHex, float $ratio): string {
    $ratio = max(0.0, min(1.0, $ratio));
    $from = strtoupper($fromHex);
    $to = strtoupper($toHex);
    $fromR = hexdec(substr($from, 1, 2));
    $fromG = hexdec(substr($from, 3, 2));
    $fromB = hexdec(substr($from, 5, 2));
    $toR = hexdec(substr($to, 1, 2));
    $toG = hexdec(substr($to, 3, 2));
    $toB = hexdec(substr($to, 5, 2));
    $mixChannel = static function (int $start, int $end) use ($ratio): string {
        return str_pad(dechex((int) round($start + (($end - $start) * $ratio))), 2, '0', STR_PAD_LEFT);
    };

    return '#' . strtoupper(
        $mixChannel($fromR, $toR)
        . $mixChannel($fromG, $toG)
        . $mixChannel($fromB, $toB)
    );
};

$primaryColor = $resolveColor($customColors['primary'] ?? null, $themeColors['primary'] ?? null, '#2563EB');
$secondaryColor = $resolveColor($customColors['secondary'] ?? null, $themeColors['secondary'] ?? null, '#3B82F6');
$accentColor = $resolveColor($customColors['accent'] ?? null, $themeColors['accent'] ?? null, $secondaryColor);
$backgroundColor = $resolveColor($customColors['background'] ?? null, $themeColors['background'] ?? null, '#FFFFFF');
$surfaceColor = $resolveColor($customColors['surface'] ?? null, $themeColors['surface'] ?? null, '#F9FAFB');
$textColor = $resolveColor($customColors['text'] ?? null, $themeColors['text'] ?? null, '#111827');
$mutedTextColor = $resolveColor($customColors['text_muted'] ?? null, $themeColors['text-muted'] ?? null, '#6B7280');
$borderColor = $resolveColor($customColors['border'] ?? null, $themeColors['border'] ?? null, '#CBD5E1');

$darkColorFields = [
    ['key' => 'primary', 'label' => __('primary_color', 'Themes'), 'value' => $primaryColor],
    ['key' => 'secondary', 'label' => __('secondary_color', 'Themes'), 'value' => $secondaryColor],
    ['key' => 'accent', 'label' => __('accent_color', 'Themes'), 'value' => $accentColor],
    ['key' => 'background', 'label' => __('background_color', 'Themes'), 'value' => $backgroundColor],
    ['key' => 'surface', 'label' => __('surface_color', 'Themes'), 'value' => $surfaceColor],
    ['key' => 'text', 'label' => __('text_color', 'Themes'), 'value' => $textColor],
    ['key' => 'text_muted', 'label' => __('muted_text_color', 'Themes'), 'value' => $mutedTextColor],
    ['key' => 'border', 'label' => __('border_color', 'Themes'), 'value' => $borderColor],
];

$lightBackgroundFallback = $mixColor($backgroundColor, '#FFFFFF', 0.92);
$lightSurfaceFallback = $mixColor($surfaceColor, '#FFFFFF', 0.90);
$lightBorderFallback = $mixColor($borderColor, '#E2E8F0', 0.68);
$lightTextFallback = $mixColor($textColor, '#0F172A', 0.90);
$lightMutedTextFallback = $mixColor($mutedTextColor, '#64748B', 0.74);

$lightPrimaryColor = $resolveColor($customLightColors['primary'] ?? null, $themeColors['primary'] ?? null, $primaryColor);
$lightSecondaryColor = $resolveColor($customLightColors['secondary'] ?? null, $themeColors['secondary'] ?? null, $secondaryColor);
$lightAccentColor = $resolveColor($customLightColors['accent'] ?? null, $themeColors['accent'] ?? null, $accentColor);
$lightBackgroundColor = $resolveColor($customLightColors['background'] ?? null, null, $lightBackgroundFallback);
$lightSurfaceColor = $resolveColor($customLightColors['surface'] ?? null, null, $lightSurfaceFallback);
$lightTextColor = $resolveColor($customLightColors['text'] ?? null, null, $lightTextFallback);
$lightMutedTextColor = $resolveColor($customLightColors['text_muted'] ?? null, null, $lightMutedTextFallback);
$lightBorderColor = $resolveColor($customLightColors['border'] ?? null, null, $lightBorderFallback);

$lightColorFields = [
    ['key' => 'light_primary', 'token' => 'primary', 'label' => __('primary_color', 'Themes'), 'value' => $lightPrimaryColor],
    ['key' => 'light_secondary', 'token' => 'secondary', 'label' => __('secondary_color', 'Themes'), 'value' => $lightSecondaryColor],
    ['key' => 'light_accent', 'token' => 'accent', 'label' => __('accent_color', 'Themes'), 'value' => $lightAccentColor],
    ['key' => 'light_background', 'token' => 'background', 'label' => __('background_color', 'Themes'), 'value' => $lightBackgroundColor],
    ['key' => 'light_surface', 'token' => 'surface', 'label' => __('surface_color', 'Themes'), 'value' => $lightSurfaceColor],
    ['key' => 'light_text', 'token' => 'text', 'label' => __('text_color', 'Themes'), 'value' => $lightTextColor],
    ['key' => 'light_text_muted', 'token' => 'text_muted', 'label' => __('muted_text_color', 'Themes'), 'value' => $lightMutedTextColor],
    ['key' => 'light_border', 'token' => 'border', 'label' => __('border_color', 'Themes'), 'value' => $lightBorderColor],
];

$buttonStyleOptions = [
    'theme' => __('button_style_theme', 'Themes'),
    'classic' => __('button_style_classic', 'Themes'),
    'soft' => __('button_style_soft', 'Themes'),
    'elevated' => __('button_style_elevated', 'Themes'),
];

$buttonShapeOptions = [
    'theme' => __('button_shape_theme', 'Themes'),
    'sharp' => __('button_shape_sharp', 'Themes'),
    'rounded' => __('button_shape_rounded', 'Themes'),
    'pill' => __('button_shape_pill', 'Themes'),
];

$buttonWeightOptions = [
    'theme' => __('button_weight_theme', 'Themes'),
    'medium' => __('button_weight_medium', 'Themes'),
    'semibold' => __('button_weight_semibold', 'Themes'),
    'bold' => __('button_weight_bold', 'Themes'),
];

$badgeStyleOptions = [
    'theme' => __('badge_style_theme', 'Themes'),
    'soft' => __('badge_style_soft', 'Themes'),
    'solid' => __('badge_style_solid', 'Themes'),
    'outline' => __('badge_style_outline', 'Themes'),
];

$badgeShapeOptions = [
    'theme' => __('badge_shape_theme', 'Themes'),
    'sharp' => __('badge_shape_sharp', 'Themes'),
    'rounded' => __('badge_shape_rounded', 'Themes'),
    'pill' => __('badge_shape_pill', 'Themes'),
];

$badgeWeightOptions = [
    'theme' => __('badge_weight_theme', 'Themes'),
    'medium' => __('badge_weight_medium', 'Themes'),
    'semibold' => __('badge_weight_semibold', 'Themes'),
    'bold' => __('badge_weight_bold', 'Themes'),
];

$typographyFamilyOptions = [
    'theme' => __('typography_family_theme', 'Themes'),
    'system' => __('typography_family_system', 'Themes'),
    'sans' => __('typography_family_sans', 'Themes'),
    'geometric' => __('typography_family_geometric', 'Themes'),
    'editorial' => __('typography_family_editorial', 'Themes'),
];

$typographyScaleOptions = [
    'theme' => __('typography_scale_theme', 'Themes'),
    'compact' => __('typography_scale_compact', 'Themes'),
    'balanced' => __('typography_scale_balanced', 'Themes'),
    'comfortable' => __('typography_scale_comfortable', 'Themes'),
];

$typographyHeadingWeightOptions = [
    'theme' => __('typography_heading_weight_theme', 'Themes'),
    'semibold' => __('typography_heading_weight_semibold', 'Themes'),
    'bold' => __('typography_heading_weight_bold', 'Themes'),
    'black' => __('typography_heading_weight_black', 'Themes'),
];

$defaultPreviewHeadline = __('preview_typography_title', 'Themes');
$defaultPreviewText = __('preview_sample_text', 'Themes');
$previewBoxAttributes = sprintf(
    'data-theme-type="%s" data-theme-name="%s" data-default-button-preset="%s" data-default-button-shape="%s" data-default-button-weight="%s" data-default-badge-appearance="%s" data-default-badge-shape="%s" data-default-badge-weight="%s" data-default-typography-body-family="%s" data-default-typography-heading-family="%s" data-default-typography-scale="%s" data-default-typography-heading-weight="%s"',
    e($type),
    e($name),
    e((string) ($buttonDefaults['style'] ?? 'classic')),
    e((string) ($buttonDefaults['shape'] ?? 'rounded')),
    e((string) ($buttonDefaults['weight'] ?? 'medium')),
    e((string) ($badgeDefaults['style'] ?? 'soft')),
    e((string) ($badgeDefaults['shape'] ?? 'rounded')),
    e((string) ($badgeDefaults['weight'] ?? 'medium')),
    e((string) ($typographyDefaults['body_family'] ?? 'sans')),
    e((string) ($typographyDefaults['heading_family'] ?? 'sans')),
    e((string) ($typographyDefaults['scale'] ?? 'balanced')),
    e((string) ($typographyDefaults['heading_weight'] ?? 'semibold'))
);
?>

<link rel="stylesheet" href="<?= e($themesCssHref) ?>">

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle) ?> - <?= e((string) ($displayName ?? ucfirst($name))) ?></h1>
    </div>
    <div class="page-header-actions">
        <a href="<?= url('/admin/themes') ?>" class="btn btn-secondary"><?= __('back', 'Core') ?></a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-palette"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('themes_help_badge', 'Themes') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('themes_help_title', 'Themes') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('themes_help_intro', 'Themes') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('colors', 'Themes') ?></li>
            <li><?= __('custom_css', 'Themes') ?></li>
            <li><?= __('preview', 'Themes') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/themes') ?>" class="btn btn-primary"><?= __('themes', 'Themes') ?></a>
        </div>
    </div>
</div>

<form method="POST" action="<?= url("/admin/themes/{$type}/{$name}/customize") ?>">
    <?= csrf_field() ?>

    <div class="theme-customize-grid">
        <div class="theme-customize-column">
            <div class="card">
                <h3 class="card-title theme-card-title"><?= __('colors', 'Themes') ?></h3>
                <?php if ($supportsDualModeCustomization): ?>
                    <div class="theme-mode-tabs" data-theme-mode-tabs role="tablist" aria-label="<?= e(__('theme_mode_tabs_aria', 'Themes')) ?>">
                        <button type="button" class="theme-mode-tab-btn is-active" data-theme-mode-tab data-theme-mode="dark" role="tab" aria-selected="true">
                            <span class="theme-mode-tab-icon" aria-hidden="true"><i class="fas fa-moon"></i></span>
                            <span class="theme-mode-tab-label"><?= __('theme_mode_dark', 'Themes') ?></span>
                        </button>
                        <button type="button" class="theme-mode-tab-btn" data-theme-mode-tab data-theme-mode="light" role="tab" aria-selected="false">
                            <span class="theme-mode-tab-icon" aria-hidden="true"><i class="fas fa-sun"></i></span>
                            <span class="theme-mode-tab-label"><?= __('theme_mode_light', 'Themes') ?></span>
                        </button>
                    </div>

                    <div class="theme-mode-panels">
                        <section class="theme-mode-panel is-active" data-theme-mode-panel="dark" role="tabpanel">
                            <div class="theme-color-grid">
                                <?php foreach ($darkColorFields as $field): ?>
                                    <div class="form-group">
                                        <label for="<?= e($field['key']) ?>" class="form-label"><?= e($field['label']) ?></label>
                                        <div class="theme-color-row">
                                            <input type="color" id="<?= e($field['key']) ?>" name="<?= e($field['key']) ?>" class="theme-color-input" data-theme-color-picker data-theme-color-scope="dark" data-theme-color-key="<?= e($field['key']) ?>" value="<?= e($field['value']) ?>">
                                            <input type="text" class="form-input" data-theme-color-text data-theme-color-scope="dark" data-theme-color-key="<?= e($field['key']) ?>" value="<?= e($field['value']) ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>

                        <section class="theme-mode-panel" data-theme-mode-panel="light" role="tabpanel" hidden>
                            <div class="theme-color-grid">
                                <?php foreach ($lightColorFields as $field): ?>
                                    <div class="form-group">
                                        <label for="<?= e($field['key']) ?>" class="form-label"><?= e($field['label']) ?></label>
                                        <div class="theme-color-row">
                                            <input type="color" id="<?= e($field['key']) ?>" name="<?= e($field['key']) ?>" class="theme-color-input" data-theme-color-picker data-theme-color-scope="light" data-theme-color-key="<?= e($field['token']) ?>" value="<?= e($field['value']) ?>">
                                            <input type="text" class="form-input" data-theme-color-text data-theme-color-scope="light" data-theme-color-key="<?= e($field['token']) ?>" value="<?= e($field['value']) ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                <?php else: ?>
                    <div class="theme-color-grid">
                        <?php foreach ($darkColorFields as $field): ?>
                            <div class="form-group">
                                <label for="<?= e($field['key']) ?>" class="form-label"><?= e($field['label']) ?></label>
                                <div class="theme-color-row">
                                    <input type="color" id="<?= e($field['key']) ?>" name="<?= e($field['key']) ?>" class="theme-color-input" data-theme-color-picker data-theme-color-scope="default" data-theme-color-key="<?= e($field['key']) ?>" value="<?= e($field['value']) ?>">
                                    <input type="text" class="form-input" data-theme-color-text data-theme-color-scope="default" data-theme-color-key="<?= e($field['key']) ?>" value="<?= e($field['value']) ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="theme-customize-column">
            <div class="card theme-custom-css-card">
                <h3 class="card-title theme-card-title"><?= __('custom_css', 'Themes') ?></h3>
                
                <div class="form-group">
                    <textarea id="custom_css" name="custom_css" class="form-input theme-custom-css" rows="15" data-no-editor><?= e($customCssValue) ?></textarea>
                    <p class="form-hint"><?= __('custom_css_hint', 'Themes') ?></p>
                </div>
            </div>

        </div>
    </div>

    <div class="card theme-customize-fullwidth">
        <h3 class="card-title theme-card-title"><?= __('theme_components_title', 'Themes') ?></h3>
        <p class="form-hint theme-section-hint"><?= __('theme_components_hint', 'Themes') ?></p>

        <div class="theme-launcher-grid">
            <button type="button" class="theme-launcher-btn" data-theme-components-open="buttons">
                <span class="theme-launcher-btn__icon" aria-hidden="true"><i class="fas fa-square"></i></span>
                <span class="theme-launcher-btn__copy">
                    <span class="theme-launcher-btn__title"><?= __('theme_buttons_title', 'Themes') ?></span>
                    <span class="theme-launcher-btn__hint"><?= __('theme_buttons_hint', 'Themes') ?></span>
                </span>
            </button>

            <button type="button" class="theme-launcher-btn" data-theme-components-open="badges">
                <span class="theme-launcher-btn__icon" aria-hidden="true"><i class="fas fa-tag"></i></span>
                <span class="theme-launcher-btn__copy">
                    <span class="theme-launcher-btn__title"><?= __('theme_badges_title', 'Themes') ?></span>
                    <span class="theme-launcher-btn__hint"><?= __('theme_badges_hint', 'Themes') ?></span>
                </span>
            </button>

            <button type="button" class="theme-launcher-btn" data-theme-components-open="typography">
                <span class="theme-launcher-btn__icon" aria-hidden="true"><i class="fas fa-font"></i></span>
                <span class="theme-launcher-btn__copy">
                    <span class="theme-launcher-btn__title"><?= __('theme_typography_title', 'Themes') ?></span>
                    <span class="theme-launcher-btn__hint"><?= __('theme_typography_hint', 'Themes') ?></span>
                </span>
            </button>
        </div>
    </div>

    <div class="modal-overlay is-initially-hidden" id="themeComponentsModal" data-theme-components-modal hidden>
        <div
            class="modal-container modal-lg theme-components-modal-container"
            data-theme-icons-endpoint="<?= e(url('/admin/menus/icons')) ?>"
            data-theme-icon-picker-title="<?= e(__('theme_icon_picker_title', 'Themes')) ?>"
            data-theme-icon-search-placeholder="<?= e(__('theme_icon_search_placeholder', 'Themes')) ?>"
            data-theme-icon-loading-label="<?= e(__('theme_icon_loading', 'Themes')) ?>"
            data-theme-icon-empty-label="<?= e(__('theme_icon_empty', 'Themes')) ?>"
            data-theme-icon-error-label="<?= e(__('theme_icon_error', 'Themes')) ?>"
        >
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-sliders-h"></i>
                    <?= __('theme_customizer_modal_title', 'Themes') ?>
                </h3>
                <button type="button" class="modal-close" data-theme-components-close aria-label="<?= e(__('close', 'Core')) ?>">&times;</button>
            </div>
            <div class="modal-body theme-components-modal-body fc-inspector-shell">
                <div class="theme-component-tabs settings-tabs" role="tablist" aria-label="<?= e(__('theme_customizer_tabs_aria', 'Themes')) ?>">
                    <button type="button" class="theme-component-tab settings-tab-btn is-active" data-theme-components-tab="buttons" role="tab" aria-selected="true">
                        <span class="theme-component-tab__icon settings-tab-icon" aria-hidden="true"><i class="fas fa-square"></i></span>
                        <span class="theme-component-tab__label settings-tab-label"><?= __('theme_buttons_title', 'Themes') ?></span>
                    </button>
                    <button type="button" class="theme-component-tab settings-tab-btn" data-theme-components-tab="badges" role="tab" aria-selected="false">
                        <span class="theme-component-tab__icon settings-tab-icon" aria-hidden="true"><i class="fas fa-tag"></i></span>
                        <span class="theme-component-tab__label settings-tab-label"><?= __('theme_badges_title', 'Themes') ?></span>
                    </button>
                    <button type="button" class="theme-component-tab settings-tab-btn" data-theme-components-tab="typography" role="tab" aria-selected="false">
                        <span class="theme-component-tab__icon settings-tab-icon" aria-hidden="true"><i class="fas fa-font"></i></span>
                        <span class="theme-component-tab__label settings-tab-label"><?= __('theme_typography_title', 'Themes') ?></span>
                    </button>
                </div>

                <div class="theme-component-panels settings-tab-panels">
                    <section class="theme-component-panel settings-tab-panel is-active" data-theme-components-panel="buttons" role="tabpanel">
                        <div class="fc-inspector-group theme-component-group">
                            <div class="fc-inspector-group-head">
                                <span class="fc-inspector-group-title"><?= __('theme_buttons_title', 'Themes') ?></span>
                            </div>
                            <div class="fc-inspector-group-body theme-component-group-body">
                                <section class="fc-inspector-section">
                                    <div class="fc-inspector-section-head">
                                        <h4 class="fc-inspector-section-title"><?= __('theme_customizer_controls_title', 'Themes') ?></h4>
                                        <p class="fc-inspector-section-help"><?= __('theme_buttons_hint', 'Themes') ?></p>
                                    </div>
                                    <div class="fc-inspector-section-body">
                                        <div class="theme-controls-grid">
                                            <div class="form-group">
                                                <label for="button_style" class="form-label"><?= __('button_style_label', 'Themes') ?></label>
                                                <select id="button_style" name="button_style" class="form-select" data-theme-button-control="style">
                                                    <?php foreach ($buttonStyleOptions as $value => $label): ?>
                                                        <option value="<?= e($value) ?>" <?= $currentButtonStyle === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="button_shape" class="form-label"><?= __('button_shape_label', 'Themes') ?></label>
                                                <select id="button_shape" name="button_shape" class="form-select" data-theme-button-control="shape">
                                                    <?php foreach ($buttonShapeOptions as $value => $label): ?>
                                                        <option value="<?= e($value) ?>" <?= $currentButtonShape === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="button_weight" class="form-label"><?= __('button_weight_label', 'Themes') ?></label>
                                                <select id="button_weight" name="button_weight" class="form-select" data-theme-button-control="weight">
                                                    <?php foreach ($buttonWeightOptions as $value => $label): ?>
                                                        <option value="<?= e($value) ?>" <?= $currentButtonWeight === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="fc-inspector-section">
                                    <div class="fc-inspector-section-head">
                                        <h4 class="fc-inspector-section-title"><?= __('preview', 'Themes') ?></h4>
                                    </div>
                                    <div class="fc-inspector-section-body">
                                        <div class="theme-component-preview-block">
                                            <div class="theme-preview-box theme-preview-box--buttons" data-theme-preview-box <?= $previewBoxAttributes ?>>
                                                <div class="theme-preview-surface theme-preview-surface--buttons">
                                                    <div class="theme-preview-copy">
                                                        <h5 class="theme-preview-headline" data-theme-preview-heading><?= e($defaultPreviewHeadline) ?></h5>
                                                        <div class="theme-preview-text" data-theme-preview-body><?= nl2br(e($defaultPreviewText)) ?></div>
                                                    </div>
                                                    <div class="theme-preview-actions">
                                                        <button type="button" class="btn btn-primary btn-sm"><?= __('primary_button', 'Themes') ?></button>
                                                        <button type="button" class="btn btn-secondary btn-sm"><?= __('secondary_button', 'Themes') ?></button>
                                                        <button type="button" class="btn btn-ghost btn-sm"><?= __('ghost_button', 'Themes') ?></button>
                                                        <button type="button" class="btn btn-outline btn-sm"><?= __('outline_button', 'Themes') ?></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </section>

                    <section class="theme-component-panel settings-tab-panel" data-theme-components-panel="badges" role="tabpanel" hidden>
                        <div class="fc-inspector-group theme-component-group">
                            <div class="fc-inspector-group-head">
                                <span class="fc-inspector-group-title"><?= __('theme_badges_title', 'Themes') ?></span>
                            </div>
                            <div class="fc-inspector-group-body theme-component-group-body">
                                <section class="fc-inspector-section">
                                    <div class="fc-inspector-section-head">
                                        <h4 class="fc-inspector-section-title"><?= __('theme_customizer_controls_title', 'Themes') ?></h4>
                                        <p class="fc-inspector-section-help"><?= __('theme_badges_hint', 'Themes') ?></p>
                                    </div>
                                    <div class="fc-inspector-section-body">
                                        <div class="theme-controls-grid theme-controls-grid--wide">
                                            <div class="form-group">
                                                <label for="badge_style" class="form-label"><?= __('badge_style_label', 'Themes') ?></label>
                                                <select id="badge_style" name="badge_style" class="form-select" data-theme-badge-control="style">
                                                    <?php foreach ($badgeStyleOptions as $value => $label): ?>
                                                        <option value="<?= e($value) ?>" <?= $currentBadgeStyle === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="badge_shape" class="form-label"><?= __('badge_shape_label', 'Themes') ?></label>
                                                <select id="badge_shape" name="badge_shape" class="form-select" data-theme-badge-control="shape">
                                                    <?php foreach ($badgeShapeOptions as $value => $label): ?>
                                                        <option value="<?= e($value) ?>" <?= $currentBadgeShape === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="badge_weight" class="form-label"><?= __('badge_weight_label', 'Themes') ?></label>
                                                <select id="badge_weight" name="badge_weight" class="form-select" data-theme-badge-control="weight">
                                                    <?php foreach ($badgeWeightOptions as $value => $label): ?>
                                                        <option value="<?= e($value) ?>" <?= $currentBadgeWeight === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="fc-inspector-section">
                                    <div class="fc-inspector-section-head">
                                        <h4 class="fc-inspector-section-title"><?= __('preview', 'Themes') ?></h4>
                                    </div>
                                    <div class="fc-inspector-section-body">
                                        <div class="theme-component-preview-block">
                                            <div class="theme-preview-box theme-preview-box--badges" data-theme-preview-box <?= $previewBoxAttributes ?>>
                                                <div class="theme-preview-surface theme-preview-surface--badges">
                                                    <div class="theme-preview-badges">
                                                        <span class="theme-preview-badge"><?= __('preview_badge_primary', 'Themes') ?></span>
                                                        <span class="theme-preview-badge theme-preview-badge-alt"><?= __('preview_badge_secondary', 'Themes') ?></span>
                                                    </div>
                                                    <div class="theme-preview-text" data-theme-preview-body><?= nl2br(e($defaultPreviewText)) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </section>

                    <section class="theme-component-panel settings-tab-panel" data-theme-components-panel="typography" role="tabpanel" hidden>
                        <div class="fc-inspector-group theme-component-group">
                            <div class="fc-inspector-group-head">
                                <span class="fc-inspector-group-title"><?= __('theme_typography_title', 'Themes') ?></span>
                            </div>
                            <div class="fc-inspector-group-body theme-component-group-body">
                                <section class="fc-inspector-section">
                                    <div class="fc-inspector-section-head">
                                        <h4 class="fc-inspector-section-title"><?= __('theme_customizer_controls_title', 'Themes') ?></h4>
                                        <p class="fc-inspector-section-help"><?= __('theme_typography_hint', 'Themes') ?></p>
                                    </div>
                                    <div class="fc-inspector-section-body">
                                        <div class="theme-controls-grid theme-controls-grid--wide">
                                            <div class="form-group">
                                                <label for="typography_body_family" class="form-label"><?= __('typography_body_family_label', 'Themes') ?></label>
                                                <select id="typography_body_family" name="typography_body_family" class="form-select" data-theme-typography-control="body_family">
                                                    <?php foreach ($typographyFamilyOptions as $value => $label): ?>
                                                        <option value="<?= e($value) ?>" <?= $currentTypographyBodyFamily === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="typography_heading_family" class="form-label"><?= __('typography_heading_family_label', 'Themes') ?></label>
                                                <select id="typography_heading_family" name="typography_heading_family" class="form-select" data-theme-typography-control="heading_family">
                                                    <?php foreach ($typographyFamilyOptions as $value => $label): ?>
                                                        <option value="<?= e($value) ?>" <?= $currentTypographyHeadingFamily === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="typography_scale" class="form-label"><?= __('typography_scale_label', 'Themes') ?></label>
                                                <select id="typography_scale" name="typography_scale" class="form-select" data-theme-typography-control="scale">
                                                    <?php foreach ($typographyScaleOptions as $value => $label): ?>
                                                        <option value="<?= e($value) ?>" <?= $currentTypographyScale === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label for="typography_heading_weight" class="form-label"><?= __('typography_heading_weight_label', 'Themes') ?></label>
                                                <select id="typography_heading_weight" name="typography_heading_weight" class="form-select" data-theme-typography-control="heading_weight">
                                                    <?php foreach ($typographyHeadingWeightOptions as $value => $label): ?>
                                                        <option value="<?= e($value) ?>" <?= $currentTypographyHeadingWeight === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="fc-inspector-section">
                                    <div class="fc-inspector-section-head">
                                        <h4 class="fc-inspector-section-title"><?= __('preview', 'Themes') ?></h4>
                                    </div>
                                    <div class="fc-inspector-section-body">
                                        <div class="theme-component-preview-block">
                                            <div class="theme-preview-box theme-preview-box--typography" data-theme-preview-box <?= $previewBoxAttributes ?>>
                                                <div class="theme-preview-surface theme-preview-surface--typography">
                                                    <span class="theme-preview-badge"><?= __('preview_badge_primary', 'Themes') ?></span>
                                                    <h4 class="theme-preview-headline" data-theme-preview-heading><?= e($defaultPreviewHeadline) ?></h4>
                                                    <div class="theme-preview-text" data-theme-preview-body><?= nl2br(e($defaultPreviewText)) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-theme-components-close><?= __('close', 'Core') ?></button>
                <button type="submit" class="btn btn-primary"><?= __('save', 'Core') ?></button>
            </div>
        </div>
    </div>

    <div class="theme-customize-actions">
        <button type="submit" class="btn btn-primary"><?= __('save', 'Core') ?></button>
        <button type="button" class="btn btn-secondary" data-theme-reset-open><?= __('reset_defaults', 'Themes') ?></button>
        <a href="<?= url('/admin/themes') ?>" class="btn btn-secondary"><?= __('cancel', 'Core') ?></a>
    </div>
</form>

<div class="modal-overlay is-initially-hidden" id="themeResetModal" data-theme-reset-modal hidden>
    <div class="modal-container modal-sm">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-exclamation-triangle modal-icon-danger"></i>
                <?= __('reset_defaults', 'Themes') ?>
            </h3>
            <button type="button" class="modal-close" data-theme-reset-close aria-label="<?= e(__('close', 'Core')) ?>">&times;</button>
        </div>
        <div class="modal-body modal-body-centered">
            <p><?= __('theme_reset_confirm_message', 'Themes') ?></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-theme-reset-close><?= __('cancel', 'Core') ?></button>
            <form method="POST" action="<?= url("/admin/themes/{$type}/{$name}/customize/reset") ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger"><?= __('reset_defaults', 'Themes') ?></button>
            </form>
        </div>
    </div>
</div>

<script src="<?= e($themesJsSrc) ?>"></script>
