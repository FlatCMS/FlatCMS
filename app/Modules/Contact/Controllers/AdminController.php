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
use App\Core\FlatFile;
use App\Core\I18n;
use App\Modules\Auth\Services\RoleService;
use App\Modules\Contact\Services\ContactFormTranslationService;
use App\Modules\Contact\Services\FormService;
use App\Modules\Contact\Services\MessageService;
use App\Modules\Pages\Support\SystemPages;

class AdminController extends BaseController
{
    private MessageService $messages;
    private FormService $forms;
    private ContactFormTranslationService $translations;

    public function __construct()
    {
        parent::__construct();
        I18n::load('Contact');
        I18n::load('Pages');
        $this->messages = new MessageService();
        $this->forms = new FormService();
        $this->translations = new ContactFormTranslationService();
    }

    public function index(): void
    {
        if (!$this->authorize('contact.view')) {
            return;
        }

        $currentUser = $this->session->get('user');
        $role = (string) ($currentUser['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER);
        $allMessages = $this->messages->all();

        $this->render('Contact/Views/admin/index', [
            'pageTitle' => __('contact_forms_list_title', 'Contact'),
            'forms' => $this->forms->all(),
            'canManageForms' => RoleService::hasPermission($role, 'contact.manage'),
            'canDeleteForms' => RoleService::hasPermission($role, 'contact.delete'),
            'messages' => array_slice($allMessages, 0, 120),
            'messageCounts' => $this->messages->counts($allMessages),
            'formTypeLabels' => $this->formTypeLabels(),
        ], 'admin.main');
    }

    public function formsIndex(): void
    {
        $this->index();
    }

    public function createForm(): void
    {
        if (!$this->authorize('contact.manage')) {
            return;
        }

        $this->render('Contact/Views/admin/forms/form', [
            'pageTitle' => __('contact_form_create_page_title', 'Contact'),
            'form' => null,
            'isEditMode' => false,
            'customFieldTypes' => $this->forms->allowedCustomFieldTypes(),
            'customFieldWidths' => $this->forms->allowedCustomFieldWidths(),
            'formTypes' => $this->forms->allowedFormTypes(),
            'formTypeLabels' => $this->formTypeLabels(),
            'formTypePresets' => $this->formTypePresets(),
            'requiredLegalPages' => $this->resolveRequiredLegalPages(),
        ], 'admin.main');
    }

    public function editForm(string $id): void
    {
        if (!$this->authorize('contact.manage')) {
            return;
        }

        $form = $this->forms->find($id);
        if ($form === null) {
            $this->session->flash('error', __('contact_form_not_found', 'Contact'));
            $this->redirectToFormsList();
            return;
        }

        $this->render('Contact/Views/admin/forms/form', [
            'pageTitle' => __('contact_form_edit_page_title', 'Contact'),
            'form' => $form,
            'isEditMode' => true,
            'customFieldTypes' => $this->forms->allowedCustomFieldTypes(),
            'customFieldWidths' => $this->forms->allowedCustomFieldWidths(),
            'formTypes' => $this->forms->allowedFormTypes(),
            'formTypeLabels' => $this->formTypeLabels(),
            'formTypePresets' => $this->formTypePresets(),
            'requiredLegalPages' => $this->resolveRequiredLegalPages(),
        ], 'admin.main');
    }

    public function exportCsv(): void
    {
        if (!$this->authorize('contact.view')) {
            return;
        }

        [$statusFilter, $search] = $this->resolveFilters();
        $items = $this->messages->filter($this->messages->all(), $statusFilter, $search);

        $filename = 'contact-messages-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            $this->session->flash('error', __('error.server', 'Core'));
            $this->back();
            return;
        }

        // UTF-8 BOM for Excel compatibility.
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            __('contact_field_id', 'Contact'),
            __('contact_field_name', 'Contact'),
            __('contact_field_email', 'Contact'),
            __('contact_field_phone', 'Contact'),
            __('contact_subject', 'Contact'),
            __('contact_field_message', 'Contact'),
            __('contact_status', 'Contact'),
            __('contact_received_at', 'Contact'),
            __('contact_source', 'Contact'),
            __('contact_field_ip', 'Contact'),
            __('contact_field_user_agent', 'Contact'),
        ]);

