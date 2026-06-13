<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>
<!-- Global Alert Modal -->
    <div class="modal-overlay is-initially-hidden" id="alertModal">
        <div class="modal-container modal-sm">
            <div class="modal-header">
                <h3 class="modal-title" id="alertModalTitle">
                    <i class="fas fa-info-circle modal-icon-info"></i>
                    <?= __('confirm', 'Core') ?>
                </h3>
                <button type="button" class="modal-close" data-modal-close="alertModal">&times;</button>
            </div>
            <div class="modal-body modal-body-centered">
                <p id="alertModalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="alertModalOk"><?= __('confirm', 'Core') ?></button>
            </div>
        </div>
    </div>

    <!-- Global Confirm Modal -->
    <div class="modal-overlay is-initially-hidden" id="confirmModal"
        data-default-message="<?= __('confirm_delete', 'Core') ?>"
        data-default-warning="<?= __('delete_warning', 'Core') ?>">
        <div class="modal-container modal-sm">
            <div class="modal-header">
                <h3 class="modal-title" id="confirmModalTitle">
                    <i class="fas fa-trash-alt modal-icon-danger"></i>
                    <?= __('confirm', 'Core') ?>
                </h3>
                <button type="button" class="modal-close" data-modal-close="confirmModal">&times;</button>
            </div>
            <div class="modal-body modal-body-centered">
                <p id="confirmModalMessage"><?= __('confirm_delete', 'Core') ?></p>
                <p id="confirmModalItemName" class="modal-item-name is-initially-hidden">
                    <strong id="confirmModalItemValue"></strong>
                </p>
                <p id="confirmModalWarning" class="modal-warning"><?= __('delete_warning', 'Core') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="confirmModal"><?= __('cancel', 'Core') ?></button>
                <button type="button" class="btn btn-danger" id="confirmModalConfirm"><?= __('delete', 'Core') ?></button>
            </div>
        </div>
    </div>

    <!-- Global Help Modal -->
    <div class="modal-overlay is-initially-hidden" id="helpModal"
        data-default-title="<?= e(__('tips', 'Core')) ?>"
        data-trigger-label="<?= e(__('tips', 'Core')) ?>">
        <div class="modal-container modal-lg">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-lightbulb modal-icon-info" aria-hidden="true"></i>
                    <span id="helpModalTitleText"><?= __('tips', 'Core') ?></span>
                </h3>
                <button type="button" class="modal-close" data-modal-close="helpModal">&times;</button>
            </div>
            <div class="modal-body admin-help-modal-body" id="helpModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="helpModal"><?= __('close', 'Core') ?></button>
            </div>
        </div>
    </div>

    <?php if (module_enabled('AiAgent')): ?>
        <?php include BASE_PATH . '/app/Modules/AiAgent/Views/admin/partials/drawer.php'; ?>
    <?php endif; ?>
