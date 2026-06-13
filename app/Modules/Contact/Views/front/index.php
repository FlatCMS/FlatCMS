<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<?php
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

$normalizeOptions = static function (mixed $options): array {
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

$contactFormRecord = is_array($contactForm ?? null) ? $contactForm : null;
$hasRenderableForm = is_array($contactFormRecord)
    && trim((string) ($contactFormRecord['id'] ?? '')) !== ''
    && !empty($contactFormRecord['is_active']);
$formState = array_merge([
    'id' => '',
    'slug' => '',
    'form_type' => 'contact',
    'newsletter_legal_url' => '',
    'newsletter_privacy_url' => '',
    'fields' => [],
    'custom_fields' => [],
    'attachments' => [
        'enabled' => false,
        'required' => false,
        'max_files' => 1,
        'max_size_mb' => 5,
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'zip'],
    ],
], $hasRenderableForm ? $contactFormRecord : []);

$rawCustomFields = is_array($formState['custom_fields'] ?? null) ? $formState['custom_fields'] : [];
$baseFields = is_array($formState['fields'] ?? null) ? $formState['fields'] : [];
$hasCustomFieldDefinition = false;
foreach ($rawCustomFields as $rawField) {
    if (!is_array($rawField)) {
        continue;
    }

    $candidateKey = trim((string) ($rawField['key'] ?? ''));
    $candidateLabel = trim((string) ($rawField['label'] ?? ''));
    if ($candidateKey !== '' && $candidateLabel !== '') {
        $hasCustomFieldDefinition = true;
        break;
    }
}

$baseFieldMap = [];
if (!$hasCustomFieldDefinition) {
    if ($toBool($baseFields['name'] ?? true, true)) {
        $baseFieldMap['name'] = [
            [
                'key' => 'name',
                'label' => __('contact_field_name', 'Contact'),
                'type' => 'text',
                'required' => true,
                'width' => 'half',
            ],
            [
                'key' => 'first_name',
                'label' => __('contact_field_first_name', 'Contact'),
                'type' => 'text',
                'required' => false,
                'width' => 'half',
            ],
        ];
    }
    if ($toBool($baseFields['email'] ?? true, true)) {
        $baseFieldMap['email'] = [[
            'key' => 'email',
            'label' => __('contact_field_email', 'Contact'),
            'type' => 'email',
            'required' => true,
            'width' => 'full',
        ]];
    }
    if ($toBool($baseFields['subject'] ?? true, true)) {
        $baseFieldMap['subject'] = [[
            'key' => 'subject',
            'label' => __('contact_subject', 'Contact'),
            'type' => 'text',
            'required' => true,
            'width' => 'full',
        ]];
    }
    if ($toBool($baseFields['phone'] ?? false, false)) {
        $baseFieldMap['phone'] = [[
            'key' => 'phone',
            'label' => __('contact_field_phone', 'Contact'),
            'type' => 'tel',
            'required' => false,
            'width' => 'half',
        ]];
    }
    if ($toBool($baseFields['message'] ?? true, true)) {
        $baseFieldMap['message'] = [[
            'key' => 'message',
            'label' => __('contact_field_message', 'Contact'),
            'type' => 'textarea',
            'required' => true,
            'width' => 'full',
        ]];
    }
}

$customFieldsByPosition = [
    'name' => [],
    'email' => [],
    'subject' => [],
    'phone' => [],
    'message' => [],
    'end' => [],
];

foreach ($rawCustomFields as $field) {
    $field = is_array($field) ? $field : [];
    $key = trim((string) ($field['key'] ?? ''));
    $label = trim((string) ($field['label'] ?? ''));
    if ($key === '' || $label === '') {
        continue;
    }

    $position = strtolower(trim((string) ($field['position_after'] ?? 'message')));
    if (!array_key_exists($position, $customFieldsByPosition)) {
        $position = 'message';
    }

    $customFieldsByPosition[$position][] = [
        'key' => $key,
        'label' => $label,
        'type' => (string) ($field['type'] ?? 'text'),
        'required' => $toBool($field['required'] ?? false, false),
        'width' => (string) ($field['width'] ?? 'full'),
        'placeholder' => (string) ($field['placeholder'] ?? ''),
        'help' => (string) ($field['help'] ?? ''),
        'options' => $field['options'] ?? [],
    ];
}

