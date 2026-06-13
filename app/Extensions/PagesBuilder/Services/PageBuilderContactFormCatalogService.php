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

use App\Core\FlatFile;
use App\Core\I18n;
use App\Core\Security\Turnstile;
use App\Modules\Contact\Services\FormService;

final class PageBuilderContactFormCatalogService
{
    public const SCOPE_CONTACT = 'contact';
    public const SCOPE_NEWSLETTER = 'newsletter';

    private ?FormService $forms = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function builderConfigForms(?string $scope = null): array
    {
        $forms = [];
        $source = $this->builderVisibleForms($scope);
        foreach ($source as $form) {
            $forms[] = $this->normalizeBuilderForm($form);
        }

        return $forms;
    }

    /**
     * @return array{options: array<int, string>, optionLabels: array<string, string>, default: string}
     */
    public function fieldChoices(string $scope, string $fallbackSlug): array
    {
        $forms = $this->builderVisibleForms($scope);
        $options = [];
        $optionLabels = [];

        foreach ($forms as $form) {
            $slug = trim((string) ($form['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $options[] = $slug;
            $optionLabels[$slug] = trim((string) ($form['name'] ?? '')) !== ''
                ? trim((string) ($form['name'] ?? ''))
                : $slug;
        }

        $default = $this->preferredSlug($scope, $fallbackSlug);
        if ($options === []) {
            $options[] = $default;
            $optionLabels[$default] = $default;
        } elseif (!in_array($default, $options, true)) {
            $options[] = $default;
            $optionLabels[$default] = $default;
        }

        return [
            'options' => array_values(array_unique($options)),
            'optionLabels' => $optionLabels,
            'default' => $default,
        ];
    }

    public function preferredSlug(string $scope, string $fallbackSlug): string
    {
        foreach ($this->builderVisibleForms($scope) as $form) {
            $slug = trim((string) ($form['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            if (!empty($form['is_default'])) {
                return $slug;
            }
        }

        foreach ($this->builderVisibleForms($scope) as $form) {
            $slug = trim((string) ($form['slug'] ?? ''));
            if ($slug !== '') {
                return $slug;
            }
        }

        return trim($fallbackSlug) !== '' ? trim($fallbackSlug) : 'contact-main';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findFormBySlug(string $slug): ?array
    {
        $target = strtolower(trim($slug));
        if ($target === '') {
            return null;
        }

        foreach ($this->allForms() as $form) {
            $candidate = strtolower(trim((string) ($form['slug'] ?? '')));
            if ($candidate === $target) {
                return $form;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allForms(): array
    {
        if (!class_exists(FormService::class)) {
            return [];
        }

        try {
            return $this->formsService()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formsForScope(string $scope): array
    {
        $safeScope = strtolower(trim($scope));

        return array_values(array_filter(
            $this->allForms(),
            fn(array $form): bool => $this->matchesScope($form, $safeScope)
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function builderVisibleForms(?string $scope = null): array
    {
        $forms = $scope === null ? $this->allForms() : $this->formsForScope($scope);
        $forms = array_values(array_filter(
            $forms,
            static function (array $form): bool {
                return !empty($form['is_active']) && trim((string) ($form['slug'] ?? '')) !== '';
            }
        ));

        usort($forms, static function (array $a, array $b): int {
            $aDefault = !empty($a['is_default']) ? 1 : 0;
            $bDefault = !empty($b['is_default']) ? 1 : 0;
            if ($aDefault !== $bDefault) {
                return $bDefault <=> $aDefault;
            }

            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return $forms;
    }

    /**
     * @param array<string, mixed> $form
     */
    private function matchesScope(array $form, string $scope): bool
    {
        $type = trim((string) ($form['form_type'] ?? FormService::FORM_TYPE_CONTACT));
        if ($scope === self::SCOPE_NEWSLETTER) {
            return $type === FormService::FORM_TYPE_NEWSLETTER;
        }

        return $type !== FormService::FORM_TYPE_NEWSLETTER;
    }

    /**
     * @param array<string, mixed> $form
     * @return array<string, mixed>
     */
    private function normalizeBuilderForm(array $form): array
    {
        $slug = trim((string) ($form['slug'] ?? ''));
        $id = trim((string) ($form['id'] ?? ''));
        return [
            'id' => $id,
            'slug' => $slug,
            'name' => trim((string) ($form['name'] ?? '')) !== '' ? trim((string) ($form['name'] ?? '')) : $slug,
            'description' => trim((string) ($form['description'] ?? '')),
            'submitLabel' => trim((string) ($form['submit_label'] ?? '')),
            'successMessage' => trim((string) ($form['success_message'] ?? '')),
            'formType' => trim((string) ($form['form_type'] ?? FormService::FORM_TYPE_CONTACT)),
            'isActive' => !empty($form['is_active']),
            'isDefault' => !empty($form['is_default']),
            'previewFields' => $this->buildPreviewFields($form),
            'attachments' => $this->buildAttachmentsConfig($form),
            'newsletterLegalUrl' => trim((string) ($form['newsletter_legal_url'] ?? '')),
            'newsletterPrivacyUrl' => trim((string) ($form['newsletter_privacy_url'] ?? '')),
            'captchaEnabled' => $this->isCaptchaEnabled(),
            'editUrl' => $id !== '' && function_exists('url')
                ? (string) url('/admin/contact/forms/' . rawurlencode($id) . '/edit')
                : '',
        ];
    }

    /**
     * @param array<string, mixed> $form
     * @return array<int, array<string, mixed>>
     */
    private function buildPreviewFields(array $form): array
    {
        $fields = is_array($form['fields'] ?? null) ? $form['fields'] : [];
        $customFields = is_array($form['custom_fields'] ?? null) ? $form['custom_fields'] : [];
        $hasCustomDefinition = false;

        foreach ($customFields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));
            $label = trim((string) ($field['label'] ?? ''));
            if ($key !== '' && $label !== '') {
                $hasCustomDefinition = true;
                break;
            }
        }

        $previewFields = [];
        $append = static function (array $items) use (&$previewFields): void {
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $previewFields[] = $item;
            }
        };

        $baseFieldMap = [];
        if (!$hasCustomDefinition && !empty($fields['name'])) {
            $baseFieldMap['name'] = [
                $this->baseField('name', $this->translate('contact_field_name'), 'text', true, 'half', $this->translate('contact_form_name_placeholder')),
                $this->baseField('first_name', $this->translate('contact_field_first_name'), 'text', false, 'half', $this->translate('contact_form_first_name_placeholder')),
            ];
        }
        if (!$hasCustomDefinition && !empty($fields['email'])) {
            $baseFieldMap['email'] = [
                $this->baseField('email', $this->translate('contact_field_email'), 'email', true, 'full', $this->translate('contact_form_email_placeholder')),
            ];
        }
        if (!$hasCustomDefinition && !empty($fields['subject'])) {
            $baseFieldMap['subject'] = [
                $this->baseField('subject', $this->translate('contact_subject'), 'text', true, 'full', $this->translate('contact_form_subject_placeholder')),
            ];
        }
        if (!$hasCustomDefinition && !empty($fields['phone'])) {
            $baseFieldMap['phone'] = [
                $this->baseField('phone', $this->translate('contact_field_phone'), 'tel', false, 'half', $this->translate('contact_form_phone_placeholder')),
            ];
        }
        if (!$hasCustomDefinition && !empty($fields['message'])) {
            $baseFieldMap['message'] = [
                $this->baseField('message', $this->translate('contact_field_message'), 'textarea', true, 'full', $this->translate('contact_form_message_placeholder')),
            ];
        }

        $customByPosition = [
            'name' => [],
            'email' => [],
            'subject' => [],
            'phone' => [],
            'message' => [],
            'end' => [],
        ];

        foreach ($customFields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));
            $label = trim((string) ($field['label'] ?? ''));
            if ($key === '' || $label === '') {
                continue;
            }

            $position = strtolower(trim((string) ($field['position_after'] ?? 'message')));
            if (!array_key_exists($position, $customByPosition)) {
                $position = 'message';
            }

            $normalizedField = [
                'key' => $key,
                'label' => $label,
                'type' => $this->normalizeFieldType((string) ($field['type'] ?? 'text')),
                'required' => !empty($field['required']),
                'width' => $this->normalizeFieldWidth((string) ($field['width'] ?? 'full')),
                'placeholder' => trim((string) ($field['placeholder'] ?? '')),
                'help' => trim((string) ($field['help'] ?? '')),
                'options' => $this->normalizeFieldOptions($field['options'] ?? []),
            ];

            $customByPosition[$position][] = $normalizedField;
        }

        foreach (['name', 'email', 'subject', 'phone', 'message'] as $anchor) {
            $baseEntries = $baseFieldMap[$anchor] ?? [];
            foreach ($baseEntries as $entry) {
                $previewFields[] = $entry;
            }

            $append($customByPosition[$anchor] ?? []);
        }
        $append($customByPosition['end'] ?? []);

        return $previewFields;
    }

    /**
     * @param array<string, mixed> $form
     * @return array<string, mixed>
     */
    private function buildAttachmentsConfig(array $form): array
    {
        $attachments = is_array($form['attachments'] ?? null) ? $form['attachments'] : [];

        return [
            'enabled' => !empty($attachments['enabled']),
            'required' => !empty($attachments['required']),
            'maxFiles' => max(1, min(5, (int) ($attachments['max_files'] ?? 1))),
            'maxSizeMb' => max(1, min(25, (int) ($attachments['max_size_mb'] ?? 5))),
            'extensions' => $this->normalizeFieldOptions($attachments['allowed_extensions'] ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseField(string $key, string $label, string $type, bool $required, string $width, string $placeholder = ''): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => $this->normalizeFieldType($type),
            'required' => $required,
            'width' => $this->normalizeFieldWidth($width),
            'placeholder' => $placeholder,
            'help' => '',
            'options' => [],
        ];
    }

    /**
     * @param mixed $options
     * @return array<int, string>
     */
    private function normalizeFieldOptions(mixed $options): array
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
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeFieldType(string $type): string
    {
        $safeType = strtolower(trim($type));
        $allowed = ['text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date'];
        return in_array($safeType, $allowed, true) ? $safeType : 'text';
    }

    private function normalizeFieldWidth(string $width): string
    {
        return strtolower(trim($width)) === 'half' ? 'half' : 'full';
    }

    private function translate(string $key): string
    {
        if (class_exists(I18n::class)) {
            I18n::load('Contact');
        }

        return function_exists('__') ? (string) __($key, 'Contact') : $key;
    }

    private function formsService(): FormService
    {
        return $this->forms ??= new FormService();
    }

    private function isCaptchaEnabled(): bool
    {
        $settings = FlatFile::settings();
        $contactCaptchaEnabled = (int) ($settings['contact_enable_captcha'] ?? 0) === 1;
        if (!$contactCaptchaEnabled || !class_exists(Turnstile::class)) {
            return false;
        }

        try {
            $turnstile = new Turnstile();
            return $turnstile->isEnabled() && trim((string) $turnstile->siteKey()) !== '';
        } catch (\Throwable) {
            return false;
        }
    }
}
