<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<link rel="stylesheet" href="<?= module_asset('Contact', 'css/contact-admin.css') ?>">

<?php
$form = is_array($form ?? null) ? $form : null;
$isEditMode = (bool) ($isEditMode ?? false);
$formId = (string) ($form['id'] ?? '');
$customFieldTypes = is_array($customFieldTypes ?? null) ? $customFieldTypes : ['text', 'email', 'tel', 'url', 'number', 'textarea', 'select', 'radio', 'checkbox', 'date'];
$customFieldWidths = is_array($customFieldWidths ?? null) ? $customFieldWidths : ['full', 'half'];
$formTypes = is_array($formTypes ?? null) ? $formTypes : ['contact'];
$formTypeLabels = is_array($formTypeLabels ?? null) ? $formTypeLabels : [];
$formTypePresets = is_array($formTypePresets ?? null) ? $formTypePresets : [];
$requiredLegalPages = is_array($requiredLegalPages ?? null) ? $requiredLegalPages : [];

$widthLabelMap = [
    'full' => __('contact_form_custom_width_full', 'Contact'),
    'half' => __('contact_form_custom_width_half', 'Contact'),
];

$toBool = static function (mixed $value, bool $default = false): bool {
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (int) $value === 1;
    }

    if (is_string($value)) {
        $value = strtolower(trim($value));
        if ($value === '') {
            return $default;
        }
        if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
    }

    return $default;
};

$normalizeOptionsList = static function (mixed $options): array {
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
};

$formState = [
    'name' => '',
    'slug' => '',
    'description' => '',
    'form_type' => 'contact',
    'recipient_email' => '',
    'submit_label' => '',
    'success_message' => '',
    'newsletter_legal_url' => '',
    'newsletter_privacy_url' => '',
    'is_active' => true,
    'is_default' => false,
    'fields' => [
        'name' => true,
        'email' => true,
        'subject' => true,
        'phone' => false,
        'message' => true,
    ],
    'custom_fields' => [],
    'attachments' => [
        'enabled' => false,
        'required' => false,
        'max_files' => 1,
        'max_size_mb' => 5,
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'zip'],
    ],
];

if ($form !== null) {
    $formState = array_merge($formState, $form);
}

$oldFormState = old('contact_form', []);
if (is_array($oldFormState) && $oldFormState !== []) {
    $formState = array_merge($formState, $oldFormState);
    $formState['attachments'] = array_merge($formState['attachments'], is_array($oldFormState['attachments'] ?? null) ? $oldFormState['attachments'] : []);
}

$customFieldsState = is_array($formState['custom_fields'] ?? null) ? $formState['custom_fields'] : [];
$baseFieldsState = is_array($formState['fields'] ?? null) ? $formState['fields'] : [];
$hasOldFormState = is_array($oldFormState) && $oldFormState !== [];
$hasCustomFieldDefinition = false;
foreach ($customFieldsState as $customFieldState) {
    if (!is_array($customFieldState)) {
        continue;
    }

    $candidateKey = trim((string) ($customFieldState['key'] ?? ''));
    $candidateLabel = trim((string) ($customFieldState['label'] ?? ''));
    if ($candidateKey !== '' && $candidateLabel !== '') {
        $hasCustomFieldDefinition = true;
        break;
    }
}

$builderDefaultFields = [];
if ($toBool($baseFieldsState['name'] ?? true, true)) {
    $builderDefaultFields[] = [
        'key' => 'name',
        'label' => __('contact_field_name', 'Contact'),
        'type' => 'text',
        'required' => true,
        'width' => 'half',
        'placeholder' => __('contact_form_name_placeholder', 'Contact'),
    ];
    $builderDefaultFields[] = [
        'key' => 'first_name',
        'label' => __('contact_field_first_name', 'Contact'),
        'type' => 'text',
        'required' => false,
        'width' => 'half',
        'placeholder' => __('contact_field_first_name', 'Contact'),
    ];
}
if ($toBool($baseFieldsState['email'] ?? true, true)) {
    $builderDefaultFields[] = [
        'key' => 'email',
        'label' => __('contact_field_email', 'Contact'),
        'type' => 'email',
        'required' => true,
        'width' => 'full',
        'placeholder' => __('contact_form_email_placeholder', 'Contact'),
    ];
}
if ($toBool($baseFieldsState['subject'] ?? true, true)) {
    $builderDefaultFields[] = [
        'key' => 'subject',
        'label' => __('contact_subject', 'Contact'),
        'type' => 'text',
        'required' => true,
        'width' => 'full',
        'placeholder' => __('contact_form_subject_placeholder', 'Contact'),
    ];
}
if ($toBool($baseFieldsState['phone'] ?? false, false)) {
    $builderDefaultFields[] = [
        'key' => 'phone',
        'label' => __('contact_field_phone', 'Contact'),
        'type' => 'tel',
        'required' => false,
        'width' => 'half',
        'placeholder' => __('contact_form_phone_placeholder', 'Contact'),
    ];
}
if ($toBool($baseFieldsState['message'] ?? true, true)) {
    $builderDefaultFields[] = [
        'key' => 'message',
        'label' => __('contact_field_message', 'Contact'),
        'type' => 'textarea',
        'required' => true,
        'width' => 'full',
        'placeholder' => __('contact_form_message_placeholder', 'Contact'),
    ];
}

if (!$hasOldFormState && !$hasCustomFieldDefinition && $customFieldsState === []) {
    $customFieldsState = $builderDefaultFields;
}

$formState['custom_fields'] = $customFieldsState;

$contactTranslationService = new \App\Modules\Contact\Services\ContactFormTranslationService();
$contactUiLocale = \App\Core\I18n::getLocale();
$contactTranslationActiveLocale = (string) old('contact_translation_active_locale', $contactUiLocale);
$contactTranslationUi = $contactTranslationService->buildEditorState($formState, $contactTranslationActiveLocale, $contactUiLocale);
$contactTranslationSourceLocale = (string) ($contactTranslationUi['source_locale'] ?? $contactTranslationService->defaultLocale());
$contactTranslationTabs = is_array($contactTranslationUi['tabs'] ?? null) ? $contactTranslationUi['tabs'] : [];
$contactTranslationPanels = is_array($contactTranslationUi['panels'] ?? null) ? $contactTranslationUi['panels'] : [];
$contactLocaleFlag = static function (string $locale): string {
    $value = trim($locale);
    if ($value === '') {
        return '🏳️';
    }

    $parts = preg_split('/[-_]/', $value) ?: [];
    $country = strtoupper((string) end($parts));
    if (!preg_match('/^[A-Z]{2}$/', $country)) {
        $country = strtoupper(substr($value, 0, 2));
    }

    if (!preg_match('/^[A-Z]{2}$/', $country)) {
        return '🏳️';
    }

    $first = 127397 + ord($country[0]);
    $second = 127397 + ord($country[1]);

    return html_entity_decode('&#' . $first . ';&#' . $second . ';', ENT_QUOTES | ENT_HTML5, 'UTF-8');
};
$contactPanelLabel = static function (array $labels, string $key, string $fallback): string {
    $value = $labels[$key] ?? null;
    return is_string($value) && trim($value) !== '' ? $value : $fallback;
};
$contactSourcePanel = is_array($contactTranslationPanels[$contactTranslationSourceLocale] ?? null)
    ? $contactTranslationPanels[$contactTranslationSourceLocale]
    : [];
