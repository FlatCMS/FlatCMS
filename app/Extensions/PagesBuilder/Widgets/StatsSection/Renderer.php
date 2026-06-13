<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\StatsSection;

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

            $parseRepeaterLines = static function (mixed $raw): array {
                if (!is_string($raw) || trim($raw) === '') {
                    return [];
                }

                $items = preg_split('/\r\n|\r|\n/', $raw) ?: [];
                $items = array_map(static fn(mixed $item): string => trim((string) $item), $items);

                while ($items !== [] && trim((string) $items[count($items) - 1]) === '') {
                    array_pop($items);
                }

                return $items;
            };

            $normalizeVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['subtle', 'strong', 'dashed'], true) ? $value : 'subtle';
            };

            $normalizeColumns = static function (mixed $raw): int {
                $value = (int) $raw;
                return max(2, min(4, $value > 0 ? $value : 3));
            };

            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(48, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $title = trim((string) ($settings['title'] ?? PageBuilderWidgetLocaleService::translate('StatsSection', 'stats_section_default_title')));
            $subtitle = trim((string) ($settings['subtitle'] ?? PageBuilderWidgetLocaleService::translate('StatsSection', 'stats_section_default_subtitle')));
            $values = $parseRepeaterLines($settings['values'] ?? PageBuilderWidgetLocaleService::translate('StatsSection', 'stats_section_default_values'));
            $labels = $parseRepeaterLines($settings['labels'] ?? PageBuilderWidgetLocaleService::translate('StatsSection', 'stats_section_default_labels'));
            $notes = $parseRepeaterLines($settings['notes'] ?? PageBuilderWidgetLocaleService::translate('StatsSection', 'stats_section_default_notes'));
            $showHeader = self::normalizeToggle($settings['showHeader'] ?? 'on', true);
            $showNotes = self::normalizeToggle($settings['showNotes'] ?? 'on', true);
            $align = self::normalizeAlign($settings['align'] ?? 'left', 'left');
            $variant = $normalizeVariant($settings['variant'] ?? 'subtle');
            $columns = $normalizeColumns($settings['columns'] ?? 3);

            $count = max(count($values), count($labels), count($notes), 1);
            $count = min($count, 8);

            $itemsHtml = '';
            for ($index = 0; $index < $count; $index++) {
                $value = trim((string) ($values[$index] ?? ''));
                $label = trim((string) ($labels[$index] ?? ''));
                $note = trim((string) ($notes[$index] ?? ''));

                if ($value === '' && $label === '' && $note === '') {
                    continue;
                }

                $cardClass = match ($variant) {
                    'strong' => 'pb-card pb-card-strong',
                    'dashed' => 'pb-card pb-card-subtle',
                    default => 'pb-card pb-card-subtle',
                };
                $itemsHtml .= '<article class="pb-stats-card ' . $escapeAttr($cardClass) . '">';
                if ($value !== '') {
                    $itemsHtml .= '<strong class="pb-stats-card-value"><span class="pb-styled-text-content">' . $escape($value) . '</span></strong>';
                }
                if ($label !== '') {
                    $itemsHtml .= '<h3 class="pb-stats-card-label"><span class="pb-styled-text-content">' . $escape($label) . '</span></h3>';
                }
                if ($showNotes && $note !== '') {
                    $itemsHtml .= '<p class="pb-stats-card-note"><span class="pb-styled-text-content">' . $escape($note) . '</span></p>';
                }
                $itemsHtml .= '</article>';
            }

            if ($itemsHtml === '') {
                $itemsHtml = '<div class="pb-empty">' . $escape(PageBuilderWidgetLocaleService::translate('StatsSection', 'stats_section_empty')) . '</div>';
            }

            $headerHtml = '';
            if ($showHeader && ($title !== '' || $subtitle !== '')) {
                $headerHtml .= '<header class="pb-stats-section-header">';
                if ($title !== '') {
                    $headerHtml .= '<h2 class="pb-stats-section-title"><span class="pb-styled-text-content">' . $escape($title) . '</span></h2>';
                }
                if ($subtitle !== '') {
                    $headerHtml .= '<p class="pb-stats-section-subtitle"><span class="pb-styled-text-content">' . $escape($subtitle) . '</span></p>';
                }
                $headerHtml .= '</header>';
            }

            $html = '<section class="pb-stats-section pb-stats-section-variant-' . $escapeAttr($variant) . ' pb-stats-section-align-' . $escapeAttr($align) . '">'
                . $headerHtml
                . '<div class="pb-stats-grid pb-stats-grid-cols-' . $escapeAttr((string) $columns) . '">'
                . $itemsHtml
                . '</div>'
                . '</section>';

            $safeId = self::blockId($context);
            $css = self::buildWidgetDesignRules(
                $safeId,
                ['.pb-stats-card', '.pb-stats-card:hover'],
                ['.pb-stats-section-title', '.pb-stats-section-subtitle', '.pb-stats-card-value', '.pb-stats-card-label', '.pb-stats-card-note'],
                $useCustomDesign,
                $designSurfaceColor,
                $designTextColor,
                $designBorderStyle,
                $designBorderWidth,
                $designBorderColor,
                $designRadius,
                $designShadow
            );

            return ['html' => $html, 'css' => implode("\n", $css)];
        };
    }
}
