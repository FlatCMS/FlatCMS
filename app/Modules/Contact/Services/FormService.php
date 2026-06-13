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

class FormService
{
    public const FORM_TYPE_CONTACT = 'contact';
    public const FORM_TYPE_NEWSLETTER = 'newsletter_rgpd';
    public const FORM_TYPE_QUOTE = 'quote';
    public const FORM_TYPE_SUPPORT = 'support';

    /**
     * @var array<int, string>
     */
    private const FORM_TYPES = [
        self::FORM_TYPE_CONTACT,
        self::FORM_TYPE_NEWSLETTER,
        self::FORM_TYPE_QUOTE,
        self::FORM_TYPE_SUPPORT,
    ];

    /**
     * @var array<int, string>
     */
    private const CUSTOM_FIELD_TYPES = [
        'text',
        'email',
        'tel',
        'url',
        'number',
        'textarea',
        'select',
        'radio',
        'checkbox',
        'date',
    ];

    /**
     * @var array<int, string>
     */
    private const CUSTOM_FIELD_POSITIONS = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'end',
    ];

    /**
     * @var array<int, string>
     */
    private const CUSTOM_FIELD_WIDTHS = [
        'full',
        'half',
    ];

    /**
     * @var array<int, string>
     */
    private const DEFAULT_ATTACHMENT_EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'jpg',
        'jpeg',
        'png',
        'webp',
        'zip',
    ];

    private FlatFile $storage;
    private ContactFormTranslationService $translations;

    public function __construct()
    {
        $this->storage = FlatFile::for('core/contact_forms');
        $this->translations = new ContactFormTranslationService();
    }

    /**
     * @return array<int, string>
     */
    public function allowedCustomFieldTypes(): array
    {
        return self::CUSTOM_FIELD_TYPES;
    }

    /**
     * @return array<int, string>
     */
    public function defaultAttachmentExtensions(): array
    {
        return self::DEFAULT_ATTACHMENT_EXTENSIONS;
    }

    /**
     * @return array<int, string>
     */
    public function allowedFormTypes(): array
    {
        return self::FORM_TYPES;
    }

    public function sanitizeFormType(string $value): string
    {
        $value = strtolower(trim($value));
        if (in_array($value, self::FORM_TYPES, true)) {
            return $value;
        }

        return self::FORM_TYPE_CONTACT;
    }

    /**
     * @return array<int, string>
     */
    public function allowedCustomFieldPositions(): array
    {
        return self::CUSTOM_FIELD_POSITIONS;
    }

    /**
     * @return array<int, string>
     */
    public function allowedCustomFieldWidths(): array
    {
        return self::CUSTOM_FIELD_WIDTHS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $this->ensureDefaultIntegrity();
        return $this->fetchAllNormalized();
    }

    public function find(string $id): ?array
    {
        $item = $this->storage->find($id);
        if (!is_array($item)) {
            return null;
        }

        return $this->normalizeRecord($item);
    }

    public function getDefault(): ?array
    {
        foreach ($this->all() as $form) {
            if (!empty($form['is_default'])) {
                return $form;
            }
        }

        return null;
    }

    public function ensureSeed(): void
    {
        if ($this->storage->count() > 0) {
            $this->ensureDefaultIntegrity();
            return;
        }

        $this->storage->create($this->sanitizePayload([
            'name' => 'Formulaire principal',
            'slug' => 'contact-main',
            'description' => '',
            'recipient_email' => '',
            'submit_label' => '',
            'success_message' => '',
            'is_active' => true,
            'is_default' => true,
            'fields' => [
                'subject' => true,
                'phone' => false,
            ],
            'custom_fields' => [],
            'attachments' => [
                'enabled' => false,
                'required' => false,
                'max_files' => 1,
                'max_size_mb' => 5,
                'allowed_extensions' => self::DEFAULT_ATTACHMENT_EXTENSIONS,
            ],
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(array $payload): array
    {
        $data = $this->sanitizePayload($payload);
        if ($data['is_default']) {
            $this->clearDefaultFlags();
            $data['is_active'] = true;
        }

        $created = $this->storage->create($data);
        $createdId = (string) ($created['id'] ?? '');

        if ($createdId !== '' && $this->getDefault() === null) {
            $this->setDefault($createdId);
            $defaulted = $this->find($createdId);
            if ($defaulted !== null) {
                return $defaulted;
            }
        }

        return $this->normalizeRecord($created);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(string $id, array $payload): ?array
    {
        $existing = $this->find($id);
        if ($existing === null) {
            return null;
        }

        $merged = array_merge($existing, $payload);
        $data = $this->sanitizePayload($merged);
        if ($data['is_default']) {
            $this->clearDefaultFlags($id);
            $data['is_active'] = true;
        }

        $updated = $this->storage->update($id, $data);
        if (!is_array($updated)) {
            return null;
        }

        $this->ensureDefaultIntegrity();
        return $this->normalizeRecord($updated);
    }

    public function delete(string $id): bool
    {
        $deleted = $this->storage->delete($id);
        if ($deleted) {
            $this->ensureDefaultIntegrity();
        }

        return $deleted;
    }

    public function toggleActive(string $id): ?array
    {
        $existing = $this->find($id);
        if ($existing === null) {
            return null;
        }

        $nextActive = !((bool) ($existing['is_active'] ?? false));
        $updates = ['is_active' => $nextActive];
        if (!$nextActive && !empty($existing['is_default'])) {
            $updates['is_default'] = false;
        }

        $updated = $this->storage->update($id, $updates);
        if (!is_array($updated)) {
            return null;
        }

        $this->ensureDefaultIntegrity();
        $resolved = $this->find($id);
        return $resolved ?? $this->normalizeRecord($updated);
    }

    public function setDefault(string $id): ?array
    {
        $existing = $this->find($id);
        if ($existing === null) {
            return null;
        }

        $this->clearDefaultFlags($id);

        $updated = $this->storage->update($id, [
            'is_default' => true,
            'is_active' => true,
        ]);

        if (!is_array($updated)) {
            return null;
        }

        return $this->normalizeRecord($updated);
    }

    public function sanitizeSlug(string $value, string $fallback = 'contact-form'): string
    {
        $slug = str_slug(trim($value));
        if ($slug !== '') {
            return $slug;
        }

        $fallback = str_slug(trim($fallback));
        return $fallback !== '' ? $fallback : 'contact-form';
    }

    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        $slug = $this->sanitizeSlug($slug);
        foreach ($this->fetchAllNormalized() as $form) {
            $id = (string) ($form['id'] ?? '');
            if ($excludeId !== null && $id === $excludeId) {
                continue;
            }

            if ((string) ($form['slug'] ?? '') === $slug) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllNormalized(): array
    {
        $items = array_values(array_filter(array_map(function (array $item): array {
            return $this->normalizeRecord($item);
        }, $this->storage->all()), static function (array $item): bool {
            return (string) ($item['id'] ?? '') !== '';
        }));

        usort($items, static function (array $a, array $b): int {
            $aDefault = !empty($a['is_default']);
            $bDefault = !empty($b['is_default']);
            if ($aDefault !== $bDefault) {
                return $aDefault ? -1 : 1;
            }

            $aActive = !empty($a['is_active']);
            $bActive = !empty($b['is_active']);
            if ($aActive !== $bActive) {
                return $aActive ? -1 : 1;
            }

            return (string) ($b['updated_at'] ?? '') <=> (string) ($a['updated_at'] ?? '');
        });

        return $items;
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function normalizeRecord(array $record): array
    {
        $name = trim((string) ($record['name'] ?? ''));
        $slugSource = trim((string) ($record['slug'] ?? ''));
        $slug = $this->sanitizeSlug($slugSource, $name !== '' ? $name : 'contact-form');
        $fields = $this->normalizeFields($record['fields'] ?? []);
        $customFields = $this->normalizeCustomFields($record['custom_fields'] ?? []);
        $attachments = $this->normalizeAttachments($record['attachments'] ?? []);

        return $this->translations->normalizeForm([
            'id' => (string) ($record['id'] ?? ''),
            'name' => $name !== '' ? $name : 'Contact form',
            'slug' => $slug,
            'form_type' => $this->sanitizeFormType((string) ($record['form_type'] ?? self::FORM_TYPE_CONTACT)),
            'description' => trim((string) ($record['description'] ?? '')),
            'recipient_email' => trim((string) ($record['recipient_email'] ?? '')),
            'submit_label' => trim((string) ($record['submit_label'] ?? '')),
            'success_message' => trim((string) ($record['success_message'] ?? '')),
            'newsletter_legal_url' => trim((string) ($record['newsletter_legal_url'] ?? '')),
            'newsletter_privacy_url' => trim((string) ($record['newsletter_privacy_url'] ?? '')),
            'is_active' => $this->toBool($record['is_active'] ?? true, true),
            'is_default' => $this->toBool($record['is_default'] ?? false, false),
            'fields' => $fields,
            'custom_fields' => $customFields,
            'source_locale' => trim((string) ($record['source_locale'] ?? '')),
            'translations' => is_array($record['translations'] ?? null) ? $record['translations'] : [],
            'attachments' => $attachments,
            'created_at' => (string) ($record['created_at'] ?? ''),
            'updated_at' => (string) ($record['updated_at'] ?? ''),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $slug = $this->sanitizeSlug((string) ($payload['slug'] ?? ''), $name !== '' ? $name : 'contact-form');
        $fields = $this->normalizeFields($payload['fields'] ?? []);
        $customFields = $this->normalizeCustomFields($payload['custom_fields'] ?? []);
        $attachments = $this->normalizeAttachments($payload['attachments'] ?? []);

        return $this->translations->normalizeForm([
            'name' => $name,
            'slug' => $slug,
            'form_type' => $this->sanitizeFormType((string) ($payload['form_type'] ?? self::FORM_TYPE_CONTACT)),
            'description' => trim((string) ($payload['description'] ?? '')),
            'recipient_email' => trim((string) ($payload['recipient_email'] ?? '')),
            'submit_label' => trim((string) ($payload['submit_label'] ?? '')),
            'success_message' => trim((string) ($payload['success_message'] ?? '')),
            'newsletter_legal_url' => trim((string) ($payload['newsletter_legal_url'] ?? '')),
            'newsletter_privacy_url' => trim((string) ($payload['newsletter_privacy_url'] ?? '')),
            'is_active' => $this->toBool($payload['is_active'] ?? true, true),
            'is_default' => $this->toBool($payload['is_default'] ?? false, false),
            'fields' => $fields,
            'custom_fields' => $customFields,
            'source_locale' => trim((string) ($payload['source_locale'] ?? '')),
            'translations' => is_array($payload['translations'] ?? null) ? $payload['translations'] : [],
            'attachments' => $attachments,
        ]);
    }

    /**
     * @param mixed $fields
     * @return array<string, bool>
     */
    private function normalizeFields(mixed $fields): array
    {
        $fields = is_array($fields) ? $fields : [];

        return [
            'name' => true,
            'email' => true,
            'subject' => $this->toBool($fields['subject'] ?? true, true),
            'phone' => $this->toBool($fields['phone'] ?? false, false),
            'message' => true,
        ];
    }

    /**
     * @param mixed $customFields
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCustomFields(mixed $customFields): array
    {
        if (!is_array($customFields)) {
            return [];
        }

        $normalized = [];
        $seenKeys = [];

        foreach ($customFields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $label = trim((string) ($field['label'] ?? ''));
            $keySource = trim((string) ($field['key'] ?? ($field['name'] ?? $label)));
            $type = strtolower(trim((string) ($field['type'] ?? 'text')));
            if (!in_array($type, self::CUSTOM_FIELD_TYPES, true)) {
                $type = 'text';
            }
            $positionAfter = strtolower(trim((string) ($field['position_after'] ?? 'message')));
            if (!in_array($positionAfter, self::CUSTOM_FIELD_POSITIONS, true)) {
                $positionAfter = 'message';
            }
            $width = strtolower(trim((string) ($field['width'] ?? 'full')));
            if (!in_array($width, self::CUSTOM_FIELD_WIDTHS, true)) {
                $width = 'full';
            }
            $optionsSeed = $this->normalizeFieldOptions($field['options'] ?? []);

            $hasContent = $label !== ''
                || $keySource !== ''
                || trim((string) ($field['placeholder'] ?? '')) !== ''
                || trim((string) ($field['help'] ?? '')) !== ''
                || $optionsSeed !== []
                || (!empty($field['required']) && $this->toBool($field['required'], false));

            if (!$hasContent) {
                continue;
            }

            if ($label === '') {
                $label = ucfirst(str_replace('_', ' ', $keySource !== '' ? $keySource : 'champ'));
            }

            $key = $this->sanitizeFieldKey($keySource !== '' ? $keySource : $label, 'field');
            if ($key === '') {
                continue;
            }

            if (isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;

            $options = $optionsSeed;
            if (!in_array($type, ['select', 'radio', 'checkbox'], true)) {
                $options = [];
            }

            $normalized[] = [
                'key' => $key,
                'label' => mb_substr($label, 0, 120),
                'type' => $type,
                'required' => $this->toBool($field['required'] ?? false, false),
                'placeholder' => trim((string) ($field['placeholder'] ?? '')),
                'help' => trim((string) ($field['help'] ?? '')),
                'position_after' => $positionAfter,
                'width' => $width,
                'options' => $options,
            ];
        }

        return array_slice($normalized, 0, 24);
    }

    /**
     * @param mixed $attachments
     * @return array<string, mixed>
     */
    private function normalizeAttachments(mixed $attachments): array
    {
        $attachments = is_array($attachments) ? $attachments : [];

        $enabled = $this->toBool($attachments['enabled'] ?? false, false);
        $required = $enabled ? $this->toBool($attachments['required'] ?? false, false) : false;
        $maxFiles = (int) ($attachments['max_files'] ?? 1);
        $maxSizeMb = (int) ($attachments['max_size_mb'] ?? 5);

        $maxFiles = max(1, min(5, $maxFiles));
        $maxSizeMb = max(1, min(25, $maxSizeMb));

        $allowedExtensions = $this->normalizeExtensionList($attachments['allowed_extensions'] ?? []);
        if ($allowedExtensions === []) {
            $allowedExtensions = self::DEFAULT_ATTACHMENT_EXTENSIONS;
        }

        return [
            'enabled' => $enabled,
            'required' => $required,
            'max_files' => $maxFiles,
            'max_size_mb' => $maxSizeMb,
            'allowed_extensions' => $allowedExtensions,
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
            if ($value === '') {
                continue;
            }
            $normalized[] = mb_substr($value, 0, 120);
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param mixed $extensions
     * @return array<int, string>
     */
    private function normalizeExtensionList(mixed $extensions): array
    {
        if (is_string($extensions)) {
            $extensions = preg_split('/[\r\n,\s;]+/', $extensions) ?: [];
        }

        if (!is_array($extensions)) {
            return [];
        }

        $normalized = [];
        foreach ($extensions as $extension) {
            $value = strtolower(trim((string) $extension));
            $value = ltrim($value, '.');
            if ($value === '' || !preg_match('/^[a-z0-9]{1,10}$/', $value)) {
                continue;
            }
            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }

    private function sanitizeFieldKey(string $value, string $fallback = 'field'): string
    {
        $value = str_replace('-', '_', str_slug(trim($value)));
        $value = preg_replace('/[^a-z0-9_]/', '', $value) ?? '';
        $value = trim((string) $value, '_');
        if ($value !== '') {
            return mb_substr($value, 0, 48);
        }

        $fallback = str_replace('-', '_', str_slug(trim($fallback)));
        $fallback = preg_replace('/[^a-z0-9_]/', '', $fallback) ?? '';
        $fallback = trim((string) $fallback, '_');

        return $fallback !== '' ? mb_substr($fallback, 0, 48) : 'field';
    }

    private function clearDefaultFlags(?string $exceptId = null): void
    {
        foreach ($this->fetchAllNormalized() as $form) {
            $id = (string) ($form['id'] ?? '');
            if ($id === '' || ($exceptId !== null && $id === $exceptId)) {
                continue;
            }

            if (!empty($form['is_default'])) {
                $this->storage->update($id, ['is_default' => false]);
            }
        }
    }

    private function ensureDefaultIntegrity(): void
    {
        $forms = $this->fetchAllNormalized();
        if ($forms === []) {
            return;
        }

        $defaultId = '';
        foreach ($forms as $form) {
            if (!empty($form['is_default'])) {
                $defaultId = (string) ($form['id'] ?? '');
                break;
            }
        }

        if ($defaultId === '') {
            foreach ($forms as $form) {
                if (!empty($form['is_active'])) {
                    $defaultId = (string) ($form['id'] ?? '');
                    break;
                }
            }
        }

        if ($defaultId === '') {
            $defaultId = (string) ($forms[0]['id'] ?? '');
        }

        if ($defaultId === '') {
            return;
        }

        foreach ($forms as $form) {
            $id = (string) ($form['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $isDefault = !empty($form['is_default']);
            $shouldDefault = $id === $defaultId;

            $updates = [];
            if ($isDefault !== $shouldDefault) {
                $updates['is_default'] = $shouldDefault;
            }

            if ($updates !== []) {
                $this->storage->update($id, $updates);
            }
        }
    }

    private function toBool(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if ($normalized === '') {
                return $default;
            }

            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return $default;
    }
}