$contactSourcePanelLabels = is_array($contactSourcePanel['form_labels'] ?? null) ? $contactSourcePanel['form_labels'] : [];
$contactTranslationModalTabs = $contactTranslationTabs;
$contactTranslationInitialModalLocale = $contactTranslationActiveLocale;
if ($contactTranslationInitialModalLocale === '') {
    $contactTranslationInitialModalLocale = (string) ($contactTranslationModalTabs[0]['code'] ?? '');
}

$attachmentsState = is_array($formState['attachments'] ?? null) ? $formState['attachments'] : [];
$attachmentsState = array_merge([
    'enabled' => false,
    'required' => false,
    'max_files' => 1,
    'max_size_mb' => 5,
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'zip'],
], $attachmentsState);
$attachmentsExtensions = implode(', ', $normalizeOptionsList($attachmentsState['allowed_extensions'] ?? []));
$attachmentsEnabled = $toBool($attachmentsState['enabled'] ?? false, false);

$actionUrl = $isEditMode
    ? url('/admin/contact/forms/' . $formId)
    : url('/admin/contact/forms');

$formTypeState = trim((string) ($formState['form_type'] ?? 'contact'));
if (!in_array($formTypeState, $formTypes, true)) {
    $formTypeState = 'contact';
}

$legalNoticePage = is_array($requiredLegalPages['legal_notice'] ?? null) ? $requiredLegalPages['legal_notice'] : [];
$privacyPolicyPage = is_array($requiredLegalPages['privacy_policy'] ?? null) ? $requiredLegalPages['privacy_policy'] : [];
$legalNoticeEditUrl = trim((string) ($legalNoticePage['edit_url'] ?? ''));
$legalNoticePublicUrl = trim((string) ($legalNoticePage['public_url'] ?? ''));
$privacyPolicyEditUrl = trim((string) ($privacyPolicyPage['edit_url'] ?? ''));
$privacyPolicyPublicUrl = trim((string) ($privacyPolicyPage['public_url'] ?? ''));

