<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 */

declare(strict_types=1);

namespace App\Modules\AiAgent\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Modules\AiAgent\Services\AdminAssistantService;
use App\Modules\Auth\Services\RoleService;
use RuntimeException;
use Throwable;

final class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        I18n::load('AiAgent');
    }

    public function chat(): void
    {
        if (!$this->request->isAjax()) {
            $this->json([
                'success' => false,
                'message' => __('assistant_error_unavailable', 'AiAgent'),
            ], 400);
            return;
        }

        $user = $this->session->get('user');
        $role = RoleService::normalizeRole((string) ($user['role'] ?? 'member'));
        if (!$user || !RoleService::canAccessAdmin($role)) {
            $this->json([
                'success' => false,
                'message' => __('error.unauthorized', 'Core'),
            ], 403);
            return;
        }

        $token = $this->request->header('X-CSRF-TOKEN');
        if (!$token || !$this->session->verifyToken($token)) {
            $this->json([
                'success' => false,
                'message' => __('error.csrf', 'Core'),
            ], 419);
            return;
        }

        $payload = $this->request->json();
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
        $message = trim((string) ($payload['message'] ?? ''));
        $action = trim((string) ($payload['action'] ?? ''));

        if ($message === '' && $action === '') {
            $this->json([
                'success' => false,
                'message' => __('assistant_error_empty_message', 'AiAgent'),
            ], 422);
            return;
        }

        try {
            $service = new AdminAssistantService();
            $result = $service->handle($context, $message, $action);

            $this->json([
                'success' => true,
                'intent' => $result['intent'],
                'proposal_type' => $result['proposal_type'],
                'proposal' => $result['proposal'],
                'chips' => $result['chips'],
            ]);
        } catch (RuntimeException $e) {
            $this->json([
                'success' => false,
                'message' => $this->mapRuntimeError($e->getMessage()),
            ], 422);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => __('assistant_error_unavailable', 'AiAgent'),
            ], 500);
        }
    }

    private function mapRuntimeError(string $code): string
    {
        $normalized = trim($code);
        if ($normalized === 'same_locale') {
            return __('assistant_error_same_locale', 'AiAgent');
        }
        if ($normalized === 'missing_message') {
            return __('assistant_error_empty_message', 'AiAgent');
        }
        if ($normalized === 'ai_not_configured') {
            return __('ai_error_not_configured', 'Settings');
        }
        if ($normalized === 'missing_content' || $normalized === 'missing_source_content' || $normalized === 'missing_brief') {
            return __('assistant_error_missing_context', 'AiAgent');
        }
        if ($normalized === 'provider_refusal') {
            return __('ai_error_provider_refusal', 'Settings');
        }

        return __('assistant_error_unavailable', 'AiAgent');
    }
}
