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
$forms = is_array($forms ?? null) ? $forms : [];
$messages = is_array($messages ?? null) ? $messages : [];
$messageCounts = is_array($messageCounts ?? null) ? $messageCounts : ['all' => count($messages), 'new' => 0, 'read' => 0, 'archived' => 0];
$canManageForms = (bool) ($canManageForms ?? false);
$canDeleteForms = (bool) ($canDeleteForms ?? false);
$formTypeLabels = is_array($formTypeLabels ?? null) ? $formTypeLabels : [];
$formsById = [];
foreach ($forms as $form) {
    $currentFormId = trim((string) ($form['id'] ?? ''));
    if ($currentFormId === '') {
        continue;
    }
    $formsById[$currentFormId] = is_array($form) ? $form : [];
}

$messagesById = [];
foreach ($messages as $message) {
    $id = (string) ($message['id'] ?? '');
    if ($id === '') {
        continue;
    }

    $rawAttachments = is_array($message['attachments'] ?? null) ? array_values($message['attachments']) : [];
    $normalizedAttachments = [];
    foreach ($rawAttachments as $attachmentIndex => $attachment) {
        if (!is_array($attachment)) {
            continue;
        }

        $name = trim((string) ($attachment['name'] ?? ''));
        if ($name === '') {
            continue;
        }

        $path = trim((string) ($attachment['path'] ?? ''));
        $legacyUrl = trim((string) ($attachment['url'] ?? ''));
        $downloadUrl = $path !== ''
            ? url('/admin/contact/' . $id . '/attachment/' . (int) $attachmentIndex . '/download')
            : $legacyUrl;

        $normalizedAttachments[] = [
            'name' => $name,
            'size' => (int) ($attachment['size'] ?? 0),
            'ext' => (string) ($attachment['ext'] ?? ''),
            'download_url' => $downloadUrl,
            'url' => $legacyUrl,
        ];
    }

    $messageFormId = trim((string) ($message['form_id'] ?? ''));
    $messageType = trim((string) ($message['form_type'] ?? ''));
    if ($messageType === '' && $messageFormId !== '' && isset($formsById[$messageFormId])) {
        $messageType = trim((string) ($formsById[$messageFormId]['form_type'] ?? ''));
    }
    if ($messageType === '') {
        $messageType = 'contact';
    }
    $messageTypeLabel = trim((string) ($formTypeLabels[$messageType] ?? $messageType));

    $messagesById[$id] = [
        'id' => $id,
        'name' => (string) ($message['name'] ?? ''),
        'email' => (string) ($message['email'] ?? ''),
        'phone' => (string) ($message['phone'] ?? ''),
        'subject' => (string) ($message['subject'] ?? ''),
        'message' => (string) ($message['message'] ?? ''),
        'status' => (string) ($message['status'] ?? 'new'),
        'received' => human_date((string) ($message['created_at'] ?? '')),
        'source' => (string) ($message['source_url'] ?? ''),
        'form_type' => $messageType,
        'form_type_label' => $messageTypeLabel,
        'custom_values' => is_array($message['custom_values'] ?? null) ? array_values($message['custom_values']) : [],
        'attachments' => $normalizedAttachments,
    ];
}

