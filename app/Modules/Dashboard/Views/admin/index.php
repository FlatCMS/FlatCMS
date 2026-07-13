<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

// Helper function for formatting bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
}

// Helper for relative time
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return __('just_now', 'Dashboard');
    if ($diff < 3600) return floor($diff / 60) . ' ' . __('minutes_ago', 'Dashboard');
    if ($diff < 86400) return floor($diff / 3600) . ' ' . __('hours_ago', 'Dashboard');
    if ($diff < 2592000) return floor($diff / 86400) . ' ' . __('days_ago', 'Dashboard');
    return date('d/m/Y', $time);
}

$dashboardBanners = array_values(array_filter(
    hook_run('dashboard.admin.banners'),
    static fn ($markup): bool => is_string($markup) && trim($markup) !== ''
));
$onboardingChecklist = is_array($onboardingChecklist ?? null) ? $onboardingChecklist : [];
$onboardingCompletedCount = max(0, (int) ($onboardingCompletedCount ?? 0));
$onboardingTotalCount = max(0, (int) ($onboardingTotalCount ?? count($onboardingChecklist)));
$onboardingNextItem = is_array($onboardingNextItem ?? null) ? $onboardingNextItem : null;
?>

<?php
$authGreetingName = \App\Modules\Users\Support\UserName::greeting(is_array($auth_user ?? null) ? $auth_user : []);
if ($authGreetingName === '') {
    $authGreetingName = __('admin_user_fallback', 'Core');
}
?>

<link rel="stylesheet" href="<?= module_asset('Dashboard', 'css/dashboard.css') ?>">

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="welcome-content">
        <h1 class="welcome-title"><?= __('welcome_back', 'Dashboard') ?>, <?= e($authGreetingName) ?> ! 👋</h1>
        <p class="welcome-text"><?= __('dashboard_subtitle', 'Dashboard') ?></p>
    </div>
    <div class="welcome-meta">
        <span class="version-badge"><?= __('version_prefix', 'Core') ?><?= e($systemStats['flatcms_version']) ?></span>
    </div>
</div>

<!-- Maintenance Mode Banner -->
<div id="maintenance-banner"
     class="maintenance-banner <?= $maintenanceMode ? 'is-on' : 'is-off' ?>"
     data-toggle-url="<?= e(url('/admin/maintenance/toggle')) ?>"
     data-label-on="<?= e(__('site_offline', 'Dashboard')) ?>"
     data-label-off="<?= e(__('site_online', 'Dashboard')) ?>">
    <div class="maintenance-info">
        <div id="maintenance-icon-box" class="maintenance-icon-box">
            <i class="fas fa-tools"></i>
        </div>
        <div class="maintenance-text">
            <strong class="maintenance-title"><?= __('maintenance_mode', 'Dashboard') ?></strong>
            <div class="maintenance-status">
                <span id="maintenance-badge" class="maintenance-badge">
                    <?= $maintenanceMode ? __('site_offline', 'Dashboard') : __('site_online', 'Dashboard') ?>
                </span>
            </div>
        </div>
    </div>
    <label class="maintenance-toggle">
        <input type="checkbox" id="maintenance-toggle" <?= $maintenanceMode ? 'checked' : '' ?>>
        <span class="maintenance-toggle-track"></span>
        <span class="maintenance-toggle-thumb"></span>
    </label>
</div>

<?php foreach ($dashboardBanners as $dashboardBanner): ?>
    <?= $dashboardBanner ?>
<?php endforeach; ?>

