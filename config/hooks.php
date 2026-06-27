<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

return [
    // System lifecycle
    'app.booting' => [
        'label' => 'Application booting',
        'group' => 'system',
        'description' => 'Triggered before the application finishes booting.',
        'params' => ['app'],
    ],
    'app.booted' => [
        'label' => 'Application booted',
        'group' => 'system',
        'description' => 'Triggered after the application has booted.',
        'params' => ['app'],
    ],
    'response.before_send' => [
        'label' => 'Before response send',
        'group' => 'system',
        'description' => 'Modify the final response before it is sent.',
        'params' => ['response'],
    ],

    // UI shell
    'frontend.assets.head' => [
        'label' => 'Frontend assets head',
        'group' => 'ui',
        'description' => 'Register frontend head assets from modules and extensions.',
        'params' => ['payload'],
    ],
    'frontend.assets.footer' => [
        'label' => 'Frontend assets footer',
        'group' => 'ui',
        'description' => 'Register frontend footer assets from modules and extensions.',
        'params' => ['payload'],
    ],
    'admin.assets.head' => [
        'label' => 'Admin assets head',
        'group' => 'ui',
        'description' => 'Register admin head assets from modules and extensions.',
        'params' => ['payload'],
    ],
    'admin.assets.footer' => [
        'label' => 'Admin assets footer',
        'group' => 'ui',
        'description' => 'Register admin footer assets from modules and extensions.',
        'params' => ['payload'],
    ],
    'frontend.layout.body_start' => [
        'label' => 'Frontend layout body start',
        'group' => 'ui',
        'description' => 'Render frontend fragments immediately after the opening body tag.',
        'params' => ['payload'],
    ],
    'frontend.layout.body_end' => [
        'label' => 'Frontend layout body end',
        'group' => 'ui',
        'description' => 'Render frontend fragments immediately before the closing body area.',
        'params' => ['payload'],
    ],
    'admin.layout.body_start' => [
        'label' => 'Admin layout body start',
        'group' => 'ui',
        'description' => 'Render admin fragments immediately after the opening body tag.',
        'params' => ['payload'],
    ],
    'admin.layout.modals' => [
        'label' => 'Admin layout modals',
        'group' => 'ui',
        'description' => 'Render admin modal or drawer fragments in the shared admin modal area.',
        'params' => ['payload'],
    ],
    'admin.layout.body_end' => [
        'label' => 'Admin layout body end',
        'group' => 'ui',
        'description' => 'Render admin fragments immediately before the closing body area.',
        'params' => ['payload'],
    ],
    'admin.guided_tour.module_tours' => [
        'label' => 'Admin guided tour module tours',
        'group' => 'ui',
        'description' => 'Allow modules and extensions to register guided-tour route maps and steps for the shared admin shell.',
        'params' => ['payload'],
    ],

    // Auth
    'auth.login' => [
        'label' => 'User login',
        'group' => 'auth',
        'description' => 'After a user logs in.',
        'params' => ['user'],
    ],
    'auth.logout' => [
        'label' => 'User logout',
        'group' => 'auth',
        'description' => 'After a user logs out.',
        'params' => ['user'],
    ],
    'auth.register' => [
        'label' => 'User register',
        'group' => 'auth',
        'description' => 'After a user registers.',
        'params' => ['user'],
    ],
    'auth.password_reset' => [
        'label' => 'Password reset',
        'group' => 'auth',
        'description' => 'After a password reset.',
        'params' => ['user'],
    ],
    'auth.permissions.extend' => [
        'label' => 'Auth permissions extend',
        'group' => 'auth',
        'description' => 'Extend permissions and role mappings from modules.',
        'params' => ['payload'],
    ],
    'auth.menus.extend' => [
        'label' => 'Auth menus extend',
        'group' => 'auth',
        'description' => 'Extend role-based admin menus from modules.',
        'params' => ['payload'],
    ],
    'auth.menus.transform' => [
        'label' => 'Auth menus transform',
        'group' => 'auth',
        'description' => 'Transform the resolved role-based admin menus after generic filtering.',
        'params' => ['payload'],
    ],

    // Shortcodes
    'shortcodes.register' => [
        'label' => 'Shortcodes register',
        'group' => 'content',
        'description' => 'Register shortcode handlers from enabled modules.',
        'params' => ['payload'],
    ],
    'content.renderer.resolve' => [
        'label' => 'Content renderer resolve',
        'group' => 'content',
        'description' => 'Allow extensions to resolve a renderable content entity before standard fallback rendering.',
        'params' => ['payload'],
    ],

    // Pages
    'pages.before_save' => [
        'label' => 'Pages before save',
        'group' => 'content',
        'description' => 'Before a page is saved.',
        'params' => ['page'],
    ],
    'pages.after_save' => [
        'label' => 'Pages after save',
        'group' => 'content',
        'description' => 'After a page is saved.',
        'params' => ['page'],
    ],
    'pages.before_archive' => [
        'label' => 'Pages before archive',
        'group' => 'content',
        'description' => 'Before a page is archived.',
        'params' => ['page'],
    ],
    'pages.after_archive' => [
        'label' => 'Pages after archive',
        'group' => 'content',
        'description' => 'After a page is archived.',
        'params' => ['page'],
    ],
    'pages.before_delete' => [
        'label' => 'Pages before delete',
        'group' => 'content',
        'description' => 'Before a page is deleted.',
        'params' => ['page'],
    ],
    'pages.after_delete' => [
        'label' => 'Pages after delete',
        'group' => 'content',
        'description' => 'After a page is deleted.',
        'params' => ['page'],
    ],
    'pages.before_render' => [
        'label' => 'Pages before render',
        'group' => 'content',
        'description' => 'Before a page is rendered.',
        'params' => ['page'],
    ],
    'pages.frontend.notices' => [
        'label' => 'Pages frontend notices',
        'group' => 'content',
        'description' => 'Allow modules and extensions to add frontend notices for a rendered page.',
        'params' => ['payload'],
    ],
    'pages.after_render' => [
        'label' => 'Pages after render',
        'group' => 'content',
        'description' => 'After a page is rendered.',
        'params' => ['page'],
    ],
    'pages.admin.route_override' => [
        'label' => 'Pages admin route override',
        'group' => 'content',
        'description' => 'Allow extensions to override admin entry routes for the Pages domain.',
        'params' => ['payload'],
    ],

    // Posts
    'posts.before_save' => [
        'label' => 'Posts before save',
        'group' => 'content',
        'description' => 'Before a post is saved.',
        'params' => ['post'],
    ],
    'posts.after_save' => [
        'label' => 'Posts after save',
        'group' => 'content',
        'description' => 'After a post is saved.',
        'params' => ['post'],
    ],
    'posts.before_publish' => [
        'label' => 'Posts before publish',
        'group' => 'content',
        'description' => 'Before a post is published.',
        'params' => ['post'],
    ],
    'posts.after_publish' => [
        'label' => 'Posts after publish',
        'group' => 'content',
        'description' => 'After a post is published.',
        'params' => ['post'],
    ],
    'posts.before_archive' => [
        'label' => 'Posts before archive',
        'group' => 'content',
        'description' => 'Before a post is archived.',
        'params' => ['post'],
    ],
    'posts.after_archive' => [
        'label' => 'Posts after archive',
        'group' => 'content',
        'description' => 'After a post is archived.',
        'params' => ['post'],
    ],
    'posts.before_delete' => [
        'label' => 'Posts before delete',
        'group' => 'content',
        'description' => 'Before a post is deleted.',
        'params' => ['post'],
    ],
    'posts.after_delete' => [
        'label' => 'Posts after delete',
        'group' => 'content',
        'description' => 'After a post is deleted.',
        'params' => ['post'],
    ],
    'posts.before_render' => [
        'label' => 'Posts before render',
        'group' => 'content',
        'description' => 'Before a post is rendered.',
        'params' => ['post'],
    ],
    'posts.after_render' => [
        'label' => 'Posts after render',
        'group' => 'content',
        'description' => 'After a post is rendered.',
        'params' => ['post'],
    ],

    // Categories
    'categories.before_save' => [
        'label' => 'Categories before save',
        'group' => 'content',
        'description' => 'Before a category is saved.',
        'params' => ['category'],
    ],
    'categories.after_save' => [
        'label' => 'Categories after save',
        'group' => 'content',
        'description' => 'After a category is saved.',
        'params' => ['category'],
    ],
    'categories.before_archive' => [
        'label' => 'Categories before archive',
        'group' => 'content',
        'description' => 'Before a category is archived.',
        'params' => ['category'],
    ],
    'categories.after_archive' => [
        'label' => 'Categories after archive',
        'group' => 'content',
        'description' => 'After a category is archived.',
        'params' => ['category'],
    ],
    'categories.before_delete' => [
        'label' => 'Categories before delete',
        'group' => 'content',
        'description' => 'Before a category is deleted.',
        'params' => ['category'],
    ],
    'categories.after_delete' => [
        'label' => 'Categories after delete',
        'group' => 'content',
        'description' => 'After a category is deleted.',
        'params' => ['category'],
    ],

    // Comments
    'comments.before_approve' => [
        'label' => 'Comments before approve',
        'group' => 'content',
        'description' => 'Before a comment is approved.',
        'params' => ['comment'],
    ],
    'comments.after_approve' => [
        'label' => 'Comments after approve',
        'group' => 'content',
        'description' => 'After a comment is approved.',
        'params' => ['comment'],
    ],
    'comments.before_delete' => [
        'label' => 'Comments before delete',
        'group' => 'content',
        'description' => 'Before a comment is deleted.',
        'params' => ['comment'],
    ],
    'comments.after_delete' => [
        'label' => 'Comments after delete',
        'group' => 'content',
        'description' => 'After a comment is deleted.',
        'params' => ['comment'],
    ],

    // Media
    'media.uploaded' => [
        'label' => 'Media uploaded',
        'group' => 'media',
        'description' => 'After a media file is uploaded.',
        'params' => ['media'],
    ],
    'media.deleted' => [
        'label' => 'Media deleted',
        'group' => 'media',
        'description' => 'After a media file is deleted.',
        'params' => ['media'],
    ],
    'media.synced' => [
        'label' => 'Media synced',
        'group' => 'media',
        'description' => 'After the media library is synchronized.',
        'params' => ['summary'],
    ],

    // Menus
    'menus.before_save' => [
        'label' => 'Menus before save',
        'group' => 'menus',
        'description' => 'Before menus are saved.',
        'params' => ['menus'],
    ],
    'menus.after_save' => [
        'label' => 'Menus after save',
        'group' => 'menus',
        'description' => 'After menus are saved.',
        'params' => ['menus'],
    ],
    'menus.before_render' => [
        'label' => 'Menus before render',
        'group' => 'menus',
        'description' => 'Before menus are rendered.',
        'params' => ['menus'],
    ],
    'menus.after_render' => [
        'label' => 'Menus after render',
        'group' => 'menus',
        'description' => 'After menus are rendered.',
        'params' => ['menus'],
    ],
    'menus.admin.route_override' => [
        'label' => 'Menus admin route override',
        'group' => 'menus',
        'description' => 'Allow extensions to override admin entry routes for the Menu domain.',
        'params' => ['payload'],
    ],
    'menus.render.resolve' => [
        'label' => 'Menus render resolve',
        'group' => 'menus',
        'description' => 'Allow extensions to resolve a frontend menu renderer before the default fallback output.',
        'params' => ['payload'],
    ],

    // Dashboard
    'dashboard.admin.banners' => [
        'label' => 'Dashboard admin banners',
        'group' => 'dashboard',
        'description' => 'Render dashboard banners contributed by modules and extensions.',
        'params' => ['payload'],
    ],

    // Footer
    'footer.before_save' => [
        'label' => 'Footer before save',
        'group' => 'content',
        'description' => 'Before footer settings are saved.',
        'params' => ['footer'],
    ],
    'footer.after_save' => [
        'label' => 'Footer after save',
        'group' => 'content',
        'description' => 'After footer settings are saved.',
        'params' => ['footer'],
    ],

    // Themes & settings
    'themes.before_activate' => [
        'label' => 'Themes before activate',
        'group' => 'themes',
        'description' => 'Before a theme is activated.',
        'params' => ['theme'],
    ],
    'themes.after_activate' => [
        'label' => 'Themes after activate',
        'group' => 'themes',
        'description' => 'After a theme is activated.',
        'params' => ['theme'],
    ],
    'settings.before_save' => [
        'label' => 'Settings before save',
        'group' => 'settings',
        'description' => 'Before settings are saved.',
        'params' => ['settings'],
    ],
    'settings.after_save' => [
        'label' => 'Settings after save',
        'group' => 'settings',
        'description' => 'After settings are saved.',
        'params' => ['settings'],
    ],

    // Modules lifecycle
    'modules.before_enable' => [
        'label' => 'Modules before enable',
        'group' => 'modules',
        'description' => 'Before a module is enabled.',
        'params' => ['module'],
    ],
    'modules.after_enable' => [
        'label' => 'Modules after enable',
        'group' => 'modules',
        'description' => 'After a module is enabled.',
        'params' => ['module'],
    ],
    'modules.before_disable' => [
        'label' => 'Modules before disable',
        'group' => 'modules',
        'description' => 'Before a module is disabled.',
        'params' => ['module'],
    ],
    'modules.after_disable' => [
        'label' => 'Modules after disable',
        'group' => 'modules',
        'description' => 'After a module is disabled.',
        'params' => ['module'],
    ],
    'modules.before_delete' => [
        'label' => 'Modules before delete',
        'group' => 'modules',
        'description' => 'Before a module is deleted.',
        'params' => ['module'],
    ],
    'modules.after_delete' => [
        'label' => 'Modules after delete',
        'group' => 'modules',
        'description' => 'After a module is deleted.',
        'params' => ['module'],
    ],

    // Languages
    'languages.before_scan' => [
        'label' => 'Languages before scan',
        'group' => 'languages',
        'description' => 'Before translation scan starts.',
        'params' => ['locale'],
    ],
    'languages.after_scan' => [
        'label' => 'Languages after scan',
        'group' => 'languages',
        'description' => 'After translation scan completes.',
        'params' => ['summary'],
    ],
    'languages.before_save' => [
        'label' => 'Languages before save',
        'group' => 'languages',
        'description' => 'Before translations are saved.',
        'params' => ['locale', 'module'],
    ],
    'languages.after_save' => [
        'label' => 'Languages after save',
        'group' => 'languages',
        'description' => 'After translations are saved.',
        'params' => ['locale', 'module'],
    ],

];