        foreach ($items as $item) {
            fputcsv($output, [
                (string) ($item['id'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['email'] ?? ''),
                (string) ($item['phone'] ?? ''),
                (string) ($item['subject'] ?? ''),
                (string) ($item['message'] ?? ''),
                (string) ($item['status'] ?? MessageService::STATUS_NEW),
                (string) ($item['created_at'] ?? ''),
                (string) ($item['source_url'] ?? ''),
                (string) ($item['ip'] ?? ''),
                (string) ($item['user_agent'] ?? ''),
            ]);
        }

        fclose($output);
        exit;
    }

    public function show(string $id): void
    {
        if (!$this->authorize('contact.view')) {
            return;
        }

        $message = $this->messages->find($id);
        if ($message === null) {
            $this->session->flash('error', __('message_not_found', 'Contact'));
            $this->redirect(url('/admin/contact'));
            return;
        }

        // Auto-mark as read when the detail page is opened.
        if (($message['status'] ?? MessageService::STATUS_NEW) === MessageService::STATUS_NEW) {
            $updated = $this->messages->updateStatus($id, MessageService::STATUS_READ);
            if ($updated !== null) {
                $message = $updated;
            }
        }

        $this->render('Contact/Views/admin/show', [
            'pageTitle' => __('contact_message_detail', 'Contact'),
            'message' => $message,
        ], 'admin.main');
    }

    public function downloadAttachment(string $id, string $index): void
    {
        if (!$this->authorize('contact.view')) {
            return;
        }

        $message = $this->messages->find($id);
        if ($message === null || !ctype_digit($index)) {
            $this->response->status(404)->html(__('error.not_found', 'Core'));
            return;
        }

        $attachments = is_array($message['attachments'] ?? null) ? array_values($message['attachments']) : [];
        $attachmentIndex = (int) $index;
        if (!isset($attachments[$attachmentIndex]) || !is_array($attachments[$attachmentIndex])) {
            $this->response->status(404)->html(__('error.not_found', 'Core'));
            return;
        }

        $attachment = $attachments[$attachmentIndex];
        $filename = trim((string) ($attachment['name'] ?? ''));
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename) ?: 'attachment';

        $relativePath = trim(str_replace('\\', '/', (string) ($attachment['path'] ?? '')), '/');
        if ($relativePath === '') {
            // Backward compatibility with old public attachments.
            $legacyUrl = trim((string) ($attachment['url'] ?? ''));
            if ($legacyUrl !== '') {
                $this->response->redirect($legacyUrl);
                return;
            }

            $this->response->status(404)->html(__('error.not_found', 'Core'));
            return;
        }

        if (str_starts_with($relativePath, 'uploads/')) {
            $relativePath = ltrim(substr($relativePath, strlen('uploads/')), '/');
        }
        if (!str_starts_with($relativePath, 'contact/')) {
            $relativePath = 'contact/' . ltrim($relativePath, '/');
        }
        if (strpos($relativePath, '..') !== false) {
            $this->response->status(404)->html(__('error.not_found', 'Core'));
            return;
        }

        $uploadsRoot = BASE_PATH . '/resources/uploads';
        $contactRoot = $uploadsRoot . '/contact';
        $contactRootRealPath = realpath($contactRoot);
        $filePath = $uploadsRoot . '/' . $relativePath;
        $fileRealPath = realpath($filePath);

        if (
            $contactRootRealPath === false
            || $fileRealPath === false
            || !is_file($fileRealPath)
        ) {
            $this->response->status(404)->html(__('error.not_found', 'Core'));
            return;
        }

        $normalizedRoot = rtrim(str_replace('\\', '/', $contactRootRealPath), '/');
        $normalizedFile = str_replace('\\', '/', $fileRealPath);
        if (
            $normalizedFile !== $normalizedRoot
            && !str_starts_with($normalizedFile, $normalizedRoot . '/')
        ) {
            $this->response->status(404)->html(__('error.not_found', 'Core'));
            return;
        }

