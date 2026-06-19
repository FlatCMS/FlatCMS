<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Extensions\GoogleForms\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Extensions\GoogleForms\Services\GoogleFormsSettingsService;

final class FrontController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        I18n::load('GoogleForms');
    }

    public function index(): void
    {
        $settings = (new GoogleFormsSettingsService())->public();

        $this->render('GoogleForms/Views/frontend/index', [
            'pageTitle' => __('google_forms_title', 'GoogleForms'),
            'settings' => $settings,
        ]);
    }
}
