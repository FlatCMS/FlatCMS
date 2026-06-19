<?php
/**
 * FlatCMS - Flat-File Content Management System
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Extensions\GoogleForms\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
use App\Extensions\GoogleForms\Services\GoogleFormsApiService;
use App\Extensions\GoogleForms\Services\GoogleFormsOAuthService;
use App\Extensions\GoogleForms\Services\GoogleFormsSettingsService;
use Throwable;

final class AdminController extends BaseController
{
    private GoogleFormsSettingsService $settings;
    private GoogleFormsOAuthService $oauth;
    private GoogleFormsApiService $api;

    public function __construct()
    {
        parent::__construct();
        I18n::load('GoogleForms');

        $this->settings = new GoogleFormsSettingsService();
        $this->oauth = new GoogleFormsOAuthService();
        $this->api = new GoogleFormsApiService();
    }

    public function index(): void
    {
        $settings = $this->settings->public();
        $connection = $this->oauth->connection();
        $forms = [];
        $responses = [];
        $dashboard = [
            'total' => 0,
            'lastSubmittedTime' => null,
            'lastResponseId' => null,
        ];

        if ($this->oauth->isConnected()) {
            try {
                $forms = $this->api->listForms(false);
                $responses = $this->api->responses();
                $dashboard = $this->api->dashboard();
            } catch (Throwable $e) {
                error_log('[GoogleForms][index] ' . $e->getMessage());
                $this->session->flash('error', sprintf(__('google_forms_google_error_detail', 'GoogleForms'), $e->getMessage()));
            }
        }

        $this->render('GoogleForms/Views/admin/index', [
            'pageTitle' => __('google_forms_title', 'GoogleForms'),
            'settings' => $settings,
            'configured' => $this->settings->isConfigured(),
            'oauthStatus' => $this->settings->oauthStatus(),
            'connected' => $this->oauth->isConnected(),
            'connection' => $connection,
            'forms' => $forms,
            'responses' => $responses,
            'dashboard' => $dashboard,
            'redirectUri' => $this->redirectUri(),
        ], 'admin.main');
    }

    public function settings(): void
    {
        $this->render('GoogleForms/Views/admin/settings', [
            'pageTitle' => __('google_forms_settings', 'GoogleForms'),
            'settings' => $this->settings->public(),
            'redirectUri' => $this->redirectUri(),
        ], 'admin.main');
    }

    public function saveSettings(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $saved = $this->settings->save([
                'google_client_id' => $this->request->input('google_client_id', ''),
                'google_client_secret' => $this->request->input('google_client_secret', ''),
                'redirect_uri' => $this->redirectUri(),
            ]);

            $this->session->flash($saved ? 'success' : 'error', $saved
                ? __('google_forms_saved', 'GoogleForms')
                : __('google_forms_save_error', 'GoogleForms'));
        } catch (Throwable $e) {
            error_log('[GoogleForms][settings] ' . $e->getMessage());
            $this->session->flash('error', __('google_forms_save_error', 'GoogleForms'));
        }

        $this->redirect($this->adminPath('/admin/google-forms/settings'));
    }

    public function connect(): void
    {
        try {
            if (!$this->settings->isConfigured()) {
                $this->session->flash('error', __('google_forms_oauth_not_configured', 'GoogleForms'));
                $this->redirect($this->adminPath('/admin/google-forms/settings'));
                return;
            }

            $state = bin2hex(random_bytes(16));
            $_SESSION['google_forms_oauth_state'] = $state;

            $this->redirectExternal($this->oauth->authorizationUrl($this->redirectUri(), $state));
            return;
        } catch (Throwable $e) {
            error_log('[GoogleForms][connect] ' . $e->getMessage());
            $this->session->flash('error', sprintf(__('google_forms_google_error_detail', 'GoogleForms'), $e->getMessage()));
            $this->redirect($this->adminPath('/admin/google-forms'));
        }
    }

    public function oauthCallback(): void
    {
        $error = (string) ($_GET['error'] ?? '');
        if ($error !== '') {
            $this->session->flash('error', $error);
            $this->redirect($this->adminPath('/admin/google-forms'));
            return;
        }

        $state = (string) ($_GET['state'] ?? '');
        $expectedState = (string) ($_SESSION['google_forms_oauth_state'] ?? '');
        unset($_SESSION['google_forms_oauth_state']);

        if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
            $this->session->flash('error', __('google_forms_invalid_oauth_state', 'GoogleForms'));
            $this->redirect($this->adminPath('/admin/google-forms'));
            return;
        }

        $code = (string) ($_GET['code'] ?? '');

        if ($code === '') {
            $this->session->flash('error', __('google_forms_missing_oauth_code', 'GoogleForms'));
            $this->redirect($this->adminPath('/admin/google-forms'));
            return;
        }

        try {
            $this->oauth->handleCallback($code, $this->redirectUri());
            $this->session->flash('success', __('google_forms_connected', 'GoogleForms'));

            try {
                $this->api->listForms(true);
            } catch (Throwable $e) {
                error_log('[GoogleForms][callback:listForms] ' . $e->getMessage());
                $this->session->flash('warning', sprintf(__('google_forms_google_error_detail', 'GoogleForms'), $e->getMessage()));
            }
        } catch (Throwable $e) {
            error_log('[GoogleForms][callback] ' . $e->getMessage());
            $this->session->flash('error', sprintf(__('google_forms_google_error_detail', 'GoogleForms'), $e->getMessage()));
        }

        $this->redirect($this->adminPath('/admin/google-forms'));
    }

    public function disconnect(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $this->oauth->disconnect();
        $this->settings->clearSelectedForm();
        $this->session->flash('success', __('google_forms_disconnected', 'GoogleForms'));
        $this->redirect($this->adminPath('/admin/google-forms'));
    }

    public function refreshForms(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        try {
            $this->api->listForms(true);
            $this->session->flash('success', __('google_forms_forms_refreshed', 'GoogleForms'));
        } catch (Throwable $e) {
            error_log('[GoogleForms][refreshForms] ' . $e->getMessage());
            $this->session->flash('error', sprintf(__('google_forms_google_error_detail', 'GoogleForms'), $e->getMessage()));
        }

        $this->redirect($this->adminPath('/admin/google-forms'));
    }

    public function selectForm(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $formId = trim((string) $this->request->input('form_id', ''));

        if ($formId === '') {
            $this->session->flash('error', __('google_forms_select_form_error', 'GoogleForms'));
            $this->redirect($this->adminPath('/admin/google-forms'));
            return;
        }

        try {
            $forms = $this->api->listForms(false);
            $selected = null;

            foreach ($forms as $form) {
                if (is_array($form) && (string) ($form['id'] ?? '') === $formId) {
                    $selected = $form;
                    break;
                }
            }

            if (!$selected) {
                $forms = $this->api->listForms(true);
                foreach ($forms as $form) {
                    if (is_array($form) && (string) ($form['id'] ?? '') === $formId) {
                        $selected = $form;
                        break;
                    }
                }
            }

            if (!$selected) {
                $this->session->flash('error', __('google_forms_select_form_error', 'GoogleForms'));
                $this->redirect($this->adminPath('/admin/google-forms'));
                return;
            }

            $this->settings->saveSelectedForm($selected);
            $this->session->flash('success', __('google_forms_form_selected', 'GoogleForms'));
        } catch (Throwable $e) {
            error_log('[GoogleForms][selectForm] ' . $e->getMessage());
            $this->session->flash('error', sprintf(__('google_forms_google_error_detail', 'GoogleForms'), $e->getMessage()));
        }

        $this->redirect($this->adminPath('/admin/google-forms'));
    }

    public function syncResponses(): void
    {
        if (!$this->verifyCsrf()) {
            return;
        }

        $settings = $this->settings->all();
        $formId = trim((string) ($settings['selected_form_id'] ?? ''));

        if ($formId === '') {
            $this->session->flash('error', __('google_forms_select_form_error', 'GoogleForms'));
            $this->redirect($this->adminPath('/admin/google-forms'));
            return;
        }

        try {
            $responses = $this->api->syncResponses($formId);
            $this->session->flash('success', sprintf(__('google_forms_responses_synced', 'GoogleForms'), count($responses)));
        } catch (Throwable $e) {
            error_log('[GoogleForms][syncResponses] ' . $e->getMessage());
            $this->session->flash('error', sprintf(__('google_forms_google_error_detail', 'GoogleForms'), $e->getMessage()));
        }

        $this->redirect($this->adminPath('/admin/google-forms'));
    }


    private function redirectExternal(string $url): void
    {
        if (!str_starts_with($url, 'https://accounts.google.com/')) {
            $this->session->flash('error', __('google_forms_google_error', 'GoogleForms'));
            $this->redirect($this->adminPath('/admin/google-forms'));
            return;
        }

        if (!headers_sent()) {
            header('Location: ' . $url, true, 302);
            exit;
        }

        echo '<!doctype html><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
        echo '<p><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(__('google_forms_continue_to_google', 'GoogleForms'), ENT_QUOTES, 'UTF-8') . '</a></p>';
        exit;
    }

    private function redirectUri(): string
    {
        return $this->settings->redirectUri($this->adminUrl('/admin/google-forms/oauth/callback'));
    }

    private function adminPath(string $path): string
    {
        $fragment = '';

        if (str_contains($path, '#')) {
            [$path, $fragment] = explode('#', $path, 2);
            $fragment = '#' . $fragment;
        }

        $path = '/' . ltrim($path, '/');

        if (function_exists('url')) {
            return url($path) . $fragment;
        }

        if (defined('BASE_URL') && BASE_URL !== '') {
            $base = rtrim((string) BASE_URL, '/');

            if ($base !== '') {
                $parts = parse_url($base);
                $prefix = '';

                if (is_array($parts) && isset($parts['path'])) {
                    $prefix = rtrim((string) $parts['path'], '/');
                }

                return $prefix . $path . $fragment;
            }
        }

        return $path . $fragment;
    }

    private function adminUrl(string $path): string
    {
        $path = $this->adminPath($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host . $path;
    }
}
