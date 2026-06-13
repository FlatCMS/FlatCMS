<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Contact\Services;

use App\Core\FlatFile;
use App\Core\I18n;

final class ContactFormTranslationService
{
    /**
     * @var array<int, string>
     */
    private const TRANSLATABLE_TEXT_FIELDS = [
        'submit_label',
        'success_message',
    ];

    /**
     * @var array<int, string>
     */
    private const TRANSLATABLE_FIELD_FIELDS = [
        'label',
        'placeholder',
        'help',
        'options',
    ];

    /**
     * @return array<int, string>
     */
    public function supportedLocales(): array
    {
        return I18n::getSupportedLocales();
    }

    public function normalizeLocale(string $locale): string
    {
        $value = trim($locale);
        if ($value === '') {
            return '';
        }

        $supported = $this->supportedLocales();
        foreach ($supported as $candidate) {
            if (strcasecmp($candidate, $value) === 0) {
                return $candidate;
            }
        }

        $prefix = strtolower(substr($value, 0, 2));
        if ($prefix === '') {
            return '';
        }

        foreach ($supported as $candidate) {
            if (strtolower(substr($candidate, 0, 2)) === $prefix) {
                return $candidate;
            }
        }

        return '';
    }

    public function defaultLocale(?array $settings = null): string
    {
        $settings = is_array($settings) ? $settings : FlatFile::settings();
        $locale = $this->normalizeLocale((string) ($settings['default_language'] ?? ''));
        if ($locale !== '') {
            return $locale;
        }

        $supported = $this->supportedLocales();
        return $supported[0] ?? 'fr-FR';
    }

    public function localeLabel(string $locale, ?string $uiLocale = null): string
    {
        $normalized = $this->normalizeLocale($locale);
        if ($normalized === '') {
            return trim($locale);
        }

        $label = I18n::getLocalizedLanguageName($normalized, $uiLocale ?? I18n::getLocale());
        return $label !== '' ? $label : $normalized;
    }

    /**
     * @param array<string, mixed> $form
     * @return array<string, mixed>
     */
    public function normalizeForm(array $form, ?array $settings = null): array
    {
        $sourceFields = $this->normalizeSourceFields($form['custom_fields'] ?? []);
        $sourceLocale = $this->normalizeLocale((string) ($form['source_locale'] ?? ''));
        if ($sourceLocale === '') {
            $sourceLocale = $this->defaultLocale($settings);
        }

        $rawTranslations = is_array($form['translations'] ?? null) ? $form['translations'] : [];
        $translations = [];

        foreach ($this->supportedLocales() as $locale) {
            $rawEntry = is_array($rawTranslations[$locale] ?? null) ? $rawTranslations[$locale] : [];
            $entry = [
                'submit_label' => trim((string) ($rawEntry['submit_label'] ?? '')),
                'success_message' => trim((string) ($rawEntry['success_message'] ?? '')),
                'fields' => $this->normalizeFieldTranslations(
                    $rawEntry['fields'] ?? $rawEntry['custom_fields'] ?? [],
                    $sourceFields
                ),
            ];

            if ($locale === $sourceLocale || $this->hasMeaningfulTranslation($entry)) {
                $translations[$locale] = $entry;
            }
        }

        $form['source_locale'] = $sourceLocale;
        $form['custom_fields'] = $sourceFields;
        $form['translations'] = $translations;

        return $form;
    }

    /**
     * @param array<string, mixed> $form
     * @return array<string, mixed>
     */
    public function resolveForLocale(array $form, string $locale, ?array $settings = null): array
    {
        $normalized = $this->normalizeForm($form, $settings);
        $sourceLocale = (string) ($normalized['source_locale'] ?? $this->defaultLocale($settings));
        $resolvedLocale = $this->normalizeLocale($locale);
        if ($resolvedLocale === '') {
            $resolvedLocale = $sourceLocale;
        }

        $translations = is_array($normalized['translations'] ?? null) ? $normalized['translations'] : [];
        $targetEntry = is_array($translations[$resolvedLocale] ?? null) ? $translations[$resolvedLocale] : [];
        $targetFieldTranslations = is_array($targetEntry['fields'] ?? null) ? $targetEntry['fields'] : [];

        foreach (self::TRANSLATABLE_TEXT_FIELDS as $field) {
            $resolved = trim((string) ($targetEntry[$field] ?? ''));
            if ($resolved !== '') {
                $normalized[$field] = $resolved;
            }
        }

        $normalized['custom_fields'] = $this->applyFieldTranslations(
            is_array($normalized['custom_fields'] ?? null) ? $normalized['custom_fields'] : [],
            $targetFieldTranslations
        );
        $normalized['resolved_locale'] = $resolvedLocale;

        return $normalized;
    }