$customFields = [];
$appendFields = static function (array $fields) use (&$customFields): void {
    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $customFields[] = $field;
    }
};

foreach (['name', 'email', 'subject', 'phone', 'message'] as $anchor) {
    $appendFields($baseFieldMap[$anchor] ?? []);
    $appendFields($customFieldsByPosition[$anchor] ?? []);
}
$appendFields($customFieldsByPosition['end'] ?? []);

$attachments = array_merge([
    'enabled' => false,
    'required' => false,
    'max_files' => 1,
    'max_size_mb' => 5,
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'zip'],
], is_array($formState['attachments'] ?? null) ? $formState['attachments'] : []);
$submitLabel = trim((string) ($formState['submit_label'] ?? ''));
if ($submitLabel === '') {
    $submitLabel = __('contact_form_submit_label', 'Contact');
}
$attachmentExtensions = $normalizeOptions($attachments['allowed_extensions'] ?? []);
$attachmentAccept = implode(', ', array_map(static fn(string $ext): string => '.' . ltrim($ext, '.'), $attachmentExtensions));
$attachmentHint = __('contact_form_attachments_front_help', 'Contact', [
    'max_files' => (string) max(1, (int) ($attachments['max_files'] ?? 1)),
    'max_size' => (string) max(1, (int) ($attachments['max_size_mb'] ?? 5)),
    'extensions' => implode(', ', $attachmentExtensions),
]);
$normalizedCustomFields = [];
foreach ($customFields as $index => $field) {
    $field = is_array($field) ? $field : [];
    $fieldKey = trim((string) ($field['key'] ?? ''));
    $fieldLabel = trim((string) ($field['label'] ?? ''));
    if ($fieldKey === '' || $fieldLabel === '') {
        continue;
    }

    $width = strtolower(trim((string) ($field['width'] ?? 'full')));
    if (!in_array($width, ['full', 'half'], true)) {
        $width = 'full';
    }

    $fieldType = strtolower(trim((string) ($field['type'] ?? 'text')));
    $fieldRequired = $toBool($field['required'] ?? false, false);
    $fieldPlaceholder = trim((string) ($field['placeholder'] ?? ''));
    $fieldHelp = trim((string) ($field['help'] ?? ''));
    $fieldOptions = $normalizeOptions($field['options'] ?? []);
    $inputId = 'contactCustomField' . (int) $index;

    $normalizedCustomFields[] = [
        'key' => $fieldKey,
        'label' => $fieldLabel,
        'type' => $fieldType,
        'required' => $fieldRequired,
        'placeholder' => $fieldPlaceholder,
        'help' => $fieldHelp,
        'options' => $fieldOptions,
        'input_id' => $inputId,
        'width' => $width,
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

$renderCustomField = static function (array $field) use ($resolveAutocomplete): void {
    $fieldKey = (string) ($field['key'] ?? '');
    $fieldLabel = (string) ($field['label'] ?? '');
    $fieldType = (string) ($field['type'] ?? 'text');
    $fieldRequired = !empty($field['required']);
    $fieldPlaceholder = (string) ($field['placeholder'] ?? '');
    $fieldHelp = (string) ($field['help'] ?? '');
    $fieldOptions = is_array($field['options'] ?? null) ? $field['options'] : [];
    $inputId = (string) ($field['input_id'] ?? '');

    if ($fieldType === 'textarea'): ?>
        <div class="form-group">
            <label class="form-label" for="<?= e($inputId) ?>">
                <?= e($fieldLabel) ?>
                <?php if ($fieldRequired): ?>
                    <span class="flatcms-contact-required-mark" aria-hidden="true">*</span>
                <?php endif; ?>
            </label>
            <textarea
                id="<?= e($inputId) ?>"
                class="form-input flatcms-contact-message"
                name="cf[<?= e($fieldKey) ?>]"
                rows="4"
                autocomplete="<?= e($resolveAutocomplete($fieldKey, $fieldType)) ?>"
                placeholder="<?= e($fieldPlaceholder) ?>"
                <?= $fieldRequired ? 'required' : '' ?>
            ></textarea>
            <?php if ($fieldHelp !== ''): ?>
                <small class="flatcms-contact-hint"><?= e($fieldHelp) ?></small>
            <?php endif; ?>
        </div>
    <?php elseif (in_array($fieldType, ['select', 'radio'], true)): ?>
        <div class="form-group">
            <label class="form-label">
                <?= e($fieldLabel) ?>
                <?php if ($fieldRequired): ?>
                    <span class="flatcms-contact-required-mark" aria-hidden="true">*</span>
                <?php endif; ?>
            </label>
            <?php if ($fieldType === 'select'): ?>
                <select class="form-input" name="cf[<?= e($fieldKey) ?>]" <?= $fieldRequired ? 'required' : '' ?>>
                    <option value=""><?= e($fieldPlaceholder !== '' ? $fieldPlaceholder : __('contact_form_select_placeholder', 'Contact')) ?></option>
                    <?php foreach ($fieldOptions as $option): ?>
                        <option value="<?= e((string) $option) ?>"><?= e((string) $option) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <div class="flatcms-contact-choice-list">
                    <?php foreach ($fieldOptions as $option): ?>
                        <label class="flatcms-contact-choice-item">
                            <input
                                type="radio"
                                name="cf[<?= e($fieldKey) ?>]"
                                value="<?= e((string) $option) ?>"
                                <?= $fieldRequired ? 'required' : '' ?>
                            >
                            <span><?= e((string) $option) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($fieldHelp !== ''): ?>
                <small class="flatcms-contact-hint"><?= e($fieldHelp) ?></small>
            <?php endif; ?>
        </div>
    <?php elseif ($fieldType === 'checkbox' && $fieldOptions !== []): ?>
        <div class="form-group">
            <label class="form-label">
                <?= e($fieldLabel) ?>
                <?php if ($fieldRequired): ?>
                    <span class="flatcms-contact-required-mark" aria-hidden="true">*</span>
                <?php endif; ?>
            </label>
            <div class="flatcms-contact-choice-list">
                <?php foreach ($fieldOptions as $option): ?>
                    <label class="flatcms-contact-choice-item">
                        <input type="checkbox" name="cf[<?= e($fieldKey) ?>][]" value="<?= e((string) $option) ?>">
                        <span><?= e((string) $option) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($fieldHelp !== ''): ?>
                <small class="flatcms-contact-hint"><?= e($fieldHelp) ?></small>
            <?php endif; ?>
        </div>
    <?php elseif ($fieldType === 'checkbox'): ?>
        <div class="form-group">
            <label class="flatcms-contact-choice-item">
                <input type="checkbox" name="cf[<?= e($fieldKey) ?>]" value="1" <?= $fieldRequired ? 'required' : '' ?>>
                <span>
                    <?= e($fieldLabel) ?>
                    <?php if ($fieldRequired): ?>
                        <span class="flatcms-contact-required-mark" aria-hidden="true">*</span>
                    <?php endif; ?>
                </span>
            </label>
            <?php if ($fieldHelp !== ''): ?>
                <small class="flatcms-contact-hint"><?= e($fieldHelp) ?></small>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="form-group">
            <label class="form-label" for="<?= e($inputId) ?>">
                <?= e($fieldLabel) ?>
                <?php if ($fieldRequired): ?>
                    <span class="flatcms-contact-required-mark" aria-hidden="true">*</span>
                <?php endif; ?>
            </label>
            <input
                id="<?= e($inputId) ?>"
                class="form-input"
                type="<?= e(in_array($fieldType, ['text', 'email', 'tel', 'url', 'number', 'date'], true) ? $fieldType : 'text') ?>"
                name="cf[<?= e($fieldKey) ?>]"
                autocomplete="<?= e($resolveAutocomplete($fieldKey, $fieldType)) ?>"
                placeholder="<?= e($fieldPlaceholder) ?>"
                <?= $fieldRequired ? 'required' : '' ?>
            >
            <?php if ($fieldHelp !== ''): ?>
                <small class="flatcms-contact-hint"><?= e($fieldHelp) ?></small>
            <?php endif; ?>
        </div>
    <?php endif;
};

$formType = trim((string) ($formState['form_type'] ?? 'contact'));
$newsletterLegalUrl = trim((string) ($formState['newsletter_legal_url'] ?? ''));
$newsletterPrivacyUrl = trim((string) ($formState['newsletter_privacy_url'] ?? ''));
$showNewsletterLinks = $formType === 'newsletter_rgpd' && ($newsletterLegalUrl !== '' || $newsletterPrivacyUrl !== '');
$embedMode = !empty($embedMode);
$showIntro = isset($showIntro) ? (bool) $showIntro : !$embedMode;
$pageHeaderEnabled = !array_key_exists('page_header_enabled', $settings ?? [])
    ? true
    : ((int) ($settings['page_header_enabled'] ?? 0) === 1);
?>

<?php if (!$embedMode): ?>
    <?php if ($pageHeaderEnabled): ?>
        <header class="page-header">
            <div class="container">
                <h1><?= e(__('contact_page_title', 'Contact')) ?></h1>
            </div>
        </header>
    <?php endif; ?>
    <div class="content-wrapper">
        <div class="container">
            <div class="content">
<?php endif; ?>

<section class="flatcms-contact-native<?= $embedMode ? ' flatcms-contact-embed' : '' ?>">
    <?php if (!$embedMode && !$pageHeaderEnabled): ?>
        <h1 class="sr-only"><?= e(__('contact_page_title', 'Contact')) ?></h1>
    <?php endif; ?>
    <?php if ($showIntro): ?>
        <p class="flatcms-contact-intro"><?= e(__('contact_page_intro', 'Contact')) ?></p>
    <?php endif; ?>

    <?php if ($hasRenderableForm): ?>
        <form
            class="flatcms-contact-form flatcms-contact-native-form"
            method="post"
            action="<?= e((string) ($formAction ?? url('/contact/send'))) ?>"
            enctype="multipart/form-data"
            data-validation-required="<?= e(__('contact_form_client_required_message', 'Contact')) ?>"
        >
            <?= csrf_field() ?>
            <input type="hidden" name="source_url" value="<?= e((string) ($sourceUrl ?? url('/contact'))) ?>">
            <input type="hidden" name="contact_form_id" value="<?= e((string) ($formState['id'] ?? '')) ?>">
            <input type="hidden" name="contact_form_slug" value="<?= e((string) ($formState['slug'] ?? '')) ?>">

            <div class="flatcms-contact-honeypot" aria-hidden="true">
                <label for="contactCompany"><?= e(__('contact_form_honeypot_company', 'Contact')) ?></label>
                <input id="contactCompany" type="text" name="company" tabindex="-1" autocomplete="off">
            </div>

            <?php if ($normalizedCustomFields !== []): ?>
                <div class="flatcms-contact-custom-grid">
                    <?php foreach ($normalizedCustomFields as $field): ?>
                        <?php $widthClass = (($field['width'] ?? 'full') === 'half') ? 'flatcms-contact-custom-field--half' : 'flatcms-contact-custom-field--full'; ?>
                        <div class="flatcms-contact-custom-field <?= e($widthClass) ?>">
                            <?php $renderCustomField($field); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($showNewsletterLinks): ?>
                <p class="flatcms-contact-consent-links">
                    <span><?= e(__('contact_form_consent_links_prefix', 'Contact')) ?></span>
                    <?php if ($newsletterLegalUrl !== ''): ?>
                        <a href="<?= e($newsletterLegalUrl) ?>" target="_blank" rel="noopener noreferrer">
                            <?= e(__('contact_form_consent_legal_label', 'Contact')) ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($newsletterLegalUrl !== '' && $newsletterPrivacyUrl !== ''): ?>
                        <span>&middot;</span>
                    <?php endif; ?>
                    <?php if ($newsletterPrivacyUrl !== ''): ?>
                        <a href="<?= e($newsletterPrivacyUrl) ?>" target="_blank" rel="noopener noreferrer">
                            <?= e(__('contact_form_consent_privacy_label', 'Contact')) ?>
                        </a>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if ($toBool($attachments['enabled'] ?? false, false)): ?>
                <div class="form-group" data-contact-attachments>
                    <label class="form-label" for="contactAttachments">
                        <?= e(__('contact_form_attachments_input_label', 'Contact')) ?>
                        <?php if ($toBool($attachments['required'] ?? false, false)): ?>
                            <span class="flatcms-contact-required-mark" aria-hidden="true">*</span>
                        <?php endif; ?>
                    </label>
                    <input
                        id="contactAttachments"
                        class="form-input flatcms-contact-attachments-input"
                        type="file"
                        name="attachments[]"
                        data-contact-attachments-input
                        data-drop-title="<?= e(__('contact_form_attachments_drop_title', 'Contact')) ?>"
                        data-drop-hint="<?= e(__('contact_form_attachments_drop_hint', 'Contact')) ?>"
                        data-selected-none="<?= e(__('contact_form_attachments_selected_none', 'Contact')) ?>"
                        data-selected-count="<?= e(__('contact_form_attachments_selected_count', 'Contact')) ?>"
                        data-remove-label="<?= e(__('remove', 'Core')) ?>"
                        data-max-files="<?= (int) ($attachments['max_files'] ?? 1) ?>"
                        <?= ((int) ($attachments['max_files'] ?? 1)) > 1 ? 'multiple' : '' ?>
                        <?= $attachmentAccept !== '' ? 'accept="' . e($attachmentAccept) . '"' : '' ?>
                        <?= $toBool($attachments['required'] ?? false, false) ? 'required' : '' ?>
                    >
                    <small class="flatcms-contact-hint"><?= e($attachmentHint) ?></small>
                </div>
            <?php endif; ?>

                <?php if (!empty($turnstileEnabled) && !empty($turnstileSiteKey)): ?>
                <div class="flatcms-contact-captcha">
                    <div
                        class="cf-turnstile"
                        data-sitekey="<?= e((string) $turnstileSiteKey) ?>"
                        data-size="flexible"
                    ></div>
                </div>
                <?php endif; ?>

            <button type="submit" class="btn btn-primary">
                <?= e($submitLabel) ?>
            </button>
        </form>
    <?php else: ?>
        <div class="flatcms-contact-unavailable" role="status">
            <p class="flatcms-contact-unavailable-text"><?= e(__('contact_form_front_unavailable', 'Contact')) ?></p>
        </div>
    <?php endif; ?>
</section>

<?php if (!$embedMode): ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($turnstileEnabled) && !empty($turnstileSiteKey)): ?>
    <?= flatcms_front_external_script('https://challenges.cloudflare.com/turnstile/v0/api.js', [
        'async' => true,
        'defer' => true,
        'essential' => true,
        'data' => [
            'flatcms-turnstile' => '1',
        ],
    ]) ?>
<?php endif; ?>