$formTypePresetsJson = json_encode(
    $formTypePresets,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
if (!is_string($formTypePresetsJson)) {
    $formTypePresetsJson = '{}';
}
?>

<div class="page-header" data-tour-target="contact-form-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle ?? ($isEditMode ? __('contact_form_edit_page_title', 'Contact') : __('contact_form_create_page_title', 'Contact'))) ?></h1>
    </div>
    <div class="page-header-actions">
        <a href="<?= url('/admin/contact/forms') ?>" class="btn btn-sm btn-outline">
            <?= __('back', 'Core') ?>
        </a>
        <a href="<?= url('/admin/contact') ?>" class="btn btn-sm btn-secondary">
            <i class="fas fa-inbox"></i>
            <?= __('contact_back_to_messages', 'Contact') ?>
        </a>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-envelope-open-text"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('contact_help_badge', 'Contact') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('contact_tour_form_main_title', 'Contact') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('contact_tour_form_main_content', 'Contact') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('contact_tour_form_identity_content', 'Contact') ?></li>
            <?php if ($formTypeState === 'newsletter_rgpd'): ?>
                <li><?= __('contact_tour_form_legal_content', 'Contact') ?></li>
            <?php endif; ?>
            <li><?= __('contact_tour_form_builder_content', 'Contact') ?></li>
            <li><?= __('contact_tour_form_translations_content', 'Contact') ?></li>
            <li><?= __('contact_tour_form_sidebar_content', 'Contact') ?></li>
            <li><?= __($isEditMode ? 'contact_tour_form_edit_next_content' : 'contact_tour_form_create_next_content', 'Contact') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/contact/forms') ?>" class="btn btn-primary"><?= __('contact_forms_list_title', 'Contact') ?></a>
            <a href="<?= url('/admin/contact') ?>" class="btn btn-secondary"><?= __('contact_back_to_messages', 'Contact') ?></a>
        </div>
    </div>
</div>

<form
    method="POST"
    action="<?= e($actionUrl) ?>"
    data-tour-state="<?= $isEditMode ? 'edit' : 'create' ?>"
    data-contact-field-builder
    data-label-options="<?= e(__('contact_form_custom_options_label', 'Contact')) ?>"
    data-remove-label="<?= e(__('remove', 'Core')) ?>"
    data-builder-preview-title="<?= e(__('contact_form_builder_preview_title', 'Contact')) ?>"
    data-builder-preview-help="<?= e(__('contact_form_builder_preview_help', 'Contact')) ?>"
    data-builder-empty-title="<?= e(__('contact_form_builder_empty_title', 'Contact')) ?>"
    data-builder-empty-help="<?= e(__('contact_form_builder_empty_help', 'Contact')) ?>"
    data-builder-unnamed-field="<?= e(__('contact_form_builder_unnamed_field', 'Contact')) ?>"
    data-builder-field-prefix="<?= e(__('contact_form_builder_field_prefix', 'Contact')) ?>"
    data-builder-inspector-title="<?= e(__('contact_form_builder_inspector_title', 'Contact')) ?>"
    data-builder-inspector-none="<?= e(__('contact_form_builder_inspector_none', 'Contact')) ?>"
    data-builder-action-edit="<?= e(__('edit', 'Core')) ?>"
    data-builder-action-duplicate="<?= e(__('contact_form_builder_duplicate_field', 'Contact')) ?>"
    data-builder-action-delete="<?= e(__('delete', 'Core')) ?>"
    data-builder-action-move-up="<?= e(__('contact_form_custom_move_up', 'Contact')) ?>"
    data-builder-action-move-down="<?= e(__('contact_form_custom_move_down', 'Contact')) ?>"
    data-builder-inspector-close="<?= e(__('close', 'Core')) ?>"
    data-builder-inspector-duplicate="<?= e(__('contact_form_builder_duplicate_field', 'Contact')) ?>"
    data-builder-inspector-delete="<?= e(__('contact_form_builder_delete_field', 'Contact')) ?>"
    data-builder-delete-confirm="<?= e(__('contact_form_builder_delete_confirm', 'Contact')) ?>"
    data-builder-options-manage="<?= e(__('contact_form_options_modal_open', 'Contact')) ?>"
    data-builder-select-placeholder="<?= e(__('contact_form_select_placeholder', 'Contact')) ?>"
    data-builder-option-sample-one="<?= e(__('contact_form_builder_option_sample_one', 'Contact')) ?>"
    data-builder-option-sample-two="<?= e(__('contact_form_builder_option_sample_two', 'Contact')) ?>"
    data-form-type-presets="<?= e($formTypePresetsJson) ?>"
    data-form-preset-confirm="<?= e(__('contact_form_preset_apply_confirm', 'Contact')) ?>"
    data-form-preset-confirm-text="<?= e(__('contact_form_preset_apply_confirm_button', 'Contact')) ?>"
>
    <?= csrf_field() ?>
    <input type="hidden" name="source_locale" value="<?= e($contactTranslationSourceLocale) ?>">
    <input type="hidden" name="contact_translation_active_locale" value="<?= e((string) ($contactTranslationUi['active_locale'] ?? $contactTranslationSourceLocale)) ?>" data-contact-translation-active-locale>

    <div class="contact-form-layout">
        <div class="contact-form-main-column">
            <div class="card" data-tour-target="contact-form-main" data-tour-section="contact-form-identity">
                <div class="contact-form-fields-row">
                    <div class="form-group">
                        <label class="form-label" for="contactFormName"><?= __('contact_form_name_label', 'Contact') ?></label>
                        <input
                            id="contactFormName"
                            name="name"
                            type="text"
                            class="form-input"
                            value="<?= e((string) ($formState['name'] ?? '')) ?>"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label contact-form-label-with-help" for="contactFormType">
                            <span><?= __('contact_form_type_label', 'Contact') ?></span>
                            <span
                                class="contact-form-hover-help"
                                data-tooltip="<?= e(__('contact_form_type_help', 'Contact')) ?>"
                                aria-label="<?= e(__('contact_form_type_help', 'Contact')) ?>"
                                tabindex="0"
                            >
                                <i class="fas fa-circle-info" aria-hidden="true"></i>
                            </span>
                        </label>
                        <select id="contactFormType" name="form_type" class="form-select" data-contact-form-type>
                            <?php foreach ($formTypes as $formType): ?>
                                <?php $typeLabel = (string) ($formTypeLabels[$formType] ?? $formType); ?>
                                <option value="<?= e((string) $formType) ?>" <?= $formTypeState === (string) $formType ? 'selected' : '' ?>>
                                    <?= e($typeLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="contact-form-fields-row">
                    <div class="form-group">
                        <label class="form-label" for="contactFormSlug"><?= __('contact_form_slug_label', 'Contact') ?></label>
                        <input
                            id="contactFormSlug"
                            name="slug"
                            type="text"
                            class="form-input"
                            value="<?= e((string) ($formState['slug'] ?? '')) ?>"
                            placeholder="<?= e(__('contact_form_slug_placeholder', 'Contact')) ?>"
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contactFormRecipient"><?= __('contact_form_recipient_email_label', 'Contact') ?></label>
                        <input
                            id="contactFormRecipient"
                            name="recipient_email"
                            type="email"
                            class="form-input"
                            value="<?= e((string) ($formState['recipient_email'] ?? '')) ?>"
                            placeholder="<?= e(__('contact_form_recipient_email_placeholder', 'Contact')) ?>"
                        >
                    </div>
                </div>

                <div class="contact-form-fields-row">
                    <div class="form-group <?= $formTypeState === 'newsletter_rgpd' ? '' : 'is-hidden' ?>" data-contact-newsletter-legal-group data-tour-target="contact-form-legal">
                        <label class="form-label"><?= __('contact_form_newsletter_legal_url_label', 'Contact') ?></label>
                        <div class="contact-form-system-page-actions">
                            <?php if ($legalNoticeEditUrl !== ''): ?>
                                <a href="<?= e($legalNoticeEditUrl) ?>" class="btn btn-sm btn-secondary">
                                    <?= __('contact_form_newsletter_manage_page_action', 'Contact') ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($legalNoticePublicUrl !== ''): ?>
                                <a href="<?= e($legalNoticePublicUrl) ?>" class="btn btn-sm btn-ghost" target="_blank" rel="noopener noreferrer">
                                    <?= __('contact_form_newsletter_open_page_action', 'Contact') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted"><?= __('contact_form_newsletter_system_page_hint', 'Contact') ?></small>
                        <input type="hidden" name="newsletter_legal_url" value="<?= e($legalNoticePublicUrl) ?>">
                    </div>
                    <div class="form-group <?= $formTypeState === 'newsletter_rgpd' ? '' : 'is-hidden' ?>" data-contact-newsletter-privacy-group>
                        <label class="form-label"><?= __('contact_form_newsletter_privacy_url_label', 'Contact') ?></label>
                        <div class="contact-form-system-page-actions">
                            <?php if ($privacyPolicyEditUrl !== ''): ?>
                                <a href="<?= e($privacyPolicyEditUrl) ?>" class="btn btn-sm btn-secondary">
                                    <?= __('contact_form_newsletter_manage_page_action', 'Contact') ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($privacyPolicyPublicUrl !== ''): ?>
                                <a href="<?= e($privacyPolicyPublicUrl) ?>" class="btn btn-sm btn-ghost" target="_blank" rel="noopener noreferrer">
                                    <?= __('contact_form_newsletter_open_page_action', 'Contact') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted"><?= __('contact_form_newsletter_system_page_hint', 'Contact') ?></small>
                        <input type="hidden" name="newsletter_privacy_url" value="<?= e($privacyPolicyPublicUrl) ?>">
                    </div>
                </div>

            </div>

            <div class="card contact-form-builder-card" data-tour-target="contact-form-builder">
                <div class="card-header contact-builder-header">
                    <h3 class="card-title"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_fields_title', __('contact_form_custom_fields_title', 'Contact'))) ?></h3>
                </div>
                <p class="text-muted mb-12"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_fields_help', __('contact_form_custom_fields_help', 'Contact'))) ?></p>

                <div class="contact-form-fields-row">
                    <div class="form-group">
                        <label class="form-label" for="contactFormSubmitLabel"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_submit_label_admin', __('contact_form_submit_label_admin', 'Contact'))) ?></label>
                        <input
                            id="contactFormSubmitLabel"
                            name="submit_label"
                            type="text"
                            class="form-input"
                            value="<?= e((string) ($contactSourcePanel['submit_label'] ?? '')) ?>"
                            placeholder="<?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_submit_placeholder', __('contact_form_submit_placeholder', 'Contact'))) ?>"
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contactFormSuccessMessage"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_success_message_label', __('contact_form_success_message_label', 'Contact'))) ?></label>
                        <input
                            id="contactFormSuccessMessage"
                            name="success_message"
                            type="text"
                            class="form-input"
                            value="<?= e((string) ($contactSourcePanel['success_message'] ?? '')) ?>"
                            placeholder="<?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_success_placeholder', __('contact_form_success_placeholder', 'Contact'))) ?>"
                        >
                    </div>
                </div>

                <div class="contact-builder-workspace" data-contact-builder-workspace data-tour-target="contact-form-builder-canvas">
                    <div class="contact-builder-canvas-card">
                        <div class="contact-builder-canvas-head">
                            <h4><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_builder_preview_title', __('contact_form_builder_preview_title', 'Contact'))) ?></h4>
                            <p class="text-muted"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_builder_preview_help', __('contact_form_builder_preview_help', 'Contact'))) ?></p>
                        </div>
                        <div class="contact-builder-canvas" data-contact-builder-canvas></div>
                    </div>
                </div>

                <div class="contact-custom-fields contact-custom-fields-source" data-contact-custom-fields hidden>
                    <?php foreach ($customFieldsState as $index => $customField): ?>
                        <?php
                        $customField = is_array($customField) ? $customField : [];
                        $customOptions = $normalizeOptionsList($customField['options'] ?? []);
                        $customType = strtolower(trim((string) ($customField['type'] ?? 'text')));
                        if (!in_array($customType, $customFieldTypes, true)) {
                            $customType = 'text';
                        }
                        $customWidth = strtolower(trim((string) ($customField['width'] ?? 'full')));
                        if (!in_array($customWidth, $customFieldWidths, true)) {
                            $customWidth = 'full';
                        }
                        ?>
                        <article class="contact-custom-field-row" data-contact-custom-field-row>
                            <div class="contact-custom-field-row__head">
                                <strong><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_field_label', __('contact_form_custom_field_label', 'Contact'))) ?></strong>
                                <div class="contact-custom-field-row__actions">
                                    <button type="button" class="btn btn-sm btn-ghost" data-contact-move-up title="<?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_move_up', __('contact_form_custom_move_up', 'Contact'))) ?>">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-ghost" data-contact-move-down title="<?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_move_down', __('contact_form_custom_move_down', 'Contact'))) ?>">
                                        <i class="fas fa-arrow-down"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-ghost contact-custom-remove" data-contact-remove-field>
                                        <i class="fas fa-trash-alt"></i>
                                        <span><?= __('remove', 'Core') ?></span>
                                    </button>
                                </div>
                            </div>

                            <div class="contact-custom-field-grid">
                                <div class="form-group">
                                    <label class="form-label"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_label', __('contact_form_custom_label', 'Contact'))) ?></label>
                                    <input
                                        type="text"
                                        class="form-input"
                                        name="custom_fields[<?= (int) $index ?>][label]"
                                        value="<?= e((string) ($customField['label'] ?? '')) ?>"
                                        maxlength="120"
                                        data-contact-field-label
                                    >
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_key', __('contact_form_custom_key', 'Contact'))) ?></label>
                                    <input
                                        type="text"
                                        class="form-input"
                                        name="custom_fields[<?= (int) $index ?>][key]"
                                        value="<?= e((string) ($customField['key'] ?? '')) ?>"
                                        maxlength="48"
                                        placeholder="delivery_company"
                                        data-contact-field-key
                                    >
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_type', __('contact_form_custom_type', 'Contact'))) ?></label>
                                    <select class="form-select" name="custom_fields[<?= (int) $index ?>][type]" data-contact-field-type>
                                        <?php foreach ($customFieldTypes as $fieldType): ?>
                                            <?php
                                            $labelKey = 'contact_form_custom_type_' . $fieldType;
                                            $typeLabel = $contactPanelLabel($contactSourcePanelLabels, $labelKey, __($labelKey, 'Contact'));
                                            if ($typeLabel === $labelKey) {
                                                $typeLabel = ucfirst($fieldType);
                                            }
                                            ?>
                                            <option value="<?= e($fieldType) ?>" <?= $customType === $fieldType ? 'selected' : '' ?>>
                                                <?= e($typeLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group contact-custom-required">
                                    <label class="contact-form-option">
                                        <input
                                            type="checkbox"
                                            name="custom_fields[<?= (int) $index ?>][required]"
                                            value="1"
                                            <?= $toBool($customField['required'] ?? false, false) ? 'checked' : '' ?>
                                        >
                                        <span><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_required', __('contact_form_custom_required', 'Contact'))) ?></span>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_width_label', __('contact_form_custom_width_label', 'Contact'))) ?></label>
                                    <select class="form-select" name="custom_fields[<?= (int) $index ?>][width]">
                                        <?php foreach ($customFieldWidths as $fieldWidth): ?>
                                            <?php $widthLabel = $widthLabelMap[$fieldWidth] ?? strtoupper((string) $fieldWidth); ?>
                                            <option value="<?= e((string) $fieldWidth) ?>" <?= $customWidth === $fieldWidth ? 'selected' : '' ?> <?= $fieldWidth === 'full' ? 'data-contact-field-default' : '' ?>>
                                                <?= e($widthLabel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_placeholder', __('contact_form_custom_placeholder', 'Contact'))) ?></label>
                                    <input
                                        type="text"
                                        class="form-input"
                                        name="custom_fields[<?= (int) $index ?>][placeholder]"
                                        value="<?= e((string) ($customField['placeholder'] ?? '')) ?>"
                                        maxlength="190"
                                    >
                                </div>
                                <div class="form-group">
                                    <label class="form-label"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_help', __('contact_form_custom_help', 'Contact'))) ?></label>
                                    <input
                                        type="text"
                                        class="form-input"
                                        name="custom_fields[<?= (int) $index ?>][help]"
                                        value="<?= e((string) ($customField['help'] ?? '')) ?>"
                                        maxlength="190"
                                    >
                                </div>
                            </div>

                            <div class="form-group contact-custom-field-options <?= in_array($customType, ['select', 'radio', 'checkbox'], true) ? '' : 'is-hidden' ?>" data-contact-field-options-group>
                                <label class="form-label"><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_options_label', __('contact_form_custom_options_label', 'Contact'))) ?></label>
                                <textarea
                                    class="form-input"
                                    name="custom_fields[<?= (int) $index ?>][options]"
                                    rows="3"
                                    placeholder="<?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_custom_options_placeholder', __('contact_form_custom_options_placeholder', 'Contact'))) ?>"
                                ><?= e(implode("\n", $customOptions)) ?></textarea>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card contact-form-translations-card">
                <div class="card-header contact-builder-header contact-form-translations-card__header">
                    <h3 class="card-title"><?= __('translations', 'Contact') ?></h3>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline"
                        data-contact-translation-open
                        data-tour-target="contact-form-translations-trigger"
                        aria-haspopup="dialog"
                        aria-controls="contactTranslationsModal"
                    >
                        <i class="fas fa-language"></i>
                        <?= __('translations', 'Contact') ?>
                    </button>
                </div>
                <p class="text-muted mb-0"><?= __('contact_form_translations_help', 'Contact') ?></p>
            </div>
        </div>

        <div class="contact-form-sidebar contact-form-sidebar--sticky" data-tour-target="contact-form-sidebar">
            <div class="card contact-form-sidebar-card contact-form-sidebar-card--sticky" data-tour-target="contact-form-delivery">
                <h3 class="card-title card-title-spaced"><?= __('contact_form_options_legend', 'Contact') ?></h3>

                <fieldset class="contact-form-options">
                    <label class="contact-form-option">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            <?= $toBool(($formState['is_active'] ?? true), true) ? 'checked' : '' ?>
                        >
                        <span><?= __('contact_form_option_active', 'Contact') ?></span>
                    </label>
                    <label class="contact-form-option">
                        <input
                            type="checkbox"
                            name="is_default"
                            value="1"
                            <?= $toBool(($formState['is_default'] ?? false), false) ? 'checked' : '' ?>
                        >
                        <span><?= __('contact_form_option_default', 'Contact') ?></span>
                    </label>
                </fieldset>

                <fieldset class="contact-form-options contact-form-attachments" data-contact-attachments-settings>
                    <legend><?= __('contact_form_attachments_title', 'Contact') ?></legend>
                    <label class="contact-form-option">
                        <input
                            type="checkbox"
                            name="attachments_enabled"
                            value="1"
                            <?= $attachmentsEnabled ? 'checked' : '' ?>
                            data-contact-attachments-enabled
                        >
                        <span><?= __('contact_form_attachments_enable', 'Contact') ?></span>
                    </label>

                    <div class="contact-attachments-config <?= $attachmentsEnabled ? '' : 'is-hidden' ?>" data-contact-attachments-config>
                        <div class="contact-form-fields-row">
                            <div class="form-group">
                                <label class="form-label" for="contactAttachmentsMaxFiles"><?= __('contact_form_attachments_max_files', 'Contact') ?></label>
                                <input
                                    id="contactAttachmentsMaxFiles"
                                    type="number"
                                    class="form-input"
                                    name="attachments_max_files"
                                    min="1"
                                    max="5"
                                    value="<?= (int) ($attachmentsState['max_files'] ?? 1) ?>"
                                >
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="contactAttachmentsMaxSize"><?= __('contact_form_attachments_max_size', 'Contact') ?></label>
                                <input
                                    id="contactAttachmentsMaxSize"
                                    type="number"
                                    class="form-input"
                                    name="attachments_max_size_mb"
                                    min="1"
                                    max="25"
                                    value="<?= (int) ($attachmentsState['max_size_mb'] ?? 5) ?>"
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="contactAttachmentsExtensions"><?= __('contact_form_attachments_extensions', 'Contact') ?></label>
                            <input
                                id="contactAttachmentsExtensions"
                                type="text"
                                class="form-input"
                                name="attachments_allowed_extensions"
                                value="<?= e($attachmentsExtensions) ?>"
                                placeholder="pdf, docx, png"
                            >
                            <small class="text-muted"><?= __('contact_form_attachments_extensions_help', 'Contact') ?></small>
                        </div>

                        <label class="contact-form-option">
                            <input
                                type="checkbox"
                                name="attachments_required"
                                value="1"
                                <?= $toBool($attachmentsState['required'] ?? false, false) ? 'checked' : '' ?>
                            >
                            <span><?= __('contact_form_attachments_required', 'Contact') ?></span>
                        </label>
                    </div>
                </fieldset>

                <div class="contact-form-submit-actions">
                    <button type="button" class="btn btn-outline btn-sm" data-contact-add-field>
                        <i class="fas fa-plus"></i>
                        <?= __('contact_form_custom_add', 'Contact') ?>
                    </button>
                    <button type="submit" class="btn btn-primary btn-block" data-tour-target="contact-form-save">
                        <?= $isEditMode ? __('contact_form_update', 'Contact') : __('contact_form_create', 'Contact') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($contactTranslationModalTabs !== []): ?>
        <div
            class="modal-overlay is-initially-hidden"
            id="contactTranslationsModal"
            aria-hidden="true"
            data-contact-translations-modal
        >
            <div class="modal-container contact-translations-modal">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-language modal-icon-info"></i>
                        <span data-contact-translation-modal-title><?= e($contactPanelLabel($contactSourcePanelLabels, 'translations', __('translations', 'Contact'))) ?></span>
                    </h3>
                    <button
                        type="button"
                        class="modal-close"
                        data-modal-close="contactTranslationsModal"
                        data-contact-translation-close-icon
                        aria-label="<?= e($contactPanelLabel($contactSourcePanelLabels, 'close', __('close', 'Core'))) ?>"
                    >&times;</button>
                </div>
                <div class="modal-body">
                    <div class="contact-form-translation-bar">
                        <div class="contact-form-translation-tabs" role="tablist" data-contact-translation-tablist aria-label="<?= e($contactPanelLabel($contactSourcePanelLabels, 'translations', __('translations', 'Contact'))) ?>">
                            <?php foreach ($contactTranslationModalTabs as $tab): ?>
                                <?php
                                $localeCode = (string) ($tab['code'] ?? '');
                                $status = (string) ($tab['status'] ?? 'missing');
                                $panelLabels = is_array($tab['form_labels'] ?? null) ? $tab['form_labels'] : [];
                                $tabClasses = ['contact-form-translation-tab'];
                                if ($localeCode === $contactTranslationInitialModalLocale) {
                                    $tabClasses[] = 'is-active';
                                }
                                if ($status === 'missing') {
                                    $tabClasses[] = 'is-missing';
                                }
                                $badge = $status === 'translated'
                                    ? $contactPanelLabel($panelLabels, 'translation_ready', __('translation_ready', 'Contact'))
                                    : $contactPanelLabel($panelLabels, 'translation_missing', __('translation_missing', 'Contact'));
                                ?>
                                <button
                                    type="button"
                                    class="<?= e(implode(' ', $tabClasses)) ?>"
                                    role="tab"
                                    aria-selected="<?= $localeCode === $contactTranslationInitialModalLocale ? 'true' : 'false' ?>"
                                    data-contact-translation-tab="<?= e($localeCode) ?>"
                                    data-tab-state="<?= e($status === 'translated' ? 'ready' : 'missing') ?>"
                                    data-contact-label-source="<?= e($contactPanelLabel($panelLabels, 'translation_source', __('translation_source', 'Contact'))) ?>"
                                    data-contact-label-ready="<?= e($contactPanelLabel($panelLabels, 'translation_ready', __('translation_ready', 'Contact'))) ?>"
                                    data-contact-label-missing="<?= e($contactPanelLabel($panelLabels, 'translation_missing', __('translation_missing', 'Contact'))) ?>"
                                    title="<?= e((string) ($tab['label'] ?? $localeCode)) ?>"
                                >
                                    <span class="contact-form-translation-tab-icon" aria-hidden="true">
                                        <span class="contact-form-translation-flag"><?= e($contactLocaleFlag($localeCode)) ?></span>
                                    </span>
                                    <span class="contact-form-translation-badge"><?= e($badge) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="contact-form-translation-panels contact-form-translation-panels--modal" data-contact-translations-root>
                        <?php foreach ($contactTranslationModalTabs as $tab): ?>
                            <?php
                            $localeCode = (string) ($tab['code'] ?? '');
                            $panel = is_array($contactTranslationPanels[$localeCode] ?? null) ? $contactTranslationPanels[$localeCode] : [];
                            $panelFields = is_array($panel['fields'] ?? null) ? $panel['fields'] : [];
                            $panelLabels = is_array($panel['form_labels'] ?? $tab['form_labels'] ?? null) ? ($panel['form_labels'] ?? $tab['form_labels']) : [];
                            $isSourcePanel = $localeCode === $contactTranslationSourceLocale;
                            $isActivePanel = $localeCode === $contactTranslationInitialModalLocale;
                            ?>
                            <section
                                class="contact-form-translation-panel<?= $isActivePanel ? ' is-active' : '' ?>"
                                data-contact-translation-panel="<?= e($localeCode) ?>"
                                data-contact-translation-ui="<?= e((string) json_encode($panelLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                                role="tabpanel"
                                <?= $isActivePanel ? '' : 'hidden' ?>
                            >
                                <div class="contact-form-fields-row">
                                    <?php if ($isSourcePanel): ?>
                                        <div class="form-group">
                                            <label class="form-label" for="contactSourceSubmitLabelModal"><?= e($contactPanelLabel($panelLabels, 'contact_form_submit_label_admin', __('contact_form_submit_label_admin', 'Contact'))) ?></label>
                                            <input
                                                id="contactSourceSubmitLabelModal"
                                                type="text"
                                                class="form-input"
                                                value="<?= e((string) ($panel['submit_label'] ?? '')) ?>"
                                                placeholder="<?= e($contactPanelLabel($panelLabels, 'contact_form_submit_placeholder', __('contact_form_submit_placeholder', 'Contact'))) ?>"
                                                data-contact-source-locale-field="submit_label"
                                            >
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="contactSourceSuccessMessageModal"><?= e($contactPanelLabel($panelLabels, 'contact_form_success_message_label', __('contact_form_success_message_label', 'Contact'))) ?></label>
                                            <input
                                                id="contactSourceSuccessMessageModal"
                                                type="text"
                                                class="form-input"
                                                value="<?= e((string) ($panel['success_message'] ?? '')) ?>"
                                                placeholder="<?= e($contactPanelLabel($panelLabels, 'contact_form_success_placeholder', __('contact_form_success_placeholder', 'Contact'))) ?>"
                                                data-contact-source-locale-field="success_message"
                                            >
                                        </div>
                                    <?php else: ?>
                                        <div class="form-group">
                                            <label class="form-label" for="contactTranslationSubmitLabel<?= e($localeCode) ?>"><?= e($contactPanelLabel($panelLabels, 'contact_form_submit_label_admin', __('contact_form_submit_label_admin', 'Contact'))) ?></label>
                                            <input
                                                id="contactTranslationSubmitLabel<?= e($localeCode) ?>"
                                                name="translations[<?= e($localeCode) ?>][submit_label]"
                                                type="text"
                                                class="form-input"
                                                value="<?= e((string) ($panel['submit_label'] ?? '')) ?>"
                                                placeholder="<?= e($contactPanelLabel($panelLabels, 'contact_form_submit_placeholder', __('contact_form_submit_placeholder', 'Contact'))) ?>"
                                            >
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="contactTranslationSuccessMessage<?= e($localeCode) ?>"><?= e($contactPanelLabel($panelLabels, 'contact_form_success_message_label', __('contact_form_success_message_label', 'Contact'))) ?></label>
                                            <input
                                                id="contactTranslationSuccessMessage<?= e($localeCode) ?>"
                                                name="translations[<?= e($localeCode) ?>][success_message]"
                                                type="text"
                                                class="form-input"
                                                value="<?= e((string) ($panel['success_message'] ?? '')) ?>"
                                                placeholder="<?= e($contactPanelLabel($panelLabels, 'contact_form_success_placeholder', __('contact_form_success_placeholder', 'Contact'))) ?>"
                                            >
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($isSourcePanel): ?>
                                    <div class="contact-form-translation-fields">
                                        <p class="text-muted mb-12"><?= e($contactPanelLabel($panelLabels, 'contact_form_translation_fields_help', __('contact_form_translation_fields_help', 'Contact'))) ?></p>
                                        <div class="contact-form-translation-fields-list" data-contact-source-translation-fields></div>
                                    </div>
                                <?php else: ?>
                                    <div class="contact-form-translation-fields">
                                        <p class="text-muted mb-12"><?= e($contactPanelLabel($panelLabels, 'contact_form_translation_fields_help', __('contact_form_translation_fields_help', 'Contact'))) ?></p>
                                        <?php foreach ($panelFields as $panelField): ?>
                                            <?php
                                            $translationKey = (string) ($panelField['key'] ?? '');
                                            $translationType = (string) ($panelField['type'] ?? 'text');
                                            $translationOptions = $normalizeOptionsList($panelField['options'] ?? []);
                                            ?>
                                            <div class="contact-form-translation-field-card">
                                                <div class="contact-form-translation-field-head">
                                                    <strong><?= e((string) ($panelField['source_label'] ?? $translationKey)) ?></strong>
                                                    <span class="contact-form-translation-field-type"><?= e($contactPanelLabel($panelLabels, 'contact_form_custom_type_' . $translationType, __('contact_form_custom_type_' . $translationType, 'Contact'))) ?></span>
                                                </div>

                                                <div class="contact-custom-field-grid contact-custom-field-grid--translation">
                                                    <div class="form-group">
                                                        <label class="form-label" for="contactTranslationLabel<?= e($localeCode . $translationKey) ?>"><?= e($contactPanelLabel($panelLabels, 'contact_form_custom_label', __('contact_form_custom_label', 'Contact'))) ?></label>
                                                        <input
                                                            id="contactTranslationLabel<?= e($localeCode . $translationKey) ?>"
                                                            type="text"
                                                            class="form-input"
                                                            name="translations[<?= e($localeCode) ?>][fields][<?= e($translationKey) ?>][label]"
                                                            value="<?= e((string) ($panelField['label'] ?? '')) ?>"
                                                            maxlength="120"
                                                        >
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label" for="contactTranslationPlaceholder<?= e($localeCode . $translationKey) ?>"><?= e($contactPanelLabel($panelLabels, 'contact_form_custom_placeholder', __('contact_form_custom_placeholder', 'Contact'))) ?></label>
                                                        <input
                                                            id="contactTranslationPlaceholder<?= e($localeCode . $translationKey) ?>"
                                                            type="text"
                                                            class="form-input"
                                                            name="translations[<?= e($localeCode) ?>][fields][<?= e($translationKey) ?>][placeholder]"
                                                            value="<?= e((string) ($panelField['placeholder'] ?? '')) ?>"
                                                            maxlength="190"
                                                        >
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="form-label" for="contactTranslationHelp<?= e($localeCode . $translationKey) ?>"><?= e($contactPanelLabel($panelLabels, 'contact_form_custom_help', __('contact_form_custom_help', 'Contact'))) ?></label>
                                                        <input
                                                            id="contactTranslationHelp<?= e($localeCode . $translationKey) ?>"
                                                            type="text"
                                                            class="form-input"
                                                            name="translations[<?= e($localeCode) ?>][fields][<?= e($translationKey) ?>][help]"
                                                            value="<?= e((string) ($panelField['help'] ?? '')) ?>"
                                                            maxlength="190"
                                                        >
                                                    </div>
                                                    <?php if (!empty($panelField['show_options'])): ?>
                                                        <div class="form-group">
                                                            <label class="form-label" for="contactTranslationOptions<?= e($localeCode . $translationKey) ?>"><?= e($contactPanelLabel($panelLabels, 'contact_form_custom_options_label', __('contact_form_custom_options_label', 'Contact'))) ?></label>
                                                            <textarea
                                                                id="contactTranslationOptions<?= e($localeCode . $translationKey) ?>"
                                                                class="form-input"
                                                                name="translations[<?= e($localeCode) ?>][fields][<?= e($translationKey) ?>][options]"
                                                                rows="3"
                                                                placeholder="<?= e($contactPanelLabel($panelLabels, 'contact_form_custom_options_placeholder', __('contact_form_custom_options_placeholder', 'Contact'))) ?>"
                                                            ><?= e(implode("\n", $translationOptions)) ?></textarea>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="modal-footer-info" data-contact-translation-footer-info><?= e($contactPanelLabel($contactSourcePanelLabels, 'contact_form_translations_help', __('contact_form_translations_help', 'Contact'))) ?></div>
                    <button type="button" class="btn btn-secondary" data-modal-close="contactTranslationsModal" data-contact-translation-close-btn><?= e($contactPanelLabel($contactSourcePanelLabels, 'close', __('close', 'Core'))) ?></button>
                    <button type="submit" class="btn btn-primary" data-contact-translation-save-btn><?= e($contactPanelLabel($contactSourcePanelLabels, 'save', __('save', 'Core'))) ?></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="contact-builder-offcanvas" data-contact-field-inspector aria-hidden="true">
        <button
            type="button"
            class="contact-builder-offcanvas__backdrop"
            data-contact-inspector-close
            aria-label="<?= e(__('close', 'Core')) ?>"
        ></button>
        <aside class="contact-builder-offcanvas__panel" role="dialog" aria-modal="true" aria-labelledby="contactBuilderInspectorTitle">
            <header class="contact-builder-offcanvas__head">
                <h3 id="contactBuilderInspectorTitle" data-contact-inspector-title><?= __('contact_form_builder_inspector_title', 'Contact') ?></h3>
                <button type="button" class="btn btn-sm btn-ghost" data-contact-inspector-close title="<?= e(__('close', 'Core')) ?>">
                    <i class="fas fa-times"></i>
                </button>
            </header>

            <div class="contact-builder-offcanvas__body">
                <p class="text-muted" data-contact-inspector-empty><?= __('contact_form_builder_inspector_none', 'Contact') ?></p>

                <div class="contact-builder-inspector-fields" data-contact-inspector-fields hidden>
                    <div class="form-group">
                        <label class="form-label" for="contactInspectorLabel"><?= __('contact_form_custom_label', 'Contact') ?></label>
                        <input id="contactInspectorLabel" type="text" class="form-input" data-contact-inspector-input="label" maxlength="120">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactInspectorKey"><?= __('contact_form_custom_key', 'Contact') ?></label>
                        <input id="contactInspectorKey" type="text" class="form-input" data-contact-inspector-input="key" maxlength="48" placeholder="delivery_company">
                    </div>

                    <div class="contact-form-fields-row">
                        <div class="form-group">
                            <label class="form-label" for="contactInspectorType"><?= __('contact_form_custom_type', 'Contact') ?></label>
                            <select id="contactInspectorType" class="form-select" data-contact-inspector-input="type">
                                <?php foreach ($customFieldTypes as $fieldType): ?>
                                    <?php
                                    $labelKey = 'contact_form_custom_type_' . $fieldType;
                                    $typeLabel = __($labelKey, 'Contact');
                                    if ($typeLabel === $labelKey) {
                                        $typeLabel = ucfirst($fieldType);
                                    }
                                    ?>
                                    <option value="<?= e($fieldType) ?>"><?= e($typeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contactInspectorWidth"><?= __('contact_form_custom_width_label', 'Contact') ?></label>
                            <select id="contactInspectorWidth" class="form-select" data-contact-inspector-input="width">
                                <?php foreach ($customFieldWidths as $fieldWidth): ?>
                                    <?php $widthLabel = $widthLabelMap[$fieldWidth] ?? strtoupper((string) $fieldWidth); ?>
                                    <option value="<?= e((string) $fieldWidth) ?>"><?= e($widthLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <label class="contact-form-option contact-builder-inspector-required">
                        <input type="checkbox" data-contact-inspector-input="required" value="1">
                        <span><?= __('contact_form_custom_required', 'Contact') ?></span>
                    </label>

                    <div class="form-group">
                        <label class="form-label" for="contactInspectorPlaceholder"><?= __('contact_form_custom_placeholder', 'Contact') ?></label>
                        <input id="contactInspectorPlaceholder" type="text" class="form-input" data-contact-inspector-input="placeholder" maxlength="190">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contactInspectorHelp"><?= __('contact_form_custom_help', 'Contact') ?></label>
                        <input id="contactInspectorHelp" type="text" class="form-input" data-contact-inspector-input="help" maxlength="190">
                    </div>

                    <div class="form-group is-hidden" data-contact-inspector-options-group>
                        <label class="form-label" for="contactInspectorOptions"><?= __('contact_form_custom_options_label', 'Contact') ?></label>
                        <input
                            id="contactInspectorOptions"
                            type="text"
                            class="form-input"
                            data-contact-inspector-input="options"
                            placeholder="<?= e(__('contact_form_custom_options_placeholder', 'Contact')) ?>"
                            readonly
                        >
                        <div class="contact-builder-inspector-options-actions">
                            <button type="button" class="btn btn-sm btn-outline" data-contact-open-options-modal>
                                <i class="fas fa-list"></i>
                                <?= __('contact_form_options_modal_open', 'Contact') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="contact-builder-offcanvas__foot" data-contact-inspector-actions hidden>
                <button type="button" class="btn btn-outline btn-sm" data-contact-duplicate-field>
                    <i class="fas fa-copy"></i>
                    <?= __('contact_form_builder_duplicate_field', 'Contact') ?>
                </button>
                <button type="button" class="btn btn-danger btn-sm" data-contact-delete-field>
                    <i class="fas fa-trash-alt"></i>
                    <?= __('contact_form_builder_delete_field', 'Contact') ?>
                </button>
            </footer>
        </aside>
    </div>

    <div
        class="modal-overlay is-initially-hidden"
        id="contactOptionsModal"
        aria-hidden="true"
        data-contact-options-modal
    >
        <div class="modal-container contact-options-modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-list modal-icon-info"></i>
                    <span data-contact-options-modal-title><?= __('contact_form_options_modal_title', 'Contact') ?></span>
                </h3>
                <button
                    type="button"
                    class="modal-close"
                    data-contact-options-close
                    aria-label="<?= e(__('close', 'Core')) ?>"
                >&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-12" data-contact-options-modal-help><?= __('contact_form_options_modal_help', 'Contact') ?></p>
                <div class="contact-options-modal-list" data-contact-options-modal-list></div>
                <button type="button" class="btn btn-sm btn-outline" data-contact-options-add>
                    <i class="fas fa-plus"></i>
                    <?= __('add', 'Core') ?>
                </button>
            </div>
            <div class="modal-footer">
                <div class="modal-footer-info" data-contact-options-modal-field></div>
                <button type="button" class="btn btn-secondary" data-contact-options-close><?= __('close', 'Core') ?></button>
                <button type="button" class="btn btn-primary" data-contact-options-save><?= __('contact_form_options_modal_apply', 'Contact') ?></button>
            </div>
        </div>
    </div>
</form>

<template id="contactCustomFieldTemplate">
    <article class="contact-custom-field-row" data-contact-custom-field-row>
        <div class="contact-custom-field-row__head">
            <strong><?= __('contact_form_custom_field_label', 'Contact') ?></strong>
            <div class="contact-custom-field-row__actions">
                <button type="button" class="btn btn-sm btn-ghost" data-contact-move-up title="<?= e(__('contact_form_custom_move_up', 'Contact')) ?>">
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-sm btn-ghost" data-contact-move-down title="<?= e(__('contact_form_custom_move_down', 'Contact')) ?>">
                    <i class="fas fa-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-ghost contact-custom-remove" data-contact-remove-field>
                    <i class="fas fa-trash-alt"></i>
                    <span><?= __('remove', 'Core') ?></span>
                </button>
            </div>
        </div>
        <div class="contact-custom-field-grid">
            <div class="form-group">
                <label class="form-label"><?= __('contact_form_custom_label', 'Contact') ?></label>
                <input type="text" class="form-input" name="custom_fields[__INDEX__][label]" maxlength="120" data-contact-field-label>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('contact_form_custom_key', 'Contact') ?></label>
                <input type="text" class="form-input" name="custom_fields[__INDEX__][key]" maxlength="48" placeholder="delivery_company" data-contact-field-key>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('contact_form_custom_type', 'Contact') ?></label>
                <select class="form-select" name="custom_fields[__INDEX__][type]" data-contact-field-type>
                    <?php foreach ($customFieldTypes as $fieldType): ?>
                        <?php
                        $labelKey = 'contact_form_custom_type_' . $fieldType;
                        $typeLabel = __($labelKey, 'Contact');
                        if ($typeLabel === $labelKey) {
                            $typeLabel = ucfirst($fieldType);
                        }
                        ?>
                        <option value="<?= e($fieldType) ?>"><?= e($typeLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group contact-custom-required">
                <label class="contact-form-option">
                    <input type="checkbox" name="custom_fields[__INDEX__][required]" value="1">
                    <span><?= __('contact_form_custom_required', 'Contact') ?></span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('contact_form_custom_width_label', 'Contact') ?></label>
                <select class="form-select" name="custom_fields[__INDEX__][width]">
                    <?php foreach ($customFieldWidths as $fieldWidth): ?>
                        <?php $widthLabel = $widthLabelMap[$fieldWidth] ?? strtoupper((string) $fieldWidth); ?>
                        <option value="<?= e((string) $fieldWidth) ?>" <?= $fieldWidth === 'full' ? 'selected' : '' ?> <?= $fieldWidth === 'full' ? 'data-contact-field-default' : '' ?>>
                            <?= e($widthLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('contact_form_custom_placeholder', 'Contact') ?></label>
                <input type="text" class="form-input" name="custom_fields[__INDEX__][placeholder]" maxlength="190">
            </div>
            <div class="form-group">
                <label class="form-label"><?= __('contact_form_custom_help', 'Contact') ?></label>
                <input type="text" class="form-input" name="custom_fields[__INDEX__][help]" maxlength="190">
            </div>
        </div>
        <div class="form-group contact-custom-field-options is-hidden" data-contact-field-options-group>
            <label class="form-label"><?= __('contact_form_custom_options_label', 'Contact') ?></label>
            <textarea class="form-input" name="custom_fields[__INDEX__][options]" rows="3" placeholder="<?= e(__('contact_form_custom_options_placeholder', 'Contact')) ?>"></textarea>
        </div>
    </article>
</template>

<template id="contactOptionModalItemTemplate">
    <div class="contact-options-modal-item" data-contact-options-item>
        <input
            type="text"
            class="form-input"
            data-contact-options-input
            placeholder="<?= e(__('contact_form_custom_options_placeholder', 'Contact')) ?>"
        >
        <button type="button" class="btn btn-sm btn-ghost is-danger" data-contact-options-remove aria-label="<?= e(__('delete', 'Core')) ?>">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div>
</template>

<script src="<?= module_asset('Contact', 'js/contact-admin.js') ?>"></script>
