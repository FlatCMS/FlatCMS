<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>
<script src="<?= theme_asset('js/main.js', 'frontend') ?>"></script>
<?php if (module_enabled('Contact')): ?>
    <script src="<?= module_asset('Contact', 'js/contact-front.js') ?>"></script>
<?php endif; ?>