$messagesJson = json_encode(
    $messagesById,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
);
if (!is_string($messagesJson)) {
    $messagesJson = '{}';
}
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e($pageTitle ?? __('contact_forms_list_title', 'Contact')) ?></h1>
        <p class="page-subtitle"><?= __('contact_forms_list_subtitle', 'Contact') ?></p>
    </div>
    <div class="page-header-actions" data-tour-target="contact-forms-toolbar">
        <button type="button" class="btn btn-sm btn-secondary contact-open-messages-btn" data-contact-open-messages>
            <i class="fas fa-inbox"></i>
            <span><?= __('contact_open_messages', 'Contact') ?></span>
            <span class="badge badge-warning" data-contact-count-trigger><?= (int) ($messageCounts['new'] ?? 0) ?></span>
        </button>
        <?php if ($canManageForms): ?>
            <a href="<?= url('/admin/contact/forms/create') ?>" class="btn btn-sm btn-primary" data-tour-target="contact-forms-create">
                <i class="fas fa-plus"></i>
                <?= __('contact_form_create_new', 'Contact') ?>
            </a>
        <?php endif; ?>
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
            <h2 class="admin-guidance-card__title"><?= __('contact_help_title', 'Contact') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('contact_help_intro', 'Contact') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('contact_help_step_structure', 'Contact') ?></li>
            <li><?= __('contact_help_step_translations', 'Contact') ?></li>
            <li><?= __('contact_help_step_messages', 'Contact') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <?php if ($canManageForms): ?>
                <a href="<?= url('/admin/contact/forms/create') ?>" class="btn btn-primary"><?= __('contact_help_action_create', 'Contact') ?></a>
            <?php endif; ?>
            <button type="button" class="btn btn-secondary" data-contact-open-messages><?= __('contact_help_action_messages', 'Contact') ?></button>
        </div>
    </div>
</div>

<div class="card contact-form-list" data-tour-target="contact-forms-list" data-tour-state="<?= $forms === [] ? 'empty' : 'ready' ?>">
    <?php if ($forms === []): ?>
        <div class="card-body">
            <div class="admin-empty-state-panel" data-tour-target="contact-forms-list" data-tour-state="empty">
                <div class="admin-empty-state-panel__icon" aria-hidden="true">
                    <i class="fas fa-address-book"></i>
                </div>
                <h2 class="admin-empty-state-panel__title"><?= __('contact_empty_title', 'Contact') ?></h2>
                <p class="admin-empty-state-panel__text"><?= __('contact_empty_text', 'Contact') ?></p>
                <div class="admin-empty-state-panel__actions">
                    <?php if ($canManageForms): ?>
                        <a href="<?= url('/admin/contact/forms/create') ?>" class="btn btn-primary"><?= __('contact_empty_action_create', 'Contact') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= __('contact_form_column_name', 'Contact') ?></th>
                        <th><?= __('contact_form_column_shortcode', 'Contact') ?></th>
                        <th><?= __('contact_form_column_status', 'Contact') ?></th>
                        <th><?= __('contact_form_column_updated', 'Contact') ?></th>
                        <th><?= __('actions', 'Core') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $form): ?>
                        <?php $formId = (string) ($form['id'] ?? ''); ?>
                        <?php $formSlug = (string) ($form['slug'] ?? ''); ?>
                        <?php $shortcode = '[contact-form slug="' . $formSlug . '"]'; ?>
                        <tr>
                            <td data-label="<?= __('contact_form_column_name', 'Contact') ?>">
                                <strong><?= e((string) ($form['name'] ?? '')) ?></strong>
                            </td>
                            <td data-label="<?= __('contact_form_column_shortcode', 'Contact') ?>" class="contact-form-shortcode-cell">
                                <button
                                    type="button"
                                    class="contact-form-shortcode-copy"
                                    data-contact-copy-shortcode
                                    data-copy-text="<?= e($shortcode) ?>"
                                    data-label-default="<?= e($shortcode) ?>"
                                    data-label-copied="<?= e(__('contact_form_shortcode_copied', 'Contact')) ?>"
                                    data-popover-message="<?= e(__('contact_form_shortcode_popover_copied', 'Contact')) ?>"
                                    title="<?= e(__('contact_form_shortcode_copy', 'Contact')) ?>"
                                    aria-label="<?= e(__('contact_form_shortcode_copy', 'Contact')) ?>"
                                >
                                    <i class="fas fa-laptop-code" aria-hidden="true"></i>
                                </button>
                            </td>
                            <td data-label="<?= __('contact_form_column_status', 'Contact') ?>">
                                <?php if (!empty($form['is_active'])): ?>
                                    <span class="badge badge-success"><?= __('contact_form_status_active', 'Contact') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= __('contact_form_status_inactive', 'Contact') ?></span>
                                <?php endif; ?>
                                <?php if (!empty($form['is_default'])): ?>
                                    <span class="badge badge-primary"><?= __('contact_form_status_default', 'Contact') ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= __('contact_form_column_updated', 'Contact') ?>">
                                <?= human_date((string) ($form['updated_at'] ?? '')) ?>
                            </td>
                            <td data-label="<?= __('actions', 'Core') ?>">
                                <div class="table-actions table-actions-compact">
                                    <?php if ($canManageForms): ?>
                                        <a
                                            href="<?= url('/admin/contact/forms/' . $formId . '/edit') ?>"
                                            class="table-action table-action-edit"
                                            title="<?= e(__('edit', 'Core')) ?>"
                                            aria-label="<?= e(__('edit', 'Core')) ?>"
                                        >
                                            <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                                        </a>

                                        <form method="POST" action="<?= url('/admin/contact/forms/' . $formId . '/toggle') ?>" class="form-inline">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="table-action table-action-toggle"
                                                title="<?= e(!empty($form['is_active']) ? __('contact_form_toggle_deactivate', 'Contact') : __('contact_form_toggle_activate', 'Contact')) ?>"
                                                aria-label="<?= e(!empty($form['is_active']) ? __('contact_form_toggle_deactivate', 'Contact') : __('contact_form_toggle_activate', 'Contact')) ?>"
                                            >
                                                <i class="fas fa-power-off" aria-hidden="true"></i>
                                            </button>
                                        </form>

                                        <?php if (empty($form['is_default'])): ?>
                                            <form method="POST" action="<?= url('/admin/contact/forms/' . $formId . '/default') ?>" class="form-inline">
                                                <?= csrf_field() ?>
                                                <button
                                                    type="submit"
                                                    class="table-action table-action-default"
                                                    title="<?= e(__('contact_form_set_default', 'Contact')) ?>"
                                                    aria-label="<?= e(__('contact_form_set_default', 'Contact')) ?>"
                                                >
                                                    <i class="fas fa-star" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($canDeleteForms): ?>
                                        <form method="POST" action="<?= url('/admin/contact/forms/' . $formId . '/delete') ?>" class="form-inline">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="table-action table-action-delete"
                                                data-action="confirm-delete"
                                                data-item-name="<?= e((string) ($form['name'] ?? '')) ?>"
                                                title="<?= e(__('delete', 'Core')) ?>"
                                                aria-label="<?= e(__('delete', 'Core')) ?>"
                                            >
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div
    class="contact-messages-modal"
    data-contact-messages-modal
    data-status-new="<?= e(__('contact_status_new', 'Contact')) ?>"
    data-status-read="<?= e(__('contact_status_read', 'Contact')) ?>"
    data-status-archived="<?= e(__('contact_status_archived', 'Contact')) ?>"
    data-read-url-template="<?= e(url('/admin/contact/__ID__/read')) ?>"
    data-csrf-token="<?= e(csrf_token()) ?>"
    data-can-manage-status-update="<?= $canManageForms ? '1' : '0' ?>"
    data-delete-confirm-message="<?= e(__('confirm_delete', 'Core')) ?>"
    data-delete-success-message="<?= e(__('message_deleted', 'Contact')) ?>"
    data-list-empty-message="<?= e(__('contact_messages_modal_empty', 'Contact')) ?>"
    hidden
