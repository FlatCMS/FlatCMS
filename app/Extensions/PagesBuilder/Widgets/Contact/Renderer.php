<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\Contact;

use App\Extensions\PagesBuilder\Services\PageBuilderContactFormCatalogService;
use App\Extensions\PagesBuilder\Services\PageBuilderWidgetLocaleService;
use App\Extensions\PagesBuilder\Support\AbstractWidgetRenderer;
use App\Modules\Contact\Services\FormService;

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
            $translate = static fn(string $key, string $fallback = ''): string => PageBuilderWidgetLocaleService::translate('Contact', $key, $fallback);

            $normalizeVariant = static function (string $raw): string {
                $value = strtolower(trim($raw));
                return in_array($value, ['subtle', 'strong', 'dark'], true) ? $value : 'subtle';
            };

            $normalizeDesignInt = static function (mixed $raw, int $fallback, int $min, int $max): int {
                $value = (int) round((float) $raw);
                $safe = $value !== 0 || (string) $raw === '0' ? $value : $fallback;
                return max($min, min($max, $safe));
            };

            $title = trim((string) ($settings['title'] ?? $translate('footer_widget_contact_default_title')));
            $formSlug = trim((string) ($settings['formSlug'] ?? 'contact-main'));
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $variant = $normalizeVariant((string) ($settings['variant'] ?? 'subtle'));
            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle((string) ($settings['designBorderStyle'] ?? 'inherit'));
            $designBorderWidth = $normalizeDesignInt($settings['designBorderWidth'] ?? 1, 1, 0, 8);
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = $normalizeDesignInt($settings['designRadius'] ?? 20, 20, 0, 48);
            $designShadow = self::normalizeShadowPreset((string) ($settings['designShadow'] ?? 'inherit'));
            $sourceUrl = url(app()->request()->uri());
            $shortcodeContext = ['source_url' => $sourceUrl];
            $embeddedForm = '';
            $formCatalog = new PageBuilderContactFormCatalogService();
            $selectedBuilderForm = null;
            $blockDomId = preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) ($context['id'] ?? ''));
            $blockDomId = is_string($blockDomId) && $blockDomId !== '' ? $blockDomId : 'pb-contact-widget';

            foreach ($formCatalog->builderConfigForms() as $builderForm) {
                if (!is_array($builderForm)) {
                    continue;
                }

                $candidateSlug = strtolower(trim((string) ($builderForm['slug'] ?? '')));
                if ($candidateSlug === '') {
                    continue;
                }

                if ($selectedBuilderForm === null) {
                    $selectedBuilderForm = $builderForm;
                }

                if ($candidateSlug !== strtolower($formSlug)) {
                    continue;
                }

                $selectedBuilderForm = $builderForm;
                break;
            }

            if ($formSlug === '' && is_array($selectedBuilderForm)) {
                $formSlug = trim((string) ($selectedBuilderForm['slug'] ?? ''));
            }

            if (function_exists('flatcms_render_shortcode_tag')) {
                $embeddedForm = trim((string) flatcms_render_shortcode_tag('contact-form', ['slug' => $formSlug], $shortcodeContext));
                if ($embeddedForm === '' && $formSlug !== '') {
                    $embeddedForm = trim((string) flatcms_render_shortcode_tag('contact-form', [], $shortcodeContext));
                }
            }

            $shortcodeUnavailable = $embeddedForm !== '' && (
                str_contains($embeddedForm, 'flatcms-contact-unavailable')
                || str_contains($embeddedForm, 'contact_form_front_unavailable')
            );

            if (($embeddedForm === '' || $shortcodeUnavailable) && is_array($selectedBuilderForm)) {
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

                    $fieldType = strtolower(trim((string) ($field['type'] ?? 'text')));
                    if (!in_array($fieldType, ['text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date'], true)) {
                        $fieldType = 'text';
                    }

                    $fieldWidth = strtolower(trim((string) ($field['width'] ?? 'full'))) === 'half' ? 'half' : 'full';
                    $fieldOptions = $field['options'] ?? [];
                    if (!is_array($fieldOptions)) {
                        $fieldOptions = preg_split('/\r\n|\r|\n|,|;/', (string) $fieldOptions) ?: [];
                    }

                    $normalizedPreviewFields[] = [
                        'key' => $fieldKey,
                        'label' => $fieldLabel,
                        'type' => $fieldType,
                        'required' => !empty($field['required']),
                        'width' => $fieldWidth,
                        'placeholder' => trim((string) ($field['placeholder'] ?? '')),
                        'help' => trim((string) ($field['help'] ?? '')),
                        'options' => array_values(array_filter(array_map(
                            static fn(mixed $value): string => trim((string) $value),
                            $fieldOptions
                        ), static fn(string $value): bool => $value !== '')),
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

                $renderFallbackField = static function (array $field, int $index) use ($escape, $escapeAttr, $blockDomId, $resolveAutocomplete): string {
                    $fieldKey = trim((string) ($field['key'] ?? 'field'));
                    $fieldId = $blockDomId . '-' . $index . '-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $fieldKey);
                    $fieldId = is_string($fieldId) ? $fieldId : 'pb-contact-widget-field-' . $index;
                    $fieldName = 'cf[' . $fieldKey . ']';
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
                $attachmentsEnabled = !empty($attachments['enabled']);
                $attachmentsRequired = !empty($attachments['required']);
                $attachmentsMaxFiles = max(1, min(5, (int) ($attachments['maxFiles'] ?? 1)));
                $attachmentsMaxSizeMb = max(1, min(25, (int) ($attachments['maxSizeMb'] ?? 5)));
                $attachmentsExtensions = array_values(array_filter(array_map(
                    static fn(mixed $value): string => trim((string) $value),
                    is_array($attachments['extensions'] ?? null) ? $attachments['extensions'] : []
                ), static fn(string $value): bool => $value !== ''));
                $attachmentAccept = $attachmentsExtensions !== [] ? implode(',', array_map(static fn(string $ext): string => '.' . ltrim($ext, '.'), $attachmentsExtensions)) : '';
                $attachmentHintParts = [(string) $attachmentsMaxFiles, (string) $attachmentsMaxSizeMb . ' MB'];
                if ($attachmentsExtensions !== []) {
                    $attachmentHintParts[] = implode(', ', $attachmentsExtensions);
                }
                $attachmentHint = implode(' · ', $attachmentHintParts);

                $attachmentsHtml = '';
                if ($attachmentsEnabled) {
                    $attachmentsHtml = '<div class="form-group" data-contact-attachments>'
                        . '<label class="form-label" for="' . $escapeAttr($blockDomId . '-attachments') . '">'
                        . $escape(__('contact_form_attachments_input_label', 'Contact'))
                        . ($attachmentsRequired ? '<span class="flatcms-contact-required-mark" aria-hidden="true">*</span>' : '')
                        . '</label>'
                        . '<input id="' . $escapeAttr($blockDomId . '-attachments') . '" class="form-input flatcms-contact-attachments-input" type="file" name="attachments[]"' . ($attachmentsMaxFiles > 1 ? ' multiple' : '') . ($attachmentAccept !== '' ? ' accept="' . $escapeAttr($attachmentAccept) . '"' : '') . ($attachmentsRequired ? ' required' : '') . '>'
                        . '<small class="flatcms-contact-hint">' . $escape($attachmentHint) . '</small>'
                        . '</div>';
                }

                $consentLinksHtml = '';
                $selectedFormType = trim((string) ($selectedBuilderForm['formType'] ?? ''));
                if ($selectedFormType === FormService::FORM_TYPE_NEWSLETTER) {
                    $consentLinks = [];
                    $newsletterLegalUrl = trim((string) ($selectedBuilderForm['newsletterLegalUrl'] ?? ''));
                    $newsletterPrivacyUrl = trim((string) ($selectedBuilderForm['newsletterPrivacyUrl'] ?? ''));
                    if ($newsletterLegalUrl !== '') {
                        $consentLinks[] = '<a href="' . $escapeAttr($newsletterLegalUrl) . '" target="_blank" rel="noopener noreferrer">' . $escape(__('contact_form_consent_legal_label', 'Contact')) . '</a>';
                    }
                    if ($newsletterPrivacyUrl !== '') {
                        $consentLinks[] = '<a href="' . $escapeAttr($newsletterPrivacyUrl) . '" target="_blank" rel="noopener noreferrer">' . $escape(__('contact_form_consent_privacy_label', 'Contact')) . '</a>';
                    }
                    if ($consentLinks !== []) {
                        $consentLinksHtml = '<p class="flatcms-contact-consent-links"><span>'
                            . $escape(__('contact_form_consent_links_prefix', 'Contact'))
                            . '</span>'
                            . implode('<span>&middot;</span>', $consentLinks)
                            . '</p>';
                    }
                }

                $embeddedForm = '<section class="flatcms-contact-native flatcms-contact-embed">'
                    . '<form class="flatcms-contact-form flatcms-contact-native-form pb-contact-widget-form" method="post" action="' . $escapeAttr(function_exists('url') ? (string) url('/contact/send') : '/contact/send') . '"' . ($attachmentsEnabled ? ' enctype="multipart/form-data"' : '') . ' data-validation-required="' . $escapeAttr(__('contact_form_client_required_message', 'Contact')) . '">'
                    . (function_exists('csrf_field') ? (string) csrf_field() : '')
                    . '<input type="hidden" name="source_url" value="' . $escapeAttr($sourceUrl) . '">'
                    . '<input type="hidden" name="contact_form_id" value="' . $escapeAttr((string) ($selectedBuilderForm['id'] ?? '')) . '">'
                    . '<input type="hidden" name="contact_form_slug" value="' . $escapeAttr((string) ($selectedBuilderForm['slug'] ?? $formSlug)) . '">'
                    . '<div class="flatcms-contact-honeypot" aria-hidden="true">'
                    . '<label for="' . $escapeAttr($blockDomId . '-company') . '">' . $escape(__('contact_form_honeypot_company', 'Contact')) . '</label>'
                    . '<input id="' . $escapeAttr($blockDomId . '-company') . '" type="text" name="company" tabindex="-1" autocomplete="off">'
                    . '</div>'
                    . '<div class="flatcms-contact-custom-grid">' . implode('', $fieldsHtml) . '</div>'
                    . $attachmentsHtml
                    . $consentLinksHtml
                    . '<button type="submit" class="btn btn-primary pb-contact-widget-submit">' . $escape($submitLabel) . '</button>'
                    . '</form>'
                    . '</section>';
            }

            if ($embeddedForm === '') {
                $embeddedForm = '<div class="pb-form-card"><p>' . $escape(__('contact_form_front_unavailable', 'Contact')) . '</p></div>';
            }

            $widgetClasses = ['pb-contact-widget', 'pb-contact-widget-variant-' . $variant, 'pb-align', 'pb-align-' . $align];
            if ($useCustomDesign) {
                $widgetClasses[] = 'pb-contact-widget-has-design';
            }

            $html = '<div class="' . $escapeAttr(implode(' ', $widgetClasses)) . '">';
            if ($title !== '') {
                $html .= '<strong class="pb-contact-widget-title">' . $escape($title) . '</strong>';
            }
            $html .= '<div class="pb-contact-widget-embed">' . $embeddedForm . '</div>';
            $html .= '</div>';

            $css = '';
            $safeBlockId = self::blockId($context);
            if ($useCustomDesign && $safeBlockId !== '') {
                $selector = self::blockSelector($safeBlockId, '.pb-contact-widget-has-design');
                $declarations = [
                    '--pb-contact-widget-border-width:' . $escapeAttr((string) $designBorderWidth) . 'px;',
                    '--pb-contact-widget-radius:' . $escapeAttr((string) $designRadius) . 'px;',
                ];

                if ($designSurfaceColor !== '') {
                    $declarations[] = '--pb-contact-widget-surface:' . $escapeAttr($designSurfaceColor) . ';';
                    $declarations[] = '--pb-contact-widget-input-bg:' . $escapeAttr($designSurfaceColor) . ';';
                }
                if ($designTextColor !== '') {
                    $declarations[] = '--pb-contact-widget-text:' . $escapeAttr($designTextColor) . ';';
                    $declarations[] = '--pb-contact-widget-label-color:' . $escapeAttr($designTextColor) . ';';
                    $declarations[] = '--pb-contact-widget-input-color:' . $escapeAttr($designTextColor) . ';';
                    $declarations[] = '--pb-contact-widget-placeholder-color:' . $escapeAttr($designTextColor) . ';';
                }
                if ($designBorderStyle !== 'inherit') {
                    $declarations[] = '--pb-contact-widget-border-style:' . $escapeAttr($designBorderStyle) . ';';
                }
                if ($designBorderColor !== '') {
                    $declarations[] = '--pb-contact-widget-border-color:' . $escapeAttr($designBorderColor) . ';';
                    $declarations[] = '--pb-contact-widget-input-border:' . $escapeAttr($designBorderColor) . ';';
                }

                $shadowValue = self::shadowValue($designShadow);
                if ($shadowValue !== '') {
                    $declarations[] = '--pb-contact-widget-shadow:' . $escapeAttr($shadowValue) . ';';
                }

                $css = $selector . '{' . implode('', $declarations) . '}';
            }

            return ['html' => $html, 'css' => $css];
        };
    }
}