<?php if ($onboardingTotalCount > 0): ?>
    <div class="card admin-onboarding-card" data-tour-target="dashboard-onboarding">
        <div class="card-header">
            <div>
                <div class="admin-guidance-card__eyebrow-row">
                    <span class="admin-guidance-card__icon" aria-hidden="true">
                        <i class="fas fa-list-check"></i>
                    </span>
                    <span class="admin-guidance-card__eyebrow"><?= __('dashboard_onboarding_badge', 'Dashboard') ?></span>
                    <span class="badge <?= $onboardingCompletedCount >= $onboardingTotalCount ? 'badge-success' : 'badge-info' ?>">
                        <?= __('dashboard_onboarding_progress', 'Dashboard', ['done' => (string) $onboardingCompletedCount, 'total' => (string) $onboardingTotalCount]) ?>
                    </span>
                </div>
                <h2 class="card-title"><?= __('dashboard_onboarding_title', 'Dashboard') ?></h2>
                <p class="admin-onboarding-card__copy"><?= __('dashboard_onboarding_content', 'Dashboard') ?></p>
            </div>
        </div>
        <div class="card-body admin-onboarding-card__body">
            <?php if ($onboardingNextItem !== null): ?>
                <div class="admin-onboarding-card__summary">
                    <h3 class="admin-onboarding-card__summary-title"><?= __('dashboard_onboarding_next_title', 'Dashboard') ?></h3>
                    <p class="admin-onboarding-card__summary-copy">
                        <strong><?= __((string) ($onboardingNextItem['title_key'] ?? ''), 'Dashboard') ?></strong>
                        <?= ' · ' ?>
                        <?= __((string) ($onboardingNextItem['content_key'] ?? ''), 'Dashboard') ?>
                    </p>
                    <div class="admin-onboarding-card__summary-actions">
                        <a href="<?= e((string) ($onboardingNextItem['url'] ?? '#')) ?>" class="btn btn-primary">
                            <?= __((string) ($onboardingNextItem['action_label_key'] ?? ''), 'Dashboard') ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="admin-onboarding-card__summary">
                    <h3 class="admin-onboarding-card__summary-title"><?= __('dashboard_onboarding_complete_title', 'Dashboard') ?></h3>
                    <p class="admin-onboarding-card__summary-copy"><?= __('dashboard_onboarding_complete_content', 'Dashboard') ?></p>
                </div>
            <?php endif; ?>

            <?php if ($onboardingChecklist !== []): ?>
                <div class="admin-checklist">
                    <?php foreach ($onboardingChecklist as $item): ?>
                        <article class="admin-checklist__item is-pending">
                            <div class="admin-checklist__status">
                                <span class="badge badge-warning">
                                    <?= __('dashboard_onboarding_pending', 'Dashboard') ?>
                                </span>
                            </div>
                            <div class="admin-checklist__body">
                                <h3 class="admin-checklist__title"><?= __((string) ($item['title_key'] ?? ''), 'Dashboard') ?></h3>
                                <p class="admin-checklist__copy"><?= __((string) ($item['content_key'] ?? ''), 'Dashboard') ?></p>
                            </div>
                            <a href="<?= e((string) ($item['url'] ?? '#')) ?>" class="btn btn-sm btn-secondary admin-checklist__action">
                                <?= __((string) ($item['action_label_key'] ?? ''), 'Dashboard') ?>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="stats-grid">
    <?php if (!empty($modulesEnabled['Pages'])): ?>
    <div class="stat-card">
        <div class="stat-icon stat-icon-indigo">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?= $stats['pages'] ?></span>
            <span class="stat-label"><?= __('pages', 'Core') ?></span>
        </div>
        <a href="<?= url('/admin/pages') ?>" class="stat-link"><?= __('view_all', 'Dashboard') ?></a>
    </div>
    <?php endif; ?>

    <?php if (!empty($modulesEnabled['Posts'])): ?>
    <div class="stat-card">
        <div class="stat-icon stat-icon-purple">
            <i class="fas fa-newspaper"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?= $stats['posts'] ?></span>
            <span class="stat-label"><?= __('posts', 'Core') ?></span>
        </div>
        <a href="<?= url('/admin/posts') ?>" class="stat-link"><?= __('view_all', 'Dashboard') ?></a>
    </div>
    <?php endif; ?>

    <?php if (!empty($modulesEnabled['Media'])): ?>
    <div class="stat-card">
        <div class="stat-icon stat-icon-cyan">
            <i class="fas fa-image"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?= $stats['media'] ?></span>
            <span class="stat-label"><?= __('media', 'Core') ?></span>
        </div>
        <a href="<?= url('/admin/media') ?>" class="stat-link"><?= __('view_all', 'Dashboard') ?></a>
    </div>
    <?php endif; ?>

    <?php if (!empty($modulesEnabled['Users'])): ?>
    <div class="stat-card">
        <div class="stat-icon stat-icon-emerald">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?= $stats['users'] ?></span>
            <span class="stat-label"><?= __('users', 'Core') ?></span>
        </div>
        <a href="<?= url('/admin/users') ?>" class="stat-link"><?= __('view_all', 'Dashboard') ?></a>
    </div>
    <?php endif; ?>

    <?php if (!empty($modulesEnabled['Comments'])): ?>
    <div class="stat-card">
        <div class="stat-icon stat-icon-orange">
            <i class="fas fa-comment"></i>
        </div>
        <div class="stat-content">
            <span class="stat-value"><?= $stats['comments'] ?></span>
            <span class="stat-label"><?= __('comments', 'Core') ?></span>
        </div>
        <a href="<?= url('/admin/comments') ?>" class="stat-link"><?= __('view_all', 'Dashboard') ?></a>
    </div>
    <?php endif; ?>
