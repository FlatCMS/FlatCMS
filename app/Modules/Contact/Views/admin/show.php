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

<?php $status = (string) ($message['status'] ?? 'new'); ?>
<?php
$customValues = is_array($message['custom_values'] ?? null) ? array_values($message['custom_values']) : [];
$attachments = is_array($message['attachments'] ?? null) ? array_values($message['attachments']) : [];
$messageId = (string) ($message['id'] ?? '');
?>

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= __('contact_message_detail', 'Contact') ?></h1>
        <p class="page-subtitle contact-message-subject"><?= e((string) (($message['subject'] ?? '') !== '' ? $message['subject'] : __('contact_no_subject', 'Contact'))) ?></p>
    </div>
    <div class="page-header-actions contact-detail-actions">
        <a href="<?= url('/admin/contact') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            <?= __('contact_back_to_list', 'Contact') ?>
        </a>

        <?php if ($status !== 'read'): ?>
            <form method="POST" action="<?= url('/admin/contact/' . $message['id'] . '/read') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline"><?= __('contact_action_mark_read', 'Contact') ?></button>
            </form>
        <?php endif; ?>

        <?php if ($status !== 'new'): ?>
            <form method="POST" action="<?= url('/admin/contact/' . $message['id'] . '/new') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline"><?= __('contact_action_mark_new', 'Contact') ?></button>
            </form>
        <?php endif; ?>

        <?php if ($status !== 'archived'): ?>
            <form method="POST" action="<?= url('/admin/contact/' . $message['id'] . '/archive') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline"><?= __('contact_action_archive', 'Contact') ?></button>
            </form>
        <?php endif; ?>

        <form method="POST" action="<?= url('/admin/contact/' . $message['id'] . '/delete') ?>">
            <?= csrf_field() ?>
            <button
                type="submit"
                class="btn btn-danger"
                data-action="confirm-delete"
                data-item-name="<?= e((string) (($message['subject'] ?? '') !== '' ? $message['subject'] : ($message['email'] ?? ''))) ?>"
            >
                <?= __('delete', 'Core') ?>
            </button>
        </form>
    </div>
</div>

<div class="card admin-guidance-card" data-admin-help-template hidden>
    <div class="card-body">
        <div class="admin-guidance-card__head">
            <div class="admin-guidance-card__eyebrow-row">
                <span class="admin-guidance-card__icon" aria-hidden="true">
                    <i class="fas fa-inbox"></i>
                </span>
                <span class="admin-guidance-card__eyebrow"><?= __('contact_help_badge', 'Contact') ?></span>
            </div>
            <h2 class="admin-guidance-card__title"><?= __('contact_message_detail', 'Contact') ?></h2>
            <p class="admin-guidance-card__copy"><?= __('contact_detail_help_intro', 'Contact') ?></p>
        </div>
        <ul class="admin-guidance-card__list">
            <li><?= __('contact_detail_help_step_sender', 'Contact') ?></li>
            <li><?= __('contact_detail_help_step_message', 'Contact') ?></li>
            <li><?= __('contact_detail_help_step_followup', 'Contact') ?></li>
        </ul>
        <div class="admin-guidance-card__actions">
            <a href="<?= url('/admin/contact') ?>" class="btn btn-primary"><?= __('contact_back_to_list', 'Contact') ?></a>
        </div>
    </div>
</div>

