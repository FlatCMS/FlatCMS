<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

$settings = is_array($settings ?? null) ? $settings : [];
$connection = is_array($connection ?? null) ? $connection : null;
$forms = is_array($forms ?? null) ? $forms : [];
$responses = is_array($responses ?? null) ? array_values($responses) : [];
$dashboard = is_array($dashboard ?? null) ? $dashboard : [];
$selectedFormId = (string) ($settings['selected_form_id'] ?? '');
$selectedFormTitle = trim((string) ($settings['selected_form_title'] ?? ''));
$selectedFormUpdatedAt = trim((string) ($settings['selected_form_updated_at'] ?? ''));

if ($selectedFormTitle === '' && $selectedFormId !== '') {
    foreach ($forms as $form) {
        if (!is_array($form)) {
            continue;
        }

        if ((string) ($form['id'] ?? '') === $selectedFormId) {
            $selectedFormTitle = trim((string) ($form['name'] ?? $form['title'] ?? $selectedFormId));
            break;
        }
    }
}

$formsCount = count($forms);
$responsesCount = count($responses);
$oauthStatus = (string) ($oauthStatus ?? ($configured ? 'configured' : 'missing'));
$oauthStatus = in_array($oauthStatus, ['missing', 'partial', 'configured'], true) ? $oauthStatus : 'missing';
$stateClass = $oauthStatus !== 'configured' ? 'is-missing-config' : (!$connected ? 'is-disconnected' : 'is-connected');
$canConnectGoogle = $oauthStatus === 'configured' && !$connected;
$oauthBadgeClass = match ($oauthStatus) {
    'configured' => 'is-ok',
    'partial' => 'is-warning',
    default => 'is-danger',
};
$oauthBadgeIcon = match ($oauthStatus) {
    'configured' => 'fa-check-circle',
    'partial' => 'fa-triangle-exclamation',
    default => 'fa-circle-xmark',
};
$oauthBadgeLabel = match ($oauthStatus) {
    'configured' => __('google_forms_configured', 'GoogleForms'),
    'partial' => __('google_forms_config_incomplete', 'GoogleForms'),
    default => __('google_forms_not_configured', 'GoogleForms'),
};
$connectionBadgeClass = $connected ? 'is-ok' : 'is-danger';
$connectionBadgeIcon = $connected ? 'fa-check-circle' : 'fa-circle-xmark';
$connectedAccountName = trim((string) ($connection['google_account_name'] ?? ''));
$connectedAccountEmail = trim((string) ($connection['google_account_email'] ?? ''));
$connectedAccountDisplay = $connectedAccountName !== '' ? $connectedAccountName : ($connectedAccountEmail !== '' ? $connectedAccountEmail : 'Google');

$gfAdminPath = static function (string $path): string {
    $fragment = '';

    if (str_contains($path, '#')) {
        [$path, $fragment] = explode('#', $path, 2);
        $fragment = '#' . $fragment;
    }

    $path = '/' . ltrim($path, '/');

    if (function_exists('url')) {
        return url($path) . $fragment;
    }

    if (defined('BASE_URL') && BASE_URL !== '') {
        $base = rtrim((string) BASE_URL, '/');
        $parts = parse_url($base);
        $prefix = is_array($parts) && isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';

        return $prefix . $path . $fragment;
    }

    return $path . $fragment;
};

$gfValue = static function (?string $value): string {
    $value = trim((string) $value);

    return $value !== '' ? $value : '—';
};

$gfShort = static function (?string $value, int $max = 72): string {
    $value = trim((string) $value);

    if ($value === '') {
        return '—';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value, 'UTF-8') > $max ? mb_substr($value, 0, $max - 1, 'UTF-8') . '…' : $value;
    }

    return strlen($value) > $max ? substr($value, 0, $max - 1) . '…' : $value;
};

$gfSplitList = static function (?string $value): array {
    $value = trim((string) $value);

    if ($value === '') {
        return [];
    }

    if (str_contains($value, "\n")) {
        $parts = preg_split('/\R+/', $value) ?: [];
    } elseif (substr_count($value, ',') >= 2) {
        $parts = explode(',', $value);
    } else {
        return [$value];
    }

    $items = [];

    foreach ($parts as $part) {
        $part = trim((string) $part);

        if ($part !== '') {
            $items[] = $part;
        }
    }

    return $items !== [] ? $items : [$value];
};

