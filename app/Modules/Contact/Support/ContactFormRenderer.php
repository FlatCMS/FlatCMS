<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\Contact\Support;

use App\Core\ContentDocumentStore;
use App\Core\FlatFile;
use App\Core\I18n;
use App\Core\Security\Turnstile;
use App\Modules\Contact\Services\ContactFormTranslationService;
use App\Modules\Contact\Services\FormService;
use App\Modules\Pages\Support\SystemPages;

final class ContactFormRenderer
{
    /**
     * @param array<string, mixed> $context
     */
    public static function render(string $slug = '', array $context = []): string
    {
        I18n::load('Contact');

        $forms = new FormService();
        $translations = new ContactFormTranslationService();
        $allForms = $forms->all();

        $resolvedForm = null;
        $slug = trim($slug);
        $hasRequestedSlug = $slug !== '';

        if ($hasRequestedSlug) {
            $normalizedSlug = $forms->sanitizeSlug($slug, 'contact-main');
            foreach ($allForms as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }
                if ((string) ($candidate['slug'] ?? '') !== $normalizedSlug) {
                    continue;
                }
                if (empty($candidate['is_active'])) {
                    continue;
                }
                $resolvedForm = $candidate;
                break;
            }
        }

        if (!$hasRequestedSlug) {
            if (!is_array($resolvedForm)) {
                $defaultForm = $forms->getDefault();
                if (is_array($defaultForm) && !empty($defaultForm['is_active'])) {
                    $resolvedForm = $defaultForm;
                }
            }

            if (!is_array($resolvedForm)) {
                foreach ($allForms as $candidate) {
                    if (is_array($candidate) && !empty($candidate['is_active'])) {
                        $resolvedForm = $candidate;
                        break;
                    }
                }
            }
        }

        if (is_array($resolvedForm)) {
            $resolvedForm = $translations->resolveForLocale($resolvedForm, locale());
            $pages = ContentDocumentStore::for('core/pages');
            $legalPage = SystemPages::findByKey($pages, SystemPages::LEGAL_NOTICE_KEY);
            $privacyPage = SystemPages::findByKey($pages, SystemPages::PRIVACY_POLICY_KEY);

            if (is_array($legalPage)) {
                $resolvedForm['newsletter_legal_url'] = SystemPages::frontendUrl($legalPage);
            }
            if (is_array($privacyPage)) {
                $resolvedForm['newsletter_privacy_url'] = SystemPages::frontendUrl($privacyPage);
            }
        }

        $settings = FlatFile::settings();
        $contactCaptchaEnabled = (int) ($settings['contact_enable_captcha'] ?? 0) === 1;
        $turnstile = new Turnstile();
        $resolvedTurnstileSiteKey = $turnstile->siteKey();
        $resolvedTurnstileEnabled = $turnstile->isEnabled() && $resolvedTurnstileSiteKey !== '';
        $captchaEnabled = $contactCaptchaEnabled && $resolvedTurnstileEnabled;

        $sourceUrl = trim((string) ($context['source_url'] ?? ''));
        if ($sourceUrl === '' && function_exists('flatcms_current_source_url')) {
            $sourceUrl = flatcms_current_source_url();
        }

        $viewPath = BASE_PATH . '/app/Modules/Contact/Views/front/index.php';
        if (!is_file($viewPath)) {
            return '';
        }

        $data = [
            'contactForm' => $resolvedForm,
            'formAction' => url('/contact/send'),
            'sourceUrl' => $sourceUrl,
            'turnstileEnabled' => $captchaEnabled,
            'turnstileSiteKey' => $captchaEnabled ? $resolvedTurnstileSiteKey : '',
            'embedMode' => true,
            'showIntro' => false,
        ];

        ob_start();
        extract($data, EXTR_SKIP);
        include $viewPath;
        $html = ob_get_clean();

        return is_string($html) ? $html : '';
    }
}