<div class="card contact-detail-card">
    <div class="card-body contact-detail-grid">
        <section class="contact-detail-section">
            <h2 class="card-title"><?= __('contact_sender_information', 'Contact') ?></h2>
            <dl class="contact-detail-list">
                <dt><?= __('contact_field_name', 'Contact') ?></dt>
                <dd class="contact-message-name"><?= e((string) ($message['name'] ?? __('contact_unknown', 'Contact'))) ?></dd>

                <dt><?= __('contact_field_email', 'Contact') ?></dt>
                <dd class="contact-message-email">
                    <?php if (!empty($message['email'])): ?>
                        <a class="contact-message-email-link" href="mailto:<?= e((string) $message['email']) ?>"><?= e((string) $message['email']) ?></a>
                    <?php else: ?>
                        <?= __('contact_unknown', 'Contact') ?>
                    <?php endif; ?>
                </dd>

                <dt><?= __('contact_field_phone', 'Contact') ?></dt>
                <dd><?= e((string) ($message['phone'] ?? '')) ?: '—' ?></dd>

                <dt><?= __('contact_status', 'Contact') ?></dt>
                <dd>
                    <?php if ($status === 'new'): ?>
                        <span class="badge badge-warning"><?= __('contact_status_new', 'Contact') ?></span>
                    <?php elseif ($status === 'archived'): ?>
                        <span class="badge badge-secondary"><?= __('contact_status_archived', 'Contact') ?></span>
                    <?php else: ?>
                        <span class="badge badge-info"><?= __('contact_status_read', 'Contact') ?></span>
                    <?php endif; ?>
                </dd>
            </dl>
        </section>

        <section class="contact-detail-section">
            <h2 class="card-title"><?= __('contact_technical_information', 'Contact') ?></h2>
            <dl class="contact-detail-list">
                <dt><?= __('contact_received_at', 'Contact') ?></dt>
                <dd><?= human_date((string) ($message['created_at'] ?? '')) ?></dd>

                <dt><?= __('contact_source', 'Contact') ?></dt>
                <dd>
                    <?php if (!empty($message['source_url'])): ?>
                        <a href="<?= e((string) $message['source_url']) ?>" target="_blank" rel="noopener noreferrer">
                            <?= e((string) ($message['source_path'] ?? $message['source_url'])) ?>
                        </a>
                    <?php elseif (!empty($message['source_path'])): ?>
                        <?= e((string) $message['source_path']) ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </dd>

                <dt><?= __('contact_field_ip', 'Contact') ?></dt>
                <dd><?= e((string) ($message['ip'] ?? '')) ?: '—' ?></dd>

                <dt><?= __('contact_field_user_agent', 'Contact') ?></dt>
                <dd class="contact-detail-agent"><?= e((string) ($message['user_agent'] ?? '')) ?: '—' ?></dd>
            </dl>
        </section>
    </div>
</div>

<div class="card contact-detail-card">
    <div class="card-header">
        <h2 class="card-title"><?= __('contact_field_message', 'Contact') ?></h2>
    </div>
    <div class="card-body">
        <div class="contact-message-body"><?= nl2br(e((string) ($message['message'] ?? ''))) ?></div>
    </div>
</div>

<?php if ($customValues !== []): ?>
    <div class="card contact-detail-card">
        <div class="card-header">
            <h2 class="card-title"><?= __('contact_custom_values_title', 'Contact') ?></h2>
        </div>
        <div class="card-body">
            <dl class="contact-detail-list">
                <?php foreach ($customValues as $customValue): ?>
                    <?php
                    if (!is_array($customValue)) {
                        continue;
                    }
                    $label = trim((string) ($customValue['label'] ?? ($customValue['key'] ?? '')));
                    $value = trim((string) ($customValue['value'] ?? ''));
                    if ($label === '' || $value === '') {
                        continue;
                    }
                    ?>
                    <dt><?= e($label) ?></dt>
                    <dd><?= nl2br(e($value)) ?></dd>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
<?php endif; ?>

<?php if ($attachments !== []): ?>
    <div class="card contact-detail-card">
        <div class="card-header">
            <h2 class="card-title"><?= __('contact_attachments_title', 'Contact') ?></h2>
        </div>
        <div class="card-body">
            <ul class="contact-attachments-list">
                <?php foreach ($attachments as $attachmentIndex => $attachment): ?>
                    <?php
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
                        ? url('/admin/contact/' . $messageId . '/attachment/' . (int) $attachmentIndex . '/download')
                        : $legacyUrl;
                    ?>
                    <li>
                        <?php if ($downloadUrl !== ''): ?>
                            <a href="<?= e($downloadUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($name) ?></a>
                        <?php else: ?>
                            <?= e($name) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>