    /**
     * @param array<string, mixed> $form
     * @return array<string, mixed>
     */
    public function buildEditorState(array $form, string $activeLocale = '', ?string $uiLocale = null, ?array $settings = null): array
    {
        $normalized = $this->normalizeForm($form, $settings);
        $sourceLocale = (string) ($normalized['source_locale'] ?? $this->defaultLocale($settings));
        $active = $this->normalizeLocale($activeLocale);
        if ($active === '') {
            $active = $sourceLocale;
        }

        $tabs = [];
        $panels = [];
        $translations = is_array($normalized['translations'] ?? null) ? $normalized['translations'] : [];

        foreach ($this->supportedLocales() as $locale) {
            $entry = is_array($translations[$locale] ?? null) ? $translations[$locale] : [];
            $status = $locale === $sourceLocale ? 'source' : ($this->hasMeaningfulTranslation($entry) ? 'translated' : 'missing');
            $formLabels = $this->buildFormLabels($locale);

            $tabs[] = [
                'code' => $locale,
                'label' => $this->localeLabel($locale, $uiLocale),
                'status' => $status,
                'is_source' => $locale === $sourceLocale,
                'is_active' => $locale === $active,
                'form_labels' => $formLabels,
            ];

            $panels[$locale] = [
                'submit_label' => $locale === $sourceLocale
                    ? trim((string) ($normalized['submit_label'] ?? ''))
                    : trim((string) ($entry['submit_label'] ?? '')),
                'success_message' => $locale === $sourceLocale
                    ? trim((string) ($normalized['success_message'] ?? ''))
                    : trim((string) ($entry['success_message'] ?? '')),
                'fields' => $this->buildEditorFieldsForLocale($normalized, $locale),
                'form_labels' => $formLabels,
            ];
        }

        return [
            'source_locale' => $sourceLocale,
            'active_locale' => $active,
            'tabs' => $tabs,
            'panels' => $panels,
        ];
    }

