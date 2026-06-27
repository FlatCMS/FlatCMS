<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

hook_register('admin.guided_tour.module_tours', static function (): array {
    return [
        'themes' => [
            'routes' => ['admin/themes'],
            'steps' => [
                guided_tour_step('.themes-toolbar', __('themes_help_title', 'Themes'), __('themes_help_step_activate', 'Themes'), 'bottom'),
                guided_tour_step('.themes-filter-group [data-theme-status]', __('themes_filter_active', 'Themes'), __('themes_help_step_activate', 'Themes'), 'bottom'),
                guided_tour_step('#themeSearchInput, .themes-filter-group-right', __('themes_filter_search', 'Themes'), __('themes_help_step_activate', 'Themes'), 'top'),
                guided_tour_step('#themeTypeFilter, #themeCategoryFilter, #themeColorFilter, #themePriceFilter', __('themes_filter_type', 'Themes'), __('themes_help_step_activate', 'Themes'), 'top'),
                guided_tour_step('.theme-installer-card', __('themes_installer_title', 'Themes'), __('themes_help_step_install', 'Themes'), 'top'),
                guided_tour_step('.module-installer-form', __('themes_installer_action', 'Themes'), __('themes_installer_hint', 'Themes'), 'top'),
                guided_tour_step('.themes-grid', __('themes', 'Themes'), __('themes_help_intro', 'Themes'), 'top'),
                guided_tour_step('.theme-card', __('themes_help_title', 'Themes'), __('themes_help_step_cleanup', 'Themes'), 'top'),
                guided_tour_step('.theme-actions', __('activate', 'Themes'), __('themes_help_step_cleanup', 'Themes'), 'left'),
                guided_tour_step('.theme-customize-grid', __('customize_theme', 'Themes'), __('theme_components_hint', 'Themes'), 'top'),
                guided_tour_step('.theme-color-row', __('colors', 'Themes'), __('theme_customizer_controls_title', 'Themes'), 'bottom'),
                guided_tour_step('#custom_css', __('custom_css', 'Themes'), __('custom_css_hint', 'Themes'), 'top'),
                guided_tour_step('#preview-box', __('preview', 'Themes'), __('preview_sample_text', 'Themes'), 'top'),
                guided_tour_step('.theme-customize-actions', __('customize_theme', 'Themes'), __('theme_components_hint', 'Themes'), 'top'),
            ],
        ],
    ];
}, ['module' => 'Themes', 'priority' => 10]);
