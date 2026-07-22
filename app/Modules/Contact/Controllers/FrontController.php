<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Contact\Controllers;

use App\Core\BaseController;
use App\Core\ContentDocumentStore;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Core\Mail\Mailer;
use App\Core\Security\Turnstile;
use App\Modules\Contact\Services\ContactFormTranslationService;
use App\Modules\Contact\Services\FormService;
use App\Modules\Contact\Services\MessageService;
use App\Modules\Pages\Support\SystemPages;
use App\Modules\Settings\Services\SiteBrandingTranslationService;
use App\Services\UpdateCatalogService;

class FrontController extends BaseController
{
    private MessageService $messages;
    private FormService $forms;
    private ContactFormTranslationService $translations;
    private ContentDocumentStore $pages;
    private array $settings;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Contact');
        $this->messages = new MessageService();
        $this->forms = new FormService();
        $this->translations = new ContactFormTranslationService();
        $this->pages = ContentDocumentStore::for('core/pages');
        $this->settings = (new SiteBrandingTranslationService())->resolveForLocale(
            FlatFile::settings(),
            (string) $this->request->locale()
        );
    }

    public function index(): void
    {
        if ($this->hasPublishedContactPage()) {
            $this->redirect(url('/page/contact'));
            return;
        }

        $this->renderFrontend('Contact/Views/front/index', [
            'pageTitle' => __('contact_page_title', 'Contact'),
            'formAction' => url('/contact/send'),
            'sourceUrl' => url($this->request->uri()),
            'contactForm' => $this->resolveActiveForm(),
            ...$this->getTurnstileViewData(),
        ]);
    }

    private function hasPublishedContactPage(): bool
    {
        $contactPage = $this->pages->findBy('slug', 'contact');
        if (!is_array($contactPage)) {
            return false;
        }

        return (string) ($contactPage['status'] ?? 'draft') === 'published';
    }

    protected function renderFrontend(string $template, array $data = []): void
    {
        $settings = $this->settings;

        $data['settings'] = $settings;
        $data['locale'] = $this->request->locale();
        $data = array_merge(
            $data,
            $this->getMenuPayload($settings),
            footer_render_payload($settings)
        );

        $this->view->render($template, $data, 'frontend.main');
    }

    protected function getMenuPayload(array $settings): array
    {
        $menus = FlatFile::settings('menus');

        return [
            'menuStandard' => $menus['main']['items'] ?? [],
        ];
    }

    public function submit(): void
    {
        $honeypot = trim((string) $this->request->input('company', ''));
        if ($honeypot !== '') {
            $this->succeed(__('contact_submit_success', 'Contact'));
            return;
        }

        if (!$this->isTrustedOrigin()) {
            $this->fail(__('contact_submit_error_origin', 'Contact'));
            return;
        }

        $token = trim((string) $this->request->input('_token', ''));
        if ($token !== '' && !$this->session->verifyToken($token)) {
            $this->fail(__('contact_submit_error_csrf', 'Contact'));
            return;
        }

        if (!$this->verifyContactCaptcha()) {
            return;
        }

        if ($this->isRateLimited()) {
            $this->fail(__('contact_submit_error_rate_limit', 'Contact'));
            return;
        }

        $formContext = $this->resolveSubmittedForm();
        $form = $formContext['form'];
        if (!is_array($form)) {
            $this->fail(__('contact_submit_error_form_unavailable', 'Contact'));
            return;
        }

        $strictFormMode = (bool) ($formContext['strict'] ?? false);

        $legacyName = $this->cleanText((string) $this->request->input('name', ''), 120);
        $legacyEmail = trim((string) $this->request->input('email', ''));
        $legacyPhone = $this->cleanText((string) $this->request->input('phone', ''), 40);
        $legacySubject = $this->cleanText((string) $this->request->input('subject', ''), 180);
        $legacyMessage = $this->cleanText((string) $this->request->input('message', ''), 5000, true);

        [$customValues, $customError] = $this->collectCustomValues($form, $strictFormMode);
        if ($customError !== '') {
            $this->fail($customError);
            return;
        }

        [$attachments, $attachmentsError] = $this->collectAttachments($form, $strictFormMode);
        if ($attachmentsError !== '') {
            $this->fail($attachmentsError);
            return;
        }

        $summary = $this->extractSummaryFromCustomValues($customValues);
        $name = $legacyName !== '' ? $legacyName : $summary['name'];
        $email = $legacyEmail !== '' ? mb_strtolower($legacyEmail) : $summary['email'];
        $phone = $legacyPhone !== '' ? $legacyPhone : $summary['phone'];
        $subject = $legacySubject !== '' ? $legacySubject : $summary['subject'];
        $message = $legacyMessage !== '' ? $legacyMessage : $summary['message'];

        if (
            $name === ''
            && $email === ''
            && $phone === ''
            && $subject === ''
            && $message === ''
            && $customValues === []
            && $attachments === []
        ) {
            $this->fail(__('contact_submit_error_required', 'Contact'));
            return;
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->fail(__('contact_submit_error_email', 'Contact'));
            return;
        }

        $recipient = '';
        if ($strictFormMode) {
            $recipient = trim((string) ($form['recipient_email'] ?? ''));
            if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                $recipient = '';
            }
        }

        if ($recipient === '') {
            $recipient = trim((string) $this->request->input('recipient', ''));
            if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                $recipient = '';
            }
        }

        $sourceUrl = $this->sanitizeSourceUrl((string) $this->request->input('source_url', ''));
        if ($sourceUrl === '') {
            $sourceUrl = $this->sanitizeSourceUrl((string) $this->request->header('Referer', ''));
        }

        $formType = $this->forms->sanitizeFormType((string) ($form['form_type'] ?? FormService::FORM_TYPE_CONTACT));
        $consent = $this->buildConsentPayload($form, $customValues);
        if (
            $formType === FormService::FORM_TYPE_NEWSLETTER
            && !($consent['accepted'] ?? false)
        ) {
            $this->fail(__('contact_submit_error_consent_required', 'Contact'));
            return;
        }

        $savedMessage = $this->messages->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message,
            'recipient' => $recipient,
            'status' => MessageService::STATUS_NEW,
            'source_url' => $sourceUrl,
            'source_path' => $this->extractSourcePath($sourceUrl),
            'ip' => $this->request->ip(),
            'user_agent' => $this->cleanText((string) $this->request->userAgent(), 255),
            'form_id' => (string) ($form['id'] ?? ''),
            'form_slug' => (string) ($form['slug'] ?? ''),
            'form_name' => (string) ($form['name'] ?? ''),
            'form_type' => $formType,
            'consent' => $consent,
            'custom_values' => $customValues,
            'attachments' => $attachments,
        ]);

        $savedMessageId = trim((string) ($savedMessage['id'] ?? ''));
        if ($savedMessageId === '' || $this->messages->find($savedMessageId) === null) {
            $this->fail(__('error.server', 'Core'));
            return;
        }

        $this->sendNotificationEmail($savedMessage);
        $this->sendDownloadLinksEmail($savedMessage);
        $this->session->set('contact_last_submit_at', time());
        $successMessage = trim((string) ($form['success_message'] ?? ''));
        if ($successMessage === '') {
            $successMessage = __('contact_submit_success', 'Contact');
        }
        $this->succeed($successMessage);
    }

    private function isRateLimited(): bool
    {
        $now = time();
        $last = (int) $this->session->get('contact_last_submit_at', 0);

        return ($now - $last) < 12;
    }

    private function isTrustedOrigin(): bool
    {
        $currentHost = $this->normalizedCurrentHost();
        if ($currentHost === '') {
            return true;
        }

        $origin = trim((string) $this->request->header('Origin', ''));
        if ($origin !== '') {
            $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
            if ($originHost !== '' && $originHost !== $currentHost) {
                return false;
            }
        }

        $referer = trim((string) $this->request->header('Referer', ''));
        if ($referer !== '') {
            $refererHost = strtolower((string) parse_url($referer, PHP_URL_HOST));
            if ($refererHost !== '' && $refererHost !== $currentHost) {
                return false;
            }
        }

        return true;
    }

    private function cleanText(string $value, int $maxLength = 255, bool $preserveNewLines = false): string
    {
        if ($preserveNewLines) {
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $value = preg_replace('#<\s*br\s*/?>#i', "\n", $value) ?? $value;
            $value = preg_replace('#<\s*/\s*(p|div|section|article|h[1-6]|blockquote)\s*>#i', "\n\n", $value) ?? $value;
            $value = preg_replace('#<\s*li[^>]*>#i', '- ', $value) ?? $value;
            $value = preg_replace('#<\s*/\s*li\s*>#i', "\n", $value) ?? $value;
        }

        $value = strip_tags($value);
        if ($preserveNewLines) {
            $value = preg_replace("/\r\n|\r/", "\n", $value);
            $value = preg_replace("/[ \t]+\n/", "\n", (string) $value);
            $value = preg_replace("/\n{3,}/", "\n\n", (string) $value);
        } else {
            $value = preg_replace('/\s+/', ' ', $value);
        }
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if ($maxLength <= 0) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength);
    }

    /**
     * @return array{form:?array<string,mixed>,strict:bool}
     */
    private function resolveSubmittedForm(): array
    {
        $locale = (string) $this->request->locale();
        $formId = trim((string) $this->request->input('contact_form_id', ''));
        $formSlug = trim((string) $this->request->input('contact_form_slug', ''));
        $requestedSpecificForm = $formId !== '' || $formSlug !== '';

        if ($formId !== '') {
            $byId = $this->forms->find($formId);
            if (is_array($byId) && !empty($byId['is_active'])) {
                return ['form' => $this->applySystemLegalUrls($this->translations->resolveForLocale($byId, $locale)), 'strict' => true];
            }
        }

        if ($formSlug !== '') {
            $normalizedSlug = $this->forms->sanitizeSlug($formSlug, 'contact-main');
            foreach ($this->forms->all() as $candidate) {
                if ((string) ($candidate['slug'] ?? '') === $normalizedSlug && !empty($candidate['is_active'])) {
                    return ['form' => $this->applySystemLegalUrls($this->translations->resolveForLocale($candidate, $locale)), 'strict' => true];
                }
            }
        }

        if ($requestedSpecificForm) {
            return ['form' => null, 'strict' => true];
        }

        $activeForm = $this->resolveActiveForm();
        if (is_array($activeForm)) {
            return ['form' => $activeForm, 'strict' => true];
        }

        return ['form' => null, 'strict' => false];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function resolveActiveForm(): ?array
    {
        $locale = (string) $this->request->locale();
        $defaultForm = $this->forms->getDefault();
        if (is_array($defaultForm) && !empty($defaultForm['is_active'])) {
            return $this->applySystemLegalUrls($this->translations->resolveForLocale($defaultForm, $locale));
        }

        foreach ($this->forms->all() as $candidate) {
            if (is_array($candidate) && !empty($candidate['is_active'])) {
                return $this->applySystemLegalUrls($this->translations->resolveForLocale($candidate, $locale));
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed>|null $form
     * @return array<string,mixed>|null
     */
    private function applySystemLegalUrls(?array $form): ?array
    {
        if (!is_array($form)) {
            return null;
        }

        $legalPage = SystemPages::findByKey($this->pages, SystemPages::LEGAL_NOTICE_KEY);
        $privacyPage = SystemPages::findByKey($this->pages, SystemPages::PRIVACY_POLICY_KEY);

        if (is_array($legalPage)) {
            $form['newsletter_legal_url'] = SystemPages::frontendUrl($legalPage);
        }

        if (is_array($privacyPage)) {
            $form['newsletter_privacy_url'] = SystemPages::frontendUrl($privacyPage);
        }

        return $form;
    }

    /**
     * @param array<string,mixed>|null $form
     * @return array{0:array<int,array<string,string>>,1:string}
     */
    private function collectCustomValues(?array $form, bool $strictMode): array
    {
        if (!$strictMode || !is_array($form)) {
            return [[], ''];
        }

        $customFields = is_array($form['custom_fields'] ?? null) ? $form['custom_fields'] : [];
        if ($customFields === []) {
            $customFields = $this->buildFallbackFieldsFromLegacyConfig($form);
        }
        if ($customFields === []) {
            return [[], ''];
        }

        $submittedValues = $this->request->input('cf', []);
        $submittedValues = is_array($submittedValues) ? $submittedValues : [];
        $values = [];

        foreach ($customFields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $label = trim((string) ($field['label'] ?? $key));
            $type = strtolower(trim((string) ($field['type'] ?? 'text')));
            $required = !empty($field['required']);
            $options = $this->normalizeFieldOptions($field['options'] ?? []);
            $rawValue = $submittedValues[$key] ?? '';

            if ($type === 'checkbox') {
                if ($options !== []) {
                    $selectedValues = is_array($rawValue) ? $rawValue : [$rawValue];
                    $selected = [];
                    foreach ($selectedValues as $selectedValue) {
                        $value = $this->cleanText((string) $selectedValue, 255);
                        if ($value === '') {
                            continue;
                        }
                        if (!in_array($value, $options, true)) {
                            return [[], __('contact_submit_error_custom_option', 'Contact', ['field' => $label])];
                        }
                        $selected[] = $value;
                    }

                    $selected = array_values(array_unique($selected));
                    if ($required && $selected === []) {
                        return [[], __('contact_submit_error_custom_required', 'Contact', ['field' => $label])];
                    }

                    if ($selected !== []) {
                        $values[] = [
                            'key' => $key,
                            'label' => $label,
                            'type' => $type,
                            'value' => implode(', ', $selected),
                        ];
                    }
                    continue;
                }

                $checked = !empty($rawValue);
                if ($required && !$checked) {
                    return [[], __('contact_submit_error_custom_required', 'Contact', ['field' => $label])];
                }

                if ($checked) {
                    $values[] = [
                        'key' => $key,
                        'label' => $label,
                        'type' => $type,
                        'value' => __('contact_boolean_yes', 'Contact'),
                    ];
                }
                continue;
            }

            $value = is_scalar($rawValue) ? trim((string) $rawValue) : '';
            if ($type === 'textarea') {
                $value = $this->cleanText($value, 3000, true);
            } else {
                $value = $this->cleanText($value, 255);
            }

            if ($required && $value === '') {
                return [[], __('contact_submit_error_custom_required', 'Contact', ['field' => $label])];
            }

            if ($value === '') {
                continue;
            }

            if ($type === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                return [[], __('contact_submit_error_custom_email', 'Contact', ['field' => $label])];
            }

            if ($type === 'url' && filter_var($value, FILTER_VALIDATE_URL) === false) {
                return [[], __('contact_submit_error_custom_url', 'Contact', ['field' => $label])];
            }

            if ($type === 'number' && !is_numeric($value)) {
                return [[], __('contact_submit_error_custom_number', 'Contact', ['field' => $label])];
            }

            if ($type === 'date' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return [[], __('contact_submit_error_custom_date', 'Contact', ['field' => $label])];
            }

            if (in_array($type, ['select', 'radio'], true) && $options !== [] && !in_array($value, $options, true)) {
                return [[], __('contact_submit_error_custom_option', 'Contact', ['field' => $label])];
            }

            $values[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'value' => $value,
            ];
        }

        return [$values, ''];
    }

    /**
     * @param array<string,mixed> $form
     * @return array<int,array<string,mixed>>
     */
    private function buildFallbackFieldsFromLegacyConfig(array $form): array
    {
        $legacy = is_array($form['fields'] ?? null) ? $form['fields'] : [];
        $toBool = static function (mixed $value, bool $default = false): bool {
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
                if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                    return true;
                }
                if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
                    return false;
                }
            }

            return $default;
        };

        $fields = [];
        if ($toBool($legacy['name'] ?? true, true)) {
            $fields[] = [
                'key' => 'name',
                'label' => __('contact_field_name', 'Contact'),
                'type' => 'text',
                'required' => true,
            ];
            $fields[] = [
                'key' => 'first_name',
                'label' => __('contact_field_first_name', 'Contact'),
                'type' => 'text',
                'required' => false,
            ];
        }
        if ($toBool($legacy['email'] ?? true, true)) {
            $fields[] = [
                'key' => 'email',
                'label' => __('contact_field_email', 'Contact'),
                'type' => 'email',
                'required' => true,
            ];
        }
        if ($toBool($legacy['subject'] ?? true, true)) {
            $fields[] = [
                'key' => 'subject',
                'label' => __('contact_subject', 'Contact'),
                'type' => 'text',
                'required' => true,
            ];
        }
        if ($toBool($legacy['phone'] ?? false, false)) {
            $fields[] = [
                'key' => 'phone',
                'label' => __('contact_field_phone', 'Contact'),
                'type' => 'tel',
                'required' => false,
            ];
        }
        if ($toBool($legacy['message'] ?? true, true)) {
            $fields[] = [
                'key' => 'message',
                'label' => __('contact_field_message', 'Contact'),
                'type' => 'textarea',
                'required' => true,
            ];
        }

        return $fields;
    }

    /**
     * @param array<int,array<string,string>> $customValues
     * @return array{name:string,email:string,phone:string,subject:string,message:string}
     */
    private function extractSummaryFromCustomValues(array $customValues): array
    {
        $summary = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'subject' => '',
            'message' => '',
        ];

        $firstName = '';
        $lastName = '';
        $directAliasMap = [
            'name' => ['name', 'full_name', 'fullname', 'nom', 'nom_complet'],
            'email' => ['email', 'e_mail', 'mail', 'courriel', 'adresse_email'],
            'phone' => ['phone', 'telephone', 'tel', 'mobile', 'portable'],
            'subject' => ['subject', 'sujet', 'objet', 'topic'],
            'message' => ['message', 'msg', 'content', 'contenu', 'description', 'comment', 'commentaire'],
        ];
        $firstNameAliases = ['first_name', 'firstname', 'prenom', 'given_name'];
        $lastNameAliases = ['last_name', 'lastname', 'nom_famille', 'family_name', 'surname'];

        foreach ($customValues as $customValue) {
            if (!is_array($customValue)) {
                continue;
            }

            $value = trim((string) ($customValue['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $key = strtolower(trim((string) ($customValue['key'] ?? '')));
            $label = strtolower(trim((string) ($customValue['label'] ?? '')));
            $labelSlug = str_replace('-', '_', str_slug($label));
            $type = strtolower(trim((string) ($customValue['type'] ?? '')));
            $aliases = array_values(array_unique(array_filter([$key, $labelSlug])));

            foreach ($aliases as $alias) {
                if ($summary['name'] === '' && in_array($alias, $directAliasMap['name'], true)) {
                    $summary['name'] = $this->cleanText($value, 120);
                    continue;
                }
                if ($summary['email'] === '' && in_array($alias, $directAliasMap['email'], true)) {
                    $summary['email'] = mb_strtolower(trim($value));
                    continue;
                }
                if ($summary['phone'] === '' && in_array($alias, $directAliasMap['phone'], true)) {
                    $summary['phone'] = $this->cleanText($value, 40);
                    continue;
                }
                if ($summary['subject'] === '' && in_array($alias, $directAliasMap['subject'], true)) {
                    $summary['subject'] = $this->cleanText($value, 180);
                    continue;
                }
                if ($summary['message'] === '' && in_array($alias, $directAliasMap['message'], true)) {
                    $summary['message'] = $this->cleanText($value, 5000, true);
                    continue;
                }
                if ($firstName === '' && in_array($alias, $firstNameAliases, true)) {
                    $firstName = $this->cleanText($value, 80);
                    continue;
                }
                if ($lastName === '' && in_array($alias, $lastNameAliases, true)) {
                    $lastName = $this->cleanText($value, 80);
                    continue;
                }
            }

            if ($summary['message'] === '' && $type === 'textarea') {
                $summary['message'] = $this->cleanText($value, 5000, true);
            }
        }

        if ($summary['name'] === '' && ($firstName !== '' || $lastName !== '')) {
            $summary['name'] = trim($firstName . ' ' . $lastName);
        }

        return $summary;
    }

    /**
     * @param array<string,mixed>|null $form
     * @return array{0:array<int,array<string,mixed>>,1:string}
     */
    private function collectAttachments(?array $form, bool $strictMode): array
    {
        if (!$strictMode || !is_array($form)) {
            return [[], ''];
        }

        $attachments = is_array($form['attachments'] ?? null) ? $form['attachments'] : [];
        $enabled = !empty($attachments['enabled']);
        if (!$enabled) {
            return [[], ''];
        }

        $required = !empty($attachments['required']);
        $maxFiles = max(1, min(5, (int) ($attachments['max_files'] ?? 1)));
        $maxSizeMb = max(1, min(25, (int) ($attachments['max_size_mb'] ?? 5)));
        $maxSizeBytes = $maxSizeMb * 1024 * 1024;
        $allowedExtensions = $this->normalizeExtensionList($attachments['allowed_extensions'] ?? []);
        if ($allowedExtensions === []) {
            $allowedExtensions = $this->forms->defaultAttachmentExtensions();
        }

        $files = $this->normalizeUploadedFiles($this->request->file('attachments'));
        if ($files === []) {
            if ($required) {
                return [[], __('contact_submit_error_attachments_required', 'Contact')];
            }

            return [[], ''];
        }

        if (count($files) > $maxFiles) {
            return [[], __('contact_submit_error_attachments_count', 'Contact', [
                'max' => (string) $maxFiles,
            ])];
        }

        $yearMonth = date('Y/m');
        $destination = 'resources/uploads/contact/' . $yearMonth;
        $relativeBase = 'contact/' . $yearMonth;
        $savedFiles = [];

        foreach ($files as $file) {
            $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_OK) {
                return [[], __('contact_submit_error_attachments_upload', 'Contact')];
            }

            $size = (int) ($file['size'] ?? 0);
            if ($size <= 0 || $size > $maxSizeBytes) {
                return [[], __('contact_submit_error_attachments_size', 'Contact', [
                    'max' => (string) $maxSizeMb,
                ])];
            }

            $originalName = $this->cleanFileName((string) ($file['name'] ?? ''));
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
                return [[], __('contact_submit_error_attachments_type', 'Contact')];
            }

            $storedName = upload_file($file, $destination, [
                'types' => $allowedExtensions,
                'max_size' => $maxSizeBytes,
            ]);

            if ($storedName === null) {
                return [[], __('contact_submit_error_attachments_upload', 'Contact')];
            }

            $relativePath = $relativeBase . '/' . $storedName;
            $savedFiles[] = [
                'name' => $originalName,
                'path' => $relativePath,
                'size' => $size,
                'ext' => $extension,
            ];
        }

        return [$savedFiles, ''];
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
            $normalized[] = $value;
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
            $extensions = preg_split('/[\r\n,\s;]+/', strtolower($extensions)) ?: [];
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

    /**
     * @param array<string,mixed>|null $files
     * @return array<int,array<string,mixed>>
     */
    private function normalizeUploadedFiles(?array $files): array
    {
        if (!is_array($files) || !isset($files['name'])) {
            return [];
        }

        $normalized = [];
        if (is_array($files['name'])) {
            $count = count($files['name']);
            for ($index = 0; $index < $count; $index++) {
                $entry = [
                    'name' => (string) ($files['name'][$index] ?? ''),
                    'type' => (string) ($files['type'][$index] ?? ''),
                    'tmp_name' => (string) ($files['tmp_name'][$index] ?? ''),
                    'error' => (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                    'size' => (int) ($files['size'][$index] ?? 0),
                ];
                if ($entry['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $normalized[] = $entry;
            }

            return $normalized;
        }

        $single = [
            'name' => (string) ($files['name'] ?? ''),
            'type' => (string) ($files['type'] ?? ''),
            'tmp_name' => (string) ($files['tmp_name'] ?? ''),
            'error' => (int) ($files['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($files['size'] ?? 0),
        ];
        if ($single['error'] === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        return [$single];
    }

    private function cleanFileName(string $name): string
    {
        $name = basename(str_replace('\\', '/', trim($name)));
        if ($name === '') {
            return 'file';
        }

        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?? '';
        $safe = trim($safe, '_');

        return $safe !== '' ? $safe : 'file';
    }

    private function sanitizeSourceUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            return '';
        }

        $currentHost = $this->normalizedCurrentHost();
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($currentHost !== '' && $host !== '' && $host !== $currentHost) {
            return '';
        }

        return $url;
    }

    private function normalizedCurrentHost(): string
    {
        $hostHeader = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
        if ($hostHeader === '') {
            return '';
        }

        if (str_starts_with($hostHeader, '[')) {
            $end = strpos($hostHeader, ']');
            if ($end !== false) {
                return substr($hostHeader, 1, $end - 1);
            }
        }

        $parts = explode(':', $hostHeader, 2);
        return $parts[0] ?? '';
    }

    private function extractSourcePath(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = (string) parse_url($url, PHP_URL_QUERY);

        if ($query !== '') {
            $path .= '?' . $query;
        }

        return $path;
    }

    /**
     * @return array{turnstileEnabled:bool,turnstileSiteKey:string}
     */
    private function getTurnstileViewData(): array
    {
        if (!$this->isContactCaptchaEnabled()) {
            return [
                'turnstileEnabled' => false,
                'turnstileSiteKey' => '',
            ];
        }

        $turnstile = new Turnstile();
        $siteKey = $turnstile->siteKey();
        $enabled = $turnstile->isEnabled() && $siteKey !== '';

        return [
            'turnstileEnabled' => $enabled,
            'turnstileSiteKey' => $enabled ? $siteKey : '',
        ];
    }

    private function verifyContactCaptcha(): bool
    {
        if (!$this->isContactCaptchaEnabled()) {
            return true;
        }

        $turnstile = new Turnstile();
        if (!$turnstile->isEnabled() || $turnstile->siteKey() === '' || $turnstile->secretKey() === '') {
            $this->fail(__('contact_submit_error_captcha_unavailable', 'Contact'));
            return false;
        }

        $token = (string) $this->request->input('cf-turnstile-response', '');
        $result = $turnstile->verify($token, $this->request->ip());
        if (!($result['success'] ?? false)) {
            $errorCodes = $result['error_codes'] ?? [];
            if (is_array($errorCodes) && $errorCodes !== []) {
                error_log(sprintf(
                    '[FlatCMS][Contact] Turnstile verification failed (%s)',
                    implode(',', array_map(static fn($code) => (string) $code, $errorCodes))
                ));
            }
            $this->fail(__('contact_submit_error_captcha', 'Contact'));
            return false;
        }

        return true;
    }

    private function isContactCaptchaEnabled(): bool
    {
        if ((int) ($this->settings['contact_enable_captcha'] ?? 0) !== 1) {
            return false;
        }

        // Global Turnstile env flag must be respected (local/dev can disable it).
        return (new Turnstile())->isEnabled();
    }

    /**
     * @param array<string,mixed> $message
     */
    private function sendNotificationEmail(array $message): void
    {
        if ((int) ($this->settings['contact_notification_enabled'] ?? 1) !== 1) {
            return;
        }

        $recipient = $this->resolveNotificationRecipient($message);
        if ($recipient === '') {
            return;
        }

        $subject = trim((string) ($message['subject'] ?? ''));
        if ($subject === '') {
            $subject = __('contact_no_subject', 'Contact');
        }

        $emailSubject = __('contact_notification_subject', 'Contact', [
            'subject' => $subject,
        ]);

        $bodyLines = [
            __('contact_notification_body_intro', 'Contact'),
            '',
            __('contact_field_name', 'Contact') . ': ' . (string) ($message['name'] ?? ''),
            __('contact_field_email', 'Contact') . ': ' . (string) ($message['email'] ?? ''),
            __('contact_field_phone', 'Contact') . ': ' . (string) ($message['phone'] ?? ''),
            __('contact_subject', 'Contact') . ': ' . (string) ($message['subject'] ?? ''),
            __('contact_received_at', 'Contact') . ': ' . (string) ($message['created_at'] ?? date('Y-m-d H:i:s')),
            __('contact_source', 'Contact') . ': ' . (string) ($message['source_url'] ?? ''),
            __('contact_field_ip', 'Contact') . ': ' . (string) ($message['ip'] ?? ''),
            '',
            __('contact_field_message', 'Contact') . ':',
            (string) ($message['message'] ?? ''),
        ];

        $customValues = is_array($message['custom_values'] ?? null) ? $message['custom_values'] : [];
        $customFieldRows = [];
        if ($customValues !== []) {
            $bodyLines[] = '';
            $bodyLines[] = __('contact_notification_custom_fields', 'Contact') . ':';
            foreach ($customValues as $customValue) {
                if (!is_array($customValue)) {
                    continue;
                }

                $label = trim((string) ($customValue['label'] ?? ''));
                $value = trim((string) ($customValue['value'] ?? ''));
                if ($label === '' || $value === '') {
                    continue;
                }

                $bodyLines[] = '- ' . $label . ': ' . $value;
                $customFieldRows[] = [
                    'label' => $label,
                    'value' => $value,
                ];
            }
        }

        $attachments = is_array($message['attachments'] ?? null) ? $message['attachments'] : [];
        $attachmentRows = [];
        $emailAttachments = [];
        if ($attachments !== []) {
            $bodyLines[] = '';
            $bodyLines[] = __('contact_notification_attachments', 'Contact') . ':';
            foreach ($attachments as $attachment) {
                if (!is_array($attachment)) {
                    continue;
                }

                $name = trim((string) ($attachment['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                $line = '- ' . $name;
                $resolvedPath = $this->resolveContactAttachmentAbsolutePath((string) ($attachment['path'] ?? ''));
                if ($resolvedPath !== '') {
                    $emailAttachments[] = [
                        'path' => $resolvedPath,
                        'name' => $name,
                    ];
                }

                $attachmentRows[] = $name;
                $bodyLines[] = $line;
            }
        }

        $body = implode("\n", $bodyLines);
        $nameValue = trim((string) ($message['name'] ?? ''));
        if ($nameValue === '') {
            $nameValue = __('contact_unknown', 'Contact');
        }

        $emailValue = trim((string) ($message['email'] ?? ''));
        $subjectValue = trim((string) ($message['subject'] ?? ''));
        if ($subjectValue === '') {
            $subjectValue = __('contact_no_subject', 'Contact');
        }

        $messageValue = trim((string) ($message['message'] ?? ''));
        if ($messageValue === '') {
            $messageValue = '-';
        }

        $esc = static function (string $value): string {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };

        $messageHtml = nl2br($esc($messageValue), false);
        $emailLink = $emailValue !== '' && filter_var($emailValue, FILTER_VALIDATE_EMAIL)
            ? '<a href="mailto:' . $esc($emailValue) . '"><u>' . $esc($emailValue) . '</u></a>'
            : '-';

        $htmlSections = [
            '<p>' . $esc(__('contact_notification_body_intro', 'Contact')) . '</p>',
            '<p><strong>' . $esc(__('contact_field_name', 'Contact') . ': ' . $nameValue) . '</strong></p>',
            '<p>' . $esc(__('contact_field_email', 'Contact')) . ': ' . $emailLink . '</p>',
            '<p>' . $esc(__('contact_field_phone', 'Contact')) . ': ' . $esc(trim((string) ($message['phone'] ?? '')) ?: '-') . '</p>',
            '<p><strong><em>' . $esc($subjectValue) . '</em></strong></p>',
            '<p>' . $esc(__('contact_field_message', 'Contact')) . ':</p>',
            '<p>' . $messageHtml . '</p>',
        ];

        if ($customFieldRows !== []) {
            $items = [];
            foreach ($customFieldRows as $row) {
                $items[] = '<li><strong>' . $esc($row['label']) . ':</strong> ' . nl2br($esc($row['value']), false) . '</li>';
            }
            $htmlSections[] = '<p><strong>' . $esc(__('contact_notification_custom_fields', 'Contact')) . ':</strong></p>';
            $htmlSections[] = '<ul>' . implode('', $items) . '</ul>';
        }

        if ($attachmentRows !== []) {
            $items = [];
            foreach ($attachmentRows as $attachmentName) {
                $items[] = '<li>' . $esc($attachmentName) . '</li>';
            }
            $htmlSections[] = '<p><strong>' . $esc(__('contact_notification_attachments', 'Contact')) . ':</strong></p>';
            $htmlSections[] = '<ul>' . implode('', $items) . '</ul>';
        }

        $htmlBody = implode("\n", $htmlSections);

        $mailer = new Mailer();
        $sent = $mailer->send($recipient, $emailSubject, $body, [
            'html_body' => $htmlBody,
            'attachments' => $emailAttachments,
        ]);
        if (!$sent && (bool) env('APP_DEBUG', false)) {
            error_log('[FlatCMS] Contact notification email could not be sent to ' . $recipient);
        }
    }

    /**
     * @param array<string,mixed> $message
     */
    private function sendDownloadLinksEmail(array $message): void
    {
        $formSlug = trim((string) ($message['form_slug'] ?? ''));
        if ($formSlug !== 'download-access') {
            return;
        }

        $recipient = trim((string) ($message['email'] ?? ''));
        if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $packages = $this->resolvePublishedDownloadPackages();
        if ($packages === []) {
            return;
        }

        $name = trim((string) ($message['name'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($this->extractCustomValue($message, ['prenom', 'firstname', 'first_name']) . ' ' . $this->extractCustomValue($message, ['nom', 'lastname', 'last_name'])));
        }

        $requestedPackage = $this->extractCustomValue($message, ['package_interest', 'package', 'download_package', 'requested_package']);
        $subject = __('contact_download_links_subject', 'Contact');

        $bodyLines = [];
        $introKey = $name !== '' ? 'contact_download_links_intro_named' : 'contact_download_links_intro';
        $bodyLines[] = __($introKey, 'Contact', ['name' => $name]);
        $bodyLines[] = '';
        $bodyLines[] = __('contact_download_links_body_intro', 'Contact');
        if ($requestedPackage !== '') {
            $bodyLines[] = __('contact_download_links_requested_package', 'Contact') . ': ' . $requestedPackage;
        }
        $bodyLines[] = '';

        $htmlItems = [];
        foreach ($packages as $package) {
            $line = '- ' . $package['name'] . ' (' . $package['version'] . ')' . "\n  " . $package['url'];
            if ($package['sha256'] !== '') {
                $line .= "\n  SHA256: " . $package['sha256'];
            }
            $bodyLines[] = $line;

            $html = '<li><strong>' . htmlspecialchars($package['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong> ';
            $html .= '<span>(' . htmlspecialchars($package['version'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ')</span><br>';
            $html .= '<a href="' . htmlspecialchars($package['url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"><u>' . htmlspecialchars($package['url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</u></a>';
            if ($package['sha256'] !== '') {
                $html .= '<br><small>SHA256: <code>' . htmlspecialchars($package['sha256'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></small>';
            }
            $html .= '</li>';
            $htmlItems[] = $html;
        }

        $bodyLines[] = '';
        $bodyLines[] = __('contact_download_links_body_closing', 'Contact');

        $htmlSections = [
            '<p>' . htmlspecialchars(__($introKey, 'Contact', ['name' => $name]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>',
            '<p>' . htmlspecialchars(__('contact_download_links_body_intro', 'Contact'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>',
        ];
        if ($requestedPackage !== '') {
            $htmlSections[] = '<p><strong>' . htmlspecialchars(__('contact_download_links_requested_package', 'Contact'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ':</strong> ' . htmlspecialchars($requestedPackage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        }
        $htmlSections[] = '<ul>' . implode('', $htmlItems) . '</ul>';
        $htmlSections[] = '<p>' . htmlspecialchars(__('contact_download_links_body_closing', 'Contact'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';

        $mailer = new Mailer();
        $sent = $mailer->send($recipient, $subject, implode("\n", $bodyLines), [
            'html_body' => implode("\n", $htmlSections),
        ]);

        if (!$sent && (bool) env('APP_DEBUG', false)) {
            error_log('[FlatCMS] Download links email could not be sent to ' . $recipient);
        }
    }

    /**
     * @return array<int,array{name:string,version:string,url:string,sha256:string}>
     */
    private function resolvePublishedDownloadPackages(): array
    {
        $service = new UpdateCatalogService();
        $packages = [];

        foreach (['core', 'extensions', 'appliances'] as $catalog) {
            $payload = $service->catalog($catalog);
            $records = is_array($payload['packages'] ?? null) ? $payload['packages'] : [];
            foreach ($records as $record) {
                if (!is_array($record)) {
                    continue;
                }

                $url = trim((string) ($record['download_url'] ?? ''));
                if ($url === '' || empty($record['download_ready']) || (string) ($record['availability'] ?? '') !== 'published') {
                    continue;
                }

                $packages[] = [
                    'name' => trim((string) ($record['name'] ?? 'Package')),
                    'version' => trim((string) ($record['version'] ?? '')),
                    'url' => $url,
                    'sha256' => trim((string) ($record['sha256'] ?? '')),
                ];
            }
        }

        return $packages;
    }

    /**
     * @param array<string,mixed> $message
     * @param array<int,string> $keys
     */
    private function extractCustomValue(array $message, array $keys): string
    {
        $customValues = is_array($message['custom_values'] ?? null) ? $message['custom_values'] : [];
        if ($customValues === []) {
            return '';
        }

        $normalizedKeys = array_map(static fn(string $key): string => strtolower(trim($key)), $keys);
        foreach ($customValues as $customValue) {
            if (!is_array($customValue)) {
                continue;
            }

            $key = strtolower(trim((string) ($customValue['key'] ?? '')));
            if ($key === '' || !in_array($key, $normalizedKeys, true)) {
                continue;
            }

            return trim((string) ($customValue['value'] ?? ''));
        }

        return '';
    }

    private function resolveContactAttachmentAbsolutePath(string $relativePath): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return '';
        }

        if (str_starts_with($relativePath, 'uploads/')) {
            $relativePath = ltrim(substr($relativePath, strlen('uploads/')), '/');
        }
        if (!str_starts_with($relativePath, 'contact/')) {
            $relativePath = 'contact/' . ltrim($relativePath, '/');
        }

        $uploadsRoot = BASE_PATH . '/resources/uploads';
        $absolutePath = $uploadsRoot . '/' . $relativePath;
        if (!is_file($absolutePath)) {
            return '';
        }

        $realUploadsRoot = realpath($uploadsRoot);
        $realAttachmentPath = realpath($absolutePath);
        if ($realUploadsRoot === false || $realAttachmentPath === false) {
            return '';
        }

        $normalizedUploadsRoot = rtrim(str_replace('\\', '/', $realUploadsRoot), '/');
        $normalizedAttachmentPath = str_replace('\\', '/', $realAttachmentPath);
        if (!str_starts_with($normalizedAttachmentPath, $normalizedUploadsRoot . '/')) {
            return '';
        }

        return $realAttachmentPath;
    }

    /**
     * @param array<string,mixed> $form
     * @param array<int,array<string,string>> $customValues
     * @return array<string,mixed>
     */
    private function buildConsentPayload(array $form, array $customValues): array
    {
        $formType = $this->forms->sanitizeFormType((string) ($form['form_type'] ?? FormService::FORM_TYPE_CONTACT));
        $legalUrl = trim((string) ($form['newsletter_legal_url'] ?? ''));
        $privacyUrl = trim((string) ($form['newsletter_privacy_url'] ?? ''));

        if ($formType !== FormService::FORM_TYPE_NEWSLETTER) {
            return [];
        }

        $accepted = false;
        foreach ($customValues as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = strtolower(trim((string) ($item['key'] ?? '')));
            if (!in_array($key, ['consent_rgpd', 'consent', 'gdpr_consent'], true)) {
                continue;
            }

            $value = trim((string) ($item['value'] ?? ''));
            if ($value !== '') {
                $accepted = true;
            }
            break;
        }

        return [
            'required' => true,
            'accepted' => $accepted,
            'timestamp' => $accepted ? date('Y-m-d H:i:s') : '',
            'ip' => (string) $this->request->ip(),
            'legal_url' => $legalUrl,
            'privacy_url' => $privacyUrl,
        ];
    }

    /**
     * @param array<string,mixed> $message
     */
    private function resolveNotificationRecipient(array $message): string
    {
        $candidates = [
            (string) ($message['recipient'] ?? ''),
            (string) ($this->settings['contact_notification_email'] ?? ''),
            (string) ($this->settings['site_email'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }

            if (filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false) {
                return $candidate;
            }
        }

        return '';
    }

    private function fail(string $message): void
    {
        if ($this->request->isAjax()) {
            $this->json([
                'success' => false,
                'message' => $message,
            ], 422);
            return;
        }

        $this->session->flash('error', $message);
        $this->back();
    }

    private function succeed(string $message): void
    {
        if ($this->request->isAjax()) {
            $this->json([
                'success' => true,
                'message' => $message,
            ]);
            return;
        }

        $this->session->flash('success', $message);
        $this->back();
    }
}
