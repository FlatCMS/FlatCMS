<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

if (!empty($flash['success'])): ?>
    <div data-component="flash-toast" data-type="success" data-message="<?= e((string) $flash['success']) ?>"></div>
<?php endif; ?>

<?php if (!empty($flash['error'])): ?>
    <div data-component="flash-toast" data-type="error" data-message="<?= e((string) $flash['error']) ?>"></div>
<?php endif; ?>

<?php if (!empty($flash['warning'])): ?>
    <div data-component="flash-toast" data-type="warning" data-message="<?= e((string) $flash['warning']) ?>"></div>
<?php endif; ?>
