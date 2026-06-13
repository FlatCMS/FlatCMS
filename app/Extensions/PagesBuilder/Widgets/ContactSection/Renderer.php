<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\ContactSection;

use App\Extensions\PagesBuilder\Services\PageBuilderContactFormCatalogService;
use App\Extensions\PagesBuilder\Services\PageBuilderWidgetLocaleService;
use App\Extensions\PagesBuilder\Support\AbstractWidgetRenderer;

final class Renderer extends AbstractWidgetRenderer
{
    protected static function renderer(): callable
    {
        return static function (array $settings, array $context): array {
            if (class_exists(\App\Core\I18n::class)) {
                \App\Core\I18n::load('Contact');
            }

            $helpers = is_array($context['helpers'] ?? null) ? $context['helpers'] : [];
            $escape = $helpers['escape'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $escapeAttr = $helpers['escape_attr'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $translate = static fn(string $key, string $fallback = ''): string => PageBuilderWidgetLocaleService::translate('ContactSection', $key, $fallback);

            $normalizeVerticalAlign = static function (string $raw, string $fallback = 'center'): string {
                $value = strtolower(trim($raw));
                if (in_array($value, ['top', 'center', 'bottom'], true)) {
                    return $value;
                }

                $safeFallback = strtolower(trim($fallback));
                return in_array($safeFallback, ['top', 'center', 'bottom'], true) ? $safeFallback : 'center';
            };

            $normalizeVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['subtle', 'strong', 'dark'], true) ? $value : 'subtle';
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

                $iconPosition = strtolower(trim((string) ($style['iconPosition'] ?? 'start')));
                if (!in_array($iconPosition, ['start', 'end'], true)) {
                    $iconPosition = 'start';
                }

                $iconHtml = '<i class="' . $escapeAttr($icon) . ' pb-styled-text-icon pb-styled-text-icon-' . $escapeAttr($iconPosition) . '" aria-hidden="true"></i>';
                return $iconPosition === 'end' ? $content . $iconHtml : $iconHtml . $content;
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

                return '<span class="pb-styled-text-list-marker pb-styled-text-list-marker-' . $escape($listStyle) . '" aria-hidden="true">'
                    . $escape($glyph)
                    . '</span>' . $content;
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

            $renderStyledParagraphs = static function (string $text, string $className, array $style) use (
                $escape,
                $escapeAttr,
                $injectTextIcon,
                $injectTextListMarker
            ): string {
                $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));
                if ($normalized === '') {
                    return '';
                }

                $chunks = preg_split('/\n\s*\n/u', $normalized) ?: [];
                $paragraphs = [];
                foreach ($chunks as $chunk) {
                    $line = trim((string) $chunk);
                    if ($line === '') {
                        continue;
                    }
                    $safeLine = nl2br($escape($line), false);
                    $content = '<span class="pb-styled-text-content">' . $safeLine . '</span>';
                    $paragraphs[] = '<p class="pb-contact-section-body-paragraph">'
                        . $injectTextListMarker($injectTextIcon($content, $style), $style)
                        . '</p>';
                }

                if ($paragraphs === []) {
                    return '';
                }

                return '<div class="' . $escapeAttr($className) . '">' . implode('', $paragraphs) . '</div>';
            };

            $buildTextStyleRules = static function (string $safeId, string $selector, array $style) use ($escapeAttr): array {
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

                $css = [$scopedSelector . '{' . implode('', $rules) . '}'];
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
                    $css[] = $scopedSelector . ' .pb-styled-text-content{' . implode('', $contentRules) . '}';
                }

                $listStyle = self::normalizeTextStyleList((string) ($style['list'] ?? 'none'));
                if ($listStyle !== 'none') {
                    $css[] = $scopedSelector . ' .pb-styled-text-list-marker{display:inline-block;margin-right:0.45rem;}';
                }

                return $css;
            };

            $buildSelfAlignRules = static function (string $safeId, string $selector, string $align) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $normalizedAlign = self::normalizeAlign($align, 'left');
                $justifySelf = match ($normalizedAlign) {
                    'center' => 'center',
                    'right' => 'end',
                    default => 'start',
                };

