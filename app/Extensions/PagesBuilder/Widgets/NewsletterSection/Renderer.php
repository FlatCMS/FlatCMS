<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: LicenseRef-FlatCMS-Commercial
 *
 * Premium FlatCMS component. See LICENSING.md, COMMERCIAL_LICENSE.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Extensions\PagesBuilder\Widgets\NewsletterSection;

use App\Extensions\PagesBuilder\Services\PageBuilderContactFormCatalogService;
use App\Extensions\PagesBuilder\Services\PageBuilderWidgetLocaleService;
use App\Extensions\PagesBuilder\Support\AbstractWidgetRenderer;

final class Renderer extends AbstractWidgetRenderer
{
    protected static function renderer(): callable
    {
        return static function (array $settings, array $context): array {
            if (class_exists(\App\Core\I18n::class)) {
                \App\Core\I18n::load('Contact');
            }

            $helpers = is_array($context['helpers'] ?? null) ? $context['helpers'] : [];
            $escape = $helpers['escape'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $escapeAttr = $helpers['escape_attr'] ?? static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            $translate = static fn(string $key, string $fallback = ''): string => PageBuilderWidgetLocaleService::translate('NewsletterSection', $key, $fallback);

            $normalizeVariant = static function (mixed $raw): string {
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['subtle', 'strong', 'dark'], true) ? $value : 'subtle';
            };

            $resolveTextStyle = static function (array $source, string $prefix, string $fallbackAlign): array {
                $keyPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?: 'textStyle';
                $iconPosition = strtolower(trim((string) ($source[$keyPrefix . 'IconPosition'] ?? 'start')));

                return [
                    'align' => self::normalizeAlign((string) ($source[$keyPrefix . 'Align'] ?? ''), $fallbackAlign),
                    'font' => self::normalizeTextStyleFont((string) ($source[$keyPrefix . 'Font'] ?? 'inherit')),
                    'size' => self::normalizeTextStyleSize((string) ($source[$keyPrefix . 'Size'] ?? 'inherit')),
                    'bold' => self::normalizeToggle($source[$keyPrefix . 'Bold'] ?? false),
                    'italic' => self::normalizeToggle($source[$keyPrefix . 'Italic'] ?? false),
                    'underline' => self::normalizeToggle($source[$keyPrefix . 'Underline'] ?? false),
                    'color' => self::normalizeColor((string) ($source[$keyPrefix . 'Color'] ?? '')),
                    'list' => self::normalizeTextStyleList((string) ($source[$keyPrefix . 'List'] ?? 'none')),
                    'icon' => self::sanitizeIconClass((string) ($source[$keyPrefix . 'Icon'] ?? '')),
                    'iconPosition' => in_array($iconPosition, ['start', 'end'], true) ? $iconPosition : 'start',
                ];
            };

            $injectTextIcon = static function (string $content, array $style) use ($escapeAttr): string {
                $icon = trim((string) ($style['icon'] ?? ''));
                if ($icon === '') {
                    return $content;
                }

                $iconPosition = strtolower(trim((string) ($style['iconPosition'] ?? 'start')));
                if (!in_array($iconPosition, ['start', 'end'], true)) {
                    $iconPosition = 'start';
                }

                $iconHtml = '<i class="' . $escapeAttr($icon) . ' pb-styled-text-icon pb-styled-text-icon-' . $escapeAttr($iconPosition) . '" aria-hidden="true"></i>';
                return $iconPosition === 'end' ? $content . $iconHtml : $iconHtml . $content;
            };

            $injectTextListMarker = static function (string $content, array $style) use ($escape): string {
                $listStyle = self::normalizeTextStyleList((string) ($style['list'] ?? 'none'));
                if ($listStyle === 'none') {
                    return $content;
                }

                $glyph = match ($listStyle) {
                    'circle' => '∘',
                    'square' => '▪',
                    default => '•',
                };

                return '<span class="pb-styled-text-list-marker pb-styled-text-list-marker-' . $escape($listStyle) . '" aria-hidden="true">'
                    . $escape($glyph)
                    . '</span>' . $content;
            };

            $renderStyledText = static function (string $text, string $tag, string $className, array $style) use (
                $escape,
                $escapeAttr,
                $injectTextIcon,
                $injectTextListMarker
            ): string {
                $value = trim($text);
                if ($value === '') {
                    return '';
                }

                $content = '<span class="pb-styled-text-content">' . $escape($value) . '</span>';
                $decorated = $injectTextListMarker($injectTextIcon($content, $style), $style);
                return '<' . $tag . ' class="' . $escapeAttr($className) . '">' . $decorated . '</' . $tag . '>';
            };

            $renderStyledParagraphs = static function (string $text, string $className, array $style) use (
                $escape,
                $escapeAttr,
                $injectTextIcon,
                $injectTextListMarker
            ): string {
                $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));
                if ($normalized === '') {
                    return '';
                }

                $chunks = preg_split('/\n\s*\n/u', $normalized) ?: [];
                $paragraphs = [];
                foreach ($chunks as $chunk) {
                    $line = trim((string) $chunk);
                    if ($line === '') {
                        continue;
                    }
                    $safeLine = nl2br($escape($line), false);
                    $content = '<span class="pb-styled-text-content">' . $safeLine . '</span>';
                    $paragraphs[] = '<p class="pb-newsletter-section-body-paragraph">'
                        . $injectTextListMarker($injectTextIcon($content, $style), $style)
                        . '</p>';
                }

                if ($paragraphs === []) {
                    return '';
                }

                return '<div class="' . $escapeAttr($className) . '">' . implode('', $paragraphs) . '</div>';
            };