    /**
     * @param array<string, mixed> $sourceValues
     * @return array<string, mixed>
     */
    public function prepareSubmittedTranslations(array $submitted, array $sourceValues, string $sourceLocale, ?array $settings = null): array
    {
        $normalizedSourceLocale = $this->normalizeLocale($sourceLocale);
        if ($normalizedSourceLocale === '') {
            $normalizedSourceLocale = $this->defaultLocale($settings);
        }

        $normalizedSourceValues = [
            'submit_label' => trim((string) ($sourceValues['submit_label'] ?? '')),
            'success_message' => trim((string) ($sourceValues['success_message'] ?? '')),
            'custom_fields' => $this->normalizeSourceFields($sourceValues['custom_fields'] ?? []),
        ];

        $translations = [];
        foreach ($this->supportedLocales() as $locale) {
            if ($locale === $normalizedSourceLocale) {
                continue;
            }

            $rawEntry = is_array($submitted[$locale] ?? null) ? $submitted[$locale] : [];
            $entry = [
                'submit_label' => trim((string) ($rawEntry['submit_label'] ?? '')),
                'success_message' => trim((string) ($rawEntry['success_message'] ?? '')),
                'fields' => $this->normalizeFieldTranslations(
                    $rawEntry['fields'] ?? $rawEntry['custom_fields'] ?? [],
                    $normalizedSourceValues['custom_fields']
                ),
            ];

            if ($this->hasMeaningfulTranslation($entry)) {
                $translations[$locale] = $entry;
            }
        }

        return [
            'source_locale' => $normalizedSourceLocale,
            'source_values' => $normalizedSourceValues,
            'translations' => $translations,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function buildFormLabels(string $locale): array
    {
        $contactTranslations = $this->loadTranslationsForLocale('Contact', $locale);
        $coreTranslations = $this->loadTranslationsForLocale('Core', $locale);

        return [
            'translations' => $this->translationValue($contactTranslations, 'translations', __('translations', 'Contact')),
            'translation_source' => $this->translationValue($contactTranslations, 'translation_source', __('translation_source', 'Contact')),
            'translation_missing' => $this->translationValue($contactTranslations, 'translation_missing', __('translation_missing', 'Contact')),
            'translation_ready' => $this->translationValue($contactTranslations, 'translation_ready', __('translation_ready', 'Contact')),
            'contact_form_translations_help' => $this->translationValue($contactTranslations, 'contact_form_translations_help', __('contact_form_translations_help', 'Contact')),
            'close' => $this->translationValue($coreTranslations, 'close', __('close', 'Core')),
            'save' => $this->translationValue($coreTranslations, 'save', __('save', 'Core')),
            'contact_form_submit_label_admin' => $this->translationValue($contactTranslations, 'contact_form_submit_label_admin', __('contact_form_submit_label_admin', 'Contact')),
            'contact_form_success_message_label' => $this->translationValue($contactTranslations, 'contact_form_success_message_label', __('contact_form_success_message_label', 'Contact')),
            'contact_form_submit_placeholder' => $this->translationValue($contactTranslations, 'contact_form_submit_placeholder', __('contact_form_submit_placeholder', 'Contact')),
            'contact_form_success_placeholder' => $this->translationValue($contactTranslations, 'contact_form_success_placeholder', __('contact_form_success_placeholder', 'Contact')),
            'contact_form_builder_preview_title' => $this->translationValue($contactTranslations, 'contact_form_builder_preview_title', __('contact_form_builder_preview_title', 'Contact')),
            'contact_form_builder_preview_help' => $this->translationValue($contactTranslations, 'contact_form_builder_preview_help', __('contact_form_builder_preview_help', 'Contact')),
            'contact_form_builder_unnamed_field' => $this->translationValue($contactTranslations, 'contact_form_builder_unnamed_field', __('contact_form_builder_unnamed_field', 'Contact')),
            'contact_form_custom_field_label' => $this->translationValue($contactTranslations, 'contact_form_custom_field_label', __('contact_form_custom_field_label', 'Contact')),
            'contact_form_custom_move_up' => $this->translationValue($contactTranslations, 'contact_form_custom_move_up', __('contact_form_custom_move_up', 'Contact')),
            'contact_form_custom_move_down' => $this->translationValue($contactTranslations, 'contact_form_custom_move_down', __('contact_form_custom_move_down', 'Contact')),
            'contact_form_custom_label' => $this->translationValue($contactTranslations, 'contact_form_custom_label', __('contact_form_custom_label', 'Contact')),
            'contact_form_custom_key' => $this->translationValue($contactTranslations, 'contact_form_custom_key', __('contact_form_custom_key', 'Contact')),
            'contact_form_custom_type' => $this->translationValue($contactTranslations, 'contact_form_custom_type', __('contact_form_custom_type', 'Contact')),
            'contact_form_custom_required' => $this->translationValue($contactTranslations, 'contact_form_custom_required', __('contact_form_custom_required', 'Contact')),
            'contact_form_custom_width_label' => $this->translationValue($contactTranslations, 'contact_form_custom_width_label', __('contact_form_custom_width_label', 'Contact')),
            'contact_form_custom_placeholder' => $this->translationValue($contactTranslations, 'contact_form_custom_placeholder', __('contact_form_custom_placeholder', 'Contact')),
            'contact_form_custom_help' => $this->translationValue($contactTranslations, 'contact_form_custom_help', __('contact_form_custom_help', 'Contact')),
            'contact_form_custom_options_label' => $this->translationValue($contactTranslations, 'contact_form_custom_options_label', __('contact_form_custom_options_label', 'Contact')),
            'contact_form_custom_options_placeholder' => $this->translationValue($contactTranslations, 'contact_form_custom_options_placeholder', __('contact_form_custom_options_placeholder', 'Contact')),
            'contact_form_translation_fields_help' => $this->translationValue($contactTranslations, 'contact_form_translation_fields_help', __('contact_form_translation_fields_help', 'Contact')),
            'contact_form_custom_type_text' => $this->translationValue($contactTranslations, 'contact_form_custom_type_text', __('contact_form_custom_type_text', 'Contact')),
            'contact_form_custom_type_email' => $this->translationValue($contactTranslations, 'contact_form_custom_type_email', __('contact_form_custom_type_email', 'Contact')),
            'contact_form_custom_type_tel' => $this->translationValue($contactTranslations, 'contact_form_custom_type_tel', __('contact_form_custom_type_tel', 'Contact')),
            'contact_form_custom_type_url' => $this->translationValue($contactTranslations, 'contact_form_custom_type_url', __('contact_form_custom_type_url', 'Contact')),
            'contact_form_custom_type_number' => $this->translationValue($contactTranslations, 'contact_form_custom_type_number', __('contact_form_custom_type_number', 'Contact')),
            'contact_form_custom_type_textarea' => $this->translationValue($contactTranslations, 'contact_form_custom_type_textarea', __('contact_form_custom_type_textarea', 'Contact')),
            'contact_form_custom_type_select' => $this->translationValue($contactTranslations, 'contact_form_custom_type_select', __('contact_form_custom_type_select', 'Contact')),
            'contact_form_custom_type_radio' => $this->translationValue($contactTranslations, 'contact_form_custom_type_radio', __('contact_form_custom_type_radio', 'Contact')),
            'contact_form_custom_type_checkbox' => $this->translationValue($contactTranslations, 'contact_form_custom_type_checkbox', __('contact_form_custom_type_checkbox', 'Contact')),
            'contact_form_custom_type_date' => $this->translationValue($contactTranslations, 'contact_form_custom_type_date', __('contact_form_custom_type_date', 'Contact')),
        ];
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function hasMeaningfulTranslation(array $entry): bool
    {
        foreach (self::TRANSLATABLE_TEXT_FIELDS as $field) {
            if (trim((string) ($entry[$field] ?? '')) !== '') {
                return true;
            }
        }

        $fields = is_array($entry['fields'] ?? null) ? $entry['fields'] : [];
        foreach ($fields as $fieldEntry) {
            if (!is_array($fieldEntry)) {
                continue;
            }

            if (!$this->isEmptyFieldTranslation($fieldEntry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadTranslationsForLocale(string $namespace, string $locale): array
    {
        $resolvedLocale = $this->normalizeLocale($locale);
        if ($resolvedLocale === '') {
            $resolvedLocale = $this->defaultLocale();
        }

        $path = I18n::resolveTranslationPathForNamespace($namespace, $resolvedLocale);
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function translationValue(array $catalog, string $key, string $fallback): string
    {
        $value = $catalog[$key] ?? null;
        return is_string($value) && trim($value) !== '' ? $value : $fallback;
    }

    /**
     * @param mixed $fields
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSourceFields(mixed $fields): array
    {
        if (!is_array($fields)) {
            return [];
        }

        $normalized = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $field['key'] = $key;
            $field['label'] = trim((string) ($field['label'] ?? ''));
            $field['placeholder'] = trim((string) ($field['placeholder'] ?? ''));
            $field['help'] = trim((string) ($field['help'] ?? ''));
            $field['options'] = $this->normalizeOptions($field['options'] ?? []);
            $normalized[] = $field;
        }

        return $normalized;
    }

    /**
     * @param mixed $translations
     * @param array<int, array<string, mixed>> $sourceFields
     * @return array<string, array<string, mixed>>
     */
    private function normalizeFieldTranslations(mixed $translations, array $sourceFields): array
    {
        $sourceKeys = [];
        foreach ($sourceFields as $field) {
            $key = trim((string) ($field['key'] ?? ''));
            if ($key !== '') {
                $sourceKeys[$key] = true;
            }
        }

        $normalized = [];
        if (!is_array($translations)) {
            return $normalized;
        }

        $items = array_is_list($translations)
            ? $translations
            : array_map(
                static function (string $key, mixed $value): array {
                    if (!is_array($value)) {
                        $value = [];
                    }
                    $value['key'] = $value['key'] ?? $key;
                    return $value;
                },
                array_keys($translations),
                array_values($translations)
            );

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = trim((string) ($item['key'] ?? ''));
            if ($key === '' || !isset($sourceKeys[$key])) {
                continue;
            }

            $entry = [
                'label' => trim((string) ($item['label'] ?? '')),
                'placeholder' => trim((string) ($item['placeholder'] ?? '')),
                'help' => trim((string) ($item['help'] ?? '')),
                'options' => $this->normalizeOptions($item['options'] ?? []),
            ];

            if (!$this->isEmptyFieldTranslation($entry)) {
                $normalized[$key] = $entry;
            }
        }

        return $normalized;
    }

    /**
     * @param array<int, array<string, mixed>> $sourceFields
     * @param array<string, array<string, mixed>> $fieldTranslations
     * @return array<int, array<string, mixed>>
     */
    private function applyFieldTranslations(array $sourceFields, array $fieldTranslations): array
    {
        $localized = [];
        foreach ($sourceFields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));
            $entry = is_array($fieldTranslations[$key] ?? null) ? $fieldTranslations[$key] : [];
            if (trim((string) ($entry['label'] ?? '')) !== '') {
                $field['label'] = trim((string) $entry['label']);
            }
            if (trim((string) ($entry['placeholder'] ?? '')) !== '') {
                $field['placeholder'] = trim((string) $entry['placeholder']);
            }
            if (trim((string) ($entry['help'] ?? '')) !== '') {
                $field['help'] = trim((string) $entry['help']);
            }
            if ($this->normalizeOptions($entry['options'] ?? []) !== []) {
                $field['options'] = $this->normalizeOptions($entry['options'] ?? []);
            }

            $localized[] = $field;
        }

        return $localized;
    }

    /**
     * @param array<string, mixed> $normalizedForm
     * @return array<int, array<string, mixed>>
     */
    private function buildEditorFieldsForLocale(array $normalizedForm, string $locale): array
    {
        $sourceLocale = (string) ($normalizedForm['source_locale'] ?? $this->defaultLocale());
        $sourceFields = is_array($normalizedForm['custom_fields'] ?? null) ? $normalizedForm['custom_fields'] : [];

        if ($locale === $sourceLocale) {
            return $sourceFields;
        }

        $translations = is_array($normalizedForm['translations'] ?? null) ? $normalizedForm['translations'] : [];
        $entry = is_array($translations[$locale] ?? null) ? $translations[$locale] : [];
        $fieldTranslations = is_array($entry['fields'] ?? null) ? $entry['fields'] : [];

        $fields = [];
        foreach ($sourceFields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $translation = is_array($fieldTranslations[$key] ?? null) ? $fieldTranslations[$key] : [];
            $options = $this->normalizeOptions($translation['options'] ?? []);
            $sourceOptions = $this->normalizeOptions($field['options'] ?? []);
            $type = strtolower(trim((string) ($field['type'] ?? 'text')));

            $fields[] = [
                'key' => $key,
                'type' => $type,
                'source_label' => trim((string) ($field['label'] ?? '')),
                'label' => trim((string) ($translation['label'] ?? '')),
                'placeholder' => trim((string) ($translation['placeholder'] ?? '')),
                'help' => trim((string) ($translation['help'] ?? '')),
                'options' => $options,
                'show_options' => in_array($type, ['select', 'radio'], true) || ($type === 'checkbox' && $sourceOptions !== []),
            ];
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function isEmptyFieldTranslation(array $entry): bool
    {
        if (trim((string) ($entry['label'] ?? '')) !== '') {
            return false;
        }
        if (trim((string) ($entry['placeholder'] ?? '')) !== '') {
            return false;
        }
        if (trim((string) ($entry['help'] ?? '')) !== '') {
            return false;
        }

        return $this->normalizeOptions($entry['options'] ?? []) === [];
    }

    /**
     * @param mixed $options
     * @return array<int, string>
     */
    private function normalizeOptions(mixed $options): array
    {
        if (is_string($options)) {
            $options = preg_split('/\r\n|\r|\n|,|;/', $options) ?: [];
        }

        if (!is_array($options)) {
            return [];
        }

        $normalized = [];
        foreach ($options as $option) {
            $value = trim((string) $option);
            if ($value === '') {
                continue;
            }
            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }
}