$gfAnswerHtml = static function (?string $value) use ($gfValue, $gfSplitList): string {
    $value = trim((string) $value);

    if ($value === '') {
        return '<span class="text-muted">—</span>';
    }

    $items = $gfSplitList($value);

    if (count($items) <= 1) {
        return nl2br(e($gfValue($value)));
    }

    $html = '<ul class="google-forms-answer-bullets">';

    foreach ($items as $item) {
        $html .= '<li>' . e($item) . '</li>';
    }

    $html .= '</ul>';

    return $html;
};

$gfDate = static function (?string $value): string {
    $value = trim((string) $value);

    if ($value === '') {
        return '—';
    }

    try {
        return (new DateTimeImmutable($value))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $value;
    }
};

$gfSearchText = static function (array $response): string {
    $parts = [
        (string) ($response['lastSubmittedTime'] ?? ''),
        (string) ($response['respondentEmail'] ?? ''),
    ];

    $summary = is_array($response['summary'] ?? null) ? $response['summary'] : [];
    foreach ($summary as $value) {
        $parts[] = is_scalar($value) ? (string) $value : '';
    }

    $answers = is_array($response['answers_labeled'] ?? null) ? $response['answers_labeled'] : [];
    foreach ($answers as $answer) {
        if (is_array($answer)) {
            $parts[] = (string) ($answer['label'] ?? '');
            $parts[] = (string) ($answer['value'] ?? '');
        }
    }

    $text = implode(' ', $parts);

    return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
};
?>

<link rel="stylesheet" href="<?= module_asset('GoogleForms', 'css/google-forms.css') ?>">

