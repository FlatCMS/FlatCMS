<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

$documentation = is_array($documentation ?? null) ? $documentation : [];
$groups = is_array($documentation['groups'] ?? null) ? $documentation['groups'] : [];
?>

<link rel="stylesheet" href="<?= module_asset('Modules', 'css/modules.css') ?>">
<link rel="stylesheet" href="<?= module_asset('Settings', 'css/settings.css') ?>">

<div class="page-header">
    <div class="page-header-content">
        <h1 class="page-title"><?= e((string) ($pageTitle ?? __('integrations_docs_title', 'Settings'))) ?></h1>
        <p class="page-subtitle"><?= e((string) ($documentation['intro'] ?? '')) ?></p>
    </div>
    <div class="page-header-actions">
        <a href="<?= e((string) ($documentation['back_url'] ?? url('/admin/settings#settings-integrations'))) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <?= e((string) ($documentation['back_label'] ?? __('integrations_docs_back', 'Settings'))) ?>
        </a>
    </div>
</div>

<?php if (trim((string) ($documentation['fallback_notice'] ?? '')) !== ''): ?>
    <div class="card settings-help-notice-card">
        <div class="settings-help-notice">
            <i class="fas fa-language" aria-hidden="true"></i>
            <span><?= e((string) $documentation['fallback_notice']) ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="settings-help-groups module-card-list">
    <?php foreach ($groups as $group): ?>
        <?php
        $topics = is_array($group['topics'] ?? null) ? $group['topics'] : [];
        ?>
        <section class="settings-integrations-group module-card settings-help-group">
            <div class="module-card-header">
                <div class="module-card-info">
                    <div class="module-card-icon settings-integrations-group-icon">
                        <i class="<?= e((string) ($group['icon'] ?? 'fas fa-circle-info')) ?>" aria-hidden="true"></i>
                    </div>
                    <div class="module-card-text">
                        <h2 class="module-card-title settings-integrations-group-title"><?= e((string) ($group['title'] ?? '')) ?></h2>
                    </div>
                </div>
                <div class="module-card-summary">
                    <span class="settings-status-badge is-ok"><?= e((string) count($topics)) ?></span>
                </div>
            </div>
            <div class="module-card-body settings-help-group-body">
                <p class="form-hint settings-help-group-intro"><?= e((string) ($group['intro'] ?? '')) ?></p>

                <div class="settings-help-topic-grid">
                    <?php foreach ($topics as $topic): ?>
                        <article id="<?= e((string) ($topic['anchor'] ?? '')) ?>" class="settings-help-topic">
                            <div class="settings-help-topic-head">
                                <h3 class="settings-help-topic-title"><?= e((string) ($topic['title'] ?? '')) ?></h3>
                                <code class="settings-help-topic-env"><?= e((string) ($topic['env_key'] ?? '')) ?></code>
                            </div>

                            <p class="settings-help-topic-copy"><?= e((string) ($topic['summary'] ?? '')) ?></p>

                            <?php if (trim((string) ($topic['example'] ?? '')) !== ''): ?>
                                <div class="settings-help-topic-example">
                                    <span class="settings-system-stat-label"><?= __('integrations_docs_example_label', 'Settings') ?></span>
                                    <code class="settings-help-topic-code"><?= e((string) $topic['example']) ?></code>
                                </div>
                            <?php endif; ?>

                            <?php if (trim((string) ($topic['official_doc_url'] ?? '')) !== ''): ?>
                                <div class="settings-help-topic-actions">
                                    <a
                                        href="<?= e((string) $topic['official_doc_url']) ?>"
                                        class="btn btn-secondary btn-sm"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <i class="fas fa-up-right-from-square" aria-hidden="true"></i>
                                        <?= __('integrations_docs_official_link', 'Settings') ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>
