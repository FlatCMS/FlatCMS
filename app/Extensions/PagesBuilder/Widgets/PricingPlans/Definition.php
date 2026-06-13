<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\PricingPlans;

use App\Extensions\PagesBuilder\Support\AbstractWidgetDefinition;

final class Definition extends AbstractWidgetDefinition
{
    public static function key(): string
    {
        return 'pricing_plans';
    }

    public static function definition(): array
    {
        return
        array (
          'type' => 'pricing_plans',
          'label' =>
          array (
            '__label' => true,
            'key' => 'pricing_plans_widget_label',
            'fallback' => '',
          ),
          'icon' => 'fas fa-tags',
          'category' => 'content',
          'i18n_module' => 'PricingPlans',
          'render' => 'render.php',
          'preview_handler' => 'pricing_plans',
          'assets' =>
          array (
            'css' =>
            array (
              0 => 'css/pricing-plans.css',
            ),
            'js' =>
            array (
              0 => 'js/pricing-plans.js',
            ),
            'preview_css' =>
            array (
              0 => 'css/pricing-plans.css',
            ),
            'preview_js' =>
            array (
              0 => 'js/pricing-plans.js',
              1 => 'js/pricing-plans-preview.js',
            ),
          ),
          'defaults' =>
          array (
            'title' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_default_title',
              'fallback' => '',
            ),
            'subtitle' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_default_subtitle',
              'fallback' => '',
            ),
            'popularBadgeLabel' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_badge_popular',
              'fallback' => '',
            ),
            'billingMonthlyLabel' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_toggle_monthly',
              'fallback' => '',
            ),
            'billingYearlyLabel' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_toggle_yearly',
              'fallback' => '',
            ),
            'billingSavingsLabel' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_toggle_savings',
              'fallback' => '',
            ),
            'featuresHeading' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_features_heading',
              'fallback' => '',
            ),
            'monthlyIntervalLabel' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_price_interval_monthly',
              'fallback' => '',
            ),
            'yearlyIntervalLabel' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_price_interval_yearly',
              'fallback' => '',
            ),
            'planNames' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_default_plan_names',
              'fallback' => '',
            ),
            'planPrices' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_default_plan_prices',
              'fallback' => '',
            ),
            'planPeriods' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_default_plan_yearly_prices',
              'fallback' => '',
            ),
            'planDescriptions' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_default_plan_descriptions',
              'fallback' => '',
            ),
            'planFeatures' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_default_plan_features',
              'fallback' => '',
            ),
            'planBadges' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_default_plan_badges',
              'fallback' => '',
            ),
            'planIcons' => '',
            'featuredPlans' => 'off
                on
                off',
            'ctaEnableds' => 'on
                on
                on',
            'ctaLabels' =>
            array (
              '__label' => true,
              'key' => 'pricing_plans_default_cta_labels',
              'fallback' => '',
            ),
            'ctaLinks' => '#
                #
                #',
            'ctaTargets' => '_self
                _self
                _self',
            'ctaVariants' => 'ghost
                primary
                ghost',
            'ctaAligns' => 'center
                center
                center',
            'columns' => 3,
            'variant' => 'subtle',
            'align' => 'center',
            'showHeader' => 'on',
            'showBadges' => 'on',
            'showDescriptions' => 'on',
            'showFeatures' => 'on',
            'showPopular' => 'on',
            'useCustomDesign' => '',
            'designSurfaceColor' => '',
            'designTextColor' => '',
            'designBorderStyle' => 'inherit',
            'designBorderWidth' => 1,
            'designBorderColor' => '',
            'designRadius' => 24,
            'designShadow' => 'inherit',
          ),
          'fields' =>
          array (
            0 =>
            array (
              'key' => 'title',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_title',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            1 =>
            array (
              'key' => 'subtitle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_subtitle',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            2 =>
            array (
              'key' => 'billingMonthlyLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_billing_monthly_label',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            3 =>
            array (
              'key' => 'billingYearlyLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_billing_yearly_label',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            4 =>
            array (
              'key' => 'billingSavingsLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_billing_savings_label',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            5 =>
            array (
              'key' => 'popularBadgeLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_popular_badge_label',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            6 =>
            array (
              'key' => 'featuresHeading',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_features_heading',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            7 =>
            array (
              'key' => 'monthlyIntervalLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_price_interval_monthly',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            8 =>
            array (
              'key' => 'yearlyIntervalLabel',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_price_interval_yearly',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
            ),
            9 =>
            array (
              'key' => 'planNames',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_plan_names',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_plan_name_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'min' => 1,
                'max' => 8,
              ),
            ),
            10 =>
            array (
              'key' => 'planPrices',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_plan_prices',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_plan_price_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'min' => 1,
                'max' => 8,
              ),
            ),
            11 =>
            array (
              'key' => 'planYearlyPrices',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_plan_yearly_prices',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_plan_yearly_price_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            12 =>
            array (
              'key' => 'planDescriptions',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_plan_descriptions',
                'fallback' => '',
              ),
              'type' => 'textarea',
              'group' => 'content',
              'rows' => 3,
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_plan_description_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            13 =>
            array (
              'key' => 'planFeatures',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_plan_features',
                'fallback' => '',
              ),
              'type' => 'textarea',
              'group' => 'content',
              'rows' => 8,
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_plan_feature_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ---
                ',
                'min' => 1,
                'max' => 8,
              ),
            ),
            14 =>
            array (
              'key' => 'planBadges',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_plan_badges',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'content',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_plan_badge_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            15 =>
            array (
              'key' => 'planIcons',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_plan_icons',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'media',
              'iconPicker' => true,
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_plan_icon_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            16 =>
            array (
              'key' => 'ctaEnableds',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_cta_enabled',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'navigation',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_cta_enabled_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            17 =>
            array (
              'key' => 'ctaLabels',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_cta_labels',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'navigation',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_cta_label_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            18 =>
            array (
              'key' => 'ctaLinks',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_cta_links',
                'fallback' => '',
              ),
              'type' => 'url',
              'group' => 'navigation',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_cta_link_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            19 =>
            array (
              'key' => 'ctaTargets',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_cta_targets',
                'fallback' => '',
              ),
              'type' => 'select',
              'group' => 'navigation',
              'options' =>
              array (
                0 => '_self',
                1 => '_blank',
              ),
              'optionLabels' =>
              array (
                '_self' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_target_self',
                  'fallback' => '',
                ),
                '_blank' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_target_blank',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_cta_target_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            20 =>
            array (
              'key' => 'ctaVariants',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_cta_variants',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'choice',
              'group' => 'navigation',
              'options' =>
              array (
                0 => 'primary',
                1 => 'secondary',
                2 => 'ghost',
              ),
              'optionLabels' =>
              array (
                'primary' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_button_variant_primary',
                  'fallback' => '',
                ),
                'secondary' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_button_variant_secondary',
                  'fallback' => '',
                ),
                'ghost' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_button_variant_ghost',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_cta_variant_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            21 =>
            array (
              'key' => 'ctaAligns',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_cta_align',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'align',
              'group' => 'navigation',
              'options' =>
              array (
                0 => 'left',
                1 => 'center',
                2 => 'right',
              ),
              'optionLabels' =>
              array (
                'left' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_align_right',
                  'fallback' => '',
                ),
              ),
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_cta_align_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            22 =>
            array (
              'key' => 'featuredPlans',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_featured_plans',
                'fallback' => '',
              ),
              'type' => 'text',
              'group' => 'layout',
              'repeater' =>
              array (
                'enabled' => true,
                'itemLabel' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_field_featured_plan_item',
                  'fallback' => '',
                ),
                'delimiter' => '
                ',
                'max' => 8,
              ),
            ),
            23 =>
            array (
              'key' => 'columns',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_columns',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'layout',
              'min' => 1,
              'max' => 4,
              'step' => 1,
            ),
            24 =>
            array (
              'key' => 'variant',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_variant',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'choice',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'subtle',
                1 => 'strong',
                2 => 'dashed',
              ),
              'optionLabels' =>
              array (
                'subtle' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_variant_subtle',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_variant_strong',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_variant_dashed',
                  'fallback' => '',
                ),
              ),
            ),
            25 =>
            array (
              'key' => 'align',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_align',
                'fallback' => '',
              ),
              'type' => 'select',
              'control' => 'align',
              'group' => 'layout',
              'options' =>
              array (
                0 => 'left',
                1 => 'center',
                2 => 'right',
              ),
              'optionLabels' =>
              array (
                'left' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_align_left',
                  'fallback' => '',
                ),
                'center' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_align_center',
                  'fallback' => '',
                ),
                'right' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_align_right',
                  'fallback' => '',
                ),
              ),
            ),
            26 =>
            array (
              'key' => 'showHeader',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_show_header',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            27 =>
            array (
              'key' => 'showBadges',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_show_badges',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            28 =>
            array (
              'key' => 'showDescriptions',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_show_descriptions',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            29 =>
            array (
              'key' => 'showFeatures',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_show_features',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            30 =>
            array (
              'key' => 'showPopular',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_show_popular',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'layout',
            ),
            31 =>
            array (
              'key' => 'useCustomDesign',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_use_custom_design',
                'fallback' => '',
              ),
              'type' => 'checkbox',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_section_surface',
                'fallback' => '',
              ),
              'sectionHelp' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_section_surface_help',
                'fallback' => '',
              ),
            ),
            32 =>
            array (
              'key' => 'designSurfaceColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_design_surface_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            33 =>
            array (
              'key' => 'designTextColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_design_text_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            34 =>
            array (
              'key' => 'designBorderStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_design_border_style',
                'fallback' => '',
              ),
              'type' => 'select',
              'options' =>
              array (
                0 => 'inherit',
                1 => 'none',
                2 => 'solid',
                3 => 'dashed',
                4 => 'dotted',
              ),
              'optionLabels' =>
              array (
                'inherit' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_design_border_style_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_design_border_style_none',
                  'fallback' => '',
                ),
                'solid' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_design_border_style_solid',
                  'fallback' => '',
                ),
                'dashed' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_design_border_style_dashed',
                  'fallback' => '',
                ),
                'dotted' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_design_border_style_dotted',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            35 =>
            array (
              'key' => 'designBorderWidth',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_design_border_width',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_section_surface',
                'fallback' => '',
              ),
              'min' => 0,
              'max' => 8,
              'step' => 1,
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            36 =>
            array (
              'key' => 'designBorderColor',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_design_border_color',
                'fallback' => '',
              ),
              'type' => 'color',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            37 =>
            array (
              'key' => 'designRadius',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_design_radius',
                'fallback' => '',
              ),
              'type' => 'number',
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_section_surface',
                'fallback' => '',
              ),
              'min' => 0,
              'max' => 48,
              'step' => 1,
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            38 =>
            array (
              'key' => 'designShadow',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_design_shadow',
                'fallback' => '',
              ),
              'type' => 'select',
              'options' =>
              array (
                0 => 'inherit',
                1 => 'none',
                2 => 'soft',
                3 => 'medium',
                4 => 'strong',
              ),
              'optionLabels' =>
              array (
                'inherit' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_design_shadow_inherit',
                  'fallback' => '',
                ),
                'none' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_design_shadow_none',
                  'fallback' => '',
                ),
                'soft' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_design_shadow_soft',
                  'fallback' => '',
                ),
                'medium' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_design_shadow_medium',
                  'fallback' => '',
                ),
                'strong' =>
                array (
                  '__label' => true,
                  'key' => 'pricing_plans_option_design_shadow_strong',
                  'fallback' => '',
                ),
              ),
              'group' => 'design',
              'section' => 'surface',
              'sectionLabel' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_section_surface',
                'fallback' => '',
              ),
              'condition' =>
              array (
                'field' => 'useCustomDesign',
                'operator' => 'equals',
                'value' => 'on',
              ),
            ),
            39 =>
            array (
              'key' => 'titleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'titleStyle',
            ),
            40 =>
            array (
              'key' => 'subtitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_subtitle_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'subtitleStyle',
            ),
            41 =>
            array (
              'key' => 'planTitleTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_plan_title_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'planTitleStyle',
            ),
            42 =>
            array (
              'key' => 'priceTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_price_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'priceStyle',
            ),
            43 =>
            array (
              'key' => 'periodTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_period_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'periodStyle',
            ),
            44 =>
            array (
              'key' => 'descriptionTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_description_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'descriptionStyle',
            ),
            45 =>
            array (
              'key' => 'featureTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_feature_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'featureStyle',
            ),
            46 =>
            array (
              'key' => 'badgeTextStyle',
              'label' =>
              array (
                '__label' => true,
                'key' => 'pricing_plans_field_badge_text_style',
                'fallback' => '',
              ),
              'type' => 'text_style',
              'group' => 'advanced',
              'stylePrefix' => 'badgeStyle',
            ),
          ),
        );
    }
}