        $this->response->download($fileRealPath, $filename);
    }

    public function markRead(string $id): void
    {
        $this->changeStatus($id, MessageService::STATUS_READ, __('message_marked_read', 'Contact'));
    }

    public function markNew(string $id): void
    {
        $this->changeStatus($id, MessageService::STATUS_NEW, __('message_marked_new', 'Contact'));
    }

    public function archive(string $id): void
    {
        $this->changeStatus($id, MessageService::STATUS_ARCHIVED, __('message_archived', 'Contact'));
    }

    public function delete(string $id): void
    {
        if (!$this->authorizeForAction('contact.delete')) {
            return;
        }

        if (!$this->verifyCsrfForAction()) {
            return;
        }

        $deleted = $this->messages->delete($id);
        if ($this->request->isAjax()) {
            if ($deleted) {
                $counts = $this->messages->counts($this->messages->all());
                $this->json([
                    'success' => true,
                    'message' => __('message_deleted', 'Contact'),
                    'counts' => $counts,
                    'id' => $id,
                ]);
                return;
            }

            $this->json([
                'success' => false,
                'message' => __('message_not_found', 'Contact'),
            ], 404);
            return;
        }

        if ($deleted) {
            $this->session->flash('success', __('message_deleted', 'Contact'));
        } else {
            $this->session->flash('error', __('message_not_found', 'Contact'));
        }

        $this->back();
    }

    public function storeForm(): void
    {
        if (!$this->authorize('contact.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $payload = $this->collectFormPayload();
        $errors = $this->validateFormPayload($payload, null);
        if ($errors !== []) {
            $this->session->flash('error', $errors[0]);
            $this->session->flash('old', ['contact_form' => $payload]);
            $this->redirectToFormsCreate();
            return;
        }

        $this->forms->create($payload);
        $this->session->flash('success', __('contact_form_created', 'Contact'));
        $this->redirectToFormsList();
    }

    public function updateForm(string $id): void
    {
        if (!$this->authorize('contact.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        if ($this->forms->find($id) === null) {
            $this->session->flash('error', __('contact_form_not_found', 'Contact'));
            $this->redirectToFormsList();
            return;
        }

        $payload = $this->collectFormPayload();
        $errors = $this->validateFormPayload($payload, $id);
        if ($errors !== []) {
            $this->session->flash('error', $errors[0]);
            $this->session->flash('old', ['contact_form' => $payload]);
            $this->redirectToFormsEdit($id);
            return;
        }

        $updated = $this->forms->update($id, $payload);
        if ($updated === null) {
            $this->session->flash('error', __('contact_form_not_found', 'Contact'));
            $this->redirectToFormsList();
            return;
        }

        $this->session->flash('success', __('contact_form_updated', 'Contact'));
        $this->redirectToFormsList();
    }

    public function deleteForm(string $id): void
    {
        if (!$this->authorize('contact.delete')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $deleted = $this->forms->delete($id);
        if ($deleted) {
            $this->session->flash('success', __('contact_form_deleted', 'Contact'));
        } else {
            $this->session->flash('error', __('contact_form_not_found', 'Contact'));
        }

        $this->redirectToFormsList();
    }

    public function toggleForm(string $id): void
    {
        if (!$this->authorize('contact.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $updated = $this->forms->toggleActive($id);
        if ($updated === null) {
            $this->session->flash('error', __('contact_form_not_found', 'Contact'));
            $this->redirectToFormsList();
            return;
        }

        $isActive = !empty($updated['is_active']);
        $this->session->flash('success', __($isActive ? 'contact_form_activated' : 'contact_form_deactivated', 'Contact'));
        $this->redirectToFormsList();
    }

    public function setDefaultForm(string $id): void
    {
        if (!$this->authorize('contact.manage')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $updated = $this->forms->setDefault($id);
        if ($updated === null) {
            $this->session->flash('error', __('contact_form_not_found', 'Contact'));
            $this->redirectToFormsList();
            return;
        }

        $this->session->flash('success', __('contact_form_default_set', 'Contact'));
        $this->redirectToFormsList();
    }

    private function changeStatus(string $id, string $status, string $successMessage): void
    {
        if (!$this->authorizeForAction('contact.manage')) {
            return;
        }

        if (!$this->verifyCsrfForAction()) {
            return;
        }

        $updated = $this->messages->updateStatus($id, $status);
        if ($this->request->isAjax()) {
            if ($updated === null) {
                $this->json([
                    'success' => false,
                    'message' => __('message_not_found', 'Contact'),
                ], 404);
                return;
            }

            $counts = $this->messages->counts($this->messages->all());
            $this->json([
                'success' => true,
                'message' => $successMessage,
                'status' => (string) ($updated['status'] ?? MessageService::STATUS_NEW),
                'id' => (string) ($updated['id'] ?? $id),
                'counts' => $counts,
            ]);
            return;
        }

        if ($updated === null) {
            $this->session->flash('error', __('message_not_found', 'Contact'));
            $this->back();
            return;
        }

        $this->session->flash('success', $successMessage);
        $this->back();
    }

    private function authorizeForAction(string $permission): bool
    {
        if (!$this->request->isAjax()) {
            return $this->authorize($permission);
        }

        $currentUser = $this->session->get('user');
        if (!is_array($currentUser)) {
            $this->json([
                'success' => false,
                'message' => __('error.unauthorized', 'Core'),
            ], 401);
            return false;
        }

        $role = (string) ($currentUser['role'] ?? \App\Modules\Auth\Services\RoleService::ROLE_MEMBER);
        if (!RoleService::hasPermission($role, $permission)) {
            $this->json([
                'success' => false,
                'message' => __('error.unauthorized', 'Core'),
            ], 403);
            return false;
        }

        return true;
    }

    private function verifyCsrfForAction(): bool
    {
        if (!$this->request->isAjax()) {
            return $this->verifyCsrf();
        }

        $token = $this->request->input('_token') ?? $this->request->header('X-CSRF-TOKEN');
        if (!$token || !$this->session->verifyToken((string) $token)) {
            $this->json([
                'success' => false,
                'message' => __('error.csrf', 'Core'),
            ], 422);
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectFormPayload(): array
    {
        $name = trim((string) $this->request->input('name', ''));
        $slugInput = trim((string) $this->request->input('slug', ''));
        $slug = $this->forms->sanitizeSlug($slugInput !== '' ? $slugInput : $name, 'contact-form');
        $formType = $this->forms->sanitizeFormType((string) $this->request->input('form_type', FormService::FORM_TYPE_CONTACT));
        $customFields = $this->request->input('custom_fields', []);
        $customFields = is_array($customFields) ? $customFields : [];
        $sourceLocale = trim((string) $this->request->input('source_locale', ''));
        $sourceCustomFields = $this->resolveCustomFieldsWithPreset($customFields, $formType);
        $translationsInput = $this->request->input('translations', []);
        $translationsInput = is_array($translationsInput) ? $translationsInput : [];
        $translationPayload = $this->translations->prepareSubmittedTranslations($translationsInput, [
            'submit_label' => trim((string) $this->request->input('submit_label', '')),
            'success_message' => trim((string) $this->request->input('success_message', '')),
            'custom_fields' => $sourceCustomFields,
        ], $sourceLocale);
        $requiredLegalPages = $this->resolveRequiredLegalPages();
        $legalUrl = trim((string) ($requiredLegalPages[SystemPages::LEGAL_NOTICE_KEY]['public_url'] ?? ''));
        $privacyUrl = trim((string) ($requiredLegalPages[SystemPages::PRIVACY_POLICY_KEY]['public_url'] ?? ''));

        return [
            'name' => $name,
            'slug' => $slug,
            'form_type' => $formType,
            'description' => trim((string) $this->request->input('description', '')),
            'recipient_email' => trim((string) $this->request->input('recipient_email', '')),
            'submit_label' => trim((string) ($translationPayload['source_values']['submit_label'] ?? '')),
            'success_message' => trim((string) ($translationPayload['source_values']['success_message'] ?? '')),
            'newsletter_legal_url' => $legalUrl,
            'newsletter_privacy_url' => $privacyUrl,
            'is_active' => $this->request->has('is_active'),
            'is_default' => $this->request->has('is_default'),
            'source_locale' => (string) ($translationPayload['source_locale'] ?? ''),
            'translations' => is_array($translationPayload['translations'] ?? null) ? $translationPayload['translations'] : [],
            'custom_fields' => $sourceCustomFields,
            'attachments' => [
                'enabled' => $this->request->has('attachments_enabled'),
                'required' => $this->request->has('attachments_required'),
                'max_files' => (int) $this->request->input('attachments_max_files', 1),
                'max_size_mb' => (int) $this->request->input('attachments_max_size_mb', 5),
                'allowed_extensions' => trim((string) $this->request->input('attachments_allowed_extensions', '')),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<int, string>
     */
    private function validateFormPayload(array $payload, ?string $excludeId = null): array
    {
        $errors = [];

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            $errors[] = __('contact_form_validation_name_required', 'Contact');
        }

        $slug = trim((string) ($payload['slug'] ?? ''));
        if ($slug === '') {
            $errors[] = __('contact_form_validation_slug_required', 'Contact');
        } elseif ($this->forms->slugExists($slug, $excludeId)) {
            $errors[] = __('contact_form_validation_slug_taken', 'Contact');
        }

        $formType = $this->forms->sanitizeFormType((string) ($payload['form_type'] ?? FormService::FORM_TYPE_CONTACT));
        if (!in_array($formType, $this->forms->allowedFormTypes(), true)) {
            $errors[] = __('contact_form_validation_type_invalid', 'Contact');
        }

        $recipientEmail = trim((string) ($payload['recipient_email'] ?? ''));
        if ($recipientEmail !== '' && !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('contact_form_validation_email_invalid', 'Contact');
        }

        $legalUrl = trim((string) ($payload['newsletter_legal_url'] ?? ''));
        if ($legalUrl !== '' && !$this->isValidContactPolicyUrl($legalUrl)) {
            $errors[] = __('contact_form_validation_legal_url_invalid', 'Contact');
        }

        $privacyUrl = trim((string) ($payload['newsletter_privacy_url'] ?? ''));
        if ($privacyUrl !== '' && !$this->isValidContactPolicyUrl($privacyUrl)) {
            $errors[] = __('contact_form_validation_privacy_url_invalid', 'Contact');
        }

        $customFields = is_array($payload['custom_fields'] ?? null) ? $payload['custom_fields'] : [];
        $allowedTypes = $this->forms->allowedCustomFieldTypes();
        $seenCustomKeys = [];
        $customFieldCount = 0;
        foreach ($customFields as $index => $field) {
            if (!is_array($field)) {
                continue;
            }

            $row = is_int($index) ? $index + 1 : ($customFieldCount + 1);
            $label = trim((string) ($field['label'] ?? ''));
            $keySource = trim((string) ($field['key'] ?? ($field['name'] ?? '')));
            $type = strtolower(trim((string) ($field['type'] ?? 'text')));
            $options = $this->parseOptions($field['options'] ?? []);

            $hasContent = $label !== ''
                || $keySource !== ''
                || trim((string) ($field['placeholder'] ?? '')) !== ''
                || trim((string) ($field['help'] ?? '')) !== ''
                || $options !== [];

            if (!$hasContent) {
                continue;
            }

            $customFieldCount++;
            if ($customFieldCount > 24) {
                $errors[] = __('contact_form_validation_custom_limit', 'Contact');
                break;
            }

            if ($label === '') {
                $errors[] = __('contact_form_validation_custom_label_required', 'Contact', [
                    'position' => (string) $row,
                ]);
                continue;
            }

            if (!in_array($type, $allowedTypes, true)) {
                $errors[] = __('contact_form_validation_custom_type_invalid', 'Contact', [
                    'label' => $label,
                ]);
                continue;
            }

            $normalizedKey = str_replace('-', '_', str_slug($keySource !== '' ? $keySource : $label));
            if ($normalizedKey === '') {
                $errors[] = __('contact_form_validation_custom_key_required', 'Contact', [
                    'label' => $label,
                ]);
                continue;
            }

            if (isset($seenCustomKeys[$normalizedKey])) {
                $errors[] = __('contact_form_validation_custom_key_duplicate', 'Contact', [
                    'key' => $normalizedKey,
                ]);
                continue;
            }
            $seenCustomKeys[$normalizedKey] = true;

            if (in_array($type, ['select', 'radio'], true) && $options === []) {
                $errors[] = __('contact_form_validation_custom_options_required', 'Contact', [
                    'label' => $label,
                ]);
            }
        }

        $attachments = is_array($payload['attachments'] ?? null) ? $payload['attachments'] : [];
        $attachmentsEnabled = !empty($attachments['enabled']);
        if ($attachmentsEnabled) {
            $maxFiles = (int) ($attachments['max_files'] ?? 1);
            if ($maxFiles < 1 || $maxFiles > 5) {
                $errors[] = __('contact_form_validation_attachments_max_files', 'Contact');
            }

            $maxSizeMb = (int) ($attachments['max_size_mb'] ?? 5);
            if ($maxSizeMb < 1 || $maxSizeMb > 25) {
                $errors[] = __('contact_form_validation_attachments_max_size', 'Contact');
            }

            $extensions = $this->parseExtensions($attachments['allowed_extensions'] ?? '');
            if ($extensions === []) {
                $errors[] = __('contact_form_validation_attachments_extensions_required', 'Contact');
            }
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    private function formTypeLabels(): array
    {
        return [
            FormService::FORM_TYPE_CONTACT => __('contact_form_type_contact', 'Contact'),
            FormService::FORM_TYPE_NEWSLETTER => __('contact_form_type_newsletter_rgpd', 'Contact'),
            FormService::FORM_TYPE_QUOTE => __('contact_form_type_quote', 'Contact'),
            FormService::FORM_TYPE_SUPPORT => __('contact_form_type_support', 'Contact'),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function formTypePresets(): array
    {
        return [
            FormService::FORM_TYPE_CONTACT => [
                'submit_label' => __('contact_form_preset_contact_submit', 'Contact'),
                'success_message' => __('contact_form_preset_contact_success', 'Contact'),
                'custom_fields' => [
                    ['key' => 'name', 'label' => __('contact_field_name', 'Contact'), 'type' => 'text', 'required' => true, 'width' => 'half', 'placeholder' => __('contact_form_name_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'first_name', 'label' => __('contact_field_first_name', 'Contact'), 'type' => 'text', 'required' => false, 'width' => 'half', 'placeholder' => __('contact_form_first_name_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'email', 'label' => __('contact_field_email', 'Contact'), 'type' => 'email', 'required' => true, 'width' => 'full', 'placeholder' => __('contact_form_email_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'subject', 'label' => __('contact_subject', 'Contact'), 'type' => 'text', 'required' => true, 'width' => 'full', 'placeholder' => __('contact_form_subject_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'phone', 'label' => __('contact_field_phone', 'Contact'), 'type' => 'tel', 'required' => false, 'width' => 'half', 'placeholder' => __('contact_form_phone_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'message', 'label' => __('contact_field_message', 'Contact'), 'type' => 'textarea', 'required' => true, 'width' => 'full', 'placeholder' => __('contact_form_message_placeholder', 'Contact'), 'help' => '', 'options' => []],
                ],
            ],
            FormService::FORM_TYPE_NEWSLETTER => [
                'submit_label' => __('contact_form_preset_newsletter_submit', 'Contact'),
                'success_message' => __('contact_form_preset_newsletter_success', 'Contact'),
                'custom_fields' => [
                    ['key' => 'email', 'label' => __('contact_field_email', 'Contact'), 'type' => 'email', 'required' => true, 'width' => 'full', 'placeholder' => __('contact_form_email_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'consent_rgpd', 'label' => __('contact_form_newsletter_consent_label', 'Contact'), 'type' => 'checkbox', 'required' => true, 'width' => 'full', 'placeholder' => '', 'help' => __('contact_form_newsletter_consent_help', 'Contact'), 'options' => []],
                ],
            ],
            FormService::FORM_TYPE_QUOTE => [
                'submit_label' => __('contact_form_preset_quote_submit', 'Contact'),
                'success_message' => __('contact_form_preset_quote_success', 'Contact'),
                'custom_fields' => [
                    ['key' => 'name', 'label' => __('contact_field_name', 'Contact'), 'type' => 'text', 'required' => true, 'width' => 'half', 'placeholder' => __('contact_form_name_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'email', 'label' => __('contact_field_email', 'Contact'), 'type' => 'email', 'required' => true, 'width' => 'half', 'placeholder' => __('contact_form_email_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'phone', 'label' => __('contact_field_phone', 'Contact'), 'type' => 'tel', 'required' => false, 'width' => 'half', 'placeholder' => __('contact_form_phone_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'company', 'label' => __('contact_form_preset_quote_company_label', 'Contact'), 'type' => 'text', 'required' => false, 'width' => 'half', 'placeholder' => __('contact_form_preset_quote_company_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'project_type', 'label' => __('contact_form_preset_quote_project_type_label', 'Contact'), 'type' => 'select', 'required' => true, 'width' => 'half', 'placeholder' => '', 'help' => '', 'options' => [__('contact_form_preset_quote_project_type_option_one', 'Contact'), __('contact_form_preset_quote_project_type_option_two', 'Contact')]],
                    ['key' => 'budget', 'label' => __('contact_form_preset_quote_budget_label', 'Contact'), 'type' => 'select', 'required' => false, 'width' => 'half', 'placeholder' => '', 'help' => '', 'options' => [__('contact_form_preset_quote_budget_option_one', 'Contact'), __('contact_form_preset_quote_budget_option_two', 'Contact')]],
                    ['key' => 'message', 'label' => __('contact_field_message', 'Contact'), 'type' => 'textarea', 'required' => true, 'width' => 'full', 'placeholder' => __('contact_form_message_placeholder', 'Contact'), 'help' => '', 'options' => []],
                ],
            ],
            FormService::FORM_TYPE_SUPPORT => [
                'submit_label' => __('contact_form_preset_support_submit', 'Contact'),
                'success_message' => __('contact_form_preset_support_success', 'Contact'),
                'custom_fields' => [
                    ['key' => 'name', 'label' => __('contact_field_name', 'Contact'), 'type' => 'text', 'required' => true, 'width' => 'half', 'placeholder' => __('contact_form_name_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'email', 'label' => __('contact_field_email', 'Contact'), 'type' => 'email', 'required' => true, 'width' => 'half', 'placeholder' => __('contact_form_email_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'order_ref', 'label' => __('contact_form_preset_support_order_ref_label', 'Contact'), 'type' => 'text', 'required' => false, 'width' => 'half', 'placeholder' => __('contact_form_preset_support_order_ref_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'subject', 'label' => __('contact_subject', 'Contact'), 'type' => 'text', 'required' => true, 'width' => 'half', 'placeholder' => __('contact_form_subject_placeholder', 'Contact'), 'help' => '', 'options' => []],
                    ['key' => 'message', 'label' => __('contact_field_message', 'Contact'), 'type' => 'textarea', 'required' => true, 'width' => 'full', 'placeholder' => __('contact_form_message_placeholder', 'Contact'), 'help' => '', 'options' => []],
                ],
            ],
        ];
    }

    /**
     * @param array<int, mixed> $customFields
     * @return array<int, mixed>
     */
    private function resolveCustomFieldsWithPreset(array $customFields, string $formType): array
    {
        if ($customFields !== []) {
            return $customFields;
        }

        $presets = $this->formTypePresets();
        if (!isset($presets[$formType])) {
            return [];
        }

        $fields = $presets[$formType]['custom_fields'] ?? [];
        return is_array($fields) ? $fields : [];
    }

    /**
     * @param mixed $options
     * @return array<int, string>
     */
    private function parseOptions(mixed $options): array
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
    private function parseExtensions(mixed $extensions): array
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

    private function isValidContactPolicyUrl(string $value): bool
    {
        $url = trim($value);
        if ($url === '') {
            return false;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return true;
        }

        if (!str_starts_with($url, '/')
            || str_starts_with($url, '//')
            || preg_match('/\s/', $url)
        ) {
            return false;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        if (isset($parts['scheme'])
            || isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
        ) {
            return false;
        }

        $path = trim((string) ($parts['path'] ?? ''));
        return $path !== '' && str_starts_with($path, '/');
    }

    private function redirectToFormsList(): void
    {
        $this->redirect(url('/admin/contact/forms'));
    }

    private function redirectToFormsCreate(): void
    {
        $this->redirect(url('/admin/contact/forms/create'));
    }

    private function redirectToFormsEdit(string $id): void
    {
        $this->redirect(url('/admin/contact/forms/' . $id . '/edit'));
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveFilters(): array
    {
        $statusFilter = trim((string) $this->request->input('status', 'all'));
        if ($statusFilter !== 'all' && !in_array($statusFilter, MessageService::allowedStatuses(), true)) {
            $statusFilter = 'all';
        }

        $search = trim((string) $this->request->input('q', ''));

        return [$statusFilter, $search];
    }

    /**
     * @return array<string,array<string,string>>
     */
    private function resolveRequiredLegalPages(): array
    {
        $pagesStore = FlatFile::for('core/pages');
        $required = SystemPages::ensureRequired($pagesStore, static function (string $key): string {
            return __($key, 'Pages');
        });

        $map = [];
        foreach ([SystemPages::LEGAL_NOTICE_KEY, SystemPages::PRIVACY_POLICY_KEY] as $requiredKey) {
            $page = $required[$requiredKey] ?? null;
            if (!is_array($page)) {
                $page = SystemPages::findByKey($pagesStore, $requiredKey);
            }

            $map[$requiredKey] = [
                'title' => is_array($page) ? trim((string) ($page['title'] ?? '')) : '',
                'edit_url' => is_array($page) ? SystemPages::adminEditUrl($page) : '',
                'public_url' => is_array($page) ? SystemPages::frontendUrl($page) : '',
            ];
        }

        return $map;
    }
}
