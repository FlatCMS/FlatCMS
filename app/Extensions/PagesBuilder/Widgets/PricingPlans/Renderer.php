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

use App\Extensions\PagesBuilder\Services\PageBuilderWidgetLocaleService;
use App\Extensions\PagesBuilder\Support\AbstractWidgetRenderer;

final class Renderer extends AbstractWidgetRenderer
{
    protected static function renderer(): callable
    {
        return static function (array $settings, array $context): array {
            $helpers = is_array($context['helpers'] ?? null) ? $context['helpers'] : [];
            $escape = $helpers['escape'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $escapeAttr = $helpers['escape_attr'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $translate = static fn(string $key, string $fallback = ''): string => PageBuilderWidgetLocaleService::translate('PricingPlans', $key, $fallback);

            $parseRepeater = static function (mixed $raw, string $delimiter = "\n"): array {
                $value = trim((string) $raw);
                if ($value === '') {
                    return [];
                }

                if (str_starts_with($value, '[')) {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        return array_map(static fn(mixed $item): string => trim((string) $item), $decoded);
                    }
                }

                if ($delimiter !== '' && str_contains($value, $delimiter)) {
                    $items = explode($delimiter, $value);
                } else {
                    $items = preg_split('/\r\n|\r|\n/', $value) ?: [];
                }

                $items = array_map(static fn(mixed $item): string => trim((string) $item), $items);
                while ($items !== [] && trim((string) $items[count($items) - 1]) === '') {
                    array_pop($items);
                }

                return $items;
            };

            $parseFeatureGroups = static function (mixed $raw) use ($parseRepeater): array {
                return $parseRepeater($raw, "\n---\n");
            };

            $parseFeatureItems = static function (mixed $raw) use ($parseRepeater): array {
                $items = $parseRepeater($raw, "\n");
                $normalized = [];
                foreach ($items as $item) {
                    $clean = ltrim($item, "-*• \t");
                    if ($clean === '') {
                        continue;
                    }
                    $normalized[] = $clean;
                }
                return $normalized;
            };

            $normalizeVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['subtle', 'strong', 'dashed'], true) ? $value : 'subtle';
            };

            $normalizeTarget = static function (mixed $raw, string $fallback = '_self'): string {
                $value = trim((string) $raw);
                if (in_array($value, ['_self', '_blank'], true)) {
                    return $value;
                }
                return in_array($fallback, ['_self', '_blank'], true) ? $fallback : '_self';
            };

            $normalizeButtonVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['primary', 'secondary'], true) ? $value : 'ghost';
            };

            $hasDigitValue = static function (string $raw): bool {
                return preg_match('/\d/u', $raw) === 1;
            };

            $resolveTextStyle = static function (array $source, string $prefix, string $fallbackAlign): array {
                $keyPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'textStyle';
                $iconPosition = strtolower(trim((string) ($source[$keyPrefix . 'IconPosition'] ?? 'start')));

                return [
                    'align' => self::normalizeAlign((string) ($source[$keyPrefix . 'Align'] ?? ''), $fallbackAlign),
                    'font' => self::normalizeTextStyleFont((string) ($source[$keyPrefix . 'Font'] ?? 'inherit')),
                    'size' => self::normalizeTextStyleSize((string) ($source[$keyPrefix . 'Size'] ?? 'inherit')),
                    'bold' => self::normalizeToggle($source[$keyPrefix . 'Bold'] ?? false),
                    'italic' => self::normalizeToggle($source[$keyPrefix . 'Italic'] ?? false),
                    'underline' => self::normalizeToggle($source[$keyPrefix . 'Underline'] ?? false),
                    'color' => self::normalizeColor((string) ($source[$keyPrefix . 'Color'] ?? '')),
                    'list' => self::normalizeTextStyleList((string) ($source[$keyPrefix . 'List'] ?? 'none')),
                    'icon' => self::sanitizeIconClass((string) ($source[$keyPrefix . 'Icon'] ?? '')),
                    'iconPosition' => in_array($iconPosition, ['start', 'end'], true) ? $iconPosition : 'start',
                ];
            };

            $injectTextIcon = static function (string $content, array $style) use ($escapeAttr): string {
                $icon = trim((string) ($style['icon'] ?? ''));
                if ($icon === '') {
                    return $content;
                }
                $position = strtolower(trim((string) ($style['iconPosition'] ?? 'start')));
                if (!in_array($position, ['start', 'end'], true)) {
                    $position = 'start';
                }

                $iconHtml = '<i class="' . $escapeAttr($icon) . ' pb-styled-text-icon pb-styled-text-icon-' . $escapeAttr($position) . '" aria-hidden="true"></i>';
                return $position === 'end' ? $content . $iconHtml : $iconHtml . $content;
            };

            $injectTextListMarker = static function (string $content, array $style) use ($escape): string {
                $listStyle = self::normalizeTextStyleList((string) ($style['list'] ?? 'none'));
                if ($listStyle === 'none') {
                    return $content;
                }

                $glyph = match ($listStyle) {
                    'circle' => '∘',
                    'square' => '▪',
                    default => '•',
                };

                return '<span class="pb-styled-text-list-marker pb-styled-text-list-marker-' . $escape($listStyle) . '" aria-hidden="true">' . $escape($glyph) . '</span>' . $content;
            };

            $renderStyledText = static function (string $text, string $tag, string $className, array $style) use (
                $escape,
                $escapeAttr,
                $injectTextIcon,
                $injectTextListMarker
            ): string {
                $value = trim($text);
                if ($value === '') {
                    return '';
                }

                $content = '<span class="pb-styled-text-content">' . $escape($value) . '</span>';
                $decorated = $injectTextListMarker($injectTextIcon($content, $style), $style);
                return '<' . $tag . ' class="' . $escapeAttr($className) . '">' . $decorated . '</' . $tag . '>';
            };

            $buildTextStyleRules = static function (string $safeId, string $selector, array $style) use (
                $escapeAttr,
            ): array {
                if ($safeId === '') {
                    return [];
                }

                $scopedSelector = self::blockSelector($safeId, $selector);
                $rules = ['text-align:' . $escapeAttr(self::normalizeAlign((string) ($style['align'] ?? 'left'))) . ';'];

                $color = trim((string) ($style['color'] ?? ''));
                if ($color !== '') {
                    $rules[] = 'color:' . $escapeAttr($color) . ';';
                }

                $fontRule = self::widgetTextFontRule((string) ($style['font'] ?? 'inherit'));
                if ($fontRule !== '') {
                    $rules[] = $fontRule;
                }

                $sizeRule = self::widgetTextSizeRule((string) ($style['size'] ?? 'inherit'));
                if ($sizeRule !== '') {
                    $rules[] = $sizeRule;
                }

                $cssRules = [$scopedSelector . '{' . implode('', $rules) . '}'];
                $contentRules = [];
                if (self::normalizeToggle($style['bold'] ?? false)) {
                    $contentRules[] = 'font-weight:700;';
                }
                if (self::normalizeToggle($style['italic'] ?? false)) {
                    $contentRules[] = 'font-style:italic;';
                }
                if (self::normalizeToggle($style['underline'] ?? false)) {
                    $contentRules[] = 'text-decoration:underline;';
                }
                if ($contentRules !== []) {
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-content{' . implode('', $contentRules) . '}';
                }

                $listStyle = self::normalizeTextStyleList((string) ($style['list'] ?? 'none'));
                if ($listStyle !== 'none') {
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-list-marker{display:inline-block;margin-right:0.45em;font-weight:700;line-height:1;}';
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-list-marker-' . $escapeAttr($listStyle) . '{color:currentColor;}';
                }

                $icon = trim((string) ($style['icon'] ?? ''));
                if ($icon !== '') {
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-icon{display:inline-flex;align-items:center;line-height:1;}';
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-icon-start{margin-right:0.5em;}';
                    $cssRules[] = $scopedSelector . ' .pb-styled-text-icon-end{margin-left:0.5em;}';
                }

                return $cssRules;
            };

            $buildFlexAlignRules = static function (string $safeId, string $selector, string $align) use (
                $escapeAttr,
            ): array {
                if ($safeId === '') {
                    return [];
                }

                $normalized = self::normalizeAlign($align, 'left');
                $justify = match ($normalized) {
                    'center' => 'center',
                    'right' => 'flex-end',
                    default => 'flex-start',
                };

                return [
                    self::blockSelector($safeId, $selector) . '{justify-content:' . $escapeAttr($justify) . ';}',
                ];
            };

            $buildFeatureListAlignRules = static function (string $safeId, array $style) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $align = self::normalizeAlign((string) ($style['align'] ?? 'left'), 'left');
                $direction = $align === 'right' ? 'row-reverse' : 'row';
                $justifySelf = match ($align) {
                    'center' => 'center',
                    'right' => 'end',
                    default => 'start',
                };
                $textAlign = $align === 'center' ? 'left' : $align;
                $featureListJustify = $align === 'center' ? 'stretch' : $justifySelf;
                $featureItemJustify = $align === 'center' ? 'stretch' : $justifySelf;
                $featureRules = [
                    'flex-direction:' . $escapeAttr($direction) . ';',
                    'justify-self:' . $escapeAttr($featureItemJustify) . ';',
                ];
                $featureTextRules = ['text-align:' . $escapeAttr($textAlign) . ';'];
                $featureListRules = ['justify-items:' . $escapeAttr($featureListJustify) . ';'];

                if ($align === 'center') {
                    $featureListRules[] = 'justify-self:center;';
                    $featureListRules[] = 'width:fit-content;';
                    $featureListRules[] = 'max-width:100%;';
                    $featureListRules[] = 'text-align:left;';
                    $featureRules[] = 'width:100%;';
                    $featureRules[] = 'max-width:100%;';
                    $featureRules[] = 'text-align:left;';
                    $featureTextRules[] = 'flex:1 1 auto;';
                    $featureTextRules[] = 'width:100%;';
                }

                return [
                    self::blockSelector($safeId, '.pb-pricing-plan-features') . '{' . implode('', $featureListRules) . '}',
                    self::blockSelector($safeId, '.pb-pricing-plan-feature') . '{' . implode('', $featureRules) . '}',
                    self::blockSelector($safeId, '.pb-pricing-plan-feature-text') . '{' . implode('', $featureTextRules) . '}',
                ];
            };

            $title = trim((string) ($settings['title'] ?? $translate('pricing_plans_default_title')));
            $subtitle = trim((string) ($settings['subtitle'] ?? $translate('pricing_plans_default_subtitle')));

            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(48, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $planNames = $parseRepeater($settings['planNames'] ?? $translate('pricing_plans_default_plan_names'));
            $planMonthlyPrices = $parseRepeater($settings['planPrices'] ?? $translate('pricing_plans_default_plan_prices'));
            $legacyPlanYearlyPrices = $parseRepeater($settings['planPeriods'] ?? '');
            $planYearlySource = array_key_exists('planYearlyPrices', $settings)
                ? (string) ($settings['planYearlyPrices'] ?? '')
                : (array_key_exists('planPeriods', $settings)
                    ? (string) ($settings['planPeriods'] ?? '')
                    : $translate('pricing_plans_default_plan_yearly_prices'));
            $planYearlyPrices = $parseRepeater($planYearlySource);
            $planDescriptions = $parseRepeater($settings['planDescriptions'] ?? $translate('pricing_plans_default_plan_descriptions'));
            $planFeatures = $parseFeatureGroups($settings['planFeatures'] ?? $translate('pricing_plans_default_plan_features'));
            $planBadges = $parseRepeater($settings['planBadges'] ?? $translate('pricing_plans_default_plan_badges'));
            $planIcons = $parseRepeater($settings['planIcons'] ?? '');
            $featuredPlans = $parseRepeater($settings['featuredPlans'] ?? '');
            $ctaEnableds = $parseRepeater($settings['ctaEnableds'] ?? 'on');
            $ctaLabels = $parseRepeater($settings['ctaLabels'] ?? $translate('pricing_plans_default_cta_labels'));
            $ctaLinks = $parseRepeater($settings['ctaLinks'] ?? '');
            $ctaTargets = $parseRepeater($settings['ctaTargets'] ?? '');
            $ctaVariants = $parseRepeater($settings['ctaVariants'] ?? '');
            $ctaAligns = $parseRepeater($settings['ctaAligns'] ?? '');

            $columns = max(1, min(4, (int) ($settings['columns'] ?? 3)));
            $align = self::normalizeAlign($settings['align'] ?? 'left');
            $variant = $normalizeVariant($settings['variant'] ?? 'subtle');
            $showHeader = self::normalizeToggle($settings['showHeader'] ?? 'on', true);
            $showBadges = self::normalizeToggle($settings['showBadges'] ?? 'on', true);
            $showDescriptions = self::normalizeToggle($settings['showDescriptions'] ?? 'on', true);
            $showFeatures = self::normalizeToggle($settings['showFeatures'] ?? 'on', true);
            $showPopular = self::normalizeToggle($settings['showPopular'] ?? 'on', true);
            $popularBadgeLabel = trim((string) ($settings['popularBadgeLabel'] ?? $translate('pricing_plans_badge_popular')));
            $billingMonthlyLabel = trim((string) ($settings['billingMonthlyLabel'] ?? $translate('pricing_plans_toggle_monthly')));
            $billingYearlyLabel = trim((string) ($settings['billingYearlyLabel'] ?? $translate('pricing_plans_toggle_yearly')));
            $billingSavingsLabel = trim((string) ($settings['billingSavingsLabel'] ?? $translate('pricing_plans_toggle_savings')));
            $featuresHeading = trim((string) ($settings['featuresHeading'] ?? $translate('pricing_plans_features_heading')));
            $monthlyIntervalLabel = trim((string) ($settings['monthlyIntervalLabel'] ?? $translate('pricing_plans_price_interval_monthly')));
            $yearlyIntervalLabel = trim((string) ($settings['yearlyIntervalLabel'] ?? $translate('pricing_plans_price_interval_yearly')));
            $billingMode = 'monthly';

            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $subtitleStyle = $resolveTextStyle($settings, 'subtitleStyle', $align);
            $planTitleStyle = $resolveTextStyle($settings, 'planTitleStyle', $align);
            $priceStyle = $resolveTextStyle($settings, 'priceStyle', $align);
            $periodStyle = $resolveTextStyle($settings, 'periodStyle', $align);
            $descriptionStyle = $resolveTextStyle($settings, 'descriptionStyle', $align);
            $featureStyle = $resolveTextStyle($settings, 'featureStyle', $align);
            $badgeStyle = $resolveTextStyle($settings, 'badgeStyle', $align);

            $count = min(max(
                count($planNames),
                count($planMonthlyPrices),
                count($planYearlyPrices),
                count($planDescriptions),
                count($planFeatures),
                count($planBadges),
                count($planIcons),
                count($featuredPlans),
                count($ctaEnableds),
                count($ctaLabels),
                count($ctaLinks),
                count($ctaTargets),
                count($ctaVariants),
                count($ctaAligns),
                1
            ), 8);

            $plansHtml = '';
            for ($i = 0; $i < $count; $i++) {
                $planIndex = $i + 1;
                $name = trim((string) ($planNames[$i] ?? ''));
                $monthlyPrice = trim((string) ($planMonthlyPrices[$i] ?? ''));
                $yearlyPrice = trim((string) ($planYearlyPrices[$i] ?? ''));
                if ($yearlyPrice === '') {
                    $legacyYearlyPrice = trim((string) ($legacyPlanYearlyPrices[$i] ?? ''));
                    if ($hasDigitValue($legacyYearlyPrice)) {
                        $yearlyPrice = $legacyYearlyPrice;
                    }
                }
                if (!$hasDigitValue($monthlyPrice) && $hasDigitValue($yearlyPrice)) {
                    $monthlyPrice = $yearlyPrice;
                }
                if (!$hasDigitValue($yearlyPrice)) {
                    $yearlyPrice = $monthlyPrice;
                }
                $initialPrice = $billingMode === 'monthly'
                    ? ($monthlyPrice !== '' ? $monthlyPrice : $yearlyPrice)
                    : ($yearlyPrice !== '' ? $yearlyPrice : $monthlyPrice);
                $initialInterval = $billingMode === 'monthly' ? $monthlyIntervalLabel : $yearlyIntervalLabel;
                $description = trim((string) ($planDescriptions[$i] ?? ''));
                $badge = trim((string) ($planBadges[$i] ?? ''));
                $iconClass = self::sanitizeIconClass((string) ($planIcons[$i] ?? ''));
                $isFeatured = self::normalizeToggle($featuredPlans[$i] ?? 'off', false);
                $featuresList = $parseFeatureItems($planFeatures[$i] ?? '');
                $ctaEnabled = self::normalizeToggle($ctaEnableds[$i] ?? 'on', true);
                $ctaLabel = trim((string) ($ctaLabels[$i] ?? ''));
                $ctaUrl = self::sanitizeUrl((string) ($ctaLinks[$i] ?? ''));
                $ctaTarget = $ctaUrl !== '' ? $normalizeTarget($ctaTargets[$i] ?? '_self', '_self') : '_self';
                $ctaVariant = $normalizeButtonVariant($ctaVariants[$i] ?? ($isFeatured ? 'primary' : 'ghost'));
                $ctaAlign = self::normalizeAlign($ctaAligns[$i] ?? 'left', 'left');
                $showPopularBadge = $showPopular && $isFeatured && $popularBadgeLabel !== '';
                $showCustomBadge = $showBadges && $badge !== '' && !$showPopularBadge;

                $cardClass = 'pb-pricing-plan';
                if ($isFeatured) {
                    $cardClass .= ' is-featured';
                }
                if ($count > 1 && $i === 0) {
                    $cardClass .= ' has-mobile-swipe-hint';
                }

                $surfaceClass = 'pb-pricing-plan-surface pb-card ' . (($isFeatured || $variant === 'strong') ? 'pb-card-strong' : 'pb-card-subtle');
                $plansHtml .= '<article class="' . $escapeAttr($cardClass) . '" data-pricing-plan-index="' . $escapeAttr((string) $planIndex) . '">';
                $plansHtml .= '<div class="' . $escapeAttr($surfaceClass) . '" aria-hidden="true"></div>';
                $plansHtml .= '<div class="pb-pricing-plan-content">';

                $plansHtml .= '<div class="pb-pricing-plan-badge-stack"><div class="pb-pricing-plan-badge-line">';
                if ($showPopularBadge) {
                    $plansHtml .= '<span class="pb-pricing-plan-badge pb-pricing-plan-badge-popular">'
                        . '<i class="fa-classic fa-solid fa-crown pb-pricing-plan-badge-icon" aria-hidden="true"></i>'
                        . '<span class="pb-pricing-plan-badge-text">' . $escape($popularBadgeLabel) . '</span>'
                        . '</span>';
                } elseif ($showCustomBadge) {
                    $plansHtml .= $renderStyledText($badge, 'span', 'pb-pricing-plan-badge', $badgeStyle);
                }
                $plansHtml .= '</div></div>';

                $plansHtml .= '<div class="pb-pricing-plan-top">';
                $plansHtml .= '<div class="pb-pricing-plan-title-wrap">';
                if ($iconClass !== '') {
                    $plansHtml .= '<span class="pb-pricing-plan-icon" aria-hidden="true"><i class="' . $escapeAttr($iconClass) . '"></i></span>';
                }
                $plansHtml .= $renderStyledText($name, 'h3', 'pb-pricing-plan-name', $planTitleStyle);
                $plansHtml .= '</div>';
                $plansHtml .= '</div>';

                if ($showDescriptions && $description !== '') {
                    $plansHtml .= '<div class="pb-pricing-plan-description">'
                        . $renderStyledText($description, 'span', 'pb-pricing-plan-description-text', $descriptionStyle)
                        . '</div>';
                }

                $plansHtml .= '<div class="pb-pricing-plan-price-row">';
                $plansHtml .= '<span class="pb-pricing-plan-price"'
                    . ' data-pricing-plan-amount'
                    . ' data-price-monthly="' . $escapeAttr($monthlyPrice) . '"'
                    . ' data-price-yearly="' . $escapeAttr($yearlyPrice) . '">'
                    . '<span class="pb-styled-text-content">' . $escape($initialPrice) . '</span>'
                    . '</span>';
                $plansHtml .= '<span class="pb-pricing-plan-period"'
                    . ' data-pricing-plan-interval'
                    . ' data-interval-monthly="' . $escapeAttr($monthlyIntervalLabel) . '"'
                    . ' data-interval-yearly="' . $escapeAttr($yearlyIntervalLabel) . '">'
                    . '<span class="pb-styled-text-content">' . $escape($initialInterval) . '</span>'
                    . '</span>';
                $plansHtml .= '</div>';

                $plansHtml .= '<div class="pb-pricing-plan-divider" aria-hidden="true"></div>';

                if ($showFeatures) {
                    $plansHtml .= '<div class="pb-pricing-plan-features-group">';
                    if ($featuresHeading !== '') {
                        $plansHtml .= '<p class="pb-pricing-plan-features-title"><span class="pb-styled-text-content">' . $escape($featuresHeading) . '</span></p>';
                    }
                    $plansHtml .= '<ul class="pb-pricing-plan-features">';
                    foreach ($featuresList as $feature) {
                        $plansHtml .= '<li class="pb-pricing-plan-feature">' . $renderStyledText($feature, 'span', 'pb-pricing-plan-feature-text', $featureStyle) . '</li>';
                    }
                    $plansHtml .= '</ul>';
                    $plansHtml .= '</div>';
                }

                if ($ctaEnabled) {
                    $plansHtml .= '<div class="pb-pricing-plan-footer pb-pricing-plan-footer-align-' . $escapeAttr($ctaAlign) . '">';
                    if ($ctaLabel !== '' && $ctaUrl !== '') {
                        $rel = $ctaTarget === '_blank' ? ' rel="noopener noreferrer"' : '';
                        $plansHtml .= '<a class="btn btn-' . $escapeAttr($ctaVariant) . ' pb-btn pb-btn-' . $escapeAttr($ctaVariant) . ' pb-pricing-plan-cta" href="' . $escapeAttr($ctaUrl) . '" target="' . $escapeAttr($ctaTarget) . '"' . $rel . '>' . $escape($ctaLabel) . '</a>';
                    } elseif ($ctaLabel !== '') {
                        $plansHtml .= '<span class="btn btn-' . $escapeAttr($ctaVariant) . ' pb-btn pb-btn-' . $escapeAttr($ctaVariant) . ' pb-pricing-plan-cta is-static" aria-disabled="true">' . $escape($ctaLabel) . '</span>';
                    }
                    $plansHtml .= '</div>';
                }

                if ($name === '' && $monthlyPrice === '' && $yearlyPrice === '' && $description === '' && $featuresList === [] && $ctaLabel === '') {
                    $plansHtml .= '<div class="pb-empty">' . $escape($translate('pricing_plans_empty')) . '</div>';
                }

                if ($count > 1 && $i === 0) {
                    $plansHtml .= '<div class="pb-mobile-swipe-hint" data-mobile-swipe-hint aria-hidden="true">'
                        . '<span class="pb-mobile-swipe-hint-core">'
                        . '<span class="pb-mobile-swipe-hint-trail"></span>'
                        . '<i class="fa-classic fa-solid fa-hand-pointer pb-mobile-swipe-hint-hand" aria-hidden="true"></i>'
                        . '</span>'
                        . '</div>';
                }
                $plansHtml .= '</div>';
                $plansHtml .= '</article>';
            }

            $headerHtml = '';
            if ($showHeader && ($title !== '' || $subtitle !== '')) {
                $headerHtml = '<header class="pb-pricing-plans-header">'
                    . $renderStyledText($title, 'h2', 'pb-pricing-plans-title', $titleStyle)
                    . $renderStyledText($subtitle, 'p', 'pb-pricing-plans-subtitle', $subtitleStyle)
                    . '</header>';
            }

            $billingHtml = '<div class="pb-pricing-plans-toggle-wrap">'
                . '<div class="pb-pricing-plans-toggle" data-pricing-plans-toggle role="group" aria-label="' . $escapeAttr($translate('pricing_plans_toggle_group_label')) . '">'
                . '<span class="pb-pricing-plans-toggle-indicator" aria-hidden="true"></span>'
                . '<button type="button" class="pb-pricing-plans-toggle-btn is-active" data-billing-choice="monthly" aria-pressed="true">' . $escape($billingMonthlyLabel) . '</button>'
                . '<button type="button" class="pb-pricing-plans-toggle-btn" data-billing-choice="yearly" aria-pressed="false">' . $escape($billingYearlyLabel) . '</button>'
                . '</div>';
            if ($billingSavingsLabel !== '') {
                $billingHtml .= '<div class="pb-pricing-plans-saving">'
                    . '<span class="pb-pricing-plans-saving-line" aria-hidden="true"></span>'
                    . '<span class="pb-pricing-plans-saving-badge">' . $escape($billingSavingsLabel) . '</span>'
                    . '</div>';
            }
            $billingHtml .= '</div>';

            $html = '<section class="pb-pricing-plans pb-pricing-plans-variant-' . $escapeAttr($variant) . ' pb-pricing-plans-align-' . $escapeAttr($align) . '" data-pricing-plans-root data-billing-default="' . $escapeAttr($billingMode) . '" data-billing-mode="' . $escapeAttr($billingMode) . '">'
                . '<div class="pb-pricing-plans-shell">'
                . '<div class="pb-pricing-plans-orb" aria-hidden="true"></div>'
                . '<div class="pb-pricing-plans-frame">'
                . $headerHtml
                . $billingHtml
                . '<div class="pb-pricing-plans-grid-shell">'
                . '<div class="pb-pricing-plans-grid pb-pricing-plans-cols-' . $columns . '">'
                . $plansHtml
                . '</div>'
                . '</div>'
                . '</div>'
                . '</div>'
                . '</section>';

            $css = [];
            $safeId = self::blockId($context);
            if ($safeId !== '') {
                $css[] = self::blockSelector($safeId, '.pb-pricing-plans') . '{text-align:' . $escapeAttr($align) . ';}';
                $css[] = self::blockSelector($safeId, '.pb-pricing-plans') . '{--pb-pricing-columns:' . $columns . ';}';
                $css = array_merge($css, self::buildWidgetDesignRules(
                    $safeId,
                    ['.pb-pricing-plan-surface', '.pb-pricing-plan:hover .pb-pricing-plan-surface', '.pb-pricing-plan.is-featured .pb-pricing-plan-surface'],
                    ['.pb-pricing-plans-title', '.pb-pricing-plans-subtitle', '.pb-pricing-plan-name', '.pb-pricing-plan-description-text', '.pb-pricing-plan-price', '.pb-pricing-plan-period', '.pb-pricing-plan-features-title', '.pb-pricing-plan-feature-text', '.pb-pricing-plan-badge', '.pb-pricing-plan-icon'],
                    $useCustomDesign,
                    $designSurfaceColor,
                    $designTextColor,
                    $designBorderStyle,
                    $designBorderWidth,
                    $designBorderColor,
                    $designRadius,
                    $designShadow
                ));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-pricing-plans-title', $titleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-pricing-plans-subtitle', $subtitleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-pricing-plan-name', $planTitleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-pricing-plan-price', $priceStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-pricing-plan-period', $periodStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-pricing-plan-description-text', $descriptionStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-pricing-plan-feature-text', $featureStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-pricing-plan-badge', $badgeStyle));
                $css = array_merge($css, $buildFlexAlignRules($safeId, '.pb-pricing-plan-badge-line', (string) ($badgeStyle['align'] ?? $align)));
                $css = array_merge($css, $buildFlexAlignRules($safeId, '.pb-pricing-plan-top', (string) ($planTitleStyle['align'] ?? $align)));
                $css = array_merge($css, $buildFlexAlignRules($safeId, '.pb-pricing-plan-price-row', (string) ($priceStyle['align'] ?? $align)));
                $css = array_merge($css, $buildFeatureListAlignRules($safeId, $featureStyle));
            }

            return [
                'html' => $html,
                'css' => implode("\n", $css),
            ];
        };
    }
}
