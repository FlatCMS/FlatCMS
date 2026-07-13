<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

?>

<?php
$authDisplayName = \App\Modules\Users\Support\UserName::display(is_array($auth_user ?? null) ? $auth_user : []);
$authInitial = \App\Modules\Users\Support\UserName::initial(is_array($auth_user ?? null) ? $auth_user : []);
if ($authDisplayName === '') {
    $authDisplayName = __('admin_user_fallback', 'Core');
}
?>
<header class="top-header sticky-top">
                <button
                    type="button"
                    class="sidebar-toggle"
                    id="sidebarToggle"
                    data-action="toggle-sidebar"
                    data-label-open="<?= e(__('admin_sidebar_open', 'Core')) ?>"
                    data-label-close="<?= e(__('admin_sidebar_close', 'Core')) ?>"
                    data-label-collapse="<?= e(__('admin_sidebar_collapse', 'Core')) ?>"
                    data-label-expand="<?= e(__('admin_sidebar_expand', 'Core')) ?>"
                    aria-controls="sidebar"
                    aria-label="<?= e(__('admin_sidebar_open', 'Core')) ?>"
                    title="<?= e(__('admin_sidebar_open', 'Core')) ?>"
                >
                    <i class="fas fa-bars"></i>
                </button>

                <div class="header-spacer"></div>

                <div class="header-actions" data-tour="topbar-actions">
                    <div class="theme-toggle" id="themeToggle">
                        <div class="theme-toggle-slider"></div>
                        <span class="theme-toggle-option active" id="themeDark" data-theme="dark">
                            <i class="fas fa-moon"></i>
                        </span>
                        <span class="theme-toggle-option" id="themeLight" data-theme="light">
                            <i class="fas fa-sun"></i>
                        </span>
                    </div>

                    <a href="<?= url('/') ?>" target="_blank" class="header-btn" title="<?= __('view_site', 'Dashboard') ?>">
                        <i class="fas fa-external-link-alt"></i>
                    </a>

                    <div class="user-dropdown" data-component="dropdown" data-tour-target="topbar-user-menu">
                        <button class="user-btn">
                            <?php $avatarUrl = avatar_url($auth_user); ?>
                            <?php if (!empty($avatarUrl)): ?>
                                <img src="<?= $avatarUrl ?>" alt="" class="user-avatar-img">
                            <?php else: ?>
                                <span class="user-avatar"><?= e($authInitial) ?></span>
                            <?php endif; ?>
                            <span class="user-name"><?= e($authDisplayName) ?></span>
                        </button>
                        <div class="dropdown-menu">
                            <?php if (can('dashboard.view')): ?>
                                <a href="<?= url('/admin/dashboard') ?>" class="dropdown-item"><?= __('dashboard', 'Core') ?></a>
                            <?php endif; ?>
                            <a href="<?= url('/admin/profile') ?>" class="dropdown-item"><?= __('profile', 'Core') ?></a>
                            <a href="<?= url('/admin/change-password') ?>" class="dropdown-item"><?= __('change_password', 'Auth') ?></a>
                            <hr class="dropdown-divider">
                            <form action="<?= url('/logout') ?>" method="POST" class="dropdown-form">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item dropdown-item-danger"><?= __('logout', 'Auth') ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>