<div class="google-forms-admin-page <?= e($stateClass) ?>">
    <div class="page-header google-forms-page-header">
        <div class="page-header-content">
            <h1 class="page-title"><?= e($pageTitle) ?></h1>
            <p class="page-subtitle"><?= __('google_forms_description', 'GoogleForms') ?></p>
        </div>
        <div class="page-header-actions">
            <a class="btn btn-secondary" href="<?= $gfAdminPath('/admin/google-forms/settings') ?>">
                <i class="fas fa-sliders" aria-hidden="true"></i>
                <?= __('google_forms_settings', 'GoogleForms') ?>
            </a>
        </div>
    </div>

    <section class="google-forms-command-center" aria-labelledby="googleFormsOverviewTitle">
        <div class="google-forms-command-main">
            <span class="google-forms-eyebrow"><?= __('google_forms_integration_eyebrow', 'GoogleForms') ?></span>
            <h2 id="googleFormsOverviewTitle"><?= __('google_forms_overview_title', 'GoogleForms') ?></h2>
            <p><?= __('google_forms_overview_help', 'GoogleForms') ?></p>
        </div>

        <div class="google-forms-status-badges" aria-label="<?= e(__('google_forms_status_summary_aria', 'GoogleForms')) ?>">
            <span class="google-forms-overview-badge <?= e($oauthBadgeClass) ?>">
                <i class="fas <?= e($oauthBadgeIcon) ?>" aria-hidden="true"></i>
                <span><?= __('google_forms_oauth_status', 'GoogleForms') ?></span>
                <strong><?= e($oauthBadgeLabel) ?></strong>
            </span>
            <span class="google-forms-overview-badge <?= e($connectionBadgeClass) ?>">
                <i class="fas <?= e($connectionBadgeIcon) ?>" aria-hidden="true"></i>
                <span><?= __('google_forms_connection_status', 'GoogleForms') ?></span>
                <strong><?= $connected ? __('google_forms_connection_ready', 'GoogleForms') : __('google_forms_connection_missing', 'GoogleForms') ?></strong>
            </span>
        </div>
    </section>

    <?php if ($oauthStatus !== 'configured'): ?>
        <div class="card google-forms-card google-forms-state-card">
            <div class="card-body google-forms-state-body">
                <div class="google-forms-state-icon" aria-hidden="true"><i class="fas fa-key"></i></div>
                <div>
                    <h2 class="card-title card-title-spaced"><?= __('google_forms_oauth_required_title', 'GoogleForms') ?></h2>
                    <p class="text-muted"><?= __('google_forms_oauth_required_help', 'GoogleForms') ?></p>
                    <div class="google-forms-actions-row is-compact">
                        <a class="btn btn-primary" href="<?= $gfAdminPath('/admin/settings#settings-integrations') ?>">
                            <i class="fas fa-sliders" aria-hidden="true"></i>
                            <?= __('google_forms_global_oauth_action', 'GoogleForms') ?>
                        </a>
                        <button class="btn btn-primary google-forms-disabled-action" type="button" disabled aria-disabled="true" title="<?= e(__('google_forms_connect_disabled_hint', 'GoogleForms')) ?>">
                            <i class="fa-brands fa-google" aria-hidden="true"></i>
                            <?= __('google_forms_connect_google', 'GoogleForms') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (!$connected): ?>
        <div class="card google-forms-card google-forms-state-card google-forms-connect-card">
            <div class="card-body google-forms-connect-body">
                <div class="google-forms-connect-row">
                    <div class="google-forms-connect-heading">
                        <div class="google-forms-state-icon is-google" aria-hidden="true"><i class="fa-brands fa-google"></i></div>
                        <h2 class="card-title card-title-spaced"><?= __('google_forms_connect_title', 'GoogleForms') ?></h2>
                    </div>
                    <?php if ($canConnectGoogle): ?>
                        <a class="btn btn-primary" href="<?= $gfAdminPath('/admin/google-forms/connect') ?>">
                            <i class="fa-brands fa-google" aria-hidden="true"></i>
                            <?= __('google_forms_connect_google', 'GoogleForms') ?>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-primary google-forms-disabled-action" type="button" disabled aria-disabled="true" title="<?= e(__('google_forms_connect_disabled_hint', 'GoogleForms')) ?>">
                            <i class="fa-brands fa-google" aria-hidden="true"></i>
                            <?= __('google_forms_connect_google', 'GoogleForms') ?>
                        </button>
                    <?php endif; ?>
                </div>
                <p class="text-muted google-forms-connect-help"><?= __('google_forms_connect_help', 'GoogleForms') ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="google-forms-kpis">
            <div class="card google-forms-kpi-card">
                <div class="card-body">
                    <span class="google-forms-kpi-label"><?= __('google_forms_connected_account', 'GoogleForms') ?></span>
                    <span class="google-forms-account-stack">
                        <strong class="google-forms-account-name"><?= e($connectedAccountDisplay) ?></strong>
                        <?php if ($connectedAccountName !== '' && $connectedAccountEmail !== ''): ?>
                            <small class="google-forms-account-email"><?= e($connectedAccountEmail) ?></small>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            <div class="card google-forms-kpi-card">
                <div class="card-body">
                    <span class="google-forms-kpi-label"><?= __('google_forms_forms_count', 'GoogleForms') ?></span>
                    <strong class="google-forms-kpi-value"><?= e((string) $formsCount) ?></strong>
                </div>
            </div>
            <div class="card google-forms-kpi-card">
                <div class="card-body">
                    <span class="google-forms-kpi-label"><?= __('google_forms_total_responses', 'GoogleForms') ?></span>
                    <strong class="google-forms-kpi-value"><?= e((string) ($dashboard['total'] ?? $responsesCount)) ?></strong>
                </div>
            </div>
            <div class="card google-forms-kpi-card">
                <div class="card-body">
                    <span class="google-forms-kpi-label"><?= __('google_forms_last_response', 'GoogleForms') ?></span>
                    <strong class="google-forms-date"><?= e($gfDate((string) ($dashboard['lastSubmittedTime'] ?? ''))) ?></strong>
                </div>
            </div>
        </div>

        <div class="card google-forms-card">
            <div class="card-body">
                <div class="google-forms-section-head">
                    <div>
                        <span class="google-forms-section-kicker"><?= __('google_forms_configuration', 'GoogleForms') ?></span>
                        <h2 class="card-title card-title-spaced"><?= __('google_forms_choose_form', 'GoogleForms') ?></h2>
                    </div>
                    <?php if ($selectedFormId !== ''): ?>
                        <div class="google-forms-selected-pill" title="<?= e($selectedFormTitle) ?>">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                            <span><?= e($selectedFormTitle !== '' ? $gfShort($selectedFormTitle, 54) : $selectedFormId) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <form action="<?= $gfAdminPath('/admin/google-forms/forms/select') ?>" method="POST" class="google-forms-inline-form">
                    <?= csrf_field() ?>
                    <div class="form-group google-forms-select-wrap">
                        <label class="form-label" for="gf_form_id"><?= __('google_forms_available_forms', 'GoogleForms') ?></label>
                        <select class="form-input" id="gf_form_id" name="form_id">
                            <option value=""><?= __('google_forms_choose_form_placeholder', 'GoogleForms') ?></option>
                            <?php foreach ($forms as $form): ?>
                                <?php if (!is_array($form)) continue; ?>
                                <?php $id = (string) ($form['id'] ?? ''); ?>
                                <option value="<?= e($id) ?>" <?= $selectedFormId === $id ? 'selected' : '' ?>>
                                    <?= e((string) ($form['name'] ?? $form['title'] ?? $id)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary google-forms-select-button" type="submit">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        <?= __('google_forms_select', 'GoogleForms') ?>
                    </button>
                    <?php if ($formsCount === 0): ?>
                        <div class="form-hint google-forms-select-hint"><?= __('google_forms_no_forms_hint', 'GoogleForms') ?></div>
                    <?php else: ?>
                        <div class="form-hint google-forms-select-hint"><?= sprintf(__('google_forms_forms_detected', 'GoogleForms'), $formsCount) ?></div>
                    <?php endif; ?>
                </form>

                <div class="google-forms-actions-row">
                    <form action="<?= $gfAdminPath('/admin/google-forms/forms/refresh') ?>" method="POST">
                        <?= csrf_field() ?>
                        <button class="btn btn-secondary" type="submit">
                            <i class="fas fa-arrows-rotate" aria-hidden="true"></i>
                            <?= __('google_forms_refresh_forms', 'GoogleForms') ?>
                        </button>
                    </form>

                    <?php if ($selectedFormId !== ''): ?>
                        <form action="<?= $gfAdminPath('/admin/google-forms/responses/sync') ?>" method="POST">
                            <?= csrf_field() ?>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-download" aria-hidden="true"></i>
                                <?= __('google_forms_sync_responses', 'GoogleForms') ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if (!empty($settings['selected_form_url'])): ?>
                        <a class="btn btn-secondary" target="_blank" rel="noopener" href="<?= e((string) $settings['selected_form_url']) ?>">
                            <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
                            <?= __('google_forms_open_google', 'GoogleForms') ?>
                        </a>
                    <?php endif; ?>

                    <form action="<?= $gfAdminPath('/admin/google-forms/disconnect') ?>" method="POST" data-google-forms-confirm="<?= e(__('google_forms_disconnect_confirm', 'GoogleForms')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline" type="submit">
                            <i class="fas fa-link-slash" aria-hidden="true"></i>
                            <?= __('google_forms_disconnect', 'GoogleForms') ?>
                        </button>
                    </form>
                </div>

                <?php if ($selectedFormUpdatedAt !== ''): ?>
                    <p class="text-muted google-forms-card-note">
                        <?= __('google_forms_selected_form_updated', 'GoogleForms') ?> : <?= e($gfDate($selectedFormUpdatedAt)) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card google-forms-card">
            <div class="card-body">
                <div class="google-forms-table-head">
                    <div>
                        <span class="google-forms-section-kicker google-forms-section-title"><?= __('google_forms_responses', 'GoogleForms') ?></span>
                        <p class="text-muted google-forms-table-subtitle"><?= __('google_forms_responses_table_help', 'GoogleForms') ?></p>
                    </div>

                    <?php if ($responses !== []): ?>
                        <div class="google-forms-search">
                            <label class="sr-only" for="gfResponseSearch"><?= __('google_forms_search_label', 'GoogleForms') ?></label>
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" id="gfResponseSearch" class="form-input" placeholder="<?= e(__('google_forms_search_placeholder', 'GoogleForms')) ?>">
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($responses === []): ?>
                    <div class="google-forms-empty-state">
                        <div class="google-forms-empty-icon" aria-hidden="true"><i class="fas fa-inbox"></i></div>
                        <h3><?= __('google_forms_responses_empty_title', 'GoogleForms') ?></h3>
                        <p><?= __('google_forms_responses_empty_help', 'GoogleForms') ?></p>
                        <?php if ($selectedFormId !== ''): ?>
                            <form action="<?= $gfAdminPath('/admin/google-forms/responses/sync') ?>" method="POST">
                                <?= csrf_field() ?>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-download" aria-hidden="true"></i>
                                    <?= __('google_forms_sync_responses', 'GoogleForms') ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper google-forms-table-wrapper" data-tour-target="google-forms-responses-table" data-tour-state="ready">
                        <table class="table google-forms-table">
                            <thead>
                                <tr>
                                    <th><?= __('google_forms_company', 'GoogleForms') ?></th>
                                    <th><?= __('google_forms_contact', 'GoogleForms') ?></th>
                                    <th><?= __('google_forms_email', 'GoogleForms') ?></th>
                                    <th><?= __('google_forms_activity', 'GoogleForms') ?></th>
                                    <th><?= __('google_forms_submitted_at', 'GoogleForms') ?></th>
                                    <th class="table-actions-header"><?= __('google_forms_actions', 'GoogleForms') ?></th>
                                </tr>
                            </thead>
                            <tbody id="gfResponsesTableBody">
                                <?php foreach ($responses as $index => $response): ?>
                                    <?php if (!is_array($response)) continue; ?>
                                    <?php
                                    $summary = is_array($response['summary'] ?? null) ? $response['summary'] : [];
                                    $detailId = 'gf-response-detail-' . (int) $index;
                                    $submittedAt = (string) ($response['lastSubmittedTime'] ?? $response['createTime'] ?? '');
                                    $email = $gfValue((string) ($summary['email'] ?? $response['respondentEmail'] ?? ''));
                                    ?>
                                    <tr data-gf-search="<?= e($gfSearchText($response)) ?>">
                                        <td data-label="<?= e(__('google_forms_company', 'GoogleForms')) ?>">
                                            <div class="google-forms-primary-cell">
                                                <strong><?= e($gfShort((string) ($summary['company'] ?? ''), 72)) ?></strong>
                                            </div>
                                        </td>
                                        <td data-label="<?= e(__('google_forms_contact', 'GoogleForms')) ?>">
                                            <strong><?= e($gfShort((string) ($summary['contact'] ?? ''), 72)) ?></strong>
                                        </td>
                                        <td data-label="<?= e(__('google_forms_email', 'GoogleForms')) ?>">
                                            <?php if ($email !== '—'): ?>
                                                <a href="mailto:<?= e($email) ?>"><?= e($gfShort($email, 72)) ?></a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="<?= e(__('google_forms_activity', 'GoogleForms')) ?>">
                                            <?= e($gfShort((string) ($summary['activity'] ?? ''), 96)) ?>
                                        </td>
                                        <td data-label="<?= e(__('google_forms_submitted_at', 'GoogleForms')) ?>">
                                            <span class="google-forms-date-compact"><?= e($gfDate($submittedAt)) ?></span>
                                        </td>
                                        <td data-label="<?= e(__('google_forms_actions', 'GoogleForms')) ?>">
                                            <div class="table-actions table-actions-compact google-forms-table-actions">
                                                <button type="button" class="btn btn-sm btn-secondary google-forms-detail-trigger" data-detail-target="<?= e($detailId) ?>">
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                                    <?= __('google_forms_view_details', 'GoogleForms') ?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="google-forms-response-templates" hidden>
                        <?php foreach ($responses as $index => $response): ?>
                            <?php if (!is_array($response)) continue; ?>
                            <?php
                            $summary = is_array($response['summary'] ?? null) ? $response['summary'] : [];
                            $detailId = 'gf-response-detail-' . (int) $index;
                            $submittedAt = (string) ($response['lastSubmittedTime'] ?? $response['createTime'] ?? '');
                            $answersLabeled = is_array($response['answers_labeled'] ?? null) ? $response['answers_labeled'] : [];
                            $responseId = (string) ($response['responseId'] ?? '');
                            ?>
                            <template id="<?= e($detailId) ?>">
                                <div class="google-forms-response-detail">
                                    <div class="google-forms-detail-grid">
                                        <div>
                                            <span><?= __('google_forms_company', 'GoogleForms') ?></span>
                                            <strong><?= e($gfValue((string) ($summary['company'] ?? ''))) ?></strong>
                                        </div>
                                        <div>
                                            <span><?= __('google_forms_contact', 'GoogleForms') ?></span>
                                            <strong><?= e($gfValue((string) ($summary['contact'] ?? ''))) ?></strong>
                                        </div>
                                        <div>
                                            <span><?= __('google_forms_email', 'GoogleForms') ?></span>
                                            <strong><?= e($gfValue((string) ($summary['email'] ?? $response['respondentEmail'] ?? ''))) ?></strong>
                                        </div>
                                        <div>
                                            <span><?= __('google_forms_submitted_at', 'GoogleForms') ?></span>
                                            <strong><?= e($gfDate($submittedAt)) ?></strong>
                                        </div>
                                        <div>
                                            <span><?= __('google_forms_activity', 'GoogleForms') ?></span>
                                            <strong><?= e($gfValue((string) ($summary['activity'] ?? ''))) ?></strong>
                                        </div>
                                        <div>
                                            <span><?= __('google_forms_budget', 'GoogleForms') ?></span>
                                            <strong><?= e($gfValue((string) ($summary['budget'] ?? ''))) ?></strong>
                                        </div>
                                    </div>

                                    <?php if (trim((string) ($summary['need'] ?? '')) !== ''): ?>
                                        <div class="google-forms-need-panel">
                                            <span><?= __('google_forms_need', 'GoogleForms') ?></span>
                                            <strong><?= $gfAnswerHtml((string) ($summary['need'] ?? '')) ?></strong>
                                        </div>
                                    <?php endif; ?>

                                    <div class="google-forms-detail-section">
                                        <h3><?= __('google_forms_all_answers', 'GoogleForms') ?></h3>

                                        <?php if ($answersLabeled === []): ?>
                                            <p class="text-muted"><?= __('google_forms_no_answers', 'GoogleForms') ?></p>
                                        <?php else: ?>
                                            <dl class="google-forms-answer-detail-list">
                                                <?php foreach ($answersLabeled as $answer): ?>
                                                    <?php if (!is_array($answer)) continue; ?>
                                                    <div class="google-forms-answer-detail-item">
                                                        <dt>
                                                            <?= e($gfValue((string) ($answer['label'] ?? $answer['question_id'] ?? ''))) ?>
                                                            <?php if (!empty($answer['question_id'])): ?>
                                                                <small><?= e((string) $answer['question_id']) ?></small>
                                                            <?php endif; ?>
                                                        </dt>
                                                        <dd><?= $gfAnswerHtml((string) ($answer['value'] ?? '')) ?></dd>
                                                    </div>
                                                <?php endforeach; ?>
                                            </dl>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($responseId !== ''): ?>
                                        <p class="google-forms-response-id">
                                            <?= __('google_forms_response_id', 'GoogleForms') ?> :
                                            <code><?= e($responseId) ?></code>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </template>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </div>
        </div>

        <div class="google-forms-modal" id="gfResponseModal" aria-hidden="true">
            <div class="google-forms-modal-backdrop" data-modal-close></div>
            <div class="google-forms-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="gfResponseModalTitle">
                <div class="google-forms-modal-header">
                    <div>
                        <span class="google-forms-modal-eyebrow"><?= __('google_forms_responses', 'GoogleForms') ?></span>
                        <h2 id="gfResponseModalTitle"><?= __('google_forms_response_detail', 'GoogleForms') ?></h2>
                    </div>
                    <button type="button" class="google-forms-modal-close" data-modal-close aria-label="<?= e(__('google_forms_close', 'GoogleForms')) ?>">
                        <i class="fas fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="google-forms-modal-body" id="gfResponseModalBody"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="<?= module_asset('GoogleForms', 'js/google-forms.js') ?>"></script>