            $buildTextStyleRules = static function (string $safeId, string $selector, array $style) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $scopedSelector = self::blockSelector($safeId, $selector);
                $rules = ['text-align:' . $escapeAttr(self::normalizeAlign((string) ($style['align'] ?? 'left'))) . ';'];
                $color = trim((string) ($style['color'] ?? ''));
                if ($color !== '') {
                    $rules[] = 'color:' . $escapeAttr($color) . ';';
                }

                $fontRule = self::widgetTextFontRule((string) ($style['font'] ?? 'inherit'));
                if ($fontRule !== '') {
                    $rules[] = $fontRule;
                }
                $sizeRule = self::widgetTextSizeRule((string) ($style['size'] ?? 'inherit'));
                if ($sizeRule !== '') {
                    $rules[] = $sizeRule;
                }

                $css = [$scopedSelector . '{' . implode('', $rules) . '}'];
                $contentRules = [];
                if (self::normalizeToggle($style['bold'] ?? false)) {
                    $contentRules[] = 'font-weight:700;';
                }
                if (self::normalizeToggle($style['italic'] ?? false)) {
                    $contentRules[] = 'font-style:italic;';
                }
                if (self::normalizeToggle($style['underline'] ?? false)) {
                    $contentRules[] = 'text-decoration:underline;';
                }

                if ($contentRules !== []) {
                    $css[] = $scopedSelector . ' .pb-styled-text-content{' . implode('', $contentRules) . '}';
                }

                $listStyle = self::normalizeTextStyleList((string) ($style['list'] ?? 'none'));
                if ($listStyle !== 'none') {
                    $css[] = $scopedSelector . ' .pb-styled-text-list-marker{display:inline-block;margin-right:0.45rem;}';
                }