</div>

<!-- Main Dashboard Grid -->
<div class="dashboard-grid">
    <!-- Left Column -->
    <div class="dashboard-main">
        <!-- Content Overview Chart -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= __('content_overview', 'Dashboard') ?></h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <div class="chart-donut">
                        <svg viewBox="0 0 100 100" class="donut-chart">
                            <?php 
                            $offset = 0;
                            $circumference = 2 * M_PI * 35; // radius = 35
                            foreach ($chartData['breakdown'] as $item):
                                $dashLength = ($item['percent'] / 100) * $circumference;
                                $dashOffset = -$offset;
                            ?>
                            <circle 
                                cx="50" cy="50" r="35" 
                                fill="transparent"
                                stroke="<?= $item['color'] ?>"
                                stroke-width="12"
                                stroke-dasharray="<?= $dashLength ?> <?= $circumference ?>"
                                stroke-dashoffset="<?= $dashOffset ?>"
                                transform="rotate(-90 50 50)"
                            />
                            <?php 
                                $offset += $dashLength;
                            endforeach; 
                            ?>
                            <text x="50" y="46" text-anchor="middle" class="chart-total-value"><?= $chartData['total'] ?></text>
                            <text x="50" y="58" text-anchor="middle" class="chart-total-label"><?= __('total_items', 'Dashboard') ?></text>
                        </svg>
                    </div>
                    <div class="chart-legend">
                        <?php foreach ($chartData['breakdown'] as $item): ?>
                        <div class="legend-item">
                            <span class="legend-dot" data-color="<?= e($item['color']) ?>"></span>
                            <span class="legend-name"><?= __($item['name'], 'Dashboard') ?: $item['name'] ?></span>
                            <span class="legend-value"><?= $item['value'] ?></span>
                            <span class="legend-percent">(<?= $item['percent'] ?>%)</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Posts -->
        <?php if (!empty($modulesEnabled['Posts'])): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= __('recent_posts', 'Dashboard') ?></h2>
                <a href="<?= url('/admin/posts/create') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus dashboard-icon-sm"></i>
                    <?= __('new_post', 'Dashboard') ?>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentPosts)): ?>
                    <div class="empty-state">
                        <p><?= __('no_posts_yet', 'Dashboard') ?></p>
                    </div>
                <?php else: ?>
                    <div class="recent-list">
                        <?php foreach ($recentPosts as $post): ?>
                        <div class="recent-item">
                            <div class="recent-icon">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <div class="recent-content">
                                <a href="<?= url('/admin/posts/' . ($post['id'] ?? $post['slug'] ?? '') . '/edit') ?>" class="recent-title">
                                    <?= e($post['title'] ?? __('untitled', 'Dashboard')) ?>
                                </a>
                                <span class="recent-meta"><?= timeAgo($post['created_at'] ?? date('Y-m-d H:i:s')) ?></span>
                            </div>
                            <span class="badge <?= ($post['status'] ?? 'draft') === 'published' ? 'badge-success' : 'badge-warning' ?>">
                                <?= __($post['status'] ?? 'draft', 'Dashboard') ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right Column (Sidebar) -->
    <div class="dashboard-sidebar">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= __('quick_actions', 'Dashboard') ?></h2>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <?php if (!empty($modulesEnabled['Posts'])): ?>
                    <a href="<?= url('/admin/posts/create') ?>" class="quick-action">
                        <span class="quick-action-icon quick-action-purple">
                            <i class="fas fa-newspaper"></i>
                        </span>
                        <span class="quick-action-label"><?= __('new_post', 'Dashboard') ?></span>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($modulesEnabled['Pages'])): ?>
                    <a href="<?= url('/admin/pages/create') ?>" class="quick-action">
                        <span class="quick-action-icon quick-action-indigo">
                            <i class="fas fa-file-alt"></i>
                        </span>
                        <span class="quick-action-label"><?= __('new_page', 'Dashboard') ?></span>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($modulesEnabled['Media'])): ?>
                    <a href="<?= url('/admin/media') ?>" class="quick-action">
                        <span class="quick-action-icon quick-action-cyan">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </span>
                        <span class="quick-action-label"><?= __('upload_media', 'Dashboard') ?></span>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($modulesEnabled['Settings'])): ?>
                    <a href="<?= url('/admin/settings') ?>" class="quick-action">
                        <span class="quick-action-icon quick-action-gray">
                            <i class="fas fa-cog"></i>
                        </span>
                        <span class="quick-action-label"><?= __('settings', 'Core') ?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= __('system_health', 'Dashboard') ?></h2>
                <span class="health-badge health-<?= $systemStats['health_status'] ?>">
                    <?= __($systemStats['health_status'], 'Dashboard') ?>
                </span>
            </div>
            <div class="card-body">
                <div class="system-info">
                    <div class="info-row">
                        <span class="info-label"><?= __('php_label', 'Dashboard') ?></span>
                        <span class="info-value"><?= $systemStats['php_version'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('memory_limit', 'Dashboard') ?></span>
                        <span class="info-value"><?= $systemStats['memory_limit'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('max_upload', 'Dashboard') ?></span>
                        <span class="info-value"><?= $systemStats['max_upload'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('cache_files', 'Dashboard') ?></span>
                        <span class="info-value"><?= $systemStats['cache_files'] ?> (<?= formatBytes($systemStats['cache_size']) ?>)</span>
                    </div>
                </div>

                <!-- Disk Usage -->
                <div class="disk-usage">
                    <div class="disk-header">
                        <span class="disk-label"><?= __('disk_usage', 'Dashboard') ?></span>
                        <span class="disk-value"><?= $systemStats['disk_percent'] ?>%</span>
                    </div>
                    <div class="disk-bar">
                        <div class="disk-fill <?= $systemStats['disk_percent'] > 75 ? 'disk-fill-warning' : '' ?> <?= $systemStats['disk_percent'] > 90 ? 'disk-fill-danger' : '' ?>" data-progress="<?= min($systemStats['disk_percent'], 100) ?>"></div>
                    </div>
                    <div class="disk-meta">
                        <span><?= formatBytes($systemStats['disk_total'] - $systemStats['disk_free']) ?> <?= __('used', 'Dashboard') ?></span>
                        <span><?= formatBytes($systemStats['disk_free']) ?> <?= __('free', 'Dashboard') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Pages -->
        <?php if (!empty($modulesEnabled['Pages'])): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?= __('recent_pages', 'Dashboard') ?></h2>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentPages)): ?>
                    <div class="empty-state">
                        <p><?= __('no_pages_yet', 'Dashboard') ?></p>
                    </div>
                <?php else: ?>
                    <div class="recent-list compact">
                        <?php foreach ($recentPages as $page): ?>
                        <a href="<?= url('/admin/pages/' . ($page['id'] ?? $page['slug'] ?? '') . '/edit') ?>" class="recent-item-compact">
                            <span class="recent-title-compact"><?= e($page['title'] ?? __('untitled', 'Dashboard')) ?></span>
                            <span class="recent-meta-compact"><?= timeAgo($page['updated_at'] ?? $page['created_at'] ?? date('Y-m-d H:i:s')) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= module_asset('Dashboard', 'js/dashboard.js') ?>"></script>