>
    <div class="contact-messages-modal__backdrop" data-contact-modal-close></div>
    <div class="contact-messages-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="contactMessagesModalTitle">
        <header class="contact-messages-modal__header">
            <div>
                <h2 id="contactMessagesModalTitle" class="contact-messages-modal__title"><?= __('contact_messages_modal_title', 'Contact') ?></h2>
                <p class="contact-messages-modal__subtitle"><?= __('contact_messages_modal_subtitle', 'Contact') ?></p>
            </div>
            <button type="button" class="btn btn-sm btn-outline" data-contact-modal-close>
                <i class="fas fa-times"></i>
                <span><?= __('close', 'Core') ?></span>
            </button>
        </header>

        <div class="contact-messages-modal__status-bar">
            <span class="badge badge-info" data-contact-count-all><?= __('contact_filter_all', 'Contact') ?>: <?= (int) ($messageCounts['all'] ?? 0) ?></span>
            <span class="badge badge-warning" data-contact-count-new><?= __('contact_status_new', 'Contact') ?>: <?= (int) ($messageCounts['new'] ?? 0) ?></span>
            <span class="badge badge-success" data-contact-count-read><?= __('contact_status_read', 'Contact') ?>: <?= (int) ($messageCounts['read'] ?? 0) ?></span>
            <span class="badge badge-secondary" data-contact-count-archived><?= __('contact_status_archived', 'Contact') ?>: <?= (int) ($messageCounts['archived'] ?? 0) ?></span>
        </div>

        <div class="contact-messages-modal__content">
            <section class="contact-messages-modal__list" data-contact-messages-list>
                <?php if ($messagesById === []): ?>
                    <div class="empty-state">
                        <p><?= __('contact_messages_modal_empty', 'Contact') ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?= __('contact_from', 'Contact') ?></th>
                                    <th><?= __('contact_subject', 'Contact') ?></th>
                                    <th><?= __('contact_message_form_type', 'Contact') ?></th>
                                    <th><?= __('contact_status', 'Contact') ?></th>
                                    <th><?= __('contact_received_at', 'Contact') ?></th>
                                    <th><?= __('actions', 'Core') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $message): ?>
                                    <?php
                                    $id = (string) ($message['id'] ?? '');
                                    if ($id === '') {
                                        continue;
                                    }
                                    $status = (string) ($message['status'] ?? 'new');
                                    $messageFormId = trim((string) ($message['form_id'] ?? ''));
                                    $messageType = trim((string) ($message['form_type'] ?? ''));
                                    if ($messageType === '' && $messageFormId !== '' && isset($formsById[$messageFormId])) {
                                        $messageType = trim((string) ($formsById[$messageFormId]['form_type'] ?? ''));
                                    }
                                    if ($messageType === '') {
                                        $messageType = 'contact';
                                    }
                                    $messageTypeLabel = trim((string) ($formTypeLabels[$messageType] ?? $messageType));
                                    ?>
                                    <tr data-contact-message-row data-message-id="<?= e($id) ?>" data-message-status="<?= e($status) ?>">
                                        <td data-label="<?= __('contact_from', 'Contact') ?>">
                                            <strong><?= e((string) ($message['name'] ?? __('contact_unknown', 'Contact'))) ?></strong>
                                            <small class="text-muted d-block"><?= e((string) ($message['email'] ?? '')) ?></small>
                                        </td>
                                        <td data-label="<?= __('contact_subject', 'Contact') ?>">
                                            <strong><?= e((string) ((($message['subject'] ?? '') !== '') ? $message['subject'] : __('contact_no_subject', 'Contact'))) ?></strong>
                                            <p class="contact-message-preview"><?= e(str_limit((string) ($message['message'] ?? ''), 100)) ?></p>
                                        </td>
                                        <td data-label="<?= __('contact_message_form_type', 'Contact') ?>">
                                            <span class="badge badge-info"><?= e($messageTypeLabel) ?></span>
                                        </td>
                                        <td data-label="<?= __('contact_status', 'Contact') ?>">
                                            <?php if ($status === 'new'): ?>
                                                <span class="badge badge-warning" data-contact-message-status-badge><?= __('contact_status_new', 'Contact') ?></span>
                                            <?php elseif ($status === 'archived'): ?>
                                                <span class="badge badge-secondary" data-contact-message-status-badge><?= __('contact_status_archived', 'Contact') ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-success" data-contact-message-status-badge><?= __('contact_status_read', 'Contact') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="<?= __('contact_received_at', 'Contact') ?>">
                                            <?= human_date((string) ($message['created_at'] ?? '')) ?>
                                        </td>
                                        <td data-label="<?= __('actions', 'Core') ?>">
                                            <div class="table-actions table-actions-compact">
                                                <button
                                                    type="button"
                                                    class="table-action table-action-view"
                                                    data-contact-open-detail
                                                    data-message-id="<?= e($id) ?>"
                                                    title="<?= e(__('contact_action_view', 'Contact')) ?>"
                                                    aria-label="<?= e(__('contact_action_view', 'Contact')) ?>"
                                                >
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                                </button>
                                                <?php if ($canManageForms): ?>
                                                    <form method="POST" action="<?= url('/admin/contact/' . $id . '/archive') ?>" class="form-inline" data-contact-archive-form data-message-id="<?= e($id) ?>">
                                                        <?= csrf_field() ?>
                                                        <button
                                                            type="submit"
                                                            class="table-action table-action-archive"
                                                            data-contact-action-archive-btn
                                                            title="<?= e(__('contact_action_archive', 'Contact')) ?>"
                                                            aria-label="<?= e(__('contact_action_archive', 'Contact')) ?>"
                                                            <?= $status === 'archived' ? 'disabled' : '' ?>
                                                        >
                                                            <i class="fas fa-box-archive" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($canDeleteForms): ?>
                                                    <form method="POST" action="<?= url('/admin/contact/' . $id . '/delete') ?>" class="form-inline" data-contact-delete-form data-message-id="<?= e($id) ?>">
                                                        <?= csrf_field() ?>
                                                        <button
                                                            type="submit"
                                                            class="table-action table-action-delete"
                                                            data-contact-delete-btn
                                                            data-item-name="<?= e((string) ((($message['subject'] ?? '') !== '') ? $message['subject'] : ($message['email'] ?? $id))) ?>"
                                                            title="<?= e(__('delete', 'Core')) ?>"
                                                            aria-label="<?= e(__('delete', 'Core')) ?>"
                                                        >
                                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="contact-messages-modal__detail" data-contact-message-detail hidden>
                <div class="contact-messages-modal__detail-actions">
                    <button type="button" class="btn btn-sm btn-outline" data-contact-detail-back>
                        <i class="fas fa-arrow-left"></i>
                        <?= __('contact_messages_back_to_list', 'Contact') ?>
                    </button>
                </div>

                <div class="contact-detail-grid">
                    <div class="contact-detail-section card">
                        <h3 class="card-title"><?= __('contact_sender_information', 'Contact') ?></h3>
                        <dl class="contact-detail-list">
                            <dt><?= __('contact_field_name', 'Contact') ?></dt>
                            <dd class="contact-message-name" data-contact-detail-name>-</dd>
                            <dt><?= __('contact_field_email', 'Contact') ?></dt>
                            <dd class="contact-message-email" data-contact-detail-email>-</dd>
                            <dt><?= __('contact_field_phone', 'Contact') ?></dt>
                            <dd data-contact-detail-phone>-</dd>
                            <dt><?= __('contact_received_at', 'Contact') ?></dt>
                            <dd data-contact-detail-received>-</dd>
                        </dl>
                    </div>

                    <div class="contact-detail-section card">
                        <h3 class="card-title"><?= __('contact_message_detail', 'Contact') ?></h3>
                        <dl class="contact-detail-list">
                            <dt><?= __('contact_subject', 'Contact') ?></dt>
                            <dd class="contact-message-subject" data-contact-detail-subject>-</dd>
                            <dt><?= __('contact_status', 'Contact') ?></dt>
                            <dd data-contact-detail-status>-</dd>
                            <dt><?= __('contact_message_form_type', 'Contact') ?></dt>
                            <dd data-contact-detail-form-type>-</dd>
                            <dt><?= __('contact_source', 'Contact') ?></dt>
                            <dd class="contact-detail-agent" data-contact-detail-source>-</dd>
                        </dl>
                    </div>
                </div>

                <article class="card contact-message-body" data-contact-detail-message>
                    -
                </article>

                <article class="card contact-detail-card">
                    <h3 class="card-title mb-12"><?= __('contact_custom_values_title', 'Contact') ?></h3>
                    <div data-contact-detail-custom-values>-</div>
                </article>

                <article class="card contact-detail-card">
                    <h3 class="card-title mb-12"><?= __('contact_attachments_title', 'Contact') ?></h3>
                    <div data-contact-detail-attachments>-</div>
                </article>
            </section>
        </div>
    </div>
</div>

<script id="contactMessagesData" type="application/json"><?= $messagesJson ?></script>
<script src="<?= module_asset('Contact', 'js/contact-admin.js') ?>"></script>