                return $css;
            };

            $buildSelfAlignRules = static function (string $safeId, string $selector, string $align) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $normalizedAlign = self::normalizeAlign($align, 'left');
                $justifySelf = match ($normalizedAlign) {
                    'center' => 'center',
                    'right' => 'end',
                    default => 'start',
                };

                return [
                    self::blockSelector($safeId, $selector) . '{justify-self:' . $escapeAttr($justifySelf) . ';}',
                ];
            };

            $buildAccentColorRules = static function (string $safeId, string $selector, array $style) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $color = trim((string) ($style['color'] ?? ''));
                if ($color === '') {
                    return [];
                }

                return [
                    self::blockSelector($safeId, $selector) . '{color:' . $escapeAttr($color) . ';}',
                ];
            };

            $buildFeatureListAlignRules = static function (string $safeId, array $style) use ($escapeAttr): array {
                if ($safeId === '') {
                    return [];
                }

                $align = self::normalizeAlign((string) ($style['align'] ?? 'left'), 'left');
                $direction = $align === 'right' ? 'row-reverse' : 'row';
                $justifySelf = match ($align) {
                    'center' => 'center',
                    'right' => 'end',
                    default => 'start',
                };
                $textAlign = $align === 'center' ? 'left' : $align;
                $featureListJustify = $align === 'center' ? 'stretch' : $justifySelf;
                $featureItemJustify = $align === 'center' ? 'stretch' : $justifySelf;
                $featureRules = [
                    'flex-direction:' . $escapeAttr($direction) . ';',
                    'justify-self:' . $escapeAttr($featureItemJustify) . ';',
                ];
                $featureTextRules = ['text-align:' . $escapeAttr($textAlign) . ';'];
                $featureListRules = ['justify-items:' . $escapeAttr($featureListJustify) . ';'];

                if ($align === 'center') {
                    $featureListRules[] = 'justify-self:center;';
                    $featureListRules[] = 'width:fit-content;';
                    $featureListRules[] = 'max-width:100%;';
                    $featureListRules[] = 'text-align:left;';
                    $featureRules[] = 'width:100%;';
                    $featureRules[] = 'max-width:100%;';
                    $featureRules[] = 'text-align:left;';
                    $featureTextRules[] = 'flex:1 1 auto;';
                    $featureTextRules[] = 'width:100%;';
                }

                return [
                    self::blockSelector($safeId, '.pb-newsletter-section-features') . '{' . implode('', $featureListRules) . '}',
                    self::blockSelector($safeId, '.pb-newsletter-section-feature') . '{' . implode('', $featureRules) . '}',
                    self::blockSelector($safeId, '.pb-newsletter-section-feature-text') . '{' . implode('', $featureTextRules) . '}',
                ];
            };

            $parseLines = static function (string $raw): array {
                $value = str_replace(["\r\n", "\r"], "\n", trim($raw));
                if ($value === '') {
                    return [];
                }

                $items = array_map(
                    static fn(string $item): string => trim(preg_replace('/^[-*•\s]+/u', '', $item) ?? ''),
                    explode("\n", $value)
                );

                return array_values(array_filter($items, static fn(string $item): bool => $item !== ''));
            };

            $useCustomDesign = self::normalizeToggle($settings['useCustomDesign'] ?? '', false);
            $designSurfaceColor = self::normalizeColor((string) ($settings['designSurfaceColor'] ?? ''));
            $designTextColor = self::normalizeColor((string) ($settings['designTextColor'] ?? ''));
            $designBorderStyle = self::normalizeBorderStyle($settings['designBorderStyle'] ?? 'inherit');
            $designBorderWidth = max(0, min(8, (int) ($settings['designBorderWidth'] ?? 1)));
            $designBorderColor = self::normalizeColor((string) ($settings['designBorderColor'] ?? ''));
            $designRadius = max(0, min(48, (int) ($settings['designRadius'] ?? 16)));
            $designShadow = self::normalizeShadowPreset($settings['designShadow'] ?? 'inherit');

            $showEyebrow = self::normalizeToggle($settings['showEyebrow'] ?? true, true);
            $showBody = self::normalizeToggle($settings['showBody'] ?? true, true);
            $showFeatures = self::normalizeToggle($settings['showFeatures'] ?? true, true);
            $showProof = self::normalizeToggle($settings['showProof'] ?? true, true);

            $eyebrow = trim((string) ($settings['eyebrow'] ?? ''));
            $title = trim((string) ($settings['title'] ?? ''));
            $subtitle = trim((string) ($settings['subtitle'] ?? ''));
            $body = trim((string) ($settings['body'] ?? ''));
            $featureItems = $parseLines((string) ($settings['featureItems'] ?? ''));
            $proofLabel = trim((string) ($settings['proofLabel'] ?? ''));
            $formTitle = trim((string) ($settings['formTitle'] ?? ''));
            $formDescription = trim((string) ($settings['formDescription'] ?? ''));
            $emailLabel = trim((string) ($settings['emailLabel'] ?? ''));
            $placeholder = trim((string) ($settings['placeholder'] ?? ''));
            $buttonLabel = trim((string) ($settings['buttonLabel'] ?? ''));
            $helperText = trim((string) ($settings['helperText'] ?? ''));
            $newsletterFormSlug = trim((string) ($settings['newsletterFormSlug'] ?? 'newsletter-rgpd'));
            if ($newsletterFormSlug === '') {
                $newsletterFormSlug = 'newsletter-rgpd';
            }
            $selectedNewsletterForm = (new PageBuilderContactFormCatalogService())->findFormBySlug($newsletterFormSlug);
            if ($buttonLabel === '' && is_array($selectedNewsletterForm)) {
                $buttonLabel = trim((string) ($selectedNewsletterForm['submit_label'] ?? ''));
            }
            $consentLabel = trim((string) ($settings['consentLabel'] ?? ''));
            $consentHelp = trim((string) ($settings['consentHelp'] ?? ''));
            $consentLinksPrefix = trim((string) ($settings['consentLinksPrefix'] ?? ''));
            $legalLinkLabel = trim((string) ($settings['legalLinkLabel'] ?? ''));
            $privacyLinkLabel = trim((string) ($settings['privacyLinkLabel'] ?? ''));
            $captchaLabel = trim((string) ($settings['captchaLabel'] ?? ''));
            $emptyMessage = trim((string) ($settings['emptyMessage'] ?? ''));
            $align = self::normalizeAlign((string) ($settings['align'] ?? 'left'));
            $variant = $normalizeVariant($settings['variant'] ?? 'subtle');

            $eyebrowStyle = $resolveTextStyle($settings, 'eyebrowStyle', $align);
            $titleStyle = $resolveTextStyle($settings, 'titleStyle', $align);
            $subtitleStyle = $resolveTextStyle($settings, 'subtitleStyle', $align);
            $bodyStyle = $resolveTextStyle($settings, 'bodyStyle', $align);
            $featureStyle = $resolveTextStyle($settings, 'featureStyle', $align);
            $proofStyle = $resolveTextStyle($settings, 'proofStyle', $align);
            $formTitleStyle = $resolveTextStyle($settings, 'formTitleStyle', $align);
            $formDescriptionStyle = $resolveTextStyle($settings, 'formDescriptionStyle', $align);
            $helperTextStyle = $resolveTextStyle($settings, 'helperTextStyle', $align);

            $legalUrl = trim((string) ($selectedNewsletterForm['newsletter_legal_url'] ?? ''));
            $privacyUrl = trim((string) ($selectedNewsletterForm['newsletter_privacy_url'] ?? ''));
            if (($legalUrl === '' || $privacyUrl === '') && class_exists(\App\Core\FlatFile::class) && class_exists(\App\Modules\Pages\Support\SystemPages::class)) {
                $pages = \App\Core\FlatFile::for('core/pages');
                $legalPage = \App\Modules\Pages\Support\SystemPages::findByKey($pages, \App\Modules\Pages\Support\SystemPages::LEGAL_NOTICE_KEY);
                $privacyPage = \App\Modules\Pages\Support\SystemPages::findByKey($pages, \App\Modules\Pages\Support\SystemPages::PRIVACY_POLICY_KEY);

                if ($legalUrl === '' && is_array($legalPage)) {
                    $legalUrl = (string) \App\Modules\Pages\Support\SystemPages::frontendUrl($legalPage);
                }

                if ($privacyUrl === '' && is_array($privacyPage)) {
                    $privacyUrl = (string) \App\Modules\Pages\Support\SystemPages::frontendUrl($privacyPage);
                }
            }

            $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($context['id'] ?? ''));
            $safeId = is_string($safeId) ? $safeId : '';

            $contentHtml = '';
            if ($showEyebrow) {
                $contentHtml .= $renderStyledText($eyebrow, 'p', 'pb-newsletter-section-eyebrow', $eyebrowStyle);
            }
            $contentHtml .= $renderStyledText($title, 'h2', 'pb-newsletter-section-title', $titleStyle);
            $contentHtml .= $renderStyledText($subtitle, 'p', 'pb-newsletter-section-subtitle', $subtitleStyle);
            if ($showBody) {
                $contentHtml .= $renderStyledParagraphs($body, 'pb-newsletter-section-body', $bodyStyle);
            }
            if ($showFeatures && $featureItems !== []) {
                $itemsHtml = [];
                foreach ($featureItems as $featureItem) {
                    $itemsHtml[] = '<li class="pb-newsletter-section-feature">'
                        . $renderStyledText($featureItem, 'span', 'pb-newsletter-section-feature-text', $featureStyle)
                        . '</li>';
                }
                $contentHtml .= '<ul class="pb-newsletter-section-features">' . implode('', $itemsHtml) . '</ul>';
            }
            if ($showProof) {
                $contentHtml .= $renderStyledText($proofLabel, 'p', 'pb-newsletter-section-proof', $proofStyle);
            }

            $consentLinks = [];
            if ($legalUrl !== '') {
                $consentLinks[] = '<a class="pb-newsletter-section-text-link" href="' . $escapeAttr($legalUrl) . '" target="_blank" rel="noopener noreferrer"><span class="pb-newsletter-section-text-link-label">' . $escape($legalLinkLabel) . '</span></a>';
            }
            if ($privacyUrl !== '') {
                $consentLinks[] = '<a class="pb-newsletter-section-text-link" href="' . $escapeAttr($privacyUrl) . '" target="_blank" rel="noopener noreferrer"><span class="pb-newsletter-section-text-link-label">' . $escape($privacyLinkLabel) . '</span></a>';
            }

            $formInnerHtml = '';
            $sourceUrl = trim((string) ($context['source_url'] ?? ''));
            if ($sourceUrl === '') {
                $sourceUrl = function_exists('url') ? (string) url('/contact') : '/contact';
            }
            $shortcodeSlug = preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) $newsletterFormSlug);
            $shortcodeSlug = is_string($shortcodeSlug) && $shortcodeSlug !== '' ? $shortcodeSlug : 'newsletter-rgpd';
            $renderContent = is_callable($helpers['render_content'] ?? null)
                ? $helpers['render_content']
                : (is_callable($helpers['sanitize_rich_text'] ?? null) ? $helpers['sanitize_rich_text'] : null);
            if (is_callable($renderContent)) {
                try {
                    $formInnerHtml = trim((string) $renderContent('[contact-form slug="' . $shortcodeSlug . '"]'));
                } catch (\Throwable) {
                    $formInnerHtml = '';
                }
            } elseif (function_exists('flatcms_render_shortcodes')) {
                try {
                    $formInnerHtml = trim((string) flatcms_render_shortcodes('[contact-form slug="' . $shortcodeSlug . '"]', ['source_url' => $sourceUrl]));
                } catch (\Throwable) {
                    $formInnerHtml = '';
                }
            }
            if ($formInnerHtml === '' || $formInnerHtml === '[contact-form slug="' . $shortcodeSlug . '"]' || str_contains($formInnerHtml, '[contact-form')) {
                $formInnerHtml = '';
            }

            if ($formInnerHtml === '') {
                $consentLinksHtml = '';
                if ($consentLinks !== []) {
                    $consentLinksHtml = '<p class="pb-newsletter-section-consent-links">'
                        . '<span>' . $escape($consentLinksPrefix) . '</span>'
                        . implode('<span class="pb-newsletter-section-consent-separator" aria-hidden="true">&middot;</span>', $consentLinks)
                        . '</p>';
                }

                $resolvedButtonLabel = $buttonLabel !== ''
                    ? $buttonLabel
                    : __('contact_form_submit_label', 'Contact');

                $formInnerHtml = '<section class="flatcms-contact-native flatcms-contact-embed">'
                    . '<form class="flatcms-contact-form flatcms-contact-native-form pb-form-contact pb-newsletter-section-form" action="' . $escapeAttr(function_exists('url') ? (string) url('/contact/send') : '/contact/send') . '" method="post" data-validation-required="' . $escapeAttr(__('contact_form_client_required_message', 'Contact')) . '">'
                    . (function_exists('csrf_field') ? (string) csrf_field() : '')
                    . '<input type="hidden" name="source_url" value="' . $escapeAttr($sourceUrl) . '">'
                    . '<input type="hidden" name="contact_form_id" value="' . $escapeAttr((string) ($selectedNewsletterForm['id'] ?? '')) . '">'
                    . '<input type="hidden" name="contact_form_slug" value="' . $escapeAttr($newsletterFormSlug) . '">'
                    . '<div class="pb-newsletter-section-form-row">'
                    . '<label class="pb-sr-only" for="' . $escapeAttr($safeId . '-newsletter-email') . '">' . $escape($emailLabel !== '' ? $emailLabel : $placeholder) . '</label>'
                    . '<input id="' . $escapeAttr($safeId . '-newsletter-email') . '" type="email" name="cf[email]" class="form-input pb-input pb-newsletter-section-input" placeholder="' . $escapeAttr($placeholder) . '" autocomplete="email" required>'
                    . '<button type="submit" class="btn btn-primary pb-btn pb-btn-primary pb-newsletter-section-submit">' . $escape($resolvedButtonLabel) . '</button>'
                    . '</div>'
                    . '<div class="pb-newsletter-section-consent">'
                    . '<label class="pb-newsletter-section-consent-label">'
                    . '<input type="checkbox" name="cf[consent_rgpd]" value="1" required>'
                    . '<span class="pb-newsletter-section-consent-text">' . $escape($consentLabel) . '</span>'
                    . '</label>'
                    . '<p class="pb-newsletter-section-consent-help">' . $escape($consentHelp) . '</p>'
                    . $consentLinksHtml
                    . '</div>'
                    . '</form>'
                    . '</section>';
            }

            $formHtml = '<div class="pb-newsletter-section-panel pb-newsletter-section-form-panel">'
                . $renderStyledText($formTitle, 'h3', 'pb-newsletter-section-form-title', $formTitleStyle)
                . $renderStyledParagraphs($formDescription, 'pb-newsletter-section-form-description', $formDescriptionStyle)
                . $formInnerHtml
                . $renderStyledParagraphs($helperText, 'pb-newsletter-section-helper', $helperTextStyle)
                . '</div>';

            $introHtml = '<div class="pb-newsletter-section-content">'
                . $contentHtml
                . '</div>';

            $bottomHtml = '<div class="pb-newsletter-section-split">'
                . '<div class="pb-newsletter-section-form-wrap">' . $formHtml . '</div>'
                . '</div>';

            $frameInner = $introHtml . $bottomHtml;

            if (trim(strip_tags($contentHtml . $formTitle . $formDescription . $buttonLabel . $helperText)) === '') {
                $frameInner = '<div class="pb-empty">' . $escape($emptyMessage !== '' ? $emptyMessage : $translate('newsletter_section_empty')) . '</div>';
            }

            $html = '<section class="pb-newsletter-section pb-newsletter-section-variant-' . $escapeAttr($variant)
                . ' pb-newsletter-section-align-' . $escapeAttr($align)
                . '">'
                . '<div class="pb-newsletter-section-frame">'
                . $frameInner
                . '</div>'
                . '</section>';

            $css = [];
            if ($safeId !== '') {
                $css = array_merge($css, self::buildWidgetDesignRules(
                    $safeId,
                    ['.pb-newsletter-section-frame'],
                    ['.pb-newsletter-section-eyebrow', '.pb-newsletter-section-title', '.pb-newsletter-section-subtitle', '.pb-newsletter-section-body', '.pb-newsletter-section-body *', '.pb-newsletter-section-feature-text', '.pb-newsletter-section-proof', '.pb-newsletter-section-form-title', '.pb-newsletter-section-form-description', '.pb-newsletter-section-helper'],
                    $useCustomDesign,
                    $designSurfaceColor,
                    $designTextColor,
                    $designBorderStyle,
                    $designBorderWidth,
                    $designBorderColor,
                    $designRadius,
                    $designShadow
                ));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-newsletter-section-eyebrow', $eyebrowStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-newsletter-section-title', $titleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-newsletter-section-subtitle', $subtitleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-newsletter-section-body', $bodyStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-newsletter-section-feature-text', $featureStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-newsletter-section-proof', $proofStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-newsletter-section-form-title', $formTitleStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-newsletter-section-form-description', $formDescriptionStyle));
                $css = array_merge($css, $buildTextStyleRules($safeId, '.pb-newsletter-section-helper', $helperTextStyle));
                $css = array_merge($css, $buildSelfAlignRules($safeId, '.pb-newsletter-section-eyebrow', (string) ($eyebrowStyle['align'] ?? 'left')));
                $css = array_merge($css, $buildSelfAlignRules($safeId, '.pb-newsletter-section-proof', (string) ($proofStyle['align'] ?? 'left')));
                $css = array_merge($css, $buildAccentColorRules($safeId, '.pb-newsletter-section-feature', $featureStyle));
                $css = array_merge($css, $buildFeatureListAlignRules($safeId, $featureStyle));
            }

            return [
                'html' => $html,
                'css' => implode("\n", $css),
            ];
        };
    }
}