                return [
                    self::blockSelector($safeId, $selector) . '{justify-self:' . $escapeAttr($justifySelf) . ';}',
                ];
            };

            $buildAccentColorRules = static function (string $safeId, string $selector, array $style) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $color = trim((string) ($style['color'] ?? ''));
                if ($color === '') {
                    return [];
                }

                return [
                    self::blockSelector($safeId, $selector) . '{color:' . $escapeAttr($color) . ';}',
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
                    self::blockSelector($safeId, '.pb-contact-section-features') . '{' . implode('', $featureListRules) . '}',
                    self::blockSelector($safeId, '.pb-contact-section-feature') . '{' . implode('', $featureRules) . '}',
                    self::blockSelector($safeId, '.pb-contact-section-feature-text') . '{' . implode('', $featureTextRules) . '}',
                ];
            };

            $parseLines = static function (string $raw): array {
                $value = str_replace(["\r\n", "\r"], "\n", trim($raw));
                if ($value === '') {
                    return [];
                }

                $items = array_map(
                    static fn(string $item): string => trim(preg_replace('/^[-*•\s]+/u', '', $item) ?? ''),
                    explode("\n", $value)
                );

                return array_values(array_filter($items, static fn(string $item): bool => $item !== ''));
            };

            $normalizePreviewFieldType = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                $allowed = ['text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date'];
                return in_array($value, $allowed, true) ? $value : 'text';
            };

            $normalizePreviewFieldWidth = static function (mixed $raw): string {
                return strtolower(trim((string) $raw)) === 'half' ? 'half' : 'full';
            };

            $normalizePreviewOptions = static function (mixed $raw): array {
                if (is_array($raw)) {
                    return array_values(array_filter(array_map(static fn(mixed $entry): string => trim((string) $entry), $raw), static fn(string $entry): bool => $entry !== ''));
                }

                $value = trim((string) $raw);
                if ($value === '') {
                    return [];
                }

                return array_values(array_filter(array_map(static fn(string $entry): string => trim($entry), preg_split('/[\r\n,;|]+/u', $value) ?: []), static fn(string $entry): bool => $entry !== ''));
            };

            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(48, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $showEyebrow = self::normalizeToggle($settings['showEyebrow'] ?? true, true);
            $showBody = self::normalizeToggle($settings['showBody'] ?? true, true);
            $showFeatures = self::normalizeToggle($settings['showFeatures'] ?? true, true);
            $showProof = self::normalizeToggle($settings['showProof'] ?? true, true);

            $eyebrow = trim((string) ($settings['eyebrow'] ?? ''));
            $title = trim((string) ($settings['title'] ?? ''));
            $subtitle = trim((string) ($settings['subtitle'] ?? ''));
            $body = trim((string) ($settings['body'] ?? ''));
            $featureItems = $parseLines((string) ($settings['featureItems'] ?? ''));
            $proofLabel = trim((string) ($settings['proofLabel'] ?? ''));
            $formTitle = trim((string) ($settings['formTitle'] ?? ''));
            $formDescription = trim((string) ($settings['formDescription'] ?? ''));
            $helperText = trim((string) ($settings['helperText'] ?? ''));
            $contactFormSlug = trim((string) ($settings['contactFormSlug'] ?? 'contact-main'));
            $formCatalog = new PageBuilderContactFormCatalogService();
            $selectedContactForm = $contactFormSlug !== '' ? $formCatalog->findFormBySlug($contactFormSlug) : null;
            if (!is_array($selectedContactForm)) {
                $fallbackContactSlug = $formCatalog->preferredSlug(
                    PageBuilderContactFormCatalogService::SCOPE_CONTACT,
                    $contactFormSlug !== '' ? $contactFormSlug : 'contact-main'
                );
                $selectedContactForm = $formCatalog->findFormBySlug($fallbackContactSlug);
                if (is_array($selectedContactForm)) {
                    $contactFormSlug = trim((string) ($selectedContactForm['slug'] ?? ''));
                }
            }
            if ($contactFormSlug === '') {
                $contactFormSlug = 'contact-main';
            }
            $selectedBuilderForm = null;
            foreach ($formCatalog->builderConfigForms(PageBuilderContactFormCatalogService::SCOPE_CONTACT) as $builderForm) {
                if (!is_array($builderForm)) {
                    continue;
                }
                if (strtolower(trim((string) ($builderForm['slug'] ?? ''))) !== strtolower($contactFormSlug)) {
                    continue;
                }
                $selectedBuilderForm = $builderForm;
                break;
            }
            $formUnavailableMessage = trim((string) ($settings['formUnavailableMessage'] ?? ''));
            $emptyMessage = trim((string) ($settings['emptyMessage'] ?? ''));
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $textVerticalAlign = $normalizeVerticalAlign((string) ($settings['textVerticalAlign'] ?? 'center'));
            $variant = $normalizeVariant($settings['variant'] ?? 'subtle');

            $eyebrowStyle = $resolveTextStyle($settings, 'eyebrowStyle', $align);
            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $subtitleStyle = $resolveTextStyle($settings, 'subtitleStyle', $align);
            $bodyStyle = $resolveTextStyle($settings, 'bodyStyle', $align);
            $featureStyle = $resolveTextStyle($settings, 'featureStyle', $align);
            $proofStyle = $resolveTextStyle($settings, 'proofStyle', $align);
            $formTitleStyle = $resolveTextStyle($settings, 'formTitleStyle', $align);
            $formDescriptionStyle = $resolveTextStyle($settings, 'formDescriptionStyle', $align);
            $helperTextStyle = $resolveTextStyle($settings, 'helperTextStyle', $align);

            $safeId = self::blockId($context);

            $contentHtml = '';
            if ($showEyebrow) {
                $contentHtml .= $renderStyledText($eyebrow, 'p', 'pb-contact-section-eyebrow', $eyebrowStyle);
            }
            $contentHtml .= $renderStyledText($title, 'h2', 'pb-contact-section-title', $titleStyle);
            $contentHtml .= $renderStyledText($subtitle, 'p', 'pb-contact-section-subtitle', $subtitleStyle);
            if ($showBody) {
                $contentHtml .= $renderStyledParagraphs($body, 'pb-contact-section-body', $bodyStyle);
            }
            if ($showFeatures && $featureItems !== []) {
                $itemsHtml = [];
                foreach ($featureItems as $featureItem) {
                    $itemsHtml[] = '<li class="pb-contact-section-feature">'
                        . $renderStyledText($featureItem, 'span', 'pb-contact-section-feature-text', $featureStyle)
                        . '</li>';
                }
                $contentHtml .= '<ul class="pb-contact-section-features">' . implode('', $itemsHtml) . '</ul>';
            }
            if ($showProof) {
                $contentHtml .= $renderStyledText($proofLabel, 'p', 'pb-contact-section-proof', $proofStyle);
            }

            $formInnerHtml = '';
            $sourceUrl = function_exists('url') && function_exists('app')
                ? (string) url(app()->request()->uri())
                : (function_exists('url') ? (string) url('/contact') : '/contact');
            $shortcodeContext = ['source_url' => $sourceUrl];
            if (function_exists('flatcms_render_shortcode_tag')) {
                $formInnerHtml = trim((string) flatcms_render_shortcode_tag('contact-form', ['slug' => $contactFormSlug], $shortcodeContext));
                if ($formInnerHtml === '' && $contactFormSlug !== '') {
                    $formInnerHtml = trim((string) flatcms_render_shortcode_tag('contact-form', [], $shortcodeContext));
                }
            }

            if ($formInnerHtml === '' && is_array($selectedBuilderForm)) {
                $submitLabel = trim((string) ($selectedBuilderForm['submitLabel'] ?? ''));
                if ($submitLabel === '') {
                    $submitLabel = __('contact_form_submit_label', 'Contact');
                }

                $previewFields = is_array($selectedBuilderForm['previewFields'] ?? null) ? $selectedBuilderForm['previewFields'] : [];
                $normalizedPreviewFields = [];
                foreach ($previewFields as $field) {
                    if (!is_array($field)) {
                        continue;
                    }
                    $fieldKey = trim((string) ($field['key'] ?? ''));
                    $fieldLabel = trim((string) ($field['label'] ?? ''));
                    if ($fieldKey === '' || $fieldLabel === '') {
                        continue;
                    }

                    $normalizedPreviewFields[] = [
                        'key' => $fieldKey,
                        'label' => $fieldLabel,
                        'type' => $normalizePreviewFieldType($field['type'] ?? 'text'),
                        'required' => self::normalizeToggle($field['required'] ?? false),
                        'width' => $normalizePreviewFieldWidth($field['width'] ?? 'full'),
                        'placeholder' => trim((string) ($field['placeholder'] ?? '')),
                        'help' => trim((string) ($field['help'] ?? '')),
                        'options' => $normalizePreviewOptions($field['options'] ?? []),
                    ];
                }

                $resolveAutocomplete = static function (string $fieldKey, string $fieldType): string {
                    $normalizedKey = strtolower(trim($fieldKey));
                    $normalizedType = strtolower(trim($fieldType));

                    if ($normalizedType === 'email' || in_array($normalizedKey, ['email', 'mail'], true)) {
                        return 'email';
                    }

                    if ($normalizedType === 'tel' || in_array($normalizedKey, ['phone', 'telephone', 'tel', 'mobile'], true)) {
                        return 'tel';
                    }

                    if ($normalizedType === 'url' || in_array($normalizedKey, ['url', 'website', 'site_web', 'siteweb', 'link'], true)) {
                        return 'url';
                    }

                    return match ($normalizedKey) {
                        'name', 'full_name', 'fullname', 'author_name', 'admin_name' => 'name',
                        'first_name', 'firstname', 'given_name', 'givenname' => 'given-name',
                        'last_name', 'lastname', 'family_name', 'familyname' => 'family-name',
                        'company', 'organisation', 'organization', 'societe', 'entreprise' => 'organization',
                        default => 'on',
                    };
                };

                $renderFallbackField = static function (array $field, int $index) use ($escape, $escapeAttr, $resolveAutocomplete): string {
                    $fieldId = 'pb-contact-section-' . $index . '-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) ($field['key'] ?? 'field'));
                    $fieldId = is_string($fieldId) ? $fieldId : 'pb-contact-section-field-' . $index;
                    $fieldName = 'cf[' . (string) ($field['key'] ?? 'field') . ']';
                    $fieldType = strtolower(trim((string) ($field['type'] ?? 'text')));
                    $fieldLabel = trim((string) ($field['label'] ?? ''));
                    $fieldPlaceholder = trim((string) ($field['placeholder'] ?? ''));
                    $fieldHelp = trim((string) ($field['help'] ?? ''));
                    $fieldOptions = is_array($field['options'] ?? null) ? $field['options'] : [];
                    $isRequired = !empty($field['required']);
                    $requiredMark = $isRequired ? '<span class="flatcms-contact-required-mark" aria-hidden="true">*</span>' : '';

                    if ($fieldType === 'checkbox' && $fieldOptions === []) {
                        $html = '<div class="form-group">'
                            . '<label class="flatcms-contact-choice-item">'
                            . '<input type="checkbox" name="' . $escapeAttr($fieldName) . '" value="1"' . ($isRequired ? ' required' : '') . '>'
                            . '<span>' . $escape($fieldLabel) . $requiredMark . '</span>'
                            . '</label>';
                        if ($fieldHelp !== '') {
                            $html .= '<small class="flatcms-contact-hint">' . $escape($fieldHelp) . '</small>';
                        }
                        return $html . '</div>';
                    }

                    $html = '<div class="form-group">'
                        . '<label class="form-label" for="' . $escapeAttr($fieldId) . '">'
                        . $escape($fieldLabel) . $requiredMark
                        . '</label>';

                    if ($fieldType === 'textarea') {
                        $html .= '<textarea id="' . $escapeAttr($fieldId) . '" name="' . $escapeAttr($fieldName) . '" class="form-input flatcms-contact-message" rows="4" placeholder="' . $escapeAttr($fieldPlaceholder) . '" autocomplete="' . $escapeAttr($resolveAutocomplete($fieldKey, $fieldType)) . '"' . ($isRequired ? ' required' : '') . '></textarea>';
                    } elseif ($fieldType === 'select') {
                        $html .= '<select id="' . $escapeAttr($fieldId) . '" name="' . $escapeAttr($fieldName) . '" class="form-input"' . ($isRequired ? ' required' : '') . '>';
                        if ($fieldPlaceholder !== '') {
                            $html .= '<option value="">' . $escape($fieldPlaceholder) . '</option>';
                        }
                        foreach ($fieldOptions as $option) {
                            $optionValue = trim((string) $option);
                            if ($optionValue === '') {
                                continue;
                            }
                            $html .= '<option value="' . $escapeAttr($optionValue) . '">' . $escape($optionValue) . '</option>';
                        }
                        $html .= '</select>';
                    } elseif (in_array($fieldType, ['radio', 'checkbox'], true) && $fieldOptions !== []) {
                        $html .= '<div class="flatcms-contact-choice-list">';
                        foreach ($fieldOptions as $optionIndex => $option) {
                            $optionValue = trim((string) $option);
                            if ($optionValue === '') {
                                continue;
                            }
                            $optionId = $fieldId . '-' . ($optionIndex + 1);
                            $html .= '<label class="flatcms-contact-choice-item" for="' . $escapeAttr($optionId) . '">'
                                . '<input id="' . $escapeAttr($optionId) . '" type="' . $escapeAttr($fieldType) . '" name="' . $escapeAttr($fieldName) . ($fieldType === 'checkbox' ? '[]' : '') . '" value="' . $escapeAttr($optionValue) . '"' . ($isRequired && $optionIndex === 0 ? ' required' : '') . '>'
                                . '<span>' . $escape($optionValue) . '</span>'
                                . '</label>';
                        }
                        $html .= '</div>';
                    } else {
                        $inputType = in_array($fieldType, ['email', 'tel', 'url', 'number', 'date'], true) ? $fieldType : 'text';
                        $html .= '<input id="' . $escapeAttr($fieldId) . '" type="' . $escapeAttr($inputType) . '" name="' . $escapeAttr($fieldName) . '" class="form-input" placeholder="' . $escapeAttr($fieldPlaceholder) . '" autocomplete="' . $escapeAttr($resolveAutocomplete($fieldKey, $fieldType)) . '"' . ($isRequired ? ' required' : '') . '>';
                    }

                    if ($fieldHelp !== '') {
                        $html .= '<small class="flatcms-contact-hint">' . $escape($fieldHelp) . '</small>';
                    }

                    return $html . '</div>';
                };

                $fieldsHtml = [];
                foreach ($normalizedPreviewFields as $index => $field) {
                    $widthClass = ($field['width'] ?? 'full') === 'half' ? 'flatcms-contact-custom-field--half' : 'flatcms-contact-custom-field--full';
                    $fieldsHtml[] = '<div class="flatcms-contact-custom-field ' . $escapeAttr($widthClass) . '">'
                        . $renderFallbackField($field, $index + 1)
                        . '</div>';
                }

                $attachments = is_array($selectedBuilderForm['attachments'] ?? null) ? $selectedBuilderForm['attachments'] : [];
                $attachmentsEnabled = self::normalizeToggle($attachments['enabled'] ?? false);
                $attachmentsRequired = self::normalizeToggle($attachments['required'] ?? false);
                $attachmentsMaxFiles = max(1, min(5, (int) ($attachments['maxFiles'] ?? 1)));
                $attachmentsMaxSizeMb = max(1, min(25, (int) ($attachments['maxSizeMb'] ?? 5)));
                $attachmentsExtensions = array_values(array_filter(array_map(static fn(mixed $value): string => trim((string) $value), is_array($attachments['extensions'] ?? null) ? $attachments['extensions'] : []), static fn(string $value): bool => $value !== ''));
                $attachmentAccept = $attachmentsExtensions !== [] ? implode(',', array_map(static fn(string $ext): string => '.' . ltrim($ext, '.'), $attachmentsExtensions)) : '';
                $attachmentHintParts = [(string) $attachmentsMaxFiles, (string) $attachmentsMaxSizeMb . ' MB'];
                if ($attachmentsExtensions !== []) {
                    $attachmentHintParts[] = implode(', ', $attachmentsExtensions);
                }
                $attachmentHint = implode(' · ', $attachmentHintParts);

                $attachmentsHtml = '';
                if ($attachmentsEnabled) {
                    $attachmentsHtml = '<div class="form-group" data-contact-attachments>'
                        . '<label class="form-label" for="pbContactSectionAttachments">'
                        . $escape(__('contact_form_attachments_input_label', 'Contact'))
                        . ($attachmentsRequired ? '<span class="flatcms-contact-required-mark" aria-hidden="true">*</span>' : '')
                        . '</label>'
                        . '<input id="pbContactSectionAttachments" class="form-input flatcms-contact-attachments-input" type="file" name="attachments[]"' . ($attachmentsMaxFiles > 1 ? ' multiple' : '') . ($attachmentAccept !== '' ? ' accept="' . $escapeAttr($attachmentAccept) . '"' : '') . ($attachmentsRequired ? ' required' : '') . '>'
                        . '<small class="flatcms-contact-hint">' . $escape($attachmentHint) . '</small>'
                        . '</div>';
                }

                $formInnerHtml = '<section class="flatcms-contact-native flatcms-contact-embed">'
                    . '<form class="flatcms-contact-form flatcms-contact-native-form pb-contact-section-form" method="post" action="' . $escapeAttr(function_exists('url') ? (string) url('/contact/send') : '/contact/send') . '"' . ($attachmentsEnabled ? ' enctype="multipart/form-data"' : '') . ' data-validation-required="' . $escapeAttr(__('contact_form_client_required_message', 'Contact')) . '">'
                    . (function_exists('csrf_field') ? (string) csrf_field() : '')
                    . '<input type="hidden" name="source_url" value="' . $escapeAttr($sourceUrl) . '">'
                    . '<input type="hidden" name="contact_form_id" value="' . $escapeAttr((string) ($selectedBuilderForm['id'] ?? '')) . '">'
                    . '<input type="hidden" name="contact_form_slug" value="' . $escapeAttr($contactFormSlug) . '">'
                    . '<div class="flatcms-contact-honeypot" aria-hidden="true">'
                    . '<label for="pbContactSectionCompany">' . $escape(__('contact_form_honeypot_company', 'Contact')) . '</label>'
                    . '<input id="pbContactSectionCompany" type="text" name="company" tabindex="-1" autocomplete="off">'
                    . '</div>'
                    . '<div class="flatcms-contact-custom-grid">' . implode('', $fieldsHtml) . '</div>'
                    . $attachmentsHtml
                    . '<button type="submit" class="btn btn-primary pb-contact-section-submit">' . $escape($submitLabel) . '</button>'
                    . '</form>'
                    . '</section>';
            }

            if ($formInnerHtml === '') {
                $fallbackMessage = $formUnavailableMessage !== '' ? $formUnavailableMessage : $translate('contact_section_default_form_unavailable_message');
                $formInnerHtml = '<div class="pb-contact-section-form-unavailable"><p class="pb-contact-section-form-unavailable-text">' . $escape($fallbackMessage) . '</p></div>';
            }

            $formHtml = '<div class="pb-contact-section-panel pb-contact-section-form-panel">'
                . $renderStyledText($formTitle, 'h3', 'pb-contact-section-form-title', $formTitleStyle)
                . $renderStyledParagraphs($formDescription, 'pb-contact-section-form-description', $formDescriptionStyle)
                . '<div class="pb-contact-section-form-embed">' . $formInnerHtml . '</div>'
                . $renderStyledParagraphs($helperText, 'pb-contact-section-helper', $helperTextStyle)
                . '</div>';

            $frameInner = '<div class="pb-contact-section-content">' . $contentHtml . '</div>'
                . '<div class="pb-contact-section-form-wrap">' . $formHtml . '</div>';

            if (trim(strip_tags($contentHtml . $formTitle . $formDescription . $helperText)) === '') {
                $frameInner = '<div class="pb-empty">' . $escape($emptyMessage !== '' ? $emptyMessage : $translate('contact_section_empty')) . '</div>';
            }

            $html = '<section class="pb-contact-section pb-contact-section-variant-' . $escapeAttr($variant)
                . ' pb-contact-section-align-' . $escapeAttr($align)
                . ' pb-contact-section-text-valign-' . $escapeAttr($textVerticalAlign)
                . '">'
                . '<div class="pb-contact-section-frame">'
                . $frameInner
                . '</div>'
                . '</section>';

            $css = [];
            if ($safeId !== '') {
                $css = array_merge($css, self::buildWidgetDesignRules(
                    $safeId,
                    ['.pb-contact-section-frame'],
                    ['.pb-contact-section-eyebrow', '.pb-contact-section-title', '.pb-contact-section-subtitle', '.pb-contact-section-body', '.pb-contact-section-body *', '.pb-contact-section-feature-text', '.pb-contact-section-proof', '.pb-contact-section-form-title', '.pb-contact-section-form-description', '.pb-contact-section-helper'],
                    $useCustomDesign,
                    $designSurfaceColor,
                    $designTextColor,
                    $designBorderStyle,
                    $designBorderWidth,
                    $designBorderColor,
                    $designRadius,
                    $designShadow
                ));
                if ($useCustomDesign) {
                    $formCssVars = [];
                    if ($designSurfaceColor !== '') {
                        $formCssVars[] = '--pb-contact-form-shell-bg:' . $escapeAttr($designSurfaceColor) . ';';
                        $formCssVars[] = '--pb-contact-form-input-bg:' . $escapeAttr($designSurfaceColor) . ';';
                    }
                    if ($designBorderColor !== '') {
                        $formCssVars[] = '--pb-contact-form-shell-border:' . $escapeAttr($designBorderColor) . ';';
                        $formCssVars[] = '--pb-contact-form-input-border:' . $escapeAttr($designBorderColor) . ';';
                    }
                    if ($designTextColor !== '') {
                        $formCssVars[] = '--pb-contact-form-label-color:' . $escapeAttr($designTextColor) . ';';
                        $formCssVars[] = '--pb-contact-form-input-color:' . $escapeAttr($designTextColor) . ';';
                        $formCssVars[] = '--pb-contact-form-placeholder-color:' . $escapeAttr($designTextColor) . ';';
                    }
                    $formCssVars[] = '--pb-contact-form-shell-radius:' . $escapeAttr((string) max(0, $designRadius - 6)) . 'px;';
                    if ($formCssVars !== []) {
                        $css[] = self::blockSelector($safeId, '.pb-contact-section') . '{' . implode('', $formCssVars) . '}';
                    }
                }
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-contact-section-eyebrow', $eyebrowStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-contact-section-title', $titleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-contact-section-subtitle', $subtitleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-contact-section-body', $bodyStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-contact-section-feature-text', $featureStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-contact-section-proof', $proofStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-contact-section-form-title', $formTitleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-contact-section-form-description', $formDescriptionStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-contact-section-helper', $helperTextStyle));
                $css = array_merge($css, $buildSelfAlignRules($safeId, '.pb-contact-section-eyebrow', (string) ($eyebrowStyle['align'] ?? 'left')));
                $css = array_merge($css, $buildSelfAlignRules($safeId, '.pb-contact-section-proof', (string) ($proofStyle['align'] ?? 'left')));
                $css = array_merge($css, $buildAccentColorRules($safeId, '.pb-contact-section-feature', $featureStyle));
                $css = array_merge($css, $buildFeatureListAlignRules($safeId, $featureStyle));
            }

            return [
                'html' => $html,
                'css' => implode("\n", $css),
            ];
        };
    }
}
