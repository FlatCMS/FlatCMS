<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

$aiAgentI18n = [
    'title' => __('assistant_title', 'AiAgent'),
    'subtitle' => __('assistant_subtitle', 'AiAgent'),
    'placeholder' => __('assistant_input_placeholder', 'AiAgent'),
    'send' => __('assistant_send', 'AiAgent'),
    'close' => __('assistant_close', 'AiAgent'),
    'apply' => __('assistant_apply', 'AiAgent'),
    'restore' => __('assistant_restore', 'AiAgent'),
    'workspaceTitle' => __('assistant_workspace_title', 'AiAgent'),
    'workspaceEmpty' => __('assistant_workspace_empty', 'AiAgent'),
    'workspaceMetaCurrent' => __('assistant_workspace_meta_current', 'AiAgent'),
    'workspaceMetaDraft' => __('assistant_workspace_meta_draft', 'AiAgent'),
    'workspaceMetaVariants' => __('assistant_workspace_meta_variants', 'AiAgent'),
    'workspaceMetaSummary' => __('assistant_workspace_meta_summary', 'AiAgent'),
    'variantsTitle' => __('assistant_variants_title', 'AiAgent'),
    'summaryTitle' => __('assistant_summary_title', 'AiAgent'),
    'contextWaiting' => __('assistant_context_waiting', 'AiAgent'),
    'greetingField' => __('assistant_greeting_field', 'AiAgent'),
    'greetingBlock' => __('assistant_greeting_block', 'AiAgent'),
    'replyField' => __('assistant_reply_field_ready', 'AiAgent'),
    'replyContent' => __('assistant_reply_content_ready', 'AiAgent'),
    'replySeo' => __('assistant_reply_seo_ready', 'AiAgent'),
    'replySummary' => __('assistant_reply_summary_ready', 'AiAgent'),
    'variantOption' => __('assistant_variant_option', 'AiAgent'),
    'applied' => __('assistant_applied', 'AiAgent'),
    'restored' => __('assistant_restored', 'AiAgent'),
    'errorEmpty' => __('assistant_error_empty_message', 'AiAgent'),
    'errorUnavailable' => __('assistant_error_unavailable', 'AiAgent'),
    'actionFieldFill' => __('assistant_action_field_fill', 'AiAgent'),
    'actionFieldImprove' => __('assistant_action_field_improve', 'AiAgent'),
    'actionFieldTranslate' => __('assistant_action_field_translate', 'AiAgent'),
    'actionBlockGenerate' => __('assistant_action_block_generate', 'AiAgent'),
    'actionBlockImprove' => __('assistant_action_block_improve', 'AiAgent'),
    'actionBlockProofread' => __('assistant_action_block_proofread', 'AiAgent'),
    'actionBlockTranslate' => __('assistant_action_block_translate', 'AiAgent'),
    'actionBlockSummary' => __('assistant_action_block_summary', 'AiAgent'),
    'actionSeoGenerate' => __('assistant_action_seo_generate', 'AiAgent'),
    'previewEmptyValue' => __('assistant_preview_empty_value', 'AiAgent'),
    'thinking' => __('assistant_thinking', 'AiAgent'),
    'summaryPagesNote' => __('assistant_summary_pages_note', 'AiAgent'),
    'summaryPostsNote' => __('assistant_summary_posts_note', 'AiAgent'),
];
?>

<div
    class="ai-agent-root"
    data-ai-agent-root
    data-endpoint="<?= e(url('/admin/ai-agent/chat')) ?>"
    data-i18n="<?= e((string) json_encode($aiAgentI18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
    data-icon-dark="<?= e(asset('images/admin/ai-agent/icon-dark.png')) ?>"
    data-icon-light="<?= e(asset('images/admin/ai-agent/icon-light.png')) ?>"
>
    <div class="ai-agent-backdrop" data-ai-agent-close hidden></div>
    <aside class="ai-agent-drawer" data-ai-agent-drawer hidden>
        <header class="ai-agent-drawer-header">
            <div class="ai-agent-drawer-hero">
                <img src="<?= e(asset('images/admin/ai-agent/agent-flatty.webp')) ?>" alt="" aria-hidden="true" class="ai-agent-drawer-hero-image">
            </div>
            <div class="ai-agent-drawer-headline">
                <p class="ai-agent-eyebrow"><?= e(__('assistant_title', 'AiAgent')) ?></p>
                <h3 class="ai-agent-title"><?= e(__('assistant_subtitle', 'AiAgent')) ?></h3>
                <p class="ai-agent-context" data-ai-agent-context-label><?= e(__('assistant_context_waiting', 'AiAgent')) ?></p>
            </div>
            <button type="button" class="ai-agent-close" data-ai-agent-close aria-label="<?= e(__('assistant_close', 'AiAgent')) ?>">
                <i class="fas fa-times"></i>
            </button>
        </header>

        <div class="ai-agent-thread" data-ai-agent-thread></div>

        <div class="ai-agent-suggestions" data-ai-agent-suggestions></div>

        <section class="ai-agent-workspace" data-ai-agent-workspace hidden>
            <div class="ai-agent-workspace-header">
                <div>
                    <h4 class="ai-agent-workspace-title"><?= e(__('assistant_workspace_title', 'AiAgent')) ?></h4>
                    <p class="ai-agent-workspace-meta" data-ai-agent-workspace-meta><?= e(__('assistant_workspace_meta_current', 'AiAgent')) ?></p>
                </div>
            </div>
            <div class="ai-agent-workspace-body" data-ai-agent-workspace-body>
                <p class="ai-agent-workspace-empty"><?= e(__('assistant_workspace_empty', 'AiAgent')) ?></p>
            </div>
            <div class="ai-agent-workspace-footer">
                <button type="button" class="btn btn-secondary" data-ai-agent-restore hidden><?= e(__('assistant_restore', 'AiAgent')) ?></button>
                <button type="button" class="btn btn-primary" data-ai-agent-apply hidden><?= e(__('assistant_apply', 'AiAgent')) ?></button>
            </div>
        </section>

        <div class="ai-agent-composer">
            <label for="ai-agent-message" class="sr-only"><?= e(__('assistant_title', 'AiAgent')) ?></label>
            <textarea
                id="ai-agent-message"
                class="form-input ai-agent-input"
                rows="3"
                data-ai-agent-input
                data-no-editor
                placeholder="<?= e(__('assistant_input_placeholder', 'AiAgent')) ?>"
            ></textarea>
            <button type="button" class="btn btn-primary ai-agent-send" data-ai-agent-send>
                <i class="fas fa-paper-plane"></i>
                <span><?= e(__('assistant_send', 'AiAgent')) ?></span>
            </button>
        </div>
    </aside>
</div>
