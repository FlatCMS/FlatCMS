<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

$settings = is_array($settings ?? null) ? $settings : [];
?>

<section class="google-forms-front">
    <div class="google-forms-front-card">
        <p class="google-forms-eyebrow">Google Forms</p>
        <h1><?= e((string) ($settings['selected_form_title'] ?? __('google_forms_title', 'GoogleForms'))) ?></h1>

        <?php if (!empty($settings['selected_form_url'])): ?>
            <p><?= __('google_forms_front_help', 'GoogleForms') ?></p>
            <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e((string) $settings['selected_form_url']) ?>">
                <?= __('google_forms_open_google', 'GoogleForms') ?>
            </a>
        <?php else: ?>
            <p><?= __('google_forms_no_public_form', 'GoogleForms') ?></p>
        <?php endif; ?>
    </div>
</section>
