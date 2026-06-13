<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Dashboard\Controllers;

use App\Core\BaseController;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Core\ModuleManager;
use App\Modules\Backups\Services\SiteBackupService;
use App\Modules\Media\Models\MediaModel;
use App\Modules\Pages\Services\PageTranslationService;
use App\Modules\Posts\Services\PostTranslationService;
use App\Modules\Settings\Services\SiteRoutingService;

class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        I18n::load('Dashboard');
    }

    public function index(): void
    {
        if (!$this->authorize('dashboard.view')) {
            return;
        }

        $manager = new ModuleManager();
        $enabled = array_flip($manager->enabledNames());

        // Gather all dashboard data
        $stats = $this->getContentStats($enabled);
        $systemStats = $this->getSystemStats();
        $recentPosts = isset($enabled['Posts']) ? $this->getRecentPosts(5) : [];
        $recentPages = isset($enabled['Pages']) ? $this->getRecentPages(5) : [];
        $chartData = $this->getChartData($stats);
        // Read maintenance mode status
        $settings = FlatFile::settings();
        $maintenanceMode = $settings['maintenance_mode'] ?? false;
        $onboarding = $this->buildOnboardingChecklist($enabled, $settings);

        $this->render('Dashboard/Views/admin/index', [
            'pageTitle' => __('dashboard', 'Dashboard'),
            'stats' => $stats,
            'systemStats' => $systemStats,
            'recentPosts' => $recentPosts,
            'recentPages' => $recentPages,
            'chartData' => $chartData,
            'maintenanceMode' => $maintenanceMode,
            'modulesEnabled' => $enabled,
            'onboardingChecklist' => $onboarding['items'],
            'onboardingCompletedCount' => $onboarding['completed_count'],
            'onboardingTotalCount' => $onboarding['total_count'],
            'onboardingNextItem' => $onboarding['next_item'],
        ], 'admin.main');
    }

    public function toggleMaintenance(): void
    {
        if (!$this->authorize('settings.edit')) {
            return;
        }

        if (!$this->verifyCsrf()) {
            return;
        }

        $settings = FlatFile::settings();
        $settings['maintenance_mode'] = !($settings['maintenance_mode'] ?? false);
        FlatFile::saveSettings($settings);

        $this->json([
            'success' => true,
            'maintenance_mode' => $settings['maintenance_mode'],
        ]);
    }

    private function getContentStats(array $enabled): array
    {
        return [
            'pages' => isset($enabled['Pages']) ? $this->getGroupedPageCount() : 0,
            'posts' => isset($enabled['Posts']) ? $this->getGroupedPostCount() : 0,
            'media' => $this->getMediaCount($enabled),
            'users' => isset($enabled['Users']) ? FlatFile::for('users')->count() : 0,
            'comments' => isset($enabled['Comments']) ? FlatFile::for('comments')->count() : 0,
        ];
    }

    private function getGroupedPageCount(): int
    {
        $translations = new PageTranslationService(FlatFile::for('core/pages'));
        $groups = [];

        foreach ($translations->all() as $page) {
            $groupId = (string) ($page['translation_group'] ?? $page['id'] ?? '');
            if ($groupId === '') {
                continue;
            }

            $groups[$groupId] = true;
        }

        return count($groups);
    }

    private function getGroupedPublishedPageCount(): int
    {
        $translations = new PageTranslationService(FlatFile::for('core/pages'));
        $groups = [];

        foreach ($translations->all() as $page) {
            $normalized = $translations->normalizePage($page);

            $groupId = trim((string) ($normalized['translation_group'] ?? $normalized['id'] ?? ''));
            if ($groupId === '' || isset($groups[$groupId])) {
                continue;
            }

            $sourcePage = $translations->resolveSourcePage($groupId);
            if (!is_array($sourcePage)) {
                continue;
            }

            $sourcePage = $translations->normalizePage($sourcePage);
            if ($translations->resolveEffectiveStatus($sourcePage) === 'published') {
                $groups[$groupId] = true;
            }
        }

        return count($groups);
    }

    private function getGroupedPostCount(): int
    {
        $translations = new PostTranslationService(FlatFile::for('core/posts'));
        $groups = [];

        foreach ($translations->all() as $post) {
            $groupId = (string) ($post['translation_group'] ?? $post['id'] ?? '');
            if ($groupId === '') {
                continue;
            }

            $groups[$groupId] = true;
        }

        return count($groups);
    }

    private function getGroupedPublishedPostCount(): int
    {
        $translations = new PostTranslationService(FlatFile::for('core/posts'));
        $groups = [];

        foreach ($translations->all() as $post) {
            $normalized = $translations->normalizePost($post);
            $groupId = trim((string) ($normalized['translation_group'] ?? $normalized['id'] ?? ''));
            if ($groupId === '' || isset($groups[$groupId])) {
                continue;
            }

            $sourcePost = $translations->resolveSourcePost($groupId);
            if (!is_array($sourcePost)) {
                continue;
            }

            $sourcePost = $translations->normalizePost($sourcePost);
            if ($translations->resolveEffectiveStatus($sourcePost) === 'published') {
                $groups[$groupId] = true;
            }
        }

        return count($groups);
    }

    private function getMediaCount(array $enabled): int
    {
        if (!isset($enabled['Media'])) {
            return 0;
        }

        $model = new MediaModel();
        $stats = $model->getStats();

        return (int) ($stats['total'] ?? 0);
    }

    private function getSystemStats(): array
    {
        // Disk usage
        $uploadPath = BASE_PATH . '/storage/uploads';
        $diskUsed = $this->getDirectorySize($uploadPath);
        $diskTotal = disk_total_space(BASE_PATH) ?: 1;
        $diskFree = disk_free_space(BASE_PATH) ?: 0;
        
        // PHP info
        $phpVersion = PHP_VERSION;
        $memoryLimit = ini_get('memory_limit');
        $maxUpload = ini_get('upload_max_filesize');
        
        // Cache status
        $cachePath = BASE_PATH . '/storage/cache';
        $cacheSize = $this->getDirectorySize($cachePath);
        $cacheFiles = is_dir($cachePath) ? count(glob($cachePath . '/*')) : 0;

        return [
            'php_version' => $phpVersion,
            'memory_limit' => $memoryLimit,
            'max_upload' => $maxUpload,
            'disk_used' => $diskUsed,
            'disk_total' => $diskTotal,
            'disk_free' => $diskFree,
            'disk_percent' => round((($diskTotal - $diskFree) / $diskTotal) * 100, 1),
            'cache_size' => $cacheSize,
            'cache_files' => $cacheFiles,
            'flatcms_version' => flatcms_version(),
            'health_status' => $this->getHealthStatus($diskTotal, $diskFree),
        ];
    }

    private function getHealthStatus(float $total, float $free): string
    {
        $usedPercent = (($total - $free) / $total) * 100;
        if ($usedPercent > 90) return 'critical';
        if ($usedPercent > 75) return 'warning';
        return 'good';
    }

    /**
     * @param array<string, int> $enabled
     * @param array<string, mixed> $settings
     * @return array{items: array<int, array<string, mixed>>, completed_count: int, total_count: int, next_item: array<string, mixed>|null}
     */
    private function buildOnboardingChecklist(array $enabled, array $settings): array
    {
        $items = [];

        if (isset($enabled['Settings'])) {
            $items[] = [
                'key' => 'branding',
                'title_key' => 'dashboard_onboarding_branding_title',
                'content_key' => 'dashboard_onboarding_branding_content',
                'action_label_key' => 'dashboard_onboarding_branding_action',
                'url' => url('/admin/settings') . '#settings-general',
                'is_complete' => $this->hasConfiguredBranding($settings),
            ];
        }

        if (isset($enabled['Settings']) && isset($enabled['Pages'])) {
            $items[] = [
                'key' => 'homepage',
                'title_key' => 'dashboard_onboarding_homepage_title',
                'content_key' => 'dashboard_onboarding_homepage_content',
                'action_label_key' => 'dashboard_onboarding_homepage_action',
                'url' => url('/admin/settings') . '#settings-content',
                'is_complete' => $this->hasConfiguredHomepage(),
            ];
        }

        if (isset($enabled['Menu'])) {
            $items[] = [
                'key' => 'menu',
                'title_key' => 'dashboard_onboarding_menu_title',
                'content_key' => 'dashboard_onboarding_menu_content',
                'action_label_key' => 'dashboard_onboarding_menu_action',
                'url' => url('/admin/menus'),
                'is_complete' => $this->hasConfiguredMainMenu(),
            ];
        }

        if (isset($enabled['Settings'])) {
            $items[] = [
                'key' => 'email',
                'title_key' => 'dashboard_onboarding_email_title',
                'content_key' => 'dashboard_onboarding_email_content',
                'action_label_key' => 'dashboard_onboarding_email_action',
                'url' => url('/admin/settings') . '#settings-mail',
                'is_complete' => $this->hasConfiguredEmail($settings),
            ];
        }

        if (isset($enabled['Pages']) || isset($enabled['Posts'])) {
            $contentUrl = isset($enabled['Pages'])
                ? url('/admin/pages/create')
                : url('/admin/posts/create');

            $items[] = [
                'key' => 'content',
                'title_key' => 'dashboard_onboarding_content_title',
                'content_key' => 'dashboard_onboarding_content_content',
                'action_label_key' => 'dashboard_onboarding_content_action',
                'url' => $contentUrl,
                'is_complete' => $this->hasPublishedContent($enabled),
            ];
        }

        if (isset($enabled['Backups'])) {
            $items[] = [
                'key' => 'backups',
                'title_key' => 'dashboard_onboarding_backups_title',
                'content_key' => 'dashboard_onboarding_backups_content',
                'action_label_key' => 'dashboard_onboarding_backups_action',
                'url' => url('/admin/backups'),
                'is_complete' => $this->hasAvailableBackup(),
            ];
        }

        $completedCount = 0;
        $nextItem = null;
        $pendingItems = [];
        foreach ($items as $item) {
            if (!empty($item['is_complete'])) {
                $completedCount++;
                continue;
            }

            $pendingItems[] = $item;

            if ($nextItem === null) {
                $nextItem = $item;
            }
        }

        return [
            'items' => $pendingItems,
            'completed_count' => $completedCount,
            'total_count' => count($items),
            'next_item' => $nextItem,
        ];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function hasConfiguredBranding(array $settings): bool
    {
        return trim((string) ($settings['site_name'] ?? '')) !== ''
            && trim((string) ($settings['site_slogan'] ?? '')) !== ''
            && trim((string) ($settings['site_logo'] ?? '')) !== '';
    }

    private function hasConfiguredHomepage(): bool
    {
        $service = new SiteRoutingService();
        $state = $service->read();
        $homepage = is_array($state['homepage'] ?? null) ? $state['homepage'] : [];

        return (string) ($homepage['mode'] ?? '') === 'page'
            && trim((string) ($homepage['ref_group'] ?? '')) !== ''
            && is_array($service->resolveHomepagePage((string) (FlatFile::settings()['default_language'] ?? I18n::getLocale())));
    }

    private function hasConfiguredMainMenu(): bool
    {
        $path = BASE_PATH . '/data/menus/menus.json';
        if (!is_file($path)) {
            return false;
        }

        $content = @file_get_contents($path);
        if (!is_string($content) || trim($content) === '') {
            return false;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return false;
        }

        $items = $decoded['main']['items'] ?? [];
        return is_array($items) && $items !== [];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function hasConfiguredEmail(array $settings): bool
    {
        $transport = strtolower(trim((string) ($settings['mail_transport'] ?? 'mail')));
        $fromAddress = trim((string) ($settings['mail_from_address'] ?? ''));
        if ($fromAddress === '') {
            $fromAddress = trim((string) ($settings['site_email'] ?? ''));
        }

        if ($fromAddress === '' || filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        if ($transport !== 'smtp') {
            return true;
        }

        $password = trim((string) env('MAIL_SMTP_PASSWORD', ''));
        if ($password === '') {
            $password = trim((string) ($settings['mail_smtp_password'] ?? ''));
        }

        return trim((string) ($settings['mail_smtp_host'] ?? '')) !== ''
            && trim((string) ($settings['mail_smtp_username'] ?? '')) !== ''
            && $password !== '';
    }

    private function hasPublishedContent(array $enabled): bool
    {
        $publishedPages = isset($enabled['Pages']) ? $this->getGroupedPublishedPageCount() : 0;
        $publishedPosts = isset($enabled['Posts']) ? $this->getGroupedPublishedPostCount() : 0;

        return ($publishedPages + $publishedPosts) > 0;
    }

    private function hasAvailableBackup(): bool
    {
        try {
            $service = new SiteBackupService();
            return count($service->listBackups()) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function getDirectorySize(string $path): int
    {
        if (!is_dir($path)) return 0;
        
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }

    private function getRecentPosts(int $limit): array
    {
        $translations = new PostTranslationService(FlatFile::for('core/posts'));
        $groups = [];

        foreach ($translations->all() as $post) {
            $groupId = (string) ($post['translation_group'] ?? $post['id'] ?? '');
            if ($groupId === '') {
                continue;
            }

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [];
            }
            $groups[$groupId][] = $post;
        }

        $rows = [];
        foreach ($groups as $groupId => $groupTranslations) {
            $sourcePost = $translations->resolveSourcePost($groupId);
            if (!is_array($sourcePost)) {
                $sourcePost = reset($groupTranslations) ?: null;
            }
            if (!is_array($sourcePost)) {
                continue;
            }

            $row = $translations->normalizePost($sourcePost);
            $row['status'] = $translations->resolveEffectiveStatus($row);
            $rows[] = $row;
        }

        usort($rows, static function (array $a, array $b): int {
            $dateA = (string) ($a['created_at'] ?? '1970-01-01');
            $dateB = (string) ($b['created_at'] ?? '1970-01-01');
            return strtotime($dateB) <=> strtotime($dateA);
        });

        return array_slice($rows, 0, $limit);
    }

    private function getRecentPages(int $limit): array
    {
        $translations = new PageTranslationService(FlatFile::for('core/pages'));
        $groups = [];
        $rows = [];

        foreach ($translations->all() as $page) {
            $groupId = (string) ($page['translation_group'] ?? $page['id'] ?? '');
            if ($groupId === '') {
                continue;
            }

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [];
            }
            $groups[$groupId][] = $page;
        }

        foreach ($groups as $groupId => $groupTranslations) {
            $sourcePage = $translations->resolveSourcePage($groupId);
            if (!is_array($sourcePage)) {
                $sourcePage = reset($groupTranslations) ?: null;
            }
            if (!is_array($sourcePage)) {
                continue;
            }

            $row = $translations->normalizePage($sourcePage);
            $row['status'] = $translations->resolveEffectiveStatus($row);
            $rows[] = $row;
        }

        usort($rows, static function (array $a, array $b): int {
            $dateA = (string) ($a['updated_at'] ?? $a['created_at'] ?? '1970-01-01');
            $dateB = (string) ($b['updated_at'] ?? $b['created_at'] ?? '1970-01-01');
            return strtotime($dateB) <=> strtotime($dateA);
        });

        return array_slice($rows, 0, $limit);
    }

    private function getChartData(array $stats): array
    {
        $total = array_sum($stats);
        if ($total === 0) $total = 1; // Prevent division by zero

        $colors = [
            'pages' => '#4f46e5',    // Indigo
            'posts' => '#8b5cf6',    // Purple
            'media' => '#06b6d4',    // Cyan
            'users' => '#10b981',    // Emerald
            'comments' => '#f59e0b', // Amber
        ];

        $breakdown = [];
        foreach ($stats as $key => $value) {
            $breakdown[] = [
                'name' => ucfirst($key),
                'value' => $value,
                'percent' => round(($value / $total) * 100, 1),
                'color' => $colors[$key] ?? '#6b7280',
            ];
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown,
        ];
    }

}
