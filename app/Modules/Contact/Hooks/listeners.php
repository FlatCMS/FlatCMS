<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

use App\Modules\Contact\Support\ContactFormRenderer;

 $contactAssetsRequired = static function (array $payload = []): bool {
    $extractContent = static function (mixed $entry): string {
        if (!is_array($entry)) {
            return '';
        }

        $content = trim((string) ($entry['content'] ?? ''));
        if ($content !== '') {
            return $content;
        }

        return trim((string) ($entry['body'] ?? ''));
    };

    $haystacks = [
        $extractContent($payload['page'] ?? null),
        $extractContent($payload['post'] ?? null),
    ];

    $markers = [
        '[contact-form',
        'flatcms-contact-native',
        'flatcms-contact-form',
        'flatcms-contact-embed',
        'contact_form_slug',
        'data-contact-form',
    ];

    foreach ($haystacks as $content) {
        if ($content === '') {
            continue;
        }

        foreach ($markers as $marker) {
            if (stripos($content, $marker) !== false) {
                return true;
            }
        }
    }

    return false;
};

hook_register('shortcodes.register', static function (): array {
    return [
        'contact-form' => static function (array $attributes = [], array $context = []): string {
            $slug = trim((string) ($attributes['slug'] ?? ''));

            try {
                return ContactFormRenderer::render($slug, $context);
            } catch (\Throwable) {
                return '';
            }
        },
    ];
}, ['module' => 'Contact', 'priority' => 10]);

hook_register('frontend.assets.head', static function (array $payload = []) use ($contactAssetsRequired): array {
    if (!$contactAssetsRequired($payload)) {
        return [];
    }

    return [[
        'id' => 'contact.front.css',
        'type' => 'css',
        'src' => module_asset('Contact', 'css/contact-front.css'),
        'priority' => 10,
    ]];
}, ['module' => 'Contact', 'priority' => 10]);

hook_register('frontend.assets.footer', static function (array $payload = []) use ($contactAssetsRequired): array {
    if (!$contactAssetsRequired($payload)) {
        return [];
    }

    return [[
        'id' => 'contact.front.js',
        'type' => 'js',
        'src' => module_asset('Contact', 'js/contact-front.js'),
        'priority' => 10,
    ]];
}, ['module' => 'Contact', 'priority' => 10]);

hook_register('admin.guided_tour.module_tours', static function (): array {
    return [
        'contact_forms' => [
            'routes' => ['admin/contact/forms'],
            'steps' => [
                guided_tour_step('[data-tour-target="contact-form-header"]', __('contact_tour_form_main_title', 'Contact'), __('contact_tour_form_main_content', 'Contact'), 'bottom'),
                guided_tour_step('[data-tour-section="contact-form-identity"]', __('contact_tour_form_identity_title', 'Contact'), __('contact_tour_form_identity_content', 'Contact'), 'top'),
                guided_tour_step('[data-tour-target="contact-form-legal"]', __('contact_tour_form_legal_title', 'Contact'), __('contact_tour_form_legal_content', 'Contact'), 'top', [
                    'whenVisible' => '[data-tour-target="contact-form-legal"]:not(.is-hidden)',
                ]),
                guided_tour_step('[data-tour-target="contact-form-builder"]', __('contact_tour_form_builder_title', 'Contact'), __('contact_tour_form_builder_content', 'Contact'), 'top'),
                guided_tour_step('[data-tour-target="contact-form-builder-canvas"]', __('contact_tour_form_canvas_title', 'Contact'), __('contact_tour_form_canvas_content', 'Contact'), 'top'),
                guided_tour_step('[data-tour-target="contact-form-translations-trigger"]', __('contact_tour_form_translations_title', 'Contact'), __('contact_tour_form_translations_content', 'Contact'), 'left'),
                guided_tour_step('[data-tour-target="contact-form-delivery"]', __('contact_tour_form_sidebar_title', 'Contact'), __('contact_tour_form_sidebar_content', 'Contact'), 'left'),
                guided_tour_step('[data-tour-target="contact-form-save"]', __('contact_tour_next_action_title', 'Contact'), __('contact_tour_form_create_next_content', 'Contact'), 'left', [
                    'whenVisible' => 'form[data-tour-state="create"]',
                ]),
                guided_tour_step('[data-tour-target="contact-form-save"]', __('contact_tour_next_action_title', 'Contact'), __('contact_tour_form_edit_next_content', 'Contact'), 'left', [
                    'whenVisible' => 'form[data-tour-state="edit"]',
                ]),
            ],
        ],
        'contact' => [
            'routes' => ['admin/contact'],
            'steps' => [
                guided_tour_step('[data-tour-target="contact-forms-create"]', __('contact_tour_next_action_title', 'Contact'), __('contact_tour_list_empty_content', 'Contact'), 'left', [
                    'whenVisible' => '[data-tour-target="contact-forms-list"][data-tour-state="empty"]',
                ]),
                guided_tour_step('[data-tour-target="contact-forms-toolbar"]', __('contact_tour_list_toolbar_title', 'Contact'), __('contact_tour_list_toolbar_content', 'Contact'), 'bottom', [
                    'whenVisible' => '[data-tour-target="contact-forms-list"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="contact-forms-list"]', __('contact_tour_list_table_title', 'Contact'), __('contact_tour_list_table_content', 'Contact'), 'top', [
                    'whenVisible' => '[data-tour-target="contact-forms-list"][data-tour-state="ready"]',
                ]),
                guided_tour_step('[data-tour-target="contact-forms-create"]', __('contact_tour_next_action_title', 'Contact'), __('contact_tour_list_ready_next_content', 'Contact'), 'left', [
                    'whenVisible' => '[data-tour-target="contact-forms-list"][data-tour-state="ready"]',
                ]),
            ],
        ],
    ];
}, ['module' => 'Contact', 'priority' => 20]);
