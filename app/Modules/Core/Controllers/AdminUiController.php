<?php
/**
 * FlatCMS - Flat-File Content Management System
 * Copyright (C) 2026 Alain BROYE
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * See LICENSE, LICENSING.md and TRADEMARK.md.
 *
 * File: app/Modules/Core/Controllers/AdminUiController.php
 * Version: 2.0.0-dev
 */

declare(strict_types=1);

namespace App\Modules\Core\Controllers;

use App\Core\BaseController;
use App\Helpers\IconHelper;
use App\Services\Seo\SeoAnalysisService;

final class AdminUiController extends BaseController
{
    public function icons(): void
    {
        $this->json(IconHelper::getAllIcons());
    }

    public function analyzeSeo(): void
    {
        if (!$this->request->isAjax()) {
            $this->json(['success' => false, 'message' => __('error.not_found', 'Core')], 400);
            return;
        }

        $token = (string) ($this->request->header('X-CSRF-TOKEN') ?? '');
        if ($token === '' || !$this->session->verifyToken($token)) {
            $this->json(['success' => false, 'message' => __('error.csrf', 'Core')], 419);
            return;
        }

        $analysis = (new SeoAnalysisService())->analyze($this->request->json());
        foreach ($analysis['checks'] as &$check) {
            $key = (string) ($check['messageKey'] ?? '');
            $params = is_array($check['params'] ?? null) ? $check['params'] : [];
            $check['message'] = $key !== '' ? __($key, 'Core', $params) : '';
            unset($check['messageKey'], $check['params']);
        }
        unset($check);

        $analysis['rating_label'] = __('seo_analysis_rating_' . $analysis['rating'], 'Core');

        $this->json(['success' => true, 'analysis' => $analysis]);
    }
}
